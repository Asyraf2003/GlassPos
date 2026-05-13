# 003 - Refunded revised notes are misclassified as underpaid

## Status

Fixed with proof.

Final local verification supersedes the earlier verification gap.

Earlier gross-back reader patch notes are retained below as historical context, but the verified fix now uses explicit current-refund settlement semantics in `NotePaidStatusPolicy` / `CustomerRefundReaderPort` instead of treating all note-level refunds as the same settlement basis.

## Severity

High.

## Source

Audit report #003: Refunded revised notes are misclassified as underpaid.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 001-refunds-counted-as-paid-in-note-totals.md

### Jenis Keterkaitan

Direct follow-up / regression edge case.

### Alasan

Laporan #003 berada pada area settlement yang sama dengan laporan #001, yaitu interaksi antara:

- payment_component_allocations
- refund_component_allocations
- DatabasePaymentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId()
- CustomerRefundReaderPort
- NotePaidStatusPolicy
- NoteOperationalStatusResolver
- outstanding/paid note mutation guards

Namun #003 bukan bug identik dengan #001.

Perbedaan utama:

- #001: active component refund ikut ditambahkan ke allocated total, lalu refund yang sama dikurangkan lagi, sehingga refund aktif menjadi netral dan nota tetap terlihat paid.
- #003: setelah refund_component_allocations tidak lagi ditambahkan ke note-level allocated total, revised note dengan historical refund bisa mengalami double subtraction karena replacement reconciler sudah membangun ulang allocation secara net-of-refund, lalu downstream masih mengurangkan refund lagi.

Karena failure mode berbeda, laporan ini harus menjadi file baru, bukan disatukan ke #001.

## Update Log

### Update 1

Initial audit log entry untuk laporan #003.

Alasan update:

- Laporan ini menunjukkan bahwa patch area #001 belum cukup untuk seluruh lifecycle note revision/refund.
- Settlement logic membutuhkan pembedaan antara active/current refund dan historical refund yang sudah dipakai saat revision/replacement replay.
- Patch cepat dengan sekadar "tambah refund lagi" atau "hapus refund lagi" berisiko membalik bug dari #001 ke #003 atau sebaliknya.

## Ringkasan Indonesia

Bug terjadi pada revised note yang memiliki historical component refund.

Commit sebelumnya mengubah note-level allocation reader agar tidak lagi menghitung refund_component_allocations sebagai allocated amount. Ini memperbaiki skenario active refund biasa, tetapi membuat skenario revised note bermasalah.

Pada saat note direvisi, NoteReplacementPaymentAllocationReconciler membaca payment allocation lama dan refund lama, lalu membangun ulang payment_component_allocations untuk replacement rows secara net-of-refund.

Artinya, allocation yang baru sudah mencerminkan refund historis.

Namun row historical refund_component_allocations tetap ada.

Setelah itu, downstream seperti NotePaidStatusPolicy dan NoteOperationalStatusResolver tetap menghitung:

net_settlement = allocated - refunded

Masalahnya:

- allocated dari reader sudah net-of-refund
- refunded dari refund reader masih mengembalikan historical refund
- hasil akhirnya refund historis dikurangkan dua kali

Akibatnya revised note yang sebenarnya sudah fully paid bisa terlihat underpaid/open.

## Contoh Dampak

Skenario dari laporan:

- Payment awal: 300.000
- Historical component refund: 100.000
- Revised note total: 200.000
- Rebuilt payment_component_allocations setelah revision: 200.000
- Historical refund_component_allocations tetap ada: 100.000

Perhitungan yang benar:

- allocated/gross settlement untuk revised note seharusnya menghasilkan net paid 200.000
- revised note total 200.000
- status seharusnya paid

Perhitungan bug:

- allocated = 200.000
- refunded = 100.000
- net_settlement = 100.000
- note dianggap underpaid/open

