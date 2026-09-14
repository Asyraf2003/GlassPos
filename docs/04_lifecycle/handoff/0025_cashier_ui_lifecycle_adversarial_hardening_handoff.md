# 0025 - Cashier UI Lifecycle Adversarial Hardening Handoff

## Status

ACTIVE NEXT SESSION.

This handoff converts owner manual-production QA into a systematic UI/lifecycle hardening campaign.

Do not treat the observed examples as a closed bug list.

The required workflow is:

```text
owner seed
-> identify violated invariant
-> find every sibling UI surface using the same invariant
-> derive adversarial variants
-> characterize first RED
-> classify TEST WRONG / PRODUCTION BUG / CONTRACT GAP
-> patch the smallest shared seam
-> focused proof
-> rerun the expanded matrix
```

Three or four manual examples should normally expand into at least eight to twelve meaningful regression scenarios when they share UI state, lifecycle, or finance primitives.

## Canonical Contracts

Read first:

1. `docs/02_architecture/adr/0044_payment_settlement_intent_cash_tender_and_ui_compression.md`
2. `docs/02_architecture/adr/0045_transaction_revision_version_graph_and_full_layer_snapshot_contract.md`
3. `docs/04_lifecycle/error_log/0062_transaction_edit_refund_payment_stock_reporting_hardening_campaign.md`
4. `docs/04_lifecycle/handoff/0024_first_principles_finance_engine_continuation_handoff.md`
5. `docs/04_lifecycle/handoff/0023_absurd_transaction_gauntlet_iterative_hardening_handoff.md`

ADR-0044 and ADR-0045 remain canonical.

Do not create separate business semantics in JavaScript.

UI is an adapter over the same backend settlement/version/refund engine.

## Latest Owner Manual QA - 2026-09-14

These are seed observations, not all pre-classified as bugs.

### Seed A - Create partial cash rewrites settlement intent

Observed:

```text
note total        780000
owner intended    280000 partial settlement
cash tender       300000
```

Expected under ADR-0044:

```text
credited payment  280000
cash received     300000
change             20000
remaining         500000
```

Observed UI/history instead behaved as if credited payment became 300000, with change 0 and remaining 480000.

Source inspection confirms stale UI semantics in:

- `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`
- `public/assets/static/js/pages/cashier-note-payment.js`

Both contain logic that derives credited settlement from tender/selected outstanding instead of preserving the existing settlement intent.

This is a confirmed UI-adapter contract drift. Backend settlement primitives must not be weakened to accommodate it.

### Seed B - Cash calculator labels and values follow outstanding instead of partial intent

Observed partial cash modal shows full outstanding as "Tagihan" and computes change/remaining against full outstanding.

For partial settlement, cash view must present the selected settlement/payable amount as the cash target and keep the total outstanding separately understandable.

Do not let presentation overwrite hidden finance inputs.

### Seed C - Service + external purchase service JavaScript inactive

Template:

- `resources/views/cashier/notes/workspace/partials/templates/service-external.blade.php`

Service catalog JavaScript:

- `public/assets/static/js/pages/cashier-note-workspace/service-catalog.js`

Source inspection found the template exposes `data-service-name` but the catalog binder expects a `data-service-search` input and returns early when none exists.

Characterize create and edit behavior before patching. Fix the shared UI contract, not a one-off click handler.

### Seed D - Edit action produced access denied

Owner clicked edit from a note lifecycle and received the generic access-denied page.

Relevant boundaries:

- `EnsureCashierNoteAccess`
- `CashierNoteRouteAccessData`
- `CashierNoteAccessGuard`
- `EditableWorkspaceNoteGuard`
- `NoteDetailNotePayloadBuilder.can_edit_workspace`

Do not assume this is a bug until the exact note state/date/refund state is reproduced.

Potential mismatch already exists conceptually:

```text
detail payload may expose can_edit_workspace for open/closed non-refunded note
cashier application mutation guard may reject close-derived edit
```

Characterize route visibility, page access, and submit separately.

### Seed E - Qty 3 product refund only offers full qty 3

Current refund request accepts `selected_row_ids`, not refund quantity.

Current domain therefore supports selected row/component refund, not necessarily "refund 1 of qty 3".

Classify partial-quantity refund as CONTRACT GAP unless an existing canonical capability is discovered.

Do not add a qty input without a backend money, stock, version, idempotency, and history contract.

### Seed F - Payment actions appeared after refund and owner could submit another payment

This is high risk.

Existing historical issue `0043_service_package_component_refund_pay_again_inventory_cash_mismatch.md` proved one class of pay-again bug and introduced allocator protection for refunded inventory-backed package components.

The owner now observed a post-refund UI path where "Bayar Sebagian/Lunasi" remained available and a payment appeared to succeed.

Do not infer whether this was valid remaining obligation or invalid repayment.

Reproduce with assertions on:

- active current components;
- refunded component identities;
- note total/current obligation;
- payment_component_allocations before/after;
- customer_payments before/after;
- inventory movements before/after;
- note_history_projection;
- action flags rendered to UI.

If payment is valid for an unrefunded active component, preserve it.
If payment reopens/re-pays refunded inventory-reversed components, treat as PRODUCTION BUG.
If backend blocks but UI still advertises the action, treat as UI projection/gating bug.

### Seed G - Full payment with total 340 and cash tender 400 failed

Under ADR-0044, full cash with tender above settlement should be valid:

```text
settlement = 340
received   = 400
change     = 60
```

Characterize the exact request/error. Do not assume payment is root cause; stock, selected product, request validation, or UI hidden-state corruption may be responsible.

## Required Bug Expansion

Do not fix only the seven seeds above.

For every confirmed shared invariant, search sibling flows and derive regressions.

Minimum target for this campaign: 12 adversarial UI/lifecycle scenarios.

### Payment UI parity

1. Create Simple partial cash:
   settlement 280, tender 300 -> credited 280, change 20, remaining correct.

2. Create Detail partial cash:
   same primitive and same persisted result.

3. Existing-note/detail partial cash:
   settlement remains typed intent when entering cash tender.

4. Full cash over-tender:
   settlement equals full payable, tender may exceed it, change correct.

5. Transfer partial/full:
   no cash received/change fields influence settlement.

6. Modal back/reopen/re-entry:
   switching partial -> cash -> back -> cash must not replace settlement intent with tender or stale hidden values.

### Transaction row UI parity

7. Service + external purchase:
   service lookup/manual create, service price, external label/amount, total, submit and reload/edit hydration remain functional.

8. Product/service/package/external mixed create:
   one row's JavaScript must not disable or overwrite another row's hidden identity/pricing state.

### Refund lifecycle

