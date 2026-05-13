HANDOFF
Dashboard Admin - penambahan 4 chart analytics + refactor urutan layout

STATUS
Selesai untuk scope yang diminta pengguna.

SCOPE YANG SELESAI
1. Tambah payload analytics baru di dashboard admin tanpa merusak payload lama.
2. Tambah 4 chart baru untuk dashboard admin.
3. Ganti renderer chart ke ApexCharts agar visual lebih sesuai gaya Mazer.
4. Refactor posisi section di halaman dashboard sesuai urutan final dari pengguna.
5. Pertahankan section lama yang diminta tetap ada.
6. Hapus wrapper besar "Analitik Dashboard Bulan Ini".
7. Hapus list data di bawah chart dari tampilan visual.
8. Jadikan chart kiri bawah sebagai "Laba Operasional Bulan Ini".
9. Tebalkan batang chart dan buat warna lebih kaya.

SCOPE FINAL YANG DIKUNCI
Urutan layout final di halaman dashboard:
1. Hero kiri atas:
   "Laporan stok, aset, penjualan, harga, dan perputaran keuangan dalam satu layar."
   Kanan atas: profil admin.
2. Empat stat card:
   - Total Qty On Hand
   - Nilai Persediaan
   - Uang Masuk Hari Ini
   - Laba Bulan Ini
3. Posisi Keuangan Bulan Ini
4. Ringkasan Posisi Bulan Ini
5. Row:
   - kiri: Barang Paling Laku
   - kanan: Top Produk Terjual Bulan Ini
6. Row:
   - kiri: Distribusi Status Stok
   - kanan: Prioritas Restok
7. Status Stok Saat Ini
8. Row paling bawah:
   - kiri: Laba Operasional Bulan Ini
   - kanan: Kinerja Operasional Bulan Ini

FAKTA YANG SUDAH TERKUNCI
1. Payload baru analytics hidup di:
   dashboard['analytics']
2. Payload lama dashboard tetap ada dan tidak diubah kontraknya:
   - hero
   - stats
   - finance
   - top_selling_rows
   - restock_priority_rows
   - position
3. Window analytics untuk chart baru:
   - month_to_date
   - from = tanggal 1 bulan aktif
   - to = hari ini
   - granularity = daily
4. Scope aturan ini hanya berlaku untuk chart/statistik baru yang ditambahkan pada pekerjaan ini, bukan untuk seluruh dashboard lama.
5. ApexCharts dipakai dari asset lokal repo, bukan CDN.

KEPUTUSAN DESAIN / TEKNIS
1. Renderer custom SVG dibuang dari jalur utama, diganti ke ApexCharts.
2. Chart baru tetap membaca payload analytics baru.
3. Dashboard lama tidak dijadikan dasar refactor domain.
4. Status Stok Saat Ini tetap dipertahankan.
5. Wrapper besar analytics dihapus, chart dipasang langsung di posisi final.
6. Chart kiri bawah secara visual sekarang adalah "Laba Operasional Bulan Ini".
7. Data list di bawah chart tidak dipakai lagi pada tampilan.

FILE YANG DIBUAT
1. app/Application/Reporting/UseCases/AdminDashboardAnalyticsPeriod.php
2. app/Application/Reporting/UseCases/GetAdminDashboardAnalyticsHandler.php
3. app/Application/Reporting/UseCases/GetAdminDashboardPagePayloadHandler.php
4. app/Application/Reporting/UseCases/AdminDashboardAnalyticsPayloadBuilder.php
5. app/Application/Reporting/UseCases/GetDashboardOperationalPerformanceDatasetHandler.php
6. app/Application/Reporting/UseCases/Charts/BuildStockStatusDonutChart.php
7. app/Application/Reporting/UseCases/Charts/BuildTopSellingBarChart.php
8. app/Application/Reporting/UseCases/Charts/BuildCashflowLineChart.php
9. app/Application/Reporting/UseCases/Charts/BuildOperationalPerformanceBarChart.php
10. app/Ports/Out/Reporting/DashboardOperationalPerformanceReaderPort.php
11. app/Adapters/Out/Reporting/DatabaseDashboardOperationalPerformanceReaderAdapter.php
12. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformancePeriodQuery.php
13. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/CashInPerDayQuery.php
14. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/RefundPerDayQuery.php
15. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/ExternalPurchaseCostPerDayQuery.php
16. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/StoreStockCogsPerDayQuery.php
17. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/OperationalExpensePerDayQuery.php
18. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/PayrollDisbursementPerDayQuery.php
19. app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/EmployeeDebtCashOutPerDayQuery.php
20. public/assets/static/js/admin/dashboard-analytics.js

