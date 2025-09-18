<?php

declare(strict_types=1);

/**
 * EndpointsControllerTest
 * 
 * Unit tests for the EndpointsController
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

use OCA\OpenConnector\Controller\EndpointsController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\EndpointService;
use OCA\OpenConnector\Service\AuthorizationService;
use OCA\OpenConnector\Service\EndpointCacheService;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\EndpointLogMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the EndpointsController
 *
 * This test class covers all functionality of the EndpointsController
 * including endpoint listing, creation, updates, and deletion operations.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class EndpointsControllerTest extends TestCase
{
    /**
     * The EndpointsController instance being tested
     *
     * @var EndpointsController
     */
    private EndpointsController $controller;

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
     * Mock endpoint mapper
     *
     * @var MockObject|EndpointMapper
     */
    private MockObject $endpointMapper;

    /**
     * Mock endpoint service
     *
     * @var MockObject|EndpointService
     */
    private MockObject $endpointService;

    /**
     * Mock authorization service
     *
     * @var MockObject|AuthorizationService
     */
    private MockObject $authorizationService;

    /**
     * Mock object service
     *
     * @var MockObject|ObjectService
     */
    private MockObject $objectService;

    /**
     * Mock endpoint cache service
     *
     * @var MockObject|EndpointCacheService
     */
    private MockObject $endpointCacheService;

    /**
     * Mock logger
     *
     * @var MockObject|LoggerInterface
     */
    private MockObject $logger;

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
        $this->endpointMapper = $this->createMock(EndpointMapper::class);
        $this->endpointService = $this->createMock(EndpointService::class);
        $this->authorizationService = $this->createMock(AuthorizationService::class);
        $this->objectService = $this->createMock(ObjectService::class);
        $this->endpointCacheService = $this->createMock(EndpointCacheService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new EndpointsController(
            'openconnector',
            $this->request,
            $this->config,
            $this->endpointMapper,
            $this->endpointService,
            $this->authorizationService,
            $this->objectService,
            $this->endpointCacheService,
            $this->logger
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
     * Test successful retrieval of all endpoints
     *
     * This test verifies that the index() method returns correct endpoint data
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
            ->with($filters, ['name', 'description', 'endpoint'])
            ->willReturn(['conditions' => 'name LIKE %test%']);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn(['limit' => 10]);

        // Mock endpoint mapper
        $expectedEndpoints = [
            new \OCA\OpenConnector\Db\Endpoint(),
            new \OCA\OpenConnector\Db\Endpoint()
        ];
        $this->endpointMapper->expects($this->once())
            ->method('findAll')
            ->with(
                null, // limit
                null, // offset
                ['limit' => 10], // filters
                ['conditions' => 'name LIKE %test%'], // searchConditions
                ['search' => 'test'] // searchParams
            )
            ->willReturn($expectedEndpoints);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedEndpoints], $response->getData());
    }

    /**
     * Test successful retrieval of a single endpoint
     *
     * This test verifies that the show() method returns correct endpoint data
     * for a valid endpoint ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $endpointId = '123';
        $expectedEndpoint = new \OCA\OpenConnector\Db\Endpoint();
        $expectedEndpoint->setId((int) $endpointId);
        $expectedEndpoint->setName('Test Endpoint');

        // Mock endpoint mapper to return the expected endpoint
        $this->endpointMapper->expects($this->once())
            ->method('find')
            ->with((int) $endpointId)
            ->willReturn($expectedEndpoint);

        // Execute the method
        $response = $this->controller->show($endpointId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedEndpoint, $response->getData());
    }

    /**
     * Test endpoint retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the endpoint ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $endpointId = '999';

        // Mock endpoint mapper to throw DoesNotExistException
        $this->endpointMapper->expects($this->once())
            ->method('find')
            ->with((int) $endpointId)
            ->willThrowException(new DoesNotExistException('Endpoint not found'));

        // Execute the method
        $response = $this->controller->show($endpointId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful endpoint creation
     *
     * This test verifies that the create() method creates a new endpoint
     * and returns the created endpoint data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $endpointData = [
            'name' => 'New Endpoint',
            'description' => 'A new test endpoint',
            'url' => 'https://api.example.com/endpoint'
        ];

        $expectedEndpoint = new \OCA\OpenConnector\Db\Endpoint();
        $expectedEndpoint->setName($endpointData['name']);
        $expectedEndpoint->setDescription($endpointData['description']);

        // Mock request to return endpoint data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($endpointData);

        // Mock endpoint mapper to return the created endpoint
        $this->endpointMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'New Endpoint', 'description' => 'A new test endpoint', 'url' => 'https://api.example.com/endpoint'])
            ->willReturn($expectedEndpoint);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedEndpoint, $response->getData());
    }

    /**
     * Test successful endpoint update
     *
     * This test verifies that the update() method updates an existing endpoint
     * and returns the updated endpoint data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $endpointId = 123;
        $updateData = [
            'name' => 'Updated Endpoint',
            'description' => 'An updated test endpoint'
        ];

        $updatedEndpoint = new \OCA\OpenConnector\Db\Endpoint();
        $updatedEndpoint->setId($endpointId);
        $updatedEndpoint->setName($updateData['name']);
        $updatedEndpoint->setDescription($updateData['description']);

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock endpoint mapper to return updated endpoint
        $this->endpointMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($endpointId, ['name' => 'Updated Endpoint', 'description' => 'An updated test endpoint'])
            ->willReturn($updatedEndpoint);

        // Execute the method
        $response = $this->controller->update($endpointId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedEndpoint, $response->getData());
    }

    /**
     * Test endpoint update with non-existent ID
     *
     * This test verifies that the update() method returns a 404 error
     * when the endpoint ID does not exist.
     *
     * @return void
     */
    public function testUpdateWithNonExistentId(): void
    {
        $id = 999; // Non-existent ID
        $data = ['name' => 'Updated Endpoint'];

        // Mock the request to return test data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($data);

        // Mock the mapper to return an endpoint for non-existent ID
        $endpoint = $this->createMock(Endpoint::class);
        $this->endpointMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($id, $data)
            ->willReturn($endpoint);

        $response = $this->controller->update($id);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertInstanceOf(Endpoint::class, $response->getData());
    }

    /**
     * Test successful endpoint deletion
     *
     * This test verifies that the destroy() method deletes an endpoint
     * and returns a success response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $endpointId = 123;
        $existingEndpoint = new \OCA\OpenConnector\Db\Endpoint();
        $existingEndpoint->setId($endpointId);
        $existingEndpoint->setName('Test Endpoint');

        // Mock endpoint mapper to return existing endpoint and handle deletion
        $this->endpointMapper->expects($this->once())
            ->method('find')
            ->with($endpointId)
            ->willReturn($existingEndpoint);

        $this->endpointMapper->expects($this->once())
            ->method('delete')
            ->with($existingEndpoint);

        // Execute the method
        $response = $this->controller->destroy($endpointId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
    }

    /**
     * Test endpoint deletion with non-existent ID
     *
     * This test verifies that the destroy() method returns a 404 error
     * when the endpoint ID does not exist.
     *
     * @return void
     */
    public function testDestroyWithNonExistentId(): void
    {
        $id = 999; // Non-existent ID

        // Mock the mapper to return an endpoint for find, then delete it
        $endpoint = $this->createMock(Endpoint::class);
        $this->endpointMapper->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($endpoint);
        
        $this->endpointMapper->expects($this->once())
            ->method('delete')
            ->with($endpoint)
            ->willReturn($endpoint);

        $response = $this->controller->destroy($id);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertIsArray($response->getData());
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
            ->with($filters, ['name', 'description', 'endpoint'])
            ->willReturn([]);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn([]);

        // Mock endpoint mapper
        $expectedEndpoints = [];
        $this->endpointMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [], [], [])
            ->willReturn($expectedEndpoints);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedEndpoints], $response->getData());
    }

    /**
     * Test handlePath method with cache hit.
     *
     * @return void
     */
    public function testHandlePathWithCacheHit(): void
    {
        $path = '/api/test';
        $endpoint = $this->createMock(Endpoint::class);
        $endpoint->method('getEndpoint')->willReturn('/api/test');
        $endpoint->method('getMethod')->willReturn('GET');
        $endpoint->method('getRules')->willReturn([]);
        $endpoint->method('getConditions')->willReturn([]);
        $endpoint->method('getInputMapping')->willReturn(null);
        $endpoint->method('getOutputMapping')->willReturn(null);
        $endpoint->method('getConfigurations')->willReturn([]);
        $endpoint->method('getTargetType')->willReturn('register/schema');
        $endpoint->method('getTargetId')->willReturn('20/111');
        $endpoint->method('getEndpointArray')->willReturn(['api', 'test']);

        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getHeader')->willReturn('application/json');
        $this->request->method('getParams')->willReturn([]);

        $this->endpointCacheService->expects($this->once())
            ->method('findByPathRegex')
            ->with($path, 'GET')
            ->willReturn($endpoint);

        // Mock ObjectService for simple endpoint handling
        $mockMapper = $this->createMock(\OCA\OpenConnector\Db\ObjectEntity::class);
        $mockMapper->method('findAllPaginated')->willReturn([
            'results' => [],
            'total' => 0,
            'page' => 1,
            'pages' => 1
        ]);

        $this->objectService->expects($this->once())
            ->method('getMapper')
            ->with(111, 20)
            ->willReturn($mockMapper);

        $this->authorizationService->expects($this->once())
            ->method('corsAfterController')
            ->willReturnArgument(1);

        $response = $this->controller->handlePath($path);

        $this->assertInstanceOf(JSONResponse::class, $response);
    }

    /**
     * Test handlePath method with no matching endpoint.
     *
     * @return void
     */
    public function testHandlePathWithNoMatch(): void
    {
        $path = '/api/nonexistent';

        $this->request->method('getMethod')->willReturn('GET');

        $this->endpointCacheService->expects($this->once())
            ->method('findByPathRegex')
            ->with($path, 'GET')
            ->willReturn(null);

        $this->authorizationService->expects($this->once())
            ->method('corsAfterController')
            ->willReturnArgument(1);

        $response = $this->controller->handlePath($path);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(404, $response->getStatus());
        $this->assertStringContainsString('No matching endpoint found', $response->getData()['error']);
    }

    /**
     * Test handlePath method with complex endpoint (not simple).
     *
     * @return void
     */
    public function testHandlePathWithComplexEndpoint(): void
    {
        $path = '/api/complex';
        $endpoint = $this->createMock(Endpoint::class);
        $endpoint->method('getEndpoint')->willReturn('/api/complex');
        $endpoint->method('getMethod')->willReturn('GET');
        $endpoint->method('getRules')->willReturn(['some-rule']); // Not empty, so not simple
        $endpoint->method('getConditions')->willReturn([]);
        $endpoint->method('getInputMapping')->willReturn(null);
        $endpoint->method('getOutputMapping')->willReturn(null);
        $endpoint->method('getConfigurations')->willReturn([]);
        $endpoint->method('getTargetType')->willReturn('register/schema');

        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getHeader')->willReturn('application/json');

        $this->endpointCacheService->expects($this->once())
            ->method('findByPathRegex')
            ->with($path, 'GET')
            ->willReturn($endpoint);

        $expectedResponse = new JSONResponse(['data' => 'test']);
        $this->endpointService->expects($this->once())
            ->method('handleRequest')
            ->with($endpoint, $this->request, $path)
            ->willReturn($expectedResponse);

        $this->authorizationService->expects($this->once())
            ->method('corsAfterController')
            ->willReturnArgument(1);

        $response = $this->controller->handlePath($path);

        $this->assertInstanceOf(JSONResponse::class, $response);
    }

    /**
     * Test preflightedCors method.
     *
     * @return void
     */
    public function testPreflightedCors(): void
    {
        $origin = 'https://example.com';
        $this->request->server = ['HTTP_ORIGIN' => $origin];

        $response = $this->controller->preflightedCors();

        $this->assertInstanceOf(\OCP\AppFramework\Http\Response::class, $response);
        $this->assertEquals($origin, $response->getHeaders()['Access-Control-Allow-Origin']);
        $this->assertEquals('PUT, POST, GET, DELETE, PATCH', $response->getHeaders()['Access-Control-Allow-Methods']);
    }

    /**
     * Test logs method.
     *
     * @return void
     */
    public function testLogs(): void
    {
        $searchService = $this->createMock(SearchService::class);

        $response = $this->controller->logs($searchService);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(500, $response->getStatus());
        $this->assertStringContainsString('Endpoint logging is not available', $response->getData()['error']);
    }
}
