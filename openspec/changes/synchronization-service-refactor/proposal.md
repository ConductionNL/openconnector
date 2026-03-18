## Why

`SynchronizationService` has grown to 4228 lines and 70+ methods, making it a God class that violates Single Responsibility Principle and is impossible to test, reason about, or extend without side effects. The class mixes orchestration, contract lifecycle, pagination, rule processing, file handling, ID resolution, and XML parsing into a single file, creating brittle coupling and well-documented `@todo this is weird` debt throughout the code.

## What Changes

- Extract **pagination logic** into a dedicated `PaginationService` (7 methods: `fetchAllPages`, `fetchAllPagesOptimized`, `fetchAllPagesSequential`, `getNextPageInfo`, `getNextPage`, `getNextEndpoint`, `getNextlinkFromCall`)
- Extract **rule processing** into a dedicated `RuleProcessorService` (8 methods: `processRules`, `processErrorRule`, `processMappingRule`, `processSyncRule`, `processSaveObjectRule`, `processFetchFileRule`, `processWriteFileRule`, `processExtendInputRule`, plus `getRuleById`, `checkRuleConditions`)
- Extract **file handling** into a dedicated `SyncFileService` (10 methods: `fetchFile`, `writeFile`, `startAsyncFileFetching`, `executeAsyncFileFetching`, `fetchFileSafely`, `cleanupOrphanedFiles`, `processMultipleFilesWithCleanup`, `cleanupFilesFromAttachments`, `getFilenameFromHeaders`, `getFileContext`, `shouldPublishFile`, `generatePlaceholderValues`)
- Extract **target adapter** logic into a `TargetAdapterInterface` with two implementations: `OpenRegisterTargetAdapter` (wraps `updateTargetOpenRegister`) and `ApiTargetAdapter` (wraps `writeObjectToTarget`)
- Extract **ID/relation resolution** into a `ContractIdResolverService` (6 methods: `replaceRelatedOriginIds`, `replaceIdInString`, `updateContractsForSubObjects`, `processSyncContract`, `updateIdsOnSubObjects`, `updateIdOnSubObject`)
- Eliminate the duplicate contract-lookup-and-process pattern that appears twice with `// @todo this is weird` comments (in `processSynchronizationObject` and `synchronizeInternToExtern`)
- Remove repeated `applyConfigDot()` calls — parse config once per sync run and pass it down
- Narrow the public API surface: make `getAllObjectsFromApi`, `getAllObjectsFromSource`, `synchronizeContract`, `deleteInvalidObjects`, `updateTarget`, `replaceRelatedOriginIds`, `getObjectFromSource` internal where possible
- Fix mixed/ambiguous return types: `SynchronizationContract|Exception|array` → proper exception throwing; `array|JSONResponse` → proper exception throwing
- Remove or replace dead code: `startAsyncFileFetching` (no-op wrapper), large commented-out switch block in `processFetchFileRule`, `fetchAllPagesSequential` (superseded but still present)
- Add missing `FlowToken` parameter to `synchronizeToTarget` so it participates in the same flow tracing as the other entrypoints

## Capabilities

### New Capabilities

- `pagination-service`: Handles paginated API traversal for synchronization sources; isolated from object processing and contract logic
- `rule-processor-service`: Applies ordered, conditional rules to synchronization data objects; isolated from both fetch and persist logic
- `sync-file-service`: Handles all file fetch, write, and cleanup operations within synchronization flows
- `target-adapter`: Adapter interface + implementations for writing synchronized data to different target types (register/schema, api)
- `contract-id-resolver`: Resolves and replaces origin IDs with target IDs across object graphs using SynchronizationContract mappings

### Modified Capabilities

*(none — external behaviour does not change; this is a pure internal refactor)*

## Impact

**Directly modified:**
- `lib/Service/SynchronizationService.php` — shrinks to orchestration only (~600–800 lines)
- `lib/AppInfo/Application.php` — registers new services in the DI container
- Any `use` statements in callers that reference public methods that move

**Callers that must be verified after refactor:**
- `lib/Action/SynchronizationAction.php` — calls `synchronize()`
- `lib/Controller/SynchronizationsController.php` — calls `synchronize()`, `getSynchronization()`, `deleteInvalidObjects()`
- `lib/Controller/JobsController.php` — calls `synchronize()`
- `lib/EventListener/ObjectCreatedEventListener.php` — calls `handleObjectEventSynchronization()`
- `lib/EventListener/ObjectDeletedEventListener.php` — calls `handleObjectEventSynchronization()`
- `lib/EventListener/ObjectUpdatedEventListener.php` — calls `handleObjectEventSynchronization()`
- `lib/EventListener/ViewUpdatedOrCreatedEventListener.php` — calls `synchronize()`
- `lib/Service/EndpointService.php` — calls `synchronize()`, `getSynchronization()`

**No API contract changes** — all HTTP endpoints, cron jobs, and event listeners continue to work identically.
