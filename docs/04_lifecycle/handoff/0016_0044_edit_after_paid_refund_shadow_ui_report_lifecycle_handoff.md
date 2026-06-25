# Handoff 0016 - 0044 Edit After Paid Refund Shadow UI Report Lifecycle

## Status

Active workflow handoff.

This handoff must be updated at the end of every 0044 session.

This file is not an implementation patch.

## Linked Documents

- Error log: `docs/04_lifecycle/error_log/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_gap.md`
- Workflow: `docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md`
- Architecture DoD: `docs/01_standards/architecture/0044_audit_and_dod.md`
- Finance residual DoD: `docs/03_blueprints/finance/0004_finance_residual_dod.md`
- Readiness analysis: `docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md`
- Characterization plan: `docs/03_blueprints/db/0017_edit_refund_characterization_plan.md`

## Connector Rule

Do not execute direct GitHub connector write actions for this workflow.

Assistant must provide local CLI commands only unless owner explicitly overrides this rule in the same session.

Reason:

Owner controls local patching, verification, git diff, commit, and push.

## Current Objective

Build and execute the 0044 workflow slowly, one verified slice at a time:

1. Structure/source-map.
2. Edit after unpaid/paid.
3. Refund shadow/historical truth.
4. Combined edit/refund/payment/stock matrix.
5. UI backend-derived action flags.
6. Browser refresh resilience.
7. Report/PDF/Excel parity.
8. Final blast-radius verification.

## Current Slice

Slice 0 - Structure, Source Map, and Guardrail.

## Workflow Checklist

### Slice 0 - Structure / Source Map

- [x] Active edit routes mapped.
- [x] Active refund routes mapped.
- [x] Active payment routes mapped.
- [x] Note detail UI data source mapped.
- [x] UI action flag builders mapped.
- [x] Note revision services mapped.
- [x] Payment allocation services mapped.
- [x] Refund allocation services mapped.
- [x] Inventory movement services mapped.
- [x] Note history projection mapped.
- [x] Surplus/refund_due/refund_paid records mapped.
- [x] Transaction report dataset mapped.
- [x] Excel export path mapped.
- [x] PDF export path mapped.
- [x] Dead/unproven paths listed.
- [x] First characterization test selected.
- [x] No production code patch made.

### Slice 1 - Edit After Unpaid/Paid

- [ ] Edit unpaid note characterization exists.
- [ ] Edit paid note carry-forward characterization exists.
- [ ] Edit paid upward revision characterization exists.
- [ ] Edit paid downward surplus characterization exists.
- [ ] Edit paid delete-all active lines characterization exists.
- [ ] Runtime patch, if any, has RED proof first.
- [ ] Targeted tests PASS.
- [ ] Existing relevant tests PASS.

### Slice 2 - Refund Shadow / Historical Truth

- [ ] Edit after ordinary refund characterization exists.
- [ ] Refund ledger/shadow persistence characterization exists.
- [ ] Refund after revision current row id test exists.
- [ ] Stale historical row id rejection test exists.
- [ ] Money effect and stock effect separation test exists.
- [ ] Runtime patch, if any, has RED proof first.
- [ ] Targeted tests PASS.
- [ ] Existing relevant tests PASS.

### Slice 3 - Combined Lifecycle Matrix

- [ ] Matrix dimensions selected.
- [ ] Paid/refund/edit upward case covered.
- [ ] Paid/refund/edit downward case covered.
- [ ] Paid/refund/delete-all active lines case covered.
- [ ] Service-only case covered.
- [ ] Product-only case covered.
- [ ] Store-stock case covered.
- [ ] Service package stock component case covered.
- [ ] Cash and transfer payment variants covered.
- [ ] Payment/refund/allocation/inventory/projection assertions exist.
- [ ] Targeted matrix PASS.

### Slice 4 - UI Backend-Derived Action Flags

- [ ] Note detail payment actions use backend flags.
- [ ] Invalid Lunasi/Bayar action hidden or explained.
- [ ] Edit action visibility matches lifecycle policy.
- [ ] Refund action visibility matches backend refundable rows.
- [ ] UI shows clear money/refund/stock/status wording.
- [ ] JS is not financial truth.
- [ ] Static render tests PASS.
- [ ] Feature UI tests PASS.

