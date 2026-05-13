# Error Log Finance Residual Implementation Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any `docs/04_lifecycle/error_log/*.md` finding as fixed.

This document exists to turn ADR-0018 finance lifecycle direction and related carry-forward settlement decisions into a rigid implementation workflow for remaining finance-related error logs.

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

## Source Of Truth

- docs/02_architecture/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/02_architecture/adr/0025-note-revision-carry-forward-settlement.md
- docs/03_blueprints/v2/note_finance/2026-04-29-note-finance-stabilization-blueprint.md
- docs/03_blueprints/v2/note_finance/2026-04-29-note-finance-current-projection-addendum.md
- docs/99_archive/handoff/v2/note_finance/2026-04-30-adr-0016-completion-handoff.md
- docs/05_audits/codex_security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/04_lifecycle/error_log/001-refunds-counted-as-paid-in-note-totals.md
- docs/04_lifecycle/error_log/003-refunded-revised-notes-are-misclassified-as-underpaid.md
- docs/04_lifecycle/error_log/004-refunded-work-items-survive-revisions-and-inflate-stock.md
- docs/04_lifecycle/error_log/005-note-revision-silently-drops-overpaid-allocations.md
- docs/04_lifecycle/error_log/006-client-controlled-price-basis-bypasses-minimum-price-checks.md
- docs/04_lifecycle/error_log/008-legacy-paid-notes-can-be-paid-again.md
- docs/04_lifecycle/error_log/011-cashier-revision-path-mutates-settled-note-state.md
- docs/04_lifecycle/error_log/012-canceled-note-rows-re-enter-payment-flows.md
- docs/04_lifecycle/error_log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md
- docs/04_lifecycle/error_log/014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md
- docs/04_lifecycle/error_log/017-workspace-edit-payments-ignore-existing-note-payments.md
- docs/04_lifecycle/error_log/021-refunds-can-be-recorded-on-open-notes.md
- docs/02_architecture/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/02_architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
- User owner decisions in planning sessions
- User command output from local repository
- Current source code at execution time

## Decision Boundary

This blueprint executes finance lifecycle decisions.

ADR-0018 owns domain lifecycle direction for:

- note revision
- settlement
- refund
- payment allocation
- inventory lifecycle
- external product lifecycle
- current projection
- historical ledger

The carry-forward settlement ADR owns current carry-forward decisions for revised notes with prior payment/refund.

ADR-0019 owns actor access, cashier date window, and admin transaction capability.

ADR-0022 owns payment concurrency and over-allocation protection.

This blueprint must not silently redefine ADR-0019 or ADR-0022.

When a finding crosses access and finance, both layers must pass:

1. access/capability layer
2. finance domain eligibility layer

When a finding crosses concurrency and finance, ADR-0022 transaction protocol must be followed during implementation.

## Explicit Scope

This blueprint covers finance residual implementation planning for:

- note paid/refunded settlement classification
- refund counted as paid or subtracted incorrectly
- revised/refunded note settlement
- carry-forward money after revision
- overpaid/kembalian/customer-credit decision gate
- current-only payable components
- current-only refundable components
- canceled rows entering payment/refund flows
- forged row refund validation
- open/unpaid note refund eligibility
- legacy paid notes paid again
- workspace edit preserving existing note money
- client-controlled price basis and minimum price enforcement
- refunded work items surviving revisions and stock/report impact
- bridge issue where settled note revision crosses access and domain boundary

## Non Goals

Do not patch application source from this document alone.

Do not create or modify production code before source inventory and characterization tests.

Do not change ADR-0019 access/capability policy.

Do not change ADR-0020 public surface, output, storage, or attachment policy.

Do not change ADR-0022 payment concurrency policy.

Do not implement idempotency or DB concurrency constraints here.

Do not rewrite refund engine unless fresh proof shows actual finance ledger bug and owner approves.

Do not block cashier edit/refund globally as a final solution.

Do not rewrite/delete payment/refund/history to hide mismatch.

Do not solve seeder credential safety here.

Do not redesign the whole UI.

Do not change locked domain terms.

Do not mark finance error logs fixed without proof.

## Error Log Coverage

| Error Log | Finance Theme | Coverage Direction |
|---|---|---|
| 001 refunds counted as paid in note totals | settlement classification | normalize paid/refund settlement policy |
| 003 refunded revised notes misclassified as underpaid | carry-forward settlement | settlement-level carry-forward fix, no double subtraction |
| 004 refunded work items survive revisions and inflate stock | revision/history/inventory | current projection + historical anchors + inventory movement proof |
| 005 note revision silently drops overpaid allocations | overpaid/carry-forward surplus | decision gate for overpaid/kembalian/customer-credit model |
| 006 client-controlled price basis bypasses minimum price checks | server-side price authority | server source of truth for price basis/minimum snapshot |
| 008 legacy paid notes can be paid again | paid-state detection | settlement policy must include legacy/current money correctly |
| 011 cashier revision path mutates settled note state | access + domain bridge | ADR-0019 access + ADR-0018 domain eligibility both required |
| 012 canceled note rows re-enter payment flows | current-only payable | payable components must exclude canceled/legacy rows |
| 013 forged row refund can auto-finalize unpaid notes | refund row validation | row ownership/current-state/payment eligibility validation |
| 014 refund endpoint can cancel open or unpaid note rows | refund eligibility | refund only allowed for eligible current settled/payment-backed rows |
| 017 workspace edit payments ignore existing note payments | carry-forward existing money | edit/revision must preserve and reallocate existing money |
| 021 refunds can be recorded on open notes | refund eligibility | reject refund when note/payment state is not eligible |

## Locked Finance Decisions Already Available

### Note Revision Remains Supported

Final fix must not remove note revision.

