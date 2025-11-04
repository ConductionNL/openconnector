<?php

declare(strict_types=1);

/**
 * SynchronizationMapperTest
 *
 * Unit tests for the SynchronizationMapper class to verify database operations
 * and Synchronization management functionality.
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

use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * SynchronizationMapper Test Suite
 *
 * Unit tests for Synchronization database operations, including
 * CRUD operations and Synchronization management methods.
 */
class SynchronizationMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SynchronizationMapper */
    private SynchronizationMapper $SynchronizationMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->SynchronizationMapper = new SynchronizationMapper($this->db);
    }

    /**
     * Test SynchronizationMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationMapper::class, $this->SynchronizationMapper);
    }

    /**
     * Test that SynchronizationMapper has the expected table name.
     *
     * @return void
     */
    public function testSynchronizationMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronizations', $property->getValue($this->SynchronizationMapper));
    }

    /**
     * Test that SynchronizationMapper has the expected entity class.
     *
     * @return void
     */
    public function testSynchronizationMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Synchronization::class, $property->getValue($this->SynchronizationMapper));
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
                    'name' => 'Test Synchronization',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Synchronization = $this->SynchronizationMapper->find($id);

        $this->assertInstanceOf(Synchronization::class, $Synchronization);
        $this->assertEquals($id, $Synchronization->getId());
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

        $Synchronizations = $this->SynchronizationMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($Synchronizations);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test Synchronization'
        ];
        
        // Mock the query builder
        $qb = $this->createMock(IQueryBuilder::class);
        
        // Set up the database mock
        $this->db->method('getQueryBuilder')->willReturn($qb);
        
        // Mock the query builder chain
        $qb->method('insert')->willReturnSelf();
        $qb->method('values')->willReturnSelf();
        $qb->method('createNamedParameter')->willReturn(':param');
        
        // Mock the result
        $result = $this->createMock(IResult::class);
        $result->method('rowCount')->willReturn(1);
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeStatement')->willReturn(1);

        $Synchronization = $this->SynchronizationMapper->createFromArray($data);

        $this->assertInstanceOf(Synchronization::class, $Synchronization);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated Synchronization'];
        
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
                    'name' => 'Test Synchronization',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Synchronization = $this->SynchronizationMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(Synchronization::class, $Synchronization);
    }

    /**
     * Test that SynchronizationMapper has the expected methods.
     *
     * @return void
     */
    public function testSynchronizationMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'find'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'findAll'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'findByUuid'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'getByTarget'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'getTotalCallCount'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->SynchronizationMapper, 'getSlugToIdMap'));
    }
}