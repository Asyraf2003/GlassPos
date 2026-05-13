# Handoff V2 - Supplier Invoice Blueprint

## Status
Selesai untuk scope halaman blueprint/domain `supplier invoice`.

Status akhir:
- blueprint domain `supplier invoice`: selesai
- kontrak policy edit / void / correction / reversal: selesai
- kontrak migration/schema: selesai
- kontrak writer + versioning + audit: selesai
- kontrak search / filter / reporting impact: selesai
- kontrak inline create product dari faktur: selesai
- blueprint UI/JS keyboard-centric: selesai
- implementasi kode: belum
- handoff-ready: ya

Progress halaman ini: 100%

## Tujuan yang ditutup
Menutup domain/page `supplier invoice` sampai level blueprint eksekusi, agar halaman berikutnya bisa langsung implementasi dengan arah tetap, dengan scope:

- identitas bisnis faktur supplier
- policy editability faktur pre-effect vs post-effect
- versioning + audit trail faktur
- correction / reversal linked document
- search / filter / reporting impact
- create product explicit dari konteks faktur
- keyboard-centric create/edit flow
- workflow eksekusi bertahap untuk halaman implementasi berikutnya

## Scope in
- domain `supplier invoice`
- kontrak create / update / void / correction / reversal
- kontrak schema/migration `supplier_invoices`
- kontrak schema/migration `supplier_invoice_lines`
- kontrak tabel histori `supplier_invoice_versions`
- kontrak audit faktur ke foundation `audit_events`
- kontrak search/filter/detail/reporting compatibility
- kontrak inline quick-create product dari layar faktur
- blueprint UI/JS keyboard-centric

## Scope out
- implementasi migration
- implementasi writer/usecase/query/UI
- event sourcing penuh lintas modul
- redesign bounded context lain
- export/import faktur
- bulk action faktur
- restore / undelete faktur
- inventory engine baru
- diskusi ulang domain product kecuali ada bug nyata
- diskusi ulang policy procurement yang sudah dikunci

## Referensi baseline repo yang dipakai

### Core procurement dan write path saat ini
- `app/Adapters/In/Http/Requests/Procurement/CreateSupplierInvoiceRequest.php`
- `app/Application/Procurement/UseCases/CreateSupplierInvoiceHandler.php`
- `app/Core/Procurement/SupplierInvoice/SupplierInvoice.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceWriterAdapter.php`
- `app/Ports/Out/Procurement/SupplierInvoiceWriterPort.php`

### Schema faktur saat ini
- `database/migrations/2026_03_12_000200_create_supplier_invoices_table.php`
- `database/migrations/2026_03_12_000300_create_supplier_invoice_lines_table.php`

### Read-side faktur / reporting saat ini
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableFilters.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailSummaryQuery.php`
- `app/Adapters/Out/Reporting/DatabaseSupplierPayableReportingSourceReaderAdapter.php`

### UI faktur saat ini
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`

### Kiblat product
- `docs/handoff/v2/product-handoff.md`
- `resources/views/admin/products/create.blade.php`
- `public/assets/static/js/pages/admin-product-master-form.js`
- `app/Adapters/Out/ProductCatalog/DatabaseVersionedProductWriterAdapter.php`

### Fondasi audit/versioning
- `database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php`
- `database/migrations/2026_04_06_230300_create_product_and_supplier_versions_tables.php`
- `app/Adapters/Out/Audit/DatabaseAuditLogAdapter.php`

### Policy handoff procurement
- `docs/handoff/ui/handoff_03_supplier_procurement_followup_questions.md`
- `docs/handoff/ui/handoff_04_supplier_procurement_policy_and_supplier_summary_execution_ready.md`

## Fakta yang terkunci

1. `supplier_invoices.id` adalah technical id internal, bukan nomor faktur bisnis.
2. `nomor_faktur` adalah identitas bisnis yang harus diisi client.
3. Create flow faktur saat ini belum membawa `nomor_faktur` di request, core, writer, dan view.
4. Read-side list/detail/reporting saat ini masih memakai `supplier_invoices.id` untuk identitas baca.
5. Faktur supplier bukan CRUD biasa.
6. Policy yang dikunci:
   - pre-effect: boleh edit, boleh void
   - post-effect: tidak boleh edit biasa
   - post-effect: tidak boleh void
   - post-effect: harus correction / reversal
