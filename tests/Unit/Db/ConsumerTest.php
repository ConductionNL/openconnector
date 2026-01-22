<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Consumer;
use DateTime;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    private Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consumer = new Consumer();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Consumer::class, $this->consumer);
        $this->assertNull($this->consumer->getUuid());
        $this->assertNull($this->consumer->getName());
        $this->assertNull($this->consumer->getDescription());
        $this->assertIsArray($this->consumer->getDomains());
        $this->assertIsArray($this->consumer->getIps());
        $this->assertNull($this->consumer->getAuthorizationType());
        $this->assertIsArray($this->consumer->getAuthorizationConfiguration());
        $this->assertNull($this->consumer->getCreated());
        $this->assertNull($this->consumer->getUpdated());
        $this->assertNull($this->consumer->getUserId());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->consumer->setUuid($uuid);
        $this->assertEquals($uuid, $this->consumer->getUuid());
    }

    public function testName(): void
    {
        $name = 'Test Consumer';
        $this->consumer->setName($name);
        $this->assertEquals($name, $this->consumer->getName());
    }

    public function testDescription(): void
    {
        $description = 'Test Description';
        $this->consumer->setDescription($description);
        $this->assertEquals($description, $this->consumer->getDescription());
    }

    public function testDomains(): void
    {
        $domains = ['example.com', 'test.com'];
        $this->consumer->setDomains($domains);
        $this->assertEquals($domains, $this->consumer->getDomains());
    }

    public function testIps(): void
    {
        $ips = ['192.168.1.1', '10.0.0.1'];
        $this->consumer->setIps($ips);
        $this->assertEquals($ips, $this->consumer->getIps());
    }

    public function testAuthorizationType(): void
    {
        $authType = 'bearer';
        $this->consumer->setAuthorizationType($authType);
        $this->assertEquals($authType, $this->consumer->getAuthorizationType());
    }

    public function testAuthorizationConfiguration(): void
    {
        $config = ['token' => 'test-token'];
        $this->consumer->setAuthorizationConfiguration($config);
        $this->assertEquals($config, $this->consumer->getAuthorizationConfiguration());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->consumer->setCreated($created);
        $this->assertEquals($created, $this->consumer->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->consumer->setUpdated($updated);
        $this->assertEquals($updated, $this->consumer->getUpdated());
    }

    public function testUserId(): void
    {
        $userId = 'user123';
        $this->consumer->setUserId($userId);
        $this->assertEquals($userId, $this->consumer->getUserId());
    }

    public function testJsonSerialize(): void
    {
        $this->consumer->setUuid('test-uuid');
        $this->consumer->setName('Test Consumer');
        $this->consumer->setDescription('Test Description');
        
        $json = $this->consumer->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test Consumer', $json['name']);
        $this->assertEquals('Test Description', $json['description']);
    }

    public function testGetDomainsWithNull(): void
    {
        $this->consumer->setDomains(null);
        $this->assertIsArray($this->consumer->getDomains());
        $this->assertEmpty($this->consumer->getDomains());
    }

    public function testGetIpsWithNull(): void
    {
        $this->consumer->setIps(null);
        $this->assertIsArray($this->consumer->getIps());
        $this->assertEmpty($this->consumer->getIps());
    }

    public function testGetAuthorizationConfigurationWithNull(): void
    {
        $this->consumer->setAuthorizationConfiguration(null);
        $this->assertIsArray($this->consumer->getAuthorizationConfiguration());
        $this->assertEmpty($this->consumer->getAuthorizationConfiguration());
    }
}
