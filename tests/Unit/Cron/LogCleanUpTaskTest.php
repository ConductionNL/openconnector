<?php

declare(strict_types=1);

/**
 * LogCleanUpTaskTest
 *
 * Comprehensive unit tests for the LogCleanUpTask class to verify log cleanup
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

use OCA\OpenConnector\Cron\LogCleanUpTask;
use OCA\OpenConnector\Db\CallLogMapper;
use OCA\OpenConnector\Db\JobLogMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Log Cleanup Task Test Suite
 *
 * Comprehensive unit tests for log cleanup background task including
 * execution, error handling, and configuration.
 *
 * @coversDefaultClass LogCleanUpTask
 */
class LogCleanUpTaskTest extends TestCase
{
    private LogCleanUpTask $logCleanUpTask;
    private ITimeFactory|MockObject $timeFactory;
    private CallLogMapper|MockObject $callLogMapper;
    private JobLogMapper|MockObject $jobLogMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeFactory = $this->createMock(ITimeFactory::class);
        $this->callLogMapper = $this->createMock(CallLogMapper::class);
        $this->jobLogMapper = $this->createMock(JobLogMapper::class);
        
        $this->logCleanUpTask = new LogCleanUpTask(
            $this->timeFactory,
            $this->callLogMapper,
            $this->jobLogMapper
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
        $this->assertInstanceOf(LogCleanUpTask::class, $this->logCleanUpTask);
        
        // Verify the job is configured correctly
        // Note: These methods may not be accessible in the test environment
        // The constructor sets the interval and configures time sensitivity and parallel runs
    }

    /**
     * Test run method with successful cleanup
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithSuccessfulCleanup(): void
    {
        $this->callLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(true);

        $this->jobLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(true);

        // The actual implementation doesn't log anything, just calls the methods
        $this->logCleanUpTask->run(null);
    }

    /**
     * Test run method with call log cleanup failure
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithCallLogCleanupFailure(): void
    {
        $this->callLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(false);

        $this->jobLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(true);

        // The actual implementation doesn't handle exceptions or log errors
        // It just calls the methods regardless of their return values
        $this->logCleanUpTask->run(null);
    }

    /**
     * Test run method with job log cleanup failure
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithJobLogCleanupFailure(): void
    {
        $this->callLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(true);

        $this->jobLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(false);

        // The actual implementation doesn't handle exceptions or log errors
        // It just calls the methods regardless of their return values
        $this->logCleanUpTask->run(null);
    }

    /**
     * Test run method with both cleanup failures
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithBothCleanupFailures(): void
    {
        $this->callLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(false);

        $this->jobLogMapper->expects($this->once())
            ->method('clearLogs')
            ->willReturn(false);

        // The actual implementation doesn't handle exceptions or log errors
        // It just calls the methods regardless of their return values
        $this->logCleanUpTask->run(null);
    }

    /**
     * Test run method with different argument types
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithDifferentArguments(): void
    {
        $this->callLogMapper->expects($this->exactly(3))
            ->method('clearLogs')
            ->willReturn(true);

        $this->jobLogMapper->expects($this->exactly(3))
            ->method('clearLogs')
            ->willReturn(false);

        // The actual implementation doesn't log anything, just calls the methods
        // Test with null argument
        $this->logCleanUpTask->run(null);
        
        // Test with empty array
        $this->logCleanUpTask->run([]);
        
        // Test with data array
        $this->logCleanUpTask->run(['test' => 'value']);
    }

    /**
     * Test job configuration
     *
     * @covers ::__construct
     * @return void
     */
    public function testJobConfiguration(): void
    {
        // Note: These methods may not be accessible in the test environment
        // The constructor sets the interval and configures time sensitivity and parallel runs
        $this->assertInstanceOf(LogCleanUpTask::class, $this->logCleanUpTask);
    }

    /**
     * Test job inheritance
     *
     * @covers ::__construct
     * @return void
     */
    public function testJobInheritance(): void
    {
        $this->assertInstanceOf(\OCP\BackgroundJob\TimedJob::class, $this->logCleanUpTask);
        $this->assertInstanceOf(\OCP\BackgroundJob\IJob::class, $this->logCleanUpTask);
    }

    /**
     * Test call log mapper dependency
     *
     * @covers ::__construct
     * @return void
     */
    public function testCallLogMapperDependency(): void
    {
        $reflection = new \ReflectionClass($this->logCleanUpTask);
        $property = $reflection->getProperty('callLogMapper');
        $property->setAccessible(true);
        
        $this->assertSame($this->callLogMapper, $property->getValue($this->logCleanUpTask));
    }

    /**
     * Test job log mapper dependency
     *
     * @covers ::__construct
     * @return void
     */
    public function testJobLogMapperDependency(): void
    {
        $reflection = new \ReflectionClass($this->logCleanUpTask);
        $property = $reflection->getProperty('jobLogMapper');
        $property->setAccessible(true);
        
        $this->assertSame($this->jobLogMapper, $property->getValue($this->logCleanUpTask));
    }

    /**
     * Test time factory dependency
     *
     * @covers ::__construct
     * @return void
     */
    public function testTimeFactoryDependency(): void
    {
        $reflection = new \ReflectionClass($this->logCleanUpTask);
        $parentReflection = $reflection->getParentClass();
        $property = $parentReflection->getProperty('time');
        $property->setAccessible(true);
        
        $this->assertSame($this->timeFactory, $property->getValue($this->logCleanUpTask));
    }
}
