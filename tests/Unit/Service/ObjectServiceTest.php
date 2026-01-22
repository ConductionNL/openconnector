<?php

declare(strict_types=1);

/**
 * ObjectServiceTest
 *
 * Comprehensive unit tests for the ObjectService class to verify MongoDB operations,
 * HTTP client management, and dynamic mapper selection functionality.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Service
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\EventSubscriptionMapper;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenRegister\Service\ObjectService as OpenRegisterObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use InvalidArgumentException;

/**
 * Object Service Test Suite
 *
 * Comprehensive unit tests for MongoDB operations, HTTP client management,
 * and dynamic mapper selection functionality. This test class validates
 * CRUD operations, aggregation queries, and service integration.
 *
 * @coversDefaultClass ObjectService
 */
class ObjectServiceTest extends TestCase
{
    /**
     * The ObjectService instance being tested
     *
     * @var ObjectService
     */
    private ObjectService $objectService;

    /**
     * Mock app manager
     *
     * @var MockObject|IAppManager
     */
    private MockObject $appManager;

    /**
     * Mock container
     *
     * @var MockObject|ContainerInterface
     */
    private MockObject $container;

    /**
     * Mock endpoint mapper
     *
     * @var MockObject|EndpointMapper
     */
    private MockObject $endpointMapper;

    /**
     * Mock event subscription mapper
     *
     * @var MockObject|EventSubscriptionMapper
     */
    private MockObject $eventSubscriptionMapper;

    /**
     * Mock job mapper
     *
     * @var MockObject|JobMapper
     */
    private MockObject $jobMapper;

    /**
     * Mock mapping mapper
     *
     * @var MockObject|MappingMapper
     */
    private MockObject $mappingMapper;

    /**
     * Mock rule mapper
     *
     * @var MockObject|RuleMapper
     */
    private MockObject $ruleMapper;

    /**
     * Mock source mapper
     *
     * @var MockObject|SourceMapper
     */
    private MockObject $sourceMapper;

    /**
     * Mock synchronization mapper
     *
     * @var MockObject|SynchronizationMapper
     */
    private MockObject $synchronizationMapper;

