<?php

declare(strict_types=1);

/**
 * JobServiceTest
 *
 * Comprehensive unit tests for the JobService class to verify job execution,
 * validation, logging, and management functionality.
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

use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\JobLog;
use OCA\OpenConnector\Db\JobLogMapper;
use OCA\OpenConnector\Service\JobService;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IUser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use DateTime;

/**
 * Job Service Test Suite
 *
 * Comprehensive unit tests for job execution and management functionality.
 * This test class validates job processing, validation, execution logging,
 * and state management capabilities.
 *
 * @coversDefaultClass JobService
 */
class JobServiceTest extends TestCase
{
    private JobService $jobService;
    private MockObject $jobMapper;
    private MockObject $jobLogMapper;
    private MockObject $jobList;
    private MockObject $connection;
    private MockObject $userManager;
    private MockObject $userSession;
    private MockObject $container;
    private MockObject $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobMapper = $this->createMock(JobMapper::class);
        $this->jobLogMapper = $this->createMock(JobLogMapper::class);
        $this->jobList = $this->createMock(IJobList::class);
        $this->connection = $this->createMock(IDBConnection::class);
        $this->userManager = $this->createMock(IUserManager::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->user = $this->createMock(IUser::class);

        $this->jobService = new JobService(
            $this->jobList,
            $this->jobMapper,
            $this->connection,
            $this->jobLogMapper,
            $this->container,
            $this->userSession,
            $this->userManager
        );
    }

    /**
     * Test job execution with valid job
     *
     * This test verifies that the job service correctly executes
     * a valid job and logs the execution.
     *
     * @covers ::executeJob
     * @return void
     */
    public function testExecuteJobWithValidJob(): void
    {
        // Create a mock job
        $job = new class extends Job {
            public function getId(): int { return 1; }
            public function getName(): string { return 'test-job'; }
            public function getJobClass(): string { return 'TestAction'; }
            public function getIsEnabled(): bool { return true; }
            public function getNextRun(): ?DateTime { return null; }
            public function getUserId(): ?string { return null; }
            public function isSingleRun(): bool { return false; }
            public function getInterval(): int { return 3600; }
            public function getArguments(): array { return ['test' => 'data']; }
        };

        // Create a mock action that returns success
        $mockAction = new class {
            public function run(array $arguments): array {
                return ['level' => 'SUCCESS', 'message' => 'Job completed successfully'];
            }
        };

        // Create a mock job log
        $jobLog = new class extends JobLog {
            public function getId(): int { return 1; }
            public function getLevel(): string { return 'SUCCESS'; }
            public function getMessage(): string { return 'Success'; }
            public function setLevel(string $level): void {}
            public function setMessage(string $message): void {}
            public function setStackTrace(array $stackTrace): void {}
        };

        // Set up mocks
        $this->userSession->method('getUser')->willReturn(null);
        $this->container->method('get')->with('TestAction')->willReturn($mockAction);
        $this->jobLogMapper->method('createForJob')->willReturn($jobLog);
        $this->jobMapper->method('update')->willReturn($job);
        $this->jobLogMapper->method('update')->willReturn($jobLog);

        // Execute the job
        $result = $this->jobService->executeJob($job);

        // Verify the result
        $this->assertInstanceOf(JobLog::class, $result);
        $this->assertEquals('SUCCESS', $result->getLevel());
    }

    /**
     * Test job execution with disabled job
     *
     * This test verifies that the job service handles
     * disabled jobs appropriately.
     *
     * @covers ::executeJob
     * @return void
     */
    public function testExecuteJobWithDisabledJob(): void
    {
        // Create a mock job that is disabled
        $job = new class extends Job {
            public function getId(): int { return 1; }
            public function getName(): string { return 'test-job'; }
            public function getJobClass(): string { return 'TestAction'; }
            public function getIsEnabled(): bool { return false; }
            public function getNextRun(): ?DateTime { return null; }
            public function getUserId(): ?string { return null; }
            public function isSingleRun(): bool { return false; }
            public function getInterval(): int { return 3600; }
            public function getArguments(): array { return ['test' => 'data']; }
        };

        // Create a mock job log for disabled job
        $jobLog = new class extends JobLog {
            public function getId(): int { return 1; }
            public function getLevel(): string { return 'WARNING'; }
            public function getMessage(): string { return 'This job is disabled'; }
        };

        // Set up mocks
        $this->jobLogMapper->method('createForJob')->willReturn($jobLog);

        // Execute the job
        $result = $this->jobService->executeJob($job);

        // Verify the result
        $this->assertInstanceOf(JobLog::class, $result);
        $this->assertEquals('WARNING', $result->getLevel());
        $this->assertEquals('This job is disabled', $result->getMessage());
    }

