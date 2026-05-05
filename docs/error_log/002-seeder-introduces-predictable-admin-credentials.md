# 002 - Seeder introduces predictable admin credentials

## Status

Patched.

## Severity

High.

## Source

Audit report #002: Seeder introduces predictable admin credentials.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 001-refunds-counted-as-paid-in-note-totals.md

### Jenis Keterkaitan

Tidak langsung.

### Alasan

Laporan #001 dan #002 sama-sama berasal dari audit keamanan HyperPOS dan sama-sama memiliki severity High, tetapi root cause, file terdampak, dan dampak teknisnya berbeda.

- #001 membahas financial-integrity bug pada kalkulasi allocated/refunded amount.
- #002 membahas authentication/admin takeover risk akibat seeded predictable admin credential.

Karena tidak memakai file yang sama, tidak berada pada flow domain yang sama, dan tidak memperbaiki bug yang sama, laporan #002 harus dicatat sebagai file baru, bukan update dari #001.

## Update Log

### Update 1

Patch pertama untuk laporan ini.

Alasan update:

- Vulnerability masih ada di HEAD pada database/seeders/UserSeeder.php.
- Admin seeded user memakai password predictable 12345678.
- Seeder memakai updateOrCreate, sehingga reseed dapat mereset password admin existing menjadi password default yang diketahui.
- Patch mengganti behavior agar admin existing tidak di-reset dan password admin baru dibuat high-entropy.

## Ringkasan Indonesia

Bug terjadi pada database seeder untuk user admin.

File terdampak:

database/seeders/UserSeeder.php

Sebelum patch, seeder membuat atau memperbarui user:

- email: admin@gmail.com
- password: 12345678

Masalah utamanya bukan hanya password default yang lemah, tapi juga penggunaan updateOrCreate. Karena updateOrCreate akan memperbarui row yang sudah ada, menjalankan ulang seeder dapat mereset password admin existing kembali ke 12345678.

Akun admin tersebut juga diberi role ADMIN dan capability admin transaction aktif. Jika seeder pernah dijalankan pada environment non-disposable atau database deployment yang bisa diakses lewat login publik, attacker yang mengetahui credential default dapat login sebagai admin.

## Jalur Risiko

Public login route /login menerima email dan password.

Jika database sudah memiliki seeded user:

- admin@gmail.com
- 12345678

maka attacker dapat mencoba login menggunakan credential tersebut.

Setelah login berhasil:

- Auth::attempt membuat authenticated session.
- Actor role dibaca dari actor_accesses.
- Seeded admin memiliki role ADMIN.
- Admin capability state aktif.
- Admin route dan transaction capability dapat diakses sesuai policy/middleware.

Ini menjadi admin account takeover jika seeded credential tersebut ada di database yang reachable.

## Dampak Bisnis

Dampak utama:

- remote unauthenticated attacker bisa menjadi authenticated admin jika seeded user aktif
- akses admin dapat membuka fungsi sensitif POS/back-office
- risiko manipulasi transaksi
- risiko manipulasi inventory
- risiko akses payroll/supplier/reporting
- risiko audit trail dan operational integrity

Severity High tepat karena dampaknya besar, tetapi tidak otomatis Critical dari bukti statis saja karena eksploitasi bergantung pada precondition operasional:

- seeder pernah dijalankan pada deployed/non-disposable database
- aplikasi reachable via HTTP/HTTPS
- seeded admin user masih aktif
- credential default diketahui attacker

## Root Cause

Seeder mencampur kebutuhan dev/test bootstrap dengan risiko credential production.

Root cause teknis:

1. Admin password hardcoded memakai nilai umum: 12345678.
2. updateOrCreate menyebabkan reseed dapat mereset credential admin existing.
3. Seeded admin langsung diberi role dan capability privileged.
4. Default seeding path reachable dari DatabaseSeeder -> SeedLevel2Seeder -> UserSeeder.

## Patch Summary

Patch minimal diterapkan pada:

database/seeders/UserSeeder.php

Perubahan:

- admin seeding diganti dari updateOrCreate menjadi firstOrCreate
- existing admin credential tidak lagi di-reset saat reseed
- hardcoded password 12345678 untuk admin diganti menjadi Hash::make(Str::random(40))
- import Illuminate\Support\Str ditambahkan

Efek patch:

- admin baru tetap bisa dibuat oleh seeder
- password admin baru tidak predictable
- admin existing tidak di-overwrite oleh reseed
- risk default admin credential/backdoor berkurang secara langsung

## Scope In

- Admin seeded credential.
- database/seeders/UserSeeder.php.
- Prevention of admin password reset on reseed.
- Removal of predictable seeded admin password.

## Scope Out

