<?php

declare(strict_types=1);

/**
 * EventSubscriptionMapperTest
 *
 * Unit tests for the EventSubscriptionMapper class to verify database operations,
 * CRUD functionality, and event subscription retrieval methods.
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
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * EventSubscriptionMapper Test Suite
 *
 * Unit tests for event subscription database operations, including
 * CRUD operations and specialized retrieval methods.
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
            ->with('openconnector_event_subscriptions')
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

        $this->eventSubscriptionMapper->find($id);
    }

    /**
     * Test findByRef method.
     *
     * @return void
     */
    public function testFindByRef(): void
    {
        $reference = 'test-ref';
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
            ->with('openconnector_event_subscriptions')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('createNamedParameter')
            ->with($reference)
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->eventSubscriptionMapper->findByRef($reference);
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
        $filters = ['enabled' => true];

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
            ->with('openconnector_event_subscriptions')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->eventSubscriptionMapper->findAll($limit, $offset, $filters);
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
            'consumerId' => 2,
            'enabled' => true
        ];

        $this->eventSubscriptionMapper->createFromArray($data);
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
            'enabled' => false,
            'lastTriggered' => new \DateTime()
        ];

        $this->eventSubscriptionMapper->updateFromArray($id, $data);
    }

    /**
     * Test EventSubscriptionMapper has expected table name.
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
     * Test EventSubscriptionMapper has expected entity class.
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
     * Test EventSubscriptionMapper has expected methods.
     *
     * @return void
     */
    public function testEventSubscriptionMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'find'));
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'findByRef'));
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'findAll'));
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->eventSubscriptionMapper, 'updateFromArray'));
    }
}
