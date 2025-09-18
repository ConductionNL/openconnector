<?php

declare(strict_types=1);

/**
 * EndpointMapperTest
 *
 * Unit tests for the EndpointMapper class to verify database operations,
 * caching functionality, and endpoint retrieval methods.
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

use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\EndpointMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * EndpointMapper Test Suite
 *
 * Unit tests for endpoint database operations, including
 * CRUD operations, caching, and specific retrieval methods.
 */
class EndpointMapperTest extends TestCase
{
    /** @var IDBConnection|MockObject */
    private MockObject $db;

    /** @var EndpointMapper */
    private EndpointMapper $endpointMapper;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMock(IDBConnection::class);
        $this->endpointMapper = new EndpointMapper($this->db);
    }

    /**
     * Test getByTarget method with no parameters (should throw exception).
     *
     * @return void
     */
    public function testGetByTargetWithNoParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either registerId or schemaId must be provided');

        $this->endpointMapper->getByTarget(null, null);
    }

    /**
     * Test isCacheDirty method when cache is clean.
     *
     * @return void
     */
    public function testIsCacheDirtyWhenClean(): void
    {
        $result = $this->endpointMapper->isCacheDirty();

        $this->assertFalse($result);
    }

    /**
     * Test setCacheClean method.
     *
     * @return void
     */
    public function testSetCacheClean(): void
    {
        // This should not throw an exception
        $this->endpointMapper->setCacheClean();
        $this->assertTrue(true); // If we get here, the method executed without error
    }

    /**
     * Test that EndpointMapper can be instantiated.
     *
     * @return void
     */
    public function testEndpointMapperInstantiation(): void
    {
        $this->assertInstanceOf(EndpointMapper::class, $this->endpointMapper);
    }

    /**
     * Test that EndpointMapper has the expected table name.
     *
     * @return void
     */
    public function testEndpointMapperTableName(): void
    {
        $reflection = new \ReflectionClass($this->endpointMapper);
        $property = $reflection->getProperty('tableName');
        $property->setAccessible(true);
        
        $this->assertEquals('openconnector_endpoints', $property->getValue($this->endpointMapper));
    }

    /**
     * Test that EndpointMapper has the expected entity class.
     *
     * @return void
     */
    public function testEndpointMapperEntityClass(): void
    {
        $reflection = new \ReflectionClass($this->endpointMapper);
        $property = $reflection->getProperty('entityClass');
        $property->setAccessible(true);
        
        $this->assertEquals(Endpoint::class, $property->getValue($this->endpointMapper));
    }

    /**
     * Test that EndpointMapper has the cache dirty flag constant.
     *
     * @return void
     */
    public function testEndpointMapperHasCacheDirtyFlag(): void
    {
        $reflection = new \ReflectionClass($this->endpointMapper);
        $constant = $reflection->getConstant('CACHE_DIRTY_FLAG');
        
        $this->assertEquals('/tmp/openconnector_endpoints_cache_dirty', $constant);
    }

    /**
     * Test that EndpointMapper has the expected methods.
     *
     * @return void
     */
    public function testEndpointMapperHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->endpointMapper, 'findByPathRegex'));
        $this->assertTrue(method_exists($this->endpointMapper, 'findByConfiguration'));
        $this->assertTrue(method_exists($this->endpointMapper, 'getByTarget'));
        $this->assertTrue(method_exists($this->endpointMapper, 'getIdToSlugMap'));
        $this->assertTrue(method_exists($this->endpointMapper, 'getSlugToIdMap'));
        $this->assertTrue(method_exists($this->endpointMapper, 'isCacheDirty'));
        $this->assertTrue(method_exists($this->endpointMapper, 'setCacheClean'));
    }

    /**
     * Test that EndpointMapper has the expected private methods.
     *
     * @return void
     */
    public function testEndpointMapperHasExpectedPrivateMethods(): void
    {
        $reflection = new \ReflectionClass($this->endpointMapper);
        
        $this->assertTrue($reflection->hasMethod('setCacheDirty'));
        $this->assertTrue($reflection->hasMethod('createEndpointRegex'));
        
        $setCacheDirtyMethod = $reflection->getMethod('setCacheDirty');
        $this->assertTrue($setCacheDirtyMethod->isPrivate());
    }

    /**
     * Test that EndpointMapper delete method exists and is public.
     *
     * @return void
     */
    public function testEndpointMapperDeleteMethod(): void
    {
        $reflection = new \ReflectionClass($this->endpointMapper);
        $deleteMethod = $reflection->getMethod('delete');
        
        $this->assertTrue($deleteMethod->isPublic());
        $this->assertEquals(1, $deleteMethod->getNumberOfParameters());
    }
}