# Note Revision Refund Ledger Blueprint

## Status
Draft locked for design review.

## Metadata
- Date: 2026-05-12
- Scope: HyperPOS note edit, note revision, refund, settlement carry forward, customer balance, inventory reversal, reporting version mode, UI and future API boundary

1. Purpose

This blueprint defines the strict architecture direction for serious note edit, revision, refund, settlement, inventory, and reporting versioning.

This document is not an implementation patch list.

This document is the rule of engagement before implementation.

No production file may be created or edited under this initiative until the active slice has:

Clear final goal
Explicit domain decision
Exact affected file map
Existing source proof
DB impact decision
Hexagonal boundary plan
Test plan
Rollback or containment plan
Residual gap statement

The goal is to prevent quick fixes that only hide financial complexity in UI, JSON payloads, active rows, or rebuild logic.

2. Final Goal

HyperPOS must support note edit and refund as a serious financial lifecycle.

The system must be able to:

Preserve immutable note revision history
Preserve old note line history
Carry forward previous payments correctly
Carry forward previous refunds correctly
Represent underpaid, paid, and overpaid states explicitly
Represent kembalian, refund due, and customer credit as DB backed domain state
Keep inventory movements traceable across edit and refund
Keep cost and price basis traceable across historical dates
Support current report and historical report without mixing semantics
Support future API using the same application use cases as Blade UI
Keep current projection fast but derived, not historical truth
Make every sensitive change auditable by actor, role, reason, event type, before state, after state, and affected ledger ids
3. Architecture Decision

The locked architecture direction is:

Ledger plus Revision Snapshot plus Current Projection.

Meaning:

Note revisions are immutable business snapshots.
Work items are current operational rows or active projection, not final historical truth.
Payment and refund records are financial ledger events.
Inventory movements are stock ledger events.
Customer balance entries are required for surplus, refund due, and customer credit.
Current note history projection is a fast read model.
Reporting must use explicit version mode.
UI and API are transport adapters only.
Domain policy lives in core or application services, not Blade, JavaScript, controller, or raw SQL query branches.

This direction intentionally avoids two unsafe extremes:

Full event sourcing rewrite of the whole system in one jump
Current state overwrite with snapshots as afterthought

The system must evolve through additive ledger and projection hardening.

4. Current Source Reality

Current proven source shape:

note_revisions exists.
note_revision_lines exists.
notes has current_revision_id.
notes has latest_revision_number.
NoteRevision core object exists.
NoteRevisionLineSnapshot core object exists.
DbNoteRevisionRepository exists.
DbNoteRevisionPayloadCodec only encodes and decodes JSON.
CreateNoteRevisionCommitter persists revision, sets current revision, and records minimal audit.
WorkItemDeletesTrait physically deletes deletable work items and child lines.
WorkItemDeletesTrait protects only work items referenced by refund component allocations.
DatabasePaymentComponentAllocationWriterAdapter can delete all payment component allocations by note id.
Downward overpaid revision is currently contained by reject and rollback behavior, not final supported overpaid workflow.
UI and docs already contain refund and revision complexity, but final ledger model is not completed.

Conclusion:

The current system is revision aware current state architecture.

It is not yet full immutable financial versioning architecture.

5. Source Priority

When documents conflict, use this priority:

Local command output
Current source code
Latest ADR or blueprint nearest to the domain
Error log with proof
Handoff with proof
Older handoff or legacy docs
General memory or assumption

If source and document disagree, source proof wins until the document is updated.

If source proof is incomplete, mark GAP and stop before implementation.

6. Non Negotiable Rules
6.1 No implementation before decision lock

Before editing production code, the active slice must state:

Goal
Decision
Source proof
Files to touch
Files not to touch
DB impact
Expected invariant
Test command
Rollback risk
Residual gap
6.2 No UI only financial truth

Blade and JavaScript may display and assist.

Blade and JavaScript must not decide final financial amount, refund amount, stock return, customer credit, or report truth.

Final truth must come from application use case and domain services.

6.3 No silent overwrite of financial history

Physical delete and rebuild are allowed only for current operational projection if immutable snapshot or ledger has already preserved the historical truth.

