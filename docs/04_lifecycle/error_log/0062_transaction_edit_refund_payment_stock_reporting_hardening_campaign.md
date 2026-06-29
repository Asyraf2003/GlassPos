# 0062 - Transaction Edit Refund Payment Stock Reporting Hardening Campaign

## Status

Resolved for sub-slices A-C.

Sub-slices D-E remain backlog.

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
- pending refund-due action remains visible through surplus action builder;
- no customer refund or surplus refund payment is created automatically;
- edit reversal and replacement issue are distinct from refund reversal;
- transaction summary and cash ledger use current capped allocation;
- operational profit and dashboard use real cash-in and net replacement COGS.

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

## Failing Test Proof

Initial 0062-A run failed before production patch:

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

## Patch Summary

Production code changed:

- `app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php`
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/StoreStockCogsPerDayQuery.php`

Patch behavior:

- store-stock COGS still counts `work_item_store_stock_line` stock_out;
- refund reversal still offsets COGS;
- edit/revision reversal `transaction_workspace_updated` now also offsets COGS;
- cross-period negative COGS behavior remains valid because existing tests still pass;
- no inventory movement source type was renamed or re-bucketed.

## Test Added

File:

- `tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php`

Tests:

- `test_paid_store_stock_revision_upward_preserves_payment_creates_outstanding_delta_and_reconciles_reports`
- `test_paid_store_stock_revision_downward_preserves_payment_creates_surplus_policy_and_reconciles_reports`
- `test_unpaid_store_stock_note_rejects_refund_but_allows_revision_without_cash_or_inventory_refund_side_effect`

## Regression Proof

Focused campaign proof:

```bash
php artisan test tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php
```

Result:

```text
PASS
Tests: 3 passed (163 assertions)
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
  tests/Feature/Reporting/GetDashboardOperationalPerformanceDatasetFeatureTest.php
```

Result:

```text
PASS
Tests: 27 passed (321 assertions)
```

## Financial And Stock Invariants Locked

- Refund is not edit.
- Edit is not refund.
- Paid edit upward creates outstanding delta; it does not erase old payment.
- Paid edit downward preserves old payment and creates surplus policy state.
- Unpaid note refund attempt is rejected.
- Store-stock edit uses revision reversal/reissue, not refund reversal.
- Refund reversal remains `work_item_store_stock_line_reversal`.
- Edit reversal remains `transaction_workspace_updated`.
- Operational profit and dashboard COGS use net replacement COGS after edit reversal.
- Transaction summary/cash ledger current report uses capped current allocations.
- Gross customer payment history remains preserved in `customer_payments`.

## Backlog

### 0062-D

Refunded store-stock transaction edited later:

- refund history preserved;
- original payment preserved;
- no double inventory reversal;
- transaction/cash/profit reports explain payment/refund/edit timeline.

### 0062-E

Store-stock master product price changed after transaction:

- historical transaction line price remains stable;
- report/profit does not follow changed master price;
- edit policy remains explicit.

## Final Status

Sub-slices A-C are closed with automated proof.

Sub-slices D-E are intentionally deferred to keep this campaign controlled and readable.
