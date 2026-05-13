# ADR-0018 — Note Revision Settlement, External Product, and Inventory Lifecycle

- Status: Accepted for domain direction, implementation pending
- Date: 2026-05-05
- Deciders: Project Owner, Architecture Decision
- Scope: Note / Revision / Edit / Refund / Payment / External Product / Inventory / Pricing / Audit / Reporting
- Related:
  - ADR-0016 — Post-Close Note Correction and Refund Flexibility
  - ADR-0017 — Audit Log Retention, Archive Evaluation, and One-Month Load Proof
  - ADR — Note Revision Carry-Forward Settlement
  - ADR — Note Current Projection And Current-Only Refund
  - ADR-0004 — Minimum Selling Price Guard
  - ADR-0006 — Costing Strategy Default Average, FIFO-ready
  - ADR-0008 — Audit-First Sensitive Mutations
  - ADR-0009 — Reporting as Read Model
  - ADR-0011 — Money Stored as Integer Rupiah

## Context

HyperPOS supports real workshop/POS operations where a note may be edited or refunded after it already has payment, refund, stock, or reporting consequences.

Earlier decisions already locked these principles:

- closed, paid, and refunded notes are not absolute terminal mutation locks
- post-consequence note changes must use official audited, revisioned, evented, projection-safe flows
- payment, refund, inventory, and audit history must not be silently overwritten
- current UI state must come from current projection, not raw historical rows
- historical financial and inventory anchors must remain auditable
- refund money, row neutralization, inventory reversal, and outstanding recalculation are separate domain concepts

The current error-log review found that the existing edit/refund/revision model still needs a more explicit domain decision.

Problems found in the error-log cluster include:

- active refunds can be counted as paid if allocated/refunded semantics are mixed
- historical refunds can be double-subtracted after note revision
- downward revisions can silently drop overpaid allocations
- refunded or replaced work item rows can survive as active rows and inflate stock through duplicate reversals
- workspace edit/payment flows can ignore existing money
- price and stock rules can be bypassed when edit/revision is treated as UI patching instead of domain lifecycle

The owner decision is that this must not be solved with ad hoc UI tricks or generic reader patches.

Refund and edit must become serious application/domain flows with explicit settlement, external product, inventory, projection, and audit behavior.

## Decision Summary

HyperPOS adopts a dedicated note revision settlement lifecycle.

Edit and refund are not simple overwrites.

A note edit creates a new current note state while carrying forward financial and inventory history from the old state for precise adjustment.

The system must preserve old history, calculate the new active state, and explicitly represent any underpaid, paid, or overpaid result.

The implementation must be built in proper application/domain flow under `app/`, not as controller/UI-only workaround.

## Carry-Forward Settlement Policy

Previous valid money on a note becomes carry-forward settlement for the edited note.

Payment and refund history must not be deleted or rewritten.

After edit/revision, the system compares carry-forward money against the new active total.

### Revised Total Greater Than Carry-Forward Money

If the new total is greater than carry-forward money:

- the note becomes underpaid / outstanding
- outstanding is the difference
- the note must require additional payment before it can be treated as fully settled

Example:

- carry-forward money: 5000
- external product: 0
- store product active total: 12000
- service active total: 0
- result: 5000 allocated toward product, 7000 outstanding

Partial coverage is valid, but the remaining amount must stay visible as debt/outstanding.

### Revised Total Equal To Carry-Forward Money

If the new total equals carry-forward money:

- the note remains paid / lunas
- outstanding is zero
- no additional payment is required

### Revised Total Less Than Carry-Forward Money

If the new total is less than carry-forward money:

- the surplus must be represented explicitly
- the system must not pretend the note is unpaid
- the system must not silently drop the surplus
- the system must not hide the surplus from refund/reporting paths

The surplus is a dedicated kembalian / overpaid / refund-due domain concern.

Final storage and workflow details are implementation scope, but the decision is locked:

- kembalian is DB-backed domain state
- kembalian is not UI-only text
- kembalian must be auditable
- kembalian must be reportable
- kembalian must be traceable to the note revision or refund event that created it

## Settlement Allocation Priority

Carry-forward money must be allocated deterministically.

Priority order:

1. External product / produk luar / external procurement component
2. Store product / barang toko component
3. Service / jasa servis component

This priority exists because external product has the highest cashflow risk for the shop.

If money is insufficient for all components, the system allocates in priority order and keeps the remainder as outstanding.

Partial allocation is allowed when the note is still underpaid, but the unpaid remainder must remain explicit.

## External Product Policy

External product is not store inventory.

External product is a special procurement/pass-through component where the shop may buy an item from outside for the customer.

This component is operationally sensitive because the shop must not finance the customer's external purchase.

### Create Transaction Rule

If a transaction contains external product:

