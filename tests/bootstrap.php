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
if (!class_exists('MockEntity')) {
    abstract class MockEntity {
        protected $id;
        protected $data = [];
        
        public function getId(): ?int {
            return $this->id;
        }
        
        public function setId(int $id): void {
            $this->id = $id;
        }
        
        public function getData(): array {
            return $this->data;
        }
        
        public function setData(array $data): void {
            $this->data = $data;
        }
        
        public function jsonSerialize(): array {
            return $this->data;
        }
    }
}

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
        
        public function findAll(
            ?int $limit = null,
            ?int $offset = null,
            ?array $filters = [],
            ?array $searchConditions = [],
            ?array $searchParams = [],
            ?array $ids = []
        ): array {
            return [];
        }
        
        public function insert($entity) {
            return $entity;
        }
        
        public function update($entity) {
            return $entity;
        }
        
        public function delete(\OCP\AppFramework\Db\Entity $entity): \OCP\AppFramework\Db\Entity {
            return $entity;
        }
        
        // Additional commonly used methods
        public function createFromArray(array $object) {
            return new \stdClass();
        }
        
        public function updateFromArray(int $id, array $object) {
            return new \stdClass();
        }
        
        public function getTotalCount(): int {
            return 0;
        }
        
        public function findByRef(string $reference): array {
            return [];
        }
        
        public function findByConfiguration(string $configurationId): array {
            return [];
        }
        
        public function getIdToSlugMap(): array {
            return [];
        }
        
        public function getSlugToIdMap(): array {
            return [];
        }
        
        // Specialized methods for specific mappers
        public function findByUuid(string $uuid) {
            return null;
        }
        
        public function findByPathRegex(string $path, string $method): array {
            return [];
        }
        
        public function getByTarget(?string $registerId = null, ?string $schemaId = null): array {
            return [];
        }
        
        public function findOrCreateByLocation(string $location, array $defaultData = []) {
            return new \stdClass();
        }
        
        public function findSyncContractByOriginId(string $synchronizationId, string $originId) {
            return null;
        }
        
        public function findTargetIdByOriginId(string $originId): ?string {
            return null;
        }
        
        public function findOnTarget(string $synchronization, string $targetId) {
            return null;
        }
        
        public function findByOriginAndTarget(string $originId, string $targetId) {
            return null;
        }
        
        public function findAllBySynchronizationAndSchema(string $synchronizationId, string $schemaId): array {
            return [];
        }
        
        public function cleanupExpired(): int {
            return 0;
        }
        
        public function getTotalCallCount(): int {
            return 0;
        }
    }
}

// Create aliases to the namespaced classes
if (!class_exists('OCP\AppFramework\Db\Entity')) {
    class_alias('MockEntity', 'OCP\AppFramework\Db\Entity');
}

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

// Additional OCP interfaces commonly used
if (!interface_exists('MockIEventListener')) {
    interface MockIEventListener {
        public function handle(MockEvent $event): void;
    }
}

if (!interface_exists('MockEvent')) {
    interface MockEvent {
        public function getSubject(): string;
        public function getArguments(): array;
    }
}

if (!interface_exists('MockIAppConfig')) {
    interface MockIAppConfig {
        public function getValue(string $app, string $key, string $default = ''): string;
        public function setValue(string $app, string $key, string $value): void;
    }
}

if (!interface_exists('MockIRequest')) {
    interface MockIRequest {
        public function getParam(string $key, string $default = ''): string;
        public function getHeader(string $name): string;
    }
}

if (!interface_exists('MockICache')) {
    interface MockICache {
        public function get(string $key): mixed;
        public function set(string $key, mixed $value, int $ttl = 0): bool;
        public function remove(string $key): bool;
    }
}

if (!interface_exists('MockICacheFactory')) {
    interface MockICacheFactory {
        public function create(string $cacheId): MockICache;
    }
}

