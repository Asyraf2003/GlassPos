# ADR-0038: Supplier Invoice Unit Cost Rounding Residue Inventory Value

Status: Proposed

Date: 2026-06-23

## Context

Supplier invoice totals can be exact supplier document values that do not divide evenly by `qty_pcs`.

Example:

- qty = 3
- subtotal = 150000
- fixed tax = 5000
- total after tax = 155000
- 155000 / 3 = 51666 remainder 2

The system currently stores integer rupiah unit cost. It already has `rounding_residue_rupiah`, but request validation can reject non-divisible line totals before allocation can record the residue.

This is wrong for real supplier documents. The client must not be forced to manipulate supplier invoice totals just to make unit cost division exact.

## Decision

Supplier invoice data must preserve the supplier document value exactly.

If a line total after tax does not divide evenly by qty:

- Store original `line_total_rupiah` exactly.
- Store `unit_cost_rupiah = intdiv(line_total_rupiah, qty_pcs)`.
- Store `rounding_residue_rupiah = line_total_rupiah % qty_pcs`.
- Require explicit UI/user confirmation before accepting the rounded unit cost.
- Do not reject merely because total is not divisible by qty.

Inventory value must also preserve the exact supplier invoice value.

For received supplier invoice revisions:

- Cost revaluation must use exact line total delta, not only unit cost delta multiplied by qty.
- Formula:
  - old exact cost = old line total after tax
  - new exact cost = new line total after tax
  - revaluation delta = new exact cost - old exact cost
- This ensures residue is not lost from inventory valuation.

## UI policy

When residue exists, the UI must show a confirmation such as:

"Total setelah pajak tidak habis dibagi qty. Modal per pcs akan dibulatkan dan selisih pembulatan akan dicatat. Lanjutkan?"

On confirmation, submit `tax_rounding_residue_confirmed = true`.

## Non-goals

- No forcing user/client to change supplier invoice totals.
- No changing supplier document values to divisible numbers.
- No decimal rupiah storage.
- No Operational Profit formula change in this slice.
- No supplier payment proof changes.
- No Mobile API changes.

## Required tests

1. Update received supplier invoice qty 3 subtotal 150000 tax 5000 without confirmation requests residue confirmation.
2. Same payload with confirmation succeeds.
3. Line stores `line_total_rupiah = 155000`, `unit_cost_rupiah = 51666`, `rounding_residue_rupiah = 2`.
4. Inventory costing stores exact value `155000`, not `154998`.
5. Received invoice revaluation uses exact total delta including residue.
6. Old hard validation `total rincian harus habis dibagi qty` is removed or no longer blocks this workflow.
