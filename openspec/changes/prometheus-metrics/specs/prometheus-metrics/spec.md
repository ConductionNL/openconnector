## MODIFIED Requirements

### REQ-PROM-001: Metrics Endpoint

The app MUST expose `GET /index.php/apps/openconnector/api/metrics` returning `text/plain; version=0.0.4; charset=utf-8`. The endpoint MUST require admin authentication via `#[AuthorizedAdminSetting(Admin::class)]` annotation. Metric collection logic MUST be extracted to `MetricsService` following ADR-008 (Controller → Service pattern). All metrics MUST follow the Prometheus text exposition format with `# HELP`, `# TYPE`, and metric lines.

#### Scenario: Controller delegates to MetricsService
- **WHEN** the MetricsController::index() method is called
- **THEN** it delegates to MetricsService::collect() and formats the result as text/plain

#### Scenario: Admin-only access enforced
- **WHEN** a non-admin user requests the metrics endpoint
- **THEN** the response is HTTP 403 Forbidden

### REQ-PROM-007: Endpoint Metrics

The app MUST expose `openconnector_endpoints_total` (gauge) counting registered endpoints. Endpoint hit tracking (`openconnector_endpoint_hits_total`) MUST be deferred to a future change as it requires CallLog schema changes. The gauge count MUST query `openconnector_endpoints` table directly.

#### Scenario: Endpoint count metric
- **WHEN** 15 endpoints are registered
- **THEN** the metrics output includes `openconnector_endpoints_total 15`

#### Scenario: Zero endpoints
- **WHEN** no endpoints are configured
- **THEN** the metrics output includes `openconnector_endpoints_total 0`

### REQ-PROM-008: Job Queue Metrics

The app MUST expose `openconnector_jobs_total` (gauge) counting configured jobs, and `openconnector_job_runs_total` (counter with label `status`) counting job log entries grouped by result. MetricsService MUST query `openconnector_jobs` and `openconnector_job_logs` tables.

#### Scenario: Job metrics with runs
- **WHEN** 5 jobs are configured and 100 success + 10 error runs logged
- **THEN** the output includes `openconnector_jobs_total 5`, `openconnector_job_runs_total{status="success"} 100`, `openconnector_job_runs_total{status="error"} 10`

#### Scenario: No jobs configured
- **WHEN** no jobs exist
- **THEN** `openconnector_jobs_total 0` is emitted

### REQ-PROM-009: Mapping and Rule Metrics

The app MUST expose `openconnector_mappings_total` (gauge) and `openconnector_rules_total` (gauge). MetricsService MUST query `openconnector_mappings` and `openconnector_rules` tables with error fallback to zero.

#### Scenario: Mapping and rule counts
- **WHEN** 20 mappings and 8 rules exist
- **THEN** the output includes `openconnector_mappings_total 20` and `openconnector_rules_total 8`

#### Scenario: Database error fallback
- **WHEN** the mappings table query fails
- **THEN** `openconnector_mappings_total 0` is emitted with a warning logged

## ADDED Requirements

### Requirement: MetricsService extraction
The metrics collection logic MUST be extracted from MetricsController into a dedicated `lib/Service/MetricsService.php` class per ADR-008. The service MUST expose a `collect(): array` method returning metric name-value pairs. The controller MUST only handle HTTP formatting.

#### Scenario: Service is injectable
- **WHEN** MetricsController is instantiated via DI
- **THEN** MetricsService is injected and used for all metric collection

#### Scenario: Service is unit-testable
- **WHEN** MetricsService::collect() is called with mocked database connection
- **THEN** it returns the expected metric array without requiring a running Nextcloud instance

### Requirement: Unit test coverage for metrics
The MetricsController and MetricsService MUST have unit tests per ADR-009. Tests MUST cover: successful metrics output format, admin auth enforcement, database error fallback, and zero-value scenarios.

#### Scenario: Unit test for MetricsService::collect()
- **WHEN** the unit test mocks IDBConnection with known counts
- **THEN** MetricsService::collect() returns the expected metric array

#### Scenario: Unit test for error fallback
- **WHEN** the unit test mocks a database exception for one metric
- **THEN** MetricsService::collect() returns zero for that metric and valid values for others

### Requirement: Newman API test for metrics endpoint
A Newman collection MUST test the `/api/metrics` and `/api/health` endpoints per ADR-009. Tests MUST verify: HTTP 200 response, correct content-type, presence of required metrics, and health check JSON structure.

#### Scenario: Newman validates metrics format
- **WHEN** the Newman collection runs against a live OpenConnector instance
- **THEN** all metric lines match the pattern `^[a-z_]+(\{[^}]*\})? [0-9.]+$`

#### Scenario: Newman validates health check
- **WHEN** the Newman collection requests /api/health
- **THEN** the response has status field ("ok", "degraded", or "error") and checks object
