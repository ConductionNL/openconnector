<?php

declare(strict_types=1);

/**
 * ConsumerMapperTest
 *
 * Unit tests for the ConsumerMapper class to verify database operations
 * and consumer management functionality.
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
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * ConsumerMapper Test Suite
 *
 * Unit tests for consumer database operations, including
 * CRUD operations and consumer management methods.
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
     * Test that ConsumerMapper has the expected table name.
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
     * Test that ConsumerMapper has the expected entity class.
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
                    'name' => 'Test Consumer',
                    'description' => 'Test Description',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $consumer = $this->consumerMapper->find($id);

        $this->assertInstanceOf(Consumer::class, $consumer);
        $this->assertEquals($id, $consumer->getId());
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
        $filters = ['name' => 'Test'];
        
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
        $expr->method('eq')->willReturn('name = :param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $consumers = $this->consumerMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($consumers);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test Consumer',
            'description' => 'Test Description'
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

        $consumer = $this->consumerMapper->createFromArray($data);

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated Consumer'];
        
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
                    'name' => 'Test Consumer',
                    'description' => 'Test Description',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $consumer = $this->consumerMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    /**
     * Test that ConsumerMapper has the expected methods.
     *
     * @return void
     */
    public function testConsumerMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->consumerMapper, 'find'));
        $this->assertTrue(method_exists($this->consumerMapper, 'findAll'));
        $this->assertTrue(method_exists($this->consumerMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->consumerMapper, 'updateFromArray'));
    }
}