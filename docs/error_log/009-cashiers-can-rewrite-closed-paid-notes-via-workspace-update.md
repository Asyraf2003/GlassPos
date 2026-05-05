# 009 - Cashiers can rewrite closed paid notes via workspace update

## Status

Patched, with verification gap.

Patch supplied and regression test updated, but the focused test could not run in the patch environment because vendor/autoload.php was missing.

## Severity

High.

## Source

Audit report #009: Cashiers can rewrite closed paid notes via workspace update.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 004-refunded-work-items-survive-revisions-and-inflate-stock.md
- 005-note-revision-silently-drops-overpaid-allocations.md
- 006-client-controlled-price-basis-bypasses-minimum-price-checks.md
- 007-admin-note-edit-page-exposes-stored-xss.md

### Jenis Keterkaitan

Direct note workspace/revision relationship with #004, #005, and #006.

Indirect shared workspace surface relationship with #007.

### Alasan

Laporan #009 berada pada route/workflow note workspace revision.

- #004 membahas inventory corruption pada revision karena stale refunded work_items dan duplicate reversal.
- #005 membahas payment allocation replay pada downward note revision.
- #006 membahas minimum price bypass pada store-stock line saat revision.
- #009 membahas authorization regression: cashier bisa PATCH closed note melalui workspace update karena route salah masuk view-only guard.

#007 juga berada pada workspace surface, tetapi root cause-nya stored XSS pada rendering admin edit workspace, bukan authorization/mutation guard.

Karena #009 adalah authorization bypass dengan root cause, file, dan patch berbeda, laporan ini dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #009.

Alasan update:

- Laporan menunjukkan cashier dapat rewrite closed paid notes lewat PATCH workspace update.
- Patch sudah diterapkan di EnsureCashierNoteAccess.
- Regression test diubah agar closed-note PATCH forbidden dan state tetap unchanged.
- Verification masih gap karena test gagal dijalankan akibat missing vendor/autoload.php.

## Ringkasan Indonesia

Bug terjadi pada middleware cashier note access.

Route terdampak:

cashier.notes.workspace.update

Route ini adalah PATCH state-changing route yang mengarah ke StoreNoteRevisionController. Controller tersebut memanggil CreateNoteRevisionHandler, lalu handler membuat revision dan langsung menerapkannya sebagai active root-note replacement.

Sebelum patch, middleware EnsureCashierNoteAccess memasukkan route ini ke branch view-only:

- cashier.notes.show
- cashier.notes.workspace.edit
- cashier.notes.workspace.update

Branch tersebut hanya memanggil:

assertCanView()

Masalahnya, assertCanView() hanya mengecek akses lihat/date-window. Ia tidak menolak closed note.

Guard yang seharusnya dipakai untuk mutation adalah:

assertCanMutateOpenNote()

Guard ini memanggil assertCanView(), lalu menolak note yang sudah closed.

Akibatnya, cashier dengan transaction-entry access dapat mengirim PATCH ke closed note dalam cashier date window dan mengubah active note state tanpa reopen/admin correction flow.

## Dampak

Dampak utama:

- cashier dapat mengubah closed paid note
- customer/header fields bisa berubah
- total_rupiah bisa berubah
- work_items bisa dihapus/diganti
- payment allocations bisa dihapus/dibangun ulang
- inventory movement bisa reverse/re-issue
- revision chain berubah
- closed-note immutability/correction policy rusak

Ini authorization dan financial/inventory integrity issue.

Severity High tepat karena closed paid note adalah record finansial yang seharusnya hanya berubah lewat correction/reopen flow yang terkendali. Tidak Critical karena membutuhkan authenticated cashier, transaction-entry access, valid CSRF/session, dan note dalam today/yesterday cashier date window.

## Jalur Risiko

Workflow risiko:

1. User login sebagai cashier/kasir.
2. User punya transaction-entry access.
3. Target note sudah closed/paid dan masih dalam cashier date window.
4. User mengirim PATCH ke cashier.notes.workspace.update.
5. EnsureCashierNoteAccess salah memasukkan workspace.update ke view-only branch.
6. Middleware hanya memanggil assertCanView().
7. Closed note tidak ditolak.
8. StoreNoteRevisionController menerima payload valid.
9. CreateNoteRevisionHandler membuat revision dan apply replacement.
10. ApplyNoteRevisionAsActiveReplacement mengubah root note aktif:
    - update header
    - delete/recreate work items
    - update total
    - rebuild payment allocations
    - sync projection
11. Inventory bisa reverse/re-issue melalui work item persister.

## Root Cause

Root cause:

State-changing route diperlakukan seperti read/edit-page route.

cashier.notes.workspace.update adalah PATCH mutation route, bukan view route. Route ini tidak boleh cukup hanya dengan assertCanView().

