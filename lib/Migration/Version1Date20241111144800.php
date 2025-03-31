<?php

/**
 * This file is part of the OpenConnector app.
 *
 * @package     OpenConnector
 * @category    Migration
 * @author      Conduction Development Team <dev@conduction.nl>
 * @copyright   2024 Conduction B.V.
 * @license     EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link        https://OpenConnector.app
 * @version     1.0.0
 */

declare(strict_types=1);

namespace OCA\OpenConnector\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\IDBConnection;

/**
 * Migration for openconnector_sync_contracts_origin_id_index table modifications.
 *
 * This migration alters the openconnector_sync_contracts_origin_id_index table structure.
 *
 * @package     OpenConnector
 * @category    Migration
 * @author      Conduction Development Team <dev@conduction.nl>
 * @copyright   2024 Conduction B.V.
 * @license     EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link        https://OpenConnector.app
 */
class Version1Date20241111144800 extends SimpleMigrationStep
{
    /**
     * Operations to be performed before schema changes.
     *
     * @param IOutput                   $output        Output handler for the migration
     * @param Closure(): ISchemaWrapper $schemaClosure Closure that returns a schema wrapper
     * @param array                     $options       Options for the migration
     *
     * @return void
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        // No operations required before schema changes.
    }//end preSchemaChange()


    /**
     * Apply schema changes.
     *
     * Modifies the openconnector_sync_contracts_origin_id_index table structure.
     *
     * @param  IOutput                   $output        Output handler for the migration
     * @param  Closure(): ISchemaWrapper $schemaClosure Closure that returns a schema wrapper
     * @param  array                     $options       Options for the migration
     * @return null|ISchemaWrapper      Modified schema or null if no changes
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /**
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();
        $table  = $schema->getTable('openconnector_synchronization_contracts');

        // Step 1: Add new columns for 'origin_id' and 'origin_hash'.
        if ($table->hasColumn('origin_id') === false) {
            $table->addColumn(
                'origin_id',
                Types::STRING,
                [
                    'length'  => 255,
                    'notnull' => true,
                ]
            );
        }

        if ($table->hasColumn('origin_hash') === false) {
            $table->addColumn(
                'origin_hash',
                Types::STRING,
                [
                    'length'  => 255,
                    'notnull' => false,
                ]
            );
        }

        // Step 4: Adjust indexes in preparation for data migration.
        if ($table->hasIndex('openconnector_sync_contracts_source_id_index') === true) {
            $table->dropIndex('openconnector_sync_contracts_source_id_index');
        }

        if ($table->hasIndex('openconnector_sync_contracts_origin_id_index') === false) {
            $table->addIndex(['origin_id'], 'openconnector_sync_contracts_origin_id_index');
        }

        if ($table->hasIndex('openconnector_sync_contracts_sync_source_index') === true) {
            $table->dropIndex('openconnector_sync_contracts_sync_source_index');
        }

        if ($table->hasIndex('openconnector_sync_contracts_sync_origin_index') === false) {
            $table->addIndex(['synchronization_id', 'origin_id'], 'openconnector_sync_contracts_sync_origin_index');
        }

        return $schema;
    }//end changeSchema()

    /**
     * Post-schema change hook
     *
     * Copy data from old columns to new columns and drop old columns
     *
     * @param IOutput                   $output        Output for the migration
     * @param Closure(): ISchemaWrapper $schemaClosure Closure that returns the schema
     * @param array                     $options       Options for the migration
     * 
     * @return void
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        /**
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();
        $table  = $schema->getTable('openconnector_synchronization_contracts');

        // Step 2: Copy data from old columns to new columns.
        if (($table->hasColumn('origin_id') === true) && ($table->hasColumn('origin_hash') === true)
            && ($table->hasColumn('source_id') === true) && ($table->hasColumn('source_hash') === true)
        ) {
            $this->connection->executeQuery(
                "
				UPDATE oc_openconnector_synchronization_contracts
				SET origin_id = source_id, origin_hash = source_hash
				WHERE source_id IS NOT NULL
			"
            );
        }

        if ($table->hasColumn('source_id') === true) {
            $table->dropColumn('source_id');
        }

        if ($table->hasColumn('source_hash') === true) {
            $table->dropColumn('source_hash');
        }
    }//end postSchemaChange()
}//end class
