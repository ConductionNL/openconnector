---
status: proposed
---

# iBabs & NotuBiz Connector

## Purpose

Provides bidirectional integration with iBabs and NotuBiz — the two dominant raadsinformatiesystemen (RIS) used by Dutch municipalities for bestuurlijke besluitvorming (B&W/College). Found as a requirement in 20+ tenders: iBabs in 12+ and NotuBiz in 8+. The connector pushes collegevoorstellen and documents from Procest to the RIS for vergaderbehandeling, and receives besluiten and besluitenlijsten back into the zaak. Implements the standard B&W workflow pattern: documenten heen, besluiten terug.

## Requirements

### iBabs API Integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| RIS-001 | Connect to the iBabs REST API with API key authentication | MUST | Planned |
| RIS-002 | Push a collegevoorstel (advies + bijlagen) to iBabs as a vergaderstuk | MUST | Planned |
| RIS-003 | Create or update an agendapunt in iBabs linked to the collegevoorstel | MUST | Planned |
| RIS-004 | Retrieve besluiten from iBabs after vergaderbehandeling | MUST | Planned |
| RIS-005 | Retrieve the besluitenlijst (PDF/document) from iBabs | MUST | Planned |
| RIS-006 | Support iBabs document upload (PDF, DOCX) with metadata (onderwerp, portefeuillehouder, zaaktype) | MUST | Planned |
| RIS-007 | Support iBabs vergadering retrieval: list upcoming and past vergaderingen with agendapunten | SHOULD | Planned |
| RIS-008 | Map iBabs besluit status (aangenomen, verworpen, aangehouden, doorgeschoven) to Procest zaak status updates | MUST | Planned |

### NotuBiz API Integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| RIS-020 | Connect to the NotuBiz API with OAuth2 or API key authentication | MUST | Planned |
| RIS-021 | Push vergaderstukken (voorstel + bijlagen) to NotuBiz | MUST | Planned |
| RIS-022 | Create or update agendapunten in NotuBiz linked to vergaderstukken | MUST | Planned |
| RIS-023 | Retrieve besluiten and besluitenlijst from NotuBiz after behandeling | MUST | Planned |
| RIS-024 | Support NotuBiz event types: collegevergadering, raadsvergadering, commissievergadering | SHOULD | Planned |
| RIS-025 | Map NotuBiz besluit metadata to Procest zaak properties | MUST | Planned |

### Bidirectional Sync

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| RIS-030 | Outbound sync: when a zaak reaches status "Ter besluitvorming" in Procest, automatically push voorstel to configured RIS | MUST | Planned |
| RIS-031 | Inbound sync: poll or webhook for besluit updates from RIS, update the source zaak in Procest | MUST | Planned |
| RIS-032 | Conflict detection: if a zaak has been modified in both Procest and the RIS, flag for manual resolution | SHOULD | Planned |
| RIS-033 | Sync history: log all sync operations (push/pull, timestamp, status, document IDs) as OpenRegister objects for audit trail | MUST | Planned |
| RIS-034 | Retry failed syncs with configurable backoff (default: 3 retries, 5/15/60 minute intervals) | SHOULD | Planned |

### Document Flow

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| RIS-040 | Outbound documents: export from Nextcloud Files, convert to PDF if needed (via Docudesk), push to RIS | MUST | Planned |
| RIS-041 | Inbound documents: download besluit/besluitenlijst from RIS, store in Nextcloud Files, link to zaak | MUST | Planned |
| RIS-042 | Document metadata mapping: onderwerp, datum, portefeuillehouder, zaaktype, geheimhouding | MUST | Planned |
| RIS-043 | Support geheimhouding flag: mark documents as vertrouwelijk in the RIS when the zaak has geheimhouding | SHOULD | Planned |

### Parafering Support (Ambtelijk Deel)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| RIS-050 | Track parafering status within Procest before pushing to RIS: steller > adviseur > parafeerder > portefeuillehouder > secretariaat | MUST | Planned |
| RIS-051 | Only push to RIS after all required paraferingen are completed (configurable per zaaktype) | MUST | Planned |
| RIS-052 | Parafering route is configurable: sequential, parallel, or mixed per zaaktype | SHOULD | Planned |
| RIS-053 | Mobile-friendly parafering: API supports paraferen from any device (responsive UI in Procest) | SHOULD | Planned |

