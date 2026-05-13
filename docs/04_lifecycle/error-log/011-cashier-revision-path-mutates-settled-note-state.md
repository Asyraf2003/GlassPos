# 011 - Cashier revision path mutates settled note state

## Status

Fixed with proof.

Targeted behavior test and focused blast-radius tests passed. Full `make verify` green is not claimed because the audit-lines blocker is deferred.

## Severity

High.

## Source

Audit report #011: Cashier revision path mutates settled note state.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
- 010-revision-reallocation-can-lose-concurrent-payments.md
- 005-note-revision-silently-drops-overpaid-allocations.md

### Jenis Keterkaitan

Direct authorization/workspace mutation relationship with #009.

Direct file/merge relationship with #010.

Direct note revision financial-integrity relationship with #005.

### Alasan

Laporan #011 dan #009 sama-sama membahas cashier workspace revision path yang dapat memutasi note yang seharusnya tidak boleh diedit lewat flow biasa.

Namun keduanya tidak identik.

- #009 membahas closed note berdasarkan stored note_state. Root cause-nya route cashier.notes.workspace.update salah masuk view-only branch di EnsureCashierNoteAccess.
- #011 membahas settled/payment-derived close note yang stored note_state-nya masih open. Root cause-nya CreateNoteRevisionHandler tidak memanggil EditableWorkspaceNoteGuard::assertEditable() sebelum mutation.

Laporan #011 juga berkaitan langsung dengan #010 karena patch #010 sebelumnya mengubah CreateNoteRevisionHandler agar memakai getByIdForUpdate() untuk serialization. Patch #011 juga menyentuh handler yang sama. Final code harus mempertahankan keduanya:

1. EditableWorkspaceNoteGuard::assertEditable()
2. NoteReaderPort::getByIdForUpdate()

Jika salah satu hilang saat merge, satu kelas bug bisa kembali.

Laporan #011 berkaitan dengan #005 karena sama-sama berada pada note revision flow yang memengaruhi root note total, work items, payment allocations, dan financial integrity.

## Update Log

### Update 1

Initial audit log entry untuk laporan #011.

Alasan update:

- Laporan menunjukkan cashier revision path dapat memutasi settled note state jika stored note_state masih open.
- Patch awal menambahkan EditableWorkspaceNoteGuard ke CreateNoteRevisionHandler.
- Guard dipanggil sebelum root-note mutation path.
- Verification saat itu masih gap karena hanya php -l yang dilaporkan pass.

### Update 6

Status diperbarui dari patched-with-gap menjadi fixed-with-proof.

Current verification sequence started from previously proven HEAD e0a2a135 and is now present in HEAD 383f544b, with 383f544b aligned to origin/main and origin/HEAD.

Source before the #011 patch already preserved the #010 lock:

- `NoteReaderPort::getByIdForUpdate()`

But source did not yet have:

- `EditableWorkspaceNoteGuard` import
- `EditableWorkspaceNoteGuard` constructor dependency
- `assertEditable()` call before revision mutation

RED characterization sequence:

1. Initial generic assertion was a false positive because the request failed for an unrelated reason.
2. Exact session-error assertion exposed a fixture gap:
   `Current revision untuk note root tidak ditemukan.`
3. After seeding the current revision with `seedServiceOnlyCurrentRevision(...)`, RED became valid:
   expected redirect to `/cashier/notes/note-1/workspace/edit`, but actual redirect was `/cashier/notes/note-1`, proving the cashier PATCH revision path still allowed open-but-settled note mutation.

Minimal source patch:

- added `EditableWorkspaceNoteGuard` import
- added `EditableWorkspaceNoteGuard` constructor dependency
- kept #010 `getByIdForUpdate(trim($noteRootId))`
- called `$this->guard->assertEditable($root->id())` after the locked root read and before current revision resolution / mutation

Targeted GREEN proof reported:

- `php -l app/Application/Note/UseCases/CreateNoteRevisionHandler.php` PASS
- `php -l tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php` PASS
- `php artisan test --filter=CashierNoteRevisionSubmitFeatureTest` PASS
- Result summary: 2 tests / 14 assertions

Focused blast-radius proof reported:

- `CashierNoteRevisionSubmitFeatureTest`
- `EditableWorkspaceNoteGuardFeatureTest`
- `CashierClosedNoteWorkspaceReplacementSubmitFeatureTest`
- `CashierNoteRevisionSmokeTest`
- `CashierNoteRevisionCleanupFeatureTest`
- `UpdateTransactionWorkspaceFeatureTest`

