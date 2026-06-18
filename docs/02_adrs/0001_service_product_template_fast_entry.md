# ADR 0001: Service Product Template Fast Entry

Status: Accepted

Date: 2026-06-18

## Context

The workshop has an existing manual note habit where a single line may contain product, service, and final package price in one number.

This creates ambiguity when product master `harga_jual` is filled with the final package price instead of the pure product sale price. The system already separates product-only sales, service-only work items, and service-with-store-stock-part work items. The current package auto split flow calculates service price from:

`package_total_rupiah - store_stock_product_total_rupiah`

The product catalog lookup uses product `harga_jual` as the default unit price. Therefore, product `harga_jual` must remain the pure product sale price.

The 20/80 explanation used during discussion is only a manual way to understand a package price example. It is not a system rule and must not be persisted as business logic in this phase.

## Decision

Use a service-product template concept for fast cashier entry.

A product remains pure master data.

A service remains pure master data.

A service-product template connects a product to a default service and optional package defaults. The template exists to autofill cashier input, not to rewrite product pricing.

In cashier workspace:

- Product-only mode keeps using product catalog price only.
- Service x Product mode allows cashier to search a product.
- If an active template exists for that product, the system autofills the linked service and default package/service values.
- Cashier may override nominal values when the real transaction differs.
- Final transaction still stores the actual product line total and service price used at submission time.

No 20/80 technician/profit split is stored in this phase.

## Rules

1. Product `harga_jual` means pure product selling price.
2. Service `default_price_rupiah` means pure/default service price.
3. Package total is transaction/template pricing, not product pricing.
4. If `default_package_total_rupiah` is available, it may be used to autofill package total.
5. Backend package auto split remains the source of truth for service price in package mode:
   `service_price_rupiah = package_total_rupiah - product_total_rupiah`
6. Existing historical notes must not be auto-rewritten into service/product splits without explicit evidence.
7. The cashier UI may be optimized to match workshop habit, but persistence must remain structured.

## Consequences

Positive:

- Cashier flow stays fast.
- Product price pollution is prevented.
- Service x product package habit becomes structured.
- Current package auto split behavior can be reused.
- Future reporting can be built from separated product and service values.

Negative:

- Master data entry becomes more disciplined.
- Existing contaminated product prices must be corrected manually.
- Historical mixed-pricing notes remain ambiguous unless manually corrected.

## Out of Scope

- Technician commission split.
- 20/80 profit allocation.
- Employee payout automation.
- Automatic migration of old mixed-price notes.
- Rebuilding historical package breakdown without source proof.

## Implementation Direction

A future blueprint should introduce:

- `service_product_templates` table.
- Reader/writer ports for template lookup.
- Admin CRUD or admin management path for templates.
- Cashier product lookup support for service-product template metadata.
- Workspace UI autofill for Service x Product mode.
- Feature tests proving product-only behavior remains unchanged and service-product template autofill works.
