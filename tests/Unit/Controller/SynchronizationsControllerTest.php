<?php

declare(strict_types=1);

/**
 * SynchronizationsControllerTest
 * 
 * Unit tests for the SynchronizationsController
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

use OCA\OpenConnector\Controller\SynchronizationsController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the SynchronizationsController
 *
 * This test class covers all functionality of the SynchronizationsController
 * including synchronization listing, creation, updates, deletion, and execution.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class SynchronizationsControllerTest extends TestCase
{
    /**
     * The SynchronizationsController instance being tested
     *
     * @var SynchronizationsController
     */
    private SynchronizationsController $controller;

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
     * Mock synchronization mapper
     *
     * @var MockObject|SynchronizationMapper
     */
    private MockObject $synchronizationMapper;

    /**
     * Mock synchronization contract mapper
     *
     * @var MockObject|SynchronizationContractMapper
     */
    private MockObject $synchronizationContractMapper;

    /**
     * Mock synchronization log mapper
     *
     * @var MockObject|SynchronizationLogMapper
     */
    private MockObject $synchronizationLogMapper;

    /**
     * Mock synchronization service
     *
     * @var MockObject|SynchronizationService
     */
    private MockObject $synchronizationService;

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
        $this->synchronizationMapper = $this->createMock(SynchronizationMapper::class);
        $this->synchronizationContractMapper = $this->createMock(SynchronizationContractMapper::class);
        $this->synchronizationLogMapper = $this->createMock(SynchronizationLogMapper::class);
        $this->synchronizationService = $this->createMock(SynchronizationService::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new SynchronizationsController(
            'openconnector',
            $this->request,
            $this->config,
            $this->synchronizationMapper,
            $this->synchronizationContractMapper,
            $this->synchronizationLogMapper,
            $this->synchronizationService
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
     * Test successful retrieval of all synchronizations
     *
     * This test verifies that the index() method returns correct synchronization data
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

        // Mock synchronization mapper
        $expectedSynchronizations = [
            new Synchronization(),
            new Synchronization()
        ];
        $this->synchronizationMapper->expects($this->once())
            ->method('findAll')
            ->with(
                null, // limit
                null, // offset
                ['limit' => 10], // filters
                ['conditions' => 'name LIKE %test%'], // searchConditions
                ['search' => 'test'] // searchParams
            )
            ->willReturn($expectedSynchronizations);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedSynchronizations], $response->getData());
    }

    /**
     * Test successful retrieval of a single synchronization
     *
     * This test verifies that the show() method returns correct synchronization data
     * for a valid synchronization ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $synchronizationId = '123';
        $expectedSynchronization = new Synchronization();
        $expectedSynchronization->setId((int) $synchronizationId);
        $expectedSynchronization->setName('Test Synchronization');

        // Mock synchronization mapper to return the expected synchronization
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with((int) $synchronizationId)
            ->willReturn($expectedSynchronization);

        // Execute the method
        $response = $this->controller->show($synchronizationId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedSynchronization, $response->getData());
    }

    /**
     * Test synchronization retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the synchronization ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $synchronizationId = '999';

        // Mock synchronization mapper to throw DoesNotExistException
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with((int) $synchronizationId)
            ->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Synchronization not found'));

        // Execute the method
        $response = $this->controller->show($synchronizationId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful synchronization creation
     *
     * This test verifies that the create() method creates a new synchronization
     * and returns the created synchronization data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $synchronizationData = [
            'name' => 'New Synchronization',
            'description' => 'A new test synchronization',
            'source_id' => 1,
            'target_id' => 2
        ];

        $expectedSynchronization = new Synchronization();
        $expectedSynchronization->setName($synchronizationData['name']);
        $expectedSynchronization->setDescription($synchronizationData['description']);

        // Mock request to return synchronization data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($synchronizationData);

        // Mock synchronization mapper to return the created synchronization
        $this->synchronizationMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'New Synchronization', 'description' => 'A new test synchronization', 'source_id' => 1, 'target_id' => 2])
            ->willReturn($expectedSynchronization);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedSynchronization, $response->getData());
    }

    /**
     * Test successful synchronization update
     *
     * This test verifies that the update() method updates an existing synchronization
     * and returns the updated synchronization data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $synchronizationId = 123;
        $updateData = [
            'name' => 'Updated Synchronization',
            'description' => 'An updated test synchronization'
        ];

        $updatedSynchronization = new Synchronization();
        $updatedSynchronization->setId($synchronizationId);
        $updatedSynchronization->setName($updateData['name']);
        $updatedSynchronization->setDescription($updateData['description']);

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock synchronization mapper to return updated synchronization
        $this->synchronizationMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($synchronizationId, ['name' => 'Updated Synchronization', 'description' => 'An updated test synchronization'])
            ->willReturn($updatedSynchronization);

        // Execute the method
        $response = $this->controller->update($synchronizationId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedSynchronization, $response->getData());
    }

    /**
     * Test successful synchronization deletion
     *
     * This test verifies that the destroy() method deletes a synchronization
     * and returns a success response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $synchronizationId = 123;
        $existingSynchronization = new Synchronization();
        $existingSynchronization->setId($synchronizationId);
        $existingSynchronization->setName('Test Synchronization');

        // Mock synchronization mapper to return existing synchronization and handle deletion
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($existingSynchronization);

        $this->synchronizationMapper->expects($this->once())
            ->method('delete')
            ->with($existingSynchronization);

        // Execute the method
        $response = $this->controller->destroy($synchronizationId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
    }

    /**
     * Test successful retrieval of synchronization contracts
     *
     * This test verifies that the contracts() method returns correct contract data
     * for a valid synchronization ID.
     *
     * @return void
     */
    public function testContractsSuccessful(): void
    {
        $synchronizationId = 123;
        $expectedContracts = [
            new \OCA\OpenConnector\Db\SynchronizationContract(),
            new \OCA\OpenConnector\Db\SynchronizationContract()
        ];

        // Mock synchronization contract mapper to return contracts
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, ['synchronization_id' => $synchronizationId])
            ->willReturn($expectedContracts);

        // Execute the method
        $response = $this->controller->contracts($synchronizationId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedContracts, $response->getData());
    }

    /**
     * Test contracts retrieval with non-existent synchronization
     *
     * This test verifies that the contracts() method returns a 404 error
     * when the synchronization ID does not exist.
     *
     * @return void
     */
    public function testContractsWithNonExistentId(): void
    {
        $synchronizationId = 999;

        // Mock synchronization contract mapper to throw DoesNotExistException
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, ['synchronization_id' => $synchronizationId])
            ->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Contracts not found'));

        // Execute the method
        $response = $this->controller->contracts($synchronizationId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Contracts not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful synchronization test execution
     *
     * This test verifies that the test() method executes a synchronization test
     * and returns the test results.
     *
     * @return void
     */
    public function testTestSuccessful(): void
    {
        $synchronizationId = 123;
        $existingSynchronization = new Synchronization();
        $existingSynchronization->setId($synchronizationId);
        $existingSynchronization->setName('Test Synchronization');

        $expectedResult = [
            'resultObject' => ['fullName' => 'John Doe'],
            'isValid' => true,
            'validationErrors' => []
        ];

        // Mock synchronization mapper to return existing synchronization
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($existingSynchronization);

        // Mock synchronization service to return test results
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with($existingSynchronization, true, false)
            ->willReturn($expectedResult);

        // Execute the method
        $response = $this->controller->test($synchronizationId, false);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedResult, $response->getData());
        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * Test synchronization test with non-existent ID
     *
     * This test verifies that the test() method returns a 404 error
     * when the synchronization ID does not exist.
     *
     * @return void
     */
    public function testTestWithNonExistentId(): void
    {
        $synchronizationId = 999;

        // Mock synchronization mapper to throw DoesNotExistException
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Synchronization not found'));

        // Execute the method
        $response = $this->controller->test($synchronizationId, false);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful synchronization run execution
     *
     * This test verifies that the run() method executes a synchronization
     * and returns the run results.
     *
     * @return void
     */
    public function testRunSuccessful(): void
    {
        $synchronizationId = 123;
        $existingSynchronization = new Synchronization();
        $existingSynchronization->setId($synchronizationId);
        $existingSynchronization->setName('Test Synchronization');

        $parameters = ['test' => 'false', 'force' => 'false', 'source' => 'test-source', 'data' => ['key' => 'value']];

        $expectedResult = [
            'resultObject' => ['fullName' => 'John Doe'],
            'isValid' => true,
            'validationErrors' => []
        ];

        // Mock request to return parameters
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($parameters);

        // Mock synchronization mapper to return existing synchronization
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($existingSynchronization);

        // Mock synchronization service to return run results
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with(
                $existingSynchronization,
                false, // isTest
                false, // force
                null, // object (null for extern-to-intern sync)
                null, // mutationType
                'test-source', // source
                ['key' => 'value'] // data
            )
            ->willReturn($expectedResult);

        // Execute the method
        $response = $this->controller->run($synchronizationId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedResult, $response->getData());
        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * Test synchronization run with non-existent ID
     *
     * This test verifies that the run() method returns a 404 error
     * when the synchronization ID does not exist.
     *
     * @return void
     */
    public function testRunWithNonExistentId(): void
    {
        $synchronizationId = 999;

        $parameters = ['test' => 'false', 'force' => 'false'];

        // Mock request to return parameters
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($parameters);

        // Mock synchronization mapper to throw DoesNotExistException
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Synchronization not found'));

        // Execute the method
        $response = $this->controller->run($synchronizationId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful log deletion
     *
     * This test verifies that the deleteLog() method deletes a synchronization log
     * and returns a success response.
     *
     * @return void
     */
    public function testDeleteLogSuccessful(): void
    {
        $logId = 123;
        $existingLog = new \OCA\OpenConnector\Db\SynchronizationLog();
        $existingLog->setId($logId);

        // Mock synchronization log mapper to return existing log and handle deletion
        $this->synchronizationLogMapper->expects($this->once())
            ->method('find')
            ->with($logId)
            ->willReturn($existingLog);

        $this->synchronizationLogMapper->expects($this->once())
            ->method('delete')
            ->with($existingLog);

        // Execute the method
        $response = $this->controller->deleteLog($logId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Log deleted successfully'], $response->getData());
        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * Test log deletion with non-existent ID
     *
     * This test verifies that the deleteLog() method returns a 404 error
     * when the log ID does not exist.
     *
     * @return void
     */
    public function testDeleteLogWithNonExistentId(): void
    {
        $logId = 999;

        // Mock synchronization log mapper to throw DoesNotExistException
        $this->synchronizationLogMapper->expects($this->once())
            ->method('find')
            ->with($logId)
            ->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Log not found'));

        // Execute the method
        $response = $this->controller->deleteLog($logId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Log not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
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

        // Mock synchronization mapper
        $expectedSynchronizations = [];
        $this->synchronizationMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [], [], [])
            ->willReturn($expectedSynchronizations);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedSynchronizations], $response->getData());
    }
}