Result summary: 13 tests / 55 assertions.

Merge-safety proof:

- #010 row-lock read is preserved through `getByIdForUpdate(trim($noteRootId))`
- #011 editability guard is present through `$this->guard->assertEditable($root->id())`
- mutation remains blocked before current revision resolution and active replacement apply

Remaining verification gaps are tracked below and do not block this #011 closure.

## Ringkasan Indonesia

Bug terjadi pada cashier PATCH workspace revision path.

Route:

cashier.notes.workspace.update

mengarah ke:

StoreNoteRevisionController -> CreateNoteRevisionHandler

Sebelum patch, CreateNoteRevisionHandler membuat revision dan langsung menerapkan replacement ke root note tanpa memanggil:

EditableWorkspaceNoteGuard::assertEditable()

Padahal normal workspace mutation path melalui UpdateTransactionWorkspaceHandler sudah memakai guard tersebut.

Perbedaannya penting:

- CashierNoteAccessGuard::assertCanMutateOpenNote() hanya mengecek date-window dan stored note_state.
- EditableWorkspaceNoteGuard::assertEditable() mengecek payment-derived operational status melalui status resolver.

Jadi note yang secara pembayaran sudah settled/close, tetapi stored note_state masih open, dapat lolos middleware cashier dan dimutasi oleh revision handler.

## Dampak

Dampak utama:

- cashier bisa mengubah settled note yang seharusnya tidak editable lewat workspace biasa
- root note header bisa berubah
- work_items bisa diganti
- total_rupiah bisa berubah
- payment allocation dan refund consistency bisa rusak
- inventory bisa reverse/re-issue melalui work-item persister
- projection/history bisa berubah
- paid/settled record dapat berubah di luar correction/reopen flow

Ini authorization dan financial/inventory integrity issue.

Severity High tepat karena settled note adalah financial record yang harus dilindungi. Tidak Critical karena butuh authenticated cashier/admin, transaction capability, date-window, dan target note dengan stored note_state open.

## Jalur Risiko

Workflow risiko:

1. User login sebagai kasir/cashier.
2. User punya transaction-entry capability.
3. Target note berada dalam cashier date window.
4. Target note memiliki stored note_state = open.
5. Secara payment-derived operational status, note sudah settled/close atau tidak boleh diedit lewat workspace.
6. User submit PATCH ke cashier.notes.workspace.update.
7. Middleware CashierNoteAccessGuard hanya mengecek stored note_state dan date-window.
8. StoreNoteRevisionController memanggil CreateNoteRevisionHandler.
9. Handler lama tidak memanggil EditableWorkspaceNoteGuard::assertEditable().
10. Handler membuat revision dan apply replacement.
11. Root note/work items/total/projection/inventory dapat berubah.

## Root Cause

Root cause:

Revision handler tidak memakai editability guard yang sudah ada untuk normal workspace mutation.

Ada dua level guard yang berbeda:

1. CashierNoteAccessGuard
   - route/middleware guard
   - cek cashier date-window
   - cek stored note_state closed/open
   - tidak cukup untuk payment-derived settlement state

2. EditableWorkspaceNoteGuard
   - application/service guard
   - cek apakah note editable berdasarkan operational/payment-derived status
   - menolak note close dari perspektif settlement

Bug terjadi karena CreateNoteRevisionHandler melewati guard #2.

## Patch Summary

Patch diterapkan pada:

app/Application/Note/UseCases/CreateNoteRevisionHandler.php

Perubahan:

- import `App\Application\Note\Services\EditableWorkspaceNoteGuard`
- constructor menerima dependency `EditableWorkspaceNoteGuard`
- `createRevisionAndApply()` tetap membaca root note memakai:
  `$this->notes->getByIdForUpdate(trim($noteRootId))`
- setelah root note ditemukan, handler memanggil:
  `$this->guard->assertEditable($root->id())`
- call dilakukan setelah locked root read dan sebelum current revision resolution / mutation path

Efek patch:

- cashier revision behavior disejajarkan dengan guarded workspace update flow
- settled/payment-derived close note ditolak sebelum mutation
- root-note mutation tidak berjalan jika note tidak editable
- #010 same-note serialization lock tetap dipertahankan

## Merge-Safety Note Dengan Error Log 010

Error log #010 juga mengubah CreateNoteRevisionHandler untuk memakai locked note read:

`getByIdForUpdate()`

