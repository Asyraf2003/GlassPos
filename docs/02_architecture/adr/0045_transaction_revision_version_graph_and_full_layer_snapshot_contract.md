# ADR-0045: Transaction Revision Version Graph And Full-Layer Snapshot Contract

Status: Accepted

Date: 2026-09-13

Deciders: Project Owner, Architecture Decision

Scope: transaction edit / revision / product / service / package / external purchase / payment / refund / inventory / audit / reporting / cashier UX

Refines:

- ADR-0042 note edit/refund settlement machine;
- ADR-0025 revision carry-forward settlement;
- ADR-0044 first-principles payment settlement/tender contract;
- current revision snapshot and package decomposition work under finance blueprints.

When this ADR conflicts with older edit-as-overwrite assumptions, this ADR wins.

## Context

Real transactions change after they are created.

Examples:

- wrong quantity;
- wrong product;
- wrong service price;
- package composition changes;
- external-purchase amount changes;
- customer identity/header correction;
- payment already happened before correction;
- refund already happened before another correction;
- stock already moved before the edit;
- master product/service data changed after the original transaction.

Cashier and customer must not be forced to understand historical carry-forward mechanics.

The UI should let the operator edit the current transaction naturally.

The backend must preserve every historical fact accurately.

Therefore transaction edit is a versioning problem, not a CRUD overwrite problem.

## Decision

Every accepted edit creates a new immutable transaction revision.

The stable identity is the note root.

The current operational state points to one active revision.

Conceptually:

```
note root
  |
  +-- revision 1
  |
  +-- revision 2
  |
  +-- revision 3  <- current
```

The current chain is linear.

A revision never silently rewrites an older revision.

## Full-Layer Version Scope

A transaction revision is not only a version of the note total.

A revision must capture enough immutable snapshot data to reconstruct the business meaning of the full transaction graph at that version.

At minimum the version boundary includes all relevant changed/current facts from these layers.

### Note/header layer

Examples:

- customer name;
- customer phone;
- transaction date where mutation policy permits;
- operational note/reason/context;
- total;
- revision number / identity;
- revision reason and actor context.

### Work-item layer

Examples:

- line identity inside the revision;
- line order;
- transaction type;
- current subtotal;
- active/replaced/shadow meaning where applicable.

### Product/store-stock layer

Examples:

- product identity;
- historical product label/snapshot;
- quantity;
- unit sale price snapshot;
- line total;
- price basis;
- package membership/decomposition;
- inventory issue/reversal linkage;
- historical cost basis required for later stock/refund/report reconciliation.

A later master product name, price, or costing change must not rewrite an older transaction version.

### Service layer

Examples:

- service identity/catalog reference where relevant;
- historical service label;
- service price;
- service notes;
- package base service price;
- package service extra;
- total service component;
- service/package pricing metadata required to explain the version.

A later service-catalog change must not rewrite the historical revision.

### Package layer

A package is versioned as its decomposition, not only as one displayed total.

The revision snapshot must preserve enough data to explain:

- package total;
- product lines and quantities;
- product sale values;
- parts total;
- service component;
- package base service amount;
- package extra/profit fields where contractually used;
- template/catalog reference as context, never as a replacement for the historical snapshot.

Changing one product in a package creates a new package version.

The old package composition remains historical truth.

### External-purchase layer

The revision snapshot must preserve:

- external purchase identity/reference when available;
- historical label/description;
- amount/cost snapshot;
- service relationship;
- current note effect;
- lifecycle facts needed to distinguish pass-through/procurement from store inventory.

Later external data must not rewrite an earlier revision.

## What Is Versioned Versus What Remains An Immutable Ledger

Not every table requires a `version_id`.

The requirement is reconstructable historical truth.

### Version snapshot / current replacement

Revision snapshots represent the transaction shape at a point in time.

Current operational work-item tables may represent only the active replacement shape when that is the existing architecture.

That is allowed only when old versions remain reconstructable through revision snapshots and immutable ledgers.

### Payment

Payments are immutable historical money events.

They are not cloned into every revision.

A revision carries settlement consequences forward from official payment/refund history.

Current component allocations may be rebuilt for the active replacement, but rebuilding allocation must not rewrite:

- original payment amount;
- method;
- paid time;
- cash received;
- change.

### Refund

Refund events remain immutable.

Refunded/shadow components do not become new current components merely because another revision is created.

A revision after refund must preserve the refund boundary.

### Inventory

Inventory movements are immutable physical events.

Revision does not edit an old stock movement.

If current stock requirement changes, the system records explicit correction/reversal and new issue effects using the revision lifecycle.

Revision stock correction remains distinct from refund stock reversal.

### Audit

Audit is append-only evidence.

Revision history must retain actor, reason, old/new context, and affected identities.

### Projection

Current projections are derived/current state.

They are not historical truth.

They may be rebuilt from official current revision plus immutable event history.

## Active Replacement Contract

Edit operates on the current active revision.

The operator sees a current editable draft.

On submit:

```
current version
-> validate command
-> create next immutable revision snapshot
-> calculate financial delta
-> calculate inventory delta
-> apply active replacement
-> carry forward payment/refund history
-> update current revision pointer/projection
-> append audit
```

The operator does not manually migrate old payment/refund/stock consequences.

The machine does it.

## Old Identity And Stale Operation Contract

When a revision replaces current transaction lines:

- old active row/component identities become historical/stale for normal current operations;
- current refund/payment/edit operations must resolve against the current revision or explicit historical correction capability;
- stale pre-revision row IDs must not silently mutate current state;
- historical rows remain readable/auditable.

A replacement line may receive a new operational identity.

That is expected.

Identity continuity is provided by revision/history links, not by pretending the mutable row never changed.

