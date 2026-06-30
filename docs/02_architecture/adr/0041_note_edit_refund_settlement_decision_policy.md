# ADR-0041: Note Edit, Refund, And Settlement Decision Policy

Status: Accepted by owner for implementation planning

Date: 2026-06-30

Deciders: Project Owner, Architecture Decision

Scope: Note / Edit / Revision / Refund / Payment / Inventory / Audit / Reporting / Cashier UX

Related:

- ADR-0005 - Paid Note Correction Requires Audit
- ADR-0016 - Post-Close Note Correction and Refund Flexibility
- ADR-0018 - Note Revision Settlement, External Product, and Inventory Lifecycle
- ADR-0024 - Note Current Projection And Current-Only Refund
- ADR-0025 - Note Revision Carry-Forward Settlement
- ADR-0026 - Note Revision Surplus Disposition
- ADR-0029 - Note Revision Surplus Refund Paid Execution
- ADR-0030 - Note Revision Payment Settlement And Cashier Calculator Contract
- Error log 0051 - Manual Transaction Reporting Sequential QA Matrix
- Error log 0062 - Transaction Edit Refund Payment Stock Reporting Hardening Campaign

## Implementation Contract Note

ADR-0041 records the policy direction.

For implementation-level behavior, use:

- `docs/02_architecture/adr/0042_note_edit_refund_settlement_machine_contract.md`

ADR-0042 refines this ADR with the final machine contract for:

- state transitions;
- money formulas;
- refund shadow lines and editable draft lines;
- fully refunded note edit behavior;
- refund due versus refund paid;
- audit outbox atomicity;
- event/source-type mapping;
- reporting invariants;
- UI automation boundaries;
- idempotency.

If ADR-0041 conflicts with ADR-0042, ADR-0042 wins for note edit/refund settlement implementation.

## Context

HyperPOS must support real workshop behavior, not a simplified CRUD transaction model.

In real operations, a cashier may need to change a note after payment because:

- quantity was wrong;
- a product line must be removed or reduced;
- a service line must be compensated;
- a package contains product and service portions with different refund behavior;
- an external purchase line is passed through, kept by the customer, canceled, or refunded differently from service;
- money may need to go back to the customer;
- stock may or may not return to the shop;
- the business owner is not always on site, so cashier flow must remain usable.

At the same time, free edit is dangerous.

If paid edit can simply lower totals and rewrite current rows, refund becomes meaningless and reports can lose the truth of money-out, stock return, service compensation, and historical payment.

The policy must therefore be flexible at the cashier level but rigid at the domain and audit level.

## Owner Decision

HyperPOS adopts an audited edit-with-settlement-decision policy.

The cashier may start from edit/revision, but when the edit creates a money-out, stock-return, service-compensation, or cancellation consequence, the backend must record the proper domain event instead of hiding that consequence inside a plain edit.

The core distinction is:

- edit/revision changes the current truth of the note;
- refund records customer money-out or refund liability;
- inventory reversal records whether stock returns;
- audit records who decided what, when, why, and from which previous state.

Edit can lead into a refund or reversal decision.

Refund must still remain visible as refund history.

Edit must not erase refund history.

Refund must not be implemented as silent edit.

## Current Projection And Refunded Line Policy

A refunded line is no longer part of the editable current projection.

When a note has refunded lines:

1. The edit form shows only active/current editable lines.
2. Refunded lines do not appear as editable rows.
3. Refunded lines remain visible in note history, timeline, detail, and reporting surfaces.
4. Submitting an edit updates the current active lines only.
5. The resulting note detail must be able to show both:
   - the edited current active result;
   - the historical refunded lines.

Example:

- a note has 5 lines;
- 2 lines are refunded;
- cashier opens edit;
- only the 3 active lines are editable;
- cashier edits those 3 active lines;
- after submit, note detail shows the revised active lines plus the 2 refunded historical lines.

The refunded lines are audit/history records, not hidden current rows and not silently deleted rows.

