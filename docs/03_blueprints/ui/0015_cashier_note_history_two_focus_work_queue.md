# 0015 - Cashier Note History Two-Focus Work Queue Blueprint

## Metadata

- Date: 2026-09-05
- Area: Cashier note history/navigation
- Status: Implemented and locally verified
- Owner decision: Cashier history final pre-production UX hardening
- Related workspace blueprint: `0014_cashier_note_workspace_simple_detail_pos_hardening.md`

## Goal

Make `Riwayat Nota` a cashier work queue with two primary focuses while preserving the underlying note, payment, refund, and operational-work distinctions:

1. `Belum Selesai`
2. `Selesai`

History is navigation and prioritization. The note detail page remains the complete operational and audit representation.

## Authoritative Read Boundary

Classification stays in the existing cashier history query adapter behind `CashierNoteHistoryTableReaderPort`. Its source is `note_history_projection`, augmented by the existing `work_items` summary. Controllers, Blade, and JavaScript do not reconstruct finance or work-state truth.

Pagination and search are applied inside the selected bucket at the query boundary. The browser never receives an unclassified collection and does not classify it locally.

## Locked Chronology

Cashier history is ordered by the time the note was actually created:

```text
notes.created_at DESC
notes.id DESC
```

`transaction_date` remains the business-date/window field, not a substitute for creation chronology. Note ID is only a deterministic fallback when creation timestamps are equal. Bucket classification, scoped search, and pagination run without changing that chronology.

## Locked Classification

### Belum Selesai

A non-refunded projected note belongs to `Belum Selesai` when either condition is true:

- authoritative `outstanding_rupiah` is greater than zero; or
- at least one related work item remains operationally open.

This keeps unpaid/partial notes visible even if work is done, and keeps financially settled notes visible while operational work remains actionable.

### Selesai

A projected note belongs to `Selesai` when it is terminally refunded, or when both conditions are true:

- authoritative `outstanding_rupiah` is zero; and
- no related work item remains operationally open.

The refunded override is required because returning a settled payment can make numeric net-paid/outstanding projection values resemble an unpaid note even though the refund lifecycle is terminal and cashier payment/edit routes are forbidden. Refunded notes and notes whose work was canceled retain those domain labels/badges. They are not re-described as ordinary completed sales merely because they fall into the finished primary focus.

## Presentation Contract

- `Belum Selesai` is the default focus.
- The two focus controls replace open/close/refund as the cashier's primary filtering interaction.
- Search remains available and scoped to the active focus.
- Mobile uses a single-column compact card/list without a wide table or horizontal scrolling.
- Desktop may be denser, but remains a compact work queue rather than a nine-column audit table.
- Each item prioritizes transaction time, customer/note identity, total, outstanding/payment state, concise line/work context, and valid Detail/Edit actions.
- Edit is exposed only when the existing route/lifecycle policy permits it. Refunded state never gains an edit shortcut.
- Advanced domain distinctions remain visible as contextual labels. Full information remains on Detail.

## Responsive Contract

Viewport width controls layout. Mobile/tablet stays single-column, while desktop uses a denser card grid only where readable. Pointer capability may improve tap treatment, and standalone mode may apply safe-area spacing. User-agent sniffing is forbidden.

## Architecture Boundary

- No new history endpoint, mobile endpoint, transaction use case, or duplicated read model is introduced.
- The existing controller -> application query -> reader port -> projection adapter path remains authoritative.
- Finance, payment, inventory, refund, revision, audit, and projection write semantics remain unchanged.
- History presentation does not mutate notes or financial state.

## Verification Contract

Closure requires proof for:

- unpaid, partial, and outstanding notes;
- financially settled notes with open work;
- settled notes with no open work;
- refunded and canceled context preservation;
- bucket-safe search and pagination;
- projection/query boundary use;
- Detail navigation and lifecycle-valid Edit visibility;
- browser focus switching, search, pagination where present, responsive widths, and no horizontal overflow;
- no regression in transaction, payment, stock, idempotency, revision, refund, audit, or projection tests.

## Scope Out

- Redefining note, payment, settlement, refund, or work-item domain statuses.
- Simplifying the note detail/audit page.
- Production deployment, production migration, production data mutation, or CDN upload.
- Claiming physical phone or installed PWA acceptance from emulation.

## Verification Proof

- Focused history/workspace regression passed 22 tests with 178 assertions.
- Full Note regression passed 367 tests with 3,051 assertions, retaining transaction, payment, inventory, idempotency, revision, refund, audit, and projection invariants.
- Database tests prove unpaid, partial, settled/open-work, settled/done, refunded, and canceled-work classification; search remains bucket-scoped and pagination totals are calculated after server-side classification.
- Query-boundary proof confirms history reads `note_history_projection` with the existing work summary and does not mutate notes, work items, payments, inventory movements, or audit outbox state.
- A same-transaction-date regression deliberately reverses UUID lexical order against `created_at` and proves the API/UI order is newest creation to oldest creation.
- Chromium exercised focus switching, search, Detail navigation, lifecycle-valid Edit visibility, and the 360, 390x844, 412, 768, 992, and 1440x900 responsive matrix with no document/card overflow or history asset/runtime failure.
- Chromium clicked from page 1 to page 2 with a local eleven-row fixture; database/query tests independently prove totals and page contents are calculated after bucket classification.
- Final repository `make verify` passed: PHPStan analyzed 2,023 files with no errors, contract audits passed, and 1,585 tests completed with 10,135 assertions.

## Remaining GAP

Physical phone and installed standalone PWA acceptance remain manual pre-production verification unless performed on real devices.

## Next Active Step

Perform physical-device/PWA and deployed production smoke verification through the separate release workflow; do not redefine bucket classification in presentation code.
