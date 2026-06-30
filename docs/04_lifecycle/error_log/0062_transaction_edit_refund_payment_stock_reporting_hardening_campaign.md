# 0062 - Transaction Edit Refund Payment Stock Reporting Hardening Campaign

## Status

In progress for the broader edit/refund/payment/stock/reporting campaign.

Sub-slices A-N are closed with automated proof.

## Context

This campaign extends the closed `0051` manual transaction reporting QA matrix with automated hardening for the highest-risk combined state-machine:

- edit/revision;
- refund;
- payment status;
- store-stock movement;
- reporting reconciliation.

The previous `0050` through `0061` inventory/reporting closure remains valid. This slice does not reopen inventory costing semantics, source type registry ownership, deleted/orphan reporting, or PDF/Excel deleted product parity.

## Hard Boundary

- Do not change costing engine.
- Do not change HPP movement creation.
- Do not change `inventory_value_rupiah` semantics.
- Do not add migration.
- Do not write production DB.
- Do not remove payment/refund/revision history.
- Do not change refund policy.
- Do not make refund act as edit.
- Do not make edit act as refund.
- Do not change master product price behavior.
- Do not change `inventory_movements.source_type` bucket membership.

## Problem

Existing tests covered important pieces separately:

- service-only paid upward revision and delta payment;
- store-stock revision reversal/reissue;
- paid downward revision surplus settlement;
- unpaid/open refund rejection;
- edit after refund preserving historical rows;
- package component refund/pay-again matrix;
- transaction/cash/inventory/profit reporting reconciliation.
- revision duplicate-submit idempotency;
- refund duplicate-submit idempotency;
- payment duplicate-submit idempotency.
- package component refund/edit/pay-again/report proof.
- external purchase/pass-through refund/edit/report proof.
- one-click edit/refund reason default UI proof.

The initial missing proof was a combined regression where a paid or unpaid store-stock transaction crosses edit, payment, refund guard, stock movement, and reports in one scenario. The later hardening added duplicate-submit protection for both revision and primary selected-row refund paths, including real UI hidden-key coverage.

## Source Map

### Revision/Edit

- `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`
- `app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php`
- `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`
- `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php`

### Payment State

- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
- `app/Application/Payment/Services/RecordNotePaymentIdempotencyService.php`
- `app/Application/Note/Policies/NotePaidStatusPolicy.php`
- `app/Application/Note/Services/NoteOperationalStatusResolver.php`

### Refund Guard

- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundEligibilityGuard.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundIdempotencyService.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php`

### Inventory Movement

- `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/Services/ReverseIssuedInventoryByNoteService.php`
- `app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php`
- `app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php`

### Reporting

- `app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php`
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/StoreStockCogsPerDayQuery.php`
- `app/Application/Reporting/UseCases/GetTransactionReportDatasetHandler.php`
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php`
- `app/Application/Reporting/UseCases/GetInventoryMovementSummaryHandler.php`
- `app/Application/Reporting/UseCases/GetOperationalProfitSummaryHandler.php`
- `app/Application/Reporting/UseCases/GetDashboardOperationalPerformanceDatasetHandler.php`

## Sub-slices Completed

### 0062-A - Paid Store-stock Revision Upward

Test:

- `test_paid_store_stock_revision_upward_preserves_payment_creates_outstanding_delta_and_reconciles_reports`

Coverage:

- original store-stock note paid in full;
- original payment row preserved;
- revision increases store-stock quantity and total;
- outstanding delta appears;
- cash ledger does not auto-increase before additional payment;
- old `stock_out` reversed once with `transaction_workspace_updated`;
- replacement `stock_out` issued once;
- no `work_item_store_stock_line_reversal` refund movement created by edit;
- delta payment settles note;
- transaction summary, cash ledger, inventory movement, operational profit, and dashboard reconcile.

### 0062-B - Paid Store-stock Revision Downward

Test:

- `test_paid_store_stock_revision_downward_preserves_payment_creates_surplus_policy_and_reconciles_reports`

Coverage:

- original gross payment preserved;
- current component allocations capped to revised total;
- revision settlement records `overpaid_pending` surplus;
- default revision workflow creates `refund_due` for the surplus;
- default revision workflow records immediate `surplus_refund_paid` through the existing surplus refund payment ledger;
- no pending refund-due or refund-paid action remains after the default auto-settlement;
- no legacy `customer_refunds` row is created for revision surplus;
- edit reversal and replacement issue are distinct from refund reversal;
- transaction summary and cash ledger use current capped allocation;
- transaction summary exposes `refund_due_rupiah`, `surplus_refund_paid_rupiah`, and zero remaining refund due;
- cash ledger reports the surplus refund paid as a cash outflow;
- operational profit and dashboard exclude the returned surplus from profit.

### 0062-C - Unpaid Store-stock Refund Attempt And Edit

Test:

- `test_unpaid_store_stock_note_rejects_refund_but_allows_revision_without_cash_or_inventory_refund_side_effect`

Coverage:

- unpaid/open store-stock note rejects forged refund attempt through HTTP route;
- no customer refund row;
- no refund component allocation;
- no cash ledger outflow;
- no `work_item_store_stock_line_reversal`;
- no refund/cancel mutation event;
- edit/revision remains allowed;
- revision stock reversal/reissue occurs through edit path only;
- transaction summary, cash ledger, inventory movement, and operational profit stay consistent.

### 0062-D - Refunded Store-stock Revision History And Reporting

Test:

- `test_refunded_store_stock_note_revision_preserves_refund_history_and_reconciles_reports_without_double_reversal`

Coverage:

- paid store-stock note is refunded for the store-stock component;
- original customer payment remains preserved;
- customer refund and refund component allocation remain preserved after admin correction revision;
- refund inventory reversal remains single;
- edit after refund does not create duplicate `transaction_workspace_updated` reversal for a line already reversed by refund;
- replacement stock-out is issued for the revised current line;
- note projection keeps current outstanding state while preserving refund totals;
- transaction summary, cash ledger, inventory movement, and operational profit reconcile the payment/refund/edit timeline.

### 0062-E - Store-stock Master Price Change Historical Stability

Test:

- `test_store_stock_transaction_keeps_historical_line_price_after_master_product_price_change`

Coverage:

- changing `products.harga_jual` after transaction does not rewrite existing note total;
- existing store-stock line total remains the transaction snapshot amount;
- transaction summary and operational profit continue reading historical transaction truth;
- attempted revision with old lower snapshot price is rejected by current minimum selling price guard;
- rejected revision does not create extra note revision;
- rejected revision does not create inventory reversal/reissue side effects;
- reports remain tied to the original transaction/payment/COGS values.

### 0062-F - Paid Store-stock Downward Revision Duplicate Submit

Test:

- `test_paid_store_stock_revision_downward_duplicate_submit_replays_without_duplicate_revision_refund_or_stock`

Coverage:

- same admin downward revision request is submitted twice with the same `idempotency_key`;
- second submit replays/no-ops instead of creating revision `r003`;
- only one new revision, one settlement, one refund_due, and one surplus_refund_paid row are created;
- only one edit reversal is created for the old store-stock line;
- replacement stock-out is issued once;
- idempotency record is persisted with operation `create_note_revision`.

### 0062-G - Edit Workspace UI Sends Revision Idempotency Key

Test:

- `test_edit_workspace_page_renders_revision_idempotency_key_for_normal_submit`

Coverage:

- edit workspace page renders a hidden `idempotency_key`;
- key is generated server-side for normal edit submit;
- old input key is preserved on validation retry;
- create workspace hidden idempotency key behavior remains unchanged.

### 0062-H - Primary Selected-row Refund Duplicate Submit

Test:

- `test_duplicate_refund_submit_with_same_idempotency_key_replays_without_duplicate_cash_or_inventory`

Coverage:

- same cashier selected-row refund request is submitted twice with the same `idempotency_key`;
- second submit replays/no-ops before stale refund eligibility checks can reject an already-canceled row;
- only one `customer_refunds` row is created;
- only one `refund_component_allocations` row is created;
- only one `work_item_store_stock_line_reversal` inventory movement is created;
- idempotency record is persisted with operation `record_selected_rows_refund`;
- duplicate submit does not create duplicate cash refund, duplicate component allocation, or duplicate stock reversal.

### 0062-I - Refund Modal UI Sends Refund Idempotency Key

Test:

- `test_refund_modal_renders_idempotency_key_for_normal_submit`

Coverage:

- note detail refund modal renders a hidden `idempotency_key`;
- key is generated server-side for normal refund submit;
- old input key is preserved on validation retry;
- legacy/alternate refund form partial also carries the same hidden key contract;
- normal UI refund submits activate the backend duplicate-submit guard.

### 0062-J - Primary Customer Payment Duplicate Submit

Test:

- `test_duplicate_payment_submit_with_same_idempotency_key_replays_without_duplicate_cash_in_or_allocation`

Coverage:

- same cashier selected-row payment request is submitted twice with the same `idempotency_key`;
- second submit replays/no-ops before stale outstanding-row checks reject an already-paid row;
- only one `customer_payments` row is created;
- only one `customer_payment_cash_details` row is created;
- only one `payment_component_allocations` row is created;
- idempotency record is persisted with operation `record_note_payment`;
- duplicate submit does not create duplicate cash-in, cash detail, or component allocation.

### 0062-K - Payment Modal UI Sends Payment Idempotency Key

Test:

- `test_detail_payment_modal_uses_simple_create_like_payment_flow`

Coverage:

- note detail payment modal renders a hidden `idempotency_key`;
- key is generated server-side for normal payment submit;
- old input key is preserved on validation retry;
- normal UI payment submits activate the backend duplicate-submit guard.

### 0062-L - Package Component Refund/Edit/Pay-again/Report Proof

Tests:

- `ManualFullRefundEditLifecycleMismatchFeatureTest`
- `ServicePackageComponentRefundPayAgainMatrixTest`
- `ServicePackageProfitBreakdownQueryTest`
- `ServicePackageProfitBreakdownHttpWorkflowFeatureTest`
- `PackageAutoSplitRevisionReportImpactFeatureTest`

Coverage:

- historical refunded package components do not appear as current collectible billing rows after edit/replacement;
- note history projection does not turn refunded package components into new debt;
- refund action remains available for the current closed revision after historical package refund;
- refunded and inventory-reversed store-stock package components are not offered as payable rows;
- 48 service package refund/pay-again matrix scenarios reject silent pay-again without deliberate new stock-out;
- package profit breakdown uses historical package, stock, COGS, and component refund sources;
- package report UI/Excel remains stable after late payment, product price change, and admin revision;
- transaction report and exports read the current package revision total after downward revision.

### 0062-M - External Purchase Pass-through Refund/Edit/Report Proof

Tests:

- `CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest`
- `ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest`
- `CorrectPaidServiceWithExternalPurchaseServiceFeeOnlyFeatureTest`
- `UpdateServiceWithExternalPurchaseServiceFeeOnlyWriterFeatureTest`
- `RecordCustomerRefundFeatureTest`
- `RecordSelectedRowsCustomerRefundFeatureTest`
- `GetOperationalProfitSummaryFeatureTest`
- `TransactionReportingReconciliationFeatureTest`
- `OperationalProfitSummaryHardeningFeatureTest`

Coverage:

- service with external purchase can be created without inventory movement;
- external purchase `package_auto_split` remains blocked until an explicit contract exists;
- selected-row refund for closed external purchase is default blocked;
- blocked external refund does not create customer refund, refund component allocation, or inventory movement;
- generic/selected-row refund policy does not refund `service_external_purchase_part` by default;
- service-fee-only correction keeps external purchase line intact;
- external purchase cost remains a case/pass-through cost in operational profit reporting;
- transaction cash, refund, external purchase cost, inventory, and profit reconciliation remain aligned in the golden master.

### 0062-N - One-click Edit/Refund Reason Default UI

Tests:

- `test_cashier_can_open_edit_workspace_page_for_unpaid_note_with_payment_modal_but_without_refund_action`
- `test_refund_modal_renders_idempotency_key_for_normal_submit`

Coverage:

- edit workspace renders an editable default reason: `Revisi nota via workspace`;
- edit workspace reason textarea is marked required in the UI;
- refund modal renders an editable default reason: `Pengembalian dana / pembatalan rincian`;
- refund modal still sends the existing idempotency key;
- direct backend refund request still rejects blank reason, so the audit policy remains strict.

## Failing Test Proof

Initial 0062-A run failed before the report COGS production patch:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php
```

