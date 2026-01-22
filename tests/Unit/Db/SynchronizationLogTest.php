<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\SynchronizationLog;
use DateTime;
use PHPUnit\Framework\TestCase;

class SynchronizationLogTest extends TestCase
{
    private SynchronizationLog $synchronizationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synchronizationLog = new SynchronizationLog();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(SynchronizationLog::class, $this->synchronizationLog);
        $this->assertNull($this->synchronizationLog->getUuid());
        $this->assertNull($this->synchronizationLog->getMessage());
        $this->assertNull($this->synchronizationLog->getSynchronizationId());
        $this->assertIsArray($this->synchronizationLog->getResult());
        $this->assertNull($this->synchronizationLog->getUserId());
        $this->assertNull($this->synchronizationLog->getSessionId());
        $this->assertFalse($this->synchronizationLog->getTest());
        $this->assertFalse($this->synchronizationLog->getForce());
        $this->assertEquals(0, $this->synchronizationLog->getExecutionTime());
        $this->assertNull($this->synchronizationLog->getCreated());
        $this->assertInstanceOf(DateTime::class, $this->synchronizationLog->getExpires());
        $this->assertEquals(4096, $this->synchronizationLog->getSize());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->synchronizationLog->setUuid($uuid);
        $this->assertEquals($uuid, $this->synchronizationLog->getUuid());
    }

    public function testMessage(): void
    {
        $message = 'Test message';
        $this->synchronizationLog->setMessage($message);
        $this->assertEquals($message, $this->synchronizationLog->getMessage());
    }

    public function testSynchronizationId(): void
    {
        $synchronizationId = 'sync-123';
        $this->synchronizationLog->setSynchronizationId($synchronizationId);
        $this->assertEquals($synchronizationId, $this->synchronizationLog->getSynchronizationId());
    }

    public function testResult(): void
    {
        $result = ['status' => 'success', 'count' => 5];
        $this->synchronizationLog->setResult($result);
        $this->assertEquals($result, $this->synchronizationLog->getResult());
    }

    public function testUserId(): void
    {
        $userId = 'user123';
        $this->synchronizationLog->setUserId($userId);
        $this->assertEquals($userId, $this->synchronizationLog->getUserId());
    }

    public function testSessionId(): void
    {
        $sessionId = 'session123';
        $this->synchronizationLog->setSessionId($sessionId);
        $this->assertEquals($sessionId, $this->synchronizationLog->getSessionId());
    }

    public function testTest(): void
    {
        $this->synchronizationLog->setTest(true);
        $this->assertTrue($this->synchronizationLog->getTest());
    }

    public function testForce(): void
    {
        $this->synchronizationLog->setForce(true);
        $this->assertTrue($this->synchronizationLog->getForce());
    }

    public function testExecutionTime(): void
    {
        $executionTime = 1500;
        $this->synchronizationLog->setExecutionTime($executionTime);
        $this->assertEquals($executionTime, $this->synchronizationLog->getExecutionTime());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->synchronizationLog->setCreated($created);
        $this->assertEquals($created, $this->synchronizationLog->getCreated());
    }

    public function testExpires(): void
    {
        $expires = new DateTime('2024-12-31 23:59:59');
        $this->synchronizationLog->setExpires($expires);
        $this->assertEquals($expires, $this->synchronizationLog->getExpires());
    }

    public function testSize(): void
    {
        $size = 8192;
        $this->synchronizationLog->setSize($size);
        $this->assertEquals($size, $this->synchronizationLog->getSize());
    }

    public function testJsonSerialize(): void
    {
        $this->synchronizationLog->setUuid('test-uuid');
        $this->synchronizationLog->setMessage('Test message');
        $this->synchronizationLog->setSynchronizationId('sync-123');
        $this->synchronizationLog->setResult(['status' => 'success']);
        
        $json = $this->synchronizationLog->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test message', $json['message']);
        $this->assertEquals('sync-123', $json['synchronizationId']);
        $this->assertEquals(['status' => 'success'], $json['result']);
    }

    public function testGetResultWithNull(): void
    {
        $this->synchronizationLog->setResult(null);
        $this->assertIsArray($this->synchronizationLog->getResult());
        $this->assertEmpty($this->synchronizationLog->getResult());
    }
}
