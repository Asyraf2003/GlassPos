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

Slice2a adjacent GREEN: 14 passed /112 assertions /5.91s, exit0. Slice2a completed. Next active slice2b only.

## Slice 2b — nominal correction through revision (2026-09-16 continuation)

Latest owner authorizes repair of unambiguous production bugs, automatic sequential continuation after GREEN, and commit/push only after GREEN. Slice1 and2a remain complete; they were not re-audited.

Exact refreshed RED: test_nominal_correction_must_not_report_success_without_a_new_revision, 1 failed /11 assertions /5.82s. HTTP success changes same work item/root63719→61987; pointer staysR1, one revision, no due/paid, audit advisory refund_required1732. Classification PRODUCTION BUG against ADR-0045. No business-contract decision needed to require next immutable revision.

Repair reuses CreateNoteRevisionWorkflow inside the existing correction transaction. Root is locked, original revision bootstrapped for legacy notes through EnsureInitialNoteRevisionExists, existing editable-line filter and revision payload mappers preserve the current graph and exclude shadow lines, and only target service fields change. Revision workflow owns replacement, allocation replay, settlement, automatic surplus due/paid, inventory effects, pointer and audit. Correction's own before/after audit remains, and refund_required_rupiah retains the generated revision surplus amount even after automatic payout. No parallel revision writer or report repair.

Files changed for2b:

- app/Application/Note/Services/BuildPaidServiceCorrectionRevisionPayload.php (new)
- app/Application/Note/Services/CorrectPaidServiceOnlyWorkItemMutation.php
- app/Application/Note/Services/CorrectPaidServiceOnlyWorkItemTransaction.php
- app/Application/Note/Services/CorrectPaidServiceOnlyWorkItemFinalizer.php
- app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php
- tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php
- tests/Feature/Note/CorrectPaidServiceOnlyWorkItemFeatureTest.php
- this handoff

Focused initial repair GREEN1/11. Proof expanded to immutable R1 records, new target identity, parent pointer, exact one1732 due and paid, surviving allocation61987, immutable payment/refund/inventory and original correction audit amount.

Adjacent RED1: TEST WRONG, expected mutable work-item-1 identity. Assertion now requires fresh result ID and old50000 snapshot/work-item identity in R1, preserving original value/history.

Adjacent RED2: PRODUCTION BUG in existing workspace mapper exposed by correction reuse: customer_owned became none. Service-only payload now retains supported part_source; stock/external variant logic unchanged. Existing expectation remains customer_owned.

Focused plus nearest correction adjacent command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php tests/Feature/Note/CorrectPaidServiceOnlyWorkItemFeatureTest.php tests/Feature/Note/CorrectPaidServiceOnlyWorkItemHttpFeatureTest.php tests/Feature/Note/CorrectPaidServiceOnlyWritesMutationTimelineFeatureTest.php --stop-on-failure --compact

Result **11 passed /86 assertions /6.27s**, exit0.

Additional adjacent command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/NoteDetailPageShowsNativeCorrectionHistoryFeatureTest.php tests/Feature/Note/NoteCorrectionHistoryBuilderFeatureTest.php tests/Feature/Note/NoteCorrectionHistoryPageFeatureTest.php tests/Feature/Note/CashierNoteCorrectionHistoryReasonViewFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php --stop-on-failure --compact

Result **12 passed /166 assertions /6.90s**, exit0.

Major gate running: make verify > /tmp/glasspos-0018-slice2b-verify.log 2>&1. No result claimed until completion.

Commit observation: another process/owner committed during execution. Production revision integration is in394a10415b1a86a6a9dd4a38f1b137d60fb936b4, identity assertion inbd5436f00e4f07deb4d4f02642d03c75290746c6, part-source fix inc2ac992841c6dfd7608f4a159ef3c10752f53594. Do not attribute those commits to assistant tool calls. Assistant will commit proof and push after gate. Active branch main tracking origin/main. Push not yet claimed.

Slice2b major gate: initial lint RED (two unused finalizer reader dependencies, one nullable settlement access). Classification implementation/type hygiene; removed unused dependencies and require the committed settlement instead of silent zero fallback. Rerun make verify **GREEN exit0;1713 passed /11670 assertions /84.25s**, PHPStan and contract audits PASS. Slice2b COMPLETE. No unresolved2b contract conflict; broader stale/idempotency gaps remain assigned to later slices.