- the external product portion cannot be full debt
- the customer must pay the external product portion first according to the settlement priority
- the shop must not be forced to buy an external product without customer money covering that component

External product can make create-transaction behavior stricter than normal store product or service debt.

### Profit Rule

External product is not the primary profit source.

Profit is expected mainly from service.

External product should not be treated like normal store inventory margin unless a future ADR explicitly changes that.

### Inventory Boundary

External product does not enter normal store stock.

It must not be mixed into `product_inventory` as if it were store-owned inventory.

A future implementation may introduce a dedicated external product/procurement tracking model if needed, but it must remain separate from store stock source of truth.

### Refund / Cancellation Boundary

External product refund is not automatically equivalent to store product refund.

The system must distinguish at least these questions before money or state changes:

- has the customer paid the external product portion?
- has the shop already paid the outside supplier/seller?
- has the item been bought?
- has the item been received?
- has the item been delivered to the customer?
- is the external product refundable?
- does cancellation create money back to customer, reset to zero, or non-refundable state?

External product refund/cancellation is therefore a dedicated heavy scope and must not be simplified into normal stock refund.

## Edit / Revision Policy

Edit is a new current calculation, not a silent rewrite.

The old note state remains historical.

The new note state becomes current projection.

Edit must carry forward:

- previous money
- previous refund history
- previous inventory history
- previous line history
- previous audit history

The system must then compute the adjustment against the new current state.

### Metadata-Only Correction

If the user edits only note metadata such as customer name or date, and no financial, line, price, payment, refund, or inventory consequence changes:

- the operation is a correction
- it must be audited
- it does not require full financial/inventory revision
- it must not cancel/rebuild line items unnecessarily

### Financial / Line / Inventory Edit

If the edit changes line items, quantity, price, component type, external product, store product, service, payment consequence, refund consequence, or inventory consequence:

- the operation is a revision
- old affected lines must become historical/inactive with clear reason
- new current lines must be created or projected
- settlement must be recalculated
- inventory adjustment must be evented
- audit must capture before/after and reason

## Line Status and Reason Policy

Rows must not be silently overwritten or deleted when they carry financial, inventory, refund, or audit meaning.

Affected old rows should become inactive through explicit lifecycle state.

The exact enum names may be refined during implementation, but the DB and UI terms must be clear.

Recommended internal meanings:

- active: current operational row
- canceled: row canceled before or during active lifecycle
- replaced: row replaced by edit/revision
- refunded: row neutralized through refund
- corrected: metadata or value correction with audit trail

Recommended Indonesian UI terms can be decided later, but they must be understandable for operators.

Possible UI wording:

- Aktif
- Dibatalkan
- Diganti karena Edit
- Direfund
- Dikoreksi

A row removed from current projection must remain available as history if it anchors payment, refund, stock, or reporting.

## Store Inventory Policy For Edit And Refund

Store product inventory remains evented and reversal-based.

The system must not edit old inventory movements backward.

For edit/revision involving store product quantity or product change:

1. Reverse the previous store inventory effect if stock had already gone out.
2. Apply the new store inventory effect for the current active state.
3. If the new required stock is unavailable, the edit fails.
4. If the new quantity is lower than the old quantity, stock increases through reversal/adjustment event.
5. If the new quantity is higher than the old quantity, additional stock is issued if available.
6. If available stock is zero or insufficient, the operation fails.

This means edit is not “change the row and hope stock follows”.

Edit must produce traceable inventory events.

Refund can mean:

- goods return to store
- money return to customer
- both
- neither, depending on component type and lifecycle state

The domain must distinguish these outcomes.

## Price Policy For Edit

Default price for a new or edited store product line should use the latest applicable master price.

However, when editing an old transaction, manual price override is allowed down to the minimum price snapshot that was valid when the original transaction/line was created.

Decision:

- default edit price = latest/current price
- minimum allowed edit price = minimum price snapshot from the old transaction/line
- price cannot go below the old minimum snapshot without a future explicit ADR
- manual override must be audited with reason, actor, role, and timestamp

This refines the older minimum selling price guard for revision/edit scenarios.

## Current Projection Policy

Current operational UI and current note calculation must read from current projection.

Historical rows must not re-enter current operational flows unless explicitly projected as current.

Refund selection must target current active projection only.

Historical or superseded rows may be displayed as history, but they must not be eligible for new operational refund/edit as if they were active.

This follows the Hybrid C+ direction:

- current note state = current projection
- ledger state = immutable payment, refund, and inventory events
- history state = revisions, legacy rows, mutation events, and audit events

## Audit Policy

All sensitive mutation paths must be audit-first.

Revision, refund, kembalian, external product cancellation, price override, inventory reversal, and metadata correction must capture enough audit context to reconstruct:

- who changed it
- role/access context
- when it happened
- why it happened
- old state
- new state
- affected rows
- financial delta
- inventory delta
- projection impact

