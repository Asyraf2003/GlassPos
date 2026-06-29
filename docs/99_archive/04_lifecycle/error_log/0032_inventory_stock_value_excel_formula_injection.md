# 032 - Inventory stock value Excel export writes product text through formula-capable cells

Status: Strict Fixed
Keparahan: High
Klasifikasi: report export security / spreadsheet formula injection

## Ringkasan

Inventory stock value Excel export sebelumnya menulis field teks produk menggunakan `setCellValue()` langsung. Pada PhpSpreadsheet, string yang diawali formula marker seperti `=1+1` dapat disimpan sebagai formula cell, bukan literal string.

Patch sekarang membuat field teks produk pada inventory stock value export ditulis eksplisit sebagai string memakai `setCellValueExplicit(..., DataType::TYPE_STRING)`.

## Strict-Fixed-Scope

Scope yang ditutup:

- sheet `Snapshot Stok` pada inventory stock value Excel export;
- sheet `Mutasi Periode` pada inventory stock value Excel export;
- field teks produk `product_id`, `kode_barang`, `nama_barang`, `merek`, dan `ukuran` pada snapshot;
- field teks produk `product_id`, `kode_barang`, dan `nama_barang` pada movement;
- formula-like product text yang diawali `=` tetap menjadi literal string cell.

Out of scope untuk log ini:

- PDF export, karena formula evaluation adalah risiko spreadsheet client;
- CSV export, karena route terkait adalah XLSX;
- hardening semua report Excel writer di luar inventory stock value, kecuali sebagai scan blast-radius.

## Root Cause

Inventory stock value export tidak memakai central safe string writer atau helper `setCellValueExplicit(..., TYPE_STRING)` untuk string fields.

Hardening yang sudah diterapkan di supplier payable export belum diterapkan secara seragam ke inventory stock value export.

## Source Reality Setelah Patch

`app/Application/Reporting/Exports/InventoryStockValueReportExcelSnapshotSheetWriter.php`

- import `PhpOffice\PhpSpreadsheet\Cell\DataType`;
- field produk kolom `A` sampai `E` memakai helper `setStringCell()`;
- helper memanggil `setCellValueExplicit($coordinate, ..., DataType::TYPE_STRING)`;
- kolom numerik inventory tetap memakai `setCellValue()` dengan cast integer.

`app/Application/Reporting/Exports/InventoryStockValueReportExcelMovementSheetWriter.php`

- import `PhpOffice\PhpSpreadsheet\Cell\DataType`;
- field produk kolom `A` sampai `C` memakai helper `setStringCell()`;
- helper memanggil `setCellValueExplicit($coordinate, ..., DataType::TYPE_STRING)`;
- kolom kuantitas dan rupiah tetap numeric.

`tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php`

- menambahkan regression test `test_inventory_stock_value_excel_export_writes_formula_like_product_text_as_literal_strings()`;
- seed product memakai nilai formula-like `=1+1`, `=2+2`, `=3+3`, dan `=4+4`;
- assert cell value tetap sama;
- assert `getDataType()` untuk cell produk adalah `DataType::TYPE_STRING`.

## Jalur Rentan Sebelum Patch

User yang dapat membuat atau mengubah master produk memasukkan nilai formula-like pada kode/nama/merek produk -> admin mengekspor inventory stock value report ke Excel -> workbook berisi formula cell dari data produk -> saat workbook dibuka di spreadsheet client, formula dapat dievaluasi sesuai perilaku aplikasi spreadsheet.

## Dampak

Dampak bergantung pada aplikasi spreadsheet dan security setting client. Risiko yang relevan:

- formula injection di file XLSX hasil aplikasi;
- kemungkinan exfiltration atau command-like behavior pada spreadsheet client tertentu;
- trust boundary report export menjadi tidak aman untuk data teks yang berasal dari user/master data.

Keparahan dibaca High karena ini adalah unsafe output di file yang bisa dibuka admin, dan field produk dapat berasal dari input aplikasi.

