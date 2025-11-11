# OpenConnector GitHub Workflows Documentation

## 📄 Overview
This document tracks the evolution of OpenConnector's GitHub Actions workflows for automated testing and code quality.

---

## 🚀 Version
**Current Version:** 1.51 - PHP version-specific Nextcloud Docker images  
**Date:** October 10, 2025  
**Status:** ✅ Completed  
**Approach:** Use PHP version-specific Nextcloud development images (`ghcr.io/juliusknorr/nextcloud-dev-php82` and `ghcr.io/juliusknorr/nextcloud-dev-php83`) dynamically selected per job based on matrix PHP version, ensuring each test runs against the correct PHP version environment.

## 🎯 Strategy
Run unit tests inside a real Nextcloud Docker container with comprehensive diagnostics and host-based autoloader generation to ensure proper class loading and test execution.

## 🐳 Docker Stack
- **MariaDB 10.6** — Database (matching local setup)
- **Redis 7** — Caching and sessions
- **MailHog** — Email testing (`ghcr.io/juliusknorr/nextcloud-dev-mailhog:latest`)
- **Nextcloud** — PHP version-specific development images (`ghcr.io/juliusknorr/nextcloud-dev-php82:latest` for PHP 8.2, `ghcr.io/juliusknorr/nextcloud-dev-php83:latest` for PHP 8.3) (v1.51)
- **Networking (v1.49)** — Dedicated per-job Docker networks (`nc-net-tests`, `nc-net-quality`) replace deprecated `--link`, improving isolation and name-based service discovery.
- **PHP Extensions (v1.50)** — Added `bcmath` to align CI with runtime expectations.

## 🔧 Key Features & Benefits

### **🚀 Reliable Autoloader Generation**
- **Enhanced Class Loading Diagnostics** — Immediate feedback on whether OpenConnector Application class is properly loaded (v1.45)
- **Multi-Step Autoloader Strategy** — Comprehensive approach with multiple fallback methods ensures autoloader creation even when individual methods fail (v1.43)
- **Manual Autoloader Creation** — Guaranteed autoloader generation through manual creation when automated methods fail (v1.43)
- **Early Exit Checks** — Prevents step interference by stopping when autoloader is successfully created (v1.43)

### **🔧 Robust Database Management**
- **Fixed sudo Command Issues** — Install sudo in containers before use (v1.48)
- **App Enable Primary Method** — Uses `app:enable` with proper user context for reliable activation (v1.47)
- **Enhanced Database Verification** — Accurate DB state verification using MariaDB container connections (v1.39)
- **Forced Migration Execution** — Disable/enable cycles ensure migrations run (v1.38)

### **⚡ Workflow Reliability & Performance**
- **Extended Timeouts** — Handle long-running operations (180s for `app:install`, 90s for `app:enable`) (v1.42)
- **Resilient Health Checks** — Prefer warnings to avoid false failures (v1.37)
- **Command Timeout Protection** — 30s timeouts for potentially hanging `occ` commands (v1.36)
- **Comprehensive Diagnostics** - Detailed troubleshooting information for faster issue resolution (v1.36)
- **Modern Networking (v1.49)** — Per-job Docker networks for stable service resolution and cleaner teardown.

### **🎯 Development Environment Parity**
- **Local App Usage** - Tests actual development code instead of published versions (v1.29)
- **Exact Environment Match** - CI environment matches local development exactly using same Docker images (v1.14)
- **Complete Service Stack** - Full Nextcloud environment with Redis, Mail, and MariaDB services (v1.13)
- **Real OCP Classes** - No mocking needed, uses actual Nextcloud classes for real-world compatibility (v1.13)

### **🔍 Advanced Diagnostics & Monitoring**
- **Class Existence Verification** - Verifies that OpenConnector Application class actually exists after autoloader generation (v1.44)
- **File Content Diagnostics** - Checks autoloader file content and permissions to identify issues (v1.44)
- **Container Status Monitoring** - Enhanced error reporting with container status and log analysis (v1.36)
- **Available Commands Testing** - Tests only commands that actually exist in the Nextcloud environment (v1.35)

### **🔐 Security & Permissions**
- **Proper User Context** - All 65+ php occ commands execute as sudo -u www-data for correct permissions and security compliance (v1.47)
- **Simplified App Management** - Focus on app:enable approach with app:install and app:update commented out for cleaner workflow (v1.47)
- **Security Compliance** - Ensures all Nextcloud commands run with appropriate user permissions for production-like security (v1.47)