Observed failure:

```text
Failed asserting that 200000 is identical to 120000.

tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php:206
```

Meaning:

- inventory movement and transaction/payment assertions already passed;
- operational profit COGS counted both original and replacement store-stock `stock_out`;
- edit reversal `transaction_workspace_updated` was not offsetting store-stock COGS.

## Root Cause

`ProductCostMetricQuery::storeStockCogs()` subtracted refund reversals:

- `source_type = work_item_store_stock_line_reversal`

but did not subtract edit/revision reversal:

- `source_type = transaction_workspace_updated`

Dashboard operational performance had the same drift in `StoreStockCogsPerDayQuery`.

The inventory engine was not changed. The bug was report COGS interpretation of existing edit reversal movement.

Later 0062-B was tightened after owner policy clarification:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php --filter=paid_store_stock_revision_downward
```

Initial RED result:

```text
Failed asserting that two strings are not identical.

tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php:321
```

Meaning:

- paid downward revision created `overpaid_pending` settlement;
- but no `note_revision_surplus_dispositions` refund-due row was written;
- therefore the surplus existed only as a pending action, not as committed customer-money obligation.

After auto-settlement was added, the same test exposed a dashboard mismatch:

```text
Failed asserting that 270000 is identical to 170000.

tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php:440
```

Meaning:

- transaction report, cash ledger, and operational profit summary already read surplus refund paid correctly;
- dashboard refund aggregation still read only legacy `customer_refunds`;
- dashboard profit could still include returned surplus as profit.

0062-F duplicate-submit RED:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php --filter=duplicate_submit
```

