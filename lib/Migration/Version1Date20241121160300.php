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
use OCP\IDBConnection;

/**
 * Migration for openconnector_sources table modifications.
 *
 * This migration alters the openconnector_sources table structure by adding
 * rate limit-related columns to track API usage.
 *
 * @package   OpenConnector
 * @category  Migration
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class Version1Date20241121160300 extends SimpleMigrationStep
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
    }


    /**
     * Apply schema changes.
     *
     * Modifies the openconnector_sources table structure by adding
     * rate limit columns for tracking API usage limits.
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


        // Get the sources table
        $table = $schema->getTable('openconnector_sources');

        // Add rate limit limit column to track the maximum number of requests allowed
    if ($table->hasColumn('rate_limit_limit') === false) {
        $table->addColumn(
            'rate_limit_limit',
            Types::INTEGER,
            [
                'notnull' => false,
                'default' => null,
            ]
        );
    }

        // Add rate limit remaining column to track remaining requests
    if ($table->hasColumn('rate_limit_remaining') === false) {
        $table->addColumn(
            'rate_limit_remaining',
            Types::INTEGER,
            [
                'notnull' => false,
                'default' => null,
            ]
        );
    }

        // Add rate limit reset column to track when limits reset
    if ($table->hasColumn('rate_limit_reset') === false) {
        $table->addColumn(
            'rate_limit_reset',
            Types::INTEGER,
            [
                'notnull' => false,
                'default' => null,
            ]
        );
    }

        // Add rate limit window column to track the time window for limits
    if ($table->hasColumn('rate_limit_window') === false) {
        $table->addColumn(
            'rate_limit_window',
            Types::INTEGER,
            [
                'notnull' => false,
                'default' => null,
            ]
        );
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
    }//end postSchemaChange()


}//end class
