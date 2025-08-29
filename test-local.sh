#!/bin/bash

# Local test script that bypasses platform requirements
# This allows running tests locally with PHP 8.1.31 while keeping PHP 8.3 for GitHub workflows

echo "Running OpenConnector tests locally (bypassing platform requirements)..."

# Set environment variable to ignore platform requirements
export COMPOSER_IGNORE_PLATFORM_REQS=1

# Run PHPUnit directly with platform requirements ignored
cd /var/www/html/apps-extra/openconnector
php -d disable_functions= vendor/bin/phpunit tests -c tests/phpunit.xml --colors=always --fail-on-warning --fail-on-risky

