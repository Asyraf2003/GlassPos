# 0054 - Supplier Receipt Reversal Projection Failure Rollback

## Status

PATCHED - targeted rollback regression proof PASS.

## Context

This slice follows the inventory/reporting analysis after:

- `0051_inventory_costing_rebuild_same_day_stock_out_skip.md`
- `0052_inventory_average_cost_rounding_residual_visibility.md`
- `0053_inventory_rounding_residual_report_presentation.md`

The previous analysis found a transaction discipline gap in supplier receipt reversal:

- `ReverseSupplierReceiptHandler` begins a transaction.
- It applies inventory delta movements through `SupplierInvoiceRevisionInventoryEffectsApplier`.
- If the projection rebuild effect returns failure, the handler returned `Result::failure(...)` directly.
- That branch did not explicitly roll back the already-started transaction.

## Problem

When supplier receipt reversal wrote delta inventory movements and the follow-up projection rebuild failed, the failure branch could leave reversal delta movement state unrolled back.

The risk was operational consistency, not HPP calculation.

Hard boundaries:

- Do not change inventory costing engine.
- Do not change HPP.
- Do not change `inventory_value_rupiah` semantics.
- Do not repair production DB.
- Do not run production DB write queries.

## Patch Summary

Updated:

- `app/Application/Procurement/UseCases/ReverseSupplierReceiptHandler.php`

Change:

```text
if inventoryEffects->apply(...) returns failure after transaction begin:
  rollBack()
  started = false
  return Result::failure(...)
```

This keeps the existing success path unchanged.

## Regression Test

Updated:

- `tests/Feature/Procurement/ReverseSupplierReceiptFeatureTest.php`

Added:

```text
test_supplier_receipt_reversal_rolls_back_delta_movement_when_projection_rebuild_fails
```

The test creates an inconsistent ledger fixture where reversal would cause projection rebuild failure after the reversal delta movement is inserted.

Expected:

- response has `supplier_receipt_reversal` error
- reversal delta movement is not persisted
- supplier receipt reversal record is not persisted
- existing product inventory remains unchanged
- existing product inventory costing remains unchanged

## Proof

Before patch, targeted test failed:

```text
Failed asserting that a row in the table [inventory_movements] does not match:
source_type = supplier_receipt_reversal_line
source_id   = receipt-line-1
```

After patch:

```text
php artisan test tests/Feature/Procurement/ReverseSupplierReceiptFeatureTest.php

PASS  Tests\Feature\Procurement\ReverseSupplierReceiptFeatureTest
✓ admin can reverse supplier receipt and restore inventory precisely
✓ admin cannot reverse same supplier receipt twice
✓ admin cannot reverse supplier receipt without reason
✓ supplier receipt reversal rolls back delta movement when projection rebuild fails

Tests: 4 passed (23 assertions)
```

## Decision

Patch is accepted as a small transaction-discipline fix.

No costing/reporting semantic change was made.

## Next Slice Candidate

`0055_inventory_report_diagnostic_owner_wording`

Possible scope:

- clarify that `avg_cost_rupiah * qty_on_hand` is diagnostic only
- keep `inventory_value_rupiah` as the main inventory value
- keep residual visible and separate from ledger mismatch
- add wording tests for page/PDF/Excel if owner approves
