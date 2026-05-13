# 018 - Refunded notes bypass cashier closed-note guards

## Status

Fixed with proof.

Refunded notes are now treated as terminal for cashier mutation routes and add-work-item policy.

Local verification proved the previous RED failures, the minimal GREEN patch, targeted regression pass, and focused blast-radius pass.

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

### Update 2

Status changed from `Patched, with verification gap` to `Fixed with proof`.

Current verification started from clean remote baseline:

- `dc98f7f2` = previous proven clean remote before #018 work.
- #009 and #011 were already closed and pushed before #018 verification.
- Current source/test closure was pushed by owner at `1a3ceb68`.

Source/docs mismatch found during verification:

- This document claimed the patch existed.
- Current source inspection proved the guard was missing from the active source before this fix.
- `CashierNoteAccessGuard::assertCanMutateOpenNote()` only rejected `$note->isClosed()`.
- `NoteAddabilityPolicy::assertAllowed()` only checked `paidStatus->isPaid($note)`.
- Fully refunded notes can look unpaid after net refund settlement, so refunded terminal state must be checked directly.

RED characterization proof:

Command:

~~~bash
php artisan test \
  tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php \
  tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php || true

Result:

FAIL
2 failed
6 passed
18 assertions

RED failures:

test_cashier_cannot_post_rows_to_refunded_note
expected HTTP 403
actual HTTP 302
meaning cashier POST rows against refunded note bypassed the mutation guard and reached controller flow
test_add_work_item_handler_rejects_new_item_when_note_is_refunded
expected $result->isFailure() true
actual handler result was success
meaning direct AddWorkItemHandler still allowed adding item to refunded note

Minimal source patch:

app/Application/Note/Policies/CashierNoteAccessGuard.php
assertCanMutateOpenNote() now rejects $note->isClosed() || $note->isRefunded()
app/Application/Note/Policies/NoteAddabilityPolicy.php
assertAllowed() now rejects $note->isRefunded() before paid-status math
app/Application/Note/Services/AddWorkItemErrorClassifier.php
maps note yang sudah refund to NOTE_NEW_ITEMS_NOT_ALLOWED_AFTER_REFUNDED

Regression tests added/updated:

tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php
added test_cashier_cannot_post_rows_to_refunded_note
tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php
added test_add_work_item_handler_rejects_new_item_when_note_is_refunded
updated seedNote() helper to accept string $noteState = 'open' and insert note_state

Syntax proof:

php -l app/Application/Note/Policies/CashierNoteAccessGuard.php
php -l app/Application/Note/Policies/NoteAddabilityPolicy.php
php -l app/Application/Note/Services/AddWorkItemErrorClassifier.php
php -l tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php
php -l tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php

Result:

PASS
no syntax errors detected in all 5 touched files

Targeted GREEN proof:

Command:

php artisan test \
  tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php \
  tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php

Result:

PASS
8 tests passed
23 assertions

Focused blast-radius proof:

Command:

php artisan test \
  tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php \
  tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php \
  tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php \
  tests/Feature/Note/EditableWorkspaceNoteGuardFeatureTest.php \
  tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php \
  tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php \
  tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
  tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php

Result:

PASS
22 tests passed
88 assertions

Focused coverage included:

refunded route/addability fix for #018
refunded note detail behavior
editability guard adjacency from #011
closed-note workspace mutation guard adjacency from #009
cashier note revision mutation guard adjacency from #011
refund lifecycle adjacency without expanding scope into #021/#022

Remaining verification gaps:

full global suite not reported
browser/manual QA not reported
full make verify not claimed because audit-lines is deferred by owner decision
#015 UI edit button visibility remains separate
#021 open-note refund remains separate
#022 refund route access guard remains separate
admin correction/reopen flow is not verified by this slice

### Update 3

Current Slice 5 re-verification found and fixed a scoped server-side workspace-edit guard regression.

Reason:

- Broad #022 route/guard rerun exposed an adjacent #018 failure:
  - `cashier cannot open workspace edit for refunded note`
  - expected 403
  - actual 200
