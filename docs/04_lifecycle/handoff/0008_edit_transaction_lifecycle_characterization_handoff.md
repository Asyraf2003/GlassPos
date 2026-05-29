# Handoff - Edit Transaction Lifecycle Characterization

## Metadata

- Date: 2026-05-26
- Slice / topic: Phase 2 edit transaction lifecycle characterization, active revision route, rollback, store-stock inventory, refund current-row boundary, and closed-note edit policy
- Workflow step: Phase 2-01 through Phase 2-05 characterization
- Status: continue in next session
- Progress: Phase 2 characterization foundation GREEN; full verify still pending

## Target Work Page

Continue HyperPOS Phase 2 edit transaction lifecycle maturity.

This handoff records the completed characterization proofs for active edit/revision behavior after Phase 1 service-only create maturity closure.

The current target is to consolidate the Phase 2 focused suite, then run full verification before claiming broader Phase 2 closure.

## References Used

- Blueprint:
  - docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md
  - docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md
  - docs/03_blueprints/db/0017_edit_refund_characterization_plan.md
  - docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- ADR:
  - docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md
- Previous handoff:
  - docs/04_lifecycle/handoff/0007_create_transaction_idempotency_handoff.md
  - docs/04_lifecycle/handoff/0004_refund_due_carry_forward_audit_fk_handoff.md
- Standards:
  - docs/04_lifecycle/handoff/README.md
  - docs/01_standards/0005_handoff_template.md
  - docs/01_standards/core/0010_scope_and_facts.md
  - docs/01_standards/core/0011_blueprint_first.md
  - docs/01_standards/core/0012_step_by_step_execution.md
  - docs/01_standards/core/0013_proof_and_progress.md
  - docs/01_standards/workflow/0020_response_structure.md
  - docs/01_standards/workflow/0021_active_step_policy.md
  - docs/01_standards/output/0033_terminal_command_delivery.md
- Repo snapshot / command output:
  - Operator command outputs from 2026-05-26 session included in this handoff.

## Locked Facts

- Phase 1 service-only create transaction maturity remains closed.
- UI/browser idempotency key delivery remains deferred.
- Active edit submit route is revision-based:
  - admin route: PATCH /admin/notes/{noteId}/workspace
  - cashier route: PATCH /cashier/notes/{noteId}/workspace
  - controller: app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php
  - request: app/Adapters/In/Http/Requests/Note/StoreNoteRevisionRequest.php
  - application entry: app/Application/Note/UseCases/CreateNoteRevisionHandler.php
- UpdateTransactionWorkspaceController and UpdateTransactionWorkspaceHandler exist but are not proven active production edit routes from routes/web/note.php.
- StoreNoteRevisionRequest forces inline_payment.decision to skip.
- Revision submit and payment submit remain separate flows.
- Admin route disables workspace editability enforcement in StoreNoteRevisionController.
- Cashier route keeps workspace editability enforcement and rejects closed note revision submit.
- Active revision workflow:
  - locks root note
  - resolves current revision
  - builds replacement note
  - builds revision settlement
  - applies active replacement
  - creates new revision
  - persists settlement
  - sets current revision pointer
  - records legacy audit note_revision_created
  - commits transaction
- ApplyNoteRevisionAsActiveReplacement:
  - captures payment allocation amounts
  - updates note header
  - deletes existing component payment allocations
  - persists replacement work items
  - updates note total
  - rebuilds payment allocations
  - syncs note_history_projection
- Store-stock revision path:
  - reverses previous issued inventory through transaction_workspace_updated reverse source type
  - deletes old current work item rows
  - creates replacement work items
  - issues inventory for replacement store-stock lines
- Refund after revision must use current replacement work item id, not stale historical work item id.
- note_history_projection is a fast current read model, not historical truth.
- Audit remains transitional:
  - revision still uses legacy AuditLogPort/audit_logs for note_revision_created
  - refund_due/refund_paid compatibility with audit_events was closed in earlier handoff
  - full audit unification is not part of this slice

## Scope Used

### SCOPE-IN

- Active note revision path.
- Rollback after active revision replacement side effects.
- Store-stock revision reversal/reissue lifecycle.
- Store-stock revision rollback after inventory side effects.
- Refund after revision current-row boundary.
- Admin vs cashier closed-note revision policy characterization.
- Focused adjacency verification for settlement, refund, rollback, inventory, and closed-note policy.

### SCOPE-OUT

