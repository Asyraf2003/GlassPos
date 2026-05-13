# Handoff — Step 12 Reporting Read Models (Mapping Closure)

## Metadata
- Tanggal: 2026-03-17
- Nama slice / topik: Step 12 — Reporting read models
- Workflow step: Step 12
- Status: MAPPING CLOSED, EXECUTION NOT STARTED
- Progres:
  - Workflow induk: 84.6% (11/13 step sudah punya handoff)
  - Step 12: 32%
  - Status Step 12 saat handoff ini dibuat: boundary terkunci, source mapping terkunci, schema risk mapping terkunci, kontrak angka implementasi belum dikunci

---

## Target halaman kerja
Menutup fase pemetaan Step 12 agar chat berikutnya bisa langsung masuk ke eksekusi backend reporting tanpa membuka ulang diskusi fondasi.

Target yang berhasil dicapai di halaman ini:
- memverifikasi boundary resmi reporting dari ADR dan workflow
- memetakan source data final yang akan menjadi bahan laporan
- memetakan batas schema yang memengaruhi exactness angka
- mengunci keputusan bahwa Step 12 tetap backend-first
- mengunci keputusan bahwa UI nanti full native JS, tetapi bukan fokus Step 12 saat ini
- menutup halaman ini sebagai handoff pemetaan, bukan halaman implementasi

---

## Referensi yang dipakai `[REF]`

### Dokumen
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- DoD:
  - `docs/dod/dod_v1.md`
- ADR:
  - `docs/adr/0003-external-spare-part-as-case-cost.md`
  - `docs/adr/0006-costing-strategy-default-average-fifo-ready.md`
  - `docs/adr/0009-reporting-as-read-model.md`
  - `docs/adr/0010-telegram-wa-integration-as-adapter.md`
  - `docs/adr/0011-money-stored-as-integer-rupiah.md`
  - `docs/adr/0013-employee-finance-foundation.md`

### Handoff sebelumnya
- `docs/handoff/handoff_step_08_payment_receivable_engine_transition_to_step_9.md`
- `docs/handoff/handoff_step_09_correction_refund_audit.md`
- `docs/handoff/handoff_step_10_employee_finance.md`
- `docs/handoff/handoff_step_11_operational_expense.md`

### Snapshot repo / output command yang dipakai
- tree repo:
  - `tree -L9 docs app resources database routes mk tests`
- test boundary reporting:
  - `cat tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`
- ADR + workflow:
  - `cat docs/adr/0009-reporting-as-read-model.md`
  - `sed -n '/## Step 12 — Reporting read models/,/^## Step 13/p' docs/workflow/workflow_v1.md`
- migration source mapping:
  - `database/migrations/2026_03_14_000100_create_notes_table.php`
  - `database/migrations/2026_03_14_000200_create_work_items_table.php`
  - `database/migrations/2026_03_14_000300_create_work_item_service_details_table.php`
  - `database/migrations/2026_03_14_000400_create_work_item_external_purchase_lines_table.php`
  - `database/migrations/2026_03_14_000500_create_work_item_store_stock_lines_table.php`
  - `database/migrations/2026_03_14_000600_create_customer_payments_table.php`
  - `database/migrations/2026_03_14_000700_create_payment_allocations_table.php`
  - `database/migrations/2026_03_16_000100_create_customer_refunds_table.php`
  - `database/migrations/2026_03_12_000200_create_supplier_invoices_table.php`
  - `database/migrations/2026_03_12_000400_create_supplier_receipts_table.php`
  - `database/migrations/2026_03_12_000500_create_supplier_receipt_lines_table.php`
  - `database/migrations/2026_03_12_000600_create_inventory_movements_table.php`
  - `database/migrations/2026_03_12_000700_create_product_inventory_table.php`
  - `database/migrations/2026_03_12_000800_create_supplier_payments_table.php`
  - `database/migrations/2026_03_13_000100_create_product_inventory_costing_table.php`
  - `database/migrations/2026_03_16_000300_create_employee_debts_table.php`
  - `database/migrations/2026_03_16_000400_create_employee_debt_payments_table.php`
  - `database/migrations/2026_03_16_000500_create_payroll_disbursements_table.php`
  - `database/migrations/2026_03_17_000200_create_operational_expenses_table.php`

---

## Fakta terkunci `[FACT]`

### Boundary reporting
- ADR-0009 menyatakan reporting diposisikan sebagai read model atas data domain final.
- Reporting bukan source of truth utama domain.
- Reporting boleh melakukan agregasi, filtering, grouping, summarization, projection.
- Reporting tidak boleh memperkenalkan business rules inti baru yang tidak ada di core.
- Selisih 1 rupiah antara report dan source of truth adalah defect kritikal.
- Step 12 di workflow memang resmi berjudul `Reporting read models`.

