
DB Blueprint 0003 - DB Hardening Workflow

Status: Planning workflow
Scope: database hardening execution workflow for temporal audit, PostgreSQL readiness, CRUD readiness, and read path readiness
Date: 2026-05-14
Owner: HyperPOS

1. Purpose

Workflow ini menetapkan urutan kerja untuk memperbaiki kualitas database HyperPOS secara bertahap tanpa membuat patch yang saling memutar balik.

Workflow ini adalah prosedur eksekusi dari:

docs/03_blueprints/db/0001_temporal_audit_columns_blueprint.md
docs/03_blueprints/db/0002_mysql_postgresql_crud_readiness_blueprint.md
docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md

Tujuan akhirnya:

date and time semantics pada finance sensitive table menjadi jelas.
schema tetap MySQL compatible untuk kondisi sekarang.
schema baru dan patch lama tetap PostgreSQL ready.
CRUD dan read path utama bisa diarahkan ke target under 1 second tanpa index asal asalan.
patch dilakukan dari root dependency agar perbaikan berikutnya tidak membatalkan perbaikan sebelumnya.
2. Non Goals

Workflow ini tidak:

mengizinkan broad schema rewrite.
mengizinkan migrasi PostgreSQL.
mengizinkan implementasi Go API.
mengklaim performa CRUD sudah under 1 second.
mengklaim semua P0 table sudah aman.
mengizinkan patch schema sebelum audit matrix dan source proof cukup.
mengganti domain term yang sudah terkunci seperti transaction_date, paid_at, refunded_at, tanggal_pengiriman, tanggal_terima, atau tanggal_mutasi.
3. Source Priority

Keputusan kerja harus mengikuti prioritas ini:

local command output dari operator.
AI_RULES.
ADR yang diterima, terutama ADR 0028.
active DB blueprint.
active DB workflow ini.
source code saat ini.
test output.
older handoff atau archive hanya sebagai referensi.

Jika dokumen mengklaim aman tetapi source atau command output membuktikan gap, source dan command output menang.

4. Evidence Labels

Gunakan label ini di matrix, patch note, dan respons kerja:

FACT: terbukti dari command output, source, migration, test, ADR, atau blueprint.
GAP: belum terbukti, belum dicek, belum dites, atau masih butuh owner decision.
RISK: risiko finance, stock, report, audit, PostgreSQL portability, CRUD performance, atau regression.
DECISION: keputusan kerja yang boleh dijalankan.
PROOF: output yang membuktikan perubahan atau status.
STOP: kondisi yang menghentikan patch.
NEXT: satu langkah aktif berikutnya.
5. Current Proven Baseline

Baseline awal dari command output operator pada 2026-05-14:

branch: main
HEAD: fa95bed5
DB blueprint files:
docs/03_blueprints/db/0001_temporal_audit_columns_blueprint.md
docs/03_blueprints/db/0002_mysql_postgresql_crud_readiness_blueprint.md

P0 migration candidates yang sudah terbukti dari local command output:

database/migrations/2026_03_12_000200_create_supplier_invoices_table.php
database/migrations/2026_03_12_000400_create_supplier_receipts_table.php
database/migrations/2026_03_12_000600_create_inventory_movements_table.php
database/migrations/2026_03_12_000800_create_supplier_payments_table.php
database/migrations/2026_03_14_000100_create_notes_table.php
database/migrations/2026_03_14_000600_create_customer_payments_table.php
database/migrations/2026_03_14_000700_create_payment_allocations_table.php
database/migrations/2026_03_15_000100_create_customer_refunds_table.php
database/migrations/2026_04_02_000800_create_payment_component_allocations_table.php
database/migrations/2026_04_02_000900_create_refund_component_allocations_table.php
database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php
database/migrations/2026_04_27_000100_add_due_date_to_notes_table.php
database/migrations/2026_04_27_000700_add_payment_method_and_cash_details_to_customer_payments.php

Initial proven notes facts:

notes has transaction_date, note_state, closed_at, closed_by_actor_id, reopened_at, reopened_by_actor_id, and total_rupiah.
notes has indexes on transaction_date, customer_name, note_state, and closed_at.
notes does not currently have created_at or updated_at in the base create migration.
DatabaseNoteWriterAdapter currently writes create, header update, total update, and operational state update without created_at or updated_at.
V2NoteOperationalStateMigrationTest inserts directly into notes without timestamp columns, so a NOT NULL timestamp patch without default or backfill can break existing tests and fixtures.
6. Dependency Order