- Production code patch.
- UI/browser QA.
- API/dashboard/seeder changes.
- Go API.
- PostgreSQL migration readiness.
- Report/export patch.
- Combined revision submit plus payment.
- Customer credit/customer balance entries.
- Full audit system unification.
- Git operations.

## GAP

- Full make verify has not been rerun after adding the Phase 2 characterization tests.
- Full consolidated Phase 2 focused suite has not yet been run in a single command.
- Browser/manual QA is not done.
- Report/export proof after edit/refund/revision is not complete.
- Full audit timeline/canonical audit contract for edit lifecycle remains transitional.
- Seeder cleanup/foundation remains deferred.
- PostgreSQL and Go readiness are not claimable.
- Performance target is not measured.
- No production bug was patched in this session because no RED production defect was produced.

## Locked Decisions

- Phase 2 edit transaction lifecycle work must target active revision route, not legacy UpdateTransactionWorkspaceHandler.
- Do not patch UpdateTransactionWorkspaceHandler unless route binding proof or dead-path cleanup decision exists.
- Keep revision submit and payment submit separate.
- Do not start with UI.
- Do not patch reports to hide settlement or projection mismatch.
- Store-stock revision must preserve inventory movement ledger correctness.
- Refund after revision must reject stale historical row id and accept current replacement row id.
- Admin can submit closed paid note revision through active admin revision route.
- Cashier cannot submit closed paid note revision through active cashier revision route.
- Phase 2 characterization foundation is GREEN through focused tests, but Phase 2 is not broadly closed until focused consolidation and make verify pass.

## Files Created / Changed

### New files

- tests/Feature/Note/NoteRevisionRollbackFeatureTest.php
- tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php
- tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php
- tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php
- tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php
- docs/04_lifecycle/handoff/0008_edit_transaction_lifecycle_characterization_handoff.md

### Changed files

- None intentionally changed in production code.
- Existing test files were used as adjacency proof but not changed in this session.

## Verification Proof

- command:
  - php artisan test tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php
- result:
  - Tests: 7 passed (34 assertions)
  - Duration: 8.12s
- meaning:
  - Active revision rollback after audit failure is characterized.
  - Replacement side effects roll back.
  - Settlement carry-forward, refund_due, refund_paid, and overpaid replay adjacency remain GREEN.

- command:
  - php artisan test tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php
- result:
  - Tests: 8 passed (68 assertions)
  - Duration: 7.34s
- meaning:
  - Store-stock revision reversal/reissue lifecycle is characterized.
  - Old store-stock issue is reversed once.
  - Replacement stock is issued.
  - Inventory qty/costing/projection are asserted.
  - Reverse duplicate protection adjacency remains GREEN.

- command:
  - php artisan test tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php
- result:
  - Tests: 6 passed (56 assertions)
  - Duration: 6.57s
- meaning:
  - Store-stock revision rollback after inventory side effects is characterized.
  - No stock_in reverse leaks.
  - No replacement stock_out leaks.
  - Original inventory qty/costing remain intact.
  - Original work item/store-stock line remain intact.

- command:
  - php artisan test tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php
- result:
  - Tests: 9 passed (70 assertions)
  - Duration: 7.04s
- meaning:
  - Refund after revision rejects stale old row id.
  - Refund after revision accepts current replacement row id.
  - Refund allocation points to current replacement row id.
  - Existing refund controller behavior remains GREEN.

- command:
  - php artisan test tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php
- result:
  - Tests: 6 passed (48 assertions)
  - Duration: 7.12s
- meaning:
  - Admin closed paid note revision policy is characterized as allowed.
  - Cashier closed paid note revision policy is characterized as forbidden.
  - Existing cashier closed-note replacement guard remains GREEN.
  - Refund-after-revision boundary remains GREEN.

## Risks / Follow-up Notes

- The new tests are characterization tests, not production patches.
- Full project verification has not been run after these new tests.
- If make verify fails, treat the failure as the next active blocker.
- Do not claim Phase 2 broadly closed before full verification.
- Do not start report/API/Go/PostgreSQL work while verification status is unknown.
- Do not interpret admin closed-note revision as a general free-for-all; it is characterized as current active behavior and should later be documented or hardened by explicit policy/ADR if needed.
- Legacy UpdateTransactionWorkspaceHandler remains source-present but route-unproven.
- Edit/refund lifecycle is improving, but audit is still transitional.

## Next Step

