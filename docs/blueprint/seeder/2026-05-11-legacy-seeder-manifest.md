# Legacy Seeder Manifest

## Status

Source inspection manifest with `UserSeeder` ADR-0023 credential boundary locally verified.

This document is still a legacy-to-clean migration manifest, not a clean seeder completion certificate.

This document is based on local file inventory, source inspection, and the documented `UserSeeder` credential-boundary proof.

This document does not prove source-level behavior.

This document does not change runtime behavior.

This document does not mark any seeder as clean, fixed, safe, idempotent, or production-ready.

## Source Of Truth

- docs/blueprint/seeder/2026-05-11-legacy-to-clean-seeder-blueprint.md
- docs/adr/0023-seeder-credential-and-environment-safety.md
- docs/blueprint/security/2026-05-06-seeder-credential-and-environment-safety-blueprint.md
- docs/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md
- docs/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md
- local command output from 2026-05-11 inventory

## Classification Rule

All current `database/seeders/**/*.php` files are legacy compatibility seeders until source inspection and clean-contract proof say otherwise.

Initial category is inferred from path and filename only.

Risk level is conservative.

A later source-inspection pass must confirm:

- make level usage
- credential behavior
- idempotency behavior
- direct DB write behavior
- finance mutation behavior
- scenario matrix coverage
- clean replacement target
- deletion/deprecation safety

## Legend

### Legacy Status

- LEGACY_COMPATIBILITY: current file may remain temporarily but is not final clean architecture
- LEGACY_ENTRYPOINT: current runtime entrypoint that must not be changed without proof
- LEGACY_SUPPORT: helper/support file used by legacy seeders until source inspection proves clean ownership
- UNKNOWN: not enough information from filename/path alone

### Clean Target

- KEEP_AS_ENTRYPOINT_UNTIL_REPLACED
- CLEAN_IDENTITY_ACCESS_SEED
- CLEAN_DOMAIN_BASELINE_SEED
- CLEAN_SCENARIO_SEED
- CLEAN_LOAD_SEED
- CLEAN_PRODUCTION_BOOTSTRAP
- CLEAN_SUPPORT_HELPER
- REWORK_TO_SYSTEM_PATH
- DEPRECATE_AFTER_REPLACEMENT
- SOURCE_INSPECTION_REQUIRED

### Risk Level

- LOW: helper or non-finance/non-credential based on filename only
- MEDIUM: domain/scenario baseline or backfill
- HIGH: credential, finance mutation, load/stress, payment/refund/correction, or unknown direct-write risk

## Initial Manifest

