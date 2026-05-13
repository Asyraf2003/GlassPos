# Handoff — Supplier / Procurement Policy Lock + Supplier Summary List V1 (Execution Ready)

## Metadata
- Tanggal: 2026-03-20
- Nama slice / topik:
  - Procurement invoice policy lock
  - Payment policy tenant ringan
  - Supplier Summary List V1
  - Inventory semantics baseline only
- Status: POLICY LOCKED / READY FOR CODE EXECUTION
- Progress:
  - Procurement invoice policy: 100%
  - Supplier Summary List V1 policy: 100%
  - Inventory semantics: baseline only, stop here
- Catatan mode kerja:
  - Halaman berikutnya diarahkan untuk **eksekusi kode**
  - Jangan buka diskusi panjang lagi kecuali ada **blocker kritikal** yang benar-benar menghambat implementasi

---

## Target halaman kerja berikutnya
Target utama halaman berikutnya adalah:

**Eksekusi kode untuk Supplier Summary List V1**

Bukan membuka ulang diskusi domain yang sudah dikunci di handoff ini.

Diskusi hanya boleh dibuka lagi jika:
- ada konflik fakta repo yang nyata
- ada kontradiksi implementasi terhadap keputusan yang sudah terkunci
- ada dependency teknis yang membuat implementasi tidak bisa lanjut tanpa keputusan tambahan yang benar-benar minimum

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
- Handoff sumber:
  - `docs/handoff/handoff_step_04.md`
  - `docs/handoff/handoff_step_12_reporting_v1_closed.md`
  - handoff Supplier / Procurement Follow-up Questions & Decision Parking 2026-03-20
- Snapshot repo area yang relevan:
  - `routes/web/procurement.php`
  - `resources/views/admin/suppliers/index.blade.php`
  - `public/assets/static/js/pages/admin-suppliers-table.js`
  - `app/Adapters/In/Http/Controllers/Admin/Supplier/*`
  - `app/Application/Procurement/DTO/*`
  - `app/Application/Procurement/UseCases/*`
  - `app/Adapters/Out/Procurement/*`
  - `app/Ports/Out/Procurement/*`
  - `app/Adapters/Out/Reporting/DatabaseSupplierPayableReportingSourceReaderAdapter.php`
  - `app/Providers/HexagonalServiceProvider.php`
  - `tests/Feature/Procurement/*`
  - `tests/Feature/Reporting/GetSupplierPayableSummaryFeatureTest.php`

---

## Fakta terkunci `[FACT]`

### A. Fakta repo / UI / read-side yang sudah hidup
- Supplier List saat ini hidup, tetapi masih minimal:
  - route index admin
  - route JSON table admin
  - server-side search/sort/pagination
  - data yang tampil masih praktis hanya `nama_pt_pengirim`
- Procurement Invoice List V1 hidup
- Procurement Create Page V2 hidup
- Procurement Invoice Detail Page hidup
- Procurement product lookup hidup
- Procurement detail saat ini read-only
- Reporting hutang supplier v1 masih dibaca di level invoice, bukan di level supplier
- Model line procurement supplier saat ini praktis berbasis:
  - `product_id`
  - `qty_pcs`
  - `line_total_rupiah`
- `unit_cost_rupiah` saat ini adalah nilai turunan, bukan input bebas
- Inventory engine repo saat ini masih bertumpu pada:
  - `inventory_movements` sebagai ledger
  - `product_inventory.qty_on_hand` sebagai projection saldo tunggal
- Repo saat ini belum punya multi-bucket inventory projection native

### B. Fakta domain yang sudah terkunci dari diskusi ini
- Procurement invoice tidak boleh diperlakukan sebagai CRUD biasa setelah punya efek turunan
- Hard delete supplier invoice tidak boleh
- Hard delete supplier master tidak boleh
- Supplier page saat ini memang terasa kurang bernilai bila hanya berisi nama
- Supplier List layak dipertahankan, tetapi harus naik kelas menjadi summary list ringan
- Inventory semantics lanjutan memang sempat dibuka, tetapi pembahasan detail trigger/event sudah dinilai mulai over-scope untuk halaman ini
- Inventory cukup berhenti di baseline tinggi pada handoff ini

