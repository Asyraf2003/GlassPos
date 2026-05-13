# 022 - Cashier refund route bypasses note access guard

## Status

Fixed and locally verified for cashier refund route note-access enforcement.

Current Slice 5 re-verification confirms the #022 route/middleware behavior directly:
cashier refund POST is inside `EnsureCashierNoteAccess`, uses `ensureCanView`, and rejects historical notes.

Focused broad route/guard suite is not fully green in the current session because of adjacent refunded/edit UI guard failures outside #022 scope.

## Severity

High.

## Ringkasan

Route refund cashier dapat dipanggil tanpa melewati `EnsureCashierNoteAccess`.

Route `POST /cashier/notes/{noteId}/refunds` berada di dalam group cashier yang memiliki `auth`, `EnsureCashierAreaAccess`, `EnsureTransactionEntryAllowed`, dan `app.shell`, tetapi route tersebut sebelumnya berada di luar nested group `EnsureCashierNoteAccess`.

Akibatnya, cashier yang sudah login dan memiliki izin input transaksi dapat mengirim POST langsung ke endpoint refund untuk `Nota` closed yang seharusnya tidak bisa diakses melalui area cashier, termasuk nota historical di luar window akses normal, selama attacker mengetahui `noteId` dan `customer_payment_id`.

## Jalur rentan

Authenticated cashier session
-> submit POST `/cashier/notes/{noteId}/refunds`
-> route melewati auth, cashier-area, dan transaction-entry
-> route tidak melewati `EnsureCashierNoteAccess`
-> `RecordClosedNoteRefundRequest::authorize()` mengizinkan caller yang sudah mencapai route
-> controller hanya mengecek note ada dan operationally closed
-> controller meneruskan payment id, amount, date, reason ke refund handler
-> handler membatasi nominal berdasarkan allocation/payment rules
-> refund tetap tercatat pada note yang tidak boleh diakses cashier

## Root cause

Guard akses per-nota sudah ada, tetapi route refund cashier ditempatkan di luar group `EnsureCashierNoteAccess`.

Controller dan request juga tidak melakukan pengganti validasi akses note/date-window. Karena itu, boundary akses nota cashier tidak diterapkan pada endpoint mutasi refund.

## Source/docs mismatch proof

Dokumen ini sebelumnya menyatakan route sudah patched melalui commit report lama:

`6f3f2e1 - Protect cashier refund route with note access middleware`

Namun source lokal saat inspeksi #022 masih membuktikan route cashier refund berada di luar `EnsureCashierNoteAccess`.

Bukti source sebelum patch lokal:

`routes/web/note.php` masih memiliki:

`Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');`

di group cashier sebelum nested middleware:

`Route::middleware(EnsureCashierNoteAccess::class)->group(function (): void { ... })`

Dengan kondisi itu, `cashier.notes.refunds.store` masih bypass guard note-access cashier.

## RED proof

Regression test ditambahkan di:

`tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`

Test baru:

`test_cashier_cannot_record_refund_for_historical_note_outside_cashier_access_window`

Helper test diubah agar bisa membuat closed paid service-only note dengan tanggal transaksi eksplisit:

`seedClosedPaidServiceOnlyNote(?string $transactionDate = null): void`

RED command:

`php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=historical || true`

RED result:

Expected response status code `403` but received `302`.

`1 failed / 1 assertion`

Makna RED:

Cashier masih bisa POST refund untuk nota historical di luar access window karena endpoint refund belum melewati `EnsureCashierNoteAccess`.

## Production patch

File changed:

- `routes/web/note.php`
- `app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php`

Patch route:

Route cashier refund dipindahkan ke dalam nested group:

`Route::middleware(EnsureCashierNoteAccess::class)->group(function (): void { ... })`

Route yang diproteksi:

`Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');`

Patch middleware:

`cashier.notes.refunds.store` ditambahkan ke branch view access:

`$this->accessData->ensureCanView($noteId);`

Route refund sengaja tidak diarahkan ke branch:

`$this->accessData->ensureCanMutateOpenNote($noteId);`

Alasan:

Refund closed note untuk today/yesterday adalah existing intended behavior dan tetap harus lolos. Jika refund route jatuh ke mutate-open branch, refund closed note akan ikut terblokir dan lifecycle refund yang valid rusak.

## Route context proof

Command:

`sed -n '40,78p' routes/web/note.php`

Proof:

Route admin refund tetap berada di group admin sebagai route terpisah:

`admin.notes.refunds.store`

Route cashier refund berada di dalam group:

`Route::middleware(EnsureCashierNoteAccess::class)->group(function (): void { ... })`

