# Blueprint 0018 Slice 1 — D05 second allocation rebuild RED

## Status and active scope

Date: 2026-09-16, Asia/Makassar.

BLOCKED on a runtime-proven PRODUCTION BUG. Characterization delivered; Slice 1 is not GREEN or complete. No downstream surplus/partial-payment checkpoint or Slice 2 was executed. No production patch, ADR change, data repair, or residual UI cleanup was made.

Active blueprint: `docs/03_blueprints/finance/0018_primitive_lifecycle_contract_map_and_adversarial_torture_tests.md`, Slice 1, D05. User explicitly selected ordinary refund → first allocation rebuild → second revision/rebuild and required stopping before any production repair.

Contracts: ADR-0042 settlement commitment and ADR-0045 immutable payment/refund/version history and active allocation replay. Reference 0065 section 20 remains CLOSED: its operational reopen/second-close regression passes in this session. This new contradiction concerns a second allocation rebuild, not the closed 0065 implementation target.

## Test artifact and fixture

Created `tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php`.

One reduced real HTTP chain, with authorized admin and isolated RefreshDatabase test database:

1. Seed product and balanced opening inventory: quantity 17, unit cost 19,721, inventory value 335,257; one explicitly test-only opening movement.
2. Create product quantity 1 at 47,513 plus service 163,719. One full cash payment 211,232; tender 220,003; change 8,771.
3. Ordinary selected-row product refund 47,513, using the real refund route. One customer_refunds row, one refund_component_allocations row, one stock return.
4. R2: remove refunded product from submitted editable graph, raise service to 181,258, skip payment.
5. R3: raise service to 199,487, skip payment.

No lifecycle outcome is seeded directly. Dates use the current permitted business date; injected ClockPort advances one minute between actions. No chronology claim depends on DB created_at timestamps.

This reduced fixture isolates D05; it is not the full Chain B composition. There is no new payment, ordinary refund, surplus disposition, or surplus payment during either revision. The surviving settlement is the original service funding 163,719. Revision changes obligation only.

## First failures and classification

Infrastructure attempt: sandbox blocked MySQL connection to local `bengkelhex_test`; 3 failed, 0 assertions. This is not domain RED. Authorized rerun outside sandbox passed.

Initial new-test run: TEST WRONG, 1 failed / 2 assertions / 5.62s, exit 2. Snapshot helper incorrectly ordered customer_payment_cash_details by id. Migration defines customer_payment_id as primary key. Corrected only the test helper to use that key; no expectation or production behavior changed.

First domain RED after fixture correction: **PRODUCTION BUG**, R3 second rebuild allocation conservation; 1 failed / 54 assertions / 5.68s, exit 1. Expected 163,719; actual 116,206. Difference 47,513 exactly equals the unchanged ordinary refund.

## Named source observations

Sources were called independently; the test does not install a proposed universal formula or mock their results.

| Checkpoint/source | R2 first rebuild | R3 second rebuild |
|---|---:|---:|
| Current obligation | 181,258 | 199,487 |
| Historical accepted payment | 211,232 | 211,232 |
| Historical ordinary refund | 47,513 | 47,513 |
| Actual current component allocation | 163,719 | 116,206 |
| S05 BuildCreateNoteRevisionSettlement net | 163,719 | 163,719 |
| S05 outstanding | 17,539 | 35,768 |
| S06 NoteOutstandingPaymentAmountResolver net | 163,719 | 116,206 |
| S06 outstanding | 17,539 | 83,281 |
| S07 NotePaymentSettlementPreviewResolver net | 163,719 | 163,719 |
| S07 preview and inline full amount | 17,539 | 35,768 |
| S10 read-only next capture | 116,206 | 68,693 |

S10 next capture is a read-only observation, not an executed third rebuild. Auto-close was inspected but was not invoked as an additional mutation probe after RED. No universal S07 or future-payment behavior is claimed from these observations.

## Exact source ownership and cause