### Keputusan arsitektur yang sudah dikunci di percakapan ini
- Step 12 dikerjakan backend-first.
- UI tidak dibangun dulu.
- UI nanti full native JS + JSON endpoints.
- Full native JS berlaku untuk adapter UI, bukan untuk tempat perhitungan angka.
- Routes reporting nanti boleh ada, tetapi hanya sebagai adapter baca setelah kontrak backend stabil.
- Halaman ini ditutup sebagai fase pemetaan, bukan fase implementasi.

### Source data final yang sudah terbukti dari migration
#### Riwayat transaksi / report transaksi dasar
- `notes`
  - `id`
  - `customer_name`
  - `transaction_date`
  - `total_rupiah`
- `work_items`
  - `id`
  - `note_id`
  - `line_no`
  - `transaction_type`
  - `status`
  - `subtotal_rupiah`
- `work_item_service_details`
  - `work_item_id`
  - `service_name`
  - `service_price_rupiah`
  - `part_source`
- `work_item_external_purchase_lines`
  - `work_item_id`
  - `cost_description`
  - `unit_cost_rupiah`
  - `qty`
  - `line_total_rupiah`
- `work_item_store_stock_lines`
  - `work_item_id`
  - `product_id`
  - `qty`
  - `line_total_rupiah`

#### Payment / refund
- `customer_payments`
  - `id`
  - `amount_rupiah`
  - `paid_at`
- `payment_allocations`
  - `id`
  - `customer_payment_id`
  - `note_id`
  - `amount_rupiah`
- `customer_refunds`
  - `id`
  - `customer_payment_id`
  - `note_id`
  - `amount_rupiah`
  - `refunded_at`
  - `reason`

#### Supplier / hutang supplier
- `supplier_invoices`
  - `id`
  - `supplier_id`
  - `tanggal_pengiriman`
  - `jatuh_tempo`
  - `grand_total_rupiah`
- `supplier_receipts`
  - `id`
  - `supplier_invoice_id`
  - `tanggal_terima`
- `supplier_receipt_lines`
  - `id`
  - `supplier_receipt_id`
  - `supplier_invoice_line_id`
  - `qty_diterima`
- `supplier_payments`
  - `id`
  - `supplier_invoice_id`
  - `amount_rupiah`
  - `paid_at`
  - `proof_status`
  - `proof_storage_path`

#### Inventory / stok / costing
- `inventory_movements`
  - `id`
  - `product_id`
  - `movement_type`
  - `source_type`
  - `source_id`
  - `tanggal_mutasi`
  - `qty_delta`
  - `unit_cost_rupiah`
  - `total_cost_rupiah`
- `product_inventory`
  - `product_id`
  - `qty_on_hand`
- `product_inventory_costing`
  - `product_id`
  - `avg_cost_rupiah`
  - `inventory_value_rupiah`

#### Employee finance
- `employee_debts`
  - `id`
  - `employee_id`
  - `total_debt`
  - `remaining_balance`
  - `status`
  - `notes`
  - timestamps
- `employee_debt_payments`
  - `id`
  - `employee_debt_id`
  - `amount`
  - `payment_date`
  - `notes`
  - timestamps
- `payroll_disbursements`
  - `id`
  - `employee_id`
  - `amount`
  - `disbursement_date`
  - `mode`
  - `notes`
  - timestamps

#### Operational expense
- `operational_expenses`
  - `id`
  - `category_id`
  - `amount_rupiah`
  - `expense_date`
  - `description`
  - `payment_method`
  - `reference_no`
  - `status`
  - timestamps

### Fakta schema yang penting untuk Step 12
- `payment_allocations` tidak memiliki kolom tanggal sendiri.
- `product_inventory` dan `product_inventory_costing` adalah state saat ini, bukan histori periodik.
- Histori stok/periode tidak bisa hanya dibaca dari current state table; harus bertumpu pada `inventory_movements` atau projection/report ledger yang rebuildable.
- `operational_expenses` memiliki kolom `status`, sehingga report biaya operasional wajib mengunci status mana yang dianggap sah.
- Tipe uang tidak sepenuhnya seragam secara schema:
  - banyak domain memakai `integer`
  - employee finance memakai `bigInteger`
