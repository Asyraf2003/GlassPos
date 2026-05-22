# Security Public Surface Findings

Scope source: Stage 0 baseline and Batch 1B route-surface discovery. This is not a final vulnerability report.

## MRD-008 - Security Public Surface Needs Route/Auth/Rate/Deployment Proof

ID: MRD-008

Status: needs-proof

Severity: P1

Area: Security/public surface

Type: readiness-debt

Evidence:

- `routes/api.php`
- `routes/web.php`
- `app/Adapters/In/Http/Requests/Api/V1/Auth/MobileApiLoginRequest.php::rules`
- `app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php::__invoke`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/Support/MobileSupplierPaymentProofUploadRequest.php::validate`
- Stage 0 command evidence: `rg "rate|throttle|Throttle|Limit|RateLimiter" routes app/Adapters/In/Http app/Providers config -n`

FACT:

- `/api/v1/auth/login` is a public API route.
- Mobile API login validates email, password, and device name.
- Mobile supplier payment proof upload validates file count, MIME extension set, and 2 MB max.

RISK:

- Public login and upload surfaces become higher risk when endpoints are mirrored, proxied, or replaced during cross-stack migration.

GAP:

- No full public route matrix.
- No brute-force/rate-limit test proof for mobile API login.
- No production proxy, CORS, WAF, cookie/session, storage, or deployment proof.

Why it matters for smooth Go/PostgreSQL transition:

Security behavior must be explicit before Laravel routes are mirrored or moved behind Go API boundaries.

Recommended direction:

Keep a public-surface register with auth, role, throttle, validation, upload behavior, envelope, and deployment assumptions.

Proof required:

- Public route inventory.
- Login rate-limit proof.
- Upload/storage proof.
- Deployment security evidence.

Suggested test/proof:

- HTTP auth failure and permission tests.
- Rate-limit tests for login.
- Upload validation and safe response tests.

Do not fix yet: yes

## SEC-1B-01 - Login/Auth Surfaces Have No Route-Level Rate-Limit Proof

ID: SEC-1B-01

Status: needs-proof

Severity: P1

Area: Security/public surface

Type: proof-gap

Evidence:

- `routes/web/auth.php:10-15`
- `routes/api.php:16-18`
- `App\Adapters\In\Http\Controllers\Auth\AuthenticateController`
- `App\Adapters\In\Http\Controllers\Api\V1\Auth\LoginMobileApiController`
- Discovery command: `rg "RateLimiter|throttle:|ThrottleRequests|Limit::|assertTooManyRequests|429|too many" app bootstrap config routes tests`

FACT:

- Web login is registered as `POST /login`.
- Mobile API login is registered as `POST /api/v1/auth/login`.
- Batch 1B did not find route-level throttle proof for these login surfaces.

RISK:

- Migration can accidentally change rate-limit semantics if protection is implicit or external.

GAP:

- No feature test proof for login throttling.
- No proxy/WAF/rate-limiter deployment proof.

Why it matters for smooth Go/PostgreSQL transition:

Auth behavior must remain stable before Go API clients or shadow traffic are introduced.

Recommended direction:

Register login as a public-surface contract entry with throttle, lockout, error envelope, and deployment assumptions.

Proof required:

- Route-level or deployment-level rate-limit policy proof.
- HTTP tests for repeated failed login attempts.
- API error-envelope proof for throttling.

Suggested test/proof:

- Web login throttle feature test.
- Mobile API login throttle feature test.
- External limiter configuration evidence if protection is outside Laravel.

Do not fix yet: yes

## SEC-1B-02 - Legacy Transaction Writes Need Explicit Migration Auth/Capability Matrix

ID: SEC-1B-02

Status: needs-proof

Severity: P1

Area: Security/API readiness

Type: readiness-debt

Evidence:

- `routes/web/note.php:34-37`
- `routes/web/note.php:60-65`
- `routes/web/note.php:70-96`
- `routes/web/procurement.php:9-15`
- `routes/web/product_catalog.php:9-15`
- `app/Adapters/In/Http/Middleware/IdentityAccess/EnsureTransactionEntryAllowed.php::handle`
- `app/Application/IdentityAccess/Policies/TransactionEntryPolicy.php::decide`

FACT:

- Several transaction writes use `transaction.entry` or `EnsureTransactionEntryAllowed`.
- `TransactionEntryPolicy::decide` allows cashier actors and admin actors with active admin transaction capability.

RISK:

- A broad Laravel middleware gate may be hard to translate into route-specific Go capability contracts.

GAP:

- No complete route-to-capability matrix for transaction-entry writes.
- No per-mutation migration authorization contract.

Why it matters for smooth Go/PostgreSQL transition:

Go endpoints need typed, explicit auth and capability rules.

Recommended direction:

Build an auth/capability matrix for transaction-entry routes before equivalent Go write endpoints are considered.

Proof required:

- Route-by-route capability matrix.
- Tests for cashier allow, admin-with-capability allow, admin-without-capability deny, unknown actor deny.

Suggested test/proof:

- Policy tests for `TransactionEntryPolicy::decide`.
- Feature tests per mutation group.
- Contract fixtures for Go-compatible decisions.

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
- `App\Adapters\In\Http\Controllers\Api\V1\Procurement\UploadMobileApiSupplierPaymentProofController::__invoke`
- `App\Adapters\In\Http\Controllers\Api\V1\Procurement\Support\MobileSupplierPaymentProofUploadRequest`
- `App\Application\Procurement\Services\Mobile\UploadSupplierInvoicePaymentProofOperation::execute`
- `App\Application\Procurement\Services\Mobile\SupplierInvoicePaymentProofRecorder::record`

FACT:

- Mobile supplier proof upload routes are authenticated by `mobile.api.auth`.
- The supplier invoice proof route records payment-side effects through Laravel.
- Upload validation exists.

RISK:

- This endpoint is not only an attachment upload; it touches procurement/finance state.

GAP:

- No route-level rate-limit proof.
- No idempotency key or retry-safety proof.
- No full mobile client contract.

Why it matters for smooth Go/PostgreSQL transition:

Mobile write endpoints are likely API candidates, but this one changes finance/procurement state and needs stronger proof before movement.

Recommended direction:

Keep Laravel ownership until upload, storage, payment side effects, idempotency, audit, and response shape are mapped.

Proof required:

- Upload size/type/storage proof.
- Idempotency or retry-safety proof.
- Payment side-effect and audit proof.
- Mobile API error-envelope proof.

Suggested test/proof:

- Valid upload, invalid upload, duplicate retry, unauthorized access, and missing proof tests.
- Golden response fixtures.
- Audit/projection reconciliation test.

Do not fix yet: yes

## SEC-1B-B-02 - Reporting/Export Routes Need Export Governance And Rate/Volume Proof

ID: SEC-1B-B-02

Status: needs-proof

Severity: P1

Area: Security/public surface

Type: readiness-debt

Evidence:

- `routes/web/admin_reporting.php:31-105`
- `App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionCashLedgerExcelExportController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionCashLedgerPdfExportController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Reporting\PayrollReportExcelExportController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Reporting\EmployeeDebtReportExcelExportController::__invoke`
- `App\Adapters\In\Http\Requests\Reporting\TransactionCashLedgerPageRequest::rules`
- `App\Adapters\In\Http\Requests\Reporting\PayrollReportPageRequest::rules`
- `App\Adapters\In\Http\Requests\Reporting\EmployeeDebtReportPageRequest::rules`

FACT:

- Reporting routes are admin-authenticated.
- Reports include finance, payroll, employee debt, supplier payable, inventory stock value, transaction, and operational data.
- Some export controllers implement maximum range guards.

RISK:

- Export migration can expose sensitive data or change totals, ranges, grouping, and generated output.

GAP:

- No route-level throttle proof.
- No download governance, audit, or per-report volume policy proof.
- Full export body-by-body proof not completed.

Why it matters for smooth Go/PostgreSQL transition:

Reports may be read-only migration candidates, but they need parity and data access governance before Go/PostgreSQL reporting can be trusted.

Recommended direction:

Freeze report contracts, date filters, volume limits, and parity fixtures before report ownership changes.

Proof required:

- Report-by-report input/output contract.
- Export range and volume policy.
- Download/audit governance decision.
- MySQL vs PostgreSQL report parity fixtures.

Suggested test/proof:

- Golden fixtures for report pages and exports.
- Shadow compare for report totals.
- Export authorization and volume-control tests.

Do not fix yet: yes

## SEC-1B-B-03 - Broad Admin Page Gate Needs Route-Specific Capability Proof

ID: SEC-1B-B-03

Status: needs-proof

Severity: P1

Area: Security/API readiness

Type: proof-gap

Evidence:

- `routes/web/admin_products.php:19-60`
- `routes/web/admin_suppliers.php:11-25`
- `routes/web/admin_employees.php:16-29`
- `routes/web/admin_employee_debts.php:16-44`
- `routes/web/admin_payrolls.php:12-29`
- `routes/web/admin_expenses.php:20-61`
- `routes/web/admin_reporting.php:31-105`
- `routes/web/admin_audit_logs.php:8-13`
- `app/Adapters/In/Http/Middleware/IdentityAccess/EnsureAdminPageAccess.php::handle`
- `app/Application/IdentityAccess/Policies/AdminPageAccessPolicy.php::decide`

FACT:

- Covered admin routes commonly use `auth` plus `admin.page`.
- `AdminPageAccessPolicy::decide` allows admin actors and rejects non-admin actors.

RISK:

- Broad admin-only behavior may be insufficient as a future API scope/capability contract for stock, finance, payroll, report, and audit surfaces.

GAP:

- No route-specific capability matrix.
- No proof that every admin should access every high-value covered route.

Why it matters for smooth Go/PostgreSQL transition:

Route authorization must become portable and explicit before routes are split across Laravel and Go.

Recommended direction:

Inventory admin routes by domain and decide whether `admin.page` is enough or domain-specific capability is required.

Proof required:

- Route-to-role/capability matrix.
- Policy tests for high-value groups.
- Migration contract for equivalent Go auth decisions.

Suggested test/proof:

- Admin allowed and non-admin denied tests per high-value group.
- Capability fixtures for future API scopes.

Do not fix yet: yes

## SEC-1B-B-05 - Push Subscription Routes Need Rate/Origin/Browser Lifecycle Proof

ID: SEC-1B-B-05

Status: needs-proof

Severity: P2

Area: Security/public surface

Type: proof-gap

Evidence:

- `routes/web/push_notifications.php:9-18`
- `App\Adapters\In\Http\Controllers\PushNotification\StorePushSubscriptionController::__invoke`
- `App\Adapters\In\Http\Controllers\PushNotification\DeletePushSubscriptionController::__invoke`
- `App\Adapters\In\Http\Requests\PushNotification\StorePushSubscriptionRequest::rules`
- `App\Adapters\In\Http\Requests\PushNotification\DeletePushSubscriptionRequest::rules`

FACT:

- Push subscription store/delete routes require `auth`.
- Store and delete validation exists.
- Controllers return JSON `data.stored` or `data.deleted`.

RISK:

- Browser push lifecycle behavior can drift if moved behind Go API without contract proof.

GAP:

- No route-level rate proof.
- No origin/CORS/browser lifecycle proof.
- No idempotent delete proof.

Why it matters for smooth Go/PostgreSQL transition:

Push subscription routes are small but client-facing JSON mutations that need stable request/response and browser behavior.

Recommended direction:

Treat push subscription endpoints as lightweight API contract candidates.

Proof required:

- Store/delete contract.
- Rate/origin assumptions.
- Browser push compatibility proof.

Suggested test/proof:

- Store, delete, duplicate store, invalid endpoint, and unauthenticated access tests.
- Browser-level subscription lifecycle smoke proof.

Do not fix yet: yes

## SEC-1B-B-07 - Audit Log Page Needs Read Governance/Access Logging Proof

ID: SEC-1B-B-07

Status: needs-proof

Severity: P2

Area: Audit/security readiness

Type: proof-gap

Evidence:

- `routes/web/admin_audit_logs.php:8-13`
- `App\Adapters\In\Http\Controllers\Admin\AuditLog\AuditLogIndexPageController::__invoke`
- `App\Application\Audit\Services\AuditLogIndexPageData::listForAdmin`

FACT:

- Audit log page is behind `web`, `auth`, `admin.page`, and `app.shell`.
- The controller trims query parameter `q`, calls `listForAdmin($search, 20)`, and uses a paginator.

RISK:

- Audit log visibility itself can be sensitive and can drift across Laravel/Go/PostgreSQL reads.

GAP:

- No audit-log read access logging proof.
- No retention/export policy.
- No route-specific audit-log capability proof.

Why it matters for smooth Go/PostgreSQL transition:

Audit readiness requires write-side audit facts and controlled read-side access.

Recommended direction:

Document audit-log read behavior as a governance contract before moving audit queries or reports.

Proof required:

- Audit-log read policy.
- Retention and export policy.
- Access logging or accepted-risk decision.

Suggested test/proof:

- Admin allowed and non-admin denied tests.
- Query/filter tests for audit-log search.
- Governance decision record for audit-log read access.

Do not fix yet: yes
