<?php

declare(strict_types=1);

/**
 * SourceMapperTest
 *
 * Unit tests for the SourceMapper class to verify database operations,
 * CRUD functionality, and source retrieval methods.
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

use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * SourceMapper Test Suite
 *
 * Unit tests for source database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class SourceMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SourceMapper */
    private SourceMapper $sourceMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->sourceMapper = new SourceMapper($this->db);
    }

    /**
     * Test SourceMapper can be instantiated.
     *
     * @return void
     */
    public function testSourceMapperInstantiation(): void
    {
        $this->assertInstanceOf(SourceMapper::class, $this->sourceMapper);
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
            ->with('openconnector_sources')
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

        $this->sourceMapper->find($id);
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
            ->with('openconnector_sources')
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

        $this->sourceMapper->find($id);
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
            ->with('openconnector_sources')
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

        $this->sourceMapper->findByRef($reference);
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
            ->with('openconnector_sources')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->sourceMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams, $ids);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Source',
            'type' => 'api',
            'location' => 'https://api.example.com',
            'enabled' => true
        ];

        $this->sourceMapper->createFromArray($object);
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
            'name' => 'Updated Source',
            'enabled' => false
        ];

        $this->sourceMapper->updateFromArray($id, $object);
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
            ->with('openconnector_sources')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '6']);

        $count = $this->sourceMapper->getTotalCount($filters);
        $this->assertEquals(6, $count);
    }

    /**
     * Test findOrCreateByLocation method.
     *
     * @return void
     */
    public function testFindOrCreateByLocation(): void
    {
        $location = 'https://api.example.com';
        $defaultData = ['name' => 'API Source', 'type' => 'api'];
        
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
            ->with('openconnector_sources')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($location)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->sourceMapper->findOrCreateByLocation($location, $defaultData);
    }

    /**
     * Test findByConfiguration method.
     *
     * @return void
     */
    public function testFindByConfiguration(): void
    {
        $configurationId = 'test-config';
        
        $this->sourceMapper->findByConfiguration($configurationId);
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
                ['id' => '1', 'slug' => 'test-source'],
                false
            );

        $mappings = $this->sourceMapper->getIdToSlugMap();
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
                ['id' => '1', 'slug' => 'test-source'],
                false
            );

        $mappings = $this->sourceMapper->getSlugToIdMap();
        $this->assertIsArray($mappings);
    }

    /**
     * Test SourceMapper has expected table name.
     *
     * @return void
     */
    public function testSourceMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->sourceMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_sources', $property->getValue($this->sourceMapper));
    }

    /**
     * Test SourceMapper has expected entity class.
     *
     * @return void
     */
    public function testSourceMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->sourceMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Source::class, $property->getValue($this->sourceMapper));
    }

    /**
     * Test SourceMapper has expected methods.
     *
     * @return void
     */
    public function testSourceMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->sourceMapper, 'find'));
        $this->assertTrue(method_exists($this->sourceMapper, 'findByRef'));
        $this->assertTrue(method_exists($this->sourceMapper, 'findAll'));
        $this->assertTrue(method_exists($this->sourceMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->sourceMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->sourceMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->sourceMapper, 'findOrCreateByLocation'));
        $this->assertTrue(method_exists($this->sourceMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->sourceMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->sourceMapper, 'getSlugToIdMap'));
    }
}
