# Security ADR-0022 Payment Concurrency Workflow
## Status
Canonical Workflow.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0022-payment-concurrency.md`


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

    sed -n '1,260p' docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
    sed -n '1,260p' docs/04_lifecycle/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
    sed -n '1,260p' docs/04_lifecycle/error_log/026-concurrent-note-payments-can-over-allocate-balances.md

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

    sed -n '1,260p' docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
    sed -n '1,320p' docs/03_blueprints/security/adr-0022-payment-concurrency.md

### Error Log Snapshot

    sed -n '1,260p' docs/04_lifecycle/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
    sed -n '1,260p' docs/04_lifecycle/error_log/026-concurrent-note-payments-can-over-allocate-balances.md

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
    git diff -- docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md docs/03_blueprints/security/adr-0022-payment-concurrency.md
    git diff -- app routes tests docs/error_log

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

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0022-payment-concurrency.md
- DoD: docs/03_blueprints/security/adr-0022-payment-concurrency-dod.md
