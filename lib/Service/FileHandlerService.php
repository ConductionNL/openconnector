<?php

/**
 * Service for handling file operations during synchronization.
 *
 * This class provides methods for fetching, writing, and tagging files
 * during synchronization processes. It manages interactions with the
 * file system, API calls to external sources, and system tag management.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use OCA\OpenConnector\Db\CallLog;
use OCA\OpenConnector\Db\Source;
use OC\User\NoUserException;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\LockedException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

/**
 * Service for handling file operations during synchronization.
 */
class FileHandlerService
{
    /**
     * Type constant for file tagging
     *
     * @var string
     */
    private const FILE_TAG_TYPE = 'files';


    /**
     * Constructor for FileHandlerService.
     *
     * Initializes the service with required dependencies for file operations.
     *
     * @param CallService            $callService        The service for making HTTP calls to external APIs
     * @param StorageService         $storageService     The service for file storage operations
     * @param ContainerInterface     $containerInterface The DI container for accessing other services
     * @param ISystemTagManager      $systemTagManager   The manager for system tags
     * @param ISystemTagObjectMapper $systemTagMapper    The mapper for associating tags with objects
     *
     * @return void
     */
    public function __construct(
        private readonly CallService $callService,
        private readonly StorageService $storageService,
        private readonly ContainerInterface $containerInterface,
        private readonly ISystemTagManager $systemTagManager,
        private readonly ISystemTagObjectMapper $systemTagMapper
    ) {

    }//end __construct()


    /**
     * Write a file to the filesystem.
     *
     * Creates a file in the specified object's folder with the given content.
     * Uses the ObjectService to locate the proper folder for the object.
     *
     * @param string $fileName The filename to create
     * @param string $content  The file content to write
     * @param string $objectId The object ID associated with this file
     *
     * @return File|bool The created file object or false on failure
     *
     * @throws ContainerExceptionInterface When container error occurs
     * @throws NotFoundExceptionInterface  When service not found in container
     * @throws GenericFileException        When a generic file error occurs
     * @throws LockedException             When file is locked
     * @throws Exception                   When any other error occurs
     */
    public function writeFile(
        string $fileName,
        string $content,
        string $objectId
    ): mixed {
        // Get the object service and find the object.
        $objectService = $this->containerInterface->get('OCA\OpenRegister\Service\ObjectService');
        $object        = $objectService->getOpenRegisters()
            ->getMapper('objectEntity')
            ->find($objectId);

        try {
            // Write the file to the object's folder.
            $file = $this->storageService->writeFile(
                path: $object->getFolder(),
                fileName: $fileName,
                content: $content
            );
        } catch (NotFoundException | NotPermittedException | NoUserException $e) {
            return false;
        }

        return $file;

    }//end writeFile()


    /**
     * Fetch a file from a source and store it.
     *
     * Retrieves a file from an external source, writes it to the filesystem,
     * and optionally attaches tags to the file.
     *
     * @param Source              $source   The source configuration
     * @param string              $endpoint The file endpoint/URL path
     * @param array<string,mixed> $config   The action configuration
     * @param string              $objectId The object ID
     * @param array<string>|null  $tags     Optional tags to assign to the file, defaults to empty array
     * @param string|null         $filename Optional filename, derived from response if not provided
     *
     * @return string The file URL or base64 content
     *
     * @throws ContainerExceptionInterface When container error occurs
     * @throws NotFoundExceptionInterface  When service not found in container
     * @throws GenericFileException        When a generic file error occurs
     * @throws LockedException             When file is locked
     * @throws GuzzleException             When HTTP request fails
     * @throws LoaderError                 When template loading fails
     * @throws SyntaxError                 When template syntax error occurs
     * @throws \OCP\DB\Exception           When database error occurs
     * @throws Exception                   When any other error occurs
     */
    public function fetchFile(
        Source $source,
        string $endpoint,
        array $config,
        string $objectId,
        ?array $tags=[],
        ?string $filename=null
    ): string {
        $originalEndpoint = $endpoint;

        // Clean endpoint if it contains source location.
        if (str_contains($endpoint, $source->getLocation()) === true) {
            $endpoint = substr($endpoint, strlen($source->getLocation()));
        }

        // Make API call.
        $result   = $this->callService->call(
            source: $source,
            endpoint: $endpoint,
            method: ($config['method'] ?? 'GET'),
            config: ($config['sourceConfiguration'] ?? [])
        );
        $response = $result->getResponse();

        // Return base64 content if write is disabled.
        if (isset($config['write']) === true && $config['write'] === true) {
            return base64_encode($response['body']);
        }

        // Get filename from response if not provided.
        if ($filename === null) {
            $filename = $this->getFilenameFromHeaders(
                response: $response,
                result: $result
            );
        }

        // Write file using ObjectService.
        $objectService = $this->containerInterface->get('OCA\OpenRegister\Service\ObjectService');

        // Determine if autoShare is enabled.
        $autoShare = false;
        if (isset($config['autoShare']) === true) {
            $autoShare = $config['autoShare'];
        }

        $file = $objectService->addFile(
            object: $objectId,
            fileName: $filename,
            base64Content: $response['body'],
            share: $autoShare
        );

        // Attach tags to file.
        $tags[] = "object:$objectId";
        if ($file instanceof File && count($tags) > 0) {
            $this->attachTagsToFile(fileId: $file->getId(), tags: $tags);
        }

        return $originalEndpoint;

    }//end fetchFile()


