# Handoff — Konsistensi UI Admin Procurement & Product, Rename Supplier, Preview Bukti Bayar, dan Standardisasi Input Nominal

## Metadata
- Tanggal: 2026-03-21
- Nama slice / topik: Hardening UI admin lintas procurement, supplier, dan product
- Workflow step: Penutupan rangkaian perbaikan pasca slice procurement + product admin hardening
- Status: CLOSED
- Progres:
  - Paket perbaikan ini: 100%
  - Preview bukti bayar: 100%
  - Dual display supplier di procurement detail: 100%
  - Dual display supplier di procurement table/index: 100%
  - Standardisasi input nominal admin: 100%

---

## Target halaman kerja
Menutup gap operasional yang ditemukan saat pengecekan web langsung, agar halaman admin procurement dan product konsisten, aman, dan jelas secara histori.

Target yang ditutup di percakapan ini:
- procurement detail tidak lagi membingungkan setelah supplier di-rename
- procurement table/index tidak lagi menampilkan hanya nama lama snapshot
- bukti bayar tidak lagi hanya teks/path, tetapi bisa dipreview dan diunduh
- input nominal admin memakai perilaku yang konsisten
- data status bukti bayar yang legacy tidak lagi merusak detail page
- route update product web admin memakai method HTTP yang tepat
- semua perubahan dibuktikan dengan lint, phpstan, test targeted/regression, dan verifikasi UI langsung

---

## Referensi yang dipakai `[REF]`
- Blueprint/kontrak kerja project kasir v2 yang menuntut histori tetap benar, zero assumption, dan verifikasi berbasis bukti
- Hasil command repo yang dikirim selama percakapan:
  - php -l
  - php artisan test
  - ./vendor/bin/phpstan analyze --memory-limit=-1
  - make audit-lines / make verify
  - grep / sed / tail / tinker / git status
- Snapshot file yang ditampilkan langsung oleh user dari repo aktif
- Verifikasi UI langsung oleh user pada web admin setelah patch diterapkan

---

## Fakta terkunci `[FACT]`

### A. Procurement snapshot & histori supplier
- `supplier_invoices` menyimpan snapshot nama supplier pada kolom `supplier_nama_pt_pengirim_snapshot`.
- Setelah supplier di-rename, procurement awalnya tetap menampilkan nama snapshot lama saja pada detail dan table.
- Histori snapshot sengaja dipertahankan dan tidak boleh ditimpa oleh rename supplier.

### B. Detail procurement
- Detail procurement awalnya hanya punya satu slot nama supplier.
- Read side detail awalnya mengambil `supplier_invoices.supplier_nama_pt_pengirim_snapshot` sebagai nama supplier.
- Detail procurement kemudian diperbaiki agar menampilkan:
  - supplier saat ini dari tabel `suppliers`
  - nama saat nota dibuat dari snapshot invoice
- Detail procurement setelah patch lulus lint dan targeted regression.
- User mengonfirmasi perilaku UI ini sudah berfungsi.

### C. Procurement table/index
- Table procurement awalnya hanya menampilkan snapshot supplier lama.
- Filter pencarian awalnya hanya mencari nomor invoice dan snapshot supplier.
- Ordering kolom nama PT sudah mengarah ke `suppliers.nama_pt_pengirim`.
- Table procurement kemudian diperbaiki agar:
  - nama utama = nama supplier saat ini
  - keterangan sekunder = nama saat nota dibuat jika berbeda
  - search cocok ke nomor nota, nama current, dan nama snapshot
- User mengonfirmasi UI table setelah patch sudah berfungsi.

### D. Bukti bayar supplier
- Bukti bayar awalnya disimpan di disk `local` private.
- Detail procurement awalnya hanya menampilkan metadata/path lampiran bukti, belum ada preview aman.
- Ditambahkan jalur preview/download berbasis `attachmentId`, bukan expose `storage_path` mentah ke browser.
- Image bisa dipreview inline.
- PDF bisa dibuka dan diunduh.
- Download/preview tetap memakai disk private.
- Implementasi awal `download()` / `response()` pada disk tidak lolos phpstan, lalu diganti dengan pola `exists()` + `get()` + `response(...)`.
- Setelah perbaikan, preview dan download lulus test dan dikonfirmasi user berfungsi di UI.

