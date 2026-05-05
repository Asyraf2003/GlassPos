# 005 - Note revision silently drops overpaid allocations

## Status

Patched, with verification gap.

Patch supplied and syntax check passed, but no focused feature/regression test was reported as passing.

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
- Patch sudah diterapkan pada NoteReplacementPaymentAllocationReconciler::rebuild().
- Patch menghapus truncating replay behavior dan mengembalikan fail-fast behavior lewat allocator.
- Verification masih gap karena hanya php -l yang dilaporkan pass.

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

- hapus remainingComponentAmount guard dari loop skip condition
- hapus replayAmount = min($amount, $remainingComponentAmount)
- allocator sekarang menerima full captured amount
- hapus remainingComponentAmount decrement setelah allocation
- jika note baru tidak bisa menyerap payment lama, allocator akan throw DomainException
- enclosing transaction seharusnya rollback, mencegah inconsistent financial state

Efek patch:

- downward revision yang tidak bisa menyerap payment lama tidak lagi silently commit
- fail-fast behavior kembali aktif
- overpaid allocation tidak hilang diam-diam dari payment_component_allocations

## Scope In

- NoteReplacementPaymentAllocationReconciler::rebuild()
- Payment allocation replay during note revision
- Fail-fast behavior for under-allocatable revised notes
- Prevention of silent overpaid excess loss

## Scope Out

- Explicit overpaid/customer credit/refund due feature
- Refund option UI redesign
- Reporting query changes
- Full note revision E2E test
- Settlement model redesign
- Historical refund double-subtraction from #003
- Inventory reversal issue from #004

## Proof Dari Patch Session

User reported:

- vulnerability still existed in HEAD
- minimal remediation applied in NoteReplacementPaymentAllocationReconciler::rebuild()
- patch scoped to single file/method
- commit created with message:
  Fix payment replay to reject under-allocatable note revisions

Testing reported:

- php -l app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php

Changed file:

app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php

Reported diff size:

+2
-6

## Verification Gap

Only PHP syntax validation was reported.

No passing feature/regression test was reported for:

- 100.000 payment revised down to 60.000
- expected DomainException / rollback
- no partial allocation persisted
- customer_payments and payment_component_allocations consistency
- refund option still accurate after failed revision
- reporting not undercounting committed payment data

Therefore this patch should be treated as source-fixed but not fully behavior-verified.

## Recommended Follow-up

Minimum regression test:

Scenario:

- original note total: 100.000
- customer payment: 100.000
- payment_component_allocations: 100.000
- revised note total: 60.000
- run revision application
- expect DomainException
- expect transaction rollback
- expect original allocation remains intact
- expect no state where customer_payments = 100.000 and payment_component_allocations = 60.000

Recommended command later:

php artisan test --filter=NoteReplacementPaymentAllocationReconciler

If no test exists, add one before treating this class as safe.

## Kesimpulan

Laporan #005 valid sebagai High severity financial-integrity issue.

Bug sebelumnya memotong replay payment secara diam-diam saat downward note revision. Ini membuat sebagian real customer payment hilang dari allocation-based refund/reporting path.

Patch minimal sudah mengarah benar: jangan truncate payment replay; replay full amount dan biarkan allocator menolak revised note yang tidak bisa menyerap payment lama. Namun patch masih membutuhkan behavior test karena php -l hanya membuktikan file valid secara sintaks, bukan benar secara akuntansi.

## Related Revision Price Invariant Finding From Error Log 006

### Related Error Log

- 006-client-controlled-price-basis-bypasses-minimum-price-checks.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity issue in the same note workspace revision flow.

This is not the same root cause as #005.

- #005 is about payment allocation replay silently dropping overpaid excess during downward revision.
- #006 is about client-controlled price_basis bypassing MinSellingPricePolicy during store-stock line materialization.

Future changes to note revision must verify both payment replay integrity and price floor enforcement.

## Related Payment Allocation Finding From Error Log 008

### Related Error Log

- 008-legacy-paid-notes-can-be-paid-again.md

### Update

Update 3.

### Reason

A later audit report found a separate High severity payment allocation issue.

This is not the same root cause as #005.

- #005 is about note revision replay silently truncating captured payments and hiding overpaid excess.
- #008 is about selected-row payment validation ignoring legacy payment_allocations and allowing duplicate payment for legacy-paid notes.

Both findings show that payment validation must use the correct settlement basis and must not silently ignore existing payment state.

## Related Closed-Note Authorization Finding From Error Log 009

### Related Error Log

- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md

### Update

Update 4.

### Reason

A later audit report found a separate High severity authorization issue in the note revision flow.

This is not the same root cause as #005.

- #005 is about payment replay silently dropping overpaid allocation during downward revision.
- #009 is about cashier access control allowing closed-note workspace PATCH mutation.

Both findings show that note revision must be guarded both financially and authorization-wise.

## Related Concurrency Finding From Error Log 010

### Related Error Log

- 010-revision-reallocation-can-lose-concurrent-payments.md

### Update

Update 5.

### Reason

A later audit report found a separate High severity issue in the note revision payment allocation area.

This is not the same root cause as #005.

- #005 is about downward revision replay truncating payment amounts and hiding overpaid excess.
- #010 is about concurrent payment allocation being lost during revision capture/delete/rebuild due to missing serialization.

Both findings affect payment allocation rebuild during note revision and should be considered together before changing NoteReplacementPaymentAllocationReconciler or active replacement flow.

## Related Settled-Note Revision Guard Finding From Error Log 011

### Related Error Log

- 011-cashier-revision-path-mutates-settled-note-state.md

### Update

Update 6.

### Reason

A later audit report found a separate High severity issue in the note revision financial mutation path.

This is not the same root cause as #005.

- #005 is about payment allocation replay silently dropping overpaid excess during downward revision.
- #011 is about missing payment-derived editability guard before revision mutates settled note state.

Both findings show that note revision must be protected by both correct payment replay semantics and strict editability policy.
