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
 * Migration for openconnector_consumers table modifications.
 *
 * This migration alters the openconnector_consumers table structure.
 *
 * @package     OpenConnector
 * @category    Migration
 * @author      Conduction Development Team <dev@conduction.nl>
 * @copyright   2024 Conduction B.V.
 * @license     EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link        https://OpenConnector.app
 */
class Version1Date20241218122932 extends SimpleMigrationStep
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
     * Modifies the openconnector_consumers table structure.
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

        if ($schema->hasTable(tableName: 'openconnector_consumers') === true) {
            $table = $schema->getTable(tableName: 'openconnector_consumers');
            $table->addColumn('authorization_configuration', Types::JSON);
            $table->addColumn('user_id', Types::STRING)->setNotnull(false);
        }

        if ($schema->hasTable(tableName: 'openconnector_endpoints') === true) {
            $table = $schema->getTable(tableName: 'openconnector_endpoints');
            $table->addColumn('conditions', Types::JSON);
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