Akibatnya paid-note guard dapat gagal mengenali note yang seharusnya locked sebagai paid.

## Jalur Risiko

Authenticated cashier/admin menggunakan flow revision/refund pada note.

Workflow ringkas:

1. Note memiliki component payment.
2. Note memiliki historical component refund.
3. Note direvisi/replaced.
4. NoteReplacementPaymentAllocationReconciler membangun ulang payment_component_allocations secara net-of-refund.
5. Historical refund_component_allocations tetap ada.
6. DatabasePaymentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId() hanya membaca payment_component_allocations.
7. NotePaidStatusPolicy atau NoteOperationalStatusResolver mengurangkan refund lagi.
8. Net settlement menjadi terlalu kecil.
9. Note yang sebenarnya paid terlihat underpaid/open.
10. Standard mutation guard bisa terbuka untuk row additions/edits yang seharusnya lewat paid-note correction/audit flow.

## Dampak Bisnis

Ini adalah financial-integrity issue.

Dampak utama:

- revised paid note bisa salah diklasifikasi sebagai underpaid/open
- paid-note mutation guard bisa tidak efektif
- authenticated cashier/admin dapat melakukan normal row mutation pada note yang seharusnya terkunci
- inventory issuance bisa terjadi lewat flow biasa pada note yang semestinya memakai correction flow
- status operasional, outstanding, dan audit settlement bisa tidak akurat

Severity High tepat karena bug menyentuh uang, status nota, inventory, dan mutation guard. Tidak otomatis Critical karena butuh authenticated transaction-entry actor dan skenario note/refund/revision tertentu.

## Root Cause

Root cause bukan sekadar satu query salah.

Root cause sebenarnya adalah tidak adanya settlement semantics yang eksplisit untuk membedakan:

1. active/current refund yang harus mengurangi net paid
2. historical refund yang sudah dipakai saat rebuilding/revision allocation
3. gross allocated payment
4. net carried-forward settlement after revision

Reader level nota sekarang terlalu sederhana untuk dua konteks berbeda:

- active refund normal membutuhkan allocated tidak ditambah refund
- revised note dengan net rebuilt allocation bisa membutuhkan gross-back atau explicit settlement service agar refund historis tidak dikurangkan dua kali

## Files Mentioned By Report

Primary affected file:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php

Related consumers:

app/Application/Note/Policies/NotePaidStatusPolicy.php
app/Application/Note/Services/NoteOperationalStatusResolver.php
app/Application/Note/Policies/NoteAddabilityPolicy.php
app/Application/Note/UseCases/AddWorkItemHandler.php
app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php

Related route surface:

routes/web/note.php

## Scope In

- Component-backed payment/refund settlement.
- Revised/replaced note settlement.
- Historical refund interaction with rebuilt payment_component_allocations.
- Paid-status and operational-status classification.
- Mutation guard impact for paid notes.

## Scope Out

- Seeder/default credential issue from #002.
- Generic authentication/access-control.
- Non-component legacy payment_allocations unless later evidence shows the same issue.
- Immediate implementation patch.
- Test creation, because no command output or patch was supplied in this report.

## Patch Status

No patch was supplied.

Do not patch blindly by reverting #001 or by re-adding all refund_component_allocations into getTotalAllocatedAmountByNoteId().

Itu berisiko mengembalikan bug #001, ketika active refund dihitung sebagai paid lalu dikurangi lagi, sehingga active refund menjadi tidak efektif.

Arah teknis yang lebih aman adalah memperkenalkan atau memakai settlement semantics eksplisit yang dapat membedakan current active refunds dari historical refunds yang sudah dikonsumsi selama note replacement/revision replay.

## Recommended Follow-up

Recommended next active step:

Create characterization tests before patching.

Minimum test matrix:

1. Active refund normal note:
   - total 50.000
   - payment 50.000
   - active refund 10.000
   - expected net paid 40.000
   - expected outstanding 10.000

