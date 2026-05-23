# Create/Edit Transaction Contract Matrix

## Status

Execution blueprint.

This document maps create/edit transaction workspace behavior before implementation hardening.

This document is not implementation proof.

This document does not change runtime behavior.

This document does not authorize refund, audit outbox expansion, seeder rewrite, or broad transaction rewrite.

## Purpose

Create/edit transaction workspace is the domain foundation for later refund maturity and audit migration.

Refund depends on stable note, work item, payment allocation, stock, and projection facts.

Audit grid expansion depends on stable mutation contracts.

This document defines the current create/edit mutation contract, known gaps, and required proof before code changes.

## Source Of Truth

Source priority follows `docs/04_lifecycle/handoff/README.md`.

Primary inspected source anchors:

- `docs/03_blueprints/db/0014_migration_readiness_dependency_grid.md`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `docs/03_blueprints/db/go_postgres_migration_readiness/findings/04_transaction_idempotency_audit.md`
- `app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php`
- `app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`
- `app/Application/Note/Services/ReverseIssuedInventoryByNoteService.php`
- `app/Application/Note/Services/AutoCloseNoteWhenFullyPaid.php`

## Current Source Facts

### Create Transaction Workspace

`CreateTransactionWorkspaceHandler` currently performs this orchestration:

1. begin transaction;
2. create note from payload;
3. persist note;
4. persist work items;
5. update note total;
6. record inline payment;
7. record legacy audit `transaction_workspace_created`;
8. sync note history projection;
9. commit transaction.

Failure handling:

- `DomainException` rolls back and returns failure;
- other `Throwable` rolls back and rethrows.

### Update Transaction Workspace

`UpdateTransactionWorkspaceHandler` currently performs this orchestration:

1. begin transaction;
2. assert note is editable;
3. load note by id;
4. update note header;
5. persist header;
6. persist replacement work items;
7. update note total;
8. record inline payment;
9. record legacy audit `transaction_workspace_updated`;
10. sync note history projection;
11. commit transaction.

Failure handling:

- `DomainException` rolls back and returns failure;
- other `Throwable` rolls back and rethrows.

### Work Item Persistence

Create work item persistence:

- maps each item payload;
- builds work item;
- adds work item to note aggregate;
- persists work item;
- issues inventory for each store stock line;
- collects package allocation audit metadata.

Update work item persistence:

- reverses issued inventory for existing note work items;
- deletes work items by note id;
- asks writer for next line number;
- replaces note work items in memory;
- delegates new item persistence to create persister.

### Inline Payment Recording

Inline payment recording:

- may skip payment;
- creates customer payment;
- creates cash detail when relevant;
- checks allocation policy;
- allocates payment across payable components;
- persists payment;
- persists component allocations;
- auto-closes note when fully paid;
- records legacy audit `payment_allocated`;
- returns inline payment summary.

### Auto Close

Auto close:

- reads allocated total and refunded total;
- computes net paid;
- closes note when net paid covers note total;
- updates note operational state;
- records timeline event `note_closed`.

## Mutation Matrix

| Mutation | Boundary | Writes / Effects | Audit | Projection | Migration Risk |
|---|---|---|---|---|---|
| Create transaction workspace | TransactionManagerPort begin/commit/rollback | note create, work item create, inventory issue, optional payment create, payment component allocation, optional note close | legacy `transaction_workspace_created`, legacy `payment_allocated` when inline payment is recorded, timeline `note_closed` when auto-close happens | sync note projection | high: create defines source ids and component facts used by refund and audit |
| Update transaction workspace | TransactionManagerPort begin/commit/rollback | note header update, inventory reversal for old store-stock lines, work item delete/recreate, inventory issue for new store-stock lines, optional payment create/allocation, optional note close | legacy `transaction_workspace_updated`, legacy `payment_allocated` when inline payment is recorded, timeline `note_closed` when auto-close happens | sync note projection | high: update can rewrite item/component shape after initial transaction |
| Inline payment inside create/edit | Runs inside parent create/edit transaction | customer payment, cash detail, payment component allocations, possible note operational state change | legacy `payment_allocated`, timeline `note_closed` when eligible | parent handler syncs projection | high: payment allocation becomes refund input |
| Store stock item create/edit | Runs inside create/edit transaction | inventory issue on create, inventory reversal then issue on edit | currently not canonical audit in this matrix | parent handler syncs projection | high: refund can later reverse store stock inventory |

## Required Contract Decisions

### CED-001 - Transaction Boundary Contract

