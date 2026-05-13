# Seeder Legacy to Clean Workflow

## Status

Active workflow.

This workflow controls any future work under `database/seeders`.

This document does not change runtime behavior.

## Purpose

The seeder area is currently classified as legacy compatibility surface.

Future work must migrate it toward a clean, deterministic, auditable seeder system without breaking existing make-level workflows.

## Required Reading Order

Before editing any seeder file, read:

1. database/seeders/README.md
2. docs/03-blueprints/seeder/2026-05-11-legacy-to-clean-seeder-blueprint.md
3. docs/03-blueprints/seeder/2026-05-11-legacy-seeder-manifest.md
4. docs/02-architecture/adr/0023-seeder-credential-and-environment-safety.md
5. docs/03-blueprints/security/adr-0023-seeder-safety.md
6. docs/99-archive/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md
7. docs/99-archive/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md

## Workflow Rule

Only one seeder slice may be active at a time.

A slice must declare:

- target file or domain
- current source behavior
- risk level
- intended clean target
- proof required
- scope-out
- rollback or stop condition

## Allowed Seeder Work Types

### 1. Documentation-only marker

Allowed when:

- no runtime behavior changes
- file or folder marker improves discoverability
- source of truth remains linked

Required proof:

- created/updated document path
- grep anchors
- git status

### 2. Source inspection

Allowed when:

- inspecting behavior before patch
- updating manifest with proven source behavior
- not changing runtime code

Required proof:

- exact files inspected
- discovered call graph or behavior
- updated manifest anchors
- git status

### 3. Credential safety patch

Allowed when:

- source inspection proves credential risk
- ADR-0023 applies
- test or characterization path exists

Required proof:

- predictable credential blocked outside local/testing
- local/testing workflow preserved if intentionally kept
- unknown environment treated as production-like
- no fallback predictable password in production-like path
- targeted test output
- relevant auth/seeder blast-radius output

### 4. Clean deterministic seed slice

Allowed when:

- manifest maps legacy file to clean target
- scenario matrix entry exists or is added
- deterministic data contract is defined

Required proof:

- syntax check
- targeted seed run
- rerun idempotency proof
- audit command or invariant test proof
- relevant feature/unit tests if finance-sensitive

### 5. Legacy deprecation or deletion

Allowed only when:

- clean replacement exists
- make-level wiring no longer depends on old file
- manifest is updated
- tests and audit pass
- owner accepts removal

Required proof:

- source dependency check
- diff stat
- targeted tests
- relevant make seed proof
- manifest update

## Forbidden Shortcuts

Do not:

- mass-edit all PHP seeders just to add comments
- rename seeders without dependency proof
- delete legacy seeders before replacement proof
- treat seeded data as proof of business correctness
- claim idempotency without rerun proof
- claim production-safe credentials without environment test
- mix load/stress seed into baseline without explicit contract
- update error_log status before implementation proof
- change make 1/2/3 behavior without documenting blast radius

## Current Known Priority

The first runtime-risk target is `UserSeeder`.

Reason:

- current entrypoint inspection showed default seed, make 1, make 2, and make 3 reach `UserSeeder`
- `UserSeeder` creates predictable admin/kasir credentials
- `UserSeeder` also assigns privileged role/access state
- ADR-0023 makes this a production-like environment safety boundary

## Recommended Next Runtime Slice

Slice name:

`ADR-0023 UserSeeder credential boundary`

Target files to inspect before patch:

- database/seeders/UserSeeder.php
- database/seeders/SeedLevel1Seeder.php
- database/seeders/SeedLevel2Seeder.php
- database/seeders/SeedLevel3Seeder.php
- tests related to seeder/auth/user creation

Minimum implementation direction:

- allow predictable local/testing accounts only in local/testing
- block or fail closed outside local/testing unless explicit secure bootstrap flow exists
- do not remove local developer workflow without owner decision
- do not introduce production-like generated secret behavior without logging/display policy

## Stop Conditions

Stop if:

- environment classification is unclear
- test cannot simulate production-like environment safely
- patch would remove local/testing login workflow
- patch would silently skip credential creation without documented behavior
- patch would still allow hardcoded privileged password outside local/testing
- patch requires broad auth redesign
- source inspection finds additional credential seeders not in current manifest

## Completion Rule

A seeder slice is complete only when:

- source behavior is inspected
- implementation scope is narrow
- proof is shown
- manifest is updated
- docs reflect residual gaps
- no unrelated runtime seeder behavior changed silently
