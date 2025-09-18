<?php

declare(strict_types=1);

/**
 * SynchronizationLogMapperTest
 *
 * Unit tests for the SynchronizationLogMapper class to verify database operations,
 * CRUD functionality, and synchronization log retrieval methods.
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

use OCA\OpenConnector\Db\SynchronizationLog;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserSession;
use OCP\ISession;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;
use Doctrine\DBAL\Result;

/**
 * SynchronizationLogMapper Test Suite
 *
 * Unit tests for synchronization log database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class SynchronizationLogMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var IUserSession|MockObject */
    private IUserSession $userSession;

    /** @var ISession|MockObject */
    private ISession $session;

    /** @var SynchronizationLogMapper */
    private SynchronizationLogMapper $synchronizationLogMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->session = $this->createMock(ISession::class);
        
        $this->synchronizationLogMapper = new SynchronizationLogMapper(
            $this->db,
            $this->userSession,
            $this->session
        );
    }

    /**
     * Test SynchronizationLogMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationLogMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationLogMapper::class, $this->synchronizationLogMapper);
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
            ->with('openconnector_synchronization_logs')
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

        $this->synchronizationLogMapper->find($id);
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
        $filters = ['status' => 'success'];
        $searchConditions = ['message LIKE :search'];
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
            ->with('openconnector_synchronization_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('created', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->synchronizationLogMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams);
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
            'status' => 'success',
            'message' => 'Synchronization completed',
            'result' => ['contracts' => ['contract-1', 'contract-2']]
        ];

        $this->synchronizationLogMapper->createFromArray($object);
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
            'status' => 'failed',
            'message' => 'Synchronization failed',
            'result' => ['contracts' => ['contract-3']]
        ];

        $this->synchronizationLogMapper->updateFromArray($id, $object);
    }

    /**
     * Test getTotalCount method with filters.
     *
     * @return void
     */
    public function testGetTotalCountWithFilters(): void
    {
        $filters = ['status' => 'success'];
        
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
            ->with('openconnector_synchronization_logs')
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

        $count = $this->synchronizationLogMapper->getTotalCount($filters);
        $this->assertEquals(12, $count);
    }

    /**
     * Test cleanupExpired method.
     *
     * @return void
     */
    public function testCleanupExpired(): void
    {
        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('delete')
            ->with('openconnector_synchronization_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('executeStatement')
            ->willReturn(3);

        $deletedCount = $this->synchronizationLogMapper->cleanupExpired();
        $this->assertEquals(3, $deletedCount);
    }

    /**
     * Test processContracts method (private method).
     *
     * @return void
     */
    public function testProcessContractsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationLogMapper);
        
        $this->assertTrue($reflection->hasMethod('processContracts'));
        
        $processContractsMethod = $reflection->getMethod('processContracts');
        $this->assertTrue($processContractsMethod->isPrivate());
    }

    /**
     * Test SynchronizationLogMapper has expected table name.
     *
     * @return void
     */
    public function testSynchronizationLogMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationLogMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronization_logs', $property->getValue($this->synchronizationLogMapper));
    }

    /**
     * Test SynchronizationLogMapper has expected entity class.
     *
     * @return void
     */
    public function testSynchronizationLogMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationLogMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(SynchronizationLog::class, $property->getValue($this->synchronizationLogMapper));
    }

    /**
     * Test SynchronizationLogMapper has expected methods.
     *
     * @return void
     */
    public function testSynchronizationLogMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->synchronizationLogMapper, 'find'));
        $this->assertTrue(method_exists($this->synchronizationLogMapper, 'findAll'));
        $this->assertTrue(method_exists($this->synchronizationLogMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->synchronizationLogMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->synchronizationLogMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->synchronizationLogMapper, 'cleanupExpired'));
    }

    /**
     * Test constructor dependencies are properly injected.
     *
     * @return void
     */
    public function testConstructorDependencies(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationLogMapper);
        
        $userSessionProperty = $reflection->getProperty('userSession');
        $userSessionProperty->setAccessible(true);
        $this->assertSame($this->userSession, $userSessionProperty->getValue($this->synchronizationLogMapper));
        
        $sessionProperty = $reflection->getProperty('session');
        $sessionProperty->setAccessible(true);
        $this->assertSame($this->session, $sessionProperty->getValue($this->synchronizationLogMapper));
    }
}
