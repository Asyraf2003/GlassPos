# 024 - Reflected XSS in expense create JSON config

## Status

Patched, with verification gap.

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

Both share the same script-context JSON encoding hazard, but they affect different pages, different data sources, and different exploit classes.
