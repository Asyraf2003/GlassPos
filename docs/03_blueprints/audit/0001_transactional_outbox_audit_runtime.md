# Transactional Outbox Audit Runtime Blueprint

## Status

Draft analysis + blueprint.

This document is not an implementation proof.

This document does not modify runtime behavior.

This document does not authorize broad transaction-flow refactor.

## Scope

This document analyzes the current audit runtime behavior and proposes a future audit runtime pipeline that keeps business activity fast while preserving precise audit records.

The target direction is:

- keep business validation synchronous;
- keep business writes valid and transactional;
- avoid heavy audit snapshot writes blocking the user-facing activity path;
- preserve canonical audit records;
- support future PostgreSQL migration readiness;
- support future API ownership readiness.

## Explicit Exclusions For This Slice

Transaction-heavy flows are skipped for now.

Skipped in this slice:

- customer transaction checkout flow;
- customer payment flow;
- customer refund flow;
- payment allocation flow;
- stock mutation tied to transaction/refund;
- transaction report mutation semantics;
- broad transaction/idempotency ownership changes.

Reason:

The transaction area still has known readiness gaps and should not be used as the first runtime audit pipeline migration target.

Allowed for later non-transaction pilot, after separate source inspection:

- create flows that do not mutate transaction/payment/refund allocation;
- edit flows that do not touch unresolved transaction correctness gaps;
- admin create/edit flows with clear use-case boundaries;
- audit event generation that already uses canonical `AuditEventWrite`.

## Source Of Truth

Current source anchors inspected:

- `database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php`
- `app/Ports/Out/AuditEventWriterPort.php`
- `app/Adapters/Out/Audit/DatabaseAuditEventWriterAdapter.php`
- `app/Providers/InfrastructureServiceProvider.php`
- `app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `docs/03_blueprints/db/go_postgres_migration_readiness/findings/04_transaction_idempotency_audit.md`
- `docs/03_blueprints/mobile/0001_mobile_api.md`

Local command output from the owner remains the highest proof source.

## Assumptions

These assumptions must be verified before implementation.

1. "Transaction flows" means customer checkout/payment/refund/allocation/stock-effect flows and any mutation whose correctness depends on transaction math.
2. "Create/edit allowed" means bounded non-transaction create/edit flows where application use cases already isolate validation and audit event creation.
3. Canonical audit remains `audit_events` + `audit_event_snapshots`.
4. `audit_logs` remains legacy/compatibility unless a later ADR says otherwise.
5. The first implementation slice should avoid changing Blade unless monitoring UI is explicitly requested.
6. The first implementation slice should avoid changing API behavior unless the selected pilot flow already has API exposure or an API contract is part of that slice.
7. Outbox entries may store snapshot payload JSON, but report-critical and audit-routing facts must remain explicit columns.

## Implementation Progress Snapshot

Latest proven progress from local operator output:

- Phase 0 documentation and matrix: completed for current audit slice.
- Phase 1 migration and adapter: implemented and focused tests passed.
- Phase 2 processor: implemented and focused tests passed.
- Phase 3 pilot binding in test: implemented and focused tests passed.
- Phase 4 global runtime binding switch: attempted and partially proven.
- Phase 5 monitoring: not implemented.

Current Phase 4 status:

- `AuditEventWriterPort` runtime binding was changed to `DatabaseAuditOutboxWriterAdapter`.
- Runtime binding test passed.
- Expense category HTTP update staged audit to `audit_outbox` and processor materialized canonical audit in focused proof.
- Selected regression still has old-expectation failures because some tests expect immediate writes to `audit_events`.
- Next required work is to update those tests to the staged audit runtime contract.

## GAP

Runtime proof has not been executed in this document.

No local benchmark exists yet for current synchronous audit overhead.

No complete audit writer usage map exists yet.

No mutation-by-mutation audit mode matrix exists yet.

No outbox schema exists yet.

No outbox processor command exists yet.

No retry, dead-letter, or pending audit monitoring exists yet.

No PostgreSQL parity test exists yet for the proposed outbox table.

No API response contract has been defined for audit processing state.

## Current Behavior Analysis

### Current Audit Shape

The current canonical audit system writes to:

- `audit_events`
- `audit_event_snapshots`

The audit event stores explicit routing and identity facts:

- id
- bounded_context
- aggregate_type
- aggregate_id
- event_name
- actor_id
- actor_role
- reason
- source_channel
- request_id
- correlation_id
- occurred_at
- metadata_json

The snapshot table stores:

- id
- audit_event_id
- snapshot_kind
- payload_json
- created_at

This is structurally good for auditability because event identity and lookup facts are explicit columns.

### Current Runtime Audit Flow

The current runtime audit path is synchronous inline audit.

Current shape:

```text
Application use case
→ transaction begin
→ business guard / validation
→ prepare domain/application mutation
→ build AuditEventWrite
→ AuditEventWriterPort::write()
→ DatabaseAuditEventWriterAdapter writes audit_events
→ DatabaseAuditEventWriterAdapter writes audit_event_snapshots
→ write business row(s)
→ transaction commit

