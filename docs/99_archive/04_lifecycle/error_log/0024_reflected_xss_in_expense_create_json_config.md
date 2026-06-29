# 024 - Reflected XSS in expense create JSON config

## Status

Status: Strict Fixed

Strict-Fixed-Scope: local reflected XSS protection for admin expense create JSON config rendered from query-string category_id.

## Update Log

### Update 2 - 2026-05-10 strict local verification

Status changed from Patched, with verification gap to Status: Strict Fixed for the local #024 admin expense create JSON config scope.

Current source/test reality:

- The previous document status was stale because current local source still rendered expense create config JSON with raw Blade output and only:
  - JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
- RED proof reproduced reflected script-breakout from query-string category_id.
- The production sink was patched at:
  - resources/views/admin/expenses/create.blade.php
- The final sink now uses:
  - JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
- The regression test file is:
  - tests/Feature/Expense/CreateExpensePageFeatureTest.php

### Strict Closure Packet

Status: Strict Fixed

Strict-Fixed-Scope: local reflected XSS protection for admin expense create JSON config where selectedCategoryId is derived from query-string category_id and rendered inside script type application/json.

#### Root Cause

CreateExpensePageController accepted category_id from the query string and passed it to selectedCategoryId.

The admin expense create Blade view rendered selectedCategoryId inside a JSON blob embedded in an HTML script tag using raw Blade output and json_encode with JSON_UNESCAPED_SLASHES. That allowed a payload containing closing script text to remain literal in the HTML response, letting the browser HTML parser terminate the JSON script block and execute attacker-controlled JavaScript.

#### Source Reality

- app/Adapters/In/Http/Controllers/Admin/Expense/CreateExpensePageController.php: reads category_id from the query string and passes selectedCategoryId to the view.
- resources/views/admin/expenses/create.blade.php: renders expense-create-config with JSON_HEX script-safe flags.
- tests/Feature/Expense/CreateExpensePageFeatureTest.php: contains regression coverage for script-breaking category_id payload.

#### UI Blade Impact

Impact: yes.

View path:

- resources/views/admin/expenses/create.blade.php

UI invariant:

- selectedCategoryId may be reflected into the JSON config, but it must not appear as literal script-breaking HTML.
- the response must not contain literal script-breakout text from attacker-controlled query input.
- the escaped form must remain present as JSON-safe text.

#### Server Boundary

This issue is an output-context/rendering vulnerability, not a mutation authorization boundary.

- Direct GET: admin expense create route was used to render the vulnerable response.
- Direct mutation request: not applicable for #024 closure scope.
- No mutation proof: not applicable for this XSS rendering closure.
- Admin boundary: admin create expense page rendering path covered by feature tests.
- Kasir boundary: existing create page test confirms kasir is redirected back to cashier dashboard and cannot access the admin page.

#### ADR / Rule Compatibility

- docs/02_architecture/adr/0020_public_surface_output_storage_attachment_security.md: requires safe JavaScript config encoding, context-aware output, no raw user-controlled HTML, and no final fixed claim from patch existence alone.
- Conflict: none found for this #024 local closure scope.

#### RED Proof

Command:

    php artisan test tests/Feature/Expense/CreateExpensePageFeatureTest.php --filter=script_breaking

Observed failure before production patch:

- FAIL Tests\Feature\Expense\CreateExpensePageFeatureTest
- 1 failed / 3 assertions
- failure at tests/Feature/Expense/CreateExpensePageFeatureTest.php:73
- rendered response still contained:
  - </script><script>alert(24)</script>

#### GREEN Proof

Command:

    php artisan test tests/Feature/Expense/CreateExpensePageFeatureTest.php --filter=script_breaking

Observed pass after Blade JSON_HEX patch:

- PASS Tests\Feature\Expense\CreateExpensePageFeatureTest
- 1 passed / 6 assertions

#### Focused Blast-Radius Proof

Command:

    php artisan test \
      tests/Feature/Expense/CreateExpensePageFeatureTest.php \
      tests/Feature/Expense/StoreExpenseHttpFeatureTest.php \
      tests/Feature/Expense/CreateExpenseCategoryPageFeatureTest.php \
      tests/Feature/Expense/StoreExpenseCategoryHttpFeatureTest.php \
      tests/Feature/Expense/ExpenseIndexPageFeatureTest.php \
      tests/Feature/Expense/ExpenseCategoryIndexPageFeatureTest.php

