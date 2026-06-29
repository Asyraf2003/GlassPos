# 0050 - Inventory Costing Rebuild Same-day Stock-out Skip

## Status

FINAL CLOSED.

Closed after targeted same-day stock-out regression, local rebuild residual verification, and closure regression for net-zero product ledger cleanup.

## Summary

Inventory costing projection rebuild can overstate `product_inventory_costing.inventory_value_rupiah` when a `stock_out` movement is replayed before matching or later `stock_in` movements for the same product.

The quantity projection can remain correct because `InventoryProjectionBuilder` sums all `qty_delta`, while costing projection replays movements sequentially and silently skips `stock_out` when current replay state qty is zero.

## Owner Proof

Owner inspected product `prod-year-001`.

Projection and ledger mismatch:

```text
projection_qty   = 9
projection_value = 11800
movement_qty     = 9
movement_value   = 10650
qty_diff         = 0
value_diff       = 1150

Replay proof using the same effective order as rebuild:

order: tanggal_mutasi, id

first movement:
movement_type      = stock_out
source_type        = work_item_store_stock_line
qty_delta          = -1
total_cost_rupiah  = -1150
before_qty         = 0
before_value       = 0
applied            = false
reason             = skipped_by_builder_condition
after_qty          = 0
after_value        = 0

Final replay result:

final_qty_by_replay   = 10
final_value_by_replay = 11800
avg_by_replay         = 1180

Expected ledger value from inventory_movements:

-1150 + 1150 + 11500 - 1150 + 300 = 10650

Actual costing projection:

inventory_value_rupiah = 11800

Mismatch:

11800 - 10650 = 1150
```

## Source Evidence

`InventoryCostingProjectionBuilder` only applies `stock_out` when replay state qty is greater than zero.

`DatabaseInventoryMovementReaderAdapter::getAll()` orders rebuild movements by `tanggal_mutasi`, then `id`.

Same-day movements can therefore be replayed in UUID/id order, not business lifecycle order.

Existing `RebuildInventoryCostingProjectionWithStockOutFeatureTest` only covers safe ordering: stock-in first, then stock-out on a later date.

## Root Cause

`InventoryCostingProjectionBuilder` silently skips `stock_out` when replay state qty is zero:

```php
} elseif ($m->movementType() === 'stock_out' && $state[$pId]['qty'] > 0) {
```

This makes costing rebuild order-sensitive.

If a stock-out movement appears before stock-in for the same product due to same-day ordering by UUID/id, the stock-out cost is ignored.

## Impact

- `product_inventory.qty_on_hand` can remain correct.
- `product_inventory_costing.inventory_value_rupiah` can be overstated.
- Inventory stock value report can show inflated inventory value.
- Rounding residual diagnostics can be polluted by real projection mismatch.
- Annual reports can accumulate material value drift if multiple products hit this edge case.

## Classification

This is not a UI formatting bug.

This is not only integer rounding residual.

This is a confirmed costing projection rebuild bug caused by replay-order sensitivity and silent stock-out skip.

## Required Regression Test

Add a test where same-day movements are ordered so `stock_out` appears before `stock_in` by ID:

```text
m1 stock_out -1 -1150 tanggal_mutasi same day
m2 stock_in  +1 +1150 tanggal_mutasi same day
m3 stock_in +10 +11500 tanggal_mutasi same day
m4 stock_out -1 -1150 tanggal_mutasi same day
m5 cost_revaluation +300 next day
```

Expected result must align with ledger:

```text
inventory_value_rupiah = 10650
```

The current buggy behavior produces:

```text
inventory_value_rupiah = 11800
```

## Solution Direction

Do not silently skip stock-out in rebuild.

Potential directions:

1. Make rebuild cost projection ledger-based for `inventory_value_rupiah`:
   - `value = SUM(total_cost_rupiah)` per product
   - `qty = SUM(qty_delta)` per product
   - `avg = intdiv(value, qty)` when qty > 0
2. Or define a stable business replay order that guarantees stock-in/reversal is processed before dependent stock-out for the same product and date.
3. Add invariant checks:
   - costing projection value must match movement ledger value for products with positive qty
   - report diagnostics should separate ledger mismatch from rounding residual

## Next Step

Create a failing regression test before patching production code.

Target file:

`tests/Feature/Inventory/RebuildInventoryCostingProjectionWithStockOutFeatureTest.php`

