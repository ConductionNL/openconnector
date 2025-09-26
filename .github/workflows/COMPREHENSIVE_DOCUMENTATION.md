# OpenConnector GitHub Workflows Documentation

## 📋 **Overview**

This directory contains GitHub Actions workflows for the OpenConnector repository, providing CI/CD automation for testing and quality assurance.

## 🚀 **Available Workflows**

### **`ci.yml`** - Main CI Pipeline ⭐
> **⚠️ Current Status**: This workflow has been updated to use a database-based testing strategy. The original workflow that caused MockMapper compatibility issues has been moved to `ci-disabled.yml`. See [`DATABASE_TESTING_STRATEGY.md`](./DATABASE_TESTING_STRATEGY.md) for details on the new approach.
- **Trigger**: Pull requests and pushes to `development`, `main`, `master` branches
- **Purpose**: Comprehensive testing and quality assurance
- **Jobs**:
  - **`tests`**: Matrix testing across PHP 8.2 and 8.3
    - Unit tests with PHPUnit
    - PHP linting
    - Coverage reporting (PHP 8.2 only)
  - **`quality`**: Code quality and standards
    - PHP linting
    - Code style checks (php-cs-fixer)
    - Static analysis (Psalm)
    - Unit tests with PHPUnit
    - Quality status reporting

### **Existing Workflows** (Pre-existing)
- **`beta-release.yaml`**: Beta release automation
- **`documentation.yml`**: Documentation generation
- **`phpcs.yml`**: PHP CodeSniffer checks
- **`pull-request-from-branch-check.yaml`**: Branch validation
- **`pull-request-lint-check.yaml`**: Lint checking
- **`push-development-to-beta.yaml`**: Development to beta promotion
- **`release-workflow.yaml`**: Production release
- **`release-workflow(nightly).yaml`**: Nightly release

## 🏷️ **Workflow Naming & Visibility**

### **GitHub PR Checks Display**
When you open a PR on GitHub, you'll see these workflow names in the checks section:

- **`CI - Tests & Quality Checks`** (from `ci.yml`)
  - `PHP 8.2 Tests` - Unit tests on PHP 8.2
  - `PHP 8.3 Tests` - Unit tests on PHP 8.3  
  - `Code Quality & Standards` - Quality checks (linting, code style, static analysis, unit tests)

### **Clear Separation of Concerns**
- **`ci.yml`**: Main development workflow (testing + quality)
- **`release-workflow.yaml`**: Production releases
- **`beta-release.yaml`**: Beta releases
- **`documentation.yml`**: Documentation updates
- **`phpcs.yml`**: Standalone code style checks

## 🔧 **Configuration**

### **Test Infrastructure**
- **Bootstrap**: `tests/bootstrap.php` - Mock OCP interfaces for testing
- **PHPUnit Config**: `tests/phpunit.xml` - Clean PHPUnit 9.6 configuration
- **Test Database**: SQLite (`tests/data/test.db`)
- **Composer Script**: `composer test:unit`

### **Bootstrap Mocking Strategy**
The `tests/bootstrap.php` file provides comprehensive mocking for Nextcloud OCP classes and interfaces:
- **Mock Classes**: Simple-named classes (MockMapper) with compatible method signatures
- **Mock Interfaces**: Simple-named interfaces (MockIUserManager, MockIUser, etc.)
- **Class Aliases**: Maps mock classes/interfaces to namespaced OCP classes
- **Database Layer**: Entity (base class), Mapper, QBMapper, IDBConnection, IQueryBuilder, IResult
- **User Management**: IUserManager, IUser, IUserSession, IGroupManager, IGroup
- **Account Management**: IAccountManager, IAccount for user account data
- **Configuration**: IConfig for app settings and configuration
- **Method Compatibility**: Mock methods match actual OCP method signatures (e.g., `find(int|string $id)`, `findAll()` with all optional parameters)
- **Proactive Mocking**: Includes commonly used methods (`createFromArray`, `updateFromArray`, `getTotalCount`, `findByRef`, etc.)
- **Specialized Methods**: Includes mapper-specific methods (`findByUuid`, `findByPathRegex`, `getByTarget`, `cleanupExpired`, etc.)
- **Exception Classes**: Mocks all required OCP exception classes (`DoesNotExistException`, `MultipleObjectsReturnedException`, etc.)
- **Extended Interfaces**: Mocks additional OCP interfaces (`IEventListener`, `IAppConfig`, `IRequest`, `ICache`, `ISchemaWrapper`, etc.)

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
- **Workflow Consolidation**: Merged `pr-unit-tests.yml`, `unit-tests.yml`, and `quality-checks.yml` into single `ci.yml` workflow
- **File Removals**: 
  - `pr-unit-tests.yml` - Merged into `ci.yml`
  - `unit-tests.yml` - Merged into `ci.yml` 
  - `quality-checks.yml` - Merged into `ci.yml`