| Path | Initial Category | Legacy Status | Clean Target | Risk | Proof Basis | Source Inspection Gap |
|---|---|---|---|---|---|---|
| database/seeders/DatabaseLoadSeeder.php | ENTRYPOINT | LEGACY_ENTRYPOINT | KEEP_AS_ENTRYPOINT_UNTIL_REPLACED | HIGH | local inventory + Makefile/docs references | confirm called seed levels and load boundary |
| database/seeders/DatabaseSeeder.php | ENTRYPOINT | LEGACY_ENTRYPOINT | KEEP_AS_ENTRYPOINT_UNTIL_REPLACED | HIGH | local inventory + Makefile/docs references | confirm default db:seed behavior and credential path |
| database/seeders/EmployeeFinanceBaselineSeeder.php | EMPLOYEE_FINANCE_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | HIGH | filename/path | inspect finance writes and idempotency |
| database/seeders/EmployeeFinanceSeeder.php | EMPLOYEE_FINANCE_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | HIGH | filename/path | inspect finance writes and idempotency |
| database/seeders/Expense/ExpenseBaselineSeeder.php | EXPENSE_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | MEDIUM | filename/path | inspect expense determinism and report coverage |
| database/seeders/ExpenseSeeder.php | EXPENSE_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | MEDIUM | filename/path | inspect old/new expense overlap |
| database/seeders/FinancialCorrectionSeeder.php | FINANCIAL_CORRECTION | LEGACY_COMPATIBILITY | REWORK_TO_SYSTEM_PATH | HIGH | filename/path | inspect correction path and direct finance mutation |
| database/seeders/Load/CustomerCorrectionLoadSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | HIGH | filename/path | inspect load determinism and correction behavior |
| database/seeders/Load/CustomerPaymentLoadSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | HIGH | filename/path | inspect payment load and allocation safety |
| database/seeders/Load/CustomerRefundLoadSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | HIGH | filename/path | inspect refund load and refund overflow safety |
| database/seeders/Load/CustomerTransactionLoadSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | HIGH | filename/path | inspect transaction load determinism |
| database/seeders/Load/ExpenseLoadSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | MEDIUM | filename/path | inspect annual expense determinism |
| database/seeders/Load/ProcurementLoadSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | HIGH | filename/path | inspect supplier/inventory load behavior |
| database/seeders/Load/ProductLoadSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | MEDIUM | filename/path | inspect product load idempotency |
| database/seeders/ProductInventoryThresholdBackfillSeeder.php | BACKFILL | LEGACY_COMPATIBILITY | SOURCE_INSPECTION_REQUIRED | MEDIUM | filename/path | inspect whether backfill is still needed |
| database/seeders/Product/ProductScenarioActiveBasicSeeder.php | PRODUCT_SCENARIO | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | MEDIUM | filename/path | inspect scenario ID and matrix coverage |
| database/seeders/Product/ProductScenarioEditedSeeder.php | PRODUCT_SCENARIO | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | MEDIUM | filename/path | inspect edited-product lifecycle coverage |
| database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php | PRODUCT_SCENARIO | LEGACY_COMPATIBILITY | DEPRECATE_AFTER_REPLACEMENT | HIGH | filename includes LegacyIncomplete | inspect whether still used and replacement plan |
| database/seeders/Product/ProductScenarioRecreatedSeeder.php | PRODUCT_SCENARIO | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | MEDIUM | filename/path | inspect recreate/soft-delete idempotency |
| database/seeders/Product/ProductScenarioSoftDeletedSeeder.php | PRODUCT_SCENARIO | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | MEDIUM | filename/path | inspect soft-delete idempotency |
| database/seeders/Product/ProductSeedCatalog.php | SUPPORT_HELPER | LEGACY_SUPPORT | CLEAN_SUPPORT_HELPER | LOW | filename/path | inspect if deterministic catalog helper |
| database/seeders/Product/ProductSeedThresholds.php | SUPPORT_HELPER | LEGACY_SUPPORT | CLEAN_SUPPORT_HELPER | LOW | filename/path | inspect threshold contract |
| database/seeders/ProductSeeder.php | PRODUCT_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | MEDIUM | filename/path | inspect baseline, scenario calls, idempotency |
| database/seeders/SeedLevel1Seeder.php | ENTRYPOINT | LEGACY_ENTRYPOINT | KEEP_AS_ENTRYPOINT_UNTIL_REPLACED | HIGH | local inventory + Makefile references | inspect exact make 1 contract |
| database/seeders/SeedLevel2Seeder.php | ENTRYPOINT | LEGACY_ENTRYPOINT | KEEP_AS_ENTRYPOINT_UNTIL_REPLACED | HIGH | local inventory + Makefile references | inspect exact make 2 contract |
| database/seeders/SeedLevel3Seeder.php | ENTRYPOINT | LEGACY_ENTRYPOINT | KEEP_AS_ENTRYPOINT_UNTIL_REPLACED | HIGH | local inventory + Makefile references | inspect exact make 3 contract |
| database/seeders/SupplierInvoiceAnnualDenseSeeder.php | LOAD_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | HIGH | filename/path | inspect annual supplier invoice determinism |
| database/seeders/SupplierInvoiceBaselineSeeder.php | SUPPLIER_INVOICE_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | HIGH | filename/path | inspect supplier invoice system path and idempotency |
| database/seeders/SupplierInvoiceScenarioSeeder.php | SUPPLIER_INVOICE_SCENARIO | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | HIGH | filename/path | inspect scenario matrix coverage |
| database/seeders/SupplierInvoiceSeeder.php | SUPPLIER_INVOICE_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | HIGH | filename/path | inspect legacy overlap with baseline/scenario seeders |
| database/seeders/SupplierInvoiceVoidedScenarioSeeder.php | SUPPLIER_INVOICE_SCENARIO | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | HIGH | filename/path | inspect void lifecycle and matrix coverage |
| database/seeders/SupplierPaymentProofSeeder.php | SUPPLIER_INVOICE_SCENARIO | LEGACY_COMPATIBILITY | REWORK_TO_SYSTEM_PATH | HIGH | filename/path | inspect attachment/proof write path |
| database/seeders/SupplierSeeder.php | SUPPLIER_BASELINE | LEGACY_COMPATIBILITY | CLEAN_DOMAIN_BASELINE_SEED | MEDIUM | filename/path | inspect deterministic key and rerun behavior |
| database/seeders/Support/SeedDensity.php | SUPPORT_HELPER | LEGACY_SUPPORT | CLEAN_SUPPORT_HELPER | LOW | filename/path | inspect helper ownership |
| database/seeders/Support/SeedWindow.php | SUPPORT_HELPER | LEGACY_SUPPORT | CLEAN_SUPPORT_HELPER | LOW | filename/path | inspect helper ownership |
| database/seeders/Transaction/CustomerCorrectionBaselineSeeder.php | CUSTOMER_CORRECTION_BASELINE | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | HIGH | filename/path | inspect correction usecase/system path |
| database/seeders/Transaction/CustomerPaymentBaselineSeeder.php | CUSTOMER_PAYMENT_BASELINE | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | HIGH | filename/path | inspect payment allocation safety |
| database/seeders/Transaction/CustomerRefundBaselineSeeder.php | CUSTOMER_REFUND_BASELINE | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | HIGH | filename/path | inspect refund allocation safety |
| database/seeders/Transaction/CustomerTransactionBaselineSeeder.php | CUSTOMER_TRANSACTION_BASELINE | LEGACY_COMPATIBILITY | CLEAN_SCENARIO_SEED | HIGH | filename/path | inspect note/payment/refund/correction matrix coverage |
| database/seeders/UserSeeder.php | CREDENTIAL | LEGACY_COMPATIBILITY | CLEAN_IDENTITY_ACCESS_SEED | HIGH | filename/path + ADR-0023 relevance | inspect environment guard and predictable credential path |
| database/seeders/WorkshopStressTestSeeder.php | STRESS_TEST | LEGACY_COMPATIBILITY | CLEAN_LOAD_SEED | HIGH | filename/path | inspect random/stress behavior and whether still wired |