## Kontrol Yang Tetap Dipertahankan

- Supplier payable Excel export tetap memiliki test formula injection sendiri.
- Central report table writer tetap memakai `setCellValueExplicit(..., TYPE_STRING)` untuk string.
- Numeric columns di inventory writer tetap numeric.
- Header statis masih ditulis dengan `setCellValue()` karena bukan user-controlled product text.

## RED Proof

Command:

```bash
php artisan test tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Hasil sebelum production patch:

- exit code `1`;
- `1 failed, 3 passed, 40 assertions`;
- failure pada formula-like product id cell;
- expected data type `s`;
- actual data type `f`;
- bukti bahwa `setCellValue()` menulis `=1+1` sebagai formula cell.

## Targeted GREEN Proof

Command:

```bash
php artisan test tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Hasil setelah patch:

- `PASS`;
- `4 passed, 52 assertions`.

Coverage targeted:

- inventory stock value export masih bisa dibuat sebagai XLSX;
- numeric rupiah/quantity cell tetap numeric;
- formula-like product text pada snapshot sheet ditulis sebagai string;
- formula-like product text pada movement sheet ditulis sebagai string;
- role guard dan max-range guard tetap hijau.

## Focused Blast-Radius Proof

Syntax checks:

```bash
php -l app/Application/Reporting/Exports/InventoryStockValueReportExcelSnapshotSheetWriter.php
php -l app/Application/Reporting/Exports/InventoryStockValueReportExcelMovementSheetWriter.php
php -l tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Hasil:

- tidak ada syntax error pada ketiga file.

Focused reporting export suite:

```bash
php artisan test tests/Feature/ReportingExports
```

Hasil:

- `PASS`;
- `56 passed, 437 assertions`.

## Full Verification Proof

Command:

```bash
make verify
```

Hasil:

- PHPStan `1794/1794`, `[OK] No errors`;
- line-count audit passed;
- Blade audit passed;
- contract audit passed;
- Pest `1173 passed, 6632 assertions`;
- duration `88.24s`;
- exit code `0`.

## Negative Search

Command:

```bash
rg -n "setCellValue\\('A'\\.\\$excelRow|setCellValue\\('B'\\.\\$excelRow|setCellValue\\('C'\\.\\$excelRow|setCellValue\\('D'\\.\\$excelRow|setCellValue\\('E'\\.\\$excelRow" app/Application/Reporting/Exports/InventoryStockValueReportExcelSnapshotSheetWriter.php app/Application/Reporting/Exports/InventoryStockValueReportExcelMovementSheetWriter.php
```

Hasil:

- tidak ada match;
- field teks produk inventory tidak lagi memakai `setCellValue()` langsung.

Command:

```bash
rg -n "setCellValue\\(|setCellValueExplicit\\(" app/Application/Reporting/Exports -g '*Excel*Writer.php'
```

Hasil relevan:

- inventory stock value writer tersisa `setCellValue()` untuk header statis dan numeric columns;
- inventory stock value product text memakai `setCellValueExplicit()`;
- `TransactionReportExcelTableWriter` tetap menjadi central safe-string writer untuk table-based exports;
- summary writers masih memakai `setCellValue()` untuk label statis dan tanggal hasil formatter, bukan product text user-controlled.

## Remaining Gaps

Belum ada shared helper lint rule yang melarang `setCellValue()` untuk semua future user-controlled string export.

Gap tersebut tidak membuka ulang status 0032 karena scope log ini adalah inventory stock value export, dan field teks produk inventory sudah memiliki RED/GREEN proof plus full verification.

## Strict Closure Decision

0032 ditutup sebagai `Strict Fixed` untuk inventory stock value XLSX export.

Dasar closure:

- root cause pada snapshot dan movement writer sudah dipatch;
- formula-like product text sekarang bertipe `TYPE_STRING`;
- numeric report behavior tetap hijau;
- focused reporting export suite hijau;
- global `make verify` hijau.
