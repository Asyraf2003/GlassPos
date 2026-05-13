# Handoff V2 - Product Threshold, Dashboard Stock Wiring, and Seeder Load Hardening

## Status

Selesai untuk slice lanjutan `product`, `dashboard admin`, dan `seeder/load`.

Status akhir:
- wiring dashboard stok admin: selesai
- panel `Prioritas Restok`: selesai
- link aksi dari dashboard ke detail product: selesai
- threshold product di create/edit/show: selesai
- default threshold saat create: selesai
- split seeder product 2 fase: selesai
- hardening rerun-safe seeder baseline/load: selesai
- threshold deterministic untuk monster/load product: selesai
- backfill threshold untuk snapshot product lama: selesai
- verifikasi repo: hijau
- handoff-ready: ya

## Ringkasan

Slice ini menutup rangkaian pekerjaan yang berawal dari dashboard admin yang masih menampilkan panel stok kosong, product threshold yang belum hidup penuh, dan jalur seeder product/load yang belum konsisten setelah kontrak threshold ditambahkan ke handler.

Hasil akhirnya:

- dashboard stok admin sudah memakai data nyata dan tidak lagi berhenti di `Belum Diatur` massal
- panel `Prioritas Restok` sudah hidup dan punya link ke detail product
- product threshold sudah dipakai konsisten di create, edit, detail, dan seed/load
- seeder product sudah resmi dipisah menjadi baseline vs monster/load
- rerun seed untuk baseline dan load sekarang tidak lagi jatuh di duplicate yang sama
- verify repo kembali hijau

## Tujuan yang Ditutup

Menutup tiga kelompok masalah sekaligus dalam satu halaman kerja:

1. menghidupkan dashboard stok admin sampai benar-benar memakai data nyata
2. menutup kontrak threshold product dari UI sampai backend dan seeder
3. merapikan dan mengeraskan jalur seeder baseline vs load agar aman diulang

## Scope In

- dashboard admin stock status
- dashboard restock priority
- dashboard top selling wiring yang menjadi dasar panel lanjutan
- contract `reorder_point_qty` dan `critical_threshold_qty`
- wording threshold di halaman product
- default threshold di form create product
- reporting payload/dashboard payload untuk stock panel
- inventory snapshot filtering pada reporting
- product seeder baseline
- product seeder monster/load
- idempotency seeder baseline yang bentrok saat rerun
- backfill threshold untuk snapshot product aktif

## Scope Out

- grafik dashboard
- margin dashboard
- slow moving report
- inventory seeder monster untuk `PRD-V2-*`
- perubahan domain baru di luar product/reporting/seeder
- sinkronisasi database hosting operasional
- restore soft delete product

## Keputusan yang Dikunci

### 1. Dashboard stok tetap membaca snapshot inventory, bukan seluruh master product

Panel stok dashboard tetap dihitung dari universe product yang punya snapshot inventory/costing.

Artinya:
- product master tanpa row `product_inventory` atau `product_inventory_costing` tidak ikut panel stok
- dashboard stok tetap dibaca sebagai status stok saat ini, bukan daftar seluruh barang master

### 2. Product threshold resmi memakai dua field

Kontrak yang dipakai penuh sekarang:

- `reorder_point_qty`
- `critical_threshold_qty`

Makna UI yang dikunci:
- `reorder_point_qty` = `Mulai Perlu Restok`
- `critical_threshold_qty` = `Batas Stok Kritis`

### 3. Default create product diisi, tetapi tetap editable

Halaman create product sekarang default terisi:

- `Mulai Perlu Restok = 5`
- `Batas Stok Kritis = 3`

Aturan resmi:
- nilai default hanya prefill
- user tetap boleh mengganti saat create
- user tetap boleh mengganti lagi saat edit
- jika submit gagal, `old()` tetap menang

### 4. Split seeder product resmi menjadi 2 fase

Struktur resmi sekarang:

- baseline:
  - `DatabaseSeeder`
  - `ProductSeeder`

- monster/load:
  - `DatabaseLoadSeeder`
  - `Load/ProductLoadSeeder`

`DatabaseSeederV2` dipensiunkan dan diganti nama menjadi `DatabaseLoadSeeder`.

### 5. Product load wajib idempotent

`ProductLoadSeeder` tidak boleh lagi hanya create.

