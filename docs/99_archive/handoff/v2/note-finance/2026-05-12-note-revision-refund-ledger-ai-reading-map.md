
AI Reading Map for Note Revision Refund Ledger

Status: Draft locked for design review
Date: 2026-05-12
Scope: minimum reading map for AI or developer working on serious note edit, revision, refund, settlement, inventory, reporting version mode, UI, and future API

1. Purpose

This file tells the next AI or developer what to read first.

The goal is to avoid reading every old handoff and inheriting legacy confusion.

Read the minimum relevant source, then prove current source reality with local commands.

2. Source Priority

Use this priority:

Local command output
Current source code
Latest ADR or blueprint nearest to the domain
Error log with proof
Handoff with proof
Older docs
Memory or assumption

If source and docs conflict, source wins until docs are updated.

3. Mandatory First Read

Read these first for this initiative:

docs/03_blueprints/v2/note-finance/2026-05-12-note-revision-refund-ledger-blueprint.md
docs/03_blueprints/v2/note-finance/2026-05-12-note-revision-refund-ledger-workflow.md
docs/03_blueprints/v2/note-finance/2026-05-12-note-revision-refund-ledger-dod.md
docs/02_architecture/adr/0018-note-revision-settlement-external-product-lifecycle.md
docs/02_architecture/adr/2026-05-04-note-revision-carry-forward-settlement.md
docs/03_blueprints/v2/note-finance/2026-05-06-error-log-finance-residual-implementation-blueprint.md
docs/04_lifecycle/error_log/005-note-revision-silently-drops-overpaid-allocations.md
docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
docs/02_architecture/adr/0021-note-detail-hybrid-versioning-billing-refund.md
4. Current Revision Source Files

Read when touching revision or edit:

database/migrations/2026_04_22_000001_create_note_revisions_table.php
database/migrations/2026_04_22_000002_create_note_revision_lines_table.php
database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php
app/Core/Note/Revision/NoteRevision.php
app/Core/Note/Revision/NoteRevisionLineSnapshot.php
app/Adapters/Out/Note/DbNoteRevisionPayloadCodec.php
app/Adapters/Out/Note/DbNoteRevisionRowMapper.php
app/Adapters/Out/Note/DbNoteRevisionLineRowMapper.php
app/Adapters/Out/Note/Concerns/WritesNoteRevisionRecords.php
app/Adapters/Out/Note/Concerns/QueriesNoteRevisionRecords.php
app/Application/Note/UseCases/CreateNoteRevisionHandler.php
app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php
app/Application/Note/UseCases/CreateNoteRevisionCommitter.php
app/Application/Note/UseCases/CreateNoteRevisionAuditPayloadBuilder.php
app/Application/Note/Services/NoteCurrentRevisionResolver.php
app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php
app/Application/Note/Services/NoteRevisionWorkspaceExistingItemMapper.php
5. Dangerous Delete and Rebuild Files

Read before touching revision commit, payment replay, or work item replacement:

app/Adapters/Out/Note/WorkItemDeletesTrait.php
app/Adapters/Out/Payment/DatabasePaymentComponentAllocationWriterAdapter.php
app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php
app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php
app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php
app/Application/Payment/Services/AllocatePaymentAcrossComponents.php

Known risk:

Work item delete is physical for deletable rows.
Refund linked work items are protected.
Payment linked but not refunded work items are not proven protected.
Payment component allocations can be deleted by note id.
Delete and rebuild must be treated as projection path unless historical ledger is proven safe.
6. Refund Files

Read before touching refund:

app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php
app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php
app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php
app/Application/Note/Services/SelectedNoteRowsRefundEligibilityGuard.php
app/Application/Note/Services/SelectedRowsRefundBucketsBuilder.php
app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php
app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php
app/Application/Payment/Services/RecordCustomerRefundOperation.php
app/Application/Payment/Services/AllocateRefundAcrossComponents.php
app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php
app/Application/Inventory/Services/RefundedStoreStockComponentTargets.php
app/Application/Note/Services/RefundImpactPayloadBuilder.php