if (!interface_exists('MockISchemaWrapper')) {
    interface MockISchemaWrapper {
        public function hasTable(string $name): bool;
        public function createTable(string $name): MockITable;
    }
}

if (!interface_exists('MockITable')) {
    interface MockITable {
        public function addColumn(string $name, string $type, array $options = []): MockIColumn;
    }
}

if (!interface_exists('MockIColumn')) {
    interface MockIColumn {
        public function setLength(int $length): self;
        public function setNotnull(bool $notnull): self;
    }
}

if (!interface_exists('MockIOutput')) {
    interface MockIOutput {
        public function info(string $message): void;
        public function warning(string $message): void;
    }
}

if (!interface_exists('MockSimpleMigrationStep')) {
    interface MockSimpleMigrationStep {
        public function changeSchema(MockIOutput $output, \Closure $schemaClosure, array $options): ?MockISchemaWrapper;
        public function sql(MockIOutput $output, \Closure $schemaClosure, array $options): void;
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

// Additional OCP interface aliases
if (!interface_exists('OCP\EventDispatcher\IEventListener')) {
    class_alias('MockIEventListener', 'OCP\EventDispatcher\IEventListener');
}

if (!interface_exists('OCP\EventDispatcher\Event')) {
    class_alias('MockEvent', 'OCP\EventDispatcher\Event');
}

if (!interface_exists('OCP\IAppConfig')) {
    class_alias('MockIAppConfig', 'OCP\IAppConfig');
}

if (!interface_exists('OCP\IRequest')) {
    class_alias('MockIRequest', 'OCP\IRequest');
}

if (!interface_exists('OCP\ICache')) {
    class_alias('MockICache', 'OCP\ICache');
}

if (!interface_exists('OCP\ICacheFactory')) {
    class_alias('MockICacheFactory', 'OCP\ICacheFactory');
}

if (!interface_exists('OCP\DB\ISchemaWrapper')) {
    class_alias('MockISchemaWrapper', 'OCP\DB\ISchemaWrapper');
}

if (!interface_exists('OCP\DB\ITable')) {
    class_alias('MockITable', 'OCP\DB\ITable');
}

if (!interface_exists('OCP\DB\IColumn')) {
    class_alias('MockIColumn', 'OCP\DB\IColumn');
}

if (!interface_exists('OCP\Migration\IOutput')) {
    class_alias('MockIOutput', 'OCP\Migration\IOutput');
}

if (!interface_exists('OCP\Migration\SimpleMigrationStep')) {
    class_alias('MockSimpleMigrationStep', 'OCP\Migration\SimpleMigrationStep');
}

// Mock exception classes with simple names first
if (!class_exists('MockDoesNotExistException')) {
    class MockDoesNotExistException extends \Exception {}
}

if (!class_exists('MockMultipleObjectsReturnedException')) {
    class MockMultipleObjectsReturnedException extends \Exception {}
}

if (!class_exists('MockGenericFileException')) {
    class MockGenericFileException extends \Exception {}
}

if (!class_exists('MockNotFoundException')) {
    class MockNotFoundException extends \Exception {}
}

// Create aliases to the namespaced exception classes
if (!class_exists('OCP\AppFramework\Db\DoesNotExistException')) {
    class_alias('MockDoesNotExistException', 'OCP\AppFramework\Db\DoesNotExistException');
}

if (!class_exists('OCP\AppFramework\Db\MultipleObjectsReturnedException')) {
    class_alias('MockMultipleObjectsReturnedException', 'OCP\AppFramework\Db\MultipleObjectsReturnedException');
}

if (!class_exists('OCP\Files\GenericFileException')) {
    class_alias('MockGenericFileException', 'OCP\Files\GenericFileException');
}

if (!class_exists('OCP\Files\NotFoundException')) {
    class_alias('MockNotFoundException', 'OCP\Files\NotFoundException');
}

// Set up any additional test configuration here
// This could include database setup, mock services, etc.
