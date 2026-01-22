<?php

declare(strict_types=1);

/**
 * SynchronizationContractLogMapperTest
 *
 * Unit tests for the SynchronizationContractLogMapper class to verify database operations
 * and SynchronizationContractLog management functionality.
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
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use OCP\IUserSession;
use OCP\ISession;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * SynchronizationContractLogMapper Test Suite
 *
 * Unit tests for SynchronizationContractLog database operations, including
 * CRUD operations and SynchronizationContractLog management methods.
 */
class SynchronizationContractLogMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SynchronizationContractLogMapper */
    private SynchronizationContractLogMapper $SynchronizationContractLogMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $userSession = $this->createMock(IUserSession::class);
        $session = $this->createMock(ISession::class);
        $this->SynchronizationContractLogMapper = new SynchronizationContractLogMapper($this->db, $userSession, $session);
    }

    /**
     * Test SynchronizationContractLogMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationContractLogMapper::class, $this->SynchronizationContractLogMapper);
    }

    /**
     * Test that SynchronizationContractLogMapper has the expected table name.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationContractLogMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronization_contract_logs', $property->getValue($this->SynchronizationContractLogMapper));
    }

    /**
     * Test that SynchronizationContractLogMapper has the expected entity class.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationContractLogMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(SynchronizationContractLog::class, $property->getValue($this->SynchronizationContractLogMapper));
    }

    /**
     * Test find method with valid ID.
     *
     * @return void
     */
    public function testFindWithValidId(): void
    {
        $id = 1;
        
        // Mock the query builder and expression builder
        $qb = $this->createMock(IQueryBuilder::class);
        $expr = $this->createMock(IExpressionBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('eq')->willReturn('id = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => $id,
                    'message' => 'Test SynchronizationContractLog',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationContractLog = $this->SynchronizationContractLogMapper->find($id);

        $this->assertInstanceOf(SynchronizationContractLog::class, $SynchronizationContractLog);
        $this->assertEquals($id, $SynchronizationContractLog->getId());
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
        $filters = ['name' => 'Test'];
        
        // Mock the query builder and expression builder
        $qb = $this->createMock(IQueryBuilder::class);
        $expr = $this->createMock(IExpressionBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('eq')->willReturn('name = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationContractLogs = $this->SynchronizationContractLogMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($SynchronizationContractLogs);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test SynchronizationContractLog'
        ];
        
        // Mock the query builder
        $qb = $this->createMock(IQueryBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('insert')->willReturnSelf();
        $qb->method('values')->willReturnSelf();
        $qb->method('createNamedParameter')->willReturn(':param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('rowCount')->willReturn(1);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeStatement')->willReturn(1);

        $SynchronizationContractLog = $this->SynchronizationContractLogMapper->createFromArray($data);

        $this->assertInstanceOf(SynchronizationContractLog::class, $SynchronizationContractLog);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated SynchronizationContractLog'];
        
        // Mock the query builder and expression builder
        $qb = $this->createMock(IQueryBuilder::class);
        $expr = $this->createMock(IExpressionBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('eq')->willReturn('id = :param');
        
        // Mock the result for find
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => $id,
                    'message' => 'Test SynchronizationContractLog',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationContractLog = $this->SynchronizationContractLogMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(SynchronizationContractLog::class, $SynchronizationContractLog);
    }

    /**
     * Test that SynchronizationContractLogMapper has the expected methods.
     *
     * @return void
     */
    public function testSynchronizationContractLogMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->SynchronizationContractLogMapper, 'find'));
        $this->assertTrue(method_exists($this->SynchronizationContractLogMapper, 'findAll'));
        $this->assertTrue(method_exists($this->SynchronizationContractLogMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationContractLogMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationContractLogMapper, 'findOnSynchronizationId'));
        $this->assertTrue(method_exists($this->SynchronizationContractLogMapper, 'getSyncStatsByDateRange'));
        $this->assertTrue(method_exists($this->SynchronizationContractLogMapper, 'getSyncStatsByHourRange'));
    }
}