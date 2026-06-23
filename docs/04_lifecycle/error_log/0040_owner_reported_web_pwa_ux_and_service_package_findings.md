# 0040 - Owner-reported web/PWA UX and service package findings

Status:
OPEN / DOCS-ONLY / NO PATCH YET

Date:
2026-06-23

Source:
- Owner report in current session.
- Local CLI probe: `/tmp/hyperpos_owner_bug_probe_20260623.txt`.
- GitHub connector read-only source-map from `Asyraf2003/hyperpos`.
- No source patch was applied in this step.

## Summary

Owner reported four web/PWA usability and contract issues:

1. Supplier payment proof media can be opened from mobile, but the opened media view has no app-level back button.
2. Some JavaScript search/filter inputs feel broken on typing: spaces may not enter, or fast typing feels blocked/laggy.
3. Service package UI/contract should be simplified:
   - product/package name area should allow 3 product forms,
   - first product is required,
   - second and third products are optional,
   - service component is single,
   - service/profit split should follow a strict 20% / 80% policy,
   - 20% is counted as service fee,
   - 80% is counted as package profit,
   - leftover UI concepts such as ordering/extra total fields should be removed if not needed by the new contract.
4. Cashier workspace search should be clearer and more informative:
   - search can be via service,
   - search can be via product,
   - each result should be explicit enough for cashier decisions,
   - if no data exists, the result area may simply be empty.

## Runtime Boundary

Active runtime boundary remains Laravel web/PWA:

- Blade views under `resources/views`.
- Browser JavaScript under `public/assets/static/js`.
- Web routes under `routes/web/*.php`.
- Session/CSRF/web middleware.
- Form submit/fetch request flow.
- No Mobile API work.
- No Kotlin Android work.

## Finding 1 - Payment proof media opens as raw attachment without app back affordance

Observed source candidates:

- `routes/web/admin_procurement.php`
  - `admin.procurement.supplier-invoices.payment-proof.store`
  - `admin.procurement.supplier-invoices.payment-proofs.show`
  - `admin.procurement.supplier-payment-proof-attachments.show`
- `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php`
  - page has `@section('back_url', route('admin.procurement.supplier-invoices.show', ...))`
  - attachment actions use direct attachment route
  - `Lihat PDF`, `Lihat Gambar`, and `Lihat Berkas` open direct binary attachment links with `target="_blank"`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ServeSupplierPaymentProofAttachmentController.php`
  - returns binary response through `SupplierPaymentProofAttachmentResponseFactory`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/SupplierPaymentProofAttachmentResponseFactory.php`
  - serves safe image/PDF MIME types inline
  - uses safe `Content-Disposition`

Current UX issue:

Opening media directly gives the browser raw media/PDF experience, not an app shell page. On mobile this can leave the user without a visible in-app back control.

Patch direction candidates for next session:

- Option A: remove `target="_blank"` so browser back returns naturally.
- Option B: add an HTML wrapper preview route/page with app shell and back button, then embed image/PDF inside it.
- Option C: keep raw route only for download/inline asset delivery, but make UI preview open a Blade page.

Security note:

Do not weaken existing MIME detection, `nosniff`, safe filename, or storage path guard behavior.

## Finding 2 - JS search/filter input may block spaces or fast typing

Observed source candidates:

- Cashier note workspace product search:
  - `public/assets/static/js/pages/cashier-note-workspace/search.js`
  - `[data-product-search]`
  - debounce/fetch behavior
- Cashier note workspace service search:
  - `public/assets/static/js/pages/cashier-note-workspace/service-catalog.js`
  - `[data-service-name]`
  - keydown handler for ArrowDown, ArrowUp, and Enter
- Filter/search surfaces:
  - `resources/views/admin/products/partials/filter_drawer.blade.php`
  - `resources/views/admin/procurement/supplier_invoices/partials/filter_drawer.blade.php`
  - `resources/views/admin/notes/partials/filter-drawer.blade.php`
  - `resources/views/cashier/notes/partials/filter-drawer.blade.php`
  - dashboard/report period filters
  - shared searchable create select

Current evidence:

The local probe found many filter/search candidates but did not yet isolate the exact listener that blocks space or typing speed.

Patch direction candidates for next session:

- Source-map all `keydown`, `keypress`, `input`, `beforeinput`, and `preventDefault()` handlers outside vendor files.
- Separate normal text input behavior from keyboard navigation behavior.
- Guard `preventDefault()` so it only runs for actual handled navigation keys.
- Avoid expensive re-render/fetch on every keystroke without debounce.
- Check browser composition events and mobile keyboard behavior.

Required proof next session:

- Reproduce with one exact page and one exact input.
- Add characterization test if possible for rendered script contract.
- Manual browser proof may be required because this is UI-interaction behavior.

## Finding 3 - Service package owner contract changed

Existing current behavior candidates:

- `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
  - current UI uses product template search
  - supports added sparepart lines
  - exposes total package input
  - service name is auto-filled from template
- `public/assets/static/js/pages/cashier-note-workspace/search.js`
  - primary product can apply service product template
- `public/assets/static/js/pages/cashier-note-workspace/service-catalog.js`
  - applies service/product template data
  - syncs package total
- `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php`
  - template branch calculates service extra using `intdiv($extra, 5)`
  - current meaning: 20% of extra goes to service fee; remaining extra becomes package profit
  - non-template branch calculates service price as package total minus sparepart total and sets package profit to zero
- related tests:
  - `CreateTransactionWorkspaceLineTypeCharacterizationTest`
  - `CreateTransactionWorkspaceServiceStoreStockFeatureTest`
  - `CreateTransactionWorkspaceTemplateContractFeatureTest`
  - `CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest`
  - `CashierWorkspaceServiceProductTemplateAutofillContractFeatureTest`

Owner desired direction:

- Package product area should be limited to 3 product forms.
- Product 1 required.
- Product 2 optional.
- Product 3 optional.
- Service component remains single.
- Split policy should be strict 20% service fee and 80% package profit.
- UI fields unrelated to the simplified contract should be removed.

Important unresolved accounting question:

Before patching financial logic, next session must prove whether 20% / 80% applies to:

1. package total after sparepart/product cost,
2. service-only margin,
3. extra above template minimum,
4. or another owner-defined amount.

Do not silently change historical package/reporting meaning without characterization and tests.

Out of scope:

- Operational Profit formula change.
- Refund policy change.
- Mobile API.
- Migration unless later proven required.

## Finding 4 - Cashier service/product lookup should be clearer and empty when no result

Observed source candidates:

- `routes/web/note.php`
  - `cashier.notes.products.lookup`
  - `cashier.notes.services.lookup`
  - `admin.notes.products.lookup`
  - `admin.notes.services.lookup`
- `app/Adapters/In/Http/Controllers/Cashier/Note/ProductLookupController.php`
  - returns `rows: []` for query length under 2
  - supports `context=service_product`
  - filters products without active service template in service-product context
- `app/Adapters/In/Http/Controllers/Cashier/Note/ServiceCatalogLookupController.php`
  - returns service catalog rows
- `public/assets/static/js/pages/cashier-note-workspace/search.js`
  - renders product result buttons
  - hides result area when rows are empty
- `public/assets/static/js/pages/cashier-note-workspace/service-catalog.js`
  - renders service result buttons
  - toggles result visibility based on item count

Owner desired direction:

- Cashier can search by service.
- Cashier can search by product.
- Results should be informative enough to distinguish entries.
- Empty result can remain empty instead of noisy error text.

Possible next-session patch direction:

- Improve product result label metadata.
- Improve service result label metadata.
- Preserve empty result behavior unless owner wants explicit empty-state text.
- Ensure product and service search do not fight each other in service package mode.

## Scope Lock

This document opens a new docs-only issue log.

This document does not:

- patch source files,
- change tests,
- change routes,
- change migrations,
- change supplier invoice payment proof security behavior,
- change Operational Profit formula,
- change refund policy,
- revive Mobile API,
- perform git add/commit/push/stash/reset/checkout.

## Next Session Handoff

Recommended next-session order:

1. Read this error log first.
2. Re-run targeted source-map only for these four issues.
3. Pick one fix slice only.
4. For payment proof media UX:
   - safest first candidate is preview-page/back-button UX without weakening binary attachment security.
5. For JS input bug:
   - isolate exact page/input before patch.
6. For service package contract:
   - require owner/accounting basis decision before changing money calculation.
7. For cashier lookup:
   - keep empty results empty unless owner explicitly wants an empty-state label.

## Verification Status

Docs-only file creation expected.

No tests run in this step.

