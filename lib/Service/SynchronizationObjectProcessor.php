<?php
/**
 * Processes individual objects during synchronization.
 *
 * @category  Service
 * @package   OCA\OpenConnector\Service
 * @author    Conduction <info@conduction.nl>
 * @copyright 2023 Conduction
 * @license   https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @version   GIT: <git_id>
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */

namespace OCA\OpenConnector\Service;

use Adbar\Dot;
use DateTime;
use Exception;
use JWadhams\JsonLogic;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\SynchronizationLog;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerRegistry;
use Symfony\Component\Uid\Uuid;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

/**
 * Service for processing individual objects during synchronization.
 *
 * @category Service
 * @package  OpenConnector
 * @version  GIT: <git_id>
 * @link     https://openregister.app
 * @since    1.0.0
 */
class SynchronizationObjectProcessor
{
    private const EXTRA_DATA_CONFIGS_LOCATION          = 'extraDataConfigs';
    private const EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION = 'dynamicEndpointLocation';
    private const EXTRA_DATA_STATIC_ENDPOINT_LOCATION  = 'staticEndpoint';
    private const KEY_FOR_EXTRA_DATA_LOCATION          = 'keyToSetExtraData';
    private const MERGE_EXTRA_DATA_OBJECT_LOCATION     = 'mergeExtraData';
    private const UNSET_CONFIG_KEY_LOCATION            = 'unsetConfigKey';

    /**
     * The call service for making API requests.
     *
     * @var CallService
     */
    private readonly CallService $callService;

    /**
     * The mapping service for data transformation.
     *
     * @var MappingService
     */
    private readonly MappingService $mappingService;

    /**
     * The mapping mapper for retrieving mappings.
     *
     * @var MappingMapper
     */
    private readonly MappingMapper $mappingMapper;

    /**
     * The synchronization contract mapper.
     *
     * @var SynchronizationContractMapper
     */
    private readonly SynchronizationContractMapper $synchronizationContractMapper;

    /**
     * The synchronization contract log mapper.
     *
     * @var SynchronizationContractLogMapper
     */
    private readonly SynchronizationContractLogMapper $synchronizationContractLogMapper;

    /**
     * The rule processor service.
     *
     * @var RuleProcessorService
     */
    private readonly RuleProcessorService $ruleProcessorService;

    /**
     * The target handler registry.
     *
     * @var TargetHandlerRegistry
     */
    private readonly TargetHandlerRegistry $targetHandlerRegistry;


    /**
     * Constructor.
     *
     * @param CallService                      $callService                      Service for API calls
     * @param MappingService                   $mappingService                   Service for data mapping
     * @param MappingMapper                    $mappingMapper                    Mapper for mappings
     * @param SynchronizationContractMapper    $synchronizationContractMapper    Mapper for contracts
     * @param SynchronizationContractLogMapper $synchronizationContractLogMapper Mapper for contract logs
     * @param RuleProcessorService             $ruleProcessorService             Service for rule processing
     * @param TargetHandlerRegistry            $targetHandlerRegistry            Registry for target handlers
     */
    public function __construct(
        CallService $callService,
        MappingService $mappingService,
        MappingMapper $mappingMapper,
        SynchronizationContractMapper $synchronizationContractMapper,
        SynchronizationContractLogMapper $synchronizationContractLogMapper,
        RuleProcessorService $ruleProcessorService,
        TargetHandlerRegistry $targetHandlerRegistry
    ) {
        $this->callService    = $callService;
        $this->mappingService = $mappingService;
        $this->mappingMapper  = $mappingMapper;
        $this->synchronizationContractMapper    = $synchronizationContractMapper;
        $this->synchronizationContractLogMapper = $synchronizationContractLogMapper;
        $this->ruleProcessorService  = $ruleProcessorService;
        $this->targetHandlerRegistry = $targetHandlerRegistry;

    }//end __construct()


