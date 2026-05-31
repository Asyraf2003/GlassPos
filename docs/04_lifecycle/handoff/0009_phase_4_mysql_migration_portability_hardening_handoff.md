# Handoff - Phase 4 MySQL Migration Portability Hardening

## Metadata

- Date: 2026-05-31
- Slice / topic: Phase 4 - MySQL-side migration portability hardening toward PostgreSQL-aligned target schema
- Workflow step: Phase 4-01 through Phase 4-07
- Status: CLOSED WITH MYSQL-SIDE PROOF
- Progress: Phase 4 MySQL-side avoidable migration portability debt for the current scan is fixed or explicitly classified.

## Target Work Page

Close Phase 4 as a MySQL-side migration hardening milestone.

This phase does not start PostgreSQL implementation, does not perform PostgreSQL cutover, and does not claim production PostgreSQL readiness.

The goal is to mature the current MySQL migration structure so a future PostgreSQL transition does not carry avoidable technical debt that can already be addressed safely in MySQL/fresh-schema research work.

## References Used

- Standards:
  - `docs/04_lifecycle/handoff/README.md`
  - `docs/01_standards/0005_handoff_template.md`
  - `docs/01_standards/core/0010_scope_and_facts.md`
  - `docs/01_standards/core/0011_blueprint_first.md`
  - `docs/01_standards/core/0012_step_by_step_execution.md`
  - `docs/01_standards/core/0013_proof_and_progress.md`
  - `docs/01_standards/workflow/0020_response_structure.md`
  - `docs/01_standards/workflow/0021_active_step_policy.md`
  - `docs/01_standards/output/0033_terminal_command_delivery.md`
- Active prior handoff:
  - `docs/04_lifecycle/handoff/0008_edit_transaction_lifecycle_characterization_handoff.md`
- Audit error log:
  - `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/README.md`
  - `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/9999_summary_matrix.md`
  - `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0010_verification_commands_and_test_baseline.md`
  - `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0001_postgresql_migration_readiness.md`
- Migration contract:
  - `database/migrations/README.md`
- ADR:
  - `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`

## Locked Facts

- Phase 4 is not an active PostgreSQL transition.
- Phase 4 is MySQL-side migration portability hardening toward a PostgreSQL-aligned target schema.
- Runtime DB remains MySQL.
- PostgreSQL implementation remains out of scope until a separate migration project is opened.
- Live MySQL database is not mutated by editing historical migration files.
- `migrate:fresh` must not be run on live.
- Historical migration edits in this research track define the intended clean/fresh schema direction.
- Live transition later still requires explicit forward migration scripts, SQL transform scripts, or controlled export/import mapping.

## Scope Used

### SCOPE-IN

- MySQL fresh-schema migration portability hardening.
- Current scan of avoidable MySQL-only migration syntax.
- Migration helper cleanup where safe.
- Raw MySQL index SQL cleanup where safe.
- README classification of remaining scan findings.
- MySQL fresh migration proof.
- Targeted database/procurement regression proof.
- Full verification proof.

### SCOPE-OUT

- PostgreSQL production cutover.
- PostgreSQL runtime migration proof.
- PostgreSQL driver/environment setup.
- Live MySQL schema mutation.
- Live data transform.
- Data export/import mapping.
- Row-count parity.
- Rupiah reconciliation.
- PostgreSQL application test suite.
- Go API migration.
- Query-plan/performance parity claim.
- Git operation.

## GAP

The following are not gaps for this Phase 4 MySQL-side closure, but remain future transition-only work:

- PostgreSQL fresh migration proof is not available.
- PostgreSQL production cutover readiness is not claimed.
- PostgreSQL partial unique index mapping is still needed for:
  - active product uniqueness currently implemented through MySQL generated-column workaround;
  - inventory reversal uniqueness currently implemented through MySQL virtual generated-column workaround.
- Data export/import mapping is not done.
- Row-count parity is not done.
- Rupiah reconciliation is not done.
- Projection rebuild proof is not done.
- PostgreSQL application test suite is not run.
- Cutover and rollback runbook is not written.
- Query-plan/performance parity for PostgreSQL is not claimed.

## Locked Decisions

- Close Phase 4 only as MySQL-side migration portability hardening.
- Do not claim PostgreSQL production cutover readiness.
- Do not claim PostgreSQL runtime readiness.
- Do not remove generated-column workarounds that protect domain/inventory uniqueness without PostgreSQL transition mapping.
- Treat Laravel framework-owned job table unsigned fields as out of domain migration hardening scope.
- Treat employee migration raw SQL as accepted driver-aware SQL.
- Treat JSON validity raw SQL as accepted MySQL/MariaDB-gated validation.
- Treat product `active_unique_marker` generated column as accepted MySQL workaround requiring future PostgreSQL partial unique index mapping.
- Treat inventory reversal `virtualAs()` generated column as accepted MySQL workaround requiring future PostgreSQL partial unique index mapping.

