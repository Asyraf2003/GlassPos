# 0011 - Cashier Stepper Mobile UI Redesign Blueprint

## Metadata

- Date: 2026-06-03
- Area: Cashier UI
- Selected direction: Variant 02 - Stepper Nota Mobile
- Status: Approved direction, not implemented
- Implementation state: Pending
- Source decision: UI lab review selected Variant 02 as the preferred direction

## FACT

Variant 02 - Stepper Nota Mobile is selected as the preferred UI direction.

The previous 10-variant exploration was reduced because too many alternatives distracted from the real goal.

The selected direction is not pure Google Form and not pure POS cart.

The selected direction is a middle path:

- mobile-first
- clear workflow
- sectioned by step
- simple like Google Form
- practical for cashier transaction work
- suitable for create and edit transaction flows

## DECISION

Use Stepper Nota Mobile as the cashier-wide UI direction.

The cashier area should use the same visual and interaction language across:

- cashier dashboard
- cashier note list/history
- cashier note detail
- create transaction workspace
- edit transaction workspace
- payment action surfaces
- refund action surfaces where visible to cashier
- table/filter/search/action surfaces used by cashier

Admin UI is out of scope unless explicitly included in a later blueprint.

## Core UI Model

The cashier UI should be organized around this mental model:

1. Context
2. Work
3. Review / Action

For create transaction:

1. Info Nota
   - customer name
   - customer phone
   - date
   - operational note / reason

2. Rincian
   - product
   - service
   - service + store-stock sparepart
   - service + external purchase
   - package total where relevant
   - quantity and selected item summary

3. Pembayaran
   - review total
   - skip payment
   - pay full
   - pay partial
   - cash / transfer decision
   - confirmation action

For edit transaction:

1. Context Edit
   - existing note identity
   - customer
   - date
   - edit/revision mode indicator
   - current status

2. Ubah Rincian
   - current rows
   - edited rows
   - add/remove/update items
   - changed total summary

3. Review Revisi / Payment
   - before/after total
   - outstanding or settlement summary
   - payment/refund decision if applicable
   - save revision action

For cashier dashboard:

1. Today Snapshot
   - quick summary cards
   - today transaction count
   - today revenue/payment snapshot if already available
   - pending/unpaid indicator if available

2. Quick Actions
   - create note
   - continue draft if available
   - note history
   - relevant cashier actions

3. Work Queue / Recent Activity
   - recent notes
   - unpaid/open items
   - items needing cashier action

## Visual Direction

Use a calm mobile-first card UI.

The style should feel like a practical stepper form:

- clean page background
- one-column layout on mobile
- card-based sections
- clear step labels
- large readable labels
- large tap targets
- sticky total/action when useful
- minimal decorative chrome
- no dense desktop table layout as the primary mobile surface
- no unnecessary dashboard/sidebar feel inside the create/edit workflow

The design should not copy Mazer desktop cards directly.

The design should not remain PC-first with `col-xl-*` as the primary layout.

## Interaction Rules

### Mobile-first

Every cashier page must be usable on mobile width first.

Desktop may become a wider version of the mobile layout, not the other way around.

### One primary action per screen region

Avoid showing too many equal-weight buttons.

Primary actions should be obvious:

- Continue
- Review
- Process
- Save
- Pay
- Cancel as secondary

### Stepper flow must not hide critical totals

Totals must remain visible at review/payment points.

Create/edit transaction should always make the current total easy to see before final action.

### Do not destroy existing backend contract

The redesign is visual and interaction-layer first.

Backend contracts must remain intact unless a later backend blueprint explicitly changes them.

### Reuse existing business behavior

Existing create/edit transaction behavior remains source of truth until proven otherwise.

The redesign must wrap or reorganize UI without changing transaction semantics.

## Scope In

- Cashier dashboard visual redesign
- Cashier create transaction workspace redesign
- Cashier edit transaction workspace redesign
- Cashier note index/history mobile layout
- Cashier note detail mobile layout
- Cashier-visible payment/refund action surfaces
- Shared cashier mobile layout components
- Stepper shell components
- Sticky review/total/action components
- Mobile-friendly cards, filters, and action groups
- Documentation before implementation

## Scope Out

- Admin UI redesign
- Database schema changes
- Transaction handler changes
- Request validation changes
- Payment settlement logic changes
- Refund ledger logic changes
- Report/export changes
- Production replacement without browser/mobile review
- Full desktop design system replacement outside cashier scope

## Candidate Files To Inspect Before Patch

The next implementation session must inspect actual current files before patching.

Likely entry points include:

- routes/web/note.php
- app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspacePageController.php
- app/Adapters/In/Http/Controllers/Cashier/Note/EditTransactionWorkspacePageController.php
- resources/views/cashier/notes/workspace/create.blade.php
- resources/views/cashier/notes/workspace/partials/rincian-card.blade.php
- resources/views/cashier/notes/workspace/partials/info-card.blade.php
- resources/views/cashier/notes/workspace/partials/payment-modal.blade.php
- resources/views/cashier/notes/workspace/partials/payment-modal-left.blade.php
- resources/views/cashier/notes/workspace/partials/payment-modal-right.blade.php
- resources/views/cashier/notes/workspace/partials/payment-modal-cash.blade.php
- resources/views/cashier/notes/workspace/partials/payment-modal-footer.blade.php
- resources/views/cashier/notes/workspace/partials/templates/product.blade.php
- resources/views/cashier/notes/workspace/partials/templates/service.blade.php
- resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php
- resources/views/cashier/notes/workspace/partials/templates/service-external.blade.php
- resources/views/cashier/notes/history or equivalent cashier list views
- resources/views/cashier/notes/detail or equivalent cashier detail views
- resources/views/cashier/dashboard or equivalent dashboard views
- public/assets/static/js/pages/cashier-note-workspace/rows.js
- public/assets/static/js/pages/cashier-note-workspace/search.js
- public/assets/static/js/pages/cashier-note-workspace/summary.js
- public/assets/static/js/pages/cashier-note-workspace/payment-flow.js
- public/assets/static/js/pages/cashier-note-workspace/draft.js
- public/assets/static/js/pages/cashier-note-workspace/boot.js

This list is a starting point, not proof that all files exist locally.

Local command output is still the source of truth.

## Implementation Strategy

### Phase 0 - Inventory

Goal:

Find the actual cashier UI files, current layout dependencies, and JS hooks.

No patch.

Required proof:

- file existence check
- route references
- view includes
- current line counts
- current JS hook map

### Phase 1 - Shared Cashier Stepper UI Shell

Goal:

Create reusable Blade partials/CSS for the selected stepper style.

Possible components:

- cashier mobile page shell
- stepper header
- step card
- sticky total action bar
- mobile action group
- summary card
- compact status badge

Do not change create/edit behavior yet.

### Phase 2 - Create Transaction Redesign

Goal:

Apply stepper style to create transaction workspace.

Suggested steps:

1. Info Nota
2. Rincian
3. Pembayaran / Review

Must preserve existing create behavior and form contract.

### Phase 3 - Edit Transaction Redesign

Goal:

Apply the same stepper style to edit workspace.

Suggested steps:

1. Context Edit
2. Ubah Rincian
3. Review Revisi / Payment / Refund surface

Must preserve edit/revision behavior.

### Phase 4 - Cashier Dashboard Redesign

Goal:

Make dashboard match stepper/mobile card style.

Suggested sections:

1. Today Snapshot
2. Quick Actions
3. Work Queue / Recent Activity

### Phase 5 - Cashier List and Detail Redesign

Goal:

Make history/list/detail pages mobile-friendly and visually consistent.

Suggested direction:

- card list on mobile
- compact filters
- clear status badges
- primary action grouping
- detail pages grouped by summary, items, payments, actions

### Phase 6 - Verification

Required verification should be decided from touched files.

Minimum expected verification:

- route:list for affected cashier routes
- view clear
- targeted feature/browser checks if available
- grep for selected style components
- line count audit
- make verify if implementation touches enough production code

## Acceptance Criteria

The redesign is acceptable only when:

- create transaction can still be used
- edit transaction can still be used
- cashier dashboard is visually consistent
- cashier list/detail are mobile usable
- no backend contract is changed without explicit blueprint
- no production behavior is claimed working without proof
- browser/mobile review is completed
- old UI lab remains only as reference or is removed after adoption decision

## Risks

- Existing workspace JavaScript may depend on IDs/data attributes.
- Payment modal behavior may need careful conversion into stepper review.
- Edit/revision/payment/refund flows are more sensitive than create.
- Dashboard data source may differ from UI assumptions.
- Cashier list/detail may use desktop table plugins that do not translate well to mobile.
- Too much redesign in one patch can make verification noisy.

## Decision Summary

Use Variant 02 - Stepper Nota Mobile as the official UI direction for cashier redesign.

Do not implement the whole cashier redesign in one patch.

Start next session with inventory and mapping, then patch incrementally.

## Next Active Step

Inventory cashier UI files and produce an implementation map.

Do not patch production UI before the inventory map is written.
