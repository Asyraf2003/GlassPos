# DB Blueprint 0011 - Work Item Timestamp Readiness Hardening Patch Blueprint

Status: Focused Verified
Scope: `work_items`, `work_item_service_details`, `work_item_external_purchase_lines`, and `work_item_store_stock_lines` row timestamp/readiness hardening
Owner: HyperPOS

## 1. Active Table Group

Table groups:

- `work_items`
- `work_item_service_details`
- `work_item_external_purchase_lines`
- `work_item_store_stock_lines`

Category:

- Note/work item transaction line group
- P0 transaction detail/rincian group
- Finance, inventory, payment, refund, revision, and reporting-sensitive operational rows

Source-of-truth status:

- `work_items` is the operational transaction detail/root line table for Nota/Kasus Rincian.
- `work_item_service_details` stores service detail extension rows.
- `work_item_external_purchase_lines` stores external purchase detail rows.
- `work_item_store_stock_lines` stores store-stock product detail rows.

## 2. Exact Problem

The work item group is heavily used by note transaction flows, payment allocation, refund allocation, inventory movement, revision, correction, and reporting paths, but it is not yet represented in the DB audit matrix.

Current proven gap:

- `work_items` has no dedicated matrix row before this slice.
- `work_item_service_details` has no dedicated matrix row before this slice.
- `work_item_external_purchase_lines` has no dedicated matrix row before this slice.
- `work_item_store_stock_lines` has no dedicated matrix row before this slice.
- These four tables do not have `created_at`.
- These four tables do not have `updated_at`.
- Writer/create/update behavior is not yet proven to write system row timestamps.

Risk:

- Adding non-null timestamps without nullable compatibility can break direct inserts across tests and seeders.
- Treating row timestamps as business/report dates would corrupt transaction, payment, refund, inventory, and reporting semantics.
- Changing line math can break note total, payment component allocation, refund component allocation, inventory movement, COGS, and operational profit reporting.
- Changing delete/FK behavior can break correction, revision, allocation protection, and historical work item integrity.
- Adding timestamp indexes without read-path proof can create unnecessary index bloat.

## 3. Current Proven Schema

`work_items` migration:

- `database/migrations/2026_03_14_000200_create_work_items_table.php`

Proven columns:

- `id`
- `note_id`
- `line_no`
- `transaction_type`
- `status`
- `subtotal_rupiah`

`work_item_service_details` migration:

- `database/migrations/2026_03_14_000300_create_work_item_service_details_table.php`

Proven columns:

- `work_item_id`
- `service_name`
- `service_price_rupiah`
- `part_source`

`work_item_external_purchase_lines` migration:

- `database/migrations/2026_03_14_000400_create_work_item_external_purchase_lines_table.php`

Proven columns:

- `id`
- `work_item_id`
- `cost_description`
- `unit_cost_rupiah`
- `qty`
- `line_total_rupiah`

`work_item_store_stock_lines` migration:

- `database/migrations/2026_03_14_000500_create_work_item_store_stock_lines_table.php`

Proven columns:

- `id`
- `work_item_id`
- `product_id`
- `qty`
- `line_total_rupiah`

Current proven gap:

- no `created_at`
- no `updated_at`

## 4. Current Proven Foreign Key Behavior

FK migration:

- `database/migrations/2026_04_06_220200_add_v2_transaction_finance_foreign_keys.php`

Proven FK behavior:

- `work_items.note_id` references `notes.id` with `restrictOnDelete`.
- `work_item_service_details.work_item_id` references `work_items.id` with `restrictOnDelete`.
- `work_item_external_purchase_lines.work_item_id` references `work_items.id` with `restrictOnDelete`.
- `work_item_store_stock_lines.work_item_id` references `work_items.id` with `restrictOnDelete`.
- `work_item_store_stock_lines.product_id` references `products.id` with `restrictOnDelete`.
- `payment_component_allocations.work_item_id` references `work_items.id` with `restrictOnDelete`.
- `refund_component_allocations.work_item_id` references `work_items.id` with `restrictOnDelete`.

Decision:

- Do not change FK/delete semantics in this timestamp patch.
- Current restrict-on-delete behavior is preserved.

## 5. Current Proven Usage

The work item group is used by:

- note/work item writer paths
- note/work item reader/detail loader paths
- note create/update flows
- note revision and correction flows
- service-only work item flows
- service with external purchase flows
- service with store-stock part flows
- store-stock sale-only flows
- payment component allocation flows
- refund component allocation flows
- inventory movement/reversal flows
- operational profit/COGS reporting
- transaction summary reporting
- dashboard/reporting paths
- seeders and fixtures
- feature tests across Note, Payment, Inventory, and Reporting

Known high-risk semantics:

- `work_items.subtotal_rupiah` participates in note/payment/refund math.
- `work_items.status` affects workflow and payment/refund behavior.
- external purchase lines affect cost/service semantics.
- store-stock lines affect inventory movement and COGS.
- timestamps must remain system row timestamps only.

