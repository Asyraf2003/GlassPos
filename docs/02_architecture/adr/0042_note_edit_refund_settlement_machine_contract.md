# ADR-0042: Note Edit Refund Settlement Machine Contract

Status: Accepted by owner as the final implementation contract

Date: 2026-06-30

Deciders: Project Owner, Architecture Decision

Scope: Note / Edit / Revision / Refund / Settlement / Payment / Inventory / Audit / Reporting / Cashier UI

Supersedes / Refines:

- ADR-0041 for implementation-level behavior.
- ADR-0016 where it allowed broad edit-after-refund without the shadow-line contract.
- `docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md` for transaction audit behavior when note money or stock is touched.
- `docs/03_blueprints/finance/0007_note_revision_refund_ledger_dod.md` as the concrete machine contract for note edit/refund settlement.
- `docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md` for paid/refunded edit policy and audit/idempotency requirements.

This ADR is the source of truth for future note edit/refund settlement implementation.

Older ADRs and blueprints remain useful as background and DoD references, but when they conflict with this file, this file wins for note edit/refund settlement behavior.

## Why This ADR Exists

ADR-0041 correctly chose the broad direction:

- flexible cashier operation;
- strict ledger and audit;
- edit is not refund;
- refund is not edit;
- refunded lines remain historical;
- UI may be simple but backend must be precise.

ADR-0041 was still mostly narrative policy.

This ADR turns that policy into a machine contract:

- state transition matrix;
- money formulas;
- shadow-line and editable-draft rules;
- refund due versus refund paid rules;
- audit outbox atomicity;
- event and source-type mapping;
- report invariants;
- UI automation boundary;
- idempotency and double-submit policy;
- minimum test matrix.

## Easy Business Model

Think of every note as three layers.

1. Current editable draft:
   - active lines that still represent the current note truth;
   - cashier may edit these lines.

2. Refund shadow:
   - lines already refunded or neutralized;
   - not editable;
   - still shown in detail/history/report;
   - cannot become payable/refundable again silently.

3. Ledger:
   - payments, refund due, refund paid, inventory movement, and audit events;
   - never rewritten to make the UI simple.

The detail page must be able to show:

```text
current active result + refund shadow history + ledger timeline
```

## Old Versus New Decision Comparison

| Topic | Older direction | Final direction in ADR-0042 |
|---|---|---|
| Partial refund then edit | Edit after refund allowed, but boundary was not strict enough. | Refunded lines become shadow, non-refunded lines become editable draft. |
| Fully refunded note edit | ADR-0041 said normal edit is not allowed. | Fully refunded note may open edit with zero editable old lines; all old lines are shadow; user may add new current lines. |
| Paid downward edit | Surplus/kembalian direction existed. | Difference becomes refund due / kembalian obligation first; refund paid is separate execution. |
| Audit | Audit required, but runtime model was mixed between legacy, canonical, and outbox. | Money/stock mutation must commit with durable audit outbox; full audit materialization may be async. |
| UI | UI asks decision questions. | Backend has strict steps; UI may execute safe defaults in one click, but must send explicit decisions or backend rejects. |
| Price edit | Existing ADRs mention snapshots. | Current master price is default for edit; old line snapshot minimum is the lower allowed bound unless future ADR changes it. |

## Money Model

Money has two different meanings.

1. Settlement money:
   - used to decide whether the note is paid, underpaid, or has refund/kembalian due.

2. Physical cash:
   - money actually still held by the shop after actual cash-out.

These must not be mixed.

### Formal Terms

```text
gross_payment_received
  = total valid customer money received for the note root

refund_paid_total
  = total money actually paid out to the customer

refund_due_remaining
  = total committed customer refund/kembalian not yet physically paid out

committed_refund
  = refund_paid_total + refund_due_remaining

settlement_available_money
  = gross_payment_received - committed_refund

physical_cash_net
  = gross_payment_received - refund_paid_total

current_receivable
  = max(current_active_total - settlement_available_money, 0)

refund_due_from_current_state
  = max(settlement_available_money - current_active_total, 0)
```

Interpretation:

- `settlement_available_money` answers: how much customer money is still valid for the current active note?
- `physical_cash_net` answers: how much actual cash has the shop received minus actual cash-out?
- `refund_due_remaining` answers: how much money must still be returned to the customer?
- `current_receivable` answers: how much the customer still owes after considering committed refunds?

