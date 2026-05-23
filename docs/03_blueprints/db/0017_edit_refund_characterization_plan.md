# Edit Refund Characterization Plan

## Status

Planning draft.

This document is test/source-map planning only.

This document does not authorize production code changes, migration changes, UI changes, report changes, or refund logic changes.

## Purpose

Define the first safe characterization test sequence for edit, revision, settlement, refund_due, refund_paid, ordinary refund, inventory effects, and projection behavior.

The goal is to prove current behavior before implementation.

## FACT

### F1 - Active edit submit route

Active admin and cashier workspace edit submit routes use:

- `StoreNoteRevisionController`
- `StoreNoteRevisionRequest`
- `CreateNoteRevisionHandler`

Active edit submit is revision-based.

`StoreNoteRevisionRequest` forces `inline_payment.decision = skip`.

Therefore revision submit and payment submit are currently separate concerns.

### F2 - Candidate update handler is not active route proof

`UpdateTransactionWorkspaceController` and `UpdateTransactionWorkspaceHandler` exist in source.

No active route binding was proven from `routes/web/note.php`.

Do not patch `UpdateTransactionWorkspaceHandler` as active edit behavior until route binding proof or dead-path decision exists.

### F3 - Active refund route

Active admin and cashier refund submit routes use:

- `RecordClosedNoteRefundController`
- `RecordClosedNoteRefundRequest`
- `SelectedNoteRowsRefundPlanResolver`
- `RecordSelectedRowsRefundPlanTransaction`

Refund route accepts:

- `selected_row_ids`
- `refunded_at`
- `reason`

Refund plan is backend-built before commit.

### F4 - Current locked architecture

The edit/refund target architecture is:

- ledger
- revision snapshot
- current projection

Current work items are current operational rows or projection, not final historical truth.

Payment and refund records are financial ledger events.

Inventory movements are stock ledger events.

Projection is derived and fast-read only.

### F5 - Backend settlement calculator boundary

Payment after edit or revision must be backend settlement-preview driven.

Blade and JavaScript may display and assist only.

Final payable amount must not come from UI calculator state or raw grand total assumptions.

### F6 - Surplus direction

Downward paid revision can create surplus.

Surplus must be explicit.

Allowed current/future concepts include:

- overpaid_pending
- refund_due
- refund_paid

Customer credit remains blocked until stable customer identity contract exists.

## GAP

### G1 - Full test status gap

This plan does not claim any new tests exist or pass.

No test has been added in this step.

No test command has been run in this step.

### G2 - Runtime behavior gap

This plan does not prove current runtime behavior for:

- later revision after refund_due
- later revision after refund_paid
- ordinary refund after revision replacement
- projection after each mutation
- report/cash ledger output
- browser-executed cashier UI behavior

### G3 - Source-map gap

This plan does not fully map every table, reader, writer, and adapter for:

- `note_revision_settlements`
- `note_revision_surplus_dispositions`
- `note_revision_surplus_refund_payments`
- canonical `audit_events`
- report readers
- export readers

Those must be mapped before touching related production files.

## ASSUMPTION

### A1 - Test-first sequence

Assumption:

The next implementation-safe work should start with characterization tests.

Reason:

The owner explicitly requested analysis first and separation of unknowns from decisions.

Risk if wrong:

If the owner wants source-map docs before tests, this plan may start too close to test implementation.

Containment:

Do not create tests until the owner accepts this plan or a narrower source-map step replaces it.

### A2 - Feature test location

Assumption:

Initial characterization tests should live under `tests/Feature/Note` or `tests/Feature/Payment`.

Reason:

The flows are routed/application-level financial lifecycle behavior.

Risk if wrong:

A lower-level application test may be better for some settlement services.

Containment:

Each test candidate below states intended level and may be split before implementation.

### A3 - No browser test in first slice

Assumption:

First slice can use PHP feature/application tests and static JS contract tests before browser QA.

Reason:

Existing project pattern already uses PHP render/static JS contract tests for cashier workspace payment flow.

Risk if wrong:

Runtime JS behavior may diverge from static contract.

Containment:

Browser/manual QA remains explicit gap.

## DECISION

### D1 - First tests must not patch production

Initial Phase 1D should add RED or characterization tests only.

Production patch is forbidden until a test proves a defect or source gap requires a narrow patch.

### D2 - Do not start with UI

Do not start from Blade or JavaScript.

