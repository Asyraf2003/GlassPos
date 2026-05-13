# HyperPOS Error Log Remediation Handoff - 2026-05-10

## Final Goal

Continue `docs/error_log/` remediation using the locked workflow:

- `docs/workflow/error-log-remediation-workflow.md`
- `docs/workflow/error-log-remediation-dod.md`
- `docs/workflow/error-log-remediation-sequence.md`

Goal: close each issue only with traceable proof: source map, RED proof where applicable, targeted GREEN, focused/blast-radius proof, docs alignment, explicit residual gaps, and final closure state.

## Locked Rules

- One active slice only.
- Do not move to the next issue before the active issue has sufficient proof or a documented stop/gap.
- Source/test proof wins over document status.
- RED proof required before patch unless source was already patched and this is explicitly documented.
- Do not commit/push unless user explicitly asks. User normally handles git commit/push manually.
- UI hiding is not a security boundary.
- Do not claim strict fixed, global verified, or browser verified without proof.
- Progress uses workflow count only:
  - Strict Fixed Progress
  - Slice Progress
  - Current Issue Step
  - Proof
  - Gap
- Seeder stays future scope unless explicitly opened.
- Command output from local repo is source of truth.

## Current Progress

Strict Fixed Progress: 17/28 = 60.7%.

Slice Progress:

- Slice 1 complete.
- Slice 2 complete.
- Slice 3 complete.
- Slice 4 active.

Slice 4 completed/checkpoint passed so far:

- #009 `docs/error_log/009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md`
- #011 `docs/error_log/011-cashier-revision-path-mutates-settled-note-state.md`
- #016 `docs/error_log/016-unauthenticated-admin-capability-toggle-endpoints.md`
- #019 `docs/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md`

Current active issue:

- #020 `docs/error_log/020-admin-note-actions-bypass-transaction-capability.md`

Do not start #027 before #020 is resolved or explicitly deferred.

## Latest Closed Issue: #019

#019 was closed.

Closure proof:

- commit: `e499954e commit 1787`
- push: `40974adf..e499954e main -> main`
- changed file:
  - `docs/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md`

#019 proof summary:

- RED: `1 failed, 1 passed / 5 assertions`
- Targeted GREEN initial: `2 passed / 8 assertions`
- Direct HTTP GREEN: `3 passed / 14 assertions`
- Focused cashier history proof: `6 passed / 34 assertions`
- Source fix:
  - `app/Adapters/Out/Note/Queries/CashierNoteHistoryCriteria.php`
  - `resolveAnchorDate()` now uses server-side `date('Y-m-d')`
- Test coverage:
  - service-level historical date ignored
  - direct `/cashier/notes/table?date=2025-01-15` endpoint ignores client historical date
  - current-window closed notes still allowed
- Residual gaps documented:
  - no full global suite
  - no browser/manual QA
  - timezone abstraction beyond current `date('Y-m-d')`
  - #020/#027/#022 still separate issues

Strict Fixed Progress increased to 17/28 = 60.7% after #019 closure.

## Current Issue: #020

Path:

- `docs/error_log/020-admin-note-actions-bypass-transaction-capability.md`

Document status at intake:

- `Patched`

Current source reality contradicted document status.

## #020 Source Intake

Doc says four admin note mutation routes should be protected by transaction-entry capability:

- `admin.notes.refunds.store`
- `admin.notes.payments.store`
- `admin.notes.rows.store`
- `admin.notes.workspace.update`

Route-list before patch showed these routes only had:

- `web`
- `auth`
- `EnsureAdminPageAccess`
- `app.shell`

They did not have:

- `EnsureTransactionEntryAllowed`

Relevant route file:

- `routes/web/note.php`

Original source structure showed the admin note route group used:

`Route::middleware(['auth', EnsureAdminPageAccess::class, 'app.shell'])`

and the four mutation routes were directly inside this group.

## #020 RED Proof

New test file created during #020:

- `tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

Initial RED partial:

Command:

`php artisan test tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

Result:

- 1 failed
- 1 passed
- 3 assertions

Failure shape:

- expected `403`
- actual `422`
- validation message: `Minimal satu line open harus dipilih.`

This proved `admin.notes.payments.store` bypassed the transaction-entry gate and reached validation/business logic.

## #020 RED Matrix Proof

The test was strengthened to collect all four route statuses before asserting.

Command:

`php artisan test tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

Result:

- 1 failed
- 1 passed
- 3 assertions

Failure status matrix:

- `payment => 422`
- `refund => 422`
- `rows => 422`
- `workspace => 422`

Expected:

- `payment => 403`
- `refund => 403`
- `rows => 403`
- `workspace => 403`

This RED proof matches root cause: all four documented admin note mutation routes bypassed `EnsureTransactionEntryAllowed`.

Read route proof in the same test passed:

- admin without transaction capability can still read admin note pages.

## #020 Production Patch

Production patch applied to:

- `routes/web/note.php`

Patch behavior:

The four scoped #020 admin mutation routes were wrapped with:

`Route::middleware(EnsureTransactionEntryAllowed::class)->group(...)`

Patched block:

- `Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');`
- `Route::post('/{noteId}/payments', RecordNotePaymentController::class)->name('payments.store');`
- `Route::post('/{noteId}/rows', AddNoteRowsController::class)->name('rows.store');`
- `Route::patch('/{noteId}/workspace', StoreNoteRevisionController::class)->name('workspace.update');`

