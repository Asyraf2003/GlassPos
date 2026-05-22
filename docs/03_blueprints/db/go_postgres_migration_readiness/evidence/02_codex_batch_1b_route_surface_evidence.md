# Codex Batch 1B Route Surface Evidence

Source baseline: [../0013_go_postgres_migration_readiness_stage_0.md](../0013_go_postgres_migration_readiness_stage_0.md)

This summarizes Batch 1B route-surface discovery. It is not a final vulnerability report.

## Batch 1B-A Route Groups

- `routes/api.php`
- `routes/web/auth.php`
- `routes/web/note.php`
- `routes/web/procurement.php`
- `routes/web/product_catalog.php`
- mobile API auth and procurement proof upload entrypoints
- middleware aliases related to `mobile.api.auth`, `transaction.entry`, `admin.page`, and `cashier.area`

## Batch 1B-B Route Groups

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

## P0 Route-Surface Result

FACT:
No P0 route-surface vulnerability was proven on the covered Batch 1B-A and Batch 1B-B route set.

DECISION:
Do not convert this into a claim that route security is complete.

GAP:
The route audit is not exhaustive and does not include production deployment controls, all browser flows, all business internals, or full test-suite proof.

## P1 Route Findings

- `SEC-1B-01`: login/auth surfaces have no route-level rate-limit proof.
- `SEC-1B-02`: legacy transaction writes using `transaction.entry` need explicit migration auth/capability matrix.
- `SEC-1B-03`: mobile supplier proof upload is authenticated finance/procurement write API with upload/rate/idempotency proof gaps.
- `SEC-1B-04`: note/payment/refund/procurement writes need per-mutation auth/validation/transaction/idempotency/audit matrix.
- `SEC-1B-B-01`: stock, payroll, employee debt, and expense mutation routes lack route-level idempotency/transaction/rollback proof.
- `SEC-1B-B-02`: reporting/export routes expose sensitive finance/stock/payroll data; export governance/rate/volume proof is GAP.
- `SEC-1B-B-03`: broad `admin.page` is common admin gate; route-specific capability proof is GAP for high-value surfaces.

## P2 Route Findings

- `SEC-1B-B-04`: read-only table JSON endpoints have query validation but no frozen API envelope/rate/versioning contract.
- `SEC-1B-B-05`: push notification subscription routes have validation but rate/origin/browser lifecycle proof is GAP.
- `SEC-1B-B-06`: dashboard analytics uses raw `Request`; month validation and read contract are GAP.
- `SEC-1B-B-07`: audit-log page is admin-only and paginated, but read governance/access logging proof is GAP.

## Coverage Not Covered

- Full route list outside Batch 1B-A and Batch 1B-B.
- Full controller and use case business audit.
- Full route-by-route permission proof from production roles and capability state.
- Full browser/session/CSRF flow proof.
- Full upload, storage, proxy, WAF, CORS, cookie, and production deployment proof.
- Full export body-by-body governance audit.
- Full mobile client compatibility audit.
- Full test-suite execution.

## Classification Warning

FACT:
Batch 1B findings are route-surface readiness debt or proof gaps.

RISK:
Some may later become confirmed vulnerabilities, accepted risks, or non-issues after deeper proof.

GAP:
No finding should be upgraded or closed without source evidence and proof.

NEXT:
Batch 2 remains PostgreSQL/database readiness discovery.
