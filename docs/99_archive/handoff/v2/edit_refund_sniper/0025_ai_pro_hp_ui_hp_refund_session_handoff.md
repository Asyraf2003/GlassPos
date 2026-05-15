# AI Pro Edit/Refund Sniper Handoff 0025 - HP-UI and HP-REFUND Closure

## Final Goal

Stabilize HyperPOS edit/refund transaction correctness using sniper-scoped AI Pro triage remediation.

Do not broad-audit the whole repo. Continue only from documented AI Pro target sections in:

- `docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md`

## Current Scope

Completed in latest session:

1. `HP-UI-001 - shared UI surplus action role-agnostic`
2. `HP-REFUND-001 - selected-row refund race`

Next session must continue from the remaining AI Pro target list only after verifying local status.

Remaining candidates in `0024` after HP-UI and HP-REFUND:

- `HP-INV-001 - inventory reversal idempotency race`
- `HP-ROWS-001 - duplicate line_no under concurrent add rows`
- `HP-REPORT-001 - operational profit may omit surplus_refund_paid`
- `HP-IDEMP-001 - refund_paid deterministic idempotency key`

## Mandatory Workflow Rules

- Blueprint/proof-first.
- Do not broad-audit the whole repo.
- Do not assume repo status.
- Local command output from owner is source of truth.
- User uses `rg` and `fd`; do not use `grep -R` or `find`.
- User handles git commit/push manually.
- Do not manage git push/remote sync unless explicitly asked.
- Do not claim fixed, full green, or safe without command proof.
- One active target per step.
- Do not start HP-INV, HP-ROWS, HP-REPORT, or HP-IDEMP before verifying HP-UI and HP-REFUND closure anchors.

## Completed: HP-UI-001

### Status

Session-safe locally.

### Problem

Shared note partial rendered admin-only surplus disposition mutation actions on cashier detail pages.

Admin-only actions involved:

- `Tandai Refund Due`
- `Catat Refund Paid`

The shared partial route targets were admin-only:

- `admin.notes.revision-settlements.refund-due.store`
- `admin.notes.revision-surplus-dispositions.refund-paid.store`

### Files Changed

- `app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php`
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `tests/Feature/Note/CashierNoteSurplusRefundDueUiAccessFeatureTest.php`
- `docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md`

### Fix

Admin note detail now passes:

- `canManageSurplusDisposition => true`

Shared partial now requires:

- `($canManageSurplusDisposition ?? false)`

Guard applies to both:

- pending refund_due action
- refund_paid action

Cashier controller remains unchanged, so missing flag defaults to false.

### Proof

Source anchors verified:

- `canManageSurplusDisposition => true` in admin note detail controller.
- Shared partial guards both refund_due and refund_paid blocks with `($canManageSurplusDisposition ?? false)`.

Focused tests passed:

- `tests/Feature/Note/CashierNoteSurplusRefundDueUiAccessFeatureTest.php`
- `tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php`
- `tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php`

Result:

- `4 passed / 42 assertions`

Small blast-radius passed:

- `tests/Feature/Note/CashierNoteSurplusRefundDueUiAccessFeatureTest.php`
- `tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php`
- `tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php`
- `tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php`
- `tests/Feature/Note/CashierNoteDetailAccessGuardFeatureTest.php`
- `tests/Feature/Note/CashierNoteDetailPaymentActionPolicyFeatureTest.php`
- `tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php`

Result:

- `15 passed / 80 assertions`

## Completed: HP-REFUND-001

### Status

Confirmed RED, fixed GREEN, make verify green.

### Problem

Selected-row refund flow had a source-level serialization gap.

Concrete source-risk proof:

- `RecordClosedNoteRefundController` resolved `SelectedRowsRefundPlan` before transaction.
- `SelectedNoteRowsRefundPlanResolver` built refund buckets from payment/refund component allocation readers.
- `PaymentComponentAllocationReaderPort` and `RefundComponentAllocationReaderPort` did not expose for-update reader methods.
- `DatabasePaymentComponentAllocationReaderAdapter::listByNoteId(...)` read without `lockForUpdate()`.
- `DatabaseRefundComponentAllocationReaderAdapter::listByNoteId(...)` read without `lockForUpdate()`.
- `RecordCustomerRefundOperation` previously loaded note through `NoteReaderPort::getById(...)`.
- `refund_component_allocations` unique constraint was scoped to `customer_refund_id + component_type + component_ref_id`, so two different refunds could still target the same component at DB constraint level.

### Files Changed

- `app/Application/Payment/Services/RecordCustomerRefundOperation.php`
- `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
- `docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md`

### RED Proof

Added lock-invariant regression:

- `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
- `test_selected_row_refund_locks_note_before_refund_allocation_reads`

Initial RED failed after successful selected-row refund:

- `forUpdateCalls` was `0`
- Failure message: selected-row customer refund must lock the note before refund allocation reads to serialize concurrent refunds.

Targeted RED:

- `1 failed / 2 assertions`

### Fix

Minimal GREEN patch:

- `app/Application/Payment/Services/RecordCustomerRefundOperation.php`

Changed:

- `NoteReaderPort::getById(...)`