Observed pass:

- PASS
- 15 passed / 82 assertions

#### Negative Search

Local negative search for the expense slice found:

- resources/views/admin/expenses/create.blade.php:
  - expense-create-config still uses raw Blade JSON output, but now with JSON_HEX script-safe flags.
- resources/views/admin/expenses/index.blade.php:
  - expense table config uses @json.
- resources/views/admin/expenses/categories/index.blade.php:
  - expense category config uses @json.

Classification:

- admin expense create raw JSON hit is the #024 sink and is now script-safe through JSON_HEX flags.
- other checked expense configs use framework-safe @json output.
- broader project-wide raw output findings remain outside this #024 local closure scope.

#### Remaining Gaps

- Browser/manual QA was not run.
- Full global make verify was not run for this #024 closure step.
- Full project-wide Blade/JS output audit remains broader Slice 7 / final verification scope.
- #025 remains a separate Slice 7 issue.
- Commit/push proof for this docs update is not claimed here.

#### Strict Closure Decision

#024 is locally strict-fixed for the tested admin expense create JSON script-context sink because:

- source behavior matches the root-cause fix
- RED proof reproduced the reflected script-breakout vulnerability
- targeted GREEN proof passed
- focused expense blast-radius proof passed
- UI/server boundary is correctly scoped as output rendering, not mutation authorization
- ADR-0020 compatibility was checked
- remaining gaps are explicit and outside this local strict closure scope

## Severity

High.

## Ringkasan

Halaman `admin.expenses.create` memiliki reflected XSS pada blok JSON config di dalam `<script type="application/json">`.

`CreateExpensePageController` membaca `category_id` langsung dari query string, lalu mengirim nilainya ke view sebagai `selectedCategoryId`.

View `resources/views/admin/expenses/create.blade.php` menulis `selectedCategoryId` dan `categoryOptions` ke dalam script block memakai raw Blade output dan `json_encode` tanpa encoding aman untuk konteks HTML/script.

Payload berisi `</script>` dapat menutup script block lebih awal dan membuat script baru yang dieksekusi di browser admin.

## Jalur rentan

Attacker membuat URL admin expense create
-> admin yang sudah login membuka URL tersebut
-> `category_id` dari query string dibaca controller
-> nilai dikirim ke view sebagai `selectedCategoryId`
-> view menulis JSON config dengan raw output
-> payload `</script>` keluar dari script block
-> JavaScript attacker berjalan di origin aplikasi
-> script dapat membaca DOM admin dan mengirim request same-origin memakai sesi admin

## Root cause

Data tidak dipercaya ditulis ke dalam `<script>` memakai raw output dan JSON encoder yang tidak aman untuk konteks HTML.

`JSON_UNESCAPED_SLASHES` membuat `</script>` tetap literal, sehingga HTML parser dapat mengakhiri script element sebelum JSON selesai.

## Patch summary

`resources/views/admin/expenses/create.blade.php` diubah dari raw `json_encode(...)` menjadi Blade `@json(...)`.

Patch mempertahankan struktur config yang sama:

- `categoryOptions`
- `selectedCategoryId`
- `createCategoryBaseUrl`

Controller behavior tidak diubah.

## Verification

Reported failed check:

`php artisan test tests/Feature/Expense/CreateExpensePageFeatureTest.php`

Failure reason:

`vendor/autoload.php` missing / dependencies not installed.

## Verification gap

Patch sudah source-level untuk output encoding, tetapi feature test belum terbukti pass di environment laporan.

Future verification:

- install dependencies
- run `php artisan test tests/Feature/Expense/CreateExpensePageFeatureTest.php`
- render payload dengan `category_id=</script><script>alert(document.domain)</script>`
- pastikan output tidak membuat executable script element baru

## Relations

Related to #007.

#007 covers unsafe workspace JSON config causing stored XSS.

#024 covers unsafe expense-create JSON config causing reflected XSS from query-string input.

Keduanya berbagi hazard encoding JSON pada konteks script yang sama, tetapi memengaruhi halaman berbeda, sumber data berbeda, dan kelas exploit berbeda.

## Related #025 - Reflected javascript URL in product return link

#025 is related through reflected admin XSS. #024 covers unsafe JSON output inside a script block on the expense create page, while #025 covers unsafe URL scheme rendering inside an `href` on the product create page.
