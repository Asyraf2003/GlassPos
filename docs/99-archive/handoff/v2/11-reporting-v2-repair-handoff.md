# Handoff V2 — Reporting Repair, Cash-Based Operational Profit, Payroll Report, Filter Simplification

- Status: Done
- Date: 2026-04-16
- Scope: Admin / Reporting / Dashboard / Payroll Report / Operational Expense Report / Operational Profit / Period Filter
- Progress: 100%

## Ringkasan

Pekerjaan ini menutup slice perbaikan reporting V2 dengan fokus pada empat area besar:

- menyederhanakan kontrak filter laporan menjadi hanya `daily`, `weekly`, dan `monthly`
- menambah laporan yang sebelumnya belum ada atau belum muncul jelas di UI admin
- memperbaiki definisi dan perhitungan `Laba Kas Operasional` agar benar-benar cash-based
- menyelaraskan dashboard admin agar memakai definisi laba yang sama dengan halaman laporan

Paket ini tidak berhenti di level implementasi dan test otomatis saja. Setelah seluruh suite hijau, pekerjaan ini juga ditutup dengan audit manual terhadap data riil untuk memastikan summary laporan laba kas operasional benar secara matematika dan match dengan output aplikasi.

## Keputusan yang Terkunci

### 1. Kontrak filter laporan

Filter laporan resmi sekarang hanya memakai tiga mode:

- `daily`
- `weekly`
- `monthly`

Mode `custom` dihapus dari kontrak UI dan backend.

Makna operasional:

- `daily` membaca tepat di `reference_date`
- `weekly` membaca Senin sampai Minggu berdasarkan `reference_date`
- `monthly` membaca tanggal 1 sampai akhir bulan berdasarkan `reference_date`

Tidak ada lagi kontrak `date_from` / `date_to` untuk halaman laporan admin dalam slice ini.

### 2. Dasar waktu laporan harian

Untuk laporan yang sifatnya harian, angka hanya memengaruhi tanggal terjadinya transaksi atau kejadian kas pada tanggal tersebut.

Makna yang dikunci:

- gaji tanggal 1 hanya masuk ke tanggal 1
- biaya operasional tanggal 1 hanya masuk ke tanggal 1
- hutang karyawan dicatat pada tanggal pencairan hutang
- efek akumulasi baru muncul bila mode laporan dibaca mingguan atau bulanan

### 3. Definisi laba operasional

Definisi yang dipakai sekarang adalah **cash-based operational profit**, bukan gross margin model lama.

Rumus resmi:

- `cash_in_rupiah`
- dikurangi `refunded_rupiah`
- dikurangi `product_purchase_cost_rupiah`
- dikurangi `operational_expense_rupiah`
- dikurangi `payroll_disbursement_rupiah`
- dikurangi `employee_debt_cash_out_rupiah`

Dengan detail:

- `product_purchase_cost_rupiah = external_purchase_cost_rupiah + store_stock_cogs_rupiah`

### 4. Dashboard admin harus ikut definisi laporan

Kartu dashboard admin untuk laba bulanan tidak boleh memakai definisi lama yang berbeda dari halaman report.

Label dan wiring yang dikunci:

- dashboard memakai definisi `Laba Kas Operasional Bulan Ini`
- dashboard membaca key `cash_operational_profit_rupiah`
- test dashboard ikut definisi cash-based yang sama

### 5. Laporan Gaji adalah laporan admin resmi

Laporan gaji tidak lagi dianggap hanya bagian operasional karyawan. Laporan ini merupakan bagian dari reporting admin dan harus muncul di area laporan.

## Paket yang Diselesaikan

### Paket 1 — Filter reporting shared UI/UX

Selesai.

Hasil:

- komponen filter laporan bersama dirapikan
- mode aktif dan rentang aktif ditampilkan lebih jelas
- drawer filter dipakai secara konsisten di halaman laporan
- mode `custom` dihapus dari UI shared filter
- penjelasan perilaku daily / weekly / monthly disederhanakan dan diseragamkan

### Paket 2 — Operational Expense Report

Selesai.

Hasil:

- halaman laporan biaya operasional resmi tersedia
- request, DTO, controller, route, dan page test tersedia
- laporan ini membaca transaksi biaya berdasarkan tanggal biaya aktual

### Paket 3 — Payroll Report

Selesai.

Hasil:

- laporan gaji resmi tersedia di admin reporting
- hanya disbursement yang tidak di-reverse yang dihitung
- report terhubung ke sidebar admin
- page test dan dataset test tersedia

### Paket 4 — Supplier Payable hardening

Selesai.

Hasil:

- hutang supplier dievaluasi terhadap `reference_date`
- status jatuh tempo pada UI mengikuti tanggal referensi aktif
- test page dan summary diperbarui agar mengikuti kontrak reference date

