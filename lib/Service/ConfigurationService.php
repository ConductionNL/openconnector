<?php
/**
 * OpenConnector Configuration Service
 *
 * This file contains the service class for handling configuration imports and exports
 * in the OpenConnector application, supporting OpenAPI format for endpoints, jobs,
 * rules, sources, and synchronizations.
 *
 * @category Service
 * @package  OCA\OpenConnector\Service
 *
 * @author   Ruben Linde <ruben@nextcloud.com>
 * @copyright 2024 Conduction B.V. (https://conduction.nl)
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://github.com/cloud-py-api/openconnector
 */

namespace OCA\OpenConnector\Service;

use Exception;
use JsonException;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ILogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Class ConfigurationService
 *
 * Service for importing and exporting configurations in OpenAPI format.
 * Handles endpoints, jobs, rules, sources, and synchronizations.
 *
 * @package OCA\OpenConnector\Service
 */
class ConfigurationService
{
    /**
     * Endpoint mapper instance for handling endpoint operations.
     *
     * @var EndpointMapper The endpoint mapper instance.
     */
    private readonly EndpointMapper $endpointMapper;

    /**
     * Job mapper instance for handling job operations.
     *
     * @var JobMapper The job mapper instance.
     */
    private readonly JobMapper $jobMapper;

    /**
     * Rule mapper instance for handling rule operations.
     *
     * @var RuleMapper The rule mapper instance.
     */
    private readonly RuleMapper $ruleMapper;

    /**
     * Source mapper instance for handling source operations.
     *
     * @var SourceMapper The source mapper instance.
     */
    private readonly SourceMapper $sourceMapper;

    /**
     * Synchronization mapper instance for handling synchronization operations.
     *
     * @var SynchronizationMapper The synchronization mapper instance.
     */
    private readonly SynchronizationMapper $synchronizationMapper;

    /**
     * Mapping mapper instance for handling mapping operations.
     *
     * @var MappingMapper The mapping mapper instance.
     */
    private readonly MappingMapper $mappingMapper;

    /**
     * Logger instance for logging operations.
     *
     * @var LoggerInterface The logger instance.
     */
    private readonly LoggerInterface $logger;

    /**
     * Map of endpoints indexed by ID during export.
     *
     * @var array<string, Endpoint> Endpoints indexed by ID.
     */
    private array $endpointsMap = [];

    /**
     * Map of jobs indexed by ID during export.
     *
     * @var array<string, Job> Jobs indexed by ID.
     */
    private array $jobsMap = [];

    /**
     * Map of rules indexed by ID during export.
     *
     * @var array<string, Rule> Rules indexed by ID.
     */
    private array $rulesMap = [];

    /**
     * Map of sources indexed by ID during export.
     *
     * @var array<string, Source> Sources indexed by ID.
     */
    private array $sourcesMap = [];

    /**
     * Map of synchronizations indexed by ID during export.
     *
     * @var array<string, Synchronization> Synchronizations indexed by ID.
     */
    private array $synchronizationsMap = [];

    /**
     * Constructor
     *
     * @param EndpointMapper        $endpointMapper        The endpoint mapper instance
     * @param JobMapper            $jobMapper            The job mapper instance
     * @param RuleMapper           $ruleMapper           The rule mapper instance
     * @param SourceMapper         $sourceMapper         The source mapper instance
     * @param SynchronizationMapper $synchronizationMapper The synchronization mapper instance
     * @param MappingMapper        $mappingMapper        The mapping mapper instance
     * @param LoggerInterface      $logger               The logger instance
     */
    public function __construct(
        EndpointMapper $endpointMapper,
        JobMapper $jobMapper,
        RuleMapper $ruleMapper,
        SourceMapper $sourceMapper,
        SynchronizationMapper $synchronizationMapper,
        MappingMapper $mappingMapper,
        LoggerInterface $logger
    ) {
        $this->endpointMapper = $endpointMapper;
        $this->jobMapper = $jobMapper;
        $this->ruleMapper = $ruleMapper;
        $this->sourceMapper = $sourceMapper;
        $this->synchronizationMapper = $synchronizationMapper;
        $this->mappingMapper = $mappingMapper;
        $this->logger = $logger;
    }

