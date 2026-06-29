# 004 - Refunded work items survive revisions and inflate stock

## Status

Fixed with proof.

Fix verified pada local repo dan sudah dipush manual oleh owner.

Verified HEAD:

78456d17 (HEAD -> main, origin/main, origin/HEAD) commit 1706

Scope fixed:

- duplicate inventory reversal idempotency
- stale refunded historical work item exclusion from current operational workspace rows
- current revision row identity aligned with persisted replacement work_item IDs
- selected refund planning rejects old historical refunded work item after revision while accepting the replacement row

Important limitation:

Reporting queries that read work_items directly were not part of this verification slice. They remain a separate audit target if reporting mismatch is suspected.

## Severity

High.

## Source

Audit report #004: Refunded work items survive revisions and inflate stock.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Jenis Keterkaitan

Direct workflow relationship, separate root cause.

### Alasan

Laporan #004 dan #003 sama-sama muncul pada lifecycle:

- note refund
- note revision / workspace replacement
- historical component state
- authenticated admin/kasir mutation route

Namun root cause dan dampaknya berbeda.

- #003 membahas settlement/payment classification: revised note dengan historical refund menjadi underpaid/open karena refund double-subtracted.
- #004 membahas inventory integrity: refunded work item yang diproteksi dari delete tetap hidup sebagai active row, lalu inventory reversal bisa diproses berulang dan menaikkan stock/costing tanpa return nyata.

Karena area file, failure mode, dan patch berbeda, laporan #004 harus dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #004.

Alasan update:

- Laporan menunjukkan stale refunded work_items dapat tetap attached ke active note setelah revision.
- Stale store-stock lines dapat direversal berulang pada revision berikutnya.
- Patch minimal diterapkan di reversal path agar idempotent per reverse source.
- Verification masih gap karena feature test gagal dijalankan akibat missing vendor/autoload.php.

### Update 2

Status dinaikkan menjadi fixed with proof untuk scope Note/Payment/current operational projection.

Alasan update:

- Duplicate reversal idempotency guard sudah diuji green.
- Root current-vs-historical leak sudah dibuat red melalui characterization test.
- Workspace panel/current operational rows dipindahkan agar membaca current revision lines, bukan semua historical work_items.
- Revision snapshot creation dipindahkan setelah replacement work_items dipersist, sehingga note_revision_lines.work_item_root_id memakai persisted current work_items.id, bukan transient UUID.
- Targeted 004 verification dan broader Note/Payment verification sudah pass.

Proof:

- HEAD: 78456d17 (HEAD -> main, origin/main, origin/HEAD) commit 1706
- Targeted 004 verification: 3 passed, 25 assertions
- Broader Note/Payment verification: 159 passed, 940 assertions

## Ringkasan Indonesia

Bug terjadi pada kombinasi refund-referenced work item dan note revision.

Commit yang dilaporkan mengubah delete behavior agar work item yang direferensikan oleh refund_component_allocations tidak dihapus oleh WorkItemDeletesTrait.

Masalahnya, revision flow melakukan urutan berikut:

1. reverse inventory untuk semua current work items
2. delete old work items
3. create replacement rows
4. update note total
5. rebuild payment allocations/projections

Karena WorkItemDeletesTrait menjaga work item yang punya refund_component_allocations, work item lama tidak ikut terhapus. Jika work item tersebut memiliki store-stock line, row lama tetap attached ke note aktif.

Pada revision berikutnya, note reader/reversal service dapat membaca stale work item itu lagi. ReverseIssuedInventoryByNoteService lalu mengirim store-stock line lama ke ReverseIssuedInventoryOperation lagi.

Sebelum patch, ReverseIssuedInventoryOperation tidak punya idempotency check untuk reversal yang sama. Akibatnya source stock_out yang sama bisa dibuatkan stock_in reversal berkali-kali.

## Contoh Dampak

Skenario ringkas:

- Note memiliki work item store-stock.
- Work item tersebut menghasilkan original stock_out.
- Work item direfund sehingga ada refund_component_allocations yang menunjuk work_item_id itu.
- Note direvisi.
- WorkItemDeletesTrait tidak menghapus work item tersebut karena dianggap protected.
- Revision berikutnya membaca stale work item yang sama.
- ReverseIssuedInventoryOperation membuat stock_in reversal lagi untuk source stock_out yang sama.
- qty_on_hand dan inventory value naik berulang tanpa return barang nyata.

Dampak dari PoC pada laporan:

- qty naik dari 7 ke 10
- revision berikutnya naik lagi dari 10 ke 13
- duplicate transaction_workspace_updated reversals dibuat untuk stock line yang sama

## Jalur Risiko

Authenticated cashier/admin menggunakan flow normal:

1. User memiliki authenticated session sebagai admin/kasir.
2. User mengakses note yang memiliki refunded store-stock work item.
3. User melakukan workspace revision.
4. UpdateTransactionWorkspaceWorkItemPersister menjalankan ReverseIssuedInventoryByNoteService sebelum delete old work items.
5. WorkItemDeletesTrait tidak menghapus work item yang direferensikan refund_component_allocations.
6. Stale work item dan stock line tetap attached ke active note.
7. Revision berikutnya membaca stale row yang sama.
8. ReverseIssuedInventoryOperation membuat duplicate stock_in karena tidak ada idempotency.
9. Inventory qty_on_hand dan costing/inventory_value_rupiah meningkat palsu.

## Dampak Bisnis

Ini adalah inventory-integrity dan financial-integrity issue.

Dampak utama:

- qty_on_hand bisa naik tanpa stock return nyata
- inventory value bisa inflated
- inventory movements menjadi misleading
- average cost/costing bisa terdampak
- laporan stock dan laporan finansial bisa tidak akurat
- note aktif bisa berisi old + new work items yang tidak sesuai dengan notes.total_rupiah
- standard revision workflow dapat dipakai untuk mengulang efeknya

Severity High tepat karena inventory dan valuation adalah source of truth bisnis. Tidak otomatis Critical karena membutuhkan authenticated admin/kasir dan prerequisite refunded store-stock note.

## Root Cause

Root cause gabungan:

1. WorkItemDeletesTrait menjaga refund-referenced work_items agar tidak dihapus.
2. Revision flow tetap memperlakukan semua loaded active work items sebagai kandidat reversal.
3. Stale refunded work_items tetap bisa direload sebagai active note rows.
4. ReverseIssuedInventoryOperation tidak idempotent terhadap reversal source yang sama.
5. Tidak ada unique constraint atau existing-reversal check untuk mencegah duplicate stock_in reversal.

## Patch Summary

Patch final terdiri dari dua lapis.

### 1. Duplicate inventory reversal guard

Patch diterapkan pada:

app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php

Perubahan:

- normalisasi sourceType, sourceId, dan reverseSourceType dilakukan sekali
- sebelum reversal, service mengecek existing movement dengan:
  - source_type = normalizedReverseSourceType
  - source_id = normalizedSourceId
- jika existing positive reversal sudah ada, method return [] lebih awal
- first-time reversal tetap berjalan seperti sebelumnya
- created reverse movement memakai normalized reverse source type dan normalized source id

Test ditambahkan/diperluas pada:

tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

Coverage:

- first call membuat reversal seperti expected
- second call untuk source yang sama return 0 reversal
- inventory qty_on_hand tetap sama setelah second call
- product_inventory_costing tetap sama setelah second call

### 2. Current-vs-historical note boundary

Patch diterapkan pada:

app/Application/Note/Services/NoteWorkspacePanelDataBuilder.php
app/Application/Note/UseCases/CreateNoteRevisionHandler.php
tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php

Perubahan:

- NoteWorkspacePanelDataBuilder sekarang membangun current operational rows dari current revision lines.
- Workspace panel tidak lagi memakai semua note->workItems() sebagai current rows.
- CreateNoteRevisionHandler sekarang menerapkan replacement terlebih dahulu.
- Revision snapshot dibuat setelah replacement work_items dipersist.
- note_revision_lines.work_item_root_id sekarang mengacu ke persisted replacement work_items.id.
- Historical refund-referenced work item tetap preserved sebagai ledger anchor.
- Historical refunded work item tidak lagi muncul sebagai current operational workspace row dalam tested flow.
- Selected refund planning menolak old historical refunded work item dan tetap menerima replacement work item.

## Scope In

- Duplicate inventory reversal prevention.
- Idempotency in ReverseIssuedInventoryOperation.
- Inventory qty_on_hand inflation prevention for repeated reversal calls.
- Inventory costing/value inflation prevention for repeated reversal calls.
- Current operational workspace rows must read current revision lines.
- Historical refunded work item must remain preserved as ledger/payment/refund/inventory anchor.
- Historical refunded work item must not be exposed as current operational row after revision.
- Replacement work item must remain visible and valid as current operational row after revision.
- Current revision line identity must align with persisted replacement work_items.id.

## Scope Out

- Deleting refund-referenced historical work_items.
- Rewriting payment/refund/inventory history.
- Schema-level note_current_lines projection table.
- Schema-level uniqueness constraint for reversal movements.
- Full Reporting suite verification.
- Direct reporting query rewrite for reports that intentionally or accidentally read work_items directly.
- Seeder/security issue 002.
- Settlement issues outside the tested 004 current/historical boundary.

## Residual Risk

Known residual risks after this fix:

1. Reporting queries that read work_items directly were not verified in this slice.
2. If a report treats all historical work_items as current business rows, it may still need a separate current-vs-history reporting audit.
3. DB-level uniqueness for reversal movements is still not added; idempotency is currently enforced in application service logic.
4. Concurrent revision behavior was not separately stress-tested.
5. Future code must not reintroduce note->workItems() as a current operational source for revision-aware UI/payment/refund flows without an explicit current boundary.

These residuals do not invalidate the fixed status for the tested 004 Note/Payment/current operational flow, but they remain follow-up audit candidates.

## Testing Reported

### Earlier patch environment

Command attempted:

php artisan test tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

Earlier result:

Failed in the patch environment because vendor/autoload.php was missing.

### Verified local repo proof

Verified HEAD:

78456d17 (HEAD -> main, origin/main, origin/HEAD) commit 1706

Targeted 004 verification:

php artisan test tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php

Result:

3 passed, 25 assertions

Broader Note/Payment verification:

php artisan test tests/Feature/Note tests/Feature/Payment

Result:

159 passed, 940 assertions

## Files Changed By Patch

app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php
tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php
app/Application/Note/Services/NoteWorkspacePanelDataBuilder.php
app/Application/Note/UseCases/CreateNoteRevisionHandler.php
tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php

Verified commit context:

78456d17 commit 1706

## Recommended Follow-up

Optional follow-up audit, not required to consider this 004 Note/Payment flow fixed:

1. Run Reporting feature tests if available.
2. Audit reporting queries that join work_items directly.
3. Decide whether reporting must read current revision lines, historical ledger rows, or both depending on report semantics.
4. Consider DB-level uniqueness/idempotency guard for reverse inventory movements:
   - source_type
   - source_id
   - movement_type
   - reverse semantic source
5. Consider concurrency test for repeated revision requests targeting the same refunded store-stock source.

Minimum commands if reporting scope is opened later:

php artisan test tests/Feature/Reporting

If reporting tests do not exist, inspect reporting query semantics before writing new tests. Jangan asal bikin test yang cuma menghibur ego CI.

## Kesimpulan

Laporan #004 valid sebagai High severity inventory/financial integrity issue.

Status sekarang fixed with proof untuk tested Note/Payment/current operational flow.

Fix yang terbukti:

- duplicate stock_in reversal untuk source yang sama dicegah oleh idempotency guard
- stale refunded historical work item tetap preserved sebagai ledger anchor
- stale refunded historical work item tidak lagi muncul sebagai current operational workspace row setelah revision
- replacement work item tetap muncul sebagai current row
- current revision line identity sekarang memakai persisted replacement work_items.id
- broader Note/Payment suite pass

Proof:

- HEAD: 78456d17
- Targeted 004 verification: 3 passed, 25 assertions
- Broader Note/Payment verification: 159 passed, 940 assertions

Catatan batas:

Reporting yang membaca work_items langsung belum diverifikasi dalam scope ini. Jika ada mismatch laporan, buka audit/reporting scope terpisah, bukan reopen 004 tanpa proof baru.

## Related Note Revision Finding From Error Log 005

### Related Error Log

- 005-note-revision-silently-drops-overpaid-allocations.md

### Update

Update 2.

### Reason

Laporan audit lanjutan menemukan issue terpisah di lifecycle note revision.

Ini bukan root cause yang sama dengan #004.

- #004 is about stale refunded work_items surviving revision deletion and causing duplicate inventory reversal.
- #005 is about payment allocation replay silently dropping overpaid excess during downward note revision.

Keduanya harus dipertimbangkan ketika mengaudit note revision karena satu memengaruhi inventory integrity dan yang lain memengaruhi financial/payment integrity.

## Related Store-Stock Revision Finding From Error Log 006

### Related Error Log

- 006-client-controlled-price-basis-bypasses-minimum-price-checks.md

### Update

Update 3.

### Reason

Laporan audit lanjutan menemukan issue terpisah yang memengaruhi store-stock lines selama note revision.

Ini bukan root cause yang sama dengan #004.

- #004 is about duplicate inventory reversal caused by stale refunded work_items.
- #006 is about underpriced store-stock line creation caused by client-controlled price_basis bypassing minimum price checks.

Keduanya memengaruhi store-stock/inventory integrity selama note revision dan harus dipertimbangkan bersama dalam audit revision-flow berikutnya.

## Related Closed-Note Authorization Finding From Error Log 009

### Related Error Log

- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md

### Update

Update 4.

### Reason

Laporan audit lanjutan menemukan issue authorization terpisah dengan severity High pada route note workspace revision.

Ini bukan root cause yang sama dengan #004.

- #004 is about stale refunded work_items causing duplicate inventory reversal.
- #009 is about cashier PATCH workspace update being allowed for closed notes because the route used view-only access instead of mutation guard.

Both findings affect note revision and can impact work items, payment allocations, and inventory state.

## Related Inactive Row Flow Finding From Error Log 012

### Related Error Log

- 012-canceled-note-rows-re-enter-payment-flows.md

### Update

Update 5.

### Reason

Laporan audit lanjutan menemukan issue terpisah yang disebabkan inactive rows masuk kembali ke operational flows.

Ini bukan root cause yang sama dengan #004.

- #004 is about refunded/stale work_items surviving revision deletion and causing duplicate inventory reversal.
- #012 is about canceled work_items being rehydrated into note->workItems() and entering payment/status correction flows.

Kedua temuan menunjukkan bahwa historical/inactive rows tidak boleh diperlakukan sebagai active operational rows tanpa filtering eksplisit.

## Related Refund Row Finalization Finding From Error Log 013

### Related Error Log

- 013-forged-row-refund-can-auto-finalize-unpaid-notes.md

### Update

Update 6.

### Reason

Laporan audit lanjutan menemukan issue terpisah pada refund/cancellation flow yang dapat memengaruhi konsistensi financial dan berpotensi inventory.

Ini bukan root cause yang sama dengan #004.

- #004 is about refunded/stale work_items surviving revision deletion and causing duplicate inventory reversal.
- #013 is about forged selected-row refund canceling unpaid rows and auto-finalizing a zero-total note as refunded without recorded refund allocations.

Keduanya harus dipertimbangkan ketika mengaudit lifecycle row terkait refund dan side effect inventory/accounting.
