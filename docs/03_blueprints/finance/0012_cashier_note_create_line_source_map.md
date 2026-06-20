# Blueprint 0012 - Cashier Note Create Line Source Map

Status:
Draft / Source Map / No Patch Yet

Links:
- [0038 audit findings](../../04_lifecycle/error_log/0038_cashier_note_create_edit_refund_reporting_audit_findings.md)
- [0011 workflow index](0011_cashier_note_consistency_workflow_index.md)

Scope:
Create nota kasir sebagai source of truth untuk line semantics, subtotal, payment component allocation, inventory movement, dan report source.

Direction locked by Owner Decision V2:
- Target awal flexible package adalah satu package row dengan satu service utama dan banyak product/sparepart lines.
- Target lanjutan adalah banyak service components dan banyak product lines setelah source contract stabil.
- Template diposisikan sebagai package preset.
- External purchase tetap domain sendiri dengan UI sederhana: label + total biaya keluar.

## Line Type: service_only
- UI Blade/JS: service row; needs re-check for exact partial path.
- Request validation: `entry_mode=service`, `part_source=none`, service fields in `StoreTransactionWorkspaceRules`.
- Normalizer: `StoreTransactionWorkspaceInputNormalizer` normalizes note/items/inline_payment.
- Mapper: `CreateTransactionWorkspaceWorkItemPayloadMapper` falls through to `TYPE_SERVICE_ONLY`.
- Domain object: `WorkItem::TYPE_SERVICE_ONLY`.
- Persisted tables: notes, work_items, work_item_service_details; payment tables if paid.
- Payment allocation: one `service_fee` component.
- Inventory movement: none.
- Report impact: transaction summary gross; cash ledger by payment/refund; Operational Profit cash-in/refund only.
- Current tests: `CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest`; exact coverage needs re-check.
- Gaps: correction fee-only is existing path; package-specific risk not applicable unless mixed with package route.

## Line Type: service_with_external_purchase
- UI Blade/JS: current row masih terlihat berorientasi label/qty/unit_cost; owner-facing target adalah label + total biaya keluar saja.
- Request validation: external line 0 label/qty/unit_cost and `total_rupiah` accepted.
- Normalizer: create item normalizer preserves first external purchase line.
- Mapper: create mapper runs external package composer then returns `TYPE_SERVICE_WITH_EXTERNAL_PURCHASE` when external lines exist.
- Domain object: `WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE`.
- Persisted tables: notes, work_items, work_item_service_details, work_item_external_purchase_lines; payment tables if paid.
- Payment allocation: external part component(s) then service_fee.
- Inventory movement: none.
- Report impact: external purchase cost in Operational Profit uses transaction_date and refund netting uses refunded_at.
- Current tests: `CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest` includes backend package path.
- Phase 2 guard fixed locally: external package auto split backend path is blocked until an explicit label + total contract exists. External purchase remains a separate domain; UI simplification remains deferred to Phase 4.

## Line Type: store_stock_sale_only
- UI Blade/JS: product row; exact partial needs re-check.
- Request validation: `entry_mode=product`, product_lines fields.
- Normalizer: product lines normalized by item normalizer.
- Mapper: create mapper returns `TYPE_STORE_STOCK_SALE_ONLY` for product entry.
- Domain object: `WorkItem::TYPE_STORE_STOCK_SALE_ONLY`.
- Persisted tables: notes, work_items, work_item_store_stock_lines, inventory_movements; payment tables if paid.
- Payment allocation: one product-only component for work item.
- Inventory movement: stock_out linked to work_item_store_stock_line.
- Report impact: transaction summary gross; cash ledger by payment/refund; inventory and COGS from inventory_movements.
- Current tests: create/product and inventory tests exist; exact coverage needs re-check.
- Gaps: product-only UI appears single-line; multi-line behavior outside package needs re-check.

## Line Type: service_with_store_stock_part
- UI Blade/JS: current `service_store_stock` template sets `pricing_mode=package_auto_split`, `requires_service_product_template=1`, and package total.
- Request validation: accepts `manual_split`, `package_auto_split`, `package_total_rupiah`, and product_lines.
- Normalizer: product_lines preserved by create item normalizer; exact multi-line browser contract is blocked by UI JS.
- Mapper: create mapper composes store-stock package pricing then maps store lines and service.
- Domain object: `WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART`.
- Persisted tables: notes, work_items, work_item_service_details, work_item_store_stock_lines, inventory_movements; payment tables if paid.
- Payment allocation: store-stock part component per store line, then service_fee.
- Inventory movement: stock_out per store-stock line.
- Report impact: transaction gross, payment/refund cash, COGS from inventory_movements, package breakdown future source.
- Current tests: backend multi-product package create tests exist in `CreateTransactionWorkspaceServiceStoreStockFeatureTest`.
- Gaps: current UI JS blocks/preloads only one product line, padahal arah final adalah flexible package multi-part bertahap.

## Package Auto Split Create Map
- manual_split: request accepts it; advanced/UI visibility needs re-check.
- package_auto_split: request accepts it and package_total.
- template branch: current behavior rejects product_lines count not equal to 1 and fills package_profit/base/extra; Owner Decision V2 positions template as preset, not permanent boundary.
- non-template branch: service price is package_total minus sparepart total; package profit/base/extra become zero/null.
- external package branch: Phase 2 guard blocks backend `package_auto_split` with `external_purchase_lines.total_rupiah` until explicit owner-facing label + total contract is implemented.

Evidence:
- Work item constants: `app/Core/Note/WorkItem/WorkItem.php:12`, `app/Core/Note/WorkItem/WorkItem.php:13`, `app/Core/Note/WorkItem/WorkItem.php:14`, `app/Core/Note/WorkItem/WorkItem.php:15`
- Create request fields: `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:23`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:26`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:28`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:35`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:40`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:46`
- Create normalizer: `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceInputNormalizer.php:13`
- Create mapper: `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:29`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:42`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:54`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:58`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:62`
- Payment components: `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:22`, `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:36`, `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:50`
- UI service_store_stock contract: `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:14`, `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:15`, `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:140`
- UI multi-line limit: `public/assets/static/js/pages/cashier-note-workspace/rows.js:80`, `public/assets/static/js/pages/cashier-note-workspace/rows.js:81`, `public/assets/static/js/pages/cashier-note-workspace/rows.js:268`
- Template/non-template package branch: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:22`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:38`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:57`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:69`
- External package branch: `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:19`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:30`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:37`
- Flexible package direction, template as preset, and external purchase as separate domain: owner decision V2 from current discussion
- Phase 2 close note: backend guards now prevent premature template multi-product preset extension and external purchase package backend auto split before Phase 4 UI/source contract work.

Progress Local:
- Status: FIXED
- Last checked: 2026-06-21
- Last evidence: Phase 2 hardening guard GREEN. Template preset multi-product extension remains blocked until Phase 4 contract; external purchase package backend path is rejected until explicit label + total contract exists.
- Next action: Use this create/package map as Phase 3 context only; do not reopen external package auto split before Phase 4/source contract work.
- Tests linked: CreateTransactionWorkspaceLineTypeCharacterizationTest, CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest, PackageAutoSplitCreateReportImpactFeatureTest.
- Owner decision dependency: none for Phase 2; flexible package UI remains deferred to Phase 4.
