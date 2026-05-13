# ADR Index

## Purpose

This folder contains Architecture Decision Records for long-lived domain, architecture, lifecycle, reporting, and data representation decisions.

ADR files are permanent decision records, not daily notes. Daily work belongs in handoff documents. Temporary design belongs in blueprints.

## How To Read ADRs

Use ADRs when you need to know the accepted decision behind a domain or architecture rule.

Start with:

1. docs/01-standards/00-index.md
2. docs/01-standards/01-decision-policy.md
3. docs/02-architecture/adr/README.md
4. the ADR relevant to the current scope

## ADR Status Values

Use these status values:

- Accepted
  The decision is active and canonical.

- Superseded
  The decision was replaced by another ADR.

- Draft
  The decision is not accepted yet.

- Deprecated
  The decision should not guide new work, but may still explain history.

## Current Cleanup Notes

### ADR-0014

Status:

Superseded.

Canonical replacement:

- docs/02-architecture/adr/0015-note-operational-status-open-close-editable-partial-payment.md

Reason:

ADR-0014 and ADR-0015 contained the same decision. ADR-0015 is the canonical decision record.

### ADR-0015

Status:

Accepted.

Topic:

Note operational status uses open and close with editable partial payment.

### Dated ADR For Note Current Projection

Current file:

- docs/02-architecture/adr/2026-04-29-note-current-projection-and-current-only-refund.md

Status:

Accepted.

Cleanup note:

This file is accepted, but its filename does not follow the numbered ADR convention.

Backlink audit result:

- docs/02-architecture/adr/README.md
- docs/03-blueprints/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md
- docs/99-archive/handoff/v2/note-finance/2026-04-29-current-projection-refund-edit-handoff.md

Decision:

Keep the dated filename until an explicit ADR number is assigned.

Do not rename it only for visual cleanup.

## Naming Rule

Preferred ADR filename:

- docs/02-architecture/adr/0016-short-decision-name.md

Date belongs inside the file metadata, not as the primary ADR identity.

## Promotion Rule

If a handoff contains a permanent decision:

1. Create a new ADR or update an existing ADR.
2. Add context, decision, consequences, rejected alternatives, and invariants.
3. Link the handoff as evidence.
4. Keep the handoff as historical session recovery.
5. Do not leave permanent decisions only inside handoff docs.

## Cleanup Rule

Before renaming or deleting ADR files:

1. Run backlink audit.
2. Search docs, app, routes, tests, database, and Makefile.
3. Update references in the same small patch.
4. Run targeted tests when code references docs.
5. Commit small.

Do not delete old ADR paths just to make the tree look clean. Public repo readers and old handoffs may still depend on them.
