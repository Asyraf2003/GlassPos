# 0010 - Service Product Template Fast Entry Blueprint

## Metadata

- Date: 2026-06-18
- Area: Finance / Note Workspace / Product Catalog / Service Catalog
- Status: Approved direction, not implemented
- Implementation state: Pending
- Source ADR: docs/02_architecture/adr/0031_service_product_template_fast_entry.md
- Source workflow: docs/01_standards/workflow/0025_service_product_template_fast_entry_implementation_workflow.md
- Related current flow:
  - service catalog lookup
  - service catalog create-if-missing
  - service with store stock part
  - package auto split

## FACT

The current note workspace already supports product-only, service-only, service with store stock part, and package auto split transaction shapes.

The current package auto split rule is:

```text
service_price_rupiah = package_total_rupiah - product_total_rupiah
```

The current product lookup uses product catalog `harga_jual` as the default product unit price.

Therefore, product `harga_jual` must remain pure product sale price and must not store service-inclusive package totals.

The workshop needs a fast cashier workflow where selecting a product in Service x Product mode can autofill the linked service and default nominal values.

The 20/80 explanation used during analysis is only a manual explanation for understanding a package total. It is not system logic and must not be stored in this implementation.

## DECISION

Create a service-product template feature.

A service-product template connects:

- one product,
- one service catalog item,
- default service price,
- optional default package total,
- active/inactive status,
- sort order.

The template is used only to autofill cashier input in Service x Product mode.

The final submitted transaction remains based on the existing work item persistence:

- product line total,
- service price,
- package total where package auto split is used.

## Invariants

1. Product-only mode remains unchanged.
2. Product `harga_jual` remains pure product sale price.
3. Service catalog item remains pure service master data.
4. Service-product template is not a transaction by itself.
5. Package total is not product price.
6. Package auto split remains backend source of truth.
7. Cashier may override default values before submit.
8. Historical mixed-price notes are not automatically rewritten.
9. No 20/80 split is persisted in this phase.

## Target User Flow

### Product-only flow

Cashier selects product-only mode.

Cashier searches product.

System fills:

- product id,
- product label,
- product unit price from product catalog,
- qty.

No service is created or autofilled.

### Service x Product flow without template

Cashier selects Service x Product mode.

Cashier searches product.

System fills the product line as it currently does.

Cashier manually fills service name and package/service nominal values.

Existing package auto split behavior remains valid.

### Service x Product flow with template

Cashier selects Service x Product mode.

Cashier searches product.

When product is selected, system looks for active service-product template.

If template exists, system autofills:

- linked service name,
- service catalog id when supported,
- default service price,
- default package total when available.

If default package total is present, package total is filled from template.

If default package total is absent, package total may be calculated as:

```text
product_total + default_service_price_rupiah
```

Cashier may edit final nominal values before submit.

## Data Blueprint

### New Table

`service_product_templates`

Required columns:

- `id` string primary key
- `product_id` string
- `service_catalog_item_id` string
- `default_service_price_rupiah` integer
- `default_package_total_rupiah` integer nullable
- `is_active` boolean default true
- `sort_order` integer default 0
- `created_at` timestamp
- `updated_at` timestamp

Indexes:

- `product_id`
- `service_catalog_item_id`
- `is_active`
- composite active lookup index on `product_id`, `is_active`, `sort_order`

Constraints:

- FK `product_id` references `products.id`
- FK `service_catalog_item_id` references `service_catalog_items.id`
- default service price must be greater than zero
- default package total, when present, must be greater than zero

Unique rule:

- Start conservative with one active primary/default template per product if implementation needs simplicity.
- If multiple templates per product are required later, expose explicit selection in cashier UI instead of hidden random selection.

Recommended phase-1 unique constraint:

- one active template per product

If database-level partial unique index is not portable enough, enforce this in application validation and tests.

## Domain / Application Blueprint

Add a small service-product template read model.

Suggested DTO:

`ServiceProductTemplateLookupRow`

Fields:

- `id`
- `product_id`
- `service_catalog_item_id`
- `service_name`
- `default_service_price_rupiah`
- `default_package_total_rupiah`
- `is_active`

Suggested read port:

`ServiceProductTemplateLookupReaderPort`

Methods:

- `findActiveByProductId(string $productId): ?ServiceProductTemplateLookupRow`

Suggested writer/admin port:

`ServiceProductTemplateWriterPort`

Methods may be added only as needed by admin CRUD:

- create
- update
- deactivate
- reactivate

Keep cashier lookup read path separate from admin mutation path.

## Adapter Blueprint

Add database adapter for service-product template lookup.

Lookup behavior:

1. filter by selected product id,
2. only active template,
3. join service catalog item,
4. ignore inactive service catalog item if service catalog supports active filtering,
5. order by sort order, then updated/created stable id order,
6. return one deterministic row.

Do not perform price split in the lookup adapter.

The adapter only returns defaults.

## Cashier Product Lookup Blueprint

