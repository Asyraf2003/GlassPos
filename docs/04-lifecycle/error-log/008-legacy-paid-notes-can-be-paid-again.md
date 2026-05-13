# 008 - Legacy-paid notes can be paid again

## Status

Fixed and locally verified for backend allocation/projection scope, with explicit migration/global/browser gaps.

Root characterization was strengthened to use a valid current-revision workspace row, then verified locally. The later mixed legacy/component allocation regression was reproduced with a failing feature test, then fixed so selected-row payment uses combined legacy and component allocation totals for outstanding/guard behavior. Targeted, focused, and wider Note + Payment suites passed locally.

Browser/manual UI QA, full global suite, full reporting suite, production migration/cutover, and explicit overpayment/customer-credit workflow remain outside this closure.

## Severity

High.

## Source

Audit report #008: Legacy-paid notes can be paid again.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 005-note-revision-silently-drops-overpaid-allocations.md
- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Jenis Keterkaitan

Indirect financial/payment allocation relationship.

### Alasan

Laporan #008 berada pada area payment allocation and settlement integrity.

- #005 membahas payment replay saat note revision yang bisa menyembunyikan overpaid excess.
- #003 membahas mixed net/gross settlement pada revised note dengan historical refund.
- #008 membahas selected-row payment validation yang mengabaikan legacy payment_allocations sehingga legacy-paid note bisa dibayar ulang.

Ketiganya menyangkut settlement basis yang harus konsisten, tetapi #008 bukan bug yang sama karena route, service, dan precondition berbeda.

#008 terjadi pada selected-row payment flow untuk note legacy-paid, bukan pada note revision replay.

## Update Log

### Update 1

Initial audit log entry untuk laporan #008.

Alasan update:

- Laporan menunjukkan legacy-paid notes dapat dibayar ulang karena selected-row resolver memakai component-only billing projection.
- Patch sudah diterapkan di SelectedNoteRowsPaymentAmountResolver.
- Regression test ditambahkan untuk fully paid legacy note.
- Verification masih gap karena test gagal dijalankan akibat missing dependencies.

### Update 2

Local root verification completed.

Alasan update:

- Source/local command output at HEAD ae090092 supersedes the earlier vendor-missing verification gap.
- Strengthened characterization seeds a current revision and note revision line so the resolver reaches a valid workspace path.
- The selected row ID was corrected to the actual current workspace row ID, wi-legacy-paid-1, because current revision row mapping uses workItemRootId().
- Legacy allocation 50.000 on a single 50.000 service line yields outstanding 0 through the legacy-aware workspace settlement path.
- Targeted and broader payment suites now pass.

### Update 3

Mixed legacy/component allocation regression fixed and locally verified.

Alasan update:

- Red characterization proved selected-row payment in a mixed legacy/component state recorded 90.000 when true outstanding was 50.000.
- Compatibility allocation reading now uses combined legacy + component totals where required by payment guard and current operational row projection.
- Targeted #008, focused #008 regression, and wider Note + Payment suites passed locally.
- Browser/manual UI QA, full global suite, full reporting suite, production migration/cutover, and explicit overpayment/customer-credit workflow remain out of scope.

## Ringkasan Indonesia

Bug terjadi pada selected-row payment validation.

Sebelum bug, selected-row payment validation memakai path settlement yang legacy-aware. Path itu bisa fallback ke:

- payment_allocations
- customer_refunds

jika component allocations belum ada.

Commit yang dilaporkan mengubah resolver agar memakai NoteBillingProjectionBuilder secara langsung.

Masalahnya, NoteBillingProjectionBuilder hanya menghitung:

- payment_component_allocations
- refund_component_allocations

dan tidak fallback ke legacy:

- payment_allocations
- customer_refunds

Akibatnya, note yang sebenarnya sudah lunas lewat legacy payment_allocations dapat terlihat masih outstanding penuh jika component allocation tables kosong.

Seorang authenticated cashier/admin bisa mengirim selected_row_ids valid ke endpoint payment, lalu sistem menerima pembayaran baru untuk note yang sebenarnya sudah paid.

## Dampak

Dampak utama:

- legacy-paid note bisa dibayar ulang
- customer_payments baru bisa tercatat
- payment_component_allocations baru bisa dibuat untuk note yang sudah settled
- cash ledger/reporting bisa corrupt
- customer billing state menjadi tidak akurat
- payment integrity rusak, terutama untuk migrated/legacy notes

Severity High tepat karena ini menyentuh payment records dan financial reporting. Tidak Critical karena membutuhkan authenticated cashier/admin, valid CSRF/session, accessible note, dan kondisi data legacy tanpa component allocations.

## Jalur Risiko

Workflow risiko:

1. User login sebagai cashier/admin.
2. Target note sudah dibayar via legacy payment_allocations.
3. Note belum memiliki payment_component_allocations.
4. User submit POST payment endpoint dengan selected_row_ids valid.
5. SelectedNoteRowsPaymentAmountResolver membaca billing rows dari NoteBillingProjectionBuilder.
6. NoteBillingProjectionBuilder hanya melihat component allocations.
7. Legacy payment_allocations diabaikan.
8. Outstanding row terlihat masih penuh.
9. Resolver menerima payment.
10. RecordAndAllocateNotePaymentOperation juga melakukan check dengan component-only allocation reader.
11. Sistem membuat customer_payments dan payment_component_allocations baru.
12. Note yang sudah settled menjadi punya duplicate/excess payment records.

## Root Cause

Root cause:

Selected-row payment validation memakai projection yang tidak memiliki legacy fallback.

Ada dua settlement basis yang tidak konsisten:

1. Workspace operational settlement path:
   - component-aware
   - legacy-aware fallback
   - bisa melihat legacy payment_allocations

2. Direct billing projection path:
   - component-only
   - tidak melihat legacy payment_allocations

Bug muncul karena selected-row resolver pindah dari basis #1 ke basis #2.

Dalam sistem yang masih punya data legacy, component-only projection tidak boleh menjadi source of truth tunggal untuk outstanding validation.

## Patch Summary

Patch diterapkan pada:

app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php

Perubahan:

- tambahkan dependency NoteWorkspacePanelDataBuilder
- build workspace panel untuk note
- jika workspace null, return PAYMENT_INVALID_TARGET
- billing rows dibangun dari legacy-aware workspace rows:
  NoteBillingProjectionBuilder::buildFromWorkspaceRows((array) ($workspace['rows'] ?? []))
- direct call ke NoteBillingProjectionBuilder::build($note->id()) tidak lagi menjadi basis utama selected-row validation

Efek patch:

- selected-row payment amount validation kembali memakai settlement rows yang legacy-aware
- fully paid legacy note menghasilkan outstanding 0
- duplicate payment untuk legacy-paid note harus ditolak

Test ditambahkan pada:

tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php

Test baru:

test_rejects_selected_row_payment_when_note_already_paid_via_legacy_allocation

Test intent:

- seed note dengan total 50.000
- seed current revision dan note revision line untuk valid workspace path
- seed work item service 50.000
- seed customer_payments 50.000
- seed legacy payment_allocations 50.000
- submit cashier selected-row payment memakai current workspace row id wi-legacy-paid-1
- expect exact session error: Hanya billing row outstanding yang boleh dipilih untuk pembayaran.
- expect customer_payments count tetap 1
- expect payment_component_allocations count tetap 0

## Scope In

- SelectedNoteRowsPaymentAmountResolver.
- Selected-row payment amount validation.
- Legacy-paid note duplicate payment prevention.
- Legacy-aware workspace row settlement reuse.
- Regression coverage for legacy payment_allocations.

## Scope Out

- Full migration from legacy payment_allocations to component allocations.
- Reporting query redesign.
- RecordAndAllocateNotePaymentOperation component-only check beyond this resolver.
- Full browser E2E.
- Production data audit for legacy-paid note count.
- Cleanup of old legacy allocation model.
- Payment replay issues from #005.
- Refund/revised note settlement issue from #003.

## Proof Dari Patch Session

User reported:

- vulnerability still existed in current HEAD
- minimal fix implemented in selected-row resolver
- settlement input switched from direct component projection to legacy-aware workspace rows
- regression feature test added
- committed on branch work with commit:
  c8938eb
- PR record created with title:
  Fix duplicate payments for legacy-paid selected billing rows

Changed files:

app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php
tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php

Reported diff size:

+74
-1

Testing reported:

./vendor/bin/phpunit --filter RecordNotePaymentHttpFeatureTest

Result:

Failed because ./vendor/bin/phpunit not found.

php artisan test --filter=RecordNotePaymentHttpFeatureTest

Result:

Warning/failure because vendor/autoload.php not present.

## Verification Status

Verified locally at HEAD ae090092.

Targeted root characterization:

Command:

php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php --filter=legacy_allocation

Result:

PASS, 1 passed, 4 assertions.

Full HTTP payment feature proof:

Command:

php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php

Result:

PASS, 2 passed, 10 assertions.

Broader payment slice proof:

Command:

php artisan test tests/Feature/Payment tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php

Result:

PASS, 21 passed, 85 assertions.

Verified behavior:

- legacy-paid selected row is rejected through the row-specific outstanding validation path
- no extra customer_payments row is created in the characterization test
- no payment_component_allocations row is created in the characterization test
- normal selected-row component allocation flow remains green after fixture upgrade to current revision workspace

Residual follow-up:

- legacy partially paid selected-row behavior still needs separate coverage unless already verified elsewhere
- mixed legacy/component migration behavior still needs separate coverage unless already verified elsewhere