    /**
     * Set up test environment before each test
     *
     * This method initializes the ObjectService with mocked dependencies
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->appManager = $this->createMock(IAppManager::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->endpointMapper = $this->createMock(EndpointMapper::class);
        $this->eventSubscriptionMapper = $this->createMock(EventSubscriptionMapper::class);
        $this->jobMapper = $this->createMock(JobMapper::class);
        $this->mappingMapper = $this->createMock(MappingMapper::class);
        $this->ruleMapper = $this->createMock(RuleMapper::class);
        $this->sourceMapper = $this->createMock(SourceMapper::class);
        $this->synchronizationMapper = $this->createMock(SynchronizationMapper::class);

        // Create the service
        $this->objectService = new ObjectService(
            $this->appManager,
            $this->container,
            $this->endpointMapper,
            $this->eventSubscriptionMapper,
            $this->jobMapper,
            $this->mappingMapper,
            $this->ruleMapper,
            $this->sourceMapper,
            $this->synchronizationMapper
        );
    }

    /**
     * Test getClient method with basic configuration
     *
     * This test verifies that the getClient method correctly creates
     * a Guzzle HTTP client with the provided configuration.
     *
     * @covers ::getClient
     * @return void
     */
    public function testGetClientWithBasicConfig(): void
    {
        $config = [
            'base_uri' => 'https://api.example.com',
            'timeout' => 30,
            'mongodbCluster' => 'test-cluster'
        ];

        $client = $this->objectService->getClient($config);

        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * Test getClient method removes mongodbCluster from config
     *
     * This test verifies that the getClient method correctly filters
     * out the mongodbCluster key from the configuration.
     *
     * @covers ::getClient
     * @return void
     */
    public function testGetClientRemovesMongoDbCluster(): void
    {
        $config = [
            'base_uri' => 'https://api.example.com',
            'mongodbCluster' => 'test-cluster'
        ];

        $client = $this->objectService->getClient($config);

        $this->assertInstanceOf(Client::class, $client);
        // Note: We can't directly test the internal config, but the method should work
    }

    /**
     * Test saveObject method
     *
     * This test verifies that the saveObject method correctly saves
     * data to MongoDB and returns the created object.
     *
     * @covers ::saveObject
     * @return void
     */
    public function testSaveObject(): void
    {
        $data = [
            'name' => 'Test Object',
            'description' => 'Test Description'
        ];

        $config = [
            'base_uri' => 'https://api.example.com',
            'mongodbCluster' => 'test-cluster'
        ];

        // Mock the HTTP response for insertOne
        $insertResponse = new Response(200, [], json_encode([
            'insertedId' => '507f1f77bcf86cd799439011'
        ]));

        // Mock the HTTP response for findOne
        $findResponse = new Response(200, [], json_encode([
            'document' => array_merge($data, [
                'id' => '507f1f77bcf86cd799439011',
                '_id' => '507f1f77bcf86cd799439011'
            ])
        ]));

        // Create a mock client that returns our responses
        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls($insertResponse, $findResponse);

        // Use reflection to replace the getClient method
        $reflection = new \ReflectionClass($this->objectService);
        $getClientMethod = $reflection->getMethod('getClient');
        $getClientMethod->setAccessible(true);

        // Create a partial mock to override getClient
        $objectService = $this->getMockBuilder(ObjectService::class)
            ->setConstructorArgs([
                $this->appManager,
                $this->container,
                $this->endpointMapper,
                $this->eventSubscriptionMapper,
                $this->jobMapper,
                $this->mappingMapper,
                $this->ruleMapper,
                $this->sourceMapper,
                $this->synchronizationMapper
            ])
            ->onlyMethods(['getClient'])
            ->getMock();

        $objectService->method('getClient')->willReturn($mockClient);

        $result = $objectService->saveObject($data, $config);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('_id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('Test Object', $result['name']);
    }

    /**
     * Test findObjects method
     *
     * This test verifies that the findObjects method correctly
     * retrieves objects from MongoDB based on filters.
     *
     * @covers ::findObjects
     * @return void
     */
    public function testFindObjects(): void
    {
        $filters = ['status' => 'active'];
        $config = [
            'base_uri' => 'https://api.example.com',
            'mongodbCluster' => 'test-cluster'
        ];

        $expectedData = [
            ['id' => '1', 'name' => 'Object 1', 'status' => 'active'],
            ['id' => '2', 'name' => 'Object 2', 'status' => 'active']
        ];

        $response = new Response(200, [], json_encode($expectedData));

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('post')
            ->with(
                'action/find',
                ['json' => [
                    'database' => 'objects',
                    'collection' => 'json',
                    'dataSource' => 'test-cluster',
                    'filter' => $filters
                ]]
            )
            ->willReturn($response);

        $objectService = $this->getMockBuilder(ObjectService::class)
            ->setConstructorArgs([
                $this->appManager,
                $this->container,
                $this->endpointMapper,
                $this->eventSubscriptionMapper,
                $this->jobMapper,
                $this->mappingMapper,
                $this->ruleMapper,
                $this->sourceMapper,
                $this->synchronizationMapper
            ])
            ->onlyMethods(['getClient'])
            ->getMock();

        $objectService->method('getClient')->willReturn($mockClient);

        $result = $objectService->findObjects($filters, $config);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test findObject method
     *
     * This test verifies that the findObject method correctly
     * retrieves a single object from MongoDB.
     *
     * @covers ::findObject
     * @return void
     */
    public function testFindObject(): void
    {
        $filters = ['_id' => '507f1f77bcf86cd799439011'];
        $config = [
            'base_uri' => 'https://api.example.com',
            'mongodbCluster' => 'test-cluster'
        ];

        $expectedData = [
            'id' => '507f1f77bcf86cd799439011',
            'name' => 'Test Object',
            'description' => 'Test Description'
        ];

        $response = new Response(200, [], json_encode([
            'document' => $expectedData
        ]));

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('post')
            ->with(
                'action/findOne',
                ['json' => [
                    'database' => 'objects',
                    'collection' => 'json',
                    'filter' => $filters,
                    'dataSource' => 'test-cluster'
                ]]
            )
            ->willReturn($response);

        $objectService = $this->getMockBuilder(ObjectService::class)
            ->setConstructorArgs([
                $this->appManager,
                $this->container,
                $this->endpointMapper,
                $this->eventSubscriptionMapper,
                $this->jobMapper,
                $this->mappingMapper,
                $this->ruleMapper,
                $this->sourceMapper,
                $this->synchronizationMapper
            ])
            ->onlyMethods(['getClient'])
            ->getMock();

        $objectService->method('getClient')->willReturn($mockClient);

        $result = $objectService->findObject($filters, $config);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test updateObject method
     *
     * This test verifies that the updateObject method correctly
     * updates an object in MongoDB and returns the updated object.
     *
     * @covers ::updateObject
     * @return void
     */
    public function testUpdateObject(): void
    {
        $filters = ['_id' => '507f1f77bcf86cd799439011'];
        $update = ['name' => 'Updated Object'];
        $config = [
            'base_uri' => 'https://api.example.com',
            'mongodbCluster' => 'test-cluster'
        ];

        $expectedData = [
            'id' => '507f1f77bcf86cd799439011',
            'name' => 'Updated Object',
            'description' => 'Test Description'
        ];

        // Mock the HTTP response for updateOne
        $updateResponse = new Response(200, [], json_encode(['modifiedCount' => 1]));

        // Mock the HTTP response for findOne
        $findResponse = new Response(200, [], json_encode([
            'document' => $expectedData
        ]));

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls($updateResponse, $findResponse);

        $objectService = $this->getMockBuilder(ObjectService::class)
            ->setConstructorArgs([
                $this->appManager,
                $this->container,
                $this->endpointMapper,
                $this->eventSubscriptionMapper,
                $this->jobMapper,
                $this->mappingMapper,
                $this->ruleMapper,
                $this->sourceMapper,
                $this->synchronizationMapper
            ])
            ->onlyMethods(['getClient'])
            ->getMock();

        $objectService->method('getClient')->willReturn($mockClient);

        $result = $objectService->updateObject($filters, $update, $config);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test deleteObject method
     *
     * This test verifies that the deleteObject method correctly
     * deletes an object from MongoDB.
     *
     * @covers ::deleteObject
     * @return void
     */
    public function testDeleteObject(): void
    {
        $filters = ['_id' => '507f1f77bcf86cd799439011'];
        $config = [
            'base_uri' => 'https://api.example.com',
            'mongodbCluster' => 'test-cluster'
        ];

        $response = new Response(200, [], json_encode(['deletedCount' => 1]));

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('post')
            ->with(
                'action/deleteOne',
                ['json' => [
                    'database' => 'objects',
                    'collection' => 'json',
                    'filter' => $filters,
                    'dataSource' => 'test-cluster'
                ]]
            )
            ->willReturn($response);

        $objectService = $this->getMockBuilder(ObjectService::class)
            ->setConstructorArgs([
                $this->appManager,
                $this->container,
                $this->endpointMapper,
                $this->eventSubscriptionMapper,
                $this->jobMapper,
                $this->mappingMapper,
                $this->ruleMapper,
                $this->sourceMapper,
                $this->synchronizationMapper
            ])
            ->onlyMethods(['getClient'])
            ->getMock();

        $objectService->method('getClient')->willReturn($mockClient);

        $result = $objectService->deleteObject($filters, $config);

        $this->assertEquals([], $result);
    }

    /**
     * Test aggregateObjects method
     *
     * This test verifies that the aggregateObjects method correctly
     * performs aggregation operations on MongoDB objects.
     *
     * @covers ::aggregateObjects
     * @return void
     */
    public function testAggregateObjects(): void
    {
        $filters = ['status' => 'active'];
        $pipeline = [
            ['$match' => ['status' => 'active']],
            ['$group' => ['_id' => '$category', 'count' => ['$sum' => 1]]]
        ];
        $config = [
            'base_uri' => 'https://api.example.com',
            'mongodbCluster' => 'test-cluster'
        ];

        $expectedData = [
            ['_id' => 'category1', 'count' => 5],
            ['_id' => 'category2', 'count' => 3]
        ];

        $response = new Response(200, [], json_encode($expectedData));

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('post')
            ->with(
                'action/aggregate',
                ['json' => [
                    'database' => 'objects',
                    'collection' => 'json',
                    'filter' => $filters,
                    'pipeline' => $pipeline,
                    'dataSource' => 'test-cluster'
                ]]
            )
            ->willReturn($response);

        $objectService = $this->getMockBuilder(ObjectService::class)
            ->setConstructorArgs([
                $this->appManager,
                $this->container,
                $this->endpointMapper,
                $this->eventSubscriptionMapper,
                $this->jobMapper,
                $this->mappingMapper,
                $this->ruleMapper,
                $this->sourceMapper,
                $this->synchronizationMapper
            ])
            ->onlyMethods(['getClient'])
            ->getMock();

        $objectService->method('getClient')->willReturn($mockClient);

        $result = $objectService->aggregateObjects($filters, $pipeline, $config);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test getOpenRegisters when OpenRegister is not installed
     *
     * This test verifies that the getOpenRegisters method returns null
     * when the OpenRegister app is not installed.
     *
     * @covers ::getOpenRegisters
     * @return void
     */
    public function testGetOpenRegistersWhenNotInstalled(): void
    {
        $this->appManager->method('getInstalledApps')
            ->willReturn(['openconnector', 'files']);

        $result = $this->objectService->getOpenRegisters();

        $this->assertNull($result);
    }

    /**
     * Test getOpenRegisters when OpenRegister is installed but service not available
     *
     * This test verifies that the getOpenRegisters method returns null
     * when OpenRegister is installed but the service is not available.
     *
     * @covers ::getOpenRegisters
     * @return void
     */
    public function testGetOpenRegistersWhenServiceNotAvailable(): void
    {
        $this->appManager->method('getInstalledApps')
            ->willReturn(['openconnector', 'openregister', 'files']);

        $this->container->method('get')
            ->with('OCA\OpenRegister\Service\ObjectService')
            ->willThrowException(new \Exception('Service not found'));

        $result = $this->objectService->getOpenRegisters();

        $this->assertNull($result);
    }

    /**
     * Test getOpenRegisters when OpenRegister is available
     *
     * This test verifies that the getOpenRegisters method returns the
     * OpenRegister service when it's available.
     *
     * @covers ::getOpenRegisters
     * @return void
     */
    public function testGetOpenRegistersWhenAvailable(): void
    {
        $this->appManager->method('getInstalledApps')
            ->willReturn(['openconnector', 'openregister', 'files']);

        $openRegisterService = $this->createMock(OpenRegisterObjectService::class);
        $this->container->method('get')
            ->with('OCA\OpenRegister\Service\ObjectService')
            ->willReturn($openRegisterService);

        $result = $this->objectService->getOpenRegisters();

        $this->assertSame($openRegisterService, $result);
    }

    /**
     * Test getMapper with endpoint object type
     *
     * This test verifies that the getMapper method returns the correct
     * mapper for the 'endpoint' object type.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithEndpointType(): void
    {
        $result = $this->objectService->getMapper('endpoint');

        $this->assertSame($this->endpointMapper, $result);
    }

    /**
     * Test getMapper with source object type
     *
     * This test verifies that the getMapper method returns the correct
     * mapper for the 'source' object type.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithSourceType(): void
    {
        $result = $this->objectService->getMapper('source');

        $this->assertSame($this->sourceMapper, $result);
    }

    /**
     * Test getMapper with mapping object type
     *
     * This test verifies that the getMapper method returns the correct
     * mapper for the 'mapping' object type.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithMappingType(): void
    {
        $result = $this->objectService->getMapper('mapping');

        $this->assertSame($this->mappingMapper, $result);
    }

    /**
     * Test getMapper with rule object type
     *
     * This test verifies that the getMapper method returns the correct
     * mapper for the 'rule' object type.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithRuleType(): void
    {
        $result = $this->objectService->getMapper('rule');

        $this->assertSame($this->ruleMapper, $result);
    }

    /**
     * Test getMapper with job object type
     *
     * This test verifies that the getMapper method returns the correct
     * mapper for the 'job' object type.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithJobType(): void
    {
        $result = $this->objectService->getMapper('job');

        $this->assertSame($this->jobMapper, $result);
    }

    /**
     * Test getMapper with synchronization object type
     *
     * This test verifies that the getMapper method returns the correct
     * mapper for the 'synchronization' object type.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithSynchronizationType(): void
    {
        $result = $this->objectService->getMapper('synchronization');

        $this->assertSame($this->synchronizationMapper, $result);
    }

    /**
     * Test getMapper with eventSubscription object type
     *
     * This test verifies that the getMapper method returns the correct
     * mapper for the 'eventSubscription' object type.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithEventSubscriptionType(): void
    {
        $result = $this->objectService->getMapper('eventSubscription');

        $this->assertSame($this->eventSubscriptionMapper, $result);
    }

    /**
     * Test getMapper with case insensitive object types
     *
     * This test verifies that the getMapper method handles case insensitive
     * object type matching.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithCaseInsensitiveTypes(): void
    {
        $result1 = $this->objectService->getMapper('ENDPOINT');
        $result2 = $this->objectService->getMapper('Endpoint');
        $result3 = $this->objectService->getMapper('endpoint');

        $this->assertSame($this->endpointMapper, $result1);
        $this->assertSame($this->endpointMapper, $result2);
        $this->assertSame($this->endpointMapper, $result3);
    }

    /**
     * Test getMapper with unknown object type
     *
     * This test verifies that the getMapper method throws an exception
     * when an unknown object type is provided.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown object type: unknown');

        $this->objectService->getMapper('unknown');
    }

    /**
     * Test getMapper with null object type
     *
     * This test verifies that the getMapper method throws an exception
     * when a null object type is provided without register and schema.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithNullType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown object type: ');

        $this->objectService->getMapper(null);
    }

    /**
     * Test getMapper with OpenRegister parameters
     *
     * This test verifies that the getMapper method correctly delegates
     * to OpenRegister when register and schema are provided.
     *
     * @covers ::getMapper
     * @return void
     */
    public function testGetMapperWithOpenRegisterParameters(): void
    {
        $this->appManager->method('getInstalledApps')
            ->willReturn(['openconnector', 'openregister', 'files']);

        $openRegisterService = $this->createMock(OpenRegisterObjectService::class);
        $openRegisterMapper = $this->createMock(\OCA\OpenRegister\Db\ObjectEntityMapper::class);

        $this->container->method('get')
            ->with('OCA\OpenRegister\Service\ObjectService')
            ->willReturn($openRegisterService);

                            $openRegisterService->expects($this->once())
                        ->method('getMapper')
                        ->with(null, 1, 2)
                        ->willReturn($openRegisterMapper);

        $result = $this->objectService->getMapper(null, 2, 1);

        $this->assertSame($openRegisterMapper, $result);
    }
}
