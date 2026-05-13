# Handoff V2 - Procurement Supplier Invoice Create / Index / Edit Pre-effect

## Ringkasan
Halaman eksekusi procurement untuk **create nota supplier**, **index procurement**, dan **edit nota supplier pre-effect** sudah stabil pada scope kerja halaman ini.

Status akhir saat handoff ini dibuat:
- create nota supplier: stabil
- index procurement: stabil
- edit nota supplier pre-effect: stabil
- update nota supplier pre-effect: stabil
- draft create procurement: sudah dibersihkan pada success landing ke index
- verifikasi repo: **lulus (`make verify` mulus)**

## Scope yang selesai

### 1. Create Nota Supplier
Selesai:
- form create nota supplier stabil
- nomor faktur masuk ke contract
- line number masuk ke contract
- auto receive tetap jalan
- draft create tetap restore saat kerja belum selesai
- draft create dibersihkan setelah submit sukses dan landing ke index
- keyboard-first interaction tetap dipertahankan
- empty line dipruning saat submit
- error line total / qty tetap tervalidasi

### 2. Index Procurement
Selesai:
- kolom utama nota memakai `nomor_faktur`
- filter eksplisit:
  - no faktur
  - nama PT
  - status tagihan
  - rentang tanggal kirim
- ringkasan filter aktif tampil di index
- reset semua filter tersedia
- modal aksi 4 slot konsisten
- aksi aktif:
  - detail
  - bayar / riwayat pembayaran
  - bukti bayar
  - edit nota
- state `Edit Nota` aktif hanya untuk invoice pre-effect:
  - `payment_count = 0`
  - `receipt_count = 0`

### 3. Edit Nota Supplier Pre-effect
Selesai:
- route edit page hidup
- guard editable / locked hidup
- route update hidup
- update use case hidup
- versioned writer update hidup
- audit/history revision update hidup
- halaman edit bukan placeholder lagi
- halaman edit interaktif:
  - tambah line
  - hapus line
  - lookup product
  - ubah header
  - ubah qty
  - ubah total rincian
- update add line dan remove line sudah terbukti lewat feature test

## Rule / keputusan yang terkunci
- edit nota supplier **hanya** boleh untuk invoice **pre-effect**
- kalau sudah ada receipt atau payment, edit biasa ditolak dan harus lewat correction / reversal
- index menilai editable dengan rule praktis saat ini:
  - `payment_count < 1`
  - `receipt_count < 1`
- draft create tidak boleh muncul lagi setelah submit sukses
- draft create tetap boleh restore saat submit belum sukses / form belum selesai

## File yang ditambah / diubah pada page ini

### Procurement create / draft
- `app/Adapters/In/Http/Controllers/Admin/Procurement/StoreSupplierInvoiceController.php`
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `public/assets/static/js/pages/admin-procurement-create.js`

### Procurement index
- `app/Adapters/In/Http/Requests/Procurement/ProcurementInvoiceTableQueryRequest.php`
- `app/Application/Procurement/DTO/ProcurementInvoiceTableQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableFilters.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTablePayload.php`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`
- `resources/views/admin/procurement/supplier_invoices/partials/filter_drawer.blade.php`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`

### Procurement edit pre-effect
- `routes/web/admin_procurement.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/EditSupplierInvoicePageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/Support/EditSupplierInvoiceLineItemsViewBuilder.php`
- `resources/views/admin/procurement/supplier_invoices/edit.blade.php`
- `public/assets/static/js/pages/admin-procurement-edit.js`
- `app/Adapters/In/Http/Requests/Procurement/UpdateSupplierInvoiceRequest.php`
- `app/Application/Procurement/UseCases/UpdateSupplierInvoiceHandler.php`
- `app/Application/Procurement/Services/SupplierInvoiceEditabilityGuard.php`
- `app/Application/Procurement/Services/UpdatedSupplierInvoiceBuilder.php`
- `app/Ports/Out/Procurement/SupplierInvoiceWriterPort.php`
- `app/Adapters/Out/Procurement/DatabaseVersionedSupplierInvoiceWriterAdapter.php`
- `app/Adapters/Out/Procurement/Concerns/LoadsCurrentSupplierInvoiceWriteSnapshot.php`
- `app/Adapters/Out/Procurement/Concerns/PersistsVersionedSupplierInvoiceWrites.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceWriterAdapter.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailPayload.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailSummaryQuery.php`

