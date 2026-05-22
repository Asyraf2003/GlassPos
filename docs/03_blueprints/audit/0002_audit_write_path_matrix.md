# Audit Write Path Matrix

## Status

Draft readiness matrix.

This document is not implementation proof.

This document does not authorize audit outbox implementation.

This document does not authorize switching `AuditEventWriterPort` binding to an outbox writer.

## Source Snapshot

Local proof provided by owner:

- repo root: `/home/asyraf/Code/laravel/bengkel2/app`
- branch: `main...origin/main`
- HEAD: `b5ed69a6`

## Purpose

Map the current HyperPOS audit write paths before changing audit runtime behavior.

The immediate goal is to prevent unsafe migration from legacy `AuditLogPort` writes to canonical `AuditEventWriterPort` or future audit outbox without knowing which mutation flows are affected.

## Binding Facts

Current binding from source inventory:

- `AuditEventWriterPort` is bound to `DatabaseAuditEventWriterAdapter`.
- `AuditLogPort` is bound to `DatabaseAuditLogAdapter`.

Current runtime implication:

- canonical audit events are written directly to `audit_events` and `audit_event_snapshots`;
- legacy audit events are written to `audit_logs`;
- no runtime `audit_outbox` file was found in `app`, `database`, or `tests` during local file check.

## Canonical Audit Path

| Area | File | Event / factory | Current behavior | Classification | Notes |
|---|---|---|---|---|---|
| Note | `app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php` | `CreateNoteRevisionSurplusRefundDueAuditEventFactory` | Calls `AuditEventWriterPort::write()` inside transaction before disposition writer create and commit | canonical | Existing canonical audit path. Finance-sensitive. Not a first broad outbox switch target without focused proof. |
| Note | `app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php` | `RecordNoteRevisionSurplusRefundPaymentAuditEventFactory` | Calls `AuditEventWriterPort::write()` inside transaction before payment writer create and commit | canonical | Existing canonical audit path with idempotency check. Finance-sensitive. Requires focused proof before any binding change. |
| Test | `tests/Feature/AuditLog/DatabaseAuditEventWriterAdapterTest.php` | `new AuditEventWrite(...)` | Tests canonical adapter | canonical_test | Adapter test only. Not a mutation flow. |

## Legacy Audit Path

