# finance

Blueprint, DoD, dan Workflow untuk domain finance.

## File

| File | Jenis | Isi |
|---|---|---|
| `0001_note_finance_stabilization.md` | Blueprint | Stabilisasi note finance: settlement, carry-forward, projection |
| `0002_note_finance_stabilization_addendum.md` | Addendum | Tambahan keputusan: current-only refund, projection schema |
| `0003_finance_residual.md` | Blueprint | Remediasi residual error log finance (001, 003, 004, 005, 006, 008, 011–014, 017, 021) |
| `0004_finance_residual_dod.md` | DoD | Kriteria selesai finance residual |
| `0005_finance_residual_workflow.md` | Workflow | Slice order, test matrix, CLI workflow finance residual |
| `0006_note_revision_refund_ledger.md` | Blueprint | Revision, refund, dan ledger lifecycle post-settlement |
| `0007_note_revision_refund_ledger_dod.md` | DoD | Kriteria selesai revision-refund-ledger |
| `0008_note_revision_refund_ledger_workflow.md` | Workflow | Workflow eksekusi revision-refund-ledger |
| `0009_create_transaction_domain_risk_handoff.md` | Handoff | Risiko create transaction sebelum pemisahan finance, payment method, cash calculator, edit, dan refund domain |
| `../99_archive/handoff/v2/edit_refund_sniper/0028_create_transaction_modular_payment_hardening_handoff.md` | Handoff | Create transaction modular payment hardening: cash, transfer, skip/no-payment, partial transfer proof |

## ADR Terkait

`docs/02_architecture/adr/0018_note_revision_settlement_external_product_lifecycle.md`, `docs/02_architecture/adr/0024_note_current_projection_and_current_only_refund.md`, `docs/02_architecture/adr/0025_note_revision_carry_forward_settlement.md`, `docs/02_architecture/adr/0026_note_revision_surplus_disposition.md`, `docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md`, `docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md`
