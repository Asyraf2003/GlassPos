# 0015 - Cashier Note History Two-Focus Work Queue Blueprint

## Metadata

- Date: 2026-09-05
- Area: Cashier note history/navigation
- Status: Implemented and locally verified
- Owner decision: Cashier history final pre-production UX hardening
- Related workspace blueprint: `0014_cashier_note_workspace_simple_detail_pos_hardening.md`

## Goal

Make `Riwayat Nota` a cashier finance work queue with two primary focuses while preserving the underlying note, payment, refund, and operational-work distinctions:

1. `Belum Lunas`
2. `Selesai Hari Ini`

History is navigation and prioritization. The note detail page remains the complete operational and audit representation.

## Authoritative Read Boundary

Classification stays in the existing cashier history query adapter behind `CashierNoteHistoryTableReaderPort`. Its source is `note_history_projection`, augmented by the existing `work_items` summary. Controllers, Blade, and JavaScript do not reconstruct finance or work-state truth.

Pagination and search are applied inside the selected bucket at the query boundary. The browser never receives an unclassified collection and does not classify it locally.

## Locked Chronology

`Belum Lunas` is ordered by the time the note was actually created:

```text
notes.created_at DESC
notes.id DESC
```

`Selesai Hari Ini` is ordered by the authoritative settlement chronology:

```text
notes.closed_at DESC
notes.created_at DESC
notes.id DESC
```

`transaction_date` remains a business date, not creation or settlement chronology. Note ID is only a deterministic fallback. Bucket classification, scoped search, sorting, and pagination happen before data reaches the browser.

## Locked Classification

### Belum Lunas

A non-refunded projected note belongs to `Belum Lunas` when authoritative `outstanding_rupiah` is greater than zero.

There is no today/yesterday date window. Old debt remains visible until it is settled. Operational open/done state remains visible context but never determines finance-bucket membership.

### Selesai Hari Ini

A projected note belongs to `Selesai Hari Ini` when its authoritative `closed_at` is today and the projection is financially settled. An old note settled today moves out of `Belum Lunas` and into this focus even when its transaction date is weeks earlier.

Refunded/canceled state is never relabeled as an ordinary sale. Its domain badge remains visible when the terminal note is in the current focus. A terminal refund is not reintroduced as collectible debt merely because refund math makes a numeric projection resemble outstanding.

## Presentation Contract

- `Belum Lunas` is the default focus.
- The two focus controls replace open/close/refund as the cashier's primary filtering interaction.
- Search remains available and scoped to the active focus.
- Mobile uses a single-column compact card/list without a wide table or horizontal scrolling.
- Desktop may be denser, but remains a compact work queue rather than a nine-column audit table.
- Each item prioritizes transaction time, customer/note identity, total, outstanding/payment state, concise line/work context, and valid Detail/Edit actions.
- Edit is exposed only when the existing route/lifecycle policy permits it. Refunded state never gains an edit shortcut.
- Advanced domain distinctions remain visible as contextual labels. Full information remains on Detail.

## Admin History Contract

Admin note history is an audit list, not the cashier work queue. Its default order is true note creation chronology:

```text
notes.created_at DESC
notes.id DESC
```

It supports reusable server/query-side sorting by created time, total note, net paid, and outstanding. Sorting is applied before pagination; the browser does not sort only its current page. Unknown sort keys fail validation rather than reaching the SQL order expression.

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
- financially settled-today notes with open or completed operational work;
- old outstanding notes outside the former cashier date window;
- refunded and canceled context preservation;
- bucket-safe search and pagination;
- projection/query boundary use;
- Detail navigation and lifecycle-valid Edit visibility;
- browser focus switching, search, pagination where present, responsive widths, and no horizontal overflow;
- no regression in transaction, payment, stock, idempotency, revision, refund, audit, or projection tests.
- admin created/total/paid/outstanding server sorting before pagination.

## Scope Out

- Redefining note, payment, settlement, refund, or work-item domain statuses.
- Simplifying the note detail/audit page.
- Production deployment, production migration, production data mutation, or CDN upload.
- Claiming physical phone or installed PWA acceptance from emulation.

## Verification Proof

- Focused history/workspace regression passed 22 tests with 178 assertions.
- Full Note regression passed 383 tests with 3,166 assertions, retaining transaction, payment, inventory, idempotency, revision, refund, audit, and projection invariants.
- Database tests prove unpaid, partial, settled/open-work, settled/done, refunded, and canceled-work classification; search remains bucket-scoped and pagination totals are calculated after server-side classification.
- Query-boundary proof confirms history reads `note_history_projection` with the existing work summary and does not mutate notes, work items, payments, inventory movements, or audit outbox state.
- A same-transaction-date regression deliberately reverses UUID lexical order against `created_at` and proves the API/UI order is newest creation to oldest creation.
- Chromium exercised focus switching, search, Detail navigation, lifecycle-valid Edit visibility, and the 360, 390x844, 412, 768, 992, and 1440x900 responsive matrix with no document/card overflow or history asset/runtime failure.
- Chromium clicked from page 1 to page 2 with a local eleven-row fixture; database/query tests independently prove totals and page contents are calculated after bucket classification.
- Admin query proof covers created, total, net-paid, and outstanding sorts before pagination and rejects unknown sort keys.
- Final repository `make verify` passed: PHPStan analyzed 2,027 files with no errors, contract audits passed, and 1,600 tests completed with 10,228 assertions.

## Remaining GAP

Physical phone and installed standalone PWA acceptance remain manual pre-production verification unless performed on real devices.

## Next Active Step

Perform physical-device/PWA and deployed production smoke verification through the separate release workflow; do not redefine bucket classification in presentation code.