This gives strong accountability.

If the transaction commits, the audit event is expected to exist.

If audit write fails, the mutation can roll back.

Current Strengths

The current model is precise.

The current model is easy to reason about.

The current model keeps audit close to the business use case.

The current model can store before/after snapshots.

The current model is appropriate for highly sensitive writes where audit must be atomic.

Current Weaknesses

The current model makes audit part of the user-facing write latency.

The current model extends the transaction duration.

The current model inserts both audit event and snapshot rows during the activity path.

The current model can become slower if snapshots grow.

The current model does not match the intended "observer beside the cashier" analogy.

The current model is closer to:

cashier works while the supervisor writes the audit book at the same desk before the cashier can finish

The intended target is closer to:

cashier works quickly
system records a small durable audit slip
audit processor writes the full audit book separately
Design Goal

The goal is not to remove audit.

The goal is to separate:

business correctness path;
durable audit capture;
heavy audit materialization.

The business path must remain valid and precise.

The audit trail must remain durable.

The heavy audit snapshot write should not unnecessarily block user-facing activity.

Rejected Direction
Reject fire-and-forget audit only

A pure queue/job dispatch after commit is not enough for finance-sensitive audit.

Risk:

business transaction succeeds
queue dispatch fails
audit event is lost
Reject database trigger as first solution

Database trigger audit may capture row changes, but it loses application-level context unless every context value is pushed into session variables or shadow columns.

Risk:

harder to test in Laravel;
harder to port to Go cleanly;
harder to preserve actor/reason/source_channel/request_id;
business event names become persistence-driven instead of use-case-driven.
Reject event sourcing rewrite

Event sourcing may be a clean audit model, but it is too broad for the current need.

Risk:

broad rewrite;
high migration cost;
too much surface area while transaction flows still have known gaps.
Proposed Direction

Use a transactional audit outbox.

Target shape:

Application use case
→ transaction begin
→ business guard / validation
→ write business row(s)
→ AuditEventWriterPort::write()
→ DatabaseAuditOutboxWriterAdapter writes a small durable audit_outbox row
→ transaction commit

Audit processor
→ reads pending audit_outbox rows
→ hydrates AuditEventWrite
→ DatabaseAuditEventWriterAdapter writes audit_events
→ DatabaseAuditEventWriterAdapter writes audit_event_snapshots
→ marks audit_outbox row processed

Important note:

The application use case may keep calling the same AuditEventWriterPort.

The implementation behind the port changes from direct canonical audit write to outbox write.

This minimizes use-case churn.

Blueprint
1. Migration Blueprint

Create a new table:

audit_outbox

Recommended columns:

id string primary key
audit_event_id string unique
bounded_context string
aggregate_type string
aggregate_id string
event_name string
actor_id string nullable
actor_role string nullable
reason text nullable
source_channel string nullable
request_id string nullable
correlation_id string nullable
occurred_at datetime
metadata_json json nullable
snapshots_json json nullable
status string
attempts integer
last_error text nullable
available_at datetime nullable
locked_at datetime nullable
processed_at datetime nullable
created_at datetime
updated_at datetime

