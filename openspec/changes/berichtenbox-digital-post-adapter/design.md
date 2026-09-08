# Design: berichtenbox-digital-post-adapter

Kind: code. One interface, three bindings, one schema, two typed events.

## D1. Provider seam

`lib/Service/DigitalPost/DigitalPostProviderInterface.php`:
- `getProviderId(): string`
- `getConfigSchema(): array`
- `send(array $sourceConfiguration, DigitalPostMessage $message): SendResult`
- `status(array $sourceConfiguration, string $externalId): DeliveryStatus`
- `pollInbound(array $sourceConfiguration, ?string $cursor): InboundBatch`

Bindings resolve by `providerId` from the source configuration, like the
klantinteracties providers. `LogDigitalPostProvider` writes to the log and
answers `delivered`. `BerichtenboxProvider` wraps the Digikoppeling ebMS
transport from `digikoppeling-adapter` with the Berichtenbox message
schema (BSN, subject, body, PDF attachments, sender OIN).
`PostexProvider` posts to the Postex REST API.

## D2. `digitalPostMessage` schema

`sourceId`, `providerId`, `recipient` (`{bsn|kvk, name}`), `subject`,
`body`, `attachments[]`, `requestedBy` (`{app, objectRef}`), `externalId`,
`status` lifecycle `queued`, `sent`, `delivered`, `read`, `failed`,
`lastError`, `sentAt`, `deliveredAt`. BSN is stored through OpenRegister's
existing `BsnFormat`, never as a plain string.

## D3. Typed events (ADR-041)

- In: `DigitalPostSendRequestedEvent(message, sourceId)` with a result slot
  carrying the `digitalPostMessage` id or a structured refusal. dossiq's
  `BerichtenboxService` dispatches it and keeps its own UI.
- Out: `DigitalPostDeliveredEvent(messageId, requestedBy, status)` on every
  status change, so the requesting app updates its case without polling.

Status updates arrive by `status()` from a scheduled job, or by the
provider's callback where it offers one.

## D4. Inbound

`pollInbound()` on a schedule. Each inbound item becomes an
`IntakeDocumentReceivedEvent` with channel `digitalPost` and the sender's
identity as metadata, so filinq's intake inbox shows it
(`document-intake-inbox`).

## D5. Health

Per source: last successful send, last error, queue depth. Surfaced on the
source page and in the health status dossiq's Integrations page reads
(analysis row A34).

## Risks
- Berichtenbox needs a PKIoverheid certificate and an OIN. The provider
  refuses to activate without both and says so.
- A failed send must not lose the letter. The message stays `failed` with
  the PDF attached; retry is an operator action.