## Fully Refunded Note Policy

If a note has no remaining active/current line because all lines have been refunded or neutralized, normal edit is not allowed.

If new work happens after a fully refunded note, the safer business path is a new note/case.

Any future exception must be a separate audited adjustment decision, not normal edit.

## Partial Refund Then Edit Policy

If only some lines are refunded, the note root may still be edited for the remaining active/current lines.

The edit must not:

- reopen refunded lines;
- move old refund allocations to new lines;
- delete refund history;
- duplicate refund stock reversal;
- make refunded lines payable/refundable again as current rows;
- let historical refunded rows inflate current totals, current stock, current payment, or current profit.

## Paid Edit Settlement Policy

Paid edit is allowed only as audited revision/settlement.

The old payment remains historical money received.

### Paid Edit Upward

If the revised total is greater than carried available money:

- old payment remains preserved;
- outstanding delta is created;
- cash ledger must not auto-increase;
- additional payment is required to settle the delta;
- stock delta must be issued only through inventory event flow.

### Paid Edit Downward

If the revised total is lower than carried available money:

- old payment remains preserved;
- the difference is not revenue;
- the difference becomes refund/kembalian decision state;
- if money is physically returned, a refund or refund-paid event must be recorded;
- cash-out must not be hidden as negative revenue;
- reports must not count returned money as profit.

This project does not adopt customer credit as the default policy for this shop.

If there is extra money, the expected operational policy is kembalian/refund to customer or explicit pending refund decision, not customer credit.

## Unpaid Edit And Refund Policy

If a note is unpaid/open:

- edit is the correction path;
- refund money is not allowed because no settled customer money needs to leave;
- line cancellation may reduce outstanding;
- stock reversal may happen if stock was already issued;
- the operation must still be audited.

Unpaid correction may be operationally similar to "cancel line", but it must not create a false customer refund money event.

## Domain-Specific Refund Behavior

Refund behavior must be component-aware.

### Store Sparepart

If a paid sparepart line is reduced, removed, or refunded:

- if the item returns to the shop, create stock reversal and money refund according to the decision;
- if the item does not return, do not recover stock;
- any money-out remains refund/kembalian/compensation, not normal expense and not hidden revenue adjustment.

### Service

Service refund is usually compensation for failed, incomplete, or unacceptable work.

Service refund:

- does not create stock movement;
- reduces/refunds service economic value according to the decision;
- must be visible as refund/compensation history;
- must not be hidden as a plain line edit when money leaves.

### Service Package With Store Sparepart

Package refund must be flexible per component.

The cashier must be able to decide:

- service portion refunded or not;
- product portion refunded or not;
- product returned to stock or not;
- product already used, installed, or consumed;
- product not yet used and fully returnable.

For this shop, product money in a package may be non-refundable when the sparepart has already entered workshop use, but can be refunded when the sparepart has not been used.

The system must not force one package-level refund rule onto every component.

### Service With External Purchase

External purchase is not normal store stock.

The system must support these cases:

- refund service only while the external item remains with the customer;
- external item money stays in shop cashflow but is treated as pass-through/flat, not service profit;
- external item canceled or refunded only when the actual business state allows it;
- no store inventory movement for external purchase.

External purchase refund/cancel decisions must ask explicit settlement questions instead of pretending the line is a normal sparepart.

## Settlement Decision Questions

When an edit or refund action touches paid money, stock, package components, or external purchase components, the UI/application must collect an explicit decision.

Minimum questions:

1. Money decision:
   - no money leaves;
   - money returned to customer now;
   - money is pending refund/kembalian decision;
   - only selected components are refunded.

2. Stock decision:
   - stock returns to shop;
   - stock does not return because already used/installed;
   - stock was given to customer;
   - stock is not applicable.

3. Component decision:
   - service portion;
   - store sparepart portion;
   - service package component;
   - external purchase/pass-through component.

4. Reason:
   - wrong input;
   - product not used;
   - product already used;
   - service failed or reached practical limit;
   - customer cancellation;
   - price/quantity correction;
   - other audited reason.

