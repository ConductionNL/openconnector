<?php

// CI-specific bootstrap that uses real database connections
// instead of MockMapper to avoid signature compatibility issues

use OCA\OpenConnector\AppInfo\Application;

// Set up the application
$app = new Application();
$app->register();

// Set up test database connection
$config = \OC::$server->getConfig();
$config->setSystemValue('dbtype', 'sqlite');
$config->setSystemValue('dbname', ':memory:');

// Initialize the database
$connection = \OC::$server->getDatabaseConnection();
$connection->beginTransaction();

// Create test tables
$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_sources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_endpoints (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_consumers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_event_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        event_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_event_subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        event_id INTEGER NOT NULL,
        consumer_id INTEGER NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_synchronizations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        source_id INTEGER NOT NULL,
        endpoint_id INTEGER NOT NULL,
        status VARCHAR(50) NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_synchronization_contracts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        synchronization_id INTEGER NOT NULL,
        contract_data TEXT,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_synchronization_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        synchronization_id INTEGER NOT NULL,
        log_level VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_synchronization_contract_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contract_id INTEGER NOT NULL,
        log_level VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        job_type VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_job_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER NOT NULL,
        log_level VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_call_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        endpoint_id INTEGER NOT NULL,
        call_type VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_mappings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        source_field VARCHAR(255) NOT NULL,
        target_field VARCHAR(255) NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS openconnector_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL,
        rule_type VARCHAR(100) NOT NULL,
        rule_data TEXT,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$connection->commit();

// Clean up after tests
register_shutdown_function(function() use ($connection) {
    $connection->rollback();
});
