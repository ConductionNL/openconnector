---
status: proposed
---

# DSO / Omgevingsloket Adapter

## Purpose

Provides integration with the Digitaal Stelsel Omgevingswet (DSO) Landelijke Voorziening for receiving and processing vergunningaanvragen, meldingen, and informatieverzoeken from the Omgevingsloket. Required by 32% of tenders (all VTH-related). The adapter receives DSO-verzoeken via the STAM koppelvlak, parses them into zaak objects in Procest, maps activiteiten to zaaktypen, and supports samenwerking met bevoegd gezag via DSO-SWF (SamenWerkingsFunctionaliteit). Replaces the legacy OLO (Omgevingsloket Online) integration.

## Requirements

### DSO-LV Inbound (Receive Verzoeken)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DSO-001 | Receive vergunningaanvragen from DSO-LV via the STAM (STAndaard Machtiging) koppelvlak REST API | MUST | Planned |
| DSO-002 | Receive meldingen (activiteiten waarvoor geen vergunning nodig is) from DSO-LV | MUST | Planned |
| DSO-003 | Receive informatieverzoeken and vooroverleg-aanvragen from DSO-LV | SHOULD | Planned |
| DSO-004 | Parse the DSO-verzoek XML/JSON payload into structured data: aanvrager, locatie, activiteiten, bijlagen, projectbeschrijving | MUST | Planned |
| DSO-005 | Download bijlagen (documenten, tekeningen, rapporten) from DSO-LV and store in Nextcloud Files | MUST | Planned |
| DSO-006 | Validate the received verzoek against DSO-LV schema and reject malformed requests with descriptive errors | MUST | Planned |

### Activiteiten-to-Zaaktype Mapping

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DSO-010 | Map DSO activiteiten (e.g., bouwen, milieu, kappen, uitrit) to Procest zaaktypen via configurable mapping table | MUST | Planned |
| DSO-011 | Support samenloop: one DSO-verzoek with multiple activiteiten can result in multiple zaak objects or one zaak with multiple deelzaken | MUST | Planned |
| DSO-012 | Default mapping configuration for common Omgevingswet activiteiten is pre-seeded | MUST | Planned |
| DSO-013 | Unmapped activiteiten create a zaak with a generic "Onbekend DSO-activiteit" zaaktype and flag for manual triage | MUST | Planned |
| DSO-014 | Mapping table is editable via the OpenConnector admin UI | SHOULD | Planned |

### Zaak Creation

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DSO-020 | Automatically create a zaak in Procest for each received DSO-verzoek | MUST | Planned |
| DSO-021 | Map aanvrager (initiatiefnemer) data to the zaak: BSN/KVK-nummer, naam, adres, contactgegevens | MUST | Planned |
| DSO-022 | Map locatie to the zaak: BAG-adres, kadastrale aanduiding, GML-geometrie (punt of polygoon) | MUST | Planned |
| DSO-023 | Set zaak startdatum to DSO-verzoek indieningsdatum | MUST | Planned |
| DSO-024 | Link downloaded bijlagen to the created zaak | MUST | Planned |
| DSO-025 | Store the original DSO-verzoek reference (verzoekId, bronorganisatie) on the zaak for traceability | MUST | Planned |
| DSO-026 | Extract bouwkosten from DSO-verzoek for legesberekening (if provided by aanvrager) | SHOULD | Planned |

### DSO-SWF (Samenwerking)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DSO-030 | Support samenwerking met bevoegd gezag: when another overheidsorgaan is betrokken bij dezelfde aanvraag, coordinate via DSO-SWF | SHOULD | Planned |
| DSO-031 | Send adviesverzoeken to ketenpartners (provincie, waterschap, omgevingsdienst) via DSO-SWF | SHOULD | Planned |
| DSO-032 | Receive adviezen from ketenpartners and link to the zaak | SHOULD | Planned |
| DSO-033 | Track samenwerkingsstatus per zaak: welke organisaties zijn betrokken, welke adviezen zijn ontvangen | SHOULD | Planned |

### Status Updates (Outbound to DSO-LV)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DSO-040 | Push zaak status updates back to DSO-LV so the aanvrager can track progress via the Omgevingsloket | MUST | Planned |
| DSO-041 | Map Procest zaak statussen to DSO-LV statuscodes: ontvangen, in behandeling, besluit genomen, etc. | MUST | Planned |
| DSO-042 | Push the vergunningbesluit (verleend, geweigerd, buiten behandeling) to DSO-LV | MUST | Planned |
| DSO-043 | Push vergunningdocumenten (beschikking PDF) to DSO-LV for publication | SHOULD | Planned |

