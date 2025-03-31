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
 * Migration for creating event-related tables and adding rules to endpoints.
 *
 * This migration creates new tables for events, event subscriptions, event messages,
 * and rules. It also adds a rules column to the endpoints table.
 *
 * @package   OpenConnector
 * @category  Migration
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class Version1Date20250109093325 extends SimpleMigrationStep
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
     * Creates new tables for events, event subscriptions, event messages, and rules.
     * Also adds a rules column to the endpoints table.
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


        // Create events table if it doesn't exist
    if (!$schema->hasTable('openconnector_events')) {
        $table = $schema->createTable('openconnector_events');
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20]);
        $table->addColumn('uuid', Types::STRING, ['notnull' => true, 'length' => 36]);
        $table->addColumn('source', Types::STRING, ['notnull' => true, 'length' => 255]);
        $table->addColumn('type', Types::STRING, ['notnull' => true, 'length' => 255]);
        $table->addColumn('specversion', Types::STRING, ['notnull' => true, 'length' => 10, 'default' => '1.0']);
        $table->addColumn('time', Types::DATETIME, ['notnull' => true]);
        $table->addColumn('datacontenttype', Types::STRING, ['notnull' => true, 'length' => 255, 'default' => 'application/json']);
        $table->addColumn('dataschema', Types::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('subject', Types::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('data', Types::JSON, ['notnull' => false]);
        $table->addColumn('user_id', Types::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('processed', Types::DATETIME, ['notnull' => false]);
        $table->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 255, 'default' => 'pending']);
        $table->addColumn('created', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('updated', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['uuid'], 'openconnector_events_uuid_index');
    }

        // Create event subscriptions table if it doesn't exist
    if (!$schema->hasTable('openconnector_event_subscriptions')) {
        $table = $schema->createTable('openconnector_event_subscriptions');
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20]);
        $table->addColumn('uuid', Types::STRING, ['notnull' => true, 'length' => 36]);
        $table->addColumn('reference', Types::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('version', Types::STRING, ['notnull' => true, 'length' => 255, 'default' => '0.0.1']);
        $table->addColumn('source', Types::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('types', Types::JSON, ['notnull' => false]);
        $table->addColumn('config', Types::JSON, ['notnull' => false]);
        $table->addColumn('filters', Types::JSON, ['notnull' => false]);
        $table->addColumn('sink', Types::STRING, ['notnull' => true, 'length' => 255]);
        $table->addColumn('protocol', Types::STRING, ['notnull' => true, 'length' => 50]);
        $table->addColumn('protocol_settings', Types::JSON, ['notnull' => false]);
        $table->addColumn('style', Types::STRING, ['notnull' => true, 'length' => 10, 'default' => 'push']);
        $table->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 20, 'default' => 'active']);
        $table->addColumn('user_id', Types::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('consumer_id', Types::BIGINT, ['notnull' => true, 'length' => 20]);
        $table->addColumn('created', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('updated', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['uuid'], 'openconnector_event_subs_uuid_index');
        $table->addIndex(['consumer_id'], 'openconnector_event_subs_consumer_index');
        $table->addIndex(['user_id'], 'openconnector_event_subs_user_index');
        $table->addIndex(['status'], 'openconnector_event_subs_status_index');
    }//end if

        // Create event messages table if it doesn't exist
    if (!$schema->hasTable('openconnector_event_messages')) {
        $table = $schema->createTable('openconnector_event_messages');
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20]);
        $table->addColumn('uuid', Types::STRING, ['notnull' => true, 'length' => 36]);
        $table->addColumn('event_id', Types::BIGINT, ['notnull' => true, 'length' => 20]);
        $table->addColumn('consumer_id', Types::BIGINT, ['notnull' => true, 'length' => 20]);
        $table->addColumn('subscription_id', Types::BIGINT, ['notnull' => true, 'length' => 20]);
        $table->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 20, 'default' => 'pending']);
        $table->addColumn('payload', Types::JSON, ['notnull' => true]);
        $table->addColumn('last_response', Types::JSON, ['notnull' => false]);
        $table->addColumn('retry_count', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('last_attempt', Types::DATETIME, ['notnull' => false]);
        $table->addColumn('next_attempt', Types::DATETIME, ['notnull' => false]);
        $table->addColumn('created', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('updated', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['uuid'], 'openconnector_event_msg_uuid_index');
        $table->addIndex(['event_id'], 'openconnector_event_msg_event_index');
        $table->addIndex(['consumer_id'], 'openconnector_event_msg_consumer_index');
        $table->addIndex(['subscription_id'], 'openconnector_event_msg_sub_index');
        $table->addIndex(['status'], 'openconnector_event_msg_status_index');
        $table->addIndex(['next_attempt'], 'openconnector_event_msg_next_index');
    }//end if

        // Create rules table if it doesn't exist
    if (!$schema->hasTable('openconnector_rules')) {
        $table = $schema->createTable('openconnector_rules');
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20]);
        $table->addColumn('uuid', Types::STRING, ['notnull' => true, 'length' => 36]);
        $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 255]);
        $table->addColumn('description', Types::TEXT, ['notnull' => false]);
        $table->addColumn('reference', Types::STRING, ['notnull' => false, 'length' => 255]);
        $table->addColumn('version', Types::STRING, ['notnull' => true, 'length' => 255, 'default' => '0.0.1']);
        $table->addColumn('action', Types::STRING, ['notnull' => true, 'length' => 20]);
        // create, read, update, delete
        $table->addColumn('timing', Types::STRING, ['notnull' => true, 'length' => 10, 'default' => 'before']);
        // before or after
        $table->addColumn('conditions', Types::JSON, ['notnull' => false]);
        $table->addColumn('type', Types::STRING, ['notnull' => true, 'length' => 50]);
        // mapping, error, script, synchronization
        $table->addColumn('configuration', Types::JSON, ['notnull' => false]);
        $table->addColumn('order', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('created', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('updated', Types::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['uuid'], 'openconnector_rules_uuid_index');
        $table->addIndex(['action'], 'openconnector_rules_action_index');
        $table->addIndex(['type'], 'openconnector_rules_type_index');
        $table->addIndex(['order'], 'openconnector_rules_order_index');
    }//end if

        // Add rules relationship to endpoints table
    if ($schema->hasTable('openconnector_endpoints')) {
        $table = $schema->getTable('openconnector_endpoints');
        if (!$table->hasColumn('rules')) {
            $table->addColumn('rules', Types::JSON, ['notnull' => false]);
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
