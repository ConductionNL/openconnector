# Tasks: berichtenbox-digital-post-adapter

## Implementation tasks

### Task 1: Provider interface and log binding
- **spec_ref**: `openspec/changes/berichtenbox-digital-post-adapter/specs/digital-post-adapter/spec.md#requirement-one-provider-seam-with-log-berichtenbox-and-postex-bindings-req-dpa-001`
- **files**: `lib/Service/DigitalPost/DigitalPostProviderInterface.php`, `lib/Service/DigitalPost/LogDigitalPostProvider.php`, `lib/Service/DigitalPost/DigitalPostProviderRegistry.php`
- [ ] Implement
- [ ] Test

### Task 2: `digitalPostMessage` schema and send path
- **spec_ref**: `openspec/changes/berichtenbox-digital-post-adapter/specs/digital-post-adapter/spec.md#requirement-a-send-is-a-typed-command-with-a-tracked-message-req-dpa-002`
- **files**: `lib/Settings/integriq_register.json`, `lib/Event/DigitalPostSendRequestedEvent.php`, `lib/Event/DigitalPostDeliveredEvent.php`, `lib/Service/DigitalPost/DigitalPostService.php`
- [ ] Implement
- [ ] Test

### Task 3: Berichtenbox and Postex bindings
- **spec_ref**: `openspec/changes/berichtenbox-digital-post-adapter/specs/digital-post-adapter/spec.md#requirement-one-provider-seam-with-log-berichtenbox-and-postex-bindings-req-dpa-001`
- **files**: `lib/Service/DigitalPost/BerichtenboxProvider.php`, `lib/Service/DigitalPost/PostexProvider.php`
- [ ] Implement (activation refused without certificate and OIN)
- [ ] Test (mock-mode fixtures)

### Task 4: Inbound post to filinq, status job, health
- **spec_ref**: `openspec/changes/berichtenbox-digital-post-adapter/specs/digital-post-adapter/spec.md#requirement-inbound-post-feeds-the-document-intake-inbox-req-dpa-003`
- **files**: `lib/BackgroundJob/DigitalPostStatusJob.php`, `lib/BackgroundJob/DigitalPostInboundJob.php`
- [ ] Implement
- [ ] Test

### Task 5: Source form, i18n, docs
- Provider picker and config fields on the source page; Dutch and English strings; docs with screenshots.
- [ ] Implement
- [ ] Test (`tests/e2e/digital-post-source.spec.ts`)
