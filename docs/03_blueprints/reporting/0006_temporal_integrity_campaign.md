# Reporting temporal integrity campaign

Status: ACTIVE, not verified or complete.
Date: 2026-09-29
Issue: https://github.com/Asyraf2003/GlassPos/issues/50
Branch: fix/reporting-temporal-integrity

## Authority and scope

Owner campaign contract supersedes the earlier one-report-per-session workflow. ADR-0009 keeps reporting read-only; ADR-0011 requires exact integer rupiah; ADR-0043 supplies Asia/Makassar business dates. Existing supplier current-position contract (0005, handoff 0037) remains authoritative for current dashboard balances. Historical report cutoffs must not be described as current balances.

No domain writes, production deployment, primitive redesign or unrelated concurrency-test repair. Preserve untracked local environment backup. Use isolated database glasspos_reporting_integrity_20260929 on local MariaDB for tests.

## Blueprint

Target: classify every report and dashboard metric as period flow, as-of/current position, or explicitly cumulative; carry forward balances; share application datasets across screen/PDF/Excel.

Current evidence: employee debt filters debt creation dates, sums unbounded active payments, and reads mutable remaining_balance; inventory report reads current snapshots regardless of date_to; supplier explicit filters are shipment cohorts with current payments; service/package has Excel but no PDF route.

Dependencies: domain timestamps, adjustment/reversal histories, inventory movement costs, invoice/note version histories, existing report filter validation and export adapters. Risks: silently restating historical periods, confusing invoice cohorts with store liabilities, replacing costing policy, counting refunds twice, and broadening flow totals to all-time.

Sequence: audit and source map; cross-month RED regressions; correct unambiguous read-side semantics and labels; export parity; adjacent regression and full gates; handoff and PR. A business ambiguity blocks only its affected metric. Historical restatement of later corrections is awaiting owner clarification; do not invent that policy.

## Initial surface matrix

Every row below is a metric family; detailed field coverage and runtime proof remain required. PASS means only an established source contract until test evidence is attached.

