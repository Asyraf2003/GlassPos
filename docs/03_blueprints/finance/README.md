# finance

Blueprints, DoD, and workflows for the finance domain.

## Files

| File | Type | Contents |
|---|---|---|
| `0001_note_finance_stabilization.md` | Blueprint | Note finance stabilization: settlement, carry-forward, projection |
| `0002_note_finance_stabilization_addendum.md` | Addendum | Additional decisions: current-only refund, projection schema |
| `0003_finance_residual.md` | Blueprint | Finance residual error-log remediation (001, 003, 004, 005, 006, 008, 011-014, 017, 021) |
| `0004_finance_residual_dod.md` | DoD | Finance residual completion criteria |
| `0005_finance_residual_workflow.md` | Workflow | Slice order, test matrix, CLI workflow for finance residual |
| `0006_note_revision_refund_ledger.md` | Blueprint | Revision, refund, and ledger lifecycle after settlement |
| `0007_note_revision_refund_ledger_dod.md` | DoD | Revision-refund-ledger completion criteria |
| `0008_note_revision_refund_ledger_workflow.md` | Workflow | Revision-refund-ledger execution workflow |
| `0009_create_transaction_domain_risk_handoff.md` | Handoff | Risk before create-transaction separation across finance, payment method, cash calculator, edit, and refund domains |
| `../99_archive/handoff/v2/edit_refund_sniper/0028_create_transaction_modular_payment_hardening_handoff.md` | Handoff | Create-transaction modular payment hardening: cash, transfer, skip / no-payment, partial transfer proof |

## Related ADRs

Active cancellation campaign: [Blueprint0019](0019_whole_note_cancellation_and_restore.md),
governed by [ADR-0046](../../02_architecture/adr/0046_whole_note_cancellation_and_revision_restore.md).
Blueprint0018 closed lifecycle proof remains the baseline; whole-note cancellation is a new scope.

`docs/02_architecture/adr/0018_note_revision_settlement_external_product_lifecycle.md`, `docs/02_architecture/adr/0024_note_current_projection_and_current_only_refund.md`, `docs/02_architecture/adr/0025_note_revision_carry_forward_settlement.md`, `docs/02_architecture/adr/0026_note_revision_surplus_disposition.md`, `docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md`, `docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md`
