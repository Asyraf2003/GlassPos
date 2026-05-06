# Payment Allocation Concurrency and Over-Allocation Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any `docs/error_log/*.md` finding as fixed.

This document exists to make ADR-0022 execution rigid enough for CLI-based implementation later.

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

## Source Of Truth

- docs/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
- docs/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
- docs/error_log/026-concurrent-note-payments-can-over-allocate-balances.md
- docs/audit/codex-security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/adr/0020-public-surface-output-storage-attachment-security.md
- docs/blueprint/security/2026-05-06-adr-0019-access-boundary-blueprint.md
- docs/blueprint/security/2026-05-06-adr-0020-public-surface-output-storage-attachment-blueprint.md
- User owner decisions in planning session
- User command output from local repository
- Current source code at execution time

## Proof Available Before This Blueprint

Local proof from user command output:

    git status --short

showed:

    ?? docs/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md

Meaning:

- ADR-0022 file exists locally as untracked file.
- ADR-0022 is not committed yet.
- Implementation has not started yet.
- No concurrency error log is fixed by this proof.

## Decision Boundary

ADR-0022 owns same-note finance mutation concurrency and over-allocation protection.

ADR-0018 owns finance lifecycle semantics:

- note revision
- refund
- settlement
- carry-forward
- overpaid/kembalian
- current projection
- historical ledger

ADR-0019 owns access and capability policy.

ADR-0020 owns public surface, output, storage, attachment, and disclosure security.

ADR-0022 must not silently redefine settlement semantics, access boundary, or public-surface policy.

ADR-0022 enforces that concurrent writes cannot violate approved finance semantics.

## Explicit Scope

This blueprint covers:

- same-note payment recording concurrency
- payment component allocation over-allocation
- customer payment plus allocation atomicity
- note revision allocation capture/delete/rebuild race
- payment/payment race on the same note
- payment/revision race on the same note
- same-note lock protocol
- transaction boundary for affected finance mutations
- post-lock outstanding/payable recalculation
- concurrency characterization strategy
- blast-radius verification for payment/revision/refund when affected
- error_log update rules for concurrency findings

## Non Goals

Do not patch application source from this document alone.

Do not create or modify production code before characterization tests or source inventory.

Do not solve carry-forward settlement semantics here.

Do not solve overpaid/kembalian storage here.

Do not solve current projection schema here.

Do not solve refund eligibility rules here.

Do not solve cashier/admin access boundary here.

Do not solve attachment or output security here.

Do not solve seeder credential safety here.

Do not redesign UI.

Do not change public route names.

Do not add queue architecture.

Do not add idempotency storage in first slice unless source already supports it and owner explicitly approves.

Do not add DB aggregate constraints before schema and invariant proof.

Do not mark `010` or `026` fixed without proof.

## Owner Decisions Locked

### Same-Note Mutation Serialization

Same-note finance mutations must serialize.

Default lock target:

    notes row for target note id

Preferred mechanism:

    database transaction + lockForUpdate()

Every approved competing same-note mutation path must follow the same lock protocol.

### Atomic Payment And Allocation

Payment creation and payment allocation creation must be atomic.

Forbidden final states:

- customer payment committed without required allocation
- allocation committed without valid customer payment
- allocation failure swallowed after payment commit
- settlement state updated from partial finance write
- payment mutation success while allocation mutation failed

### Recalculate After Lock

Final write authority must come from state calculated after acquiring the same-note lock.

Allowed before lock:

- UI preview
- request form display
- optimistic display value

Forbidden before lock:

- final outstanding authority
- final allocation authority
- final close/paid authority
- final payable component authority

### Allocation Invariant

Minimum invariant:

    total current allocation must not exceed valid payable/current outstanding under approved settlement semantics

Component invariant:

    allocated amount for a component must not exceed eligible component payable amount

Payment invariant:

    allocated amount for a customer payment must not exceed customer payment amount

### No Silent Amount Adjustment

If a concurrent mutation changes outstanding amount before the second request writes, the second request must not silently adjust requested payment amount.

Default behavior:

- fail validation
- return existing app/domain error format
- write no partial payment/allocation state