### **📁 Directory Structure & Compatibility**
- **Standardized Directory Structure** - Use `/var/www/html/custom_apps/` for better Nextcloud compatibility (v1.46)
- **Updated References** - All paths in tests and quality jobs reflect the standardized structure (v1.46)

## 🐛 Issues Resolved
- 🔄 Runner APT permission handling — runner uses `composer install`; APT actions within containers run as root via `docker exec -u 0` (v1.49) - TESTING IN PROGRESS
- 🔄 Tests/Quality job parity — identical bootstrap, diagnostics, composer strategy, migration flow, autoload verification, and logging (v1.49) - TESTING IN PROGRESS
- 🔄 Path normalization — removed `/apps/openconnector` references; standardized to `/var/www/html/custom_apps/openconnector` (v1.49) - TESTING IN PROGRESS
- 🔄 Post-copy ownership — `chown -R www-data:www-data` after `docker cp` to prevent root-owned files (v1.49) - TESTING IN PROGRESS
- 🔄 Container sudo removal — use `docker exec --user www-data` instead of `sudo -u www-data` inside containers (v1.49) - TESTING IN PROGRESS
- 🔄 Shell robustness — added `set -euo pipefail` to major run blocks (v1.49) - TESTING IN PROGRESS
- 🔄 Composer strategy — install curl+Composer as root; run app composer install as www-data; verify composer available (v1.49) - TESTING IN PROGRESS
- ✅ “sudo: command not found” — resolved by installing required tools before usage (v1.48)
- ✅ Container setup — clarified around APT/curl and Composer order (v1.48)
- ✅ Standardized directory structure — `custom_apps` over legacy locations (v1.46)
- ✅ Autoloader generation strategy — multi-step + class existence verification (v1.43–v1.45)
- ✅ Database verification — MariaDB container with explicit table checks (v1.39)
- ✅ Health checks and occ timeouts — prevent hangs (v1.36–v1.37)
- ✅ Composer/enable ordering — prevent missing vendor/autoload.php (v1.31–v1.33)
- ✅ Local parity — same images as local docker-compose (v1.14)
- ✅ Real Nextcloud Docker environment with full service stack (MariaDB, Redis, MailHog) — enables use of real OCP classes without brittle mocks; improves reliability of tests and diagnostics (v1.13)
- ✅ Reproducible runs — explicit container cleanup and isolation across jobs to avoid state leakage between workflow executions (v1.13)
- ✅ Comprehensive container diagnostics — added targeted logs and inspection commands (process lists, Nextcloud logs, directory listings) to speed up troubleshooting (v1.13)

## 📁 Files
- **`.github/workflows/ci.yml`** - Fixed step ordering and added autoloader verification
- **`tests/bootstrap.php`** - Simplified bootstrap for container environment
- **`.github/workflows/versions.env`** - Centralized version management


## 📦 Centralized Version Management
- **`.github/workflows/versions.env`** — Single source of truth for all versions
- **Environment variables** — CI workflow uses `${{ env.VARIABLE_NAME }}`
- **Local parity** — Versions match your local `docker-compose.yml` and `.env`
- **Easy updates** — Change versions in one place, affects entire CI

---

## 📜 Changelog

### Version 1.51 - PHP version-specific Nextcloud Docker images
**Date:** October 10, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🐳 **Dynamic Nextcloud Image Selection** — Removed hardcoded `nextcloud-dev-php83:latest` from global `env:` section; each job now dynamically selects the correct PHP version-specific Nextcloud image based on the PHP version being tested
- 🧪 **Tests Job** — Uses `ghcr.io/juliusknorr/nextcloud-dev-php82:latest` for PHP 8.2 matrix entry and `ghcr.io/juliusknorr/nextcloud-dev-php83:latest` for PHP 8.3 matrix entry
- 🔍 **Quality Job** — Uses `ghcr.io/juliusknorr/nextcloud-dev-php83:latest` for PHP 8.3 (matches the PHP version used in the quality job)
- 🎯 **Version Alignment** — Ensures each test runs against a Nextcloud container with the exact PHP version being tested, improving test accuracy and eliminating version mismatches
- 📦 **Image Registry** — All images now use the `ghcr.io/juliusknorr/` prefix for consistency with MailHog image (`ghcr.io/juliusknorr/nextcloud-dev-mailhog:latest`)

