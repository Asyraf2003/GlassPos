# 0043 Service Package Component Refund Pay Again Inventory Cash Mismatch

## Status

Fixed with proof.

Current verification:

- `make verify` PASS on 2026-06-25.
- Full Pest summary: `1416 passed, 8405 assertions`.
- Target matrix included in full suite:
  - `tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php`
  - result in full suite: PASS.

Final patch scope:

- Write-side payment allocator guard blocks silent pay-again for refunded
  `service_store_stock_part` components that already have inventory reversal.
- Report layer was not used to hide write-side mismatch.
- No Mobile API scope was changed.

Historical note:

- The forensic audit text below is retained as source evidence for the original
  bug and patch reasoning.

## Scope

Manual QA HyperPOS Laravel menemukan lifecycle kasir yang membuat laporan terlihat ngawur setelah:

1. Create nota kasir berisi service x product package, package product parts, service lain, dan pembayaran sebagian.
2. Edit/revision package.
3. Lunasi nota.
4. Refund selected rows/components.
5. Aksi lunasi muncul lagi.
6. User melunasi lagi.
7. Laporan cash/profit/package breakdown menjadi tidak sinkron.

Scope audit ini hanya data lifecycle:

- note state
- revision
- payments
- payment component allocations
- customer refunds
- refund component allocations
- inventory movements
- transaction summary
- cash ledger
- operational profit
- service package profit breakdown

Tidak mengubah Mobile API.
Tidak mengubah formula Operational Profit.
Tidak patch report sebelum akar write-side jelas.

## FACT

Kasus runtime lokal:

- note_id: `e5ed4fbc-4e3b-4105-af12-227886b1ab22`
- transaction_date: `2026-06-24`
- customer: `tes`
- final note_state: `closed`
- current_revision_id: `e5ed4fbc-4e3b-4105-af12-227886b1ab22-r002`
- final active total: `265000`

Audit membuktikan ini bukan full refund seluruh nota.

Refund yang tercatat:

- customer_refunds sum: `182500`
- refund_component_allocations sum: `182500`

Payment yang tercatat:

- payment 1: `210000` cash
- payment 2: `82500` cash
- payment 3 after refund/pay-again: `155000` transfer
- payment_component_allocations sum: `447500`
- net component paid after refund: `265000`

## DATA FOUND

Runtime sequence:

- `07:24:09` payment 1 dibuat sebesar `210000`.
- `07:28:49` revision r002 aktif.
- `07:32:51` payment 2 dibuat sebesar `82500`.
- `07:32:51` note auto-closed via `AUTO_CLOSE_ON_FULL_PAYMENT`.
- `07:33:57` refund dibuat sebesar `182500`.
- `07:33:57` mutation event hanya `note_rows_canceled_via_refund`.
- Tidak ada mutation event `note_refunded`.
- `07:35:44` payment 3 dibuat sebesar `155000`.

Refund component allocation:

- package part `prod-year-009`: `37500`
- package part `prod-year-030`: `90000`
- package part `prod-year-005`: `27500`
- canceled product-only work item: `27500`
- total: `182500`

Yang tetap paid setelah refund:

- service package service fee: `50000`
- service only: `60000`
- retained paid total: `110000`

Outstanding setelah refund:

- active note total: `265000`
- retained net paid: `110000`
- outstanding: `155000`

Payment ketiga sebesar `155000` dialokasikan lagi ke tiga package product components yang sebelumnya direfund.

Inventory movements:

- seluruh store stock line source punya net_qty `0`
- seluruh store stock line source punya net_cost `0`

Artinya stok untuk package parts sudah keluar lalu direversal masuk lagi, tetapi payment ketiga membayar ulang package parts yang sama tanpa stock_out baru.

## BROKEN INVARIANT

Invariant yang rusak bukan “full refunded note bisa dibayar lagi”.

Invariant yang rusak:

Selected/component refund pada service package membalik inventory product components, tetapi service package row tetap active dan product components yang sama tetap payable lagi.

Akibatnya:

- cash layer melihat product components sebagai outstanding lagi
- inventory layer sudah mengembalikan stok
- payment ketiga membayar product components yang sama
- tidak ada inventory stock_out baru untuk payment ketiga
- report membaca event cash/payment/refund yang memang sudah tidak sinkron secara write-side

## ROOT CAUSE CANDIDATE

Kandidat root cause utama:

Refund selected rows/components belum punya policy domain tegas untuk service package product components.

Ambiguitas policy:

1. Jika product components package direfund, apakah entire package row harus canceled?
2. Jika package row tetap active, apakah refunded product components boleh payable lagi?
3. Jika payable lagi, apakah inventory harus stock_out ulang?
4. Jika inventory sudah direversal, apakah report boleh tetap menganggap package sold amount aktif?

Source-level suspect:

- outstanding/payment allocation memperlakukan refund sebagai pengurang paid component, sehingga component bisa outstanding lagi.
- selected row cancel hanya membatalkan selected work items, bukan otomatis membatalkan service package row.
- finalize refunded note tidak jalan karena active note total masih `265000`, bukan `0`.
- normal payment flow masih memungkinkan pembayaran outstanding pada note closed/refund-sensitive flow.

## REPORT IMPACT

Transaction summary untuk note:

- gross_transaction_rupiah: `265000`
- allocated_payment_rupiah: `447500`
- refunded_rupiah: `182500`

Cash ledger untuk note:

- in cash: `210000`
- in cash: `82500`
- in transfer: `155000`
- out refund: `182500`
- net cash: `265000`

Operational profit:

- cash_in_rupiah: `447500`
- refunded_rupiah: `182500`
- store_stock_cogs_rupiah: `5573`
- cash_operational_profit_rupiah: `259427`

Service package profit breakdown:

- package_sold_amount_rupiah: `205000`
- parts_total_rupiah: `155000`
- refunded_product_component_rupiah: `155000`
- sparepart_cogs_rupiah: `0`
- sparepart_margin_rupiah: `155000`
- total_package_gross_profit_rupiah: `205000`

Report impact paling mencurigakan:

Service package breakdown menampilkan package sold amount dan parts total aktif, tetapi refunded product component sama dengan parts total dan COGS net zero. Ini gejala cash/inventory/package lifecycle tidak sinkron.

## WHY TEST SEBELUMNYA BELUM NANGKAP

Test sebelumnya mengunci bahwa partial refund boleh membuka outstanding lagi.

Gap test:

- belum ada test service package product component refund
- belum ada test pay-again setelah package product component refund
- belum ada assertion inventory reversal vs payment ulang
- belum ada assertion report package breakdown setelah component refund + pay again
- belum ada invariant bahwa package product components yang sudah inventory-reversed tidak boleh silently paid again tanpa stock_out baru

## WHAT IS SAFE / UNSAFE

Safe:

- tambah characterization test
- tambah policy decision doc
- audit source write-side refund/package/payment/inventory
- audit read model hanya setelah write-side invariant diputuskan

Unsafe:

- langsung ubah Operational Profit formula
- langsung ubah report agar “kelihatan benar”
- langsung block semua pay-again setelah refund
- langsung final refunded untuk semua selected refund
- ubah Mobile API
- patch tanpa test lifecycle package component refund

## PATCH PLAN

Belum patch.

Candidate patch plan setelah policy diputuskan:

1. Tambah characterization test untuk service package product component refund lalu pay-again.
2. Pilih satu domain policy:
   - refund product components package membatalkan entire package row; atau
   - package row tetap active, tapi refunded product components tidak boleh payable lagi; atau
   - pay-again allowed, tapi wajib create stock_out baru.
3. Terapkan guard write-side di payment/refund/package lifecycle, bukan hanya report.
4. Baru sesuaikan report jika write-side invariant sudah benar.

## TEST PLAN

Minimal tests:

- service package product component refund creates inventory reversal.
- after package component refund, same component cannot be silently paid again without stock_out.
- cash ledger after refund/pay attempt stays consistent with allowed lifecycle.
- transaction summary allocated/refunded does not exceed intended lifecycle.
- operational profit does not count cash for product components whose inventory is net zero unless stock_out is recreated.
- service package profit breakdown does not show full package profit from refunded product components with COGS zero.