Future silent adjustment requires explicit owner decision and ADR update.

### Idempotency

Idempotency is recommended for a future phase.

It is not required for the first implementation slice unless existing infrastructure already supports it.

Idempotency does not replace row lock and transaction invariant.

### DB Constraint

DB constraints are defense-in-depth only after invariant and schema impact are proven.

No aggregate over-allocation constraint may be added casually.

### UI Is Not Concurrency Control

JavaScript debounce, disabled submit button, hidden button, loading spinner, or operator instruction is not a valid concurrency control.

Server-side transaction correctness is mandatory.

## Error Log Coverage

| Error Log | Covered By ADR-0022 | Coverage Notes |
|---|---|---|
| 010 revision reallocation can lose concurrent payments | Yes | Same-note revision/payment serialization and payment allocation atomicity |
| 026 concurrent note payments can over-allocate balances | Yes | Same-note payment/payment serialization and post-lock outstanding validation |

## Risk Model

### Risk 1 — Lost Payment Allocation

Bad flow:

1. Revision captures allocation snapshot.
2. Another request records payment and allocation.
3. Revision deletes all note allocations.
4. Revision rebuilds from stale snapshot.
5. New payment row remains but allocation is lost.

Required prevention:

- payment and revision serialize through same note lock
- revision cannot delete allocations while payment is concurrently writing
- committed payment cannot lose its required allocation

### Risk 2 — Over-Allocated Note

Bad flow:

1. Note outstanding is 100000.
2. Request A reads outstanding 100000.
3. Request B reads outstanding 100000.
4. A writes payment 100000.
5. B writes payment 100000.
6. Total allocated becomes 200000.

Required prevention:

- same-note payment requests serialize
- second request recalculates outstanding after lock
- second request fails safely if outstanding changed

### Risk 3 — Stale Auto-Close

Bad flow:

1. Payment writes allocation.
2. Auto-close reads stale or partial settlement.
3. Note closes or remains open incorrectly.

Required prevention:

- auto-close reads settlement after committed payment/allocation state
- close decision is consistent with atomic payment mutation

### Risk 4 — Partial Payment Race

Bad flow:

1. Outstanding is 100000.
2. Two partial payments of 60000 run concurrently.
3. Both validate against stale outstanding.
4. Total allocated becomes 120000.

Required prevention:

- second partial payment observes post-lock outstanding 40000
- second request fails because requested 60000 exceeds current outstanding
- system does not silently mutate amount to 40000

### Risk 5 — Bypassed Lock Path

Bad flow:

1. Payment path A locks note.
2. Revision path B locks note.
3. Another old mutation path writes allocations without lock.
4. Race returns through side door, wearing a fake mustache like all cowardly bugs.

Required prevention:

- source inventory finds all approved same-note finance mutation paths
- all in-scope paths adopt shared protocol
- any remaining unknown path is documented as verification gap

## Required Same-Note Mutation Protocol

Every in-scope same-note finance mutation should follow this protocol:

1. Begin database transaction.
2. Lock target note row using `lockForUpdate()` or equivalent.
3. Load current note and relevant work items after lock.
4. Load current payment/refund/allocation state after lock.
5. Calculate outstanding, payable, and eligibility after lock.
6. Validate request against post-lock state.
7. Write payment/refund/revision allocation rows atomically.
8. Update note total/status/projection/timeline if required.
9. Commit transaction.
10. Roll back all writes on failure.

Forbidden protocol:

1. Calculate outstanding before lock.
2. Trust stale UI/request amount.
3. Insert payment row.
4. Insert allocation row separately.
5. Commit payment without allocation.
6. Use auto-close or report reconciliation to hide inconsistency.

## Source Inventory Requirements

Before implementation, identify exact current files for:

- route/controller for admin note payment
- route/controller for cashier note payment
- payment recording use case/operation
- payment allocation service
- payment component allocation writer
- payment component allocation reader
- note reader lock method
- note revision handler
- note replacement allocation reconciler
- refund operation if it competes with payment/revision
- auto-close service
- outstanding resolver
- note paid status policy
- note operational status resolver
- transaction wrappers or DB::transaction usage
- any direct DB insert into `customer_payments`
- any direct DB insert into `payment_component_allocations`
- any delete/rebuild allocation flow

