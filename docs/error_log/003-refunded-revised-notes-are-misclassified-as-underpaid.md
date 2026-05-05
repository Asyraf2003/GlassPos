# 003 - Refunded revised notes are misclassified as underpaid

## Status

Patched, with verification gap.

Patch supplied later under report title: Refunded revisions undercount paid totals.

The focused feature test was added, but php artisan test could not run in the patch environment because vendor/autoload.php was missing.

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

That would risk restoring bug #001, where active refunds are counted as paid and then subtracted, making active refunds ineffective.

The safer technical direction is to introduce or use explicit settlement semantics that can distinguish current active refunds from historical refunds already consumed during note replacement/revision replay.

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

A valid fix must pass both tests. Passing only one means the code merely moved the bug from one side of the settlement model to the other, which is not engineering, just rearranging broken furniture.

## Kesimpulan

Laporan #003 valid sebagai High severity settlement/accounting logic issue.

Temuan ini menunjukkan bahwa patch area #001 belum cukup untuk seluruh lifecycle refund dan revised note. Active refund dan historical refund setelah revision tidak boleh diperlakukan dengan kalkulasi generic yang sama.

Akar masalah yang perlu diselesaikan adalah settlement semantics, bukan hanya agregasi query. Sistem perlu membedakan gross allocation, active refund, historical consumed refund, dan carried-forward settlement agar paid status, outstanding, mutation guard, dan inventory flow tetap konsisten.

## Patch Update

### Update 2

Patch supplied under report title:

Refunded revisions undercount paid totals

### Relationship Classification

Identical issue / same root cause as this file.

This update is not a new error log because it covers the same failure mode:

- revised note has historical refund_component_allocations
- NoteReplacementPaymentAllocationReconciler rebuilds payment_component_allocations on a net-of-refund basis
- note-level paid/outstanding/status logic subtracts refund_component_allocations again
- revised note becomes incorrectly underpaid/open

### Reason For Update

The original #003 entry had no patch. This update records the later patch and test addition.

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

That matches the revised note total and prevents the note from being misclassified as underpaid/open.

### Testing Reported

Command attempted:

php artisan test tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

Result:

Failed in the patch environment because dependencies were not installed.

Failure reason:

missing vendor/autoload.php

### Verification Status

Patch is recorded, but full test verification is not proven from this environment.

This is important: the presence of a test file is not proof that the test passed. Humanity keeps confusing intention with evidence, and software punishes that hobby.

### Regression Risk Against #001

This patch intentionally re-adds refund_component_allocations to the note-level allocated total.

That is the opposite movement from the patch recorded in:

001-refunds-counted-as-paid-in-note-totals.md

Therefore, this patch must be verified against both scenarios:

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

A valid final fix must pass both. If only scenario #003 passes, #001 may be reopened.

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

If a #001 regression test exists, run it too. If it does not exist, add it before trusting this settlement patch.

### Kesimpulan Update

This update patches the #003 double-subtraction issue for revised notes by restoring a gross allocation basis at the note-level reader.

However, because #001 was caused by refund_component_allocations being added too broadly for active refunds, this patch must be treated as settlement-risky until both active-refund and revised-historical-refund scenarios are locked by tests.

The technical direction may still need explicit settlement semantics later so active refunds and historical consumed refunds are not forced through the same generic aggregate.

## Related Workflow Finding From Error Log 004

### Related Error Log

- 004-refunded-work-items-survive-revisions-and-inflate-stock.md

### Update

Update 3.

### Reason

A later audit report found a separate High severity issue in the same refund + note revision lifecycle.

This is not the same root cause as #003.

- #003 is a settlement classification issue caused by mixed net/gross refund accounting after revised-note allocation replay.
- #004 is an inventory integrity issue caused by refund-referenced work_items surviving revision deletion and being processed repeatedly by inventory reversal.

Both findings should be considered together when changing note revision, refund preservation, payment allocation replay, or inventory reversal logic.
