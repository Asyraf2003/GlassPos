# Finance Residual Error Log Definition of Done
## Status
Canonical DoD.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/finance/finance-residual.md`


## DoD For Planning

Planning is complete only when:

- finance residual blueprint exists
- covered error logs are mapped
- locked ADR-0018 and carry-forward decisions are listed
- open decision gates are explicit
- preferred implementation slices are defined
- slice order is defined
- source inventory requirements are defined
- discovery commands are defined
- characterization test rules are defined
- CLI workflow is defined
- implementation DoD is defined
- stop conditions are defined
- ADR-0019 access scope is not redefined
- ADR-0022 concurrency scope is not redefined
- no app source patch is made during planning

## DoD For Implementation

Implementation is complete only when all relevant conditions for the selected slice are proven.

### Source Boundary

- finance logic lives in application/domain/service boundary, not UI-only
- payment/refund/history rows are not destroyed
- current and historical rows are separated by policy/projection
- current payment selection excludes canceled/legacy rows
- current refund selection excludes canceled/legacy/unpaid rows
- carry-forward settlement does not double-subtract refund
- existing money is not silently lost during revision
- overpaid/surplus is not treated as unpaid
- price basis is server-authoritative
- client price basis cannot bypass minimum price
- refund endpoint cannot act as generic row delete
- paid status/outstanding/operational status agree
- report/current projection does not double-count legacy rows when affected

### Tests

- red characterization test exists before patch
- targeted test fails before patch for expected reason
- targeted test passes after patch
- relevant payment/refund/revision/reporting tests pass
- no unrelated tests are weakened
- no test is changed merely to hide a failure
- exact money examples are covered where relevant
- surplus behavior is covered as detection if storage decision remains open
- verification gap is documented when full final model needs owner decision

### Documentation

- docs/error_log finding is updated only after proof
- proof quality is stated explicitly
- ADR is not rewritten casually during implementation
- any new domain decision gets ADR/addendum
- any deviation from this blueprint is recorded with reason
- residual gaps remain visible

### Git

- git status is checked before and after
- diff contains only files in approved slice
- commit message references narrow fix
- owner reviews proof before commit
- no untracked unexpected file is left unreviewed

## Finance Blast-Radius Suite

After finance residual slices are complete, run the narrowest available suite covering:

- payment allocation
- refund allocation
- note revision
- workspace edit
- paid status
- outstanding resolver
- operational status
- current projection
- reporting if touched
- cashier/admin affected flows
- inventory if touched

Suggested final proof should include:

- targeted tests per error_log
- relevant payment suite
- relevant note suite
- relevant refund suite
- relevant reporting suite if touched
- make/audit command if project rules require it
- final git diff stat
- final docs/error_log updates
- owner acceptance

## Error Log Update Rule

Do not update `docs/04_lifecycle/error_log/*.md` before implementation proof.

When updating a finance error log, include:

- status
- exact patch scope
- tests added
- targeted command output
- blast-radius command output
- residual gaps
- owner decision reference if applicable
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

- marking fixed because a patch exists
- hiding missing finance proof
- claiming domain decision not recorded
- changing settlement semantics without ADR/update
- deleting known gap without evidence

## Stop Conditions

Stop immediately if:

- patch changes refund engine without fresh proof and owner approval
- patch blocks cashier edit/refund globally as final solution
- patch rewrites/deletes payment/refund/history
- patch mixes current and historical rows without boundary
- patch encodes generic reader semantics before consumer behavior is proven
- patch silently drops existing note money
- patch treats carry-forward surplus as unpaid
- patch silently adjusts money without owner decision
- patch trusts client-controlled price basis
- patch lets open/unpaid row use money refund path
- patch lets forged row id refund/cancel/finalize a note
- patch changes ADR-0019 access policy without opening that scope
- patch changes ADR-0022 concurrency policy without opening that scope
- full surplus storage is needed but owner decision is still missing
- current projection schema change is needed but schema decision is not documented
- failing test reason is not understood
- broad refactor is needed before exact affected files are proven
- error_log update is attempted before proof

---

## Related Documents

- Blueprint: docs/03_blueprints/finance/finance-residual.md
- Workflow: docs/03_blueprints/finance/finance-residual-workflow.md