### Version 1.50 - Per-job PHPUnit constraint, bcmath, tool path fixes
**Date:** October 10, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🧪 PHPUnit constraint defined per job: `tests` job uses matrix-based `^9.6` (PHP 8.2) or `^10.5` (PHP 8.3); `quality` job sets constraint explicitly to `^10.5` for PHP 8.3. Avoids cross-job step output leakage and removes duplication.
- ➕ PHP extension `bcmath` added to both jobs' `setup-php` steps for parity with runtime requirements.
- 🛠️ Tool paths and shells: use `bash -lc` and tool binaries from `./lib/composer/bin` (php-cs-fixer, psalm) for consistent resolution inside the Nextcloud container.

### Version 1.49 - Job parity, custom_apps standardization, PHPUnit matrix, Docker networks, safer shell
**Date:** October 9, 2025  
**Status:** 🔄 Testing In Progress  
**Changes:**
- 🧭 Centralized defaults via top-level `env:`; `versions.env` remains supported and, if present, is echoed in logs.
- 🧩 Tests/Quality parity: same container bootstrap, copy to `custom_apps`, ownership fix, occ diagnostics, composer strategy (curl+Composer as root; app composer install as www-data), `MIGRATION_SUCCESS` flow, DB helpers, toggle app, autoload verification, and class_exists retry.
- 📁 Path normalization: all references standardized to `/var/www/html/custom_apps/openconnector`; removed redundant copies and checks under `/apps`.
- 👤 Permissions: avoid `sudo` in containers; use `docker exec --user www-data` for all occ calls.
- 🧰 Composer: install curl+Composer as root; verify `composer --version`; run app composer install as www-data.
- 🧷 Robustness: `set -euo pipefail` added to major `run:` blocks; clearer banners/echo diagnostics for every phase.
- 🧪 PHPUnit: in-container install/verify; tests job runs with coverage and uploads to Codecov; quality job also runs with coverage output inside container.
- 🧪 PHPUnit matrix by PHP version: `^9.6` for PHP 8.2 and `^10.5` for PHP 8.3 (runner and containers).
- 🎯 Accurate code style step: renamed to PHP CS Fixer to match `friendsofphp/php-cs-fixer`.
- 🌐 Modern container networking: replaced deprecated `--link` with per-job Docker networks (`nc-net-tests`, `nc-net-quality`), using service names for `MYSQL_HOST`, `REDIS_HOST`, and `SMTP_HOST`. Networks are removed during cleanup.

### Version 1.48 - Fixed sudo Command Issues and Enhanced Container Setup
**Date:** October 7, 2025  
**Status:** 🔄 Testing In Progress  
**Changes:**
- 🔧 **Fixed sudo Command Not Found Errors** — Added proper `sudo` installation in Nextcloud containers before using `sudo -u www-data` commands to resolve "command not found" errors
- 🐳 **Enhanced Container Setup** — Added `apt update -y && apt install -y sudo curl` in both tests and quality jobs before Composer installation
- 🛠️ **Improved Container Dependencies** — Ensures all required tools (sudo, curl) are available in containers before executing commands
- 🔍 **Fixed Permission Issues** — Resolved APT permission denied errors by ensuring proper package installation in containers
- 🎯 **Workflow Reliability** — Eliminates "sudo: command not found" errors that were causing workflow failures

### Version 1.47 - Enhanced Security with sudo -u www-data Commands and Simplified App Management
**Date:** October 7, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🔐 **Enhanced Security Implementation** — Added `sudo -u www-data` to all 65+ `php occ` commands across both tests and quality jobs for proper user context and security compliance
- 🎯 **Simplified App Management Strategy** — Commented out `app:install` and `app:update` options to focus exclusively on `app:enable` approach for cleaner, more reliable workflow
- 🔧 **Consistent Command Execution** — All `php occ` commands now run with proper user context ensuring correct permissions and security
- 🛡️ **Security Compliance** — Ensures all Nextcloud commands run with appropriate user permissions for production-like security
- 🎯 **Workflow Simplification** — Removed complex fallback chains by focusing on single app:enable approach

