<?php

declare(strict_types=1);

/**
 * Simplified Bootstrap file for OpenConnector unit tests
 * 
 * This file sets up the necessary environment for running unit tests
 * when running inside a Nextcloud container where all OCP classes are available.
 */

// Set up the testing environment
if (!defined('TESTING')) {
    define('TESTING', true);
}

// When running inside Nextcloud container, we don't need complex mocks
// The real Nextcloud environment provides all necessary classes
echo "Running tests in Nextcloud environment - using real OCP classes\n";

// Set up basic autoloading
require_once __DIR__ . '/../vendor/autoload.php';

// No need for complex mocking when running inside Nextcloud
// All OCP classes and interfaces are available from the Nextcloud installation
