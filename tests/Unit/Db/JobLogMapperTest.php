<?php

declare(strict_types=1);

/**
 * JobLogMapperTest
 *
 * Unit tests for the JobLogMapper class to verify database operations
 * and job log management functionality.
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

use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\JobLog;
use OCA\OpenConnector\Db\JobLogMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * JobLogMapper Test Suite
 *
 * Unit tests for job log database operations, including
 * CRUD operations and job log management methods.
 */
class JobLogMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var JobLogMapper */
    private JobLogMapper $jobLogMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->jobLogMapper = new JobLogMapper($this->db);
    }

    /**
     * Test JobLogMapper can be instantiated.
     *
     * @return void
     */
    public function testJobLogMapperInstantiation(): void
    {
        $this->assertInstanceOf(JobLogMapper::class, $this->jobLogMapper);
    }

    /**
     * Test that JobLogMapper has the expected table name.
     *
     * @return void
     */
    public function testJobLogMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->jobLogMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_job_logs', $property->getValue($this->jobLogMapper));
    }

    /**
     * Test that JobLogMapper has the expected entity class.
     *
     * @return void
     */
    public function testJobLogMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->jobLogMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(JobLog::class, $property->getValue($this->jobLogMapper));
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
                    'level' => 'INFO',
                    'message' => 'Job completed successfully',
                    'job_id' => 'job-123',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $jobLog = $this->jobLogMapper->find($id);

        $this->assertInstanceOf(JobLog::class, $jobLog);
        $this->assertEquals($id, $jobLog->getId());
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
        $filters = ['level' => 'ERROR'];
        
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
        $expr->method('eq')->willReturn('level = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $jobLogs = $this->jobLogMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($jobLogs);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'level' => 'INFO',
            'message' => 'Test job log',
            'job_id' => 'job-123'
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

        $jobLog = $this->jobLogMapper->createFromArray($data);

        $this->assertInstanceOf(JobLog::class, $jobLog);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['level' => 'WARNING'];
        
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
                    'level' => 'INFO',
                    'message' => 'Job completed successfully',
                    'job_id' => 'job-123',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $jobLog = $this->jobLogMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(JobLog::class, $jobLog);
    }

    /**
     * Test createForJob method.
     *
     * @return void
     */
    public function testCreateForJob(): void
    {
        // Create a real Job instance instead of mocking
        $job = new Job();
        $job->setId(1);
        $job->setJobClass('OCA\OpenConnector\Action\PingAction');
        $job->setJobListId('test-job-list-id');
        $job->setArguments(['param1' => 'value1']);
        $job->setLastRun(new \DateTime('2024-01-01 10:00:00'));
        $job->setNextRun(new \DateTime('2024-01-01 11:00:00'));

        $object = [
            'message' => 'Test job log message',
            'level' => 'info',
            'executionTime' => 1500
        ];

        // Mock the query builder for the insert operation
        $qb = $this->createMock(IQueryBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain for insert
        $qb->method('insert')->willReturnSelf();
        $qb->method('values')->willReturnSelf();
        $qb->method('createNamedParameter')->willReturn(':param');
        
        // Mock the result - executeStatement returns int, not IResult
        $qb->method('executeStatement')->willReturn(1);
        
        // Mock the lastInsertId method
        $this->db->method('lastInsertId')->willReturn(1);

        $result = $this->jobLogMapper->createForJob($job, $object);

        $this->assertInstanceOf(JobLog::class, $result);
        $this->assertEquals(1, $result->getJobId());
        $this->assertEquals('OCA\OpenConnector\Action\PingAction', $result->getJobClass());
        $this->assertEquals('test-job-list-id', $result->getJobListId());
        $this->assertEquals(['param1' => 'value1'], $result->getArguments());
        $this->assertEquals('Test job log message', $result->getMessage());
        $this->assertEquals('info', $result->getLevel());
        $this->assertEquals(1500, $result->getExecutionTime());
    }

    /**
     * Test getLastCallLog method.
     *
     * @return void
     */
    public function testGetLastCallLog(): void
    {
        $jobId = 'job-123';
        
        // Mock the query builder and expression builder
        $qb = $this->createMock(IQueryBuilder::class);
        $expr = $this->createMock(IExpressionBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $expr->method('eq')->willReturn('job_id = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'level' => 'INFO',
                    'message' => 'Job completed successfully',
                    'job_id' => $jobId,
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $jobLog = $this->jobLogMapper->getLastCallLog($jobId);

        $this->assertInstanceOf(JobLog::class, $jobLog);
    }

    /**
     * Test getJobStatsByDateRange method.
     *
     * @return void
     */
    public function testGetJobStatsByDateRange(): void
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
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $qb->method('createFunction')->willReturn('DATE(created) as date');
        $expr->method('gte')->willReturn('created >= :param');
        $expr->method('lte')->willReturn('created <= :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['date' => '2024-01-01', 'info' => 5, 'warning' => 2, 'error' => 1, 'debug' => 2],
                ['date' => '2024-01-02', 'info' => 8, 'warning' => 3, 'error' => 2, 'debug' => 2],
                false // End of results
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $stats = $this->jobLogMapper->getJobStatsByDateRange($startDate, $endDate);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('2024-01-01', $stats);
        $this->assertArrayHasKey('2024-01-02', $stats);
    }

    /**
     * Test getJobStatsByHourRange method.
     *
     * @return void
     */
    public function testGetJobStatsByHourRange(): void
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
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('createNamedParameter')->willReturn(':param');
        $qb->method('createFunction')->willReturn('HOUR(created) as hour');
        $expr->method('gte')->willReturn('created >= :param');
        $expr->method('lte')->willReturn('created <= :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['hour' => '09:00', 'info' => 3, 'warning' => 1, 'error' => 0, 'debug' => 1],
                ['hour' => '10:00', 'info' => 5, 'warning' => 2, 'error' => 1, 'debug' => 0],
                false // End of results
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $stats = $this->jobLogMapper->getJobStatsByHourRange($startDate, $endDate);

        $this->assertIsArray($stats);
        $this->assertCount(2, $stats);
        $this->assertArrayHasKey('09:00', $stats);
        $this->assertArrayHasKey('10:00', $stats);
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
        $expr->method('isNotNull')->willReturn('created IS NOT NULL');
        $expr->method('lt')->willReturn('created < :param');
        
        // Mock the result - executeStatement returns int, not IResult
        $qb->method('executeStatement')->willReturn(5);

        $deletedCount = $this->jobLogMapper->clearLogs($olderThan);

        $this->assertEquals(5, $deletedCount);
    }

    /**
     * Test getTotalCount method.
     *
     * @return void
     */
    public function testGetTotalCount(): void
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
        $expr->method('eq')->willReturn('level = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['count' => 42],
                false // End of results
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $count = $this->jobLogMapper->getTotalCount(['level' => 'ERROR']);

        $this->assertEquals(42, $count);
    }

    /**
     * Test that JobLogMapper has the expected methods.
     *
     * @return void
     */
    public function testJobLogMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->jobLogMapper, 'find'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'findAll'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'createForJob'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getLastCallLog'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getJobStatsByDateRange'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getJobStatsByHourRange'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'clearLogs'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getTotalCount'));
    }
}