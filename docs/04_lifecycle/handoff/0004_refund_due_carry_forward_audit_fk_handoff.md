# Refund Due Carry-Forward and Audit FK Handoff

## Metadata

- Date: 2026-05-24
- Repo: Asyraf2003/hyperpos
- Slice / topic: note revision refund_due carry-forward, audit FK/outbox mismatch, create/edit/refund maturity
- Workflow step: continuation after Phase 1D refund_due characterization and narrow implementation
- Status: continue in next session
- Current active blocker: audit FK mismatch in refund_due/refund_paid HTTP and handler flows

## Target Work Page

Target domain:

- create transaction maturity
- edit transaction / note revision maturity
- ordinary refund
- surplus refund_due
- surplus refund_paid
- revision settlement carry-forward
- audit records for every important action
- audit runtime that feels watched but remains fast through outbox/runtime separation
- seeders that are mature enough to support realistic edit/refund/report proofs

Core product goal:

Make create transaction mature enough that edit and refund workflows are safe, auditable, fast, and reportable.

Core audit UX goal:

Every meaningful action should feel monitored and traceable, but user-facing actions should remain instant and should not feel like heavy telemetry blocks the request.

Core data goal:

Seeder and fixture support must be mature enough to prove realistic create/edit/refund/report flows without guessing schema columns.

## Rules / Guardrails

- Read repo rules first before continuing:
  - docs/04_lifecycle/handoff/README.md
  - docs/01_standards/0005_handoff_template.md
  - docs/01_standards/core/0010_scope_and_facts.md
  - docs/01_standards/core/0011_blueprint_first.md
  - docs/01_standards/core/0012_step_by_step_execution.md
  - docs/01_standards/core/0013_proof_and_progress.md
  - docs/01_standards/workflow/0020_response_structure.md
  - docs/01_standards/workflow/0021_active_step_policy.md
  - docs/01_standards/output/0033_terminal_command_delivery.md
- Do not mix assumption with fact.
- Separate FACT, GAP, ASSUMPTION, DECISION, PROOF, NEXT.
- Do not patch UI first.
- Do not patch reports to hide domain mismatch.
- Do not patch UpdateTransactionWorkspaceHandler unless route binding proof or dead-path decision exists.
- Do not merge revision submit and payment submit.
- Do not guess columns. Source-map migrations/tests first.
- Git operations are operator-owned.

## Source Anchors

Relevant docs:

- docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md
- docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md
- docs/03_blueprints/db/0017_edit_refund_characterization_plan.md
- docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md
- docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md

Relevant implementation:

- app/Application/Note/Services/BuildCreateNoteRevisionSettlement.php
- app/Ports/Out/Note/NoteRevisionSurplusDispositionReaderPort.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapter.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php
- app/Providers/InfrastructureServiceProvider.php
- app/Adapters/Out/Audit/DatabaseAuditOutboxWriterAdapter.php
- app/Adapters/Out/Audit/DatabaseAuditEventWriterAdapter.php

Relevant tests:

- tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php
- tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php
- tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php
- tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueRaceInvariantTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php

## FACT

Phase 1D-1 settlement carry-forward was implemented and verified locally.

Targeted test added:

- tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php

It covers:

- revision after partial payment carries paid amount into underpaid settlement
- revision after ordinary refund counts refund once in settlement

Operator proof:

- targeted test passed with 2 tests and 4 assertions
- adjacency proof passed with 9 tests and 40 assertions

Next characterization test added:

- tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php

It proved RED first:

- expected carry_forward_refunded_rupiah = 122000
- actual carry_forward_refunded_rupiah = 0
- actual settlement incorrectly became overpaid_pending

Narrow production patch was applied:

- extended NoteRevisionSurplusDispositionReaderPort with sumActiveRefundDueAmountByNoteRootId
- implemented sumActiveRefundDueAmountByNoteRootId in DatabaseNoteRevisionSurplusDispositionAdapter
- injected NoteRevisionSurplusDispositionReaderPort into BuildCreateNoteRevisionSettlement
- settlement builder now treats refund_due/refund_paid as unavailable surplus obligation effect
- unit builder tests were expanded

Current formula intent:

- ordinary refunded reduces carry-forward net exactly once
- surplus refund_paid is cash-out and must not be reclaimed
- refund_due is liability and must not be silently consumed
- surplus obligation effect uses max(active refund_due, active surplus refund_paid) to avoid double count when both records exist for the same surplus obligation

Operator proof after patch:

- php artisan test tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php
  - PASS
  - 1 passed
  - 3 assertions
- php artisan test tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php
  - PASS
  - 6 passed
  - 34 assertions
- settlement blast-radius suite:
  - PASS
  - 12 passed
  - 55 assertions

PHPStan blocker fixed/identified:

- make verify first failed because LockAwarePendingRefundDueReaderFake did not implement new sumActiveRefundDueAmountByNoteRootId method.
- This is a test fake contract drift from extending NoteRevisionSurplusDispositionReaderPort.

Remaining full verify blocker from operator log:

- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php returns HTTP 500.
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest also has QueryException.
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest has HTTP 500.
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest has QueryException.
- Final make verify summary from operator log:
  - 10 failed
  - 2 skipped
  - 1070 passed
  - 5781 assertions

Common failure shape:

- SQLSTATE[23000]
- FK violation
- note_revision_surplus_dispositions.audit_event_id references audit_events.id
- note_revision_surplus_refund_payments.audit_event_id references audit_events.id
- inserted domain row refers to audit-event-refund-due-http-001 or generated refund-paid audit event id
- corresponding audit_events row does not exist at insert time

Current audit binding fact:

- InfrastructureServiceProvider binds AuditEventWriterPort to DatabaseAuditOutboxWriterAdapter.
- DatabaseAuditOutboxWriterAdapter writes audit_outbox only.
- DatabaseAuditEventWriterAdapter writes audit_events and audit_event_snapshots.
- Audit outbox blueprint says outbox runtime is intended for fast user-facing action plus later canonical audit materialization.
- However, refund_due/refund_paid domain tables still enforce hard FK to audit_events.

## GAP

- Full make verify is not GREEN.
- Current outbox audit runtime is incompatible with domain tables that require immediate audit_events FK.
- No source-map has yet proven the correct fix strategy for refund_due/refund_paid:
  - direct canonical audit writer for these FK-bound flows,
  - hybrid writer that inserts canonical event and outbox,
  - schema change to reference outbox or nullable audit_event_id,
  - or processor-before-domain-write pattern.
- No decision yet whether FK-bound transaction/refund flows are excluded from global outbox binding.
- Seeder maturity for realistic create/edit/refund/report flow is still incomplete.
- Report/export proof after edit/refund/refund_due/refund_paid is not complete.
- Audit monitoring UI/status for pending/failed outbox is not complete.
- Full audit action coverage matrix is not complete.
- Full transaction create -> edit -> refund -> report end-to-end proof is not complete.

## ASSUMPTION

Do not assume audit_outbox rows satisfy existing audit_events FK.

Do not assume the audit processor runs inside the same HTTP request.

Do not assume refund_due/refund_paid can keep hard audit_events FK while using outbox-only AuditEventWriterPort unless source proves canonical audit row is materialized before domain insert.

Do not assume this failure was introduced only by refund_due carry-forward. The carry-forward work surfaced and crossed paths with existing audit runtime migration.

## DECISION

Current state must be blocked from release/full-verify success until audit FK mismatch is fixed or explicitly scoped out.

Do not ignore the 10 failing tests.

Do not hide this in report patches.

Do not weaken audit FK without ADR/blueprint decision.

Next active step should be source-map and narrow patch for audit FK/outbox mismatch in refund_due/refund_paid flows.

Preferred next slice name:

Phase 1E-1 - Audit FK compatibility for refund_due/refund_paid outbox runtime.

## PROOF

Targeted settlement proof:

- NoteRevisionRefundDueCarryForwardFeatureTest: PASS, 1 passed, 3 assertions.
- BuildCreateNoteRevisionSettlementTest: PASS, 6 passed, 34 assertions.
- Settlement blast-radius suite: PASS, 12 passed, 55 assertions.

Full verify blocker proof from operator log:

- make verify failed after tests.
- 10 failed, 2 skipped, 1070 passed, 5781 assertions.
- Common failure: SQLSTATE[23000] FK violation from audit_event_id referencing missing audit_events row.

## NEXT

One active step only:

Source-map audit writer and FK-bound refund_due/refund_paid flows before patching.

Recommended commands:

grep -R "AuditEventWriterPort::class" -n app tests
grep -R "DatabaseAuditOutboxWriterAdapter" -n app tests docs
grep -R "DatabaseAuditEventWriterAdapter" -n app tests docs
grep -R "audit_event_id" -n database/migrations app/Application app/Adapters tests/Feature/Note
grep -R "note_revision_surplus_dispositions" -n database/migrations app tests/Feature/Note
grep -R "note_revision_surplus_refund_payments" -n database/migrations app tests/Feature/Note

