# Synchronization Architecture

## Overview

The OpenConnector application uses a modular synchronization architecture to efficiently handle data transfers between different sources and targets. This document explains the core components and their interactions.

## Core Components

### SynchronizationService

The SynchronizationService orchestrates the entire synchronization process, but delegates specialized tasks to more focused services:

- Manages the overall synchronization flow
- Tracks synchronization progress and results
- Delegates specific operations to specialized services
- Maintains synchronization logs and contracts

### Source Handlers

Source handlers are responsible for retrieving data from specific types of data sources:

- **SourceHandlerRegistry**: Manages all source handlers and provides access to the appropriate one
- **JsonApiHandler**: Handles JSON API sources
- **XmlHandler**: Handles XML sources
- **SoapHandler**: Handles SOAP web services

See [Source Handlers Architecture](source-handlers.md) for more details.

### FileHandlerService

The FileHandlerService manages all file-related operations during synchronization:

- Fetching files from external sources
- Writing files to the filesystem
- Assigning tags to files
- Extracting filenames from HTTP responses

### RuleProcessorService

The RuleProcessorService evaluates and applies rules during synchronization:

- Checks rule conditions against data
- Processes error rules
- Applies mapping rules to transform data
- Handles file fetching and writing rules
- Manages synchronization rules

### MappingService

The MappingService transforms data between source and target formats:

- Applies templates to transform data structures
- Handles different mapping types (Twig, JSON, etc.)
- Manages complex data transformations

## Synchronization Flow

The typical synchronization process follows these steps:

1. **Initialization**: Create synchronization log and validate configuration
2. **Data Retrieval**: Use source handlers to fetch data from the source
3. **Processing**: For each object:
   - Apply source hash mapping
   - Check conditions for processing
   - Fetch additional data if needed
   - Transform data using mappings
   - Apply before-synchronization rules
4. **Target Updates**: Update target systems with processed data
5. **Clean-up**: Delete invalid objects if needed
6. **Follow-ups**: Process any follow-up synchronizations
7. **Finalization**: Complete logs and return results

## Architecture Benefits

This modular architecture provides several advantages:

1. **Maintainability**: Each component has a clear, focused responsibility
2. **Extensibility**: New source types, rules, or target types can be added easily
3. **Testability**: Components can be tested independently
4. **Performance**: Specialized handling for different source types
5. **Robustness**: Better error handling and rate limiting support

## Dependency Diagram

```
SynchronizationService
 ├── SourceHandlerRegistry
 │    ├── JsonApiHandler
 │    ├── XmlHandler
 │    └── SoapHandler
 ├── FileHandlerService
 ├── RuleProcessorService
 └── MappingService
```

## Best Practices

When working with the synchronization architecture:

1. **Add new source types** by creating handlers that implement SourceHandlerInterface
2. **Extend rule types** by adding new case handlers to the RuleProcessorService
3. **Maintain separation** between data retrieval, transformation, and target updates
4. **Use logging** throughout the synchronization process for debugging
5. **Handle rate limits** appropriately for each source type

## Error Handling

The architecture includes several error handling mechanisms:

- Rate limit detection and management
- Detailed logging of synchronization steps
- Exception capture and reporting
- Ability to retry failed synchronizations

## Future Extensions

The modular architecture allows for several possible extensions:

- Additional source types (GraphQL, RSS, etc.)
- More rule types for complex business logic
- Enhanced monitoring and reporting capabilities
- Improved performance through parallel processing 