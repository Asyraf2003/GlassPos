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


## Phase 3 Payment / Settlement Proof - Exact Paid Package Multi-Product

PAYMENT-SETTLEMENT-003

Problem / target:

Characterize exact-paid payment allocation rebuild and revision settlement after a fully paid note is revised into service-store-stock package auto split multi-product with the same final package total.

Scenario:
- original package total = 250000
- existing payment = 250000
- revision package total = 250000
- revised product A = 100000
- revised product B = 60000
- revised service residual = 90000

Local command:

```text
php artisan test tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php --filter=exact_paid_revision
```

Local output:

```text
PASS  Tests\Feature\Note\EditTransactionWorkspacePackageAutoSplitCharacterizationTest
✓ package auto split multi product exact paid revision records paid settlement             6.10s

Tests: 1 passed (13 assertions)
Duration: 6.27s
```

Proven by this local output:

old payment_component_allocations are removed from the old work item
rebuilt allocation targets replacement work item rows
product A allocation = 100000
product B allocation = 60000
service fee allocation = 90000
replay total = 250000
customer_payments row is preserved at 250000
note_revision_settlements r002:
gross_total_rupiah = 250000
carry_forward_paid_rupiah = 250000
carry_forward_refunded_rupiah = 0
net_paid_rupiah = 250000
outstanding_rupiah = 0
surplus_rupiah = 0
settlement_status = paid

Boundary:

This proof covers exact-paid package multi-product revision settlement only.
This proof does not close report/export.
This proof does not close browser/manual QA.
This proof does not close full focused consolidation after this new test.
This proof does not close full make verify.

Status impact:

Payment/settlement after package multi-product revision is now GREEN for:
partial-paid underpaid
exact-paid paid
downward overpaid_pending


## Phase 3 Focused Consolidation Proof After Exact-Paid Settlement

FOCUSED-CONSOLIDATION-004

Problem / target:

Consolidate the full focused Phase 3 characterization file after adding exact-paid package multi-product revision settlement proof.

Local command:

```text
php artisan test tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php
```

Local output:

```text
PASS  Tests\Feature\Note\EditTransactionWorkspacePackageAutoSplitCharacterizationTest
✓ edit workspace preloads service store stock package auto split multi product revision    6.09s
✓ admin can submit service store stock package auto split multi product revision           0.15s
✓ package auto split multi product revision reverses old stock and issues replacement sto… 0.11s
✓ package auto split multi product revision rebuilds payment allocations and records unde… 0.10s
✓ package auto split multi product downward revision caps replay and records overpaid set… 0.10s
✓ package auto split multi product refund after downward revision targets current replace… 0.21s
✓ package auto split multi product exact paid revision records paid settlement             0.12s

Tests: 7 passed (102 assertions)
Duration: 7.03s
```

Proven by this local output:

edit workspace preload service-store-stock package auto split multi-product revision remains GREEN
admin submit edit/revision package auto split multi-product remains GREEN
inventory reverse/reissue for package multi-product revision remains GREEN
payment allocation rebuild and underpaid settlement remains GREEN
downward overpaid replay cap and overpaid_pending settlement remains GREEN
downward-overpaid selected-row refund boundary remains GREEN
exact-paid paid settlement remains GREEN
focused Phase 3 characterization file is consolidated at 7 tests / 102 assertions

Boundary:

This focused consolidation does not close report/export after revision.
This focused consolidation does not close browser/manual QA.
This focused consolidation does not close full make verify.
This focused consolidation does not claim full lifecycle closure.

Status impact:

Payment/settlement after package multi-product revision is GREEN for partial-paid underpaid, exact-paid paid, and downward overpaid scenarios.
Core focused edit/revision + inventory + payment/settlement + downward refund boundary is GREEN.
Remaining high-value lifecycle gap before full verify is report/export after package multi-product revision.


## Phase 3 Report Dataset Proof - Package Multi-Product Revision

REPORT-EXPORT-001

Problem / target:

Characterize transaction report dataset after service-store-stock package auto split multi-product downward revision.

Bug found during characterization:

Transaction report dataset read the revised gross total correctly.
Existing payment allocation was read correctly.
But outstanding_rupiah became negative on overpaid revision:
gross_transaction_rupiah = 200000
allocated_payment_rupiah = 250000
old outstanding_rupiah = -50000

Root cause:

app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php
outstandingRupiah() returned gross - allocated + refunded without clamping to zero.

Patch:

Clamp outstanding_rupiah to minimum 0.
Overpaid/surplus is not negative receivable.

Changed file:

app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php

New characterization test:

tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Local syntax proof:

text:
No syntax errors detected in app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php

Local focused proof:

Command:
php artisan test tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Output:
PASS Tests\Feature\Reporting\PackageAutoSplitRevisionReportImpactFeatureTest
✓ transaction report reads current total after package multi product downward revision

Tests: 1 passed (12 assertions)
Duration: 6.09s

Local regression proof:

Command:
php artisan test \
tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php \
tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php \
tests/Feature/Reporting/PackageAutoSplitCreateReportImpactFeatureTest.php

Output:
PASS Tests\Feature\Reporting\GetTransactionReportDatasetFeatureTest
✓ transaction report dataset returns single exact dataset from summary rows
✓ transaction report dataset distinguishes refund due surplus refund paid and remaining refund due

PASS Tests\Feature\Reporting\TransactionSummaryReportingQueryFeatureTest
✓ it reads summary from cash records
✓ transaction summary uses cash records when component allocations are rebuilt after refund
✓ downward revision surplus reporting uses capped allocations not customer payment gross

PASS Tests\Feature\Reporting\PackageAutoSplitCreateReportImpactFeatureTest
✓ package auto split create output is visible to transaction and profit reports

Tests: 6 passed (70 assertions)
Duration: 6.28s

Proven:

transaction report reads current revised notes.total_rupiah = 200000
allocated_payment_rupiah remains 250000
overpaid downward revision does not produce negative outstanding
outstanding_rupiah is clamped to 0
existing transaction report dataset tests remain GREEN
existing transaction summary query tests remain GREEN
existing package auto split create report impact test remains GREEN

Boundary:

This proof covers transaction report dataset only.
This proof does not close PDF export.
This proof does not close Excel export.
This proof does not close transaction cash ledger export.
This proof does not close browser/manual QA.
This proof does not close full make verify.

Status impact:

Transaction report dataset after package multi-product revision is PARTIAL GREEN.
Report/export lifecycle is still not fully closed because PDF/Excel export and browser/manual QA remain open.


## Phase 3 Report Export HTTP Proof - Package Multi-Product Revision

REPORT-EXPORT-002

Problem / target:

Characterize transaction report PDF and Excel export HTTP routes after service-store-stock package auto split multi-product downward revision.

Scenario:

Original note total = 250000.
Existing payment = 250000.
Downward revision package total = 200000.
Revision settlement becomes overpaid_pending with surplus 50000.
Transaction report dataset already proved:
gross_transaction_rupiah = 200000
allocated_payment_rupiah = 250000
outstanding_rupiah = 0

New export characterization:

tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Added test:

test_transaction_report_exports_after_package_multi_product_downward_revision_return_files

Routes covered:

admin.reports.transaction_summary.export_excel
admin.reports.transaction_summary.export_pdf

Local syntax proof:

Command:
php -l tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Output:
No syntax errors detected in tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Local focused proof:

Command:
php artisan test tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Output:
PASS Tests\Feature\Reporting\PackageAutoSplitRevisionReportImpactFeatureTest
✓ transaction report reads current total after package multi product downward revision
✓ transaction report exports after package multi product downward revision return files

Tests: 2 passed (20 assertions)
Duration: 6.62s

Proven:

transaction report dataset after package multi-product downward revision remains GREEN
Excel transaction report export route returns HTTP 200
Excel transaction report export returns spreadsheet content type
Excel transaction report export returns expected laporan-transaksi filename
PDF transaction report export route returns HTTP 200
PDF transaction report export returns application/pdf content type
PDF transaction report export returns expected laporan-transaksi filename
PDF transaction report response starts with %PDF

Boundary:

This proof covers transaction summary PDF and Excel export HTTP responses only.
This proof does not close transaction cash ledger export.
This proof does not close browser/manual QA.
This proof does not close full make verify.
This proof does not prove downloaded file visual formatting beyond content type, filename, and PDF signature.

Status impact:

Transaction report dataset and transaction summary PDF/Excel export after package multi-product revision are GREEN in focused proof.
Report/export lifecycle is PARTIAL GREEN because transaction cash ledger export and browser/manual QA remain open.


## Phase 3 Cash Ledger Export HTTP Proof - Package Multi-Product Revision

REPORT-EXPORT-003

Problem / target:

Characterize transaction cash ledger PDF and Excel export HTTP routes after service-store-stock package auto split multi-product downward revision.

Context:

Transaction summary dataset and transaction summary PDF/Excel export were already GREEN.
Cash ledger export uses a different route/controller/use case path:
admin.reports.transaction_cash_ledger.export_excel
admin.reports.transaction_cash_ledger.export_pdf

