<?php


/**
 * This file is part of the OpenConnector app.
 *
 * @package   OpenConnector
 * @category  Migration
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 * @version   1.0.0
 */

declare(strict_types=1);

namespace OCA\OpenConnector\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration for openconnector_sources table modifications.
 *
 * This migration alters the openconnector_sources table structure.
 *
 * @package   OpenConnector
 * @category  Migration
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class Version1Date20241206095007 extends SimpleMigrationStep
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
        // No operations required before schema changes.    }//end preSchemaChange()


    /**
     * Apply schema changes.
     *
     * Modifies the openconnector_sources table structure.
     *
     * @param  IOutput                   $output        Output handler for the migration
     * @param  Closure(): ISchemaWrapper $schemaClosure Closure that returns a schema wrapper
     * @param  array                     $options       Options for the migration
     * @return null|ISchemaWrapper      Modified schema or null if no changes
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /*
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();


        // Check if the sources table exists
    if ($schema->hasTable('openconnector_sources') === true) {
        $table = $schema->getTable('openconnector_sources');

        // Rename logRetention column to log_retention
        if ($table->hasColumn('logRetention') === true) {
            $table->dropColumn('logRetention');
            $table->addColumn('log_retention', Types::INTEGER)->setNotnull(false)->setDefault(3600);
        }

        // Rename errorRetention column to error_retention
        if ($table->hasColumn('errorRetention') === true) {
            $table->dropColumn('errorRetention');
            $table->addColumn('error_retention', Types::INTEGER)->setNotnull(false)->setDefault(86400);
        }
    }

        return $schema;

    }//end changeSchema()


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
        // No operations required after schema changes.    }//end postSchemaChange()    }//end postSchemaChange()
