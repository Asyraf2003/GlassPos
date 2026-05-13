# Migration + Seeder + Projection Audit Status

Date: 2026-04-26  
Repository: Asyraf2003/bengkelnativejs  
Status: B / BELUM LULUS  
Scope: audit only, no implementation patch yet.

## Target Final

Seeder dan migration harus mendekati 100% dalam arti:

1. make 1 hanya akun/access.
2. make 2 data normal 1 bulan.
3. make 3 data ekstrem 1 tahun.
4. Semua tabel utama dan projection wajib UI terisi.
5. Semua skenario umum dan edge-case migration terbaru punya data.
6. Tidak ada orphan, overflow, projection mismatch, atau runtime failure.

## Current Verified Facts

- DB driver: mysql.
- Migration files: 69.
- Seeder files: 41.
- DB tables: 60.
- notes: 12089.
- note_history_projection: 1998.
- missing note projection: 10091.
- notes range: 2025-04-27..2026-04-26.
- projection range: 2025-04-27..2026-04-17.
- first date mismatch: 2025-06-17.
- first mismatch notes count: 44.
- first mismatch projection count: 35.

## Empty Edge-Case Tables

The following migration tables exist but are empty in the audited DB snapshot:

- supplier_versions
- supplier_invoice_versions
- inventory_cost_adjustments
- supplier_receipt_reversals
- supplier_payment_reversals
- supplier_payment_proof_attachments
- employee_debt_adjustments
- employee_debt_payment_reversals
- payroll_disbursement_reversals

## Candidate Data Availability

Audited candidate counts:

- suppliers: 25
- supplier_invoices: 3492
- supplier_invoice_editable_candidates: 582
- supplier_payments: 2328
- supplier_payments_pending: 1164
- supplier_receipts: 2910
- supplier_receipt_reversal_candidates: 2910
- supplier_payment_reversal_candidates: 2328
- product_inventory_costing: 338
- employee_debts: 6
- employee_debt_adjustment_candidates: 4
- employee_debt_payments: 7
- employee_debt_payment_reversal_candidates: 7
- payroll_disbursements: 12
- payroll_disbursement_reversal_candidates: 12

## Seeder Level Status

### make 1

Status: keep.

Current scope is valid:

- UserSeeder only.
- Account/access only.

### make 2

Status: not clean-contract yet.

Current seeders include:

- UserSeeder
- ProductSeeder
- SupplierSeeder
- EmployeeFinanceBaselineSeeder
- SupplierInvoiceScenarioSeeder
- SupplierInvoiceVoidedScenarioSeeder
- SupplierInvoiceBaselineSeeder
- ExpenseBaselineSeeder
- CustomerTransactionBaselineSeeder
- CustomerPaymentBaselineSeeder
- CustomerRefundBaselineSeeder
- CustomerCorrectionBaselineSeeder

Problems:

- Several baseline/scenario seeders direct insert protected tables.
- Supplier invoice seeders bypass supplier_invoice_versions.
- Customer baseline seeders are direct DB/destructive.
- Some edge-case migration tables are not populated.

### make 3

Status: high volume but not migration-contract-complete.

Current seeders include:

- UserSeeder
- ProductSeeder
- ProductLoadSeeder
- SupplierSeeder
- EmployeeFinanceBaselineSeeder
- ProcurementLoadSeeder
- ExpenseLoadSeeder
- CustomerTransactionLoadSeeder
- CustomerPaymentLoadSeeder
- CustomerRefundLoadSeeder
- CustomerCorrectionLoadSeeder

Problems:

- ProcurementLoadSeeder direct inserts supplier_invoices and supplier_invoice_lines.
- supplier_invoice_versions remains empty.
- supplier_versions remains empty.
- supplier_payment_proof_attachments remains empty.
- supplier_receipt_reversals remains empty.
- supplier_payment_reversals remains empty.
- inventory_cost_adjustments remains empty.
- employee finance adjustment/reversal tables remain empty.
- note_history_projection mismatch remains unresolved.

## Seeder Classification Summary

### KEEP

- UserSeeder

### KEEP_WITH_AUDIT

- ProductSeeder
- SupplierSeeder
- EmployeeFinanceBaselineSeeder
- ExpenseBaselineSeeder
- ExpenseLoadSeeder
- ProductLoadSeeder

### REWORK

- ProcurementLoadSeeder
- SupplierInvoiceBaselineSeeder
- SupplierInvoiceScenarioSeeder
- SupplierInvoiceVoidedScenarioSeeder
- SupplierPaymentProofSeeder
- FinancialCorrectionSeeder

### REWORK_REVIEW

- CustomerTransactionBaselineSeeder
- CustomerPaymentBaselineSeeder
- CustomerRefundBaselineSeeder
- CustomerCorrectionBaselineSeeder
- CustomerTransactionLoadSeeder
- CustomerPaymentLoadSeeder
- CustomerRefundLoadSeeder
- CustomerCorrectionLoadSeeder

### DEPRECATE_OR_MERGE

- SupplierInvoiceAnnualDenseSeeder

### DELETE_CANDIDATE

- EmployeeFinanceSeeder
- SupplierInvoiceSeeder
- WorkshopStressTestSeeder

### SUPPORT_KEEP

- SeedWindow
- SeedDensity
- ProductSeedCatalog
- ProductSeedThresholds

## Domain Rework Decision Table