## Stale Edit / Lost Update Contract

Versioning must prevent silent overwrite of a newer edit.

An edit command must be applied against the authoritative current revision under the existing transaction/locking boundary.

If the client is editing an older revision while another revision has already become current, the system must not silently replace the newer state.

Preferred behavior:

- reject as stale/conflict and require refresh/retry from current revision;
- or use another explicitly documented conflict policy.

Silent last-write-wins across transaction revisions is rejected.

If current request payloads do not carry enough revision identity to prove this behavior, classify it as a hardening gap and add characterization before implementation.

## Financial Carry-Forward Across Versions

Versioning changes obligation, not historical money.

For every revision:

```
historical payments remain historical payments
historical refunds remain historical refunds
new active obligation comes from the new revision
settlement/outstanding/surplus is recalculated from official history
```

A user editing:

```
100k -> 130k
```

after paying 20k does not create a new 20k payment.

The new version has:

```
current obligation = 130k
historical valid settlement = 20k
outstanding = 110k
```

A downward version may create surplus/refund-due through the existing revision settlement lifecycle.

## Inventory Delta Across Versions

Editing store-stock components must produce physical delta through explicit inventory events.

Examples:

### Quantity 2 -> 1

Do not rewrite the original issue quantity.

Record revision correction/reversal so net stock effect becomes one issued unit.

### Product A -> Product B

The old A issue is corrected/reversed according to revision rules.

The new B issue is recorded.

### Service-only price edit

No store inventory movement.

### External purchase edit

No store inventory movement unless another explicit store-stock component is also changed.

## UI Compression Contract

Versioning complexity belongs to the system.

Cashier UX may remain:

```
Buka nota
-> Edit
-> ubah yang memang salah
-> Simpan
```

The cashier must not be asked to:

- manually copy previous payment;
- re-enter old refunds;
- decide how current allocations are rebuilt;
- manually reverse inventory;
- choose which historical version tables to update;
- understand internal revision IDs except when conflict/reload communication is necessary.

The backend version machine owns those consequences.

Simple/Detail modes must edit the same current transaction truth.

They must not create different version semantics.

## Reporting Contract

Reports must be able to answer two different questions without mixing them.

### Current truth

What is the transaction now?

Use current revision/projection.

### Historical truth

What was the transaction at revision N and what events happened between revisions?

Use revision snapshots plus immutable payment/refund/inventory/audit events.

A report must not use current product/service master data to reconstruct historical transaction value when a transaction snapshot exists.

A current report must not sum replaced historical lines as though they were still active.

## Cancellation And Versioning

Cancellation/reversal does not erase versions.

If current domain supports a cancellation operation, its result must be recorded as another explicit lifecycle transition/effect over the versioned transaction.

Depending on the case it may involve:

- current obligation neutralization;
- refund/refund-due;
- inventory reversal;
- service/external consequence;
- current projection change;
- audit event.

Historical revisions remain intact.

If whole-note cancellation semantics are not currently defined, keep it as CONTRACT GAP rather than introducing destructive deletion.

## Idempotency

Revision creation remains idempotent:

- same key + same semantic payload => replay existing result, no extra revision;
- same key + changed payload => reject;
- duplicate submit must not create duplicate inventory correction, duplicate allocation rebuild, duplicate surplus refund, or duplicate audit effect.

## Required Regression Matrix

The hardening campaign must eventually prove versioning across all transaction layers.

### Product

1. product quantity edit;
2. product price edit;
3. product A replaced by product B;
4. master product name/price changes later but old version remains unchanged.

### Service

5. service price edit;
6. service label/catalog context changes;
7. old service snapshot remains reconstructable after master change.

### Package

8. package product quantity changes;
9. package swaps one product;
10. package changes from one to multiple product lines or vice versa where supported;
11. service/package split changes;
12. previous package decomposition remains historically reconstructable.

### External purchase

13. external label/amount edit;
14. external component removal/addition where policy allows;
15. external edit does not mutate store inventory.

### Mixed transaction

16. one revision changes product + service + package + external purchase together;
17. total/current projection equals the new graph only;
18. old version remains reconstructable.

### Finance across versions

19. edit after partial payment;
20. edit after multiple payments;
21. edit after refund;
22. downward edit creates correct surplus/refund lifecycle;
23. payment timeline remains immutable across all edits.

### Inventory across versions

24. revision correction and reissue reconcile stock exactly;
25. refund after revision targets current replacement component;
26. stale old component cannot create duplicate stock return.

### Concurrency/idempotency

27. duplicate same-key revision creates no second version;
28. changed payload under same key rejects;
29. stale editor cannot silently overwrite a newer current revision.

### Reporting

30. current report reads latest version;
31. historical/audit view can explain prior version;
32. operational profit/COGS uses active replacement plus immutable movement/refund truth;
33. no current master-data change rewrites historical transaction reporting.

These tests should use the real revision/application/database path wherever practical.

## Rejected Behaviors

Reject:

- in-place destructive overwrite as the canonical edit model;
- versioning only the note total while losing component shape;
- versioning product but not service/package/external components;
- copying payment events into every revision;
- rewriting old payment/refund/inventory events to match current state;
- current master data silently replacing historical snapshot values;
- stale row IDs mutating current components;
- last-write-wins edit silently overwriting a newer revision;
- forcing cashier/customer to manually reconcile version history;
- using reporting queries to hide missing revision history.

## Consequence

The transaction engine becomes more complex internally but simpler operationally.

Humans edit the current truth.

The machine preserves and reconciles:

- old versions;
- current version;
- money;
- refunds;
- stock;
- audit;
- reports.

This is intentional.

Transaction complexity is absorbed by the version engine rather than pushed onto the cashier or customer.
