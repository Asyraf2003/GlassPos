# 0023 - Absurd Transaction Gauntlet Iterative Hardening Handoff

## Status

ACTIVE.

This handoff owns the current end-to-end adversarial lifecycle gauntlet:

- `tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php`

Do not replace this with a collection of easy isolated tests. Focused regression tests may be added around each discovered defect, but the hostile end-to-end chain must remain.

## Goal

Use one deliberately complicated business story to expose cross-domain inconsistencies that isolated tests can miss.

The chain must keep combining as many real state transitions as practical:

1. supplier receipt creates real stock and costing;
2. mixed transaction contains:
   - service + store-stock package;
   - standalone store-stock product;
   - service + external purchase;
   - service-only row;
3. initial partial cash payment with cash received greater than settlement amount;
4. open-note revision changes quantities, package composition, service fee, external cost, and total;
5. duplicate revision replay and same-key mutated-payload rejection;
6. later note-level settlement using another payment instrument;
7. stale pre-revision row refund attempt;
8. mixed valid + blocked refund selection atomicity;
9. standalone product refund and stock reversal;
10. package product-component refund;
11. blocked service-only refund;
12. blocked external-purchase refund;
13. post-close/downward correction or revision where allowed by locked contracts;
14. surplus/refund-due handling when caused by revision;
15. final inventory reconciliation;
16. final payment/refund/revision arithmetic;
17. transaction report, cash ledger, operational profit, and related reporting reconciliation.

The point is not test count. The point is forcing many domains to tell the same financial story.

## Mandatory Working Method

Use first-failure iteration.

For every RED:

1. reproduce only the smallest relevant command first;
2. inspect the assertion, current implementation, existing focused tests, and locked docs;
3. classify the failure:
   - TEST WRONG / stale expectation;
   - PRODUCTION BUG;
   - CONTRACT GAP / owner decision genuinely missing;
4. if TEST WRONG:
   - change the test to the already-locked business contract;
   - do not patch production;
5. if PRODUCTION BUG:
   - add or strengthen a focused regression test that proves the defect;
   - patch the smallest production seam;
   - rerun the focused regression;
   - rerun the absurd gauntlet;
6. continue to the next RED;
7. do not run broad `make verify` until the gauntlet is green or the current failure requires blast-radius proof.

Never weaken or delete an assertion only because implementation disagrees. First determine which side contradicts the locked contract.

## Non-negotiable Test Quality

The gauntlet must stay adversarial.

Do not make it easy by:

- reducing it to one item type;
- removing revisions;
- removing partial payment;
- removing duplicate/idempotency replay;
- removing invalid/stale selections;
- removing mixed refundable + blocked selections;
- skipping inventory reconciliation;
- skipping final finance/report reconciliation;
- mocking away the real application/database paths when a real handler/HTTP path is available;
- changing values so every branch trivially becomes fully paid or fully refundable;
- asserting implementation internals instead of business truth.

Prefer uncomfortable combinations:

- cash tendered > actual amount paid;
- old work item IDs after revision;
- same idempotency key replay;
- same idempotency key with mutated payload;
- package containing more than one product;
- selected refund set containing both valid and policy-blocked rows;
- partial refund followed by later correction;
- paid note becoming overpaid because obligation decreases;
- stock components refunded while service component stays active;
- external purchase present beside store-stock;
- debt / outstanding before later settlement;
- note-level payment even when client sends a misleading component suggestion;
- final reporting after several historical payments, refunds, revisions, and inventory movements.

## Locked Domain Contracts

### Payment

- Payment authority is note-level.
- Client row/component selection must not redefine the authoritative payable amount.
- For partial cash:
  - `amount_paid_rupiah` = amount that settles debt;
  - `amount_received_rupiah` = physical cash tendered;
  - `change_rupiah = amount_received_rupiah - amount_paid_rupiah`.
- Transfer over outstanding is rejected.
- Historical customer payment rows must remain auditable across later revisions/refunds.

### Revision

- Canonical full-layer versioning contract: `docs/02_architecture/adr/0045_transaction_revision_version_graph_and_full_layer_snapshot_contract.md`.
- Edit uses immutable revision + active replacement.
- Revision is a version of the full transaction graph, not only note total.
- Product, service, package decomposition, external purchase, pricing snapshot, and relevant header facts must remain reconstructable for every historical revision.
- Payments/refunds/inventory/audit remain immutable event truth across versions.
- Current operational rows/projection may be replacement/current-only, but they must not erase historical reconstructability.
- Master data changes must not rewrite historical version snapshots.
- A stale concurrent editor must not silently overwrite a newer revision; characterize and harden this boundary if current request/version identity is insufficient.
- Old row IDs become stale after replacement.
- Duplicate same-key/same-payload revision must replay/no-op.
- Same key with changed payload must reject.
- Revision surplus uses revision-surplus settlement/refund tables, not ordinary `customer_refunds`.

### Refund

Current Phase-5 policy is LOCKED:

- `product_only_work_item`: default refundable;
- `service_store_stock_part`: default refundable;
- `service_fee`: default blocked;
- `service_external_purchase_part`: default blocked.

Consequences:

- product-only selected row can refund/cancel and reverse stock;
- service-only selected row is blocked by default;
- external-purchase-only selected row is blocked by default;
- service + store-stock package may refund only its refundable product/store-stock components;
- selecting a package row does NOT imply service fee refund;
- package row must not be canceled merely because its product components were refunded if blocked service components remain;
- mixed request containing a fully blocked selected row must fail atomically rather than silently ignore that selected row while mutating another selected row.

