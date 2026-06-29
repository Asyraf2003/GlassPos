# 0055 - Inventory Report Diagnostic Owner Wording

## Status

PATCHED - targeted presentation regression proof PASS.

## Context

This slice follows the inventory/reporting analysis after:

- `0051_inventory_costing_rebuild_same_day_stock_out_skip.md`
- `0052_inventory_average_cost_rounding_residual_visibility.md`
- `0053_inventory_rounding_residual_report_presentation.md`
- `0054_supplier_receipt_reversal_projection_failure_rollback.md`

The reporting model intentionally separates:

```text
inventory_value_rupiah
```

from the diagnostic derived value:

```text
avg_cost_rupiah * qty_on_hand
```

and the rounding residual:

```text
inventory_value_rupiah - (avg_cost_rupiah * qty_on_hand)
```

Rounding residual is not a ledger mismatch while:

```text
total_ledger_value_diff_rupiah = 0
```

## Problem

The report already exposed residual and ledger diff, but owner-facing presentation still risked over-reading `Nilai Berdasar Avg x Qty` as an alternate inventory value.

That wording risk could make a user compare two inventory values without understanding that:

- `Nilai Persediaan` is the main inventory value.
- `Avg x Qty` is diagnostic only.
- `Residual Pembulatan HPP` is an integer average-cost rounding residual.
- `Selisih Nilai Ledger` remains the projection-vs-ledger mismatch indicator.

This was a presentation clarity issue, not a costing/HPP bug.

Hard boundaries:

- Do not change inventory costing engine.
- Do not change HPP.
- Do not change `inventory_value_rupiah` semantics.
- Do not change report dataset/query calculations.
- Do not hide rounding residual as an error.
- Do not merge rounding residual with ledger mismatch.

## Patch Summary

Updated:

- `resources/views/admin/reporting/inventory_stock_value/index.blade.php`
- `app/Application/Reporting/Exports/InventoryStockValueReportExcelSummarySheetWriter.php`
- `app/Application/Reporting/Exports/InventoryStockValueReportExcelSnapshotSheetWriter.php`
- `app/Application/Reporting/Exports/InventoryStockValueReportPdfViewDataBuilder.php`

Change:

```text
Add owner-readable diagnostic note:
Nilai utama tetap Nilai Persediaan; Avg x Qty hanya pembanding pembulatan.
```

Presentation adjustments:

- Report page now shows `Diagnostik Internal` before residual/ledger diagnostic cards.
- Excel Ringkasan now includes `Catatan Diagnostik`.
- Excel Snapshot headers label Avg x Qty and residual as diagnostic.
- PDF summary builder includes the same diagnostic note.

No dataset, engine, HPP, or database semantic change was made.

## Regression Test

Updated:

- `tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php`
- `tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php`
- `tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php`

Covered:

- report page shows diagnostic note
- Excel Ringkasan shows diagnostic note
- Excel Snapshot labels derived value/residual as diagnostic
- PDF view/export data keeps diagnostic note available

## Proof

Before patch, targeted tests failed as expected:

```text
InventoryStockValueReportPageFeatureTest
Failed asserting response contains: Diagnostik Internal

InventoryStockValueReportExcelExportFeatureTest
Failed asserting that an array contains 'Catatan Diagnostik'.
```

After patch:

```text
php artisan test tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php

PASS  Tests\Feature\Reporting\InventoryStockValueReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\InventoryStockValueReportExcelExportFeatureTest
PASS  Tests\Feature\ReportingExports\InventoryStockValueReportPdfExportFeatureTest

Tests: 19 passed (155 assertions)
```

## Decision

Patch accepted as a small presentation-discipline fix.

`inventory_value_rupiah` remains the source of truth for inventory value. `avg_cost_rupiah * qty_on_hand` remains derived/diagnostic only.

## Next Slice Candidate

`0056_inventory_movement_source_type_registry_guard`

Possible scope:

- centralize known inventory movement `source_type` values
- add tests that report movement summaries do not silently misclassify known sources
- document unknown source behavior as an audit gap, not a costing error
