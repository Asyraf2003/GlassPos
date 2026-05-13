# Handoff — Penutupan Step 5 Supplier + Inventory Receiving

## Metadata
- Tanggal: 2026-03-12
- Nama slice / topik: Step 5 — Supplier + Inventory Receiving
- Workflow step: Step 5
- Status: SELESAI untuk scope Step 5 yang dibangun pada repo saat ini
- Progres:
  - Step 5.1 Procurement minimal: 100%
  - Step 5.3 Manual receiving minimal: 100%
  - Step 5.4 Auto full receive default: 100%
  - Step 5.5 Payable baseline: 100%
  - Step 5 induk: SELESAI
  - Next workflow target: masuk ke Step 6 — Inventory engine

## Target Halaman Kerja
Menutup Step 5 Supplier + Inventory Receiving dengan hasil sah berikut:

1. procurement minimal hidup end-to-end untuk supplier, supplier invoice, dan supplier invoice line
2. receiving manual hidup end-to-end dengan source of truth stock-in melalui supplier receipt -> inventory movement -> product inventory
3. default flow client hidup tanpa merusak base enterprise:
   - create invoice default = auto full receive
   - create invoice default = auto full settle
4. payable baseline hidup dengan payment ledger eksplisit, proof status terpisah dari status finansial, dan proof attachment level di payment

## Referensi yang Dipakai [REF]
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
    - bounded context `Procurement / Supplier`
    - bounded context `Inventory`
    - aturan inti inventory: stok negatif default dilarang, stok bertambah bebas lewat edit manual tidak boleh
- Workflow:
  - `docs/workflow/workflow_v1.md`
    - `Step 5 — Supplier + Inventory Receiving`
    - `Step 6 — Inventory engine`
- DoD:
  - tidak dibawa / tidak dipakai langsung pada halaman ini
- ADR:
  - `docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
  - `docs/adr/0002-negative-stock-policy-default-off.md`
  - `docs/adr/0006-costing-strategy-default-average-fifo-ready.md`
- Handoff basis:
  - handoff penutupan Step 4 Product Catalog
- Snapshot repo / output command yang dipakai:
  - `sed -n '/Procurement/,/Inventory/p' docs/blueprint/blueprint_v1.md`
  - `cat docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
  - `cat docs/adr/0002-negative-stock-policy-default-off.md`
  - `cat docs/adr/0006-costing-strategy-default-average-fifo-ready.md`
  - `sed -n '/Step 5/,/Step 7/p' docs/workflow/workflow_v1.md`
  - `tree -L4 app routes database/migrations tests docs`
  - `cat app/Providers/HexagonalServiceProvider.php`
  - `cat routes/web.php`
  - seluruh syntax check dan test outputs yang tercantum pada bagian `[PROOF]`

## Fakta Terkunci [FACT]

### A. Fakta procurement minimal yang sudah terbukti
- Repo awal Step 5 belum memiliki module, migration, route, dan test untuk procurement/supplier/inventory.
- Setelah Step 5.1 selesai, repo sudah memiliki procurement minimal end-to-end untuk:
  - `supplier`
  - `supplier invoice`
  - `supplier invoice line`
- Kontrak bisnis supplier invoice line yang dikunci dan sudah dibuktikan:
  - line wajib merefer ke `product` existing
  - `qty_pcs` wajib `> 0`
  - `line_total_rupiah` wajib `> 0`
  - `line_total_rupiah` harus habis dibagi `qty_pcs`
  - `unit_cost_rupiah` adalah turunan dari `line_total_rupiah / qty_pcs`
- Kontrak due date yang dikunci dan sudah dibuktikan:
  - `due_date = shipment_date + 1 bulan kalender`
  - bila tanggal target tidak tersedia, dipakai hari terakhir bulan target
- Supplier resolution yang dikunci dan sudah dibuktikan:
  - request create invoice membawa `nama_pt_pengirim`
  - handler mencari supplier existing berdasarkan normalized name
  - bila belum ada, sistem membuat supplier minimal baru
  - bila nama normalized sama, supplier existing dipakai ulang

