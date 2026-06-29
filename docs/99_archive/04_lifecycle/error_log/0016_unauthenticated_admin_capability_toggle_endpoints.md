# 016 - Unauthenticated admin capability toggle endpoints

## Status

Fixed with proof and explicit residual global/browser gaps.

Local source contradicted the previous patched-with-gap document status: the route still used only `web` middleware, requests still accepted `performed_by_actor_id`, and controllers still trusted the client-supplied performer. The vulnerability was reproduced with RED tests, fixed with route/auth/audit performer hardening, then verified with targeted and focused auth/access tests.

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

Test sudah diupdate, tetapi belum pass di environment patch.

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

## Related #020 - Admin note actions bypass transaction capability

#020 is directly related to the identity/access capability authorization cluster. #016 covers unauthenticated capability toggle endpoints, while #020 covers admin note mutation routes that bypass the `transaction.entry` / `EnsureTransactionEntryAllowed` gate after an admin is authenticated.

## Related #002 update - Predictable seeded admin credentials introduced

The #002 update remains indirectly related to the identity/access cluster. #002 covers predictable seeded admin credentials and privileged role/capability seeding, while #016 covers unauthenticated admin capability toggle endpoints.

## Related #027 - Admin invoice creation bypasses transaction-entry gate

#027 is related through identity/access capability enforcement. #016 covers unauthenticated capability toggle endpoints, while #027 covers a supplier-invoice mutation route that bypassed the transaction-entry capability gate after authentication.

## Related report: Unauthenticated admin capability toggle routes

Klasifikasi: update existing #016, bukan file error-log unik baru.

Severity: High.

Related commit: af9afc8.

Patch report commit: eed085b.

Summary:
The identity-access admin transaction capability toggle endpoints were reachable through the main web router after `routes/web.php` included `routes/web/identity_access.php`. The route group used only Laravel `web` middleware, which provides session/CSRF behavior but does not authenticate or authorize the caller.

The enable and disable FormRequests authorized all callers and accepted both `target_actor_id` and `performed_by_actor_id` from client-controlled request input. The controllers passed those values directly into the enable/disable use cases. The handlers only checked that the target actor existed and had an admin role before mutating `admin_transaction_capability_states` and recording an audit event using the untrusted performer actor ID.

Impact:
An unauthenticated HTTP client with a normal session/CSRF token could enable or disable admin transaction capability for a known or guessed admin actor ID and forge audit attribution. This is a high-severity authorization bypass on a security-control surface, with audit-integrity impact. It is not treated as critical because the path does not by itself grant an authenticated admin session or direct arbitrary financial transaction execution.

Attack path:
Unauthenticated HTTP client with own session/CSRF token -> main Laravel web route -> included identity-access route file -> POST capability toggle endpoint with only `web` middleware -> FormRequest authorizes caller and accepts actor IDs from request body -> controller passes client-supplied IDs -> use case checks only target-is-admin -> capability state and audit record are mutated.

Affected files:
- `routes/web.php`
- `routes/web/identity_access.php`
- `app/Adapters/In/Http/Requests/IdentityAccess/EnableAdminTransactionCapabilityRequest.php`
- `app/Adapters/In/Http/Requests/IdentityAccess/DisableAdminTransactionCapabilityRequest.php`
- `app/Adapters/In/Http/Controllers/IdentityAccess/EnableAdminTransactionCapabilityController.php`
- `app/Adapters/In/Http/Controllers/IdentityAccess/DisableAdminTransactionCapabilityController.php`
- `app/Application/IdentityAccess/UseCases/EnableAdminTransactionCapabilityHandler.php`
- `app/Application/IdentityAccess/UseCases/DisableAdminTransactionCapabilityHandler.php`

Required fix:
- Protect both toggle routes with authentication and admin/owner authorization middleware.
- Do not accept `performed_by_actor_id` from request input.
- Derive performer identity from the authenticated session.
- Preserve audit integrity by recording only server-derived performer identity.
- Keep use-case mutation behind an explicit capability-management authorization boundary.

Patch status from report:
A patch was reported under commit `eed085b` with route middleware changed to `['web', 'auth', 'admin.page']`, request validation adjusted to require an authenticated user, and controllers changed to derive performer identity from `$request->user()->getAuthIdentifier()`.

Verification gap:
This session has not independently verified the local repository diff or runtime behavior. Treat patch status as report-derived until `git status --short`, `git diff`, and relevant tests are provided.

## Update 2026-05-09 - Local verification closure after source contradiction

Current local source contradicted the previous `Patched, with verification gap` document status.

Source before this closure still showed the vulnerable behavior:

- `routes/web/identity_access.php`
  - both admin transaction capability toggle routes were still inside `Route::middleware('web')`;
  - `auth` and `admin.page` were not applied.
