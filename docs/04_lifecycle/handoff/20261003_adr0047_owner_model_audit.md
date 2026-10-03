# ADR-0047 / PR #77 owner-model audit and handoff

**MERGED:** PR #77 merged at `4c9ee9b57ea45a6f431a2dad49b3bf10b756eba4`. Read [code-only merged closeout](20261004_pr77_merged.md) first; earlier OPEN/hold/next-target status below is historical. No real business-data adoption was run.

Latest delivery: [verified canonical implementation](20261003_adr0047_canonical_implementation.md). Owner approved persistence and explicit prior-transfer adoption is now implemented/tested. Earlier gate/hold sections below are historical; PR remains open for review.

Latest continuation: [canonical mapping discovery gate](20261003_adr0047_canonical_mapping_gate.md). Owner selected current invoice canonicalization; implementation is awaiting the explicitly required persistence decision because no authoritative A -> B relation contract was found.

## Status and exact target

Audit PR #77 against the owner's same-physical-product merge model. Characterize inactive-current A qty 5 -> 10, inspect actual merge evidence, distinguish immutable historical A from incorrect current A after completed merge, and record owner decisions. STOP before production logic changes, merge-lifecycle expansion, data repair, or merging PR #77.

Audit evidence is ready for owner review. Merge implementation provenance/atomicity remains OPEN; do not call that part of discovery complete. PR #77 should remain on hold pending review of the concrete economic-reference risk below. Passing characterization documents that risk; it does not approve the behavior.

## Git / continuity

- Worktree: `/home/asus/projects/GlassPos-adr0047`.
- Branch: `fix/adr0047-supplier-invoice-metadata`.
- Audited HEAD: `fe79be6b7a4ad0c5fb1d861c7f96e2b534c16c3d`.
- Implementation commit: `14f7093ee6d7465df6ea2b73d162251024a5259e`.
- Locked origin/main: `9d16c3819532ab768a7a7a63c6d9b862165eb90d`.
- This handoff, ADR/blueprint clarification, and characterization test form a subsequent audit commit. Run `git log -1` for its exact SHA; no production source changes are included.
- PR: https://github.com/Asyraf2003/GlassPos/pull/77 (OPEN; do not merge).
- Existing issue: https://github.com/Asyraf2003/GlassPos/issues/76.
- Original dashboard worktree `/home/asus/projects/GlassPos` remains on `fix/57-dashboard-finance-port`, SHA `c170c8a2dfff36de9d16b79364f44efcba3109db`. No dashboard changes.
- Earlier implementation/browser/full-verification proof: [baseline handoff](20261003_adr0047_supplier_invoice_baseline.md). Its earlier completion narrative does not override this audit hold.

## Locked owner decisions

1. Merge corrects duplicate identities of the same physical product; different goods remain different products.
2. Completed A -> B merge makes B current across relevant dependent state, including current supplier invoice lines.
3. R1 A qty 5 remains immutable. Merge creates R2 B qty 5 with actor/reason. Current UI prioritizes B; history explains A -> B.
4. Subsequent B qty 5 -> 10 uses ordinary economic revision and B +5. No special legacy routing is needed in ordinary editing after completed merge.
5. Old revisions are never edited; restoring an old value creates another revision.
6. Wrong product-versus-service classification means deactivate product and create separate service. No cross-domain transformation engine.
7. Metadata compatibility for existing inactive references remains required. Current inactive A after a claimed merge is a migration gap, not the permanent target.
8. Owner has NOT selected a new economic-edit policy for incomplete state. This audit implements no block, reroute, repair, or additional lifecycle policy.

## A. PR #77 behavior classification

