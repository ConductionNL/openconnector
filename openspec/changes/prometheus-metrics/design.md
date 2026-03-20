## Context

OpenConnector already has `MetricsController` and `HealthController` registered at `/api/metrics` and `/api/health`. The current implementation provides basic entity count metrics. ADR-015 requires all Conduction apps to expose standardized Prometheus metrics and health checks. This change validates the existing implementation and extends it with endpoint-level, job queue, and mapping/rule metrics.

## Goals / Non-Goals

**Goals:**
- Validate existing MetricsController output format against Prometheus text exposition spec
- Add endpoint-level metrics (per-source request counts, latency)
- Add job queue metrics (pending/running/failed/completed)
- Add mapping and rule execution metrics
- Validate HealthController JSON output against ADR-015
- Add unit tests for both controllers
- Add Newman API tests

**Non-Goals:**
- Grafana dashboard templates (future work)
- Alerting rule definitions (operational concern, not app code)
- Custom metric registration API (not needed for OpenConnector's scope)

## Decisions

### 1. Extract metrics collection to MetricsService

**Decision**: Move metric collection logic from MetricsController into a dedicated `MetricsService` following ADR-008 (Controller → Service → Mapper pattern).

**Rationale**: The controller should only handle HTTP concerns. MetricsService can be unit-tested independently and reused by health checks.

**Alternative considered**: Keep logic in controller — rejected because it violates ADR-008 and makes testing harder.

### 2. Use database COUNT queries, not in-memory aggregation

**Decision**: All entity count metrics use indexed `SELECT COUNT(*)` queries via existing mappers.

**Rationale**: OpenConnector tables have manageable row counts. COUNT queries on indexed tables complete in <10ms. No need for pre-computed counters or caching.

### 3. Endpoint metrics via middleware timing

**Decision**: Track per-endpoint request count and latency using a simple array counter in MetricsService, populated by the existing request lifecycle.

**Rationale**: OpenConnector's `CallService` already tracks call results. Metrics can aggregate from `CallLog` table rather than adding middleware.

## Risks / Trade-offs

- **[Risk] Database load from frequent scraping** → Mitigation: COUNT queries are cheap; Prometheus default 15s scrape interval is acceptable. Add query timeout of 500ms.
- **[Risk] Metric cardinality explosion from per-source labels** → Mitigation: Cap source labels to source ID only (not full URL). OpenConnector typically has <100 sources.
