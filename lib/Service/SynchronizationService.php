<?php

namespace OCA\OpenConnector\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JWadhams\JsonLogic;
use OC\User\NoUserException;
use OCA\OpenConnector\Db\CallLog;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\RuleMapper;
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
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;
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
//    private ObjectService $objectService;
	private Source $source;

    const EXTRA_DATA_CONFIGS_LOCATION          = 'extraDataConfigs';
    const EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION = 'dynamicEndpointLocation';
    const EXTRA_DATA_STATIC_ENDPOINT_LOCATION  = 'staticEndpoint';
    const KEY_FOR_EXTRA_DATA_LOCATION          = 'keyToSetExtraData';
    const MERGE_EXTRA_DATA_OBJECT_LOCATION     = 'mergeExtraData';
    const UNSET_CONFIG_KEY_LOCATION            = 'unsetConfigKey';


	public function __construct(
		CallService                      $callService,
		MappingService                   $mappingService,
		ContainerInterface               $containerInterface,
		SourceMapper                     $sourceMapper,
		MappingMapper                    $mappingMapper,
		SynchronizationMapper            $synchronizationMapper,
		SynchronizationContractMapper    $synchronizationContractMapper,
		SynchronizationContractLogMapper $synchronizationContractLogMapper,
		private readonly ObjectService   $objectService,
        private readonly StorageService  $storageService,
        private readonly RuleMapper      $ruleMapper,
	)
	{
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
		$sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());

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
			// We can only deal with arrays (bassed on the source empty values or string might be returned)
			if (is_array($object) === false) {
				continue;
			}

			// Check if object adheres to conditions.
			// Take note, JsonLogic::apply() returns a range of return types, so checking it with '=== false' or '!== true' does not work properly.
			if ($synchronization->getConditions() !== [] && !JsonLogic::apply($synchronization->getConditions(), $object)) {

				// @todo log that this object is not valid
				unset($objectList[$key]);
				continue;
			}

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

			$synchronizationContract = $this->synchronizationContractMapper->update($synchronizationContract);

			$synchronizedTargetIds[] = $synchronizationContract->getTargetId();
		}

		if (isset($sourceConfig['deleteInvalidObjects']) === false ||
		   (isset($sourceConfig['deleteInvalidObjects']) === true && ($sourceConfig['deleteInvalidObjects'] === true || $sourceConfig['deleteInvalidObjects'] === 'true'))) {
			$this->deleteInvalidObjects(synchronization: $synchronization, synchronizedTargetIds: $synchronizedTargetIds);
		}
		// @todo log deleted objects count


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
		$sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());
		$headers = $sourceConfig['headers'] ?? [];
		$query = $sourceConfig['query'] ?? [];
		$config = [
			'headers' => $headers,
			'query' => $query,
		];


		if (str_starts_with($endpoint, $source->getLocation()) === true) {
			$endpoint = str_replace(search: $source->getLocation(), replace: '', subject: $endpoint);
		}

		// Make the initial API call, read denotes that we call an endpoint for a single object (for config variations).
		$response = $this->callService->call(source: $source, endpoint: $endpoint, config: $config, read: true)->getResponse();

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
	 * @param string|null $originId
	 *
	 * @return array The original object merged with the extra data, or the extra data itself based on the configuration.
	 *
	 * @throws Exception If both dynamic and static endpoint configurations are missing or the endpoint cannot be determined.
	 */
	private function fetchExtraDataForObject(
		Synchronization $synchronization,
		array $extraDataConfig,
		array $object, ?string
		$originId = null
	)
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

			if ($originId === null) {
				$originId = $this->getOriginId($synchronization, $object);
			}

			if (isset($extraDataConfig['endpointIdLocation']) === true) {
				$dotObject = new Dot($object);
				$originId = $dotObject->get($extraDataConfig['endpointIdLocation']);
			}


			$endpoint = $extraDataConfig[$this::EXTRA_DATA_STATIC_ENDPOINT_LOCATION];

			if ($originId === null) {
				$originId = $this->getOriginId($synchronization, $object);
			}

			$endpoint = str_replace(search: '{{ originId }}', replace: $originId, subject: $endpoint);
			$endpoint = str_replace(search: '{{originId}}', replace: $originId, subject: $endpoint);

			if (isset($extraDataConfig['subObjectId']) === true) {
				$objectDot = new Dot($object);
				$subObjectId = $objectDot->get($extraDataConfig['subObjectId']);
				if ($subObjectId !== null) {
					$endpoint = str_replace(search: '{{ subObjectId }}', replace: $subObjectId, subject: $endpoint);
					$endpoint = str_replace(search: '{{subObjectId}}', replace: $subObjectId, subject: $endpoint);
				}
			}
		}

		if (!$endpoint) {
			throw new Exception(
				sprintf(
					'Could not get static or dynamic endpoint, object: %s',
					json_encode($object)
				)
			);
		}

        $sourceConfig = $synchronization->getSourceConfig();
        if (isset($extraDataConfig[$this::UNSET_CONFIG_KEY_LOCATION]) === true && isset($sourceConfig[$extraDataConfig[$this::UNSET_CONFIG_KEY_LOCATION]]) === true) {
            unset($sourceConfig[$extraDataConfig[$this::UNSET_CONFIG_KEY_LOCATION]]);
            $synchronization->setSourceConfig($sourceConfig);
        }

        $extraData = $this->getObjectFromSource($synchronization, $endpoint);

		// Temporary fix,
		if (isset($extraDataConfig['extraDataConfigPerResult']) === true) {
			$dotObject = new Dot($extraData);
			$results = $dotObject->get($extraDataConfig['resultsLocation']);

			foreach ($results as $key => $result) {
				$results[$key] = $this->fetchExtraDataForObject(synchronization: $synchronization, extraDataConfig: $extraDataConfig['extraDataConfigPerResult'], object: $result, originId: $originId);
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
	 * Fetches multiple extra data entries for an object based on the source configuration.
	 *
	 * This method iterates through a list of extra data configurations, fetches the additional data for each configuration,
	 * and merges it with the original object.
	 *
	 * @param Synchronization $synchronization The synchronization instance containing configuration details.
	 * @param array $sourceConfig The source configuration containing extra data retrieval settings.
	 * @param array $object The original object for which extra data needs to be fetched.
	 *
	 * @return array The updated object with all fetched extra data merged into it.
	 */
	private function fetchMultipleExtraData(Synchronization $synchronization, array $sourceConfig, array $object): array
	{
		if (isset($sourceConfig[$this::EXTRA_DATA_CONFIGS_LOCATION]) === true) {
			foreach ($sourceConfig[$this::EXTRA_DATA_CONFIGS_LOCATION] as $extraDataConfig) {
				$object = array_merge($object, $this->fetchExtraDataForObject($synchronization, $extraDataConfig, $object));
			}
		}

		return $object;
	}

	/**
	 * Maps a given object using a source hash mapping configuration.
	 *
	 * This function retrieves a hash mapping configuration for a synchronization instance, if available,
	 * and applies it to the input object using the mapping service.
	 *
	 * @param Synchronization $synchronization The synchronization instance containing the hash mapping configuration.
	 * @param array $object The input object to be mapped.
	 *
	 * @return array The mapped object, or the original object if no mapping is found.
	 */
	private function mapHashObject(Synchronization $synchronization, array $object): array
	{
		if (empty($synchronization->getSourceHashMapping()) === false) {
			try {
				$sourceHashMapping = $this->mappingMapper->find(id: $synchronization->getSourceHashMapping());
			} catch (DoesNotExistException $exception) {
				return new Exception($exception->getMessage());
			}

			// Execute mapping if found
			if ($sourceHashMapping) {
				return $this->mappingService->executeMapping(mapping: $sourceHashMapping, input: $object);
			}
		}

		return $object;
	}

	/**
	 * Deletes invalid objects associated with a synchronization.
	 *
	 * This function identifies and removes objects that are no longer valid or do not exist
	 * in the source data for a given synchronization. It compares the target IDs from the
	 * synchronization contract with the synchronized target IDs and deletes the unmatched ones.
	 *
	 * @param Synchronization $synchronization The synchronization entity to process.
	 * @param array|null $synchronizedTargetIds An array of target IDs that are still valid in the source.
	 *
	 * @return int The count of objects that were deleted.
	 *
	 * @throws Exception If any database or object deletion errors occur during execution.
	 */
	public function deleteInvalidObjects(Synchronization $synchronization, ?array $synchronizedTargetIds = []): int
	{
		$deletedObjectsCount = 0;
		$type = $synchronization->getTargetType();

		switch ($type) {
			case 'register/schema':

				$targetIdsToDelete = [];
				$allContracts = $this->synchronizationContractMapper->findAllBySynchronization($synchronization->getId());
				$allContractTargetIds = [];
				foreach ($allContracts as $contract) {
					if ($contract->getTargetId() !== null) {
						$allContractTargetIds[] = $contract->getTargetId();
					}
				}

				// Initialize $synchronizedTargetIds as empty array if null
				if ($synchronizedTargetIds === null) {
					$synchronizedTargetIds = [];
				}

				// Check if we have contracts that became invalid or do not exist in the source anymore
				$targetIdsToDelete = array_diff($allContractTargetIds, $synchronizedTargetIds);

				foreach ($targetIdsToDelete as $targetIdToDelete) {
					try {
						$synchronizationContract = $this->synchronizationContractMapper->findOnTarget(synchronization: $synchronization->getId(), targetId: $targetIdToDelete);
						if ($synchronizationContract === null) {
							continue;
						}
						$synchronizationContract = $this->updateTarget(synchronizationContract: $synchronizationContract, targetObject: [], action: 'delete');
						$this->synchronizationContractMapper->update($synchronizationContract);
						$deletedObjectsCount++;
					} catch (DoesNotExistException $exception) {
						// @todo log
					}
				}
				break;
		}

		return $deletedObjectsCount;

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
		$object = $this->fetchMultipleExtraData(synchronization: $synchronization, sourceConfig: $sourceConfig, object: $object);

		// Get mapped hash object (some fields can make it look the object has changed even if it hasn't)
		$hashObject = $this->mapHashObject(synchronization: $synchronization, object: $object);
		// Let create a source hash for the object
		$originHash = md5(serialize($hashObject));

		// If no source target mapping is defined, use original object
		if (empty($synchronization->getSourceTargetMapping()) === true) {
            $sourceTargetMapping = null;
		} else {
			try {
				$sourceTargetMapping = $this->mappingMapper->find(id: $synchronization->getSourceTargetMapping());
			} catch (DoesNotExistException $exception) {
				return new Exception($exception->getMessage());
			}
		}

        // Let's prevent pointless updates by checking:
        // 1. If the origin hash matches (object hasn't changed)
        // 2. If the synchronization config hasn't been updated since last check
        // 3. If source target mapping exists, check it hasn't been updated since last check
        // 4. If target ID and hash exist (object hasn't been removed from target)
		if (
            $originHash === $synchronizationContract->getOriginHash() &&
            $synchronization->getUpdated() < $synchronizationContract->getSourceLastChecked() &&
            ($sourceTargetMapping === null ||
             $sourceTargetMapping->getUpdated() < $synchronizationContract->getSourceLastChecked()) &&
            $synchronizationContract->getTargetId() !== null &&
            $synchronizationContract->getTargetHash() !== null
            ) {
			// The object has not changed and neither config nor mapping have been updated since last check
			return $synchronizationContract;
		}

		// The object has changed, oke let do mappig and set metadata
		$synchronizationContract->setOriginHash($originHash);
		$synchronizationContract->setSourceLastChanged(new DateTime());
		$synchronizationContract->setSourceLastChecked(new DateTime());

        // Execute mapping if found
        if ($sourceTargetMapping) {
            $targetObject = $this->mappingService->executeMapping(mapping: $sourceTargetMapping, input: $object);
        } else {
            $targetObject = $object;
        }

        if($synchronization->getActions() !== []) {
            $targetObject = $this->processRules(synchronization: $synchronization, data: $targetObject, timing: 'before');
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

        if($synchronization->getTargetType() === 'register/schema') {
            [$registerId, $schemaId] = explode(separator: '/', string: $synchronization->getTargetId());
            $this->processRules(synchronization: $synchronization, data: $targetObject, timing: 'after', objectId: $synchronizationContract->getTargetId(), registerId: $registerId, schemaId: $schemaId);
        }

		// Create log entry for the synchronization
		$this->synchronizationContractLogMapper->createFromArray($log);

		return $synchronizationContract;

	}

	/**
	 * Updates or deletes a target object in the Open Register system.
	 *
	 * This method updates a target object associated with a synchronization contract
	 * or deletes it based on the specified action. It extracts the register and schema
	 * from the target ID and performs the corresponding operation using the object service.
	 *
	 * @param SynchronizationContract $synchronizationContract The synchronization contract being updated.
	 * @param Synchronization $synchronization The synchronization entity containing the target ID.
	 * @param array|null $targetObject An optional array containing the data for the target object. Defaults to an empty array.
	 * @param string|null $action The action to perform: 'save' (default) to update or 'delete' to remove the target object.
	 *
	 * @return SynchronizationContract The updated synchronization contract with the modified target ID.
	 *
	 * @throws Exception If an error occurs while interacting with the object service or processing the data.
	 */
	private function updateTargetOpenRegister(SynchronizationContract $synchronizationContract, Synchronization $synchronization, ?array $targetObject = [], ?string $action = 'save'): SynchronizationContract
	{
		// Setup the object service
		$objectService = $this->containerInterface->get('OCA\OpenRegister\Service\ObjectService');
		$sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());

		// if we already have an id, we need to get the object and update it
		if ($synchronizationContract->getTargetId() !== null) {
			$targetObject['id'] = $synchronizationContract->getTargetId();
		}

		if (isset($sourceConfig['subObjects']) === true) {
			$targetObject = $this->updateIdsOnSubObjects(subObjectsConfig: $sourceConfig['subObjects'], synchronizationId: $synchronization->getId(), targetObject: $targetObject);
		}

		// Extract register and schema from the targetId
		// The targetId needs to be filled in as: {registerId} + / + {schemaId} for example: 1/1
		$targetId = $synchronization->getTargetId();
		list($register, $schema) = explode('/', $targetId);

		// Save the object to the target
		switch ($action) {
			case 'save':
				$target = $objectService->saveObject($register, $schema, $targetObject);
				// Get the id form the target object
				$synchronizationContract->setTargetId($target->getUuid());

				// Handle sub-objects synchronization if sourceConfig is defined
				if (isset($sourceConfig['subObjects']) === true) {
					$targetObject = $objectService->extendEntity($target->jsonSerialize(), ['all']);
					$this->updateContractsForSubObjects(subObjectsConfig: $sourceConfig['subObjects'], synchronizationId: $synchronization->getId(), targetObject: $targetObject);
				}

				break;
			case 'delete':
				$objectService->deleteObject(register: $register, schema: $schema, uuid: $synchronizationContract->getTargetId());
				$synchronizationContract->setTargetId(null);
				break;
		}

		return $synchronizationContract;
	}

	/**
	 * Handles the synchronization of subObjects based on source configuration.
	 *
	 * @param array  $subObjectsConfig  The configuration for subObjects.
	 * @param string $synchronizationId The ID of the synchronization.
	 * @param array  $targetObject      The target object containing subObjects to be processed.
	 *
	 * @return void
	 */
	private function updateContractsForSubObjects(array $subObjectsConfig, string $synchronizationId,  array $targetObject): void
	{
		foreach ($subObjectsConfig as $propertyName => $subObjectConfig) {
			if (isset($targetObject[$propertyName]) === false) {
				continue;
			}

			$propertyData = $targetObject[$propertyName];

			// If property data is an array of subObjects, iterate and process
			if (is_array($propertyData) && $this->isAssociativeArray($propertyData)) {
				if (isset($propertyData['originId'])) {
					$this->processSyncContract($synchronizationId, $propertyData);
				}

				// Recursively process any nested subObjects within the associative array
				foreach ($propertyData as $key => $value) {
					if (is_array($value) === true && isset($subObjectConfig['subObjects']) === true) {
						$this->updateContractsForSubObjects($subObjectConfig['subObjects'], $synchronizationId, [$key => $value]);
					}
				}
			}

			// Process if it's an indexed array (list) of associative arrays
			if (is_array($propertyData) === true && !$this->isAssociativeArray($propertyData)) {
				foreach ($propertyData as $subObjectData) {
					if (is_array($subObjectData) === true && isset($subObjectData['originId']) === true) {
						$this->processSyncContract($synchronizationId, $subObjectData);
					}

					// Recursively process nested sub-objects
					if (is_array($subObjectData) === true && isset($subObjectConfig['subObjects']) === true) {
						$this->updateContractsForSubObjects($subObjectConfig['subObjects'], $synchronizationId, $subObjectData);
					}
				}
			}
		}
	}
	/**
	 * Processes a single synchronization contract for a subObject.
	 *
	 * @param string $synchronizationId The ID of the synchronization.
	 * @param array  $subObjectData     The data of the subObject to process.
	 *
	 * @return void
	 */
	private function processSyncContract(string $synchronizationId, array $subObjectData): void
	{
		$id = $subObjectData['id']['id']['id']['id'] ?? $subObjectData['id']['id']['id'] ?? $subObjectData['id']['id'] ?? $subObjectData['id'];
		$subContract = $this->synchronizationContractMapper->findByOriginId(
			originId: $subObjectData['originId']
		);

		if (!$subContract) {
			$subContract = new SynchronizationContract();
			$subContract->setSynchronizationId($synchronizationId);
			$subContract->setOriginId($subObjectData['originId']);
			$subContract->setTargetId($id);
			$subContract->setUuid(Uuid::V4());
			$subContract->setTargetHash(md5(serialize($subObjectData)));
			$subContract->setTargetLastChanged(new DateTime());
			$subContract->setTargetLastSynced(new DateTime());
			$subContract->setSourceLastSynced(new DateTime());

			$subContract = $this->synchronizationContractMapper->insert($subContract);
		} else {
			$subContract = $this->synchronizationContractMapper->updateFromArray(
				id: $subContract->getId(),
				object: [
					'synchronizationId' => $synchronizationId,
					'originId'   => $subObjectData['originId'],
					'targetId'   => $id,
					'targetHash' => md5(serialize($subObjectData)),
					'targetLastChanged' => new DateTime(),
					'targetLastSynced' => new DateTime(),
					'sourceLastSynced' => new DateTime()
				]
			);
		}

		$this->synchronizationContractLogMapper->createFromArray([
			'synchronizationId' => $subContract->getSynchronizationId(),
			'synchronizationContractId' => $subContract->getId(),
			'target' => $subObjectData,
			'expires' => new DateTime('+1 day')
		]);
	}

	/**
	 * Checks if an array is associative.
	 *
	 * @param array $array The array to check.
	 *
	 * @return bool True if the array is associative, false otherwise.
	 */
	private function isAssociativeArray(array $array): bool
	{
		// Check if the array is associative
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}

	/**
	 * Processes subObjects update their arrays with existing targetId's so OpenRegister can update the objects instead of duplicate them.
	 *
	 * @param array     $subObjectsConfig The configuration for subObjects.
	 * @param string    $synchronizationId The ID of the synchronization.
	 * @param array     $targetObject The target object containing subObjects to be processed.
	 * @param bool|null $parentIsNumericArray Whether the parent object is a numeric array (default false).
	 *
	 * @return array The updated target object with IDs updated on subObjects.
	 */
	private function updateIdsOnSubObjects(array $subObjectsConfig, string $synchronizationId, array $targetObject, ?bool $parentIsNumericArray = false): array
	{
		foreach ($subObjectsConfig as $propertyName => $subObjectConfig) {
			if (isset($targetObject[$propertyName]) === false) {
				continue;
			}

			// If property data is an array of sub-objects, iterate and process
			if (is_array($targetObject[$propertyName]) === true) {
				if (isset($targetObject[$propertyName]['originId']) === true) {
					$targetObject[$propertyName] = $this->updateIdOnSubObject($synchronizationId, $targetObject[$propertyName]);
				}

				// Recursively process any nested sub-objects within the associative array
				foreach ($targetObject[$propertyName] as $key => $value) {
					if (is_array($value) === true && isset($subObjectConfig['subObjects'][$key]) === true) {
						if ($this->isAssociativeArray($value) === true) {
							$targetObject[$propertyName][$key] = $this->updateIdsOnSubObjects($subObjectConfig['subObjects'], $synchronizationId, [$key => $value]);
						} elseif (is_array($value) === true && $this->isAssociativeArray(reset($value)) === true) {
							foreach ($value as $iterativeSubArrayKey => $iterativeSubArray) {
								$targetObject[$propertyName][$key][$iterativeSubArrayKey] = $this->updateIdsOnSubObjects($subObjectConfig['subObjects'], $synchronizationId, [$key => $iterativeSubArray], true);
							}
						}
					}
				}
			}
		}

		if ($parentIsNumericArray === true) {
			return reset($targetObject);
		}

		return $targetObject;
	}

	/**
	 * Updates the ID of a single subObject based on its synchronization contract so OpenRegister can update the object .
	 *
	 * @param string $synchronizationId The ID of the synchronization.
	 * @param array  $subObject 		The subObject to update.
	 *
	 * @return array The updated subObject with the ID set based on the synchronization contract.
	 */
	private function updateIdOnSubObject(string $synchronizationId, array $subObject): array
	{
		if (isset($subObject['originId']) === true) {
			$subObjectContract = $this->synchronizationContractMapper->findSyncContractByOriginId(
				synchronizationId: $synchronizationId,
				originId: $subObject['originId']
			);

			if ($subObjectContract !== null) {
				$subObject['id'] = $subObjectContract->getTargetId();
			}
		}

		return $subObject;
	}

	/**
	 * Write the data to the target
	 *
	 * @param SynchronizationContract $synchronizationContract
	 * @param array $targetObject
	 * @param string|null $action Determines what needs to be done with the target object, defaults to 'save'
	 *
	 * @return SynchronizationContract
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function updateTarget(SynchronizationContract $synchronizationContract, ?array $targetObject = [], ?string $action = 'save'): SynchronizationContract
	{
		// The function can be called solo set let's make sure we have the full synchronization object
		if (isset($synchronization) === false) {
			$synchronization = $this->synchronizationMapper->find($synchronizationContract->getSynchronizationId());
		}

		// Let's check if we need to create or update
		$update = false;
		if ($synchronizationContract->getTargetId()) {
			$update = true;
		}

		$type = $synchronization->getTargetType();

		switch ($type) {
			case 'register/schema':
				$synchronizationContract = $this->updateTargetOpenRegister(synchronizationContract: $synchronizationContract, synchronization: $synchronization, targetObject: $targetObject, action: $action);
				break;
			case 'api':
				$targetConfig = $synchronization->getTargetConfig();
				$synchronizationContract = $this->writeObjectToTarget(synchronization: $synchronization, contract: $synchronizationContract, endpoint: $targetConfig['endpoint'] ?? '');
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
                //@todo: implement
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
	 * Fetches all objects from an API source for a given synchronization.
	 *
	 * @param Synchronization $synchronization The synchronization object containing source information.
	 * @param bool|null $isTest If true, only a single object is returned for testing purposes.
	 *
	 * @return array An array of all objects retrieved from the API.
	 * @throws GuzzleException
	 * @throws TooManyRequestsHttpException
	 */
	public function getAllObjectsFromApi(Synchronization $synchronization, ?bool $isTest = false): array
	{
		$objects = [];
		$source = $this->sourceMapper->find($synchronization->getSourceId());

		// Check rate limit before proceeding
		$this->checkRateLimit($source);

		// Extract source configuration
		$sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());
		$endpoint = $sourceConfig['endpoint'] ?? '';
		$headers = $sourceConfig['headers'] ?? [];
		$query = $sourceConfig['query'] ?? [];
		$config = ['headers' => $headers, 'query' => $query];

		$currentPage = 1;

		// Start with the current page
        if ($source->getRateLimitLimit() !== null) {
            $currentPage = $synchronization->getCurrentPage() ?? 1;
        }

		// Fetch all pages recursively
		$objects = $this->fetchAllPages(
			source: $source,
			endpoint: $endpoint,
			config: $config,
			synchronization: $synchronization,
			currentPage: $currentPage,
			isTest: $isTest
		);

		// Reset the current page after synchronization if not a test
		if ($isTest === false) {
			$synchronization->setCurrentPage(1);
			$this->synchronizationMapper->update($synchronization);
		}

		return $objects;
	}

	/**
	 * Recursively fetches all pages of data from the API.
	 *
	 * @param Source $source The source object containing rate limit and configuration details.
	 * @param string $endpoint The API endpoint to fetch data from.
	 * @param array $config Configuration for the API call (e.g., headers and query parameters).
	 * @param Synchronization $synchronization The synchronization object containing state information.
	 * @param int $currentPage The current page number for pagination.
	 * @param bool $isTest If true, stops after fetching the first object from the first page.
	 * @param bool $usesNextEndpoint If true, doesnt use normal pagination but next endpoint.
	 *
	 * @return array An array of objects retrieved from the API.
	 * @throws GuzzleException
	 * @throws TooManyRequestsHttpException
	 */
	private function fetchAllPages(Source $source, string $endpoint, array $config, Synchronization $synchronization, int $currentPage, bool $isTest = false, ?bool $usesNextEndpoint = false): array
	{
		// Update pagination configuration for the current page
		if ($usesNextEndpoint === false) {
			$config = $this->getNextPage(config: $config, sourceConfig: $synchronization->getSourceConfig(), currentPage: $currentPage);
		}

		// Make the API call
		$callLog = $this->callService->call(source: $source, endpoint: $endpoint, config: $config);
		$response = $callLog->getResponse();

		// Check for rate limiting
		if ($response === null && $callLog->getStatusCode() === 429) {
			throw new TooManyRequestsHttpException(
				message: "Rate Limit on Source exceeded.",
				code: 429,
				headers: $this->getRateLimitHeaders($source)
			);
		}

		$body = json_decode($response['body'], true);
		if (empty($body) === true) {
			return []; // Stop if the response body is empty
		}

		// Process the current page
		$objects = $this->getAllObjectsFromArray(array: $body, synchronization: $synchronization);

		// If test mode is enabled, return only the first object
		if ($isTest === true) {
			return [$objects[0]] ?? [];
		}


		// Increment the current page and update synchronization
		$currentPage++;
		$synchronization->setCurrentPage($currentPage);
		$this->synchronizationMapper->update($synchronization);

		$nextEndpoint = null;
		$newNextEndpoint = $this->getNextEndpoint(body: $body, url: $source->getLocation());
		if ($newNextEndpoint !== $endpoint) {
			$nextEndpoint = $newNextEndpoint;
		}

		// Check if there's a next page
		if ($nextEndpoint !== null) {
			// Recursively fetch the next pages
			$objects = array_merge(
				$objects,
				$this->fetchAllPages(
					source: $source,
					endpoint: $nextEndpoint,
					config: $config,
					synchronization: $synchronization,
					currentPage: $currentPage,
					isTest: $isTest,
					usesNextEndpoint: true
				)
			);
		}

		return $objects;
	}

	/**
	 * Checks if the source has exceeded its rate limit and throws an exception if true.
	 *
	 * @param Source $source The source object containing rate limit details.
	 *
	 * @throws TooManyRequestsHttpException
	 */
	private function checkRateLimit(Source $source): void
	{
		if ($source->getRateLimitRemaining() !== null &&
			$source->getRateLimitReset() !== null &&
			$source->getRateLimitRemaining() <= 0 &&
			$source->getRateLimitReset() > time()
		) {
			throw new TooManyRequestsHttpException(
				message: "Rate Limit on Source has been exceeded. Canceling synchronization...",
				code: 429,
				headers: $this->getRateLimitHeaders($source)
			);
		}
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
	 * Updates the API request configuration with pagination details for the next page.
	 *
	 * @param array $config The current request configuration.
	 * @param array $sourceConfig The source configuration containing pagination settings.
	 * @param int $currentPage The current page number for pagination.
	 *
	 * @return array Updated configuration with pagination settings.
	 */
	private function getNextPage(array $config, array $sourceConfig, int $currentPage): array
	{
		$config['pagination'] = [
			'paginationQuery' => $sourceConfig['paginationQuery'] ?? 'page',
			'page' => $currentPage
		];

		return $config;
	}

	/**
	 * Extracts the next API endpoint for pagination from the response body.
	 *
	 * @param array $body The decoded JSON response body from the API.
	 * @param string $url The base URL of the API source.
	 *
	 * @return string|null The next endpoint URL if available, or null if there is no next page.
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

	/**
	 * Extracts all objects from the API response body.
	 *
	 * @param array $array The decoded JSON body of the API response.
	 * @param Synchronization $synchronization The synchronization object containing source configuration.
	 *
	 * @return array An array of items extracted from the response body.
	 * @throws Exception If the position of objects in the return body cannot be determined.
	 */
	public function getAllObjectsFromArray(array $array, Synchronization $synchronization): array
	{
		// Get the source configuration from the synchronization object
		$sourceConfig = $synchronization->getSourceConfig();

		// Check if a specific objects position is defined in the source configuration
		if (empty($sourceConfig['resultsPosition']) === false) {
			$position = $sourceConfig['resultsPosition'];
			// if position is root, return the array
			if ($position === '_root') {
				return $array;
			}
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

		// If no objects can be found, throw an exception
		throw new Exception("Cannot determine the position of objects in the return body.");
	}

	/**
     * Write an created, updated or deleted object to an external target.
     *
	 * @param Synchronization $synchronization The synchronization to run.
	 * @param SynchronizationContract $contract The contract to enforce.
	 * @param string $endpoint The endpoint to write the object to.
     *
	 * @return SynchronizationContract The updated contract.
     *
	 * @throws ContainerExceptionInterface
	 * @throws GuzzleException
	 * @throws LoaderError
	 * @throws NotFoundExceptionInterface
	 * @throws SyntaxError
	 * @throws \OCP\DB\Exception
	 */
	private function writeObjectToTarget(
		Synchronization         $synchronization,
		SynchronizationContract $contract,
		string                  $endpoint,
	): SynchronizationContract
	{
		$target = $this->sourceMapper->find(id: $synchronization->getTargetId());

		$sourceId = $synchronization->getSourceId();
		if ($synchronization->getSourceType() === 'register/schema' && $contract->getOriginId() !== null) {
			$sourceIds = explode(separator: '/', string: $sourceId);

			$this->objectService->getOpenRegisters()->setRegister($sourceIds[0]);
			$this->objectService->getOpenRegisters()->setSchema($sourceIds[1]);

			$object = $this->objectService->getOpenRegisters()->find(
				id: $contract->getOriginId(),
			)->jsonSerialize();
		}

		$targetConfig = $this->callService->applyConfigDot($synchronization->getTargetConfig());

		if (str_starts_with($endpoint, $target->getLocation()) === true) {
			$endpoint = str_replace(search: $target->getLocation(), replace: '', subject: $endpoint);
		}

		if ($contract->getOriginId() === null) {

			$endpoint .= '/'.$contract->getTargetId();
			$response = $this->callService->call(source: $target, endpoint: $endpoint, method: 'DELETE', config: $targetConfig)->getResponse();

			$contract->setTargetHash(md5(serialize($response['body'])));
			$contract->setTargetId(null);

			return $contract;
		}

		// @TODO For now only JSON APIs are supported
		$targetConfig['json'] = $object;

		if ($contract->getTargetId() === null) {
			$response = $this->callService->call(source: $target, endpoint: $endpoint, method: 'POST', config: $targetConfig)->getResponse();

			$body = json_decode($response['body'], true);


			$contract->setTargetId($body[$targetConfig['idposition']] ?? $body['id']);

			return $contract;
		}

		$endpoint .= '/'.$contract->getTargetId();

		$response = $this->callService->call(source: $target, endpoint: $endpoint, method: 'PUT', config: $targetConfig)->getResponse();

		$body = json_decode($response['body'], true);

		return $contract;
	}

	/**
	 * Synchronize data to a target.
	 *
	 * The synchronizationContract should be given if the normal procedure to find the contract (on originId) is not available to the contract that should be updated.
	 *
	 * @param ObjectEntity $object The object to synchronize
	 * @param SynchronizationContract|null $synchronizationContract If given: the synchronization contract that should be updated.
	 *
	 * @return array The updated synchronizationContracts
	 *
	 * @throws ContainerExceptionInterface
	 * @throws LoaderError
	 * @throws NotFoundExceptionInterface
	 * @throws SyntaxError
	 * @throws \OCP\DB\Exception
	 */
	public function synchronizeToTarget(ObjectEntity $object, ?SynchronizationContract $synchronizationContract = null): array
	{
		$objectId = $object->getUuid();

		if ($synchronizationContract === null) {
			$synchronizationContract = $this->synchronizationContractMapper->findByOriginId($objectId);
		}

		$synchronizations = $this->synchronizationMapper->findAll(filters: [
			'source_type' => 'register/schema',
			'source_id' => "{$object->getRegister()}/{$object->getSchema()}",
		]);
		if (count($synchronizations) === 0) {
			return [];
		}

		$synchronization = $synchronizations[0];

		if ($synchronizationContract instanceof SynchronizationContract === false) {

			$synchronizationContract = $this->synchronizationContractMapper->createFromArray([
				'synchronizationId' => $synchronization->getId(),
				'originId' => $objectId,
			]);

		}

		$synchronizationContract = $this->synchronizeContract(synchronizationContract: $synchronizationContract, synchronization: $synchronization, object: $object->jsonSerialize());
		if ($synchronizationContract instanceof SynchronizationContract === true) {
			// If this is a regular synchronizationContract update it to the database.
			$synchronizationContract = $this->synchronizationContractMapper->update(entity: $synchronizationContract);
		}

		$synchronizationContract = $this->synchronizationContractMapper->update($synchronizationContract);

		return [$synchronizationContract];

	}



    /**
     * Processes rules for an endpoint request
     *
     * @param Endpoint $synchronization The endpoint being processed
     * @param IRequest $request The incoming request
     * @param array $data Current request data
     *
     * @return array|JSONResponse Returns modified data or error response if rule fails
     */
    private function processRules(Synchronization $synchronization, array $data, string $timing, ?string $objectId = null, ?int $registerId = null, ?int $schemaId = null): array|JSONResponse
    {
        $rules = $synchronization->getActions();
        if (empty($rules) === true) {
            return $data;
        }

        try {
            // Get all rules at once and sort by order
            $ruleEntities = array_filter(
                array_map(
                    fn($ruleId) => $this->getRuleById($ruleId),
                    $rules
                )
            );

            // Sort rules by order
            usort($ruleEntities, fn($a, $b) => $a->getOrder() - $b->getOrder());

            // Process each rule in order
            foreach ($ruleEntities as $rule) {
                // Check rule conditions
                if ($this->checkRuleConditions($rule, $data) === false || $rule->getTiming() !== $timing) {
                    continue;
                }

                // Process rule based on type
                $result = match ($rule->getType()) {
                    'error' => $this->processErrorRule($rule),
                    'mapping' => $this->processMappingRule($rule, $data),
                    'synchronization' => $this->processSyncRule($rule, $data),
                    'fetch_file' => $this->processFetchFileRule($rule, $data, $objectId),
                    'write_file' => $this->processWriteFileRule($rule, $data, $objectId, $registerId, $schemaId),
                    default => throw new Exception('Unsupported rule type: ' . $rule->getType()),
                };

                // If result is JSONResponse, return error immediately
                if ($result instanceof JSONResponse) {
                    return $result;
                }

                // Update data with rule result
                $data = $result;
            }

            return $data;
        } catch (Exception $e) {
//            $this->logger->error('Error processing rules: ' . $e->getMessage());
            return new JSONResponse(['error' => 'Rule processing failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a rule by its ID using RuleMapper
     *
     * @param string $id The unique identifier of the rule
     *
     * @return Rule|null The rule object if found, or null if not found
     */
    private function getRuleById(string $id): ?Rule
    {
        try {
            return $this->ruleMapper->find((int)$id);
        } catch (Exception $e) {
//            $this->logger->error('Error fetching rule: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Write a file to the filesystem
     *
     * @param string $fileName The filename
     * @param string $content The content of the file
     * @param string $objectId The id of the object the file belongs to.
     *
     * @return bool Whether the file write is successful.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function writeFile(string $fileName, string $content, string $objectId): bool
    {
        $object = $this->objectService->getOpenRegisters()->getMapper('objectEntity')->find($objectId);

        try{
            $file = $this->storageService->writeFile(
                path: $object->getFolder(),
                fileName: $fileName,
                content: $content
            );
        } catch (NotFoundException|NotPermittedException|NoUserException $e) {
            return false;
        }

        return true;
    }

    /**
     * Fetch a file from a source.
     *
     * @param Source $source The source to fetch the file from.
     * @param string $endpoint The endpoint for the file.
     * @param array $config The configuration of the action.
     * @param string $objectId The id of the object the file belongs to.
     *
     * @return string If write is enabled: the url of the file, if write is disabled: the base64 encoded file.
     *
     * @throws GuzzleException
     * @throws LoaderError
     * @throws SyntaxError
     * @throws \OCP\DB\Exception
     */
    private function fetchFile (Source $source, string $endpoint, array $config, string $objectId): string
    {
        $originalEndpoint = $endpoint;
        $endpoint = str_contains(haystack: $endpoint, needle: $source->getLocation()) === true ? substr(string: $endpoint, offset: strlen(string: $source->getLocation())) : $endpoint;

        $result = $this->callService->call(
            source: $source,
            endpoint: $endpoint,
            method: $config['method'] ?? 'GET',
            config: $config['sourceConfiguration'] ?? []
        );
        $response = $result->getResponse();

        if(isset($config['write']) === true && $config['write'] === false) {
            return base64_encode($response['body']);
        }

        // Get a filename from the response. First try to do this using the Content-Disposition header
        if (isset($response['headers']['Content-Disposition']) === true
            && str_contains($response['headers']['Content-Disposition'][0], 'filename')) {
            $explodedContentDisposition = explode('=', $response['headers']['Content-Disposition'][0]);

            $filename = trim(string: $explodedContentDisposition[1], characters: '"');
        } else {
            // Otherwise, parse the url and content type header.
            $parsedUrl = parse_url($result->getRequest()['url']);
            $path = explode(separator:'/', string: $parsedUrl['path']);
            $filename = end($path);

            if (count(explode(separator: '.', string: $filename)) === 1
                && (isset($response['headers']['Content-Type']) === true || isset($response['headers']['content-type']) === true)
            ) {
                $explodedMimeType = isset($response['headers']['Content-Type']) === true
                    ? explode(separator: '/', string: explode(separator: ';', string: $response['headers']['Content-Type'][0])[0])
                    : explode(separator: '/', string: explode(separator: ';', string: $response['headers']['content-type'][0])[0]);


                $filename = $filename.'.'.end($explodedMimeType);
            }
        }

        // Write the file
        $this->writeFile($filename, $response['body'], $objectId);

        return $originalEndpoint;
    }

    /**
     * Process a rule to fetch a file from an external source.
     *
     * @param Rule $rule The rule to process.
     * @param array $data The data written to the object.
     *
     * @return array The resulting object data.
     * @throws GuzzleException
     * @throws LoaderError
     * @throws SyntaxError
     * @throws \OCP\DB\Exception
     */
    private function processFetchFileRule(Rule $rule, array $data, string $objectId): array
    {
        if (isset($rule->getConfiguration()['fetch_file']) === false) {
            throw new Exception('No configuration found for fetch_file');
        }

        $config = $rule->getConfiguration()['fetch_file'];

        $source = $this->sourceMapper->find($config['source']);
        $dataDot = new Dot($data);
        $endpoint = $dataDot[$config['filePath']];

        // If we get one endpoint, fetch that file, otherwise fetch all files from endpoint array.
        if(is_array($endpoint) === true) {
            $result = [];
            foreach($endpoint as $key => $value) {
                $result[$key] = $this->fetchFile($source, $value, $config, $objectId);
            }
            $dataDot[$config['filePath']] = $result;
        } else {
            $dataDot[$config['filePath']] = $this->fetchFile($source, $endpoint, $config, $objectId);
        }

        return $dataDot->jsonSerialize();
    }

    /**
     * Process a rule to write files.
     *
     * @param Rule $rule The rule to process.
     * @param array $data The data to write.
     * @param string $objectId The object to write the data to.
     * @param int $registerId The register the object is in.
     * @param int $schemaId The schema the object is in.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function processWriteFileRule(Rule $rule, array $data, string $objectId, int $registerId, int $schemaId): array
    {

        if (isset($rule->getConfiguration()['write_file']) === false) {
            throw new Exception('No configuration found for write_file');
        }

        $config  = $rule->getConfiguration()['write_file'];
        $dataDot = new Dot($data);
        $content = base64_decode($dataDot[$config['filePath']]);
        $fileName = $dataDot[$config['fileNamePath']];
        $openRegisters = $this->objectService->getOpenRegisters();
        $openRegisters->setRegister($registerId);
        $openRegisters->setSchema($schemaId);

        $object = $openRegisters->find($objectId);

        try {
            $file = $this->storageService->writeFile(
                path: $object->getFolder(),
                fileName: $fileName,
                content: $content
            );
        } catch (Exception $exception) {
        }

        $dataDot[$config['filePath']] = $file->getPath();

        return $dataDot->jsonSerialize();
    }



    /**
     * Processes an error rule
     *
     * @param Rule $rule The rule object containing error details
     *
     * @return JSONResponse Response containing error details and HTTP status code
     */
    private function processErrorRule(Rule $rule): JSONResponse
    {
        $config = $rule->getConfiguration();
        return new JSONResponse(
            [
                'error' => $config['error']['name'],
                'message' => $config['error']['message']
            ],
            $config['error']['code']
        );
    }

    /**
     * Processes a mapping rule
     *
     * @param Rule $rule The rule object containing mapping details
     * @param array $data The data to be processed through the mapping rule
     *
     * @return array The processed data after applying the mapping rule
     * @throws DoesNotExistException When the mapping configuration does not exist
     * @throws MultipleObjectsReturnedException When multiple mapping objects are returned unexpectedly
     * @throws LoaderError When there is an error loading the mapping
     * @throws SyntaxError When there is a syntax error in the mapping configuration
     */
    private function processMappingRule(Rule $rule, array $data): array
    {
        $config = $rule->getConfiguration();
        $mapping = $this->mappingService->getMapping($config['mapping']);
        return $this->mappingService->executeMapping($mapping, $data);
    }

    /**
     * Processes a synchronization rule
     *
     * @param Rule $rule The rule object containing synchronization details
     * @param array $data The data to be synchronized
     *
     * @return array The data after synchronization processing
     */
    private function processSyncRule(Rule $rule, array $data): array
    {
        $config = $rule->getConfiguration();
        // Here you would implement the synchronization logic
        // For now, just return the data unchanged
        return $data;
    }

    /**
     * Checks if rule conditions are met
     *
     * @param Rule $rule The rule object containing conditions to be checked
     * @param array $data The input data against which the conditions are evaluated
     *
     * @return bool True if conditions are met, false otherwise
     * @throws Exception
     */
    private function checkRuleConditions(Rule $rule, array $data): bool
    {
        $conditions = $rule->getConditions();
        if (empty($conditions) === true) {
            return true;
        }

        return JsonLogic::apply($conditions, $data) === true;
    }
}