Phase 2-06 - Consolidated Phase 2 focused verification.

Goal:

Run all Phase 2 characterization tests plus key adjacency tests in one focused command.

If focused suite is GREEN, run full make verify.

If focused suite or make verify is RED, stop and source-map the first failure only.

Do not patch production before RED proof.

Suggested focused command:

    php artisan test \
      tests/Feature/Note/NoteRevisionRollbackFeatureTest.php \
      tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php \
      tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php \
      tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php \
      tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php \
      tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php \
      tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php \
      tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php \
      tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php \
      tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php \
      tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
      tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

If focused command is GREEN, run:

    make verify

## Opening Prompt For Next Session

Use this prompt exactly.

    Kita lanjut repo HyperPOS. Jangan sentuh git. Baca dulu:
    - docs/04_lifecycle/handoff/README.md
    - docs/01_standards/0005_handoff_template.md
    - docs/01_standards/core/0010_scope_and_facts.md
    - docs/01_standards/core/0011_blueprint_first.md
    - docs/01_standards/core/0012_step_by_step_execution.md
    - docs/01_standards/core/0013_proof_and_progress.md
    - docs/01_standards/workflow/0020_response_structure.md
    - docs/01_standards/workflow/0021_active_step_policy.md
    - docs/01_standards/output/0033_terminal_command_delivery.md
    - docs/04_lifecycle/handoff/0008_edit_transaction_lifecycle_characterization_handoff.md

    Current status:
    - Phase 1 service-only create transaction maturity remains closed.
    - Phase 2 edit transaction lifecycle characterization foundation is GREEN through focused operator proofs.
    - Active edit route is revision-based through StoreNoteRevisionController, StoreNoteRevisionRequest, and CreateNoteRevisionHandler.
    - UpdateTransactionWorkspaceHandler exists but is route-unproven and must not be patched as active edit path.
    - Revision submit and payment submit remain separate. StoreNoteRevisionRequest forces inline_payment.decision = skip.
    - Phase 2-01 active revision rollback characterization is GREEN.
    - Phase 2-02 store-stock revision reversal/reissue characterization is GREEN.
    - Phase 2-03 store-stock revision rollback after inventory side effects is GREEN.
    - Phase 2-04 refund after revision current-row boundary is GREEN.
    - Phase 2-05 admin vs cashier closed-note revision policy is GREEN.
    - Latest Phase 2-05 proof:
      php artisan test tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php
      Result: 6 passed (48 assertions), Duration: 7.12s.
    - Full make verify has not been rerun after the Phase 2 characterization tests.
    - Browser/manual QA is not done.
    - Report/export proof after edit/refund/revision remains a gap.
    - Audit is still transitional.

### Phase 2-06 - Consolidated Phase 2 focused verification

    Status: GREEN / CLOSED

    Proof command:

    php artisan test \
      tests/Feature/Note/NoteRevisionRollbackFeatureTest.php \
      tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php \
      tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php \
      tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php \
      tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php \
      tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php \
      tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php \
      tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php \
      tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php \
      tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php \
      tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
      tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

    Result:

    Tests: 22 passed (166 assertions)
    Duration: 8.30s

    Full verification command:

    make verify

    Result:

    phpstan:
    [OK] No errors

    Line limit audit:
    SUCCESS: Semua file memenuhi standar limit baris (atau memiliki label bypass).

    Blade PHP/directive audit:
    SUCCESS: Tidak ditemukan PHP/directive PHP di Blade resources/views.

    Contract audit:
    Contract audit passed.

    Pest:
    Tests: 2 skipped, 1108 passed (6153 assertions)
    Duration: 55.65s

    Closure decision:

    Phase 2 focused characterization verification is closed through operator proof.

    Remaining gaps:

    - Browser/manual QA is not done.
    - Report/export proof after edit/refund/revision remains a gap.
    - Audit is still transitional.

### Phase 2-07 - Report/export proof after active revision refund

Status: GREEN / CLOSED

Goal:

Close the report/export proof gap for edit/refund/revision by proving that an active note revision followed by refund of the current replacement row is visible through transaction cash ledger report surfaces.

Files added:

- tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php

Characterization covered:

- Seed closed paid service-only note.
- Submit active note revision through CreateNoteRevisionHandler.
- Reject stale-row refund remained covered by existing adjacency proof.
- Refund current replacement work item through active refund route.
- Verify transaction cash ledger page includes:
  - note id
  - Alokasi Pembayaran
  - Pengembalian Dana
  - payment_allocations
  - customer_refunds
  - payment id
  - refund id
  - Rp 100.000