## Immediate Source Inspection Order

Start with entrypoints and credential risk:

1. database/seeders/DatabaseSeeder.php
2. database/seeders/DatabaseLoadSeeder.php
3. database/seeders/SeedLevel1Seeder.php
4. database/seeders/SeedLevel2Seeder.php
5. database/seeders/SeedLevel3Seeder.php
6. database/seeders/UserSeeder.php

Reason:

- entrypoints define blast radius
- UserSeeder is credential-sensitive
- ADR-0023 makes predictable privileged credentials a production safety boundary
- make 1/2/3 contract depends on entrypoint wiring

## Stop Conditions

Stop before runtime edits if:

- manifest conflicts with source inspection
- make level wiring is unclear
- UserSeeder creates predictable privileged credentials without environment guard
- DatabaseSeeder default path reaches credential seed unexpectedly
- SeedLevel2 or SeedLevel3 mixes load/scenario/credential in a way not documented
- source inspection shows a seeder is already clean but manifest marks it for replacement
- source inspection shows a support helper is still required by clean path
- classification cannot be proven from source

## Next Step

Inspect entrypoint and UserSeeder source.

Do not edit runtime seeders yet.

## Source Inspection Update 1 — Entrypoints And UserSeeder

Date: 2026-05-11

Scope:

- database/seeders/DatabaseSeeder.php
- database/seeders/DatabaseLoadSeeder.php
- database/seeders/SeedLevel1Seeder.php
- database/seeders/SeedLevel2Seeder.php
- database/seeders/SeedLevel3Seeder.php
- database/seeders/UserSeeder.php

