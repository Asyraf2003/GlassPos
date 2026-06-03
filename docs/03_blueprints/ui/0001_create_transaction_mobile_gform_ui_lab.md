# 0001 - Create Transaction Mobile Google Form UI Lab Blueprint

## Metadata

- Date: 2026-06-03
- Slice / topic: Create transaction mobile UI experiment
- Status: Blueprint only
- Progress: Design blueprint recorded, implementation not started

## Target Work Page

Create transaction workspace page:

- `resources/views/cashier/notes/workspace/create.blade.php`
- route: `cashier.notes.workspace.create`
- store action: `notes.workspace.store`

## Problem Being Solved

The current create transaction workspace is functionally rich, but the layout is still desktop-oriented. The target usage is web running on mobile, so the create transaction UI needs a mobile-first experiment phase before any production replacement.

The goal is to create 10 UI variants inspired by a simple Google Form style while preserving the original create transaction contracts and actions.

## References Used

- `docs/04_lifecycle/handoff/README.md`
- `docs/01_standards/0005_handoff_template.md`
- `docs/01_standards/core/0010_scope_and_facts.md`
- `docs/01_standards/core/0011_blueprint_first.md`
- `docs/01_standards/core/0012_step_by_step_execution.md`
- `docs/01_standards/core/0013_proof_and_progress.md`
- `docs/01_standards/workflow/0020_response_structure.md`
- `docs/01_standards/workflow/0021_active_step_policy.md`
- `docs/01_standards/output/0033_terminal_command_delivery.md`
- `resources/views/cashier/notes/workspace/create.blade.php`
- `resources/views/cashier/notes/workspace/partials/rincian-card.blade.php`
- `resources/views/cashier/notes/workspace/partials/info-card.blade.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal.blade.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal-left.blade.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal-right.blade.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal-cash.blade.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal-footer.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/product.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service-external.blade.php`
- `routes/web/note.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspacePageController.php`
- `app/Adapters/In/Http/Controllers/Note/StoreTransactionWorkspaceController.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php`

## Locked Facts

- The production create workspace view is `resources/views/cashier/notes/workspace/create.blade.php`.
- The production create form uses `id="cashier-note-workspace-form"`.
- The production create form default action is `route('notes.workspace.store')`.
- The production create form includes `rincian-card`, `info-card`, `payment-modal`, and `refund-modal`.
- The production create page loads existing workspace JavaScript modules:
  - `admin-money-input.js`
  - `rows.js`
  - `search.js`
  - `summary.js`
  - `payment-flow.js`
  - `draft.js`
  - `boot.js`
- The create store route is `POST /notes/workspace/store` handled by `StoreTransactionWorkspaceController`.
- The create store controller sends validated payload to `CreateTransactionWorkspaceHandler`.
- The backend validation contract includes `note`, `items`, and `inline_payment` payload groups.
- No implementation proof exists yet for the mobile UI lab.

## SCOPE-IN

- Create a UI lab / prototype page for create transaction mobile layout exploration.
- Preserve create transaction field names, action intent, and JavaScript hook contracts where required.
- Prepare 10 mobile-first UI variants inspired by simple Google Form style.
- Keep the prototype isolated from the production create workspace view until one candidate is selected.

## SCOPE-OUT

- Do not replace production `create.blade.php` in the first implementation step.
- Do not change `StoreTransactionWorkspaceController`.
- Do not change `CreateTransactionWorkspaceHandler`.
- Do not change request validation rules.
- Do not change database schema.
- Do not change edit/revision/refund lifecycle.
- Do not claim mobile UX is accepted without browser/mobile proof.

## Required Contract To Preserve

### Note fields

- `note[customer_name]`
- `note[customer_phone]`
- `note[transaction_date]`
- `note[operational_note]`

### Item fields

- `items[n][entry_mode]`
- `items[n][part_source]`
- `items[n][description]`
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

### Inline payment fields

- `inline_payment[decision]`
- `inline_payment[payment_method]`
- `inline_payment[paid_at]`
- `inline_payment[amount_paid_rupiah]`
- `inline_payment[amount_received_rupiah]`
- `inline_payment[notes]`

### Important UI / JS hooks

