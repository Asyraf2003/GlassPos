# Handoff 0018 - Surplus Refund Paid Report Screen Export Visibility

## Metadata

- Date: 2026-05-14
- Sequence: 0018
- Scope: report screen and export visibility for surplus_refund_paid and remaining_refund_due
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0017_surplus_refund_paid_report_cash_ledger_read_model_handoff.md
- Owner workflow: owner handles commit and push manually
- Closure state: final make verify passed; owner commit/push pending unless already handled

## Status

Focused screen and export parity slice is locally verified.

This slice started after backend report/cash-ledger read model support from handoff 0017 was already closed and pushed.

The active goal was to make the already-proven backend transaction report dataset visible on:

- transaction report screen
- Excel export
- PDF export view data
- PDF Blade template

No backend reporting query, cash ledger query, or refund_paid mutation foundation was changed in this slice.

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
- dashboard as source of truth
- export query divergence from report dataset

Screen and export labels locked in this slice:

- Surplus Refund Paid
- Sisa Refund Due

Dataset keys surfaced in this slice:

- surplus_refund_paid_rupiah
- remaining_refund_due_rupiah

## Baseline Before This Slice

Owner provided baseline from handoff 0017:

- refund_paid backend foundation completed and verified
- refund_paid audit timeline read model completed and pushed
- transaction report dataset support for surplus_refund_paid completed
- transaction cash ledger support for surplus_refund_paid outflow completed
- targeted GREEN passed:
  - GetTransactionReportDatasetFeatureTest filtered surplus refund paid test: 1 passed / 9 assertions
  - TransactionCashLedgerReportingQueryFeatureTest filtered surplus refund paid test: 1 passed / 10 assertions
- final make verify passed:
  - 1011 passed / 5412 assertions
- owner reported make push and verify are safe
- latest handoff pointer was 0017

## Source Audit Summary

Required docs were read first:

- docs/01_standards/0001_index.md
- docs/01_standards/0002_decision_policy.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0017_surplus_refund_paid_report_cash_ledger_read_model_handoff.md
- docs/02_architecture/adr/0029_note_revision_surplus_refund_paid_execution.md
- docs/02_architecture/adr/0009_reporting_as_read_model.md
- docs/03_blueprints/reporting/0004_reporting_execution_workflow.md

Audit findings:

- TransactionReportPageController already uses GetTransactionReportDatasetHandler.
- The report screen already reads the official backend dataset.
- The screen did not display surplus_refund_paid_rupiah.
- The screen did not display remaining_refund_due_rupiah.
- Excel export already uses the dataset through TransactionReportExcelWorkbookBuilder.
- PDF export already uses the dataset through TransactionReportPdfViewDataBuilder.
- Export writers/builders did not carry surplus_refund_paid_rupiah or remaining_refund_due_rupiah.
- PDF Blade did not render the new export fields before this slice.

## RED Proof

Screen RED:

    php artisan test tests/Feature/Reporting/TransactionReportPageFeatureTest.php \
      --filter=test_admin_can_see_surplus_refund_paid_and_remaining_refund_due_on_transaction_report_page

Failure:

    Expected response to contain: Surplus Refund Paid

This proved the transaction report screen did not expose the new backend fields.

Export RED:

    php artisan test tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php \
      --filter=test_exports_include_surplus_refund_paid_and_remaining_refund_due_from_dataset

Failure:

    Failed asserting that two strings are identical.
    -'Total Surplus Refund Paid'
    +'Total Kas Bersih'

This proved the Excel export summary dropped surplus_refund_paid_rupiah and remaining_refund_due_rupiah.

## Files Changed

Production files changed:

- resources/views/admin/reporting/transaction_summary/index.blade.php
- app/Application/Reporting/Exports/TransactionReportExcelSummarySheetWriter.php
- app/Application/Reporting/Exports/TransactionReportExcelDetailSheetWriter.php
- app/Application/Reporting/Exports/TransactionReportExcelPeriodSheetWriter.php
- app/Application/Reporting/Exports/TransactionReportExcelCustomerSheetWriter.php
- app/Application/Reporting/Exports/TransactionReportPdfViewDataBuilder.php
- resources/views/admin/reporting/transaction_summary/export_pdf.blade.php

Test files changed:

- tests/Feature/Reporting/TransactionReportPageFeatureTest.php
- tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php

Docs files changed by this handoff step:

- docs/99_archive/handoff/v2/edit_refund_sniper/0018_surplus_refund_paid_report_screen_export_visibility_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

## Behavior Implemented

Transaction report screen now displays:

- Surplus Refund Paid
- Sisa Refund Due

The screen displays the new values across relevant visible report surfaces:

- summary cards
- breakdown per tanggal
- breakdown customer
- detail per nota

Excel export now includes:

- Total Surplus Refund Paid
- Total Sisa Refund Due
- Surplus Refund Paid column
- Sisa Refund Due column

Excel sheets covered:

- Ringkasan
- Rincian Nota
- Rekap Per Tanggal
- Rekap Per Customer

PDF export view data now includes:

- Surplus Refund Paid summary item
- Sisa Refund Due summary item
- surplus_refund_paid row value
- remaining_refund_due row value

PDF Blade template now renders:

- Surplus Refund Paid
- Sisa Refund Due
- corresponding rupiah values

## Behavior Explicitly Not Implemented

Not implemented:

- dashboard integration
- operational profit integration
- refund_paid submit UI/controller/route
- cash ledger source metadata hardening for source_table/source_id/source_disposition_id
- reversal/cancel flow
- customer_credit
- customer_balance_entries
- PostgreSQL
- Go API

Not touched:

- backend report read model queries
- transaction cash ledger queries
- refund_paid backend mutation foundation
- audit timeline read model
- customer_refunds mutation flow
- refund_component_allocations
- note refunded lifecycle
- inventory reversal

