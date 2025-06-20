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

    protected function setUp(): void
    {
        $this->sourceMapper = $this->createMock(SourceMapper::class);
        $this->endpointMapper = $this->createMock(EndpointMapper::class);
        $this->mappingMapper = $this->createMock(MappingMapper::class);
        $this->ruleMapper = $this->createMock(RuleMapper::class);
        $this->jobMapper = $this->createMock(JobMapper::class);
        $this->synchronizationMapper = $this->createMock(SynchronizationMapper::class);

        $this->configurationService = new ConfigurationService(
            $this->sourceMapper,
            $this->endpointMapper,
            $this->mappingMapper,
            $this->ruleMapper,
            $this->jobMapper,
            $this->synchronizationMapper
        );
    }

    public function testGetEntitiesByConfiguration(): void
    {
        $configurationId = 'test-config-1';
        $expectedSources = [new Source()];
        $expectedEndpoints = [new Endpoint()];
        $expectedMappings = [new Mapping()];
        $expectedRules = [new Rule()];
        $expectedJobs = [new Job()];
        $expectedSynchronizations = [new Synchronization()];

        $this->sourceMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedSources);

        $this->endpointMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedEndpoints);

        $this->mappingMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedMappings);

        $this->ruleMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedRules);

        $this->jobMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedJobs);

        $this->synchronizationMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedSynchronizations);

        $result = $this->configurationService->getEntitiesByConfiguration($configurationId);

        $this->assertEquals($expectedSources, $result['sources']);
        $this->assertEquals($expectedEndpoints, $result['endpoints']);
        $this->assertEquals($expectedMappings, $result['mappings']);
        $this->assertEquals($expectedRules, $result['rules']);
        $this->assertEquals($expectedJobs, $result['jobs']);
        $this->assertEquals($expectedSynchronizations, $result['synchronizations']);
    }

    public function testExportConfiguration(): void
    {
        $configurationId = 'test-config-1';
        $expectedSources = [new Source()];
        $expectedEndpoints = [new Endpoint()];
        $expectedMappings = [new Mapping()];
        $expectedRules = [new Rule()];
        $expectedJobs = [new Job()];
        $expectedSynchronizations = [new Synchronization()];

        $this->sourceMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedSources);

        $this->endpointMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedEndpoints);

        $this->mappingMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedMappings);

        $this->ruleMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedRules);

        $this->jobMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedJobs);

        $this->synchronizationMapper->expects($this->once())
            ->method('findByConfiguration')
            ->with($configurationId)
            ->willReturn($expectedSynchronizations);

        $result = $this->configurationService->exportConfiguration($configurationId);

        $this->assertEquals($configurationId, $result['configurationId']);
        $this->assertArrayHasKey('exportDate', $result);
        $this->assertEquals($expectedSources, $result['entities']['sources']);
        $this->assertEquals($expectedEndpoints, $result['entities']['endpoints']);
        $this->assertEquals($expectedMappings, $result['entities']['mappings']);
        $this->assertEquals($expectedRules, $result['entities']['rules']);
        $this->assertEquals($expectedJobs, $result['entities']['jobs']);
        $this->assertEquals($expectedSynchronizations, $result['entities']['synchronizations']);
    }
} 