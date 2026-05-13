# Handoff 0017 - Surplus Refund Paid Report Cash Ledger Read Model

## Metadata

- Date: 2026-05-14
- Sequence: 0017
- Scope: backend report and cash ledger read model for surplus_refund_paid from refund_due
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0016_refund_paid_audit_timeline_read_model_handoff.md
- Owner workflow: owner handles commit and push manually
- Closure state: final make verify passed and owner reported push completed

## Status

Closed and pushed by owner.

This slice started from source audit only, then added RED tests, then applied the minimum read model patch, then reached final make verify green.

The backend report and cash ledger read model now distinguish surplus_refund_paid from customer_refunds and refund_due.

## Locked Decisions

refund_paid from refund_due uses:

    note_revision_surplus_refund_payments

Do not use:

- customer_refunds for surplus refund_paid
- customer_payment_id for surplus refund_paid
- refund_component_allocations for surplus refund_paid
- note refunded lifecycle
- inventory reversal
- customer_credit
- customer_balance_entries
- PostgreSQL implementation
- Go API implementation
- UI/export as source of truth

customer_refunds remains the source for normal customer payment/component refund.

note_revision_surplus_dispositions remains the source for refund_due liability/disposition.

note_revision_surplus_refund_payments is the source for actual surplus refund_paid cash-out execution.

## Baseline Before This Slice

Owner provided baseline from handoff 0016:

- refund_paid backend foundation completed and verified
- refund_paid audit timeline read model completed and pushed
- targeted refund_paid timeline proof: 1 passed / 9 assertions
- focused timeline proof: 3 passed / 25 assertions
- bounded backend and timeline proof: 22 passed / 112 assertions
- owner reported final make verify passed
- owner reported push completed
- latest handoff pointer was 0016

## Source Audit Proof

The first audit command searched report, cash ledger, refund_due, customer_refunds, and surplus_refund_paid references.

Audit found:

- TransactionSummaryReportingQuery only read customer_refunds into refunded_rupiah.
- TransactionSummaryRefundDueTotalsQuery only read note_revision_surplus_dispositions into refund_due_rupiah.
- Transaction summary did not expose surplus_refund_paid_rupiah.
- Transaction summary did not expose remaining_refund_due_rupiah.
- TransactionCashLedgerReportingQuery only concatenated payment rows and customer refund rows.
- TransactionCashLedgerRefundRowsQuery only read customer_refunds and emitted event_type refund.
- No cash ledger query read note_revision_surplus_refund_payments.
- TransactionCashLedgerPerNoteRow was generic enough for the minimum patch because it already carried event_type, direction, event_amount_rupiah, customer_payment_id, and refund_id.

Second audit inspected relevant query, DTO, builder, use case, and test files.

Files inspected included:

- app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionSummaryRefundDueTotalsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- app/Adapters/Out/Reporting/DatabaseTransactionReportingSourceReaderAdapter.php
- app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php
- app/Application/Reporting/Services/TransactionReportingReconciliationService.php
- app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php
- app/Application/Reporting/Services/TransactionSummaryPerNoteBuilder.php
- app/Application/Reporting/Services/TransactionReportSummaryBuilder.php
- app/Application/Reporting/Services/TransactionPeriodBreakdownBuilder.php
- app/Application/Reporting/Services/TransactionCustomerBreakdownBuilder.php
- app/Application/Reporting/UseCases/GetTransactionReportDatasetHandler.php
- app/Application/Reporting/UseCases/GetTransactionSummaryPerNoteHandler.php
- app/Application/Reporting/Services/TransactionCashLedgerPerNoteBuilder.php

## RED Proof

Two RED tests were added.

Report dataset RED:

    Tests\Feature\Reporting\GetTransactionReportDatasetFeatureTest
    test_transaction_report_dataset_distinguishes_refund_due_surplus_refund_paid_and_remaining_refund_due

Failure:

    Undefined array key "surplus_refund_paid_rupiah"

This proved the transaction report dataset did not expose surplus_refund_paid_rupiah or remaining_refund_due_rupiah.

Cash ledger RED:

    Tests\Feature\Reporting\TransactionCashLedgerReportingQueryFeatureTest
    test_cash_ledger_includes_surplus_refund_paid_as_separate_outflow

Failure:

    Failed asserting that actual size 0 matches expected size 1.

This proved the transaction cash ledger did not include note_revision_surplus_refund_payments as an outflow.

## Files Changed

Production files changed:

- app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionSummarySurplusRefundPaymentTotalsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerSurplusRefundPaidRowsQuery.php
- app/Application/Reporting/UseCases/GetTransactionSummaryPerNoteHandler.php
- app/Application/Reporting/Services/TransactionReportSummaryBuilder.php
- app/Application/Reporting/Services/TransactionPeriodBreakdownBuilder.php
- app/Application/Reporting/Services/TransactionCustomerBreakdownBuilder.php

Test files changed:

- tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php
- tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php

Docs files changed by this handoff step:

- docs/99_archive/handoff/v2/edit_refund_sniper/0017_surplus_refund_paid_report_cash_ledger_read_model_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

## Behavior Implemented

Transaction summary read model now distinguishes:

- refunded_rupiah from customer_refunds
- refund_due_rupiah from note_revision_surplus_dispositions
- surplus_refund_paid_rupiah from note_revision_surplus_refund_payments
- remaining_refund_due_rupiah as refund_due minus surplus_refund_paid

remaining_refund_due_rupiah is clamped to zero with GREATEST in the read model.

surplus_refund_paid_rupiah is not merged into refunded_rupiah.

Transaction report dataset now carries surplus_refund_paid_rupiah and remaining_refund_due_rupiah through:

- raw summary query
- dataset payload rows
- report summary
- period breakdown
- customer breakdown

Transaction cash ledger now includes surplus refund paid as a separate outflow:

- source table: note_revision_surplus_refund_payments
- event_type: surplus_refund_paid
- direction: out
- event_date: effective_date
- event_amount_rupiah: amount_rupiah
- note_id: note_root_id
- customer_payment_id: null
- refund_id: null

## Behavior Explicitly Not Implemented

Not implemented:

- UI labels for new report fields
- PDF export parity
- Excel export parity
- dashboard wiring
- operational profit integration
- refund_paid submit UI/controller/route
- reversal/cancel flow
- customer_credit
- customer_balance_entries
- PostgreSQL
- Go API

Not touched:

- customer_refunds mutation flow
- refund_component_allocations
- payment refund lifecycle
- note refunded lifecycle
- inventory reversal
- refund_paid backend mutation foundation
- audit timeline read model

## Targeted GREEN Proof

Syntax proof passed:

    No syntax errors detected in app/Adapters/Out/Reporting/Queries/TransactionSummarySurplusRefundPaymentTotalsQuery.php
    No syntax errors detected in app/Adapters/Out/Reporting/Queries/TransactionCashLedgerSurplusRefundPaidRowsQuery.php
    No syntax errors detected in app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
    No syntax errors detected in app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php
    No syntax errors detected in app/Application/Reporting/UseCases/GetTransactionSummaryPerNoteHandler.php
    No syntax errors detected in app/Application/Reporting/Services/TransactionReportSummaryBuilder.php
    No syntax errors detected in app/Application/Reporting/Services/TransactionPeriodBreakdownBuilder.php
    No syntax errors detected in app/Application/Reporting/Services/TransactionCustomerBreakdownBuilder.php
    No syntax errors detected in tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php
    No syntax errors detected in tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php

Targeted report dataset proof:

    PASS Tests\Feature\Reporting\GetTransactionReportDatasetFeatureTest
    Tests: 1 passed / 9 assertions

Targeted cash ledger proof:

    PASS Tests\Feature\Reporting\TransactionCashLedgerReportingQueryFeatureTest
    Tests: 1 passed / 10 assertions

## Residual Gaps

Still pending:

- focused report/cash-ledger test proof
- exact array updates in existing report dataset tests if focused suite fails
- TransactionSummaryReportingQueryFeatureTest full proof
- TransactionCashLedgerReportingQueryFeatureTest full proof
- GetTransactionCashLedgerPerNoteFeatureTest proof
- GetTransactionReportDatasetFeatureTest full proof
- report UI labels for new fields
- export PDF/Excel parity for new fields
- dashboard/operational profit dependency review
- final make verify
- commit and push proof from owner

## Residual Risks

The targeted patch is green, but focused tests may reveal exact-array failures because new keys were added to summary, period rows, customer rows, or report payload rows.

GetTransactionSummaryPerNoteHandler currently carries new fields into payload rows by merging raw fields after DTO conversion. This is intentionally a minimum patch to avoid expanding TransactionSummaryPerNoteRow and TransactionReportingReconciliationService in the targeted slice. A later cleanup may move surplus_refund_paid_rupiah and remaining_refund_due_rupiah into the DTO and reconciliation contract if focused proof or audit-lines requires stricter typing.

TransactionCashLedgerSurplusRefundPaidRowsQuery emits surplus_refund_payment_id in raw rows, but TransactionCashLedgerPerNoteRow currently drops it because the DTO has no source_id/source_table field. This is acceptable for the targeted test but remains a contract gap versus ADR 0029, which wants source_table, source_id, and source_disposition_id semantics. The next slice should decide whether to extend ledger DTO/source metadata or keep UI-facing dataset minimal and add separate detailed source metadata later.

Exports are not updated yet. Export parity must not be claimed.

UI is not updated yet. UI visibility must not be claimed.

## Next Active Step

Run focused reporting/cash-ledger proof.

Recommended command:

    php artisan test \
      tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php \
      tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php \
      tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php \
      tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php

If focused tests fail because expected arrays are missing new keys, update only expected arrays and rerun the focused command.

If focused tests pass, next step is docs closure or a more explicit ledger source metadata patch depending on owner decision.

