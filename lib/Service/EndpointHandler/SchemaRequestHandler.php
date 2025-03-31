<?php
/**
 * This file is part of the OpenConnector app.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service\EndpointHandler;

use Exception;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Service\ObjectService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

/**
 * Handler for schema-based endpoint requests.
 *
 * This class handles requests to schema-based endpoints, which connect to a schema
 * within a register to perform CRUD operations.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class SchemaRequestHandler implements RequestHandlerInterface
{
    /**
     * Constants for parameters that should be unset during request processing.
     *
     * @var string[]
     */
    private const UNSET_PARAMETERS = [
        '_parameters',
        '_utility',
        '_method',
        '_headers',
        '_route',
        '_path'
    ];

    /**
     * Constructor.
     *
     * @param ObjectService      $objectService      Service for handling object operations.
     * @param MappingService     $mappingService     Service for data mapping operations.
     * @param IURLGenerator      $urlGenerator       URL generator for creating endpoint URLs.
     * @param ContainerInterface $containerInterface Container for service resolution.
     * @param LoggerInterface    $logger             Logger for error logging.
     *
     * @return void
     */
    public function __construct(
        private readonly ObjectService $objectService,
        private readonly MappingService $mappingService,
        private readonly IURLGenerator $urlGenerator,
        private readonly ContainerInterface $containerInterface,
        private readonly LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Determines if this handler can handle the given endpoint.
     *
     * @param Endpoint $endpoint The endpoint to check.
     *
     * @return bool True if this handler can handle the endpoint, false otherwise.
     */
    public function canHandle(Endpoint $endpoint): bool
    {
        return $endpoint->getTargetType() === 'register/schema';
    }//end canHandle()

    /**
     * Handles the request to the given endpoint.
     *
     * @param Endpoint  $endpoint The endpoint configuration.
     * @param IRequest  $request  The incoming request.
     * @param string    $path     The path from the request.
     * @param array     $data     Additional data needed for handling the request.
     *
     * @return JSONResponse Response containing the result of the request.
     *
     * @throws DoesNotExistException When a requested object doesn't exist.
     * @throws MultipleObjectsReturnedException When multiple objects are returned unexpectedly.
     * @throws LoaderError If there is an error loading templates.
     * @throws SyntaxError If there is a syntax error in templates.
     * @throws ContainerExceptionInterface If there is a container error.
     * @throws NotFoundExceptionInterface If a service is not found.
     * @throws Exception For other errors during processing.
     *
     * @psalm-param array<string, mixed> $data
     */
    public function handle(Endpoint $endpoint, IRequest $request, string $path, array $data): JSONResponse
    {
        // Get request method
        $method = $request->getMethod();
        $target = explode('/', $endpoint->getTargetId());

        $register = $target[0];
        $schema = $target[1];

        $mapper = $this->objectService->getMapper(schema: $schema, register: $register);

        $parameters = $request->getParams();

        if ($endpoint->getInputMapping() !== null) {
            $inputMapping = $this->mappingService->getMapping($endpoint->getInputMapping());
            $parameters = $this->mappingService->executeMapping(mapping: $inputMapping, input: $parameters);
        }

        $pathParams = $this->getPathParameters($endpoint->getEndpointArray(), $path);
        if (isset($pathParams['id']) === true) {
            $parameters['id'] = $pathParams['id'];
        }
        
        foreach (self::UNSET_PARAMETERS as $parameter) {
            unset($parameters[$parameter]);
        }

        $status = 200;

        $headers = $request->getHeader('Accept-Crs') === '' ? [] : ['Content-Crs' => $request->getHeader('Accept-Crs')];

        // Route to appropriate ObjectService method based on HTTP method
        try {
            switch ($method) {
                case 'GET':
                    return new JSONResponse(
                        $this->getObjects(
                            mapper: $mapper,
                            parameters: $parameters,
                            pathParams: $pathParams,
                            status: $status
                        ),
                        statusCode: $status,
                        headers: $headers
                    );
                case 'POST':
                    return new JSONResponse(
                        $this->replaceInternalReferences(
                            mapper: $mapper,
                            serializedObject: $mapper->createFromArray(object: $parameters)
                        )
                    );
                case 'PUT':
                    return new JSONResponse(
                        $this->replaceInternalReferences(
                            mapper: $mapper,
                            serializedObject: $mapper->updateFromArray($parameters['id'], $request->getParams(), true, false)
                        )
                    );
                case 'PATCH':
                    return new JSONResponse(
                        $this->replaceInternalReferences(
                            mapper: $mapper,
                            serializedObject: $mapper->updateFromArray($parameters['id'], $request->getParams(), true, true)
                        )
                    );
                case 'DELETE':
                    if (isset($parameters['id']) === false) {
                        return new JSONResponse(data: ['error' => 'No id given to delete'], statusCode: 400);
                    }

                    if ($mapper->delete(['id' => $parameters['id']]) !== true) {
                        return new JSONResponse(
                            data: ['error' => sprintf('Something went wrong deleting object: %s', $parameters['id'])],
                            statusCode: 500
                        );
                    }

                    return new JSONResponse(statusCode: 200);

                default:
                    throw new Exception('Unsupported HTTP method');
            }
        } catch (Exception $exception) {
            if (in_array(get_class($exception), ['OCA\OpenRegister\Exception\ValidationException', 'OCA\OpenRegister\Exception\CustomValidationException']) === true) {
                return $mapper->handleValidationException(exception: $exception);
            }

            throw $exception;
        }
    }//end handle()

    /**
     * Parses a path to get the parameters in a path.
     *
     * Extracts path parameters from an endpoint's path based on a template pattern.
     *
     * @param array  $endpointArray The endpoint array from an endpoint object.
     * @param string $path          The path called by the client.
     *
     * @return array The parsed path with the fields having the correct name.
     *
     * @psalm-param array<int, string> $endpointArray
     * @psalm-return array<string, string>
     */
    private function getPathParameters(array $endpointArray, string $path): array
    {
        $pathParts = explode(separator: '/', string: $path);

        $endpointArrayNormalized = array_map(
            function ($item) {
                return str_replace(
                    search: ['{{', '{{ ', '}}', '}}'],
                    replace: '',
                    subject: $item
                );
            },
            $endpointArray
        );

        try {
            $pathParams = array_combine(
                keys: $endpointArrayNormalized,
                values: $pathParts
            );
        } catch (\ValueError $error) {
            array_pop($endpointArrayNormalized);
            $pathParams = array_combine(
                keys: $endpointArrayNormalized,
                values: $pathParts
            );
        }

        return $pathParams;
    }//end getPathParameters()

    /**
     * Replaces internal pointers with urls and ids by endpoint urls.
     *
     * @param QBMapper|\OCA\OpenRegister\Service\ObjectService $mapper           The mapper used to find objects.
     * @param \OCA\OpenRegister\Db\ObjectEntity|null           $object           The object to substitute pointers in.
     * @param array                                            $serializedObject The serialized object (if the object itself is not available).
     *
     * @return array|null The serialized object including substituted pointers.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-param array<string, mixed>|null $serializedObject
     * @psalm-return array<string, mixed>|null
     */
    private function replaceInternalReferences(
        QBMapper|\OCA\OpenRegister\Service\ObjectService $mapper,
        ?\OCA\OpenRegister\Db\ObjectEntity $object = null,
        ?array $serializedObject = []
    ): ?array {
        if ($serializedObject === [] && $object !== null) {
            $serializedObject = $object->jsonSerialize();
        } else if ($serializedObject === null) {
            return $serializedObject;
        } else {
            $object = $mapper->find($serializedObject['id']);
        }

        $uses = $object->getRelations();
        $useUrls = [];

        $uuidToUrlMap = [];
        // Initiate schemaMapper here once for performance
        $schemaMapper = $this->containerInterface->get('OCA\OpenRegister\Db\SchemaMapper');
        $schema = $schemaMapper->find($object->getSchema());

        // Find property names that are uris
        $validUriProperties = [];
        foreach ($schema->getProperties() as $propertyName => $property) {
            if (isset($property['objectConfiguration']['handling']) === true && $property['objectConfiguration']['handling'] === 'uri') {
                $validUriProperties[] = $propertyName;
            }
        }

        foreach ($uses as $key => $use) {
            $baseKey = explode('.', $key, 2)[0];
            // Skip if the key (or its base form) is not in the valid URI properties
            if (in_array(needle: $baseKey, haystack: $validUriProperties) === false) {
                continue;
            }

            if (Uuid::isValid(uuid: $use) === true) {
                $useId = $use;
            } elseif (
                str_contains(haystack: $use, needle: 'localhost') === true
                || str_contains(haystack: $use, needle: 'nextcloud.local') === true
                || str_contains(haystack: $use, needle: $this->urlGenerator->getBaseUrl()) === true
            ) {
                $explodedUrl = explode(separator: '/', string: $use);
                $useId = end($explodedUrl);
            } else {
                unset($uses[$key]);
                continue;
            }

            try {
                $generatedUrl = $this->generateEndpointUrl(id: $useId, parentIds: [$object->getUuid()], schemaMapper: $schemaMapper);
                $uuidToUrlMap[$useId] = $generatedUrl;
                $useUrls[] = $generatedUrl;
            } catch (Exception $exception) {
                continue;
            }
        }

        // Add self object URI mapping
        $uuidToUrlMap[$object->getUuid()] = $this->generateEndpointUrl(id: $object->getUuid(), schemaMapper: $schemaMapper);

        // Replace UUIDs in serializedObject recursively
        $serializedObject = $this->replaceUuidsInArray($serializedObject, $uuidToUrlMap);

        return $serializedObject;
    }//end replaceInternalReferences()

    /**
     * Recursively replaces UUIDs in an array with their corresponding URLs.
     *
     * @param array $data         The input array that may contain UUIDs.
     * @param array $uuidToUrlMap An associative array mapping UUIDs to URLs.
     * @param bool  $isRelatedObject Are we currently iterating through a related object.
     *
     * @return array The modified array with UUIDs replaced by URLs.
     *
     * @psalm-param array<string, mixed> $data
     * @psalm-param array<string, string> $uuidToUrlMap
     * @psalm-return array<string, mixed>
     */
    private function replaceUuidsInArray(array $data, array $uuidToUrlMap, ?bool $isRelatedObject = false): array
    {
        foreach ($data as $key => $value) {
            // Don't check @self
            if ($key === '@self') {
                continue;
            }

            // If in array of multiple objects and has id
            if (is_array($value) === true && isset($value['id']) === true && isset($uuidToUrlMap[$value['id']]) === true) {
                $data[$key] = $uuidToUrlMap[$value['id']];
                continue;
            }

            // If related object and has id
            if ($isRelatedObject === true && $key === 'id' && isset($uuidToUrlMap[$value]) === true) {
                $data[$key] = $uuidToUrlMap[$value];
                continue;
            }

            // Never replace 'id' or 'uuid' fields but only in previous checks
            if ($key === 'id' || $key === 'uuid') {
                continue;
            }

            if (is_array($value) === true && empty($value) === false) {
                $data[$key] = $this->replaceUuidsInArray(data: $value, uuidToUrlMap: $uuidToUrlMap, isRelatedObject: true);
            } else if (is_string($value) === true && isset($uuidToUrlMap[$value]) === true) {
                $data[$key] = $uuidToUrlMap[$value];
            }
        }

        return $data;
    }//end replaceUuidsInArray()

    /**
     * Fetch objects for the endpoint.
     *
     * @param \OCA\OpenRegister\Service\ObjectService|QBMapper $mapper     The mapper for the object type.
     * @param array                                            $parameters The parameters from the request.
     * @param array                                            $pathParams The parameters in the path.
     * @param int                                              $status     The HTTP status to return.
     *
     * @return Entity|array The object(s) confirming to the request.
     *
     * @throws Exception
     *
     * @psalm-param array<string, mixed> $parameters
     * @psalm-param array<string, string> $pathParams
     * @psalm-return Entity|array<string, mixed>
     */
    private function getObjects(
        \OCA\OpenRegister\Service\ObjectService|QBMapper $mapper,
        array $parameters,
        array $pathParams,
        int &$status = 200
    ): Entity|array {
        if (isset($pathParams['id']) === true && $pathParams['id'] === end($pathParams)) {
            return $this->replaceInternalReferences(mapper: $mapper, object: $mapper->find($pathParams['id']));
        } else if (isset($pathParams['id']) === true) {
            // Set the array pointer to the location of the id, so we can fetch the parameters further down the line in order
            while (prev($pathParams) !== $pathParams['id']) {
            }

            $property = next($pathParams);

            if (next($pathParams) !== false) {
                $id = pos($pathParams);
            }

            $main = $mapper->findByUuid($pathParams['id'])->getObject();
            $ids = $main[$property];

            if ($ids === null || empty($ids) === true) {
                $returnArray = [
                    'count' => 0,
                    'results' => [],
                ];

                return $returnArray;
            }

            if (isset($id) === true && in_array(needle: $id, haystack: $ids) === true) {
                $object = $mapper->find($id);

                return $this->replaceInternalReferences(mapper: $mapper, object: $object);
            } else if (isset($id) === true) {
                $status = 404;
                return ['error' => 'not found', 'message' => "the subobject with id $id does not exist"];
            }

            $results = $mapper->findMultiple($ids);
            foreach ($results as $key => $result) {
                $results[$key] = $this->replaceInternalReferences(mapper: $mapper, object: $result);
            }

            $returnArray = [
                'count' => count($results),
                'results' => $results,
            ];

            return $returnArray;
        }

        $result = $mapper->findAllPaginated(requestParams: $parameters);

        $result['results'] = array_map(function ($object) use ($mapper) {
            return $this->replaceInternalReferences(mapper: $mapper, object: $object);
        }, $result['results']);

        $returnArray = [
            'count' => $result['total'],
        ];

        if ($result['page'] < $result['pages']) {
            $parameters['page'] = $result['page'] + 1;
            $parameters['_path'] = implode('/', $pathParams);

            $returnArray['next'] = $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->linkToRoute(
                    routeName: 'openconnector.endpoints.handlepath',
                    arguments: $parameters
                )
            );
        }
        
        if ($result['page'] > 1) {
            $parameters['page'] = $result['page'] - 1;
            $parameters['_path'] = implode('/', $pathParams);

            $returnArray['previous'] = $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->linkToRoute(
                    routeName: 'openconnector.endpoints.handlepath',
                    arguments: $parameters
                )
            );
        }

        $returnArray['results'] = $result['results'];

        return $returnArray;
    }//end getObjects()

    /**
     * Generates url based on available endpoints for the object type.
     *
     * @param string                             $id           The id of the object to generate an url for.
     * @param \OCA\OpenRegister\Db\SchemaMapper $schemaMapper The mapper to get schemas.
     * @param int|null                           $register     The register of the object (aids performance).
     * @param int|null                           $schema       The schema of the object (aids performance).
     * @param array                              $parentIds    The ids of the main object on subobjects.
     *
     * @return string The generated url.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-param array<int, string> $parentIds
     */
    public function generateEndpointUrl(
        string $id,
        \OCA\OpenRegister\Db\SchemaMapper $schemaMapper,
        ?int $register = null,
        ?int $schema = null,
        array $parentIds = []
    ): string {
        if ($register === null) {
            $object = $this->objectService->getOpenRegisters()->getMapper('objectEntity')->find($id);
            $register = $object->getRegister();
            $schema = $object->getSchema();
        }

        $target = "$register/$schema";
        $endpoints = $this->objectService->getMapper('endpoint')->findAll(filters: ['target_id' => $target, 'method' => 'GET']);

        if (count($endpoints) === 0) {
            return $id;
        }

        $endpoint = $endpoints[0];
        $location = $endpoint->getEndpointArray();

        // Determine schema title (lowercased)
        $schemaTitle = strtolower($schemaMapper->find($schema)->getTitle());

        // Use first parentId if available
        $parentId = $parentIds[0] ?? null;

        // Make sure we are dealing with a sub endpoint
        $isSubEndpoint = false;
        foreach ($location as $key => $part) {
            if (preg_match('#{{([^}]+)}}#', $part, $matches)) {
                $placeholder = trim($matches[1]);
                if ($placeholder === "{$schemaTitle}_id") {
                    $isSubEndpoint = true;
                }
            }
        }

        foreach ($location as $key => $part) {
            if (preg_match('#{{([^}]+)}}#', $part, $matches)) {
                $placeholder = trim($matches[1]);

                if ($placeholder === 'id' && $parentId !== null && $isSubEndpoint === true) {
                    // Replace {{id}} with parent id if set
                    $location[$key] = $parentId;
                } else if ($placeholder === 'id') {
                    // Otherwise, replace {{id}} with current object id
                    $location[$key] = $id;
                } else if ($placeholder === "{$schemaTitle}_id") {
                    // Replace {{schematitle_id}} with object id
                    $location[$key] = $id;
                }
            }
        }

        $path = implode(separator: '/', array: $location);
        return $this->urlGenerator->getBaseUrl().'/apps/openconnector/api/endpoint/'.$path;
    }//end generateEndpointUrl()
} 