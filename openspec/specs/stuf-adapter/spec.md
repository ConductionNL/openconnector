---
status: proposed
---

# StUF Adapter

## Purpose

Provides bidirectional translation between modern REST/ZGW APIs and legacy StUF-BG (personen/adressen) and StUF-ZKN (zaken/documenten) SOAP-based interfaces. 79% of Dutch government tenders still require StUF support despite the migration to ZGW APIs. The adapter enables OpenRegister objects to be exposed as StUF services (for legacy consumers) and allows OpenConnector to query legacy StUF sources (for data import). Supports StUF-BG 3.10 and StUF-ZKN 3.10/3.10e.

## Requirements

### StUF-BG Inbound (Legacy Consumer Queries OpenRegister)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| STUF-001 | Expose a SOAP endpoint that accepts StUF-BG 3.10 `npsLv01` (persoon opvragen) requests | MUST | Planned |
| STUF-002 | Map StUF-BG person fields (`bsn`, `geslachtsnaam`, `voorvoegsel`, `voornamen`, `geboortedatum`, `verblijfsadres`) to OpenRegister object properties | MUST | Planned |
| STUF-003 | Expose `npsLa01` (persoon antwoord) response with correctly formed StUF-BG XML | MUST | Planned |
| STUF-004 | Support StUF-BG `adrLv01` (adres opvragen) and `adrLa01` (adres antwoord) for BAG-adressen | SHOULD | Planned |
| STUF-005 | Support `scope` element filtering — return only requested fields in the response | MUST | Planned |
| STUF-006 | Handle StUF `sortering` and `maximumAantal` parameters for result limiting | SHOULD | Planned |

### StUF-BG Outbound (OpenConnector Queries Legacy Source)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| STUF-010 | Query external StUF-BG services via SOAP and map responses to OpenRegister objects | MUST | Planned |
| STUF-011 | Support certificate-based mutual TLS authentication (PKIoverheid) for StUF endpoints | MUST | Planned |
| STUF-012 | Support WS-Security (UsernameToken) authentication for StUF endpoints | MUST | Planned |
| STUF-013 | Parse StUF-BG `npsLa01` responses and extract person/address data into flat JSON | MUST | Planned |
| STUF-014 | Handle StUF `Fo01`/`Fo02` fault messages and map to HTTP error responses with diagnostic info | MUST | Planned |

### StUF-ZKN Inbound (Legacy Consumer Manages Zaken)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| STUF-020 | Expose a SOAP endpoint that accepts StUF-ZKN 3.10 `zakLk01` (zaak aanmaken/bijwerken) messages | MUST | Planned |
| STUF-021 | Map StUF-ZKN zaak fields (`zaakidentificatie`, `omschrijving`, `startdatum`, `einddatum`, `zaaktype`, `status`) to Procest zaak objects in OpenRegister | MUST | Planned |
| STUF-022 | Support `edcLk01` (document koppelen aan zaak) for document management via StUF-ZKN | SHOULD | Planned |
| STUF-023 | Support `zakLv01` (zaak opvragen) and respond with `zakLa01` including related documenten and statussen | MUST | Planned |
| STUF-024 | Handle `Bv03` (bevestiging) and `Fo03` (foutmelding) asynchronous response patterns | SHOULD | Planned |

### StUF-ZKN Outbound (OpenConnector Queries Legacy Zaaksysteem)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| STUF-030 | Query external StUF-ZKN services for zaak data and map to OpenRegister objects | MUST | Planned |
| STUF-031 | Support `genereerZaakIdentificatie` for obtaining zaak IDs from legacy systems | SHOULD | Planned |
| STUF-032 | Support document retrieval via `edcLv01` and store in Nextcloud Files | SHOULD | Planned |

### SOAP/XML Processing

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| STUF-040 | WSDL files for StUF-BG 3.10 and StUF-ZKN 3.10 are bundled with the adapter | MUST | Planned |
| STUF-041 | XML namespace handling for `StUF`, `BG`, `ZKN`, `xsi`, `gml` namespaces | MUST | Planned |
| STUF-042 | StUF `stuurgegevens` (zender, ontvanger, referentienummer, tijdstip) correctly populated on all messages | MUST | Planned |
| STUF-043 | StUF `noValue` attribute handling: `geenWaarde`, `nietOndersteund`, `waardeOnbekend`, `vastgesteldOnbekend` | MUST | Planned |
| STUF-044 | XML schema validation of outbound messages against StUF XSD schemas | SHOULD | Planned |

### Field Mapping Configuration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| STUF-050 | Field mappings between StUF XML paths and OpenRegister object properties are configurable via mapping objects stored in OpenRegister | MUST | Planned |
| STUF-051 | Default mapping configurations for BRP-personen (StUF-BG) and ZGW-zaken (StUF-ZKN) are pre-seeded | MUST | Planned |
| STUF-052 | Custom mappings can be added for municipality-specific StUF extensions | SHOULD | Planned |
| STUF-053 | Mapping supports value transformations: date format conversion (StUF `YYYYMMDD` to ISO 8601), code list lookups, string concatenation | MUST | Planned |

