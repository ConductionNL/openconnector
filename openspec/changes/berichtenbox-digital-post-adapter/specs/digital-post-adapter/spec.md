# digital-post-adapter Specification

**Status**: proposed
**Scope**: integriq
**OpenSpec changes**:
- berichtenbox-digital-post-adapter

## Purpose

One provider seam for digital post: MijnOverheid Berichtenbox, Postex and a
log binding. A fleet app asks for a send through a typed event (ADR-041),
integriq tracks delivery and announces status changes, and inbound post
feeds filinq's intake inbox. Requested by the dossiq competitor analysis,
finding B19.

## ADDED Requirements

### Requirement: One provider seam with log, Berichtenbox and Postex bindings (REQ-DPA-001)

Integriq MUST define `DigitalPostProviderInterface` with `getProviderId()`,
`getConfigSchema()`, `send()`, `status()` and `pollInbound()`, resolved by
`providerId` from the source configuration, with a `log` binding, a
`berichtenbox` binding over the Digikoppeling transport and a `postex` REST
binding. The Berichtenbox binding MUST refuse activation without a
PKIoverheid certificate and a sender OIN and MUST say which is missing.

#### Scenario: A source without a certificate cannot activate Berichtenbox
- GIVEN a digital post source with `providerId = berichtenbox` and no certificate
- WHEN an operator activates it
- THEN activation is refused with a message naming the certificate
- e2e: `tests/e2e/digital-post-source.spec.ts`

#### Scenario: The log binding answers delivered
- GIVEN a source with `providerId = log`
- WHEN a message is sent
- THEN the provider logs it and the message reaches `delivered`
- @e2e exclude the log provider is a backend fixture; covered by PHPUnit

### Requirement: A send is a typed command with a tracked message (REQ-DPA-002)

Integriq MUST handle `DigitalPostSendRequestedEvent` by creating a
`digitalPostMessage` (`recipient`, `subject`, `body`, `attachments[]`,
`requestedBy`, lifecycle `queued`, `sent`, `delivered`, `read`, `failed`),
calling the source's provider, and answering the event's result slot with
the message id or a structured refusal. Every status change MUST dispatch
`DigitalPostDeliveredEvent` with the message id and `requestedBy`. A failed
send MUST keep the message and its attachments.

#### Scenario: dossiq sends a letter from a case
- GIVEN a case app dispatches `DigitalPostSendRequestedEvent` with a PDF and a BSN recipient
- WHEN integriq handles it against a mock-mode Berichtenbox source
- THEN a `digitalPostMessage` in `sent` exists, the result slot carries its id, and a later status poll moves it to `delivered` and dispatches `DigitalPostDeliveredEvent`
- @e2e exclude cross-app typed events; covered by PHPUnit with a stub requester and a mock-mode source

#### Scenario: A failed send keeps the letter
- GIVEN the provider answers an error
- WHEN the send runs
- THEN the message is `failed` with `lastError` set and the PDF still attached
- @e2e exclude failure path; covered by PHPUnit

### Requirement: Inbound post feeds the document intake inbox (REQ-DPA-003)

A scheduled job MUST call `pollInbound()` per active source and dispatch one
`IntakeDocumentReceivedEvent` with channel `digitalPost` per received item,
carrying the sender identity as metadata. Health per source (last send,
last error, queue depth) MUST be readable on the source page.

#### Scenario: A citizen reply arrives
- GIVEN a mock-mode Berichtenbox source with one inbound fixture
- WHEN the inbound job runs
- THEN one `IntakeDocumentReceivedEvent` with channel `digitalPost` was dispatched
- @e2e exclude background job; covered by PHPUnit with a recording dispatcher