- `work_item_external_purchase_lines` terpisah dari inventory movement, sehingga external purchase bukan otomatis inventory usage.
- `inventory_movements` sudah menyediakan `movement_type`, `source_type`, `source_id`, `tanggal_mutasi`, `qty_delta`, `unit_cost_rupiah`, `total_cost_rupiah`, sehingga paling layak menjadi basis historis stok dan komponen cost periodik.
- `supplier_payments` memiliki `proof_status`, tetapi belum diputuskan pada fase ini apakah status tersebut memengaruhi inclusion ke report.

---

## Scope yang dipakai

### `[SCOPE-IN]`
- memverifikasi boundary resmi reporting
- memetakan source data final Step 12
- memetakan constraint schema yang berpengaruh ke exactness 1 rupiah
- mengunci strategi kerja Step 12 sebagai backend-first
- mengunci strategi UI nanti sebagai full native JS consumer
- menentukan bahwa halaman ini ditutup sebagai fase pemetaan

### `[SCOPE-OUT]`
- implementasi code reporting
- nama file/folder final reporting module
- struktur JSON final
- route/controller final reporting
- kontrak angka final tiap report
- pilihan final cara pandang profit yang akan dieksekusi duluan
- chart/UI delivery
- export/telegram adapter implementation

---

## Keputusan yang dikunci `[DECISION]`

- Step 12 tidak dimulai dari UI.
- Step 12 tidak dimulai dari routes/controller.
- Step 12 dimulai dari penguncian kontrak backend/reporting.
- Reporting module harus menjaga boundary ADR-0009: read model, bukan source of truth.
- Perhitungan tidak boleh dipindahkan ke JS.
- Halaman ini ditutup setelah source mapping cukup terbukti.
- Chat berikutnya boleh langsung masuk ke eksekusi Step 12 tanpa membuka ulang diskusi pemetaan.

### Catatan klarifikasi istilah profit/costing dari user
- User mengklarifikasi maksud istilah sebelumnya adalah `COGS x FIFO`.
- Interpretasi aman untuk chat berikutnya:
  - user ingin sistem pelaporan/laba mampu menampung cara pandang COGS dan dukungan FIFO
  - tetapi keputusan resmi yang sudah terdokumentasi tetap harus dibaca dari ADR-0006
  - handoff ini tidak mengubah ADR
  - saat eksekusi Step 12, costing/profit view wajib direkonsiliasi dengan ADR-0006 dan boundary reporting, bukan diasumsikan berubah hanya dari percakapan ini

---

## Peta laporan Step 12 hasil pemetaan

### 1) Laporan transaksi / riwayat transaksi
Sumber baca:
- `notes`
- `work_items`
- `work_item_service_details`
- `work_item_external_purchase_lines`
- `work_item_store_stock_lines`
- `customer_payments`
- `payment_allocations`
- `customer_refunds`

Catatan:
- `transaction_date` tersedia di `notes`
- detail line tersedia cukup untuk memecah jasa, stok toko, dan external purchase
- report ini layak menjadi salah satu jalur verifikasi paling awal

### 2) Laporan supplier / hutang supplier
Sumber baca:
- `supplier_invoices`
- `supplier_receipts`
- `supplier_receipt_lines`
- `supplier_payments`

Catatan:
- schema sudah mendukung invoice date, due date, receipt date, dan payment date
- definisi payable period belum dikunci di halaman ini

### 3) Laporan hutang karyawan
Sumber baca:
- `employee_debts`
- `employee_debt_payments`

Catatan:
- ada `remaining_balance`, sehingga domain ini cukup kuat untuk report saldo hutang
- exact inclusion rule per periode belum dikunci di halaman ini

### 4) Laporan biaya operasional
Sumber baca:
- `operational_expenses`
- `expense_categories`

Catatan:
- `status` harus dikunci saat eksekusi
- `expense_date` sudah tersedia untuk periodisasi

### 5) Laporan stok
Sumber baca:
- histori / rebuild / reconcile:
  - `inventory_movements`
- current state:
  - `product_inventory`
  - `product_inventory_costing`

Catatan:
- historical stock tidak boleh hanya membaca `product_inventory`
- current stock snapshot boleh membaca `product_inventory` + `product_inventory_costing`

### 6) Laporan laba operasional
Calon sumber baca gabungan:
- revenue side:
  - `notes`
  - `work_items`
  - `payment_allocations`
  - `customer_refunds`
- cost side:
  - `inventory_movements`
  - `product_inventory_costing`
  - `operational_expenses`
  - `payroll_disbursements`
  - kemungkinan domain employee finance tertentu sesuai kontrak angka yang nanti dikunci