- `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php`, captureAllocatedAmounts, lines 29–44: sums current allocations by payment, then subtracts every historical component refund for that payment.
- `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`, apply: captures that result before deleting current allocations, replacing rows, and rebuilding from captured amounts.
- First capture: 211,232 − 47,513 = 163,719, correctly replayed to R2.
- Second capture: already-net 163,719 − historical 47,513 = 116,206, incorrectly replayed to R3.
- S05 reads linked historical payment and refund independently and retains net 163,719.
- S06 current-revision component projector follows the diminished allocation and exposes outstanding 83,281.
- S07 preview/inline follows historical gross linked money minus ordinary refund and exposes outstanding 35,768.

ADR-0045 does not permit a price-only revision to consume additional valid historical settlement. There is sufficient contract to classify this as a production bug, not a missing business decision. Do not change the test's expected 163,719 to normalize the loss.

## Ledger, identity and effect evidence before failing assertion

The test compares entire captured rows, not only monetary sums:

- Original customer_payments row unchanged, including ID, amount, method and timestamps; exactly one payment.
- Original cash detail unchanged, including credited amount, tender and change.
- Original customer_refunds and refund_component_allocations unchanged, including source payment and old component identity; exactly one of each.
- Exactly one current service allocation per revision, tied to the original payment and the new current revision's work_item_root_id; none assigned to the refunded product.
- Legacy payment_allocations empty at both observed rebuild checkpoints.
- Revision count advances to 2 then 3; all captured earlier revision and line records remain identical.
- Inventory movement set remains identical after refund through both revisions: opening + one issue + one refund reversal. No duplicate revision reversal of the refunded stock line.
- Zero surplus dispositions and surplus payments at both checkpoints.

Raw failing output includes payment, refund, refund-allocation and current-allocation rows with actual UUIDs for R2/R3. Local transient log: `/tmp/glasspos-0018-slice1-d05.log`. RefreshDatabase fixtures are disposable; do not query production expecting those UUIDs to exist.

## Commands and exact proof

Execution context: `/home/asyraf/projects/laravel/GlassPos`. Feature tests used the configured local test database with approved access outside sandbox.

Baseline command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php --stop-on-failure --compact

Result: exit 0, **3 passed / 49 assertions / 6.08s**. This includes the closed 0065 regression.

Syntax command:

    php -l tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php

Result: no syntax errors (initial file, before the small primary-key helper correction; corrected file subsequently executed successfully up to the domain assertion).

Characterization command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php --stop-on-failure --compact > /tmp/glasspos-0018-slice1-d05.log 2>&1

Final result: exit 1, **1 failed / 54 assertions / 5.68s**. Failing assertion: D05 R3 allocation conservation, expected 163719, actual 116206. No skipped or expected-failure annotation hides the defect.

No make verify, downstream suite, or post-RED production modification was performed. No full-suite GREEN is claimed.

## Files written in this session

- `tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php`
- This handoff.

No claim is made about unrelated pre-existing working-tree changes. Blueprint and accepted ADRs remain unchanged.

## Smallest safe next implementation slice

Request/authorize a separate bounded D05 production repair slice: make replacement replay conserve each payment's surviving settlement across repeated revisions without consuming the same historical ordinary refund twice. Start with the existing RED and the capture/delete/rebuild seam. Preserve source payment/refund identities, existing first-rebuild behavior, component caps and atomicity; do not replace every settlement consumer or consolidate formulas broadly.

Before choosing the fix, inspect per-payment linkage, legacy compatibility allocations and surplus clipping so the repair does not restore already-refunded or committed-surplus money. This requirement is not permission to broaden into unrelated D04/D06 fixes. If the safe repair requires a wider decision, stop and report it.

Then run this test and nearest adjacent refund/revision/0065 regressions. Only after D05 is GREEN resume the remaining Slice 1 surplus-paid and next-payment characterization. Keep later slices blocked until Slice 1's stop gates are satisfied.

## Progress and session context

Characterization proof is recorded; completed GREEN slices: 0. Active/blocking slice: 1 / D05. New production bug reproduced. No production patch made.

Continuation needs this handoff, Blueprint 0018 and refreshed source. Context is sufficient for this bounded conclusion; start the separately authorized repair with current source and test output, not narrative alone.