| Behavior | Classification / owner-model assessment |
|---|---|
| Metadata-only invoice-number edit preserves unchanged inactive lines and snapshots | Required compatibility for existing legacy/incomplete data |
| Historical snapshot rendering on edit/detail; inactive badge when active catalog excludes master | Required compatibility; not a claim that inactive A is intended permanent current identity |
| Active-only new product lookup; reject added/replaced inactive references | Permanent normal lifecycle invariant |
| Semantic diff under invoice lock, metadata-only persistence, immutable version/audit actor/reason, stale revision guard | Permanent normal lifecycle primitives |
| Ordinary economic revision through existing delta/revaluation/payable engine | Correct for valid current identities; completed merge should already have B |
| Same previous-line ID + same product ID skips active eligibility even when qty/cost changes | Overbroad continuity allowance: permits new economic effects on inactive current A; concrete risk reproduced |
| Canonical merge revises dependent current invoices A -> B | Not implemented by PR #77 |

## B. Proven economic mutation mechanism and result

`SupplierInvoiceFactory::makeLines()` maps authoritative current lines by ID. Matching `previous_line_id` and unchanged `product_id` supplies `$previous`; `ProductReaderPort::getById()` runs only when `$previous` is null. Qty and cost equality are NOT conditions of that eligibility exemption.

`UpdatedSupplierInvoiceBuilder` supplies the current lines to this factory. `UpdateSupplierInvoiceOperation` correctly sends non-metadata edits into the existing revision engine. `SupplierInvoiceRevisionPairedLineDeltaResolver::resolve()` sees old/new product A as equal, computes quantity delta, and creates movements for A. `SupplierInvoiceRevisionInventoryEffectsApplier::apply()` writes those movements and rebuilds inventory/costing for the affected identity. There is no canonical resolution here.

Characterization: `tests/Feature/Procurement/SupplierInvoiceInactiveEconomicAuditFeatureTest.php`, method `test_pr77_accepts_inactive_current_quantity_increase_and_posts_delta_to_old_identity`.

Fixture uses isolated test DB, not business data. It models already-persisted incomplete state, NOT the unknown merge command: received invoice A qty 5/value 50,000, payment 5,000; paired transfer A -5/B +5; A inactive; A stock/value 0; B stock 5/value 50,000. Submit actual PUT update route with the same current line/product A, qty 10/value 100,000, expected revision 1, actor and reason.

Observed/asserted:

| Surface | Actual PR #77 result |
|---|---|
| HTTP result | Accepted with success; no validation error |
| Inventory movement | `supplier_invoice_revision_delta_line`, A +5, value +50,000 |
| A current stock/cost | qty 5, inventory value 50,000, average cost 10,000 |
| B current stock/cost | remains qty 5, value 50,000, average cost 10,000; no revision delta to B |
| Payable | accepted invoice total 100,000; payment remains 5,000; list outstanding 95,000 |
| Revision/version | new revision 2 still A qty 10; actor and reason retained |
| Audit | before snapshot A qty 5; version after snapshot A qty 10 |
| Old line / receipt | superseded old line remains A qty 5; receipt remains qty 5 |
| Master | A remains soft-deleted; no resurrection |

Risk is concrete: new stock/value appears under inactive A after a transfer to B, splitting operational identity again. Versioning/audit accurately records the action, but does not make its current identity match the owner's completed-merge model. Payable follows the economic edit, not the metadata path. Do not describe this as a metadata side effect.

## C. Actual merge audit: source versus persisted evidence

### Source located / source not located

Search of tracked app/scripts/routes/tests/database, repo history, and both local worktrees did not locate an implementation emitting `product_master_merge`. The new characterization fixture is not production merge code. An asynchronous request for the original script/command/commit was sent to the owner; no provenance answer is available at audit close.

Existing master correction code is not evidence of a merge lifecycle:

- `BulkProductMaintenanceValidator::ACTIONS` supports UPDATE_PRICE, DELETE, SKIP_UNKNOWN_PRICE, UNCHANGED; no MERGE.
- `BulkProductMaintenanceRunner::run()` wraps its product row processing in `DB::transaction` and locks product rows.
- `BulkProductMaintenanceRowApplier::apply()` invokes update or soft-delete handlers; it does not revise dependent invoices or transfer inventory.
- `SoftDeletesProducts::softDelete()` transaction updates product deletion state and writes product version/audit. It does not perform canonical transfer or dependent-document revision.
- `ProductLifecyclePort` exposes softDelete/restore, not a canonical merge operation.

