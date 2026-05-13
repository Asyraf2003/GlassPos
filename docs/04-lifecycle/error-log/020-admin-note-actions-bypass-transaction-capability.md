# 020 - Admin note actions bypass transaction capability

## Status

Fixed and locally verified.

## Severity

High.

## Summary

Admin note mutation routes allowed financial and workspace mutations without enforcing the transaction-entry capability gate.

The affected admin routes were:

- `admin.notes.refunds.store`
- `admin.notes.payments.store`
- `admin.notes.rows.store`
- `admin.notes.workspace.update`

The admin route group was protected by authentication, admin-page access, and app shell middleware, but did not require `EnsureTransactionEntryAllowed`.

Karena UI detail nota admin menyediakan URL action payment/refund admin dan merender form payment/refund, admin terautentikasi yang capability transaction-entry-nya inactive tetap dapat submit form mutasi nota admin normal dan mengubah record finansial.

## Vulnerable Path

Authenticated admin session
-> admin role passes `EnsureAdminPageAccess`
-> open `/admin/notes/{noteId}`
-> page renders payment/refund/add-row/workspace actions
-> submit normal form with valid session/CSRF
-> admin mutation route lacks `EnsureTransactionEntryAllowed`
-> payment/refund/row/workspace controller executes
-> note financial or workspace state changes without required transaction-entry capability

## Root Cause

The transaction-entry capability boundary existed but was not applied to admin note mutation routes.

Gate akses halaman admin hanya membuktikan actor boleh mengakses halaman admin. Gate itu tidak membuktikan actor boleh input atau memutasi transaksi.

## Patch Summary

`routes/web/note.php` was changed so these admin mutation routes are wrapped in `EnsureTransactionEntryAllowed`:

- `Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');`
- `Route::post('/{noteId}/payments', RecordNotePaymentController::class)->name('payments.store');`
- `Route::post('/{noteId}/rows', AddNoteRowsController::class)->name('rows.store');`
- `Route::patch('/{noteId}/workspace', StoreNoteRevisionController::class)->name('workspace.update');`

Admin read routes remain outside the transaction-entry gate.

## Verification

Local verification from #020 remediation:

- `git status --short --branch --untracked-files=all`
  - branch clean and aligned with `origin/main`.
- `git rev-list --left-right --count origin/main...HEAD`
  - `0 0`.
- `sed -n '36,62p' routes/web/note.php`
  - confirmed these four scoped admin note mutation routes are inside `EnsureTransactionEntryAllowed`:
    - `admin.notes.refunds.store`
    - `admin.notes.payments.store`
    - `admin.notes.rows.store`
    - `admin.notes.workspace.update`
  - confirmed admin read routes remain outside the transaction-entry gate.
  - confirmed `admin.notes.reopen` remains outside this #020 scoped patch and is an adjacent discovered mutation route, not silently included in #020.
- `php artisan test tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php`
  - PASS: 2 tests, 10 assertions.
  - Covers admin read access without transaction-entry capability.
  - Covers forbidden response for the four scoped admin note mutation routes when admin transaction-entry capability is inactive.
- Focused admin read route proof:
  - `php artisan test tests/Feature/Note/AdminNoteHistoryPageFeatureTest.php tests/Feature/Note/AdminNoteHistoryTableDataFeatureTest.php tests/Feature/Note/AdminNoteDetailPageFeatureTest.php`
  - PASS: 5 tests, 39 assertions.

Focused failure classification:

- `tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php` failed before mutation submit because the closed note show page did not render the workspace edit link.
- The failure is classified as a stale or separate UI/policy expectation, not a #020 route gate regression.
- Source evidence:
  - `NoteDetailNotePayloadBuilder` sets `can_edit_workspace` from `$isOpen`.
  - `resources/views/shared/notes/partials/line-workspace.blade.php` only renders the Edit link when `can_edit_workspace` is true.
  - The failing test seeds a `closed` note.
- Therefore the failed assertion is outside #020's transaction-entry middleware boundary and is not used as #020 closure proof.

## Verification Gap

Not globally verified:

- Full `make verify` was not rerun for #020 closure.
- Browser/manual QA was not run.
- `admin.notes.reopen` remains an adjacent discovered mutation route outside this #020 patch scope and must be handled by a separate issue/scope decision if required.
- `AdminNoteWorkspaceReplacementFeatureTest` closed-note edit-link expectation remains unresolved as a separate UI/policy/test concern.

## Relations

Directly related to #016 as part of the identity/access capability authorization cluster.

#016 covers unauthenticated admin capability toggle endpoints.

#020 covers admin note mutation routes bypassing the transaction-entry capability gate.

Terkait dengan #009, #011, dan #017 karena laporan tersebut juga melibatkan jalur mutasi nota yang harus menegakkan authorization dan settlement safety sebelum menjalankan perubahan payment/revision/workspace.

## Update - Duplicate report from commit a78999d

Laporan ini diklasifikasikan sebagai update #020, bukan file error-log baru.

Root cause identik: empat route admin note yang mengubah state terekspos di bawah `admin/notes` tanpa `EnsureTransactionEntryAllowed` / `transaction.entry`.

Affected routes:

- `admin.notes.refunds.store`
- `admin.notes.payments.store`
- `admin.notes.rows.store`
- `admin.notes.workspace.update`

Bukti tambahan pada laporan ini mengonfirmasi dampak melalui controller/use case yang tercapai:

- `RecordNotePaymentController` records and allocates note payments once reached.
- `RecordClosedNoteRefundController` records customer refunds once reached.
- `AddNoteRowsController` mutates note rows once reached.
- `UpdateTransactionWorkspaceHandler` changes note header, items, and totals once reached.

Additional policy evidence:

- `AdminPageAccessPolicy` only checks whether the actor is an admin.
- `TransactionEntryPolicy` separately denies inactive admin transaction capability.
- Because the vulnerable admin routes skipped the transaction-entry gate, that denial policy was not reached.

Patch variant:

Fix yang dilaporkan hanya membungkus empat route mutasi admin dengan:

`Route::middleware(EnsureTransactionEntryAllowed::class)->group(...)`

Admin read-only routes remain outside that middleware group.

Reported verification:

- `php -l routes/web/note.php`
- `git status --short`
- `git commit -m "Protect admin note mutation routes with transaction-entry middleware"`

No progress increase because this is the same root cause and same target file as #020.

## Update - 2026-05-10 local verification

The previous `Patched` status is promoted to `Fixed and locally verified` based on local targeted and focused feature-test proof.

The implemented boundary remains intentionally narrow:

- Gate the four scoped admin note mutation routes with `EnsureTransactionEntryAllowed`.
- Keep admin read routes outside the transaction-entry gate.
- Do not silently include `admin.notes.reopen` in #020 because it is an adjacent discovered mutation route and was not part of the original #020 affected-route matrix.

## Related #027 - Admin invoice creation bypasses transaction-entry gate

#027 is related through the same admin transaction-entry capability boundary. #020 covers admin note mutation routes missing `EnsureTransactionEntryAllowed`, while #027 covers admin procurement supplier-invoice creation missing `transaction.entry`.
