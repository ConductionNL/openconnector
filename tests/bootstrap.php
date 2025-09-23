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
if (!class_exists('MockMapper')) {
    abstract class MockMapper {
        protected $db;
        protected $tableName;
        
        public function __construct($db, $tableName) {
            $this->db = $db;
            $this->tableName = $tableName;
        }
        
        public function find(int|string $id) {
            return null;
        }
        
        public function findAll($limit = null, $offset = null) {
            return [];
        }
        
        public function insert($entity) {
            return $entity;
        }
        
        public function update($entity) {
            return $entity;
        }
        
        public function delete($entity) {
            return $entity;
        }
    }
}

// Create aliases to the namespaced classes
if (!class_exists('OCP\AppFramework\Db\Mapper')) {
    class_alias('MockMapper', 'OCP\AppFramework\Db\Mapper');
}

if (!class_exists('OCP\AppFramework\Db\QBMapper')) {
    class_alias('MockMapper', 'OCP\AppFramework\Db\QBMapper');
}

// Define mock interfaces with simple names first
if (!interface_exists('MockIUserManager')) {
    interface MockIUserManager {
        public function get(string $uid): ?MockIUser;
        public function userExists(string $uid): bool;
    }
}

if (!interface_exists('MockIUser')) {
    interface MockIUser {
        public function getUID(): string;
        public function getDisplayName(): string;
        public function getEMailAddress(): string;
    }
}

if (!interface_exists('MockIUserSession')) {
    interface MockIUserSession {
        public function getUser(): ?MockIUser;
        public function isLoggedIn(): bool;
    }
}

if (!interface_exists('MockIConfig')) {
    interface MockIConfig {
        public function getAppValue(string $app, string $key, string $default = ''): string;
        public function setAppValue(string $app, string $key, string $value): void;
    }
}

if (!interface_exists('MockIGroupManager')) {
    interface MockIGroupManager {
        public function get(string $gid): ?MockIGroup;
        public function groupExists(string $gid): bool;
    }
}

if (!interface_exists('MockIGroup')) {
    interface MockIGroup {
        public function getGID(): string;
        public function getDisplayName(): string;
    }
}

if (!interface_exists('MockIDBConnection')) {
    interface MockIDBConnection {
        public function getQueryBuilder(): MockIQueryBuilder;
    }
}

if (!interface_exists('MockIQueryBuilder')) {
    interface MockIQueryBuilder {
        public function select(string ...$columns): self;
        public function from(string $table, string $alias = null): self;
        public function where(string $condition, ...$parameters): self;
        public function andWhere(string $condition, ...$parameters): self;
        public function orWhere(string $condition, ...$parameters): self;
        public function execute(): MockIResult;
    }
}

if (!interface_exists('MockIResult')) {
    interface MockIResult {
        public function fetchRow(): array|false;
        public function fetchAll(): array;
    }
}

if (!interface_exists('MockIAccountManager')) {
    interface MockIAccountManager {
        public function getAccount(string $user): MockIAccount;
        public function updateAccount(MockIAccount $account): void;
    }
}

if (!interface_exists('MockIAccount')) {
    interface MockIAccount {
        public function getProperty(string $name): string;
        public function setProperty(string $name, string $value): void;
    }
}

// Now create aliases to the namespaced names
if (!interface_exists('OCP\IUserManager')) {
    class_alias('MockIUserManager', 'OCP\IUserManager');
}

if (!interface_exists('OCP\IUser')) {
    class_alias('MockIUser', 'OCP\IUser');
}

if (!interface_exists('OCP\IUserSession')) {
    class_alias('MockIUserSession', 'OCP\IUserSession');
}

if (!interface_exists('OCP\IConfig')) {
    class_alias('MockIConfig', 'OCP\IConfig');
}

if (!interface_exists('OCP\IGroupManager')) {
    class_alias('MockIGroupManager', 'OCP\IGroupManager');
}

if (!interface_exists('OCP\IGroup')) {
    class_alias('MockIGroup', 'OCP\IGroup');
}

if (!interface_exists('OCP\IDBConnection')) {
    class_alias('MockIDBConnection', 'OCP\IDBConnection');
}

if (!interface_exists('OCP\DB\QueryBuilder\IQueryBuilder')) {
    class_alias('MockIQueryBuilder', 'OCP\DB\QueryBuilder\IQueryBuilder');
}

if (!interface_exists('OCP\DB\IResult')) {
    class_alias('MockIResult', 'OCP\DB\IResult');
}

if (!interface_exists('OCP\Accounts\IAccountManager')) {
    class_alias('MockIAccountManager', 'OCP\Accounts\IAccountManager');
}

if (!interface_exists('OCP\Accounts\IAccount')) {
    class_alias('MockIAccount', 'OCP\Accounts\IAccount');
}

// Set up any additional test configuration here
// This could include database setup, mock services, etc.
