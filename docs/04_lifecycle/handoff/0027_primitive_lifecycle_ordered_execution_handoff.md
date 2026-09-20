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

Closeout proof commit320abaab published successfully: git push origin main exit0, remote advanced1cf033f3→320abaab. This publication receipt is a documentation-only follow-up; no test/code changes or Slice3 execution followed.

## Slice 3 — Chain A payment / debt / cash — COMPLETE

2026-09-16 continuation: owner authorizes automatic sequential GREEN slices and commit/push. Starting tree clean at0222ef31. Execution context for all commands: /home/asyraf/projects/laravel/GlassPos; configured isolated local test DB outside sandbox. Sandbox connection failures are environment failures, not domain RED.

Refreshed Blueprint0018 section6 A/section9, ADR0015/0025/0042/0044/0045, payment HTTP validator/controller, allocation/priority/replay, revision workflow and settlement owner. Existing BuildNoteRevisionSettlementTest already proves upward formula; no redundant unit case added.

New tests/Feature/Note/PrimitivePaymentDebtCashChainFeatureTest.php and tests/Support/BuildsPrimitiveLifecycleFixture.php. No production changes. Fixture uses injected ClockPort/Carbon at2026-09-15 with minute advances, balanced stock opening movements/costing, active Q qty2 package template, real HTTP create with inline DP and subsequent payment/revision requests. Exact A1–A5 values preserved. Proof covers source/event and allocation counts, priority, each event's credit/tender/change, immutable earlier payment/cash/revision records, new active identities, same-key replay and changed payload rejection, atomic invalid overpayment/short tender, one final operational close and no final-payment stock effect.

Classifications: TEST WRONG only. Corrected surplus table name, cash-detail primary key, required HTTP selected rows, and settled resolver result shape. An initially overstrict assertion demanded immutable per-payment component distribution after revision; ADR0045 expressly permits redistribution. Replaced with per-payment amount conservation plus exact aggregate external53127/product109459 priority; original event payload assertions retained. No chronology/replay-order business rule invented, no production behavior changed to satisfy this assumption.

Baseline:

    php -d memory_limit=-1 vendor/bin/pest tests/Unit/Application/Note/Services/BuildNoteRevisionSettlementTest.php tests/Feature/Note/ExistingNoteCashSettlementIntentFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php --stop-on-failure --compact

GREEN19 passed /170 assertions /6.61s.

Focused final:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitivePaymentDebtCashChainFeatureTest.php --stop-on-failure --compact

GREEN1 passed /109 assertions /5.95s, exit0.

Combined adjacent (before final two additional assertions):

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitivePaymentDebtCashChainFeatureTest.php tests/Feature/Note/ExistingNoteCashSettlementIntentFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php tests/Unit/Application/Note/Services/BuildNoteRevisionSettlementTest.php --stop-on-failure --compact

GREEN22 passed /298 assertions /6.56s, exit0. git diff --check clean. No major gate run for this test-only slice; previous broad gate remains2b. Commit/push pending below. Next active slice4 only, Chain B.

Slice3 publication: assistant commit69375390; git push origin main exit0,0222ef31→69375390. Slice4 baseline/proof now active; no Slice4 result claimed yet.

## Slice 4 — Chain B refund / revision / receivable — COMPLETE

New tests/Feature/Note/PrimitiveRefundRevisionReceivableChainFeatureTest.php only; no production changes. Reused Slice3 fixture/action helpers. Refreshed accepted ADR0025/0042/0045 and Blueprint B, refund plan/bucket/idempotency transaction, surplus disposition/payment storage, revision replay and stock-return operation.

Exact B1–B8 through real HTTP. Three original payments395933; one logical refund142539 with receipt matching exactly three source IDs (20002/89457/33080), three refund allocations, one original P stock return. Replay has no duplicate effects; new-key old target rejects. B5 active241658 with distinct linked due/paid11736; B6 reordered E/K/S/new R427741 with outstanding186083; partial27119 leaves158964; final cash158964/tender170003/change11039 closes second time. Original payment/cash/refund/revision-line facts and surplus due/paid remain unchanged. Old P stays canceled without new payment rights.

Classification: TEST WRONG for added receipt assertion referencing adapter result_payload instead of DB result_payload_json; corrected from migration/adapter. Initial Chain B passed1/62 before receipt/link assertions. No production bug or unresolved contract in exercised Chain B. Historical0065 remains CLOSED.

Baseline:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php --stop-on-failure --compact

GREEN4 passed /52 assertions /6.20s.

Initial focused:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRefundRevisionReceivableChainFeatureTest.php --stop-on-failure --compact

GREEN1 passed /62 assertions /5.75s.

Final focused+adjacent:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRefundRevisionReceivableChainFeatureTest.php tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php --stop-on-failure --compact

GREEN7 passed /230 assertions /6.52s, exit0. No major gate in this test-only slice. Commit/push pending. Next active Slice5 supported Chain C only; diagnostic probes already resolved in2a/2b are adjacent regressions, not reopened findings.

Slice4 publication: assistant commit3caa7869; git push origin main exit0,69375390→3caa7869. Slice5 active.

## Slice 5 — Chain C distinct draft / revision / payment / refund — COMPLETE

New tests/Feature/Note/PrimitiveCancelCorrectionVersionChainFeatureTest.php; no production changes. Exact C0–C6 executed via HTTP with injected fixture clock. Refreshed draft request/controller/writer/deleter, revision stock reversal, ADR0015/0045 and Blueprint C. Diagnostic2a/2b remain closed and run as adjacent proof.

C0 saves scratch draft without note/payment/refund/history/stock effects. C1 creates unpaid158745, issues P2 and deletes only creating actor's draft; other actor draft byte-for-byte unchanged. C2 removes unpaid P through R2, reverses its stock under transaction_workspace_updated, preserves R1 and creates no refund/due. C3 cash63719/tender70003/change6284 closes without stock effect. C4 authorized R3 adds fresh P1, opens debt47513; C5 transfer settles and closes second time. C6 refund47513 returns new source P1, keeps active service63719 settled. Refund replay/current refunded target/new-key stale old ID create no money/stock/projection changes. Four non-opening stock movements, distinct revision and refund sources.

Classification: no meaningful failure; focused first run GREEN. No contract decision or production repair required.

Baseline:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php --stop-on-failure --compact

GREEN3 passed /51 assertions /5.73s.

Focused:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveCancelCorrectionVersionChainFeatureTest.php --stop-on-failure --compact

GREEN1 passed /62 assertions /6.10s.

Final focused+adjacent:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveCancelCorrectionVersionChainFeatureTest.php tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/CorrectPaidServiceOnlyWorkItemFeatureTest.php tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php --stop-on-failure --compact

GREEN9 passed /168 assertions /6.54s, exit0. No major gate for this test-only slice. Commit/push pending. Next active Slice6, Chain D exact source/cost/cardinality and rollback. Owner said continue across date rollover2026-09-17; explicit test fixture stays2026-09-15.

Slice5 publication observation: another local process committed the test and Slice4 receipt as aeef02a3; assistant commit6f7896b7 contains Slice5 proof only. Initial SSH push failed (agent has no identities), and Slice6 baseline encountered stopped DB. MariaDB subsequently active; retry baseline GREEN10/155. GitHub CLI authentication exists; HTTPS credential-helper push attempted, result recorded later. No credentials/configuration rewritten.

Slice5 HTTPS publication confirmed: git -c credential.helper= -c 'credential.helper=!gh auth git-credential' push https://github.com/Asyraf2003/GlassPos.git main exited0, remote aeef02a3→6f7896b7. Existing SSH origin configuration preserved.

## Slice 6 — Chain D inventory identity / source / cost — focused GREEN; major gate pending

