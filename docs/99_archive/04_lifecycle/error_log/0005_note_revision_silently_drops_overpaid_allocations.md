# 005 - Note revision silently drops overpaid allocations

## Status

Fixed and verified.

## Severity

High.

## Source

Audit report #005: Note revision silently drops overpaid allocations.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 003-refunded-revised-notes-are-misclassified-as-underpaid.md
- 004-refunded-work-items-survive-revisions-and-inflate-stock.md

### Jenis Keterkaitan

Direct relationship with #003.

Indirect workflow relationship with #004.

### Alasan

Laporan #005 dan #003 sama-sama berada pada area note revision payment replay dan menyentuh:

- app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
- payment allocation rebuild
- payment/refund/reporting consistency after note revision

Namun failure mode berbeda.

- #003 membahas historical refund pada revised note yang menyebabkan paid total undercount karena refund double-subtracted.
- #005 membahas downward revision yang menyebabkan captured payment dipotong saat replay, sehingga overpaid excess hilang dari allocation/report/refund path.

Laporan #005 berkaitan tidak langsung dengan #004 karena sama-sama berada pada note revision lifecycle, tetapi #004 fokus pada stale work_items dan duplicate inventory reversal, bukan payment replay.

Karena root cause dan dampak teknis berbeda, laporan #005 dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #005.

Alasan update:

- Laporan menunjukkan note revision dapat silently drop overpaid allocation.
- Patch awal diklaim sudah diterapkan pada NoteReplacementPaymentAllocationReconciler::rebuild().
- Verification masih gap karena hanya php -l yang dilaporkan pass.
- Sesi lanjutan menemukan status dokumen tidak cocok dengan source lokal.

### Update 2

Final verification untuk laporan #005.

Alasan update:

- Source lokal masih memiliki truncating replay behavior.
- NoteReplacementPaymentAllocationReconciler::rebuild() masih memakai replayAmount = min($amount, $remainingComponentAmount).
- Focused characterization test dibuat untuk downward note replacement dengan old payment lebih besar dari revised total.
- Red test membuktikan current behavior masih silent cap.
- Patch minimal diterapkan ulang agar allocator menerima full captured amount.
- Existing regression tests yang sebelumnya mengunci behavior caps_old_payment diperbarui agar mengharapkan reject + rollback.
- Focused, related, dan blast-radius tests sudah pass.

## Ringkasan Indonesia

Bug terjadi saat note yang sudah memiliki payment allocation direvisi turun ke total lebih kecil.

Flow revision:

1. Existing allocated payment amounts dicapture.
2. Existing component allocations dihapus.
3. Work item/note rows diganti.
4. Note total diperbarui.
5. Payment allocations dibangun ulang melalui NoteReplacementPaymentAllocationReconciler::rebuild().

Sebelum patch, rebuild() memakai:

replayAmount = min($amount, $remainingComponentAmount)

Artinya jika payment lama lebih besar dari total komponen note baru, hanya sebagian payment yang direplay ke payment_component_allocations.

Contoh:

- Payment lama: 100.000
- Note setelah revisi turun: 60.000
- Rebuild hanya membuat allocation: 60.000
- Sisa 40.000 tetap ada di customer_payments, tetapi tidak lagi terhubung ke note lewat payment_component_allocations

Akibatnya, 40.000 menjadi hidden overpaid excess.

## Dampak

Dampak utama:

- customer_payments.amount_rupiah tetap 100.000
- payment_component_allocations hanya mencatat 60.000
- sisa 40.000 tidak muncul sebagai allocated payment pada note
- refund option tidak dapat memilih excess tersebut lewat normal flow
- cash ledger/reporting berbasis component allocation dapat underreport payment
- transaction summary bisa salah
- overpayment tidak ditolak, tidak direfund, dan tidak dicatat eksplisit

Ini financial-integrity issue.

Severity High tepat karena payment/refund/reporting accuracy adalah core asset POS/back-office. Tidak otomatis Critical karena butuh authenticated cashier/admin, note yang sudah punya payment, dan downward revision workflow.

## Jalur Risiko

Authenticated cashier/admin melakukan downward note revision.

Workflow risiko:

1. User login sebagai admin/kasir dengan akses transaksi.
2. User membuka note yang sudah paid/partially paid.
3. User submit PATCH workspace revision dengan total baru lebih rendah.
4. CreateNoteRevisionHandler menjalankan revision dalam DB transaction.
5. ApplyNoteRevisionAsActiveReplacement capture allocated payment lama.
6. Existing allocations dihapus.
7. Replacement rows dipersist.
8. Note total diperbarui.
9. NoteReplacementPaymentAllocationReconciler::rebuild() mereplay payment.
10. Logic lama memotong replay amount ke remaining component amount.
11. Excess payment tidak dialokasikan ulang.
12. Refund/reporting yang bergantung pada payment_component_allocations tidak melihat excess tersebut.

## Root Cause

Root cause:

NoteReplacementPaymentAllocationReconciler::rebuild() mencoba menjaga replay agar tidak melebihi total komponen baru dengan cara memotong payment amount.

Masalahnya, memotong payment secara diam-diam menciptakan state finansial yang tidak eksplisit.

Untuk sistem finansial, overpaid/downward revision harus salah satu dari:

1. ditolak dan rollback
2. dipertahankan sebagai explicit overpaid/customer credit/refund due
3. diarahkan ke explicit refund/correction flow

