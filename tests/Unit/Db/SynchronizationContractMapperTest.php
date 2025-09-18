<?php

declare(strict_types=1);

/**
 * SynchronizationContractMapperTest
 *
 * Unit tests for the SynchronizationContractMapper class to verify database operations,
 * CRUD functionality, and synchronization contract retrieval methods.
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

use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * SynchronizationContractMapper Test Suite
 *
 * Unit tests for synchronization contract database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class SynchronizationContractMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SynchronizationContractMapper */
    private SynchronizationContractMapper $synchronizationContractMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->synchronizationContractMapper = new SynchronizationContractMapper($this->db);
    }

    /**
     * Test SynchronizationContractMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationContractMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationContractMapper::class, $this->synchronizationContractMapper);
    }

    /**
     * Test find method with valid ID.
     *
     * @return void
     */
    public function testFindWithValidId(): void
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
            ->with('openconnector_synchronization_contracts')
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

        $this->synchronizationContractMapper->find($id);
    }

    /**
     * Test findSyncContractByOriginId method.
     *
     * @return void
     */
    public function testFindSyncContractByOriginId(): void
    {
        $synchronizationId = 'sync-123';
        $originId = 'origin-456';
        
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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationContractMapper->findSyncContractByOriginId($synchronizationId, $originId);
    }

    /**
     * Test findTargetIdByOriginId method.
     *
     * @return void
     */
    public function testFindTargetIdByOriginId(): void
    {
        $originId = 'origin-456';
        
        $qb = $this->createMock(IQueryBuilder::class);
        $result = $this->createMock(Result::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('target_id')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($originId)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $qb->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('target-789');

        $targetId = $this->synchronizationContractMapper->findTargetIdByOriginId($originId);
        $this->assertEquals('target-789', $targetId);
    }

    /**
     * Test findOnTarget method.
     *
     * @return void
     */
    public function testFindOnTarget(): void
    {
        $synchronization = 'sync-123';
        $targetId = 'target-456';
        
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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationContractMapper->findOnTarget($synchronization, $targetId);
    }

    /**
     * Test findByOriginAndTarget method.
     *
     * @return void
     */
    public function testFindByOriginAndTarget(): void
    {
        $originId = 'origin-123';
        $targetId = 'target-456';
        
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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationContractMapper->findByOriginAndTarget($originId, $targetId);
    }

    /**
     * Test findAllBySynchronizationAndSchema method.
     *
     * @return void
     */
    public function testFindAllBySynchronizationAndSchema(): void
    {
        $synchronizationId = 'sync-123';
        $schemaId = 'schema-456';
        
        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('c.*')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_synchronization_contracts', 'c')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('innerJoin')
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

        $this->synchronizationContractMapper->findAllBySynchronizationAndSchema($synchronizationId, $schemaId);
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
        $filters = ['status' => 'active'];
        $searchConditions = ['name LIKE :search'];
        $searchParams = ['search' => '%test%'];

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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->synchronizationContractMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'synchronizationId' => 'sync-123',
            'originId' => 'origin-456',
            'targetId' => 'target-789',
            'status' => 'active'
        ];

        $this->synchronizationContractMapper->createFromArray($object);
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
            'status' => 'inactive',
            'lastSync' => new \DateTime()
        ];

        $this->synchronizationContractMapper->updateFromArray($id, $object);
    }

    /**
     * Test findByOriginId method.
     *
     * @return void
     */
    public function testFindByOriginId(): void
    {
        $originId = 'origin-123';
        
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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($originId)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationContractMapper->findByOriginId($originId);
    }

    /**
     * Test findByTargetId method.
     *
     * @return void
     */
    public function testFindByTargetId(): void
    {
        $targetId = 'target-123';
        
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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($targetId)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationContractMapper->findByTargetId($targetId);
    }

    /**
     * Test findByTypeAndId method.
     *
     * @return void
     */
    public function testFindByTypeAndId(): void
    {
        $type = 'user';
        $id = 'user-123';
        
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
            ->with('openconnector_synchronization_contracts')
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

        $this->synchronizationContractMapper->findByTypeAndId($type, $id);
    }

    /**
     * Test getTotalCallCount method.
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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '7']);

        $count = $this->synchronizationContractMapper->getTotalCallCount();
        $this->assertEquals(7, $count);
    }

    /**
     * Test getTotalCount method with filters.
     *
     * @return void
     */
    public function testGetTotalCountWithFilters(): void
    {
        $filters = ['status' => 'active'];
        
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
            ->with('openconnector_synchronization_contracts')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '5']);

        $result->expects($this->once())
            ->method('closeCursor');

        $count = $this->synchronizationContractMapper->getTotalCount($filters);
        $this->assertEquals(5, $count);
    }

    /**
     * Test handleObjectRemoval method.
     *
     * @return void
     */
    public function testHandleObjectRemoval(): void
    {
        $objectIdentifier = 'object-123';
        
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
            ->with('openconnector_synchronization_contracts')
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

        $this->synchronizationContractMapper->handleObjectRemoval($objectIdentifier);
    }

    /**
     * Test SynchronizationContractMapper has expected table name.
     *
     * @return void
     */
    public function testSynchronizationContractMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationContractMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronization_contracts', $property->getValue($this->synchronizationContractMapper));
    }

    /**
     * Test SynchronizationContractMapper has expected entity class.
     *
     * @return void
     */
    public function testSynchronizationContractMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationContractMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(SynchronizationContract::class, $property->getValue($this->synchronizationContractMapper));
    }

    /**
     * Test SynchronizationContractMapper has expected methods.
     *
     * @return void
     */
    public function testSynchronizationContractMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'find'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findSyncContractByOriginId'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findTargetIdByOriginId'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findOnTarget'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findByOriginAndTarget'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findAllBySynchronizationAndSchema'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findAll'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findByOriginId'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findByTargetId'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'findByTypeAndId'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'getTotalCallCount'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->synchronizationContractMapper, 'handleObjectRemoval'));
    }
}
