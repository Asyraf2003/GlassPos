# AI Pro Repo Logic Audit Triage Handoff

## Scope

This handoff records the triage result from the AI Pro repo-wide logic/security/math audit during the edit refund sniper track.

Primary audit focus:
- security / authorization
- transaction math
- payment / refund / allocation / settlement
- inventory / stock movement / COGS
- profit / laba reporting
- idempotency / concurrency
- UI-to-logic consistency

Seeder is excluded as the primary bug source.
Cosmetic UI is excluded.
UI connected to business logic is included.

## Source of Truth

Local command output from owner is the highest source of truth.

AI Pro findings are treated as leads until proven locally.

Finding status levels:
- Confirmed RED: local test/source proof reproduces unsafe behavior.
- Fixed GREEN: local patch plus focused tests pass.
- Suspected: plausible from source reading but no RED proof yet.
- False positive: disproven by source/test proof.
- Needs narrowing: finding wording is too broad or stale.

## AI Pro Findings Received

AI Pro reported 8 findings:

1. HP-AUTH-001 — P0 — cashier can call refund_due cashier route wired to admin controller.
2. HP-REFUND-001 — P0 — selected-row refund race can double refund and double reverse stock.
3. HP-SURPLUS-001 — P0 — surplus refund_due race can exceed pending surplus.
4. HP-UI-001 — P1 — surplus action UI is role-agnostic in shared note view.
5. HP-INV-001 — P1 — inventory reversal idempotency race risk.
6. HP-ROWS-001 — P1 — concurrent add rows can duplicate line_no.
7. HP-REPORT-001 — P1 — operational profit may omit surplus_refund_paid cash-out.
8. HP-IDEMP-001 — P1 — refund_paid UI idempotency key is deterministic and stale-tab collision-prone.

## Confirmed and Fixed

### HP-AUTH-001 — Cashier refund_due route used admin controller

Status: Fixed GREEN.

Severity: P0.

### Bug mechanism

A cashier route existed:

- POST /cashier/notes/revision-settlements/{settlementId}/refund-due

It was wired to:

- AdminCreateNoteRevisionSurplusRefundDueController

The cashier route sat inside the cashier notes group but outside EnsureCashierNoteAccess.

The admin controller created the command using admin semantics, including admin actor role / admin source channel.

### Impact

A cashier request could enter an admin-only refund_due creation path.

The RED response redirected to the admin note detail path instead of being denied.

### RED proof

Added test:

- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
- test_cashier_cannot_create_refund_due_through_cashier_route

Initial RED result:

- php -l tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
- PASS.

Focused RED:

- php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php --filter=cashier

Result:

- cashier cannot access admin refund due route: PASS.
- cashier cannot create refund due through cashier route: FAIL.

Failure:

- Expected redirect: http://localhost:8000/cashier/dashboard
- Actual redirect: http://localhost:8000/admin/notes/note-root-http-001

This confirmed the cashier route was not denied and entered the admin refund_due path.

### Patch

Changed:

- routes/web/note.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

Patch summary:

- Restored/kept the admin refund_due route.
- Removed the cashier refund_due route.
- Changed the new cashier route regression test to call the hardcoded deleted cashier URL.
- Expected response for the deleted cashier URL is 404.
- Kept admin route behavior intact.

### GREEN proof

Syntax:

- php -l routes/web/note.php
- PASS: No syntax errors detected.

- php -l tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
- PASS: No syntax errors detected.

Route proof:

- php artisan route:list | grep "revision-settlements.*refund-due" || true

Result:

- Only admin route remains:
  - POST admin/notes/revision-settlements/{settlementId}/refund-due

Focused cashier proof:

- php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php --filter=cashier

Result:

- PASS: 2 tests / 6 assertions.

Full controller feature proof:

- php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

Result:

- PASS: 6 tests / 28 assertions.

Adjacent UI/payload proof:

- php artisan test tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php tests/Feature/Note/NoteDetailSurplusDispositionPayloadFeatureTest.php

Result:

- PASS: 3 tests / 32 assertions.

## Out of Scope for HP-AUTH-001 Fix

Not changed:
- controller
- request
- handler
- guard
- UI
- refund_paid
- docs before proof
- git sync / push

Owner handles commit/push manually.

## Remaining Findings Not Yet Confirmed

### HP-REFUND-001 — selected-row refund race

Status: Confirmed RED, fixed GREEN.

Original AI Pro claim:
- Selected-row refund flow had a plausible race window that could allow double refund / over-refund / duplicate stock reversal.

