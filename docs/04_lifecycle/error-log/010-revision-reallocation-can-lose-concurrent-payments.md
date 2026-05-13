# 010 - Revision reallocation can lose concurrent payments

## Status

Fixed and locally verified for minimum revision/payment same-note serialization control.

Revision and payment mutation paths now use the same note-level lock protocol before payment allocation capture/read/write. Focused #010 tests and wider Note + Payment feature suites passed locally. True parallel two-connection concurrency stress testing remains out of scope for this closure.

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

Patch applies the minimum ADR-0022 same-note serialization control for revision/payment contention.

Source finding before this closure:

- Payment path already used `getByIdForUpdate()` from the #026 payment serialization fix.
- Revision path still used non-locking `getById()` in `CreateNoteRevisionHandler`.
- That meant payment and revision did not consistently share the same note-level lock protocol.

Production file changed in this closure:

- `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`

Related lock support already present from #026 source fix:

- `app/Ports/Out/Note/NoteReaderPort.php`
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`

Patch behavior:

- `CreateNoteRevisionHandler` still opens a transaction before applying the revision.
- Inside that transaction, it now reads the root note with `getByIdForUpdate()`.
- `ApplyNoteRevisionAsActiveReplacement` then runs allocation capture, delete, work item persistence, allocation rebuild, and projection sync after the note lock is acquired.
- `RecordAndAllocateNotePaymentOperation` already reads the same target note through `getByIdForUpdate()` before allocated-total read and allocation write.
- Revision and payment mutation paths now serialize through the same target `notes` row lock.

This patch does not redesign capture/delete/rebuild allocation behavior. It only ensures same-note revision/payment mutation cannot interleave without passing the shared note lock.

## Verification Proof

Syntax check passed:

- `php -l app/Application/Note/UseCases/CreateNoteRevisionHandler.php`

Source lock anchors verified:

- `CreateNoteRevisionHandler` begins a transaction before calling `getByIdForUpdate()`.
- `CreateNoteRevisionHandler` calls `getByIdForUpdate()` before revision apply.
- `ApplyNoteRevisionAsActiveReplacement` performs `captureAllocatedAmounts()`, `deleteExisting()`, and `rebuild()` after the locked root note read.
- `RecordAndAllocateNotePaymentOperation` calls `getByIdForUpdate()` before `getTotalAllocatedAmountByNoteId()`.
- `RecordAndAllocateNotePaymentHandler` wraps payment operation, audit, projection sync, and commit/rollback in a transaction.

Focused #010 tests passed:

- `php artisan test tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php`
- PASS: 11 passed, 93 assertions

Wider Note + Payment tests passed:

- `php artisan test tests/Feature/Note tests/Feature/Payment`
- PASS: 162 passed, 955 assertions

## Verification Gaps / Out of Scope

Not performed in this closure:

- true parallel revision/payment two-connection concurrency stress test
- database-engine-specific lock wait/timeout assertion
- full global suite
- browser/manual UI QA
- idempotency token for duplicate form submission
- complete audit of every possible same-note mutation route
- #001 final global verification claim

The implemented control is source-level minimum serialization under ADR-0022: target note row lock before revision allocation capture/delete/rebuild, and target note row lock before payment allocated-total read/write.

## Residual Risk

`lockForUpdate()` only protects #010 while competing same-note mutation paths consistently follow the same note-level lock protocol inside an active database transaction.

If a future revision/payment/refund/note mutation path bypasses `getByIdForUpdate()` or performs finance mutation outside an equivalent transaction boundary, this race class can reappear.

Idempotency keys and database-specific lock wait tests remain valid defense-in-depth work, but they are not part of this minimum closure.

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

Bug sebelumnya memungkinkan revision allocation rebuild menghapus allocation dari concurrent payment karena revision path memakai stale non-locked snapshot dan broad delete by note_id. Minimum closure now serializes revision/payment mutation through the same target note row lock before allocation capture/read/write.

Patch mengarah benar secara minimal: serialize competing same-note payment/revision flows menggunakan row lock pada note. Namun status tetap verification gap sampai concurrency test atau minimal focused test berhasil dijalankan dan sampai semua competing mutation paths terbukti memakai locking protocol yang sama.

## Related CreateNoteRevisionHandler Guard Finding From Error Log 011

### Related Error Log

- 011-cashier-revision-path-mutates-settled-note-state.md

### Update

Update 2.

### Reason

A later audit report patched the same CreateNoteRevisionHandler file for a separate issue.

Ini bukan root cause yang sama dengan #010.

- #010 is about missing serialization between note revision and payment recording, fixed by getByIdForUpdate().
- #011 is about missing payment-derived editability guard, fixed by EditableWorkspaceNoteGuard::assertEditable().

Final CreateNoteRevisionHandler must preserve both fixes. It should not regress from getByIdForUpdate() back to getById() when adding assertEditable().

## Related #026 - Concurrent note payments can over-allocate balances

#026 is related through payment concurrency safety. #010 covers revision/payment concurrency where revision reallocation can lose concurrent payment allocations. #026 covers payment/payment concurrency where two parallel payment requests can over-allocate the same note unless allocation validation and writes are serialized per note.
