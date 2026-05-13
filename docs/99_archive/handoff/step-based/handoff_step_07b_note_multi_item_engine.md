# Handoff — Step 7 Note Multi-Item Engine

## Metadata
- Tanggal: 2026-03-14
- Nama slice / topik: Step 7 — Note Multi-Item Engine
- Workflow step: Step 7
- Status: CLOSED
- Progres:
  - Step 7: 100%
  - Workflow induk: 50%

---

## Target halaman kerja
Menutup Step 7 agar engine Note Multi-Item hidup di level domain/application/persistence/test, sekaligus menyiapkan halaman berikutnya agar bisa langsung masuk ke Step 8 tanpa membuka ulang domain Step 7.

Target final Step 7 yang harus terbukti:
- create note hidup
- add work item hidup
- `service_only` hidup
- `service_with_external_purchase` hidup
- `store_stock_sale_only` hidup
- `service_with_store_stock_part` hidup
- total note calculation hidup
- external purchase tidak masuk inventory
- store stock potong stok lewat inventory engine resmi
- insufficient stock tertolak
- floor pricing store stock tertolak bila di bawah floor

Catatan penting:
- penutupan Step 7 ini adalah pada level engine/domain/application/persistence/test
- HTTP transport Step 7 belum dibangun
- Step 8 harus mulai dari kontrak internal payment/receivable, bukan dari ulang domain Step 7

---

## Referensi yang dipakai `[REF]`

### Dokumen utama
- AI Contract:
  - `docs/setting_control/ai_contract.md`
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- Handoff sebelumnya:
  - `docs/handoff/handoff_step_6_inventory_engine.md`
  - `docs/handoff/handoff_step_7_preparation_design_lock.md`

### ADR relevan
- `docs/adr/0001-one-note-multi-item.md`
- `docs/adr/0003-external-spare-part-as-case-cost.md`
- `docs/adr/0004-minimum-selling-price-guard.md`
- `docs/adr/0005-paid-note-correction-requires-audit.md`
- `docs/adr/0011-money-stored-as-integer-rupiah.md`

### Snapshot repo / output command yang dipakai
- tree repo utama
- use case inventory/product/procurement existing
- core entity existing
- provider binding existing
- test suite Step 6 dan Step 7

---

## Fakta terkunci `[FACT]`

### 1. Bounded context Note resmi sudah lahir
Area baru yang hidup:
- `app/Core/Note/...`
- `app/Application/Note/...`
- `app/Ports/Out/Note/...`
- `app/Adapters/Out/Note/...`
- `tests/Feature/Note/...`

Ini berarti Step 7 tidak lagi berupa design lock saja, tetapi sudah punya implementasi nyata.

---

### 2. Persistence Step 7 memakai struktur relasional typed detail
Table yang hidup:
- `notes`
- `work_items`
- `work_item_service_details`
- `work_item_external_purchase_lines`
- `work_item_store_stock_lines`

Implikasi:
- Step 7 tidak memakai JSON blob tunggal
- typed detail tetap tegas
- struktur selaras dengan pola procurement/inventory yang sudah hidup di repo

---

### 3. Domain inti Step 7 tetap Note dan WorkItem
Kontrak inti yang hidup di kode:
- `Note` sebagai aggregate root
- `WorkItem` sebagai unit kerja di dalam note

UI boleh memakai istilah line, tetapi backend tetap memetakan line ke `WorkItem`.

---

### 4. Tipe transaksi Step 7 yang hidup dan terbukti
Tipe transaksi yang saat ini hidup di engine Step 7:
- `service_only`
- `service_with_external_purchase`
- `store_stock_sale_only`
- `service_with_store_stock_part`

Seluruh tipe di atas sudah punya proof lewat feature test.

---

### 5. Status operasional WorkItem yang hidup
Status WorkItem yang hidup di domain:
- `open`
- `done`
- `canceled`

Fakta pembuktian:
- field status tersimpan di persistence
- work item yang dibuat default ke `open`
- proof eksplisit lewat test ada pada status default `open`

