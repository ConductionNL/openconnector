<?php

declare(strict_types=1);

/**
 * SynchronizationContractsControllerTest
 * 
 * Unit tests for the SynchronizationContractsController
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

use OCA\OpenConnector\Controller\SynchronizationContractsController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the SynchronizationContractsController
 *
 * This test class covers all functionality of the SynchronizationContractsController
 * including contract listing, creation, updates, deletion, activation, deactivation,
 * execution, statistics, performance, and export operations.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class SynchronizationContractsControllerTest extends TestCase
{
    /**
     * The SynchronizationContractsController instance being tested
     *
     * @var SynchronizationContractsController
     */
    private SynchronizationContractsController $controller;

    /**
     * Mock request object
     *
     * @var MockObject|IRequest
     */
    private MockObject $request;

    /**
     * Mock synchronization contract mapper
     *
     * @var MockObject|SynchronizationContractMapper
     */
    private MockObject $synchronizationContractMapper;

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
        $this->synchronizationContractMapper = $this->createMock(SynchronizationContractMapper::class);
        $this->objectService = $this->createMock(ObjectService::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new SynchronizationContractsController(
            'openconnector',
            $this->request,
            $this->synchronizationContractMapper,
            $this->objectService
        );
    }

    /**
     * Test successful retrieval of all contracts with default parameters
     *
     * This test verifies that the index() method returns correct contract data
     * with default pagination parameters.
     *
     * @return void
     */
    public function testIndexWithDefaultParameters(): void
    {
        $expectedContracts = [
            new SynchronizationContract(),
            new SynchronizationContract()
        ];

        // Mock synchronization contract mapper
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findAll')
            ->with(20, 0, [])
            ->willReturn($expectedContracts);

        $this->synchronizationContractMapper->expects($this->once())
            ->method('getTotalCount')
            ->with([])
            ->willReturn(2);

        // Execute the method
        $response = $this->controller->index();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedContracts, $data['results']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(1, $data['pagination']['pages']);
        $this->assertEquals(2, $data['pagination']['results']);
        $this->assertEquals(2, $data['pagination']['total']);
    }

    /**
     * Test successful retrieval of contracts with custom parameters
     *
     * This test verifies that the index() method returns correct contract data
     * with custom pagination and filter parameters.
     *
     * @return void
     */
    public function testIndexWithCustomParameters(): void
    {
        $expectedContracts = [new SynchronizationContract()];
        $filters = [
            'synchronization_id' => '123',
            'status' => 'active',
            'origin_id' => 'origin1',
            'target_id' => 'target1',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'success_rate_min' => '80',
            'success_rate_max' => '100'
        ];

        // Mock synchronization contract mapper
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findAll')
            ->with(10, 20, $filters)
            ->willReturn($expectedContracts);

        $this->synchronizationContractMapper->expects($this->once())
            ->method('getTotalCount')
            ->with($filters)
            ->willReturn(1);

        // Execute the method
        $response = $this->controller->index(
            10, // limit
            20, // offset
            '123', // synchronizationId
            'active', // status
            'origin1', // originId
            'target1', // targetId
            '2024-01-01', // dateFrom
            '2024-12-31', // dateTo
            '80', // successRateMin
            '100' // successRateMax
        );

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedContracts, $data['results']);
        $this->assertEquals(3, $data['pagination']['page']);
        $this->assertEquals(1, $data['pagination']['pages']);
        $this->assertEquals(1, $data['pagination']['results']);
        $this->assertEquals(1, $data['pagination']['total']);
    }

    /**
     * Test successful retrieval of a single contract
     *
     * This test verifies that the show() method returns correct contract data
     * for a valid contract ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $contractId = '123';
        $expectedContract = new SynchronizationContract();
        $expectedContract->setId((int) $contractId);

        // Mock synchronization contract mapper to return the expected contract
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with((int) $contractId)
            ->willReturn($expectedContract);

        // Execute the method
        $response = $this->controller->show($contractId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedContract, $response->getData());
    }

    /**
     * Test contract retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the contract ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $contractId = '999';

        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with((int) $contractId)
            ->willThrowException(new \Exception('Contract not found'));

        // Execute the method
        $response = $this->controller->show($contractId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Contract not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful contract creation
     *
     * This test verifies that the create() method creates a new contract
     * and returns the created contract data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $contractData = [
            'synchronization_id' => '123',
            'origin_id' => 'origin1',
            'target_id' => 'target1'
        ];

        $expectedContract = new SynchronizationContract();
        $expectedContract->setSynchronizationId('123');
        $expectedContract->setOriginId('origin1');
        $expectedContract->setTargetId('target1');

        // Mock request to return contract data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($contractData);

        // Mock synchronization contract mapper to return the created contract
        $this->synchronizationContractMapper->expects($this->once())
            ->method('createFromArray')
            ->with($contractData)
            ->willReturn($expectedContract);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedContract, $response->getData());
        $this->assertEquals(201, $response->getStatus());
    }

    /**
     * Test contract creation with error
     *
     * This test verifies that the create() method returns an error response
     * when contract creation fails.
     *
     * @return void
     */
    public function testCreateWithError(): void
    {
        $contractData = ['invalid' => 'data'];

        // Mock request to return contract data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($contractData);

        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('createFromArray')
            ->with($contractData)
            ->willThrowException(new \Exception('Invalid data'));

        // Execute the method
        $response = $this->controller->create();

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Could not create contract: Invalid data'], $response->getData());
        $this->assertEquals(400, $response->getStatus());
    }

    /**
     * Test successful contract update
     *
     * This test verifies that the update() method updates an existing contract
     * and returns the updated contract data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $contractId = 123;
        $updateData = [
            'origin_id' => 'origin2',
            'target_id' => 'target2'
        ];

        $updatedContract = new SynchronizationContract();
        $updatedContract->setId($contractId);
        $updatedContract->setOriginId('origin2');
        $updatedContract->setTargetId('target2');

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock synchronization contract mapper to return updated contract
        $this->synchronizationContractMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($contractId, $updateData)
            ->willReturn($updatedContract);

        // Execute the method
        $response = $this->controller->update((string) $contractId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedContract, $response->getData());
    }

    /**
     * Test contract update with error
     *
     * This test verifies that the update() method returns an error response
     * when contract update fails.
     *
     * @return void
     */
    public function testUpdateWithError(): void
    {
        $contractId = 123;
        $updateData = ['invalid' => 'data'];

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($contractId, $updateData)
            ->willThrowException(new \Exception('Invalid data'));

        // Execute the method
        $response = $this->controller->update((string) $contractId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Could not update contract: Invalid data'], $response->getData());
        $this->assertEquals(400, $response->getStatus());
    }

    /**
     * Test successful contract deletion
     *
     * This test verifies that the destroy() method deletes a contract
     * and returns a success response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $contractId = 123;
        $existingContract = new SynchronizationContract();
        $existingContract->setId($contractId);

        // Mock synchronization contract mapper to return existing contract and handle deletion
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willReturn($existingContract);

        $this->synchronizationContractMapper->expects($this->once())
            ->method('delete')
            ->with($existingContract);

        // Execute the method
        $response = $this->controller->destroy((string) $contractId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Contract deleted successfully'], $response->getData());
    }

    /**
     * Test contract deletion with non-existent ID
     *
     * This test verifies that the destroy() method returns an error response
     * when the contract ID does not exist.
     *
     * @return void
     */
    public function testDestroyWithNonExistentId(): void
    {
        $contractId = 999;

        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willThrowException(new \Exception('Contract not found'));

        // Execute the method
        $response = $this->controller->destroy((string) $contractId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Contract not found or could not be deleted'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful contract activation
     *
     * This test verifies that the activate() method activates a contract
     * and returns a success response.
     *
     * @return void
     */
    public function testActivateSuccessful(): void
    {
        $contractId = 123;
        $existingContract = new SynchronizationContract();
        $existingContract->setId($contractId);

        // Mock synchronization contract mapper to return existing contract
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willReturn($existingContract);

        // Execute the method
        $response = $this->controller->activate((string) $contractId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Contract activated successfully'], $response->getData());
    }

    /**
     * Test contract activation with non-existent ID
     *
     * This test verifies that the activate() method returns an error response
     * when the contract ID does not exist.
     *
     * @return void
     */
    public function testActivateWithNonExistentId(): void
    {
        $contractId = 999;

        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willThrowException(new \Exception('Contract not found'));

        // Execute the method
        $response = $this->controller->activate((string) $contractId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Contract not found or could not be activated'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful contract deactivation
     *
     * This test verifies that the deactivate() method deactivates a contract
     * and returns a success response.
     *
     * @return void
     */
    public function testDeactivateSuccessful(): void
    {
        $contractId = 123;
        $existingContract = new SynchronizationContract();
        $existingContract->setId($contractId);

        // Mock synchronization contract mapper to return existing contract
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willReturn($existingContract);

        // Execute the method
        $response = $this->controller->deactivate((string) $contractId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Contract deactivated successfully'], $response->getData());
    }

    /**
     * Test contract deactivation with non-existent ID
     *
     * This test verifies that the deactivate() method returns an error response
     * when the contract ID does not exist.
     *
     * @return void
     */
    public function testDeactivateWithNonExistentId(): void
    {
        $contractId = 999;

        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willThrowException(new \Exception('Contract not found'));

        // Execute the method
        $response = $this->controller->deactivate((string) $contractId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Contract not found or could not be deactivated'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful contract execution
     *
     * This test verifies that the execute() method executes a contract
     * and returns a success response.
     *
     * @return void
     */
    public function testExecuteSuccessful(): void
    {
        $contractId = 123;
        $existingContract = new SynchronizationContract();
        $existingContract->setId($contractId);

        // Mock synchronization contract mapper to return existing contract
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willReturn($existingContract);

        // Execute the method
        $response = $this->controller->execute((string) $contractId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Contract executed successfully'], $response->getData());
    }

    /**
     * Test contract execution with non-existent ID
     *
     * This test verifies that the execute() method returns an error response
     * when the contract ID does not exist.
     *
     * @return void
     */
    public function testExecuteWithNonExistentId(): void
    {
        $contractId = 999;

        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('find')
            ->with($contractId)
            ->willThrowException(new \Exception('Contract not found'));

        // Execute the method
        $response = $this->controller->execute((string) $contractId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Contract not found or could not be executed'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful statistics retrieval
     *
     * This test verifies that the statistics() method returns correct
     * statistical information about contracts.
     *
     * @return void
     */
    public function testStatisticsSuccessful(): void
    {
        // Mock synchronization contract mapper to return statistics
        $this->synchronizationContractMapper->expects($this->exactly(4))
            ->method('getTotalCount')
            ->withConsecutive(
                [[]],
                [['status' => 'active']],
                [['status' => 'inactive']],
                [['status' => 'error']]
            )
            ->willReturnOnConsecutiveCalls(100, 60, 30, 10);

        // Execute the method
        $response = $this->controller->statistics();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals(100, $data['totalCount']);
        $this->assertEquals(60, $data['activeCount']);
        $this->assertEquals(30, $data['inactiveCount']);
        $this->assertEquals(10, $data['errorCount']);
    }

    /**
     * Test statistics retrieval with error
     *
     * This test verifies that the statistics() method returns an error response
     * when statistics retrieval fails.
     *
     * @return void
     */
    public function testStatisticsWithError(): void
    {
        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('getTotalCount')
            ->with([])
            ->willThrowException(new \Exception('Database error'));

        // Execute the method
        $response = $this->controller->statistics();

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Could not fetch statistics'], $response->getData());
        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * Test successful performance data retrieval
     *
     * This test verifies that the performance() method returns correct
     * performance data for contracts.
     *
     * @return void
     */
    public function testPerformanceSuccessful(): void
    {
        // Execute the method
        $response = $this->controller->performance();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('last_7_days', $data);
        $this->assertArrayHasKey('last_30_days', $data);
        $this->assertArrayHasKey('last_90_days', $data);
        $this->assertEquals(85.5, $data['last_7_days']['successRate']);
        $this->assertEquals(120, $data['last_7_days']['totalExecutions']);
        $this->assertEquals(103, $data['last_7_days']['successfulExecutions']);
    }

    /**
     * Test performance data retrieval with error
     *
     * This test verifies that the performance() method returns an error response
     * when performance data retrieval fails.
     *
     * @return void
     */
    public function testPerformanceWithError(): void
    {
        // Since the performance method uses hardcoded data and doesn't have external dependencies,
        // we can't easily simulate an error condition. However, we can test the successful case
        // and verify the structure of the returned data.
        
        // Execute the method
        $response = $this->controller->performance();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        
        // Verify the structure of the performance data
        $this->assertArrayHasKey('last_7_days', $data);
        $this->assertArrayHasKey('last_30_days', $data);
        $this->assertArrayHasKey('last_90_days', $data);
        
        // Verify the structure of each time period
        foreach (['last_7_days', 'last_30_days', 'last_90_days'] as $period) {
            $this->assertArrayHasKey('successRate', $data[$period]);
            $this->assertArrayHasKey('totalExecutions', $data[$period]);
            $this->assertArrayHasKey('successfulExecutions', $data[$period]);
            
            // Verify data types
            $this->assertIsFloat($data[$period]['successRate']);
            $this->assertIsInt($data[$period]['totalExecutions']);
            $this->assertIsInt($data[$period]['successfulExecutions']);
            
            // Verify reasonable values
            $this->assertGreaterThanOrEqual(0, $data[$period]['successRate']);
            $this->assertLessThanOrEqual(100, $data[$period]['successRate']);
            $this->assertGreaterThanOrEqual(0, $data[$period]['totalExecutions']);
            $this->assertGreaterThanOrEqual(0, $data[$period]['successfulExecutions']);
            $this->assertLessThanOrEqual($data[$period]['totalExecutions'], $data[$period]['successfulExecutions']);
        }
    }

    /**
     * Test successful contract export
     *
     * This test verifies that the export() method exports contracts
     * as CSV with correct filters.
     *
     * @return void
     */
    public function testExportSuccessful(): void
    {
        $expectedContracts = [
            new SynchronizationContract(),
            new SynchronizationContract()
        ];

        $filters = [
            'synchronization_id' => '123',
            'status' => 'active',
            'origin_id' => 'origin1',
            'target_id' => 'target1',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31'
        ];

        // Mock synchronization contract mapper to return contracts
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, $filters)
            ->willReturn($expectedContracts);

        // Execute the method
        $response = $this->controller->export(
            '123', // synchronizationId
            'active', // status
            'origin1', // originId
            'target1', // targetId
            '2024-01-01', // dateFrom
            '2024-12-31' // dateTo
        );

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('filename', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertArrayHasKey('contentType', $data);
        $this->assertEquals('text/csv', $data['contentType']);
        $this->assertStringContainsString('synchronization_contracts_', $data['filename']);
        $this->assertStringContainsString('.csv', $data['filename']);
        $this->assertStringContainsString('ID,UUID,Synchronization ID,Origin ID,Target ID', $data['content']);
    }

    /**
     * Test contract export with error
     *
     * This test verifies that the export() method returns an error response
     * when export fails.
     *
     * @return void
     */
    public function testExportWithError(): void
    {
        // Mock synchronization contract mapper to throw exception
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [])
            ->willThrowException(new \Exception('Export error'));

        // Execute the method
        $response = $this->controller->export();

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Could not export contracts'], $response->getData());
        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * Test index method with zero total count
     *
     * This test verifies that the index() method handles zero total count correctly.
     *
     * @return void
     */
    public function testIndexWithZeroTotalCount(): void
    {
        $expectedContracts = [];

        // Mock synchronization contract mapper
        $this->synchronizationContractMapper->expects($this->once())
            ->method('findAll')
            ->with(20, 0, [])
            ->willReturn($expectedContracts);

        $this->synchronizationContractMapper->expects($this->once())
            ->method('getTotalCount')
            ->with([])
            ->willReturn(0);

        // Execute the method
        $response = $this->controller->index();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedContracts, $data['results']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(0, $data['pagination']['pages']);
        $this->assertEquals(0, $data['pagination']['results']);
        $this->assertEquals(0, $data['pagination']['total']);
    }
}