Status: open.

Current fact:

- create/edit use `TransactionManagerPort` with manual begin/commit/rollback.

Gap:

- no explicit isolation level;
- no retry/deadlock behavior;
- no nested transaction policy;
- no after-commit policy.

Required decision:

- define whether create/edit requires a stronger transaction manager contract before Go/API ownership.

### CED-002 - Idempotency Contract

Status: open.

Current fact:

- no idempotency key is visible in the inspected create/edit handlers.

Gap:

- duplicate submit behavior is not documented in this matrix;
- replay response policy is not defined;
- same-key different-payload rejection is not defined.

Required decision:

- decide whether create/edit requires idempotency before migration readiness closure.

### CED-003 - Update Replacement Semantics

Status: open.

Current fact:

- update reverses issued inventory, deletes existing work items, then recreates work items.

Gap:

- line number semantics after delete/recreate need proof;
- payment allocation behavior after item replacement needs proof;
- refund implications for already-paid or already-refunded notes need proof.

Required decision:

- define whether edit is allowed only before payment/refund, or how paid/refunded edits are constrained.

### CED-004 - Inline Payment Coupling

Status: open.

Current fact:

- create/edit can record inline payment inside the same transaction.

Gap:

- payment allocation is refund input;
- payment audit remains legacy;
- no canonical audit facts are defined for inline payment in this matrix.

Required decision:

- decide whether inline payment remains inside create/edit use case or becomes a separate mutation contract.

### CED-005 - Audit Contract

Status: open.

Current fact:

- create/edit workspace still use legacy `AuditLogPort`;
- payment allocation inside create/edit also uses legacy audit;
- audit outbox selected proof currently covers expense category only.

Gap:

- canonical `audit_events` payload for create/edit is not defined;
- before/after snapshots are not defined;
- aggregate/source id policy is not defined.

Required decision:

- block create/edit audit outbox expansion until create/edit mutation contract is proven.

### CED-006 - Projection Contract

Status: open.

Current fact:

- create/edit call `NoteHistoryProjectionService::syncNote` before commit.

Gap:

- projection table effects are not mapped in this document yet;
- report-read expectations after create/edit are not listed.

Required decision:

- define projection/report assertions needed for create/edit regression.

## Required Proof Before Implementation Patch

Before modifying create/edit logic, provide:

1. source inspection for route/controller request shape;
2. source inspection for note writer and work item writer effects;
3. source inspection for payment allocation tables touched by inline payment;
4. source inspection for projection sync output;
5. list of current create/edit tests or proof that tests are missing;
6. table write map;
7. rollback characterization;
8. duplicate submit/idempotency characterization;
9. paid-note edit policy proof;
10. refunded-note edit policy proof or explicit scope-out.

## Suggested Focused Test Matrix

| Test Area | Required Behavior |
|---|---|
| Create without inline payment | creates note/work items, updates total, no payment allocation |
| Create with inline payment | creates payment/component allocations and can auto-close note |
| Create with store stock item | issues inventory movement |
| Update unpaid note | updates header/items/total and syncs projection |
| Update store stock item | reverses previous issue and issues new inventory |
| Update with inline payment | creates additional payment allocation safely |
| Update paid note | either rejected or proven safe |
| Update refunded note | either rejected or proven safe |
| Rollback after failure | no partial note/item/payment/inventory/projection/audit writes |
| Duplicate submit | idempotency policy is proven or accepted-risk is documented |

## Phase 1B Inspection Addendum

Status: source inspection addendum.

This section records the route, controller, request, persistence adapter, projection sync, and existing test inventory inspection for create/edit transaction workspace.

This section is not implementation proof.

This section does not change runtime behavior.

This section does not authorize refund hardening, audit outbox expansion, seeder hardening, or broad transaction rewrite.

### Route / Controller / Request Map

