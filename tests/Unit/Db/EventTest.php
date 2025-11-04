<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Event;
use DateTime;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->event = new Event();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Event::class, $this->event);
        $this->assertNull($this->event->getUuid());
        $this->assertNull($this->event->getSource());
        $this->assertNull($this->event->getType());
        $this->assertEquals('1.0', $this->event->getSpecversion());
        $this->assertNull($this->event->getTime());
        $this->assertEquals('application/json', $this->event->getDatacontenttype());
        $this->assertNull($this->event->getDataschema());
        $this->assertNull($this->event->getSubject());
        $this->assertIsArray($this->event->getData());
        $this->assertNull($this->event->getUserId());
        $this->assertNull($this->event->getCreated());
        $this->assertNull($this->event->getUpdated());
        $this->assertNull($this->event->getProcessed());
        $this->assertEquals('pending', $this->event->getStatus());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->event->setUuid($uuid);
        $this->assertEquals($uuid, $this->event->getUuid());
    }

    public function testSource(): void
    {
        $source = 'https://example.com/source';
        $this->event->setSource($source);
        $this->assertEquals($source, $this->event->getSource());
    }

    public function testType(): void
    {
        $type = 'com.example.object.created';
        $this->event->setType($type);
        $this->assertEquals($type, $this->event->getType());
    }

    public function testSpecversion(): void
    {
        $specversion = '1.0';
        $this->event->setSpecversion($specversion);
        $this->assertEquals($specversion, $this->event->getSpecversion());
    }

    public function testTime(): void
    {
        $time = new DateTime('2024-01-01 00:00:00');
        $this->event->setTime($time);
        $this->assertEquals($time, $this->event->getTime());
    }

    public function testDatacontenttype(): void
    {
        $contentType = 'application/xml';
        $this->event->setDatacontenttype($contentType);
        $this->assertEquals($contentType, $this->event->getDatacontenttype());
    }

    public function testDataschema(): void
    {
        $schema = 'https://example.com/schema';
        $this->event->setDataschema($schema);
        $this->assertEquals($schema, $this->event->getDataschema());
    }

    public function testSubject(): void
    {
        $subject = 'object-123';
        $this->event->setSubject($subject);
        $this->assertEquals($subject, $this->event->getSubject());
    }

    public function testData(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $this->event->setData($data);
        $this->assertEquals($data, $this->event->getData());
    }

    public function testUserId(): void
    {
        $userId = 'user123';
        $this->event->setUserId($userId);
        $this->assertEquals($userId, $this->event->getUserId());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->event->setCreated($created);
        $this->assertEquals($created, $this->event->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->event->setUpdated($updated);
        $this->assertEquals($updated, $this->event->getUpdated());
    }

    public function testProcessed(): void
    {
        $processed = new DateTime('2024-01-03 00:00:00');
        $this->event->setProcessed($processed);
        $this->assertEquals($processed, $this->event->getProcessed());
    }

    public function testStatus(): void
    {
        $status = 'completed';
        $this->event->setStatus($status);
        $this->assertEquals($status, $this->event->getStatus());
    }

    public function testJsonSerialize(): void
    {
        $this->event->setUuid('test-uuid');
        $this->event->setSource('https://example.com');
        $this->event->setType('test.type');
        $this->event->setData(['test' => 'data']);
        
        $json = $this->event->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('https://example.com', $json['source']);
        $this->assertEquals('test.type', $json['type']);
        $this->assertEquals(['test' => 'data'], $json['data']);
    }

    public function testGetDataWithNull(): void
    {
        $this->event->setData(null);
        $this->assertIsArray($this->event->getData());
        $this->assertEmpty($this->event->getData());
    }
}
