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

### Using Mock Register Data

The **DSO** mock register provides test data for developing the DSO adapter without requiring access to the DSO-LV production/test environment.

**Loading the register:**
```bash
# Load DSO register (53 records, register slug: "dso", schemas: "activiteit", "locatie", "omgevingsdocument", "vergunningaanvraag")
docker exec -u www-data nextcloud php occ openregister:load-register /var/www/html/custom_apps/openregister/lib/Settings/dso_register.json
```

**Test data for this spec's use cases:**
- **Activiteiten-to-zaaktype mapping (DSO-010)**: 20+ activiteit records (bouwen, kappen, uitrit aanleggen, etc.) -- test mapping configuration
- **Vergunningaanvraag parsing (DSO-004)**: 10+ vergunningaanvraag records with activiteiten, locatie, and aanvrager data
- **Samenloop testing (DSO-011)**: Vergunningaanvragen referencing multiple activiteiten -- test single-zaak vs multi-deelzaak creation

## Current Implementation Status

### Implemented
- **None of the DSO-specific requirements are implemented.** There is no DSO adapter, STAM endpoint, activiteiten-mapping, or DSO-SWF integration in the codebase.

### Partially relevant existing infrastructure
- **SOAP engine** (`lib/Service/SOAPService.php`): A generic SOAP client exists that can call SOAP sources using WSDL, Guzzle HTTP, and the `php-soap` extension. It already handles SOAP 1.1/1.2, cookie management, WSDL caching, and binary data encoding. This could serve as a foundation for DSO-LV STAM SOAP communication.
- **Source entity** (`lib/Db/Source.php`, `src/entities/source/source.types.ts`): Sources support types `json`, `xml`, `soap`, `ftp`, `sftp` with configurable authentication (`apikey`, `jwt`, `username-password`, `oauth`, etc.). A new `dso` source type would need to be added.
- **CallService** (`lib/Service/CallService.php`): Routes SOAP-type sources to the SOAPService (line ~448). Already supports certificate file writing to disk for mTLS connections.
- **SynchronizationService** (`lib/Service/SynchronizationService.php`): Full sync framework with contracts, logging, and mapping between external and internal objects. Could be leveraged for DSO-verzoek sync.
- **AuthenticationService** (`lib/Service/AuthenticationService.php`): Has certificate handling logic that could be extended for PKIoverheid mTLS.

### Not implemented
- DSO-LV STAM koppelvlak endpoint (inbound REST/SOAP receiver)
- DSO verzoek parsing (XML/JSON payload to structured data)
- Activiteiten-to-zaaktype mapping table and UI
- Samenloop handling (multiple deelzaken from one verzoek)
- DSO-SWF samenwerking (adviesverzoeken, adviezen)
- Status push back to DSO-LV (outbound)
- PKIoverheid certificate validation chain
- DSO-LV webhook signature verification
- DSO-specific source type registration
- Bijlagen download and Nextcloud Files storage
- All zaak creation logic (depends on Procest)

## Standards & References

- **DSO-LV STAM koppelvlak**: REST API specification maintained by Kadaster/RWS for the Digitaal Stelsel Omgevingswet. Defines the verzoek intake interface.
- **Omgevingswet (2024)**: The Dutch Environment and Planning Act that replaced the Wabo/Wro, effective January 1, 2024.
- **DSO-SWF**: SamenWerkingsFunctionaliteit — the collaboration API within the DSO-LV for coordinating between bevoegd gezag and ketenpartners.
- **PKIoverheid**: Dutch government PKI for mutual TLS authentication (PKIO Server 2020 certificate chain).
- **BAG (Basisregistratie Adressen en Gebouwen)**: National address registry, used for locatie-validatie.
- **BRK (Basisregistratie Kadaster)**: Cadastral registry for kadastrale aanduidingen.
- **GML (Geography Markup Language)**: OGC standard for geospatial data encoding, used for locatie geometrie.
- **OIN (Organisatie-Identificatienummer)**: Unique identifier for Dutch government organizations.

## Specificity Assessment

### Sufficient for implementation
- The data model for DSO-Verzoek is well-defined with clear field types.
- Requirements are granular with individual IDs and clear MUST/SHOULD priorities.
- Scenarios cover the main flows (receive, samenloop, besluit push, unknown activiteit).

### Missing or ambiguous
- **STAM API version**: The spec doesn't specify which version of the STAM koppelvlak API to target. The DSO has evolved significantly since its 2024 launch.
- **Authentication flow details**: How PKIoverheid certificates are obtained, renewed, and stored is not specified. The CallService already writes certs to disk — how does this integrate?
- **Webhook vs polling**: DSO-001 says "receive" but doesn't clarify whether this is a webhook (DSO pushes to us) or polling (we poll DSO). The STAM interface is typically push-based but the mechanism needs clarification.
- **Status mapping table**: DSO-041 mentions mapping Procest statussen to DSO statuscodes, but the actual mapping values are not defined.
- **Error handling**: DSO-006 mentions "descriptive errors" but doesn't define error response format (HTTP status codes, error schema).
- **Samenloop strategy**: DSO-011 says "multiple zaak objects or one zaak with multiple deelzaken" — which strategy is preferred? This is a significant architectural decision.
- **Procest dependency**: All zaak creation logic depends on Procest, which is itself under development. The interface between this adapter and Procest is undefined.
- **n8n workflow template**: DSO-063 mentions n8n integration but doesn't specify the workflow structure or trigger mechanism.

### Open questions
1. Which STAM API version and environment (pre-prod/prod) endpoints should be targeted first?
2. Should the adapter support the legacy OLO format during a transition period, or DSO-only?
3. How are PKIoverheid certificates provisioned — uploaded via UI, or configured via Nextcloud admin settings?
4. What is the preferred samenloop strategy: one hoofdzaak with deelzaken, or separate independent zaken?
5. How does the adapter discover which activiteiten mappings exist? Is there a national registry of activiteit codes?