7. Trigger lock yang dikunci:
   - ada receipt
   - ada inventory movement
   - ada payment efektif
8. Faktur tidak boleh soft delete seperti master product/supplier.
9. Histori perubahan faktur harus immutable dan append-only.
10. Product dari line faktur harus existing.
11. Jika product belum ada, user boleh buat dari layar faktur, tetapi harus explicit quick-create product, bukan create diam-diam oleh writer invoice.
12. Kiblat UX/JS dan writer pattern adalah `product`.

## Keputusan arsitektur yang dikunci

### 1. Model arsitektur faktur
Dipakai model:

- active row + immutable mutation trail

Maknanya:
- `supplier_invoices` tetap menjadi source row operasional
- histori perubahan masuk ke tabel version khusus faktur
- log lintas domain masuk ke audit foundation resmi

### 2. Identitas faktur
- `id` internal tetap dipakai untuk FK / join / route
- `nomor_faktur` dipakai untuk user / business / search / reporting display

### 3. Lifecycle faktur
Nilai yang dikunci:
- `active`
- `voided`
- `superseded`

### 4. Jenis dokumen
Nilai yang dikunci:
- `invoice`
- `correction`
- `reversal`

### 5. Soft delete
Untuk faktur:
- tidak ada

### 6. Mutation policy
- pre-effect: update biasa dan void
- post-effect: correction / reversal linked document

### 7. Audit resmi
Sumber audit resmi faktur:
- `audit_events`
- `audit_event_snapshots`

Bukan `audit_logs` legacy.

### 8. Versioning resmi
Sumber version resmi faktur:
- `supplier_invoice_versions`

### 9. UI keyboard-centric
Kiblat pola:
- `product`

## Kontrak migration/schema yang dikunci

### A. `supplier_invoices`
Tabel ini tetap dipakai sebagai active operational row.

Kolom final yang harus ada:
- `nomor_faktur`
- `nomor_faktur_normalized`
- `document_kind`
- `lifecycle_status`
- `origin_supplier_invoice_id`
- `superseded_by_supplier_invoice_id`
- `voided_at`
- `void_reason`
- `last_revision_no`

Catatan:
- `nomor_faktur` wajib untuk transaksi baru
- unique constraint `nomor_faktur` belum dikunci di tahap ini sampai data existing diaudit
- `is_locked` sengaja tidak disimpan sebagai flag terpisah

Index minimum:
- `nomor_faktur_normalized`
- `jatuh_tempo`
- `(lifecycle_status, tanggal_pengiriman)`
- `(lifecycle_status, jatuh_tempo)`
- `origin_supplier_invoice_id`
- `superseded_by_supplier_invoice_id`

### B. `supplier_invoice_lines`
Tambahan minimum:
- `line_no`

Constraint minimum:
- unique `(supplier_invoice_id, line_no)`

### C. `supplier_invoice_versions`
Tabel baru immutable untuk histori revision faktur.

Kolom minimum:
- `id`
- `supplier_invoice_id`
- `revision_no`
- `event_name`
- `changed_by_actor_id`
- `change_reason`
- `changed_at`
- `snapshot_json`

Event minimum:
- `supplier_invoice_created`
- `supplier_invoice_updated`
- `supplier_invoice_voided`
- `supplier_invoice_correction_created`
- `supplier_invoice_reversal_created`

## Kontrak application/usecase yang dikunci

### 1. Create
Usecase:
- `CreateSupplierInvoice`

Input minimum:
- `nomor_faktur`
- `nama_pt_pengirim`
- `tanggal_pengiriman`
- `tanggal_jatuh_tempo` atau default policy
- `auto_receive`
- `tanggal_terima`
- `lines[]`
  - `line_no`
  - `product_id`
  - `qty_pcs`
  - `line_total_rupiah`

