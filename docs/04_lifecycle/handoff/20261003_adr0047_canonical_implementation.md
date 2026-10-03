# Supplier invoice canonicalization implementation checkpoint

## Target / locked decisions

Owner approved minimal ProductCatalog authoritative A -> B relation, explicit same-physical-product meaning, no automatic backfill/inference. Current supplier invoices must append B revision while old A survives. Identity-only canonicalization must not replay prior stock transfer or change invoice qty/value/payment/payable/receipts. Ordinary later edits operate on B. Preserve PR #77 metadata compatibility.

Implementation boundary: explicit adoption of an already-applied, verified paired `product_master_merge` transfer. Relation + all affected active invoice revisions/projection sync + audit share existing TransactionManagerPort transaction. It creates no stock movement or cost adjustment. This is NOT a new physical merge executor or whole-domain merge lifecycle. No real business-data adoption has been run.

## Git / state

Worktree `/home/asus/projects/GlassPos-adr0047`, branch `fix/adr0047-supplier-invoice-metadata`. Starting HEAD `76b7363354bb7f7daa94c3fd68aa3d8012880528`; origin/main baseline `9d16c3819532ab768a7a7a63c6d9b862165eb90d`. Dashboard worktree preserved at c170c8a2. PR #77 open, not merged. This checkpoint will be committed with implementation; use git log for containing SHA.

Previous [mapping gate](20261003_adr0047_canonical_mapping_gate.md) is RESOLVED by explicit owner approval. Previous audit remains proof of original risk, not approved steady-state semantics.

## Implementation so far (not final verified delivery)

- New `product_identity_merges` table: immutable operation ID, unique source, canonical target, actor/reason/time, unique prior transfer source ID; product FKs and source != target CHECK.
- ProductIdentityMerge DTO/port/database adapter: explicit inputs only, retired A/active B, valid actor, no conflicting target/op/claimed transfer; matching two movement rows validate qty/value conservation. No inferred relation.
- Read method deterministic by source; exact request retry returns existing relation, no append.
- AdoptTransferredProductMergeHandler wraps relation + invoice batch + audit in transaction; defaults only CLI to dry-run; transaction context is cleared on success/failure.
- CanonicalizeSupplierInvoiceProduct locks current active invoice roots, replaces only matching product identity/snapshot in NEW line rows, preserves every line's qty/value/unit cost/tax/residue, uses existing writer/version/audit. No revision inventory engine invocation.
- Existing version writer preserves stored header fields for source-operation identity revisions except last_revision_no; before/after snapshots remain existing primitives. Invoice audit correlation/metadata links merge operation and before/after revision numbers.
- Final locking ledger read after invoice locks rejects remaining A qty/value, including concurrent changes committed while waiting. No stock repair invented.
- CLI `products:adopt-transferred-merge` requires explicit IDs, reason, actor, prior transfer, and `--same-physical-product`; dry-run rolls back all effects, `--apply` commits.
- Existing duplicate-product-per-invoice-revision constraint remains intact. An invoice containing BOTH A and B fails atomically; no line consolidation/tax policy invented. This limitation needs owner semantics if encountered in intended data.

## Latest checkpoint — 2026-10-04

- Full procurement regression PASS: 330 tests / 2350 assertions (28.20s).
- Real Chromium proof PASS: current B edit, metadata save, current B detail, expanded historical A -> B snapshots, PDF print and mobile B. Synthetic database only, no real-data mapping/adoption.
- Browser first assertion assumed name-before-code display order; corrected harness to actual code-before-name, no production UI change.
- Initial make verify run ended without final result during session interruption (no process remains); NOT a pass. Restarted full `DB_DATABASE=glasspos_adr0047_test make verify`, log `/tmp/adr0047-canonical-verify-final.log`, explicit VERIFY_EXIT marker required.
- Audit-hex still reports only the two known unchanged baseline BulkProductMaintenance DB-facade imports; no new hex violation. Diff whitespace and line-count checks passed.
- Implementation checkpoint pushed while final full verification remains pending. Continue rather than treating this checkpoint as final delivery.

## Proof to date

- RED first canonicalization test failed: missing AdoptTransferredProductMergeHandler (expected).
- First GREEN: 1 test / 13 assertions.
- Expanded canonicalization + safety + legacy metadata: 21 tests / 137 assertions PASS (13.33s).
- Includes canonical-only ordinary economic delta, unchanged old version, immutable economic tables, multiple invoices, exact retry, conflict, dry-run, missing target/transfer, duplicate-line aggregate rollback.
- Initial configured PHPStan passed before latest extra tests/locking refinement.
- Initial multi-invoice fixture failed on generated column insert; corrected fixture omits active_nomor_faktur_normalized. No production workaround.
- Additional actor/residual ledger, audit failure rollback, CLI, header equality tests added; full procurement run underway at checkpoint. `/tmp/adr0047-canonical-procurement.log`.

## Files changed

New DTO + use case in Application/ProductCatalog; new canonicalization service in Application/Procurement; new ProductCatalog and Procurement ports/adapters; ProductCatalog provider binding; SupplierInvoiceChangeContext and existing writer/history traits; one schema migration; CLI command; two Feature/Procurement test files and one shared test fixture. This handoff. No dashboard/refund/payment engine/seed cleanup changes.

## Unfinished / ONE next target

ONE next target: finish verification and review of the explicit prior-transfer adoption slice. Remaining: final targeted + full procurement, PHPStan/contract audits, `make verify`, browser current B + old A history/print proof, boundary review, durable docs/PR update and clean push. Do NOT claim future physical merge executor is implemented. No automatic backfill or real-data mutation.

Starting commands (worktree above):

```bash
git status --short --branch
git log -3 --oneline
cat docs/04_lifecycle/handoff/20261003_adr0047_canonical_implementation.md
tail -50 /tmp/adr0047-canonical-procurement.log
DB_DATABASE=glasspos_adr0047_test php artisan test tests/Feature/Procurement
DB_DATABASE=glasspos_adr0047_test make verify
```

Browser proof will use a dedicated synthetic database/fixture, never real invoices. Prior fixture/harness live in tests/Browser/supplier-invoice-legacy*. Do not run that old harness after canonicalizing its fixture; use a separate canonical fixture/harness so legacy proof remains reproducible.