    /**
     * Export configuration for a register
     *
     * @param int $registerId The ID of the register to export configuration for
     * @param bool $includeObjects Whether to include objects in the export
     *
     * @return array The exported configuration
     *
     * @throws Exception If configuration is invalid
     */
    public function exportConfig(int $registerId, bool $includeObjects = false): array
    {
        // Reset the maps for this export
        $this->endpointsMap = [];
        $this->jobsMap = [];
        $this->rulesMap = [];
        $this->sourcesMap = [];
        $this->synchronizationsMap = [];

        // Initialize OpenAPI specification with default values
        $openApiSpec = [
            'openapi' => '3.0.0',
            'components' => [
                'endpoints'        => [],
                'sources'          => [],
                'mappings'         => [],
                'jobs'             => [],
                'synchronizations' => [],
                'rules'            => [],
            ],
        ];

        // Arrays to collect rule and mapping IDs
        $ruleIds = [];
        $mappingIds = [];

        // Get all endpoints that target this register
        $endpoints = $this->endpointMapper->getByRegister($registerId);

        foreach ($endpoints as $endpoint) {
            // Store endpoint in map by ID for reference
            $this->endpointsMap[$endpoint->getId()] = $endpoint;

            // Export the endpoint
            $openApiSpec['components']['endpoints'][$endpoint->getUuid()] = $this->exportEndpoint($endpoint);

            // Collect rule IDs from endpoint rules
            if (is_array($endpoint->getRules())) {
                $ruleIds = array_merge($ruleIds, $endpoint->getRules());
            }

            // Collect mapping IDs from endpoint mappings
            if ($endpoint->getInputMapping() !== null) {
                $mappingIds[] = (int) $endpoint->getInputMapping();
            }
            if ($endpoint->getOutputMapping() !== null) {
                $mappingIds[] = (int) $endpoint->getOutputMapping();
            }
        }

        // Get all synchronizations that target this register
        $synchronizations = $this->synchronizationMapper->getByRegister($registerId);

        foreach ($synchronizations as $synchronization) {
            // Store synchronization in map by ID for reference
            $this->synchronizationsMap[$synchronization->getId()] = $synchronization;

            // Export the synchronization
            $openApiSpec['components']['synchronizations'][$synchronization->getUuid()] = $this->exportSynchronization($synchronization);

            // Get and export source associated with this synchronization
            if ($synchronization->getSourceId() !== null) {
                $source = $this->sourceMapper->find($synchronization->getSourceId());
                if ($source !== null) {
                    // Store source in map by ID for reference
                    $this->sourcesMap[$source->getId()] = $source;

                    $openApiSpec['components']['sources'][$source->getUuid()] = $this->exportSource($source);
                }
            }

            // Collect rule IDs from synchronization follow-ups and actions
            $followUps = $synchronization->getFollowUps();
            if (is_array($followUps)) {
                foreach ($followUps as $followUp) {
                    if (isset($followUp['ruleId'])) {
                        $ruleIds[] = $followUp['ruleId'];
                    }
                }
            }

            $actions = $synchronization->getActions();
            if (is_array($actions)) {
                foreach ($actions as $action) {
                    if (isset($action['ruleId'])) {
                        $ruleIds[] = $action['ruleId'];
                    }
                }
            }

            // Collect mapping IDs from synchronization mappings
            if ($synchronization->getSourceTargetMapping() !== null) {
                $mappingIds[] = (int) $synchronization->getSourceTargetMapping();
            }
            if ($synchronization->getTargetSourceMapping() !== null) {
                $mappingIds[] = (int) $synchronization->getTargetSourceMapping();
            }
        }

        // @todo: fix this:
        // // Get all jobs that target this register
        // $jobs = $this->jobMapper->findAll(null, null, [
        //     'targetType' => 'register/schema',
        //     'targetId' => $registerId,
        // ]);

        // foreach ($jobs as $job) {
        //     // Store job in map by ID for reference
        //     $this->jobsMap[$job->getId()] = $job;

        //     // Export the job
        //     $openApiSpec['components']['jobs'][$job->getUuid()] = $this->exportJob($job);
        // }

        // Get and export all collected rules
        $ruleIds = array_unique($ruleIds);
        foreach ($ruleIds as $ruleId) {
            try {
                $rule = $this->ruleMapper->find($ruleId);
                if ($rule !== null) {
                    $openApiSpec['components']['rules'][$rule->getUuid()] = $this->exportRule($rule);
                }
            } catch (DoesNotExistException $e) {
                $this->logger->warning('Rule with ID ' . $ruleId . ' not found during export.');
            }
        }

        // Get and export all collected mappings
        $mappingIds = array_unique($mappingIds);
        foreach ($mappingIds as $mappingId) {
            try {
                // Skip if not a valid integer ID
                if (is_numeric($mappingId) === false || empty($mappingId) === true) {
                    $this->logger->warning('Invalid mapping ID: ' . $mappingId);
                    continue;
                }
                
                $mapping = $this->mappingMapper->find((int)$mappingId);
                if ($mapping !== null) {
                    $openApiSpec['components']['mappings'][$mapping->getUuid()] = $this->exportMapping($mapping);
                }
            } catch (DoesNotExistException $e) {
                $this->logger->warning('Mapping with ID ' . $mappingId . ' not found during export.');
            }
        }

        return $openApiSpec;
    }

