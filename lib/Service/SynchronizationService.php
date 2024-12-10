<?php

namespace OCA\OpenConnector\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JWadhams\JsonLogic;
use OCA\OpenConnector\Db\CallLog;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\MappignMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\MappingService;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Uid\Uuid;
use OCP\AppFramework\Db\DoesNotExistException;
use Adbar\Dot;

use Psr\Container\ContainerInterface;
use DateInterval;
use DateTime;
use OCA\OpenConnector\Db\MappingMapper;
use OCP\AppFramework\Http\NotFoundResponse;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

class SynchronizationService
{
    private CallService $callService;
    private MappingService $mappingService;
    private ContainerInterface $containerInterface;
    private SynchronizationMapper $synchronizationMapper;
    private SourceMapper $sourceMapper;
    private MappingMapper $mappingMapper;
    private SynchronizationContractMapper $synchronizationContractMapper;
    private SynchronizationContractLogMapper $synchronizationContractLogMapper;
    private ObjectService $objectService;
    private Source $source;

    const EXTRA_DATA_CONFIGS_LOCATION          = 'extraDataConfigs';
    const EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION = 'dynamicEndpointLocation';
    const EXTRA_DATA_STATIC_ENDPOINT_LOCATION  = 'staticEndpoint';
    const KEY_FOR_EXTRA_DATA_LOCATION          = 'keyToSetExtraData';
    const MERGE_EXTRA_DATA_OBJECT_LOCATION     = 'mergeExtraData';


	public function __construct(
		CallService $callService,
		MappingService $mappingService,
		ContainerInterface $containerInterface,
        SourceMapper $sourceMapper,
        MappingMapper $mappingMapper,
		SynchronizationMapper $synchronizationMapper,
		SynchronizationContractMapper $synchronizationContractMapper,
        SynchronizationContractLogMapper $synchronizationContractLogMapper,
	) {
		$this->callService = $callService;
		$this->mappingService = $mappingService;
		$this->containerInterface = $containerInterface;
		$this->synchronizationMapper = $synchronizationMapper;
		$this->mappingMapper = $mappingMapper;
		$this->synchronizationContractMapper = $synchronizationContractMapper;
        $this->synchronizationContractLogMapper = $synchronizationContractLogMapper;
        $this->sourceMapper = $sourceMapper;
	}

