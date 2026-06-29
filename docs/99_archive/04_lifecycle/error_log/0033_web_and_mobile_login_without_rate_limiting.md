# 033 - Web login rate limiting; obsolete mobile API note retired

Status: Strict Fixed
Keparahan: Medium
Klasifikasi: authentication hardening / brute-force resistance

## Current Runtime Note

HyperPOS current target adalah PWA/web-only. `routes/api.php`, `/api/v1/*`, `mobile-login`, dan Kotlin Android companion app tidak boleh diperlakukan sebagai runtime target aktif untuk sesi audit baru. Penyebutan mobile API di dokumen audit lama hanya boleh dibaca sebagai histori/superseded context.

## Ringkasan

Web login sebelumnya tidak memiliki throttle middleware atau rate limiter eksplisit. Catatan lama tentang mobile API sudah obsolete karena HyperPOS sekarang memakai PWA/web dan tidak mendaftarkan routes/api.php.

Patch sekarang mempertahankan named rate limiter `web-login` untuk `POST /login`.

Limiter memakai batas `5` request per menit dengan key `email ternormalisasi + IP address`.

## Strict-Fixed-Scope

Scope yang ditutup:

- web login route `POST /login`;
- repeated invalid login attempts dari email dan IP yang sama;
- response `429` setelah limit terlampaui;
- existing safe invalid-credential responses tetap dipertahankan.

Out of scope untuk log ini:

- MFA;
- captcha;
- IP reputation;
- account lockout persisten di database;
- device fingerprinting;
- distributed credential stuffing dari banyak IP.

## Root Cause

Route web auth sebelumnya hanya memakai middleware `web` dan `app.shell`.

Catatan mobile API pada dokumen ini adalah arsip lama dan tidak lagi menjadi runtime target.

Controller login web dan mobile sudah mengembalikan pesan invalid credential yang aman, tetapi tidak ada request counter atau `429` proof untuk repeated attempts.

## Source Reality Setelah Patch

`app/Providers/IdentityAccessServiceProvider.php`

- mendefinisikan `LOGIN_MAX_ATTEMPTS_PER_MINUTE = 5`;
- register `RateLimiter::for('web-login', ...)`;
- key limiter dibuat dari `mb_strtolower(trim(email))` dan `$request->ip()`;
- jika email kosong, key memakai fallback `missing-email`;
- jika IP tidak tersedia, key memakai fallback `unknown-ip`.

`routes/web/auth.php`

- `POST /login` sekarang memakai middleware `throttle:web-login`.

`routes/api.php` sudah tidak menjadi runtime route target untuk HyperPOS PWA/web.

- Catatan `POST /api/v1/auth/login` adalah histori lama; route API mobile sudah retired dari target runtime PWA/web.

`tests/Feature/Auth/WebAuthenticationFeatureTest.php`

- menambahkan regression test `test_web_login_is_rate_limited_after_repeated_invalid_attempts()`;
- 5 invalid attempts masih redirect ke login dengan error aman;
- attempt ke-6 menghasilkan `429`.

`tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php`

- menambahkan regression test `test_mobile_api_login_is_rate_limited_after_repeated_invalid_attempts()`;
- 5 invalid attempts masih menghasilkan safe payload `422`;
- attempt ke-6 menghasilkan `429`.

## Jalur Rentan Sebelum Patch

Attacker mengirim banyak request login dengan kombinasi email/password ke web login -> aplikasi memproses setiap attempt tanpa counter rate-limit yang terlihat -> attacker dapat melakukan brute force atau credential stuffing sampai dibatasi oleh kontrol eksternal.

## Dampak

- Meningkatkan peluang credential stuffing.
- Meningkatkan beban aplikasi dan password hashing workload.
- Membuat audit security auth belum lengkap meskipun pesan invalid credential sudah aman.

Keparahan Medium karena issue ini tidak membuktikan bypass autentikasi, tetapi menghilangkan kontrol dasar pada boundary login.

## RED Proof

Command:

```bash
php artisan test tests/Feature/Auth/WebAuthenticationFeatureTest.php tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php
```

Hasil saat route login belum memakai throttle middleware:

- exit code `1`;
- `2 failed, 12 passed, 65 assertions`;
- web attempt ke-6 expected `429`, actual `302`;
- mobile attempt ke-6 expected `429`, actual `422`;
- web masih mengembalikan error aman `Email atau password tidak valid.`;
- mobile masih mengembalikan safe payload `AUTH_FAILED`;
- failure membuktikan repeated attempts belum dibatasi di boundary route.

Catatan eksekusi:

- test DB sempat menolak koneksi ke `127.0.0.1:3306`;
- MariaDB lokal dinyalakan dengan `sudo systemctl start mariadb`;
- setelah DB hidup, RED/GREEN proof dapat dijalankan normal.

## Targeted GREEN Proof

Command:

```bash
php artisan test tests/Feature/Auth/WebAuthenticationFeatureTest.php tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php
```

Hasil setelah patch:

- `PASS`;
- `14 passed, 66 assertions`.

Coverage targeted:

- web admin login tetap sukses;
- web kasir login tetap sukses;
- invalid web login tetap redirect aman;
- web repeated invalid attempts menghasilkan `429` pada attempt ke-6;
- mobile admin login tetap sukses;
- mobile kasir login tetap sukses;
- invalid mobile login tetap safe payload;
- mobile repeated invalid attempts menghasilkan `429` pada attempt ke-6;
- mobile token me/logout behavior tetap hijau.

## Focused Blast-Radius Proof

Command:

```bash
php artisan test tests/Feature/Auth tests/Feature/MobileApi
```

Hasil:

- `PASS`;
- `39 passed, 151 assertions`.

## Full Verification Proof

Command:

```bash
make verify
```

Hasil:

- PHPStan `1794/1794`, `[OK] No errors`;
- line-count audit passed;
- Blade audit passed;
- contract audit passed;
- Pest `1175 passed, 6650 assertions`;
- duration `51.43s`;
- exit code `0`.

## Negative Search

Command:

```bash
rg -n "RateLimiter::for|throttle:web-login|LOGIN_MAX_ATTEMPTS_PER_MINUTE|loginRateLimiterKey" app/Providers/IdentityAccessServiceProvider.php routes/web/auth.php
```

Hasil relevan:

- `RateLimiter::for('web-login', ...)` ditemukan;
- `POST /login` memakai `throttle:web-login`;
- limiter key builder ada di provider.

## Remaining Gaps

Route-level throttle menghitung semua POST login untuk email+IP yang sama, termasuk successful login dan validation-error request.

Gap ini tidak membuka ulang status 0033 karena:

- brute-force boundary sekarang menghasilkan `429`;
- invalid credential response tetap aman;
- limiter window pendek, `5` request per menit;
- jika UX masa depan membutuhkan invalid-only counter dan clear-on-success, implementasi bisa dipindah ke manual login rate limiter di controller/service.

Belum ada kontrol untuk distributed credential stuffing dari banyak IP. Itu membutuhkan desain tambahan seperti IP reputation, account-level lockout, MFA, atau captcha, dan berada di luar scope 0033.

## Strict Closure Decision

0033 ditutup sebagai `Strict Fixed` untuk web login rate limiting. Bagian mobile API adalah histori lama dan superseded oleh keputusan PWA/web-only.

Dasar closure:

- RED membuktikan endpoint sebelumnya tidak menghasilkan `429`;
- named limiter sudah dipasang di web dan mobile login route;
- key limiter memakai email normalized + IP;
- targeted auth regression hijau;
- focused Auth + MobileApi suite hijau;
- global `make verify` hijau.
