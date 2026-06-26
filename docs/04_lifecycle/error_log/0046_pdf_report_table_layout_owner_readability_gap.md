# 0046 PDF Report Table Layout Owner Readability Gap

Status: Targeted Verified

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

### RESIDUAL

The inventory stock value screen still keeps the existing detail tables below
the new owner-readable sections because existing UI tests currently cover those
tables. A later UI-only tightening step may move or remove screen detail tables
after each report family has the summary/PDF contract in place.

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
- Excel export tests remain green and preserve detail/numeric data.

### PROOF

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/ReportingExports/OperationalProfitReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php tests/Feature/ReportingExports/OperationalProfitReportExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportPdfExportFeatureTest.php tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php tests/Feature/ReportingExports/SupplierPayableReportExcelExportFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportPdfExportFeatureTest.php tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php tests/Feature/ReportingExports/EmployeeDebtReportExcelExportFeatureTest.php tests/Feature/ReportingExports/PayrollReportPdfExportFeatureTest.php tests/Feature/Reporting/PayrollReportPageFeatureTest.php tests/Feature/ReportingExports/PayrollReportExcelExportFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportPdfExportFeatureTest.php tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php tests/Feature/ReportingExports/OperationalExpenseReportExcelExportFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

Result:

```text
Tests: 123 passed, 836 assertions
```

Meaning:

- focused regression for all touched report page/PDF/Excel export tests is
  green;
- Excel detail remains covered for every touched report family;
- no query/domain/formula patch was needed for this presentation slice.

### RESIDUAL

- Screen pages still keep existing detail tables below the owner-readable
  sections because existing UI tests cover those detail tables.
- `service_package_profit_breakdown` has screen and Excel routes but no active
  PDF route in `routes/web/admin_reporting.php`; this issue did not add a new
  PDF route.
- Full `make verify` has not been run after these presentation changes.

### NEXT

Either:

- run full `make verify`; or
- continue with a separate UI tightening slice to move/remove screen detail
  tables after the owner-readable sections are proven across all report
  families.

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

### RESIDUAL

The operational expense screen still keeps the existing detail tables below the
new owner-readable sections because existing UI tests currently cover those
tables. A later UI-only tightening step may move or remove screen detail tables
after each report family has the summary/PDF contract in place.

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

### RESIDUAL

The payroll screen still keeps the existing detail tables below the new
owner-readable sections because existing UI tests currently cover those tables.
A later UI-only tightening step may move or remove screen detail tables after
each report family has the summary/PDF contract in place.

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

### RESIDUAL

The employee debt screen still keeps the existing detail tables below the new
owner-readable sections because existing UI tests currently cover those tables.
A later UI-only tightening step may move or remove screen detail tables after
each report family has the summary/PDF contract in place.

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

### RESIDUAL

The supplier payable screen still keeps the existing detail tables below the new
owner-readable sections because existing UI tests currently cover those tables.
A later UI-only tightening step may move or remove screen detail tables after
each report family has the summary/PDF contract in place.

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

### RESIDUAL

The transaction summary screen still keeps the existing detail tables below the
new owner-readable sections because existing UI tests currently cover those
tables. A later UI-only tightening step may move or remove screen detail tables
after each report family has the summary/PDF contract in place.

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

### RESIDUAL

The transaction cash ledger screen still keeps the existing detail table below
the new owner-readable report sections because existing UI tests currently cover
that detail table. A later UI-only tightening step may move or remove screen
detail tables after each report family has the summary/PDF contract in place.

### NEXT

Continue with the next report family using the same RED -> patch -> GREEN ->
log-update sequence.
