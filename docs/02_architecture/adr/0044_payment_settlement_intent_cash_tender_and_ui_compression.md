# ADR-0044: Payment Settlement Intent, Cash Tender, And UI Compression Contract

Status: Accepted

Date: 2026-09-13

Deciders: Project Owner, Architecture Decision

Scope: Customer payment / cash / transfer / debt / settlement / refund / revision / cashier UI / reporting

Supersedes or refines:

- the cash-credit rule introduced by `docs/04_lifecycle/handoff/0022_cashier_note_level_payment_and_finance_work_queue_handoff.md`;
- the Detail cash formula in `docs/03_blueprints/ui/0014_cashier_note_workspace_simple_detail_pos_hardening.md`;
- clarifies the two-money model already established by ADR-0042;
- keeps the modular create-payment direction in `docs/03_blueprints/finance/0009_create_transaction_domain_risk_handoff.md`.

When this ADR conflicts with the two superseded cash-credit descriptions above, this ADR wins.

## Context

GlassPos intentionally models financial behavior from first principles rather than from cashier-screen shortcuts.

The system already distinguishes:

- current obligation / receivable;
- settlement money;
- physical cash;
- payment instrument;
- refund obligation;
- actual refund cash-out;
- inventory movement;
- immutable historical events;
- current projection.

ADR-0042 explicitly says settlement money and physical cash are different meanings of money.

The create-payment domain blueprint also separates:

1. finance settlement;
2. payment instrument;
3. cash calculator;
4. revision;
5. refund.

A later cashier hardening collapsed two concepts for existing-note cash payments by treating:

```
credited payment = min(cash received, current outstanding)
```

That simplification is convenient for one UI flow, but it makes physical tender authoritative over settlement intent.

The absurd transaction gauntlet exposed the same class of defect on transaction creation:

```
amount_paid     = 300000
amount_received = 350000
```

was incorrectly interpreted as a 350000 settlement with zero change.

The create path was corrected in commit:

- `d9fa443e619267a75c12799e4b18301bb2b0df4e`
- `fix: preserve partial cash paid amount`

The same primitive contract must apply to every payment surface.

## Decision

A payment event has separate primitives.

### Obligation layer

```
outstanding_before
```

means the authoritative amount still owed immediately before the payment event.

### Settlement-intent layer

```
settlement_intent
```

means the amount of the obligation the customer is paying in this event.

For persisted successful payment events:

```
credited_payment = settlement_intent
```

subject to the authoritative outstanding boundary.

### Instrument layer

Instrument answers how settlement happens.

Current instruments:

- cash;
- transfer.

Debt / hutang and paid / lunas are NOT instruments.

They are derived settlement states.

### Physical-cash layer

Cash additionally has:

```
cash_received
change
```

Physical tender is not settlement authority.

For cash:

```
0 < settlement_intent <= outstanding_before
cash_received >= settlement_intent
credited_payment = settlement_intent
change = cash_received - settlement_intent
outstanding_after = outstanding_before - credited_payment
```

For transfer:

```
0 < settlement_intent <= outstanding_before
credited_payment = settlement_intent
cash_received = not applicable
change = not applicable
outstanding_after = outstanding_before - credited_payment
```

A transfer above outstanding is rejected.

## Canonical Example

Initial obligation:

```
outstanding_before = 100000
```

Customer wants to pay only:

```
settlement_intent = 20000
```

but physically gives:

```
cash_received = 100000
```

The correct event is:

```
credited_payment = 20000
change = 80000
outstanding_after = 80000
```

A later settlement may be:

```
outstanding_before = 80000
settlement_intent = 80000
cash_received = 100000
credited_payment = 80000
change = 20000
outstanding_after = 0
```

The payment timeline must be able to show both events exactly.

## Persistence Contract

For a successful cash payment:

```
customer_payments.amount_rupiah
  = credited_payment

customer_payment_cash_details.amount_paid_rupiah
  = credited_payment

customer_payment_cash_details.amount_received_rupiah
  = cash_received

customer_payment_cash_details.change_rupiah
  = cash_received - credited_payment
```

For transfer:

- `customer_payments.amount_rupiah` stores credited settlement;
- no cash-detail row is created.

Payment/component allocations distribute credited settlement only.

They never allocate physical tender or change.

## UI Compression Principle

The engine remains primitive and explicit.

The UI may compress those primitives into safe presets.

UI simplicity must never create a second financial engine.

### Simple - Bayar Penuh

The UI may assemble:

```
settlement_intent = authoritative outstanding
cash_received = authoritative outstanding
change = 0
```

for the common exact-cash case.

If a surface allows over-tender full cash, it still uses the same primitive engine:

```
settlement_intent = outstanding
cash_received > outstanding
change = cash_received - outstanding
```

### Simple - Bayar Sebagian

The UI may ask only for the amount actually being paid now and assemble:

```
settlement_intent = entered partial amount
cash_received = settlement_intent
change = 0
```

This is a presentation shortcut, not a different backend contract.

### Detail - Cash

