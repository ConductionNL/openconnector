<?php

declare(strict_types=1);

/**
 * EventSubscriptionMapperTest
 *
 * Unit tests for the EventSubscriptionMapper class to verify database operations
 * and event subscription management functionality.
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

use OCA\OpenConnector\Db\EventSubscription;
use OCA\OpenConnector\Db\EventSubscriptionMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * EventSubscriptionMapper Test Suite
 *
 * Unit tests for event subscription database operations, including
 * CRUD operations and event subscription management methods.
 */
class EventSubscriptionMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var EventSubscriptionMapper */
    private EventSubscriptionMapper $eventSubscriptionMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->eventSubscriptionMapper = new EventSubscriptionMapper($this->db);
    }

    /**
     * Test EventSubscriptionMapper can be instantiated.
     *
     * @return void
     */
    public function testEventSubscriptionMapperInstantiation(): void
    {
        $this->assertInstanceOf(EventSubscriptionMapper::class, $this->eventSubscriptionMapper);
    }

    /**
     * Test that EventSubscriptionMapper has the expected table name.
     *
     * @return void
     */
    public function testEventSubscriptionMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->eventSubscriptionMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_event_subscriptions', $property->getValue($this->eventSubscriptionMapper));
    }

    /**
     * Test that EventSubscriptionMapper has the expected entity class.
     *
     * @return void
     */
    public function testEventSubscriptionMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->eventSubscriptionMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(EventSubscription::class, $property->getValue($this->eventSubscriptionMapper));
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
                    'source' => 'https://example.com',
                    'sink' => 'https://consumer.com/webhook',
                    'protocol' => 'HTTP',
                    'style' => 'push',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $eventSubscription = $this->eventSubscriptionMapper->find($id);

        $this->assertInstanceOf(EventSubscription::class, $eventSubscription);
        $this->assertEquals($id, $eventSubscription->getId());
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
        $filters = ['protocol' => 'HTTP'];
        
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
        $expr->method('eq')->willReturn('protocol = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $eventSubscriptions = $this->eventSubscriptionMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($eventSubscriptions);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'source' => 'https://example.com',
            'sink' => 'https://consumer.com/webhook',
            'protocol' => 'HTTP',
            'style' => 'push'
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

        $eventSubscription = $this->eventSubscriptionMapper->createFromArray($data);

        $this->assertInstanceOf(EventSubscription::class, $eventSubscription);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['protocol' => 'MQTT'];
        
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
                    'source' => 'https://example.com',
                    'sink' => 'https://consumer.com/webhook',
                    'protocol' => 'HTTP',
                    'style' => 'push',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $eventSubscription = $this->eventSubscriptionMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(EventSubscription::class, $eventSubscription);
    }

    /**
     * Test that EventSubscriptionMapper has the expected methods.
     *
     * @return void
     */
    public function testEventSubscriptionMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'find'));
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'findAll'));
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'updateFromArray'));
    }
}