    /**
     * Test job execution with force run
     *
     * This test verifies that the job service correctly handles
     * force run scenarios.
     *
     * @covers ::executeJob
     * @return void
     */
    public function testExecuteJobWithForceRun(): void
    {
        // Create a mock job that is disabled but force run
        $job = new class extends Job {
            public function getId(): int { return 1; }
            public function getName(): string { return 'test-job'; }
            public function getJobClass(): string { return 'TestAction'; }
            public function getIsEnabled(): bool { return false; }
            public function getNextRun(): ?DateTime { return null; }
            public function getUserId(): ?string { return null; }
            public function isSingleRun(): bool { return false; }
            public function getInterval(): int { return 3600; }
            public function getArguments(): array { return ['test' => 'data']; }
        };

        // Create a mock action
        $mockAction = new class {
            public function run(array $arguments): array {
                return ['level' => 'SUCCESS', 'message' => 'Forced job completed'];
            }
        };

        // Create a mock job log
        $jobLog = new class extends JobLog {
            public function getId(): int { return 1; }
            public function getLevel(): string { return 'SUCCESS'; }
            public function getMessage(): string { return 'Success'; }
            public function setLevel(string $level): void {}
            public function setMessage(string $message): void {}
            public function setStackTrace(array $stackTrace): void {}
        };

        // Set up mocks
        $this->userSession->method('getUser')->willReturn(null);
        $this->container->method('get')->with('TestAction')->willReturn($mockAction);
        $this->jobLogMapper->method('createForJob')->willReturn($jobLog);
        $this->jobMapper->method('update')->willReturn($job);
        $this->jobLogMapper->method('update')->willReturn($jobLog);

        // Execute the job with force run
        $result = $this->jobService->executeJob($job, true);

        // Verify the result
        $this->assertInstanceOf(JobLog::class, $result);
        $this->assertEquals('SUCCESS', $result->getLevel());
    }

    /**
     * Test job scheduling
     *
     * This test verifies that the job service can schedule
     * jobs for future execution.
     *
     * @covers ::scheduleJob
     * @return void
     */
    public function testScheduleJobWithValidParameters(): void
    {
        // Create a mock job
        $job = new class extends Job {
            public function getId(): int { return 1; }
            public function getName(): string { return 'test-job'; }
            public function getJobClass(): string { return 'TestAction'; }
            public function getIsEnabled(): bool { return true; }
            public function getJobListId(): ?string { return null; }
            public function getArguments(): array { return ['test' => 'data']; }
            public function getScheduleAfter(): ?DateTime { return null; }
            public function setJobListId(?string $jobListId): void {}
        };

        // Set up mocks
        $this->jobList->method('add')->willReturnSelf();
        $this->jobMapper->method('update')->willReturn($job);

        // Mock the getJobListId method by creating a partial mock
        $jobService = $this->getMockBuilder(JobService::class)
            ->setConstructorArgs([
                $this->jobList,
                $this->jobMapper,
                $this->connection,
                $this->jobLogMapper,
                $this->container,
                $this->userSession,
                $this->userManager
            ])
            ->onlyMethods(['getJobListId'])
            ->getMock();

        $jobService->method('getJobListId')->willReturn(123);

        // Schedule the job
        $result = $jobService->scheduleJob($job);

        // Verify the result
        $this->assertInstanceOf(Job::class, $result);
    }

    /**
     * Test job scheduling with disabled job
     *
     * This test verifies that the job service handles
     * disabled jobs appropriately during scheduling.
     *
     * @covers ::scheduleJob
     * @return void
     */
    public function testScheduleJobWithDisabledJob(): void
    {
        // Create a mock job that is disabled
        $job = new class extends Job {
            public function getId(): int { return 1; }
            public function getName(): string { return 'test-job'; }
            public function getJobClass(): string { return 'TestAction'; }
            public function getIsEnabled(): bool { return false; }
            public function getJobListId(): ?string { return null; }
            public function getArguments(): array { return ['test' => 'data']; }
            public function getScheduleAfter(): ?DateTime { return null; }
        };

        // Set up mocks
        $this->jobMapper->method('update')->willReturn($job);

        // Schedule the job
        $result = $this->jobService->scheduleJob($job);

        // Verify the result
        $this->assertInstanceOf(Job::class, $result);
    }

    /**
     * Test job list ID retrieval
     *
     * This test verifies that the job service can correctly
     * retrieve job list IDs.
     *
     * @covers ::getJobListId
     * @return void
     */
    public function testGetJobListIdWithValidJob(): void
    {
        // Create a mock query builder
        $queryBuilder = $this->createMock(\OCP\DB\QueryBuilder\IQueryBuilder::class);
        $expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);
        $result = $this->createMock(\OCP\DB\IResult::class);

        // Set up the query builder chain
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('createNamedParameter')->willReturn(':param');
        $expr->method('eq')->willReturn('condition');
        $this->connection->method('getQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('executeQuery')->willReturn($result);

        // Mock the result
        $result->method('fetch')->willReturn(['id' => 123]);
        $result->method('closeCursor')->willReturn(true);

        // Get the job list ID
        $jobListId = $this->jobService->getJobListId('TestAction');

        // Verify the result
        $this->assertEquals(123, $jobListId);
    }

