# ADR-0037: Editable Received Supplier Invoice Cost Revaluation

Status: Proposed

Date: 2026-06-23

## Context

Supplier invoice revision currently supports versioned updates and received-invoice quantity delta movements, but it rejects revision when the invoice has already been received and a same-product line changes `unit_cost_rupiah`.

The current business requirement is stricter and more realistic:

- Supplier invoices must remain editable after receipt.
- Editing price, tax, quantity, or landed cost may change unit cost.
- The system must not silently overwrite inventory or costing history.
- The system must explicitly record correction/revaluation effects.
- Supplier payable must remain exact.
- Audit/version history must preserve before/after invoice state.

The current source-map shows these relevant facts:

- `UpdateSupplierInvoiceOperation` builds the updated invoice, resolves payment/receipt context, blocks revised total below paid amount, blocks received unit-cost change, builds delta movements, checks negative stock, writes the invoice, then applies inventory effects.
- `SupplierInvoiceReceivedUnitCostRevisionGuard` detects same-product received line unit-cost change and causes the current unsupported failure.
- `SupplierInvoiceRevisionDeltaMovementsBuilder` and `SupplierInvoiceRevisionPairedLineDeltaResolver` already create stock-in/stock-out movements for quantity/product deltas.
- Cost-only change with same product and same quantity currently produces no delta movement.
- `SupplierInvoiceRevisionDeltaStockGuard` protects stock-out deltas from making current stock negative.
- `SupplierInvoiceRevisionInventoryEffectsApplier` writes movements and rebuilds inventory and costing projections.
- `supplier_invoice_versions` and canonical audit snapshots already store invoice revision history.
- `inventory_movements` stores quantity and cost movement fields: `movement_type`, `source_type`, `source_id`, `qty_delta`, `unit_cost_rupiah`, `total_cost_rupiah`.
- The current `InventoryMovement` domain rejects zero quantity movements.
- Operational Profit currently derives store-stock COGS from stock-out movements with `source_type = work_item_store_stock_line` minus stock-in reversal movements with `source_type = work_item_store_stock_line_reversal`.

## Problem

The old guard protects reporting precision by refusing received invoice revisions that change unit cost.

That is safe but too restrictive for real supplier workflows. Supplier invoices can be corrected after goods are received because tax, price, quantity, discount, or document corrections can arrive late.

The replacement policy must keep the safety properties without blocking valid work:

- Do not delete or rewrite old receipt movement history silently.
- Do not mutate old stock-out COGS silently.
- Do not allow negative stock.
- Do not allow revised payable total below already-paid amount.
- Do not lose audit/version trace.
- Do not change Operational Profit formula accidentally.

## Decision

### 1. Received supplier invoices remain editable

A supplier invoice may be revised even when one or more receipt rows already exist.

The system must not reject the revision only because received goods would have a different unit cost after the revision.

### 2. Unit-cost revision becomes explicit revaluation policy

`SupplierInvoiceReceivedUnitCostRevisionGuard` must not remain a terminal blocker.

It may be replaced, wrapped, or renamed into a policy/resolver that detects received unit-cost changes and produces explicit correction effects.

Deleting the guard without replacement is not allowed.

### 3. Quantity delta policy remains movement-based

If revised quantity increases after receipt, the system must create explicit incoming delta movement.

If revised quantity decreases after receipt, the system must create explicit outgoing/reversal delta movement.

Outgoing/reversal delta must keep the existing negative-stock protection.

### 4. Cost-only revision must create explicit revaluation effect

If product and quantity remain the same but unit cost changes, the system must create explicit cost revaluation/correction, not silently rely on the new supplier invoice line.

Implementation must record the cost delta as a first-class effect with traceable source.

Preferred implementation direction:

- Extend the inventory movement model to support value-only revaluation with:
  - `movement_type = cost_revaluation`
  - explicit source type such as `supplier_invoice_cost_revaluation`
  - `qty_delta = 0`
  - `unit_cost_rupiah = 0` or a documented neutral value
  - `total_cost_rupiah = value delta`
- Relax the current zero-qty domain validation only for approved value-only movement types.
- Update movement reader/writer and costing projection builder to understand value-only movements.

If this direction proves too invasive during tests, introduce a dedicated `supplier_invoice_cost_revaluations` ledger instead, but it must still be treated as a movement/effect ledger and must participate in projection/reporting through explicit code.

### 5. Stock still on hand policy

When the affected received quantity is still on hand, revaluation adjusts current inventory value and current average cost.

Expected effect:

- Quantity stays unchanged.
- Inventory value changes by the cost delta for stock still on hand.
- Current average cost is recalculated from current value / current quantity.
- Projection rebuild must be deterministic from ledger/effect rows.

### 6. Sold-stock policy

ADR-0037 selects policy C: separate sold-stock correction.

