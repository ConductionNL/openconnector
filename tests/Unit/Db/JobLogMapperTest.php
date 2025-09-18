<?php

declare(strict_types=1);

/**
 * JobLogMapperTest
 *
 * Unit tests for the JobLogMapper class to verify database operations,
 * statistics functionality, and job log retrieval methods.
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
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;
use Doctrine\DBAL\Result;

/**
 * JobLogMapper Test Suite
 *
 * Unit tests for job log database operations, including
 * CRUD operations, statistics, and specialized retrieval methods.
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
            ->with('openconnector_job_logs')
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

        $this->jobLogMapper->find($id);
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
        $searchConditions = ['message LIKE :search'];
        $searchParams = ['search' => '%error%'];

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
            ->with('openconnector_job_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->jobLogMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams);
    }

    /**
     * Test createForJob method.
     *
     * @return void
     */
    public function testCreateForJob(): void
    {
        $job = $this->createMock(Job::class);
        $job->expects($this->once())->method('getId')->willReturn(1);
        $job->expects($this->once())->method('getJobClass')->willReturn('TestJob');
        $job->expects($this->once())->method('getJobListId')->willReturn('test-list');
        $job->expects($this->once())->method('getArguments')->willReturn(['arg1' => 'value1']);
        $job->expects($this->once())->method('getLastRun')->willReturn(new DateTime());
        $job->expects($this->once())->method('getNextRun')->willReturn(new DateTime());

        $object = [
            'level' => 'INFO',
            'message' => 'Test log message'
        ];

        $this->jobLogMapper->createForJob($job, $object);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'jobId' => 1,
            'level' => 'INFO',
            'message' => 'Test log message',
            'executionTime' => 100
        ];

        $this->jobLogMapper->createFromArray($object);
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
            'level' => 'ERROR',
            'message' => 'Updated log message'
        ];

        $this->jobLogMapper->updateFromArray($id, $object);
    }

    /**
     * Test getLastCallLog method.
     *
     * @return void
     */
    public function testGetLastCallLog(): void
    {
        $qb = $this->createMock(IQueryBuilder::class);
        $result = $this->createMock(Result::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('*')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->with('openconnector_job_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('created', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $lastLog = $this->jobLogMapper->getLastCallLog();
        $this->assertNull($lastLog);
    }

    /**
     * Test getJobStatsByDateRange method.
     *
     * @return void
     */
    public function testGetJobStatsByDateRange(): void
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
            ->with('openconnector_job_logs')
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
                ['date' => '2024-01-01', 'info' => '5', 'warning' => '1', 'error' => '0', 'debug' => '2'],
                false
            );

        $stats = $this->jobLogMapper->getJobStatsByDateRange($from, $to);
        $this->assertIsArray($stats);
    }

    /**
     * Test getJobStatsByHourRange method.
     *
     * @return void
     */
    public function testGetJobStatsByHourRange(): void
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
            ->with('openconnector_job_logs')
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
                ['hour' => '10', 'info' => '3', 'warning' => '0', 'error' => '1', 'debug' => '1'],
                false
            );

        $stats = $this->jobLogMapper->getJobStatsByHourRange($from, $to);
        $this->assertIsArray($stats);
    }

    /**
     * Test clearLogs method.
     *
     * @return void
     */
    public function testClearLogs(): void
    {
        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('delete')
            ->with('openconnector_job_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createFunction')
            ->with('NOW()')
            ->willReturn('NOW()');

        $qb->expects($this->once())
            ->method('executeStatement')
            ->willReturn(3);

        $result = $this->jobLogMapper->clearLogs();
        $this->assertTrue($result);
    }

    /**
     * Test getTotalCount method with filters.
     *
     * @return void
     */
    public function testGetTotalCountWithFilters(): void
    {
        $filters = ['level' => 'ERROR'];
        
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
            ->with('openconnector_job_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '8']);

        $count = $this->jobLogMapper->getTotalCount($filters);
        $this->assertEquals(8, $count);
    }

    /**
     * Test JobLogMapper has expected table name.
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
     * Test JobLogMapper has expected entity class.
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
     * Test JobLogMapper has expected methods.
     *
     * @return void
     */
    public function testJobLogMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->jobLogMapper, 'find'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'findAll'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'createForJob'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getLastCallLog'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getJobStatsByDateRange'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getJobStatsByHourRange'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'clearLogs'));
        $this->assertTrue(method_exists($this->jobLogMapper, 'getTotalCount'));
    }
}
