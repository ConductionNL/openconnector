<?php

declare(strict_types=1);

/**
 * SearchServiceTest
 *
 * Comprehensive unit tests for the SearchService class to verify search functionality,
 * facet merging, query processing, and result aggregation capabilities.
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

use GuzzleHttp\Client;
use OCA\OpenConnector\Service\SearchService;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Search Service Test Suite
 *
 * Comprehensive unit tests for search functionality including facet merging,
 * query processing, and result aggregation. This test class validates the core
 * search capabilities of the OpenConnector application.
 *
 * @coversDefaultClass SearchService
 */
class SearchServiceTest extends TestCase
{
    private SearchService $searchService;
    private MockObject $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = $this->createMock(IURLGenerator::class);

        $this->searchService = new SearchService($this->urlGenerator);
    }

    /**
     * Test facet merging with overlapping data
     *
     * This test verifies that the search service correctly merges
     * facet aggregations with overlapping values.
     *
     * @covers ::mergeFacets
     * @return void
     */
    public function testMergeFacetsWithOverlappingData(): void
    {
        $existingAggregation = [
            ['_id' => 'category1', 'count' => 10],
            ['_id' => 'category2', 'count' => 5]
        ];

        $newAggregation = [
            ['_id' => 'category1', 'count' => 3],
            ['_id' => 'category3', 'count' => 7]
        ];

        $result = $this->searchService->mergeFacets($existingAggregation, $newAggregation);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Find the merged category1 entry
        $category1 = null;
        foreach ($result as $item) {
            if ($item['_id'] === 'category1') {
                $category1 = $item;
                break;
            }
        }
        
        $this->assertNotNull($category1);
        $this->assertEquals(13, $category1['count']); // 10 + 3
    }

    /**
     * Test facet merging with non-overlapping data
     *
     * This test verifies that the search service correctly merges
     * facet aggregations with completely different values.
     *
     * @covers ::mergeFacets
     * @return void
     */
    public function testMergeFacetsWithNonOverlappingData(): void
    {
        $existingAggregation = [
            ['_id' => 'category1', 'count' => 10],
            ['_id' => 'category2', 'count' => 5]
        ];

        $newAggregation = [
            ['_id' => 'category3', 'count' => 7],
            ['_id' => 'category4', 'count' => 2]
        ];

        $result = $this->searchService->mergeFacets($existingAggregation, $newAggregation);

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
    }

    /**
     * Test facet merging with empty arrays
     *
     * This test verifies that the search service handles
     * empty aggregation arrays correctly.
     *
     * @covers ::mergeFacets
     * @return void
     */
    public function testMergeFacetsWithEmptyArrays(): void
    {
        $existingAggregation = [];
        $newAggregation = [];

        $result = $this->searchService->mergeFacets($existingAggregation, $newAggregation);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Test facet merging with one empty array
     *
     * This test verifies that the search service handles
     * scenarios where one aggregation is empty.
     *
     * @covers ::mergeFacets
     * @return void
     */
    public function testMergeFacetsWithOneEmptyArray(): void
    {
        $existingAggregation = [
            ['_id' => 'category1', 'count' => 10]
        ];
        $newAggregation = [];

        $result = $this->searchService->mergeFacets($existingAggregation, $newAggregation);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('category1', $result[0]['_id']);
        $this->assertEquals(10, $result[0]['count']);
    }

    /**
     * Test search service constants
     *
     * This test verifies that the search service has the correct
     * constants defined for base object configuration.
     *
     * @covers SearchService::BASE_OBJECT
     * @return void
     */
    public function testSearchServiceConstants(): void
    {
        $this->assertIsArray(SearchService::BASE_OBJECT);
        $this->assertArrayHasKey('database', SearchService::BASE_OBJECT);
        $this->assertArrayHasKey('collection', SearchService::BASE_OBJECT);
        $this->assertEquals('objects', SearchService::BASE_OBJECT['database']);
        $this->assertEquals('json', SearchService::BASE_OBJECT['collection']);
    }

    /**
     * Test client initialization
     *
     * This test verifies that the search service correctly
     * initializes the HTTP client.
     *
     * @covers ::__construct
     * @return void
     */
    public function testClientInitialization(): void
    {
        $this->assertInstanceOf(Client::class, $this->searchService->client);
    }

    /**
     * Test MongoDB search filter creation
     *
     * This test verifies that the search service can create
     * proper MongoDB search filters from input parameters.
     *
     * @covers ::createMongoDBSearchFilter
     * @return void
     */
    public function testCreateMongoDBSearchFilterWithSearch(): void
    {
        $filters = [
            '_search' => 'test query',
            'category' => 'test',
            'status' => 'active'
        ];
        $fieldsToSearch = ['name', 'description', 'content'];

        $result = $this->searchService->createMongoDBSearchFilter($filters, $fieldsToSearch);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('$or', $result);
        $this->assertCount(3, $result['$or']); // One for each search field
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayNotHasKey('_search', $result); // Should be unset
    }

    /**
     * Test MongoDB search filter with null values
     *
     * This test verifies that the search service correctly handles
     * null value filters in MongoDB.
     *
     * @covers ::createMongoDBSearchFilter
     * @return void
     */
    public function testCreateMongoDBSearchFilterWithNullValues(): void
    {
        $filters = [
            'field1' => 'IS NULL',
            'field2' => 'IS NOT NULL',
            'field3' => 'normal value'
        ];
        $fieldsToSearch = ['name'];

        $result = $this->searchService->createMongoDBSearchFilter($filters, $fieldsToSearch);

        $this->assertIsArray($result);
        $this->assertEquals(['$eq' => null], $result['field1']);
        $this->assertEquals(['$ne' => null], $result['field2']);
        $this->assertEquals('normal value', $result['field3']);
    }

    /**
     * Test MySQL search conditions creation
     *
     * This test verifies that the search service can create
     * proper MySQL search conditions from input parameters.
     *
     * @covers ::createMySQLSearchConditions
     * @return void
     */
    public function testCreateMySQLSearchConditionsWithSearch(): void
    {
        $filters = [
            '_search' => 'test query',
            'category' => 'test'
        ];
        $fieldsToSearch = ['name', 'description'];

        $result = $this->searchService->createMySQLSearchConditions($filters, $fieldsToSearch);

        $this->assertIsArray($result);
        $this->assertCount(2, $result); // One for each search field
        $this->assertContains('LOWER(name) LIKE :search', $result);
        $this->assertContains('LOWER(description) LIKE :search', $result);
    }

    /**
     * Test MySQL search conditions without search
     *
     * This test verifies that the search service handles
     * filters without search parameters correctly.
     *
     * @covers ::createMySQLSearchConditions
     * @return void
     */
    public function testCreateMySQLSearchConditionsWithoutSearch(): void
    {
        $filters = [
            'category' => 'test',
            'status' => 'active'
        ];
        $fieldsToSearch = ['name', 'description'];

        $result = $this->searchService->createMySQLSearchConditions($filters, $fieldsToSearch);

        $this->assertIsArray($result);
        $this->assertCount(0, $result); // No search conditions when no _search
    }

    /**
     * Test MySQL search parameters creation
     *
     * This test verifies that the search service can create
     * proper MySQL search parameters from input filters.
     *
     * @covers ::createMySQLSearchParams
     * @return void
     */
    public function testCreateMySQLSearchParamsWithSearch(): void
    {
        $filters = [
            '_search' => 'Test Query',
            'category' => 'test'
        ];

        $result = $this->searchService->createMySQLSearchParams($filters);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('search', $result);
        $this->assertEquals('%test query%', $result['search']); // Should be lowercase with % wildcards
    }

    /**
     * Test MySQL search parameters without search
     *
     * This test verifies that the search service handles
     * filters without search parameters correctly.
     *
     * @covers ::createMySQLSearchParams
     * @return void
     */
    public function testCreateMySQLSearchParamsWithoutSearch(): void
    {
        $filters = [
            'category' => 'test',
            'status' => 'active'
        ];

        $result = $this->searchService->createMySQLSearchParams($filters);

        $this->assertIsArray($result);
        $this->assertCount(0, $result); // No search params when no _search
    }

    /**
     * Test MySQL sort creation
     *
     * This test verifies that the search service can create
     * proper MySQL sort arrays from input parameters.
     *
     * @covers ::createSortForMySQL
     * @return void
     */
    public function testCreateSortForMySQLWithOrder(): void
    {
        $filters = [
            '_order' => [
                'name' => 'ASC',
                'created' => 'DESC',
                'status' => 'asc' // Should be converted to uppercase
            ],
            'category' => 'test'
        ];

        $result = $this->searchService->createSortForMySQL($filters);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('ASC', $result['name']);
        $this->assertEquals('DESC', $result['created']);
        $this->assertEquals('ASC', $result['status']); // Should be converted to uppercase
    }

    /**
     * Test MySQL sort without order
     *
     * This test verifies that the search service handles
     * filters without order parameters correctly.
     *
     * @covers ::createSortForMySQL
     * @return void
     */
    public function testCreateSortForMySQLWithoutOrder(): void
    {
        $filters = [
            'category' => 'test',
            'status' => 'active'
        ];

        $result = $this->searchService->createSortForMySQL($filters);

        $this->assertIsArray($result);
        $this->assertCount(0, $result); // No sort when no _order
    }

    /**
     * Test MongoDB sort creation
     *
     * This test verifies that the search service can create
     * proper MongoDB sort arrays from input parameters.
     *
     * @covers ::createSortForMongoDB
     * @return void
     */
    public function testCreateSortForMongoDBWithOrder(): void
    {
        $filters = [
            '_order' => [
                'name' => 'ASC',
                'created' => 'DESC',
                'status' => 'asc' // Should be converted to uppercase
            ],
            'category' => 'test'
        ];

        $result = $this->searchService->createSortForMongoDB($filters);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals(1, $result['name']); // ASC = 1
        $this->assertEquals(-1, $result['created']); // DESC = -1
        $this->assertEquals(1, $result['status']); // Should be converted to uppercase and then to 1
    }

    /**
     * Test MongoDB sort without order
     *
     * This test verifies that the search service handles
     * filters without order parameters correctly.
     *
     * @covers ::createSortForMongoDB
     * @return void
     */
    public function testCreateSortForMongoDBWithoutOrder(): void
    {
        $filters = [
            'category' => 'test',
            'status' => 'active'
        ];

        $result = $this->searchService->createSortForMongoDB($filters);

        $this->assertIsArray($result);
        $this->assertCount(0, $result); // No sort when no _order
    }

    /**
     * Test special query parameters unsetting
     *
     * This test verifies that the search service correctly
     * unsets all parameters starting with underscore.
     *
     * @covers ::unsetSpecialQueryParams
     * @return void
     */
    public function testUnsetSpecialQueryParams(): void
    {
        $filters = [
            '_search' => 'test',
            '_order' => ['name' => 'ASC'],
            '_limit' => 10,
            'category' => 'test',
            'status' => 'active',
            '_page' => 1
        ];

        $result = $this->searchService->unsetSpecialQueryParams($filters);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('_search', $result);
        $this->assertArrayNotHasKey('_order', $result);
        $this->assertArrayNotHasKey('_limit', $result);
        $this->assertArrayNotHasKey('_page', $result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('test', $result['category']);
        $this->assertEquals('active', $result['status']);
    }

    /**
     * Test query string parsing
     *
     * This test verifies that the search service can correctly
     * parse query strings into parameter arrays.
     *
     * @covers ::parseQueryString
     * @return void
     */
    public function testParseQueryString(): void
    {
        $queryString = 'name=test&category=active&limit=10';

        $result = $this->searchService->parseQueryString($queryString);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertEquals('test', $result['name']);
        $this->assertEquals('active', $result['category']);
        $this->assertEquals('10', $result['limit']);
    }

    /**
     * Test query string parsing with empty string
     *
     * This test verifies that the search service handles
     * empty query strings correctly.
     *
     * @covers ::parseQueryString
     * @return void
     */
    public function testParseQueryStringWithEmptyString(): void
    {
        $result = $this->searchService->parseQueryString('');

        $this->assertIsArray($result);
        // When empty string is passed, it creates one element with empty key
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('', $result);
        $this->assertEquals('', $result['']);
    }

    /**
     * Test query string parsing with URL encoding
     *
     * This test verifies that the search service correctly
     * handles URL encoded parameters.
     *
     * @covers ::parseQueryString
     * @return void
     */
    public function testParseQueryStringWithUrlEncoding(): void
    {
        $queryString = 'name=test%20user&category=active%20status&limit=10';

        $result = $this->searchService->parseQueryString($queryString);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertEquals('test user', $result['name']);
        $this->assertEquals('active status', $result['category']);
        $this->assertEquals('10', $result['limit']);
    }
}
