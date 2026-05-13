# Handoff — Step 12 Reporting Read Models (Execution Closed)

## Metadata
- Tanggal: 2026-03-17
- Nama slice / topik: Step 12 — Reporting read models
- Workflow step: Step 12
- Status: CLOSED
- Progres:
  - Step 12: 100%
  - Workflow induk: 92.3% (12/13 step)

---

## Target halaman kerja
Menutup Step 12 sampai reporting backend v1 benar-benar hidup di repo, bukan hanya mapping atau keputusan desain.

Target yang berhasil dicapai di halaman ini:
- mengeksekusi reporting module backend-first sesuai boundary ADR reporting
- menghidupkan report transaksi v1
- menghidupkan report biaya operasional v1
- menghidupkan report hutang karyawan v1
- menghidupkan report hutang supplier v1
- menghidupkan report stok v1
- menghidupkan report laba operasional v1
- menjaga exactness lewat feature test + reconciliation
- menjaga reporting tetap read model, bukan source of truth
- menutup Step 12 dengan bundle test reporting hijau

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
- `docs/handoff/handoff_step_12_reporting_read_models_mapping_closure.md`  (handoff mapping penutup fase diskusi Step 12)

### Snapshot repo / output command yang dipakai
- contract/boundary:
  - `php artisan test tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`
- bundle reporting akhir:
  - `php artisan test tests/Feature/Reporting`
- syntax check file reporting:
  - `find app/Application/Reporting app/Ports/Out/Reporting app/Adapters/Out/Reporting tests/Feature/Reporting -type f -name '*.php' | sort | xargs -n1 php -l`
- syntax provider:
  - `php -l app/Providers/HexagonalServiceProvider.php`
- test per report:
  - `php artisan test tests/Feature/Reporting/GetTransactionSummaryPerNoteFeatureTest.php`
  - `php artisan test tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php`
  - `php artisan test tests/Feature/Reporting/GetOperationalExpenseSummaryFeatureTest.php`
  - `php artisan test tests/Feature/Reporting/GetEmployeeDebtSummaryFeatureTest.php`
  - `php artisan test tests/Feature/Reporting/GetSupplierPayableSummaryFeatureTest.php`
  - `php artisan test tests/Feature/Reporting/GetInventoryMovementSummaryFeatureTest.php`
  - `php artisan test tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`

---

## Fakta terkunci `[FACT]`

### Boundary reporting
- Reporting tetap diposisikan sebagai read model atas data domain final.
- Reporting bukan source of truth domain.
- Reporting boleh melakukan agregasi, filtering, grouping, summarization, dan projection.
- Reporting tidak boleh menciptakan business rules inti baru di luar core.
- Selisih 1 rupiah antara read model dan source of truth diperlakukan sebagai defect kritikal.
- Step 12 dieksekusi backend-first, bukan dimulai dari route/controller/UI.
- UI reporting belum dikerjakan di halaman ini.

### Status implementasi Step 12
- Rumah implementasi reporting sudah ada di repo.
- Provider binding reporting source reader sudah ditambahkan.
- DTO, builder, reconciliation service, use case handler, reader port, dan database adapter untuk report v1 sudah hidup.
- Bundle reporting final di akhir chat ini hijau.

### Report yang hidup
#### 1) Transaction summary per note
- `transaction_summary_per_note` hidup.
- Grain:
  - 1 baris = 1 note
- Filter:
  - `notes.transaction_date`
- Angka utama:
  - `gross_transaction_rupiah`
  - `allocated_payment_rupiah`
  - `refunded_rupiah`
  - `net_cash_collected_rupiah`
  - `outstanding_rupiah`

#### 2) Transaction cash ledger per note
- `transaction_cash_ledger_per_note` hidup.
- Grain:
  - 1 baris = 1 event kas untuk 1 note
- Event:
  - `payment_allocation`
  - `refund`
- Filter:
  - payment memakai `customer_payments.paid_at`
  - refund memakai `customer_refunds.refunded_at`

#### 3) Operational expense summary
- `operational_expense_summary` hidup.
- Filter:
  - `operational_expenses.expense_date`
- Inclusion v1:
  - hanya `status = posted`