### Example

```text
gross payment received = 100
committed refund = 30
refund paid total = 0
current active total after edit = 80

settlement available money = 100 - 30 = 70
physical cash net = 100 - 0 = 100
refund due remaining = 30
current receivable = 80 - 70 = 10
```

Business meaning:

- the shop still physically holds 100;
- 30 is customer money that must be returned;
- only 70 counts toward the current active note;
- because the revised note is 80, the customer still owes 10 after the refund obligation is respected.

This can feel unintuitive in a paper workflow because people often think in one combined correction.

Machine order matters.

If the refund/kembalian 30 was already committed first, then the later edit to 80 does not erase that refund decision. The note has 70 valid settlement money and needs 10 more from the customer.

If the business wants to preview a combined edit before committing the refund, the UI may display the net difference differently, but once the refund decision is committed the system must replay the events in order.

If the 30 is later physically returned:

```text
refund paid total = 30
refund due remaining = 0
settlement available money = 70
physical cash net = 70
current receivable = 10
```

## Refund Due And Refund Paid

The domain uses two steps.

1. `refund_due` / kembalian due:
   - customer money must be returned;
   - not profit;
   - not current active note revenue;
   - may still physically be in shop cash until paid.

2. `refund_paid`:
   - money actually leaves the shop;
   - cash ledger outflow;
   - reduces remaining refund due.

UI may make this feel like one click, but backend still records the two concepts.

Default operation for cashier UI may be:

```text
edit/refund decision -> create refund_due -> immediately execute refund_paid
```

But the data model must support:

```text
edit/refund decision -> create refund_due -> refund remains pending -> later refund_paid
```

## Data Model Direction

Use explicit financial settlement records.

Preferred naming can be refined during implementation, but the contract is:

- a table/ledger must represent refund/kembalian obligation;
- a table/ledger must represent actual refund cash-out;
- each row must reference the note root;
- each row must reference source revision/refund/line/component when applicable;
- amount must be positive integer rupiah;
- status must be string, not enum;
- rows must be append-friendly and audit-friendly;
- no destructive update may hide money history.

Existing tables that may be reused when suitable:

- `note_revision_settlements`
- `note_revision_surplus_dispositions`
- `note_revision_surplus_refund_payments`
- `customer_refunds`
- `refund_component_allocations`

However, if existing tables cannot represent both pending obligation and actual paid cash-out clearly, implementation may add a generalized financial settlement ledger.

Default table naming is allowed to be financial and generic if it keeps the domain clear.

## Line State Policy

### Definitions

Editable draft line:

- current line;
- not refunded;
- appears in edit form;
- can be changed, removed, or replaced through revision.

Refund shadow line:

- already refunded or neutralized;
- not editable;
- not payable;
- not refundable again;
- still visible in detail/history/report;
- remains linked to refund/payment/inventory/audit records.

### Partial Refund Then Edit

If a note has both refunded and non-refunded lines:

```text
refunded lines -> shadow
non-refunded lines -> editable draft
```

The edit form must show editable draft lines only.

The detail/history/report surfaces must show both current active result and refund shadow history.

### Fully Refunded Then Edit

If all old lines are refunded:

```text
all old lines -> shadow
editable old lines -> none
```

The edit form may open with no preloaded active lines.

The cashier may add new current lines.

After submit:

```text
new current lines + old refund shadow lines
```

The old refund shadow lines must not be reopened silently.

## State Transition Matrix

