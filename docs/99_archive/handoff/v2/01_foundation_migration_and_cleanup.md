# Handoff
Halaman: Migrasi v2 foundation + cleanup legacy tests terkait schema

## Status
Selesai untuk scope halaman ini.
Kondisi akhir yang terkunci:

* migrasi foundation v2 selesai
* cleanup legacy tests yang pecah karena FK baru selesai
* full suite hijau
* hasil akhir verifikasi: 468 passed, 2148 assertions

Progress halaman ini: 100%

## Tujuan halaman yang sudah tercapai
Yang diminta di halaman ini adalah mematangkan sisi migrasi dulu, sebelum pindah ke usecase/application. Itu sudah tercapai untuk ruang lingkup berikut:

* index untuk hot path query
* foreign key hardening untuk relasi eksplisit utama
* audit foundation v2
* soft delete foundation untuk master data aman
* versioning foundation untuk master data aman
* search normalization + duplicate hardening untuk products
* cleanup legacy tests lama agar selaras dengan schema baru

## Keputusan arsitektur yang terkunci
**v1 tetap live dulu, v2 dibangun offline dari backup**
Ini dikunci karena hosting shared minim fasilitas operasional. Strategi ini tetap valid.

**Tidak semua tabel diberi soft delete**
Ini keputusan penting.
* `products`, `suppliers` boleh soft delete
* `notes` tetap lifecycle-based
* `work_items` ikut lifecycle note
* `inventory_movements` immutable, tidak soft delete
* event/snapshot/ledger tidak diperlakukan seperti master biasa

**Foreign key tidak diturunkan demi test lama**
Test yang diperbaiki, bukan schema yang dilemahkan.

**Polymorphic relation belum diredesign di halaman ini**
Area seperti `source_type`/`source_id` dan `component_type`/`component_ref_id` sengaja tidak dipaksa jadi FK biasa di halaman ini.

## Migrasi baru yang ditambahkan
File migration yang dibuat:
* `database/migrations/2026_04_06_210000_add_v2_hot_path_indexes_for_existing_tables.php`
* `database/migrations/2026_04_06_220100_add_v2_procurement_inventory_foreign_keys.php`
* `database/migrations/2026_04_06_220200_add_v2_transaction_finance_foreign_keys.php`
* `database/migrations/2026_04_06_220300_add_v2_note_mutation_workspace_foreign_keys.php`
* `database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php`
* `database/migrations/2026_04_06_230200_add_soft_delete_foundation_to_products_and_suppliers.php`
* `database/migrations/2026_04_06_230300_create_product_and_supplier_versions_tables.php`
* `database/migrations/2026_04_06_230400_add_product_search_normalization_and_duplicate_hardening.php`

## Hasil perubahan schema
Yang sudah ditanam di database:

**1. Hot-path indexes**
Index baru yang ditambahkan:
* `audit_logs.event`
* `products.merek`
* `products.ukuran`
* `products.harga_jual`
* composite lookup products (`nama_barang`, `merek`, `ukuran`)
* `payment_allocations` (`customer_payment_id`, `note_id`)
* `payment_component_allocations` (`customer_payment_id`, `note_id`)
* `payment_component_allocations.work_item_id`
* `refund_component_allocations.work_item_id`

**2. Foreign keys**
Total FK baru yang ditanam: 32
Cakupan utama:
* procurement chain
* inventory projection/costing chain
* work item ke note
* payment/refund allocation chain
* note mutation chain
* workspace draft ke note

**3. Audit foundation v2**
Tabel baru:
* `audit_events`
* `audit_event_snapshots`
Tujuan:
* actor
* role
* aggregate
* event
* reason
* occurred_at
* metadata
* before/after snapshot

**4. Soft delete foundation**
Ditambahkan ke:
* `products`
* `suppliers`
Kolom:
* `deleted_at`
* `deleted_by_actor_id`
* `delete_reason`

