# Domain Mutation Readiness Findings

Scope source: Stage 0 baseline and Batch 1B route-surface discovery. This file is pending a dedicated Domain Mutation Readiness batch.

## MRD-001 - Finance, Stock, Payment, Refund Write Ownership Is Not Safe For Early Go Ownership

ID: MRD-001

Status: open

Severity: P0

Area: Finance/stock/payment/refund write readiness

Type: readiness-debt

Evidence:

- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `routes/api.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierInvoicePaymentProofController.php::__invoke`
- `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofRecorder.php::record`
- `app/Application/Procurement/Services/Mobile/UploadSupplierInvoicePaymentProofOperation.php::execute`
- `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofPreflight.php::prepare`
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`

FACT:

- Mobile supplier invoice proof upload records supplier payment and proof attachment through Laravel.
- The recorder writes supplier payment, attachment rows, audit log, and projection sync.
- The previous transition audit excludes financial, stock, refund, revision, reversal, payroll, and allocation writes from first Go ownership.

RISK:

- Moving these writes to Go before mutation parity, locks, idempotency, audit, and rollback proof can create split-brain finance/stock state.

GAP:

- No complete write mutation map.
- No Go/Laravel golden parity suite.
- No PostgreSQL write parity suite.

Why it matters for smooth Go/PostgreSQL transition:

Write ownership changes are the highest blast-radius migration step.

Recommended direction:

Keep Go read-only first and register every write mutation with owner, transaction boundary, lock target, idempotency requirement, audit facts, and rollback behavior.

Proof required:

- Complete write mutation map for payment/refund/allocation/procurement/inventory/note revision/employee finance.
- Golden input/output fixtures.
- Lock/idempotency/audit contract per mutation.
- Shadow or dual-run strategy with rollback.

Suggested test/proof:

- Contract tests for every write command/result/error envelope.
- Laravel-on-PostgreSQL write tests for critical flows.
- Concurrent write characterization where feasible.
- Ledger/projection/audit reconciliation after write runs.

Do not fix yet: yes

## SEC-1B-03 - Mobile Supplier Proof Upload Has Upload/Rate/Idempotency Proof Gaps

ID: SEC-1B-03

Status: needs-proof

Severity: P1

Area: API contract/readiness

Type: readiness-debt

Evidence:

- `routes/api.php:36-40`
- `App\Adapters\In\Http\Controllers\Api\V1\Procurement\UploadMobileApiSupplierInvoicePaymentProofController::__invoke`
- `App\Application\Procurement\Services\Mobile\UploadSupplierInvoicePaymentProofOperation::execute`
- `App\Application\Procurement\Services\Mobile\SupplierInvoicePaymentProofRecorder::record`

FACT:

- The mobile supplier invoice proof route is authenticated and records payment-side effects.

RISK:

- Repeated mobile retries can become unsafe without idempotency proof.

GAP:

- No route-level rate proof.
- No idempotency/retry-safety proof.
- No full mobile contract.

Why it matters for smooth Go/PostgreSQL transition:

Procurement/payment upload writes must not move before request, storage, payment, audit, and retry contracts are frozen.

Recommended direction:

Keep this under Laravel ownership until domain mutation proof is complete.

Proof required:

- Upload/payment mutation contract.
- Storage rollback contract.
- Audit/projection contract.

Suggested test/proof:

- Duplicate upload retry test.
- Unauthorized and invalid upload tests.
- Reconciliation proof after upload-created payment.

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

FACT:

- Note/payment/refund/procurement writes exist on covered routes.

RISK:

- These writes combine finance, stock, note state, and revision behavior.

GAP:

- No per-mutation matrix for auth, validation, transaction, idempotency, audit, projection, and rollback.

Why it matters for smooth Go/PostgreSQL transition:

Mutation-by-mutation proof is required before deciding any Go write ownership.

Recommended direction:

Register every mutation as Laravel-owned until proven otherwise.

Proof required:

- Command/result/error map.
- Transaction/lock/idempotency/audit map.
- PostgreSQL parity proof.

Suggested test/proof:

- Golden tests for payment, refund, allocation, procurement receipt, and note revision.

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
- `App\Adapters\In\Http\Controllers\Admin\EmployeeDebt\StoreEmployeeDebtPaymentController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Payroll\StorePayrollController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Expense\StoreExpenseController::__invoke`

FACT:

- Stock adjustment/reversal, payroll, employee debt/payment/reversal, and expense mutation routes were identified in Batch 1B-B.

RISK:

- These domains affect inventory, payroll, debt, expenses, and reversals.

GAP:

- No route-level idempotency, transaction, rollback, or reversal parity proof.

Why it matters for smooth Go/PostgreSQL transition:

These write domains need explicit contracts before any API or DB ownership change.

Recommended direction:

Prioritize them in a future Domain Mutation Readiness batch.

Proof required:

- Mutation map for:
  - note/payment/refund
  - procurement/supplier payment proof
  - stock adjustment/reversal
  - payroll disbursement/reversal
  - employee debt/payment/reversal
  - expense mutation

Suggested test/proof:

- Duplicate submit tests.
- Reversal and rollback tests.
- Ledger/projection/audit reconciliation.

Do not fix yet: yes