Targeted failing proof commands:

php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php
php artisan test tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php
php artisan test tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php

Decision checkpoint after source-map:

Option A:
Use direct canonical DatabaseAuditEventWriterAdapter for FK-bound refund_due/refund_paid flows until outbox schema is made FK-compatible.

Option B:
Introduce composite/hybrid audit writer for FK-bound flows that writes canonical audit_events synchronously and optionally outbox for async processing.

Option C:
Change domain schema away from audit_events FK, but only with ADR and migration plan.

Do not choose before source-map proof.


## Phase Map / System Maturity Direction

### Goal inti produk

Sistem diarahkan agar create transaction menjadi fondasi matang untuk:

- edit / note revision
- payment settlement
- ordinary refund
- surplus refund_due
- surplus refund_paid
- report/export
- audit timeline
- future API
- future PostgreSQL
- future Go extraction

Prinsip utama:

- create transaction harus menghasilkan financial truth yang stabil
- edit/revision tidak boleh menghancurkan paid/refunded/surplus truth
- refund harus memakai current financial state, bukan stale row
- report harus membaca official records, bukan menambal mismatch
- audit harus terasa semua aksi diawasi, tetapi user action tetap instan
- seeder harus realistis agar lifecycle proof tidak menebak schema

### Current maturity rough map

Domain settlement/edit/refund core:

- Status: partially mature
- Progress: carry-forward paid, ordinary refund, refund_due, refund_paid behavior sudah mulai dikunci dengan characterization and unit tests
- Blocker: full lifecycle proof belum lengkap

Audit runtime:

- Status: transitional / blocked
- Progress: audit outbox runtime exists and is globally bound
- Blocker: FK-bound refund_due/refund_paid tables still require audit_events row synchronously
- Current issue: audit intent goes to audit_outbox, but domain rows reference audit_events.id

Seeder / fixture maturity:

- Status: partially mature
- Progress: minimal note/payment fixture works for current characterization
- Gap: realistic create-edit-refund-report-audit lifecycle seeder belum lengkap

Report/export maturity:

- Status: not complete
- Gap: report/export after edit/refund/refund_due/refund_paid still needs proof
- Rule: do not patch report to hide settlement/audit mismatch

PostgreSQL readiness:

- Status: not ready for claim
- Required before claim:
  - migration compatibility proof
  - lockForUpdate proof
  - JSON/date behavior proof
  - FK/restrict behavior proof
  - core lifecycle tests on PostgreSQL

Go API readiness:

- Status: contract preparation phase
- Required before Go:
  - stable command/query contracts
  - OpenAPI or equivalent DTO contract
  - idempotency model
  - actor/auth model
  - audit correlation model
  - settlement preview contract
  - lifecycle proof in Laravel first

Performance 1 second target:

- Status: GAP until measured
- Required proof:
  - p95 response/page load metric
  - query count
  - slow query evidence
  - realistic seeded dataset
  - no N+1 on target pages
  - indexed report queries

### Do not jump phases

Do not jump to Go API before:

1. make verify is GREEN
2. audit FK/outbox mismatch is resolved
3. create-edit-refund-report-audit lifecycle proof exists
4. realistic seeder exists
5. baseline performance measurement exists

Do not jump to PostgreSQL before:

1. lifecycle tests are stable on MySQL
2. migration assumptions are source-mapped
3. PostgreSQL compatibility test subset exists

Do not optimize for 1 second by rewriting to Go first.

First optimize by:

- fixing source-of-truth correctness
- reducing synchronous audit weight through valid outbox design
- eliminating N+1
- adding indexes
- using projection/read models
- measuring realistic dataset performance

### Execution Protocol For Next Session

The next session must work one active blocker at a time.

Required loop:

1. Read rules.
2. Read this handoff.
3. Identify current failing command.
4. Source-map the failure.
5. State FACT/GAP/ASSUMPTION/DECISION before patch.
6. Patch only the narrowest files needed.
7. Run targeted proof.
8. Run adjacent proof.
9. Run make verify.
10. If another failure appears, treat it as next active blocker.
11. Do not start a new feature while current blocker is RED.

Current active blocker order:

1. Fix PHPStan contract drift if not already fixed:
   - tests/Feature/Note/CreateNoteRevisionSurplusRefundDueRaceInvariantTest.php
   - LockAwarePendingRefundDueReaderFake must implement sumActiveRefundDueAmountByNoteRootId

