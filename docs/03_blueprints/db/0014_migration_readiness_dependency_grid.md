# Migration Readiness Dependency Grid

## Status

Execution blueprint.

This document defines cross-area execution order for migration readiness work.

This document is not implementation proof.

This document does not change runtime behavior.

This document does not authorize broad rewrite.

## Purpose

The purpose of this document is to prevent circular work between:

- transaction create/edit maturity;
- customer refund maturity;
- audit grid and audit outbox expansion;
- seeder hardening;
- full verification closure.

The current risk is doing these areas in the wrong order.

If audit is expanded before transaction contracts are stable, audit payloads may be rewritten later.

If seeder hardening becomes the main driver before domain contracts are clear, test data may be cleaned for behavior that still changes.

If refund is patched before create/edit contracts are mapped, refund correctness may be built on unstable transaction facts.

## Source Of Truth

Source priority follows `docs/04_lifecycle/handoff/README.md`.

Relevant anchors:

- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `docs/03_blueprints/db/0013_go_postgres_migration_readiness_stage_0.md`
- `docs/03_blueprints/db/go_postgres_migration_readiness/findings/04_transaction_idempotency_audit.md`
- `docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md`
- `docs/03_blueprints/audit/0002_audit_write_path_matrix.md`
- `docs/03_blueprints/seeder/0001_legacy_to_clean.md`
- `docs/03_blueprints/seeder/0002_legacy_to_clean_manifest.md`
- `app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php`
- `app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php`
- `app/Application/Payment/Services/RecordCustomerRefundTransaction.php`
- `app/Application/Payment/Services/RecordCustomerRefundOperation.php`
- `app/Application/Payment/Services/AllocateRefundAcrossComponents.php`
- `app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php`
- `app/Application/Note/Services/AutoRefundNoteWhenFullyRefunded.php`

## Current Proven Facts

- Audit outbox runtime is proven only for the selected expense category regression scope.
- Expense category update/activate/deactivate can stage audit to `audit_outbox`.
- `audit:outbox:process` can materialize selected canonical audit events and snapshots.
- Global `AuditEventWriterPort` outbox binding selected regression passed after old-expectation tests were patched.
- Transaction-heavy audit expansion is not proven.
- Seeder cleanup is not proven complete.
- Full `make verify` proof is not available in this document.

## Current Open Problems

### 1. Create/Edit Transaction Maturity

Create transaction workspace and update transaction workspace are not isolated CRUD flows.

They involve:

- note creation or update;
- work item persistence;
- total recalculation;
- inline payment recording;
- legacy audit logging;
- note history projection sync;
- transaction manager boundary.

These flows are migration drivers because future API ownership depends on stable application use case contracts.

### 2. Refund Maturity

Customer refund is not a single-row mutation.

It involves:

- payment lookup;
- note lock;
- allocated/refunded amount checks;
- component refund allocation;
- refund row creation;
- refund allocation row creation;
- inventory reversal when store stock lines are fully refunded;
- note lifecycle transition when fully refunded;
- timeline write;
- legacy audit logging;
- projection sync.

Refund cannot be treated as audit-only work.

### 3. Audit Grid Maturity

Audit is a migration readiness grid, not only a logging mechanism.

For transaction-heavy flows, audit must follow stable mutation contracts.

Audit expansion must define:

- canonical versus legacy audit decision;
- required audit facts;
- aggregate id;
- source id;
- actor id;
- actor role;
- reason;
- occurred_at;
- before snapshot;
- after snapshot;
- outbox staging behavior;
- canonical materialization behavior.

### 4. Seeder Maturity

Seeder hardening is a verification foundation.

Seeder work should reduce proof noise and make regression data deterministic.

Seeder work must not become the main driver for transaction semantics.

Seeder hardening may be inserted earlier only when it blocks proof commands or focused regression setup.

## Dependency Rule

### Rule 1 - Domain Contract Before Audit Expansion

Do not expand audit outbox to transaction-heavy flows before the target mutation has a documented and tested contract.

Required before audit expansion:

- mutation boundary;
- transaction boundary;
- lock target;
- idempotency decision;
- rollback behavior;
- projection/report effect;
- current audit behavior;
- expected canonical audit facts.

### Rule 2 - Create/Edit Before Refund

Create/edit transaction maturity must come before refund maturity.

Reason:

- refund depends on payment allocation and note/work item facts created or changed by create/edit;
- refund allocation and inventory reversal depend on stable component references;
- refund lifecycle depends on note state and totals;
- audit facts for refund depend on stable source ids and mutation semantics.

### Rule 3 - Refund Before Refund Audit Migration

Refund audit migration is blocked until refund mutation contract is stable.

Do not migrate refund audit from legacy audit or expand outbox coverage for refund until refund behavior is proven.

### Rule 4 - Seeder As Proof Foundation