	/**
	 * Synchronizes a given synchronization (or a complete source).
	 *
	 * @param Synchronization $synchronization
	 * @param bool|null $isTest False by default, currently added for synchronziation-test endpoint
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws GuzzleException
	 * @throws LoaderError
	 * @throws SyntaxError
	 * @throws MultipleObjectsReturnedException
	 * @throws \OCP\DB\Exception
	 */
    public function synchronize(Synchronization $synchronization, ?bool $isTest = false): array
	{
        if (empty($synchronization->getSourceId()) === true) {
			$log = [
				'synchronizationId' => $synchronization->getId(),
				'synchronizationContractId' => 0,
				'message' => 'sourceId of synchronization cannot be empty. Canceling synchronization...',
				'created' => new DateTime(),
				'expires' => new DateTime('+1 day')
			];

			$this->synchronizationContractLogMapper->createFromArray($log);
            throw new Exception('sourceId of synchronization cannot be empty. Canceling synchronization...');
        }

		try {
			$objectList = $this->getAllObjectsFromSource(synchronization: $synchronization, isTest: $isTest);
		} catch (TooManyRequestsHttpException $e) {
			$rateLimitException = $e;
		}

        foreach ($objectList as $key => $object) {
            // If the source configuration contains a dot notation for the id position, we need to extract the id from the source object
            $originId = $this->getOriginId($synchronization, $object);

            // Get the synchronization contract for this object
            $synchronizationContract = $this->synchronizationContractMapper->findSyncContractByOriginId(synchronizationId: $synchronization->id, originId: $originId);

            if ($synchronizationContract instanceof SynchronizationContract === false) {
                // Only persist if not test
                if ($isTest === false) {
                    $synchronizationContract = $this->synchronizationContractMapper->createFromArray([
                        'synchronizationId' => $synchronization->getId(),
                        'originId' => $originId,
                    ]);
                } else {
                    $synchronizationContract = new SynchronizationContract();
                    $synchronizationContract->setSynchronizationId($synchronization->getId());
                    $synchronizationContract->setOriginId($originId);
                }

                $synchronizationContract = $this->synchronizeContract(synchronizationContract: $synchronizationContract, synchronization: $synchronization, object: $object, isTest: $isTest);

                if ($isTest === true && is_array($synchronizationContract) === true) {
                    // If this is a log and contract array return for the test endpoint.
                    $logAndContractArray = $synchronizationContract;

                    return $logAndContractArray;
                }
            } else {
				// @todo this is wierd
				$synchronizationContract = $this->synchronizeContract(synchronizationContract: $synchronizationContract, synchronization: $synchronization, object: $object, isTest: $isTest);
				if ($isTest === false && $synchronizationContract instanceof SynchronizationContract === true) {
                    // If this is a regular synchronizationContract update it to the database.
                    $objectList[$key] = $this->synchronizationContractMapper->update(entity: $synchronizationContract);
                } elseif ($isTest === true && is_array($synchronizationContract) === true) {
                    // If this is a log and contract array return for the test endpoint.
                    $logAndContractArray = $synchronizationContract;
                    return $logAndContractArray;
                }
            }

            $this->synchronizationContractMapper->update($synchronizationContract);
        }

		foreach ($synchronization->getFollowUps() as $followUp) {
			$followUpSynchronization = $this->synchronizationMapper->find($followUp);
			$this->synchronize($followUpSynchronization, $isTest);
		}

		if (isset($rateLimitException) === true) {
			$log = [
				'synchronizationId' => $synchronization->getId(),
				'synchronizationContractId' => isset($synchronizationContract) === true ? $synchronizationContract->getId() : 0,
				'message' => $rateLimitException->getMessage(),
				'created' => new DateTime(),
				'expires' => new DateTime('+1 day')
			];

			$this->synchronizationContractLogMapper->createFromArray($log);
			throw new TooManyRequestsHttpException(
				message: $rateLimitException->getMessage(),
				code: 429,
				headers: $rateLimitException->getHeaders()
			);
		}

        return $objectList;
    }

	/**
	 * Gets id from object as is in the origin
	 *
	 * @param Synchronization $synchronization
	 * @param array $object
	 *
	 * @return string|int id
	 * @throws Exception
	 */
    private function getOriginId(Synchronization $synchronization, array $object): int|string
	{
        // Default ID position is 'id' if not specified in source config
        $originIdPosition = 'id';
        $sourceConfig = $synchronization->getSourceConfig();

        // Check if a custom ID position is defined in the source configuration
        if (isset($sourceConfig['idPosition']) === true && empty($sourceConfig['idPosition']) === false) {
            // Override default with custom ID position from config
            $originIdPosition = $sourceConfig['idPosition'];
        }

        // Create Dot object for easy access to nested array values
        $objectDot = new Dot($object);

        // Try to get the ID value from the specified position in the object
        $originId = $objectDot->get($originIdPosition);

        // If no ID was found at the specified position, throw an error
        if ($originId === null) {
            throw new Exception('Could not find origin id in object for key: ' . $originIdPosition);
        }

        // Return the found ID value
        return $originId;
    }

	/**
	 * Fetch an object from a specific endpoint.
	 *
	 * @param Synchronization $synchronization The synchronization containing the source.
	 * @param string $endpoint The endpoint to request to fetch the desired object.
	 *
	 * @return array The resulting object.
	 *
	 * @throws GuzzleException
	 * @throws \OCP\DB\Exception
	 */
	public function getObjectFromSource(Synchronization $synchronization, string $endpoint): array
	{
		$source = $this->sourceMapper->find(id: $synchronization->getSourceId());

		// Lets get the source config
		$sourceConfig = $synchronization->getSourceConfig();
		$headers = $sourceConfig['headers'] ?? [];
		$query = $sourceConfig['query'] ?? [];
		$config = [
			'headers' => $headers,
			'query' => $query,
		];

		if (str_starts_with($endpoint, $source->getLocation()) === true) {
			$endpoint = str_replace(search: $source->getLocation(), replace: '', subject: $endpoint);
		}

		// Make the initial API call
		// @TODO: method is now fixed to GET, but could end up in configuration.
		$response = $this->callService->call(source: $source, endpoint: $endpoint, config: $config)->getResponse();

		return json_decode($response['body'], true);
	}

