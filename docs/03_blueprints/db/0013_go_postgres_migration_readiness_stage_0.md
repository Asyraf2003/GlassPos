# Go API + PostgreSQL Migration Readiness Blueprint - Stage 0 Discovery Baseline

Status: Stage 0 discovery baseline  
Scope: Laravel/MySQL readiness debt for gradual Go API and PostgreSQL transition  
Date: 2026-05-21  
Owner: HyperPOS  

## 1. Purpose

This document is a Stage 0 baseline for discovery only.

It maps early critical problems, likely risks, proof gaps, transition work, and areas that already show useful structure for a future gradual Go API and PostgreSQL migration.

This is not a final migration plan, not a patch plan, not remediation execution, and not a claim that the system is migration-ready.

## 2. Migration Goal

Make the current Laravel/MySQL system mature enough that a future transition to Go API and PostgreSQL can happen gradually, smoothly, audit-ready, rollback-friendly, and without a large rewrite.

The intended migration shape is conservative:

- keep Laravel and MySQL as the source of truth until proof exists;
- freeze and test contracts before moving ownership;
- introduce Go and PostgreSQL behind parity, shadow, and rollback controls;
- treat finance, stock, payment, refund, audit, and reversal flows as high-risk write boundaries.

## 3. Non-Goals

- This is not a rewrite plan.
- This is not patch execution.
- This is not a final PostgreSQL cutover plan.
- This is not a Go implementation plan.
- This is not a claim that the system is ready.
- This is not an exhaustive audit.

## 4. Readiness Vocabulary

Migration Readiness Debt:
Current Laravel/MySQL design, implementation, schema, contract, or proof weakness that would make a future Go API/PostgreSQL transition expensive, regression-prone, unsafe, or likely to require a large rewrite.

Transition Work:
Natural work caused by changing stack, not necessarily a design problem in the current system. Examples: creating a Go service, PostgreSQL adapter, migration runner, contract test harness, or shadow compare job.

Accepted Risk:
A known risk that remains open by explicit decision, with scope, owner, proof status, and rollback expectation recorded.

Proof Gap:
A claim that cannot yet be made because no test, runtime run, audit output, fixture parity, or source-level proof has been read.

Contract Gap:
An API, DTO, command, result, error, auth, idempotency, audit, or schema contract that is not frozen or not consistently enforced.

Runtime Gap:
A behavior that may be designed or documented, but has not been proven in the target runtime such as PostgreSQL, Go, production-like proxy/session settings, or browser flow.

Data Parity Gap:
No proof that the same source data produces the same rows, amounts, statuses, projections, reports, API response, or audit facts across old and new paths.

## 5. Severity Model P0-P4

P0:
Absolute blocker for smooth migration, data correctness, security, finance, stock, payment, refund, audit, rollback, or direct cutover.

P1:
High-risk blocker that makes migration expensive or regression-prone.

P2:
Migration friction that should be resolved before cutover or Go ownership.

P3:
Maintainability debt that slows audit, porting, or onboarding.

P4:
Nice-to-have.

## 6. Initial Readiness Estimate

These numbers are conservative discovery estimates, not final proof.

- Blueprint maturity: 45%
- Security/API readiness coverage: 30%
- DB/PostgreSQL readiness coverage: 35%
- Architecture/system readiness coverage: 45%
- Blade/JS readiness coverage: 20%
- Test/proof readiness coverage: 30%
- Overall smooth transition readiness: 25%

Reasons:

- Existing ADRs and DB blueprints are strong enough to guide Stage 0 classification.
- Hexagonal structure exists and `php scripts/audit-hex.php` reports OK, but this is structural proof only.
- PostgreSQL runtime migration, seed/import, parity harness, and Laravel-on-PostgreSQL suite were not run.
- API responses exist but are not yet treated as frozen cross-stack contracts.
- Blade/JS flows remain heavily involved in finance/procurement payload composition.

## 7. Evidence Read

### Read directly

- `docs/01_standards/README.md`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`
- `docs/03_blueprints/db/README.md`
- `docs/03_blueprints/db/0002_mysql_postgresql_crud_readiness_blueprint.md`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `docs/03_blueprints/security/0007_payment_concurrency.md`
- `docs/03_blueprints/security/0008_payment_concurrency_dod.md`
- `database/migrations/README.md`
- `routes/api.php`
- `routes/web.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php`
- `app/Adapters/In/Http/Requests/Api/V1/Auth/MobileApiLoginRequest.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierInvoicePaymentProofController.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierPaymentProofController.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/Support/MobileSupplierPaymentProofUploadRequest.php`
- `app/Application/Procurement/UseCases/UploadSupplierInvoicePaymentProofHandler.php`
- `app/Application/Procurement/UseCases/AttachSupplierPaymentProofHandler.php`
- `app/Application/Procurement/Services/Mobile/UploadSupplierInvoicePaymentProofOperation.php`
- `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofPreflight.php`
- `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofRecorder.php`
- `app/Application/Procurement/Services/AttachSupplierPaymentProofTransaction.php`
- `app/Ports/Out/TransactionManagerPort.php`
- `app/Adapters/Out/Persistence/DatabaseTransactionManagerAdapter.php`
- `app/Ports/Out/AuditEventWriterPort.php`
- `public/assets/static/js/pages/cashier-note-payment.js`
- `public/assets/static/js/pages/admin-procurement-create.js`

### Scanned by rg/tree/find

- `find app/Core -maxdepth 2 -type d -print`
- `find app/Application -maxdepth 2 -type d -print`
- `find app/Ports -maxdepth 3 -type d -print`
- `find app/Adapters/In/Http -maxdepth 3 -type d -print`
- `find app/Adapters/Out -maxdepth 3 -type d -print`
- `find database/migrations -maxdepth 1 -type f -name '*.php' -print`
- `find resources/views -maxdepth 2 -type d -print`
- `find resources/views/shared/notes -maxdepth 2 -type f -print`
- `find resources/views/admin/procurement -maxdepth 2 -type f -print`
- `find resources/views/admin/notes -maxdepth 2 -type f -print`
- `find resources/views/cashier/notes -maxdepth 2 -type f -print`
- `find tests -maxdepth 2 -type d -print`
- `find tests/Feature/MobileApi -maxdepth 2 -type f -print`
- `find tests/Feature/Database -maxdepth 2 -type f -print`
- `find tests/Feature/Payment -maxdepth 2 -type f -print`
- `find tests/Feature/Procurement -maxdepth 2 -type f -print`
- `find tests/Arch -maxdepth 2 -type f -print`
- `rg "PostgreSQL|postgres|Go API|Go service|migration readiness|readiness" docs/02_architecture/adr docs/03_blueprints/db docs/03_blueprints/security -n`
- `rg "DB::raw|selectRaw|whereRaw|orderByRaw|havingRaw|DATE\\(|CONCAT\\(|GREATEST\\(|LEAST\\(|lockForUpdate|updateOrInsert|insertGetId|insertOrIgnore" app database/migrations -n`
- `rg "idempotency|idempotency_key|Idempotency" app database/migrations tests docs -n`
- `rg "success' =>|errors' =>|message' =>|response\\(\\)->json|JsonPresenter|Result" app/Adapters/In/Http/Controllers/Api app/Adapters/In/Http/Presenters app/Application/MobileApi -n`
- `rg "rate|throttle|Throttle|Limit|RateLimiter" routes app/Adapters/In/Http app/Providers config -n`
- `rg "<script|@json|Js::from|json_encode|innerHTML|insertAdjacentHTML|hidden|type=\\"hidden\\"|data-|window\\." resources/views -n`
- `rg "innerHTML|insertAdjacentHTML|outerHTML|template|dataset|JSON\\.parse|window\\.[A-Za-z0-9_]+Config|fetch\\(" public/assets/static/js resources/views -n`
- `php scripts/audit-hex.php`
- `git status --short`

### Not read yet

- Full route auth and permission matrix for every web/API route.
- Full Blade and JavaScript sink audit.
- Full controller/form request validation inventory.
- Full application use case mutation map.
- Full DB migration source review line by line.
- Full raw SQL portability classification.
- Full PostgreSQL runtime migration.
- Full seed/import and data reconciliation.
- Full API contract tests and mobile client compatibility.
- Full browser QA.
- Full test suite.
- Deployment proxy, CORS, WAF, session, cookie, and storage config in production.

## 8. Initial Findings Register

### MRD-001 - Finance, Stock, Payment, Refund Write Ownership Is Not Safe For Early Go Ownership

ID: MRD-001

Status: open

Severity: P0

Area: Finance/stock/payment/refund write readiness

Type: readiness-debt

Evidence:

- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md` sections "Write migration only after parity..." and "Not recommended as first Go write targets".
- `routes/api.php` exposes mobile supplier payment proof upload routes.
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierInvoicePaymentProofController.php::__invoke`
- `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofRecorder.php::record`
- `app/Application/Procurement/Services/Mobile/UploadSupplierInvoicePaymentProofOperation.php::execute`
- `app/Application/Procurement/Services/Mobile/SupplierInvoicePaymentProofPreflight.php::prepare`
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`