| Report / metric | Domain source | Current filtering / semantics | Required class / behavior | Screen / PDF / Excel source | Coverage / status / action |
|---|---|---|---|---|---|
| Transaction cash ledger: in, out, net, cash/transfer, payment/refund details | customer_payments, allocations, customer_refunds, surplus refund payments | Event date range | FLOW, event date range even for old notes | GetTransactionCashLedgerPerNoteHandler, all three | Existing ledger/export tests; audit boundary and cross-month regression |
| Payroll totals, modes, daily rows | payroll_disbursements + reversals | Disbursement date, currently active records | FLOW, preserve period; later reversal policy requires clarification | GetPayrollReportDatasetHandler, all three | Existing payroll/export tests; historical reversal GAP |
| Employee debt principal, paid, remaining, statuses | employee_debts, adjustments, payments, reversals | Created-in-period cohort + all-time active payments + current balance | FLOW new/payments/adjustments; AS-OF opening/closing carrying older debts | GetEmployeeDebtReportDatasetHandler, all three | BUG carry-forward and future payments; add temporal reconciliation |
| Operational profit: cash in/refund, expense, payroll, COGS, external cost | cash events, expense/payroll, stock movements, external lines | Period dates; employee debt uses mutable principal at original creation | FLOW; retain accepted formula, date new debt increments correctly | GetOperationalProfitSummaryHandler, all three | BUG debt principal timing; later correction policy GAP; sold-stock correction excluded by ADR-0037 |
| Service/package: sales, parts, COGS, margin, service share, refunds | work_items + details, movements, refund components | Transaction-date cohort with current detail and unbounded reversals | FLOW for sales; correction/restatement contract must be explicit | GetServicePackageProfitBreakdownHandler; screen/Excel, PDF absent | PDF GAP; historical detail semantics require review |
| Operational expense: total/category/daily/detail | operational_expenses | expense_date within period, excludes deleted | FLOW; retain period scope | GetOperationalExpenseReportDatasetHandler, all three | Existing tests; later deletion/restatement GAP |
| Supplier invoices/payments/remaining/due status | supplier_invoices + versions, payments + reversals | All/current by default; explicit shipment cohort with current balances | AS-OF closing for date_to; current all default; due date urgency only | GetSupplierPayableReportDatasetHandler, all three | Current carry-forward already covered; explicit historical position BUG/GAP |
| Inventory quantity/value/average/residual/diagnostics | inventory_movements, product_inventory, product_inventory_costing | Current snapshot even for historical report | AS-OF date_to with prior movements; current dashboard current position | GetInventoryStockValueReportDatasetHandler; full Excel vs summary-only screen/PDF | BUG historical cutoff; preserve deleted/orphan movement visibility and exact costing |
| Inventory in/out/reversal/correction/net qty/cost | inventory_movements | tanggal_mutasi range | FLOW, retain period range | Same inventory dataset | Existing reconciliation tests; boundary regression |
| Transaction summary: gross/count/customer/daily | notes + current financial projections | Transaction cohort with current state | FLOW cohort totals; distinguish current cohort settlement from event-period cash | GetTransactionReportDatasetHandler, all three | Historical revision/cancellation policy GAP |
| Transaction summary: paid/refund/refund-due/outstanding | payments/refunds, settlements and dispositions | Lifetime/current amounts restricted to note cohort | Explicit CUMULATIVE cohort history or AS-OF position; never global store position | Same transaction dataset | BUG/GAP ambiguous temporal labels; preserve primitive contract |
| Dashboard employee debt remaining | employee debt report | Month creation cohort and cached result | CURRENT position independent of selected activity month | AdminDashboardOverviewPayloadBuilder | BUG; dedicated current read outside period cache |
| Dashboard customer outstanding | transaction report | Selected-month note cohort | CURRENT position across all active notes | Same dashboard builder | BUG; current projection read, no month cohort |
| Dashboard supplier outstanding | CurrentSupplierOutstandingReaderPort | All active non-void invoices, outside month cache | CURRENT | GetAdminDashboardOverviewHandler | Established PASS contract, preserve |
| Dashboard stock/value/status/restock | inventory projections | Current snapshot, some month-keyed caching | CURRENT, explicit labels; activity separately FLOW | DashboardInventoryOverviewReaderPort | Audit cache/read-after-write and label parity |
| Dashboard cash/activity/expense/profit/top selling/charts | reporting readers and per-day queries | Selected event/transaction period | FLOW | Dashboard overview/analytics handlers | Preserve period; compare dashboard with report sources |
| Dashboard export shortcuts | report routes | Links to report exports | Same filters and semantics as target report | AdminDashboardReportExportShortcuts | No consolidated export route discovered; blueprint expects dashboard exports, scope GAP |

## Validation contract

Cross-month A-J matrix includes the canonical owner example: 1000000 minus 200000 = 800000, September payment 300000 leaves 500000. Test integer values directly. Include empty period, paid prior, reversals, new records, boundaries, future events, historical/current comparison and exact 1 rupiah.

Initial baseline before implementation: user's preceding suite output is 1804 passed / 1 concurrency failure; it is baseline evidence only, not proof of current reporting changes. Required final gates and output remain open.

## First implementation evidence (campaign remains open)

Employee debt RED: three cross-month regressions failed (six assertions) on the original query: September outstanding was zero instead of 800000/500000 and opening field was absent. New read-side query reconstructs original principal from signed adjustment snapshots, bounds payments by cutoff, and exposes opening/new/adjustment/payment/reversal/closing reconciliation. Screen, PDF view data and actual XLSX numeric cells share temporal summary rows. Existing same-period tests were retained; the daily test now excludes the following day's payment, as required by as-of semantics.

Dashboard RED: historical selected month returned zero instead of current 800000. Dedicated current debt port reads the domain's current remaining balances outside the month cache. Regression proves current balance after payment and after switching the activity month.

Employee disbursement flow RED: September principal increase of 1 changed July disbursement from 1000000 to 1000001. One shared event query now separates initial principal and dated increases for operational profit and dashboard daily rows. The operational-profit formula itself is unchanged. Legacy decreases are adjustments, not silently classified as new cash disbursement.

Service/package PDF RED: authorized and unauthorized tests both returned 404. PDF route/controller/view now exist; screen and exports call the full shared dataset. Access and range tests pass. This does not close historical package correction semantics or complete detailed PDF parity.

