<?php

declare(strict_types=1);

/**
 * EndpointHandlerTest
 *
 * Unit tests for the EndpointHandler class to verify configuration
 * export and import functionality for endpoints.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Service\ConfigurationHandlers
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service\ConfigurationHandlers;

use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Service\ConfigurationHandlers\EndpointHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * EndpointHandler Test Suite
 *
 * Unit tests for endpoint configuration export and import.
 */
class EndpointHandlerTest extends TestCase
{
    /** @var EndpointMapper|MockObject */
    private MockObject $endpointMapper;

    /** @var EndpointHandler */
    private EndpointHandler $endpointHandler;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->endpointMapper = $this->createMock(EndpointMapper::class);
        $this->endpointHandler = new EndpointHandler($this->endpointMapper);
    }

    /**
     * Test that EndpointHandler can be instantiated.
     *
     * @return void
     */
    public function testEndpointHandlerInstantiation(): void
    {
        $this->assertInstanceOf(EndpointHandler::class, $this->endpointHandler);
    }

    /**
     * Test that EndpointHandler has the expected methods.
     *
     * @return void
     */
    public function testEndpointHandlerHasExpectedMethods(): void
    {
        $this->assertTrue(method_exists($this->endpointHandler, 'export'));
        $this->assertTrue(method_exists($this->endpointHandler, 'import'));
    }

    /**
     * Test that EndpointHandler has the expected properties.
     *
     * @return void
     */
    public function testEndpointHandlerHasExpectedProperties(): void
    {
        $reflection = new \ReflectionClass($this->endpointHandler);
        
        $this->assertTrue($reflection->hasProperty('endpointMapper'));
    }

    /**
     * Test that EndpointHandler constructor parameters are correct.
     *
     * @return void
     */
    public function testEndpointHandlerConstructor(): void
    {
        $reflection = new \ReflectionClass($this->endpointHandler);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertEquals(1, $constructor->getNumberOfParameters());
        
        $parameters = $constructor->getParameters();
        $this->assertEquals('endpointMapper', $parameters[0]->getName());
    }

    /**
     * Test that EndpointHandler methods exist and are public.
     *
     * @return void
     */
    public function testEndpointHandlerMethodVisibility(): void
    {
        $reflection = new \ReflectionClass($this->endpointHandler);
        
        $methods = [
            'export',
            'import'
        ];
        
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method $methodName should be public");
        }
    }

    /**
     * Test that EndpointHandler has the expected method signatures.
     *
     * @return void
     */
    public function testEndpointHandlerMethodSignatures(): void
    {
        $reflection = new \ReflectionClass($this->endpointHandler);
        
        $exportMethod = $reflection->getMethod('export');
        $this->assertEquals(0, $exportMethod->getNumberOfParameters());
        
        $importMethod = $reflection->getMethod('import');
        $this->assertEquals(3, $importMethod->getNumberOfParameters());
        
        $importParameters = $importMethod->getParameters();
        $this->assertEquals('endpointData', $importParameters[0]->getName());
        $this->assertEquals('mappings', $importParameters[1]->getName());
        $this->assertEquals('mappingIds', $importParameters[2]->getName());
    }
}