Do not change this policy merely to satisfy the gauntlet.

### Inventory

- supplier receipt creates inbound truth;
- create transaction stock issue uses normal store-stock source;
- revision replacement reversal remains distinct from refund reversal;
- refund stock reversal uses the original issued cost;
- no duplicate reversal under idempotent replay;
- service-only and external-purchase refund attempts must not create store-stock movements.

### Reporting

Reports must reconcile with the same historical business facts as payment/refund/inventory tables.

Do not automatically change report expectations to whatever a current query emits. Determine whether the query is incorrectly losing historical money events first.

## Findings Already Closed By This Gauntlet

### Finding 1 - Partial cash used tendered cash as settlement amount

Original gauntlet RED:

- total = 780000;
- intended partial settlement = 300000;
- cash received = 350000;
- expected outstanding = 480000;
- actual projection showed net paid = 350000 and outstanding = 430000.

Root cause:

- `CreateTransactionWorkspaceInlinePaymentAmountResolver` used `amount_received_rupiah` as partial cash settlement amount.

This contradicted the request validator and the documented cash contract.

Fix commit:

- `d9fa443e619267a75c12799e4b18301bb2b0df4e`
- `fix: preserve partial cash paid amount`

Regression coverage was corrected/added around partial cash amount, change, allocation, and projection.

### Finding 2 - Mixed valid + blocked refund silently partially executed

Original gauntlet RED:

- selected one refundable standalone product row;
- selected one fully blocked external-purchase row in the same request;
- expected atomic rejection;
- request succeeded because the resolver silently skipped blocked components and refunded the valid row.

Fix:

- every submitted selector must contribute at least one structurally refundable component;
- package selector is still allowed when it contains refundable store-stock components even though service fee is blocked;
- a fully blocked row mixed beside a valid row causes the whole request to reject.

Fix commit:

- `b62ac6a82d04186ef8f874086324e3b967451c94`
- `fix: reject mixed refund selections with blocked rows`

Focused proof:

- `ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest`
- 3 tests / 27 assertions PASS in owner local runtime after pull.

## Current RED And Correct Interpretation

Current owner-local gauntlet failure:

```
Failed asserting that 440000 is identical to 620000.
tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php:232
```

The gauntlet had already refunded standalone product C:

- ordinary refund cumulative = 240000.

The revised package is:

- Product A: 1 x 100000 = 100000;
- Product B: 2 x 50000 = 100000;
- total refundable store-stock components = 200000;
- package total = 380000;
- remaining package service component = 180000.

Locked refund policy says only the 200000 product/store-stock portion is default refundable.

Therefore after package component refund:

- expected ordinary refund cumulative = 240000 + 200000 = 440000;
- NOT 620000.

This RED is currently classified as TEST WRONG / stale expectation.

Do not make `service_fee` default refundable to force 620000.

The next lines in the gauntlet also currently assume service-only refund succeeds. That assumption conflicts with locked policy and must be corrected before using downstream totals such as 710000 as business truth.

## Immediate Next Work

Start from the current gauntlet failure only.

1. Correct package refund expectations to component-aware policy.
2. Prove both package stock products return exactly once.
3. Prove the package service component remains non-refunded.
4. Correct the service-only refund checkpoint to expect policy rejection and zero inventory mutation.
5. Recalculate downstream ordinary-refund totals from locked policy rather than preserving the old 710000 expectation.
6. Rerun only:
   - relevant focused refund test(s);
   - `tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php`.
7. Stop at the next RED.
8. Classify it before editing production.
9. Continue iteratively until the gauntlet is green.
10. Only then run adjacent focused suites and finally `make verify`.

## Owner Intent

The owner explicitly wants difficult integrated testing, not a friendly test suite.

The desired pattern is:

```
create hostile scenario
-> RED
-> decide test-wrong vs production-bug
-> smallest correction/fix
-> focused GREEN
-> rerun same hostile scenario
-> next RED
-> repeat
```

A gauntlet reaching more assertions before failing is progress. Immediate all-green is not the objective if it was achieved by weakening the scenario.


## September 13 Continuation Update - First-Principles Payment Contract

Repository progress after this handoff was created:

- commit `98674660ab0b14717a441bd6a7db9d43b13509e6` advanced the gauntlet into historical payment/reporting reconciliation;
- package refund expectation was corrected from 620000 to 440000;
- blocked service-only/external attempts were aligned with the locked refund policy;
- focused historical-payment/reporting regressions were added;
- production reporting/payment-history readers were changed to preserve immutable historical payment amounts instead of capped current allocations.

Before continuing failure-guided gauntlet work, apply ADR-0044 as the canonical payment contract.

Important new classification:

- create transaction partial cash already follows settlement-intent vs tender separation after commit `d9fa443e619267a75c12799e4b18301bb2b0df4e`;
- existing-note/detail cash payment still contains a known contract drift in `NotePaymentAmountResolver`: current implementation derives credited cash payment from physical amount received;
- older September 5 tests/docs that expect cash tender to become credited settlement are not canonical after ADR-0044.

Next continuation must first characterize and correct that drift with focused RED tests before trusting downstream gauntlet payment combinations.

Do not discard the historical reporting work in `98674660...`; preserving immutable historical customer payments is consistent with ADR-0044.