    /**
     * Process a single object during synchronization.
     *
     * @param Synchronization     $synchronization The synchronization being processed
     * @param array<string,mixed> $object          The object to synchronize
     * @param array<string,mixed> $result          The current result tracking data
     * @param bool                $isTest          Whether this is a test run
     * @param bool                $force           Whether to force synchronization regardless of changes
     * @param SynchronizationLog  $log             The synchronization log
     *
     * @return array{result: array<string,mixed>, targetId: string|null} The updated result data and target ID
     *
     * @throws Exception If processing fails
     */
    public function processSynchronizationObject(
        Synchronization $synchronization,
        array $object,
        array $result,
        bool $isTest,
        bool $force,
        SynchronizationLog $log
    ): array {
        // Skip non-array objects
        if (is_array($object) === false) {
            $result['objects']['invalid']++;
            return [
                'result'   => $result,
                'targetId' => null,
            ];
        }

        // Encode array keys to handle dots
        $conditionsObject = $this->encodeArrayKeys($object, '.', '&#46;');

        // Check if object meets synchronization conditions
        if ($synchronization->getConditions() !== []
            && !JsonLogic::apply($synchronization->getConditions(), $conditionsObject)
        ) {
            $result['objects']['skipped']++;
            return [
                'result'   => $result,
                'targetId' => null,
            ];
        }

        // Extract origin ID from source object
        $originId = $this->getOriginId($synchronization, $object);

        // Get or create synchronization contract
        $synchronizationContract = $this->synchronizationContractMapper->findSyncContractByOriginId(
            synchronizationId: $synchronization->getId(),
            originId: $originId
        );

        // Process object synchronization
        if ($synchronizationContract instanceof SynchronizationContract === false) {
            $synchronizationContract = new SynchronizationContract();
            $synchronizationContract->setSynchronizationId($synchronization->getId());
            $synchronizationContract->setOriginId($originId);

            $synchronizationContractResult = $this->synchronizeContract(
                synchronizationContract: $synchronizationContract,
                synchronization: $synchronization,
                object: $object,
                isTest: $isTest,
                force: $force,
                log: $log
            );

            $result['contracts'][] = $synchronizationContractResult['contract']['uuid'] ?? null;
            $result['logs'][]      = $synchronizationContractResult['log']['uuid'] ?? null;
            $result['objects']['created']++;

            $targetId = $synchronizationContractResult['contract']['targetId'] ?? null;
        } else {
            $synchronizationContractResult = $this->synchronizeContract(
                synchronizationContract: $synchronizationContract,
                synchronization: $synchronization,
                object: $object,
                isTest: $isTest,
                force: $force,
                log: $log
            );

            $result['contracts'][] = $synchronizationContractResult['contract']['uuid'] ?? null;
            $result['logs'][]      = $synchronizationContractResult['log']['uuid'] ?? null;
            $result['objects']['updated']++;

            $targetId = $synchronizationContractResult['contract']['targetId'] ?? null;
        }//end if

        return [
            'result'   => $result,
            'targetId' => $targetId,
        ];

    }//end processSynchronizationObject()


    /**
     * Gets ID from object as is in the origin.
     *
     * @param Synchronization     $synchronization The synchronization containing source configuration
     * @param array<string,mixed> $object          The object from which to extract the origin ID
     *
     * @return string|int ID from the origin object
     *
     * @throws Exception If origin ID cannot be found
     */
    public function getOriginId(Synchronization $synchronization, array $object): (string | int)
    {
        // Default ID position is 'id' if not specified in source config
        $originIdPosition = 'id';
        $sourceConfig     = $synchronization->getSourceConfig();

        // Check if a custom ID position is defined in the source configuration
        if (isset($sourceConfig['idPosition']) === true && $sourceConfig['idPosition'] !== '') {
            // Override default with custom ID position from config
            $originIdPosition = $sourceConfig['idPosition'];
        }

        // Create Dot object for easy access to nested array values
        $objectDot = new Dot($object);

        // Try to get the ID value from the specified position in the object
        $originId = $objectDot->get($originIdPosition);

        // If no ID was found at the specified position, throw an error
        if ($originId === null) {
            throw new Exception('Could not find origin id in object for key: '.$originIdPosition);
        }

        // Return the found ID value
        return $originId;

    }//end getOriginId()