### Authentication & Security

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DSO-050 | Authenticate with DSO-LV using PKIoverheid certificates (mTLS) | MUST | Planned |
| DSO-051 | Validate incoming DSO-LV webhook signatures to prevent spoofing | MUST | Planned |
| DSO-052 | Support DSO-LV test environment (pre-productie) alongside production for acceptance testing | SHOULD | Planned |
| DSO-053 | Store DSO API credentials and certificates securely in Nextcloud's credential store | MUST | Planned |

### OpenConnector Integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DSO-060 | Registered as an OpenConnector source type with DSO-LV-specific configuration | MUST | Planned |
| DSO-061 | Connection settings: DSO-LV API URL, PKIoverheid certificates, organisatie OIN, bevoegd-gezag code | MUST | Planned |
| DSO-062 | Health check: validate connectivity and certificate validity against DSO-LV | SHOULD | Planned |
| DSO-063 | n8n workflow integration: DSO-verzoek ontvangst triggers a configurable n8n workflow for intake processing | SHOULD | Planned |

## Data Model

### DSO-Verzoek (stored in OpenRegister before zaak creation)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| verzoekId | string | Yes | DSO-LV unique verzoek identifier |
| bronorganisatie | string | Yes | OIN of the submitting DSO-LV instance |
| type | string (enum) | Yes | `aanvraag`, `melding`, `informatieverzoek`, `vooroverleg` |
| indieningsdatum | datetime | Yes | Date/time of submission in DSO-LV |
| aanvrager | object | Yes | Initiatiefnemer: BSN/KVK, naam, adres, contactgegevens |
| locatie | object | Yes | BAG-adres, kadastrale aanduiding, GML-geometrie |
| activiteiten | array | Yes | List of DSO activiteiten with codes and omschrijvingen |
| bouwkosten | decimal | No | Opgegeven bouwkosten (for legesberekening) |
| bijlagen | array | No | References to downloaded documents in Nextcloud Files |
| zaakId | string (UUID) | No | Created Procest zaak reference (set after processing) |
| status | string (enum) | Yes | `ontvangen`, `verwerkt`, `fout` |

## Scenarios

### Receive vergunningaanvraag from Omgevingsloket

```
GIVEN the DSO-LV adapter is configured with valid PKIoverheid certificates
AND an initiatiefnemer submits a vergunningaanvraag via het Omgevingsloket
WHEN DSO-LV sends the verzoek to our STAM endpoint
THEN the verzoek payload is parsed and validated
AND bijlagen are downloaded and stored in Nextcloud Files
AND activiteiten are mapped to zaaktypen
AND a zaak is created in Procest with aanvrager, locatie, and activiteiten data
AND a status "ontvangen" is pushed back to DSO-LV
```

### Multiple activiteiten with samenloop

```
GIVEN a verzoek contains activiteiten "bouwen" and "kappen"
AND "bouwen" maps to zaaktype "Omgevingsvergunning Bouwen"
AND "kappen" maps to zaaktype "Omgevingsvergunning Kappen"
WHEN the adapter processes the verzoek
THEN two deelzaken are created under one hoofdzaak
AND both share the same aanvrager and locatie
AND each deelzaak follows its own behandelproces
```

### Push besluit to DSO-LV

```
GIVEN a zaak originated from a DSO-verzoek
AND the vergunning is verleend
WHEN the zaak status changes to "Besluit genomen" in Procest
THEN the adapter pushes status "besluit genomen" to DSO-LV
AND the beschikking PDF is uploaded to DSO-LV
AND the aanvrager can view the besluit in het Omgevingsloket
```

### Unknown activiteit fallback

```
GIVEN a verzoek contains an activiteit not in the mapping table
WHEN the adapter processes the verzoek
THEN a zaak is created with zaaktype "Onbekend DSO-activiteit"
AND the zaak is flagged for manual triage
AND a notification is sent to the VTH-behandelaar
```

## Dependencies

- **OpenConnector**: Source registration and connection management
- **OpenRegister**: Verzoek and mapping table storage
- **Procest**: Zaak creation and lifecycle management
- **Docudesk**: PDF generation for beschikkingen pushed to DSO-LV
- **DSO-LV STAM API**: External service (Kadaster/RWS)
- **PKIoverheid certificates**: For mTLS authentication
- **BAG/BRK services**: For locatie-validatie (via OpenConnector)
