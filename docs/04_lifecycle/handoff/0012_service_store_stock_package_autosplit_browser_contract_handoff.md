# Handoff 0012 - Service Store Stock Package Autosplit Browser Contract

## FACT

- Slice ini memperbaiki create workspace flow untuk `service_store_stock` dengan `package_auto_split`.
- Original manual browser symptom:
  - `Harga servis wajib lebih dari 0 kecuali package service + sparepart.`
  - `Nominal pembayaran sebagian harus lebih kecil dari grand total nota.`
  - setelah UI guard awal, muncul `Qty sparepart toko wajib lebih dari 0.`
- Root cause backend:
  - `package_total_rupiah` dari browser form dikirim sebagai string, tetapi `StoreTransactionWorkspaceItemNormalizer::intOrNull()` hanya menerima PHP int.
  - `StoreTransactionWorkspaceGrandTotalCalculator` belum memakai `package_total_rupiah` untuk `package_auto_split`, sehingga partial payment dibandingkan terhadap total yang salah.
- Root cause UI:
  - `service-store-stock.blade.php` menaruh `data-product-line` hanya pada kolom product search.
  - Qty berada di kolom saudara, sehingga JS guard membaca qty sebagai 0.
- Final state:
  - Backend browser-form contract fixed.
  - Payment grand total autosplit fixed.
  - UI payment guard added.
  - Blade `data-product-line` scope repaired so product search and qty are in the same product-line scope.
  - Manual browser flow confirmed by owner as successful.

## REFERENCES

- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceItemNormalizer.php`
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceGrandTotalCalculator.php`
- `tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php`
- `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`
- `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
- `tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php`

## SCOPE-IN

- Fix browser-form request contract for 1-product `service_store_stock` package autosplit.
- Add characterization coverage for form-style string payload and partial payment.
- Add UI guard before payment modal/submit for invalid store-stock autosplit rows.
- Repair Blade scope so product search, hidden product id, hidden price, and qty are inside the same `data-product-line`.

## SCOPE-OUT

- Multi-product UI.
- Edit/revision flow.
- Refund flow.
- Full browser automation.
- Full `make verify`.
- Git commit/push.

## DECISION

`service_store_stock` package autosplit 1-product create flow is closed for this slice.

Final status:

`FIXED WITH BACKEND CONTRACT + UI GUARD + BLADE SCOPE REPAIR + MANUAL BROWSER PROOF`

## PROOF

### Static syntax proof

Command:

```bash
php -l resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php
php -l app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceItemNormalizer.php
php -l app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceGrandTotalCalculator.php

Proof:

No syntax errors detected in resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php
No syntax errors detected in app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceItemNormalizer.php
No syntax errors detected in app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceGrandTotalCalculator.php
Blade scope proof

Command:

rg -n 'row g-2 align-items-start" data-product-line|col-12 col-lg-4" data-product-line|data-qty-input|data-product-search|product_lines\]\[0\]\[qty\]' resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php

Proof:

18:        <div class="row g-2 align-items-start" data-product-line>
57:                        data-product-search
71:                        name="items[__INDEX__][product_lines][0][qty]"
74:                        data-qty-input

Meaning:

data-product-line is now on the parent row that contains product search and qty.
col-12 col-lg-4" data-product-line no longer appears.
UI guard static proof

Command:

rg -n "ensureWorkspaceReadyForPayment|serviceStoreStockAutosplitIssue|workspace-client-validation-error|bindWorkspaceSubmitGuard|Sparepart toko wajib dipilih" public/assets/static/js/pages/cashier-note-workspace/payment-flow.js

Proof:

176:    const alert = byId("workspace-client-validation-error");
197:    let alert = byId("workspace-client-validation-error");
201:      alert.id = "workspace-client-validation-error";
217:  const serviceStoreStockAutosplitIssue = (row) => {
247:          message: "Sparepart toko wajib dipilih dari hasil pencarian, bukan diketik manual.",
289:      const issue = serviceStoreStockAutosplitIssue(row);
298:  const ensureWorkspaceReadyForPayment = () => {
723:      if (!ensureWorkspaceReadyForPayment()) {
852:  const bindWorkspaceSubmitGuard = () => {
862:      if (ensureWorkspaceReadyForPayment()) {
872:  bindWorkspaceSubmitGuard();
Targeted package regression

Command:

php artisan test tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php --filter=package

Proof:

PASS  Tests\Feature\Note\CreateTransactionWorkspaceServiceStoreStockFeatureTest

Tests: 6 passed (73 assertions)
Duration: 6.24s
Inline payment lifecycle regression

Command:

php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php

Proof:

PASS  Tests\Feature\Note\CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest

Tests: 5 passed (92 assertions)
Duration: 6.22s
Manual browser proof

Owner confirmed:

ok berhasil

Meaning:

manual service_store_stock package autosplit flow passed after backend contract, UI guard, and Blade scope fixes.
GAP
Full make verify has not been run after this slice.
node --check public/assets/static/js/pages/cashier-note-workspace/payment-flow.js was not provided.
Multi-product UI remains pending.
Multi-product UI + inline payment characterization remains pending.
NEXT

Recommended next active step:

Run optional JS syntax check if Node is available:

node --check public/assets/static/js/pages/cashier-note-workspace/payment-flow.js

Then run broader verify when ready:

make verify
Do not start multi-product UI until a dedicated characterization test exists for:
2 product lines
part_source=store_stock
pricing_mode=package_auto_split
browser-form string payload
inline partial/full payment allocations
PROGRESS
Backend browser-form autosplit contract: 100%
UI guard + Blade scope repair: 100% for 1-product flow
Manual browser proof: passed by owner report
Multi-product UI: 0%
Full suite proof after this slice: pending

Estimated total create-edit-refund progress after this slice:

33 / 100

### Detail UI create-result visibility proof

Status: DONE for focused Detail UI visibility.

Implemented proof:
- Detail note payload now carries operational_note.
- Detail header renders Keterangan Nota when operational_note exists.
- Detail line rows render service store-stock package breakdown:
  - Paket total
  - Total sparepart
  - Sisa jasa
  - store-stock part rows with product display names
- Existing focused detail/payment regressions remain green.

Proof commands:

php -l app/Application/Note/Services/CurrentRevision/CurrentRevisionPackageBreakdownMapper.php
php -l app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailBaseRowMapper.php
php -l app/Application/Note/Services/NoteDetailPageDataBuilder.php
php -l resources/views/shared/notes/partials/header-summary.blade.php
php -l resources/views/cashier/notes/partials/note-row-refund-style.blade.php
php -l resources/views/cashier/notes/partials/note-row-package-breakdown.blade.php
php -l resources/views/cashier/notes/partials/note-rows-table.blade.php

wc -l \
  app/Application/Note/Services/CurrentRevision/CurrentRevisionPackageBreakdownMapper.php \
  app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailBaseRowMapper.php \
  app/Application/Note/Services/NoteDetailPageDataBuilder.php \
  resources/views/shared/notes/partials/header-summary.blade.php \
  resources/views/cashier/notes/partials/note-row-refund-style.blade.php \
  resources/views/cashier/notes/partials/note-row-package-breakdown.blade.php \
  resources/views/cashier/notes/partials/note-rows-table.blade.php

php artisan test tests/Feature/Note/NoteDetailOperationalPackageVisibilityFeatureTest.php

php artisan test \
  tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php \
  tests/Feature/Note/NoteDetailEditEntryFeatureTest.php \
  tests/Feature/Note/CashierDetailRenderedBillingRowsPaymentFeatureTest.php \
  tests/Feature/Note/NoteDetailOperationalPackageVisibilityFeatureTest.php

Proof output:
- Syntax checks: all target PHP/Blade files passed php -l.
- Line count:
  - 90 app/Application/Note/Services/CurrentRevision/CurrentRevisionPackageBreakdownMapper.php
  - 68 app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailBaseRowMapper.php
  - 86 app/Application/Note/Services/NoteDetailPageDataBuilder.php
  - 55 resources/views/shared/notes/partials/header-summary.blade.php
  - 20 resources/views/cashier/notes/partials/note-row-refund-style.blade.php
  - 31 resources/views/cashier/notes/partials/note-row-package-breakdown.blade.php
  - 84 resources/views/cashier/notes/partials/note-rows-table.blade.php
- Detail visibility test:
  - PASS Tests\Feature\Note\NoteDetailOperationalPackageVisibilityFeatureTest
  - Tests: 1 passed (11 assertions)
- Focused detail/payment regression:
  - PASS Tests\Feature\Note\CashierHybridNoteDetailFeatureTest
  - PASS Tests\Feature\Note\NoteDetailEditEntryFeatureTest
  - PASS Tests\Feature\Note\CashierDetailRenderedBillingRowsPaymentFeatureTest
  - PASS Tests\Feature\Note\NoteDetailOperationalPackageVisibilityFeatureTest
  - Tests: 7 passed (36 assertions)

Remaining gaps:
- CLOSED: Product display name snapshot gap is closed by `Historical product display name snapshot proof`; package breakdown now prefers revision payload snapshot with current catalog fallback for legacy payload.
- Date display still uses current ViewDateFormatter format d/m/Y; client-facing tgl bulan tahun display remains a separate UI formatting step.

### Detail UI Indonesian date display proof

Status: DONE for centralized client-facing date formatter.

Implemented proof:
- `ViewDateFormatter::display()` now renders Indonesian month-name date output.
- Date-only display target:
  - `01 Juni 2026`
- Date-time display target:
  - `01 Juni 2026 14:30`
- Legacy slash date input is converted:
  - `01/06/2026` -> `01 Juni 2026`
  - `01/06/2026 14:30` -> `01 Juni 2026 14:30`
- `ViewDateFormatter::range()` now inherits Indonesian month-name output.

Files changed:
- `app/Support/ViewDateFormatter.php`
- `tests/Unit/Support/ViewDateFormatterTest.php`

Proof commands:

```bash
php -l app/Support/ViewDateFormatter.php
php -l tests/Unit/Support/ViewDateFormatterTest.php

php artisan test tests/Unit/Support/ViewDateFormatterTest.php

php artisan test \
  tests/Feature/Note/NoteDetailOperationalPackageVisibilityFeatureTest.php \
  tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php \
  tests/Feature/Note/NoteDetailEditEntryFeatureTest.php \
  tests/Feature/Note/CashierDetailRenderedBillingRowsPaymentFeatureTest.php

rg -n "INDONESIAN_MONTHS|01 Juni 2026|d/m/Y|ViewDateFormatterTest|formatIndonesian|parseSlashDate" \
  app/Support/ViewDateFormatter.php \
  tests/Unit/Support/ViewDateFormatterTest.php

Proof output:

Syntax:
No syntax errors detected in app/Support/ViewDateFormatter.php
No syntax errors detected in tests/Unit/Support/ViewDateFormatterTest.php
Focused formatter unit test:
PASS Tests\Unit\Support\ViewDateFormatterTest
Tests: 7 passed (9 assertions)
Focused detail/payment regression:
PASS Tests\Feature\Note\NoteDetailOperationalPackageVisibilityFeatureTest
PASS Tests\Feature\Note\CashierHybridNoteDetailFeatureTest
PASS Tests\Feature\Note\NoteDetailEditEntryFeatureTest
PASS Tests\Feature\Note\CashierDetailRenderedBillingRowsPaymentFeatureTest
Tests: 7 passed (36 assertions)
Static grep:
INDONESIAN_MONTHS
formatIndonesian
parseSlashDate
ViewDateFormatterTest
01 Juni 2026

Remaining gaps:

Product display name in package breakdown is still read from current products table, not a historical product name snapshot in revision payload.
Full make verify has not been run after this formatter patch.
Browser/manual UI proof after formatter patch is not recorded.
Reporting/export impact from global ViewDateFormatter change has not been fully regressed.

### Historical product display name snapshot proof

Status: DONE for service store-stock package breakdown product display name snapshot.

Implemented proof:
- Revision line payload now writes `product_name_snapshot` for store-stock product lines when the product can be resolved.
- Package breakdown display now prefers historical snapshot names before current catalog fallback.
- Fallback order:
  - `product_name_snapshot`
  - `product_nama_barang_snapshot`
  - current `products.nama_barang`
  - `product_id`
- Legacy payload without snapshot remains supported through current catalog fallback.
- Existing detail package visibility regression remains green.
- Create workspace package autosplit integration remains green.
- Revision payload builder trusted snapshot regression remains green.

Files changed:
- `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php`
- `app/Application/Note/Services/CurrentRevision/CurrentRevisionPackageBreakdownMapper.php`
- `tests/Unit/Application/Note/Services/NoteRevisionLinePayloadMapperTest.php`
- `tests/Feature/Note/CurrentRevisionPackageBreakdownMapperFeatureTest.php`

Proof commands:

```bash
php -l app/Application/Note/Services/NoteRevisionLinePayloadMapper.php
php -l app/Application/Note/Services/CurrentRevision/CurrentRevisionPackageBreakdownMapper.php
php -l tests/Unit/Application/Note/Services/NoteRevisionLinePayloadMapperTest.php
php -l tests/Feature/Note/CurrentRevisionPackageBreakdownMapperFeatureTest.php

php artisan test \
  tests/Unit/Application/Note/Services/NoteRevisionLinePayloadMapperTest.php \
  tests/Feature/Note/CurrentRevisionPackageBreakdownMapperFeatureTest.php

php artisan test tests/Feature/Note/NoteDetailOperationalPackageVisibilityFeatureTest.php

php artisan test tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php --filter=package

php artisan test tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php

rg -n "product_name_snapshot|Nama Snapshot Lama|Filter Oli Lama|4 passed \(9 assertions\)|1 passed \(11 assertions\)" \
  app/Application/Note/Services/NoteRevisionLinePayloadMapper.php \
  app/Application/Note/Services/CurrentRevision/CurrentRevisionPackageBreakdownMapper.php \
  tests/Unit/Application/Note/Services/NoteRevisionLinePayloadMapperTest.php \
  tests/Feature/Note/CurrentRevisionPackageBreakdownMapperFeatureTest.php

Proof output:

Syntax:
No syntax errors detected in all 4 changed PHP files.
Focused snapshot tests:
PASS Tests\Unit\Application\Note\Services\NoteRevisionLinePayloadMapperTest
PASS Tests\Feature\Note\CurrentRevisionPackageBreakdownMapperFeatureTest
Tests: 4 passed (9 assertions)
Existing detail package regression:
PASS Tests\Feature\Note\NoteDetailOperationalPackageVisibilityFeatureTest
Tests: 1 passed (11 assertions)
Create workspace package autosplit integration:
PASS Tests\Feature\Note\CreateTransactionWorkspaceServiceStoreStockFeatureTest
Tests: 6 passed (73 assertions)
Revision payload builder integration:
PASS Tests\Unit\Application\Note\UseCases\CreateNoteRevisionPayloadNoteBuilderTest
Tests: 2 passed (4 assertions)
Static grep:
product_name_snapshot
product_nama_barang_snapshot
Nama Snapshot Lama
Filter Oli Lama

Remaining gaps after this proof:

Full make verify has not been run after date formatter and product snapshot patches.
Browser/manual UI proof after these patches is not recorded.
Reporting/export impact from global ViewDateFormatter change has not been fully regressed.
