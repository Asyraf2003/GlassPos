# Handoff - Product Recreated Seeder Idempotency Slice

Date: 2026-05-11

## Final Goal

Clean seeder migration for HyperPOS.

The final goal is to make seeders deterministic, rerunnable, auditable, and safe, while keeping current `database/seeders/**/*.php` as legacy compatibility surface until a clean seeder contract is proven.

## Current Scope

Active slice: product seeder source inspection and first idempotency patch.

Current target:

- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`

Supporting test:

- `tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php`

## Locked Decisions

- Local command output is the source of truth.
- Do not rename seeders.
- Do not rename Makefile targets.
- Do not mass-edit legacy seeder files.
- Do not update docs without proof.
- Do not claim full clean seeder migration.
- Do not claim full ProductSeeder idempotency.
- User handles commit/push manually.

## Previous Completed Slice

`docs/error_log/002-seeder-introduces-predictable-admin-credentials.md` was closed for the minimum ADR-0023 `UserSeeder` predictable credential boundary.

Previous proven pushed HEAD before this product slice:

- branch: `main`
- HEAD: `65a72c26`
- remote: `origin/main` aligned
- commit label: `commit 1843`

## Product Seeder Inventory

Product-related seeder files inspected:

- `database/seeders/ProductSeeder.php`
- `database/seeders/Product/ProductScenarioActiveBasicSeeder.php`
- `database/seeders/Product/ProductScenarioEditedSeeder.php`
- `database/seeders/Product/ProductScenarioSoftDeletedSeeder.php`
- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`
- `database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php`
- `database/seeders/Product/ProductSeedCatalog.php`
- `database/seeders/Product/ProductSeedThresholds.php`
- `database/seeders/Load/ProductLoadSeeder.php`
- `database/seeders/ProductInventoryThresholdBackfillSeeder.php`

`ProductSeeder` calls:

- `ProductScenarioActiveBasicSeeder`
- `ProductScenarioEditedSeeder`
- `ProductScenarioSoftDeletedSeeder`
- `ProductScenarioRecreatedSeeder`
- `ProductScenarioLegacyIncompleteSeeder`

## Source Inspection Findings

### ProductScenarioActiveBasicSeeder

Status: rerun noisy / not clean idempotent.

Reason:

- It always calls `CreateProductHandler`.
- It has no pre-check for existing product code.
- On rerun, duplicate creation returns failure and logs warning.

### ProductScenarioEditedSeeder

Status: mostly idempotent.

Reason:

- It resolves existing product id by create code or update code.
- It creates only when missing.
- It updates existing product afterward.

### ProductScenarioSoftDeletedSeeder

Status: guarded idempotent-ish.

Reason:

- It checks `productCodeAlreadySeeded()` before create/delete.
- Existing product code causes the item to be skipped.

### ProductScenarioRecreatedSeeder

Status before patch: rerun noisy.

Reason:

- It always tried to create original product, soft delete it, then create replacement.
- Original and replacement use the same `PRD-RCR-*` code.
- On rerun, active replacement already exists, so original create fails with duplicate behavior.
- The seeder logged four warnings on rerun.

### ProductScenarioLegacyIncompleteSeeder

Status: legacy compatibility / high-risk by design.

Reason:

- It directly inserts into `products`.
- It intentionally simulates incomplete legacy product history.
- It should not be treated as clean final seeder contract.

### ProductLoadSeeder

Status: mostly idempotent load path.

Reason:

- It uses create-or-update behavior by product code.

### ProductInventoryThresholdBackfillSeeder

Status: targeted backfill / non-destructive looking.

Reason:

- It updates active products with null thresholds only when inventory/costing rows exist.

## Product Application Behavior Proved

`CreateProductHandler` behavior:

- Duplicate product create returns failure.
- Duplicate create is not treated as idempotent success.

`ProductDuplicateLookupQuery` behavior:

- Duplicate lookup checks only active products using `whereNull('deleted_at')`.

Product database behavior:

- Product migrations include soft-delete columns.
- Product migrations include normalized search columns.
- Product migrations include `active_unique_marker`.
- Product migrations include threshold columns.

Writer behavior:

- `ProductWritePayloads` writes normalized fields and threshold columns.

Soft delete behavior:

- `SoftDeleteProductHandler` returns failure when product is missing or already deleted.

## RED Test Added

File:

- `tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php`

Test name:

- `test_recreated_product_scenario_can_be_rerun_without_warning_or_state_growth`

RED proof:

- Syntax passed for the test file.
- Targeted test failed.
- Failure: `Log::warning` expected exactly 0 calls, but was called 4 times.
- Result: 1 failed, 63 assertions.
- Meaning: recreated scenario state did not grow, but rerun produced 4 warning logs.

## Patch Applied Locally

File changed:

- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`

Patch summary:

- Added `Illuminate\Support\Facades\DB`.
- Added `$originalCode = trim($item['original']['code'])`.
- Added guard:

  - if product code already exists in `products`, skip the recreated scenario item.

- Changed original create call to use `$originalCode`.
- Added private method `productCodeAlreadySeeded(string $kodeBarang): bool`.

Expected behavior after patch:

- First run creates original, soft-deletes original, then creates active replacement.
- Second run sees the `PRD-RCR-*` code already exists and skips the item.
- Rerun should not emit warning.
- Product state should remain stable:
  - 8 total `PRD-RCR-*` rows
  - 4 active rows
  - 4 deleted historical rows
  - each code has 1 active row and 1 deleted row
  - thresholds are present

## GREEN Proof

Latest local proof:

- `php -l database/seeders/Product/ProductScenarioRecreatedSeeder.php` passed.
- `php -l tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php` passed.
- `php artisan test tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php` passed.
- Result: 1 passed, 63 assertions.

Latest diff proof:

- `database/seeders/Product/ProductScenarioRecreatedSeeder.php | 16 +++++++++++++++-`

Source diff summary:

- Added DB import.
- Added existing-code skip guard.
- Added helper method `productCodeAlreadySeeded()`.
## Current Important Gap

No current implementation gap remains for the
`ProductScenarioRecreatedSeeder` runtime slice at HEAD `8fcd32d8`.

Remaining migration gaps:

- `ProductScenarioActiveBasicSeeder` remains rerun noisy.
- Full `ProductSeeder` idempotency is not claimed.
- Full clean seeder migration is not claimed.
- Legacy entrypoints remain in place.
- Historical handoffs may still mention old paths from before the docs
  restructure.

## Closure Proof

Runtime source proof:

- `database/seeders/Product/ProductScenarioRecreatedSeeder.php` imports
  `Illuminate\Support\Facades\DB`.
- The seeder reads `$originalCode = trim($item['original']['code']);`.
- The seeder skips when `productCodeAlreadySeeded($originalCode)` is true.
- Original create uses `kodeBarang: $originalCode`.
- The seeder has private helper
  `productCodeAlreadySeeded(string $kodeBarang): bool`.

RED proof:

- Targeted product seeder test failed before the patch.
- Failure: `Log::warning` expected exactly 0 calls, but was called 4 times.
- Result: 1 failed, 63 assertions.

GREEN proof:

- Targeted product seeder test passed: 1 passed, 63 assertions.
- Targeted seeder tests passed after lint cleanup: 5 passed, 75 assertions.
- Full `make verify` passed:
  - PHPStan OK, no errors;
  - audit line limit SUCCESS;
  - audit Blade PHP/directive SUCCESS;
  - contract audit passed;
  - Pest 936 passed, 5024 assertions.

Commit/push proof:

- Latest verified baseline after docs restructure path fix:
  - branch `main`;
  - HEAD `8fcd32d8`;
  - remote aligned with `origin/main`;
  - commit label `commit 1850`.

## Required Next Active Step

Inspect and characterize `ProductScenarioActiveBasicSeeder` rerun noisy behavior.

Do not patch runtime until a RED characterization test proves the current noisy
rerun behavior.

## Expected Next Proof

Expected RED proof:

- product seeder rerun emits warning for active-basic scenario, or
- product seeder rerun produces another measurable non-idempotent behavior.

Expected GREEN proof after a future patch:

- targeted active-basic seeder test passes;
- focused product seeder blast-radius tests pass;
- no warning logs are emitted for the fixed scenario;
- product state remains stable.

## Do Not Claim Yet

Do not claim:

- `ProductScenarioActiveBasicSeeder` fixed;
- full `ProductSeeder` idempotency;
- full clean seeder migration;
- legacy seeder replacement complete;
- staging/production seeder safety complete.

## Recommended Closure Path

This handoff is now closed for `ProductScenarioRecreatedSeeder`.

Next session should start from `ProductScenarioActiveBasicSeeder` only, unless
the owner chooses a higher-priority seeder risk.

## Progress Snapshot

Final Goal Progress: 17% for clean seeder migration.

Governance Docs Foundation Progress: 100%.

Product Source Inspection Progress: 80%.

Product Recreated Seeder Runtime Progress: 100%.

Product Docs Closure Progress: 100% after this docs-only closure is verified.

Session Context Health: 35%, safe.
