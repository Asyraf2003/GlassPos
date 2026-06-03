# 0010 - Create Transaction Mobile Google Form UI Lab Blueprint

## Metadata

- Date: 2026-06-03
- Slice / topic: Create transaction mobile UI experiment
- Workflow step: Blueprint
- Status: Proposed, not implemented
- Progress: Blueprint only

## Target

Create a dedicated UI lab for the create transaction workspace with 10 mobile-first variants inspired by Google Form simplicity.

The goal is to compare several mobile UI approaches before changing the production create transaction page.

## Current State

The current create transaction workspace uses:

- `resources/views/cashier/notes/workspace/create.blade.php`
- `resources/views/cashier/notes/workspace/partials/rincian-card.blade.php`
- `resources/views/cashier/notes/workspace/partials/info-card.blade.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal.blade.php`
- dynamic Blade templates for transaction item types
- existing workspace JavaScript for row handling, product search, summary, payment flow, draft restore, and bootstrapping

The production create page currently mixes desktop-oriented layout with mobile usage needs.

## Problem

The create transaction UI is used on mobile, but the current layout still behaves like a PC-first workspace.

The UI must become simpler and more mobile-friendly without losing the existing transaction contract.

## Binding Rules

- Do not patch production `create.blade.php` before the UI lab is reviewed.
- Do not change backend store logic.
- Do not change validation rules.
- Do not change database schema.
- Do not treat create UI proof as edit, refund, or revision proof.
- Preserve the create transaction payload contract.
- Preserve traceability between UI fields and backend request fields.
- Implementation must remain step-by-step.

## Contract To Preserve

### Form

- Form id:
  - `cashier-note-workspace-form`
- Create action:
  - `route('notes.workspace.store')`
- Method:
  - `POST`

### Note Fields

- `note[customer_name]`
- `note[customer_phone]`
- `note[transaction_date]`
- `note[operational_note]`

### Item Fields

- `items[n][entry_mode]`
- `items[n][description]`
- `items[n][part_source]`
- `items[n][pricing_mode]`
- `items[n][package_total_rupiah]`
- `items[n][service][name]`
- `items[n][service][price_rupiah]`
- `items[n][service][notes]`
- `items[n][product_lines][m][product_id]`
- `items[n][product_lines][m][qty]`
- `items[n][product_lines][m][unit_price_rupiah]`
- `items[n][external_purchase_lines][0][label]`
- `items[n][external_purchase_lines][0][qty]`
- `items[n][external_purchase_lines][0][unit_cost_rupiah]`

### Inline Payment Fields

- `inline_payment[decision]`
- `inline_payment[payment_method]`
- `inline_payment[paid_at]`
- `inline_payment[amount_paid_rupiah]`
- `inline_payment[amount_received_rupiah]`
- `inline_payment[notes]`

### Important UI/Data Hooks

The UI lab must preserve or intentionally map these hooks if it reuses existing JavaScript:

- `cashier-note-workspace-form`
- `cashier-note-workspace-config`
- `workspace-line-items`
- `workspace-empty-state`
- `workspace-add-button`
- `workspace-item-type-menu`
- `workspace-open-payment-dialog`
- `data-line-item`
- `data-item-type`
- `data-line-title`
- `data-remove-line`
- `data-product-line`
- `data-product-lines`
- `data-product-line-template`
- `data-add-product-line`
- `data-remove-product-line`
- `data-product-search`
- `data-product-results`
- `data-product-id`
- `data-price-input`
- `data-price-basis`
- `data-qty-input`
- `data-money-raw`
- `data-money-display`
- `data-package-total-input`
- `data-pricing-mode`

## Scope In

- Create a UI lab page for create transaction mobile experiments.
- Build 10 mobile-first layout variants.
- Keep variants as Blade-based prototypes.
- Preserve realistic form field names and submit contract.
- Add only routing/controller/view files required to access the lab.
- Use the UI lab to compare concepts before production adoption.

## Scope Out

- Production create page replacement.
- Backend handler changes.
- Store request validation changes.
- Database changes.
- Edit transaction lifecycle.
- Refund lifecycle.
- Revision lifecycle.
- Report/export changes.
- Product lookup backend changes.
- Payment settlement backend changes.

## Recommended File Plan

### New Controller

- `app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspaceMobileUiLabPageController.php`

### Route

Add inside cashier notes route group:

- `GET /cashier/notes/workspace/mobile-ui-lab`
- route name:
  - `cashier.notes.workspace.mobile-ui-lab`

### New Views

- `resources/views/cashier/notes/workspace/mobile-ui-lab.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/styles.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-tabs.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/form-shell.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-01.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-02.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-03.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-04.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-05.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-06.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-07.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-08.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-09.blade.php`
- `resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-10.blade.php`

## UI Variants

### Variant 01 - Google Form Single Scroll

One long mobile-first form.

Purpose:

- Establish the simplest baseline.
- Reduce page complexity.
- Make the full transaction flow readable from top to bottom.

### Variant 02 - Google Form Step Cards

Create visual step cards:

1. Info Nota
2. Rincian
3. Pembayaran

Purpose:

- Make workflow sequence clearer.
- Avoid overwhelming the cashier with all fields at once.

### Variant 03 - Compact Cashier Fast Entry

Fast entry mode with:

- fewer helper texts
- larger primary fields
- large action buttons
- minimal decoration

Purpose:

- Optimize for repeated cashier usage.

### Variant 04 - Sticky Total Bottom Bar

Use bottom sticky bar containing:

- current total
- primary process button
- optional quick save action

Purpose:

- Keep the main action reachable on mobile.

### Variant 05 - Accordion Sections

Use collapsible sections:

- Informasi Nota
- Rincian Nota
- Pembayaran

Purpose:

- Reduce scroll fatigue.
- Keep context grouped.

### Variant 06 - Service Store Stock First

Prioritize service + store-stock package flow:

- service name
- package total
- sparepart search
- qty
- add sparepart

Purpose:

- Optimize the most complex create flow before generalizing.

### Variant 07 - Card Per Item

Every transaction line becomes a dedicated mobile card.

Purpose:

- Improve readability for multiple item entries.
- Make remove/edit actions local to each item.

### Variant 08 - Review Before Payment

Show a review page/sheet before payment action.

Purpose:

- Reduce accidental submit.
- Give clear summary before choosing skip/full/partial payment.

### Variant 09 - One Hand Mode

Design around thumb reach:

- main actions near bottom
- full-width fields
- minimal horizontal controls
- large touch targets

Purpose:

- Improve mobile ergonomics during real cashier usage.

### Variant 10 - Dense Mobile Power User

Keep mobile layout but make it denser.

Purpose:

- Serve experienced users who need speed, not hand-holding.

## Recommended Implementation Order

### Step 1 - UI Lab Shell

Create:

- controller
- route
- main lab view
- styles partial
- variant tabs partial
- one initial variant

Proof target:

- route exists
- page renders
- no production create page changed

### Step 2 - Add 10 Static Variants

Create all 10 variant partials.

Proof target:

- all partials are included and renderable
- no backend behavior changed

### Step 3 - Add Variant Switcher

Add simple query/tab switcher.

Proof target:

- each variant can be opened directly
- UI state is readable on mobile width

### Step 4 - Contract Audit

Check that prototype keeps required field names and action contract.

Proof target:

- grep confirms required names exist
- route/list confirms UI lab route exists

### Step 5 - Manual Mobile Review

Open in mobile width and choose one or two candidates.

Proof target:

- manual screenshots or user confirmation
- chosen variant documented before production patch

### Step 6 - Production Adoption Blueprint

Only after selection, write a production adoption blueprint.

Proof target:

- selected variant is explicitly chosen
- affected production files listed
- rollback path listed

## Verification Commands

Run from repo root after implementation:

```bash
php artisan route:list --name=mobile-ui-lab
php artisan view:clear
rg -n "mobile-ui-lab|CreateTransactionWorkspaceMobileUiLabPageController" routes app resources/views
rg -n "note\\[customer_name\\]|items\\[|inline_payment\\[decision\\]" resources/views/cashier/notes/workspace/mobile-ui-lab*
```

If implementation touches PHP or Blade enough to require full verification:

```bash
make verify
```

## Risks

- Reusing existing workspace JavaScript may require preserving many IDs and data attributes.
- Building fully independent prototype JavaScript may create duplicate behavior.
- Production replacement before review can break existing create flow.
- Payment modal behavior may need mobile-specific treatment, but backend contract must remain unchanged.
- Service + store-stock package auto split is the highest-risk UI path because it combines package total, product lookup, qty, and auto split semantics.

