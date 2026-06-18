# Service Product Template Fast Entry Implementation Workflow

Status: Draft

Date: 2026-06-18

Canonical ADR:

- docs/02_architecture/adr/0031_service_product_template_fast_entry.md

Implementation blueprint:

- docs/03_blueprints/finance/0010_service_product_template_fast_entry.md

## Goal

Implement service-product template fast entry without changing the existing product-only and package auto split invariants.

The workflow exists so the next implementation session starts from a locked path instead of reopening the same pricing debate. Humanity has suffered enough.

## Locked Decisions

1. Product `harga_jual` is pure product sale price.
2. Service catalog item is pure service master data.
3. Service-product template is an autofill helper for cashier workflow.
4. Package total is not product price.
5. Package auto split remains:
   `service_price_rupiah = package_total_rupiah - product_total_rupiah`
6. 20/80 package explanation is discussion-only and not system logic.
7. Historical mixed-price notes are not auto-rewritten.

## Current Baseline

The current system already supports:

- product-only work item,
- service-only work item,
- service with store stock part,
- package auto split,
- service catalog lookup,
- service catalog create-if-missing.

The next implementation should extend this flow, not replace it.

## Implementation Phases

### Phase 0: Baseline Verification

Run targeted tests before code changes.

Required proof:

- current branch and head,
- ADR exists,
- package auto split tests pass,
- service catalog endpoint tests pass.

### Phase 1: Database Foundation

Add service-product template persistence.

Expected table:

`service_product_templates`

Required fields:

- `id`
- `product_id`
- `service_catalog_item_id`
- `default_service_price_rupiah`
- `default_package_total_rupiah` nullable
- `is_active`
- `sort_order`
- `created_at`
- `updated_at`

Rules:

- product must exist,
- service catalog item must exist,
- default service price must be greater than 0,
- default package total, when present, must be greater than 0,
- active lookup must ignore inactive templates.

### Phase 2: Domain and Ports

Add a small service-product template read path.

Required behavior:

- lookup active template by product id,
- return linked service name and default prices,
- keep write/admin concerns separate from cashier lookup concerns.

Do not add employee commission, 20/80 split, or reporting allocation in this phase.

### Phase 3: Admin Management

Add minimal admin path to create, edit, deactivate, and reactivate templates.

Admin entry may be less fast than cashier entry.

Required admin validation:

- product required,
- service required,
- default service price required,
- default package total optional,
- package total cannot be below product sale price when product price is available for validation.

### Phase 4: Cashier Lookup Autofill

Extend service x product cashier flow.

Expected behavior:

- cashier uses Service x Product mode,
- cashier searches product,
- selecting product fills product line as today,
- if active service-product template exists:
  - service name is autofilled,
  - service catalog id is tracked when supported,
  - package total is autofilled when template has default package total,
  - otherwise service default is used to calculate package total,
  - cashier may override final nominal values.

Product-only mode must remain unchanged.

### Phase 5: Transaction Snapshot

Persist final transaction values as existing work item values.

Required invariant:

- submitted product line total is product total,
- submitted service price is final service price,
- package auto split calculates final service price from package total and product total,
- historical notes remain stable after template changes.

Template id persistence may be added only if it does not break existing work item read/revision flows.

### Phase 6: Tests

Required test groups:

1. product-only lookup still uses product price only,
2. service x product lookup without template behaves as current system,
3. service x product lookup with template autofills service and package defaults,
4. package total below product total is rejected,
5. cashier override is preserved,
6. old package auto split feature tests stay green,
7. service catalog endpoint tests stay green.

## Non-Goals

- no 20/80 persistence,
- no technician payout,
- no employee commission report,
- no automatic rewrite of old notes,
- no product price reinterpretation as package price,
- no large transaction lifecycle rewrite.

## Suggested Next Blueprint

Create one blueprint dedicated to:

`service_product_templates + cashier service x product autofill`

The blueprint should be split into small patches:

1. migration and tests,
2. reader adapter and DTO,
3. admin management path,
4. cashier lookup payload extension,
5. workspace JS autofill,
6. regression tests and final proof.

## Final Acceptance

Implementation is accepted only when:

- targeted tests pass,
- no product-only behavior changes,
- service x product template autofill works,
- package auto split remains backend source of truth,
- docs reference ADR-0031,
- `git status --short` shows only intentional files.