Aturan:
- `nomor_faktur` wajib input client
- product harus existing
- `unit_cost_rupiah` derived, bukan input bebas
- create tidak membuat payment row

### 2. Update pre-effect
Usecase:
- `UpdateSupplierInvoiceBeforeEffect`

Input minimum:
- `supplier_invoice_id`
- `expected_revision_no`
- `change_reason`

Editable:
- `nomor_faktur`
- `tanggal_pengiriman`
- `jatuh_tempo`
- `notes`
- add/remove line
- `line product`
- `line qty`
- `line total`

Tidak editable:
- supplier
- payment state
- receipt state
- inventory effect
- auto receive flag setelah create

### 3. Void pre-effect
Usecase:
- `VoidSupplierInvoiceBeforeEffect`

Input minimum:
- `supplier_invoice_id`
- `expected_revision_no`
- `void_reason`

### 4. Correction post-effect
Usecase:
- `CreateSupplierInvoiceCorrection`

### 5. Reversal post-effect
Usecase:
- `CreateSupplierInvoiceReversal`

### 6. Lock guard
Service/policy wajib:
- `SupplierInvoiceLockPolicy` atau nama setara

## Kontrak writer + versioning + audit yang dikunci

### Writer pattern
Dipakai pola product:
- writer versioned khusus
- bukan writer biasa

Direkomendasikan:
- `DatabaseVersionedSupplierInvoiceWriterAdapter`
- `SupplierInvoiceChangeContext`
- port lifecycle faktur khusus

### Dual-write resmi dalam satu transaksi
Urutan:
1. begin transaction
2. tulis active row
3. hitung `next revision_no`
4. tulis `supplier_invoice_versions`
5. tulis `audit_events`
6. tulis `audit_event_snapshots`
7. commit

Kalau gagal:
- rollback penuh

### Snapshot version minimum
`snapshot_json` harus memuat:
- header invoice
- `nomor_faktur`
- supplier snapshot
- shipment/due date
- `document_kind`
- `lifecycle_status`
- relasi origin/superseded
- `grand_total_rupiah`
- full lines dengan `line_no`

### Change context minimum
Seperti product:
- `actor_id`
- `actor_role`
- `source_channel`
- `reason`

## Kontrak search / filter / reporting yang dikunci

### Search faktur
Harus pindah ke:
- `nomor_faktur`
- `nomor_faktur_normalized`
- nama supplier
- snapshot nama supplier

Bukan `id` internal sebagai primary user search.

### Filter list minimum

`status_mode`:
- `active`
- `voided`
- `history`
- `all`

`document_kind_mode`:
- `invoice_only`
- `adjustments_only`
- `all`

Tanggal:
- shipment from/to
- due date from/to

Due status:
- `all`
- `not_due`
- `due_today_or_earlier`
- `overdue_unpaid`

### Default list
Default hanya:
- `document_kind = invoice`
- `lifecycle_status = active`

### Detail page
Harus tampil minimum:
- `nomor_faktur`
- `document_kind`
- `lifecycle_status`
- origin document
- superseded document
- lock reasons
- allowed actions

### Reporting hutang supplier
Secara default:
- `invoice` aktif: masuk
- `voided`: tidak masuk
- `superseded`: tidak masuk sebagai dokumen aktif utama
- `correction` / `reversal`: masuk sesuai semantik finansial read-side

### Reconciliation report
Harus memakai rule inclusion yang sama.
Tidak boleh menjumlah semua invoice mentah tanpa peduli lifecycle.

## Kontrak inline create product yang dikunci

Dipilih:
- Opsi B

Maknanya:
- quick-create explicit dari layar faktur
- hasil sukses auto-bind ke line aktif

### Aturan utama
- explicit, bukan implicit
- tetap memakai jalur product resmi
- tidak menulis product lewat writer invoice
- hasil sukses mengembalikan `product_id` ke line aktif
- fokus kembali ke `qty_pcs`

### Field minimum quick-create
Kiblat ke product create:
- `kode_barang`
- `nama_barang`
- `merek`
- `ukuran`
- `harga_jual`

