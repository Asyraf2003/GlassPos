# Temporal Legacy CRUD And Revision UI Blueprint

Status: Active blueprint for future UI hardening

Date: 2026-10-03

Governed by:

- [ADR-0047 Temporal Legacy Reference And UI Mutation Policy](../../02_architecture/adr/0047_temporal_legacy_reference_and_ui_mutation_policy.md)
- [ADR-0045 Transaction Revision Version Graph And Full-Layer Snapshot Contract](../../02_architecture/adr/0045_transaction_revision_version_graph_and_full_layer_snapshot_contract.md)

Scope: admin/cashier UI surfaces that create, edit, replace, merge, deactivate, report, or inspect persisted business data with historical references.

This blueprint does not authorize production data cleanup and does not invent new financial semantics. It defines the UI/application behavior required to keep current and historical truth coherent.

## Goal

The UI should feel like normal CRUD to the operator while the application handles the real lifecycle underneath.

The operator must not need to know every table that references `product_id`, every historical snapshot table, or every inventory/cost/payable consequence.

At the same time, a small visual edit must not silently trigger unrelated historical rewrites.

The target experience is:

```
user changes what is actually wrong
-> system understands mutation class
-> system previews meaningful consequences
-> system validates only relevant invariants
-> system creates required revision/effects
-> history remains explainable
```

## Owner-Locked Current Versus Historical Display

A duplicate merge joins identities of the same physical product only. Completed A -> B merge makes B current across relevant dependent documents, while old revisions keep A. Current list/detail shows B prominently; optional previous-A context is small. History can show R1 A qty 5, R2 B qty 5, R3 B qty 10.

A later B qty 5 -> B qty 10 uses the ordinary revision engine with B +5. Old revisions remain immutable; returning to an earlier value creates a new revision. An inactive A still in the current invoice after merge indicates incomplete current-state migration. The compatibility flow below permits reading and metadata correction of existing legacy data; it is not a permanent target for current economic identity.

Product/service are separate domains: deactivate the wrongly entered product and create a service separately. Do not build cross-domain conversion/lineage UI.

