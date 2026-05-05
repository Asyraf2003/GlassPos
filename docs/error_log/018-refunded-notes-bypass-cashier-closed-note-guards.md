# 018 - Refunded notes bypass cashier closed-note guards

## Status

Patched, with verification gap.

Patch supplied and regression tests added, but tests could not run in the patch environment because vendor/autoload.php / dependencies were missing.

## Severity

High.

## Source

Audit report #018: Refunded notes bypass cashier closed-note guards.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 015-refunded-notes-expose-edit-workspace.md
- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
- 011-cashier-revision-path-mutates-settled-note-state.md
- 013-forged-row-refund-can-auto-finalize-unpaid-notes.md
- 014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md

### Jenis Keterkaitan

Direct refunded-note editability relationship with #015.

Direct cashier mutation guard relationship with #009 and #011.

Direct refunded lifecycle relationship with #013 and #014.

### Alasan

Laporan #018 sangat berkaitan dengan #015, tetapi bukan bug yang sama.

- #015 membahas UI exposure: refunded note masih menampilkan Edit button karena Blade partial tidak mengecek can_edit_workspace.
- #018 membahas server-side/business-rule bypass: refunded note tidak dianggap terminal oleh cashier mutation guard dan addability policy.

Laporan #018 juga berkaitan dengan #009/#011 karena sama-sama melindungi note yang tidak boleh dimutasi lewat cashier workspace/route biasa.

Laporan #018 berkaitan dengan #013/#014 karena refunded state berasal dari refund lifecycle. Setelah note masuk state refunded, state itu harus terminal kecuali ada explicit admin reopen/correction flow.

Karena root cause, file, dan patch berbeda, laporan #018 dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #018.

Alasan update:

- Laporan menunjukkan refunded notes bisa melewati cashier closed-note guards.
- Patch memperlakukan refunded notes sebagai non-mutable pada cashier mutation guard.
- Patch memperkuat addability policy agar refunded notes tidak bisa ditambah work item.
- Verification masih gap karena tests gagal dijalankan akibat missing dependencies.

## Ringkasan Indonesia

Bug terjadi setelah lifecycle baru memperkenalkan state refunded.

Sebelum perubahan lifecycle, note yang fully refunded tetap berada di state closed. Karena itu cashier mutation routes yang menolak closed note masih memblokir mutation.

Setelah perubahan lifecycle, full refund mengubah note state dari:

closed

menjadi:

refunded

Masalahnya, guard lama hanya mengecek:

$note->isClosed()

Sedangkan:

$note->isClosed() hanya true untuk state closed
$note->isRefunded() true untuk state refunded

Akibatnya refunded note tidak dianggap closed oleh cashier mutation guard.

Masalah tambahan terjadi pada addability policy. Paid status dihitung dari:

allocated - refunded

Setelah full refund, net settlement menjadi nol. Karena note tidak lagi dianggap paid, NoteAddabilityPolicy dapat mengizinkan row baru ditambahkan, padahal refunded note seharusnya terminal.

## Dampak

Dampak utama:

- cashier dapat memutasi note yang sudah fully refunded
- refunded note bisa menerima row baru
- note total bisa berubah setelah final refund
- inventory issue path bisa berjalan lagi untuk note terminal
- audit finality rusak
- financial dan inventory records yang seharusnya finalized menjadi mutable
- refund lifecycle tidak lagi menjadi terminal state

Severity High tepat karena ini menyentuh finalized financial/inventory records. Tidak Critical karena membutuhkan authenticated cashier/admin capability, note dalam akses window, dan tidak menghasilkan unauthenticated compromise, RCE, account takeover, atau secret leak.

## Jalur Risiko

Workflow risiko:

1. Note dalam state closed.
2. Cashier/admin melakukan full refund.
3. AutoRefundNoteWhenFullyRefunded mengubah state note menjadi refunded.
4. Cashier mengirim mutation request, misalnya POST rows.
5. EnsureCashierNoteAccess memanggil CashierNoteAccessGuard::assertCanMutateOpenNote().
6. Guard lama hanya menolak isClosed().
7. isClosed() false untuk refunded note.
8. Request lanjut ke AddWorkItemHandler.
9. NoteAddabilityPolicy melihat net paid setelah refund sebagai tidak paid.
10. Row baru dibuat, note total berubah, inventory bisa issued.

## Root Cause

Root cause:

State baru refunded tidak dimasukkan ke terminal/non-mutable policy.

Beberapa guard lama memakai konsep:

closed = tidak boleh dimutasi

Namun setelah lifecycle baru, terminal state menjadi minimal:

closed
refunded

Bug muncul karena policy dan guard belum diperbarui agar refunded diperlakukan sebagai terminal state.

## Patch Summary

Patch diterapkan pada:

app/Application/Note/Policies/CashierNoteAccessGuard.php

Perubahan:

- assertCanMutateOpenNote() sekarang menolak:
  - note->isClosed()
  - note->isRefunded()

Efek:

- cashier mutation routes tidak boleh memproses refunded note

Patch juga diterapkan pada:

app/Application/Note/Policies/NoteAddabilityPolicy.php

Perubahan:

- assertAllowed() sekarang menolak note refunded sebelum/bersama paid check:
  - note->isRefunded()
  - paidStatus->isPaid($note)

Efek:

- use-case-level add row path juga menolak refunded note
- net settlement nol setelah refund tidak membuat note tampak editable/addable lagi

Test ditambahkan/diubah pada:

tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php
tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php

Test intent:

- cashier masih bisa view detail refunded/closed sesuai route rules jika diizinkan
- cashier tidak bisa membuka mutation/edit workspace route untuk refunded note
- AddWorkItemHandler menolak add row ke refunded note
- no work_items dibuat saat refunded note ditargetkan

## Scope In

- Cashier mutation route guard for refunded notes.
- Addability policy for refunded notes.
- Refunded note terminal-state protection.
- Add row/workspace mutation protection.
- Regression coverage for route-level and use-case-level mutation.

## Scope Out

- UI edit button visibility from #015.
- Stored XSS workspace issue from #007.
- Refund endpoint validation from #013/#014.
- Full admin correction/reopen workflow.
- All possible note mutation routes beyond covered tests.
- Full Laravel test pass, because dependencies were missing.
- Production data cleanup for refunded notes already mutated.

## Proof Dari Patch Session

User reported:

- vulnerability still present in current HEAD
- minimal fix treats refunded notes as non-mutable in cashier mutation guard logic
- NoteAddabilityPolicy hardened to reject refunded notes
- regression test coverage added for cashier route access
- regression test coverage added for AddWorkItemHandler
- fixture helper updated to seed note state explicitly
- committed on current branch:
  e37d521
- PR metadata created via make_pr

Changed files:

app/Application/Note/Policies/CashierNoteAccessGuard.php
app/Application/Note/Policies/NoteAddabilityPolicy.php
tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php
tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php

Reported diff size:

+44
-2

Testing attempted:

php artisan test tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php

Result:

Failed due to missing vendor/autoload.php / dependencies not installed.

## Verification Gap

Tests were added but did not pass in the patch environment.

Missing proof:

- cashier cannot open workspace edit for refunded note
- cashier cannot post rows/payments/corrections against refunded note
- AddWorkItemHandler rejects refunded note
- admin correction/reopen route remains the only intended path if mutation is needed
- refunded note detail view remains accessible if intended
- all mutation routes consistently treat refunded as terminal
- no direct URL bypass remains

## Recommended Follow-up

Minimum verification command:

composer install
php artisan test tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php

Recommended additional audit:

Search mutation paths that still check only isClosed():

grep -R "isClosed()" -n app/Application app/Adapters app/Core | grep -v vendor

Search policies that should treat refunded as terminal:

grep -R "isRefunded()" -n app/Application app/Adapters app/Core

Recommended tests:

1. Cashier can view refunded note detail if within date window and policy allows view.
2. Cashier cannot GET workspace edit for refunded note.
3. Cashier cannot POST rows to refunded note.
4. Cashier cannot POST payment to refunded note.
5. Cashier cannot PATCH workspace update for refunded note.
6. AddWorkItemHandler rejects refunded note even if route guard is bypassed.
7. Admin explicit correction/reopen flow, if any, is the only allowed mutation path.

## Kesimpulan

Laporan #018 valid sebagai High severity refunded-note terminal-state authorization issue.

Bug sebelumnya memperkenalkan refunded state tetapi tidak memperbarui guard yang hanya mengenal closed sebagai terminal. Setelah full refund, note dapat lolos cashier mutation guard dan addability policy karena net settlement menjadi nol.

Patch minimal sudah tepat: refunded sekarang ditolak oleh cashier mutation guard dan addability policy. Namun test belum terbukti pass karena dependency environment belum tersedia, jadi status tetap patched with verification gap.

## Related #019 - Cashiers can list historical closed notes by date

#019 is related through cashier access-boundary enforcement. #018 covers refunded terminal-state mutation/addability guards, while #019 covers historical closed-note disclosure through the cashier history table when a client-controlled date anchor is accepted.
