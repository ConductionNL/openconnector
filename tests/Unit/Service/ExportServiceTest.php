<?php

namespace OCA\OpenConnector\Tests\Unit\Service;

use Exception;
use OCA\OpenConnector\Service\ExportService;
use OCA\OpenConnector\Service\ObjectService;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * ExportServiceTest
 *
 * Unit tests for the ExportService class.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Service
 * @author   Conduction <info@conduction.nl>
 * @copyright 2024 Conduction b.v.
 * @license  AGPL-3.0-or-later
 * @version  1.0.0
 * @link     https://github.com/ConductionNL/OpenConnector
 */
class ExportServiceTest extends TestCase
{
    private ExportService $exportService;
    private ObjectService $objectService;
    private IURLGenerator $urlGenerator;

    protected function setUp(): void
    {
        $this->objectService = $this->createMock(ObjectService::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);

        $this->exportService = new ExportService(
            $this->urlGenerator,
            $this->objectService
        );
    }

    /**
     * Test encode method with JSON format
     *
     * This test verifies that the encode method correctly
     * encodes data to JSON format.
     *
     * @covers ::encode
     * @return void
     */
    public function testEncodeWithJsonFormat(): void
    {
        $objectArray = [
            'name' => 'Test Object',
            'version' => '1.0.0',
            'data' => ['key' => 'value']
        ];

        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('encode');
        $method->setAccessible(true);

        $result = $method->invoke($this->exportService, $objectArray, 'application/json');

        $this->assertIsString($result);
        $this->assertStringContainsString('"name": "Test Object"', $result);
        $this->assertStringContainsString('"version": "1.0.0"', $result);
    }

    /**
     * Test encode method with YAML format
     *
     * This test verifies that the encode method correctly
     * encodes data to YAML format.
     *
     * @covers ::encode
     * @return void
     */
    public function testEncodeWithYamlFormat(): void
    {
        $objectArray = [
            'name' => 'Test Object',
            'version' => '1.0.0',
            'data' => ['key' => 'value']
        ];

        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('encode');
        $method->setAccessible(true);

        $result = $method->invoke($this->exportService, $objectArray, 'application/yaml');

        $this->assertIsString($result);
        $this->assertStringContainsString("name: 'Test Object'", $result);
        $this->assertStringContainsString('version: 1.0.0', $result);
    }

    /**
     * Test encode method with invalid data
     *
     * This test verifies that the encode method correctly
     * handles invalid data that cannot be encoded.
     *
     * @covers ::encode
     * @return void
     */
    public function testEncodeWithInvalidData(): void
    {
        // Create data that cannot be JSON encoded (circular reference)
        $objectArray = [];
        $objectArray['self'] = &$objectArray;

        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('encode');
        $method->setAccessible(true);

        $result = $method->invoke($this->exportService, $objectArray, 'application/json');

        $this->assertNull($result);
    }

    /**
     * Test encode method with default format
     *
     * This test verifies that the encode method correctly
     * handles unspecified format by defaulting to JSON.
     *
     * @covers ::encode
     * @return void
     */
    public function testEncodeWithDefaultFormat(): void
    {
        $objectArray = [
            'name' => 'Test Object',
            'version' => '1.0.0'
        ];

        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('encode');
        $method->setAccessible(true);

        $result = $method->invoke($this->exportService, $objectArray, null);

        $this->assertIsString($result);
        $this->assertStringContainsString('"name": "Test Object"', $result);
    }

    /**
     * Test prepareObject method with existing reference
     *
     * This test verifies that the prepareObject method correctly
     * prepares an object with an existing reference.
     *
     * @covers ::prepareObject
     * @return void
     */
    public function testPrepareObjectWithExistingReference(): void
    {
        $objectType = 'test';
        $mapper = $this->createMock(\stdClass::class);
        
        // Create an anonymous class that implements the required methods
        $object = new class extends Entity {
            public function getId(): int { return 1; }
            public function jsonSerialize(): array { 
                return [
                    'id' => 1,
                    'name' => 'Test Object',
                    'version' => '1.0.0',
                    'reference' => 'https://example.com/test/1'
                ]; 
            }
        };

        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('prepareObject');
        $method->setAccessible(true);

        $result = $method->invoke($this->exportService, $objectType, $mapper, $object);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('@context', $result);
        $this->assertArrayHasKey('@type', $result);
        $this->assertArrayHasKey('@id', $result);
        $this->assertEquals('test', $result['@type']);
        $this->assertEquals('https://example.com/test/1', $result['@id']);
        $this->assertEquals('Test Object', $result['name']);
        // When there's an existing reference, the id field is not removed
        $this->assertArrayHasKey('id', $result);
    }
}
