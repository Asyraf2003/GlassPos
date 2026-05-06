# Seeder Credential and Environment Safety Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any `docs/error_log/*.md` finding as fixed.

This document exists to make ADR-0023 execution rigid enough for CLI-based implementation later.

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

## Source Of Truth

- docs/adr/0023-seeder-credential-and-environment-safety.md
- docs/error_log/002-seeder-introduces-predictable-admin-credentials.md
- docs/audit/codex-security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/adr/0020-public-surface-output-storage-attachment-security.md
- docs/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
- User owner decisions in planning session
- User command output from local repository
- Current source code at execution time

## Proof Available Before This Blueprint

Local proof from user command output:

    grep -nE "^(#|## Status|## Context|## Problem Statement|## Decision|## Owner Decisions Locked|## Environment Classification|## Approved Immediate Safety Direction|## Future Seeder Structure Direction|## Required Verification Themes|## Stop Conditions|## Final Rule)" docs/adr/0023-seeder-credential-and-environment-safety.md

showed ADR-0023 has required planning sections.

Local proof also showed:

    forbidden reduced-scope wording check returned no output

returned no output.

Meaning:

- ADR-0023 exists locally.
- ADR-0023 has required decision sections.
- ADR-0023 does not use forbidden reduced-scope wording.
- Implementation has not started yet.
- Error log 002 is not fixed by this proof.

## Decision Boundary

ADR-0023 owns seeder credential and environment safety.

ADR-0019 owns access and capability policy.

ADR-0020 owns public surface, output, storage, attachment, and disclosure security.

ADR-0022 owns payment allocation concurrency and over-allocation protection.

ADR-0023 must not silently redefine authentication product behavior, access capability rules, public surface policy, or payment concurrency.

ADR-0023 only decides how seeders and bootstrap paths may create privileged credentials safely across environments.

## Explicit Scope

This blueprint covers:

- predictable privileged credential prevention
- local/testing credential allowance
- production-like credential denial
- environment classification for seeders
- bootstrap admin credential source
- local credential seeder boundary
- seeder level direction
- tests for environment-specific seeder behavior
- error_log 002 update rule
- future seeder refactor direction
- CLI workflow and DoD for seeder safety implementation

## Non Goals

Do not patch application source from this document alone.

Do not create or modify production code before source inventory and characterization tests.

Do not change ADR-0019 access/capability policy.

Do not change ADR-0020 public surface/security behavior.

Do not change ADR-0022 payment concurrency behavior.

Do not redesign authentication product flow.

Do not introduce owner/super-admin role.

Do not decide external identity provider integration.

Do not decide password reset full product behavior.

Do not decide credential manager policy.

Do not decide production secret rotation schedule.

Do not refactor every seeder before the immediate credential safety risk is guarded.

Do not mark error_log 002 fixed without proof.

## Owner Decisions Locked

### Predictable Credential Boundary

Predictable privileged credentials are allowed only in explicitly local or testing environments.

Forbidden outside local/testing:

- hardcoded admin password
- hardcoded cashier password
- shared known privileged password
- local documented password fallback
- default privileged password
- silent admin creation without explicit secure credential source

### Production-Like Default

Any environment not explicitly local or testing is production-like by default.

Production-like includes:

- production
- staging
- preview
- demo
- shared QA
- owner-visible test server
- unknown environment
- any environment with real or semi-real operational data

If uncertain, classify as production-like.

### Bootstrap Admin Rule

Production-like bootstrap admin creation requires explicit secure credential source.

Required:

- explicit operator intent
- explicit email or identity input
- explicit password/secret input from env/config or future approved bootstrap mechanism
- no hardcoded credential
- fail closed if required input is missing
- documented behavior

### Local Developer Convenience

Local/testing seeders may stay convenient.

Allowed:

- predictable local accounts
- test-only users
- local scenario users
- load/test users

Required:

- environment guard proves local/testing before creating predictable privileged credentials
- no local fallback outside local/testing

### Seed Level Direction

Final seeder structure should move toward seed levels:

1. base seed
2. domain seed
3. local credential seed
4. scenario seed
5. load/test seed
6. production bootstrap seed or command

First implementation may guard current seeders before full refactor.

### No Silent Fallback

Production-like path must never fallback to local predictable password.

If required credential input is missing, fail closed.

### Generated Secret Policy

Random generated production-like secret is deferred until secure display/logging/handoff policy exists.