- Kasir seeded credential.
- Secure production bootstrap flow.
- Login throttling/lockout.
- Deployment/environment proof.
- Full artisan db:seed execution.
- Browser login E2E test.
- Rotation of any already-seeded admin credential in existing databases.

## Catatan Residual Risk

Patch ini memperbaiki seeded admin credential risk, tetapi dari potongan diff masih terlihat kasir seeding memakai:

- email: kasir@gmail.com
- password: 12345678
- updateOrCreate

Itu jangan dicampur ke laporan ini kecuali ada audit finding khusus. Namun secara security hygiene, ini layak diaudit sebagai temuan terpisah jika akun kasir reachable dan punya transaction-entry capability.

Catatan lain:

Jika database production atau staging pernah terlanjur menjalankan seeder lama, patch source code saja belum otomatis mengganti password admin yang sudah ada. Perlu credential rotation/manual reset pada environment terdampak.

## Proof Dari Patch Session

User reported these commands passed/executed:

- php -l database/seeders/UserSeeder.php
- git status --short
- git commit -m "Fix seeded admin default credential vulnerability"

Commit message:

Fix seeded admin default credential vulnerability

Changed file:

database/seeders/UserSeeder.php

Reported diff size:

+3
-2

Reported code change:

- User::query()->updateOrCreate(...) for admin changed to User::query()->firstOrCreate(...)
- Hash::make('12345678') for admin changed to Hash::make(Str::random(40))
- Illuminate\Support\Str import added

## Recommended Follow-up

Recommended next audit/test step:

1. Add a regression test proving rerunning UserSeeder does not reset an existing admin password.
2. Add a regression/static test proving admin seeded password is not a literal predictable value.
3. Audit kasir seeded credential separately.
4. Document production bootstrap policy: seeders must not create known privileged credentials in operational databases.
5. If any deployed database may have run the old seeder, rotate admin@gmail.com credential immediately.

## Kesimpulan

Laporan #002 valid sebagai High severity source-level security issue.

Bug ini menciptakan risiko default admin credential karena UserSeeder menulis admin@gmail.com dengan password predictable 12345678 dan memakai updateOrCreate, sehingga reseed bisa mereset admin existing ke credential publik. Patch minimal sudah tepat untuk root cause langsung: firstOrCreate mencegah reset credential existing, dan Str::random(40) menghapus predictable admin password untuk admin baru.

Namun patch source code tidak otomatis membersihkan database yang sudah pernah terkena seeder lama. Jika ada environment non-disposable yang pernah menjalankan seeder lama, password admin harus dirotasi manual.

## Related Identity Access Finding From Error Log 016

### Related Error Log

- 016-unauthenticated-admin-capability-toggle-endpoints.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity identity/access issue.

This is not the same root cause as #002.

- #002 is about predictable seeded admin credentials.
- #016 is about unauthenticated admin transaction capability toggle endpoints and client-spoofed performed_by_actor_id.

Both findings affect admin/identity access risk, but #002 is credential/bootstrap risk while #016 is route authorization and audit integrity risk.

## Update - Predictable seeded admin credentials introduced

This report is classified as an update to #002, not a new error-log file.

## Update Status

Patched.

## Summary

The same seeded-admin credential vulnerability was reported again with additional evidence.

`UserSeeder` previously used `updateOrCreate` for `admin@gmail.com` and set the password to:

`Hash::make('12345678')`

Because `updateOrCreate` updates an existing row, rerunning the seeder could reset an existing admin password back to the predictable public value.

The default seeding path also grants the seeded account privileged access:

- admin role through `actor_accesses`
- active admin cashier-area access
- active admin transaction capability

If the seeder is run in production or an important deployed environment, this can create or reset a highly privileged admin account with known credentials.

## Additional Evidence

Reported files:

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/UserSeeder.php`

The report confirms that `DatabaseSeeder` calls `UserSeeder`, and `UserSeeder` assigns privileged role/capability state to the seeded admin account.

## Patch Variant

The reported patch changes admin seeding from:

`updateOrCreate`

to:

`firstOrCreate`

and replaces the hardcoded admin password:

`12345678`

with a generated UUID-derived value before hashing.

This prevents rerunning the seeder from resetting an existing admin password to a predictable default.

## Verification

Reported successful checks:

- `php -l database/seeders/UserSeeder.php`
- `git diff -- database/seeders/UserSeeder.php`
- `git commit -m "Fix seeded admin default credential overwrite"`

## Residual Deployment Check

Repository proof confirms the source-level vulnerability and patch.

Deployment proof is still required to determine real-world exposure:

- whether production or staging ever ran `DatabaseSeeder` / `UserSeeder`
- whether `admin@gmail.com` exists in any deployed database
- whether the account is active
- whether the account password was reset after seeding
- whether login throttling, MFA, or account disablement exists outside the inspected source

No progress increase because this is the same root cause and same target file as #002.
