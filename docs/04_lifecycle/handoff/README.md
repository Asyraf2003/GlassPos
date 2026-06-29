# Handoff

This folder stores session recovery notes for the active or latest session.

## Rules

- One file per session or per session topic.
- Naming: `NNNN_topic_handoff.md`
- After a session is finished and no longer relevant, move it to `docs/99_archive/handoff/`.
- Do not keep permanent decisions here only - promote them to `docs/02_architecture/adr`.
- Do not keep active blueprints here - promote them to `docs/03_blueprints`.
- Canonical handoff template: `docs/01_standards/0005_handoff_template.md`

## Note

This folder is only for active or latest handoffs. Once a session is closed, archive it to `docs/99_archive/handoff/` so the history remains while the active workflow stays clean.

## Source of Truth Priority

1. Latest local operator output
2. `docs/01_standards`
3. `docs/02_architecture/adr`
4. Active blueprint in `docs/03_blueprints`
5. Latest handoff in this folder
6. Archive in `docs/99_archive/handoff`

## Active Handoffs

| File | Topic | Status |
|---|---|---|
| `0001_audit_write_path_canonical_pilot_handoff.md` | Audit write path canonical pilot | superseded by audit outbox runtime handoff |
| `0002_audit_outbox_runtime_switch_handoff.md` | Audit outbox runtime switch | continue in next session |
| `0018_service_catalog_lookup_migration_handoff.md` | Service catalog lookup + migration backfill | focused verified |

## Archive

All old handoffs live in `docs/99_archive/handoff/`:

- `step-based/` - handoffs from steps 02 through 12 (v1 era)
- `ui/` - UI session handoffs
- `v2/` - feature continuation session handoffs
- `mobile-api/` - Mobile API handoffs
- `seeder/` - Seeder handoffs
- `error_log/` - Error-log remediation handoffs
- `codex-security/` - Security audit handoffs

## Session Update - Inventory Costing Rebuild Same-day Stock-out Skip Fixed

### Scope

Inventory costing projection rebuild bug found from inventory stock value report diagnostics.

### FACT

- Owner found `product_inventory_costing` mismatch for `prod-year-001`.
- Projection qty matched movement ledger qty.
- Projection value did not match movement ledger value.
- Root proof showed same-day `stock_out` was replayed before `stock_in` and skipped because replay state qty was zero.
- Existing test only covered safe ordering: `stock_in` before `stock_out`.
- New regression test reproduced the failure.
- Patch changed costing rebuild from order-sensitive replay to ledger aggregation.
- Targeted test passed after patch.

### Files Changed

- `app/Application/Inventory/Services/InventoryCostingProjectionBuilder.php`
- `tests/Feature/Inventory/RebuildInventoryCostingProjectionWithStockOutFeatureTest.php`
- `docs/04_lifecycle/error_log/0051_inventory_costing_rebuild_same_day_stock_out_skip.md`

### Error Log

- `docs/04_lifecycle/error_log/0051_inventory_costing_rebuild_same_day_stock_out_skip.md`

### Root Cause

`InventoryCostingProjectionBuilder` previously skipped `stock_out` when current replay qty was zero.

Because rebuild order was `tanggal_mutasi`, then `id`, same-day movements could be replayed in UUID/id order instead of business lifecycle order.

This allowed costing projection value to overstate ledger value.

### Decision

Use ledger aggregation for rebuild costing projection:

```text
qty   = SUM(qty_delta)
value = SUM(total_cost_rupiah)
avg   = intdiv(value, qty)
```

This makes rebuild deterministic against the movement ledger and removes UUID/id order sensitivity.

### Proof

Targeted regression test:

```bash
php artisan test --filter=test_rebuild_costing_projection_does_not_skip_same_day_stock_out_before_stock_in
```

Owner reported PASS after patch.

### Next Step

Run broader targeted tests:

```bash
php artisan test --filter=RebuildInventoryCostingProjectionWithStockOutFeatureTest
php artisan test --filter=GetInventoryStockValueReportDatasetFeatureTest
```

Then re-run read-only projection-vs-ledger residual diagnostic.

