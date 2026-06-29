# Service Catalog Lookup Migration Handoff

## Metadata
- Date: 2026-06-05
- Slice / topic: Cashier note service catalog lookup and migration backfill
- Workflow step: Service autocomplete defect closure and PostgreSQL-readiness documentation
- Status: Focused verified
- Progress: 100% for this slice

## Target Work Page
Cashier create/edit note workspace service-name autocomplete for service-only, service + store-stock product, and service + external product rows.

## References Used
- Blueprint: user request for service default pricing and mobile-centric cashier note workflow
- Workflow: AI_RULES one active step, proof-first execution
- DoD: endpoint works from actual development DB, default service master exists without manual seed, `make verify` green
- ADR: none created in this slice
- Previous handoff: `docs/04_lifecycle/handoff/0017_note_services_domain_rule_audit_handoff.md`
- Repo snapshot / command output:
  - `php artisan migrate`
  - development DB read for `service_catalog_items`
  - `php artisan test tests/Feature/Note/TransactionWorkspaceServiceCatalogSyncFeatureTest.php tests/Feature/Note/ServiceCatalogEndpointFeatureTest.php`
  - `make verify`

## Locked Facts
- The cashier UI service autocomplete initially did not show saved services in the browser because the development database did not yet have the `service_catalog_items` table.
- Route wiring existed before the DB fix:
  - `cashier.notes.services.lookup`
  - `cashier.notes.services.store`
  - `admin.notes.services.lookup`
  - `admin.notes.services.store`
- After running `php artisan migrate`, the development database had `12` `service_catalog_items` rows.
- Default service rows include `Sok Kopling (Besar)`, `Sok Kopling (Kecil)`, setting in/ex variants, bosklep in/ex variants, and pasang stang variants.
- Service lookup supports:
  - full normalized query such as `sok kopling besar`
  - partial token query such as `sok besar`
  - query-less open/focus list for saved service names
- `sok kopling besar` and `sok kopling (besar)` normalize to the same key, `sok kopling besar`.

## Scope Used
### SCOPE-IN
- Service catalog table creation.
- Default service master backfill.
- Cashier/admin service lookup endpoints.
- Create/edit note workspace service autocomplete behavior.
- Documentation for migration and handoff.

### SCOPE-OUT
- Go Echo API implementation.
- PostgreSQL cutover.
- Live MySQL data export/import.
- Full DB parity harness.
- Changing financial transaction source-of-truth tables.

## GAP
- PostgreSQL production cutover is not proven by this slice.
- There is no completed PostgreSQL runtime test suite proof in this slice.
- Live MySQL to PostgreSQL transition still needs row-count parity, rupiah reconciliation, inventory reconciliation, audit/projection rebuild proof, and rollback runbook.

## Locked Decisions
- Default service names belong in `service_catalog_items`, not hardcoded only in JavaScript.
- Missing service catalog rows are created idempotently when service names are used in create/edit note flows.
- Existing catalog default prices are not overwritten by cashier-entered prices during transaction entry.
- The transaction remains the financial source of truth; service catalog is only a default/suggestion source.
- A forward migration backfills the 12 default service rows so existing development/live-like MySQL databases do not depend on manual seeding.

## Files Created / Changed
### New files
- `database/migrations/2026_06_04_000100_create_service_catalog_items_table.php`
- `database/migrations/2026_06_04_000200_seed_default_service_catalog_items.php`
- `app/Core/ServiceCatalog/ServiceNameNormalizer.php`
- `app/Core/ServiceCatalog/ServiceCatalogItem.php`
- `app/Ports/Out/ServiceCatalog/ServiceCatalogReaderPort.php`
- `app/Ports/Out/ServiceCatalog/ServiceCatalogWriterPort.php`
- `app/Adapters/Out/ServiceCatalog/DatabaseServiceCatalogAdapter.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/ServiceCatalogLookupController.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/ServiceCatalogStoreController.php`
- `app/Application/Note/Services/ServiceCatalogFromWorkItemSync.php`
- `public/assets/static/js/pages/cashier-note-workspace/service-catalog.js`
- `tests/Unit/Core/ServiceCatalog/ServiceNameNormalizerTest.php`
- `tests/Feature/Note/ServiceCatalogEndpointFeatureTest.php`
- `tests/Feature/Note/TransactionWorkspaceServiceCatalogSyncFeatureTest.php`