- Verify Excel export includes payment and refund events in Detail Event Kas.
- Verify PDF export returns a valid downloadable PDF for the same lifecycle range.

Targeted proof:

php artisan test tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php

Result:

Tests: 1 passed (25 assertions)
Duration: 6.90s

Focused consolidation command:

php artisan test \
  tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php \
  tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php \
  tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php \
  tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php \
  tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php \
  tests/Feature/Note/NoteRevisionRollbackFeatureTest.php \
  tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php

Focused consolidation result:

Tests: 23 passed (226 assertions)
Duration: 8.66s

Full verification command:

make verify

Full verification result:

Tests: 2 skipped, 1109 passed (6178 assertions)
Duration: 56.77s

Closure decision:

Report/export proof after edit/refund/revision is closed through targeted characterization, focused consolidation, and full make verify proof.

Remaining gaps:

- Browser/manual QA is not done.
- Audit is still transitional.

### Phase 2-09 - Closed paid edit settlement preview

Status: GREEN / CLOSED

Goal:

Fix manual QA bug where closed paid edit workspace rendered the Proses Nota/payment modal without backend settlement context. The UI must still show settlement context when current payable is zero, because closed/paid notes still need carry-forward context before revision/payment decisions.

Files added/changed:

- tests/Feature/Note/ClosedPaidNoteEditPaymentSettlementPreviewFeatureTest.php
- app/Application/Note/Services/NotePaymentSettlementPreviewResolver.php
- app/Application/Note/Services/EditTransactionWorkspacePaymentSettlementDataBuilder.php
- app/Application/Note/Services/NoteOutstandingPaymentAmountResolver.php
- resources/views/cashier/notes/workspace/partials/payment-modal.blade.php
- public/assets/static/js/pages/cashier-note-workspace/payment-flow.js
- tests/Feature/Note/CashierWorkspacePaymentFlowJavascriptContractTest.php

Proof:

Initial RED:

php artisan test tests/Feature/Note/ClosedPaidNoteEditPaymentSettlementPreviewFeatureTest.php

Result:

FAILED
Expected response to contain: Settlement pembayaran backend

Patch summary:

- Added preview-only resolver for edit workspace settlement context.
- Kept payment submit resolver semantics separate.
- Exposed backend net paid/gross total dataset to payment modal.
- Updated JS contract so backend settlement context is used even when initial payable is zero.

Focused proof:

php artisan test \
  tests/Feature/Note/ClosedPaidNoteEditPaymentSettlementPreviewFeatureTest.php \
  tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php \
  tests/Feature/Note/CashierWorkspacePaymentFlowJavascriptContractTest.php \
  tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php \
  tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php \
  tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php \
  tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php

Result:

Tests: 12 passed (57 assertions)
Duration: 7.01s

Full verification:

make verify

Result:

Tests: 2 skipped, 1110 passed (6188 assertions)
Duration: 57.56s

Closure decision:

Closed paid edit settlement preview is closed through RED proof, targeted fix, focused proof, and full make verify proof.

### Phase 2-10 - Automated coverage replacement for manual QA

Status: IN PROGRESS

Goal:

Convert Phase 2-08 manual QA checklist into automated coverage where possible. Browser-only checks remain manual unless a real browser runner exists.

Coverage decisions:

- Browser availability, console errors, visual responsive behavior, real modal focus, and real double-click timing remain browser/manual gaps unless Dusk/Playwright or equivalent is introduced.
- HTTP/render/static JS contracts are acceptable automated coverage for server and render behavior.
- Existing Phase 2 report/export/revision/refund tests remain part of the focused safety net.

### Phase 2-10B - JS backend settlement zero-payable contract

Status: GREEN / CLOSED

Goal:

Lock the JS contract that payment-flow uses backend settlement context even when initial payable is zero.

Files changed:

- tests/Feature/Note/CashierWorkspacePaymentFlowJavascriptContractTest.php

Proof:

Operator reported targeted and focused tests GREEN after adding assertions for:

- dataset.backendNetPaidRupiah
- dataset.backendGrossTotalRupiah
- backend_outstanding_settlement basis
- Math.max(total - context.netPaid, 0)
- absence of old fallback: return backendPayable > 0 ? backendPayable : total;

Closure decision:

JS zero-payable backend settlement contract is closed by operator GREEN proof.

### Phase 2-10C - Payment after active revision delta HTTP proof

Status: GREEN / CLOSED

Goal:

Prove that after a closed paid note is revised upward, old money is carried forward and the payment route only accepts the upward delta.

Target scenario:

- Original closed paid note total: 100000
- Existing payment: 100000
- Active revision through CreateNoteRevisionHandler changes total to 120000
- Expected outstanding delta: 20000
- Expected payment route accepts 20000
- Expected new allocation points to current replacement row
- Expected note projection becomes fully settled after delta payment

Files added/changed:

- tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php
- app/Application/Note/Services/CurrentRevision/CurrentRevisionRowSettlementProjector.php

Important warning:

The latest patch to CurrentRevisionRowSettlementProjector is NOT accepted as valid. It produced focused RED. Treat it as suspect state for the next session.

RED proof 1:

php artisan test tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php

Initial result:

FAILED

Session error:

Hanya billing row outstanding yang boleh dipilih untuk pembayaran.

Source-map debug result:

Financial core was correct before payment delta:

- customer_payments.amount_rupiah: 100000
- payment_allocations.amount_rupiah: 100000
- payment_component_allocations.allocated_amount_rupiah: 100000
- note_revision_settlements.outstanding_rupiah: 20000
- note_history_projection.outstanding_rupiah: 20000

But workspace/payment selection projection was wrong:

- workspace_panel_rows.allocated_rupiah: 120000
- workspace_panel_rows.outstanding_rupiah: 0
- billing_rows.allocated_rupiah: 120000
- billing_rows.outstanding_rupiah: 0
- billing_rows.can_select_manually: false

Diagnosis at that point:

- selected row ID was correct
- payment route was not the first source of the bug
- payment_component_allocations table was correct
- note_history_projection was correct
- workspace panel/payment selection projection double-counted or over-merged settlement

Patch attempted:

Changed CurrentRevisionRowSettlementProjector to stop merging note-level remainders when component allocations/refunds exist.

Focused command after patch:

php artisan test \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Note/ClosedPaidNoteEditPaymentSettlementPreviewFeatureTest.php \
  tests/Feature/Note/CashierClosedReplacementOutstandingPaymentFeatureTest.php \
  tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php \
  tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php \
  tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php \
  tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php \
  tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php

Focused result after patch:

FAILED

First failure:

Tests\Feature\Note\PaymentAfterRevisionSettlementFeatureTest
test_admin_can_pay_only_upward_delta_after_active_closed_paid_revision

Session error:

Total alokasi pada note melebihi total note.

Second failure:

Tests\Feature\Note\RecordNotePaymentHttpFeatureTest
test_selected_row_payment_uses_combined_legacy_and_component_allocations

Session error:

Amount alokasi payment melebihi outstanding note.

Stop decision:

Stop on RED. Do not run make verify. Do not close Phase 2-10C. Do not close manual QA replacement. Next session must source-map first failure only.

Next recommended source-map:

1. Inspect payment submit path after the projector patch:
   - RecordNotePaymentController
   - SelectedNoteRowsPaymentAmountResolver
   - SelectedNoteRowsOutstandingTotalResolver
   - payment allocation writer/guard that throws "Total alokasi pada note melebihi total note."

2. Determine whether the projector patch made selected outstanding 20000 but payment allocation guard still sees total allocated as:
   - legacy payment_allocations 100000
   - component allocations 100000
   - new delta 20000
   - combined total 220000

3. If so, the next likely issue is not workspace projection anymore, but payment guard/reader double-counting component + legacy allocations during allocation validation.

4. Do not patch broadly. First source-map the first failure only:
   - "Total alokasi pada note melebihi total note."

Remaining gaps:

- Phase 2-10C RED / OPEN.
- Browser/manual QA replacement is not closed.
- Browser-only QA remains manual unless real browser automation is introduced.
- Audit is still transitional.

### Phase 2-10C closure update - Payment after active revision delta HTTP proof

Status: GREEN / CLOSED

Files changed in closure:
- tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php
- app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- app/Application/Note/Services/CurrentRevision/CurrentRevisionRowSettlementProjector.php