Extend cashier product lookup response only for Service x Product context.

Recommended query parameter:

```text
context=service_product
```

Existing product-only lookup should stay unchanged unless explicitly requested by frontend.

When `context=service_product`, product lookup row may include:

`service_product_template`

Example shape:

```json
{
  "id": "template-id",
  "service_catalog_item_id": "service-id",
  "service_name": "Jasa Pasang Produk A",
  "default_service_price_rupiah": 200000,
  "default_package_total_rupiah": 450000
}
```

If no active template exists, `service_product_template` should be null or omitted.

Product-only mode must not autofill service.

## Workspace UI Blueprint

When selecting a product inside service store stock row:

1. Existing product selection behavior runs first.
2. If lookup row has `service_product_template`:
   - fill service name,
   - fill service catalog id if the hidden field exists,
   - fill service default fee,
   - fill package total when template default package total exists,
   - otherwise calculate package total from product total + default service price,
   - mark package total as autofilled.
3. Call existing summary refresh.
4. Focus remains practical for cashier speed.

Cashier override rules:

- if cashier manually edits package total, do not overwrite it again unless product/template changes,
- if cashier changes product, refetch/reapply template,
- if cashier clears product, clear template-derived service values only when not manually edited.

## Backend Transaction Blueprint

Do not create new transaction type.

Keep using existing:

- `store_stock_sale_only`
- `service_only`
- `service_with_store_stock_part`

For package auto split:

- request may submit service price as zero,
- request submits package total,
- backend computes final service price from package total minus product total.

The submitted work item remains the transaction source of truth.

Template values are defaults, not final truth.

## Optional Snapshot Rule

Phase 1 may skip persisting template id into work item details if it risks widening scope.

If persisted, add nullable snapshot fields only after checking revision and read model impact.

Allowed later fields:

- `service_catalog_item_id` nullable on `work_item_service_details`
- `service_product_template_id` nullable on `work_item_service_details`
- explicit package breakdown table only if reporting requires it

Do not add these in the first DB patch unless tests prove current edit/revision/read flows remain stable.

## Admin Management Blueprint

Admin needs a practical way to manage templates.

Minimum admin fields:

- product search/select,
- service search/select,
- default service price,
- default package total optional,
- active status.

Validation:

- product required,
- service required,
- default service price required and greater than zero,
- default package total nullable and greater than zero when present,
- if default package total is present and product price is known, package total cannot be below product price,
- one active default template per product unless multi-template selection is explicitly implemented.

Admin UI does not need to be as fast as cashier UI.

## Testing Blueprint

Required targeted tests:

### Database / Migration

- service product template table exists,
- FK to product works,
- FK to service catalog item works,
- active lookup indexes/columns exist as intended.

### Lookup

- active template can be found by product id,
- inactive template is ignored,
- no template returns null,
- lookup returns linked service name and default nominal values.

### Product Lookup

- product-only lookup payload remains compatible,
- service-product context includes template metadata,
- no template does not break product search.

### Workspace / Feature

- selecting product in Service x Product mode with template autofills service/package defaults,
- submitted package auto split stores final service price,
- cashier override package total is preserved,
- package total below product total is rejected,
- product-only transaction does not create service detail.

### Regression

Must stay green:

- `CreateTransactionWorkspaceServiceStoreStockFeatureTest`
- `CreateTransactionWorkspacePackageAllocationAuditFeatureTest`
- `ServiceCatalogEndpointFeatureTest`

## Implementation Slices

### Slice 1: DB foundation

- migration
- minimal tests
- no UI changes

### Slice 2: Lookup read model

- DTO
- port
- adapter
- service provider binding
- unit/feature tests

### Slice 3: Cashier product lookup extension

- add optional context parameter
- include template metadata only for service-product context
- keep existing payload stable

### Slice 4: Workspace JS autofill

- update product selection in service store stock rows
- respect manual override
- update summary behavior

### Slice 5: Admin template management

- minimal admin CRUD
- validation
- active/inactive behavior

### Slice 6: Final regression proof

- targeted tests
- relevant browser/manual QA note if needed
- git status proof

## Non-Goals

- no 20/80 persistence
- no technician commission
- no employee payout
- no report allocation split
- no automatic historical rewrite
- no product price reinterpretation
- no large note lifecycle rewrite
- no new transaction type

## Acceptance Criteria

Implementation is accepted when:

- ADR-0031 is linked from this blueprint.
- Workflow-0025 is linked from this blueprint.
- Product-only behavior remains unchanged.
- Service x Product template autofill works.
- Package auto split backend calculation remains source of truth.
- Cashier override is preserved.
- Historical notes remain stable.
- Targeted regression tests pass.
- `git status --short` shows only intentional files.

## Verification Commands

Run after implementation slices:

```bash
php artisan test \
  tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php \
  tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php \
  tests/Feature/Note/ServiceCatalogEndpointFeatureTest.php
```

Run full suite before final merge when practical.