Aturan yang dikunci:
- jika product monster belum ada, create
- jika product monster sudah ada, update master
- aturan ini berlaku untuk:
  - `PRD-V2-ACT-*`
  - `PRD-V2-EDT-*`

### 6. Monster/load product wajib punya threshold deterministic

Rule resmi yang dipakai di `ProductLoadSeeder`:

- `index % 3 == 0`
  - `reorder_point_qty = 5`
  - `critical_threshold_qty = 2`

- `index % 3 == 1`
  - `reorder_point_qty = 8`
  - `critical_threshold_qty = 3`

- `index % 3 == 2`
  - `reorder_point_qty = 12`
  - `critical_threshold_qty = 5`

Rule ini dipilih agar:
- deterministic
- cukup variatif untuk demo dashboard/reporting
- tetap pendek dan mudah diaudit

### 7. Seeder baseline yang mutatif harus skip bila sudah pernah jalan

Seeder baseline berikut sekarang dibuat skip total bila data baseline sejenis sudah ada:

- `ExpenseSeeder`
- `FinancialCorrectionSeeder`

Tujuannya:
- mencegah duplicate insert
- mencegah mutasi saldo/riwayat berulang saat rerun seed load

### 8. Snapshot stock dashboard tidak boleh menghitung product soft delete

`InventoryCurrentSnapshotDatabaseQuery` sekarang mengecualikan `products.deleted_at is not null`.

Akibatnya:
- product soft delete yang masih punya histori inventory tidak lagi ikut summary dashboard stok

### 9. Produk snapshot lama yang masih null threshold dibackfill terbatas

Ditambahkan `ProductInventoryThresholdBackfillSeeder` dengan aturan:

- hanya menyentuh product aktif
- threshold masih null
- sudah punya `product_inventory` atau `product_inventory_costing`

Nilai backfill default:
- `reorder_point_qty = 5`
- `critical_threshold_qty = 3`

Tujuan backfill ini:
- menghidupkan dashboard stok yang memang membaca snapshot lama
- menurunkan `Belum Diatur` tanpa mengubah seluruh universe master product

## Hasil Akhir yang Sudah Jadi

### 1. Dashboard stock status hidup

Panel `Status Stok Saat Ini` sudah hidup memakai payload real dari reporting.

Bucket yang dipakai:
- `Stok Aman`
- `Mulai Perlu Restok`
- `Stok Kritis`
- `Belum Diatur`

### 2. Dashboard restock priority hidup

Panel `Prioritas Restok` sudah hidup.

Isi panel:
- hanya product status `critical` dan `low`
- ada empty state yang jujur
- ada tombol `Lihat Detail`
- tombol menuju route detail product admin

### 3. Dashboard stock tidak lagi menghitung product deleted

Sisa `Belum Diatur` yang tadinya berasal dari product soft delete berhasil dibersihkan dari snapshot dashboard.

### 4. Wording threshold product lebih mudah dipahami

Halaman berikut sudah memakai wording yang lebih manusiawi:
- create product
- edit product
- show/detail product

Istilah yang dipakai:
- `Mulai Perlu Restok (Reorder Point)`
- `Batas Stok Kritis`

### 5. Default threshold create sudah aktif

Form create product sekarang tidak lagi kosong untuk threshold.

Default:
- reorder point `5`
- critical threshold `3`

### 6. Seeder product 2 fase resmi

Jalur seeder sekarang jelas:

#### Baseline
- `DatabaseSeeder`
- `ProductSeeder`

#### Load / monster
- `DatabaseLoadSeeder`
- `Load/ProductLoadSeeder`

### 7. Product scenario seeders sudah kompatibel dengan kontrak handler baru

Semua caller seeder product yang memakai:
- `CreateProductHandler`
- `UpdateProductHandler`

sudah diadaptasi ke signature baru yang mencakup threshold.

Untuk baseline scenario, threshold sementara dipassing `null`.

### 8. Product load threshold sudah terpasang ke existing monster data

Setelah `ProductLoadSeeder` dibuat idempotent, rerun load berhasil memperbarui product monster existing.

Hasil cek data:
- total `PRD-V2-*` = 300
- configured `PRD-V2-*` = 300

### 9. Dashboard snapshot lama berhasil dibackfill