## 6. Current Proven Writer/Mutation Seams

Primary writer:

- `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php`

Proven methods:

- `create()` inserts `work_items`.
- `create()` may insert `work_item_service_details`.
- `create()` calls external purchase line insert helper.
- `create()` calls store-stock line insert helper.
- `updateStatus()` updates `work_items.status`.
- `updateServiceOnly()` updates service-only paths.
- `updateServiceWithStoreStockPartServiceFeeOnly()` updates service fee paths for store-stock service work.
- `updateServiceWithExternalPurchaseServiceFeeOnly()` updates service fee paths for external-purchase service work.

Line insert helper:

- `app/Adapters/Out/Note/WorkItemLineInsertsTrait.php`

Proven behavior:

- inserts `work_item_external_purchase_lines`
- inserts `work_item_store_stock_lines`

Update guard/helper:

- `app/Adapters/Out/Note/WorkItemServiceUpdateGuardsTrait.php`

Proven behavior:

- updates `work_items.subtotal_rupiah`
- updates `work_item_service_details.service_name`
- updates `work_item_service_details.service_price_rupiah`
- updates `work_item_service_details.part_source`

Delete helper:

- `app/Adapters/Out/Note/WorkItemDeletesTrait.php`

Proven behavior:

- deletes service/external/store-stock child rows
- deletes `work_items`
- checks refund allocation protection before delete

Readers:

- `app/Adapters/Out/Note/DatabaseNoteWorkItemDetailLoader.php`
- `app/Adapters/Out/Note/DatabaseWorkItemStoreStockLineReaderAdapter.php`

## 7. Recommended Schema Change

Create a new migration. Do not edit old migrations.

Recommended first patch:

- Add nullable-safe/backfilled `created_at` to `work_items`.
- Add nullable-safe/backfilled `updated_at` to `work_items`.
- Add nullable-safe/backfilled `created_at` to `work_item_service_details`.
- Add nullable-safe/backfilled `updated_at` to `work_item_service_details`.
- Add nullable-safe/backfilled `created_at` to `work_item_external_purchase_lines`.
- Add nullable-safe/backfilled `updated_at` to `work_item_external_purchase_lines`.
- Add nullable-safe/backfilled `created_at` to `work_item_store_stock_lines`.
- Add nullable-safe/backfilled `updated_at` to `work_item_store_stock_lines`.

Preferred semantics:

- `created_at`: system row creation/persistence timestamp.
- `updated_at`: system row mutation timestamp.
- For insert-only child rows, initial `updated_at` equals `created_at`.
- Update-capable parent/detail paths must update `updated_at` without replacing `created_at`.

Do not add timestamp indexes in this slice.

Reason:

- Existing read paths use note id, work item id, product id, transaction type, status, and report joins.
- No proven read path currently filters/sorts work item rows by `created_at` or `updated_at`.
- Index hardening must follow real read-path proof.

## 8. Backfill Policy

Do not infer work item timestamps from:

- note transaction date
- note closed/reopened date
- payment date
- refund date
- revision date
- inventory movement date
- report period
- line number
- revision order

Safe policy:

- Keep timestamp columns nullable to preserve direct insert compatibility.
- Backfill existing rows with migration execution time only if the migration explicitly updates existing rows.
- Record that historical creation time for pre-patch rows remains approximate/unknown.
- Writers must set `created_at` and `updated_at` for new rows going forward.
- Update-capable paths must update `updated_at` without replacing `created_at`.

## 9. Domain And Report Impact

Expected domain impact:

- No change to note total semantics.
- No change to work item status semantics.
- No change to payment allocation math.
- No change to refund allocation math.
- No change to inventory movement semantics.
- No change to COGS/profit reporting.
- No change to revision/correction semantics.
- No change to source id/source type semantics.
- No change to FK/delete semantics.

Forbidden impact:

- Do not use `created_at` or `updated_at` as transaction/report dates.
- Do not replace note transaction date.
- Do not change line math.
- Do not change status transition policy.
- Do not change payment/refund allocation logic.
- Do not change inventory issue/reversal math.
- Do not change supplier/procurement logic.
- Do not change UI/API/mobile behavior.
- Do not change FK/delete semantics.
- Do not add timestamp indexes without proven read-path demand.

## 10. PostgreSQL Readiness Impact

Patch must avoid:

- relying on implicit MySQL timestamp defaults as domain truth;
- unsigned-only assumptions;
- DB engine-specific timestamp side effects;
- non-portable index claims;
- report semantics tied to system timestamps.

Preferred:

- explicit application timestamp writes in work item writer paths;
- nullable direct-insert compatibility until fixture migration is intentionally handled;
- no timestamp indexes without proven read-path demand.

## 11. Files To Touch In Patch Slice

Expected production files:

- new migration under `database/migrations/`
- `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php`
- `app/Adapters/Out/Note/WorkItemLineInsertsTrait.php`
- `app/Adapters/Out/Note/WorkItemServiceUpdateGuardsTrait.php`

Potential production file if delete/rebuild timestamp behavior needs explicit coverage:

- `app/Adapters/Out/Note/WorkItemDeletesTrait.php`

Expected test files:

- focused database schema test for work item timestamp columns
- focused work item writer timestamp persistence test
- focused status/update timestamp preservation test
- focused service detail update timestamp preservation test
- focused line insert timestamp persistence test
- focused note/payment/refund/inventory/reporting non-regression tests most likely to break

Docs:

- this blueprint
- `docs/03_blueprints/db/0004_db_audit_matrix.md`

Do not touch in this slice:

- allocation math
- refund math
- inventory movement/reversal semantics
- note revision semantics
- UI
- API/mobile
- supplier payable math
- receipt stock movement logic
- proof attachment semantics
- FK/delete semantics
- timestamp indexes
- Go API
- PostgreSQL runtime

## 12. RED Proof Plan

First RED schema test must prove missing timestamps for:

- `work_items.created_at`
- `work_items.updated_at`
- `work_item_service_details.created_at`
- `work_item_service_details.updated_at`
- `work_item_external_purchase_lines.created_at`
- `work_item_external_purchase_lines.updated_at`
- `work_item_store_stock_lines.created_at`
- `work_item_store_stock_lines.updated_at`

First RED writer test must prove writer-created rows do not have system row timestamps, or are not proven to have them, for:

- `work_items`
- `work_item_service_details`
- `work_item_external_purchase_lines`
- `work_item_store_stock_lines`

RED proof must be captured before patching migration/source.

## 13. GREEN Proof Plan

Minimum targeted GREEN proof:

- schema test passes for all eight timestamp columns
- writer create test proves new parent/detail/line rows persist `created_at` and `updated_at`
- update status test proves `work_items.updated_at` changes while `created_at` is preserved
- service detail update test proves `work_items.updated_at` and `work_item_service_details.updated_at` change while `created_at` is preserved
- domain values remain unchanged:
  - `note_id`
  - `line_no`
  - `transaction_type`
  - `status`
  - `subtotal_rupiah`
  - service detail fields
  - external purchase line fields
  - store stock line fields

Focused blast-radius proof should include tests around:

- note creation with service-only row
- note creation with external purchase row
- note creation with store-stock row
- work item status update
- paid work item correction
- payment component allocation
- refund component allocation
- inventory movement/reversal
- operational profit/COGS reporting
- transaction summary reporting
- note revision/correction path

Full/global verification remains deferred unless explicitly run.

## 14. Completion Criteria

This slice can be marked `Focused Verified` only after:

- RED schema proof is captured.
- RED writer/update proof is captured where testable.
- Migration/source patch is applied.
- Targeted GREEN schema and writer/update proof passes.
- Focused note/payment/refund/inventory/reporting blast-radius proof passes.
- Matrix rows are updated with source, proof, residual gaps, and final status.
- Docs reflect remaining gaps explicitly.

Do not claim:

- full `make verify`
- browser/manual QA
- PostgreSQL runtime migration
- full DB hardening completion
## 15. Patch Implementation Summary

Status: Focused Verified.

Production files changed:

- `database/migrations/2026_05_15_000005_add_operational_timestamps_to_work_item_tables.php`
- `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php`
- `app/Adapters/Out/Note/WorkItemLineInsertsTrait.php`
- `app/Adapters/Out/Note/WorkItemServiceUpdateGuardsTrait.php`

Test files changed:

- `tests/Feature/Database/WorkItemTimestampSchemaTest.php`
- `tests/Feature/Note/WorkItemWriterTimestampFeatureTest.php`

RED schema proof:

- `WorkItemTimestampSchemaTest` failed with `Missing work_items.created_at`.
- Result: `1 failed / 1 assertion`.

RED writer proof:

- `WorkItemWriterTimestampFeatureTest` failed with SQL `Unknown column 'created_at'` on `work_items` timestamp select.
- Result: `1 failed / 0 assertions`.

GREEN targeted proof:

- `WorkItemTimestampSchemaTest` passed.
- `WorkItemWriterTimestampFeatureTest` passed create/update timestamp coverage.
- Result: `4 passed / 36 assertions`.

Focused blast-radius proof:

- Covered schema, writer create/update, service-only add, external purchase add, store-stock add, store-stock sale-only add, work item status update, service fee update writers, paid work item correction, payment allocation, selected-row refund, inventory reversal, and operational profit reporting.
- Result: `45 passed / 321 assertions`.

## 16. Remaining Gaps

- Full `make verify` has not been run for this slice.
- Browser/manual QA has not been run.
- PostgreSQL runtime migration has not been executed because PostgreSQL is not active.
- Timestamp read-path/index hardening is not approved because no real read path filters or sorts these rows by `created_at` / `updated_at`.
- `created_at` / `updated_at` remain system row timestamps only and must not be used as business/report dates.

## 17. Next Table Group

Next safe DB hardening step should select the next matrix row with `Reported`, `Audited`, or `Patch Blueprinted` status after confirming no dirty source/doc gap remains for 0011.