Recommended indexes:

unique(audit_event_id)

index(status, available_at)
index(bounded_context, occurred_at)
index(aggregate_type, aggregate_id, occurred_at)
index(event_name, occurred_at)
index(correlation_id)
index(request_id)
PostgreSQL Readiness Rules

Use string status, not MySQL enum.

Use string ids.

Do not rely on MySQL unsigned integer behavior.

Keep audit routing facts as explicit columns.

Do not store all query-critical facts only inside JSON.

Use JSON for metadata and snapshots only.

Use explicit occurred_at.

Use explicit created_at / updated_at.

Avoid cascade delete for audit history.

Use restrict delete where foreign keys are introduced.

Status Values

Allowed status values:

pending
processing
processed
failed

Status validation must live in application/adapter code.

Do not use MySQL enum.

2. DTO Blueprint

Existing AuditEventWrite should remain the canonical write object.

Optional new DTO:

app/Application/Audit/DTO/AuditOutboxRecord.php

Purpose:

represent persisted outbox row;
validate status values;
avoid leaking raw DB row shape into processor logic.

This DTO is optional for the first slice if the processor remains small.

3. Port Blueprint

Keep existing port:

app/Ports/Out/AuditEventWriterPort.php

Current contract can remain:

public function write(AuditEventWrite $event): void;

Reason:

use cases should not know whether audit is direct or outbox-backed;
the port already expresses "write this audit event";
adapter decides whether the write is canonical or staged.

No new port is required for first slice.

Optional future port:

app/Ports/Out/AuditOutboxReaderPort.php

Only add if processor complexity grows.

4. Adapter Blueprint

Add:

app/Adapters/Out/Audit/DatabaseAuditOutboxWriterAdapter.php

Responsibility:

AuditEventWrite
→ serialize metadata
→ serialize snapshots
→ insert audit_outbox row

It must not write audit_events or audit_event_snapshots.

Keep existing:

app/Adapters/Out/Audit/DatabaseAuditEventWriterAdapter.php

Responsibility remains:

AuditEventWrite
→ insert audit_events
→ insert audit_event_snapshots

The existing adapter becomes the final materializer used by the processor.

5. Service Provider Blueprint

Current binding should eventually change from:

AuditEventWriterPort::class => DatabaseAuditEventWriterAdapter::class

to:

AuditEventWriterPort::class => DatabaseAuditOutboxWriterAdapter::class

This is the main switch that moves runtime audit from inline materialization to durable staged audit.

Do not change this binding until the outbox processor and tests exist.

6. Processor Blueprint

Add command:

app/Console/Commands/ProcessAuditOutboxCommand.php

Command name:

audit:outbox:process

Suggested options:

--limit=100
--retry-failed
--max-attempts=5

Processor algorithm:

1. select pending rows where available_at is null or available_at <= now
2. process in small batches
3. mark row processing or lock row
4. hydrate AuditEventWrite
5. write canonical audit via DatabaseAuditEventWriterAdapter
6. mark processed
7. on failure:
   - increment attempts
   - store last_error
   - set failed or pending retry
MySQL First Safe Locking

First slice may use simple transactional claiming:

status=pending
→ update to processing
→ process
→ processed/failed

Do not claim PostgreSQL-specific SKIP LOCKED behavior in first slice unless implemented and tested for both DB targets.

PostgreSQL Transition Note

The processor must avoid MySQL-specific SQL where possible.

If later optimization uses FOR UPDATE SKIP LOCKED, isolate it behind adapter behavior and document database-specific support.

7. Use Case Blueprint

First slice target:

do not refactor transaction-heavy flows;
pilot on a create/edit flow with existing canonical AuditEventWrite;
keep use-case call shape stable.

Preferred first pilot criteria:

- has existing AuditEventWrite factory
- has feature tests
- does not mutate customer transaction/payment/refund allocation
- does not depend on unresolved transaction math
- has clear before/after snapshot

Do not start with:

- customer payment
- customer refund
- selected-row refund
- checkout transaction
- stock reversal tied to refund
- payment allocation
8. Blade / UI Blueprint

