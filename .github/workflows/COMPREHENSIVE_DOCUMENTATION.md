# OpenConnector GitHub Workflows Documentation

## Overview
This document tracks the evolution of OpenConnector's GitHub Actions workflows for automated testing and code quality.

---

## Version 1.13 - Docker-Based Nextcloud Environment (Current)

**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Approach:** Real Nextcloud Docker environment matching local development

### 🎯 **Strategy**
Instead of complex OCP mocking, we run tests inside a real Nextcloud container with all required services.

### 🐳 **Docker Stack**
- **MariaDB 10.6** - Database (matching local setup)
- **Redis 7** - Caching and sessions
- **MailHog** - Email testing (`ghcr.io/juliusknorr/nextcloud-dev-mailhog:latest`)
- **Nextcloud** - Real environment (`ghcr.io/juliusknorr/nextcloud-dev-php81:latest`)

### 🔧 **Key Features**
1. **Complete Service Stack** - All services linked and configured
2. **App Installation** - OpenConnector copied and installed in real Nextcloud
3. **Real OCP Classes** - No mocking needed, uses actual Nextcloud classes
4. **Database Migrations** - Handled automatically by Nextcloud
5. **Local Parity** - Exact same images as local docker-compose.yml

### 🐛 **Issues Resolved**
- ✅ **Missing apps-extra directory** - Added `mkdir -p` before copying
- ✅ **PHPUnit command not found** - Use `./vendor/bin/phpunit`
- 🔄 **Composer not available** - Added diagnostics to investigate

### 📁 **Files**
- **`.github/workflows/ci.yml`** - Complete Docker environment
- **`tests/bootstrap.php`** - Simplified bootstrap for container environment
- **Container cleanup** - All services cleaned up after tests

### 🎯 **Benefits**
- **No MockMapper issues** - Uses real OCP classes
- **Local development parity** - Same environment as local
- **Automatic migrations** - Database setup handled by Nextcloud
- **Complete service stack** - Redis, Mail, MariaDB all available

### 📋 **Centralized Version Management**
- **`.github/workflows/versions.env`** - Single source of truth for all versions
- **Environment variables** - CI workflow uses `${{ env.VARIABLE_NAME }}` syntax
- **Local parity** - Versions match your local `docker-compose.yml` and `.env`
- **Easy updates** - Change versions in one place, affects entire CI

---

## Changelog

### Version 1.16 - PHPUnit Installation Fix
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
- Fixed invalid `--no-bin-links` Composer option that doesn't exist
- Reverted to standard PHPUnit installation approach
- Enhanced diagnostics to show PHPUnit executable location
- Applied fixes to both test and quality jobs

### Version 1.15 - PHP Version Fix and Composer Installation
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
- Fixed PHP version in quality job from 8.2 to 8.3 (matches local development)
- Added Composer installation step to both test and quality jobs
- Improved occ command diagnostics with proper file and execution checks
- Fixed missing version configuration in quality job

### Version 1.14 - Centralized Version Management
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
- Added `.github/workflows/versions.env` for centralized version management
- Updated CI workflow to use environment variables (`${{ env.VARIABLE_NAME }}`)
- All Docker images now reference centralized versions
- Matches local development environment versions
- Easy to update versions in one place

### Version 1.13 - Docker-Based Nextcloud Environment
**Date:** September 26, 2025  
**Status:** ✅ Implemented  
**Changes:**
- Implemented real Nextcloud Docker environment
- Added MariaDB 10.6, Redis 7, MailHog services
- Matched local docker-compose.yml setup exactly
- Simplified bootstrap.php for container environment
- Added comprehensive diagnostics for troubleshooting
- Fixed apps-extra directory creation issue
- Fixed PHPUnit command path issue
- Added container cleanup for all services
- **Enhanced Nextcloud health check** - Wait for full initialization including database setup
- **Improved occ command reliability** - Proper working directory and timing
- **Extended timeout** - 10 minutes for complete Nextcloud initialization
- **Better error handling** - Robust curl commands with JSON validation

### Version 1.12 - Reversion to Original Approach
**Date:** September 26, 2025  
**Status:** ❌ Failed  
**Changes:**
- Reverted database-based testing strategy
- Attempted to fix MockMapper signature compatibility
- Removed complex database testing files
- Restored original ci.yml configuration
- **Issue:** MockMapper signature conflicts persisted

### Version 1.11 - Database-Based Testing Strategy
**Date:** September 26, 2025  
**Status:** ❌ Abandoned  
**Changes:**
- Introduced in-memory SQLite database testing
- Created phpunit-ci.xml and bootstrap-ci.php
- Added database setup steps to CI workflow
- **Issue:** Still required complex OCP mocking
- **Result:** Reverted due to complexity

### Future Versions
*This section will be updated as new versions are released*

---

## Current Status

### ✅ **Working**
- Docker environment setup
- Service linking (MariaDB, Redis, Mail, Nextcloud)
- App installation and enabling
- Container cleanup

### 🔄 **In Progress**
- PHPUnit installation in container
- Composer availability investigation
- Test execution optimization

### ✅ **Recently Fixed**
- **Nextcloud initialization timing** - Enhanced health check with JSON validation
- **occ command reliability** - Proper working directory and extended timeouts
- **Container startup sequence** - Better coordination between services

### 📋 **Next Steps**
1. Resolve composer availability in Nextcloud container
2. Install PHPUnit using available package manager
3. Test complete workflow with real environment
4. Optimize performance if needed

---

## Maintenance

### 🔄 **Regular Updates**
- Update Docker image versions
- Monitor workflow performance
- Keep composer.lock synchronized
- Test with actual pull requests

### 📚 **Documentation**
- Update when making changes
- Keep version history clear
- Document issues and solutions

---

*Last Updated: September 26, 2025 | Version: 1.16 | Status: PHPUnit Installation Fix*