# Handoff 0014 - Edit/Revision Service Store Stock Package Auto Split Phase 3

## Status

IN PROGRESS.

This handoff records Phase 3 edit/revision characterization and fixes for service-store-stock package auto split multi-product flow.

Do not treat this handoff as final closure for payment, settlement, refund, report/export, browser QA, or full verify.

## Source of Truth Priority

Use repo/local output priority from project rules.

1. Latest local operator command output.
2. Repo standards and rules.
3. Active blueprint/handoff docs.
4. Existing tests and source code.
5. Model inference only when explicitly labeled.

If this handoff conflicts with later local command output, later local command output wins.

## Required Working Rules

- Read rules before answering or patching.
- Local command output is source of truth.
- Do not invent file contents, repo status, test result, proof, or closure.
- Blueprint before implementation.
- One active step per response.
- Use FACT / GAP / DECISION / ACTIVE STEP / PROOF / NEXT.
- If data is missing, state GAP explicitly.
- If assumption is used, label it ASSUMPTION and do not treat it as fact.
- Do not claim pass, green, safe, done, or closed without command output.
- Characterize before implementing.
- After implementation, run focused test.
- After focused green, run verify gate before closure.
- After green, update handoff.
- Do not treat create/detail proof as edit/revision/refund proof.

## Context From Earlier Closed Work

The following were proven closed before this Phase 3 slice:

- Create transaction workspace service + store-stock package auto split: CLOSED.
- Multi-product service + store-stock create UI: CLOSED.
- Package auto split calculation: CLOSED.
- DB persistence note/work item/service detail/store stock lines: CLOSED.
- Inventory stock out on create: CLOSED.
- Inline payment lifecycle baseline: CLOSED.
- Package allocation audit: CLOSED.
- Report impact baseline: CLOSED.
- Detail UI `Alasan Nota`: CLOSED.
- Detail UI package breakdown: CLOSED.
- Reporting/page/export date expectation reconciliation after Indonesian ViewDateFormatter: CLOSED.
- Detail UI intro labels `Workspace Nota Admin/Kasir` were removed and tests were updated to current UI contract.

Previous local final gate before Phase 3:

```text
make verify
Tests: 2 skipped, 1131 passed (6342 assertions)
Phase 3 Goal

Edit/Revision lifecycle characterization for service-store-stock package auto split.

The goal includes proving or characterizing:

route edit workspace,
controller edit/update workspace,
request rules update,
old note/items hydration to edit UI,
whether Alasan Nota / operational_note can be edited and persisted,
whether service-store-stock multi-product survives edit,
whether package_auto_split survives edit,
whether package_total_rupiah, sparepart total, and service residual are correct after revision,
whether product name snapshot remains historical,
whether settlement/payment preview is safe after revision,
whether first-line assumptions break multi-product,
whether reporting/export after revision needs additional proof.
Focused Characterization File

Current focused characterization file:

tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
Latest Focused Local Proof

Command run locally:

php artisan test tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php

Latest local output:

PASS  Tests\Feature\Note\EditTransactionWorkspacePackageAutoSplitCharacterizationTest
✓ edit workspace preloads service store stock package auto split multi product revision    5.99s
✓ admin can submit service store stock package auto split multi product revision           0.15s
✓ package auto split multi product revision reverses old stock and issues replacement stock 0.11s

Tests: 3 passed (36 assertions)
Duration: 6.39s
Proven In This Phase 3 Slice

Based on the focused local proof above:

Edit preload multi-product package auto split revision: GREEN.
Admin submit edit/revision package auto split multi-product: GREEN.
operational_note update through revision submit: GREEN in focused test.
package_total_rupiah survives revision submit: GREEN in focused test.
Service residual calculation after package revision: GREEN in focused test.

Example tested:

package_total_rupiah = 300000
product A = 2 x 50000 = 100000
product B = 2 x 30000 = 60000
parts total = 160000
service residual = 140000
Two work_item_store_stock_lines survive replacement: GREEN in focused test.
Revision snapshot payload includes package metadata:
pricing_mode = package_auto_split
package_total_rupiah
service residual
2 store_stock_lines
Inventory reverse/reissue for two old/new product lines: GREEN in focused test.
Final inventory qty/costing for tested scenario: GREEN in focused test.
Bugs Found And Patched
EDIT-PRELOAD-001

Problem:

Revision workspace service-store-stock mapper hardcoded single store-stock line and crashed on valid multi-product package auto split current revision.

Observed local RED:

DomainException:
Revision servis + sparepart toko hanya mendukung 1 store stock line.

Patch files:

app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceProductLineMapper.php
app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceServiceStoreStockMapper.php

Patch intent:

Support multiple store_stock_lines when hydrating edit workspace oldItems.
Preserve product snapshot label if present.
Use revision_snapshot price basis.
Set package total from payload or revision line subtotal.

Focused status:

GREEN in tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
EDIT-REQUEST-001

Problem:

Update request rules were not aligned with create package auto split contract.

Observed local RED:

items.0.service.price_rupiah minimal harus bernilai 1.

Patch file:

app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceRules.php

Patch intent:

Add update rules for:
items.*.pricing_mode
items.*.package_total_rupiah
items.*.product_lines.*.*
items.*.product_lines.*.price_basis
items.*.external_purchase_lines.*.total_rupiah
Change service price rule from min:1 to min:0 so package_auto_split can submit zero and calculate residual.

Focused status:

GREEN in tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
EDIT-OPERATIONAL-NOTE-001

Problem:

Revision submit updated customer/date/total but did not update notes.operational_note.

Observed local RED:

Expected operational_note:
Alasan revisi package multi.

Actual operational_note:
Alasan awal package multi.

Patch files:

app/Core/Note/Note/NoteMutations.php
app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php
app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php

Patch intent:

NoteMutations::updateHeader() accepts optional operationalNote.
CreateNoteRevisionPayloadNoteBuilder passes note.operational_note into replacement Note::rehydrate(...).
ApplyNoteRevisionAsActiveReplacement passes $replacement->operationalNote() when updating root header.

Focused status:

GREEN in tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
REVISION-SNAPSHOT-001

Problem:

Revision submit created note_revision_lines.payload, but payload lacked package metadata:

pricing_mode
package_total_rupiah

Observed local RED:

Failed asserting that null is identical to 'package_auto_split'.

Patch file:

app/Application/Note/Services/NoteRevisionLinePayloadMapper.php

Patch intent:

For WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, revision line payload now includes:

pricing_mode = package_auto_split
package_total_rupiah = work item subtotal
parts_total_rupiah = sum(store_stock_lines.line_total_rupiah)
service_price_rupiah = service detail price
existing service payload and store_stock_lines remain present.

Focused status:

GREEN in tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
Files Modified In This Phase 3 Slice
app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceProductLineMapper.php
app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceServiceStoreStockMapper.php
app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceRules.php
app/Core/Note/Note/NoteMutations.php
app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php
app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php
app/Application/Note/Services/NoteRevisionLinePayloadMapper.php
tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
docs/04_lifecycle/handoff/0014_edit_revision_service_store_stock_package_autosplit_phase3_handoff.md
Important Boundary

Do not claim full Phase 3 closure yet.

The focused test proves preload, submit, package metadata, operational note update, and inventory reverse/reissue for the characterized scenario.

It does not prove payment, settlement, refund, report/export, browser QA, or full repo verify.

## Phase 3 Refund Boundary Proof - Downward Overpaid Package Multi-Product

REFUND-BOUNDARY-001

Problem / target:

Characterize selected-row refund boundary after a fully paid service-store-stock package auto split multi-product note is revised downward and becomes overpaid.

Scenario:
- original package total = 250000
- existing payment = 250000
- revision package total = 200000
- revised product A = 100000
- revised product B = 30000
- revised service residual = 70000
- surplus after revision = 50000

Local command:

```text
php artisan test tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php --filter=refund_after_downward_revision
```

Local output:

```text
PASS  Tests\Feature\Note\EditTransactionWorkspacePackageAutoSplitCharacterizationTest
✓ package auto split multi product refund after downward revision targets current replace… 5.87s

