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
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;
use Doctrine\DBAL\Result;

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
            ->with('openconnector_call_logs')
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

        $this->callLogMapper->find($id);
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
        $searchConditions = ['name LIKE :search'];
        $searchParams = ['search' => '%test%'];
        $sortFields = ['created' => 'DESC'];

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
            ->with('openconnector_call_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->callLogMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams, $sortFields);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Call',
            'status' => 'success',
            'response_time' => 100
        ];

        $this->callLogMapper->createFromArray($object);
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
            'name' => 'Updated Call',
            'status' => 'failed'
        ];

        $this->callLogMapper->updateFromArray($id, $object);
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
            ->with('openconnector_call_logs')
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
            ->willReturn(5);

        $result = $this->callLogMapper->clearLogs();
        $this->assertTrue($result);
    }

    /**
     * Test getCallCountsByDate method.
     *
     * @return void
     */
    public function testGetCallCountsByDate(): void
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
            ->with('openconnector_call_logs')
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
                ['date' => '2024-01-01', 'count' => '5'],
                false
            );

        $counts = $this->callLogMapper->getCallCountsByDate();
        $this->assertIsArray($counts);
    }

    /**
     * Test getCallCountsByTime method.
     *
     * @return void
     */
    public function testGetCallCountsByTime(): void
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
            ->with('openconnector_call_logs')
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
                ['hour' => '10', 'count' => '3'],
                false
            );

        $counts = $this->callLogMapper->getCallCountsByTime();
        $this->assertIsArray($counts);
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
            ->with('openconnector_call_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '25']);

        $count = $this->callLogMapper->getTotalCallCount();
        $this->assertEquals(25, $count);
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
            ->with('openconnector_call_logs')
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

        $lastLog = $this->callLogMapper->getLastCallLog();
        $this->assertNull($lastLog);
    }

    /**
     * Test getCallStatsByDateRange method.
     *
     * @return void
     */
    public function testGetCallStatsByDateRange(): void
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
            ->with('openconnector_call_logs')
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
                ['date' => '2024-01-01', 'success' => '5', 'error' => '1'],
                false
            );

        $stats = $this->callLogMapper->getCallStatsByDateRange($from, $to);
        $this->assertIsArray($stats);
    }

    /**
     * Test getCallStatsByHourRange method.
     *
     * @return void
     */
    public function testGetCallStatsByHourRange(): void
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
            ->with('openconnector_call_logs')
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
                ['hour' => '10', 'success' => '3', 'error' => '0'],
                false
            );

        $stats = $this->callLogMapper->getCallStatsByHourRange($from, $to);
        $this->assertIsArray($stats);
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
            ->with('openconnector_call_logs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '15']);

        $count = $this->callLogMapper->getTotalCount($filters);
        $this->assertEquals(15, $count);
    }
}
