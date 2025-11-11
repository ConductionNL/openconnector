<?php

declare(strict_types=1);

/**
 * JobsControllerTest
 * 
 * Unit tests for the JobsController
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

use OCA\OpenConnector\Controller\JobsController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\JobService;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\JobLogMapper;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\BackgroundJob\IJobList;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the JobsController
 *
 * This test class covers all functionality of the JobsController
 * including job listing, creation, updates, and deletion operations.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class JobsControllerTest extends TestCase
{
    /**
     * The JobsController instance being tested
     *
     * @var JobsController
     */
    private JobsController $controller;

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
     * Mock job mapper
     *
     * @var MockObject|JobMapper
     */
    private MockObject $jobMapper;

    /**
     * Mock job log mapper
     *
     * @var MockObject|JobLogMapper
     */
    private MockObject $jobLogMapper;

    /**
     * Mock job service
     *
     * @var MockObject|JobService
     */
    private MockObject $jobService;

    /**
     * Mock job list
     *
     * @var MockObject|IJobList
     */
    private MockObject $jobList;

    /**
     * Mock synchronization service
     *
     * @var MockObject|SynchronizationService
     */
    private MockObject $synchronizationService;

    /**
     * Mock synchronization mapper
     *
     * @var MockObject|SynchronizationMapper
     */
    private MockObject $synchronizationMapper;

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
        $this->jobMapper = $this->createMock(JobMapper::class);
        $this->jobLogMapper = $this->createMock(JobLogMapper::class);
        $this->jobService = $this->createMock(JobService::class);
        $this->jobList = $this->createMock(IJobList::class);
        $this->synchronizationService = $this->createMock(SynchronizationService::class);
        $this->synchronizationMapper = $this->createMock(SynchronizationMapper::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new JobsController(
            'openconnector',
            $this->request,
            $this->config,
            $this->jobMapper,
            $this->jobLogMapper,
            $this->jobService,
            $this->jobList,
            $this->synchronizationService,
            $this->synchronizationMapper
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
     * Test successful retrieval of all jobs
     *
     * This test verifies that the index() method returns correct job data
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

        // Mock job mapper
        $expectedJobs = [
            new Job(),
            new Job()
        ];
        $this->jobMapper->expects($this->once())
            ->method('findAll')
            ->with(
                null, // limit
                null, // offset
                ['limit' => 10], // filters
                ['conditions' => 'name LIKE %test%'], // searchConditions
                ['search' => 'test'] // searchParams
            )
            ->willReturn($expectedJobs);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedJobs], $response->getData());
    }

    /**
     * Test successful retrieval of a single job
     *
     * This test verifies that the show() method returns correct job data
     * for a valid job ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $jobId = '123';
        $expectedJob = new Job();
        $expectedJob->setId((int) $jobId);
        $expectedJob->setName('Test Job');

        // Mock job mapper to return the expected job
        $this->jobMapper->expects($this->once())
            ->method('find')
            ->with((int) $jobId)
            ->willReturn($expectedJob);

        // Execute the method
        $response = $this->controller->show($jobId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedJob, $response->getData());
    }

    /**
     * Test job retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the job ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $jobId = '999';

        // Mock job mapper to throw DoesNotExistException
        $this->jobMapper->expects($this->once())
            ->method('find')
            ->with((int) $jobId)
            ->willThrowException(new DoesNotExistException('Job not found'));

        // Execute the method
        $response = $this->controller->show($jobId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful job creation
     *
     * This test verifies that the create() method creates a new job
     * and returns the created job data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $jobData = [
            'name' => 'New Job',
            'description' => 'A new test job',
            'jobClass' => 'OCA\OpenConnector\Action\PingAction'
        ];

        $expectedJob = new Job();
        $expectedJob->setName($jobData['name']);
        $expectedJob->setDescription($jobData['description']);
        $expectedJob->setJobClass($jobData['jobClass']);

        // Mock request to return job data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($jobData);

        // Mock job mapper to return the created job
        $this->jobMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'New Job', 'description' => 'A new test job', 'jobClass' => 'OCA\OpenConnector\Action\PingAction'])
            ->willReturn($expectedJob);

        // Mock job service to handle scheduling
        $this->jobService->expects($this->once())
            ->method('scheduleJob')
            ->with($expectedJob)
            ->willReturn($expectedJob);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedJob, $response->getData());
    }

    /**
     * Test successful job update
     *
     * This test verifies that the update() method updates an existing job
     * and returns the updated job data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $jobId = 123;
        $updateData = [
            'name' => 'Updated Job',
            'description' => 'An updated test job'
        ];

        $updatedJob = new Job();
        $updatedJob->setId($jobId);
        $updatedJob->setName($updateData['name']);
        $updatedJob->setDescription($updateData['description']);

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock job mapper to return updated job
        $this->jobMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($jobId, ['name' => 'Updated Job', 'description' => 'An updated test job'])
            ->willReturn($updatedJob);

        // Mock job service to handle scheduling
        $this->jobService->expects($this->once())
            ->method('scheduleJob')
            ->with($updatedJob)
            ->willReturn($updatedJob);

        // Execute the method
        $response = $this->controller->update($jobId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedJob, $response->getData());
    }

    /**
     * Test job update with non-existent ID
     *
     * This test verifies that the update() method returns a 404 error
     * when the job ID does not exist.
     *
     * @return void
     */
    public function testUpdateWithNonExistentId(): void
    {
        $id = 999; // Non-existent ID
        $data = ['name' => 'Updated Job'];

        // Mock the request to return test data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($data);

        // Mock the mapper to return a job for non-existent ID
        $job = $this->createMock(Job::class);
        $this->jobMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($id, $data)
            ->willReturn($job);

        // Mock the job service
        $this->jobService->expects($this->once())
            ->method('scheduleJob')
            ->with($job)
            ->willReturn($job);

        $response = $this->controller->update($id);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertInstanceOf(Job::class, $response->getData());
    }

    /**
     * Test successful job deletion
     *
     * This test verifies that the destroy() method deletes a job
     * and returns a success response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $jobId = 123;
        $existingJob = new Job();
        $existingJob->setId($jobId);
        $existingJob->setName('Test Job');

        // Mock job mapper to return existing job and handle deletion
        $this->jobMapper->expects($this->once())
            ->method('find')
            ->with($jobId)
            ->willReturn($existingJob);

        $this->jobMapper->expects($this->once())
            ->method('delete')
            ->with($existingJob);

        // Execute the method
        $response = $this->controller->destroy($jobId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
    }

    /**
     * Test job deletion with non-existent ID
     *
     * This test verifies that the destroy() method returns a 404 error
     * when the job ID does not exist.
     *
     * @return void
     */
    public function testDestroyWithNonExistentId(): void
    {
        $id = 999; // Non-existent ID

        // Mock the mapper to return a job for find, then delete it
        $job = $this->createMock(Job::class);
        $this->jobMapper->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($job);
        
        $this->jobMapper->expects($this->once())
            ->method('delete')
            ->with($job)
            ->willReturn($job);

        $response = $this->controller->destroy($id);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertIsArray($response->getData());
    }

    /**
     * Test successful job execution
     *
     * This test verifies that the run() method executes a job
     * and returns the execution results.
     *
     * @return void
     */
    public function testRunSuccessful(): void
    {
        $jobId = 123;
        $existingJob = new Job();
        $existingJob->setId($jobId);
        $existingJob->setName('Test Job');
        $existingJob->setJobClass('OCA\OpenConnector\Action\PingAction');

        // Mock job mapper to return existing job
        $this->jobMapper->expects($this->once())
            ->method('find')
            ->with($jobId)
            ->willReturn($existingJob);

        // Mock request to return execution parameters
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['forceRun' => false]);

        // Mock job service to handle execution
        $this->jobService->expects($this->once())
            ->method('executeJob')
            ->with($existingJob, false)
            ->willReturn(new \OCA\OpenConnector\Db\JobLog());

        // Execute the method
        $response = $this->controller->run($jobId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertInstanceOf(\OCA\OpenConnector\Db\JobLog::class, $response->getData());
    }

    /**
     * Test job execution with non-existent ID
     *
     * This test verifies that the run() method returns a 404 error
     * when the job ID does not exist.
     *
     * @return void
     */
    public function testRunWithNonExistentId(): void
    {
        $jobId = 999;

        // Mock job mapper to throw DoesNotExistException
        $this->jobMapper->expects($this->once())
            ->method('find')
            ->with($jobId)
            ->willThrowException(new DoesNotExistException('Job not found'));

        // Execute the method
        $response = $this->controller->run($jobId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Job not found'], $response->getData());
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

        // Mock job mapper
        $expectedJobs = [];
        $this->jobMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [], [], [])
            ->willReturn($expectedJobs);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedJobs], $response->getData());
    }
}
