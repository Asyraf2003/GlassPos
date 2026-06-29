# 017 - Workspace edit payments ignore existing note payments

## Status

Fixed and verified.

## Severity

High.

## Source

Audit report #017: Workspace edit payments ignore existing note payments.

## Current Verified HEAD

Verified at:

- HEAD: `4859ab8b`
- Branch: `main`
- Remote alignment shown: `origin/main`, `origin/HEAD`
- Commit label: `commit 1723`

Source/local command output is the source of truth for this status.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- `008-legacy-paid-notes-can-be-paid-again.md`
- `005-note-revision-silently-drops-overpaid-allocations.md`
- `011-cashier-revision-path-mutates-settled-note-state.md`

### Jenis Keterkaitan

Direct existing-payment/payment-allocation relationship with #008.

Direct financial allocation integrity relationship with #005.

Indirect workspace editability relationship with #011.

### Alasan

Laporan #017 dan #008 sama-sama membahas flow pembayaran yang mengabaikan existing note payments.

- #008 terjadi pada selected-row payment flow yang memakai projection component-only dan mengabaikan legacy `payment_allocations`.
- #017 terjadi pada workspace inline payment flow yang menganggap existing allocation total adalah zero, sehingga inline payment bisa dicatat terhadap total penuh note, bukan outstanding.

Laporan #017 berkaitan dengan #005 karena sama-sama menyentuh payment allocation integrity saat note berubah melalui workspace/revision/edit flow.

Laporan #017 berkaitan tidak langsung dengan #011 karena partially paid open note masih dapat diedit, lalu inline payment path berjalan pada note yang sudah memiliki financial state sensitif.

Karena root cause, service, dan patch berbeda, laporan #017 dicatat sebagai file terpisah.

## Ringkasan Indonesia

Bug terjadi pada inline payment workspace flow untuk note yang sudah memiliki payment allocation.

Flow bermasalah:

1. User membuat atau mengubah workspace note yang sudah punya existing payment allocation.
2. Inline payment diproses memakai service create/workspace.
3. `pay_full` dihitung dari total penuh note.
4. Partial payment divalidasi terhadap total penuh note, bukan outstanding.
5. Recorder memanggil `PaymentAllocationPolicy` dengan existing note allocation = `Money::zero()`.
6. Sistem bisa mencatat payment baru melebihi sisa tagihan sebenarnya.

Contoh red characterization:

- Note total: `100.000`
- Existing legacy allocation: `40.000`
- Outstanding seharusnya: `60.000`
- Behavior lama: `pay_full` mencatat `100.000`
- Expected behavior: `pay_full` hanya mencatat `60.000`

## Dampak

Dampak utama:

- open partially paid note bisa menerima inline payment berlebih
- `customer_payments` dapat overstated
- `payment_component_allocations` dapat melebihi outstanding sebenarnya
- cash ledger/reporting bisa salah
- note settlement dan audit payment menjadi tidak akurat
- legacy `payment_allocations` bisa diabaikan walaupun masih didukung oleh settlement reader

Severity High tepat karena payment settlement dan cash reports adalah core financial-integrity data untuk POS/back-office. Tidak Critical karena membutuhkan authenticated cashier/admin, valid CSRF/session, target note yang editable/open, dan existing payment state.

## Jalur Risiko

Workflow risiko:

1. User login sebagai cashier/admin.
2. Target note masih editable/open tetapi sudah punya existing payment allocation.
3. User submit workspace flow dengan inline payment.
4. Inline payment resolver menghitung amount.
5. Amount resolver lama menghitung `pay_full` dari full note total.
6. Recorder lama memanggil `PaymentAllocationPolicy` dengan existing allocation zero.
7. Policy menerima payment karena prior allocations tidak dihitung.
8. Customer payment dan component allocation baru dibuat.
9. Combined payment bisa melebihi true outstanding.

## Root Cause

Root cause:

Inline payment services untuk create/workspace flow tidak membawa existing settlement state.

Kesalahan spesifik:

1. `CreateTransactionWorkspaceInlinePaymentAmountResolver` menghitung `pay_full` dari note total penuh.
2. `resolvePartial` membandingkan partial payment dengan note total penuh, bukan outstanding.
3. `CreateTransactionWorkspaceInlinePaymentRecorder` memanggil `PaymentAllocationPolicy` dengan existing allocated note total = `Money::zero()`.
4. Existing payment allocation, termasuk legacy `payment_allocations`, tidak dipakai saat validasi inline payment.

## Invalid Characterization Yang Dibuang

Percobaan HTTP test lama menargetkan route:

`cashier.notes.workspace.update`

Route tersebut saat ini map ke:

`StoreNoteRevisionController`

Request normalizer pada route itu memaksa inline payment menjadi skip:

`StoreNoteRevisionRequest` sets `inline_payment.decision = skip`.

Karena itu HTTP red test lama tidak valid untuk root #017. Test tersebut dihapus dari:

`tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php`

Valid root characterization dipindahkan ke direct service/application-level test terhadap:

`CreateTransactionWorkspaceInlinePaymentRecorder`

## Valid Red Characterization

Test file:

`tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest.php`

Test:

`test_pay_full_uses_outstanding_after_existing_legacy_allocation`

Scenario:

- seed note `note-inline-017-1`
- total note: `100.000`
- seed existing `customer_payments`: `40.000`
- seed existing legacy `payment_allocations`: `40.000`
- call `CreateTransactionWorkspaceInlinePaymentRecorder::record()` directly
- inline payment decision: `pay_full`
- expected new payment: `60.000`
- expected legacy allocation remains: `40.000`
- expected new component allocation: `60.000`
- expected combined allocated total: `100.000`

Red proof:

Command:

`php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest.php --filter='pay_full_uses_outstanding_after_existing_legacy_allocation'`

Result:

- `FAIL`
- `1 failed, 1 assertion`
- Failure: expected `60000`, actual `100000`
- Failure line: `tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest.php:104`

Meaning:

`pay_full` recorded full note total `100.000` even though existing legacy allocation was `40.000` and true outstanding was `60.000`.

## Patch Summary

Patch diterapkan pada:

`app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentAmountResolver.php`

Perubahan:

- inject `PaymentAllocationReaderPort`
- compute existing allocated total with:
  `getTotalAllocatedAmountByNoteId($note->id())`
- compute outstanding:
  `max(note total - existing allocated, 0)`
- `pay_full` now resolves to outstanding amount
- partial payment is compared against outstanding amount
- partial payment `>= outstanding` now throws:
  `Nominal pembayaran sebagian harus lebih kecil dari sisa tagihan.`
- zero-outstanding note now rejects inline payment with:
  `Nota sudah tidak memiliki sisa tagihan.`

Patch diterapkan pada:

`app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`

Perubahan:

- inject `PaymentAllocationReaderPort`
- before policy check, read existing note allocation:
  `getTotalAllocatedAmountByNoteId($note->id())`
- `PaymentAllocationPolicy::assertAllocatable()` now receives existing allocated note total instead of `Money::zero()`

Efek patch:

- inline payment on existing note respects outstanding balance
- `pay_full` no longer overpays full total if note already has allocation
- policy check receives existing note allocation
- legacy allocation total is counted through `PaymentAllocationReaderPort` fallback

## Related Regression Found During Verification

Wider verification exposed selected-row payment failures after current-revision projection became the source for billing rows.

This was not the original #017 root, but it blocked the wider Note/Payment verification required for safe closure.

Additional narrow patches were applied to preserve selected-row payment behavior under current revision projection.

### Current Revision Billing Projection Patch

Files:

`app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailRowMapper.php`

`app/Application/Note/Services/NoteBillingProjectionFromWorkspaceRowsBuilder.php`

Reason:

Current-revision workspace rows were projected as line-level billing rows only. Selected component IDs such as:

`wi-1::service_store_stock_part::ssl-1`

were expanded to work item `wi-1`, causing selected outstanding to become full line total instead of selected component total.

