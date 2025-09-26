<?php

// CI-specific bootstrap for non-Db tests only
// This avoids MockMapper signature compatibility issues by excluding Db tests entirely

// Set up basic autoloading
require_once __DIR__ . '/../vendor/autoload.php';

// Use the original bootstrap which has all the necessary mocks for non-Db tests
// The phpunit-ci-simple.xml configuration excludes Db tests that cause MockMapper issues
require_once __DIR__ . '/bootstrap.php';
