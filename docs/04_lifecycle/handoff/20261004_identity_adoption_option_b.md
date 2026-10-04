# A3GN5 -> A3GN520: Option B execution

## Target / locked decision

Owner approved identity-only adoption on glasspos_local, including automatic apply after successful technical dry-run. Option B: do NOT reconcile stale aggregate projections. Only current product identity and required version/audit/revision markers change. Historical versions immutable. No second mapping, seed cleanup or other-domain canonicalization.

Branch fix/identity-adoption-projection-scope, base main/origin-main e1820a1208244fbc757db5bf72bd992fac5967a3. PR77 closed; this follow-up addresses newly reproduced broad projection side effects. Prior handoff docs branch ends 6e42cb4a. Dashboard worktree remains untouched at c170c8a2.

## Root cause / patch

CanonicalizeSupplierInvoiceProduct::apply called SupplierInvoiceListProjectionService::syncInvoice, recomputing invoice received/payment aggregates and cascading to supplier aggregate sync. Replaced with existing projection writer port's narrow syncRevisionFromInvoice: update ONLY last_revision_no from accepted invoice header on an existing projection. No aggregate reader, supplier sync, timestamp reset, or missing-projection rebuild. Current B UI derives from current lines. ADR0047 records this semantic boundary.

Files: CanonicalizeSupplierInvoiceProduct.php; SupplierInvoiceListProjectionWriterPort.php; DatabaseSupplierInvoiceListProjectionWriterAdapter.php; SupplierInvoiceCanonicalMergeFeatureTest.php; ADR0047; this handoff.

## Proof so far

- RED regression reproduced unintended received-quantity refresh before patch.
- Procurement regression after patch: 331 passed, 2359 assertions.
- make verify running; final outcome to append.
- Real dry-run PASSED: 1 simulated invoice revision R3 -> R4, current B, old R1-R3 version rows unchanged; all line economics unchanged.
- Compared ALL 73 base tables before/after rollback: identical row counts/SHA256. In simulation only relation/header/lines/version/audit tables and invoice projection revision marker changed. Supplier projection, receipt/payment/stock/cost tables untouched.
- Existing schema migration already recorded; no schema migration rerun.

Private durable machine artifacts (not committed business dumps): /home/asus/.local/state/glasspos-adoption-20261004/. run.php observes existing Artisan command before rollback/commit and validates table whitelist/row deltas/header/line/version/audit invariants. It never substitutes SQL business mutation. dry-proof.json, before-dry.json, simulated-dry.json, after-dry.json, dry.log. Secrets loaded privately from original worktree .env, database+host explicitly verified.

## Exact command identity

Operation and prior-transfer ID: 8087a690-0324-429e-bc87-3887d4bd03bb.
A: c824fe66-71d5-4d86-89b5-03bdb3476b5b (A3GN5).
B: 137b8787-7b3e-425e-b4b0-ab286cd8118a (A3GN520).
Actor 1. Reason: Owner-confirmed duplicate physical product A3GN5 merged into canonical A3GN520; adopt prior stock transfer and correct current supplier invoice identity only.
Invoice: 5b6b8e6b-0ea7-4ef4-845d-f76f3e45578d / iss26041043.

Command is products:adopt-transferred-merge OP A B --actor=1 --reason='above exact reason' --prior-transfer=OP --same-physical-product, with --apply only for commit/retry. Stable IDs unchanged. Harness phases: php /home/asus/.local/state/glasspos-adoption-20261004/run.php dry|apply|retry. Apply refuses DB drift since successful dry-run and validates before commit.

## Current progress / ONE next target

At initial writing: apply NOT YET run. Finish verify, apply exact approved mapping, post-apply and idempotency/UI proof, then close. No owner checkpoint needed absent NEW contradictory evidence.

Separate next-slice candidate ONLY: projection received50 vs source60, supplier invoice_count14 vs source15, shipment2026-07-21 vs source2026-09-06. Preserve in this operation. B opening_stock_seed discrepancy ledger77/value8480600 vs projection30/value3710100 is also preserved and outside scope.

