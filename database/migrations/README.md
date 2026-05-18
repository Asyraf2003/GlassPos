# HyperPOS Database Migration Contract

Status: Active migration contract reference  
Scope: MySQL schema hardening toward PostgreSQL-aligned structure  
Runtime DB now: MySQL  
Future target: PostgreSQL-ready schema discipline, not immediate PostgreSQL cutover

## Goal

The goal of this migration folder is to keep the current MySQL schema structured so that a future PostgreSQL transition is significantly easier.

This does not mean PostgreSQL is active now.

This means new and revised migrations must avoid MySQL-only domain assumptions and must keep finance, stock, audit, projection, and reporting tables portable, explicit, and traceable.

## Before

The migration history currently contains a mixed MySQL-era structure:

- domain tables mostly use string primary keys
- employee/payroll/debt tables use UUID primary keys
- framework/system tables may still use numeric IDs
- money is mostly stored as integer or big integer rupiah
- some fields still rely on MySQL unsigned semantics
- some migrations use MySQL-oriented helpers such as after/change
- JSON storage is mixed between native json columns and text payload columns
- source tables and projection tables exist, but projection materialization policy is not fully uniform
- some legacy seeders still direct-write source/projection/audit tables

## After Target

The target structure is:

- domain identity is explicit and stable
- domain IDs use string or UUID based on table contract
- framework/system numeric IDs stay internal only
- money is stored as integer rupiah, never float or decimal
- status columns use string values, never MySQL enum
- business dates, action timestamps, created_at, and updated_at are not mixed
- JSON is allowed only for snapshot, metadata, draft, or compatibility payload
- JSON must not be the only source of truth for money, status, source ID, actor ID, business date, inventory quantity, payment/refund amount, or report-critical facts
- financial history must not cascade-delete casually
- projection/read-model tables are not canonical financial truth
- indexes must follow real read paths, not guesswork
- PostgreSQL-incompatible assumptions must be documented before new schema work

## ID Policy

Allowed:

- string primary keys for domain tables
- UUID primary keys for domains already modeled as UUID
- numeric IDs for Laravel/framework/system-owned tables only

Forbidden:

- exposing auto-increment IDs as domain/public identity
- adding new mixed identity styles without table-level reason
- relying on numeric framework IDs for finance domain references unless explicitly internal

## Money Policy

Allowed:

- integer
- bigInteger
- explicit signed integer when negative value is valid domain behavior

Forbidden:

- float
- decimal as official rupiah truth
- formatted strings such as 15.000
- relying only on MySQL unsigned as a financial invariant

All rupiah values must reconcile exactly. A one-rupiah mismatch is a defect.

## Date and Time Policy

Do not mix date meanings.

Business/report dates:

- transaction_date
- paid_at
- refunded_at
- tanggal_pengiriman
- tanggal_terima
- tanggal_mutasi
- effective_date where needed

Action/audit timestamps:

- occurred_at
- closed_at
- reopened_at
- uploaded_at
- performed_at where needed

System timestamps:

- created_at
- updated_at

Rules:

- created_at is not transaction_date
- transaction_date is not audit occurred_at
- paid_at/refunded_at are financial event dates
- projection timestamps should use projected_at or rebuilt_at when semantics are selected

## Source Table vs Projection Table

Source-of-truth tables must be seeded and migrated before projections.

Source examples:

- notes
- work_items
- customer_payments
- customer_refunds
- payment_allocations
- payment_component_allocations
- refund_component_allocations
- supplier_invoices
- supplier_invoice_lines
- supplier_receipts
- supplier_payments
- inventory_movements
- audit_events
- note_mutation_events
- version tables

Projection/read-model examples:

- product_inventory
- product_inventory_costing
- note_history_projection
- supplier_invoice_list_projection
- supplier_list_projection

Projection tables must be rebuildable from source or explicitly documented as current-state tables.

## PostgreSQL Readiness Watchlist

Every migration review must check:

- unsignedInteger
- unsignedBigInteger
- after()
- change()
- mediumText
- longText used as JSON payload
- dateTime vs timestamp semantics
- MySQL enum
- cascadeOnDelete on financial history
- raw JSON financial truth
- money stored outside integer/bigInteger
- source/projection ambiguity

## Change Rule

Do not mass-edit old migrations without a clear baseline decision.

Preferred order:

1. document current migration contract
2. audit migration risk by group
3. patch one table group at a time
4. add new compatibility migrations when preserving deployed history matters
5. edit old migrations only when the branch is intentionally treated as rebuildable schema history
6. update seeders only after DB contract is clear

## Current Next Target

First technical target after this README:

- classify unsigned fields into:
  - harmless counter
  - non-negative financial/counter invariant needing validation
  - PostgreSQL check constraint candidate
  - migration rewrite candidate

Do not start seeder rewrite before this classification is done.