## Suggested Discovery Commands

Run before implementation:

    grep -RIn "customer_payments\|payment_component_allocations\|payment_allocations\|deleteByNoteId\|lockForUpdate\|getByIdForUpdate\|DB::transaction\|closeIfEligible\|RecordAndAllocate\|PaymentAllocation\|NoteReplacementPaymentAllocationReconciler" app routes tests 2>/dev/null || true

Run route discovery:

    php artisan route:list | grep -Ei "payment|refund|workspace|revision|note" || true

Run document snapshot:

    sed -n '1,260p' docs/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
    sed -n '1,260p' docs/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
    sed -n '1,260p' docs/error_log/026-concurrent-note-payments-can-over-allocate-balances.md

## Characterization Test Matrix

Tests must be written before production patch for each slice.

### Slice 1 — Payment/Payment Over-Allocation

Goal:

- prove two same-note payment requests cannot over-allocate note outstanding

Required test shape:

1. Create note with known total/outstanding.
2. Simulate two payment operations that both attempt to pay based on same original outstanding.
3. Ensure operation A succeeds.
4. Ensure operation B recalculates after lock or fails safely.
5. Assert total allocated does not exceed outstanding/current payable.
6. Assert no duplicate settlement state.
7. Assert no partial payment row without allocation.

Preferred expected behavior:

- first request succeeds
- second request fails with already paid or invalid amount after post-lock recalculation
- no silent amount adjustment

### Slice 2 — Payment/Revision Lost Allocation

Goal:

- prove note revision cannot delete allocation from a concurrent committed payment

Required test shape:

1. Create note with existing payment allocation.
2. Start revision allocation capture path.
3. Interleave or simulate a payment allocation write.
4. Run revision delete/rebuild path.
5. Assert committed payment allocation is not lost.
6. Assert payment row and allocation row remain consistent.
7. Assert final note settlement remains explainable.

If true interleaving cannot be tested:

- write strongest available lock-protocol regression
- prove both revision and payment paths load note for update inside transaction
- document remaining verification gap

### Slice 3 — Partial Payment Race

Goal:

- prove concurrent partial payments cannot exceed current outstanding

Required test shape:

1. Note outstanding is 100000.
2. Request A attempts partial 60000.
3. Request B attempts partial 60000.
4. A succeeds.
5. B sees remaining outstanding 40000 after lock.
6. B fails because requested 60000 exceeds remaining outstanding.
7. Assert total allocated is 60000, not 120000.

### Slice 4 — Payment Atomicity

Goal:

- prove payment row and allocation row commit or roll back together

Required test shape:

1. Force allocation failure after payment creation inside test-controlled path.
2. Assert customer payment does not remain committed without allocation.
3. Assert no settlement state changes from partial write.
4. Assert error is surfaced.

If forcing allocation failure requires invasive production hooks, design a lower-level transaction test around the operation boundary.

### Slice 5 — Auto-Close Settlement Consistency

Goal:

- prove auto-close decision is consistent with committed payment/allocation state

Required test shape:

1. Create note with outstanding equal to payment amount.
2. Record payment.
3. Assert payment and allocation are committed atomically.
4. Assert auto-close sees post-write settlement.
5. Assert note close state matches final settlement.

### Slice 6 — Lock Protocol Coverage

Goal:

- prove all in-scope same-note finance mutation paths use the shared locking protocol

Required checks:

1. payment path locks target note inside transaction
2. revision path locks target note inside transaction
3. refund path decision documented if in or out of first slice
4. no direct allocation write path bypasses protocol in approved scope
5. any legacy path is documented as gap or out of scope

## Implementation Order

The safest order:

1. Start with local baseline proof.
2. Read ADR-0022.
3. Read this blueprint.
4. Read error_log 010 and 026.
5. Inventory all same-note finance mutation paths.
6. Identify existing transaction boundaries.
7. Identify existing lock methods.
8. Pick one slice only.
9. Add red characterization test.
10. Run targeted test and confirm expected failure, if feasible.
11. Patch smallest shared boundary.
12. Run targeted test again.
13. Run relevant payment/revision/refund blast-radius tests.
14. Show diff.
15. Update docs/error_log only after proof.
16. Commit only after owner reviews diff and proof.
17. Move to next slice.

