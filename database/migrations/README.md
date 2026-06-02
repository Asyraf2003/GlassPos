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
- supplier payment proof `file_size_bytes` signed metadata alignment
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

## Slice 4 - JSON payload column alignment

Status: Focused Verified.

FACT:
- `database/migrations/2026_04_02_001100_create_note_mutation_snapshots_table.php` now declares `payload_json` with `$table->json('payload_json')`.
- `database/migrations/2026_04_04_100000_create_transaction_workspace_drafts_table.php` now declares `payload_json` with `$table->json('payload_json')`.
- MariaDB 12.2.2 reports these JSON columns physically as `longtext`, but the testing schema now includes `JSON_VALID(payload_json)` checks.
- Explicit MariaDB check constraints are present:
  - `nms_payload_json_valid_chk`
  - `twd_payload_json_valid_chk`
- `tests/Feature/Database/JsonPayloadSchemaTest.php` verifies JSON-native type or validated JSON alias storage.
- Seeder work remains intentionally out of scope until migration/system readiness is mature.

PROOF:
- Syntax check passed for both target migrations and `JsonPayloadSchemaTest`.
- `php artisan migrate:fresh --env=testing` passed.
- Testing DB metadata proof used `php artisan --env=testing tinker`.
- Testing DB is `bengkelhex_test` on MariaDB `12.2.2-MariaDB`.
- `JsonPayloadSchemaTest` passed: 1 test / 6 assertions.
- `make verify` passed: 1064 tests / 5775 assertions.

BOUNDARY:
- This is research/target-schema readiness work.
- This does not modify the live MySQL database by itself.
- This does not claim production PostgreSQL cutover readiness.
- Live transition still requires explicit forward migration, SQL transform, or export/import mapping.

## Slice 5 - Supplier payment proof file size metadata signed integer alignment

Status: Focused Verified.

FACT:
- `database/migrations/2026_03_16_000110_create_supplier_payment_proof_attachments_table.php` now declares `file_size_bytes` with `$table->bigInteger('file_size_bytes')`.
- `file_size_bytes` is file metadata, not money, stock, quantity, ledger, or settlement math.
- Admin and mobile upload paths already validate proof files with MIME restrictions and `max:2048`.
- The field is written from uploaded file size and read back as integer metadata.

PROOF:
- Syntax check passed for the target migration.
- Syntax check passed for `tests/Feature/Database/SupplierPaymentProofAttachmentSchemaTest.php`.
- `php artisan migrate:fresh --env=testing` passed.
- `SupplierPaymentProofAttachmentSchemaTest` passed.
- `make verify` passed: 1065 tests / 5778 assertions.

BOUNDARY:
- This is research/target-schema readiness work.
- This does not modify the live MySQL database by itself.
- This does not claim production PostgreSQL cutover readiness.
- Live transition still requires explicit forward migration, SQL transform, or export/import mapping.
- Seeder work remains intentionally out of scope until DB baseline is frozen.

## Slice 6 - User-linked foreignId compatibility decision

Status: Focused Verified / acceptable documented residual.

FACT:
- `users.id` is created with `$table->id()`.
- `push_subscriptions.user_id` uses `foreignId('user_id')->constrained('users')->cascadeOnDelete()`.
- `mobile_api_tokens.user_id` uses `foreignId('user_id')->constrained('users')->cascadeOnDelete()`.
- These fields are auth/framework-linked identifiers, not money, stock, ledger, quantity, or financial math.
- Migration patch is not required because the app-owned user-linked tables intentionally follow Laravel `users.id` identity type.
- `tests/Feature/Database/UserLinkedForeignKeySchemaTest.php` verifies type compatibility, FK existence, and cascade delete behavior.

PROOF:
- Syntax check passed for `UserLinkedForeignKeySchemaTest`.
- Targeted `UserLinkedForeignKeySchemaTest` passed: 1 test / 13 assertions.
- `make verify` passed: 1066 tests / 5791 assertions.

BOUNDARY:
- This is research/target-schema readiness work.
- This does not modify the live MySQL database by itself.
- This does not claim production PostgreSQL cutover readiness.
- Live transition still requires explicit forward migration, SQL transform, or export/import mapping.
- Seeder work remains intentionally out of scope until DB baseline is frozen.

## Final DB baseline residual classification before seeder

Status: DB baseline freeze candidate.

