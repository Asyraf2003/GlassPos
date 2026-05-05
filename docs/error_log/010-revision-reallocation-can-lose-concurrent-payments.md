# 010 - Revision reallocation can lose concurrent payments

## Status

Patched, with concurrency verification gap.

Patch supplied and PHP syntax checks passed, but the focused PHPUnit command could not run because vendor/bin/phpunit was not available in the patch environment.

## Severity

High.

## Source

Audit report #010: Revision reallocation can lose concurrent payments.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 005-note-revision-silently-drops-overpaid-allocations.md
- 008-legacy-paid-notes-can-be-paid-again.md
- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md

### Jenis Keterkaitan

Direct payment/revision allocation relationship with #005 and #008.

Direct workspace revision relationship with #009.

### Alasan

Laporan #010 berada pada area note revision + payment allocation integrity.

- #005 membahas payment replay saat note revision yang dapat silently drop overpaid allocation.
- #008 membahas selected-row payment validation yang mengabaikan legacy payment allocations.
- #009 membahas cashier workspace update authorization untuk closed notes.
- #010 membahas race condition antara note revision allocation rebuild dan concurrent payment allocation.

Root cause #010 berbeda dari semuanya: ini bukan salah basis settlement, bukan legacy fallback, dan bukan authorization. Ini lost-update race akibat snapshot allocation yang tidak dikunci dan broad delete by note_id.

Karena root cause, exploit condition, dan patch berbeda, laporan #010 dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #010.

Alasan update:

- Laporan menunjukkan concurrent payment bisa hilang dari payment_component_allocations saat note revision melakukan delete/rebuild.
- Patch menambahkan row locking pada note read untuk dua competing transaction paths.
- Verification masih gap karena PHPUnit tidak tersedia di patch environment.

## Ringkasan Indonesia

Bug terjadi pada flow active note replacement saat note revision.

Flow bermasalah:

1. Revision membaca existing payment_component_allocations.
2. Revision menyimpan hasilnya sebagai captured payment amounts.
3. Revision kemudian menghapus semua payment_component_allocations untuk note tersebut.
4. Revision membangun ulang allocations hanya dari payment IDs yang tercapture di awal.

Masalahnya, pembacaan awal adalah plain SELECT tanpa lock.

Jika ada request payment lain berjalan bersamaan:

- payment request membuat customer_payments row
- payment request membuat payment_component_allocations row
- row allocation itu commit setelah revision capture
- tetapi sebelum revision deleteByNoteId()

maka revision akan menghapus allocation baru tersebut. Karena payment ID baru tidak ada di captured snapshot, allocation itu tidak ikut dibuat ulang.

Akibatnya customer_payments tetap ada, tetapi allocation-nya hilang.

## Dampak

Dampak utama:

- customer payment tercatat tetapi component allocation hilang
- note paid total bisa understated
- payment bisa terlihat unallocated
- settlement/projection/reporting bisa rusak
- pembayaran yang valid bisa hilang dari allocation source of truth
- audit finansial menjadi tidak konsisten

Ini financial-integrity issue.

Severity High tepat karena payment allocation integrity adalah core business/security asset untuk POS/back-office. Tidak Critical karena butuh authenticated cashier/admin, same-note concurrent request timing, dan tidak menyebabkan unauthenticated compromise, RCE, account takeover, secret leak, atau cross-tenant impact.

## Jalur Risiko

Workflow risiko:

1. User authenticated sebagai cashier/admin.
2. User punya akses ke mutable note.
3. Request A melakukan PATCH workspace revision.
4. Request A masuk ApplyNoteRevisionAsActiveReplacement.
5. Request A capture allocation snapshot tanpa lock.
6. Request B melakukan POST payment untuk note yang sama.
7. Request B membuat customer_payments dan payment_component_allocations.
8. Request A menjalankan deleteByNoteId(note_id).
9. Allocation dari request B ikut terhapus.
10. Request A rebuild hanya payment IDs dari snapshot lama.
11. customer_payments dari request B tetap ada, tetapi component allocation hilang.

## Root Cause

Root cause:

Multi-step mutation terhadap same-note payment allocation tidak diserialisasi.

Critical sequence:

- captureAllocatedAmounts()
- deleteByNoteId()
- rebuild()

Sequence ini memakai snapshot lama dan broad delete. Tanpa lock/serialization, concurrent insert dari payment request bisa masuk di antara capture dan delete.

Masalah teknis spesifik:

1. DatabasePaymentComponentAllocationReaderAdapter::listByNoteId() memakai plain SELECT.
2. DatabasePaymentComponentAllocationWriterAdapter::deleteByNoteId() menghapus semua rows berdasarkan note_id.
3. rebuild() hanya merecreate allocations dari captured paymentAmounts.
4. RecordAndAllocateNotePaymentOperation bisa membuat customer_payments + payment_component_allocations secara terpisah untuk note yang sama.
5. Tidak ada lockForUpdate/sharedLock/advisory lock di path lama.

## Patch Summary

Patch menerapkan minimal serialization fix, bukan mengubah business allocation logic.