FACT:

- Mobile supplier invoice proof upload records a supplier payment for the invoice outstanding amount and attaches proof in the Laravel application path.
- The recorder writes supplier payment, attachment rows, audit log, and projection sync.
- ADR-0022 treats same-note finance mutation concurrency as transaction-integrity risk, not UI risk.
- The previous transition audit explicitly excludes financial, stock, refund, revision, reversal, payroll, and allocation writes from first Go ownership.

RISK:

- Moving these writes to Go before complete mutation parity, lock behavior, idempotency, audit, and rollback proof can create split-brain finance/stock state.

GAP:

- No complete write mutation map was read.
- No Go/Laravel golden parity suite was read.
- No PostgreSQL write parity suite was run.

Why it matters for smooth Go/PostgreSQL transition:

Write ownership changes are the highest blast-radius migration step. Finance, stock, payment, refund, audit, and reversal writes must not move before their invariants are frozen and independently proven.

Recommended direction:

Keep Go read-only first. Register every write mutation with owner, transaction boundary, lock target, idempotency requirement, audit facts, and rollback behavior before any Go write is implemented.

Proof required:

- Complete write mutation map for payment/refund/allocation/procurement/inventory/note revision/employee finance.
- Golden input/output fixtures for current Laravel write behavior.
- Lock/idempotency/audit contract per mutation.
- Shadow or dual-run strategy with rollback.

Suggested test/proof:

- Contract tests for every write command/result/error envelope.
- Laravel-on-PostgreSQL write tests for critical flows.
- Concurrent write characterization where feasible.
- Ledger/projection/audit reconciliation after write runs.

Do not fix yet: yes

### MRD-002 - PostgreSQL Fresh Migration And Runtime Parity Are Not Proven

ID: MRD-002

Status: needs-proof

Severity: P0

Area: PostgreSQL/schema/query readiness

Type: gap

Evidence:

- `database/migrations/README.md` says PostgreSQL transition requires backup, schema snapshot, PostgreSQL fresh migration, data import mapping, row counts, rupiah reconciliation, payment/refund/allocation reconciliation, inventory/projection reconciliation, audit/version/projection rebuild, app test suite on PostgreSQL, and cutover/rollback runbook.
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md` section "Required Proof Before PostgreSQL Cutover".
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md` says PostgreSQL is not active and a full migration plan remains a gap.
- `find database/migrations -maxdepth 1 -type f -name '*.php' -print` found a broad migration set, but no PostgreSQL runtime execution was performed in this discovery.

FACT:

- Existing docs explicitly forbid claiming PostgreSQL production cutover safe without runtime proof.
- PostgreSQL is a future target, not the active runtime.

RISK:

- MySQL-valid migrations, indexes, unsigned assumptions, date functions, and raw SQL may not preserve behavior on PostgreSQL.

GAP:

- No PostgreSQL fresh migration proof was read or run.
- No PostgreSQL seed/import proof was read or run.
- No PostgreSQL parity harness was read.

Why it matters for smooth Go/PostgreSQL transition:

Without PostgreSQL runtime proof, any Go API backed by PostgreSQL would rely on unproven schema, query, timestamp, JSON, lock, and constraint behavior.

Recommended direction:

Build a PostgreSQL compatibility harness before any production cutover plan. Keep it as proof infrastructure, not application rewrite.

Proof required:

- Fresh PostgreSQL migration output.
- Fixture/seed load output.
- Row-count and rupiah reconciliation.
- Payment/refund/allocation/inventory/projection/audit parity report.
- Rollback runbook proof.

Suggested test/proof:

- Dedicated PostgreSQL CI job or local target.
- Schema diff and migration smoke tests.
- Query parity tests for high-risk reports/projections.

Do not fix yet: yes

