# ADR — Note Revision Surplus Disposition

## Status

Accepted by owner.

## Context

HyperPOS supports note revision as a financial correction lifecycle.

A paid note can be revised downward.

Example:

- original customer payment: 265000
- revised payable total: 143000
- surplus: 122000

Previous implementation phases already established:

- previous customer payment must be preserved
- revised payable allocation is capped to the revised payable total
- surplus is stored in note_revision_settlements
- surplus status is overpaid_pending
- overpaid_pending is not revenue
- overpaid_pending is not automatic refund paid
- overpaid_pending is not automatic customer credit
- UI is not financial truth
- ledger and history must not be rewritten to hide surplus

The remaining decision is what overpaid_pending means operationally and how it behaves when the same note is revised again before or after disposition.

## Problem

Without an explicit decision, the system can accidentally treat the same money in unsafe ways:

- silently drop surplus
- mark a revised note unpaid even though carried money exists
- treat surplus as revenue
- treat surplus as automatic customer credit
- treat surplus as automatic refund paid
- use edit/revision as a fake replacement for refund records
- use refund as a vague correction tool without preserving note history

These are not acceptable for a finance-sensitive operational system.

## Real World Model

Paper workflows have two different actions.

First action: edit or revise a note.

This is like correcting a note with pen marks, or creating a replacement note when the old note has too many corrections.

The old note is not meaningless.

It becomes history.

The new note becomes the current operational version.

Second action: refund.

This is a real business event where money, stock, service, or receivable state changes because something is returned, canceled, or compensated.

In a real note, refund should be written as refund.

It should not be hidden by creating a new note with no refund data.

Therefore edit/revision and refund are both valid functions.

They are not the same function.

## Decision

The system uses a two-step surplus model.

Step 1: Revision settlement creates holding state.

When a revision creates surplus, the surplus is recorded as overpaid_pending.

overpaid_pending means:

- customer money exists beyond the current revised payable amount
- the money is not lost
- the money is not revenue
- the money is not refund paid
- the money is not customer credit yet
- the money is pending disposition

Step 2: A later explicit disposition converts pending surplus into a final operational state.

Allowed final disposition targets:

- refund_due
- customer_credit
- split disposition between refund_due and customer_credit
- manual adjustment only with explicit high-trust capability and audit reason

refund_paid and credit_used are execution states, not automatic states created by revision itself.

## Subsequent Revision Rule

A note can be revised again while surplus is still overpaid_pending.

If the surplus has not been disposed, it remains part of available carry-forward money for the same note root.

Every new revision recalculates settlement from available carry-forward money versus revised payable total.

If revised payable total is greater than available carry-forward money:

- the note becomes underpaid
- outstanding is the difference
- operationally this is hutang or sisa tagihan

If revised payable total equals available carry-forward money:

- the note is paid
- outstanding is zero
- surplus is zero

If revised payable total is lower than available carry-forward money:

- the difference becomes overpaid_pending
- previous pending surplus is replaced by the newly calculated pending surplus for the current revision settlement
- the system must not accumulate duplicate pending surplus for the same unresolved money

## Disposed Money Rule

Money that has already been disposed is no longer silently available to later revision settlement.

If pending surplus was converted to refund_paid:

- that money has left the business
- later revision must not reclaim it without a new payment or explicit reversal policy

If pending surplus was converted to customer_credit:

- that money moved to customer balance ledger
- later revision must not silently consume it
- it can only be used through an explicit credit_used operation

If pending surplus was split:

- only the unresolved remaining amount can participate in later same-note pending recalculation
- refunded or credited portions follow their own ledger lifecycle

## Edit Versus Refund Boundary

Edit or revision is used when the note content is wrong or must be changed.

Examples:

- wrong quantity
- wrong item
- wrong service line
- correction that would be represented on paper by crossing out a line
- correction that is large enough that a replacement note is clearer

Refund is used when the business event is a return, compensation, cancellation, or money/stock/service reversal.

Examples:

- customer returns a paid item
- business returns money
- stock is returned or explicitly not returned
- service charge is refunded or canceled
- external purchase effect must be handled
- refund must be visible as refund history

Revision may create surplus.

Refund may dispose surplus.

Revision must not fake a refund.

Refund must not fake a revision.

## Rejected Behaviors

The following are rejected:

- treating overpaid_pending as revenue
- treating overpaid_pending as automatic refund paid
- treating overpaid_pending as automatic customer credit
- hiding refund by creating a new note without refund data
- replacing refund ledger with edit/revision only
- replacing edit/revision with refund only
- rewriting old payment, refund, allocation, or note history to make current state look simple
- using UI text as financial truth
- creating customer_balance_entries before the active slice has a locked table contract and tests
- allowing later revision to silently consume money already refunded or credited

## Required Future Implementation Shape

Future implementation must be additive and hexagonal.

Minimum required concepts:

- note_revision_settlements remains the revision settlement snapshot
- overpaid_pending remains the first holding state
- customer balance ledger can be introduced only after table contract is locked
- refund_due, customer_credit, refund_paid, and credit_used require explicit use cases
- every disposition must trace to source note_revision_settlement_id or equivalent source id
- every disposition must capture actor, actor role, reason, occurred time, and amount
- partial disposition must be supported if the active slice chooses split behavior
- remaining amount must never go negative
- reports must distinguish current payable, pending surplus, refund due, customer credit, refund paid, and credit used when those states are in scope

## Permission And Audit Policy

Creating overpaid_pending is part of revision settlement.

Disposing overpaid_pending is a sensitive finance action.

Default permission:

- admin only
- or explicit capability if cashier disposition is later allowed

Required audit fields:

- actor id
- actor role
- event type
- note id
- note revision id when available
- note revision settlement id or equivalent source id
- disposition type
- amount
- before state
- after state
- reason
- occurred at

A disposition without reason is invalid unless a later ADR explicitly relaxes this.

## Reporting Policy

Current transaction reporting may continue to use current note payable and capped payment allocation for current mode.

Pending surplus is not revenue.

Pending surplus is not cash collected beyond payable for profit.

When customer balance or refund due becomes in scope, reports must expose those states explicitly instead of hiding them inside note status.

Historical and revision reports must not silently read only current projection when revision mode is requested.

## UI Policy

UI can display backend-generated surplus state when in scope.

UI cannot decide final disposition.

UI cannot compute final refund amount.

UI cannot compute customer credit truth.

UI cannot convert overpaid_pending into refund_due or customer_credit without calling the backend use case.

## Consequences

Positive consequences:

- surplus does not disappear
- revision and refund remain separate domain functions
- repeated edits behave predictably
- pending money can be resolved later without corrupting current note status
- future customer balance work has a clear boundary
- future refund work can consume explicit surplus instead of guessing

Costs:

- requires future disposition use cases
- requires customer balance or disposition ledger before credit/refund_due lifecycle is complete
- requires audit and permission policy
- requires reporting updates when disposition states become visible
- requires UI only after backend truth exists

## Implementation Stop Conditions

Stop before production patch if:

- source of available carry-forward money is unclear
- customer identity is not stable enough for customer credit
- actor/capability policy is not decided for disposition
- audit source id cannot be captured
- a patch would treat pending surplus as revenue
- a patch would auto-refund without refund execution
- a patch would auto-credit without customer balance ledger
- a patch would consume already refunded or credited money
- a patch would make UI the financial truth

## Next Safe Slice

The next safe implementation slice is not UI.

Recommended next slice:

Surplus disposition foundation source audit and table contract.

Scope:

- inspect current customer identity model
- inspect existing audit capability
- design customer balance or surplus disposition ledger contract
- decide whether the first implementation supports refund_due only, customer_credit only, or split disposition
- add DB only after contract is locked
- add unit and feature tests before UI

Out of scope for the next slice:

- UI display
- API
- report export
- automatic refund paid
- automatic customer credit
- destructive migration
- ledger/history rewrite
