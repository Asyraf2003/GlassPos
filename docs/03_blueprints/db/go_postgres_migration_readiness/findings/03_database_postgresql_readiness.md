# Database PostgreSQL Readiness Findings

Scope source: Stage 0 baseline and database transition docs. This file is pending Batch 2 expansion and must not be treated as complete.

## MRD-002 - PostgreSQL Fresh Migration And Runtime Parity Are Not Proven

ID: MRD-002

Status: needs-proof

Severity: P0

Area: PostgreSQL/schema/query readiness

Type: runtime-gap

Evidence:

- `database/migrations/README.md`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- Stage 0 command evidence: `find database/migrations -maxdepth 1 -type f -name '*.php' -print`

FACT:

- Existing docs explicitly forbid claiming PostgreSQL production cutover safe without runtime proof.
- PostgreSQL is a future target, not the active runtime.

RISK:

- MySQL-valid migrations, indexes, date functions, raw SQL, locks, and constraints may not preserve behavior on PostgreSQL.

GAP:

- Fresh PostgreSQL migration not proven.
- Seed/import not proven.
- Row-count/rupiah reconciliation not proven.
- Payment/refund/allocation reconciliation not proven.
- Inventory/projection reconciliation not proven.
- Audit/version/projection rebuild not proven.
- App test suite on PostgreSQL not run.
- Cutover/rollback runbook not proven.

Why it matters for smooth Go/PostgreSQL transition:

Without PostgreSQL runtime proof, a Go API backed by PostgreSQL would depend on unproven schema, query, timestamp, JSON, lock, and constraint behavior.

Recommended direction:

Build PostgreSQL compatibility proof before any cutover plan. Treat the harness as proof infrastructure, not a rewrite.

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

## MRD-004 - Raw SQL And Query Builder Portability Need Classification

ID: MRD-004

Status: needs-proof

Severity: P1

Area: PostgreSQL/schema/query readiness

Type: readiness-debt

Evidence:

- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- Stage 0 command evidence: `rg "DB::raw|selectRaw|whereRaw|orderByRaw|havingRaw|DATE\\(|CONCAT\\(|GREATEST\\(|LEAST\\(|lockForUpdate|updateOrInsert|insertGetId|insertOrIgnore" app database/migrations -n`
- Example paths from Stage 0:
  - `app/Adapters/Out/Procurement/Concerns/ProcurementInvoicePolicySqlFragments.php`
  - `app/Adapters/Out/Note/Queries/NoteHistoryLegacyLineSummarySubquery.php`
  - `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/CashInPerDayQuery.php`
  - `app/Adapters/Out/Reporting/DatabasePayrollReportingSourceReaderAdapter.php`
  - `app/Adapters/Out/MobileApi/DatabaseMobileApiTokenStoreAdapter.php`
  - `database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php`

FACT:

- Raw expressions exist in adapters, reporting queries, projection sources, and migrations.
- Query-builder operations such as `lockForUpdate`, `updateOrInsert`, `insertGetId`, and `insertOrIgnore` appear in persistence code.
- Concentration in adapters is useful for future replacement, but not proof of PostgreSQL parity.

RISK:

- Date extraction, string functions, aggregate expressions, window functions, upsert semantics, insert ID behavior, and lock behavior may differ between MySQL and PostgreSQL.

GAP:

- Raw SQL portability not classified.
- Query-builder behavior not proven.
- Lock/upsert/insert ID behavior not proven.
- Report/projection parity not proven.

Why it matters for smooth Go/PostgreSQL transition:

Read-only Go API and PostgreSQL cutover depend heavily on projection/report query correctness.

Recommended direction:

Create a compatibility matrix for raw SQL and query-builder constructs before touching implementation.

Proof required:

- Query inventory with status: portable, needs rewrite, needs PostgreSQL test, or accepted risk.
- PostgreSQL execution proof for high-risk read/report/projection paths.

Suggested test/proof:

- Query parity tests on MySQL and PostgreSQL fixtures.
- Report/projection snapshot comparisons.
- Lock/upsert behavior characterization.

Do not fix yet: yes

## Pending Batch 2 Expansion

Status: non-register note

Area: PostgreSQL/schema/query readiness

Type: proof-gap summary

Evidence:

- `database/migrations/README.md`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`

FACT:

- Stage 0 names Batch 2 as the next audit batch.
- Known DB/PostgreSQL gap categories are already listed in Stage 0.

RISK:

- Treating these gaps as solved before Batch 2 would create a false readiness claim.

GAP:

- Migration/schema portability matrix not complete.
- FK/cascade/restrict classification not complete.
- JSON/date/time/group-by/collation behavior not fully reviewed.
- Money integer proof not complete.
- DB compatibility tests not fully mapped.

Why it matters for smooth Go/PostgreSQL transition:

Database behavior is the largest runtime change in the migration path.

Recommended direction:

Run Batch 2 discovery before updating readiness scores upward.

Proof required:

- Batch 2 findings with source evidence and severity.

Suggested test/proof:

- PostgreSQL migration smoke and query parity proof plan.

Do not fix yet: yes
