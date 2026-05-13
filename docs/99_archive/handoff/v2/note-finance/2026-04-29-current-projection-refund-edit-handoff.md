# Handoff: Note Finance Current Projection, Edit, And Refund

## Project

Laravel kasir or bengkel app.

Current branch from last proven baseline:

~~~text
audit-1461-selective-patch
~~~

Last proven HEAD from user output:

~~~text
3c0b0f6e
~~~

## Working Mode

Mandatory:

~~~text
- blueprint-first
- evidence-driven
- zero assumption
- one active step per response
- command output user is highest source of truth
- do not claim test pass without pasted output
- do not touch stash unless user explicitly asks
- do not rewrite locked domain terms without evidence and decision
~~~

## Current Dirty State

Known untracked file:

~~~text
tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
~~~

Known stash:

~~~text
stash@{0}: On audit-1461-selective-patch: temp-ui-refund-label-outside-audit
~~~

Do not touch stash.

## Documents Created By This Handoff Step

Expected files:

~~~text
docs/blueprint/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md
docs/adr/2026-04-29-note-current-projection-and-current-only-refund.md
docs/handoff/v2/note-finance/2026-04-29-current-projection-refund-edit-handoff.md
~~~

## Parent Blueprint

Read first:

~~~text
docs/blueprint/v2/note-finance/2026-04-29-note-finance-stabilization-blueprint.md
~~~

## Current Root Bug

A characterization test has already proven the root bug.

Test file:

~~~text
tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
~~~

Observed failure:

~~~text
SQLSTATE[23000]: Integrity constraint violation: 1451
Cannot delete or update a parent row
fk_rca_work_item
refund_component_allocations.work_item_id -> work_items.id
SQL: delete from work_items where id in (wi-refund-revision-old-1)
~~~

Stack path:

~~~text
StoreNoteRevisionController
CreateNoteRevisionHandler
ApplyNoteRevisionAsActiveReplacement
UpdateTransactionWorkspaceWorkItemPersister
WorkItemDeletesTrait::deleteByNoteId
~~~

## Current Design Problem

work_items currently plays two conflicting roles:

~~~text
1. current operational row
2. historical or ledger anchor
~~~

Revision currently tries to physical delete old work_items.

This is invalid once those rows are referenced by payment, refund, inventory, or audit tables.

## Locked Product Decision

User wants:

~~~text
- Edit button always available on note page.
- Refund button always available on note page.
- Every edit means new calculation.
- Current note must be easy for cashier users.
- Old versions become legacy or history.
- Old versions do not participate in current calculation.
- Refund new action only targets current active version.
- Existing old refund events remain valid ledger.
~~~

## Accepted Direction

Hybrid C+:

~~~text
Immutable ledger and history + current projection table.
~~~

Meaning:

~~~text
current note page -> current projection
edit workspace -> current projection
new refund selection -> current projection
ledger report -> payment, refund, and inventory events
audit/history -> revisions + legacy rows + event anchors
~~~

## Rejected Final Solutions

Do not implement these as final design:

~~~text
- cascade delete financial history
- nullable FK to bypass lifecycle bug
- rewrite old refund allocation to new work_item
- skip delete referenced rows without current projection boundary
- allow new refund from legacy or historical rows
~~~

## Implementation Direction

### Phase 1 - Current State

Already done:

~~~text
- parent blueprint committed at 3c0b0f6e
- root characterization test created
- root FK failure reproduced
- work_items reader audit run
~~~

### Phase 2 - Documentation Lock

Create and commit:

~~~text
docs/blueprint/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md
docs/adr/2026-04-29-note-current-projection-and-current-only-refund.md
docs/handoff/v2/note-finance/2026-04-29-current-projection-refund-edit-handoff.md
tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
~~~

Commit message suggestion:

~~~text
Document note current projection decision
~~~

### Phase 3 - Projection Design

Do not jump straight into implementation.

First inspect local current readers:

~~~text
DatabaseNoteReaderAdapter
DatabaseNoteActiveWorkItemFilter
DatabaseNoteWorkItemDetailLoader
SelectedActiveWorkItemsResolver
EditTransactionWorkspacePageDataBuilder
NoteDetailPageDataBuilder
NoteRefundPaymentOptionsBuilder
NoteBillingProjectionBuilder
reporting queries using work_items
dashboard queries using work_items
history queries using work_items
~~~

Design candidate projection:

~~~text
note_current_lines
~~~

Candidate data:

~~~text
id
note_id
source_revision_id
source_work_item_id or source_revision_line_id
line_no
transaction_type
status
subtotal_rupiah
payload json or normalized child projection tables
created_at
updated_at
~~~

Do not finalize schema without local snapshot and option evaluation.

### Phase 4 - Write Path

Revision should:

~~~text
- create revision
- preserve old history
- rebuild current projection
- update note total from projection
- keep old payment, refund, and inventory anchors
- create required inventory event adjustments
- commit atomically
~~~

### Phase 5 - Reader Migration

Current readers must read projection.

History or audit readers may read legacy/versioned rows.

Reporting must be split by purpose:

~~~text
current operational report -> projection
ledger or historical report -> event tables
~~~

### Phase 6 - Verification

Minimum tests:

~~~text
php artisan test tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
php artisan test tests/Feature/Payment/RecordCustomerRefundFeatureTest.php
php artisan test tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php
php artisan test tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php
~~~

Final required:

~~~text
make verify
~~~

Do not claim done without output.

## Known Gaps

~~~text
- projection table schema not yet decided
- migration not created
- ports/adapters not created
- current reader migration not done
- reporting current-vs-ledger split not implemented
- old replacement tests still assert old work_items missing
- red test not committed yet
- make verify not run
~~~

## Safe Next Active Step

After this documentation step:

~~~text
1. Verify files exist.
2. Commit documentation + red characterization test.
3. Then inspect current reader classes before migration design.
~~~

Recommended command:

~~~bash
git status --short
git add \
  docs/blueprint/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md \
  docs/adr/2026-04-29-note-current-projection-and-current-only-refund.md \
  docs/handoff/v2/note-finance/2026-04-29-current-projection-refund-edit-handoff.md \
  tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
git diff --cached --name-only
git diff --cached --stat
git commit -m "Document note current projection decision"
git status --short
git log --oneline -5
~~~

## New Session Opening Prompt

Use this in the next chat:

~~~text
Kita lanjut dari repo Laravel kasir/bengkel branch audit-1461-selective-patch.

Baca dulu:
docs/AI_RULES/00_INDEX.md
docs/AI_RULES/01_DECISION_POLICY.md
docs/blueprint/v2/note-finance/2026-04-29-note-finance-stabilization-blueprint.md
docs/blueprint/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md
docs/adr/2026-04-29-note-current-projection-and-current-only-refund.md
docs/handoff/v2/note-finance/2026-04-29-current-projection-refund-edit-handoff.md

Locked decision:
Hybrid C+ current projection.
Edit always available.
Refund always available but only for current active projection.
Legacy rows are audit/history only.
Old refund/payment/inventory events remain immutable ledger.

Current proof:
Red test exists at tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php.
It currently fails with FK fk_rca_work_item because revision deletes old work_items referenced by refund_component_allocations.

Rules:
zero assumption, one active step, no patch before local snapshot, decision gate for projection schema options.
~~~

## Session Context Health

Risk estimate:

~~~text
75%
~~~

Reason:

~~~text
Large domain decision, red test, reader audit, projection strategy, and future migration design are now in context.
Next large implementation should start from this handoff.
~~~
