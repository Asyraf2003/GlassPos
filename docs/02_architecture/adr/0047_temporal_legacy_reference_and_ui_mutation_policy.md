# ADR-0047: Temporal Legacy Reference And UI Mutation Policy

Status: Accepted

Date: 2026-10-03

Deciders: Project Owner, Architecture Decision

Scope: CRUD / UI mutation / master data / legacy references / product identity / supplier invoices / inventory / product/service boundary / versioning / audit / reporting

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
- deactivating a wrongly cataloged product and creating a separate service;
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

Preserving the old invoice revisions is correct. However, the 2026-10-03 read-only audit found the CURRENT invoice still referencing A3GN5, without a dependent revision at merge time. That is an incomplete current-state migration, not the intended completed merge lifecycle. Paired stock movements alone do not prove merge completion or atomicity.

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

A historical reference must not become invalid merely because its current master is renamed, merged, or soft-deleted.

## Owner Clarification: Same-Product Merge And Current Truth

Locked by the owner on 2026-10-03:

- Merge means correcting duplicate identities of the SAME physical product. Different physical products remain separate products.
- After completed A -> B merge, B is the canonical identity in all relevant current dependent state, including current supplier invoice lines. Moving stock alone is insufficient.
- Existing version primitives preserve the transition: R1 A qty 5 remains immutable; merge creates R2 B qty 5 with actor/reason; current is B qty 5.
- Current lists/details prioritize B, with optional small previous-A context. History renders each revision's own snapshot.
- Subsequent B qty 5 -> B qty 10 is an ordinary supplier invoice revision producing B +5. No special A -> B routing belongs in that ordinary edit.
- Old revisions cannot be edited. Restoring an earlier value creates another revision.
- A product incorrectly cataloged instead of a service is deactivated; the correct service is created separately. No product_id -> service_id transformation or generic cross-domain merge is required.

Compatibility for metadata corrections and reading existing inactive references remains required. A CURRENT invoice still using inactive A after a claimed merge is an incomplete merge/current-state migration gap, not a permanent normal lifecycle. This ADR does not choose a new economic-edit policy for that incomplete state.

See [PR #77 owner-model audit](../../04_lifecycle/handoff/20261003_adr0047_owner_model_audit.md) for evidence and the implementation hold. This clarification records the domain outcome; it does not authorize a merge implementation expansion or data repair.

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

- explicit line product replacement (distinct from a master merge);
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
- deactivate a wrongly cataloged product and create the correct service separately.

Contract:

- current master changes control future use;
- historical snapshots remain unchanged;
- a completed duplicate merge transfers/reconciles relevant current stock and dependent current references to the canonical identity, preserving prior document revisions;
- merge requires reason, actor, time, source operation, old identity, and canonical target;
- product and service remain separate domains; deactivation does not transform historical product references into service references;
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

In a historical revision view, the current canonical label is explanatory context and never replaces the historical snapshot. In the CURRENT document view after completed merge, canonical B is the primary identity from the new accepted revision; previous A is optional context.

Historical transfer evidence includes product-version reasons plus paired `product_master_merge` movements. Neither prose nor paired movements alone authorizes a canonical relation.

### Explicit prior-transfer adoption (supplier invoice slice)

Owner-approved ProductCatalog persistence is `product_identity_merges`: unique operation ID, unique retired source product, canonical target product, actor, reason, occurred_at, and unique prior_stock_transfer_source_id. Source differs from target; both are ProductCatalog identities. Records are append-only through the application; conflicting source/operation claims reject, exact retries reuse the existing record. A future correction must be a separate explicit operation, never an update of the accepted historical relation; a correction workflow is not introduced in this slice.

The first command adopts an explicit same-physical-product merge whose stock transfer is already applied. It requires a retired source, active target, registered actor, explicit reason and transfer linkage. It verifies the supplied source/target against the paired transfer rather than discovering the target from movements, prose, codes or names. No automatic backfill is permitted.

Within one existing database transaction: record relation; lock affected current active supplier invoices; append canonical identity revisions with B snapshots; synchronize invoice projections; record audit and source-operation links. Identity-only revision preserves invoice economics and historical receipts/payments/versions. It creates no inventory movement or cost revaluation. A final locking ledger read requires no remaining A stock/value. Failure rolls back relation and all dependent writes; dry-run rolls back the same path. An exact rerun skips current B invoices.

Existing one-product-per-invoice-revision uniqueness remains in force. If an invoice already contains both A and B, the entire adoption rejects under that existing invariant. Consolidating quantity/tax/cost across those lines requires a separately defined correction policy; it is not silently implemented here.

This command is adoption/recovery for prior transfers, not a physical stock-transfer executor for a new merge. It does not claim to complete canonicalization in other domains. New physical merge and correction lifecycles remain separate work; no partially applied future lifecycle is exposed by this command.

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
| Merge duplicate of same physical product | keep old revision identity snapshot | old inactive, target canonical | reconcile stock and revise dependent current documents to canonical identity | use canonical target |
| Correct product entered instead of service | keep historical product facts | deactivate product; create separate service | no product-to-service transformation; no invented stock effects | use separate service catalog |
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

After completed duplicate merge, current invoice identity is already canonical B. Subsequent B quantity/cost edits use the ordinary revision engine. A current inactive A left behind by a merge is a migration gap; retaining its historical snapshot does not authorize new economic effects on A. Do not use ordinary invoice editing as an implicit merge repair or invent canonical routing without proven source state.

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
- later correction/merge/deactivation;
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
6. merge preserves old invoice revisions/receipt/movement identities and creates dependent current invoice revision B with actor/reason;
7. explicit product replacement on received invoice creates revision deltas;
8. correcting product-versus-service catalog mistakes keeps the domains separate and historical product facts intact;
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
