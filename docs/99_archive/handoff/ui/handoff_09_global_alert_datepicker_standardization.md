# Handoff

## Metadata
- Tanggal: 2026-03-25
- Nama slice / topik: UI global feedback notification + standardisasi datepicker
- Workflow step: UI consistency / global shared behavior hardening
- Status: Selesai dengan koreksi sinkronisasi implementasi mobile range-single
- Progres: 100%

## Target halaman kerja
Menstandarkan 2 area UI lintas halaman:
1. Feedback notification global:
   - success / warning / info -> toast
   - error / validation error -> dialog
   - basis SweetAlert
   - payload dinormalisasi sebelum view render
2. Date input global:
   - single date -> Flatpickr global
   - filter date range -> 1 visible range input mirip template, tetap menjaga canonical hidden fields existing
   - desktop terverifikasi live
  - mobile range-single bergantung pada patch `static/coarse-pointer` di file runtime final `public/assets/static/js/shared/admin-date-input.js`

## Referensi yang dipakai `[REF]`
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- DoD:
  - `docs/dod/dod_v1.md`
- ADR:
  - `docs/adr/0007-admin-transaction-entry-behind-capability-policy.md`
  - `docs/adr/0008-audit-first-sensitive-mutations.md`
- Handoff sebelumnya:
  - `docs/handoff/ui/handoff_07_operational_expense_hardening_closure.md`
- Snapshot repo / output command yang dipakai:
  - `tree -L9 app database docs mk resources routes scripts tests`
  - `grep -RIn --include='*.blade.php' 'type="date"' resources/views`
  - `grep -RIn --include='*.blade.php' 'data-ui-date=' resources/views`
  - verifikasi runtime live manual desktop + mobile pada halaman target

## Fakta terkunci `[FACT]`
- Repo sudah memiliki partial alert global di:
  - `resources/views/layouts/partials/alerts.blade.php`
