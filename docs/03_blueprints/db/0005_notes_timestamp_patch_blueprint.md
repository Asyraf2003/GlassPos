# DB Blueprint 0005 - Notes Timestamp Patch Blueprint

Status: Patch Blueprinted  
Scope: `notes` system row timestamp hardening  
Owner: HyperPOS  

## 1. Active Table Group

Table group:

- `notes`

Category:

- Transaction header table

Source-of-truth status:

- P0 finance-sensitive root transaction header

## 2. Exact Problem

`notes` currently separates business date and lifecycle action timestamps, but does not have proven system row timestamps.

Current proven dates:

- `transaction_date` is the business/report date.
- `due_date` is the payment due date.
- `closed_at` is the close lifecycle action timestamp.
- `reopened_at` is the reopen lifecycle action timestamp.

Current gap:

- `created_at` is missing.
- `updated_at` is missing.
- `DatabaseNoteWriterAdapter` creates and updates `notes` without system timestamps.
- Many tests and seeders insert into `notes` directly, so adding non-null timestamps naively can break fixtures.

## 3. Current Proven Schema

Base migration:

- `database/migrations/2026_03_14_000100_create_notes_table.php`

Proven columns:

- `id`
- `customer_name`
- `customer_phone`
- `transaction_date`
- `note_state`
- `closed_at`
- `closed_by_actor_id`
- `reopened_at`
- `reopened_by_actor_id`
- `total_rupiah`

Alter migrations:

- `database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php`
- `database/migrations/2026_04_27_000100_add_due_date_to_notes_table.php`

Proven added columns:

- `current_revision_id`
- `latest_revision_number`
- `due_date`

## 4. Current Proven Writer Behavior

Writer:

- `app/Adapters/Out/Note/DatabaseNoteWriterAdapter.php`

Current behavior:

- `create()` inserts note identity, customer fields, business date, due date, lifecycle state, lifecycle actor ids, and total.
- `updateHeader()` updates customer fields, business date, and due date.
- `updateTotal()` updates total.
- `updateOperationalState()` updates state, close/reopen timestamps, and close/reopen actor ids.
- No method currently writes `created_at`.
- No method currently writes `updated_at`.

## 5. Current Proven Fixture/Test Risk

Direct insert/updateOrInsert into `notes` exists in many tests and seeders.

Known direct insert/updateOrInsert examples include:

- `tests/Support/SeedsMinimalNotePaymentFixture.php`
- `database/seeders/Transaction/CustomerTransactionBaselineSeeder.php`
- `database/seeders/Load/CustomerTransactionLoadSeeder.php`
- `tests/Feature/Database/V2NoteOperationalStateMigrationTest.php`
- reporting/export tests
- payment tests
- refund tests
- note revision tests
- inventory reversal tests
- dashboard tests
- due reminder tests

Risk:

- A `NOT NULL` timestamp migration without DB default/backfill can break many direct inserts.
- A writer-only timestamp patch will not cover direct test fixtures unless schema allows safe defaults or fixtures are migrated carefully.

## 6. Recommended Schema Change

Create a new migration. Do not edit old migrations.

Recommended first patch:

- Add nullable-safe/backfilled `created_at`.
- Add nullable-safe/backfilled `updated_at` only if writer update behavior is patched in the same slice.
- Keep `transaction_date` as business/report date.
- Keep `due_date` as due date.
- Keep `closed_at` and `reopened_at` as lifecycle action timestamps.
- Do not expose new timestamps to the domain object unless a use case needs them.

Preferred column semantics:

- `created_at`: system row creation/persistence timestamp.
- `updated_at`: system row mutation timestamp.

## 7. Backfill Policy

Do not copy `transaction_date` into `created_at`.

Reason:

- `transaction_date` is a business/report date.
- `created_at` is system persistence time.
- Existing historical row creation time is unknowable from current schema.

Safe policy candidates:

1. Backfill existing rows with migration execution time.
2. Allow nullable `created_at` temporarily and only enforce writer behavior going forward.
3. Backfill from a related audit/event source only if exact row creation proof exists.

Current recommendation:

- Use migration execution time for existing rows if `created_at` must be non-null.
- Record this as a known audit limitation.
- Do not claim historical creation accuracy for pre-patch rows.

## 8. Domain Impact

Expected domain impact:

- No change to note business date.
- No change to note due date.
- No change to close/reopen lifecycle semantics.
- No change to payment/refund/report period semantics.
- No new public/domain contract unless explicitly required later.

Forbidden impact:

- Do not use `created_at` for finance report period.
- Do not change `transaction_date` meaning.
- Do not rename existing locked terms.

## 9. Report Impact

Expected report impact:

- None, if reports continue using `transaction_date`, payment date, refund date, supplier date, or movement date as appropriate.

Required proof:

- Focused reporting tests must still pass if touched indirectly.
- No report query should silently switch to `created_at`.

## 10. PostgreSQL Readiness Impact

Patch must avoid:

- MySQL-only timestamp behavior as business rule.
- relying on unsigned-only invariant.
- implicit timestamp defaults that cannot be made portable.
- JSON as timestamp truth.

Preferred:

- Explicit application timestamp writes where writer behavior matters.
- Migration/backfill behavior that can be expressed clearly in Laravel schema/DB operations.

## 11. CRUD And Read Path Impact

No new index is approved in this slice.

Reason:

- No proven read path currently needs sorting/filtering by `created_at` or `updated_at`.
- Index hardening must follow real read path proof, not vibes wearing a lab coat.

## 12. Files To Touch In Patch Slice

Expected files:

- new migration under `database/migrations/`
- `app/Adapters/Out/Note/DatabaseNoteWriterAdapter.php`
- focused migration/database test
- focused writer or note persistence test if available
- `docs/03_blueprints/db/0004_db_audit_matrix.md` after proof

Possible test files:

- `tests/Feature/Database/V2NoteOperationalStateMigrationTest.php`
- a focused note writer/persistence test if one exists or can be created minimally

## 13. Files Not To Touch

Do not touch in this slice:

- payment allocation logic
- refund logic
- supplier invoice/payment/receipt logic
- inventory movement logic
- note revision finance behavior
- reporting query semantics
- UI
- API/mobile
- PostgreSQL migration implementation
- Go API

## 14. Characterization Proof Plan

Minimum characterization should prove:

- `notes` currently has no `created_at` / `updated_at`, or
- new migration adds expected columns safely, depending on whether the test is written before or after migration creation.

If RED proof is not practical because schema tests are migration-state dependent, record it as a characterization gap and use post-patch migration assertions.

## 15. GREEN Proof Plan

Minimum proof after patch:

- `php -l` for changed PHP files.
- targeted migration/database test for `notes` timestamp columns.
- targeted writer test or feature test proving created notes receive timestamps.
- existing `V2NoteOperationalStateMigrationTest` still passes.
- focused tests for direct note inserts most likely to break.
- `git diff --check`.

## 16. Rollback Or Defer Criteria

Stop or defer if:

- patch requires changing many unrelated fixtures manually;
- report semantics would change;
- historical `created_at` would be falsely inferred from `transaction_date`;
- direct insert compatibility cannot be preserved cleanly;
- writer timestamp behavior cannot be tested narrowly.

## 17. Current Decision

Patch is not yet implemented.

Next safe step:

- inspect existing migration/database tests for best location to assert `notes.created_at` and `notes.updated_at`;
- inspect whether there is an existing note writer persistence test;
- then create the smallest characterization/GREEN path.