### Test yang ditambah / diubah
- `tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php`
- `tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php`
- `tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`

## Bukti verifikasi yang sudah lulus

### Feature test procurement
~~~bash
php artisan test tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php
~~~

### JS syntax
~~~bash
node --check public/assets/static/js/pages/admin-procurement-create.js
node --check public/assets/static/js/pages/admin-procurement-edit.js
~~~

### Repo verify
~~~bash
make verify
~~~

### Hasil akhir verifikasi
- create page feature test: pass
- edit page feature test: pass
- update feature test: pass
- add line update: pass
- remove line update: pass
- repo verify: pass

## Tidak ada hutang teknis aktif pada scope halaman ini
Tidak ada blocker aktif yang tersisa pada scope:
- create nota supplier
- index procurement
- edit nota supplier pre-effect

Catatan jujur:
- correction / reversal belum dikerjakan di page ini
- proof preview direct dari index belum dibuka lebih jauh
- itu **bukan** hutang teknis aktif dari scope halaman ini, tapi kandidat scope berikutnya bila dibutuhkan

## Command ringkas untuk cek ulang cepat
~~~bash
php artisan test tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php
node --check public/assets/static/js/pages/admin-procurement-create.js
node --check public/assets/static/js/pages/admin-procurement-edit.js
make verify
~~~

## Command untuk simpan file ini ke repo
~~~bash
mkdir -p handoff/v2
cat > handoff/v2/procurement_create_index_edit_handoff_v2.md <<'MD'
# Handoff V2 - Procurement Supplier Invoice Create / Index / Edit Pre-effect

## Ringkasan
Halaman eksekusi procurement untuk **create nota supplier**, **index procurement**, dan **edit nota supplier pre-effect** sudah stabil pada scope kerja halaman ini.

Status akhir saat handoff ini dibuat:
- create nota supplier: stabil
- index procurement: stabil
- edit nota supplier pre-effect: stabil
- update nota supplier pre-effect: stabil
- draft create procurement: sudah dibersihkan pada success landing ke index
- verifikasi repo: **lulus (`make verify` mulus)**

## Scope yang selesai

### 1. Create Nota Supplier
Selesai:
- form create nota supplier stabil
- nomor faktur masuk ke contract
- line number masuk ke contract
- auto receive tetap jalan
- draft create tetap restore saat kerja belum selesai
- draft create dibersihkan setelah submit sukses dan landing ke index
- keyboard-first interaction tetap dipertahankan
- empty line dipruning saat submit
- error line total / qty tetap tervalidasi

### 2. Index Procurement
Selesai:
- kolom utama nota memakai `nomor_faktur`
- filter eksplisit:
  - no faktur
  - nama PT
  - status tagihan
  - rentang tanggal kirim
- ringkasan filter aktif tampil di index
- reset semua filter tersedia
- modal aksi 4 slot konsisten
- aksi aktif:
  - detail
  - bayar / riwayat pembayaran
  - bukti bayar
  - edit nota
- state `Edit Nota` aktif hanya untuk invoice pre-effect:
  - `payment_count = 0`
  - `receipt_count = 0`

### 3. Edit Nota Supplier Pre-effect
Selesai:
- route edit page hidup
- guard editable / locked hidup
- route update hidup
- update use case hidup
- versioned writer update hidup
- audit/history revision update hidup
- halaman edit bukan placeholder lagi
- halaman edit interaktif:
  - tambah line
  - hapus line
  - lookup product
  - ubah header
  - ubah qty
  - ubah total rincian
- update add line dan remove line sudah terbukti lewat feature test

## Rule / keputusan yang terkunci
- edit nota supplier **hanya** boleh untuk invoice **pre-effect**
- kalau sudah ada receipt atau payment, edit biasa ditolak dan harus lewat correction / reversal
- index menilai editable dengan rule praktis saat ini:
  - `payment_count < 1`
  - `receipt_count < 1`
