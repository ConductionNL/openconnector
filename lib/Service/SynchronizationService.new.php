<?php
/**
 * SynchronizationService.php
 *
 * This file contains the SynchronizationService class which orchestrates synchronization
 * operations between different data sources and targets in the OpenConnector app.
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

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationLog;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Service\SourceHandler\SourceHandlerRegistry;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerRegistry;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

/**
 * Class SynchronizationService
 *
 * Orchestrates the synchronization process between data sources and targets.
 *
 * @category Service
 * @package  OCA\OpenConnector\Service
 * @author   Conduction <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/nextcloud/server/tree/master/apps/openconnector
 */
class SynchronizationService
{

    /**
     * The call service instance.
     *
     * @var CallService
     */
    private readonly CallService $callService;

    /**
     * The synchronization mapper instance.
     *
     * @var SynchronizationMapper
     */
    private readonly SynchronizationMapper $synchronizationMapper;

    /**
     * The source mapper instance.
     *
     * @var SourceMapper
     */
    private readonly SourceMapper $sourceMapper;

    /**
     * The synchronization log mapper instance.
     *
     * @var SynchronizationLogMapper
     */
    private readonly SynchronizationLogMapper $synchronizationLogMapper;

    /**
     * The synchronization contract mapper instance.
     *
     * @var SynchronizationContractMapper
     */
    private readonly SynchronizationContractMapper $synchronizationContractMapper;

    /**
     * The source handler registry instance.
     *
     * @var SourceHandlerRegistry
     */
    private readonly SourceHandlerRegistry $sourceHandlerRegistry;

    /**
     * The target handler registry instance.
     *
     * @var TargetHandlerRegistry
     */
    private readonly TargetHandlerRegistry $targetHandlerRegistry;

    /**
     * The synchronization object processor instance.
     *
     * @var SynchronizationObjectProcessor
     */
    private readonly SynchronizationObjectProcessor $objectProcessor;


    /**
     * Constructor
     *
     * @param CallService                    $callService                   Call service for API requests
     * @param SynchronizationMapper          $synchronizationMapper         Mapper for synchronization entities
     * @param SourceMapper                   $sourceMapper                  Mapper for source entities
     * @param SynchronizationLogMapper       $synchronizationLogMapper      Mapper for log entities
     * @param SynchronizationContractMapper  $synchronizationContractMapper Mapper for contract entities
     * @param SourceHandlerRegistry          $sourceHandlerRegistry         Registry for source handlers
     * @param TargetHandlerRegistry          $targetHandlerRegistry         Registry for target handlers
     * @param SynchronizationObjectProcessor $objectProcessor               Processor for objects
     */
    public function __construct(
        CallService $callService,
        SynchronizationMapper $synchronizationMapper,
        SourceMapper $sourceMapper,
        SynchronizationLogMapper $synchronizationLogMapper,
        SynchronizationContractMapper $synchronizationContractMapper,
        SourceHandlerRegistry $sourceHandlerRegistry,
        TargetHandlerRegistry $targetHandlerRegistry,
        SynchronizationObjectProcessor $objectProcessor
    ) {
        $this->callService           = $callService;
        $this->synchronizationMapper = $synchronizationMapper;
        $this->sourceMapper          = $sourceMapper;
        $this->synchronizationLogMapper      = $synchronizationLogMapper;
        $this->synchronizationContractMapper = $synchronizationContractMapper;
        $this->sourceHandlerRegistry         = $sourceHandlerRegistry;
        $this->targetHandlerRegistry         = $targetHandlerRegistry;
        $this->objectProcessor = $objectProcessor;

    }//end __construct()


    /**
     * Synchronizes a given synchronization (or a complete source).
     *
     * @param Synchronization $synchronization The synchronization to run
     * @param bool            $isTest          False by default, currently added for synchronization-test endpoint
     * @param bool            $force           False by default, if true, the object will be updated regardless of changes
     *
     * @return array The synchronization log and results
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws GuzzleException
     * @throws LoaderError
     * @throws SyntaxError
     * @throws MultipleObjectsReturnedException
     * @throws \OCP\DB\Exception
     * @throws Exception
     * @throws TooManyRequestsHttpException
     */
    public function synchronize(
        Synchronization $synchronization,
        bool $isTest=false,
        bool $force=false
    ): array {
        // Start execution time measurement
        $startTime = microtime(true);

        // Create log with synchronization ID and initialize results tracking
        $log = [
            'synchronizationId' => $synchronization->getUuid(),
            'result'            => [
                'objects'   => [
                    'found'   => 0,
                    'skipped' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'deleted' => 0,
                    'invalid' => 0,
                ],
                'contracts' => [],
                'logs'      => [],
            ],
            'test'              => $isTest,
            'force'             => $force,
        ];

        // Create initial log entry for tracking purposes
        $log = $this->synchronizationLogMapper->createFromArray($log);

        $sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());