Inventory report now reconstructs cutoff quantity from movement deltas and valuation from exact stored movement costs. Average uses the existing InventoryCostingProjectionBuilder, not a new report costing policy. Empty September retains July quantity/value and excludes October movement. Current projection-vs-ledger diagnostics remain separate from historical balance and remain visible in summary. A projection-only product has no proven historical ledger position; it is not fabricated into the historical snapshot. Current diagnostic rows are retained in dataset; the owner-facing completeness warning still needs review. Historical product threshold/name changes are not versioned by this patch.

Inventory RED attempt initially failed on an invalid test fixture column (products.created_at), so it is NOT valid semantic RED evidence. A later overlapping test invocation produced a migration harness failure in the isolated database; both processes completed, then all reporting tests were run sequentially. Do not classify either harness failure as a domain bug.

Local execution context: repository root, APP_ENV=testing, DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=glasspos_reporting_integrity_20260929, DB_USERNAME=root, empty local test password. No production database used.

Proof logs (local /tmp):

- glasspos-reporting-debt-red.log: 3 failed / 6 assertions.
- glasspos-reporting-debt-parity.log: 21 passed / 170 assertions.
- glasspos-reporting-dashboard-red.log: 1 failed / 1 assertion.
- glasspos-reporting-dashboard-green.log: 5 passed / 49 assertions.
- glasspos-reporting-debt-flow-red.log: 1 failed / 1 assertion.
- glasspos-reporting-debt-flow-green.log: 5 passed / 14 assertions.
- glasspos-reporting-package-red.log: 2 failed / 2 assertions, missing route.
- glasspos-reporting-package-green.log: 5 passed / 22 assertions.
- glasspos-reporting-regression2.log: Reporting + ReportingExports 237 passed / 2348 assertions, exit 0.
- glasspos-reporting-phpstan2.log: no errors.
- glasspos-reporting-audit.log: line/Blade/contract audits pass.

These are executed local tool results, not owner-reported test output. Full make verify is in progress at this checkpoint.

## Remaining campaign work and decisions

- Supplier historical dated position still uses the previous shipment-cohort/current-payment contract. Current supplier balance remains intentionally unchanged and retains its existing cross-month tests. Need explicit reconciliation of historical version cutoff and later void/reversal treatment before changing this closed report contract.
- Customer outstanding dashboard now reads all active notes outside the period cache using the existing settlement query. Local regression proves old receivables remain current while new-month revenue stays zero, and a payment leaves exactly Rp1. Visible dashboard cohort metrics still need field-by-field label review.
- Historical transaction/service-package totals use current projections. Resolve original/revision/as-of/current mode and later cancellation/refund semantics from the existing revision contracts; do not silently relabel current results as historical.
- Payroll and expense remain period-scoped and retain their existing current cancellation/deletion inclusion rules. Later correction/restatement decision remains open.
- Dashboard inventory remains current, as intended, but cache freshness and all chart/report reconciliation need final proof.
- Export metadata, detailed PDF output and consolidated dashboard export coverage are not yet complete. Existing inventory/employee PDF templates are summary-only. This is a GAP against the broader export blueprint, not proof of full parity.
- Temporal test matrix A-J is partial. Cross-month, empty period, first/last-day, future payment/movement and exact-rupiah cases exist; broader reversal/void/version matrices remain open.
- Historical employee reversal behavior in the draft implementation uses reversal recorded time; owner clarification on later-correction restatement is still pending. Do not merge that policy as approved merely because tests pass.
- No claim that every report/metric has been audited exhaustively. The matrix above is the discovered surface inventory and initial classification; route-to-field/export mapping remains to be completed.
- No production deployment, finance primitive mutation, merge or campaign closeout is authorized by this checkpoint.


## Metadata and current receivable checkpoint

All nine report Excel builders now append a shared Metadata sheet containing source dataset, normalized date bounds, generation time and full filters. Metadata is stored explicitly as text, including formula-like input; rupiah report cells remain numeric. The sheet does not perform business calculations or alter report filters. It does not itself prove full screen/PDF/Excel detail parity.