Final fix must not globally block edit after payment/refund.

Temporary containment is allowed only with explicit owner decision and must not become final domain.

### Ledger And History Must Not Be Destroyed

Payment, refund, inventory movement, and historical work item anchors are financial/historical events.

Forbidden:

- cascade delete financial history
- rewrite refund allocation silently
- detach financial allocation from historical anchor without replacement audit model
- delete old work item that anchors payment/refund/history
- nullable FK as shortcut without immutable snapshot model

### Current Projection Must Be Separate From History

The system must distinguish:

1. current operational state
2. historical ledger/audit trail

Current UI and current settlement must not accidentally count legacy rows as current.

Historical report/audit must remain able to explain old versions and events.

### Carry-Forward Settlement

Previous money on a revised note becomes carry-forward settlement for the edited note.

Rules:

1. revised total > carry-forward:
   - note is partial/open
   - outstanding is difference

2. revised total = carry-forward:
   - note is paid/lunas
   - outstanding is zero

3. revised total < carry-forward:
   - surplus must become explicit overpaid/kembalian/refund-due/customer-credit according to separate decision
   - system must not pretend note is unpaid

4. allocation priority:
   - product components first
   - service components after products

### Refund Engine Caution

Refund engine should not be changed again unless fresh proof shows actual finance ledger bug.

Settlement/status/current projection consumers should be tested before generic reader or refund engine patches.

## Decision Gates Still Open

### Gate 1 — Overpaid/Kembalian Storage Model

Required for:

- error_log 005
- downward revision where carry-forward exceeds revised total
- any flow where previous money exceeds current total

Options:

A. immediate refund due
B. customer credit
C. explicit overpaid balance
D. forced refund workflow before final close

Current status:

- not decided in final storage/workflow form
- implementation must stop before production patch if it needs final surplus storage

Temporary characterization may assert:

- surplus detected
- note is not unpaid
- no double subtraction
- no silent money loss

Production implementation needs owner decision before storing final surplus behavior.

### Gate 2 — Current Projection Storage Detail

Required for:

- current-only payable/refundable components
- legacy/canceled row exclusion
- note detail/current UI correctness
- report boundary

Current status:

- current projection direction exists
- exact table/column/model may depend on existing source state
- do not decide schema silently during patch

Implementation must inspect existing migrations/models/readers before schema decisions.

### Gate 3 — Refund Eligibility For Open/Partial Notes

Required for:

- error_log 014
- error_log 021
- forged row refund cases

Default safe direction:

- refund requires eligible payment-backed/current row state
- open/unpaid rows must not be refund-finalized through forged request
- refund eligibility must be server-side and row-level

If business wants refund on unpaid/open rows as non-money row cancellation, it must be modeled separately from money refund.

### Gate 4 — Server Price Basis Authority

Required for:

- error_log 006

Default direction:

- request/client may submit selected item/quantity
- server must resolve price basis and minimum price authority
- old minimum snapshot rule must be enforced by server
- client-submitted price basis is not trusted

Exact source of truth must be inspected from current product/pricing code before patch.

### Gate 5 — ADR-0019 Bridge For Error Log 011

Required for:

- cashier revision path mutates settled note state

Rule:

- access allowed does not imply domain operation valid
- domain operation valid does not imply actor has route access
- official audited lifecycle may allow cashier correction/revision only when both ADR-0019 and ADR-0018 rules pass

Implementation must not globally block cashier edit as shortcut.

## Risk Model

### Risk 1 — Double-Subtracted Refund

Bad flow:

1. note has gross payment
2. historical refund exists
3. revision captures net carry-forward
4. settlement subtracts historical refund again
5. note appears underpaid

Required prevention:

- settlement consumer must know whether allocation input is gross ledger or current carry-forward
- no double subtraction
- revised total equal to carry-forward must be paid

### Risk 2 — Legacy/Canceled Rows Enter Current Payment

Bad flow:

1. row becomes canceled/legacy
2. payable component resolver still includes it
3. user pays or refunds old/canceled row
4. settlement/current projection becomes wrong

Required prevention:

- payable/refundable component resolvers must be current-only
- canceled/legacy rows excluded from current mutation flows
- historical rows remain audit-only

### Risk 3 — Forged Row Refund

Bad flow:

1. request submits row id not eligible/current/owned by note
2. refund endpoint accepts it
3. row cancel/refund finalizes note incorrectly

Required prevention:

- server validates row belongs to note
- row is current/eligible
- row has payment-backed refundable amount
- note/payment state allows refund
- request amount does not exceed eligible refundable amount

### Risk 4 — Open/Unpaid Refund

Bad flow:

1. note or row has no valid paid/refundable settlement
2. refund request is accepted
3. system cancels row or finalizes note via refund path

Required prevention:

- refund money flow requires paid/refundable settlement
- non-money cancellation must be separate domain action if allowed
- refund endpoint must not be used as generic row delete

### Risk 5 — Client-Controlled Price Basis

Bad flow:

1. request sends price basis or old minimum values
2. server trusts client
3. minimum price protection bypassed
4. report/profit/pricing becomes unsafe

Required prevention:

- server resolves price basis
- server enforces minimum price/snapshot
- client values are hints only when safe

### Risk 6 — Existing Money Lost During Edit

Bad flow:

1. note has previous payment/refund
2. workspace edit/revision ignores existing money
3. payment appears lost or note reopens incorrectly

Required prevention:

- revision captures and carries forward existing valid money
- existing money reallocated by priority
- no silent drop of previous settlement
- surplus becomes explicit when needed


---

## Related Documents

- DoD: docs/03_blueprints/finance/finance-residual-dod.md
- Workflow: docs/03_blueprints/finance/finance-residual-workflow.md
