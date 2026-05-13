# Handoff — Supplier / Procurement Follow-up Questions & Decision Parking

## Metadata
- Tanggal: 2026-03-20
- Nama slice / topik: Supplier vs Procurement, Procurement Detail, edit/delete sensitivity, Supplier List utility, inventory state semantics, stock adjustment policy
- Workflow step:
  - Procurement UI lanjutan setelah:
    - Product UI Interactive Table admin CLOSED
    - Supplier List V1 CLOSED
    - Procurement Invoice List V1 CLOSED
    - Procurement Create Page V2 CLOSED
    - Procurement Invoice Detail Page sedang dikunci
- Status: OPEN QUESTIONS LOCKED
- Progres:
  - Supplier List V1: 100%
  - Procurement Invoice List V1: 100%
  - Procurement Create Page V2: 100%
  - Procurement Invoice Detail backend read-side: 100%
  - Procurement Invoice Detail surface admin: 100%
  - Rule final edit/delete/cancel supplier invoice: BELUM DIKUNCI
  - Rule final supplier page utility enrichment: BELUM DIKUNCI
  - Rule final inventory state `bisa dijual / dipakai / rusak` dan manual stock adjustment: BELUM DIKUNCI

---

## Target halaman kerja
Menutup percakapan ini dengan handoff yang merangkum:

1. fakta repo dan dokumen yang sudah terbukti
2. keputusan yang sudah dikunci
3. baseline enterprise sementara yang dipakai
4. pertanyaan lanjutan user yang belum boleh dijawab final tanpa pembahasan berikutnya
5. urutan bahasan yang paling aman untuk halaman berikutnya

Tujuan handoff ini:
- halaman berikutnya langsung paham konteks tanpa membuka ulang semua diskusi
- tidak ada keputusan sensitif yang diambil diam-diam
- semua pertanyaan user tetap tercatat sebagai agenda eksplisit

---

