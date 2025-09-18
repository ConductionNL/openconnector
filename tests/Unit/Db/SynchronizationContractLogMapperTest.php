<?php

declare(strict_types=1);

/**
 * SynchronizationContractLogMapperTest
 *
 * Unit tests for the SynchronizationContractLogMapper class to verify database operations,
 * CRUD functionality, and synchronization contract log retrieval methods.
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

use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserSession;
use OCP\ISession;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;
use Doctrine\DBAL\Result;

/**
 * SynchronizationContractLogMapper Test Suite
 *
 * Unit tests for synchronization contract log database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class SynchronizationContractLogMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var IUserSession|MockObject */
    private IUserSession $userSession;

    /** @var ISession|MockObject */
    private ISession $session;

    /** @var SynchronizationContractLogMapper */
    private SynchronizationContractLogMapper $synchronizationContractLogMapper;

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
        
        $this->synchronizationContractLogMapper = new SynchronizationContractLogMapper(
            $this->db,
            $this->userSession,
            $this->session
        );
    }

    /**
     * Test SynchronizationContractLogMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationContractLogMapper::class, $this->synchronizationContractLogMapper);
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
            ->with('openconnector_synchronization_contract_logs')
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

        $this->synchronizationContractLogMapper->find($id);
    }

    /**
     * Test findOnSynchronizationId method.
     *
     * @return void
     */
    public function testFindOnSynchronizationId(): void
    {
        $synchronizationId = 'sync-123';
        
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
            ->with('openconnector_synchronization_contract_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($synchronizationId)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->synchronizationContractLogMapper->findOnSynchronizationId($synchronizationId);
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
            ->with('openconnector_synchronization_contract_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->synchronizationContractLogMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams);
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
            'contractId' => 'contract-456',
            'status' => 'success',
            'message' => 'Synchronization completed'
        ];

        $this->synchronizationContractLogMapper->createFromArray($object);
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
            'message' => 'Synchronization failed'
        ];

        $this->synchronizationContractLogMapper->updateFromArray($id, $object);
    }

    /**
     * Test getSyncStatsByDateRange method.
     *
     * @return void
     */
    public function testGetSyncStatsByDateRange(): void
    {
        $from = new DateTime('2024-01-01');
        $to = new DateTime('2024-01-31');
        
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
            ->with('openconnector_synchronization_contract_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('groupBy')
            ->with('date')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('date', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['date' => '2024-01-01', 'executions' => '5'],
                false
            );

        $stats = $this->synchronizationContractLogMapper->getSyncStatsByDateRange($from, $to);
        $this->assertIsArray($stats);
    }

    /**
     * Test getSyncStatsByHourRange method.
     *
     * @return void
     */
    public function testGetSyncStatsByHourRange(): void
    {
        $from = new DateTime('2024-01-01');
        $to = new DateTime('2024-01-31');
        
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
            ->with('openconnector_synchronization_contract_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('groupBy')
            ->with('hour')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('hour', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['hour' => '10', 'executions' => '3'],
                false
            );

        $stats = $this->synchronizationContractLogMapper->getSyncStatsByHourRange($from, $to);
        $this->assertIsArray($stats);
    }

    /**
     * Test SynchronizationContractLogMapper has expected table name.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationContractLogMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronization_contract_logs', $property->getValue($this->synchronizationContractLogMapper));
    }

    /**
     * Test SynchronizationContractLogMapper has expected entity class.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationContractLogMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(SynchronizationContractLog::class, $property->getValue($this->synchronizationContractLogMapper));
    }

    /**
     * Test SynchronizationContractLogMapper has expected methods.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->synchronizationContractLogMapper, 'find'));
        $this->assertTrue(method_exists($this->synchronizationContractLogMapper, 'findOnSynchronizationId'));
        $this->assertTrue(method_exists($this->synchronizationContractLogMapper, 'findAll'));
        $this->assertTrue(method_exists($this->synchronizationContractLogMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->synchronizationContractLogMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->synchronizationContractLogMapper, 'getSyncStatsByDateRange'));
        $this->assertTrue(method_exists($this->synchronizationContractLogMapper, 'getSyncStatsByHourRange'));
    }

    /**
     * Test constructor dependencies are properly injected.
     *
     * @return void
     */
    public function testConstructorDependencies(): void
    {
        $reflection = new \ReflectionClass($this->synchronizationContractLogMapper);
        
        $userSessionProperty = $reflection->getProperty('userSession');
        $userSessionProperty->setAccessible(true);
        $this->assertSame($this->userSession, $userSessionProperty->getValue($this->synchronizationContractLogMapper));
        
        $sessionProperty = $reflection->getProperty('session');
        $sessionProperty->setAccessible(true);
        $this->assertSame($this->session, $sessionProperty->getValue($this->synchronizationContractLogMapper));
    }
}