9. Whole product row refund:
   correct refund amount, exact stock reversal, row/current projection/action flags consistent after refresh.

10. Package component refund:
    refunded component must not silently become payable again; valid remaining service/other components may remain payable.

11. Post-refund payment action gating:
    rendered buttons must correspond to backend payable components, not merely note-level arithmetic.

12. Refund then edit/revision:
    edit availability, current replacement IDs, stale old IDs, refund shadow, stock correction vs refund reversal, and payment actions must remain coherent.

## Additional Expansion When First 12 Are Green

Continue only if first 12 expose sibling risks:

- duplicate submit/double click in UI with same idempotency key;
- browser back/forward or stale draft hydration;
- edit after partial payment;
- edit after multiple payments;
- edit after refund;
- close -> edit visibility vs submit authority;
- admin vs cashier parity where policy intentionally differs;
- master product/service/template change after historical revision;
- stale concurrent editor ADR-0045;
- cancellation/batal presentation if a supported capability exists.

## UI Invariants

### Settlement UI

For cash:

```text
0 < settlement_intent <= outstanding_before
cash_received >= settlement_intent
credited_payment = settlement_intent
change = cash_received - settlement_intent
outstanding_after = outstanding_before - settlement_intent
```

JavaScript may format or prefill these values but must not redefine them.

### UI Compression

Simple mode may auto-fill:

```text
partial exact cash:
settlement = x
received   = x

full exact cash:
settlement = outstanding
received   = outstanding
```

Detail mode may expose settlement and tender separately.

Both modes must submit to the same backend semantics.

### Action Visibility

A visible action is a claim that the backend capability is currently meaningful.

Do not show:

- payment action when no allocatable current component exists;
- edit action when current route/application contract will necessarily reject it;
- refund action for stale/non-refundable selectors;
- "cancel" semantics that do not exist in the domain.

Conversely, do not hide a valid backend action merely to avoid a UI bug.

### Refund Quantity

Do not invent partial quantity refund.

If required by owner, first define:

```text
original qty
refund qty
historical unit sale basis
refund amount
inventory reversal qty
remaining active qty/version
component identity
idempotency
reporting effect
```

Until then classify as CONTRACT GAP.

### Versioning UI

Cashier edits current truth only.

UI must not ask cashier to manually reconcile:

- previous payments;
- refunds;
- stock reversals;
- allocation carry-forward;
- revision IDs except conflict/reload communication.

Stale pre-revision IDs must not mutate current state.

## Failure Loop

For each first RED:

1. FACT - exact browser/request/database evidence.
2. CONTRACT - ADR/blueprint/current capability.
3. CLASSIFICATION:
   - TEST WRONG
   - PRODUCTION BUG
   - CONTRACT GAP
4. ACTION - smallest correction.
5. PROOF - focused test, then expanded matrix.

Do not update expected values to current output without proving the contract.

Do not patch reports to hide invalid write-side state.

Do not broad-refactor UI before a focused failing characterization exists.

## Test Strategy

Prefer layered proof:

```text
JS/static contract test
-> focused HTTP/Feature lifecycle test
-> browser interaction test where state/hidden fields matter
-> Note/Payment adjacent suites
-> absurd transaction gauntlet
-> make verify only after focused matrix is green
```

Where possible, regression tests must assert both UI payload and persisted backend facts.

A UI test that only checks visible text is insufficient for finance-sensitive flows.

A backend test that never exercises the browser adapter is insufficient for the current bugs.

## First Mandatory Slice

Start with the confirmed shared payment UI drift before ADR-0045 stale-editor work.

Characterize and fix both:

- create workspace payment UI;
- existing note/detail payment UI.

Mandatory proof:

```text
outstanding 780000
settlement intent 280000
cash received 300000

hidden/submitted settlement = 280000
submitted tender            = 300000
persisted customer payment  = 280000
cash detail paid            = 280000
cash detail received        = 300000
change                      = 20000
projection outstanding      = 500000
timeline                    = 280000 / received 300000 / change 20000
```

Then prove:

```text
full payable 480000
cash received 500000
credited 480000
change 20000
outstanding 0
```

Do not change backend settlement semantics to make stale JavaScript pass.

## Second Mandatory Slice

Characterize service_external UI contract:

- service lookup works;
- manual service create/selection where supported;
- service price raw/display stay synchronized;
- external label/amount stay independent;
- total is service + external;
- submitted request creates service_with_external_purchase;
- no inventory movement;
- edit hydration preserves the same fields.

## Third Mandatory Slice

Reproduce owner refund/edit/payment chain from UI to persistence.

Use at least:

```text
create product qty 3
-> pay full
-> attempt edit
-> refund selected product row
-> refresh detail
-> inspect action flags
-> attempt partial/full payment
-> inspect payment/refund/allocation/inventory/projection history
```

Do not add partial-quantity refund in this slice. Record it as CONTRACT GAP.

The goal is to prove whether post-refund pay-again is valid remaining obligation, stale UI gating, or a write-side regression.

## Session Exit Requirement

Before token/context exhaustion update this handoff with:

- exact first REDs encountered;
- classification for each;
- last GREEN command;
- files changed;
- production bugs fixed;
- contract gaps intentionally left open;
- exact next command;
- unresolved UI sibling risks.

Do not leave the session with only a commit message.

## 2026-09-14 continuation — local recovery state

Started from clean `d29d9f1d` main. No stale-editor work or financial backend
rewrite was undertaken. During execution the checkout advanced to `a0d67bd8`,
containing the production fixes and initial regression files. Later matrix/test
and handoff changes remain in the working tree; do not reset either set.

### First REDs and classification

1. **PRODUCTION BUG**: production workspace JS in Chromium submitted settlement
   300000 for intent 280000/tender 300000/obligation 780000. Corrected cash
   refresh/click payload and calculator to preserve intent; tender below intent
   disables cash submit.
2. **PRODUCTION BUG**: existing-note JS reproduced the same 300000-vs-280000 RED.
   Corrected refresh, click and submit seams; calculator targets settlement.
3. **PRODUCTION BUG**: service_external template/binder browser lookup never
   invoked. Template now follows the existing service-only picker contract:
   separate hidden identity/search input, selected display and change action.
4. **PRODUCTION BUG**: external part was stored correctly but absent from detail
   reload. Current revision row mapper only built store-stock subtitles. It now
   derives external subtitle from immutable revision payload cost_description/qty.
5. **PRODUCTION BUG**: fully paid qty3 note exposed cashier can_edit_workspace=true
   while actual PATCH returned 403. Cashier detail now asks the existing date,
   state and operational editability guards for that capability. Admin policy
   is unchanged. Direct closed edit GET remains available under existing route
   policy; this change does not claim that GET route was forbidden.