**5. Versioning foundation**
Tabel baru:
* `product_versions`
* `supplier_versions`
Tujuan:
* histori edit master
* revision number
* siapa ubah
* alasan
* snapshot state saat revision

**6. Product search hardening**
Ditambahkan ke `products`:
* `nama_barang_normalized`
* `merek_normalized`
* index normalized search
* unique `kode_barang`
* unique business identity berbasis normalized name + normalized brand + ukuran

## Test database baru yang ditambahkan
File test schema/migration baru:
* `tests/Feature/Database/V2HotPathIndexesMigrationTest.php`
* `tests/Feature/Database/V2ForeignKeysMigrationTest.php`
* `tests/Feature/Database/V2AuditFoundationMigrationTest.php`
* `tests/Feature/Database/V2MasterSoftDeleteFoundationMigrationTest.php`
* `tests/Feature/Database/V2MasterVersioningFoundationMigrationTest.php`
* `tests/Feature/Database/V2ProductSearchNormalizationMigrationTest.php`
* `tests/Feature/Database/V2NoteOperationalStateMigrationTest.php`

## Helper test fixture baru
Untuk membuat legacy tests patuh pada schema baru:
* `tests/Support/SeedsMinimalProductFixture.php`
* `tests/Support/SeedsMinimalProcurementFixture.php`
* `tests/Support/SeedsMinimalInventoryProductFixture.php`
* `tests/Support/SeedsMinimalNotePaymentFixture.php`

## Legacy tests yang dirapikan
Klaster yang sudah dirapikan di halaman ini:

**Database/migration style**
* `tests/Feature/Note/NoteOperationalStateColumnsMigrationFeatureTest.php` dihapus/diganti arah uji
* `tests/Feature/Database/V2NoteOperationalStateMigrationTest.php` dibuat sebagai pengganti gaya baru

**Inventory**
* `IssueInventoryFeatureTest`
* `RebuildInventoryProjectionFeatureTest`
* `RebuildInventoryCostingProjectionFeatureTest`
* `RebuildInventoryCostingProjectionWithStockOutFeatureTest`
* `ReverseIssuedInventoryOperationFeatureTest`
* `ReverseNoteStoreStockInventoryOperationFeatureTest`

**Note / cashier access / correction / payment**
* `CashierNoteDetailAccessGuardFeatureTest`
* `CorrectPaidServiceOnlyWorkItemFeatureTest`
* `CorrectPaidServiceOnlyWorkItemHttpFeatureTest`
* `CorrectPaidServiceWithStoreStockPartServiceFeeOnlyFeatureTest`
* `NoteCorrectionHistoryBuilderFeatureTest`
* `NoteDetailPageFeatureTest`
* `UpdateServiceWithStoreStockPartServiceFeeOnlyWriterFeatureTest`
* `RecordAndAllocateNotePaymentFeatureTest`
* `AutoClosePaidNoteOnFullPaymentFeatureTest`
* `RecordCustomerRefundFeatureTest`

**Procurement**
* `RecordSupplierPaymentFeatureTest`
* `CreateSupplierInvoiceFeatureTest`
* `ReceiveSupplierInvoiceFeatureTest`
* `AttachSupplierPaymentProofFeatureTest`
* `ProcurementInvoiceTableDataAccessFeatureTest`
* `ProcurementInvoiceTableDataQueryFeatureTest`
* `ServeSupplierPaymentProofAttachmentFeatureTest`

**Reporting**
* `TransactionSummaryReportingQueryFeatureTest`
* `TransactionCashLedgerReportingQueryFeatureTest`
* `GetOperationalProfitSummaryFeatureTest`
* `GetSupplierPayableSummaryFeatureTest`

## Verifikasi yang sudah terbukti
Bukti yang sudah ada di halaman ini:
* orphan scan relasi kandidat FK: lolos
* compatibility scan tipe kolom FK: lolos
* migration hot-path indexes: lolos
* migration FK hardening: lolos
* migration audit foundation: lolos
* migration soft delete foundation: lolos
* migration versioning foundation: lolos
* migration product normalization/hardening: lolos
* targeted migration tests: lolos
* cleanup legacy tests per klaster: lolos
* final verification: make test hijau dengan 468 passed, 2148 assertions

