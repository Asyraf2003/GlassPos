# 001 - Refunds counted as paid in note totals

## Status

Fixed with characterization proof and explicit residual global/browser gaps.

## Severity

High.

## Source

Audit report #001: Refunds counted as paid in note totals.

## Ringkasan Indonesia

Bug terjadi pada kalkulasi total alokasi pembayaran level nota.

Method:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- getTotalAllocatedAmountByNoteId(string $noteId)

Sebelum patch, method tersebut menjumlahkan:

- payment_component_allocations.allocated_amount_rupiah
- refund_component_allocations.refunded_amount_rupiah

Kemudian hasilnya dikembalikan sebagai total allocated amount untuk nota.

Masalahnya, refund reader lain juga menghitung refund_component_allocations sebagai total refunded amount. Service downstream seperti paid status dan outstanding resolver memakai pola:

net_paid = allocated - refunded

Karena refund sudah ditambahkan ke allocated lalu dikurangi lagi sebagai refunded, refund aktif menjadi netral. Dengan kata lain, refund tidak benar-benar mengurangi net paid/outstanding.

## Contoh Dampak

Skenario:

- Total nota: 50.000
- Payment component: 50.000
- Refund component aktif: 10.000

Perhitungan yang benar:

- allocated = 50.000
- refunded = 10.000
- net_paid = 40.000
- outstanding = 10.000

Perhitungan saat bug:

- allocated = 60.000
- refunded = 10.000
- net_paid = 50.000
- outstanding = 0

Akibatnya nota bisa tetap dianggap lunas/closed walaupun ada refund aktif yang seharusnya membuka outstanding atau koreksi pembayaran.

## Jalur Risiko

Authenticated cashier/admin dapat membuat refund melalui route refund nota.

Refund tersebut membuat row pada refund_component_allocations.

Row refund yang sama kemudian:
1. ikut dihitung sebagai allocated amount oleh DatabasePaymentAllocationReaderAdapter
2. ikut dihitung sebagai refunded amount oleh refund reader

Paid-status dan outstanding resolver akhirnya menerima nilai settlement yang salah.

## Dampak Bisnis

Ini adalah financial-integrity issue.

Dampak utama:

- nota yang sudah direfund bisa tetap dianggap lunas
- outstanding bisa menjadi 0 padahal seharusnya masih ada
- follow-up payment atau correction flow bisa terblokir
- laporan/status nota bisa misleading
- audit settlement menjadi tidak akurat

Severity High tepat karena bug menyentuh uang, status nota, dan alur pembayaran. Tidak Critical karena membutuhkan authenticated role dan tidak melibatkan auth bypass, secret leak, code execution, atau cross-tenant takeover.

## Root Cause

Reader level nota mencampur dua konsep berbeda:

- allocated payment: uang yang dialokasikan dari pembayaran
- refunded amount: uang yang sudah dikembalikan/refund

Refund tidak boleh dihitung sebagai allocated payment umum pada reader ini, karena settlement aktif sudah punya jalur pengurangan refund sendiri.

## Patch Summary

Patch minimal diterapkan pada:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php

Perubahan:

- hapus query/agregasi refund_component_allocations dari getTotalAllocatedAmountByNoteId()
- ubah kondisi component-backed agar hanya melihat payment_component_allocations
- return hanya componentTotal untuk component-backed note
- fallback legacy payment_allocations tetap dipertahankan
- method getTotalAllocatedAmountByCustomerPaymentIdAndNoteId() tidak diubah

## Scope In

- Note-level allocated total calculation.
- Component-backed note payment allocation reader.
- Preservation of legacy payment_allocations fallback.

## Scope Out

- Customer-payment scoped allocation reader.
- Refund recording flow.
- Refund reader.
- Paid status policy.
- Outstanding resolver.
- Reports/exports/projections.
- Characterization/regression test creation.

## Proof Dari Patch Session

User reported these commands passed/executed:

- php -l app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- git status --short
- git diff -- app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- git add app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php && git commit -m "Fix note allocated total to exclude refunds"

Commit message:

Fix note allocated total to exclude refunds

Changed file:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php

Reported diff size:

+2
-6

## Remaining Follow-up

Recommended next audit/test step:

Create a characterization test for:

- note total 50.000
- component payment 50.000
- component refund 10.000
- expected allocated = 50.000
- expected refunded = 10.000
- expected net paid = 40.000
- expected outstanding = 10.000
- expected note is not treated as fully paid after active refund

Test ini harus mengunci perilaku settlement agar bug yang sama tidak kembali lagi dengan bentuk berbeda, karena bug memang punya hobi menyebalkan seperti itu.

## Related Follow-up Discovered Later

### Related Error Log

- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Update

Update 2.

### Reason

A later audit report found a directly related but non-identical edge case in the same settlement area.

Patch untuk #001 menghapus refund_component_allocations dari allocated total level nota agar active refund tidak ikut dihitung sebagai paid. Laporan #003 menunjukkan bahwa perilaku ini dapat meng-under-count revised notes ketika NoteReplacementPaymentAllocationReconciler sudah membangun ulang payment_component_allocations net-of-refund sementara historical refund_component_allocations masih tersisa.

Artinya #001 dan #003 harus dipertimbangkan bersama sebelum perubahan settlement berikutnya. Fix yang valid harus mempertahankan behavior yang benar untuk keduanya:

1. active refund normal notes
2. revised notes with historical refunds already consumed during replacement/reconciliation

Do not solve one by blindly reverting into the other.

## Related Patch Update From Error Log 003

### Related Error Log

- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Update

Update 3.

### Reason

A later patch for #003 re-added refund_component_allocations into DatabasePaymentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId() to restore gross allocation basis for revised notes with historical refunds.

Ini relevan langsung dengan #001 karena #001 awalnya disebabkan active refund ikut ditambahkan ke allocated totals lalu dikurangi lagi, sehingga active refund menjadi tidak efektif.

Future verification must prove both:

1. active refunds on normal notes reduce net paid/outstanding correctly
2. historical refunds on revised notes are not double-subtracted

Jika keduanya tidak dites, fix hanya mungkin memindahkan bug settlement antara #001 dan #003.

## Update 2026-05-09 - Characterization closure after #001/#003 settlement conflict review

Current source/test review confirmed that the earlier `Patched` document status needed explicit verification before closure because #001 and #003 share the same settlement arithmetic boundary.

The #001 historical failure mode was:

- active refund component rows were counted as allocated payment at note level;
- the same refund rows were also counted by the refund reader;
- downstream settlement used `allocated - refunded`;
- active refund therefore became neutral instead of reducing net paid/outstanding.

Current source reality:

- `app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`
  - `getTotalAllocatedAmountByNoteId()` now sums `payment_component_allocations.allocated_amount_rupiah` plus legacy `payment_allocations.amount_rupiah`.
  - It does not include `refund_component_allocations.refunded_amount_rupiah` in note-level allocated total.
- `app/Adapters/Out/Payment/DatabaseCustomerRefundReaderAdapter.php`
  - `getTotalCurrentRefundedAmountByNoteId()` counts current component refunds only when a matching payment component still exists.
  - Historical refund components without a current matching payment component are excluded from current refund settlement.
- `app/Application/Note/Policies/NotePaidStatusPolicy.php`
  - paid status uses note-level allocated amount minus current refunded amount.
- `app/Application/Note/Services/NoteOutstandingPaymentAmountResolver.php`
  - outstanding resolution uses note-level allocated amount minus total refunded amount for full-note outstanding calculation.

No production source patch was made in this #001 closure session. The source had already been patched before this verification pass, so a current-source RED failure was not available without artificially reverting production behavior. This closure therefore uses characterization/regression proof for the historical failure mode and explicitly records that RED-before-patch is not applicable to this session.

Test files added/updated for characterization:

- `tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php`
- `tests/Feature/Note/NoteOutstandingPaymentAmountResolverFeatureTest.php`

Characterization proof:

- `DatabasePaymentAllocationReaderAdapterFeatureTest::test_note_level_allocated_total_excludes_active_component_refunds`
  - note total: 50.000
  - payment component allocation: 50.000
  - active refund component allocation: 10.000
  - expected note-level allocated total: 50.000
  - this locks the #001 historical failure mode so refund components cannot re-enter note-level allocated payment totals.
- `NoteOutstandingPaymentAmountResolverFeatureTest::test_active_refund_reopens_outstanding_amount_for_normal_note`
  - note total: 50.000
  - payment component allocation: 50.000
  - active refund component allocation: 10.000
  - expected net paid: 40.000
  - expected outstanding: 10.000
  - this proves an active refund on a normal note reopens outstanding balance instead of leaving the note effectively paid.

Focused/blast-radius proof:

`php artisan test tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php tests/Feature/Note/NoteOutstandingPaymentAmountResolverFeatureTest.php tests/Unit/Application/Note/Policies/NotePaidStatusPolicyTest.php tests/Unit/Application/Note/Services/NoteOperationalStatusResolverTest.php tests/Feature/Payment/RecordCustomerRefundFeatureTest.php tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php tests/Feature/Note/CashierClosedReplacementOutstandingPaymentFeatureTest.php`

Result:

- 16 passed
- 66 assertions

Settlement matrix covered:

- active refund normal note reduces net paid/outstanding correctly;
- note-level allocated amount excludes active refund components;
- current refund paid status semantics remain correct;
- revised historical refund is not subtracted again from carry-forward current settlement;
- refund component recording remains compatible with pair allocation limits;
- revision-after-refund historical anchor behavior remains protected;
- closed replacement outstanding payment remains payable after replacement.

UI/Blade decision:

No UI/Blade file was changed for #001. The closure is settlement arithmetic and backend characterization only.

Native JS decision:

No native JavaScript file was changed for #001.

Security decision:

No authorization, authentication, or route guard was changed for #001. The security-relevant boundary remains server-side settlement calculation; UI hiding is not part of this closure.

Audit/log/redaction decision:

No new audit writer, log path, or sensitive logging surface was introduced. The patch is test/documentation characterization only and does not add a new successful financial mutation path.

Residual gaps:

- Full Note + Payment suite was not rerun in this #001 closure session.
- Full global suite was not run in this #001 closure session.
- Browser/manual QA was not run.
- Reporting/export paths were not re-audited by this #001 closure.
- Seeder remediation remains future scope and is not part of this workflow closure.
- True parallel concurrency stress belongs to the later concurrency slice, not this #001 settlement characterization closure.

Verification status:

Fixed with characterization proof for #001 active refund settlement and #003 historical refund compatibility boundaries, with residual global/browser/reporting gaps explicitly recorded.
