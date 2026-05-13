# Security ADR-0022 Payment Concurrency Definition of Done
## Status
Canonical DoD.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0022-payment-concurrency.md`


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

Do not update `docs/04_lifecycle/error_log/*.md` before implementation proof.

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

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0022-payment-concurrency.md
- Workflow: docs/03_blueprints/security/adr-0022-payment-concurrency-workflow.md