FACT:
- App-owned MySQL/PostgreSQL-alignment residuals found during this research track are now either Focused Verified or explicitly classified.
- `->change()` cleanup is Focused Verified.
- `payload_json` JSON alignment is Focused Verified.
- `supplier_payment_proof_attachments.file_size_bytes` signed metadata alignment is Focused Verified.
- User-linked `foreignId('user_id')` usage in `push_subscriptions` and `mobile_api_tokens` is Focused Verified as framework-coupled and compatible with `users.id`.
- Remaining `json(...)` columns are intentional JSON/snapshot/payload columns.
- Remaining framework/default-owned text or unsigned signals are intentionally documented skips:
  - `0001_01_01_000002_create_jobs_table.php`
  - `0001_01_01_000001_create_cache_table.php`
  - `0001_01_01_000000_create_users_table.php` sessions table fields
- Seeder work remains intentionally deferred until this DB baseline is accepted as frozen.

BOUNDARY:
- This freeze candidate only covers MySQL DB migration research baseline.
- This does not modify the live MySQL database by itself.
- This does not claim production PostgreSQL cutover readiness.
- PostgreSQL transition still requires explicit forward migration, SQL transform, or export/import mapping.
- PostgreSQL runtime verification is a later track.

## Slice 5 - Procurement projection raw index SQL cleanup

Status: Focused Verified

FACT:
- `database/migrations/2026_04_23_130000_add_desc_index_for_admin_procurement_default_load.php` previously used raw MySQL `CREATE INDEX ... DESC ... ASC` and `DROP INDEX ... ON ...` statements.
- `database/migrations/2026_04_23_140000_add_invoice_search_index_for_procurement_projection.php` previously used raw MySQL `CREATE INDEX ... DESC ... ASC` and `DROP INDEX ... ON ...` statements.
- This slice replaces those raw MySQL statements with Laravel schema builder `index()` and `dropIndex()` calls.
- This keeps the composite index column coverage on `supplier_invoice_list_projection`.
- This does not preserve explicit MySQL index sort direction syntax in the migration declaration.

DECISION:
- Treat these projection indexes as portability cleanup candidates because they are read-model performance indexes, not canonical financial truth.
- Prefer Laravel schema builder for this slice to reduce avoidable MySQL-only migration syntax in the fresh target schema.
- Do not claim exact query-plan or performance parity from this patch alone.

BOUNDARY:
- This is research/target-schema readiness work.
- This does not modify the live MySQL database by itself.
- This does not claim PostgreSQL production cutover readiness.
- Live transition still requires explicit forward migration, SQL transform, export/import mapping, row-count proof, money reconciliation proof, projection rebuild proof, and rollback planning.

REQUIRED PROOF:
- Syntax/scan proof that raw `CREATE INDEX` and `DROP INDEX ... ON` statements are gone from the two target migrations.
- Fresh MySQL migration proof.
- Targeted procurement/index regression proof.
- Full verification proof before closing this slice.

## Slice 6 - Remaining migration portability scan classification

Status: Classification baseline

FACT:
- The latest remaining scan command was:

    rg -n "GENERATED ALWAYS|virtualAs|storedAs|DB::statement|after\(|->change\(|unsignedInteger|unsignedBigInteger|enum" database/migrations -g "*.php"

- The scan still finds:
  - Laravel framework-owned job table unsigned integer fields.
  - Driver-aware employee nullability SQL.
  - MySQL/MariaDB-gated JSON validity SQL.
  - Product active unique generated-column workaround.
  - Inventory reversal generated-column workaround.

CLASSIFICATION:
1. `database/migrations/0001_01_01_000002_create_jobs_table.php`
   - Category: framework-owned table.
   - Decision: do not patch as part of domain PostgreSQL-readiness hardening.

2. `database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php`
   - Category: accepted driver-aware SQL.
   - Decision: keep. It already uses explicit MySQL/MariaDB and PostgreSQL branches for nullability tightening.

3. `database/migrations/2026_04_02_001100_create_note_mutation_snapshots_table.php`
   - Category: MySQL/MariaDB-gated JSON validity check.
   - Decision: keep. It validates JSON storage on MySQL/MariaDB and skips unsupported drivers.

4. `database/migrations/2026_04_04_100000_create_transaction_workspace_drafts_table.php`
   - Category: MySQL/MariaDB-gated JSON validity check.
   - Decision: keep. It validates JSON storage on MySQL/MariaDB and skips unsupported drivers.