        // Validate source ID
        if ($synchronization->getSourceId() === '') {
            $errorMessage = 'sourceId of synchronization cannot be empty. Canceling synchronization...';
            $log->setMessage($errorMessage);
            $this->synchronizationLogMapper->update($log);
            throw new Exception($errorMessage);
        }

        // Fetch objects from source
        try {
            $objectList = $this->getAllObjectsFromSource(
                synchronization: $synchronization,
                isTest: $isTest
            );
        } catch (TooManyRequestsHttpException $e) {
            $rateLimitException = $e;
        }

        // Update log with object count
        $result = $log->getResult();
        $result['objects']['found'] = count($objectList);

        $synchronizedTargetIds = [];

        // Handle single object case
        if (isset($sourceConfig['resultsPosition']) && $sourceConfig['resultsPosition'] === '_object') {
            $objectList = [$objectList];
            $result['objects']['found'] = count($objectList);
        }

        // Process each object
        foreach ($objectList as $object) {
            $processResult = $this->objectProcessor->processSynchronizationObject(
                synchronization: $synchronization,
                object: $object,
                result: $result,
                isTest: $isTest,
                force: $force,
                log: $log
            );

            $result = $processResult['result'];

            if ($processResult['targetId'] !== null) {
                $synchronizedTargetIds[] = $processResult['targetId'];
            }
        }

        // Handle object deletion
        if ($isTest === false) {
            $result['objects']['deleted'] = $this->targetHandlerRegistry->deleteInvalidObjects(
                synchronization: $synchronization,
                synchronizedTargetIds: $synchronizedTargetIds
            );
        } else {
            $result['objects']['deleted'] = 0;
        }

        // Process follow-up synchronizations
        foreach ($synchronization->getFollowUps() as $followUp) {
            $followUpSynchronization = $this->synchronizationMapper->find($followUp);
            $this->synchronize(
                synchronization: $followUpSynchronization,
                isTest: $isTest,
                force: $force
            );
        }

        $log->setResult($result);

        // Handle rate limit exception
        if (isset($rateLimitException) === true) {
            $log->setMessage($rateLimitException->getMessage());
            $this->synchronizationLogMapper->update($log);
            throw new TooManyRequestsHttpException(
                message: $rateLimitException->getMessage(),
                code: 429,
                headers: $rateLimitException->getHeaders()
            );
        }

        // Finalize log
        $executionTime = round((microtime(true) - $startTime) * 1000);
        $log->setExecutionTime($executionTime);
        $log->setMessage('Success');
        $this->synchronizationLogMapper->update($log);