DB hardening must run in this order:

Step 0 - Baseline Intake

Collect current proof before any file change:

branch and HEAD.
git status.
DB blueprint files.
DB ADR reference.
relevant migration list.
relevant writer adapters.
relevant reader, query, and report paths.
relevant tests.

Gate:

active scope is explicit.
files to inspect are listed.
no schema or source patch has started.

Stop condition:

branch or HEAD missing.
working tree dirty in unrelated files.
blueprint or ADR path missing.
migration list incomplete.
Step 1 - Build DB Audit Matrix

Create or update a DB audit matrix before schema patch.

Matrix columns:

migration file.
table name.
category.
source of truth status.
business or effective date columns.
occurred or action date columns.
system timestamp columns.
money columns.
status columns.
actor, reason, or audit link.
source id columns.
JSON usage.
FK and delete policy.
indexes.
known read path.
PostgreSQL risk.
CRUD performance risk.
recommendation.
patch allowed now: yes or no.
required proof before patch.

Gate:

each P0 table has a row.
each row separates FACT from GAP.
patch ordering is based on dependency, not convenience.

Stop condition:

any P0 table is missing from matrix.
recommendation is not tied to source proof.
patch allowed is yes without writer and read path proof.
Step 2 - Pick One Root Table Group

Start from the table group that reduces downstream rework.

Default first group:

notes

Reason:

notes is transaction header and root for customer transaction flow.
payments, refunds, allocations, revisions, projections, and reports depend on note semantics.
fixing child tables before root date semantics can force rework.

Gate:

owner has accepted the active table group.
source files and tests for that group are identified.
no unrelated table is patched.

Stop condition:

the chosen table depends on an unresolved upstream table.
patch would change report basis without explicit report proof.
Step 3 - Source Inspection For Active Group

For the active group, inspect:

create and alter migrations.
writer adapters.
reader adapters.
query, report, and projection paths.
direct DB test fixtures.
feature and unit tests that insert rows manually.
foreign key and delete policy migrations.
audit or event writer linkage.

For notes, inspect at minimum:

database/migrations/2026_03_14_000100_create_notes_table.php
database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php
database/migrations/2026_04_27_000100_add_due_date_to_notes_table.php
app/Adapters/Out/Note/DatabaseNoteWriterAdapter.php
app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php
app/Adapters/Out/Note/Mappers/NoteMapper.php
tests/Feature/Database/V2NoteOperationalStateMigrationTest.php
tests/Support/SeedsMinimalNotePaymentFixture.php

Gate:

current behavior is documented.
writer behavior is documented.
direct insert fixtures are identified.
test blast radius is identified.

Stop condition:

source and docs contradict and contradiction is unresolved.
insert or update path is not known.
backfill behavior is not defined.
Step 4 - Patch Blueprint For Active Group

Before source patch, write a narrow patch blueprint for the active table group.

Required fields:

table group.
exact problem.
current proven schema.
current proven writer behavior.
current proven tests and fixtures affected.
recommended schema change.
backfill policy.
domain impact.
report impact.
PostgreSQL readiness impact.
CRUD and read path impact.
files to touch.
files not to touch.
RED or characterization proof plan.
GREEN and focused proof plan.
rollback or defer criteria.

Gate:

patch blueprint is small enough for one implementation slice.
no domain term is renamed.
no report semantics are changed silently.

Stop condition:

patch blueprint requires broad rewrite.
backfill would invent historical facts.
report basis changes without report proof.
Step 5 - Characterization Test

Add or update the smallest test that proves the current gap.

Preferred proof types:

migration column existence test.
direct insert compatibility test.
writer timestamp behavior test.
query or read path test.
focused projection or report regression test when report behavior is touched.

RED proof is required before patch unless the source is already patched. If RED is impossible, record why and use post patch characterization with explicit GAP.

Gate:

failure proves the intended gap.
failure is not caused by unrelated setup.
test name states the behavior.

Stop condition:

test is broad and fails for unrelated reasons.
test encodes an assumption not backed by blueprint or ADR.
Step 6 - Minimal Schema And Application Patch

Patch only the active group.

Rules:

create new migration, do not edit old migration unless explicitly allowed.
preserve existing domain terms.
do not use MySQL enum for domain status.
do not use float or decimal for rupiah truth.
do not make JSON the only source for financial facts.
do not cascade delete financial history.
avoid PostgreSQL hostile assumptions.
avoid index changes unless tied to a known read path.
keep source patch inside correct adapter or application boundary.

