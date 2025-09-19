<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Synchronization;
use DateTime;
use PHPUnit\Framework\TestCase;

class SynchronizationTest extends TestCase
{
    private Synchronization $synchronization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synchronization = new Synchronization();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Synchronization::class, $this->synchronization);
        $this->assertNull($this->synchronization->getUuid());
        $this->assertNull($this->synchronization->getName());
        $this->assertNull($this->synchronization->getDescription());
        $this->assertNull($this->synchronization->getReference());
        $this->assertEquals('0.0.0', $this->synchronization->getVersion());
        $this->assertNull($this->synchronization->getSourceId());
        $this->assertNull($this->synchronization->getSourceType());
        $this->assertNull($this->synchronization->getSourceHash());
        $this->assertNull($this->synchronization->getSourceHashMapping());
        $this->assertNull($this->synchronization->getSourceTargetMapping());
        $this->assertIsArray($this->synchronization->getSourceConfig());
        $this->assertNull($this->synchronization->getSourceLastChanged());
        $this->assertNull($this->synchronization->getSourceLastChecked());
        $this->assertNull($this->synchronization->getSourceLastSynced());
        $this->assertEquals(1, $this->synchronization->getCurrentPage());
        $this->assertNull($this->synchronization->getTargetId());
        $this->assertNull($this->synchronization->getTargetType());
        $this->assertNull($this->synchronization->getTargetHash());
        $this->assertNull($this->synchronization->getTargetSourceMapping());
        $this->assertIsArray($this->synchronization->getTargetConfig());
        $this->assertNull($this->synchronization->getTargetLastChanged());
        $this->assertNull($this->synchronization->getTargetLastChecked());
        $this->assertNull($this->synchronization->getTargetLastSynced());
        $this->assertNull($this->synchronization->getCreated());
        $this->assertNull($this->synchronization->getUpdated());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->synchronization->setUuid($uuid);
        $this->assertEquals($uuid, $this->synchronization->getUuid());
    }

    public function testName(): void
    {
        $name = 'Test Synchronization';
        $this->synchronization->setName($name);
        $this->assertEquals($name, $this->synchronization->getName());
    }

    public function testDescription(): void
    {
        $description = 'Test Description';
        $this->synchronization->setDescription($description);
        $this->assertEquals($description, $this->synchronization->getDescription());
    }

    public function testReference(): void
    {
        $reference = 'test-reference';
        $this->synchronization->setReference($reference);
        $this->assertEquals($reference, $this->synchronization->getReference());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->synchronization->setVersion($version);
        $this->assertEquals($version, $this->synchronization->getVersion());
    }

    public function testSourceId(): void
    {
        $sourceId = 'source-123';
        $this->synchronization->setSourceId($sourceId);
        $this->assertEquals($sourceId, $this->synchronization->getSourceId());
    }

    public function testSourceType(): void
    {
        $sourceType = 'api';
        $this->synchronization->setSourceType($sourceType);
        $this->assertEquals($sourceType, $this->synchronization->getSourceType());
    }

    public function testSourceHash(): void
    {
        $sourceHash = 'hash123';
        $this->synchronization->setSourceHash($sourceHash);
        $this->assertEquals($sourceHash, $this->synchronization->getSourceHash());
    }

    public function testSourceHashMapping(): void
    {
        $sourceHashMapping = 'mapping-123';
        $this->synchronization->setSourceHashMapping($sourceHashMapping);
        $this->assertEquals($sourceHashMapping, $this->synchronization->getSourceHashMapping());
    }

    public function testSourceTargetMapping(): void
    {
        $sourceTargetMapping = 'mapping-456';
        $this->synchronization->setSourceTargetMapping($sourceTargetMapping);
        $this->assertEquals($sourceTargetMapping, $this->synchronization->getSourceTargetMapping());
    }

    public function testSourceConfig(): void
    {
        $sourceConfig = ['endpoint' => 'https://api.example.com', 'auth' => 'bearer'];
        $this->synchronization->setSourceConfig($sourceConfig);
        $this->assertEquals($sourceConfig, $this->synchronization->getSourceConfig());
    }

    public function testSourceLastChanged(): void
    {
        $sourceLastChanged = new DateTime('2024-01-01 10:00:00');
        $this->synchronization->setSourceLastChanged($sourceLastChanged);
        $this->assertEquals($sourceLastChanged, $this->synchronization->getSourceLastChanged());
    }

    public function testSourceLastChecked(): void
    {
        $sourceLastChecked = new DateTime('2024-01-01 11:00:00');
        $this->synchronization->setSourceLastChecked($sourceLastChecked);
        $this->assertEquals($sourceLastChecked, $this->synchronization->getSourceLastChecked());
    }

    public function testSourceLastSynced(): void
    {
        $sourceLastSynced = new DateTime('2024-01-01 12:00:00');
        $this->synchronization->setSourceLastSynced($sourceLastSynced);
        $this->assertEquals($sourceLastSynced, $this->synchronization->getSourceLastSynced());
    }

    public function testCurrentPage(): void
    {
        $currentPage = 5;
        $this->synchronization->setCurrentPage($currentPage);
        $this->assertEquals($currentPage, $this->synchronization->getCurrentPage());
    }

    public function testTargetId(): void
    {
        $targetId = 'target-123';
        $this->synchronization->setTargetId($targetId);
        $this->assertEquals($targetId, $this->synchronization->getTargetId());
    }

    public function testTargetType(): void
    {
        $targetType = 'database';
        $this->synchronization->setTargetType($targetType);
        $this->assertEquals($targetType, $this->synchronization->getTargetType());
    }

    public function testTargetHash(): void
    {
        $targetHash = 'target-hash-123';
        $this->synchronization->setTargetHash($targetHash);
        $this->assertEquals($targetHash, $this->synchronization->getTargetHash());
    }

    public function testTargetSourceMapping(): void
    {
        $targetSourceMapping = 'mapping-789';
        $this->synchronization->setTargetSourceMapping($targetSourceMapping);
        $this->assertEquals($targetSourceMapping, $this->synchronization->getTargetSourceMapping());
    }

    public function testTargetConfig(): void
    {
        $targetConfig = ['host' => 'localhost', 'port' => 3306];
        $this->synchronization->setTargetConfig($targetConfig);
        $this->assertEquals($targetConfig, $this->synchronization->getTargetConfig());
    }

    public function testTargetLastChanged(): void
    {
        $targetLastChanged = new DateTime('2024-01-02 10:00:00');
        $this->synchronization->setTargetLastChanged($targetLastChanged);
        $this->assertEquals($targetLastChanged, $this->synchronization->getTargetLastChanged());
    }

    public function testTargetLastChecked(): void
    {
        $targetLastChecked = new DateTime('2024-01-02 11:00:00');
        $this->synchronization->setTargetLastChecked($targetLastChecked);
        $this->assertEquals($targetLastChecked, $this->synchronization->getTargetLastChecked());
    }

    public function testTargetLastSynced(): void
    {
        $targetLastSynced = new DateTime('2024-01-02 12:00:00');
        $this->synchronization->setTargetLastSynced($targetLastSynced);
        $this->assertEquals($targetLastSynced, $this->synchronization->getTargetLastSynced());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->synchronization->setCreated($created);
        $this->assertEquals($created, $this->synchronization->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->synchronization->setUpdated($updated);
        $this->assertEquals($updated, $this->synchronization->getUpdated());
    }

    public function testJsonSerialize(): void
    {
        $this->synchronization->setUuid('test-uuid');
        $this->synchronization->setName('Test Sync');
        $this->synchronization->setSourceId('source-123');
        $this->synchronization->setTargetId('target-456');
        
        $json = $this->synchronization->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test Sync', $json['name']);
        $this->assertEquals('source-123', $json['sourceId']);
        $this->assertEquals('target-456', $json['targetId']);
    }

    public function testGetSourceConfigWithNull(): void
    {
        $this->synchronization->setSourceConfig(null);
        $this->assertIsArray($this->synchronization->getSourceConfig());
        $this->assertEmpty($this->synchronization->getSourceConfig());
    }

    public function testGetTargetConfigWithNull(): void
    {
        $this->synchronization->setTargetConfig(null);
        $this->assertIsArray($this->synchronization->getTargetConfig());
        $this->assertEmpty($this->synchronization->getTargetConfig());
    }
}