The system must not silently revalue historical COGS movements that were already created for customer sales.

The system must not hide the delta inside current inventory value when the affected stock is no longer on hand.

For sold quantity, create a separate sold-stock cost correction effect.

Initial policy:

- Sold-stock correction is traceable to the supplier invoice revision.
- It does not change current stock quantity.
- It does not silently rewrite old `work_item_store_stock_line` stock-out movements.
- It is not included in Operational Profit formula until a separate reporting ADR/test explicitly defines how sold-stock supplier correction appears in profit reporting.

This avoids retroactive profit mutation without audit. Apparently accounting dislikes time travel. Annoying, but fair.

### 7. Supplier payable remains exact

Revised `grand_total_rupiah` is the payable truth after revision.

If revised total is greater than current paid amount:

- Revision is allowed.
- Outstanding/payable remaining increases.

If revised total is equal to current paid amount:

- Revision is allowed.
- Outstanding remains zero.

If revised total is less than current paid amount:

- Revision is rejected.
- Refund/credit-note policy is out of scope for this ADR.

### 8. Audit/version is mandatory

Every successful revision must preserve:

- `supplier_invoice_versions` after snapshot.
- Canonical audit event.
- Canonical audit before/after snapshots.
- Current-line replacement history through existing versioned supplier invoice writer.

No inventory/cost correction may happen without invoice version/audit trace in the same transaction.

### 9. Residue remains explicit

ADR-0036 tax rounding residue behavior remains valid.

Cost revaluation must not hide tax rounding residue.

If revised tax creates residue, residue remains stored on supplier invoice lines and version snapshots.

### 10. Operational Profit formula is not changed by this ADR implementation slice

ADR-0037 does not authorize changing the current Operational Profit formula.

Any inclusion of sold-stock supplier correction into Operational Profit requires a separate explicit report test or follow-up ADR section before code changes.

## Consequences

Positive:

- Received supplier invoices can follow real supplier correction workflows.
- Inventory value remains correct when stock is still on hand.
- Historical sale COGS is not silently rewritten.
- Payable stays exact.
- Audit/version trace remains intact.

Negative / risk:

- Value-only revaluation is not supported by current `InventoryMovement` domain because zero quantity is currently rejected.
- Costing projection builder must be extended carefully.
- Reporting must be protected so correction movements do not accidentally inflate sales, COGS, stock-in, or stock-out summaries.
- Sold-stock correction remains visible but not yet included in Operational Profit until explicit reporting policy is added.

## Required characterization tests before implementation

Add characterization tests before patching implementation:

1. Received invoice edit tax 33% qty 2 changes unit cost and currently fails because of the received unit-cost guard.
2. Unpaid received invoice can be edited and creates explicit correction/revaluation effect.
3. Paid received invoice revised total below paid amount is rejected.
4. Paid received invoice revised total above paid amount is allowed and payable remaining increases.
5. Quantity increase after receipt creates incoming delta movement.
6. Quantity decrease after receipt creates outgoing/reversal delta movement and cannot make stock negative.
7. Same product, same quantity, changed price/tax creates cost revaluation correction.
8. Tax rounding residue remains explicit in line and version snapshot.
9. `supplier_invoice_versions` and canonical audit snapshots preserve before/after revision.
10. Sold-stock scenario creates separate sold-stock correction and does not silently rewrite old sale COGS movement.

## Non-goals

- No supplier refund or credit-note implementation.
- No supplier payment proof changes.
- No Mobile API changes.
- No refund policy changes.
- No Operational Profit formula change without explicit reporting test.
- No `migrate:fresh` requirement.
- No deletion of historical inventory movement rows.

## Implementation notes for next slice

Likely files to inspect/test first:

- `tests/Feature/Procurement/ExtremeReceivedInvoiceRevisionMatrixFeatureTest.php`
- `tests/Feature/Procurement/ReviseReceivedSupplierInvoiceDeltaFeatureTest.php`
- `tests/Feature/Procurement/ReviseReceivedSupplierInvoiceNegativeStockGuardFeatureTest.php`
- `tests/Support/SeedsReceivedSupplierInvoiceRevisionMatrixFixture.php`
- `app/Application/Procurement/Services/SupplierInvoiceReceivedUnitCostRevisionGuard.php`
- `app/Application/Procurement/Services/SupplierInvoiceRevisionPairedLineDeltaResolver.php`
- `app/Application/Procurement/Services/SupplierInvoiceRevisionMovementFactory.php`
- `app/Core/Inventory/Movement/InventoryMovementValidation.php`
- `app/Application/Inventory/Services/InventoryCostingProjectionBuilder.php`
- `app/Adapters/Out/Reporting/InventoryMovementSummaryDatabaseQuery.php`
- `app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php`

Next valid step after this ADR is committed to working tree: add characterization tests that prove the current RED guard and the target expected behavior.
