<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Job;
use DateTime;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->job = new Job();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Job::class, $this->job);
        $this->assertNull($this->job->getUuid());
        $this->assertNull($this->job->getName());
        $this->assertNull($this->job->getDescription());
        $this->assertNull($this->job->getReference());
        $this->assertEquals('0.0.0', $this->job->getVersion());
        $this->assertEquals('OCA\OpenConnector\Action\PingAction', $this->job->getJobClass());
        $this->assertIsArray($this->job->getArguments());
        $this->assertEquals(3600, $this->job->getInterval());
        $this->assertEquals(3600, $this->job->getExecutionTime());
        $this->assertTrue($this->job->getTimeSensitive());
        $this->assertFalse($this->job->getAllowParallelRuns());
        $this->assertTrue($this->job->getIsEnabled());
        $this->assertFalse($this->job->getSingleRun());
        $this->assertNull($this->job->getScheduleAfter());
        $this->assertNull($this->job->getUserId());
        $this->assertNull($this->job->getJobListId());
        $this->assertEquals(3600, $this->job->getLogRetention());
        $this->assertEquals(86400, $this->job->getErrorRetention());
        $this->assertNull($this->job->getLastRun());
        $this->assertNull($this->job->getNextRun());
        $this->assertNull($this->job->getCreated());
        $this->assertNull($this->job->getUpdated());
        $this->assertNull($this->job->getStatus());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->job->setUuid($uuid);
        $this->assertEquals($uuid, $this->job->getUuid());
    }

    public function testName(): void
    {
        $name = 'Test Job';
        $this->job->setName($name);
        $this->assertEquals($name, $this->job->getName());
    }

    public function testDescription(): void
    {
        $description = 'Test Description';
        $this->job->setDescription($description);
        $this->assertEquals($description, $this->job->getDescription());
    }

    public function testReference(): void
    {
        $reference = 'test-reference';
        $this->job->setReference($reference);
        $this->assertEquals($reference, $this->job->getReference());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->job->setVersion($version);
        $this->assertEquals($version, $this->job->getVersion());
    }

    public function testJobClass(): void
    {
        $jobClass = 'OCA\OpenConnector\Action\CustomAction';
        $this->job->setJobClass($jobClass);
        $this->assertEquals($jobClass, $this->job->getJobClass());
    }

    public function testArguments(): void
    {
        $arguments = ['param1' => 'value1', 'param2' => 'value2'];
        $this->job->setArguments($arguments);
        $this->assertEquals($arguments, $this->job->getArguments());
    }

    public function testInterval(): void
    {
        $interval = 7200;
        $this->job->setInterval($interval);
        $this->assertEquals($interval, $this->job->getInterval());
    }

    public function testExecutionTime(): void
    {
        $executionTime = 1800;
        $this->job->setExecutionTime($executionTime);
        $this->assertEquals($executionTime, $this->job->getExecutionTime());
    }

    public function testTimeSensitive(): void
    {
        $this->job->setTimeSensitive(false);
        $this->assertFalse($this->job->getTimeSensitive());
    }

    public function testAllowParallelRuns(): void
    {
        $this->job->setAllowParallelRuns(true);
        $this->assertTrue($this->job->getAllowParallelRuns());
    }

    public function testIsEnabled(): void
    {
        $this->job->setIsEnabled(false);
        $this->assertFalse($this->job->getIsEnabled());
    }

    public function testSingleRun(): void
    {
        $this->job->setSingleRun(true);
        $this->assertTrue($this->job->getSingleRun());
    }

    public function testScheduleAfter(): void
    {
        $scheduleAfter = new DateTime('2024-12-31 23:59:59');
        $this->job->setScheduleAfter($scheduleAfter);
        $this->assertEquals($scheduleAfter, $this->job->getScheduleAfter());
    }

    public function testUserId(): void
    {
        $userId = 'user123';
        $this->job->setUserId($userId);
        $this->assertEquals($userId, $this->job->getUserId());
    }

    public function testJobListId(): void
    {
        $jobListId = 'job-list-123';
        $this->job->setJobListId($jobListId);
        $this->assertEquals($jobListId, $this->job->getJobListId());
    }

    public function testLogRetention(): void
    {
        $logRetention = 7200;
        $this->job->setLogRetention($logRetention);
        $this->assertEquals($logRetention, $this->job->getLogRetention());
    }

    public function testErrorRetention(): void
    {
        $errorRetention = 172800;
        $this->job->setErrorRetention($errorRetention);
        $this->assertEquals($errorRetention, $this->job->getErrorRetention());
    }

    public function testLastRun(): void
    {
        $lastRun = new DateTime('2024-01-01 10:00:00');
        $this->job->setLastRun($lastRun);
        $this->assertEquals($lastRun, $this->job->getLastRun());
    }

    public function testNextRun(): void
    {
        $nextRun = new DateTime('2024-01-01 11:00:00');
        $this->job->setNextRun($nextRun);
        $this->assertEquals($nextRun, $this->job->getNextRun());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->job->setCreated($created);
        $this->assertEquals($created, $this->job->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->job->setUpdated($updated);
        $this->assertEquals($updated, $this->job->getUpdated());
    }


    public function testStatus(): void
    {
        $status = 'running';
        $this->job->setStatus($status);
        $this->assertEquals($status, $this->job->getStatus());
    }

    public function testSlug(): void
    {
        $slug = 'test-job-slug';
        $this->job->setSlug($slug);
        $this->assertEquals($slug, $this->job->getSlug());
    }

    public function testJsonSerialize(): void
    {
        $this->job->setUuid('test-uuid');
        $this->job->setName('Test Job');
        $this->job->setDescription('Test Description');
        $this->job->setArguments(['param' => 'value']);
        
        $json = $this->job->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test Job', $json['name']);
        $this->assertEquals('Test Description', $json['description']);
        $this->assertEquals(['param' => 'value'], $json['arguments']);
    }

}
