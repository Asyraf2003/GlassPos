# ADR Index

## Purpose

This folder contains Architecture Decision Records for long-lived domain, architecture, lifecycle, reporting, and data representation decisions.

ADR files are permanent decision records, not daily notes. Daily work belongs in handoff documents. Temporary design belongs in blueprints.

## How To Read ADRs

Use ADRs when you need to know the accepted decision behind a domain or architecture rule.

Start with:

1. docs/01_standards/0001_index.md
2. docs/01_standards/0002_decision_policy.md
3. docs/02_architecture/adr/README.md
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

## Implementation-Critical ADRs

### ADR-0031

Status:

Accepted.

Topic:

Service product template fast entry keeps cashier input fast while preserving structured product and service pricing.

File:

- docs/02_architecture/adr/0031_service_product_template_fast_entry.md

Implementation boundary:

- Product `harga_jual` remains pure product sale price.
- Service catalog remains pure service master data.
- Service-product template only autofills cashier workflow.
- Package auto split remains `package_total_rupiah - product_total_rupiah`.
- 20/80 package explanation is not persisted as system logic.
- Historical mixed-price notes are not auto-rewritten.

### ADR-0044

Status:

Accepted.

Topic:

Payment settlement intent, physical cash tender, and UI compression remain separate. Cash received is never settlement authority for a partial payment.

File:

- docs/02_architecture/adr/0044_payment_settlement_intent_cash_tender_and_ui_compression.md

Implementation boundary:

- credited payment follows settlement intent within authoritative outstanding;
- cash received is physical tender;
- change equals tender minus credited payment;
- Simple/Detail are presentation compression only;
- hutang, DP, lunas, refund, revision, and cancellation derive from shared primitives rather than separate financial engines;
- when older cashier docs conflict on cash-credit semantics, ADR-0044 wins.

### ADR-0045

Status:

Accepted.

Topic:

Transaction edit is full-layer immutable revision versioning, not CRUD overwrite.

File:

- docs/02_architecture/adr/0045_transaction_revision_version_graph_and_full_layer_snapshot_contract.md

Implementation boundary:

- note root identity stays stable while accepted edits create monotonically newer revisions;
- revision snapshots cover header plus product, service, package decomposition, and external-purchase transaction facts;
- payments/refunds/inventory/audit remain immutable ledgers rather than being cloned into every version;
- current work-item/projection tables may represent the active replacement only if historical versions remain reconstructable;
- old row IDs are stale for current operations;
- stale concurrent editors must not silently overwrite a newer revision;
- cashier UX edits current truth while backend carries historical consequences automatically.

## Current Cleanup Notes

### ADR-0014

Status:

Superseded.

Canonical replacement:

- docs/02_architecture/adr/0015_note_operational_status_open_close_editable_partial_payment.md

Reason:

ADR-0014 and ADR-0015 contained the same decision. ADR-0015 is the canonical decision record.

### ADR-0015

Status:

Accepted.

Topic:

Note operational status uses open and close with editable partial payment.

### ADR For Note Current Projection

Current file:

- docs/02_architecture/adr/0024_note_current_projection_and_current_only_refund.md

Status:

Accepted.

Cleanup note:

This file is accepted, and its filename already follows the numbered ADR convention.

Backlink audit result:

- docs/02_architecture/adr/README.md
- docs/03_blueprints/finance/0002_note_finance_stabilization_addendum.md
- docs/99_archive/handoff/v2/note_finance/2026-04-29-current-projection-refund-edit-handoff.md

Decision:

Keep the dated filename until an explicit ADR number is assigned.

Do not rename it only for visual cleanup.

## Naming Rule

Preferred ADR filename:

- docs/02_architecture/adr/0016-short-decision-name.md

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