    /**
     * Export an endpoint to OpenAPI format
     *
     * @param Endpoint $endpoint The endpoint to export
     *
     * @return array The OpenAPI endpoint specification
     */
    private function exportEndpoint(Endpoint $endpoint): array
    {
        $endpointArray = $endpoint->jsonSerialize();

        unset($endpointArray['id'], $endpointArray['uuid']);

        return $endpointArray;
    }

    /**
     * Export a job to OpenAPI format
     *
     * @param Job $job The job to export
     *
     * @return array The OpenAPI job specification
     */
    private function exportJob(Job $job): array
    {
        $jobArray = $job->jsonSerialize();

        unset($jobArray['id'], $jobArray['uuid']);

        return $jobArray;
    }

    /**
     * Export a rule to OpenAPI format
     *
     * @param Rule $rule The rule to export
     *
     * @return array The OpenAPI rule specification
     */
    private function exportRule(Rule $rule): array
    {
        $ruleArray = $rule->jsonSerialize();

        unset($ruleArray['id'], $ruleArray['uuid']);

        return $ruleArray;
    }

    /**
     * Export a source to OpenAPI format
     *
     * @param Source $source The source to export
     *
     * @return array The OpenAPI source specification
     */
    private function exportSource(Source $source): array
    {
        $sourceArray = $source->jsonSerialize();

        // @todo: maybe we should "set" instead of unset? To prevent new sensitive data from being added to exports in the future?
        unset($sourceArray['id'], $sourceArray['uuid'], 
        $sourceArray['authorizationHeader'], $sourceArray['auth'], $sourceArray['authenticationConfig'],
        $sourceArray['authorizationPassthroughMethod'], $sourceArray['locale'], $sourceArray['accept'],
        $sourceArray['jwt'], $sourceArray['jwtId'], $sourceArray['secret'], $sourceArray['password'],
        $sourceArray['apikey'], $sourceArray['headers'], $sourceArray['configuration']);

        return $sourceArray;
    }

