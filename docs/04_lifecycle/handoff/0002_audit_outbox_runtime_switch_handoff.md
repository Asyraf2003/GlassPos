# Audit Outbox Runtime Switch Handoff

## Metadata
- Date: 2026-05-23
- Slice / topic: Audit outbox runtime switch
- Workflow step: handoff after global binding switch proof found test expectation gaps
- Status: continue in next session
- Progress: audit outbox foundation, processor, pilot binding, and global binding attempt completed; test expectation fix pending

## Target Work Page

Continue audit runtime work under:

- `docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md`
- `docs/03_blueprints/audit/0002_audit_write_path_matrix.md`

Current next target:

- fix tests after global `AuditEventWriterPort` binding switch to `DatabaseAuditOutboxWriterAdapter`

## References Used

- Standards:
  - `docs/01_standards/0001_index.md`
  - `docs/01_standards/0005_handoff_template.md`
  - `docs/01_standards/core/0011_blueprint_first.md`
  - `docs/01_standards/core/0013_proof_and_progress.md`
  - `docs/01_standards/workflow/0021_active_step_policy.md`
- Handoff README:
  - `docs/04_lifecycle/handoff/README.md`
- Active audit blueprints:
  - `docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md`
  - `docs/03_blueprints/audit/0002_audit_write_path_matrix.md`
- Latest operator output:
  - global binding switch selected regression produced 17 passed, 4 failed, 105 assertions
  - failure cause: tests still expected immediate `audit_events` after global outbox binding

## Locked Facts

- `UpdateExpenseCategoryHandler` uses canonical `AuditEventWriterPort`.
- `ActivateExpenseCategoryHandler` uses canonical `AuditEventWriterPort`.
- `DeactivateExpenseCategoryHandler` uses canonical `AuditEventWriterPort`.
- `audit_outbox` migration exists and passed syntax proof.
- `DatabaseAuditOutboxWriterAdapter` exists and passed syntax/test proof.
- `audit:outbox:process` command exists and passed syntax/test proof.
- Processor materialization, duplicate-run, and failure handling tests passed.
- Test-only expense category outbox binding pilot passed.
- Broader test-only expense category outbox binding regression passed.
- Global binding switch was attempted in `app/Providers/InfrastructureServiceProvider.php`.
- After global binding switch, actual `AuditEventWriterPort` resolves to `DatabaseAuditOutboxWriterAdapter`.
- After global binding switch, selected regression produced 4 failing old-expectation tests.

## Scope Used

### SCOPE-IN
- Audit outbox migration.
- Outbox writer adapter.
- Audit outbox processor command.
- Test-only outbox binding pilot.
- Global binding switch attempt.
- Focused audit/expense regression.

### SCOPE-OUT
- Payment/refund/allocation/stock/transaction-heavy flows.
- Monitoring UI.
- Scheduler/queue production operation.
- Payment/procurement/employee finance audit migration.
- `make verify` legacy seeder PHPStan issue.
- Remote write by assistant.

## GAP

- `DatabaseAuditEventWriterAdapterTest` still resolves `AuditEventWriterPort`; after global binding switch this no longer tests direct materializer.
- `UpdateExpenseCategoryFeatureTest` still expects immediate canonical `audit_events`.
- `ActivateExpenseCategoryFeatureTest` still expects immediate canonical `audit_events`.
- `DeactivateExpenseCategoryFeatureTest` still expects immediate canonical `audit_events`.
- Expense feature tests must be updated to assert outbox staging then processor materialization.
- Blueprint/matrix progress has not yet been updated after the global binding attempt.
- Minimal pending/failed monitoring is not implemented.
- `make verify` still has unrelated PHPStan seeder class errors from product seeder tests.

## Locked Decisions

- Do not rollback global binding unless new proof shows the outbox design is invalid.
- Do not switch to payment/refund/stock/transaction-heavy flows yet.
- Do not add monitoring before fixing current failing test expectations.
- Direct materializer tests should instantiate or resolve `DatabaseAuditEventWriterAdapter` concrete directly.
- Runtime feature tests should reflect outbox semantics after global binding:
  - audit is staged in `audit_outbox`;
  - `audit_events` remains empty before processor;
  - `audit:outbox:process` materializes canonical audit;
  - outbox row becomes `processed`.

## Files Created / Changed

