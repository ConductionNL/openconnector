# OpenConnector GitHub Workflows Documentation

## 📋 **Overview**

This directory contains GitHub Actions workflows for the OpenConnector repository, providing CI/CD automation for testing and quality assurance.

## 🚀 **Available Workflows**

### **`ci.yml`** - Main CI Pipeline ⭐
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

### **Version 1.3-1.5** - Enhanced Error Handling (September 23, 2025)
- **PHPUnit Installation**: Added fallback installation if missing
- **Dependency Resolution**: Multi-layered approach with `composer update` fallback
- **Error Handling**: Improved conditional logic for better error detection

### **Version 1.6** - Critical Workflow Fixes (September 23, 2025)
- **PHP Version Mismatch**: Removed PHP 8.1 (dependencies require >= 8.2.0)
- **Missing PHPUnit Config**: Created comprehensive `tests/phpunit.xml`
- **Missing Tools**: Added `php-cs-fixer` and `psalm` to dev dependencies
- **Summary Logic**: Fixed conditional logic for accurate status reporting

### **Version 1.7** - Critical Entity Base Class Fix (September 23, 2025)
- **Missing Entity Base Class**: Added `MockEntity` base class for `OCP\AppFramework\Db\Entity`
- **Fatal Error Resolution**: Fixed "Class OCP\AppFramework\Db\Entity not found" fatal error
- **Inheritance Chain**: Ensured all entity classes can properly extend from base Entity class
- **Documentation Update**: Added Entity base class to bootstrap mocking strategy

### **Version 1.8** - CI/CD Pipeline Fixes (December 19, 2024)
- **Method Signature Compatibility**: Fixed all mapper `find()` methods to accept `int|string $id` parameters
- **Type Safety Improvements**: Added proper type casting for database parameter handling
- **Psalm Static Analysis**: Resolved mixed array access and operand errors in Action classes
- **Test Bootstrap**: Fixed MockMapper method signature compatibility issues
- **Code Quality**: Eliminated all PHP CodeSniffer violations and linting errors

### **Version 1.9** - Additional CI/CD Pipeline Fixes (December 19, 2024)
- **findAll Method Signatures**: Fixed all mapper `findAll()` methods to include missing `$ids` parameter
- **Dependency Injection**: Added proper type assertions for Application.php service registration
- **Mixed Operand Resolution**: Fixed remaining mixed operand errors in SynchronizationAction
- **Parameter Usage**: Resolved unused parameter warnings in EventAction
- **OpenRegister Integration**: Implemented conditional event listener registration based on OpenRegister availability
- **Comprehensive Testing**: Ensured all static analysis tools pass without errors

### **Version 1.10** - Code Quality and Style Improvements (December 19, 2024)
- **Comparison Style**: Replaced `!empty()` and `if (!` with strict `===` and `!==` comparisons
- **Type Safety**: Added proper type hints for EndpointsController constructor parameters
- **Error Handling**: Improved error handling in MappingRuntime with proper exception throwing
- **Code Consistency**: Standardized comparison operators throughout the codebase
- **Documentation**: Updated comprehensive documentation with latest improvements

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
├── ci.yml                     # Main CI pipeline (tests + quality)
├── beta-release.yaml          # Beta release workflow
├── documentation.yml           # Documentation workflow
├── phpcs.yml                  # PHP CodeSniffer workflow
├── pull-request-from-branch-check.yaml  # Branch validation
├── pull-request-lint-check.yaml        # Lint checking
├── push-development-to-beta.yaml       # Development to beta
├── release-workflow.yaml      # Production release
├── release-workflow(nightly).yaml      # Nightly release
└── COMPREHENSIVE_DOCUMENTATION.md      # This file

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

*Last Updated: September 23, 2025 | Version: 1.7 | Status: Complete*