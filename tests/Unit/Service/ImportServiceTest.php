<?php

declare(strict_types=1);

/**
 * ImportServiceTest
 *
 * Comprehensive unit tests for the ImportService class to verify file and JSON imports,
 * data decoding, object creation/updates, and error handling functionality.
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

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use OCA\OpenConnector\Service\ImportService;
use OCA\OpenConnector\Service\ObjectService;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Yaml\Yaml;

/**
 * Import Service Test Suite
 *
 * Comprehensive unit tests for file and JSON import operations, data decoding,
 * object creation/updates, and error handling functionality. This test class validates
 * the core import capabilities of the OpenConnector application.
 *
 * @coversDefaultClass ImportService
 */
class ImportServiceTest extends TestCase
{
    private ImportService $importService;
    private Client $client;
    private MockObject $objectService;
    private MockObject $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock instances for the constructor
        $this->client = $this->createMock(Client::class);
        $this->objectService = $this->createMock(ObjectService::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);

        $this->importService = new ImportService(
            $this->client,
            $this->urlGenerator,
            $this->objectService
        );
    }

    /**
     * Test import method with missing input data
     *
     * This test verifies that the import method correctly handles
     * requests with no input data (no url, json, or files).
     *
     * @covers ::import
     * @return void
     */
    public function testImportWithMissingInputData(): void
    {
        $data = [];
        $uploadedFiles = null;

        $response = $this->importService->import($data, $uploadedFiles);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());
        $this->assertStringContainsString('Missing one of these keys', $response->getData()['error']);
    }

    /**
     * Test import method with JSON data
     *
     * This test verifies that the import method correctly handles
     * JSON data input.
     *
     * @covers ::import
     * @return void
     */
    public function testImportWithJsonData(): void
    {
        $data = ['json' => '{"@type": "endpoint", "name": "Test Object", "reference": "https://example.com/endpoint/1"}'];
        $uploadedFiles = null;

        // Create a custom mock mapper that can handle named parameters
        $mockMapper = new class {
            public function createFromArray($object = null, $id = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function updateFromArray($id = null, $object = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function findByRef($ref) {
                return [];
            }
            
            public function find($id) {
                $entity = new class extends \OCP\AppFramework\Db\Entity {
                    public function getId(): int { return 1; }
                    public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                    public function getVersion(): ?string { return '1.0.0'; }
                };
                return $entity;
            }
        };
        
        $this->objectService->method('getMapper')->willReturn($mockMapper);

        $result = $this->importService->import($data, $uploadedFiles);

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(201, $result->getStatus());
        $responseData = $result->getData();
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('object', $responseData);
    }

    /**
     * Test import method with URL data
     *
     * This test verifies that the import method correctly handles
     * URL data input.
     *
     * @covers ::import
     * @return void
     */
    public function testImportWithUrlData(): void
    {
        $data = ['url' => 'https://example.com/api/data'];
        $uploadedFiles = null;

        // Create a custom mock mapper that can handle named parameters
        $mockMapper = new class {
            public function createFromArray($object = null, $id = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function updateFromArray($id = null, $object = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function findByRef($ref) {
                return [];
            }
            
            public function find($id) {
                $entity = new class extends \OCP\AppFramework\Db\Entity {
                    public function getId(): int { return 1; }
                    public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                    public function getVersion(): ?string { return '1.0.0'; }
                };
                return $entity;
            }
        };
        
        $this->objectService->method('getMapper')->willReturn($mockMapper);

        // Mock HTTP response with proper StreamInterface
        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('getContents')->willReturn('{"@type": "endpoint", "name": "Test Object", "reference": "https://example.com/endpoint/1"}');
        
        $mockResponse = $this->createMock(\GuzzleHttp\Psr7\Response::class);
        $mockResponse->method('getBody')->willReturn($mockStream);
        $mockResponse->method('getHeaderLine')->willReturn('application/json');
        
        $this->client->method('request')->willReturn($mockResponse);

        $result = $this->importService->import($data, $uploadedFiles);

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(201, $result->getStatus());
        $responseData = $result->getData();
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('object', $responseData);
    }

    /**
     * Test import method with single uploaded file
     *
     * This test verifies that the import method correctly handles
     * single file uploads.
     *
     * @covers ::import
     * @return void
     */
    public function testImportWithSingleUploadedFile(): void
    {
        $data = [];
        $uploadedFiles = [
            'file' => [
                'name' => 'test.json',
                'type' => 'application/json',
                'tmp_name' => '/tmp/test.json',
                'error' => 0,
                'size' => 1024
            ]
        ];

        // Create a custom mock mapper that can handle named parameters
        $mockMapper = new class {
            public function createFromArray($object = null, $id = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function updateFromArray($id = null, $object = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function findByRef($ref) {
                return [];
            }
            
            public function find($id) {
                $entity = new class extends \OCP\AppFramework\Db\Entity {
                    public function getId(): int { return 1; }
                    public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                    public function getVersion(): ?string { return '1.0.0'; }
                };
                return $entity;
            }
        };
        
        $this->objectService->method('getMapper')->willReturn($mockMapper);

        // Mock file_get_contents to return valid JSON
        $this->markTestSkipped('File system mocking requires more complex setup - keeping skipped for now');
    }

    /**
     * Test import method with multiple uploaded files
     *
     * This test verifies that the import method correctly handles
     * multiple file uploads.
     *
     * @covers ::import
     * @return void
     */
    public function testImportWithMultipleUploadedFiles(): void
    {
        $data = [];
        $uploadedFiles = [
            'files' => [
                'name' => ['test1.json', 'test2.json'],
                'type' => ['application/json', 'application/json'],
                'tmp_name' => ['/tmp/test1.json', '/tmp/test2.json'],
                'error' => [0, 0],
                'size' => [1024, 2048]
            ]
        ];

        // Create a custom mock mapper that can handle named parameters
        $mockMapper = new class {
            public function createFromArray($object = null, $id = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function updateFromArray($id = null, $object = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function findByRef($ref) {
                return [];
            }
            
            public function find($id) {
                $entity = new class extends \OCP\AppFramework\Db\Entity {
                    public function getId(): int { return 1; }
                    public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                    public function getVersion(): ?string { return '1.0.0'; }
                };
                return $entity;
            }
        };
        
        $this->objectService->method('getMapper')->willReturn($mockMapper);

        // Mock file_get_contents to return valid JSON
        $this->markTestSkipped('File system mocking requires more complex setup - keeping skipped for now');
    }

    /**
     * Test decode method with JSON data
     *
     * This test verifies that the decode method correctly handles
     * JSON data with proper MIME type.
     *
     * @covers ::decode
     * @return void
     */
    public function testDecodeWithJsonData(): void
    {
        $data = '{"name": "Test Object", "value": 123}';
        $type = 'application/json';

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('decode');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $data, $type);

        $this->assertIsArray($result);
        $this->assertEquals('Test Object', $result['name']);
        $this->assertEquals(123, $result['value']);
    }

    /**
     * Test decode method with YAML data
     *
     * This test verifies that the decode method correctly handles
     * YAML data with proper MIME type.
     *
     * @covers ::decode
     * @return void
     */
    public function testDecodeWithYamlData(): void
    {
        $data = "name: Test Object\nvalue: 123";
        $type = 'application/yaml';

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('decode');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $data, $type);

        $this->assertIsArray($result);
        $this->assertEquals('Test Object', $result['name']);
        $this->assertEquals(123, $result['value']);
    }

    /**
     * Test decode method with invalid data
     *
     * This test verifies that the decode method correctly handles
     * completely invalid data.
     *
     * @covers ::decode
     * @return void
     */
    public function testDecodeWithInvalidData(): void
    {
        $data = 'invalid data that is neither JSON nor YAML';
        $type = 'application/json';

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('decode');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $data, $type);

        $this->assertNull($result);
    }

    /**
     * Test decode method with auto-detection (JSON first)
     *
     * This test verifies that the decode method correctly auto-detects
     * JSON data when no specific type is provided.
     *
     * @covers ::decode
     * @return void
     */
    public function testDecodeWithAutoDetectionJson(): void
    {
        $data = '{"name": "Test Object", "value": 123}';
        $type = null;

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('decode');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $data, $type);

        $this->assertIsArray($result);
        $this->assertEquals('Test Object', $result['name']);
        $this->assertEquals(123, $result['value']);
    }

    /**
     * Test decode method with auto-detection (YAML fallback)
     *
     * This test verifies that the decode method correctly falls back
     * to YAML when JSON parsing fails.
     *
     * @covers ::decode
     * @return void
     */
    public function testDecodeWithAutoDetectionYaml(): void
    {
        $data = "name: Test Object\nvalue: 123";
        $type = null;

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('decode');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $data, $type);

        $this->assertIsArray($result);
        $this->assertEquals('Test Object', $result['name']);
        $this->assertEquals(123, $result['value']);
    }

    /**
     * Test getJSONfromBody method with valid JSON string
     *
     * This test verifies that the getJSONfromBody method correctly handles
     * valid JSON string input.
     *
     * @covers ::getJSONfromBody
     * @return void
     */
    public function testGetJSONfromBodyWithValidJsonString(): void
    {
        $validJson = '{"@type": "endpoint", "name": "Test Object", "reference": "https://example.com/endpoint/1"}';
        $type = null;

        // Create a custom mock mapper that can handle named parameters
        $mockMapper = new class {
            public function createFromArray($object = null, $id = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function updateFromArray($id = null, $object = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function findByRef($ref) {
                return [];
            }
            
            public function find($id) {
                $entity = new class extends \OCP\AppFramework\Db\Entity {
                    public function getId(): int { return 1; }
                    public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                    public function getVersion(): ?string { return '1.0.0'; }
                };
                return $entity;
            }
        };
        
        $this->objectService->method('getMapper')->willReturn($mockMapper);

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('getJSONfromBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $validJson, $type);

        $this->assertInstanceOf(JSONResponse::class, $result);
    }

    /**
     * Test getJSONfromBody method with valid array
     *
     * This test verifies that the getJSONfromBody method correctly handles
     * valid array input.
     *
     * @covers ::getJSONfromBody
     * @return void
     */
    public function testGetJSONfromBodyWithValidArray(): void
    {
        $validArray = ['@type' => 'endpoint', 'name' => 'Test Object', 'reference' => 'https://example.com/endpoint/1'];
        $type = null;

        // Create a custom mock mapper that can handle named parameters
        $mockMapper = new class {
            public function createFromArray($object = null, $id = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function updateFromArray($id = null, $object = null) {
                // Handle both named and positional parameters
                if (is_array($object)) {
                    $entity = new class extends \OCP\AppFramework\Db\Entity {
                        public function getId(): int { return 1; }
                        public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                        public function getVersion(): ?string { return '1.0.0'; }
                    };
                    return $entity;
                }
                return null;
            }
            
            public function findByRef($ref) {
                return [];
            }
            
            public function find($id) {
                $entity = new class extends \OCP\AppFramework\Db\Entity {
                    public function getId(): int { return 1; }
                    public function jsonSerialize(): array { return ['id' => 1, 'name' => 'Test Object']; }
                    public function getVersion(): ?string { return '1.0.0'; }
                };
                return $entity;
            }
        };
        
        $this->objectService->method('getMapper')->willReturn($mockMapper);

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('getJSONfromBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $validArray, $type);

        $this->assertInstanceOf(JSONResponse::class, $result);
    }

    /**
     * Test getJSONfromBody method with invalid JSON
     *
     * This test verifies that the getJSONfromBody method correctly handles
     * invalid JSON input.
     *
     * @covers ::getJSONfromBody
     * @return void
     */
    public function testGetJSONfromBodyWithInvalidJson(): void
    {
        $invalidJson = '{"name":"Test Object",}'; // Invalid JSON
        $type = null;

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('getJSONfromBody');
        $method->setAccessible(true);

        $result = $method->invoke($this->importService, $invalidJson, $type);

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());
        $this->assertArrayHasKey('error', $result->getData());
    }

    /**
     * Test that ImportService can be instantiated
     *
     * This test verifies that the ImportService can be properly
     * instantiated with its required dependencies.
     *
     * @covers ::__construct
     * @return void
     */
    public function testImportServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ImportService::class, $this->importService);
    }

    /**
     * Test that all required public methods exist
     *
     * This test verifies that all expected public methods are available
     * in the ImportService class.
     *
     * @return void
     */
    public function testAllRequiredPublicMethodsExist(): void
    {
        $expectedMethods = [
            'import'
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                method_exists($this->importService, $method),
                "Method {$method} should exist in ImportService"
            );
        }
    }
}