## Files Created / Changed

### New files

- `docs/04_lifecycle/handoff/0009_phase_4_mysql_migration_portability_hardening_handoff.md`

### Changed files

- `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php`
- `database/migrations/2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php`
- `database/migrations/2026_04_18_000100_alter_supplier_invoice_lines_for_revisioned_post_receipt_edit.php`
- `database/migrations/2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php`
- `database/migrations/2026_04_23_130000_add_desc_index_for_admin_procurement_default_load.php`
- `database/migrations/2026_04_23_140000_add_invoice_search_index_for_procurement_projection.php`
- `database/migrations/README.md`

## Phase 4 Work Completed

### Phase 4-01 - Goal correction and blueprint

Decision:
- Corrected Phase 4 framing from "run PostgreSQL transition now" to "MySQL migration portability hardening toward PostgreSQL-aligned target schema."

Meaning:
- PostgreSQL runtime proof is future transition work.
- Current work should remove or classify avoidable MySQL-side migration debt.

### Phase 4-02 - Baseline scan and MySQL migration proof

Proof:
- `php artisan migrate:fresh --force`
- Result:
  - all migrations completed with `DONE`.

Meaning:
- Current MySQL fresh-schema baseline was GREEN before/around hardening work.

### Phase 4-03 - `SHOW INDEX FROM` helper cleanup

Changed files:
- `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php`
- `database/migrations/2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php`
- `database/migrations/2026_04_18_000100_alter_supplier_invoice_lines_for_revisioned_post_receipt_edit.php`
- `database/migrations/2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php`

Decision:
- Replace raw MySQL `SHOW INDEX FROM ...` helper logic with Laravel-native `Schema::getIndexes()`.

Proof:
- `rg -n -F "SHOW INDEX FROM" ...`
  - result: no output for the four target migration files.
- `rg -n -F "Schema::getIndexes" ...`
  - result:
    - `database/migrations/2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php:52`
    - `database/migrations/2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php:71`
    - `database/migrations/2026_04_18_000100_alter_supplier_invoice_lines_for_revisioned_post_receipt_edit.php:142`
    - `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php:81`

Meaning:
- Raw MySQL metadata lookup was removed from the four target migration helpers.

### Phase 4-04 - Targeted regression after `SHOW INDEX` cleanup

Proof:
- command:
  - `php artisan test tests/Feature/Database/V2MasterSoftDeleteFoundationMigrationTest.php tests/Feature/Database/V2MasterVersioningFoundationMigrationTest.php tests/Feature/Database/V2HotPathIndexesMigrationTest.php tests/Feature/Database/V2ProductSearchNormalizationMigrationTest.php tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`
- result:
  - `Tests: 12 passed (93 assertions)`
  - `Duration: 6.56s`

Meaning:
- Database/procurement behavior adjacent to the edited migration helpers remained GREEN.

### Phase 4-05 - Full verification after `SHOW INDEX` cleanup

Proof:
- command:
  - `make verify`
- result:
  - `Tests: 2 skipped, 1118 passed (6285 assertions)`
  - `Duration: 79.44s`

Meaning:
- Full local verification remained GREEN after `SHOW INDEX` helper portability cleanup.

### Phase 4-06 - Procurement projection raw index SQL cleanup

Changed files:
- `database/migrations/2026_04_23_130000_add_desc_index_for_admin_procurement_default_load.php`
- `database/migrations/2026_04_23_140000_add_invoice_search_index_for_procurement_projection.php`
- `database/migrations/README.md`

Decision:
- Replace raw MySQL `CREATE INDEX ... DESC ... ASC` and `DROP INDEX ... ON ...` statements with Laravel schema builder `index()` and `dropIndex()`.
- Keep composite index column coverage.
- Do not claim exact query-plan or performance parity because explicit MySQL sort direction syntax is no longer declared.

Proof:
- README anchors:
  - `database/migrations/README.md:609` - `Slice 5 - Procurement projection raw index SQL cleanup`
  - `database/migrations/README.md:611` - `Status: Pending focused verification`
  - `database/migrations/README.md:618` - note that explicit MySQL index sort direction is not preserved.
- raw SQL scans:
  - `DB::statement` scan on two target files: no output.
  - `CREATE INDEX` scan on two target files: no output.
  - `DROP INDEX` scan on two target files: no output.
- command:
  - `php artisan migrate:fresh --force`
- result:
  - all migrations completed with `DONE`.
- command:
  - `php artisan test tests/Feature/Database/V2HotPathIndexesMigrationTest.php tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`
- result:
  - `Tests: 6 passed (41 assertions)`
  - `Duration: 6.23s`
- command:
  - `make verify`