### OpenConnector Integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| RIS-060 | Registered as an OpenConnector endpoint type with separate configurations for iBabs and NotuBiz | MUST | Planned |
| RIS-061 | Connection settings: API URL, authentication credentials, organisatie-ID, default vergadertype | MUST | Planned |
| RIS-062 | Health check: validate API connectivity and authentication | SHOULD | Planned |
| RIS-063 | n8n workflow integration: connector can be triggered from n8n nodes for custom B&W-besluitvorming workflows | SHOULD | Planned |

## Data Model

### Sync Record (stored in OpenRegister)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| zaakId | string (UUID) | Yes | Source zaak in Procest |
| risType | string (enum) | Yes | `ibabs` or `notubiz` |
| risDocumentId | string | No | Document/agendapunt ID in the RIS |
| direction | string (enum) | Yes | `outbound` (push) or `inbound` (pull) |
| status | string (enum) | Yes | `pending`, `synced`, `failed`, `conflict` |
| syncedAt | datetime | No | Timestamp of last successful sync |
| errorMessage | string | No | Error details if status is `failed` |
| documents | array | No | List of document references (Nextcloud file ID + RIS doc ID) |

## Scenarios

### Push collegevoorstel to iBabs

```
GIVEN a zaak "Bestemmingsplan Centrum" has completed parafering in Procest
AND the zaak reaches status "Ter besluitvorming"
WHEN the outbound sync triggers
THEN the voorstel document and bijlagen are exported from Nextcloud
AND pushed to iBabs as vergaderstukken with metadata (onderwerp, portefeuillehouder)
AND an agendapunt is created for the next collegevergadering
AND a sync record is stored with status "synced"
```

### Receive besluit from iBabs

```
GIVEN a collegevoorstel was pushed to iBabs for zaak "Bestemmingsplan Centrum"
AND the college has behandeld the voorstel
WHEN the inbound sync polls iBabs for updates
THEN the besluit (aangenomen/verworpen) is retrieved
AND the besluitenlijst PDF is downloaded and stored in Nextcloud Files
AND the zaak status in Procest is updated to reflect the besluit
AND the besluit document is linked to the zaak
```

### NotuBiz raadsvergadering flow

```
GIVEN a collegevoorstel requires raadsbehandeling after collegebesluit
WHEN the connector pushes stukken to NotuBiz for raadsvergadering
THEN vergaderstukken are uploaded with commissie/raad metadata
AND after raadsbehandeling, the raadsbesluit is synced back to Procest
```

### Failed sync with retry

```
GIVEN a voorstel push to iBabs fails due to API timeout
WHEN the first retry is triggered after 5 minutes
THEN if it succeeds, the sync record is updated to "synced"
AND if all 3 retries fail, the sync record is set to "failed" with error details
AND a notification is sent to the behandelaar
```

## Dependencies

- **OpenConnector**: Endpoint registration and connection management
- **OpenRegister**: Sync record storage and zaak object access
- **Procest**: Zaak lifecycle management and parafering workflow
- **Docudesk**: PDF conversion for outbound documents
- **iBabs REST API**: External service (api.ibabs.eu)
- **NotuBiz API**: External service (api.notubiz.nl)

### Using Mock Register Data

The **ORI** mock register provides test data for developing the iBabs/NotuBiz connector without requiring access to production RIS systems.

**Loading the register:**
```bash
# Load ORI register (115 records, register slug: "ori", schemas: "vergadering", "agendapunt", "raadsdocument", "stemming", "raadslid", "fractie")
docker exec -u www-data nextcloud php occ openregister:load-register /var/www/html/custom_apps/openregister/lib/Settings/ori_register.json
```

**Test data for this spec's use cases:**
- **Vergadering retrieval (RIS-007)**: 10+ vergaderingen with dates and types (raadsvergadering, commissievergadering) -- test sync back to ORI register
- **Agendapunt creation (RIS-003)**: 30+ agendapunten linked to vergaderingen -- test push/pull of agenda items
- **Besluit mapping (RIS-008)**: Stemmingen with aangenomen/verworpen results -- test besluit status mapping
- **Document handling (RIS-006)**: 15+ raadsdocumenten (moties, amendementen, besluiten) -- test document upload/download sync

## Current Implementation Status

### Implemented
- **None of the iBabs/NotuBiz-specific requirements are implemented.** There is no iBabs connector, NotuBiz connector, parafering workflow, or RIS sync mechanism in the codebase.

