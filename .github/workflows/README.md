# GitHub Workflows for OpenConnector

This directory contains GitHub Actions workflows for the OpenConnector repository.

## Available Workflows

### 1. `pr-unit-tests.yml` - Pull Request Unit Tests
- **Trigger**: Pull requests to `development`, `main`, or `master` branches
- **Purpose**: Runs unit tests for OpenConnector when a pull request is created
- **Features**:
  - PHP 8.2 environment
  - Composer dependency caching
  - Unit test execution
  - Test result reporting

### 2. `unit-tests.yml` - Comprehensive Unit Tests
- **Trigger**: Pull requests and pushes to main branches
- **Purpose**: Runs unit tests across multiple PHP versions
- **Features**:
  - Matrix strategy with PHP 8.1, 8.2, and 8.3
  - Coverage reporting
  - Codecov integration

### 3. `quality-checks.yml` - Quality Assurance
- **Trigger**: Pull requests and pushes to main branches
- **Purpose**: Comprehensive quality checks including unit tests, linting, and static analysis
- **Features**:
  - Unit tests
  - PHP linting
  - Code style checks (PHPCS)
  - Static analysis (Psalm)
  - Summary reporting

### 4. `pull-request-unit-tests.yaml` - Simple PR Tests
- **Trigger**: Pull requests to main branches
- **Purpose**: Simple unit test execution for pull requests
- **Features**:
  - Basic PHP 8.2 setup
  - Composer caching
  - Unit test execution

## Usage

These workflows will automatically run when:
- A pull request is created targeting `development`, `main`, or `master` branches
- Code is pushed to `development`, `main`, or `master` branches

## Test Configuration

The workflows use the following configuration:
- **PHP Version**: 8.2 (primary), with matrix testing for 8.1, 8.2, 8.3
- **Test Framework**: PHPUnit
- **Bootstrap**: `tests/bootstrap.php`
- **Test Directory**: `tests/Unit/`
- **Composer Script**: `composer test:unit`

## Dependencies

The workflows require:
- Composer dependencies installed
- PHP extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite, dom, filter, gd, json, pdo
- Test database setup (SQLite file)

## Troubleshooting

If tests fail:
1. Check the workflow logs for specific error messages
2. Ensure all dependencies are properly installed
3. Verify test database setup
4. Check for PHP version compatibility issues