## Targeted GREEN Proof

Screen targeted GREEN:

    PASS Tests\Feature\Reporting\TransactionReportPageFeatureTest
    Tests: 1 passed / 5 assertions

Screen focused GREEN:

    PASS Tests\Feature\Reporting\TransactionReportPageFeatureTest
    Tests: 6 passed / 43 assertions

Export syntax proof:

    No syntax errors detected in app/Application/Reporting/Exports/TransactionReportExcelSummarySheetWriter.php
    No syntax errors detected in app/Application/Reporting/Exports/TransactionReportExcelDetailSheetWriter.php
    No syntax errors detected in app/Application/Reporting/Exports/TransactionReportExcelPeriodSheetWriter.php
    No syntax errors detected in app/Application/Reporting/Exports/TransactionReportExcelCustomerSheetWriter.php
    No syntax errors detected in app/Application/Reporting/Exports/TransactionReportPdfViewDataBuilder.php

Export targeted GREEN:

    PASS Tests\Unit\Application\Reporting\Exports\TransactionReportExportRefundDueVisibilityTest
    Tests: 1 passed / 24 assertions

Export focused GREEN before PDF Blade render test:

    PASS Tests\Unit\Application\Reporting\Exports\TransactionReportExportRefundDueVisibilityTest
    Tests: 3 passed / 43 assertions

PDF Blade targeted GREEN:

    PASS Tests\Unit\Application\Reporting\Exports\TransactionReportExportRefundDueVisibilityTest
    Tests: 1 passed / 4 assertions

Export focused GREEN after PDF Blade render test:

    PASS Tests\Unit\Application\Reporting\Exports\TransactionReportExportRefundDueVisibilityTest
    Tests: 4 passed / 47 assertions

## Final Focused Proof

Final focused source-to-surface proof:

    php artisan test \
      tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php \
      tests/Feature/Reporting/TransactionReportPageFeatureTest.php \
      tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php

Result:

    PASS Tests\Feature\Reporting\GetTransactionReportDatasetFeatureTest
    PASS Tests\Feature\Reporting\TransactionReportPageFeatureTest
    PASS Tests\Unit\Application\Reporting\Exports\TransactionReportExportRefundDueVisibilityTest
    Tests: 12 passed / 115 assertions

## Residual Gaps

Still pending:

- commit and push proof from owner
- dashboard/operational profit integration
- refund_paid submit UI/controller/route
- cash ledger source metadata hardening for ADR 0029 source_table/source_id/source_disposition_id semantics
- reversal/cancel flow

## Final Make Verify Proof

Final make verify proof after stale export feature fixture fixes:

    Tests: 1014 passed / 5455 assertions

This verifies the full project test suite after report screen/export visibility changes.

## Residual Risks

The focused proof is strong for report dataset, screen, and export visibility.

Full local verification is green.

No export query divergence was introduced in this slice because export surfaces still consume the same GetTransactionReportDatasetHandler dataset path through the existing controllers/builders.

## Next Safe Step

Recommended next scope options:

1. Full make verify and owner commit/push for this slice.
2. Cash ledger source metadata hardening for ADR 0029 source_table/source_id/source_disposition_id semantics.
3. refund_paid submit UI/controller/route slice.
4. Dashboard/operational profit integration only after report/export parity and source metadata decisions are stable.

Recommended default:

Owner commit/push this verified slice.

After owner closure, the next engineering slice should be cash ledger source metadata hardening if ADR 0029 traceability is prioritized, otherwise refund_paid submit UI/controller/route.

Do not start dashboard wiring before report/export parity is committed and source metadata gap is explicitly accepted or fixed.

## Next Session Opening Prompt

Kita lanjut HyperPOS refund_paid dari handoff 0018.

Baseline proof:

- refund_paid backend foundation completed and verified.
- refund_paid audit timeline read model completed and pushed.
- transaction report dataset support for surplus_refund_paid completed.
- transaction cash ledger support for surplus_refund_paid outflow completed.
- report screen visibility for surplus_refund_paid and remaining_refund_due completed.
- Excel export parity for surplus_refund_paid and remaining_refund_due completed.
- PDF export view data and Blade render visibility completed.
- Final focused source-to-surface proof passed:
  - GetTransactionReportDatasetFeatureTest
  - TransactionReportPageFeatureTest
  - TransactionReportExportRefundDueVisibilityTest
  - 12 passed / 115 assertions.

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
- transaction report backend dataset distinguishes:
  - refunded_rupiah from customer_refunds
  - refund_due_rupiah from note_revision_surplus_dispositions
  - surplus_refund_paid_rupiah from note_revision_surplus_refund_payments
  - remaining_refund_due_rupiah as refund_due minus surplus_refund_paid
- transaction cash ledger includes surplus_refund_paid as separate outflow
- report screen displays Surplus Refund Paid and Sisa Refund Due
- Excel export includes Surplus Refund Paid and Sisa Refund Due
- PDF export view data and Blade template render Surplus Refund Paid and Sisa Refund Due

Known residual gaps:

- owner commit/push proof after 0018 slice
- cash ledger source metadata is still minimal; ADR 0029 source_table/source_id/source_disposition_id semantics are not fully carried through DTO yet
- refund_paid submit UI/controller/route is not implemented
- dashboard/operational profit integration is not implemented
- reversal/cancel flow is out of scope

Next safest step:

Owner commit/push for 0018.

After closure, choose between:

1. cash ledger source metadata hardening for ADR 0029 traceability
2. refund_paid submit UI/controller/route

Do not start dashboard.
Do not modify refund_paid mutation foundation unless the chosen next slice is submit UI/controller/route.
Do not use customer_refunds for surplus refund_paid.
