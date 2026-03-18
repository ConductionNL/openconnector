# Tasks: SynchronizationService Refactor

> **Scope:** Pure internal refactor — no external API/contract changes.
> Each group is a deployable unit; complete one group before starting the next.
> Run `composer check` after each group to verify no regressions.

---

## 1. Preparation & Safety Net

- [ ] 1.1 Audit current test coverage of `SynchronizationService` — list which public methods have tests and which do not
- [ ] 1.2 Add integration/unit tests for `synchronize()` (extern-to-intern path) covering: new object, unchanged object (hash match → skip), changed object, rate-limit exception
- [ ] 1.3 Add integration/unit tests for `synchronize()` (intern-to-extern path) covering: create, update, delete mutation types
- [ ] 1.4 Add unit tests for `synchronizeContract()` covering: skip on hash match, skip in test mode, update path
- [ ] 1.5 Add unit tests for `deleteInvalidObjects()` covering: register/schema target type, empty synchronizedTargetIds
- [ ] 1.6 Document every public method's current call sites (already mapped in proposal) in a code comment block at the top of `SynchronizationService.php` to guard against accidental breakage during extraction

---

## 2. Fix Known Bugs & Dead Code First

- [ ] 2.1 Remove the large commented-out switch block in `processFetchFileRule()` (lines ~3202–3240) — it is fully superseded by `startAsyncFileFetching`
- [ ] 2.2 Remove `fetchAllPagesSequential()` — it is dead code superseded by `fetchAllPagesOptimized()`; confirm no call sites exist
- [ ] 2.3 Fix `startAsyncFileFetching()` — it is a no-op wrapper around `executeAsyncFileFetching()`; either implement actual async or inline the call and remove the wrapper method
- [ ] 2.4 Fix `updateTarget()`: remove the dead `isset($synchronization)` guard on line 1738 — `$synchronization` is never set in that scope; the method always needs to fetch it from the mapper
- [ ] 2.5 Fix `synchronizeInternToExtern()`: the duplicate contract-persistence block (lines ~452–490) — both the `if (new contract)` and `else (existing contract)` branches call `synchronizeContract()` identically then call `synchronizationContractMapper->update()` again; consolidate into one path
- [ ] 2.6 Fix `processSynchronizationObject()`: same duplicate `synchronizeContract()` call pattern (lines ~3764–3803, both branches identical) — remove the `// @todo this is weird` duplication and unify into a single call
- [ ] 2.7 Fix `mapHashObject()`: it returns `array|Exception` but never actually throws; change return type to `array` and throw the exception instead of returning it

---

## 3. Extract PaginationService

- [ ] 3.1 Create `lib/Service/PaginationService.php` with constructor injection of `CallService` and `LoggerInterface`
- [ ] 3.2 Move these methods from `SynchronizationService` → `PaginationService`: `fetchAllPages`, `fetchAllPagesOptimized`, `fetchSinglePage`, `fetchSinglePageData`, `getNextPageInfo`, `getNextPage`, `getNextEndpoint`, `getNextlinkFromCall`, `checkRateLimit`, `getRateLimitHeaders`
- [ ] 3.3 Update `getAllObjectsFromApi()` in `SynchronizationService` to call `$this->paginationService->fetchAllPages(...)` instead of calling the now-extracted methods directly
- [ ] 3.4 Register `PaginationService` in `lib/AppInfo/Application.php`
- [ ] 3.5 Write unit tests for `PaginationService::fetchAllPagesOptimized()` covering: single page, multi-page, next-endpoint style, rate limit
- [ ] 3.6 Run `composer check` — verify no regressions

---

## 4. Extract RuleProcessorService