[PR #77 audit and hold](../../04_lifecycle/handoff/20261003_adr0047_owner_model_audit.md) records the observed incomplete state and economic risk. No policy for economic edits of that incomplete state is invented here.

## Core UI Vocabulary

Every screen that can encounter legacy data needs explicit concepts instead of blank/missing controls.

Recommended visible states:

- **Aktif**: eligible for new use.
- **Tidak aktif**: master exists historically but cannot be selected for new use.
- **Digabung**: legacy identity has a canonical replacement.
- **Historis**: valid only as an old reference/snapshot.
- **Revisi lama**: immutable older accepted document version.
- **Revisi saat ini**: current accepted operational version.
- **Dikoreksi**: current value differs from a prior accepted value through an explicit correction.

Avoid presenting a soft-deleted historical entity as an empty select value, `null`, or generic “data tidak ditemukan” when the snapshot exists.

## Mutation Router

UI submit must not map directly to one generic update path.

The application should derive a semantic diff against the current accepted state and route to one mutation class.

### M1: Metadata correction

Examples:

- supplier invoice number;
- external reference;
- administrative description;
- other explicitly non-economic header data.

Expected UI:

- ordinary edit control;
- optional/required reason according to sensitivity;
- clear note that stock/cost/payment is unaffected when true;
- submit only changed semantics, even if the browser posts full form state.

Expected backend path:

- validate changed metadata;
- preserve unchanged historical lines;
- append document version/audit where applicable;
- no inventory/cost/payable mutation.

### M2: Economic revision

Examples:

- product replacement;
- qty change;
- received cost change;
- tax change;
- line removal/addition after receipt.

Expected UI:

- impact preview before final confirmation;
- reason required;
- identify affected product/qty/value;
- warn if stock is already partially consumed or another current-state guard applies;
- submit against current revision identity.

Expected backend path:

- immutable revision;
- explicit stock delta/revaluation;
- costing/payable reconciliation;
- stale-edit protection;
- append audit.

### M3: Master lifecycle / identity change

Examples:

- rename product;
- merge duplicate product;
- deactivate product;
- deactivate a wrongly cataloged product; create the service separately.

Expected UI:

- do not present this as a harmless text edit when consequences exist;
- show dependent current-state summary when available;
- require reason for merge/deactivation;
- show canonical target for merge;
- show what will *not* be rewritten: historical invoices, receipts, revisions, movements.

Expected backend path:

- master current state change;
- duplicate merge reconciles current references to canonical identity and creates dependent document revisions;
- current stock transfer only when proven and required;
- historical snapshots untouched.

### M4: Projection repair

Examples:

- rebuild current inventory from ledger;
- rebuild costing projection;
- repair stale current read model.

Expected UI:

- admin-only maintenance/audit surface if exposed at all;
- preview counts/deltas;
- source-of-truth explanation;
- no pretending repair is ordinary product edit.

### M5: Cancellation / void / delete-like intent

Expected UI must name the real lifecycle action:

- Nonaktifkan master;
- Batalkan transaksi;
- Refund;
- Koreksi melalui revisi;
- Arsipkan.

Avoid one generic “Hapus” button for historically significant data.

## Supplier Invoice Reference Flow

Supplier invoice is the first concrete implementation target because it already exposes the failure mode.

### Existing legacy line in edit page

Compatibility case: when existing legacy/incomplete data still has a saved current invoice line referencing an inactive product. After completed merge, the current line must instead show canonical B from its new accepted revision.

Render the historical snapshot, for example:

```
A3GN5
PISTON GREND
HONDA / 50

Status: Digabung
Sekarang: A3GN520 / PISTON GREND / AHM / 50
```

Show a canonical target only when supported by machine-readable evidence; a free-text reason is insufficient for automatic resolution. Otherwise show the historical snapshot and inactive status only. The existing line must remain representable and submittable unchanged for metadata correction.

Do not require the historical product to appear in the active-product search endpoint just to preserve the line.

### Metadata-only invoice edit

Example:

```
ISS26041043 -> ISS-26041043
```

Behavior:

- detect that no line economics changed;
- preserve all line snapshots/references;
- do not require active product validation for unchanged lines;
- do not create stock movements;
- do not revalue inventory;
- do not alter payable amount;
- write version/audit evidence according to supplier-invoice version contract.

This is the characterization test that should reproduce and then prevent the current failure class.

### Economic edit after completed merge

The merge lifecycle has already created the current canonical line B qty 5. Editing it to B qty 10 is an ordinary economic revision with B +5 and existing costing/payable reconciliation.

Explicit product replacement remains an economic revision. It must not serve as an implicit repair of a partially completed master merge. If current data still uses inactive A after a claimed merge, record the migration gap; do not invent special A -> B routing or repeat historical transfer movements.

### Revision timeline

Supplier invoice detail/edit should eventually expose a compact revision timeline:

```
Revisi 1 - historical
Revisi 2 - historical
Revisi 3 - saat ini
```

Each revision should show:

- actor;
- timestamp;
- reason;
- changed fields summary;
- historical line snapshots;
- financial/stock effect summary where applicable.

Users may view/print/export older revisions for audit.

Operational actions must default to the current accepted revision and clearly label an older one as historical.

## Product Edit Flow

Product edit requires field-level semantics.

### Rename only

If only name/code/brand/size changes within accepted master policy:

- historical invoice/transaction snapshots stay unchanged;
- current master screens show the new value;
- detail/history can show previous values and changed-at/actor/reason;
- current reports use current identity where the report asks current state;
- historical reports retain event-time identity.

### Price change

- affects future/current price policy according to existing domain rules;
- must not retroactively rewrite historical invoice/transaction prices;
- package/template synchronization, if required by another accepted contract, is a separate explicit effect rather than accidental cascade.

### Merge duplicate

UI should require:

- source product;
- canonical target;
- reason;
- preview of current stock/costing references that require reconciliation;
- explicit note that historical documents remain unchanged.

After merge:

- source becomes unavailable for new use;
- target is canonical for current dependent state and new use;
- current supplier invoice receives a new revision using B, with actor/reason; old revision A remains immutable;
- historical source references remain readable;
- current stock transfer has explicit paired movement/source operation if required;
- current UI shows canonical B as primary; history shows A -> B across revisions.

### Product entered instead of service

Deactivate the incorrectly cataloged product and create the correct service in the separate service catalog. Keep historical product transactions unchanged. No product_id -> service_id conversion, generic cross-domain lineage, or invented stock reconciliation belongs in this flow.

## Create Flow

Create is not always one-row insertion.

Before a create command that has downstream effects, the UI/application must know the intended graph.

Examples:

- creating a received supplier invoice may create receipt/inventory/cost/payable consequences;
- creating a package may create references to product and service masters;
- creating a transaction may issue stock and create financial obligation.

Rules:

- new references resolve only to current eligible masters;
- UI should not silently select inactive legacy identities;
- server validates graph invariants atomically;
- partial create is rejected when required downstream effects fail;
- audit/source identity connects downstream effects to the originating command.

Do not generalize every create into a huge confirmation dialog. Preview only consequences that materially affect operator decisions.

## Delete / Deactivate Flow

Before exposing delete-like actions, classify the entity.

For referenced master data:

- prefer deactivate/soft-delete or explicit lifecycle;
- show reference/history count when useful;
- block destructive deletion when historical reconstruction would break;
- historical screens continue to resolve snapshot/reference.

For transactional roots:

- use domain cancellation/refund/revision contracts;
- do not map UI delete directly to SQL delete.

## Current And Historical Presentation

A single detail page may need to show both truths without confusing the operator.

Recommended pattern:

### Primary current card

Show current operational state first.

Example:

```
Produk saat ini
A3GN520 - PISTON GREND - AHM - 50
Aktif
```

### Historical context

Show old identity only when relevant:

```
Asal historis
A3GN5 - PISTON GREND - HONDA - 50
Digabung pada 29 Sep 2026
Alasan: duplicate master correction
```

Do not drown ordinary users in audit data when there is no legacy condition.

Complexity should appear when it explains an anomaly or a sensitive mutation.

## Reporting Rules For UI

### Current-state report

Question: “Apa keadaan sekarang?”

Use:

- current accepted revision;
- current projection;
- current canonical identity.

Where lineage affects understanding, add compact legacy context, not duplicate rows.

Example:

```
A3GN520 - qty 30
asal merge: +10 dari A3GN5
```

### Historical/event report

Question: “Apa yang terjadi waktu itu?”

Use:

- event-time snapshot/reference;
- immutable movement/payment/refund chronology.

A now-inactive product is still visible when it participated historically.

### Audit report/detail

Question: “Mengapa keadaan sekarang berbeda dari dulu?”

Show transition:

```
old value -> action/reason/actor/time -> current value
```

This is the place for full lineage/version context.

## Seed/Legacy Data Incident Guardrail

The 2026-10-03 local audit is a characterization case for provenance-aware UI and maintenance flows.

Observed:

- 200 `opening_stock_seed` products;
- 6,820 synthetic qty;
- Rp698,525,264 synthetic movement value;
- 106 products with real movement history plus false seed movement;
- 94 products whose qty/costing were entirely seed-created;
- all 94 remained active legitimate product masters with no delete/merge/service history.

UI/reporting must not equate “row exists and product is active” with “stock is business-proven”.

Maintenance/repair screens, if added, must show provenance/source type and must not issue cleanup from this blueprint without a separate repair proof.

## Server-Side Semantic Diff

Do not trust UI field disabling alone.

On update:

1. load authoritative current accepted state;
2. compare normalized submitted state;
3. determine changed semantic fields;
4. classify mutation M1-M5;
5. enforce class-specific validation;
6. lock authoritative roots when required;
7. write effects atomically;
8. append version/audit;
9. return current accepted state.

Presence of an unchanged `product_id` in the request is not proof of product replacement.

This rule prevents full-form submits from accidentally converting legacy historical references into invalid “new selections”.

## UI Safety Requirements

Sensitive flows should provide the minimum useful safety, not bureaucratic theater.

Use when relevant:

- reason required;
- current revision/version token;
- stale-edit conflict message;
- effect preview;
- canonical target preview;
- warning for inactive historical references;
- explicit confirmation for destructive/economic effects;
- server-generated current values after save.

Do not require a confirmation modal for harmless metadata corrections merely because the underlying domain is complex.

## Error Handling Contract

Errors must explain the business boundary.

Bad:

- `product_id invalid` when an unchanged historical product was soft-deleted;
- empty product field;
- generic 422 with no distinction between stale version and inactive new selection.

Preferred:

- “Produk ini merupakan referensi historis dan tetap dipertahankan. Pilih produk aktif hanya jika Anda ingin mengganti barang.”
- “Faktur sudah berubah sejak halaman ini dibuka. Muat ulang revisi saat ini sebelum menyimpan.”
- “Perubahan qty tidak dapat diterapkan karena stok saat ini tidak cukup untuk koreksi.”

## Test Matrix

### Supplier invoice

1. metadata-only invoice-number edit with active lines;
2. metadata-only edit with soft-deleted historical product line;
3. metadata-only edit with merged historical product line;
4. unchanged legacy line posts successfully without stock/cost/payable effect;
5. new line cannot choose inactive product;
6. completed duplicate merge creates current canonical invoice revision; later canonical edits use the ordinary revision engine;
7. qty increase/decrease produces exact movement delta;
8. cost-only revision produces required revaluation without qty movement;
9. stale revision submit rejects without partial effect;
10. revision timeline shows before/after actor/reason.

### Product master

11. rename does not rewrite historical supplier invoice snapshot;
12. rename does not rewrite historical transaction snapshot;
13. soft-delete preserves old detail/report visibility;
14. merge requires canonical target/reason;
15. merge transfers current stock exactly once when required;
16. merge does not rewrite old receipt/invoice IDs;
17. wrong product can be deactivated without rewriting historical product facts;
18. correct service is created separately, without product-to-service conversion.

### Reporting

19. current report does not double count legacy + canonical after merge;
20. historical movement report still shows inactive product events;
21. current report can expose lineage context without changing totals;
22. revision report can render old and current names independently.

### Audit/provenance

23. every sensitive accepted mutation records actor/reason/time;
24. source operation links paired/delta effects;
25. duplicate retry cannot create duplicate merge/revision movement;
26. unknown provenance cannot be silently treated as zero/empty/current.

### UI/browser

27. legacy product renders nonblank value in edit form;
28. legacy badge and canonical target are understandable on desktop/mobile;
29. metadata-only edit has no unnecessary economic warning;
30. economic edit clearly previews affected qty/value;
31. viewing an old revision is visually distinct from current revision;
32. print/export from an old revision is labeled historical.

## Supplier Invoice Canonicalization Delivery Boundary

The explicit backend command `products:adopt-transferred-merge` adopts an owner-authorized duplicate identity relation and prior transfer linkage; it discovers affected current active invoices automatically. It is dry-run by default and requires `--apply` plus explicit same-physical-product attestation to commit. No per-invoice UI repair is required.

After success, existing current edit/detail rendering reads canonical B from the new accepted line snapshot. The existing version timeline preserves A and shows B in the next revision, with actor/reason. History can be expanded for browser printing. No new dense legacy narrative is added to current screens. The existing invoice list is header/total oriented, not a product-line list; its projections remain synchronized.

The command does not infer mappings or initiate stock transfers. Unknown evidence, remaining source stock/value, or an A/B line collision rejects atomically rather than choosing new business semantics.

## Suggested Implementation Order

Do not attempt all CRUD surfaces at once.

### Slice 1: Supplier invoice metadata edit boundary

Characterize current bug, then allow invoice-number-only correction with unchanged legacy lines.

No new lineage schema required for this first slice if the line snapshot already exists.

### Slice 2: Legacy product rendering

Edit/detail pages render inactive historical lines without blank controls.

### Slice 3: Revision timeline UX

Expose supplier invoice versions with current/historical labels and changed-field summary.

### Slice 4: Product master lifecycle UI

Rename/deactivate/merge impact presentation and explicit mutation routing.

### Slice 5: Machine-readable lineage

Only after current persistence is audited. Do not create a second lineage mechanism if one already exists.

### Slice 6: Reporting lineage presentation

Current versus historical report semantics with no duplicate totals.

## Definition Of Done

A slice is complete only when:

- current and historical semantics are explicitly identified;
- the UI does not lose legacy references;
- backend semantic diff routes only the intended mutation;
- unrelated ledgers remain unchanged;
- required delta/compensation effects are atomic;
- reason/actor/version evidence is durable;
- current report totals remain correct;
- historical report/detail remains reconstructable;
- focused regression and browser proof cover the legacy case;
- no cleanup or cascade rewrite is smuggled into a UI patch.

## Non-Goals

This blueprint does not:

- rewrite all existing CRUD immediately;
- mandate event sourcing for the whole application;
- require version tables for every master entity;
- authorize seed cleanup;
- redefine costing, refund, payment, or cancellation semantics;
- make every old revision operationally editable;
- expose internal database complexity to ordinary operators.

The operator gets a simple workflow.

The system carries the complicated history.
