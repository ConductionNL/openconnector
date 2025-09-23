# OpenConnector GitHub Workflows Documentation

## 📋 **Overview**

This directory contains GitHub Actions workflows for the OpenConnector repository, providing CI/CD automation for testing and quality assurance.

## 🚀 **Available Workflows**

### **`pr-unit-tests.yml`** - Pull Request Unit Tests
- **Trigger**: Pull requests to `development`, `main`, or `master` branches
- **Purpose**: Runs unit tests when a pull request is created
- **PHP Version**: 8.2
- **Features**: Composer caching, PHPUnit verification, test reporting

### **`unit-tests.yml`** - Matrix Unit Tests
- **Trigger**: Pull requests and pushes to main branches
- **Purpose**: Runs unit tests across multiple PHP versions
- **PHP Versions**: 8.2, 8.3 (matrix strategy)
- **Features**: Coverage reporting, Codecov integration

### **`quality-checks.yml`** - Quality Assurance Pipeline
- **Trigger**: Pull requests and pushes to main branches
- **Purpose**: Comprehensive quality checks
- **Features**: Unit tests, PHP linting, code style (PHPCS), static analysis (Psalm)

## 🔧 **Configuration**

### **Test Infrastructure**
- **Bootstrap**: `tests/bootstrap.php`
- **PHPUnit Config**: `tests/phpunit.xml`
- **Test Database**: SQLite (`tests/data/test.db`)
- **Composer Script**: `composer test:unit`

### **PHP Setup**
- **Versions**: PHP 8.2, 8.3
- **Extensions**: `mbstring, xml, ctype, iconv, intl, pdo_sqlite, dom, filter, gd, json, pdo, zip, curl, mysql`
- **Tools**: Composer v2, PHPUnit ^9.6, php-cs-fixer ^3.0, psalm ^5.0

## 🚨 **Issues Resolved**

### **Version 1.0** - Initial Setup (September 23, 2025)
- Created basic workflow infrastructure
- Added `pr-unit-tests.yml`, `unit-tests.yml`, `quality-checks.yml`
- Created `tests/bootstrap.php` and `tests/phpunit.xml`

### **Version 1.1** - Critical Fixes (September 23, 2025)
- **PHPUnit Not Found**: Added `phpunit/phpunit: ^9.6` to dev dependencies
- **Lock File Sync**: Added `composer update --lock` step
- **PHP Extensions**: Enhanced extension list for compatibility
- **Success Messages**: Fixed misleading workflow summaries

### **Version 1.2** - Documentation Consolidation (September 23, 2025)
- Merged all individual .md files into single comprehensive documentation
- Removed 6 duplicate documentation files

### **Version 1.3-1.5** - Enhanced Error Handling (September 23, 2025)
- **PHPUnit Installation**: Added fallback installation if missing
- **Dependency Resolution**: Multi-layered approach with `composer update` fallback
- **Error Handling**: Improved conditional logic for better error detection

### **Version 1.6** - Critical Workflow Fixes (September 23, 2025)
- **PHP Version Mismatch**: Removed PHP 8.1 (dependencies require >= 8.2.0)
- **Missing PHPUnit Config**: Created comprehensive `tests/phpunit.xml`
- **Missing Tools**: Added `php-cs-fixer` and `psalm` to dev dependencies
- **Summary Logic**: Fixed conditional logic for accurate status reporting

## 🔍 **Troubleshooting**

### **Common Issues**

**Tests Fail to Run**
1. Check workflow logs for specific errors
2. Verify PHPUnit is installed: `./vendor/bin/phpunit --version`
3. Ensure `tests/phpunit.xml` exists and is valid
4. Check PHP version compatibility (requires >= 8.2.0)

**Linting Fails**
1. Verify PHP extensions are available
2. Check for syntax errors in code
3. Ensure `php-cs-fixer` is installed: `composer require --dev friendsofphp/php-cs-fixer`

**Lock File Issues**
1. Run `composer update --lock` locally
2. Commit the updated lock file
3. Verify `composer.json` and `composer.lock` are synchronized

**Missing Tools**
- **php-cs-fixer**: `composer require --dev friendsofphp/php-cs-fixer:^3.0`
- **psalm**: `composer require --dev vimeo/psalm:^5.0`
- **phpunit**: `composer require --dev phpunit/phpunit:^9.6`

## 📁 **File Structure**

```
.github/workflows/
├── pr-unit-tests.yml          # Pull request unit tests
├── unit-tests.yml             # Matrix testing (PHP 8.2, 8.3)
├── quality-checks.yml         # Comprehensive QA pipeline
└── COMPREHENSIVE_DOCUMENTATION.md

tests/
├── bootstrap.php              # Test bootstrap
├── phpunit.xml               # PHPUnit configuration
└── Unit/                     # Unit test files
```

## 🎯 **Success Criteria**

The workflows should:
- ✅ Run on compatible PHP versions (8.2, 8.3)
- ✅ Install all required dependencies automatically
- ✅ Execute unit tests successfully
- ✅ Generate accurate status reports
- ✅ Handle errors gracefully with proper fallbacks

## 🔄 **Maintenance**

- **Update action versions** regularly
- **Monitor workflow performance** and adjust caching
- **Keep composer.lock synchronized** with composer.json
- **Test workflows** with actual pull requests
- **Update documentation** when making changes

---

*Last Updated: September 23, 2025 | Version: 1.6 | Status: Complete*