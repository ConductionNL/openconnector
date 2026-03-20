## 1. Extract MetricsService

- [x] 1.1 Create `lib/Service/MetricsService.php` with `collect(): array` method, moving all database query logic from MetricsController
- [x] 1.2 Refactor MetricsController::index() to inject MetricsService and delegate collection, keeping only HTTP formatting
- [x] 1.3 Add `#[AuthorizedAdminSetting(Admin::class)]` annotation to MetricsController::index()

## 2. Add Missing Metrics

- [x] 2.1 Add endpoint count metric (`openconnector_endpoints_total`) to MetricsService — query `openconnector_endpoints` table
- [x] 2.2 Add job metrics (`openconnector_jobs_total`, `openconnector_job_runs_total{status}`) — query `openconnector_jobs` and `openconnector_job_logs` tables
- [x] 2.3 Add mapping and rule count metrics (`openconnector_mappings_total`, `openconnector_rules_total`) — query respective tables

## 3. Unit Tests

- [x] 3.1 Create `tests/Unit/Service/MetricsServiceTest.php` — test collect() with mocked IDBConnection returning known counts
- [x] 3.2 Add test for database error fallback — mock exception for one metric, verify zero-value and other metrics still valid
- [x] 3.3 Create `tests/Unit/Controller/MetricsControllerTest.php` — test index() returns correct content-type and delegates to service
- [x] 3.4 Create `tests/Unit/Controller/HealthControllerTest.php` — test index() returns correct JSON structure for ok/degraded/error states

## 4. API Tests

- [x] 4.1 Create Newman collection `tests/newman/prometheus-metrics.json` — test GET /api/metrics returns 200 with valid Prometheus format
- [x] 4.2 Add Newman test for GET /api/health — validate JSON structure with status and checks fields
- [x] 4.3 Add Newman test for unauthenticated access — verify 401/403 response

## 5. Documentation

- [x] 5.1 Add metrics endpoint documentation to app docs (available metrics, scrape configuration, Grafana example)
