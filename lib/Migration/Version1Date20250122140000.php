<?php

declare(strict_types=1);

/**
 * OpenConnector Entity Expires and Size Migration
 *
 * This migration adds expires and size columns to all entity tables for better retention management.
 *
 * @category Migration
 * @package  OCA\OpenConnector\Migration
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.openconnector.nl
 */

namespace OCA\OpenConnector\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration step to add expires and size columns to all entity tables.
 *
 * This migration adds 'expires' and 'size' columns to all main entity tables,
 * which is useful for retention management and storage optimization.
 *
 * @package OCA\OpenConnector\Migration
 * @category Migration
 * @author OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license EUPL-1.2
 * @version 1.0.0
 * @link https://github.com/ConductionNL/OpenConnector
 */
class Version1Date20250122140000 extends SimpleMigrationStep 
{
    /**
     * Pre-schema change hook.
     *
     * @param IOutput $output Migration output interface
     * @param Closure $schemaClosure Closure that returns the current schema
     * @param array   $options Migration options
     *
     * @return void
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void 
    {
        // No pre-schema changes needed
    }//end preSchemaChange()

    /**
     * Schema change implementation.
     *
     * Adds the 'expires' and 'size' columns to all entity tables to track
     * entity expiration and sizes for better retention management.
     *
     * @param IOutput $output Migration output interface
     * @param Closure $schemaClosure Closure that returns the current schema
     * @param array   $options Migration options
     *
     * @return ISchemaWrapper|null The modified schema
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper 
    {
        /**
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();

        // List of entity tables that need the expires and size columns
        $entityTables = [
            'openconnector_sources',
            'openconnector_synchronizations',
            'openconnector_mappings',
            'openconnector_jobs',
            'openconnector_rules',
            'openconnector_synchronization_contracts',
        ];

        // Add expires and size columns to each entity table
        foreach ($entityTables as $tableName) {
            if ($schema->hasTable($tableName)) {
                $table = $schema->getTable($tableName);

                // Add expires column if it doesn't exist
                if (!$table->hasColumn('expires')) {
                    $table->addColumn('expires', Types::DATETIME, [
                        'notnull' => false,
                        'default' => null,
                        'comment' => 'Expiration date for this entity',
                    ]);

                    $output->info("Added 'expires' column to table: " . $tableName);
                }

                // Add size column if it doesn't exist
                if (!$table->hasColumn('size')) {
                    $table->addColumn('size', Types::BIGINT, [
                        'notnull' => false,
                        'default' => null,
                        'comment' => 'Size of the entity in bytes for retention management',
                    ]);

                    $output->info("Added 'size' column to table: " . $tableName);
                }
            } else {
                $output->warning("Table not found: " . $tableName);
            }
        }

        return $schema;
    }//end changeSchema()

    /**
     * Post-schema change hook.
     *
     * Calculates and sets the size for existing entity entries based on
     * their JSON content and text fields.
     *
     * @param IOutput $output Migration output interface
     * @param Closure $schemaClosure Closure that returns the current schema
     * @param array   $options Migration options
     *
     * @return void
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void 
    {
        // Get the database connection
        $connection = \OC::$server->get(\OCP\IDBConnection::class);
        
        // Calculate sizes for existing entity entries
        $this->calculateExistingSizes($connection, $output);
    }//end postSchemaChange()

    /**
     * Calculate and update sizes for existing entity entries.
     *
     * This method estimates the size of existing entity entries by calculating
     * the total length of their text and JSON fields.
     *
     * @param \OCP\IDBConnection $connection Database connection
     * @param IOutput            $output     Migration output interface
     *
     * @return void
     */
    private function calculateExistingSizes(\OCP\IDBConnection $connection, IOutput $output): void 
    {
        // Define size calculation queries for each table
        $sizeCalculations = [
            'openconnector_sources' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(name), 0) + 
                COALESCE(LENGTH(description), 0) + 
                COALESCE(LENGTH(reference), 0) + 
                COALESCE(LENGTH(version), 0) + 
                COALESCE(LENGTH(location), 0) + 
                COALESCE(LENGTH(type), 0) + 
                COALESCE(LENGTH(authorization_header), 0) + 
                COALESCE(LENGTH(auth), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(authentication_config, '$')), 0) + 
                COALESCE(LENGTH(authorization_passthrough_method), 0) + 
                COALESCE(LENGTH(locale), 0) + 
                COALESCE(LENGTH(accept), 0) + 
                COALESCE(LENGTH(jwt), 0) + 
                COALESCE(LENGTH(jwt_id), 0) + 
                COALESCE(LENGTH(secret), 0) + 
                COALESCE(LENGTH(username), 0) + 
                COALESCE(LENGTH(password), 0) + 
                COALESCE(LENGTH(apikey), 0) + 
                COALESCE(LENGTH(documentation), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(logging_config, '$')), 0) + 
                COALESCE(LENGTH(oas), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(paths, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(headers, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(translation_config, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(configuration, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(endpoints_config, '$')), 0) + 
                COALESCE(LENGTH(status), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(configurations, '$')), 0) + 
                COALESCE(LENGTH(slug), 0)
            ",
            'openconnector_synchronizations' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(name), 0) + 
                COALESCE(LENGTH(description), 0) + 
                COALESCE(LENGTH(reference), 0) + 
                COALESCE(LENGTH(version), 0) + 
                COALESCE(LENGTH(source_id), 0) + 
                COALESCE(LENGTH(source_type), 0) + 
                COALESCE(LENGTH(source_hash), 0) + 
                COALESCE(LENGTH(source_hash_mapping), 0) + 
                COALESCE(LENGTH(source_target_mapping), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(source_config, '$')), 0) + 
                COALESCE(LENGTH(target_id), 0) + 
                COALESCE(LENGTH(target_type), 0) + 
                COALESCE(LENGTH(target_hash), 0) + 
                COALESCE(LENGTH(target_source_mapping), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(target_config, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(conditions, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(follow_ups, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(actions, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(configurations, '$')), 0) + 
                COALESCE(LENGTH(status), 0) + 
                COALESCE(LENGTH(slug), 0)
            ",
            'openconnector_mappings' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(reference), 0) + 
                COALESCE(LENGTH(version), 0) + 
                COALESCE(LENGTH(name), 0) + 
                COALESCE(LENGTH(description), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(mapping, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(unset, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(cast, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(configurations, '$')), 0) + 
                COALESCE(LENGTH(slug), 0)
            ",
            'openconnector_jobs' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(name), 0) + 
                COALESCE(LENGTH(description), 0) + 
                COALESCE(LENGTH(reference), 0) + 
                COALESCE(LENGTH(version), 0) + 
                COALESCE(LENGTH(job_class), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(arguments, '$')), 0) + 
                COALESCE(LENGTH(user_id), 0) + 
                COALESCE(LENGTH(job_list_id), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(configurations, '$')), 0) + 
                COALESCE(LENGTH(status), 0) + 
                COALESCE(LENGTH(slug), 0)
            ",
            'openconnector_rules' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(name), 0) + 
                COALESCE(LENGTH(description), 0) + 
                COALESCE(LENGTH(reference), 0) + 
                COALESCE(LENGTH(version), 0) + 
                COALESCE(LENGTH(action), 0) + 
                COALESCE(LENGTH(timing), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(conditions, '$')), 0) + 
                COALESCE(LENGTH(type), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(configuration, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(configurations, '$')), 0) + 
                COALESCE(LENGTH(slug), 0)
            ",
            'openconnector_synchronization_contracts' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(version), 0) + 
                COALESCE(LENGTH(synchronization_id), 0) + 
                COALESCE(LENGTH(origin_id), 0) + 
                COALESCE(LENGTH(origin_hash), 0) + 
                COALESCE(LENGTH(target_id), 0) + 
                COALESCE(LENGTH(target_hash), 0) + 
                COALESCE(LENGTH(target_last_action), 0) + 
                COALESCE(LENGTH(source_id), 0) + 
                COALESCE(LENGTH(source_hash), 0)
            ",
        ];

        foreach ($sizeCalculations as $tableName => $sizeCalculation) {
            try {
                // Update size for existing entries where size is null
                $query = $connection->getQueryBuilder();
                $query->update($tableName)
                    ->set('size', $query->createFunction('(' . $sizeCalculation . ')'))
                    ->where($query->expr()->isNull('size'));

                $affectedRows = $query->executeStatement();
                
                if ($affectedRows > 0) {
                    $output->info("Updated size for {$affectedRows} existing entries in table: " . $tableName);
                }
            } catch (\Exception $e) {
                $output->warning("Failed to calculate sizes for table {$tableName}: " . $e->getMessage());
            }
        }
    }//end calculateExistingSizes()
}//end class