#### 4) Employee debt summary
- `employee_debt_summary` hidup.
- Filter:
  - `employee_debts.created_at`
- Inclusion v1:
  - semua debt dalam scope tanggal masuk, termasuk yang status `paid`
- Payment total diambil dari `employee_debt_payments`
- Report ini tidak mencampur `payroll_disbursements`

#### 5) Supplier payable summary
- `supplier_payable_summary` hidup.
- Grain:
  - 1 invoice = 1 row
- Filter:
  - `supplier_invoices.tanggal_pengiriman`
- Outstanding:
  - `grand_total_rupiah - total_paid_rupiah`
- Inclusion payment:
  - semua `supplier_payments` milik invoice
  - tidak difilter `proof_status`
- Data receipt dipakai sebagai informasi operasional:
  - `receipt_count`
  - `total_received_qty`

#### 6) Inventory movement summary
- `inventory_movement_summary` hidup.
- Grain:
  - 1 product = 1 row jika punya movement dalam scope
- Filter:
  - `inventory_movements.tanggal_mutasi`
- Histori stok dan cost periodik dibaca dari `inventory_movements`
- `product_inventory` dan `product_inventory_costing` dipakai sebagai current snapshot, bukan histori periodik

#### 7) Operational profit summary
- `operational_profit_summary` hidup.
- View v1 menggunakan:
  - `gross_revenue_rupiah` dari `notes.total_rupiah`
  - `refunded_rupiah` dari `customer_refunds`
  - `external_purchase_cost_rupiah` dari `work_item_external_purchase_lines`
  - `store_stock_cogs_rupiah` dari `inventory_movements` `stock_out`
  - `operational_expense_rupiah` dari `operational_expenses` `posted`
  - `payroll_disbursement_rupiah` dari `payroll_disbursements`
- Reporting tidak menghitung ulang policy costing baru.
- Cost store stock dibaca dari hasil domain/inventory movement yang sudah resmi, bukan membuat policy FIFO/average baru di layer reporting.

### Exactness / verification
- Semua report v1 punya feature test masing-masing.
- Contract test reporting tetap hijau setelah implementasi.
- Bundle test reporting final hijau:
  - 10 tests passed
  - 46 assertions

---

## Scope yang dipakai

### `[SCOPE-IN]`
- implementasi module reporting backend-first
- transaction summary per note
- transaction cash ledger per note
- operational expense summary
- employee debt summary
- supplier payable summary
- inventory movement summary
- operational profit summary
- provider binding reporting reader
- DTO / builder / reconciliation / handler / reader adapter
- feature test reporting
- bundle verification reporting

### `[SCOPE-OUT]`
- route/controller reporting
- JSON endpoint final untuk UI
- `resources/views` reporting
- full native JS reporting UI
- chart / visual widget
- export PDF
- Telegram/WA adapter implementation
- materialized projection / async rebuild khusus reporting
- redesign costing policy domain
- multi-view laba lanjutan di luar kontrak v1

---

## Keputusan yang dikunci `[DECISION]`

### Keputusan arsitektur
- Step 12 dieksekusi backend-first.
- Reporting tetap read model, bukan source of truth.
- Perhitungan angka tidak dipindahkan ke JS.
- UI akan menjadi consumer dari backend reporting, bukan tempat kalkulasi.

### Keputusan transaksi
- Paket B dikunci sebagai bentuk report transaksi v1:
  - `transaction_summary_per_note`
  - `transaction_cash_ledger_per_note`
- Filter summary dan ledger dipisah:
  - summary by `notes.transaction_date`
  - ledger by event date kas
- Inclusion rule v1 transaksi sudah dikunci dan dieksekusi.

### Keputusan expense
- Report biaya operasional v1 hanya memasukkan `operational_expenses.status = posted`.

### Keputusan employee debt
- Report hutang karyawan v1 memakai `employee_debts.created_at` sebagai basis periode.
- Histori hutang karyawan tidak mencampur payroll.
- Payment debt dijumlahkan dari seluruh `employee_debt_payments` milik debt yang masuk scope.

### Keputusan supplier payable
- Hutang supplier v1 dibaca di level invoice.
- Outstanding dihitung sebagai `invoice total - total pembayaran`.
- Payment tidak difilter oleh `proof_status`, karena `proof_status` dipisah dari status finansial.