### Version 1.46 - Standardized Directory Structure with Custom Apps Path
**Date:** October 7, 2025  
**Status:** 🔄 Testing In Progress  
**Changes:**
- 📁 **Standardized Directory Structure** — Updated workflow to use `/var/www/html/custom_apps/` instead of `/var/www/html/apps-extra/` for better Nextcloud compatibility
- 🏗️ **Improved App Installation Path** — Changed app copy destination from `apps-extra/openconnector` to `custom_apps/openconnector` following Nextcloud standards
- 🔧 **Updated All References** — Updated all file path references throughout both tests and quality jobs to use the new directory structure
- 📋 **Enhanced Diagnostics** — Updated diagnostic messages to reflect the new directory structure for better troubleshooting
- 🎯 **Nextcloud Best Practices** — Aligns with Nextcloud's recommended directory structure for custom applications

### Version 1.45 - Enhanced Autoloader Generation with Improved Class Mapping
**Date:** October 6, 2025  
**Status:** 🔄 Testing In Progress  
**Changes:**
- 🔧 **Enhanced Autoloader Generation with Heredoc Syntax** — Replaced manual echo commands with heredoc syntax for cleaner autoloader creation
- 🔍 **Improved Class Mapping** — Enhanced PSR-4 namespace handling and explicit Application class loading
- 🧪 **Comprehensive Class Loading Diagnostics** — Added class loading tests after autoloader creation to verify OpenConnector Application class is actually loadable
- 📊 **Enhanced Autoloader Content Structure** — Better autoloader structure with improved namespace handling and explicit class loading

### Version 1.44 - Enhanced Diagnostics and Fixed Invalid Flags
**Date:** October 3, 2025  
**Status:** 🔄 Testing In Progress  
**Changes:**
- 🔧 **Fixed Invalid --Force Flag** — Removed non-existent `--force` flag from `app:update` commands that was causing errors and hanging progress bars
- 🔍 **Enhanced Class Existence Checks** — Verify that OpenConnector Application class actually exists after each autoloader generation step, not just file existence
- ⏳ **Improved Timing with Longer Delays** — Added 30-second delays for Nextcloud background processes to complete before checking autoloader generation
- 📋 **Enhanced File Content Diagnostics** — Check actual autoloader file content and permissions to identify malformed or incomplete files

### Version 1.43 - Comprehensive Autoloader Generation Strategy
**Date:** October 3, 2025  
**Status:** 🔄 Testing In Progress  
**Changes:**
- 🔧 **Comprehensive Autoloader Generation Strategy** — Multi-step approach with early exit checks: disable/enable cycle, maintenance repair, forced app update, Composer optimization, and manual creation as fallback
- 🛠️ **Manual Autoloader Creation** — Create `lib/autoload.php` manually with proper PSR-4 autoloader registration if all other methods fail
- 🔄 **Force App Update** — Attempt to trigger autoloader regeneration through app update
- 🔧 **Maintenance Repair Integration** — Use `maintenance:repair` to regenerate autoloaders as part of comprehensive strategy
- ⚡ **Classmap Authoritative Optimization** — Use Composer’s `--classmap-authoritative` flag for optimized autoloader generation
- 🎯 **Guaranteed Autoloader Creation** — Manual creation ensures `lib/autoload.php` exists even if all automated methods fail
- 🔍 **Multi-Step Fallback Strategy** — Multiple approaches ensure autoloader generation success
- ✅ **Early Exit Checks** — Prevent step interference by checking for success after each method and exiting early if successful

### Version 1.42 - Workflow Structure Fix + Early Autoloader Check + Timing Fix
**Date:** October 3, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🔍 **Early Autoloader Check** — Check if `lib/autoload.php` was already generated during app installation before attempting generation
- 🏗️ **Workflow Structure Fix** — Address the core timing issue by checking autoloader immediately after app installation
- ⏱️ **Timing Fix** — Added 10-second wait for background autoloader generation to complete
- 🔧 **Nextcloud App Autoloader Generation** — Use Nextcloud’s `app:update` to trigger autoloader generation for app-specific classes
- ⏱️ **Extended Timeouts** — Increased timeouts to 180s for `app:install` and 90s for `app:enable`
- 🎯 **Targeted Autoloader Fix** — Addresses the core issue that Composer generates vendor autoloaders, not app-specific autoloaders
- 🔍 **Progress Bar Resolution** — Extended timeouts should resolve the hanging progress bar during app installation