### Paket 5 — Operational Profit summary hardening

Selesai.

Hasil:

- summary laba operasional dipindah ke definisi cash-based
- komponen payroll dan hutang karyawan ikut masuk ke cash out
- summary mengecualikan payroll reversal
- hardening test ditambahkan untuk parity dan exclusion case

### Paket 6 — Dashboard admin alignment

Selesai.

Hasil:

- dashboard admin diselaraskan ke definisi cash-based
- label dashboard diganti menjadi `Laba Kas Operasional Bulan Ini`
- wiring payload dashboard tidak lagi membaca metric laba lama
- test dashboard mengikuti angka cash-based

### Paket 7 — Kontrak backend non-custom

Selesai.

Hasil:

- request validator laporan tidak lagi menerima `custom`
- DTO query reporting dibersihkan dari cabang `custom`
- properti mati `dateFrom/dateTo` dihapus dari DTO reporting
- test page reporting yang masih memakai kontrak lama diselaraskan

### Paket 8 — Audit matematis laporan laba kas operasional

Selesai.

Hasil:

- audit manual pada periode non-refund berhasil
- audit manual pada periode refund berhasil
- parity manual vs output aplikasi berhasil untuk kedua periode

## File yang Diubah / Ditambahkan

### Reporting Request / DTO / Controller / Use Case / Service

- `app/Adapters/In/Http/Requests/Reporting/OperationalExpenseReportPageRequest.php`
- `app/Adapters/In/Http/Requests/Reporting/PayrollReportPageRequest.php`
- `app/Adapters/In/Http/Requests/Reporting/TransactionCashLedgerPageRequest.php`
- `app/Adapters/In/Http/Requests/Reporting/TransactionReportPageRequest.php`
- `app/Adapters/In/Http/Requests/Reporting/InventoryStockValueReportPageRequest.php`
- `app/Adapters/In/Http/Requests/Reporting/EmployeeDebtReportPageRequest.php`
- `app/Adapters/In/Http/Requests/Reporting/OperationalProfitReportPageRequest.php`
- `app/Adapters/In/Http/Requests/Reporting/SupplierPayableReportPageRequest.php`

- `app/Application/Reporting/DTO/OperationalExpenseReportPageQuery.php`
- `app/Application/Reporting/DTO/PayrollReportPageQuery.php`
- `app/Application/Reporting/DTO/TransactionCashLedgerPageQuery.php`
- `app/Application/Reporting/DTO/TransactionReportPageQuery.php`
- `app/Application/Reporting/DTO/InventoryStockValueReportPageQuery.php`
- `app/Application/Reporting/DTO/EmployeeDebtReportPageQuery.php`
- `app/Application/Reporting/DTO/OperationalProfitReportPageQuery.php`
- `app/Application/Reporting/DTO/SupplierPayableReportPageQuery.php`

- `app/Application/Reporting/DTO/PayrollReportRow.php`
- `app/Application/Reporting/DTO/OperationalProfitSummaryRow.php`

- `app/Application/Reporting/Services/PayrollReportRowBuilder.php`
- `app/Application/Reporting/Services/PayrollReportingReconciliationService.php`
- `app/Application/Reporting/Services/PayrollReportSummaryBuilder.php`
- `app/Application/Reporting/Services/PayrollReportPeriodBreakdownBuilder.php`
- `app/Application/Reporting/Services/PayrollReportModeBreakdownBuilder.php`

- `app/Application/Reporting/Services/OperationalProfitSummaryBuilder.php`
- `app/Application/Reporting/Services/OperationalProfitReportingReconciliationService.php`

- `app/Application/Reporting/UseCases/GetPayrollReportDatasetHandler.php`
- `app/Application/Reporting/UseCases/GetOperationalProfitSummaryHandler.php`
- `app/Application/Reporting/UseCases/AdminDashboardOverviewPayload.php`

- `app/Ports/Out/Reporting/PayrollReportingSourceReaderPort.php`
- `app/Adapters/Out/Reporting/DatabasePayrollReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/DatabaseOperationalProfitReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/Queries/OperationalProfitMetricsQuery.php`

### HTTP Controller / Route / Provider

- `app/Adapters/In/Http/Controllers/Admin/Reporting/OperationalExpenseReportPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Reporting/PayrollReportPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/AdminDashboardPageController.php`
- `routes/web/admin_reporting.php`
- `app/Providers/HexagonalServiceProvider.php`

### View / Shared UI / JS

- `resources/views/admin/reporting/partials/period_filter.blade.php`
- `public/assets/static/js/shared/admin-report-period-filter.js`