Catatan presisi:
- capability status per item hidup
- tetapi belum ada feature test khusus yang memodelkan satu note dengan banyak item dan status campuran berbeda-beda

Jadi ini bukan blocker, tetapi perlu dicatat agar tidak overclaim.

---

### 6. Semua nominal uang tetap integer rupiah
Field uang di Step 7 tetap integer rupiah:
- `total_rupiah`
- `subtotal_rupiah`
- `service_price_rupiah`
- `unit_cost_rupiah`
- `line_total_rupiah`

Domain menggunakan `Money` value object untuk kalkulasi internal.

---

### 7. Customer owned part tetap ringan di UI dan tegas di backend
Untuk `service_only`, marker part source yang hidup:
- `none`
- `customer_owned`

Fakta yang terbukti:
- `customer_owned` pada jalur service only tidak membuat inventory movement
- jalur ini tetap dianggap non-store-stock

---

### 8. External purchase diperlakukan sebagai case cost, bukan inventory
Untuk `service_with_external_purchase`:
- service detail hidup
- external purchase lines hidup
- subtotal note memasukkan biaya external purchase
- tidak ada inventory movement
- tidak ada update `product_inventory`
- tidak ada update `product_inventory_costing`

Ini selaras dengan ADR external spare part sebagai case cost.

---

### 9. Store stock flow hidup melalui inventory engine resmi
Untuk `store_stock_sale_only` dan `service_with_store_stock_part`:
- store stock lines tersimpan
- inventory movement `stock_out` tercipta
- `product_inventory` turun
- `product_inventory_costing` turun
- source type movement yang dipakai:
  - `work_item_store_stock_line`

Fakta terpenting:
- Step 7 tidak mutasi projection inventory secara liar
- Step 7 memanggil integration surface resmi inventory

---

### 10. Refactor Step 6 yang dilakukan bukan perubahan domain, tetapi integration surface reuse
File baru:
- `app/Application/Inventory/Services/IssueInventoryOperation.php`

File existing yang diubah:
- `app/Application/Inventory/UseCases/IssueInventoryHandler.php`

Tujuan refactor:
- menghindari nested transaction saat Step 7 stock-backed item memanggil logika issue inventory
- menjaga rule Step 6 tetap satu sumber
- membiarkan `IssueInventoryHandler` tetap hidup untuk mode standalone
- memberi Step 7 reusable operation tanpa transaction ownership sendiri

Fakta pembuktian:
- seluruh regression test inventory Step 6 tetap PASS setelah refactor

---

### 11. Floor pricing store stock dikunci dari Product.hargaJual
Untuk store stock line:
- harga awal boleh diisi dari UI
- harga tetap editable
- tetapi validasi floor minimum memakai:
  - `Product.hargaJual() * qty`

Fakta pembuktian:
- jalur store stock sukses saat `line_total_rupiah >= harga_jual * qty`
- jalur store stock gagal saat `line_total_rupiah < harga_jual * qty`

Ini adalah floor pricing yang hidup pada Step 7 saat ini.

---

### 12. AddWorkItemHandler sekarang menjadi orchestration point resmi untuk Step 7
`AddWorkItemHandler` saat ini menangani:
- ambil note
- bangun typed detail sesuai transaction type
- simpan work item
- untuk store stock line: panggil `IssueInventoryOperation`
- update total note
- semua dalam transaction manager yang sama

Implikasi:
- atomisitas bisnis untuk stock-backed work item terjaga
- menghindari save note sukses tetapi issue stock gagal, atau sebaliknya

---

### 13. Step 7 yang selesai adalah engine layer, bukan transport HTTP
Belum ada proof bahwa Step 7 punya:
- route
- request validation HTTP
- controller
- presenter

Jadi penutupan Step 7 harus dibaca sebagai:
- engine/domain/application/persistence/test selesai
- transport UI HTTP belum dibangun

Ini penting agar halaman berikutnya tidak salah klaim.

---

## Scope yang dipakai