To:

- `NoteReaderPort::getByIdForUpdate(...)`

This acquires the existing note row lock inside the transaction before pair-limit validation and component refund allocation.

### GREEN Proof

Syntax:

- `php -l app/Application/Payment/Services/RecordCustomerRefundOperation.php`
- `php -l tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`

Targeted GREEN:

- `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php --filter=selected_row_refund_locks_note_before_refund_allocation_reads`

Result:

- `1 passed / 2 assertions`

Focused refund blast-radius:

- `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
- `tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`
- `tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
- `tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php`
- `tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php`
- `tests/Unit/Application/Note/Services/SelectedRowsRefundBucketsBuilderTest.php`

Result:

- `19 passed / 99 assertions`

Full `make verify` proof:

- Final visible test summary: `1051 passed / 5658 assertions`
- Duration: `56.37s`

### Remaining Gaps

Keep these as explicit future gaps, not blockers for this patch:

- No true parallel two-connection stress test.
- No explicit DB lock wait/timeout assertion.
- No browser/manual QA.

Do not broaden HP-REFUND unless new RED proof shows over-refund or duplicate stock reversal can still happen.

## Latest Known Progress

- Final Goal Progress: 15%
- Main Process Progress: 74%
- HP-UI Sub-step Progress: 100%
- HP-REFUND Sub-step Progress: 100%
- Session Context Health at handoff: 75%, risky but not forced handoff.

## Safest Next Step

Start next session by verifying local state and reading only the remaining AI Pro target sections.

Do not start with broad repo audit.

Recommended next candidate:

1. `HP-INV-001 - inventory reversal idempotency race`

Only move to HP-ROWS, HP-REPORT, or HP-IDEMP if HP-INV is already fixed, false positive, or intentionally deferred with proof.

## Opening Prompt For Next Session

Kita sedang di repo HyperPOS Laravel, root lokal:

`/home/asyraf/Code/laravel/bengkel2/app`

Mode kerja wajib:

- Blueprint/proof-first.
- Jangan broad audit satu repo.
- Jangan asumsi status repo.
- Local command output owner adalah source of truth tertinggi.
- User memakai `rg` dan `fd`; jangan pakai `grep -R` atau `find`.
- User handles git commit/push manual.
- Jangan urus git push/remote sync kecuali diminta.
- Jangan klaim fixed/full green tanpa proof command lokal.
- One active target per step.

Dokumen utama:

- `docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md`
- `docs/99_archive/handoff/v2/edit_refund_sniper/0025_ai_pro_hp_ui_hp_refund_session_handoff.md`

Status terakhir:

- HP-UI-001 sudah session-safe locally.
  - Source anchors verified.
  - Docs updated.
  - Focused tests: `4 passed / 42 assertions`.
  - Small blast-radius: `15 passed / 80 assertions`.

- HP-REFUND-001 sudah Confirmed RED, fixed GREEN, make verify green.
  - RED: selected-row refund did not call `getByIdForUpdate`; `forUpdateCalls = 0`.
  - Source fix: `app/Application/Payment/Services/RecordCustomerRefundOperation.php` now uses `NoteReaderPort::getByIdForUpdate(...)`.
  - Test added: `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php::test_selected_row_refund_locks_note_before_refund_allocation_reads`.
  - Targeted GREEN: `1 passed / 2 assertions`.
  - Focused refund blast-radius: `19 passed / 99 assertions`.
  - Full `make verify`: final visible summary `1051 passed / 5658 assertions`, duration `56.37s`.
  - Docs updated in `0024`.

Remaining candidates:

- `HP-INV-001 - inventory reversal idempotency race`
- `HP-ROWS-001 - duplicate line_no under concurrent add rows`
- `HP-REPORT-001 - operational profit may omit surplus_refund_paid`
- `HP-IDEMP-001 - refund_paid deterministic idempotency key`

Active task for new session:

Step 1 only: verify local state and inspect `HP-INV-001` section. Do not patch yet.

First commands from repo root:

~~~bash
printf '\n--- local status ---\n'
git status --short

printf '\n--- latest handoff docs exist ---\n'
ls -la docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md \
  docs/99_archive/handoff/v2/edit_refund_sniper/0025_ai_pro_hp_ui_hp_refund_session_handoff.md

printf '\n--- HP-INV docs section ---\n'
sed -n '/### HP-INV-001/,/### HP-ROWS-001/p' \
  docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md

printf '\n--- HP-INV candidate source anchors ---\n'
rg -n "AutoReverseRefundedStoreStockInventory|executeFullRowReversal|inventory_movements|source_type|source_id|related_customer_refund_id|refund_component_allocations|customer_refunds|lockForUpdate|ForUpdate|idempot" \
  app tests database/migrations \
  --glob '*.php'
~~~

Expected next response behavior:

If local status shows dirty files, classify them before proceeding.

If HP-INV docs are still `Status: Suspected`, inspect only inventory reversal refund seam.

Do not start HP-ROWS, HP-REPORT, or HP-IDEMP yet.

Do not patch until source-risk and RED placement are clear.

If Session Context Health reaches 80%+, create handoff before continuing.
