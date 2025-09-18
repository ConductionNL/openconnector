<?php

declare(strict_types=1);

/**
 * EventMapperTest
 *
 * Unit tests for the EventMapper class to verify database operations,
 * CRUD functionality, and event retrieval methods.
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
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * EventMapper Test Suite
 *
 * Unit tests for event database operations, including
 * CRUD operations and specialized retrieval methods.
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
            ->with('openconnector_events')
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

        $this->eventMapper->find($id);
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
        $filters = ['type' => 'webhook'];
        $searchConditions = ['name LIKE :search'];
        $searchParams = ['search' => '%test%'];

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
            ->with('openconnector_events')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->eventMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Event',
            'type' => 'webhook',
            'enabled' => true
        ];

        $this->eventMapper->createFromArray($object);
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
            'name' => 'Updated Event',
            'enabled' => false
        ];

        $this->eventMapper->updateFromArray($id, $object);
    }

    /**
     * Test getTotalCount method.
     *
     * @return void
     */
    public function testGetTotalCount(): void
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
            ->with('openconnector_events')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '20']);

        $count = $this->eventMapper->getTotalCount();
        $this->assertEquals(20, $count);
    }

    /**
     * Test EventMapper has expected table name.
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
     * Test EventMapper has expected entity class.
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
     * Test EventMapper has expected methods.
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