### MRD-003 - API Contract And Error Envelope Are Not Frozen

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
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md` section "Required Proof Before Go API Production Use".

FACT:

- API controllers manually return JSON envelopes with `success`, `data`, `message`, and `errors`.
- A shared `JsonPresenter`/`JsonResultResponder` exists, but the scanned API controllers do not universally use it.
- Existing docs require frozen JSON contract, Laravel/Go fixture diff parity, pagination/filter/sort parity, error envelope, auth/access, observability/redaction, and rollout proof before publishing Go API endpoints.

RISK:

- A Go API implemented before contract freeze can duplicate Laravel response quirks or diverge silently from mobile/client expectations.

GAP:

- No OpenAPI/schema contract was read.
- No cross-implementation contract test suite was read.
- No complete mobile client compatibility matrix was read.

Why it matters for smooth Go/PostgreSQL transition:

The API contract is the boundary that lets Laravel UI, mobile clients, and future Go services move independently. If it is not frozen, every extraction becomes a behavior audit.

Recommended direction:

Freeze read-only API contracts first, including response envelope, error codes, auth failures, pagination, sorting, filtering, timestamps, IDs, and money integer semantics.

Proof required:

- API contract document or schema.
- Golden JSON fixtures for current Laravel endpoints.
- Compatibility tests that a Go implementation must pass.

Suggested test/proof:

- HTTP feature tests that assert exact envelope and error shapes.
- Contract snapshots for list/detail/upload failure paths.
- Consumer compatibility checks for mobile API clients.

Do not fix yet: yes

### MRD-004 - Raw SQL And Query Builder Portability Need Classification

ID: MRD-004

Status: needs-proof

Severity: P1

Area: PostgreSQL/schema/query readiness

Type: readiness-debt

Evidence:

- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md` sections "PostgreSQL Risks" and "Raw SQL Risks".
- `rg "DB::raw|selectRaw|whereRaw|orderByRaw|havingRaw|DATE\\(|CONCAT\\(|GREATEST\\(|LEAST\\(|lockForUpdate|updateOrInsert|insertGetId|insertOrIgnore" app database/migrations -n`
- Example paths from scan:
  - `app/Adapters/Out/Procurement/Concerns/ProcurementInvoicePolicySqlFragments.php`
  - `app/Adapters/Out/Note/Queries/NoteHistoryLegacyLineSummarySubquery.php`
  - `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/CashInPerDayQuery.php`
  - `app/Adapters/Out/Reporting/DatabasePayrollReportingSourceReaderAdapter.php`
  - `app/Adapters/Out/MobileApi/DatabaseMobileApiTokenStoreAdapter.php`
  - `database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php`

FACT:

- Raw expressions exist in adapters, reporting queries, projection sources, and migrations.
- Query builder operations such as `lockForUpdate`, `updateOrInsert`, `insertGetId`, and `insertOrIgnore` appear in persistence code.
- Concentration in adapters is useful for future replacement, but not proof of PostgreSQL parity.

RISK:

- Date extraction, string functions, aggregate expressions, window functions, upsert semantics, insert ID behavior, and lock behavior may differ between MySQL and PostgreSQL.

GAP:

- No query-by-query PostgreSQL compatibility classification was completed.
- No PostgreSQL query parity suite was run.

Why it matters for smooth Go/PostgreSQL transition:

Read-only Go API and PostgreSQL cutover both depend heavily on projection/report query correctness. Portability bugs can look like data discrepancies instead of obvious crashes.

Recommended direction:

Create a compatibility matrix for raw SQL/query-builder constructs before touching implementation.

Proof required:

- Query inventory with status: portable, needs rewrite, needs PostgreSQL test, or accepted risk.
- PostgreSQL execution proof for high-risk read/report/projection paths.

Suggested test/proof:

- Query parity tests on MySQL and PostgreSQL fixtures.
- Report/projection snapshot comparisons.
- Lock/upsert behavior characterization.

Do not fix yet: yes

### MRD-005 - Transaction Boundary Contract Is Too Thin For Cross-Stack Ownership

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
- ADR-0022 requires specific lock target, post-lock recalculation, atomicity, and source coverage for finance-sensitive flows.

RISK:

- A thin transaction port does not itself define isolation level, retry behavior, deadlock behavior, lock target policy, nested transaction rules, or after-commit side effects.
- A future Go service could reimplement the same operation with subtly different transaction semantics.

GAP:

- No global transaction boundary map was read.
- No transaction manager contract tests were read.
- No deadlock/retry/isolation policy was read.

Why it matters for smooth Go/PostgreSQL transition:

Cross-stack write migration needs transaction semantics that can be implemented in both Laravel and Go with the same correctness guarantees.

Recommended direction:

Document transaction boundary contracts per high-risk use case before moving write ownership.

Proof required:

- Per-use-case transaction boundary map.
- Lock target and post-lock recalculation proof.
- Deadlock/retry and after-commit policy decisions.

Suggested test/proof:

- Characterization tests around rollback and partial failure.
- Concurrency tests where feasible.
- File/storage cleanup tests for upload-plus-DB transactions.

Do not fix yet: yes

### MRD-006 - Idempotency Coverage Is Partial And Not A Global Write Contract

ID: MRD-006

Status: open

Severity: P1

Area: Transaction/idempotency/audit readiness

Type: readiness-debt

Evidence:

- `rg "idempotency|idempotency_key|Idempotency" app database/migrations tests docs -n`
- `database/migrations/2026_05_13_000300_create_note_revision_surplus_refund_payments_table.php`
- `app/Adapters/In/Http/Requests/Note/RecordNoteRevisionSurplusRefundPaymentRequest.php`
- `app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php`
- `tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php`
- `tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php`
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`

FACT:

- Idempotency is implemented and tested for note revision surplus refund paid flow.
- ADR-0022 states idempotency is recommended but not required for its first concurrency slice unless existing source supports it.

RISK:

- Payment, refund, supplier payment proof upload, stock, and reversal writes may still lack a consistent idempotency contract.
- Retries across Laravel, Go, network, browser, mobile, and storage operations can duplicate side effects if idempotency is absent.

GAP:

- No full mutation idempotency matrix was read.
- No global idempotency key source, uniqueness scope, request hash, replay response, expiration, or audit policy was read.

Why it matters for smooth Go/PostgreSQL transition:

During phased migration, retries and shadow paths are more common. Idempotency must be explicit for high-risk writes before they can safely cross services.

Recommended direction:

Classify idempotency per mutation. Treat missing idempotency in finance/stock/payment/refund/reversal writes as a proof gap until the owner accepts or resolves it.

Proof required:

- Idempotency matrix for every high-risk write.
- Explicit accepted-risk records for mutations without idempotency.
- Duplicate submit/retry tests for selected write paths.

Suggested test/proof:

- Same-key same-payload replay test.
- Same-key different-payload rejection test.
- Storage upload retry and rollback tests.

Do not fix yet: yes

### MRD-007 - Audit Canonical Path And Legacy Audit Log Split Need Write-Path Classification

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

- Future Go writes may write canonical audit while legacy Laravel flows still write legacy audit, or vice versa, creating incomplete timelines and rollback evidence.

GAP:

- No full audit writer usage map was read.
- No canonical-vs-legacy migration policy per bounded context was read.
- No audit parity test across Laravel/Go paths exists in this discovery.

Why it matters for smooth Go/PostgreSQL transition:

Audit is a rollback and finance correctness boundary. Migration must preserve who, why, when, source ID, amount, status, and before/after facts.

Recommended direction:

Inventory every audit-producing write path and classify canonical, legacy, dual-write, or accepted-risk before write migration.

Proof required:

- Audit write-path matrix.
- Required audit facts per mutation.
- Parity tests comparing expected audit events/logs.

Suggested test/proof:

- Feature tests asserting audit facts for payment/refund/procurement/inventory/revision writes.
- Reconciliation script for legacy logs vs canonical audit events where both apply.

Do not fix yet: yes

### MRD-008 - Security Public Surface Needs Route/Auth/Rate/Deployment Proof

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
- `rg "rate|throttle|Throttle|Limit|RateLimiter" routes app/Adapters/In/Http app/Providers config -n`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/Support/MobileSupplierPaymentProofUploadRequest.php::validate`