## FINAL — adoption CLOSED on glasspos_local

Implementation commit fc4dc7db. Executed existing command with --apply after passing dry-run:
`APPLIED: 1 supplier invoice revisions; no stock transfer.`

- Exactly one relation persisted, operation/prior-transfer 8087a690-0324-429e-bc87-3887d4bd03bb, actor 1, exact approved reason and recorded occurrence time.
- iss26041043 R3 A -> R4 B. Canonical current line qty10/value1236700; invoice grand total6785250 unchanged. All six new line economic fields match prior current lines; only target identity snapshots change.
- R1-R3 version rows unchanged byte-for-byte. Historical code is lowercase `a3gn5`, preserved; current snapshot `A3GN520`.
- Invoice header changes only revision. Invoice list projection changes only revision marker. Received50, supplier count14, last shipment2026-07-21 preserved.
- Entire inventory_movements/product_inventory/product_inventory_costing/inventory_cost_adjustments/receipt/payment/supplier projection table hashes unchanged. Thus no duplicate merge, invoice delta movement, cost revaluation, payable/payment/receipt effect; B seed anomaly also untouched.
- Expected changed tables/row deltas only: relation+1; invoice lines+6; versions+1; audit_events+1; audit_event_snapshots+2; audit_outbox+1; invoice header and invoice projection row counts unchanged.
- Audit verifies actor/reason/correlation to merge OP, before_revision3/after_revision4 and before A/after B snapshots.
- Exact retry with --apply: `APPLIED: 0 supplier invoice revisions; no stock transfer.` ALL 73 table counts/hashes identical before/after retry. No added audit/version/relation effects.
- Post-apply independent read confirms relation count1, R4, received50, supplier count14/dateJuly21. Source-derived discrepancies still60/15/Sep6; no repair performed.

Proof files in private durable directory above: apply-proof.json, retry-proof.json, before/after-apply.json, before/after-retry.json, apply.log/retry.log. Validation observed effects BEFORE commit and again AFTER commit, not only CLI output.

Final checks: make verify exit0, PHPStan no errors; contract audits passed; 86 frontend tests passed; 1895 PHP tests /14827 assertions passed. Procurement targeted run331/2359. Diff self-review/check clean. No global withTrashed, hardcoded business values, inventory engine changes, schema changes or dashboard modifications.

Browser/manual proof: actual DB detail + edit views rendered through existing controller/use-case in READ ONLY transaction with in-memory actor/session; all 73 table hashes unchanged after rendering. Chromium opened these exported actual HTML pages served locally with assets: current B, R3 historical a3gn5, R4 B, edit product_id B, expected_revision4 all asserted. History panels expanded; screenshot detail.png and browser-proof.json retained privately. This is actual-data rendered-view/browser proof, NOT authenticated HTTP login/navigation coverage. No form submitted to real data. Initial Snap browser could not start; cached Chromium worked using existing Snap shared libraries. One initial history assertion incorrectly expected uppercase old code; corrected assertion to actual immutable lowercase snapshot, with NO data/code mutation.

Code follow-up is pushed for review; do not claim merged main contains patch until PR actually merged. Real-data adoption target is CLOSED. Remaining repository delivery: follow-up PR review/merge (no automatic merge authorization inferred from old PR77 approval). No further business mutation needed.

ONE next target recommendation: separately review stale projection reconciliation scope (received50 vs60, supplier count14 vs15, shipmentJuly21 vsSep6). Do not perform it without owner scope approval. Opening-stock-seed remains excluded.

Resume commands: git fetch origin; git status -sb; git log -1; read this FINAL section first. Do NOT rerun adoption as unfinished work. For read-only persisted-state recheck: php /home/asus/.local/state/glasspos-adoption-20261004/run.php inspect. No future session should rely on /tmp artifacts.