### OpenConnector Integration

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| STUF-060 | The adapter is registered as an OpenConnector source type, configurable via the connector UI | MUST | Planned |
| STUF-061 | Connection settings: endpoint URL, authentication method (mTLS/WS-Security), certificates, zender/ontvanger codes | MUST | Planned |
| STUF-062 | Health check: validate connectivity and authentication against the StUF endpoint | SHOULD | Planned |

## Scenarios

### Query BRP via StUF-BG

```
GIVEN an external StUF-BG service is configured in OpenConnector
WHEN a user or workflow requests person data by BSN
THEN OpenConnector sends a StUF-BG npsLv01 SOAP request
AND parses the npsLa01 response
AND returns a JSON object with mapped person fields
```

### Legacy system queries zaak via StUF-ZKN

```
GIVEN a legacy application sends a StUF-ZKN zakLv01 SOAP request
WHEN the adapter receives the request at the SOAP endpoint
THEN it resolves the zaak from OpenRegister by zaakidentificatie
AND returns a zakLa01 response with zaak data, statussen, and documenten
AND stuurgegevens are correctly populated with the adapter's zender code
```

### Create zaak from StUF-ZKN message

```
GIVEN a legacy formulierensysteem sends a StUF-ZKN zakLk01 message
WHEN the adapter receives the create-zaak message
THEN it maps the StUF fields to OpenRegister properties
AND creates a zaak object in Procest's register
AND returns a Bv03 bevestiging message
```

### Certificate-based authentication

```
GIVEN a StUF endpoint requires PKIoverheid mTLS
WHEN the connection is configured with client certificate and key
THEN SOAP requests include the client certificate
AND the server's certificate is validated against the PKIoverheid chain
```

## Dependencies

- **OpenConnector**: Source/endpoint registration and connection management
- **OpenRegister**: Object storage and field mapping configuration
- **PHP SOAP extension**: SOAP client/server functionality
- **PKIoverheid root certificates**: For mTLS validation
- **StUF-BG 3.10 and StUF-ZKN 3.10 XSD schemas**: For XML validation

### Using Mock Register Data

The **BRP** and **BAG** mock registers provide test data for StUF-BG person/address queries without requiring external government endpoints.

**Loading the registers:**
```bash
# Load BRP register (35 persons, register slug: "brp", schema: "ingeschreven-persoon")
docker exec -u www-data nextcloud php occ openregister:load-register /var/www/html/custom_apps/openregister/lib/Settings/brp_register.json

# Load BAG register (32 addresses, register slug: "bag", schema: "nummeraanduiding")
docker exec -u www-data nextcloud php occ openregister:load-register /var/www/html/custom_apps/openregister/lib/Settings/bag_register.json
```

**Test data for this spec's use cases:**
- **StUF-BG npsLv01/npsLa01**: BSN `999993653` (Suzanne Moulin) -- test person query and response mapping
- **StUF-BG adrLv01/adrLa01**: Use BAG `nummeraanduiding` records -- test address query and response mapping
- **Field mapping validation**: BRP records include all fields from the StUF-BG mapping table (bsn, geslachtsnaam, voorvoegsel, voornamen, geboortedatum, verblijfsadres)

## Current Implementation Status

### Implemented (partial)
- **SOAP engine** (`lib/Service/SOAPService.php`): A working generic SOAP client that supports WSDL-driven requests, SOAP 1.1/1.2, cookie jar management, and XML response parsing. This is the outbound foundation (STUF-010/030).
- **edcLk01 handling** (`lib/Service/SOAPService.php`, lines 218-223): There is **specific StUF-ZKN code** — the SOAPService already handles `edcLk01` document messages by detecting `body['edcLk01']['object']['inhoud']` and base64-decoding the document content. This directly relates to STUF-022 (document koppelen).
- **Source type `soap`** (`src/entities/source/source.types.ts`): Sources can be configured as type `soap` with WSDL URL, SOAP version, and authentication. StUF endpoints can be set up as SOAP sources today.
- **CallService SOAP routing** (`lib/Service/CallService.php`, line ~448): When a source has type `soap`, calls are automatically routed to the SOAPService.
- **Certificate handling** (`lib/Service/CallService.php`): Supports writing client certificates and SSL keys to disk for mTLS connections. This is directly relevant for PKIoverheid mTLS (STUF-011).
- **AuthenticationService** (`lib/Service/AuthenticationService.php`): Has certificate and authentication handling that could support WS-Security (STUF-012).