### New files
- `database/migrations/2026_05_23_010000_create_audit_outbox_table.php`
- `app/Adapters/Out/Audit/DatabaseAuditOutboxWriterAdapter.php`
- `app/Application/Audit/Support/AuditOutboxStatus.php`
- `app/Application/Audit/Services/AuditOutboxEventHydrator.php`
- `app/Application/Audit/Services/AuditOutboxFailureRecorder.php`
- `app/Application/Audit/UseCases/ProcessAuditOutboxHandler.php`
- `routes/console_audit_outbox.php`
- `tests/Feature/AuditLog/Support/AuditOutboxTestEventFactory.php`
- `tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php`
- `tests/Feature/AuditLog/ProcessAuditOutboxMaterializationCommandTest.php`
- `tests/Feature/AuditLog/ProcessAuditOutboxDuplicateRunCommandTest.php`
- `tests/Feature/AuditLog/ProcessAuditOutboxFailureCommandTest.php`
- `tests/Feature/AuditLog/AuditOutboxExpenseCategoryPilotTest.php`
- `tests/Feature/AuditLog/AuditOutboxExpenseCategoryRuntimeRegressionTest.php`
- `tests/Feature/AuditLog/AuditOutboxRuntimeBindingTest.php`
- `docs/04_lifecycle/handoff/0002_audit_outbox_runtime_switch_handoff.md`

### Changed files
- `app/Providers/InfrastructureServiceProvider.php`
- `routes/console.php`
- `docs/04_lifecycle/handoff/README.md`
- `docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md`

## Verification Proof

- command:
  - `php -l database/migrations/2026_05_23_010000_create_audit_outbox_table.php`
  - result: no syntax errors detected
  - meaning: audit outbox migration syntax is valid

- command:
  - `php artisan test tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php tests/Feature/AuditLog/DatabaseAuditEventWriterAdapterTest.php`
  - result: 6 passed, 31 assertions
  - meaning: outbox writer stages events and direct writer regression still passed before global binding switch

- command:
  - `php artisan test tests/Feature/AuditLog/ProcessAuditOutboxMaterializationCommandTest.php tests/Feature/AuditLog/ProcessAuditOutboxDuplicateRunCommandTest.php tests/Feature/AuditLog/ProcessAuditOutboxFailureCommandTest.php tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php tests/Feature/AuditLog/DatabaseAuditEventWriterAdapterTest.php`
  - result: 9 passed, 46 assertions
  - meaning: processor materialization, duplicate-run, failure handling, outbox writer, and direct writer proof passed after file-size refactor

- command:
  - selected outbox pilot regression with `AuditOutboxExpenseCategoryPilotTest`
  - result: 12 passed, 79 assertions
  - meaning: update expense category could stage audit in outbox and materialize through processor in test-only binding

- command:
  - selected broader outbox regression with `AuditOutboxExpenseCategoryRuntimeRegressionTest`
  - result: 15 passed, 112 assertions
  - meaning: update/activate/deactivate expense category flows could stage and materialize through outbox binding in test-only scope

- command:
  - selected regression after global binding switch
  - result: 17 passed, 4 failed, 105 assertions
  - meaning: global binding switch worked for new outbox tests, but old tests still expected direct canonical audit writes

## Risks / Follow-up Notes

- Failing tests are expected after binding switch because their expectations still target immediate `audit_events`.
- Updating tests is safer than rolling back binding because the new runtime contract is staged audit plus processor materialization.
- `DatabaseAuditEventWriterAdapterTest` should not use `AuditEventWriterPort` after global binding switch.
- Expense category feature tests should prove outbox runtime behavior, not direct writer behavior.
- `make verify` failure from product seeder PHPStan is unrelated to audit outbox runtime and should be handled in a separate step.
- Keep all new/changed code and tests at or below 100 lines per file.

## Next Step

Patch only the failing tests after global outbox binding:

- `tests/Feature/AuditLog/DatabaseAuditEventWriterAdapterTest.php`
- `tests/Feature/Expense/UpdateExpenseCategoryFeatureTest.php`
- `tests/Feature/Expense/ActivateExpenseCategoryFeatureTest.php`
- `tests/Feature/Expense/DeactivateExpenseCategoryFeatureTest.php`

Expected behavior after patch:

- direct materializer test uses concrete `DatabaseAuditEventWriterAdapter`;
- expense feature tests assert `audit_outbox` pending before processor;
- expense feature tests run `audit:outbox:process`;
- expense feature tests assert canonical `audit_events` and snapshots after processor;
- selected audit/expense regression passes.
