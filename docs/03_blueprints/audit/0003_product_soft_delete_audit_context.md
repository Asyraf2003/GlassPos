# Product Soft-Delete Audit Context

## Scope

This note covers one narrow ProductCatalog behavior: product soft-delete audit metadata.

Soft delete remains a ProductCatalog lifecycle action. It does not migrate product data into another domain.

## Contract

When a caller sets `ProductChangeContext` before soft-delete, the soft-delete path must carry:

- `actor_id`
- `actor_role`
- `source_channel`
- `reason`

into the existing product history/audit writes.

The mutation still performs the established lifecycle work:

1. mark the product soft-deleted,
2. create the next `product_versions` revision,
3. create the matching `audit_events` row,
4. commit transactionally.

If no explicit source channel is supplied, the existing web path falls back to `web_admin`.

## Bulk-operation use

A future bulk product maintenance command may use the same lifecycle path.

For example, a row classified as `DELETE` may supply a business reason such as:

> Data bukan seharusnya berada di produk dan dipindahkan ke service.

The sentence above is audit context only. It does not mean the soft-delete path creates or migrates a Service record.

Rows that do not mutate production state, such as unknown-price or unchanged rows, should not emit fake product revisions or audit events.

## Proof

Regression coverage:

`tests/Feature/ProductCatalog/ProductSoftDeleteAuditContextFeatureTest.php`

The proof asserts that ProductChangeContext metadata reaches both `product_versions` and `audit_events`.