5. `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php`
   - Category: accepted MySQL generated-column workaround.
   - Decision: keep for the MySQL target schema until PostgreSQL transition mapping exists.
   - Transition note: PostgreSQL target should evaluate partial unique indexes for active product uniqueness instead of copying the MySQL generated-column workaround blindly.

6. `database/migrations/2026_05_15_000005_add_unique_inventory_reversal_source_key.php`
   - Category: accepted MySQL generated-column workaround.
   - Decision: keep for the MySQL target schema until PostgreSQL transition mapping exists.
   - Transition note: PostgreSQL target should evaluate partial unique indexes for reversal uniqueness instead of copying the MySQL generated-column workaround blindly.

PROOF:
- SHOW INDEX helper portability cleanup was verified by scan, fresh MySQL migration, targeted database/procurement tests, and full verification.
- Procurement projection raw index SQL cleanup was verified by scan, fresh MySQL migration, targeted procurement/index tests, and full verification.
- Latest full verification after Slice 5:
  - Tests: 2 skipped, 1118 passed (6285 assertions)
  - Duration: 76.51s

CLAIM ALLOWED:
- Current MySQL migration structure has been hardened against the known avoidable MySQL-only migration debt found in this Phase 4 slice.
- Remaining scan findings are either framework-owned, accepted driver-aware SQL, accepted MySQL/MariaDB-gated validation, or accepted MySQL generated-column workarounds requiring future PostgreSQL transition mapping.

CLAIM FORBIDDEN:
- Do not claim PostgreSQL production cutover readiness.
- Do not claim PostgreSQL runtime migration proof.
- Do not claim query-plan or performance parity for PostgreSQL.
- Do not claim edited historical migrations mutate the live MySQL database.

NEXT TRANSITION-ONLY WORK:
- PostgreSQL fresh migration proof.
- PostgreSQL partial unique index mapping for active product uniqueness.
- PostgreSQL partial unique index mapping for inventory reversal uniqueness.
- Data export/import mapping.
- Row-count parity.
- Rupiah reconciliation.
- Projection rebuild proof.
- PostgreSQL application test suite.
- Cutover and rollback runbook.

## Live Production Schema Parity Track - Structure Only

Status: Active planning note
Scope: Compare and align production MySQL table names and table structures against the migration-defined target schema.
Runtime DB now: MySQL
Production DB: `arbiconb_abiad`
Local/reference DB name seen in prior snapshot: `bengkelhex`

## Scope Decision

The live parity target is structure only.

Included:

- table names
- table existence
- column names
- column order where migration compatibility or raw SQL depends on it
- column type
- nullable flag
- default value
- primary key shape
- index and unique key shape
- foreign key shape
- generated columns
- table engine and collation when they affect compatibility

Excluded:

- table row contents
- row count parity
- seeded data parity
- transaction data parity
- data overwrite
- dump/restore data sync

Decision:

- Do not compare or copy business data for this track.
- Do not generate `INSERT`, data `UPDATE`, data `DELETE`, or full data dump scripts from this track.
- Production structure changes must be forward-only SQL or explicit Laravel migration scripts.

## Source Of Truth Order For This Track

Use this priority order:

1. Repository migration files in `database/migrations` define the target structure.
2. Existing migration contract README defines policy and boundaries.
3. Production `information_schema` query output defines current live production structure.
4. Prior local database snapshot is supporting evidence only, not the final target source.

Rationale:

- Local MySQL CLI may not be available or reliable as the schema source.
- Edited historical migrations define the intended research/fresh schema direction.
- The live database will not automatically receive those edits.
- Therefore the correct comparison is target migration schema versus live production schema.

## Current Snapshot Finding From Prior Ad-Hoc Schema Comparison

Status: Informational, not final patch proof.

FACT:

- A prior ad-hoc schema comparison suggested production-like output had extra backup tables not present in the cleaner reference snapshot:
  - `backup_srl_a3kzl_20260419`
  - `supplier_list_projection_backup_before_manual_rebuild`
- A prior ad-hoc comparison also suggested several structure differences around:
  - unsigned-to-signed integer cleanup results
  - JSON/text payload alignment
  - timestamp default/on-update semantics
  - supplier payment proof attachment metadata
  - supplier invoice and receipt line timestamp residue
  - production backup tables

