# 026 - Concurrent note payments can over-allocate balances

## Status

Fixed and locally verified for minimum note-level payment serialization control.

## Keparahan

High.

## Ringkasan

Payment allocation rentan race condition.

Route `POST /cashier/notes/{noteId}/payments` dapat menerima dua request paralel untuk nota dan row yang sama. Sebelum patch, flow payment membaca total allocated saat ini dengan query `SUM` biasa, lalu menjalankan `PaymentAllocationPolicy`, lalu menulis `customer_payment` dan allocation record.

Karena tidak ada row lock, idempotency token, serializable isolation, atau constraint database yang menjaga aggregate allocation tidak melebihi total nota, dua transaction paralel dapat membaca allocated total yang sama, sama-sama lolos validasi outstanding, lalu sama-sama commit.

Dampaknya: total payment allocation dapat melebihi total `Nota`, merusak receivable, cash ledger, report, dan rekonsiliasi.

## Jalur rentan

Authenticated cashier
-> mengirim dua POST paralel ke `/cashier/notes/{noteId}/payments`
-> controller resolve selected-row amount tanpa idempotency guard
-> handler mulai transaction
-> handler membaca current allocated total dengan non-locking SUM
-> dua request membaca stale total yang sama
-> dua request lolos `PaymentAllocationPolicy`
-> dua request insert payment/allocation
-> total allocation melebihi total nota
-> financial state corrupt

## Root cause

Proteksi over-allocation bergantung pada hasil read current allocated total, tetapi read tersebut tidak diserialisasi terhadap write allocation berikutnya.

`PaymentAllocationPolicy` benar secara single-request, tetapi tidak cukup untuk concurrent requests jika nilai `totalAllocatedByNoteRupiah` berasal dari pembacaan non-locking.

## Patch summary

`app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php` now reads the target note through a lock-aware note reader before reading allocated totals, checking policy, writing payment/component allocations, auto-closing the note, and letting the handler write audit/projection changes inside the same transaction boundary.

Changed production files:

- `app/Ports/Out/Note/NoteReaderPort.php`
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`

Patch behavior:

- `NoteReaderPort` now exposes `getByIdForUpdate(string $id): ?Note`.
- `DatabaseNoteReaderAdapter::getByIdForUpdate()` loads the note row using `lockForUpdate()`.
- `DatabaseNoteReaderAdapter` reuses the same mapper/detail loading path for normal and lock-aware reads.
- `RecordAndAllocateNotePaymentOperation::execute()` now calls `getByIdForUpdate()` before `getTotalAllocatedAmountByNoteId()`.
- `RecordAndAllocateNotePaymentHandler::handle()` already wraps operation, audit, projection sync, and commit/rollback in one transaction boundary.

With a note-level row lock inside the existing transaction, concurrent payment requests for the same note must serialize at the note read before allocated-total read and allocation write.

## Verification proof

Syntax checks passed:

- `php -l app/Ports/Out/Note/NoteReaderPort.php`
- `php -l app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `php -l app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`

Lock proof anchors:

- `app/Ports/Out/Note/NoteReaderPort.php` exposes `getByIdForUpdate()`.
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php` implements `getByIdForUpdate()`.
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php` uses `lockForUpdate()`.
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php` calls `getByIdForUpdate()` before allocation total read.
- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php` begins a transaction before calling the operation and commits after audit/projection sync.

Focused tests passed:

- `php artisan test tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php`
- PASS: 6 passed, 30 assertions

Wider Note + Payment tests passed:

- `php artisan test tests/Feature/Note tests/Feature/Payment`
- PASS: 162 passed, 955 assertions

## Verification gaps / out of scope

Not performed in this closure:

- true parallel two-connection concurrency stress test
- database-engine-specific lock wait/timeout assertion
- idempotency token for duplicate form submission
- full global suite
- browser/manual UI QA
- #001 final global verification claim

The implemented control is source-level minimum serialization: note row lock before allocated-total read and allocation write, under the handler transaction boundary.

## Residual risk

`lockForUpdate()` is only effective while the locked read, allocated-total read, policy check, payment write, allocation write, auto-close, audit, projection sync, and commit/rollback remain in one database transaction boundary.

If this operation is later called outside `RecordAndAllocateNotePaymentHandler` or outside an equivalent transaction wrapper, the lock will not protect the aggregate allocation invariant.

Idempotency key support for form submission remains a valid defense-in-depth improvement, but the note-level lock is the minimum required control for serializing payment writes on the same note.

## Relations

Related to #010.

#010 covers revision/payment concurrency where a revision flow can lose concurrent payment allocations.

#026 covers payment/payment concurrency where two concurrent payment requests can over-allocate the same note.

Terkait dengan #008 karena kedua masalah merusak invariant payment allocation dan dapat memungkinkan overpayment atau total outstanding/payment yang salah, tetapi #008 disebabkan campuran sumber allocation legacy/component sedangkan #026 disebabkan concurrent writes yang tidak diserialisasi.