    /**
     * Extract filename from response headers.
     *
     * Tries to determine the filename from various sources in the response:
     * 1. Content-Disposition header
     * 2. URL path
     * 3. Adds extension from Content-Type if needed
     *
     * @param array<string,mixed> $response The response data containing headers
     * @param CallLog             $result   The call log containing request data
     *
     * @return string|null The extracted filename or null if not determinable
     */
    public function getFilenameFromHeaders(array $response, CallLog $result): ?string
    {
        // Try Content-Disposition header first.
        if (isset($response['headers']['Content-Disposition']) === true
            && str_contains($response['headers']['Content-Disposition'][0], 'filename') === true
        ) {
            $explodedContentDisposition = explode(
                '=',
                $response['headers']['Content-Disposition'][0]
            );
            return trim($explodedContentDisposition[1], '"');
        }

        // Fall back to URL path and content type.
        $parsedUrl = parse_url($result->getRequest()['url']);
        $path      = explode('/', $parsedUrl['path']);
        $filename  = end($path);

        // Add extension from content type if missing.
        if (count(explode('.', $filename)) === 1
            && (isset($response['headers']['Content-Type']) === true
            || isset($response['headers']['content-type']) === true)
        ) {
            // Determine which content-type header to use.
            $contentType = '';
            if (isset($response['headers']['Content-Type']) === true) {
                $contentType = $response['headers']['Content-Type'][0];
            } else {
                $contentType = $response['headers']['content-type'][0];
            }

            $explodedMimeType = explode(
                '/',
                explode(';', $contentType)[0]
            );

            $filename = $filename.''.end($explodedMimeType);
        }

        return $filename;

    }//end getFilenameFromHeaders()


    /**
     * Attach tags to a file.
     *
     * Creates tags if they don't exist and assigns them to the specified file.
     * Tags are used for organizing and filtering files in the system.
     *
     * @param string        $fileId The file ID to tag
     * @param array<string> $tags   The tag names to attach
     *
     * @return void
     *
     * @throws TagNotFoundException When a tag cannot be found or created
     */
    public function attachTagsToFile(string $fileId, array $tags): void
    {
        $tagIds = [];
        foreach ($tags as $tagName) {
            try {
                // Try to get existing tag.
                $tag = $this->systemTagManager->getTag(
                    tagName: $tagName,
                    userVisible: true,
                    userAssignable: true
                );
            } catch (TagNotFoundException $exception) {
                // Create tag if it doesn't exist.
                $tag = $this->systemTagManager->createTag(
                    tagName: $tagName,
                    userVisible: true,
                    userAssignable: true
                );
            }

            $tagIds[] = $tag->getId();
        }

        // Assign collected tag IDs to the file.
        $this->systemTagMapper->assignTags(
            objId: $fileId,
            objectType: self::FILE_TAG_TYPE,
            tagIds: $tagIds
        );

    }//end attachTagsToFile()


}//end class
