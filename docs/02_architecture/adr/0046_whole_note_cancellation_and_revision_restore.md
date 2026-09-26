# ADR-0046: Whole-Note Cancellation And Revision Restore

Status: Accepted owner business direction; technical contract recorded for implementation

Date: 2026-09-26

Scope: persisted customer-note cancellation, current effects, history, refund boundary, restore

Traceability: [Issue #1](https://github.com/Asyraf2003/GlassPos/issues/1), [owner record0029](../../04_lifecycle/handoff/0029_whole_note_cancellation_owner_decision_handoff.md), [Blueprint0019](../../03_blueprints/finance/0019_whole_note_cancellation_and_restore.md).

Refines the cancellation gaps in ADR-0044/0045 and Blueprint0018, and ADR-0042's Event And Source Type Mapping: its stock-line reversal source now also identifies cancellation compensation, with the separate lifecycle event identifying the cause. ADR-0042 still owns settlement/refund meaning; ADR-0045 owns immutable revisions. ADR-0007 capability policy remains accepted; ADR-0019 remains draft. This ADR does not claim production support or change unrelated refund defaults.

## Context and authority

Owner handoff0029 distinguishes cancellation from refund and correction. The session instruction explicitly authorizes promoting those decisions before implementation. The raw evidence below is separate from normalized policy and technical choices; it must not be represented as an owner endorsement of field names or algorithms.

Current source has open/closed/refunded roots, stock compensation, revision snapshots, refund allocations, audit outbox and command receipts, but no whole-note cancellation/restore. Merely hiding a root leaves its component obligations and inventory effects active.

## RAW OWNER STATEMENT

Selected verbatim excerpts from handoff0029 follow. Both complete raw blocks remain at that source, unchanged.

> 3. untuk ini uang masuk tercatat tapi uang fisiknya g ada itu kesalahan kasir, tetep masuknnya ke refund dan kasir mempertangungjawabkan isi soal uang tapi uangnya g ada, gimana klo dumy? tetep refund, karena ada laporan keunangan disana beda dengan simpan nota saja tanpa membayar 1 rp pun

> 11. untuk ini klo data sudah ditulis sudah disimpan itu data barang luar masuknya transaksi walaupun full hutang, masuk area refund dia

> b auto g dibuat, buat saja versi detailnya, auto dibuat karena kasir ngeluh ini kok proses lama bisa ngga ini auto a b c aja, nah auto lahir darisana bukan lahir dari build

The current owner campaign also states verbatim:

> Restore berarti membuat **new accepted revision/version** berdasarkan history sebelumnya.

> R1 -> Cancel -> R2 restored/current.

> Cancellation tetap historical fact.

## Normalized Contract

### Intents and eligibility

| Intent / facts | Required lifecycle |
|---|---|
| Unsaved workspace | Buang/Kosongkan Draf; no business cancellation event. |
| Persisted note, no recorded payment, no committed external-purchase consequence | Batalkan Transaksi can neutralize the entire current graph. Product, service and package shape do not themselves prohibit this. |
| Any recorded customer payment, including fully refunded or allegedly dummy money | Route to existing Refund lifecycle; never delete payment, invent payment reversal, or treat net-zero settlement as never-paid. |
| Committed external purchase, including full debt | Existing external/refund transaction boundary; not pure cancellation. |
| Real transaction with incorrect price/data | Edit/Revision through immutable version graph. |
| Cancelled note requested again | Replay prior command or reject/no-op without a second business effect, according to command identity below. |
| Cancelled note to resume | Pulihkan creates a new accepted revision. No flag toggle or soft-delete restore. |

For pure cancellation: active customer sale/revenue, receivable and transaction profit are zero; transaction-owned inventory effect is compensated. History remains reconstructable. No customer money-in, refund due or money-out is invented. A real independent material loss uses existing stock/cost adjustment, with reason/reference, rather than residual cancelled-sale COGS.

Physical hand-to-hand handling is not an inventory event. Compensate only committed business effects: issue -N followed by one compensation +N at the original issue cost.

Refund money and physical return remain distinct. `refund_paid` means actual money-out. Existing default refund restrictions are not lifted by this ADR. If a requested refund case is unsupported, the UI/backend must report that boundary truthfully and leave the graph unchanged; redirecting alone is not successful cancellation/refund.

### Current versus historical truth

The root marker is `note_state = cancelled`. It expresses inactive customer business, not financial or stock authority. Cancellation must also neutralize current component rights/obligation, compensate source movements and synchronously update current projections within one transaction.

Keep the last accepted revision and its snapshot immutable. Cancellation is a separate historical lifecycle event over that revision. The current pointer remains R1 until restoration accepts R2. Current readers must combine current revision with lifecycle/component eligibility; historical readers must still show R1's original values. No report-side repair is allowed.

Current sales/receivable/profit aggregations exclude cancelled active effect. Historical/detail/cancellation views retain the note and explain original values, compensation, reason, actor and time. Event reports retain actual issue/compensation chronology; zero current effect does not mean erasing earlier-period events.

## Technical implementation decisions

These choices implement the owner contract; they are not raw owner statements. A contradictory source finding must be resolved in the blueprint/ADR before production patching.

### Command identity and ordering

- Require authenticated actor, nonblank reason, `base_revision_id` and `idempotency_key` for cancellation. Root identity comes from the route/command target, never an independently trusted body alias.
- Use existing actor/operation/key receipt storage with operation `cancel_note`. Semantic payload includes target root, base revision and normalized reason; actor scope is included. No optional key bypass for this new sensitive command.
- Authorize actor/date/capability, then resolve exact successful replay or changed-payload conflict using the existing protocol. A fresh command validates current base and eligibility under the canonical note-root lock.
- Same key + same semantics replays the original result, even if a later revision has changed current state, subject to existing authorization. Same key + different semantics conflicts. Preserve existing typed claim-collision rollback/replay handling.
- A fresh key against an already cancelled root returns `NOTE_ALREADY_CANCELLED` without new cancellation, stock, money or version effects. A stale fresh base returns `STALE_REVISION`; check base before current cancellation eligibility.
- Ordinary payment/refund/edit/add-row/status operations cannot reactivate a cancelled root. Enforce under-lock domain guards, not just absent UI buttons. Restore is the explicit reactivation path.

### Persistence and durable event

Reuse string `notes.note_state`; no soft-delete column or destructive root deletion. Preserve work-item/component historical anchors; neutralize current rows using the existing inactive/canceled semantics without rewriting snapshot amounts. Do not call destructive workspace replacement to perform cancellation.

Record `note_cancelled` through existing note mutation event/snapshot storage and canonical `AuditEventWriterPort` in the same business transaction. Use one stable cancellation event identity as the command result/reference. Existing note mutation storage can hold actor, role, reason and occurred time; before/after metadata must also identify root, base revision, affected components and actual compensation movement IDs. Canonical audit carries this cancellation identity.

Before/after capture must preserve the original header/shape/status and the resulting inactive graph. Reason/actor/action time come from validated command/server context. Compensation business date is the action's date in the existing Asia/Makassar business timezone; retain the actual timestamp separately. Do not backdate cancellation to original sale date merely to zero a historical period.

Eligibility must establish absence of recorded payment using official historical links, refunds and settlement evidence, not current allocation or paid badge alone. Historical evidence of money requires Refund. Unresolvable legacy linkage must fail safely with no mutation; do not classify unknown as unpaid or introduce a payment-link backfill in this slice.

### Inventory compensation

Reuse `ReverseIssuedInventoryOperation` and existing `work_item_store_stock_line_reversal` source identity/uniqueness. This source represents compensation of the issue; `note_cancelled` identifies the cancellation cause, without fabricating a customer refund. Existing reporting/UI labels that assume every such movement is a money refund must be made cause-aware where exposed.

Preflight actual current issue identities and prior compensation under root/inventory locks. Return original quantities/costs exactly once. Require complete effect reconciliation before commit; the generic reversal's missing-inventory/costing skip is insufficient proof of success. Missing or inconsistent required effect data rejects atomically. No second costing algorithm or guessed movement is permitted.

Restore must recognize cancellation compensation, so revision replacement cannot return that issue again. Existing revision reversal already skips the stock-line reversal source; prove that boundary with actual restore before relying on it.

### Restore as accepted revision

Restore command requires base revision, reason, key and the current cancellation event identity. The cancellation identity distinguishes lifecycle cycles independently of the revision pointer. Validate both under the same root lock; reject stale event/base and non-cancelled targets.

Load a draft from immutable prior revision through existing revision mappers. Accept an explicitly reviewed current payload through the revision workflow, price/stock validation, snapshot factory, current-pointer writer, settlement and audit boundaries. Old component IDs stay historical; new current components receive fresh identities and issue only required stock. No old refund shadow is revived.

Use an operation-specific restore receipt scope with existing replay/conflict protocol. Commit R2 parent R1, current state/effects and `note_restored` history linked to cancellation plus `note_revision_created` atomically. Cancellation history remains. Insufficient stock, stale identity, snapshot/audit failure or payment validation failure rolls everything back. Any optional payment uses the existing payment engine, never cloned history.

### Access and UI

Reuse cashier today/yesterday operational access and admin transaction capability. Admin broad reading is not mutation authority. Do not inherit generic closed/refunded denial as a blanket rule for an official lifecycle, and do not relax unrelated old routes. ADR-0019 remains draft; authority is owner direction, accepted ADR-0007/0016 and the existing policy seams.

Build Detail with explicit effect preview, required reason, cancellation/refund distinction, server eligibility and history. Normal refund UX can feel like returning money while retaining backend due/paid meanings. Auto presets wait for observed cashier usage; no new dashboard is required.

## Proof obligations and consequences

Blueprint0019 owns bounded tests: unpaid product/service/package; source-cost compensation; current report exclusion and immutable history; recorded-payment/external rejection/routing; same-key replay/conflict; stale base; real overlapping cancel/cancel, cancel/payment, cancel/refund and cancel/revision where applicable; restore; actual canonical writer failure rollback. Existing Blueprint0018 regressions remain the baseline.

This creates a coordinated lifecycle change across existing primitives. Root state, obligations, stock and required projections must not ship as disconnected partial correctness changes. Restore/UI and independently valid dependencies may use subsequent focused Issue/PR flows. Each merge must remain valid and tested within its stated scope.

Rejected: SQL/soft deletion, paid cancellation bypass, invented payment-reversal engine, zero-value money refund, overwritten R1, restore by state toggle, duplicated inventory/report engine, guessed Auto presets and sequential tests labeled concurrency.

## Explicit implementation gaps retained

Current refund type/settlement restrictions and unconditional stock return conflict with richer ADR-0042 capabilities. They are existing dependencies, not resolved by this documentation. External supplier ownership/return/reuse rules not established in accepted contracts remain out of scope pending actual need and evidence. No production-ready claim follows from accepting this ADR.
