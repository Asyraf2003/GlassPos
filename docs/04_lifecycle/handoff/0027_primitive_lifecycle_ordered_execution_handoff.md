# Blueprint 0018 ordered execution — D05 repair onward

Date: 2026-09-16. Scope: execute Blueprint 0018 slices in order, continuing automatically after GREEN under the owner's latest instruction; stop for unresolved contract/public-contract/semantic decisions. This supersedes the previous stop-before-production authorization in handoff 0026, not its historical RED evidence.

## Slice 1 — settlement replay and surplus consumers

D05 before repair reproduced: 1 failed / 54 assertions / 5.78s, exit 1. Expected R3 allocation 163719, actual 116206. Classification: PRODUCTION BUG, accepted ADR-0042/0045 contract unambiguous.

Small repair in NoteReplacementPaymentAllocationReconciler: match ordinary refunds to the payment + component type + component reference of current allocations. Only the original component still includes its refund; fresh replacement allocations already carry net money. No ledger rewrite, payment reconstruction, surplus restoration or public HTTP contract change.

D05 focused GREEN: 1 passed / 54 assertions / 5.81s. Adjacent refund/revision/0065/surplus/rollback: 9 passed / 108 assertions / 6.13s.

Remaining Slice 1 composition added to the same fixture:

- R4 downward service revision to151983 from available163719: one surplus due and one surplus paid11736; allocation capped151983.
- R5 upward to190007: available151983, outstanding38024; original ordinary refund47513 remains unchanged.
- S05/S06/S10 agree. S07 preview and inline initially offered26288 (surplus11736 reclaimed). RED: 1 failed / 76 assertions / 5.81s. Classification: PRODUCTION BUG.
- Preview/inline now subtract max(active surplus due, active surplus paid), preserving S05's commitment semantics without subtracting due+paid twice. Required reader dependencies use existing ports; unit fixtures explicitly supply empty surplus readers. No response schema or business meaning changed.
- Focused GREEN: 1 passed / 77 assertions / 5.84s.
- Next partial26288/tender30007 leaves outstanding11736 and allocated178271. Auto-close incorrectly closed root: RED 1 failed / 81 assertions / 6.35s. Classification: PRODUCTION BUG, not a reopened 0065 ordinary-refund defect.
- Auto-close now subtracts the same committed surplus. Final11736/tender20003 allocates190007 and closes. Original payment/cash details/refund/refund allocations/movements remain unchanged; three payment events total, one surplus cash-out.
- Final focused GREEN: 1 passed / 92 assertions / 5.96s.
- Final adjacent GREEN: 25 passed / 149 assertions / 6.32s, exit0.

### Slice 1 files changed

Production:

- app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
- app/Application/Note/Services/NotePaymentSettlementPreviewResolver.php
- app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentAmountResolver.php
- app/Application/Note/Services/AutoCloseNoteWhenFullyPaid.php

Tests:

- tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php
- tests/Unit/Application/Note/Services/NotePaymentGrossSettlementResolverTest.php
- tests/Unit/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentAmountResolverTest.php

The unit expectations were not weakened; only required reader fixtures were added. This handoff is the execution record; Blueprint0018 and accepted ADRs remain unchanged.

### Commands / proof context

All commands from /home/asyraf/projects/laravel/GlassPos. Tests use configured local test DB with approved access outside sandbox. No production data repair.

Focused:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php --stop-on-failure --compact

Adjacent:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentAmountResolverFeatureTest.php tests/Feature/Note/CashierNoteRevisionInlinePaymentContractTest.php tests/Unit/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentAmountResolverTest.php tests/Unit/Application/Note/Services/NotePaymentGrossSettlementResolverTest.php --stop-on-failure --compact

Major gate:

    make verify > /tmp/glasspos-0018-slice1-verify.log 2>&1

Major gate result pending at this intermediate write. No full-suite pass claimed yet. Remaining blueprint gaps are not closed by this reduced fixture, including wholly clipped payment linkage, stale editors, and richer refund semantics.

### Major gate fixture failure classification

First make verify: PHPStan and contract audits passed; test result 6 failed, 1705 passed /11426 assertions /79.63s, exit2. All six failures belonged to three date-sensitive fixtures, not changed settlement expectations.

Classification TEST WRONG: CashierNoteRefundHistoryPresentationFeatureTest, CashierProductRefundActionLifecycleFeatureTest, ServiceExternalBrowserLifecycleFeatureTest froze Carbon to2026-09-14 but left ClockPort bound to SystemClockAdapter (native DateTimeImmutable now,2026-09-16). The real cashier access guard therefore rejected refund/edit requests outside today/yesterday. Their ClockPort now follows the fixture's Carbon time. Production access rules and all business expectations remain unchanged. No residual UI cleanup.

Focused fixture rerun:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php tests/Feature/Note/CashierProductRefundActionLifecycleFeatureTest.php tests/Feature/Note/ServiceExternalBrowserLifecycleFeatureTest.php --stop-on-failure --compact

Result: 8 passed /358 assertions /6.96s, exit0. These three test files are additional changes in the Slice1 gate. Major gate rerun pending at this intermediate write.

Major gate rerun GREEN: make verify exit0; PHPStan, line/Blade/contract audits PASS; **1711 passed /11633 assertions /76.33s**. Slice1 completed. Next active slice2a only.

## Slice 2a — paid cancel bypass

Baseline CorrectPaidWorkItemStatusFeatureTest + CorrectPaidWorkItemStatusHttpFeatureTest: 7 passed /37 assertions /5.68s.

New file: tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php. Real HTTP mixed note product47513 + service63719, full cash111232/tender120003. Cashier closed-note correction remains403. Authorized admin reopens; money remains fully paid; cashier then attempts target_status=canceled.

Initial setup RED: TEST WRONG, admin transaction capability does not grant cashier-area access; 302 instead of403, 1 failed /3 assertions /5.60s. Corrected fixture actors: cashier correction, admin reopen; no middleware bypass.

Domain RED: 1 failed /10 assertions /5.59s. Product work item changes open→canceled and paid_work_item_status_corrected audit is appended; no refund, stock return or new revision. Original paid111232 remains. Classified PRODUCTION BUG under mandatory paid-cannot-cancel rule.

Smallest repair: app/Application/Note/UseCases/CorrectPaidWorkItemStatusHandler.php rejects target canceled after paid-policy check and before mutation, through the existing INVALID_WORK_ITEM_STATE error envelope. No invented cancel/refund action and no schema change. Normal status correction remains available.

Focused GREEN: 1 passed /13 assertions /5.70s, exit0. Test compares entire note/work/payment/refund/revision/inventory/audit/projection sets for no side effects on rejection. Adjacent result recorded below.

Focused command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php --stop-on-failure --compact

Adjacent command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/CorrectPaidWorkItemStatusFeatureTest.php tests/Feature/Note/CorrectPaidWorkItemStatusHttpFeatureTest.php tests/Feature/Note/UpdateWorkItemStatusFeatureTest.php tests/Feature/Note/AdminReopenClosedNoteHttpFeatureTest.php tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php --stop-on-failure --compact