2. Revised note with historical refund:
   - original payment 300.000
   - historical refund 100.000
   - revised note total 200.000
   - rebuilt payment_component_allocations 200.000
   - historical refund_component_allocations 100.000
   - expected net paid 200.000
   - expected paid status true

Fix yang valid harus pass kedua test. Jika hanya satu yang pass, berarti code hanya memindahkan bug dari satu sisi settlement model ke sisi lain; itu bukan engineering, hanya menyusun ulang perabot yang sudah rusak.

## Kesimpulan

Laporan #003 valid sebagai High severity settlement/accounting logic issue.

Temuan ini menunjukkan bahwa patch area #001 belum cukup untuk seluruh lifecycle refund dan revised note. Active refund dan historical refund setelah revision tidak boleh diperlakukan dengan kalkulasi generic yang sama.

Akar masalah yang perlu diselesaikan adalah settlement semantics, bukan hanya agregasi query. Sistem perlu membedakan gross allocation, active refund, historical consumed refund, dan carried-forward settlement agar paid status, outstanding, mutation guard, dan inventory flow tetap konsisten.

## Patch Update

### Update 2

Patch supplied under report title:

Refunded revisions undercount paid totals

### Relationship Classification

Issue identik / root cause sama dengan file ini.

Update ini bukan error log baru karena membahas failure mode yang sama:

- revised note has historical refund_component_allocations
- NoteReplacementPaymentAllocationReconciler rebuilds payment_component_allocations on a net-of-refund basis
- note-level paid/outstanding/status logic subtracts refund_component_allocations again
- revised note becomes incorrectly underpaid/open

### Reason For Update

Entry #003 awal belum memiliki patch. Update ini mencatat patch lanjutan dan penambahan test.

### Patch Summary

Changed file:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php

Change:

- getTotalAllocatedAmountByNoteId() now queries refund_component_allocations for the note
- component allocation total is grossed back by adding component refund total
- return becomes componentTotal + componentRefundTotal when either component payment rows or component refund rows exist
- legacy fallback to payment_allocations remains unchanged when no component/refund rows exist

Added test file/update:

tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

Added focused feature test:

test_note_total_includes_component_refunds_for_revised_note_component_flow

The test reproduces the revised-note shape:

- note total: 200.000
- customer payment: 300.000
- rebuilt payment_component_allocations: 200.000
- historical refund_component_allocations: 100.000
- expected note-level allocated total: 300.000

This allows downstream allocated-minus-refunded logic to compute:

- allocated: 300.000
- refunded: 100.000
- net paid: 200.000

Itu sesuai dengan revised note total dan mencegah nota salah diklasifikasikan sebagai underpaid/open.

### Testing Reported

Command attempted:

php artisan test tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

Result:

Gagal di environment patch karena dependencies belum terpasang.

Failure reason:

missing vendor/autoload.php

### Verification Status

Patch sudah dicatat, tetapi verifikasi test penuh belum terbukti dari environment ini.

Ini penting: keberadaan file test bukan bukti bahwa test sudah pass. Manusia terus menyamakan niat dengan evidence, dan software menghukum hobi itu tanpa ampun.

### Regression Risk Against #001

Patch ini secara sengaja menambahkan kembali refund_component_allocations ke allocated total level nota.

Itu adalah arah berlawanan dari patch yang dicatat di:

001-refunds-counted-as-paid-in-note-totals.md

Karena itu, patch ini harus diverifikasi terhadap kedua skenario:

1. Active refund normal note

Expected behavior:

- total: 50.000
- payment: 50.000
- active refund: 10.000
- net paid: 40.000
- outstanding: 10.000
- note must not remain fully paid because of active refund cancellation

2. Revised note with historical refund

Expected behavior:

- original payment: 300.000
- historical refund: 100.000
- revised note total: 200.000
- rebuilt payment_component_allocations: 200.000
- refund_component_allocations: 100.000
- gross allocated basis: 300.000
- net paid after subtract refund: 200.000
- note should be paid/closed

