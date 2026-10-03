# Supplier invoice canonicalization — implementation delivery

## Latest status (2026-10-04)

Explicit prior-transfer adoption slice implemented and verified. Implementation commit `7602f11b` is pushed on `fix/adr0047-supplier-invoice-metadata`; this closeout is a subsequent docs commit. PR #77 remains OPEN for review, not merged/deployed. No real business mapping/invoice was migrated.

## Exact target / locked decisions

Owner approved minimal ProductCatalog authoritative A -> B relation for duplicate identities of the SAME physical product, with actor/reason/time and prior-transfer linkage. No inference or automatic backfill. Current supplier invoices append B revision; old A remains immutable. Identity-only canonicalization must not replay stock transfer or change qty/value/cost/payable/payment/receipts. Later ordinary edits operate on B. Keep PR #77 metadata compatibility, inactive new-selection rejection and stale guard.

Scope delivered: explicit adoption/recovery of a previously applied, verified paired `product_master_merge` transfer. New physical product-merge execution and other dependent domains are outside this slice. This boundary was stated before implementation; the command cannot expose a partially implemented new physical merge as completed.

## Git / continuity

- Worktree: `/home/asus/projects/GlassPos-adr0047`.
- Branch: `fix/adr0047-supplier-invoice-metadata`.
- Implementation HEAD: `7602f11b` (use `git rev-parse 7602f11b` for full SHA); `git log -1` identifies the containing documentation closeout SHA.
- origin/main baseline: `9d16c3819532ab768a7a7a63c6d9b862165eb90d`.
- Original dashboard worktree `/home/asus/projects/GlassPos` remains at `c170c8a2dfff36de9d16b79364f44efcba3109db`, branch `fix/57-dashboard-finance-port`.
- PR: https://github.com/Asyraf2003/GlassPos/pull/77. Issue: https://github.com/Asyraf2003/GlassPos/issues/76.
- [Mapping gate](20261003_adr0047_canonical_mapping_gate.md) is resolved by owner approval. [Earlier audit](20261003_adr0047_owner_model_audit.md) is historical evidence of the pre-canonicalization risk, not the intended steady-state behavior.

## Proven root cause / implementation

PR #77 initially exempted a matching current line/product from active lookup even during qty changes. Existing delta resolver consequently posted A +qty after physical stock had moved to B. Prior read-only audit found physical transfer and deletion prose but no authoritative canonical relation or dependent invoice revision. Original merge script remains unavailable.

Implemented:

- `product_identity_merges`: unique operation ID, unique source, canonical target, actor/reason/time, unique prior transfer source ID. Product FKs and source != target CHECK. No backfill migration.
- ProductIdentityMerge DTO/port/adapter: explicit normalized input only; retired A/active B, registered actor, no conflicting source/operation/transfer claim; supplied A/B verified against matching paired movement qty/value. It never discovers B from those rows or prose/similarity.
- Deterministic relation read by source; exact request retry reuses relation. No update/delete command for accepted historical operations.
- `AdoptTransferredProductMergeHandler`: one existing TransactionManagerPort transaction for relation, all affected active invoice revisions, projection sync and durable audit outbox. Failure rolls everything back; context is always cleared.
- `CanonicalizeSupplierInvoiceProduct`: finds and locks current active invoice roots; creates new line IDs and canonical B labels only for matching A, preserves other snapshots and all line economics including unit cost/tax/residue, uses existing version/audit writer. No invocation of economic inventory reconciliation.
- Existing writer retains stored header columns for identity-only revisions except revision number. Invoice before/after audit snapshots and correlation metadata reference the source merge operation and before/after revision numbers.
- Final locking ledger read after invoice locks rejects any remaining A stock/value, including committed changes while waiting. No repair of remaining stock is invented.
- CLI `products:adopt-transferred-merge`: explicit IDs/actor/reason/prior transfer plus same-physical-product attestation. Default dry-run executes the transactional path then rolls back; `--apply` commits. Rerun skips already-B invoices.

Atomic boundary is the local application database transaction, including relation, invoice/current rows, versions, invoice audit, merge audit outbox and invoice projections. Historical physical transfer already happened and is only referenced/validated; this command neither replays it nor claims retroactive atomicity with it. Audit outbox delivery follows existing infrastructure.

## Proof

