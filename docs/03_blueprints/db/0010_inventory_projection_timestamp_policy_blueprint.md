# DB Blueprint 0010 - Inventory Projection Timestamp Policy Blueprint

Status: Audited
Scope: `product_inventory` and `product_inventory_costing` projection timestamp policy
Owner: HyperPOS

## 1. Active Table Group

Table groups:

- `product_inventory`
- `product_inventory_costing`

Category:

- Derived inventory projection/snapshot tables

Source-of-truth status:

- Derived from `inventory_movements`
- Not the primary stock ledger
- Current stock quantity/value snapshot for read paths

## 2. Exact Problem

`product_inventory` and `product_inventory_costing` are heavily used read/projection tables, but they do not have dedicated DB audit matrix rows yet.

Current proven gap:

- `product_inventory` has no dedicated matrix row.
- `product_inventory_costing` has no dedicated matrix row.
- `product_inventory` has no system timestamp columns.
- `product_inventory_costing` has no system timestamp columns.

However, these tables are projection/snapshot tables, not source ledgers.

Risk:

- Adding `created_at` and `updated_at` naively can create misleading audit semantics.
- Normal writers use `updateOrInsert`.
- Projection rebuild writers use `delete()` followed by `insert()`.
- After a rebuild, `created_at` would likely mean projection rebuild time, not original stock state creation time.
- `updated_at` would likely mean projection materialization time, not a domain stock event.
- Treating projection timestamps as business/report dates would corrupt inventory and reporting interpretation.

## 3. Current Proven Schema

`product_inventory` migration:

- `database/migrations/2026_03_12_000700_create_product_inventory_table.php`

Proven columns:

- `product_id`
- `qty_on_hand`

`product_inventory_costing` migration:

- `database/migrations/2026_03_13_000100_create_product_inventory_costing_table.php`

Proven columns:

- `product_id`
- `avg_cost_rupiah`
- `inventory_value_rupiah`

Current proven gap:

- no `created_at`
- no `updated_at`
- no projection materialization timestamp
- no dedicated projection metadata table

## 4. Current Proven Foreign Key Behavior

FK migration:

- `database/migrations/2026_04_06_220100_add_v2_procurement_inventory_foreign_keys.php`

Proven FK behavior:

- `product_inventory.product_id` references `products.id` with `restrictOnDelete`.
- `product_inventory_costing.product_id` references `products.id` with `restrictOnDelete`.

Decision:

- Do not change FK/delete semantics in this policy slice.

## 5. Current Proven Usage

`product_inventory` is used by:

- product inventory writer
- product inventory projection writer
- product inventory reader
- issue inventory operation
- stock adjustment flow
- product catalog read paths
- dashboard inventory queries
- inventory current snapshot report
- inventory movement summary report
- inventory stock value report
- mobile/product/admin/note tests and fixtures

`product_inventory_costing` is used by:

- product inventory costing writer
- product inventory costing projection writer
- product inventory costing reader
- issue inventory operation
- rebuild costing projection
- dashboard inventory queries
- inventory current snapshot report
- inventory movement summary report
- inventory stock value report
- operational profit/COGS reporting
- mobile/product/admin/note tests and fixtures

Known high-risk semantics:

- `inventory_movements` remains the official stock ledger/source of truth.
- `product_inventory.qty_on_hand` is a current snapshot/projection value.
- `product_inventory_costing.avg_cost_rupiah` is a current costing snapshot/projection value.
- `product_inventory_costing.inventory_value_rupiah` is a current inventory value snapshot/projection value.

## 6. Policy Decision

Do not add `created_at` or `updated_at` to `product_inventory` or `product_inventory_costing` in the current DB hardening timestamp patch sequence.

Reason:

- Projection rebuild paths delete and recreate rows.
- Generic row timestamps would not reliably express original domain creation or mutation time.
- The source ledger already has `inventory_movements.tanggal_mutasi` for business/report time.
- The source ledger now has `inventory_movements.created_at` and `inventory_movements.updated_at` for system row persistence time.
- Projection tables need a separate projection materialization policy if audit requirements demand it.

Preferred future options, if needed:

1. Add explicit `projected_at` to projection rows.
2. Add explicit `rebuilt_at` to projection rows.
3. Add a separate projection metadata table, for example `inventory_projection_runs`.
4. Keep projection tables without timestamps and audit from `inventory_movements`.

No option is selected in this slice.

## 7. Forbidden Impact

Do not change in this policy slice:

- inventory movement ledger semantics
- inventory quantity math
- average cost math
- COGS/profit reporting
- projection rebuild behavior
- source type/source id semantics
- FK/delete semantics
- UI
- API/mobile
- timestamp indexes
- PostgreSQL runtime

## 8. PostgreSQL Readiness Impact

Current residual PostgreSQL/readiness risk:

- Projection tables are simple primary-key snapshots and portable at schema level.
- Projection rebuild behavior is application-level and not PostgreSQL runtime proven in this slice.
- Timestamp policy remains intentionally unresolved until projection materialization semantics are decided.

Preferred future posture:

- Do not rely on generic row timestamps for projection truth.
- If projection materialization needs audit, use explicit projection-oriented timestamp names.
- Keep reporting based on ledger/business dates, not projection timestamps.

## 9. Required Proof Before Any Future Patch

Before patching projection timestamps, require:

- a written decision choosing `created_at`/`updated_at`, `projected_at`, `rebuilt_at`, or metadata table
- proof of all writer paths:
  - normal `updateOrInsert`
  - full `delete()->insert()` projection rebuild
- RED test proving the specific current gap
- GREEN test proving the selected timestamp/materialization semantics
- focused inventory/reporting blast-radius proof

## 10. Completion Criteria For This Policy Slice

This policy slice can be considered docs-aligned when:

- `product_inventory` has a matrix row.
- `product_inventory_costing` has a matrix row.
- Both rows document projection semantics.
- Both rows explicitly defer timestamp patch until projection materialization semantics are selected.
- No schema/source patch is applied.
