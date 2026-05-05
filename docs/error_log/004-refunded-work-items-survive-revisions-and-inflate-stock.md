# 004 - Refunded work items survive revisions and inflate stock

## Status

Patched, with residual risk and verification gap.

Patch supplied for duplicate inventory reversal prevention.

The reported test could not run in the patch environment because vendor/autoload.php was missing.

## Severity

High.

## Source

Audit report #004: Refunded work items survive revisions and inflate stock.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Jenis Keterkaitan

Direct workflow relationship, separate root cause.

### Alasan

Laporan #004 dan #003 sama-sama muncul pada lifecycle:

- note refund
- note revision / workspace replacement
- historical component state
- authenticated admin/kasir mutation route

Namun root cause dan dampaknya berbeda.

- #003 membahas settlement/payment classification: revised note dengan historical refund menjadi underpaid/open karena refund double-subtracted.
- #004 membahas inventory integrity: refunded work item yang diproteksi dari delete tetap hidup sebagai active row, lalu inventory reversal bisa diproses berulang dan menaikkan stock/costing tanpa return nyata.

Karena area file, failure mode, dan patch berbeda, laporan #004 harus dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #004.

Alasan update:

- Laporan menunjukkan stale refunded work_items dapat tetap attached ke active note setelah revision.
- Stale store-stock lines dapat direversal berulang pada revision berikutnya.
- Patch minimal diterapkan di reversal path agar idempotent per reverse source.
- Verification masih gap karena feature test gagal dijalankan akibat missing vendor/autoload.php.

## Ringkasan Indonesia

Bug terjadi pada kombinasi refund-referenced work item dan note revision.

Commit yang dilaporkan mengubah delete behavior agar work item yang direferensikan oleh refund_component_allocations tidak dihapus oleh WorkItemDeletesTrait.

Masalahnya, revision flow melakukan urutan berikut:

1. reverse inventory untuk semua current work items
2. delete old work items
3. create replacement rows
4. update note total
5. rebuild payment allocations/projections

Karena WorkItemDeletesTrait menjaga work item yang punya refund_component_allocations, work item lama tidak ikut terhapus. Jika work item tersebut memiliki store-stock line, row lama tetap attached ke note aktif.

Pada revision berikutnya, note reader/reversal service dapat membaca stale work item itu lagi. ReverseIssuedInventoryByNoteService lalu mengirim store-stock line lama ke ReverseIssuedInventoryOperation lagi.

Sebelum patch, ReverseIssuedInventoryOperation tidak punya idempotency check untuk reversal yang sama. Akibatnya source stock_out yang sama bisa dibuatkan stock_in reversal berkali-kali.

## Contoh Dampak

Skenario ringkas:

- Note memiliki work item store-stock.
- Work item tersebut menghasilkan original stock_out.
- Work item direfund sehingga ada refund_component_allocations yang menunjuk work_item_id itu.
- Note direvisi.
- WorkItemDeletesTrait tidak menghapus work item tersebut karena dianggap protected.
- Revision berikutnya membaca stale work item yang sama.
- ReverseIssuedInventoryOperation membuat stock_in reversal lagi untuk source stock_out yang sama.
- qty_on_hand dan inventory value naik berulang tanpa return barang nyata.

Dampak dari PoC pada laporan:

- qty naik dari 7 ke 10
- revision berikutnya naik lagi dari 10 ke 13
- duplicate transaction_workspace_updated reversals dibuat untuk stock line yang sama

## Jalur Risiko

Authenticated cashier/admin menggunakan flow normal:

1. User memiliki authenticated session sebagai admin/kasir.
2. User mengakses note yang memiliki refunded store-stock work item.
3. User melakukan workspace revision.
4. UpdateTransactionWorkspaceWorkItemPersister menjalankan ReverseIssuedInventoryByNoteService sebelum delete old work items.
5. WorkItemDeletesTrait tidak menghapus work item yang direferensikan refund_component_allocations.
6. Stale work item dan stock line tetap attached ke active note.
7. Revision berikutnya membaca stale row yang sama.
8. ReverseIssuedInventoryOperation membuat duplicate stock_in karena tidak ada idempotency.
9. Inventory qty_on_hand dan costing/inventory_value_rupiah meningkat palsu.

## Dampak Bisnis

Ini adalah inventory-integrity dan financial-integrity issue.

Dampak utama:

