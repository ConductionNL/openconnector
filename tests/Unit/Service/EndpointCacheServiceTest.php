<?php

declare(strict_types=1);

/**
 * EndpointCacheServiceTest
 *
 * Unit tests for the EndpointCacheService class to verify caching functionality,
 * endpoint retrieval, and cache management operations.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Service
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Service\EndpointCacheService;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * EndpointCacheService Test Suite
 *
 * Unit tests for endpoint caching and retrieval.
 */
class EndpointCacheServiceTest extends TestCase
{
    /** @var EndpointMapper|MockObject */
    private MockObject $endpointMapper;

    /** @var ICacheFactory|MockObject */
    private MockObject $cacheFactory;

    /** @var ICache|MockObject */
    private MockObject $cache;

    /** @var LoggerInterface|MockObject */
    private MockObject $logger;

    /** @var EndpointCacheService */
    private EndpointCacheService $endpointCacheService;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->endpointMapper = $this->createMock(EndpointMapper::class);
        $this->cacheFactory = $this->createMock(ICacheFactory::class);
        $this->cache = $this->createMock(ICache::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Configure cache factory to return mock cache
        $this->cacheFactory->expects($this->any())
            ->method('createDistributed')
            ->with('openconnector')
            ->willReturn($this->cache);

        $this->endpointCacheService = new EndpointCacheService(
            $this->cacheFactory,
            $this->endpointMapper,
            $this->logger
        );
    }

    /**
     * Test that EndpointCacheService can be instantiated.
     *
     * @return void
     */
    public function testEndpointCacheServiceInstantiation(): void
    {
        $this->assertInstanceOf(EndpointCacheService::class, $this->endpointCacheService);
    }

    /**
     * Test that EndpointCacheService has the expected methods.
     *
     * @return void
     */
    public function testEndpointCacheServiceHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->endpointCacheService, 'getAllEndpoints'));
        $this->assertTrue(method_exists($this->endpointCacheService, 'refreshCache'));
        $this->assertTrue(method_exists($this->endpointCacheService, 'clearCache'));
        $this->assertTrue(method_exists($this->endpointCacheService, 'getCacheStats'));
        $this->assertTrue(method_exists($this->endpointCacheService, 'findByPathRegex'));
    }

    /**
     * Test that EndpointCacheService has the expected properties.
     *
     * @return void
     */
    public function testEndpointCacheServiceHasExpectedProperties(): void
    {
        $reflection = new \ReflectionClass($this->endpointCacheService);
        
        $this->assertTrue($reflection->hasProperty('cacheFactory'));
        $this->assertTrue($reflection->hasProperty('endpointMapper'));
        $this->assertTrue($reflection->hasProperty('logger'));
        $this->assertTrue($reflection->hasProperty('memoryCache'));
    }

    /**
     * Test that EndpointCacheService properties are readonly.
     *
     * @return void
     */
    public function testEndpointCacheServicePropertiesAreReadonly(): void
    {
        $reflection = new \ReflectionClass($this->endpointCacheService);
        
        $cacheFactoryProperty = $reflection->getProperty('cacheFactory');
        $this->assertTrue($cacheFactoryProperty->isReadOnly());
        
        $endpointMapperProperty = $reflection->getProperty('endpointMapper');
        $this->assertTrue($endpointMapperProperty->isReadOnly());
        
        $loggerProperty = $reflection->getProperty('logger');
        $this->assertTrue($loggerProperty->isReadOnly());
    }

    /**
     * Test that EndpointCacheService constructor parameters are correct.
     *
     * @return void
     */
    public function testEndpointCacheServiceConstructor(): void
    {
        $reflection = new \ReflectionClass($this->endpointCacheService);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertEquals(3, $constructor->getNumberOfParameters());
        
        $parameters = $constructor->getParameters();
        $this->assertEquals('cacheFactory', $parameters[0]->getName());
        $this->assertEquals('endpointMapper', $parameters[1]->getName());
        $this->assertEquals('logger', $parameters[2]->getName());
    }

    /**
     * Test that EndpointCacheService has the expected cache key.
     *
     * @return void
     */
    public function testEndpointCacheServiceCacheKey(): void
    {
        // This test verifies that the service uses the correct cache key
        // by checking if the cache factory is called with the right parameter
        $this->cacheFactory->expects($this->atLeastOnce())
            ->method('createDistributed')
            ->with('openconnector');

        // Trigger a method that would use the cache
        $this->cache->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $this->endpointMapper->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $this->endpointCacheService->getAllEndpoints();
    }

    /**
     * Test that EndpointCacheService methods exist and are public.
     *
     * @return void
     */
    public function testEndpointCacheServiceMethodVisibility(): void
    {
        $reflection = new \ReflectionClass($this->endpointCacheService);
        
        $methods = [
            'getAllEndpoints',
            'refreshCache', 
            'clearCache',
            'getCacheStats',
            'findByPathRegex'
        ];
        
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method $methodName should be public");
        }
    }
}