Tests: 1 passed (27 assertions)
Duration: 6.01s
```

Proven by this local output:

stale old work item id is rejected for refund after revision
no customer_refunds row is created from stale old work item id
no refund_component_allocations row is created from stale old work item id
current replacement work item id is accepted for selected-row refund
customer_refunds amount is 200000
refund_component_allocations target current replacement components only:
product A service_store_stock_part = 100000
product B service_store_stock_part = 30000
service fee = 70000
refund_component_allocations total = 200000
surplus 50000 is not wrongly refunded through selected-row component refund
old work item id is not used in refund_component_allocations
current replacement work item becomes canceled after refund

Boundary:

This proof covers downward overpaid package multi-product revision selected-row refund only.
This proof does not close exact-paid revision settlement.
This proof does not close report/export.
This proof does not close browser/manual QA.
This proof does not close full focused consolidation or make verify.

Status impact:

Refund boundary after package multi-product revision is PARTIAL GREEN for downward overpaid current replacement row refund.


## Phase 3 Focused Consolidation Proof After Refund Boundary

FOCUSED-CONSOLIDATION-003

Problem / target:

Consolidate the full focused Phase 3 characterization file after adding downward-overpaid package multi-product refund boundary proof.

Local command:

```text
php artisan test tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
```

Local output:

```text
PASS  Tests\Feature\Note\EditTransactionWorkspacePackageAutoSplitCharacterizationTest
✓ edit workspace preloads service store stock package auto split multi product revision    5.95s
✓ admin can submit service store stock package auto split multi product revision           0.15s
✓ package auto split multi product revision reverses old stock and issues replacement sto… 0.11s
✓ package auto split multi product revision rebuilds payment allocations and records unde… 0.10s
✓ package auto split multi product downward revision caps replay and records overpaid set… 0.13s
✓ package auto split multi product refund after downward revision targets current replace… 0.23s

Tests: 6 passed (89 assertions)
Duration: 6.81s
```

Proven by this local output:

edit workspace preload service-store-stock package auto split multi-product revision remains GREEN
admin submit edit/revision package auto split multi-product remains GREEN
inventory reverse/reissue for package multi-product revision remains GREEN
payment allocation rebuild and underpaid settlement remains GREEN
downward overpaid replay cap and overpaid_pending settlement remains GREEN
downward-overpaid selected-row refund boundary remains GREEN
focused Phase 3 characterization file is consolidated at 6 tests / 89 assertions

Boundary:

This focused consolidation does not close exact-paid package multi-product revision settlement.
This focused consolidation does not close report/export after revision.
This focused consolidation does not close browser/manual QA.
This focused consolidation does not close full make verify.

Status impact:

Phase 3 edit/revision service-store-stock package auto split multi-product is stronger after refund boundary proof.
Core focused edit/revision + inventory + payment/settlement base + downward refund boundary is GREEN.
Full lifecycle closure is still not claimed.


Still OPEN
Payment / Settlement

OPEN:

Payment allocation rebuild after package multi-product revision is PARTIAL GREEN for partial-paid underpaid and downward overpaid scenarios.
Settlement record/status after package multi-product revision is PARTIAL GREEN for partial-paid underpaid and downward overpaid scenarios.
Paid/partial-paid/overpaid scenario after revision.
Guarantee no payment amount disappears or doubles.
note_revision_settlements correctness after r002 revision.

Need proof for:

gross total,
carry forward paid,
carry forward refunded if relevant,
net paid,
outstanding,
surplus,
settlement_status.
Refund

OPEN:

Refund boundary after package multi-product revision.
Refund must not be inferred from create/detail proof.
Do not touch refund until payment/settlement revision proof exists.
Report / Export

OPEN:

Reporting/export after package multi-product revision.
Existing report proof from create/detail must not be reused as edit/revision proof.
Need dedicated proof after current revision r002 package multi-product.
Product Snapshot Historical Name

PARTIAL / GAP:

Revision payload includes product snapshot concept.
Need focused proof by mutating catalog product name after revision and asserting historical display/snapshot remains correct.
First-Line Assumption

PARTIAL / GAP:

Update rules were moved to wildcard product lines.
Need focused proof for edit update duplicate product or invalid second product line.
Existing concern: validators historically used first-line assumptions.
Browser / Manual QA

OPEN:

Browser/manual QA not done for this Phase 3 slice.
Full Verify

OPEN:

make verify not run after these Phase 3 changes.
Do not claim full repo green.
Handoff Update

THIS FILE is the handoff update for the current Phase 3 slice.

Need local proof that this file exists and contains the latest focused proof before using it as source in the next session.

Estimated Progress

Phase 3 edit/revision service-store-stock package auto split:

Approximately 75% to 80%.

If counting only preload + submit + inventory, that slice is about 80%+.

Full lifecycle remains lower because payment/settlement/refund/report/export still need proof.

Recommended Next Active Step

Characterize payment/settlement after a paid or partially paid note is revised into service-store-stock package auto split multi-product.

Start with one simple scenario:

Seed note with payment allocation before revision.
Submit package auto split multi-product revision.
Assert payment allocation is captured and rebuilt.
Assert no payment amount disappears or doubles.
Assert note_revision_settlements for r002:
gross total,
carry_forward_paid_rupiah,
carry_forward_refunded_rupiah,
net_paid_rupiah,
outstanding_rupiah,
surplus_rupiah,
settlement_status.

Do not start report/export before payment/settlement proof.

Suggested Inspection Command For Next Session

Run before writing payment/settlement test:

rg -n "note_revision_settlements|NoteReplacementPaymentAllocationReconciler|ClosedPaidNoteEditPaymentSettlementPreviewFeatureTest|RecordSelectedRowsNotePaymentFeatureTest|payment_component_allocations|customer_payments|settlement_status|outstanding_rupiah|surplus_rupiah" \
  app/Application/Note \
  app/Application/Payment \
  app/Adapters/Out \
  tests/Feature/Note \
  tests/Feature/Payment \
  tests/Support
Suggested Focused Test Direction For Next Session

Target file may continue in:

tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php

or a dedicated payment file may be created if repo line-limit / test organization requires it.

Do not decide without inspecting existing payment/settlement test patterns first.

## Phase 3 Payment / Settlement Proof - Partial Paid Underpaid Package Multi-Product

PAYMENT-SETTLEMENT-001

Problem / target:

Characterize payment allocation rebuild and revision settlement after a partially paid note is revised into service-store-stock package auto split multi-product.

Local command:

```text
php artisan test tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
```

Local output:

```text
PASS  Tests\Feature\Note\EditTransactionWorkspacePackageAutoSplitCharacterizationTest
✓ edit workspace preloads service store stock package auto split multi product revision
✓ admin can submit service store stock package auto split multi product revision
✓ package auto split multi product revision reverses old stock and issues replacement stock
✓ package auto split multi product revision rebuilds payment allocations and records underpaid settlement