Yang tidak boleh terjadi:

- customer payment tetap penuh
- allocation hanya sebagian
- excess hilang dari refund/reporting path

## Patch Summary

Patch minimal diterapkan pada:

app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php

Perubahan:

- hapus remainingComponentAmount total tracking dari rebuild()
- hapus remainingComponentAmount guard dari loop skip condition
- hapus replayAmount = min($amount, $remainingComponentAmount)
- allocator sekarang menerima full captured amount melalui Money::fromInt($amount)
- hapus remainingComponentAmount decrement setelah allocation
- jika note baru tidak bisa menyerap payment lama, allocator akan throw DomainException
- enclosing transaction rollback mencegah inconsistent financial state

Efek patch:

- downward revision yang tidak bisa menyerap payment lama tidak lagi silently commit
- fail-fast behavior kembali aktif
- overpaid allocation tidak hilang diam-diam dari payment_component_allocations
- current safe behavior adalah reject + rollback sampai explicit overpaid/customer credit/refund due model tersedia

## Scope In

- NoteReplacementPaymentAllocationReconciler::rebuild()
- Payment allocation replay during note revision
- Fail-fast behavior for under-allocatable revised notes
- Prevention of silent overpaid excess loss
- Regression tests for product-only and service-store-stock downward overpaid replacement

## Scope Out

- Explicit overpaid/customer credit/refund due feature
- Refund option UI redesign
- Reporting query changes
- Settlement model redesign
- Historical refund double-subtraction from #003
- Inventory reversal issue from #004
- Seeder/security work

## Files Changed

- app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
- tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php
- tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php
- tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php
- docs/04_lifecycle/error_log/0005_note_revision_silently_drops_overpaid_allocations.md

## Proof Dari Patch Session

Initial red proof:

- Command: php artisan test --filter=NoteReplacementOverpaidAllocationReplayFeatureTest
- Result: 1 failed, 1 assertion
- Failure: Downward replacement must reject overpaid replay instead of silently capping old payment.
- Failure line: tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php:82
- Meaning: current source still silently capped old payment instead of rejecting downward overpaid replay.

Focused green proof:

- Command: php artisan test --filter=NoteReplacementOverpaidAllocationReplayFeatureTest
- Result: 1 passed, 8 assertions
- Source marker proof:
  - allocator->allocate is called in rebuild()
  - Money::fromInt($amount) is passed to allocator
  - no remainingComponentAmount marker
  - no replayAmount marker

Conflicting product-only regression proof:

- Command: php artisan test --filter=CashierProductReplacementBackdatedPriceFinanceFeatureTest
- Initial result after source patch: 1 failed, 1 passed
- Failure reason: old test expected redirect to note show, but current correct behavior redirected to workspace edit with error Payment tidak bisa dialokasikan penuh ke komponen note.
- Final result after test expectation update: 2 passed, 26 assertions

Limited related suite proof:

- Command:
  - php artisan test tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php tests/Feature/Note/CashierClosedReplacementOutstandingPaymentFeatureTest.php tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
- Result: 7 passed, 66 assertions

Conflicting service-store-stock regression proof:

- Initial blast-radius result: 1 failed, 158 passed, 923 assertions
- Failure: CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest expected redirect to note show, but current correct behavior redirected to workspace edit with error Payment tidak bisa dialokasikan penuh ke komponen note.
- Command after expectation update:
  - php artisan test tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php
- Result: 1 passed, 18 assertions

Blast-radius proof:

- Command: php artisan test tests/Feature/Note tests/Feature/Payment
- Result: 159 passed, 935 assertions
- Duration: 14.60s

## Verification Gap

No verification gap remains for the minimum safe behavior of #005.

Verified behavior:

- downward replacement with old allocated payment greater than revised payable components is rejected
- transaction rolls back
- original note data remains
- original work item data remains
- original payment_component_allocations remain
- no partial capped allocation is persisted
- existing product-only replacement regression updated
- existing service-store-stock replacement regression updated
- Feature Note + Payment blast-radius passes

Remaining future product gap:

- explicit overpaid/customer credit/refund due model is still out of scope
- until that model exists, reject + rollback is the safe behavior

## Recommended Follow-up

Future settlement feature, outside this patch:

- design explicit overpaid/customer credit/refund due state
- decide UI entry point for downward revision where old paid amount exceeds revised total
- preserve immutable payment/refund/inventory history
- make current projection separate from historical ledger
- add tests for explicit refund/customer credit flow before allowing downward overpaid revision to commit

Do not reintroduce silent cap behavior.

## Kesimpulan

Laporan #005 valid sebagai High severity financial-integrity issue.

Bug sebelumnya memotong replay payment secara diam-diam saat downward note revision. Ini membuat sebagian real customer payment hilang dari allocation-based refund/reporting path.

Patch final sudah terverifikasi: jangan truncate payment replay; replay full amount dan biarkan allocator menolak revised note yang tidak bisa menyerap payment lama. Current safe behavior adalah reject + rollback.

## Related Revision Price Invariant Finding From Error Log 006

### Related Error Log

- 006-client-controlled-price-basis-bypasses-minimum-price-checks.md

### Update

Update 2.

### Reason

Laporan audit lanjutan menemukan issue terpisah dengan severity High pada note workspace revision flow yang sama.
