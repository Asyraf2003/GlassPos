# Handoff - Create Transaction Cash Ledger Consumer Page And Export Split

## Metadata
- Date: 2026-05-25
- Slice / topic: Create transaction lifecycle cash ledger consumer, page, PDF export, Excel export, and verification closure for cash vs transfer split
- Workflow step: Phase 1F-9 cash ledger consumer/page/export exposure and verify closure
- Status: continue in next session
- Progress: 90%

## Target Work Page
Continue lifecycle maturity proof for create transaction cash/transfer reporting after cash ledger reader, handler, summary builder, period builder, admin page, PDF export, Excel export, and full verification were proven GREEN.

Current next target is admin cash ledger detail table payment method exposure characterization, if the owner wants to continue the same reporting split sequence.

## References Used
- Previous handoff: docs/04_lifecycle/handoff/0006_create_transaction_cash_ledger_consumer_page_handoff.md
- Blueprint: docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md
- Blueprint: docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md
- Blueprint: docs/03_blueprints/db/0017_edit_refund_characterization_plan.md
- Blueprint: docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- ADR: docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md
- Blueprint: docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md
- Workflow standard: docs/01_standards/0005_handoff_template.md
- Repo snapshot / command output: local operator outputs from 2026-05-25 session

## Locked Facts
- Full make verify before this lifecycle sequence was GREEN:
  - Tests: 2 skipped, 1080 passed (5844 assertions)
  - Duration: 59.90s
- Audit FK/outbox mismatch for refund_due and refund_paid was already closed before this handoff.
- Global AuditEventWriterPort remains bound to DatabaseAuditOutboxWriterAdapter.
- FK-bound refund_due and refund_paid handlers use canonical DatabaseAuditEventWriterAdapter through contextual binding in InfrastructureServiceProvider.
- Audit FK blocker must not be reopened unless there is new RED proof.
- Create transaction without payment is debt/save-note scenario.
- Transfer payment is real money-in and must be recorded and auditable separately from physical cash.
- Canonical customer payment money-in naming is transfer, not tf.
- Legacy tf input is normalized to transfer in note payment/cash ledger paths where relevant.
- Cash ledger reader reads modern payment_component_allocations money-in.
- Cash ledger reader exposes payment_method and splits cash vs transfer in reconciliation.
- Cash ledger handler consumer preserves payment_method through DTO boundary.
- Cash ledger summary builder exposes cash and transfer money-in split.
- Cash ledger period table builder exposes cash and transfer money-in split per date.
- Admin cash ledger page exposes summary cards for Kas Masuk, Tunai Masuk, and Transfer Masuk.
- PDF export view data summary exposes Kas Masuk, Tunai Masuk, and Transfer Masuk.
- PDF export view data detail rows expose payment_method for money-in rows.
- PDF Blade export renders Metode Pembayaran for detail rows.
- Excel summary sheet exposes cash-vs-transfer money-in split.
- Excel period sheet exposes total/cash/transfer money-in split per date.
- Excel detail sheet exposes payment method for money-in rows.
- Audit-lines blocker for app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php was closed by extracting query builders.
- Full make verify after export split and audit-lines sequence is GREEN:
  - PHPStan: OK, no errors.
  - audit-lines: SUCCESS.
  - Blade directive audit: SUCCESS.
  - Contract audit: passed.
  - Pest: 2 skipped, 1096 passed (6007 assertions).
  - Duration: 53.08s.

## Scope Used

### SCOPE-IN
- Cash ledger consumer characterization after query reader proof.
- Handler DTO boundary exposure of payment_method.
- Summary builder cash-vs-transfer split.
- Period table builder cash-vs-transfer split.
- Admin page summary exposure of cash-vs-transfer split.
- PDF view data summary cash-vs-transfer split.
- PDF view data detail payment_method exposure.
- PDF Blade detail payment_method rendering.
- Excel summary sheet cash-vs-transfer split.
- Excel period sheet total/cash/transfer split.
- Excel detail sheet payment_method exposure.
- Focused export feature regression update after split contract changed.
- Audit-lines refactor for TransactionCashLedgerPaymentRowsQuery.php without changing cash ledger behavior.
- Existing stale reporting export feature expectations updated where output contract changed.

### SCOPE-OUT
- Git status, git diff, git add, git commit, git push, branch, PR, merge.
- UpdateTransactionWorkspaceHandler.
- Revision submit and payment submit merge.
- UI/payment forms.
- Expense/payment-out naming.
- PostgreSQL, Go API, dashboard performance, large seeder refactor.
- Migration/backfill for legacy tf rows.
- Dashboard wording.
- Operational profit transfer split proof.
- Store-stock/inventory create lifecycle proof.
- Rollback/idempotency characterization.
- Admin cash ledger detail table payment_method exposure.
- Broad report version mode redesign.

## GAP
- Dashboard cash-in wording may still collapse total_in_rupiah as cash-in, but dashboard is separate scope.
- PDF binary visual/manual review was not performed; proof is data builder, Blade render, and export feature test.
- Browser/manual UI QA was not performed.
- No PostgreSQL/Go/API parity proof in this slice.
- No migration/backfill for legacy tf rows in this slice.