### C. Fakta mode kerja yang perlu dibawa
- User ingin halaman berikutnya fokus ke **eksekusi kode**
- Jangan pecah diskusi jadi terlalu banyak pertanyaan kecil lagi
- Bila butuh klarifikasi, kelompokkan pertanyaan minimum sekaligus
- Jangan buka domain baru kalau tidak kritikal untuk implementasi target berikutnya

---

## Scope yang dipakai

### `[SCOPE-IN]`
- Policy procurement invoice
- Payment policy supplier untuk tenant ringan
- Supplier Summary List V1
- Inventory semantics baseline tinggi
- Arahan kerja halaman berikutnya agar langsung eksekusi

### `[SCOPE-OUT]`
- Detail event perpindahan bucket inventory
- Flow stock adjustment resmi detail
- Integrasi bucket inventory dengan transaction/note engine
- Implementasi reversal document sekarang juga
- Diskusi panjang tambahan yang tidak diperlukan untuk Supplier Summary List V1

---

## Keputusan yang dikunci `[DECISION]`

# 1. Procurement Invoice Policy — FINAL LOCK

## 1.1 State lifecycle
### Pre-effect
- view = ya
- edit = ya
- cancel/void = ya
- hard delete = tidak
- correction/reversal = tidak

### Post-effect
- view = ya
- edit = tidak
- cancel/void = tidak
- hard delete = tidak
- correction/reversal = ya

## 1.2 Trigger lock procurement invoice
Procurement invoice menjadi locked bila ada salah satu:
- receipt tercatat
- inventory movement tercatat
- payment efektif tercatat

Catatan:
- costing/projection bukan trigger lock utama

## 1.3 Editable fields untuk invoice pre-effect
- `supplier_id / supplier` = tidak boleh diedit
- `invoice_number` = boleh
- `invoice_date` = boleh
- `due_date` = boleh
- `notes` = boleh
- `line_add_remove` = boleh
- `line_product` = boleh
- `line_qty` = boleh
- `line_total_rupiah` = boleh
- `line_discount` = N/A, belum ada di model supplier procurement saat ini
- `line_subtotal_auto_recalc` = N/A sebagai field; model saat ini memakai `unit_cost` hasil turunan dari total line / qty

## 1.4 Cancel / Void semantics
Untuk invoice pre-effect:
- `cancel/void` = soft operational removal
- invoice tetap disimpan
- invoice tidak muncul di default list aktif
- invoice tetap muncul di history/filter/audit
- `cancel/void` bukan hard delete

## 1.5 Correction / Reversal semantics
Untuk invoice post-effect:
- koreksi dilakukan dengan **linked compensating document**
- invoice asal tetap utuh dan locked
- koreksi bukan edit biasa
- reversal bisa full maupun partial

## 1.6 Reversal document minimum
### Header minimum
- `original_supplier_invoice_id`
- `reversal_number`
- `reversal_date`
- `reason_code`
- `reason_notes`
- `created_by_actor`
- `approval_marker`

### Reason code taxonomy
- `wrong_invoice_meta`
- `wrong_product`
- `wrong_qty`
- `wrong_amount`
- `duplicate_invoice`
- `supplier_return_or_rejection`
- `other`

### Approval marker semantics
- `not_required`
- `pending_approval`
- `approved`
- `rejected`

Default tenant ringan:
- `approval_marker = not_required`

### Payload line reversal
Untuk tiap line reversal:
- `reversal_qty_pcs`
- `reversal_line_total_rupiah`

Makna:
- mendukung full reversal
- mendukung partial reversal
- tetap mengikuti model procurement line yang berbasis qty + total line

---

# 2. Payment Policy Supplier — Tenant Ringan, Tetap Enterprise