## Current Migration Compatibility Scan - unsigned/layout/text

Status: Active classification target  
Source command:

    rg -n -- "unsignedInteger|unsignedBigInteger|->after\\(|->change\\(|mediumText|longText|enum" database/migrations

## Scan Result Classification

### 1. Framework-owned tables

These are Laravel/framework tables. They are not domain financial truth and should not be patched in the first domain migration hardening slice.

- `0001_01_01_000000_create_users_table.php`
  - `longText('payload')`
- `0001_01_01_000001_create_cache_table.php`
  - `mediumText('value')`
- `0001_01_01_000002_create_jobs_table.php`
  - `longText('payload')`
  - `longText('failed_job_ids')`
  - `mediumText('options')`
  - `longText('exception')`
  - unsigned framework queue timestamps/counters

Decision:

- Keep for now.
- Do not mix framework table cleanup with domain PostgreSQL-readiness work.

### 2. MySQL layout helpers

These are mostly column-order helpers. They are PostgreSQL-hostile as migration syntax style, but not domain truth by themselves.

Detected patterns:

- `->after(...)`
- `->change()`

Affected examples:

- product/supplier soft-delete foundation
- product search normalization
- supplier invoice revision line additions
- supplier receipt line snapshots
- employee master v2 alteration
- payment method/cash detail addition
- current revision pointer on notes
- operational timestamp additions

Decision:

- Do not mass-remove blindly.
- Prefer removing or rewriting only when touching the same migration group.
- `->change()` is higher risk than `->after()` because it can require DBAL/platform-specific behavior.

### 3. Domain unsigned counters/revision numbers

These represent non-negative counters or revision numbers.

Affected fields include:

- `supplier_invoices.last_revision_no`
- `inventory_cost_adjustments.source_revision_no`
- `supplier_invoice_lines.revision_no`
- `employee_versions.revision_no`
- `supplier_invoice_versions.revision_no`
- `product_versions.revision_no`
- `supplier_versions.revision_no`
- `notes.latest_revision_number`
- `note_revisions.revision_number`
- `note_revisions.line_count`
- `note_revision_lines.line_no`
- projection counters such as line/payment/receipt/proof counts

Decision:

- PostgreSQL has no native unsigned type.
- Future target should use signed integer/bigInteger plus explicit non-negative invariant.
- Do not weaken current MySQL DB protection until replacement invariant is planned.
- First patch candidate should classify each field as:
  - counter
  - revision number
  - file size
  - money
  - projection count

### 4. Domain unsigned money

These are more sensitive than counters.

Affected fields include:

- `note_revisions.grand_total_rupiah`
- `note_revision_lines.service_price_rupiah`
- `note_revision_lines.subtotal_rupiah`

Decision:

- Money must stay integer rupiah.
- Do not rely on unsigned as the only non-negative invariant.
- Patch requires test proof that negative values are rejected in domain/application or DB constraint.
- Do not patch together with counters unless proof scope stays small.

### 5. Domain JSON/text payload

Detected domain payload text:

- `transaction_workspace_drafts.payload_json` as `longText`
- `note_mutation_snapshots.payload_json` as `longText`

Detected native JSON:

- version snapshots
- audit metadata
- audit snapshots
- note revision line payload

Decision:

- Keep text only when payload is opaque and not queried.
- Prefer native JSON/JSONB direction when future PostgreSQL query/validation matters.
- Do not convert without read/write compatibility tests.

## First Safe Migration Refactor Order

1. Counters/revision numbers classification.
2. Domain unsigned money invariant proof.
3. JSON/text payload classification.
4. MySQL layout helper cleanup by touched group.
5. Projection timestamp/materialization policy.
6. Seeder adjustment only after migration contract is locked.

## Current Goal Statement

The goal is not to move to PostgreSQL now.

The goal is to make the current MySQL schema mature, explicit, and PostgreSQL-aligned so a future PostgreSQL transition is significantly easier and less risky.

## Live MySQL Transition Note

Status: Live-system safety note

This project currently has a live MySQL database.

Editing historical migration files does not change an already-migrated live MySQL database.

These migration edits define the intended clean/fresh schema direction and make future PostgreSQL transition easier, but they are not a live schema migration by themselves.

## Live Database Rule

For the live MySQL database:

- do not run `migrate:fresh`
- do not assume edited old migration files alter existing live columns
- do not drop/recreate finance-sensitive tables casually
- use explicit forward migration, dump/restore transform, or PostgreSQL import mapping for live transition
- every live transition step must have backup, rollback, row-count proof, money reconciliation proof, and projection rebuild proof

## Current Fresh-Schema Proof

Current local proof after unsigned cleanup:

- fresh testing migration completed successfully
- database feature tests passed
- result: 26 tests / 241 assertions

This proves the fresh MySQL schema can be rebuilt after the current migration hardening slice.