Any delete path touching note, work item, payment allocation, refund allocation, inventory movement, customer balance, or report source must be treated as high risk.

6.4 No hidden surplus

If carried forward paid amount is greater than the revised total, the surplus must not disappear.

The surplus must become explicit domain state:

Overpaid pending
Refund due
Customer credit
Refund paid
Credit used

Reject and rollback is acceptable only as temporary containment before explicit overpaid model exists.

6.5 No uncontrolled raw JSON domain

JSON payload may store flexible line details.

But money, cost, inventory effect, price basis, settlement, lifecycle, and report critical fields must have either:

First class columns
Strict payload contract with validation and tests
Derived ledger entries that make the JSON non critical for financial truth
6.6 No direct report query without mode

Report reads must explicitly declare the selected report mode:

current
original
revision
as of date
ledger actual

A report query that silently reads current projection while the caller expects history is invalid.

6.7 No cross boundary shortcuts

Core must not depend on Laravel, DB facade, request, session, auth, or Blade.

Application may orchestrate domain services through ports.

Adapters may use DB, HTTP, Laravel, and framework details.

Controllers and FormRequests are transport only.

JavaScript is interaction only.

6.8 No fixed claim without proof

A slice is not fixed until it has:

Source map
RED proof or documented source gap
Minimal production patch
Targeted GREEN proof
Focused blast radius proof
Projection or report proof if affected
UI or API proof if affected
Docs update
Residual gap list
7. Domain Areas
7.1 Note Revision

Responsibility:

Version number
Parent revision
Actor
Actor role
Reason
Header snapshot
Line snapshot
Transaction date
Revision created time
Effective date
Price basis
Cost basis
Settlement snapshot
Report visibility

Existing files:

database/migrations/2026_04_22_000001_create_note_revisions_table.php
database/migrations/2026_04_22_000002_create_note_revision_lines_table.php
database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php
app/Core/Note/Revision/NoteRevision.php
app/Core/Note/Revision/NoteRevisionLineSnapshot.php
app/Adapters/Out/Note/DbNoteRevisionPayloadCodec.php
app/Adapters/Out/Note/DbNoteRevisionRepository.php
app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php
app/Application/Note/UseCases/CreateNoteRevisionCommitter.php
app/Application/Note/Services/NoteCurrentRevisionResolver.php
app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php
app/Application/Note/Services/NoteRevisionWorkspaceExistingItemMapper.php

Current gap:

Settlement is not first class in revision.
Cost basis is not proven first class.
Inventory effect is not proven first class.
Payload codec has no schema validation.
Actor role is not proven in revision.
Change type is not proven in revision.
Effective date is not proven in revision.

Decision:

Existing note_revisions and note_revision_lines stay as revision snapshot foundation.

They should be extended by additive tables instead of replaced.

7.2 Settlement Carry Forward

Responsibility:

Previous paid amount
Previous refunded amount
Net paid amount
Revised total
Outstanding amount
Surplus amount
Settlement status
Source revision id
Source payment ids
Source refund ids

Required new concept:

Note Revision Settlement.

Recommended table:

note_revision_settlements

Recommended fields:

id
note_revision_id
note_root_id
gross_total_rupiah
carry_forward_paid_rupiah
carry_forward_refunded_rupiah
net_paid_rupiah
outstanding_rupiah
surplus_rupiah
settlement_status
created_at

Allowed settlement statuses:

underpaid
paid
overpaid_pending
refund_due
customer_credit_pending

Rules:

Revised total equal to carried money keeps paid state.
Revised total greater than carried money creates outstanding.
Revised total lower than carried money creates surplus.
Surplus must not be dropped.
Surplus must not be represented only as UI text.
7.3 Customer Balance Ledger

Responsibility:

Overpaid pending
Refund due
Customer credit
Refund paid
Credit used
Manual adjustment if later approved
Remaining balance
Trace to note revision or refund event

Required new concept:

Customer Balance Entry.

Recommended table:

customer_balance_entries

Recommended fields:

id
customer_key
note_id
note_revision_id
source_type
source_id
entry_type
amount_rupiah
remaining_rupiah
reason
actor_id
actor_role
occurred_at
created_at

Allowed source types:

note_revision
customer_refund
customer_credit_use
manual_adjustment

Allowed entry types:

overpaid_pending
refund_due
customer_credit
refund_paid
credit_used
adjustment

Rules:

Customer balance is not note status text.
Customer balance is not payment allocation row.
Customer balance is an auditable ledger.
Customer balance can be partially consumed.
Customer balance can be partially refunded.
Customer balance must be reportable.
7.4 Refund and Reversal Engine

Responsibility:

Select rows or components
Validate eligibility
Build refund plan
Split effects
Commit money effect
Commit stock effect
Commit receivable effect
Commit service effect
Commit external procurement effect
Commit customer balance effect
Sync projection
Write audit

Existing files:

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

Target use cases:

PreviewSelectedRowsRefundPlan
CommitSelectedRowsRefundPlan
PreviewRefundEffectPlan
CommitRefundEffectPlan

Rules:

Refund money and stock return are separate effects.
Refunding money does not always mean stock returns.
Returning stock does not always mean money refund.
Canceling unpaid row can be zero money effect.
External purchase refund must consider procurement state.
Double refund must be rejected.
Refund plan must be computed by backend.
7.5 Inventory and COGS

Responsibility:

Stock out for original sale
Stock return for refund
Stock reversal for revision
New stock out for revised line
Unit cost snapshot
COGS snapshot
Report mode compatibility
Trace to revision line or refund effect

Existing files:

app/Application/Inventory/Services/IssueInventoryOperation.php
app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php
app/Application/Note/Services/ReverseIssuedInventoryByNoteService.php
app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php
app/Adapters/Out/Inventory/DatabaseInventoryMovementReaderAdapter.php
app/Adapters/Out/Inventory/DatabaseInventoryMovementWriterAdapter.php
app/Adapters/Out/Reporting/DatabaseInventoryMovementReportingSourceReaderAdapter.php

Decision:

Inventory movements remain stock ledger.

However, revision and refund flows must create traceable source types and source ids.

If inventory_movements cannot represent all revision and refund effects clearly, add note_revision_inventory_effects or note_financial_inventory_effects.

Rules:

No stock mutation without ledger movement.
No revision stock effect without revision or effect reference.
No refund stock effect without refund or effect reference.
Historical COGS must not be recalculated from current average cost.
If exact historical cost is unavailable, mark legacy estimate or unknown source explicitly.
7.6 Report Version Mode

Responsibility:

Current report
Original report
Specific revision report
As of date report
Ledger actual report
PDF and Excel export consistency
Dashboard consistency if included

Potential new concepts:

ReportVersionMode
VersionedTransactionReportQuery
NoteReportVersionReaderPort
TransactionReportVersionedSourceReaderPort
OperationalProfitVersionedSourceReaderPort

Existing affected areas:

app/Adapters/Out/Reporting/DatabaseTransactionReportingSourceReaderAdapter.php
app/Adapters/Out/Reporting/DatabaseOperationalProfitReportingSourceReaderAdapter.php
app/Adapters/Out/Reporting/DatabaseTransactionCashLedgerReportingSourceReaderAdapter.php
app/Adapters/Out/Reporting/DatabaseInventoryMovementReportingSourceReaderAdapter.php
app/Application/Reporting/DTO/TransactionReportPageQuery.php
app/Application/Reporting/DTO/OperationalProfitReportPageQuery.php
app/Application/Reporting/Exports/*
resources/views/admin/reporting/*

Rules:

Every report dataset must declare version mode.
Export output must show selected version mode.
Current projection is valid only for current mode.
Revision mode must read revision snapshot and related settlement snapshot.
Ledger actual mode must read actual payment, refund, and inventory movement ledgers.
As of mode must use event time or effective date consistently.
7.7 UI and API Boundary

Responsibility:

Render plan
Collect user intent
Send command
Display result
Display validation errors
Never decide financial truth

Existing UI files:

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

Future API must share the same application services.

Target transport adapters:

Blade controller
API controller
Mobile API controller if needed

Shared use cases:

PreviewNoteRevisionPlan
CommitNoteRevisionPlan
PreviewRefundPlan
CommitRefundPlan
GetNoteVersionTimeline
GetNoteVersionDetail
GetVersionedReportDataset

Rules:

FormRequest validates shape only.
Controller resolves actor and route area only.
Application service handles business rule.
Domain service handles invariant.
Adapter persists.
Presenter formats.
8. Dangerous Existing Paths

These files are high risk and must not be changed casually:

app/Adapters/Out/Note/WorkItemDeletesTrait.php
app/Adapters/Out/Payment/DatabasePaymentComponentAllocationWriterAdapter.php
app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php
app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php
app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php
app/Application/Payment/Services/AllocatePaymentAcrossComponents.php
app/Application/Payment/Services/AllocateRefundAcrossComponents.php
app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php
app/Application/Note/Services/NoteHistoryProjectionService.php

Reason:

They can delete or rebuild current operational state.
They can change settlement allocation.
They can change inventory.
They can change projected reports.
They can hide or expose financial defects.
9. Implementation Strategy

The implementation must move from the deepest financial truth outward.

Correct order:

Decision lock
Source audit
DB additive foundation
Domain contract
Application use case
Adapter
Projection
Report source
UI
API
Docs and final audit

Do not start with UI.

Do not start with report export.

Do not start with JavaScript.

Do not patch delete behavior before proving snapshot and ledger safety.

10. Required New or Extended DB Concepts

Recommended additions:

note_revision_settlements
customer_balance_entries
optional note_revision_inventory_effects
optional note_report_snapshots
optional report_version_cache

Do not add all tables blindly.

Add only after proving the active slice needs them.

11. Minimum Target Invariants
11.1 Revision invariants
Every committed revision has parent or is initial revision.
Current revision pointer points to a persisted revision.
Revision number only increases.
Old revision remains readable.
Revision lines remain readable after current work items change.
Revision reason is never empty for sensitive edit.
Revision actor is captured.
Revision settlement exists for financial revision.
11.2 Settlement invariants
Payment carry forward never disappears.
Refund carry forward never disappears.
Surplus never disappears.
Outstanding is not negative.
Surplus is not stored as unpaid.
Customer balance entry is created when required.
Rollback restores original allocations if commit fails.
11.3 Refund invariants
Refund cannot exceed refundable component amount.
Double refund is rejected.
Paid row refund can create money effect.
Unpaid row cancellation can be zero money effect.
Stock return is explicit.
Stock no return is explicit.
External purchase effect is explicit.
Refund audit references affected rows and effects.
11.4 Inventory invariants
Every stock mutation has inventory movement.
Every revision reversal references source.
Every refund reversal references source.
Negative stock is blocked unless policy changes.
Historical cost is not recalculated from current cost.
Current inventory can be rebuilt from ledger.
11.5 Report invariants
Current report can differ from original report.
Revision report can differ from current report.
As of report is stable for the selected date.
Ledger actual report reconciles cash and stock events.
PDF and Excel output declares report mode.
No report silently mixes current and historical basis.
12. Stop Conditions

Stop and do not implement if:

The active slice cannot identify source of truth.
Overpaid behavior is required but not decided.
A DB migration is needed but table contract is unclear.
A report query would mix current and historical data.
A UI change would hide a backend defect.
A delete path would remove unprotected financial evidence.
A legacy record cannot be reconstructed exactly and the design has no legacy uncertainty marker.
Actor, reason, or audit source is missing for sensitive mutation.
13. Explicit Out of Scope Until Decision
Full rewrite to pure event sourcing
Replacing all reports at once
Removing legacy projection without migration plan
Replacing all work item storage
Changing procurement lifecycle unless active external purchase slice requires it
Browser redesign before backend plan
API rewrite before use case contract is stable
14. First Safe Slice Recommendation

First implementation slice should not be UI.

Recommended first slice:

Revision Settlement and Customer Balance Foundation.

Reason:

Downward revision currently has containment but not final product.
Overpaid is the core blocker for serious edit.
Refund and report improvements depend on explicit surplus model.
UI can only be honest after backend can produce plan.

Minimum first slice output:

note_revision_settlements migration
customer_balance_entries migration if surplus is in scope
core value object or DTO for revision settlement
writer and reader ports
adapter
settlement builder
tests for equal, upward, downward revision
no UI change unless backend plan is ready

