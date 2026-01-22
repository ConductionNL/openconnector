<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Endpoint;
use DateTime;
use PHPUnit\Framework\TestCase;

class EndpointTest extends TestCase
{
    private Endpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->endpoint = new Endpoint();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Endpoint::class, $this->endpoint);
        $this->assertNull($this->endpoint->getUuid());
        $this->assertNull($this->endpoint->getName());
        $this->assertNull($this->endpoint->getDescription());
        $this->assertNull($this->endpoint->getReference());
        $this->assertEquals('0.0.0', $this->endpoint->getVersion());
        $this->assertNull($this->endpoint->getEndpoint());
        $this->assertIsArray($this->endpoint->getEndpointArray());
        $this->assertNull($this->endpoint->getEndpointRegex());
        $this->assertNull($this->endpoint->getMethod());
        $this->assertNull($this->endpoint->getTargetType());
        $this->assertNull($this->endpoint->getTargetId());
        $this->assertIsArray($this->endpoint->getConditions());
        $this->assertNull($this->endpoint->getCreated());
        $this->assertNull($this->endpoint->getUpdated());
        $this->assertNull($this->endpoint->getInputMapping());
        $this->assertNull($this->endpoint->getOutputMapping());
        $this->assertIsArray($this->endpoint->getRules());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->endpoint->setUuid($uuid);
        $this->assertEquals($uuid, $this->endpoint->getUuid());
    }

    public function testName(): void
    {
        $name = 'Test Endpoint';
        $this->endpoint->setName($name);
        $this->assertEquals($name, $this->endpoint->getName());
    }

    public function testDescription(): void
    {
        $description = 'Test Description';
        $this->endpoint->setDescription($description);
        $this->assertEquals($description, $this->endpoint->getDescription());
    }

    public function testReference(): void
    {
        $reference = 'test-reference';
        $this->endpoint->setReference($reference);
        $this->assertEquals($reference, $this->endpoint->getReference());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->endpoint->setVersion($version);
        $this->assertEquals($version, $this->endpoint->getVersion());
    }

    public function testEndpoint(): void
    {
        $endpoint = '/api/buildings/{{id}}';
        $this->endpoint->setEndpoint($endpoint);
        $this->assertEquals($endpoint, $this->endpoint->getEndpoint());
    }

    public function testEndpointArray(): void
    {
        $endpointArray = ['/api/buildings/', '{{id}}'];
        $this->endpoint->setEndpointArray($endpointArray);
        $this->assertEquals($endpointArray, $this->endpoint->getEndpointArray());
    }

    public function testEndpointRegex(): void
    {
        $regex = '/api/buildings/\d+';
        $this->endpoint->setEndpointRegex($regex);
        $this->assertEquals($regex, $this->endpoint->getEndpointRegex());
    }

    public function testMethod(): void
    {
        $method = 'GET';
        $this->endpoint->setMethod($method);
        $this->assertEquals($method, $this->endpoint->getMethod());
    }

    public function testTargetType(): void
    {
        $targetType = 'source';
        $this->endpoint->setTargetType($targetType);
        $this->assertEquals($targetType, $this->endpoint->getTargetType());
    }

    public function testTargetId(): void
    {
        $targetId = 'target-123';
        $this->endpoint->setTargetId($targetId);
        $this->assertEquals($targetId, $this->endpoint->getTargetId());
    }

    public function testConditions(): void
    {
        $conditions = ['param1' => 'value1', 'param2' => 'value2'];
        $this->endpoint->setConditions($conditions);
        $this->assertEquals($conditions, $this->endpoint->getConditions());
    }

    public function testCreated(): void
    {
        $created = new DateTime('2024-01-01 00:00:00');
        $this->endpoint->setCreated($created);
        $this->assertEquals($created, $this->endpoint->getCreated());
    }

    public function testUpdated(): void
    {
        $updated = new DateTime('2024-01-02 00:00:00');
        $this->endpoint->setUpdated($updated);
        $this->assertEquals($updated, $this->endpoint->getUpdated());
    }

    public function testInputMapping(): void
    {
        $inputMapping = '{"id": "{{id}}"}';
        $this->endpoint->setInputMapping($inputMapping);
        $this->assertEquals($inputMapping, $this->endpoint->getInputMapping());
    }

    public function testOutputMapping(): void
    {
        $outputMapping = '{"result": "{{data}}"}';
        $this->endpoint->setOutputMapping($outputMapping);
        $this->assertEquals($outputMapping, $this->endpoint->getOutputMapping());
    }

    public function testRules(): void
    {
        $rules = ['rule1', 'rule2'];
        $this->endpoint->setRules($rules);
        $this->assertEquals($rules, $this->endpoint->getRules());
    }


    public function testSlug(): void
    {
        $slug = 'test-endpoint-slug';
        $this->endpoint->setSlug($slug);
        $this->assertEquals($slug, $this->endpoint->getSlug());
    }

    public function testJsonSerialize(): void
    {
        $this->endpoint->setUuid('test-uuid');
        $this->endpoint->setName('Test Endpoint');
        $this->endpoint->setDescription('Test Description');
        $this->endpoint->setEndpoint('/api/test');
        $this->endpoint->setMethod('GET');
        
        $json = $this->endpoint->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test Endpoint', $json['name']);
        $this->assertEquals('Test Description', $json['description']);
        $this->assertEquals('/api/test', $json['endpoint']);
        $this->assertEquals('GET', $json['method']);
    }

    public function testGetEndpointArrayWithNull(): void
    {
        $this->endpoint->setEndpointArray(null);
        $this->assertIsArray($this->endpoint->getEndpointArray());
        $this->assertEmpty($this->endpoint->getEndpointArray());
    }

    public function testGetConditionsWithNull(): void
    {
        $this->endpoint->setConditions(null);
        $this->assertIsArray($this->endpoint->getConditions());
        $this->assertEmpty($this->endpoint->getConditions());
    }

    public function testGetRulesWithNull(): void
    {
        $this->endpoint->setRules(null);
        $this->assertIsArray($this->endpoint->getRules());
        $this->assertEmpty($this->endpoint->getRules());
    }

}