Untuk cashier flow:

- view/show/edit page boleh memakai assertCanView()
- mutation terhadap note harus memakai assertCanMutateOpenNote()
- closed paid note mutation harus lewat explicit admin/correction/reopen flow, bukan normal workspace update

## Patch Summary

Patch diterapkan pada:

app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php

Perubahan:

- cashier.notes.workspace.update dihapus dari view-only branch
- PATCH workspace update sekarang jatuh ke else branch
- else branch memanggil assertCanMutateOpenNote()
- assertCanMutateOpenNote() menolak closed note dengan DomainException
- middleware abort 403 saat guard menolak

Test diubah pada:

tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php

Test intent setelah patch:

- cashier masih bisa view closed note jika allowed by date-window
- cashier PATCH workspace replacement untuk closed note mendapat 403
- notes row tetap unchanged
- work_items lama tetap ada
- note_revisions revision_number 2 tidak dibuat
- payment_component_allocations lama tetap ada
- tidak ada replacement state yang tersimpan

## Scope In

- Cashier note workspace update authorization.
- Closed note mutation guard.
- EnsureCashierNoteAccess route classification.
- Prevention of cashier rewrite on closed paid notes.
- Regression coverage for forbidden closed-note PATCH.

## Scope Out

- Admin correction/reopen policy.
- Admin route behavior.
- UI hiding/showing edit buttons.
- Full note revision correctness.
- Inventory reversal correctness from #004.
- Payment replay correctness from #005.
- Price floor correctness from #006.
- Stored XSS rendering issue from #007.
- Full Laravel test pass, because dependencies were missing.

## Proof Dari Patch Session

User reported:

- patch removes cashier.notes.workspace.update from view-only branch
- PATCH workspace updates now go through assertCanMutateOpenNote()
- assertCanMutateOpenNote() blocks closed notes
- regression feature test updated
- committed on branch work with commit:
  ba200c4

Changed files:

app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php
tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php

Reported diff size:

+10
-24

Testing reported:

php artisan test --filter=CashierClosedNoteWorkspaceReplacementSubmitFeatureTest

Result:

Failed in patch environment.

Failure reason:

vendor/autoload.php is missing; dependencies are not installed.

## Verification Gap

Regression test was updated but did not pass in the patch environment.

Missing proof:

- closed-note PATCH returns 403
- no note header/total change
- no work item replacement
- no payment allocation rebuild
- no new note revision
- cashier open-note mutation still works if intended
- cashier view/edit page access still follows date-window policy

## Recommended Follow-up

Minimum verification command:

composer install
php artisan test --filter=CashierClosedNoteWorkspaceReplacementSubmitFeatureTest

Recommended additional tests:

1. Cashier can view closed note inside allowed date window.
2. Cashier cannot PATCH closed note workspace update.
3. Cashier can PATCH open note workspace update when allowed.
4. Admin correction/reopen flow remains the explicit path for closed paid note mutation.
5. No inventory/payment side effects occur after forbidden cashier PATCH.

## Kesimpulan

Laporan #009 valid sebagai High severity authorization regression.

Bug sebelumnya memasukkan route PATCH cashier.notes.workspace.update ke branch assertCanView(), padahal route itu melakukan mutation besar pada active note. Akibatnya cashier dapat rewrite closed paid notes tanpa reopen/correction flow.

Patch minimal sudah benar untuk root cause langsung: route mutation dikembalikan ke assertCanMutateOpenNote(). Namun test belum terbukti pass karena dependency environment tidak tersedia, jadi status tetap patched with verification gap.

## Related Workspace Revision Concurrency Finding From Error Log 010

### Related Error Log

- 010-revision-reallocation-can-lose-concurrent-payments.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity issue in the note workspace revision path.

This is not the same root cause as #009.

- #009 is about cashier authorization allowing closed-note workspace PATCH mutation.
- #010 is about concurrent payment allocation being lost during legitimate revision/payment interleaving.

Both findings involve workspace revision, but #009 is route authorization while #010 is transactional serialization.

## Related Settled-Note Revision Guard Finding From Error Log 011

### Related Error Log

- 011-cashier-revision-path-mutates-settled-note-state.md

### Update

Update 3.

### Reason

A later audit report found a separate High severity authorization issue in the cashier workspace revision path.

This is not the same root cause as #009.

- #009 is about cashier.notes.workspace.update being routed through view-only access, allowing mutation of stored closed notes.
- #011 is about CreateNoteRevisionHandler missing EditableWorkspaceNoteGuard, allowing mutation of payment-derived settled notes whose stored note_state is still open.

Both findings must be considered together. Cashier workspace revision requires both route-level mutation guard and application-level editability guard.
