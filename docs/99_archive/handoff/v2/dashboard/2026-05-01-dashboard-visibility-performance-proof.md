# HANDOFF — Dashboard Visibility + Performance Proof

## Final Goal

Dashboard Hyperpos harus:
- tidak menyembunyikan data ketika data ada,
- bisa membaca periode bulan tertentu,
- tetap memberi konteks ledger saat current projection/top selling = 0 karena full refund/reversal,
- initial dashboard page ditargetkan load < 1 detik,
- full data-ready dashboard perlu diarahkan mendekati < 1 detik pada dataset ramai.

## Current Branch / Baseline

Branch: main
HEAD / origin/main terakhir: 8d91f4c6 Split dashboard reporting files for audit limits
Working tree sebelum probe terakhir clean.
Temp probe files selalu removed.
Temp indexes terakhir dropped semua.

## Commits Completed and Pushed

- 865c95ab Add dashboard ledger activity context
- 399cb8b6 Add dashboard month period selector
- c86c3e30 Keep dashboard analytics fallback alive without charts
- 8d91f4c6 Split dashboard reporting files for audit limits

## Locked Decisions

- Cash totals harus pakai cash records, bukan component projection.
- Inventory movements immutable/auditable.
- Reversal movement rows tidak boleh dihapus/disembunyikan.
- Jangan ubah formula finance/refund/top selling tanpa proof baru.
- Dashboard visibility diperbaiki lewat period selector + ledger context, bukan mutasi history.
- Dashboard query saat ini masih memakai notes/work_items untuk beberapa reporting path.

## Visibility Root Cause Proven

Dashboard default membaca bulan aktif berdasarkan tanggal sistem.
Saat sistem berada di 2026-05-01, data lokal penting berada di 2026-04-30, sehingga transaksi/refund/top selling terlihat kosong.

Data April lokal fully refunded/reversed:
- cash in 710800
- refund out 710800
- net cash 0
- stock out qty 9
- stock reversal qty 9
- net stock out qty 0
- top selling current 0

Dua nota April punya note_state = refunded dan total_rupiah = 0. Maka hero gross/current projection memang 0. Top selling kosong karena semua work_item_store_stock_lines fully reversed, net qty = 0.

## Patch Completed

### Ledger Activity Context

Files:
- app/Application/Reporting/UseCases/AdminDashboardOverviewPayload.php
- resources/views/admin/dashboard/index.blade.php
- tests/Feature/Admin/AdminDashboardPageFeatureTest.php

Added dashboard['ledger_activity'] and UI section:
- Aktivitas Ledger Periode Ini
- Kas Masuk Sebelum Refund
- Refund Keluar Periode Ini
- Qty Keluar Sebelum Reversal
- Net Qty Setelah Reversal

### Month Period Selector

URLs:
- /admin/dashboard?month=YYYY-MM
- /admin/dashboard/analytics?month=YYYY-MM

Rules:
- month format YYYY-MM
- empty/invalid fallback bulan aktif
- active month to = today
- past month to = endOfMonth
- Overview and analytics use shared period builder
- Blade analytics URL carries selected month

Files changed:
- app/Adapters/In/Http/Controllers/Admin/AdminDashboardAnalyticsPayloadController.php
- app/Adapters/In/Http/Controllers/Admin/AdminDashboardPageController.php
- app/Application/Reporting/UseCases/AdminDashboardAnalyticsPeriod.php
- app/Application/Reporting/UseCases/AdminDashboardOverviewPayloadBuilder.php
- app/Application/Reporting/UseCases/AdminDashboardOverviewPeriod.php
- app/Application/Reporting/UseCases/AdminDashboardPeriod.php
- app/Application/Reporting/UseCases/GetAdminDashboardAnalyticsHandler.php
- app/Application/Reporting/UseCases/GetAdminDashboardOverviewHandler.php
- app/Application/Reporting/UseCases/GetAdminDashboardPagePayloadHandler.php
- resources/views/admin/dashboard/index.blade.php
- tests/Feature/Admin/AdminDashboardPageFeatureTest.php

### JS Analytics Fallback

Commit:
- c86c3e30 Keep dashboard analytics fallback alive without charts

File:
- public/assets/static/js/admin/dashboard-analytics.js

Fix:
- Boot guard no longer returns when ApexCharts unavailable.
- loadRemotePayload() still runs.
- renderAnalyticsSummaries() still runs before chart render.
- ApexCharts guard moved into chart renderers via canRenderCharts().

### Audit Line Split

Commit:
- 8d91f4c6 Split dashboard reporting files for audit limits

Files:
- app/Adapters/Out/Reporting/Queries/DashboardInventory/DashboardInventoryMovementSummaryQuery.php
- app/Adapters/Out/Reporting/Queries/DashboardInventory/DashboardInventorySnapshotSummaryQuery.php
- app/Adapters/Out/Reporting/Queries/DashboardInventory/DashboardInventorySummaryQuery.php
- app/Application/Reporting/UseCases/AdminDashboardOverviewPayload.php
- app/Application/Reporting/UseCases/Concerns/BuildsAdminDashboardOverviewContext.php