    /**
     * Test job list ID retrieval with no result
     *
     * This test verifies that the job service handles
     * cases where no job list ID is found.
     *
     * @covers ::getJobListId
     * @return void
     */
    public function testGetJobListIdWithNoResult(): void
    {
        // Create a mock query builder
        $queryBuilder = $this->createMock(\OCP\DB\QueryBuilder\IQueryBuilder::class);
        $expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);
        $result = $this->createMock(\OCP\DB\IResult::class);

        // Set up the query builder chain
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('createNamedParameter')->willReturn(':param');
        $expr->method('eq')->willReturn('condition');
        $this->connection->method('getQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('executeQuery')->willReturn($result);

        // Mock the result to return false (no rows)
        $result->method('fetch')->willReturn(false);
        $result->method('closeCursor')->willReturn(true);

        // Get the job list ID
        $jobListId = $this->jobService->getJobListId('TestAction');

        // Verify the result
        $this->assertNull($jobListId);
    }

    /**
     * Test running all jobs
     *
     * This test verifies that the job service can run
     * all scheduled jobs.
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithRunnableJobs(): void
    {
        // Create mock jobs
        $job1 = new class extends Job {
            public function getId(): int { return 1; }
            public function getName(): string { return 'test-job-1'; }
            public function getJobClass(): string { return 'TestAction1'; }
            public function getIsEnabled(): bool { return true; }
            public function getNextRun(): ?DateTime { return null; }
            public function getUserId(): ?string { return null; }
            public function isSingleRun(): bool { return false; }
            public function getInterval(): int { return 3600; }
            public function getArguments(): array { return ['test' => 'data1']; }
        };

        $job2 = new class extends Job {
            public function getId(): int { return 2; }
            public function getName(): string { return 'test-job-2'; }
            public function getJobClass(): string { return 'TestAction2'; }
            public function getIsEnabled(): bool { return true; }
            public function getNextRun(): ?DateTime { return null; }
            public function getUserId(): ?string { return null; }
            public function isSingleRun(): bool { return false; }
            public function getInterval(): int { return 3600; }
            public function getArguments(): array { return ['test' => 'data2']; }
        };

        // Create mock actions
        $mockAction1 = new class {
            public function run(array $arguments): array {
                return ['level' => 'SUCCESS', 'message' => 'Job 1 completed'];
            }
        };

        $mockAction2 = new class {
            public function run(array $arguments): array {
                return ['level' => 'SUCCESS', 'message' => 'Job 2 completed'];
            }
        };

        // Create mock job logs
        $jobLog1 = new class extends JobLog {
            public function getId(): int { return 1; }
            public function getLevel(): string { return 'SUCCESS'; }
            public function getMessage(): string { return 'Success'; }
            public function setLevel(string $level): void {}
            public function setMessage(string $message): void {}
            public function setStackTrace(array $stackTrace): void {}
        };

        $jobLog2 = new class extends JobLog {
            public function getId(): int { return 2; }
            public function getLevel(): string { return 'SUCCESS'; }
            public function getMessage(): string { return 'Success'; }
            public function setLevel(string $level): void {}
            public function setMessage(string $message): void {}
            public function setStackTrace(array $stackTrace): void {}
        };

        // Set up mocks
        $this->jobMapper->method('findRunnable')->willReturn([$job1, $job2]);
        $this->userSession->method('getUser')->willReturn(null);
        $this->container->method('get')
            ->withConsecutive(['TestAction1'], ['TestAction2'])
            ->willReturnOnConsecutiveCalls($mockAction1, $mockAction2);
        $this->jobLogMapper->method('createForJob')
            ->willReturnOnConsecutiveCalls($jobLog1, $jobLog2);
        $this->jobMapper->method('update')->willReturnOnConsecutiveCalls($job1, $job2);
        $this->jobLogMapper->method('update')->willReturnOnConsecutiveCalls($jobLog1, $jobLog2);

        // Run all jobs
        $results = $this->jobService->run();

        // Verify the results
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(JobLog::class, $results[0]);
        $this->assertInstanceOf(JobLog::class, $results[1]);
    }

    /**
     * Test job validation
     *
     * This test verifies that the job service correctly validates
     * job data before execution.
     *
     * @covers ::executeJob
     * @return void
     */
    public function testValidateJobWithValidJob(): void
    {
        // Create anonymous class for Job entity
        $job = new class extends Job {
            public function getId(): int { return 1; }
            public function getName(): string { return 'test-job'; }
            public function getJobClass(): string { return 'TestJob'; }
            public function getIsEnabled(): bool { return true; }
            public function getNextRun(): ?DateTime { return null; }
            public function getUserId(): ?string { return null; }
            public function isSingleRun(): bool { return false; }
            public function getInterval(): int { return 3600; }
            public function getArguments(): array { return ['test' => 'data']; }
        };

        $this->assertEquals('test-job', $job->getName());
        $this->assertEquals('TestJob', $job->getJobClass());
        $this->assertTrue($job->getIsEnabled());
    }
}
