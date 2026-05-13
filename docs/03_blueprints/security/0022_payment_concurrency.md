# Payment Allocation Concurrency and Over-Allocation Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any `docs/04_lifecycle/error_log/*.md` finding as fixed.

This document exists to make ADR-0022 execution rigid enough for CLI-based implementation later.

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

## Source Of Truth

- docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
- docs/04_lifecycle/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
- docs/04_lifecycle/error_log/026-concurrent-note-payments-can-over-allocate-balances.md
- docs/05_audits/codex_security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/02_architecture/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/02_architecture/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/02_architecture/adr/0020-public-surface-output-storage-attachment-security.md
- docs/03_blueprints/security/adr-0019-access-boundary.md
- docs/03_blueprints/security/adr-0020-public-surface.md
- User owner decisions in planning session
- User command output from local repository
- Current source code at execution time

## Proof Available Before This Blueprint

Local proof from user command output:

    git status --short

showed:

    ?? docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md

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


---

## Related Documents

- DoD: docs/03_blueprints/security/adr-0022-payment-concurrency-dod.md
- Workflow: docs/03_blueprints/security/adr-0022-payment-concurrency-workflow.md