### `[SCOPE-IN]`
- create note
- add work item
- `service_only`
- `service_with_external_purchase`
- `store_stock_sale_only`
- `service_with_store_stock_part`
- total note calculation
- typed detail persistence
- floor pricing store stock
- integration store stock ke inventory engine resmi
- regression safety Step 6 inventory
- feature tests Step 7

### `[SCOPE-OUT]`
- HTTP route/controller/request/presenter Step 7
- payment / receivable / outstanding
- paid detection
- refund
- correction paid note
- audit mutation detail lanjutan
- reporting
- explicit multi-item mixed-status feature test
- UI page / JS transport

---

## Keputusan yang dikunci `[DECISION]`

### 1. Step 7 diselesaikan dengan vertical slices bertahap
Urutan yang dipakai:
- Slice 1:
  - create note
  - `service_only`
  - `service_with_external_purchase`
- Slice 2:
  - inventory integration surface refactor
  - store stock line foundation
  - `store_stock_sale_only`
  - `service_with_store_stock_part`

Alasan:
- paling aman
- paling mudah dibuktikan
- paling selaras dengan repo yang sudah hidup

---

### 2. Integration surface Step 6 disentuh secara minimal dan sah
Keputusan yang diambil:
- Step 6 tidak dibongkar
- domain/rule Step 6 tidak diubah
- hanya permukaan reuse yang diekstrak agar Step 7 bisa reuse inventory issue tanpa nested transaction liar

Ini penting untuk dibawa ke halaman berikutnya:
- jangan sebut Step 6 cacat
- yang diperbaiki hanyalah application composition surface

---

### 3. Floor pricing store stock untuk Step 7 memakai Product.hargaJual
Karena:
- product master sudah hidup
- `harga_jual` sudah jadi nilai resmi domain product
- belum ada fakta repo yang mendukung floor lain yang lebih spesifik

Jadi untuk Step 8 dan halaman berikutnya:
- anggap floor ini sudah final untuk scope Step 7
- jangan buka ulang tanpa konflik fakta nyata

---

### 4. Step 7 selesai pada engine/domain/application/persistence/test level
Jangan mengulang diskusi:
- Note vs CustomerOrder vs CustomerTransaction
- typed detail vs common fields
- customer owned part sebagai preset besar
- store stock line harus atau tidak harus typed detail
- external purchase masuk inventory atau tidak

Semua itu sudah final di Step 7.

---

### 5. Step 8 harus mulai dari kontrak internal payment/receivable
Bukan langsung coding.

Alasan:
- Step 7 sudah menutup domain note/item/stock/case cost
- Step 8 adalah bounded context baru untuk pembayaran
- payment tidak boleh bocor mundur dan merusak desain Step 7

---

## File yang dibuat/diubah `[FILES]`

