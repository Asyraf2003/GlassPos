# 0024 - First-Principles Finance Engine Continuation Handoff

## Status

ACTIVE NEXT SESSION.

This handoff exists because the previous Astra/Codex session stopped after token exhaustion while the absurd lifecycle gauntlet was already advancing into reporting/history work.

Canonical architecture decision:

- `docs/02_architecture/adr/0044_payment_settlement_intent_cash_tender_and_ui_compression.md`

Gauntlet handoff:

- `docs/04_lifecycle/handoff/0023_absurd_transaction_gauntlet_iterative_hardening_handoff.md`

Latest observed main commit at handoff creation:

- `98674660ab0b14717a441bd6a7db9d43b13509e6`
- message: `Update project`

## Owner Intent

GlassPos financial behavior must be built from first principles.

Human-facing workflows may be radically simplified, but backend shortcuts must compile down to the same primitive machine.

The goal is not to create special engines for:

- DP;
- hutang;
- lunas;
- refund;
- batal;
- edit/revision;
- package;
- product;
- service;
- external purchase.

Those are scenarios or projections over a smaller set of primitive facts.

## Primitive Finance Model

The minimum model is:

```
current obligation
settlement intent
payment instrument
physical cash tender (cash only)
change (cash only)
credited payment
allocation
refund commitment / refund event
actual money-out
inventory movement
immutable event history
current projection
```

Derived labels:

```
hutang / belum lunas -> outstanding > 0
lunas                 -> outstanding = 0
DP / bayar sebagian  -> valid payment event(s) with remaining outstanding
surplus               -> valid settlement money exceeds current obligation after revision
```

Cancellation is not destructive deletion and must decompose into obligation/refund/inventory/history effects.

## Full-Layer Transaction Versioning - ADR-0045

Also read and obey:

- `docs/02_architecture/adr/0045_transaction_revision_version_graph_and_full_layer_snapshot_contract.md`

Edit/revision is NOT only a note-total correction.

Every accepted edit creates the next immutable transaction revision and must preserve enough snapshot data to reconstruct the whole transaction shape at that version.

Version-sensitive layers include:

- note/header;
- product/store-stock lines;
- service detail;
- package total and decomposition;
- multi-product package lines;
- external-purchase lines;
- pricing snapshots and package metadata;
- current obligation/settlement snapshot;
- inventory consequences;
- audit/reason/actor context.

Payments, refunds, and inventory movements remain immutable event ledgers and are not copied into every revision.

The cashier edits only current truth. The system carries old payment/refund/stock consequences automatically.

Old row/component IDs become stale after replacement.

Current operations must not mutate stale pre-revision identities.

Master product/service/template changes must not rewrite historical revision snapshots.

A stale editor must not silently overwrite a newer current revision. Characterize current concurrency/stale-edit behavior before changing it; if the active request cannot prove which revision it was based on, record that as a hardening gap rather than assuming last-write-wins is acceptable.

Required adversarial edit/version combinations include:

- product qty/price replacement;
- product A -> B;
- service price/name context;
- package product composition and service split;
- external purchase amount/label;
- multiple layers changed in one revision;
- edit after DP/payment;
- edit after multiple payments;
- edit after refund;
- refund after revision using current replacement IDs;
- revision stock correction distinct from refund stock reversal;
- master-data changes after old revision;
- duplicate idempotent revision;
- stale concurrent editor;
- current-vs-historical reporting.

## Known Contract Drift To Fix First

Create transaction partial cash is already corrected:

```
amount_paid     = credited settlement
amount_received = physical cash tender
change          = received - paid
```

Existing-note cash payment currently differs.

Current implementation anchor:

- `app/Application/Note/Services/NotePaymentAmountResolver.php`

Current behavior derives credited cash amount from received tender.

That conflicts with ADR-0044.

Example that MUST become valid:

```
outstanding_before = 100000
settlement_intent = 20000
cash_received = 100000

credited_payment = 20000
change = 80000
outstanding_after = 80000
```

Later:

```
outstanding_before = 80000
settlement_intent = 80000
cash_received = 100000

credited_payment = 80000
change = 20000
outstanding_after = 0
```

Both events must remain visible in payment history/reporting.

## Mandatory First Slice

Before resuming broad gauntlet mutation, characterize the payment drift.

Add focused RED proof for existing-note/detail payment that demonstrates:

1. partial cash settlement intent 20k + tender 100k against 100k outstanding;
2. persisted payment is 20k;
3. cash detail paid=20k, received=100k, change=80k;
4. projection outstanding=80k;
5. second payment settlement 80k + tender 100k;
6. persisted second payment 80k, received=100k, change=20k;
7. final outstanding=0;
8. timeline shows both events distinctly;
9. cash/reporting uses credited payment as finance money-in and cash detail as tender/change evidence.

Then patch the smallest production seam.

Do not introduce a second endpoint or alternative Simple-mode backend.

## Existing Tests That May Be Stale

Treat tests expecting tender to overwrite settlement intent as candidates for TEST WRONG.

Known example:

- `CashierNotePaymentTimelineChainFeatureTest` currently contains requests where `amount_paid` differs from `amount_received` but historical expectation may persist the received amount as credited payment.

Do not update such tests blindly.

For each one:

1. read ADR-0044;
2. classify;
3. preserve scenario difficulty;
4. change expectation only when the old expectation contradicts the canonical contract.

## Continue The Absurd Gauntlet After Focused Payment Correction

Return to:

```
php artisan test tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php
```

Use first-failure iteration from handoff 0023.

The gauntlet must continue combining:

- supplier receipt;
- products;
- service;
- store-stock package with multiple products;
- external purchase;
- debt/outstanding;
- repeated payments;
- cash over-tender with smaller settlement intent;
- transfer;
- revision up/down;
- refund;
- blocked refund;
- stale rows;
- idempotency;
- inventory reversal;
- surplus/refund due;
- cancellation semantics supported by current domain;
- final reporting reconciliation.

## First-Principles Rules For Future A-Z Combinations

### Payment

Payment only reduces obligation by credited settlement.

Physical tender does not automatically increase credited settlement.

### Debt / DP

No special debt engine.

Outstanding is derived after each event.

Repeated payment #1/#2/#3/#4 is just an ordered sequence of immutable payment events.

### Refund

Refund never rewrites original payment.

It records money-return effects and component/stock effects according to policy.

### Revision

Revision changes obligation.

It preserves old payments/refunds and recomputes current settlement from official history.

### Batal

Do not invent destructive delete semantics.

Use the current domain's supported cancellation/refund/revision primitives.

If a desired whole-note cancellation capability does not exist, classify it as CONTRACT GAP rather than silently inventing a new state.

### Inventory

Financial settlement does not imply stock movement.

Stock movement comes only from explicit item/inventory lifecycle decisions.

### UI

Simple, Detail, mobile, desktop, shortcut buttons, and one-click actions are presentation layers.

They may hide primitive inputs or derive safe defaults.

They must not create different financial truth.

### Reporting

Reports read official event truth.

Current projection may summarize current state but must not erase historical payments, tender/change, refunds, revisions, or stock movements.

## Working Loop

For each RED:

```
FACT
CONTRACT
CLASSIFICATION
ACTION
PROOF
```

Classification must be exactly one of:

- TEST WRONG;
- PRODUCTION BUG;
- CONTRACT GAP.

If production bug:

```
gauntlet/focused RED
-> focused regression
-> smallest production fix
-> focused GREEN
-> rerun gauntlet
```

If test wrong:

```
contract proof
-> correct stale expectation
-> no production patch
-> rerun
```

If contract gap:

- do not invent semantics;
- document the missing owner/domain decision;
- continue only on independent branches that do not depend on the gap.

## Do Not

- do not make tender authoritative over partial settlement;
- do not merge payment and cash-detail concepts;
- do not introduce a special DP ledger when ordinary payment events already express DP;
- do not invent hutang as a payment method;
- do not make Simple mode bypass Detail/backend rules;
- do not make refund act as edit;
- do not make edit act as refund;
- do not make cancellation erase history;
- do not patch reports to hide broken underlying settlement;
- do not weaken the absurd gauntlet to get green;
- do not run `make verify` after every tiny fix.

## Verification Order

1. focused payment first-principles regression;
2. adjacent payment/timeline tests;
3. absurd transaction gauntlet;
4. focused regression for each newly found defect;
5. when gauntlet is green, broader Note/Payment/Reporting suites;
6. finally `make verify`.

## Session Recovery Command

Start the next session with:

```bash
git status --short
git log -5 --oneline
php artisan test tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php
```

If the first gauntlet failure is downstream of the known existing-note cash drift, stop and fix the focused payment primitive first rather than interpreting downstream numbers from a known-wrong payment contract.
