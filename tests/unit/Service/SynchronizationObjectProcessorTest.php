<?php

declare(strict_types=1);

/**
 * SynchronizationObjectProcessorTest.php
 *
 * Unit tests for the SynchronizationObjectProcessor class.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service
 * @author    Conduction <info@conduction.nl>
 * @copyright 2023 Conduction
 * @license   https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @version   GIT: <git_id>
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use Exception;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\SynchronizationLog;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Service\SynchronizationObjectProcessor;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerInterface;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Class SynchronizationObjectProcessorTest
 *
 * Unit tests for the SynchronizationObjectProcessor class.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service
 * @author    Conduction <info@conduction.nl>
 * @license   EUPL-1.2
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */
class SynchronizationObjectProcessorTest extends TestCase
{
    /**
     * The call service mock.
     *
     * @var CallService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $callService;

    /**
     * The mapping service mock.
     *
     * @var MappingService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mappingService;

    /**
     * The mapping mapper mock.
     *
     * @var MappingMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mappingMapper;

    /**
     * The synchronization contract mapper mock.
     *
     * @var SynchronizationContractMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $synchronizationContractMapper;

    /**
     * The synchronization contract log mapper mock.
     *
     * @var SynchronizationContractLogMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $synchronizationContractLogMapper;

    /**
     * The target handler registry mock.
     *
     * @var TargetHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $targetHandlerRegistry;

    /**
     * The processor to test.
     *
     * @var SynchronizationObjectProcessor
     */
    private $processor;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->callService = $this->createMock(CallService::class);
        $this->mappingService = $this->createMock(MappingService::class);
        $this->mappingMapper = $this->createMock(MappingMapper::class);
        $this->synchronizationContractMapper = $this->createMock(SynchronizationContractMapper::class);
        $this->synchronizationContractLogMapper = $this->createMock(SynchronizationContractLogMapper::class);
        $this->targetHandlerRegistry = $this->createMock(TargetHandlerRegistry::class);

