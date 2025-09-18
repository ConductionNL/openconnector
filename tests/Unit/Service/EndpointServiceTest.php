<?php

namespace OCA\OpenConnector\Tests\Unit\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use JWadhams\JsonLogic;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Service\AuthorizationService;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\EndpointService;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\RuleService;
use OCA\OpenConnector\Service\StorageService;
use OCA\OpenConnector\Service\SynchronizationService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * EndpointServiceTest
 *
 * Unit tests for the EndpointService class.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Service
 * @author   Conduction <info@conduction.nl>
 * @copyright 2024 Conduction b.v.
 * @license  AGPL-3.0-or-later
 * @version  1.0.0
 * @link     https://github.com/ConductionNL/OpenConnector
 */
class EndpointServiceTest extends TestCase
{
    private EndpointService $endpointService;
    private ObjectService $objectService;
    private CallService $callService;
    private LoggerInterface $logger;
    private IURLGenerator $urlGenerator;
    private MappingService $mappingService;
    private EndpointMapper $endpointMapper;
    private RuleMapper $ruleMapper;
    private IConfig $config;
    private IAppConfig $appConfig;
    private StorageService $storageService;
    private AuthorizationService $authorizationService;
    private ContainerInterface $containerInterface;
    private SynchronizationService $synchronizationService;
    private RuleService $ruleService;

    protected function setUp(): void
    {
        $this->objectService = $this->createMock(ObjectService::class);
        $this->callService = $this->createMock(CallService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->mappingService = $this->createMock(MappingService::class);
        $this->endpointMapper = $this->createMock(EndpointMapper::class);
        $this->ruleMapper = $this->createMock(RuleMapper::class);
        $this->config = $this->createMock(IConfig::class);
        $this->appConfig = $this->createMock(IAppConfig::class);
        $this->storageService = $this->createMock(StorageService::class);
        $this->authorizationService = $this->createMock(AuthorizationService::class);
        $this->containerInterface = $this->createMock(ContainerInterface::class);
        $this->synchronizationService = $this->createMock(SynchronizationService::class);
        $this->ruleService = $this->createMock(RuleService::class);

        $this->endpointService = new EndpointService(
            $this->objectService,
            $this->callService,
            $this->logger,
            $this->urlGenerator,
            $this->mappingService,
            $this->endpointMapper,
            $this->ruleMapper,
            $this->config,
            $this->appConfig,
            $this->storageService,
            $this->authorizationService,
            $this->containerInterface,
            $this->synchronizationService,
            $this->ruleService
        );
    }

    /**
     * Test parseMessage method with validation errors
     *
     * This test verifies that the parseMessage method correctly
     * parses validation error messages.
     *
     * @covers ::parseMessage
     * @return void
     */
    public function testParseMessageWithValidationErrors(): void
    {
        $response = [];
        $responseData = [
            'message' => 'Validation failed',
            'errors' => [
                [
                    'message' => 'missing (field1, field2)'
                ]
            ]
        ];

        $reflection = new \ReflectionClass($this->endpointService);
        $method = $reflection->getMethod('parseMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->endpointService, $response, $responseData);

        $this->assertArrayHasKey('detail', $result);
        $this->assertArrayHasKey('invalidParams', $result);
        $this->assertEquals('missing (field1, field2)', $result['detail']);
        $this->assertCount(2, $result['invalidParams']);
    }

    /**
     * Test parseMessage method with type errors
     *
     * This test verifies that the parseMessage method correctly
     * parses type error messages.
     *
     * @covers ::parseMessage
     * @return void
     */
    public function testParseMessageWithTypeErrors(): void
    {
        $response = [];
        $responseData = [
            'message' => 'Validation failed',
            'errors' => [
                [
                    'message' => 'Type validation failed',
                    'errors' => [
                        'field1' => ['invalid value'],
                        'field2' => ['type error']
                    ]
                ]
            ]
        ];

        $reflection = new \ReflectionClass($this->endpointService);
        $method = $reflection->getMethod('parseMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->endpointService, $response, $responseData);

        $this->assertArrayHasKey('detail', $result);
        $this->assertArrayHasKey('invalidParams', $result);
        $this->assertEquals('Type validation failed', $result['detail']);
        $this->assertCount(2, $result['invalidParams']);
        $this->assertEquals('invalid value', $result['invalidParams'][0]['code']);
        $this->assertEquals('invalid type', $result['invalidParams'][1]['code']);
    }

    /**
     * Test parseMessage method with general errors
     *
     * This test verifies that the parseMessage method correctly
     * handles general error messages.
     *
     * @covers ::parseMessage
     * @return void
     */
    public function testParseMessageWithGeneralErrors(): void
    {
        $response = [];
        $responseData = [
            'errors' => [
                'error1' => 'General error message'
            ]
        ];

        $reflection = new \ReflectionClass($this->endpointService);
        $method = $reflection->getMethod('parseMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->endpointService, $response, $responseData);

        $this->assertArrayHasKey('invalidParams', $result);
        $this->assertEquals($responseData['errors'], $result['invalidParams']);
    }

    /**
     * Test checkConditions method with valid conditions
     *
     * This test verifies that the checkConditions method correctly
     * validates endpoint conditions.
     *
     * @covers ::checkConditions
     * @return void
     */
    public function testCheckConditionsWithValidConditions(): void
    {
        // Create a mock endpoint with JsonLogic conditions that will pass
        $endpoint = $this->createMock(Endpoint::class);
        $endpoint->method('getConditions')->willReturn([]); // Empty conditions should pass

        // Create a mock request with server variables and parameters
        $request = $this->createMock(IRequest::class);
        $request->server = [
            'HTTP_HOST' => 'example.com',
            'REQUEST_METHOD' => 'GET'
        ];
        $request->method('getParams')->willReturn(['id' => '123']);

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($this->endpointService);
        $method = $reflection->getMethod('checkConditions');
        $method->setAccessible(true);

        $result = $method->invoke($this->endpointService, $endpoint, $request);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
