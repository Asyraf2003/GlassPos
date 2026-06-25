# 0044 Edit After Paid Refund Shadow UI Report Lifecycle Workflow

## Status

Canonical workflow for error log `0044`.

This file is not an implementation patch.

This file does not mark `0044` as fixed.

## Session Rule

Every 0044 session must update the active handoff:

`docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md`

A session is not complete until the handoff checklist is updated with:

- files read
- source facts found
- files changed
- tests added or changed
- commands run
- PASS/FAIL output summary
- residual gaps
- next allowed slice

## Connector Rule

Do not execute GitHub connector write actions for this workflow.

The assistant must provide local CLI commands only, unless the owner explicitly overrides this rule in the same session.

Forbidden by default:

- GitHub.create_file
- GitHub.update_file
- GitHub.delete_file
- GitHub.create_branch
- GitHub.create_pull_request
- GitHub.merge_pull_request

Allowed by default:

- local CLI command generation
- read-only source analysis
- local test command planning

Reason:

The owner controls local verification, git diff, commit, and push manually.

## Core Domain Policy

The target lifecycle policy is:

1. Notes with unpaid debt may be edited through the official revision path.
2. Paid notes may be edited through the official revision path.
3. Refunded effects are ledger/shadow historical truth and must not be overwritten by later edit.
4. Edit after refund must not reset:
   - customer payments
   - payment component allocations
   - customer refunds
   - refund component allocations
   - inventory movements
   - cash ledger
   - current projection
   - report/export source records
5. Downward edit after payment must expose surplus explicitly.
6. Downward edit after paid/refund/delete-all active lines must expose money status explicitly:
   - overpaid_pending
   - refund_due
   - refund_paid
   - future customer credit only after stable customer identity is officially decided
7. UI must render backend-derived action flags and status text.
8. Browser refresh, Ctrl+R, and Ctrl+Shift+R must not change truth or expose invalid actions.
9. Screen report, PDF, and Excel must read one official dataset contract.

## Non-Negotiable Architecture Rules

- Backend owns money.
- Backend owns stock effects.
- Backend owns settlement preview.
- Backend owns payable/refundable amount.
- UI displays and transports intent only.
- JavaScript may assist, but must never be financial truth.
- Report is read model, not mutation engine.
- Export must not diverge from report dataset.
- Current rows and historical ledger anchors must not be mixed casually.

## Workflow Slices

### Slice 0 - Structure, Source Map, and Guardrail

Goal:

Map active source before patch.

Allowed:

- docs only
- source-map commands
- route/source inventory
- no production code patch
- no test weakening

Must map:

- active edit routes
- active refund routes
- active payment routes
- note detail page data builder
- UI action flag builders
- note revision services
- payment allocation services
- refund allocation services
- inventory movement services
- note history projection
- surplus/refund_due/refund_paid records
- transaction report dataset
- PDF export builder
- Excel export builder

Exit criteria:

- active source map documented
- dead paths listed
- first RED characterization target selected
- handoff checklist updated

### Slice 1 - Edit After Unpaid/Paid Note

Goal:

Prove and harden edit behavior for unpaid and paid notes.

Required tests:

- edit unpaid note preserves outstanding calculation
- edit paid note carries existing payment exactly once
- edit paid note upward creates backend-derived outstanding
- edit paid note downward creates explicit surplus state
- edit delete-all active lines after paid creates explicit surplus state

Forbidden:

- resetting payment rows
- rebuilding allocations without lock/source proof
- hiding surplus as unpaid
- relying on UI total

### Slice 2 - Refund Shadow / Historical Truth

Goal:

Prove and harden refund as immutable ledger/shadow truth.

Required tests:

- edit after ordinary refund does not double-subtract refund
- edit after refund keeps refund rows traceable
- edit after refund does not delete historical refund anchors
- refund after revision accepts current row ids
- refund after revision rejects stale historical row ids
- refund money effect and stock return effect stay separate

Forbidden:

- deleting customer_refunds
- deleting refund_component_allocations
- mutating historical refund anchors as if they are current rows
- triggering duplicate stock reversal

### Slice 3 - Combined Edit + Refund + Payment + Stock Matrix

Goal:

Catch lifecycle bugs before they breed.

Required matrix dimensions:

- unpaid note
- partially paid note
- fully paid note
- paid then ordinary refund
- paid then package component refund
- edit upward
- edit downward
- edit delete-all active lines
- service-only row
- product-only row
- service_store_stock row
- service package stock components
- cash payment
- transfer payment

Required assertions:

- payment rows are not silently lost
- refund rows are not silently lost
- allocation totals match official settlement
- stock movements do not double reverse
- inventory net state is explainable
- note projection matches official ledger
- payable components do not reopen invalidly

### Slice 4 - UI Backend-Derived Action Flags

Goal:

Make note detail and workspace UI reflect backend truth.

Required tests:

- note detail does not show invalid Lunasi/Bayar action
- note detail shows clear reason when payment is blocked
- edit action visibility matches lifecycle policy
- refund action visibility matches refundable rows from backend
- UI status labels explain:
  - Hutang
  - Lunas
  - Refund
  - Uang Lebih
  - Refund Due
  - Refund Paid
  - Stok Dikembalikan
  - Tidak Bisa Dibayar Ulang
- static render survives empty JS
- backend flags exist before JS enhancement

Forbidden:

- JS-only action gating
- hiding backend failure behind generic error
- showing action that backend will always reject without explanation

### Slice 5 - Browser Refresh Resilience

Goal:

Ctrl+R and Ctrl+Shift+R must not expose stale/wrong UI state.

Required tests or manual QA proof:

- normal refresh preserves correct action visibility
- hard refresh preserves correct action visibility
- modal default values come from backend state
- stale browser payload is rejected by backend
- old hidden inputs cannot revive invalid payment/refund/edit action

Allowed:

- PHP render tests
- static JS contract tests
- manual browser QA checklist

Forbidden:

- treating browser cache as source of truth
- accepting stale hidden values without backend verification

### Slice 6 - Report / PDF / Excel Parity

Goal:

All reporting surfaces read the same official dataset contract.

Required report surfaces:

- transaction report screen
- cash ledger screen if affected
- operational profit if affected
- service package profit breakdown if affected
- Excel export
- PDF export view data
- PDF Blade output

Required fields when affected:

- gross transaction
- payment in
- refund out
- surplus refund paid
- remaining refund due
- net cash
- outstanding
- note lifecycle status
- stock effect status
- package sold amount
- refunded package component amount
- COGS net effect
- margin/profit explanation

Forbidden:

- making report "look right" by hiding mismatch
- divergence between screen and export
- changing formula before write-side invariant is proven

### Slice 7 - Final Blast-Radius Verification

Goal:

Only claim fixed after complete proof.

Required commands:

- targeted 0044 tests
- relevant note revision tests
- relevant payment tests
- relevant refund tests
- relevant inventory tests if touched
- relevant reporting tests if touched
- relevant export tests if touched
- make verify
- git diff stat
- error log grep proof
- handoff checklist proof

Exit criteria:

- error log updated after proof
- handoff marks all completed slices
- residual gaps are explicit
- no unrelated changes
- owner accepts result

## Global Stop Conditions

Stop immediately if:

- failing test reason is not understood
- patch deletes payment/refund/history records
- patch silently drops existing note money
- patch treats surplus as unpaid
- patch globally blocks edit/refund as a shortcut
- patch trusts client-side price/payment/refund amount
- patch creates UI-only financial state
- patch changes report formula before write-side source is proven
- patch mixes current rows and historical anchors without explicit boundary
- patch touches dead/unproven route path
- patch requires customer credit before customer identity contract is stable
- patch weakens unrelated tests
- patch marks error log fixed before proof

## Definition of Done

### DoD Per Slice

A slice is done only when:

- source files touched are listed
- source files avoided are listed
- RED proof exists before patch when patching runtime behavior
- targeted test passes after patch
- relevant existing tests pass
- no unrelated test is weakened
- money examples are exact
- stock examples are exact when stock is affected
- UI text is asserted when UI is affected
- report/export parity is asserted when report/export is affected
- handoff checklist is updated
- residual gaps remain visible

### DoD For Full 0044

`0044` is fixed only when all are true:

- edit after unpaid note is proven
- edit after paid note is proven
- edit after refund is proven
- refund shadow/historical truth is proven
- delete-all active lines after paid/refund has explicit money status
- surplus/refund_due/refund_paid lifecycle is proven
- stock movement lifecycle is proven
- UI action flags are backend-derived
- refresh/hard-refresh does not expose invalid actions
- report screen/PDF/Excel parity is proven
- make verify passes
- error log is updated after proof
- handoff marks workflow complete

## Verification Command For This Workflow File

```bash
grep -n \
  -e "## Session Rule" \
  -e "## Connector Rule" \
  -e "## Core Domain Policy" \
  -e "## Workflow Slices" \
  -e "### Slice 0" \
  -e "### Slice 7" \
  -e "## Global Stop Conditions" \
  -e "## Definition of Done" \
  docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md
```