New tests/Feature/Inventory/PrimitiveInventoryRevisionRefundChainFeatureTest.php; no production edits. Refreshed inventory issue/reversal, active replacement, rollback tests, editable historical package mapper and ADR0007/0008/0045. Template validation stays active on create; revision uses the supported historical_package_snapshot/requires_service_product_template=false payload emitted by RevisionWorkspaceServiceStoreStockMapper, with exact41983 service component.

Baseline10 passed /155 assertions /6.17s after environment-only DB connection refusal recovered. Command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php --stop-on-failure --compact

First meaningful RED: TEST WRONG, rollback snapshot included pre-transaction admin_transaction_capability_used access audit. Narrow rerun identified audit_logs only; domain money/stock/revision rows rolled back. Accepted ADR0007 requires capability usage audit. Test now explicitly requires one access event and compares all mutation evidence unchanged; no mutation assertion weakened and no production patch.

Exact D1–D7 proven. Full ten-row set includes product/source type/source ID/qty/original unit cost/total cost. Six issues, three revision returns, one refund return; historical initial issues unchanged. R20 rejection rolls back money, current/revision rows, stock, costing, and mutation audit; valid R2 then succeeds. D5 same-key replay and fresh-key stale P2 refund do not change domain effects. Final P16/Q23/R17, inventory values315536/264569/233053; net movement COGS47139. Final partial/final cash causes no stock events.

Focused:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Inventory/PrimitiveInventoryRevisionRefundChainFeatureTest.php --stop-on-failure --compact

GREEN1 passed /88 assertions /5.60s.

Focused+adjacent:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Inventory/PrimitiveInventoryRevisionRefundChainFeatureTest.php tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php tests/Feature/Note/PrimitiveRefundRevisionReceivableChainFeatureTest.php --stop-on-failure --compact

GREEN12 passed /310 assertions /6.43s. Major gate now running:

    make verify > /tmp/glasspos-0018-slice6-verify.log 2>&1

No broad pass claimed yet. Commit/push pending major gate. Next active after GREEN: Slice7 first identity probe, stale-base edit/public request contract. Do not skip this known gap to later slices.

Slice6 major gate GREEN: make verify exit0; PHPStan no errors, line/Blade/contract audits PASS;1718 passed /12022 assertions /78.33s. Slice6 COMPLETE. No production files changed during Chains A–D. Commit/push pending; next active Slice7 stale-base identity probe only.

Slice6 publication: assistant commit967b5d93; HTTPS git push exit0,6f7896b7→967b5d93. Latest broad GREEN1718/12022 applies through Slice6. Slice7 first stale-editor probe active; current request rules expose no base revision/version field, workflow resolves current server pointer without comparing an editing base. ADR0045 explicitly requires characterization before implementation for this condition.

## Slice 7 — first stale-editor identity probe — STOP: CONTRACT GAP

Date2026-09-17. Slice3/4/5/6 are GREEN and published; Slice7 is not GREEN. Slices8–12 have NOT started. User mandated STOP where public request cannot express correctness; no owner decision inferred from general repair authorization.

Refreshed ADR0045 Stale Edit / Lost Update Contract, Blueprint0018 V04/section9 Slice7, UpdateTransactionWorkspaceRequest/Rules/InputNormalizer/Controller, CreateNoteRevisionHandler/Workflow and idempotency service. Public update rules have no base_revision_id/expected_revision/version input. Controller passes validated request; workflow locks the root then resolves its latest current revision but never compares it to the editor's base. A row lock serializes writes without detecting a stale editor. Distinct command keys do not convey an editing base.

Nearest existing baseline:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/EditTransactionWorkspaceRevisionPaymentCharacterizationTest.php --stop-on-failure --compact

GREEN5 passed /80 assertions /8.21s.

New LOCAL ONLY test: tests/Feature/Note/PrimitiveRevisionIdentityContractFeatureTest.php. One bounded HTTP identity probe, with real create and two editor GETs before either submission. R1 service63719; first editor commits R2 service81258; second editor's stale R1 draft submits51983 using a distinct command key. No invented base-version request field or direct lifecycle DB writes.

Focused command:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRevisionIdentityContractFeatureTest.php --stop-on-failure --compact

RED1 failed /8 assertions /6.15s, exit1. Failure at test line50: expected pointerR2, actualR3; actual revision count3, total51983, HTTP302. Thus newer accepted value81258 was silently superseded by stale editor content. Normative assertion is retained; no acceptance assertion added to bless current last-write-wins.

Classification: CONTRACT GAP at public request boundary, with directly proven behavior contrary to ADR0045. Root cause: missing client editing-base identity plus no base/current comparison under the revision lock. This is an explicit owner STOP condition even though silent overwrite is already forbidden. Implementing a base token or another conflict policy requires the public-contract decision. No production fix, no ADR edit, no second identity probe, no downstream continuation.

Owner decision needed before next implementation: define the required editing-base identity in the public update payload, handling for absent/stale tokens and caller compatibility, then authorize validation/comparison under the existing lock. Recommended direction is explicit base revision identity with stale rejection and refresh; do not invent merge/last-write-wins semantics.

Adjacent tests after RED: not run; baseline above and broad1718/12022 gate before this test remain the last GREEN evidence. No broad GREEN claim applies to the working tree containing this deliberately failing test. Slice7 commit SHA: NONE. Push status: NOT COMMITTED / NOT PUSHED. Handoff including this STOP and Slice6 publication receipt is local/uncommitted. Latest published GREEN commit967b5d93. Preserve local RED evidence for the owner; do not accidentally include it in a GREEN publication.

Exact next active slice remains7, first stale-base contract decision and repair only after authorization. Remaining Slice7 probes and slices8–12 remain pending. Historical0065 and completed1–2c remain CLOSED. No production files changed in this continuation.

## Slice 7 — owner contract resolved; implementation in progress (2026-09-17)

The owner explicitly requires base_revision_id on existing-note edits, authoritative comparison inside the root lock, STALE_REVISION with JSON409 or established form session error, and zero domain effects on stale rejection. Exact successful idempotency replay precedes stale validation; changed payload remains conflict. Drafts preserve the observed base, including stale bases; missing base must not default to current. Legacy editor uses existing initial-revision bootstrap. Slice11 retains concurrent-race ownership.

Repository observation supersedes the earlier LOCAL ONLY receipt: another local process committed a59da03a and 7b276002 (Update project). Do not attribute those commits to this assistant. Owner then explicitly instructed `sai push` after every file change. This changes publication cadence; intermediate publication is not a GREEN claim. The script stages all files, commits, and pushes. Assistant ran `sai push "wip: extend stale revision contract proof and response handling"`: commit c2f8d87a, push exit0, main7b276002→c2f8d87a.

Implemented paths currently include workspace revision request/workflow/handler/controller; editor and draft base propagation; paid nominal correction request/locked mutation/form; add-rows public route delegation to existing immutable revision workflow. Idempotency semantic hash includes target root. Stale workflow failure rolls back processing receipt and all transactional effects. Test fixtures supply explicit observed revision identity, with retry payloads preserving their original base.

Focused command from repository root:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRevisionIdentityContractFeatureTest.php --compact

Latest proof GREEN2 passed /49 assertions /6.81s, exit0. Covers matching base, stale form/JSON rejection and raw domain cardinality, exact replay, changed-payload conflict, missing/unrelated base, different target root, stored stale draft, next current revision and stale nominal correction after surplus payout. Previous continuation adjacent proof4/298 and13/185 exists, but final adjacent and broad verification remain pending. Full test suite currently running to identify callers needing explicit base fixture migration. No Slice7 completion or current broad GREEN claim.

Remaining active work: add-rows proof and fixture migration; legacy/draft refresh behavior; final revision/idempotency/draft adjacent regressions and static checks. Exact next active slice remains7. Slices8–12 have not started. No new unresolved owner semantic gap has been established. Latest broad GREEN still applies only through Slice6 at967b5d93.