Audit reason must be entered at the source mutation flow, not added later from the audit log page.

## Reporting Policy

Reports must not read raw latest overwritten values blindly.

Reports must be able to distinguish:

- original note value
- current active note value
- active rows
- historical/canceled/replaced/refunded rows
- money received
- money refunded
- kembalian / refund due / overpaid state
- external product paid/unpaid/canceled state
- store stock issued
- store stock reversed
- actor, timestamp, and reason for sensitive changes

Reporting must not mix current projection semantics and immutable ledger semantics accidentally.

## Testing Policy

Implementation cannot be considered safe without strict tests.

Minimum required test groups:

### Settlement Tests

- active refund normal note reduces net paid
- historical refund after revision is not double-subtracted
- revised total greater than carry-forward money creates outstanding
- revised total equals carry-forward money remains paid
- revised total less than carry-forward money creates kembalian/refund-due/overpaid state
- carry-forward allocation follows priority: external product, store product, service

### External Product Tests

- create transaction with external product cannot leave external product fully unpaid
- external product payment is allocated before store product and service
- external product does not mutate store inventory
- external product cancellation/refund follows its own lifecycle, not store-stock refund logic

### Inventory Tests

- edit reverses old store stock issue
- edit issues new store stock requirement
- edit fails when new stock requirement exceeds available stock
- lowering quantity returns stock through event/reversal
- increasing quantity consumes additional stock only if available
- repeated edit/refund cannot duplicate reversal for the same source

### Price Tests

- edit default uses latest product price
- manual override cannot go below old minimum price snapshot
- price override is audited
- external product is not subject to store product minimum selling price guard

### Projection Tests

- historical rows do not re-enter current calculation
- current UI reads active projection
- refund selection uses current active projection only
- old rows remain visible as history/audit

### Audit Tests

- revision requires reason
- refund requires reason
- kembalian/refund-due event is audited
- external product lifecycle changes are audited
- before/after snapshot or equivalent context exists

### Concurrency Tests

- edit and payment cannot race into lost allocation
- concurrent payments cannot over-allocate the same note
- repeated refund/edit cannot duplicate inventory reversal
- transaction/locking boundary protects note settlement aggregate

## Deferred / Not Active In This ADR

This ADR does not implement code.

This ADR does not choose the final table names for:

- kembalian
- refund due
- overpaid balance
- external product procurement tracking
- current projection
- revision events

This ADR does not resolve all security findings in `docs/error_log`.

Seeder/default credential findings are deferred bootstrap hygiene and not part of the active edit/refund implementation scope.

XSS, route authorization, private storage exposure, content-type, and information leak findings must be handled as separate security issues unless they directly affect this lifecycle.

## Consequences

### Positive

- edit/refund becomes a real domain lifecycle, not UI patching
- financial settlement becomes explicit
- overpaid/kembalian no longer disappears
- external product cashflow is protected
- store stock movement remains auditable
- current UI can stay simple through projection
- reporting can reconstruct old and current state
- future error-log fixes can be classified against a clear decision

### Negative

- implementation is significantly heavier
- create transaction must be revisited for external product rules
- edit/revision flow needs dedicated design and tests
- kembalian/refund-due needs DB-backed domain model
- external product may need dedicated procurement lifecycle model
- concurrency/locking becomes mandatory implementation concern
- test matrix is large

## Implementation Direction

Safe implementation order:

1. Finish error-log analysis and classify each finding against this ADR.
2. Create a blueprint for note revision settlement lifecycle.
3. Design DB-backed kembalian/refund-due state.
4. Design external product lifecycle separate from store inventory.
5. Design current projection table/model and active-row filtering.
6. Design inventory reversal/idempotency boundary.
7. Design settlement aggregate locking/concurrency boundary.
8. Add characterization tests before production patch.
9. Implement one slice at a time through application/domain use cases.
10. Run targeted tests and audit after each slice.

Do not start with a generic payment reader patch.

Do not start by blocking cashier edit.

Do not rewrite/delete payment, refund, inventory, or audit history.

Do not solve external product by pretending it is store stock.

Do not treat kembalian as UI-only text.

## Invariants

- Money is integer rupiah.
- Payment history is immutable.
- Refund history is immutable.
- Inventory movement history is immutable.
- Audit history is append-only.
- Current state comes from projection.
- Historical state remains reconstructable.
- Edit/revision carries forward old money and stock consequences.
- Kembalian/overpaid/refund-due is explicit domain state.
- External product has highest settlement priority.
- External product is not store stock.
- External product cannot be full debt at transaction creation.
- Store stock cannot go negative.
- Store stock edit/refund uses evented reversal and new issue.
- Price edit uses latest default price and old minimum snapshot floor.
- Sensitive mutation requires reason, actor, role, timestamp, and before/after or equivalent context.
- Every implementation slice must have tests before being trusted.

