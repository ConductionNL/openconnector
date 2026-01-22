<?php

declare(strict_types=1);

/**
 * SynchronizationLogMapperTest
 *
 * Unit tests for the SynchronizationLogMapper class to verify database operations
 * and SynchronizationLog management functionality.
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
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use OCP\IUserSession;
use OCP\ISession;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * SynchronizationLogMapper Test Suite
 *
 * Unit tests for SynchronizationLog database operations, including
 * CRUD operations and SynchronizationLog management methods.
 */
class SynchronizationLogMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SynchronizationLogMapper */
    private SynchronizationLogMapper $SynchronizationLogMapper;

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
        $this->SynchronizationLogMapper = new SynchronizationLogMapper($this->db, $userSession, $session);
    }

    /**
     * Test SynchronizationLogMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationLogMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationLogMapper::class, $this->SynchronizationLogMapper);
    }

    /**
     * Test that SynchronizationLogMapper has the expected table name.
     *
     * @return void
     */
    public function testSynchronizationLogMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationLogMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronization_logs', $property->getValue($this->SynchronizationLogMapper));
    }

    /**
     * Test that SynchronizationLogMapper has the expected entity class.
     *
     * @return void
     */
    public function testSynchronizationLogMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationLogMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(SynchronizationLog::class, $property->getValue($this->SynchronizationLogMapper));
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
                    'message' => 'Test SynchronizationLog',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationLog = $this->SynchronizationLogMapper->find($id);

        $this->assertInstanceOf(SynchronizationLog::class, $SynchronizationLog);
        $this->assertEquals($id, $SynchronizationLog->getId());
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
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('eq')->willReturn('message = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationLogs = $this->SynchronizationLogMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($SynchronizationLogs);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test SynchronizationLog'
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

        $SynchronizationLog = $this->SynchronizationLogMapper->createFromArray($data);

        $this->assertInstanceOf(SynchronizationLog::class, $SynchronizationLog);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated SynchronizationLog'];
        
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
                    'message' => 'Test SynchronizationLog',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationLog = $this->SynchronizationLogMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(SynchronizationLog::class, $SynchronizationLog);
    }

    /**
     * Test that SynchronizationLogMapper has the expected methods.
     *
     * @return void
     */
    public function testSynchronizationLogMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->SynchronizationLogMapper, 'find'));
        $this->assertTrue(method_exists($this->SynchronizationLogMapper, 'findAll'));
        $this->assertTrue(method_exists($this->SynchronizationLogMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationLogMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationLogMapper, 'cleanupExpired'));
        $this->assertTrue(method_exists($this->SynchronizationLogMapper, 'getTotalCount'));
    }
}