### Partially relevant existing infrastructure
- **Source entity** (`lib/Db/Source.php`, `src/entities/source/source.types.ts`): Supports source types `json`, `xml`, `soap`, `ftp`, `sftp` with multiple auth methods including `apikey`, `jwt`, `oauth`. Both iBabs (REST + API key) and NotuBiz (OAuth2/API key) could be configured as `json`-type sources with appropriate auth.
- **CallService** (`lib/Service/CallService.php`): Generic HTTP client that handles REST calls to configured sources. Could be used for iBabs/NotuBiz API calls without modification.
- **SynchronizationService** (`lib/Service/SynchronizationService.php`): Full bidirectional sync framework with contracts, logs, and mapping. Supports sync between external sources and OpenRegister objects. This is directly relevant for RIS-030/031 (bidirectional sync).
- **AuthenticationService** (`lib/Service/AuthenticationService.php`): Handles various auth methods. iBabs API key and NotuBiz OAuth2 should be supportable.
- **EndpointService** (`lib/Service/EndpointService.php`): Manages endpoint configuration and routing.
- **JobService** (`lib/Service/JobService.php`): Background job execution — could be used for polling and retry logic (RIS-031, RIS-034).

### Not implemented
- iBabs REST API client (document upload, agendapunt creation, besluit retrieval)
- NotuBiz API client (vergaderstuk upload, agendapunt, besluit retrieval)
- Bidirectional sync triggers (status-based outbound push, polling/webhook inbound)
- Sync record storage (the data model described in the spec)
- Conflict detection (RIS-032)
- Retry with configurable backoff (RIS-034)
- Parafering workflow (RIS-050 through RIS-053) — entirely within Procest scope
- Document flow with PDF conversion via Docudesk
- Geheimhouding flag mapping
- RIS-specific source type registration

## Standards & References

- **iBabs REST API**: Proprietary API by iBabs BV (now part of Meeting.nl). Documented at developer.ibabs.eu. Uses API key authentication, REST/JSON format.
- **NotuBiz API**: Proprietary API by NotuBiz BV (part of CMSolutions). Supports OAuth2 and API key auth. REST/JSON format.
- **Gemeentelijke besluitvormingsprocessen**: The B&W-besluitvorming workflow is standardized across Dutch municipalities: steller > adviseur > parafeerder > portefeuillehouder > secretariaat > collegevergadering > besluit.
- **GEMMA procesarchitectuur**: The reference architecture for Dutch municipal decision-making processes.
- **Archiefwet**: Dutch archiving law — besluitenlijsten and vergaderstukken must be archived according to selectielijsten.

## Specificity Assessment

### Sufficient for implementation
- The sync record data model is well-defined.
- The document flow direction (outbound voorstel, inbound besluit) is clear.
- Scenarios cover the main happy path and error/retry cases.
- Parafering route requirements are specific (sequential, parallel, mixed).

### Missing or ambiguous
- **iBabs API version**: No specific API version is mentioned. iBabs has multiple API generations.
- **NotuBiz API version**: Similarly unspecified. NotuBiz API access may require a specific contract/license.
- **Webhook vs polling**: RIS-031 says "poll or webhook" but doesn't specify which is preferred or what the polling interval should be.
- **Vergadering selection**: RIS-003 says "create agendapunt" but doesn't specify how the target vergadering is selected (next upcoming? manual selection? configurable default?).
- **Document format requirements**: RIS-006 mentions PDF/DOCX but iBabs may require specific metadata fields or format constraints not documented here.
- **Parafering scope ambiguity**: RIS-050-053 describe parafering within Procest, but the spec is for the OpenConnector adapter. The boundary between Procest and OpenConnector is unclear.
- **Multi-tenant**: Can multiple iBabs/NotuBiz connections be configured simultaneously (e.g., different vergadertypen mapped to different RIS instances)?
- **Besluit status mapping**: RIS-008 lists status values (aangenomen, verworpen, aangehouden, doorgeschoven) but doesn't define the target Procest zaak statussen.

### Open questions
1. Are iBabs and NotuBiz API access agreements/licenses in place? Both are proprietary APIs with access restrictions.
2. Should parafering logic live in Procest (as a zaak workflow) or in OpenConnector (as a sync prerequisite)? The spec mixes both.
3. What is the polling interval for inbound besluit sync? Is a webhook option available from either RIS?
4. How is the target vergadering selected when pushing a collegevoorstel? Manual or automatic?
5. Is there a test/sandbox environment available for both iBabs and NotuBiz APIs?
