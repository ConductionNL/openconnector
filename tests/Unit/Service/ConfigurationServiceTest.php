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

    public function testGetEntitiesByConfiguration(): void
    {
        $configurationId = 'test-config-1';

        // The getEntitiesByConfiguration method uses $indexBySlug which
        // accesses entities as arrays (e.g. $entity['slug']). We mock
        // findByConfiguration to return empty arrays so the method
        // returns empty arrays for each type (matching real behavior
        // when no entities are found).
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

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('endpoints', $result);
        $this->assertArrayHasKey('mappings', $result);
        $this->assertArrayHasKey('rules', $result);
        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('synchronizations', $result);
        $this->assertEmpty($result['sources']);
        $this->assertEmpty($result['endpoints']);
        $this->assertEmpty($result['mappings']);
        $this->assertEmpty($result['rules']);
        $this->assertEmpty($result['jobs']);
        $this->assertEmpty($result['synchronizations']);
    }

    public function testExportConfiguration(): void
    {
        $configurationId = 'test-config-1';

        // exportConfiguration calls resetMappings() first, which calls
        // getIdToSlugMap/getSlugToIdMap on all mappers
        $this->endpointMapper->method('getIdToSlugMap')->willReturn([]);
        $this->endpointMapper->method('getSlugToIdMap')->willReturn([]);
        $this->jobMapper->method('getIdToSlugMap')->willReturn([]);
        $this->jobMapper->method('getSlugToIdMap')->willReturn([]);
        $this->synchronizationMapper->method('getIdToSlugMap')->willReturn([]);
        $this->synchronizationMapper->method('getSlugToIdMap')->willReturn([]);
        $this->mappingMapper->method('getIdToSlugMap')->willReturn([]);
        $this->mappingMapper->method('getSlugToIdMap')->willReturn([]);
        $this->ruleMapper->method('getIdToSlugMap')->willReturn([]);
        $this->ruleMapper->method('getSlugToIdMap')->willReturn([]);
        $this->sourceMapper->method('getIdToSlugMap')->willReturn([]);
        $this->sourceMapper->method('getSlugToIdMap')->willReturn([]);
        $this->registerMapper->method('getIdToSlugMap')->willReturn([]);
        $this->registerMapper->method('getSlugToIdMap')->willReturn([]);
        $this->schemaMapper->method('getIdToSlugMap')->willReturn([]);
        $this->schemaMapper->method('getSlugToIdMap')->willReturn([]);

        // exportConfiguration calls findByConfiguration on all mappers
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

        $this->assertIsArray($result);
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('sources', $result['components']);
        $this->assertArrayHasKey('endpoints', $result['components']);
        $this->assertArrayHasKey('mappings', $result['components']);
        $this->assertArrayHasKey('rules', $result['components']);
        $this->assertArrayHasKey('jobs', $result['components']);
        $this->assertArrayHasKey('synchronizations', $result['components']);
    }
}