Latest code commit observed6de7adb6 (lint fixes included by another local process). Assistant proof commit/push will follow this GREEN. Next active slice2c, then3; no downstream fixture has been executed yet.

Slice2b proof commit: eb8099c2dbec69313eb549ef8a22168e245e1382 (assistant git commit). Push first encountered concurrent remote ref update already at that SHA; retry git push origin main exited0, Everything up-to-date. Remote main SHA verified equal to eb8099c2. Code and GREEN evidence published. Next active2c.

## Slice 2c — fully refunded root with a new receivable — COMPLETE

Owner closeout instruction: finish proof/publication for 2c only, then STOP. Slice3 belongs to a fresh session. Do not repeat Slice1/2a/2b implementation.

Classification: PRODUCTION BUG. Accepted ADR-0042 permits new current lines after full refund while old lines remain shadow; ADR-0015 defines open for positive outstanding. Root remained refunded after the new revision. Payment63719 then rolled back with “Hanya note open yang boleh ditutup.” Exact RED: 1 failed /15 assertions /6.21s.

Fixture: product47513 paid then fully refunded; new service63719 through authorized revision; new cash payment63719/tender70003/change6284. Old payment/refund/refund-allocation/revision/inventory records stay immutable, old product receives no new allocation.

Small repair: ReopenNoteForRevisionOutstanding also handles refunded roots with positive revision outstanding. It invokes the explicit domain reopenForRevisionOutstanding transition, which requires actor and positive active obligation for a refunded root. Ordinary manual reopen retains its existing closed-only contract. Existing note_reopened audit and close history are reused. No 0065 historical metadata repair or old component resurrection.

Files for this slice:

- app/Application/Note/Services/ReopenNoteForRevisionOutstanding.php
- app/Core/Note/Note/NoteOperationalStateMutations.php
- tests/Feature/Note/PrimitiveFullyRefundedNewReceivableFeatureTest.php
- this handoff

### Exact proof

Execution context: /home/asyraf/projects/laravel/GlassPos, configured local test DB with approved access outside sandbox.

Baseline revision-after-refund plus core operational transitions: 7 passed /38 assertions /5.97s.

Initial focused after repair: 1 passed /20 assertions /5.88s. Final assertions add explicit reopened state/event, two close events, unchanged refund allocation/movement sets and exact tender/change.

Final focused + adjacent command (already executed before interruption; original process output recovered without rerun):

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveFullyRefundedNewReceivableFeatureTest.php tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Feature/Note/AdminReopenClosedNoteHttpFeatureTest.php tests/Feature/Note/ReopenClosedNoteFeatureTest.php tests/Unit/Core/Note/NoteOperationalStateTransitionsTest.php --stop-on-failure --compact

Result: **12 passed /106 assertions /5.81s**, exit0.

Additional adjacent boundary/auto-close/rollback command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php --stop-on-failure --compact

Result: **3 passed /37 assertions /6.12s**, exit0.

No further production edit during closeout. No new make verify run: the last broad GREEN remains Slice2b1713/11670; final2c has the focused/adjacent proof above, not a claimed fresh full-suite pass.

### Publication and next session

Repo was clean at closeout; implementation/tests were already committed by another local process. Repair commit63ba478788df1733336e62055914a3f56f6371b4; final test assertions commit1cf033f3d88091cfed3aebcb1123de94b1649cca. git ls-remote confirmed origin/main at1cf033f3d88091cfed3aebcb1123de94b1649cca, equal to local HEAD. Only this new proof section remained unpublished when written.

The first remote read inside sandbox failed on SSH config permissions; approved outside-sandbox read succeeded. No SSH configuration was changed.

Remaining GAP/CONFLICT: no blocker in the exercised2c contract. Unexecuted blueprint gaps (stale editor identity, audit, richer refund semantics, etc.) remain open; this proof does not resolve them.

PROGRESS: Slice1,2a,2b,2c COMPLETE with scoped proof. Next active slice in a NEW SESSION:3, Chain A payment/debt/cash. Slice3 has NOT started. Stop here per owner instruction; retain this handoff for context recovery.