Initial RED result:

```text
Failed asserting that 3 is identical to 2.

tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php:916
```

Meaning:

- repeating the same edit request created revision `r003`;
- duplicate submit could duplicate stock correction, settlement, refund_due, and refund_paid side effects;
- create workspace already had idempotency, but edit/revision submit did not.

0062-G edit UI RED:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php --filter=renders_revision_idempotency_key
```

Initial RED result:

```text
Expected response to contain: name="idempotency_key"
```

Meaning:

- backend revision idempotency was available only if a key was sent;
- normal edit UI did not send a key, so double-click protection was not active from the real form.

0062-H primary refund duplicate-submit RED:

```bash
php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=duplicate_refund_submit
```

Initial RED result:

```text
Session has unexpected errors: Line yang sudah batal/refund tidak boleh dipilih lagi.
```

Meaning:

- first submit correctly created refund/cancel/reversal state;
- second same-key submit did not replay the completed operation;
- the request fell through into normal refund eligibility and failed against the already-canceled row;
- the stale error avoided duplicate refund rows in this fixture, but did not provide idempotent UX or a stored duplicate-submit contract.

0062-I refund modal UI RED:

```bash
php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=refund_modal_renders_idempotency_key
```

Initial RED result:

```text
Expected response to contain: name="idempotency_key"
```

Meaning:

- backend refund idempotency would only work if a key was sent;
- normal refund modal UI did not send a key, so real double-click/back-button resubmit paths were not protected.

0062-J primary payment duplicate-submit RED:

```bash
php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php --filter=duplicate_payment_submit
```

Initial RED result:

```text
Hanya billing row outstanding yang boleh dipilih untuk pembayaran.
```

Meaning:

- first submit correctly created payment/allocation state;
- second same-key submit did not replay the completed operation;
- the request fell through into normal selected-row payment validation and failed against the already-paid row;
- the stale error avoided duplicate payment rows in this fixture, but did not provide idempotent UX or a stored duplicate-submit contract.

0062-K payment modal UI RED:

```bash
php artisan test tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php --filter=detail_payment_modal_uses_simple_create_like_payment_flow
```

Initial RED result:

```text
Expected response to contain: name="idempotency_key"
```

Meaning:

- backend payment idempotency would only work if a key was sent;
- normal payment modal UI did not send a key, so real double-click/back-button resubmit paths were not protected.

## Patch Summary

Production code changed:

- `app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php`
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/StoreStockCogsPerDayQuery.php`
- `app/Application/Note/Services/AutoSettleNoteRevisionSurplusRefund.php`
- `app/Application/Note/Services/CreateNoteRevisionIdempotencyService.php`
- `app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php`
- `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`
- `app/Providers/InfrastructureServiceProvider.php`
- `app/Adapters/In/Http/Requests/Note/StoreNoteRevisionRequest.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/EditTransactionWorkspacePageController.php`
- `resources/views/cashier/notes/workspace/create.blade.php`
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/RefundPerDayQuery.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundIdempotencyService.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php`
- `app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php`
- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/NoteDetailPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `resources/views/cashier/notes/partials/refund-form.blade.php`
- `app/Application/Payment/Services/RecordNotePaymentIdempotencyService.php`
- `app/Adapters/In/Http/Requests/Note/RecordNotePaymentRequest.php`
- `app/Adapters/In/Http/Controllers/Note/RecordNotePaymentController.php`
- `resources/views/cashier/notes/partials/payment-modal.blade.php`

Patch behavior:

- store-stock COGS still counts `work_item_store_stock_line` stock_out;
- refund reversal still offsets COGS;
- edit/revision reversal `transaction_workspace_updated` now also offsets COGS;
- cross-period negative COGS behavior remains valid because existing tests still pass;
- paid downward revision surplus now creates refund-due and immediate surplus-refund-paid records inside the revision transaction;
- surplus auto-settlement writes canonical `audit_events` and keeps FK-backed audit links;
- cash ledger and dashboard refund aggregation both include `note_revision_surplus_refund_payments`;
- revision submit supports optional `idempotency_key` with operation `create_note_revision`;
- repeated same-key same-payload revision requests return the stored success result without mutating note, stock, settlement, refund_due, or refund_paid again;
- edit workspace page now emits a hidden idempotency key so normal UI submits activate the backend guard;
- selected-row refund submit supports optional `idempotency_key` with operation `record_selected_rows_refund`;
- repeated same-key same-payload selected-row refund requests replay success before stale canceled/refunded row checks;
- normal refund modal submits now emit a hidden idempotency key so backend duplicate-submit protection is active in the real UI path;
- customer payment submit supports optional `idempotency_key` with operation `record_note_payment`;
- repeated same-key same-payload payment requests replay success before stale already-paid row checks;
- normal payment modal submits now emit a hidden idempotency key so backend duplicate-submit protection is active in the real UI path;
- no inventory movement source type was renamed or re-bucketed.

## Test Added

File:

- `tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php`
- `tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
- `tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php`
- `tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php`