6. **PRODUCTION BUG**: stale Detail partial value 50000 overwrote a later Simple
   partial intent 280000 during refresh. Simple now synchronizes its intent into
   the display/hidden pair before refreshing.
7. **TEST WRONG**: old CashierClosedNoteRefundViewFeatureTest expected Edit for a
   closed cashier note. Updated to assert no edit link; open partial edit remains.
8. **TEST WRONG**: NoteDetailEditEntryFeatureTest expected edit for raw open state
   with full 50000 settlement. Operational close blocks its write-side edit;
   assert no edit link while preserving unpaid sibling (2 / 6 assertions GREEN).

New test fixture errors corrected separately: required create idempotency key,
full payment_scope must be null (only partial is accepted), and external DB label
column is cost_description. These were not production defects.

### Regression matrix and proof boundary

- `tests/Browser/cashier-payment-intent.html` executes production scripts in real
  headless Chromium, dispatches input/click/submit and captures FormData.
- `scripts/test-cashier-payment-intent.mjs` runs that browser matrix and emits JSON
  for HTTP regression consumption. Requires Node and Chromium (CHROMIUM_BIN may
  override binary); lookup fixture stubs responses, not production binder logic.
- `CashierPaymentBrowserPayloadFeatureTest`: 12 scenarios / 120 assertions GREEN
  before adding the 13th stale Detail-to-Simple case. The final adjacent run
  includes all 13 cases GREEN. Covers workspace/detail,
  back/reopen, transfer, Simple exact cash, 480000/500000 full cash and 340/400.
  Every case feeds browser-derived values into actual HTTP handlers and checks
  payment, allocation, cash detail, outstanding and timeline. The 13th browser
  case independently passed and is included in the combined GREEN below.
- `ServiceExternalBrowserLifecycleFeatureTest`: 1 / 12 assertions GREEN. Browser
  reads actual Blade templates plus rows/catalog/money/summary scripts; proves
  lookup, raw/display price, external fields, hydration, manual service creation,
  and sibling independence. Extended browser independently passed product +
  multi-product package + external + service coexistence and manual external
  replacement. HTTP proves create, detail/edit reload, no payment/inventory.
- `CashierProductRefundActionLifecycleFeatureTest`: 2 / 54 assertions GREEN.
  Product qty3 and package qty3 with surviving service: full payment, edit GET,
  rejected cashier edit PATCH, refund/replay, refresh flags, partial/full pay-again
  rejection, unchanged six ledgers/projection, original cost stock restored once.
- ClosedNoteRefundView + ClosedNoteRevisionPolicy: 4 / 32 assertions GREEN;
  open cashier edit and authorized admin closed revision remain valid.

The owner 340/400 primitive is valid in both browser and persisted backend proof.
The specific manual failure cause is not established from that nominal alone.
For the reproduced whole-product/package fully-paid refund cases, no post-refund
write regression was found: payment rejects and flags are false. Do not generalize
this to every possible remaining-obligation/revision combination.

### Files changed

Production:

- public/assets/static/js/pages/cashier-note-workspace/payment-flow.js
- public/assets/static/js/pages/cashier-note-payment.js
- resources/views/cashier/notes/workspace/partials/templates/service-external.blade.php
- app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailBaseRowMapper.php
- app/Application/Note/Services/CashierNoteDetailPageAccessData.php
- app/Adapters/In/Http/Controllers/Cashier/Note/NoteDetailPageController.php

Regression files: browser fixtures/runner and three Feature tests listed above,
plus tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php,
tests/Feature/Note/NoteDetailEditEntryFeatureTest.php and this handoff.

### Gaps and next command

**CONTRACT GAP**: refund qty1 of qty3 remains unimplemented: no quantity refund
request/financial-stock-version-idempotency contract is defined. No whole-note
canceled state or deletion semantics were invented.

Unresolved sibling risks: complete authenticated-page browser navigation (fixtures
exercise real production scripts/templates but HTTP submission is bridged through
Feature tests); post-refund payment with a newly revised legitimate obligation;
stale concurrent editor/master-data mutation remain later ADR-0045 work.

Last adjacent GREEN: **659 tests, 5418 assertions, 56.24s**, exit 0.
Exact command from repository root:

```bash
php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note tests/Feature/Payment tests/Feature/Reporting --stop-on-failure --compact
```

Then rerun AbsurdTransactionGauntletFeatureTest explicitly. Run make verify only
once these matrices are GREEN. Sandbox blocks Chromium sockets/MySQL; authorized
external execution is required for browser/database proofs, not code changes.


## 2026-09-14 continuation — refund -> revision -> legitimate new obligation characterization

### Scope lock

This continuation touches one target only:

```text
paid mixed transaction
-> refundable inventory-backed component refund
-> accepted revision after refund
-> legitimate new current obligation
-> partial payment
-> final settlement
```

The characterization explicitly distinguishes historical refunded/stale component
identity from the replacement/current payable component identity.

### Contract proof

ADR-0045 is explicit:

- payment and refund events remain immutable historical ledgers;
- refunded/shadow components do not become current merely because a revision exists;
- revision after refund preserves the refund boundary;
- stale pre-revision component IDs must not mutate current state;
- a new revision may create a new active obligation, with outstanding recalculated
  from official payment/refund history.

ADR-0044 continues to govern later cash payments: credited settlement is the
settlement intent, not physical tender.

This target is therefore defined by existing ADRs and is not a CONTRACT GAP.

### Focused characterization added

Branch:

```text
ai/refund-revision-new-obligation-characterization
```

Test commit:

```text
10d7a9276160bf4340c4a8553906f7e6bb761580
test: characterize refund revision new obligation payment
```

Changed test:

- `tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php`

New scenario:

- creates the existing mixed service + inventory-backed product transaction;
- pays the original 250000 obligation;
- refunds the old 200000 inventory-backed component;
- proves the old payment/refund and one refund stock reversal remain immutable;
- accepts the existing post-refund revision that creates a new 150000 current
  transaction shape with 50000 valid carried settlement and 100000 outstanding;
- proves the stale refunded stock-line/work-item IDs receive no rebuilt allocation;
- proves the original surviving 50000 settlement is replayed only onto the new
  current component;
- attempts a new 40000 partial cash payment against current obligation;
- attempts the remaining 60000 settlement;
- requires the final payment to allocate 10000 to the current replacement product
  component and 50000 to the current service component;
- requires zero payment allocation to the stale refunded component/work-item;
- requires exactly one old stock-out, one old refund reversal, one deliberate new
  replacement stock-out, and no payment-triggered inventory movement;
- requires the original payment timeline and refund mutation history to remain;
- requires final `note_history_projection` 150000/150000/200000/150000/0;
- requires transaction report, cash ledger, and operational profit to reconcile.

