# ADR-0022 — Payment Allocation Concurrency and Over-Allocation Protection

## Status

Accepted for planning.

Implementation status: not implemented yet.

This ADR records owner-approved direction for same-note payment allocation concurrency, revision/payment serialization, and over-allocation protection.

This ADR does not mark any `docs/error_log/*.md` finding as fixed.

A finding is fixed only after characterization test, implementation proof, relevant blast-radius verification, owner review, and accepted diff.

## Context

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

The system handles operational finance, notes, payments, refunds, stock, supplier evidence, reports, and cashier/admin workflows.

Payment allocation is a money-integrity boundary.

If concurrent note mutation, payment mutation, refund mutation, or revision replay can interleave unsafely, the system can record money incorrectly.

Existing audit findings show at least two concurrency-related risks:

1. Revision reallocation can lose concurrent payments.
2. Concurrent note payments can over-allocate balances.

These are not UI issues.

These are transaction integrity issues.

The final solution must be enforced in application/database transaction boundaries, not through Blade, JavaScript, button disabling, user discipline, or hope. Hope is not a lock primitive.

## Related Error Logs

ADR-0022 covers:

- `docs/error_log/010-revision-reallocation-can-lose-concurrent-payments.md`
- `docs/error_log/026-concurrent-note-payments-can-over-allocate-balances.md`

ADR-0022 may influence future implementation checks around:

- payment recording
- payment component allocation
- note revision allocation replay
- refund mutation when it competes with payment or revision
- auto-close behavior triggered after payment
- settlement/outstanding calculation under concurrent mutation

## Problem Statement

The system has finance mutation paths that can affect the same note.

Examples:

- payment records money and writes allocations
- note revision captures existing allocations, deletes/rebuilds allocations, updates note total, and syncs projection
- refund records customer refund and component refund allocation
- auto-close may update note state based on settlement
- cashier/admin requests may run close together through browser double-submit, repeated click, slow network retry, or parallel users

If two requests mutate the same note without a shared serialization protocol, the system can enter invalid states:

1. payment row exists but allocation row is lost
2. two concurrent payments both see the same outstanding amount
3. total allocated exceeds current payable or outstanding amount
4. revision replay deletes allocation from a payment that committed after capture but before delete
5. auto-close evaluates stale settlement
6. reports and current projection read inconsistent finance state
7. audit trail becomes hard to reconcile

The core problem is not one missing check.

The core problem is missing same-note transaction serialization and allocation invariant enforcement.

## Decision

Same-note finance mutation must be serialized.

Any operation that can mutate payment, refund, note revision allocation, current note total, payment component allocation, refund component allocation, or settlement-driven note state must use a shared same-note concurrency boundary.

The primary lock boundary is the target note row.

The lock must be acquired inside an active database transaction.

Payment write and payment allocation write must be atomic.

Outstanding, allocated, refunded, and payable state must be calculated after the same-note lock is acquired.

A request must not rely on a value calculated before the lock as final authority.

If after acquiring the lock the note is no longer payable, no longer eligible, or the requested payment exceeds the valid outstanding amount, the request must fail with a domain validation error and must not write partial money/allocation state.

## Owner Decisions Locked

### 1. Same-Note Mutation Serialization

All same-note payment/revision/refund mutation paths must serialize through the same note-level concurrency protocol.

Default lock target:

    notes row for the target note id

The preferred implementation mechanism is:

    database transaction + lockForUpdate() on the target note row

This lock must be used consistently by competing same-note mutation paths.

### 2. Atomic Payment And Allocation

Payment recording and allocation creation must be atomic.

Forbidden final state:

- `customer_payments` row exists but its required allocation rows are missing
- allocation rows exist without valid payment row
- payment mutation commits while allocation mutation fails silently
- note settlement changes based on stale or partial allocation state

If allocation fails, the payment transaction must roll back unless the operation intentionally records an explicitly unallocated payment under a future ADR.