BOUNDARY:

- These findings must not be used as final production patch SQL yet.
- The final diff must be regenerated from repository migrations plus production `information_schema` output.
- Backup-looking production tables must not be dropped until ownership and preservation decision are documented.

## Production Access Constraint

Production allows MySQL query access only.

Allowed production reads:

- `information_schema.TABLES`
- `information_schema.COLUMNS`
- `information_schema.STATISTICS`
- `information_schema.KEY_COLUMN_USAGE`
- `information_schema.TRIGGERS`
- `SHOW CREATE TABLE` if available in the panel

Forbidden production behavior in this track:

- `migrate:fresh`
- destructive table rebuilds
- dropping backup-looking tables without explicit decision
- data import/export for parity
- mass alteration without backup and rollback SQL

## Required Next Work - Migration Schema Inventory

Status: Next active step before patching production.

Goal:

Build a migration-derived target schema inventory from repository files.

Required repository reads:

- all files in `database/migrations/*.php`
- any migration helper/macro used by migrations
- current migration README/contract file
- any handoff that declares DB baseline freeze or production parity plan

Minimum extraction categories:

- `Schema::create(...)`
- `Schema::table(...)`
- `$table->...` column definitions
- index definitions
- unique constraints
- foreign keys
- raw `DB::statement(...)`
- generated-column workarounds
- JSON validity checks
- driver-aware migration branches

Required output artifact:

- `target_schema_from_migrations.md` or equivalent section in this README
- table-by-table target structure summary
- accepted residual classification
- production diff checklist

## Required Production Snapshot Query - Structure Only

Use production SQL only. Do not query row contents.

```sql
SELECT
  TABLE_NAME,
  TABLE_TYPE,
  ENGINE,
  TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'arbiconb_abiad'
ORDER BY TABLE_NAME;

SELECT
  TABLE_NAME,
  ORDINAL_POSITION,
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_DEFAULT,
  COLUMN_KEY,
  EXTRA,
  CHARACTER_SET_NAME,
  COLLATION_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'arbiconb_abiad'
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT
  TABLE_NAME,
  INDEX_NAME,
  NON_UNIQUE,
  SEQ_IN_INDEX,
  COLUMN_NAME,
  SUB_PART,
  INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'arbiconb_abiad'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

SELECT
  TABLE_NAME,
  CONSTRAINT_NAME,
  COLUMN_NAME,
  REFERENCED_TABLE_NAME,
  REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'arbiconb_abiad'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION;

SELECT
  TRIGGER_NAME,
  EVENT_MANIPULATION,
  EVENT_OBJECT_TABLE,
  ACTION_TIMING
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = 'arbiconb_abiad'
ORDER BY EVENT_OBJECT_TABLE, TRIGGER_NAME;
```

## Patch Planning Rule

Do not patch production directly from an unreviewed diff.

Patch sequence must be:

1. read migration contract
2. read all migration files
3. generate migration-derived target schema inventory
4. collect production structure-only snapshot
5. classify diff table-by-table
6. separate harmless backup/legacy tables from active application tables
7. write forward-only SQL patch draft
8. write rollback SQL draft
9. review destructive operations separately
10. execute one small table group at a time
11. verify production structure after each group

## Table Group Order For Slow Execution

Recommended order:

1. non-destructive metadata/collation/type alignment
2. JSON validity and payload alignment
3. timestamp/default alignment
4. revision/counter signed integer alignment
5. supplier/procurement line timestamp cleanup
6. generated-column/index compatibility checks
7. backup-looking table decision
8. final schema verification

Do not combine all groups into one giant patch unless a rollback-tested dry run exists. Large SQL blobs make humans feel powerful right before they delete the wrong thing.

## Required Proof Before Marking Production Structure Parity Done

Required proof:

- migration-derived target schema inventory exists
- production pre-patch structure snapshot exists
- reviewed diff exists
- production backup exists or hosting backup point is documented
- forward SQL exists
- rollback SQL exists
- production post-patch structure snapshot exists
- post-patch diff shows no unintended structural drift
- application smoke test is completed after production structure patch

Allowed claim after proof:

- Production MySQL table structure has been aligned to the migration-defined target schema for the reviewed table groups.

Forbidden claim after proof:

- Production data was synchronized.
- Production PostgreSQL cutover is ready.
- Full application correctness is proven from schema parity alone.