### Keputusan inventory
- Historical stock/cost report bertumpu pada `inventory_movements`.
- `product_inventory` dan `product_inventory_costing` hanya dipakai sebagai snapshot saat ini.

### Keputusan operational profit
- Profit v1 memakai direct cost yang terbukti dari domain final:
  - external purchase cost
  - store stock COGS dari inventory movement `stock_out`
- Reporting tidak membuat ulang strategi costing.
- Policy costing domain tetap tunduk pada ADR/domain resmi, bukan diubah oleh Step 12.

---

## File yang dibuat/diubah `[FILES]`

### File baru
#### DTO
- `app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php`
- `app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php`
- `app/Application/Reporting/DTO/OperationalExpenseSummaryRow.php`
- `app/Application/Reporting/DTO/EmployeeDebtSummaryRow.php`
- `app/Application/Reporting/DTO/SupplierPayableSummaryRow.php`
- `app/Application/Reporting/DTO/InventoryMovementSummaryRow.php`
- `app/Application/Reporting/DTO/OperationalProfitSummaryRow.php`

#### Services
- `app/Application/Reporting/Services/TransactionSummaryPerNoteBuilder.php`
- `app/Application/Reporting/Services/TransactionCashLedgerPerNoteBuilder.php`
- `app/Application/Reporting/Services/TransactionReportingReconciliationService.php`
- `app/Application/Reporting/Services/OperationalExpenseSummaryBuilder.php`
- `app/Application/Reporting/Services/OperationalExpenseReportingReconciliationService.php`
- `app/Application/Reporting/Services/EmployeeDebtSummaryBuilder.php`
- `app/Application/Reporting/Services/EmployeeDebtReportingReconciliationService.php`
- `app/Application/Reporting/Services/SupplierPayableSummaryBuilder.php`
- `app/Application/Reporting/Services/SupplierPayableReportingReconciliationService.php`
- `app/Application/Reporting/Services/InventoryMovementSummaryBuilder.php`
- `app/Application/Reporting/Services/InventoryMovementReportingReconciliationService.php`
- `app/Application/Reporting/Services/OperationalProfitSummaryBuilder.php`
- `app/Application/Reporting/Services/OperationalProfitReportingReconciliationService.php`

#### UseCases
- `app/Application/Reporting/UseCases/GetTransactionSummaryPerNoteHandler.php`
- `app/Application/Reporting/UseCases/GetTransactionCashLedgerPerNoteHandler.php`
- `app/Application/Reporting/UseCases/GetOperationalExpenseSummaryHandler.php`
- `app/Application/Reporting/UseCases/GetEmployeeDebtSummaryHandler.php`
- `app/Application/Reporting/UseCases/GetSupplierPayableSummaryHandler.php`
- `app/Application/Reporting/UseCases/GetInventoryMovementSummaryHandler.php`
- `app/Application/Reporting/UseCases/GetOperationalProfitSummaryHandler.php`

#### Ports
- `app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php`
- `app/Ports/Out/Reporting/OperationalExpenseReportingSourceReaderPort.php`
- `app/Ports/Out/Reporting/EmployeeDebtReportingSourceReaderPort.php`
- `app/Ports/Out/Reporting/SupplierPayableReportingSourceReaderPort.php`
- `app/Ports/Out/Reporting/InventoryMovementReportingSourceReaderPort.php`
- `app/Ports/Out/Reporting/OperationalProfitReportingSourceReaderPort.php`

#### Adapters
- `app/Adapters/Out/Reporting/DatabaseTransactionReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/DatabaseOperationalExpenseReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/DatabaseEmployeeDebtReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/DatabaseSupplierPayableReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/DatabaseInventoryMovementReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/DatabaseOperationalProfitReportingSourceReaderAdapter.php`

#### Tests
- `tests/Feature/Reporting/GetTransactionSummaryPerNoteFeatureTest.php`
- `tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php`
- `tests/Feature/Reporting/GetOperationalExpenseSummaryFeatureTest.php`
- `tests/Feature/Reporting/GetEmployeeDebtSummaryFeatureTest.php`
- `tests/Feature/Reporting/GetSupplierPayableSummaryFeatureTest.php`
- `tests/Feature/Reporting/GetInventoryMovementSummaryFeatureTest.php`
- `tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`