### Changed files
- `routes/web/note.php`
- `app/Providers/ProductCatalogServiceProvider.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspacePageController.php`
- `app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php`
- `app/Application/Note/Services/EditTransactionWorkspaceRouteNames.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php`
- `resources/views/cashier/notes/workspace/create.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service-external.blade.php`
- `resources/views/cashier/notes/workspace/partials/dropdown-layer-fix.blade.php`
- `public/assets/static/js/pages/cashier-note-workspace/rows.js`
- `public/assets/static/js/pages/cashier-note-workspace/search.js`
- `database/seeders/CreateOnly/Support/CreateOnlyMasterSeeder.php`
- `database/seeders/CreateOnly/CreateMasterBasicSeeder.php`
- `database/seeders/CreateOnly/CreateMasterDenseWeekSeeder.php`
- `database/seeders/CreateOnly/CreateMasterDenseYearSeeder.php`
- `database/migrations/README.md`

## Verification Proof
- command:
  - `php artisan migrate`
  - result: `2026_06_04_000100_create_service_catalog_items_table` and `2026_06_04_000200_seed_default_service_catalog_items` ran successfully.
  - meaning: development database has the table and default rows needed by browser lookup.
- command:
  - development DB read for `service_catalog_items`
  - result: count `12`, sample rows included `Pasang Stang (Kecil)`, `Bosklep In (Besar)`, `Sok Kopling (Besar)`, `Setting In (Kecil)`, `Setting Ex (Kecil)`.
  - meaning: the browser endpoint is no longer blocked by a missing table or missing default data.
- command:
  - `php artisan test tests/Feature/Note/TransactionWorkspaceServiceCatalogSyncFeatureTest.php tests/Feature/Note/ServiceCatalogEndpointFeatureTest.php`
  - result: `9 passed (26 assertions)`.
  - meaning: lookup, default list, partial token matching, create-if-missing, and non-overwrite behavior are covered.
- command:
  - `make verify`
  - result: `2 skipped, 1167 passed (6581 assertions)`.
  - meaning: repository verification is green after the migration and documentation slice.

## PostgreSQL Migration Assessment
- The new `service_catalog_items` table migration uses portable Laravel schema-builder primitives:
  - string primary key
  - string unique normalized name
  - integer rupiah default price
  - boolean active flag
  - timestamps
- The default data migration uses Laravel query builder and `Str::uuid()`, not MySQL-only SQL.
- No new MySQL-only constructs were introduced:
  - no unsigned integer
  - no enum
  - no `after()`
  - no `change()`
  - no raw MySQL DDL
  - no auto-increment domain identity
- Allowed claim: this service catalog slice is PostgreSQL-aligned as a fresh-schema migration.
- Forbidden claim: the full application is PostgreSQL production-cutover ready.

## Risks / Follow-up Notes
- Browser may need a hard refresh if stale `service-catalog.js` is cached.
- If a deployed environment already ran the create-table migration but not the default-data migration, run pending migrations normally, not `migrate:fresh`.
- Future PostgreSQL cutover still requires dedicated parity proof outside this slice.

## Next Step
If the owner wants to continue DB hardening, the next valid step is PostgreSQL compatibility classification for remaining migrations or a PostgreSQL test harness dry-run, not Go API implementation yet.

## Session Update - 0052 Inventory Average Cost Rounding Residual Visibility

### Status

PATCHED - dataset-level diagnostic visibility proof PASS.

### Context

Previous slice `0051` fixed the costing projection engine mismatch.

Remaining value gap is true integer average-cost rounding residual:

```text
rounding_residual = inventory_value_rupiah - (avg_cost_rupiah * qty_on_hand)
```

Known post-rebuild residual:

```text
prod-year-001: 3
prod-year-006: 23
total residual: 26
```

### Files Changed

- `app/Adapters/Out/Reporting/InventoryCurrentSnapshotDatabaseQuery.php`
- `app/Adapters/Out/Reporting/InventoryCurrentSnapshotRowMapper.php`
- `app/Application/Reporting/Services/InventoryStockValueReportSummaryBuilder.php`
- `tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php`
- `docs/04_lifecycle/error_log/0052_inventory_average_cost_rounding_residual_visibility.md`

### Acceptance Proof

Owner reported:

```text
GetInventoryStockValueReportDatasetFeatureTest PASS
all relevant tests PASS
```

### Boundary