## Decision

Start with a separate UI lab.

Do not replace the production create transaction page until one variant is selected and documented.

## Next Active Step

Patch UI lab shell only:

- add controller
- add route
- add main lab view
- add style partial
- add variant tabs partial
- add variant 01 only as first render proof

Do not add all 10 full variants in the same active step.

---

## ADDENDUM - UI-Only Preview Scope Correction

### FACT

The UI lab is not a backend contract implementation.

The UI lab is only a mobile web interface exploration.

The source create transaction page is used only to extract:

- visible form sections
- form content meaning
- cashier workflow order
- UI pain points
- core labels and concepts

### DECISION

Do not bind UI variants to Laravel request fields, backend submit actions, workspace JavaScript hooks, validation rules, or transaction handlers during the UI exploration phase.

A route/controller may exist only as a preview entry point.

The UI itself should be treated as independent HTML/CSS Blade markup.

### SCOPE-IN

- Blade UI preview
- mobile-first layout
- static HTML form mockup
- optional small JavaScript only for visual interaction
- 10 UI variants created one by one

### SCOPE-OUT

- backend submit
- request payload contract
- existing workspace JavaScript integration
- product lookup integration
- payment handler integration
- create transaction production replacement
- edit/refund/revision flows

### Preview Rule

The preview page must not link back into the production create transaction workflow unless explicitly needed for comparison.

### Next Active Step

Continue with Variant 01 as a pure mobile UI preview.

After Variant 01 is reviewed, continue to Variant 02.

Do not create all 10 variants at once.

---

## ADDENDUM - Variant 02 UI Preview

### FACT

Variant 01 exists as a pure mobile single-scroll UI preview.

### DECISION

Variant 02 will explore a step-card layout.

The UI remains independent from backend submit, Laravel request names, workspace JavaScript hooks, and product lookup behavior.

### Variant 02 Goal

Use large mobile cards to split the create transaction flow into:

1. Informasi Nota
2. Rincian Nota
3. Pembayaran

This variant tests whether the create transaction page feels clearer when grouped into visible workflow steps instead of one continuous form.

### Scope

- Add `variant-02.blade.php`
- Add small step-card CSS partial
- Include Variant 02 in the preview page after Variant 01

### Scope Out

- backend integration
- route changes
- controller changes
- production create page changes

### Next Active Step

Review Variant 02 in mobile preview, then continue to Variant 03 only after feedback.

---

## ADDENDUM - Variant 03 UI Preview

### FACT

Variant 01 exists as a pure mobile single-scroll UI preview.
Variant 02 exists as a pure mobile step-card UI preview.

### DECISION

Variant 03 will explore compact cashier fast entry.

The UI remains independent from backend submit, Laravel request names, workspace JavaScript hooks, and product lookup behavior.

### Variant 03 Goal

Test whether create transaction can feel faster for repeated cashier use by reducing helper text and emphasizing:

- quick customer input
- quick transaction type selection
- compact active item entry
- visible total
- large primary action

### Scope

- Add `variant-03.blade.php`
- Add compact cashier CSS partial
- Include Variant 03 in the preview page after Variant 02

### Scope Out

- backend integration
- route changes
- controller changes
- production create page changes

### Next Active Step

Review Variant 03 in mobile preview, then continue to Variant 04 only after feedback.

---

## ADDENDUM - Pure Google Form Visual Direction

### FACT

The first UI preview still used the app layout shell, which means it inherited the application/Mazer visual frame.

### DECISION

The mobile UI lab must render as a standalone preview page.

Do not extend `layouts.app` for the preview page.

The preview route may remain as the access door, but the view must behave like an independent mobile web mockup.

### Visual Direction

The UI should resemble Google Forms:

- soft purple page background
- centered form column
- white form cards
- purple top accent
- simple title and description
- clean input underline style
- radio-like option rows
- large but minimal mobile spacing
- no admin dashboard chrome
- no Mazer card styling

### Scope

- Rewrite the preview shell as standalone HTML.
- Rewrite Variant 01 as a Google Form-like mobile UI.
- Keep backend integration out of scope.

### Next Active Step

Review Variant 01 as a pure Google Form-like preview before continuing to other variants.
