# Handoff 0002 - MySQL PostgreSQL-Aligned Migration Research

Status: Safe-state handoff
Scope: Migration research track for making MySQL schema more PostgreSQL-aligned before future live transition
Owner: HyperPOS

## Final Goal

Goal utama project ini bukan pindah PostgreSQL sekarang.

Goal utama adalah membuat struktur MySQL saat ini makin matang, eksplisit, dan PostgreSQL-aligned supaya nanti transisi live MySQL ke PostgreSQL jauh lebih mudah, lebih terukur, dan tidak gelap.

Repo ini dipakai sebagai research/target-schema track.

Live system masih memakai MySQL.

Perubahan migration lama di research repo tidak otomatis mengubah database live yang sudah pernah migrate.

## Working Model

Model kerja yang terkunci:

1. Live MySQL tetap berjalan.
2. Migration di repo ini dipakai untuk mematangkan target schema.
3. README migration menjadi catatan fisik before/after dan live-transition boundary.
4. Setelah target schema matang, perubahan dipindahkan ke app live lewat forward migration, SQL transform, atau export/import mapping.
5. Data lama dipertahankan lewat mapping, bukan dibuang.
6. Projection dapat direbuild dari source table jika source lengkap.
7. Semua rupiah harus tetap integer dan direkonsiliasi 1 rupiah exact.

## Files Changed In This Slice

Migration files changed:

- database/migrations/2026_03_12_000200_create_supplier_invoices_table.php
- database/migrations/2026_04_06_230200_add_soft_delete_foundation_to_products_and_suppliers.php
- database/migrations/2026_04_06_230300_create_product_and_supplier_versions_tables.php
- database/migrations/2026_04_06_230400_add_product_search_normalization_and_duplicate_hardening.php
- database/migrations/2026_04_09_000200_create_supplier_invoice_versions_table.php
- database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php
- database/migrations/2026_04_10_000200_create_employee_versions_table.php
- database/migrations/2026_04_17_013500_add_stock_threshold_columns_to_products_table.php
- database/migrations/2026_04_18_000100_alter_supplier_invoice_lines_for_revisioned_post_receipt_edit.php
- database/migrations/2026_04_18_000200_alter_supplier_receipt_lines_add_snapshots.php
- database/migrations/2026_04_18_000300_create_inventory_cost_adjustments_table.php
- database/migrations/2026_04_19_100000_create_supplier_invoice_list_projection_table.php
- database/migrations/2026_04_19_100100_create_note_history_projection_table.php
- database/migrations/2026_04_22_000001_create_note_revisions_table.php
- database/migrations/2026_04_22_000002_create_note_revision_lines_table.php
- database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php
- database/migrations/2026_04_23_150000_create_supplier_list_projection_table.php
- database/migrations/2026_04_27_000700_add_payment_method_and_cash_details_to_customer_payments.php
- database/migrations/2026_05_15_000004_add_operational_timestamps_to_inventory_movements.php
- database/migrations/README.md

Docs/handoff files created or updated by this handoff step:

- docs/99_archive/handoff/db/0002_mysql_postgresql_aligned_migration_research_handoff.md
- docs/99_archive/handoff/db/README.md

## Slice 1 - Unsigned Cleanup

Status: verified.

Problem:

MySQL unsigned integer types are not PostgreSQL-native.

Research target:

Remove domain dependency on MySQL unsigned for counters, revision numbers, and note revision money fields.

Changed shape:

- domain revision/counter fields moved from unsignedInteger to integer
- note revision money fields moved from unsignedBigInteger to bigInteger
- framework jobs unsigned fields were intentionally not touched
- supplier payment proof file_size_bytes was intentionally not touched because it is metadata, not money

Domain counter/revision examples changed:

- supplier_invoices.last_revision_no
- inventory_cost_adjustments.source_revision_no
- supplier_invoice_lines.revision_no
- employee_versions.revision_no
- supplier_invoice_versions.revision_no
- product_versions.revision_no
- supplier_versions.revision_no
- notes.latest_revision_number
- note_revisions.revision_number
- note_revisions.line_count
- note_revision_lines.line_no
- supplier and note projection count fields

Domain money examples changed:

- note_revisions.grand_total_rupiah
- note_revision_lines.service_price_rupiah
- note_revision_lines.subtotal_rupiah