| Initial state | Action | Allowed | Money effect | Stock effect | Report effect | Audit/event requirement |
|---|---|---:|---|---|---|---|
| Unpaid/open | Edit active line up/down | Yes | Recalculate current total and receivable. No customer money refund. | Reverse/reissue stock only if stock line changed. | Current total/outstanding updates. | Revision/edit event with before/after. |
| Unpaid/open | Refund money attempt | No | No refund due, no refund paid. | No refund reversal. | No report change. | Rejected attempt may be logged if sensitive. |
| Paid | Edit upward | Yes | Old payment preserved; `current_receivable` increases. No automatic cash-in. | Reverse/reissue stock delta. | Outstanding appears. Profit waits for recognized revenue/cash rules. | Revision settlement event. |
| Paid | Edit downward | Yes | Create `refund_due` / kembalian due. Not revenue. Optional immediate `refund_paid`. | Reverse/reissue stock delta if line changed. | Refund due visible; cash-out only when paid. | Revision + settlement obligation event. |
| Paid | Full refund selected lines | Yes | Create refund due and normally refund paid via UI default. | Stock return if selected/default says return. | Refunded values separated from current active total. | Refund event, line shadow event, inventory event if any. |
| Partial refund | Edit remaining lines | Yes | Refund shadow money remains committed. Remaining active lines recalc. | Refunded stock reversal not duplicated. Remaining active lines may reverse/reissue. | Detail/report show current lines plus shadow refund history. | Revision event references shadow boundary. |
| Fully refunded | Open edit | Yes | Old refund remains committed/paid. New lines create new receivable/payment need. | Old refund stock reversal not duplicated. New lines issue stock if needed. | Current lines plus all old shadow refund lines. | Revision event with empty old editable draft allowed. |
| Refunded shadow line | Select for refund again | No | No duplicate refund. | No duplicate stock reversal. | No report change. | Rejection or guarded no-op. |
| Package with stock component | Refund component | Yes | Per component: service/product refund due/paid. | Store product returns only when selected/default return applies. | Package report separates service and product components. | Component-level refund event. |
| Package used stock component | Refund money but no stock return | Yes | Refund due/paid according to decision. | No stock return. | Profit/COGS remains explainable. | Refund event states no stock return. |
| Service-only | Refund/compensation | Yes if paid/eligible | Refund due/paid. | No inventory movement. | Service revenue/refund separated. | Service refund event. |
| External purchase | Service-only refund while item kept | Yes | Service refund due/paid; external pass-through remains. | No store inventory movement. | External value not service profit. | External/pass-through decision event. |
| Any sensitive mutation | Double submit | No duplicate effect | Same idempotency key replays same result or rejects changed payload. | No duplicate reversal/issue. | No duplicate report rows. | Idempotency event/guard. |

## Event And Source Type Mapping

Inventory source types that already exist remain valid:

| Effect | Inventory source_type |
|---|---|
| Store stock sale/issue | `work_item_store_stock_line` |
| Refund stock return | `work_item_store_stock_line_reversal` |
| Edit/revision stock correction | `transaction_workspace_updated` |

No inventory movement is created for:

- service-only refund;
- external purchase pass-through;
- store sparepart refund where stock does not return.

Financial/audit event names should be explicit and stable:

| Business event | Suggested event name |
|---|---|
| Note revision created | `note_revision_created` |
| Refund due created | `note_refund_due_created` |
| Refund paid executed | `note_refund_paid_recorded` |
| Selected rows moved to refund shadow | `note_refund_shadow_lines_recorded` |
| Store stock returned by refund | `note_refund_stock_return_recorded` |
| Refund money with no stock return | `note_refund_money_only_recorded` |
| Service compensation/refund | `note_service_refund_recorded` |
| External purchase pass-through decision | `note_external_purchase_settlement_recorded` |
| Package component refund decision | `note_package_component_refund_recorded` |
| Fully refunded note edited with new active lines | `note_fully_refunded_revision_created` |

Implementation may keep legacy event names only as compatibility, but canonical audit/reporting should map to the explicit meanings above.

## Audit Runtime Contract

Business validation remains synchronous.

Business writes remain transactional.

Heavy audit materialization may be asynchronous.

The required runtime shape for money/stock mutation is:

```text
begin transaction
validate business command
write money/stock/note/projection rows
write durable audit_outbox row
commit transaction

async processor later:
audit_outbox -> audit_events + audit_event_snapshots
```

Commit is allowed only if the durable audit capture is stored.

If durable audit capture fails, money/stock/note mutation must roll back.

If async audit materialization is delayed, the cashier flow may still complete because the durable audit outbox row exists.

Minimum required audit facts:

- actor id;
- actor role;
- reason;
- event name;
- note id;
- revision id when relevant;
- payment id when relevant;
- refund due id when relevant;
- refund paid id when relevant;
- affected line ids;
- component type;
- money decision;
- stock decision;
- inventory movement ids when created;
- before snapshot;
- after snapshot.

## UI Contract

Backend owns truth.

UI may optimize clicks.

The strict backend flow is:

```text
preview impact
choose money decision
choose stock decision
commit command
write settlement/refund/inventory/audit
```

The UI may execute safe defaults in one click, for example:

- store product normal refund:
  - money returned;
  - stock returned;
  - reason required.