### Findings

#### DatabaseSeeder

`DatabaseSeeder` calls `SeedLevel2Seeder`.

Impact:

- default `php artisan db:seed` path reaches level 2
- level 2 includes `UserSeeder`
- default seed path can create/update predictable privileged credentials through `UserSeeder`

Classification impact:

- keep as `LEGACY_ENTRYPOINT`
- risk remains `HIGH`
- source inspection confirms default seed path is not credential-neutral

#### DatabaseLoadSeeder

`DatabaseLoadSeeder` calls `SeedLevel3Seeder`.

Impact:

- explicit load entrypoint reaches level 3
- level 3 includes `UserSeeder`
- load seed path can create/update predictable privileged credentials through `UserSeeder`

Classification impact:

- keep as `LEGACY_ENTRYPOINT`
- risk remains `HIGH`
- source inspection confirms load seed path is not credential-neutral

#### SeedLevel1Seeder

`SeedLevel1Seeder` calls only `UserSeeder`.

Impact:

- make 1 is identity/access scoped at source level
- make 1 still depends on `UserSeeder`
- make 1 inherits credential safety risk from `UserSeeder`

Classification impact:

- keep as `LEGACY_ENTRYPOINT`
- risk remains `HIGH`
- clean replacement target remains identity/access local/test seed

#### SeedLevel2Seeder

`SeedLevel2Seeder` calls:

- UserSeeder
- ProductSeeder
- SupplierSeeder
- EmployeeFinanceBaselineSeeder
- SupplierInvoiceBaselineSeeder
- SupplierInvoiceScenarioSeeder
- SupplierInvoiceVoidedScenarioSeeder
- ExpenseBaselineSeeder
- CustomerTransactionBaselineSeeder
- ProductInventoryThresholdBackfillSeeder
- CustomerPaymentBaselineSeeder
- CustomerRefundBaselineSeeder
- CustomerCorrectionBaselineSeeder

Impact:

- make 2 is not credential-neutral
- make 2 mixes identity/access with deterministic domain baseline/scenario data
- make 2 cannot be considered clean final contract until credential boundary is separated or guarded

Classification impact:

- keep as `LEGACY_ENTRYPOINT`
- risk remains `HIGH`
- clean path should eventually split credential/local access seed from domain baseline seed

#### SeedLevel3Seeder

`SeedLevel3Seeder` calls:

- UserSeeder
- ProductSeeder
- ProductLoadSeeder
- SupplierSeeder
- EmployeeFinanceBaselineSeeder
- ProcurementLoadSeeder
- ProductInventoryThresholdBackfillSeeder
- ExpenseLoadSeeder
- CustomerTransactionLoadSeeder
- CustomerPaymentLoadSeeder
- CustomerRefundLoadSeeder
- CustomerCorrectionLoadSeeder

Impact:

- make 3 is not credential-neutral
- make 3 mixes identity/access with load/stress data
- make 3 cannot be considered clean final contract until credential boundary is separated or guarded

Classification impact:

- keep as `LEGACY_ENTRYPOINT`
- risk remains `HIGH`
- clean path should eventually split credential/local access seed from load seed

#### UserSeeder

`UserSeeder` creates or updates:

- `admin@gmail.com`
- `kasir@gmail.com`

Both accounts use predictable hardcoded password:

- `12345678`

`UserSeeder` also writes privileged access state:

- admin role
- kasir role
- admin cashier area active
- admin transaction capability active

No environment guard was observed in `UserSeeder`.

Impact:

- predictable privileged credentials are reachable from make 1
- predictable privileged credentials are reachable from make 2
- predictable privileged credentials are reachable from make 3
- predictable privileged credentials are reachable from default `DatabaseSeeder`
- this directly falls under ADR-0023 credential safety boundary

Classification impact:

- `UserSeeder` remains `LEGACY_COMPATIBILITY`
- category remains `CREDENTIAL`
- risk remains `HIGH`
- clean target remains `CLEAN_IDENTITY_ACCESS_SEED`
- runtime patch must not expand this behavior
- next implementation slice should prioritize ADR-0023 guard before broad clean seeder refactor

