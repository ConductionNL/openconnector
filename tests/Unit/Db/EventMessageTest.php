<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\EventMessage;
use DateTime;
use PHPUnit\Framework\TestCase;

class EventMessageTest extends TestCase
{
    private EventMessage $eventMessage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventMessage = new EventMessage();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(EventMessage::class, $this->eventMessage);
        $this->assertNull($this->eventMessage->getUuid());
        $this->assertNull($this->eventMessage->getEventId());
        $this->assertNull($this->eventMessage->getConsumerId());
        $this->assertNull($this->eventMessage->getSubscriptionId());
        $this->assertEquals('pending', $this->eventMessage->getStatus());
        $this->assertIsArray($this->eventMessage->getPayload());
        $this->assertIsArray($this->eventMessage->getLastResponse());
        $this->assertEquals(0, $this->eventMessage->getRetryCount());
        $this->assertNull($this->eventMessage->getLastAttempt());
        $this->assertNull($this->eventMessage->getNextAttempt());
        $this->assertNull($this->eventMessage->getCreated());
        $this->assertNull($this->eventMessage->getUpdated());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->eventMessage->setUuid($uuid);
        $this->assertEquals($uuid, $this->eventMessage->getUuid());
    }

    public function testEventId(): void
    {
        $eventId = 123;
        $this->eventMessage->setEventId($eventId);
        $this->assertEquals($eventId, $this->eventMessage->getEventId());
    }

    public function testConsumerId(): void
    {
        $consumerId = 456;
        $this->eventMessage->setConsumerId($consumerId);
        $this->assertEquals($consumerId, $this->eventMessage->getConsumerId());
    }

    public function testSubscriptionId(): void
    {
        $subscriptionId = 789;
        $this->eventMessage->setSubscriptionId($subscriptionId);
        $this->assertEquals($subscriptionId, $this->eventMessage->getSubscriptionId());
    }

    public function testStatus(): void
    {
        $status = 'delivered';
        $this->eventMessage->setStatus($status);
        $this->assertEquals($status, $this->eventMessage->getStatus());
    }

    public function testPayload(): void
    {
        $payload = ['message' => 'test data', 'type' => 'notification'];
        $this->eventMessage->setPayload($payload);
        $this->assertEquals($payload, $this->eventMessage->getPayload());
    }

    public function testLastResponse(): void
    {
        $response = ['status' => 200, 'message' => 'success'];
        $this->eventMessage->setLastResponse($response);
        $this->assertEquals($response, $this->eventMessage->getLastResponse());
    }

    public function testRetryCount(): void
    {
        $retryCount = 3;
        $this->eventMessage->setRetryCount($retryCount);
        $this->assertEquals($retryCount, $this->eventMessage->getRetryCount());
    }

    public function testLastAttempt(): void
    {
        $lastAttempt = new DateTime('2024-01-01 10:00:00');
        $this->eventMessage->setLastAttempt($lastAttempt);
        $this->assertEquals($lastAttempt, $this->eventMessage->getLastAttempt());
    }

    public function testNextAttempt(): void
    {
        $nextAttempt = new DateTime('2024-01-01 11:00:00');
        $this->eventMessage->setNextAttempt($nextAttempt);
        $this->assertEquals($nextAttempt, $this->eventMessage->getNextAttempt());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->eventMessage->setCreated($created);
        $this->assertEquals($created, $this->eventMessage->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->eventMessage->setUpdated($updated);
        $this->assertEquals($updated, $this->eventMessage->getUpdated());
    }

    public function testJsonSerialize(): void
    {
        $this->eventMessage->setUuid('test-uuid');
        $this->eventMessage->setEventId(123);
        $this->eventMessage->setConsumerId(456);
        $this->eventMessage->setStatus('delivered');
        $this->eventMessage->setPayload(['test' => 'data']);
        
        $json = $this->eventMessage->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals(123, $json['eventId']);
        $this->assertEquals(456, $json['consumerId']);
        $this->assertEquals('delivered', $json['status']);
        $this->assertEquals(['test' => 'data'], $json['payload']);
    }

    public function testGetPayloadWithNull(): void
    {
        $this->eventMessage->setPayload(null);
        $this->assertIsArray($this->eventMessage->getPayload());
        $this->assertEmpty($this->eventMessage->getPayload());
    }

    public function testGetLastResponseWithNull(): void
    {
        $this->eventMessage->setLastResponse(null);
        $this->assertIsArray($this->eventMessage->getLastResponse());
        $this->assertEmpty($this->eventMessage->getLastResponse());
    }
}
