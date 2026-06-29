# 0046 PDF Report Table Layout Owner Readability Gap

Status: Full Verified

Reported by owner on 2026-06-26. This log captures the report readability
problem found after the latest `0045` lifecycle/report fixes.

## Scope

Fix the report presentation contract from system data shape to screen UI and PDF
export:

- PDF must be a readable report, not a copied table dump.
- Screen report UI must follow the same report shape as the PDF.
- Report wording must be plain operational reporting language.
- PDF and screen must prioritize summary, explanation, and clear numbers.
- Excel must keep detailed rows and sheets for audit/export work.
- Reporting remains read-only and must not become domain correction logic.

## Scope Out

- Do not change core payment, refund, inventory, payroll, procurement, or note
  mutation rules under this issue.
- Do not remove Excel detail sheets.
- Do not patch report formulas before source/domain proof shows a formula bug.
- Do not close `0044` or `0045` from this issue.

## 2026-06-27 Owner Follow-up - Remove Descriptions And Mass Detail Cards

### FACT

Owner clarified that production report data can contain thousands of products
or hundreds of service-package rows. Replacing detail tables with one card per
row is still the wrong contract for screen/PDF because it only changes the
shape of the detail dump.

Owner also clarified that explanatory description blocks such as
`Catatan Laporan`, `Detail lengkap tersedia di Excel`, and paragraph copy under
`Rincian Ringkas` add noise. The report should show titles and numbers only.

### ANALYSIS

The previous patch removed dense tables from active PDF reports, but several
screen pages still had description paragraphs. `inventory_stock_value` also
rendered one card per product snapshot and one card per product movement.
`service_package_profit_breakdown` still rendered the full detail table on the
screen.

For production scale, the owner-readable screen/PDF contract must be:

- title/header;
- `Ringkasan Utama` numbers;
- `Rincian Ringkas` aggregate numbers only when useful;
- no explanatory paragraphs;
- no product/package/detail-row loop on screen/PDF;
- Excel remains the place for all detailed rows.

### PATCH

Patched screen/PDF presentation:

- removed `Catatan Laporan` and the `Detail lengkap tersedia di Excel` copy
  from active report PDFs and screens;
- removed paragraph descriptions under report section headings;
- changed `inventory_stock_value` screen so it no longer renders per-product
  snapshot/movement cards;
- changed `service_package_profit_breakdown` screen so it no longer renders
  the detail package table;
- kept Excel detail exports unchanged.

Patched system path for production-scale UI/PDF:

- `InventoryStockValueReportPageController` and
  `InventoryStockValueReportPdfExportController` now use summary-only data;
- `ServicePackageProfitBreakdownReportPageController` now uses summary-only
  data;
- added summary-only reader/query path for inventory stock value;
- added summary-only reader/query path for service package profit breakdown;
- retained detail-row data path for Excel exports.

### PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php tests/Feature/Reporting/ServicePackageProfitBreakdownReportPageFeatureTest.php tests/Feature/Reporting/ServicePackageProfitBreakdownUiScenarioMatrixFeatureTest.php tests/Feature/ReportingExports/ServicePackageProfitBreakdownExcelExportFeatureTest.php tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php tests/Feature/Reporting/InventoryMovementSummaryHardeningFeatureTest.php tests/Feature/Reporting/InventoryMovementBucketSplitFeatureTest.php tests/Feature/Reporting/ServicePackageProfitBreakdownQueryTest.php
```

Result:

```text
Tests: 30 passed (265 assertions)
```

Command:

```bash
./vendor/bin/phpstan analyze --memory-limit=-1
```

Result:

```text
[OK] No errors
```

### NEXT

Full verification completed after this follow-up patch.

Command:

```bash
make verify
```

Result:

```text
[OK] No errors
Contract audit passed.
Tests: 1439 passed (8597 assertions)
```

Meaning:

- PHPStan passed;
- line-limit and Blade PHP/directive checks passed;
- contract audit passed;
- full Pest suite passed after removing screen/PDF descriptions, removing
  mass detail rendering, and adding summary-only UI/PDF query paths.

## FACT

Source inspection found active report routes for PDF and Excel in
`routes/web/admin_reporting.php`.

Active PDF routes exist for:

- `transaction_cash_ledger`
- `payroll`
- `employee_debt`
- `operational_profit`
- `operational_expense`
- `supplier_payable`
- `inventory_stock_value`
- `transaction_summary`

`service_package_profit_breakdown` currently has screen and Excel routes, but no
PDF route in `routes/web/admin_reporting.php`.

PDF Blade files currently render report content with HTML tables:

- `resources/views/admin/reporting/transaction_cash_ledger/export_pdf.blade.php`
- `resources/views/admin/reporting/payroll/export_pdf.blade.php`
- `resources/views/admin/reporting/employee_debt/export_pdf.blade.php`
- `resources/views/admin/reporting/operational_profit/export_pdf.blade.php`
- `resources/views/admin/reporting/operational_expense/export_pdf.blade.php`
- `resources/views/admin/reporting/supplier_payable/export_pdf.blade.php`
- `resources/views/admin/reporting/inventory_stock_value/export_pdf.blade.php`
- `resources/views/admin/reporting/transaction_summary/export_pdf.blade.php`

Screen report pages also render detailed tables for the same report families:

- `resources/views/admin/reporting/transaction_cash_ledger/index.blade.php`
- `resources/views/admin/reporting/payroll/index.blade.php`
- `resources/views/admin/reporting/employee_debt/index.blade.php`
- `resources/views/admin/reporting/operational_profit/index.blade.php`
- `resources/views/admin/reporting/operational_expense/index.blade.php`
- `resources/views/admin/reporting/supplier_payable/index.blade.php`
- `resources/views/admin/reporting/inventory_stock_value/index.blade.php`
- `resources/views/admin/reporting/transaction_summary/index.blade.php`
- `resources/views/admin/reporting/service_package_profit_breakdown/index.blade.php`

Excel exports are implemented separately through workbook builders in
`app/Application/Reporting/Exports/*Excel*` and are covered by existing
`tests/Feature/ReportingExports/*ExcelExportFeatureTest.php` files.

PDF exports are covered by existing tests that mainly assert route response,
range rejection, PDF content type, `%PDF` header, and selected rendered HTML
markers. They do not yet prove owner-readable layout quality.

## Owner Clue

The owner-facing problem is not only whether PDF generation succeeds.

The problem is that report PDFs are hard to read because they look like tables.
The expected report is a clean readable report/ringkasan:

- important totals first;
- short report-language labels;
- grouped explanation of what the numbers mean;
- no dense table-first PDF;
- no accounting-heavy wording for owner review;
- detailed row data remains available in Excel.

## Problem Analysis

The current implementation has two presentation contracts mixed together:

1. Excel/export contract:
   - detailed rows;
   - multiple sheets;
   - table-first layout;
   - suitable for audit and data checking.
2. Owner report contract:
   - quick reading;
   - summary-first;
   - plain operational labels;
   - suitable for screen review and PDF.

The PDF layer currently behaves closer to the Excel contract because the PDF
Blade files render summary/detail tables. The screen UI also still keeps many
detail tables as the primary visual structure.

This makes the reports technically correct but weak for owner reading.

## Source Map

Route layer:

- `routes/web/admin_reporting.php`

Screen controller layer:

- `app/Adapters/In/Http/Controllers/Admin/Reporting/*ReportPageController.php`

PDF controller layer:

- `app/Adapters/In/Http/Controllers/Admin/Reporting/*PdfExportController.php`

PDF view-data builders:

- `app/Application/Reporting/Exports/*PdfViewDataBuilder.php`

PDF Blade views:

- `resources/views/admin/reporting/*/export_pdf.blade.php`

Screen Blade views:

- `resources/views/admin/reporting/*/index.blade.php`

Excel workbook builders and sheet writers:

- `app/Application/Reporting/Exports/*ExcelWorkbookBuilder.php`
- `app/Application/Reporting/Exports/*Excel*SheetWriter.php`

Existing report/export tests:

- `tests/Feature/Reporting/*ReportPageFeatureTest.php`
- `tests/Feature/ReportingExports/*PdfExportFeatureTest.php`
- `tests/Feature/ReportingExports/*ExcelExportFeatureTest.php`

## Decision

Use one report presentation contract for screen and PDF:

- screen and PDF show the owner-readable report/ringkasan;
- Excel keeps detailed tables and sheets;
- report labels must be rewritten as report language, not accounting-heavy
  internal vocabulary;
- source data and formulas stay in reporting use cases/read models unless a
  separate formula bug is proven;
- presentation mappers may reshape data for readability, but may not repair
  lifecycle/domain state.

## Proposed Report Shape

Each screen/PDF report should be built from these sections:

1. Header
   - report name;
   - period;
   - date basis in plain language.
2. Ringkasan Utama
   - 3-6 key numbers only;
   - short labels;
   - clear positive/negative meaning.
3. Catatan Laporan
   - 2-5 plain sentences explaining what changed or what needs attention.
4. Rincian Ringkas
   - grouped highlights, not full detailed row tables.
5. Export Detail
   - a clear Excel action for users who need full detail.

## Wording Direction

Replace wording that reads like accounting/internal calculation with owner
report wording.

Examples:

- `Nilai Bersih` may become `Sisa Kas Setelah Keluar Masuk`.
- `Outstanding` may become `Sisa yang Belum Dibayar`.
- `Grand Total` may become `Total Nilai`.
- `Allocated` may become `Pembayaran yang Sudah Masuk`.
- `COGS/HPP` may become `Harga Beli Barang yang Terpakai` where the context is
  owner-facing.

Final labels must be decided per report during implementation and tested from
rendered screen/PDF output.

## Affected Report Matrix

### Transaction Cash Ledger

- PDF problem: dense event table.
- UI problem: detail event table dominates the screen.
- Desired report: cash in, cash out, net cash, payment/refund explanation,
  compact highlights.
- Excel: keep event-level detail.

### Transaction Summary

- PDF problem: per-note table is too detailed for a PDF report.
- UI problem: screen presents multiple tables before an owner-readable story.
- Desired report: number of notes, total transaction value, paid/refund/debt
  summary, status highlights.
- Excel: keep per-note and customer/detail sheets.

### Operational Profit

- PDF problem: summary is still table-shaped and label-heavy.
- UI problem: card labels still need report wording alignment.
- Desired report: money in, money out, operating cash result, short explanation.
- Excel: keep numeric summary sheet.

### Operational Expense

- PDF problem: category/detail tables are not report-like.
- UI problem: category/detail tables dominate.
- Desired report: total expenses, top category, daily average, notable groups.
- Excel: keep category and detail sheets.

### Supplier Payable

- PDF problem: supplier/detail payable tables are dense.
- UI problem: multiple tables with supplier aging/status details dominate.
- Desired report: total supplier bills, paid amount, remaining unpaid amount,
  due/overdue highlights in plain wording.
- Excel: keep supplier, period, and detail sheets.

### Employee Debt

- PDF problem: status/detail debt tables are dense.
- UI problem: status/detail tables dominate.
- Desired report: total employee debt, paid, remaining, status highlights.
- Excel: keep status and detail sheets.

### Payroll

- PDF problem: period/mode/detail payroll tables are dense.
- UI problem: tables dominate.
- Desired report: total payroll paid, latest date, main payment mode, daily
  average.
- Excel: keep period, mode, and detail sheets.

### Inventory Stock Value

- PDF problem: snapshot and movement tables make the PDF hard to read.
- UI problem: snapshot/movement tables dominate.
- Desired report: stock count, total stock value, in/out/refund/revision movement
  highlights, low/critical stock count.
- Excel: keep snapshot and movement sheets.

### Service Package Profit Breakdown

- Current state: screen and Excel exist, PDF route is not active.
- UI problem: detail table dominates.
- Desired report: package sales, sparepart value, service component, refund
  impact, profit highlight.
- Excel: keep package detail sheet.
- PDF decision needed: add PDF route only if owner wants this report exported as
  PDF in the same style.

## Implementation Blueprint

Target:

- convert report screen/PDF presentation to summary-first readable reports while
  preserving Excel detail.

Current state:

- report data exists;
- PDF exports render successfully;
- Excel exports preserve detailed data;
- screen/PDF layout is table-heavy.

Constraints:

- reporting is read-only;
- no domain correction in reporting presentation;
- Excel detail remains unchanged unless a separate Excel bug is proven;
- preserve existing auth/admin route boundary;
- preserve PDF 30-day range limits and Excel 366-day range limits.

Dependencies:

- per-report source map;
- owner-approved wording per report;
- rendered HTML/PDF response tests;
- Excel regression tests to prove detail remains available.

Risks:

- over-flattening the report may hide data needed for audit;
- changing labels without tests can break owner expectations;
- UI and PDF can drift if each report is patched independently;
- PDF layout can pass `%PDF` but still be unreadable.

Recommended approach:

1. Create shared report presentation DTO/value structure for summary-first
   screen/PDF sections.
2. Convert one report first as a vertical slice.
3. Add tests that assert screen and PDF use the same owner-facing labels and do
   not render dense detail tables as the primary PDF body.
4. Keep Excel export tests green and unchanged for detail sheets.
5. Repeat report-by-report.

## Proposed Step Order

1. Choose the first vertical slice, recommended:
   `operational_profit`, because it is already summary-oriented and has active
   PDF/Excel tests.
2. Build shared screen/PDF report presentation structure.
3. Convert `operational_profit` screen and PDF to the new structure.
4. Prove:
   - screen renders report-style labels;
   - PDF rendered HTML uses report sections;
   - PDF response still returns `%PDF`;
   - Excel export still keeps numeric detail.
5. Convert the next report family only after the first slice is proven.

## GAP

- No screenshot or rendered PDF visual proof has been captured in this log.
- Exact final owner-facing wording for every report is not yet approved.
- It is not yet proven whether all detail tables should disappear from screen or
  move below the report summary as secondary/detail sections.
- It is not yet decided whether `service_package_profit_breakdown` needs a PDF
  route.

## ACTIVE STEP

Create this error log and stop before implementation.

Next allowed implementation step after owner feedback:

- pick the first report slice and add characterization tests for current
  table-heavy screen/PDF output.

## PROOF

Initial source inspection commands, from
`/home/asyraf/Code/laravel/bengkel2/app`:

```bash
sed -n '1,240p' routes/web/admin_reporting.php
rg -n "<table|<thead|<tbody|Ringkasan|Laporan|Total|Kas|Laba|Hutang|Persediaan|Payroll|Gaji|Excel|PDF" resources/views/admin/reporting -S
rg -n "loadHtml|export_pdf|Excel|download|view\\('admin\\.reporting|MAX_PDF_RANGE_DAYS|filename" app/Adapters/In/Http/Controllers/Admin/Reporting -S
rg -n "export_pdf|export.pdf|application/pdf|%PDF|Content-Type.*pdf|loadHtml|dompdf|export_excel|xlsx" tests app/Application/Reporting/Exports resources/views/admin/reporting -S
fd . app/Application/Reporting/Exports -t f
git status --short --untracked-files=all
git rev-parse --short HEAD
```

Visible result:

- active reporting route map was found;
- active PDF routes were found for 8 report families;
- active PDF Blade files were found with HTML table structures;
- active screen report views were found with detail table structures;
- Excel workbook builders and Excel export tests were found;
- current HEAD during intake: `48118800`;
- `git status --short --untracked-files=all` printed no rows before this log was
  added.

## PROGRESS

10%.

This issue is logged, source-mapped at intake level, and has an initial
operational-profit RED characterization. No production code has been changed yet.

## 2026-06-26 RED Characterization - Operational Profit Slice

### FACT

The first vertical slice is `operational_profit`.

RED tests added:

- `tests/Feature/ReportingExports/OperationalProfitReportPdfExportFeatureTest.php`
  - `test_operational_profit_pdf_view_uses_owner_readable_report_sections_not_table_layout`
- `tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_operational_profit_page`

The tests lock the desired screen/PDF report contract:

- render `Ringkasan Utama`;
- render `Catatan Laporan`;
- render `Detail lengkap tersedia di Excel`;
- PDF must not keep `<table class="summary">` as the report body layout.

### PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/OperationalProfitReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php
```

Result:

```text
FAIL  Tests\Feature\ReportingExports\OperationalProfitReportPdfExportFeatureTest
✓ admin can export operational profit report as pdf
✓ kasir cannot export operational profit report as pdf
✓ operational profit pdf view contains indonesian report labels
⨯ operational profit pdf view uses owner readable report sections not table layout

FAIL  Tests\Feature\Reporting\OperationalProfitReportPageFeatureTest
✓ guest is redirected to login when accessing operational profit report page
✓ kasir is redirected back to cashier dashboard when accessing operational profit report page
✓ admin can access operational profit report page and see cash based metrics
⨯ admin sees owner readable report sections on operational profit page

Tests: 2 failed, 6 passed, 37 assertions
```

Failure meaning:

- PDF rendered HTML does not contain `Ringkasan Utama`.
- Screen report HTML does not contain `Ringkasan Utama`.
- The failure matches the owner-readable report layout gap, not a route/auth or
  fixture failure.

### NEXT

Patch only the `operational_profit` screen/PDF presentation so these two RED
tests become GREEN while keeping Excel export detail unchanged.

## 2026-06-26 Patch Proof - Operational Profit Slice

### FACT

Patched only the operational-profit presentation layer:

- `resources/views/admin/reporting/operational_profit/export_pdf.blade.php`
  - removed table-shaped summary body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/operational_profit/index.blade.php`
  - added matching `Ringkasan Utama`;
  - added matching `Catatan Laporan`;
  - added Excel detail note.

No query, controller, domain, payment/refund, inventory, or Excel writer file was
changed for this slice.

### PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/OperationalProfitReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php tests/Feature/ReportingExports/OperationalProfitReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\OperationalProfitReportPdfExportFeatureTest
PASS  Tests\Feature\Reporting\OperationalProfitReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\OperationalProfitReportExcelExportFeatureTest

Tests: 10 passed, 61 assertions
```

Meaning:

- operational-profit PDF still exports as `%PDF`;
- operational-profit PDF now renders owner-readable report sections;
- operational-profit screen now renders the same report sections;
- operational-profit Excel export remains available and still preserves numeric
  cells.

### NEXT

Continue with the next report family using the same RED -> patch -> GREEN ->
log-update sequence.

## 2026-06-27 Full Verify Attempt And Cash Ledger Unit Contract Patch

### FACT

After the active PDF report families had owner-readable PDF/screen sections in
place, full verification was run from
`/home/asyraf/Code/laravel/bengkel2/app`:

```bash
make verify
```

The run passed PHPStan and the contract audits, then failed in Pest on one unit
test:

```text
[OK] No errors
Contract audit passed.
Tests:    1 failed, 1438 passed (8553 assertions)
```

Failing test:

- `tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php`
  - expected PDF HTML to contain `Metode Pembayaran`

### ANALYSIS

The failing test was still enforcing the old PDF contract: transaction cash
ledger PDF should render detail rows and a payment-method column.

That conflicts with this error log's owner decision:

- PDF is a readable report/ringkasan, not a detail table dump;
- detailed payment-method rows remain available in Excel and screen detail;
- PDF should keep payment-method signal only through summary labels such as
  `Tunai Masuk` and `Transfer Masuk`.

### PATCH

Patched:

- `tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php`
  - renamed the test to the new contract;
  - asserted PDF contains `Ringkasan Utama`, `Tunai Masuk`,
    `Transfer Masuk`, and `Detail lengkap tersedia di Excel`;
  - asserted PDF no longer contains `Metode Pembayaran`, `INV-001`, or
    `payment_component_allocations`.

No production query, controller, domain, payment/refund, inventory, or Excel
writer file was changed in this patch.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Unit\Application\Reporting\Exports\TransactionCashLedgerPdfBladePaymentMethodTest
PASS  Tests\Feature\ReportingExports\TransactionCashLedgerPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionCashLedgerExcelExportFeatureTest

Tests: 9 passed, 83 assertions
```

### FULL VERIFY PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
make verify
```

Result after the unit contract patch:

```text
[OK] No errors
Contract audit passed.
Tests: 1439 passed (8559 assertions)
```

Meaning:

- PHPStan passed;
- line-limit and Blade PHP/directive checks passed;
- contract audit passed;
- the full Pest suite passed with the new PDF report contract.

## 2026-06-27 RED And Patch Proof - Inventory Stock Value Slice

### FACT

The eighth vertical slice is `inventory_stock_value`.

RED tests added:

- `tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php`
  - `test_inventory_stock_value_pdf_view_uses_owner_readable_report_sections_not_detail_tables`
- `tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_inventory_stock_value_page`

Initial RED command:

```bash
php artisan test tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php
```

Initial RED result:

```text
Tests: 2 failed, 10 passed, 50 assertions
```

Failure meaning:

- inventory stock value PDF did not render `Ringkasan Utama`;
- inventory stock value screen did not render `Ringkasan Utama`.

Patched presentation files:

- `resources/views/admin/reporting/inventory_stock_value/export_pdf.blade.php`
  - removed summary grid, movement table, and snapshot table from PDF body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/inventory_stock_value/index.blade.php`
  - added matching report sections to the screen.
- `tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php`
  - updated PDF expectation so inventory snapshot/movement detail stays out of
    PDF and belongs to Excel/detail export.

No query, controller, domain, inventory write logic, costing logic, or Excel
writer file was changed for this slice.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\InventoryStockValueReportPdfExportFeatureTest
PASS  Tests\Feature\Reporting\InventoryStockValueReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\InventoryStockValueReportExcelExportFeatureTest

Tests: 16 passed, 110 assertions
```

Meaning:

- inventory stock value PDF still exports as `%PDF`;
- inventory stock value PDF now renders owner-readable sections and no longer
  renders movement/snapshot detail tables;
- inventory stock value screen now renders the same owner-readable sections;
- inventory stock value Excel export remains available, preserves detailed
  numeric data, and keeps formula-like product text safe as literal string.

### UI TIGHTENING PROOF

The inventory stock value screen residual was removed after the main PDF
contract was green.

Patched:

- `resources/views/admin/reporting/inventory_stock_value/index.blade.php`
  - removed table-shaped snapshot/movement blocks from the owner-facing page;
  - added `Rincian Ringkas` cards for stock position and movement totals;
  - kept item code, average cost, movement source, and row detail in Excel.
- `tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php`
  - stopped expecting table/detail-only item code and movement source rows on
    the screen report;
  - asserted the page still shows product names, stock value, movement totals,
    and owner-readable report sections;
  - asserted the old `Snapshot Stok Saat Ini` and `Ringkasan Mutasi Periode`
    tables are not rendered.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Reporting\InventoryStockValueReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\InventoryStockValueReportPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\InventoryStockValueReportExcelExportFeatureTest

Tests: 16 passed, 119 assertions
```

Meaning:

- inventory stock value screen now follows the same owner-readable direction as
  the PDF;
- inventory stock value PDF remains owner-readable;
- inventory stock value Excel remains the detailed export surface.

### NEXT

Run focused reporting export/page regression for all touched report families,
then decide whether to add service-package PDF support or leave it as no-PDF
route per current route map.

## 2026-06-27 Focused Regression Proof

### FACT

All active PDF report families in `routes/web/admin_reporting.php` have now been
converted to owner-readable PDF sections:

- `operational_profit`
- `transaction_cash_ledger`
- `transaction_summary`
- `supplier_payable`
- `employee_debt`
- `payroll`
- `operational_expense`
- `inventory_stock_value`

For each family:

- PDF keeps export route, PDF response, and PDF range guard behavior.
- PDF renders `Ringkasan Utama`, `Catatan Laporan`, and
  `Detail lengkap tersedia di Excel`.
- PDF no longer renders detailed row tables.
- Screen UI renders matching owner-readable sections.
- Screen UI for the active target families no longer renders the old detail
  tables under the report sections.
- Excel export tests remain green and preserve detail/numeric data.

### PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/OperationalProfitReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php tests/Feature/ReportingExports/OperationalProfitReportExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportPdfExportFeatureTest.php tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportExcelExportFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportPdfExportFeatureTest.php tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportExcelExportFeatureTest.php tests/Feature/ReportingExports/PayrollReportPdfExportFeatureTest.php tests/Feature/Reporting/PayrollReportPageFeatureTest.php tests/Feature/ReportingExports/PayrollReportExcelExportFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportExcelExportFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Result:

```text
Tests: 123 passed, 868 assertions
```

Meaning:

- focused regression for all touched report page/PDF/Excel export tests is
  green;
- Excel detail remains covered for every touched report family;
- screen report UI now follows the same owner-readable direction as PDF for
  `transaction_cash_ledger`, `transaction_summary`, `supplier_payable`,
  `employee_debt`, `payroll`, `operational_expense`, `inventory_stock_value`,
  and `operational_profit`;
- no query/domain/formula patch was needed for this presentation slice.

### RESIDUAL

- `service_package_profit_breakdown` has screen and Excel routes but no active
  PDF route in `routes/web/admin_reporting.php`; this issue did not add a new
  PDF route.

## 2026-06-27 Full Verify Legacy UI Contract Patch

### FACT

Full `make verify` after UI tightening passed static checks and most tests, but
found two remaining legacy UI contract tests:

- `tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php`
  still expected cash-ledger screen detail rows such as note id, source table,
  payment id, and refund id.
- `tests/Feature/Reporting/TaxLandedCostReportingFeatureTest.php` still
  expected inventory stock value screen detail such as average cost `Rp 11.000`
  and item code.

Those expectations conflict with the new screen/PDF contract: screen is now an
owner-readable report, while row-level detail remains in Excel/read-model tests.

### PATCH

Patched:

- `tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php`
  - screen assertion now verifies `Ringkasan Utama`, `Rincian Ringkas`,
    `Detail lengkap tersedia di Excel`, and total money;
  - screen assertion now verifies note/source/payment/refund ids do not render
    on the owner-facing report page;
  - Excel detail assertions remain unchanged and still prove refund-after-active
    revision appears in the detail sheet.
- `tests/Feature/Reporting/TaxLandedCostReportingFeatureTest.php`
  - screen assertion now verifies product name, stock value, and
    `Rincian Ringkas`;
  - screen assertion now verifies detail-only average cost and item code do not
    render on the owner-facing report page.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php tests/Feature/Reporting/TaxLandedCostReportingFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Note\TransactionCashLedgerAfterRevisionRefundFeatureTest
PASS  Tests\Feature\Reporting\TaxLandedCostReportingFeatureTest
PASS  Tests\Feature\Reporting\TransactionCashLedgerPageFeatureTest
PASS  Tests\Feature\Reporting\InventoryStockValueReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionCashLedgerExcelExportFeatureTest
PASS  Tests\Feature\ReportingExports\InventoryStockValueReportExcelExportFeatureTest

Tests: 33 passed, 293 assertions
```

### FULL VERIFY PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
make verify
```

Result:

```text
[OK] No errors
Contract audit passed.
Tests: 1439 passed (8594 assertions)
```

Meaning:

- PHPStan passed;
- line-limit and Blade PHP/directive checks passed;
- contract audit passed;
- the full Pest suite passed after the UI owner-readable contract and legacy
  test contract patches.

## 2026-06-27 RED And Patch Proof - Operational Expense Slice

### FACT

The seventh vertical slice is `operational_expense`.

RED tests added:

- `tests/Feature/ReportingExports/OperationalExpenseReportPdfExportFeatureTest.php`
  - `test_operational_expense_pdf_view_uses_owner_readable_report_sections_not_detail_tables`
- `tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_operational_expense_page`

Initial RED command:

```bash
php artisan test tests/Feature/ReportingExports/OperationalExpenseReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php
```

Initial RED result:

```text
Tests: 2 failed, 10 passed, 52 assertions
```

Failure meaning:

- operational expense PDF did not render `Ringkasan Utama`;
- operational expense screen did not render `Ringkasan Utama`.

Patched presentation files:

- `resources/views/admin/reporting/operational_expense/export_pdf.blade.php`
  - removed summary/detail tables from PDF body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/operational_expense/index.blade.php`
  - added matching report sections to the screen.
- `tests/Feature/ReportingExports/OperationalExpenseReportPdfExportFeatureTest.php`
  - updated PDF expectation so operational expense detail stays out of PDF and
    belongs to Excel/detail export.

No query, controller, domain, expense write logic, or Excel writer file was
changed for this slice.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/OperationalExpenseReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\OperationalExpenseReportPdfExportFeatureTest
PASS  Tests\Feature\Reporting\OperationalExpenseReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\OperationalExpenseReportExcelExportFeatureTest

Tests: 15 passed, 93 assertions
```

Meaning:

- operational expense PDF still exports as `%PDF`;
- operational expense PDF now renders owner-readable sections and no longer
  renders operational expense detail rows;
- operational expense screen now renders the same owner-readable sections;
- operational expense Excel export remains available and preserves detailed
  numeric data.

### UI TIGHTENING PROOF

The operational expense screen residual was removed after the main PDF contract
was green.

Patched:

- `resources/views/admin/reporting/operational_expense/index.blade.php`
  - removed table-shaped period/category/detail blocks from the owner-facing
    page;
  - added `Rincian Ringkas` cards for period totals and category totals;
  - kept descriptions, payment methods, references, and row detail in Excel.
- `tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php`
  - stopped expecting expense description detail rows on the screen report;
  - asserted the page still shows period, category, total expense, and average
    daily expense;
  - asserted the old `Detail Biaya Operasional` table is not rendered.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportPdfExportFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Reporting\OperationalExpenseReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\OperationalExpenseReportPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\OperationalExpenseReportExcelExportFeatureTest

Tests: 15 passed, 98 assertions
```

Meaning:

- operational expense screen now follows the same owner-readable direction as
  the PDF;
- operational expense PDF remains owner-readable;
- operational expense Excel remains the detailed export surface.

### NEXT

Continue with the next report family using the same RED -> patch -> GREEN ->
log-update sequence.

## 2026-06-27 RED And Patch Proof - Payroll Slice

### FACT

The sixth vertical slice is `payroll`.

RED tests added:

- `tests/Feature/ReportingExports/PayrollReportPdfExportFeatureTest.php`
  - `test_payroll_pdf_view_uses_owner_readable_report_sections_not_detail_tables`
- `tests/Feature/Reporting/PayrollReportPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_payroll_page`

Initial RED command:

```bash
php artisan test tests/Feature/ReportingExports/PayrollReportPdfExportFeatureTest.php tests/Feature/Reporting/PayrollReportPageFeatureTest.php
```

Initial RED result:

```text
Tests: 2 failed, 10 passed, 51 assertions
```

Failure meaning:

- payroll PDF did not render `Ringkasan Utama`;
- payroll screen did not render `Ringkasan Utama`.

Patched presentation files:

- `resources/views/admin/reporting/payroll/export_pdf.blade.php`
  - removed summary/period/mode/detail tables from PDF body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/payroll/index.blade.php`
  - added matching report sections to the screen.
- `tests/Feature/ReportingExports/PayrollReportPdfExportFeatureTest.php`
  - updated PDF expectation so payroll detail stays out of PDF and belongs to
    Excel/detail export.

No query, controller, domain, employee finance, payroll write logic, or Excel
writer file was changed for this slice.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/PayrollReportPdfExportFeatureTest.php tests/Feature/Reporting/PayrollReportPageFeatureTest.php tests/Feature/ReportingExports/PayrollReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\PayrollReportPdfExportFeatureTest
PASS  Tests\Feature\Reporting\PayrollReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\PayrollReportExcelExportFeatureTest

Tests: 15 passed, 91 assertions
```

Meaning:

- payroll PDF still exports as `%PDF`;
- payroll PDF now renders owner-readable sections and no longer renders payroll
  detail rows;
- payroll screen now renders the same owner-readable sections;
- payroll Excel export remains available and preserves detailed numeric data.

### UI TIGHTENING PROOF

The payroll screen residual was removed after the main PDF contract was green.

Patched:

- `resources/views/admin/reporting/payroll/index.blade.php`
  - removed table-shaped period/mode/detail blocks from the owner-facing page;
  - added `Rincian Ringkas` cards for period totals and mode totals;
  - kept employee names, payroll notes, and row detail in Excel.
- `tests/Feature/Reporting/PayrollReportPageFeatureTest.php`
  - stopped expecting employee detail names on the screen report;
  - asserted the page still shows period, total, mode, and owner-readable
    report sections;
  - asserted the old `Detail Pencairan Gaji` table is not rendered.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/PayrollReportPageFeatureTest.php tests/Feature/ReportingExports/PayrollReportPdfExportFeatureTest.php tests/Feature/ReportingExports/PayrollReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Reporting\PayrollReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\PayrollReportPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\PayrollReportExcelExportFeatureTest

Tests: 15 passed, 96 assertions
```

Meaning:

- payroll screen now follows the same owner-readable direction as the PDF;
- payroll PDF remains owner-readable;
- payroll Excel remains the detailed export surface.

### NEXT

Continue with the next report family using the same RED -> patch -> GREEN ->
log-update sequence.

## 2026-06-26 RED And Patch Proof - Employee Debt Slice

### FACT

The fifth vertical slice is `employee_debt`.

RED tests added:

- `tests/Feature/ReportingExports/EmployeeDebtReportPdfExportFeatureTest.php`
  - `test_employee_debt_pdf_view_uses_owner_readable_report_sections_not_detail_tables`
- `tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_employee_debt_page`

Initial RED command:

```bash
php artisan test tests/Feature/ReportingExports/EmployeeDebtReportPdfExportFeatureTest.php tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php
```

Initial RED result:

```text
Tests: 2 failed, 10 passed, 47 assertions
```

Failure meaning:

- employee debt PDF did not render `Ringkasan Utama`;
- employee debt screen did not render `Ringkasan Utama`.

Patched presentation files:

- `resources/views/admin/reporting/employee_debt/export_pdf.blade.php`
  - removed summary/period/status/detail tables from PDF body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/employee_debt/index.blade.php`
  - added matching report sections to the screen.
- `tests/Feature/ReportingExports/EmployeeDebtReportPdfExportFeatureTest.php`
  - updated PDF expectation so debt detail stays out of PDF and belongs to
    Excel/detail export.

No query, controller, domain, employee finance, payroll, payment, or Excel writer
file was changed for this slice.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/EmployeeDebtReportPdfExportFeatureTest.php tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\EmployeeDebtReportPdfExportFeatureTest
PASS  Tests\Feature\Reporting\EmployeeDebtReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\EmployeeDebtReportExcelExportFeatureTest

Tests: 15 passed, 93 assertions
```

Meaning:

- employee debt PDF still exports as `%PDF`;
- employee debt PDF now renders owner-readable sections and no longer renders
  debt detail rows;
- employee debt screen now renders the same owner-readable sections;
- employee debt Excel export remains available and preserves detailed numeric
  data.

### UI TIGHTENING PROOF

The employee debt screen residual was removed after the main PDF contract was
green.

Patched:

- `resources/views/admin/reporting/employee_debt/index.blade.php`
  - removed table-shaped period/status/detail blocks from the owner-facing
    page;
  - added `Rincian Ringkas` cards for period totals and status totals;
  - kept debt references, employee IDs, and row detail in Excel.
- `tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php`
  - stopped expecting debt id detail rows on the screen report;
  - asserted the page still shows period, total debt, paid amount, remaining
    debt, and status summary;
  - asserted the old `Detail Hutang` table is not rendered.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportPdfExportFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Reporting\EmployeeDebtReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\EmployeeDebtReportPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\EmployeeDebtReportExcelExportFeatureTest

Tests: 15 passed, 97 assertions
```

Meaning:

- employee debt screen now follows the same owner-readable direction as the
  PDF;
- employee debt PDF remains owner-readable;
- employee debt Excel remains the detailed export surface.

### NEXT

Continue with the next report family using the same RED -> patch -> GREEN ->
log-update sequence.

## 2026-06-26 RED And Patch Proof - Supplier Payable Slice

### FACT

The fourth vertical slice is `supplier_payable`.

RED tests added:

- `tests/Feature/ReportingExports/SupplierPayableReportPdfExportFeatureTest.php`
  - `test_supplier_payable_pdf_view_uses_owner_readable_report_sections_not_detail_tables`
- `tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_supplier_payable_page`

Initial RED command:

```bash
php artisan test tests/Feature/ReportingExports/SupplierPayableReportPdfExportFeatureTest.php tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php
```

Initial RED result:

```text
Tests: 2 failed, 10 passed, 55 assertions
```

Failure meaning:

- supplier payable PDF did not render `Ringkasan Utama`;
- supplier payable screen did not render `Ringkasan Utama`.

Patched presentation files:

- `resources/views/admin/reporting/supplier_payable/export_pdf.blade.php`
  - removed summary/period/supplier/detail tables from PDF body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/supplier_payable/index.blade.php`
  - added matching report sections to the screen.
- `tests/Feature/ReportingExports/SupplierPayableReportPdfExportFeatureTest.php`
  - updated PDF expectation so invoice detail stays out of PDF and belongs to
    Excel/detail export.

No query, controller, domain, procurement, payment, inventory, or Excel writer
file was changed for this slice.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/SupplierPayableReportPdfExportFeatureTest.php tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\SupplierPayableReportPdfExportFeatureTest
PASS  Tests\Feature\Reporting\SupplierPayableReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\SupplierPayableReportExcelExportFeatureTest

Tests: 16 passed, 114 assertions
```

Meaning:

- supplier payable PDF still exports as `%PDF`;
- supplier payable PDF now renders owner-readable sections and no longer renders
  invoice detail rows;
- supplier payable screen now renders the same owner-readable sections;
- supplier payable Excel export remains available, preserves detailed numeric
  data, and keeps formula-like text safe as literal string.

### UI TIGHTENING PROOF

The supplier payable screen residual was removed after the main PDF contract was
green.

Patched:

- `resources/views/admin/reporting/supplier_payable/index.blade.php`
  - removed table-shaped period/supplier/detail invoice blocks from the
    owner-facing page;
  - added `Rincian Ringkas` cards for period totals and supplier totals;
  - kept invoice numbers, due dates per invoice, payments, and row detail in
    Excel.
- `tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php`
  - stopped expecting invoice id detail rows on the screen report;
  - asserted the page still shows totals, due status buckets, supplier payable
    warning text, and owner-readable report sections;
  - asserted the old `Detail Hutang Pemasok` table is not rendered.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportPdfExportFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Reporting\SupplierPayableReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\SupplierPayableReportPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\SupplierPayableReportExcelExportFeatureTest

Tests: 16 passed, 120 assertions
```

Meaning:

- supplier payable screen now follows the same owner-readable direction as the
  PDF;
- supplier payable PDF remains owner-readable;
- supplier payable Excel remains the detailed export surface.

### NEXT

Continue with the next report family using the same RED -> patch -> GREEN ->
log-update sequence.

## 2026-06-26 RED And Patch Proof - Transaction Summary Slice

### FACT

The third vertical slice is `transaction_summary`.

RED tests added:

- `tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php`
  - `test_pdf_view_uses_owner_readable_report_sections_not_detail_tables`
- `tests/Feature/Reporting/TransactionReportPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_transaction_report_page`

Initial RED command:

```bash
php artisan test tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php
```

Initial RED result:

```text
Tests: 2 failed, 10 passed, 64 assertions
```

Failure meaning:

- transaction summary PDF did not render `Ringkasan Utama`;
- transaction summary screen did not render `Ringkasan Utama`.

Patched presentation files:

- `resources/views/admin/reporting/transaction_summary/export_pdf.blade.php`
  - removed summary/detail tables from PDF body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/transaction_summary/index.blade.php`
  - added matching report sections to the screen.
- `tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php`
  - updated PDF expectation so per-note status detail stays out of PDF and
    belongs to Excel/detail export.

No query, controller, domain, payment/refund, inventory, or Excel writer file was
changed for this slice.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\TransactionReportPdfExportFeatureTest
PASS  Tests\Feature\Reporting\TransactionReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionReportExcelExportFeatureTest

Tests: 15 passed, 112 assertions
```

Meaning:

- transaction summary PDF still exports as `%PDF`;
- transaction summary PDF now renders owner-readable sections and no longer
  renders per-note detail rows;
- transaction summary screen now renders the same owner-readable sections;
- transaction summary Excel export remains available and preserves detailed
  numeric data.

### UI TIGHTENING PROOF

The transaction summary screen residual was removed after the main PDF contract
was green.

Patched:

- `resources/views/admin/reporting/transaction_summary/index.blade.php`
  - removed table-shaped period/customer/detail note blocks from the
    owner-facing page;
  - added `Rincian Ringkas` cards for period totals and customer totals;
  - kept note id, per-note status, and row detail in Excel.
- `tests/Feature/Reporting/TransactionReportPageFeatureTest.php`
  - stopped expecting note id detail rows on the screen report;
  - asserted the page still shows transaction totals, customer summary, refund
    due summary, and owner-readable report sections;
  - asserted the old `Detail Per Nota` table is not rendered.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Reporting\TransactionReportPageFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionReportPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionReportExcelExportFeatureTest

Tests: 15 passed, 117 assertions
```

Meaning:

- transaction summary screen now follows the same owner-readable direction as
  the PDF;
- transaction summary PDF remains owner-readable;
- transaction summary Excel remains the detailed export surface.

### NEXT

Continue with the next report family using the same RED -> patch -> GREEN ->
log-update sequence.

## 2026-06-26 RED And Patch Proof - Transaction Cash Ledger Slice

### FACT

The second vertical slice is `transaction_cash_ledger`.

RED tests added:

- `tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php`
  - `test_transaction_cash_ledger_pdf_view_uses_owner_readable_report_sections_not_detail_tables`
- `tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php`
  - `test_admin_sees_owner_readable_report_sections_on_transaction_cash_ledger_page`

Initial RED command:

```bash
php artisan test tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php
```

Initial RED result:

```text
Tests: 2 failed, 16 passed, 106 assertions
```

Failure meaning:

- transaction cash ledger PDF did not render `Ringkasan Utama`;
- transaction cash ledger screen did not render `Ringkasan Utama`.

Patched presentation files:

- `resources/views/admin/reporting/transaction_cash_ledger/export_pdf.blade.php`
  - removed summary/detail tables from PDF body;
  - added `Ringkasan Utama`;
  - added `Catatan Laporan`;
  - added `Detail lengkap tersedia di Excel`.
- `resources/views/admin/reporting/transaction_cash_ledger/index.blade.php`
  - added matching report sections to the screen.
- `tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php`
  - updated PDF expectation so source-table detail stays out of PDF and belongs
    to Excel/detail export.

No query, controller, domain, payment/refund, inventory, or Excel writer file was
changed for this slice.

### GREEN PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\ReportingExports\TransactionCashLedgerPdfExportFeatureTest
PASS  Tests\Feature\Reporting\TransactionCashLedgerPageFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionCashLedgerExcelExportFeatureTest

Tests: 21 passed, 162 assertions
```

Meaning:

- transaction cash ledger PDF still exports as `%PDF`;
- transaction cash ledger PDF now renders owner-readable sections and no longer
  renders source-table detail rows;
- transaction cash ledger screen now renders the same owner-readable sections;
- transaction cash ledger Excel export remains available and preserves detailed
  numeric data.

### UI TIGHTENING PROOF

The transaction cash ledger screen residual was removed after the main PDF
contract was green.

Patched:

- `resources/views/admin/reporting/transaction_cash_ledger/index.blade.php`
  - removed the screen detail event table from the owner-facing report page;
  - removed the table-shaped period aggregation block;
  - added `Rincian Ringkas` card/list output for period-level cash movement;
  - kept the clear Excel note for full event/source/payment-method detail.
- `tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php`
  - stopped expecting note id/source id/detail event rows on the screen report;
  - asserted the screen keeps `Tunai Masuk` and `Transfer Masuk` summary;
  - asserted the screen does not render `Metode Pembayaran` detail table rows.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php
```

Result:

```text
PASS  Tests\Feature\Reporting\TransactionCashLedgerPageFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionCashLedgerPdfExportFeatureTest
PASS  Tests\Feature\ReportingExports\TransactionCashLedgerExcelExportFeatureTest

Tests: 21 passed, 160 assertions
```

Meaning:

- cash ledger screen now follows the same owner-readable direction as the PDF;
- cash ledger PDF remains owner-readable;
- cash ledger Excel remains the detailed export surface.

### NEXT

Continue UI-only tightening for the remaining report screens that still keep
detail tables below the owner-readable sections.

## 2026-06-27 Owner Follow-up - Remove Remaining UI Aggregate Loops

### FACT

After the production-scale follow-up, a residual scan still found report screen
loops that could grow with period, supplier, payroll mode, employee-debt status,
or operational-expense category data.

Residual UI patterns found:

- `supplier_payable/index.blade.php`: `periodRows` and `supplierRows`;
- `payroll/index.blade.php`: `periodRows` and `modeRows`;
- `employee_debt/index.blade.php`: `periodRows` and `statusRows`;
- `operational_expense/index.blade.php`: `periodRows` and `categoryRows`;
- several report includes still passed `basisDateNote` / `noteText` strings.

### ANALYSIS

These rows were aggregate rows, not raw Excel detail rows, but they still create
an unbounded screen surface. That does not match the owner direction: PDF and UI
should stay as readable reports, while Excel keeps detailed breakdowns.

### PATCH

Patched report screens:

- removed remaining `basisDateNote` and `noteText` arguments from active report
  screen includes;
- replaced remaining per-period/per-supplier/per-mode/per-status/per-category
  UI loops with fixed summary cards sourced from existing `summary` data;
- updated page tests so owner-facing screens assert summary labels and do not
  assert detail-like names/status rows;
- kept Excel export tests unchanged and passing to prove detailed rows remain
  available in Excel.

### PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
rg -n 'basisDateNote|noteText|Catatan Laporan|Detail lengkap tersedia di Excel|Gunakan ringkasan|Halaman ini|Laporan ini merangkum|Laporan ini menunjukkan|tersedia di Excel|<table|@forelse' resources/views/admin/reporting/*/index.blade.php resources/views/admin/reporting/*/export_pdf.blade.php resources/views/admin/reporting/partials/period_filter.blade.php
```

Result:

```text
no matches
```

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php tests/Feature/Reporting/PayrollReportPageFeatureTest.php tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/Reporting/ServicePackageProfitBreakdownReportPageFeatureTest.php tests/Feature/Reporting/ServicePackageProfitBreakdownUiScenarioMatrixFeatureTest.php tests/Feature/Reporting/ServicePackageProfitBreakdownHttpWorkflowFeatureTest.php tests/Feature/Reporting/TaxLandedCostReportingFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportExcelExportFeatureTest.php tests/Feature/ReportingExports/PayrollReportExcelExportFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportExcelExportFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportExcelExportFeatureTest.php tests/Feature/ReportingExports/ServicePackageProfitBreakdownExcelExportFeatureTest.php
```

Result:

```text
Tests: 94 passed, 821 assertions
```

### NEXT

Full verification after this residual UI cleanup is complete.

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
make verify
```

Result:

```text
PHPStan: [OK] No errors
Line limit: SUCCESS
Blade PHP/directive check: SUCCESS
Contract audit: passed
Tests: 1439 passed, 8600 assertions
```

### STATUS

`0046` remains `Full Verified`.
