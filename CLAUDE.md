# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

OpenConnector is a **Nextcloud application** that acts as an Enterprise Service Bus (ESB) and API Gateway. It enables data integration between external systems via REST, SOAP, and XML APIs. It runs entirely within the Nextcloud framework and cannot be run as a standalone PHP application.

## Commands

### PHP (Backend)

```bash
composer lint           # PHP syntax check
composer cs:check       # PHPCS code style check
composer cs:fix         # Auto-fix code style issues
composer phpmd          # Mess detection (code smells)
composer psalm          # Psalm static analysis
composer phpstan        # PHPStan static analysis
composer test:unit      # Run unit tests
composer check          # Quick gate: lint + phpcs + psalm + test:unit
composer check:strict   # Full gate: lint + phpcs + phpmd + psalm + phpstan + test:all
```

Run a single PHPUnit test file or method:
```bash
./vendor/bin/phpunit tests/Unit/Controller/UiControllerTest.php
./vendor/bin/phpunit tests/Unit/Controller/UiControllerTest.php --filter testDashboardReturnsTemplateResponse
```

### Frontend (Vue 2 / Webpack)

```bash
npm run build       # Production build
npm run dev         # Development build
npm run watch       # Watch mode
npm run lint        # ESLint
npm run lint-fix    # Auto-fix ESLint issues
npm run stylelint   # CSS/SCSS linting
npm run test        # Jest unit tests
```

Run a single Jest test:
```bash
npm run test -- src/__tests__/example.test.js
```

### Git Hooks (GrumPHP)

Pre-commit runs: phplint, phpcs, jsonlint, yamllint, composer validation.
Pre-push adds: phpmd, phpunit.

These run automatically. Do not use `--no-verify` unless explicitly instructed.

## Architecture

### Request Flow

```
Vue 2 frontend (src/) → REST API Controllers (lib/Controller/) → Services (lib/Service/) → DB (lib/Db/)
                                                                        ↓
                                                               Guzzle HTTP → External APIs
```

### Core Domain Objects

| Entity | Role |
|--------|------|
| **Source** | External API connection (URL, auth, headers) |
| **Endpoint** | Exposed API path within Nextcloud; acts as reverse proxy or direct handler |
| **Mapping** | Data transformation using Twig expressions and field mapping |
| **Synchronization** | Automated sync flow between a source and a target |
| **SynchronizationContract** | Tracks per-object sync state; holds hash for deduplication |
| **Rule** | Conditional logic applied to endpoints (auth, locking, file handling) |
| **Job / JobLog** | Cron-based scheduling and execution history |
| **Consumer** | Inbound webhook / CloudEvents handler |
| **Event / EventSubscription** | CloudEvents-compliant event definitions |
| **CallLog** | Full HTTP call audit trail |

Each entity has a paired ORM Mapper in `lib/Db/`.

### Key Services

- `CallService` — all outbound HTTP calls via Guzzle
- `MappingService` — Twig-based data transformation
- `SynchronizationService` — orchestrates sync flows and change detection
- `AuthenticationService` / `AuthorizationService` — OAuth 2.0, JWT, API keys, Basic auth
- `SoapService` — SOAP/WSDL integration via php-soap
- `EndpointService` / `EndpointCacheService` — endpoint routing and caching
- `RuleService` — evaluates JSON Logic rules on endpoints
- `JobService` — cron scheduling
- `EventService` — CloudEvents dispatch and subscription

### Frontend Structure (`src/`)

Vue 2 with Pinia state management. Key subdirectories:
- `entities/` — typed data models mirroring backend entities
- `components/` — reusable UI components
- `composables/` — Vue composition API helpers
- `modals/` / `dialogs/` — CRUD dialogs per entity type

### Nextcloud Integration

- App manifest: `appinfo/info.xml`
- Routes: `appinfo/routes.php`
- Migrations: `lib/Migration/` (each file = a schema version)
- Background jobs: `lib/Cron/`
- Admin settings: `lib/Settings/`

## Code Quality Requirements

From `.cursorrules` — apply to all PHP:

- All methods, classes, and properties must have **docblocks**
- All methods must have **return types** and **type hints**
- Add **default values** to all method parameters where applicable
- Annotate with **PHPStan and Psalm** annotations
- Add **PHPUnit tests** for all methods
- Add **inline comments** explaining logic steps
- Use **`readonly` properties** where appropriate

## Tech Stack Summary

| Layer | Technology |
|-------|-----------|
| Backend framework | Nextcloud App Framework (PHP 8.1+) |
| HTTP client | Guzzle 7 |
| Templating/mapping | Twig 3 |
| SOAP | php-soap/ext-soap-engine + PSR-18 transport |
| JWT/auth | web-token/jwt-framework |
| Conditional logic | jwadhams/json-logic-php |
| Async fetching | ReactPHP |
| Frontend framework | Vue 2.7 + Pinia |
| UI components | @nextcloud/vue, Bootstrap Vue |
| Build | Webpack 5 + Babel |
| Testing (PHP) | PHPUnit |
| Testing (JS) | Jest 29 + @vue/test-utils |
| Static analysis | Psalm + PHPStan |
| DB | PostgreSQL / MySQL 8.0+ / SQLite (via Nextcloud ORM) |

## Requirements

- Nextcloud 28–33
- PHP 8.1+
- Node ^20.0.0, NPM ^10.0.0