`ProductInventoryThresholdBackfillSeeder` berhasil mengisi threshold untuk 37 product snapshot aktif yang tadinya null.

### 10. Dashboard stock final sudah bersih

Setelah backfill dan filter soft delete di query snapshot, distribusi akhir yang terbukti:

- `safe = 36`
- `low = 0`
- `critical = 1`
- `unconfigured = 0`

## Masalah yang Ditemukan dan Sudah Ditutup

### 1. Panel restock link sempat ditempel di blok yang salah

Masalah:
- tombol `Lihat Detail` sempat masuk ke panel top sales, bukan restock priority

Perbaikan:
- anchor dipindahkan ke blok `restock_priority_rows`

### 2. Test reporting dataset masih mengunci shape lama

Masalah:
- dataset snapshot reporting belum mengantisipasi field:
  - `reorder_point_qty`
  - `critical_threshold_qty`

Perbaikan:
- test diperbarui agar sinkron dengan kontrak dataset baru
- summary stock classification juga ikut dikunci

### 3. PHPStan protes pada `DashboardRestockPriorityRows`

Masalah:
- akses array memakai `??` pada key yang sebenarnya selalu ada

Perbaikan:
- akses array dibersihkan agar lolos static analysis

### 4. `AdminDashboardOverviewPayload.php` melewati limit baris

Masalah:
- audit-lines menolak file > 100 baris

Perbaikan:
- formatting `top_selling_rows` dipindah ke concern kecil:
  - `FormatsAdminDashboardTopSellingRows`

### 5. Product seeder pecah setelah signature handler berubah

Masalah:
- `CreateProductHandler` dan `UpdateProductHandler` sudah minta threshold
- scenario seeders masih memakai kontrak lama

Perbaikan:
- semua caller scenario seeders dipatch agar kompatibel

### 6. Soft delete product snapshot pecah karena row select belum membawa threshold

Masalah:
- `SoftDeletesProducts` mengambil row tanpa kolom threshold
- `toDeletedSnapshot()` mencoba membaca kolom yang tidak ikut dipilih

Perbaikan:
- select soft delete ditambah:
  - `reorder_point_qty`
  - `critical_threshold_qty`

### 7. `ExpenseSeeder` tidak idempotent

Masalah:
- rerun load memicu duplicate `expense_categories.code`

Perbaikan:
- `ExpenseSeeder` sekarang skip total bila baseline expense categories sudah ada

### 8. `FinancialCorrectionSeeder` tidak idempotent

Masalah:
- rerun load memicu duplicate reversal dan adjustment yang mutatif

Perbaikan:
- `FinancialCorrectionSeeder` sekarang skip total bila correction baseline sudah ada

### 9. Dashboard stock tetap kosong walau monster threshold sudah hidup

Masalah:
- `PRD-V2-*` memang punya threshold, tetapi tidak punya row:
  - `product_inventory`
  - `product_inventory_costing`

Akibat:
- dashboard stok tetap membaca product snapshot lama, bukan monster products

Perbaikan yang dipilih pada slice ini:
- bukan mengubah query dashboard menjadi semua master product
- bukan memaksa monster products masuk snapshot
- melainkan backfill threshold pada snapshot lama yang memang dibaca dashboard

### 10. Sisa `Belum Diatur = 2` ternyata berasal dari product soft delete

Masalah:
- product deleted masih ikut query snapshot

Perbaikan:
- query snapshot stok ditambah `whereNull('products.deleted_at')`

## File / Area yang Disentuh

### Reporting / Dashboard
- `app/Application/Reporting/UseCases/ReportingResultDataExtractor.php`
- `app/Application/Reporting/UseCases/DashboardRestockPriorityRows.php`
- `app/Application/Reporting/UseCases/AdminDashboardOverviewPayloadBuilder.php`
- `app/Application/Reporting/UseCases/AdminDashboardOverviewPayload.php`
- `app/Application/Reporting/UseCases/Concerns/FormatsAdminDashboardTopSellingRows.php`
- `app/Adapters/Out/Reporting/InventoryCurrentSnapshotDatabaseQuery.php`
- `resources/views/admin/dashboard/index.blade.php`

### Product UI / Product Detail
- `resources/views/admin/products/create.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/admin/products/show.blade.php`

