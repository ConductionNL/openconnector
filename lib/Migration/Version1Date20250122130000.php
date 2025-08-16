<?php

declare(strict_types=1);

/**
 * OpenConnector Log Size Migration
 *
 * This migration adds size columns to all log tables for better log retention management.
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
 * Migration step to add size columns to all log tables.
 *
 * This migration adds a 'size' column to track the size of log entries,
 * which is useful for log retention management and storage optimization.
 *
 * @package OCA\OpenConnector\Migration
 * @category Migration
 * @author OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license EUPL-1.2
 * @version 1.0.0
 * @link https://github.com/ConductionNL/OpenConnector
 */
class Version1Date20250122130000 extends SimpleMigrationStep 
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
     * Adds the 'size' column to all log tables to track log entry sizes
     * for better retention management and storage optimization.
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

        // List of log tables that need the size column
        $logTables = [
            'openconnector_call_logs',
            'openconnector_job_logs',
            'openconnector_synchronization_logs',
            'openconnector_synchronization_contract_logs',
        ];

        // Add size column to each log table
        foreach ($logTables as $tableName) {
            if ($schema->hasTable($tableName)) {
                $table = $schema->getTable($tableName);

                // Add size column if it doesn't exist
                if (!$table->hasColumn('size')) {
                    $table->addColumn('size', Types::BIGINT, [
                        'notnull' => false,
                        'default' => null,
                        'comment' => 'Size of the log entry in bytes for retention management',
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
     * Calculates and sets the size for existing log entries based on
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
        
        // Calculate sizes for existing log entries
        $this->calculateExistingSizes($connection, $output);
    }//end postSchemaChange()

    /**
     * Calculate and update sizes for existing log entries.
     *
     * This method estimates the size of existing log entries by calculating
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
            'openconnector_call_logs' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(status_message), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(request, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(response, '$')), 0) + 
                COALESCE(LENGTH(user_id), 0) + 
                COALESCE(LENGTH(session_id), 0)
            ",
            'openconnector_job_logs' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(level), 0) + 
                COALESCE(LENGTH(message), 0) + 
                COALESCE(LENGTH(job_id), 0) + 
                COALESCE(LENGTH(job_list_id), 0) + 
                COALESCE(LENGTH(job_class), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(arguments, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(stack_trace, '$')), 0) + 
                COALESCE(LENGTH(user_id), 0) + 
                COALESCE(LENGTH(session_id), 0)
            ",
            'openconnector_synchronization_logs' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(message), 0) + 
                COALESCE(LENGTH(synchronization_id), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(result, '$')), 0) + 
                COALESCE(LENGTH(user_id), 0) + 
                COALESCE(LENGTH(session_id), 0)
            ",
            'openconnector_synchronization_contract_logs' => "
                COALESCE(LENGTH(uuid), 0) + 
                COALESCE(LENGTH(message), 0) + 
                COALESCE(LENGTH(synchronization_id), 0) + 
                COALESCE(LENGTH(synchronization_contract_id), 0) + 
                COALESCE(LENGTH(synchronization_log_id), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(source, '$')), 0) + 
                COALESCE(LENGTH(JSON_EXTRACT(target, '$')), 0) + 
                COALESCE(LENGTH(target_result), 0) + 
                COALESCE(LENGTH(user_id), 0) + 
                COALESCE(LENGTH(session_id), 0)
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