Changed files:

app/Ports/Out/Note/NoteReaderPort.php
app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php
app/Application/Note/UseCases/CreateNoteRevisionHandler.php
app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php

Perubahan utama:

1. NoteReaderPort ditambah method:

getByIdForUpdate(string $id): ?Note

2. DatabaseNoteReaderAdapter ditambah internal loader:

getByIdInternal(string $id, bool $forUpdate): ?Note

3. Jika forUpdate = true:

- notes query memakai lockForUpdate()
- related work_items query juga memakai lockForUpdate()

4. CreateNoteRevisionHandler sekarang membaca root note dengan:

getByIdForUpdate()

5. RecordAndAllocateNotePaymentOperation sekarang membaca target note dengan:

getByIdForUpdate()

Efek patch:

- revision flow dan payment flow untuk note yang sama sama-sama mengambil note row lock
- concurrent mutation pada note yang sama harus serialize
- payment insert tidak boleh interleave secara unsafe dengan revision delete/rebuild
- business allocation behavior tetap dipertahankan

## Scope In

- Same-note serialization between revision and payment recording.
- Note row lock introduction.
- Work item row lock during locked note load.
- Lost-update prevention for payment_component_allocations.
- Transactional mutation paths:
  - CreateNoteRevisionHandler
  - RecordAndAllocateNotePaymentOperation

## Scope Out

- Redesign of capture/delete/rebuild allocation model.
- DB-level unique constraint or advisory lock.
- Full HTTP concurrency E2E test.
- Locking every possible note mutation route.
- Legacy payment issue from #008.
- Downward revision overpaid issue from #005.
- Closed-note authorization issue from #009.
- Production DB isolation verification.

## Proof Dari Patch Session

User reported syntax checks passed:

- php -l app/Ports/Out/Note/NoteReaderPort.php
- php -l app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php
- php -l app/Application/Note/UseCases/CreateNoteRevisionHandler.php
- php -l app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php

Testing attempted:

./vendor/bin/phpunit --filter RecordAndAllocateNotePayment --testsuite Unit

Result:

Warning/failure because ./vendor/bin/phpunit is not available in the environment.

Commit reported:

639cb0c - Serialize note revision/payment flows with row locks

Reported diff size:

+29
-5

## Verification Gap

The patch is source-level plausible, but concurrency behavior is not fully proven by passing tests.

Missing proof:

- concurrent payment and revision serialize correctly
- payment request cannot insert allocation between revision capture and delete
- payment row and allocation row remain consistent after contention
- lockForUpdate is always called inside an active transaction
- all competing same-note mutation paths use the same note lock
- no deadlock or unacceptable lock ordering issue appears under concurrent traffic

Important caveat:

Row lock on notes works only if every competing mutation path follows the same locking protocol. If another payment/allocation/note mutation path bypasses getByIdForUpdate(), this race class can reappear wearing a fake mustache, because bugs apparently enjoy cosplay.

## Recommended Follow-up

Minimum verification commands:

composer install
./vendor/bin/phpunit --filter RecordAndAllocateNotePayment --testsuite Unit

If PHPUnit wrapper is not available:

php artisan test --filter=RecordAndAllocateNotePayment

Recommended additional concurrency test:

1. Start transaction A:
   - revision reads note with getByIdForUpdate()
   - capture allocations
2. Start transaction B:
   - payment tries getByIdForUpdate() on same note
   - assert it blocks/waits until A commits
3. A completes delete/rebuild and commits
4. B continues and records payment
5. Assert:
   - customer_payments row exists
   - payment_component_allocations row exists
   - note total paid reflects payment correctly
   - no allocation is lost

Recommended audit:

Search for other same-note mutation paths that must use getByIdForUpdate():

- note payments
- note refunds
- note revisions
- add rows
- correction flows
- auto-close/payment lifecycle flows

## Kesimpulan

Laporan #010 valid sebagai High severity financial-integrity race condition.

Bug sebelumnya memungkinkan revision allocation rebuild menghapus allocation dari concurrent payment karena memakai stale non-locked snapshot dan broad delete by note_id. Payment row bisa tetap ada, sementara allocation row hilang.

Patch mengarah benar secara minimal: serialize competing same-note payment/revision flows menggunakan row lock pada note. Namun status tetap verification gap sampai concurrency test atau minimal focused test berhasil dijalankan dan sampai semua competing mutation paths terbukti memakai locking protocol yang sama.

## Related CreateNoteRevisionHandler Guard Finding From Error Log 011

### Related Error Log

- 011-cashier-revision-path-mutates-settled-note-state.md

### Update

Update 2.

### Reason

A later audit report patched the same CreateNoteRevisionHandler file for a separate issue.

This is not the same root cause as #010.

- #010 is about missing serialization between note revision and payment recording, fixed by getByIdForUpdate().
- #011 is about missing payment-derived editability guard, fixed by EditableWorkspaceNoteGuard::assertEditable().

Final CreateNoteRevisionHandler must preserve both fixes. It should not regress from getByIdForUpdate() back to getById() when adding assertEditable().