This does not prove PostgreSQL production cutover.

## Meaning Of Current Migration Edits

Current edits move the target fresh schema away from MySQL-only unsigned domain assumptions.

Changed categories:

- revision numbers
- line counters
- projection counters
- domain money fields in note revision tables

Remaining intentionally unpatched:

- Laravel framework job-table unsigned fields
- supplier payment proof `file_size_bytes`
- PostgreSQL runtime migration
- live MySQL data transformation
- PostgreSQL import/parity proof

## PostgreSQL Transition Requirement

Before live PostgreSQL transition can be claimed safe, the project still needs:

1. MySQL live backup proof.
2. MySQL schema snapshot.
3. PostgreSQL fresh migration proof.
4. Data export/import mapping.
5. Row-count parity by table group.
6. Integer rupiah reconciliation by finance domain.
7. Payment/refund/allocation reconciliation.
8. Inventory movement and projection reconciliation.
9. Audit/version/projection rebuild proof.
10. Application test suite against PostgreSQL.
11. Cutover and rollback runbook.

## Current Claim Allowed

Allowed claim:

The MySQL migration structure is being hardened toward a PostgreSQL-aligned target schema, and the current unsigned cleanup slice is fresh-schema verified on MySQL testing.

Forbidden claim:

The live system is already PostgreSQL-ready for production cutover.

## Research Schema To Live Transition Model

Status: Active working model

This repository migration work is treated as a schema research and target-shape hardening track.

The live system currently runs on MySQL.

The purpose of editing and hardening these migrations is to make the target schema mature first, while keeping a physical record of before/after decisions.

## Why Historical Migrations Are Being Edited Here

In this research track, historical migrations may be edited to define the clean target schema.

This is acceptable only because this work is not directly mutating the live MySQL database.

The live database will not automatically receive these changes just because old migration files are edited.

Live transition must be done later through explicit forward migration scripts, SQL transform scripts, or controlled export/import mapping.

## Live Transition Strategy

Allowed future transition path:

1. keep live MySQL operational
2. mature the target schema in this research codebase
3. prove fresh schema rebuild
4. prove seed/test compatibility
5. create live transition SQL or migration scripts
6. run dry-run migration against copied MySQL data
7. import transformed data into PostgreSQL
8. compare row counts by table
9. reconcile all rupiah totals by finance domain
10. rebuild projections from source tables
11. run application tests against PostgreSQL
12. prepare cutover and rollback runbook

Forbidden live behavior:

- running `migrate:fresh` on live
- assuming edited historical migrations mutate live tables
- dropping finance tables without backup and parity proof
- migrating data without money reconciliation
- treating projection tables as canonical financial truth
- cutting over without rollback path

## Data Preservation Rule

Old live data must be preserved.

Schema changes must be handled through mapping, not data loss.

Examples:

- MySQL unsigned integer values can map to PostgreSQL signed integer values when non-negative.
- MySQL integer rupiah values remain integer rupiah values.
- String domain IDs remain string IDs.
- UUID domain IDs remain UUID-compatible string values.
- JSON/text payloads must be classified before conversion.
- Projection tables may be rebuilt from source if source tables are complete.

## Current Research Proof

Current fresh MySQL testing proof exists for the unsigned cleanup slice:

- fresh migration completed
- database feature tests passed
- result: 26 tests / 241 assertions

This proof validates the research target schema on fresh MySQL testing.

It does not yet validate PostgreSQL runtime or live data migration.

## Current Next Research Target

Continue with PostgreSQL compatibility classification in this order:

1. remaining unsigned fields
2. MySQL layout helpers
3. JSON/text payload fields
4. timestamp/date semantics
5. source/projection rebuild policy
6. live SQL transition mapping

## Slice 3 - Employee migration `change()` cleanup

Status: Focused Verified.

FACT:
- `database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php` no longer uses Laravel `->change()`.
- Employee master v2 nullability tightening now uses explicit driver-aware SQL instead of schema-builder `change()`.
- MySQL/MariaDB path uses `ALTER TABLE ... MODIFY ... NOT NULL`.
- PostgreSQL path uses `ALTER TABLE ... ALTER COLUMN ... SET NOT NULL`.
- Table identity is preserved; the `employees` table is not recreated.
- Seeder work is intentionally out of scope until migration/system readiness is mature.

PROOF:
- Syntax check passed for employee master v2 migration.
- `php artisan migrate:fresh --env=testing` passed.
- Clean migration scan found no remaining `->change()` in `database/migrations`.
- `make verify` passed: 1063 tests / 5769 assertions.

BOUNDARY:
- This is research/target-schema readiness work.
- This does not modify the live MySQL database by itself.
- This does not claim production PostgreSQL cutover readiness.
- Live transition still requires explicit forward migration, SQL transform, or export/import mapping.