- result:
  - `Tests: 2 skipped, 1118 passed (6285 assertions)`
  - `Duration: 76.51s`

Meaning:
- Procurement projection raw index SQL cleanup remained compatible with fresh MySQL migration, targeted procurement/index regression, and full verification.

### Phase 4-07 - Remaining migration portability scan classification

Scan command:
- `rg -n "GENERATED ALWAYS|virtualAs|storedAs|DB::statement|after\(|->change\(|unsignedInteger|unsignedBigInteger|enum" database/migrations -g "*.php"`

Result categories:
- `database/migrations/0001_01_01_000002_create_jobs_table.php`
  - `unsignedInteger` fields.
  - Decision: framework-owned table, do not patch in domain hardening slice.
- `database/migrations/2026_04_10_000100_alter_employees_table_for_employee_master_v2.php`
  - `DB::statement`.
  - Decision: accepted driver-aware SQL.
- `database/migrations/2026_04_02_001100_create_note_mutation_snapshots_table.php`
  - `DB::statement`.
  - Decision: accepted MySQL/MariaDB-gated JSON validation.
- `database/migrations/2026_04_04_100000_create_transaction_workspace_drafts_table.php`
  - `DB::statement`.
  - Decision: accepted MySQL/MariaDB-gated JSON validation.
- `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php`
  - `DB::statement`
  - `GENERATED ALWAYS`
  - Decision: accepted MySQL generated-column workaround for active product uniqueness; future PostgreSQL transition should map this to a PostgreSQL partial unique index candidate.
- `database/migrations/2026_05_15_000005_add_unique_inventory_reversal_source_key.php`
  - `virtualAs(...)`
  - Decision: accepted MySQL generated-column workaround for inventory reversal uniqueness; future PostgreSQL transition should map this to a PostgreSQL partial unique index candidate.

README classification:
- `database/migrations/README.md` was updated with Slice 6 classification.

Final proof:
- command:
  - `make verify`
- result:
  - `Tests: 2 skipped, 1118 passed (6285 assertions)`
  - `Duration: 74.84s`

Meaning:
- After final classification documentation, full local verification remained GREEN.

## Verification Proof

- command:
  - `php artisan migrate:fresh --force`
- result:
  - all migrations completed with `DONE`
- meaning:
  - fresh MySQL target schema can be rebuilt after Phase 4 migration hardening edits.

- command:
  - `rg -n -F "SHOW INDEX FROM" ...`
- result:
  - no output for the four target migration files
- meaning:
  - raw MySQL `SHOW INDEX FROM` lookup was removed from the target migration helpers.

- command:
  - `rg -n -F "Schema::getIndexes" ...`
- result:
  - four target migration files contain `Schema::getIndexes()`
- meaning:
  - index existence checks now use Laravel-native schema introspection.

- command:
  - `php artisan test tests/Feature/Database/V2MasterSoftDeleteFoundationMigrationTest.php tests/Feature/Database/V2MasterVersioningFoundationMigrationTest.php tests/Feature/Database/V2HotPathIndexesMigrationTest.php tests/Feature/Database/V2ProductSearchNormalizationMigrationTest.php tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`
- result:
  - `Tests: 12 passed (93 assertions)`
  - `Duration: 6.56s`
- meaning:
  - targeted database/procurement regression passed after the first helper cleanup slice.

- command:
  - `php artisan test tests/Feature/Database/V2HotPathIndexesMigrationTest.php tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`
- result:
  - `Tests: 6 passed (41 assertions)`
  - `Duration: 6.23s`
- meaning:
  - targeted procurement/index regression passed after raw procurement projection index SQL cleanup.

- command:
  - `make verify`
- result after `SHOW INDEX` helper cleanup:
  - `Tests: 2 skipped, 1118 passed (6285 assertions)`
  - `Duration: 79.44s`
- meaning:
  - full local verification remained GREEN after helper cleanup.

- command:
  - `make verify`
- result after procurement projection index cleanup:
  - `Tests: 2 skipped, 1118 passed (6285 assertions)`
  - `Duration: 76.51s`
- meaning:
  - full local verification remained GREEN after raw index SQL cleanup.

- command:
  - `make verify`
- final result after README remaining scan classification:
  - `Tests: 2 skipped, 1118 passed (6285 assertions)`
  - `Duration: 74.84s`
- meaning:
  - full local verification remained GREEN after final Phase 4 documentation/classification.

## Final Status

Phase 4 is CLOSED for MySQL-side migration portability hardening.

Allowed claim:
- Current MySQL migration structure has been hardened against the known avoidable MySQL-only migration debt found in this Phase 4 scan.
- Remaining scan findings are either framework-owned, accepted driver-aware SQL, accepted MySQL/MariaDB-gated validation, or accepted MySQL generated-column workarounds requiring future PostgreSQL transition mapping.