The dashboard current customer outstanding reader reuses the established transaction settlement query with explicitly unbounded note dates. Partial date bounds are rejected. Current receivables are refreshed outside the month cache, while monthly flow metrics retain their period filters.

Local proof:
- `glasspos-reporting-make-verify.log`: checkpoint run completed with 8 failures, 1806 passes, 13861 assertions (482.03s). Failures were one obsolete dashboard kasbon caption and seven old Excel sheet-list expectations. No concurrency failure appeared in this run. Because files changed while that suite ran, it is not a final immutable-tree gate.
- After expectation updates, `glasspos-reporting-metadata-check.log`: 88 passed, 791 assertions, including export tests, dashboard page tests, current receivable regression and transaction export refund-due unit tests.
- Metadata safety/numeric preservation unit test: 1 passed, 6 assertions.
- `glasspos-reporting-phpstan3.log`: no errors.

The full campaign remains open. Final make verify must be rerun after the remaining implementation is stable. No merge or production deployment has occurred.

## PDF detail parity checkpoint (2026-09-29)

FACT: all nine PDF templates previously rendered summary only; eight reports have detail datasets (operational profit is intentionally aggregate-only). Six PDF builders already mapped detail but templates discarded it. Inventory and service/package needed detail presenters as well. Screen templates also omitted supplied rows except supplier payable.

Correction: eight PDF templates now render dataset detail tables; seven screen pages expose the same presentation rows. Supplier screen retains its existing invoice detail table. No PDF-specific database query or business aggregation was added. Inventory tables explicitly separate historical position, period movement and nonzero current projection diagnostics. Cash-ledger/transaction/package PDFs use landscape for wider detail. Employee detail labels include the actual cutoff date. Normal page presentation does not construct an Excel workbook or PDF document.

Regression evidence:
- `/tmp/glasspos-pdf-detail-red.log`: render regression failed because supplied employee debt detail was absent; same test now covers eight templates (24 assertions).
- `/tmp/glasspos-supplier-temporal-red.log`: three new supplier regressions fail (4 assertions): old outstanding disappears, opening/payment-period fields absent, settled historical invoice omitted. These remain RED until supplier source correction; they are not skipped or weakened in source.
- `/tmp/glasspos-pdf-parity-final-checkpoint.log`: 95 passed / 792 assertions before further detail parity assertions.
- Inventory regression compares the historical 301-rupiah position and 1-rupiah residual against actual screen view data, PDF detail/render and numeric Excel H2. Employee regression compares 500000 closing against screen/PDF detail and numeric Excel G2.
- Existing summary-only screen assertions were updated only for included rows; excluded period/cancelled/deleted rows remain excluded. These expectation updates are separate from the new semantic regression fixtures.

Open supplier decision: whether corrections/reversals recorded after cutoff restate an earlier report. The finance 0006 blueprint explicitly says as-of reports are stable for the selected date and current projection is valid only for current mode. Recommended historical behavior therefore uses recorded versions/events through cutoff and current behavior remains latest. An owner question is pending; no supplier mutation primitive or historical void/reversal policy has been changed in this checkpoint.

The supplier regressions remain a campaign failure, not an unrelated baseline failure. Full make verify is not a final gate while these tests are RED. No commit/push/PR closeout or merge/deploy performed.

## Accepted historical policy and supplier correction

Owner explicitly answered: **Ya, keadaan pada tanggal as-of**. This supersedes the pending-decision notes above. Future corrections/reversals must not restate an earlier as-of position.

Supplier dated report now reconstructs opening and closing from invoice versions, effective payment dates, recorded reversal timestamps and recorded void timestamps. Old invoices remain included. Date bounds select activity plus closing position, superseding 0005's dated shipment-cohort behavior; default current/all and the dedicated dashboard current reader remain unchanged. Due status uses closing date for dated reports. Signed principal changes/void reconcile as adjustments. Voided-in-period rows remain visible for reconciliation with a Void label, not Lunas. Receipts are gross receipt activity through cutoff, not net stock.

Versioned invoice creation/revision uses recorded changed_at. Unversioned legacy invoice uses shipment date and exposes history_basis=legacy_shipment_date. A claimed version history that is missing fails rather than manufacturing a historical amount. Historical display-name/threshold versioning for inventory is not introduced.

