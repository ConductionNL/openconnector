<?php

declare(strict_types=1);

/**
 * RuleMapperTest
 *
 * Unit tests for the RuleMapper class to verify database operations,
 * CRUD functionality, and rule retrieval methods.
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

use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\RuleMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * RuleMapper Test Suite
 *
 * Unit tests for rule database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class RuleMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var RuleMapper */
    private RuleMapper $ruleMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->ruleMapper = new RuleMapper($this->db);
    }

    /**
     * Test RuleMapper can be instantiated.
     *
     * @return void
     */
    public function testRuleMapperInstantiation(): void
    {
        $this->assertInstanceOf(RuleMapper::class, $this->ruleMapper);
    }

    /**
     * Test find method with numeric ID.
     *
     * @return void
     */
    public function testFindWithNumericId(): void
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
            ->with('openconnector_rules')
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

        $this->ruleMapper->find($id);
    }

    /**
     * Test find method with string ID (UUID/slug).
     *
     * @return void
     */
    public function testFindWithStringId(): void
    {
        $id = 'test-uuid';
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
            ->with('openconnector_rules')
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

        $this->ruleMapper->find($id);
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
            ->with('openconnector_rules')
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

        $this->ruleMapper->findByRef($reference);
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
        $ids = ['id' => [1, 2, 3]];

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
            ->with('openconnector_rules')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('order', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->ruleMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams, $ids);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Rule',
            'condition' => 'status = "active"',
            'action' => 'send_notification',
            'enabled' => true
        ];

        $this->ruleMapper->createFromArray($object);
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
            'name' => 'Updated Rule',
            'enabled' => false
        ];

        $this->ruleMapper->updateFromArray($id, $object);
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
            ->with('openconnector_rules')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '8']);

        $count = $this->ruleMapper->getTotalCount();
        $this->assertEquals(8, $count);
    }

    /**
     * Test reorder method.
     *
     * @return void
     */
    public function testReorder(): void
    {
        $orderMap = [1 => 1, 2 => 2, 3 => 3];
        
        $qb = $this->createMock(IQueryBuilder::class);
        
        $this->db->expects($this->exactly(3))
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->exactly(3))
            ->method('update')
            ->with('openconnector_rules')
            ->willReturnSelf();

        $qb->expects($this->exactly(3))
            ->method('set')
            ->willReturnSelf();

        $qb->expects($this->exactly(3))
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(1);

        $this->ruleMapper->reorder($orderMap);
    }

    /**
     * Test findByConfiguration method.
     *
     * @return void
     */
    public function testFindByConfiguration(): void
    {
        $configurationId = 'test-config';
        
        $this->ruleMapper->findByConfiguration($configurationId);
    }

    /**
     * Test getIdToSlugMap method.
     *
     * @return void
     */
    public function testGetIdToSlugMap(): void
    {
        $qb = $this->createMock(IQueryBuilder::class);
        $result = $this->createMock(Result::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('id', 'slug')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['id' => '1', 'slug' => 'test-rule'],
                false
            );

        $result->expects($this->once())
            ->method('closeCursor');

        $mappings = $this->ruleMapper->getIdToSlugMap();
        $this->assertIsArray($mappings);
    }

    /**
     * Test getSlugToIdMap method.
     *
     * @return void
     */
    public function testGetSlugToIdMap(): void
    {
        $qb = $this->createMock(IQueryBuilder::class);
        $result = $this->createMock(Result::class);
        
        $this->db->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('id', 'slug')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['id' => '1', 'slug' => 'test-rule'],
                false
            );

        $result->expects($this->once())
            ->method('closeCursor');

        $mappings = $this->ruleMapper->getSlugToIdMap();
        $this->assertIsArray($mappings);
    }

    /**
     * Test RuleMapper has expected table name.
     *
     * @return void
     */
    public function testRuleMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->ruleMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_rules', $property->getValue($this->ruleMapper));
    }

    /**
     * Test RuleMapper has expected entity class.
     *
     * @return void
     */
    public function testRuleMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->ruleMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Rule::class, $property->getValue($this->ruleMapper));
    }

    /**
     * Test RuleMapper has expected methods.
     *
     * @return void
     */
    public function testRuleMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->ruleMapper, 'find'));
        $this->assertTrue(method_exists($this->ruleMapper, 'findByRef'));
        $this->assertTrue(method_exists($this->ruleMapper, 'findAll'));
        $this->assertTrue(method_exists($this->ruleMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->ruleMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->ruleMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->ruleMapper, 'reorder'));
        $this->assertTrue(method_exists($this->ruleMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->ruleMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->ruleMapper, 'getSlugToIdMap'));
    }

    /**
     * Test RuleMapper has private getMaxOrder method.
     *
     * @return void
     */
    public function testRuleMapperHasPrivateGetMaxOrderMethod(): void
    {
        $reflection = new \ReflectionClass($this->ruleMapper);
        
        $this->assertTrue($reflection->hasMethod('getMaxOrder'));
        
        $getMaxOrderMethod = $reflection->getMethod('getMaxOrder');
        $this->assertTrue($getMaxOrderMethod->isPrivate());
    }
}