| Surface | Route / Entry | Controller | Request | Application Entry | Current Finding |
|---|---|---|---|---|---|
| Create transaction workspace | `POST /notes/workspace/store`, route name `notes.workspace.store` | `app/Adapters/In/Http/Controllers/Note/StoreTransactionWorkspaceController.php` | `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRequest.php` | `app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php` | Active create route. Controller sends validated payload to create workspace handler and deletes create draft on success when actor exists. |
| Admin workspace update | `PATCH /admin/notes/{noteId}/workspace`, route name `admin.notes.workspace.update` | `app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php` | `app/Adapters/In/Http/Requests/Note/StoreNoteRevisionRequest.php` | `app/Application/Note/UseCases/CreateNoteRevisionHandler.php` | Active admin update route is revision-based. Controller disables workspace editability enforcement for admin route. |
| Cashier workspace update | `PATCH /cashier/notes/{noteId}/workspace`, route name `cashier.notes.workspace.update` | `app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php` | `app/Adapters/In/Http/Requests/Note/StoreNoteRevisionRequest.php` | `app/Application/Note/UseCases/CreateNoteRevisionHandler.php` | Active cashier update route is revision-based and passes through cashier note access middleware. |
| Legacy / candidate update workspace controller | No active route found in inspected `routes/web/note.php` | `app/Adapters/In/Http/Controllers/Note/UpdateTransactionWorkspaceController.php` | `app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceRequest.php` | `app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php` | Source exists, but route binding is not proven. Do not treat it as active production route until route proof exists. |

### Request Shape Map

| Request | Normalizer | Rules | Extra Validator | Special Behavior |
|---|---|---|---|---|
| `StoreTransactionWorkspaceRequest` | `StoreTransactionWorkspaceInputNormalizer::normalize()` | `StoreTransactionWorkspaceRules::build()` | `StoreTransactionWorkspaceValidator::validate()` | Authorizes true and passes create payload through normal create workspace validation. |
| `StoreNoteRevisionRequest` | `UpdateTransactionWorkspaceInputNormalizer::normalize()` | `UpdateTransactionWorkspaceRules::build()` plus nullable `reason` | `UpdateTransactionWorkspaceValidator::validate()` | Forces `inline_payment.decision = skip` and sets default revision reason when missing. This is the active routed update request. |
| `UpdateTransactionWorkspaceRequest` | `UpdateTransactionWorkspaceInputNormalizer::normalize()` | `UpdateTransactionWorkspaceRules::build()` | `UpdateTransactionWorkspaceValidator::validate()` | Exists for `UpdateTransactionWorkspaceController`, but the controller route is not proven active from inspected routes. |

### Handler Boundary Map

| Entry | Transaction Boundary | Main Mutation Sequence | Failure Behavior |
|---|---|---|---|
| `CreateTransactionWorkspaceHandler` | Manual `TransactionManagerPort` begin / commit / rollback | create note, persist work items, update total, optional inline payment, legacy audit `transaction_workspace_created`, projection sync | `DomainException` rolls back and returns failure; other throwable rolls back and rethrows. |
| `CreateNoteRevisionHandler` through active update route | Manual `TransactionManagerPort` begin / commit / rollback | lock root note, optional editable guard, resolve current revision, build replacement, apply active replacement, create next revision, create settlement, set current revision, legacy audit `note_revision_created` | `DomainException` rolls back and returns failure; other throwable rolls back and rethrows. |
| `UpdateTransactionWorkspaceHandler` | Manual `TransactionManagerPort` begin / commit / rollback | editable guard, load note, update header, replace work items, update total, optional inline payment, legacy audit `transaction_workspace_updated`, projection sync | Source exists, but route binding is not proven. Treat as candidate / legacy path until route proof exists. |

### Table Write Map

