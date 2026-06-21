# Blueprint 0015 - Service Package Profit Breakdown Source Contract

Status:
Draft / Source Contract / No Patch Yet

Links:
- [0038 audit findings](../../04_lifecycle/error_log/0038_cashier_note_create_edit_refund_reporting_audit_findings.md)
- [0011 workflow index](0011_cashier_note_consistency_workflow_index.md)

Scope:
Service Package Profit Breakdown adalah report terpisah dari Operational Profit. Operational Profit tetap laporan kas operasional dan formulanya tidak diubah.

Direction locked by Owner Decision V2:
- Source contract harus mendukung future flexible package.
- Basis reporting adalah kombinasi `transaction_date`, `payment_date`, `refund_date`, dan `movement_date`.
- `payment_date` dan `refund_date` tetap basis realisasi uang.
- `transaction_date` tetap konteks kapan package dibuat/dijual.
- `movement_date` tetap basis inventory/COGS.

## Source Data
- work_items: package row identity, transaction type, active/canceled/refunded status.
- work_item_service_details: service price, package_profit_rupiah, package_base_service_price_rupiah, package_service_extra_rupiah.
- work_item_store_stock_lines: sparepart sales line totals.
- work_item_external_purchase_lines: external purchase snapshot jika tetap dibutuhkan sebagai domain terpisah, bukan package auto split utama.
- note_revision_lines payload: candidate historical fingerprint after Phase 3 decision.
- payment_component_allocations: paid component allocation by current replacement component.
- refund_component_allocations: refunded component allocation and netting source.
- inventory_movements: store-stock original COGS and reversal COGS.

## Field Minimal
- package_total_rupiah
- parts_total_rupiah
- service_price_rupiah
- sparepart_sales_total_rupiah
- sparepart_cogs_rupiah
- sparepart_margin_rupiah
- base_service_price_rupiah
- package_service_extra_rupiah
- package_profit_rupiah
- total_service_component_rupiah
- total_package_gross_profit_rupiah

## Formula Candidate
- package_total_rupiah = final customer package total from historical package row.
- parts_total_rupiah = historical sum of raw package part sales snapshot.
- service_price_rupiah = historical package service price snapshot after package-aware recalculation.
- sparepart_sales_total_rupiah = sum store_stock line totals for package rows.
- sparepart_cogs_rupiah = sum ABS stock_out total_cost_rupiah for linked store_stock lines minus linked reversal costs, depending on selected date basis.
- sparepart_margin_rupiah = sparepart_sales_total_rupiah - sparepart_cogs_rupiah.
- base_service_price_rupiah = package_base_service_price_rupiah if present; otherwise owner decision required for non-template branch.
- package_service_extra_rupiah = package_service_extra_rupiah if present; otherwise 0.
- package_profit_rupiah = package_profit_rupiah if present; otherwise 0.
- total_service_component_rupiah = package-aware service component from full payload fields, after correction-aware recalculation; exact formula when base snapshot missing is `needs characterization`.
- total_package_gross_profit_rupiah = sparepart_margin_rupiah + total_service_component_rupiah.

## Basis Tanggal Decision
Owner Decision V2 sudah memilih basis kombinasi:
- `transaction_date`: konteks package dibuat/dijual.
- `payment_date`: realisasi uang masuk.
- `refund_date`: realisasi uang keluar.
- `movement_date`: inventory/COGS.

No patch may start for report query phase until field mapping for combination basis is characterized.

## Historical Snapshot Rules
- Historical money must come from work item rows, service detail package fields, note revision payload, payment/refund allocations, or inventory movements.
- Product current price must not change old package sales.
- Product current name may be display-only; report money cannot depend on it.
- Current AVG/modal must not change historical COGS.
- Service catalog/template current defaults must not change old package service values.
- Refund package breakdown must not double count service_fee and package_profit.
- Revision payload minimal harus memuat `package_total_rupiah`, `parts_total_rupiah`, `service_price_rupiah`, `package_base_service_price_rupiah`, `package_service_extra_rupiah`, `package_profit_rupiah`, `total_service_component_rupiah`, `store_stock_lines` snapshot, dan `external_purchase_lines` snapshot jika ada.
- Payment/refund allocation references dimasukkan jika memang dibutuhkan report; exact field set `needs characterization`.

