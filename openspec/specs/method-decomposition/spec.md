---
status: draft
priority: high
estimated_effort: large
---

# Method Decomposition — OpenConnector

## Goal
Eliminate 109 PHPMD complexity suppressions by decomposing complex methods into smaller, focused units. Each suppression represents a method or class that exceeds PHPMD's strict thresholds (CC>10, NPath>200, MethodLength>100, ClassLength>1000).

## Current State
- **CyclomaticComplexity suppressions:** 36 (methods with >10 branches)
- **NPathComplexity suppressions:** 24 (methods with >200 execution paths)
- **ExcessiveMethodLength suppressions:** 12 (methods >100 lines)
- **ExcessiveClassComplexity suppressions:** 11 (classes with too much logic)
- **ExcessiveClassLength suppressions:** 1 (classes >1000 lines)
- **CouplingBetweenObjects suppressions:** 25 (too many dependencies)
- **TooManyMethods suppressions:** 0

## Files Requiring Decomposition

### Priority 1 — Highest complexity (files with 5+ suppressions)

**lib/Service/MappingService.php** (6 suppressions)
Data mapping and transformation service executing Twig-based mapping rules, JSONPath expressions, and conditional transformations. Class-level suppressions (6) for coupling, class length, class complexity, CC, NPath, and method length.

**lib/Db/SynchronizationMapper.php** (6 suppressions)
Database mapper for synchronization entities with complex query building, filtering, and join operations. Class-level suppressions (2) for class complexity and coupling. Method-level suppressions on `findAll` (CC+NPath) and `applyFilters` (CC+NPath).

**lib/Service/ConfigurationService.php** (5 suppressions)
Configuration import/export service handling source, mapping, synchronization, and endpoint configurations. Class-level suppressions (5) for coupling, class complexity, CC, NPath, and method length.

**lib/Service/CallService.php** (5 suppressions)
HTTP call service handling REST, GraphQL, and SOAP API calls with authentication, pagination, and error handling. Class-level suppressions (5) for coupling, class complexity, CC, NPath, and method length.

**lib/Controller/EndpointsController.php** (5 suppressions)
Endpoints REST controller managing API endpoint CRUD. Class-level suppressions (5) for coupling, CC, NPath, class complexity, and method length.

### Priority 2 — Medium complexity (files with 2-4 suppressions)

**lib/Service/RuleService.php** (4 suppressions)
Rule execution service applying conditional logic to data flows. Class-level suppressions for coupling, class complexity, CC, and method length.

**lib/Service/JobService.php** (4 suppressions)
Job scheduling and execution service managing background processing. Class-level suppressions for coupling, CC, NPath, and method length.

**lib/Service/ConfigurationHandlers/SynchronizationHandler.php** (4 suppressions)
Handler for importing/exporting synchronization configurations. Class-level suppressions for class complexity, CC, NPath, and method length.

**lib/Db/JobMapper.php** (4 suppressions)
Job database mapper with query building and scheduling logic. Class-level suppression for coupling. Method-level suppressions on `findAll` (CC+NPath) and `findNextJob` (CC).

**lib/Db/EndpointMapper.php** (4 suppressions)
Endpoint database mapper with complex routing and matching queries. Class-level suppressions (2) for class complexity and coupling. Method-level suppressions on `findAll` (CC+NPath).

**lib/Controller/SynchronizationsController.php** (4 suppressions)
Synchronizations REST controller. Class-level suppressions for coupling, CC, NPath, and class complexity.

**lib/Service/UserService.php** (3 suppressions)
User service handling authentication context and permission checks. Class-level suppressions for class complexity, CC, and NPath.

**lib/Service/SearchService.php** (3 suppressions)
Search service with cross-entity query building. Class-level suppressions for class complexity, CC, and NPath.

**lib/Service/SettingsService.php** (3 suppressions)
Settings persistence service. Class-level suppressions for CC, NPath, and method length.

**lib/Controller/SourcesController.php** (3 suppressions)
Sources REST controller. Class-level suppressions for CC, NPath, and method length.

**lib/Controller/MappingsController.php** (3 suppressions)
Mappings REST controller. Class-level suppressions for coupling, CC, and NPath.

**lib/Controller/JobsController.php** (3 suppressions)
Jobs REST controller. Class-level suppressions for coupling, CC, and NPath.

**lib/Migration/Version0Date20240826193657.php** (3 suppressions)
Initial migration with CC, NPath, and method length suppressions.

**lib/Db/SourceMapper.php** (2 suppressions)
Source database mapper with CC and NPath suppressions.

**lib/Db/RuleMapper.php** (2 suppressions)
Rule database mapper with CC and NPath suppressions on `findAll`.

**lib/Db/MappingMapper.php** (2 suppressions)
Mapping database mapper with CC and NPath suppressions on `findAll`.

**lib/Service/SOAPService.php** (2 suppressions)
SOAP API call service with coupling and CC suppressions.