Tests:

- `test_paid_store_stock_revision_upward_preserves_payment_creates_outstanding_delta_and_reconciles_reports`
- `test_paid_store_stock_revision_downward_preserves_payment_creates_surplus_policy_and_reconciles_reports`
- `test_unpaid_store_stock_note_rejects_refund_but_allows_revision_without_cash_or_inventory_refund_side_effect`
- `test_refunded_store_stock_note_revision_preserves_refund_history_and_reconciles_reports_without_double_reversal`
- `test_store_stock_transaction_keeps_historical_line_price_after_master_product_price_change`
- `test_paid_store_stock_revision_downward_duplicate_submit_replays_without_duplicate_revision_refund_or_stock`
- `test_edit_workspace_page_renders_revision_idempotency_key_for_normal_submit`
- `test_duplicate_refund_submit_with_same_idempotency_key_replays_without_duplicate_cash_or_inventory`
- `test_refund_modal_renders_idempotency_key_for_normal_submit`
- `test_duplicate_payment_submit_with_same_idempotency_key_replays_without_duplicate_cash_in_or_allocation`
- `test_detail_payment_modal_uses_simple_create_like_payment_flow`

## Regression Proof

Focused campaign proof:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php
```

Result:

```text
PASS
Tests: 6 passed
```

Focused 0062-B proof after owner policy clarification:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php --filter=paid_store_stock_revision_downward
```

