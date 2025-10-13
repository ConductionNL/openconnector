<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\SynchronizationContractLog;
use DateTime;
use PHPUnit\Framework\TestCase;

class SynchronizationContractLogTest extends TestCase
{
    private SynchronizationContractLog $synchronizationContractLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synchronizationContractLog = new SynchronizationContractLog();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(SynchronizationContractLog::class, $this->synchronizationContractLog);
        $this->assertNull($this->synchronizationContractLog->getUuid());
        $this->assertNull($this->synchronizationContractLog->getMessage());
        $this->assertNull($this->synchronizationContractLog->getSynchronizationId());
        $this->assertNull($this->synchronizationContractLog->getSynchronizationContractId());
        $this->assertNull($this->synchronizationContractLog->getSynchronizationLogId());
        $this->assertIsArray($this->synchronizationContractLog->getSource());
        $this->assertIsArray($this->synchronizationContractLog->getTarget());
        $this->assertNull($this->synchronizationContractLog->getTargetResult());
        $this->assertNull($this->synchronizationContractLog->getUserId());
        $this->assertNull($this->synchronizationContractLog->getSessionId());
        $this->assertFalse($this->synchronizationContractLog->getTest());
        $this->assertFalse($this->synchronizationContractLog->getForce());
        $this->assertInstanceOf(DateTime::class, $this->synchronizationContractLog->getExpires());
        $this->assertNull($this->synchronizationContractLog->getCreated());
        $this->assertEquals(4096, $this->synchronizationContractLog->getSize());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->synchronizationContractLog->setUuid($uuid);
        $this->assertEquals($uuid, $this->synchronizationContractLog->getUuid());
    }

    public function testMessage(): void
    {
        $message = 'Test message';
        $this->synchronizationContractLog->setMessage($message);
        $this->assertEquals($message, $this->synchronizationContractLog->getMessage());
    }

    public function testSynchronizationId(): void
    {
        $synchronizationId = 'sync-123';
        $this->synchronizationContractLog->setSynchronizationId($synchronizationId);
        $this->assertEquals($synchronizationId, $this->synchronizationContractLog->getSynchronizationId());
    }

    public function testSynchronizationContractId(): void
    {
        $synchronizationContractId = 'contract-123';
        $this->synchronizationContractLog->setSynchronizationContractId($synchronizationContractId);
        $this->assertEquals($synchronizationContractId, $this->synchronizationContractLog->getSynchronizationContractId());
    }

    public function testSynchronizationLogId(): void
    {
        $synchronizationLogId = 'log-123';
        $this->synchronizationContractLog->setSynchronizationLogId($synchronizationLogId);
        $this->assertEquals($synchronizationLogId, $this->synchronizationContractLog->getSynchronizationLogId());
    }

    public function testSource(): void
    {
        $source = ['id' => '123', 'name' => 'test'];
        $this->synchronizationContractLog->setSource($source);
        $this->assertEquals($source, $this->synchronizationContractLog->getSource());
    }

    public function testTarget(): void
    {
        $target = ['id' => '456', 'name' => 'target'];
        $this->synchronizationContractLog->setTarget($target);
        $this->assertEquals($target, $this->synchronizationContractLog->getTarget());
    }

    public function testTargetResult(): void
    {
        $targetResult = 'create';
        $this->synchronizationContractLog->setTargetResult($targetResult);
        $this->assertEquals($targetResult, $this->synchronizationContractLog->getTargetResult());
    }

    public function testUserId(): void
    {
        $userId = 'user123';
        $this->synchronizationContractLog->setUserId($userId);
        $this->assertEquals($userId, $this->synchronizationContractLog->getUserId());
    }

    public function testSessionId(): void
    {
        $sessionId = 'session123';
        $this->synchronizationContractLog->setSessionId($sessionId);
        $this->assertEquals($sessionId, $this->synchronizationContractLog->getSessionId());
    }

    public function testTest(): void
    {
        $this->synchronizationContractLog->setTest(true);
        $this->assertTrue($this->synchronizationContractLog->getTest());
    }

    public function testForce(): void
    {
        $this->synchronizationContractLog->setForce(true);
        $this->assertTrue($this->synchronizationContractLog->getForce());
    }

    public function testExpires(): void
    {
        $expires = new DateTime('2024-12-31 23:59:59');
        $this->synchronizationContractLog->setExpires($expires);
        $this->assertEquals($expires, $this->synchronizationContractLog->getExpires());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->synchronizationContractLog->setCreated($created);
        $this->assertEquals($created, $this->synchronizationContractLog->getCreated());
    }

    public function testSize(): void
    {
        $size = 8192;
        $this->synchronizationContractLog->setSize($size);
        $this->assertEquals($size, $this->synchronizationContractLog->getSize());
    }

    public function testJsonSerialize(): void
    {
        $this->synchronizationContractLog->setUuid('test-uuid');
        $this->synchronizationContractLog->setMessage('Test message');
        $this->synchronizationContractLog->setSynchronizationId('sync-123');
        $this->synchronizationContractLog->setTargetResult('create');
        
        $json = $this->synchronizationContractLog->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test message', $json['message']);
        $this->assertEquals('sync-123', $json['synchronizationId']);
        $this->assertEquals('create', $json['targetResult']);
    }

    public function testGetSourceWithNull(): void
    {
        $this->synchronizationContractLog->setSource(null);
        $this->assertNull($this->synchronizationContractLog->getSource());
    }

    public function testGetTargetWithNull(): void
    {
        $this->synchronizationContractLog->setTarget(null);
        $this->assertNull($this->synchronizationContractLog->getTarget());
    }
}