Admin read routes remain outside the transaction-entry gate.

Important adjacent route:

- `admin.notes.reopen`

This route remains outside transaction-entry gate. It was discovered as an adjacent mutation route during #020 route-list review, but it is not part of the four-route scope documented in #020. Treat it as an adjacent discovered gap unless user decides to expand scope or create/update a separate error log.

## #020 Targeted GREEN Proof

After route patch:

Command:

`php artisan test tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

Result:

- 2 passed
- 3 assertions

Then test was strengthened to assert the exact capability JSON error.

Strengthened targeted rerun:

Command:

`php artisan test tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

Result:

- 2 passed
- 10 assertions

Covered:

- admin without transaction capability can still read:
  - `admin.notes.index`
  - `admin.notes.table`
- admin without transaction capability gets `403` on four scoped admin note mutation routes:
  - payment
  - refund
  - rows
  - workspace update
- response JSON includes:
  - `success: false`
  - `data: null`
  - message: `Admin belum diizinkan input transaksi.`
  - error code: `ADMIN_TRANSACTION_CAPABILITY_DISABLED`

## #020 Route Proof After Patch

Command:

`php artisan route:list --path=admin/notes -v`

Proof showed these four routes now include `EnsureTransactionEntryAllowed`:

- `POST admin/notes/{noteId}/payments`
- `POST admin/notes/{noteId}/refunds`
- `POST admin/notes/{noteId}/rows`
- `PATCH admin/notes/{noteId}/workspace`

Read routes remain outside transaction-entry gate:

- `admin.notes.index`
- `admin.notes.table`
- `admin.notes.show`
- `admin.notes.workspace.edit`

`admin.notes.reopen` also remains outside transaction-entry gate and is an adjacent gap.

## #020 Focused Proof Attempt

Focused command:

`php artisan test tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php tests/Feature/Auth/WebPageAccessFeatureTest.php tests/Feature/Note/AdminNoteDetailPageFeatureTest.php tests/Feature/Note/AdminNoteHistoryPageFeatureTest.php tests/Feature/Note/AdminNoteHistoryTableDataFeatureTest.php tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php`

Result:

- 17 passed
- 1 failed
- 91 assertions

Failing test:

- `Tests\Feature\Note\AdminNoteWorkspaceReplacementFeatureTest`
- test: `admin can open and submit closed note workspace replacement as revision`

Failure:

- expected admin note show page HTML to contain:
  - `http://localhost:8000/admin/notes/note-admin-1/workspace/edit`
- actual HTML did not contain this link
- failure line:
  - `tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php:46`

Important analysis:

- Failure occurs before submitting the mutation route.
- Failure is at show-page edit link rendering.
- The test uses `loginAsAuthorizedAdmin()`.
- `loginAsAuthorizedAdmin()` creates an admin and sets `admin_transaction_capability_states.active = true`.
- Therefore this focused failure is likely not caused by inactive transaction capability.
- Investigation found the shared detail workspace partial renders the edit link only when:
  - `$note['can_edit_workspace'] ?? false`
- Search result:
  - `resources/views/shared/notes/partials/line-workspace.blade.php`
  - line condition: `@if ($note['can_edit_workspace'] ?? false)`
- Source search found:
  - `app/Application/Note/Services/NoteDetailNotePayloadBuilder.php`
  - `can_edit_workspace` is based on `$isOpen`
- The test note is seeded as `closed`.
- Therefore the failure may be a stale test expectation or a separate UI/policy issue around closed-note admin revision link visibility.
- Do not silently change this test or production UI without inspecting the intended admin correction/revision policy from #011/ADR context.

The single-test rerun command used:

`php artisan test tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php --filter='admin can open and submit closed note workspace replacement as revision'`

returned:

- `INFO No tests found.`

This likely happened because the filter string did not match Pest/PHPUnit test name format exactly. It does not prove the failure disappeared.

## #020 Git/State Anomaly

After patch and tests, output showed surprising state changes.

At one point:

`git status --short --untracked-files=all`

showed:

- `M tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

It did not show `routes/web/note.php` even though route-list and source anchor showed the patch active.

Later investigation output:

`git status --short --branch --untracked-files=all`

showed only:

`## main...origin/main`

and final status after investigation appeared blank.

Yet route source anchor still showed the patched route block.

This means the next session must verify whether the #020 route/test changes are committed, staged elsewhere, or otherwise already persisted. Do not assume.

## Last Known #020 Investigation Output

Route source anchor currently seen in worktree:

`routes/web/note.php`

Admin route group contains:

- read routes outside transaction-entry middleware
- four scoped mutation routes inside `Route::middleware(EnsureTransactionEntryAllowed::class)->group(...)`
- `admin.notes.reopen` outside that group

