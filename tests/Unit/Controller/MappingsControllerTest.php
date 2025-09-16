<?php

declare(strict_types=1);

/**
 * MappingsControllerTest
 * 
 * Unit tests for the MappingsController
 *
 * @category   Test
 * @package    OCA\OpenConnector\Tests\Unit\Controller
 * @author     Conduction.nl <info@conduction.nl>
 * @copyright  Conduction.nl 2024
 * @license    EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version    1.0.0
 * @link       https://github.com/ConductionNL/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Controller;

use OCA\OpenConnector\Controller\MappingsController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the MappingsController
 *
 * This test class covers all functionality of the MappingsController
 * including mapping listing, creation, updates, deletion, testing, and object operations.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class MappingsControllerTest extends TestCase
{
    /**
     * The MappingsController instance being tested
     *
     * @var MappingsController
     */
    private MappingsController $controller;

    /**
     * Mock request object
     *
     * @var MockObject|IRequest
     */
    private MockObject $request;

    /**
     * Mock app config
     *
     * @var MockObject|IAppConfig
     */
    private MockObject $config;

    /**
     * Mock mapping mapper
     *
     * @var MockObject|MappingMapper
     */
    private MockObject $mappingMapper;

    /**
     * Mock mapping service
     *
     * @var MockObject|MappingService
     */
    private MockObject $mappingService;

    /**
     * Mock object service
     *
     * @var MockObject|ObjectService
     */
    private MockObject $objectService;

    /**
     * Set up test environment before each test
     *
     * This method initializes all mocks and the controller instance
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for all dependencies
        $this->request = $this->createMock(IRequest::class);
        $this->config = $this->createMock(IAppConfig::class);
        $this->mappingMapper = $this->createMock(MappingMapper::class);
        $this->mappingService = $this->createMock(MappingService::class);
        $this->objectService = $this->createMock(ObjectService::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new MappingsController(
            'openconnector',
            $this->request,
            $this->config,
            $this->mappingMapper,
            $this->mappingService,
            $this->objectService
        );
    }

    /**
     * Test successful page rendering
     *
     * This test verifies that the page() method returns a proper TemplateResponse.
     *
     * @return void
     */
    public function testPageSuccessful(): void
    {
        // Execute the method
        $response = $this->controller->page();

        // Assert response is a TemplateResponse
        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertEquals('index', $response->getTemplateName());
        $this->assertEquals([], $response->getParams());
    }

    /**
     * Test successful retrieval of all mappings
     *
     * This test verifies that the index() method returns correct mapping data
     * with search functionality.
     *
     * @return void
     */
    public function testIndexSuccessful(): void
    {
        // Setup mock request parameters
        $filters = ['search' => 'test', 'limit' => 10];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($filters);

        // Create mock services
        $objectService = $this->createMock(ObjectService::class);
        $searchService = $this->createMock(SearchService::class);

        // Mock search service methods
        $searchService->expects($this->once())
            ->method('createMySQLSearchParams')
            ->with($filters)
            ->willReturn(['search' => 'test']);

        $searchService->expects($this->once())
            ->method('createMySQLSearchConditions')
            ->with($filters, ['name', 'description'])
            ->willReturn(['conditions' => 'name LIKE %test%']);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn(['limit' => 10]);

        // Mock mapping mapper
        $expectedMappings = [
            new Mapping(),
            new Mapping()
        ];
        $this->mappingMapper->expects($this->once())
            ->method('findAll')
            ->with(
                null, // limit
                null, // offset
                ['limit' => 10], // filters
                ['conditions' => 'name LIKE %test%'], // searchConditions
                ['search' => 'test'] // searchParams
            )
            ->willReturn($expectedMappings);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedMappings], $response->getData());
    }

    /**
     * Test successful retrieval of a single mapping
     *
     * This test verifies that the show() method returns correct mapping data
     * for a valid mapping ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $mappingId = '123';
        $expectedMapping = new Mapping();
        $expectedMapping->setId((int) $mappingId);
        $expectedMapping->setName('Test Mapping');

        // Mock mapping mapper to return the expected mapping
        $this->mappingMapper->expects($this->once())
            ->method('find')
            ->with((int) $mappingId)
            ->willReturn($expectedMapping);

        // Execute the method
        $response = $this->controller->show($mappingId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedMapping, $response->getData());
    }

    /**
     * Test mapping retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the mapping ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $mappingId = '999';

        // Mock mapping mapper to throw DoesNotExistException
        $this->mappingMapper->expects($this->once())
            ->method('find')
            ->with((int) $mappingId)
            ->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Mapping not found'));

        // Execute the method
        $response = $this->controller->show($mappingId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful mapping creation
     *
     * This test verifies that the create() method creates a new mapping
     * and returns the created mapping data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $mappingData = [
            'name' => 'New Mapping',
            'description' => 'A new test mapping',
            'mapping' => ['field1' => 'value1']
        ];

        $expectedMapping = new Mapping();
        $expectedMapping->setName($mappingData['name']);
        $expectedMapping->setDescription($mappingData['description']);

        // Mock request to return mapping data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($mappingData);

        // Mock mapping mapper to return the created mapping
        $this->mappingMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'New Mapping', 'description' => 'A new test mapping', 'mapping' => ['field1' => 'value1']])
            ->willReturn($expectedMapping);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedMapping, $response->getData());
    }

    /**
     * Test successful mapping update
     *
     * This test verifies that the update() method updates an existing mapping
     * and returns the updated mapping data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $mappingId = 123;
        $updateData = [
            'name' => 'Updated Mapping',
            'description' => 'An updated test mapping'
        ];

        $updatedMapping = new Mapping();
        $updatedMapping->setId($mappingId);
        $updatedMapping->setName($updateData['name']);
        $updatedMapping->setDescription($updateData['description']);

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock mapping mapper to return updated mapping
        $this->mappingMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($mappingId, ['name' => 'Updated Mapping', 'description' => 'An updated test mapping'])
            ->willReturn($updatedMapping);

        // Execute the method
        $response = $this->controller->update($mappingId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedMapping, $response->getData());
    }

    /**
     * Test successful mapping deletion
     *
     * This test verifies that the destroy() method deletes a mapping
     * and returns a success response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $mappingId = 123;
        $existingMapping = new Mapping();
        $existingMapping->setId($mappingId);
        $existingMapping->setName('Test Mapping');

        // Mock mapping mapper to return existing mapping and handle deletion
        $this->mappingMapper->expects($this->once())
            ->method('find')
            ->with($mappingId)
            ->willReturn($existingMapping);

        $this->mappingMapper->expects($this->once())
            ->method('delete')
            ->with($existingMapping);

        // Execute the method
        $response = $this->controller->destroy($mappingId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
    }

    /**
     * Test successful mapping test execution
     *
     * This test verifies that the test() method executes a mapping test
     * and returns the test results.
     *
     * @return void
     */
    public function testTestSuccessful(): void
    {
        $testData = [
            'inputObject' => '{"name":"John Doe","email":"john@example.com"}',
            'mapping' => '{"name":"{{inputObject.name}}","email":"{{inputObject.email}}"}',
            'validation' => true
        ];

        // Mock the request to return test data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($testData);

        // Mock ObjectService to return OpenRegisters service
        $openRegisters = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        
        $this->objectService->expects($this->once())
            ->method('getOpenRegisters')
            ->willReturn($openRegisters);

        // Mock URLGenerator
        $urlGenerator = $this->createMock(\OCP\IURLGenerator::class);
        $urlGenerator->expects($this->any())
            ->method('linkToRoute')
            ->willReturn('/test/url');

        $response = $this->controller->test($this->objectService, $urlGenerator);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * Test mapping test with missing required parameters
     *
     * This test verifies that the test() method throws an exception
     * when required parameters are missing.
     *
     * @return void
     */
    public function testTestWithMissingParameters(): void
    {
        $testData = [
            'inputObject' => '{"name":"John Doe"}'
            // Missing 'mapping' parameter
        ];

        // Mock object service
        $objectService = $this->createMock(ObjectService::class);
        $objectService->expects($this->once())
            ->method('getOpenRegisters')
            ->willReturn(null);

        $urlGenerator = $this->createMock(IURLGenerator::class);

        // Mock request to return test data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($testData);

        // Execute the method and expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Both `inputObject` and `mapping` are required');

        $this->controller->test($objectService, $urlGenerator);
    }

    /**
     * Test successful object save
     *
     * This test verifies that the saveObject() method saves an object
     * when OpenRegisters is available.
     *
     * @return void
     */
    public function testSaveObjectSuccessful(): void
    {
        $objectData = [
            'register' => '1',
            'schema' => '1',
            'object' => ['name' => 'Test Object', 'description' => 'Test Description']
        ];

        // Mock the request to return test data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($objectData);

        // Mock ObjectService to return OpenRegisters service
        $openRegisters = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $objectMapper = $this->createMock(\OCA\OpenRegister\Db\ObjectEntityMapper::class);
        $object = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        
        $this->objectService->expects($this->once())
            ->method('getOpenRegisters')
            ->willReturn($openRegisters);
            
        $openRegisters->expects($this->once())
            ->method('saveObject')
            ->with($objectData['object'], [], $objectData['register'], $objectData['schema'])
            ->willReturn($object);

        $response = $this->controller->saveObject($this->objectService);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * Test object save when OpenRegisters is not available
     *
     * This test verifies that the saveObject() method returns null
     * when OpenRegisters is not available.
     *
     * @return void
     */
    public function testSaveObjectWithoutOpenRegisters(): void
    {
        // Mock object service to return null (no OpenRegisters)
        $this->objectService->expects($this->once())
            ->method('getOpenRegisters')
            ->willReturn(null);

        // Execute the method
        $response = $this->controller->saveObject();

        // Assert response is null
        $this->assertNull($response);
    }

    /**
     * Test successful object retrieval
     *
     * This test verifies that the getObjects() method returns correct object data
     * when OpenRegisters is available.
     *
     * @return void
     */
    public function testGetObjectsSuccessful(): void
    {
        // Mock ObjectService to return OpenRegisters service
        $openRegisters = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $registers = [
            $this->createMock(\OCA\OpenRegister\Db\Register::class),
            $this->createMock(\OCA\OpenRegister\Db\Register::class)
        ];
        
        $this->objectService->expects($this->once())
            ->method('getOpenRegisters')
            ->willReturn($openRegisters);
            
        $openRegisters->expects($this->once())
            ->method('getRegisters')
            ->willReturn($registers);

        $response = $this->controller->getObjects($this->objectService);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(200, $response->getStatus());
        $this->assertTrue($response->getData()['openRegisters']);
    }

    /**
     * Test object retrieval when OpenRegisters is not available
     *
     * This test verifies that the getObjects() method returns correct data
     * when OpenRegisters is not available.
     *
     * @return void
     */
    public function testGetObjectsWithoutOpenRegisters(): void
    {
        // Mock object service to return null (no OpenRegisters)
        $this->objectService->expects($this->once())
            ->method('getOpenRegisters')
            ->willReturn(null);

        // Execute the method
        $response = $this->controller->getObjects();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $expectedData = [
            'openRegisters' => false
        ];
        $this->assertEquals($expectedData, $response->getData());
    }

    /**
     * Test index method with empty filters
     *
     * This test verifies that the index() method handles empty filters correctly.
     *
     * @return void
     */
    public function testIndexWithEmptyFilters(): void
    {
        // Setup mock request parameters with empty filters
        $filters = [];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($filters);

        // Create mock services
        $objectService = $this->createMock(ObjectService::class);
        $searchService = $this->createMock(SearchService::class);

        // Mock search service methods
        $searchService->expects($this->once())
            ->method('createMySQLSearchParams')
            ->with($filters)
            ->willReturn([]);

        $searchService->expects($this->once())
            ->method('createMySQLSearchConditions')
            ->with($filters, ['name', 'description'])
            ->willReturn([]);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn([]);

        // Mock mapping mapper
        $expectedMappings = [];
        $this->mappingMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [], [], [])
            ->willReturn($expectedMappings);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedMappings], $response->getData());
    }
}