## Universal Reversal / Refund / Cancellation Policy

HyperPOS does not define refund as money-back only.

In real operations, a transaction row can fail, be canceled, be revised, or be reversed in many settlement states:

- fully unpaid
- partially paid
- fully paid
- already edited
- already carrying historical payment/refund/inventory consequences

Therefore the system must support a broader reversal lifecycle.

The UI may use simple operator wording such as Refund, Batal, or Koreksi, but the domain must split the real effects explicitly.

A reversal/refund/cancellation operation may create one or more of these effects:

- money refund: money is returned to the buyer
- stock return: goods return to store inventory
- receivable cancellation: unpaid debt is canceled or neutralized
- service cancellation: service revenue/profit is neutralized or adjusted
- external procurement cancellation: external-product consequence is canceled or changed under external product rules
- projection update: current note total and current active rows are recalculated
- audit event: actor, reason, before/after, and financial/inventory effects are recorded

The system must not treat every reversal as money refund.

The system must not treat every cancellation as refund allocation.

The system must not reduce note total, receivable, stock, or profit silently.

Every effect must be DB-backed, auditable, reportable, and traceable to the note, row, revision, and actor.

### Unpaid Row Reversal

A fully unpaid row may be reversed/canceled if the real-world transaction is not continued.

This must not create a money refund because no money was received for that row.

The effect is receivable cancellation / current projection reduction / row lifecycle transition.

If the row affects store stock and no stock was issued, no stock return is created.

If stock was already issued despite unpaid state, the inventory effect must be handled explicitly through stock return/reversal rules.

### Partially Paid Row Reversal

A partially paid row may be reversed/canceled.

The system must split effects:

- paid portion may become money refund or other settlement handling
- unpaid portion becomes receivable cancellation or outstanding neutralization
- stock/service/external-product consequences must be processed according to component type

The row must not be canceled in a way that hides unpaid debt or hides received money.

### Fully Paid Row Reversal

A fully paid row may be refunded/reversed.

Depending on component type and real-world condition, the operation may create:

- money refund
- stock return
- service cancellation
- external procurement cancellation
- kembalian/refund-due/overpaid state
- current projection update

### Store Product Reversal

Store product reversal must distinguish money and goods.

If goods return to the store, inventory must receive an explicit stock return/reversal event.

If goods do not return, inventory must not be increased.

Money refund and stock return are separate effects.

### Service Reversal

Service reversal has no store-stock return by default.

It affects service revenue/profit, settlement, receivable, and current projection.

If service was already paid, money refund or refund-due may be created according to the operation result.

If service was unpaid, receivable can be canceled without money refund.

### External Product Reversal

External product follows a dedicated rule.

External product is not store inventory and is not normal store product margin.

External product represents an outside procurement/pass-through consequence for the customer.

The shop must not lose money on external product.

External product has the highest settlement priority because the shop should not finance the customer's outside purchase.

If external product is present at transaction creation, the external product portion must be paid first. It cannot be fully unpaid debt.

If an external product is canceled after payment/procurement consequence, money is not automatically returned to the buyer.

The paid external-product money may already have become procurement consequence, external item ownership, supplier payment, or non-refundable state.

External product cancellation may reset the current note/projection effect, but the financial handling must follow external procurement lifecycle rules rather than normal money refund rules.

External product revenue/profit must not be mixed with store product/service profit unless a future ADR explicitly changes that.

### Required External Product Domain

External product likely requires a dedicated DB-backed domain model.

The model must be separate from normal store inventory.

The model should be able to answer:

- has the customer paid the external product portion?
- has the shop committed to buy the external product?
- has the shop paid the outside seller/supplier?
- has the external product been received?
- has the external product been delivered to the customer?
- was the external product canceled before procurement?
- was the external product canceled after procurement?
- is money refundable, non-refundable, or converted into external product consequence?
- does the current note still include the external product?
- does reporting treat it as pass-through/procurement rather than profit?

Implementation details are pending, but the direction is locked:

- external product is flexible in edit/refund
- external product is financially strict
- external product must not create shop loss
- external product must not be implemented as normal store stock
- external product must not be implemented as UI-only text

## Row Operation Eligibility Policy

Every row operation must resolve eligibility based on row state, note state, settlement state, component type, and requested operation.

The system must not use one generic check for all row mutations.

At minimum, the application must distinguish whether a row is:

- payable
- refundable
- reversible
- cancelable
- editable
- replaceable
- stock-returnable
- external-procurement-cancelable
- visible in current projection
- visible only as history

Historical, canceled, replaced, or refunded rows must not re-enter current payment/refund/edit/inventory flows unless an explicit correction/reversal policy allows it.

Current operational flows must use current projection or an explicit eligibility service.

They must not directly treat every `note->workItems()` row as current active row.

