# Handoff V2 - Product

## Status
Selesai untuk scope page/domain `product`.

Status akhir:
- backend `product`: selesai
- detail/versioning `product`: selesai
- edit identitas vs ubah stok: sudah dipisah halaman
- filter status product: selesai
- seeder skenario `product`: selesai
- restore soft delete: belum ada
- handoff-ready: ya

## Tujuan yang ditutup
Menutup domain/page `product` sampai siap dipakai admin dan siap jadi basis laporan, dengan scope:
- create product
- edit identitas product
- ubah stok keluar operasional
- soft delete product
- detail product + versioning
- filter active / soft deleted / all
- seeder deterministik yang menutup skenario utama product

## Scope in
- product master backend
- product table / filter / action modal
- detail product
- edit identitas product
- ubah stok product
- versioning + audit trail product
- seeder product 50 data berbasis skenario
- keyboard flow form product master

## Scope out
- restore soft delete
- bulk action product
- import/export product
- laporan product khusus
- styling polish final Mazer yang lebih halus
- automation/backfill restore untuk histori lama di luar skenario legacy yang disengaja

## Keputusan yang dikunci
1. Soft delete product tetap menyimpan histori dan tidak menghapus jejak audit.
2. Product aktif harus unik berdasarkan:
   - `kode_barang`
   - business identity `nama_barang_normalized + merek_normalized + ukuran`
   hanya untuk row aktif.
3. Recreate setelah soft delete diperbolehkan.
4. Detail product harus jujur:
   - jika ada `product_created`, tampilkan identitas awal
   - jika tidak ada `product_created`, jangan mengaku tahu data awal asli
5. Edit identitas dan ubah stok dipisah menjadi dua halaman berbeda.
6. Filter status product memakai mode eksplisit:
   - `active`
   - `deleted`
   - `all`
7. Seeder product diganti dari generator random besar menjadi skenario deterministik 50 data.

## Hasil akhir yang sudah jadi

### 1. Backend product
Sudah aktif dan lolos smoke test untuk:
- create
- update identitas
- search/list
- soft delete
- recreate setelah soft delete

### 2. Soft delete lifecycle
Sudah konsisten:
- row soft deleted tidak muncul pada mode default active
- row soft deleted tetap ada untuk histori
- row baru dengan `kode_barang` / business identity yang sama bisa dibuat lagi setelah row lama soft deleted

### 3. Detail + versioning
Sudah ada halaman detail product yang menampilkan:
- identitas saat ini
- status histori awal
- timeline versioning

Aturan kejujuran histori:
- jika histori awal lengkap, tampilkan identitas awal
- jika histori awal tidak lengkap, tampilkan note bahwa data awal asli tidak tersedia
- tidak lagi menampilkan nilai awal palsu

### 4. Halaman edit dipisah
Sudah dipisah menjadi:
- halaman edit identitas
- halaman ubah stok

### 5. Action modal di index
Sudah ada 4 aksi:
- Detail
- Edit identitas barang
- Ubah stok
- Soft delete

### 6. Keyboard flow form
Sudah ada flow keyboard untuk form product master:
- fokus awal ke `kode_barang`
- `Enter` pindah ke field berikutnya
- `Enter` terakhir submit form

Scope keyboard hanya untuk:
- form product master

Tidak diterapkan ke:
- form stock adjustment

### 7. Filter status product
Sudah ada dukungan filter:
- `Aktif`
- `Soft Deleted`
- `Semua`

Default:
- `Aktif`

Row soft deleted di table:
- bisa difilter
- punya penanda visual

### 8. Seeder product baru
Seeder product lama yang random/massal diganti arah kerjanya menjadi seeder berbasis skenario.

Total target data:
- 50 row product

Komposisi skenario:
- 20 active basic
- 10 active edited
- 8 soft deleted
- 8 recreated after delete
- 4 legacy incomplete history

## Catatan penting soal migration
Untuk reset database, jalur aman yang dipakai adalah:

~~~bash
php artisan migrate:fresh --seed
~~~

Bukan:

~~~bash
php artisan migrate:refresh --seed
~~~

Alasan:
- rollback ke schema lama tidak lossless lagi setelah recreate-after-soft-delete diizinkan
- `refresh` sempat gagal saat mencoba mengembalikan unique absolut lama
- `fresh` adalah jalur yang benar untuk dev reset pada state schema sekarang

## Catatan penting soal restore
Soft delete saat ini:
- bisa dilacak
- bisa difilter
- bisa dilihat di detail/versioning

Tetapi:
- **belum bisa restore**

Jadi lifecycle resmi saat ini:
- create
- update
- soft delete
- detail/versioning
- filter active/deleted/all

## Catatan penting soal histori lama
Untuk product legacy yang tidak punya event `product_created`:
- sistem tidak tahu data awal asli
- UI sekarang jujur menyebut histori awal tidak lengkap
- skenario ini juga sudah ditutup oleh seeder legacy incomplete history

## File/area yang disentuh

