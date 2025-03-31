# Endpoint Service Architecture

The OpenConnector's Endpoint Service has been refactored to follow a modular, handler-based architecture similar to the Synchronization Service. This document explains the new architecture, components, and how they work together.

## Overview

The EndpointService has been redesigned to use a plugin-based architecture with specialized handlers for different endpoint types. This approach offers several benefits:

- **Modularity**: New endpoint types can be added without modifying the core service
- **Separation of concerns**: Each handler focuses on a specific type of endpoint
- **Testability**: Components can be tested in isolation
- **Extensibility**: Rules processing is now handled by specialized rule handlers

## Core Components

### EndpointService

The central service that manages endpoint requests and coordinates between handlers. Its responsibilities include:

- Registering and managing request handlers
- Processing request data and content
- Executing pre-request and post-request rules
- Routing requests to appropriate handlers
- Generating endpoint URLs

### RequestHandlerInterface

Defines the contract for endpoint request handlers:

```php
interface RequestHandlerInterface {
    public function canHandle(Endpoint $endpoint): bool;
    public function handle(Endpoint $endpoint, IRequest $request, string $path, array $data): JSONResponse;
}
```

### Handler Implementations

- **SchemaRequestHandler**: Handles requests to schema-based endpoints in a register
- **SourceRequestHandler**: Handles requests to API source endpoints 

### Rule Processing

Rules processing has been extracted into dedicated components:

- **RuleProcessorService**: Manages the rule processing pipeline and delegates to handlers
- **RuleHandlerInterface**: Defines the contract for rule handlers
- **Rule Handler Implementations**: Specialized handlers for different rule types (Authentication, Mapping, etc.)

### Support Services

- **RequestProcessorService**: Handles common HTTP request processing tasks like content parsing and header management

## Request Flow

1. **Request Received**: An HTTP request comes in for an endpoint
2. **Data Extraction**: Request data is extracted and parsed
3. **Pre-Request Rules**: Rules with timing "before" are processed
4. **Handler Selection**: The appropriate handler is selected based on endpoint type
5. **Request Handling**: The selected handler processes the request
6. **Post-Request Rules**: Rules with timing "after" are processed for successful responses
7. **Response**: The final response is returned to the client

## Adding New Handlers

To add support for a new endpoint type:

1. Create a new class implementing `RequestHandlerInterface`
2. Implement the `canHandle()` method to identify when your handler should be used
3. Implement the `handle()` method with the endpoint-specific logic
4. Register your handler with the EndpointService

## Adding New Rule Handlers

To add support for a new rule type:

1. Create a new class implementing `RuleHandlerInterface`
2. Implement the `canProcess()` method to identify which rules your handler can process
3. Implement the `process()` method with the rule-specific logic
4. Register your handler with the RuleProcessorService

## Example: Processing a Request

```php
// Request comes in
$endpoint = $endpointMapper->find($endpointId);
$response = $endpointService->handleRequest($endpoint, $request, $path);

// Inside EndpointService handleRequest method:
// 1. Extract and parse request data
// 2. Process pre-request rules
// 3. Find appropriate handler
$handler = $this->findHandlerForEndpoint($endpoint);
// 4. Handle the request
$response = $handler->handle($endpoint, $request, $path, $preRuleResult);
// 5. Process post-request rules
// 6. Return response
```

## Conclusion

The new handler-based architecture for EndpointService improves code organization and makes the system more maintainable and extensible. It follows the same patterns used in the SynchronizationService, providing a consistent development experience across the application. 