No such unallocated-payment model is approved by this ADR.

### 3. Invariant

The minimum invariant:

    total current allocated settlement for a note must not exceed the valid payable or outstanding amount for the current finance state

For component-level allocation:

    component allocated amount must not exceed the eligible component payable amount

For payment-level allocation:

    allocated amount for a customer payment must not exceed the customer payment amount

For revised/refunded notes, the invariant must respect ADR-0018 and carry-forward settlement decisions.

This ADR does not redefine finance settlement semantics.

It enforces that whichever settlement semantics ADR-0018 approves cannot be violated by concurrent writes.

### 4. Recalculate After Lock

Before writing payment/refund/revision allocation changes, the operation must:

1. open transaction
2. lock target note row
3. load current finance state after lock
4. calculate outstanding/payable/eligibility after lock
5. validate requested mutation
6. write all related rows
7. update settlement/projection/note state if required
8. commit atomically

Values calculated outside this lock may be used for UI preview only.

Values calculated outside this lock must not be used as final write authority.

### 5. Idempotency

Idempotency key is recommended but not required for the first implementation slice unless existing source already supports it.

Reason:

- row-level serialization is the immediate money-integrity requirement
- idempotency prevents duplicate retry/double-submit, but it does not replace locking
- idempotency may require separate storage and request contract decisions

Future idempotency addendum may define:

- idempotency key source
- uniqueness scope
- request hash behavior
- replay response behavior
- expiration policy
- audit handling

### 6. DB Constraints

Database constraints are allowed only after the invariant and schema impact are proven.

No aggregate over-allocation constraint may be added casually.

Reason:

- aggregate allocation invariants often cannot be expressed as a simple unique constraint
- wrong constraints can break valid partial payment, refund, or revision lifecycle
- migration must be carefully reviewed for existing production data

DB constraints may be added as defense-in-depth after application-level transaction logic is proven.

### 7. Queue Is Not Primary Concurrency Control

Queueing is not approved as the primary fix for same-note finance mutation concurrency.

Reason:

- the web request path still needs correctness
- queue ordering may not cover all mutation paths
- queue retry can introduce duplicate processing without idempotency
- database transaction invariant is still required

Queueing may be considered later for background projection rebuild or async reporting, not as the primary money-integrity lock.

### 8. UI Is Not Concurrency Control

The following are not valid final concurrency controls:

- disabling a submit button
- hiding a button
- debounce in JavaScript
- modal confirmation
- optimistic UI state
- cashier instruction
- admin instruction
- "do not click twice" training

UI may reduce accidental duplicate requests, but server-side transaction enforcement remains mandatory.

### 9. Error Behavior

If a request becomes invalid after the same-note lock is acquired, the system must fail safely.

Examples:

- note already fully paid
- requested partial payment exceeds current outstanding
- target row is no longer current/eligible
- revision already changed payable components
- refund already changed settlement state

Expected behavior:

- no partial finance write
- domain validation error or existing app error envelope
- audit or timeline record only if current policy requires denied-attempt audit
- no silent success
- no fallback mutation that changes money differently from the request

### 10. Error Log Fixed Rule

No concurrency error log is fixed only because a lock was added.

Final fixed requires:

- red characterization test or strongest available reproduction exists before patch
- targeted test fails for expected reason when feasible
- patch uses shared same-note serialization
- targeted test passes after patch
- relevant payment/revision/refund blast-radius tests pass
- no stale unlocked competing path remains in approved scope
- docs/error_log is updated only after proof
- owner reviews and accepts proof

If true concurrent test is not feasible in local environment, status must remain:

    Patched with verification gap

until stronger behavior proof exists.

## Scope In

ADR-0022 applies to:

- same-note payment recording
- customer payment creation when tied to note settlement
- payment allocation creation
- payment component allocation creation
- note revision allocation capture/delete/rebuild
- same-note payment/revision race
- same-note payment/payment race
- current outstanding validation during payment
- note auto-close after payment
- refund mutation only when it competes with payment/revision settlement state
- finance transaction boundary for affected routes/use cases

## Scope Out

ADR-0022 does not decide:

- carry-forward settlement semantics
- overpaid/kembalian storage model
- current projection schema
- refund eligibility rules
- cashier/admin access boundary
- attachment or output security
- seeder credential safety
- UI redesign
- route naming changes
- reporting formula changes except when stale allocation race directly affects consistency
- queue architecture
- new idempotency storage model for first slice
- DB constraints before invariant proof

## Binding Relationship To Existing ADRs

### ADR-0018

ADR-0018 owns finance lifecycle semantics:

- note revision
- refund
- settlement
- carry-forward
- overpaid/kembalian
- current projection
- historical ledger

ADR-0022 does not redefine these.

ADR-0022 enforces that concurrent writes cannot violate whichever finance semantics ADR-0018 defines.

### ADR-0019

ADR-0019 owns access boundary:

- actor access
- cashier date window
- admin transaction capability
- supplier invoice capability
- route guard
- capability audit

ADR-0022 assumes the actor has already passed access checks when required.

Passing access does not permit violating concurrency or allocation invariants.

### ADR-0020

ADR-0020 owns output, URL, storage, attachment, and disclosure security.

ADR-0022 does not modify public surface behavior.

## Required Same-Note Mutation Protocol

The preferred same-note mutation protocol:

1. Begin database transaction.
2. Load target note using a locking read.
3. Ensure the locking read uses `lockForUpdate()` or equivalent.
4. Re-load or calculate current finance state after the lock.
5. Validate domain eligibility and outstanding/payable amount after the lock.
6. Write payment/refund/revision allocation rows atomically.
7. Update note total/status/projection/timeline if required.
8. Commit transaction.
9. Roll back all writes on failure.

Forbidden protocol:

1. Calculate outstanding outside transaction.
2. Accept request amount based on stale UI state.
3. Insert payment row.
4. Insert allocation row separately.
5. Handle allocation failure after payment commit.
6. Rely on auto-close or later reconciliation to hide inconsistency.

## Candidate Implementation Surfaces

Exact files must be confirmed from source at execution time.

Likely affected areas include:

- note payment use cases
- payment recording operation
- payment allocation services
- payment component allocation reader/writer adapters
- note revision handler
- note replacement payment allocation reconciler
- refund operation if it competes with allocation/settlement
- auto-close service
- note reader lock methods
- route/controller entry points for admin/cashier payment
- route/controller entry points for note revision/workspace update

Implementation must start with source inventory, not assumptions.

## Characterization Test Direction

The tests should prove at least these behaviors.

### Scenario 1 — Payment/Revision Lost Allocation Race

Given:

- note has existing payment allocations
- request A starts note revision
- request A captures allocations
- request B records a new payment/allocation for the same note
- request A deletes/rebuilds allocations from stale snapshot

Expected final behavior after fix:

- payment request and revision request serialize
- no committed customer payment loses its allocation
- note settlement remains explainable
- no orphan/unallocated payment appears unless future ADR explicitly allows it

### Scenario 2 — Concurrent Payments Cannot Over-Allocate

Given:

- note has outstanding amount 100000
- request A attempts payment 100000
- request B attempts payment 100000 at the same time

Expected final behavior after fix:

- only one request can allocate the outstanding amount
- second request observes updated settlement after lock
- second request fails safely or is rejected as already paid
- total allocated does not exceed valid outstanding/current payable

### Scenario 3 — Partial Payment Race

Given:

- note has outstanding amount 100000
- request A attempts partial payment 60000
- request B attempts partial payment 60000 concurrently

Expected:

- first valid request commits
- second request recalculates outstanding after lock
- second request may only allocate up to valid remaining amount if request semantics allow adjustment
- otherwise second request fails validation
- system must not allocate total 120000 against 100000 outstanding