- `resources/views/admin/reporting/operational_expense/index.blade.php`
- `resources/views/admin/reporting/payroll/index.blade.php`
- `resources/views/admin/reporting/operational_profit/index.blade.php`
- `resources/views/admin/reporting/employee_debt/index.blade.php`
- `resources/views/admin/reporting/supplier_payable/index.blade.php`
- `resources/views/admin/reporting/transaction_cash_ledger/index.blade.php`
- `resources/views/admin/reporting/inventory_stock_value/index.blade.php`
- `resources/views/admin/reporting/transaction_summary/index.blade.php`

- `resources/views/admin/dashboard/index.blade.php`
- `resources/views/layouts/partials/sidebar-admin.blade.php`

### Test

- `tests/Feature/Reporting/OperationalExpenseReportPageFeatureTest.php`
- `tests/Feature/Reporting/GetOperationalExpenseReportDatasetFeatureTest.php`
- `tests/Feature/Reporting/GetOperationalExpenseSummaryFeatureTest.php`

- `tests/Feature/Reporting/PayrollReportPageFeatureTest.php`
- `tests/Feature/Reporting/GetPayrollReportDatasetFeatureTest.php`

- `tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php`
- `tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`
- `tests/Feature/Reporting/OperationalProfitSummaryHardeningFeatureTest.php`

- `tests/Feature/Reporting/EmployeeDebtReportPageFeatureTest.php`
- `tests/Feature/Reporting/SupplierPayableReportPageFeatureTest.php`
- `tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php`
- `tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php`
- `tests/Feature/Reporting/TransactionReportPageFeatureTest.php`

- `tests/Feature/Admin/AdminDashboardPageFeatureTest.php`

## Perubahan Perilaku yang Sekarang Berlaku

### 1. Period filter

Semua halaman reporting admin yang memakai shared filter sekarang tunduk pada kontrak berikut:

- pilih mode: harian, mingguan, bulanan
- isi `reference_date`
- sistem menghitung rentang aktif sendiri
- tidak ada lagi input `custom from-to`

### 2. Laporan Gaji

- halaman laporan gaji resmi tersedia
- hasil report hanya menghitung disbursement non-reversed
- menu laporan gaji muncul di sidebar admin pada area laporan

### 3. Laporan Biaya Operasional

- halaman biaya operasional resmi tersedia
- angka dibaca dari tanggal biaya aktual
- dapat dibaca dengan daily / weekly / monthly

### 4. Laba Kas Operasional

Summary resmi sekarang membaca:

- uang masuk kas
- refund kas keluar
- biaya pembelian produk eksternal
- COGS stok toko
- biaya operasional
- gaji yang benar-benar cair
- hutang karyawan yang keluar sebagai kas

### 5. Dashboard admin

Dashboard tidak lagi memajang kartu laba dengan definisi lama.

Sekarang:

- label kartu: `Laba Kas Operasional Bulan Ini`
- angka dashboard mengikuti halaman report cash-based

## Proof Verifikasi Teknis

Seluruh paket ini sudah ditutup dengan `make verify` hijau penuh.

Suite yang secara eksplisit dijalankan dan menjadi bagian dari closure slice ini:

### Reporting page / dataset / summary

- `Tests\Feature\Reporting\OperationalExpenseReportPageFeatureTest`
- `Tests\Feature\Reporting\GetOperationalExpenseReportDatasetFeatureTest`
- `Tests\Feature\Reporting\GetOperationalExpenseSummaryFeatureTest`

- `Tests\Feature\Reporting\PayrollReportPageFeatureTest`
- `Tests\Feature\Reporting\GetPayrollReportDatasetFeatureTest`

- `Tests\Feature\Reporting\OperationalProfitReportPageFeatureTest`
- `Tests\Feature\Reporting\GetOperationalProfitSummaryFeatureTest`
- `Tests\Feature\Reporting\OperationalProfitSummaryHardeningFeatureTest`

- `Tests\Feature\Reporting\EmployeeDebtReportPageFeatureTest`
- `Tests\Feature\Reporting\SupplierPayableReportPageFeatureTest`
- `Tests\Feature\Reporting\TransactionCashLedgerPageFeatureTest`
- `Tests\Feature\Reporting\InventoryStockValueReportPageFeatureTest`
- `Tests\Feature\Reporting\TransactionReportPageFeatureTest`

### Dashboard

- `Tests\Feature\Admin\AdminDashboardPageFeatureTest`

### Full verification

- `make verify`

Final state saat handoff ini ditulis:

- `558 passed`
- `2909 assertions`
- `make verify` hijau

## Proof Audit Matematis

### Audit 1 — Periode multi-komponen non-refund

Periode:

- `2026-04-07` s/d `2026-04-11`

Hasil audit manual:

- `cash_in_rupiah = 35.734.305`
- `refunded_rupiah = 0`
- `external_purchase_cost_rupiah = 3.142.500`
- `store_stock_cogs_rupiah = 9.505.323`
- `product_purchase_cost_rupiah = 12.647.823`
- `operational_expense_rupiah = 6.666.250`
- `payroll_disbursement_rupiah = 13.470.000`
- `employee_debt_cash_out_rupiah = 2.600.000`
- `cash_operational_profit_rupiah = 350.232`

Validasi manual:

- `3.142.500 + 9.505.323 = 12.647.823`
- `35.734.305 - 0 - 12.647.823 - 6.666.250 - 13.470.000 - 2.600.000 = 350.232`

Paritas aplikasi:

- output handler `GetOperationalProfitSummaryHandler` untuk periode yang sama match 100% dengan angka manual

### Audit 2 — Periode refund

Periode:

- `2026-03-28` s/d `2026-03-30`

Hasil audit manual:

- `cash_in_rupiah = 22.378.405`
- `refunded_rupiah = 63.093`
- `external_purchase_cost_rupiah = 1.667.000`
- `store_stock_cogs_rupiah = 6.084.728`
- `product_purchase_cost_rupiah = 7.751.728`
- `operational_expense_rupiah = 5.203.750`
- `payroll_disbursement_rupiah = 0`
- `employee_debt_cash_out_rupiah = 900.000`
- `cash_operational_profit_rupiah = 8.459.834`

Validasi manual:

- `1.667.000 + 6.084.728 = 7.751.728`
- `22.378.405 - 63.093 - 7.751.728 - 5.203.750 - 0 - 900.000 = 8.459.834`

Paritas aplikasi:

- output handler `GetOperationalProfitSummaryHandler` untuk periode yang sama match 100% dengan angka manual

## Status Kepercayaan Angka Saat Diserahkan

Untuk scope summary `Laba Kas Operasional`, status kepercayaan saat handoff ini diserahkan adalah:

- test otomatis: pass
- verify teknis: pass
- audit manual non-refund: pass
- audit manual refund: pass
- parity manual vs aplikasi: pass

Kesimpulan:

- angka summary `Laba Kas Operasional` pada scope ini sudah lolos validasi teknis dan validasi matematis

## Risiko / Catatan Penting

### 1. Tidak semua komponen aktif pada semua periode

Data riil menunjukkan:

- payroll non-reversed hanya ada pada window tertentu
- refund aktif pada window lain
- employee debt aktif pada window tertentu

Artinya audit lintas waktu harus selalu memakai periode yang benar-benar punya data pada komponen yang hendak diuji.

### 2. Audit ini membuktikan summary, bukan seluruh tabel turunan UI

Yang sudah dibuktikan penuh di handoff ini adalah:

- summary cash-based operational profit

Yang belum diaudit sedalam itu:

- seluruh breakdown / grouping / formatting UI pada semua halaman laporan
- cross-page reconciliation lain di luar scope ini

### 3. Kontrak non-custom sudah final di slice ini

Karena UI dan backend sudah dibersihkan dari `custom`, semua pekerjaan lanjutan di reporting V2 harus mengikuti kontrak:

- daily
- weekly
- monthly
- reference_date sebagai titik referensi tunggal

## Pending / Belum Dikerjakan

Tidak ada blocker aktif untuk slice ini.

Yang tersisa hanya pekerjaan lanjutan opsional, bukan debt blocker:

1. perluasan audit manual ke laporan lain bila dibutuhkan
2. audit detail row parity untuk seluruh halaman selain summary laba
3. packaging release note / merge summary bila branch siap digabung

## Langkah Lanjutan yang Paling Aman

Urutan lanjutan paling aman setelah handoff ini:

1. pertahankan kontrak reporting V2 tanpa membuka kembali `custom`
2. bila ingin audit tambahan, lakukan parity manual pada report lain yang paling kritikal
3. siapkan merge summary untuk penutupan branch bila semua stakeholder sudah setuju

## Ringkasan Keadaan Saat Diserahkan

- paket reporting repair V2 selesai
- semua verifikasi teknis hijau
- dashboard admin sudah sinkron dengan laporan cash-based
- laporan gaji dan biaya operasional resmi tersedia
- filter reporting sudah disederhanakan
- laba kas operasional sudah terbukti benar secara manual pada data riil

## Snapshot Singkat

### Sudah jadi

- period filter non-custom
- payroll report
- operational expense report
- supplier payable reference-date aware
- cash-based operational profit
- dashboard admin cash-based
- sidebar laporan gaji
- verify hijau
- audit manual summary laba pass

### Tidak ada blocker aktif

- tidak ada fail test
- tidak ada fail verify
- tidak ada mismatch audit summary laba
