<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\CallLog;
use DateTime;
use PHPUnit\Framework\TestCase;

class CallLogTest extends TestCase
{
    private CallLog $callLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->callLog = new CallLog();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(CallLog::class, $this->callLog);
        $this->assertNull($this->callLog->getUuid());
        $this->assertNull($this->callLog->getStatusCode());
        $this->assertNull($this->callLog->getStatusMessage());
        $this->assertNull($this->callLog->getRequest());
        $this->assertNull($this->callLog->getResponse());
        $this->assertNull($this->callLog->getSourceId());
        $this->assertNull($this->callLog->getActionId());
        $this->assertNull($this->callLog->getSynchronizationId());
        $this->assertNull($this->callLog->getUserId());
        $this->assertNull($this->callLog->getSessionId());
        $this->assertInstanceOf(DateTime::class, $this->callLog->getExpires()); // Constructor sets default
        $this->assertNull($this->callLog->getCreated());
        $this->assertEquals(4096, $this->callLog->getSize());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->callLog->setUuid($uuid);
        $this->assertEquals($uuid, $this->callLog->getUuid());
    }

    public function testStatusCode(): void
    {
        $statusCode = 200;
        $this->callLog->setStatusCode($statusCode);
        $this->assertEquals($statusCode, $this->callLog->getStatusCode());
    }

    public function testStatusMessage(): void
    {
        $statusMessage = 'OK';
        $this->callLog->setStatusMessage($statusMessage);
        $this->assertEquals($statusMessage, $this->callLog->getStatusMessage());
    }

    public function testRequest(): void
    {
        $request = [
            'method' => 'POST',
            'url' => 'https://api.example.com/endpoint',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"test": "data"}'
        ];
        $this->callLog->setRequest($request);
        $this->assertEquals($request, $this->callLog->getRequest());
    }

    public function testResponse(): void
    {
        $response = [
            'status_code' => 200,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"result": "success"}'
        ];
        $this->callLog->setResponse($response);
        $this->assertEquals($response, $this->callLog->getResponse());
    }

    public function testSourceId(): void
    {
        $sourceId = 123;
        $this->callLog->setSourceId($sourceId);
        $this->assertEquals($sourceId, $this->callLog->getSourceId());
    }

    public function testActionId(): void
    {
        $actionId = 456;
        $this->callLog->setActionId($actionId);
        $this->assertEquals($actionId, $this->callLog->getActionId());
    }

    public function testSynchronizationId(): void
    {
        $synchronizationId = 789;
        $this->callLog->setSynchronizationId($synchronizationId);
        $this->assertEquals($synchronizationId, $this->callLog->getSynchronizationId());
    }

    public function testUserId(): void
    {
        $userId = 'user123';
        $this->callLog->setUserId($userId);
        $this->assertEquals($userId, $this->callLog->getUserId());
    }

    public function testSessionId(): void
    {
        $sessionId = 'session123';
        $this->callLog->setSessionId($sessionId);
        $this->assertEquals($sessionId, $this->callLog->getSessionId());
    }

    public function testExpires(): void
    {
        $expires = new DateTime('2024-12-31 23:59:59');
        $this->callLog->setExpires($expires);
        $this->assertEquals($expires, $this->callLog->getExpires());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->callLog->setCreated($created);
        $this->assertEquals($created, $this->callLog->getCreated());
    }

    public function testSize(): void
    {
        $size = 8192;
        $this->callLog->setSize($size);
        $this->assertEquals($size, $this->callLog->getSize());
    }

    public function testJsonSerialize(): void
    {
        $this->callLog->setUuid('test-uuid');
        $this->callLog->setStatusCode(200);
        $this->callLog->setStatusMessage('OK');
        
        $json = $this->callLog->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals(200, $json['statusCode']);
        $this->assertEquals('OK', $json['statusMessage']);
    }

    public function testCalculateSize(): void
    {
        $this->callLog->setRequest(['test' => 'data']);
        $this->callLog->setResponse(['result' => 'success']);
        
        $this->callLog->calculateSize();
        
        $this->assertGreaterThan(0, $this->callLog->getSize());
    }
}