Final expected safe behavior untuk CreateNoteRevisionHandler adalah:

1. transaction dimulai
2. root note dibaca dengan `getByIdForUpdate()` untuk serialization
3. `EditableWorkspaceNoteGuard::assertEditable()` dipanggil memakai locked root note id
4. current revision resolution, revision creation, dan active replacement hanya berjalan setelah lock + guard aman

Jika patch #011 diterapkan di branch yang belum mengandung patch #010, atau merge conflict diselesaikan asal-asalan, ada risiko `getByIdForUpdate()` dari #010 hilang.

Final code tidak boleh kembali ke plain `getById()` jika #010 sudah menjadi keputusan final.

## Scope In

- CreateNoteRevisionHandler.
- Cashier revision mutation path.
- Payment-derived editability guard.
- Settled/open-state note protection.
- Root note mutation prevention before revision apply.

## Scope Out

- Middleware route classification bug from #009.
- Concurrency serialization bug from #010.
- Payment replay truncation from #005.
- Full settlement model redesign.
- Admin correction/reopen flow.
- Full HTTP E2E test.
- Production data audit for stale open settled notes.

## Proof Dari Patch Session

Repository state after source patch verification:

- current branch: `main`
- current HEAD: `383f544b`
- remote alignment: `383f544b = HEAD = origin/main = origin/HEAD`
- commit 1751 changed production source:
  `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`
- working tree status output was blank before docs update, indicating clean state

Production source anchors proven:

- `use App\Application\Note\Services\EditableWorkspaceNoteGuard;`
- `private readonly EditableWorkspaceNoteGuard $guard,`
- `$root = $this->notes->getByIdForUpdate(trim($noteRootId));`
- `$this->guard->assertEditable($root->id());`

Relevant test anchors proven in current checkout:

- `test_workspace_update_route_rejects_open_note_that_is_already_settled`
- `seedServiceOnlyCurrentRevision`
- exact session error:
  `Nota close tidak boleh diedit lewat workspace.`

RED proof:

- generic assertion attempt was not accepted as valid because failure was unrelated
- exact message assertion first exposed fixture gap:
  `Current revision untuk note root tidak ditemukan.`
- after fixture fix, valid RED showed expected redirect to workspace edit, but actual redirect went to cashier note show, proving mutation succeeded before the guard patch

Targeted GREEN proof:

- source syntax PASS
- test syntax PASS
- `CashierNoteRevisionSubmitFeatureTest` PASS
- 2 tests / 14 assertions

Focused blast-radius proof:

- `CashierNoteRevisionSubmitFeatureTest` PASS
- `EditableWorkspaceNoteGuardFeatureTest` PASS
- `CashierClosedNoteWorkspaceReplacementSubmitFeatureTest` PASS
- `CashierNoteRevisionSmokeTest` PASS
- `CashierNoteRevisionCleanupFeatureTest` PASS
- `UpdateTransactionWorkspaceFeatureTest` PASS
- 13 tests / 55 assertions

Changed production file:

app/Application/Note/UseCases/CreateNoteRevisionHandler.php

Relevant test file:

tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php

## Verification Gap

Remaining gaps:

- full global suite was not reported
- browser/manual QA was not reported
- full `make verify` green is not claimed because audit-lines is deferred
- admin correction/reopen flow was not verified by this #011 slice
- #018 refunded-note guard remains a separate residual item
- #020 admin capability remains a separate residual item
- #005 payment replay behavior was not changed by this #011 slice

The prior behavior-test gap for cashier PATCH revision against open-but-settled note is closed by the targeted and focused proof above.

## Recommended Follow-up

Recommended follow-up after this docs closure:

- commit this #011 docs update after review
- keep #010 `getByIdForUpdate()` and #011 `assertEditable($root->id())` together during future merge conflict resolution
- do not reopen #011 unless new regression proof shows cashier revision can mutate an open-but-settled note again
- continue residual error_log closure separately for #018, #020, #028, #029, and other non-fixed entries
- do not use this #011 closure to claim full `make verify` green while audit-lines remains deferred

## Kesimpulan

Fixed with proof.

#011 root cause was CreateNoteRevisionHandler bypassing `EditableWorkspaceNoteGuard` on cashier revision mutation path. The final source keeps the #010 row-lock read with `getByIdForUpdate()` and adds #011 editability enforcement through `assertEditable($root->id())` before current revision resolution and mutation.

Targeted behavior proof and focused blast-radius proof passed. Full global verification and browser/manual QA remain unreported, and full `make verify` green is not claimed because audit-lines is deferred.

