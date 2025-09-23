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

// Set up any additional test configuration here
// This could include database setup, mock services, etc.
