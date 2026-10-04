# A3GN5 -> A3GN520 adoption: projection gate

## Exact target and authority

Finish ONE real adoption on glasspos_local. Owner explicitly authorized schema migration, dry-run and apply if all gates pass; no additional routine apply approval is required. Same physical product mapping and prior transfer are locked. Stop only for new material evidence, including unexpected business-data changes. No second mapping, seed cleanup or other-domain canonicalization.

Main/origin-main freshly fetched and equal e1820a1208244fbc757db5bf72bd992fac5967a3. PR77 remains CLOSED. Documentation branch docs/adr0047-real-mapping-preflight; parent HEAD before this update 282c1b5b (exact containing commit: git log -1). Dashboard worktree remains fix/57-dashboard-finance-port at c170c8a2, untouched.

This supersedes the schema/approval gate in 20261004_real_mapping_preflight.md. Apply approval EXISTS; the new blocker is the observed projection refresh.

## Proven current state (read-only query this continuation)

- Database SELECT DATABASE(): glasspos_local.
- Migration 2026_10_03_000100_create_product_identity_merges_table recorded.
- product_identity_merges count = 0: adoption NOT applied.
- Invoice 5b6b8e6b-0ea7-4ef4-845d-f76f3e45578d / iss26041043 remains R3.
- Invoice projection total_received_qty = 50, revision = 3.
- Authoritative receipt rows sum qty 50. Existing supplier_invoice_revision_delta_line movements joined through ALL invoice lines sum +10.
- Existing receivedQtyTotals() deliberately combines receipts and revision deltas: source-backed result is 60, already before adoption.
- Supplier d317f8d4-3469-4969-a126-c83f89385963 source rows (non-void invoices): count 15, max shipment 2026-09-06.
- Its stored projection: count 14, last shipment 2026-07-21.

These are stale projections, not evidence that adoption creates a new receipt or invoice. However refreshing them changes operational read values beyond canonical identity and must not be silently approved.

Source: app/Adapters/Out/Procurement/SupplierInvoiceListProjectionReceiptSubqueries.php; DatabaseSupplierListProjectionSourceReaderAdapter.php. Canonicalization calls invoice projection sync, which also syncs supplier projection.

## Previous execution evidence and its limits

Before context continuation, authorized migration succeeded. Recorded whole-table comparison found only migration record and new empty relation table changed. Instrumented existing command dry-run reported: `DRY RUN (rolled back): 1 supplier invoice revisions; no stock transfer.`

The transaction observation recorded these unexpected projection differences:

- invoice total_received_qty 50 -> 60;
- supplier invoice_count 14 -> 15;
- supplier last_shipment_date 2026-07-21 -> 2026-09-06;
- usual projection timestamps and invoice R3 -> R4.

Recorded full row hashes/counts for all 73 tables matched before/after rollback; economic tables were unchanged in simulation. No --apply ran. IMPORTANT: raw /tmp/adr0047-real-adoption artifacts disappeared before this continuation. Those hash results are prior execution evidence, not reverified artifacts. This continuation independently reverified migration, empty relation, R3 and the stale/source projection differences. A complete successful gate (including all old-version/line assertions) was NOT reached; do not claim it passed. Recreate full instrumentation and proof before any eventual apply.

No production source edits or tests/browser in this continuation. Read-only PDO transaction ended with rollback; no app boot or business writes. Temporary reproduction script /tmp/glasspos-read-proof.php contains no credentials (loads original .env privately); temporary files are not continuity storage.

## Locked mapping / prepared command

A: c824fe66-71d5-4d86-89b5-03bdb3476b5b (A3GN5).
B: 137b8787-7b3e-425e-b4b0-ab286cd8118a (A3GN520).
Stable operation and prior transfer: 8087a690-0324-429e-bc87-3887d4bd03bb.
Actor 1. Prior transfer A -10/-1236700, B +10/+1236700; A drained. Expected one invoice R3 A -> R4 B with qty10/value1236700. Full product/transfer evidence in previous preflight handoff.

Execute from current-main worktree ONLY after explicit environment selection/verification of glasspos_local; worktree default env must not be assumed correct:

```bash
php artisan products:adopt-transferred-merge \
  8087a690-0324-429e-bc87-3887d4bd03bb \
  c824fe66-71d5-4d86-89b5-03bdb3476b5b \
  137b8787-7b3e-425e-b4b0-ab286cd8118a \
  --actor=1 \
  --reason='Owner-confirmed duplicate physical product A3GN5 merged into canonical A3GN520; adopt prior stock transfer and correct current supplier invoice identity only.' \
  --prior-transfer=8087a690-0324-429e-bc87-3887d4bd03bb \
  --same-physical-product
```

Future apply is the exact command plus --apply, NOT executed. Existing approval remains valid after resolving the new projection boundary and passing all technical gates.

## ONE next target / owner decision

Resolve projection refresh boundary before rerunning dry-run/apply:

A. Explicitly accept source-backed refresh (received display 60; supplier count 15/date Sep6) as part of this adoption. Physical receipt, qty/value/payment histories still must remain unchanged.
B. Narrow identity-only projection writes to identity/revision fields through a code follow-up, preserving these existing projection values; repair stale projections separately.

Technical recommendation: B for strict identity-only mutation scope, but it deliberately leaves known stale read values; owner must choose this UI consequence versus A. No code patch selected yet.

Remaining: decision, complete fresh dry-run/invariant/rollback proof, apply, post-apply history/economics/idempotency/browser proof, closure. Target NOT CLOSED.

Starting commands: git fetch origin; git status -sb; git log -1; read this handoff and projection source files above. Query source receipt sum and existing revision deltas separately before evaluating projection output. Do not rerun schema migration blindly; already applied.

Opening-stock-seed anomaly on B remains outside scope: ledger77/value8480600 vs projection30/value3710100. Do not reconcile it. No candidate two.