Catatan:
- rumus final belum dikunci di halaman ini
- multi-view profit tetap harus tunduk pada ADR-0009
- current costing table tidak cukup untuk historical profit tanpa dukungan ledger/projection yang rebuildable

---

## Risiko aktif / gap yang harus dibawa ke halaman eksekusi `[GAP]`

1. **Kontrak angka per report belum dikunci**
- basis tanggal
- event masuk
- event keluar
- pasangan rekonsiliasi
- inclusion status
- grouping per periode

2. **Pilihan profit view pertama belum dikunci**
- cash view
- operational profit view
- multi-view resmi dari awal

3. **Basis tanggal untuk setiap report belum dikunci**
- transaksi: kemungkinan `transaction_date`
- cash: kemungkinan `paid_at`, `refunded_at`, `payment_date`, `disbursement_date`, `expense_date`
- stok: `tanggal_mutasi`
- supplier: bisa menyentuh `tanggal_pengiriman`, `jatuh_tempo`, `tanggal_terima`, `paid_at`

4. **Status inclusion belum dikunci**
- `operational_expenses.status`
- kemungkinan `supplier_payments.proof_status`
- `work_items.status`

5. **Historical costing/profit belum punya design resmi**
- current state table tidak cukup
- perlu projection/read model/ledger yang rebuildable

6. **Reporting module belum ada rumah implementasi di app**
- belum ada `app/Application/Reporting`
- belum ada `app/Ports/Out/Reporting`
- belum ada `app/Adapters/Out/Reporting`
- belum ada controller reporting

---

## Rekomendasi untuk chat eksekusi berikutnya

### Fokus awal yang disarankan
Masuk ke eksekusi Step 12 dengan urutan:
1. lock kontrak angka/report
2. tentukan report mana yang dibangun dulu
3. baru bentuk module reporting + test + projection/reconciliation

### Urutan implementasi yang disarankan
- mulai dari report yang paling mudah direkonsiliasi dari schema:
  - laporan transaksi / riwayat transaksi
  - laporan biaya operasional
  - laporan hutang karyawan
  - laporan supplier
  - laporan stok
  - laba operasional terakhir setelah kontrak cost/profit jelas

### Guard yang wajib dijaga
- mismatch 1 rupiah = defect
- reporting tidak boleh menambah business rules inti baru
- external purchase tidak boleh diam-diam dianggap inventory usage
- historical stock/profit tidak boleh hanya baca current state table
- JS tidak boleh menghitung ulang angka backend

---

## Bukti verifikasi `[PROOF]`

- command:
  - `cat tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`
  - hasil:
    - test hanya mengunci boundary dokumen reporting, belum implementasi reporting
- command:
  - `cat docs/adr/0009-reporting-as-read-model.md`
  - hasil:
    - reporting diposisikan sebagai read model atas data domain final
    - mismatch 1 rupiah adalah defect kritikal
    - reporting tidak boleh membuat business rules inti baru
- command:
  - `sed -n '/## Step 12 — Reporting read models/,/^## Step 13/p' docs/workflow/workflow_v1.md`
  - hasil:
    - Step 12 resmi memuat 8 area laporan dan output exactness
- command:
  - `tree -L9 docs app resources database routes mk tests`
  - hasil:
    - source domains tersedia
    - module reporting implementasi belum terlihat
    - test boundary reporting sudah ada
- command:
  - pembacaan seluruh migration source mapping yang tercantum di bagian referensi
  - hasil:
    - source data final untuk report transaksi, payment/refund, supplier, inventory, employee finance, expense berhasil dipetakan

---

## File yang dibuat/diubah `[FILES]`

### File baru
- belum ada perubahan repo pada fase ini

### File diubah
- belum ada perubahan repo pada fase ini

---

## Blocker aktif
- tidak ada blocker teknis untuk memulai chat eksekusi
- blocker yang tersisa bersifat keputusan desain:
  - kontrak angka per report
  - basis tanggal per report
  - status inclusion
  - design historical costing/profit

---

## Pembuka yang disarankan untuk chat berikutnya
Gunakan handoff ini sebagai konteks, lalu mulai dengan tujuan berikut:

- eksekusi Step 12 dari hasil pemetaan
- backend-first
- reporting = read model
- mismatch 1 rupiah = defect
- UI nanti full native JS
- historical stock/profit tidak boleh hanya baca current state table
- lock kontrak angka dulu sebelum code

Target chat berikutnya:
- pilih report pertama yang dibangun
- kunci kontrak angka report tersebut
- tentukan rumah implementasi reporting module
- mulai test-first untuk read model / reconciliation

---