### File diubah
- `app/Providers/HexagonalServiceProvider.php`

### File yang sudah ada dan dipertahankan sebagai guard
- `tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`

---

## Bukti verifikasi `[PROOF]`

- command:
  - `php -l app/Providers/HexagonalServiceProvider.php`
  - hasil:
    - syntax provider tetap valid setelah semua binding reporting ditambahkan

- command:
  - `find app/Application/Reporting app/Ports/Out/Reporting app/Adapters/Out/Reporting tests/Feature/Reporting -type f -name '*.php' | sort | xargs -n1 php -l`
  - hasil:
    - seluruh file reporting baru lolos syntax check

- command:
  - `php artisan test tests/Feature/Reporting/GetTransactionSummaryPerNoteFeatureTest.php`
  - hasil:
    - PASS

- command:
  - `php artisan test tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php`
  - hasil:
    - PASS

- command:
  - `php artisan test tests/Feature/Reporting/GetOperationalExpenseSummaryFeatureTest.php`
  - hasil:
    - PASS

- command:
  - `php artisan test tests/Feature/Reporting/GetEmployeeDebtSummaryFeatureTest.php`
  - hasil:
    - PASS
    - sempat gagal di awal karena fixture belum seed `employees`
    - perbaikan dilakukan di fixture test, bukan di logic report

- command:
  - `php artisan test tests/Feature/Reporting/GetSupplierPayableSummaryFeatureTest.php`
  - hasil:
    - PASS

- command:
  - `php artisan test tests/Feature/Reporting/GetInventoryMovementSummaryFeatureTest.php`
  - hasil:
    - PASS

- command:
  - `php artisan test tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`
  - hasil:
    - PASS

- command:
  - `php artisan test tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`
  - hasil:
    - PASS
    - boundary reporting sebagai read model tetap terjaga

- command:
  - `php artisan test tests/Feature/Reporting`
  - hasil:
    - PASS
    - 10 tests passed
    - 46 assertions passed

---

## Blocker aktif
- tidak ada blocker teknis aktif untuk Step 12 v1
- blocker yang tersisa sudah berada di luar scope Step 12 v1:
  - UI reporting belum dibangun
  - endpoint/report controller belum dibangun
  - integrasi Telegram/WA belum disentuh
  - export/report delivery channel belum dibangun
  - materialized/async reporting projection belum dibangun

---

## Catatan transisi ke halaman berikutnya `[NOTE]`
- Berdasarkan arahan user di akhir chat ini, **besar kemungkinan Step 13 Telegram tidak dilanjutkan sekarang**.
- Arah kerja yang paling mungkin setelah Step 12 adalah:
  - fokus ke UI lebih dulu
  - integrasi Telegram diletakkan belakangan
- Catatan ini adalah **arah kerja user saat penutupan chat**, bukan keputusan arsitektur repo yang sudah dikunci oleh ADR baru.
- Jadi pada chat berikutnya:
  - jangan menganggap Step 13 Telegram dibatalkan permanen
  - tetapi aman menganggap prioritas berikutnya kemungkinan besar adalah UI reporting / UI operasional

---

## Rekomendasi pembuka chat berikutnya
Gunakan handoff ini sebagai konteks, lalu mulai dari tujuan berikut:

### Opsi paling konsisten dengan arahan user sekarang
- tidak melanjutkan Telegram dulu
- fokus ke UI
- gunakan reporting backend yang sudah hidup sebagai source data UI

### Target chat berikutnya yang paling aman
- lock scope UI yang benar-benar mau dibuka dulu
- tentukan halaman mana yang jadi consumer pertama reporting
- bentuk route/controller/read endpoint untuk report yang sudah hidup
- baru lanjut ke `resources/views` + native JS consumer

### Urutan yang disarankan
1. pilih report UI pertama
2. kunci kontrak endpoint baca
3. bangun controller/read endpoint
4. bangun view/UI native JS
5. lakukan verifikasi UI terhadap backend reporting yang sudah hijau

---