Tests: 4 passed (49 assertions)
Duration: 6.87s
```

Proven by this focused test:

Partial paid package multi-product revision rebuilds payment_component_allocations against replacement work item rows.
Old work item payment_component_allocations are removed after replacement.
Existing customer_payments row is preserved.
Total replay allocation remains 200000, so payment amount is not lost or doubled in the characterized scenario.
Replacement allocation is distributed across:
product A store-stock part = 100000
product B store-stock part = 60000
service fee residual = 40000
r002 note_revision_settlements is recorded with:
gross_total_rupiah = 300000
carry_forward_paid_rupiah = 200000
carry_forward_refunded_rupiah = 0
net_paid_rupiah = 200000
outstanding_rupiah = 100000
surplus_rupiah = 0
settlement_status = underpaid

Boundary:

This proof covers partial-paid underpaid package multi-product revision only.
It does not prove exact-paid, overpaid/downward, refund boundary, report/export, browser QA, or full verify.


## Phase 3 Payment / Settlement Proof - Downward Overpaid Package Multi-Product

PAYMENT-SETTLEMENT-002

Problem / target:

Characterize downward revision after a fully paid service-store-stock package auto split multi-product note becomes overpaid after replacement total decreases.

Local command:

```text
php artisan test tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
```

Local output:

```text
PASS  Tests\Feature\Note\EditTransactionWorkspacePackageAutoSplitCharacterizationTest
✓ edit workspace preloads service store stock package auto split multi product revision
✓ admin can submit service store stock package auto split multi product revision
✓ package auto split multi product revision reverses old stock and issues replacement stock
✓ package auto split multi product revision rebuilds payment allocations and records underpaid settlement
✓ package auto split multi product downward revision caps replay and records overpaid settlement

Tests: 5 passed (62 assertions)
Duration: 6.48s
```

Proven by this focused test:

Downward package multi-product revision caps rebuilt payment_component_allocations to replacement gross total.
Existing customer_payments row is preserved at 250000.
Replacement allocation total is capped to 200000, so the overpaid amount is not incorrectly allocated into replacement components.
Replacement allocation is distributed across:
product A store-stock part = 100000
product B store-stock part = 30000
service fee residual = 70000
r002 note_revision_settlements is recorded with:
gross_total_rupiah = 200000
carry_forward_paid_rupiah = 250000
carry_forward_refunded_rupiah = 0
net_paid_rupiah = 250000
outstanding_rupiah = 0
surplus_rupiah = 50000
settlement_status = overpaid_pending

Boundary:

This proof covers downward overpaid package multi-product revision only.
It does not prove exact-paid package multi-product revision, refund boundary, report/export, browser QA, or full verify.


Next Session Opening Prompt Should Reference This File

The next session prompt should instruct the assistant to read:

docs/04_lifecycle/handoff/0014_edit_revision_service_store_stock_package_autosplit_phase3_handoff.md

along with the standard rules.

Do not rely only on chat memory.