## Slice 7 — COMPLETE / GREEN (2026-09-17)

Owner base-revision contract is implemented on existing public workspace revision, nominal correction and add-rows paths. Create remains base-free. Root comparison happens under the existing transaction/root lock; unsuccessful workflow rolls back idempotency processing receipt. Actor/key replay and changed-payload conflict precede stale checking, with root identity included in semantic hash. Browser errors retain redirect/session behavior, JSON stale returns409/code STALE_REVISION. Drafts preserve base; missing draft base stays empty; explicit “Muat revisi terbaru” refreshes state and base together. Existing legacy editor bootstraps R1 before submission. No concurrent barrier redesign; Slice11 owns that proof.

Additional classifications:

- TEST WRONG: first full test run6 failed/1714 passed/12021 assertions/85.38s, all six old caller fixtures omitted the owner-required base. Explicit request bases added; replay payload captures one original base. A mechanical edit accidentally added base_revision_id to an idempotency_records DB expectation (not a column):1 failed/16 passed/412 assertions; removed that accidental expectation, preserved the original receipt assertion.
- PRODUCTION BUG: reordered/remove/re-add snapshot proof found product_name_snapshot stored as QA P but NoteRevisionLineSnapshotLabelResolver returned Line1. RED1 failed/2 passed/65 assertions/6.07s. Resolver now prioritizes immutable product_name_snapshot; no master lookup or old snapshot rewrite. Focused identity+add-rows GREEN5 passed/81 assertions/6.34s.
- Tool/configuration: standalone PHPStan default128MB exhausted; rerun --memory-limit=1G passed. App line audit found one101-line builder; removed one blank line, no bypass label. Major gate subsequently passed.

Regressions for original six fixture failures plus identity:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRevisionIdentityContractFeatureTest.php tests/Feature/Note/AddNoteRowsHttpFeatureTest.php tests/Feature/Note/CorrectPaidServiceOnlyWorkItemHttpFeatureTest.php tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php tests/Feature/Reporting/TransactionReportRevisionCashTruthFeatureTest.php --compact --stop-on-failure

GREEN19 passed/466 assertions/7.39s, exit0.

Final focused+adjacent (includes stricter exact replay/conflict messages, missing-base draft and explicit refresh):

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRevisionIdentityContractFeatureTest.php tests/Feature/Note/AddNoteRowsHttpFeatureTest.php tests/Feature/Note/PrimitiveCancelCorrectionVersionChainFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceLineTypeCharacterizationTest.php tests/Feature/Note/CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest.php tests/Feature/Note/EditTransactionWorkspaceRevisionPaymentCharacterizationTest.php tests/Feature/Note/CorrectPaidServiceOnlyWorkItemHttpFeatureTest.php --compact --stop-on-failure

GREEN26 passed/356 assertions/7.24s, exit0.

Major gate:

    make verify > /tmp/glasspos-0018-slice7-verify.log 2>&1

GREEN exit0; PHPStan2118 files no errors, line/Blade/contract audits PASS;1721 passed/12100 assertions/78.70s. Gate began at8839827b; only stricter replay/conflict test assertions changed afterward in9ccbe9c5 and passed the final26/356 suite above. No production changes after gate start. git diff --check967b5d93..HEAD exit0. Working tree clean before this receipt.

Publication follows the owner's newer “sai push after changes” instruction. Confirmed script pushes to main: c2f8d87a,6605282d,0ef329f2,0a479bc4,cb545cd4,c76c9a3e,1c18b96d,8839827b,9ccbe9c5; every invocation exited0. Latest tested proof commit9ccbe9c5 is pushed. This completion receipt is published separately via sai push. Exact next active slice8 presentation contract. Slices9–12 not started; earlier closed0065 and Slices1–6 remain closed.

### Slice 7 changed production and test files

Exact scope inventory from git diff967b5d93..9ccbe9c5 (includes changes another process committed in a59da03a/7b276002; attribution above):

- app/Adapters/In/Http/Controllers/Cashier/Note/EditTransactionWorkspacePageController.php
- app/Adapters/In/Http/Controllers/Note/AddNoteRowsController.php
- app/Adapters/In/Http/Controllers/Note/CorrectPaidServiceOnlyWorkItemController.php
- app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php
- app/Adapters/In/Http/Requests/Note/AddNoteRowsRequest.php
- app/Adapters/In/Http/Requests/Note/CorrectPaidServiceOnlyWorkItemRequest.php
- app/Adapters/In/Http/Requests/Note/SaveTransactionWorkspaceDraftRequest.php
- app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceRules.php
- app/Application/Note/Services/BuildAddNoteRowsRevisionPayload.php
- app/Application/Note/Services/CorrectPaidServiceOnlyWorkItemMutation.php
- app/Application/Note/Services/CorrectPaidServiceOnlyWorkItemTransaction.php
- app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php
- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- app/Application/Note/Services/NoteDetailRevisionViewDataBuilder.php
- app/Application/Note/Services/NoteRevisionLineSnapshotLabelResolver.php
- app/Application/Note/UseCases/CorrectPaidServiceOnlyWorkItemHandler.php
- app/Application/Note/UseCases/CreateNoteRevisionHandler.php
- app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php
- public/assets/static/js/pages/cashier-note-workspace/draft.js
- resources/views/cashier/notes/partials/correction-actions.blade.php
- resources/views/cashier/notes/workspace/create.blade.php
- tests/Feature/Inventory/PrimitiveInventoryRevisionRefundChainFeatureTest.php
- tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php
- tests/Feature/Note/AddNoteRowsHttpFeatureTest.php
- tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php
- tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php
- tests/Feature/Note/CashierEditPageUsesCurrentRevisionFeatureTest.php
- tests/Feature/Note/CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.php
- tests/Feature/Note/CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.php
- tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php
- tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php
- tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php
- tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php
- tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php
- tests/Feature/Note/CorrectPaidServiceOnlyWorkItemFeatureTest.php
- tests/Feature/Note/CorrectPaidServiceOnlyWorkItemHttpFeatureTest.php
- tests/Feature/Note/CorrectPaidServiceOnlyWritesMutationTimelineFeatureTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php
- tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
- tests/Feature/Note/EditTransactionWorkspaceRevisionPaymentCharacterizationTest.php
- tests/Feature/Note/NoteEditRefundSettlementDecisionPolicyTest.php
- tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php
- tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php
- tests/Feature/Note/NoteRevisionRollbackFeatureTest.php
- tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php
- tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php
- tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php
- tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php
- tests/Feature/Note/PaymentTimelineRevisionTruthFeatureTest.php
- tests/Feature/Note/PrimitiveCancelCorrectionVersionChainFeatureTest.php
- tests/Feature/Note/PrimitiveFullyRefundedNewReceivableFeatureTest.php
- tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php
- tests/Feature/Note/PrimitivePaymentDebtCashChainFeatureTest.php
- tests/Feature/Note/PrimitiveRefundRevisionReceivableChainFeatureTest.php
- tests/Feature/Note/PrimitiveRevisionIdentityContractFeatureTest.php
- tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php
- tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php
- tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php
- tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
- tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php
- tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php
- tests/Feature/Note/TransactionWorkspaceServiceCatalogSyncFeatureTest.php
- tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php
- tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php
- tests/Feature/Reporting/ServicePackageProfitBreakdownHttpWorkflowFeatureTest.php
- tests/Feature/Reporting/TransactionReportRevisionCashTruthFeatureTest.php
- tests/TestCase.php

## Slice 8 — COMPLETE / GREEN (2026-09-17)