### Decision After Source Inspection Update 1

The first runtime-safe implementation target should be:

- guard or split `UserSeeder` behavior by environment

Minimum safe direction:

- predictable local/testing accounts remain allowed only in local/testing
- production-like environments must not create predictable privileged credentials
- unknown environments must fail closed or skip predictable credential creation according to ADR-0023-approved behavior
- default seed path must not silently create production-capable known credentials

Do not start broad file-level legacy marker edits before `UserSeeder` credential boundary is handled or explicitly deferred.

### Next Source Inspection Target

Inspect `ProductSeeder`, `SupplierSeeder`, and product scenario seeders for idempotency and clean scenario mapping.

Do not edit runtime seeders until the owner chooses between:

1. immediate ADR-0023 credential guard
2. document-only legacy marking first
3. clean seeder namespace/folder split planning

## Source Inspection Update 2 - UserSeeder ADR-0023 Credential Boundary Closure

Date: 2026-05-11.

Scope:

- `database/seeders/UserSeeder.php`
- `tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php`
- `docs/error_log/002-seeder-introduces-predictable-admin-credentials.md`
- `docs/blueprint/seeder/2026-05-11-legacy-seeder-manifest.md`

Status:

`UserSeeder` now has a locally verified ADR-0023 environment boundary for predictable seeded credentials.

This does not convert `UserSeeder` into a final clean seeder. It remains a legacy compatibility surface until the clean seeder contract and staging/production bootstrap path are implemented and verified.

Updated source behavior:

- `UserSeeder::run()` fails closed outside `local` and `testing`.
- `local` and `testing` keep predictable convenience users for developer/test workflows.
- `staging` and unknown/custom environments are blocked before seeded user creation.
- the guard error is `Predictable seeded users are only allowed in local/testing environments.`
- no Makefile target was renamed.
- no seeder class was renamed.
- no broad legacy seeder refactor was performed.

Files changed in the runtime implementation slice:

- `database/seeders/UserSeeder.php`
- `tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php`

Proof recorded for the implementation slice:

- RED characterization: 2 failed, 2 passed, 6 assertions.
- syntax proof: `php -l database/seeders/UserSeeder.php` passed.
- syntax proof: `php -l tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php` passed.
- targeted GREEN: 4 passed, 12 assertions.
- blast-radius GREEN: 34 passed, 136 assertions.
- local closure snapshot: branch `main`, HEAD `28b27745`, remote aligned with `origin/main`.

Classification impact:

- `database/seeders/UserSeeder.php` remains `LEGACY_COMPATIBILITY`.
- initial category remains `CREDENTIAL`.
- clean target remains `CLEAN_IDENTITY_ACCESS_SEED`.
- risk remains `HIGH` at migration level because staging bootstrap, deployed database rotation, and full clean seeder split are not complete.
- immediate ADR-0023 predictable credential boundary is fixed for source/test scope.

Superseded manifest statement:

The previous Source Inspection Update 1 statement that no environment guard was observed is now historical. It was true before the ADR-0023 implementation slice. Current source behavior has a non-local/non-testing fail-closed guard.

Remaining gaps:

- staging bootstrap is not implemented.
- full `make verify` was not run for the closure.
- production/staging deployed database credential rotation is not proven.
- old seeded credentials require manual rotation if an old seeder ever ran in a non-local database.
- default seed levels still route through legacy entrypoints.
- clean deterministic seeder namespace/folder contract is not implemented.
- broader source inspection for product, supplier, finance, load, and scenario seeders remains pending.

Next step after docs closure:

Choose one active slice only:

1. design explicit staging bootstrap with env/config credential input, fail-closed missing values, no fallback to `12345678`, no password logging, and no automatic wiring into default `DatabaseSeeder` or existing seed levels; or
2. continue source inspection for `ProductSeeder`, `SupplierSeeder`, and product scenario seeders for idempotency and clean scenario mapping.

Do not start both in the same step.
