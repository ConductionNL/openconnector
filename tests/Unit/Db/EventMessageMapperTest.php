<?php

declare(strict_types=1);

/**
 * EventMessageMapperTest
 *
 * Unit tests for the EventMessageMapper class to verify database operations
 * and event message management functionality.
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

use OCA\OpenConnector\Db\EventMessage;
use OCA\OpenConnector\Db\EventMessageMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * EventMessageMapper Test Suite
 *
 * Unit tests for event message database operations, including
 * CRUD operations and event message management methods.
 */
class EventMessageMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var EventMessageMapper */
    private EventMessageMapper $eventMessageMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->eventMessageMapper = new EventMessageMapper($this->db);
    }

    /**
     * Test EventMessageMapper can be instantiated.
     *
     * @return void
     */
    public function testEventMessageMapperInstantiation(): void
    {
        $this->assertInstanceOf(EventMessageMapper::class, $this->eventMessageMapper);
    }

    /**
     * Test that EventMessageMapper has the expected table name.
     *
     * @return void
     */
    public function testEventMessageMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->eventMessageMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_event_messages', $property->getValue($this->eventMessageMapper));
    }

    /**
     * Test that EventMessageMapper has the expected entity class.
     *
     * @return void
     */
    public function testEventMessageMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->eventMessageMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(EventMessage::class, $property->getValue($this->eventMessageMapper));
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
                    'event_id' => 1,
                    'consumer_id' => 1,
                    'status' => 'pending',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $eventMessage = $this->eventMessageMapper->find($id);

        $this->assertInstanceOf(EventMessage::class, $eventMessage);
        $this->assertEquals($id, $eventMessage->getId());
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
        $filters = ['status' => 'pending'];
        
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

        $eventMessages = $this->eventMessageMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($eventMessages);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'event_id' => 1,
            'consumer_id' => 1,
            'status' => 'pending'
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

        $eventMessage = $this->eventMessageMapper->createFromArray($data);

        $this->assertInstanceOf(EventMessage::class, $eventMessage);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['status' => 'delivered'];
        
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
                    'event_id' => 1,
                    'consumer_id' => 1,
                    'status' => 'pending',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $eventMessage = $this->eventMessageMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(EventMessage::class, $eventMessage);
    }

    /**
     * Test findPendingRetries method.
     *
     * @return void
     */
    public function testFindPendingRetries(): void
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
        $expr->method('eq')->willReturn('status = :param');
        $expr->method('lte')->willReturn('next_attempt <= :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $eventMessages = $this->eventMessageMapper->findPendingRetries();

        $this->assertIsArray($eventMessages);
    }

    /**
     * Test markDelivered method.
     *
     * @return void
     */
    public function testMarkDelivered(): void
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
        
        // Mock the result for find
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => $id,
                    'event_id' => 1,
                    'consumer_id' => 1,
                    'status' => 'pending',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $eventMessage = $this->eventMessageMapper->markDelivered($id, ['status' => 'success']);

        $this->assertInstanceOf(EventMessage::class, $eventMessage);
    }

    /**
     * Test markFailed method.
     *
     * @return void
     */
    public function testMarkFailed(): void
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
        
        // Mock the result for both find calls (markFailed calls find, then updateFromArray calls find again)
        $result = $this->createMock(IResult::class);
        $result->method('fetch')
            ->willReturnOnConsecutiveCalls(
                // First call from markFailed->find
                [
                    'id' => $id,
                    'event_id' => 1,
                    'consumer_id' => 1,
                    'status' => 'pending',
                    'retry_count' => 0,
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false, // Second call from markFailed->find
                // Third call from updateFromArray->find
                [
                    'id' => $id,
                    'event_id' => 1,
                    'consumer_id' => 1,
                    'status' => 'pending',
                    'retry_count' => 0,
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Fourth call from updateFromArray->find
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $eventMessage = $this->eventMessageMapper->markFailed($id, ['error' => 'test error']);

        $this->assertInstanceOf(EventMessage::class, $eventMessage);
    }

    /**
     * Test that EventMessageMapper has the expected methods.
     *
     * @return void
     */
    public function testEventMessageMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->eventMessageMapper, 'find'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'findAll'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'findPendingRetries'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'markDelivered'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'markFailed'));
    }
}