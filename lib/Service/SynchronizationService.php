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
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */
class SynchronizationService
{
    /**
     * The call service instance.
     *
     * Used for making API calls to external sources.
     *
     * @var CallService
     */
    private readonly CallService $callService;

    /**
     * The synchronization mapper instance.
     *
     * Used for database operations on synchronization entities.
     *
     * @var SynchronizationMapper
     */
    private readonly SynchronizationMapper $synchronizationMapper;

    /**
     * The source mapper instance.
     *
     * Used for database operations on source entities.
     *
     * @var SourceMapper
     */
    private readonly SourceMapper $sourceMapper;

    /**
     * The synchronization log mapper instance.
     *
     * Used for database operations on synchronization log entities.
     *
     * @var SynchronizationLogMapper
     */
    private readonly SynchronizationLogMapper $synchronizationLogMapper;

    /**
     * The synchronization contract mapper instance.
     *
     * Used for database operations on synchronization contract entities.
     *
     * @var SynchronizationContractMapper
     */
    private readonly SynchronizationContractMapper $synchronizationContractMapper;

    /**
     * The source handler registry instance.
     *
     * Provides access to available source handlers.
     *
     * @var SourceHandlerRegistry
     */
    private readonly SourceHandlerRegistry $sourceHandlerRegistry;

    /**
     * The target handler registry instance.
     *
     * Provides access to available target handlers.
     *
     * @var TargetHandlerRegistry
     */
    private readonly TargetHandlerRegistry $targetHandlerRegistry;

    /**
     * The synchronization object processor instance.
     *
     * Processes individual objects during synchronization.
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
     *
     * @return void
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
        // Start execution time measurement.
        $startTime = microtime(true);

        // Create log with synchronization ID and initialize results tracking.
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

        // Create initial log entry for tracking purposes.
        $log = $this->synchronizationLogMapper->createFromArray($log);

        $sourceConfig = $this->callService->applyConfigDot($synchronization->getSourceConfig());

        // Validate source ID.
        if ($synchronization->getSourceId() === '') {
            $errorMessage = 'sourceId of synchronization cannot be empty. Canceling synchronization...';
            $log->setMessage($errorMessage);
            $this->synchronizationLogMapper->update($log);
            throw new Exception($errorMessage);
        }

        // Fetch objects from source.
        try {
            $objectList = $this->getAllObjectsFromSource(
                synchronization: $synchronization,
                isTest: $isTest
            );
        } catch (TooManyRequestsHttpException $e) {
            $rateLimitException = $e;
        }

        // Update log with object count.
        $result = $log->getResult();
        $result['objects']['found'] = count($objectList);

        $synchronizedTargetIds = [];

        // Handle single object case.
        if (isset($sourceConfig['resultsPosition']) && $sourceConfig['resultsPosition'] === '_object') {
            $objectList = [$objectList];
            $result['objects']['found'] = count($objectList);
        }

        // Process each object.
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

        // Handle object deletion.
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

        // Update log with final results.
        $log->setResult($result);
        $log->setEnded(microtime(true));
        $log->setDuration($log->getEnded() - $startTime);

        $this->synchronizationLogMapper->update($log);

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

        // Return final results.
        return [
            'id'       => $log->getId(),
            'objects'  => $result['objects'],
            'logs'     => $result['logs'],
            'duration' => $log->getDuration(),
        ];

    }//end synchronize()


    /**
     * Get all objects from a source based on synchronization configuration.
     *
     * @param Synchronization $synchronization The synchronization configuration
     * @param bool            $isTest          Whether this is a test run
     *
     * @return array List of objects from the source
     *
     * @throws GuzzleException
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getAllObjectsFromSource(Synchronization $synchronization, bool $isTest=false): array
    {
        // Validate required properties.
        if (empty($synchronization->getSourceId())) {
            throw new Exception('No source ID configured for this synchronization.');
        }

        // Get the source type and determine appropriate handler.
        $source = $this->sourceMapper->find($synchronization->getSourceId());
        $sourceType = $source->getType();

        // Get the appropriate source handler.
        $sourceHandler = $this->sourceHandlerRegistry->getHandler($sourceType);
        if ($sourceHandler === null) {
            throw new Exception("No handler available for source type: $sourceType");
        }

        // Get source configuration from synchronization.
        $sourceConfig = $synchronization->getSourceConfig();
        
        if (empty($sourceConfig)) {
            throw new Exception("Source configuration is empty for synchronization: {$synchronization->getId()}");
        }

        // Get all objects from source.
        return $sourceHandler->getAllObjects($source, $sourceConfig);

    }//end getAllObjectsFromSource()


    /**
     * Synchronize an object to a target.
     *
     * @param ObjectEntity                $object                The object to synchronize
     * @param SynchronizationContract|null $synchronizationContract Optional contract for tracking
     * @param bool                         $force                Force update regardless of changes
     * @param bool                         $test                 Whether this is a test run
     * @param SynchronizationLog|null      $log                  Optional log for tracking
     *
     * @return array Result of the synchronization operation
     *
     * @throws Exception
     */
    public function synchronizeToTarget(
        ObjectEntity $object,
        ?SynchronizationContract $synchronizationContract=null,
        bool $force=false,
        bool $test=false,
        ?SynchronizationLog $log=null
    ): array {
        // Check if contract is provided.
        if ($synchronizationContract === null) {
            throw new Exception('No synchronization contract provided.');
        }

        // Get target configuration.
        $targetConfig = $synchronizationContract->getTargetConfig();
        if (empty($targetConfig)) {
            throw new Exception('Target configuration is empty.');
        }

        // Get target type and determine appropriate handler.
        $targetType = $targetConfig['type'] ?? null;
        if ($targetType === null) {
            throw new Exception('No target type specified in configuration.');
        }

        // Get the appropriate target handler.
        $targetHandler = $this->targetHandlerRegistry->getHandler($targetType);
        if ($targetHandler === null) {
            throw new Exception("No handler available for target type: $targetType");
        }

        // Process the object with the target handler.
        $result = $targetHandler->processObject($object, $targetConfig, $force, $test);

        // For contract-based synchronization, update contract status.
        if ($synchronizationContract !== null && $test === false && $result['targetId'] !== null && !empty($result['targetId'])) {
            $synchronizationContract->setTargetId($result['targetId']);
            $synchronizationContract->setLastSync(new \DateTime());
            $this->synchronizationContractMapper->update($synchronizationContract);
        }

        // Return the result.
        return $result;

    }//end synchronizeToTarget()


    /**
     * Get a synchronization by ID or filters.
     *
     * @param string|int|null $id      The ID of the synchronization to retrieve
     * @param array           $filters Optional filters to apply when ID is not provided
     *
     * @return Synchronization The found synchronization
     *
     * @throws Exception When no synchronization is found
     */
    public function getSynchronization(
        string|int|null $id=null,
        array $filters=[]
    ): Synchronization {
        // Handle retrieval by ID.
        if ($id !== null) {
            return $this->synchronizationMapper->find($id);
        }

        // Handle retrieval by filters.
        $synchronizations = $this->synchronizationMapper->findAll(filters: $filters);
        
        if (count($synchronizations) === 0) {
            throw new Exception('No synchronization found matching the provided filters.');
        }

        return $synchronizations[0];

    }//end getSynchronization()


}//end class