Local source-risk proof:
- `RecordClosedNoteRefundController` resolves `SelectedRowsRefundPlan` before entering the selected-row refund transaction.
- `SelectedNoteRowsRefundPlanResolver` builds refund buckets from `payment_component_allocations` and `refund_component_allocations` using normal reader methods.
- `PaymentComponentAllocationReaderPort` and `RefundComponentAllocationReaderPort` did not expose for-update reader methods.
- `DatabasePaymentComponentAllocationReaderAdapter::listByNoteId(...)` and `DatabaseRefundComponentAllocationReaderAdapter::listByNoteId(...)` read without `lockForUpdate()`.
- `RecordCustomerRefundOperation` previously loaded the note through `NoteReaderPort::getById(...)`, so selected-row refund did not acquire the existing note row lock before refund allocation reads.
- `refund_component_allocations` unique constraint is scoped to `customer_refund_id + component_type + component_ref_id`, so two different refunds are not prevented at the database layer from writing the same component.

Local RED proof:
- Added lock-invariant regression:
  - `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
  - `test_selected_row_refund_locks_note_before_refund_allocation_reads`
- RED result:
  - Test reached the assertion after a successful selected-row refund.
  - Failure: `forUpdateCalls` was `0`.
  - Message: selected-row customer refund must lock the note before refund allocation reads to serialize concurrent refunds.
- Targeted RED:
  - `1 failed, 2 assertions`.

Production fix:
- `app/Application/Payment/Services/RecordCustomerRefundOperation.php`
  - Changed note read from `NoteReaderPort::getById(...)` to `NoteReaderPort::getByIdForUpdate(...)`.
  - This makes customer refund operation acquire the note row lock inside the existing transaction before pair-limit validation and component refund allocation.

Local GREEN proof:
- Syntax:
  - `php -l app/Application/Payment/Services/RecordCustomerRefundOperation.php`
  - `php -l tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
- Targeted GREEN:
  - `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php --filter=selected_row_refund_locks_note_before_refund_allocation_reads`
  - Result: `1 passed, 2 assertions`.
- Focused refund blast-radius:
  - `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
  - `tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`
  - `tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
  - `tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php`
  - `tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php`
  - `tests/Unit/Application/Note/Services/SelectedRowsRefundBucketsBuilderTest.php`
  - Result: `19 passed, 99 assertions`.

Conclusion:
- HP-REFUND-001 was a real source-level serialization gap.
- The selected-row refund operation now uses the existing note row lock seam before refund allocation reads.
- The fix is intentionally minimal and does not add schema constraints, new idempotency keys, or true parallel test infrastructure.

Remaining verification gaps:
- No true parallel two-connection stress test was executed.
- No explicit DB lock wait or timeout assertion was executed.
- No full `make verify` run was executed for this HP-REFUND patch.
- No browser/manual QA was executed.

Next action:
- Do not broaden this HP-REFUND patch unless a new RED proof shows over-refund or duplicate stock reversal can still happen.
- If deeper concurrency proof is needed later, add a dedicated two-connection selected-row refund stress test around the same note and selected row.

### HP-SURPLUS-001 - refund_due race can exceed pending surplus

Status: Source-mitigated and locally verified by transaction-level invariant.

Original AI Pro claim:
- Two concurrent `refund_due` requests for the same pending surplus settlement could both read the same remaining surplus and create active dispositions that exceed pending surplus.

Local source proof:
- `CreateNoteRevisionSurplusRefundDueHandler` starts a transaction before reading pending settlement.
- The handler reads pending settlement through `findPendingBySettlementIdForUpdate(...)`.
- The handler validates requested amount after the locked pending read.
- The handler writes the disposition before commit.
- `DatabaseNoteRevisionSurplusPendingQuery` applies `lockForUpdate()` to the `note_revision_settlements` row when the for-update reader path is used.
- The pending query computes already-active dispositions from `note_revision_surplus_dispositions` where `status = active`.

Local verification proof:
- Source anchors inspected locally:
  - `app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php`
  - `app/Adapters/Out/Note/DatabaseNoteRevisionSurplusPendingQuery.php`
  - `app/Adapters/Out/Persistence/DatabaseTransactionManagerAdapter.php`
- Focused tests passed:
  - `tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php`
  - `tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php`
  - `tests/Feature/Note/DatabaseNoteRevisionSurplusDispositionAdapterTest.php`
  - `tests/Feature/Database/NoteRevisionSurplusDispositionMigrationTest.php`
- Result: 18 passed, 101 assertions.

Conclusion:
- The suspected race is mitigated by transaction-scoped settlement row locking plus post-lock active disposition aggregation.
- No production source patch was required for this finding.

Remaining verification gaps:
- No true parallel two-connection stress test was executed.
- No explicit DB lock wait or timeout assertion was executed.
- No full `make verify` run was executed for this documentation-only status update.

Next action:
- Do not patch HP-SURPLUS-001 source unless a new RED proof shows the invariant can still be violated.
- If deeper proof is needed later, add a dedicated concurrency stress test around two simultaneous refund_due requests for the same settlement.

### HP-UI-001 - shared UI surplus action role-agnostic

Status: Confirmed RED, fixed GREEN.

Original AI Pro claim:
- Shared surplus action UI could render admin-only surplus mutation actions outside the intended admin boundary.