    /**
     * Synchronize a contract with source data.
     *
     * @param SynchronizationContract $synchronizationContract The contract to synchronize
     * @param Synchronization         $synchronization         The synchronization configuration
     * @param array<string,mixed>     $object                  The source object data
     * @param bool                    $isTest                  Whether this is a test run
     * @param bool                    $force                   Whether to force synchronization
     * @param SynchronizationLog      $log                     The log to update
     *
     * @return array{log: array<string,mixed>, contract: array<string,mixed>} The synchronization result
     *
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     */
    public function synchronizeContract(
        SynchronizationContract $synchronizationContract,
        Synchronization $synchronization,
        array $object,
        bool $isTest,
        bool $force,
        SynchronizationLog $log
    ): array {
        $contractLog = null;

        // Create a contract log for tracking
        if ($synchronizationContract->getId() !== null) {
            $contractLog = $this->synchronizationContractLogMapper->createFromArray(
                [
                    'synchronizationId'         => $synchronization->getId(),
                    'synchronizationContractId' => $synchronizationContract->getId(),
                    'source'                    => $object,
                    'test'                      => $isTest,
                    'force'                     => $force,
                ]
            );
        }

        if (isset($contractLog) === true) {
            $contractLog->setSynchronizationLogId($log->getId());
        }

        $sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());

        // Check if extra data needs to be fetched
        $object = $this->fetchMultipleExtraData(
            synchronization: $synchronization,
            sourceConfig: $sourceConfig,
            object: $object
        );

        // Get mapped hash object (some fields can make it look the object has changed even if it hasn't)
        $hashObject = $this->mapHashObject($synchronization, $object);

        // Create a source hash for the object
        $originHash = md5(serialize($hashObject));

        // If no source target mapping is defined, use original object
        if ($synchronization->getSourceTargetMapping() === '') {
            $sourceTargetMapping = null;
        } else {
            $sourceTargetMapping = $this->mappingMapper->find(
                id: $synchronization->getSourceTargetMapping()
            );
        }

        // Check if we need to update:
        // 1. If the origin hash matches (object hasn't changed)
        // 2. If the synchronization config hasn't been updated since last check
        // 3. If source target mapping exists, check it hasn't been updated since last check
        // 4. If target ID and hash exist (object hasn't been removed from target)
        // 5. Force parameter is false (otherwise always continue with update)
        if ($force === false
            && $originHash === $synchronizationContract->getOriginHash()
            && $synchronization->getUpdated() < $synchronizationContract->getSourceLastChecked()
            && ($sourceTargetMapping === null
            || $sourceTargetMapping->getUpdated() < $synchronizationContract->getSourceLastChecked())
            && $synchronizationContract->getTargetId() !== null
            && $synchronizationContract->getTargetHash() !== null
        ) {
            // We checked the source so log that
            $synchronizationContract->setSourceLastChecked(new DateTime());

            // The object has not changed and neither config nor mapping have been updated since last check
            if ($contractLog instanceof SynchronizationContractLog) {
                $contractLog = $this->synchronizationContractLogMapper->update($contractLog);
            }

            return [
                'log'      => $contractLog ? $contractLog->jsonSerialize() : [],
                'contract' => $synchronizationContract->jsonSerialize(),
            ];
        }

        // Update contract metadata since we're processing it
        $synchronizationContract->setOriginHash($originHash);
        $synchronizationContract->setSourceLastChanged(new DateTime());
        $synchronizationContract->setSourceLastChecked(new DateTime());

        // Execute mapping if found
        if ($sourceTargetMapping) {
            $targetObject = $this->mappingService->executeMapping(
                mapping: $sourceTargetMapping,
                input: $object
            );
        } else {
            $targetObject = $object;
        }

        if (isset($contractLog) === true) {
            $contractLog->setTarget($targetObject);
        }

        // Process pre-update rules
        if ($synchronization->getActions() !== []) {
            $targetObject = $this->ruleProcessorService->processRules(
                synchronization: $synchronization,
                data: $targetObject,
                timing: 'before'
            );
        }

        // Set the target hash
        $targetHash = md5(serialize($targetObject));

        $synchronizationContract->setTargetHash($targetHash);
        $synchronizationContract->setTargetLastChanged(new DateTime());
        $synchronizationContract->setTargetLastSynced(new DateTime());
        $synchronizationContract->setSourceLastSynced(new DateTime());

        // Handle test mode
        if ($isTest === true) {
            // Return test data without updating target
            if ($contractLog instanceof SynchronizationContractLog) {
                $contractLog->setTargetResult('test');
                $contractLog = $this->synchronizationContractLogMapper->update($contractLog);
            }

            return [
                'log'      => $contractLog ? $contractLog->jsonSerialize() : [],
                'contract' => $synchronizationContract->jsonSerialize(),
            ];
        }

        // Update target via the appropriate handler
        $synchronizationContract = $this->targetHandlerRegistry->updateTarget(
            contract: $synchronizationContract,
            synchronization: $synchronization,
            targetObject: $targetObject
        );

