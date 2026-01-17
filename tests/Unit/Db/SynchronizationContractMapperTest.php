<?php

declare(strict_types=1);

/**
 * SynchronizationContractMapperTest
 *
 * Unit tests for the SynchronizationContractMapper class to verify database operations
 * and SynchronizationContract management functionality.
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

use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * SynchronizationContractMapper Test Suite
 *
 * Unit tests for SynchronizationContract database operations, including
 * CRUD operations and SynchronizationContract management methods.
 */
class SynchronizationContractMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SynchronizationContractMapper */
    private SynchronizationContractMapper $SynchronizationContractMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->SynchronizationContractMapper = new SynchronizationContractMapper($this->db);
    }

    /**
     * Test SynchronizationContractMapper can be instantiated.
     *
     * @return void
     */
    public function testSynchronizationContractMapperInstantiation(): void
    {
        $this->assertInstanceOf(SynchronizationContractMapper::class, $this->SynchronizationContractMapper);
    }

    /**
     * Test that SynchronizationContractMapper has the expected table name.
     *
     * @return void
     */
    public function testSynchronizationContractMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationContractMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_synchronization_contracts', $property->getValue($this->SynchronizationContractMapper));
    }

    /**
     * Test that SynchronizationContractMapper has the expected entity class.
     *
     * @return void
     */
    public function testSynchronizationContractMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->SynchronizationContractMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(SynchronizationContract::class, $property->getValue($this->SynchronizationContractMapper));
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
                    'uuid' => 'test-uuid',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationContract = $this->SynchronizationContractMapper->find($id);

        $this->assertInstanceOf(SynchronizationContract::class, $SynchronizationContract);
        $this->assertEquals($id, $SynchronizationContract->getId());
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

        $SynchronizationContracts = $this->SynchronizationContractMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($SynchronizationContracts);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test SynchronizationContract'
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

        $SynchronizationContract = $this->SynchronizationContractMapper->createFromArray($data);

        $this->assertInstanceOf(SynchronizationContract::class, $SynchronizationContract);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated SynchronizationContract'];
        
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
                    'uuid' => 'test-uuid',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $SynchronizationContract = $this->SynchronizationContractMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(SynchronizationContract::class, $SynchronizationContract);
    }

    /**
     * Test that SynchronizationContractMapper has the expected methods.
     *
     * @return void
     */
    public function testSynchronizationContractMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'find'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'findAll'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'findSyncContractByOriginId'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'findTargetIdByOriginId'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'findOnTarget'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'findByOriginAndTarget'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'findAllBySynchronizationAndSchema'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'findByTypeAndId'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'handleObjectRemoval'));
        $this->assertTrue(method_exists($this->SynchronizationContractMapper, 'getTotalCount'));
    }
}