Slice7 receipt3eef7451 push confirmed exit0. Slice8 consumes exact A and B checkpoint values, with production HTTP/domain actions building the fixtures. No lifecycle arithmetic engine added. Only production change: NoteDetailPageDataBuilder exposes root current_total_rupiah; shared payment-summary-actions labels the immutable snapshot Total Revisi and shows Tagihan Aktif when root current total differs. B4 explicitly displays395933 snapshot versus253394 active, B6 displays427741/241658/186083 while historical payments remain395933 and three refund source amounts remain20002/89457/33080. No lifecycle state repair on page reads.

Baseline:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.php tests/Feature/Note/EditTransactionWorkspaceRevisionPaymentCharacterizationTest.php --compact --stop-on-failure

GREEN6 passed/87 assertions/5.99s. Existing node scripts/test-cashier-payment-intent.mjs baseline all13 cases PASS, exit0.

Classifications:

- TEST WRONG: A presentation test assumed closed-note GET workspace must403; actual200. Existing CashierProtectedNoteRoutesAccessGuardFeatureTest explicitly permits GET; ClosedNoteRevisionPolicyFeatureTest/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest prohibit PATCH. Replaced transport assumption with actual fresh-base PATCH403 and unchanged state; no mutation assertion weakened. Initial1 failed/27 assertions/6.10s; corrected focused+access9 passed/49 assertions/5.93s.
- TEST WRONG / harness timing: DevTools Page.reload raced production page-freshness.js automatic Back reload. Dedicated tab and bounded waiting for navigation.type=reload fix the harness; production freshness unchanged.
- B4 initial1 failed/1 passed/34 assertions/5.77s: grand_total_rupiah is explicitly sourced from immutable revision395933, not active root253394. The test's field assumption was wrong; Blueprint section7 permits snapshot only with clear labeling. PRODUCTION BUG in rendered ambiguity: generic Total did not identify snapshot or show active charge. Preserved old field semantics and added root current-total presentation; final test requires both values and both labels. No refund arithmetic changed.

Files:

- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- resources/views/shared/notes/partials/payment-summary-actions.blade.php
- tests/Feature/Note/PrimitiveLifecyclePresentationContractFeatureTest.php
- tests/Browser/cashier-payment-intent.html (optional backend settlement fixture fields)
- scripts/test-primitive-lifecycle-presentation.mjs
- scripts/test-primitive-lifecycle-pages.mjs
- this handoff

Focused/browser commands:

    node scripts/test-primitive-lifecycle-presentation.mjs
    node scripts/test-primitive-lifecycle-pages.mjs

First runner:12 scenarios PASS, exit0; A1/A4/A5/B8 each workspace/detail/Simple use exact Blueprint inputs. Simple uses existing exact-tender behavior; credited amount matches all surfaces, detailed cash preserves distinct tender/change. B8 fixture corrected to268777 net/158964 credit/170003 tender before its first execution.

Second runner: HTTP2 passed/53 assertions/6.06s, then actual application-rendered DOM with production Bootstrap/JS at widths1280 and390 PASS. Checks modal focus, exact112903 credit/120011 tender/7108 change/137983 remaining, modal viewport fit, Back cash pane, Back/reload/Forward with real page-freshness, and no settle action after A5. HTML is exported from real HTTP fixtures before DB rollback; browser navigation uses these immutable rendered pages and local public assets. This proves rendered interaction, not a second independent live-server payment acceptance (HTTP test owns acceptance). Browser screenshots inspected at /tmp/glasspos-primitive-pages-FCV2ZN/a4-cash-1280.png and a4-cash-390.png; mobile screenshot viewed directly, readable amounts and buttons, transient standard success toast remains at top. Original intermediate screenshot /tmp/glasspos-primitive-pages-Xf6Ouf/a4-cash-1280.png was also viewed.

Final focused+adjacent:

    php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveLifecyclePresentationContractFeatureTest.php tests/Feature/Note/CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.php tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php tests/Feature/Note/PaymentTimelineRevisionTruthFeatureTest.php tests/Feature/Note/NoteDetailSurplusDispositionPayloadFeatureTest.php tests/Feature/Note/CashierNoteDetailDevicePresentationFeatureTest.php tests/Feature/Note/AdminNoteDetailDevicePresentationFeatureTest.php tests/Feature/Note/CashierCreateWorkspacePresentationFeatureTest.php --compact --stop-on-failure

GREEN20 passed/447 assertions/12.56s, exit0. PHPStan --memory-limit=1G --no-progress PASS; app line audit PASS; git diff --check3eef7451..HEAD exit0. No new major make verify gate in this small presentation slice; last major1721/12100 through Slice7 remains accurately scoped.

Publication: owner requires sai push for every change. All Slice8 changes pushed through fdcbaef9; intermediate commits6d5d22e3,2d784572,74d0fa2e,caf62f86,9f000364,d2c4c473,5630148b,8602df6c,d3367f42. SSH push of2d784572 initially failed publickey, then sai push succeeded with per-process HTTPS URL rewrite and GitHub CLI credential helper; no permanent Git config change. Later sai pushes use the same per-process HTTPS environment. Final proof commitfdcbaef9 push exit0. This receipt committed/pushed separately.

Exact next active slice9 Reporting Chain E and cross-date dataset contract. Slices10–12 have not started. No unresolved owner-level gap in Slice8's exercised contracts; no historical closure reopened.

## Slice 8 recovery closeout — COMPLETE / GREEN (2026-09-19)

Scope: Blueprint0018 Slice8 only. The recovery began with a clean tree at 2ffdbddb080599f114eb6233096d21f19d6fea3e, including the existing Slice8 receipt524fe04d and a pre-existing reporting commit. No Slice9 implementation was performed in this recovery. On continuation, the two recovered test changes had been committed outside this assistant's execution as 130e8b96654982886bf2df278854e721eb8995b7 (`Update project`); git fetch origin and HEAD...origin/main confirmed0/0. No uncommitted Slice8 production work was lost.

Changed files for this recovery:

- tests/Feature/Note/PrimitiveLifecyclePresentationContractFeatureTest.php
- scripts/test-primitive-lifecycle-pages.mjs
- this handoff

No production change in this recovery. The prior Slice8 production fix remains the shared payment summary's explicit revision snapshot versus active charge labels. Existing public JSON/error envelopes and lifecycle arithmetic are unchanged.

Semantic proof adds current detail/editor revision identity, B6 complete settlement tuple, surplus due/paid history11736 each with no pending cash-out action, rejected-payment validation and read-only snapshots including cash details, both payment allocation representations, refund allocations, revision settlements, child component rows, mutation snapshots and audit outbox. These are presentation/read invariants, not a duplicate finance engine.

Classifications:

- TEST WRONG: expanded evidence used the shared helper's id ordering on work_item_service_details, whose primary key is work_item_id. RED2 failed/7 assertions; corrected only the test snapshot ordering. Next focused GREEN2/58.
- TEST WRONG: the second device export consumed an already-aged validation flash, causing browser timeout. An initial persistent session-error workaround leaked the error into the next successful request (RED1 failed/1 passed/55 assertions). Re-flashing the actual returned ViewErrorBag preserves the second render and normal flash lifetime. No production validation change.
- Environment failures, not domain RED: default test DB connection refused (2 failed/0 assertions); sandbox denied MariaDB/PHPStan local sockets and, after continuation, Node child-process spawn. An isolated disposable MariaDB database and approved execution resolved these. No production database used.
- No new PRODUCTION BUG, CONTRACT GAP or unresolved SOURCE CONFLICT established by the recovery.