- `cashier-note-workspace-form`
- `cashier-note-workspace-config`
- `workspace-line-items`
- `workspace-empty-state`
- `workspace-add-button`
- `workspace-item-type-menu`
- `data-add-item-type`
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
- `data-stock-text`
- `data-min-price-text`
- `data-stock-error`
- `data-min-price-warning`
- `data-money-input-group`
- `data-money-raw`
- `data-money-display`
- `data-package-total-input`
- `workspace-open-payment-dialog`
- `workspace-note-total-text`
- `workspace-payment-modal`
- `workspace-payment-line-summary`
- `workspace-modal-total-text`
- `workspace-payment-choice-full`
- `workspace-payment-choice-partial`
- `workspace-payment-choice-skip`
- `workspace-payment-submit-skip`
- `workspace-payment-submit-transfer`
- `workspace-payment-open-cash`
- `workspace-payment-submit-cash`

## Locked Decisions

- The UI lab must not copy the full production desktop layout as-is.
- The UI lab must preserve the form/action contract rather than only drawing static mockups.
- The first implementation should create an isolated prototype route/page.
- Production create redesign may happen only after manual mobile review selects one variant or a merged variant.

## Recommended Implementation Shape

### Route

Add a separate cashier route:

```php
Route::get('/workspace/mobile-ui-lab', CreateTransactionWorkspaceMobileUiLabPageController::class)
    ->name('workspace.mobile-ui-lab');
```

### Controller

Create:

```txt
app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspaceMobileUiLabPageController.php
```

The controller should reuse the same page data contract as `CreateTransactionWorkspacePageController` where practical, but may force the page title and render the lab view:

```txt
cashier.notes.workspace.mobile-ui-lab
```

### Views

Create:

```txt
resources/views/cashier/notes/workspace/mobile-ui-lab.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/styles.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-tabs.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/form-shell.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-01.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-02.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-03.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-04.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-05.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-06.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-07.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-08.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-09.blade.php
resources/views/cashier/notes/workspace/mobile-ui-lab/partials/variant-10.blade.php
```

## Variant Plan

### Variant 01 - Google Form Single Scroll

One long vertical mobile form. Use stacked sections with clear titles and helper text.

### Variant 02 - Google Form Step Cards

Separate large cards for `Informasi Nota`, `Rincian`, and `Pembayaran`.

### Variant 03 - Compact Cashier Fast Entry

Reduce helper text, keep controls large, prioritize fast input on mobile.

### Variant 04 - Sticky Total Bottom Bar

Keep total and `Proses Nota` visible in a sticky bottom bar.

### Variant 05 - Accordion Sections

Use collapsible sections for info, item details, and payment review.

### Variant 06 - Service Store Stock First

Optimize the complex service + store-stock + package auto split flow.

### Variant 07 - Card Per Item

Render each line item as its own mobile card, similar to a Google Form answer block.

### Variant 08 - Review Before Payment

Show a review page/sheet before choosing payment mode.

### Variant 09 - One-Hand Mode

Place major actions near the lower screen area and keep all fields full width.

### Variant 10 - Dense Mobile Power User

Mobile-first but denser, for faster cashier operation once the user is familiar.

## Workflow

1. Create UI lab route, controller, shell view, and first variant only.
2. Verify route and view render.
3. Add variants 02-10 as isolated Blade partials.
4. Add a lightweight variant switcher.
5. Manually review on mobile width.
6. Select one variant or combine winning parts.
7. Only then prepare production create workspace replacement blueprint.

## Verification Plan

Minimum verification after implementation:

```bash
php artisan route:list --name=workspace.mobile-ui-lab
php artisan view:clear
```

If route/view files are patched locally, also run the repo standard verification gate when practical:

```bash
make verify
```

Manual browser proof required before production adoption:

- Open create mobile UI lab route.
- Test width close to 360px, 390px, and 430px.
- Check all 10 variants render.
- Check primary action visibility.
- Check form field names remain compatible.
- Check product search and payment actions if wired to existing JS.

## Risks / Follow-up Notes

- Reusing the existing JS hooks may constrain markup freedom.
- Changing IDs or data attributes can silently break product search, row summary, draft hydration, and payment flow.
- A purely static mockup is not enough because the create page relies on dynamic row and payment behavior.
- The most dangerous path is replacing production `create.blade.php` before reviewing the UI lab on a real mobile viewport.

## GAP

- No local command output is attached to this blueprint.
- No browser/mobile screenshot proof exists yet.
- No prototype route or Blade file exists yet at the time this blueprint is created.
- The selected production variant is not decided yet.

## Next Step

Patch batch 1 only:

- Add `CreateTransactionWorkspaceMobileUiLabPageController`.
- Add `cashier.notes.workspace.mobile-ui-lab` route.
- Add `mobile-ui-lab.blade.php` shell.
- Add mobile lab styles partial.
- Add variant tabs partial.
- Add Variant 01 only as the first render proof.

Stop after batch 1 and request local proof before adding variants 02-10.
