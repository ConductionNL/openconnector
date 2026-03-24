<?php

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Service\ConfigurationService;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenRegister\Db\RegisterMapper;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenConnector\Service\ConfigurationHandlers\EndpointHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\SynchronizationHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\MappingHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\JobHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\SourceHandler;
use OCA\OpenConnector\Service\ConfigurationHandlers\RuleHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigurationServiceTest
 *
 * Unit tests for the ConfigurationService class.
 *
 * @package OCA\OpenConnector\Tests\Unit\Service
 * @category Test
 * @author OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license AGPL-3.0
 * @version 1.0.0
 * @link https://github.com/OpenConnector/openconnector
 */
class ConfigurationServiceTest extends TestCase
{
    private ConfigurationService $configurationService;
    private SourceMapper $sourceMapper;
    private EndpointMapper $endpointMapper;
    private MappingMapper $mappingMapper;
    private RuleMapper $ruleMapper;
    private JobMapper $jobMapper;
    private SynchronizationMapper $synchronizationMapper;
    private RegisterMapper $registerMapper;
    private SchemaMapper $schemaMapper;
    private EndpointHandler $endpointHandler;
    private SynchronizationHandler $synchronizationHandler;
    private MappingHandler $mappingHandler;
    private JobHandler $jobHandler;
    private SourceHandler $sourceHandler;
    private RuleHandler $ruleHandler;

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

    public function testGetEntitiesByConfigurationEmpty(): void
    {
        $configurationId = 'test-config-1';

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

        $this->assertEquals([], $result['sources']);
        $this->assertEquals([], $result['endpoints']);
        $this->assertEquals([], $result['mappings']);
        $this->assertEquals([], $result['rules']);
        $this->assertEquals([], $result['jobs']);
        $this->assertEquals([], $result['synchronizations']);
    }

    public function testGetEntitiesByConfigurationWithSluggedEntities(): void
    {
        $configurationId = 'test-config-1';

        // The indexBySlug helper in getEntitiesByConfiguration expects entities
        // with array access and a 'slug' key. Use arrays to simulate serialized entities.
        $sourceArray = ['slug' => 'source-one', 'name' => 'Source One'];
        $endpointArray = ['slug' => 'endpoint-one', 'name' => 'Endpoint One'];
        $mappingArray = ['slug' => 'mapping-one', 'name' => 'Mapping One'];
        $ruleArray = ['slug' => 'rule-one', 'name' => 'Rule One'];
        $jobArray = ['slug' => 'job-one', 'name' => 'Job One'];
        $syncArray = ['slug' => 'sync-one', 'name' => 'Sync One'];

        $this->sourceMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$sourceArray]);

        $this->endpointMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$endpointArray]);

        $this->mappingMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$mappingArray]);

        $this->ruleMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$ruleArray]);

        $this->jobMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$jobArray]);

        $this->synchronizationMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn([$syncArray]);

        $result = $this->configurationService->getEntitiesByConfiguration($configurationId);

        $this->assertEquals(['source-one' => $sourceArray], $result['sources']);
        $this->assertEquals(['endpoint-one' => $endpointArray], $result['endpoints']);
        $this->assertEquals(['mapping-one' => $mappingArray], $result['mappings']);
        $this->assertEquals(['rule-one' => $ruleArray], $result['rules']);
        $this->assertEquals(['job-one' => $jobArray], $result['jobs']);
        $this->assertEquals(['sync-one' => $syncArray], $result['synchronizations']);
    }

    public function testGetEntitiesByConfigurationReturnsCorrectKeys(): void
    {
        $configurationId = 'test-config-1';

        $this->sourceMapper->method('findByConfiguration')->willReturn([]);
        $this->endpointMapper->method('findByConfiguration')->willReturn([]);
        $this->mappingMapper->method('findByConfiguration')->willReturn([]);
        $this->ruleMapper->method('findByConfiguration')->willReturn([]);
        $this->jobMapper->method('findByConfiguration')->willReturn([]);
        $this->synchronizationMapper->method('findByConfiguration')->willReturn([]);

        $result = $this->configurationService->getEntitiesByConfiguration($configurationId);

        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('endpoints', $result);
        $this->assertArrayHasKey('mappings', $result);
        $this->assertArrayHasKey('rules', $result);
        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('synchronizations', $result);
    }

    public function testExportConfigurationEmpty(): void
    {
        $configurationId = 'test-config-1';

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

        $result = $this->configurationService->exportConfiguration($configurationId);

        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('sources', $result['components']);
        $this->assertArrayHasKey('endpoints', $result['components']);
        $this->assertArrayHasKey('mappings', $result['components']);
        $this->assertArrayHasKey('rules', $result['components']);
        $this->assertArrayHasKey('jobs', $result['components']);
        $this->assertArrayHasKey('synchronizations', $result['components']);
    }

    public function testExportConfigurationReturnsComponentsStructure(): void
    {
        $configurationId = 'test-config-1';

        $this->sourceMapper->method('findByConfiguration')->willReturn([]);
        $this->endpointMapper->method('findByConfiguration')->willReturn([]);
        $this->mappingMapper->method('findByConfiguration')->willReturn([]);
        $this->ruleMapper->method('findByConfiguration')->willReturn([]);
        $this->jobMapper->method('findByConfiguration')->willReturn([]);
        $this->synchronizationMapper->method('findByConfiguration')->willReturn([]);

        $result = $this->configurationService->exportConfiguration($configurationId);

        // exportConfiguration now returns a components-based structure
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('components', $result);
        $components = $result['components'];
        $this->assertCount(6, $components);
    }
}