Perubahan:

- expose `billing_components` from revision payload
- expand `store_stock_lines`, `external_purchase_lines`, and service fee into component-level billing rows
- keep line-level fallback when component payload is unavailable
- preserve component IDs compatible with selected-row payment flow:
  - `workItemId::service_store_stock_part::storeStockLineId`
  - `workItemId::service_external_purchase_part::externalLineId`
  - `workItemId::service_fee::workItemId`
  - `workItemId::product_only_work_item::workItemId`

### Selected Paid Row Error Semantics Patch

File:

`app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php`

Reason:

After component-level projection, legacy selection by work item ID against a fully paid row could collapse into an empty expanded selection and return a generic outstanding error.

Perubahan:

- preserve original selected IDs before expansion
- if selected IDs only target settled rows, return the existing specific error:
  `Hanya billing row outstanding yang boleh dipilih untuk pembayaran.`

## Test Fixture Patch

File:

`tests/Support/SeedsMinimalNotePaymentFixture.php`

Added current-revision fixture helpers:

- `seedServiceOnlyCurrentRevision(...)`
- `seedServiceWithStoreStockCurrentRevision(...)`
- `seedCurrentRevision(...)`

Updated tests:

- `tests/Feature/Note/CashierClosedReplacementOutstandingPaymentFeatureTest.php`
- `tests/Feature/Note/CashierHybridPaymentComponentSelectionFeatureTest.php`
- `tests/Feature/Note/CashierHybridPaymentDpPresetFeatureTest.php`
- `tests/Feature/Note/CashierHybridPaymentSettleIntentFeatureTest.php`

Reason:

Selected-row payment now requires current revision workspace projection. Old fixtures seeded legacy `notes/work_items` only and did not seed `note_revisions`.

## Scope In

- Workspace inline payment path.
- Inline payment amount resolution.
- Existing note allocation awareness.
- `pay_full` based on outstanding.
- partial payment validation against outstanding.
- `PaymentAllocationPolicy` existing allocation input.
- Current-revision billing projection needed for selected-row payment regression safety.
- Test fixture current revision support.

## Scope Out

- Reporting rewrite.
- `note_current_lines` migration.
- Refund engine rewrite.
- Settlement semantics rewrite.
- Production cleanup for already-overpaid notes.
- Connector branch/commit/push automation.
- Global #001 final verification claim.

## Proof

### Syntax Checks

Passed:

`php -l app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentAmountResolver.php`

`php -l app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`

`php -l app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailRowMapper.php`

`php -l app/Application/Note/Services/NoteBillingProjectionFromWorkspaceRowsBuilder.php`

`php -l app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php`

### Targeted 017 Green Proof

Command:

`php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest.php --filter='pay_full_uses_outstanding_after_existing_legacy_allocation'`

Result:

- `PASS`
- `1 passed`
- `5 assertions`

### Focused Selected-row + 017 Regression Proof

Command:

`php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php tests/Feature/Note/CashierClosedReplacementOutstandingPaymentFeatureTest.php tests/Feature/Note/CashierHybridPaymentComponentSelectionFeatureTest.php tests/Feature/Note/CashierHybridPaymentDpPresetFeatureTest.php tests/Feature/Note/CashierHybridPaymentSettleIntentFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest.php`

Result:

- `PASS`
- `7 passed`
- `32 assertions`

### Wider Note + Payment Proof

Command:

`php artisan test tests/Feature/Note tests/Feature/Payment`

Result:

- `PASS`
- `161 passed`
- `949 assertions`

## Verification Gap

No known verification gap remains for #017 within current active scope.

Still out of scope:

- full global suite
- reporting suite
- browser/manual QA
- production data cleanup
- final #001 global verification claim

## Final Decision

#017 is fixed and verified for the active finance residual slice.

The original root was fixed by making inline payment amount and policy validation allocation-aware.

The verification-discovered current-revision selected-row projection regression was also fixed because it blocked safe Note/Payment proof and protected the same payment integrity surface.

