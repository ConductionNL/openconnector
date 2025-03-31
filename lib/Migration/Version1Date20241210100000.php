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
class Version1Date20241210100000 extends SimpleMigrationStep
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


        // Check if sources table exists
    if ($schema->hasTable(tableName: 'openconnector_sources') === true) {
        $table = $schema->getTable(tableName: 'openconnector_sources');

        // Add source_hash_mapping column if it doesn't exist
        if ($table->hasColumn('source_hash_mapping') === false) {
            // Add a nullable string column for source hash mapping
            $table->addColumn('source_hash_mapping', Types::STRING)->setNotnull(false);
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