### First RED / classification

No runtime RED has been observed in this continuation because this execution
environment does not contain a runnable GlassPos checkout/database and the
repository has no dispatchable GitHub Actions workflow.

Do not classify the target from source inspection alone.

Current classification:

```text
PENDING RUNTIME PROOF
```

If the focused test rejects the new payment while the 100000 current obligation
is valid under ADR-0045, classify the exact failing seam from runtime proof.

If any new payment allocation points to the old refunded component/work-item ID,
classify it as PRODUCTION BUG.

If the test passes as written, no production patch is justified for this target;
the required hardening is the regression proof itself.

### Static source characterization, not runtime proof

Relevant shared seams inspected:

- `NoteReplacementPaymentAllocationReconciler` captures gross allocations minus
  refunds, deletes current allocations, then replays only surviving settlement
  against components resolved from the active replacement note;
- `RecordAndAllocateNotePaymentOperation` resolves the authoritative current note,
  calculates net allocated amount from official allocations/refunds, and allocates
  new payment only across current components;
- `AllocatePaymentAcrossComponents` keys allocation state by component type plus
  component ref ID and guards refunded/reversed stock component identities;
- current priority is inventory-backed component before service fee.

For the focused fixture, source behavior predicts:

```text
original payment             250000
old product refund           200000
surviving settlement          50000
new revision total           150000
new outstanding              100000

partial new payment           40000
remaining                     60000

final new payment             60000
final outstanding                 0
```

This prediction is intentionally not labeled GREEN until runtime execution.

### Last GREEN

Owner-provided current-main proof at session entry remains authoritative:

```text
make verify
1700 passed
11131 assertions
PHPStan PASS
line audit PASS
Blade audit PASS
contract audit PASS
```

No later GREEN is claimed.

### Exact next command

Run from a checkout of the characterization branch:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  --filter=refund_then_revision_new_obligation_accepts_new_payments_without_resurrecting_stale_component \
  --compact
```

Failure loop after that command:

```text
FACT
-> CONTRACT
-> CLASSIFICATION
-> ACTION
-> PROOF
```

Do not run adjacent suites, the absurd gauntlet, or `make verify` until this
focused characterization is GREEN.

### Unresolved risk inside this target

Only one unresolved risk remains: whether runtime behavior actually permits the
new legitimate post-refund revision obligation to receive partial/final payment
without allocating to stale refunded identities or creating duplicate inventory
effects.

No stale-editor, cancellation, partial-quantity refund, or unrelated residual gap
was started in this continuation.


## 2026-09-14 continuation — runtime RED: historical service fee re-entered payment target

### Scope lock

Single target only:

```text
paid mixed transaction
-> refund historical store-stock component
-> accepted revision creates legitimate replacement obligation
-> partial payment
-> final settlement
```

No stale-editor, cancellation, partial-quantity refund, reporting rewrite, or broad
reader refactor was opened.

### FACT — first runtime RED

Owner ran:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  --filter=refund_then_revision_new_obligation_accepts_new_payments_without_resurrecting_stale_component \
  --compact
```

Result:

```text
1 failed
34 assertions completed before failure
```

The final 60000 settlement correctly allocated:

```text
10000 -> current replacement service_store_stock_part
```

but the remaining:

```text
50000 -> service_fee
```

was written to work_item_id:

```text
cbc6c15b-4a2c-44a0-a777-8fe2ba74fffa
```

instead of the proven replacement work_item_id:

```text
4ac7ab55-53c1-4f8d-8eea-d5c57181d25f
```

The scenario contains exactly one original work item and one persisted replacement
work item. The original work item is preserved because
`WorkItemDeletesTrait` protects refund-referenced rows. Therefore the only
non-replacement work_item identity in this focused scenario is the historical
pre-revision work item.

A focused diagnostic assertion was added first in commit:

```text
4792d491 test: prove stale service fee payment identity
```

It explicitly records the pre-patch expectation that the unexpected final
service-fee allocation identity is `$oldWorkItemId`. The final regression now
asserts the inverse invariant after the production patch.

### CONTRACT

ADR-0045 is explicit:

- old active row/component identities become historical/stale after replacement;
- current payment operations resolve against the current revision;
- historical refund anchors remain immutable;
- historical refunded/shadow rows do not become current components merely because
  another revision exists;
- new active obligation comes from the accepted current revision.

ADR-0044 settlement semantics are unchanged. The 60000 final cash settlement is
valid. Only its component target boundary was wrong.

### CLASSIFICATION

```text
PRODUCTION BUG
```

This is not TEST WRONG and not CONTRACT GAP.

### Root cause

`DatabaseNoteReaderAdapter` intentionally rehydrates every `work_items` row for
the note, including refund-referenced historical anchors.

`ResolveNotePayableComponents::fromNote()` only excluded canceled rows. A preserved
historical package/service row can remain non-canceled because its refunded stock
component and non-refundable service fee share the same historical work item.

Consequently, `RecordAndAllocateNotePaymentOperation` could receive both:

- historical pre-revision work item components;
- active replacement work item components.

The refunded/reversed stock guard correctly prevented resurrection of the old
store-stock component, but the old service fee had no equivalent historical-row
boundary and therefore remained a candidate. Component priority then allowed that
historical service fee to consume the final 50000.

### ACTION — smallest shared seam

No global note-reader filtering was introduced.

Production changes:

1. `ResolveNotePayableComponents`
   - added explicit current-revision filtering using
     `NoteRevision::lines()->workItemRootId()`;
   - full-note and selected-row payment resolution can now be constrained to the
     active revision;
   - selected-row resolution rejects a selection if any resolved component belongs
     to a non-current work item.

2. `RecordAndAllocateNotePaymentOperation`
   - resolves the authoritative current revision when one exists;
   - new payments allocate only across components whose work-item identity belongs
     to that revision;
   - legacy notes without revision history retain the existing fallback behavior.

Important temporal boundary preserved:

`NoteReplacementPaymentAllocationReconciler` still uses
`ResolveNotePayableComponents::fromNote()` while a replacement revision is being
applied. It is not forced through the persisted current-revision pointer before the
new pointer is committed. This avoids breaking revision allocation replay.

Production commits:

```text
6bd08185 fix: constrain payable components to current revision
36147e99 fix: allocate new payments only to current revision
46338696 test: lock current revision service fee allocation
```

Files changed for this RED:

```text
app/Application/Payment/Services/ResolveNotePayableComponents.php
app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php
tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php
```

No reporting, inventory, refund-history, reader-global, cancellation, or ADR semantic
files were changed.

### PROOF boundary

Confirmed proof already available:

- owner runtime reached 34 assertions before the first RED;
- current replacement stock component correctly received the first 10000 of the
  final settlement;
- source/cardinality proof identifies the other service-fee identity as the single
  preserved historical pre-revision work item;
- current revision snapshot creation occurs after replacement work items are
  persisted, so `note_revision_lines.work_item_root_id` is the authoritative
  persisted replacement identity.

Post-patch runtime GREEN is still pending owner execution. Do not classify the
slice as GREEN before that command passes.

### Exact next command

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  --filter=refund_then_revision_new_obligation_accepts_new_payments_without_resurrecting_stale_component \
  --compact
```

Stop after this command. Do not run adjacent suites, AbsurdTransactionGauntlet, or
`make verify` until this focused test is GREEN.


### Runtime RED #2 — component refund expected on wrong history surface

Owner reran the focused lifecycle test after the current-revision payable-component patch.

Result:

```text
1 failed
54 assertions completed before failure
```

The previous payment-allocation RED was no longer present. The test advanced through:

- accepted post-refund revision;
- current replacement stock component allocation;
- partial payment;
- final settlement;
- no allocation to the historical refunded work item/component;
- payment timeline with all three historical payments.

The new failure was only this assertion:

```text
expected refund reason inside note['correction_history']
```

#### FACT

This scenario selects the historical work item containing:

- one refundable `service_store_stock_part`;
- one non-refundable `service_fee`.

`SelectedNoteRowsRefundPlanFactory::cancellableRowIds()` only marks a whole row
cancellable when every payment allocation on that selected row is refundable.

Because `service_fee` is not selected-row refundable, this component-only refund
produces:

```text
cancellableRowIds = []
```

Therefore `RecordSelectedRowsRefundPlanTransaction` does not call
`CancelSelectedRowsAndSyncActiveNoteTotal`, so it intentionally does not create a
`note_rows_canceled_via_refund` mutation event.

`correction_history` is sourced from `note_mutation_events`, not from the refund ledger.

The canonical immutable refund history for this path is:

```text
customer_refunds
refund_component_allocations
```

The refund reason and original component identity are already persisted there.

#### CONTRACT

ADR-0045 requires historical refunds to remain immutable and distinct from edits or
row replacement. It does not require a component-only refund to masquerade as a
whole-row cancellation mutation.

The original session constraint also explicitly forbids turning refund into edit or
edit into refund.

#### CLASSIFICATION

```text
TEST WRONG
```

Production behavior is correct for this boundary.

#### ACTION

Only the focused test was corrected:

- removed the invalid expectation that the component refund reason must appear in
  `correction_history`;
- added an explicit assertion that the component-only refund reason is not presented
  as a row-cancellation correction event;
- re-asserted the immutable `customer_refunds` row after revision and later payments;
- re-asserted the immutable `refund_component_allocations` row with the original
  historical work-item/component identity.

Commit:

```text
ba6d6db7 test: keep component refund history on refund ledger
```

No production file changed for RED #2.

### Exact next command after RED #2

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  --filter=refund_then_revision_new_obligation_accepts_new_payments_without_resurrecting_stale_component \
  --compact
```

Do not move to adjacent suites until this focused test is GREEN.


### Focused runtime GREEN

Owner reran the focused characterization after RED #2 test correction.

Command:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php \
  --filter=refund_then_revision_new_obligation_accepts_new_payments_without_resurrecting_stale_component \
  --compact
```

Result:

```text
1 passed
71 assertions
Duration: 5.82s
```

This closes the focused target proof for:

```text
paid mixed transaction
-> component refund
-> accepted replacement revision
-> legitimate current outstanding
-> partial payment
-> final payment
```

Proven invariants:

- old refunded stock component receives zero new allocation;
- old historical work item receives zero new allocation;
- surviving historical settlement is replayed onto the replacement component;
- new partial payment targets current replacement component only;
- final payment settles current replacement stock component and current service fee;
- original payment/refund history remains immutable;
- no duplicate inventory reversal is created;
- payment timeline remains gross historical payment truth;
- component-only refund remains on refund ledger and is not misrepresented as a
  row-cancellation correction event;
- final current settlement reaches zero outstanding.

Focused checkpoint status:

```text
GREEN
```

Next checkpoint is adjacent revision/refund/payment regression only.
Do not run AbsurdTransactionGauntlet or make verify before that checkpoint is GREEN.


### Next checkpoint — adjacent revision/refund/payment regression

Run only this adjacent set before any broader Note/Payment suite:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Unit/Application/Payment/Services/ResolveNotePayableComponentsTest.php \
  tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php \
  tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php \
  tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php \
  --compact
```

Why these six:

- direct unit boundary for the modified payable-component resolver;
- refund -> revision historical/current row boundary;
- ordinary payment-after-revision settlement;
- refunded component pay-again guard;
- product replacement payment/revision finance path;
- service + store-stock replacement path, the closest sibling to the service-fee
  identity bug fixed in this session.

Stop after this command and classify any RED before changing production again.


### Adjacent revision/refund/payment regression GREEN

Owner ran:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Unit/Application/Payment/Services/ResolveNotePayableComponentsTest.php \
  tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php \
  tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php \
  tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php \
  --compact
```

Result:

```text
61 passed
435 assertions
Duration: 7.11s
```

Checkpoint status:

```text
GREEN
```

This proves the current-revision payment boundary patch is compatible with:

- direct payable-component resolver behavior;
- refund -> revision historical/current row separation;
- payment-after-revision settlement;
- component refund pay-again protection;
- product replacement finance replay;
- service + store-stock replacement finance replay.

Next checkpoint is the adjacent Note + Payment feature suite only.
Do not run AbsurdTransactionGauntlet or make verify before that checkpoint is GREEN.


### Next checkpoint — wider Note + Payment feature regression

Run all Note and Payment feature tests except the dedicated absurd gauntlet, which remains
the following checkpoint:

```bash
mapfile -t tests < <(
  find tests/Feature/Note tests/Feature/Payment \
    -type f -name '*Test.php' \
    ! -name 'AbsurdTransactionGauntletFeatureTest.php' \
    | sort
)

php -d memory_limit=-1 vendor/bin/pest "${tests[@]}" --compact
```

The gauntlet is deliberately excluded so a failure in the wider adjacent surface is not
mixed with the dedicated end-to-end absurd-chain checkpoint.

Stop and classify any RED before moving on.


### Wider Note + Payment feature regression GREEN

Owner ran all Note and Payment feature tests except the dedicated absurd gauntlet:

```bash
mapfile -t tests < <(
  find tests/Feature/Note tests/Feature/Payment \
    -type f -name '*Test.php' \
    ! -name 'AbsurdTransactionGauntletFeatureTest.php' \
    | sort
)