## Locked Decisions
- Progress is 88%.
- Progress meaning:
  - 20%: full/partial cash create lifecycle baseline proved.
  - 28%: no-payment debt/save-note baseline proved.
  - 35%: full transfer money-in baseline proved.
  - 40%: partial transfer money-in baseline proved.
  - 43%: canonical transfer naming for customer payment money-in proved.
  - 48%: cash ledger reader reads modern component allocation money-in and splits cash vs transfer.
  - 51%: handler DTO boundary exposes payment_method.
  - 54%: summary builder exposes cash-vs-transfer money-in split.
  - 57%: period table builder exposes cash-vs-transfer money-in split per date.
  - 60%: admin cash ledger page exposes cash-vs-transfer summary cards.
  - 63%: PDF export view data summary exposes cash-vs-transfer money-in split.
  - 66%: Excel summary sheet exposes cash-vs-transfer money-in split.
  - 69%: Excel period sheet exposes total/cash/transfer money-in split per date.
  - 72%: Excel detail sheet exposes payment method for money-in rows.
  - 75%: PDF view data detail rows expose payment method for money-in rows.
  - 78%: PDF Blade renders payment method column for cash ledger detail rows.
  - 81%: audit-lines blocker for TransactionCashLedgerPaymentRowsQuery.php closed without changing cash ledger behavior.
  - 84%: stale reporting export feature regressions fixed after split contract.
  - 88%: full make verify GREEN after cash ledger export split and audit-lines sequence.
  - 90%: admin cash ledger page detail table exposes payment_method and focused adjacent reporting suite remains GREEN.
- Use transfer as canonical customer payment money-in naming.
- Keep legacy tf normalization where needed.
- Do not patch UI/payment forms or expense naming unless selected as a separate active step.
- Do not patch dashboard wording unless active step explicitly chooses dashboard.
- Do not reopen audit FK blocker unless there is new RED proof.
- Keep one active step per response.
- Do not claim full verify GREEN without current make verify output.

## Files Created / Changed

### New files
- tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php
- tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelSummaryCashTransferSplitTest.php
- tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodCashTransferSplitTest.php
- tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php
- tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php
- tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerLegacyPaymentAllocationRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerComponentAllocationRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundedPaymentFallbackRowsQuery.php

### Changed files
- app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php
- app/Application/Reporting/Services/TransactionCashLedgerPerNoteBuilder.php
- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php
- tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php
- app/Application/Reporting/Services/TransactionCashLedgerSummaryBuilder.php
- tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php
- app/Application/Reporting/Services/TransactionCashLedgerPeriodTableBuilder.php
- tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php
- tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php
- resources/views/admin/reporting/transaction_cash_ledger/index.blade.php
- app/Application/Reporting/Exports/TransactionCashLedgerPdfViewDataBuilder.php
- app/Application/Reporting/Exports/TransactionCashLedgerExcelSummarySheetWriter.php
- app/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodSheetWriter.php
- app/Application/Reporting/Exports/TransactionCashLedgerExcelDetailSheetWriter.php
- resources/views/admin/reporting/transaction_cash_ledger/export_pdf.blade.php
- tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- docs/04_lifecycle/handoff/0006_create_transaction_cash_ledger_consumer_page_handoff.md

## Verification Proof
- command:
  - php artisan test tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
  - result:
    - Tests: 20 passed (134 assertions)
    - Duration: 6.80s
  - meaning:
    - PDF view data summary exposes cash-vs-transfer split and adjacent suite remains GREEN.

- command:
  - php artisan test tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelSummaryCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
  - result:
    - Tests: 21 passed (140 assertions)
    - Duration: 6.97s
  - meaning:
    - Excel summary sheet exposes cash-vs-transfer split and adjacent suite remains GREEN.

- command:
  - php artisan test tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelSummaryCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
  - result:
    - Tests: 22 passed (150 assertions)
    - Duration: 6.98s
  - meaning:
    - Excel period sheet exposes total/cash/transfer money-in split per date and adjacent suite remains GREEN.

- command:
  - php artisan test tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelSummaryCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
  - result:
    - Tests: 23 passed (156 assertions)
    - Duration: 6.84s
  - meaning:
    - Excel detail sheet exposes payment_method and adjacent suite remains GREEN.

- command:
  - php artisan test tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelSummaryCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
  - result:
    - Tests: 24 passed (160 assertions)
    - Duration: 6.80s
  - meaning:
    - PDF view data detail rows expose payment_method and adjacent suite remains GREEN.

- command:
  - php artisan test tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelSummaryCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
  - result:
    - Tests: 25 passed (163 assertions)
    - Duration: 6.85s
  - meaning:
    - PDF Blade renders payment_method and adjacent suite remains GREEN.

