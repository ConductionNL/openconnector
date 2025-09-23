<?php

declare(strict_types=1);

/**
 * Bootstrap file for OpenConnector unit tests
 * 
 * This file sets up the necessary environment for running unit tests
 * for the OpenConnector application.
 */

// Set up error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone for consistent test results
date_default_timezone_set('UTC');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment variables
if (!defined('TESTING')) {
    define('TESTING', true);
}

// Mock Nextcloud constants and functions that might be needed
if (!defined('OCP_IUser_CLASS')) {
    // Define any necessary constants for testing
    define('OCP_IUser_CLASS', 'OCP\IUser');
}

// Mock missing OCP classes that are not available in test environment
if (!class_exists('OCP\AppFramework\Db\QBMapper')) {
    class_alias('OCP\AppFramework\Db\Mapper', 'OCP\AppFramework\Db\QBMapper');
}

if (!interface_exists('OCP\IUserManager')) {
    interface OCP\IUserManager {
        public function get(string $uid): ?OCP\IUser;
        public function userExists(string $uid): bool;
    }
}

if (!interface_exists('OCP\IUser')) {
    interface OCP\IUser {
        public function getUID(): string;
        public function getDisplayName(): string;
        public function getEMailAddress(): string;
    }
}

if (!interface_exists('OCP\IUserSession')) {
    interface OCP\IUserSession {
        public function getUser(): ?OCP\IUser;
        public function isLoggedIn(): bool;
    }
}

if (!interface_exists('OCP\IConfig')) {
    interface OCP\IConfig {
        public function getAppValue(string $app, string $key, string $default = ''): string;
        public function setAppValue(string $app, string $key, string $value): void;
    }
}

if (!interface_exists('OCP\IGroupManager')) {
    interface OCP\IGroupManager {
        public function get(string $gid): ?OCP\IGroup;
        public function groupExists(string $gid): bool;
    }
}

if (!interface_exists('OCP\IGroup')) {
    interface OCP\IGroup {
        public function getGID(): string;
        public function getDisplayName(): string;
    }
}

if (!interface_exists('OCP\IDBConnection')) {
    interface OCP\IDBConnection {
        public function getQueryBuilder(): OCP\DB\QueryBuilder\IQueryBuilder;
    }
}

if (!interface_exists('OCP\DB\QueryBuilder\IQueryBuilder')) {
    interface OCP\DB\QueryBuilder\IQueryBuilder {
        public function select(string ...$columns): self;
        public function from(string $table, string $alias = null): self;
        public function where(string $condition, ...$parameters): self;
        public function andWhere(string $condition, ...$parameters): self;
        public function orWhere(string $condition, ...$parameters): self;
        public function execute(): OCP\DB\IResult;
    }
}

if (!interface_exists('OCP\DB\IResult')) {
    interface OCP\DB\IResult {
        public function fetchRow(): array|false;
        public function fetchAll(): array;
    }
}

// Set up any additional test configuration here
// This could include database setup, mock services, etc.