### E. Status bukti bayar legacy
- Di database ada data legacy `supplier_payments.proof_status = 'valid'`.
- Status `valid` tidak sesuai kontrak domain yang hanya mengizinkan `pending` dan `uploaded`.
- Query tinker menunjukkan:
  - distinct proof_status di DB = `valid`
  - total row legacy invalid = 122
  - seluruh row invalid yang dicek tidak punya attachment dan tidak punya `proof_storage_path`
- Dilakukan normalisasi DB:
  - `valid` -> `pending`
  - hasil update row = 122
  - distinct status setelah normalisasi = `pending`
- Seeder `WorkshopStressTestSeeder` juga dipatch agar tidak lagi menulis `valid`.

### F. Input nominal admin
- Product create/edit awalnya masih memakai input angka biasa tanpa formatter nominal seperti yang diinginkan.
- Procurement create memakai JS sendiri untuk nominal, lalu distandarkan.
- Dibuat helper JS shared untuk input nominal admin.
- Helper shared kemudian dipakai pada:
  - product create
  - product edit
  - procurement create
- User mengonfirmasi:
  - titik ribuan sudah muncul
  - nominal procurement create berjalan
  - nominal bayar procurement detail berjalan
- Rule domain procurement create tetap berlaku:
  - line total harus habis dibagi qty
  - jika tidak habis dibagi qty, proses gagal memang by design
- Ini dikonfirmasi user saat menguji langsung di web.

### G. Product admin web route
- Route web admin update product awalnya masih POST.
- Route diperbaiki menjadi PUT.
- Blade edit product dan feature test admin product disesuaikan.
- Targeted regression product edit/update lulus.

### H. Supplier edit/update
- Domain supplier kemudian punya kemampuan rename.
- Reader/writer port supplier ditambah kontrak baca by id dan update.
- Adapter DB supplier diperluas sesuai kontrak baru.
- Use case `UpdateSupplierHandler` dibuat dan lulus regression.
- Snapshot nama pada invoice existing tetap tidak berubah setelah supplier rename.
- Ini ditegaskan sebagai keputusan desain yang benar.

### I. Kualitas hasil
- Beberapa kali static analysis (`phpstan`) berakhir `[OK] No errors`.
- Beberapa kali `git status --short` berakhir kosong setelah patch final.
- User menyatakan final state:
  - clear
  - clean
  - pass
  - berfungsi di UI

---

## Scope yang dipakai

### `[SCOPE-IN]`
- Procurement detail page
- Procurement invoice table/index
- Supplier rename visibility terhadap histori procurement
- Payment proof preview/download di admin procurement
- Normalisasi status bukti bayar legacy dan patch seeder
- Standardisasi input nominal pada admin product create/edit dan procurement create
- Perbaikan route update product web admin
- Targeted regression dan static analysis yang terkait langsung dengan perubahan di atas

### `[SCOPE-OUT]`
- Mengubah histori snapshot invoice agar ikut rename supplier
- Menghapus/menyatukan current name dan snapshot menjadi satu sumber data
- Mengubah rule domain procurement bahwa line total harus habis dibagi qty
- Membuka file proof ke public storage
- Membangun gallery/lightbox penuh untuk attachment
- Mengubah seluruh domain procurement selain yang diperlukan untuk UI consistency, preview, dan data validity
- Menghapus konsep snapshot dari procurement

---

## Keputusan yang dikunci `[DECISION]`

- Snapshot nama supplier pada invoice tetap menjadi sumber histori dan tidak diubah saat supplier di-rename.
- UI procurement harus menampilkan nama current dan nama snapshot secara eksplisit agar tidak menimbulkan miss komunikasi.
- Untuk detail procurement:
  - supplier saat ini menjadi data live
  - nama saat nota dibuat menjadi konteks histori
- Untuk procurement table/index:
  - nama utama = nama supplier saat ini
  - subteks = nama saat nota dibuat jika berbeda
- Search procurement table harus mendukung:
  - nomor nota
  - nama supplier saat ini
  - nama supplier pada snapshot
- Proof attachment tetap private pada disk `local`.
- Preview/download file dilakukan lewat controller admin berbasis `attachmentId`, bukan lewat path mentah.
- Image preview boleh inline; PDF dan semua file tetap punya aksi lihat/unduh.
- Status proof yang valid secara domain hanya:
  - `pending`
  - `uploaded`
- Nilai legacy `valid` dianggap invalid dan dinormalisasi ke `pending` karena seluruh row yang dicek tidak memiliki attachment/path.
- Helper JS input nominal dibagi menjadi shared helper agar perilaku product dan procurement konsisten.
- Hidden raw numeric field tetap menjadi source of truth untuk submit nominal, sedangkan tampilan display memakai formatter ribuan.
- Route update product web admin dikunci ke method PUT agar sesuai semantic HTTP dan form method spoofing di blade.
- Fix phpstan untuk file streaming attachment memakai pendekatan yang dikenali contract filesystem.

