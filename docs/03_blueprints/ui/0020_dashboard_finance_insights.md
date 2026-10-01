# Dashboard finance insights

## Scope and baseline

Issue #57. Owner requests payroll, employee debt visibility, largest operational expenses, varied charts, precise values, human-readable labels, issue/PR and main merge. Existing dashboard already shows current all-time employee debt; payroll and category breakdown are absent. Extend blueprint 0018 with this financial overview. Existing transaction sections and handset supplier hub stay unchanged.

## Design and boundaries

Reuse reconciled operational expense and payroll report datasets. Payroll means actual disbursements by disbursement date, excluding reversals through period end, not salary owed. Expenses use expense date and exclude entries deleted through period end. Current employee debt remains outside the monthly cache and explicitly labeled current. No domain writes, new dependencies, or permission changes.

Add a payroll card with total, count, and up to five recipients grouped by employee identity but displayed by name. Show five largest expense categories as horizontal bars with exact amounts and a full-report link. A donut compares only operational expenses and paid salaries, explicitly excluding inventory costs, refunds and cash advances. Keep exact server-rendered amounts accessible without charts; empty datasets show a clear message and charts appear automatically when data exists. Retain the existing daily line chart. Remove visible product codes from restock and bestselling tables; internal identifiers remain in navigation only.

Overview payload gains finance_insights; existing fields remain unchanged. Version the overview cache key so old cache entries cannot hide new fields. Reuse the already loaded expense result to avoid a duplicate query.

## Validation

Check populated and empty states, selected months, deleted expenses, reversed salaries, same-name distinct employees, category ranking, exact totals, and HTML escaping. Run dashboard, reporting and export regression plus formatting, static analysis and browser checks in light/dark responsive layouts. Review diff, publish PR, inspect checks and merge main after validation.

## Validation results

Executed locally against the testing database, 2026-09-30:

- Dashboard page/handset/month filter, Reporting and ReportingExports: 267 passed, 3 failed, 2646 assertions. All 13 dashboard tests pass, including the new finance integration scenario.
- Three existing failures reproduced on baseline 6b030003 in a detached worktree: EmployeeDebtTemporalIntegrityFeatureTest, InventoryTemporalIntegrityFeatureTest and ServicePackageProfitBreakdownUiScenarioMatrixFeatureTest compare array rows with newly paginated rows. They do not originate from this dashboard patch. Baseline run: 6 passed, 3 failed.
- PHPStan: no errors. Pint, JavaScript syntax, diff whitespace, line-count and Blade PHP-boundary checks pass.
- Global hexagonal audit still reports existing Illuminate DB imports in BulkProductMaintenanceRunner and BulkProductMaintenanceValidator, verified in baseline source. No new architecture violations added.
- Chromium: actual server-rendered finance partial extracted from the feature response, synthetic fixtures, production CSS/ApexCharts/finance script. Two charts render at 1440 light/dark and 768 dark; no document overflow. Screenshots inspected: [light](evidence/dashboard_finance_insights/light.png), [dark](evidence/dashboard_finance_insights/dark.png), [narrow](evidence/dashboard_finance_insights/narrow.png). This is fragment-level browser proof, not a production deployment or full-page end-to-end test.
- Regression covers deleted/reversed rows through period end, later corrections preserving historical totals, month filtering, current kasbon in empty historical periods, exact rupiah, distinct same-name employees, escaped category names and absent visible product codes.

## Delivery boundary

Changes target the existing desktop Ringkasan Toko. The handset supplier hub is unchanged. New report data follows the existing short-lived overview cache; current kasbon remains fetched outside it. Charts appear on a subsequent page load once source data exists. Existing transaction presentation and daily line chart remain intact. No full repository green or production deployment claim.
