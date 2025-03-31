<?php

declare(strict_types=1);

/**
 * SynchronizationServiceTest.php
 *
 * Unit tests for the SynchronizationService class.
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
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationLog;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Service\SynchronizationObjectProcessor;
use OCA\OpenConnector\Service\SourceHandler\SourceHandlerRegistry;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerRegistry;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;

/**
 * Class SynchronizationServiceTest
 *
 * Unit tests for the SynchronizationService class.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service
 * @author    Conduction <info@conduction.nl>
 * @license   EUPL-1.2
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */
class SynchronizationServiceTest extends TestCase
{
    /**
     * The call service mock.
     *
     * @var CallService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $callService;

    /**
     * The synchronization mapper mock.
     *
     * @var SynchronizationMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $synchronizationMapper;

    /**
     * The source mapper mock.
     *
     * @var SourceMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sourceMapper;

    /**
     * The synchronization log mapper mock.
     *
     * @var SynchronizationLogMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $synchronizationLogMapper;

    /**
     * The synchronization contract mapper mock.
     *
     * @var SynchronizationContractMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $synchronizationContractMapper;

    /**
     * The source handler registry mock.
     *
     * @var SourceHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sourceHandlerRegistry;

    /**
     * The target handler registry mock.
     *
     * @var TargetHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $targetHandlerRegistry;

    /**
     * The synchronization object processor mock.
     *
     * @var SynchronizationObjectProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectProcessor;

    /**
     * The service to test.
     *
     * @var SynchronizationService
     */
    private $service;

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
        $this->synchronizationMapper = $this->createMock(SynchronizationMapper::class);
        $this->sourceMapper = $this->createMock(SourceMapper::class);
        $this->synchronizationLogMapper = $this->createMock(SynchronizationLogMapper::class);
        $this->synchronizationContractMapper = $this->createMock(SynchronizationContractMapper::class);
        $this->sourceHandlerRegistry = $this->createMock(SourceHandlerRegistry::class);
        $this->targetHandlerRegistry = $this->createMock(TargetHandlerRegistry::class);
        $this->objectProcessor = $this->createMock(SynchronizationObjectProcessor::class);

