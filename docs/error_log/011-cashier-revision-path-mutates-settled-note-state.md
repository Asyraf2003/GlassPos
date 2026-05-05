# 011 - Cashier revision path mutates settled note state

## Status

Patched, with verification gap and merge-safety note.

Patch supplied and PHP syntax checks passed, but no focused behavior test was reported as passing.

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
- Patch menambahkan EditableWorkspaceNoteGuard ke CreateNoteRevisionHandler.
- Guard dipanggil sebelum root-note mutation path.
- Verification masih gap karena hanya php -l yang dilaporkan pass.

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

- import App\Application\Note\Services\EditableWorkspaceNoteGuard
- constructor menerima dependency EditableWorkspaceNoteGuard
- createRevisionAndApply() memanggil:
  $this->guard->assertEditable($noteRootId);
- call dilakukan sebelum root note dibaca dan sebelum mutation path dijalankan

Efek patch:

- cashier revision behavior disejajarkan dengan guarded workspace update flow
- settled/payment-derived close note harus ditolak sebelum mutation
- root-note mutation tidak berjalan jika note tidak editable

## Merge-Safety Note Dengan Error Log 010

Error log #010 juga mengubah CreateNoteRevisionHandler untuk memakai locked note read:

getByIdForUpdate()

Final expected safe behavior untuk CreateNoteRevisionHandler adalah:

1. transaction dimulai
2. editability guard dipanggil sebelum mutation
3. root note dibaca dengan getByIdForUpdate() untuk serialization
4. revision dan active replacement dilakukan setelah guard + lock aman

Jika patch #011 diterapkan di branch yang belum mengandung patch #010, atau merge conflict diselesaikan asal-asalan, ada risiko getByIdForUpdate() dari #010 hilang.

Final code tidak boleh kembali ke plain getById() jika #010 sudah menjadi keputusan final.

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

User reported:

- vulnerability still existed in HEAD
- minimal fix applied in CreateNoteRevisionHandler
- EditableWorkspaceNoteGuard injected
- assertEditable($noteRootId) called before root-note mutation path
- behavior aligned with existing guarded workspace update flow
- committed with message:
  Guard cashier note revisions against settled notes
- commit:
  e418e3c

Testing reported:

- php -l app/Application/Note/UseCases/CreateNoteRevisionHandler.php
- php -l app/Application/Note/Services/EditableWorkspaceNoteGuard.php

Changed file:

app/Application/Note/UseCases/CreateNoteRevisionHandler.php

Reported diff size:

+4
-0

## Verification Gap

Only PHP syntax validation was reported.

Missing proof:

- cashier PATCH revision is rejected for payment-derived close/settled note with note_state=open
- no root note header mutation occurs
- no work item replacement occurs
- no note total update occurs
- no inventory reverse/reissue occurs
- no projection sync mutation occurs
- open unpaid note revision still works
- final CreateNoteRevisionHandler still uses getByIdForUpdate() if #010 patch is already in branch

## Recommended Follow-up

Minimum regression test:

Scenario 1:

- note_state = open
- note total = 50.000
- payment allocation = 50.000
- operational/payment-derived status = close/settled
- cashier sends PATCH workspace revision
- expect failure/redirect error or 403 depending controller behavior
- expect no root note mutation
- expect no new revision applied
- expect work_items unchanged
- expect payment allocations unchanged

Scenario 2:

- note_state = open
- no payment allocation or still unpaid
- cashier sends valid PATCH workspace revision
- expect success if route policy allows it

Recommended commands later:

php artisan test --filter=CreateNoteRevisionHandler
php artisan test --filter=Cashier

Recommended merge check:

grep -R "getByIdForUpdate" -n app/Application/Note/UseCases/CreateNoteRevisionHandler.php
grep -R "assertEditable" -n app/Application/Note/UseCases/CreateNoteRevisionHandler.php

Both must exist in final branch if #010 and #011 are both accepted.

## Kesimpulan

Laporan #011 valid sebagai High severity authorization/business-logic issue.

Bug sebelumnya membuat cashier revision path hanya bergantung pada stored note_state lewat middleware, sementara payment-derived close/settled status tidak dicek di handler. Akibatnya note yang secara settlement tidak boleh diedit dapat dimutasi jika note_state masih open.

Patch minimal sudah benar untuk root cause langsung: panggil EditableWorkspaceNoteGuard::assertEditable() sebelum root-note mutation. Namun patch ini harus dijaga agar tidak menghapus row-lock fix dari #010, dan masih butuh behavior test karena php -l hanya membuktikan sintaks, bukan policy benar-benar bekerja.
