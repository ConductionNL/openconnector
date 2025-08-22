<?php

namespace OCA\OpenConnector\Tests\Unit\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JWadhams\JsonLogic;
use OCA\OpenConnector\Db\CallLog;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationLog;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\StorageService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Files\GenericFileException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Lock\LockedException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Uid\Uuid;
use Adbar\Dot;
use OCP\Files\File;
use DateTime;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\EventLoop\Loop;
use React\Promise\Timer;
use React\Async;
use React\Promise\Deferred;
use function React\Promise\resolve;

/**
 * SynchronizationServiceTest
 *
 * Unit tests for the SynchronizationService class.
 * Tests synchronization operations between internal and external data sources.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Service
 * @author   Conduction <info@conduction.nl>
 * @copyright 2024 Conduction b.v.
 * @license  AGPL-3.0-or-later
 * @version  1.0.0
 * @link     https://github.com/ConductionNL/OpenConnector
 */
class SynchronizationServiceTest extends TestCase
{
    private SynchronizationService $synchronizationService;
    private CallService $callService;
    private MappingService $mappingService;
    private ContainerInterface $containerInterface;
    private SynchronizationMapper $synchronizationMapper;
    private SourceMapper $sourceMapper;
    private MappingMapper $mappingMapper;
    private SynchronizationContractMapper $synchronizationContractMapper;
    private SynchronizationContractLogMapper $synchronizationContractLogMapper;
    private SynchronizationLogMapper $synchronizationLogMapper;
    private ObjectService $objectService;
    private StorageService $storageService;
    private RuleMapper $ruleMapper;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->callService = $this->getMockBuilder(CallService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mappingService = $this->getMockBuilder(MappingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->containerInterface = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->synchronizationMapper = $this->getMockBuilder(SynchronizationMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceMapper = $this->getMockBuilder(SourceMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mappingMapper = $this->getMockBuilder(MappingMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->synchronizationContractMapper = $this->getMockBuilder(SynchronizationContractMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->synchronizationContractLogMapper = $this->getMockBuilder(SynchronizationContractLogMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->synchronizationLogMapper = $this->getMockBuilder(SynchronizationLogMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectService = $this->getMockBuilder(ObjectService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storageService = $this->getMockBuilder(StorageService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleMapper = $this->getMockBuilder(RuleMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create the SynchronizationService instance with mocked dependencies
        $this->synchronizationService = new SynchronizationService(
            $this->callService,
            $this->mappingService,
            $this->containerInterface,
            $this->sourceMapper,
            $this->mappingMapper,
            $this->synchronizationMapper,
            $this->synchronizationLogMapper,
            $this->synchronizationContractMapper,
            $this->synchronizationContractLogMapper,
            $this->objectService,
            $this->storageService,
            $this->ruleMapper
        );
    }

    /**
     * Test findAllBySourceId method
     *
     * This test verifies that the findAllBySourceId method correctly
     * finds synchronizations by source ID.
     *
     * @covers ::findAllBySourceId
     * @return void
     */
    public function testFindAllBySourceId(): void
    {
        $register = 'test-register';
        $schema = 'test-schema';
        $sourceId = "$register/$schema";
        $expectedSynchronizations = [
            new Synchronization(),
            new Synchronization()
        ];

        $this->synchronizationMapper
            ->expects($this->once())
            ->method('findAll')
            ->with(null, null, ['source_id' => $sourceId])
            ->willReturn($expectedSynchronizations);

        $result = $this->synchronizationService->findAllBySourceId($register, $schema);

        $this->assertEquals($expectedSynchronizations, $result);
    }

    /**
     * Test sortNestedArray method
     *
     * This test verifies that the sortNestedArray method correctly
     * sorts nested arrays.
     *
     * @covers ::sortNestedArray
     * @return void
     */
    public function testSortNestedArray(): void
    {
        $array = [
            'b' => 'value2',
            'a' => 'value1',
            'c' => [
                'z' => 'nested2',
                'y' => 'nested1'
            ]
        ];

        $result = $this->synchronizationService->sortNestedArray($array);

        $this->assertTrue($result);
        $this->assertEquals(['a', 'b', 'c'], array_keys($array));
        $this->assertEquals(['y', 'z'], array_keys($array['c']));
    }

    /**
     * Test sortNestedArray method with non-array input
     *
     * This test verifies that the sortNestedArray method handles
     * non-array input correctly.
     *
     * @covers ::sortNestedArray
     * @return void
     */
    public function testSortNestedArrayWithNonArrayInput(): void
    {
        $nonArray = 'not an array';

        $result = $this->synchronizationService->sortNestedArray($nonArray);

        $this->assertFalse($result);
    }

    /**
     * Test getNextlinkFromCall method
     *
     * This test verifies that the getNextlinkFromCall method correctly
     * extracts next link from API response.
     *
     * @covers ::getNextlinkFromCall
     * @return void
     */
    public function testGetNextlinkFromCall(): void
    {
        $body = [
            'next' => 'https://api.example.com/objects?page=2'
        ];

        $result = $this->synchronizationService->getNextlinkFromCall($body);

        $this->assertEquals('https://api.example.com/objects?page=2', $result);
    }

    /**
     * Test getNextlinkFromCall method with no next link
     *
     * This test verifies that the getNextlinkFromCall method returns null
     * when no next link is present.
     *
     * @covers ::getNextlinkFromCall
     * @return void
     */
    public function testGetNextlinkFromCallWithNoNextLink(): void
    {
        $body = [
            '_links' => [
                'self' => [
                    'href' => 'https://api.example.com/objects'
                ]
            ]
        ];

        $result = $this->synchronizationService->getNextlinkFromCall($body);

        $this->assertNull($result);
    }

    /**
     * Test encodeArrayKeys method
     *
     * This test verifies that the encodeArrayKeys method correctly
     * encodes array keys.
     *
     * @covers ::encodeArrayKeys
     * @return void
     */
    public function testEncodeArrayKeys(): void
    {
        $array = [
            'test_key' => 'value1',
            'another_key' => [
                'nested_key' => 'value2'
            ]
        ];

        $toReplace = '_';
        $replacement = '-';

        $result = $this->synchronizationService->encodeArrayKeys($array, $toReplace, $replacement);

        $expected = [
            'test-key' => 'value1',
            'another-key' => [
                'nested-key' => 'value2'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getSynchronization method with ID
     *
     * This test verifies that the getSynchronization method correctly
     * retrieves a synchronization by ID.
     *
     * @covers ::getSynchronization
     * @return void
     */
    public function testGetSynchronizationWithId(): void
    {
        $id = 1;
        $expectedSynchronization = new Synchronization();
        $expectedSynchronization->setId($id);

        $this->synchronizationMapper
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedSynchronization);

        $result = $this->synchronizationService->getSynchronization($id);

        $this->assertEquals($expectedSynchronization, $result);
    }

    /**
     * Test getSynchronization method with filters
     *
     * This test verifies that the getSynchronization method correctly
     * retrieves synchronizations with filters.
     *
     * @covers ::getSynchronization
     * @return void
     */
    public function testGetSynchronizationWithFilters(): void
    {
        $filters = ['source_id' => 'test/schema'];
        $expectedSynchronizations = [new Synchronization()];

        $this->synchronizationMapper
            ->expects($this->once())
            ->method('findAll')
            ->with(null, null, $filters)
            ->willReturn($expectedSynchronizations);

        $result = $this->synchronizationService->getSynchronization(null, $filters);

        $this->assertEquals($expectedSynchronizations[0], $result);
    }

    /**
     * Test getSynchronization method with no results
     *
     * This test verifies that the getSynchronization method throws an exception
     * when no synchronization is found.
     *
     * @covers ::getSynchronization
     * @return void
     */
    public function testGetSynchronizationWithNoResults(): void
    {
        $filters = ['source_id' => 'nonexistent/schema'];

        $this->synchronizationMapper
            ->expects($this->once())
            ->method('findAll')
            ->with(null, null, $filters)
            ->willReturn([]);

        $this->expectException(DoesNotExistException::class);

        $this->synchronizationService->getSynchronization(null, $filters);
    }

    /**
     * Test getAllObjectsFromArray method
     *
     * This test verifies that the getAllObjectsFromArray method correctly
     * processes objects from an array.
     *
     * @covers ::getAllObjectsFromArray
     * @return void
     */
    public function testGetAllObjectsFromArray(): void
    {
        $array = [
            'objects' => [
                ['id' => '123', 'name' => 'Object 1'],
                ['id' => '456', 'name' => 'Object 2']
            ]
        ];

        $synchronization = new Synchronization();
        $synchronization->setSourceConfig([
            'resultsPosition' => 'objects'
        ]);

        $result = $this->synchronizationService->getAllObjectsFromArray($array, $synchronization);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Object 1', $result[0]['name']);
        $this->assertEquals('Object 2', $result[1]['name']);
    }

    /**
     * Test getAllObjectsFromArray method with different object location
     *
     * This test verifies that the getAllObjectsFromArray method correctly
     * handles different object locations in the array.
     *
     * @covers ::getAllObjectsFromArray
     * @return void
     */
    public function testGetAllObjectsFromArrayWithDifferentLocation(): void
    {
        $array = [
            'data' => [
                'items' => [
                    ['id' => '123', 'name' => 'Item 1'],
                    ['id' => '456', 'name' => 'Item 2']
                ]
            ]
        ];

        $synchronization = new Synchronization();
        $synchronization->setSourceConfig([
            'resultsPosition' => 'data.items'
        ]);

        $result = $this->synchronizationService->getAllObjectsFromArray($array, $synchronization);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Item 1', $result[0]['name']);
        $this->assertEquals('Item 2', $result[1]['name']);
    }

    /**
     * Test getAllObjectsFromArray method with empty array
     *
     * This test verifies that the getAllObjectsFromArray method correctly
     * handles empty arrays.
     *
     * @covers ::getAllObjectsFromArray
     * @return void
     */
    public function testGetAllObjectsFromArrayWithEmptyArray(): void
    {
        $array = [
            'objects' => []
        ];

        $synchronization = new Synchronization();
        $synchronization->setSourceConfig([
            'resultsPosition' => 'objects'
        ]);

        $result = $this->synchronizationService->getAllObjectsFromArray($array, $synchronization);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Test encodeArrayKeys method with empty arrays
     *
     * This test verifies that the encodeArrayKeys method correctly
     * handles empty arrays and nested empty arrays.
     *
     * @covers ::encodeArrayKeys
     * @return void
     */
    public function testEncodeArrayKeysWithEmptyArrays(): void
    {
        $input = [
            'empty' => [],
            'nested' => [
                'deep' => []
            ]
        ];

        $expected = [
            'empty' => [],
            'nested' => [
                'deep' => []
            ]
        ];

        $result = $this->synchronizationService->encodeArrayKeys($input, '.', '&#46;');
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test encodeArrayKeys method with different replacement characters
     *
     * This test verifies that the encodeArrayKeys method works with various
     * replacement characters and handles edge cases.
     *
     * @covers ::encodeArrayKeys
     * @return void
     */
    public function testEncodeArrayKeysWithDifferentReplacements(): void
    {
        $input = [
            'user-name' => 'John Doe',
            'user_email' => 'john@example.com',
            'settings:notifications' => true
        ];

        $expected = [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            'settings:notifications' => true
        ];

        $result = $this->synchronizationService->encodeArrayKeys($input, '-', '_');
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test sortNestedArray method with complex nested structure
     *
     * This test verifies that the sortNestedArray method correctly
     * sorts complex nested array structures.
     *
     * @covers ::sortNestedArray
     * @return void
     */
    public function testSortNestedArrayWithComplexStructure(): void
    {
        $array = [
            'zebra' => 'value3',
            'alpha' => 'value1',
            'beta' => [
                'gamma' => 'nested3',
                'alpha' => 'nested1',
                'beta' => 'nested2'
            ],
            'charlie' => [
                'delta' => 'deep3',
                'alpha' => 'deep1',
                'beta' => 'deep2'
            ]
        ];

        $result = $this->synchronizationService->sortNestedArray($array);

        $this->assertTrue($result);
        $this->assertEquals(['alpha', 'beta', 'charlie', 'zebra'], array_keys($array));
        $this->assertEquals(['alpha', 'beta', 'gamma'], array_keys($array['beta']));
        $this->assertEquals(['alpha', 'beta', 'delta'], array_keys($array['charlie']));
    }

    /**
     * Test sortNestedArray method with mixed data types
     *
     * This test verifies that the sortNestedArray method correctly
     * handles mixed data types in arrays.
     *
     * @covers ::sortNestedArray
     * @return void
     */
    public function testSortNestedArrayWithMixedDataTypes(): void
    {
        $array = [
            'string' => 'value',
            'number' => 42,
            'boolean' => true,
            'null' => null,
            'array' => ['b', 'a', 'c']
        ];

        $result = $this->synchronizationService->sortNestedArray($array);

        $this->assertTrue($result);
        $this->assertEquals(['array', 'boolean', 'null', 'number', 'string'], array_keys($array));
        // The nested numeric array should remain unchanged as sortNestedArray only sorts associative arrays
        $this->assertEquals(['b', 'a', 'c'], $array['array']);
    }
}