Execution context for all commands: /home/asyraf/projects/laravel/GlassPos. Disposable DB initialization: mariadb-install-db --datadir=/tmp/glasspos-slice8-recovery-db --auth-root-authentication-method=normal --skip-test-db. Server: mariadbd --datadir=/tmp/glasspos-slice8-recovery-db --socket=/tmp/glasspos-slice8-recovery.sock --port=3318 --bind-address=127.0.0.1 --pid-file=/tmp/glasspos-slice8-recovery.pid --log-error=/tmp/glasspos-slice8-db.log. Database: mariadb --protocol=tcp --host=127.0.0.1 --port=3318 --user=root -e 'CREATE DATABASE glasspos_slice8_test'.

Final browser command:

    env DB_HOST=127.0.0.1 DB_PORT=3318 DB_DATABASE=glasspos_slice8_test DB_USERNAME=root DB_PASSWORD= node scripts/test-primitive-lifecycle-pages.mjs

GREEN exit0, HTTP2 passed/69 assertions/1.17s (includes extra handset export assertions). Chromium1280x844 and390x844: actual server-selected handset and desktop markup; modal open/focus, close/backdrop removal, reopen intent persistence, insufficient-tender disabled save, sufficient-tender enabled save, modal scroll to action, amount fit, A5 action absence, Back/reload/Forward freshness, server validation popup fit/dismissal, B4/B6 ordinary history source rows and separate surplus labels, editor/detail horizontal fit. Final artifacts /tmp/glasspos-primitive-pages-ip0MlM; validation-390.png visually inspected after animation settled. Earlier direct screenshot review included B4 handset, B6 desktop, editor handset and cash modal handset. Temporary artifacts are not durable publication assets.

    node scripts/test-primitive-lifecycle-presentation.mjs

GREEN exit0,12 scenarios: A1/A4/A5/B8 across workspace/detail/Simple; exact credited equality and intentional exact-tender Simple behavior. This runner's synthetic fixture does not prove a live browser submission.

Final focused plus adjacent command:

    env DB_HOST=127.0.0.1 DB_PORT=3318 DB_DATABASE=glasspos_slice8_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveLifecyclePresentationContractFeatureTest.php tests/Feature/Note/CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.php tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php tests/Feature/Note/PaymentTimelineRevisionTruthFeatureTest.php tests/Feature/Note/NoteDetailSurplusDispositionPayloadFeatureTest.php tests/Feature/Note/CashierNoteDetailDevicePresentationFeatureTest.php tests/Feature/Note/AdminNoteDetailDevicePresentationFeatureTest.php tests/Feature/Note/CashierCreateWorkspacePresentationFeatureTest.php --compact --stop-on-failure

GREEN20 passed/457 assertions/4.15s, exit0. make audit-contract GREEN (line/Blade/contract); ./vendor/bin/phpstan analyze --memory-limit=1G --no-progress GREEN/no errors; git diff --check GREEN. No new broad make verify: recovery changes only tests/browser proof/docs, and prior Slice8 production scope is the small shared presentation fix. Historical broad1721/12100 remains scoped to Slice7; no current full-suite GREEN claim, and pre-existing Slice9 tests were not executed as part of recovery.

Visual consistency inspection: Blueprint0014 supersedes compulsory workspace stepper ceremony, while0011 remains the shared mobile card direction for detail/history. Workspace and detail styles both consume cashier-surface/cashier-border/accent tokens; detail handset uses shared numbered sections and the same payment-summary-actions partial as desktop/admin. Added active-charge metric reuses note-detail-payment-metric, not a new card style. Existing cash modal boxes use their established bordered panel treatments; buttons retain shared Bootstrap primary/outline roles and status badges. Screenshots show readable amounts, single-column handset content, consistent palette/section hierarchy, and no new visual fork. Existing workspace/detail radius and section-density differences follow their existing POS versus audit presentation roles; no unrelated redesign performed.

Explicit proof limits / MANUAL QA remaining for Blueprint0018 Slice12: physical handset keyboard/safe-area/PWA behavior and live-server browser payment/refund submission/retry with persisted refresh across the complete battle cards. Chromium responsive emulation is not physical-device evidence. Exported pages execute real production markup/assets and navigation reload behavior, but their underlying HTTP fixture is rolled back; HTTP tests independently prove accepted mutations and subsequent read-only persisted reads. These unexecuted end-to-end/device checks remain pending, not GREEN. No remaining Slice8 scoped semantic or responsive-browser blocker.

Publication: implementation proof commit130e8b96654982886bf2df278854e721eb8995b7 is already published and verified equal to origin/main. This recovery receipt is published separately via sai push; final receipt SHA/push output and clean status are reported in the session closeout. STOP after Slice8. Do not interpret the earlier handoff's proposed next Slice9 as authorization to start it in this session.

## Slice 9 — Chain E reporting (2026-09-20 continuation)

Status: focused and adjacent GREEN; major gate/publication pending until the receipt below. Started from clean74fb9983fb2d8a00e5e875cd3c18054efdf922e4, matching origin/main after fetch. Recovered existing characterization2ffdbddb080599f114eb6233096d21f19d6fea3e rather than recreating the chain. Its unchanged baseline passed1 test/114 assertions. A usage-limit interruption preserved the three uncommitted timestamp assertions; subsequent environment reset removed temporary DB/process/log files, not repository work. No Slice8 redesign or reopened closure.

First meaningful RED: PRODUCTION BUG,1 failed/67 assertions. E5 surplus disposition occurred_at was2026-09-15 00:00:00 instead of actual action2026-09-15 09:04:00. CreateNoteRevisionSettlementCommitter passes note business date to automatic surplus recorders, which incorrectly also used it as audit/action time. ADR0027 temporal policy and Blueprint0018 forbid this. Small repair: both recorders use settlement.createdAt, captured from ClockPort by the revision workflow, for occurredAt; payout effectiveDate remains the business date. No historical backfill, reporting repair, amount change, schema or public-envelope change.

Changed files:

- app/Application/Note/Services/AutoSettleNoteRevisionSurplusRefundDueRecorder.php
- app/Application/Note/Services/AutoSettleNoteRevisionSurplusRefundPaymentRecorder.php
- tests/Feature/Reporting/PrimitiveLifecycleReportingChainFeatureTest.php
- tests/Support/AssertsPrimitiveReportingSurfaces.php (new)
- this handoff

Test-only classifications during expansion: incorrect page route name (missing .index); expected surplus audit in outbox although contextual binding writes canonical audit_events synchronously with FK references; package service_price is only the auto-split fee, while total_service_component includes fee plus package profit and preserves exact41983. These were TEST WRONG, corrected against inspected routes/bindings/composer/row mapper, without changing Chain E literals. First major gate caught typed auth factory user() lookup in helper; corrected to guard()->user(), with no suppression.

Execution context: /home/asyraf/projects/laravel/GlassPos. Disposable MariaDB port3318, database glasspos_slice9_test, root without password; no production data. After environment reset, initialize with mariadb-install-db --datadir=/tmp/glasspos-slice9-db --auth-root-authentication-method=normal --skip-test-db; start mariadbd --datadir=/tmp/glasspos-slice9-db --socket=/tmp/glasspos-slice9.sock --port=3318 --bind-address=127.0.0.1 --pid-file=/tmp/glasspos-slice9.pid --log-error=/tmp/glasspos-slice9-db.log; create only glasspos_slice9_test. Approved local socket execution required outside sandbox.

Focused:

    env DB_HOST=127.0.0.1 DB_PORT=3318 DB_DATABASE=glasspos_slice9_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Reporting/PrimitiveLifecycleReportingChainFeatureTest.php --compact --stop-on-failure

