# 025 - Reflected javascript URL in product return link

## Status

Status: Strict Fixed

Strict-Fixed-Scope: local product create return-link URL allowlist for return_to rendered as href.

## Update Log

### Update 2 - 2026-05-10 strict local verification

Status changed from Patched, with verification gap to Status: Strict Fixed for the local #025 product create return-link URL allowlist scope.

Current source/test reality:

- The previous document status was stale because current local source still passed query-string return_to through trim-only normalization in CreateProductPageController.
- RED proof reproduced an active javascript: URL in the rendered product create return link.
- The production boundary was patched at:
  - app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php
- The final controller uses:
  - resolveReturnTo($request->query('return_to'))
- The allowlist accepts only the supplier invoice create route in absolute or relative form:
  - admin.procurement.supplier-invoices.create
- The regression test file is:
  - tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php

### Strict Closure Packet

Status: Strict Fixed

Strict-Fixed-Scope: local reflected click-triggered XSS protection for admin product create return link where return_to is rendered into href.

#### Root Cause

CreateProductPageController accepted return_to from the query string and passed it to the product create view after trim-only normalization.

The Blade view rendered returnTo into an href attribute. Blade HTML escaping prevents quote or markup injection, but it does not make URL schemes safe. A javascript: URL remains executable when clicked.

#### Source Reality

- app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php: now resolves return_to through resolveReturnTo().
- app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php: resolveReturnTo() allows only the supplier invoice create route, absolute or relative.
- resources/views/admin/products/create.blade.php: still renders returnTo as href, but returnTo is now server-side allowlisted before reaching the view.
- tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php: contains unsafe and allowed return URL coverage.

#### UI Blade Impact

Impact: yes.

View path:

- resources/views/admin/products/create.blade.php

UI invariant:

- unsafe return_to values must not render as active href values.
- invalid return_to must fall back to the normal product index cancel link.
- allowed procurement return URL must still render for the intended procurement workflow.

#### Server Boundary

This issue is an output URL-context vulnerability, not a product mutation authorization boundary.

- Direct GET: admin product create route was used to render the vulnerable return link.
- Direct mutation request: product store redirect already has its own return URL allowlist and was not the production patch target in this closure.
- No mutation proof: not applicable for this reflected href rendering closure.
- Admin boundary: admin product create rendering path covered by feature tests.
- Kasir boundary: existing create page test confirms kasir is redirected back to cashier dashboard and cannot access the admin page.

#### ADR / Rule Compatibility

- docs/02_architecture/adr/0020_public_surface_output_storage_attachment_security.md: forbids javascript:, data:, external full URL, protocol-relative URL, and raw unvalidated URL from request.
- docs/02_architecture/adr/0020_public_surface_output_storage_attachment_security.md: invalid return URL must fall back to a safe default route.
- Conflict: none found for this #025 local closure scope.

#### RED Proof

Command:

    php artisan test tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php --filter=javascript_return_url

Observed failure before production patch:

- FAIL Tests\Feature\ProductCatalog\ProductCreatePageFeatureTest
- 1 failed / 3 assertions
- failure at tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php:125
- rendered response still contained:
  - href="javascript:alert(25)"

#### GREEN Proof

Command:

    php artisan test tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php --filter=return_url

Observed pass after controller allowlist patch and extended URL matrix:

- PASS Tests\Feature\ProductCatalog\ProductCreatePageFeatureTest
- 4 passed / 24 assertions

Covered cases:

- javascript: URL rejected and falls back to admin.products.index
- data: URL rejected and falls back to admin.products.index
- external https URL rejected and falls back to admin.products.index
- protocol-relative URL rejected and falls back to admin.products.index
- allowed absolute procurement return URL renders
- allowed relative procurement return URL renders

#### Focused Blast-Radius Proof

Command:

    php artisan test \
      tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php \
      tests/Feature/Admin/Product/ProductMasterValidationFeedbackTest.php \
      tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php

Observed pass:

- PASS
- 14 passed / 73 assertions

#### Negative Search

Local source intake found the affected rendered href at:

- resources/views/admin/products/create.blade.php
  - href uses returnTo

Classification:

- product create href remains the #025 sink, but returnTo is now server-side allowlisted before rendering.
- StoreProductController already had a separate return_to allowlist before this closure.
- broader project-wide return URL search remains outside this #025 local closure scope.

#### Remaining Gaps

- Browser/manual QA was not run.
- Full global make verify was not run for this #025 closure step.
- Full project-wide URL-context audit remains broader final verification scope.
- Commit/push proof for this docs/test update is not claimed here.

#### Strict Closure Decision

#025 is locally strict-fixed for the tested admin product create return-link sink because:

- source behavior matches the root-cause fix
- RED proof reproduced active javascript: href rendering
- targeted GREEN proof passed for unsafe and allowed return URLs
- focused product/procurement blast-radius proof passed
- UI/server boundary is correctly scoped as URL-context output safety
- ADR-0020 compatibility was checked
- remaining gaps are explicit and outside this local strict closure scope

## Keparahan

High.

## Ringkasan

Halaman `admin.products.create` memiliki reflected, click-triggered XSS melalui parameter `return_to`.

`CreateProductPageController` menerima `return_to` dari query string, lalu sebelumnya hanya melakukan trim/type normalization sebelum mengirim nilainya ke view sebagai `returnTo`.

View `resources/views/admin/products/create.blade.php` merender nilai tersebut ke atribut:

`href="{{ $returnTo }}"`

Blade HTML escaping mencegah quote/markup injection, tetapi tidak memblokir URL scheme berbahaya seperti `javascript:`.

Akibatnya, URL seperti:

`/admin/products/create?return_to=javascript:...&return_label=Kembali`

dapat membuat tombol kembali yang menjalankan JavaScript saat diklik admin.

## Jalur rentan

Attacker membuat URL product create
-> admin yang sudah login membuka URL tersebut
-> `return_to` dibaca dari query string
-> controller hanya trim nilai tersebut
-> nilai dikirim ke view sebagai `returnTo`
-> view merender `href`
-> admin klik tombol kembali
-> `javascript:` berjalan di origin aplikasi
-> script memakai sesi admin dan token/form same-origin untuk membaca data atau mengirim request admin

## Root cause

Nilai URL dari query string dipakai langsung sebagai `href` tanpa allowlist route atau validasi scheme.

Escaping HTML saja tidak cukup untuk konteks URL karena `javascript:` tetap valid sebagai nilai `href`.

## Patch summary

`app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php` diubah agar `__invoke()` memakai:

`resolveReturnTo($request->query('return_to'))`

bukan trim-only normalization.

Method `resolveReturnTo()` menolak nilai kosong atau tidak dipercaya, termasuk payload `javascript:`.

Nilai yang diterima hanya return URL yang cocok dengan route:

`admin.procurement.supplier-invoices.create`

dalam bentuk absolute atau relative.

## Verification

Reported successful checks:

- `php -l app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php`
- `git commit -m "Harden product create return link allowlist"`

## Verification gap

Belum ada feature/browser test yang membuktikan payload `javascript:` tidak lagi dirender sebagai href aktif.

Future verification:

- render `/admin/products/create?return_to=javascript:alert(1)&return_label=Kembali`
- pastikan tombol kembali tidak memakai `javascript:` sebagai `href`
- render return URL yang valid dari `admin.procurement.supplier-invoices.create`
- pastikan tombol kembali tetap muncul untuk route yang diizinkan

## Relations

Related to #024.

#024 covers reflected XSS through unsafe JSON config in `admin.expenses.create`.

#025 covers reflected click-triggered XSS through unsafe `href` rendering in `admin.products.create`.

Related to #007 as part of the broader XSS/output-context cluster, but #007 is stored XSS through workspace JSON config while #025 is reflected XSS through a return link URL.
