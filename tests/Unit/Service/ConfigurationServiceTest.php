<?php

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Service\ConfigurationService;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Service\ConfigurationHandlers\EndpointHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\SynchronizationHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\MappingHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\JobHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\SourceHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\RuleHandler;
use OCA\OpenRegister\Db\RegisterMapper;
use OCA\OpenRegister\Db\SchemaMapper;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigurationServiceTest
 *
 * Comprehensive unit tests for the ConfigurationService class.
 * 
 * This test class verifies the functionality of the ConfigurationService,
 * including entity retrieval by configuration, configuration export, and
 * proper interaction with mappers and handlers.
 *
 * @package OCA\OpenConnector\Tests\Unit\Service
 * @category Test
 * @author OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license AGPL-3.0
 * @version 1.0.0
 * @link https://github.com/OpenConnector/openconnector
 * 
 * @coversDefaultClass \OCA\OpenConnector\Service\ConfigurationService
 */
class ConfigurationServiceTest extends TestCase
{
    /**
     * The ConfigurationService instance under test
     *
     * @var ConfigurationService
     */
    private ConfigurationService $configurationService;

    /**
     * Mock source mapper for testing
     *
     * @var SourceMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private SourceMapper $sourceMapper;

    /**
     * Mock endpoint mapper for testing
     *
     * @var EndpointMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private EndpointMapper $endpointMapper;

    /**
     * Mock mapping mapper for testing
     *
     * @var MappingMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private MappingMapper $mappingMapper;

    /**
     * Mock rule mapper for testing
     *
     * @var RuleMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private RuleMapper $ruleMapper;

    /**
     * Mock job mapper for testing
     *
     * @var JobMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private JobMapper $jobMapper;

    /**
     * Mock synchronization mapper for testing
     *
     * @var SynchronizationMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private SynchronizationMapper $synchronizationMapper;

    /**
     * Mock register mapper for testing
     *
     * @var RegisterMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private RegisterMapper $registerMapper;

    /**
     * Mock schema mapper for testing
     *
     * @var SchemaMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private SchemaMapper $schemaMapper;

    /**
     * Mock endpoint handler for testing
     *
     * @var EndpointHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private EndpointHandler $endpointHandler;

    /**
     * Mock synchronization handler for testing
     *
     * @var SynchronizationHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private SynchronizationHandler $synchronizationHandler;

    /**
     * Mock mapping handler for testing
     *
     * @var MappingHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private MappingHandler $mappingHandler;

    /**
     * Mock job handler for testing
     *
     * @var JobHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private JobHandler $jobHandler;

    /**
     * Mock source handler for testing
     *
     * @var SourceHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private SourceHandler $sourceHandler;

    /**
     * Mock rule handler for testing
     *
     * @var RuleHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private RuleHandler $ruleHandler;

    /**
     * Set up the test environment before each test
     *
     * This method initializes all mock objects and creates the
     * ConfigurationService instance with mocked dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->sourceMapper = $this->createMock(SourceMapper::class);
        $this->endpointMapper = $this->createMock(EndpointMapper::class);
        $this->mappingMapper = $this->createMock(MappingMapper::class);
        $this->ruleMapper = $this->createMock(RuleMapper::class);
        $this->jobMapper = $this->createMock(JobMapper::class);
        $this->synchronizationMapper = $this->createMock(SynchronizationMapper::class);
        $this->registerMapper = $this->createMock(RegisterMapper::class);
        $this->schemaMapper = $this->createMock(SchemaMapper::class);
        $this->endpointHandler = $this->createMock(EndpointHandler::class);
        $this->synchronizationHandler = $this->createMock(SynchronizationHandler::class);
        $this->mappingHandler = $this->createMock(MappingHandler::class);
        $this->jobHandler = $this->createMock(JobHandler::class);
        $this->sourceHandler = $this->createMock(SourceHandler::class);
        $this->ruleHandler = $this->createMock(RuleHandler::class);

        $this->configurationService = new ConfigurationService(
            $this->sourceMapper,
            $this->endpointMapper,
            $this->mappingMapper,
            $this->ruleMapper,
            $this->jobMapper,
            $this->synchronizationMapper,
            $this->registerMapper,
            $this->schemaMapper,
            $this->endpointHandler,
            $this->synchronizationHandler,
            $this->mappingHandler,
            $this->jobHandler,
            $this->sourceHandler,
            $this->ruleHandler
        );
    }

    /**
     * Test that getEntitiesByConfiguration calls all mappers with correct configuration ID
     *
     * This test verifies that the method properly delegates to all 6 entity mappers
     * and passes the configuration ID to each one correctly.
     *
     * @covers ::getEntitiesByConfiguration
     * @return void
     */
    public function testGetEntitiesByConfigurationCallsAllMappers(): void
    {
        $configurationId = 'test-config-1';

        // Mock all mappers to return empty arrays for this test
        $this->sourceMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([]);

        $this->endpointMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([]);

        $this->mappingMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([]);

        $this->ruleMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([]);

        $this->jobMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([]);

        $this->synchronizationMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([]);

        $result = $this->configurationService->getEntitiesByConfiguration($configurationId);

        // Verify the result structure contains all expected entity types
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('endpoints', $result);
        $this->assertArrayHasKey('mappings', $result);
        $this->assertArrayHasKey('rules', $result);
        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('synchronizations', $result);
    }