php -d memory_limit=-1 vendor/bin/pest "${tests[@]}" --compact
```

Result:

```text
510 passed
4086 assertions
Duration: 50.63s
```

Checkpoint status:

```text
GREEN
```

This materially widens proof beyond the focused and adjacent regression set while keeping
the dedicated absurd lifecycle chain isolated for the next checkpoint.

Next checkpoint:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php \
  --compact
```

Do not run make verify until the absurd gauntlet is GREEN.


### Absurd transaction gauntlet GREEN

Owner ran:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/AbsurdTransactionGauntletFeatureTest.php \
  --compact
```

Result:

```text
1 passed
161 assertions
Duration: 5.97s
```

Checkpoint status:

```text
GREEN
```

### Final make verify RED — line audit only

The final `make verify` did not report a runtime lifecycle regression. It stopped at
the repository line-count audit:

```text
ERROR: File berikut melebihi limit 100 baris tanpa label bypass:
- [114 lines] app/Application/Payment/Services/ResolveNotePayableComponents.php
- [104 lines] app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php
make: *** [mk/hexagonal.mk:61: audit-lines] Error 1
```

#### CLASSIFICATION

```text
ARCHITECTURE / LINE-AUDIT FAILURE
```

This is not a new payment semantic failure. The current-revision hardening added enough
orchestration to push two application services past the repository's 100-line rule.

No bypass label was added.

#### ACTION

The current-revision payment-target boundary was extracted into:

```text
app/Application/Payment/Services/ResolveNotePaymentTargetComponents.php
```

Responsibilities after the split:

- `ResolveNotePayableComponents`
  - raw aggregate component resolution;
  - remains usable by revision allocation replay before current revision pointer commit.
- `ResolveNotePaymentTargetComponents`
  - new-payment-only current revision boundary;
  - legacy note fallback;
  - current revision work-item filtering;
  - rejection of stale selected payment rows.
- `RecordAndAllocateNotePaymentOperation`
  - payment orchestration only;
  - delegates target component resolution to the extracted service.

Static line-count proof after refactor:

```text
ResolveNotePayableComponents.php: 48 lines
ResolveNotePaymentTargetComponents.php: 77 lines
RecordAndAllocateNotePaymentOperation.php: 87 lines
```

No `@audit-skip: line-limit` bypass was introduced.

Refactor commits on `main`:

```text
c0f6255b refactor: isolate current revision payment targets
ba724f3e refactor: isolate current revision payment targets
05c5beab refactor: isolate current revision payment targets
980509a2 refactor: clean payment target resolver import
```

Runtime proof after this refactor is still pending. The next and only command is the
full repository gate:

```bash
make verify
```

If that command is GREEN, this target can be closed.


### Final repository verification GREEN — target closed

Owner pulled the final refactor and ran:

```bash
make verify
```

Final result:

```text
PHPStan: [OK] No errors
line audit: SUCCESS
Blade audit: SUCCESS
contract audit: passed

Tests: 1701 passed
Assertions: 11202
Duration: 75.34s
```

Final checkpoint status:

```text
GREEN
```

The session target is now closed.

Proven end-to-end for this target:

- paid mixed transaction;
- partial/component refund;
- immutable historical refund allocation;
- accepted revision after refund;
- stale refunded historical work item/component excluded from new payment targeting;
- legitimate current revision obligation remains payable;
- partial payment succeeds against the current revision;
- final settlement succeeds against the current revision;
- no resurrection of refunded component allocation;
- no duplicate stock reversal;
- immutable payment/refund history remains intact;
- Note/Payment adjacent regression remains green;
- absurd transaction gauntlet remains green;
- full repository verification remains green;
- PHPStan, line limit, Blade, and contract audits remain green.

Final proof ladder for this session:

```text
focused lifecycle
  1 passed / 71 assertions

adjacent revision-refund-payment
  61 passed / 435 assertions

wider Note + Payment
  510 passed / 4086 assertions

AbsurdTransactionGauntlet
  1 passed / 161 assertions

make verify
  1701 passed / 11202 assertions
  PHPStan PASS
  line audit PASS
  Blade audit PASS
  contract audit PASS
```

No further production or test changes are required for this target.
Do not broaden this closed checkpoint into stale editor, cancellation, partial-quantity
refund, or unrelated UI work without a new explicit target.


## 2026-09-14 continuation — Detail Nota ordinary/component refund UI hardening

### Scope lock

Single UI/read-model target only:

```text
Detail Nota after ordinary/component refund
```

The previously closed backend target remains closed:

```text
refund -> revision -> legitimate new payment -> final settlement
```

No finance write-engine patch is allowed unless a focused runtime RED proves a new write bug.

### First focused RED

Characterization:

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php \
  --compact
```

Initial result:

```text
1 failed
3 assertions completed before failure
Undefined array key "refund_timeline"
Duration: 5.76s
```

Scenario:

```text
paid product-only note
-> ordinary refund
-> refresh Detail Nota
-> inspect desktop + handset history/action state
```

### FACT

- refund request succeeds;
- customer refund/refund component ledger already exists and is authoritative;
- refreshed Detail Nota payload has no explicit ordinary/component refund timeline;
- existing financial history renders payment timeline and revision-surplus refund history only;
- `correction_history` remains note mutation history and is not a valid substitute for component refund ledger history.

### CONTRACT

ADR-0042 requires refunded historical components to remain:

- not editable;
- not payable;
- not refundable again;
- visible in detail/history/report;
- linked to refund/payment/inventory/audit records.

ADR-0045 requires historical refunded identities to remain historical rather than becoming current payable rows.

### CLASSIFICATION

```text
PRODUCTION BUG — read-model/presentation gap
```

No finance write bug was observed.

### Smallest action

Added a dedicated ordinary/component refund history surface sourced from canonical ledger data:

```text
customer_refunds
refund_component_allocations
```

New read-side pieces:

- `CustomerRefundHistoryReaderPort`
- `DatabaseCustomerRefundHistoryReaderAdapter`
- `NoteRefundTimelineBuilder`
- shared `refund-timeline.blade.php`

Detail payload now exposes:

```text
refund_timeline
```

Financial history renders that timeline for both desktop and handset because both layouts consume the shared financial-history partial.

The builder anchors component labels to historical `work_item_id` / `component_ref_id`, not current revision identity.

No change was made to:

- refund/payment write paths;
- allocation engine;
- stock reversal;
- revision engine;
- ADR-0044 settlement semantics;
- `correction_history`.

### Focused proof pending

Next command:

```bash
git pull

php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php \
  --compact
```

Do not expand to package/revision/new-payment UI cases until this focused proof is GREEN.


### Focused ordinary refund history proof GREEN