- Repo sudah memiliki shared layout:
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/auth.blade.php`
- Repo sudah memiliki middleware shared shell:
  - `app/Adapters/In/Http/Middleware/IdentityAccess/ShareAppShellData.php`
- Repo sudah memiliki asset Flatpickr lokal di:
  - `public/assets/extensions/flatpickr/*`
- Field validation English seperti:
  - `The lines.0.product_id field is required.`
  berasal dari default Laravel validation message, bukan dari SweetAlert
- Locale app dan testing sudah dikunci ke Indonesia:
  - `APP_LOCALE=id`
  - `APP_FALLBACK_LOCALE=id`
  - `APP_FAKER_LOCALE=id_ID`
- FormRequest procurement sudah diubah agar validation nested lines tampil natural dalam Bahasa Indonesia
- Hook global date UI sudah dikunci:
  - `data-ui-date="single"`
  - `data-ui-date="range-single"`
- Opsi range yang dipakai adalah:
  - 1 visible range input
  - 2 hidden canonical fields existing
  - kontrak existing query/filter tetap dipertahankan
- Verifikasi yang benar-benar terkunci untuk slice ini:
  - desktop berhasil
  - mobile range-single memerlukan patch `static/coarse-pointer` pada `bindRangeSingle` di `public/assets/static/js/shared/admin-date-input.js`
  - status mobile harus selalu dicocokkan dengan isi file runtime final, bukan handoff saja

## Scope yang dipakai
### `[SCOPE-IN]`
- Standardisasi global alert UI berbasis SweetAlert
- Normalisasi session flash + validation error menjadi payload UI tunggal
- Standardisasi locale validasi ke Bahasa Indonesia
- Standardisasi global single date input dengan Flatpickr
- Standardisasi filter date range menjadi 1 visible range input
- Hardening mobile behavior untuk range filter

### `[SCOPE-OUT]`
- Tidak mengubah domain rule di `Core/*`
- Tidak mengubah business use case di `Application/*`
- Tidak mengubah kontrak backend filter/query menjadi single field
- Tidak membongkar existing canonical field `from/to`
- Tidak memigrasikan semua kemungkinan field tanggal di luar titik yang terverifikasi dalam slice ini

## Keputusan yang dikunci `[DECISION]`
- SweetAlert hanya concern presentation layer
- Payload feedback global dinormalisasi sebelum view render, bukan dirakit di Blade
- Blade tidak dijadikan tempat logika session/errors yang berat
- Footer WhatsApp untuk error diperlakukan sebagai concern presentation
- Semua validation/error dialog diprioritaskan lebih tinggi daripada success/info/warning toast
- Bahasa validation dikunci ke Indonesia secara global
- Nested validation kompleks tetap boleh punya `messages()` / `attributes()` khusus per FormRequest
- Single date global memakai Flatpickr shared initializer
- Filter range memakai Opsi A:
  - 1 visible range input mirip template
  - hidden canonical existing tetap dipakai untuk submit/state/filter
- Mobile range-single pada `bindRangeSingle` di `public/assets/static/js/shared/admin-date-input.js` wajib memakai render `static` untuk coarse pointer agar tidak gagal saat dibuka dalam drawer

## File yang dibuat/diubah `[FILES]`

### File baru
- `public/assets/static/js/shared/admin-date-input.js`
- `lang/id/validation.php`

### File diubah
- `app/Adapters/In/Http/Middleware/IdentityAccess/ShareAppShellData.php`
- `routes/web/auth.php`
- `resources/views/layouts/partials/alerts.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/auth.blade.php`
- `app/Adapters/In/Http/Requests/Procurement/CreateSupplierInvoiceRequest.php`
- `config/app.php`
- `.env`
- `.env.testing`
- `resources/views/admin/expenses/create.blade.php`
- `resources/views/admin/payrolls/create.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `resources/views/admin/expenses/partials/filter_drawer.blade.php`
- `resources/views/admin/procurement/supplier_invoices/partials/filter_drawer.blade.php`
- `public/assets/static/js/pages/admin-expenses-table.js`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`

## Ringkasan implementasi

### 1. Global alert / notification
Alur final:
- session flash + validation error
- dinormalisasi di `ShareAppShellData`
- dibagikan ke view sebagai payload UI feedback
- `alerts.blade.php` menjadi renderer SweetAlert
- behavior final:
  - `error` -> dialog
  - `success` / `warning` / `info` -> toast

### 2. Bahasa validation Indonesia
- locale app dan testing dikunci ke `id`
- dibuat `lang/id/validation.php`
- `CreateSupplierInvoiceRequest` diberi `messages()` dan `attributes()` agar nested lines tampil natural

### 3. Global single date
- Flatpickr dimuat global dari layout
- shared initializer di `admin-date-input.js`
- input yang memakai `data-ui-date="single"` otomatis bind ke Flatpickr

### 4. Global range filter seperti template
- drawer filter tidak lagi menampilkan 2 input date visible
- sekarang user melihat 1 visible range input
- hidden canonical fields tetap ada:
  - expense -> `date_from`, `date_to`
  - procurement -> `shipment_date_from`, `shipment_date_to`
- JS shared melakukan sinkronisasi:
  - visible range -> hidden canonical
  - hidden canonical -> preload visible range
- JS table tetap memakai canonical fields, jadi kontrak existing aman

### 5. Mobile hardening
- Gejala awal mobile:
  - klik range field membuat layer/drawer terasa burem dan tidak usable
- Solusi final yang harus ada di runtime file:
  - `bindRangeSingle` pada `public/assets/static/js/shared/admin-date-input.js`
  - opsi Flatpickr `static: window.matchMedia?.('(pointer: coarse)').matches ?? false,`
- Catatan sinkronisasi:
  - status mobile tidak boleh dianggap final bila file runtime belum dicocokkan
- Hasil verifikasi yang aman dicatat:
  - desktop berhasil
  - mobile range-single bergantung pada keberadaan patch runtime di atas

## Bukti verifikasi `[PROOF]`
- command:
  - `php -l app/Adapters/In/Http/Requests/Procurement/CreateSupplierInvoiceRequest.php`
  - hasil:
    - `No syntax errors detected`
- command:
  - `php -l config/app.php`
  - hasil:
    - `No syntax errors detected`
- command:
  - `grep -nE 'APP_LOCALE|APP_FALLBACK_LOCALE|APP_FAKER_LOCALE' .env`
  - hasil:
    - `APP_LOCALE=id`
    - `APP_FALLBACK_LOCALE=id`
    - `APP_FAKER_LOCALE=id_ID`
- command:
  - `grep -nE 'APP_LOCALE|APP_FALLBACK_LOCALE|APP_FAKER_LOCALE' .env.testing`
  - hasil:
    - `APP_LOCALE=id`
    - `APP_FALLBACK_LOCALE=id`
    - `APP_FAKER_LOCALE=id_ID`
- command:
  - `grep -RIn --include='*.blade.php' 'data-ui-date=' resources/views`
  - hasil:
    - hook single dan range-single/range terpasang pada target yang diverifikasi
- command:
  - `grep -n 'AdminDateInput' public/assets/static/js/pages/admin-expenses-table.js`
  - hasil:
    - refresh hook terpasang
- command:
  - `grep -n 'AdminDateInput' public/assets/static/js/pages/admin-procurement-invoices-table.js`
  - hasil:
    - refresh hook terpasang
- command:
  - `grep -nA12 -B4 "const bindRangeSingle = (input) => {" public/assets/static/js/shared/admin-date-input.js`
  - hasil:
    - block `bindRangeSingle` harus memuat `static: window.matchMedia?.('(pointer: coarse)').matches ?? false,`
- verifikasi live:
  - notification flow berhasil
  - validation Bahasa Indonesia berhasil
  - single date Flatpickr berhasil
  - range filter desktop berhasil
  - range filter mobile hanya boleh dinyatakan berhasil bila patch runtime `bindRangeSingle` di atas benar-benar ada

## Risiko / catatan lanjutan
- `auth.blade.php` sempat tertinggal JS Flatpickr saat salah satu patch awal; pastikan final file sudah sinkron dengan kebutuhan auth page bila nanti ada date input di auth context
- Masih ada kemungkinan titik `type="date"` lain di luar slice yang belum dimigrasikan bila muncul halaman baru atau bila grep baru menemukan area lain
- Jika nanti target UI ingin 100% identik dengan template dalam semua kondisi mobile/desktop dan filter complex state, tetap wajib audit file runtime final karena handoff ini pernah tidak sinkron dengan `public/assets/static/js/shared/admin-date-input.js`

## Next yang direkomendasikan
1. Audit semua titik `type="date"` baru setiap ada halaman baru, wajib pakai hook shared:
   - `data-ui-date="single"`
   - `data-ui-date="range-single"` untuk range visible
2. Jangan buat init Flatpickr per halaman lagi
3. Bila ada form/filter baru dengan range:
   - tetap simpan canonical hidden fields existing bila query/backend sudah from/to
4. Bila ingin lanjut hardening, buat test UI/manual checklist untuk:
   - desktop single
   - desktop range
   - mobile single
   - mobile range
   - preload dari URL/state

## Status penutupan
Slice ini boleh dianggap closed dengan koreksi ini:
- verifikasi desktop sudah kuat
- mobile range-single hanya dianggap selesai bila file runtime final `public/assets/static/js/shared/admin-date-input.js` memang memuat patch `static/coarse-pointer` pada `bindRangeSingle`
- kontrak existing tetap aman
- pattern shared/global sudah terbentuk
- audit handoff harus dicocokkan dengan file runtime final agar tidak terjadi mismatch dokumentasi vs implementasi