2. Fix or explicitly block audit FK/outbox mismatch:
   - CreateNoteRevisionSurplusRefundDueControllerFeatureTest
   - CreateNoteRevisionSurplusRefundDueHandlerTest
   - RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest
   - RecordNoteRevisionSurplusRefundPaymentHandlerTest

3. Re-run focused failing tests.

4. Re-run settlement blast-radius tests.

5. Re-run make verify.

Decision checkpoint for audit FK/outbox mismatch:

- Option A: direct canonical writer for FK-bound refund_due/refund_paid flows
- Option B: hybrid writer for FK-bound flows
- Option C: schema redesign away from audit_events FK, only with ADR/migration plan

Do not choose without source-map proof.

## AI Session Control Addendum

### Operator-Owned Git Boundary

The assistant must not manage git.

Do not ask for or suggest:

- git status
- git diff
- git add
- git commit
- git push
- branch creation
- pull request creation
- staging workflow
- merge workflow

Git, commit, branch, push, and PR are operator-owned.

The assistant may mention changed file paths only when needed for implementation clarity.

### Existing Proof Boundary

Do not ask the operator to rerun make verify as the first step.

The latest make verify failure is already known and must be treated as FACT for the next session.

Known latest failure class:

- audit FK and outbox mismatch
- refund_due/refund_paid domain rows reference audit_events.id
- runtime AuditEventWriterPort writes audit_outbox through DatabaseAuditOutboxWriterAdapter
- audit_events row is not present at domain insert time
- result is SQLSTATE 23000 FK violation

The next session may request make verify only after a targeted patch and focused proofs are GREEN.

### Source-Map Boundary

Do not redo broad source-map work that is already documented in this handoff.

Allowed source-map work:

- inspect only files needed for the active blocker
- inspect failing tests
- inspect exact writer binding
- inspect exact audit writer implementation
- inspect exact FK migration or table write path

Do not source-map UI, reports, API, PostgreSQL, Go, or seeders unless the active blocker requires it.

### Implementation Delivery Format

All implementation must be delivered as copy-paste CLI commands.

Preferred patch format:

- python3 inline patch when modifying existing files
- cat here-doc only when creating a new file
- no nested triple backtick fences inside generated handoff or prompt text
- avoid decorative ASCII boxes
- avoid markdown table if it makes copy-paste fragile
- keep command output expectations plain text

Do not produce a patch that requires the operator to manually edit multiple snippets.

### Response Structure Required For Next Session

Every technical response must separate:

- FACT
- GAP
- ASSUMPTION
- DECISION
- PROOF
- NEXT

For simple confirmation, a short response is allowed.

For implementation work, the assistant must start with a blueprint before patch.

### Active Blocker Handling Rule

The next session must process exactly one active blocker at a time.

Loop:

1. State current active blocker from existing proof.
2. Source-map only the files needed for that blocker.
3. Decide narrow patch.
4. Provide CLI patch.
5. Provide targeted proof command.
6. Provide adjacent proof command.
7. Only then request make verify.

Do not start new features while current blocker remains RED.

### Current Active Blocker

Current active blocker:

audit FK/outbox mismatch for refund_due/refund_paid flows.

Known affected tests from operator log:

- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php

Known failure:

- HTTP 500 or QueryException
- SQLSTATE 23000
- audit_event_id foreign key fails
- missing audit_events row

Do not treat this as random test noise.

Do not patch report or UI for this.

Do not weaken audit integrity without decision.

### Decision Options For Active Blocker

The next assistant must choose only after source-map proof.

Possible options:

Option A:
Use direct canonical audit writer for FK-bound refund_due/refund_paid flows.

Option B:
Use hybrid writer for FK-bound flows that writes canonical audit_events synchronously and keeps outbox compatibility if needed.

Option C:
Block and require ADR/schema migration because current outbox-only writer is incompatible with FK-bound domain tables.

Do not pick option C unless the source-map proves a small safe compatibility patch is not enough.

### Long-Term Direction Reminder

The product direction remains:

- mature create transaction first
- make edit and refund safe on top of create transaction truth
- keep settlement as backend truth
- keep report based on official records
- make every important action auditable
- keep user-facing action fast
- mature seeders for realistic lifecycle proof
- only then prepare PostgreSQL and Go API transition

Do not jump to PostgreSQL or Go while make verify is RED.

Do not claim 1 second load until measured with realistic seed data.