The backend, not JavaScript, owns the final financial and stock interpretation.

## Audit Policy

Every sensitive edit/refund/settlement decision must record:

- actor id;
- actor role;
- timestamp;
- reason;
- before state;
- after state;
- affected line ids;
- component type;
- money decision;
- stock decision;
- refund amount if any;
- inventory movement ids if any;
- relation to note revision, refund, settlement, or surplus record.

Audit must explain both:

1. current note truth;
2. how the note reached that truth through payment, edit, refund, stock, and settlement events.

## Reporting Policy

Reports must not collapse edit and refund into one ambiguous number.

Reporting must distinguish:

- current active note total;
- historical refunded lines;
- customer payments;
- customer refunds;
- kembalian/refund due;
- cash-out that actually happened;
- store stock issue;
- store stock reversal;
- service revenue/refund;
- external purchase pass-through value;
- operational profit;
- inventory movement.

Money returned to the customer must not be reported as profit.

Money held only as pass-through external purchase value must not inflate service profit.

Refunded historical lines must not re-enter current editable/payable/refundable projection.

## UI Policy

The cashier workflow should stay flexible and understandable.

The cashier may begin with edit, but if the edit creates a refund/reversal/settlement consequence, the UI must ask the required decision questions before submit or during submit confirmation.

The UI must not silently decide:

- refund amount;
- whether stock returns;
- whether external purchase is refundable;
- whether service compensation is cash-out;
- whether surplus is revenue.

More clicks are acceptable for this workflow because the alternative is silent financial ambiguity.

## Rejected Behaviors

The following are rejected:

- editing refunded lines as if they are still current active rows;
- hiding refunded lines from all note detail/history;
- deleting refunded lines during edit;
- rewriting old payment/refund/allocation history;
- making refund useless by allowing paid edit to silently erase money-out;
- making edit useless by forcing every correction to be a refund;
- treating unpaid note correction as customer money refund;
- treating sparepart, service, package, and external purchase refund rules as identical;
- treating customer credit as the default extra-money policy for this shop;
- counting returned money as operational profit;
- using JavaScript or UI text as financial truth;
- allowing stock reversal without traceable event source;
- allowing cash-out without refund/settlement/audit source.

## Implementation Direction

Future implementation must be test-first.

Minimum future test slices:

1. Partial refunded note edit:
   - refunded lines hidden from edit current rows;
   - refunded lines remain visible in detail/history;
   - revised active lines remain current;
   - refund history is not duplicated or deleted.

2. Paid edit downward settlement decision:
   - extra money becomes refund/kembalian decision state;
   - no automatic profit;
   - no automatic refund unless selected;
   - cash-out is recorded only when chosen.

3. Store sparepart refund decision:
   - money refund and stock return are independent decisions;
   - stock reversal happens only when item returns.

4. Service refund decision:
   - no stock movement;
   - service refund/compensation is visible in finance/reporting.

5. Package component refund decision:
   - service and sparepart components can be handled differently;
   - used sparepart may remain non-stock-returned;
   - unused sparepart can be refunded and returned.

6. External purchase refund decision:
   - service-only refund can keep external item pass-through value;
   - no store stock movement;
   - profit remains flat/traceable.

## Out Of Scope

This ADR does not authorize production code changes by itself.

Out of scope until a future test-first slice:

- migration;
- new customer credit ledger;
- source type bucket changes;
- costing engine changes;
- HPP semantic changes;
- report semantic changes without failing proof;
- UI redesign without backend settlement contract;
- changing historical data.

## Decision Summary

The accepted model is flexible for users and rigid for the ledger.

Cashier can edit active note content.

Refunded lines leave current edit projection but remain in history.

When edit creates money-out or stock-return consequences, the system must record refund, settlement, or inventory reversal events explicitly.

The final note detail must show the edited current truth plus the historical refunded truth, without rewriting or deleting either side.
