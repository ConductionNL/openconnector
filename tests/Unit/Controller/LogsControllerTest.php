<?php

declare(strict_types=1);

/**
 * LogsControllerTest
 * 
 * Unit tests for the LogsController
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

use OCA\OpenConnector\Controller\LogsController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Db\SynchronizationLog;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the LogsController
 *
 * This test class covers all functionality of the LogsController
 * including log listing, retrieval, deletion, statistics, and export.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class LogsControllerTest extends TestCase
{
    /**
     * The LogsController instance being tested
     *
     * @var LogsController
     */
    private LogsController $controller;

    /**
     * Mock request object
     *
     * @var MockObject|IRequest
     */
    private MockObject $request;

    /**
     * Mock synchronization log mapper
     *
     * @var MockObject|SynchronizationLogMapper
     */
    private MockObject $synchronizationLogMapper;

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
        $this->synchronizationLogMapper = $this->createMock(SynchronizationLogMapper::class);
        $this->objectService = $this->createMock(ObjectService::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new LogsController(
            'openconnector',
            $this->request,
            $this->synchronizationLogMapper,
            $this->objectService
        );
    }

    /**
     * Test successful retrieval of all logs with default parameters
     *
     * This test verifies that the index() method returns correct log data
     * with default pagination parameters.
     *
     * @return void
     */
    public function testIndexSuccessfulWithDefaultParameters(): void
    {
        $expectedLogs = [
            new SynchronizationLog(),
            new SynchronizationLog()
        ];

        // Mock synchronization log mapper
        $this->synchronizationLogMapper->expects($this->once())
            ->method('findAll')
            ->with(20, 0, [])
            ->willReturn($expectedLogs);

        $this->synchronizationLogMapper->expects($this->once())
            ->method('getTotalCount')
            ->with([])
            ->willReturn(50);

        // Execute the method
        $response = $this->controller->index();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedLogs, $data['results']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(3, $data['pagination']['pages']);
        $this->assertEquals(2, $data['pagination']['results']);
        $this->assertEquals(50, $data['pagination']['total']);
    }

    /**
     * Test successful retrieval of logs with custom parameters
     *
     * This test verifies that the index() method returns correct log data
     * with custom pagination and filtering parameters.
     *
     * @return void
     */
    public function testIndexSuccessfulWithCustomParameters(): void
    {
        $expectedLogs = [
            new SynchronizationLog()
        ];

        $filters = [
            'level' => 'error',
            'message' => 'test',
            'synchronization_id' => '123',
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31'
        ];

        // Mock synchronization log mapper
        $this->synchronizationLogMapper->expects($this->once())
            ->method('findAll')
            ->with(10, 20, $filters)
            ->willReturn($expectedLogs);

        $this->synchronizationLogMapper->expects($this->once())
            ->method('getTotalCount')
            ->with($filters)
            ->willReturn(25);

        // Execute the method
        $response = $this->controller->index(10, 20, 'error', 'test', '123', '2024-01-01', '2024-01-31');

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedLogs, $data['results']);
        $this->assertEquals(3, $data['pagination']['page']);
        $this->assertEquals(3, $data['pagination']['pages']);
        $this->assertEquals(1, $data['pagination']['results']);
        $this->assertEquals(25, $data['pagination']['total']);
    }

    /**
     * Test successful retrieval of a single log
     *
     * This test verifies that the show() method returns correct log data
     * for a valid log ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $logId = '123';
        $expectedLog = new SynchronizationLog();
        $expectedLog->setId((int) $logId);
        $expectedLog->setMessage('Test error message');

        // Mock synchronization log mapper to return the expected log
        $this->synchronizationLogMapper->expects($this->once())
            ->method('find')
            ->with((int) $logId)
            ->willReturn($expectedLog);

        // Execute the method
        $response = $this->controller->show($logId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedLog, $response->getData());
    }

    /**
     * Test log retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the log ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $logId = '999';

        // Mock synchronization log mapper to throw exception
        $this->synchronizationLogMapper->expects($this->once())
            ->method('find')
            ->with((int) $logId)
            ->willThrowException(new \Exception('Log not found'));

        // Execute the method
        $response = $this->controller->show($logId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Log not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful log deletion
     *
     * This test verifies that the destroy() method deletes a log
     * and returns a success response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $logId = '123';
        $existingLog = new SynchronizationLog();
        $existingLog->setId((int) $logId);

        // Mock synchronization log mapper to return existing log and handle deletion
        $this->synchronizationLogMapper->expects($this->once())
            ->method('find')
            ->with((int) $logId)
            ->willReturn($existingLog);

        $this->synchronizationLogMapper->expects($this->once())
            ->method('delete')
            ->with($existingLog);

        // Execute the method
        $response = $this->controller->destroy($logId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Log deleted successfully'], $response->getData());
    }

    /**
     * Test log deletion with non-existent ID
     *
     * This test verifies that the destroy() method returns a 404 error
     * when the log ID does not exist.
     *
     * @return void
     */
    public function testDestroyWithNonExistentId(): void
    {
        $logId = '999';

        // Mock synchronization log mapper to throw exception
        $this->synchronizationLogMapper->expects($this->once())
            ->method('find')
            ->with((int) $logId)
            ->willThrowException(new \Exception('Log not found'));

        // Execute the method
        $response = $this->controller->destroy($logId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Log not found or could not be deleted'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful statistics retrieval
     *
     * This test verifies that the statistics() method returns correct
     * statistical information about logs.
     *
     * @return void
     */
    public function testStatisticsSuccessful(): void
    {
        // Mock synchronization log mapper to return counts
        $this->synchronizationLogMapper->expects($this->exactly(5))
            ->method('getTotalCount')
            ->withConsecutive(
                [['level' => 'error']],
                [['level' => 'warning']],
                [['level' => 'info']],
                [['level' => 'success']],
                [['level' => 'debug']]
            )
            ->willReturnOnConsecutiveCalls(10, 5, 20, 15, 2);

        // Execute the method
        $response = $this->controller->statistics();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals(10, $data['errorCount']);
        $this->assertEquals(5, $data['warningCount']);
        $this->assertEquals(20, $data['infoCount']);
        $this->assertEquals(15, $data['successCount']);
        $this->assertEquals(2, $data['debugCount']);
        $this->assertEquals([
            'error' => 10,
            'warning' => 5,
            'info' => 20,
            'success' => 15,
            'debug' => 2,
        ], $data['levelDistribution']);
    }

    /**
     * Test statistics retrieval with exception
     *
     * This test verifies that the statistics() method handles exceptions correctly.
     *
     * @return void
     */
    public function testStatisticsWithException(): void
    {
        // Mock synchronization log mapper to throw exception
        $this->synchronizationLogMapper->expects($this->once())
            ->method('getTotalCount')
            ->willThrowException(new \Exception('Database error'));

        // Execute the method
        $response = $this->controller->statistics();

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Could not fetch statistics'], $response->getData());
        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * Test successful log export
     *
     * This test verifies that the export() method returns correct CSV data
     * for log export.
     *
     * @return void
     */
    public function testExportSuccessful(): void
    {
        $expectedLogs = [
            new SynchronizationLog(),
            new SynchronizationLog()
        ];

        // Set up the first log
        $expectedLogs[0]->setId(1);
        $expectedLogs[0]->setUuid('uuid-1');
        $expectedLogs[0]->setMessage('Test message 1');
        $expectedLogs[0]->setSynchronizationId('sync-1');
        $expectedLogs[0]->setUserId('user-1');
        $expectedLogs[0]->setSessionId('session-1');
        $expectedLogs[0]->setCreated(new \DateTime('2024-01-01 10:00:00'));
        $expectedLogs[0]->setExpires(new \DateTime('2024-01-02 10:00:00'));

        // Set up the second log
        $expectedLogs[1]->setId(2);
        $expectedLogs[1]->setUuid('uuid-2');
        $expectedLogs[1]->setMessage('Test message 2');
        $expectedLogs[1]->setSynchronizationId('sync-2');
        $expectedLogs[1]->setUserId('user-2');
        $expectedLogs[1]->setSessionId('session-2');
        $expectedLogs[1]->setCreated(new \DateTime('2024-01-03 10:00:00'));
        $expectedLogs[1]->setExpires(new \DateTime('2024-01-04 10:00:00'));

        // Mock synchronization log mapper
        $this->synchronizationLogMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [])
            ->willReturn($expectedLogs);

        // Execute the method
        $response = $this->controller->export();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        
        $this->assertArrayHasKey('filename', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertArrayHasKey('contentType', $data);
        $this->assertEquals('text/csv', $data['contentType']);
        $this->assertStringContainsString('synchronization_logs_', $data['filename']);
        $this->assertStringContainsString('.csv', $data['filename']);
        
        // Check CSV content
        $this->assertStringContainsString('ID,UUID,Message,Synchronization ID,User ID,Session ID,Created,Expires', $data['content']);
        $this->assertStringContainsString('1,uuid-1,"Test message 1",sync-1,user-1,session-1,2024-01-01 10:00:00,2024-01-02 10:00:00', $data['content']);
        $this->assertStringContainsString('2,uuid-2,"Test message 2",sync-2,user-2,session-2,2024-01-03 10:00:00,2024-01-04 10:00:00', $data['content']);
    }

    /**
     * Test log export with exception
     *
     * This test verifies that the export() method handles exceptions correctly.
     *
     * @return void
     */
    public function testExportWithException(): void
    {
        // Mock synchronization log mapper to throw exception
        $this->synchronizationLogMapper->expects($this->once())
            ->method('findAll')
            ->willThrowException(new \Exception('Database error'));

        // Execute the method
        $response = $this->controller->export();

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Could not export logs'], $response->getData());
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
        $expectedLogs = [];

        // Mock synchronization log mapper
        $this->synchronizationLogMapper->expects($this->once())
            ->method('findAll')
            ->with(20, 0, [])
            ->willReturn($expectedLogs);

        $this->synchronizationLogMapper->expects($this->once())
            ->method('getTotalCount')
            ->with([])
            ->willReturn(0);

        // Execute the method
        $response = $this->controller->index();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedLogs, $data['results']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(0, $data['pagination']['pages']);
        $this->assertEquals(0, $data['pagination']['results']);
        $this->assertEquals(0, $data['pagination']['total']);
    }

    /**
     * Test index method with custom limit and offset
     *
     * This test verifies that the index() method handles custom limit and offset correctly.
     *
     * @return void
     */
    public function testIndexWithCustomLimitAndOffset(): void
    {
        $expectedLogs = [new SynchronizationLog()];

        // Mock synchronization log mapper
        $this->synchronizationLogMapper->expects($this->once())
            ->method('findAll')
            ->with(5, 10, [])
            ->willReturn($expectedLogs);

        $this->synchronizationLogMapper->expects($this->once())
            ->method('getTotalCount')
            ->with([])
            ->willReturn(25);

        // Execute the method
        $response = $this->controller->index(5, 10);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedLogs, $data['results']);
        $this->assertEquals(3, $data['pagination']['page']);
        $this->assertEquals(5, $data['pagination']['pages']);
        $this->assertEquals(1, $data['pagination']['results']);
        $this->assertEquals(25, $data['pagination']['total']);
    }
}