## Referensi yang dipakai `[REF]`
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- ADR:
  - `docs/adr/0008-audit-first-sensitive-mutations.md`
  - `docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
  - `docs/adr/0011-money-stored-as-integer-rupiah.md`
  - `docs/adr/0002-negative-stock-policy-default-off.md`
  - `docs/adr/0005-paid-note-correction-requires-audit.md`
  - `docs/adr/0006-costing-strategy-default-average-fifo-ready.md`
  - `docs/adr/0009-reporting-as-read-model.md`
- Handoff sebelumnya:
  - `docs/handoff/handoff_step_04.md`
  - `docs/handoff/handoff_step_12_reporting_v1_closed.md`
  - Handoff Product UI Interactive Table (Admin) yang dipakai user di awal halaman ini
- Snapshot repo / output command pada percakapan ini:
  - `routes/web/procurement.php`
  - `resources/views/admin/suppliers/index.blade.php`
  - `resources/views/admin/procurement/supplier_invoices/index.blade.php`
  - `resources/views/admin/procurement/supplier_invoices/create.blade.php`
  - `resources/views/admin/procurement/supplier_invoices/show.blade.php`
  - `public/assets/static/js/pages/admin-suppliers-table.js`
  - `public/assets/static/js/pages/admin-procurement-invoices-table.js`
  - `public/assets/static/js/pages/admin-procurement-create.js`
  - `app/Adapters/In/Http/Controllers/Admin/Supplier/*`
  - `app/Adapters/In/Http/Controllers/Admin/Procurement/*`
  - `app/Adapters/In/Http/Controllers/Procurement/*`
  - `app/Application/Procurement/DTO/*`
  - `app/Application/Procurement/UseCases/*`
  - `app/Adapters/Out/Procurement/*`
  - `app/Ports/Out/Procurement/*`
  - `app/Adapters/Out/Reporting/DatabaseSupplierPayableReportingSourceReaderAdapter.php`
  - `app/Providers/HexagonalServiceProvider.php`
  - `tests/Feature/Procurement/*`
  - `tests/Feature/Reporting/GetSupplierPayableSummaryFeatureTest.php`
  - `php artisan route:list | grep -E 'admin.suppliers|admin.procurement.supplier-invoices|procurement.supplier-invoices'`
  - `make audit-lines`
  - `make lint`

---

## Fakta terkunci `[FACT]`

### A. Fakta domain dan dokumen
- Blueprint memisahkan `Product Catalog` dari `Procurement / Supplier`.
- Procurement / Supplier adalah bounded context resmi dengan cakupan:
  - supplier
  - faktur supplier
  - harga beli
  - hutang supplier
  - jatuh tempo
  - pembayaran supplier
- Penambahan stok normal hanya boleh berasal dari supplier invoice / receipt yang valid.
- Supplier invoice / receipt tidak boleh menciptakan product baru secara implisit.
- Audit-first mutation sudah dikunci untuk tindakan sensitif, termasuk perubahan supplier invoice yang berdampak finansial / stok.
- Dokumen yang ada menandai edit supplier invoice sebagai area sensitif, tetapi tidak memberikan rule final operasional untuk edit/delete/cancel invoice.
- Reporting hutang supplier v1 dibaca di level invoice, bukan di level supplier.
- Dokumen yang ada belum mengunci rule final untuk:
  - detail supplier invoice seperti apa
  - edit supplier invoice boleh dalam kondisi apa
  - delete supplier invoice
  - cancel / void supplier invoice
  - delete supplier master
  - enrich Supplier List dengan angka pendukung
  - semantics inventory `bisa dijual / dipakai / rusak / cacat`

### B. Fakta repo UI / read-side yang sudah hidup
- Supplier List V1 hidup:
  - route index admin
  - route JSON table admin
  - server-side search/sort/pagination
- Procurement Invoice List V1 hidup:
  - route index admin
  - route JSON table admin
  - server-side search/sort/pagination
  - filter tanggal kirim
  - kolom summary payable/receipt
- Procurement Create Page V2 hidup:
  - line di kiri
  - metadata nota di kanan
  - product autocomplete lookup
  - input uang bertipe text + hidden raw integer
  - mode auto receive dibuat lebih natural sebagai pilihan radio, bukan checkbox mentah
- Procurement product lookup hidup:
  - `admin.procurement.products.lookup`
- Procurement Invoice Detail backend read-side hidup:
  - `ProcurementInvoiceDetailReaderPort`
  - `GetProcurementInvoiceDetailHandler`
  - `DatabaseProcurementInvoiceDetailReaderAdapter`
- Procurement Invoice Detail surface admin hidup:
  - route show admin
  - page controller detail
  - blade show detail
  - tombol `Detail` dari list procurement

### C. Fakta utility Supplier List saat ini
- Supplier table V1 saat ini hanya menampilkan `nama_pt_pengirim`.
- Secara operasional, Supplier List saat ini bukan workflow utama.
- Workflow utama yang lebih bernilai operasional saat ini adalah Procurement Invoice List, karena hutang supplier/reporting juga dibaca di level invoice.

### D. Fakta sensitif edit/delete
- Tidak ada bukti dokumen yang mengizinkan hard delete supplier invoice.
- Tidak ada bukti dokumen yang mengizinkan edit bebas supplier invoice.
- Tidak ada bukti dokumen yang mengizinkan hard delete supplier master.
- Karena supplier invoice bisa punya efek turunan ke payment, receipt, inventory movement, projection, dan costing, maka edit/delete tidak boleh diperlakukan sebagai CRUD biasa.

---

## Scope yang dipakai

### `[SCOPE-IN]`
- Supplier List V1 utility discussion
- Procurement Invoice List sebagai workflow utama
- Procurement Create Page V2
- Procurement Invoice Detail Page
- enterprise baseline untuk edit/delete/cancel sensitif
- pertanyaan user tentang:
  - apakah detail boleh diedit
  - supplier page sebaiknya memuat angka pendukung
  - hapus bagaimana
  - edit bagaimana
  - jumlah produk yang bisa dijual / sedang dipakai
  - stock adjustment untuk rusak / cacat / selisih non-transaksi
- kebutuhan handoff agar halaman berikutnya bisa lanjut fresh

### `[SCOPE-OUT]`
- keputusan final implementasi edit supplier invoice
- keputusan final implementasi cancel/void supplier invoice
- keputusan final enrich Supplier List
- keputusan final inventory state segmentation `sellable / in use / damaged`
- implementasi stock adjustment baru
- perubahan blueprint/ADR pada halaman ini
- coding lanjutan setelah handoff

---

## Keputusan yang sudah dikunci `[DECISION]`

### 1. Opsi enterprise baseline yang dipilih
User memilih **Opsi B** sebagai baseline enterprise sementara.

Maknanya:
- `Supplier List` = modul pendukung, bukan workflow utama
- `Supplier master`
  - detail boleh
  - edit nanti hanya koreksi terbatas + audit
  - hard delete tidak boleh
- `Supplier invoice / nota supplier`
  - detail boleh
  - edit hanya boleh sebelum ada efek turunan
  - hard delete tidak boleh
  - jika sudah ada efek turunan, jalur resmi harus correction / reversal, bukan edit/delete biasa
- semua mutasi sensitif wajib audit

### 2. Next safest slice
Next safest slice setelah diskusi ini adalah:
- `Procurement Invoice Detail Page`
dan slice itu sudah dibangun sampai level surface admin + read-side backend.

### 3. Supplier List utility
Supplier List tetap sah hidup, tetapi statusnya adalah:
- supporting module
- directory / reference
- bukan layar kerja utama

### 4. Procurement List adalah layar kerja utama
Untuk bounded context supplier/procurement, layar yang paling bernilai operasional saat ini adalah:
- `Procurement Invoice List`
karena:
- invoice adalah unit read model hutang supplier
- summary payable/receipt sudah ada di level invoice
- invoice lebih dekat dengan aksi operasional harian daripada daftar supplier master

---

## Hasil yang sudah hidup `[RESULT]`

### Supplier List V1
- interactive table supplier admin hidup
- search native 2 huruf hidup
- sort/pagination server-side hidup
- page size 10 hidup
- sidebar admin link supplier hidup

### Procurement Invoice List V1
- interactive table nota supplier admin hidup
- search by nomor nota / nama PT hidup
- filter tanggal kirim hidup
- summary invoice-level:
  - grand total
  - total paid
  - outstanding
  - receipt count
  - total received qty
- tombol `Detail` dari list ke show page hidup
- sidebar admin link procurement hidup

### Procurement Create Page V2
- create page admin procurement hidup
- product lookup hidup
- line item dynamic hidup
- money input formatted hidup
- metadata nota terpisah hidup
- create/store route admin procurement hidup

### Procurement Invoice Detail Page
- detail page admin procurement hidup
- menampilkan ringkasan invoice:
  - nomor nota
  - nama PT
  - tanggal kirim
  - jatuh tempo
  - grand total
  - total paid
  - outstanding
  - receipt count
  - total received qty
- menampilkan line invoice:
  - kode barang
  - nama barang
  - merek
  - ukuran
  - qty
  - unit cost
  - line total

---

## Pertanyaan user yang harus dibawa ke halaman berikutnya `[OPEN QUESTIONS]`

### Q1. Detail ini tidak boleh diedit jadinya?
Inti pertanyaan:
- apakah page detail procurement invoice harus read-only selamanya
- atau bisa menjadi pintu edit

Status saat handoff ini:
- belum diputuskan final
- baseline enterprise yang sudah dikunci hanya:
  - edit boleh dipertimbangkan **sebelum ada efek turunan**
  - setelah ada efek turunan, edit biasa tidak boleh
- yang belum dikunci:
  - definisi persis efek turunan
  - field mana yang masih boleh diubah
  - apakah edit harus dipisah menjadi:
    - edit draft / pre-receipt
    - correction / reversal post-effect

### Q2. Halaman supplier sebaiknya ada isi selain nama?
Inti pertanyaan:
- apakah Supplier List yang hanya berisi nama terlalu boros
- apakah perlu angka pendukung seperti:
  - jumlah nota
  - total hutang / outstanding
  - total barang diterima
  - jumlah invoice aktif
  - due soon count

Status saat handoff ini:
- belum diputuskan final
- user memberi sinyal kuat bahwa halaman supplier dengan hanya nama terasa kurang bernilai
- perlu diputuskan:
  - apakah Supplier List tetap dipertahankan
  - atau diubah menjadi supplier summary list yang lebih bernilai
  - atau tetap minimal dan diarahkan ke procurement sebagai layar utama

### Q3. Hapus bagaimana?
Inti pertanyaan:
- user menilai hapus bisa saja ada kebutuhan nyata
- tidak semua kasus harus dilarang total

Status saat handoff ini:
- belum diputuskan final
- baseline enterprise sementara:
  - hard delete supplier invoice tidak boleh
  - hard delete supplier master tidak boleh
- yang perlu dibahas berikutnya:
  - apakah yang dimaksud user sebenarnya:
    - hard delete
    - soft delete
    - archive
    - cancel/void
  - kondisi apa yang mengizinkan cancel/void

### Q4. Edit bagaimana?
Inti pertanyaan:
- user menilai edit bisa saja perlu, tidak harus always forbidden

Status saat handoff ini:
- belum diputuskan final
- baseline enterprise sementara:
  - edit invoice hanya mungkin sebelum efek turunan
- yang perlu dikunci di halaman berikutnya:
  - invoice state matrix:
    - sebelum payment
    - sesudah payment pending/uploaded
    - sebelum receipt
    - sesudah receipt
    - sebelum inventory effect
    - sesudah inventory effect
  - field-level edit policy:
    - nama PT
    - tanggal kirim
    - due date
    - line qty
    - line total
    - product line
    - auto receive flags tidak relevan lagi setelah create

### Q5. Jumlah produk yang bisa dijual, sedang dipakai, dll bagaimana?
Inti pertanyaan:
- user mengangkat kebutuhan inventory state yang lebih kaya
- tidak semua stok seharusnya dianggap sellable
- mungkin perlu pemisahan:
  - bisa dijual
  - sedang dipakai / reserved / on-work
  - rusak / cacat
  - mungkin hilang / adjustment loss

Status saat handoff ini:
- belum diputuskan final
- docs yang ada baru mengunci:
  - stok negatif default dilarang
  - stok masuk normal hanya dari supplier receipt valid
  - stok tidak boleh bertambah lewat edit manual liar
- docs belum mengunci model state inventory multi-bucket seperti sellable / in-use / damaged
- ini perlu diskusi domain tersendiri sebelum coding

### Q6. Edit jumlah stok product untuk barang rusak/cacat bagaimana?
Inti pertanyaan:
- user menyatakan penurunan stok tidak selalu datang dari transaksi penjualan/servis
- ada kasus barang rusak / cacat / selisih fisik yang butuh adjustment resmi

Status saat handoff ini:
- belum diputuskan final
- baseline enterprise yang aman:
  - jangan edit `qty_on_hand` langsung di product master atau projection
  - jika ada kebutuhan rusak/cacat/selisih, itu harus lewat **inventory adjustment flow resmi**
- yang belum dikunci:
  - apakah damage/cacat cukup jadi `stock_adjustment` dengan reason
  - atau perlu bucket/state inventory terpisah
  - apakah adjustment mengurangi sellable stock saja atau qty_on_hand total
  - bagaimana audit reason / actor / before-after snapshot

---

## Analisis arah bahasan paling aman untuk halaman berikutnya `[SAFE ORDER]`

### Opsi urutan bahasan yang paling aman
1. **Kunci policy edit/cancel/void procurement invoice**
2. **Kunci utility Supplier List**
3. **Kunci inventory semantics untuk damaged / adjustment / sellable state**

Alasan:
- pertanyaan user paling sensitif dan paling dekat ke risiko data ada di edit/delete procurement invoice
- Supplier List enrichment dan inventory semantics bisa menyusul setelah edit/delete policy jelas

### Alternatif urutan
1. Utility Supplier List dulu
2. lalu edit/delete policy
3. lalu inventory semantics

Risiko:
- lebih cepat memberi hasil UI
- tetapi bisa membuat arah kerja bercabang sebelum policy sensitif invoice dikunci

### Rekomendasi
Halaman berikutnya sebaiknya mulai dari:
- **policy matrix procurement invoice edit / cancel / delete / correction**
baru setelah itu:
- supplier page utility enrichment
- inventory adjustment + damaged state semantics

---

## Rekomendasi baseline enterprise yang bisa dibawa sebagai starting point `[RECOMMENDATION]`

### Procurement invoice
#### Sebelum efek turunan
Calon baseline yang layak dibahas:
- boleh edit terbatas
- boleh cancel/void
- hard delete tetap tidak disarankan

#### Setelah ada efek turunan
Calon baseline yang layak dibahas:
- edit biasa forbidden
- delete forbidden
- hanya boleh lewat correction / reversal flow dengan audit

#### Efek turunan kandidat yang perlu diuji sebagai lock trigger
- sudah ada payment
- sudah ada receipt
- sudah ada inventory movement
- sudah ada costing / projection effect

### Supplier page
Calon enrich summary yang bisa dibahas:
- jumlah nota
- outstanding total
- invoice count belum lunas
- total received qty
- due soon count
- last shipment date

### Inventory
Calon arah enterprise yang bisa dibahas:
- jangan edit qty projection langsung
- buat adjustment flow resmi
- adjustment wajib:
  - actor
  - reason
  - timestamp
  - before / after
- pertimbangkan pemisahan semantic:
  - sellable
  - in use / reserved
  - damaged / defect
tetapi ini belum boleh diputuskan tanpa diskusi domain berikutnya

---

## File yang dibuat/diubah pada percakapan ini `[FILES]`

### File baru
- `app/Adapters/In/Http/Requests/Procurement/SupplierTableQueryRequest.php`
- `app/Application/Procurement/DTO/SupplierTableQuery.php`
- `app/Application/Procurement/UseCases/GetSupplierTableHandler.php`
- `app/Ports/Out/Procurement/SupplierTableReaderPort.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierTableReaderAdapter.php`
- `app/Adapters/Out/Procurement/Concerns/SupplierTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/SupplierTableFilters.php`
- `app/Adapters/Out/Procurement/Concerns/SupplierTableOrdering.php`
- `app/Adapters/Out/Procurement/Concerns/SupplierTablePayload.php`
- `app/Adapters/In/Http/Controllers/Admin/Supplier/SupplierIndexPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Supplier/SupplierTableDataController.php`
- `resources/views/admin/suppliers/index.blade.php`
- `public/assets/static/js/pages/admin-suppliers-table.js`
- `tests/Feature/Procurement/SupplierIndexPageFeatureTest.php`
- `tests/Feature/Procurement/SupplierTableDataAccessFeatureTest.php`
- `tests/Feature/Procurement/SupplierTableDataQueryFeatureTest.php`
- `tests/Feature/Procurement/SupplierTableDataValidationFeatureTest.php`

- `app/Adapters/In/Http/Requests/Procurement/ProcurementInvoiceTableQueryRequest.php`
- `app/Application/Procurement/DTO/ProcurementInvoiceTableQuery.php`
- `app/Application/Procurement/UseCases/GetProcurementInvoiceTableHandler.php`
- `app/Ports/Out/Procurement/ProcurementInvoiceTableReaderPort.php`
- `app/Adapters/Out/Procurement/DatabaseProcurementInvoiceTableReaderAdapter.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableFilters.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableOrdering.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTablePayload.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ProcurementInvoiceIndexPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ProcurementInvoiceTableDataController.php`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`
- `tests/Feature/Procurement/ProcurementInvoiceIndexPageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceTableDataQueryFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceTableDataValidationFeatureTest.php`

- `app/Adapters/In/Http/Controllers/Admin/Procurement/ProductLookupController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/CreateSupplierInvoicePageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/StoreSupplierInvoiceController.php`
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `public/assets/static/js/pages/admin-procurement-create.js`
- `tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php`
- `tests/Feature/Procurement/ProductLookupFeatureTest.php`

- `app/Ports/Out/Procurement/ProcurementInvoiceDetailReaderPort.php`
- `app/Application/Procurement/UseCases/GetProcurementInvoiceDetailHandler.php`
- `app/Adapters/Out/Procurement/DatabaseProcurementInvoiceDetailReaderAdapter.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ProcurementInvoiceDetailPageController.php`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`

### File diubah
- `routes/web/procurement.php`
- `resources/views/layouts/partials/sidebar-admin.blade.php`
- `app/Providers/HexagonalServiceProvider.php`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`

---

## Bukti verifikasi `[PROOF]`

### Feature tests yang terbukti PASS di percakapan ini
- `php artisan test tests/Feature/Procurement/SupplierIndexPageFeatureTest.php`
- `php artisan test tests/Feature/Procurement/SupplierTableDataAccessFeatureTest.php`
- `php artisan test tests/Feature/Procurement/SupplierTableDataQueryFeatureTest.php`
- `php artisan test tests/Feature/Procurement/SupplierTableDataValidationFeatureTest.php`
- `php artisan test tests/Feature/Procurement/ProcurementInvoiceIndexPageFeatureTest.php`
- `php artisan test tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php`
- `php artisan test tests/Feature/Procurement/ProcurementInvoiceTableDataQueryFeatureTest.php`
- `php artisan test tests/Feature/Procurement/ProcurementInvoiceTableDataValidationFeatureTest.php`
- `php artisan test tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php`
- `php artisan test tests/Feature/Procurement/ProductLookupFeatureTest.php`

### Syntax / route / hygiene yang terbukti PASS
- `php -l` untuk file-file baru Supplier / Procurement / Detail
- `php artisan route:list | grep -E 'admin.suppliers|admin.procurement.supplier-invoices|procurement.supplier-invoices'`
- `make audit-lines` → PASS
- `make lint` → PASS

### Bukti visual / struktur yang terkunci dari output file
- Supplier List V1 hanya menampilkan nama PT
- Procurement List menampilkan invoice-level summary
- Procurement Create V2 memakai lookup product
- Procurement Detail menampilkan summary + line invoice
- Link `Detail` dari procurement list sudah ada

---

## Blocker aktif `[BLOCKER]`
- tidak ada blocker teknis aktif
- blocker yang tersisa adalah **keputusan domain/policy**, bukan syntax atau wiring

---

## State repo yang penting untuk langkah berikutnya
- Supplier master saat ini minimal dan tidak menjadi workflow utama.
- Procurement invoice sekarang sudah punya:
  - list
  - create
  - detail
- Surface edit/delete/cancel belum dibuat dan belum boleh diasumsikan bebas.
- Product master tetap wajib existing sebelum procurement line valid.
- Procurement detail page sekarang read-only.
- Inventory semantics lanjutan untuk `sellable / in use / damaged` belum dikunci.
- Manual stock correction untuk rusak/cacat belum punya flow resmi di repo snapshot percakapan ini.
- Karena docs tidak memberi rule final edit/delete procurement, halaman berikutnya harus memulai dari policy matrix, bukan langsung coding mutasi sensitif.

---

## Next step paling aman `[NEXT]`

### Prioritas utama untuk halaman berikutnya
**Kunci policy matrix procurement invoice edit / cancel / delete / correction**

Agenda minimum yang harus dibahas:
1. definisi `efek turunan`
2. kapan invoice dianggap locked
3. field mana yang masih boleh diedit sebelum lock
4. apakah yang dibutuhkan user adalah:
   - edit
   - cancel/void
   - archive
   - hard delete
5. bentuk audit minimum untuk mutasi procurement invoice

### Prioritas kedua
**Putuskan utility Supplier List**
Pilihan yang perlu dibandingkan:
- tetap minimal
- enrich dengan supplier summary
- merge value ke procurement-only workflow

### Prioritas ketiga
**Kunci inventory semantics & stock adjustment flow**
Agenda minimum:
- apakah cukup adjustment flow resmi
- apakah perlu state/bucket stock tambahan:
  - bisa dijual
  - sedang dipakai
  - rusak/cacat
- bagaimana before/after + reason + actor dicatat

---

## Catatan masuk halaman berikutnya
Saat membuka halaman berikutnya, bawa minimal:
- handoff ini
- `docs/blueprint/blueprint_v1.md`
- `docs/workflow/workflow_v1.md`
- `docs/adr/0008-audit-first-sensitive-mutations.md`
- `docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
- `docs/adr/0002-negative-stock-policy-default-off.md`
- `docs/adr/0011-money-stored-as-integer-rupiah.md`
- `docs/handoff/handoff_step_12_reporting_v1_closed.md`
- snapshot repo area:
  - procurement admin controllers
  - procurement read-side adapters
  - reporting supplier payable
  - inventory adapters / policies
  - product inventory projection/costing area

---

## Ringkasan singkat siap tempel

### Ringkasan
- Supplier List V1, Procurement Invoice List V1, Procurement Create Page V2, dan Procurement Invoice Detail Page sudah hidup.
- User mempertanyakan nilai Supplier List bila hanya berisi nama.
- User juga mempertanyakan kemungkinan edit/hapus invoice dan kebutuhan stock adjustment untuk rusak/cacat.
- Dokumen belum mengunci rule final edit/delete/cancel procurement invoice.
- Baseline enterprise sementara yang dipilih user adalah Opsi B:
  - detail boleh
  - edit invoice hanya sebelum ada efek turunan
  - hard delete tidak boleh
  - setelah ada efek turunan, gunakan correction/reversal
  - mutasi sensitif wajib audit
- Pertanyaan berikutnya yang harus dibahas bukan coding dulu, tetapi policy matrix:
  - procurement invoice edit/cancel/delete/correction
  - utility Supplier List
  - inventory damaged/adjustment semantics

### Jangan dibuka ulang
- Procurement/Supplier adalah bounded context resmi.
- Product master wajib ada sebelum supplier flow valid.
- Hutang supplier v1 dibaca di level invoice.
- Hard delete supplier invoice tidak boleh diasumsikan boleh.
- Hard delete supplier master tidak boleh diasumsikan boleh.
- Detail procurement invoice sekarang read-only sampai policy edit/cancel dikunci.

### Data minimum bila ingin lanjut
- handoff ini
- blueprint/workflow/ADR yang disebut di atas
- snapshot repo procurement + inventory terbaru
- pertanyaan user yang harus dibahas:
  - apakah detail boleh diedit
  - Supplier List sebaiknya menampilkan apa saja
  - delete/cancel seperti apa yang dibutuhkan
  - bagaimana menangani stok rusak/cacat/selisih non-transaksi