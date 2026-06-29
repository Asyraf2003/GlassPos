# 007 - Admin note edit page exposes stored XSS

## Status

Status: Strict Fixed

Strict-Fixed-Scope: local workspace JSON script-context escaping for stored note/service fields and product selected_label data rendered through the shared note workspace config sink.

Patch disediakan dan regression test ditambahkan, tetapi focused test tidak dapat berjalan di environment patch karena vendor/autoload.php tidak ada.

## Severity

High.

## Source

Audit report #007: Admin note edit page exposes stored XSS.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 006-client-controlled-price-basis-bypasses-minimum-price-checks.md

### Jenis Keterkaitan

Direct workflow relationship, separate root cause.

### Alasan

Laporan #007 dan #006 sama-sama berada pada area note workspace/revision surface yang dapat dijangkau oleh authenticated admin/cashier workflows.

Namun root cause berbeda.

- #006 membahas trust-boundary bug pada client-controlled price_basis yang dipakai untuk bypass MinSellingPricePolicy.
- #007 membahas stored XSS pada admin edit workspace karena stored cashier-controlled fields dimasukkan ke raw JSON script block tanpa HTML-safe JSON escaping.

Keduanya menyentuh note workspace, tetapi #007 adalah browser/client-side injection issue, sedangkan #006 adalah server-side financial invariant bypass.

Karena root cause, sink, dampak, dan patch berbeda, laporan #007 dicatat sebagai file baru.

## Update Log

### Update 5 - 2026-05-10 strict local verification

Status changed from `Patched, with verification gap` to `Status: Strict Fixed` for the local #007 workspace JSON script-context scope.

Current source/test reality:

- The previous document status was stale because current local source initially still rendered the workspace config JSON with raw Blade output and only:
  - `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`
- RED proof reproduced the script-breakout vulnerability in the admin note workspace edit response.
- The production sink was patched at:
  - `resources/views/cashier/notes/workspace/create.blade.php`
- The final sink now uses:
  - `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`
- The regression test file is:
  - `tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php`

#### Strict Closure Packet

Status: Strict Fixed

Strict-Fixed-Scope: local rendered workspace JSON config escaping for #007 stored XSS sources that reach `oldNote`, `oldItems`, and `selected_label` in the shared note workspace config script block.

#### Root Cause

Stored user-controlled values from note/customer/service fields and product labels reached a JSON blob embedded inside an HTML `<script type="application/json">` block.

The old sink used raw Blade output and `json_encode(...)` with `JSON_UNESCAPED_SLASHES`, so `</script>` could remain literal in the HTML response. The browser HTML parser could close the JSON script block and execute attacker-controlled JavaScript in the victim admin/cashier same-origin session.

#### Source Reality

- `resources/views/cashier/notes/workspace/create.blade.php`: renders `cashier-note-workspace-config` with JSON_HEX script-safe flags.
- `app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php`: still contains a stale `workspaceConfigJson` field using `JSON_UNESCAPED_SLASHES`, but current source grep found only this builder reference and the shown Blade view does not render that variable. It is recorded as a source smell, not a proven rendered sink in this closure.
- `app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceProductLineMapper.php`: product `namaBarang()` can flow into `selected_label`.
- `app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceProductOnlyMapper.php`: product-only revision rows copy `selected_label` into `oldItems`.
- `app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceServiceStoreStockMapper.php`: service-with-store-stock revision rows copy `selected_label` into `oldItems`.

#### UI Blade Impact

Impact: yes.

View path:

- `resources/views/cashier/notes/workspace/create.blade.php`

UI invariant:

- user-controlled text may appear in workspace config data, but it must not appear as literal script-breaking HTML.
- the response must not contain literal `</script><script>` from attacker-controlled values.
- the escaped form must be present as JSON-safe text.

#### Server Boundary

This issue is an output-context/rendering vulnerability, not a server-side authorization or mutation boundary.

- Direct GET: admin workspace edit route was used to render the vulnerable response.
- Direct mutation request: not applicable for #007 closure scope.
- No mutation proof: not applicable for this XSS rendering closure.
- Admin boundary: admin edit workspace rendering path covered by feature tests.
- Cashier boundary: shared cashier workspace view sink is patched; focused workspace tests covered cashier edit/create workspace rendering compatibility.

