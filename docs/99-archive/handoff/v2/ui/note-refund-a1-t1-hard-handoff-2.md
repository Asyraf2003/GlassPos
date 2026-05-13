# HANDOFF V2 UI / NOTE REFUND / A1 + T1-HARD / FINALIZED VERIFY-GREEN
Status date: 2026-04-24  
Project: Kasir / Nota Pelanggan  
This file replaces the older handoff that still reported 40% overall and 75% UI.

---

# 1. Final Status

## Current state
Branch is now **verify-green**.

Latest proven result:
- `make verify` passed
- `727 tests passed`
- `3778 assertions`
- duration `32.93s`

This means:
- current implementation is no longer in migration-red state
- handoff that said branch was not merge-safe is obsolete
- next chat should treat this page as current source of truth

---

# 2. Final Progress

## End-to-end target
**100% for the current scoped work page**

Reason:
The scoped target for this work page was:
- note detail UI stabilization
- refund modal/UI anchor
- versioning/detail cleanup
- A1 refund contract migration
- T1-hard active-total direction
- selected-row refund flow stabilization
- verify clean

That scoped target is now achieved because the repo is verify-green.

## UI-only target
**100% for the current scoped UI page**

Reason:
The note detail / refund / versioning family is already aligned to the locked UI direction and the repository now verifies cleanly with that direction.

---

# 3. Locked Final Decisions

## UI decisions
1. `Billing Projection` stays removed.
2. `Current Revision` stays replaced by `Revision Aktif`.
3. `Versioning Nota` stays as the section title.
4. Detail page remains centered around:
   - line list
   - precise header
   - tighter action hierarchy
   - refund modal two-column layout
5. Refund launcher remains unified.

## Refund contract decisions
1. Refund follows **A1**:
   - selected-row based
   - no manual `customer_payment_id` from UI
   - no manual `amount_rupiah` from UI
   - server resolves payment-source buckets automatically
2. Refund meaning:
   - selected line is no longer an active transaction line
3. Unpaid selected rows:
   - remain allowed in final contract
   - do not need fake refund ledger amount 0
4. Controller/request contract is already migrated to:
   - `selected_row_ids[]`
   - `refunded_at`
   - `reason`

## Total / note behavior decisions
1. Direction follows **T1-hard** at page/domain scope:
   - active note meaning follows active lines
   - canceled/refunded rows remain as history
2. Reader + total behavior is now stabilized enough to satisfy full verify.

## Payment/refund component decisions
1. Payment component priority has been aligned so product / stock / external-part flow no longer follows the older fee-first assumption.
2. Legacy tests that asserted old priority/copy behavior were migrated to the new contract.

---

# 4. What Was Completed

## A. UI detail family completed
Affected family already stabilized:
- `resources/views/shared/notes/show.blade.php`
- `resources/views/shared/notes/partials/line-workspace.blade.php`
- `resources/views/shared/notes/partials/header-summary.blade.php`
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `resources/views/shared/notes/partials/versioning-compact.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-refund.js`

### Final UI outcome
- tighter note detail page
- versioning wording updated
- refund modal aligned with selected-row UX
- billing projection removed
- note page no longer depends on the old wording structure

## B. Backend refund groundwork completed
Completed family includes:
- refund plan DTOs
- payment bucket DTOs
- selected-row refund plan resolver
- selected-row cancel and active-total sync
- aggregate refund transaction scaffolding
- finalize refunded note flow
- selected-row request/controller rewiring

## C. Governance completed
- file-length governance cleaned up
- line-limit issues resolved
- repository returned to verify-green state

## D. Legacy test migration completed
Migrated:
- versioning copy expectations
- billing projection legacy expectations
- old refund reject-open-line expectation
- old payment component priority expectation
- old partial selected-row refund expectations that no longer matched A1 contract

---

# 5. Final Working Contract For Next Chat

Next chat must assume these as already settled:

## Settled
- no need to reopen debate about A1
- no need to reopen debate about T1-hard for this page
- no need to reopen `Billing Projection`
- no need to reopen `Current Revision`
- no need to ask whether branch is healthy
- no need to ask whether verify is green

## Already true
- branch is verify-green
- current page work is complete
- next work should start from a **new scoped page**, not by re-litigating this one

---

# 6. Safest Next Actions

Next chat should choose only one of these:

## Option A - Freeze and document
If the goal is handoff/admin cleanliness:
- review changed files
- prepare PR summary
- prepare release note / changelog note
- document user-facing behavior changes for note detail and refund page

## Option B - Start next page
If continuing product work:
- open a new scope/page separate from this handoff
- do not blend this finished note/refund work with a new problem statement

---

# 7. Do Not Do

1. Do not restore `Billing Projection`.
2. Do not restore `Current Revision`.
3. Do not reintroduce manual `customer_payment_id` to refund UI.
4. Do not reintroduce manual `amount_rupiah` to refund UI.
5. Do not downgrade component priority back to the old expectation just to match obsolete reasoning.
6. Do not describe this page as “still 40%” or “still 75%”.
7. Do not reopen this page unless a brand-new regression is proven with new evidence.

---

# 8. Final Completion Statement

This scoped work page is considered **complete** because:
- UI direction is implemented
- refund contract migration is implemented
- note detail/versioning direction is implemented
- repository governance is clean
- `make verify` passes

## Final status
- page status: **completed**
- branch health: **verify-green**
- current handoff status: **replace older stale handoff immediately**

