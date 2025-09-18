<?php

declare(strict_types=1);

/**
 * JobMapperTest
 *
 * Unit tests for the JobMapper class to verify database operations,
 * CRUD functionality, and job retrieval methods.
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
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Result;

/**
 * JobMapper Test Suite
 *
 * Unit tests for job database operations, including
 * CRUD operations and specialized retrieval methods.
 */
class JobMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private IDBConnection $db;

    /** @var JobMapper */
    private JobMapper $jobMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->jobMapper = new JobMapper($this->db);
    }

    /**
     * Test JobMapper can be instantiated.
     *
     * @return void
     */
    public function testJobMapperInstantiation(): void
    {
        $this->assertInstanceOf(JobMapper::class, $this->jobMapper);
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
            ->with('openconnector_jobs')
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

        $this->jobMapper->find($id);
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
            ->with('openconnector_jobs')
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

        $this->jobMapper->find($id);
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
            ->with('openconnector_jobs')
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

        $this->jobMapper->findByRef($reference);
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
            ->with('openconnector_jobs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $this->jobMapper->findAll($limit, $offset, $filters, $searchConditions, $searchParams, $ids);
    }

    /**
     * Test createFromArray method.
     *
     * @return void
     */
    public function testCreateFromArray(): void
    {
        $object = [
            'name' => 'Test Job',
            'jobClass' => 'TestJobClass',
            'enabled' => true
        ];

        $this->jobMapper->createFromArray($object);
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
            'name' => 'Updated Job',
            'enabled' => false
        ];

        $this->jobMapper->updateFromArray($id, $object);
    }

    /**
     * Test getTotalCount method.
     *
     * @return void
     */
    public function testGetTotalCount(): void
    {
        $filters = ['enabled' => true];
        
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
            ->with('openconnector_jobs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetch')
            ->willReturn(['count' => '15']);

        $count = $this->jobMapper->getTotalCount($filters);
        $this->assertEquals(15, $count);
    }

    /**
     * Test findByConfiguration method.
     *
     * @return void
     */
    public function testFindByConfiguration(): void
    {
        $configurationId = 'test-config';
        
        $this->jobMapper->findByConfiguration($configurationId);
    }

    /**
     * Test findByArgumentIds method.
     *
     * @return void
     */
    public function testFindByArgumentIds(): void
    {
        $synchronizationIds = ['sync1', 'sync2'];
        $endpointIds = ['endpoint1'];
        $sourceIds = ['source1'];

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
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $this->jobMapper->findByArgumentIds($synchronizationIds, $endpointIds, $sourceIds);
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
                ['id' => '1', 'slug' => 'test-job'],
                false
            );

        $mappings = $this->jobMapper->getIdToSlugMap();
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
                ['id' => '1', 'slug' => 'test-job'],
                false
            );

        $mappings = $this->jobMapper->getSlugToIdMap();
        $this->assertIsArray($mappings);
    }

    /**
     * Test findRunnable method.
     *
     * @return void
     */
    public function testFindRunnable(): void
    {
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
            ->with('openconnector_jobs')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('createNamedParameter')
            ->willReturn(':param1');

        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class));

        $this->jobMapper->findRunnable();
    }

    /**
     * Test JobMapper has expected table name.
     *
     * @return void
     */
    public function testJobMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->jobMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_jobs', $property->getValue($this->jobMapper));
    }

    /**
     * Test JobMapper has expected entity class.
     *
     * @return void
     */
    public function testJobMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->jobMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Job::class, $property->getValue($this->jobMapper));
    }

    /**
     * Test JobMapper has expected methods.
     *
     * @return void
     */
    public function testJobMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->jobMapper, 'find'));
        $this->assertTrue(method_exists($this->jobMapper, 'findByRef'));
        $this->assertTrue(method_exists($this->jobMapper, 'findAll'));
        $this->assertTrue(method_exists($this->jobMapper, 'createFromArray'));
        $this->assertTrue(method_exists($this->jobMapper, 'updateFromArray'));
        $this->assertTrue(method_exists($this->jobMapper, 'getTotalCount'));
        $this->assertTrue(method_exists($this->jobMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->jobMapper, 'findByArgumentIds'));
        $this->assertTrue(method_exists($this->jobMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->jobMapper, 'getSlugToIdMap'));
        $this->assertTrue(method_exists($this->jobMapper, 'findRunnable'));
    }
}
