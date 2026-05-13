# Seeder Legacy to Clean Definition of Done

## Status

Active DoD.

This document defines completion criteria for migrating legacy seeders into a clean, modular, deterministic, auditable seeder system.

This document does not change runtime behavior.

This document does not claim the current Makefile already satisfies this DoD.

## Source Of Truth

- database/seeders/README.md
- docs/03-blueprints/seeder/2026-05-11-legacy-to-clean-seeder-blueprint.md
- docs/03-blueprints/seeder/2026-05-11-legacy-seeder-manifest.md
- docs/03-blueprints/seeder/legacy-to-clean-workflow.md
- docs/02-architecture/adr/0023-seeder-credential-and-environment-safety.md
- docs/03-blueprints/security/adr-0023-seeder-safety.md
- docs/99-archive/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md
- docs/99-archive/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md
- local command output at execution time

## Goal

Seeder migration is done only when seeders can be executed safely and explicitly by domain, by scenario weight, and by aggregate level.

The final system must support:

1. one-by-one modular seeding
2. normal dataset seeding
3. heavy dataset seeding
4. extreme dataset seeding
5. all-in-one seeding where intentionally allowed
6. audit proof for each level
7. idempotency proof for reruns
8. credential safety by environment
9. scenario traceability
10. clear legacy deprecation path

## Required Makefile Contract

The clean Makefile must expose modular seed targets.

Exact target names may be adjusted during implementation, but the final contract must provide equivalent capability.

### Individual Domain Targets

Required domain-level targets:

- seed user / identity access
- seed product
- seed supplier
- seed employee / karyawan finance
- seed expense / biaya
- seed supplier invoice / procurement
- seed customer transaction / nota
- seed customer payment / pembayaran
- seed customer refund / refund
- seed customer correction / koreksi
- seed inventory threshold / stock threshold
- seed load/stress data where applicable

Recommended target naming:

- `make seed:user`
- `make seed:product`
- `make seed:supplier`
- `make seed:employee`
- `make seed:expense`
- `make seed:procurement`
- `make seed:customer-transaction`
- `make seed:customer-payment`
- `make seed:customer-refund`
- `make seed:customer-correction`
- `make seed:inventory-threshold`

Each target must call one explicit seeder class or one explicit clean aggregate for that domain.

No domain target may silently run unrelated domains.

### Normal Dataset Targets

Required normal targets:

- `make seed:normal`
- or equivalent documented target

Purpose:

- deterministic normal operating dataset
- roughly one month scenario coverage
- safe for manual review and business analysis

Allowed:

- identity/access only if explicitly local/testing or guarded
- product baseline
- supplier baseline
- employee finance baseline
- supplier invoice baseline/scenario
- customer transaction baseline
- payment/refund/correction baseline
- expense baseline
- stock threshold baseline

Forbidden:

- uncontrolled random data
- extreme annual load
- production-like predictable credentials
- hidden stress-only data

### Heavy Dataset Targets

Required heavy targets:

- `make seed:heavy`
- or equivalent documented target

Purpose:

- larger deterministic dataset than normal
- used for report load and operational review
- not as extreme as full stress dataset

Allowed:

- expanded transaction count
- expanded product/supplier/customer scenario count
- deterministic higher-volume reports

Forbidden:

- random finance state
- unbounded row growth
- production-like predictable credentials
- scenario not covered by audit snapshot

### Extreme Dataset Targets

Required extreme targets:

- `make seed:extreme`
- or equivalent documented target

Purpose:

- deterministic high-volume/stress dataset
- annual or near-annual load simulation
- report performance and edge-case review

Allowed:

- 1-year deterministic load
- procurement load
- customer transaction load
- payment/refund/correction load
- expense load
- inventory threshold/load conditions

Forbidden:

- untraceable random data
- non-idempotent rerun behavior
- hidden credential creation
- baseline seed pollution

### Aggregate Targets

Required aggregate targets:

- `make seed:all`
- or equivalent documented target

Purpose:

- intentionally run all clean seed levels in a documented order

Rules:

- must be explicit
- must print clear phase names
- must not be the default accidental production-like path
- must not create predictable privileged credentials outside local/testing
- must run audit or tell operator exact audit command to run

### Existing Compatibility Targets

Existing targets such as `make 1`, `make 2`, and `make 3` may remain temporarily as compatibility aliases.

Compatibility aliases must be documented.

Final compatibility decision must state whether:

- `make 1` maps to identity/access local-test seed
- `make 2` maps to normal clean seed
- `make 3` maps to extreme clean seed

No compatibility target may hide broader behavior than its name suggests.

## Required Seeder Class Contract

Each clean seeder class must have:

- explicit purpose
- deterministic data
- stable business/scenario identifiers
- idempotent rerun behavior
- clear dependency order
- no unrelated domain side effects
- source path listed in manifest
- scenario matrix link where applicable
- audit or invariant proof where finance-sensitive

## Required Safety Contract

Credential-sensitive seeders must satisfy ADR-0023:

- predictable credentials only in explicitly local/testing environments
- production-like environments must not create predictable privileged credentials
- unknown environments are production-like by default
- missing production-like secure credential input must fail closed if bootstrap is attempted
- no fallback local password in production-like path
- no generated secret without logging/display/handoff policy

## Required Idempotency Contract

Every official clean Makefile seed target must be rerunnable.

Required proof for each target:

1. run target once
2. run audit or count check
3. run target again
4. run audit or count check again
5. compare key counts and invariants
6. confirm no unintended row growth
7. confirm no orphan rows
8. confirm no duplicate active business keys

## Required Audit Contract

Clean seeder system must provide audit commands or documented audit scripts for:

- seed user / identity access
- seed normal
- seed heavy
- seed extreme
- finance reconciliation
- orphan row check
- duplicate active business key check
- payment/refund allocation check
- supplier payable check
- inventory quantity/value check
- employee finance balance check
- report sanity check where applicable

Recommended future commands:

- `php artisan audit:seed-user`
- `php artisan audit:seed-normal`
- `php artisan audit:seed-heavy`
- `php artisan audit:seed-extreme`
- `php artisan audit:finance`

Exact names may change, but audit coverage must remain.

## Required Scenario Matrix Contract

Finance-sensitive seeders must map to scenario matrix entries.

Required mapping fields:

- domain
- scenario ID
- make target
- seeder class
- tables involved
- expected money effect
- expected stock effect
- expected report effect
- invariant checks
- proof status

A seeded finance scenario is not clean until it is mapped.

## Required Test Contract

Each clean seeder slice must have targeted proof.

Minimum proof by type:

### Credential / User Seed

Required:

- local/testing allowed behavior
- production-like blocked behavior
- unknown environment blocked behavior
- no fallback hardcoded credential outside local/testing
- existing local developer workflow preserved if intentionally kept

### Product / Supplier / Master Data Seed

Required:

- deterministic keys
- rerun does not grow rows unintentionally
- no duplicate active business keys
- status/soft-delete scenario proof if applicable

### Customer Transaction / Payment / Refund / Correction Seed

Required:

- note totals reconcile
- payment allocation does not exceed payment amount
- refund does not exceed allocated payment
- correction does not corrupt current/historical rows
- rerun idempotency proof

### Supplier Invoice / Procurement Seed

Required:

- invoice total reconciles with lines
- payment does not imply receipt
- receipt does not imply payment
- voided invoice does not leak active payable
- inventory movement matches receipt policy
- proof attachment behavior uses approved path or remains explicitly legacy

### Employee Finance Seed

Required:

- employee debt remaining balance reconciles
- payroll or salary data is deterministic
- inactive employee behavior is explicit
- financial correction path is system-path aligned or explicitly legacy

### Load / Heavy / Extreme Seed

Required:

- deterministic load density
- bounded row counts
- rerun stability
- audit output remains stable
- no random finance mutation
- no credential creation beyond allowed boundary

## Required Documentation Contract

Seeder migration is not done until these docs are updated:

- database/seeders/README.md
- docs/03-blueprints/seeder/2026-05-11-legacy-to-clean-seeder-blueprint.md
- docs/03-blueprints/seeder/2026-05-11-legacy-seeder-manifest.md
- docs/03-blueprints/seeder/legacy-to-clean-workflow.md
- docs/03-blueprints/seeder/legacy-to-clean-dod.md
- scenario matrix docs
- relevant error_log docs if a security/finance issue is fixed

## Required Diff Contract

A clean seeder slice diff must be narrow.

Allowed per slice:

- selected seeder files
- selected tests
- selected audit command/script
- selected Makefile target
- selected docs/manifest updates

Forbidden:

- broad unrelated seeder rewrite
- mass rename without proof
- deleting legacy seeders without replacement proof
- weakening tests to pass seeders
- hiding credential behavior behind vague comments

## Completion Criteria

Seeder Legacy to Clean migration is complete only when:

1. all legacy seeders are classified
2. each active runtime seeder has a clean target or accepted legacy status
3. Makefile exposes modular domain seed targets
4. Makefile exposes normal/heavy/extreme aggregate targets
5. Makefile exposes an explicit all/aggregate target if needed
6. existing compatibility targets are documented
7. predictable privileged credentials are guarded by environment
8. individual domain targets are idempotent
9. normal target is idempotent
10. heavy target is idempotent
11. extreme target is idempotent
12. scenario matrix maps finance-sensitive seeders
13. audit command or equivalent audit proof exists
14. finance invariant tests cover seeded finance behavior
15. load/stress seeders are deterministic and bounded
16. no production-like seed path creates known privileged credentials
17. docs and manifest are updated
18. legacy deletion/deprecation happens only after replacement proof
19. final verification output is captured
20. owner accepts the final diff

## Stop Conditions

Stop immediately if:

- Makefile target name hides broad or dangerous behavior
- individual target seeds unrelated domains silently
- normal target includes extreme/load data without explicit contract
- heavy/extreme target creates predictable credentials
- production-like environment can run local credential seed
- rerun creates unintended row growth
- audit output is missing for finance-sensitive seed
- scenario matrix is missing for seeded finance scenario
- source behavior contradicts manifest classification
- implementation changes auth/access policy beyond ADR-0023
- implementation requires broad rewrite before one narrow proof slice

## Current Status

Initial governance documentation is in progress.

Completed in documentation foundation:

- legacy folder notice
- legacy-to-clean blueprint
- initial 41-row legacy manifest
- seeder legacy-to-clean workflow

Pending:

- this DoD file verification
- Makefile modular target implementation
- UserSeeder ADR-0023 credential boundary implementation
- source inspection for product/supplier/finance/load seeders
- clean seeder implementation
- audit command and invariant proof
