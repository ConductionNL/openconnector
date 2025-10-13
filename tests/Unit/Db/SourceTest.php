<?php

namespace OCA\OpenConnector\Tests\Unit\Db;

use OCA\OpenConnector\Db\Source;
use DateTime;
use PHPUnit\Framework\TestCase;

class SourceTest extends TestCase
{
    private Source $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = new Source();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Source::class, $this->source);
        $this->assertNull($this->source->getUuid());
        $this->assertNull($this->source->getName());
        $this->assertNull($this->source->getDescription());
        $this->assertNull($this->source->getReference());
        $this->assertEquals('0.0.0', $this->source->getVersion());
        $this->assertNull($this->source->getLocation());
        $this->assertNull($this->source->getIsEnabled());
        $this->assertNull($this->source->getType());
        $this->assertNull($this->source->getAuthorizationHeader());
        $this->assertNull($this->source->getAuth());
        $this->assertIsArray($this->source->getAuthenticationConfig());
        $this->assertNull($this->source->getAuthorizationPassthroughMethod());
        $this->assertNull($this->source->getLocale());
        $this->assertNull($this->source->getAccept());
        $this->assertNull($this->source->getJwt());
        $this->assertNull($this->source->getJwtId());
        $this->assertNull($this->source->getSecret());
        $this->assertNull($this->source->getUsername());
        $this->assertNull($this->source->getPassword());
        $this->assertNull($this->source->getApikey());
        $this->assertNull($this->source->getDocumentation());
        $this->assertIsArray($this->source->getLoggingConfig());
        $this->assertNull($this->source->getOas());
        $this->assertIsArray($this->source->getPaths());
        $this->assertIsArray($this->source->getHeaders());
        $this->assertIsArray($this->source->getTranslationConfig());
        $this->assertIsArray($this->source->getConfiguration());
    }

    public function testUuid(): void
    {
        $uuid = 'test-uuid-123';
        $this->source->setUuid($uuid);
        $this->assertEquals($uuid, $this->source->getUuid());
    }

    public function testName(): void
    {
        $name = 'Test Source';
        $this->source->setName($name);
        $this->assertEquals($name, $this->source->getName());
    }

    public function testDescription(): void
    {
        $description = 'Test Description';
        $this->source->setDescription($description);
        $this->assertEquals($description, $this->source->getDescription());
    }

    public function testReference(): void
    {
        $reference = 'test-reference';
        $this->source->setReference($reference);
        $this->assertEquals($reference, $this->source->getReference());
    }

    public function testVersion(): void
    {
        $version = '1.0.0';
        $this->source->setVersion($version);
        $this->assertEquals($version, $this->source->getVersion());
    }

    public function testLocation(): void
    {
        $location = 'https://api.example.com';
        $this->source->setLocation($location);
        $this->assertEquals($location, $this->source->getLocation());
    }

    public function testIsEnabled(): void
    {
        $this->source->setIsEnabled(true);
        $this->assertTrue($this->source->getIsEnabled());
    }

    public function testType(): void
    {
        $type = 'REST';
        $this->source->setType($type);
        $this->assertEquals($type, $this->source->getType());
    }

    public function testAuthorizationHeader(): void
    {
        $header = 'Authorization: Bearer token';
        $this->source->setAuthorizationHeader($header);
        $this->assertEquals($header, $this->source->getAuthorizationHeader());
    }

    public function testAuth(): void
    {
        $auth = 'bearer';
        $this->source->setAuth($auth);
        $this->assertEquals($auth, $this->source->getAuth());
    }

    public function testAuthenticationConfig(): void
    {
        $config = ['type' => 'bearer', 'token' => 'test-token'];
        $this->source->setAuthenticationConfig($config);
        $this->assertEquals($config, $this->source->getAuthenticationConfig());
    }

    public function testAuthorizationPassthroughMethod(): void
    {
        $method = 'header';
        $this->source->setAuthorizationPassthroughMethod($method);
        $this->assertEquals($method, $this->source->getAuthorizationPassthroughMethod());
    }

    public function testLocale(): void
    {
        $locale = 'en_US';
        $this->source->setLocale($locale);
        $this->assertEquals($locale, $this->source->getLocale());
    }

    public function testAccept(): void
    {
        $accept = 'application/json';
        $this->source->setAccept($accept);
        $this->assertEquals($accept, $this->source->getAccept());
    }

    public function testJwt(): void
    {
        $jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';
        $this->source->setJwt($jwt);
        $this->assertEquals($jwt, $this->source->getJwt());
    }

    public function testJwtId(): void
    {
        $jwtId = 'jwt-id-123';
        $this->source->setJwtId($jwtId);
        $this->assertEquals($jwtId, $this->source->getJwtId());
    }

    public function testSecret(): void
    {
        $secret = 'secret-key';
        $this->source->setSecret($secret);
        $this->assertEquals($secret, $this->source->getSecret());
    }

    public function testUsername(): void
    {
        $username = 'testuser';
        $this->source->setUsername($username);
        $this->assertEquals($username, $this->source->getUsername());
    }

    public function testPassword(): void
    {
        $password = 'testpassword';
        $this->source->setPassword($password);
        $this->assertEquals($password, $this->source->getPassword());
    }

    public function testApikey(): void
    {
        $apikey = 'api-key-123';
        $this->source->setApikey($apikey);
        $this->assertEquals($apikey, $this->source->getApikey());
    }

    public function testDocumentation(): void
    {
        $documentation = 'https://docs.example.com';
        $this->source->setDocumentation($documentation);
        $this->assertEquals($documentation, $this->source->getDocumentation());
    }

    public function testLoggingConfig(): void
    {
        $config = ['level' => 'info', 'format' => 'json'];
        $this->source->setLoggingConfig($config);
        $this->assertEquals($config, $this->source->getLoggingConfig());
    }

    public function testOas(): void
    {
        $oas = 'https://api.example.com/openapi.json';
        $this->source->setOas($oas);
        $this->assertEquals($oas, $this->source->getOas());
    }

    public function testPaths(): void
    {
        $paths = ['/users', '/products'];
        $this->source->setPaths($paths);
        $this->assertEquals($paths, $this->source->getPaths());
    }

    public function testHeaders(): void
    {
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        $this->source->setHeaders($headers);
        $this->assertEquals($headers, $this->source->getHeaders());
    }

    public function testTranslationConfig(): void
    {
        $config = ['source' => 'en', 'target' => 'nl'];
        $this->source->setTranslationConfig($config);
        $this->assertEquals($config, $this->source->getTranslationConfig());
    }

    public function testConfiguration(): void
    {
        $config = ['timeout' => 30, 'retries' => 3];
        $this->source->setConfiguration($config);
        $this->assertEquals($config, $this->source->getConfiguration());
    }

    public function testJsonSerialize(): void
    {
        $this->source->setUuid('test-uuid');
        $this->source->setName('Test Source');
        $this->source->setDescription('Test Description');
        $this->source->setLocation('https://api.example.com');
        $this->source->setType('REST');
        
        $json = $this->source->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('test-uuid', $json['uuid']);
        $this->assertEquals('Test Source', $json['name']);
        $this->assertEquals('Test Description', $json['description']);
        $this->assertEquals('https://api.example.com', $json['location']);
        $this->assertEquals('REST', $json['type']);
    }

    public function testGetAuthenticationConfigWithNull(): void
    {
        $this->source->setAuthenticationConfig(null);
        $this->assertIsArray($this->source->getAuthenticationConfig());
        $this->assertEmpty($this->source->getAuthenticationConfig());
    }

    public function testGetLoggingConfigWithNull(): void
    {
        $this->source->setLoggingConfig(null);
        $this->assertIsArray($this->source->getLoggingConfig());
        $this->assertEmpty($this->source->getLoggingConfig());
    }

    public function testGetPathsWithNull(): void
    {
        $this->source->setPaths(null);
        $this->assertIsArray($this->source->getPaths());
        $this->assertEmpty($this->source->getPaths());
    }

    public function testGetHeadersWithNull(): void
    {
        $this->source->setHeaders(null);
        $this->assertIsArray($this->source->getHeaders());
        $this->assertEmpty($this->source->getHeaders());
    }

    public function testGetTranslationConfigWithNull(): void
    {
        $this->source->setTranslationConfig(null);
        $this->assertIsArray($this->source->getTranslationConfig());
        $this->assertEmpty($this->source->getTranslationConfig());
    }

    public function testGetConfigurationWithNull(): void
    {
        $this->source->setConfiguration(null);
        $this->assertIsArray($this->source->getConfiguration());
        $this->assertEmpty($this->source->getConfiguration());
    }
}