## 2.1 Prinsip utama
Untuk tenant paman:
- payment supplier tidak perlu dibuat berat seperti approval-heavy AP enterprise
- tetapi tetap harus enterprise secara source of truth dan audit
- profit operasional tidak perlu dipaksa bergantung pada status lunas supplier
- status lunas / belum lunas lebih berfungsi sebagai kebutuhan operasional + notif

## 2.2 Baseline payment yang dikunci
- payment harus datang dari **aksi eksplisit admin**
- tidak boleh auto-lunas diam-diam
- tidak boleh reminder-only palsu
- tombol utama konsep:
  - `Tandai Sudah Dibayar`

## 2.3 Payment command shape
- default nominal = outstanding penuh
- nominal boleh diubah
- hasil bisa:
  - partial
  - lunas

## 2.4 Payment form minimum
- `payment_date` = wajib
- `amount` = wajib
- `payment_method` = opsional
- `notes` = opsional
- `proof_attachment` = opsional

## 2.5 Payment effectiveness
- submit payment = langsung efektif
- proof attachment tidak menentukan sah/tidaknya payment
- proof hanya enrich data

## 2.6 Source of truth supplier payment
- `Create Invoice` = hanya create invoice
- `supplier_payments` row **tidak** lahir saat create invoice
- `supplier_payments` row baru lahir saat explicit payment action
- placeholder payment saat create invoice **tidak dipakai**

Makna:
- outstanding tidak tercemar placeholder
- audit lebih bersih
- tenant paman tetap ringan
- app tetap enterprise

---

# 3. Supplier Summary List V1 — FINAL LOCK

## 3.1 Arah halaman
Supplier page **dipertahankan**, tetapi bukan lagi directory minimal.

Arah final:
- `Supplier List` menjadi **Supplier Summary List ringan**

## 3.2 Grain
- grain = **supplier**
- perlu read model supplier-grain baru
- tidak cukup menambal UI existing
- tidak boleh sekadar menyalin mentah reporting invoice-grain lama

## 3.3 Field minimum V1
- `invoice_count`
- `outstanding_rupiah`
- `invoice_unpaid_count`
- `last_shipment_date`

## 3.4 Definisi field
### `invoice_count`
- jumlah invoice **aktif / non-void** milik supplier
- boleh mencakup invoice yang lunas maupun belum lunas
- tidak mencakup invoice void

### `outstanding_rupiah`
- total outstanding semua invoice **aktif / non-void** milik supplier
- outstanding per invoice = `grand_total_rupiah - effective_paid_rupiah`
- payment yang dihitung harus mengikuti policy final baru:
  - payment efektif dari explicit payment action
  - bukan placeholder auto-create

### `invoice_unpaid_count`
- jumlah invoice **aktif / non-void**
- dengan `outstanding_rupiah > 0`

### `last_shipment_date`
- `shipment_date` terbaru
- dari invoice **aktif / non-void**
- milik supplier tersebut

## 3.5 Catatan penting implementasi
- summary supplier v1 harus built on read model baru
- jangan bergantung mentah pada report `supplier_payable_summary` lama, karena report existing masih invoice-grain
- saat implementasi, logic payment harus mengikuti source-of-truth final yang baru dikunci di handoff ini

---

# 4. Inventory Semantics — BASELINE ONLY, STOP HERE

## 4.1 Baseline inventory
Arah inventory yang dipilih:
- **multi-bucket stock semantics**

## 4.2 Bucket minimum v1
- `sellable`
- `in_use`
- `damaged`

## 4.3 Definisi bucket
### `sellable`
- stok layak dijual / layak dipakai untuk transaksi normal
- masih utuh
- belum dialokasikan
- belum masuk `in_use`
- belum masuk `damaged`

### `in_use`
- stok yang sudah dialokasikan
- atau sedang dipakai
- reserved + sedang dipakai digabung ke bucket ini untuk v1
- stok ini tidak lagi `sellable`

### `damaged`
- semua stok tidak layak jual / pakai, termasuk:
  - rusak
  - cacat
  - gagal pakai
  - reject internal

## 4.4 Boundary penting
Pembahasan inventory **cukup berhenti di sini** pada handoff ini.

