# PR79 merged; subsequent real invoice revision discovered

## Exact target / repository outcome

Owner authorized merge PR79 and verification that the completed real adoption remained unchanged. PR79 was OPEN/MERGEABLE/CLEAN with exactly reviewed commits fc4dc7db and f761805e. Remote main remained e1820a12; no semantic delta. Security check SUCCESS. Used repository merge-commit workflow with exact-head guard.

PR79 MERGED 2026-10-04T03:36:27Z. Merge/main SHA d73818d4ac4d3bc9ec04a1feb432587e6a9e184f. Main fetched/fast-forwarded and clean. app/tests/ADR diff against reviewed f761805e is empty. Post-merge sanity SupplierInvoiceCanonicalMergeFeatureTest: 4 passed /43 assertions, isolated test DB. Previous full verification remains applicable; no code changes after it.

Continuity branch docs/pr79-merge-state-handoff based on d73818d4. This document's exact commit can be obtained with git log -1. No production source changes in this branch. Dashboard worktree untouched.

## NEW evidence: requested R4 baseline had already changed BEFORE merge

Read-only inspect immediately before merge returned relation_count1, invoice current revision5. Comparison to after-retry adoption baseline failed. Recorded R5 supplier_invoice_updated:

- changed_at 2026-10-04 11:26:20 (stored database timestamp);
- changed_by_actor_id 1;
- change_reason `Perbaiki no Faktur`;
- nomor_faktur iss26041043 -> ISS26041043;
- current product remains A3GN520 qty10/value1236700;
- invoice projection received50 ->60;
- supplier projection count14 ->15, last shipmentJuly21 ->Sep6.

Do not infer who physically initiated actor1's operation. This R5 existed before the merge command. No adoption or real-data mutation command ran in this merge session. Do not attribute it to code merge.

Execution error disclosed to owner: pre-merge PHP inspect, Python comparison and gh merge were placed in one shell call without fail-fast. Python assertion failed but shell continued to gh merge. Therefore merge happened despite the newly detected data-state contradiction. Do not hide this, claim all gates passed, or represent the data as still R4. Future dependent approval/gate/mutation commands MUST use separate calls and explicitly inspect gate success before mutation.

## Independent post-merge read-only proof

- Relation table byte-equivalent to successful adoption baseline: exactly one authoritative relation operation8087a690-0324-429e-bc87-3887d4bd03bb.
- R1-R4 version rows byte-for-byte unchanged; R5 is an appended ordinary invoice update.
- All inventory_movements, product_inventory, product_inventory_costing, inventory_cost_adjustments, supplier_receipts, supplier_receipt_lines, supplier_payments table counts/hashes unchanged from after-retry baseline.
- Invoice header difference only number casing and revision; amount6785250 unchanged. Current B qty10/value1236700 preserved.
- Changed tables from completed-adoption baseline: invoice header/lines/versions/list projection, supplier list projection, audit events/snapshots; sessions/cache also differ. This comparison is against the prior session, NOT a claim that merge caused changes.
- Actual current received projection60; receipt source50 + existing delta10; supplier projection15/date2026-09-06. These were not repaired by this session.

Private evidence directory /home/asus/.local/state/glasspos-adoption-20261004, baseline after-retry.json and fresh current.json; read-only runner `php .../run.php inspect`. Never use dry/apply/retry phases to re-open the closed adoption. /tmp/glasspos-read-proof.php independently selected projection/source totals; ephemeral script is not required continuity.

## Status / ONE next target

PR79 CLOSED/MERGED; original A3GN5 -> A3GN520 adoption CLOSED. Identity-only code exists on main. Requested confirmation “still R4 with projection50/14/July21” CANNOT be given: current R5 contradicts that baseline.

STOP at owner acknowledgement of the already-existing R5 correction and its projection refresh. No undo, reconciliation, new mapping, stock cleanup or historical rewrite. The discrepancy remains a separately tracked concern; it was refreshed by a later recorded invoice update, outside identity-adoption semantics. Opening_stock_seed anomaly remains outside scope and unchanged.

One next target: owner review of R5 correction/projection refresh evidence before opening a separate projection-policy/reconciliation slice. Do not start it autonomously.

Resume: git fetch origin; git status -sb; gh pr view 79 --json state,mergeCommit; read this handoff first. Code sanity already complete; no repeated full tests needed absent changes.