GREEN1 passed/367 assertions/1.55s. Earlier minimal timestamp repair passed1/117. All E1–E8 original amounts unchanged. E4/E5/E6/E7/E8 real page/PDF/Excel HTTP requests consume the real transaction source reader with identical filters/numeric source rows, not canned report results; page arrays and PDF view-builder inputs match authoritative dataset, actual XLSX summary/detail cells match integer values. Actual PDF generation succeeds. Twice-read datasets and full domain snapshots remain unchanged, covering child rows, payment cash details, both allocation types, refund allocations, settlements/due/payout, stock balance/cost/movements, mutation snapshots, projection, idempotency and both canonical/legacy audit stores. Package component totals, stock ledger/value reconciliation and unfinished debt queue match each checkpoint's defined scope. Sep15 current-note report includes latest settled state; Sep16 note-date dataset empty; cash ledger Sep15 excludes158964 and Sep16 includes it. Final payment recorded_at remains2026-09-16 10:11:12; E5 canonical audit events retain09:04:00 while payout business date staysSep15.

Focused plus adjacent:

    env DB_HOST=127.0.0.1 DB_PORT=3318 DB_DATABASE=glasspos_slice9_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Reporting/PrimitiveLifecycleReportingChainFeatureTest.php tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php tests/Feature/Reporting/TransactionSummaryPerNoteHardeningFeatureTest.php tests/Feature/Reporting/TransactionReportRevisionCashTruthFeatureTest.php tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php tests/Feature/Reporting/ServicePackageProfitBreakdownHttpWorkflowFeatureTest.php tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php --compact --stop-on-failure

GREEN20 passed/656 assertions/2.36s. Later auth guard typing correction is covered by the final major gate below.

Major gate command:

    env DB_HOST=127.0.0.1 DB_PORT=3318 DB_DATABASE=glasspos_slice9_test DB_USERNAME=root DB_PASSWORD= make verify > /tmp/glasspos-0018-slice9-verify.log 2>&1

Proof limits: this slice proves report dataset/export consumption, not new PDF visual design, physical handset behavior, complete live-server battle cards, or concurrency. Those remain Slice12 and Slice11 respectively. No browser/device claim added. No report-side domain mutation or repair. Slice10 durable audit/atomicity is not closed by surplus audit timestamp proof. Exact next active slice after GREEN/publication: Slice10 only.

Final major gate GREEN exit0: PHPStan2121 files/no errors; line/Blade/contract audits PASS;1724 passed/12531 assertions/56.74s. git diff --check PASS. Slice9 COMPLETE / GREEN for its automated reporting scope. Implementation and this receipt are committed/published next; exact commit SHA and verified push are recorded in the following publication receipt. Slice10 remains next, not yet executed.

Slice9 publication receipt:174584529dd4663bdbe6f55ea43a3dd4eba3c0f4 committed all five scoped files. Initial sai push via SSH failed publickey; retry through per-process HTTPS URL rewrite and gh auth git-credential succeeded exit0 (74fb9983..17458452 main -> main). No permanent Git configuration changed. git rev-parse HEAD origin/main returned174584529dd4663bdbe6f55ea43a3dd4eba3c0f4 for both; working tree clean before this receipt. This documentation receipt is published separately via sai push. Exact next active slice10 audit/atomicity; Slices11–12 remain pending, including physical-device/manual battle cards.

## Slice 10 — actual payment audit path characterization (2026-09-20)

Owner requires actual successful RecordAndAllocate payment evidence before any outbox failure injection. One critical step only: baseline plus runtime characterization. No production repair, writer mock, audit binding override, new semantic event, or Slice11 concurrency work in this checkpoint.

Execution context: /home/asyraf/projects/laravel/GlassPos. Separate disposable MariaDB /tmp/glasspos-slice10-db, localhost3319, database glasspos_slice10_test, root with empty password. Sandbox socket creation failed with errno1; approved outside-sandbox server and test access succeeded. No production database used.

Baseline command:

    env DB_HOST=127.0.0.1 DB_PORT=3319 DB_DATABASE=glasspos_slice10_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php tests/Feature/Note/PrimitivePaymentDebtCashChainFeatureTest.php tests/Feature/AuditLog/AuditOutboxRuntimeBindingTest.php tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php --compact --stop-on-failure

Baseline GREEN: 10 passed /167 assertions /0.89s, exit0 (assistant-executed local tool output).

New test: tests/Feature/Note/PrimitiveLifecycleAuditAtomicityFeatureTest.php. Creates the existing mixed primitive fixture through the real workspace HTTP action with inline payment skipped. Captures all listed tables before payment, then directly resolves and invokes the real RecordAndAllocateNotePaymentHandler with logged-in cashier, selected current rows, explicit actor-scoped idempotency payload, cash395933/tender400003/change4070. Production AuditLogPort and global AuditEventWriterPort bindings are asserted, not replaced. No outbox processor is run.

Focused command:

    env DB_HOST=127.0.0.1 DB_PORT=3319 DB_DATABASE=glasspos_slice10_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveLifecycleAuditAtomicityFeatureTest.php --compact --stop-on-failure

Exact RED: 1 failed /38 assertions /0.82s, exit1. All payment assertions pass before the final ADR-0042/A01 durable capture assertion fails: expected positive audit_outbox delta, actual0. Failure message contains before/after cardinalities and raw-row change indicators:

| Surface | Before -> after / observed effect |
|---|---|
| customer_payments | 0 -> 1; cash395933, returned payment ID verified |
| payment_allocations | 0 -> 0; unchanged legacy allocation set |
| payment_component_allocations | 0 -> 6; product142539, external53127, service142993, store57274; sum395933 |
| customer_payment_cash_details | 0 -> 1; credited395933/tender400003/change4070 |
| notes | 1 -> 1; open -> closed; total395933/current revision retained |
| note_history_projection | 1 -> 1; paid0/outstanding395933 -> paid395933/outstanding0 |
| idempotency_records | 1 -> 2; new record_note_payment succeeded, actor/key/note/result payment ID verified |
| note_mutation_events / snapshots | 0 -> 1 / 0 -> 2; note_closed |
| audit_logs | 1 -> 2; payment_allocated context matches payment/note/amount/component count/selected IDs |
| audit_outbox | 2 -> 2, entire row set unchanged; pre-existing create-time rows are NOT payment capture |
| audit_events / audit_event_snapshots | 0 -> 0 / 0 -> 0, unchanged |
| inventory movements / revisions / refunds / refund allocations | unchanged raw row sets |

Classification: PRODUCTION BUG against ADR-0042 Audit Runtime Contract (money mutation requires same-transaction durable outbox capture), matching Blueprint0018 A01 SOURCE CONFLICT. Successful legacy payment_allocated capture does not satisfy canonical outbox compliance. Global writer resolution proves availability only; this runtime payment produces no outbox capture.

Proof limits: RefreshDatabase wraps the test in a database transaction; evidence establishes application success and actual persisted row effects on that connection before test cleanup, not crash durability or separate-connection commit visibility. No failure injection/rollback atomicity claim, no new broad GREEN, and no Slice10 completion claim. The new acceptance test intentionally remains RED; do not change its expectation to accept missing outbox. PHP syntax check passed; git diff --check passed before receipt update.

Compatibility evidence: AuditLogAdminRowMapper exposes both legacy and canonical sources; payment_allocated is also used by AllocateCustomerPaymentHandler and CreateTransactionWorkspaceInlinePaymentRecorder. No removal/renaming or global binding change is authorized by this probe. ADR-0042 lists suggested revision/refund event names but does not name a new combined-payment event; no new event has been invented and this inspection alone does not establish that one is needed.

Next critical step remains within Slice10: establish the smallest existing-contract payment capture mapping and legacy compatibility boundary before repair. Stop if a new semantic event/owner decision is required. Only after actual payment outbox capture is proven may the called writer failure be injected to verify rollback of money/allocation/cash detail/note/projection/idempotency/audit effects. Slices11–12 remain untouched. No commit/push in this checkpoint.

## Slice 10 — payment success and actual canonical writer failure seam (2026-09-20)