### B. Fakta receiving minimal yang sudah terbukti
- `invoice != receiving` tetap dipertahankan sebagai base domain.
- Stok resmi bertambah hanya melalui receiving yang sah.
- `received_qty` source of truth tidak disimpan di `supplier_invoice_line`; source of truth ada di receipt / movement.
- Receiving parsial didukung pada base domain.
- Receiving line di-anchor ke `supplier_invoice_line_id`, bukan hanya `product_id`.
- Jalur stock-in resmi yang sudah dibuktikan adalah:
  - `supplier_receipts`
  - `supplier_receipt_lines`
  - `inventory_movements`
  - `product_inventory`
- Receipt manual hidup end-to-end dan sudah dibuktikan:
  - unknown invoice ditolak
  - line yang bukan milik invoice target ditolak
  - over-receive ditolak
  - movement stock-in dibuat
  - `product_inventory.qty_on_hand` diperbarui

### C. Fakta default flow client yang sudah terbukti
- Base enterprise tetap dipertahankan, tetapi default workflow client dibuat ringan.
- Create invoice default sekarang otomatis:
  - full receive
  - full settle payment
- Auto receive dapat dimatikan dengan `auto_receive = false`.
- Auto settle default untuk tenant/client ini tidak bergantung pada `auto_receive`.
- Bila `tanggal_terima` tidak dikirim pada auto receive, sistem memakai `tanggal_pengiriman`.

### D. Fakta payable baseline yang sudah terbukti
- Hutang supplier tetap lahir dari invoice sebagai base enterprise.
- Source of truth outstanding yang dikunci:
  - `invoice total - total pembayaran`
- Payment supplier boleh parsial pada base domain.
- Bukti bayar melekat ke payment, bukan ke invoice.
- `proof_status` dipisah dari status finansial.
- Untuk tenant/client ini, default create invoice otomatis membuat payment penuh dengan:
  - `amount_rupiah = grand_total_rupiah`
  - `paid_at = tanggal_pengiriman`
  - `proof_status = pending`
  - `proof_storage_path = null`
- Reminder yang tersisa pada baseline ini adalah reminder bukti bayar, bukan reminder outstanding hutang.

## Output Wajib Step 5 yang Sudah Terbukti
- supplier invoice line ke product master valid
- supplier invoice minimal hidup end-to-end
- receive inventory minimal hidup end-to-end
- stok masuk resmi hanya melalui supplier receipt -> inventory movement -> product inventory
- default create invoice dapat auto full receive
- payable baseline hidup dengan payment ledger eksplisit dan proof status terpisah dari status finansial

## Scope yang Dipakai [SCOPE-IN]
- supplier master minimal
- supplier invoice minimal
- supplier invoice line minimal
- validasi line ke product existing
- due date otomatis
- manual receiving minimal
- supplier receipt
- supplier receipt line
- inventory movement untuk stock-in supplier receipt
- product inventory projection untuk stock-in
- auto full receive default
- payable baseline
- auto full settle default untuk tenant/client ini
- proof status baseline pada payment

## Scope yang Tidak Dipakai [SCOPE-OUT]
- upload bukti bayar ke storage/private
- attach proof mutation flow
- read model reminder bukti bayar
- outstanding/payable dashboard khusus
- payment verification workflow
- multi-proof attachment per payment
- stock-out umum
- stock adjustment
- negative stock runtime penuh
- costing average runtime penuh
- inventory engine Step 6 penuh
- auto settle flag sebagai field request publik terpisah

## Keputusan yang Dikunci [DECISION]

### A. Procurement / supplier
- Root context Step 5 memakai `Procurement`.
- Slice pertama Step 5 adalah `Create Supplier Invoice` tanpa menerima stock-in sebagai domain default yang menyatu.
- `supplier invoice` dan `receiving` tetap dua hal domain berbeda.
- `supplier` tetap ada sebagai master, tetapi create invoice boleh melakukan supplier resolution otomatis dari `nama_pt_pengirim`.

