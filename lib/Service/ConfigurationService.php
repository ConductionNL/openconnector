<?php

namespace OCA\OpenConnector\Service;

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

/**
 * Class ConfigurationService
 *
 * Service class for managing configurations and their associated entities.
 *
 * @package OCA\OpenConnector\Service
 * @category Service
 * @author OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license AGPL-3.0
 * @version 1.0.0
 * @link https://github.com/OpenConnector/openconnector
 */
class ConfigurationService
{
    /**
     * @var SourceMapper
     */
    private SourceMapper $sourceMapper;

    /**
     * @var EndpointMapper
     */
    private EndpointMapper $endpointMapper;

    /**
     * @var MappingMapper
     */
    private MappingMapper $mappingMapper;

    /**
     * @var RuleMapper
     */
    private RuleMapper $ruleMapper;

    /**
     * @var JobMapper
     */
    private JobMapper $jobMapper;

    /**
     * @var SynchronizationMapper
     */
    private SynchronizationMapper $synchronizationMapper;

    /**
     * ConfigurationService constructor.
     *
     * @param SourceMapper $sourceMapper
     * @param EndpointMapper $endpointMapper
     * @param MappingMapper $mappingMapper
     * @param RuleMapper $ruleMapper
     * @param JobMapper $jobMapper
     * @param SynchronizationMapper $synchronizationMapper
     */
    public function __construct(
        SourceMapper $sourceMapper,
        EndpointMapper $endpointMapper,
        MappingMapper $mappingMapper,
        RuleMapper $ruleMapper,
        JobMapper $jobMapper,
        SynchronizationMapper $synchronizationMapper
    ) {
        $this->sourceMapper = $sourceMapper;
        $this->endpointMapper = $endpointMapper;
        $this->mappingMapper = $mappingMapper;
        $this->ruleMapper = $ruleMapper;
        $this->jobMapper = $jobMapper;
        $this->synchronizationMapper = $synchronizationMapper;
    }

    /**
     * Get all entities associated with a specific configuration ID, indexed by their slug.
     *
     * @param string $configurationId The ID of the configuration to get entities for
     * @return array<string,array> Array containing all entities grouped by type and indexed by slug
     */
    public function getEntitiesByConfiguration(string $configurationId): array
    {
        // Helper function to index entities by slug
        $indexBySlug = function(array $entities): array {
            $indexedEntities = [];
            foreach ($entities as $entity) {
                if (isset($entity['slug'])) {
                    $indexedEntities[$entity['slug']] = $entity;
                }
            }
            return $indexedEntities;
        };

        return [
            'sources' => $indexBySlug($this->sourceMapper->findByConfiguration($configurationId)),
            'endpoints' => $indexBySlug($this->endpointMapper->findByConfiguration($configurationId)),
            'mappings' => $indexBySlug($this->mappingMapper->findByConfiguration($configurationId)),
            'rules' => $indexBySlug($this->ruleMapper->findByConfiguration($configurationId)),
            'jobs' => $indexBySlug($this->jobMapper->findByConfiguration($configurationId)),
            'synchronizations' => $indexBySlug($this->synchronizationMapper->findByConfiguration($configurationId)),
        ];
    }

    /**
     * Export all entities associated with a specific configuration ID to JSON.
     * Entities are organized by components following OAS structure.
     *
     * @param string $configurationId The ID of the configuration to export
     * @return array<string,array> JSON-serializable array containing all entities
     */
    public function exportConfiguration(string $configurationId): array
    {
        $entities = $this->getEntitiesByConfiguration($configurationId);
        
        // Organize entities by components
        $components = [
            'components' => [
                'sources' => $this->organizeEntitiesByComponent($entities['sources']),
                'endpoints' => $this->organizeEntitiesByComponent($entities['endpoints']),
                'mappings' => $this->organizeEntitiesByComponent($entities['mappings']),
                'rules' => $this->organizeEntitiesByComponent($entities['rules']),
                'jobs' => $this->organizeEntitiesByComponent($entities['jobs']),
                'synchronizations' => $this->organizeEntitiesByComponent($entities['synchronizations']),
            ],
        ];
        
        return $components;
    }

    /**
     * Organize entities by their component type.
     *
     * @param array $entities Array of entities to organize
     * @return array Organized entities by component
     */
    private function organizeEntitiesByComponent(array $entities): array
    {
        $organized = [];
        foreach ($entities as $entity) {
            $component = $this->getEntityComponent($entity);
            if (!isset($organized[$component])) {
                $organized[$component] = [];
            }
            $organized[$component][] = $entity;
        }
        return $organized;
    }

    /**
     * Get the component type for an entity.
     *
     * @param mixed $entity The entity to get the component type for
     * @return string The component type
     */
    private function getEntityComponent($entity): string
    {
        if ($entity instanceof Source) {
            return $entity->getType() ?? 'default';
        }
        if ($entity instanceof Endpoint) {
            return $entity->getTargetType() ?? 'default';
        }
        if ($entity instanceof Mapping) {
            return 'mapping';
        }
        if ($entity instanceof Rule) {
            return $entity->getType() ?? 'default';
        }
        if ($entity instanceof Job) {
            return 'job';
        }
        if ($entity instanceof Synchronization) {
            return 'sync';
        }
        return 'default';
    }

} 