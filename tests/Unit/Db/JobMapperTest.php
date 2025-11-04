<?php

declare(strict_types=1);

/**
 * JobMapperTest
 *
 * Unit tests for the JobMapper class to verify database operations
 * and Job management functionality.
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

use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\JobMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * JobMapper Test Suite
 *
 * Unit tests for Job database operations, including
 * CRUD operations and Job management methods.
 */
class JobMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var JobMapper */
    private JobMapper $JobMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->JobMapper = new JobMapper($this->db);
    }

    /**
     * Test JobMapper can be instantiated.
     *
     * @return void
     */
    public function testJobMapperInstantiation(): void
    {
        $this->assertInstanceOf(JobMapper::class, $this->JobMapper);
    }

    /**
     * Test that JobMapper has the expected table name.
     *
     * @return void
     */
    public function testJobMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->JobMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_jobs', $property->getValue($this->JobMapper));
    }

    /**
     * Test that JobMapper has the expected entity class.
     *
     * @return void
     */
    public function testJobMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->JobMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Job::class, $property->getValue($this->JobMapper));
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
                    'name' => 'Test Job',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Job = $this->JobMapper->find($id);

        $this->assertInstanceOf(Job::class, $Job);
        $this->assertEquals($id, $Job->getId());
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

        $Jobs = $this->JobMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($Jobs);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test Job'
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

        $Job = $this->JobMapper->createFromArray($data);

        $this->assertInstanceOf(Job::class, $Job);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated Job'];
        
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
                    'name' => 'Test Job',
                    'created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Job = $this->JobMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(Job::class, $Job);
    }

    /**
     * Test that JobMapper has the expected methods.
     *
     * @return void
     */
    public function testJobMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->JobMapper, 'find'));
        $this->assertTrue(method_exists($this->JobMapper, 'findAll'));
        $this->assertTrue(method_exists($this->JobMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->JobMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->JobMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->JobMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->JobMapper, 'findByArgumentIds'));
        $this->assertTrue(method_exists($this->JobMapper, 'findRunnable'));
        $this->assertTrue(method_exists($this->JobMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->JobMapper, 'getSlugToIdMap'));
    }
}