FACT:

- `/api/v1/auth/login` is a public API route.
- Mobile API login request validates email, password, and device name.
- The targeted throttle/rate-limit scan found auth config throttle settings and session token regeneration, but no route-level mobile API login throttle proof in the scanned paths.
- Mobile supplier payment proof upload validates file count, MIME extension set, and 2 MB max.

RISK:

- Public login and upload surfaces can become higher risk once exposed as API boundaries for cross-stack clients.
- Deployment proxy, CORS, WAF, storage, and cookie/session behavior were not verified.

GAP:

- No full public route matrix was completed.
- No brute-force/rate-limit tests were read for mobile API login.
- No production deployment security config proof was read.

Why it matters for smooth Go/PostgreSQL transition:

A migration often increases API exposure and client diversity. Security controls must be explicit before endpoints are mirrored or proxied to Go.

Recommended direction:

Run a dedicated public-surface audit batch before freezing API contracts.

Proof required:

- Public route inventory with auth, role, throttle, validation, file handling, response envelope, and log redaction status.
- Deployment config evidence for proxy/CORS/WAF/storage/session.

Suggested test/proof:

- HTTP auth failure and permission tests.
- Rate-limit tests for login.
- Upload validation and safe response header tests.

Do not fix yet: yes

### MRD-009 - Blade/JS Payload Coupling Remains A Contract And Browser Proof Gap

ID: MRD-009

Status: needs-proof

Severity: P2

Area: Blade/JS/UI payload readiness

Type: readiness-debt

Evidence:

- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `resources/views/admin/procurement/supplier_invoices/edit.blade.php`
- `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/product.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
- `public/assets/static/js/pages/cashier-note-payment.js`
- `public/assets/static/js/pages/admin-procurement-create.js`
- `rg "<script|@json|Js::from|json_encode|innerHTML|insertAdjacentHTML|hidden|type=\\"hidden\\"|data-|window\\." resources/views -n`
- `rg "innerHTML|insertAdjacentHTML|outerHTML|template|dataset|JSON\\.parse|window\\.[A-Za-z0-9_]+Config|fetch\\(" public/assets/static/js resources/views -n`

FACT:

- Finance/procurement UI uses hidden inputs, data attributes, global JS config, template cloning, dynamic HTML, and JavaScript-derived selected row payloads.
- `cashier-note-payment.js` builds hidden `selected_row_ids[]` inputs and computes UI payable/selected totals from DOM dataset values.
- `admin-procurement-create.js` uses window config, hidden product IDs, money raw fields, template cloning, draft storage, and client-side duplicate feedback.
- ADR-0028 says controllers are transport and Blade must not become financial truth.

RISK:

- UI/backend mismatch can become an API contract bug when Go clients or mobile clients reimplement payload composition.
- XSS or unsafe dynamic HTML risk cannot be dismissed without a sink audit.

GAP:

- No full browser QA was run.
- No full Blade/JS sink audit was completed.
- No backend-vs-UI contract matrix was read.

Why it matters for smooth Go/PostgreSQL transition:

Go API extraction needs typed contracts independent from Blade behavior. Any business rule hidden in DOM/JS must be either moved to backend contract or documented as UI-only preview.

Recommended direction:

Map each UI payload field to backend command fields and classify it as authoritative, preview-only, or derived server-side.

Proof required:

- UI payload contract map for note payment/refund/workspace and procurement create/edit/payment proof.
- Browser tests or HTTP tests proving backend rejects tampered UI payloads.
- XSS sink audit for dynamic HTML paths.

Suggested test/proof:

- Tampered hidden input tests.
- Browser flow tests for payment/refund/procurement.
- Static sink audit for `innerHTML`, `insertAdjacentHTML`, JSON config, and dataset rendering.

Do not fix yet: yes

### MRD-010 - Test And Proof Readiness Is Broad But Not Migration-Complete

ID: MRD-010

Status: needs-proof

Severity: P2

Area: Test/proof readiness

Type: gap

Evidence:

- `find tests -maxdepth 2 -type d -print`
- `find tests/Feature/MobileApi -maxdepth 2 -type f -print`
- `find tests/Feature/Database -maxdepth 2 -type f -print`
- `find tests/Feature/Payment -maxdepth 2 -type f -print`
- `find tests/Feature/Procurement -maxdepth 2 -type f -print`
- `find tests/Arch -maxdepth 2 -type f -print`
- `tests/Arch/HexagonalDependencyTest.php`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `database/migrations/README.md`

FACT:

- Unit, feature, database, mobile API, procurement, payment, reporting, and architecture test directories exist.
- Mobile API, payment, procurement, and database feature tests exist.
- Architecture test file exists.
- Full test suite was intentionally not run in this Stage 0 discovery.

RISK:

- Existing tests may prove current Laravel/MySQL behavior but not PostgreSQL runtime, Go contract parity, shadow compare, or browser/API cutover safety.

GAP:

- No full test run was executed.
- No PostgreSQL test suite was executed.
- No Go/Laravel contract parity tests were read.
- No shadow compare harness was read.

Why it matters for smooth Go/PostgreSQL transition:

Migration safety depends on proof, not structure. Without parity tests, every phased migration step relies on manual inspection.

Recommended direction:

Treat proof infrastructure as Stage 1 work after Stage 0 inventory is complete.

Proof required:

- Test coverage matrix by domain and migration risk.
- PostgreSQL compatibility job.
- API contract fixture parity.
- Shadow compare plan.

Suggested test/proof:

- Minimal PostgreSQL migration smoke suite.
- Contract snapshots for read-only APIs.
- Domain/use case tests for finance/stock invariants.
- Browser tests for UI payload boundaries.

Do not fix yet: yes

### MRD-011 - Hexagonal Boundary Exists But Runtime/Contract Completeness Is Not Proven

ID: MRD-011

Status: needs-proof

Severity: P3

Area: Architecture/hexagonal boundary readiness

Type: gap

Evidence:

- `find app/Core -maxdepth 2 -type d -print`
- `find app/Application -maxdepth 2 -type d -print`
- `find app/Ports -maxdepth 3 -type d -print`
- `find app/Adapters/In/Http -maxdepth 3 -type d -print`
- `find app/Adapters/Out -maxdepth 3 -type d -print`
- `php scripts/audit-hex.php` returned `HEXAGONAL AUDIT: OK`.
- `docs/01_standards/README.md`

FACT:

- Core, Application, Ports, Adapters/In/Http, and Adapters/Out folders exist and are segmented by bounded context.
- The local hexagonal audit script passed.

RISK:

- Structural dependency cleanliness does not prove command/result completeness, error taxonomy, transaction boundary completeness, idempotency, audit correctness, or Go-ready port shape.

GAP:

- No complete port contract review was performed.
- No provider binding map was fully read in this Stage 0 pass.
- No use case input/output contract matrix was completed.

Why it matters for smooth Go/PostgreSQL transition:

Hexagonal structure reduces extraction risk only when the contracts are stable enough to be implemented by another runtime or database adapter.

Recommended direction:

Use the existing structure as an advantage, but inventory contracts before claiming extraction readiness.

Proof required:

- Port and adapter implementation matrix.
- Use case command/result/error map.
- Provider binding and transaction/audit/clock/uuid abstraction map.

Suggested test/proof:

- Architecture tests plus contract tests per use case.
- Adapter tests for DB-specific implementations.
- Boundary tests that prevent HTTP/Blade/Eloquent leakage into Core/Application.

Do not fix yet: yes

### TW-001 - Creating Go Service, PostgreSQL Adapter, Migration Runner, And Shadow Compare Is Transition Work

ID: TW-001

Status: transition-work

Severity: P2

Area: Migration strategy

Type: transition-work

Evidence:

- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `database/migrations/README.md`

FACT:

- Existing docs treat PostgreSQL and Go as future work, not active implementation.
- The previous transition audit recommends staged proof, PostgreSQL harness, read-only Go API over projections, and later write ownership only after proof.

RISK:

- Misclassifying normal transition work as current-system debt can create unnecessary refactor pressure in Laravel.

GAP:

- No implementation plan exists in this Stage 0 document.

Why it matters for smooth Go/PostgreSQL transition:

Separating debt from transition work prevents premature rewrites and keeps Stage 0 focused on evidence.

Recommended direction:

Keep Go service scaffolding, PostgreSQL adapter, migration runner, and shadow compare as future transition work unless evidence shows current Laravel/MySQL design makes them unusually difficult.

Proof required:

- Stage 1 selection of first read-only target.
- Contract and parity harness requirements.

Suggested test/proof:

- Read-only projection API shadow compare before any write ownership.

Do not fix yet: yes

## Batch 1B - Security Public Surface Route Discovery

This section records Batch 1B-A and Batch 1B-B route-surface discovery. It is discovery-only, not remediation, not a vulnerability final report, not an exhaustive route security audit, and not a claim that route security is complete.

### 1. Scope covered

Batch 1B-A covered:

- `routes/api.php`
- `routes/web/auth.php`
- `routes/web/note.php`
- `routes/web/procurement.php`
- `routes/web/product_catalog.php`
- mobile API auth and procurement proof upload entrypoints
- middleware aliases related to `mobile.api.auth`, `transaction.entry`, `admin.page`, and `cashier.area`

Batch 1B-B covered:

- `routes/web/admin_products.php`
- `routes/web/admin_suppliers.php`
- `routes/web/admin_employees.php`
- `routes/web/admin_employee_debts.php`
- `routes/web/admin_payrolls.php`
- `routes/web/admin_expenses.php`
- `routes/web/admin_reporting.php`
- `routes/web/push_notifications.php`
- `routes/web/dashboard.php`
- `routes/web/admin_audit_logs.php`
- representative controller entrypoints and request validation classes for the listed route groups

### 2. Scope not covered

- Full route list outside Batch 1B-A and Batch 1B-B.
- Full controller and use case business audit.
- Full route-by-route permission proof from production roles and capability state.
- Full browser/session/CSRF flow proof.
- Full upload, storage, proxy, WAF, CORS, cookie, and production deployment proof.
- Full export body-by-body governance audit.
- Full mobile client compatibility audit.
- Full test-suite execution.

### 3. Summary

- P0: none proven on the covered route surface.
- This does not prove the system is secure or migration-ready.
- P1/P2 findings below are Migration Readiness Debt or GAP labels, not final vulnerability claims.
- Covered admin routes generally require `auth` plus `admin.page`; cashier/note routes use cashier and transaction-entry middleware where mapped.
- Login and high-value write/export routes do not yet have route-level throttle proof.
- Finance, stock, payroll, employee debt, procurement, payment, refund, and export routes need stronger mutation/read governance proof before Go API or PostgreSQL ownership changes.

### 4. P0 findings

- FACT: No P0 route-surface vulnerability was proven in the covered Batch 1B-A and Batch 1B-B route set.
- GAP: The route audit is not exhaustive and does not include production deployment controls, all browser flows, all business internals, or full test-suite proof.
- DECISION: Do not convert "P0 none proven on covered surface" into a claim that route security is complete.

### 5. P1 findings

- `SEC-1B-01`: login/auth surfaces have no route-level rate-limit proof.
- `SEC-1B-02`: legacy transaction writes using `transaction.entry` need explicit migration auth/capability matrix.
- `SEC-1B-03`: mobile supplier proof upload is authenticated finance/procurement write API with upload/rate/idempotency proof gaps.
- `SEC-1B-04`: note/payment/refund/procurement writes need per-mutation auth/validation/transaction/idempotency/audit matrix.
- `SEC-1B-B-01`: stock, payroll, employee debt, and expense mutation routes lack route-level idempotency/transaction/rollback proof.
- `SEC-1B-B-02`: reporting/export routes expose sensitive finance/stock/payroll data; export governance/rate/volume proof is GAP.
- `SEC-1B-B-03`: broad `admin.page` is common admin gate; route-specific capability proof is GAP for high-value surfaces.

### 6. P2 findings

- `SEC-1B-B-04`: read-only table JSON endpoints have query validation but no frozen API envelope/rate/versioning contract.
- `SEC-1B-B-05`: push notification subscription routes have validation but rate/origin/browser lifecycle proof is GAP.
- `SEC-1B-B-06`: dashboard analytics uses raw `Request`; month validation and read contract are GAP.
- `SEC-1B-B-07`: audit-log page is admin-only and paginated, but read governance/access logging proof is GAP.

### 7. Route-surface readiness debt register

#### SEC-1B-01 - Login/Auth Surfaces Have No Route-Level Rate-Limit Proof

ID: SEC-1B-01

Status: needs-proof

Severity: P1

Area: Security/public surface

Type: gap

Evidence:

- `routes/web/auth.php:10-15`
- `routes/api.php:16-18`
- `App\Adapters\In\Http\Controllers\Auth\AuthenticateController`
- `App\Adapters\In\Http\Controllers\Api\V1\Auth\LoginMobileApiController`
- Discovery command: `rg "RateLimiter|throttle:|ThrottleRequests|Limit::|assertTooManyRequests|429|too many" app bootstrap config routes tests`

FACT:

- Web login is registered as `POST /login` with `web` and `app.shell` middleware in `routes/web/auth.php`.
- Mobile API login is registered as `POST /api/v1/auth/login` in `routes/api.php`.
- The route scan did not find route-level throttle middleware or RateLimiter configuration for these login surfaces.

RISK:

- Login endpoints are brute-force and abuse-sensitive surfaces.
- Lack of explicit throttle proof makes API/public-surface readiness weaker even if another deployment layer later provides protection.

GAP:

- No feature test proof for login throttling was read.
- No proxy/WAF/rate-limiter deployment proof was read.

Why it matters for smooth Go/PostgreSQL transition:

Auth behavior must be stable before Go API clients or shadow traffic are introduced. If rate-limit semantics are implicit or external, migration can accidentally remove or duplicate protection.

Recommended direction:

Register web and mobile login as public-surface contract entries with explicit throttle, lockout, error envelope, and deployment assumptions.

Proof required:

- Route-level or deployment-level rate-limit policy proof.
- HTTP tests for repeated failed login attempts.
- API error-envelope proof for mobile login throttling.

Suggested test/proof:

- Web login throttle feature test.
- Mobile API login throttle feature test.
- Configuration evidence for any external limiter if protection lives outside Laravel.

Do not fix yet: yes

#### SEC-1B-02 - Legacy Transaction Writes Need Explicit Migration Auth/Capability Matrix

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
- The middleware returns JSON failures for unauthenticated or forbidden transaction-entry attempts.

RISK:

- During Go API migration, a broad transaction-entry gate may be hard to translate into route-specific capability contracts.
- A future API split could accidentally broaden or narrow cashier/admin write access if the current matrix is not explicit.

GAP:

- No complete route-to-capability matrix was read for transaction-entry writes.
- No per-mutation migration authorization contract was read.

Why it matters for smooth Go/PostgreSQL transition:

Go endpoints need typed, explicit auth/capability rules. A generic Laravel middleware gate is useful but not enough as a cross-stack contract unless each mutation's expected actor, role, capability, audit, and error behavior is recorded.

Recommended direction:

Build an auth/capability matrix for transaction-entry routes before implementing equivalent Go write endpoints.

Proof required:

- Route-by-route capability matrix.
- Tests for cashier allow, admin-with-capability allow, admin-without-capability deny, unknown actor deny.
- Error envelope expectations for web and JSON callers.

Suggested test/proof:

- Feature tests per mutation group.
- Policy tests for `TransactionEntryPolicy::decide`.
- Contract fixture for Go-compatible auth decisions.

Do not fix yet: yes

#### SEC-1B-03 - Mobile Supplier Proof Upload Has Upload/Rate/Idempotency Proof Gaps

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
- The supplier invoice proof route records payment-side effects through the Laravel application path.
- Upload validation exists in `MobileSupplierPaymentProofUploadRequest`.

RISK:

- This is not only an attachment upload; it touches procurement/finance state and therefore has write-migration risk.
- Repeated client retries could become unsafe if idempotency is not explicitly proven.

GAP:

- No route-level rate-limit proof was read.
- No idempotency key or retry-safety proof was read for these upload writes.
- No full mobile client contract was read.

Why it matters for smooth Go/PostgreSQL transition:

Mobile write endpoints are likely early API candidates, but this endpoint changes finance/procurement state. It should not move to Go until upload validation, storage behavior, payment side effects, idempotency, and audit facts are frozen.

Recommended direction:

Keep Laravel ownership until the upload/payment mutation contract is explicitly mapped and tested.

Proof required:

- Upload size/type/storage proof.
- Idempotency or retry-safety proof.
- Payment side-effect and audit proof.
- Mobile API error-envelope proof.

Suggested test/proof:

- Feature tests for valid upload, invalid upload, duplicate retry, unauthorized access, and missing proof.
- Golden response fixtures for mobile clients.
- Audit/projection reconciliation test for upload-created payment effects.

Do not fix yet: yes

#### SEC-1B-04 - Note/Payment/Refund/Procurement Writes Need Per-Mutation Matrix

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

- Note creation, workspace store/update, row addition, payment, refund, correction, procurement invoice create, and supplier invoice receive routes exist on the web surface.
- Existing security docs treat payment concurrency and allocation correctness as high-risk behavior requiring proof.

RISK:

- These routes combine finance, stock, note state, and revision behavior.
- Moving any part to Go without per-mutation contracts can create split-brain state or rollback gaps.

GAP:

- No complete per-mutation map was created in Batch 1B.
- No complete transaction boundary, idempotency, audit, and rollback matrix was read.

Why it matters for smooth Go/PostgreSQL transition:

Go API ownership cannot be decided safely by route group alone. Each mutation needs command/result/error, auth, validation, transaction, lock, audit, projection, idempotency, and rollback proof.

Recommended direction:

Treat these writes as protected Laravel-owned flows until a Domain Mutation Readiness batch proves their contracts.

Proof required:

- Per-mutation command/result/error matrix.
- Lock target and transaction boundary matrix.
- Audit event matrix.
- PostgreSQL parity proof for critical writes.

Suggested test/proof:

- Golden fixture tests for payments, refunds, allocations, procurement receipt, and note revision.
- Concurrency tests for payment/refund/allocation where feasible.
- Reconciliation tests for note/payment/projection/audit outputs.

Do not fix yet: yes

#### SEC-1B-B-01 - Stock, Payroll, Employee Debt, And Expense Mutations Need Idempotency/Transaction/Rollback Proof

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
- `App\Adapters\In\Http\Controllers\Admin\EmployeeDebt\StoreEmployeeDebtAdjustmentController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\EmployeeDebt\StoreEmployeeDebtPaymentReversalController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Payroll\StorePayrollController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Payroll\StorePayrollReversalController::__invoke`
- `App\Adapters\In\Http\Controllers\Admin\Expense\StoreExpenseController::__invoke`

FACT:

- Covered stock, payroll, employee debt, and expense mutation routes are admin-authenticated.
- Multiple routes have FormRequest or inline validation for amounts, dates, reason fields, and reversal fields.
- Some state-change controllers use raw `Request` and delegate to application handlers.

RISK:

- Admin-authenticated financial and stock writes remain high-blast-radius routes even when validation exists.
- Retries, double submits, reversal ordering, and rollback behavior can diverge when moved to Go or PostgreSQL without proof.

GAP:

- No route-level idempotency proof was read.
- No route-level transaction boundary proof was established for each mutation.
- No rollback/reversal parity proof was read.

Why it matters for smooth Go/PostgreSQL transition:

These writes affect money, debt balances, payroll, expenses, stock, and reversals. They must remain on a proven owner until command semantics and persistence behavior are stable across MySQL/PostgreSQL and Laravel/Go.

Recommended direction:

Add these routes to the Domain Mutation Readiness map before any Go ownership proposal.

Proof required:

- Mutation map with owner, idempotency, transaction, lock, audit, projection, and rollback expectations.
- PostgreSQL write compatibility proof for each high-risk mutation.

Suggested test/proof:

- Feature tests for duplicate submit/retry cases.
- Handler tests for reversal and rollback constraints.
- Ledger/projection/audit reconciliation tests.

Do not fix yet: yes

#### SEC-1B-B-02 - Reporting/Export Routes Need Export Governance And Rate/Volume Proof

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

- Reporting routes are admin-authenticated and include finance, payroll, employee debt, supplier payable, inventory stock value, transaction, and operational reports.
- Report requests validate date filters.
- Several export controllers implement maximum range guards for Excel or PDF generation.

RISK:

- Exports can expose sensitive finance, stock, payroll, and supplier data at high volume.
- Go/PostgreSQL report migration can silently change totals, ranges, grouping, or export behavior without a parity harness.

GAP:

- No route-level throttle proof was read.
- No export download governance, audit, or per-report volume policy was read.
- Full export controller body-by-body proof was not completed.

Why it matters for smooth Go/PostgreSQL transition:

Reports are likely read-only migration candidates, but they must prove parity and data access governance before Go/PostgreSQL reporting can be trusted.

Recommended direction:

Treat reporting as read-only candidate only after report contracts, date filters, volume limits, and parity fixtures are frozen.

Proof required:

- Report-by-report input/output contract.
- Export range and volume policy.
- Download/audit governance decision.
- MySQL vs PostgreSQL report parity fixtures.

Suggested test/proof:

- Golden fixtures for each report page and export.
- Shadow compare for report totals across MySQL and PostgreSQL.
- Export authorization and volume-control tests.

Do not fix yet: yes

#### SEC-1B-B-03 - Broad Admin Page Gate Needs Route-Specific Capability Proof For High-Value Surfaces

ID: SEC-1B-B-03

Status: needs-proof

Severity: P1

Area: Security/API readiness

Type: gap

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

- Covered admin route groups commonly use `auth` plus `admin.page`.
- `AdminPageAccessPolicy::decide` allows admin actors and rejects non-admin actors.

RISK:

- A broad admin-only gate may be insufficient as a future API capability contract for high-value stock, finance, payroll, report, and audit-log surfaces.
- Go API endpoints often need explicit scopes or capabilities rather than page-level middleware semantics.

GAP:

- No route-specific capability matrix was read for high-value admin surfaces.
- No proof was read that every admin should access every covered high-value route.

Why it matters for smooth Go/PostgreSQL transition:

Route authorization must become portable and explicit before endpoints are split across Laravel and Go. Otherwise, different stacks can drift on who can read or mutate sensitive data.

Recommended direction:

Inventory admin routes by domain and define whether `admin.page` is sufficient or whether domain-specific capability is required.

Proof required:

- Route-to-role/capability matrix.
- Policy tests for high-value route groups.
- Migration contract for equivalent Go auth decisions.

Suggested test/proof:

- Feature tests covering admin allowed and non-admin denied per high-value group.
- Capability test fixtures for future API scopes.

Do not fix yet: yes

#### SEC-1B-B-04 - Read-Only Table JSON Endpoints Need Frozen API Envelope/Rate/Versioning Contract

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

- These endpoints are natural Go read-only candidates, but unfrozen envelopes, pagination, sort keys, and error behavior can break UI parity.

GAP:

- No versioned API contract was read for these web JSON endpoints.
- No rate-limit proof was found.
- No cross-stack response fixture was read.

Why it matters for smooth Go/PostgreSQL transition:

Read-only table endpoints are good low-risk migration candidates only if their query and response contracts are frozen and tested before replacement or shadowing.

Recommended direction:

Create a read-model API contract registry for table endpoints before implementing Go equivalents.

Proof required:

- Query parameter contract.
- JSON envelope and pagination contract.
- Error behavior contract.
- UI compatibility fixtures.

Suggested test/proof:

- Snapshot or contract tests for table JSON responses.
- Shadow compare between Laravel and Go read models.
- Rate/volume tests for table endpoints.

Do not fix yet: yes

#### SEC-1B-B-05 - Push Subscription Routes Need Rate/Origin/Browser Lifecycle Proof

ID: SEC-1B-B-05

Status: needs-proof

Severity: P2

Area: Security/public surface

Type: gap

Evidence:

- `routes/web/push_notifications.php:9-18`
- `App\Adapters\In\Http\Controllers\PushNotification\StorePushSubscriptionController::__invoke`
- `App\Adapters\In\Http\Controllers\PushNotification\DeletePushSubscriptionController::__invoke`
- `App\Adapters\In\Http\Requests\PushNotification\StorePushSubscriptionRequest::rules`
- `App\Adapters\In\Http\Requests\PushNotification\DeletePushSubscriptionRequest::rules`

FACT:

- Push subscription store/delete routes require `auth`.
- Store validation requires URL endpoint and key fields with max lengths.
- Delete validation requires URL endpoint.
- Controllers return JSON `data.stored` or `data.deleted`.

RISK:

- Authenticated users can submit long subscription endpoints and key payloads.
- Browser push lifecycle behavior can drift if this is later moved behind a Go API without contract proof.

GAP:

- No route-level rate proof was read.
- No origin/CORS/browser lifecycle proof was read.
- No idempotent delete proof was read.

Why it matters for smooth Go/PostgreSQL transition:

Push subscription routes are small but client-facing JSON mutations. Moving them later requires stable request/response and browser behavior.

Recommended direction:

Treat push subscription endpoints as lightweight API contract candidates with explicit browser lifecycle and retry behavior.

Proof required:

- Store/delete contract.
- Rate/origin assumptions.
- Browser push compatibility proof.

Suggested test/proof:

- Feature tests for store, delete, duplicate store, invalid endpoint, and unauthenticated access.
- Browser-level smoke proof for subscription lifecycle.

Do not fix yet: yes

#### SEC-1B-B-06 - Dashboard Analytics Uses Raw Request And Needs Read Contract

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

- Analytics responses are likely to become read-only Go candidates.
- Unfrozen query format and response shape can cause UI/backend mismatch during migration.

GAP:

- No FormRequest or explicit month format validation was read.
- No response contract or rate/volume proof was read.

Why it matters for smooth Go/PostgreSQL transition:

Dashboard analytics is read-only but business-sensitive. It needs typed input and response proof before Go or PostgreSQL shadow comparison.

Recommended direction:

Register dashboard analytics as a read-model contract with explicit month format, response envelope, and parity expectations.

Proof required:

- Month query contract.
- JSON response fixture.
- MySQL/PostgreSQL parity proof for analytics totals.

Suggested test/proof:

- Feature tests for valid, missing, and invalid month values.
- Snapshot tests for analytics JSON response.
- Shadow compare for dashboard metrics.

Do not fix yet: yes

#### SEC-1B-B-07 - Audit Log Page Needs Read Governance/Access Logging Proof

ID: SEC-1B-B-07

Status: needs-proof

Severity: P2

Area: Audit/security readiness

Type: gap

Evidence:

- `routes/web/admin_audit_logs.php:8-13`
- `App\Adapters\In\Http\Controllers\Admin\AuditLog\AuditLogIndexPageController::__invoke`
- `App\Application\Audit\Services\AuditLogIndexPageData::listForAdmin`

FACT:

- Audit log page is behind `web`, `auth`, `admin.page`, and `app.shell`.
- The controller trims query parameter `q`, calls `listForAdmin($search, 20)`, and uses a `LengthAwarePaginator`.

RISK:

- Audit log visibility itself can be sensitive.
- During Go/PostgreSQL transition, audit log read behavior, retention, and access traceability can drift if not explicitly governed.

GAP:

- No audit-log read access logging proof was read.
- No retention/export policy was read.
- No route-specific audit-log capability proof was read.

Why it matters for smooth Go/PostgreSQL transition:

Audit readiness requires both write-side audit facts and controlled read-side access. A split Laravel/Go system should not create inconsistent audit visibility or governance.

Recommended direction:

Keep audit-log read behavior documented as a governance contract before moving audit queries or reports to PostgreSQL/Go.

Proof required:

- Audit-log read policy.
- Retention and export policy.
- Access logging or accepted-risk decision.

Suggested test/proof:

- Feature tests for admin allowed and non-admin denied.
- Query/filter tests for audit-log search.
- Governance decision record for audit-log read access.

Do not fix yet: yes

### 8. Impact on Stage 0 readiness estimate

- FACT: Batch 1B increases route-surface discovery coverage, especially for security/API readiness.
- FACT: It does not increase implementation readiness for Go API or PostgreSQL ownership.
- FACT: It does not close P1/P2 readiness debt; it registers more specific route-surface proof gaps.
- RISK: Overall smooth transition readiness should remain conservative until Batch 2 PostgreSQL/database proof and later mutation proof batches are complete.
- Working estimate adjustment: Security/API readiness coverage can be treated as improved from 30% to about 38% for discovery coverage only; overall smooth transition readiness remains about 25%.

### 9. Next audit batch

Next audit batch remains Batch 2: PostgreSQL/database readiness.

## 9. What Looks Healthy So Far

- FACT: Hexagonal folders exist: `app/Core`, `app/Application`, `app/Ports`, `app/Adapters/In/Http`, and `app/Adapters/Out`.
- FACT: `php scripts/audit-hex.php` returned `HEXAGONAL AUDIT: OK`.
- FACT: ADR-0028 exists and defines MySQL-to-PostgreSQL/API readiness constraints.
- FACT: DB readiness blueprints exist under `docs/03_blueprints/db`.
- FACT: `database/migrations/README.md` explicitly forbids claiming PostgreSQL readiness without runtime proof.
- FACT: Money policy in docs favors integer rupiah, not float.
- FACT: Projection/read model tables exist in migrations, including `note_history_projection`, `supplier_invoice_list_projection`, and `supplier_list_projection`.
- FACT: Mobile API auth routes and tests exist in `routes/api.php` and `tests/Feature/MobileApi`.
- FACT: Attachment proof upload has validation in `MobileSupplierPaymentProofUploadRequest::validate`.
- FACT: Tests exist across Feature, Unit, Database, MobileApi, Procurement, Payment, Reporting, and Arch directories.

These are useful starting points. They do not prove the system is ready for direct Go write ownership or PostgreSQL cutover.

## 10. What Is Still Unknown

- Full route auth matrix.
- Full public route and permission matrix.
- Full Blade/JS sink audit.
- Full PostgreSQL migration runtime.
- Full raw SQL parity.
- Full finance mutation map.
- Full idempotency matrix.
- Full transaction boundary and lock target map.
- Full audit canonical-vs-legacy write map.
- Live DB parity.
- Deployment proxy, WAF, CORS, session, cookie, and storage config.
- Mobile client contract.
- Browser QA.
- Full test suite.
- Go API endpoint contract.
- Shadow compare design.
- Rollback runbook.

## 11. Stage 0 Exit Criteria

Stage 0 can be considered complete only when:

- all initial findings are registered;
- all P0/P1 candidates have evidence or explicit gap labels;
- all remaining audit batches are listed;
- no remediation patch is mixed into discovery;
- the next stage is chosen based on evidence;
- readiness scores are updated after evidence changes;
- no "ready" claim is made without P0/P1 and parity proof.

## 12. Recommended Next Audit Batches

Batch 1B: Security public surface - discovery recorded

- Route auth/permission matrix.
- Login throttle and brute-force proof.
- Upload/storage/attachment surface.
- Deployment proxy/CORS/session/storage assumptions.
- Status: Batch 1B-A and Batch 1B-B registered route-surface findings; route security is not complete.

Batch 2: PostgreSQL/database readiness - next

- Migration compatibility matrix.
- Raw SQL/query-builder portability matrix.
- PostgreSQL fresh migration and seed/import proof plan.
- Data parity and rollback proof requirements.

Batch 3: Architecture/system/hexagonal boundary readiness

- Core/Application/Ports/Adapters contract inventory.
- Provider binding map.
- Transaction/clock/uuid/audit abstractions.
- Typed errors and command/result contract map.

Batch 4: Domain mutation readiness

- Payment/refund/allocation/procurement/inventory/note revision/employee finance mutation map.
- Transaction, lock, idempotency, audit, projection, and rollback classification.

Batch 5: Test/proof readiness

- Existing test matrix by domain.
- Missing contract, adapter, DB compatibility, browser, and shadow tests.
- Minimum proof harness for Stage 1.

Batch 6: Migration strategy and sequencing

- Choose first read-only target.
- Define contract freeze.
- Define shadow compare and rollout safety.
- Defer write ownership until proof exists.

## 13. Working Rule

Do not start remediation before a finding is registered, classified, and has proof requirement.

## 14. One Next Step

Run Batch 2: PostgreSQL/database readiness audit, starting with migrations, schema constraints, raw SQL/query-builder portability, indexes, foreign keys, JSON/date/group-by behavior, money integer usage, and database compatibility proof gaps.
