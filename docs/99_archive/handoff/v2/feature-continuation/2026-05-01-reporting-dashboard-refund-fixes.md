# HANDOFF — Reporting/Dashboard Refund Fixes Final

Date: 2026-05-01  
Repo: /home/asyraf/Code/laravel/bengkel2/app  
Branch: main  
Remote: origin/main  

## Final Goal

Memperbaiki laporan/dashboard agar angka cash, outstanding, stok/movement, produk terjual/top-selling, dan tampilan alert laporan tidak menyesatkan setelah refund, revision, dan full refund.

## Final State

Scope ini selesai dan sudah pushed ke origin/main.

Latest pushed HEAD:

- 72c7bc86 Remove escaped newlines before report alerts
- 0b9a66bb Net dashboard top-selling products after reversals
- 26223885 Split inventory report movement buckets
- 3ebaee3b Add refunded note cash reporting fallback

## Completed Work

### 1. Money Reporting Fallback

Commit:

- 3ebaee3b Add refunded note cash reporting fallback

Root bug:

payment_allocations bisa kosong setelah refund/revision/full refund, sementara customer_refunds tetap lengkap. Akibatnya laporan transaksi dan arus kas membaca cash-in 0 tetapi refund tetap terbaca, sehingga net cash dan outstanding menjadi salah.

Changed files:

- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
- tests/Feature/Reporting/RefundedNoteCashReportingFallbackFeatureTest.php

Proof from previous scope:

- RefundedNoteCashReportingFallbackFeatureTest passed
- Reporting money tests: 9 passed, 92 assertions
- Live April sanity:
  - allocated_payment_rupiah = 710.800
  - refunded_rupiah = 710.800
  - net_cash_collected_rupiah = 0
  - outstanding_rupiah = 0
  - cash_in = 710.800
  - cash_out = 710.800
  - net = 0

### 2. Inventory Movement Bucket Split

Commit:

- 26223885 Split inventory report movement buckets

Root bug:

Inventory report sebelumnya menghitung qty_in dari semua qty_delta > 0 dan qty_out dari semua qty_delta < 0. Itu mencampur pembelian, refund reversal, dan revision/update dalam satu angka.

Fix:

Inventory movement report sekarang punya semantic buckets:

- period_supply_in_qty
- period_sale_out_qty
- period_refund_reversal_qty
- period_revision_correction_qty
- period_net_qty_delta

Legacy fields retained:

- period_qty_in
- period_qty_out

Changed files:

- app/Adapters/Out/Reporting/InventoryMovementReconciliationDatabaseQuery.php
- app/Adapters/Out/Reporting/InventoryMovementSummaryDatabaseQuery.php
- app/Adapters/Out/Reporting/InventoryMovementSummaryRowMapper.php
- app/Application/Reporting/DTO/Concerns/InventoryMovementSummaryRowAccessors.php
- app/Application/Reporting/DTO/InventoryMovementSummaryRow.php
- app/Application/Reporting/Services/InventoryMovementReportingReconciliationService.php
- app/Application/Reporting/Services/InventoryMovementSummaryBuilder.php
- app/Application/Reporting/Services/InventoryStockValueReportSummaryBuilder.php
- resources/views/admin/reporting/inventory_stock_value/index.blade.php
- tests/Feature/Reporting/GetInventoryMovementSummaryFeatureTest.php
- tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php
- tests/Feature/Reporting/InventoryMovementBucketSplitFeatureTest.php
- tests/Feature/Reporting/InventoryMovementSummaryHardeningFeatureTest.php

Proof from previous scope:

- Inventory targeted tests: 8 passed, 73 assertions
- Final targeted reporting verify: 11 passed, 123 assertions
- git diff --check clean
- Live April sanity:
  - period_supply_in_qty = 8
  - period_sale_out_qty = 9
  - period_refund_reversal_qty = 9
  - period_revision_correction_qty = 5
  - period_qty_in = 8
  - period_qty_out = 9
  - period_net_qty_delta = 13

### 3. Dashboard Top-Selling Product After Refund/Reversal

Commit:

- 0b9a66bb Net dashboard top-selling products after reversals

Root bug:

Dashboard Barang Paling Laku membaca raw work_item_store_stock_lines. Setelah full refund/reversal, raw stock lines masih ada, sehingga produk yang sudah dikembalikan tetap tampil sebagai produk terjual dan omzet.

Confirmed bad data before fix:

- Product dad masih tampil dari raw stock lines:
  - qty = 9
  - revenue = Rp 1.098.000
- inventory_movements punya reversal 1:1 untuk semua stock line:
  - source_type = work_item_store_stock_line_reversal
  - source_id = work_item_store_stock_lines.id
  - total reversal qty = 9

Decision:

Top-selling harus dinetralisir memakai inventory movement reversal linkage, bukan refund_component_allocations sebagai sumber utama.

Reason:

refund_component_allocations tidak selalu lengkap untuk semua stock line di live April check, sedangkan inventory_movements reversal lengkap dan terhubung langsung ke stock_line_id.

Fix:

DashboardTopSellingProductQuery sekarang:

- tetap memakai work_item_store_stock_lines sebagai gross stock sale source
- left join aggregate inventory_movements reversal by source_id
- menghitung net sold qty:
  - stock_lines.qty - reversal_qty
  - minimum 0
- menghitung revenue secara proporsional terhadap net qty
- hide product aggregate dengan net sold qty <= 0

Changed files:

- app/Adapters/Out/Reporting/Queries/DashboardTopSellingProductQuery.php
- tests/Feature/Reporting/DashboardTopSellingProductQueryFeatureTest.php

Proof:

- DashboardTopSellingProductQueryFeatureTest: 2 passed
- AdminDashboardPageFeatureTest: 3 passed, 49 assertions
- git diff --cached --check clean before commit
- Live April top-selling after patch:
  - []

Expected behavior:

Fully refunded/reversed products do not remain counted as sold products in Barang Paling Laku.

### 4. Report Alert Escaped Newline Cleanup

Commit:

- 72c7bc86 Remove escaped newlines before report alerts

Root bug:

Two report Blade files had literal escaped newline text before alert block:

- \n<div class="alert alert-warning ...

Changed files:

- resources/views/admin/reporting/inventory_stock_value/index.blade.php
- resources/views/admin/reporting/supplier_payable/index.blade.php

Proof:

- GetInventoryStockValueReportDatasetFeatureTest: 1 passed, 11 assertions
- GetSupplierPayableReportDatasetFeatureTest: 1 passed, 19 assertions
- git diff --check clean
- pushed to origin/main

## Locked Decisions

- Cash totals must use cash records, not component projection.
- Inventory movements remain immutable and auditable.
- Do not delete or hide reversal movement rows.
- Inventory report must be fixed by semantic buckets, not by mutating movement history.
- Full refund must not produce negative transaction cash report or outstanding debt.
- Dashboard top-selling must net stock line reversals.
- work_item_store_stock_lines must not be mutated to hide refunded sales.
- Refund component allocation is not the primary neutralizer for top-selling because it may not fully map every stock line in observed live data.
- inventory_movements source_type/source_id linkage is the reliable neutralizer for stock-line reversal reporting.

## Current Final Remote Proof

Final pushed remote after cleanup:

- origin/main = 72c7bc86 Remove escaped newlines before report alerts

Recent log after final push:

- 72c7bc86 Remove escaped newlines before report alerts
- 0b9a66bb Net dashboard top-selling products after reversals
- 26223885 Split inventory report movement buckets
- 3ebaee3b Add refunded note cash reporting fallback

## Safe Verification Commands

Run from repo root:

    cd /home/asyraf/Code/laravel/bengkel2/app

    git branch --show-current
    git --no-pager log -4 --oneline --decorate
    git status --short

    php artisan test tests/Feature/Reporting/DashboardTopSellingProductQueryFeatureTest.php
    php artisan test tests/Feature/Admin/AdminDashboardPageFeatureTest.php
    php artisan test tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php
    php artisan test tests/Feature/Reporting/GetSupplierPayableReportDatasetFeatureTest.php

Optional live top-selling sanity:

    php artisan tinker --execute='
    $rows = app(App\Adapters\Out\Reporting\Queries\DashboardTopSellingProductQuery::class)
        ->rows("2026-04-01", "2026-04-30", 5);

    dump($rows);
    '

Expected for the previously observed April full-refund-only stock lines:

    []

## Remaining Known Gaps

No known open gap for this scope.

This does not prove the entire reporting system is bug-free. It only proves the audited bugs in this handoff were fixed with targeted tests, live sanity where available, commit history, and remote push proof.

## Safest Next Step

Do not reopen money reporting, inventory bucket split, or top-selling semantics unless new proof shows regression.

If continuing work, start a new scope with:

1. git status --short
2. git --no-pager log -5 --oneline --decorate
3. exact bug evidence or requested feature
4. one active step only

## Opening Prompt For Next Session

Lanjutkan project Hyperpos dari repo lokal /home/asyraf/Code/laravel/bengkel2/app. Wajib baca repo rules/dokumen seperlunya. Local command output adalah source of truth tertinggi. Scope reporting/dashboard refund fixes sudah selesai dan pushed ke origin/main. Commit terkait: 3ebaee3b Add refunded note cash reporting fallback, 26223885 Split inventory report movement buckets, 0b9a66bb Net dashboard top-selling products after reversals, 72c7bc86 Remove escaped newlines before report alerts. Jangan ubah ulang money reporting, inventory movement bucket, atau top-selling refund netting kecuali ada proof regression baru. Mulai scope baru hanya dengan snapshot repo, exact bug evidence, blueprint kecil, test-first, patch, targeted tests, diff check, dan push proof.