- command:
  - php -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
  - php -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerLegacyPaymentAllocationRowsQuery.php
  - php -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerComponentAllocationRowsQuery.php
  - php -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundedPaymentFallbackRowsQuery.php
  - result:
    - No syntax errors detected in all four files.
  - meaning:
    - Audit-lines refactor files are syntactically valid.

- command:
  - wc -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php app/Adapters/Out/Reporting/Queries/TransactionCashLedgerLegacyPaymentAllocationRowsQuery.php app/Adapters/Out/Reporting/Queries/TransactionCashLedgerComponentAllocationRowsQuery.php app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundedPaymentFallbackRowsQuery.php
  - result:
    - 79 app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
    - 37 app/Adapters/Out/Reporting/Queries/TransactionCashLedgerLegacyPaymentAllocationRowsQuery.php
    - 43 app/Adapters/Out/Reporting/Queries/TransactionCashLedgerComponentAllocationRowsQuery.php
    - 49 app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundedPaymentFallbackRowsQuery.php
    - 208 total
  - meaning:
    - All split query files are under the 100-line audit limit.

- command:
  - php artisan test tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php --filter=admin_can_export_transaction_cash_ledger_as_xlsx_with_numeric_rupiah_cells
  - result:
    - Tests: 1 passed (45 assertions)
    - Duration: 6.23s
  - meaning:
    - Excel export feature expectation is aligned with cash/transfer split contract.

- command:
  - php artisan test tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php --filter=transaction_cash_ledger_pdf_view_contains_indonesian_report_labels
  - result:
    - Tests: 1 passed (11 assertions)
    - Duration: 5.86s
  - meaning:
    - PDF direct Blade render no longer crashes when legacy fixture lacks payment_method.

- command:
  - php artisan test tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelSummaryCashTransferSplitTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfExportCashTransferSplitTest.php
  - result:
    - Tests: 32 passed (234 assertions)
    - Duration: 7.99s
  - meaning:
    - Focused cash ledger reporting/export suite is GREEN after export split and stale feature test updates.

- command:
  - php artisan test tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php
  - result:
    - Tests: 7 passed (71 assertions)
    - Duration: 6.79s
  - meaning:
    - Reporting export feature suite is GREEN.

- command:
  - make verify
  - result:
    - PHPStan: OK, no errors.
    - audit-lines: SUCCESS.
    - Blade PHP/directive audit: SUCCESS.
    - Contract audit: passed.
    - Pest: 2 skipped, 1096 passed (6007 assertions).
    - Duration: 53.08s.
  - meaning:
    - Full verification is GREEN after cash ledger export split and audit-lines closure.

## Risks / Follow-up Notes
- Dashboard cash-in wording may still collapse total_in_rupiah as cash-in; treat dashboard as separate active step.
- Export proof covers data builder, Blade render, feature export, and full verify; no manual PDF visual QA was done.
- Query split is intended as behavior-preserving refactor. Existing query/reporting tests stayed GREEN.
- Do not reopen audit FK/outbox blocker without new RED proof.

## Next Step
Phase 1F-9Y - Admin cash ledger page detail payment_method characterization.

Single active step:
- Add or patch a focused test for resources/views/admin/reporting/transaction_cash_ledger/index.blade.php or the existing TransactionCashLedgerPageFeatureTest.
- Goal: prove whether admin page detail table exposes payment method for money-in rows.
- Do not patch dashboard in the same response.
- Do not patch export again in the same response.
- Do not ask for make verify as first action.

<!-- phase-1f-9z-admin-detail-payment-method-proof -->
## Phase 1F-9Z - Admin Cash Ledger Detail Payment Method Proof

## FACT
- Admin cash ledger page detail table now exposes payment_method for money-in rows.
- Targeted page characterization test is GREEN.
- Focused adjacent reporting suite is GREEN.

## Verification Proof
- command:
  - php artisan test tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php --filter=test_admin_cash_ledger_detail_table_exposes_payment_method_for_money_in_rows
  - result:
    - Tests: 1 passed (4 assertions)
    - Duration: 6.00s
  - meaning:
    - Admin cash ledger page detail table exposes payment method for money-in rows.

- command:
  - php artisan test tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerSummaryBuilderFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerPeriodTableBuilderFeatureTest.php
  - result:
    - Tests: 20 passed (135 assertions)
    - Duration: 6.73s
  - meaning:
    - Focused admin cash ledger page and adjacent reporting suite remain GREEN after detail payment_method exposure.

## GAP
- Browser/manual UI QA was not performed.
- Full make verify was not rerun after this admin page detail payment_method patch.

## DECISION
- Progress is now 90%.
- Do not claim a new full make verify GREEN without a new make verify output.
- Keep dashboard, export, API, PostgreSQL, Go, migration/backfill, payment forms, and handlers out of scope unless selected as a separate active step.

## Next Step
Phase 1F-9AB - Select next remaining cash ledger lifecycle gap.

Recommended candidates:
- Browser/manual QA for admin cash ledger page detail table.
- Dashboard cash-in wording characterization only if dashboard is explicitly selected as active step.
- Keep PostgreSQL, Go API, migration/backfill, export, and payment forms out of scope unless selected by owner.
