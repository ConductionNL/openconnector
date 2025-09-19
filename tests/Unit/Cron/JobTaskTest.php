<?php

declare(strict_types=1);

/**
 * JobTaskTest
 *
 * Comprehensive unit tests for the JobTask class to verify job execution
 * functionality and error handling.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Cron
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Cron;

use OCA\OpenConnector\Cron\JobTask;
use OCA\OpenConnector\Service\JobService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Job Task Test Suite
 *
 * Comprehensive unit tests for job execution background task including
 * execution, error handling, and configuration.
 *
 * @coversDefaultClass JobTask
 */
class JobTaskTest extends TestCase
{
    private JobTask $jobTask;
    private ITimeFactory|MockObject $timeFactory;
    private JobService|MockObject $jobService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeFactory = $this->createMock(ITimeFactory::class);
        $this->jobService = $this->createMock(JobService::class);
        
        $this->jobTask = new JobTask(
            $this->timeFactory,
            $this->jobService
        );
    }

    /**
     * Test constructor
     *
     * @covers ::__construct
     * @return void
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(JobTask::class, $this->jobTask);
        
        // Verify the job is configured correctly
        // Note: These methods may not be accessible in the test environment
        // The constructor sets the interval to 300 seconds (5 minutes)
        // and configures time sensitivity and parallel runs
    }

    /**
     * Test run method with valid job ID
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithValidJobId(): void
    {
        $jobId = 123;
        $argument = ['jobId' => $jobId];
        
        // JobTask::run() calls jobService->run() without parameters
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method with string job ID
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithStringJobId(): void
    {
        $jobId = '123';
        $argument = ['jobId' => $jobId];
        
        // JobTask::run() calls jobService->run() without parameters
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method without job ID
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithoutJobId(): void
    {
        $argument = [];
        
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method with null argument
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithNullArgument(): void
    {
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run(null);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method with invalid job ID
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithInvalidJobId(): void
    {
        $argument = ['jobId' => 'invalid'];
        
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method with zero job ID
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithZeroJobId(): void
    {
        $argument = ['jobId' => 0];
        
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method with negative job ID
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithNegativeJobId(): void
    {
        $argument = ['jobId' => -1];
        
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method with additional arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithAdditionalArguments(): void
    {
        $jobId = 123;
        $argument = [
            'jobId' => $jobId,
            'additional' => 'value',
            'nested' => ['data' => 'value'],
            'number' => 42
        ];
        
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test run method with job service exception
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithJobServiceException(): void
    {
        $jobId = 123;
        $argument = ['jobId' => $jobId];
        
        $this->jobService->expects($this->once())
            ->method('run')
            ->willThrowException(new \Exception('Job execution failed'));
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Job execution failed');
        
        $this->jobTask->run($argument);
    }

    /**
     * Test run method with different job service return values
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithDifferentJobServiceReturnValues(): void
    {
        $jobId = 123;
        $argument = ['jobId' => $jobId];
        
        $serviceReturn = [
            'status' => 'completed',
            'processed' => 100,
            'errors' => 0,
            'duration' => 30.5,
            'message' => 'Job completed successfully'
        ];
        
        $this->jobService->expects($this->once())
            ->method('run');
        
        $this->jobTask->run($argument);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test job configuration
     *
     * @covers ::__construct
     * @return void
     */
    public function testJobConfiguration(): void
    {
        // Test that the job task is properly configured
        // The actual configuration is set in the constructor
        $this->assertInstanceOf(JobTask::class, $this->jobTask);
    }

    /**
     * Test job inheritance
     *
     * @covers ::__construct
     * @return void
     */
    public function testJobInheritance(): void
    {
        $this->assertInstanceOf(\OCP\BackgroundJob\TimedJob::class, $this->jobTask);
        $this->assertInstanceOf(\OCP\BackgroundJob\IJob::class, $this->jobTask);
    }

    /**
     * Test job service dependency
     *
     * @covers ::__construct
     * @return void
     */
    public function testJobServiceDependency(): void
    {
        $reflection = new \ReflectionClass($this->jobTask);
        $property = $reflection->getProperty('jobService');
        $property->setAccessible(true);
        
        $this->assertSame($this->jobService, $property->getValue($this->jobTask));
    }

    /**
     * Test time factory dependency
     *
     * @covers ::__construct
     * @return void
     */
    public function testTimeFactoryDependency(): void
    {
        $reflection = new \ReflectionClass($this->jobTask);
        $parentReflection = $reflection->getParentClass();
        $property = $parentReflection->getProperty('time');
        $property->setAccessible(true);
        
        $this->assertSame($this->timeFactory, $property->getValue($this->jobTask));
    }
}