### File baru
- `app/Core/Note/Note/Note.php`
- `app/Core/Note/WorkItem/ServiceDetail.php`
- `app/Core/Note/WorkItem/ExternalPurchaseLine.php`
- `app/Core/Note/WorkItem/StoreStockLine.php`
- `app/Core/Note/WorkItem/WorkItem.php`
- `app/Application/Note/UseCases/CreateNoteHandler.php`
- `app/Application/Note/UseCases/AddWorkItemHandler.php`
- `app/Application/Inventory/Services/IssueInventoryOperation.php`
- `app/Ports/Out/Note/NoteReaderPort.php`
- `app/Ports/Out/Note/NoteWriterPort.php`
- `app/Ports/Out/Note/WorkItemWriterPort.php`
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Adapters/Out/Note/DatabaseNoteWriterAdapter.php`
- `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php`
- `database/migrations/2026_03_14_000100_create_notes_table.php`
- `database/migrations/2026_03_14_000200_create_work_items_table.php`
- `database/migrations/2026_03_14_000300_create_work_item_service_details_table.php`
- `database/migrations/2026_03_14_000400_create_work_item_external_purchase_lines_table.php`
- `database/migrations/2026_03_14_000500_create_work_item_store_stock_lines_table.php`
- `tests/Feature/Note/CreateNoteFeatureTest.php`
- `tests/Feature/Note/AddServiceOnlyWorkItemFeatureTest.php`
- `tests/Feature/Note/AddExternalPurchaseWorkItemFeatureTest.php`
- `tests/Feature/Note/AddStoreStockSaleOnlyWorkItemFeatureTest.php`
- `tests/Feature/Note/AddServiceWithStoreStockPartWorkItemFeatureTest.php`

### File diubah
- `app/Application/Inventory/UseCases/IssueInventoryHandler.php`
- `app/Providers/HexagonalServiceProvider.php`

Catatan:
- beberapa file baru seperti `WorkItem.php`, `AddWorkItemHandler.php`, `DatabaseNoteReaderAdapter.php`, dan `DatabaseWorkItemWriterAdapter.php` mengalami revisi bertahap selama Step 7, tetapi secara status repo mereka tetap tergolong file baru pada Step 7.

---

## Bukti verifikasi `[PROOF]`

### 1. Syntax check file Step 7 awal
- command:
  - `php -l app/Core/Note/Note/Note.php`
  - `php -l app/Core/Note/WorkItem/WorkItem.php`
  - `php -l app/Core/Note/WorkItem/ServiceDetail.php`
  - `php -l app/Core/Note/WorkItem/ExternalPurchaseLine.php`
  - `php -l database/migrations/2026_03_14_000100_create_notes_table.php`
  - `php -l database/migrations/2026_03_14_000200_create_work_items_table.php`
  - `php -l database/migrations/2026_03_14_000300_create_work_item_service_details_table.php`
  - `php -l database/migrations/2026_03_14_000400_create_work_item_external_purchase_lines_table.php`
- hasil:
  - seluruh file syntax valid

### 2. Syntax check ports/adapters/use case Note
- command:
  - `php -l app/Ports/Out/Note/NoteReaderPort.php`
  - `php -l app/Ports/Out/Note/NoteWriterPort.php`
  - `php -l app/Ports/Out/Note/WorkItemWriterPort.php`
  - `php -l app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
  - `php -l app/Adapters/Out/Note/DatabaseNoteWriterAdapter.php`
  - `php -l app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php`
  - `php -l app/Application/Note/UseCases/CreateNoteHandler.php`
  - `php -l app/Application/Note/UseCases/AddWorkItemHandler.php`
  - `php -l app/Providers/HexagonalServiceProvider.php`
- hasil:
  - seluruh file syntax valid

### 3. Feature test Slice 1 PASS
- command:
  - `php artisan test tests/Feature/Note/CreateNoteFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddServiceOnlyWorkItemFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddExternalPurchaseWorkItemFeatureTest.php`
- hasil:
  - `CreateNoteFeatureTest` PASS
  - `AddServiceOnlyWorkItemFeatureTest` PASS
  - `AddExternalPurchaseWorkItemFeatureTest` PASS

### 4. Inventory integration surface refactor regression PASS
- command:
  - `php artisan test tests/Feature/Inventory/IssueInventoryFeatureTest.php`
  - `php artisan test tests/Feature/Inventory/RebuildInventoryProjectionFeatureTest.php`
  - `php artisan test tests/Feature/Inventory/RebuildInventoryCostingProjectionFeatureTest.php`
  - `php artisan test tests/Feature/Inventory/RebuildInventoryCostingProjectionWithStockOutFeatureTest.php`
- hasil:
  - seluruh regression inventory PASS setelah `IssueInventoryOperation` diperkenalkan

### 5. Store stock foundation regression PASS
- command:
  - `php -l app/Core/Note/WorkItem/StoreStockLine.php`
  - `php -l app/Core/Note/WorkItem/WorkItem.php`
  - `php -l database/migrations/2026_03_14_000500_create_work_item_store_stock_lines_table.php`
  - `php artisan test tests/Feature/Note/CreateNoteFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddServiceOnlyWorkItemFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddExternalPurchaseWorkItemFeatureTest.php`
- hasil:
  - syntax valid
  - regression Slice 1 tetap PASS

### 6. `store_stock_sale_only` PASS
- command:
  - `php artisan test tests/Feature/Note/AddStoreStockSaleOnlyWorkItemFeatureTest.php`