        // Create the service to test
        $this->service = new SynchronizationService(
            $this->callService,
            $this->synchronizationMapper,
            $this->sourceMapper,
            $this->synchronizationLogMapper,
            $this->synchronizationContractMapper,
            $this->sourceHandlerRegistry,
            $this->targetHandlerRegistry,
            $this->objectProcessor
        );
    }

    /**
     * Test the getSynchronization method when finding by ID.
     *
     * @return void
     */
    public function testGetSynchronizationById(): void
    {
        // Create a mock synchronization
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setName('Test Synchronization');

        // Configure the mapper to return the mock synchronization
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($synchronization);

        // Call the method
        $result = $this->service->getSynchronization(1);

        // Assert the result
        $this->assertSame($synchronization, $result);
    }

    /**
     * Test the getSynchronization method when finding by filters.
     *
     * @return void
     */
    public function testGetSynchronizationByFilters(): void
    {
        // Create a mock synchronization
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setName('Test Synchronization');

        // Define filters
        $filters = ['source_type' => 'api'];

        // Configure the mapper to return the mock synchronization
        $this->synchronizationMapper->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo(['filters' => $filters]))
            ->willReturn([$synchronization]);

        // Call the method
        $result = $this->service->getSynchronization(null, $filters);

        // Assert the result
        $this->assertSame($synchronization, $result);
    }

    /**
     * Test the getSynchronization method when no synchronization is found.
     *
     * @return void
     */
    public function testGetSynchronizationNotFound(): void
    {
        // Define filters
        $filters = ['source_type' => 'nonexistent'];

        // Configure the mapper to return empty array
        $this->synchronizationMapper->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo(['filters' => $filters]))
            ->willReturn([]);

        // Expect an exception
        $this->expectException(DoesNotExistException::class);
        $this->expectExceptionMessage('The synchronization you are looking for does not exist.');

        // Call the method
        $this->service->getSynchronization(null, $filters);
    }

    /**
     * Test the getAllObjectsFromSource method with API source type.
     *
     * @return void
     */
    public function testGetAllObjectsFromSourceWithApiType(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setSourceId('source-1');
        $synchronization->setCurrentPage(1);
        $synchronization->setSourceConfig([
            'endpoint' => 'https://api.example.com/data',
            'headers' => ['X-API-KEY' => 'test-key'],
            'query' => ['limit' => 100]
        ]);

        $source = new Source();
        $source->setId(1);
        $source->setType('api');

        $expectedObjects = [
            ['id' => 1, 'name' => 'Object 1'],
            ['id' => 2, 'name' => 'Object 2']
        ];

        // Configure mocks
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with('source-1')
            ->willReturn($source);

        $this->callService->expects($this->once())
            ->method('applyConfigDot')
            ->with($synchronization->getSourceConfig())
            ->willReturn($synchronization->getSourceConfig());

        $this->sourceHandlerRegistry->expects($this->once())
            ->method('getAllObjects')
            ->with(
                $this->equalTo($source),
                $this->equalTo($synchronization->getSourceConfig()),
                $this->equalTo(false),
                $this->equalTo(1),
                $this->equalTo(['X-API-KEY' => 'test-key']),
                $this->equalTo(['limit' => 100])
            )
            ->willReturn($expectedObjects);

        $this->synchronizationMapper->expects($this->once())
            ->method('update')
            ->with($synchronization);

        // Call the method
        $result = $this->service->getAllObjectsFromSource($synchronization);

        // Assert the result
        $this->assertEquals($expectedObjects, $result);
        $this->assertEquals(1, $synchronization->getCurrentPage());
    }

    /**
     * Test the synchronize method with a basic successful synchronization.
     *
     * @return void
     */
    public function testSynchronizeSuccess(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setUuid('sync-uuid-123');
        $synchronization->setSourceId('source-1');
        $synchronization->setSourceConfig([
            'endpoint' => 'https://api.example.com/data'
        ]);
        $synchronization->setFollowUps([]);

        $log = new SynchronizationLog();
        $log->setId(1);
        $log->setSynchronizationId('sync-uuid-123');
        $log->setResult([
            'objects' => [
                'found' => 0,
                'skipped' => 0,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'invalid' => 0
            ],
            'contracts' => [],
            'logs' => []
        ]);

        $mockObjects = [
            ['id' => 1, 'name' => 'Object 1'],
            ['id' => 2, 'name' => 'Object 2']
        ];

        // Configure mocks
        $this->callService->expects($this->once())
            ->method('applyConfigDot')
            ->with($synchronization->getSourceConfig())
            ->willReturn($synchronization->getSourceConfig());

        $this->synchronizationLogMapper->expects($this->once())
            ->method('createFromArray')
            ->willReturn($log);

        $this->synchronizationLogMapper->expects($this->exactly(2))
            ->method('update')
            ->with($log);

        // Mock getAllObjectsFromSource behavior
        $this->sourceMapper->method('find')->willReturnCallback(function() {
            $source = new Source();
            $source->setType('api');
            return $source;
        });
        
        $this->sourceHandlerRegistry->method('getAllObjects')->willReturn($mockObjects);

        // Mock object processor behavior
        $this->objectProcessor->expects($this->exactly(2))
            ->method('processSynchronizationObject')
            ->willReturnCallback(function ($synchronization, $object, $result, $isTest, $force, $log) {
                return [
                    'result' => [
                        'objects' => [
                            'found' => 2,
                            'skipped' => 0,
                            'created' => 1,
                            'updated' => 0,
                            'deleted' => 0,
                            'invalid' => 0
                        ],
                        'contracts' => [],
                        'logs' => []
                    ],
                    'targetId' => 'target-id-' . $object['id']
                ];
            });

        $this->targetHandlerRegistry->expects($this->once())
            ->method('deleteInvalidObjects')
            ->with(
                $this->equalTo($synchronization),
                $this->equalTo(['target-id-1', 'target-id-2'])
            )
            ->willReturn(0);

        // Call the method
        $result = $this->service->synchronize($synchronization);

        // Assert the result
        $this->assertEquals($log->jsonSerialize(), $result);
        $this->assertEquals('Success', $log->getMessage());
        $this->assertNotNull($log->getExecutionTime());
    }
} 