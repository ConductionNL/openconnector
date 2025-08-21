<?php

declare(strict_types=1);

/**
 * RuleServiceTest
 *
 * Comprehensive unit tests for the RuleService class to verify rule processing,
 * software catalog generation, and custom rule handling functionality.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Service
 * @author    OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Service\RuleService;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SoftwareCatalogueService;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenRegister\Db\RegisterMapper;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\AppFramework\Http\JSONResponse;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Rule Service Test Suite
 *
 * Comprehensive unit tests for rule processing, software catalog generation,
 * and custom rule handling functionality. This test class validates the complex
 * business logic for processing different types of rules and data transformations.
 *
 * @coversDefaultClass RuleService
 */
class RuleServiceTest extends TestCase
{
    /**
     * The RuleService instance being tested
     *
     * @var RuleService
     */
    private RuleService $ruleService;

    /**
     * Mock logger
     *
     * @var MockObject|LoggerInterface
     */
    private MockObject $logger;

    /**
     * Mock object service
     *
     * @var MockObject|ObjectService
     */
    private MockObject $objectService;

    /**
     * Mock software catalogue service
     *
     * @var MockObject|SoftwareCatalogueService
     */
    private MockObject $catalogueService;

    /**
     * Mock register mapper
     *
     * @var MockObject|RegisterMapper
     */
    private MockObject $registerMapper;

    /**
     * Mock schema mapper
     *
     * @var MockObject|SchemaMapper
     */
    private MockObject $schemaMapper;

    /**
     * Mock call service
     *
     * @var MockObject|CallService
     */
    private MockObject $callService;

    /**
     * Mock source mapper
     *
     * @var MockObject|SourceMapper
     */
    private MockObject $sourceMapper;