Command:

`php artisan route:list | grep -n "refunds.store\|refunds" || true`

Proof:

- `POST admin/notes/{noteId}/refunds` -> `admin.notes.refunds.store`
- `POST cashier/notes/{noteId}/refunds` -> `cashier.notes.refunds.store`

Makna:

Dua route refund yang terlihat dari grep adalah route admin dan cashier yang berbeda, bukan duplicate cashier bypass route.

## Syntax proof

Commands:

`php -l routes/web/note.php`

`php -l app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php`

`php -l tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`

Result:

No syntax errors detected in all 3 files.

## GREEN proof

Historical targeted command:

`php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=historical`

Result:

`PASS`

`1 passed / 4 assertions`

Full controller targeted command:

`php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`

Result:

`PASS`

`5 passed / 31 assertions`

Passing controller cases:

- cashier can record refund for closed note
- cashier cannot record refund for historical note outside cashier access window
- cashier can record refund for open note
- refund request requires reason
- refund allocates only selected rows

## Focused blast-radius proof

Command:

`php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/CashierRefundSelectionFirstFeatureTest.php tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundProductOnlyInventoryLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php`

Result:

`PASS`

`21 passed / 113 assertions`

Focused coverage included:

- cashier refund route guard behavior
- closed note refund view behavior
- refunded note detail action visibility
- open line refund rejection/selection behavior
- protected cashier note route access guard behavior
- full refund lifecycle for service-only notes
- full refund lifecycle for product-only inventory notes
- full refund lifecycle for store-stock inventory notes
- full refund lifecycle for external-purchase notes

## Current Slice 5 Reverification

### Scope

This reverification checks only #022:

- cashier refund route placement
- cashier note-access middleware branch for refund route
- historical note refund POST rejection

It does not patch #015/#018 refunded workspace edit behavior.

### Source anchors

`routes/web/note.php`:

- `Route::middleware(EnsureCashierNoteAccess::class)->group(function (): void {`
- `Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');`

`app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php`:

- `cashier.notes.refunds.store` is included in the view-access branch
- refund route calls `$this->accessData->ensureCanView($noteId)`
- refund route does not fall through to `ensureCanMutateOpenNote($noteId)`

### Targeted proof

Command:

`php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php --filter=historical`

Result:

`PASS`

`1 passed / 4 assertions`

### Controller proof

Command:

`php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`

Result:

`PASS`

`5 passed / 34 assertions`

### Broad focused suite result

Command:

`php artisan test tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/CashierRefundSelectionFirstFeatureTest.php tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundProductOnlyInventoryLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php`

Result:

`FAIL`

`2 failed / 21 passed / 120 assertions`

Failures:

1. `CashierClosedNoteRefundViewFeatureTest::closed_note_detail_shows_refund_launcher...`
   - expected page to contain `Edit`
   - actual page did not contain `Edit`
   - classification: adjacent UI/edit visibility expectation, not #022 refund route POST access

2. `CashierProtectedNoteRoutesAccessGuardFeatureTest::cashier_cannot_open_workspace_edit_for_refunded_note...`
   - expected 403
   - actual 200
   - classification: refunded workspace edit guard issue, belongs to #015/#018 cluster, not #022 refund route POST access

### Reverification conclusion

#022 route-specific behavior is locally verified.

The broad focused suite is blocked by adjacent Slice 5 refunded/edit guard failures. Do not use the old broad focused PASS claim as current truth.


## Verification gaps

Current remaining gaps:

- full global suite was not run
- browser/manual QA was not run
- full `make verify` is not claimed
- broad route/guard focused suite is not green in the current session because of adjacent #015/#018 refunded/edit failures
- #022 itself has targeted local proof, but broad focused proof must not be claimed as current green proof

## Scope boundaries

#014 remains separate and is not patched here.

#015 remains separate and is not patched here.

#021 remains separate and is not patched here.

Open-note refund behavior is not changed here. Existing test still proves current behavior allows cashier refund for open note.

Blade/UI visibility is not changed here.

Audit-lines is not changed here.

## Relations

Direct follow-up to #021.

#021 covers missing whole-note closed invariant in the refund controller.

#022 covers missing cashier note-access guard on the refund route.

Terkait dengan #019 karena #019 membahas disclosure closed note historis melalui route tabel kasir, sedangkan #022 membahas mutasi refund unauthorized terhadap nota closed/historis jika identifier diketahui.

Terkait dengan #014 karena kedua masalah berada di cluster policy endpoint refund.

Related to #018 because refund lifecycle state must remain protected by cashier closed-note and terminal-state guards.
