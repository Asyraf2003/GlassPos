# ADR-0047: Temporal Legacy Reference And UI Mutation Policy

Status: Accepted

Date: 2026-10-03

Deciders: Project Owner, Architecture Decision

Scope: CRUD / UI mutation / master data / legacy references / product identity / supplier invoices / inventory / service reclassification / versioning / audit / reporting

Refines:

- ADR-0045 transaction revision version graph and full-layer snapshot contract;
- ADR-0046 cancellation and restore current-versus-history contract;
- supplier-invoice revision/version storage and received-invoice correction behavior;
- existing inventory source-of-truth and current-projection rules.

When this ADR conflicts with generic CRUD assumptions, this ADR wins.

## Context

GlassPos data is no longer isolated CRUD data.

A master entity such as a product can be referenced by supplier invoices, receipts, inventory movements, inventory projections, costing projections, service/package relationships, transaction revisions, reporting read models, and audit/version history.

Therefore an operation that looks small in the UI can have different meanings depending on what changed.

Examples:

- correcting only a supplier invoice number;
- renaming an active product;
- replacing a product on a received supplier invoice;
- merging duplicate product identities;
- reclassifying a legacy product into a service;
- changing quantity after stock has already been received;
- soft-deleting a master that remains referenced by historical documents;
- showing current reports when historical rows refer to identities that are no longer active.

A generic form submit that revalidates and rewrites the whole graph is unsafe.

Likewise, freezing all legacy documents forever is operationally unacceptable.

The system must preserve old truth while still allowing safe correction of current truth.

## Motivating Evidence

### Duplicate product identity and stock transfer

A historical supplier invoice referenced product `A3GN5` / `PISTON GREND` / `HONDA` / size 50.

Inventory evidence proved the historical quantity was real:

- supplier receipt stock-in `+10`;
- an invoice revision stock-out `-10` then stock-in `+10`;
- final pre-merge balance `10`.

On 2026-09-29 the product was explicitly merged to canonical product `A3GN520` / `PISTON GREND` / `AHM` / size 50.

The merge used one operation identity and paired movements:

- legacy product `A3GN5`: `product_master_merge` stock-out `-10`;
- canonical product `A3GN520`: `product_master_merge` stock-in `+10`.

The historical supplier invoice remained a historical fact referencing the identity known at that time.

This is correct behavior: current stock identity changes without rewriting the original source document.

### Local opening-stock seed contamination

A separate local-data audit found `CreateInventorySeeder` had inserted synthetic `opening_stock_seed` movements for 200 active products.

Audit proof:

- 200 seeded products;
- total synthetic quantity: 6,820;
- total synthetic movement value: Rp698,525,264;
- 106 products already had real business movements and only gained a false seed movement;
- 94 products had no non-seed inventory movement at all;
- all 94 had quantity and costing exactly matching the seed;
- all 94 remained active products;
- no delete/merge/service-reclassification event was found in their product version history.

This incident is not itself the permanent policy, but it proves that provenance matters. A current row that looks valid is insufficient evidence of business truth.

No cleanup is authorized by this ADR. Data cleanup remains a separate evidence-driven repair task.

## Core Decision

GlassPos treats persisted business data as temporal data with two simultaneous truths:

1. **Historical truth**: what identity, values, and relationships were recorded when an event/document/version occurred.
2. **Current truth**: what entity, lifecycle state, canonical identity, projection, and obligation are authoritative now.

A change to current truth must not silently rewrite historical truth.

A historical reference must not become invalid merely because its current master is renamed, merged, reclassified, or soft-deleted.

## CRUD Is Intent-Sensitive, Not Row-Sensitive

The UI and application layer must classify a mutation by semantic intent before deciding what to validate or propagate.

### Class A: Metadata Correction

Examples:

- supplier invoice number correction;
- administrative reference correction;
- non-economic note/description correction where policy allows.

Contract:

- validate only the changed metadata plus document-level invariants;
- do not revalidate unchanged historical product references as if they were new selections;
- do not create inventory, costing, payment, payable, or refund effects unless the changed field semantically requires them;
- append version/audit evidence when the document is versioned;
- preserve line snapshots unchanged.

A metadata-only correction must not fail merely because an unchanged line points to a soft-deleted or merged product.

### Class B: Economic / Quantity / Identity Revision

Examples:

- product A becomes product B;
- qty changes;
- unit cost or line total changes;
- tax changes;
- received supplier invoice line changes after receipt.

Contract:

- route through the domain revision engine;
- create immutable revision/version evidence;
- calculate explicit inventory delta rather than rewriting old movements;
- reconcile costing and supplier payable where applicable;
- preserve historical receipts, payments, movements, and previous revision snapshots;
- reject unsafe negative-stock or stale-revision outcomes atomically.

### Class C: Master Identity / Lifecycle Correction

Examples:

- rename product;
- merge duplicate product masters;
- soft-delete product;
- reclassify an old product record into service domain;
- replace one canonical master with another.

Contract:

- current master changes control future use;
- historical snapshots remain unchanged;
- current stock/state that belongs to the old identity must be explicitly transferred/reconciled if required;
- merge/reclassification requires reason, actor, time, source operation, old identity, and target/current identity where one exists;
- no bulk rewrite of historical foreign keys merely to make old documents look current.

### Class D: Current Projection Correction

Examples:

- rebuilding current inventory projection from the official movement ledger;
- rebuilding current costing projection;
- repairing a derived dashboard/read model.

Contract:

- projection repair must not invent or rewrite historical business events;
- source-of-truth event history remains authoritative;
- repair must be reproducible and auditable;
- report-side hiding is not a substitute for repairing broken source/projection semantics.

### Class E: Destructive / Cancellation / Void Intent

Delete-like UI actions must resolve to an explicit lifecycle.

Depending on domain this may mean:

- soft delete for a master no longer available for new use;
- cancellation with compensating effects;
- refund;
- revision replacement;
- archive/inactive state.

Hard deletion of historically referenced financial/inventory roots is rejected unless a separate accepted contract proves it safe.

## Active Reference Versus Historical Reference

The same foreign key can have different validation semantics depending on use.

### Active reference

Creating a new operational relationship must normally resolve to an eligible current master.

Examples:

- adding a new invoice line;
- adding a new transaction product;
- adding a new service-package component.

Inactive/soft-deleted/merged-away identities must not be silently selectable for new use.

### Historical reference

An existing persisted relationship remains valid for history even if its master is no longer current.

Examples:

- old supplier invoice line;
- old receipt line;
- old transaction revision snapshot;
- old inventory movement;
- old report/audit event.

The UI must render the historical reference and its snapshot instead of treating it as missing data.

Soft delete therefore means primarily **not eligible for new use**, not **never existed**.

## Snapshot And Canonical Identity Contract

Historical documents must preserve enough snapshot data to explain what the operator saw at the time.

For a product reference this can include:

- product id;
- code snapshot;
- name snapshot;
- brand snapshot;
- size snapshot;
- relevant price/cost snapshot.

Current canonical identity may differ later.

When a machine-readable lineage exists, UI/read models may additionally show:

```
Historical: A3GN5 / PISTON GREND / HONDA
Current:    A3GN520 / PISTON GREND / AHM
Status:     merged / superseded
```

The current canonical label is explanatory context, not a retroactive replacement of the historical snapshot.

Current evidence for product merge includes product-version reasons plus paired `product_master_merge` movements. A dedicated lineage relation must be verified or designed before UI implementation relies on automatic canonical resolution.

## Versioning Contract

Persisted documents that already support revisions must preserve immutable generations.

Conceptually:

```
document root
  +-- revision 1  immutable historical truth
  +-- revision 2  immutable historical truth
  +-- revision 3  current accepted truth
```

Users may inspect and, where useful, print/export an older revision for audit purposes.

Operational workflows must clearly mark which revision is historical and which revision is current.

An older revision must never accidentally become the source for new stock/payment/payable effects merely because the user is viewing it.

## Product Master Change Propagation Matrix

| Change | Historical documents | Current master | Current projection | New operations |
|---|---|---|---|---|
| Rename | keep old snapshot | show new name | normally unchanged | use new name |
| Price change | keep historical price snapshot | show new price | no retroactive event rewrite | use current price policy |
| Soft delete | remain readable | inactive | preserve justified current state until domain correction | cannot select for new use |
| Merge duplicate | keep old identity snapshot | old inactive, target canonical | transfer current state explicitly where required | use canonical target |
| Reclassify product -> service | keep historical product facts | product inactive / service current | reconcile inventory only from proven facts | use service domain |
| Qty/cost correction on received invoice | old revision immutable | master unchanged unless separate master command | explicit delta/revaluation | use new accepted revision |

No cascade rewrite of historical snapshots is allowed merely because the master changed.

## UI Mutation Contract

Every sensitive edit surface must distinguish between:

- fields changed by the user;
- unchanged historical references;
- current master context;
- resulting domain effects.

A full-form HTTP request may technically contain unchanged fields, but the backend must not treat presence as semantic change.

Preferred flow:

```
load current accepted state
-> calculate semantic diff server-side
-> classify mutation intent
-> preflight domain effects
-> require reason/confirmation where sensitive
-> apply atomically
-> append version/audit
-> render current state plus history
```

For legacy references the UI should display a clear state such as:

- Active;
- Inactive;
- Merged -> canonical target;
- Reclassified;
- Historical reference only.

A blank dropdown/value because the current master query excludes a historical record is a UI correctness bug.

## Supplier Invoice Rule

Supplier invoice editing is a primary reference implementation.

### Metadata-only edit

If the user changes only `nomor_faktur`, the application must not require unchanged line products to still be active.

The accepted result is:

- new invoice version/audit entry where current versioning requires it;
- changed invoice number;
- unchanged historical lines;
- no stock delta;
- no costing delta;
- no payable delta except where invoice-number uniqueness/reference policy itself requires handling.

### Line/economic edit

If product, qty, cost, tax, or received economics change, use the supplier-invoice revision engine.

Historical line snapshots remain available.

If an unchanged line references a legacy product, it remains a valid historical line.

If the operator explicitly replaces that legacy product with a current product, that is a new revision decision and its inventory/cost/payable effects must be computed from actual current state.

## Reporting Contract

Reports must answer the question they claim to answer.

### Current-state reports

Use current accepted revisions/current projections/current eligible identities.

If historical provenance matters to interpretation, show the current canonical identity plus legacy/origin context.

Do not double count legacy and canonical identities after a merge.

### Historical/event reports

Use event-time identity/snapshots and immutable ledger chronology.

Historical movements for a now-inactive product remain visible when the report is asking what happened historically.

A blanket `WHERE products.deleted_at IS NULL` is invalid when it would erase historical business facts.

### Audit/detail views

Must be able to explain:

- original identity/value;
- later correction/merge/reclassification;
- reason;
- actor;
- time;
- operation/source reference;
- resulting current identity/state.

## Audit And Safety Requirements

Sensitive mutations require enough durable evidence to reconstruct why current truth differs from historical truth.

At minimum where relevant:

- actor;
- reason;
- occurred/changed time;
- base/current revision identity;
- before snapshot;
- after snapshot;
- affected entity ids;
- source operation id;
- canonical/replacement target;
- resulting inventory/cost/payment/payable movement ids;
- idempotency identity for retry-sensitive commands.

Unknown legacy state must fail safely. Do not guess missing stock, missing product lineage, missing payment linkage, or missing cost basis.

## Rejected Behaviors

Reject:

- generic CRUD overwrite across historically significant data;
- cascade-updating old documents to current master labels;
- making a historical document invalid because a referenced master is soft-deleted;
- metadata edit revalidating unchanged legacy lines as new input;
- hiding legacy rows from historical reports only because the current master is inactive;
- transferring stock by directly editing current quantity without a source operation;
- merge by text-only rename with no auditable current-state reconciliation;
- report queries used to conceal broken event/projection data;
- hard delete of referenced financial/inventory history;
- requiring operators to manually understand and repair all cross-table consequences.

## Required Proof Matrix

Future hardening must include at least:

1. rename product after historical invoice/receipt/transaction; old snapshots unchanged;
2. soft-delete product; old invoice/detail/history remains renderable;
3. metadata-only supplier invoice edit succeeds with unchanged soft-deleted line;
4. adding a new line cannot select the inactive product;
5. product merge transfers proven current stock exactly once;
6. merge preserves original invoice/receipt/movement identities;
7. explicit product replacement on received invoice creates revision deltas;
8. reclassification product -> service does not invent stock consequences;
9. current reports avoid duplicate legacy/canonical counting;
10. historical reports retain inactive-product events;
11. stale revision update cannot overwrite current accepted revision;
12. audit can explain old value -> reason -> actor -> current value;
13. projection rebuild reproduces current state from authoritative sources;
14. unknown legacy linkage fails without partial mutation.

## Consequences

CRUD becomes more complex internally but simpler and safer for operators.

The operator should be able to perform a normal business correction without learning which eight tables happen to reference a product id.

The system owns propagation, versioning, compensation, lineage, audit, and reporting semantics.

The permanent rule is:

> Past facts remain past facts. Current truth may change, but the transition must be explicit, reconstructable, and domain-correct.
