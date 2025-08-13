# Logging Migration to PSR LoggerInterface for Nextcloud 31 Compatibility

## Overview

OpenConnector has been updated to use the modern PSR-3 LoggerInterface for all logging operations, ensuring full compatibility with Nextcloud 31. This migration replaces the deprecated logging patterns with the standard PSR LoggerInterface approach.

## What Changed

### Before (Deprecated - Nextcloud 30 and earlier)
```php
// Old pattern 1 - Direct server access
\OC::$server->getLogger()->error('Error message', ['context']);

// Old pattern 2 - PHP error_log function  
error_log('Error message');
```

### After (Nextcloud 31 Compatible)
```php
// New pattern - PSR LoggerInterface dependency injection
class YourService {
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
    
    public function someMethod() {
        $this->logger->error('Error message', [
            'app' => 'openconnector',
            'service' => 'YourService', 
            'exception' => $e
        ]);
    }
}

// For Mapper classes - using lazy resolution
class YourMapper extends QBMapper {
    private function getLogger(): LoggerInterface {
        return Server::get(LoggerInterface::class);
    }
    
    public function someMethod() {
        $this->getLogger()->error('Error message', [
            'app' => 'openconnector',
            'exception' => $e
        ]);
    }
}
```

## Files Updated

### All Mapper Classes (15 files)
All database mapper classes have been updated to use PSR LoggerInterface:

- `EventMessageMapper.php`
- `EndpointMapper.php` 
- `EventSubscriptionMapper.php`
- `SynchronizationContractMapper.php`
- `MappingMapper.php`
- `JobMapper.php`
- `RuleMapper.php`
- `SynchronizationMapper.php`
- `SourceMapper.php`
- `CallLogMapper.php`
- `JobLogMapper.php`
- `SynchronizationContractLogMapper.php`
- `SynchronizationLogMapper.php`
- Plus 2 additional mapper classes

### Service Classes (2 files)
- `SynchronizationService.php` - Updated constructor to inject LoggerInterface
- `SettingsService.php` - Updated constructor to inject LoggerInterface

## Implementation Details

### Dependency Injection Pattern
Service classes now use proper dependency injection for the logger:

```php
public function __construct(
    // ... other dependencies
    private readonly LoggerInterface $logger
) {
    // Constructor implementation
}
```

### Lazy Resolution Pattern (Mappers)
Mapper classes use lazy resolution to avoid circular dependencies:

```php
private function getLogger(): LoggerInterface
{
    return Server::get(LoggerInterface::class);
}
```

### Enhanced Context Information
All logging calls now include rich context information:

```php
$this->logger->error('Operation failed', [
    'app' => 'openconnector',
    'service' => 'ServiceName',
    'method' => 'methodName',
    'objectId' => $objectId,
    'exception' => $e
]);
```

## Benefits

1. **Nextcloud 31 Compatibility**: Full compatibility with Nextcloud 31's logging infrastructure
2. **PSR-3 Standard**: Follows industry-standard logging interface
3. **Better Context**: Rich contextual information in all log entries
4. **Proper Error Levels**: Uses appropriate log levels (error, warning, info)
5. **Performance**: Lazy loading where appropriate to avoid overhead
6. **Maintainability**: Clean, consistent logging patterns across the codebase

## Log Levels Used

- **Error**: Critical issues that need immediate attention
- **Warning**: Issues that should be monitored but don't stop operation  
- **Info**: Informational messages about normal operations

## Migration Statistics

- **Total files updated**: 17 files
- **Old logging calls replaced**: 56 instances
  - `\OC::$server->getLogger()`: 41 instances
  - `error_log()`: 15 instances
- **New PSR LoggerInterface calls**: 56 instances

All old logging patterns have been completely removed from the codebase.

## For Developers

When adding new logging to OpenConnector:

1. **Service Classes**: Inject `LoggerInterface` via constructor
2. **Mapper Classes**: Use the `getLogger()` lazy resolution method
3. **Always include context**: Provide relevant context information
4. **Use appropriate levels**: Choose the correct log level for the message
5. **Include app identifier**: Always include `'app' => 'openconnector'` in context

Example new logging implementation:
```php
$this->logger->warning('File upload failed', [
    'app' => 'openconnector',
    'service' => 'FileService',
    'filename' => $filename,
    'error' => $error,
    'exception' => $e
]);
```

This migration ensures OpenConnector remains fully compatible with current and future versions of Nextcloud while following modern PHP logging standards.