FILE YANG DIUBAH
1. app/Adapters/In/Http/Controllers/Admin/AdminDashboardPageController.php
2. app/Providers/HexagonalServiceProvider.php
3. resources/views/admin/dashboard/index.blade.php

FILE BACKUP
1. resources/views/admin/dashboard/index.blade.php.bak-20260417-175249

VERIFIKASI YANG SUDAH LOLOS
1. php -l lolos untuk file fondasi payload analytics.
2. php -l lolos untuk file source operational performance.
3. php -l lolos untuk chart builders.
4. php -l lolos untuk controller dan provider setelah wiring.
5. php artisan tinker berhasil memverifikasi payload dashboard root:
   - ada analytics
   - ada period
   - ada charts
   - ada 4 chart key baru
6. node --check lolos untuk:
   public/assets/static/js/admin/dashboard-analytics.js
7. View render sempat gagal karena number_format salah argumen, sudah diperbaiki.
8. Verifikasi terakhir:
   - analytics_wrapper=missing
   - wrapper besar analytics sudah hilang
   - Blade berhasil render lagi
9. Pengguna sudah konfirmasi visual: "ok beres".

KONTRAK PAYLOAD ANALYTICS
Root:
dashboard['analytics']

Isi:
- period
- charts.stock_status_donut
- charts.top_selling_bar
- charts.cashflow_line
- charts.operational_performance_bar

CATATAN TEKNIS PENTING
1. Secara visual, chart kiri bawah berjudul "Laba Operasional Bulan Ini".
2. Secara teknis, container kiri bawah masih memakai id:
   admin-chart-cashflow-line
   dan renderer JS mengisinya memakai data operational_performance_bar.
   Ini tidak memblok pekerjaan saat ini, tetapi termasuk technical debt kecil yang aman dibersihkan nanti.
3. Payload cashflow_line masih ada di backend walau tidak lagi dipakai untuk chart kiri bawah pada layout final.
4. Backup file Blade lama masih ada dan aman untuk rollback manual.

BLOCKER
Tidak ada blocker untuk scope yang diminta pengguna.

TECHNICAL DEBT / CLEANUP OPSIONAL
1. Rapikan naming container kiri bawah agar selaras dengan isi visual.
2. Hapus payload cashflow_line jika memang diputuskan tidak lagi dipakai.
3. Hapus file backup Blade bila sudah tidak dibutuhkan.
4. Rapikan kemungkinan CSS yang sekarang tidak lagi dipakai setelah wrapper analytics dihapus.

SAFEST NEXT STEP
Pisahkan cleanup kecil sebagai task terpisah, jangan dicampur ke scope ini:
1. rename container cashflow -> operational profit area
2. evaluasi apakah payload cashflow_line tetap dipertahankan atau dihapus
3. screenshot regression check light/dark
4. cleanup backup file setelah final approval

PROGRESS
1. Backend analytics baru: 100%
2. Integrasi chart ApexCharts: 100%
3. Refactor posisi layout sesuai permintaan: 100%
4. Scope fitur yang diminta pengguna: 100%

RINGKASAN PENUTUP
Fitur dashboard admin untuk chart baru, posisi layout baru, dan tampilan visual gaya Mazer-lite sudah selesai untuk scope yang diminta. Tidak ada perubahan domain besar. Payload lama tetap aman. Handoff ini menutup pekerjaan saat ini.