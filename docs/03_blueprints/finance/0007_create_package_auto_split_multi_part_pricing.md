# Blueprint 0007 - Create Package Auto Split Multi-Part Pricing

## Metadata

- Date: 2026-05-29
- Scope: Create transaction package auto split pricing
- Status: RESEARCH / BLUEPRINT READY, NOT IMPLEMENTED
- Primary implementation target: create transaction
- Future-readiness target: edit/revision, API migration, PostgreSQL readiness

## Problem

Current create transaction package pricing supports service + store-stock package auto split, but the flow is still effectively single-line for product/external lines.

The real workshop workflow needs a simpler cashier input model:

- cashier enters one package/total charge;
- cashier selects the related store-stock products, possibly more than one;
- system calculates product/part total;
- system stores service residual as:
  service_price = package_total - parts_total.

For external purchase, the cashier should not be forced into a complex split. The first mature create target is:

- package total is customer charge;
- external part total is entered as a total amount;
- optional hidden label may be captured for field observation;
- service price is package total minus external part total.

## Locked User Decisions

- Implementation starts with create transaction only.
- Edit/revision must be prepared in the design, but not implemented in the first slice.
- Default UX should be auto/package total.
- Manual split must remain available, but hidden or advanced.
- Service + store-stock must support multiple products in one service row.
- Duplicate product IDs in the same service row must be rejected.
- Duplicate rejection must explain:
  - what was violated;
  - why it is restricted;
  - how the cashier can fix it, usually by increasing qty on the existing product line.
- Service + external purchase does not need multiple external lines in the first slice.
- External purchase input should be simple:
  - label optional;
  - qty optional;
  - total_rupiah required.
- Package total below product/external total must be rejected.
- Package total equal to product/external total is valid, service price may be 0.
- Audit metadata is mandatory and is treated as the final financial fingerprint.
- Report/export impact must be analyzed and adjusted if the persisted input change affects report meaning.
- Suggestion/history metadata should be designed so the future API/PostgreSQL migration remains clean.

## Current Repo Facts

- Store transaction workspace request already accepts:
  - pricing_mode;
  - package_total_rupiah.
- Existing package auto split was previously implemented for service + store-stock.
- Existing locked rule for store-stock package:
  - sparepart_total = product.harga_jual * qty;
  - service_price = package_total - sparepart_total.
- Existing minimum rule:
  - package_total must be greater than or equal to sparepart total;
  - package_total may equal sparepart total;
  - service price may be 0;
  - package_total below sparepart total must be rejected.
- External purchase package pricing was previously out of scope because the existing field is unit_cost_rupiah, not a customer-facing charge.
- Current create normalizer/mapper path is effectively first-line only for:
  - product_lines;
  - external_purchase_lines.
- Current service + store-stock package composer reads only the first product line.
- Current UI summary already supports package total for service + store-stock, but not package total for service + external purchase.

## Scope In

### Slice A - Create service + store-stock multi-product package auto split

- Update create input normalization to preserve multiple product_lines.
- Update validation to validate every product line.
- Reject duplicate product_id inside the same service row.
- Validate package_total_rupiah against total store-stock product value.
- Compute:
  - parts_total_rupiah = sum(catalog harga_jual * qty);
  - service_price_rupiah = package_total_rupiah - parts_total_rupiah.
- Persist multiple store-stock lines under the same service work item.
- Issue inventory for every store-stock line.
- Preserve manual split as hidden/advanced compatibility mode.

### Slice B - Create service + external purchase simple package auto split

- Add package auto split support for service + external purchase.
- Input target:
  - package_total_rupiah required;
  - external_purchase.total_rupiah required;
  - external label optional;
  - qty optional, default interpretation may be 1.
- Compute:
  - external_total_rupiah = input total_rupiah;
  - service_price_rupiah = package_total_rupiah - external_total_rupiah.
- Reject package_total below external_total.
- Allow service_price 0.

### Slice C - Audit metadata

Audit metadata must record package split details for every package auto split row.

Minimum audit payload shape:

{
  "pricing_mode": "package_auto_split",
  "package_total_rupiah": 250000,
  "parts_total_rupiah": 130000,
  "service_price_rupiah": 120000,
  "source": "store_stock",
  "parts": [
    {
      "source": "store_stock",
      "product_id": "product-id",
      "qty": 2,
      "unit_price_rupiah": 50000,
      "line_total_rupiah": 100000
    }
  ]
}

For external purchase:

