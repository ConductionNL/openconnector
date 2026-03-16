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
