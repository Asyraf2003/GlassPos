# 009 - Cashiers can rewrite closed paid notes via workspace update

## Status

Fixed with proof.

Cashier closed-note workspace PATCH mutation is now routed through the mutation guard, and the local targeted plus focused blast-radius tests pass.

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
- Initial patch-session verification was incomplete in the earlier environment.

### Update 6

Local verification completed on main at HEAD e7fe4c41.

Alasan update:

- Current HEAD before #009 patch was 86049acd.
- Source was still vulnerable before patch because cashier.notes.workspace.update was classified inside the view-only ensureCanView() branch.
- RED characterization proved the vulnerability: expected response status code [403] but received 302.
- Minimal patch removed cashier.notes.workspace.update from the ensureCanView() branch.
- cashier.notes.workspace.update now falls through to ensureCanMutateOpenNote().
- Syntax verification passed for app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php.
- Targeted GREEN verification passed: 1 passed (7 assertions).
- Focused blast-radius verification passed: 7 passed (48 assertions).
- Repository state after source patch was main at e7fe4c41, aligned with origin/main.

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
- Full global suite / make verify full green remains out of scope for #009 closure because audit-lines is deferred.

## Proof Dari Patch Session

Source patch:

app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php

Minimal source change:

- cashier.notes.workspace.update removed from the view-only ensureCanView() branch.
- PATCH workspace update now falls through to ensureCanMutateOpenNote().
- Closed notes are rejected by the mutation guard and return 403 through the middleware.

Characterization RED proof:

Command:

php artisan test --filter=CashierClosedNoteWorkspaceReplacementSubmitFeatureTest

Result:

Expected response status code [403] but received 302.

Meaning:

- Closed-note PATCH still reached the successful redirect path before the fix.
- The route was still treated as view-only access instead of mutation access.

Syntax proof:

Command:

php -l app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php

Result:

No syntax errors detected.

Targeted GREEN proof:

Command:

php artisan test --filter=CashierClosedNoteWorkspaceReplacementSubmitFeatureTest

Result:

PASS: 1 passed (7 assertions).

Focused blast-radius proof:

Command:

php artisan test tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php tests/Feature/Note/CashierEditPageUsesCurrentRevisionFeatureTest.php tests/Feature/Note/CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.php tests/Feature/Note/CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.php tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php

Result:

PASS: 7 passed (48 assertions).

Passing focused coverage:

- CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.
- CashierNoteRevisionSubmitFeatureTest.
- CashierEditPageUsesCurrentRevisionFeatureTest.
- CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.
- CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.
- UpdateTransactionWorkspaceFeatureTest.

Repository proof:

- Branch: main.
- HEAD after source patch: e7fe4c41.
- origin/main aligned with e7fe4c41.
- Source diff at e7fe4c41: EnsureCashierNoteAccess.php changed by 1 deletion.

## Verification Gap

Targeted and focused cashier workspace behavior is verified locally.

Remaining gaps:

- Full global suite not reported.
- Browser/manual QA not reported.
- make verify full green not claimed because audit-lines remains deferred.
- Related issues #011, #015, #018, #019, #020, #022, #027, and #029 remain separate and are not fixed by #009.
- Admin correction/reopen policy was not evaluated in this slice.

## Recommended Follow-up

After docs update:

1. Run markdown/document sanity checks.
2. Review the #009 docs diff.
3. Owner commits and pushes manually.

Do not expand this slice into #011, #018, #020, or UI visibility work without separate proof and scope.

## Kesimpulan

Laporan #009 valid sebagai High severity authorization regression.

Bug sebelumnya memasukkan route PATCH cashier.notes.workspace.update ke branch assertCanView(), padahal route itu melakukan mutation besar pada active note. Akibatnya cashier dapat rewrite closed paid notes tanpa reopen/correction flow.

Patch minimal sudah benar dan sekarang locally verified: route mutation dikembalikan ke ensureCanMutateOpenNote(), RED characterization membuktikan bypass lama dengan expected 403 tetapi actual 302, lalu targeted dan focused blast-radius tests pass.

## Related Workspace Revision Concurrency Finding From Error Log 010

### Related Error Log

- 010-revision-reallocation-can-lose-concurrent-payments.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity issue in the note workspace revision path.

Ini bukan root cause yang sama dengan #009.

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

Ini bukan root cause yang sama dengan #009.

- #009 is about cashier.notes.workspace.update being routed through view-only access, allowing mutation of stored closed notes.
- #011 is about CreateNoteRevisionHandler missing EditableWorkspaceNoteGuard, allowing mutation of payment-derived settled notes whose stored note_state is still open.

Both findings must be considered together. Cashier workspace revision requires both route-level mutation guard and application-level editability guard.

## Related Refunded-Note Edit Visibility Finding From Error Log 015

### Related Error Log

- 015-refunded-notes-expose-edit-workspace.md

### Update

Update 4.

### Reason

A later audit report found a separate issue in the note workspace editability surface.

Ini bukan root cause yang sama dengan #009.

- #009 is about server-side cashier workspace update authorization for closed notes.
- #015 is about UI rendering an Edit button for refunded notes because can_edit_workspace was not checked.

Both findings must be considered together. UI visibility should match editability flags, but server-side mutation guards remain mandatory.

## Related Refunded-State Guard Finding From Error Log 018

### Related Error Log

- 018-refunded-notes-bypass-cashier-closed-note-guards.md

### Update

Update 5.

### Reason

A later audit report found a separate High severity issue in cashier note mutation guards.

Ini bukan root cause yang sama dengan #009.

- #009 is about closed notes being mutable because workspace.update was routed through view-only access.
- #018 is about refunded notes being mutable because guard logic only rejected isClosed() and did not reject isRefunded().

Both findings show cashier mutation guards must deny all terminal note states, not only one route/state combination.

## Related #019 - Cashiers can list historical closed notes by date

#019 is an indirect cashier access-boundary relation. #009 concerns unauthorized mutation through workspace update, while #019 concerns read-only disclosure of historical closed notes through the cashier table endpoint using a client-controlled date anchor and `openOnly=false`.

## Related #020 - Admin note actions bypass transaction capability

#020 is related through note mutation authorization. #009 covers cashier workspace mutation bypass for closed paid notes, while #020 covers admin payment/refund/row/workspace mutation routes missing the transaction-entry capability gate.
