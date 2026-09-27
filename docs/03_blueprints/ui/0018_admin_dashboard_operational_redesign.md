# Admin dashboard operational redesign

## Blueprint and source baseline

- Scope: desktop /admin/dashboard and its presentation dependencies; analytics contract preserved.
- Baseline: main = origin/main at 7723dea9d06bc2c58d77405e1dff8df8657542b4, clean after fetch.
- Owner authorizes autonomous issue → branch → implementation → tests → browser proof → PR → review → merge.
- Excluded: mobile_supplier_hub, global shell/sidebar, business queries/lifecycle/permissions, dependencies/framework migration.
- Current controller intentionally sends handset to supplier hub; desktop overview fetched server-side, analytics separately.
- Canonical constraints: AGENTS.md and docs/01_standards; owner Golden Reference detail principles.

## Phase A: complete exposed data inventory

A primary KPI; B operational health; C finance; D analytics; E action; F audit.
Aliases have one canonical visible value. Collections enumerate all fields; chart metadata remains in the API.
Sources below are class names under app/Application/Reporting and app/Adapters/Out/Reporting, inspected from current source.

| DATA KEY | SEMANTIC BUSINESS | UNIT | PERIOD | SOURCE | CURRENT PRESENTATION | ACTIONABILITY | PRIORITY | RISK IF MISLABELED |
|---|---|---|---|---|---|---|---|---|
| hero.monthly_gross_transaction_rupiah | Total nilai nota non-cancelled, bukan revenue atau laba | Rp | tanggal nota | TransactionSummaryReportingQuery | hero | Buka laporan transaksi | A | Disangka kas/revenue |
| hero.monthly_net_cash_collected_rupiah | Pembayaran historis dikurangi customer refund dan surplus refund dibayar untuk nota terpilih | Rp | cohort tanggal nota; pembayaran/refund lintas waktu | GetTransactionSummaryPerNoteHandler::payloadRows | hero | Rekonsiliasi nota | A | Disamakan event cash flow |
| hero.monthly_outstanding_rupiah | Sisa tagihan dari report/projection settlement | Rp | cohort tanggal nota | TransactionSummaryReportingQuery | hero + posisi | Tindak lanjut tagihan | A | Disangka seluruh piutang toko |
| stats.total_qty_on_hand | Qty persediaan aktif | unit | saat ini, bukan akhir bulan terpilih | DashboardInventorySnapshotSummaryQuery | stat + posisi / donut async | Stok / restok / atur threshold | B | Snapshot dianggap historis; produk disamakan unit |
| stats.total_inventory_value_rupiah | Modal persediaan dari costing projection | Rp | saat ini, bukan akhir bulan terpilih | DashboardInventorySnapshotSummaryQuery | stat + posisi / donut async | Stok / restok / atur threshold | B | Snapshot dianggap historis; produk disamakan unit |
| stats.stock_safe_product_rows | Qty > reorder point; thresholds lengkap | produk | saat ini, bukan akhir bulan terpilih | DashboardInventorySnapshotSummaryQuery | stat + posisi / donut async | Stok / restok / atur threshold | B | Snapshot dianggap historis; produk disamakan unit |
| stats.stock_low_product_rows | Critical < qty <= reorder point | produk | saat ini, bukan akhir bulan terpilih | DashboardInventorySnapshotSummaryQuery | stat + posisi / donut async | Stok / restok / atur threshold | B | Snapshot dianggap historis; produk disamakan unit |
| stats.stock_critical_product_rows | Qty <= critical; thresholds lengkap | produk | saat ini, bukan akhir bulan terpilih | DashboardInventorySnapshotSummaryQuery | stat + posisi / donut async | Stok / restok / atur threshold | B | Snapshot dianggap historis; produk disamakan unit |
| stats.stock_unconfigured_product_rows | Salah satu threshold null | produk | saat ini, bukan akhir bulan terpilih | DashboardInventorySnapshotSummaryQuery | stat + posisi / donut async | Stok / restok / atur threshold | B | Snapshot dianggap historis; produk disamakan unit |
| stats.daily_cash_in_rupiah | Penerimaan buku kas transaksi hari ini (cash dan transfer) | Rp | hari ini, tidak mengikuti filter | DashboardCashLedgerTotals / TransactionCashLedgerReportingQuery | stat | Pantau penerimaan hari ini | C | Disangka uang fisik saja atau bulan terpilih |
| finance.monthly_cash_in_rupiah | Total event masuk buku kas transaksi | Rp | tanggal event di periode | DashboardCashLedgerTotals / TransactionCashLedgerReportingQuery | finance + ledger | Buka buku kas | C | Disangka laba atau semua arus kas usaha |
| finance.monthly_cash_out_rupiah | Event refund + surplus refund aktif dibayar; bukan semua pengeluaran toko | Rp | tanggal event di periode | DashboardCashLedgerTotals / TransactionCashLedgerReportingQuery | finance + ledger | Buka buku kas | C | Disangka laba atau semua arus kas usaha |
| finance.monthly_net_cash_flow_rupiah | Event masuk minus event refund keluar | Rp | tanggal event di periode | DashboardCashLedgerTotals / TransactionCashLedgerReportingQuery | finance + ledger | Buka buku kas | C | Disangka laba atau semua arus kas usaha |
| stats.monthly_cash_operational_profit_rupiah; finance.monthly_cash_operational_profit_rupiah | Sisa kas operasional = payment - refund - modal produk - biaya - gaji - kasbon | Rp | mixed basis existing report dalam periode | OperationalProfitMetricsQuery + CashFlowMetricQuery/ProductCostMetricQuery/OperatingCostMetricQuery | stat + finance | Buka ringkasan kas operasional | A | Disangka laba akuntansi; alias diduplikasi |
| ledger_activity.gross_transaction_rupiah | Alias hero gross | Rp | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | tidak tampil | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| ledger_activity.cash_in_before_refund_rupiah | Alias finance masuk | Rp | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | ledger | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| ledger_activity.cash_refund_out_rupiah | Alias finance keluar | Rp | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | ledger | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| ledger_activity.net_cash_flow_rupiah | Alias finance net | Rp | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | tidak tampil | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| ledger_activity.stock_out_qty_before_reversal | Sale-out movement qty sebelum reversal | unit | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | ledger | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| ledger_activity.stock_reversal_qty | Store-stock-line reversal qty positif | unit | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | tidak tampil | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| ledger_activity.net_stock_out_qty | Sale-out minus reversal; dapat negatif | unit | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | ledger | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| ledger_activity.is_cash_fully_refunded_period | Cash in > 0 dan out >= in; bukan bukti setiap nota fully refunded | boolean | gross: cohort nota; uang: event; stok: tanggal_mutasi | BuildsAdminDashboardOverviewContext / DashboardInventoryMovementSummaryQuery | helper | Rekonsiliasi | F | Net nol bukan tidak ada aktivitas; flag bukan status semua nota |
| position.inventory_value_rupiah | Alias stats nilai modal | Rp | saat ini | DashboardInventorySnapshotSummaryQuery | posisi | Buka report sumber | B | Disangka saldo seluruh toko/historis atau disamakan event refund |
| position.transaction_outstanding_rupiah | Alias hero outstanding | Rp | cohort tanggal nota | TransactionSummaryReportingQuery | posisi | Buka report sumber | A | Disangka saldo seluruh toko/historis atau disamakan event refund |
| position.supplier_outstanding_rupiah | Faktur non-void minus pembayaran non-reversed | Rp | cohort tanggal pengiriman; pembayaran sepanjang waktu | DatabaseSupplierPayableReportingSourceReaderAdapter | posisi | Buka report sumber | C | Disangka saldo seluruh toko/historis atau disamakan event refund |
| position.employee_debt_remaining_rupiah | Saldo tersisa kasbon yang dibuat dalam periode | Rp | cohort created_at; saldo sekarang | DatabaseEmployeeDebtReportingSourceReaderAdapter | posisi | Buka report sumber | C | Disangka saldo seluruh toko/historis atau disamakan event refund |
| position.monthly_refunded_rupiah | Customer refund pada nota terpilih, bukan event refund bulan ini | Rp | cohort tanggal nota; refund lintas waktu | TransactionSummaryReportingQuery | posisi | Buka report sumber | F | Disangka saldo seluruh toko/historis atau disamakan event refund |
| position.monthly_operational_expense_rupiah | Biaya non-deleted pada expense_date | Rp | tanggal biaya di periode | GetOperationalExpenseReportDatasetHandler | posisi | Buka report sumber | C | Disangka saldo seluruh toko/historis atau disamakan event refund |
| top_selling_rows[].product_id/kode_barang/nama_barang/sold_qty/gross_revenue_rupiah | 5 produk, qty net reversal, nilai line diprorata net reversal; key gross_revenue adalah nama legacy | id/teks/unit/Rp | cohort tanggal nota; reversal sepanjang waktu | DashboardTopSellingProductQuery / FormatsAdminDashboardTopSellingRows | tabel + chart terpisah; label Omzet | Detail produk | D | Disangka gross tanpa reversal, cash atau profit |
| restock_priority_rows[].product_id/kode_barang/nama_barang/current_qty_on_hand/reorder_point_qty/critical_threshold_qty/status/status_label | 5 prioritas: critical dahulu, qty ascending, id; hanya thresholds lengkap | id/teks/unit/status | saat ini | DashboardRestockPriorityQuery | tabel bawah | Detail produk; lihat seluruh stok | B/E | Unconfigured disangka aman; list disangka seluruh stok |
| period.today/active_month/date_from/date_to | Bulan aktif sampai hari ini; bulan lain sampai akhir bulan | tanggal | timezone aplikasi | AdminDashboardPeriod | heading/filter | GET month YYYY-MM | header | Semua angka dianggap basis waktu sama |
| analytics.period.window_type/anchor_date/active_month/date_from/date_to/granularity/generated_at | Metadata: window_type selalu month_to_date meski bulan lampau penuh; generated_at UTC | teks/tanggal | daily dalam selected range | AdminDashboardAnalyticsPayloadBuilder | range JS | Baca range aktual | D | window_type disangka selalu MTD |
| analytics.charts.stock_status_donut.title/metric_unit/snapshot_date/total_value/segments[].key/label/value/color_token | Distribusi counts snapshot stok sekarang; snapshot_date berisi period.to, bukan histori stok | produk | saat ini | BuildStockStatusDonutChart | donut + list | Status stok | B | Tanggal payload memberi kesan stok historis; UI pakai Saat ini |
| analytics.charts.top_selling_bar.title/metric_unit/range/categories[].id/code/label/series[].key/label/values/detail[].id/code/label/sold_qty/gross_revenue_rupiah | Representasi top_selling_rows sama | unit/Rp | cohort nota | BuildTopSellingBarChart | chart duplikat tabel | Canonical tabel server; payload tetap utuh | D | Metrik legacy gross disalahlabel |
| analytics.charts.operational_performance_bar.title/metric_unit/range/labels/series[].key/label/values/summary.total_operational_profit_rupiah/total_operational_expense_rupiah/total_refund_rupiah/total_potential_change_rupiah | Harian: laba versi dataset analytics, biaya, refund termasuk surplus, potensi kembalian; total masing-masing | Rp | payment/refund/biaya/gaji/kasbon tanggal event; external cost tanggal nota; COGS tanggal mutasi | DashboardOperationalPerformancePeriodQuery + builders | area smoothed + 4 summary boxes | Tren harian; rincian sumber | D/F | Tidak jamin identik KPI: external cost analytics belum subtract returned external part, overview sudah |
| analytics.cash_change_denominations[].denomination/count/total_rupiah | Agregasi pecahan representable atas change_rupiah payment cash; bukan saldo laci atau stok pecahan | Rp/lembar-koin | paid_at periode | PotentialChangeAmountRowsQuery / CashChangeDenominationCalculator | tabel | Perencanaan pecahan | F | Disangka ketersediaan uang fisik |
| dashboardExportQuery; dashboardFilterDrawer; dashboardReportExportShortcuts | 8 report: transaksi, buku kas, stok/modal, kas operasional, biaya, gaji, hutang karyawan, hutang pemasok; PDF/Excel/Buka | route/query | monthly + reference_date = period.to | AdminDashboardPageController + ViewData | drawer gabungan | Filter/reset; export dan laporan | header | Mengganti filter tanpa submit disangka mengubah export aktif |

