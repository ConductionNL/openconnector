<?php

declare(strict_types=1);

/**
 * MappingMapperTest
 *
 * Unit tests for the MappingMapper class to verify database operations,
 * CRUD functionality, and mapping retrieval methods.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Db
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * MappingMapper Test Suite
 *
 * Unit tests for mapping database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class MappingMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var MappingMapper */
    private MappingMapper $mappingMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->mappingMapper = new MappingMapper($this->db);
    }

    /**
     * Test MappingMapper can be instantiated.
     *
     * @return void
     */
    public function testMappingMapperInstantiation(): void
    {
        $this->assertInstanceOf(MappingMapper::class, $this->mappingMapper);
    }

    /**
     * Test find method with numeric ID.
     *
     * @return void
     */
    public function testFindWithNumericId(): void
    {
        $id = 1;
        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('*')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_mappings')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($id, IQueryBuilder::PARAM_INT)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->mappingMapper->find($id);
    }

    /**
     * Test find method with string ID (UUID/slug).
     *
     * @return void
     */
    public function testFindWithStringId(): void
    {
        $id = 'test-uuid';
        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('*')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_mappings')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(3))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->mappingMapper->find($id);
    }

    /**
     * Test findByRef method.
     *
     * @return void
     */
    public function testFindByRef(): void
    {
        $reference = 'test-ref';
        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('*')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_mappings')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($reference)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->mappingMapper->findByRef($reference);
    }

    /**
     * Test findAll method with parameters.
     *
     * @return void
     */
    public function testFindAllWithParameters(): void
    {
        $limit = 10;
        $offset = 0;
        $filters = ['enabled' => true];
        $searchConditions = ['name LIKE :search'];
        $searchParams = ['search' => '%test%'];
        $ids = ['id' => [1, 2, 3]];

        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('*')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_mappings')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->mappingMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams, $ids);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Mapping',
            'sourceType' => 'api',
            'targetType' => 'database',
            'enabled' => true
        ];

        $this->mappingMapper->createFromArray($object);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $object = [
            'name' => 'Updated Mapping',
            'enabled' => false
        ];

        $this->mappingMapper->updateFromArray($id, $object);
    }

    /**
     * Test getTotalCount method.
     *
     * @return void
     */
    public function testGetTotalCount(): void
    {
        $filters = ['enabled' => true];
        
        $qb = $this->createMock(IQueryBuilder::class);
        $result = $this->createMock(Result::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_mappings')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '12']);

        $count = $this->mappingMapper->getTotalCount($filters);
        $this->assertEquals(12, $count);
    }

    /**
     * Test findByConfiguration method.
     *
     * @return void
     */
    public function testFindByConfiguration(): void
    {
        $configurationId = 'test-config';
        
        $this->mappingMapper->findByConfiguration($configurationId);
    }

    /**
     * Test getIdToSlugMap method.
     *
     * @return void
     */
    public function testGetIdToSlugMap(): void
    {
        $qb = $this->createMock(IQueryBuilder::class);
        $result = $this->createMock(Result::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('id', 'slug')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['id' => '1', 'slug' => 'test-mapping'],
                false
            );

        $mappings = $this->mappingMapper->getIdToSlugMap();
        $this->assertIsArray($mappings);
    }

    /**
     * Test getSlugToIdMap method.
     *
     * @return void
     */
    public function testGetSlugToIdMap(): void
    {
        $qb = $this->createMock(IQueryBuilder::class);
        $result = $this->createMock(Result::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('id', 'slug')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['id' => '1', 'slug' => 'test-mapping'],
                false
            );

        $mappings = $this->mappingMapper->getSlugToIdMap();
        $this->assertIsArray($mappings);
    }

    /**
     * Test MappingMapper has expected table name.
     *
     * @return void
     */
    public function testMappingMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->mappingMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_mappings', $property->getValue($this->mappingMapper));
    }

    /**
     * Test MappingMapper has expected entity class.
     *
     * @return void
     */
    public function testMappingMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->mappingMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Mapping::class, $property->getValue($this->mappingMapper));
    }

    /**
     * Test MappingMapper has expected methods.
     *
     * @return void
     */
    public function testMappingMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->mappingMapper, 'find'));
        $this->assertTrue(method_exists($this->mappingMapper, 'findByRef'));
        $this->assertTrue(method_exists($this->mappingMapper, 'findAll'));
        $this->assertTrue(method_exists($this->mappingMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->mappingMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->mappingMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->mappingMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->mappingMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->mappingMapper, 'getSlugToIdMap'));
    }
}