### Slice 5 - Browser Refresh Resilience

- [ ] Normal refresh behavior checked.
- [ ] Hard refresh behavior checked.
- [ ] Stale hidden payload rejected by backend.
- [ ] Modal values come from backend state.
- [ ] Manual QA proof or automated equivalent recorded.

### Slice 6 - Report / PDF / Excel Parity

- [ ] Official dataset source mapped.
- [ ] Screen report parity asserted.
- [ ] Excel export parity asserted.
- [ ] PDF view data parity asserted.
- [ ] PDF Blade parity asserted.
- [ ] Cash ledger affected fields asserted if touched.
- [ ] Operational profit affected fields asserted if touched.
- [ ] Service package breakdown affected fields asserted if touched.
- [ ] No report formula patch without write-side proof.

### Slice 7 - Final Blast Radius

- [ ] Targeted 0044 tests PASS.
- [ ] Relevant note revision tests PASS.
- [ ] Relevant payment tests PASS.
- [ ] Relevant refund tests PASS.
- [ ] Relevant inventory tests PASS if touched.
- [ ] Relevant reporting tests PASS if touched.
- [ ] Relevant export tests PASS if touched.
- [ ] make verify PASS.
- [ ] git diff --stat reviewed.
- [ ] Error log updated after proof.
- [ ] Residual gaps listed.
- [ ] Owner acceptance recorded.

## Session Update Template

Copy and fill this section at the end of every 0044 session.

```markdown
## Session Update - YYYY-MM-DD HH:MM

### Slice

- Active slice:
- Status:

### Files Read

- `path`

### Files Changed

- `path`

### FACT

- 

### GAP

- 

### DECISION

- 

### Tests / Commands Run

```bash
command
```

Result:

```text
output summary
```

Meaning:

- 

### Checklist Changes

- [ ] item

### Residual Gaps

- 

### Next Allowed Step

- 
```

## Session Log

### Session Update - 2026-06-25 Initial Workflow Setup

#### Slice

- Active slice: Slice 0 - Structure, Source Map, and Guardrail.
- Status: workflow/handoff setup only.

#### Files Read

- Existing error log 0044 if present.
- Existing architecture and finance DoD references from prior analysis.

#### Files Changed

- `docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md`
- `docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md`
- `docs/04_lifecycle/error_log/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_gap.md` only if it did not already exist.

#### FACT

- 0044 needs workflow and DoD before production patch.
- Every future 0044 session must update this handoff.
- Direct connector write execution is forbidden by default for this workflow.

#### GAP

- Active source map has not been completed yet.
- No characterization test has been added yet.
- No production patch has been made.

#### DECISION

- Start next session from Slice 0 source-map.
- Do not patch UI, report, payment, refund, or inventory yet.

#### Tests / Commands Run

```bash
grep -n \
  -e "## Session Rule" \
  -e "## Connector Rule" \
  -e "## Core Domain Policy" \
  -e "## Workflow Slices" \
  -e "### Slice 0" \
  -e "### Slice 7" \
  -e "## Global Stop Conditions" \
  -e "## Definition of Done" \
  docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md

grep -n \
  -e "## Connector Rule" \
  -e "## Workflow Checklist" \
  -e "### Slice 0" \
  -e "### Slice 7" \
  -e "## Session Update Template" \
  -e "## Session Log" \
  docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md
```

Result:

```text
Pending owner local run.
```

Meaning:

- Handoff setup must be verified locally before commit.

#### Checklist Changes

- [x] Workflow file drafted.
- [x] Handoff file drafted.
- [x] Connector write prohibition recorded.
- [ ] Slice 0 source map completed.

#### Residual Gaps

- Source map still pending.
- Characterization test selection still pending.
- Runtime behavior still unpatched.

#### Next Allowed Step

- Slice 0 source-map only.


### Session Update - 2026-06-25 18:26 WITA - Slice 0 Source Map Completion

#### Slice

- Active slice: Slice 0 - Structure, Source Map, and Guardrail.
- Status: source-map documented, dead/unproven paths listed, first characterization test selected.
- Production code patch: none.