Local supplier proof before final gates: 3 RED / 4 assertions; initial GREEN 3 / 11; expanded matrix GREEN 7 / 25; supplier/export adjacent GREEN 39 / 295; actual screen/PDF/XLSX parity plus matrix GREEN 8 / 51. Numeric detail outstanding 700001 and numeric summary reconcile exactly. Old cohort-exclusion and future-payment assertions were changed to the owner's new dated-position contract; current supplier tests remain.

Dashboard stock regression proved cached historical month returned qty 2 after current qty became 1. Current inventory summary/restock and stock status chart now refresh outside period caches; chart snapshot date is today, not selected month-end. Activity and top-selling remain period scoped.

Payroll reversal and expense deletion after cutoff no longer erase earlier-period flow amounts. Corresponding profit and daily-chart readers use the same cutoff inclusion. These primitives do not record money received, so reporting does not fabricate a negative cash outflow or a cash receipt from reversal/deletion alone. Two new regressions were RED with 0 instead of 100001 before the filter fix.

Inventory Excel now has a separate current-diagnostics sheet. Current projection differences are explicitly labeled current in summary; historical positions remain movement-derived. Screen/PDF explain the legacy/no-movement limitation. PDF detail templates are shared render-only adapters; monetary values remain in the application dataset.

Persistent local logs now live under storage/logs/reporting-integrity (ignored, not committed). Earlier /tmp logs may disappear across runner resets; earlier exact receipts above are historical execution evidence, not a new run claim.

Checkpoint adjacent run: Reporting + ReportingExports + Admin + Unit/Application/Reporting = **283 passed, 2745 assertions**, 30.77s, exit 0. PHPStan and audit-contract passed at that checkpoint. Changes after that run still require final gates.

### Still open; do not claim campaign closeout

- Transaction summary and service/package still read current revision/cohort state. Explicit current-mode explanatory labels have been added across screen/PDF/Excel to prevent falsely advertising an as-of snapshot, but labels alone do not implement historical revision/settlement reconstruction. This remains a campaign GAP under the owner's cutoff requirement.
- Consolidated dashboard export has not been implemented; per-report shortcuts now include service/package. No independent dashboard export parity proof exists.
- Remaining field-level/rollup detail parity and historical note mutation matrix need completion. Current partial proof is not exhaustive parity.
- Final targeted run, PHPStan, audit-contract, diff check, full make verify on final HEAD, final handoff, commit/push and PR remain required. No merge or deployment.


## Payment cutoff regression checkpoint (campaign remains RED)

The new TransactionReportCutoffFeatureTest first failed: a payment dated 2026-10-01 incorrectly contributed 100001 to the September report. Payment allocation, component allocation and historical gross-payment queries now accept the report closing date. Unbounded current reads retain all payment dates. The regression also covers first/last day inclusion and a one-rupiah closing residual versus zero current outstanding.

Proof from isolated local test database, logs under storage/logs/reporting-integrity:
- transaction-cutoff-red.log: 1 failed / 1 assertion before source fix.
- transaction-cutoff-green.log: initial cutoff plus current dashboard regression, 2 passed / 7 assertions.
- payment-cutoff-adjacent.log: expanded Reporting + ReportingExports + Admin + Unit/Application/Reporting, 283 passed / 2 failed / 2686 assertions. Both failures are campaign-related, not baseline: GetTransactionSummaryPerNoteFeatureTest expects a March 16 payment in a March 15 report; PrimitiveLifecycleReportingChainFeatureTest expects September 16 payment cash in the September 15 cohort. Assertions have deliberately not been changed pending complete historical settlement/refund reconstruction.
- payment-cutoff-phpstan.log: no errors; payment-cutoff-audit.log: passed; git diff --check: passed.

The current note-history outstanding projection is still unsuitable for historical refund/revision cases. The payment cutoff fix alone does not close transaction temporal semantics. Presentation now explicitly states that payments are cutoff-bounded while refund/settlement remains current; this warning is not parity or completion proof. Next work must resolve the source reconstruction and retain lifecycle assertions that verify domain semantics. Final make verify has not been run and no commit/push/merge/deployment occurred at this checkpoint.