Blade is not required for the first runtime implementation.

Optional later UI:

GET /admin/audit-outbox

Purpose:

show pending audit count;
show failed audit rows;
show attempts;
show last_error;
allow manual retry if authorized.

Do not build Blade before:

migration exists;
writer exists;
processor exists;
tests prove pending/processed/failed behavior.
9. API Readiness Blueprint

The outbox design must support future API use.

API controllers must remain transport adapters.

API controllers should not write audit tables directly.

API controllers should call application use cases.

Application use cases should emit audit through AuditEventWriterPort.

This keeps future Blade and API paths aligned.

Expected future shape:

Blade Controller
→ Application UseCase
→ AuditEventWriterPort
→ audit_outbox
→ audit processor
→ canonical audit

API Controller
→ same Application UseCase
→ same AuditEventWriterPort
→ same audit_outbox
→ same audit processor
→ same canonical audit

This avoids API-only duplicate business logic.

10. Verification Blueprint

Required tests before switching binding:

tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php
tests/Feature/AuditLog/ProcessAuditOutboxCommandTest.php
tests/Feature/AuditLog/AuditOutboxCanonicalMaterializationTest.php

Required proof:

php -l database/migrations/<new_audit_outbox_migration>.php
php -l app/Adapters/Out/Audit/DatabaseAuditOutboxWriterAdapter.php
php -l app/Console/Commands/ProcessAuditOutboxCommand.php
php artisan test tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php
php artisan test tests/Feature/AuditLog/ProcessAuditOutboxCommandTest.php

Behavior proof required:

- outbox row is created from AuditEventWrite
- audit_events is not written by outbox writer
- processor creates audit_events
- processor creates audit_event_snapshots
- processor marks outbox processed
- failed processing increments attempts and stores last_error
- repeated processor run does not duplicate canonical audit
11. Rollout Blueprint
Phase 0 - Documentation And Matrix

Create this document.

Create audit runtime mutation candidate matrix.

Do not implement runtime changes yet.

Phase 1 - Migration And Adapter

Add audit_outbox migration.

Add DatabaseAuditOutboxWriterAdapter.

Do not switch global binding yet.

Phase 2 - Processor

Add audit:outbox:process.

Prove canonical materialization.

Phase 3 - Pilot Binding In Test

Bind AuditEventWriterPort to outbox writer only in focused tests.

Use one create/edit pilot.

Skip transaction-heavy flows.

Phase 4 - Runtime Binding Switch

Switch service provider binding only after proof.

Run focused audit tests.

Run selected pilot feature test.

Phase 5 - Monitoring

Add command output or admin UI for pending/failed audit outbox rows.

Blade remains optional.

12. Open Questions

These must be resolved by source inspection or owner decision before implementation.

Which exact create/edit flow should be the first pilot?
Should canonical audit_events be available immediately for the selected pilot, or is eventual materialization acceptable?
What max acceptable audit materialization delay is allowed for local/prod?
Should failed outbox rows block any admin operation, or only alert?
Should old audit_logs receive dual-write during transition, or remain untouched?
Should outbox processor run by scheduler, queue worker, or manual artisan command first?
Should snapshots remain full JSON payload, or should snapshot payload be reduced for heavy flows?
Should audit_outbox retain processed rows forever, or purge/archive after canonical materialization?
13. Recommended Initial Decision

Adopt transactional audit outbox as the target audit runtime model.

Do not migrate transaction-heavy flows first.

Start with a non-transaction create/edit flow that already builds AuditEventWrite.

Keep canonical audit tables unchanged.

Add audit_outbox as a durable staging table.

Keep use cases stable by preserving AuditEventWriterPort.

Move heavy canonical audit materialization into a processor.

14. Non-Negotiable Constraints

Do not lose audit events.

Do not use JSON as the only source for audit routing facts.

Do not introduce MySQL enum.

Do not duplicate business logic for API.

Do not refactor transaction/payment/refund allocation flows in this slice.

Do not claim performance improvement without measurement.

Do not claim PostgreSQL readiness without migration/source proof.

Do not switch production binding before processor and retry behavior are proven.