Recommended slice order:

1. Slice 1 payment/payment over-allocation
2. Slice 2 payment/revision lost allocation
3. Slice 4 payment atomicity
4. Slice 5 auto-close settlement consistency
5. Slice 3 partial payment race
6. Slice 6 lock protocol coverage
7. final docs/error_log update and handoff

Reason:

- direct over-allocation protects money first
- lost allocation protects revision/payment integrity
- atomicity supports both
- auto-close should follow committed settlement
- partial race and coverage check harden the boundary

## CLI Workflow

Rules:

1. Start every slice with git status.
2. Read relevant ADR, blueprint, and error logs before editing.
3. Read current source before writing tests.
4. Add red characterization test first when feasible.
5. Run targeted test and confirm expected failure.
6. Patch the smallest shared transaction boundary.
7. Run targeted test again.
8. Run relevant blast-radius tests.
9. Show diff.
10. Update error_log only with proof.
11. Commit only after owner approval.

## Required Commands For Execution Sessions

### Start Session Snapshot

    git status --short --untracked-files=all
    git rev-parse --abbrev-ref HEAD
    git rev-parse --short HEAD
    git log --oneline -5

### ADR-0022 Document Snapshot

    sed -n '1,260p' docs/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
    sed -n '1,320p' docs/blueprint/security/2026-05-06-payment-allocation-concurrency-and-over-allocation-blueprint.md

### Error Log Snapshot

    sed -n '1,260p' docs/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
    sed -n '1,260p' docs/error_log/026-concurrent-note-payments-can-over-allocate-balances.md

### Source Discovery

    grep -RIn "customer_payments\|payment_component_allocations\|payment_allocations\|deleteByNoteId\|lockForUpdate\|getByIdForUpdate\|DB::transaction\|closeIfEligible\|RecordAndAllocate\|PaymentAllocation\|NoteReplacementPaymentAllocationReconciler" app routes tests 2>/dev/null || true

### Route Discovery

    php artisan route:list | grep -Ei "payment|refund|workspace|revision|note" || true

### Test Pattern

Targeted tests first:

    php artisan test --filter=TargetedTestName

Potential blast-radius tests after exact files are known:

    php artisan test tests/Feature/Payment
    php artisan test tests/Feature/Note
    php artisan test tests/Feature/Cashier
    php artisan test tests/Feature/Admin

Run only the relevant blast-radius suites for the slice.

### Final Diff Snapshot

    git status --short --untracked-files=all
    git diff --stat
    git diff -- docs/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md docs/blueprint/security/2026-05-06-payment-allocation-concurrency-and-over-allocation-blueprint.md
    git diff -- app routes tests docs/error_log

## DoD For Planning

Planning is complete only when:

- ADR-0022 exists
- ADR-0022 owner decisions are captured
- error_log 010 and 026 are mapped
- same-note serialization rule is defined
- lock target is defined
- atomic payment/allocation rule is defined
- post-lock recalculation rule is defined
- allocation invariant is defined
- idempotency is explicitly phase 2 or existing-support-only
- DB constraints are explicitly deferred until invariant/schema proof
- UI prevention is rejected as concurrency control
- test matrix is defined
- implementation order is defined
- CLI workflow is defined
- stop conditions are defined
- ADR-0018 finance semantics are not changed
- ADR-0019 access boundary is not changed
- ADR-0020 public surface policy is not changed
- no app source patch is made during planning

## DoD For Implementation

Implementation is complete only when all relevant conditions for the selected slice are proven.

### Source Boundary

- same-note finance mutation uses shared locking protocol
- target note row is locked inside DB transaction
- payment and allocation write are atomic
- outstanding/payable state is recalculated after lock
- stale UI/request values are not final write authority
- over-allocation is rejected
- payment row cannot commit without required allocation
- allocation failure rolls back payment write
- second same-note payment request fails safely when outstanding changed
- no silent amount adjustment happens
- revision/payment interleaving cannot lose committed allocation in approved scope
- auto-close reads settlement consistent with committed rows