#### ADR / Rule Compatibility

- `docs/02_architecture/adr/0020_public_surface_output_storage_attachment_security.md`: requires context-aware output, safe JavaScript config encoding, no raw user-controlled HTML, and no final fixed claim from patch existence alone.
- Conflict: none found for this #007 local closure scope.

#### RED Proof

Command:

    php artisan test tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php --filter=script_breaking

Observed failure before production patch:

- `FAIL Tests\Feature\Note\AdminNoteWorkspaceReplacementFeatureTest`
- `1 failed / 3 assertions`
- failure at `tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php:162`
- rendered response still contained:
  - `</script><script>alert(1)</script>`

#### GREEN Proof

Command:

    php artisan test tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php --filter=script_breaking

Observed pass after Blade JSON_HEX patch and product-label characterization:

- `PASS Tests\Feature\Note\AdminNoteWorkspaceReplacementFeatureTest`
- `2 passed / 10 assertions`

Covered payload sources:

- stored note/customer/service fields with `alert(1)`
- product `namaBarang()` flowing through `selected_label` with `alert(2)`

Product-label proof note:

- Product-label RED-before-patch was not available in this later local step because the shared Blade JSON sink had already been fixed in current HEAD.
- The product-label test is therefore post-patch characterization for the same sink and documented data flow.

#### Focused Blast-Radius Proof

Command:

    php artisan test \
      tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php \
      tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceTemplateContractFeatureTest.php \
      tests/Feature/Note/CashierClosedNoteWorkspaceReplacementFeatureTest.php

Observed pass:

- `PASS`
- `7 passed / 48 assertions`

#### Negative Search

Search result before docs closure found:

- `RAW_JSON_HITS`
  - `resources/views/cashier/notes/workspace/create.blade.php`
- `SCRIPT_CONFIG_HITS`
  - includes workspace, expense, dashboard, note index, alerts, and other application/json blocks
- `RAW_BLADE_HITS`
  - `resources/views/cashier/notes/workspace/create.blade.php`
  - `resources/views/admin/expenses/create.blade.php`
  - `resources/views/layouts/partials/alerts.blade.php`

Classification:

- workspace create raw JSON hit is the #007 sink and is now script-safe through JSON_HEX flags.
- `resources/views/admin/expenses/create.blade.php` belongs to #024 and remains separate Slice 7 scope.
- `resources/views/layouts/partials/alerts.blade.php` is not closed by this #007 proof and remains a later classification/audit candidate if it handles user-controlled raw output.

#### Remaining Gaps

- Browser/manual QA was not run.
- Full global `make verify` was not run for this #007 closure step.
- Full project-wide Blade/JS output audit remains broader Slice 7 / final verification scope.
- The stale `workspaceConfigJson` builder field still uses unsafe flags but has no rendered usage found by current grep; it should not be described as an active rendered sink without new proof.
- #024 and #025 remain separate Slice 7 issues.
- Commit/push proof for this docs/test update is not claimed here.

#### Strict Closure Decision

#007 is locally strict-fixed for the tested workspace JSON script-context sink because:

- source behavior matches the root-cause fix
- RED proof reproduced the stored script-breakout vulnerability
- targeted GREEN proof passed for stored note/service fields and product selected_label source
- focused workspace blast-radius proof passed
- UI/server boundary is correctly scoped as output rendering, not authorization
- ADR-0020 compatibility was checked
- remaining gaps are explicit and outside this local strict closure scope

### Update 1

Initial audit log entry untuk laporan #007.

Alasan update:

- Laporan menunjukkan cashier-controlled stored note fields dapat menjadi stored XSS saat admin membuka edit workspace.
- Patch diterapkan pada Blade shared workspace view.
- Regression test ditambahkan untuk script-breaking payload.
- Verification masih gap karena php artisan test gagal akibat missing vendor/autoload.php.

## Ringkasan Indonesia

Bug terjadi pada admin note edit workspace.

Cashier dapat menyimpan field seperti:

- note.customer_name
- service.name
- field note/service lain yang diterima sebagai string

Field tersebut kemudian dibaca ulang oleh EditTransactionWorkspacePageDataBuilder dan dimasukkan ke:

- oldNote
- oldItems
- defaultCustomerName
- workspace config JSON

Shared workspace Blade view menulis data tersebut ke:

<script type="application/json">

menggunakan raw output:

{!! json_encode(...) !!}

tanpa JSON_HEX_TAG atau safe JavaScript encoding.

Masalahnya, browser tetap akan menghentikan script block saat menemukan literal:

</script>

walaupun script type adalah application/json.

Jadi payload stored seperti:

</script><script>alert(1)</script>

dapat memecah JSON script block dan membuat script attacker dieksekusi ketika admin membuka edit workspace untuk note tersebut.

## Dampak

Dampak utama:

- cashier dapat menanam payload di note/service field
- admin membuka admin note edit workspace
- JavaScript attacker berjalan dalam admin same-origin session
- script dapat membaca halaman admin yang bisa diakses victim
- script dapat submit request state-changing memakai admin session dan CSRF token yang tersedia di page
- cashier-to-admin privilege boundary dapat ditembus melalui browser

HttpOnly cookie tidak cukup sebagai mitigasi karena XSS tidak perlu mencuri cookie untuk melakukan same-origin request memakai session victim.

Severity High tepat karena ini stored XSS lintas role cashier ke admin pada aplikasi POS/back-office. Tidak otomatis Critical karena butuh authenticated cashier, admin interaction, dan tidak langsung membuktikan RCE/server-side compromise/secret-store compromise.

## Jalur Risiko

Workflow risiko:

1. Cashier login dengan akses transaksi.
2. Cashier membuat/mengubah note field atau service field.
3. Payload disimpan, misalnya:
   </script><script>alert(1)</script>
4. Admin membuka detail note.
5. Admin klik edit workspace atau langsung akses /admin/notes/{noteId}/workspace/edit.
6. EditTransactionWorkspacePageDataBuilder preload stored fields ke oldNote/oldItems.
7. Blade view render config JSON memakai raw json_encode.
8. Literal </script> memecah script block.
9. Browser mengeksekusi attacker-controlled script dalam admin session.

## Root Cause

Root cause:

Stored user-controlled data dimasukkan ke HTML script context dengan raw JSON output tanpa escaping yang aman untuk HTML parser.

json_encode saja tidak cukup untuk script context jika output dapat berisi literal:

- </script>
- <
- >
- &
- quotes/apostrophes dalam konteks tertentu

Untuk Laravel Blade, opsi aman biasanya:

- Illuminate\Support\Js::from(...)
- atau json_encode dengan JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_HEX_QUOT

Bug ini juga menunjukkan trust boundary cashier -> admin belum diproteksi pada rendering layer.

## Patch Summary

Patch diterapkan pada:

resources/views/cashier/notes/workspace/create.blade.php

Perubahan:

json_encode flags sebelumnya:

JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES

diubah menjadi:

JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT

Efek patch:

- literal < dan > pada payload menjadi escaped unicode form
- </script> tidak muncul literal di HTML output
- HTML parser tidak dapat memecah script block dari stored payload
- JSON payload structure tetap dipertahankan

Test ditambahkan pada:

tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php

Test baru:

test_admin_workspace_config_json_escapes_script_breaking_sequences_from_stored_fields

Test intent:

- seed stored payload ke note/customer dan service field
- buka admin workspace edit page
- assert raw breakout string tidak ada
- assert escaped JSON form ada

## Scope In

- Admin note edit workspace XSS sink.
- Shared workspace Blade JSON config script.
- Stored cashier-controlled note/service fields.
- HTML parser breakout via </script>.
- JSON_HEX escaping remediation.

## Scope Out

- Full CSP implementation.
- Replacing all Blade JSON sinks across project.
- Sanitizing stored data at database level.
- Removing price_basis issue from #006.
- Server-side note revision financial logic.
- Full browser-to-database E2E proof.
- Production deployment/CSP verification.

## Proof Dari Patch Session

User reported:

- vulnerability still exists in HEAD at the same sink
- fix applied by adding JSON hex-escaping flags
- regression test added for stored script-breaking payload
- committed with commit:
  96668aa
- PR message created using make_pr tool

Changed files:

resources/views/cashier/notes/workspace/create.blade.php
tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php

Reported diff size:

+20
-1

Testing reported:

php artisan test tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php

Result:

Failed due to environment limitation.

Failure reason:

missing vendor/autoload.php

## Verification Gap

Regression test sudah ada, tetapi belum pass di environment patch.

Karena itu, patch ini harus diperlakukan sebagai source-fixed tetapi belum terverifikasi penuh secara behavior sampai test berhasil dijalankan.

Missing proof:

- focused test pass
- rendered admin edit page verified not to contain literal </script><script>
- escaped JSON form verified in actual response
- no other workspace config JSON sinks remain unsafe
- no CSP assumptions required for exploit prevention

## Recommended Follow-up

Minimum verification command:

composer install
php artisan test tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php

Recommended additional audit:

Search for other raw JSON script sinks:

grep -R "{!! json_encode" -n resources/views
grep -R "type=\"application/json\"" -n resources/views

Setiap raw JSON script sink yang memuat data user-controlled harus memakai Js::from atau flag JSON_HEX_*.

## Kesimpulan

Laporan #007 valid sebagai High severity stored XSS.

Bug sebelumnya menaruh stored cashier-controlled data ke raw JSON inside script tag. Karena HTML parser tetap membaca literal </script>, payload dapat keluar dari application/json script block dan menjadi executable script ketika admin membuka edit workspace.

Patch minimal sudah tepat untuk sink langsung: JSON_HEX_* flags menetralkan script-breaking sequences tanpa mengubah struktur JSON. Namun test belum terbukti pass karena dependency environment belum tersedia, jadi status tetap patched with verification gap.

## Related Workspace Authorization Finding From Error Log 009

### Related Error Log

- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity issue in the note workspace surface.

Ini bukan root cause yang sama dengan #007.

- #007 is about stored XSS in the admin workspace rendering path.
- #009 is about cashier closed-note mutation through workspace update authorization regression.

Both are workspace-surface security findings, but one is browser injection and the other is server-side authorization.

## Related Workspace Edit Surface Finding From Error Log 015

### Related Error Log

- 015-refunded-notes-expose-edit-workspace.md

### Update

Update 3.

### Reason

A later audit report found a separate issue involving the note edit workspace surface.

Ini bukan root cause yang sama dengan #007.

- #007 is about stored XSS in workspace JSON rendering.
- #015 is about Edit button visibility for refunded notes.

Both affect workspace exposure, but one is browser injection and the other is editability/navigation control.

## Additional Stored XSS Data Flow From Product Labels

### Update 4

### Related Report Title

Stored XSS via product labels in note edit config

### Relationship Classification

Sink sama / root cause sama / sumber data tambahan.

Ini bukan error log baru karena memakai workspace bootstrap sink vulnerable yang sama dan sudah didokumentasikan di #007:

resources/views/cashier/notes/workspace/create.blade.php

The original #007 flow used cashier-controlled note/service fields.

This update adds another confirmed data source:

- product catalog name
- RevisionWorkspaceProductLineMapper
- selected_label
- oldItems
- cashier note workspace JSON config

Kedua flow berakhir di raw JSON script block yang sama-sama tidak aman.

### Summary

A stored XSS data flow was introduced by adding product catalog labels to the note workspace edit config.

Product names are accepted as strings and can contain HTML parser-breaking payloads such as:

</script><script>...</script>

Revision workspace mapping resolves the product and copies product->namaBarang() into selected_label.

That selected_label is included in oldItems for:

- product-only revision rows
- service-with-store-stock revision rows

The workspace Blade view embeds oldItems into:

<script type="application/json">

Before the patch, it used raw:

