<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Rule;
use DateTime;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    private Rule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Rule();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Rule::class, $this->rule);
        $this->assertNull($this->rule->getUuid());
        $this->assertNull($this->rule->getName());
        $this->assertNull($this->rule->getDescription());
        $this->assertNull($this->rule->getReference());
        $this->assertEquals('0.0.0', $this->rule->getVersion());
        $this->assertNull($this->rule->getAction());
        $this->assertEquals('before', $this->rule->getTiming());
        $this->assertIsArray($this->rule->getConditions());
        $this->assertNull($this->rule->getType());
        $this->assertIsArray($this->rule->getConfiguration());
        $this->assertEquals(0, $this->rule->getOrder());
        $this->assertNull($this->rule->getCreated());
        $this->assertNull($this->rule->getUpdated());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->rule->setUuid($uuid);
        $this->assertEquals($uuid, $this->rule->getUuid());
    }

    public function testName(): void
    {
        $name = 'Test Rule';
        $this->rule->setName($name);
        $this->assertEquals($name, $this->rule->getName());
    }

    public function testDescription(): void
    {
        $description = 'Test Description';
        $this->rule->setDescription($description);
        $this->assertEquals($description, $this->rule->getDescription());
    }

    public function testReference(): void
    {
        $reference = 'test-reference';
        $this->rule->setReference($reference);
        $this->assertEquals($reference, $this->rule->getReference());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->rule->setVersion($version);
        $this->assertEquals($version, $this->rule->getVersion());
    }

    public function testAction(): void
    {
        $action = 'create';
        $this->rule->setAction($action);
        $this->assertEquals($action, $this->rule->getAction());
    }

    public function testTiming(): void
    {
        $timing = 'after';
        $this->rule->setTiming($timing);
        $this->assertEquals($timing, $this->rule->getTiming());
    }

    public function testConditions(): void
    {
        $conditions = ['and' => [['var' => 'field1'], ['==', ['var' => 'field2'], 'value']]];
        $this->rule->setConditions($conditions);
        $this->assertEquals($conditions, $this->rule->getConditions());
    }

    public function testType(): void
    {
        $type = 'mapping';
        $this->rule->setType($type);
        $this->assertEquals($type, $this->rule->getType());
    }

    public function testConfiguration(): void
    {
        $configuration = ['mappingId' => 123, 'enabled' => true];
        $this->rule->setConfiguration($configuration);
        $this->assertEquals($configuration, $this->rule->getConfiguration());
    }

    public function testOrder(): void
    {
        $order = 5;
        $this->rule->setOrder($order);
        $this->assertEquals($order, $this->rule->getOrder());
    }


    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->rule->setCreated($created);
        $this->assertEquals($created, $this->rule->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->rule->setUpdated($updated);
        $this->assertEquals($updated, $this->rule->getUpdated());
    }

    public function testSlug(): void
    {
        $slug = 'test-rule-slug';
        $this->rule->setSlug($slug);
        $this->assertEquals($slug, $this->rule->getSlug());
    }

    public function testJsonSerialize(): void
    {
        $this->rule->setUuid('test-uuid');
        $this->rule->setName('Test Rule');
        $this->rule->setDescription('Test Description');
        $this->rule->setAction('create');
        $this->rule->setType('mapping');
        $this->rule->setConditions(['var' => 'field1']);
        
        $json = $this->rule->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test Rule', $json['name']);
        $this->assertEquals('Test Description', $json['description']);
        $this->assertEquals('create', $json['action']);
        $this->assertEquals('mapping', $json['type']);
        $this->assertEquals(['var' => 'field1'], $json['conditions']);
    }

    public function testGetConditionsWithNull(): void
    {
        $this->rule->setConditions(null);
        $this->assertIsArray($this->rule->getConditions());
        $this->assertEmpty($this->rule->getConditions());
    }

    public function testGetConfigurationWithNull(): void
    {
        $this->rule->setConfiguration(null);
        $this->assertIsArray($this->rule->getConfiguration());
        $this->assertEmpty($this->rule->getConfiguration());
    }

}