### Version 1.41 - Enhanced Autoload Diagnostics + Changelog Status Updates
**Date:** October 3, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🔍 **Enhanced Autoload Diagnostics** — Added comprehensive diagnostics to identify where Composer places autoload files
- 📊 **Updated Changelog Statuses** — Updated v1.35–v1.38 to ✅ Completed
- 🔍 **Autoload File Location Investigation** — Added diagnostics to find autoload files in `vendor/`, `lib/`, and other locations
- 🔍 **Composer Working Directory Diagnostics** — Added checks to verify Composer execution context and file placement

### Version 1.40 - Fixed Autoload Generation Inside Container + Timeout Protection
**Date:** October 2, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🔧 **Fixed Autoload Generation** — Generate autoload files inside container instead of host to fix `lib/autoload.php not found`
- ⏱️ **Added Timeout Protection** — Added timeouts to prevent hanging progress bars and command timeouts
- 🔍 **Enhanced Diagnostics** — Added comprehensive diagnostics for autoload generation and timeout issues

### Version 1.39 - Enhanced Database Verification with MariaDB Container Connection
**Date:** October 2, 2025  
**Status:** ✅ Completed  
**Changes:**
- ✅ **Proper DB Verification** — Use MariaDB container for `mysql` commands
- 🧪 **Diagnostics** — Show actual tables and counts for `oc_openconnector_*`
- 🚦 **Better Errors** — Clearer messages on verification failures

### Version 1.38 - App Install Primary Method + Forced Migration Execution
**Date:** October 2, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🔄 **Primary Install via app:install** — Ensures migrations run before app code executes
- 🔁 **Forced Migration** — Disable/enable cycle to force migration execution
- 🧰 **Schema Fix Commands** — `db:add-missing-indices`, `db:add-missing-columns`, `db:convert-filecache-bigint`

### Version 1.37 - Resilient Health Checks
**Date:** October 2, 2025  
**Status:** ✅ Completed  
**Changes:**
- ⚠️ **Warnings over Failures** — Soft health checks avoid false negatives
- 🔁 **Fallbacks** — Alternative checks when primary commands fail

### Version 1.36 - Command Timeout and Health Checks
**Date:** October 2, 2025  
**Status:** ✅ Completed  
**Changes:**
- ⏱️ **30s Timeouts** — Prevent hanging `occ` commands
- 🧪 **Health Checks** — Verify readiness before running commands

### Version 1.35 - Available Commands Testing
**Date:** October 2, 2025  
**Status:** ✅ Completed  
**Changes:**
- 🧭 **Command Discovery** — Use `app --help` to validate availability
- 🧹 **Removed Unsupported Options** — No `app:upgrade`, no `--path`

### Version 1.34 - Database Schema Preparation Fix
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧰 **Pre-Enable Repair** — Run `maintenance:repair` before `app:enable`
- 🗃️ **Tables Ready** — Reduce “table doesn’t exist” errors

### Version 1.33 - Composer Installation Order Fix
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🔧 **Order** — Install Composer before composer-based steps
- 🧪 **Stability** — Avoid “composer: command not found”

### Version 1.32 - Database Migration Step Addition
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧰 **Migrations** — Added explicit migration step around enabling

### Version 1.31 - Dependencies Before Enabling and Step Name Fixes
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 📦 **Deps First** — Install app dependencies before `app:enable`
- 🏷️ **Step Names** — Clarified step naming and contexts

### Version 1.30 - Comprehensive Workflow Consistency Fixes
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧩 **Consistency** — Tests and quality jobs follow identical patterns
- 🧹 **De-duplication** — Removed redundant enable steps

### Version 1.29 - Workflow Step Ordering and App Installation Fixes
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧭 **Ordering** — Start containers before installing dev deps
- 🛠️ **Local App** — Prefer `app:enable` for local code

### Version 1.28 - Critical Workflow Fixes
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧭 **Ordering** — Start Docker first in quality job
- 🔍 **Autoloader Verification** — Verify `lib/autoload.php` creation

### Version 1.27 - App Autoloader Generation Fix
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧰 **Host Generation** — Generate autoloader on host; copy into container
- 🔁 **Reload** — Disable/enable and `maintenance:repair` to reload

### Version 1.26 - Optimized Retry Mechanism and Timing Fixes
**Date:** September 30, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🔁 **Retries** — 5 attempts with short sleeps for class checks

### Version 1.25 - Enhanced Diagnostics and Cache Clearing
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🔍 **Diagnostics** — Clear structure, logs, and cache checks

