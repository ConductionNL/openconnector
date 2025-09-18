<?php

declare(strict_types=1);

/**
 * SynchronizationMapperTest
 *
 * Unit tests for the SynchronizationMapper class to verify database operations,
 * CRUD functionality, and synchronization retrieval methods.
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

use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * SynchronizationMapper Test Suite
 *
 * Unit tests for synchronization database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class SynchronizationMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SynchronizationMapper */
    private SynchronizationMapper $synchronizationMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->synchronizationMapper = new SynchronizationMapper($this->db);
    }

    /**
     * Test SynchronizationMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationMapper::class, $this->synchronizationMapper);
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
            ->with('openconnector_synchronizations')
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

        $this->synchronizationMapper->find($id);
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
            ->with('openconnector_synchronizations')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationMapper->find($id);
    }

    /**
     * Test findByUuid method.
     *
     * @return void
     */
    public function testFindByUuid(): void
    {
        $uuid = 'test-uuid';
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
            ->with('openconnector_synchronizations')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($uuid)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationMapper->findByUuid($uuid);
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
            ->with('openconnector_synchronizations')
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

        $this->synchronizationMapper->findByRef($reference);
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
            ->with('openconnector_synchronizations')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->synchronizationMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams, $ids);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Synchronization',
            'sourceType' => 'api',
            'targetType' => 'database',
            'enabled' => true
        ];

        $this->synchronizationMapper->createFromArray($object);
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
            'name' => 'Updated Synchronization',
            'enabled' => false
        ];

        $this->synchronizationMapper->updateFromArray($id, $object);
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
            ->with('openconnector_synchronizations')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '4']);

        $result->expects($this->once())
            ->method('closeCursor');

        $count = $this->synchronizationMapper->getTotalCount($filters);
        $this->assertEquals(4, $count);
    }

    /**
     * Test getTotalCallCount method (deprecated).
     *
     * @return void
     */
    public function testGetTotalCallCount(): void
    {
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
            ->with('openconnector_synchronizations')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '4']);

        $result->expects($this->once())
            ->method('closeCursor');

        $count = $this->synchronizationMapper->getTotalCallCount();
        $this->assertEquals(4, $count);
    }

    /**
     * Test findByConfiguration method.
     *
     * @return void
     */
    public function testFindByConfiguration(): void
    {
        $configurationId = 'test-config';
        
        $this->synchronizationMapper->findByConfiguration($configurationId);
    }

    /**
     * Test getByTarget method with registerId and schemaId.
     *
     * @return void
     */
    public function testGetByTargetWithRegisterAndSchema(): void
    {
        $registerId = 'test-register';
        $schemaId = 'test-schema';
        
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
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(4))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationMapper->getByTarget($registerId, $schemaId);
    }

    /**
     * Test getByTarget method with only registerId.
     *
     * @return void
     */
    public function testGetByTargetWithRegisterOnly(): void
    {
        $registerId = 'test-register';
        $schemaId = null;
        
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
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationMapper->getByTarget($registerId, $schemaId);
    }

    /**
     * Test getByTarget method with only schemaId.
     *
     * @return void
     */
    public function testGetByTargetWithSchemaOnly(): void
    {
        $registerId = null;
        $schemaId = 'test-schema';
        
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
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationMapper->getByTarget($registerId, $schemaId);
    }

    /**
     * Test getByTarget method with no parameters (should throw exception).
     *
     * @return void
     */
    public function testGetByTargetWithNoParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either registerId or schemaId must be provided');

        $this->synchronizationMapper->getByTarget(null, null);
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
                ['id' => '1', 'slug' => 'test-sync'],
                false
            );

        $mappings = $this->synchronizationMapper->getIdToSlugMap();
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
                ['id' => '1', 'slug' => 'test-sync'],
                false
            );

        $mappings = $this->synchronizationMapper->getSlugToIdMap();
        $this->assertIsArray($mappings);
    }

    /**
     * Test SynchronizationMapper has expected table name.
     *
     * @return void
     */
    public function testSynchronizationMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronizations', $property->getValue($this->synchronizationMapper));
    }

    /**
     * Test SynchronizationMapper has expected entity class.
     *
     * @return void
     */
    public function testSynchronizationMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Synchronization::class, $property->getValue($this->synchronizationMapper));
    }

    /**
     * Test SynchronizationMapper has expected methods.
     *
     * @return void
     */
    public function testSynchronizationMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->synchronizationMapper, 'find'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'findByUuid'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'findByRef'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'findAll'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'getTotalCallCount'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'getByTarget'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->synchronizationMapper, 'getSlugToIdMap'));
    }
}
