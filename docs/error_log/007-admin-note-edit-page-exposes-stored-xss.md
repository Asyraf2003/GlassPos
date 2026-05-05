# 007 - Admin note edit page exposes stored XSS

## Status

Patched, with verification gap.

Patch supplied and regression test added, but the focused test could not run in the patch environment because vendor/autoload.php was missing.

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

Regression test exists but did not pass in the patch environment.

Therefore this patch should be treated as source-fixed but not fully behavior-verified until the test runs successfully.

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

Any raw JSON script sink that includes user-controlled data should use Js::from or JSON_HEX_* flags.

## Kesimpulan

Laporan #007 valid sebagai High severity stored XSS.

Bug sebelumnya menaruh stored cashier-controlled data ke raw JSON inside script tag. Karena HTML parser tetap membaca literal </script>, payload dapat keluar dari application/json script block dan menjadi executable script ketika admin membuka edit workspace.

Patch minimal sudah tepat untuk sink langsung: JSON_HEX_* flags menetralkan script-breaking sequences tanpa mengubah struktur JSON. Namun test belum terbukti pass karena dependency environment belum tersedia, jadi status tetap patched with verification gap.
