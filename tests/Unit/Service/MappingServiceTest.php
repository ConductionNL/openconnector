<?php

declare(strict_types=1);

/**
 * MappingServiceTest
 *
 * Comprehensive unit tests for the MappingService class to verify data transformation,
 * array encoding, type casting, and coordinate parsing functionality.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Service
 * @author    OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Twig\MappingExtension;
use OCA\OpenConnector\Twig\MappingRuntimeLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Exception;

/**
 * Mapping Service Test Suite
 *
 * Comprehensive unit tests for data mapping, transformation, and encoding functionality.
 * This test class validates the core mapping engine, type casting operations,
 * array key encoding, and coordinate string parsing.
 *
 * @coversDefaultClass MappingService
 */
class MappingServiceTest extends TestCase
{
    /**
     * The MappingService instance being tested
     *
     * @var MappingService
     */
    private MappingService $mappingService;

    /**
     * Mock mapping mapper
     *
     * @var MockObject|MappingMapper
     */
    private MockObject $mappingMapper;

    /**
     * Mock Twig environment
     *
     * @var MockObject|Environment
     */
    private MockObject $twigEnvironment;

    /**
     * Set up test environment before each test
     *
     * This method initializes the MappingService with mocked dependencies
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->mappingMapper = $this->createMock(MappingMapper::class);
        
        // Create a real ArrayLoader for Twig
        $loader = new ArrayLoader();
        
        // Create the service with real Twig environment
        $this->mappingService = new MappingService($loader, $this->mappingMapper);
    }

    /**
     * Test encoding array keys with dot replacement
     *
     * This test verifies that the encodeArrayKeys method correctly replaces
     * specified characters in array keys with replacement characters.
     *
     * @covers ::encodeArrayKeys
     * @return void
     */
    public function testEncodeArrayKeysWithDotReplacement(): void
    {
        $input = [
            'user.name' => 'John Doe',
            'user.email' => 'john@example.com',
            'settings.notifications.enabled' => true,
            'nested' => [
                'deep.key' => 'value',
                'normal' => 'data'
            ]
        ];

        $expected = [
            'user&#46;name' => 'John Doe',
            'user&#46;email' => 'john@example.com',
            'settings&#46;notifications&#46;enabled' => true,
            'nested' => [
                'deep&#46;key' => 'value',
                'normal' => 'data'
            ]
        ];

        $result = $this->mappingService->encodeArrayKeys($input, '.', '&#46;');
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test encoding array keys with different replacement characters
     *
     * This test verifies that the encodeArrayKeys method works with various
     * replacement characters and handles edge cases.
     *
     * @covers ::encodeArrayKeys
     * @return void
     */
    public function testEncodeArrayKeysWithDifferentReplacements(): void
    {
        $input = [
            'user-name' => 'John Doe',
            'user_email' => 'john@example.com',
            'settings:notifications' => true
        ];

        $expected = [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            'settings:notifications' => true
        ];

        $result = $this->mappingService->encodeArrayKeys($input, '-', '_');
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test encoding array keys with empty arrays
     *
     * This test verifies that the encodeArrayKeys method handles empty arrays
     * and nested empty arrays correctly.
     *
     * @covers ::encodeArrayKeys
     * @return void
     */
    public function testEncodeArrayKeysWithEmptyArrays(): void
    {
        $input = [
            'empty' => [],
            'nested' => [
                'deep' => []
            ]
        ];

        $expected = [
            'empty' => [],
            'nested' => [
                'deep' => []
            ]
        ];

        $result = $this->mappingService->encodeArrayKeys($input, '.', '&#46;');
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test basic mapping execution with simple input
     *
     * This test verifies that the executeMapping method correctly transforms
     * input data according to mapping configuration.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithSimpleInput(): void
    {
        $mapping = new Mapping();
        $mapping->setMapping([
            'name' => 'user.name',
            'email' => 'user.email',
            'status' => 'active'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([]);
        $mapping->setName('test-mapping');
        $mapping->setPassThrough(false);

        $input = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test mapping execution with pass-through enabled
     *
     * This test verifies that the executeMapping method correctly handles
     * pass-through mode where the original input structure is preserved.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithPassThrough(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(true);
        $mapping->setMapping([
            'displayName' => 'user.name',
            'emailAddress' => 'user.email'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([]);
        $mapping->setName('test-mapping');

        $input = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'settings' => [
                'theme' => 'dark'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'displayName' => 'John Doe',
            'emailAddress' => 'john@example.com',
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'settings' => [
                'theme' => 'dark'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test mapping execution with list processing
     *
     * This test verifies that the executeMapping method correctly processes
     * lists of items and applies mapping to each item.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithList(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'name' => 'user.name',
            'email' => 'user.email'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([]);
        $mapping->setName('test-mapping');

        $input = [
            'listInput' => [
                ['user' => ['name' => 'John', 'email' => 'john@example.com']],
                ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']]
            ],
            'extra' => 'data'
        ];

        $result = $this->mappingService->executeMapping($mapping, $input, true);

        $expected = [
            0 => [
                'name' => 'John',
                'email' => 'john@example.com'
            ],
            1 => [
                'name' => 'Jane',
                'email' => 'jane@example.com'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test mapping execution with unset operations
     *
     * This test verifies that the executeMapping method correctly removes
     * specified keys from the output.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithUnset(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(true);
        $mapping->setMapping([
            'name' => 'user.name',
            'email' => 'user.email'
        ]);
        $mapping->setUnset(['user', 'settings']);
        $mapping->setCast([]);
        $mapping->setName('test-mapping');

        $input = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'settings' => [
                'theme' => 'dark'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test coordinate string to array conversion
     *
     * This test verifies that the coordinateStringToArray method correctly
     * parses coordinate strings into structured arrays.
     *
     * @covers ::coordinateStringToArray
     * @return void
     */
    public function testCoordinateStringToArray(): void
    {
        $coordinates = "52.3676 4.9041 52.3677 4.9042 52.3678 4.9043";
        
        $result = $this->mappingService->coordinateStringToArray($coordinates);
        
        $expected = [
            [52.3676, 4.9041],
            [52.3677, 4.9042],
            [52.3678, 4.9043]
        ];
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test coordinate string to array with single point
     *
     * This test verifies that the coordinateStringToArray method correctly
     * handles single coordinate points.
     *
     * @covers ::coordinateStringToArray
     * @return void
     */
    public function testCoordinateStringToArrayWithSinglePoint(): void
    {
        $coordinates = "52.3676 4.9041";
        
        $result = $this->mappingService->coordinateStringToArray($coordinates);
        
        $expected = [52.3676, 4.9041];
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test coordinate string to array with empty string
     *
     * This test verifies that the coordinateStringToArray method correctly
     * handles empty coordinate strings.
     *
     * @covers ::coordinateStringToArray
     * @return void
     */
    public function testCoordinateStringToArrayWithEmptyString(): void
    {
        $coordinates = "";
        
        $result = $this->mappingService->coordinateStringToArray($coordinates);
        
        $expected = [''];
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test mapping execution with type casting
     *
     * This test verifies that the executeMapping method correctly applies
     * type casting operations to mapped values.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithTypeCasting(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'name' => 'user.name',
            'age' => 'user.age',
            'active' => 'user.active',
            'score' => 'user.score'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'age' => 'int',
            'active' => 'bool',
            'score' => 'float'
        ]);
        $mapping->setName('test-mapping');

        $input = [
            'user' => [
                'name' => 'John Doe',
                'age' => '25',
                'active' => '1',
                'score' => '95.5'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'name' => 'John Doe',
            'age' => 25,
            'active' => true,
            'score' => 95.5
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test mapping execution with complex casting operations
     *
     * This test verifies that the executeMapping method correctly handles
     * complex casting operations like unsetIfValue and setNullIfValue.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithComplexCasting(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'name' => 'user.name',
            'email' => 'user.email',
            'status' => 'user.status',
            'description' => 'user.description'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'status' => 'unsetIfValue==inactive',
            'description' => 'setNullIfValue=='
        ]);
        $mapping->setName('test-mapping');

        $input = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'status' => 'inactive',
                'description' => ''
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => null
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test mapping execution with Twig template rendering
     *
     * This test verifies that the executeMapping method correctly renders
     * Twig templates in mapping values.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithTwigTemplates(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'fullName' => '{{ user.firstName }} {{ user.lastName }}',
            'greeting' => 'Hello {{ user.firstName }}!',
            'profile' => '{{ user.firstName|lower }}.profile'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([]);
        $mapping->setName('test-mapping');

        $input = [
            'user' => [
                'firstName' => 'John',
                'lastName' => 'Doe'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'fullName' => 'John Doe',
            'greeting' => 'Hello John!',
            'profile' => 'john.profile'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test mapping execution with root level object handling
     *
     * This test verifies that the executeMapping method correctly handles
     * root level objects using the '#' key.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithRootLevelObject(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            '#' => 'user'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([]);
        $mapping->setName('test-mapping');

        $input = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getMapping method
     *
     * This test verifies that the getMapping method correctly delegates
     * to the mapping mapper.
     *
     * @covers ::getMapping
     * @return void
     */
    public function testGetMapping(): void
    {
        $mappingId = 'test-mapping-id';
        $expectedMapping = $this->createMock(Mapping::class);
        
        $this->mappingMapper->expects($this->once())
            ->method('find')
            ->with($mappingId)
            ->willReturn($expectedMapping);

        $result = $this->mappingService->getMapping($mappingId);
        
        $this->assertSame($expectedMapping, $result);
    }

    /**
     * Test getMappings method
     *
     * This test verifies that the getMappings method correctly delegates
     * to the mapping mapper.
     *
     * @covers ::getMappings
     * @return void
     */
    public function testGetMappings(): void
    {
        $expectedMappings = [
            $this->createMock(Mapping::class),
            $this->createMock(Mapping::class)
        ];
        
        $this->mappingMapper->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedMappings);

        $result = $this->mappingService->getMappings();
        
        $this->assertSame($expectedMappings, $result);
    }
}