## Current Master Data Leak Risks
- Phase 5 source contract: refund allocations are now component-aware; product/store-stock components are default refundable, service_fee and external purchase components are default blocked, and package refunds must avoid double-counting raw components in future breakdown reports.
- Inventory current snapshot joins products/current costing and is not a historical package profit source.
- Phase 3 local source contract: revision payload now writes `package_profit_rupiah`, `package_base_service_price_rupiah`, `package_service_extra_rupiah`, and `total_service_component_rupiah`; report query remains deferred.
- UI/backend mismatch can create different package shapes unless Phase 4 locks the contract.
- External package backend exists, but owner direction justru memisahkan external purchase dari package auto split utama.

## Required Tests Before Report UI
- normal template package.
- non-template package.
- multi-product package.
- external purchase separate-domain behavior if it still affects report source.
- package after edit upward and downward.
- package after selected-row refund.
- package after product price update.
- package after AVG update.
- package after service template update.
- basis tanggal behavior across transaction/payment/refund/movement dates.
- package-aware correction behavior and base-missing fallback characterization.
- no Operational Profit formula regression.

Evidence:
- Operational Profit separation: `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:9`, `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:23`, `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:27`, `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:72`
- Existing package fields in create mapper/service detail writer: `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:48`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:49`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:50`, `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php:39`, `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php:40`, `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php:41`
- Template branch package field calculation: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:38`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:39`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:40`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:41`
- Non-template branch package fields: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:57`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:69`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:70`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:71`
- Payment/refund component source: `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:36`, `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:50`, `app/Application/Payment/Services/AllocateRefundAcrossComponents.php:36`, `app/Application/Payment/Services/AllocateRefundAcrossComponents.php:65`
- Inventory movement COGS source: `app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php:31`, `app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php:77`
- Current inventory snapshot leak risk: `app/Adapters/Out/Reporting/InventoryCurrentSnapshotDatabaseQuery.php:13`, `app/Adapters/Out/Reporting/InventoryCurrentSnapshotDatabaseQuery.php:38`, `app/Adapters/Out/Reporting/InventoryCurrentSnapshotDatabaseQuery.php:39`
- Combination date basis, future flexible package support, and full payload fingerprint lock: owner decision V2 from current discussion

Progress Local:
- Status: Phase 3 fingerprint fixed locally
- Last checked: 2026-06-21
- Last evidence: Phase 3 revision payload historical fingerprint GREEN. `php artisan test --filter=NoteRevisionLinePayloadMapperTest`, `php artisan test --filter=EditTransactionWorkspaceRevisionPaymentCharacterizationTest`, `php artisan test --filter=EditTransactionWorkspacePackageAutoSplitCharacterizationTest`, `php artisan test --filter=CreateTransactionWorkspaceLineTypeCharacterizationTest`, `php artisan test --filter=CorrectPaidServiceWithStoreStockPartServiceFeeOnly`, and `make verify` GREEN: 1275 passed, 7417 assertions.
- Current behavior found:
  - Future package breakdown source remains `payment_component_allocations`, `refund_component_allocations`, and `inventory_movements`.
  - Operational Profit remains separate cash-operational report and does not expose package breakdown fields.
  - InventoryCurrentSnapshotDatabaseQuery remains current product inventory/costing snapshot, not historical package profit source.
  - Refund allocation shape uses raw component types (`service_store_stock_part`, `service_fee`, `service_external_purchase_part`, `product_only_work_item`) and does not create a package aggregate component.
  - Revision payload now snapshots minimal package fingerprint fields from work item/service detail/store-stock snapshots; `total_service_component_rupiah` is the historical service detail component total (`service_price_rupiah + package_profit_rupiah`).
- Gap summary:
  - Phase 6 candidate: explicit Service Package Profit Breakdown source contract across `transaction_date`, `payment_date`, `refund_date`, and `movement_date`.
  - Double-count guard candidate: avoid counting `service_fee`, package service fields, and package profit fields twice once breakdown report exists.
- Next action: Prepare Phase 6 report query explicitly; do not change Operational Profit formula.
- Owner decision dependency: none for V2 direction; exact base-missing formula remains future characterization/hardening input.