Result:

```text
PASS
Tests: 1 passed (62 assertions)
```

Focused 0062-F proof:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php --filter=duplicate_submit
```

Result:

```text
PASS
Tests: 1 passed (16 assertions)
```

Focused 0062-G proof:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php --filter=renders_revision_idempotency_key
```

Result:

```text
PASS
Tests: 1 passed (6 assertions)
```

Focused 0062-H proof:

```bash
php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=duplicate_refund_submit
```

Result:

```text
PASS
Tests: 1 passed (11 assertions)
```

Focused 0062-I proof:

```bash
php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=refund_modal_renders_idempotency_key
```

Result:

```text
PASS
Tests: 1 passed (4 assertions)
```

Primary refund idempotency regression proof:

```bash
php artisan test \
  tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php \
  tests/Feature/Payment/RecordCustomerRefundFeatureTest.php \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  tests/Feature/Reporting/TransactionReportingReconciliationFeatureTest.php \
  tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php \
  tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
```

Result:

```text
PASS
Tests: 40 passed (489 assertions)
```

Focused 0062-J proof:

```bash
php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php --filter=duplicate_payment_submit
```

Result:

```text
PASS
Tests: 1 passed (11 assertions)
```

Focused 0062-K proof:

```bash
php artisan test tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php --filter=detail_payment_modal_uses_simple_create_like_payment_flow
```

Result:

```text
PASS
Tests: 1 passed (18 assertions)
```

Payment/edit/refund targeted regression proof:

```bash
php artisan test \
  tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php \
  tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php \
  tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsNotePaymentFeatureTest.php \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
  tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
```

Result:

```text
PASS
Tests: 33 passed (479 assertions)
```

Package component refund/edit/pay-again/report proof:

```bash
php artisan test \
  tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php \
  tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php \
  tests/Feature/Reporting/ServicePackageProfitBreakdownQueryTest.php \
  tests/Feature/Reporting/ServicePackageProfitBreakdownHttpWorkflowFeatureTest.php \
  tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php
```

Result:

```text
PASS
Tests: 59 passed (457 assertions)
```

External purchase pass-through refund/edit/report proof:

```bash
php artisan test \
  tests/Feature/Note/CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php \
  tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php \
  tests/Feature/Note/CorrectPaidServiceWithExternalPurchaseServiceFeeOnlyFeatureTest.php \
  tests/Feature/Note/UpdateServiceWithExternalPurchaseServiceFeeOnlyWriterFeatureTest.php \
  tests/Feature/Payment/RecordCustomerRefundFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php \
  tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php \
  tests/Feature/Reporting/TransactionReportingReconciliationFeatureTest.php \
  tests/Feature/Reporting/OperationalProfitSummaryHardeningFeatureTest.php
```

Result:

```text
PASS
Tests: 23 passed (145 assertions)
```

Focused 0062-N proof:

