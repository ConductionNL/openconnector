# Code Quality Tools

## Overview

This document explains the code quality tools we use to maintain high standards in our codebase. We employ three primary static analysis tools:

1. **PHP_CodeSniffer** - For coding standards and style
2. **PHPStan** - For type checking and detecting errors
3. **Psalm** - For additional static analysis and type checking

## Setting Up

All quality tools are installed as development dependencies via Composer:

```bash
# Install all development dependencies
composer install

# If you need to install them separately:
composer require --dev squizlabs/php_codesniffer
composer require --dev phpstan/phpstan
composer require --dev vimeo/psalm
```

## Running Quality Checks

### Using the Quality Check Script

We provide a convenient script to run all quality checks on a specific file or directory:

```bash
# Check a single file
./scripts/check-quality.sh lib/Service/FileHandlerService.php

# Check a directory
./scripts/check-quality.sh lib/Service/
```

The script generates detailed reports in the `build/quality-reports/` directory.

### Running Tools Manually

You can also run the tools individually:

#### PHP_CodeSniffer

```bash
# Check coding standards
phpcs --standard=PSR12 path/to/file.php

# Automatically fix issues
phpcbf --standard=PSR12 path/to/file.php
```

#### PHPStan

```bash
vendor/bin/phpstan analyse path/to/file.php

# Specify analysis level (0-9, higher is stricter)
vendor/bin/phpstan analyse --level=5 path/to/file.php
```

#### Psalm

```bash
vendor/bin/psalm path/to/file.php --no-cache

# Show issues and suggestions
vendor/bin/psalm --show-info=true path/to/file.php
```

## Pre-commit Hook

> **Note**: The pre-commit hook is currently disabled due to issues with handling error reporting. You are encouraged to run the quality checks manually before committing code.

To enable the pre-commit hook for automatic quality checks:

```bash
# Copy the pre-commit hook to Git hooks directory
cp scripts/pre-commit.sample .git/hooks/pre-commit

# Make sure the hook is executable
chmod +x .git/hooks/pre-commit
```

When enabled, the pre-commit hook will automatically run quality checks on PHP files being committed and prevent commits if there are quality issues.

## Configuration Files

### PHPStan Configuration (phpstan.neon)

```yaml
parameters:
    level: 5
    paths:
        - lib
    excludePaths:
        analyse:
            - vendor
    tmpDir: build/phpstan
    reportUnmatchedIgnoredErrors: false
    ignoreErrors:
        - '#Call to an undefined method OCP\\#'
        - '#Call to an undefined method OCA\\#'
        - '#Instantiated class OCP\\#'
        - '#Instantiated class OCA\\#'
```

### Psalm Configuration (psalm.xml)

```xml
<?xml version="1.0"?>
<psalm
    errorLevel="5"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    findUnusedBaselineEntry="true"
    findUnusedCode="false"
>
    <projectFiles>
        <directory name="lib" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>
    <issueHandlers>
        <UndefinedClass>
            <errorLevel type="suppress">
                <directory name="lib" />
            </errorLevel>
        </UndefinedClass>
        <UndefinedMethod>
            <errorLevel type="suppress">
                <directory name="lib" />
            </errorLevel>
        </UndefinedMethod>
    </issueHandlers>
</psalm>
```

## Common Issues and How to Fix Them

### PHP_CodeSniffer Issues

1. **Line length exceeds 120 characters**
   - Break long lines into multiple lines
   - Extract complex expressions into variables

2. **Spaces instead of tabs**
   - Configure your editor to use spaces
   - Use 4 spaces for indentation

3. **Missing docblocks**
   - Add comprehensive docblocks to all classes and methods
   - Include @param, @return, and @throws tags

### PHPStan Issues

1. **Parameter type mismatch**
   - Ensure parameter types match method signatures
   - Use proper type hints in docblocks and method signatures

2. **Undefined method/property**
   - Check for typos in method/property names
   - Ensure the class actually has the method/property

3. **Missing return type**
   - Add return type declarations to methods
   - Use void for methods that don't return anything

### Psalm Issues

1. **Possibly undefined variable**
   - Initialize variables before using them
   - Check if variables exist before using them

2. **Possibly null value**
   - Use null coalescing operator (??) for defaults
   - Add null checks before using values

3. **Type mismatch**
   - Add type assertions or checks
   - Use more specific types in docblocks

## Best Practices

1. **Run checks locally before committing**
   - Use `./scripts/check-quality.sh` on modified files
   - Fix all issues before pushing

2. **Use annotations to improve type checking**
   - Add @psalm-pure for pure functions
   - Use @phpstan-param for complex parameter types
   - Add @phpstan-return for complex return types

3. **Improve code incrementally**
   - Focus on fixing one type of issue at a time
   - Address critical issues first

4. **Document exceptions**
   - Add @throws tags in docblocks
   - Document all possible exception scenarios

By maintaining high code quality standards, we ensure our codebase remains maintainable, robust, and free of common bugs and issues. 