### Not implemented
- **Inbound SOAP server** (STUF-001, STUF-020, STUF-023): No SOAP server endpoint exists. The current SOAPService is client-only (outbound). Exposing StUF-BG/ZKN endpoints as a SOAP server requires a fundamentally different architecture.
- **StUF-BG field mapping** (STUF-002, STUF-003): No mapping between StUF-BG XML paths (`bsn`, `geslachtsnaam`, etc.) and OpenRegister object properties.
- **StUF-ZKN field mapping** (STUF-021): No mapping between StUF-ZKN zaak fields and Procest/OpenRegister objects.
- **WSDL files bundled** (STUF-040): No StUF-BG or StUF-ZKN WSDL/XSD files are included in the codebase.
- **XML namespace handling** (STUF-041): No StUF-specific namespace management (StUF, BG, ZKN, xsi, gml).
- **Stuurgegevens** (STUF-042): No automatic population of zender/ontvanger/referentienummer/tijdstip.
- **noValue attribute handling** (STUF-043): No support for StUF noValue semantics.
- **Configurable field mapping** (STUF-050-053): No mapping configuration UI or storage in OpenRegister.
- **Scope filtering** (STUF-005): Not implemented.
- **Fault message handling** (STUF-014, STUF-024): No Fo01/Fo02/Fo03 or Bv03 handling.
- **WS-Security UsernameToken** (STUF-012): Not implemented as a specific auth method.

### Summary
The outbound SOAP client infrastructure is in place and already has one piece of StUF-ZKN awareness (edcLk01 document handling). The inbound SOAP server side is entirely missing and represents the larger implementation effort.

## Standards & References

- **StUF-BG 3.10**: Standaard Uitwisseling Formaat - Basisgegevens. SOAP-based standard for person and address data exchange in Dutch government. Maintained by VNG Realisatie.
- **StUF-ZKN 3.10 / 3.10e**: Standaard Uitwisseling Formaat - Zaak-/Documentservices. SOAP-based standard for case and document management exchange. The "e" extension adds extra message types.
- **ZGW APIs (Zaakgericht Werken)**: The modern REST-based successor to StUF-ZKN. This adapter bridges the gap between legacy StUF and modern ZGW.
- **WS-Security**: OASIS standard for SOAP message security. UsernameToken profile is commonly used by Dutch government StUF endpoints.
- **PKIoverheid**: Dutch government PKI for mTLS authentication. Required for most production StUF endpoints.
- **GEMMA**: Reference architecture for Dutch municipalities — defines the role of StUF in the information architecture.
- **BRP (Basisregistratie Personen)**: National person registry, accessed via StUF-BG by municipalities.
- **RGBZ (Referentiemodel Gemeentelijke Basisgegevens Zaken)**: The information model underlying StUF-ZKN.
- **CMIS**: Content Management Interoperability Services — sometimes used alongside StUF-ZKN for document management.

## Specificity Assessment

### Sufficient for implementation
- StUF message types are well-known and standardized (npsLv01, npsLa01, zakLk01, etc.).
- The requirement IDs clearly separate inbound/outbound and BG/ZKN concerns.
- The scenarios cover the main integration patterns (query BRP, expose zaken, create zaken, certificate auth).
- The edcLk01 handling already in the code proves the pattern works.

### Missing or ambiguous
- **SOAP server architecture**: How to expose inbound SOAP endpoints within Nextcloud is a significant architectural question. Nextcloud routes are REST-based. Running a SOAP server may require a separate endpoint or a raw POST handler that processes SOAP XML.
- **StUF version specifics**: The spec says "3.10" but doesn't address version negotiation. Some municipalities run 3.01 or custom extensions.
- **Performance requirements**: No mention of expected throughput, response time SLAs, or concurrent request handling.
- **Mapping storage format**: STUF-050 says "configurable via mapping objects stored in OpenRegister" but doesn't define the mapping object schema (which register, which schema, what fields).
- **Pre-seeded mappings scope**: STUF-051 says "default mapping configurations" but doesn't list which specific fields are included in the default BRP and ZGW mappings.
- **Asynchronous patterns**: STUF-024 mentions Bv03/Fo03 async patterns but doesn't detail the callback mechanism (how does the adapter receive async responses?).
- **Multi-source routing**: Can the adapter expose multiple StUF endpoints for different registers/schemas, or is it one global SOAP endpoint?

### Open questions
1. How should the inbound SOAP server be hosted within Nextcloud? As a regular route that parses raw SOAP XML, or as a separate PHP SOAP server process?
2. Which StUF-BG and StUF-ZKN WSDL/XSD files should be bundled? Where are the official schema packages obtained?
3. Should the adapter support StUF-BG 3.01 (still in use by some municipalities) alongside 3.10?
4. What is the expected mapping object schema in OpenRegister for field mappings (STUF-050)?
5. How does WS-Security UsernameToken integrate with the existing AuthenticationService — as a new auth type, or as middleware on the SOAP transport?
