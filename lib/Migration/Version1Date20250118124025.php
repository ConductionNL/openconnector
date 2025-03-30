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

/**
 * Migration for openconnector_sync_logs_created_index table modifications.
 *
 * This migration alters the openconnector_sync_logs_created_index table structure.
 *
 * @package     OpenConnector
 * @category    Migration
 * @author      Conduction Development Team <dev@conduction.nl>
 * @copyright   2024 Conduction B.V.
 * @license     EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link        https://OpenConnector.app
 */
class Version1Date20250118124025 extends SimpleMigrationStep
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
     * Modifies the openconnector_sync_logs_created_index table structure.
     *
     * @param  IOutput                   $output        Output handler for the migration
     * @param  Closure(): ISchemaWrapper $schemaClosure Closure that returns a schema wrapper
     * @param  array                     $options       Options for the migration
     * @return null|ISchemaWrapper      Modified schema or null if no changes
     */
    {
        /*
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();

        if (!$schema->hasTable('openconnector_synchronization_logs')) {
            $table = $schema->createTable('openconnector_synchronization_logs');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20]);
            $table->addColumn('uuid', Types::STRING, ['notnull' => true, 'length' => 36]);
            $table->addColumn('message', Types::STRING, ['notnull' => false, 'length' => 255]);
            $table->addColumn('synchronization_id', Types::STRING, ['notnull' => true, 'length' => 36]);
            $table->addColumn('result', Types::JSON, ['notnull' => false]);
            $table->addColumn('user_id', Types::STRING, ['notnull' => false, 'length' => 255]);
            $table->addColumn('session_id', Types::STRING, ['notnull' => false, 'length' => 255]);
            $table->addColumn('test', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $table->addColumn('force', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $table->addColumn('execution_time', Types::INTEGER, ['notnull' => true, 'default' => 3600]);
            $table->addColumn('created', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
            $table->addColumn('expires', Types::DATETIME, ['notnull' => false]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['uuid'], 'openconnector_sync_logs_uuid_index');
            $table->addIndex(['synchronization_id'], 'openconnector_sync_logs_sync_id_index');
            $table->addIndex(['user_id'], 'openconnector_sync_logs_user_id_index');
            $table->addIndex(['created'], 'openconnector_sync_logs_created_index');
        }//end if

        if ($schema->hasTable(tableName: 'openconnector_synchronization_contracts') === true) {
            $table = $schema->getTable(tableName: 'openconnector_synchronization_contracts');
            $table->addColumn('target_last_action', Types::STRING, ['notnull' => false, 'length' => 6]);
            // 6 chars is enough for 'create', 'update', 'delete'
        }

        if ($schema->hasTable(tableName: 'openconnector_synchronization_contract_logs') === true) {
            $table = $schema->getTable(tableName: 'openconnector_synchronization_contract_logs');
            $table->addColumn('synchronization_log_id', Types::STRING, ['notnull' => false, 'length' => 36]);
            // synchronization_log_id
            $table->addColumn('target_result', Types::STRING, ['notnull' => false, 'length' => 6]);
            // target_result
            $table->addColumn('test', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $table->addColumn('force', Types::BOOLEAN, ['notnull' => true, 'default' => false]);

            $table->addIndex(['synchronization_log_id'], 'openconnector_sync_logs_sync_index');
        }

        return $schema;

    }//end changeSchema()


    /**
     * @param IOutput                   $output
     * @param Closure(): ISchemaWrapper $schemaClosure
     * @param array                     $options
     */
    {

    }//end postSchemaChange()


}//end class

    /**
     * Operations to be performed after schema changes.
     *
     * @param IOutput                   $output        Output handler for the migration
     * @param Closure(): ISchemaWrapper $schemaClosure Closure that returns a schema wrapper
     * @param array                     $options       Options for the migration
     *
     * @return void
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        // No operations required after schema changes.
    }//end postSchemaChange()


}//end class
