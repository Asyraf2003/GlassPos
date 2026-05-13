# Handoff - Seeder ADR-0023 UserSeeder Credential Boundary

Date: 2026-05-11

## Final Goal

Migrate HyperPOS legacy seeders into a clean, deterministic, auditable seeder system.

Final clean seeder direction:

- local/testing identity-access seed remains convenient
- production-like environments do not receive predictable privileged credentials
- staging/demo/preview/shared QA/unknown environments are production-like by default
- future staging account creation must use an explicit bootstrap path with env/config credential input
- legacy seeders remain compatibility surface until mapped and replaced with proof

## Active Scope Completed In This Slice

Slice:

ADR-0023 UserSeeder credential boundary.

Files changed:

- database/seeders/UserSeeder.php
- tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php

Runtime behavior now intended:

- local environment allows predictable local seeded admin/kasir users
- testing environment allows predictable local seeded admin/kasir users
- staging environment blocks predictable seeded users
- unknown/custom environment blocks predictable seeded users

## Locked Decisions

1. UserSeeder remains local/testing convenience seed for now.

2. Staging account requirement is valid, but staging must not use the hardcoded predictable credential path.

3. Staging bootstrap is deferred to a separate explicit slice.

4. Current UserSeeder predictable accounts remain allowed only for local/testing.

5. Production-like and unknown environments must fail closed before creating seeded users, roles, or capabilities.

6. Do not rename legacy seeder files.

7. Do not rename Makefile targets in this slice.

8. Do not mass-edit legacy PHP seeders for marker comments.

9. Do not update error_log status before implementation proof.

10. User handles git commit/push manually.

## Source Behavior Before Patch

database/seeders/UserSeeder.php created or updated:

- admin@gmail.com
- kasir@gmail.com

Both used predictable hardcoded password:

- 12345678

UserSeeder also granted privileged state:

- admin role
- kasir role
- admin cashier-area access active
- admin transaction capability active

Entrypoints reaching UserSeeder:

- DatabaseSeeder -> SeedLevel2Seeder -> UserSeeder
- DatabaseLoadSeeder -> SeedLevel3Seeder -> UserSeeder
- SeedLevel1Seeder -> UserSeeder
- SeedLevel2Seeder -> UserSeeder
- SeedLevel3Seeder -> UserSeeder

Risk:

- predictable privileged credentials were reachable from default/local seed paths
- staging/production-like/unknown environments were not guarded
- ADR-0023 requirement was not enforced at runtime

## Test Added

Path:

tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php

Coverage:

1. local environment allows predictable local seeded users
2. testing environment allows predictable local seeded users
3. staging environment blocks predictable seeded users
4. unknown/custom environment blocks predictable seeded users

Important test detail:

The test uses app environment override through:

$this->app->detectEnvironment(static fn (): string => $environment);

## RED Proof

After resolving DB runtime issue and using the available MySQL test config, RED characterization was valid:

- local allowed: passed
- testing allowed: passed
- staging blocked: failed because admin@gmail.com was still created
- unknown/custom blocked: failed because admin@gmail.com was still created

RED result:

- 2 failed
- 2 passed
- 6 assertions

Meaning:

The test proved the ADR-0023 bug behavior before patch.

## Runtime Patch

Path:

database/seeders/UserSeeder.php

Patch summary:

- imported RuntimeException
- added guard at the top of run()
- allowed only local/testing environments
- non-local/testing environments throw before user creation

Guard message:

Predictable seeded users are only allowed in local/testing environments.

Behavior:

- local/testing continues to seed admin@gmail.com and kasir@gmail.com with password 12345678
- staging/production-like/unknown environments fail closed
- no user/role/capability rows should be created after guard failure

## GREEN Proof

Syntax check:

- php -l database/seeders/UserSeeder.php
- php -l tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php

Result:

- no syntax errors

Targeted seeder test:

Command:

php artisan test tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php

Result:

- 4 passed
- 12 assertions

Blast-radius test:

Command:

php artisan test tests/Feature/Auth tests/Feature/IdentityAccess tests/Unit/Adapters/In/Http/Middleware/IdentityAccess tests/Unit/Application/IdentityAccess

Result:

- 34 passed
- 136 assertions

Diff snapshot from proof output:

- database/seeders/UserSeeder.php changed
- 7 insertions
- runtime diff narrow
- guard added before user creation