Default decision:

- do not auto-adjust user-requested payment amount silently
- fail validation when requested amount exceeds current outstanding after lock

### Scenario 4 — Auto-Close Uses Post-Write Settlement

Given:

- payment completes note settlement
- auto-close runs after payment

Expected:

- auto-close evaluates settlement inside or after the same transaction boundary according to implementation design
- stale settlement does not close incorrectly
- note state is consistent with committed payment/allocation rows

### Scenario 5 — Competing Refund/Payment/Revision

Given:

- refund or revision changes current payable/settlement state
- payment request competes with that change

Expected:

- same-note serialization prevents stale allocation
- final settlement remains explainable
- no over-allocation
- no lost allocation
- no double mutation from stale state

This scenario may be deferred to later slice if source inventory shows refund path has separate semantics. If deferred, the error log must record the verification gap.

## Test Strategy

Preferred tests:

- feature or integration tests around actual use-case/operation
- transaction-aware tests when possible
- targeted concurrency simulation if test environment supports two database connections
- deterministic interleaving tests using test hooks only if they do not leak into production
- unit tests for allocation invariant as supplement, not replacement

If true concurrent tests are not feasible:

- write strongest available sequential stale-state regression test
- prove lock protocol exists in both competing mutation paths
- prove payment + allocation are atomic in one transaction
- keep verification gap documented

No test may pass by only checking UI behavior.

## Implementation Options Considered

### Option A — Minimal Note Row Lock Only

Description:

Add `lockForUpdate()` on the target note row in competing payment and revision paths.

Pros:

- small diff
- easy to reason about
- immediately reduces interleaving risk
- suitable as first hardening step

Cons:

- unsafe if any competing path bypasses the lock
- does not solve idempotency
- does not prove allocation invariant by itself
- may not cover refund/payment/revision combinations unless all paths adopt protocol

Decision:

Allowed as mechanism, not sufficient as complete policy.

### Option B — Centralized Payment Allocation Transaction Boundary

Description:

Route payment allocation through a single operation that opens transaction, locks note, recalculates settlement, validates invariant, writes payment and allocations, and commits atomically.

Pros:

- strongest application-level boundary
- auditable
- easier to verify
- reduces scattered lock logic
- aligns with finance-sensitive POS requirements

Cons:

- requires source inventory
- may touch multiple services
- requires broader tests
- may reveal hidden callers

Decision:

Chosen as target direction.

### Option C — Idempotency Key

Description:

Require mutation requests to carry idempotency key to avoid duplicate processing.

Pros:

- protects against double-click and retry
- helps audit duplicate request behavior
- useful for payment endpoints

Cons:

- not a substitute for locking
- requires storage and expiry decisions
- may require request contract change
- not needed to close first lock/invariant gap

Decision:

Recommended future phase, not required for first implementation slice unless existing infrastructure already supports it.

### Option D — Database Constraints

Description:

Add unique indexes or constraints to prevent duplicate/invalid allocation.

Pros:

- database-level defense
- protects against bypassed application path for some cases
- useful as defense-in-depth

Cons:

- aggregate over-allocation is hard to enforce with simple constraints
- wrong constraint can block valid partial/refund/revision lifecycle
- may require data cleanup/migration
- must be designed after invariant proof

Decision:

Deferred until schema and invariant proof are available.

### Option E — Queue Serialization

Description:

Send same-note mutation requests to a queue and process sequentially.

Pros:

- can serialize work if designed correctly
- may help background jobs

Cons:

- web path still needs correctness
- queue retry can duplicate without idempotency
- not every mutation may go through queue
- more infrastructure complexity
- not a replacement for transaction invariant

Decision:

Rejected as primary fix.

### Option F — UI Prevention

Description:

Disable buttons, debounce clicks, or prevent double-submit in JavaScript.

Pros:

- improves user experience
- reduces accidental duplicate requests

Cons:

- not a server-side guarantee
- bypassable
- does not protect parallel users or retries
- does not protect API/HTTP replays

Decision:

Rejected as concurrency control.

UI prevention may be added as usability polish only after server-side correctness exists.

## Consequences

### Positive Consequences

- same-note finance mutations become serialized
- payment and allocation consistency improves
- over-allocation risk is reduced
- revision replay cannot silently delete concurrent payment allocation if all paths follow protocol
- future tests can target a clear invariant
- finance mutation behavior becomes more audit-able

### Negative Consequences

- more transaction code required
- possible lock wait under same-note contention
- broader source inventory required
- tests are more complex
- deadlock risk must be watched if lock ordering is inconsistent
- some flows may need refactor to share one boundary

### Accepted Tradeoff

The system accepts slightly stricter transaction discipline and possible same-note lock contention to protect money integrity.

Fast incorrect payment mutation is not acceptable.

## Implementation Direction

The first implementation should not start by adding random locks.

The first implementation should:

1. inventory all same-note mutation paths
2. identify existing transaction boundaries
3. identify all current `getByIdForUpdate()` or lock methods
4. identify all payment allocation writes
5. identify all delete/rebuild allocation flows
6. define one shared same-note lock protocol
7. add characterization test for `010` or `026`
8. patch smallest safe boundary
9. run targeted tests
10. run relevant blast-radius tests
11. update error_log only after proof

## Required Verification Themes

Implementation proof must include relevant checks for:

- note row lock used inside transaction
- payment and allocation write atomicity
- outstanding recalculated after lock
- concurrent or simulated concurrent payment cannot over-allocate
- revision/payment interleaving cannot lose allocation
- no unlocked competing path remains in approved scope
- second payment request fails safely when outstanding changed
- no silent amount adjustment unless future ADR approves it
- existing partial payment behavior still works
- existing full payment behavior still works
- existing refund/revision tests still pass when affected
- no docs/error_log fixed status without proof

## Documentation Rule

`docs/error_log/*.md` may be updated only after implementation proof exists.

For concurrency findings, documentation must be extra explicit about proof quality.

Allowed status examples:

- `Planned`
- `Patched with verification gap`
- `Fixed with proof`
- `Deferred with owner acceptance`

If true concurrency behavior is not tested, do not mark as fully fixed.

Use `Patched with verification gap` until stronger proof exists.

## Stop Conditions

Stop immediately if:

- patch calculates outstanding before lock and writes based on stale value
- patch inserts payment row outside the same transaction as allocation rows
- patch locks one path but leaves another approved competing path unlocked
- patch uses UI/JS prevention as final concurrency control
- patch changes finance settlement semantics without ADR-0018 update
- patch changes access/capability policy without ADR-0019 scope
- patch introduces idempotency request contract without explicit decision
- patch adds DB constraints without schema/invariant proof
- patch swallows allocation failure after payment commit
- patch silently adjusts requested payment amount without owner decision
- patch makes note paid/closed state inconsistent with committed allocation
- test failure reason is not understood
- broad refactor is needed before exact affected files are proven
- error_log update is attempted before proof

## Related Documents

- docs/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
- docs/error_log/026-concurrent-note-payments-can-over-allocate-balances.md
- docs/audit/codex-security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/adr/0020-public-surface-output-storage-attachment-security.md
- docs/blueprint/security/2026-05-06-adr-0019-access-boundary-blueprint.md
- docs/blueprint/security/2026-05-06-adr-0020-public-surface-output-storage-attachment-blueprint.md

## Final Rule

Payment allocation concurrency is a production money-integrity boundary.

If the system cannot prove that same-note finance mutations serialize safely, the implementation must not claim the concurrency finding is fixed.

For HyperPOS, money mutation correctness must be enforced by transaction and invariant, not by UI timing, operator discipline, or luck.