For notes timestamp hardening, default candidate shape:

add created_at as row created system timestamp with safe default and backfill behavior.
add updated_at only if update writer behavior is patched and tested.
keep transaction_date as business and report date.
keep closed_at and reopened_at as lifecycle action timestamps.
do not expose new timestamps to the domain object unless a use case needs them.
do not use created_at as report period.

Gate:

patch matches patch blueprint.
direct insert compatibility is preserved or intentionally updated with proof.
writer behavior is deterministic enough for tests.
no unrelated files are touched.

Stop condition:

patch requires touching reports, UI, payment, refund, supplier, and inventory at once.
patch depends on unverifiable historical created time.
patch causes fixture churn without value.
Step 7 - Targeted And Focused Verification

Run proof in layers.

Minimum:

syntax for changed PHP files.
targeted migration or database test.
targeted writer or use case test if writer changed.
focused blast radius around the active table group.
git diff check.

For notes, likely focused candidates:

tests/Feature/Database/V2NoteOperationalStateMigrationTest.php
note create and update feature tests.
payment tests that seed notes directly.
refund tests that seed notes directly.
note revision tests that depend on note root.
report tests that read transaction_date.

Gate:

targeted tests pass.
focused blast radius passes.
failures are either fixed or explicitly deferred with owner acceptance.

Stop condition:

unrelated failures are not understood.
full suite failure is hidden.
docs claim verified beyond available proof.
Step 8 - Docs Alignment

Update docs only after proof.

Required docs update:

active DB audit matrix row.
active DB workflow note if workflow changed.
active blueprint if the rule changed.
ADR only if architecture decision changed.

Docs must record:

production files changed.
tests run.
exact pass or fail counts.
remaining gaps.
non goals.
next table group.

Gate:

docs do not overclaim.
docs distinguish fixed, verified, residual gap, and deferred.
local command output is cited or pasted in session or handoff.

Stop condition:

docs say fixed without proof.
docs erase gaps without test.
docs imply PostgreSQL migration has started.
Step 9 - Move To Next Table Group

Only move after active group reaches one of:

targeted verified.
focused verified.
deferred with owner acceptance.

Default next groups after notes:

customer_payments and customer_refunds.
payment_allocations, payment_component_allocations, and refund_component_allocations.
supplier_invoices, supplier_receipts, and supplier_payments.
inventory_movements.
P1 reversal and adjustment tables.
P2 master, access, and current state tables.
CRUD, index, and read path hardening.

Do not start CRUD or index hardening before temporal and source of truth ambiguity is mapped.

7. Patch Ordering Rules

Use these rules when deciding where to start:

Root transaction header before child financial rows.
Source of truth table before projection or read model.
Business, action, and system date semantics before index optimization.
Writer contract before report or dashboard contract.
Audit and reversal path before destructive update behavior.
Matrix before schema patch.
RED or characterization proof before GREEN patch.
Focused proof before docs closure.
8. Status Model

Use these statuses in the matrix:

Reported
Audited
Patch Blueprinted
Characterized RED
Patched Unverified
Targeted Verified
Focused Verified
Docs Aligned
Deferred with owner acceptance

Do not use Fixed unless source patch, tests, and docs alignment proof exist for the scope being claimed.

9. First Recommended Active Slice

Recommended first active slice:

Table group: notes
Problem: root transaction header has business and action timestamps but lacks system row timestamps.
Reason: downstream payment, refund, allocation, revision, and report behavior depends on notes semantics.
First deliverable: DB audit matrix row and patch blueprint for notes.
First patch: not approved until writer, fixtures, migration behavior, and backfill policy are verified.

Initial recommended notes decision:

transaction_date remains business and report date.
closed_at remains lifecycle close action time.
reopened_at remains lifecycle reopen action time.
created_at should represent system row creation time if added.
updated_at should represent system row mutation time if added.
unknown historical created time must not be invented from transaction_date unless a separate ADR or owner decision accepts that approximation.
10. Completion Criteria For This Workflow

DB hardening workflow is complete only when:

all P0 rows have matrix entries.
active P0 table groups are either verified or explicitly deferred.
temporal audit gaps are closed or documented.
PostgreSQL readiness risks are closed or documented.
CRUD and index risks are tied to real read paths.
docs and source do not contradict.
no fix is claimed without command proof.
11. Next Safe Step

Create the DB audit matrix for P0 tables.

Do not patch notes yet.

Start with the notes matrix row because it is the root transaction header and reduces downstream rework risk.
