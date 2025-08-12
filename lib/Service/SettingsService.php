<?php
/**
 * OpenConnector Settings Service
 *
 * This file contains the service class for handling settings in the OpenConnector application.
 *
 * @category Service
 * @package  OCA\OpenConnector\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.OpenConnector.nl
 */

namespace OCA\OpenConnector\Service;

use OCP\IAppConfig;
use OCP\IRequest;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use OCP\AppFramework\Http\JSONResponse;
use OC_App;
use OCA\OpenConnector\AppInfo\Application;

use OCA\OpenConnector\Db\CallLogMapper;
use OCA\OpenConnector\Db\JobLogMapper;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;

/**
 * Service for handling settings-related operations.
 *
 * Provides functionality for retrieving, saving, and loading settings,
 * as well as managing configuration for different object types.
 */
class SettingsService
{

    /**
     * This property holds the name of the application, which is used for identification and configuration purposes.
     *
     * @var string $appName The name of the app.
     */
    private string $appName;

    /**
     * This constant represents the unique identifier for the OpenConnector application, used to check its installation and status.
     *
     * @var string $openConnectorAppId The ID of the OpenConnector app.
     */
    private const OPENCONNECTOR_APP_ID = 'openconnector';

    /**
     * This constant defines the minimum version of the OpenConnector application that is required for compatibility and functionality.
     *
     * @var string $minOpenConnectorVersion The minimum required version of OpenConnector.
     */
    private const MIN_OPENCONNECTOR_VERSION = '1.0.0';


    /**
     * SettingsService constructor.
     *
     * @param IAppConfig                        $config                        App configuration interface.
     * @param IRequest                          $request                       Request interface.
     * @param ContainerInterface                $container                     Container for dependency injection.
     * @param IAppManager                       $appManager                    App manager interface.
     * @param CallLogMapper                     $callLogMapper                 Call log mapper for database operations.
     * @param JobLogMapper                      $jobLogMapper                  Job log mapper for database operations.
     * @param SynchronizationLogMapper          $synchronizationLogMapper      Synchronization log mapper for database operations.
     * @param SynchronizationContractLogMapper  $synchronizationContractLogMapper Synchronization contract log mapper for database operations.
     * @param SourceMapper                      $sourceMapper                  Source mapper for database operations.
     * @param SynchronizationMapper             $synchronizationMapper         Synchronization mapper for database operations.
     * @param MappingMapper                     $mappingMapper                 Mapping mapper for database operations.
     * @param JobMapper                         $jobMapper                     Job mapper for database operations.
     * @param RuleMapper                        $ruleMapper                    Rule mapper for database operations.
     * @param SynchronizationContractMapper     $synchronizationContractMapper Synchronization contract mapper for database operations.
     */
    public function __construct(
        private readonly IAppConfig $config, 
        private readonly IRequest $request, 
        private readonly ContainerInterface $container, 
        private readonly IAppManager $appManager,
        private readonly CallLogMapper $callLogMapper,
        private readonly JobLogMapper $jobLogMapper,
        private readonly SynchronizationLogMapper $synchronizationLogMapper,
        private readonly SynchronizationContractLogMapper $synchronizationContractLogMapper,
        private readonly SourceMapper $sourceMapper,
        private readonly SynchronizationMapper $synchronizationMapper,
        private readonly MappingMapper $mappingMapper,
        private readonly JobMapper $jobMapper,
        private readonly RuleMapper $ruleMapper,
        private readonly SynchronizationContractMapper $synchronizationContractMapper
    ) {
        // Set the application name for identification and configuration purposes.
        $this->appName = 'openconnector';

    }//end __construct()


    /**
     * Checks if OpenConnector is installed and meets version requirements.
     *
     * @param string|null $minVersion Minimum required version (e.g. '1.0.0').
     *
     * @return bool True if OpenConnector is installed and meets version requirements.
     */
    public function isOpenConnectorInstalled(?string $minVersion=self::MIN_OPENCONNECTOR_VERSION): bool
    {
        if ($this->appManager->isInstalled(self::OPENCONNECTOR_APP_ID) === false) {
            return false;
        }

        if ($minVersion === null) {
            return true;
        }

        $currentVersion = $this->appManager->getAppVersion(self::OPENCONNECTOR_APP_ID);
        return version_compare($currentVersion, $minVersion, '>=');

    }//end isOpenConnectorInstalled()