---

## File yang dibuat/diubah `[FILES]`

### File baru
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ServeSupplierPaymentProofAttachmentController.php`
- `tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php`
- `public/assets/static/js/shared/admin-money-input.js`
- `app/Application/Procurement/UseCases/UpdateSupplierHandler.php`
- `tests/Feature/Procurement/UpdateSupplierFeatureTest.php`

### File diubah
- `database/seeders/WorkshopStressTestSeeder.php`
- `routes/web/procurement.php`
- `routes/web/product_catalog.php`

- `app/Ports/Out/Procurement/SupplierPaymentProofAttachmentReaderPort.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierPaymentProofAttachmentReaderAdapter.php`

- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailSummaryQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailPayload.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/Concerns/BuildsProcurementInvoiceDetailSummaryView.php`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`

- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableFilters.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTablePayload.php`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`

- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `public/assets/static/js/pages/admin-procurement-create.js`

- `resources/views/admin/products/create.blade.php`
- `resources/views/admin/products/edit.blade.php`

- `app/Core/Procurement/Supplier/Supplier.php`
- `app/Ports/Out/Procurement/SupplierReaderPort.php`
- `app/Ports/Out/Procurement/SupplierWriterPort.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierWriterAdapter.php`

- `tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php`
- `tests/Feature/Procurement/AttachSupplierPaymentProofFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceTableDataQueryFeatureTest.php`

- `tests/Feature/ProductCatalog/ProductEditPageFeatureTest.php`

### Operasi data manual / non-file
- Normalisasi data DB:
  - `supplier_payments.proof_status: valid -> pending`
  - row ter-update: 122

---

## Bukti verifikasi `[PROOF]`

- command:
  - `php artisan tinker --execute="dump(DB::table('supplier_payments')->select('proof_status')->distinct()->pluck('proof_status')->all());"`
  - hasil:
    - sebelum normalisasi: distinct status = `valid`
    - sesudah normalisasi: distinct status = `pending`

- command:
  - `php artisan tinker --execute="dump(DB::table('supplier_payments')->whereNotIn('proof_status', ['pending','uploaded'])->limit(20)->get(['id','supplier_invoice_id','proof_status'])->toArray());"`
  - hasil:
    - ditemukan row legacy invalid dengan `proof_status = valid`

- command:
  - query agregat valid status + attachment/path
  - hasil:
    - `valid_total = 122`
    - `valid_with_path = 0`
    - `valid_with_attachment = 0`

- command:
  - normalisasi DB invalid status
  - hasil:
    - `updated_rows = 122`

- command:
  - `php artisan test tests/Feature/Procurement/AttachSupplierPaymentProofFeatureTest.php`
  - hasil:
    - PASS, 2 test, 22 assertions

- command:
  - `php artisan test tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php`
  - hasil:
    - PASS, 5 test
    - final run yang dikirim user: 63 assertions

- command:
  - `php artisan test tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php`
  - hasil:
    - PASS, 2 test, 6 assertions

- command:
  - `php artisan test tests/Feature/Procurement/RecordSupplierPaymentFeatureTest.php`
  - hasil:
    - PASS, 4 test, 15 assertions

- command:
  - `php artisan test tests/Feature/Procurement/CreateSupplierInvoiceFeatureTest.php`
  - hasil:
    - PASS, 4 test, 51 assertions

- command:
  - `php artisan test tests/Feature/Procurement/ReceiveSupplierInvoiceFeatureTest.php`
  - hasil:
    - PASS, 4 test, 26 assertions

- command:
  - `php artisan test tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php`
  - hasil:
    - PASS, 3 test, 13 assertions

- command:
  - `php artisan test tests/Feature/Procurement/ProcurementInvoiceTableDataQueryFeatureTest.php`
  - hasil:
    - setelah perbaikan duplicate admin creation: clear/clean dan UI table procurement berfungsi menurut user

- command:
  - `php artisan test tests/Feature/Reporting/GetSupplierPayableSummaryFeatureTest.php`
  - hasil:
    - PASS, 1 test, 5 assertions

- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php`
  - hasil:
    - PASS, 5 test, 19 assertions

- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductEditPageFeatureTest.php`
  - hasil:
    - PASS, 7 test, 25 assertions

- command:
  - `php artisan test tests/Feature/ProductCatalog/CreateProductFeatureTest.php`
  - hasil:
    - PASS, 4 test, 8 assertions

- command:
  - `php artisan test tests/Feature/ProductCatalog/UpdateProductFeatureTest.php`
  - hasil:
    - PASS, 3 test, 6 assertions

- command:
  - `php artisan test tests/Feature/Procurement/SupplierIndexPageFeatureTest.php`
  - hasil:
    - PASS, 3 test, 17 assertions

- command:
  - `php artisan test tests/Feature/Procurement/UpdateSupplierFeatureTest.php`
  - hasil:
    - PASS, 4 test, 11 assertions

- command:
  - `./vendor/bin/phpstan analyze --memory-limit=-1`
  - hasil:
    - beberapa run berakhir `[OK] No errors`

- command:
  - `make audit-lines`
  - hasil:
    - sempat gagal karena beberapa file >100 line
    - kemudian setelah refactor/penutupan patch, user menyatakan clear/clean dan verifikasi akhir lulus

- command:
  - `git status --short`
  - hasil:
    - pada penutupan beberapa paket kerja, status kosong / repo bersih

- bukti UI dari user:
  - preview bukti bayar berfungsi aman
  - nominal procurement create sudah bertitik
  - nominal bayar procurement sudah aktif
  - dual display supplier di detail procurement jelas
  - dual display supplier di procurement table/index berfungsi
  - status akhir: clear, clean, dan berfungsi di UI

---

## Ringkasan isu yang dibahas dari awal sampai akhir

### 1. Slice procurement history/snapshot hardening
- procurement invoice diperkaya snapshot supplier dan snapshot product line
- ada beberapa test awal yang pecah karena test fixture belum ikut snapshot contract
- fixture test kemudian disesuaikan agar data snapshot di test lengkap

### 2. Procurement payment proof multi-attachment
- domain dan handler bukti bayar dipisah ke attachment table
- status proof dibakukan ke pending/uploaded
- upload multi-file maksimal 3 file per submit berjalan
- lampiran bisa ditambah lagi ke payment yang sama

### 3. Product admin edit/update web
- route admin update product dikoreksi menjadi PUT
- blade edit dan feature test diselaraskan
- targeted regression lulus

### 4. Supplier edit/update
- supplier bisa di-rename melalui use case update
- rename supplier tidak mengubah snapshot histori invoice lama
- ini dikunci sebagai keputusan desain

### 5. Default tanggal & UX input
- beberapa input tanggal admin diberi default yang lebih operasional
- input nominal admin distandarkan lewat helper shared

### 6. Payment proof preview/download
- semula hanya metadata/path
- diubah menjadi preview/download aman berbasis attachment id
- image preview inline
- PDF/file bisa dibuka/diunduh
- implementasi disesuaikan ulang agar lolos phpstan

### 7. Legacy data fix
- ditemukan status proof legacy `valid` yang tidak sesuai domain
- dicek attachment/path-nya kosong
- dinormalisasi ke `pending`
- seeder juga dipatch agar tidak menghasilkan `valid` lagi

### 8. Supplier rename visibility in procurement detail
- masalah utama user: supplier sudah diubah tetapi procurement tetap terlihat memakai nama lama
- solusi: tampilkan dua nama
  - supplier saat ini
  - nama saat nota dibuat

### 9. Supplier rename visibility in procurement table/index
- masalah yang sama juga ada di table procurement
- table kemudian dibuat konsisten dengan detail:
  - nama current sebagai utama
  - snapshot sebagai subteks
  - search current + snapshot + nomor nota

---

## Catatan penting untuk halaman chat berikutnya
- Jangan ubah atau hapus snapshot histori procurement.
- Jika ada pengembangan lanjutan, pertahankan pola:
  - data live untuk operasional
  - snapshot untuk histori
- Payment proof harus tetap lewat jalur private/admin-only, bukan expose storage path.
- Helper JS nominal shared sudah ada dan sebaiknya dipakai ulang untuk form admin lain yang butuh pola serupa.
- Rule procurement create bahwa line total harus habis dibagi qty masih aktif dan benar secara domain.
- Jika nanti ingin mempercantik UI, itu murni polishing; fondasi fungsionalnya sudah beres.

---

## Status penutupan
Paket diskusi ini dapat dianggap selesai dan aman ditutup.

Hasil akhir yang sudah terbukti:
- clear
- clean
- pass
- berfungsi di UI
