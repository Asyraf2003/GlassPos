# Create Transaction Domain Risk Handoff

Status: Draft for owner analysis  
Scope: create transaction only  
Date: 2026-05-16

## Purpose

This document records the current create transaction flow risks before expanding the system into stronger separated domains:

- finance settlement domain
- cash/transfer payment domain
- cash received and change calculator domain
- edit/revision domain
- refund domain

The goal is not to implement edit/refund now.

The goal is to make sure create transaction is mature enough to become a safe foundation for those future domains.

## Current proof

Local proof shows create transaction baseline is green.

Migration proof:

- notes table migration ran
- work_items table migration ran
- work_item_service_details table migration ran
- work_item_external_purchase_lines table migration ran
- work_item_store_stock_lines table migration ran
- customer_payments table migration ran
- payment_component_allocations table migration ran
- inventory tables ran
- payment method and cash detail migration ran
- operational/system timestamp hardening ran
- unique work item note line number migration ran

Route proof:

- GET cashier/notes/workspace/create exists
- POST notes/workspace/store exists

Focused test proof:

- php artisan test --filter=CreateTransactionWorkspace
- Result: 9 passed, 42 assertions

Focused full cash test proof:

- php artisan test --filter=CreateTransactionWorkspaceFullCashFeatureTest
- Result: 1 passed, 5 assertions

## Important interpretation

The green tests prove the basic create transaction flow works.

They do not prove create transaction data is complete for financial audit.

Current full cash test only proves:

- workspace create can submit
- customer payment amount is persisted
- paid date is persisted
- payment component allocation is created
- legacy payment_allocations row is not used

Current full cash test does not prove:

- customer_payments.payment_method is cash
- customer_payment_cash_details row exists
- amount_paid_rupiah is persisted
- amount_received_rupiah is persisted
- change_rupiah is persisted

Therefore, create transaction cannot be called 100% mature yet.

## Locked domain direction

Create transaction must stay focused on initial transaction entry.

Future domains must remain separated:

1. Finance settlement domain

Responsible for:

- active note total
- paid total
- refunded total
- net paid
- outstanding
- overpaid/refund due
- derived payment status

2. Payment method domain

Responsible for:

- cash
- transfer
- future mixed payment
- payment method classification
- payment reporting basis

3. Cash calculator domain

Responsible for:

- amount paid
- amount received
- change
- future denomination/pecahan detail

4. Edit/revision domain

Responsible for:

- changing note obligation
- preserving historical payment/refund facts
- preserving audit trail
- triggering settlement recalculation

5. Refund domain

Responsible for:

- money-out events
- refund limits
- refund audit
- refund payment method
- future refund correction

Create transaction must not absorb all of these responsibilities.

Create transaction only needs to produce correct initial facts so those domains can work later.

## Create transaction current flow summary

Current create workspace flow is:

1. Cashier opens create workspace page.
2. UI builds note header, item rows, product lookup config, draft state, and inline payment state.
3. Form posts to notes.workspace.store.
4. Request normalizes:
   - note
   - items
   - inline_payment
5. Request validates:
   - customer name
   - transaction date
   - item entry mode
   - product/service/external purchase payload
   - inline payment decision
   - payment method
   - paid date
   - amount paid/received
6. Handler begins database transaction.
7. Handler creates note.
8. Handler persists work items.
9. Store stock lines issue inventory through inventory movement.
10. Handler updates note total.
11. Handler records inline payment if selected.
12. Payment is allocated into payment_component_allocations.
13. Handler records audit event.
14. Handler syncs note history projection.
15. Handler commits transaction.

## Supported create variants

Current create flow supports:

- product only / store stock sale
- service only
- service with store stock part
- service with external purchase
- create without payment
- full payment
- partial payment
- cash payment
- transfer payment

## Risk 1 - Cash payment method may not be persisted

Severity: P1  
Status: suspicious  
Scope: create inline payment

Problem:

Current create cash test does not assert that customer_payments.payment_method is cash.

Current create transfer test also does not assert that customer_payments.payment_method is transfer.

Why this matters:

If payment method is not persisted correctly, then:

- cash vs transfer reporting can be wrong
- payment can fall back to unknown
- daily cash/bank separation can be unreliable
- future cash reconciliation will be built on weak data

Minimum proof needed:

Add assertion to full cash create test:

- customer_payments.payment_method = cash

Add assertion to full transfer create test:

- customer_payments.payment_method = transfer

Expected result:

If test fails, patch create inline payment recorder to pass payment method into CustomerPayment creation.

## Risk 2 - Cash received and change may not be persisted

Severity: P1  
Status: suspicious / primary create risk  
Scope: create inline cash payment

Problem:

The UI sends amount_received_rupiah.

The flow can calculate change_rupiah.