        // Process post-update rules
        if ($synchronization->getTargetType() === 'register/schema') {
            [
                $registerId,
                $schemaId,
            ] = explode('/', $synchronization->getTargetId());

            $this->ruleProcessorService->processRules(
                synchronization: $synchronization,
                data: $targetObject,
                timing: 'after',
                objectId: $synchronizationContract->getTargetId(),
                registerId: $registerId,
                schemaId: $schemaId
            );
        }

        // Update contract log
        if ($contractLog instanceof SynchronizationContractLog) {
            $contractLog->setTargetResult($synchronizationContract->getTargetLastAction());
            $contractLog = $this->synchronizationContractLogMapper->update($contractLog);
        }

        // Save contract
        if ($synchronizationContract->getId()) {
            $synchronizationContract = $this->synchronizationContractMapper->update($synchronizationContract);
        } else {
            if ($synchronizationContract->getUuid() === null) {
                $synchronizationContract->setUuid(Uuid::v4());
            }

            $synchronizationContract = $this->synchronizationContractMapper->insertOrUpdate($synchronizationContract);
        }

        return [
            'log'      => $contractLog ? $contractLog->jsonSerialize() : [],
            'contract' => $synchronizationContract->jsonSerialize(),
        ];

    }//end synchronizeContract()


    /**
     * Fetches additional data for a given object based on the synchronization configuration.
     *
     * @param Synchronization     $synchronization The synchronization instance
     * @param array<string,mixed> $extraDataConfig The configuration array for retrieving extra data
     * @param array<string,mixed> $object          The original object for which extra data needs to be fetched
     * @param string|null         $originId        Optional origin ID for static endpoint configuration
     *
     * @return array<string,mixed> The object merged with extra data or the extra data itself
     *
     * @throws Exception If endpoint configuration is invalid or endpoint cannot be determined
     */
    private function fetchExtraDataForObject(
        Synchronization $synchronization,
        array $extraDataConfig,
        array $object,
        ?string $originId=null
    ): array {
        // Return original object if no endpoint configuration exists
        if (isset($extraDataConfig[self::EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION]) === false
            && isset($extraDataConfig[self::EXTRA_DATA_STATIC_ENDPOINT_LOCATION]) === false
        ) {
            return $object;
        }

        $endpoint = null;

        // Get endpoint from object if dynamic endpoint is configured
        if (isset($extraDataConfig[self::EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION]) === true) {
            $dotObject = new Dot($object);
            $endpoint  = $dotObject->get($extraDataConfig[self::EXTRA_DATA_DYNAMIC_ENDPOINT_LOCATION] ?? null);
        }

        // Get endpoint from static configuration if available
        if (isset($extraDataConfig[self::EXTRA_DATA_STATIC_ENDPOINT_LOCATION]) === true) {
            if ($originId === null) {
                $originId = $this->getOriginId($synchronization, $object);
            }

            // Override origin ID if endpoint ID location is specified
            if (isset($extraDataConfig['endpointIdLocation']) === true) {
                $dotObject = new Dot($object);
                $originId  = $dotObject->get($extraDataConfig['endpointIdLocation']);
            }

            $endpoint = $extraDataConfig[self::EXTRA_DATA_STATIC_ENDPOINT_LOCATION];

            // Replace origin ID placeholders in endpoint
            $endpoint = str_replace(
                search: [
                    '{{ originId }}',
                    '{{originId}}',
                ],
                replace: $originId,
                subject: $endpoint
            );

            // Handle sub-object ID replacement if configured
            if (isset($extraDataConfig['subObjectId']) === true) {
                $objectDot   = new Dot($object);
                $subObjectId = $objectDot->get($extraDataConfig['subObjectId']);
                if ($subObjectId !== null) {
                    $endpoint = str_replace(
                        search: [
                            '{{ subObjectId }}',
                            '{{subObjectId}}',
                        ],
                        replace: $subObjectId,
                        subject: $endpoint
                    );
                }
            }
        }//end if

        // Validate endpoint existence
        if ($endpoint === null) {
            throw new Exception(
                sprintf(
                    'Could not get static or dynamic endpoint, object: %s.',
                    json_encode($object)
                )
            );
        }

        // Handle config key unset if specified
        $sourceConfig = $synchronization->getSourceConfig();
        if (isset($extraDataConfig[self::UNSET_CONFIG_KEY_LOCATION]) === true
            && isset($sourceConfig[$extraDataConfig[self::UNSET_CONFIG_KEY_LOCATION]]) === true
        ) {
            unset($sourceConfig[$extraDataConfig[self::UNSET_CONFIG_KEY_LOCATION]]);
            $synchronization->setSourceConfig($sourceConfig);
        }

        // Fetch the object from the source
        $extraData = $this->callService->fetchObjectFromSource(
            synchronization: $synchronization,
            endpoint: $endpoint
        );

        // Handle per-result extra data configuration
        if (isset($extraDataConfig['extraDataConfigPerResult']) === true) {
            $dotObject = new Dot($extraData);
            $results   = $dotObject->get($extraDataConfig['resultsLocation']);

            foreach ($results as $key => $result) {
                $results[$key] = $this->fetchExtraDataForObject(
                    synchronization: $synchronization,
                    extraDataConfig: $extraDataConfig['extraDataConfigPerResult'],
                    object: $result,
                    originId: $originId
                );
            }

            $extraData = $results;
        }

        // Set custom key for extra data if configured
        if (isset($extraDataConfig[self::KEY_FOR_EXTRA_DATA_LOCATION]) === true) {
            $extraData = [$extraDataConfig[self::KEY_FOR_EXTRA_DATA_LOCATION] => $extraData];
        }

        // Merge with original object if configured
        if (isset($extraDataConfig[self::MERGE_EXTRA_DATA_OBJECT_LOCATION]) === true
            && ($extraDataConfig[self::MERGE_EXTRA_DATA_OBJECT_LOCATION] === true
            || $extraDataConfig[self::MERGE_EXTRA_DATA_OBJECT_LOCATION] === 'true')
        ) {
            return array_merge($object, $extraData);
        }

        return $extraData;

    }//end fetchExtraDataForObject()


    /**
     * Fetches multiple extra data entries for an object.
     *
     * @param Synchronization     $synchronization The synchronization instance
     * @param array<string,mixed> $sourceConfig    The source configuration
     * @param array<string,mixed> $object          The original object
     *
     * @return array<string,mixed> The updated object with merged extra data
     *
     * @throws Exception If extra data fetching fails
     */
    private function fetchMultipleExtraData(
        Synchronization $synchronization,
        array $sourceConfig,
        array $object
    ): array {
        if (isset($sourceConfig[self::EXTRA_DATA_CONFIGS_LOCATION]) === true) {
            foreach ($sourceConfig[self::EXTRA_DATA_CONFIGS_LOCATION] as $extraDataConfig) {
                $object = array_merge(
                    $object,
                    $this->fetchExtraDataForObject($synchronization, $extraDataConfig, $object)
                );
            }
        }

        return $object;

    }//end fetchMultipleExtraData()


    /**
     * Maps an object using source hash mapping configuration.
     *
     * @param Synchronization     $synchronization The synchronization instance
     * @param array<string,mixed> $object          The input object to map
     *
     * @return array<string,mixed> The mapped object
     *
     * @throws Exception If mapping fails
     */
    private function mapHashObject(
        Synchronization $synchronization,
        array $object
    ): array {
        if ($synchronization->getSourceHashMapping() !== '') {
            try {
                $sourceHashMapping = $this->mappingMapper->find(
                    $synchronization->getSourceHashMapping()
                );

                // Execute mapping if found
                return $this->mappingService->executeMapping(
                    mapping: $sourceHashMapping,
                    input: $object
                );
            } catch (Exception $exception) {
                // Return original object if mapping fails
                return $object;
            }
        }

        return $object;

    }//end mapHashObject()


    /**
     * Replace characters in array keys recursively.
     *
     * @param array<string,mixed> $array       The array to process
     * @param string              $toReplace   The character to replace
     * @param string              $replacement The replacement character
     *
     * @return array<string,mixed> The array with replaced key characters
     */
    public function encodeArrayKeys(
        array $array,
        string $toReplace,
        string $replacement
    ): array {
        $result = [];

        foreach ($array as $key => $value) {
            // Replace character in key
            $newKey = str_replace($toReplace, $replacement, $key);

            // Recursively process nested arrays
            if (is_array($value) === true && $value !== []) {
                $result[$newKey] = $this->encodeArrayKeys(
                    array: $value,
                    toReplace: $toReplace,
                    replacement: $replacement
                );
                continue;
            }

            $result[$newKey] = $value;
        }

        return $result;

    }//end encodeArrayKeys()


}//end class