### Existing Seeder Compatibility

Existing seeders may remain temporarily as legacy compatibility only if predictable privileged credential creation is blocked outside local/testing.

## Error Log Coverage

| Error Log | Covered By ADR-0023 | Coverage Notes |
|---|---|---|
| 002 seeder introduces predictable admin credentials | Yes | Environment-specific guard and bootstrap credential policy |

## Risk Model

### Risk 1 — Predictable Production-Like Admin

Bad flow:

1. Seeder creates admin account.
2. Password is hardcoded or documented.
3. Seeder runs in staging/production-like environment.
4. Anyone with knowledge of default credential can log in.

Required prevention:

- predictable privileged credential path blocked outside local/testing
- production-like bootstrap requires explicit secure credential source

### Risk 2 — Unknown Environment Treated As Safe

Bad flow:

1. Environment is `demo`, `preview`, `staging`, or custom.
2. Seeder only blocks exact `production`.
3. Predictable credential still gets created.

Required prevention:

- allow-list local/testing
- all other environments production-like by default

### Risk 3 — Silent Password Fallback

Bad flow:

1. Production-like bootstrap expects env password.
2. Env password missing.
3. Seeder falls back to local default password.
4. Privileged account exists with predictable password.

Required prevention:

- fail closed when secure credential input is missing
- no fallback to known password

### Risk 4 — Seeder Levels Mixed Together

Bad flow:

1. Base/domain data and local users live in one seeder.
2. Operator runs seed command for safe baseline.
3. Local test user appears in production-like data.

Required prevention:

- document seed levels
- split or guard local credential seed
- make production-safe seed path explicit

### Risk 5 — Generated Secret Leaks

Bad flow:

1. Seeder generates random password.
2. Password is printed into normal logs.
3. Logs are retained/shared.
4. Secret leaks.

Required prevention:

- defer generated secret flow until display/logging/handoff policy exists
- prefer explicit env/config secret for first production-like bootstrap

## Source Inventory Requirements

Before implementation, identify:

- all seeders under `database/seeders`
- factories that create users or credentials
- code that creates admin/kasir/users
- code that assigns roles/capabilities
- hardcoded email/password strings
- env checks inside seeders
- artisan commands related to seed/bootstrap
- tests that depend on seeded credentials
- docs/handoff/runbook that mention local credentials
- CI/test setup that runs seeders

## Suggested Discovery Commands

Run before implementation:

    grep -RIn "password\|admin\|kasir\|cashier\|User::\|create(\|factory\|role\|capability\|APP_ENV\|environment()" database app config tests docs 2>/dev/null || true

Run seeder file list:

    find database/seeders -type f -maxdepth 2 | sort

Run artisan seed references:

    grep -RIn "db:seed\|DatabaseSeeder\|Seeder" Makefile composer.json package.json docs app tests database 2>/dev/null || true

Run document snapshot:

    sed -n '1,260p' docs/adr/0023-seeder-credential-and-environment-safety.md
    sed -n '1,260p' docs/error_log/002-seeder-introduces-predictable-admin-credentials.md

## Characterization Test Matrix

Tests must be written before production patch when feasible.

### Slice 1 — Production-Like Predictable Credential Block

Goal:

- prove predictable privileged credential seeding is blocked outside local/testing

Required tests:

1. production-like environment does not create predictable admin credential
2. staging-like environment does not create predictable admin credential
3. unknown environment does not create predictable admin credential
4. missing secure credential input fails closed
5. failure does not leave partially-created privileged user

Expected:

- command/test fails safely or skips local credential seed with explicit behavior
- no predictable privileged account exists

### Slice 2 — Local/Testing Credential Allowance

Goal:

- prove local/testing developer workflow remains usable

Required tests:

1. local environment can create documented local admin if intended
2. testing environment can create test-scoped user if intended
3. local/test predictable credential does not run through production-like path
4. local/test seed remains explicit and auditable

Expected:

- local/testing convenience works
- environment guard is visible and tested

### Slice 3 — Env-Provided Bootstrap Credential

Goal:

- prove production-like bootstrap works only with explicit secure input

Required tests:

1. production-like bootstrap with env password succeeds if approved path exists
2. production-like bootstrap without env password fails closed
3. password is not hardcoded
4. local fallback password is not used
5. docs explain required env/config keys

If production-like bootstrap command does not exist yet:

- document as deferred
- ensure current seeder does not create predictable privileged account outside local/testing

### Slice 4 — Seeder Level Boundary

Goal:

- prove local credential seed is separate or guarded from base/domain seed

Required tests/checks:

1. production-safe seed path does not call local credential seed
2. local credential seed is explicitly local/testing only
3. scenario/load seed is not part of production-safe baseline
4. docs/runbook distinguish safe seed commands

This slice may be phased after immediate safety guard.

### Slice 5 — Documentation And Handoff

Goal:

- prove operator and future engineer know how seeder behavior works

Required checks:

1. docs/error_log 002 updated only after proof
2. runbook/handoff mentions local/testing credential boundary
3. production-like bootstrap requirements documented if implemented
4. residual gaps documented

## Implementation Order

The safest order:

1. Start with local baseline proof.
2. Read ADR-0023.
3. Read this blueprint.
4. Read error_log 002.
5. Inventory all seeders and credential creation paths.
6. Pick Slice 1 production-like predictable credential block.
7. Add characterization test for production-like env.
8. Confirm red failure or document why red cannot be produced.
9. Patch smallest environment guard.
10. Run targeted test again.
11. Add local/testing allowance test if local workflow is kept.
12. Run relevant seeder/auth tests.
13. Show diff.
14. Update docs/error_log only after proof.
15. Commit only after owner reviews diff and proof.
16. Move to future seed-level refactor only after immediate safety proof.

Recommended slice order:

1. Slice 1 production-like predictable credential block
2. Slice 2 local/testing credential allowance
3. Slice 3 env-provided bootstrap credential if needed
4. Slice 5 documentation and handoff
5. Slice 4 seeder level boundary/refactor as later hardening

Reason:

- block production-like risk first
- preserve local workflow second
- document bootstrap only if implemented
- seed-level refactor can wait until the dangerous credential path is guarded

## CLI Workflow

Rules:

1. Start every slice with git status.
2. Read ADR-0023, blueprint, and error_log 002 before editing.
3. Inventory current seeders before writing tests.
4. Add characterization test first when feasible.
5. Run targeted test and confirm expected failure.
6. Patch smallest safe guard.
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

### ADR-0023 Document Snapshot

    sed -n '1,260p' docs/adr/0023-seeder-credential-and-environment-safety.md
    sed -n '1,320p' docs/blueprint/security/2026-05-06-seeder-credential-and-environment-safety-blueprint.md

### Error Log Snapshot

    sed -n '1,260p' docs/error_log/002-seeder-introduces-predictable-admin-credentials.md

### Source Discovery

    grep -RIn "password\|admin\|kasir\|cashier\|User::\|create(\|factory\|role\|capability\|APP_ENV\|environment()" database app config tests docs 2>/dev/null || true

### Seeder File Discovery

    find database/seeders -type f -maxdepth 2 | sort

### Seed Command Reference Discovery

    grep -RIn "db:seed\|DatabaseSeeder\|Seeder" Makefile composer.json package.json docs app tests database 2>/dev/null || true

### Test Pattern

Targeted tests first:

    php artisan test --filter=TargetedTestName

Potential blast-radius tests after exact files are known:

    php artisan test tests/Feature/Auth
    php artisan test tests/Feature/IdentityAccess
    php artisan test tests/Unit
    php artisan test tests/Feature

Run only relevant suites for the slice.

### Final Diff Snapshot

    git status --short --untracked-files=all
    git diff --stat
    git diff -- docs/adr/0023-seeder-credential-and-environment-safety.md docs/blueprint/security/2026-05-06-seeder-credential-and-environment-safety-blueprint.md
    git diff -- database app config tests docs/error_log docs

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

Do not update `docs/error_log/*.md` before implementation proof.

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

After ADR-0023 and this blueprint are accepted, next implementation should start with:

1. local baseline proof
2. read ADR-0023
3. read this blueprint
4. read error_log 002
5. inventory seeders and credential creation paths
6. create production-like predictable credential characterization test
7. confirm red or document why red cannot be produced
8. patch local/testing environment guard
9. confirm green
10. run relevant seeder/auth tests
11. update error_log only after proof

Do not start with full seeder refactor before immediate predictable credential risk is guarded.

## Final Rule

Seeder credential safety is a production boundary.

If a privileged credential is predictable, it must be impossible for that credential path to run in production-like environments.

If the environment is unclear, the seeder must deny the predictable credential path.
