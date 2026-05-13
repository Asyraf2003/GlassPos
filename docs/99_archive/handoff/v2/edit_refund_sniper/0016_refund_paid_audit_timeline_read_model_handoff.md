# Handoff 0016 - Refund Paid Audit Timeline Read Model

## Metadata

- Date: 2026-05-13
- Sequence: 0016
- Scope: refund_paid audit timeline read model for surplus refund_paid from refund_due
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0015_refund_paid_backend_foundation_handoff.md
- Owner workflow: owner handles commit and push manually
- Closure proof: owner reported all verification passed and pushed

## Status

Completed and pushed by owner.

This slice adds read-only audit timeline visibility for refund_paid execution from refund_due.

This slice does not implement UI refund_paid submit form, report integration, export integration, cash ledger integration, reversal/cancel, customer credit, customer balance entries, PostgreSQL, or Go API.

## Locked Decisions

refund_paid from refund_due uses:

    note_revision_surplus_refund_payments

Do not use:

- customer_refunds
- customer_payment_id
- refund_component_allocations
- note refunded lifecycle
- inventory reversal
- customer_credit
- customer_balance_entries
- PostgreSQL implementation
- Go API implementation
- UI/report/export as source of truth

Backend use case remains source of truth for mutation.

Audit timeline is read-only.

## Baseline Before This Slice

Owner provided baseline proof before this slice:

- Backend refund_paid foundation already existed.
- DTO, ports, adapter, and use case were green.
- Focused backend proof: 22 passed / 97 assertions.
- Existing refund allocator candidate: 1 passed / 4 assertions.
- Final make verify proof: 1008 passed / 5384 assertions.
- Owner stated make push and make verify were safe.
- Latest handoff pointer was 0015.

## Source Audit Facts

Source audit found:

- note_revision_surplus_refund_payments existed in backend foundation, migration, adapter, source reader, handler tests, adapter tests, ADR, and handoff docs.
- Existing surplus audit timeline was refund_due-only.
- NoteSurplusDispositionAuditTimelineReaderPort only exposed findRefundDueCreatedEventsByNoteRootId.
- DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter only joined note_revision_surplus_dispositions to audit_events for note_revision_surplus_refund_due_created.
- NoteSurplusDispositionAuditTimelineBuilder hardcoded Refund Due Ditandai.
- resources/views/shared/notes/partials/payment-summary-actions.blade.php rendered surplus audit timeline with Sisa pending from after_pending_rupiah.
- RecordNoteRevisionSurplusRefundPaymentAuditEventFactory already wrote canonical audit event note_revision_surplus_refund_paid_recorded with metadata and before/after snapshots.

## RED Proof

A new refund_paid audit timeline test was added to:

    tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php

Initial RED proof:

    FAIL Tests\Feature\Note\AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest
    Expected response to contain: Refund Paid Dicatat
    Tests: 1 failed / 4 assertions

This proved refund_paid audit events were not visible through the note detail surplus audit timeline.

## Files Changed

Production files:

- app/Ports/Out/Note/NoteSurplusDispositionAuditTimelineReaderPort.php
- app/Adapters/Out/Note/DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter.php
- app/Adapters/Out/Note/DatabaseNoteSurplusRefundDueAuditTimelineRowsQuery.php
- app/Adapters/Out/Note/DatabaseNoteSurplusRefundPaidAuditTimelineRowsQuery.php
- app/Application/Note/Services/NoteSurplusDispositionAuditTimelineBuilder.php
- app/Application/Note/Services/NoteSurplusDispositionAuditTimelineRowMapper.php
- resources/views/shared/notes/partials/payment-summary-actions.blade.php

Test files:

- tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php

Docs files:

- docs/99_archive/handoff/v2/edit_refund_sniper/0016_refund_paid_audit_timeline_read_model_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

## Behavior Implemented

Port:

- Added findSurplusAuditEventsByNoteRootId for combined surplus audit timeline.
- Kept findRefundDueCreatedEventsByNoteRootId for compatibility.

Adapter:

- Split large adapter into small read-only query classes.
- Reads refund_due created events from note_revision_surplus_dispositions plus audit_events.
- Reads refund_paid recorded events from note_revision_surplus_refund_payments plus audit_events plus audit_event_snapshots.
- Combines and sorts by occurred_at desc and event_id desc.
- Keeps app files under audit-lines limit.

Application builder:

- Maps refund_due event to Refund Due Ditandai.
- Maps refund_paid event to Refund Paid Dicatat.
- Adds display fields:
  - remaining_label
  - remaining_rupiah
- Keeps backend payload as source of display semantics.

Blade:

- Timeline display now renders backend-provided remaining_label and remaining_rupiah.
- Existing refund_due display remains compatible through fallback.

Test:

- Added coverage that admin note detail renders refund_paid audit timeline.
- Verifies:
  - Timeline Audit Surplus
  - Refund Due Ditandai
  - Refund Paid Dicatat
  - Amount 50.000
  - Sisa refund due 72.000
  - reason
  - admin actor role
  - no customer_credit leakage

## Behavior Explicitly Not Implemented

Not implemented:

- refund_paid submit UI
- refund_paid controller/route
- report integration
- export integration
- cash ledger integration
- reversal/cancel flow
- customer_credit
- customer_balance_entries
- PostgreSQL
- Go API