    /**
     * Fetches additional data for a given object based on the synchronization configuration.
     *
     * This method retrieves extra data using either a dynamically determined endpoint from the object
     * or a statically defined endpoint in the configuration. The extra data can be merged with the original
     * object or returned as-is, based on the provided configuration.
     *
     * @param Synchronization $synchronization The synchronization instance containing configuration details.
     * @param array $extraDataConfig The configuration array specifying how to retrieve and handle the extra data:
     *      - EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION: The key to retrieve the dynamic endpoint from the object.
     *      - EXTRA_DATA_STATIC_ENDPOINT_LOCATION: The statically defined endpoint.
     *      - KEY_FOR_EXTRA_DATA_LOCATION: The key under which the extra data should be returned.
     *      - MERGE_EXTRA_DATA_OBJECT_LOCATION: Boolean flag indicating whether to merge the extra data with the object.
     * @param array $object The original object for which extra data needs to be fetched.
     *
     * @return array The original object merged with the extra data, or the extra data itself based on the configuration.
     *
     * @throws Exception If both dynamic and static endpoint configurations are missing or the endpoint cannot be determined.
     */
    private function fetchExtraDataForObject(Synchronization $synchronization, array $extraDataConfig, array $object)
    {
        if (isset($extraDataConfig[$this::EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION]) === false && isset($extraDataConfig[$this::EXTRA_DATA_STATIC_ENDPOINT_LOCATION]) === false) {
            return $object;
        }

        // Get endpoint from earlier fetched object.
        if (isset($extraDataConfig[$this::EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION]) === true) {
            $dotObject = new Dot($object);
            $endpoint = $dotObject->get($extraDataConfig[$this::EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION] ?? null);
        }

        // Get endpoint static defined in config.
        if (isset($extraDataConfig[$this::EXTRA_DATA_STATIC_ENDPOINT_LOCATION]) === true) {
            $endpoint = $extraDataConfig[$this::EXTRA_DATA_STATIC_ENDPOINT_LOCATION];
            $endpoint = str_replace(search: '{{ originId }}', replace: $this->getOriginId($synchronization, $object), subject: $endpoint);
            $endpoint = str_replace(search: '{{originId}}', replace: $this->getOriginId($synchronization, $object), subject: $endpoint);
        }

        if (!$endpoint) {
            throw new Exception(
                sprintf(
                    'Could not get static or dynamic endpoint, object: %s',
                    json_encode($object)
                )
            );
        }

        $extraData = $this->getObjectFromSource($synchronization, $endpoint);

        // Temporary fix,
        if (isset($extraDataConfig['extraDataConfigPerResult']) === true) {
            $dotObject = new Dot($extraData);
            $results = $dotObject->get($extraDataConfig['resultsLocation']);

            foreach ($results as $key => $result) {
                $results[$key] = $this->fetchExtraDataForObject($synchronization, $extraDataConfig['extraDataConfigPerResult'], $result);
            }

            $extraData = $results;
        }

        // Set new key if configured.
        if (isset($extraDataConfig[$this::KEY_FOR_EXTRA_DATA_LOCATION]) === true) {
            $extraData = [$extraDataConfig[$this::KEY_FOR_EXTRA_DATA_LOCATION] => $extraData];
        }

        // Merge with earlier fetchde object if configured.
        if (isset($extraDataConfig[$this::MERGE_EXTRA_DATA_OBJECT_LOCATION]) === true && ($extraDataConfig[$this::MERGE_EXTRA_DATA_OBJECT_LOCATION] === true || $extraDataConfig[$this::MERGE_EXTRA_DATA_OBJECT_LOCATION] === 'true')) {
            return array_merge($object, $extraData);
        }

        return $extraData;
    }


