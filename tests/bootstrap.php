<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set up basic test environment
if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
}

// Mock Nextcloud Entity class with all required methods
if (!class_exists('OCP_AppFramework_Db_Entity')) {
    class OCP_AppFramework_Db_Entity {
        protected array $fieldTypes = [];
        protected ?int $id = null;
        protected ?string $type = null;
        protected ?array $mapping = null;
        protected ?bool $passThrough = null;
        
        public function addType(string $fieldName, string $type): void {
            $this->fieldTypes[$fieldName] = $type;
        }
        
        public function getFieldTypes(): array {
            return $this->fieldTypes;
        }
        
        public function setId(?int $id): void {
            $this->id = $id;
        }
        
        public function getId(): ?int {
            return $this->id;
        }
        
        public function setType(?string $type): void {
            $this->type = $type;
        }
        
        public function getType(): ?string {
            return $this->type;
        }
        
        public function setMapping(?array $mapping): void {
            $this->mapping = $mapping;
        }
        
        public function getMapping(): ?array {
            return $this->mapping;
        }
        
        public function setPassThrough(?bool $passThrough): void {
            $this->passThrough = $passThrough;
        }
        
        public function getPassThrough(): ?bool {
            return $this->passThrough;
        }
        
        public function setUuid(?string $uuid): void {
            // Mock implementation
        }
        
        public function getUuid(): ?string {
            return null;
        }
        
        public function setSlug(?string $slug): void {
            // Mock implementation
        }
        
        public function getSlug(): ?string {
            return null;
        }
        
        public function setUnset($unset): void {
            // Mock implementation - accept any type
        }
        
        public function getUnset() {
            // Mock implementation - return null by default, but allow overrides
            return null;
        }
        
        public function hydrate(array $data) {
            // Mock implementation - return $this for fluent interface
            return $this;
        }
        
        // Add missing entity methods
        public function setName($name) { return $this; }
        public function getName() { return 'Test Name'; }
        public function setCast($cast) { return $this; }
        public function getCast() { return null; }
        public function setSourceId($sourceId) { return $this; }
        public function getSourceId() { return null; }
        public function setSynchronizationId($synchronizationId) { return $this; }
        public function getSynchronizationId() { return null; }
        public function setOriginId($originId) { return $this; }
        public function getOriginId() { return null; }
        public function setFieldName($fieldName) { return $this; }
        public function getFieldName() { return null; }
        public function setData($data) { return $this; }
        public function getData() { return null; }
        public function setStatus($status) { return $this; }
        public function getStatus() { return null; }
        public function getMessage() { return null; }
        public function setCreated($created) { return $this; }
        public function getCreated() { return null; }
        public function setUpdated($updated) { return $this; }
        public function getUpdated() { return null; }
        public function setTargetId($targetId) { return $this; }
        public function getTargetId() { return null; }
        public function setStatusCode($statusCode) { return $this; }
        public function getStatusCode() { return null; }
        public function setDeleted($deleted) { return $this; }
        public function getDeleted() { return null; }
        public function setRegister($register) { return $this; }
        public function getRegister() { return null; }
        public function setSourceConfig($sourceConfig) { return $this; }
        public function setLastRun($lastRun) { return $this; }
        public function setNextRun($nextRun) { return $this; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_AppFramework_Db_Entity', 'OCP\\AppFramework\\Db\\Entity');

// Mock Nextcloud QBMapper class
if (!class_exists('OCP_AppFramework_Db_QBMapper')) {
    class OCP_AppFramework_Db_QBMapper {
        protected $db;
        protected $tableName;
        
        public function __construct($db, $tableName) {
            $this->db = $db;
            $this->tableName = $tableName;
        }
        
        public function select($select = null) { return $this; }
        public function from($from, $alias = null) { return $this; }
        public function where($predicates) { return $this; }
        public function andWhere($predicates) { return $this; }
        public function orWhere($predicates) { return $this; }
        public function execute() { return null; }
        public function insert($entity) { return null; }
        public function update($entity) { return null; }
        public function delete($entity) { return null; }
        public function find($id) { return null; }
        public function findAll() { return []; }
        public function findEntity($query = null) { return null; }
        public function findEntities($query = null) { return []; }
        public function createNamedParameter($value, $type = null) { return $value; }
        public function expr() { return null; }
        public function setMaxResults($maxResults) { return $this; }
        public function setFirstResult($firstResult) { return $this; }
        public function setParameter($key, $value) { return $this; }
        public function orderBy($field, $direction) { return $this; }
        public function addOrderBy($field, $direction) { return $this; }
        public function get($key, $default = null) { return $default; }
        public function info($message) { return null; }
        public function error($message) { return null; }
        public function getBody() { return ''; }
        public function getValueInt($key, $default = 0) { return $default; }
        public function getMapper($className) { return null; }
        public function setRegister($register) { return $this; }
        public function checkPassword($password) { return false; }
        public function getEMailAddress() { return 'test@example.com'; }
        public function setName($name) { return $this; }
        public function setCast($cast) { return $this; }
        public function setSourceId($sourceId) { return $this; }
        public function setSynchronizationId($synchronizationId) { return $this; }
        public function setOriginId($originId) { return $this; }
        public function getSynchronizationId() { return null; }
        public function getLastLogin() { return null; }
        public function isEnabled() { return true; }
        public function getTemplateName() { return 'template'; }
        public function getUserFolder($user) { return null; }
        public function setLimit($limit) { return $this; }
        public function setOffset($offset) { return $this; }
        public function count() { return 0; }
    }
}
class_alias('OCP_AppFramework_Db_QBMapper', 'OCP\\AppFramework\\Db\\QBMapper');

// Mock Nextcloud Controller class
if (!class_exists('OCP_AppFramework_Controller')) {
    class OCP_AppFramework_Controller {
        public function __construct(...$args) {
            // Mock constructor - accept any number of arguments
        }
    }
}
class_alias('OCP_AppFramework_Controller', 'OCP\\AppFramework\\Controller');

// Mock Nextcloud interfaces
if (!class_exists('OCP_IRequest')) {
    class OCP_IRequest {
        public function getParams() { return []; }
        public function getParam($key, $default = null) { return $default; }
        public function get($key, $default = null) { return $default; }
        public function getValueInt($key, $default = 0) { return $default; }
        public function getBody() { return ''; }
        public function error($message) { return null; }
        public function info($message) { return null; }
        public function getUploadedFile($key) { return null; }
        public function warning($message) { return null; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_IRequest', 'OCP\\IRequest');

if (!class_exists('OCP_IUserManager')) {
    class OCP_IUserManager {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_IUserManager', 'OCP\\IUserManager');

if (!class_exists('OCP_IUserSession')) {
    class OCP_IUserSession {
        public function isLoggedIn() { return false; }
        public function getUser() { return null; }
    }
}
class_alias('OCP_IUserSession', 'OCP\\IUserSession');

if (!class_exists('OCP_ICacheFactory')) {
    class OCP_ICacheFactory {
        public function createDistributed($key) { return null; }
    }
}
class_alias('OCP_ICacheFactory', 'OCP\\ICacheFactory');

if (!class_exists('OCP_IUser')) {
    class OCP_IUser {
        public function getUID() { return 'testuser'; }
        public function checkPassword($password) { return false; }
        public function getDisplayName() { return 'Test User'; }
        public function getEMailAddress() { return 'test@example.com'; }
        public function getLastLogin() { return null; }
        public function isEnabled() { return true; }
        public function getHome() { return '/home/testuser'; }
        public function getQuota() { return 1024; }
        public function getBackendClassName() { return 'Database'; }
        public function getUserGroups() { return []; }
        public function getUserValue($app, $key, $default = null) { return $default; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getValueInt($key, $default = 0) { return $default; }
    }
}
class_alias('OCP_IUser', 'OCP\\IUser');

if (!class_exists('OCP_IConfig')) {
    class OCP_IConfig {
        public function getSystemValue($key, $default = null) { return $default; }
        public function getAppValue($app, $key, $default = null) { return $default; }
        public function getUserValue($user, $app, $key, $default = null) { return $default; }
        public function get($key, $default = null) { return $default; }
        public function info($message) { return null; }
        public function warning($message) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getValueInt($key, $default = 0) { return $default; }
    }
}
class_alias('OCP_IConfig', 'OCP\\IConfig');

if (!class_exists('OCP_IURLGenerator')) {
    class OCP_IURLGenerator {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_IURLGenerator', 'OCP\\IURLGenerator');

if (!class_exists('OCP_ICache')) {
    class OCP_ICache {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_ICache', 'OCP\\ICache');

if (!class_exists('OCP_App_IAppManager')) {
    class OCP_App_IAppManager {
        public function getInstalledApps() { return []; }
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_App_IAppManager', 'OCP\\App\\IAppManager');

if (!class_exists('OCP_Files_IRootFolder')) {
    class OCP_Files_IRootFolder {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getFirstNodeById($id) { return null; }
    }
}
class_alias('OCP_Files_IRootFolder', 'OCP\\Files\\IRootFolder');

if (!class_exists('OCP_IDBConnection')) {
    class OCP_IDBConnection {
        public function getQueryBuilder() { return null; }
        public function executeQuery($sql, $params = []) { 
            return new class {
                public function fetch() { return null; }
                public function fetchAll() { return []; }
                public function rowCount() { return 0; }
                public function closeCursor() { return null; }
            };
        }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_IDBConnection', 'OCP\\IDBConnection');

if (!class_exists('OCP_Http_Client_IClientService')) {
    class OCP_Http_Client_IClientService {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_Http_Client_IClientService', 'OCP\\Http\\Client\\IClientService');

if (!class_exists('OCP_BackgroundJob_IJobList')) {
    class OCP_BackgroundJob_IJobList {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function add($job) { return null; }
    }
}
class_alias('OCP_BackgroundJob_IJobList', 'OCP\\BackgroundJob\\IJobList');

if (!class_exists('OCP_Accounts_IAccountManager')) {
    class OCP_Accounts_IAccountManager {
        const PROPERTY_PHONE = 'phone';
        const PROPERTY_ADDRESS = 'address';
        const PROPERTY_WEBSITE = 'website';
        const PROPERTY_EMAIL = 'email';
        const PROPERTY_AVATAR = 'avatar';
        const PROPERTY_DISPLAYNAME = 'displayname';
        const PROPERTY_TWITTER = 'twitter';
        const PROPERTY_FEDIVERSE = 'fediverse';
        const PROPERTY_ORGANISATION = 'organisation';
        const PROPERTY_ROLE = 'role';
        const PROPERTY_HEADLINE = 'headline';
        const PROPERTY_BIOGRAPHY = 'biography';
        const PROPERTY_PROFILE_ENABLED = 'profile_enabled';
        const PROPERTY_PHONE_VERIFIED = 'phone_verified';
        const PROPERTY_EMAIL_VERIFIED = 'email_verified';
        const PROPERTY_TWITTER_VERIFIED = 'twitter_verified';
        const PROPERTY_FEDIVERSE_VERIFIED = 'fediverse_verified';
        const PROPERTY_ORGANISATION_VERIFIED = 'organisation_verified';
        const PROPERTY_ROLE_VERIFIED = 'role_verified';
        const PROPERTY_HEADLINE_VERIFIED = 'headline_verified';
        const PROPERTY_BIOGRAPHY_VERIFIED = 'biography_verified';
        const PROPERTY_PROFILE_ENABLED_VERIFIED = 'profile_enabled_verified';
        
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getAccount($user) { return null; }
    }
}
class_alias('OCP_Accounts_IAccountManager', 'OCP\\Accounts\\IAccountManager');

if (!class_exists('OCP_Accounts_IAccount')) {
    class OCP_Accounts_IAccount {
        public function get($key, $default = null) { return $default; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getProperty($property) { return null; }
    }
}
class_alias('OCP_Accounts_IAccount', 'OCP\\Accounts\\IAccount');

if (!class_exists('OCP_IGroupManager')) {
    class OCP_IGroupManager {
        public function get($key, $default = null) { return $default; }
        public function getUserGroups($user) { return []; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_IGroupManager', 'OCP\\IGroupManager');

if (!class_exists('OCP_IAppConfig')) {
    class OCP_IAppConfig {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_IAppConfig', 'OCP\\IAppConfig');

if (!class_exists('OCP_AppFramework_Http_JSONResponse')) {
    class OCP_AppFramework_Http_JSONResponse {
        public function __construct($data = null) {
            // Mock constructor
        }
        public function getData() { return []; }
    }
}
class_alias('OCP_AppFramework_Http_JSONResponse', 'OCP\\AppFramework\\Http\\JSONResponse');

if (!class_exists('OCP_AppFramework_Http_DataResponse')) {
    class OCP_AppFramework_Http_DataResponse {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_AppFramework_Http_DataResponse', 'OCP\\AppFramework\\Http\\DataResponse');

if (!class_exists('OCP_AppFramework_Http_Response')) {
    class OCP_AppFramework_Http_Response {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_AppFramework_Http_Response', 'OCP\\AppFramework\\Http\\Response');

if (!class_exists('OCP_AppFramework_Db_DoesNotExistException')) {
    class OCP_AppFramework_Db_DoesNotExistException extends Exception {}
}
class_alias('OCP_AppFramework_Db_DoesNotExistException', 'OCP\\AppFramework\\Db\\DoesNotExistException');

if (!class_exists('OCP_Http_Client_IResponse')) {
    class OCP_Http_Client_IResponse {
        public function getStatusCode() { return 200; }
        public function getBody() { return ''; }
        public function error($message) { return null; }
        public function info($message) { return null; }
        public function warning($message) { return null; }
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_Http_Client_IResponse', 'OCP\\Http\\Client\\IResponse');

if (!class_exists('OCP_DB_QueryBuilder_IQueryBuilder')) {
    class OCP_DB_QueryBuilder_IQueryBuilder {
        public function select($select = null) { return $this; }
        public function from($from, $alias = null) { return $this; }
        public function where($predicates) { return $this; }
        public function andWhere($predicates) { return $this; }
        public function orWhere($predicates) { return $this; }
        public function execute() { return null; }
        public function executeQuery() { 
            return new class {
                public function fetch() { return null; }
                public function fetchAll() { return []; }
                public function rowCount() { return 0; }
                public function closeCursor() { return null; }
            };
        }
        public function createNamedParameter($value, $type = null) { return $value; }
        public function expr() { return null; }
        public function setMaxResults($maxResults) { return $this; }
        public function setFirstResult($firstResult) { return $this; }
        public function setParameter($key, $value) { return $this; }
        public function orderBy($field, $direction) { return $this; }
        public function addOrderBy($field, $direction) { return $this; }
        public function setLimit($limit) { return $this; }
        public function setOffset($offset) { return $this; }
        public function count() { return 0; }
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_DB_QueryBuilder_IQueryBuilder', 'OCP\\DB\\QueryBuilder\\IQueryBuilder');

if (!class_exists('OCP_DB_QueryBuilder_IExpressionBuilder')) {
    class OCP_DB_QueryBuilder_IExpressionBuilder {
        public function eq($x, $y) { return 'eq'; }
        public function neq($x, $y) { return 'neq'; }
        public function lt($x, $y) { return 'lt'; }
        public function lte($x, $y) { return 'lte'; }
        public function gt($x, $y) { return 'gt'; }
        public function gte($x, $y) { return 'gte'; }
        public function isNull($x) { return 'isNull'; }
        public function isNotNull($x) { return 'isNotNull'; }
        public function like($x, $y) { return 'like'; }
        public function notLike($x, $y) { return 'notLike'; }
        public function in($x, $y) { return 'in'; }
        public function notIn($x, $y) { return 'notIn'; }
        public function andX($x, $y) { return 'andX'; }
        public function orX($x, $y) { return 'orX'; }
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_DB_QueryBuilder_IExpressionBuilder', 'OCP\\DB\\QueryBuilder\\IExpressionBuilder');

if (!class_exists('OCP_DB_IResult')) {
    class OCP_DB_IResult {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function fetch() { return null; }
        public function fetchAll() { return []; }
        public function rowCount() { return 0; }
        public function closeCursor() { return null; }
    }
}
class_alias('OCP_DB_IResult', 'OCP\\DB\\IResult');

if (!class_exists('OCP_Authentication_Token_IProvider')) {
    class OCP_Authentication_Token_IProvider {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_Authentication_Token_IProvider', 'OCP\\Authentication\\Token\\IProvider');

if (!class_exists('OCP_Http_Client_IClient')) {
    class OCP_Http_Client_IClient {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_Http_Client_IClient', 'OCP\\Http\\Client\\IClient');

// Mock OC class
if (!class_exists('OC')) {
    class OC {
        public static $server;
        public static $systemManager = null;
        public static $userManager = null;
        public static $groupManager = null;
        public static $config = null;
        public static $appManager = null;
        
        public static function getServer() { 
            if (self::$server === null) {
                self::$server = new class {
                    public function getDatabaseConnection() { 
                        return new class {
                            public function getQueryBuilder() { 
                                return new class {
                                    public function select($select = null) { return $this; }
                                    public function from($from, $alias = null) { return $this; }
                                    public function join($fromAlias, $join, $alias, $condition = null) { return $this; }
                                    public function where($predicates) { return $this; }
                                    public function expr() { 
                                        return new class {
                                            public function eq($x, $y) { return 'eq'; }
                                        };
                                    }
                                    public function createNamedParameter($value, $type = null) { return $value; }
                                    public function setMaxResults($maxResults) { return $this; }
                                    public function execute() { 
                                        return new class {
                                            public function fetch() { return null; }
                                            public function closeCursor() { return null; }
                                        };
                                    }
                                };
                            }
                        };
                    }
                    public function getL10NFactory() { 
                        return new class {
                            public function findLanguage() { return 'en'; }
                        };
                    }
                };
            }
            return self::$server; 
        }
        public static function getSystemManager() { return self::$systemManager; }
        public static function getUserManager() { return self::$userManager; }
        public static function getGroupManager() { return self::$groupManager; }
        public static function getConfig() { return self::$config; }
        public static function getAppManager() { return self::$appManager; }
        public static function getAppPath($app) { return '/var/www/html/apps/' . $app; }
        public static function getAppWebPath($app) { return '/apps/' . $app; }
        public static function getAppVersion($app) { return '1.0.0'; }
        public static function isAppEnabled($app) { return true; }
        public static function isAppInstalled($app) { return true; }
    }
    
    // Initialize the server property
    OC::$server = new class {
        public function getDatabaseConnection() { 
            return new class {
                public function getQueryBuilder() { 
                    return new class {
                        public function select($select = null) { return $this; }
                        public function from($from, $alias = null) { return $this; }
                        public function join($fromAlias, $join, $alias, $condition = null) { return $this; }
                        public function where($predicates) { return $this; }
                        public function expr() { 
                            return new class {
                                public function eq($x, $y) { return 'eq'; }
                            };
                        }
                        public function createNamedParameter($value, $type = null) { return $value; }
                        public function setMaxResults($maxResults) { return $this; }
                        public function execute() { 
                            return new class {
                                public function fetch() { return null; }
                                public function closeCursor() { return null; }
                            };
                        }
                    };
                }
            };
        }
        public function getL10NFactory() { 
            return new class {
                public function findLanguage() { return 'en'; }
            };
        }
    };
}

// Mock OpenRegister classes
if (!class_exists('OCA_OpenRegister_Db_RegisterMapper')) {
    class OCA_OpenRegister_Db_RegisterMapper {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getMapper() { return null; }
        public function setRegister($register) { return null; }
    }
}
class_alias('OCA_OpenRegister_Db_RegisterMapper', 'OCA\\OpenRegister\\Db\\RegisterMapper');

if (!class_exists('OCA_OpenRegister_Db_SchemaMapper')) {
    class OCA_OpenRegister_Db_SchemaMapper {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCA_OpenRegister_Db_SchemaMapper', 'OCA\\OpenRegister\\Db\\SchemaMapper');

if (!class_exists('OCA_OpenRegister_Service_ObjectService')) {
    class OCA_OpenRegister_Service_ObjectService {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCA_OpenRegister_Service_ObjectService', 'OCA\\OpenRegister\\Service\\ObjectService');

if (!class_exists('OCA_OpenRegister_Db_ObjectEntityMapper')) {
    class OCA_OpenRegister_Db_ObjectEntityMapper {
        public function get($key, $default = null) { return $default; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCA_OpenRegister_Db_ObjectEntityMapper', 'OCA\\OpenRegister\\Db\\ObjectEntityMapper');

if (!class_exists('OCA_OpenRegister_Service_OrganisationService')) {
    class OCA_OpenRegister_Service_OrganisationService {
        public function get($key, $default = null) { return $default; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCA_OpenRegister_Service_OrganisationService', 'OCA\\OpenRegister\\Service\\OrganisationService');

// Mock PSR Logger interface
if (!class_exists('Psr_Log_LoggerInterface')) {
    class Psr_Log_LoggerInterface {
        public function get($key, $default = null) { return $default; }
        public function warning($message) { return null; }
        public function info($message) { return null; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('Psr_Log_LoggerInterface', 'Psr\\Log\\LoggerInterface');

// Mock additional interfaces
if (!class_exists('OCP_IContainer')) {
    class OCP_IContainer {
        public function get($id) { return null; }
        public function has($id) { return false; }
        public function warning($message) { return null; }
        public function info($message) { return null; }
        public function getUserFolder($user) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getValueInt($key, $default = 0) { return $default; }
    }
}
class_alias('OCP_IContainer', 'OCP\\IContainer');

if (!class_exists('OCP_Files_IFolder')) {
    class OCP_Files_IFolder {
        public function getUserFolder($user) { return null; }
        public function get($path) { return null; }
        public function newFile($name) { return null; }
        public function newFolder($name) { return null; }
        public function getValueInt($key, $default = 0) { return $default; }
        public function warning($message) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_Files_IFolder', 'OCP\\Files\\IFolder');

if (!class_exists('Psr_Container_ContainerInterface')) {
    class Psr_Container_ContainerInterface {
        public function get($id) { return null; }
        public function has($id) { return false; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('Psr_Container_ContainerInterface', 'Psr\\Container\\ContainerInterface');

if (!class_exists('OCP_AppFramework_Http_TemplateResponse')) {
    class OCP_AppFramework_Http_TemplateResponse {
        public function getTemplateName() { return 'template'; }
    }
}
class_alias('OCP_AppFramework_Http_TemplateResponse', 'OCP\\AppFramework\\Http\\TemplateResponse');

if (!class_exists('OCP_Files_File')) {
    class OCP_Files_File {
        public function get($key, $default = null) { return $default; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
        public function getExtension() { return 'txt'; }
    }
}
class_alias('OCP_Files_File', 'OCP\\Files\\File');

if (!class_exists('OCP_Files_NotFoundException')) {
    class OCP_Files_NotFoundException extends Exception {
        public function __construct($message = '', $code = 0, Exception $previous = null) {
            parent::__construct($message, $code, $previous);
        }
    }
}
class_alias('OCP_Files_NotFoundException', 'OCP\\Files\\NotFoundException');

if (!class_exists('OCP_Files_Folder')) {
    class OCP_Files_Folder {
        public function getUserFolder($user) { return null; }
        public function get($path) { return null; }
        public function newFile($name) { return null; }
        public function newFolder($name) { return null; }
        public function getValueInt($key, $default = 0) { return $default; }
        public function warning($message) { return null; }
        public function set($key, $value, $ttl = null) { return null; }
        public function remove($key) { return null; }
    }
}
class_alias('OCP_Files_Folder', 'OCP\\Files\\Folder');
