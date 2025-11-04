<?php

declare(strict_types=1);

/**
 * SourceMapperTest
 *
 * Unit tests for the SourceMapper class to verify database operations
 * and Source management functionality.
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

use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * SourceMapper Test Suite
 *
 * Unit tests for Source database operations, including
 * CRUD operations and Source management methods.
 */
class SourceMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var SourceMapper */
    private SourceMapper $SourceMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->SourceMapper = new SourceMapper($this->db);
    }

    /**
     * Test SourceMapper can be instantiated.
     *
     * @return void
     */
    public function testSourceMapperInstantiation(): void
    {
        $this->assertInstanceOf(SourceMapper::class, $this->SourceMapper);
    }

    /**
     * Test that SourceMapper has the expected table name.
     *
     * @return void
     */
    public function testSourceMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->SourceMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_sources', $property->getValue($this->SourceMapper));
    }

    /**
     * Test that SourceMapper has the expected entity class.
     *
     * @return void
     */
    public function testSourceMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->SourceMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Source::class, $property->getValue($this->SourceMapper));
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
                    'name' => 'Test Source',
                    'date_created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Source = $this->SourceMapper->find($id);

        $this->assertInstanceOf(Source::class, $Source);
        $this->assertEquals($id, $Source->getId());
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

        $Sources = $this->SourceMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($Sources);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test Source'
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

        $Source = $this->SourceMapper->createFromArray($data);

        $this->assertInstanceOf(Source::class, $Source);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated Source'];
        
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
                    'name' => 'Test Source',
                    'date_created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Source = $this->SourceMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(Source::class, $Source);
    }

    /**
     * Test that SourceMapper has the expected methods.
     *
     * @return void
     */
    public function testSourceMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->SourceMapper, 'find'));
        $this->assertTrue(method_exists($this->SourceMapper, 'findAll'));
        $this->assertTrue(method_exists($this->SourceMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->SourceMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->SourceMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->SourceMapper, 'findOrCreateByLocation'));
        $this->assertTrue(method_exists($this->SourceMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->SourceMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->SourceMapper, 'getSlugToIdMap'));
    }
}