Forbidden claim:
- Do not claim PostgreSQL production cutover readiness.
- Do not claim PostgreSQL runtime migration proof.
- Do not claim PostgreSQL query-plan or performance parity.
- Do not claim edited historical migrations mutate the live MySQL database.

## Risks / Follow-up Notes

- Product active uniqueness still needs future PostgreSQL mapping, likely through partial unique indexes.
- Inventory reversal uniqueness still needs future PostgreSQL mapping, likely through partial unique indexes.
- Query-plan/performance parity for procurement projection indexes is not proven.
- PostgreSQL driver/runtime proof is still unavailable.
- Live MySQL transition requires separate forward migration or SQL transform path.
- Future PostgreSQL transition must include row-count parity, rupiah reconciliation, projection rebuild proof, app test suite on PostgreSQL, and rollback runbook.

## Next Step

Move to the next full-repo audit queue item after owner acceptance.

Recommended next audit item:
- `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0002_seeder_role_contract.md`

Reason:
- `0006_payment_concurrency_characterization_gap.md` is fixed with proof for MySQL local payment/payment and payment/refund same-note concurrency characterization.
- `0001_postgresql_migration_readiness.md` is now closed for MySQL-side avoidable migration portability debt and remaining transition-only mappings are classified.
- Summary matrix previously placed `0002_seeder_role_contract.md` after `0001`.

Do not start the next audit item until Phase 4 documentation is verified locally.

## Opening Prompt For Next Session

Use this prompt exactly.

Kita lanjut repo HyperPOS. Jangan sentuh git kecuali saya eksplisit minta.

Baca rules dulu:
- docs/04_lifecycle/handoff/README.md
- docs/01_standards/0005_handoff_template.md
- docs/01_standards/core/0010_scope_and_facts.md
- docs/01_standards/core/0011_blueprint_first.md
- docs/01_standards/core/0012_step_by_step_execution.md
- docs/01_standards/core/0013_proof_and_progress.md
- docs/01_standards/workflow/0020_response_structure.md
- docs/01_standards/workflow/0021_active_step_policy.md
- docs/01_standards/output/0033_terminal_command_delivery.md
- docs/04_lifecycle/handoff/0009_phase_4_mysql_migration_portability_hardening_handoff.md

Current status:
- Phase 3 payment concurrency remediation is closed with proof for MySQL local payment/payment and payment/refund same-note concurrency characterization.
- Phase 4 MySQL migration portability hardening is closed with proof.
- Phase 4 was not PostgreSQL implementation and did not claim PostgreSQL production cutover readiness.
- Phase 4 goal was to remove or classify avoidable MySQL-side migration portability debt before future PostgreSQL transition.
- SHOW INDEX helper debt was fixed by replacing raw `SHOW INDEX FROM ...` lookup with Laravel-native `Schema::getIndexes()`.
- Procurement projection raw MySQL index SQL was fixed by replacing raw `CREATE INDEX ... DESC ... ASC` / `DROP INDEX ... ON ...` with Laravel schema builder `index()` / `dropIndex()`.
- Remaining scan findings were classified:
  - Laravel jobs table unsigned fields: framework-owned, do not patch in domain hardening slice.
  - Employee migration raw SQL: accepted driver-aware SQL.
  - JSON validity raw SQL: accepted MySQL/MariaDB-gated validation.
  - Product active uniqueness generated column: accepted MySQL workaround; future PostgreSQL transition should map to partial unique index candidate.
  - Inventory reversal virtual generated column: accepted MySQL workaround; future PostgreSQL transition should map to partial unique index candidate.

Phase 4 proof:
- `php artisan migrate:fresh --force` passed after migration hardening.
- SHOW INDEX scan returned no output for the four target migration files.
- `Schema::getIndexes()` scan found four target migration files.
- Targeted database/procurement regression passed: `12 passed (93 assertions)`.
- Targeted procurement/index regression passed: `6 passed (41 assertions)`.
- Full verify passed after SHOW INDEX cleanup: `2 skipped, 1118 passed (6285 assertions)`, duration `79.44s`.
- Full verify passed after raw projection index cleanup: `2 skipped, 1118 passed (6285 assertions)`, duration `76.51s`.
- Final full verify passed after README classification: `2 skipped, 1118 passed (6285 assertions)`, duration `74.84s`.

Next audit candidate:
- `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0002_seeder_role_contract.md`

Start by reading:
- docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/README.md
- docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/9999_summary_matrix.md
- docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0010_verification_commands_and_test_baseline.md
- docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0002_seeder_role_contract.md

Use FACT, GAP, DECISION, BLUEPRINT, ACTIVE STEP, PROOF, NEXT, PROGRESS.

One active step only.
Blueprint first.
Do not patch before proof.
Do not claim anything passed without command output proof.