### **Version 1.3-1.5** - Enhanced Error Handling (September 23, 2025)
- **PHPUnit Installation**: Added fallback installation if missing
- **Dependency Resolution**: Multi-layered approach with `composer update` fallback
- **Error Handling**: Improved conditional logic for better error detection

### **Version 1.6** - Critical Workflow Fixes (September 23, 2025)
- **PHP Version Mismatch**: Removed PHP 8.1 (dependencies require >= 8.2.0)
- **Missing PHPUnit Config**: Created comprehensive `tests/phpunit.xml`
- **Missing Tools**: Added `php-cs-fixer` and `psalm` to dev dependencies
- **Summary Logic**: Fixed conditional logic for accurate status reporting

### **Version 1.7** - Critical Entity Base Class Fix (September 25, 2025)
- **Missing Entity Base Class**: Added `MockEntity` base class for `OCP\AppFramework\Db\Entity`
- **Fatal Error Resolution**: Fixed "Class OCP\AppFramework\Db\Entity not found" fatal error
- **Inheritance Chain**: Ensured all entity classes can properly extend from base Entity class
- **Documentation Update**: Added Entity base class to bootstrap mocking strategy

### **Version 1.8** - CI/CD Pipeline Fixes (September 25, 2025)
- **Method Signature Compatibility**: Fixed all mapper `find()` methods to accept `int|string $id` parameters
- **Type Safety Improvements**: Added proper type casting for database parameter handling
- **Psalm Static Analysis**: Resolved mixed array access and operand errors in Action classes
- **Test Bootstrap**: Fixed MockMapper method signature compatibility issues
- **Code Quality**: Eliminated all PHP CodeSniffer violations and linting errors

### **Version 1.9** - Additional CI/CD Pipeline Fixes (September 25, 2025)
- **findAll Method Signatures**: Fixed all mapper `findAll()` methods to include missing `$ids` parameter
- **Dependency Injection**: Added proper type assertions for Application.php service registration
- **Mixed Operand Resolution**: Fixed remaining mixed operand errors in SynchronizationAction
- **Parameter Usage**: Resolved unused parameter warnings in EventAction
- **OpenRegister Integration**: Implemented conditional event listener registration based on OpenRegister availability
- **Comprehensive Testing**: Ensured all static analysis tools pass without errors

### **Version 1.10** - Code Quality and Style Improvements (September 25, 2025)
- **Comparison Style**: Replaced `!empty()` and `if (!` with strict `===` and `!==` comparisons
- **Type Safety**: Added proper type hints for EndpointsController constructor parameters
- **Error Handling**: Improved error handling in MappingRuntime with proper exception throwing
- **Code Consistency**: Standardized comparison operators throughout the codebase
- **Documentation**: Updated comprehensive documentation with latest improvements

### **Version 1.11** - MockMapper Compatibility and Workflow Strategy Change (September 25, 2025)
- **MockMapper Issues**: Identified signature compatibility issues between MockMapper and actual mapper classes
- **Workflow Disabled**: Moved original `ci.yml` to `ci-disabled.yml` due to MockMapper signature conflicts
- **New Strategy**: Implementing database-based testing approach to avoid MockMapper compatibility issues
- **New Files Added**:
  - `tests/phpunit-ci-simple.xml` - Minimal CI test configuration
  - `tests/phpunit-ci.xml` - Comprehensive CI test configuration  
  - `tests/bootstrap-ci.php` - Experimental CI bootstrap with real database connections
  - `.github/workflows/ci-disabled.yml` - Disabled original workflow
