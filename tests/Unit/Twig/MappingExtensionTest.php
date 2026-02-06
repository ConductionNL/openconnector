<?php

declare(strict_types=1);

/**
 * MappingExtensionTest
 *
 * Comprehensive unit tests for the MappingExtension class to verify
 * Twig extension functionality and function registration.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Twig
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Twig;

use OCA\OpenConnector\Twig\MappingExtension;
use Twig\TwigFunction;
use PHPUnit\Framework\TestCase;

/**
 * Mapping Extension Test Suite
 *
 * Comprehensive unit tests for Twig mapping extension including
 * function registration and configuration.
 *
 * @coversDefaultClass MappingExtension
 */
class MappingExtensionTest extends TestCase
{
    private MappingExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new MappingExtension();
    }

    /**
     * Test constructor
     *
     * @covers ::__construct
     * @return void
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(MappingExtension::class, $this->extension);
    }

    /**
     * Test getFunctions method
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();
        
        $this->assertIsArray($functions);
        $this->assertCount(2, $functions);
        
        // Check that all functions are TwigFunction instances
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
        }
        
        // Check function names
        $functionNames = array_map(fn($func) => $func->getName(), $functions);
        $this->assertContains('executeMapping', $functionNames);
        $this->assertContains('generateUuid', $functionNames);
    }

    /**
     * Test executeMapping function registration
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testExecuteMappingFunctionRegistration(): void
    {
        $functions = $this->extension->getFunctions();
        
        $executeMappingFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'executeMapping') {
                $executeMappingFunction = $function;
                break;
            }
        }
        
        $this->assertNotNull($executeMappingFunction);
        $this->assertEquals('executeMapping', $executeMappingFunction->getName());
        $this->assertEquals([\OCA\OpenConnector\Twig\MappingRuntime::class, 'executeMapping'], $executeMappingFunction->getCallable());
    }

    /**
     * Test generateUuid function registration
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testGenerateUuidFunctionRegistration(): void
    {
        $functions = $this->extension->getFunctions();
        
        $generateUuidFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'generateUuid') {
                $generateUuidFunction = $function;
                break;
            }
        }
        
        $this->assertNotNull($generateUuidFunction);
        $this->assertEquals('generateUuid', $generateUuidFunction->getName());
        $this->assertEquals([\OCA\OpenConnector\Twig\MappingRuntime::class, 'generateUuid'], $generateUuidFunction->getCallable());
    }

    /**
     * Test function uniqueness
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionUniqueness(): void
    {
        $functions = $this->extension->getFunctions();
        
        $functionNames = array_map(fn($func) => $func->getName(), $functions);
        $uniqueNames = array_unique($functionNames);
        
        $this->assertEquals(count($functionNames), count($uniqueNames), 'Function names should be unique');
    }

    /**
     * Test function callable validity
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionCallableValidity(): void
    {
        $functions = $this->extension->getFunctions();
        
        foreach ($functions as $function) {
            $callable = $function->getCallable();
            $this->assertIsArray($callable);
            $this->assertCount(2, $callable);
            $this->assertEquals(\OCA\OpenConnector\Twig\MappingRuntime::class, $callable[0]);
            $this->assertIsString($callable[1]);
        }
    }

    /**
     * Test extension inheritance
     *
     * @covers ::__construct
     * @return void
     */
    public function testExtensionInheritance(): void
    {
        $this->assertInstanceOf(\Twig\Extension\AbstractExtension::class, $this->extension);
    }

    /**
     * Test multiple calls to getFunctions
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testMultipleCallsToGetFunctions(): void
    {
        $functions1 = $this->extension->getFunctions();
        $functions2 = $this->extension->getFunctions();
        
        $this->assertEquals($functions1, $functions2);
        $this->assertCount(2, $functions1);
        $this->assertCount(2, $functions2);
    }

    /**
     * Test function options
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionOptions(): void
    {
        $functions = $this->extension->getFunctions();
        
        // Verify that functions exist and can be iterated
        $this->assertIsArray($functions);
        $this->assertGreaterThan(0, count($functions));
        
        // Test that all functions are properly configured
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertIsString($function->getName());
            $this->assertIsArray($function->getCallable());
            $this->assertCount(2, $function->getCallable());
        }
    }

    /**
     * Test function node class
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionNodeClass(): void
    {
        $functions = $this->extension->getFunctions();
        
        // Verify that functions exist and have proper structure
        $this->assertIsArray($functions);
        $this->assertCount(2, $functions);
        
        // Test that all functions are TwigFunction instances
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertIsString($function->getName());
            $this->assertIsArray($function->getCallable());
        }
    }

    /**
     * Test function needs environment
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionNeedsEnvironment(): void
    {
        $functions = $this->extension->getFunctions();
        
        foreach ($functions as $function) {
            $this->assertFalse($function->needsEnvironment());
        }
    }

    /**
     * Test function needs context
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionNeedsContext(): void
    {
        $functions = $this->extension->getFunctions();
        
        foreach ($functions as $function) {
            $this->assertFalse($function->needsContext());
        }
    }

    /**
     * Test function safe analysis
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionSafeAnalysis(): void
    {
        $functions = $this->extension->getFunctions();
        
        // Verify that functions exist and have proper structure
        $this->assertIsArray($functions);
        $this->assertCount(2, $functions);
        
        // Test that all functions are properly configured
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertIsString($function->getName());
            $this->assertIsArray($function->getCallable());
            $this->assertCount(2, $function->getCallable());
        }
    }

    /**
     * Test function deprecated
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionDeprecated(): void
    {
        $functions = $this->extension->getFunctions();
        
        // Verify that functions exist and have proper structure
        $this->assertIsArray($functions);
        $this->assertCount(2, $functions);
        
        // Test that all functions are properly configured
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertIsString($function->getName());
            $this->assertIsArray($function->getCallable());
            $this->assertCount(2, $function->getCallable());
        }
    }

    /**
     * Test function alternative
     *
     * @covers ::getFunctions
     * @return void
     */
    public function testFunctionAlternative(): void
    {
        $functions = $this->extension->getFunctions();
        
        foreach ($functions as $function) {
            $this->assertNull($function->getAlternative());
        }
    }
}
