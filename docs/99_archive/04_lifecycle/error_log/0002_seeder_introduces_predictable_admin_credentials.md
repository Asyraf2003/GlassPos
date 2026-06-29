# 002 - Seeder introduces predictable admin credentials

## Status

Fixed for ADR-0023 UserSeeder predictable credential boundary.

Residual deployment and staging bootstrap gaps remain open.

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

Ini bukan root cause yang sama dengan #002.

- #002 is about predictable seeded admin credentials.
- #016 is about unauthenticated admin transaction capability toggle endpoints and client-spoofed performed_by_actor_id.

Kedua temuan memengaruhi risiko admin/identity access, tetapi #002 adalah risiko credential/bootstrap sedangkan #016 adalah risiko route authorization dan audit integrity.

## Update - Predictable seeded admin credentials introduced

Laporan ini diklasifikasikan sebagai update #002, bukan file error-log baru.

## Update Status

Patched.

## Summary

Vulnerability seeded-admin credential yang sama dilaporkan lagi dengan bukti tambahan.

`UserSeeder` previously used `updateOrCreate` for `admin@gmail.com` and set the password to:

`Hash::make('12345678')`

Karena `updateOrCreate` mengupdate row existing, menjalankan ulang seeder dapat mereset password admin existing kembali ke nilai publik yang predictable.

Jalur seeding default juga memberi akun seeded akses privileged:

- admin role through `actor_accesses`
- active admin cashier-area access
- active admin transaction capability

Jika seeder dijalankan di production atau environment deployed yang penting, ini dapat membuat atau mereset akun admin highly privileged dengan credential yang diketahui.

## Additional Evidence

Reported files:

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/UserSeeder.php`

Laporan mengonfirmasi bahwa `DatabaseSeeder` memanggil `UserSeeder`, dan `UserSeeder` menetapkan role/capability privileged ke akun admin seeded.

## Patch Variant

The reported patch changes admin seeding from:

`updateOrCreate`

to:

`firstOrCreate`

and replaces the hardcoded admin password:

`12345678`

with a generated UUID-derived value before hashing.

Ini mencegah seeder yang dijalankan ulang mereset password admin existing ke default yang predictable.

## Verification

Reported successful checks:

- `php -l database/seeders/UserSeeder.php`
- `git diff -- database/seeders/UserSeeder.php`
- `git commit -m "Fix seeded admin default credential overwrite"`

## Residual Deployment Check

Proof repository mengonfirmasi vulnerability dan patch pada level source.

Deployment proof is still required to determine real-world exposure:

- whether production or staging ever ran `DatabaseSeeder` / `UserSeeder`
- whether `admin@gmail.com` exists in any deployed database
- whether the account is active
- whether the account password was reset after seeding
- whether login throttling, MFA, or account disablement exists outside the inspected source

Tidak ada kenaikan progress karena ini root cause yang sama dan target file yang sama dengan #002.

## Related report: Seeder now resets admin credentials to a known password

Klasifikasi: update existing #002, bukan file error-log unik baru.

Severity: High.

Introduced commit: fbfabf9.

Patch report commit: e3a685e.

Ringkasan:
Masalah default seeded credential sudah ada sebelumnya, tetapi laporan ini mendokumentasikan perilaku credential reset berulang yang lebih buruk. `UserSeeder` memakai `updateOrCreate()` untuk `admin@gmail.com` dan `kasir@gmail.com`, sehingga menjalankan ulang seeder menimpa password akun existing dengan nilai hardcoded `12345678`.

Karena `DatabaseSeeder` memanggil `UserSeeder` secara default, operator yang menjalankan `php artisan db:seed` untuk setup biasa, deployment, atau seed data lain dapat tanpa sadar mereset credential akun privileged. Seeder yang sama juga melakukan upsert state role/access untuk user seeded, termasuk role admin, role cashier, dan akses admin cashier-area.

Impact:
After a production-like seed run, an unauthenticated attacker who knows or guesses the seeded email/password pair could authenticate through the normal login flow as admin or cashier. This creates high-impact account takeover risk for POS/back-office data and workflow integrity.

Attack path:
Operator runs `DatabaseSeeder` or `UserSeeder` against a production-like database -> `UserSeeder` resets existing seeded account password hashes to known value -> role/access rows are upserted -> public login accepts known credentials -> authenticated admin/cashier session is created -> protected application areas become reachable through normal role/capability middleware.

Affected files:
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/UserSeeder.php`

Controls present:
- Login uses Laravel web/session authentication.
- Admin/cashier routes still require authenticated role/capability middleware.
- The attacker cannot trigger the seeder directly through the reviewed HTTP surface.