## NEXT COMMAND

Next audit step should read source around:

- `SelectedNoteRowsPaymentAmountResolver`
- `NoteBillingProjectionBuilder`
- `ExistingPaymentComponentTotals`
- `AllocatePaymentAcrossComponents`
- `AllocateRefundAcrossComponents`
- `CancelSelectedRowsAndSyncActiveNoteTotal`
- `FinalizeRefundedNoteFromActiveRows`
- `AutoReverseRefundedStoreStockInventory`
- `ServicePackageProfitBreakdownQuery`

No patch before characterization test exists.

## Slice 2 Forensic Source Discovery - Payment Allocator Candidate

### FACT

Source discovery mengarah ke write-side payment allocation, bukan reporting layer.

Kandidat utama:

- `app/Application/Payment/Services/AllocatePaymentAcrossComponents.php`
- `app/Application/Payment/Services/ExistingPaymentComponentTotals.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`

`RecordAndAllocateNotePaymentOperation` membuat payment baru melalui flow:

1. Lock note via `getByIdForUpdate`.
2. Hitung `grossAllocatedByNote`.
3. Hitung `refundedByNote`.
4. Hitung `netAllocatedByNote = grossAllocated - refunded`.
5. Resolve payable components.
6. Panggil `AllocatePaymentAcrossComponents::allocate`.
7. Baru tulis `customer_payments`.
8. Baru tulis `payment_component_allocations`.

Implikasi: guard paling aman diletakkan sebelum writer dipanggil, supaya silent pay-again gagal sebagai `Result::failure()` dan tidak membuat payment/allocation baru.

### DATA FOUND

`AllocatePaymentAcrossComponents` saat ini membaca existing component total lewat:

```php
$existing = ExistingPaymentComponentTotals::build($this->existingAllocations, $noteId, $this->refunds);
```

Lalu untuk setiap component:

```php
$key = ExistingPaymentComponentTotals::key($component->componentType(), $component->componentRefId());
$already = $existing[$key] ?? 0;
$available = max($component->amountRupiah()->amount() - $already, 0);
```

`ExistingPaymentComponentTotals` mengurangi allocated component total dengan refund component total:

```php
$totals[$key] = max(($totals[$key] ?? 0) - $refund->refundedAmountRupiah()->amount(), 0);
```

Ini membuka kembali allocation room untuk semua refunded component.

Untuk component biasa, perilaku ini masih masuk akal karena refund memang membuka outstanding ulang.

Untuk inventory-backed service package stock component, perilaku ini berbahaya karena component `service_store_stock_part` memakai `component_ref_id` berupa `work_item_store_stock_lines.id`, dan refund sudah membalik inventory lewat movement reversal.

Tipe component yang relevan:

```php
PaymentComponentType::SERVICE_STORE_STOCK_PART
```

Inventory reversal sudah bisa dibaca lewat existing port:

```php
InventoryMovementReaderPort::getBySource(string $sourceType, string $sourceId): array
```

Dengan source:

```text
source_type = work_item_store_stock_line_reversal
source_id   = component_ref_id
```

Binding port inventory reader juga sudah tersedia di `InventoryServiceProvider`, sehingga tidak perlu query DB langsung dari payment service.

### ROOT CAUSE

Akar kandidat yang sudah terkonfirmasi secara source:

`ExistingPaymentComponentTotals` membuka kembali ruang payment allocation untuk refunded component tanpa membedakan component yang inventory-backed dan sudah memiliki stock reversal.

Akibatnya `AllocatePaymentAcrossComponents` dapat melihat `service_store_stock_part` yang sudah refund sebagai available lagi, lalu membuat `payment_component_allocations` baru tanpa ada deliberate new `stock_out`.

Inilah penyebab write-side mismatch:

- Cash/payment naik lagi.
- Payment allocation baru tercatat.
- Inventory tetap net zero karena `stock_out` lama sudah dibalik reversal.
- Report kemudian terlihat ngawur karena membaca state yang memang sudah tidak konsisten.

