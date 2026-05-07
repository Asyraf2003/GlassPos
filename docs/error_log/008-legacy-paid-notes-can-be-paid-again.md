# 008 - Legacy-paid notes can be paid again

## Status

Patched and locally verified.

Root characterization was strengthened to use a valid current-revision workspace row, then verified locally. Selected-row payment now rejects a legacy-paid row using legacy-aware workspace settlement, and the broader payment feature slice remains green.

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

4. Mixed legacy + component note:
   - define expected migration behavior before patching further

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

Open / regression risk.

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

No progress increase because this is the same root cause cluster as #008.

## Update - Mixed payment allocations enable overpayment

Laporan ini diklasifikasikan sebagai update #008, bukan file error-log baru.

## Update Status

Patched, with verification gap.

## Summary

A later report confirmed the same payment-allocation source-of-truth problem in a broader form.

The system partially migrated payment recording from legacy `payment_allocations` to `payment_component_allocations`, but not all writers, readers, and reports were updated consistently.

Affected behavior:

- `CreateTransactionWorkspaceInlinePaymentRecorder` still writes legacy `payment_allocations`.
- `RecordAndAllocateNotePaymentHandler` validates against and writes `payment_component_allocations`.
- `DatabasePaymentComponentAllocationReaderAdapter` previously read component totals only.
- `DatabasePaymentAllocationReaderAdapter` previously returned component total when component total was greater than zero, hiding legacy allocation totals.
- transaction summary reporting still read legacy `payment_allocations` only.
- cash ledger reporting still read legacy `payment_allocations` only.

This allowed a note already paid through the legacy inline path to receive another payment through the component-allocation path.

## Vulnerable Path

Legacy inline payment is recorded
-> row exists in `payment_allocations`
-> selected/manual payment is submitted later
-> `RecordAndAllocateNotePaymentHandler` checks component allocations only
-> legacy payment is ignored
-> new `payment_component_allocations` are written
-> combined legacy + component allocations can exceed note total
-> compatibility reader and reports may hide one side of the data
-> financial records, outstanding display, reconciliation, and reports become inconsistent

## Patch Summary

Patch yang dilaporkan menyatukan total allocation level nota di tabel legacy dan component.

Changed files:

- `app/Adapters/Out/Payment/DatabasePaymentComponentAllocationReaderAdapter.php`
- `app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`
- `app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php`
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php`

Patch behavior:

- component allocation reader now includes legacy `payment_allocations` in note-level allocated totals.
- compatibility allocation reader now returns `componentTotal + legacyTotal`.
- transaction summary cash-payment totals now include both legacy and component allocation rows.
- cash ledger payment rows now union component allocation payment events with legacy allocation payment events.

Reported commit:

`73c7fa9 - Fix mixed payment allocation totals across readers and reports`

## Verification

Reported successful syntax checks:

- `php -l app/Adapters/Out/Payment/DatabasePaymentComponentAllocationReaderAdapter.php`
- `php -l app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`
- `php -l app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php`
- `php -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php`

## Verification Gap

No end-to-end Laravel feature test result was included.

Future verification must prove:

- note total Rp100,000
- legacy inline payment Rp40,000
- component payment attempt Rp100,000 is rejected
- component payment attempt Rp60,000 is accepted only when outstanding is Rp60,000
- compatibility reader returns legacy + component totals
- transaction summary includes component-only payments
- cash ledger includes component-only payment rows
- no report double-counts the same payment when both allocation tables contain rows for the same note

## Residual Risk

This patch intentionally sums legacy and component tables. That is safe only if the two tables represent distinct allocation records.

If a future migration backfills component allocations from legacy rows without marking migrated legacy rows or preventing duplicate semantic rows, summing both tables can double-count migrated payments.

Any migration must define a clear cutover or idempotent compatibility strategy.

No progress increase because this is the same root cause cluster as #008.

## Related #026 - Concurrent note payments can over-allocate balances

#026 is related through the payment allocation invariant. #008 covers overpayment caused by mixed legacy/component allocation sources. #026 covers overpayment caused by concurrent payment requests reading the same stale allocation total before writing.
