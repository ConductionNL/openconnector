<?php

declare(strict_types=1);

/**
 * ConsumersControllerTest
 * 
 * Unit tests for the ConsumersController
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

use OCA\OpenConnector\Controller\ConsumersController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Db\Consumer;
use OCA\OpenConnector\Db\ConsumerMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the ConsumersController
 *
 * This test class covers all functionality of the ConsumersController
 * including consumer listing, creation, updates, deletion, and individual consumer retrieval.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class ConsumersControllerTest extends TestCase
{
    /**
     * The ConsumersController instance being tested
     *
     * @var ConsumersController
     */
    private ConsumersController $controller;

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
     * Mock consumer mapper
     *
     * @var MockObject|ConsumerMapper
     */
    private MockObject $consumerMapper;

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
        $this->consumerMapper = $this->createMock(ConsumerMapper::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new ConsumersController(
            'openconnector',
            $this->request,
            $this->config,
            $this->consumerMapper
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
     * Test successful retrieval of all consumers
     *
     * This test verifies that the index() method returns correct consumer data
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

        // Mock consumer mapper
        $expectedConsumers = [
            new Consumer(),
            new Consumer()
        ];
        $this->consumerMapper->expects($this->once())
            ->method('findAll')
            ->with(
                null, // limit
                null, // offset
                ['limit' => 10], // filters
                ['conditions' => 'name LIKE %test%'], // searchConditions
                ['search' => 'test'] // searchParams
            )
            ->willReturn($expectedConsumers);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedConsumers], $response->getData());
    }

    /**
     * Test successful retrieval of a single consumer
     *
     * This test verifies that the show() method returns correct consumer data
     * for a valid consumer ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $consumerId = '123';
        $expectedConsumer = new Consumer();
        $expectedConsumer->setId((int) $consumerId);

        // Mock consumer mapper to return the expected consumer
        $this->consumerMapper->expects($this->once())
            ->method('find')
            ->with((int) $consumerId)
            ->willReturn($expectedConsumer);

        // Execute the method
        $response = $this->controller->show($consumerId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedConsumer, $response->getData());
    }

    /**
     * Test consumer retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the consumer ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $consumerId = '999';

        // Mock consumer mapper to throw DoesNotExistException
        $this->consumerMapper->expects($this->once())
            ->method('find')
            ->with((int) $consumerId)
            ->willThrowException(new DoesNotExistException('Consumer not found'));

        // Execute the method
        $response = $this->controller->show($consumerId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful consumer creation
     *
     * This test verifies that the create() method creates a new consumer
     * and returns the created consumer data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $consumerData = [
            'name' => 'New Consumer',
            'description' => 'A new test consumer',
            '_internal' => 'should_be_removed',
            'id' => '999' // should be removed
        ];

        $expectedConsumer = new Consumer();
        $expectedConsumer->setName('New Consumer');
        $expectedConsumer->setDescription('A new test consumer');

        // Mock request to return consumer data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($consumerData);

        // Mock consumer mapper to return the created consumer
        $this->consumerMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'New Consumer', 'description' => 'A new test consumer'])
            ->willReturn($expectedConsumer);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedConsumer, $response->getData());
    }

    /**
     * Test successful consumer update
     *
     * This test verifies that the update() method updates an existing consumer
     * and returns the updated consumer data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $consumerId = 123;
        $updateData = [
            'name' => 'Updated Consumer',
            'description' => 'An updated test consumer',
            '_internal' => 'should_be_removed',
            'id' => '999' // should be removed
        ];

        $updatedConsumer = new Consumer();
        $updatedConsumer->setId($consumerId);
        $updatedConsumer->setName('Updated Consumer');
        $updatedConsumer->setDescription('An updated test consumer');

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock consumer mapper to return updated consumer
        $this->consumerMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($consumerId, ['name' => 'Updated Consumer', 'description' => 'An updated test consumer'])
            ->willReturn($updatedConsumer);

        // Execute the method
        $response = $this->controller->update($consumerId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedConsumer, $response->getData());
    }

    /**
     * Test successful consumer deletion
     *
     * This test verifies that the destroy() method deletes a consumer
     * and returns an empty response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $consumerId = 123;
        $existingConsumer = new Consumer();
        $existingConsumer->setId($consumerId);

        // Mock consumer mapper to return existing consumer and handle deletion
        $this->consumerMapper->expects($this->once())
            ->method('find')
            ->with($consumerId)
            ->willReturn($existingConsumer);

        $this->consumerMapper->expects($this->once())
            ->method('delete')
            ->with($existingConsumer);

        // Execute the method
        $response = $this->controller->destroy($consumerId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
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

        // Mock consumer mapper
        $expectedConsumers = [];
        $this->consumerMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [], [], [])
            ->willReturn($expectedConsumers);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedConsumers], $response->getData());
    }

    /**
     * Test consumer creation with data filtering
     *
     * This test verifies that the create() method properly filters out
     * internal fields and ID fields.
     *
     * @return void
     */
    public function testCreateWithDataFiltering(): void
    {
        $consumerData = [
            'name' => 'Filtered Consumer',
            '_internal_field' => 'should_be_removed',
            '_another_internal' => 'also_removed',
            'id' => '999',
            'description' => 'A consumer with filtered data'
        ];

        $expectedConsumer = new Consumer();
        $expectedConsumer->setName('Filtered Consumer');
        $expectedConsumer->setDescription('A consumer with filtered data');

        // Mock request to return consumer data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($consumerData);

        // Mock consumer mapper to return the created consumer
        $this->consumerMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'Filtered Consumer', 'description' => 'A consumer with filtered data'])
            ->willReturn($expectedConsumer);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedConsumer, $response->getData());
    }

    /**
     * Test consumer update with data filtering
     *
     * This test verifies that the update() method properly filters out
     * internal fields and ID fields.
     *
     * @return void
     */
    public function testUpdateWithDataFiltering(): void
    {
        $consumerId = 123;
        $updateData = [
            'name' => 'Updated Filtered Consumer',
            '_internal_field' => 'should_be_removed',
            '_another_internal' => 'also_removed',
            'id' => '999',
            'description' => 'An updated consumer with filtered data'
        ];

        $updatedConsumer = new Consumer();
        $updatedConsumer->setId($consumerId);
        $updatedConsumer->setName('Updated Filtered Consumer');
        $updatedConsumer->setDescription('An updated consumer with filtered data');

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock consumer mapper to return updated consumer
        $this->consumerMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($consumerId, ['name' => 'Updated Filtered Consumer', 'description' => 'An updated consumer with filtered data'])
            ->willReturn($updatedConsumer);

        // Execute the method
        $response = $this->controller->update($consumerId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedConsumer, $response->getData());
    }
}