### B. Receiving / inventory stock-in
- Manual receiving dibangun lebih dulu sebelum auto receive default.
- `received_qty` source of truth tidak dipindah ke `supplier_invoice_line`.
- Movement adalah source of truth mutasi stok.
- `product_inventory` dipakai sebagai projection / saldo berjalan yang diperbarui setelah movement ditulis.
- Receipt line harus mengacu ke `supplier_invoice_line_id`.

### C. Auto full receive default
- Endpoint create invoice tetap satu untuk UX.
- Domain create invoice dan receiving tetap dipisah.
- Auto full receive dipasang sebagai orchestration convenience, bukan penyatuan domain.
- Bila `auto_receive = false`, create invoice tetap sukses dan stok tidak bertambah.

### D. Payable baseline
- Base enterprise tetap dijaga dengan payment ledger eksplisit.
- Tenant/client ini memakai default auto full settle.
- Auto settle default tidak bergantung pada `auto_receive`.
- `proof_status` dipisah dari status finansial.
- Bukti bayar melekat ke payment.
- Untuk baseline Step 5.5, model proof hanya memakai dua status:
  - `pending`
  - `uploaded`

## File yang Dibuat / Diubah [FILES]

### File baru
- `database/migrations/2026_03_12_000100_create_suppliers_table.php`
- `database/migrations/2026_03_12_000200_create_supplier_invoices_table.php`
- `database/migrations/2026_03_12_000300_create_supplier_invoice_lines_table.php`
- `database/migrations/2026_03_12_000400_create_supplier_receipts_table.php`
- `database/migrations/2026_03_12_000500_create_supplier_receipt_lines_table.php`
- `database/migrations/2026_03_12_000600_create_inventory_movements_table.php`
- `database/migrations/2026_03_12_000700_create_product_inventory_table.php`
- `database/migrations/2026_03_12_000800_create_supplier_payments_table.php`
- `app/Core/Procurement/Supplier/Supplier.php`
- `app/Core/Procurement/SupplierInvoice/SupplierInvoice.php`
- `app/Core/Procurement/SupplierInvoice/SupplierInvoiceLine.php`
- `app/Core/Procurement/SupplierReceipt/SupplierReceipt.php`
- `app/Core/Procurement/SupplierReceipt/SupplierReceiptLine.php`
- `app/Core/Procurement/SupplierPayment/SupplierPayment.php`
- `app/Core/Inventory/Movement/InventoryMovement.php`
- `app/Core/Inventory/ProductInventory/ProductInventory.php`
- `app/Ports/Out/Procurement/SupplierReaderPort.php`
- `app/Ports/Out/Procurement/SupplierWriterPort.php`
- `app/Ports/Out/Procurement/SupplierInvoiceWriterPort.php`
- `app/Ports/Out/Procurement/SupplierInvoiceReaderPort.php`
- `app/Ports/Out/Procurement/SupplierInvoiceLineReaderPort.php`
- `app/Ports/Out/Procurement/SupplierReceiptWriterPort.php`
- `app/Ports/Out/Procurement/SupplierReceiptLineReaderPort.php`
- `app/Ports/Out/Procurement/SupplierPaymentWriterPort.php`
- `app/Ports/Out/Procurement/SupplierPaymentReaderPort.php`
- `app/Ports/Out/Inventory/InventoryMovementWriterPort.php`
- `app/Ports/Out/Inventory/ProductInventoryReaderPort.php`
- `app/Ports/Out/Inventory/ProductInventoryWriterPort.php`
- `app/Application/Procurement/UseCases/CreateSupplierInvoiceHandler.php`
- `app/Application/Procurement/UseCases/ReceiveSupplierInvoiceHandler.php`
- `app/Application/Procurement/UseCases/CreateSupplierInvoiceFlowHandler.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierWriterAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceWriterAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceLineReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierReceiptLineReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierReceiptWriterAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierPaymentWriterAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierPaymentReaderAdapter.php`
- `app/Adapters/Out/Inventory/DatabaseInventoryMovementWriterAdapter.php`
- `app/Adapters/Out/Inventory/DatabaseProductInventoryReaderAdapter.php`
- `app/Adapters/Out/Inventory/DatabaseProductInventoryWriterAdapter.php`
- `app/Adapters/In/Http/Requests/Procurement/CreateSupplierInvoiceRequest.php`
- `app/Adapters/In/Http/Requests/Procurement/ReceiveSupplierInvoiceRequest.php`
- `app/Adapters/In/Http/Controllers/Procurement/CreateSupplierInvoiceController.php`
- `app/Adapters/In/Http/Controllers/Procurement/ReceiveSupplierInvoiceController.php`
- `tests/Feature/Procurement/CreateSupplierInvoiceFeatureTest.php`
- `tests/Feature/Procurement/ReceiveSupplierInvoiceFeatureTest.php`