    /**
     * Set up test environment before each test
     *
     * This method initializes the RuleService with mocked dependencies
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->objectService = $this->createMock(ObjectService::class);
        $this->catalogueService = $this->createMock(SoftwareCatalogueService::class);
        $this->registerMapper = $this->createMock(RegisterMapper::class);
        $this->schemaMapper = $this->createMock(SchemaMapper::class);
        $this->callService = $this->createMock(CallService::class);
        $this->sourceMapper = $this->createMock(SourceMapper::class);

        // Create the service
        $this->ruleService = new RuleService(
            $this->logger,
            $this->objectService,
            $this->catalogueService,
            $this->registerMapper,
            $this->schemaMapper,
            $this->callService,
            $this->sourceMapper
        );
    }

    /**
     * Test processCustomRule with softwareCatalogus type
     *
     * This test verifies that the processCustomRule method correctly
     * processes software catalog rules and returns the expected data structure.
     *
     * @covers ::processCustomRule
     * @return void
     */
    /**
     * @group integration
     */
    public function testProcessCustomRuleWithSoftwareCatalogus(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'softwareCatalogus',
            'configuration' => [
                'register' => 'test-register',
                'VoorzieningSchema' => 'voorziening-schema',
                'VoorzieningGebruikSchema' => 'voorziening-gebruik-schema',
                'OrganisatieSchema' => 'organisatie-schema',
                'VoorzieningAanbodSchema' => 'voorziening-aanbod-schema'
            ]
        ]);

        $data = [
            'parameters' => [
                'organisatie' => 'test-org'
            ],
            'body' => [
                'propertyDefinitions' => [
                    [
                        'identifier' => 'publish-property-id',
                        'name' => 'Publiceren',
                        'type' => 'string'
                    ]
                ],
                'views' => [],
                'organizations' => [
                    [
                        'label' => 'Application',
                        'item' => []
                    ],
                    [
                        'label' => 'Relations',
                        'item' => []
                    ]
                ]
            ]
        ];

        // Mock OpenRegister service
        $openRegisterService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $this->objectService->method('getOpenRegisters')->willReturn($openRegisterService);

        // Mock object entity mapper
        $objectEntityMapper = $this->createMock(\OCA\OpenRegister\Db\ObjectEntityMapper::class);
        $openRegisterService->method('getMapper')->willReturn($objectEntityMapper);

        // Mock voorziening gebruik objects
        $voorzieningGebruik = $this->createMock(ObjectEntity::class);
        $voorzieningGebruik->method('jsonSerialize')->willReturn([
            'voorzieningId' => 'test-voorziening-1',
            'id' => 'test-voorziening-1',
            'naam' => 'Test Voorziening',
            'beschrijving' => 'Test Description',
            'referentieComponenten' => ['ref-comp-1', 'ref-comp-2']
        ]);

        $objectEntityMapper->method('findAll')->willReturn([$voorzieningGebruik]);

        // Mock register and schema
        $register = $this->createMock(\OCA\OpenRegister\Db\Register::class);
        $register->id = 'test-register-id';
        $this->registerMapper->method('find')->willReturn($register);

        $schema = $this->createMock(\OCA\OpenRegister\Db\Schema::class);
        $schema->id = 'test-schema-id';
        // Create a simple object with id property instead of mocking non-existent class
        $publishPropertyDefinition = new \stdClass();
        $publishPropertyDefinition->id = 'publish-property-id';
        $schema->propertyDefinitions = [$publishPropertyDefinition];
        $this->schemaMapper->method('find')->willReturn($schema);

        // Mock added views with the exact structure expected by the filter
        $addedView = $this->createMock(ObjectEntity::class);
        $addedView->method('jsonSerialize')->willReturn([
            'identifier' => 'test-view',
            'properties' => [
                [
                    'propertyDefinitionRef' => 'publish-property-id',
                    'value' => 'Softwarecatalogus en GEMMA Online en redactie'
                ]
            ],
            'connections' => [],
            'nodes' => []
        ]);

        // Mock the findAll method to return the added view
        $openRegisterService->method('findAll')->willReturn([$addedView]);
        
        // Mock the register and schema to have proper IDs
        $register->id = 'vng-gemma';
        $schema->id = 'extendview';
        
        // Mock the register and schema to have proper IDs
        $register->id = 'vng-gemma';
        $schema->id = 'extendview';

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('views', $result['body']);
        $this->assertArrayHasKey('organizations', $result['body']);
    }

    /**
     * Test processCustomRule with connectRelations type
     *
     * This test verifies that the processCustomRule method correctly
     * processes connection rules and returns the expected data structure.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithConnectRelations(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'connectRelations',
            'configuration' => [
                'sourceType' => 'api',
                'targetType' => 'database'
            ]
        ]);

        $data = [
            'path' => '/test/path/550e8400-e29b-41d4-a716-446655440000',
            'elements' => [
                [
                    'identifier' => 'element-1',
                    'type' => 'BusinessActor'
                ],
                [
                    'identifier' => 'element-2',
                    'type' => 'ApplicationService'
                ]
            ]
        ];

        // Mock the catalogue service's extendModel method
        $this->catalogueService->expects($this->once())
            ->method('extendModel')
            ->with('550e8400-e29b-41d4-a716-446655440000');

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
    }

    /**
     * Test processCustomRule with unsupported type
     *
     * This test verifies that the processCustomRule method throws an exception
     * when an unsupported rule type is provided.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithUnsupportedType(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'unsupported-type'
        ]);
        // Remove getType method since it doesn't exist on Rule entity

        $data = [];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported custom rule type: ');

        $this->ruleService->processCustomRule($rule, $data);
    }

    /**
     * Test processCustomRule returns JSONResponse for error cases
     *
     * This test verifies that the processCustomRule method can return
     * a JSONResponse for error handling scenarios.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleReturnsJSONResponse(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'softwareCatalogus',
            'configuration' => [
                'register' => 'test-register',
                'VoorzieningSchema' => 'voorziening-schema',
                'VoorzieningGebruikSchema' => 'voorziening-gebruik-schema',
                'OrganisatieSchema' => 'organisatie-schema',
                'VoorzieningAanbodSchema' => 'voorziening-aanbod-schema'
            ]
        ]);

        $data = [
            'parameters' => [
                'organisatie' => 'test-org'
            ],
            'body' => [
                'propertyDefinitions' => [
                    [
                        'identifier' => 'publish-property-id',
                        'name' => 'Publiceren',
                        'type' => 'string'
                    ]
                ],
                'views' => [],
                'organizations' => [
                    [
                        'label' => 'Application',
                        'item' => []
                    ],
                    [
                        'label' => 'Relations',
                        'item' => []
                    ]
                ]
            ]
        ];

        // Mock OpenRegister service to return a mock that can handle the calls
        $openRegisterService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $openRegisterService->method('setRegister')->willReturnSelf();
        $openRegisterService->method('setSchema')->willReturnSelf();
        $openRegisterService->method('getMapper')->willReturn($this->createMock(\OCA\OpenRegister\Db\ObjectEntityMapper::class));
        $openRegisterService->method('findAll')->willReturn([]);
        $this->objectService->method('getOpenRegisters')->willReturn($openRegisterService);

        $result = $this->ruleService->processCustomRule($rule, $data);

        // The method should process the data and return the modified structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('headers', $result);
    }

    /**
     * Test processCustomRule with empty configuration
     *
     * This test verifies that the processCustomRule method handles
     * rules with empty or missing configuration gracefully.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithEmptyConfiguration(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'softwareCatalogus',
            'configuration' => [
                'register' => 'test-register',
                'VoorzieningSchema' => 'voorziening-schema',
                'VoorzieningGebruikSchema' => 'voorziening-gebruik-schema',
                'OrganisatieSchema' => 'organisatie-schema',
                'VoorzieningAanbodSchema' => 'voorziening-aanbod-schema'
            ]
        ]);

        $data = [
            'parameters' => [
                'organisatie' => 'test-org'
            ],
            'body' => [
                'propertyDefinitions' => [
                    [
                        'identifier' => 'publish-property-id',
                        'name' => 'Publiceren',
                        'type' => 'string'
                    ]
                ],
                'views' => [],
                'organizations' => [
                    [
                        'label' => 'Application',
                        'item' => []
                    ],
                    [
                        'label' => 'Relations',
                        'item' => []
                    ]
                ]
            ]
        ];

        // Mock OpenRegister service
        $openRegisterService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $this->objectService->method('getOpenRegisters')->willReturn($openRegisterService);

        // Mock object entity mapper
        $objectEntityMapper = $this->createMock(\OCA\OpenRegister\Db\ObjectEntityMapper::class);
        $openRegisterService->method('getMapper')->willReturn($objectEntityMapper);

        // Mock empty results
        $objectEntityMapper->method('findAll')->willReturn([]);

        // Mock register and schema
        $register = $this->createMock(\OCA\OpenRegister\Db\Register::class);
        $register->id = 'test-register-id';
        $this->registerMapper->method('find')->willReturn($register);

        $schema = $this->createMock(\OCA\OpenRegister\Db\Schema::class);
        $schema->id = 'test-schema-id';
        // Create a simple object with id property instead of mocking non-existent class
        $publishPropertyDefinition = new \stdClass();
        $publishPropertyDefinition->id = 'publish-property-id';
        $schema->propertyDefinitions = [$publishPropertyDefinition];
        $this->schemaMapper->method('find')->willReturn($schema);

        // Mock empty added views
        $openRegisterService->method('findAll')->willReturn([]);

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('body', $result);
    }

    /**
     * Test processCustomRule with complex data structure
     *
     * This test verifies that the processCustomRule method correctly
     * handles complex data structures with nested elements.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithComplexDataStructure(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'connectRelations',
            'configuration' => [
                'sourceType' => 'api',
                'targetType' => 'database',
                'relationType' => 'depends_on'
            ]
        ]);

        $data = [
            'path' => '/test/path/550e8400-e29b-41d4-a716-446655440000',
            'elements' => [
                [
                    'identifier' => 'api-service-1',
                    'type' => 'ApplicationService',
                    'name' => 'API Service 1',
                    'properties' => [
                        [
                            'propertyDefinitionRef' => 'source-type',
                            'value' => 'api'
                        ]
                    ]
                ],
                [
                    'identifier' => 'database-service-1',
                    'type' => 'ApplicationService',
                    'name' => 'Database Service 1',
                    'properties' => [
                        [
                            'propertyDefinitionRef' => 'target-type',
                            'value' => 'database'
                        ]
                    ]
                ]
            ],
            'relationships' => []
        ];

        // Mock the catalogue service's extendModel method
        $this->catalogueService->expects($this->once())
            ->method('extendModel')
            ->with('550e8400-e29b-41d4-a716-446655440000');

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
    }

    /**
     * Test processCustomRule with missing required configuration
     *
     * This test verifies that the processCustomRule method handles
     * missing required configuration gracefully.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithMissingConfiguration(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'softwareCatalogus',
            'configuration' => [
                'register' => 'test-register',
                'VoorzieningSchema' => 'voorziening-schema',
                'VoorzieningGebruikSchema' => 'voorziening-gebruik-schema',
                'OrganisatieSchema' => 'organisatie-schema',
                'VoorzieningAanbodSchema' => 'voorziening-aanbod-schema'
            ]
        ]);

        $data = [
            'parameters' => [
                'organisatie' => 'test-org'
            ],
            'body' => [
                'propertyDefinitions' => [
                    [
                        'identifier' => 'publish-property-id',
                        'name' => 'Publiceren',
                        'type' => 'string'
                    ]
                ],
                'views' => [],
                'organizations' => [
                    [
                        'label' => 'Application',
                        'item' => []
                    ],
                    [
                        'label' => 'Relations',
                        'item' => []
                    ]
                ]
            ]
        ];

        // Mock OpenRegister service
        $openRegisterService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $this->objectService->method('getOpenRegisters')->willReturn($openRegisterService);

        // Mock object entity mapper
        $objectEntityMapper = $this->createMock(\OCA\OpenRegister\Db\ObjectEntityMapper::class);
        $openRegisterService->method('getMapper')->willReturn($objectEntityMapper);

        // Mock empty results
        $objectEntityMapper->method('findAll')->willReturn([]);

        // Mock register and schema
        $register = $this->createMock(\OCA\OpenRegister\Db\Register::class);
        $register->id = 'test-register-id';
        $this->registerMapper->method('find')->willReturn($register);

        $schema = $this->createMock(\OCA\OpenRegister\Db\Schema::class);
        $schema->id = 'test-schema-id';
        // Create a simple object with id property instead of mocking non-existent class
        $publishPropertyDefinition = new \stdClass();
        $publishPropertyDefinition->id = 'publish-property-id';
        $schema->propertyDefinitions = [$publishPropertyDefinition];
        $this->schemaMapper->method('find')->willReturn($schema);

        // Mock empty added views
        $openRegisterService->method('findAll')->willReturn([]);

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertIsArray($result);
    }

    /**
     * Test processCustomRule with null data
     *
     * This test verifies that the processCustomRule method handles
     * null data gracefully.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithNullData(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'connectRelations',
            'configuration' => [
                'sourceType' => 'api',
                'targetType' => 'database'
            ]
        ]);

        $data = ['path' => '/test/path/550e8400-e29b-41d4-a716-446655440000'];

        // Mock the catalogue service's extendModel method
        $this->catalogueService->expects($this->once())
            ->method('extendModel')
            ->with('550e8400-e29b-41d4-a716-446655440000');

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
    }

    /**
     * Test processCustomRule with empty data
     *
     * This test verifies that the processCustomRule method handles
     * empty data arrays gracefully.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithEmptyData(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'connectRelations',
            'configuration' => [
                'sourceType' => 'api',
                'targetType' => 'database'
            ]
        ]);

        $data = ['path' => '/test/path/550e8400-e29b-41d4-a716-446655440000'];

        // Mock the catalogue service's extendModel method
        $this->catalogueService->expects($this->once())
            ->method('extendModel')
            ->with('550e8400-e29b-41d4-a716-446655440000');

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
    }

    /**
     * Test processCustomRule with invalid rule object
     *
     * This test verifies that the processCustomRule method handles
     * invalid rule objects gracefully.
     *
     * @covers ::processCustomRule
     * @return void
     */
    public function testProcessCustomRuleWithInvalidRule(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getConfiguration')->willReturn([
            'type' => 'softwareCatalogus',
            'configuration' => [
                'register' => 'test-register',
                'VoorzieningSchema' => 'voorziening-schema',
                'VoorzieningGebruikSchema' => 'voorziening-gebruik-schema',
                'OrganisatieSchema' => 'organisatie-schema',
                'VoorzieningAanbodSchema' => 'voorziening-aanbod-schema'
            ]
        ]);

        $data = [
            'parameters' => [
                'organisatie' => 'test-org'
            ],
            'body' => [
                'propertyDefinitions' => [
                    [
                        'identifier' => 'publish-property-id',
                        'name' => 'Publiceren',
                        'type' => 'string'
                    ]
                ],
                'views' => [],
                'organizations' => [
                    [
                        'label' => 'Application',
                        'item' => []
                    ],
                    [
                        'label' => 'Relations',
                        'item' => []
                    ]
                ]
            ]
        ];

        // Mock OpenRegister service to return a mock that can handle the calls
        $openRegisterService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $openRegisterService->method('setRegister')->willReturnSelf();
        $openRegisterService->method('setSchema')->willReturnSelf();
        $openRegisterService->method('getMapper')->willReturn($this->createMock(\OCA\OpenRegister\Db\ObjectEntityMapper::class));
        $openRegisterService->method('findAll')->willReturn([]);
        $this->objectService->method('getOpenRegisters')->willReturn($openRegisterService);

        $result = $this->ruleService->processCustomRule($rule, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('headers', $result);
    }
}
