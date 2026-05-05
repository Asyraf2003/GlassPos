# 016 - Unauthenticated admin capability toggle endpoints

## Status

Patched, with verification gap.

Patch supplied and feature tests updated, but tests could not run in the patch environment because vendor/autoload.php / dependencies were missing.

## Severity

High.

## Source

Audit report #016: Unauthenticated admin capability toggle endpoints.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 002-seeder-introduces-predictable-admin-credentials.md

### Jenis Keterkaitan

Indirect identity/access relationship.

### Alasan

Laporan #016 dan #002 sama-sama berada pada area identity/access security.

Namun keduanya tidak identik.

- #002 membahas predictable seeded admin credential dan risiko admin account takeover jika seeder dijalankan di environment non-disposable.
- #016 membahas unauthenticated privileged capability toggle endpoint yang dapat mengubah admin transaction capability state dan spoof audit performer.

Keduanya menyentuh akses admin, tetapi root cause, route, file, dan patch berbeda. Karena itu #016 dicatat sebagai file baru.

## Update Log

### Update 1

Initial audit log entry untuk laporan #016.

Alasan update:

- Laporan menunjukkan endpoint enable/disable admin transaction capability tidak memakai auth/admin middleware.
- Endpoint menerima target_actor_id dan performed_by_actor_id dari client.
- Use case hanya memvalidasi target adalah admin, bukan caller authorization.
- Patch menambahkan auth + admin.page middleware.
- Patch mengambil performed_by_actor_id dari authenticated session.
- Verification masih gap karena test gagal dijalankan akibat missing dependencies.

## Ringkasan Indonesia

Bug terjadi pada route identity access untuk admin transaction capability.

Route terdampak:

routes/web/identity_access.php

Endpoint:

- POST /identity-access/admin-transaction-capability/enable
- POST /identity-access/admin-transaction-capability/disable

Sebelum patch, route hanya dilindungi oleh middleware:

web

Tidak ada:

- auth
- admin.page
- policy/admin authorization

FormRequest untuk enable/disable juga:

- authorize(): true
- menerima target_actor_id dari client
- menerima performed_by_actor_id dari client

Controller lalu meneruskan kedua nilai tersebut langsung ke use case.

Use case hanya mengecek apakah target actor adalah admin. Jika target admin valid, capability bisa diaktifkan/dinonaktifkan dan audit log memakai performed_by_actor_id dari request body.

CSRF bukan authentication. Client unauthenticated masih bisa membuat session sendiri dan mendapatkan CSRF token dari flow web normal, lalu submit POST jika route tidak butuh auth.

## Dampak

Dampak utama:

- unauthenticated actor dapat mengubah admin transaction capability state jika mengetahui target admin actor_id
- admin transaction entry dapat diaktifkan atau dinonaktifkan tanpa otorisasi
- audit metadata dapat dipalsukan melalui performed_by_actor_id dari payload
- integrity identity/access control rusak
- financial transaction-entry governance dapat dilemahkan

Severity High tepat karena endpoint mengubah authorization control untuk fungsi transaksi finansial. Tidak otomatis Critical karena attacker masih perlu mengetahui target admin actor_id dan toggle ini tidak langsung membuat attacker login sebagai admin, tidak RCE, dan tidak langsung exfiltrate secret.

## Jalur Risiko

Workflow risiko:

1. Attacker membuka aplikasi tanpa login.
2. Attacker mendapat Laravel web session dan CSRF token.
3. Attacker mengetahui atau menebak target admin actor_id.
4. Attacker POST ke endpoint enable/disable capability.
5. Route lama hanya memakai web middleware.
6. FormRequest authorize() return true.
7. Request body berisi:
   - target_actor_id
   - performed_by_actor_id
8. Controller meneruskan body IDs ke use case.
9. Use case hanya mengecek target actor adalah admin.
10. Capability state diubah.
11. Audit log mencatat performed_by_actor_id palsu dari attacker payload.

## Root Cause

Root cause gabungan:

1. Privileged identity-access route tidak dilindungi auth/admin middleware.
2. FormRequest authorize() unconditional true.
3. performed_by_actor_id dipercaya dari request body.
4. Controller tidak mengambil performer dari authenticated session.
5. Use case mengecek target role, tetapi tidak mengotorisasi caller.
6. Audit metadata dapat diisi dari input client.

## Patch Summary

Patch diterapkan pada:

routes/web/identity_access.php

Perubahan:

- middleware route group dari:
  web
