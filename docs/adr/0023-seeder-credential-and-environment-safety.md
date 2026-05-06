# ADR-0023 — Seeder Credential and Environment Safety

## Status

Accepted for planning.

Implementation status: not implemented yet.

This ADR records owner-approved direction for seeder credential safety, environment boundary, and predictable credential prevention.

This ADR does not mark any `docs/error_log/*.md` finding as fixed.

A finding is fixed only after characterization test, implementation proof, relevant verification, owner review, and accepted diff.

## Context

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

Seeders are not harmless when they create users, credentials, roles, capabilities, or finance-sensitive baseline data.

Predictable credentials are acceptable only for local developer convenience when explicitly scoped to local/testing environments.

Predictable credentials are not acceptable in production-like environments.

A seeder that works safely in local can become a security issue if it can run in staging, production, shared demo, preview, or any owner/customer-visible environment with known credentials.

## Related Error Log

ADR-0023 covers:

- `docs/error_log/002-seeder-introduces-predictable-admin-credentials.md`

## Problem Statement

The system may contain seeders that create admin or privileged accounts with predictable credentials.

This creates risk if the seeder runs outside local/testing environments.

Risks include:

1. predictable admin credential in production-like environment
2. accidental privileged account creation
3. credential reuse across machines or deployments
4. unclear distinction between development seed data and production baseline data
5. operational handoff ambiguity
6. future engineer running seed command without understanding credential impact

The core problem is not merely a weak password string.

The core problem is missing seeder environment boundary and seed level policy.

## Decision

Seeder credential behavior must be environment-aware.

Predictable credentials are allowed only in local/testing environments.

Production-like environments must not create predictable privileged credentials.

If a production-like environment needs bootstrap admin creation, credential input must come from an explicit secure source, such as environment-provided secret, or a future approved bootstrap mechanism.

No seeder may silently create a privileged production-capable account with a known password.

## Owner Decisions Locked

### 1. Predictable Credential Boundary

Predictable credentials are allowed only for local/testing developer workflow.

Allowed only when environment is explicitly local or testing:

- known local admin email
- known local admin password
- demo-like fake users
- test-only cashier/admin accounts
- scenario users

Forbidden outside local/testing:

- default admin password
- default cashier password
- shared known password
- hardcoded privileged password
- auto-created admin without explicit secure credential source

### 2. Production-Like Environment Default

Any environment that is not explicitly local or testing is treated as production-like by default.

Production-like includes:

- production
- staging
- preview
- demo
- shared QA
- owner-visible test server
- any environment where real or semi-real operational data may exist
- any environment where unaudited credential exposure could harm operations

If environment classification is unclear, default is production-like.

### 3. Bootstrap Admin Rule

Production-like bootstrap admin creation is allowed only when all conditions are true:

1. owner or operator intentionally runs the bootstrap path
2. email/identity comes from explicit environment/config input
3. password or bootstrap secret comes from explicit environment/config input or approved secure generation flow
4. credential is not hardcoded in repo
5. command fails closed when required credential input is missing
6. behavior is documented in runbook/handoff

No fallback predictable password is allowed.

### 4. Local Developer Convenience

Local/testing seeders may remain convenient.

Allowed local/testing behavior:

- predictable local accounts
- documented local credentials
- scenario accounts
- load/demo fake users

Required local/testing boundary:

- code must prove environment is local/testing before creating predictable privileged credentials
- local/test credentials must not be used as fallback in production-like environments
- seeders must make the boundary obvious in code and docs

### 5. Seed Level Direction

Final seeder structure should separate seed intent.

Recommended seed levels:

1. base seed
2. domain seed
3. local credential seed
4. scenario seed
5. load/test seed
6. production bootstrap seed or command

Definitions:

- base seed: safe reference data needed by app; no privileged credentials
- domain seed: safe domain baseline; no production-capable predictable credentials
- local credential seed: local/testing only users
- scenario seed: local/testing only demo/scenario data
- load/test seed: testing/performance only
- production bootstrap: explicit operator-controlled credential creation

First implementation may add guards before full seed level refactor.

Full seeder restructuring can be phased after safety guard is in place.

### 6. No Silent Fallback

If production-like bootstrap requires a password and the required env/config value is missing, the command must fail.

Forbidden:

- fallback to `password`
- fallback to `admin`
- fallback to `admin123`
- fallback to local documented password
- generating credential and writing it to normal logs without policy
- silently skipping security warning while still creating account

### 7. Generated Secret Policy

Random generated secret may be considered only if the implementation defines:

- where the secret is displayed
- who can see it
- whether it is logged
- how it is stored
- how it is rotated
- how handoff is performed
- how accidental log leak is prevented

Until such policy exists, production-like bootstrap should prefer explicit environment/config secret.

### 8. Existing Seeder Compatibility

Existing seeders may remain temporarily as legacy compatibility only if guarded.

Legacy seeders must not be treated as final production-safe behavior.

Any legacy predictable credential path must be either:

- blocked outside local/testing
- moved to local-only seed
- replaced by explicit bootstrap credential flow

### 9. Error Log Fixed Rule

Error log `002` is not fixed merely because a guard or documentation exists.

Final fixed requires:

- red characterization test or equivalent environment-safety test
- proof predictable privileged credential cannot be created outside local/testing
- proof local/testing convenience still works if kept
- docs updated with exact behavior
- owner accepts proof

## Scope In

ADR-0023 applies to:

- database seeders
- admin/cashier/user seeders
- role/capability seeders when tied to privileged accounts
- local/test scenario account creation
- production-like bootstrap account creation
- default password prevention
- environment detection for credential seed behavior
- seeder documentation and runbook/handoff updates
- tests proving environment-specific behavior