Scope: payment success plus one canonical outbox failure seam only. Existing production changes in commits41573464/e0176193 were inspected; no production/domain code or historical migrations changed in this checkpoint.

Prior exit2 with61 assertions is classified TEST/ENVIRONMENT HARNESS FAILURE during cleanup, not domain RED. Migration2026_04_07_160100 leaves products.active_unique_marker in down(); that generated column depends on deleted_at. Later rollback of migration2026_04_06_230200 therefore fails when dropping deleted_at. Separate discovered migration defect/GAP, deferred outside Slice10.

Harness: PrimitiveLifecycleAuditAtomicityFeatureTest now uses DatabaseTruncation with no outer transaction and no migrate:rollback. RefreshDatabaseState::$migrated is reset in tearDown so later tests recreate schema instead of inheriting committed fixture rows. An intermediate combined run exposed that fixture leak (9 allocations versus expected3); final rerun below proves the harness isolation correction. Disposable database is removed externally after tests.

Execution context: repository root, isolated MariaDB localhost3319, database glasspos_slice10_test. All following results are assistant-executed local tool proof.

Command prefix for test runs:

    env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3319 DB_DATABASE=glasspos_slice10_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest

1. Focused success, rebuilt schema, before failure injection:

       tests/Feature/Note/PrimitiveLifecycleAuditAtomicityFeatureTest.php --filter=test_successful_record_and_allocate_payment_requires_durable_audit_capture --compact --stop-on-failure

   GREEN:1 passed/61 assertions/0.68s, exit0. Payment395933, six allocations, cash400003/change4070, closed note/projection, succeeded idempotency, legacy audit plus exactly one pending canonical payment_allocated outbox row. Semantic IDs, actor, timestamps and metadata checked. Transaction level0 before and after handler; no test transaction masks commit.

2. Focused success plus failure seam:

       tests/Feature/Note/PrimitiveLifecycleAuditAtomicityFeatureTest.php --compact --stop-on-failure

   GREEN:2 passed/108 assertions/0.87s, exit0. Production writer binding is retained. A connection-local QueryExecuted listener throws the exact sentinel exception after the real adapter inserts payment_allocated into audit_outbox, while transaction level is1. Snapshot inside the listener proves payment, six allocations, cash detail, succeeded-idempotency row, close event/two snapshots, legacy audit and canonical outbox existed before failure; note/projection changed and outbox aggregate matches payment ID. All17 captured table row sets equal their pre-payment state after rollback, with transaction level0. Listener is restored in finally. Retry with the same idempotency key commits successfully.

3. Final formatted test plus adjacent payment/audit regressions after harness isolation correction:

       tests/Feature/Note/PrimitiveLifecycleAuditAtomicityFeatureTest.php tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php tests/Feature/Note/PrimitivePaymentDebtCashChainFeatureTest.php tests/Feature/AuditLog/AuditOutboxRuntimeBindingTest.php tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php --compact --stop-on-failure

   GREEN:11 passed/262 assertions/2.06s, exit0. Pint --test on the changed test and git diff --check passed.

External cleanup, separately from test outcome:

    mariadb --protocol=tcp --host=127.0.0.1 --port=3319 --user=root --execute="DROP DATABASE glasspos_slice10_test; SELECT COUNT(*) AS remaining_test_databases FROM information_schema.schemata WHERE schema_name = 'glasspos_slice10_test';"

Cleanup PASS exit0; remaining_test_databases=0. No historical migration rollback used. Recreate this disposable database before a later test run.

Proof limits: this seam is an exception after a real SQL insert, not a simulated database outage or process crash. Commit/rollback are observed on the application connection; no independent connection durability or concurrency claim. Payment success and this failure seam are complete; Slice10 overall remains open. Refund/revision/surplus seams and Slices11–12 were not opened. Next: owner feedback on this payment checkpoint before selecting another seam. No commit/push performed.

## Slice 10 — revision and automatic surplus writer distinction (2026-09-20)

Owner execution policy now permits serial continuation across GREEN checkpoints and requests sai push after each verified change. No checkpoint-only STOP gate.

Revision without surplus initially succeeded with no canonical note_revision_created capture: focused1 failed/10 assertions, exit1. Existing event name is specified by ADR-0042. CreateNoteRevisionDurableAudit now writes that event in the existing revision transaction, preserving legacy capture, with revision/root/parent IDs, actual actor role, reason and revision action time. Production change was already present in commitccc9ba31 when this continuation resumed.

PrimitiveRevisionAuditAtomicityFeatureTest verifies successful revision capture, failure after the actual revision outbox insert, and failure after actual automatic surplus payout canonical insert. Both failure seams run through the authorized admin HTTP route and restore27-table business snapshots; the single admin_transaction_capability_used attempt event occurs before the business transaction and is separately asserted, not misclassified as a rollback leak. Same-key retry succeeds.

TEST WRONG corrections: closed-note direct handler default guard did not exercise authorized admin edit; HTTP route now owns authorization. Surplus does NOT use global outbox binding: InfrastructureServiceProvider contextually injects DatabaseAuditEventWriterAdapter due to hard audit_events foreign keys. This matches CLOSED FK compatibility in handoff0004 and Slice9's binding evidence. Test now targets actual audit_events insert and checks contextual binding, due/payout semantic IDs and FK event IDs. No FK, binding or historical migration changed. Automatic due and paid share the revision transaction; failure on the second canonical event rolls back the earlier due event/disposition, revision, stock replacement, allocations and all captured business effects. This does not establish standalone surplus-handler rollback equivalence.

Final command from repo root:

    env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3319 DB_DATABASE=glasspos_slice10_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRevisionAuditAtomicityFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php --compact --stop-on-failure

GREEN6 passed/166 assertions/2.36s, exit0. A sandbox connection-denied run had0 assertions and is environment failure, resolved by approved execution. Pint formatted the changed test. Payment seam not repeated. Next required distinction: ordinary refund canonical capture, followed by any necessary standalone surplus transaction proof. Slice10 not complete; no Slice11 claim.

## Slice 10 — selected-row ordinary refund durable capture (2026-09-20)

Source distinction: selected-row bucket processor calls RecordCustomerRefundOperation directly under one outer RecordSelectedRowsRefundPlanTransaction, not nested RecordCustomerRefundTransaction. One logical action can own multiple customer_refunds rows. Existing selected_rows_refund_plan_recorded metadata already groups their IDs and selected rows; canonical capture now preserves that existing event identity/metadata and legacy compatibility in the same transaction. No newly invented refund operation entity or domain semantics.

Initial focused acceptance RED:1 failed/8 assertions, exit1, successful refund never reached canonical writer. Repair adds outbox write to the existing plan audit recorder. Final actual-writer seam fires after outbox insert, after both refund buckets, stock reversal, shadow rows and projection;27-table snapshots restore exactly. Retry succeeds; canonical aggregate/root, refund IDs, selected row IDs, actor and142539 total are checked. Exact replay leaves the whole captured graph unchanged. No payment seam repeated.

Repo-root command prefix remains the isolated3319/glasspos_slice10_test environment from preceding receipt. Focused PrimitiveRefundAuditAtomicityFeatureTest GREEN1/62 assertions/0.72s exit0. Final focused plus PrimitiveRefundRevisionReceivableChainFeatureTest GREEN2/129 assertions/1.33s exit0, with --compact --stop-on-failure. Pint and git diff --check PASS. Next: distinguish direct customer refund and standalone FK-bound surplus handlers before Slice10 closure.

## Slice 10 closure — bounded audit atomicity GREEN (2026-09-20)