- service refund:
  - money returned;
  - no stock movement;
  - reason required.

- external purchase service-only refund:
  - service money returned;
  - external item remains pass-through;
  - no store stock movement.

If UI omits a required decision for a mutation that touches money or stock, backend must reject safely.

## Price Snapshot Contract

Master product price changes do not rewrite historical transaction truth.

For edit:

- current master price is the default value shown to the cashier;
- user may lower the price down to the old line minimum snapshot;
- user may not go below the old minimum snapshot unless a future ADR allows it;
- every override must be audited.

Historical note line unit price, cost basis, and component allocation are immutable snapshots unless an audited revision explicitly creates a new current active line.

## Reporting Invariants

All reports must be able to reconcile to these formulas:

```text
settlement_available_money
  = gross_payment_received - committed_refund

physical_cash_net
  = gross_payment_received - refund_paid_total

refund_liability_remaining
  = committed_refund - refund_paid_total

current_receivable
  = max(current_active_total - settlement_available_money, 0)

refund_due_from_current_state
  = max(settlement_available_money - current_active_total, 0)

inventory_qty_net
  = stock_out_qty - refund_stock_return_qty - revision_stock_return_qty
```

Operational profit must not count:

- refund due as profit;
- refund paid as profit;
- external purchase pass-through as service profit;
- returned customer money as revenue.

Current reports may show current active note values.

Ledger reports must show actual payment/refund/cash/inventory events.

No report may silently mix current projection and historical ledger without naming the mode.

## Idempotency And Double Submit

Every sensitive command must be protected against duplicate effect:

- edit/revision submit;
- refund due create;
- refund paid execute;
- payment record;
- stock reversal;
- package component refund;
- external purchase settlement.

Policy:

- same idempotency key and same payload may replay the same result;
- same idempotency key and different payload must reject;
- duplicate stock reversal by same source must not create a second reversal;
- duplicate refund paid must not create a second cash-out;
- duplicate revision submit must not create two equivalent active revisions.

## Minimum Test Matrix

The first implementation campaign after this ADR must add focused tests for:

1. Partial refunded note edit:
   - refunded lines become shadow;
   - non-refunded lines preload as editable draft;
   - detail shows revised active lines plus refund shadow lines.

2. Fully refunded note edit:
   - edit form opens with zero old editable lines;
   - user can add new current lines;
   - old refunded lines remain shadow.

3. Paid edit downward:
   - creates refund due/kembalian obligation;
   - no profit inflation;
   - optional immediate refund paid is traceable.

4. Paid edit upward after refund:
   - committed refund remains respected;
   - current receivable is computed from settlement_available_money.

5. Store product refund:
   - stock return true creates refund stock reversal;
   - stock return false creates no inventory movement.

6. Service refund:
   - no inventory movement;
   - service refund visible in reports.

7. Package component refund:
   - product and service components can differ;
   - used product can have no stock return;
   - unused product can return to stock.

8. External purchase settlement:
   - service-only refund does not mutate store stock;
   - external pass-through does not inflate service profit.

9. Audit atomicity:
   - money/stock mutation rolls back if durable audit capture fails.

10. Idempotency:
   - double submit does not duplicate revision/refund/payment/stock movement.

11. Master price:
   - current master price is default on edit;
   - old snapshot minimum is allowed lower bound;
   - historical transaction truth does not change.

## Rejected Behavior

The following are rejected:

- treating refund due as profit;
- treating refund paid as negative revenue without ledger identity;
- using customer credit as default for this shop;
- editing refund shadow lines as current active lines;
- deleting refund shadow lines;
- moving old refund allocation to new active lines silently;
- duplicating refund stock reversal during later edit;
- stock return without source event;
- cash-out without refund due/refund paid identity;
- full audit materialization as a blocker when durable outbox exists;
- fire-and-forget audit with no durable outbox row;
- JavaScript deciding financial truth;
- report formulas that differ across screen, PDF, Excel, and dashboard.

## Implementation Boundary

This ADR does not itself authorize a broad production patch.

Implementation must proceed slice by slice, test first.

No costing engine/HPP semantic change is authorized by this ADR.

No source type bucket membership change is authorized without registry/test proof.

No migration is authorized until the table contract for the selected slice is written and tested.

No legacy docs should be archived only because they are older. They should first be marked as refined/superseded by this ADR where conflicts exist.
