# 015 - Refunded notes expose edit workspace

## Status

Fixed with local UI and focused guard proof.

Current local verification proves the #015 UI visibility boundary for open, closed, and refunded note detail pages, plus adjacent cashier refunded-note server-side guard regression.

This closure is scoped to local source/test proof only. Full global make verify, browser/manual QA, and commit/push proof are not claimed.

## Severity

High.

## Source

Audit report #015: Refunded notes expose edit workspace.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
- 011-cashier-revision-path-mutates-settled-note-state.md
- 007-admin-note-edit-page-exposes-stored-xss.md
- 013-forged-row-refund-can-auto-finalize-unpaid-notes.md
- 014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md

### Jenis Keterkaitan

Direct editability/workspace relationship with #009 and #011.

Indirect shared workspace surface relationship with #007.

Indirect refunded-note lifecycle relationship with #013 and #014.

### Alasan

Laporan #015 sangat berkaitan dengan #009 dan #011 karena sama-sama membahas note yang tidak boleh diedit lewat normal workspace.

Namun root cause berbeda.

- #009 membahas server-side route authorization: cashier workspace update dapat memutasi closed note.
- #011 membahas application-level guard: cashier revision path dapat memutasi payment-derived settled note karena tidak memakai EditableWorkspaceNoteGuard.
- #015 membahas UI visibility: tombol Edit tetap muncul untuk refunded note karena Blade view tidak mengecek can_edit_workspace.

Laporan #015 berkaitan tidak langsung dengan #007 karena sama-sama shared workspace/edit surface, tetapi #007 adalah stored XSS pada JSON script sink, bukan edit visibility.

Laporan #015 berkaitan tidak langsung dengan #013/#014 karena target state-nya refunded note, tetapi #013/#014 membahas refund/cancel flow, bukan detail-page edit button.

Karena root cause, file, dan patch berbeda, laporan #015 dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #015.

Alasan update:

- Laporan menunjukkan refunded notes masih menampilkan link Edit workspace.
- Patch mengembalikan conditional can_edit_workspace pada tombol Edit.
- Verification masih gap karena hanya php -l dan commit yang dilaporkan.


### Update 3 - 2026-05-10 local focused UI/guard verification

Status changed from Patched, with server-side authorization verification gap to Fixed with local UI and focused guard proof.

Current source/test reality clarified the #015 root cause:

- the Blade partial already renders the Edit link only when can_edit_workspace is true
- the remaining bug was the payload flag being too narrow
- closed note detail expected Edit
- actual rendered page did not contain Edit
- refunded note detail must continue hiding workspace/payment actions

Production file changed:

- app/Application/Note/Services/NoteDetailNotePayloadBuilder.php

Current source anchor:

- can_edit_workspace => ! $isRefunded && ($isOpen || $isClosed),

Blade anchor verified:

- resources/views/shared/notes/partials/line-workspace.blade.php
- @if ($note['can_edit_workspace'] ?? false)
- Edit route is rendered inside that guard

Targeted UI proof:

Command:

    php artisan test \
      tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php \
      tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php

Result:

- PASS
- 3 passed / 29 assertions

Focused UI/guard regression proof:

Command:

    php artisan test \
      tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php \
      tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php \
      tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php \
      tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
      tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php \
      tests/Feature/Note/CashierRefundSelectionFirstFeatureTest.php

Result:

- PASS
- 17 passed / 82 assertions

Behavior proven in this #015 closure scope:

- closed note detail renders the Edit entry point again when locally eligible
- open note detail still renders workspace edit/payment actions
- refunded note detail hides workspace/payment actions
- cashier direct refunded workspace edit and workspace update remain rejected through adjacent #018 server-side guard coverage
- selected-row refund focused behavior still passes in the included refund lifecycle regression set

Separation from #018:

- #015 owns UI visibility and payload editability flag for normal navigation
- #018 owns server-side refunded terminal mutation guard
- UI hiding is not treated as a security boundary
- #018 is not reopened by this update because the focused guard regression stayed green

Remaining verification gaps:

- full global make verify not run in this #015 closure step
- browser/manual QA not run
- commit/push proof not claimed
- no broader admin/cashier browser navigation audit beyond the listed feature tests

## Ringkasan Indonesia

Bug terjadi pada shared note detail partial:

resources/views/shared/notes/partials/line-workspace.blade.php

Sebelum patch, tombol Edit dirender tanpa mengecek:

$note['can_edit_workspace']

Akibatnya note yang sudah refunded tetap dapat menampilkan link menuju workspace edit route dari normal UI/navigation.

Patch mengembalikan guard:

@if ($note['can_edit_workspace'] ?? false)
  Edit button
@endif

Dengan begitu, UI kembali mengikuti persisted/computed editability flag.

## Dampak

Dampak utama:

- refunded note dapat menampilkan entry point normal ke edit workspace
- cashier/admin dapat diarahkan ke flow edit yang seharusnya tidak tersedia
- risiko user melakukan mutation pada note yang seharusnya immutable jika server-side guard juga lemah
- confusion pada operational workflow karena UI tidak mencerminkan editability state
- dapat memperbesar dampak bug server-side seperti #009 atau #011 jika direct route juga belum kuat

Severity High dapat dipahami jika edit workspace route benar-benar bisa dipakai untuk mutation. Namun patch ini sendiri hanya memperbaiki UI exposure. Keamanan final tetap harus dibuktikan oleh server-side authorization guard.

## Jalur Risiko

Workflow risiko:

1. User membuka detail note.
2. Note sudah refunded atau tidak editable.
3. View shared line workspace tetap menampilkan tombol Edit.
4. User klik Edit.
5. Browser menuju workspace edit route.
6. Jika server-side guard tidak menolak, user dapat masuk ke edit workspace dan mencoba mutation.
7. Refunded note bisa masuk normal edit workflow yang seharusnya tertutup.

## Root Cause

Root cause:

UI edit button tidak lagi dibungkus dengan conditional editability flag.

Field yang seharusnya menjadi sumber visibility:

$note['can_edit_workspace']

tidak dipakai saat render tombol Edit.

Akibatnya view membuka navigation path yang bertentangan dengan note state/editability policy.

## Patch Summary

Patch diterapkan pada:

resources/views/shared/notes/partials/line-workspace.blade.php

Perubahan:

- tombol Edit yang sebelumnya unconditional dihapus
- tombol Edit dibungkus dengan:

@if ($note['can_edit_workspace'] ?? false)

Efek patch:

- refunded/non-editable notes tidak lagi menampilkan Edit button dari shared line workspace partial
- UI kembali align dengan can_edit_workspace flag
- normal navigation ke edit workspace untuk refunded notes tertutup dari view

## Scope In

- Shared note line workspace partial.
- Edit button visibility.
- can_edit_workspace UI guard.
- Refunded note edit link exposure through normal navigation.

## Scope Out

- Server-side route authorization.
- Direct URL access to workspace edit route.
- CreateNoteRevisionHandler editability guard from #011.
- Cashier route mutation guard from #009.
- Admin/cashier route policy redesign.
- Full browser E2E test.
- Audit of all other edit buttons/links.

## Residual Risk

This patch hides the Edit button, but UI hiding is not a security boundary.

A valid final fix must also prove:

- direct GET to workspace edit for refunded note is rejected or returns safe non-editable page
- direct PATCH workspace update for refunded note is rejected
- CreateNoteRevisionHandler still calls EditableWorkspaceNoteGuard::assertEditable()
- cashier middleware still routes mutation through assertCanMutateOpenNote()
- admin routes do not bypass editability checks for refunded/settled notes

If direct URL still works, this patch only removes the doorknob label while leaving the door unlocked. A timeless human contribution to security theater.

## Proof Dari Patch Session

User reported:

- vulnerability still existed at HEAD in:
  resources/views/shared/notes/partials/line-workspace.blade.php
- Edit link was unguarded
- minimal fix restored can_edit_workspace conditional around the Edit button render
- UI behavior realigned with persisted editability flag
- refunded-note edit path blocked from normal navigation

Testing reported:

- php -l resources/views/shared/notes/partials/line-workspace.blade.php
- git commit -m "Fix workspace edit visibility for refunded notes"

Changed file:

resources/views/shared/notes/partials/line-workspace.blade.php

Reported diff size:

+8
-6

## Verification Gap

Locally verified for #015 UI visibility and adjacent focused cashier refunded-note guard regression.

Verified local proof:

- targeted UI tests: 3 passed / 29 assertions
- focused UI/guard regression: 17 passed / 82 assertions
- source anchors verified for NoteDetailNotePayloadBuilder and line-workspace.blade.php
- diff check clean for #015 source/view/doc paths before docs patch

Remaining gaps:

- full global make verify not run in this closure step
- browser/manual QA not run
- commit/push proof not claimed
- wider UI navigation audit outside the focused feature tests remains out of scope

## Recommended Follow-up

Minimum UI regression test:

Scenario 1:

- note_state = refunded
- can_edit_workspace = false
- open note detail page
- expect Edit button not visible
- expect workspace edit route link not present

Scenario 2:

- note_state = open
- can_edit_workspace = true
- open note detail page
- expect Edit button visible

Minimum server-side security test:

Scenario 3:

- refunded note
- direct GET workspace edit route
- expect forbidden/redirect safe behavior

Scenario 4:

- refunded note
- direct PATCH workspace update
- expect failure/403/validation error
- expect no note/work-item/payment mutation

Recommended command later:

php artisan test --filter=WorkspaceEdit

Recommended audit search:

grep -R "workspace_edit_route" -n resources/views app
grep -R "can_edit_workspace" -n app resources/views

## Kesimpulan

Laporan #015 valid sebagai High severity UI exposure issue.

Final local fix for #015 is now verified for the UI visibility path:

- normal Edit navigation is controlled by can_edit_workspace
- closed notes can show Edit when locally eligible
- refunded notes do not expose workspace/payment actions on detail page
- focused cashier refunded-note guard regression remains green

Security boundary remains server-side.

#015 must not be interpreted as authorization by UI hiding. The server-side refunded terminal guard belongs to #018 and remains separate. Current focused proof includes #018-adjacent route guard coverage only to prove #015 did not regress the direct route/mutation protections.

Technical status: fixed and locally verified for scoped #015 UI visibility plus adjacent focused guard regression.

Global status: full make verify, browser/manual QA, and commit/push proof are not claimed.

## Related Server-Side Refunded Guard Finding From Error Log 018

### Related Error Log

- 018-refunded-notes-bypass-cashier-closed-note-guards.md

### Update

Update 2.

### Reason

A later audit report found a directly related but separate refunded-note editability issue.

Ini bukan root cause yang sama dengan #015.

- #015 is about UI exposing the Edit button for refunded notes.
- #018 is about server-side cashier mutation guard and addability policy failing to treat refunded notes as terminal.

Kedua fix wajib ada. UI harus menyembunyikan navigasi edit, dan guard server-side harus menolak request mutasi langsung.