| Flow | Table / Effect | Writer / Source | Current Finding |
|---|---|---|---|
| Create workspace | `notes` insert and later total update | `DatabaseNoteWriterAdapter` through `NoteWriterPort` | `create()` inserts note row; `updateTotal()` updates `total_rupiah`. |
| Create workspace | `work_items` insert | `DatabaseWorkItemWriterAdapter` through `WorkItemWriterPort` | Work item base row is inserted for each mapped item. |
| Create workspace | `work_item_service_details` insert | `DatabaseWorkItemWriterAdapter` | Inserted when service detail exists. |
| Create workspace | `work_item_external_purchase_lines` insert | `WorkItemLineInsertsTrait` | Inserted when external purchase lines exist. |
| Create workspace | `work_item_store_stock_lines` insert | `WorkItemLineInsertsTrait` | Inserted when store stock lines exist. |
| Create workspace with store stock | product inventory, costing, movement writer effects | `IssueInventoryOperation` | Issues `stock_out` for each store stock line and updates inventory/costing through inventory ports. Exact adapter table mapping is outside this addendum unless inventory adapters are inspected. |
| Create workspace with inline payment | `customer_payments` insert | `DatabaseCustomerPaymentWriterAdapter` | Inserted when inline payment is not skipped. |
| Create workspace with cash inline payment | `customer_payment_cash_details` insert | `DatabaseCustomerPaymentWriterAdapter` | Inserted when cash detail exists. |
| Create workspace with inline payment | `payment_component_allocations` insert | `DatabasePaymentComponentAllocationWriterAdapter` | `createMany()` inserts component allocations. |
| Create workspace with full payment | note operational state and timeline effect | `AutoCloseNoteWhenFullyPaid` | Existing matrix records auto-close and timeline behavior; table-level writer for timeline was not re-inspected in this addendum. |
| Create workspace | legacy audit | `AuditLogPort` | Handler records `transaction_workspace_created`; existing create package audit test asserts `audit_logs` row. |
| Create workspace | `note_history_projection` upsert | `NoteHistoryProjectionService` and `DatabaseNoteHistoryProjectionWriterAdapter` | Handler calls `syncNote()` before commit. |
| Active update route | `notes` header, total, current revision fields | `ApplyNoteRevisionAsActiveReplacement`, `CreateNoteRevisionCommitter`, writer ports | Active route applies replacement to root note and later sets current revision. Tests assert `current_revision_id` and `latest_revision_number`. |
| Active update route | old work item replacement | `UpdateTransactionWorkspaceWorkItemPersister` through `ApplyNoteRevisionAsActiveReplacement` | Applies inventory reversal, deletes allowed old work items, recreates trusted replacement items. |
| Active update route | payment allocation replay | `NoteReplacementPaymentAllocationReconciler` | Captures existing allocation minus refunds, deletes existing component allocations, rebuilds replayable allocations after replacement. |
| Active update route | `note_revisions` and revision lines | `CreateNoteRevisionCommitter` through revision writer | Existing tests assert `note_revisions` and `note_revision_lines`; specific adapter table implementation is not inspected in this addendum. |
| Active update route | revision settlement | `CreateNoteRevisionCommitter` through settlement writer | Workflow builds settlement and committer persists it when present. Exact table mapping is not inspected in this addendum. |
| Active update route | legacy audit | `AuditLogPort` | Committer records `note_revision_created`; existing update tests assert `audit_logs`. |
| Candidate / legacy update handler | `notes`, work item replacement, optional inline payment, legacy audit, projection | `UpdateTransactionWorkspaceHandler` | Source exists and is mapped, but active route binding is not proven. |

### Persistence Adapter Write Map

| Adapter / Service | Write Target | Notes |
|---|---|---|
| `DatabaseNoteWriterAdapter` | `notes` | Creates notes and updates header, total, or operational state. |
| `DatabaseWorkItemWriterAdapter` | `work_items`, `work_item_service_details`, external purchase lines, store stock lines | Creates base work item and related detail/line records. |
| `WorkItemDeletesTrait` | work item detail/line tables and `work_items` | Deletes by note id but skips work items protected by `refund_component_allocations`. |
| `DatabaseCustomerPaymentWriterAdapter` | `customer_payments`, `customer_payment_cash_details` | Payment row is always created when called; cash detail is conditional. |
| `DatabasePaymentComponentAllocationWriterAdapter` | `payment_component_allocations` | Creates component allocations and can delete allocations by note id. |
| `IssueInventoryOperation` | inventory movement, product inventory, product costing through ports | Table adapter details are not mapped in this addendum. |
| `ReverseIssuedInventoryOperation` | reverse inventory movement, product inventory, product costing through ports | Duplicate reverse protection is based on reverse source type and source id. |
| `DatabaseNoteHistoryProjectionWriterAdapter` | `note_history_projection` | Upserts projection row by `note_id`. |

### Projection Sync Output Map

`NoteHistoryProjectionService::syncNote()` reads a projection source row by note id, computes derived fields, then upserts `note_history_projection`.

Source fields:

- `note_id`
- `transaction_date`
- `note_state`
- `customer_name`
- `customer_phone`
- `total_rupiah`
- `allocated_rupiah`
- `refunded_rupiah`
- `line_open_count`
- `line_close_count`
- `line_refund_count`

Derived output fields:

- `customer_name_normalized`
- `net_paid_rupiah`
- `outstanding_rupiah`
- `has_open_lines`
- `has_close_lines`
- `has_refund_lines`
- `projected_at`

Projection sync is explicitly called by:

- `CreateTransactionWorkspaceHandler`
- `UpdateTransactionWorkspaceHandler`
- `ApplyNoteRevisionAsActiveReplacement`

### Existing Tests Inventory