These paths prove ordinary product soft-delete behavior and its local transaction boundary, NOT the atomicity of the historical `product_master_merge` operation.

### Read-only local business database evidence

Queried via PDO `START TRANSACTION READ ONLY`, SELECT/SHOW only, followed by rollback. Credentials were read privately from the original local `.env`, never printed/committed. No production server was queried, and no local business row was mutated.

- A: `c824fe66-71d5-4d86-89b5-03bdb3476b5b` / A3GN5, inactive since `2026-09-29 14:25:45`.
- B: `137b8787-7b3e-425e-b4b0-ab286cd8118a` / A3GN520, active.
- Paired movement `source_type=product_master_merge`, shared operation `8087a690-0324-429e-bc87-3887d4bd03bb`: A -10/value -1,236,700; B +10/value +1,236,700; same created timestamp as A deletion.
- A product version R4 records `product_soft_deleted` with reason: “Merge duplicate PISTON GREND HONDA ke master AHM A3GN520 sesuai koreksi client.” Its snapshot includes deletion and product fields, not a machine-readable target-product/merge-operation link.
- B's latest product version is R3 at 2026-09-11; no merge-specific B version was found.
- Invoice `5b6b8e6b-0ea7-4ef4-845d-f76f3e45578d` has `last_revision_no=3`. Its A line `42560753-59d9-4d14-9266-9bbe5f573993` is still `is_current=1`, qty 10.
- Invoice versions R1/R2/R3 all reference A and were written on 2026-04-22; latest at 08:57:32. No dependent invoice revision at/after September merge.

| A references found by scanning columns named product_id / product_id_snapshot | Count | Meaning |
|---|---:|---|
| supplier_invoice_lines | 3 | R1/R2 historical A is correct; R3 CURRENT A is migration gap |
| inventory_movements | 4 | Immutable historical events including transfer; preserve |
| product_versions | 4 | Historical product snapshots; preserve |
| product_inventory | 1 | Projection row key retained; row existence alone is not proof of positive stock |
| product_inventory_costing | 1 | Projection row key retained; row existence alone is not proof of positive value |

Other scanned direct-reference columns had no A matches. This is NOT proof that a merge implementation migrates other transactions, nor an exhaustive JSON/indirect-reference audit. Current supplier invoice read/edit derives from `is_current` line rows, so it will still read A. Product-selection queries exclude A because the master is inactive. A supplier-invoice header/list total projection is not evidence of product identity migration.

Conclusion: persisted transfer/deletion and a missed CURRENT invoice revision are proven. Exact merge source, atomicity, retry behavior, and general migration coverage remain unknown. Shared operation ID and timestamps do not prove a single database transaction.

### Minimal read-only reproduction SQL

Run only on the intended local database with read-only access; actual values above are observations, not hardcoded production behavior:

```sql
START TRANSACTION READ ONLY;
SELECT id, kode_barang, deleted_at FROM products
WHERE kode_barang IN ('A3GN5', 'A3GN520');
SELECT product_id, source_type, source_id, qty_delta, total_cost_rupiah, created_at
FROM inventory_movements
WHERE source_id = '8087a690-0324-429e-bc87-3887d4bd03bb';
SELECT l.id, l.product_id, l.revision_no, l.is_current, l.qty_pcs, i.last_revision_no
FROM supplier_invoice_lines l JOIN supplier_invoices i ON i.id = l.supplier_invoice_id
WHERE l.supplier_invoice_id = '5b6b8e6b-0ea7-4ef4-845d-f76f3e45578d'
AND l.product_id = 'c824fe66-71d5-4d86-89b5-03bdb3476b5b';
SELECT revision_no, event_name, changed_at, change_reason, snapshot_json
FROM supplier_invoice_versions
WHERE supplier_invoice_id = '5b6b8e6b-0ea7-4ef4-845d-f76f3e45578d'
ORDER BY revision_no;
ROLLBACK;
```