Remaining intentionally unpatched:

- 0001_01_01_000002_create_jobs_table.php unsigned framework fields
- supplier_payment_proof_attachments.file_size_bytes
- README mentions of unsigned scan patterns

## Slice 2 - MySQL after() Layout Helper Cleanup

Status: verified.

Problem:

after() is MySQL-specific column ordering behavior and not relevant to domain truth.

Research target:

Remove domain after() usage from migrations while preserving schema semantics.

Changed shape:

- removed after() from product/supplier soft-delete foundation
- removed after() from product search normalization
- removed after() from supplier invoice line revision additions
- removed after() from supplier receipt line snapshots
- removed after() from employee master v2 additive columns
- removed after() from stock threshold columns
- removed after() from current revision pointer on notes
- removed after() from inventory movement operational timestamps
- removed after() from payment method addition
- tidied dangling semicolon in customer payment method migration

Remaining intentionally unpatched:

- change() in employee migration
- framework longText and mediumText
- domain payload_json longText
- supplier payment proof file_size_bytes unsignedBigInteger

## Proof

Syntax proof:

- all touched migration files passed php -l when tested in the session
- payment method migration tidy passed php -l

Fresh MySQL testing migration proof:

    php artisan migrate:fresh --env=testing

Result:

- all migrations completed successfully after unsigned cleanup
- all migrations completed successfully after after() cleanup

Database feature proof after unsigned cleanup:

    php artisan test tests/Feature/Database --stop-on-failure

Result:

- 26 passed / 241 assertions

Database feature proof after after() cleanup:

    php artisan test tests/Feature/Database --stop-on-failure

Result:

- 26 passed / 241 assertions

Full verification proof after unsigned cleanup:

    make verify

Result:

- PHPStan OK, no errors
- audit line limit SUCCESS
- audit Blade PHP/directive SUCCESS
- contract audit passed
- Pest 1063 passed / 5769 assertions

Full verification proof after after() cleanup:

    make verify

Result:

- Pest 1063 passed / 5769 assertions
- Duration 57.59s
- Owner output returned to shell

## Current Scan Residuals

Current expected residual scan categories:

1. Framework-owned, intentionally skipped:
   - users payload longText
   - cache mediumText
   - jobs longText, mediumText, unsigned queue fields

2. Employee migration change():
   - employee_name nullable(false).change()
   - salary_basis_type nullable(false).change()
   - employment_status nullable(false).change()
   - name nullable(false).change()
   - base_salary nullable(false).change()
   - pay_period nullable(false).change()
   - status nullable(false).change()

3. Domain payload_json longText:
   - transaction_workspace_drafts.payload_json
   - note_mutation_snapshots.payload_json

4. Metadata unsigned:
   - supplier_payment_proof_attachments.file_size_bytes

5. README scan pattern mentions.

## Locked Decisions

- This is research/target-schema work, not direct live DB mutation.
- Live MySQL must not run migrate:fresh.
- Historical migration edits do not change already-migrated live DB schema.
- Live transition later must use explicit forward migration, SQL transform, or export/import mapping.
- Old data should be preserved through mapping.
- Integer rupiah model remains locked.
- Do not use float or decimal for official rupiah truth.
- Do not rely on MySQL unsigned as the only non-negative invariant.
- Do not treat projection tables as canonical financial truth.
- Do not change seeder yet until migration contract is stable.
- Do not patch application/system behavior without a source/test reason.
- Do not touch PostgreSQL runtime implementation yet unless explicitly selected.
- Do not claim production PostgreSQL cutover readiness.

## Remaining Gaps

- PostgreSQL runtime migration has not been executed.
- PostgreSQL test database harness has not been created.
- Live MySQL data migration SQL has not been created.
- Live data export/import mapping has not been created.
- Row-count parity proof does not exist yet.
- Rupiah reconciliation proof across live data does not exist yet.
- Projection rebuild proof for migrated data does not exist yet.
- change() cleanup is Focused Verified for employee master v2 migration.
- payload_json longText classification is not handled yet.
- file_size_bytes unsigned metadata decision is not handled yet.
- Full PostgreSQL application test suite does not exist yet.
- Cutover and rollback runbook does not exist yet.

## Goal Progress

Final Goal Progress:

- 83 percent for MySQL target schema becoming PostgreSQL-aligned.
- Reason: ID and money direction were already strong, unsigned domain dependency was reduced, after() layout helpers were removed, README physical transition notes were added, fresh migration proof passed, and make verify passed.

Main Process Progress:

- 42 percent for live PostgreSQL transition readiness.
- Reason: target schema research has progressed, but PostgreSQL runtime harness, data mapping, parity checks, reconciliation, and cutover runbook are still missing.

Sub-step Progress:

- 100 percent for unsigned cleanup.
- 100 percent for domain after() cleanup.
- 100 percent for Slice 3 employee change() cleanup within research scope.
- 0 percent for payload_json classification.
- 0 percent for PostgreSQL runtime harness.
- 0 percent for live data migration SQL.

## Next Safe Step

Next active slice:

Migration hardening Slice 3 - employee change() cleanup is Focused Verified.

Do not patch immediately.

Employee master v2 migration `change()` cleanup has been patched and verified with explicit driver-aware nullability SQL.

Minimum first command:

    sed -n '1,140p' database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php

    rg -n "employee_name|salary_basis_type|employment_status|base_salary|pay_period|employee_versions|employee_debts|payroll_disbursements" app tests database --glob '*.php'

Expected next output:

- classify change() as:
  - keep temporarily,
  - rewrite in research migration history,
  - split into safer forward migration,
  - or defer until PostgreSQL harness.

Stop condition:

- Stop if cleanup changes employee finance behavior, payroll/debt semantics, or existing test fixtures without proof.

## Next Session Opening Prompt

Lanjut HyperPOS DB migration research.

Baca berurutan:

- docs/01_standards/0001_index.md
- docs/01_standards/0002_decision_policy.md
- docs/03_blueprints/db/0002_mysql_postgresql_crud_readiness_blueprint.md
- docs/03_blueprints/db/0003_db_hardening_workflow.md
- docs/03_blueprints/db/0004_db_audit_matrix.md
- docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md
- database/migrations/README.md
- docs/99_archive/handoff/db/README.md
- docs/99_archive/handoff/db/0002_mysql_postgresql_aligned_migration_research_handoff.md

Baseline FACT:

- Repo ini dipakai sebagai research/target-schema track.
- Live system masih MySQL.
- Editing historical migrations here does not mutate live MySQL DB.
- Live transition later requires explicit forward migration, SQL transform, or export/import mapping.
- User handles commit/push manually.
- Local command output is source of truth.
- README migration is the physical note for before/after and live transition boundary.
- Slice 1 unsigned cleanup is verified.
- Slice 2 after() cleanup is verified.
- make verify passed with 1063 tests / 5769 assertions.
- Do not start by changing seeders.
- Do not start PostgreSQL runtime implementation yet.
- Do not claim production PostgreSQL cutover readiness.

Current changed scope:

- database/migrations/*
- database/migrations/README.md
- docs/99_archive/handoff/db/0002_mysql_postgresql_aligned_migration_research_handoff.md
- docs/99_archive/handoff/db/README.md

Latest completed:

- Domain unsigned counter/revision cleanup.
- Domain unsigned money cleanup for note revision tables.
- Domain after() layout-helper cleanup.
- Fresh MySQL testing migration passed.
- Database feature tests passed 26 / 241.
- make verify passed 1063 / 5769.

Current residuals:

- change() cleanup verified; clean scan has no remaining `->change()` in `database/migrations`.
- payload_json longText remains in transaction workspace drafts and note mutation snapshots.
- framework longText/mediumText remains skipped.
- framework jobs unsigned remains skipped.
- supplier_payment_proof_attachments.file_size_bytes remains metadata unsigned.

Active target:

- Slice 3 employee migration change() cleanup is Focused Verified.
- Do not patch before classification.

Required response shape:

FACT
GAP
ASSUMPTION
DECISION
ACTIVE STEP
FILES TO TOUCH
FILES NOT TO TOUCH
COMMAND
EXPECTED PROOF
NEXT

First command:

    sed -n '1,140p' database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php

    rg -n "employee_name|salary_basis_type|employment_status|base_salary|pay_period|employee_versions|employee_debts|payroll_disbursements" app tests database --glob '*.php'

Stop at classification before implementation.

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