### Backend product lifecycle
- `app/Adapters/Out/ProductCatalog/DatabaseVersionedProductWriterAdapter.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductWritePayloads.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductVersionRevisionLookup.php`
- `app/Adapters/Out/ProductCatalog/Concerns/RecordsProductHistory.php`
- `app/Adapters/Out/ProductCatalog/Concerns/PersistsVersionedProductWrites.php`
- `app/Adapters/Out/ProductCatalog/Concerns/SoftDeletesProducts.php`
- `app/Ports/Out/ProductCatalog/ProductLifecyclePort.php`
- `app/Application/ProductCatalog/UseCases/SoftDeleteProductHandler.php`
- `app/Providers/HexagonalServiceProvider.php`

### Duplicate/search hardening
- `app/Adapters/Out/ProductCatalog/DatabaseProductDuplicateCheckerAdapter.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductDuplicateLookupQuery.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductListQuery.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductTableBaseQuery.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductTableFilters.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductTablePayload.php`
- `app/Application/ProductCatalog/DTO/ProductTableQuery.php`
- `app/Adapters/In/Http/Requests/ProductCatalog/ProductTableQueryRequest.php`

### Detail/versioning read side
- `app/Ports/Out/ProductCatalog/ProductDetailReaderPort.php`
- `app/Adapters/Out/ProductCatalog/DatabaseProductDetailReaderAdapter.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductDetailSnapshotMapper.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductDetailVersionQueries.php`
- `app/Application/ProductCatalog/UseCases/GetProductDetailHandler.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/ShowProductPageController.php`
- `app/Adapters/In/Http/Presenters/Admin/Product/ProductDetailPagePresenter.php`
- `app/Adapters/In/Http/Presenters/Admin/Product/Concerns/FormatsProductDetailIdentity.php`
- `app/Adapters/In/Http/Presenters/Admin/Product/Concerns/FormatsProductDetailTimeline.php`

### HTTP controllers / routes
- `app/Adapters/In/Http/Controllers/Admin/Product/DeleteProductController.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/EditProductPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/EditProductStockPageController.php`
- `routes/web/admin_products.php`

### Views / UI
- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/products/partials/filter_drawer.blade.php`
- `resources/views/admin/products/show.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/admin/products/stock-edit.blade.php`
- `resources/views/admin/products/create.blade.php`

### JS
- `public/assets/static/js/pages/admin-products-table.js`
- `public/assets/static/js/pages/admin-product-master-form.js`

### Migration
- `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php`
- `database/migrations/2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php`

### Seeder
- `database/seeders/ProductSeeder.php`
- `database/seeders/Product/ProductSeedCatalog.php`
- `database/seeders/Product/ProductScenarioActiveBasicSeeder.php`
- `database/seeders/Product/ProductScenarioEditedSeeder.php`
- `database/seeders/Product/ProductScenarioSoftDeletedSeeder.php`
- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`
- `database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php`

## Proof / verifikasi yang sudah pernah lolos
1. Syntax check file-file utama product:
- controller
- use case
- adapter
- provider
- route
- migration
- seeder scenario

2. Smoke test lifecycle:
- create -> update -> search -> soft delete -> search
- recreate setelah soft delete

3. Route runtime:
- route product utama aktif
- route detail aktif
- route stock edit aktif

4. Migration:
- `migrate:fresh` berhasil
- seed product scenario berjalan bertahap sampai selesai

5. Verifikasi repo:
- `make verify` hijau

6. Test suite:
- `468 passed (2148 assertions)`

7. Verifikasi browser/manual:
- action modal product tampil
- detail product tampil
- edit identitas dan ubah stok terpisah
- filter status product berfungsi
- row soft deleted bisa dilacak
- keyboard flow form product master berfungsi

## Masalah yang ditemukan dan sudah ditutup
1. Unique constraint lama bentrok dengan recreate after soft delete
- ditutup dengan active-only unique

2. Migration refresh gagal rollback ke schema lama
- diputuskan jalur reset aman adalah `migrate:fresh --seed`

3. Detail product sempat bohong menganggap versi pertama tercatat sebagai data awal asli
- ditutup dengan read side + presenter yang jujur

4. Edit identitas dan ubah stok sempat satu halaman
- dipisah jadi dua halaman

5. Seeder lama random terlalu besar dan miskin skenario
- diganti jadi catalog deterministic + scenario seeders

6. Filter status product belum ada
- ditutup di request, DTO, query, payload, drawer, dan JS

## Batasan yang masih tersisa
- restore soft delete belum ada
- visual polish modal/action masih bisa diperhalus lagi kalau nanti mau
- tidak ada backfill otomatis untuk mengubah histori lama menjadi `product_created` asli
- untuk product legacy, histori awal asli memang tidak tersedia bila tidak pernah terekam

## Safest next step
Kasus `product` ditutup.

Kalau lanjut fase berikutnya, yang paling aman:
1. jangan buka lagi domain `product` kecuali ada bug nyata
2. lanjut ke domain berikutnya yang bergantung pada data product ini
3. gunakan handoff ini sebagai baseline tetap

## Ringkasan satu kalimat
Kasus `product` selesai sampai level backend + lifecycle + detail/versioning + split edit pages + filter status + seeder skenario, dengan satu hutang resmi yang masih tersisa: **restore soft delete belum ada**.