- qty_on_hand bisa naik tanpa stock return nyata
- inventory value bisa inflated
- inventory movements menjadi misleading
- average cost/costing bisa terdampak
- laporan stock dan laporan finansial bisa tidak akurat
- note aktif bisa berisi old + new work items yang tidak sesuai dengan notes.total_rupiah
- standard revision workflow dapat dipakai untuk mengulang efeknya

Severity High tepat karena inventory dan valuation adalah source of truth bisnis. Tidak otomatis Critical karena membutuhkan authenticated admin/kasir dan prerequisite refunded store-stock note.

## Root Cause

Root cause gabungan:

1. WorkItemDeletesTrait menjaga refund-referenced work_items agar tidak dihapus.
2. Revision flow tetap memperlakukan semua loaded active work items sebagai kandidat reversal.
3. Stale refunded work_items tetap bisa direload sebagai active note rows.
4. ReverseIssuedInventoryOperation tidak idempotent terhadap reversal source yang sama.
5. Tidak ada unique constraint atau existing-reversal check untuk mencegah duplicate stock_in reversal.

## Patch Summary

Patch minimal diterapkan pada:

app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php

Perubahan:

- normalisasi sourceType, sourceId, dan reverseSourceType dilakukan sekali
- sebelum reversal, service mengecek existing movement dengan:
  - source_type = normalizedReverseSourceType
  - source_id = normalizedSourceId
- jika existing reversal sudah ada, method return [] lebih awal
- first-time reversal tetap berjalan seperti sebelumnya
- created reverse movement memakai normalized reverse source type dan normalized source id

Test ditambahkan/diperluas pada:

tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

Test coverage:

- first call membuat reversal seperti expected
- second call untuk source yang sama return 0 reversal
- inventory qty_on_hand tetap sama setelah second call
- product_inventory_costing tetap sama setelah second call

## Scope In

- Duplicate inventory reversal prevention.
- Idempotency in ReverseIssuedInventoryOperation.
- Inventory qty_on_hand inflation prevention.
- Inventory costing/value inflation prevention for repeated reversal calls.

## Scope Out

- Deletion strategy for refund-referenced work_items.
- Active note reader filtering for stale historical/refunded work items.
- Note total mismatch caused by old + new active rows.
- Schema-level uniqueness constraint for reversal movements.
- Full HTTP E2E for refund + revision.
- Full Laravel test pass, because dependency/vendor was missing in patch environment.

## Residual Risk

Patch mencegah duplicate stock_in reversal untuk source yang sama, tetapi tidak membuktikan bahwa stale refunded work_items tidak lagi survive sebagai active note rows.

Residual risks that still need audit:

1. Stale work item may still appear in note detail/read model.
2. Active note may still contain old + new work_items while notes.total_rupiah reflects only replacement rows.
3. Reports or UI that read work_items directly may still become inconsistent.
4. Deletion/preservation model for refund-referenced work items may need explicit archived/historical state instead of leaving rows active.
5. Idempotency in service code may need DB-level protection if concurrent revisions are possible.

## Testing Reported

Command attempted:

php artisan test tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

Result:

Failed in the patch environment.

Failure reason:

vendor/autoload.php is missing / dependencies are not installed.

Important note:

The test being added is useful, but it has not been proven passing from the supplied patch environment.

## Files Changed By Patch

app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php
tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

Reported diff size:

+25
-3

Reported commit context:

Refunded work items survive revisions and inflate stock

## Recommended Follow-up

Minimum required verification:

1. Restore/install Composer dependencies.
2. Run the focused inventory reversal test.
3. Add or run an E2E/feature test for refund-referenced work item + two revisions.
4. Verify second revision does not create duplicate stock_in movement.
5. Verify active note read model does not incorrectly expose stale refunded work_items as active editable rows.
6. Consider DB-level uniqueness/idempotency guard for reverse movements:
   - source_type
   - source_id
   - movement_type
   - reverse semantic source

Minimum commands expected later:

composer install
php artisan test tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

If full payment/note revision tests exist, run them too before trusting this area.

## Kesimpulan

Laporan #004 valid sebagai High severity inventory/financial integrity issue.

Patch minimal di ReverseIssuedInventoryOperation tepat untuk menghentikan repeated inventory/costing inflation dari duplicate reversal. Namun patch ini belum sepenuhnya membuktikan penyelesaian terhadap akar workflow yang lebih luas: refund-referenced work_items masih dapat survive deletion dan mungkin tetap terlihat sebagai active note rows.

Jadi status terbaik untuk saat ini adalah patched for duplicate reversal, not fully closed for stale work item lifecycle.
