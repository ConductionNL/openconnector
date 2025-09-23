# OpenConnector GitHub Workflow Setup

## Created Files

### 1. Workflow Files
- `pr-unit-tests.yml` - **Main workflow for pull request unit tests**
- `unit-tests.yml` - Comprehensive unit tests with matrix strategy
- `quality-checks.yml` - Full quality assurance pipeline
- `pull-request-unit-tests.yaml` - Simple PR test workflow

### 2. Test Infrastructure
- `tests/bootstrap.php` - Test bootstrap file for PHPUnit
- Updated `phpunit.xml` - Fixed directory path from `tests/unit` to `tests/Unit`

### 3. Documentation
- `README.md` - Comprehensive workflow documentation
- `WORKFLOW_SETUP.md` - This setup summary

## Recommended Workflow

For pull request unit tests, use: **`pr-unit-tests.yml`**

This workflow:
- ✅ Triggers on pull requests to `development`, `main`, `master`
- ✅ Uses PHP 8.2 (stable version)
- ✅ Caches Composer dependencies for faster builds
- ✅ Creates test database
- ✅ Runs `composer test:unit`
- ✅ Provides clear test results

## Test Command

The workflow uses the Composer script defined in `composer.json`:
```bash
composer test:unit
```

This runs:
```bash
phpunit tests -c tests/phpunit.xml --colors=always --fail-on-warning --fail-on-risky
```

## Next Steps

1. **Commit and push** these files to your OpenConnector repository
2. **Create a test pull request** to verify the workflow works
3. **Check the Actions tab** in GitHub to see the workflow running
4. **Customize** the workflow as needed for your specific requirements

## Workflow Features

- **Automatic triggering** on pull requests
- **Composer dependency caching** for faster builds
- **PHP 8.2 environment** with required extensions
- **Test database setup** (SQLite)
- **Clear test reporting** with success/failure status
- **Error handling** with proper exit codes

## Troubleshooting

If the workflow fails:
1. Check that `tests/bootstrap.php` exists and is valid
2. Verify `phpunit.xml` points to the correct test directory
3. Ensure all Composer dependencies are installed
4. Check PHP version compatibility
5. Verify test database permissions
