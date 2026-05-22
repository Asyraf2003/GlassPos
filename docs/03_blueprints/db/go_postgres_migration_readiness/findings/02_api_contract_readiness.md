# API Contract Readiness Findings

Scope source: Stage 0 baseline and Batch 1B route-surface discovery. This file covers contract gaps, not implementation fixes.

## MRD-003 - API Contract And Error Envelope Are Not Frozen

ID: MRD-003

Status: open

Severity: P1

Area: API contract/readiness

Type: readiness-debt

Evidence:

- `routes/api.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php::__invoke`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierInvoicePaymentProofController.php::__invoke`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierPaymentProofController.php::__invoke`
- `app/Adapters/In/Http/Presenters/JsonPresenter.php`
- `app/Adapters/In/Http/Presenters/Response/JsonResultResponder.php`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`

FACT:

- API controllers manually return JSON envelopes with `success`, `data`, `message`, and `errors`.
- A shared `JsonPresenter`/`JsonResultResponder` exists.
- Scanned API controllers do not universally use the shared responder.
- Existing docs require frozen JSON contract, fixture diff parity, pagination/filter/sort parity, error envelope, auth/access, observability/redaction, and rollout proof.

RISK:

- Go can duplicate Laravel quirks or diverge silently if API contracts are not frozen first.

GAP:

- No OpenAPI/schema contract.
- No cross-implementation contract tests.
- No complete mobile client compatibility matrix.
- Validation and error shape are not proven as a frozen cross-stack contract.

Why it matters for smooth Go/PostgreSQL transition:

The API contract is the boundary that lets Laravel UI, mobile clients, and future Go services move independently.

Recommended direction:

Freeze read-only API contracts first, including response envelope, error codes, auth failures, pagination, sorting, filtering, timestamps, IDs, and integer money semantics.

Proof required:

- API contract document or schema.
- Golden JSON fixtures.
- Compatibility tests for Laravel and future Go implementation.

Suggested test/proof:

- HTTP feature tests asserting exact envelope and error shapes.
- Contract snapshots for list/detail/upload failure paths.
- Consumer compatibility checks for mobile clients.

Do not fix yet: yes

## SEC-1B-B-04 - Read-Only Table JSON Endpoints Need Frozen API Envelope/Rate/Versioning Contract

ID: SEC-1B-B-04

Status: needs-proof

Severity: P2

Area: API contract/readiness

Type: contract-gap

Evidence:

- `routes/web/admin_products.php:20-21`
- `routes/web/admin_suppliers.php:12-13`
- `routes/web/admin_employees.php:17-18`
- `routes/web/admin_employee_debts.php:17-18`
- `routes/web/admin_payrolls.php:13-14`
- `routes/web/admin_expenses.php:21-25`
- `App\Adapters\In\Http\Controllers\Admin\Product\ProductTableDataController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Supplier\SupplierTableDataController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Expense\ExpenseTableDataController::__invoke`
- `App\Adapters\In\Http\Presenters\JsonPresenter`

FACT:

- Covered table endpoints return JSON through presenter-based controllers.
- Query FormRequests exist and commonly restrict `page`, `per_page`, `sort_by`, and `sort_dir`.
- Several table requests restrict `per_page` to `10`.

RISK:

- These endpoints are natural Go read-only candidates, but unfrozen envelopes and query semantics can break UI parity.

GAP:

- No versioned API contract.
- No rate-limit proof.
- No cross-stack response fixture.

Why it matters for smooth Go/PostgreSQL transition:

Read-only table endpoints are low-risk candidates only after query and response contracts are frozen and tested.

Recommended direction:

Create a read-model API contract registry for table endpoints.

Proof required:

- Query parameter contract.
- JSON envelope and pagination contract.
- Error behavior contract.
- UI compatibility fixtures.

Suggested test/proof:

- Snapshot or contract tests for table JSON responses.
- Shadow compare between Laravel and Go read models.
- Rate/volume tests.

Do not fix yet: yes

## SEC-1B-B-06 - Dashboard Analytics Uses Raw Request And Needs Read Contract

ID: SEC-1B-B-06

Status: needs-proof

Severity: P2

Area: API contract/readiness

Type: contract-gap

Evidence:

- `routes/web/dashboard.php:13-18`
- `App\Adapters\In\Http\Controllers\Admin\AdminDashboardAnalyticsPayloadController::__invoke`

FACT:

- Admin dashboard analytics is an authenticated admin JSON endpoint.
- The controller reads `month` from raw `Request` and passes string or null to `GetAdminDashboardAnalyticsHandler`.

RISK:

- Analytics responses may become read-only Go candidates, and unfrozen query/response contracts can cause UI/backend mismatch.

GAP:

- No FormRequest or explicit month format validation read.
- No response contract.
- No rate/volume proof.

Why it matters for smooth Go/PostgreSQL transition:

Dashboard analytics is read-only but business-sensitive and should be typed before shadow comparison.

Recommended direction:

Register dashboard analytics as a read-model contract with explicit month format, envelope, and parity expectations.

Proof required:

- Month query contract.
- JSON response fixture.
- MySQL/PostgreSQL parity proof for analytics totals.

Suggested test/proof:

- Valid, missing, and invalid month feature tests.
- Snapshot tests for analytics JSON.
- Shadow compare for dashboard metrics.

Do not fix yet: yes

## Cross-Cutting API/Mobile Contract Notes

Status: non-register note

Area: API contract/readiness

Type: contract-gap summary

Evidence:

- `routes/api.php`
- `routes/web/admin_products.php`
- `routes/web/admin_suppliers.php`
- `routes/web/admin_employees.php`
- `routes/web/admin_employee_debts.php`
- `routes/web/admin_payrolls.php`
- `routes/web/admin_expenses.php`
- `routes/web/dashboard.php`
- Stage 0 Batch 1B findings `SEC-1B-03`, `SEC-1B-B-04`, and `SEC-1B-B-06`

FACT:

- Mobile and web JSON surfaces exist.
- Some JSON endpoints are read-only table/data endpoints and may be suitable for future Go read-only shadowing.

RISK:

- Client-visible behavior may drift if validation, error shape, pagination, sorting, and response envelopes are not frozen.

GAP:

- Response envelope not frozen.
- Validation and error shape not frozen.
- Mobile compatibility contract not complete.
- Read-only table endpoints do not yet have versioning/rate contracts.

Why it matters for smooth Go/PostgreSQL transition:

Contract stability is prerequisite for replacing or shadowing Laravel endpoints with Go endpoints.

Recommended direction:

Start with read-only table/data contracts and mobile auth/upload failure contracts before any write migration.

Proof required:

- Contract fixtures for request, success, validation failure, auth failure, and domain failure paths.

Suggested test/proof:

- Golden JSON fixtures and compatibility tests.
- Shadow compare for read-only endpoints.

Do not fix yet: yes
