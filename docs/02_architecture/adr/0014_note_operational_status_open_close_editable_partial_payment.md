# ADR-0014 - Superseded By ADR-0015

- Status: Superseded
- Date: 2026-04-15
- Superseded by: docs/02_architecture/adr/0015-note-operational-status-open-close-editable-partial-payment.md
- Scope: Core Domain / Note / Payment / Refund / Cashier UI / Audit

## Reason

This ADR overlapped with ADR-0015.

Both ADR-0014 and ADR-0015 described the same accepted decision:

- note operational status uses open and close
- open note can remain editable during partial payment
- close note is not edited through normal workspace flow
- financial ledger remains immutable
- refund flow is the official reversal path for close note

ADR-0015 is kept as the canonical decision record because it already uses the proper ADR title format.

## Canonical ADR

Read this file instead:

- docs/02_architecture/adr/0015-note-operational-status-open-close-editable-partial-payment.md

## Historical Note

This file is kept to preserve numbering and backlink stability.

Do not delete this file unless a separate backlink audit proves no old handoff, blueprint, or external reference depends on this path.

## Current Instruction

AI assistants and maintainers must treat ADR-0015 as the source of truth for this decision.