- RED canonicalization test: failed because AdoptTransferredProductMergeHandler did not exist (before implementation).
- First GREEN: 1 test / 13 assertions.
- Intermediate canonicalization + safety + metadata: 21 tests / 137 assertions PASS.
- Full procurement regression: **330 tests / 2350 assertions PASS**, 28.20s.
- Final `DB_DATABASE=glasspos_adr0047_test make verify`: **1894 tests / 14820 assertions PASS**, PHP suite 484.84s, **86 frontend tests PASS**, configured PHPStan and contract audits PASS, **VERIFY_EXIT=0**.
- Real Chromium desktop/mobile PASS: current B edit, metadata save, current B detail, expanded historical A -> B snapshots and reason, PDF printing, mobile B.
- Browser database readback: R1 line A qty 2/value 20,000 historical; R2 line B qty 2/value 20,000 current; R3 metadata version. Exactly three original movements (receipt A +2, prior transfer A -2/B +2), payable 15,000, invoice total 20,000. No canonicalization movement/revaluation.
- Current edit and expanded detail/history screenshots visually inspected. PDF generated from expanded history; no standalone old-revision export feature was introduced. PDF text extraction was not separately verified (extractor unavailable).
- Tests prove same-ID rejection, missing target/transfer/actor rejection, conflicting target, no inferred relation, exact retry, multiple invoices, already-B skip, unchanged old version, no economic table changes, dry-run, audit-failure rollback, existing duplicate-line rejection, source residual stock/value rejection, and ordinary B quantity delta after adoption. Existing metadata and sequential stale tests remain green.
- Self-review: no broad withTrashed/validation bypass, no historical rewrite, no dashboard/refund/payment engine/opening-stock changes. Diff whitespace and line count passed.
- Supplemental `audit-hex` has only the two known unchanged baseline DB facade imports in BulkProductMaintenanceRunner and BulkProductMaintenanceValidator. No new hex violation.

## Failed experiments / proof limits

- First multi-invoice fixture copied a generated invoice column; corrected the fixture to omit active_nomor_faktur_normalized. No production workaround.
- Browser assertion initially assumed name before code; corrected harness to actual code-before-name order.
- First full verify was interrupted with no completion/exit marker. It is NOT counted as a pass; the final explicit-exit run above supersedes it.
- No simultaneous two-connection contention test; do not claim it from sequential stale/rollback coverage.
- Original merge script/transaction boundary remains unknown; adoption relies on explicit business-approved mapping plus verified transfer evidence, not a reconstructed script.

## Real remaining boundaries

- New physical merges, zero-stock merges without a prior transfer pair, and correction/reversal of an accepted wrong mapping are not exposed by this adoption command. A mistaken accepted relation must not be rewritten; a new correction workflow requires its own contract.
- An invoice already containing BOTH A and B rejects atomically under existing one-product-per-revision uniqueness. No consolidation of quantities/taxes/costs has been invented. If such a case needs processing, obtain explicit owner semantics.
- Only active operational invoices are canonicalized; voided/superseded historical documents remain unchanged.
- Existing unmapped legacy data is not silently repaired. Command execution requires explicit authoritative IDs and business-approved mapping. No real business-data adoption has run here.
- Current supplier invoice list is header/total oriented, not a product-line list. Existing projections are synchronized; product identity is verified on current edit/detail and revision history.

## Changed files

Implementation commit contains 22 files. Exact list: `git show --stat 7602f11b`.

Production: ProductIdentityMerge DTO/use case; CanonicalizeSupplierInvoiceProduct; ProductCatalog/Procurement ports and adapters; CLI; ProductCatalog provider binding; SupplierInvoiceChangeContext; existing PersistsVersionedSupplierInvoiceWrites and RecordsSupplierInvoiceHistory traits; one schema migration.

Proof/docs: two feature test files, shared transferred-merge fixture, separate canonical browser fixture/harness, ADR-0047, Blueprint UI 0019, mapping-gate pointer and this handoff. No real env/credentials committed.

## Runbook / ONE next target

ONE next target: review PR #77's explicit prior-transfer adoption slice and its boundaries. Implementation/proof is complete for that slice; review/merge/deployment and any real explicit backfill remain pending. Do not silently expand to a new physical merge executor or other domains.

Starting commands from `/home/asus/projects/GlassPos-adr0047`:

```bash
git status --short --branch
git log -3 --oneline
git rev-parse HEAD origin/main
gh pr view 77 --repo Asyraf2003/GlassPos --json state,url,headRefOid
cat docs/04_lifecycle/handoff/20261003_adr0047_canonical_implementation.md
```

After normal schema deployment, explicit mapping dry-run (replace placeholders with authoritative IDs; command does not infer them):

```bash
php artisan products:adopt-transferred-merge OPERATION_ID SOURCE_ID CANONICAL_ID \
  --actor=ACTOR_ID --reason='Explicit duplicate identity of the same physical product' \
  --prior-transfer=EXISTING_TRANSFER_SOURCE_ID --same-physical-product
```

Review dry-run outcome before adding `--apply` for a business-approved mapping. Same operation/inputs retry is idempotent. No real adoption command was executed in this session.

Verification if later changes justify rerun:

```bash
DB_DATABASE=glasspos_adr0047_test php artisan test tests/Feature/Procurement
DB_DATABASE=glasspos_adr0047_test make verify
php tests/Browser/supplier-invoice-canonical-fixture.php
APP_ENV=adr0047-canonical-browser php artisan serve --env=adr0047-canonical-browser --host=127.0.0.1 --port=8175
# Separate terminal, same worktree:
node tests/Browser/supplier-invoice-canonical.mjs
```

Browser uses only `glasspos_adr0047_canonical_browser`, reuses fixture history, and creates a private untracked `.env.adr0047-canonical-browser`. Do not commit it. Its session copy was moved to `/tmp/adr0047-canonical-browser.env` after proof. Legacy browser fixture/harness remains separate.

Temporary logs `/tmp/adr0047-canonical-{procurement,verify-final,browser}.log`, readback JSONL and screenshots/PDF may disappear. Outcomes and commands above are durable continuity; do not rely on chat or /tmp survival.
