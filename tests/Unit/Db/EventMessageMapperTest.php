<?php

declare(strict_types=1);

/**
 * EventMessageMapperTest
 *
 * Unit tests for the EventMessageMapper class to verify database operations,
 * CRUD functionality, and event message retrieval methods.
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
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * EventMessageMapper Test Suite
 *
 * Unit tests for event message database operations, including
 * CRUD operations and specialized retrieval methods.
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
            ->with('openconnector_event_messages')
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

        $this->eventMessageMapper->find($id);
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
            ->with('openconnector_event_messages')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->eventMessageMapper->findAll($limit, $offset, $filters);
    }

    /**
     * Test findPendingRetries method.
     *
     * @return void
     */
    public function testFindPendingRetries(): void
    {
        $maxRetries = 5;
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
            ->with('openconnector_event_messages')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(3))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->eventMessageMapper->findPendingRetries($maxRetries);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'eventId' => 1,
            'message' => 'Test message',
            'status' => 'pending'
        ];

        $this->eventMessageMapper->createFromArray($data);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = [
            'status' => 'delivered',
            'response' => 'Success'
        ];

        $this->eventMessageMapper->updateFromArray($id, $data);
    }

    /**
     * Test markDelivered method.
     *
     * @return void
     */
    public function testMarkDelivered(): void
    {
        $id = 1;
        $response = ['status' => 'success', 'message' => 'Delivered'];

        $this->eventMessageMapper->markDelivered($id, $response);
    }

    /**
     * Test markFailed method.
     *
     * @return void
     */
    public function testMarkFailed(): void
    {
        $id = 1;
        $response = ['status' => 'error', 'message' => 'Failed'];
        $backoffMinutes = 10;

        $this->eventMessageMapper->markFailed($id, $response, $backoffMinutes);
    }

    /**
     * Test EventMessageMapper has expected table name.
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
     * Test EventMessageMapper has expected entity class.
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
     * Test EventMessageMapper has expected methods.
     *
     * @return void
     */
    public function testEventMessageMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->eventMessageMapper, 'find'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'findAll'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'findPendingRetries'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'markDelivered'));
        $this->assertTrue(method_exists($this->eventMessageMapper, 'markFailed'));
    }
}
