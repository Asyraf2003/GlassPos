# ADR: Note Current Projection And Current-Only Refund

## Status

Accepted.

## Date

2026-04-29

## Context

The note finance stabilization scope found a lifecycle bug:

~~~text
A note revision deletes old work_items.
Old work_items may already be referenced by refund_component_allocations.work_item_id.
The database correctly rejects deleting those rows through FK fk_rca_work_item.
~~~

The deeper problem is that work_items is used for two different purposes:

~~~text
1. current operational note rows
2. historical financial anchors
~~~

This causes edit, refund, and revision flows to collide with immutable financial history.

The product requirement is stricter than a small FK fix:

~~~text
- Edit must always be available from the note page.
- Refund must always be available from the note page.
- User mistakes and overwriting old notes must be treated as normal operating behavior.
- Current note calculation must remain easy for cashier users.
- Financial history, stock history, and reports must remain auditable.
~~~

## Decision

Adopt Hybrid C+:

~~~text
Immutable ledger and history + current projection table.
~~~

### Current UI

The note page must use current projection as the source of truth for active state.

### Edit

Every edit creates a new current calculation and updates or rebuilds current projection.

Old data becomes legacy, history, and audit.

### Refund

Refund is always available, but only for the current active version or projection.

Historical or legacy rows are not eligible for new refunds.

Refund events that already happened remain valid ledger events.

### Ledger

Payment, refund, and inventory events remain immutable anchors.

Do not rewrite old allocations to new current rows.

Do not cascade delete financial history.

## Consequences

### Positive

~~~text
- Current UI stays simple.
- User can edit and refund without understanding revision history.
- Old financial anchors remain valid.
- Current calculation is separated from historical rows.
- Reporting can separate current projection from ledger history.
~~~

### Negative

~~~text
- Requires projection table or projection model.
- Requires reader migration.
- Requires clear current-vs-ledger report boundaries.
- Requires more tests.
- Write path becomes heavier and must be transaction-safe.
~~~

### Rejected Alternatives

#### Reject: Cascade Delete

Rejected because it destroys financial history.

#### Reject: Nullable FK

Rejected because it hides broken lifecycle instead of modeling it.

#### Reject: Rewrite Refund Allocation To New Work Item

Rejected because it falsifies historical event anchors.

#### Reject: Skip Delete Referenced Work Items Only

Rejected as final solution because it prevents FK crash but risks old and new rows both being read as current.

#### Reject: Refund Historical Legacy Rows

Rejected for current product behavior.

Historical rows remain audit-only after superseded.

## Invariants

~~~text
current note state = current projection
ledger state = immutable payment, refund, and inventory events
history state = revisions + legacy rows + mutation or audit events
~~~

A row may leave current projection, but it must not disappear from ledger or history if it has financial or inventory relevance.

## Required Follow-Up

Before implementation:

~~~text
- design current projection table
- define projection rebuild behavior
- define current reader migration list
- define ledger and history reporting boundary
- define tests for current-only refund and edit-after-refund
~~~

## Verification Requirements

~~~text
- revision after refund does not FK crash
- old refunded work item remains available as historical anchor
- old row does not appear in current note calculation
- refund selection uses current projection only
- existing refund ledger remains valid
- current report and ledger report do not mix semantics
~~~