        return $log->jsonSerialize();

    }//end synchronize()


    /**
     * Get all the objects from a source.
     *
     * @param Synchronization $synchronization The synchronization to run
     * @param bool            $isTest          False by default, currently added for synchronization-test endpoint
     *
     * @return array The objects fetched from the source
     *
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws NotFoundExceptionInterface
     * @throws \OCP\DB\Exception
     */
    public function getAllObjectsFromSource(Synchronization $synchronization, bool $isTest=false): array
    {
        $source       = $this->sourceMapper->find($synchronization->getSourceId());
        $sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());

        switch ($source->getType()) {
            case 'register/schema':
                // @todo: implement for register/schema type
                return [];

            case 'api':
            case 'xml':
            case 'json-api':
            case 'soap':
                // Extract configuration for the API call
                $endpoint = ($sourceConfig['endpoint'] ?? '');
                $headers  = ($sourceConfig['headers'] ?? []);
                $query    = ($sourceConfig['query'] ?? []);

                // Set current page based on rate limit
                $currentPage = 1;
                if ($source->getRateLimitLimit() !== null) {
                    $currentPage = ($synchronization->getCurrentPage() ?? 1);
                }

                // Use the appropriate handler through the registry
                $objects = $this->sourceHandlerRegistry->getAllObjects(
                source: $source,
                config: $sourceConfig,
                isTest: $isTest,
                currentPage: $currentPage,
                headers: $headers,
                query: $query
                );

                // Reset page counter after synchronization if not in test mode
                if ($isTest === false) {
                    $synchronization->setCurrentPage(1);
                    $this->synchronizationMapper->update($synchronization);
                }
                return $objects;

            case 'database':
                // @todo: implement for database type
                return [];

            default:
                return [];
        }//end switch

    }//end getAllObjectsFromSource()


    /**
     * Synchronize data to a target.
     *
     * This method provides an entry point for OpenRegister to synchronize objects to targets.
     * The synchronizationContract should be given if the normal procedure to find the contract (on originId)
     * is not available to the contract that should be updated.
     *
     * @param ObjectEntity                 $object                  The object to synchronize
     * @param SynchronizationContract|null $synchronizationContract If given: the contract that should be updated
     * @param bool                         $force                   If true, object will be updated regardless of changes
     * @param bool                         $test                    If true, it's a test run only
     * @param SynchronizationLog|null      $log                     Optional synchronization log
     *
     * @return array The updated synchronization contracts
     *
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws SyntaxError
     * @throws \OCP\DB\Exception
     * @throws GuzzleException
     */
    public function synchronizeToTarget(
        ObjectEntity $object,
        ?SynchronizationContract $synchronizationContract=null,
        bool $force=false,
        bool $test=false,
        ?SynchronizationLog $log=null
    ): array {
        $objectId = $object->getUuid();

        // Find contract or create new one
        if ($synchronizationContract === null) {
            $synchronizationContract = $this->synchronizationContractMapper->findByOriginId($objectId);
        }

        // Find synchronization for this object's schema
        $synchronizations = $this->synchronizationMapper->findAll(
            filters: [
                'source_type' => 'register/schema',
                'source_id'   => "{$object->getRegister()}/{$object->getSchema()}",
            ]
        );

        if (count($synchronizations) === 0) {
            return [];
        }

        $synchronization = $synchronizations[0];

        // Create new contract if not found
        if ($synchronizationContract instanceof SynchronizationContract === false) {
            $synchronizationContract = $this->synchronizationContractMapper->createFromArray(
                [
                    'synchronizationId' => $synchronization->getId(),
                    'originId'          => $objectId,
                ]
            );
        }

        // Use the object processor to handle the synchronization
        $synchronizationContractResult = $this->objectProcessor->synchronizeContract(
            synchronizationContract: $synchronizationContract,
            synchronization: $synchronization,
            object: $object->jsonSerialize(),
            isTest: $test,
            force: $force,
            log: $log
        );

        // Determine contract to return
        if (isset($synchronizationContractResult['contract'])
            && is_array($synchronizationContractResult['contract'])
            && isset($synchronizationContractResult['contract']['id'])
        ) {
            // Return contract from result
            $contract = $this->synchronizationContractMapper->find(
                $synchronizationContractResult['contract']['id']
            );
            return [$contract];
        }

        // Return original contract if no result contract
        return [$synchronizationContract];

    }//end synchronizeToTarget()


    /**
     * Fetch a synchronization by ID or other characteristics.
     *
     * @param string|int|null     $id      The synchronization ID
     * @param array<string,mixed> $filters Additional filters to find the synchronization
     *
     * @return Synchronization The found synchronization
     *
     * @throws DoesNotExistException When synchronization not found
     */
    public function getSynchronization(
        (string | int | )null $id=null,
        array $filters=[]
    ): Synchronization {
        // Find by ID if provided
        if ($id !== null) {
            return $this->synchronizationMapper->find(intval($id));
        }

        // Find by filters
        /*
         * @var Synchronization[] $synchronizations
         */
        $synchronizations = $this->synchronizationMapper->findAll(filters: $filters);

        if (count($synchronizations) === 0) {
            throw new DoesNotExistException('The synchronization you are looking for does not exist.');
        }

        return $synchronizations[0];

    }//end getSynchronization()


}//end class