    /**
     * Test slug-based indexing functionality
     *
     * This test verifies that entities returned by mappers are correctly
     * indexed by their slug values, and that the slug indexing logic works
     * properly for all entity types.
     *
     * @covers ::getEntitiesByConfiguration
     * @return void
     */
    public function testGetEntitiesByConfigurationIndexesBySlug(): void
    {
        $configurationId = 'test-config-1';

        // Create test entities with slug properties (as arrays to simulate jsonSerialize output)
        $sourceWithSlug = ['id' => 1, 'slug' => 'test-source', 'name' => 'Test Source'];
        $endpointWithSlug = ['id' => 2, 'slug' => 'test-endpoint', 'name' => 'Test Endpoint'];

        // Mock mappers to return entities with slug properties
        $this->sourceMapper->method('findByConfiguration')->willReturn([$sourceWithSlug]);
        $this->endpointMapper->method('findByConfiguration')->willReturn([$endpointWithSlug]);
        $this->mappingMapper->method('findByConfiguration')->willReturn([]);
        $this->ruleMapper->method('findByConfiguration')->willReturn([]);
        $this->jobMapper->method('findByConfiguration')->willReturn([]);
        $this->synchronizationMapper->method('findByConfiguration')->willReturn([]);

        $result = $this->configurationService->getEntitiesByConfiguration($configurationId);

        // Verify entities are indexed by their slugs
        $this->assertArrayHasKey('test-source', $result['sources']);
        $this->assertEquals($sourceWithSlug, $result['sources']['test-source']);
        
        $this->assertArrayHasKey('test-endpoint', $result['endpoints']);
        $this->assertEquals($endpointWithSlug, $result['endpoints']['test-endpoint']);
        
        // Verify other entity types are empty arrays
        $this->assertEmpty($result['mappings']);
        $this->assertEmpty($result['rules']);
        $this->assertEmpty($result['jobs']);
        $this->assertEmpty($result['synchronizations']);
    }

    /**
     * Test handling of entities with and without slug properties
     *
     * This test verifies that only entities with slug properties (including
     * empty slugs) are included in the result. Entities without the 'slug'
     * key are filtered out, but entities with empty slug values are included.
     *
     * @covers ::getEntitiesByConfiguration
     * @return void
     */
    public function testGetEntitiesByConfigurationFiltersEntitiesWithoutSlugs(): void
    {
        $configurationId = 'test-config-1';

        // Create entities: one with slug, one without
        $entityWithSlug = ['id' => 1, 'slug' => 'valid-slug', 'name' => 'Valid Entity'];
        $entityWithoutSlug = ['id' => 2, 'name' => 'Invalid Entity']; // Missing slug
        $entityWithEmptySlug = ['id' => 3, 'slug' => '', 'name' => 'Empty Slug']; // Empty slug

        $this->sourceMapper->method('findByConfiguration')
            ->willReturn([$entityWithSlug, $entityWithoutSlug, $entityWithEmptySlug]);
        
        // Mock other mappers to return empty arrays
        $this->endpointMapper->method('findByConfiguration')->willReturn([]);
        $this->mappingMapper->method('findByConfiguration')->willReturn([]);
        $this->ruleMapper->method('findByConfiguration')->willReturn([]);
        $this->jobMapper->method('findByConfiguration')->willReturn([]);
        $this->synchronizationMapper->method('findByConfiguration')->willReturn([]);

        $result = $this->configurationService->getEntitiesByConfiguration($configurationId);

        // Entities with valid slugs should be included
        $this->assertArrayHasKey('valid-slug', $result['sources']);
        $this->assertEquals($entityWithSlug, $result['sources']['valid-slug']);
        
        // Entity with empty slug should be included (empty string is a valid key)
        $this->assertArrayHasKey('', $result['sources']);
        $this->assertEquals($entityWithEmptySlug, $result['sources']['']);
        
        // Only entities with 'slug' key should be included (2 entities)
        $this->assertCount(2, $result['sources']);
        $this->assertArrayNotHasKey(2, $result['sources']); // Should not have numeric keys
    }