Closure summary:
- Fixed note-level payment allocation reader so legacy allocations are not double-counted when component allocations exist for the same note_id + customer_payment_id pair.
- Fixed current revision row settlement projection so valid legacy note-level remainder is merged into component row settlement without reintroducing same-payment double-counting.
- Phase 2-10C target scenario is now proven:
  - Original closed paid note total: 100000.
  - Existing payment: 100000.
  - Active revision changes total to 120000.
  - Delta payment route accepts 20000.
  - New allocation points to current replacement row.
  - Projection becomes fully settled after delta payment.

Focused proof:
php artisan test \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Note/ClosedPaidNoteEditPaymentSettlementPreviewFeatureTest.php \
  tests/Feature/Note/CashierClosedReplacementOutstandingPaymentFeatureTest.php \
  tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php \
  tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php \
  tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php \
  tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php \
  tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php

Focused result:
Tests: 12 passed (84 assertions)
Duration: 7.72s

Full verification proof:
make verify

Full verification result:
Tests: 2 skipped, 1112 passed (6205 assertions)
Duration: 49.31s

Phase 2-10 automated QA replacement status:
- Automated HTTP/render/static JS replacement scope is GREEN/CLOSED.
- Browser-only manual QA remains manual unless Dusk/Playwright or equivalent browser runner is introduced.
- Remaining browser-only checks:
  - Browser availability.
  - Console errors.
  - Visual responsive behavior.
  - Real modal focus.
  - Real double-click timing.

Remaining gaps:
- Browser-only QA remains manual.
- Audit is still transitional.


### Phase 2-11 - Create package auto split multi-part pricing research

Status: RESEARCH / BLUEPRINT READY, NOT IMPLEMENTED

Blueprint:
- docs/03_blueprints/finance/0007_create_package_auto_split_multi_part_pricing.md

Reason:
- Workshop create flow needs cashier-friendly package pricing.
- Cashier should enter total package charge and related products/parts.
- System should split total into product/part total and service residual.
- Store-stock package pricing existed, but current create path is effectively first-line only.
- External purchase package pricing was previously out of scope and now needs a simplified create design.

Locked user decisions:
- Implement create first.
- Keep edit/revision as future-readiness design only.
- Default UX should be auto/package total.
- Manual split remains available but hidden/advanced.
- Service + store-stock must support multiple products in one service row.
- Duplicate store-stock product in the same service row must be rejected with a clear reason and solution.
- Service + external purchase first implementation should use a simple total external part amount.
- External label is optional and can stay hidden initially for field observation.
- External qty is optional.
- External total_rupiah is required.
- Package total below product/external total must be rejected.
- Package total equal to product/external total is valid and service price may be 0.
- Audit metadata is mandatory.
- Report/export impact must be analyzed before deciding whether to patch report/export.

Primary formula:
- service_price_rupiah = package_total_rupiah - parts_total_rupiah

Implementation status:
- No production code changed in this research step.
- No test added in this research step.
- No verification command required beyond documentation presence proof.

Known current repo limitation:
- create product_lines and external_purchase_lines normalization/validation/mapping are effectively first-line only.
- Multi store-stock product support is therefore a root create-flow change, not a small UI-only patch.

Next recommended active step:
- Add RED characterization test for service + store-stock package_auto_split with two different products in one service row.
- Do not patch production before RED proof.
- Do not start edit/revision implementation before create package semantics are GREEN.

Remaining gaps:
- Exact hidden/advanced UI layout is not finalized.
- Exact note-level operational note storage is not finalized.
- Suggestion/history storage is not finalized.
- External purchase total_rupiah schema/input mapping needs inspection before implementation.
- Report/export impact is not proven yet.
- Edit/revision remains future-readiness only.


### Phase 2-11 - Package auto split create maturity closure

#### Scope

Phase 2-11 closed create-side package auto split maturity for service workspace rows.

Included scope:
- service + store-stock `package_auto_split` with multiple different `product_lines` in one service row;
- duplicate `product_id` rejection inside one service row;
- service + external purchase `package_auto_split`;
- package allocation audit payload for store-stock and external purchase package allocations;
- report impact characterization for transaction summary and operational profit reports.

Explicitly out of scope:
- edit/revision implementation;
- payment lifecycle redesign;
- API/Go/PostgreSQL work;
- report/export patching;
- browser/manual QA;
- PDF/XLSX visual/manual review.

#### Implementation summary

Create store-stock package split now preserves multiple product lines instead of collapsing to the first line.

Key behavior:
- `package_total_rupiah` is treated as the final customer-facing package price.
- Store-stock sparepart total is computed from all product lines using trusted catalog selling price.
- Service residual is computed as:

    service_price_rupiah = package_total_rupiah - summed_store_stock_part_total