    /**
     * Checks if OpenConnector is enabled.
     *
     * @return bool True if OpenConnector is enabled.
     */
    public function isOpenConnectorEnabled(): bool
    {
        return $this->appManager->isEnabled(self::OPENCONNECTOR_APP_ID) === true;

    }//end isOpenConnectorEnabled()





    /**
     * Retrieve the current settings for OpenConnector.
     *
     * @return array The current settings configuration.
     * @throws \RuntimeException If settings retrieval fails.
     */
    public function getSettings(): array
    {
        try {
            $data = [];
            
            // Version information
            $data['version'] = [
                'appName' => 'Open Connector',
                'appVersion' => '1.0.0',
            ];



            // Retention Settings with defaults for OpenConnector log types
            $retentionConfig = $this->config->getValueString($this->appName, 'retention', '');
            if (empty($retentionConfig)) {
                $data['retention'] = [
                    'callLogRetention' => 2592000000, // 1 month default
                    'jobLogRetention' => 2592000000, // 1 month default
                    'syncLogRetention' => 2592000000, // 1 month default
                    'contractLogRetention' => 2592000000, // 1 month default
                ];
            } else {
                $retentionData = json_decode($retentionConfig, true);
                $data['retention'] = [
                    'callLogRetention' => $retentionData['callLogRetention'] ?? 2592000000,
                    'jobLogRetention' => $retentionData['jobLogRetention'] ?? 2592000000,
                    'syncLogRetention' => $retentionData['syncLogRetention'] ?? 2592000000,
                    'contractLogRetention' => $retentionData['contractLogRetention'] ?? 2592000000,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to retrieve settings: ' . $e->getMessage());
        }

    }//end getSettings()





    /**
     * Update the settings configuration.
     *
     * @param array $data The settings data to update.
     *
     * @return array The updated settings configuration.
     * @throws \RuntimeException If settings update fails.
     */
    public function updateSettings(array $data): array
    {
        try {
            // Handle Retention settings for OpenConnector log types
            if (isset($data['retention'])) {
                $retentionData = $data['retention'];
                $retentionConfig = [
                    'callLogRetention' => $retentionData['callLogRetention'] ?? 2592000000,
                    'jobLogRetention' => $retentionData['jobLogRetention'] ?? 2592000000,
                    'syncLogRetention' => $retentionData['syncLogRetention'] ?? 2592000000,
                    'contractLogRetention' => $retentionData['contractLogRetention'] ?? 2592000000,
                ];
                $this->config->setValueString($this->appName, 'retention', json_encode($retentionConfig));
            }

            // Return the updated settings
            return $this->getSettings();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update settings: ' . $e->getMessage());
        }

    }//end updateSettings()


    /**
     * Get the current publishing options.
     *
     * @return array The current publishing options configuration.
     * @throws \RuntimeException If publishing options retrieval fails.
     */
    public function getPublishingOptions(): array
    {
        try {
            // Retrieve publishing options from configuration with defaults to false.
            $publishingOptions = [
                // Convert string 'true'/'false' to boolean for auto publish attachments setting.
                'auto_publish_attachments'      => $this->config->getValueString($this->appName, 'auto_publish_attachments', 'false') === 'true',
                // Convert string 'true'/'false' to boolean for auto publish objects setting.
                'auto_publish_objects'          => $this->config->getValueString($this->appName, 'auto_publish_objects', 'false') === 'true',
                // Convert string 'true'/'false' to boolean for old style publishing view setting.
                'use_old_style_publishing_view' => $this->config->getValueString($this->appName, 'use_old_style_publishing_view', 'false') === 'true',
            ];

            return $publishingOptions;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to retrieve publishing options: '.$e->getMessage());
        }

    }//end getPublishingOptions()


    /**
     * Update the publishing options configuration.
     *
     * @param array $options The publishing options data to update.
     *
     * @return array The updated publishing options configuration.
     * @throws \RuntimeException If publishing options update fails.
     */
    public function updatePublishingOptions(array $options): array
    {
        try {
            // Define valid publishing option keys for security.
            $validOptions = [
                'auto_publish_attachments',
                'auto_publish_objects',
                'use_old_style_publishing_view',
            ];

            $updatedOptions = [];

            // Update each publishing option in the configuration.
            foreach ($validOptions as $option) {
                // Check if this option is provided in the input data.
                if (isset($options[$option]) === true) {
                    // Convert boolean or string to string format for storage.
                    $value = $options[$option] === true || $options[$option] === 'true' ? 'true' : 'false';
                    // Store the value in the configuration.
                    $this->config->setValueString($this->appName, $option, $value);
                    // Retrieve and convert back to boolean for the response.
                    $updatedOptions[$option] = $this->config->getValueString($this->appName, $option) === 'true';
                }
            }

            return $updatedOptions;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update publishing options: '.$e->getMessage());
        }//end try

    }//end updatePublishingOptions()


    /**
     * Rebase all logs with current retention settings.
     *
     * This method sets expiry dates for logs based on current retention settings,
     * ensuring all existing logs follow the new retention policies.
     *
     * @return array Array containing the rebase operation results
     * @throws \RuntimeException If the rebase operation fails
     */
    public function rebaseObjectsAndLogs(): array
    {
        try {
            $startTime = new \DateTime();
            $results = [
                'startTime' => $startTime,
                'retentionResults' => [],
                'errors' => [],
            ];

            // Get current settings
            $settings = $this->getSettings();
            
            // Set expiry dates based on retention settings for OpenConnector logs
            $retention = $settings['retention'] ?? [];
            
            try {
                // Set expiry dates for call logs
                if (isset($retention['callLogRetention']) && $retention['callLogRetention'] > 0) {
                    $callLogsUpdated = $this->callLogMapper->setExpiryDate($retention['callLogRetention']);
                    $results['retentionResults']['callLogsUpdated'] = $callLogsUpdated;
                }

                // Set expiry dates for job logs
                if (isset($retention['jobLogRetention']) && $retention['jobLogRetention'] > 0) {
                    $jobLogsUpdated = $this->jobLogMapper->setExpiryDate($retention['jobLogRetention']);
                    $results['retentionResults']['jobLogsUpdated'] = $jobLogsUpdated;
                }

                // Set expiry dates for synchronization logs
                if (isset($retention['syncLogRetention']) && $retention['syncLogRetention'] > 0) {
                    $syncLogsUpdated = $this->synchronizationLogMapper->setExpiryDate($retention['syncLogRetention']);
                    $results['retentionResults']['syncLogsUpdated'] = $syncLogsUpdated;
                }

                // Set expiry dates for contract logs
                if (isset($retention['contractLogRetention']) && $retention['contractLogRetention'] > 0) {
                    $contractLogsUpdated = $this->synchronizationContractLogMapper->setExpiryDate($retention['contractLogRetention']);
                    $results['retentionResults']['contractLogsUpdated'] = $contractLogsUpdated;
                }
                
            } catch (\Exception $e) {
                $error = 'Failed to set expiry dates for logs: ' . $e->getMessage();
                error_log('[SettingsService] ' . $error);
                $results['errors'][] = $error;
            }

            $results['endTime'] = new \DateTime();
            $results['duration'] = $results['endTime']->diff($startTime)->format('%H:%I:%S');
            $results['success'] = empty($results['errors']);

            return $results;

        } catch (\Exception $e) {
            throw new \RuntimeException('Rebase operation failed: ' . $e->getMessage());
        }
    }//end rebaseObjectsAndLogs()


    /**
     * General rebase method that can be called from any settings section.
     *
     * This is an alias for rebaseObjectsAndLogs() to provide a consistent interface
     * for all sections that have rebase buttons.
     *
     * @return array Array containing the rebase operation results
     * @throws \RuntimeException If the rebase operation fails
     */
    public function rebase(): array
    {
        return $this->rebaseObjectsAndLogs();
    }//end rebase()





    /**
     * Get statistics for the settings dashboard.
     *
     * This method provides warning counts for logs that need attention,
     * as well as total counts for all OpenConnector log types.
     *
     * @return array Array containing warning counts and total counts
     * @throws \RuntimeException If statistics retrieval fails
     */
    public function getStats(): array
    {
        try {
            $stats = [
                'warnings' => [
                    // Log warnings
                    'callLogsWithoutExpiry' => 0,
                    'callLogsWithoutExpirySize' => 0,
                    'jobLogsWithoutExpiry' => 0,
                    'jobLogsWithoutExpirySize' => 0,
                    'syncLogsWithoutExpiry' => 0,
                    'syncLogsWithoutExpirySize' => 0,
                    'contractLogsWithoutExpiry' => 0,
                    'contractLogsWithoutExpirySize' => 0,
                    'expiredCallLogs' => 0,
                    'expiredCallLogsSize' => 0,
                    'expiredJobLogs' => 0,
                    'expiredJobLogsSize' => 0,
                    'expiredSyncLogs' => 0,
                    'expiredSyncLogsSize' => 0,
                    'expiredContractLogs' => 0,
                    'expiredContractLogsSize' => 0,
                    // Entity warnings
                    'sourcesWithoutExpiry' => 0,
                    'sourcesWithoutExpirySize' => 0,
                    'synchronizationsWithoutExpiry' => 0,
                    'synchronizationsWithoutExpirySize' => 0,
                    'mappingsWithoutExpiry' => 0,
                    'mappingsWithoutExpirySize' => 0,
                    'jobsWithoutExpiry' => 0,
                    'jobsWithoutExpirySize' => 0,
                    'rulesWithoutExpiry' => 0,
                    'rulesWithoutExpirySize' => 0,
                    'contractsWithoutExpiry' => 0,
                    'contractsWithoutExpirySize' => 0,
                    'expiredSources' => 0,
                    'expiredSourcesSize' => 0,
                    'expiredSynchronizations' => 0,
                    'expiredSynchronizationsSize' => 0,
                    'expiredMappings' => 0,
                    'expiredMappingsSize' => 0,
                    'expiredJobs' => 0,
                    'expiredJobsSize' => 0,
                    'expiredRules' => 0,
                    'expiredRulesSize' => 0,
                    'expiredContracts' => 0,
                    'expiredContractsSize' => 0,
                ],
                'totals' => [
                    // Log totals
                    'totalCallLogs' => 0,
                    'totalCallLogsSize' => 0,
                    'totalJobLogs' => 0,
                    'totalJobLogsSize' => 0,
                    'totalSyncLogs' => 0,
                    'totalSyncLogsSize' => 0,
                    'totalContractLogs' => 0,
                    'totalContractLogsSize' => 0,
                    // Entity totals
                    'totalSources' => 0,
                    'totalSourcesSize' => 0,
                    'totalSynchronizations' => 0,
                    'totalSynchronizationsSize' => 0,
                    'totalMappings' => 0,
                    'totalMappingsSize' => 0,
                    'totalJobs' => 0,
                    'totalJobsSize' => 0,
                    'totalRules' => 0,
                    'totalRulesSize' => 0,
                    'totalContracts' => 0,
                    'totalContractsSize' => 0,
                ],
                'lastUpdated' => new \DateTime(),
            ];

            // Count log warnings (logs without expiry date)
            $stats['warnings']['callLogsWithoutExpiry'] = $this->callLogMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['callLogsWithoutExpirySize'] = $this->callLogMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['jobLogsWithoutExpiry'] = $this->jobLogMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['jobLogsWithoutExpirySize'] = $this->jobLogMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['syncLogsWithoutExpiry'] = $this->synchronizationLogMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['syncLogsWithoutExpirySize'] = $this->synchronizationLogMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['contractLogsWithoutExpiry'] = $this->synchronizationContractLogMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['contractLogsWithoutExpirySize'] = $this->synchronizationContractLogMapper->size(['expires' => ['IS NULL', '']]);

            // Count entity warnings (entities without expiry date)
            $stats['warnings']['sourcesWithoutExpiry'] = $this->sourceMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['sourcesWithoutExpirySize'] = $this->sourceMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['synchronizationsWithoutExpiry'] = $this->synchronizationMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['synchronizationsWithoutExpirySize'] = $this->synchronizationMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['mappingsWithoutExpiry'] = $this->mappingMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['mappingsWithoutExpirySize'] = $this->mappingMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['jobsWithoutExpiry'] = $this->jobMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['jobsWithoutExpirySize'] = $this->jobMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['rulesWithoutExpiry'] = $this->ruleMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['rulesWithoutExpirySize'] = $this->ruleMapper->size(['expires' => ['IS NULL', '']]);

            $stats['warnings']['contractsWithoutExpiry'] = $this->synchronizationContractMapper->count(['expires' => ['IS NULL', '']]);
            $stats['warnings']['contractsWithoutExpirySize'] = $this->synchronizationContractMapper->size(['expires' => ['IS NULL', '']]);

            // Count expired logs using mapper methods
            $stats['warnings']['expiredCallLogs'] = $this->callLogMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredCallLogsSize'] = $this->callLogMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredJobLogs'] = $this->jobLogMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredJobLogsSize'] = $this->jobLogMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredSyncLogs'] = $this->synchronizationLogMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredSyncLogsSize'] = $this->synchronizationLogMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredContractLogs'] = $this->synchronizationContractLogMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredContractLogsSize'] = $this->synchronizationContractLogMapper->size(['expires' => ['<', 'NOW()']]);

            // Count expired entities using mapper methods
            $stats['warnings']['expiredSources'] = $this->sourceMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredSourcesSize'] = $this->sourceMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredSynchronizations'] = $this->synchronizationMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredSynchronizationsSize'] = $this->synchronizationMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredMappings'] = $this->mappingMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredMappingsSize'] = $this->mappingMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredJobs'] = $this->jobMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredJobsSize'] = $this->jobMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredRules'] = $this->ruleMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredRulesSize'] = $this->ruleMapper->size(['expires' => ['<', 'NOW()']]);

            $stats['warnings']['expiredContracts'] = $this->synchronizationContractMapper->count(['expires' => ['<', 'NOW()']]);
            $stats['warnings']['expiredContractsSize'] = $this->synchronizationContractMapper->size(['expires' => ['<', 'NOW()']']);

            // Count total logs and their sizes
            $stats['totals']['totalCallLogs'] = $this->callLogMapper->count();
            $stats['totals']['totalCallLogsSize'] = $this->callLogMapper->size();

            $stats['totals']['totalJobLogs'] = $this->jobLogMapper->count();
            $stats['totals']['totalJobLogsSize'] = $this->jobLogMapper->size();

            $stats['totals']['totalSyncLogs'] = $this->synchronizationLogMapper->count();
            $stats['totals']['totalSyncLogsSize'] = $this->synchronizationLogMapper->size();

            $stats['totals']['totalContractLogs'] = $this->synchronizationContractLogMapper->count();
            $stats['totals']['totalContractLogsSize'] = $this->synchronizationContractLogMapper->size();

            // Count total entities and their sizes
            $stats['totals']['totalSources'] = $this->sourceMapper->count();
            $stats['totals']['totalSourcesSize'] = $this->sourceMapper->size();

            $stats['totals']['totalSynchronizations'] = $this->synchronizationMapper->count();
            $stats['totals']['totalSynchronizationsSize'] = $this->synchronizationMapper->size();

            $stats['totals']['totalMappings'] = $this->mappingMapper->count();
            $stats['totals']['totalMappingsSize'] = $this->mappingMapper->size();

            $stats['totals']['totalJobs'] = $this->jobMapper->count();
            $stats['totals']['totalJobsSize'] = $this->jobMapper->size();

            $stats['totals']['totalRules'] = $this->ruleMapper->count();
            $stats['totals']['totalRulesSize'] = $this->ruleMapper->size();

            $stats['totals']['totalContracts'] = $this->synchronizationContractMapper->count();
            $stats['totals']['totalContractsSize'] = $this->synchronizationContractMapper->size();

            return $stats;

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }//end getStats()





}//end class
