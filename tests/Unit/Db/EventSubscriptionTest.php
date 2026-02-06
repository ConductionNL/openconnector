<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\EventSubscription;
use DateTime;
use PHPUnit\Framework\TestCase;

class EventSubscriptionTest extends TestCase
{
    private EventSubscription $eventSubscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventSubscription = new EventSubscription();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(EventSubscription::class, $this->eventSubscription);
        $this->assertNull($this->eventSubscription->getUuid());
        $this->assertNull($this->eventSubscription->getReference());
        $this->assertEquals('0.0.0', $this->eventSubscription->getVersion());
        $this->assertNull($this->eventSubscription->getSource());
        $this->assertIsArray($this->eventSubscription->getTypes());
        $this->assertIsArray($this->eventSubscription->getConfig());
        $this->assertIsArray($this->eventSubscription->getFilters());
        $this->assertNull($this->eventSubscription->getSink());
        $this->assertNull($this->eventSubscription->getProtocol());
        $this->assertIsArray($this->eventSubscription->getProtocolSettings());
        $this->assertEquals('push', $this->eventSubscription->getStyle());
        $this->assertEquals('active', $this->eventSubscription->getStatus());
        $this->assertNull($this->eventSubscription->getUserId());
        $this->assertNull($this->eventSubscription->getCreated());
        $this->assertNull($this->eventSubscription->getUpdated());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->eventSubscription->setUuid($uuid);
        $this->assertEquals($uuid, $this->eventSubscription->getUuid());
    }

    public function testReference(): void
    {
        $reference = 'test-reference';
        $this->eventSubscription->setReference($reference);
        $this->assertEquals($reference, $this->eventSubscription->getReference());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->eventSubscription->setVersion($version);
        $this->assertEquals($version, $this->eventSubscription->getVersion());
    }

    public function testSource(): void
    {
        $source = 'https://example.com/source';
        $this->eventSubscription->setSource($source);
        $this->assertEquals($source, $this->eventSubscription->getSource());
    }

    public function testTypes(): void
    {
        $types = ['com.example.object.created', 'com.example.object.updated'];
        $this->eventSubscription->setTypes($types);
        $this->assertEquals($types, $this->eventSubscription->getTypes());
    }

    public function testConfig(): void
    {
        $config = ['timeout' => 30, 'retries' => 3];
        $this->eventSubscription->setConfig($config);
        $this->assertEquals($config, $this->eventSubscription->getConfig());
    }

    public function testFilters(): void
    {
        $filters = ['subject' => 'user.*', 'type' => 'com.example.*'];
        $this->eventSubscription->setFilters($filters);
        $this->assertEquals($filters, $this->eventSubscription->getFilters());
    }

    public function testSink(): void
    {
        $sink = 'https://consumer.example.com/webhook';
        $this->eventSubscription->setSink($sink);
        $this->assertEquals($sink, $this->eventSubscription->getSink());
    }

    public function testProtocol(): void
    {
        $protocol = 'HTTP';
        $this->eventSubscription->setProtocol($protocol);
        $this->assertEquals($protocol, $this->eventSubscription->getProtocol());
    }

    public function testProtocolSettings(): void
    {
        $settings = ['headers' => ['Authorization' => 'Bearer token']];
        $this->eventSubscription->setProtocolSettings($settings);
        $this->assertEquals($settings, $this->eventSubscription->getProtocolSettings());
    }

    public function testStyle(): void
    {
        $style = 'pull';
        $this->eventSubscription->setStyle($style);
        $this->assertEquals($style, $this->eventSubscription->getStyle());
    }

    public function testStatus(): void
    {
        $status = 'inactive';
        $this->eventSubscription->setStatus($status);
        $this->assertEquals($status, $this->eventSubscription->getStatus());
    }

    public function testUserId(): void
    {
        $userId = 'user123';
        $this->eventSubscription->setUserId($userId);
        $this->assertEquals($userId, $this->eventSubscription->getUserId());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->eventSubscription->setCreated($created);
        $this->assertEquals($created, $this->eventSubscription->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->eventSubscription->setUpdated($updated);
        $this->assertEquals($updated, $this->eventSubscription->getUpdated());
    }

    public function testJsonSerialize(): void
    {
        $this->eventSubscription->setUuid('test-uuid');
        $this->eventSubscription->setSource('https://example.com/source');
        $this->eventSubscription->setTypes(['com.example.event']);
        $this->eventSubscription->setSink('https://consumer.example.com/webhook');
        $this->eventSubscription->setProtocol('HTTP');
        
        $json = $this->eventSubscription->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('https://example.com/source', $json['source']);
        $this->assertEquals(['com.example.event'], $json['types']);
        $this->assertEquals('https://consumer.example.com/webhook', $json['sink']);
        $this->assertEquals('HTTP', $json['protocol']);
    }

    public function testGetTypesWithNull(): void
    {
        $this->eventSubscription->setTypes(null);
        $this->assertIsArray($this->eventSubscription->getTypes());
        $this->assertEmpty($this->eventSubscription->getTypes());
    }

    public function testGetConfigWithNull(): void
    {
        $this->eventSubscription->setConfig(null);
        $this->assertIsArray($this->eventSubscription->getConfig());
        $this->assertEmpty($this->eventSubscription->getConfig());
    }

    public function testGetFiltersWithNull(): void
    {
        $this->eventSubscription->setFilters(null);
        $this->assertIsArray($this->eventSubscription->getFilters());
        $this->assertEmpty($this->eventSubscription->getFilters());
    }

    public function testGetProtocolSettingsWithNull(): void
    {
        $this->eventSubscription->setProtocolSettings(null);
        $this->assertIsArray($this->eventSubscription->getProtocolSettings());
        $this->assertEmpty($this->eventSubscription->getProtocolSettings());
    }
}
