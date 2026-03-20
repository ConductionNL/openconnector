## Why

OpenConnector already has MetricsController and HealthController with routes at `/api/metrics` and `/api/health`. However, the current implementation needs validation against ADR-015 (Per-App Prometheus Metrics) and extension with missing metrics categories (endpoint-level, job queue, mapping/rule metrics). Production monitoring for municipalities requires comprehensive observability beyond basic entity counts.

## What Changes

- Validate existing MetricsController output against Prometheus text exposition format (ADR-015)
- Add endpoint-level metrics (request count, latency per source/endpoint)
- Add job queue metrics (pending, running, failed, completed jobs)
- Add mapping and rule metrics (execution count, error rate)
- Validate HealthController JSON structure against ADR-015 requirements
- Add unit tests for MetricsController and HealthController (ADR-009)
- Add Newman/API tests for metrics and health endpoints

## Capabilities

### New Capabilities
_(none — this is validation and extension of existing functionality)_

### Modified Capabilities
- `prometheus-metrics`: Extending existing metrics with endpoint, job queue, and mapping metrics per ADR-015

## Impact

- **Code**: `lib/Controller/MetricsController.php`, `lib/Controller/HealthController.php`, possibly new `lib/Service/MetricsService.php`
- **Routes**: No changes — `/api/metrics` and `/api/health` already registered
- **Tests**: New unit tests + Newman collection for metrics/health endpoints
- **Dependencies**: None — uses existing Nextcloud and OpenConnector infrastructure
