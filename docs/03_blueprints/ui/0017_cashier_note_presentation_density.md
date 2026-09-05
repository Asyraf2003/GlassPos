# 0017 - Cashier Note Presentation Density Contract

## Metadata

- Date: 2026-09-06
- Area: Cashier create, edit, and note detail presentation
- Status: Implemented pending local verification
- Supersedes only redundant presentation copy/layout from earlier cashier UI blueprints
- Does not supersede transaction, payment, revision, refund, inventory, audit, or device-classification semantics

## Goal

Keep cashier note surfaces operationally dense without removing financial truth or canonical form state.

The rule is simple:

```text
one region -> one visible context heading -> useful fields/actions
```

Do not stack eyebrow + heading + helper + field label when they describe the same thing.

## Locked Rules

### Create and edit workspace

- Customer/date fields do not need a second visible `Info Nota` / `Pelanggan & tanggal` heading.
- Operational note does not need a visible `Detail` / `Keterangan nota` heading above the `Keterangan Nota` field label.
- Transaction type selection exposes one visible `Jenis transaksi` context.
- Active lines expose one visible `Rincian` context and item count.
- Review exposes one visible `Review & Pembayaran` context.
- The layout-level smart back action is the only page back action. Do not add a second `Batal dan kembali` link inside the checkout card.
- Edit-only audit/revision fields remain canonical. Compatibility copy required by historical tests may remain visually hidden rather than being restored as visible helper text.

### Payment presentation

- Modal title identifies the current payment step; repeated explanatory subtitle copy is not visible by default.
- Standard payment keeps transaction summary, payment choices, actual amounts, payment method actions, and required date state.
- A label is not repeated as a section heading immediately above the same field.
- Cash mode visibly prioritizes only `Tagihan`, `Uang Pelanggan`, `Kembalian`, and `Sisa`.
- JavaScript-required DOM IDs and canonical hidden payment fields must remain stable even when their old visible copy becomes visually hidden.
- Presentation cleanup must not introduce another payment endpoint, controller, use case, allocator, or persistence path.

### Note detail

- `ID Nota` displays the actual note ID; customer identity is not repeated as a fake note number.
- Info summary does not repeat line count or line summary already represented by the Rincian section.
- Review does not repeat `Status Operasional` already represented by Info Nota.
- Payment history retains real event data but drops explanatory copy that merely restates that it is a history.
- On desktop the layout remains two-column:
  - left: Info Nota -> Rincian Nota -> Riwayat Nota;
  - right: Review & Pembayaran spanning those rows and staying available as the finance/action rail.
- Riwayat Nota must not span the full desktop width underneath the review rail.
- On handset the existing compact stack remains authoritative.

## Non-Negotiable Data Contract

UI density reduction may hide or remove presentation copy only. It must not remove or reinterpret:

- note/customer/date canonical inputs;
- authoritative product/service/package identities;
- idempotency key;
- `inline_payment[...]` canonical payload;
- payment allocation semantics;
- cash received/change calculations;
- revision reason requirements;
- refund lifecycle;
- audit/history data;
- device-aware Simple/Detail policy.

## Verification Contract

A change in this area is not complete until:

1. the presentation-density contract test is green;
2. existing Note/payment/revision/refund tests remain green;
3. `make verify` exits 0;
4. desktop create Detail, payment modal, cash payment step, and note detail are visually checked;
5. physical handset create Simple/Detail remains checked separately from desktop viewport emulation.