### Yang tidak boleh terjadi
- create product diam-diam dari pencarian line
- save invoice otomatis saat create product
- mutasi stok melalui quick-create product

## Blueprint UI/JS keyboard-centric yang dikunci

### Fokus awal create
- `nomor_faktur`
- `nama_pt_pengirim`
- `tanggal_pengiriman`
- `tanggal_terima`
- `product search` line pertama

### Header flow
- `Enter` maju
- `Shift+Enter` mundur
- field header terakhir lanjut ke line pertama

### Per line
Urutan:
- `product search`
- `qty_pcs`
- `line_total_display`

Perilaku:
- `Enter` di product search memilih hasil
- `Enter` di qty pindah ke total
- `Enter` di total pindah ke line berikutnya atau buat line baru

### Shortcut minimum
- `Ctrl+S` simpan
- `Ctrl+Enter` tambah line baru
- `Ctrl+Backspace` hapus line kosong
- `Ctrl+K` quick-create product dari product search aktif
- `Ctrl+Shift+V` void di edit pre-effect

### Quick-create product flow
- buka drawer/modal
- fokus awal `kode_barang`
- urutan `Enter` meniru product
- sukses kembali ke line aktif
- fokus ke `qty_pcs`

### Detail post-effect
Tidak ada edit biasa.
Yang ada:
- `Buat Koreksi`
- `Buat Reversal`

## Gaps yang masih jujur diakui

1. Persistence nyata `nomor_faktur` di repo aktif belum terbukti di file yang dibaca.
2. Semantik unique `nomor_faktur` belum final.
3. Audit seluruh report lain di repo belum ditutup satu per satu.
4. `notes`, `reason_code`, `approval_marker` belum terlihat hidup di current create flow, tetapi sudah dikunci sebagai kebutuhan correction / reversal.

## Workflow eksekusi halaman berikutnya

Halaman berikutnya harus langsung eksekusi dengan urutan:

1. audit final branch kerja untuk persistence `nomor_faktur`
2. implement migration/schema faktur
3. implement port + writer versioned faktur
4. implement application/usecase create/update/void/correction/reversal
5. implement integration audit foundation
6. implement read-side list/detail/filter/reporting compatibility
7. implement quick-create product dari faktur
8. implement UI/JS keyboard-centric faktur
9. verifikasi migration, feature test, reporting compatibility, manual flow
10. tulis handoff final hasil implementasi

## Active step untuk halaman berikutnya
Step aktif pertama yang wajib diambil:

- implement migration/schema faktur sesuai kontrak yang sudah dikunci

Fokus awal:
- cek apakah `nomor_faktur` sudah persisted di branch kerja
- finalkan migration `supplier_invoices`
- finalkan migration `supplier_invoice_lines`
- buat migration `supplier_invoice_versions`

Jangan lompat ke UI dulu.

## Proof / bukti kenapa halaman ini boleh ditutup

Halaman ini boleh ditutup karena keputusan utamanya sudah punya pijakan repo yang jelas:

- current create faktur belum membawa `nomor_faktur`
- current read-side masih memakai `id` internal
- current writer faktur masih writer biasa
- current audit procurement masih ke audit legacy
- product sudah punya contoh versioned write dan keyboard flow
- procurement policy pre/post effect sudah ada handoff-nya

Jadi baseline untuk eksekusi sudah cukup kuat.

## Safest next step
Mulai halaman baru dan langsung eksekusi:

1. audit final branch kerja terhadap persistence `nomor_faktur`
2. kerjakan migration faktur
3. jangan buka ulang diskusi domain kecuali ada bukti repo baru yang konflik

## Ringkasan satu kalimat
Kasus blueprint `supplier invoice` selesai sampai level domain + kontrak eksekusi, dengan baseline final: `nomor_faktur` jadi identitas bisnis input client, faktur memakai active row + immutable versioning, tidak soft delete, edit hanya pre-effect, post-effect wajib correction/reversal, audit resmi pindah ke foundation, search/reporting dibuat lifecycle-aware, dan UI create/edit dibuat keyboard-centric dengan kiblat implementasi product.