    /**
     * Export a synchronization to OpenAPI format
     *
     * @param Synchronization $synchronization The synchronization to export
     *
     * @return array The OpenAPI synchronization specification
     */
    private function exportSynchronization(Synchronization $synchronization): array
    {
        $synchronizationArray = $synchronization->jsonSerialize();

        unset($synchronizationArray['id'], $synchronizationArray['uuid']);

        return $synchronizationArray;
    }

    /**
     * Export a mapping to OpenAPI format
     *
     * @param Mapping $mapping The mapping to export
     *
     * @return array The OpenAPI mapping specification
     */
    private function exportMapping(Mapping $mapping): array
    {
        $mappingArray = $mapping->jsonSerialize();

        unset($mappingArray['id'], $mappingArray['uuid']);

        return $mappingArray;
    }

    /**
     * Import configuration from a JSON file
     *
     * @param string      $jsonContent The configuration JSON content
     * @param string|null $owner       The owner of the configuration components
     *
     * @return array Array of created/updated entities
     *
     * @throws JsonException If JSON parsing fails
     * @throws Exception If configuration is invalid
     *
     * @phpstan-return array{
     *     endpoints: array<Endpoint>,
     *     jobs: array<Job>,
     *     rules: array<Rule>,
     *     sources: array<Source>,
     *     synchronizations: array<Synchronization>
     * }
     */
    public function importFromJson(string $jsonContent, ?string $owner = null): array
    {
        // Reset the maps for this import
        $this->endpointsMap = [];
        $this->jobsMap = [];
        $this->rulesMap = [];
        $this->sourcesMap = [];
        $this->synchronizationsMap = [];

        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error('Failed to parse JSON: ' . $e->getMessage());
            throw new Exception('Invalid JSON format: ' . $e->getMessage());
        }

        $result = [
            'endpoints'        => [],
            'sources'          => [],
            'mappings'         => [],
            'jobs'             => [],
            'synchronizations' => [],
            'rules'            => [],
        ];

        // Import sources first as they are referenced by synchronizations
        if (isset($data['components']['sources'])) {
            foreach ($data['components']['sources'] as $uuid => $sourceData) {
                $source = $this->importSource($sourceData, $owner);
                if ($source !== null) {
                    // Store source in map by UUID for reference
                    $this->sourcesMap[$uuid] = $source;
                    $result['sources'][] = $source;
                }
            }
        }

        // Import endpoints next as they are referenced by rules
        if (isset($data['components']['endpoints'])) {
            foreach ($data['components']['endpoints'] as $uuid => $endpointData) {
                $endpoint = $this->importEndpoint($endpointData, $owner);
                if ($endpoint !== null) {
                    // Store endpoint in map by UUID for reference
                    $this->endpointsMap[$uuid] = $endpoint;
                    $result['endpoints'][] = $endpoint;
                }
            }
        }

        // Import rules after endpoints
        if (isset($data['components']['rules'])) {
            foreach ($data['components']['rules'] as $uuid => $ruleData) {
                // Link rule to endpoint using the map
                if (isset($ruleData['endpointUuid']) && isset($this->endpointsMap[$ruleData['endpointUuid']])) {
                    $ruleData['endpointId'] = $this->endpointsMap[$ruleData['endpointUuid']]->getId();
                }

                $rule = $this->importRule($ruleData, $owner);
                if ($rule !== null) {
                    // Store rule in map by UUID for reference
                    $this->rulesMap[$uuid] = $rule;
                    $result['rules'][] = $rule;
                }
            }
        }

        // Import synchronizations after sources
        if (isset($data['components']['synchronizations'])) {
            foreach ($data['components']['synchronizations'] as $uuid => $syncData) {
                // Link synchronization to source using the map
                if (isset($syncData['sourceUuid']) && isset($this->sourcesMap[$syncData['sourceUuid']])) {
                    $syncData['sourceId'] = $this->sourcesMap[$syncData['sourceUuid']]->getId();
                }

                $synchronization = $this->importSynchronization($syncData, $owner);
                if ($synchronization !== null) {
                    // Store synchronization in map by UUID for reference
                    $this->synchronizationsMap[$uuid] = $synchronization;
                    $result['synchronizations'][] = $synchronization;
                }
            }
        }

