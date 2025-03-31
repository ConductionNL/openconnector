<?php

/**
 * Service for handling export requests for database entities.
 *
 * This service enables exporting database entities as files in various formats,
 * determined by the `Accept` header of the request. It retrieves the appropriate
 * data from mappers and generates responses or downloadable files.
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
use GuzzleHttp\Client;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use OCA\OpenRegister\Db\Register;
use OCA\OpenRegister\Db\RegisterMapper;
use OCA\OpenRegister\Db\Schema;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Service\DoesNotExistException;
use OCA\OpenRegister\Service\GuzzleHttp;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IURLGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Service for handling export requests for database entities.
 *
 */
class ExportService
{
    /**
     * Constructor for ExportService.
     *
     * Initializes the service with required dependencies for generating exports.
     *
     * @param IURLGenerator $urlGenerator  The URL generator service for creating absolute URLs
     * @param ObjectService $objectService The object service for retrieving object mappers
     *
     * @return void
     */
    public function __construct(
        private readonly IURLGenerator $urlGenerator,
        private readonly ObjectService $objectService
    ) {
    }

    /**
     * Handles an export request to create a downloadable file for an object.
     *
     * This method determines the export format based on the Accept header,
     * validates the object type, retrieves the object, and generates the appropriate
     * file for download.
     *
     * @param string $objectType The type of object to export
     * @param string $id         The id of the object to export
     * @param string $accept     The Accept-header from the export request
     *
     * @return JSONResponse The error response if export cannot be completed
     *
     * @throws Exception When an unexpected error occurs during export
     */
    public function export(string $objectType, string $id, string $accept): JSONResponse
    {
        // Determine the type based on the Accept header
        $type = match (true) {
            str_contains($accept, 'application/json') => 'json',
            $accept === 'application/yaml' => 'yaml',
            default => null
        };

        // If the type is not supported, return an error response
        if ($type === null) {
            return new JSONResponse(['error' => "The Accept type $accept is not supported."], 400);
        }

        try {
            // Get the appropriate mapper for the object type
            $mapper = $this->objectService->getMapper($objectType);
        } catch (InvalidArgumentException | NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return new JSONResponse(['error' => "Could not find a mapper for this type: " . $objectType], 400);
        }

        // Check if the object type is allowed to be exported
        if (in_array(
            strtolower($objectType),
            [
                'calllog',
                'consumer',
                'event',
                'eventmessage',
                'joblog',
                'synchronizationcontract',
                'synchronizationcontractlog',
            ]
        )) {
            return new JSONResponse(['error' => "It is not allowed to export objects of type: " . $objectType], 400);
        }

        try {
            // Find the object by its ID
            $object = $mapper->find($id);
        } catch (Exception $exception) {
            return new JSONResponse(['error' => "Could not find an object with this id: " . $id], 400);
        }

        // Prepare the object array for export
        $objectArray = $this->prepareObject($objectType, $mapper, $object);

        // Generate the filename for the export
        $filename = ucfirst($objectType) . '-' . ($objectArray['name'] ?? $objectType) . '-v' .
            ($objectArray['version'] ?? '0.0.0');

        // Encode the object array to the appropriate format
        $dataString = $this->encode($objectArray, $accept);

        // Generate the downloadable file response
        $this->download($dataString, $filename, $type);

        // This line should never be reached due to the exit in download()
        // but is needed for static analysis
        return new JSONResponse(['error' => 'Unexpected error occurred'], 500);
    }

    /**
     * Prepares a PHP array with all data of the object for the downloadable file.
     *
     * Transforms the object data into a format suitable for export, including
     * adding necessary JSON-LD context information and removing internal fields.
     *
     * @param string $objectType The type of object to export
     * @param mixed  $mapper     The mapper of the correct object type to export
     * @param Entity $object     The object we want to export
     *
     * @return array<string, mixed> The prepared object array data ready for export
     *
     * @phpstan-return array<string, mixed>
     * @psalm-return array<string, mixed>
     */
    private function prepareObject(string $objectType, mixed $mapper, Entity $object): array
    {
        // Convert object to array representation
        $objectArray = $object->jsonSerialize();

        if (!empty($objectArray['reference'])) {
            $url = $objectArray['reference'];
        } else {
            // Generate a reference URL if none exists
            $url = $objectArray['reference'] = $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->linkToRoute(
                    'openconnector.' . ucfirst($objectType) . 's.show',
                    ['id' => $object->getId()]
                )
            );

            // Remove internal fields that shouldn't be part of export
            unset(
                $objectArray['id'],
                $objectArray['uuid'],
                $objectArray['created'],
                $objectArray['updated'],
                $objectArray['dateCreated'],
                $objectArray['dateModified']
            );

            // Update the object with the new reference
            $mapper->updateFromArray($object->getId(), $objectArray);
        }

        // Prepare JSON-LD default properties for semantic web compatibility
        $jsonLdDefault = [
            '@context' => [
                "schema"   => "http://schema.org",
                "register" => "Not Implemented",
            ],
            '@type'    => $objectType,
            '@id'      => $url,
        ];

        // Merge JSON-LD properties with object data
        return array_merge($jsonLdDefault, $objectArray);
    }

    /**
     * Encodes an object array to a data string in the requested format.
     *
     * Converts the PHP array to either JSON or YAML format based on the Accept header.
     * If the specified format fails, attempts to use an alternative format.
     *
     * @param array<string, mixed> $objectArray The object array data to encode
     * @param string|null          $type        The MIME type from the accept header
     *
     * @return string|null The encoded data string or null if encoding fails
     *
     * @phpstan-return string|null
     * @psalm-return string|null
     */
    private function encode(array $objectArray, ?string $type): ?string
    {
        // Choose encoding strategy based on the requested type
        switch ($type) {
            case 'application/json':
                // Encode to pretty-printed JSON
                $dataString = json_encode($objectArray, JSON_PRETTY_PRINT);
                break;
            case 'application/yaml':
                // Encode to YAML format
                $dataString = Yaml::dump($objectArray);
                break;
            default:
                // Fall back to JSON if type is not specified or recognized
                $dataString = json_encode($objectArray, JSON_PRETTY_PRINT);
                // If JSON encoding fails, try YAML as a fallback
                if ($dataString === false) {
                    try {
                        $dataString = Yaml::dump($objectArray);
                    } catch (Exception $exception) {
                        $dataString = null;
                    }
                }
                break;
        }

        // Return null if encoding failed
        if ($dataString === null || $dataString === false) {
            return null;
        }

        return $dataString;
    }

    /**
     * Generates a downloadable file response and terminates script execution.
     *
     * Creates a temporary file with the encoded data, sets appropriate HTTP headers
     * for download, sends the file to the client, and cleans up resources.
     *
     * @param string $dataString The data to create a file with of the given $type
     * @param string $filename   The filename, without extension
     * @param string $type       The file type extension, defaults to 'json'
     *
     * @return void Does not return as script execution is terminated
     *
     * @phpstan-return never
     * @psalm-return never
     */
    #[NoReturn]
    private function download(string $dataString, string $filename, string $type = 'json'): void
    {
        // Define the file name and path for the temporary file
        $fileName = "$filename.$type";
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        // Create and write the data to the file
        file_put_contents($filePath, $dataString);

        // Set headers to download the file
        header("Content-Type: application/$type");
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));

        // Output the file contents
        readfile($filePath);

        // Clean up: delete the temporary file
        unlink($filePath);

        // Ensure no further script execution
        exit;
    }
}
