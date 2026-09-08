---
kind: code
---

# Proposal: brp-kvk-store-and-subscriptions

## Summary
Keep a local copy of every person and organisation a case app has looked up,
subscribe to changes on them at the source (BRP afnemerindicatie, KvK
mutaties), and announce each change so the case that holds them updates.
Integriq owns the store and the subscriptions. The case app keeps its lookup
adapters and reacts to the announcement.

## Motivation
Zaaksysteem's Gegevensmagazijn holds persons and organisations locally with
an afnemerindicatie per record and a CSV export
(`zs/pages/Gegevensmagazijn.md`); OpenCase's enterprise edition listens to
CPR events (`oc/code-census.md`). Both under
`concurrentie-analyse/procest/_round2/`. dossiq's `HaalCentraalBrpAdapter`
and `KvkApiAdapter` look up and forget; nothing subscribes (finding B22,
M1 5.11). The analysis splits the row: the store and the subscriptions are
integriq's, the case-side reaction is dossiq's (D11).

Integriq already seeds a BRP Haal Centraal source (`source-management`) and
owns synchronizations and the CloudEvents fan-out.

## Scope
- A `registryStore` register with `person` (BRP) and `organisation` (KvK)
  schemas, one row per looked-up subject, with `fetchedAt` and
  `subscription` state.
- A subscription per row: Haal Centraal BRP `ingeschrevenpersonen`
  volgindicaties, KvK mutatieservice. Set on first lookup, dropped when no
  holder remains.
- Change intake: a poll or a notification per source, a diff on the row, a
  CloudEvent `nl.conduction.integriq.registry.<person|organisation>.changed`
  with the diff and the holders.
- A `data-provider` leaf (ADR-066) `integriq-registry-subject` that lists
  the stored row for a host object that references a BSN or KvK number.

## Out of scope
- The lookup UI. The case app has it (`brp-register`, `kvk-register`).
- Consent and purpose binding on the lookup. That stays where the lookup is.