Known risk:

Current refund flow is selected row oriented.
Money effect and stock effect are not yet fully separated.
Zero money cancellation must be supported if domain allows it.
External purchase refund must not be guessed.
7. Projection and Report Files

Read before touching reporting or projection:

database/migrations/2026_04_19_100100_create_note_history_projection_table.php
app/Application/Note/Services/NoteHistoryProjectionService.php
app/Adapters/Out/Note/DatabaseNoteHistoryProjectionSourceReaderAdapter.php
app/Adapters/Out/Note/DatabaseNoteHistoryProjectionWriterAdapter.php
app/Adapters/Out/Note/Queries/NoteHistoryAggregationSubqueries.php
app/Adapters/Out/Note/Queries/NoteHistoryComponentLineSummarySubquery.php
app/Adapters/Out/Note/Queries/NoteHistoryLegacyLineSummarySubquery.php
app/Adapters/Out/Reporting/DatabaseTransactionReportingSourceReaderAdapter.php
app/Adapters/Out/Reporting/DatabaseOperationalProfitReportingSourceReaderAdapter.php
app/Adapters/Out/Reporting/DatabaseTransactionCashLedgerReportingSourceReaderAdapter.php
app/Adapters/Out/Reporting/DatabaseInventoryMovementReportingSourceReaderAdapter.php

Known risk:

Current projection is fast current read model.
It is not full historical version report source.
Report mode must be explicit before report switch feature.
Current projection must not hide overpaid as unpaid.
8. UI Files

Read only after backend plan and use case are stable:

resources/views/cashier/notes/workspace/create.blade.php
resources/views/cashier/notes/workspace/partials/refund-modal.blade.php
resources/views/cashier/notes/partials/refund-modal.blade.php
resources/views/cashier/notes/partials/refund-form.blade.php
public/assets/static/js/pages/cashier-note-refund.js
public/assets/static/js/pages/cashier-note-workspace/rows.js
public/assets/static/js/pages/cashier-note-workspace/search.js
public/assets/static/js/pages/cashier-note-workspace/summary.js
public/assets/static/js/pages/cashier-note-workspace/payment-flow.js
public/assets/static/js/pages/cashier-note-workspace/boot.js

Rule:

Do not start from UI.

UI must render backend truth.

9. Test Files to Inventory

Start with these test families:

tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php
tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php
tests/Feature/Note/CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php
tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php
tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php
tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php
tests/Feature/Payment/RecordSelectedRowsNotePaymentFeatureTest.php
tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php
tests/Unit/Application/Note/Services/RevisionWorkspace/RevisionSnapshotStoreStockLineTrustMarkerTest.php
tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php
tests/Unit/Application/Payment/Services/AllocateRefundAcrossComponentsTest.php

Add report tests when report mode is touched.

Add UI tests when UI is touched.

10. Docs That May Be Legacy or Context Only

These may contain useful history but must not override current source or latest blueprint:

Older UI handoffs
Older refund UI failure handoffs
Older note open close refund handoffs
Older error remediation handoffs after source has moved
Pseudo versioning handoff if contradicted by current revision source

Use them only to understand why decisions were made.

Do not use them as final truth without current source proof.

11. Minimum Command Before Any Implementation

Before implementation, run a targeted source inventory for the active slice.

Required output should include:

git status
relevant migrations
relevant core files
relevant application files
relevant adapters
relevant tests
grep anchors for dangerous methods
no unrelated dirty files or explicit dirty file explanation
12. Required Response Shape For Next AI

Every implementation response must include:

FACT
GAP
DECISION
ACTIVE STEP
FILES TO TOUCH
FILES NOT TO TOUCH
COMMAND
EXPECTED PROOF
NEXT

No implementation may proceed without this shape.