        // Import jobs last as they might reference other entities
        if (isset($data['components']['jobs'])) {
            foreach ($data['components']['jobs'] as $uuid => $jobData) {
                $job = $this->importJob($jobData, $owner);
                if ($job !== null) {
                    // Store job in map by UUID for reference
                    $this->jobsMap[$uuid] = $job;
                    $result['jobs'][] = $job;
                }
            }
        }

        return $result;
    }

    /**
     * Import an endpoint from configuration data
     *
     * @param array       $data  The endpoint data
     * @param string|null $owner The owner of the endpoint
     *
     * @return Endpoint|null The imported endpoint or null if skipped
     */
    private function importEndpoint(array $data, ?string $owner = null): ?Endpoint
    {
        try {
            // Check if endpoint already exists by UUID
            $existingEndpoint = null;
            try {
                $existingEndpoint = $this->endpointMapper->findByUuid($data['uuid']);
            } catch (DoesNotExistException $e) {
                // Endpoint doesn't exist, we'll create a new one
            }

            if ($existingEndpoint !== null) {
                // Compare versions using version_compare for proper semver comparison
                if (version_compare($data['version'], $existingEndpoint->getVersion(), '<=')) {
                    $this->logger->info('Skipping endpoint import as existing version is newer or equal.');
                    return null;
                }

                // Update existing endpoint
                $existingEndpoint->hydrate($data);
                if ($owner !== null) {
                    $existingEndpoint->setOwner($owner);
                }

                return $this->endpointMapper->update($existingEndpoint);
            }

            // Create new endpoint
            $endpoint = new Endpoint();
            $endpoint->hydrate($data);
            if ($owner !== null) {
                $endpoint->setOwner($owner);
            }

            return $this->endpointMapper->insert($endpoint);
        } catch (Exception $e) {
            $this->logger->error('Failed to import endpoint: ' . $e->getMessage());
            throw new Exception('Failed to import endpoint: ' . $e->getMessage());
        }
    }

    /**
     * Import a source from configuration data
     *
     * @param array       $data  The source data
     * @param string|null $owner The owner of the source
     *
     * @return Source|null The imported source or null if skipped
     */
    private function importSource(array $data, ?string $owner = null): ?Source
    {
        try {
            // Check if source already exists by UUID
            $existingSource = null;
            try {
                $existingSource = $this->sourceMapper->findByUuid($data['uuid']);
            } catch (DoesNotExistException $e) {
                // Source doesn't exist, we'll create a new one
            }

            if ($existingSource !== null) {
                // Compare versions using version_compare for proper semver comparison
                if (version_compare($data['version'], $existingSource->getVersion(), '<=')) {
                    $this->logger->info('Skipping source import as existing version is newer or equal.');
                    return null;
                }

                // Update existing source
                $existingSource->hydrate($data);
                if ($owner !== null) {
                    $existingSource->setOwner($owner);
                }

                return $this->sourceMapper->update($existingSource);
            }

            // Create new source
            $source = new Source();
            $source->hydrate($data);
            if ($owner !== null) {
                $source->setOwner($owner);
            }

            return $this->sourceMapper->insert($source);
        } catch (Exception $e) {
            $this->logger->error('Failed to import source: ' . $e->getMessage());
            throw new Exception('Failed to import source: ' . $e->getMessage());
        }
    }

    /**
     * Import a rule from configuration data
     *
     * @param array       $data  The rule data
     * @param string|null $owner The owner of the rule
     *
     * @return Rule|null The imported rule or null if skipped
     */
    private function importRule(array $data, ?string $owner = null): ?Rule
    {
        try {
            // Check if rule already exists by UUID
            $existingRule = null;
            try {
                $existingRule = $this->ruleMapper->findByUuid($data['uuid']);
            } catch (DoesNotExistException $e) {
                // Rule doesn't exist, we'll create a new one
            }

            if ($existingRule !== null) {
                // Compare versions using version_compare for proper semver comparison
                if (version_compare($data['version'], $existingRule->getVersion(), '<=')) {
                    $this->logger->info('Skipping rule import as existing version is newer or equal.');
                    return null;
                }

                // Update existing rule
                $existingRule->hydrate($data);
                if ($owner !== null) {
                    $existingRule->setOwner($owner);
                }

                return $this->ruleMapper->update($existingRule);
            }

            // Create new rule
            $rule = new Rule();
            $rule->hydrate($data);
            if ($owner !== null) {
                $rule->setOwner($owner);
            }

            return $this->ruleMapper->insert($rule);
        } catch (Exception $e) {
            $this->logger->error('Failed to import rule: ' . $e->getMessage());
            throw new Exception('Failed to import rule: ' . $e->getMessage());
        }
    }

    /**
     * Import a synchronization from configuration data
     *
     * @param array       $data  The synchronization data
     * @param string|null $owner The owner of the synchronization
     *
     * @return Synchronization|null The imported synchronization or null if skipped
     */
    private function importSynchronization(array $data, ?string $owner = null): ?Synchronization
    {
        try {
            // Check if synchronization already exists by UUID
            $existingSynchronization = null;
            try {
                $existingSynchronization = $this->synchronizationMapper->findByUuid($data['uuid']);
            } catch (DoesNotExistException $e) {
                // Synchronization doesn't exist, we'll create a new one
            }

            if ($existingSynchronization !== null) {
                // Compare versions using version_compare for proper semver comparison
                if (version_compare($data['version'], $existingSynchronization->getVersion(), '<=')) {
                    $this->logger->info('Skipping synchronization import as existing version is newer or equal.');
                    return null;
                }

                // Update existing synchronization
                $existingSynchronization->hydrate($data);
                if ($owner !== null) {
                    $existingSynchronization->setOwner($owner);
                }

                return $this->synchronizationMapper->update($existingSynchronization);
            }

            // Create new synchronization
            $synchronization = new Synchronization();
            $synchronization->hydrate($data);
            if ($owner !== null) {
                $synchronization->setOwner($owner);
            }

            return $this->synchronizationMapper->insert($synchronization);
        } catch (Exception $e) {
            $this->logger->error('Failed to import synchronization: ' . $e->getMessage());
            throw new Exception('Failed to import synchronization: ' . $e->getMessage());
        }
    }

    /**
     * Import a job from configuration data
     *
     * @param array       $data  The job data
     * @param string|null $owner The owner of the job
     *
     * @return Job|null The imported job or null if skipped
     */
    private function importJob(array $data, ?string $owner = null): ?Job
    {
        try {
            // Check if job already exists by UUID
            $existingJob = null;
            try {
                $existingJob = $this->jobMapper->findByUuid($data['uuid']);
            } catch (DoesNotExistException $e) {
                // Job doesn't exist, we'll create a new one
            }

            if ($existingJob !== null) {
                // Compare versions using version_compare for proper semver comparison
                if (version_compare($data['version'], $existingJob->getVersion(), '<=')) {
                    $this->logger->info('Skipping job import as existing version is newer or equal.');
                    return null;
                }

                // Update existing job
                $existingJob->hydrate($data);
                if ($owner !== null) {
                    $existingJob->setOwner($owner);
                }

                return $this->jobMapper->update($existingJob);
            }

            // Create new job
            $job = new Job();
            $job->hydrate($data);
            if ($owner !== null) {
                $job->setOwner($owner);
            }

            return $this->jobMapper->insert($job);
        } catch (Exception $e) {
            $this->logger->error('Failed to import job: ' . $e->getMessage());
            throw new Exception('Failed to import job: ' . $e->getMessage());
        }
    }
}