Reason:
- make verify previously failed because file length exceeded audit limits.
- Split files under 100 lines.
- No intended domain math/query semantic change.

## Proof Completed

### Full Verify

make verify passed:
- PHPStan OK
- audit-lines OK
- Blade PHP/directive audit OK
- Contract audit passed
- Pest: 801 passed, 4176 assertions

### Browser Manual Proof

Default May:
- /admin/dashboard
- payload URL: /admin/dashboard/analytics?month=2026-05
- active_month = 2026-05
- charts render where data exists

Selected April:
- /admin/dashboard?month=2026-04
- analytics URL month=2026-04
- backend period:
  - active_month = 2026-04
  - date_from = 2026-04-01
  - date_to = 2026-04-30
- DOM visible:
  - topSellingRange: Range: 01-04-2026 s.d. 30-04-2026
  - stockRange: Snapshot stok pada 30-04-2026
  - operationalRange: Range: 01-04-2026 s.d. 30-04-2026
  - cashChangeDenominationsCount = 2
- chart keys:
  - stock_status_donut
  - top_selling_bar
  - cashflow_line
  - operational_performance_bar

## Performance Proof

### Empty Dashboard Temporary Probe

Dataset: empty dashboard fixture.
Result:
- page_ms = 104.66
- analytics_ms = 27.02
- total_ms = 131.68
- Test pass: 1 passed, 7 assertions
- Temp file removed.

### Bulk Dashboard Temporary Probe

Dataset:
- 500 products
- 3000 notes
- 3000 work_items
- 3000 work_item_store_stock_lines
- 3000 inventory_movements
- 3000 customer_payments
- 3000 payment_allocations
- 3000 customer_payment_cash_details

Result:
- page_ms = 561.72
- analytics_ms = 425.58
- total_ms = 987.30
- Test pass: 1 passed, 7 assertions
- Temp file removed.

Caveat:
- This proves Laravel test-request timing on synthetic bulk dataset.
- This is not browser real-user timing and not production RUM.

### Query Count Probe

Dataset:
- 500 products
- 3000 notes

Result:
- page_queries = 37
- analytics_queries = 15
- page_ms = 724.05
- analytics_ms = 562.17
- total_ms = 1286.21
- Test pass: 1 passed, 2 assertions
- Temp file removed.

Conclusion:
- No brutal N+1.
- Query count bounded.
- Full page + analytics still above 1 second in this probe.

### Slow Query Profile Probe

Page:
- route_ms = 745.45
- db_ms = 349.37
- php_render_ms = 396.08
- query_count = 37

Analytics:
- route_ms = 557.82
- db_ms = 251.88
- php_render_ms = 305.94
- query_count = 15

Hotspots:
- Transaction summary note/payment/refund projection
- Cash ledger event union
- Dashboard top selling product
- Inventory movement summary
- Potential change rows/per-day

### Index Audit

DB:
- connection = mysql
- driver = mysql
- database = bengkelhex

Important existing indexes:
- notes has transaction_date index.
- work_items has note_id and note_id,line_no indexes.
- work_item_store_stock_lines has work_item_id and product_id indexes.
- inventory_movements has product_id, source_type/source_id, tanggal_mutasi.
- customer_payments has paid_at and payment_method/paid_at.
- payment_allocations has customer_payment_id, note_id, customer_payment_id/note_id.
- customer_refunds has customer_payment_id, note_id, refunded_at, customer_payment_id/note_id.
- customer_payment_cash_details PK is customer_payment_id and has change_rupiah index.

Gaps:
- No composite index specifically matching several dashboard heavy patterns.
- But index experiment proves only modest improvement, not enough as final solution.

### DDL Listener Sanity Probe

Result:
- before = 1
- after_create_index = 1
- after_analyze = 1
- after_relisten = 2

Conclusion:
- DB listener survives CREATE INDEX and ANALYZE TABLE.
- Earlier in-process index experiment result with query_count = 0 was invalid due to test design/cache/memoization, not listener failure.

### Process-Isolated Index Experiment

Baseline:
- Page:
  - route_ms = 751.23
  - db_ms = 352.64
  - php_render_ms = 398.59
  - query_count = 37
- Analytics:
  - route_ms = 572.10
  - db_ms = 264.73
  - php_render_ms = 307.37
  - query_count = 15
- Combined route = 1323.33 ms

Indexed:
- Page:
  - route_ms = 710.56
  - db_ms = 316.13
  - php_render_ms = 394.43
  - query_count = 37
- Analytics:
  - route_ms = 543.19
  - db_ms = 238.58
  - php_render_ms = 304.61
  - query_count = 15
- Combined route = 1253.75 ms

Delta:
- Page route improves 40.67 ms, about 5.4%.
- Page DB improves 36.51 ms, about 10.4%.
- Analytics route improves 28.91 ms, about 5.1%.
- Analytics DB improves 26.15 ms, about 9.9%.
- Combined improves 69.58 ms, about 5.3%.
- Still above 1 second.