## Session Update - Targeted Patch Applied

### Status Update

PATCHED - targeted regression proof PASS.

### Files Changed

- `app/Application/Inventory/Services/InventoryCostingProjectionBuilder.php`
- `tests/Feature/Inventory/RebuildInventoryCostingProjectionWithStockOutFeatureTest.php`

### Patch Summary

`InventoryCostingProjectionBuilder` was changed from order-sensitive replay logic into ledger aggregation logic.

The rebuilt costing projection now derives:

```text
qty   = SUM(qty_delta) per product
value = SUM(total_cost_rupiah) per product
avg   = intdiv(value, qty)
```

This removes the previous silent skip behavior where `stock_out` could be ignored when replay state qty was zero.

### Regression Proof

Added targeted regression test:

```text
test_rebuild_costing_projection_does_not_skip_same_day_stock_out_before_stock_in
```

The test reproduced the confirmed bug:

```text
buggy actual:
avg_cost_rupiah         = 1180
inventory_value_rupiah = 11800
```

Expected ledger-safe result:

```text
avg_cost_rupiah         = 1183
inventory_value_rupiah = 10650
```

After patch, targeted test passed.

### Follow-up Verification Requirement - Completed

Broader inventory/reporting verification and read-only residual diagnostics were required to confirm:

```text
value_diff = 0 for projection vs movement ledger
```

Remaining residuals were confirmed as true integer average-cost rounding residuals only.

## Session Update - Local Rebuild and Residual Verification

### Rebuild Proof

Owner ran costing projection rebuild after patch.

Result:

```text
success         = true
message         = Inventory costing projection rebuilt.
total_movements = 18
total_products  = 6
```

### Post-rebuild Diagnostic

Read-only projection-vs-ledger diagnostic after rebuild:

```text
mismatch_rows                = 2
total_qty_diff               = 0
total_value_diff             = 0
total_rounding_residual      = 26
```

Rows with remaining residual:

```text
prod-year-006:
projection_qty       = 30
projection_avg       = 1149
projection_value     = 34493
movement_qty         = 30
movement_value       = 34493
qty_diff             = 0
value_diff           = 0
rounding_residual    = 23

prod-year-001:
projection_qty       = 9
projection_avg       = 1183
projection_value     = 10650
movement_qty         = 9
movement_value       = 10650
qty_diff             = 0
value_diff           = 0
rounding_residual    = 3
```

### Final Verification

The confirmed projection mismatch is fixed.

The remaining `mismatch_rows` are not ledger mismatches. They are true integer average-cost rounding residuals:

```text
prod-year-001: 10650 - (1183 * 9)  = 3
prod-year-006: 34493 - (1149 * 30) = 23
total residual = 26
```

### Final Status

- `product_inventory_costing.inventory_value_rupiah` now matches movement ledger value.
- `product_inventory.qty_on_hand` matches movement ledger qty.
- Remaining residual is expected from integer average-cost storage.
- Follow-up should treat residual visibility separately from projection correctness.

## Final Closure - 2026-06-29 WITA

### Status

FINAL CLOSED.

### Closure Regression Added

Added closure regression coverage:

```text
test_rebuild_costing_projection_removes_stale_projection_when_product_ledger_net_qty_is_zero
```

This proves rebuild does not keep stale `product_inventory_costing` rows when a product has ledger movements but final net quantity is zero.

### Final Test Proof

Owner reported PASS after adding the closure regression:

```bash
php artisan test tests/Feature/Inventory/RebuildInventoryCostingProjectionWithStockOutFeatureTest.php
```

Existing coverage also confirms:

- normal stock-out rebuild remains valid
- value-only cost revaluation remains valid
- same-day stock-out before stock-in no longer inflates costing projection
- stale projection rows are cleared when ledger is empty
- stale projection row is cleared when a specific product ledger nets to zero

### Final Decision

No production code patch is required after the closure regression.

The costing projection rebuild now derives inventory costing from the movement ledger aggregate:

```text
qty   = SUM(qty_delta)
value = SUM(total_cost_rupiah)
avg   = intdiv(value, qty)
```

Products with `qty <= 0` are intentionally excluded from `product_inventory_costing`.

### No Remaining Follow-up

No remaining technical debt is tracked under this error log.

Any future inventory rounding residual work must stay in the residual visibility/reporting logs, not this costing rebuild correctness log.