## Fakta baru yang terkunci
* data produk nyata yang dipakai untuk preflight bersih untuk hardening:
  * tidak ada blank field utama
  * tidak ada duplicate `kode_barang`
  * tidak ada duplicate business key `nama_barang` + `merek` + `ukuran`
* relasi eksplisit utama sudah aman untuk FK
* mayoritas kegagalan setelah FK bukan masalah migration, tapi fixture test lama
* refund test yang paling keras kepala ternyata gagal karena invariant domain work item, bukan FK

## Scope yang sengaja tidak dikerjakan di halaman ini
Ini penting supaya tidak ada salah ekspektasi nanti.
Belum dikerjakan:
* wiring usecase/application agar menulis `audit_events` / `audit_event_snapshots`
* wiring version rows otomatis dari writer/usecase
* query adapter full switch ke normalized product search
* redesign relasi polymorphic
* importer/backfill v1 backup ke v2
* kebijakan soft delete/versioning untuk semua bounded context
* behavioral tests yang memverifikasi seluruh restrict/delete behavior lintas semua modul

## Hutang teknis yang tersisa
Ini hutang teknis aktif setelah halaman ini ditutup:

**Audit foundation belum dipakai penuh oleh application layer**
`audit_events` dan `audit_event_snapshots` sudah ada, tapi writer/usecase belum semuanya dual-write atau pindah ke model baru.

**Versioning foundation belum diisi otomatis**
`product_versions` dan `supplier_versions` sudah ada, tapi belum ada writer/service yang membuat revision row saat create/edit/delete.

**Normalized search belum dipakai menyeluruh di query layer**
Kolom normalized untuk `products` sudah ada, tapi penggunaan adapter/query ke kolom itu masih pekerjaan halaman berikutnya.

**Polymorphic relation masih application-enforced**
Contoh:
* `inventory_movements.source_type`/`source_id`
* `payment_component_allocations.component_type`/`component_ref_id`
* `refund_component_allocations.component_type`/`component_ref_id`

**Soft delete baru foundation level**
Baru aman di `products` dan `suppliers`. Belum ada penerapan penuh di repos/query/usecase/filtering UI.

## Strategi migrasi data nyata v1 -> v2 belum dibuat
Ini memang sengaja ditunda karena halaman ini fokus migrasi foundation.

## Risiko kalau halaman berikutnya mulai
Tidak ada blocker dari sisi migrasi foundation. Tapi ada aturan main:
* halaman berikutnya sebaiknya fokus ke usecase/application integration
* jangan ubah keputusan yang sudah dikunci di sini tanpa bukti baru
* jangan lemahkan FK atau unique hanya demi test lama
* jika nanti ada import data nyata, lakukan dari backup offline, bukan eksperimen di hosting live

## Safest next step
Halaman berikutnya yang paling aman adalah:
Usecase/Application integration untuk master data dan audit/versioning, dengan urutan:
1. product writer/service menulis `product_versions` + `audit_events`
2. supplier writer/service menulis `supplier_versions` + `audit_events`
3. query/search product beralih ke normalized columns
4. baru evaluasi bounded context lain

## Penutup halaman
Halaman ini saya anggap closed untuk scope:
* migrasi foundation v2
* hardening schema
* cleanup legacy tests yang pecah karena schema baru

Ringkasan akhir:
* migrasi foundation: selesai
* legacy test cleanup terkait schema: selesai
* final proof: 468 passed, 2148 assertions
* handoff: siap dipakai lanjut ke halaman usecase/application

Kalau Anda buka halaman baru untuk usecase/application, baseline yang dipakai adalah hasil handoff ini. Jangan mulai dari asumsi baru, nanti kita malah mengulang sirkus yang sama.
