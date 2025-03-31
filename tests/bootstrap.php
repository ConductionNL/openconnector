<?php

declare(strict_types=1);

/**
 * bootstrap.php
 *
 * Bootstrap file for PHPUnit tests in the OpenConnector app.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests
 * @author    Conduction <info@conduction.nl>
 * @copyright 2023 Conduction
 * @license   https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @version   GIT: <git_id>
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */

// Include the composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set up path constants for Nextcloud
define('NEXTCLOUD_ROOT', __DIR__ . '/../../../../');
define('NEXTCLOUD_APP_PATH', __DIR__ . '/../');

// Load Nextcloud autoloader if available
$ncLoader = NEXTCLOUD_ROOT . 'lib/composer/autoload.php';
if (file_exists($ncLoader)) {
    require_once $ncLoader;
}

// Set up test environment
if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
} 