{
  "pricing_mode": "package_auto_split",
  "package_total_rupiah": 180000,
  "parts_total_rupiah": 80000,
  "service_price_rupiah": 100000,
  "source": "external_purchase",
  "external": {
    "label": null,
    "qty": null,
    "total_rupiah": 80000
  }
}

Audit metadata must be explicit enough to explain the final service residual without recalculating from mutable catalog data later.

### Slice D - Suggestion/history readiness

Design should prepare metadata for future suggestion/history UX:

- service names from prior transactions;
- external part labels from prior transactions;
- package/part context from prior transactions;
- reason/catatan history if note-level operational notes are added later.

First implementation may query from existing transaction/audit data rather than introducing a new master table, but payloads should be structured enough for a future PostgreSQL/API-backed suggestion service.

### Slice E - Report/export analysis

Before patching report/export, inspect whether existing report surfaces already read persisted work item/service/store-stock/external rows correctly.

If report/export only displays final row totals and persisted rows remain semantically correct, no report patch is needed.

If report/export needs package split clarity, add detail output for:

- package total;
- parts total;
- service residual;
- part source;
- store-stock product breakdown or external total.

## Scope Out

- Payment seam changes.
- Refund behavior changes.
- Edit/revision implementation.
- PostgreSQL migration.
- API implementation.
- New suggestion master table unless proven necessary.
- Multi external purchase lines in the first implementation slice.
- Browser-only QA closure without real browser runner.

## Required Future Edit/Revision Readiness

Edit/revision should not flatten package auto split into manual split.

Future edit behavior should preserve package metadata:

- pricing_mode;
- package_total_rupiah;
- parts_total_rupiah;
- service_price_rupiah;
- parts breakdown.

When edit changes package total or parts, service residual must be recalculated through the same create-root package split rule.

Future edit/revision tests must prove:

- package metadata survives revision snapshot;
- replacement rows keep package context;
- settlement/payment/refund projections still use persisted component rows;
- audit records package recalculation context.

## Proposed Implementation Order

1. Characterization RED for current first-line limitation:
   - service + store-stock with two different products should persist both lines and compute residual.
2. Patch input normalizer to preserve all product_lines.
3. Patch validation to validate all product lines and reject duplicate product_id with a clear actionable message.
4. Patch package pricing composer to support multiple store-stock lines.
5. Patch store-stock mapper/persister path if it still maps only first line.
6. Add focused GREEN proof for multi store-stock package.
7. Add RED for service + external package total.
8. Patch external package split.
9. Add audit metadata proof for both store-stock multi-product and external package split.
10. Analyze report/export impact and patch only if proven necessary.
11. Run focused create transaction package suite.
12. Run make verify only after focused suite is GREEN.

## Required Tests

### Store-stock multi-product package test

Scenario:

- package_total_rupiah = 250000;
- product A harga_jual = 50000, qty = 2, total = 100000;
- product B harga_jual = 30000, qty = 1, total = 30000;
- parts_total = 130000;
- expected service_price = 120000;
- expected note total = 250000;
- expected two store-stock lines;
- expected two inventory stock_out records;
- expected audit package allocation contains both product lines.

### Store-stock duplicate rejection test

Scenario:

- same product_id appears twice in one service row.

Expected:

- request rejected;
- error explains duplicate product violates one-product-one-line rule;
- solution says increase qty on the existing product row.

### External package split test

Scenario:

- package_total_rupiah = 180000;
- external total_rupiah = 80000;
- label omitted or hidden;
- expected service_price = 100000;
- expected note total = 180000;
- expected audit external package allocation.

### Minimum rejection tests

- package_total below store-stock parts total must be rejected.
- package_total below external total must be rejected.

### Zero service tests

- package_total equals store-stock parts total is accepted.
- package_total equals external total is accepted.
- service_price_rupiah becomes 0.

## GAP

- Exact UI hidden/advanced layout is not finalized.
- Exact note-level operational note column/storage is not finalized.
- Suggestion/history storage may start from existing transaction/audit data, but a future master/cache table is still undecided.
- External purchase field migration from unit_cost_rupiah to total_rupiah needs DB/schema inspection before implementation.
- Report/export impact is not proven yet.
- Edit/revision design is ready as direction only, not implementation scope.

## Decision

The safest path is to remodel create package pricing from the root:

- make create package auto split canonical;
- preserve manual split as advanced compatibility;
- support real multi store-stock products;
- keep external purchase simple with total part amount;
- write explicit audit metadata;
- defer edit/revision implementation until create semantics are stable and proven.