- menjadi:
  web, auth, admin.page

Patch juga diterapkan pada:

app/Adapters/In/Http/Requests/IdentityAccess/EnableAdminTransactionCapabilityRequest.php
app/Adapters/In/Http/Requests/IdentityAccess/DisableAdminTransactionCapabilityRequest.php

Perubahan:

- performed_by_actor_id dihapus dari request validation rules
- client tidak lagi dapat submit performer ID

Patch juga diterapkan pada:

app/Adapters/In/Http/Controllers/IdentityAccess/EnableAdminTransactionCapabilityController.php
app/Adapters/In/Http/Controllers/IdentityAccess/DisableAdminTransactionCapabilityController.php

Perubahan:

- performed_by_actor_id diambil dari:
  $request->user()->getAuthIdentifier()
- bukan dari validated request body

Test diupdate pada:

tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php
tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php

Test intent:

- endpoint dipanggil sebagai authorized admin
- audit context memakai authenticated actor ID
- client-supplied performer tidak lagi dipakai

## Scope In

- Identity access admin transaction capability enable endpoint.
- Identity access admin transaction capability disable endpoint.
- Route-level auth/admin protection.
- Removal of client-controlled performed_by_actor_id.
- Audit performer sourced from authenticated session.
- Feature test update for authenticated admin behavior.

## Scope Out

- Business policy redesign for who may toggle admin transaction capability.
- Owner-only approval workflow.
- Actor ID enumeration hardening.
- Existing audit log cleanup.
- CSRF/session configuration.
- Full route:list verification.
- Full feature test pass, because dependencies were missing.
- Seeded admin credential issue from #002.

## Proof Dari Patch Session

User reported:

- vulnerability still existed in HEAD
- privileged enable/disable endpoints protected with auth + admin.page middleware
- performed_by_actor_id removed from request validation
- controllers source performed_by_actor_id from authenticated session
- tests updated to authenticate as authorized admin
- audit context expected to use authenticated actor ID

Changed files:

routes/web/identity_access.php
app/Adapters/In/Http/Controllers/IdentityAccess/EnableAdminTransactionCapabilityController.php
app/Adapters/In/Http/Controllers/IdentityAccess/DisableAdminTransactionCapabilityController.php
app/Adapters/In/Http/Requests/IdentityAccess/EnableAdminTransactionCapabilityRequest.php
app/Adapters/In/Http/Requests/IdentityAccess/DisableAdminTransactionCapabilityRequest.php
tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php
tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php

Reported diff size:

+13
-28

Commit reported:

466e6a5 - Harden admin capability toggle authorization

PR title reported:

Harden identity-access admin capability toggle endpoints

Testing attempted:

php artisan test tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php

Result:

Failed due to missing vendor/autoload.php / dependencies not installed.

## Verification Gap

Tests were updated but did not pass in the patch environment.

Missing proof:

- unauthenticated POST to enable endpoint is rejected
- unauthenticated POST to disable endpoint is rejected
- non-admin authenticated user is rejected
- admin authenticated user can toggle according to policy
- performed_by_actor_id from payload is ignored
- audit log uses authenticated actor ID
- route:list confirms auth + admin.page middleware on both endpoints
- no alternate route exposes the same handlers without auth

## Recommended Follow-up

Minimum verification commands:

composer install
php artisan route:list | grep -E "identity-access|admin-transaction-capability"
php artisan test tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php

Recommended additional tests:

1. Guest POST enable:
   - expect redirect to login or 401/403 according to app behavior
   - no capability state mutation

2. Guest POST disable:
   - expect redirect to login or 401/403
   - no capability state mutation

3. Authenticated kasir POST:
   - expect forbidden
   - no capability state mutation

4. Authenticated admin POST with spoofed performed_by_actor_id:
   - expect success only if admin allowed
   - audit performed_by_actor_id must equal authenticated admin ID
   - spoofed body field must be ignored or rejected

5. Invalid target actor:
   - expect failure
   - no state mutation

## Kesimpulan

Laporan #016 valid sebagai High severity identity/access authorization issue.

Bug sebelumnya membuat privileged admin transaction capability toggle endpoint reachable tanpa auth/admin authorization dan mempercayai performed_by_actor_id dari client. Ini merusak capability state dan audit integrity.

Patch minimal sudah tepat untuk root cause langsung: route dilindungi auth + admin.page, dan performer audit diambil dari authenticated session. Namun test belum terbukti pass karena dependency environment belum tersedia, jadi status tetap patched with verification gap.