## Remote Verification

Remote main was checked read-only through GitHub integration after user push.

Verified on remote main:

- database/seeders/UserSeeder.php contains the local/testing environment guard
- tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php exists
- test file contains local/testing allowed and staging/unknown blocked coverage

Commit hash was not captured in this handoff.

## Current Status

Code/test status:

- UserSeeder ADR-0023 boundary is implemented and targeted-green
- relevant auth/identity blast-radius is green
- source/test are present on remote main

Documentation status:

- docs/error_log/002-seeder-introduces-predictable-admin-credentials.md has not been updated for this final proof in this session
- docs/blueprint/seeder/2026-05-11-legacy-seeder-manifest.md has not been updated for final runtime proof in this session
- docs/handoff/seeder/2026-05-11-userseeder-credential-boundary-handoff.md is this handoff file

## Remaining Gaps

1. Staging bootstrap account is not implemented.

2. No explicit staging env credential path exists yet.

3. docs/error_log/002 still needs update with final proof if owner wants closure.

4. Seeder manifest still needs update if this slice should be reflected there.

5. Full make verify was not run in this slice.

6. Production/staging deployed database rotation is not proven.

7. If any non-local database previously ran old UserSeeder, manual credential rotation may still be required.

## Do Not Claim

Do not claim:

- full clean seeder migration is complete
- staging bootstrap exists
- error_log 002 is fully closed in docs
- full project DoD is green
- production database is safe
- old deployed credentials are rotated
- Makefile seed contract is clean
- legacy seeder system is fully migrated

## Safe Next Step Options

Option A - Documentation closure for completed slice

Update:

- docs/error_log/002-seeder-introduces-predictable-admin-credentials.md
- docs/blueprint/seeder/2026-05-11-legacy-seeder-manifest.md

Include:

- exact files changed
- RED proof
- GREEN targeted proof
- blast-radius proof
- remaining gaps
- note that staging bootstrap is deferred

Option B - Staging bootstrap design slice

Create minimum blueprint for explicit staging bootstrap account.

Target behavior:

- no hardcoded staging password
- no fallback to 12345678
- env/config credential input required
- fail closed when missing
- not wired into default DatabaseSeeder/SeedLevel1/SeedLevel2/SeedLevel3
- no password logging
- tests for success with explicit env and fail-closed when missing

Recommended order:

1. inspect existing artisan command/seeder structure
2. decide command vs dedicated seeder
3. add characterization tests
4. patch smallest bootstrap path
5. run targeted + auth/identity tests
6. document runbook behavior

## Recommended Next Active Step

If continuing the current closure path:

1. Verify current repo status and latest commit.

Command:

git status --short --untracked-files=all
git rev-parse --abbrev-ref HEAD
git rev-parse --short HEAD
git log --oneline -5

2. Update docs/error_log/002 with proof from this slice.

3. Update manifest with source inspection/proof that UserSeeder now has an ADR-0023 local/testing guard.

4. Run grep anchors and diff check.

5. User commits/pushes manually.

## Opening Prompt For Next Session

Kita lanjut HyperPOS repo Asyraf2003/hyperpos.

Current slice: Seeder ADR-0023 UserSeeder credential boundary.

Important locked decisions:
- UserSeeder remains local/testing convenience seed.
- Staging account must not use hardcoded predictable credentials.
- Staging bootstrap is separate explicit slice using env/config credential input and fail-closed behavior.
- Do not rename seeders.
- Do not rename Makefile targets.
- Do not mass-edit legacy seeder PHP files.
- Do not update error_log without proof.
- User handles commit/push manually.

Current proof:
- RED characterization existed: staging/unknown env still created seeded users before patch, 2 failed/2 passed/6 assertions.
- Runtime patch added local/testing environment guard to database/seeders/UserSeeder.php.
- Targeted GREEN: tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php passed 4 tests/12 assertions.
- Blast-radius GREEN: Auth + IdentityAccess suites passed 34 tests/136 assertions.
- Remote main was verified to contain UserSeeder guard and new test file.
- Full make verify not run.
- docs/error_log/002 and seeder manifest still need final proof update if closing docs.

Next safest step:
Verify local git status/HEAD, then update docs/error_log/002 and docs/blueprint/seeder/2026-05-11-legacy-seeder-manifest.md with the final proof and explicit gaps, without touching runtime code.