- No costing engine change.
- No HPP change.
- No aggressive main report value change.
- Dataset now separates ledger mismatch from rounding residual.

### Next

Continue with export/UI diagnostic visibility only after this handoff is committed.

## Session Update - 0053 Inventory Rounding Residual Report Presentation

### Status

PATCHED - page, Excel, PDF summary builder, and targeted tests PASS.

### Context

Slice `0052` made rounding residual and ledger diff visible at dataset level.

Slice `0053` completes presentation-level visibility.

### Failing Proof First

Initial presentation tests showed:

```text
Page FAIL:
Expected HTML to contain "Nilai Berdasar Avg x Qty"

Excel FAIL:
Summary sheet did not contain "Nilai Berdasar Avg x Qty"

PDF view PASS:
PDF view received injected summaryItems directly
```

### Patch Summary

Added report presentation for:

```text
Nilai Berdasar Avg x Qty
Residual Pembulatan HPP
Selisih Qty Ledger
Selisih Nilai Ledger
```

Presentation layers updated:

- Page summary UI
- Excel Ringkasan sheet
- Excel Snapshot Stok sheet
- PDF summary data builder

### Regression During Patch

Excel export initially failed because ledger-only products were included as snapshot rows.

Fix:

- Retain movement ledger join for diagnostic diff.
- Do not include ledger-only products in current snapshot filter.

Snapshot inclusion remains based on current inventory and/or current costing rows.

### Files Changed

- `app/Adapters/Out/Reporting/InventoryCurrentSnapshotDatabaseQuery.php`
- `app/Adapters/Out/Reporting/InventoryStockValueSummaryDatabaseQuery.php`
- `resources/views/admin/reporting/inventory_stock_value/index.blade.php`
- `app/Application/Reporting/Exports/InventoryStockValueReportExcelSummarySheetWriter.php`
- `app/Application/Reporting/Exports/InventoryStockValueReportExcelSnapshotSheetWriter.php`
- `app/Application/Reporting/Exports/InventoryStockValueReportPdfViewDataBuilder.php`
- `tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php`
- `tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php`
- `tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php`
- `docs/04_lifecycle/error_log/0053_inventory_rounding_residual_report_presentation.md`

### Acceptance Proof

Owner reported:

```text
PASS all targeted tests
20 passed
```

Proof command:

```bash
php artisan test \
  tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php \
  tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php \
  tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php \
  tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php
```

### Boundary

- No costing engine change.
- No HPP change.
- No main inventory value semantics change.
- Residual is now visible as diagnostic, not treated as ledger mismatch.

## Session Update - 0057 Inventory Deleted Product Movement Report Visibility

### Status

Resolved.

### Scope

Slice 0057 memastikan movement report tetap audit-friendly ketika produk sudah soft-deleted atau ketika ada legacy/corrupt movement dengan `product_id` orphan/missing.

### Files Changed

- `app/Adapters/Out/Reporting/InventoryMovementSummaryDatabaseQuery.php`
- `tests/Feature/Reporting/InventoryDeletedProductMovementReportVisibilityFeatureTest.php`
- `docs/04_lifecycle/error_log/0057_inventory_deleted_product_movement_report_visibility.md`
- this handoff file

### FACT

- Movement summary tetap memakai `inventory_movements` sebagai source utama.
- Join ke `products` tetap `leftJoin`.
- Current snapshot tetap source dari `products` aktif dan tetap exclude deleted/orphan/ledger-only product.
- No costing engine changes.
- No HPP changes.
- No `inventory_value_rupiah` semantic changes.
- No migration.
- No source-type bucket membership changes.
- No production repair/write.

### Behavior Locked

- Active product movement displays normal product name.
- Soft-deleted product movement displays `[Produk terhapus] {nama_barang}`.
- Orphan product movement displays `[Produk tidak ditemukan: {product_id}]`.
- Deleted/orphan movement contributes to period movement summary.
- Deleted/orphan product does not enter current snapshot.

### Proof

Owner reported targeted regression PASS:

```bash
php artisan test \
  tests/Feature/Reporting/InventoryDeletedProductMovementReportVisibilityFeatureTest.php \
  tests/Feature/Reporting/GetInventoryMovementSummaryFeatureTest.php \
  tests/Feature/Reporting/InventoryMovementSummaryHardeningFeatureTest.php \
  tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php
```

Result:

```text
PASS
```