Temp indexes tested:
- dash_notes_date_id_idx on notes(transaction_date, id)
- dash_wi_note_id_idx on work_items(note_id, id)
- dash_wissl_wi_product_idx on work_item_store_stock_lines(work_item_id, product_id)
- dash_im_type_source_date_idx on inventory_movements(movement_type, source_type, tanggal_mutasi)
- dash_im_rev_source_qty_idx on inventory_movements(source_type, source_id, qty_delta)
- dash_pa_note_payment_idx on payment_allocations(note_id, customer_payment_id)
- dash_cr_refund_note_payment_idx on customer_refunds(refunded_at, note_id, customer_payment_id)
- dash_cp_paid_id_idx on customer_payments(paid_at, id)

Cleanup proof:
- temp process-isolated probe removed
- all temp indexes OK_DROPPED

## Hotspot Source Map

Likely files:
- app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundRowsQuery.php
- app/Adapters/Out/Reporting/Queries/DashboardTopSellingProductQuery.php
- app/Adapters/Out/Reporting/Queries/DashboardInventory/DashboardInventoryMovementSummaryQuery.php
- app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/PotentialChangeAmountRowsQuery.php
- app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/PotentialChangePerDayQuery.php

Top selling query:
- joins work_item_store_stock_lines -> work_items -> notes
- left joins products
- left joins reversal subquery from inventory_movements
- groups by product_id
- having net qty > 0
- orders by sold_qty, gross_revenue, nama_barang

Potential change amount rows:
- joins customer_payment_cash_details -> customer_payments
- filters paid_at
- orders by paid_at and customer_payment_id
- plucks change_rupiah

Potential change per day:
- joins customer_payment_cash_details -> customer_payments
- filters paid_at
- groups and orders by DATE(customer_payments.paid_at)

## Current Conclusion

Dashboard visibility bug is largely fixed and proven.

Performance:
- Initial dashboard page is below 1s in current probes.
- Full page + async analytics is still above 1s in latest query-count/slow/index-isolated probes.
- Query count is bounded; not an N+1 disaster.
- Indexes help modestly, but not enough.
- PHP/render cost is large and likely needs attention.
- Page and analytics likely duplicate heavy report work.

## Remaining GAP

- Browser/RUM timing for real local April page still not done.
- Manual fallback proof with ApexCharts unavailable still not done.
- No permanent performance test added.
- No permanent index migration added.
- No query-shape optimization implemented.
- No PHP/render cost breakdown beyond route_ms - db_ms.
- Need decide whether page should compute only initial/hero data and let analytics own heavy charts.

## Recommended Next Active Step

Do not patch permanent index yet.

Next safest step:
Profile page payload duplication and determine which heavy reports are computed both by page and analytics.

Suggested command for next session:
- inspect AdminDashboard page/analytics handlers and payload builders
- map which usecases execute in page vs analytics
- remove duplicate heavy chart/report computation from initial page if possible
- keep initial page useful and visible
- keep analytics async as source for charts/summaries
- run targeted dashboard tests
- rerun query-count/slow probe
- then decide if small index migration is still worth it

## Opening Prompt for Next Session

Lanjutkan project Hyperpos dari repo lokal:

/home/asyraf/Code/laravel/bengkel2/app

Branch main.
HEAD/origin/main terakhir:
8d91f4c6 Split dashboard reporting files for audit limits.

Goal:
Dashboard Hyperpos harus tidak menyembunyikan data, bisa pilih bulan, tetap menampilkan konteks ledger untuk full refund/reversal, dan diarahkan supaya initial dashboard page < 1 detik serta full data-ready dashboard mendekati < 1 detik pada dataset ramai.

Locked decisions:
- Jangan ubah formula finance/refund/top selling tanpa proof baru.
- Jangan mutasi inventory movement history.
- Cash totals harus pakai cash records.
- Reversal rows tidak boleh dihapus/disembunyikan.
- Dashboard query masih memakai notes/work_items untuk beberapa reporting path.

Proof terakhir:
- make verify pass: PHPStan OK, audit-lines OK, contract audit passed, Pest 801 passed / 4176 assertions.
- Browser April valid: /admin/dashboard?month=2026-04, analytics URL month=2026-04, DOM ranges April, chart keys present.
- Query count bulk 500 products / 3000 notes:
  - page_queries=37
  - analytics_queries=15
  - page_ms=724.05
  - analytics_ms=562.17
  - total_ms=1286.21
- Slow profile:
  - page route_ms=745.45, db_ms=349.37, php_render_ms=396.08
  - analytics route_ms=557.82, db_ms=251.88, php_render_ms=305.94
- Process-isolated index experiment:
  - baseline page 751.23 ms, analytics 572.10 ms, combined 1323.33 ms
  - indexed page 710.56 ms, analytics 543.19 ms, combined 1253.75 ms
  - indexes improve DB around 10%, route around 5%, but still not enough
  - temp files removed and temp indexes dropped

Next safest active step:
Profile duplicate heavy computation between admin dashboard page payload and analytics payload. Inspect handlers/builders first. Do not patch index migration yet. Find whether page computes reports that analytics recomputes, then propose bounded removal/deferral from initial page while preserving visible summary and tests.