## Phase B: information architecture and decisions

1. Header: Ringkasan Toko, explicit active range, existing combined Filter & Cetak drawer.
2. Four primary values: Total Nilai Nota; Uang Bersih Diterima (cohort nota); Sisa Tagihan (cohort nota); Sisa Kas Operasional. Selected-period heading makes repeated Bulan Ini unnecessary and avoids mislabeling historical months.
3. Operational attention: restock list first, current stock status counts alongside, total units and inventory value once. No donut duplicating four directly actionable counts. Snapshot clearly says Saat ini even when month filter is historical.
4. Finance: event-based cash in/out/net, daily receipts with today's date; supplier and employee debt show cohort qualifier; operational expense once.
5. Analytics: straight daily lines for operational dataset, negative values retained, no invented growth/comparison; top selling canonical table includes net qty, net line value and product links. Remove redundant top selling chart from page, leave endpoint untouched.
6. Audit: customer refund by note cohort and stock out/reversal/net; explain cohort vs event. Cash fully-refunded flag says out >= in, never claims all notes refunded or all sales zero. Analytics totals have explicit dataset context, since existing operational analytics and report differ in external-return deduction. Potensi Kembalian and denomination estimates remain below the fold.

## Visual contract

Reference: https://preview.tabler.io/ and https://docs.tabler.io/ui/components/card (read 2026-09-27).
Adopt compact flat cards, thin borders, modest radius, neutral surfaces, restrained semantic color, tabular numbers, clear section/value/body typography, efficient whitespace. Keep Bootstrap, ApexCharts and shell. Dedicated scoped CSS with light/dark tokens; no gradients/profile card/hover lift/nested cards. Charts use theme tokens for axes/grid/tooltips/legend, explicit loading/error/empty states. Drawer remains month-filter/export entry, with keyboard focus and Escape support scoped to dashboard.