Do not start UI/export before backend dataset proof is focused-green.

## Next Session Opening Prompt

Kita lanjut HyperPOS refund_paid dari handoff 0017.

Baseline proof:

- refund_paid backend foundation completed and verified.
- refund_paid audit timeline read model completed and pushed.
- Source audit proved transaction report and cash ledger did not include surplus_refund_paid.
- RED report dataset proof failed on missing surplus_refund_paid_rupiah.
- RED cash ledger proof failed because surplus_refund_paid row count was 0.
- Targeted GREEN passed:
  - GetTransactionReportDatasetFeatureTest filtered surplus refund paid test: 1 passed / 9 assertions.
  - TransactionCashLedgerReportingQueryFeatureTest filtered surplus refund paid test: 1 passed / 10 assertions.
- Syntax proof passed for 10 touched files.
- Focused proof is still pending.
- Owner handles commit and push manually.

Read first:

1. docs/01_standards/0001_index.md
2. docs/01_standards/0002_decision_policy.md
3. docs/99_archive/handoff/v2/edit_refund_sniper/README.md
4. docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
5. docs/99_archive/handoff/v2/edit_refund_sniper/0017_surplus_refund_paid_report_cash_ledger_read_model_handoff.md
6. docs/02_architecture/adr/0029_note_revision_surplus_refund_paid_execution.md
7. docs/02_architecture/adr/0009_reporting_as_read_model.md
8. docs/03_blueprints/reporting/0004_reporting_execution_workflow.md

Locked decision:

refund_paid from refund_due uses note_revision_surplus_refund_payments.

Do not use customer_refunds for surplus refund_paid.
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
- targeted transaction report dataset support for surplus_refund_paid
- targeted transaction cash ledger support for surplus_refund_paid outflow

Current changed files:

- app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionSummarySurplusRefundPaymentTotalsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerSurplusRefundPaidRowsQuery.php
- app/Application/Reporting/UseCases/GetTransactionSummaryPerNoteHandler.php
- app/Application/Reporting/Services/TransactionReportSummaryBuilder.php
- app/Application/Reporting/Services/TransactionPeriodBreakdownBuilder.php
- app/Application/Reporting/Services/TransactionCustomerBreakdownBuilder.php
- tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php
- tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
- docs/99_archive/handoff/v2/edit_refund_sniper/0017_surplus_refund_paid_report_cash_ledger_read_model_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

Next safest step:

Run focused report/cash-ledger proof:

    php artisan test \
      tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php \
      tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php \
      tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php \
      tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php

If exact-array tests fail, update expected arrays only.

Do not start export/UI/dashboard until backend dataset proof is focused-green.

## README Update Required

Yes.

New latest handoff filename:

    0017_surplus_refund_paid_report_cash_ledger_read_model_handoff.md

## Session Context Health

84 percent.

Handoff required before continuing large work.

## Final Closure Update

After the initial targeted GREEN proof, focused/final verification found exact-array drift in existing reporting tests because the report payload now includes two new zero-default fields:

- surplus_refund_paid_rupiah
- remaining_refund_due_rupiah

The expected arrays were updated only where the new fields were now present in payloads or summaries.

One PHPStan duplicate-key blocker appeared in:

- tests/Feature/Reporting/GetTransactionSummaryPerNoteFeatureTest.php

That blocker was caused by duplicate expected array keys introduced during the exact-array update. It was fixed by removing the duplicate key pair only.

Final proof reported by owner:

    make verify
    Tests: 1011 passed / 5412 assertions

Owner reported make push and verify are safe.

## Final Verification Proof

Final make verify proof:

    PASS
    Tests: 1011 passed / 5412 assertions

Owner reported push completed.

## Final Closure State

Closed and pushed by owner.

Backend report/cash ledger read model contract is now implemented and verified for this slice.

What is now proven:

- transaction report dataset exposes refunded_rupiah from customer_refunds
- transaction report dataset exposes refund_due_rupiah from note_revision_surplus_dispositions
- transaction report dataset exposes surplus_refund_paid_rupiah from note_revision_surplus_refund_payments
- transaction report dataset exposes remaining_refund_due_rupiah as refund_due minus surplus_refund_paid
- transaction cash ledger includes surplus_refund_paid as a separate outflow
- final make verify passed

Still not implemented:

- report UI label polish for new fields
- PDF export parity
- Excel export parity
- dashboard/operational profit integration
- refund_paid submit UI/controller/route
- reversal/cancel flow

## Next Safe Step After Closure

Start the next slice from backend dataset consumer decision, not from blind UI polish.

Recommended next scope options:

1. Report screen visibility for surplus_refund_paid and remaining_refund_due.
2. Export parity for transaction report after screen dataset labels are locked.
3. Cash ledger source metadata hardening if owner wants ADR 0029 source_table/source_id/source_disposition_id semantics before UI/export.

Recommended default:

Start with report screen visibility contract for the new backend fields, then export parity.

Do not start dashboard wiring before report screen/export parity is settled.