Owner reported the focused product-only refund detail test GREEN after the dedicated
refund timeline read-model/presentation patch.

Exact assertion count/duration were not supplied in the chat, so they are not fabricated here.

Proven focused behavior:

- ordinary product refund remains visible after refresh;
- refund date, amount, reason and refunded component are exposed from canonical refund ledger;
- desktop and handset consume the same shared refund history presentation;
- historical refunded component is not exposed as a current payable component;
- payment/edit/refund actions are hidden when no current backend capability remains;
- `correction_history` remains untouched as note mutation history.

### Next focused slice — package component refund with surviving service

Characterization added:

```text
paid service + store-stock package
-> refund store-stock product component
-> service component survives as current settled component
-> refresh Detail Nota
-> inspect desktop + handset
```

Required assertions:

- refund timeline shows the historical store-stock component, amount and reason;
- current row still shows the surviving service identity;
- row settlement shows refunded product amount + surviving service net paid;
- current billing projection excludes the refunded stock component;
- current billing projection retains only the settled service fee;
- outstanding remains zero;
- no payment action when no current payable obligation exists;
- no refund action for the already-refunded stock component;
- no edit action when cashier submit capability would reject the current state.

Next command:

```bash
git pull

php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php \
  --filter=package_component_refund_refresh_keeps_service_current_and_product_historical \
  --compact
```


### Package component refund detail proof GREEN

Owner reported the focused package-component refund detail characterization GREEN.

Exact assertion count/duration were not supplied, so they are not invented here.

Proven UI/read-model behavior:

- historical store-stock refund remains visible in refund timeline;
- surviving service identity remains visible as current row content;
- refunded stock component is absent from current billing projection;
- current billing retains the settled service fee only;
- outstanding is zero;
- payment/refund/edit action visibility matches backend capability;
- desktop and handset render the same shared refund history surface.

### Next focused slice — refund -> revision -> refresh detail

Characterization added for:

```text
paid 300k product note
-> historical refund 100k
-> accepted revision to current 250k service-only obligation
-> carry-forward 200k
-> current outstanding 50k
-> refresh Detail Nota on desktop + handset
```

Required UI/read-model boundary:

- historical refund event still shows 100k, reason, and old product identity;
- old refunded work item remains historical only;
- current row is the new revision work item;
- current grand total = 250k;
- current net paid = 200k;
- current outstanding = 50k;
- Bayar Sebagian + Lunasi visible because legitimate current obligation exists;
- Edit visible only because the accepted current admin revision remains editable;
- refund action hidden because the current service-only outstanding row is not yet refundable;
- historical refund must not appear as current payable/billing identity.

Next command:

```bash
git pull

php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php \
  --filter=refund_then_revision_refresh_keeps_refund_historical_and_new_revision_current \
  --compact
```


### Refund -> revision detail proof GREEN

Owner reported the focused refund -> accepted revision -> refreshed Detail Nota characterization GREEN.

Exact assertion count/duration were not supplied, so they are not invented here.

Proven UI/read-model boundary:

- historical refund remains visible after accepted revision;
- old refunded work-item identity remains historical only;
- current detail row is the replacement revision identity;
- current carry-forward settlement drives outstanding correctly;
- legitimate current payment actions appear when current outstanding exists;
- edit visibility matches the active admin revision capability;
- desktop and handset remain aligned.

### Next focused slice — refund -> legitimate new payment -> refresh detail

Characterization added for a current revision with:

```text
historical original payment = 300k
historical ordinary refund = 100k
current revision service obligation = 250k
carried current allocation = 200k
current outstanding before payment = 50k
```

The test then records a legitimate new 50k payment through the HTTP payment route and refreshes
Detail Nota on desktop + handset.

Required assertions:

- the new payment allocates only to the current revision service component;
- zero new allocation is created for the stale refunded work item;
- current row reaches net paid 250k and outstanding 0;
- payment actions disappear after settlement;
- admin edit action remains available if backend capability allows it;
- current refund action appears for the newly settled current row;
- historical refund event, reason and old product identity remain visible;
- current billing projection contains only the current service component;
- historical refund does not become current payable identity.

Next command:

```bash
git pull

php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php \
  --filter=refund_then_legitimate_new_payment_refresh_settles_current_revision_without_hiding_history \
  --compact
```


### Refund -> legitimate new payment focused RED

Runtime:

```text
1 failed
6 assertions before failure
Duration: 5.73s
```

Failure:

```text
Uang masuk kurang dari nominal pembayaran.
```

The rendered Detail Nota immediately before submit showed current outstanding `50000`,
but the HTTP payment preflight rejected cash tender `50000` as insufficient.

### FACT

- Detail Nota current-revision projection reported:
  - grand total 250000
  - net paid 200000
  - outstanding 50000
  - payment action visible
- HTTP payment preflight used `NoteOutstandingPaymentAmountResolver`.
- `DatabaseNoteReaderAdapter` loads all root work items, including refund-protected historical rows.
- `NoteMapper` derives root aggregate total from all non-canceled root work items.
- Refund-protected historical rows remain persisted so refund FK/audit history is immutable.
- Therefore root aggregate total can exceed current revision total after refund + revision.
- Current Detail projection is already revision-aware; payment preflight was not.

### CONTRACT

ADR-0042 / ADR-0045 require:

- historical refunded rows remain historical/shadow;
- current payable state comes from current revision;
- legitimate current outstanding remains payable;
- stale refunded historical identity must not re-enter current payment semantics.

ADR-0044 remains unchanged: settlement intent and cash tender semantics are not modified.

### CLASSIFICATION

```text
PRODUCTION BUG — revision-unaware payment outstanding preflight/read boundary
```

This is not a finance write-engine bug. Allocation/write semantics remain closed and unchanged.

### Smallest action

`NoteOutstandingPaymentAmountResolver` now uses the same
`ResolvesNoteOperationalCurrentRevisionSettlement` boundary already used by
`NoteOperationalStatusResolver`.

Behavior:

- if current revision exists:
  - grand total/net paid/outstanding come from current revision settlement projection;
- otherwise:
  - existing legacy note-level gross/allocated/refund calculation remains unchanged.

No changes to:

- payment allocation writer;
- payment component allocator;
- refund writer;
- stock reversal;
- revision write/replay;
- ADR-0044 tender semantics.

Regression lock added inside the focused UI/HTTP characterization:

```text
payment outstanding resolver:
grand total = 250000
net paid = 200000
outstanding = 50000
```

Touched production file remains within line audit limit without bypass.

Next command:

```bash
git pull

php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php \
  --filter=refund_then_legitimate_new_payment_refresh_settles_current_revision_without_hiding_history \
  --compact
```

Stop and classify any further RED before expanding.