### File diubah
- `app/Providers/HexagonalServiceProvider.php`
- `routes/web.php`
- `docs/workflow/workflow_v1.md`

## Bukti Verifikasi [PROOF]

### 1) Migration Step 5 procurement
- command:
  - `php -l database/migrations/2026_03_12_000100_create_suppliers_table.php`
  - `php -l database/migrations/2026_03_12_000200_create_supplier_invoices_table.php`
  - `php -l database/migrations/2026_03_12_000300_create_supplier_invoice_lines_table.php`
  - `php artisan migrate`
- hasil:
  - semua syntax PASS
  - migration `suppliers`, `supplier_invoices`, `supplier_invoice_lines` DONE

### 2) Migration receiving + inventory stock-in
- command:
  - `php -l database/migrations/2026_03_12_000400_create_supplier_receipts_table.php`
  - `php -l database/migrations/2026_03_12_000500_create_supplier_receipt_lines_table.php`
  - `php -l database/migrations/2026_03_12_000600_create_inventory_movements_table.php`
  - `php -l database/migrations/2026_03_12_000700_create_product_inventory_table.php`
  - `php artisan migrate`
- hasil:
  - semua syntax PASS
  - migration `supplier_receipts`, `supplier_receipt_lines`, `inventory_movements`, `product_inventory` DONE

### 3) Migration payable baseline
- command:
  - `php -l database/migrations/2026_03_12_000800_create_supplier_payments_table.php`
  - `php artisan migrate`
- hasil:
  - syntax PASS
  - migration `supplier_payments` DONE

### 4) Syntax check core procurement / inventory
- command:
  - `php -l app/Core/Procurement/Supplier/Supplier.php`
  - `php -l app/Core/Procurement/SupplierInvoice/SupplierInvoice.php`
  - `php -l app/Core/Procurement/SupplierInvoice/SupplierInvoiceLine.php`
  - `php -l app/Core/Procurement/SupplierReceipt/SupplierReceipt.php`
  - `php -l app/Core/Procurement/SupplierReceipt/SupplierReceiptLine.php`
  - `php -l app/Core/Procurement/SupplierPayment/SupplierPayment.php`
  - `php -l app/Core/Inventory/Movement/InventoryMovement.php`
  - `php -l app/Core/Inventory/ProductInventory/ProductInventory.php`
- hasil:
  - semua PASS / No syntax errors detected

### 5) Syntax check ports + adapters + handlers + HTTP wiring
- command:
  - seluruh syntax check file ports, adapters, handlers, request, controller, provider, dan routes yang dijalankan sepanjang Step 5
- hasil:
  - semua PASS / No syntax errors detected
  - route procurement terdaftar:
    - `POST procurement/supplier-invoices/create`
    - `POST procurement/supplier-invoices/{supplierInvoiceId}/receive`

### 6) Feature test — create supplier invoice
- command:
  - `php artisan test tests/Feature/Procurement/CreateSupplierInvoiceFeatureTest.php`
- hasil akhir:
  - PASS
  - `4 passed`
  - `45 assertions`
- proof yang tercakup:
  - auto receive by default
  - auto settle by default
  - reject unknown product
  - reject total tidak habis dibagi qty
  - `auto_receive = false` tetap create invoice + payment tanpa stock-in