Jangan lanjut di halaman berikutnya ke:
- trigger perpindahan bucket
- event `sellable -> in_use`
- adjustment flow detail
- UI adjustment
- integrasi bucket inventory ke transaction/note engine

Kecuali halaman berikutnya memang sengaja dibuka khusus untuk slice inventory lanjutan.

---

## Hasil yang sudah siap dipakai untuk eksekusi `[RESULT READY]`

### Siap dieksekusi sekarang
**Supplier Summary List V1**

Karena:
- policy-nya sudah cukup terkunci
- tidak perlu membuka domain transaksi lebih jauh
- tidak perlu membuka inventory detail lebih jauh
- tidak perlu diskusi panjang lagi kecuali ada blocker implementasi nyata

### Belum untuk dieksekusi di halaman berikutnya ini
- reversal document procurement
- adjustment flow inventory
- multi-bucket inventory projection detail
- integrasi inventory bucket dengan transaction engine

---

## Rekomendasi implementasi halaman berikutnya `[NEXT EXECUTION]`

## Target implementasi utama
Bangun **Supplier Summary List V1** dengan:
- read model supplier-grain baru
- controller / request / DTO / port / adapter yang sesuai arsitektur repo
- table payload berisi:
  - `nama_pt_pengirim`
  - `invoice_count`
  - `outstanding_rupiah`
  - `invoice_unpaid_count`
  - `last_shipment_date`
- update blade + JS table supplier agar membaca field baru
- test minimal:
  - access/page
  - data query
  - sorting/filter yang tetap relevan
  - payload field summary
  - perhitungan invoice aktif/non-void
  - perhitungan outstanding sesuai payment source-of-truth final

## Guardrail implementasi
Pada halaman berikutnya:
- jangan ubah inventory engine
- jangan buka bucket movement policy
- jangan buka lagi payment policy
- jangan buka lagi procurement reversal policy
- fokus hanya ke **Supplier Summary List V1**

## Kapan boleh diskusi lagi
Diskusi tambahan hanya kalau:
- implementasi menemukan konflik nyata antara keputusan handoff ini dengan struktur repo
- ada data/field yang ternyata tidak tersedia di source existing sehingga perlu keputusan teknis minimum
- ada kontradiksi faktual pada test/adapter/query

Selain itu:
- **langsung eksekusi kode**

---

## Bukti verifikasi yang relevan `[PROOF SNAPSHOT]`
- Supplier List existing masih minimal dan hanya bernilai directory
- Procurement Invoice List / Create / Detail sudah hidup
- Reporting hutang supplier existing masih invoice-grain
- Inventory repo existing masih saldo tunggal + ledger movement
- User secara eksplisit meminta halaman berikutnya fokus ke eksekusi, bukan diskusi panjang

---

## Ringkasan singkat siap tempel

### Ringkasan
- Procurement invoice policy sudah final:
  - pre-effect boleh edit/void
  - post-effect hanya correction/reversal
  - hard delete tidak boleh
- Payment supplier tenant ringan sudah final:
  - explicit action
  - proof opsional
  - payment row lahir saat aksi payment, bukan create invoice
- Supplier page sudah final:
  - jadi **Supplier Summary List V1**
  - field:
    - `invoice_count`
    - `outstanding_rupiah`
    - `invoice_unpaid_count`
    - `last_shipment_date`
- Inventory cukup berhenti di baseline:
  - `sellable`
  - `in_use`
  - `damaged`
  - jangan lanjut detail di halaman berikutnya

### Instruksi untuk halaman berikutnya
- fokus = **eksekusi kode Supplier Summary List V1**
- jangan buka diskusi panjang lagi
- diskusi hanya jika ada blocker kritikal yang nyata

---

## Progress final
- Procurement invoice policy: 100%
- Payment policy supplier tenant ringan: 100%
- Supplier Summary List V1 policy: 100%
- Inventory semantics baseline: cukup / stop here
- Readiness untuk halaman eksekusi berikutnya: 100%