| Area | File | Event / call | Current behavior | Classification | Outbox pilot eligibility |
|---|---|---|---|---|---|
| Payment | `app/Application/Payment/UseCases/AllocateCustomerPaymentHandler.php` | `payment_allocated` | Writes legacy audit after payment allocation writer create, before transaction commit | legacy_audit | no, transaction-heavy / allocation-sensitive |
| Payment | `app/Application/Payment/Services/RecordCustomerRefundTransaction.php` | `customer_refund_recorded` | Writes legacy audit after refund operation, inventory reversal, and refund lifecycle, before projection sync and commit | legacy_audit | no, refund/payment/stock-sensitive |
| Payment | `app/Application/Payment/Services/RecordSelectedRowsRefundPlanAuditRecorder.php` | `selected_rows_refund_plan_recorded` | Writes selected-row refund plan audit through legacy audit port | legacy_audit | no, refund-sensitive |
| Procurement | `app/Application/Procurement/UseCases/ReceiveSupplierInvoiceHandler.php` | `supplier_receipt_created` | Writes legacy audit after receipt, inventory movements, and inventory projection, before invoice projection sync and commit | legacy_audit | no, procurement/stock/finance-sensitive |
| Procurement | `app/Application/Procurement/Services/AttachSupplierPaymentProofTransaction.php` | `supplier_payment_proof_attached` | Writes supplier payment proof audit through legacy audit port | legacy_audit | maybe later, requires upload/storage rollback proof |
| Procurement | `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofRecorder.php` | `supplier_invoice_mobile_payment_proof_uploaded` | Writes mobile payment proof audit through legacy audit port | legacy_audit | maybe later, requires API/mobile/storage proof |
| Procurement | `app/Application/Procurement/Services/RecordSupplierPaymentAuditLog.php` | `supplier_payment_recorded` | Writes supplier payment audit through legacy audit port | legacy_audit | no, finance-sensitive |
| Procurement | `app/Application/Procurement/Services/SupplierPaymentReversalRecorder.php` | `supplier_payment_reversed` | Writes supplier payment reversal audit through legacy audit port | legacy_audit | no, reversal-sensitive |
| Procurement | `app/Application/Procurement/Services/SupplierReceiptReversalRecorder.php` | `supplier_receipt_reversed` | Writes supplier receipt reversal audit through legacy audit port | legacy_audit | no, stock/procurement reversal-sensitive |
| Inventory | `app/Application/Inventory/UseCases/RecordStockAdjustmentHandler.php` | `stock_adjustment_recorded` | Writes legacy audit inside transaction after inventory operation, before commit | legacy_audit | maybe later, but stock mutation requires inventory proof |
| Inventory | `app/Application/Inventory/UseCases/ReverseStockAdjustmentHandler.php` | `stock_adjustment_reversed` | Writes legacy audit for stock adjustment reversal | legacy_audit | no, reversal-sensitive |
| Inventory | `app/Application/Inventory/UseCases/IssueInventoryHandler.php` | `inventory_issued` | Writes legacy audit for inventory issue | legacy_audit | maybe later, requires stock proof |
| Inventory | `app/Application/Inventory/UseCases/RebuildInventoryProjectionHandler.php` | `inventory_projection_rebuilt` | Writes legacy audit for projection rebuild | legacy_audit | no, projection rebuild should not be first outbox pilot |
| Inventory | `app/Application/Inventory/UseCases/RebuildInventoryCostingProjectionHandler.php` | `inventory_costing_rebuilt` | Writes legacy audit for costing projection rebuild | legacy_audit | no, costing/projection-sensitive |
| Expense | `app/Application/Expense/UseCases/UpdateExpenseCategoryHandler.php` | `expense_category_updated` | Writes canonical audit through `AuditEventWriterPort` with metadata plus before/after snapshots | canonical_pilot | done for pilot `1d608443`; outbox still not implemented |
| Expense | `app/Application/Expense/UseCases/ActivateExpenseCategoryHandler.php` | `expense_category_activated` | Writes canonical audit through `AuditEventWriterPort` with metadata plus before/after snapshots | canonical_pilot | done for pilot `b5ed69a6`; outbox still not implemented |
| Expense | `app/Application/Expense/UseCases/DeactivateExpenseCategoryHandler.php` | `expense_category_deactivated` | Writes legacy audit for expense category deactivation | legacy_audit | possible candidate after source/test proof |
| Expense | `app/Application/Expense/UseCases/SoftDeleteOperationalExpenseHandler.php` | `operational_expense_soft_deleted` | Writes legacy audit for operational expense soft delete | legacy_audit | maybe later, expense mutation requires proof |
| IdentityAccess | `app/Application/IdentityAccess/Policies/TransactionEntryPolicy.php` | `admin_transaction_capability_used` | Writes legacy audit when admin transaction capability is used | legacy_audit | possible candidate only after access ADR/policy proof |
| IdentityAccess | `app/Application/IdentityAccess/Policies/CashierAreaAccessPolicy.php` | `admin_cashier_area_access_used` | Writes legacy audit for cashier area access use | legacy_audit | possible candidate only after access ADR/policy proof |
| IdentityAccess | `app/Application/IdentityAccess/UseCases/EnableAdminTransactionCapabilityHandler.php` | `admin_transaction_capability_enabled` | Writes legacy audit for capability enable | legacy_audit | possible candidate only after access ADR/policy proof |
| IdentityAccess | `app/Application/IdentityAccess/UseCases/DisableAdminTransactionCapabilityHandler.php` | `admin_transaction_capability_disabled` | Writes legacy audit for capability disable | legacy_audit | possible candidate only after access ADR/policy proof |
| EmployeeFinance | `app/Application/EmployeeFinance/UseCases/PayrollBatchRowProcessor.php` | `payroll_disbursement_recorded` | Writes legacy audit for payroll disbursement | legacy_audit | no, finance-sensitive |
| EmployeeFinance | `app/Application/EmployeeFinance/UseCases/ReversePayrollDisbursementHandler.php` | `payroll_disbursement_reversed` | Writes legacy audit for payroll reversal | legacy_audit | no, reversal/finance-sensitive |
| EmployeeFinance | `app/Application/EmployeeFinance/UseCases/PayEmployeeDebtHandler.php` | `employee_debt_payment_recorded` | Writes legacy audit for employee debt payment | legacy_audit | no, finance-sensitive |
| EmployeeFinance | `app/Application/EmployeeFinance/UseCases/ReverseEmployeeDebtPaymentHandler.php` | `employee_debt_payment_reversed` | Writes legacy audit for employee debt reversal | legacy_audit | no, reversal/finance-sensitive |
| EmployeeFinance | `app/Application/EmployeeFinance/UseCases/AdjustEmployeeDebtPrincipalHandler.php` | `employee_debt_principal_adjusted` | Writes legacy audit for employee debt principal adjustment | legacy_audit | no, finance-sensitive |
| Note | `app/Application/Note/Services/ReopenClosedNoteTransaction.php` | `note_reopened` | Writes timeline and legacy audit | legacy_audit_plus_timeline | no, note lifecycle-sensitive |
| Note | `app/Application/Note/Services/FinalizePaidNoteCorrection.php` | dynamic `$mutationType` | Writes timeline and legacy audit | legacy_audit_plus_timeline | no, paid correction-sensitive |
| Note | `app/Application/Note/Services/CorrectPaidServiceOnlyWorkItemFinalizer.php` | dynamic or formatted audit payload | Writes timeline and legacy audit | legacy_audit_plus_timeline | no, paid correction-sensitive |
| Note | `app/Application/Note/UseCases/CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler.php` | `paid_service_with_store_stock_part_service_fee_only_corrected` | Writes timeline and legacy audit | legacy_audit_plus_timeline | no, paid correction/stock-sensitive |
| Note | `app/Application/Note/UseCases/UpdateWorkItemStatusHandler.php` | `work_item_status_updated` | Writes legacy audit | legacy_audit | maybe later, requires note lifecycle proof |
| Note | `app/Application/Note/UseCases/AddWorkItemHandler.php` | `work_item_added` | Writes legacy audit | legacy_audit | maybe later, requires note lifecycle proof |
| Note | `app/Application/Note/UseCases/CorrectPaidWorkItemStatusHandler.php` | `paid_work_item_status_corrected` | Writes legacy audit | legacy_audit | no, paid correction-sensitive |
| Note | `app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php` | dynamic record call | Writes payment recorder and legacy audit | legacy_audit_mixed | no, transaction workspace-sensitive |
| Note | `app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php` | dynamic record call | Writes payment recorder and legacy audit | legacy_audit_mixed | no, transaction workspace-sensitive |
| Note | `app/Application/Note/UseCases/CreateNoteRevisionCommitter.php` | dynamic record call | Writes legacy audit | legacy_audit | no, revision-sensitive |
| Note | `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php` | dynamic record call | Writes legacy audit | legacy_audit | no, inline payment-sensitive |