```bash
php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=refund_modal_renders_idempotency_key
php artisan test tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php --filter=can_open_edit_workspace_page
```

Result:

```text
PASS
Tests: 1 passed (5 assertions)
PASS
Tests: 1 passed (14 assertions)
```

Edit/refund/payment UI and backend reason regression proof:

```bash
php artisan test \
  tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php \
  tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php \
  tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php
```

Result:

```text
PASS
Tests: 27 passed (427 assertions)
```

Edit/create UI idempotency regression proof:

```bash
php artisan test \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php \
  tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php
```

Result:

```text
PASS
Tests: 15 passed (352 assertions)
```

Revision idempotency baseline proof:

```bash
php artisan test \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php \
  tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php \
  tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php \
  tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php \
  tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php \
  tests/Feature/Reporting/GetDashboardOperationalPerformanceDatasetFeatureTest.php \
  tests/Feature/Reporting/TransactionReportingReconciliationFeatureTest.php \
  tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php \
  tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
```

Result:

```text
PASS
Tests: 47 passed (548 assertions)
```

Targeted domain baseline proof:

```bash
php artisan test \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php \
  tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php \
  tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php \
  tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php \
  tests/Feature/Reporting/TransactionReportingReconciliationFeatureTest.php \
  tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php \
  tests/Feature/Reporting/GetDashboardOperationalPerformanceDatasetFeatureTest.php \
  tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php \
  tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php \
  tests/Feature/Note/NoteDetailSurplusDispositionPayloadFeatureTest.php
```

Result:

```text
PASS
Tests: 34 passed (457 assertions)
```

## Financial And Stock Invariants Locked

- Refund is not edit.
- Edit is not refund.
- Paid edit upward creates outstanding delta; it does not erase old payment.
- Paid edit downward preserves old payment, creates refund-due, records default surplus refund paid, and does not treat surplus as profit.
- Duplicate paid downward edit submit does not create duplicate revision, stock correction, settlement, refund_due, or refund_paid rows.
- Normal edit form submits carry `idempotency_key`, so the backend duplicate-submit guard is active for real UI use.
- Duplicate selected-row refund submit does not create duplicate cash refund, component allocation, or stock reversal.
- Normal refund form submits carry `idempotency_key`, so the backend duplicate-submit guard is active for real UI use.
- Duplicate selected-row payment submit does not create duplicate cash-in, cash detail, or component allocation.
- Normal payment form submits carry `idempotency_key`, so the backend duplicate-submit guard is active for real UI use.
- Refunded package components remain historical/shadow and do not become current collectible debt after edit/replacement.
- Service package component refunds cannot be silently paid again without deliberate new stock-out.
- Service package profit breakdown remains tied to historical package/stock/refund sources after payment, price change, and revision.
- External purchase refunds remain default-blocked without a manual exception path.
- Blocked external purchase refunds do not mutate customer refund, refund allocation, or inventory movement tables.
- External purchase cost remains pass-through/case cost in reporting and does not become store-stock inventory movement.
- Edit/refund UI now provides editable default reasons while preserving strict backend reason validation.
- Unpaid note refund attempt is rejected.
- Refunded store-stock admin correction preserves payment/refund history.
- Master product price change does not rewrite historical transaction line value.
- Revision below current master minimum price is rejected without financial/stock side effects.
- Store-stock edit uses revision reversal/reissue, not refund reversal.
- Refund reversal remains `work_item_store_stock_line_reversal`.
- Edit reversal remains `transaction_workspace_updated`.
- Operational profit and dashboard COGS use net replacement COGS after edit reversal.
- Dashboard refund/profit reads surplus refund paid cash-out, not only legacy customer refunds.
- Transaction summary/cash ledger current report uses capped current allocations.
- Gross customer payment history remains preserved in `customer_payments`.

## Remaining Backlog

No remaining backlog for this campaign.

## Final Status

Sub-slices A-N are closed with automated proof.
