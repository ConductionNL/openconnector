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