{!! json_encode(..., JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}

Karena literal </script> tidak di-escape, browser dapat menutup JSON script block dan menjalankan JavaScript yang dikendalikan attacker di sesi same-origin korban.

### Affected Data Flow

1. Authenticated transaction-entry user creates or updates product name.
2. Product name contains script-breaking payload.
3. Note/revision includes that product as store-stock line.
4. RevisionWorkspaceProductLineMapper reads current product name.
5. Product name is copied into selected_label.
6. Product-only or service-with-store-stock mapper includes selected_label in oldItems.
7. Workspace view embeds oldItems into JSON script block.
8. Raw json_encode with JSON_UNESCAPED_SLASHES leaves </script> dangerous.
9. Victim opens note edit workspace.
10. Injected script executes same-origin.

### Files Mentioned By Report

Product input:

app/Adapters/In/Http/Requests/ProductCatalog/CreateProductRequest.php

Product label mapping:

app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceProductLineMapper.php
app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceProductOnlyMapper.php
app/Application/Note/Services/RevisionWorkspace/RevisionWorkspaceServiceStoreStockMapper.php

Unsafe sink:

resources/views/cashier/notes/workspace/create.blade.php

Product route surface:

routes/web/product_catalog.php

### Patch Summary

Patch applied to:

resources/views/cashier/notes/workspace/create.blade.php

Change:

- replaced raw `{!! json_encode(...) !!}` output with Blade `@json(...)`
- removed unsafe JSON_UNESCAPED_SLASHES usage from the script block
- preserved existing config payload structure and keys

Reported commit message:

Fix workspace config JSON embedding to prevent script breakout

Reported testing:

- php -l resources/views/cashier/notes/workspace/create.blade.php
- git status --short
- git add resources/views/cashier/notes/workspace/create.blade.php && git commit -m "Fix workspace config JSON embedding to prevent script breakout"

### Verification Gap

Only syntax validation was reported.

Missing proof:

- product name containing </script><script> no longer appears literally in rendered workspace config
- oldItems selected_label is safely escaped
- note/service field XSS flow from earlier #007 remains fixed
- admin and cashier edit workspace both use the safe rendering
- no other `{!! json_encode(...) !!}` script sinks remain in workspace views

### Important Merge Note

Earlier #007 patch used JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, and JSON_HEX_QUOT on json_encode.

Update ini melaporkan patch yang memakai Blade @json.

Kedua pendekatan bertujuan memperbaiki sink yang sama. Final branch harus memakai satu pendekatan aman secara konsisten dan tidak boleh mengembalikan:

JSON_UNESCAPED_SLASHES

inside script blocks containing user-controlled data.

### Recommended Follow-up

Run focused XSS rendering tests for both data sources:

1. note/customer/service field payload:
   </script><script>alert(1)</script>

2. product name selected_label payload:
   </script><script>alert(1)</script>

Expected result:

- raw breakout string is not present
- safe escaped form is present
- no extra executable script element is created

Recommended audit command:

grep -R "{!! json_encode" -n resources/views
grep -R "JSON_UNESCAPED_SLASHES" -n resources/views
grep -R "type=\"application/json\"" -n resources/views

### Kesimpulan

Update ini memperkuat #007 dengan mengonfirmasi bahwa titik output JSON workspace yang sama juga dapat dijangkau dari label product catalog, bukan hanya dari field note/service.

Akar masalahnya tetap sama: JSON yang tidak aman dimasukkan ke konteks HTML script. Fix yang benar adalah render JSON secara aman memakai @json, Js::from, atau flag JSON_HEX_*, ditambah regression test yang membuktikan literal </script> tidak dapat muncul di konfigurasi workspace yang dirender.

## Related #024 - Reflected XSS in expense create JSON config

#024 is related through the same unsafe JSON-in-script encoding pattern. #007 covers stored XSS in workspace JSON config, while #024 covers reflected XSS in the expense create page JSON config from query-string `category_id`.

## Related #025 - Reflected javascript URL in product return link

#025 is related to the broader XSS/output-context cluster. #007 covers stored XSS through workspace JSON config, while #025 covers reflected click-triggered XSS through an untrusted `href` URL.

## Update - Script-breaking XSS in cashier workspace config JSON

Laporan ini diklasifikasikan sebagai update #007, bukan file error-log baru.

## Update Status

Patched.

## Summary

Workspace JSON script sink yang sama dilaporkan lagi dengan bukti tambahan dari cashier workspace.

`resources/views/cashier/notes/workspace/create.blade.php` rendered `cashier-note-workspace-config` inside:

`<script type="application/json">`

using raw Blade output and `json_encode(...)` with:

`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`

Because `JSON_UNESCAPED_SLASHES` keeps `</script>` literal, attacker-controlled values can terminate the JSON script block and inject executable JavaScript.

## Additional Data Sources

The affected config includes:

- `oldNote`
- `oldInlinePayment`
- `oldItems`
- `defaultCustomerName`
- other workspace config values

Reported attacker-controlled paths:

- `old('note')`
- `old('inline_payment')`
- stored note fields from edit workspace, including `customer_name` and `customer_phone`

## Additional Vulnerable Path

Authenticated cashier submits script-breaking payload
-> payload stored in note/customer fields or returned through old input
-> workspace page renders `oldNote` / `oldInlinePayment`
-> raw JSON script block preserves `</script>`
-> browser closes JSON script early
-> injected script executes in another cashier/admin browser session
-> same-origin cashier/admin actions become possible with victim session

## Patch Variant

Fix yang dilaporkan mengubah flag JSON di:

`resources/views/cashier/notes/workspace/create.blade.php`

from:

`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`

to:

`JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`

Ini mempertahankan perilaku Unicode sambil membuat JSON aman untuk konteks script.

## Verification

Reported successful check:

`php -l resources/views/cashier/notes/workspace/create.blade.php`

Reported commit:

`2dc1e2b`

## Merge Safety Note

Final branch must keep script-safe JSON encoding for every workspace JSON block.

Jangan mengembalikan `JSON_UNESCAPED_SLASHES` di dalam konteks `<script>` kecuali digabung dengan flag `JSON_HEX_*` yang wajib, atau diganti dengan framework helper yang aman untuk konteks ini.

Tidak ada kenaikan progress karena ini root cause yang sama dan cluster workspace JSON sink yang sama dengan #007.

## Update - Stored XSS via new cashier note edit route

Laporan ini diklasifikasikan sebagai update #007, bukan file error-log baru.

## Update Status

Patched.

## Summary

Laporan lanjutan mengonfirmasi jalur reachable lain menuju workspace JSON script sink yang sama.

Route edit kasir baru membuat `EditTransactionWorkspacePageController` reachable untuk user terautentikasi dengan akses cashier-area. Controller tersebut merender shared workspace view dengan data dari `EditTransactionWorkspacePageDataBuilder`.

Builder menyalin string note dan work-item yang tersimpan ke `workspaceConfigJson`, lalu meng-encode config dengan:

`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`

View merender nilai tersebut memakai raw Blade output di dalam:

`<script type="application/json">`

Karena `JSON_UNESCAPED_SLASHES` mempertahankan literal `</script>` dan sink memakai raw output, nilai tersimpan dapat menutup JSON script block dan menjalankan JavaScript.

## Additional Data Sources

Field tersimpan yang dilaporkan dapat mencapai sink:

- `note.customer_name`
- `items.*.service.name`
- `items.*.external_purchase_lines.0.label`

Nilai-nilai ini divalidasi sebagai string, tetapi tidak di-encode untuk konteks script sebelum masuk ke JSON sink.

## Additional Vulnerable Path

Authenticated cashier stores script-breaking text
-> value persists in note/work-item fields
-> another cashier or admin opens `/cashier/notes/{noteId}/workspace/edit`
-> edit route reaches `EditTransactionWorkspacePageController`
-> `EditTransactionWorkspacePageDataBuilder` builds `workspaceConfigJson`
-> raw JSON script block preserves `</script>`
-> browser creates executable script element
-> injected JavaScript runs with victim session and same-origin access

## Patch Variant

The reported fix changes `json_encode` flags in:

`app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php`

from:

`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`

to:

`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`

Ini mencegah breakout bergaya `</script>` sambil mempertahankan struktur config existing untuk parsing frontend.

## Verification

Reported successful check:

`php -l app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php`

Reported commit:

`Fix workspace JSON script escaping for edit page`

## Merge Safety Note

Final branch must keep script-safe JSON encoding for every workspace JSON sink.

Jangan emit `workspaceConfigJson` atau config sejenis melalui raw Blade output kecuali JSON sudah di-encode dengan flag script-safe atau framework helper yang aman untuk konteks ini.

Tidak ada kenaikan progress karena ini root cause yang sama dan cluster stored XSS workspace JSON sink yang sama dengan #007.