- [ ] 4.1 Create `lib/Service/RuleProcessorService.php` with constructor injection of `MappingService`, `ObjectService`, `StorageService`, `SynchronizationService` (for processSyncRule — see note), `RuleMapper`, `LoggerInterface`, `ContainerInterface`
- [ ] 4.2 Move these methods from `SynchronizationService` → `RuleProcessorService`: `processRules`, `getRuleById`, `checkRuleConditions`, `processErrorRule`, `processMappingRule`, `processSyncRule`, `processSaveObjectRule`, `processExtendInputRule`, `processMapping`, `processMappingRule`
- [ ] 4.3 Move file-rule methods temporarily left in `SynchronizationService` (they will move in step 5); keep `processRules` dispatching to file-rule handlers via an interface or by injecting `SyncFileService` once created
- [ ] 4.4 Note: `processSyncRule` calls `$this->synchronizationService->synchronize()` — after extraction this creates a circular dependency; resolve by injecting `SynchronizationService` lazily via `ContainerInterface` in `RuleProcessorService`, or by moving `processSyncRule` to remain in `SynchronizationService` as a thin wrapper
- [ ] 4.5 Update `synchronizeContract()` in `SynchronizationService` to call `$this->ruleProcessorService->processRules(...)`
- [ ] 4.6 Update `EndpointService` — it has its own `processSyncRule` implementation; check whether it can reuse `RuleProcessorService` or must remain separate
- [ ] 4.7 Register `RuleProcessorService` in `lib/AppInfo/Application.php`
- [ ] 4.8 Write unit tests for `RuleProcessorService::processRules()` covering: no rules, condition check fails, each rule type dispatches correctly, error rule returns JSONResponse
- [ ] 4.9 Run `composer check` — verify no regressions

---

## 5. Extract SyncFileService

- [ ] 5.1 Create `lib/Service/SyncFileService.php` with constructor injection of `CallService`, `StorageService`, `ObjectService`, `SourceMapper`, `LoggerInterface`, `ContainerInterface`
- [ ] 5.2 Move these methods from `SynchronizationService` → `SyncFileService`: `fetchFile`, `writeFile`, `startAsyncFileFetching`, `executeAsyncFileFetching`, `fetchFileSafely`, `cleanupOrphanedFiles`, `processMultipleFilesWithCleanup`, `cleanupFilesFromAttachments`, `getFilenameFromHeaders`, `getFileContext`, `shouldPublishFile`, `generatePlaceholderValues`, `processFetchFileRule`, `processWriteFileRule`
- [ ] 5.3 Inject `SyncFileService` into `RuleProcessorService` so `processRules` can dispatch `fetch_file` and `write_file` rule types
- [ ] 5.4 Register `SyncFileService` in `lib/AppInfo/Application.php`
- [ ] 5.5 Write unit tests for `SyncFileService::fetchFile()` covering: GET with valid response, filename from headers, filename from config, invalid objectId UUID
- [ ] 5.6 Run `composer check` — verify no regressions

---

## 6. Extract ContractIdResolverService

- [ ] 6.1 Create `lib/Service/ContractIdResolverService.php` with constructor injection of `SynchronizationContractMapper`
- [ ] 6.2 Move these methods from `SynchronizationService` → `ContractIdResolverService`: `replaceRelatedOriginIds`, `replaceIdInString`, `updateContractsForSubObjects`, `processSyncContract`, `updateIdsOnSubObjects`, `updateIdOnSubObject`, `isAssociativeArray`
- [ ] 6.3 Update `synchronizeContract()` and `updateTargetOpenRegister()` in `SynchronizationService` to call the new service
- [ ] 6.4 Register `ContractIdResolverService` in `lib/AppInfo/Application.php`
- [ ] 6.5 Write unit tests for `replaceRelatedOriginIds()` covering: leaf replacement, nested associative, indexed array of objects, URL with embedded UUID
- [ ] 6.6 Run `composer check` — verify no regressions

---

## 7. Extract Target Adapters

- [ ] 7.1 Create `lib/Service/TargetAdapter/TargetAdapterInterface.php` with methods `save(SynchronizationContract, Synchronization, array &$targetObject): SynchronizationContract` and `delete(SynchronizationContract, Synchronization): SynchronizationContract`
- [ ] 7.2 Create `lib/Service/TargetAdapter/OpenRegisterTargetAdapter.php` implementing the interface — extracted from `updateTargetOpenRegister()` in `SynchronizationService`
- [ ] 7.3 Create `lib/Service/TargetAdapter/ApiTargetAdapter.php` implementing the interface — extracted from `writeObjectToTarget()` in `SynchronizationService`
- [ ] 7.4 Create `lib/Service/TargetAdapter/TargetAdapterFactory.php` (or inline resolution in `SynchronizationService::updateTarget()`) that returns the correct adapter for a given target type string
- [ ] 7.5 Refactor `updateTarget()` to use the factory/interface instead of the switch + private methods
- [ ] 7.6 Register adapters and factory in `lib/AppInfo/Application.php`
- [ ] 7.7 Write unit tests for each adapter covering: save (new object), save (update), delete
- [ ] 7.8 Run `composer check` — verify no regressions