### Tests

- red characterization test exists before patch when feasible
- targeted test fails before patch for expected reason when feasible
- targeted test passes after patch
- relevant payment/revision/refund blast-radius tests pass
- no unrelated tests are weakened
- no test is changed merely to hide a failure
- concurrency verification gap is documented if true concurrent test is not feasible
- payment atomicity is tested or explicitly documented as gap
- source coverage confirms no approved competing path bypasses lock protocol

### Documentation

- docs/error_log 010 or 026 is updated only after proof
- proof quality is stated explicitly
- verification gap remains visible if concurrency proof is incomplete
- ADR is not rewritten casually during implementation
- any deviation from ADR-0022 is recorded with reason
- any deviation from this blueprint is recorded with reason

### Git

- git status is checked before and after
- diff contains only files in approved slice
- commit message references narrow fix
- owner reviews proof before commit
- no untracked unexpected file is left unreviewed

## ADR-0022 Blast-Radius Suite

After all ADR-0022 slices are complete, run the narrowest available blast-radius suite that covers:

- payment recording
- payment allocation
- payment component allocation
- note revision
- note replacement allocation replay
- refund if affected
- auto-close after payment
- outstanding resolver if affected
- paid status policy if affected

Suggested final proof should include:

- targeted tests for 010 and 026
- relevant payment suite
- relevant note revision suite
- relevant refund suite if touched
- final git diff stat
- final docs/error_log updates
- owner acceptance

## Error Log Update Rule

Do not update `docs/error_log/*.md` before implementation proof.

When updating error_log 010 or 026, include:

- status
- exact patch scope
- lock protocol used
- tests added
- targeted command output
- blast-radius command output
- residual verification gaps
- whether true concurrency was tested
- commit hash after commit, if committed
- owner acceptance note if applicable

Allowed statuses:

- Reported
- Accepted risk
- Planned
- Patched with verification gap
- Fixed with proof
- Deferred with owner acceptance

Forbidden behavior:

- marking fixed because a lock exists
- hiding lack of true concurrency proof
- claiming no race without testing or complete lock coverage
- deleting known gap without evidence

## Stop Conditions

Stop immediately if any of these happen:

- source code contradicts ADR-0022 owner decisions
- patch calculates final outstanding before acquiring lock
- patch writes payment outside same transaction as allocation
- patch leaves approved competing path unlocked
- patch uses UI/JS prevention as concurrency control
- patch changes ADR-0018 finance semantics
- patch changes ADR-0019 access policy
- patch changes ADR-0020 public surface behavior
- patch introduces idempotency storage without decision
- patch adds DB aggregate constraint without invariant/schema proof
- patch swallows allocation failure after payment commit
- patch silently adjusts requested payment amount
- patch makes note paid/closed state inconsistent with allocation
- failing test reason is not understood
- broad refactor is needed before exact affected files are proven
- error_log update is attempted before proof

## Handoff Rule

If session context becomes risky or implementation is paused, create handoff with:

- active ADR and blueprint
- selected slice
- owner decisions
- files changed
- tests added
- command proof
- failing tests
- residual gaps
- stop conditions triggered, if any
- safest next step
- exact opening prompt for next session

## Recommended Execution Sequence After Planning

After ADR-0022 and this blueprint are accepted, next implementation should start with:

1. local baseline proof
2. read ADR-0022
3. read this blueprint
4. read error_log 010 and 026
5. source inventory for payment/revision/allocation mutation paths
6. select Slice 1 payment/payment over-allocation
7. create characterization test
8. confirm red or document why true red is not feasible
9. patch shared transaction/lock boundary
10. confirm green
11. run payment blast-radius tests
12. update error_log only after proof

Do not begin with DB constraints or idempotency storage unless owner explicitly changes the implementation direction.

## Final Rule

Payment allocation concurrency is a production money-integrity boundary.

For HyperPOS, same-note finance mutation correctness must be enforced by transaction, lock protocol, atomic writes, and post-lock invariant validation.

If the system cannot prove same-note finance mutation serializes safely, it must not claim the concurrency finding is fixed.