Not touched:

- customer_refunds
- refund_component_allocations
- payment customer refund flow
- note refunded lifecycle
- inventory reversal

## Verification Proof

Syntax proof was run for touched timeline files and view.

Targeted refund_paid timeline proof:

    PASS Tests\Feature\Note\AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest
    Tests: 1 passed / 9 assertions

Focused timeline proof:

    PASS Tests\Feature\Note\AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest
    Tests: 3 passed / 25 assertions

Bounded backend and timeline blast-radius proof:

    PASS
    Tests: 22 passed / 112 assertions

The bounded suite included:

- AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest
- RecordNoteRevisionSurplusRefundPaymentHandlerTest
- DatabaseNoteRevisionSurplusRefundPaymentAdapterTest
- CreateNoteRevisionSurplusRefundDueHandlerTest
- CreateNoteRevisionSurplusRefundDueControllerFeatureTest

Owner reported final make verify passed.

Owner reported changes were pushed.

## Residual Gaps

Still pending:

- report integration for surplus_refund_paid
- export integration for surplus_refund_paid
- cash ledger integration for surplus_refund_paid
- refund_paid submit UI/controller/route
- reversal/cancel flow
- strict idempotency reason replay comparison
- true two-connection concurrent overpay stress test
- docs for report/export/cash ledger labels after those slices are implemented

## Residual Risks

Audit timeline now displays refund_paid read-only visibility, but report/cash ledger/export still do not prove surplus_refund_paid handling.

Cash ledger must not count refund_due as cash out.

Report/export must distinguish:

- refunded_rupiah from customer_refunds
- refund_due_rupiah from note_revision_surplus_dispositions
- surplus_refund_paid_rupiah from note_revision_surplus_refund_payments
- remaining_refund_due_rupiah as refund_due minus surplus_refund_paid

Do not merge customer_refunds and note_revision_surplus_refund_payments into an undifferentiated refunded_rupiah field.

## Next Active Step

Recommended next session:

Start from backend report/cash ledger contract audit for surplus_refund_paid.

Do not start from UI polish.

Do not start from export.

Do not start from controller.

First inspect existing reporting and cash ledger query contracts:

- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php
- app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionSummaryRefundDueTotalsQuery.php
- cash ledger report query/read model files found by source search
- transaction report dataset tests
- transaction cash ledger dataset tests

Goal:

Determine the smallest backend read model change required so transaction report and cash ledger can distinguish surplus_refund_paid from customer_refunds and refund_due.

## Next Session Opening Prompt

Kita lanjut HyperPOS refund_paid dari handoff 0016.

Baseline proof:

- refund_paid backend foundation completed and verified.
- refund_paid audit timeline read model completed and pushed.
- Targeted refund_paid timeline proof: 1 passed / 9 assertions.
- Focused timeline proof: 3 passed / 25 assertions.
- Bounded backend and timeline proof: 22 passed / 112 assertions.
- Owner reported final make verify passed.
- Owner reported push completed.
- Latest handoff pointer should be 0016.

Read first:

1. docs/01_standards/0001_index.md
2. docs/01_standards/0002_decision_policy.md
3. docs/99_archive/handoff/v2/edit_refund_sniper/README.md
4. docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
5. docs/99_archive/handoff/v2/edit_refund_sniper/0016_refund_paid_audit_timeline_read_model_handoff.md
6. docs/02_architecture/adr/0029_note_revision_surplus_refund_paid_execution.md
7. docs/02_architecture/adr/0009_reporting_as_read_model.md
8. docs/03_blueprints/reporting/0004_reporting_execution_workflow.md

Locked decision:

refund_paid from refund_due uses note_revision_surplus_refund_payments.

Do not use customer_refunds.
Do not require customer_payment_id.
Do not create refund_component_allocations.
Do not trigger note refunded lifecycle.
Do not trigger inventory reversal.
Do not implement customer_credit.
Do not implement customer_balance_entries.
Do not implement PostgreSQL.
Do not implement Go API.

Current completed slices:

- migration/table contract for note_revision_surplus_refund_payments
- backend DTO/ports/adapters/use case
- canonical audit_events write for note_revision_surplus_refund_paid_recorded
- read-only audit timeline display for refund_paid on note detail

Next safest slice:

Start from backend report/cash ledger contract audit for surplus_refund_paid, not UI polish and not export first.

Required response shape:

FACT
GAP
ASSUMPTION
DECISION
ACTIVE STEP
FILES TO TOUCH
FILES NOT TO TOUCH
COMMAND
EXPECTED PROOF
NEXT

## README Update Required

Yes.

New latest handoff filename:

    0016_refund_paid_audit_timeline_read_model_handoff.md

## Session Context Health

79 percent.

Mini-summary required before continuing.

Locked facts:

- refund_paid uses note_revision_surplus_refund_payments.
- refund_paid backend foundation is complete and verified.
- refund_paid audit timeline read model is complete and verified.
- refund_due is liability, not cash out.
- surplus_refund_paid is cash out.
- report/cash ledger/export integration remains pending.

Current active next step:

Backend report/cash ledger contract audit for surplus_refund_paid.
