# Admin dashboard redesign proof

## Scope and baseline

Campaign: [Issue 26](https://github.com/Asyraf2003/GlassPos/issues/26).
Baseline: 7723dea9d06bc2c58d77405e1dff8df8657542b4, main and origin/main matched after fetch.
See [data inventory and execution map](../../0018_admin_dashboard_operational_redesign.md).

Only the desktop dashboard presentation, its assets/partials, and focused tests changed. No controller, reporting query, response envelope, route, permission, handset view, or global shell file changed. Tabler was used as a reference; no runtime dependency was installed.

## Automated proof

Commands run from repository root:

- `php artisan test tests/Feature/Admin/AdminDashboardPageFeatureTest.php tests/Feature/Admin/AdminDashboardHandsetFeatureTest.php`: 10 passed, 126 assertions.
- `php artisan test tests/Feature/Reporting tests/Feature/ReportingExports tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php`: 223 passed, 2216 assertions.
- `vendor/bin/phpstan analyze --memory-limit=-1`: 2171 files, no errors.
- `php scripts/audit-line-count.php`: pass.
- `node --check public/assets/static/js/admin/dashboard-analytics.js` and `node --check public/assets/static/js/admin/dashboard-drawer.js`: pass.
- `git diff --check`: pass.
- Dashboard Blade scan: no inline PHP directives/tags.

`make audit-contract` fails at the global Blade audit on `resources/views/shared/notes/partials/status-badge.blade.php:1,16`. Those `@php`/`@endphp` directives exist at the baseline commit, verified with `git show`; this campaign leaves that unrelated file untouched. This is not a full-repository green claim. Full suite was not run; regression is scoped to the dashboard and all reporting/export feature tests.

## Browser setup and coverage

Real Laravel routes and ApexCharts rendered in headless Chromium using Playwright installed outside the repo. A temporary server on localhost:8096 authenticates an isolated fixture admin and uses `glasspos_dashboard_ui_proof`, a separate migrated database. The temporary authentication router is outside the repository and is not shipped. Browser fixtures start from AdminDashboardPageFeatureTest, adding one stock reversal and Rp 37,000 cash change. Frozen date: 9 January 2030.

Screenshots contain synthetic fixture data, not production data. Automation and full logs are in `/tmp/glasspos-dashboard-proof` on the working machine. The portable result summaries and representative screenshots are committed here.

| Check | Result |
|---|---|
| 1366 / 1920 light and dark | Pass; screenshots inspected |
| 1024 / 768 light and dark | Pass; no document/KPI overflow; tables fit |
| Four KPI values and section hierarchy | Pass; section headings remain 16px in both themes |
| Theme toggle, axis/grid/legend | Pass; charts re-render with theme tokens |
| Tooltip date and exact Rupiah | Pass, light/dark |
| Month submit and reset | Pass; analytics query and export reference_date follow applied month |
| Export/report links | All 24 shortcuts return HTTP 200 in fixture environment |
| Restock/top product links | HTTP 200; reversal reduces top-product qty and value |
| Filter drawer | Open/close, Escape, focus return, keyboard focus cycle implemented |
| No selected-period transactions | Pass; current stock and today's receipts remain explicitly separate |
| No active inventory | Pass; stock counts zero, no restock rows, empty products/chart/denominations |
| Refund >= period cash in | Pass; Rp -89,000 event net flow and truthful warning, no claim that all notes refunded |
| Long product labels and extreme currency | DOM-only stress probe; no document/KPI overflow, values wrap without truncation |
| Analytics request 503 | Explicit failure state; unavailable totals are not presented as zero |
| Handset | Mobile client hint renders supplier hub, no desktop dashboard container |
| Browser JavaScript errors | None in completed main matrix |

Handset proof is browser emulation plus feature tests, not a physical-device test. Export proof covers response success plus existing PDF/Excel feature tests; it does not claim a manual inspection of every exported workbook/PDF.

## Visual evidence

- [Laptop light](1366-light.png), [laptop dark](1366-dark.png)
- [Wide light](1920-light.png), [wide dark](1920-dark.png)
- [Narrow desktop/tablet dark](768-dark.png)
- [Drawer dark](drawer-dark.png)
- [Empty period and stock](empty-dark.png)
- [Refund/reversal audit](refund-dark.png)
- [Tooltip dark](tooltip-dark.png)
- [Handset supplier hub](handset.png)
- [Layout/interaction results](browser-results.json), [refund/tooltip results](adverse-results.json)

## Final code review

- Canonical KPI and financial values retain original source keys. Duplicate aliases are presented once; distinct cohort/event metrics remain distinct.
- Current inventory is explicitly labelled current even for historical months. No historical snapshot is invented from analytics snapshot_date.
- Operational analytics retains the existing endpoint and four series; an audit note explains its existing external-return difference from the overview report.
- Product names render through escaped Blade; generated analytics cells use textContent.
- CSS is scoped to the desktop dashboard and its heading; light/dark share tokens. Existing dark Bootstrap specificity was caught by browser inspection and corrected within this scope.
- Old gradients, profile card, nested finance cards, redundant charts, and inline dashboard CSS are removed.
- Review performed by the implementing agent; no independent reviewer approval is claimed.