## Related Note-State Mutation Finding From Error Log 013

### Related Error Log

- 013-forged-row-refund-can-auto-finalize-unpaid-notes.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity note-state mutation issue.

Ini bukan root cause yang sama dengan #011.

- #011 is about cashier revision mutating payment-derived settled notes because CreateNoteRevisionHandler missed EditableWorkspaceNoteGuard.
- #013 is about selected-row refund auto-finalizing unpaid zero-total notes as refunded without recorded refund allocations.

Both findings show note-state transitions must be based on explicit financial evidence, not just mutable row totals or stored state shortcuts.

## Related Refunded-Note Edit Visibility Finding From Error Log 015

### Related Error Log

- 015-refunded-notes-expose-edit-workspace.md

### Update

Update 3.

### Reason

A later audit report found a separate editability issue.

Ini bukan root cause yang sama dengan #011.

- #011 is about missing application-level EditableWorkspaceNoteGuard in CreateNoteRevisionHandler.
- #015 is about the shared note detail view exposing an Edit button despite can_edit_workspace being false.

Both findings require the editability policy to be enforced in two places:
1. UI visibility for normal navigation
2. server-side guard for direct route access and mutation

## Related Workspace Inline Payment Finding From Error Log 017

### Related Error Log

- 017-workspace-edit-payments-ignore-existing-note-payments.md

### Update

Update 4.

### Reason

A later audit report found a separate issue in workspace edit/payment behavior.

Ini bukan root cause yang sama dengan #011.

- #011 is about missing payment-derived editability guard before cashier revision mutates settled note state.
- #017 is about inline payments during workspace edit ignoring existing note payments.

Both findings show workspace edit must account for existing financial state before mutation.

## Related Refunded-State Terminal Guard Finding From Error Log 018

### Related Error Log

- 018-refunded-notes-bypass-cashier-closed-note-guards.md

### Update

Update 5.

### Reason

A later audit report found another note editability guard issue.

Ini bukan root cause yang sama dengan #011.

- #011 is about payment-derived settled notes mutating because CreateNoteRevisionHandler missed EditableWorkspaceNoteGuard.
- #018 is about refunded notes mutating because route/addability guards did not treat refunded as terminal.

Both findings require note editability policy to account for both settlement-derived and explicit lifecycle terminal states.

## Update - route-scoped admin revision editability compatibility

Status: Follow-up verified.

During final verification after #029, the full test suite exposed a compatibility regression where the cashier workspace editability guard was applied too broadly to the shared note revision workflow.

The failing path was the audited admin revision/correction route, not the cashier mutation route.

Failure symptom:

    Nota close tidak boleh diedit lewat workspace.

Scope clarification:

- #011 protects cashier revision/workspace mutation paths from mutating settled note state.
- #011 does not make closed/paid/refunded notes an absolute mutation lock for audited admin correction/revision flows.
- Admin correction/revision must remain supported through the official revision workflow.
- Cashier/default mutation paths must still enforce workspace editability.
- The #010 same-note serialization lock must remain preserved.

Patch decision:

- `StoreNoteRevisionController` determines whether workspace editability enforcement is required from the route.
- `admin.notes.workspace.update` passes `enforceWorkspaceEditability = false`.
- Cashier/default paths keep `enforceWorkspaceEditability = true`.
- `CreateNoteRevisionHandler` forwards the flag to the workflow.
- `CreateNoteRevisionWorkflow` only calls `EditableWorkspaceNoteGuard::assertEditable()` when enforcement is enabled.
- `CreateNoteRevisionWorkflow` still reads the root note through `getByIdForUpdate(trim($noteRootId))`.

Production files changed:

- `app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php`
- `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`
- `app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php`

Verification proof:

Focused route-scoped guard verification:

    Tests: 13 passed (98 assertions)

Covered areas:

- admin closed-note workspace replacement
- admin product replacement finance cases
- revision after refund preserves historical anchors
- cashier revision submit regression #011
- cashier closed-note workspace replacement submit
- editable workspace guard tests

Full verification:

    PHPStan: [OK] No errors
    audit-lines: SUCCESS
    audit-blade: SUCCESS
    contract audit passed
    Tests: 901 passed (4797 assertions)

Conclusion:

The follow-up keeps cashier settled-note protections intact while restoring the audited admin revision/correction capability. The patch is route-scoped and preserves the #010 `getByIdForUpdate` lock.