Local RED proof:
- Added cashier DOM regression:
  - `tests/Feature/Note/CashierNoteSurplusRefundDueUiAccessFeatureTest.php`
- RED result:
  - Cashier detail rendered `Tandai Refund Due` for a note with pending surplus.
  - Failure: response HTML was expected not to contain `Tandai Refund Due`.
- Admin UI remained green in the same targeted run.

Root cause:
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php` rendered surplus refund_due action from shared note detail data.
- The refund_due form posts to admin route:
  - `admin.notes.revision-settlements.refund-due.store`
- Admin and cashier note detail pages share the same `shared.notes.show` view.
- The shared partial did not require an explicit admin-only render flag before showing surplus mutation actions.

Production fix:
- `app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php`
  - Adds `canManageSurplusDisposition => true` for admin detail rendering.
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
  - Requires `($canManageSurplusDisposition ?? false)` before rendering pending refund_due actions.
  - Requires `($canManageSurplusDisposition ?? false)` before rendering refund_paid actions.
- Cashier controller remains unchanged.
  - Missing flag defaults to false in the shared partial.

Local GREEN proof:
- Syntax:
  - `php -l app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php`
  - `php -l resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- Focused tests passed:
  - `tests/Feature/Note/CashierNoteSurplusRefundDueUiAccessFeatureTest.php`
  - `tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php`
  - `tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php`
- Result: 4 passed, 42 assertions.

Conclusion:
- HP-UI-001 was a real UI-to-logic boundary bug.
- The shared partial now requires an explicit admin render capability before showing admin-only surplus mutation forms.
- Refund_due and refund_paid surplus actions are both guarded by the same view boundary.

Remaining verification gaps:
- No full `make verify` run was executed for this HP-UI patch.
- No browser/manual QA was executed.
- No broader cashier note detail feature suite was executed after this patch.

Next action:
- Run focused cashier/admin note detail blast-radius tests before closing this finding as session-safe.
- Do not change backend refund_due/refund_paid handlers for HP-UI unless a new backend proof appears.

### HP-INV-001 — inventory reversal idempotency race

Status: Suspected.

Required proof:
- duplicate reversal test or DB invariant proof.

### HP-ROWS-001 — duplicate line_no under concurrent add rows

Status: Suspected.

Required proof:
- concurrent add rows test showing duplicate line_no or missing DB unique constraint impact.

### HP-REPORT-001 — operational profit may omit surplus_refund_paid

Status: Needs narrowing.

Reason:
Do not claim all transaction reporting omits surplus_refund_paid.
Existing transaction report / cash ledger paths may already include surplus_refund_paid.
The remaining question should be narrowed to operational profit/dashboard calculation only.

Required proof:
- identify exact report query.
- seed surplus_refund_paid.
- compare cash ledger vs operational profit output.

### HP-IDEMP-001 — refund_paid deterministic idempotency key

Status: Suspected / design risk.

Reason:
Potential stale-tab collision is plausible, but it is not proven as financial corruption.
Likely UX/idempotency semantics risk.

Required proof:
- same key, different amount stale-tab scenario.
- expected behavior decision: reject stale form vs allow new key per attempt.

## Recommended Next Sniper Target

Next safest target:

- HP-SURPLUS-001 refund_due race/idempotency.

Reason:
- Adjacent to the just-fixed HP-AUTH-001 route.
- Affects surplus settlement integrity.
- Likely smaller than selected-row refund plus inventory reversal race.

Required first step:
Read only:
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueGuard.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
- migration for note_revision_surplus_dispositions
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

Then produce a minimal RED/invariant test plan before patch.

## Do Not Do

Do not:
- accept all AI Pro findings as confirmed.
- broad audit entire repo again.
- patch concurrency risks without RED/invariant proof.
- touch seeders as primary bug source.
- ignore UI-to-logic boundary.
- manage git push/sync here.

- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueGuard.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
- migration for note_revision_surplus_dispositions
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

Before any patch:
- produce minimal RED/invariant test plan.
- do not accept AI Pro finding as confirmed without local RED or invariant proof.
- do not patch concurrency/idempotency risk directly.
- do not run broad repo audit.
- do not use seeders as primary bug source.
- include UI-to-logic boundary only if rendered action, route, payload, hidden input, idempotency_key, amount/default/max, status label, or role/status conditional rendering affects business logic.

## Next Session Opening Prompt

Lanjutkan HyperPOS edit_refund_sniper.

Current state:
- HP-AUTH-001 is Fixed GREEN with owner command proof.
- docs 0024 exists and records AI Pro audit triage.
- Owner handles commit/push/manual sync.
- Do not start with git status/log/diff.
- Do not broad repo audit.
- Do not patch before RED/invariant proof.

Next active target:
- HP-SURPLUS-001 — refund_due race/idempotency.

Read only:
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueGuard.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
- migration for note_revision_surplus_dispositions
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

First required output:
- FACT / GAP / ASSUMPTION / DECISION
- minimal RED/invariant test plan
- no patch yet
