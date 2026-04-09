# Tasks: ibabs-notubiz-connector

## Task 1: iBabs REST API Connection (REQ-RIS-001)
- **spec_ref**: `specs/ibabs-notubiz-connector/spec.md#req-ris-001`
- **files**: `lib/Service/IBabsConnectorService.php`
- **acceptance_criteria**:
  - GIVEN iBabs source configured WHEN test connection clicked THEN API connectivity verified
  - GIVEN API key expired WHEN call returns 401 THEN Source status set to error with notification
- [ ] Implement IBabsConnectorService with connection management
- [ ] Add test connection method
- [ ] Test

## Task 2: Collegevoorstel Push to iBabs (REQ-RIS-002)
- **spec_ref**: `specs/ibabs-notubiz-connector/spec.md#req-ris-002`
- **files**: `lib/Service/IBabsConnectorService.php`
- **acceptance_criteria**:
  - GIVEN zaak with voorstel WHEN pushed to iBabs THEN document uploaded with metadata
  - GIVEN DOCX document WHEN pushed THEN Docudesk converts to PDF first
- [ ] Implement document push via CallService
- [ ] Add PDF conversion via Docudesk
- [ ] Add geheimhouding flag support
- [ ] Test

## Task 3: Agendapunt Creation (REQ-RIS-003)
- **spec_ref**: `specs/ibabs-notubiz-connector/spec.md#req-ris-003`
- **files**: `lib/Service/IBabsConnectorService.php`
- **acceptance_criteria**:
  - GIVEN voorstel pushed WHEN auto-select configured THEN next vergadering selected
  - GIVEN no upcoming vergadering WHEN creating agendapunt THEN sync set to pending
- [ ] Implement agendapunt creation
- [ ] Add auto-select vergadering logic
- [ ] Test

## Task 4: Besluit Retrieval from iBabs (REQ-RIS-004)
- **spec_ref**: `specs/ibabs-notubiz-connector/spec.md#req-ris-004`
- **files**: `lib/Service/IBabsConnectorService.php`, `lib/Cron/RISPollJob.php`
- **acceptance_criteria**:
  - GIVEN voorstel aangenomen WHEN poll retrieves besluit THEN zaak status updated
  - GIVEN voorstel verworpen WHEN retrieved THEN behandelaar notified
- [ ] Implement besluit polling cron job
- [ ] Add status mapping (aangenomen/verworpen/aangehouden/doorgeschoven)
- [ ] Test

## Task 5: Besluitenlijst Retrieval (REQ-RIS-005)
- **spec_ref**: `specs/ibabs-notubiz-connector/spec.md#req-ris-005`
- **files**: `lib/Service/IBabsConnectorService.php`
- **acceptance_criteria**:
  - GIVEN vergadering concluded WHEN poll finds besluitenlijst THEN PDF stored in Nextcloud Files
- [ ] Implement besluitenlijst download
- [ ] Add file storage in /RIS-besluiten/ folder structure
- [ ] Test

## Task 6: NotuBiz API Connection (REQ-RIS-020)
- **spec_ref**: `specs/ibabs-notubiz-connector/spec.md#req-ris-020`
- **files**: `lib/Service/NotuBizConnectorService.php`
- **acceptance_criteria**:
  - GIVEN NotuBiz source with OAuth2 WHEN configured THEN auto token refresh works
- [ ] Implement NotuBizConnectorService
- [ ] Add OAuth2 token management via AuthenticationService
- [ ] Test

## Task 7: Vergaderstuk Push to NotuBiz (REQ-RIS-021)
- **spec_ref**: `specs/ibabs-notubiz-connector/spec.md#req-ris-021`
- **files**: `lib/Service/NotuBizConnectorService.php`
- **acceptance_criteria**:
  - GIVEN zaak requires raadsbehandeling WHEN pushed THEN correct vergadertype used
- [ ] Implement vergaderstuk push
- [ ] Test

## Task 8: Unit Tests
- **spec_ref**: ADR-009
- **files**: `tests/Unit/Service/IBabsConnectorServiceTest.php`, `tests/Unit/Service/NotuBizConnectorServiceTest.php`
- [ ] Write connection management tests
- [ ] Write document push tests
- [ ] Write besluit retrieval tests

## Task 9: API Documentation
- **spec_ref**: ADR-010
- **files**: `docs/features/ibabs-notubiz-connector.md`
- [ ] Write configuration guide
- [ ] Write workflow documentation

## Verification
- [ ] All tasks checked off
- [ ] Unit tests pass
- [ ] iBabs integration verified with test API
- [ ] NotuBiz integration verified with test API
