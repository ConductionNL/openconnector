<?php

declare(strict_types=1);

/**
 * EventMapperTest
 *
 * Unit tests for the EventMapper class to verify database operations
 * and event management functionality.
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

use OCA\OpenConnector\Db\Event;
use OCA\OpenConnector\Db\EventMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * EventMapper Test Suite
 *
 * Unit tests for event database operations, including
 * CRUD operations and event management methods.
 */
class EventMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var EventMapper */
    private EventMapper $eventMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->eventMapper = new EventMapper($this->db);
    }

    /**
     * Test EventMapper can be instantiated.
     *
     * @return void
     */
    public function testEventMapperInstantiation(): void
    {
        $this->assertInstanceOf(EventMapper::class, $this->eventMapper);
    }

    /**
     * Test that EventMapper has the expected table name.
     *
     * @return void
     */
    public function testEventMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->eventMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_events', $property->getValue($this->eventMapper));
    }

    /**
     * Test that EventMapper has the expected entity class.
     *
     * @return void
     */
    public function testEventMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->eventMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Event::class, $property->getValue($this->eventMapper));
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
                    'type' => 'test.event',
                    'specversion' => '1.0',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $event = $this->eventMapper->find($id);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($id, $event->getId());
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
        $filters = ['source' => 'https://example.com'];
        
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
        $expr->method('eq')->willReturn('source = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $events = $this->eventMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($events);
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
            'type' => 'test.event',
            'specversion' => '1.0'
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

        $event = $this->eventMapper->createFromArray($data);

        $this->assertInstanceOf(Event::class, $event);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['source' => 'https://updated.com'];
        
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
                    'type' => 'test.event',
                    'specversion' => '1.0',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $event = $this->eventMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(Event::class, $event);
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
        $expr->method('eq')->willReturn('source = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetch')->willReturn(['count' => 42]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('execute')->willReturn($result);

        $count = $this->eventMapper->getTotalCount();

        $this->assertEquals(42, $count);
    }

    /**
     * Test that EventMapper has the expected methods.
     *
     * @return void
     */
    public function testEventMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->eventMapper, 'find'));
        $this->assertTrue(method_exists($this->eventMapper, 'findAll'));
        $this->assertTrue(method_exists($this->eventMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->eventMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->eventMapper, 'getTotalCount'));
    }
}