Scenario reused:

Original note total = 250000.
Existing payment = 250000.
Downward revision package total = 200000.
Revision settlement becomes overpaid_pending with surplus 50000.

Updated characterization test:

tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Updated test:

test_transaction_report_exports_after_package_multi_product_downward_revision_return_files

Local syntax proof:

Command:
php -l tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Output:
No syntax errors detected in tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Local focused proof:

Command:
php artisan test tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php

Output:
PASS Tests\Feature\Reporting\PackageAutoSplitRevisionReportImpactFeatureTest
✓ transaction report reads current total after package multi product downward revision
✓ transaction report exports after package multi product downward revision return files

Tests: 2 passed (27 assertions)
Duration: 6.80s

Proven:

transaction report dataset after package multi-product downward revision remains GREEN
transaction summary Excel export remains GREEN
transaction summary PDF export remains GREEN
cash ledger Excel export route returns HTTP 200
cash ledger Excel export returns spreadsheet content type
cash ledger Excel export returns expected laporan-buku-kas-transaksi filename
cash ledger PDF export route returns HTTP 200
cash ledger PDF export returns application/pdf content type
cash ledger PDF export returns expected laporan-buku-kas-transaksi filename
cash ledger PDF response starts with %PDF

Boundary:

This proof covers transaction summary dataset/export and transaction cash ledger export HTTP responses.
This proof does not close browser/manual QA.
This proof does not close full make verify.
This proof does not prove downloaded file visual formatting beyond content type, filename, and PDF signature.

Status impact:

Report/export after package multi-product revision is focused GREEN for transaction summary dataset, transaction summary PDF/Excel export, and transaction cash ledger PDF/Excel export.
Remaining lifecycle gaps before full closure are browser/manual QA and full make verify.


## Phase 3 Line Limit Refactor and Full Automated Verify

LINE-LIMIT-001

Problem / target:

make verify failed after report/export proof because audit-lines found one file over the 100 line limit.

Failure output:

ERROR: File berikut melebihi limit 100 baris tanpa label bypass:
- [104 lines] app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php

Patch:

Extracted revision payload work item loop into a dedicated builder.

Changed files:

app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php
app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php
tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php

Local syntax proof:

Command:
php -l app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php
php -l app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php
php -l tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php

Output:
No syntax errors detected in app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php
No syntax errors detected in app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php
No syntax errors detected in tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php

Line-count proof:

Command:
wc -l \
  app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php \
  app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php

Output:
69 app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php
49 app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php
118 total

Local unit proof:

Command:
php artisan test tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php

Output:
PASS Tests\Unit\Application\Note\UseCases\CreateNoteRevisionPayloadNoteBuilderTest
✓ it accepts server trusted historical revision snapshot below current catalog price
✓ it rejects forged revision snapshot below current catalog price

Tests: 2 passed (4 assertions)
Duration: 0.13s

Proven:

CreateNoteRevisionPayloadNoteBuilder is now below the 100-line audit limit.
New CreateNoteRevisionPayloadWorkItemBuilder is below the 100-line audit limit.
Revision payload builder unit coverage remains GREEN.
No bypass label was added.

FULL-VERIFY-001

Problem / target:

Run full automated verification after Phase 3 package multi-product revision, report/export proof, and line-limit refactor.

Command:
make verify

Final local output:
Tests: 2 skipped, 1140 passed (6466 assertions)
Duration: 81.71s

Proven:

Full automated test suite completed with 1140 passing tests and 2 skipped tests.
No failing test is visible in the final local output.
The earlier line-limit blocker was removed before this full verify reached the test suite.

Boundary:

This proof covers automated verification only.
This proof does not close browser/manual QA.
This proof does not prove production deploy state.
This proof does not prove remote connector state.
This proof does not prove visual formatting of downloaded report files beyond focused HTTP export assertions.

Status impact:

Phase 3 focused automated lifecycle is GREEN for:
edit/revision package multi-product preload and submit
inventory reversal/reissue
payment allocation rebuild and settlement
downward refund boundary
transaction summary dataset
transaction summary PDF/Excel export HTTP
transaction cash ledger PDF/Excel export HTTP
line-limit audit blocker
full automated make verify

Remaining lifecycle gap:
browser/manual QA.


Still OPEN
Payment / Settlement

OPEN:

Payment allocation rebuild after package multi-product revision is GREEN for partial-paid underpaid, exact-paid paid, and downward overpaid scenarios.
Settlement record/status after package multi-product revision is GREEN for partial-paid underpaid, exact-paid paid, and downward overpaid scenarios.
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