If UI is involved, it must only render backend-derived context or transport user intent.

### D3 - Do not merge revision submit and payment submit

Keep `StoreNoteRevisionRequest` payment skip boundary.

Do not implement combined revision-plus-payment submit in this phase.

### D4 - Do not patch reports yet

Do not patch reports before official source records and version/read mode are mapped.

### D5 - Do not introduce customer credit

Customer credit and customer_balance_entries are blocked until stable customer identity is decided.

### D6 - Do not touch high-risk delete/rebuild paths casually

The following paths require direct characterization before any production patch:

- `WorkItemDeletesTrait`
- `DatabasePaymentComponentAllocationWriterAdapter`
- `ApplyNoteRevisionAsActiveReplacement`
- `NoteReplacementPaymentAllocationReconciler`
- `UpdateTransactionWorkspaceWorkItemPersister`
- `CancelSelectedRowsAndSyncActiveNoteTotal`
- `RecordSelectedRowsRefundPlanTransaction`
- `NoteHistoryProjectionService`

## Characterization Test Candidates

### C1 - Revision after partial payment preserves backend settlement

Intent:

Prove active revision behavior when an existing note has partial payment.

Level:

Feature or application test.

Possible file:

`tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`

Scenario:

1. Create or seed note total 100000.
2. Seed payment allocation 40000.
3. Submit active revision to total 120000.
4. Assert revision succeeds.
5. Assert carried/net payment remains 40000.
6. Assert outstanding becomes 80000.
7. Assert projection does not invent paid amount.

Expected current behavior:

Unknown.

Status:

GAP until test exists.

### C2 - Revision after ordinary refund does not double-subtract refund

Intent:

Prove old refund is carried exactly once.

Level:

Feature or application test.

Possible file:

`tests/Feature/Note/NoteRevisionAfterRefundSettlementFeatureTest.php`

Scenario:

1. Seed note total 100000.
2. Seed payment allocation 100000.
3. Seed ordinary refund 30000 with refund component allocation.
4. Submit revision to total 70000.
5. Assert net paid is 70000.
6. Assert payable is zero.
7. Assert projection does not mark underpaid because refund was subtracted twice.

Expected current behavior:

Unknown from this step.

Status:

GAP until test exists.

### C3 - Later revision must not consume existing refund_due

Intent:

Prove refund_due liability stays explicit after later revision.

Level:

Feature or application test.

Possible file:

`tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php`

Scenario:

1. Seed paid note.
2. Create downward revision that creates surplus.
3. Create refund_due disposition.
4. Submit later revision.
5. Assert refund_due remains traceable.
6. Assert later revision does not silently reuse refund_due as available payment.

Expected current behavior:

Unknown from this step.

Status:

GAP until test exists.

### C4 - Later revision must not reclaim surplus refund_paid

Intent:

Prove refund_paid is actual cash-out and is not reusable money.

Level:

Feature or application test.

Possible file:

`tests/Feature/Note/NoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php`

Scenario:

1. Seed paid note.
2. Create downward revision with surplus.
3. Create refund_due.
4. Execute surplus refund_paid.
5. Submit later revision.
6. Assert refund_paid reduces available net carried money.
7. Assert refund_paid remains explicit cash-out.

Expected current behavior:

Unknown from this step.

Status:

GAP until test exists.

### C5 - Refund after revision uses current replacement row id

Intent:

Prove selected-row refund after revision accepts current row id and rejects stale historical row id.

Level:

Feature test.

Possible file:

`tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php`

Scenario:

1. Seed note with paid row.
2. Submit revision replacement.
3. Attempt refund using old historical work item id.
4. Assert rejected.
5. Attempt refund using current replacement row id.
6. Assert accepted or valid plan exists.

Expected current behavior:

Partially suggested by prior error-log proof, but must be re-characterized for current source before new patch.

Status:

GAP until test exists.

### C6 - Refund money effect and stock return effect stay separate

Intent:

Prove refund flow does not equate money refund with stock return blindly.

Level:

Application or feature test.

Possible file:

`tests/Feature/Payment/SelectedRowsRefundEffectBoundaryFeatureTest.php`

Scenario:

1. Service-only paid row refund.
2. Store-stock paid row refund.
3. External purchase paid row refund.
4. Assert each effect is explicit and no unrelated effect is triggered.

Expected current behavior:

Unknown from this step.

