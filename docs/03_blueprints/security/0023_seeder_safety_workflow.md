# Security ADR-0023 Seeder Safety Workflow
## Status
Canonical Workflow.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0023-seeder-safety.md`


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
- docs/99_archive/handoff/runbook that mention local credentials
- CI/test setup that runs seeders

## Suggested Discovery Commands

Run before implementation:

    grep -RIn "password\|admin\|kasir\|cashier\|User::\|create(\|factory\|role\|capability\|APP_ENV\|environment()" database app config tests docs 2>/dev/null || true

Run seeder file list:

    find database/seeders -type f -maxdepth 2 | sort

Run artisan seed references:

    grep -RIn "db:seed\|DatabaseSeeder\|Seeder" Makefile composer.json package.json docs app tests database 2>/dev/null || true

Run document snapshot:

    sed -n '1,260p' docs/02_architecture/adr/0023-seeder-credential-and-environment-safety.md
    sed -n '1,260p' docs/04_lifecycle/error_log/002-seeder-introduces-predictable-admin-credentials.md

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

    sed -n '1,260p' docs/02_architecture/adr/0023-seeder-credential-and-environment-safety.md
    sed -n '1,320p' docs/03_blueprints/security/adr-0023-seeder-safety.md

### Error Log Snapshot

    sed -n '1,260p' docs/04_lifecycle/error_log/002-seeder-introduces-predictable-admin-credentials.md

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
    git diff -- docs/02_architecture/adr/0023-seeder-credential-and-environment-safety.md docs/03_blueprints/security/adr-0023-seeder-safety.md
    git diff -- database app config tests docs/error_log docs

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

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0023-seeder-safety.md
- DoD: docs/03_blueprints/security/adr-0023-seeder-safety-dod.md