#### Files Read

- `docs/04_lifecycle/error_log/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_gap.md`
- `docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md`
- `docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md`
- `docs/01_standards/architecture/0044_audit_and_dod.md`
- `docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md`
- `docs/03_blueprints/db/0017_edit_refund_characterization_plan.md`
- `routes/web.php`
- `routes/web/note.php`
- `routes/web/admin_reporting.php`
- `app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/NoteDetailPageController.php`
- `app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php`
- `app/Adapters/In/Http/Controllers/Note/RecordNotePaymentController.php`
- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `app/Adapters/In/Http/Controllers/Admin/Note/CreateNoteRevisionSurplusRefundDueController.php`
- `app/Adapters/In/Http/Controllers/Admin/Note/RecordNoteRevisionSurplusRefundPaymentController.php`
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Application/Note/Services/NoteDetailNotePayloadBuilder.php`
- `app/Application/Note/Services/NoteWorkspacePanelDataBuilder.php`
- `app/Application/Note/Services/NoteBillingProjectionBuilder.php`
- `app/Application/Note/Services/CurrentRevision/CurrentRevisionRowSettlementProjector.php`
- `app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailRowMapper.php`
- `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`
- `app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php`
- `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`
- `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php`
- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
- `app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php`
- `app/Application/Note/Services/SelectedNoteBillingRowsProvider.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php`
- `app/Application/Payment/Services/RecordCustomerRefundOperation.php`
- `app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php`
- `app/Application/Note/Services/NoteHistoryProjectionService.php`
- `app/Adapters/Out/Note/DatabaseNoteHistoryProjectionSourceReaderAdapter.php`
- `app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php`
- `app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php`
- `app/Adapters/In/Http/Controllers/Admin/Reporting/TransactionReportPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Reporting/TransactionReportExcelExportController.php`
- `app/Adapters/In/Http/Controllers/Admin/Reporting/TransactionReportPdfExportController.php`
- `app/Application/Reporting/UseCases/GetTransactionReportDatasetHandler.php`
- `app/Application/Reporting/UseCases/GetTransactionSummaryPerNoteHandler.php`
- `app/Adapters/Out/Reporting/DatabaseTransactionReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php`
- `app/Application/Reporting/Exports/TransactionReportExcelWorkbookBuilder.php`
- `app/Application/Reporting/Exports/TransactionReportExcelDetailSheetWriter.php`
- `app/Application/Reporting/Exports/TransactionReportPdfViewDataBuilder.php`
- `resources/views/shared/notes/show.blade.php`
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `resources/views/cashier/notes/partials/payment-modal.blade.php`
- `resources/views/admin/reporting/transaction_summary/export_pdf.blade.php`
- `public/assets/static/js/pages/cashier-note-payment.js`

#### Files Changed

- `docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md`

#### FACT

- Active edit routes:
  - `PATCH /admin/notes/{noteId}/workspace` -> `StoreNoteRevisionController` -> `StoreNoteRevisionRequest` -> `CreateNoteRevisionHandler`.
  - `PATCH /cashier/notes/{noteId}/workspace` -> `StoreNoteRevisionController` -> `StoreNoteRevisionRequest` -> `CreateNoteRevisionHandler`.
- Active refund routes:
  - `POST /admin/notes/{noteId}/refunds` -> `RecordClosedNoteRefundController`.
  - `POST /cashier/notes/{noteId}/refunds` -> `RecordClosedNoteRefundController`.
- Active payment routes:
  - `POST /admin/notes/{noteId}/payments` -> `RecordNotePaymentController`.
  - `POST /cashier/notes/{noteId}/payments` -> `RecordNotePaymentController`.
- Active note detail pages:
  - Admin detail -> `Admin\\Note\\NoteDetailPageController` -> `NoteDetailPageDataBuilder` -> `shared.notes.show`.
  - Cashier detail -> `Cashier\\Note\\NoteDetailPageController` -> `NoteDetailPageDataBuilder` -> `shared.notes.show`.
- Active UI action flags are built by backend payload:
  - `NoteDetailNotePayloadBuilder`
  - `can_edit_workspace`
  - `can_show_payment_form`
  - `can_show_partial_payment_action`
  - `can_show_settle_payment_action`
  - `can_show_refund_form`
- Active note revision services:
  - `CreateNoteRevisionHandler`
  - `CreateNoteRevisionWorkflow`
  - `ApplyNoteRevisionAsActiveReplacement`
  - `NoteReplacementPaymentAllocationReconciler`
  - `BuildCreateNoteRevisionSettlement`
  - `CreateNoteRevisionCommitter`
- Active payment allocation services:
  - `SelectedNoteRowsPaymentAmountResolver`
  - `SelectedNoteBillingRowsProvider`
  - `RecordAndAllocateNotePaymentHandler`
  - `RecordAndAllocateNotePaymentOperation`
  - `ResolveNotePayableComponents`
  - `AllocatePaymentAcrossComponents`
  - `PaymentAllocationPolicy`
- Active refund allocation services:
  - `SelectedNoteRowsRefundPlanResolver`
  - `SelectedRowsRefundBucketsBuilder`
  - `SelectedNoteRowsRefundEligibilityGuard`
  - `SelectedNoteRowsRefundPlanFactory`
  - `RecordSelectedRowsRefundPlanTransaction`
  - `RecordSelectedRowsRefundPlanBucketProcessor`
  - `RecordCustomerRefundOperation`
  - `AllocateRefundAcrossComponents`
- Active inventory refund effect source:
  - `AutoReverseRefundedStoreStockInventory`
  - `ReverseIssuedInventoryOperation`
  - `RefundedStoreStockComponentTargets`
- Active note history projection source:
  - `NoteHistoryProjectionService`
  - `DatabaseNoteHistoryProjectionSourceReaderAdapter`
  - `NoteHistoryAggregationSubqueries`
  - component/legacy line summary subqueries
- Active surplus/refund_due/refund_paid source:
  - `CreateNoteRevisionSurplusRefundDueController`
  - `CreateNoteRevisionSurplusRefundDueHandler`
  - `RecordNoteRevisionSurplusRefundPaymentController`
  - `RecordNoteRevisionSurplusRefundPaymentHandler`
  - `NoteRevisionSurplusDispositionActionViewDataBuilder`
  - `NoteSurplusDispositionAuditTimelineBuilder`
- Active transaction report dataset:
  - `TransactionReportPageController`
  - `GetTransactionReportDatasetHandler`
  - `GetTransactionSummaryPerNoteHandler`
  - `DatabaseTransactionReportingSourceReaderAdapter`
  - `TransactionSummaryReportingQuery`
- Active Excel export path:
  - `TransactionReportExcelExportController`
  - `TransactionReportExcelWorkbookBuilder`
  - `TransactionReportExcelDetailSheetWriter`
- Active PDF export path:
  - `TransactionReportPdfExportController`
  - `TransactionReportPdfViewDataBuilder`
  - `resources/views/admin/reporting/transaction_summary/export_pdf.blade.php`

#### GAP

- No local test command was executed in this Slice 0 source-map update.
- No local `php artisan route:list` proof was executed yet.
- Browser refresh and hard-refresh behavior are not proven.
- Report/PDF/Excel lifecycle parity after edit/refund/surplus/refund_paid is not proven.
- Cash ledger impact is not proven in this slice.
- DB row state for a real 0044 runtime case is not inspected in this slice.

#### DECISION

- Treat `UpdateTransactionWorkspaceController` and `UpdateTransactionWorkspaceHandler` as dead/unproven path for active edit behavior until route proof says otherwise.
- Do not patch UI, report, payment, refund, inventory, projection, or allocation code in Slice 0.
- First characterization target:
  - `tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`
  - first test: `test_revision_after_partial_payment_carries_paid_amount_into_underpaid_settlement`
  - second test: `test_revision_after_ordinary_refund_counts_refund_once_in_settlement`
- Next step is test-only, not runtime patch.

#### Tests / Commands Run

```bash
# ChatGPT connector read-only source inspection only.
# Local command generated to update handoff.
# No local test executed by assistant.
```

Result:

```text
Pending owner local proof after running this handoff update command.
```

Meaning:

- Slice 0 source-map is documented.
- Handoff checklist is updated.
- Production code remains untouched.

#### Checklist Changes

- [x] Active edit routes mapped.
- [x] Active refund routes mapped.
- [x] Active payment routes mapped.
- [x] Note detail UI data source mapped.
- [x] UI action flag builders mapped.
- [x] Note revision services mapped.
- [x] Payment allocation services mapped.
- [x] Refund allocation services mapped.
- [x] Inventory movement services mapped.
- [x] Note history projection mapped.
- [x] Surplus/refund_due/refund_paid records mapped.
- [x] Transaction report dataset mapped.
- [x] Excel export path mapped.
- [x] PDF export path mapped.
- [x] Dead/unproven paths listed.
- [x] First characterization test selected.
- [x] No production code patch made.

#### Residual Gaps

- Local route proof remains required.
- Local test fixture proof remains required.
- Browser refresh/hard-refresh proof remains required.
- Report/PDF/Excel parity proof remains required.
- Cash ledger impact proof remains required.
- Runtime DB proof for a concrete 0044 lifecycle case remains required.

#### Next Allowed Step

- Add only `tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`.
- Run only targeted characterization test first.
- Stop on first failure and record RED proof before any production patch.


### Session Update - 2026-06-25 Local Route Proof Recorded

#### Slice

- Active slice: Slice 0 - Structure, Source Map, and Guardrail.
- Status: local route proof recorded after owner ran `php artisan route:list`.
- Production code patch: none.

#### Files Read

- `docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md`
- owner command output: `php artisan route:list`

#### Files Changed

- `docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md`

#### FACT

- Owner ran `php artisan route:list`.
- Route output proves active admin note routes exist:
  - `admin.notes.show`
  - `admin.notes.payments.store`
  - `admin.notes.refunds.store`
  - `admin.notes.workspace.update`
  - `admin.notes.workspace.edit`
  - `admin.notes.revision-settlements.refund-due.store`
  - `admin.notes.revision-surplus-dispositions.refund-paid.store`
- Route output proves active cashier note routes exist:
  - `cashier.notes.show`
  - `cashier.notes.payments.store`
  - `cashier.notes.refunds.store`
  - `cashier.notes.workspace.update`
  - `cashier.notes.workspace.edit`
- Route output proves active report/export routes exist:
  - `admin.reports.transaction_summary.index`
  - `admin.reports.transaction_summary.export_pdf`
  - `admin.reports.transaction_summary.export_excel`
  - `admin.reports.transaction_cash_ledger.index`
  - `admin.reports.transaction_cash_ledger.export_pdf`
  - `admin.reports.transaction_cash_ledger.export_excel`

#### GAP

- No characterization test has been added yet.
- No targeted test has been run for 0044 yet.
- Browser refresh/hard-refresh behavior is still not proven.
- Report/PDF/Excel lifecycle parity is still not proven.
- Cash ledger lifecycle impact is still not proven.

#### DECISION

- Local route proof gap is now recorded.
- Slice 0 remains source-map/docs only.
- Next allowed implementation step is test-only:
  - create `tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`
  - start with `test_revision_after_partial_payment_carries_paid_amount_into_underpaid_settlement`
- Production code patch remains forbidden until RED proof exists.

#### Tests / Commands Run

```bash
php artisan route:list
```

Result:

```text
Owner output showed 172 routes and included active admin/cashier note edit, payment, refund, surplus refund due/refund paid, transaction report, PDF export, Excel export, and cash ledger routes.
```

Meaning:

- Active route map from Slice 0 is locally verified.
- `UpdateTransactionWorkspaceController` / `UpdateTransactionWorkspaceHandler` remain dead/unproven as active edit route path unless future route proof says otherwise.

#### Checklist Changes

- No new checklist checkbox added.
- Existing Slice 0 route-map checkboxes remain valid with local proof attached.

#### Residual Gaps

- First characterization test still pending.
- Test fixture proof still pending.
- Runtime DB proof still pending.
- Browser refresh proof still pending.
- Report/export parity proof still pending.

#### Next Allowed Step

- Test-only characterization for revision carry-forward settlement.
