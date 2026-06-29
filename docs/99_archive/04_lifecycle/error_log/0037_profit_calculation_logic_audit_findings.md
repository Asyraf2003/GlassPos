# 0037 - Profit calculation logic audit findings

Status: Audit Findings / Owner Decision Gap Logged
Keparahan: Medium
Klasifikasi: reporting clarity / service package profit breakdown / profit basis boundary

## Boundary Decision

Operational Profit saat ini tetap sah sebagai laporan kas operasional periodik.

Audit ini tidak mengubah formula Operational Profit.

Evidence:

- Laporan Operational Profit ditampilkan sebagai `Laba Kas Operasional`: `resources/views/admin/reporting/operational_profit/index.blade.php:4`
- UI menjelaskan laporan ini berbasis kas: `resources/views/admin/reporting/operational_profit/index.blade.php:12`
- UI menjelaskan formula kas operasional sebagai uang masuk dikurangi pengembalian dana, harga beli produk, biaya operasional, gaji, dan hutang karyawan: `resources/views/admin/reporting/operational_profit/index.blade.php:15`
- Query summary menghitung `cash_operational_profit_rupiah` dari `cash_in - refund - product_purchase_cost - operational_expense - payroll - employee_debt_cash_out`: `app/Adapters/Out/Reporting/Queries/OperationalProfitMetricsQuery.php:42`
- Formula Operational Profit source reader tetap memakai `OperationalProfitMetricsQuery`: `app/Adapters/Out/Reporting/DatabaseOperationalProfitReportingSourceReaderAdapter.php:17`

## Owner Decision

Untuk kebutuhan owner melihat keuntungan paket service secara akurat, perlu report atau section terpisah:

Service Package Profit Breakdown.

Breakdown minimal:

- `package_total_rupiah`
- `sparepart_sales_total_rupiah`
- `sparepart_cogs_rupiah`
- `sparepart_margin_rupiah`
- `base_service_price_rupiah`
- `package_service_extra_rupiah`
- `package_profit_rupiah`
- `total_service_component_rupiah`
- `total_package_gross_profit_rupiah`

## Current Source Reality

Paket service dan sparepart sudah memiliki sebagian sumber data, tetapi belum ada report terpisah yang menyajikan breakdown keuntungan paket service secara eksplisit.

Evidence:

- Request transaksi menerima `pricing_mode` dengan nilai `manual_split` atau `package_auto_split`: `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:26`
- Request transaksi menerima `package_total_rupiah`: `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:28`
- Auto-split paket service + stok toko membaca `package_total_rupiah`: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitComposer.php:26`
- Auto-split paket service + stok toko menghitung `sparepartTotal` dari product lines: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitComposer.php:34`
- Product lines composer menghitung `sparepart_total_rupiah` dari harga jual produk dikali qty: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer.php:31`
- Product lines composer mengembalikan `sparepart_total_rupiah`: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer.php:40`
- Template package branch mengisi `package_profit_rupiah`, `package_base_service_price_rupiah`, dan `package_service_extra_rupiah`: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:38`
- Service detail menyimpan `package_profit_rupiah`, `package_base_service_price_rupiah`, dan `package_service_extra_rupiah`: `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php:35`
- Store-stock COGS untuk profit saat ini berasal dari `inventory_movements` stock out dan reversal: `app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php:31`
- Inventory issue memakai current average cost dari `product_inventory_costing`: `app/Application/Inventory/Services/IssueInventoryOperation.php:38`

## Reporting Clarity Gap

Operational Profit tidak dimaksudkan untuk menjawab pertanyaan detail keuntungan paket service per paket atau per komponen.

Gap yang perlu ditutup oleh report/section baru:

- `package_total_rupiah` adalah input request pada saat transaksi, tetapi perlu dipastikan apakah sudah tersedia sebagai snapshot historis yang stabil untuk report.
- `sparepart_sales_total_rupiah` dapat diturunkan dari store-stock line totals, tetapi report harus memastikan hanya komponen paket service yang dihitung.
- `sparepart_cogs_rupiah` harus ditarik dari inventory movement yang terkait store-stock line paket, bukan dari current product average cost saat report dibuka.
- `sparepart_margin_rupiah` harus dihitung sebagai sparepart sales minus sparepart COGS.
- `base_service_price_rupiah`, `package_service_extra_rupiah`, dan `package_profit_rupiah` tersedia pada service detail untuk branch package-template, tetapi branch non-template dapat bernilai null atau 0 sesuai composer.
- `total_service_component_rupiah` perlu definisi final: apakah hanya base service + service extra + package profit, atau mengikuti service detail total yang sudah dipersist.
- `total_package_gross_profit_rupiah` perlu definisi final: minimal kandidat formula adalah `sparepart_margin_rupiah + total_service_component_rupiah`.

## Non-Goal

- Jangan ubah formula Operational Profit dulu.
- Jangan mengubah payment proof supplier invoice.
- Jangan menghidupkan Mobile API.
- Jangan membuat `routes/api.php`.
- Jangan mengubah schema sebelum data source breakdown final dikunci.

## Patch Recommendation

Belum eksekusi.

1. Tambahkan characterization query/test untuk satu paket service + store-stock part yang membuktikan semua komponen breakdown dapat diambil dari snapshot historis.
2. Jika `package_total_rupiah` belum tersimpan sebagai snapshot historis yang stabil, owner perlu memutuskan apakah report menghitung ulang dari komponen historis atau menambah snapshot baru pada lifecycle transaksi berikutnya.
3. Buat report/section `Service Package Profit Breakdown` terpisah dari Operational Profit.
4. Pastikan `sparepart_cogs_rupiah` memakai inventory movement source yang terkait line paket, bukan current avg cost.

## Test Recommendation

- Test paket service template dengan satu sparepart stok toko.
- Test paket service non-template dengan sparepart stok toko.
- Test paket service multi-sparepart jika flow ini didukung.
- Test refund/retur paket service untuk membuktikan sales, COGS, margin, dan gross profit tidak double count.
- Test perubahan harga produk/modal setelah nota agar report tetap memakai snapshot/movement historis.
- Test periode laporan lintas tanggal transaksi, payment, dan inventory movement agar report basis tanggal dikunci eksplisit.