The database has customer_payment_cash_details.

The writer adapter can write cash detail.

But current full cash test does not prove the cash detail row is actually created.

Why this matters:

If cash detail is not persisted, then:

- amount received from customer is lost
- change/kembalian is lost
- cash drawer audit is incomplete
- future denomination/pecahan feature has no safe foundation
- payment amount may be correct while cash handling proof is missing

Minimum proof needed:

Update full cash create test to assert:

- customer_payment_cash_details row exists
- amount_paid_rupiah = 150000
- amount_received_rupiah = 200000
- change_rupiah = 50000

Expected result:

If test fails, patch create inline payment recorder to create CustomerPaymentCashDetail and pass it into CustomerPaymentWriterPort.

## Risk 3 - Full cash insufficient received amount is guarded late

Severity: P2  
Status: validation seam  
Scope: request validation versus domain validation

Problem:

For full cash payment, request validation may not reject amount_received_rupiah lower than the full payable amount at field-validation level.

The domain/application resolver still guards the invariant.

Why this matters:

This is not the primary data corruption risk.

But cashier UX can be less precise because the error may appear as a workspace failure instead of a direct amount_received field error.

Decision:

Do not patch first.

Fix P1 cash method and cash detail persistence first.

## Risk 4 - Mixed cash and transfer is not supported in create flow

Severity: P2  
Status: known limitation  
Scope: future payment domain

Problem:

Current create inline payment accepts one method only:

- cash
- transfer

It does not support one create transaction paid by both cash and transfer.

Why this matters:

Real-world transactions can use mixed payment.

Decision:

Do not implement now.

Keep this as future Payment Method Domain work after single-method create is hardened.

## Risk 5 - External purchase is only a cost line, not lifecycle

Severity: P1 for future edit/refund  
Status: known domain gap  
Scope: future external purchase/edit/refund integration

Problem:

Current create flow supports service with external purchase line.

But it does not model the lifecycle of the external purchase.

Future lifecycle states may include:

- not yet bought
- already bought
- returned to external shop
- given to customer
- moved into store stock
- written off as shop loss
- partially charged to customer

Why this matters:

This is acceptable for create v1.

But edit/refund must not be implemented for external purchase until lifecycle decisions are explicit.

Decision:

Do not patch now.

Document external purchase lifecycle before edit/refund implementation.

## Risk 6 - Store stock price snapshot still needs proof

Severity: P1 for future edit/reprice  
Status: needs schema/writer proof  
Scope: store stock line pricing

Problem:

Create mapping computes line total from qty and unit price.

Future edit/reprice needs proof that the persisted line keeps enough price snapshot data.

Minimum required data for mature future edit:

- product id
- qty
- unit price at transaction time
- line total
- price basis/source if relevant

Why this matters:

If only line total is persisted, future edit after catalog price changes can become hard to audit.

Decision:

Do not patch now.

Run a focused schema/writer inspection later.

## Risk 7 - Store stock create immediately issues inventory movement

Severity: P0 for future edit/refund  
Status: foundation decision  
Scope: inventory movement

Problem:

Create transaction with store stock immediately issues stock out.

This is good for real-time inventory.

But it means future edit/refund must never silently overwrite or delete stock data.

Required future rule:

- stock correction must use inventory reversal/correction movement
- old movement must remain auditable
- edit/refund must not directly mutate historical stock movement

Decision:

Keep movement-ledger model.

Do not implement edit/refund before stock reversal rules are locked.

## Current create-only conclusion

Create transaction is usable as a foundation.

But it is not 100% mature for finance-grade audit.

The current high-risk gap is not basic note creation.

The current high-risk gap is cash/payment audit fidelity:

- payment method persistence
- cash received persistence
- change persistence
- test coverage for cash detail

## Next active step

Add RED assertions to:

tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php

Assertions to add:

- customer_payments.payment_method = cash
- customer_payment_cash_details.customer_payment_id = paymentId
- customer_payment_cash_details.amount_paid_rupiah = 150000
- customer_payment_cash_details.amount_received_rupiah = 200000
- customer_payment_cash_details.change_rupiah = 50000

Run:

php artisan test --filter=CreateTransactionWorkspaceFullCashFeatureTest

Expected interpretation:

- If RED: patch create inline payment recorder to persist payment method and cash detail.
- If GREEN: cash detail exists through another path; continue to transfer method and partial cash assertions.

## Strict non-goals

Do not touch in this slice:

- edit/revision
- refund
- post-refund correction
- write-off
- external purchase lifecycle
- mixed cash + transfer
- denomination/pecahan
- PostgreSQL migration
- Go API
- reporting rewrite
- git push/remote sync

## Final note

Do not treat green create tests as full domain maturity.

Treat them as baseline safety only.

The first thing to harden is the cash audit seam.
