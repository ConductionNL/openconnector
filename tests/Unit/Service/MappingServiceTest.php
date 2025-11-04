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
 * @author    Conduction <info@conduction.nl>
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
     * Test all basic type casting operations
     *
     * This test verifies that all basic type casting operations work correctly:
     * string, bool, boolean, ?bool, ?boolean, int, integer, float, array
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithAllBasicTypeCasts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'string_val' => 'data.string_val',
            'bool_true' => 'data.bool_true',
            'bool_false' => 'data.bool_false',
            'nullable_bool' => 'data.nullable_bool',
            'int_val' => 'data.int_val',
            'float_val' => 'data.float_val',
            'array_val' => 'data.array_val'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'string_val' => 'string',
            'bool_true' => 'bool',
            'bool_false' => 'boolean',
            'nullable_bool' => '?bool',
            'int_val' => 'int',
            'float_val' => 'float',
            'array_val' => 'array'
        ]);
        $mapping->setName('test-basic-casts');

        $input = [
            'data' => [
                'string_val' => 123,
                'bool_true' => 'true',
                'bool_false' => '0',
                'nullable_bool' => null,
                'int_val' => '42',
                'float_val' => '3.14',
                'array_val' => 'single_value'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'string_val' => '123',
            'bool_true' => true,
            'bool_false' => false,
            'nullable_bool' => null,
            'int_val' => 42,
            'float_val' => 3.14,
            'array_val' => ['single_value']
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test URL encoding and decoding casting operations
     *
     * This test verifies that URL encoding and decoding casting operations work correctly:
     * url, urlDecode, rawurl, rawurlDecode
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithUrlCasts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'url_encoded' => 'data.url_encoded',
            'url_decoded' => 'data.url_decoded',
            'raw_url_encoded' => 'data.raw_url_encoded',
            'raw_url_decoded' => 'data.raw_url_decoded'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'url_encoded' => 'url',
            'url_decoded' => 'urlDecode',
            'raw_url_encoded' => 'rawurl',
            'raw_url_decoded' => 'rawurlDecode'
        ]);
        $mapping->setName('test-url-casts');

        $input = [
            'data' => [
                'url_encoded' => 'https://example.com/path with spaces',
                'url_decoded' => 'https%3A%2F%2Fexample.com%2Fpath%20with%20spaces',
                'raw_url_encoded' => 'https://example.com/path with spaces',
                'raw_url_decoded' => 'https%3A%2F%2Fexample.com%2Fpath%20with%20spaces'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'url_encoded' => 'https%3A%2F%2Fexample.com%2Fpath+with+spaces',
            'url_decoded' => 'https://example.com/path with spaces',
            'raw_url_encoded' => 'https%3A%2F%2Fexample.com%2Fpath%20with%20spaces',
            'raw_url_decoded' => 'https://example.com/path with spaces'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test HTML encoding and decoding casting operations
     *
     * This test verifies that HTML encoding and decoding casting operations work correctly:
     * html, htmlDecode
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithHtmlCasts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'html_encoded' => 'data.html_encoded',
            'html_decoded' => 'data.html_decoded'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'html_encoded' => 'html',
            'html_decoded' => 'htmlDecode'
        ]);
        $mapping->setName('test-html-casts');

        $input = [
            'data' => [
                'html_encoded' => '<script>alert("test")</script>',
                'html_decoded' => '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'html_encoded' => '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;',
            'html_decoded' => '<script>alert("test")</script>'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test Base64 encoding and decoding casting operations
     *
     * This test verifies that Base64 encoding and decoding casting operations work correctly:
     * base64, base64Decode
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithBase64Casts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'base64_encoded' => 'data.base64_encoded',
            'base64_decoded' => 'data.base64_decoded'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'base64_encoded' => 'base64',
            'base64_decoded' => 'base64Decode'
        ]);
        $mapping->setName('test-base64-casts');

        $input = [
            'data' => [
                'base64_encoded' => 'Hello World',
                'base64_decoded' => 'SGVsbG8gV29ybGQ='
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'base64_encoded' => 'SGVsbG8gV29ybGQ=',
            'base64_decoded' => 'Hello World'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test JSON encoding and decoding casting operations
     *
     * This test verifies that JSON encoding and decoding casting operations work correctly:
     * json, jsonToArray
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithJsonCasts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'json_encoded' => 'data.json_encoded',
            'json_decoded' => 'data.json_decoded'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'json_encoded' => 'json',
            'json_decoded' => 'jsonToArray'
        ]);
        $mapping->setName('test-json-casts');

        $input = [
            'data' => [
                'json_encoded' => ['name' => 'John', 'age' => 30],
                'json_decoded' => '{"name":"John","age":30}'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'json_encoded' => '{"name":"John","age":30}',
            'json_decoded' => ['name' => 'John', 'age' => 30]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test UTF8 and special string casting operations
     *
     * This test verifies that UTF8 and special string casting operations work correctly:
     * utf8, nullStringToNull
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithStringCasts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'utf8_converted' => 'data.utf8_converted',
            'null_string' => 'data.null_string',
            'normal_string' => 'data.normal_string'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'utf8_converted' => 'utf8',
            'null_string' => 'nullStringToNull',
            'normal_string' => 'nullStringToNull'
        ]);
        $mapping->setName('test-string-casts');

        $input = [
            'data' => [
                'utf8_converted' => 'café résumé',
                'null_string' => 'null',
                'normal_string' => 'not null'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'utf8_converted' => 'cafe resume',
            'null_string' => null,
            'normal_string' => 'not null'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test coordinate and money casting operations
     *
     * This test verifies that coordinate and money casting operations work correctly:
     * coordinateStringToArray, moneyStringToInt, intToMoneyString
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithSpecialCasts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'coordinates' => 'data.coordinates',
            'money_to_int' => 'data.money_to_int',
            'int_to_money' => 'data.int_to_money'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'coordinates' => 'coordinateStringToArray',
            'money_to_int' => 'moneyStringToInt',
            'int_to_money' => 'intToMoneyString'
        ]);
        $mapping->setName('test-special-casts');

        $input = [
            'data' => [
                'coordinates' => '52.3676 4.9041',
                'money_to_int' => '1.234,56',
                'int_to_money' => 123456
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'coordinates' => ['52.3676', '4.9041'],
            'money_to_int' => 123456,
            'int_to_money' => '1.234,56'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test conditional casting operations with unsetIfValue and setNullIfValue
     *
     * This test verifies that unsetIfValue and setNullIfValue casting operations work correctly.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithUnsetAndNullCasts(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'unset_field' => 'data.unset_field',
            'null_field' => 'data.null_field',
            'keep_field' => 'data.keep_field'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'unset_field' => 'unsetIfValue==inactive',
            'null_field' => 'setNullIfValue==',
            'keep_field' => 'setNullIfValue==remove_me'
        ]);
        $mapping->setName('test-unset-null-casts');

        $input = [
            'data' => [
                'unset_field' => 'inactive',
                'null_field' => '',
                'keep_field' => 'keep_this_value'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'null_field' => null,
            'keep_field' => 'keep_this_value'
            // unset_field should be removed because value matches 'inactive'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test countValue casting operation with various countable types
     *
     * This test verifies that countValue casting operation works correctly
     * with arrays and other countable types.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithCountValueCast(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'array_count' => 'data.dummy1',
            'nested_count' => 'data.dummy2',
            'empty_count' => 'data.dummy3',
            'items' => 'data.items',
            'nested_items' => 'data.nested_items',
            'empty_array' => 'data.empty_array'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'array_count' => 'countValue:items',
            'nested_count' => 'countValue:nested_items',
            'empty_count' => 'countValue:empty_array'
        ]);
        $mapping->setName('test-count-cast');

        $input = [
            'data' => [
                'dummy1' => 'placeholder1',
                'dummy2' => 'placeholder2',
                'dummy3' => 'placeholder3',
                'items' => ['item1', 'item2', 'item3', 'item4', 'item5'],
                'nested_items' => [
                    'group1' => ['a', 'b', 'c'],
                    'group2' => ['x', 'y'],
                    'group3' => []
                ],
                'empty_array' => []
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'array_count' => 5, // Count of array elements
            'nested_count' => 3, // Count of nested array keys
            'empty_count' => 0, // Count of empty array
            'items' => ['item1', 'item2', 'item3', 'item4', 'item5'],
            'nested_items' => [
                'group1' => ['a', 'b', 'c'],
                'group2' => ['x', 'y'],
                'group3' => []
            ],
            'empty_array' => []
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test keyCantBeValue casting operation
     *
     * This test verifies that keyCantBeValue casting operation works correctly.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithKeyCantBeValueCast(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'field_name' => 'data.field_name',
            'other_field' => 'data.other_field'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'field_name' => 'keyCantBeValue',
            'other_field' => 'keyCantBeValue'
        ]);
        $mapping->setName('test-key-cant-be-value-cast');

        $input = [
            'data' => [
                'field_name' => 'field_name', // This should be removed because key equals value
                'other_field' => 'different_value' // This should be kept because key doesn't equal value
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'other_field' => 'different_value'
            // field_name should be removed because key equals value
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test integer and ?boolean aliases
     *
     * This test verifies that 'integer' (alias for 'int') and '?boolean' (alias for '?bool') work correctly.
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithTypeAliases(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'int_val' => 'data.int_val',
            'nullable_bool' => 'data.nullable_bool'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'int_val' => 'integer',
            'nullable_bool' => '?boolean'
        ]);
        $mapping->setName('test-type-aliases');

        $input = [
            'data' => [
                'int_val' => '42',
                'nullable_bool' => null
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'int_val' => 42,
            'nullable_bool' => null
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test date casting operation
     *
     * This test verifies that date casting operation works correctly:
     * date
     *
     * @covers ::executeMapping
     * @return void
     */
    public function testExecuteMappingWithDateCast(): void
    {
        $mapping = new Mapping();
        $mapping->setPassThrough(false);
        $mapping->setMapping([
            'date_field' => 'data.date_field'
        ]);
        $mapping->setUnset([]);
        $mapping->setCast([
            'date_field' => 'date'
        ]);
        $mapping->setName('test-date-cast');

        $input = [
            'data' => [
                'date_field' => 'Y-m-d'
            ]
        ];

        $result = $this->mappingService->executeMapping($mapping, $input);

        $expected = [
            'date_field' => date('Y-m-d')
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