	/**
	 * Synchronize a contract
	 *
	 * @param SynchronizationContract $synchronizationContract
	 * @param Synchronization|null $synchronization
	 * @param array $object
	 * @param bool|null $isTest False by default, currently added for synchronization-test endpoint
	 *
	 * @return SynchronizationContract|Exception|array
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws LoaderError
	 * @throws SyntaxError
	 */
    public function synchronizeContract(SynchronizationContract $synchronizationContract, Synchronization $synchronization = null, array $object = [], ?bool $isTest = false): SynchronizationContract|Exception|array
	{
        $sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());

        // Check if extra data needs to be fetched
        if (isset($sourceConfig[$this::EXTRA_DATA_CONFIGS_LOCATION]) === true) {
            foreach ($sourceConfig[$this::EXTRA_DATA_CONFIGS_LOCATION] as $extraDataConfig) {
                $object = array_merge($object, $this->fetchExtraDataForObject($synchronization, $extraDataConfig, $object));
            }
        }

		// @TODO: This should be unset through pre-mapping
		if(isset($object['d']['vti_x005f_dirlateststamp']) === true) {
			unset($object['d']['vti_x005f_dirlateststamp']);
		}


        // Let create a source hash for the object
        $originHash = md5(serialize($object));

        // Let's prevent pointless updates @todo account for omnidirectional sync, unless the config has been updated since last check then we do want to rebuild and check if the tagert object has changed
        if ($originHash === $synchronizationContract->getOriginHash() && $synchronization->getUpdated() < $synchronizationContract->getSourceLastChecked()) {
            // The object has not changed and the config has not been updated since last check
			return $synchronizationContract;
        }

        // The object has changed, oke let do mappig and bla die bla
        $synchronizationContract->setOriginHash($originHash);
        $synchronizationContract->setSourceLastChanged(new DateTime());
		$synchronizationContract->setSourceLastChecked(new DateTime());

		// Check if object adheres to conditions.
		// Take note, JsonLogic::apply() returns a range of return types, so checking it with '=== false' or '!== true' does not work properly.
		if ($synchronization->getConditions() !== [] && !JsonLogic::apply($synchronization->getConditions(), $object)) {
			return $synchronizationContract;
		}

        // If no source target mapping is defined, use original object
        if (empty($synchronization->getSourceTargetMapping()) === true) {
            $targetObject = $object;
        } else {
            try {
                $sourceTargetMapping = $this->mappingMapper->find(id: $synchronization->getSourceTargetMapping());
            } catch (DoesNotExistException $exception) {
                return new Exception($exception->getMessage());
            }

            // Execute mapping if found
            if ($sourceTargetMapping) {
                $targetObject = $this->mappingService->executeMapping(mapping: $sourceTargetMapping, input: $object);
            } else {
                $targetObject = $object;
            }
        }

        // set the target hash
        $targetHash = md5(serialize($targetObject));
        $synchronizationContract->setTargetHash($targetHash);
        $synchronizationContract->setTargetLastChanged(new DateTime());
        $synchronizationContract->setTargetLastSynced(new DateTime());
        $synchronizationContract->setSourceLastSynced(new DateTime());

        // prepare log
        $log = [
            'synchronizationId' => $synchronizationContract->getSynchronizationId(),
            'synchronizationContractId' => $synchronizationContract->getId(),
            'source' => $object,
            'target' => $targetObject,
            'expires' => new DateTime('+1 day')
        ];

        // Handle synchronization based on test mode
		if ($isTest === true) {
			// Return test data without updating target
			return [
				'log' => $log,
				'contract' => $synchronizationContract->jsonSerialize()
			];
		}

		// Update target and create log when not in test mode
		$synchronizationContract = $this->updateTarget(
			synchronizationContract: $synchronizationContract,
			targetObject: $targetObject
		);

		// Create log entry for the synchronization
		$this->synchronizationContractLogMapper->createFromArray($log);