- Initial attempt moved `cashier.notes.workspace.edit` to the generic mutate-open guard.
- That correctly blocked refunded notes, but also regressed the existing closed-note workspace edit contract:
  - `cashier can open workspace edit for closed note`
  - expected 200
  - actual 403
- Refined patch added a dedicated workspace-edit access path:
  - closed notes remain allowed for GET workspace edit
  - refunded notes are denied for GET workspace edit

Production files changed:

- app/Application/Note/Services/CashierNoteRouteAccessData.php
- app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php

Patch summary:

- `CashierNoteRouteAccessData::ensureCanOpenWorkspaceEdit(string $noteId): bool` was added.
- The new method:
  - loads the note
  - applies normal cashier view/date-window access through `assertCanView`
  - rejects refunded notes directly with a DomainException
- `EnsureCashierNoteAccess` now routes:
  - `cashier.notes.workspace.edit` to `ensureCanOpenWorkspaceEdit`
  - `cashier.notes.show`, `cashier.notes.payments.store`, and `cashier.notes.refunds.store` to `ensureCanView`
  - remaining mutation routes to `ensureCanMutateOpenNote`

Targeted proof:

- `php artisan test tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php --filter=test_cashier_can_open_workspace_edit_for_closed_note`
  - 1 passed, 2 assertions
- `php artisan test tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php --filter=test_cashier_cannot_open_workspace_edit_for_refunded_note`
  - 1 passed, 1 assertion
- `php artisan test tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php`
  - 7 passed, 11 assertions

Focused server-side guard proof:

- Command:
  - `php artisan test tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php tests/Feature/Note/EditableWorkspaceNoteGuardFeatureTest.php tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php`
- Result:
  - 25 passed, 99 assertions

Diff check:

- `git diff --check` passed for:
  - app/Application/Note/Services/CashierNoteRouteAccessData.php
  - app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php
  - docs/04-lifecycle/error-log/018-refunded-notes-bypass-cashier-closed-note-guards.md

Remaining scope boundary:

- #015 UI `Edit` button visibility remains separate.
- This update fixes server-side GET workspace edit access for refunded notes.

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

Closed by previous proof:

- cashier cannot post rows to refunded note
- AddWorkItemHandler rejects refunded note
- cashier cannot patch workspace update for refunded note
- refunded detail view remains accessible when intended

Closed by current Slice 5 proof:

- cashier can still open workspace edit for closed note
- cashier cannot open workspace edit for refunded note
- dedicated workspace-edit guard allows closed notes while denying refunded notes
- focused server-side guard suite passed: 25 tests, 99 assertions

Remaining gaps:

- #015 UI `Edit` button visibility remains separate
- browser/manual QA not run
- full global `make verify` not run in this step
- admin correction/reopen flow is not verified by this slice

## Recommended Follow-up

Minimum verification command:

composer install
php artisan test tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php

Recommended additional audit:

Cari jalur mutasi yang masih hanya memeriksa isClosed():

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

Patch minimal sudah terbukti secara lokal: refunded sekarang ditolak oleh cashier mutation guard dan addability policy. Targeted regression dan focused blast-radius tests sudah pass, jadi status laporan ini menjadi fixed with proof.

## Related #019 - Cashiers can list historical closed notes by date

#019 is related through cashier access-boundary enforcement. #018 covers refunded terminal-state mutation/addability guards, while #019 covers historical closed-note disclosure through the cashier history table when a client-controlled date anchor is accepted.

## Related #021 - Refunds can be recorded on open notes

#021 is related through refund lifecycle state boundaries. #018 covers refunded terminal notes becoming mutable again, while #021 covers refunds being recorded before the parent `Nota` reaches operational close status.

## Related #022 - Cashier refund route bypasses note access guard

#022 is related through cashier closed-note and refund lifecycle boundaries. #018 covers refunded terminal notes becoming mutable again, while #022 covers the refund route bypassing per-note cashier access checks before mutation.
