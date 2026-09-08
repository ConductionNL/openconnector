---
kind: code
---

# Proposal: berichtenbox-digital-post-adapter

## Summary
Send a letter from a case to a citizen's MijnOverheid Berichtenbox, or to a
postal service such as Postex, through one integriq provider seam. Receive
digital post the same way. dossiq keeps its `BerichtenboxService` and
compose dialog and stops shipping only a mock adapter.

## Motivation
Zaaksysteem sends a document from the case by e-mail, Postex or MijnOverheid
(`zs/pages/Case-Documenten.md`, Versturen); OpenCase has a
`DigitalPostController` in its enterprise edition (`oc/code-census.md`).
Both under `concurrentie-analyse/procest/_round2/`. dossiq's
`BerichtenboxService` and `BerichtenboxComposeDialog.vue` exist with a mock
adapter only (finding B19, M1 6.6). The analysis puts the adapter with
integriq and asks integriq to write it now (D11).

Integriq already owns the Digikoppeling transport (`digikoppeling-adapter`)
and a provider-interface pattern with log and REST bindings
(`kiss-kcc-bridge`, REQ-001). This change is that pattern for digital post.

## Scope
- `DigitalPostProviderInterface` with `send`, `status`, `pollInbound`.
- Three bindings: `log` (development), `berichtenbox` (Logius Berichtenbox
  via Digikoppeling ebMS), `postex` (REST).
- A `digitalPostMessage` object per send with delivery status.
- Typed events per ADR-041: `DigitalPostSendRequestedEvent` in, with a
  result slot, and `DigitalPostDeliveredEvent` out.
- Inbound post to filinq through `IntakeDocumentReceivedEvent`, channel
  `digitalPost`.

## Out of scope
- The compose dialog and the case-side send button. dossiq has them
  (`berichtenbox-integration`).
- A postal address register. The sender passes the recipient.