Report bukan akar masalah. Report hanya memperlihatkan state busuk yang dibuat write-side. Komputer tidak sedang kerasukan, cuma terlalu patuh.

### PATCH DESIGN

Patch produksi yang disarankan untuk slice berikutnya:

1. Inject `InventoryMovementReaderPort` ke `AllocatePaymentAcrossComponents`.
2. Saat iterasi payable component, deteksi component:
   - `component_type === PaymentComponentType::SERVICE_STORE_STOCK_PART`
   - sudah punya refunded component amount `> 0`
   - sudah punya inventory movement:
     - `source_type = work_item_store_stock_line_reversal`
     - `source_id = component_ref_id`
3. Component tersebut tidak boleh dialokasikan ulang secara silent.
4. Jika semua target component blocked, allocator tetap throw existing domain failure:
   - `Tidak ada komponen note yang bisa dialokasikan untuk payment ini.`
   - atau `Payment tidak bisa dialokasikan penuh ke komponen note.`
5. Handler akan classify `DomainException` menjadi `Result::failure()`, sehingga matrix test cukup assert `isFailure()`.

Patch ini akan melindungi dua flow sekaligus karena sama-sama memakai allocator yang sama:

- Normal note payment via `RecordAndAllocateNotePaymentOperation`.
- Inline payment create workspace via `CreateTransactionWorkspaceInlinePaymentRecorder`.

### TEST IMPACT

Unit test berikut instantiate allocator secara manual dan harus ikut disesuaikan dengan fake `InventoryMovementReaderPort`:

- `tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php`

Target verification setelah patch:

```bash
php -l app/Application/Payment/Services/AllocatePaymentAcrossComponents.php
php -l tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
php artisan test tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
php artisan test tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php
```

Expected:

- Unit allocator tetap PASS.
- Matrix test `ServicePackageComponentRefundPayAgainMatrixTest` berubah dari RED business menjadi PASS.
- Tidak ada `customer_payments` baru untuk refunded reversed stock component.
- Tidak ada `payment_component_allocations` baru untuk refunded reversed stock component.
- Tidak ada inventory `stock_out` baru yang diam-diam dibuat.
- Net inventory component yang sudah refund tetap zero.

### PROOF

Slice 2 source discovery menyimpulkan patch tidak boleh dimulai dari report.

Guard harus berada di write-side allocation path sebelum `customer_payments` dan `payment_component_allocations` ditulis.

Final target invariant:

`service_store_stock_part` yang sudah refunded dan sudah punya `work_item_store_stock_line_reversal` tidak boleh menerima payment allocation ulang kecuali ada deliberate new stock-out/reissue flow.

## Slice 3 Patch Verification - Allocator Guard Green

### FACT

Patch allocator untuk mencegah silent pay-again pada refunded service package stock component sudah berhasil diverifikasi.

Target patch:

- `app/Application/Payment/Services/AllocatePaymentAcrossComponents.php`
- `tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php`

Guard ditambahkan di write-side allocation path, sebelum `customer_payments` dan `payment_component_allocations` ditulis.

### DATA FOUND

Targeted verification PASS:

```bash
php -l app/Application/Payment/Services/AllocatePaymentAcrossComponents.php
php -l tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
php artisan test tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
php artisan test tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php
```

Hasil:

```text
syntax check PASS
unit allocator PASS
service package component refund pay-again matrix PASS
tidak ada SQLSTATE generated column issue
tidak ada silent pay-again scenario tersisa pada matrix target
```

### ROOT CAUSE CONFIRMED

Akar bug terkonfirmasi di write-side payment allocation.

`ExistingPaymentComponentTotals` mengurangi allocated component total dengan refund total. Perilaku ini membuka kembali allocation room setelah refund.

Untuk component non-inventory, ini masih wajar.

Untuk `service_store_stock_part`, ini tidak aman ketika component sudah memiliki inventory reversal:

```text
source_type = work_item_store_stock_line_reversal
source_id   = component_ref_id
```

Tanpa guard, allocator menganggap component tersebut payable lagi, lalu mencatat payment/allocation baru tanpa membuat deliberate new `stock_out`.

### PATCH