Fix final yang valid harus pass keduanya. Jika hanya skenario #003 yang pass, #001 dapat terbuka kembali.

### Files Changed By Patch

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

Reported diff size:

+47
-2

Reported commit:

7b030a0

Reported patch commit message/context:

Refunded revisions undercount paid totals

### Remaining Follow-up

Required proof before considering this fully verified:

1. Install/restore Composer dependencies if needed.
2. Run the focused feature test successfully.
3. Run or add regression test for #001 active refund normal note.
4. Run broader payment/note tests if available.
5. Confirm no settlement consumer now misclassifies active refunds as fully paid.

Minimum commands expected later:

composer install
php artisan test tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

Jika regression test #001 sudah ada, jalankan juga. Jika belum ada, tambahkan sebelum mempercayai patch settlement ini.

### Kesimpulan Update

Update ini mempatch issue double-subtraction #003 untuk revised notes dengan mengembalikan gross allocation basis pada note-level reader.

Namun, karena #001 disebabkan refund_component_allocations ditambahkan terlalu luas untuk active refunds, patch ini harus diperlakukan sebagai settlement-risky sampai skenario active-refund dan revised-historical-refund sama-sama dikunci oleh test.

Arah teknis mungkin tetap membutuhkan settlement semantics eksplisit nanti, agar active refunds dan historical consumed refunds tidak dipaksa melewati aggregate generik yang sama.

## Related Workflow Finding From Error Log 004

### Related Error Log

- 004-refunded-work-items-survive-revisions-and-inflate-stock.md

### Update

Update 3.

### Reason

A later audit report found a separate High severity issue in the same refund + note revision lifecycle.

Ini bukan root cause yang sama dengan #003.

- #003 is a settlement classification issue caused by mixed net/gross refund accounting after revised-note allocation replay.
- #004 is an inventory integrity issue caused by refund-referenced work_items surviving revision deletion and being processed repeatedly by inventory reversal.

Kedua temuan harus dipertimbangkan bersama ketika mengubah note revision, refund preservation, payment allocation replay, atau inventory reversal logic.

## Related Payment Replay Finding From Error Log 005

### Related Error Log

- 005-note-revision-silently-drops-overpaid-allocations.md

### Update

Update 4.

### Reason

A later audit report found a separate High severity issue in the same NoteReplacementPaymentAllocationReconciler / note revision payment replay area.

Ini bukan root cause yang sama dengan #003.

- #003 is about revised notes with historical refunds being misclassified as underpaid because refund can be subtracted twice.
- #005 is about downward note revisions silently truncating captured payment replay and hiding overpaid excess from allocation-based refund/reporting paths.

Perubahan berikutnya pada NoteReplacementPaymentAllocationReconciler harus memperhitungkan kedua kasus.

## Related Settlement Basis Finding From Error Log 008

### Related Error Log

- 008-legacy-paid-notes-can-be-paid-again.md

### Update

Update 5.

### Reason

A later audit report found a separate issue caused by inconsistent settlement basis.

Ini bukan root cause yang sama dengan #003.

- #003 is about component refund/history semantics during revised-note settlement.
- #008 is about component-only billing projection ignoring legacy payment_allocations during selected-row payment validation.

Keduanya harus dipertimbangkan ketika mengubah settlement readers/projections, karena kalkulasi component-only tidak aman ketika nota masih dapat memiliki legacy payment state.

## Verified Fix Update

### Update 6

Final verified local patch completed for this error log.

### Final Patch Scope

Changed files:

- `app/Ports/Out/Payment/CustomerRefundReaderPort.php`
- `app/Adapters/Out/Payment/DatabaseCustomerRefundReaderAdapter.php`
- `app/Application/Note/Policies/NotePaidStatusPolicy.php`
- `tests/Unit/Application/Note/Policies/NotePaidStatusPolicyTest.php`

