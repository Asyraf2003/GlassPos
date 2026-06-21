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
- UI Blade/JS: Phase 4 row is owner-facing label + total biaya keluar.
- Request validation: external line 0 label/qty/unit_cost and `total_rupiah` accepted for compatibility; total-only is valid for normal external purchase.
- Normalizer: create item normalizer preserves first external purchase line including `total_rupiah`.
- Mapper: create mapper runs external package composer then returns `TYPE_SERVICE_WITH_EXTERNAL_PURCHASE` when external lines exist; `total_rupiah` is persisted internally as `qty=1`, `unit_cost_rupiah=total_rupiah`.
- Domain object: `WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE`.
- Persisted tables: notes, work_items, work_item_service_details, work_item_external_purchase_lines; payment tables if paid.
- Payment allocation: external part component(s) then service_fee.
- Inventory movement: none.
- Report impact: external purchase cost in Operational Profit uses transaction_date and refund netting uses refunded_at.
- Current tests: `CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest` includes backend package path; `CreateTransactionWorkspaceLineTypeCharacterizationTest` covers label + total UI/source contract.
- Phase 4 fixed locally: normal external purchase accepts owner-facing label + total. External purchase remains a separate domain; backend `package_auto_split` path remains blocked.

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
- UI Blade/JS: Phase 4 `service_store_stock` template sets `pricing_mode=package_auto_split`, `requires_service_product_template=1`, package total, and a product-line template/add button for many product/sparepart lines.
- Request validation: accepts `manual_split`, `package_auto_split`, `package_total_rupiah`, and product_lines.
- Normalizer: product_lines preserved by create item normalizer; browser draft/preload now preserves multiple service_store_stock product lines.
- Mapper: create mapper composes store-stock package pricing then maps store lines and service.
- Domain object: `WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART`.
- Persisted tables: notes, work_items, work_item_service_details, work_item_store_stock_lines, inventory_movements; payment tables if paid.
- Payment allocation: store-stock part component per store line, then service_fee.
- Inventory movement: stock_out per store-stock line.
- Report impact: transaction gross, payment/refund cash, COGS from inventory_movements, package breakdown future source.
- Current tests: backend multi-product package create tests exist in `CreateTransactionWorkspaceServiceStoreStockFeatureTest`; UI/source contract and template preset multi-product extension are covered in `CreateTransactionWorkspaceLineTypeCharacterizationTest`.
- Gaps: many service components remain deferred; current Phase 4 target is one main service plus many product/sparepart lines.

## Package Auto Split Create Map
- manual_split: request accepts it; advanced/UI visibility needs re-check.
- package_auto_split: request accepts it and package_total.
- template branch: Phase 4 supports `requires_service_product_template=1` with multiple product_lines by using the first/primary product line's active template as the preset and including all product lines in sparepart total.
- non-template branch: service price is package_total minus sparepart total; package profit/base/extra become zero/null.
- external package branch: backend `package_auto_split` with `external_purchase_lines.total_rupiah` remains blocked because external purchase stays a separate domain.

Evidence:
- Work item constants: `app/Core/Note/WorkItem/WorkItem.php:12`, `app/Core/Note/WorkItem/WorkItem.php:13`, `app/Core/Note/WorkItem/WorkItem.php:14`, `app/Core/Note/WorkItem/WorkItem.php:15`
- Create request fields: `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:23`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:26`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:28`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:35`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:40`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:46`
- Create normalizer: `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceInputNormalizer.php:13`
- Create mapper: `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:29`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:42`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:54`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:58`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:62`
- Payment components: `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:22`, `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:36`, `app/Application/Payment/Services/PayableComponentsFromWorkItem.php:50`
- UI service_store_stock contract: `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:14`, `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:15`, `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:119`, `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:144`
- UI external purchase label + total: `resources/views/cashier/notes/workspace/partials/templates/service-external.blade.php:47`
- UI multi-line preload/draft: `public/assets/static/js/pages/cashier-note-workspace/rows.js:141`, `public/assets/static/js/pages/cashier-note-workspace/rows.js:271`, `public/assets/static/js/pages/cashier-note-workspace/draft.js:245`
- Template/non-template package branch: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:22`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:34`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:63`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:65`
- External purchase total mapper: `app/Application/Note/Services/CreateTransactionWorkspaceExternalPurchaseLineMapper.php:18`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceWorkItemVariantResolver.php:34`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceGrandTotalLineCalculator.php:24`
- External package branch: `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:17`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:22`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:28`
- Flexible package direction, template as preset, and external purchase as separate domain: owner decision V2 from current discussion
- Phase 4 close note: template preset multi-product extension is now supported for store-stock packages; external purchase normal row is label + total; external purchase package auto split remains blocked.

Progress Local:
- Status: FIXED
- Last checked: 2026-06-21
- Last evidence: Phase 4 UI flexible package GREEN. Targeted filters GREEN: `CreateTransactionWorkspaceLineTypeCharacterizationTest`, `CreateTransactionWorkspaceServiceStoreStockFeatureTest`, `CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest`, `CreateTransactionWorkspaceTemplateContractFeatureTest`, `CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest`, `CashierWorkspaceServiceProductTemplateAutofillContractFeatureTest`, `EditTransactionWorkspacePackageAutoSplitCharacterizationTest`, `EditTransactionWorkspaceRevisionPaymentCharacterizationTest`, `NoteRevisionLinePayloadMapperTest`, `CorrectPaidServiceWithStoreStockPartServiceFeeOnly`. `make verify` GREEN: 1275 passed, 7423 assertions.
- Current behavior:
  - service_store_stock UI supports one service + many product/sparepart lines.
  - template preset multi-product extension is supported using the primary product line active template.
  - external purchase owner-facing label + total is supported and persisted internally as qty 1/unit cost total.
  - external package_auto_split remains blocked.
- Next action: Prepare Phase 5 refund component-type policy; do not start report query.
- Tests linked: CreateTransactionWorkspaceLineTypeCharacterizationTest, CreateTransactionWorkspaceServiceStoreStockFeatureTest, CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest, CreateTransactionWorkspaceTemplateContractFeatureTest, CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest.
- Owner decision dependency: none for Phase 4 target; many service components remain deferred.