Allocator sekarang memblokir allocation ulang untuk component yang memenuhi kondisi:

- `component_type === service_store_stock_part`
- component sudah pernah direfund
- component sudah memiliki inventory reversal movement
- tidak ada deliberate new stock-out/reissue flow

Jika payment hanya menarget component yang sudah blocked, allocator gagal lewat existing `DomainException`, lalu handler mengubahnya menjadi `Result::failure()`.

### TEST

Matrix test memastikan invariant berikut:

- tidak membuat `customer_payments` baru
- tidak membuat `payment_component_allocations` baru
- tidak membuat `stock_out` baru
- net inventory refunded stock component tetap zero
- payment ulang terhadap refunded reversed stock component gagal

### PROOF

Final invariant untuk issue ini sudah dijaga di write-side:

`service_store_stock_part` yang sudah refunded dan sudah punya `work_item_store_stock_line_reversal` tidak boleh menerima payment allocation ulang secara silent.

Report layer tidak dipatch karena report hanya membaca state. Bug berasal dari state write-side yang sebelumnya bisa tidak konsisten.

## Slice 4 Final Verification - Full Regression Green With Explicit PHP Memory Limit

### FACT

Patch allocator guard untuk issue `#0043 Service Package Component Refund Pay Again Inventory Cash Mismatch` sudah melewati targeted test, payment/refund regression kecil, dan full test suite dengan explicit PHP memory limit.

Full suite default sempat gagal karena PHP memory limit `256MB` habis di dependency ZipStream:

```text
Pest\Exceptions\FatalException
Allowed memory size of 268435456 bytes exhausted
vendor/maennchen/zipstream-php/src/File.php
```

Kegagalan tersebut bukan assertion failure dan tidak menunjuk ke payment allocator. Full suite kemudian diverifikasi ulang dengan memory limit lebih besar.

### DATA FOUND

Verification yang sudah PASS:

```bash
php -l app/Application/Payment/Services/AllocatePaymentAcrossComponents.php
php -l tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
php artisan test tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
php artisan test tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php
php artisan test \
  tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsNotePaymentFeatureTest.php \
  tests/Feature/Payment/RecordCustomerRefundFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php \
  tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php \
  tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
php -d memory_limit=1024M artisan test tests
```

### ROOT CAUSE FINAL

Akar bug final:

`ExistingPaymentComponentTotals` mengurangi allocated component total dengan refunded component total. Perilaku ini membuka kembali room allocation setelah refund.

Untuk component biasa, ini bisa valid.

Untuk `service_store_stock_part` yang sudah memiliki inventory reversal `work_item_store_stock_line_reversal`, membuka room allocation ulang tanpa deliberate new stock-out membuat write-side state tidak konsisten:

- cash/payment bisa naik lagi
- allocation bisa tercatat lagi
- inventory tetap sudah dibalik/refund
- report membaca state mismatch tersebut

### PATCH FINAL

Guard ditambahkan di `AllocatePaymentAcrossComponents`.

Allocator sekarang melewati/memblokir component `service_store_stock_part` yang:

- sudah memiliki refund component allocation
- sudah memiliki inventory reversal movement:
  - `source_type = work_item_store_stock_line_reversal`
  - `source_id = component_ref_id`

Jika payment hanya bisa masuk ke component yang sudah diblokir, allocator gagal sebelum writer dipanggil.

Dengan begitu:

- tidak ada `customer_payments` baru
- tidak ada `payment_component_allocations` baru
- tidak ada silent pay-again terhadap stock component yang sudah refund
- tidak ada perubahan report layer

### TEST

Full regression PASS dengan explicit PHP memory limit.

Default memory limit `256MB` tidak cukup untuk seluruh suite karena ada path ZipStream/export yang boros memory. Itu dicatat sebagai environment/test-run constraint, bukan defect dari patch `#0043`.

### PROOF

Invariant final:

`service_store_stock_part` yang sudah direfund dan sudah memiliki inventory reversal tidak boleh menerima payment allocation ulang secara silent tanpa deliberate new stock-out/reissue flow.

Issue `#0043` untuk slice ini dinyatakan fixed pada write-side allocation guard.