Status:

GAP until test exists.

### C7 - Projection after revision and refund matches explicit financial state

Intent:

Prove `note_history_projection` is derived from official records and remains consistent after mutation.

Level:

Feature test.

Possible file:

`tests/Feature/Note/NoteHistoryProjectionAfterEditRefundFeatureTest.php`

Scenario:

1. Seed note with payment.
2. Submit revision.
3. Submit refund.
4. Assert projection fields:
   - total_rupiah
   - allocated_rupiah
   - refunded_rupiah
   - net_paid_rupiah
   - outstanding_rupiah
   - line status counters

Expected current behavior:

Unknown from this step.

Status:

GAP until test exists.

### C8 - Cashier edit page renders backend settlement context

Intent:

Confirm UI displays backend-derived payment context and does not rely on JS-only totals.

Level:

Feature render test or static JS contract.

Possible file:

`tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php`

Scenario:

1. Seed partially paid note.
2. Open cashier edit workspace.
3. Assert backend settlement explanation rendered.
4. Assert modal exposes backend payable dataset.
5. Assert JS contract consumes backend payable dataset.

Expected current behavior:

Likely covered by ADR 0030 updates, but not reconfirmed in this step.

Status:

GAP until current command proof exists.

## Recommended Test Order

### Order 1 - Settlement carry-forward

Start with C1 and C2.

Reason:

If carry-forward paid/refunded math is not stable, refund_due/refund_paid tests will be noisy.

### Order 2 - Surplus liability continuity

Then run C3 and C4.

Reason:

refund_due and refund_paid are the core blocker for serious edit after downward revision.

### Order 3 - Refund current/historical row boundary

Then run C5 and C6.

Reason:

Refund after revision must not mutate stale historical anchors or trigger wrong inventory/money effects.

### Order 4 - Projection and UI display

Then run C7 and C8.

Reason:

Projection/UI must reflect backend truth after domain behavior is proven.

## First Active Test Slice Proposal

### Slice name

Phase 1D-1 - Revision carry-forward settlement characterization.

### First file

`tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`

### First tests

1. `test_revision_after_partial_payment_carries_existing_payment_into_outstanding`
2. `test_revision_after_ordinary_refund_does_not_double_subtract_refund`

### Production files allowed

None.

### Production files forbidden

All.

### Expected command

`php artisan test tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`

### Exit criteria

One of:

1. Tests pass and document current behavior as proven.
2. Tests fail and produce RED proof for a narrow backend patch.
3. Fixture cannot be built safely, creating a documented fixture/source-map GAP.

## Stop Conditions

Stop before writing production code if:

1. Route path is not active.
2. Fixture requires guessing table columns not proven by migrations or existing tests.
3. Test needs customer credit or customer identity.
4. Test requires combined revision-plus-payment submit.
5. Test touches UpdateTransactionWorkspaceHandler without route proof.
6. Test relies on JavaScript to decide backend money.
7. Test mutates report/export code.
8. Test hides failure by weakening assertions.

## Verification Commands

After creating this document:

    grep -n \
      -e "## FACT" \
      -e "## GAP" \
      -e "## ASSUMPTION" \
      -e "## DECISION" \
      -e "## Characterization Test Candidates" \
      -e "## Recommended Test Order" \
      -e "## First Active Test Slice Proposal" \
      -e "## Stop Conditions" \
      docs/03_blueprints/db/0017_edit_refund_characterization_plan.md

Guardrail verification:

    grep -n \
      -e "Production files allowed" \
      -e "None." \
      -e "Do not merge revision submit and payment submit" \
      -e "Do not touch high-risk delete/rebuild paths casually" \
      -e "Phase 1D-1 - Revision carry-forward settlement characterization" \
      docs/03_blueprints/db/0017_edit_refund_characterization_plan.md

## Phase 1D Source Map Correction

Status: source-map correction.

This section updates the initial characterization plan after inspecting existing tests and settlement services.

This section does not authorize production code changes.

## FACT

### Existing settlement unit coverage

`tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php` already covers:

- component payment and component refund totals when component settlement exists;
- fallback to legacy payment and legacy refund totals when component settlement does not exist;
- component surplus becomes `overpaid_pending`;
- active surplus `refund_paid` is treated as cash-out when building later revision settlement.

Therefore a new unit test for the same settlement math is not the first useful Phase 1D test.