`git diff --stat -- routes/web/note.php tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

returned no output during investigation.

`git diff -- routes/web/note.php`

returned no output during investigation.

This suggests route patch may already be in HEAD, but commit/log proof was not captured after the anomaly.

## Current Known Files

Expected/known #020 files touched during the session:

- `routes/web/note.php`
- `tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`

Docs #020 have not been updated yet.

Potentially relevant focused failure files:

- `tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php`
- `resources/views/shared/notes/partials/line-workspace.blade.php`
- `app/Application/Note/Services/NoteDetailNotePayloadBuilder.php`
- `app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php`

Do not patch these until source/policy is inspected and the relationship to #020 is decided.

## Current Gaps

#020 is not closed.

Gaps:

- Need verify HEAD/status/log after state anomaly.
- Need verify whether `routes/web/note.php` patch and new test are committed/pushed.
- Need rerun targeted #020 after verifying current state.
- Need resolve focused failure:
  - either update stale `AdminNoteWorkspaceReplacementFeatureTest` fixture/expectation with proof,
  - or identify a real regression/source policy issue,
  - or remove it from #020 focused set with documented rationale and run a better focused set.
- Need decide/document `admin.notes.reopen` adjacent mutation route:
  - out-of-scope gap for #020,
  - or explicit scope expansion with RED proof,
  - but do not silently patch it.
- Need update `docs/error_log/020-admin-note-actions-bypass-transaction-capability.md` only after focused proof decision.
- Need closure commit/push proof before strict fixed progress can increase.

Strict Fixed Progress remains:

- 17/28 = 60.7%

Do not raise to 18/28 yet.

## Safest Next Commands

Start next session with this verification block:

~~~bash
printf '\n== #020 CURRENT STATUS ==\n'
git status --short --branch --untracked-files=all

printf '\n== #020 LATEST LOG ==\n'
git log --oneline --decorate -n 10

printf '\n== #020 LOCAL VS ORIGIN ==\n'
git rev-list --left-right --count origin/main...HEAD

printf '\n== #020 ROUTE SOURCE ANCHOR ==\n'
sed -n '36,62p' routes/web/note.php

printf '\n== #020 TEST FILE EXISTS / ANCHORS ==\n'
test -f tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php && \
grep -RIn \
  -e 'ADMIN_TRANSACTION_CAPABILITY_DISABLED' \
  -e 'admin.notes.payments.store' \
  -e 'admin.notes.refunds.store' \
  -e 'admin.notes.rows.store' \
  -e 'admin.notes.workspace.update' \
  tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php

printf '\n== #020 DIFF STAT ==\n'
git diff --stat -- \
  routes/web/note.php \
  tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php \
  docs/error_log/020-admin-note-actions-bypass-transaction-capability.md

printf '\n== #020 TARGETED RERUN ==\n'
php artisan test tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php

Then inspect the focused failure with a correct filter or full file rerun:

printf '\n== #020 ADMIN WORKSPACE SINGLE FILE RERUN ==\n'
php artisan test tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php

printf '\n== #020 WORKSPACE EDIT POLICY SOURCES ==\n'
sed -n '1,140p' app/Application/Note/Services/NoteDetailNotePayloadBuilder.php

printf '\n== #020 LINE WORKSPACE PARTIAL ==\n'
sed -n '1,80p' resources/views/shared/notes/partials/line-workspace.blade.php

printf '\n== #020 ADMIN WORKSPACE TEST CURRENT SOURCE ==\n'
sed -n '1,140p' tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php
Opening Prompt for Next Session

Continue HyperPOS error-log remediation from:

docs/handoff/error_log/2026-05-10-hyperpos-error-log-remediation-handoff.md

Workflow source of truth:

docs/workflow/error-log-remediation-workflow.md
docs/workflow/error-log-remediation-dod.md
docs/workflow/error-log-remediation-sequence.md

Locked rules:

One active slice only.
Source/test proof wins over docs.
RED proof required before patch unless source already patched and explicitly documented.
Do not commit/push unless user explicitly asks.
User handles git commit/push manually.
UI hiding is not a security boundary.
Do not claim strict fixed/global/browser verified without proof.
Progress uses workflow count only.

Progress:

Strict Fixed Progress: 17/28 = 60.7%.
Slice 1 complete.
Slice 2 complete.
Slice 3 complete.
Slice 4 active.
#019 closed at e499954e commit 1787.
Current issue: #020.

Current #020 state:

Docs status says Patched, but source intake contradicted it.
RED matrix proved four admin note mutation routes returned 422 instead of expected 403.
Patch applied to wrap four scoped admin note mutation routes with EnsureTransactionEntryAllowed.
Targeted GREEN passed: 2 passed / 10 assertions.
Route-list proved four scoped routes gated.
Focused proof failed: AdminNoteWorkspaceReplacementFeatureTest missing admin workspace edit link on closed note show page.
Failure is likely stale test/policy issue around can_edit_workspace, not proven #020 regression.
Need verify git status/log because output later showed clean working tree and route/test diff empty.
Do not update docs #020 or move to #027 until #020 focused proof decision and state verification are done.