## Non-Audit Or Needs-Separation Calls

These calls appeared in broad `record()` / `write()` grep but must not be classified as audit without source proof:

| Pattern | Reason |
|---|---|
| `timeline->record(...)` | Timeline write, not `AuditLogPort` by itself |
| `reversals->record(...)` | Business reversal writer, not audit by itself |
| `reversalWriter->record(...)` | Business reversal writer, not audit by itself |
| `adjustmentWriter->record(...)` | Business adjustment writer, not audit by itself |
| `payments->record(...)` | Payment recorder, not audit by itself |
| Reporting Excel workbook `->write(...)` calls | Export writer calls, not `AuditEventWriterPort` |

## First-Pilot Candidate Notes

Do not pick a transaction-heavy first pilot.

Explicitly excluded for first outbox pilot:

- customer payment allocation
- customer refund
- selected-row refund
- transaction workspace create/update
- paid correction
- note revision commit
- inventory stock mutation/reversal
- supplier receipt/payment/reversal
- employee finance payment/reversal/payroll
- projection rebuilds

Possible later pilot candidates after source and test proof:

- expense category update / activate / deactivate
- identity access capability enable / disable
- identity access capability used audit

These are only candidates, not decisions.

## Pilot Progress

| Pilot | Commit | Proof | Status |
|---|---|---|---|
| Update expense category canonical audit | `1d608443` | `php -l` for handler/test; `php artisan test tests/Feature/Expense/UpdateExpenseCategoryFeatureTest.php tests/Feature/Expense/UpdateExpenseCategoryHttpFeatureTest.php tests/Feature/Expense/ActivateExpenseCategoryFeatureTest.php tests/Feature/Expense/DeactivateExpenseCategoryFeatureTest.php` passed with 6 tests and 35 assertions | completed |
| Activate expense category canonical audit | `b5ed69a6` | `php -l` for handler/test; `php artisan test tests/Feature/Expense/ActivateExpenseCategoryFeatureTest.php tests/Feature/Expense/ActivateExpenseCategoryHttpFeatureTest.php tests/Feature/Expense/UpdateExpenseCategoryFeatureTest.php tests/Feature/Expense/DeactivateExpenseCategoryFeatureTest.php` passed with 5 tests and 35 assertions | completed |

## Required Proof Before Any Outbox Implementation

Before implementing `audit_outbox`, provide:

1. owner approval of first pilot flow;
2. source inspection for the selected pilot;
3. red/green or characterization test for existing audit behavior;
4. proof of current legacy audit payload;
5. migration design for `audit_outbox`;
6. adapter test for outbox writer;
7. processor test for canonical materialization;
8. retry/failure behavior proof;
9. duplicate processor run proof;
10. proof that canonical `audit_events` and `audit_event_snapshots` are materialized correctly.

## Stop Conditions

Stop before implementation if:

- selected pilot touches payment/refund/allocation/transaction workspace;
- selected pilot touches stock mutation/reversal;
- selected pilot touches payroll/debt/payment finance flows;
- selected pilot requires public contract change;
- selected pilot has no characterization test path;
- source inspection shows audit payload lacks actor/reason/source facts needed for canonical audit;
- owner decision is missing.

## Next Active Step

Read source and tests for one candidate group before choosing a pilot.

Recommended candidate group for inspection:

- expense category update / activate / deactivate

Reason:

- non-transaction category maintenance is likely lower risk than payment, refund, stock, procurement, or employee finance;
- source snippets show before/after payload for update;
- still requires source and test proof before decision.
