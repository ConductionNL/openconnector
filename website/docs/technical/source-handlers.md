# Source Handlers Architecture

## Introduction

The OpenConnector app uses a modular architecture for handling different types of data sources. This design ensures that the SynchronizationService remains maintainable by delegating source-specific logic to specialized handler classes.

## Components

The source handler architecture consists of the following key components:

### 1. SourceHandlerInterface

This interface defines the contract that all source handlers must implement:

- `canHandle(string $sourceType): bool` - Determines if a handler can process a specific source type
- `getAllObjects(Source $source, array $config, ...): array` - Retrieves all objects from a data source
- `getObject(Source $source, string $endpoint, array $config, ...): array` - Retrieves a single object from a data source

### 2. AbstractSourceHandler

This abstract class provides common functionality for all handlers:

- Rate limit checking
- Headers management
- Object extraction from response data

### 3. Specific Handlers

Each specific data format has its own handler:

- `JsonApiHandler` - For JSON API sources
- `XmlHandler` - For XML data sources
- `SoapHandler` - For SOAP web services

### 4. SourceHandlerRegistry

This registry manages all available handlers and provides a facade for the SynchronizationService to access the appropriate handler for each source type.

## Architecture Benefits

This architecture provides several advantages:

1. **Separation of Concerns**: Each handler focuses on a specific data format
2. **Extensibility**: New source types can be added by implementing new handlers
3. **Maintainability**: The SynchronizationService is decoupled from source-specific logic
4. **Code Organization**: Related functionality is grouped logically

## Sequence of Events

When synchronizing data:

1. The SynchronizationService receives a synchronization request
2. It identifies the source type and requests the appropriate handler from the SourceHandlerRegistry
3. The registry returns the correct handler based on the source type
4. The SynchronizationService delegates data retrieval to the handler
5. The handler processes the source-specific logic and returns the data
6. The SynchronizationService continues with mapping and synchronization

## Related Services

The source handlers architecture works alongside other services:

- **FileHandlerService**: Manages file operations during synchronization
- **RuleProcessorService**: Processes rules and conditions for synchronization
- **MappingService**: Transforms data between source and target formats

## Implementation

The handlers are registered in the SourceHandlerRegistry during construction. When a source needs to be accessed, the registry finds the appropriate handler by calling `canHandle()` on each registered handler until it finds one that returns true.

## Adding New Handlers

To add support for a new source type:

1. Create a new handler class that extends AbstractSourceHandler
2. Implement the required methods from SourceHandlerInterface
3. Add specialized logic for the new source type
4. Register the new handler in the SourceHandlerRegistry constructor

## Code Examples

### Using the SourceHandlerRegistry

```php
// Get all objects from a source
$objects = $this->sourceHandlerRegistry->getAllObjects(
    source: $source,
    config: $sourceConfig,
    isTest: $isTest,
    currentPage: $currentPage,
    headers: $headers,
    query: $query
);

// Get a single object
$object = $this->sourceHandlerRegistry->getObject(
    source: $source,
    endpoint: $endpoint,
    config: $config,
    headers: $headers,
    query: $query
);
``` 