- `app/Adapters/In/Http/Requests/IdentityAccess/EnableAdminTransactionCapabilityRequest.php`
  - request validation still accepted `performed_by_actor_id`.
- `app/Adapters/In/Http/Requests/IdentityAccess/DisableAdminTransactionCapabilityRequest.php`
  - request validation still accepted `performed_by_actor_id`.
- `app/Adapters/In/Http/Controllers/IdentityAccess/EnableAdminTransactionCapabilityController.php`
  - controller still passed client-supplied `performed_by_actor_id` into the use case.
- `app/Adapters/In/Http/Controllers/IdentityAccess/DisableAdminTransactionCapabilityController.php`
  - controller still passed client-supplied `performed_by_actor_id` into the use case.
- Existing feature tests still proved the unsafe behavior by posting without authentication and expecting success.

Therefore #016 was not only a verification-gap item. The privileged capability toggle endpoint was still vulnerable locally before this closure.

RED proof before patch:

`php artisan test tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php`

Result:

- 6 failed
- 14 assertions

Failure shape:

- guest enable expected 401 but received 200;
- kasir enable expected rejection but received 200;
- admin enable audit expected authenticated actor but received `spoofed-actor`;
- guest disable expected 401 but received 200;
- kasir disable expected rejection but received 200;
- admin disable audit expected authenticated actor but received `spoofed-actor`.

Production patch:

- `routes/web/identity_access.php`
  - admin transaction capability enable/disable routes now use `['web', 'auth', 'admin.page']`.
- `app/Adapters/In/Http/Requests/IdentityAccess/EnableAdminTransactionCapabilityRequest.php`
  - removed client-controlled `performed_by_actor_id` from validation.
- `app/Adapters/In/Http/Requests/IdentityAccess/DisableAdminTransactionCapabilityRequest.php`
  - removed client-controlled `performed_by_actor_id` from validation.
- `app/Adapters/In/Http/Controllers/IdentityAccess/EnableAdminTransactionCapabilityController.php`
  - derives performer from `$request->user()->getAuthIdentifier()`.
- `app/Adapters/In/Http/Controllers/IdentityAccess/DisableAdminTransactionCapabilityController.php`
  - derives performer from `$request->user()->getAuthIdentifier()`.

Test files updated:

- `tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php`
- `tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php`

Route proof after patch:

`php artisan route:list --path=identity-access -v`

Both routes showed:

- `web`
- `auth`
- `admin.page`

Targeted GREEN proof:

`php artisan test tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php`

Result:

- 6 passed
- 26 assertions

Covered behavior:

- guest cannot enable admin transaction capability;
- guest cannot disable admin transaction capability;
- kasir cannot enable admin transaction capability and is redirected by the existing `admin.page` middleware contract;
- kasir cannot disable admin transaction capability and is redirected by the existing `admin.page` middleware contract;
- admin can enable capability;
- admin can disable capability;
- spoofed `performed_by_actor_id` payload is ignored;
- audit context uses the authenticated admin actor ID.

Focused/blast-radius proof:

`php artisan test tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php tests/Feature/Auth/WebPageAccessFeatureTest.php tests/Feature/Auth/WebAuthenticationFeatureTest.php`

Result:

- 21 passed
- 89 assertions

Source anchor proof:

- `routes/web/identity_access.php` uses `Route::middleware(['web', 'auth', 'admin.page'])`.
- enable/disable request validation contains `target_actor_id` only.
- enable/disable controllers derive `$performedByActorId` from `$request->user()->getAuthIdentifier()`.
- route exposure search found the toggle controllers only in `routes/web/identity_access.php`.

UI/Blade decision:

No UI/Blade file was changed for #016. The closure is route/controller/request/test hardening for a privileged identity-access mutation endpoint.

Native JS decision:

No native JavaScript file was changed for #016.

Security decision:

The security boundary is server-side route middleware plus server-derived audit performer identity. CSRF/session alone is not treated as authentication. The route now requires an authenticated admin-page actor before the capability state can be changed.

Audit/log/redaction decision:

The patch restores audit integrity by deriving `performed_by_actor_id` from the authenticated session instead of client input. No new logging surface was added, and no sensitive value is introduced into logs by this closure.

Residual gaps:

- Full global suite was not run in this #016 closure session.
- Browser/manual QA was not run.
- Owner-only approval workflow for capability changes remains out of scope.
- Actor ID enumeration hardening remains out of scope.
- Existing audit log cleanup for any historical spoofed entries remains out of scope.
- Seeder credential issue #002 remains separate future scope.
- #020 and #027 still require their own capability enforcement checkpoint/closure.

Verification status:

Fixed with proof for unauthenticated/admin capability toggle authorization and audit performer integrity, with residual global/browser/governance gaps explicitly recorded.
