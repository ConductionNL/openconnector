<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\SynchronizationContract;
use DateTime;
use PHPUnit\Framework\TestCase;

class SynchronizationContractTest extends TestCase
{
    private SynchronizationContract $synchronizationContract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synchronizationContract = new SynchronizationContract();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(SynchronizationContract::class, $this->synchronizationContract);
        $this->assertNull($this->synchronizationContract->getSourceId());
        $this->assertNull($this->synchronizationContract->getSourceHash());
        $this->assertNull($this->synchronizationContract->getUuid());
        $this->assertNull($this->synchronizationContract->getVersion());
        $this->assertNull($this->synchronizationContract->getSynchronizationId());
        $this->assertNull($this->synchronizationContract->getOriginId());
        $this->assertNull($this->synchronizationContract->getOriginHash());
        $this->assertNull($this->synchronizationContract->getSourceLastChanged());
        $this->assertNull($this->synchronizationContract->getSourceLastChecked());
        $this->assertNull($this->synchronizationContract->getSourceLastSynced());
        $this->assertNull($this->synchronizationContract->getTargetId());
        $this->assertNull($this->synchronizationContract->getTargetHash());
        $this->assertNull($this->synchronizationContract->getTargetLastChanged());
        $this->assertNull($this->synchronizationContract->getTargetLastChecked());
        $this->assertNull($this->synchronizationContract->getTargetLastSynced());
        $this->assertNull($this->synchronizationContract->getTargetLastAction());
        $this->assertNull($this->synchronizationContract->getCreated());
        $this->assertNull($this->synchronizationContract->getUpdated());
    }

    public function testSourceId(): void
    {
        $sourceId = 'source-123';
        $this->synchronizationContract->setSourceId($sourceId);
        $this->assertEquals($sourceId, $this->synchronizationContract->getSourceId());
    }

    public function testSourceHash(): void
    {
        $sourceHash = 'hash-123';
        $this->synchronizationContract->setSourceHash($sourceHash);
        $this->assertEquals($sourceHash, $this->synchronizationContract->getSourceHash());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->synchronizationContract->setUuid($uuid);
        $this->assertEquals($uuid, $this->synchronizationContract->getUuid());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->synchronizationContract->setVersion($version);
        $this->assertEquals($version, $this->synchronizationContract->getVersion());
    }

    public function testSynchronizationId(): void
    {
        $synchronizationId = 'sync-123';
        $this->synchronizationContract->setSynchronizationId($synchronizationId);
        $this->assertEquals($synchronizationId, $this->synchronizationContract->getSynchronizationId());
    }

    public function testOriginId(): void
    {
        $originId = 'origin-123';
        $this->synchronizationContract->setOriginId($originId);
        $this->assertEquals($originId, $this->synchronizationContract->getOriginId());
    }

    public function testOriginHash(): void
    {
        $originHash = 'origin-hash-123';
        $this->synchronizationContract->setOriginHash($originHash);
        $this->assertEquals($originHash, $this->synchronizationContract->getOriginHash());
    }

    public function testSourceLastChanged(): void
    {
        $sourceLastChanged = new DateTime('2024-01-01 10:00:00');
        $this->synchronizationContract->setSourceLastChanged($sourceLastChanged);
        $this->assertEquals($sourceLastChanged, $this->synchronizationContract->getSourceLastChanged());
    }

    public function testSourceLastChecked(): void
    {
        $sourceLastChecked = new DateTime('2024-01-01 11:00:00');
        $this->synchronizationContract->setSourceLastChecked($sourceLastChecked);
        $this->assertEquals($sourceLastChecked, $this->synchronizationContract->getSourceLastChecked());
    }

    public function testSourceLastSynced(): void
    {
        $sourceLastSynced = new DateTime('2024-01-01 12:00:00');
        $this->synchronizationContract->setSourceLastSynced($sourceLastSynced);
        $this->assertEquals($sourceLastSynced, $this->synchronizationContract->getSourceLastSynced());
    }

    public function testTargetId(): void
    {
        $targetId = 'target-123';
        $this->synchronizationContract->setTargetId($targetId);
        $this->assertEquals($targetId, $this->synchronizationContract->getTargetId());
    }

    public function testTargetHash(): void
    {
        $targetHash = 'target-hash-123';
        $this->synchronizationContract->setTargetHash($targetHash);
        $this->assertEquals($targetHash, $this->synchronizationContract->getTargetHash());
    }

    public function testTargetLastChanged(): void
    {
        $targetLastChanged = new DateTime('2024-01-02 10:00:00');
        $this->synchronizationContract->setTargetLastChanged($targetLastChanged);
        $this->assertEquals($targetLastChanged, $this->synchronizationContract->getTargetLastChanged());
    }

    public function testTargetLastChecked(): void
    {
        $targetLastChecked = new DateTime('2024-01-02 11:00:00');
        $this->synchronizationContract->setTargetLastChecked($targetLastChecked);
        $this->assertEquals($targetLastChecked, $this->synchronizationContract->getTargetLastChecked());
    }

    public function testTargetLastSynced(): void
    {
        $targetLastSynced = new DateTime('2024-01-02 12:00:00');
        $this->synchronizationContract->setTargetLastSynced($targetLastSynced);
        $this->assertEquals($targetLastSynced, $this->synchronizationContract->getTargetLastSynced());
    }

    public function testTargetLastAction(): void
    {
        $targetLastAction = 'create';
        $this->synchronizationContract->setTargetLastAction($targetLastAction);
        $this->assertEquals($targetLastAction, $this->synchronizationContract->getTargetLastAction());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->synchronizationContract->setCreated($created);
        $this->assertEquals($created, $this->synchronizationContract->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->synchronizationContract->setUpdated($updated);
        $this->assertEquals($updated, $this->synchronizationContract->getUpdated());
    }

    public function testJsonSerialize(): void
    {
        $this->synchronizationContract->setUuid('test-uuid');
        $this->synchronizationContract->setSynchronizationId('sync-123');
        $this->synchronizationContract->setOriginId('origin-456');
        $this->synchronizationContract->setTargetId('target-789');
        
        $json = $this->synchronizationContract->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('sync-123', $json['synchronizationId']);
        $this->assertEquals('origin-456', $json['originId']);
        $this->assertEquals('target-789', $json['targetId']);
    }
}