Direct RecordCustomerRefundHandler is distinct from selected-row plan: selected buckets call the operation directly, while direct handler uses PaymentTransactionRetryRunner. Direct capture acceptance initially RED1/5 assertions (no writer call); RecordCustomerRefundDurableAudit now preserves customer_refund_recorded identity and metadata with canonical outbox before commit. Actual insert failure rolls back captured27-table graph; successful retry verifies refund/payment/note IDs and amount142539. Focused ordinary/direct plus RecordCustomerRefundFeatureTest: GREEN6/96 assertions/1.79s exit0.

Standalone surplus due and paid each own a separate transaction. PrimitiveStandaloneSurplusAuditAtomicityFeatureTest uses the existing persisted settlement fixture boundary, then only real handlers. Production contextual DatabaseAuditEventWriterAdapter is asserted. Failure after real snapshot insertion proves one event plus two snapshots existed at transaction level1 before failure, while business insertion had not yet occurred. Canonical rows and27-table business graph roll back exactly. Retry creates due122000 then payout50000; event FK/aggregate IDs verified, remaining72000, same-key payout replay unchanged. Focused plus existing due/paid handler tests GREEN12/84 assertions/1.07s exit0. Initial nullable success-message assertion TypeError was TEST WRONG and corrected; no surplus production/binding/FK change.

Coverage equivalence: automatic due/paid share outer revision transaction, so its later paid-event failure covers earlier due capture/disposition and revision replacement. Standalone due/paid need their own proofs because they own independent transactions; both covered. Selected-row refund groups multiple source refunds under one plan transaction, direct refund has a separate transaction owner; both covered. Product/service/package combinations reuse those transaction/audit boundaries rather than form a Cartesian matrix. Payment's prior committed success/17-table rollback/same-key retry proof retained. Legacy audit compatibility retained throughout. FK-bound synchronous canonical capture remains the previously CLOSED compatibility contract, not a new claim of surplus outbox rows.

Required broad gate from repo root:

    env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3319 DB_DATABASE=glasspos_slice10_test DB_USERNAME=root DB_PASSWORD= make verify

GREEN exit0 (artifact /tmp/glasspos-slice10-final-verify.exit=0): PHPStan2128 files/no errors; line/Blade/contract audits PASS;1732 passed/12886 assertions/54.72s. Log /tmp/glasspos-slice10-final-verify.log. Previous interrupted process handle had the same1732 pass result but no captured exit, so gate rerun solely to capture durable exit proof. All formatting changes were immediately sai-pushed file by file per current owner instruction. Slice10 COMPLETE/GREEN for bounded application atomicity, not crash durability or concurrency. Historical migrate:rollback dependency defect remains separate/out of scope. Database remains disposable on3319 for next slice; no production DB used.

Next active Slice11: true independent connection/barrier proof for revision replay/conflict/stale ordering and same-note financial/inventory serialization. Do not label sequential tests concurrency. Slice12 remains afterward. Continue automatically per owner policy.

## Slice 11 first race — CLOSED replay contract contradiction / STOP (2026-09-20)

Slice10 publication receipt: f5f997525ca74cfc5a13b2b796361fa9aa7e58fb == origin/main; clean tree before opening Slice11. No payment audit seam repeated. User explicitly requires immediate sai push after every file change, so the new acceptance test was published before execution as characterization (b249c99f), not as a GREEN claim.

PrimitiveRevisionConcurrencyFeatureTest uses pcntl_fork, fresh independent connections after disconnect/purge, and a third observer connection. First real CreateNoteRevisionHandler holds the note lock inside its transaction at a QueryExecuted barrier; second submits exact same actor/key/payload/R1. Parent queries MariaDB INNODB_LOCK_WAITS and proves second connection1419 waiting on first1418 while neither result exists, then releases first. Both workers exit normally. This is true overlapping runtime evidence, not sequential replay.

Repo-root command:

    env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3319 DB_DATABASE=glasspos_slice10_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveRevisionConcurrencyFeatureTest.php --compact --stop-on-failure

RED1 failed/20 assertions/0.71s, exit1. First revision succeeds R002; second throws Illuminate\Database\UniqueConstraintViolationException, SQLSTATE23000/error1062 on idempotency_records_scope_key_unique. Before the failing replay assertion, test proves two revisions total (initial plus one new), root total402425, one create_note_revision idempotency row, one canonical note_revision_created outbox event, and transaction level0. It does not yet prove the full inventory race surface or other concurrency cases.

Classification: PRODUCTION BUG, not harness failure. Source: CreateNoteRevisionHandler calls idempotency replay before begin; both requests can see no committed record. Then start inserts processing before the root lock in workflow. Losing insert waits on the winning key, then fails after winner commit; handler rollback/rethrow never resolves the winner as replay. Exact replay contract was CLOSED for Slice7 sequential identity proof and remains required under current Slice11 ordering instructions; concurrent runtime now contradicts that behavior. Do not weaken acceptance to treat duplicate SQL error as replay.

Per owner's explicit STOP condition (runtime evidence contradictory to CLOSED contract), no production repair or changed event/ADR semantics chosen at this checkpoint. Next required work after owner feedback: repair concurrent revision idempotency while preserving exact replay > changed-payload conflict > genuinely-new stale-base ordering, then continue remaining Slice11 financial/inventory serialization and Slice12. Neither Slice11 nor Slice12 is GREEN. Latest branch includes an intentionally failing acceptance test; prior Slice10 broad GREEN does not describe this later test addition.

Proof artifact: /tmp/glasspos-revision-race-902660b7ecb506f7/proof.json (lock wait IDs, winner result and losing exception); test also keeps barrier/result files in that directory. Pint --test and git diff --check PASS. External cleanup separately PASS exit0: dropped only glasspos_slice10_test on localhost3319, schema count0. Recreate the disposable schema before resuming. No historical migration or production DB touched. All changes immediately sai-pushed per owner policy.

## Slice 11 — owner-authorized revision ordering repair GREEN (2026-09-21)

Owner classified the previous RED as a production bug and explicitly authorized repair without changing the CLOSED ordering contract. The previous STOP is superseded. Fast pre-transaction replay remains; two optimistic misses can still compete for the canonical unique scope. DatabaseIdempotencyRecordAdapter now classifies ONLY idempotency_records_scope_key_unique collisions through IdempotencyClaimCollisionClassifier and throws a typed port exception. Unrelated unique/database errors retain their original identity. CreateNoteRevisionHandler rolls back the losing transaction before resolving the winner through existing replay/conflict/processing semantics. No polling, note-lock reordering, or domain SQL parsing was introduced.

True forked independent-connection probes now cover exact replay, same-key changed payload, same-key different root, fresh-key stale base, and winner rollback permitting the waiting insert. Exact replay retains its original INNODB_LOCK_WAITS acceptance barrier and verifies identical revision ID, one new revision, one successful key, one canonical revision event, and unchanged 22-table effects after loser resolution. Additional probes permit server PROCESSLIST evidence of an executing claim/root-lock statement while the first transaction is held when MariaDB does not expose its lock graph; receipts explicitly distinguish that evidence from an INNODB edge. No sequential probe is labeled concurrency.

Focused command uses the preceding disposable3319 environment and PrimitiveRevisionConcurrencyFeatureTest.php --compact --stop-on-failure: exit0,5 passed/123 assertions/4.86s, /tmp/glasspos-slice11-ordering.exit=0 and matching .log. After formatting, focused plus PrimitiveRevisionIdentityContractFeatureTest and IdempotencyClaimCollisionClassifierTest passed17 tests/234 assertions/5.86s, exit0. Unit coverage includes misleading duplicate values, similarly named keys, unrelated unique/FK errors and driver-state negatives. Every file change immediately sai-pushed as requested.

Slice11 remains ACTIVE: next required distinction is same-note financial/revision and refund-inventory/revision serialization. Existing payment/payment and payment/refund T15 use independent workers and a start barrier; they do not alone establish a deterministic root-lock wait. Slice10 remains CLOSED. No Slice12 completion claim.
