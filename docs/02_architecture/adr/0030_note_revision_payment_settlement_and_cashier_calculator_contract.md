# ADR 0030 - Note Revision Payment Settlement And Cashier Calculator Contract

## Status

Draft for owner review.

## Context

HyperPOS supports transaction create, edit, revision, partial payment, full payment, refund, refund_due, and refund_paid.

The cashier-facing workflow must stay simple.

The system must automatically carry old paid money, old refunds, and explicit surplus/refund states into payment math.

Cashier must not manually calculate or re-enter old money.

Current source and test inventory show:

- create workspace inline payment has direct service coverage for existing legacy allocation
- revision settlement builder already carries paid and refunded totals into net settlement
- workspace update tests currently submit inline_payment skip
- StoreNoteRevisionRequest currently forces inline_payment skip for revision submit
- request payment validator still has grand-total-oriented validation wording and target amount behavior
- cashier payment JavaScript assists modal calculation from visible rows
- refund_due and refund_paid are explicit domain records and must not be silently consumed by later revision

## Problem

Edited or revised transaction payment math can become unsafe if the payment calculator or validator uses only the current visible row total.

Old money can be missed or double-counted.

Refunds can be subtracted twice.

refund_due can be silently consumed.

refund_paid can be treated as still available money even though it already left the business.

The result is cashier confusion and unexpected outstanding after the user believes payment is correct.

## Decision

Payment after edit or revision must be settlement-preview-driven.

Backend application services must provide the payable amount and explanation.

Blade and JavaScript may display and assist, but must not decide final payable amount.

Request validators may validate input shape and obvious invalid forms, but final amount acceptance must be owned by application/domain settlement logic.

## Source Of Truth

customer_payments plus payment_allocations or payment_component_allocations represent actual money received and allocated.

customer_refunds plus refund_component_allocations represent ordinary payment/component refund.

note_revision_settlements represents revision settlement snapshot:

- gross total
- carry-forward paid
- carry-forward refunded
- net paid
- outstanding
- surplus
- settlement status

note_revision_surplus_dispositions represents explicit surplus decisions such as refund_due.

note_revision_surplus_refund_payments represents actual surplus refund_paid cash-out.

Current work items and current revision projection represent current operational rows, not full financial history truth.

Reporting reads official records and must not invent settlement math.

## Route And Use Case Boundary

Create transaction workspace may use grand total as initial payable because no old note money exists.

Update transaction workspace may keep inline payment support only if it uses backend settlement payable.

Note revision submit currently forces inline_payment to skip.

That behavior remains the safe default until a later ADR explicitly allows combined revision-plus-payment submit.

Payment after revision must use latest settlement and carry-forward state.

## Cashier UX Contract

Cashier edits normally.

Cashier sees clear backend-derived information:

- current total
- already paid
- already refunded
- carried forward to revised note
- payable now
- surplus if any
- refund_due if any
- refund_paid if any

Cashier does not manually calculate old money.

Full payment pays backend payable.

Partial payment is validated against backend payable.

Cash received must be greater than or equal to backend payable or backend accepted partial amount.

Transfer payment must use backend payable or backend accepted partial amount.

Fallback form submit must remain valid without JavaScript.

JavaScript may format, focus, open modal, and show convenience calculations only.

## Math Contract

New transaction payable equals current total.

Existing note payable equals backend settlement outstanding, not raw grand total.

For revision:

- revised total greater than carried net money creates outstanding
- revised total equal to carried net money creates settled state
- revised total lower than carried net money creates surplus

Refunded money reduces carry-forward net exactly once.

refund_due remains liability and must not be silently consumed by a later revision.

refund_paid is actual cash-out and must not be silently reclaimed by a later revision.

Downward revision must not appear unpaid only because surplus exists.

External purchase lines affect note total and case cost or profit, but do not mutate inventory.

## Backend Calculator Contract

Introduce or identify a backend application service that can build cashier payment context.

Working name:

PreviewNoteWorkspacePaymentSettlement

Required output:

- note_id
- workspace_mode
- current_total_rupiah
- old_paid_rupiah
- old_refunded_rupiah
- carry_forward_net_rupiah
- refund_due_rupiah
- surplus_refund_paid_rupiah
- remaining_refund_due_rupiah
- settlement_status
- payable_rupiah
- surplus_rupiah
- payment_allowed
- explanation_lines

Controllers may pass this data to Blade.

Future API must call the same application service.

## Rejected Behaviors

Reject raw grand-total validation as final finance guard for edited or revised notes.

Reject JavaScript-only payable calculation.

Reject cashier manual old-money entry.

Reject silent consumption of refund_due in later revision.

Reject silent reclaim of refund_paid in later revision.

Reject report query patches that hide settlement mismatch.

Reject customer_refunds for surplus refund_paid.

Reject customer_credit or customer_balance_entries in this ADR.

Reject PostgreSQL, Go API, and dashboard work in this ADR.

## Scenario Matrix

| ID | Scenario | Expected Result |
| --- | --- | --- |
| S01 | New note full payment | payable equals grand total |
| S02 | New note partial payment | outstanding equals total minus paid |
| S03 | Existing note paid 40k total 100k pay full | payable equals 60k |
| S04 | Partially paid note revised upward | payable equals revised total minus carried net |
| S05 | Partially paid note revised equal to carried net | payable zero and settled |
| S06 | Partially paid note revised downward | surplus explicit, no hidden outstanding |
| S07 | Paid note revised downward | overpaid_pending settlement |
| S08 | Paid note revised upward | outstanding delta |
| S09 | Paid then refunded note revised equal to net paid | payable zero, refund not double-subtracted |
| S10 | Paid then refunded note revised upward | payable equals revised total minus net carried |
| S11 | refund_due exists then later revision | refund_due remains liability |
| S12 | refund_paid exists then later revision | refund_paid remains cash-out |
| S13 | Cash after edit | received must be at least backend payable |
| S14 | Transfer after edit | paid amount follows backend payable or accepted partial |
| S15 | External purchase create | note total includes external purchase contract |
| S16 | External purchase edit or revision | cost/profit traceable, inventory untouched |
| S17 | JavaScript disabled fallback | backend accepts or rejects using same payable |
| S18 | Stale draft payment amount | backend rejects stale amount exceeding current payable |

## Test Plan

Minimum first tests after this ADR is accepted:

- HTTP or validator test proving edit payment validates against backend payable, not raw grand total.
- Application test for partial payment plus refund plus revision equal to net paid.
- Application test for partial payment plus refund plus upward revision.
- Application test proving refund_due is not silently consumed by later revision.
- Application test proving refund_paid is not silently reclaimed by later revision.
- Render test showing backend-derived calculator context on cashier edit page.
- Fallback submit test proving JavaScript is not financial truth.
- External purchase edit or revision test proving cost/profit traceability and no inventory mutation.

## Reporting Contract

Reporting must read official records.

Reporting must distinguish:

- customer payments
- customer_refunds
- refund_due
- surplus_refund_paid
- remaining_refund_due
- settlement carry-forward where explicitly stored
- external purchase cost where relevant

Reporting must not invent carried-forward payment math.

## Out Of Scope

Production code patch.

Migration.

customer_credit.

customer_balance_entries.

PostgreSQL.

Go API.

Dashboard.

Blind UI polish.

refund_paid mutation foundation rewrite.

## Next Safe Step

After owner accepts this ADR, add RED tests for the route/use-case boundary before production patch.

Start with validator or HTTP proof for edit payment payable.