### Focused rerun RED — assertion harness only

Runtime:

```text
1 failed
3 assertions before failure
Duration: 5.26s
```

Failure:

```text
TypeError:
PHPUnit Assert::assertTrue() argument #2 must be string, null given.
```

### CLASSIFICATION

```text
TEST WRONG
```

The characterization passed a nullable domain result message directly as PHPUnit's
required string assertion message. A successful Result carries `message() = null`,
so the test harness raised TypeError before it could inspect the resolver result.

Smallest action:

- coalesce nullable result message to a string fallback;
- no production change;
- rerun the same focused scenario.


### Focused rerun RED — revision-aware resolver not wired by container

Runtime:

```text
1 failed
5 assertions before failure
Duration: 5.16s
```

Failure:

```text
expected current revision grand total 250000
actual payment outstanding grand total 550000
```

### FACT

The resolver source had current-revision support, but its two revision dependencies were
nullable constructor arguments with default `null`.

Laravel container therefore resolved the service through the legacy 3-dependency shape,
so `currentRevisionSettlement()` returned `null` and payment preflight still used the
root aggregate containing both:

- historical refund-protected work item = 300000;
- current revision work item = 250000.

That produced the incorrect preflight grand total 550000.

Sibling evidence:

`NoteOperationalStatusResolver` already uses an explicit container binding to inject
the same current-revision resolver/projector pair, because those dependencies are also
optional in the class constructor.

### CLASSIFICATION

```text
PRODUCTION BUG PATCH INCOMPLETE — dependency wiring
```

No new finance write bug was discovered.

### ACTION

Added an explicit `NoteOutstandingPaymentAmountResolver` singleton binding in
`NoteApplicationServiceProvider` with:

- `NoteReaderPort`
- `PaymentAllocationReaderPort`
- `CustomerRefundReaderPort`
- `NoteCurrentRevisionResolver`
- `CurrentRevisionRowSettlementProjector`

No payment/refund/revision writer changed.

Static line counts:

```text
NoteApplicationServiceProvider.php: 65
NoteOutstandingPaymentAmountResolver.php: 99
```

Next proof is the same focused HTTP/UI characterization.


### Refund -> legitimate new payment detail proof GREEN

Focused runtime:

```text
Tests: 1 passed
Assertions: 72
Duration: 5.28s
```

This proves the repaired current-revision payment preflight and refreshed Detail Nota agree on the same current obligation:

- current revision grand total = 250000;
- current revision net paid before new payment = 200000;
- legitimate outstanding = 50000;
- new 50000 payment is accepted;
- new allocation targets only the current revision service component;
- stale refunded historical work item receives no new allocation;
- refreshed current row reaches outstanding 0;
- payment actions disappear after settlement;
- current refund action becomes available for the newly settled current row;
- historical refund remains visible and unchanged;
- desktop + handset remain aligned.

### Next focused slice — duplicate refund replay display

Characterization added for:

```text
paid product-only note
-> refund with idempotency key
-> replay exact same refund request
-> refresh Detail Nota
-> desktop + handset
```

Required proof:

- exactly one `customer_refunds` row;
- exactly one `refund_component_allocations` row;
- exactly one inventory reversal;
- exactly one `refund_timeline` event;
- rendered HTML contains exactly one refund-history event;
- reason/product label appear once as one historical event;
- no payment/refund action is resurrected after replay.

Next command:

```bash
git pull

php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php \
  --filter=duplicate_refund_replay_renders_one_historical_event_after_refresh \
  --compact
```


### Duplicate refund replay focused RED — redirect expectation

Runtime:

```text
1 failed
3 assertions before failure
Duration: 5.22s
```

Failure:

```text
expected redirect to cashier note detail
actual redirect to cashier notes index
```

### CLASSIFICATION

```text
TEST WRONG
```

Canonical transport behavior from `ClosedNoteRefundResponseFactory::success()` is:

- cashier refund success -> `cashier.notes.index`;
- admin refund success -> `admin.notes.index`.

The same response factory is used for idempotent replay success.

Smallest action:

- align both first-submit and replay redirect assertions with the canonical index route;
- no production change;
- rerun the same duplicate refund replay display characterization.


### Duplicate refund replay focused RED — global reason string count

Runtime:

```text
1 failed
24 assertions before failure
Duration: 5.32s
```

Observed before failure:

- exactly one `customer_refunds` row;
- exactly one `refund_component_allocations` row;
- exactly one stock reversal;
- exactly one `refund_timeline` item;
- exactly one rendered `data-refund-history-event` on desktop.

Failure:

```text
expected refund reason text to occur once in the entire HTML
actual occurrences = 3
```

### CLASSIFICATION

```text
TEST WRONG
```

The duplicate-display invariant is structural event cardinality, not global text-substring
cardinality. The same reason string may legally appear in other rendered state/form/history
markup without creating another refund event.

Smallest action:

- keep exact refund event count assertion;
- remove the global reason substring count assertion;
- retain explicit timeline reason equality and `assertSee` proof;
- no production change.

Next proof is the same focused duplicate refund replay characterization.


### Full verify RED — two adjacency regressions

Latest full verify:

```text
PHPStan: PASS
line audit: PASS
Blade audit: PASS
contract audit: PASS
Tests: 2 failed, 1704 passed
Assertions: 11485
Duration: 57.80s
```

Failures:

1. `RecordNotePaymentHttpFeatureTest::test_selected_row_payment_uses_combined_legacy_and_component_allocations`
   - error: `Amount alokasi payment melebihi outstanding note.`
   - classification: PRODUCTION BUG in current-revision read-model reconciliation.
   - current component settlement saw component allocation but did not fold note-level legacy allocation remainder into `net_paid/outstanding`.
   - fix: current-revision component summary now reconciles row-level allocated/refunded totals with component collectible settlement while preserving non-collectible refunded component semantics.

2. `CashierNoteRevisionSmokeTest::test_note_detail_repairs_existing_revision_pointer_when_current_pointer_is_empty`
   - actual status: 403.
   - classification: PRODUCTION REGRESSION from revision-aware preflight.
   - revision history existed but `current_revision_id` was empty; `hasRevision()` returned true and settlement attempted `resolveOrFail()` before the detail controller could repair the pointer.
   - fix: add explicit `hasCurrentRevision()`; operational/current settlement only uses revision projection when a current pointer is actually resolvable. Historical revision existence remains distinct, and write targeting behavior is unchanged.

Production commits:

```text
c6f14d76 fix: include legacy money in current revision settlement
7a80e503 fix: distinguish current revision from revision history
19282d5c fix: allow legacy settlement before revision pointer repair
```

Runtime proof pending. Run the two previously failing tests before the next full `make verify`.