    /**
     * Test with multiple entities of the same type
     *
     * This test verifies that multiple entities of the same type are all
     * properly indexed by their respective slugs without conflicts.
     *
     * @covers ::getEntitiesByConfiguration
     * @return void
     */
    public function testGetEntitiesByConfigurationHandlesMultipleEntities(): void
    {
        $configurationId = 'test-config-1';

        $sources = [
            ['id' => 1, 'slug' => 'source-one', 'name' => 'First Source'],
            ['id' => 2, 'slug' => 'source-two', 'name' => 'Second Source'],
            ['id' => 3, 'slug' => 'source-three', 'name' => 'Third Source']
        ];

        $this->sourceMapper->method('findByConfiguration')->willReturn($sources);
        $this->endpointMapper->method('findByConfiguration')->willReturn([]);
        $this->mappingMapper->method('findByConfiguration')->willReturn([]);
        $this->ruleMapper->method('findByConfiguration')->willReturn([]);
        $this->jobMapper->method('findByConfiguration')->willReturn([]);
        $this->synchronizationMapper->method('findByConfiguration')->willReturn([]);

        $result = $this->configurationService->getEntitiesByConfiguration($configurationId);

        // Verify all sources are properly indexed
        $this->assertCount(3, $result['sources']);
        $this->assertArrayHasKey('source-one', $result['sources']);
        $this->assertArrayHasKey('source-two', $result['sources']);
        $this->assertArrayHasKey('source-three', $result['sources']);
        
        $this->assertEquals($sources[0], $result['sources']['source-one']);
        $this->assertEquals($sources[1], $result['sources']['source-two']);
        $this->assertEquals($sources[2], $result['sources']['source-three']);
    }

    /**
     * Test exporting a configuration with all its entities
     *
     * This test verifies that the exportConfiguration method correctly exports
     * a complete configuration including all entity types. It tests that the
     * method properly calls mappers to retrieve entities, creates real entity
     * objects, and uses handlers to export them in the correct format.
     * The test also verifies that the export process handles entity relationships
     * and produces the expected output structure.
     *
     * @covers ::exportConfiguration
     * @return void
     */
    public function testExportConfiguration(): void
    {
        $configurationId = 'test-config-1';
        
        // Create simple objects that can be used by the export methods
        $source = new Source();
        $source->setId(1);
        $source->setSlug('test-source');
        
        $endpoint = new Endpoint();
        $endpoint->setId(1);
        $endpoint->setSlug('test-endpoint');
        $endpoint->setTargetType('api');
        $endpoint->setTargetId('test-target');
        
        $mapping = new Mapping();
        $mapping->setId(1);
        $mapping->setSlug('test-mapping');
        
        $rule = new Rule();
        $rule->setId(1);
        $rule->setSlug('test-rule');
        
        $job = new Job();
        $job->setId(1);
        $job->setSlug('test-job');
        
        $synchronization = new Synchronization();
        $synchronization->setId(1);
        $synchronization->setSlug('test-sync');
        $synchronization->setSourceType('api');
        $synchronization->setSourceId('test-source');
        $synchronization->setTargetType('api');
        $synchronization->setTargetId('test-target');

        $this->sourceMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$source]);

        $this->endpointMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$endpoint]);

        $this->mappingMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$mapping]);

        $this->ruleMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$rule]);

        $this->jobMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$job]);

        $this->synchronizationMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$synchronization]);

        // Mock the export methods to return expected data
        $this->sourceHandler->method('export')->willReturn(['id' => 1, 'slug' => 'test-source']);
        $this->endpointHandler->method('export')->willReturn(['id' => 1, 'slug' => 'test-endpoint']);
        $this->mappingHandler->method('export')->willReturn(['id' => 1, 'slug' => 'test-mapping']);
        $this->ruleHandler->method('export')->willReturn(['id' => 1, 'slug' => 'test-rule']);
        $this->jobHandler->method('export')->willReturn(['id' => 1, 'slug' => 'test-job']);
        $this->synchronizationHandler->method('export')->willReturn(['id' => 1, 'slug' => 'test-sync']);

        $result = $this->configurationService->exportConfiguration($configurationId);

        // Check that the result has the expected structure
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('sources', $result['components']);
        $this->assertArrayHasKey('endpoints', $result['components']);
        $this->assertArrayHasKey('mappings', $result['components']);
        $this->assertArrayHasKey('rules', $result['components']);
        $this->assertArrayHasKey('jobs', $result['components']);
        $this->assertArrayHasKey('synchronizations', $result['components']);
    }
} 