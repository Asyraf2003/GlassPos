# Transaction, Idempotency, And Audit Findings

Scope source: Stage 0 baseline and Batch 1B route-surface discovery.

## MRD-005 - Transaction Boundary Contract Is Too Thin For Cross-Stack Ownership

ID: MRD-005

Status: open

Severity: P1

Area: Transaction/idempotency/audit readiness

Type: readiness-debt

Evidence:

- `app/Ports/Out/TransactionManagerPort.php`
- `app/Adapters/Out/Persistence/DatabaseTransactionManagerAdapter.php`
- `app/Application/Procurement/Services/Mobile/UploadSupplierInvoicePaymentProofOperation.php::execute`
- `app/Application/Procurement/Services/AttachSupplierPaymentProofTransaction.php::run`
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`

FACT:

- `TransactionManagerPort` exposes `begin`, `commit`, and `rollBack`.
- The Laravel adapter delegates directly to `DB::beginTransaction`, `DB::commit`, and `DB::rollBack`.
- Scanned procurement write flows manually begin, commit, roll back, and clean up stored files.

RISK:

- A thin transaction port does not define isolation level, retry behavior, deadlock behavior, lock target policy, nested transaction rules, or after-commit side effects.

GAP:

- No global transaction boundary map.
- No transaction manager contract tests.
- No deadlock/retry/isolation policy.

Why it matters for smooth Go/PostgreSQL transition:

Cross-stack write migration requires transaction semantics that can be implemented in Laravel and Go with the same guarantees.

Recommended direction:

Document transaction boundary contracts per high-risk use case before moving write ownership.

Proof required:

- Per-use-case transaction boundary map.
- Lock target and post-lock recalculation proof.
- Deadlock/retry and after-commit policy decisions.

Suggested test/proof:

- Rollback and partial failure characterization tests.
- Concurrency tests where feasible.
- File/storage cleanup tests for upload-plus-DB transactions.

Do not fix yet: yes

## MRD-006 - Idempotency Coverage Is Partial And Not A Global Write Contract

ID: MRD-006

Status: open

Severity: P1

Area: Transaction/idempotency/audit readiness

Type: readiness-debt

Evidence:

- Stage 0 command evidence: `rg "idempotency|idempotency_key|Idempotency" app database/migrations tests docs -n`
- `database/migrations/2026_05_13_000300_create_note_revision_surplus_refund_payments_table.php`
- `app/Adapters/In/Http/Requests/Note/RecordNoteRevisionSurplusRefundPaymentRequest.php`
- `app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php`
- `tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php`
- `tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php`
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`

FACT:

- Idempotency is implemented and tested for note revision surplus refund paid flow.
- ADR-0022 says idempotency is recommended but not required for its first concurrency slice unless existing source supports it.

RISK:

- Payment, refund, supplier payment proof upload, stock, and reversal writes may lack consistent idempotency contracts.

GAP:

- No full mutation idempotency matrix.
- No global idempotency key source, uniqueness scope, request hash, replay response, expiration, or audit policy.

Why it matters for smooth Go/PostgreSQL transition:

Phased migration increases retries and shadow paths; high-risk writes need explicit idempotency.

Recommended direction:

Classify idempotency per mutation and require accepted-risk records for writes without idempotency.

Proof required:

- Idempotency matrix for every high-risk write.
- Explicit accepted-risk decisions where idempotency is absent.
- Duplicate submit/retry tests.

Suggested test/proof:

- Same-key same-payload replay test.
- Same-key different-payload rejection test.
- Storage upload retry and rollback tests.

Do not fix yet: yes

## MRD-007 - Audit Canonical Path And Legacy Audit Log Split Need Write-Path Classification

ID: MRD-007

Status: needs-proof

Severity: P1

Area: Transaction/idempotency/audit readiness

Type: readiness-debt

Evidence:

- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php`
- `database/migrations/2026_03_10_000300_create_audit_logs_table.php`
- `app/Ports/Out/AuditEventWriterPort.php`
- `app/Ports/Out/AuditLogPort.php`
- `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofRecorder.php::record`
- `app/Application/Procurement/Services/AttachSupplierPaymentProofTransaction.php::run`

FACT:

- ADR-0028 says `audit_events` and `audit_event_snapshots` are canonical, while `audit_logs` are legacy/compatibility.
- Both `AuditEventWriterPort` and `AuditLogPort` exist.
- Scanned procurement proof flows use `AuditLogPort`.

RISK:

- Future Go writes may write canonical audit while legacy Laravel flows still write legacy audit, or vice versa.

GAP:

- No full audit writer usage map.
- No canonical-vs-legacy migration policy per bounded context.
- No audit parity tests across Laravel/Go paths.

Why it matters for smooth Go/PostgreSQL transition:

Audit is a rollback and finance correctness boundary.

Recommended direction:

Inventory every audit-producing write path and classify canonical, legacy, dual-write, or accepted-risk.

Proof required:

- Audit write-path matrix.
- Required audit facts per mutation.
- Parity tests comparing expected audit events/logs.

Suggested test/proof:

- Feature tests asserting audit facts for payment/refund/procurement/inventory/revision writes.
- Reconciliation script for legacy logs vs canonical audit events where both apply.

Do not fix yet: yes

## SEC-1B-04 - Note/Payment/Refund/Procurement Writes Need Per-Mutation Matrix

ID: SEC-1B-04

Status: open

Severity: P1

Area: Transaction/idempotency/audit readiness

Type: readiness-debt

Evidence:

- `routes/web/note.php:34-37`
- `routes/web/note.php:60-65`
- `routes/web/note.php:82-95`
- `routes/web/procurement.php:9-15`
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`
- `docs/03_blueprints/security/0007_payment_concurrency.md`
- `docs/03_blueprints/security/0008_payment_concurrency_dod.md`

FACT:

- Note, payment, refund, correction, procurement invoice, and supplier invoice receive routes exist.
- Existing security docs treat payment concurrency and allocation correctness as high-risk proof areas.

RISK:

- These routes combine finance, stock, note state, and revision behavior.

GAP:

- No complete per-mutation map.
- No complete transaction boundary, idempotency, audit, and rollback matrix.

Why it matters for smooth Go/PostgreSQL transition:

Go API ownership cannot be decided safely by route group alone.

Recommended direction:

Treat these writes as Laravel-owned until a Domain Mutation Readiness batch proves their contracts.

Proof required:

- Per-mutation command/result/error matrix.
- Lock target and transaction boundary matrix.
- Audit event matrix.
- PostgreSQL parity proof for critical writes.

Suggested test/proof:

- Golden fixture tests for payments, refunds, allocations, procurement receipt, and note revision.
- Concurrency tests where feasible.
- Reconciliation tests for note/payment/projection/audit outputs.

Do not fix yet: yes

## SEC-1B-B-01 - Stock, Payroll, Employee Debt, And Expense Mutations Need Proof

ID: SEC-1B-B-01

Status: open

Severity: P1

Area: Finance/stock/payment/refund write readiness

Type: readiness-debt

Evidence:

- `routes/web/admin_products.php:23-27`
- `routes/web/admin_employee_debts.php:20-27`
- `routes/web/admin_payrolls.php:16-28`
- `routes/web/admin_expenses.php:27-43`
- `App\Adapters\In\Http\Controllers\Admin\Product\RecordProductStockAdjustmentController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Product\ReverseProductStockAdjustmentController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\EmployeeDebt\StoreEmployeeDebtPaymentController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Payroll\StorePayrollController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Expense\StoreExpenseController::__invoke`

FACT:

- Covered stock, payroll, employee debt, and expense mutation routes are admin-authenticated.
- Multiple routes have FormRequest or inline validation for amounts, dates, reason fields, and reversal fields.

RISK:

- Retries, double submits, reversal ordering, and rollback behavior can diverge when moved to Go or PostgreSQL.

GAP:

- No route-level idempotency proof.
- No route-level transaction boundary proof for each mutation.
- No rollback/reversal parity proof.

Why it matters for smooth Go/PostgreSQL transition:

These writes affect money, debt balances, payroll, expenses, stock, and reversals.

Recommended direction:

Add these routes to the Domain Mutation Readiness map before any Go ownership proposal.

Proof required:

- Mutation map with owner, idempotency, transaction, lock, audit, projection, and rollback expectations.
- PostgreSQL write compatibility proof for high-risk mutations.

Suggested test/proof:

- Duplicate submit/retry tests.
- Handler tests for reversal and rollback constraints.
- Ledger/projection/audit reconciliation tests.

Do not fix yet: yes