- hasil:
  - success path PASS
  - reject insufficient stock PASS
  - reject below floor pricing PASS

### 7. `service_with_store_stock_part` PASS
- command:
  - `php artisan test tests/Feature/Note/AddServiceWithStoreStockPartWorkItemFeatureTest.php`
- hasil:
  - success path PASS
  - reject insufficient stock PASS
  - reject below floor pricing PASS

### 8. Final regression set PASS
- command:
  - `php artisan test tests/Feature/Inventory/IssueInventoryFeatureTest.php`
  - `php artisan test tests/Feature/Note/CreateNoteFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddServiceOnlyWorkItemFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddExternalPurchaseWorkItemFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddStoreStockSaleOnlyWorkItemFeatureTest.php`
  - `php artisan test tests/Feature/Note/AddServiceWithStoreStockPartWorkItemFeatureTest.php`
- hasil:
  - seluruh suite PASS

---

## Blocker aktif
- tidak ada blocker aktif untuk penutupan Step 7
- Step 7 engine dinyatakan selesai

### Catatan yang perlu dibawa, bukan blocker
- HTTP transport Step 7 belum dibangun
- belum ada feature test khusus multi-item satu note dengan status campuran
- ini tidak menghalangi Step 8, tetapi harus dicatat agar halaman berikutnya tidak salah klaim bahwa UI transport Step 7 sudah selesai

---

## Next step paling aman

## Step 8 — Payment & Receivable Engine

### Halaman berikutnya harus mulai dari:
- lock internal contract Step 8
- bukan langsung coding
- bukan membuka ulang Step 7

### Fokus awal Step 8 yang sah
Halaman berikutnya cukup mulai dari penguncian kontrak internal berikut:
- entitas/aggregate payment minimum
- relasi payment ke note
- partial payment default
- outstanding calculation source
- over-allocation rejection
- full paid detection
- apakah alokasi payment level note total dulu atau level work item
- persistence minimum Step 8
- batas Step 8 vs Step 9 correction/refund

---

## Arahan implementasi agar Step 8 tidak merusak Step 7

### 1. Jangan buka ulang domain Step 7 tanpa konflik fakta nyata
Asumsikan hal berikut sudah final:
- Note dan WorkItem tetap inti domain Step 7
- transaction type Step 7 sudah final untuk scope saat ini
- store stock line logic sudah final untuk Step 7
- external purchase tetap non-inventory
- floor pricing Step 7 memakai `Product.hargaJual`

### 2. Payment state jangan dimasukkan mundur ke WorkItem Step 7
Status operasional item tetap:
- `open`
- `done`
- `canceled`

Status lunas/hutang/outstanding bukan status operasional WorkItem Step 7.

### 3. Outstanding dan paid detection harus dibangun di Step 8
Jangan menempelkan logika payment ke Step 7 handler.

### 4. Koreksi transaksi lunas dan refund tetap ditahan untuk Step 9
Step 8 hanya fondasi payment/receivable.
Jangan lompat ke refund/correction di halaman berikutnya.

---

## Ringkasan eksekutif untuk halaman berikutnya

Anggap hal-hal berikut **sudah final dan tidak perlu didiskusikan ulang**:
- Step 7 closed
- engine Note multi-item hidup
- non-stock dan stock-backed work item hidup
- external purchase adalah case cost, bukan inventory
- store stock potong stok lewat inventory engine resmi
- floor pricing store stock sudah hidup
- integration surface Step 6 ke Step 7 sudah sah dan tervalidasi
- Step 7 belum punya HTTP transport, tetapi itu tidak menghalangi Step 8 engine

Halaman berikutnya langsung mulai dari:
- **Step 8 internal contract lock**
- target: payment & receivable engine
- jangan ulang Step 7 kecuali ada defect nyata atau konflik fakta repo

---

## Status untuk laporan
- Step 7 Preparation / Design Lock: selesai
- Step 7 Implementation / Verification: selesai
- Step 7 overall: CLOSED 100%
- Workflow induk:
  - Step 1–7: selesai
  - Progres workflow induk: 50%