## Match / gap / recommended scope (not owner approval)

KEEP in PR #77: metadata-only compatibility, historical snapshot rendering, rejection of inactive new selections, semantic diff, no metadata economic effects, stale guard, existing version/audit primitives.

HOLD: same-reference continuity currently also admits changed economics on inactive A. Owner review must settle the bounded response for incomplete current state before calling PR #77 ready; this audit does not silently select block/reroute/repair behavior.

NEXT SEPARATE SLICE recommended: locate/recover actual merge implementation, then implement same-physical-product canonical merge that revises dependent CURRENT documents to B with durable actor/reason while preserving old versions and existing inventory/costing semantics. Prove atomicity/idempotency and ordinary B qty delta afterward. Any legacy data repair needs separate authorization/proof. No generic product-service engine, opening-stock cleanup, or dashboard scope.

## Files changed in this audit

- ADR-0047: owner clarification; distinguish correct old revisions from missed current migration; remove cross-domain merge implication.
- Blueprint UI 0019: canonical current UI, compatibility-only inactive-current case, ordinary post-merge economic edit, separate service catalog correction.
- This audit/handoff.
- Previous baseline handoff: pointer to this superseding audit hold.
- `tests/Feature/Procurement/SupplierInvoiceInactiveEconomicAuditFeatureTest.php`: isolated characterization of risk, explicitly not approved policy.

No production PHP, UI implementation, schema, business data, dashboard files, or existing tests changed.

## Proof and limitations

- New characterization alone: PASS, 1 test / 20 assertions, 17.68 seconds.
- Characterization + existing legacy metadata suite: PASS, 13 tests / 94 assertions, 20.15 seconds.
- PHP syntax check of new test: PASS.
- Full configured PHPStan: PASS, no errors. `git diff --check`: PASS. Self-review confirmed this audit changes only docs and one characterization test; production code remains identical to audited HEAD.
- Prior implementation `make verify`: PASS 1881 tests / 14704 assertions and 86 frontend tests, as recorded in baseline handoff. Not rerun for this docs + characterization audit; do not present prior counts as a fresh full run.
- Prior isolated Chromium desktop/mobile metadata edit/detail/history proof remains valid for unchanged production code. This audit did NOT submit an economic edit against the real legacy invoice and did NOT execute the unknown merge command.
- Sequential stale guard remains covered by the existing suite; simultaneous two-connection contention is still not tested.
- Raw logs under `/tmp/adr0047-owner-audit-*.log` and read-only evidence under `/tmp/adr0047-merge-evidence.jsonl` are temporary. Durable facts/results/reproduction are above.

## Unfinished work / ONE next target

ONE next target: owner review of this audit to settle PR #77 scope/handling of incomplete-current economic edits. Obtain original merge script/command/commit to close provenance and atomicity audit. Do not implement merge expansion or merge PR #77 before that review.

Starting commands (execution context: `/home/asus/projects/GlassPos-adr0047`):

```bash
git status --short --branch
git log -3 --oneline
git rev-parse HEAD origin/main
gh pr view 77 --repo Asyraf2003/GlassPos --json state,url,headRefOid
cat docs/04_lifecycle/handoff/20261003_adr0047_owner_model_audit.md
DB_DATABASE=glasspos_adr0047_test php artisan test tests/Feature/Procurement/SupplierInvoiceInactiveEconomicAuditFeatureTest.php tests/Feature/Procurement/SupplierInvoiceLegacyMetadataFeatureTest.php
```

Read ADR-0047 and Blueprint UI 0019 owner clarification before further work. Closed metadata characterization/browser discovery should not be repeated absent contradictory evidence. The new characterization asserts current risky behavior and must be revised, not preserved as a desired invariant, if the owner approves a changed policy.