### Final Patch Summary

The verified fix does not rely on the earlier reader-level gross-back approach.

The fix introduces an explicit current-refund settlement boundary:

- `CustomerRefundReaderPort::getTotalCurrentRefundedAmountByNoteId()`
- `DatabaseCustomerRefundReaderAdapter::getTotalCurrentRefundedAmountByNoteId()`
- `NotePaidStatusPolicy::isPaid()` now subtracts current refund only, not every historical refund ledger row.

This preserves the intended distinction between:

1. active/current refund that still reduces current settlement, and
2. historical refund already consumed by note replacement/revision replay.

### Why This Fix Addresses #003

`NoteReplacementPaymentAllocationReconciler::captureAllocatedAmounts()` captures carry-forward settlement by subtracting refund component allocations from previous payment component allocations.

`NoteReplacementPaymentAllocationReconciler::rebuild()` then writes the replayed `payment_component_allocations` using that net carry-forward amount.

Therefore, after revision:

- rebuilt payment component allocation already represents current net settlement
- historical `refund_component_allocations` remain as ledger/audit rows
- paid-status classification must not subtract those historical refunds again

The verified fix makes `NotePaidStatusPolicy` subtract only current refunds, preventing double subtraction for revised notes with historical refunds.

### Characterization Test Added

Added unit characterization in:

`tests/Unit/Application/Note/Policies/NotePaidStatusPolicyTest.php`

Focused test:

`test_it_treats_carry_forward_current_settlement_as_paid_without_subtracting_historical_refund_again`

Scenario:

- current carry-forward settlement: 200.000
- historical refund ledger: 100.000
- revised note total: 200.000
- expected paid status: true

This reproduces the #003 failure shape where the old policy returned unpaid because it subtracted the historical refund again.

### Regression Coverage For #001

Existing unit behavior remains covered:

`test_it_treats_note_as_not_paid_when_current_refund_reduces_settlement_below_total`

Scenario:

- current allocated settlement: 50.000
- current refund: 20.000
- note total: 50.000
- expected paid status: false

This guards against reopening #001-style behavior where active refunds are accidentally ignored and refunded notes remain classified as paid.

### Local Verification Proof

Targeted red proof before patch:

`php artisan test --filter=NotePaidStatusPolicyTest`

Result before patch:

- `1 failed, 3 passed`
- failing test: `test_it_treats_carry_forward_current_settlement_as_paid_without_subtracting_historical_refund_again`
- failure: `Failed asserting that false is true.`

Targeted proof after patch:

`php artisan test --filter=NotePaidStatusPolicyTest`

Result after patch:

- `4 passed (4 assertions)`

Limited related blast-radius proof:

`php artisan test tests/Unit/Application/Note/Policies/NotePaidStatusPolicyTest.php tests/Unit/Application/Note/Policies tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`

Result:

- `10 passed (31 assertions)`

Relevant Note + Payment blast-radius proof:

`php artisan test tests/Feature/Note tests/Feature/Payment`

Result:

- `158 passed (934 assertions)`

### Verification Status

Fixed with proof.

The earlier verification gap from missing `vendor/autoload.php` is no longer applicable to the final verified patch because local targeted and relevant blast-radius tests passed.

### Residual Gaps

This update fixes #003 settlement classification for revised notes with historical refunds at paid-status policy level.

It does not close separate error logs:

- `004-refunded-work-items-survive-revisions-and-inflate-stock.md`
- `005-note-revision-silently-drops-overpaid-allocations.md`
- `008-legacy-paid-notes-can-be-paid-again.md`
- `017-workspace-edit-payments-ignore-existing-note-payments.md`

Those remain separate lifecycle/payment replay/current-projection issues unless separately patched and verified.

### Commit / Push

Commit and push are intentionally outside this error-log update.

Owner handles git commit/push manually.
