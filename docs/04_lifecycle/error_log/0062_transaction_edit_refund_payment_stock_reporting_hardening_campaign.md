# 0062 - Transaction Edit Refund Payment Stock Reporting Hardening Campaign

## Status

Resolved for sub-slices A-E.

No remaining campaign backlog.

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

The missing proof was a combined regression where a paid or unpaid store-stock transaction crosses edit, payment, refund guard, stock movement, and reports in one scenario.

## Source Map

### Revision/Edit

- `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`
- `app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php`
- `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`
- `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php`

### Payment State

- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
- `app/Application/Note/Policies/NotePaidStatusPolicy.php`
- `app/Application/Note/Services/NoteOperationalStatusResolver.php`

### Refund Guard

- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundEligibilityGuard.php`

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
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/RefundPerDayQuery.php`

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
- no inventory movement source type was renamed or re-bucketed.

## Test Added

File:

- `tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php`

Tests:

- `test_paid_store_stock_revision_upward_preserves_payment_creates_outstanding_delta_and_reconciles_reports`
- `test_paid_store_stock_revision_downward_preserves_payment_creates_surplus_policy_and_reconciles_reports`
- `test_unpaid_store_stock_note_rejects_refund_but_allows_revision_without_cash_or_inventory_refund_side_effect`
- `test_refunded_store_stock_note_revision_preserves_refund_history_and_reconciles_reports_without_double_reversal`
- `test_store_stock_transaction_keeps_historical_line_price_after_master_product_price_change`
- `test_paid_store_stock_revision_downward_duplicate_submit_replays_without_duplicate_revision_refund_or_stock`

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

Revision idempotency baseline proof:

```bash
php artisan test \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php \
  tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php \
  tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php
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
Tests: 43 passed (515 assertions)
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

## Backlog

No remaining backlog for this campaign.

## Final Status

Sub-slices A-E are closed with automated proof.
