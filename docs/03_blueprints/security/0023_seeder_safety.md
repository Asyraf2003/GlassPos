# Seeder Credential and Environment Safety Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any `docs/04_lifecycle/error_log/*.md` finding as fixed.

This document exists to make ADR-0023 execution rigid enough for CLI-based implementation later.

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

## Source Of Truth

- docs/02_architecture/adr/0023-seeder-credential-and-environment-safety.md
- docs/04_lifecycle/error_log/002-seeder-introduces-predictable-admin-credentials.md
- docs/05_audits/codex_security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/02_architecture/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/02_architecture/adr/0020-public-surface-output-storage-attachment-security.md
- docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
- User owner decisions in planning session
- User command output from local repository
- Current source code at execution time

## Proof Available Before This Blueprint

Local proof from user command output:

    grep -nE "^(#|## Status|## Context|## Problem Statement|## Decision|## Owner Decisions Locked|## Environment Classification|## Approved Immediate Safety Direction|## Future Seeder Structure Direction|## Required Verification Themes|## Stop Conditions|## Final Rule)" docs/02_architecture/adr/0023-seeder-credential-and-environment-safety.md

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


---

## Related Documents

- DoD: docs/03_blueprints/security/adr-0023-seeder-safety-dod.md
- Workflow: docs/03_blueprints/security/adr-0023-seeder-safety-workflow.md