Controls missing:
- No production-environment guard around default credential seeding.
- No separation between local/dev seeders and production-safe seeders.
- No prevention of default seeded credentials in production-like databases before the reported patch.
- No observed login throttling in the report context.

Status patch dari laporan:
Patch dilaporkan pada commit `e3a685e` yang mengubah kedua pemanggilan pembuatan seeded user dari `updateOrCreate()` menjadi `firstOrCreate()`. Ini mempertahankan behavior pembuatan awal seed sambil mencegah seed run berulang menimpa password admin/cashier existing.

Residual risk:
Patch mencegah credential reset berulang untuk akun existing, tetapi pembuatan awal known default credentials tetap menjadi risiko operasional jika local/dev seeders dijalankan terhadap environment production-like. Workflow seed production harus menghindari hardcoded privileged credentials sepenuhnya, atau mewajibkan guard non-production yang eksplisit.

Gap verifikasi:
Sesi ini belum memverifikasi diff repository lokal atau behavior runtime secara independen. Perlakukan status patch sebagai berbasis laporan sampai `git status --short`, `git diff`, dan output test relevan disediakan.

## Update - ADR-0023 UserSeeder credential boundary closure

Date: 2026-05-11.

Status: Fixed and locally verified for the minimum ADR-0023 `UserSeeder` predictable credential environment boundary.

This update supersedes the earlier partial patch state for the environment-boundary portion of this finding. The current fix does not claim full clean seeder migration, deployed database safety, staging bootstrap completion, or production credential rotation.

Runtime files changed in the implementation slice:

- `database/seeders/UserSeeder.php`
- `tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php`

Docs files changed in the closure slice:

- `docs/04_lifecycle/error_log/0002_seeder_introduces_predictable_admin_credentials.md`
- `docs/03_blueprints/seeder/2026-05-11-legacy-seeder-manifest.md`

Runtime behavior now documented for this slice:

- `local` environment allows predictable local convenience users.
- `testing` environment allows predictable local convenience users.
- `staging` environment is blocked before seeded users, roles, and capability state are created.
- unknown/custom environment is blocked before seeded users, roles, and capability state are created.
- guard failure message is: `Predictable seeded users are only allowed in local/testing environments.`

Source behavior after patch:

- `UserSeeder::run()` fails closed before user creation when the application environment is not `local` or `testing`.
- local/testing still seed `admin@gmail.com` and `kasir@gmail.com` with local password `12345678`.
- predictable seeded credentials remain explicitly scoped to disposable local/testing convenience usage.
- staging and unknown environments must not use this hardcoded credential path.

RED proof before patch:

- command: `php artisan test tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php`
- result: 2 failed, 2 passed, 6 assertions.
- failure meaning: staging and unknown/custom environments still created predictable seeded users before the guard.

Syntax proof after patch:

- `php -l database/seeders/UserSeeder.php` passed.
- `php -l tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php` passed.

Targeted GREEN proof:

- command: `php artisan test tests/Feature/Seeder/UserSeederCredentialBoundaryFeatureTest.php`
- result: 4 passed, 12 assertions.

Blast-radius GREEN proof:

- command group: `tests/Feature/Auth`, `tests/Feature/IdentityAccess`, `tests/Unit/Adapters/In/Http/Middleware/IdentityAccess`, `tests/Unit/Application/IdentityAccess`
- result: 34 passed, 136 assertions.

Closure snapshot proof:

- local branch: `main`
- local HEAD: `28b27745`
- remote alignment: local `main`, `origin/main`, and `origin/HEAD` point to `28b27745`
- working tree before docs closure was clean
- handoff file exists at `docs/99_archive/handoff/seeder/2026-05-11-userseeder-credential-boundary-handoff.md`

Residual gaps:

- staging bootstrap is not implemented.
- full `make verify` was not run for this closure.
- production/staging deployed database credential rotation is not proven.
- old seeded credentials require manual rotation if an old seeder ever ran in a non-local database.
- current legacy seeder migration is not complete.
- current default seeded local/testing credential path is still legacy compatibility surface.
- `UserSeeder` is not reclassified as clean final seeder contract.
- `DatabaseSeeder`, `SeedLevel1Seeder`, `SeedLevel2Seeder`, and `SeedLevel3Seeder` are not renamed or refactored by this slice.

Do not claim:

- full clean seeder migration complete.
- staging bootstrap done.
- production database safe.
- all legacy seeders clean.
- full DoD complete.
- full `make verify` green.

Conclusion:

Error log #002 is fixed for the minimum ADR-0023 `UserSeeder` predictable seeded credential environment boundary. The remaining work is a separate staging/production bootstrap and clean seeder migration path, not a blocker for closing this specific source/test boundary.