- Multiple `work_item_store_stock_lines` are persisted under the same service work item.
- Inventory issue is executed once per persisted store-stock line.
- Package allocation audit now records one allocation row per store-stock line.

Duplicate product behavior:
- Duplicate `product_id` inside one service row is rejected.
- Error message is actionable:
  - product may not be input twice in one service row;
  - reason: prevents duplicate package allocation and duplicate stock recording;
  - fix: increase `qty` on the existing product line.

External purchase package behavior:
- External package split accepts simple `external_purchase_lines[0][total_rupiah]`.
- Service residual is computed as:

    service_price_rupiah = package_total_rupiah - external_total_rupiah

- Persisted external purchase line uses:
  - default label: `Pembelian luar` when label is blank;
  - `qty = 1`;
  - `unit_cost_rupiah = total_rupiah`;
  - `line_total_rupiah = total_rupiah`.
- Audit payload records external package allocation metadata.

Report impact:
- Transaction summary report reads `notes.total_rupiah`, so package totals are visible without report patching.
- Operational profit reads:
  - external purchase cost from `work_item_external_purchase_lines.line_total_rupiah`;
  - store-stock COGS from `inventory_movements` with `source_type = work_item_store_stock_line`.
- Report patch was not indicated.
- Focused report characterization proves create output is visible to transaction and profit reports.

#### Files touched

Production/request:
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceProductLineNormalizer.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceProductLineListNormalizer.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceProductLineShape.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceProductLineValueNormalizer.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceExternalPurchaseLineNormalizer.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceServicePriceValidator.php`

Production/application:
- `app/Application/Note/Services/CreateTransactionWorkspaceProductLineCollection.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackagePricingComposer.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceStoreStockLineMapper.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/Services/CreateTransactionWorkspacePackageAllocationAuditMapper.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceDuplicateProductLineGuard.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php`
- `app/Application/Note/Services/CreateTransactionWorkspacePersistResult.php`

Tests:
- `tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php`
- `tests/Feature/Note/CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php`
- `tests/Feature/Reporting/PackageAutoSplitCreateReportImpactFeatureTest.php`

#### RED proof captured during the slice

Store-stock multi-product package RED:
- Expected service price: `120000`
- Actual service price before patch: `150000`
- Cause: only first store-stock product line was used in package split.

Duplicate product RED:
- Expected redirect: `/cashier/notes/workspace/create`
- Actual redirect before patch: `/cashier/notes`
- Cause: duplicate product was accepted and transaction was created.

External purchase package RED:
- Expected redirect: `/cashier/notes`
- Actual redirect before patch: `/`
- Validation error:
  - `Harga servis wajib lebih dari 0 kecuali package service + sparepart.`
- Cause: zero service price was only allowed for package service + store-stock, not package service + external purchase.

#### GREEN proof

Focused store-stock package proof:
- multi-product package test passed;
- duplicate product rejection test passed;
- focused note adjacency suite passed.

Focused external purchase package proof:
- targeted external package test passed:
  - `Tests: 1 passed (11 assertions)`

Focused report impact proof:
- `tests/Feature/Reporting/PackageAutoSplitCreateReportImpactFeatureTest.php`
- Result:
  - `Tests: 1 passed (20 assertions)`

Final full verification:
- Command:
  - `make verify`
- Final proof:
  - `Tests: 2 skipped, 1116 passed (6262 assertions)`
  - `Duration: 55.01s`

Because `make verify` reaches the final Pest summary only after earlier verification gates pass, this also confirms:
- PHPStan passed;
- audit-lines passed;
- Blade PHP/directive audit passed;
- contract audit passed;
- full Pest suite passed.

#### Residual gaps

Still not done:
- browser/manual QA;
- PDF/XLSX visual/manual review;
- edit/revision implementation;
- edit/refund/revision report/export lifecycle proof beyond existing prior closure;
- broader audit lifecycle redesign;
- API/Go/PostgreSQL scope.

#### Next recommended active step

Recommended next step:
- Phase 2-12 - decide whether to continue with browser/manual QA, handoff cleanup, or open the next lifecycle slice.

Do not start edit/revision implementation unless a new blueprint step is selected and scoped.
Do not patch report/export unless a failing characterization proves a report/export gap.
Do not use report code to repair domain state.

