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

// Mock Nextcloud classes if they don't exist
if (!class_exists('OCP_IRequest')) {
    class OCP_IRequest {}
}

if (!class_exists('OCP_IUserManager')) {
    class OCP_IUserManager {}
}

if (!class_exists('OCP_IUserSession')) {
    class OCP_IUserSession {}
}

if (!class_exists('OCP_ICacheFactory')) {
    class OCP_ICacheFactory {}
}

if (!class_exists('OCP_IUser')) {
    class OCP_IUser {}
}

if (!class_exists('Psr_Log_LoggerInterface')) {
    class Psr_Log_LoggerInterface {}
}

// Create aliases for the original namespaces
class_alias('OCP_IRequest', 'OCP\\IRequest');
class_alias('OCP_IUserManager', 'OCP\\IUserManager');
class_alias('OCP_IUserSession', 'OCP\\IUserSession');
class_alias('OCP_ICacheFactory', 'OCP\\ICacheFactory');
class_alias('OCP_IUser', 'OCP\\IUser');
class_alias('Psr_Log_LoggerInterface', 'Psr\\Log\\LoggerInterface');