## Recommended Follow-up

Recommended additional tests:

1. Legacy partially paid note:
   - payment_allocations less than total
   - component allocations empty
   - selected-row payment accepted only up to remaining outstanding

2. Component-paid note:
   - component allocations exist
   - existing component behavior unchanged

3. Mixed legacy/component note:
   - migration-edge settlement behavior follows intended migration rules
   - no duplicate/excess payment is accepted

## Kesimpulan

Laporan #008 valid sebagai High severity financial-integrity issue.

Bug sebelumnya membuat selected-row payment validation memakai component-only billing projection. Untuk note yang sudah paid melalui legacy payment_allocations, sistem bisa menganggap outstanding masih penuh dan menerima pembayaran baru.

Patch mengarah benar karena mengembalikan selected-row validation ke legacy-aware workspace settlement rows. Namun karena test belum bisa dijalankan, status tetap patched with verification gap. Sekali lagi: test yang ditulis tapi tidak jalan itu bukan proof, cuma niat baik dalam format PHP.

## Related Payment Allocation Concurrency Finding From Error Log 010

### Related Error Log

- 010-revision-reallocation-can-lose-concurrent-payments.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity payment allocation issue.

Ini bukan root cause yang sama dengan #008.

- #008 is about selected-row payment validation ignoring legacy payment_allocations.
- #010 is about lost updates between concurrent payment recording and note revision allocation rebuild.

Both findings affect payment allocation integrity and prove payment state must be validated against the correct settlement basis and protected against unsafe interleavings.

## Related Selected-Row Payment Finding From Error Log 012

### Related Error Log

- 012-canceled-note-rows-re-enter-payment-flows.md

### Update

Update 3.

### Reason

A later audit report found a separate High severity issue in selected-row payment allocation.

Ini bukan root cause yang sama dengan #008.

- #008 is about selected-row payment validation ignoring legacy payment_allocations.
- #012 is about selected-row payment selection accepting canceled work item components.

Both findings show that selected-row payment validation must use the correct settlement basis and must exclude non-payable inactive rows.

## Related Existing-Payment Validation Finding From Error Log 017

### Related Error Log

- 017-workspace-edit-payments-ignore-existing-note-payments.md

### Update

Update 4.

### Reason

A later audit report found a separate High severity issue where a payment flow ignored existing note payments.

Ini bukan root cause yang sama dengan #008.

- #008 is about selected-row payment validation ignoring legacy payment_allocations.
- #017 is about workspace edit inline payment treating existing allocated totals as zero and resolving pay_full to the full note total.

Both findings show that all payment entry paths must validate against existing allocated/outstanding balance, including legacy allocations when supported.

## Update - Legacy inline payments ignored in row settlement

Laporan ini diklasifikasikan sebagai update #008, bukan file error-log baru.

## Update Status

Covered by the later mixed allocation compatibility patch, with scoped verification gaps.

## Summary

A later row-settlement path can still ignore legacy inline payments.

`NoteOperationalRowSettlementProjector` now reads only component allocation readers:

- `PaymentComponentAllocationReaderPort::listByNoteId()`
- `RefundComponentAllocationReaderPort::listByNoteId()`

However, `CreateTransactionWorkspaceInlinePaymentRecorder` still writes inline workspace payments as legacy `PaymentAllocation` records.

This creates an incompatible mixed state:

- note has legacy `payment_allocations`
- note has no `payment_component_allocations`
- row settlement projector reports zero allocated per row
- selected-row payment resolver treats the row as fully outstanding
- final payment handler also checks prior allocation through component-only totals
- duplicate component allocation can be created on top of the existing legacy payment

Example from report:

A Rp100,000 note with Rp40,000 legacy inline partial payment can be treated as having Rp100,000 outstanding. A new selected-row payment of Rp100,000 can then be accepted, leaving combined legacy + component payment records at Rp140,000.

## Additional Vulnerable Path

Legacy inline payment exists
-> inline payment is stored in `payment_allocations`
-> no matching `payment_component_allocations`
-> `NoteOperationalRowSettlementProjector` reads component allocations only
-> row outstanding is inflated
-> `SelectedNoteRowsPaymentAmountResolver` accepts excessive selected-row payment
-> `RecordAndAllocateNotePaymentHandler` checks component-only prior allocation
-> duplicate payment component allocation is persisted
-> financial records overstate received payment

## Additional Evidence

Affected files from report:

- `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`
- `app/Application/Note/Services/NoteOperationalRowSettlementProjector.php`
- `app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php`
- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php`

## Required Fix Direction

Do not rely on component-only allocation readers while legacy inline payments can still exist.

The safe fix must choose one explicit direction:

1. make the row settlement projector legacy-aware again, or
2. migrate/write inline payments into component allocations, or
3. provide a compatibility adapter that merges legacy allocation totals and component allocation totals without double-counting.

The final handler policy check must also use the same legacy-aware allocated total, not a different component-only source.

## Verification Required

No patch was included in this report.

Future verification must include a regression test for:

- note total Rp100,000
- legacy inline payment Rp40,000
- no component allocations
- selected-row outstanding must be Rp60,000, not Rp100,000
- attempt to pay Rp100,000 must be rejected
- combined legacy + component allocated total must never exceed note total unless an explicit overpayment workflow exists


## Update - Mixed payment allocations enable overpayment

Laporan ini diklasifikasikan sebagai update #008, bukan file error-log baru.

## Update Status

Fixed and locally verified for backend allocation/projection scope.

## Summary

A later report confirmed the same payment-allocation source-of-truth problem in a broader mixed state.

The system had both legacy note-level allocations and newer component allocations:

- legacy inline payment writes to `payment_allocations`
- selected/component payment writes to `payment_component_allocations`
- row settlement and final payment guard could read only one side
- selected-row payment could therefore over-record a new payment when existing payment already existed in the other allocation table

The confirmed failing scenario:

- note total 100.000
- existing legacy payment allocation 40.000
- existing component allocation 10.000
- true outstanding 50.000
- selected-row cash tender 90.000
- expected new customer payment amount 50.000
- actual red-test customer payment amount 90.000

This proved the selected-row flow still ignored mixed legacy/component allocation state and could over-record payment.

## Red Characterization

File:

- `tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php`

Test:

- `test_selected_row_payment_uses_combined_legacy_and_component_allocations`

Red proof:

- expected new payment amount: 50.000
- actual new payment amount: 90.000

## Production Fix

Changed files:

- `app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
- `app/Application/Note/Services/NoteOperationalRowSettlementProjector.php`
- `app/Application/Note/Services/CurrentRevision/CurrentRevisionRowSettlementProjector.php`

Fix summary:

- `DatabasePaymentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId()` now returns `componentTotal + legacyTotal` instead of component-first fallback.
- `RecordAndAllocateNotePaymentOperation` now uses `PaymentAllocationReaderPort` for the final payment policy check, so the guard uses compatibility totals instead of component-only totals.
- `NoteOperationalRowSettlementProjector` now reads compatibility note-level allocated/refunded totals and merges note-level remainders into component row settlement when component totals exist.
- `CurrentRevisionRowSettlementProjector` applies the same mixed remainder behavior for current operational projection used by payment workspace/UI data.

## Test Updates

Changed files:

- `tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php`
- `tests/Unit/Application/Note/Services/NoteOperationalRowSettlementProjectorTest.php`

Test summary:

- Added mixed legacy/component characterization test.
- Updated component-only projector mocks so compatibility note-level totals are intentionally read even when component rows exist.

## Verification Proof

Syntax checks passed:

- `php -l app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`
- `php -l app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
- `php -l app/Application/Note/Services/NoteOperationalRowSettlementProjector.php`
- `php -l app/Application/Note/Services/CurrentRevision/CurrentRevisionRowSettlementProjector.php`

Targeted #008:

- `php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php --filter='selected_row_payment_uses_combined_legacy_and_component_allocations'`
- PASS: 1 passed, 6 assertions

Focused #008 regression:

- `php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php tests/Unit/Application/Note/Services/NoteOperationalRowSettlementProjectorTest.php tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php`
- PASS: 9 passed, 72 assertions

Wider Note + Payment:

- `php artisan test tests/Feature/Note tests/Feature/Payment`
- PASS: 162 passed, 955 assertions

## Verification Gaps / Out of Scope

Not performed in this closure:

- browser/manual UI QA
- visual UI layout verification
- full global suite
- full reporting suite beyond touched/focused areas
- production data cleanup
- migration/cutover strategy for future backfilled component allocations
- explicit customer credit/overpayment model
- #001 final global verification claim
- connector commit/push

Patch affects UI data indirectly because row outstanding, allocated total, settlement label, and selected-row payment amount are backend-projected values consumed by cashier/admin UI.

No Blade, JavaScript, or view file was changed.

## Residual Risk

`componentTotal + legacyTotal` is safe only while the two tables represent distinct allocation records.

If a future migration backfills component allocations from legacy rows without marking migrated legacy rows, clearing legacy rows, or defining an idempotent compatibility strategy, summing both tables can double-count migrated payments.

Any future migration must define a clear cutover or idempotent compatibility strategy before production rollout.

## Related #026 - Concurrent note payments can over-allocate balances

#026 is related through the payment allocation invariant. #008 covers overpayment caused by mixed legacy/component allocation sources. #026 covers overpayment caused by concurrent payment requests reading the same stale allocation total before writing.
