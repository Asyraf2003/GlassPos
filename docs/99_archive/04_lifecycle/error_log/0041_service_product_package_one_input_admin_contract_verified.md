# 0041 Service Product Package One Input Admin Contract Verified

## Status

Selesai dan verified.

Final verification:

- PHPStan OK
- make verify GREEN
- Pest: 1364 passed, 8089 assertions
- Duration: 101.67s

## Scope

Service x Product package flow dirapikan untuk model paket bengkel.

Target akhir:

- Admin membuat template paket dari 3 produk maksimal dan 1 jasa.
- Produk 1 wajib.
- Produk 2 opsional.
- Produk 3 opsional.
- Jasa wajib.
- Admin tidak mengisi harga jasa template.
- Admin tidak mengisi total paket.
- Admin tidak mengisi urutan.
- Kasir memilih paket lewat 1 input pencarian.
- Payload lama tetap diisi otomatis untuk backend workspace.

## Admin Contract

Form admin service product template sekarang visible hanya:

- Produk 1
- Produk 2 opsional
- Produk 3 opsional
- Jasa

Field berikut tidak lagi menjadi input admin:

- Harga Jasa Template
- Total
- Urutan
- Qty Produk 2
- Qty Produk 3

Backend tetap menyimpan field lama yang masih dipakai sistem:

- default_service_price_rupiah
- default_package_total_rupiah
- sort_order

Namun nilainya sekarang derived:

- default_service_price_rupiah dari service_catalog_items.default_price_rupiah
- default_package_total_rupiah dari total harga produk + harga jasa
- sort_order default 0
- qty tiap product line default 1

## Duplicate Rule

Produk yang sama boleh dipakai untuk jasa berbeda.

Contoh valid:

- Produk A + Service A
- Produk A + Service B

Yang ditolak:

- Produk duplikat dalam satu paket.
- Paket aktif dengan kombinasi Produk 1 + Jasa yang sama.

## Cashier Contract

Kasir Service x Product memakai 1 input package search.

Setelah paket dipilih, JavaScript mengisi hidden payload lama:

- entry_mode = service
- part_source = store_stock
- pricing_mode = package_auto_split
- requires_service_product_template = 1
- service.name
- service.price_rupiah
- product_lines[0..2].product_id
- product_lines[0..2].qty
- product_lines[0..2].unit_price_rupiah

## Files Touched

Main files:

- resources/views/admin/service_product_templates/partials/form.blade.php
- app/Adapters/In/Http/Controllers/Admin/ServiceProductTemplate/Concerns/ValidatesServiceProductTemplateForm.php
- app/Adapters/In/Http/Controllers/Admin/ServiceProductTemplate/StoreServiceProductTemplateController.php
- app/Adapters/In/Http/Controllers/Admin/ServiceProductTemplate/UpdateServiceProductTemplateController.php
- app/Adapters/In/Http/Controllers/Admin/ServiceProductTemplate/ReactivateServiceProductTemplateController.php
- app/Application/ServiceProductTemplate/Services/ServiceProductTemplateAdminLineInput.php
- tests/Feature/ServiceProductTemplate/AdminServiceProductTemplateManagementFeatureTest.php

Previously added package lookup and cashier one-input files remain part of this scope.

## Proof

Focused admin test:

```text
PASS Tests\Feature\ServiceProductTemplate\AdminServiceProductTemplateManagementFeatureTest
Tests: 4 passed (58 assertions)
```

Full verify:

```text
Tests: 1364 passed (8089 assertions)
Duration: 101.67s
```

## Handoff

Do not continue patching this scope unless a new bug is reported.

Next safe work, if needed:

- Manual browser check for admin create/edit template.
- Manual browser check for cashier one-input package selection.
- Manual edit workspace check for package line hydration.
- Only patch if manual behavior differs from the verified contract.

## Notes

No git add, commit, push, stash, reset, or checkout was performed by assistant.