### Existing surplus refund_paid feature coverage

`tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php` already covers later revision after surplus `refund_paid`.

The test asserts the later revision settlement records:

- `carry_forward_paid_rupiah = 265000`
- `carry_forward_refunded_rupiah = 50000`
- `net_paid_rupiah = 215000`
- `outstanding_rupiah = 15000`
- `settlement_status = underpaid`

Therefore candidate C4 is already represented by existing feature coverage and should not be the first new Phase 1D test.

### Existing downward overpaid/surplus feature coverage

`tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php` already covers active admin workspace revision that creates pending surplus settlement.

The test asserts:

- active revision route commits;
- `note_revision_settlements` stores `settlement_status = overpaid_pending`;
- `surplus_rupiah` is explicit;
- active component allocation is capped to current revised total;
- original customer payment remains preserved.

Therefore the first Phase 1D test should not duplicate downward paid revision surplus coverage.

### Existing fixture support

`tests/Support/SeedsMinimalNotePaymentFixture.php` already supports minimal note, payment, work item, service, store-stock, legacy allocation, and current revision fixtures.

New tests should reuse this fixture instead of inventing table columns.

## GAP

### G1 - Partial payment revision integration proof not yet found

Current source-map has not proven an existing feature test for active revision after partial payment where the new revision total is greater than carried paid amount.

Required proof:

- revision commits through active application or route path;
- `note_revision_settlements` stores carried paid amount;
- `outstanding_rupiah` equals revised total minus carried net paid;
- projection does not invent paid amount.

### G2 - Ordinary refund carry-forward integration proof not yet found

Current source-map has not proven an existing feature test for active revision after ordinary customer refund / refund component allocation where refund must be counted exactly once.

Required proof:

- carried paid amount is preserved;
- carried ordinary refunded amount is included once;
- net paid equals paid minus ordinary refunded;
- revision settlement does not double-subtract refund;
- projection remains consistent after revision.

### G3 - Existing test command proof not produced in this step

This source-map correction inspected repository files only.

It does not claim that existing tests currently pass locally.

Local command proof remains required before closing any test slice.

## DECISION

### D1 - Update Phase 1D-1 target

Phase 1D-1 should target gaps not already covered by existing tests.

New first candidate:

`tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`

Initial tests:

1. `test_revision_after_partial_payment_carries_paid_amount_into_underpaid_settlement`
2. `test_revision_after_ordinary_refund_counts_refund_once_in_settlement`

### D2 - Production files remain forbidden

Phase 1D-1 remains test-only.

Production files allowed:

None.

### D3 - Existing surplus refund_paid test is not duplicated

Do not duplicate `CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php`.

If needed, run it as adjacency proof, not as new coverage.

### D4 - Existing downward surplus test is not duplicated

Do not duplicate `NoteReplacementOverpaidAllocationReplayFeatureTest.php`.

If needed, run it as adjacency proof, not as new coverage.

## Updated First Active Test Slice

### Slice name

Phase 1D-1 - Missing carry-forward settlement feature characterization.

### Test file

`tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`

### Test 1

`test_revision_after_partial_payment_carries_paid_amount_into_underpaid_settlement`

Expected fixture shape:

- note total before revision: `100000`
- existing payment allocation: `40000`
- revised total: `120000`

Expected settlement:

- `gross_total_rupiah = 120000`
- `carry_forward_paid_rupiah = 40000`
- `carry_forward_refunded_rupiah = 0`
- `net_paid_rupiah = 40000`
- `outstanding_rupiah = 80000`
- `surplus_rupiah = 0`
- `settlement_status = underpaid`

### Test 2

`test_revision_after_ordinary_refund_counts_refund_once_in_settlement`

Expected fixture shape:

- note total before revision: `100000`
- existing paid amount: `100000`
- existing ordinary refunded amount: `30000`
- revised total: `70000`

Expected settlement:

- `gross_total_rupiah = 70000`
- `carry_forward_paid_rupiah = 100000`
- `carry_forward_refunded_rupiah = 30000`
- `net_paid_rupiah = 70000`
- `outstanding_rupiah = 0`
- `surplus_rupiah = 0`
- `settlement_status = paid`

## Suggested Local Proof Commands

After adding the test file in a later step:

```bash
php artisan test tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php

Adjacency proof candidates:

php artisan test \
  tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php \
  tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php \
  tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php \
  tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php

