# Tasks: dso-omgevingsloket

## Task 1: STAM Endpoint Registration (REQ-DSO-001)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-001`
- **files**: `lib/Controller/DSOController.php`, `appinfo/routes.php`
- **acceptance_criteria**:
  - GIVEN DSO adapter endpoint registered WHEN DSO-LV pushes verzoek THEN HTTP 202 returned with verzoekId
  - GIVEN invalid webhook signature WHEN request arrives THEN HTTP 401 returned
  - GIVEN malformed payload WHEN schema validation fails THEN HTTP 400 with field-level errors
- [ ] Implement DSOController with STAM endpoint
- [ ] Register route at /api/dso/stam/verzoeken
- [ ] Add webhook signature validation
- [ ] Add schema validation
- [ ] Test

## Task 2: Verzoek Payload Parsing (REQ-DSO-004)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-004`
- **files**: `lib/Service/DSOParserService.php`
- **acceptance_criteria**:
  - GIVEN verzoek with aanvrager, locatie, activiteiten WHEN parser runs THEN structured data extracted
  - GIVEN GML geometrie WHEN parser runs THEN GeoJSON conversion produced
  - GIVEN version mismatch WHEN parsing THEN auto-detection attempted with warning
- [ ] Implement DSOParserService
- [ ] Add BSN/KVK extraction
- [ ] Add locatie/BAG parsing
- [ ] Add activiteiten parsing
- [ ] Add GML to GeoJSON conversion
- [ ] Test

## Task 3: Verzoek Schema Validation (REQ-DSO-006)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-006`
- **files**: `lib/Service/DSOParserService.php`
- **acceptance_criteria**:
  - GIVEN missing required fields WHEN validation runs THEN descriptive errors returned
  - GIVEN invalid BSN (11-proef fails) WHEN validation runs THEN BSN error returned
- [ ] Implement STAM schema validation
- [ ] Add BSN 11-proef validation
- [ ] Add date format validation
- [ ] Test

## Task 4: Melding and Informatieverzoek Reception (REQ-DSO-002, REQ-DSO-003)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-002`, `#req-dso-003`
- **files**: `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN melding received WHEN processed THEN zaak created with "Melding" zaaktype
  - GIVEN vooroverleg received WHEN processed THEN lightweight zaak created
- [ ] Implement melding handling
- [ ] Implement informatieverzoek handling
- [ ] Implement vooroverleg handling
- [ ] Test

## Task 5: Bijlagen Download and Storage (REQ-DSO-005)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-005`
- **files**: `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN verzoek with bijlagen WHEN processed THEN files downloaded and stored in Nextcloud Files
  - GIVEN download failure WHEN retries exhausted THEN warning flagged on zaak
- [ ] Implement bijlagen download with mTLS
- [ ] Add retry with exponential backoff
- [ ] Add file size limit check
- [ ] Add folder structure creation
- [ ] Test

## Task 6: Activiteiten-to-Zaaktype Mapping (REQ-DSO-010)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-010`
- **files**: `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN mapping table configured WHEN verzoek has activiteit THEN correct zaaktype used
  - GIVEN empty mapping table WHEN admin loads defaults THEN 25+ mappings seeded
- [ ] Implement mapping table lookup
- [ ] Add default mapping seed
- [ ] Test

## Task 7: Samenloop Handling (REQ-DSO-011)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-011`
- **files**: `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN multiple activiteiten with deelzaken strategy WHEN processed THEN hoofdzaak + deelzaken created
  - GIVEN gecombineerd strategy WHEN processed THEN single combined zaak created
- [ ] Implement deelzaken strategy
- [ ] Implement gecombineerd strategy
- [ ] Test

## Task 8: Unmapped Activiteit Fallback (REQ-DSO-013)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-013`
- **files**: `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN unmapped activiteit WHEN processed THEN triage zaak created with notification
- [ ] Implement fallback zaaktype creation
- [ ] Add notification to triage user
- [ ] Test

## Task 9: Automatic Zaak Creation (REQ-DSO-020)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-020`
- **files**: `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN valid verzoek parsed WHEN zaak creation runs THEN zaak has all mapped fields
  - GIVEN zaak created WHEN complete THEN EventService dispatches event for n8n
- [ ] Implement zaak creation via OpenRegister
- [ ] Add event dispatch
- [ ] Test

## Task 10: DSO-SWF Samenwerking (REQ-DSO-030)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-030`
- **files**: `lib/Service/DSOSamenwerkingService.php`
- **acceptance_criteria**:
  - GIVEN zaak requires advies WHEN behandelaar marks for samenwerking THEN adviesverzoek sent via DSO-SWF
  - GIVEN advies received WHEN processed THEN stored and behandelaar notified
- [ ] Implement adviesverzoek sending
- [ ] Implement advies reception
- [ ] Test

## Task 11: Status Push to DSO-LV (REQ-DSO-040)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-040`
- **files**: `lib/Service/DSOStatusService.php`
- **acceptance_criteria**:
  - GIVEN zaak status changes WHEN DSO-originated zaak THEN status pushed to DSO-LV
  - GIVEN push fails WHEN retries exhausted THEN manual-retry task created
- [ ] Implement status mapping
- [ ] Implement outbound push with retry
- [ ] Test

## Task 12: PKIoverheid Certificate Authentication (REQ-DSO-050)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-050`
- **files**: `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN PKIoverheid certificate configured WHEN outbound call made THEN mTLS used
  - GIVEN certificate expiring in 30 days WHEN health check runs THEN warning notification sent
- [ ] Implement certificate validation
- [ ] Add expiry monitoring
- [ ] Test

## Task 13: Source Registration (REQ-DSO-060)
- **spec_ref**: `specs/dso-omgevingsloket/spec.md#req-dso-060`
- **files**: `lib/Db/Source.php`, `lib/Service/DSOAdapterService.php`
- **acceptance_criteria**:
  - GIVEN new source type "dso" WHEN configured THEN DSO-specific fields stored
  - GIVEN DSO source WHEN test connection clicked THEN STAM probe validates connectivity
- [ ] Add "dso" source type
- [ ] Implement test connection
- [ ] Test

## Task 14: Unit Tests
- **spec_ref**: ADR-009
- **files**: `tests/Unit/Service/DSOParserServiceTest.php`, `tests/Unit/Controller/DSOControllerTest.php`
- [ ] Write parser tests (BSN validation, payload extraction, GML conversion)
- [ ] Write controller tests (endpoint responses, validation errors)
- [ ] Write adapter service tests (mapping, samenloop, fallback)

## Task 15: API Documentation
- **spec_ref**: ADR-010
- **files**: `docs/features/dso-omgevingsloket.md`
- [ ] Write endpoint documentation
- [ ] Write configuration guide
- [ ] Write mapping administration guide

## Verification
- [ ] All tasks checked off
- [ ] Unit tests pass
- [ ] Integration with DSO-LV test environment verified
- [ ] Activiteiten mapping works end-to-end