- **Documentation**: New approach documented in `DATABASE_TESTING_STRATEGY.md`

### **Version 1.12** - Reversion to Original Approach (September 26, 2025)
- **Strategy Reversion**: Reverted from database-based testing back to original MockMapper approach
- **File Cleanup**: Removed all database-based testing files and configurations
- **Original Workflow Restored**: Restored `ci.yml` to use original `composer test:unit` approach
- **Focus**: Fix MockMapper signature compatibility issues in original `bootstrap.php`
- **Removed Files**:
  - `tests/bootstrap-ci.php` - Database bootstrap approach
  - `tests/phpunit-ci.xml` - Database test configuration
  - `tests/phpunit-ci-simple.xml` - Simple test configuration
  - `.github/workflows/ci-disabled.yml` - Disabled workflow
  - `.github/workflows/DATABASE_TESTING_STRATEGY.md` - Database strategy documentation
- **Next Steps**: Fix MockMapper signature compatibility issues in original bootstrap.php
### **Development Pattern**
The git history shows an iterative approach to resolving MockMapper compatibility:
1. **Initial Attempts**: Removing unused parameters
2. **Standardization**: Trying to make all signatures consistent
3. **Flexibility**: Implementing flexible MockMapper with variadic parameters
4. **Strategy Change**: Moving to database-based testing approach

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

### **Current Workflow Files**
```
.github/workflows/
├── ci.yml                     # Main CI pipeline (tests + quality) - ACTIVE
├── ci-disabled.yml            # Disabled original workflow - INACTIVE
├── beta-release.yaml          # Beta release workflow
├── documentation.yml           # Documentation workflow
├── phpcs.yml                  # PHP CodeSniffer workflow
├── pull-request-from-branch-check.yaml  # Branch validation
├── pull-request-lint-check.yaml        # Lint checking
├── push-development-to-beta.yaml       # Development to beta
├── release-workflow.yaml      # Production release
├── release-workflow(nightly).yaml      # Nightly release
├── COMPREHENSIVE_DOCUMENTATION.md      # This file - Main workflow documentation
└── DATABASE_TESTING_STRATEGY.md        # Database testing strategy docs - See this file for new testing approach
```

### **Test Configuration Files**
```
tests/
├── bootstrap.php              # Test bootstrap (original)
├── bootstrap-ci.php          # CI-specific bootstrap (experimental)
├── phpunit.xml               # PHPUnit configuration (original)
├── phpunit-ci.xml            # CI-specific PHPUnit config
├── phpunit-ci-simple.xml     # Minimal CI PHPUnit config
└── Unit/                     # Unit test files
```

### **Removed Files (Historical)**
- `pr-unit-tests.yml` - Merged into `ci.yml` in Version 1.2
- `unit-tests.yml` - Merged into `ci.yml` in Version 1.2  
- `quality-checks.yml` - Merged into `ci.yml` in Version 1.2
- 6 duplicate documentation files - Consolidated in Version 1.2

## 🎯 **Success Criteria**

The workflows should:
- ✅ Run on compatible PHP versions (8.2, 8.3)
- ✅ Install all required dependencies automatically
- ✅ Execute unit tests successfully
- ✅ Generate accurate status reports
- ✅ Handle errors gracefully with proper fallbacks

### **Current Status (September 25, 2025)**
- ✅ **MockMapper Issues**: Identified and documented
- ✅ **Workflow Strategy**: Changed to database-based testing
- ✅ **Documentation**: Comprehensive documentation created
- 🔄 **Testing**: New strategy being implemented and tested
- 🔄 **Migration**: Existing tests being updated for new approach

## 🔄 **Maintenance**

- **Update action versions** regularly
- **Monitor workflow performance** and adjust caching
- **Keep composer.lock synchronized** with composer.json
- **Test workflows** with actual pull requests
- **Update documentation** when making changes

---

*Last Updated: September 26, 2025 | Version: 1.12 | Status: Reverted to Original Approach*