### Product Writer / Soft Delete Compatibility
- `app/Adapters/Out/ProductCatalog/Concerns/ProductWritePayloads.php`
- `app/Adapters/Out/ProductCatalog/Concerns/SoftDeletesProducts.php`

### Seeder / Load / Backfill
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/DatabaseLoadSeeder.php`
- `database/seeders/Load/ProductLoadSeeder.php`
- `database/seeders/ProductInventoryThresholdBackfillSeeder.php`
- `database/seeders/ExpenseSeeder.php`
- `database/seeders/FinancialCorrectionSeeder.php`
- `database/seeders/Product/ProductScenarioActiveBasicSeeder.php`
- `database/seeders/Product/ProductScenarioEditedSeeder.php`
- `database/seeders/Product/ProductScenarioSoftDeletedSeeder.php`
- `database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php`
- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`

### Test
- `tests/Feature/Admin/AdminDashboardPageFeatureTest.php`
- `tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php`
- `tests/Feature/ProductCatalog/ProductDetailPageFeatureTest.php`
- `tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php`

### Doc Reference yang Disinkronkan
- `docs/handoff/v2/06-employee-finance-handoff-v2.md`

## Proof / Verifikasi yang Sudah Lolos

### 1. Dashboard admin
- panel stock status tampil
- panel restock priority tampil
- detail link tampil
- dashboard feature test hijau

### 2. Reporting dataset
- snapshot dataset dan summary report sudah sinkron dengan field threshold baru
- reporting dataset feature test hijau

### 3. Product create page
- default threshold create aktif
- product create page feature test hijau

### 4. Seeder runtime
Berhasil:
- `php artisan db:seed --class=DatabaseLoadSeeder --no-interaction`
- `ProductInventoryThresholdBackfillSeeder`
- rerun `DatabaseLoadSeeder` setelah guard baseline aktif

### 5. Data proof monster product
Hasil cek:
- `total_prd_v2 = 300`
- `configured_prd_v2 = 300`

### 6. Data proof dashboard snapshot
Hasil cek akhir:
- `safe = 36`
- `low = 0`
- `critical = 1`
- `unconfigured = 0`

### 7. Repo verification
- `make verify` hijau
- `LAST_EXIT=0`

## Catatan Penting Operasional

### 1. Dashboard stok saat ini belum membaca monster inventory

Walaupun `PRD-V2-*` sudah punya threshold, product monster itu saat slice ini ditutup masih belum punya row di:
- `product_inventory`
- `product_inventory_costing`

Artinya dashboard stok saat ini terutama hidup dari snapshot product lama yang sudah dibackfill threshold, bukan dari monster stock snapshot.

### 2. Zip root repo aman dari sisi kode, tetapi database hosting harus sinkron

Untuk deploy ke hosting:
- kode repo sudah bersih
- tetapi hasil dashboard hosting tetap bergantung pada state DB hosting

Minimum yang harus sinkron bila ingin hasil dashboard sama:
- schema threshold product
- query snapshot terbaru
- data threshold/backfill yang relevan

## Batasan yang Masih Tersisa

- monster product `PRD-V2-*` belum punya inventory/costing seeder sendiri
- dashboard stock belum menunjukkan distribusi `low` yang kaya, karena snapshot aktif sekarang hampir semua masih aman dan satu kritis
- belum ada seeder khusus untuk membuat monster stock snapshot yang benar-benar ikut dashboard
- grafik / margin / slow moving dashboard belum disentuh di halaman ini

## Safest Next Step

Urutan paling aman setelah handoff ini:

1. buat seeder/load untuk:
   - `product_inventory`
   - `product_inventory_costing`
   khusus `PRD-V2-*`

2. buat distribusi qty on hand yang sengaja menghasilkan:
   - aman
   - low
   - critical
   pada monster products

3. baru setelah itu kalau perlu lanjut ke:
   - slow moving
   - margin
   - trend dashboard

## Ringkasan Satu Kalimat

Slice ini menutup wiring dashboard stok admin, mengaktifkan threshold product sampai end-to-end, memisahkan seeder product menjadi baseline vs load, mengeraskan jalur rerun seed agar aman, dan menormalkan dashboard stok sampai `unconfigured = 0` pada snapshot aktif yang benar-benar dibaca dashboard.