### Version 1.24 - Fixed App Location Issue
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 📁 **Path Fix** — Use correct app paths to satisfy Nextcloud expectations

### Version 1.23 - Added App Structure Diagnostics and Fixed Command Failures
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🔍 **Structure Checks** — Stronger diagnostics for app integrity

### Version 1.22 - Fixed CodeSniffer Dependencies and App Class Loading
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧰 **Deps** — Ensure project deps are installed before style tools
- 🔁 **Reload** — Disable/enable after deps to refresh autoloader

### Version 1.21 - Improved User Feedback and Fixed Missing PHP Extensions
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🗣️ **Messaging** — Clearer success/warning output
- 📦 **Composer Flags** — Ignore missing `ext-soap`/`ext-xsl` on CI

### Version 1.20 - Fixed Composer Installation Order
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧭 **Ordering** — Composer available before any composer commands

### Version 1.19 - App Dependencies Installation Fix
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 📦 **Install** — `composer install --no-dev --optimize-autoloader` for app

### Version 1.18 - Enhanced App Installation Diagnostics
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🔍 **Diagnostics** — More visibility into install steps and failures

### Version 1.17 - PHPUnit Autoloader Fix
**Date:** September 29, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧪 **Autoload** — `composer dump-autoload --optimize` after PHPUnit install

### Version 1.16 - PHPUnit Installation Fix
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🧰 **Composer Flags** — Removed invalid flags; stable PHPUnit install

### Version 1.15 - PHP Version Fix and Composer Installation
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🔢 **Versions** — Align PHP versions; install Composer in containers
- 🧪 **occ Diagnostics** — Better checks for file/exec and versions

### Version 1.14 - Centralized Version Management
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
- 🗂️ **versions.env** — Single source of truth for versions
- 🔗 **env Usage** — Reference images via `${{ env.* }}`

### Version 1.13 - Docker-Based Nextcloud Environment
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
### Version 1.12 — Reversion to Original Approach
**Date:** September 26, 2025  
**Status:** ❌ Failed  
**Changes:** Attempted to revert to prior CI setup to resolve MockMapper conflicts; problems persisted, approach abandoned.
### Version 1.11 — Database-Based Testing Strategy (Experimental)
**Date:** September 26, 2025  
**Status:** ❌ Abandoned  
**Changes:** Prototype SQLite strategy (`phpunit-ci.xml`, `bootstrap-ci.php`) reduced mocks but added complexity; reverted in favor of full Nextcloud containers.

---

## 📊 Current Status

### 🔄 **Currently Testing (v1.49)**
- Fixed sudo command issues — Ensure `sudo` is present before `sudo -u www-data`. (v1.48)
- Service linking (now via Docker networks & service names, v1.49)
- App dependencies installation
- Database schema preparation with `maintenance:repair`
- Command availability checking

### 🔄 **Currently Testing (v1.48)**
- Fixed sudo command issues — Testing proper sudo installation in Nextcloud containers before using `sudo -u www-data`
- Enhanced container setup — Testing improved container dependencies with `apt update` and sudo/curl installation before Composer setup
- Enhanced security implementation — Testing all `php occ` commands running as `sudo -u www-data` (v1.47)
- Standardized directory structure — Testing updated workflow to use `/var/www/html/custom_apps/` (v1.46)
- Enhanced autoloader generation — Testing comprehensive strategy with improved class mapping and diagnostics (v1.45)

### ✅ **Recently Fixed**
- PHPUnit versioning aligned per PHP (v1.49)
- Per-job PHPUnit constraint; removed duplicate step in tests job (v1.50)
- Added `bcmath` to PHP extensions in both jobs (v1.50)
- Use `bash -lc` and `./lib/composer/bin/*` for dev tools execution (v1.50)
- Deprecated `--link` removed; networks used (v1.49)
- Step naming corrected to **PHP CS Fixer** (v1.49)
- Invalid `--force` flag removed (v1.44)
- Database verification via MariaDB container (v1.39)

## 🛠️ Maintenance

### 🔄 **Regular Updates**
- Update Docker image versions
- Monitor workflow performance
- Keep `composer.lock` synchronized
- Test with actual pull requests

### 📚 **Documentation**
- Update when making changes
- Keep version history clear
- Document issues and solutions

---

*Last Updated: October 10, 2025 | Version: 1.51 | Status: PHP version-specific Nextcloud Docker images*
