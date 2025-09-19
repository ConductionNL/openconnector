<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Mapping;
use DateTime;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    private Mapping $mapping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapping = new Mapping();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Mapping::class, $this->mapping);
        $this->assertNull($this->mapping->getUuid());
        $this->assertNull($this->mapping->getReference());
        $this->assertEquals('0.0.0', $this->mapping->getVersion());
        $this->assertNull($this->mapping->getName());
        $this->assertNull($this->mapping->getDescription());
        $this->assertIsArray($this->mapping->getMapping());
        $this->assertIsArray($this->mapping->getUnset());
        $this->assertIsArray($this->mapping->getCast());
        $this->assertNull($this->mapping->getPassThrough());
        $this->assertNull($this->mapping->getDateCreated());
        $this->assertNull($this->mapping->getDateModified());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->mapping->setUuid($uuid);
        $this->assertEquals($uuid, $this->mapping->getUuid());
    }

    public function testReference(): void
    {
        $reference = 'test-reference';
        $this->mapping->setReference($reference);
        $this->assertEquals($reference, $this->mapping->getReference());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->mapping->setVersion($version);
        $this->assertEquals($version, $this->mapping->getVersion());
    }

    public function testName(): void
    {
        $name = 'Test Mapping';
        $this->mapping->setName($name);
        $this->assertEquals($name, $this->mapping->getName());
    }

    public function testDescription(): void
    {
        $description = 'Test Description';
        $this->mapping->setDescription($description);
        $this->assertEquals($description, $this->mapping->getDescription());
    }

    public function testMapping(): void
    {
        $mapping = ['field1' => 'target1', 'field2' => 'target2'];
        $this->mapping->setMapping($mapping);
        $this->assertEquals($mapping, $this->mapping->getMapping());
    }

    public function testUnset(): void
    {
        $unset = ['field1', 'field2'];
        $this->mapping->setUnset($unset);
        $this->assertEquals($unset, $this->mapping->getUnset());
    }

    public function testCast(): void
    {
        $cast = ['field1' => 'string', 'field2' => 'integer'];
        $this->mapping->setCast($cast);
        $this->assertEquals($cast, $this->mapping->getCast());
    }

    public function testPassThrough(): void
    {
        $this->mapping->setPassThrough(true);
        $this->assertTrue($this->mapping->getPassThrough());
    }

    public function testDateCreated(): void
    {
        $dateCreated = new DateTime('2024-01-01 00:00:00');
        $this->mapping->setDateCreated($dateCreated);
        $this->assertEquals($dateCreated, $this->mapping->getDateCreated());
    }

    public function testDateModified(): void
    {
        $dateModified = new DateTime('2024-01-02 00:00:00');
        $this->mapping->setDateModified($dateModified);
        $this->assertEquals($dateModified, $this->mapping->getDateModified());
    }


    public function testSlug(): void
    {
        $slug = 'test-mapping-slug';
        $this->mapping->setSlug($slug);
        $this->assertEquals($slug, $this->mapping->getSlug());
    }

    public function testJsonSerialize(): void
    {
        $this->mapping->setUuid('test-uuid');
        $this->mapping->setName('Test Mapping');
        $this->mapping->setDescription('Test Description');
        $this->mapping->setMapping(['field1' => 'target1']);
        
        $json = $this->mapping->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test Mapping', $json['name']);
        $this->assertEquals('Test Description', $json['description']);
        $this->assertEquals(['field1' => 'target1'], $json['mapping']);
    }

    public function testGetMappingWithNull(): void
    {
        $this->mapping->setMapping(null);
        $this->assertIsArray($this->mapping->getMapping());
        $this->assertEmpty($this->mapping->getMapping());
    }

    public function testGetUnsetWithNull(): void
    {
        $this->mapping->setUnset(null);
        $this->assertIsArray($this->mapping->getUnset());
        $this->assertEmpty($this->mapping->getUnset());
    }

    public function testGetCastWithNull(): void
    {
        $this->mapping->setCast(null);
        $this->assertIsArray($this->mapping->getCast());
        $this->assertEmpty($this->mapping->getCast());
    }

}