- draft create tidak boleh muncul lagi setelah submit sukses
- draft create tetap boleh restore saat submit belum sukses / form belum selesai

## File yang ditambah / diubah pada page ini

### Procurement create / draft
- `app/Adapters/In/Http/Controllers/Admin/Procurement/StoreSupplierInvoiceController.php`
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `public/assets/static/js/pages/admin-procurement-create.js`

### Procurement index
- `app/Adapters/In/Http/Requests/Procurement/ProcurementInvoiceTableQueryRequest.php`
- `app/Application/Procurement/DTO/ProcurementInvoiceTableQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableFilters.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTablePayload.php`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`
- `resources/views/admin/procurement/supplier_invoices/partials/filter_drawer.blade.php`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`

### Procurement edit pre-effect
- `routes/web/admin_procurement.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/EditSupplierInvoicePageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/Support/EditSupplierInvoiceLineItemsViewBuilder.php`
- `resources/views/admin/procurement/supplier_invoices/edit.blade.php`
- `public/assets/static/js/pages/admin-procurement-edit.js`
- `app/Adapters/In/Http/Requests/Procurement/UpdateSupplierInvoiceRequest.php`
- `app/Application/Procurement/UseCases/UpdateSupplierInvoiceHandler.php`
- `app/Application/Procurement/Services/SupplierInvoiceEditabilityGuard.php`
- `app/Application/Procurement/Services/UpdatedSupplierInvoiceBuilder.php`
- `app/Ports/Out/Procurement/SupplierInvoiceWriterPort.php`
- `app/Adapters/Out/Procurement/DatabaseVersionedSupplierInvoiceWriterAdapter.php`
- `app/Adapters/Out/Procurement/Concerns/LoadsCurrentSupplierInvoiceWriteSnapshot.php`
- `app/Adapters/Out/Procurement/Concerns/PersistsVersionedSupplierInvoiceWrites.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceWriterAdapter.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailPayload.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailSummaryQuery.php`

### Test yang ditambah / diubah
- `tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php`
- `tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php`
- `tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`

## Bukti verifikasi yang sudah lulus

### Feature test procurement
~~~bash
php artisan test tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php
~~~

### JS syntax
~~~bash
node --check public/assets/static/js/pages/admin-procurement-create.js
node --check public/assets/static/js/pages/admin-procurement-edit.js
~~~

### Repo verify
~~~bash
make verify
~~~

### Hasil akhir verifikasi
- create page feature test: pass
- edit page feature test: pass
- update feature test: pass
- add line update: pass
- remove line update: pass
- repo verify: pass

## Tidak ada hutang teknis aktif pada scope halaman ini
Tidak ada blocker aktif yang tersisa pada scope:
- create nota supplier
- index procurement
- edit nota supplier pre-effect

Catatan jujur:
- correction / reversal belum dikerjakan di page ini
- proof preview direct dari index belum dibuka lebih jauh
- itu **bukan** hutang teknis aktif dari scope halaman ini, tapi kandidat scope berikutnya bila dibutuhkan

## Command ringkas untuk cek ulang cepat
~~~bash
php artisan test tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php
php artisan test tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php
node --check public/assets/static/js/pages/admin-procurement-create.js
node --check public/assets/static/js/pages/admin-procurement-edit.js
make verify
~~~

## Safest next step
Kalau nanti buka scope baru, urutan teraman setelah page ini adalah salah satu dari:
1. correction / reversal procurement
2. preview / download bukti bayar dari index
3. polish UX minor procurement berdasarkan feedback manual pengguna

## Progress akhir
- page scope procurement create/index/edit/update pre-effect: **100% selesai**
MD
~~~

## Safest next step
Kalau nanti buka scope baru, urutan teraman setelah page ini adalah salah satu dari:
1. correction / reversal procurement
2. preview / download bukti bayar dari index
3. polish UX minor procurement berdasarkan feedback manual pengguna

## Progress akhir
- page scope procurement create/index/edit/update pre-effect: **100% selesai**