### 7) Feature test — receive supplier invoice
- command:
  - `php artisan test tests/Feature/Procurement/ReceiveSupplierInvoiceFeatureTest.php`
- hasil:
  - PASS
  - `4 passed`
  - `21 assertions`
- proof yang tercakup:
  - stores receipt + movements + inventory projection
  - reject unknown invoice
  - reject line yang bukan milik invoice target
  - reject over receive

## Blocker Aktif [BLOCKER]

### Untuk penutupan Step 5
- tidak ada blocker aktif
- Step 5 sah ditutup untuk scope yang dibangun di repo saat ini

### Catatan boundary ke Step 6
- inventory engine penuh belum dibuka
- negative stock runtime penuh belum dibuka
- costing average runtime penuh belum dibuka
- stock-out umum dan adjustment belum dibuka

## State Repo yang Penting untuk Langkah Berikutnya
- create supplier invoice hidup end-to-end
- receive supplier invoice hidup end-to-end
- default create invoice dapat auto full receive
- default create invoice dapat auto full settle
- `inventory_movements` sudah menjadi source of truth stock-in resmi dari supplier receipt
- `product_inventory` sudah menjadi projection saldo stok berjalan untuk stock-in supplier receipt
- `supplier_payments` sudah hidup sebagai payment ledger baseline
- `proof_status` baseline sudah hidup pada payment dengan status minimum `pending` dan `uploaded`
- route procurement create dan receive sudah aktif
- Step 6 dapat dibuka di atas fondasi inventory movement + product inventory yang sudah hidup

## Next Step Paling Aman [NEXT]
Masuk ke `Step 6 — Inventory engine` dengan urutan aman berikut:

1. audit boundary Step 6 terhadap state repo aktual
2. kunci kontrak inventory engine:
   - stock balance
   - inventory movement umum
   - stock adjustment
   - negative stock policy
   - costing average strategy
3. tentukan mana yang sudah hidup dari Step 5 dan mana yang baru resmi dibuka pada Step 6
4. baru lanjut ke slice pertama Step 6

## Catatan Masuk Halaman Berikutnya
Saat membuka halaman kerja Step 6, bawa minimal:

- file handoff ini
- `docs/setting_control/first_in.md`
- `docs/setting_control/ai_contract.md`
- `docs/blueprint/blueprint_v1.md`
- `docs/workflow/workflow_v1.md`
- `docs/adr/0002-negative-stock-policy-default-off.md`
- `docs/adr/0006-costing-strategy-default-average-fifo-ready.md`
- `docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
- snapshot repo terbaru area inventory setelah penutupan Step 5

---

# Ringkasan Singkat Siap Tempel

## Ringkasan

### Target
menutup Step 5 Supplier + Inventory Receiving

### Status
selesai untuk Step 5

### Hasil utama
- supplier invoice minimal hidup end-to-end
- receiving minimal hidup end-to-end
- stock-in resmi lahir dari supplier receipt -> inventory movement -> product inventory
- create invoice default dapat auto full receive
- payable baseline hidup dengan supplier payment ledger
- create invoice default dapat auto full settle
- proof status baseline pada payment hidup dan terpisah dari status finansial

### Next step
- masuk ke Step 6 — Inventory engine
- audit boundary inventory movement / product inventory / negative stock / costing average

## Jangan Dibuka Ulang
- `invoice != receiving`
- movement adalah source of truth mutasi stok
- `received_qty` source of truth bukan di `supplier_invoice_line`
- auto receive default adalah orchestration convenience, bukan penyatuan domain
- source of truth outstanding = invoice total - total pembayaran
- bukti bayar melekat ke payment
- `proof_status` dipisah dari status finansial
- tenant/client ini default auto full settle

## Data Minimum Bila Ingin Lanjut ke Step 6
- handoff ini
- referensi Step 6 yang relevan
- snapshot repo terbaru area inventory
- output command / isi file inventory-related saat Step 6 mulai dibangun
