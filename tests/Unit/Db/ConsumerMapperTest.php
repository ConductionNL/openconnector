<?php

declare(strict_types=1);

/**
 * ConsumerMapperTest
 *
 * Unit tests for the ConsumerMapper class to verify database operations,
 * CRUD functionality, and consumer retrieval methods.
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

use OCA\OpenConnector\Db\Consumer;
use OCA\OpenConnector\Db\ConsumerMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * ConsumerMapper Test Suite
 *
 * Unit tests for consumer database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class ConsumerMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var ConsumerMapper */
    private ConsumerMapper $consumerMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->consumerMapper = new ConsumerMapper($this->db);
    }

    /**
     * Test ConsumerMapper can be instantiated.
     *
     * @return void
     */
    public function testConsumerMapperInstantiation(): void
    {
        $this->assertInstanceOf(ConsumerMapper::class, $this->consumerMapper);
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
            ->with('openconnector_consumers')
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

        $this->consumerMapper->find($id);
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
            ->with('openconnector_consumers')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->consumerMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Consumer',
            'type' => 'webhook',
            'enabled' => true
        ];

        $this->consumerMapper->createFromArray($object);
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
            'name' => 'Updated Consumer',
            'enabled' => false
        ];

        $this->consumerMapper->updateFromArray($id, $object);
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
            ->with('openconnector_consumers')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '10']);

        $count = $this->consumerMapper->getTotalCallCount();
        $this->assertEquals(10, $count);
    }

    /**
     * Test ConsumerMapper has expected table name.
     *
     * @return void
     */
    public function testConsumerMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->consumerMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_consumers', $property->getValue($this->consumerMapper));
    }

    /**
     * Test ConsumerMapper has expected entity class.
     *
     * @return void
     */
    public function testConsumerMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->consumerMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Consumer::class, $property->getValue($this->consumerMapper));
    }

    /**
     * Test ConsumerMapper has expected methods.
     *
     * @return void
     */
    public function testConsumerMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->consumerMapper, 'find'));
        $this->assertTrue(method_exists($this->consumerMapper, 'findAll'));
        $this->assertTrue(method_exists($this->consumerMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->consumerMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->consumerMapper, 'getTotalCallCount'));
    }
}