Detail may expose both values independently:

```
settlement_intent
cash_received
```

This supports a customer paying Rp20.000 of debt using a Rp100.000 note while receiving Rp80.000 change.

### Transfer

Transfer exposes settlement amount only.

There is no physical-tender/change abstraction.

## First-Principles State Model

User-facing labels are projections over primitive facts.

### Hutang / belum lunas

Derived from:

```
outstanding > 0
```

Hutang is not a payment method and should not require a special ledger path.

### Lunas

Derived from:

```
outstanding = 0
```

Lunas is not a payment method and should not rewrite payment history.

### DP / bayar sebagian

A DP is simply one or more payment events whose cumulative valid settlement is still below the current obligation.

No separate financial engine is required.

### Refund

Refund decomposes into:

- eligibility / target decision;
- refund obligation or ordinary refund fact;
- actual money-out where applicable;
- component allocation;
- stock return decision where applicable;
- audit/history.

Refund does not rewrite the original payment event.

### Revision / edit

Revision changes current obligation while preserving historical payment/refund facts.

It may derive:

- new outstanding;
- settled state;
- surplus / refund due.

Revision does not transform old tender into a new payment event.

### Batal / cancellation

Cancellation is a lifecycle decision, not destructive deletion.

Its financial and inventory effects must be expressed using existing primitives:

- obligation/current-active change;
- refund/refund-due when money must be returned;
- stock reversal when physical stock returns;
- immutable history/audit.

No new "magic cancellation math" may bypass settlement/refund/inventory primitives.

## Layer Boundary

The intended architecture is:

```
presentation / shortcut
        |
        v
settlement intent
        |
        v
authoritative obligation
        |
        +--> payment instrument
        |       |
        |       +--> cash tender/change
        |
        +--> allocation
        |
        +--> payment ledger/history
        |
        v
projection / outstanding
```

Other domains remain separate:

```
revision -> changes obligation
refund   -> creates money-out / refund commitment
inventory-> moves physical goods
reporting-> reads official facts
```

Settlement must not know product names or service categories.

Inventory must not decide how much debt is paid.

Reporting must not recreate business events from current projections when immutable event records exist.

## Reporting Contract

Historical payment reporting reads credited payment from immutable payment events.

Cash-detail reporting may additionally show:

- amount paid / credited;
- amount received;
- change.

Physical tender must not inflate revenue or settlement.

For the canonical Rp20.000 / Rp100.000 example:

```
payment money-in / settlement = 20000
cash received display = 100000
change display = 80000
net physical cash retained from the event = 20000
```

Multiple payments remain separate timeline events.

A later revision or refund must not rewrite the historical amount paid, amount received, or change of an earlier event.

## Idempotency

Every payment command must keep the existing rule:

- same key + same semantic payload => replay/no duplicate effect;
- same key + changed semantic payload => reject.

The semantic payload must distinguish at least:

- note;
- settlement intent;
- instrument;
- cash received when cash;
- paid date;
- actor/context already required by the command.

Changing only physical tender is still a changed financial payload because it changes change/cash-detail history.

## Rejected Behaviors

Reject:

- deriving partial cash settlement solely from `cash_received`;
- treating `amount_paid` and `amount_received` as aliases;
- storing physical tender as credited payment when only part of the debt was intended to be paid;
- using UI suggestions or selected component IDs as settlement authority;
- creating separate Simple-mode financial behavior;
- hiding primitive ledger facts merely because a preset can calculate them automatically;
- treating hutang, DP, lunas, refund, or batal as payment instruments;
- rewriting historical payment events after revision/refund.

## Required Regression Matrix

At minimum, the next hardening slice must prove:

1. outstanding 100k, partial settlement 20k, cash received 100k -> payment 20k, change 80k, outstanding 80k;
2. same note later settles 80k with cash received 100k -> payment 80k, change 20k, outstanding 0;
3. timeline preserves both payment events and both change values;
4. Simple partial may compress received=settlement but reaches the same backend engine;
5. Simple full may compress settlement=outstanding and exact received but reaches the same backend engine;
6. Detail partial may submit settlement and tender independently;
7. transfer partial uses settlement intent and no cash detail;
8. transfer above outstanding rejects;
9. revision between payment #1 and #2 preserves payment #1 tender/change history;
10. refund between payment events does not rewrite earlier payment history;
11. component allocation distributes credited payment only;
12. reports reconcile credited payments, refunds, surplus cash-out, outstanding, and historical cash details.

These cases must later be combined with product/service/package/external-purchase scenarios in the absurd gauntlet rather than proven only in isolated happy-path tests.

## Consequences

The backend may remain more explicit than the cashier UI.

That is intentional.

A one-click fast path is allowed only as input compression into the same primitive engine.

This keeps common POS operation fast while preserving enough low-level truth for:

- repeated DP;
- debt collection;
- change on partial payments;
- revision;
- refund;
- cancellation effects;
- inventory reconciliation;
- audit;
- reporting;
- future payment instruments without rewriting settlement semantics.