| Domain | Decision | Risk | Reason |
|---|---|---:|---|
| identity_access | KEEP | LOW | make 1 contract hanya akun/access dan seeder sudah scoped |
| product_catalog | KEEP_WITH_AUDIT | MEDIUM | ProductLoadSeeder punya system path hint, product scenario orphan perlu keputusan nanti |
| supplier_master | REWORK_VERSIONING_OR_BACKFILL | HIGH | supplier_versions table kosong dan tidak ada seeder level yang mengisi version history |
| procurement_invoice | REWORK_TO_SYSTEM_PATH_OR_DETERMINISTIC_BACKFILL | HIGH | direct insert bypasses supplier_invoice_versions/audit/projection behavior |
| supplier_payment_proof | REWORK_TO_ATTACHMENT_WRITE_PATH | HIGH | random/direct proof seed is not deterministic and make 3 does not cover attachments |
| procurement_reversal | ADD_DETERMINISTIC_SCENARIO_AFTER_PRECHECK | HIGH | system supports reversal but seeders create no reversal data |
| inventory_cost_adjustment | DESIGN_DECISION_REQUIRED | HIGH | table exists but no clear official write path from audit |
| employee_finance | REWORK_FINANCIAL_CORRECTION_TO_SYSTEM_PATH | HIGH | FinancialCorrectionSeeder is random/direct and not in make 2/3 |
| customer_transaction | SEPARATE_REWORK_PHASE | HIGH | direct/destructive seeders plus note_history_projection mismatch need isolated redesign |
| legacy_orphan | DEPRECATE_OR_DELETE_AFTER_REPLACEMENT | MEDIUM | not in make 1/2/3 final path and some are random/stress-only |

## Write Path Availability Summary

### Procurement Invoice

Write path exists.

Important references identified by audit:

- CreateSupplierInvoice
- UpdateSupplierInvoice
- DatabaseVersionedSupplierInvoiceWriterAdapter
- PersistsVersionedSupplierInvoiceWrites
- supplier_invoice_versions
- SupplierInvoiceListProjectionService

Decision:

- Rework supplier invoice seeders to use system path or deterministic backfill strategy.
- Do not keep direct DB insert as final contract for supplier invoice data.

### Supplier Payment Proof

Write path exists.

Important references identified by audit:

- AttachSupplierPaymentProofHandler
- AttachSupplierPaymentProofTransaction
- SupplierPaymentProofAttachmentFactory
- DatabaseSupplierPaymentProofAttachmentWriterAdapter

Decision:

- Rework proof seeder to deterministic attachment write path.
- Avoid random file selection and direct insert.

### Procurement Reversal

Write path exists.

Important references identified by audit:

- ReverseSupplierPaymentHandler
- ReverseSupplierReceiptHandler
- SupplierPaymentReversalRecorder
- SupplierReceiptReversalRecorder

Decision:

- Add deterministic scenario after precheck.
- Do not direct insert reversal as final approach.

### Inventory Cost Adjustment

Write path is unclear.

Decision:

- Requires design decision before seeding.
- Do not direct insert yet unless explicitly approved as fixture-only strategy.

### Employee Finance

Write path exists.

Important references identified by audit:

- AdjustEmployeeDebtPrincipalHandler
- ReverseEmployeeDebtPaymentHandler
- ReversePayrollDisbursementHandler
- DatabaseEmployeeDebtAdjustmentWriterAdapter
- DatabaseEmployeeDebtPaymentReversalWriterAdapter
- DatabasePayrollDisbursementReversalWriterAdapter

Decision:

- Rework FinancialCorrectionSeeder into deterministic/system-path seeder.
- Do not keep random/direct correction seeder as final.

### Customer Transaction

Write path exists but domain is large and projection is expensive.

Decision:

- Separate rework phase.
- Do not mix with procurement/employee edge-case work.

## Note Projection Status

Problem:

- notes: 12089.
- note_history_projection: 1998.
- missing: 10091.

Read-only source reader test showed missing sample notes can be read successfully.

Sample result:

- seed-note-load-20250617-36: SOURCE_OK
- seed-note-load-20250617-37: SOURCE_OK
- seed-note-load-20250617-38: SOURCE_OK
- seed-note-load-20250617-39: SOURCE_OK
- seed-note-load-20250617-40: SOURCE_OK
- seed-note-load-20250617-41: SOURCE_OK
- seed-note-load-20250617-42: SOURCE_OK
- seed-note-load-20250617-43: SOURCE_OK
- seed-note-load-20250617-44: SOURCE_OK

Conclusion:

- Data sample is not fatally corrupt.
- Projection rebuild per note is too slow.
- Need separate optimized projection strategy.

## Next Safest Work Order

1. Lock this audit handoff.
2. Rework supplier invoice seeding/versioning first.
3. Rework supplier payment proof.
4. Add procurement reversal scenario after precheck.
5. Rework employee finance correction/reversal.
6. Decide inventory_cost_adjustments write path.
7. Rework customer transaction and note projection separately.
8. Only after replacements exist, deprecate/delete legacy orphan seeders.

## Current Progress

- Migration/schema audit: done.
- Empty edge-case table audit: done.
- Candidate edge-case data audit: done.
- Seeder classification audit: done.
- Seeder contract matrix: done.
- Write path availability audit: done.
- Domain rework decision table: done.
- Implementation patch: not started.
- Final make 1/2/3 contract: not passed.

Official status: B / BELUM LULUS.
