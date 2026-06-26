# 0046 PDF Report Table Layout Owner Readability Gap

Status: Characterized RED

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
