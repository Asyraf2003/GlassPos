# Security ADR-0023 Seeder Safety Definition of Done
## Status
Canonical DoD.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0023-seeder-safety.md`


## DoD For Planning

Planning is complete only when:

- ADR-0023 exists
- ADR-0023 owner decisions are captured
- error_log 002 is mapped
- predictable credential boundary is defined
- production-like environment default is defined
- bootstrap admin rule is defined
- local/testing convenience boundary is defined
- seed level direction is defined
- no silent fallback rule is defined
- generated secret policy is deferred or defined
- immediate safety direction is defined
- future seeder structure direction is defined
- test matrix is defined
- implementation order is defined
- CLI workflow is defined
- stop conditions are defined
- ADR-0019 access model is not changed
- ADR-0020 public surface policy is not changed
- ADR-0022 concurrency policy is not changed
- no app source patch is made during planning

## DoD For Implementation

Implementation is complete only when all relevant conditions for the selected slice are proven.

### Source Boundary

- predictable privileged credentials are blocked outside local/testing
- unknown environment defaults to production-like
- local/testing seed workflow remains available if kept
- production-like bootstrap fails closed when secure credential input is missing
- no local fallback password is used outside local/testing
- no privileged hardcoded password is production-capable
- partial privileged user creation does not remain after failure
- seed behavior is explicit in code and docs
- local credential seed is separate or guarded
- scenario/load seed does not run in production-like baseline path

### Tests

- characterization test exists before patch when feasible
- production-like predictable credential test fails before patch when feasible
- production-like predictable credential test passes after patch
- local/testing credential allowance test passes if local workflow is kept
- missing credential input fails closed
- relevant seeder/auth tests pass
- no unrelated tests are weakened
- no test is changed merely to hide a failure
- verification gap is documented if environment simulation is incomplete

### Documentation

- docs/error_log 002 is updated only after proof
- proof quality is stated explicitly
- runbook/handoff documents safe seeder behavior if changed
- ADR is not rewritten casually during implementation
- any deviation from ADR-0023 is recorded with reason
- any deviation from this blueprint is recorded with reason

### Git

- git status is checked before and after
- diff contains only files in approved slice
- commit message references narrow fix
- owner reviews proof before commit
- no untracked unexpected file is left unreviewed

## ADR-0023 Blast-Radius Suite

After ADR-0023 implementation is complete, run the narrowest available blast-radius suite that covers:

- seeder behavior
- auth/user creation
- identity access if affected
- tests depending on seeded users
- local/testing seed workflow
- production-like guard behavior

Suggested final proof should include:

- targeted seeder credential tests
- relevant auth/identity tests if touched
- final git diff stat
- final docs/error_log update
- owner acceptance

## Error Log Update Rule

Do not update `docs/04_lifecycle/error_log/*.md` before implementation proof.

When updating error_log 002, include:

- status
- exact seeders changed
- environment behavior
- credential source policy
- tests added
- targeted command output
- blast-radius command output
- residual gaps
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

- marking fixed because a guard exists
- hiding untested production-like behavior
- claiming production-safe without environment test
- deleting known gap without evidence

## Stop Conditions

Stop immediately if any of these happen:

- source code contradicts ADR-0023 owner decisions
- patch removes local/testing seed workflow without owner decision
- patch allows hardcoded privileged password outside local/testing
- patch silently falls back to predictable password
- patch creates production-like admin without explicit secure credential source
- patch logs generated production-like secret without policy
- patch changes ADR-0019 access model
- patch changes ADR-0020 public surface behavior
- patch changes ADR-0022 concurrency behavior
- patch changes auth/password reset product flow
- patch requires broad auth refactor before exact seeder risk is proven
- source inventory finds multiple credential seeders and only one is patched without documenting residual gap
- failing test reason is not understood
- error_log update is attempted before proof

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0023-seeder-safety.md
- Workflow: docs/03_blueprints/security/adr-0023-seeder-safety-workflow.md
