<?php

declare(strict_types=1);

/**
 * MappingMapperTest
 *
 * Unit tests for the MappingMapper class to verify database operations
 * and Mapping management functionality.
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

use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\IDBConnection;
use OCP\DB\IResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

/**
 * MappingMapper Test Suite
 *
 * Unit tests for Mapping database operations, including
 * CRUD operations and Mapping management methods.
 */
class MappingMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var MappingMapper */
    private MappingMapper $MappingMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->MappingMapper = new MappingMapper($this->db);
    }

    /**
     * Test MappingMapper can be instantiated.
     *
     * @return void
     */
    public function testMappingMapperInstantiation(): void
    {
        $this->assertInstanceOf(MappingMapper::class, $this->MappingMapper);
    }

    /**
     * Test that MappingMapper has the expected table name.
     *
     * @return void
     */
    public function testMappingMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->MappingMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_mappings', $property->getValue($this->MappingMapper));
    }

    /**
     * Test that MappingMapper has the expected entity class.
     *
     * @return void
     */
    public function testMappingMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->MappingMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Mapping::class, $property->getValue($this->MappingMapper));
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
                    'name' => 'Test Mapping',
                    'date_created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Mapping = $this->MappingMapper->find($id);

        $this->assertInstanceOf(Mapping::class, $Mapping);
        $this->assertEquals($id, $Mapping->getId());
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

        $Mappings = $this->MappingMapper->findAll($limit, $offset, $filters);

        $this->assertIsArray($Mappings);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'name' => 'Test Mapping'
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

        $Mapping = $this->MappingMapper->createFromArray($data);

        $this->assertInstanceOf(Mapping::class, $Mapping);
    }

    /**
     * Test updateFromArray method.
     *
     * @return void
     */
    public function testUpdateFromArray(): void
    {
        $id = 1;
        $data = ['name' => 'Updated Mapping'];
        
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
                    'name' => 'Test Mapping',
                    'date_created' => (new DateTime())->format('Y-m-d H:i:s')
                ],
                false // Second call returns false to indicate no more rows
            );
        $result->method('closeCursor')->willReturn(true);
        
        $qb->method('executeQuery')->willReturn($result);

        $Mapping = $this->MappingMapper->updateFromArray($id, $data);

        $this->assertInstanceOf(Mapping::class, $Mapping);
    }

    /**
     * Test that MappingMapper has the expected methods.
     *
     * @return void
     */
    public function testMappingMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->MappingMapper, 'find'));
        $this->assertTrue(method_exists($this->MappingMapper, 'findAll'));
        $this->assertTrue(method_exists($this->MappingMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->MappingMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->MappingMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->MappingMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->MappingMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->MappingMapper, 'getSlugToIdMap'));
    }
}