| Test File | Covered Path | Current Coverage | Still Missing |
|---|---|---|---|
| `tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php` | Active create route `notes.workspace.store` | Creates package auto split transaction and asserts legacy `transaction_workspace_created` audit payload contains package allocation metadata. | Does not assert full create table map, inline payment, projection output, rollback, idempotency, or auto-close. |
| `tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php` | Active cashier update route `cashier.notes.workspace.update` | Verifies routed update creates a new note revision, revision lines, updates current revision metadata, and records `note_revision_created`. Also verifies inline payment is skipped for revision route. | Does not cover `UpdateTransactionWorkspaceController` or `UpdateTransactionWorkspaceHandler` as an active route. Does not assert projection output or store-stock reversal/re-issue. |
| `tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php` | Active cashier update route guard | Verifies cashier cannot submit closed note workspace replacement; asserts note/work item/allocation state remains unchanged and no second revision is created. | Covers closed-note cashier guard only; does not define broader paid/refunded edit policy for admin or all revision states. |
| `tests/Feature/Note/WorkItemWriterTimestampFeatureTest.php` | Work item writer timestamp behavior | Adjacent adapter-level proof for writer timestamps. | Not a create/edit transaction contract test. |
| `tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php` | Reverse inventory operation | Adjacent inventory operation proof. | Not a routed create/edit transaction test. |

### Missing Characterization Tests

| Missing Test | Why It Matters | Suggested Priority |
|---|---|---|
| Create workspace without inline payment full write map | Baseline create transaction must prove note, work item, total, audit, and projection effects. | High |
| Create workspace with inline payment and service-only item | Proves `customer_payments`, optional cash detail, `payment_component_allocations`, payment audit, and projection math. | Highest |
| Create workspace with full inline payment auto-close | Proves note state, net paid, outstanding, projection, and timeline behavior. | High |
| Create workspace rollback on post-note failure | Proves transaction boundary prevents partial note/item/payment/projection/audit writes. | High |
| Active update route projection assertion | Verifies revision replacement updates `note_history_projection`. | High |
| Active update route store-stock reversal/re-issue | Proves replacement path does not corrupt inventory movement and costing. | High |
| `UpdateTransactionWorkspaceController` route binding proof or dead-path decision | Prevents future work from hardening a route that is not active. | Highest before patching that handler |
| Duplicate submit / idempotency characterization | Required for migration readiness; current idempotency policy is still open. | Medium |
| Paid/refunded edit policy characterization | Refund maturity depends on stable edit behavior after payment/refund. | High |

### First Safest Test Patch Candidate

First safest test patch candidate:

- file: `tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentProjectionFeatureTest.php`
- type: characterization test only
- target route: `notes.workspace.store`
- fixture shape: service-only item, no store-stock line, inline cash payment
- expected assertions:
  - `notes` row exists with expected total;
  - `work_items` and `work_item_service_details` exist;
  - `customer_payments` exists;
  - `customer_payment_cash_details` exists for cash payment;
  - `payment_component_allocations` exists for the note/work item;
  - `note_history_projection` has allocated, net paid, and outstanding values;
  - legacy `payment_allocated` and `transaction_workspace_created` audit rows exist or current audit semantics are explicitly adjusted if runtime binding has changed.

Reason:

- it does not touch implementation;
- it stays on the active create route;
- it avoids store-stock fixture complexity;
- it proves the highest-risk create/edit dependency for later refund work: payment allocation plus projection output.

Do not patch `UpdateTransactionWorkspaceHandler` until route binding is proven or a dead-path decision is recorded.

## Work Gates

| Gate | Opens | Blocks |
|---|---|---|
| table write map exists | focused characterization tests | implementation patch |
| current tests inventory exists | gap-based test patch | guessing coverage |
| rollback characterization exists | safe transaction hardening | partial-write regressions |
| paid/refunded edit policy exists | refund matrix | refund built on unstable edit semantics |
| audit fact contract exists | audit outbox expansion | legacy/canonical churn |
| projection assertions exist | migration-readiness proof | report drift |

## Current Decision

Create/edit transaction maturity remains the next domain driver.

Do not start refund hardening until create/edit contract gaps are mapped.

Do not expand audit outbox to create/edit until create/edit mutation contract is proven.

Do not use seeder hardening to define domain semantics.

Seeder hardening may be pulled earlier only if it blocks focused proof.

## Next Active Step

Inspect create/edit route, controller, request, persistence adapters, projection sync, and existing tests.

Recommended next document or patch target:

- update this matrix with table write map and test inventory; or
- create a focused characterization test if test inventory proves a missing critical path.

No implementation patch should be made before that inspection is complete.