## Scope Out

ADR-0023 does not decide:

- ADR-0019 access/capability policy
- ADR-0020 output/storage/attachment security
- ADR-0022 payment concurrency
- finance settlement
- refund/revision lifecycle
- password reset full product flow
- external identity provider
- owner/super-admin role introduction
- credential manager choice
- production secret rotation schedule
- full disaster recovery process

## Environment Classification

Default classification:

| Environment | Classification | Predictable Privileged Credential Seeder |
|---|---|---|
| local | local | allowed if documented |
| testing | testing | allowed if test-scoped |
| production | production-like | forbidden |
| staging | production-like | forbidden |
| preview | production-like | forbidden |
| demo | production-like unless explicitly local-only | forbidden by default |
| shared QA | production-like | forbidden |
| unknown | production-like | forbidden |

If environment name is custom and not explicitly local/testing, treat it as production-like.

## Approved Immediate Safety Direction

First implementation should prioritize safety guard over full refactor.

Minimum immediate direction:

1. identify seeders creating privileged users
2. identify hardcoded passwords
3. add environment guard
4. allow predictable credentials only in local/testing
5. fail closed outside local/testing unless explicit secure credential source exists
6. add tests for local/testing allowed behavior
7. add tests for production-like blocked behavior
8. update error_log only after proof

## Future Seeder Structure Direction

After immediate safety guard, the project should move toward explicit seed levels:

- `BaseSeeder`
- `DomainSeeder`
- `LocalCredentialSeeder`
- `ScenarioSeeder`
- `LoadTestSeeder`
- `ProductionBootstrapAdminCommand` or equivalent

Exact class names must be decided after source inventory.

This ADR approves the direction, not the exact class names.

## Implementation Options Considered

### Option A — Block Predictable Credentials Outside Local/Testing

Description:

Keep local predictable credentials but block them in production-like environments.

Pros:

- fast
- preserves developer workflow
- directly prevents production-like predictable credential risk
- simple to test

Cons:

- legacy local credentials remain
- environment classification must be correct
- may not clean seed structure yet

Decision:

Approved as immediate safety baseline.

### Option B — Require Env-Provided Credential Outside Local/Testing

Description:

Production-like bootstrap admin creation requires explicit env/config credential.

Pros:

- production-safe
- operator-controlled
- avoids hardcoded secrets
- easy to fail closed

Cons:

- requires runbook
- missing env may block bootstrap
- env secret handling must be disciplined

Decision:

Approved for production-like bootstrap path.

### Option C — Generate Random One-Time Credential

Description:

Seeder generates a random credential when creating admin.

Pros:

- not predictable
- useful for bootstrap

Cons:

- secret display/logging policy required
- accidental log leakage risk
- handoff complexity
- rotation requirement unclear

Decision:

Deferred until secure generation/display policy exists.

### Option D — Full Seeder Level Refactor

Description:

Split seeders by base/domain/local/scenario/load/bootstrap purpose.

Pros:

- clean long-term structure
- audit-friendly
- prevents mixed production/test seed behavior
- easier handoff

Cons:

- larger refactor
- requires source inventory
- may affect developer commands

Decision:

Approved as final direction, not required for first safety patch.

### Option E — Remove All Seeded Users

Description:

No seeded users in any environment.

Pros:

- very strict
- eliminates hardcoded local credentials

Cons:

- hurts local/testing workflow
- may make test setup noisy
- unnecessary if environment boundary is strong

Decision:

Rejected for now.

Local/testing convenience is allowed with strict boundary.

## Consequences

### Positive Consequences

- predictable privileged credentials cannot silently reach production-like environments
- local workflow remains usable
- seed behavior becomes auditable
- future seeder refactor gets clear direction
- error_log 002 can be fixed with proof instead of vibes, society's least reliable security tool

### Negative Consequences

- production-like bootstrap may fail until env/config is provided
- existing seed commands may need updates
- tests must simulate environment behavior
- docs/runbook must explain safe bootstrap
- some developer assumptions may need adjustment

### Accepted Tradeoff

The system chooses production-like credential safety over silent convenience.

Local convenience remains available only where it cannot become production-capable by accident.

## Required Verification Themes

Implementation proof must include:

- local/testing seed predictable credential allowed only where intended
- production-like seed predictable credential blocked
- missing production-like credential input fails closed
- no hardcoded privileged password used outside local/testing
- no fallback local password in production-like environment
- relevant seeder command/test passes
- docs/error_log updated only after proof

## Documentation Rule

`docs/error_log/002-seeder-introduces-predictable-admin-credentials.md` may be updated only after proof exists.

The update must include:

- exact seeders changed
- environment behavior
- test command output
- remaining gaps
- owner acceptance

If guard exists but production-like behavior is not tested, status must remain:

    Patched with verification gap

## Stop Conditions

Stop immediately if:

- patch removes local/testing seed workflow without owner decision
- patch allows hardcoded privileged password outside local/testing
- patch silently falls back to predictable password
- patch creates production-like admin without explicit secure credential source
- patch logs generated production-like secret without policy
- patch changes ADR-0019 access model
- patch changes password reset/auth product behavior
- test requires broad auth refactor before exact seeder risk is proven
- source inventory finds multiple credential seeders and only one is patched without documenting residual gap
- error_log update is attempted before proof

## Related Documents

- docs/error_log/002-seeder-introduces-predictable-admin-credentials.md
- docs/audit/codex-security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/adr/0020-public-surface-output-storage-attachment-security.md
- docs/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md

## Final Rule

Seeder credentials are a production safety boundary.

If a privileged credential is predictable, it must be impossible for that credential path to run in production-like environments.

If the environment is unclear, the seeder must deny the predictable credential path.