Seeder hardening is important, but it is not the source of domain truth.

Seeder may be handled before domain work only if it blocks:

- focused test setup;
- PHPStan or static verification;
- `make verify` closure;
- deterministic fixture creation for transaction/refund regression.

### Rule 5 - Full Verify After Focused Proof

Do not require full verification before every blueprint step.

Do require focused proof before claiming each slice complete.

Full verification belongs near closure or when the active step changes shared foundation.

## Execution Order

### Phase 0 - Dependency Grid

Status: active blueprint step.

Goal:

- define execution order;
- prevent circular work;
- define gates between seeder, domain, audit, and verification.

Exit proof:

- this document exists;
- this document passes text verification;
- no runtime file is changed.

### Phase 1 - Create/Edit Transaction Contract Matrix

Status: next domain step.

Scope:

- `CreateTransactionWorkspaceHandler`
- `UpdateTransactionWorkspaceHandler`
- related item persistence;
- inline payment recording;
- note total recalculation;
- note projection sync;
- legacy audit behavior.

Required output:

- create/edit mutation matrix;
- table write map;
- transaction boundary map;
- rollback behavior map;
- idempotency decision;
- audit fact gap;
- focused test list.

Exit proof:

- source inspection citations or local grep output;
- matrix document created or updated;
- no implementation claim without test proof.

### Phase 2 - Create/Edit Hardening

Status: blocked until Phase 1.

Scope:

- one smallest create/edit correctness gap at a time.

Required proof:

- focused tests for the patched behavior;
- no broad refund/audit/seeder patch in the same step.

### Phase 3 - Refund Contract Matrix

Status: blocked until create/edit contract is mapped.

Scope:

- customer refund transaction;
- refund operation;
- refund allocation;
- selected row refund;
- inventory reversal;
- note fully refunded lifecycle;
- timeline/projection/audit effects.

Required output:

- refund mutation matrix;
- allocation invariant map;
- stock reversal invariant map;
- note lifecycle invariant map;
- audit fact gap;
- focused test list.

### Phase 4 - Refund Hardening

Status: blocked until Phase 3.

Scope:

- one smallest refund correctness gap at a time.

Required proof:

- focused refund regression;
- selected row behavior proof when touched;
- inventory reversal proof when touched;
- note lifecycle proof when touched.

### Phase 5 - Audit Grid Expansion

Status: blocked until target mutation contract is stable.

Scope:

- canonical-vs-legacy audit classification;
- audit facts per mutation;
- outbox eligibility;
- processor materialization proof.

Allowed first targets:

- create/edit transaction audit only after Phase 1/2 proof;
- refund audit only after Phase 3/4 proof.

Not allowed:

- payment/refund/stock audit migration without mutation matrix;
- broad all-flow audit migration;
- event payload guesswork.

### Phase 6 - Seeder Hardening

Status: verification foundation.

Scope:

- product seeder idempotency gap;
- skipped seeder tests;
- PHPStan/make verify seeder noise;
- deterministic fixtures needed by create/edit/refund tests.

Seeder may move earlier only if it blocks focused proof.

Required proof:

- unskipped or replaced focused seeder tests;
- deterministic rerun proof;
- PHP syntax/static proof where relevant;
- no finance semantics changed only through seed data.

### Phase 7 - Full Verification And Closure

Status: final closure phase.

Scope:

- selected regression groups;
- `make verify` or agreed equivalent;
- blueprint/handoff update.

Exit proof:

- command output;
- known unrelated failures explicitly separated;
- no completion claim without proof.

## Work Gates

| Gate | Opens | Blocks |
|---|---|---|
| Phase 0 dependency grid exists | ordered execution | ad hoc cross-area work |
| Create/edit matrix exists | create/edit hardening | refund-first patching |
| Create/edit hardening proven | refund matrix | refund assumptions over unstable create/edit |
| Refund matrix exists | refund hardening | refund audit migration |
| Refund hardening proven | refund audit expansion | audit payload churn |
| Audit grid expansion proven | wider audit rollout | broad legacy/canonical mismatch |
| Seeder hardening proven | cleaner full verify | verification noise |
| Full verify proof exists | closure | false completion |

## Current Decision

The starting order is domain-first:

1. create/edit transaction maturity;
2. refund maturity;
3. audit grid expansion;
4. seeder hardening as verification foundation;
5. full verification and closure.

Seeder can be pulled earlier only when it blocks focused proof or static verification.

## Explicit Scope-Out

This document does not authorize:

- PostgreSQL implementation;
- Go API implementation;
- broad transaction rewrite;
- broad audit rewrite;
- payment/refund/stock audit migration without a mutation matrix;
- seeder rewrite across all domains;
- remote write by assistant.

## Next Active Step

Create the create/edit transaction contract matrix.

Recommended path:

- `docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md`

The next step must inspect source and tests before proposing implementation.