## Execution map and expected files

- This blueprint/inventory; campaign issue before implementation branch.
- resources/views/admin/dashboard/index.blade.php and dashboard partials.
- public/assets/static/css/admin-dashboard.css (new).
- public/assets/static/js/admin/dashboard-analytics.js; optional dashboard drawer script.
- tests/Feature/Admin/AdminDashboardPageFeatureTest.php, focused presentation regression tests if needed.
- Controller/view-data only if required for presentation; no report query changes.

## Proof plan / definition of done

Run AdminDashboardPageFeatureTest and AdminDashboardHandsetFeatureTest, adjacent Reporting and ReportingExports regression, syntax/Blade/line audits. Meaningful assertions cover cohort labels, no repeated canonical metrics, snapshot current for old month, refund/reversal and negative cash, permissions and existing export/product URLs.
Browser: actual Laravel rendering with isolated fixtures; 1366 and 1920 light/dark, 1024/768 grid, handset hub, long currency, empty and populated states, refund/reversal, month filter, drawer links, analytics failure and theme switch. Capture screenshots and inspect them. Do not call synthetic stress fixtures production data.
Review complete diff, verify public payload and business source unchanged, open coherent PR, inspect checks/review and merge when scoped checks pass, recording any verified pre-existing global audit failure separately. Record exact proof and limitations.

## Progress

Inventory, implementation, focused/report regression and browser proof complete. See [proof and review](evidence/admin_dashboard/README.md). Campaign issue: https://github.com/Asyraf2003/GlassPos/issues/26. PR/merge status is tracked in GitHub.