        // Create the processor to test
        $this->processor = new SynchronizationObjectProcessor(
            $this->callService,
            $this->mappingService,
            $this->mappingMapper,
            $this->synchronizationContractMapper,
            $this->synchronizationContractLogMapper,
            $this->targetHandlerRegistry
        );
    }

    /**
     * Test the processSynchronizationObject method with a new object.
     *
     * @return void
     * 
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     * @throws \OCP\DB\Exception
     */
    public function testProcessSynchronizationObjectNew(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setUuid('sync-uuid-123');
        $synchronization->setTargetType('api');
        $synchronization->setMappingId(1);

        $object = ['id' => 'object-1', 'name' => 'Test Object'];

        $result = [
            'objects' => [
                'found' => 1,
                'skipped' => 0,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'invalid' => 0
            ],
            'contracts' => [],
            'logs' => []
        ];

        $log = new SynchronizationLog();
        $log->setId(1);
        $log->setSynchronizationId('sync-uuid-123');
        $log->setResult($result);

        $mapping = new Mapping();
        $mapping->setId(1);
        $mapping->setMapping('{ "id": "$.id", "name": "$.name" }');
        
        $contract = new SynchronizationContract();
        $contract->setId(1);
        $contract->setSynchronizationId(1);
        $contract->setOriginId('object-1');
        
        $contractLog = new SynchronizationContractLog();
        $contractLog->setId(1);
        $contractLog->setContractId(1);
        
        $targetHandler = $this->createMock(TargetHandlerInterface::class);
        
        // Configure mocks
        $this->mappingMapper->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mapping);
            
        $this->mappingService->expects($this->once())
            ->method('applyMapping')
            ->willReturn(['id' => 'object-1', 'name' => 'Test Object']);
            
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findByOriginId')
            ->with('object-1')
            ->willReturn(null);
            
        $this->synchronizationContractMapper->expects($this->once())
            ->method('createFromArray')
            ->willReturn($contract);
            
        $this->targetHandlerRegistry->expects($this->once())
            ->method('getHandlerFromSynchronization')
            ->with($synchronization)
            ->willReturn($targetHandler);
            
        $targetHandler->expects($this->once())
            ->method('createObject')
            ->willReturn(['id' => 'target-object-1']);
            
        $this->synchronizationContractLogMapper->expects($this->once())
            ->method('createFromArray')
            ->willReturn($contractLog);
            
        $this->synchronizationContractMapper->expects($this->once())
            ->method('update')
            ->with($contract);
        
        // Call the method
        $processResult = $this->processor->processSynchronizationObject(
            $synchronization,
            $object,
            $result,
            false,
            false,
            $log
        );
        
        // Assert the result
        $this->assertEquals('target-object-1', $processResult['targetId']);
        $this->assertEquals(1, $processResult['result']['objects']['created']);
        $this->assertEquals(0, $processResult['result']['objects']['updated']);
    }
    
    /**
     * Test the processSynchronizationObject method with an existing object.
     *
     * @return void
     * 
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     * @throws \OCP\DB\Exception
     */
    public function testProcessSynchronizationObjectExisting(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setUuid('sync-uuid-123');
        $synchronization->setTargetType('api');
        $synchronization->setMappingId(1);
        
        $object = ['id' => 'object-1', 'name' => 'Test Object Updated'];
        
        $result = [
            'objects' => [
                'found' => 1,
                'skipped' => 0,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'invalid' => 0
            ],
            'contracts' => [],
            'logs' => []
        ];
        
        $log = new SynchronizationLog();
        $log->setId(1);
        $log->setSynchronizationId('sync-uuid-123');
        $log->setResult($result);
        
        $mapping = new Mapping();
        $mapping->setId(1);
        $mapping->setMapping('{ "id": "$.id", "name": "$.name" }');
        
        $contract = new SynchronizationContract();
        $contract->setId(1);
        $contract->setSynchronizationId(1);
        $contract->setOriginId('object-1');
        $contract->setTargetId('target-object-1');
        $contract->setData(['id' => 'object-1', 'name' => 'Test Object']);
        
        $contractLog = new SynchronizationContractLog();
        $contractLog->setId(1);
        $contractLog->setContractId(1);
        
        $targetHandler = $this->createMock(TargetHandlerInterface::class);
        
        // Configure mocks
        $this->mappingMapper->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mapping);
            
        $this->mappingService->expects($this->once())
            ->method('applyMapping')
            ->willReturn(['id' => 'object-1', 'name' => 'Test Object Updated']);
            
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findByOriginId')
            ->with('object-1')
            ->willReturn($contract);
            
        $this->targetHandlerRegistry->expects($this->once())
            ->method('getHandlerFromSynchronization')
            ->with($synchronization)
            ->willReturn($targetHandler);
            
        $targetHandler->expects($this->once())
            ->method('objectHasChanged')
            ->willReturn(true);
            
        $targetHandler->expects($this->once())
            ->method('updateObject')
            ->willReturn(['id' => 'target-object-1']);
            
        $this->synchronizationContractLogMapper->expects($this->once())
            ->method('createFromArray')
            ->willReturn($contractLog);
            
        $this->synchronizationContractMapper->expects($this->once())
            ->method('update')
            ->with($contract);
        
        // Call the method
        $processResult = $this->processor->processSynchronizationObject(
            $synchronization,
            $object,
            $result,
            false,
            false,
            $log
        );
        
        // Assert the result
        $this->assertEquals('target-object-1', $processResult['targetId']);
        $this->assertEquals(0, $processResult['result']['objects']['created']);
        $this->assertEquals(1, $processResult['result']['objects']['updated']);
    }
    
    /**
     * Test the processSynchronizationObject method with a skipped object.
     *
     * @return void
     * 
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     * @throws \OCP\DB\Exception
     */
    public function testProcessSynchronizationObjectSkipped(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setUuid('sync-uuid-123');
        $synchronization->setTargetType('api');
        $synchronization->setMappingId(1);
        
        $object = ['id' => 'object-1', 'name' => 'Test Object'];
        
        $result = [
            'objects' => [
                'found' => 1,
                'skipped' => 0,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'invalid' => 0
            ],
            'contracts' => [],
            'logs' => []
        ];
        
        $log = new SynchronizationLog();
        $log->setId(1);
        $log->setSynchronizationId('sync-uuid-123');
        $log->setResult($result);
        
        $mapping = new Mapping();
        $mapping->setId(1);
        $mapping->setMapping('{ "id": "$.id", "name": "$.name" }');
        
        $contract = new SynchronizationContract();
        $contract->setId(1);
        $contract->setSynchronizationId(1);
        $contract->setOriginId('object-1');
        $contract->setTargetId('target-object-1');
        $contract->setData(['id' => 'object-1', 'name' => 'Test Object']);
        
        $targetHandler = $this->createMock(TargetHandlerInterface::class);
        
        // Configure mocks
        $this->mappingMapper->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($mapping);
            
        $this->mappingService->expects($this->once())
            ->method('applyMapping')
            ->willReturn(['id' => 'object-1', 'name' => 'Test Object']);
            
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findByOriginId')
            ->with('object-1')
            ->willReturn($contract);
            
        $this->targetHandlerRegistry->expects($this->once())
            ->method('getHandlerFromSynchronization')
            ->with($synchronization)
            ->willReturn($targetHandler);
            
        $targetHandler->expects($this->once())
            ->method('objectHasChanged')
            ->willReturn(false);
            
        // Updated should not be called
        $targetHandler->expects($this->never())->method('updateObject');
        
        // Call the method
        $processResult = $this->processor->processSynchronizationObject(
            $synchronization,
            $object,
            $result,
            false,
            false,
            $log
        );
        
        // Assert the result
        $this->assertEquals('target-object-1', $processResult['targetId']);
        $this->assertEquals(0, $processResult['result']['objects']['created']);
        $this->assertEquals(0, $processResult['result']['objects']['updated']);
        $this->assertEquals(1, $processResult['result']['objects']['skipped']);
    }
} 