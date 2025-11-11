<?php

declare(strict_types=1);

/**
 * CallLogMapperTest
 *
 * Unit tests for the CallLogMapper class to verify database operations,
 * statistics functionality, and call log retrieval methods.
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

use OCA\OpenConnector\Db\CallLog;
use OCA\OpenConnector\Db\CallLogMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * CallLogMapper Test Suite
 *
 * Unit tests for call log database operations, including
 * CRUD operations, statistics, and specialized retrieval methods.
 */
class CallLogMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var CallLogMapper */
    private CallLogMapper $callLogMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->callLogMapper = new CallLogMapper($this->db);
    }

    /**
     * Test CallLogMapper can be instantiated.
     *
     * @return void
     */
    public function testCallLogMapperInstantiation(): void
    {
        $this->assertInstanceOf(CallLogMapper::class, $this->callLogMapper);
    }

    /**
     * Test that CallLogMapper has the expected table name.
     *
     * @return void
     */
    public function testCallLogMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->callLogMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_call_logs', $property->getValue($this->callLogMapper));
    }

    /**
     * Test that CallLogMapper has the expected entity class.
     *
     * @return void
     */
    public function testCallLogMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->callLogMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(CallLog::class, $property->getValue($this->callLogMapper));
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
                    'source_id' => 1,
                    'action_id' => 1,
                    'status_code' => 200,
                    'status_message' => 'OK',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $callLog = $this->callLogMapper->find($id);

        $this->assertInstanceOf(CallLog::class, $callLog);
        $this->assertEquals($id, $callLog->getId());
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
        $expr->method('eq')->willReturn('status = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $callLogs = $this->callLogMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($callLogs);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'source_id' => 1,
            'target_id' => 1,
            'method' => 'GET',
            'url' => 'https://example.com',
            'status' => 200,
            'response_time' => 0.5
        ];
        
        // Mock the query builder
        $qb = $this->createMock(IQueryBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('insert')->willReturnSelf();
        $qb->method('values')->willReturnSelf();
        $qb->method('createNamedParameter')->willReturn(':param');
        
        // Mock the result - executeStatement returns int, not IResult
        $qb->method('executeStatement')->willReturn(1);

        $callLog = $this->callLogMapper->createFromArray($data);

        $this->assertInstanceOf(CallLog::class, $callLog);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['status' => 201];
        
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
                    'source_id' => 1,
                    'action_id' => 1,
                    'status_code' => 200,
                    'status_message' => 'OK',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $callLog = $this->callLogMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(CallLog::class, $callLog);
    }

    /**
     * Test clearLogs method.
     *
     * @return void
     */
    public function testClearLogs(): void
    {
        $olderThan = new DateTime('-1 day');
        
        // Mock the query builder and expression builder
        $qb = $this->createMock(IQueryBuilder::class);
        $expr = $this->createMock(IExpressionBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('delete')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('isNotNull')->willReturn('created_at IS NOT NULL');
        $expr->method('lt')->willReturn('created_at < :param');
        
        // Mock the result - executeStatement returns int, not IResult
        $qb->method('executeStatement')->willReturn(5);

        $deletedCount = $this->callLogMapper->clearLogs($olderThan);

        $this->assertEquals(5, $deletedCount);
    }

    /**
     * Test getCallCountsByDate method.
     *
     * @return void
     */
    public function testGetCallCountsByDate(): void
    {
        $startDate = new DateTime('-7 days');
        $endDate = new DateTime();
        
        // Mock the query builder and expression builder
        $qb = $this->createMock(IQueryBuilder::class);
        $expr = $this->createMock(IExpressionBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('gte')->willReturn('created_at >= :param');
        $expr->method('lte')->willReturn('created_at <= :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['date' => '2024-01-01', 'count' => 10],
                ['date' => '2024-01-02', 'count' => 15],
                false // End of results
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $counts = $this->callLogMapper->getCallCountsByDate($startDate, $endDate);

        $this->assertIsArray($counts);
        $this->assertCount(2, $counts);
    }

    /**
     * Test getCallCountsByTime method.
     *
     * @return void
     */
    public function testGetCallCountsByTime(): void
    {
        $startDate = new DateTime('-7 days');
        $endDate = new DateTime();
        
        // Mock the query builder and expression builder
        $qb = $this->createMock(IQueryBuilder::class);
        $expr = $this->createMock(IExpressionBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('gte')->willReturn('created_at >= :param');
        $expr->method('lte')->willReturn('created_at <= :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['hour' => '09:00', 'count' => 5],
                ['hour' => '10:00', 'count' => 8],
                false // End of results
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $counts = $this->callLogMapper->getCallCountsByTime($startDate, $endDate);

        $this->assertIsArray($counts);
        $this->assertCount(2, $counts);
    }

    /**
     * Test getTotalCallCount method.
     *
     * @return void
     */
    public function testGetTotalCallCount(): void
    {
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
        $expr->method('eq')->willReturn('status = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['count' => 42],
                false // End of results
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $count = $this->callLogMapper->getTotalCallCount(['status' => 200]);

        $this->assertEquals(42, $count);
    }

    /**
     * Test that CallLogMapper has the expected methods.
     *
     * @return void
     */
    public function testCallLogMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->callLogMapper, 'find'));
        $this->assertTrue(method_exists($this->callLogMapper, 'findAll'));
        $this->assertTrue(method_exists($this->callLogMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->callLogMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->callLogMapper, 'clearLogs'));
        $this->assertTrue(method_exists($this->callLogMapper, 'getCallCountsByDate'));
        $this->assertTrue(method_exists($this->callLogMapper, 'getCallCountsByTime'));
        $this->assertTrue(method_exists($this->callLogMapper, 'getTotalCallCount'));
    }
}