---

## 8. Config Parsing Deduplication

- [ ] 8.1 Identify all call sites of `$this->callService->applyConfigDot($synchronization->getSourceConfig())` within `SynchronizationService` — there are at least 6 and self-documented TODOs note the problem
- [ ] 8.2 Parse `sourceConfig` once at the top of each public entry method (`synchronize`, `synchronizeContract`, `getAllObjectsFromApi`) and pass the parsed array as a parameter to private methods
- [ ] 8.3 Update all private method signatures to accept `array $sourceConfig` instead of re-parsing — reduces DB + computation overhead per sync run
- [ ] 8.4 Run `composer check` — verify no regressions

---

## 9. Public API Cleanup

- [ ] 9.1 Audit visibility of every method in the slimmed-down `SynchronizationService` — change to `private` anything not called externally (cross-reference the call-site list from task 1.6)
- [ ] 9.2 Make `synchronizeContract()` private — it is only called from within `SynchronizationService`; its public callers (`SynchronizationsController`) should use `synchronize()` instead
- [ ] 9.3 Make `getAllObjectsFromApi()` private — it is only called from `getAllObjectsFromSource()` which is also only called from within `SynchronizationService`
- [ ] 9.4 Make `getAllObjectsFromSource()` private — no external callers found
- [ ] 9.5 Make `getObjectFromSource()` private unless a verified external caller exists
- [ ] 9.6 Make `deleteInvalidObjects()` package-private (move to controller-level call if needed) or keep public but document the contract clearly
- [ ] 9.7 Fix `synchronizeToTarget()` — add missing `FlowToken` parameter so it participates in the same tracing as all other entry points; verify it is not just duplicating `synchronizeInternToExtern()`; consolidate if possible
- [ ] 9.8 Fix all mixed return types: `SynchronizationContract|Exception|array` → throw exceptions instead of returning them; `array|JSONResponse` in `processRules` → throw a typed exception and let callers handle HTTP response mapping
- [ ] 9.9 Run `composer check` — verify no regressions

---

## 10. Utility & Leftover Cleanup

- [ ] 10.1 Move `sortNestedArray()`, `encodeArrayKeys()`, `getArrayType()`, `xmlToArray()`, `calculateMedian()`, `getSlowestStage()`, `calculateEfficiencyRatio()` to appropriate utility classes or dedicated helpers (e.g. `ArrayUtils`, `XmlUtils`, `TimingUtils`) — these have no business dependency on synchronization
- [ ] 10.2 Move `hashObject()` and `sortNestedArray()` (used for hashing) to `ContractIdResolverService` or a `HashingService` since they are part of change-detection
- [ ] 10.3 Remove the dead `fetchAllPagesSequential()` method confirmed in task 2.2
- [ ] 10.4 Remove all `error_log()` calls in `SynchronizationService` and extracted services — replace with `$this->logger->error()`
- [ ] 10.5 Remove all `// @todo` comments that are addressed by this refactor; add new `@todo` for anything deferred

---

## 11. Final Verification

- [ ] 11.1 Verify `SynchronizationService` is under 1000 lines after all extractions
- [ ] 11.2 Run the full test suite: `composer test:all`
- [ ] 11.3 Run `composer check:strict` — all quality gates must pass
- [ ] 11.4 Run a manual smoke test: trigger one extern-to-intern sync via cron, one intern-to-extern via event listener, one via endpoint rule
- [ ] 11.5 Verify no new `ContainerInterface->get()` calls have been added (they indicate a DI problem; prefer constructor injection)
- [ ] 11.6 Update `CLAUDE.md` Key Services section to list the new services
