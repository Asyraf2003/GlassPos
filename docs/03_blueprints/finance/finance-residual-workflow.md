# Finance Residual Error Log Workflow
## Status
Canonical Workflow.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03-blueprints/finance/finance-residual.md`


## Preferred Implementation Slices

### Slice 1 — Settlement Classification Normalization

Covers:

- 001
- 008
- part of 003
- part of 017

Goal:

- paid/open/refunded/outstanding state uses one explicit settlement policy
- legacy/current money distinction is clear
- paid notes cannot be paid again due to legacy detection gap

Tests:

1. refunded amount does not count as paid settlement
2. paid-state detects existing valid settlement
3. legacy paid note cannot be paid again
4. outstanding resolver returns already paid when fully settled
5. operational status matches settlement

### Slice 2 — Carry-Forward Settlement After Revision

Covers:

- 003
- 017
- part of 005

Goal:

- revised notes preserve existing valid money as carry-forward
- refund is not double-subtracted
- product-first allocation priority is preserved

Tests:

1. previous payment 300000, refund 100000, revised total 200000 => paid/outstanding 0
2. carry-forward 200000, revised total 250000 => partial/outstanding 50000
3. carry-forward 200000, revised total 150000 => surplus detected, not unpaid
4. carry-forward allocation fills product before service
5. existing payment is not silently lost during workspace edit

Stop before final surplus storage if owner decision is still missing.

### Slice 3 — Current-Only Payable Components

Covers:

- 012
- part of 004
- part of 008

Goal:

- payment selection excludes canceled/legacy/superseded rows
- current note payable components are active/current only
- historical rows remain audit anchors

Tests:

1. canceled row excluded from payable components
2. legacy/superseded row excluded from current payment flow
3. current active row remains payable
4. historical row remains readable for audit/history
5. note total/payment selection does not include legacy rows

### Slice 4 — Current-Only Refundable Components And Refund Eligibility

Covers:

- 013
- 014
- 021
- part of 004

Goal:

- refund endpoint validates note/row/current/payment eligibility
- forged row id cannot refund/cancel/finalize note
- open/unpaid rows cannot be money-refunded

Tests:

1. forged row id rejected
2. row from another note rejected
3. legacy/superseded row rejected for new refund
4. canceled row rejected for refund
5. unpaid row rejected for money refund
6. open/unpaid note cannot be finalized by refund endpoint
7. valid current paid row can be refunded through official flow

### Slice 5 — Server-Side Price Basis Authority

Covers:

- 006

Goal:

- client cannot bypass minimum price by controlling price basis
- server resolves authoritative price/minimum snapshot

Tests:

1. forged price basis ignored
2. below-minimum client price rejected
3. server minimum snapshot enforced
4. valid server-authorized price passes
5. report/profit source remains explainable

### Slice 6 — Revision, Inventory, And Historical Work Item Boundary

Covers:

- 004
- part of 003
- part of 017

Goal:

- revision does not destroy historical anchors
- refunded work items do not inflate current stock/report
- inventory movements remain traceable
- current projection excludes historical rows

Tests:

1. refunded old work item remains historical anchor
2. old refunded work item excluded from current stock/payment/refund selection
3. inventory reversal or movement source remains traceable
4. current projection uses active/current rows
5. report does not double-count superseded rows

### Slice 7 — Error Log 011 Bridge Access + Domain

Covers:

- 011

Goal:

- cashier revision path cannot mutate settled note state outside official audited lifecycle
- ADR-0019 access layer and ADR-0018 domain eligibility both enforced

Tests:

1. cashier route access passes only if within date window
2. cashier cannot use unofficial workspace mutation to bypass domain lifecycle
3. official audited correction/revision path remains available when domain policy allows
4. settled note mutation requires correct domain reason/audit where applicable
5. handler preserves any required row lock from concurrency patch if applicable

## Recommended Slice Order

The safest order:

1. Slice 1 settlement classification normalization
2. Slice 2 carry-forward settlement after revision
3. Slice 3 current-only payable components
4. Slice 4 current-only refundable components and refund eligibility
5. Slice 5 server-side price basis authority
6. Slice 6 revision/inventory/historical boundary
7. Slice 7 error_log 011 bridge access + domain
8. final finance blast-radius suite
9. docs/error_log updates only after proof

Reason:

- settlement must be correct before deciding payable/refundable behavior
- carry-forward money must be stable before overpaid/current-only refinements
- payment eligibility comes before refund eligibility
- price basis is isolated and can be done after core settlement
- inventory/report boundary should follow current/legacy decisions
- bridge issue should preserve both ADR-0019 and ADR-0018 rules

## Source Inventory Requirements

Before implementation, identify exact files for:

- note paid status policy
- outstanding resolver
- operational status resolver
- payment allocation readers
- payment component allocation readers
- refund readers
- note replacement/revision handlers
- note replacement payment allocation reconciler
- payable component resolver
- refundable component resolver
- selected active work item resolver
- note detail/current projection readers
- workspace edit/update handler
- refund endpoint/use case
- payment endpoint/use case
- price/minimum price validators
- product/stock price source
- inventory movement writers/readers
- reporting queries affected by current/history split
- tests already covering refund/revision/payment

## Suggested Discovery Commands

Run before implementation:

    grep -RIn "NotePaidStatusPolicy\|Outstanding\|OperationalStatus\|PaymentAllocation\|PaymentComponentAllocation\|RefundComponent\|NoteReplacement\|PayableComponent\|Refundable\|SelectedActiveWorkItems\|price_basis\|minimum\|min_price\|work_items\|inventory_movements" app tests database 2>/dev/null || true

Run route discovery:

    php artisan route:list | grep -Ei "note|payment|refund|workspace|product|price|inventory|report" || true

Run document snapshot:

    sed -n '1,260p' docs/02-architecture/adr/0018-note-revision-settlement-external-product-lifecycle.md
    sed -n '1,260p' docs/02-architecture/adr/0025-note-revision-carry-forward-settlement.md
    sed -n '1,260p' docs/03-blueprints/v2/note-finance/2026-04-29-note-finance-stabilization-blueprint.md
    sed -n '1,260p' docs/03-blueprints/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md

Run error log snapshot:

    sed -n '1,220p' docs/04-lifecycle/error-log/001-refunds-counted-as-paid-in-note-totals.md
    sed -n '1,220p' docs/04-lifecycle/error-log/003-refunded-revised-notes-are-misclassified-as-underpaid.md
    sed -n '1,220p' docs/04-lifecycle/error-log/004-refunded-work-items-survive-revisions-and-inflate-stock.md
    sed -n '1,220p' docs/04-lifecycle/error-log/005-note-revision-silently-drops-overpaid-allocations.md
    sed -n '1,220p' docs/04-lifecycle/error-log/006-client-controlled-price-basis-bypasses-minimum-price-checks.md
    sed -n '1,220p' docs/04-lifecycle/error-log/008-legacy-paid-notes-can-be-paid-again.md
    sed -n '1,220p' docs/04-lifecycle/error-log/011-cashier-revision-path-mutates-settled-note-state.md
    sed -n '1,220p' docs/04-lifecycle/error-log/012-canceled-note-rows-re-enter-payment-flows.md
    sed -n '1,220p' docs/04-lifecycle/error-log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md
    sed -n '1,220p' docs/04-lifecycle/error-log/014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md
    sed -n '1,220p' docs/04-lifecycle/error-log/017-workspace-edit-payments-ignore-existing-note-payments.md
    sed -n '1,220p' docs/04-lifecycle/error-log/021-refunds-can-be-recorded-on-open-notes.md

## Characterization Test Rules

Every implementation slice must start with characterization tests.

Rules:

1. Prefer feature/use-case tests over generic reader tests.
2. Test observable business behavior.
3. Do not encode generic reader semantics before consumer semantics are known.
4. Use exact money examples from ADR/error log when available.
5. Assert status, outstanding, mutation eligibility, and persisted rows.
6. Preserve audit/history rows.
7. Do not mark fixed if only a low-level unit test passes.
8. Do not weaken existing tests to hide failure.

## CLI Workflow

Rules:

1. Start every slice with git status.
2. Read ADR-0018, carry-forward ADR, this blueprint, and relevant error logs.
3. Read current source before writing tests.
4. Add red characterization test first.
5. Run targeted test and confirm expected failure.
6. Patch smallest safe application/domain boundary.
7. Run targeted test again.
8. Run relevant finance blast-radius tests.
9. Show diff.
10. Update error_log only with proof.
11. Commit only after owner approval.

## Required Commands For Execution Sessions

### Start Session Snapshot

    git status --short --untracked-files=all
    git rev-parse --abbrev-ref HEAD
    git rev-parse --short HEAD
    git log --oneline -5

### Blueprint Snapshot

    sed -n '1,320p' docs/03-blueprints/finance/finance-residual.md

### Test Pattern

Targeted test first:

    php artisan test --filter=TargetedTestName

Potential blast-radius suites after exact files are known:

    php artisan test tests/Feature/Payment
    php artisan test tests/Feature/Note
    php artisan test tests/Feature/Cashier
    php artisan test tests/Feature/Admin
    php artisan test tests/Feature/Reporting

Run only relevant suites for the selected slice and source changes.

### Final Diff Snapshot

    git status --short --untracked-files=all
    git diff --stat
    git diff -- docs/03-blueprints/finance/finance-residual.md
    git diff -- app routes tests docs/04-lifecycle/error-log docs/02-architecture/adr docs/03-blueprints

## Handoff Rule

If session context becomes risky or implementation is paused, create handoff with:

- active blueprint
- selected slice
- owner decisions
- open decision gates
- files changed
- tests added
- command proof
- failing tests
- residual gaps
- stop conditions triggered, if any
- safest next step
- exact opening prompt for next session

## Recommended Execution Sequence After Planning

After this blueprint is accepted, next implementation should start with:

1. local baseline proof
2. read ADR-0018
3. read carry-forward ADR
4. read this blueprint
5. read error logs 001, 003, 008, and 017 for Slice 1/2 dependency
6. source inventory for settlement readers/resolvers and revision reconciler
7. select Slice 1 settlement classification normalization
8. create characterization test
9. confirm red
10. patch application/domain settlement boundary
11. confirm green
12. run relevant payment/note blast-radius tests
13. update error_log only after proof

Do not begin with generic reader gross-back patch unless consumer semantics are proven.

---

## Related Documents

- Blueprint: docs/03-blueprints/finance/finance-residual.md
- DoD: docs/03-blueprints/finance/finance-residual-dod.md