**lib/Service/ConfigurationHandlers/EndpointHandler.php** (2 suppressions)
Endpoint configuration handler with CC and NPath suppressions.

**lib/Controller/UserController.php** (2 suppressions)
User controller with CC and method length suppressions.

**lib/Controller/SynchronizationContractsController.php** (2 suppressions)
Synchronization contracts controller with CC and NPath suppressions.

**lib/Migration/Version1Date20250826103500.php** (2 suppressions)
Migration with CC and NPath suppressions.

### Priority 3 — Single suppressions

- `lib/Twig/MappingRuntime.php` (1) — CouplingBetweenObjects
- `lib/Service/StorageService.php` (1) — CouplingBetweenObjects
- `lib/Service/ObjectService.php` (1) — CouplingBetweenObjects
- `lib/Service/ImportService.php` (1) — CouplingBetweenObjects
- `lib/Service/EventService.php` (1) — CyclomaticComplexity
- `lib/Service/EndpointCacheService.php` (1) — CyclomaticComplexity
- `lib/Service/ConfigurationHandlers/SourceHandler.php` (1) — CyclomaticComplexity
- `lib/Service/ConfigurationHandlers/RuleHandler.php` (1) — CyclomaticComplexity
- `lib/Service/ConfigurationHandlers/JobHandler.php` (1) — CyclomaticComplexity
- `lib/Service/AuthorizationService.php` (1) — CouplingBetweenObjects
- `lib/Service/AuthenticationService.php` (1) — CouplingBetweenObjects
- `lib/Migration/Version1Date20250109093325.php` (1) — ExcessiveMethodLength
- `lib/Http/XMLResponse.php` (1) — CyclomaticComplexity
- `lib/Db/SynchronizationContractMapper.php` (1) — CouplingBetweenObjects
- `lib/Db/Mapping.php` (1) — CyclomaticComplexity
- `lib/Db/JobLogMapper.php` (1) — CouplingBetweenObjects
- `lib/Controller/SettingsController.php` (1) — CouplingBetweenObjects
- `lib/Controller/LogsController.php` (1) — CyclomaticComplexity
- `lib/Controller/EventsController.php` (1) — CouplingBetweenObjects
- `lib/Controller/DashboardController.php` (1) — CouplingBetweenObjects
- `lib/AppInfo/Application.php` (1) — CouplingBetweenObjects

## Decomposition Strategy

### For CyclomaticComplexity (>10 branches)
Extract conditional branches into private helper methods:
- Guard clauses: Extract early-return validation into `validate{Thing}()` methods
- Switch-like logic: Extract case handlers into `handle{Case}()` methods
- Nested conditions: Flatten by extracting inner blocks into descriptive methods

### For NPathComplexity (>200 paths)
Reduce execution paths by:
- Breaking method into pipeline stages (each stage = private method)
- Extracting independent conditional blocks into separate methods
- Using early returns to eliminate nested paths

### For ExcessiveMethodLength (>100 lines)
Split long methods into logical phases:
- Validation phase -> `validate{Input}()`
- Preparation phase -> `prepare{Data}()`
- Processing phase -> `process{Thing}()`
- Response phase -> `build{Response}()`

### For ExcessiveClassComplexity / ExcessiveClassLength
Extract method groups into Handler classes (existing pattern in codebase):
- Create `{ClassName}/{HandlerName}Handler.php`
- Move related methods to the handler
- Inject handler via constructor
- Delegate from original methods (keep public API stable)

### For CouplingBetweenObjects (>13 dependencies)
Reduce constructor parameters by:
- Grouping related dependencies into a single service
- Using lazy loading for rarely-used dependencies
- Moving methods that use specific deps to handler classes

## Testing Strategy

### Before decomposition
1. Run existing unit tests: `docker exec -w /var/www/html/custom_apps/openconnector nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
2. Note any pre-existing failures
3. Run PHPMD to record current suppression count: `./vendor/bin/phpmd lib/ text phpmd.xml 2>&1 | wc -l`

### During decomposition (per method)
1. Verify `php -l` passes on all changed files
2. Run unit tests for the specific class: `--filter ClassName`
3. Run PHPMD on the specific file to confirm suppression can be removed

### After decomposition
1. Full unit test suite passes
2. PHPMD reports 0 violations (no new warnings)
3. Total suppression count reduced by expected amount
4. `composer check:strict` passes
5. Manual smoke test in browser (http://localhost:3000)

## Acceptance Criteria
- [ ] All CyclomaticComplexity suppressions eliminated or reduced to <=5
- [ ] All NPathComplexity suppressions eliminated or reduced to <=5
- [ ] All ExcessiveMethodLength suppressions eliminated or reduced to <=5
- [ ] ExcessiveClassComplexity reduced by extracting handler classes
- [ ] No new PHPMD violations introduced
- [ ] All existing tests continue to pass
- [ ] No behavioral changes (pure refactoring)
