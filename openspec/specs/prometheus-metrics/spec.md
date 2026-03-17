# Prometheus Metrics Endpoint

## Purpose
Expose application metrics in Prometheus text exposition format at `GET /api/metrics` for monitoring, alerting, and operational dashboards.

## Requirements

### REQ-PROM-001: Metrics Endpoint
- MUST expose `GET /index.php/apps/openconnector/api/metrics` returning `text/plain; version=0.0.4; charset=utf-8`
- MUST require admin authentication (Nextcloud admin or API token)
- MUST return metrics in Prometheus text exposition format

### REQ-PROM-002: Standard Metrics
Every app MUST expose these standard metrics:
- `openconnector_info` (gauge, labels: version, php_version, nextcloud_version) — always 1
- `openconnector_up` (gauge) — 1 if app is healthy, 0 if degraded
- `openconnector_requests_total` (counter, labels: method, endpoint, status) — HTTP request count
- `openconnector_request_duration_seconds` (histogram, labels: method, endpoint) — request latency
- `openconnector_errors_total` (counter, labels: type) — error count by type

### REQ-PROM-003: App-Specific Metrics
- `openconnector_sources_total` (gauge, labels: type) — total sources by type (rest/soap/graphql)
- `openconnector_calls_total` (counter, labels: source, method, status) — API calls made
- `openconnector_call_duration_seconds` (histogram, labels: source) — call latency
- `openconnector_synchronizations_total` (counter, labels: source, status) — sync operations
- `openconnector_sync_objects_total` (counter, labels: source) — objects synced

### REQ-PROM-004: Health Check
- MUST expose `GET /index.php/apps/openconnector/api/health` returning JSON `{"status": "ok"|"degraded"|"error", "checks": {...}}`
- Checks: database connectivity, required dependencies available, source endpoint reachability

## Current Implementation Status
- **Not implemented**: No MetricsController, HealthController, or metrics/monitoring code exists in the app.

## Standards & References
- Prometheus text exposition format: https://prometheus.io/docs/instrumenting/exposition_formats/
- OpenMetrics specification: https://openmetrics.io/
- Nextcloud server monitoring patterns
- OpenRegister MetricsService and HeartbeatController as reference implementation

## Specificity Assessment
Highly specific — metric names, types, and labels are fully defined. Implementation follows a standard pattern that can be shared via a base MetricsService trait/class from OpenRegister.