        return $synchronizationContract;

    }

	/**
	 * Write the data to the target
	 *
	 * @param SynchronizationContract $synchronizationContract
	 * @param array $targetObject
	 *
	 * @return SynchronizationContract
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
    public function updateTarget(SynchronizationContract $synchronizationContract, array $targetObject): SynchronizationContract
	{
         // The function can be called solo set let's make sure we have the full synchronization object
        if (isset($synchronization) === false) {
            $synchronization = $this->synchronizationMapper->find($synchronizationContract->getSynchronizationId());
        }

        // Let's check if we need to create or update
        $update = false;
        if ($synchronizationContract->getTargetId()){
            $update = true;
        }

        $type = $synchronization->getTargetType();

        switch ($type) {
            case 'register/schema':
                // Setup the object service
                $objectService = $this->containerInterface->get('OCA\OpenRegister\Service\ObjectService');

                // if we already have an id, we need to get the object and update it
                if ($synchronizationContract->getTargetId() !== null) {
                    $targetObject['id'] = $synchronizationContract->getTargetId();
                }

                // Extract register and schema from the targetId
                // The targetId needs to be filled in as: {registerId} + / + {schemaId} for example: 1/1
                $targetId = $synchronization->getTargetId();
                list($register, $schema) = explode('/', $targetId);

                // Save the object to the target
                $target = $objectService->saveObject($register, $schema, $targetObject);

                // Get the id form the target object
                $synchronizationContract->setTargetId($target->getUuid());
                break;
            case 'api':
                //@todo: implement
                //$this->callService->put($targetObject);
                break;
            case 'database':
                //@todo: implement
                break;
            default:
                throw new Exception("Unsupported target type: $type");
        }

        return $synchronizationContract;
    }

	/**
	 * Get all the object from a source
	 *
	 * @param Synchronization $synchronization
	 * @param bool|null $isTest False by default, currently added for synchronziation-test endpoint
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 * @throws GuzzleException
	 * @throws NotFoundExceptionInterface
	 * @throws \OCP\DB\Exception
	 */
    public function getAllObjectsFromSource(Synchronization $synchronization, ?bool $isTest = false): array
	{
        $objects = [];

        $type = $synchronization->getSourceType();

        switch ($type) {
            case 'register/schema':
                // Setup the object service
                $this->objectService = $this->containerInterface->get('OCA\OpenRegister\Service\ObjectService');

                break;
            case 'api':
                $objects = $this->getAllObjectsFromApi(synchronization: $synchronization, isTest: $isTest);
                break;
            case 'database':
                //@todo: implement
                break;
        }

        return $objects;
    }

	/**
	 * Retrieves all objects from an API source for a given synchronization.
	 * @todo re-write this entire function so that it is recursive.
	 *
	 * @param Synchronization $synchronization The synchronization object containing source information.
	 * @param bool $isTest If we only want to return a single object (for example a test)
	 *
	 * @return array An array of all objects retrieved from the API.
	 * @throws GuzzleException
	 * @throws \OCP\DB\Exception
	 */
    public function getAllObjectsFromApi(Synchronization $synchronization, ?bool $isTest = false): array
	{
        $objects = [];
        $source = $this->sourceMapper->find(id: $synchronization->getSourceId());

		if ($source->getRateLimitRemaining() !== null && $source->getRateLimitReset() !== null
			&& $source->getRateLimitRemaining() <= 0 && $source->getRateLimitReset() > time()
		) {
			throw new TooManyRequestsHttpException(
				message: "Rate Limit on Source has been exceeded. Canceling synchronization...",
				code: 429,
				headers: $this->getRateLimitHeaders($source)
			);
		}

        // Let's get the source config
        $sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());
        $endpoint = $sourceConfig['endpoint'] ?? '';
        $headers = $sourceConfig['headers'] ?? [];
        $query = $sourceConfig['query'] ?? [];
        $config = [
            'headers' => $headers,
            'query' => $query,
        ];

		// Get CurrentPage from Synchronization.
		$currentPage = $synchronization->getCurrentPage() ?? 1;
		// Current Page could be higher than 1 if we continue after rate limit had been reached previously.
		if ($currentPage > 1) {
			$config = $this->getNextPage(config: $config, sourceConfig: $sourceConfig, currentPage: $currentPage);
		}

        // Make the initial API call
		// @TODO: method is now fixed to GET, but could end up in configuration.
		$callLog = $this->callService->call(source: $source, endpoint: $endpoint, method: 'GET', config: $config);
        $response = $callLog->getResponse();
		if ($response === null) {
			// @todo
			if ($callLog->getStatusCode() === 429) {
				throw new TooManyRequestsHttpException(
					message: "Stopped sync because rate limit on Source has been exceeded.",
					code: 429,
					headers: $this->getRateLimitHeaders($source)
				);
			}
		}

		$lastHash = md5($response['body']);
        $body = json_decode($response['body'], true);
        if (empty($body) === true) {
            // @todo log that we got an empty response
            return [];
        }
        $objects = array_merge($objects, $this->getAllObjectsFromArray(array: $body, synchronization: $synchronization));

        // Return a single object or empty array if in test mode
        if ($isTest === true) {
            return [$objects[0]] ?? [];
        }

        // Increase Current page because of the first call made above.
        $currentPage++;
		// Update CurrentPage on Synchronization
		$synchronization->setCurrentPage($currentPage);
		$this->synchronizationMapper->update($synchronization);

        $useNextEndpoint = false;
        if (array_key_exists('next', $body)) {
            $useNextEndpoint = true;
        }

		// Continue making API calls if there are more pages from 'next' the response body or if paginationQuery is set
		while ($useNextEndpoint === true && $nextEndpoint = $this->getNextEndpoint(body: $body, url: $source->getLocation())) {
			// Do not pass $config here because it overwrites the query attached to nextEndpoint
			$callLog = $this->callService->call(source: $source, endpoint: $nextEndpoint);
			$response = $callLog->getResponse();
			if ($response === null) {
				// @todo
				if ($callLog->getStatusCode() === 429) {
					throw new TooManyRequestsHttpException(
						message: "Stopped sync because rate limit on Source has been exceeded.",
						code: 429,
						headers: $this->getRateLimitHeaders($source)
					);
				}
			}

			$body = json_decode($response['body'], true);
			$objects = array_merge($objects, $this->getAllObjectsFromArray($body, $synchronization));

			// Increase Current page. And update CurrentPage on Synchronization.
			$currentPage++;
			$synchronization->setCurrentPage($currentPage);
			$this->synchronizationMapper->update($synchronization);
		}

		if ($useNextEndpoint === false && $synchronization->usesPagination() === true) {
			do {
				$config   = $this->getNextPage(config: $config, sourceConfig: $sourceConfig, currentPage: $currentPage);
				$callLog = $this->callService->call(source: $source, endpoint: $endpoint, method: 'GET', config: $config);
				$response = $callLog->getResponse();
				if ($response === null) {
					// @todo
					if ($callLog->getStatusCode() === 429) {
						throw new TooManyRequestsHttpException(
							message: "Stopped sync because rate limit on Source has been exceeded.",
							code: 429,
							headers: $this->getRateLimitHeaders($source)
						);
					}
				}

				$hash     = md5($response['body']);

				if ($hash === $lastHash) {
					break;
				}

				$lastHash = $hash;
				$body     = json_decode($response['body'], true);

				if (empty($body) === true) {
					break;
				}

				$newObjects = $this->getAllObjectsFromArray(array: $body, synchronization: $synchronization);
				$objects = array_merge($objects, $newObjects);

				// Increase Current page. And update CurrentPage on Synchronization.
				$currentPage++;
				$synchronization->setCurrentPage($currentPage);
				$this->synchronizationMapper->update($synchronization);
			} while (empty($newObjects) === false);
		}

		// Reset Current Page to 1 when we are done with all pages.
		$synchronization->setCurrentPage(1);
		$this->synchronizationMapper->update($synchronization);

        return $objects;
    }

	/**
	 * Retrieves rate limit information from a given source and formats it as HTTP headers.
	 *
	 * This function extracts rate limit details from the provided source object and returns them
	 * as an associative array of headers. The headers can be used for communicating rate limit status
	 * in API responses or logging purposes.
	 *
	 * @param Source $source The source object containing rate limit details, such as limits, remaining requests, and reset times.
	 *
	 * @return array An associative array of rate limit headers:
	 *               - 'X-RateLimit-Limit' (int|null): The maximum number of allowed requests.
	 *               - 'X-RateLimit-Remaining' (int|null): The number of requests remaining in the current window.
	 *               - 'X-RateLimit-Reset' (int|null): The Unix timestamp when the rate limit resets.
	 *               - 'X-RateLimit-Used' (int|null): The number of requests used so far.
	 *               - 'X-RateLimit-Window' (int|null): The duration of the rate limit window in seconds.
	 */
	private function getRateLimitHeaders(Source $source): array
	{
		return [
			'X-RateLimit-Limit' => $source->getRateLimitLimit(),
			'X-RateLimit-Remaining' => $source->getRateLimitRemaining(),
			'X-RateLimit-Reset' => $source->getRateLimitReset(),
			'X-RateLimit-Used' => 0,
			'X-RateLimit-Window' => $source->getRateLimitWindow(),
		];
	}

	/**
	 * Determines the next API endpoint based on a provided next.
	 *
	 * @param array $body
	 * @param string $url
	 *
	 * @return string|null The next endpoint URL if a next link or pagination query is available, or null if neither exists.
	 */
    private function getNextEndpoint(array $body, string $url): ?string
    {
        $nextLink = $this->getNextlinkFromCall($body);

        if ($nextLink !== null) {
            return str_replace($url, '', $nextLink);
        }

        return null;
    }

    /**
     * Updatesc config with pagination from pagination config.
     *
     * @param array  $config
     * @param array  $sourceConfig
     * @param int    $currentPage The current page number for pagination, used if no next link is available.
     *
     * @return array $config
     */
    private function getNextPage(array $config, array $sourceConfig, int $currentPage): array
	{
        // If paginationQuery exists, replace any placeholder with the current page number
        $config['pagination'] = [
            'paginationQuery' => $sourceConfig['paginationQuery'] ?? 'page',
            'page' => $currentPage
        ];

        return $config;
    }

    /**
     * Extracts all objects from the API response body.
     *
     * @param array $body The decoded JSON body of the API response.
     * @param Synchronization $synchronization The synchronization object containing source configuration.
     *
     * @throws Exception If the position of objects in the return body cannot be determined.
     *
     * @return array An array of items extracted from the response body.
     */
    public function getAllObjectsFromArray(array $array, Synchronization $synchronization): array
	{
        // Get the source configuration from the synchronization object
        $sourceConfig = $synchronization->getSourceConfig();

        // Check if a specific objects position is defined in the source configuration
        if (empty($sourceConfig['resultsPosition']) === false) {
            $position = $sourceConfig['resultsPosition'];
            // Use Dot notation to access nested array elements
            $dot = new Dot($array);
            if ($dot->has($position) === true) {
                // Return the objects at the specified position
                return $dot->get($position);
            } else {
                // Throw an exception if the specified position doesn't exist

                return [];
                // @todo log error
                // throw new Exception("Cannot find the specified position of objects in the return body.");
            }
        }

        // Define common keys to check for objects
        $commonKeys = ['items', 'result', 'results'];

        // Loop through common keys and return first match found
        foreach ($commonKeys as $key) {
            if (isset($array[$key]) === true) {
                return $array[$key];
            }
        }

        // If 'results' key exists, return its value
        if (isset($array['results']) === true) {
            return $array['results'];
        }

        // If no objects can be found, throw an exception
        throw new Exception("Cannot determine the position of objects in the return body.");
    }

	/**
	 * Retrieves the next link for pagination from the API response body.
	 *
	 * @param array $body The decoded JSON body of the API response.
	 *
	 * @return string|null The URL for the next page of results, or null if there is no next page.
	 */
    public function getNextlinkFromCall(array $body): ?string
    {
		return $body['next'] ?? null;
    }
}
