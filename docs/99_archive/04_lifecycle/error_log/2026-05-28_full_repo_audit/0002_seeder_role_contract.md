# ERROR LOG 0002 - SEEDER ROLE CONTRACT

## FACT
- Laporan ini membahas mismatch kontrak seeder role, bukan patch seeder, bukan refactor, dan bukan klaim bug runtime yang sudah terbukti.
- `DatabaseSeeder` aktif memanggil `CreateOnly\CreateUserSeeder`, bukan `UserSeeder` legacy.
- `CreateOnly\CreateUserSeeder` menulis `actor_accesses.role = 'user'` untuk akun `kasir@gmail.com`.
- Canonical role domain hanya mengenal `admin` dan `kasir`.
- `DatabaseActorAccessReaderAdapter` membaca `actor_accesses.role` lalu memanggil `Role::fromString()`, sehingga value yang tidak canonical tidak bisa dipetakan.
- Test auth yang diperiksa membuat user dan `actor_accesses` secara manual; test tersebut tidak membuktikan bahwa `DatabaseSeeder` fresh-seed path sudah benar.

## OWNER PROOF
- Owner scan command: `rg -n "createActorAccessOnly|role' => 'user'|Role::KASIR|const KASIR" database/seeders app/Core app/Adapters/Out/IdentityAccess`
- Owner scan output membuktikan:
  - `database/seeders/UserSeeder.php` memakai `Role::KASIR`
  - `app/Core/IdentityAccess/Role/Role.php` memiliki `const KASIR = 'kasir'`
  - `database/seeders/CreateOnly/CreateUserSeeder.php` memasukkan role `'user'`
  - `CreateOnly/CreateUserSeeder` punya `createActorAccessOnly`

## SOURCE EVIDENCE
- `database/seeders/DatabaseSeeder.php:15-18` memanggil `CreateUserSeeder::class` dan `CreateMasterBasicSeeder::class`.
- `database/seeders/UserSeeder.php:24-37` membuat `admin@gmail.com` dan `kasir@gmail.com`, lalu menulis `actor_accesses` dengan `Role::ADMIN` dan `Role::KASIR`.
- `database/seeders/CreateOnly/CreateUserSeeder.php:20-34` membuat `admin@gmail.com` dan `kasir@gmail.com`, lalu memanggil `createActorAccessOnly(..., 'admin')` dan `createActorAccessOnly(..., 'user')`.
- `database/seeders/CreateOnly/CreateUserSeeder.php:65-74` memperlihatkan helper `createActorAccessOnly()` yang menulis nilai role mentah ke tabel `actor_accesses`.
- `app/Core/IdentityAccess/Role/Role.php:11-13` mendefinisikan role canonical hanya `admin` dan `kasir`.
- `app/Core/IdentityAccess/Role/Role.php:29-35` menunjukkan `Role::fromString()` hanya menerima `admin` atau `kasir`, selain itu melempar `InvalidArgumentException`.
- `app/Adapters/Out/IdentityAccess/DatabaseActorAccessReaderAdapter.php:16-28` membaca `actor_accesses.role` lalu memetakan dengan `Role::fromString()`.
- `app/Application/IdentityAccess/Services/LoginActorAccessDecision.php:20-36` menunjukkan keputusan login bergantung pada hasil pembacaan actor access dan hanya mengenali `admin` / `kasir`; nilai lain akan jatuh ke `UNSUPPORTED`.
- `tests/Feature/Auth/WebPageAccessFeatureTest.php:1-106` membuat user dan `actor_accesses` secara manual via helper test, bukan lewat `DatabaseSeeder`.
- `tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php:1-197` juga membuat user dan `actor_accesses` secara manual, bukan lewat `DatabaseSeeder`.

## FINDINGS
- CONFIRMED: ada mismatch kontrak antara seeder aktif dan canonical role domain. `DatabaseSeeder` memakai `CreateOnly\CreateUserSeeder`, dan seeder itu menanam `role = 'user'` untuk akun `kasir@gmail.com`, sementara canonical role hanya `admin` dan `kasir`.
- CONFIRMED: legacy/alternate `UserSeeder` memakai `Role::KASIR`, jadi ada dua sumber seed perilaku yang berbeda untuk akun kasir.
- CONFIRMED: `DatabaseActorAccessReaderAdapter` membaca role dari database dan memetakan lewat `Role::fromString()`, jadi `user` bukan value canonical yang diterima oleh reader.
- CONFIRMED: `LoginActorAccessDecision` hanya mengenali hasil pembacaan `admin` atau `kasir`; value lain tidak masuk jalur canonical login decision.
- GAP: belum ada proof fresh seed yang benar-benar menjalankan `DatabaseSeeder` lalu gagal pada login atau route access.
- SUSPECTED: bila `DatabaseSeeder` dijalankan pada environment fresh, akun `kasir@gmail.com` dengan role `user` akan memicu mismatch saat login / akses dashboard / mobile auth, tetapi efek runtime spesifik ini belum dibuktikan dengan seed fresh.

## IMPACT
- Dampak kontrak ada pada jalur auth dan identity access: seed aktif dapat menanam role yang tidak dapat dibaca sebagai canonical role domain.
- Karena `LoginActorAccessDecision` dan `DatabaseActorAccessReaderAdapter` mengandalkan canonical role, mismatch ini berpotensi membuat akun kasir seeded tidak dikenali sebagai `kasir`.
- Dampak runtime yang paling mungkin adalah login / akses dashboard / mobile login menjadi tidak sesuai ekspektasi bila environment benar-benar memakai `DatabaseSeeder`, tetapi itu masih perlu proof fresh seed.
- Perbedaan antara `DatabaseSeeder` aktif dan `UserSeeder` legacy/alternate membuat owner perlu menentukan mana yang dianggap contract source of truth untuk seed demo account.

## GAP
- Belum ada proof fresh seed SQLite/MySQL yang menjalankan `DatabaseSeeder` dari awal.
- Belum ada proof hasil tabel `actor_accesses` setelah fresh seed yang menunjukkan hanya `admin` dan `kasir`.
- Belum ada proof login `kasir@gmail.com` setelah fresh seed.
- Belum ada proof akses `cashier.dashboard` setelah fresh seed.
- Belum ada proof mobile login untuk akun kasir seeded dari `DatabaseSeeder`.
- Command test yang diperiksa pada turn ini memakai `RefreshDatabase` dan membuat actor access secara manual; itu bukan proof bahwa `DatabaseSeeder` contract mismatch sudah memukul runtime.

## WHY FULL SUITE GREEN DOES NOT CLOSE THIS
- Full suite green tidak otomatis menutup laporan ini karena masalahnya ada pada kontrak seed aktif versus canonical role domain.
- Test auth yang diperiksa tidak memakai `DatabaseSeeder`; test tersebut langsung membuat `actor_accesses` dengan role manual di helper test.
- Artinya suite yang hijau di jalur lain tidak membuktikan bahwa jalur seed demo account aktif sudah konsisten.
- Bahkan bila suite lain hijau, mismatch `role = 'user'` vs canonical `kasir` tetap ada di source dan tetap harus dibaca sebagai contract mismatch.

## CLASSIFICATION
- CONFIRMED contract mismatch
  - `DatabaseSeeder` aktif memanggil `CreateOnly\CreateUserSeeder`.
  - `CreateOnly\CreateUserSeeder` menanam `'user'` untuk kasir demo account.
  - Canonical role domain hanya `admin` dan `kasir`.
  - Reader/login decision memakai canonical role map.
- Runtime impact needs proof
  - efek login/route/mobile pada fresh seed belum dibuktikan.
- SUSPECTED
  - fresh seed kemungkinan besar akan membuat akun kasir demo tidak canonical, tetapi status runtime ini belum final tanpa proof.
- GAP
  - fresh seed proof, role table assertion, dan auth path proof belum tersedia.

## SOLUTION DIRECTION, NO IMPLEMENTATION
- Tetapkan satu seed path canonical untuk demo account.
- Samakan seed role dengan canonical role domain yang sudah ada.
- Jika `CreateOnly` seeder tetap dipakai, role yang ditulis harus dipetakan ke `admin` / `kasir`, bukan string non-canonical.
- Jika `UserSeeder` legacy dianggap obsolete, dokumentasikan statusnya agar tidak menjadi alternate contract yang membingungkan.
- Setelah kontrak seed dibersihkan, ulangi proof fresh seed dan proof auth path.

## SUGGESTED NEXT PROOF
- Jalankan fresh seed SQLite/MySQL dengan `DatabaseSeeder`.
- Assert isi `actor_accesses` hanya berisi role `admin` dan `kasir`.
- Login `kasir@gmail.com`.
- Akses `cashier.dashboard`.
- Coba mobile login untuk akun kasir seeded.
- Jika perlu, bandingkan hasil seeding dari `DatabaseSeeder` versus `UserSeeder` untuk memastikan mana yang canonical.

## MINIMUM OWNER COMMANDS
```bash
rg -n "class DatabaseSeeder|CreateUserSeeder|UserSeeder" database/seeders -g '*.php'
rg -n "role' => 'user'|Role::KASIR|const KASIR|fromString" database/seeders app/Core app/Adapters/Out/IdentityAccess app/Application/IdentityAccess
php artisan test --filter=MobileApiAuthenticationFeatureTest
php artisan test --filter=WebPageAccessFeatureTest
```

## FINAL STATUS
- Status: CONFIRMED contract mismatch
- Runtime impact: GAP
- Owner-facing summary: seed aktif menulis role `user` untuk demo kasir, sementara canonical domain hanya mengenal `admin` dan `kasir`. Itu adalah mismatch kontrak yang sudah terbukti dari source, tetapi dampak runtime login/route/mobile masih perlu fresh seed proof.

---

## Resolution update - Phase 5 CreateOnly seeder contract hardening

### Status

FIXED WITH EXTENDED SEED + FULL VERIFY PROOF.

### Decision

The active `DatabaseSeeder` path remains on `Database\Seeders\CreateOnly\CreateUserSeeder` and `Database\Seeders\CreateOnly\CreateMasterBasicSeeder`.

The seeder role contract was fixed in the active CreateOnly path. `kasir@gmail.com` is now seeded with canonical `Role::KASIR` / `kasir`, not the non-canonical `user` role.

The CreateOnly seeders were also consolidated behind the shared create-only support path:

- `database/seeders/CreateOnly/Support/CreateOnlySeeder.php`
- `database/seeders/CreateOnly/Support/CreateOnlyMasterSeeder.php`

The write behavior is now centralized through shared create-only helpers instead of scattered raw insert / local helper paths.

### Proof

- Static CreateOnly scan showed raw insert/update helpers only remain in the shared base or out-of-scope legacy `UserSeeder.php`.
- Fresh seed proof showed:
  - `admin@gmail.com => admin`
  - `kasir@gmail.com => kasir`
- Admin cashier area access proof showed:
  - `admin@gmail.com => active: 1`
- Idempotency proof after a second `php artisan db:seed --force` showed the demo users remained non-duplicated and roles stayed stable.
- `make seed-create-all-v3` passed after extended CreateOnly scenario seed execution:
  - supplier procurement seed created 24 invoices, 72 invoice lines, 24 receipts, and 72 receipt lines;
  - supplier payment seed created 24 payments and 12 proof attachments;
  - operational expense seed created 45 rows;
  - employee debt/payment/adjustment/payroll scenario seeders completed with planned counts.
- Targeted auth regression passed:
  - `MobileApiAuthenticationFeatureTest`: 7 passed, 25 assertions.
  - `WebPageAccessFeatureTest`: 8 passed, 20 assertions.
- Full verify passed:
  - `2 skipped, 1118 passed (6285 assertions)`.
  - Duration: `76.37s`.

### Remaining non-blocking follow-up

`ProductSeederIdempotencyFeatureTest` still has 2 explicit skipped tests because product scenario seeders remain pending restoration under `database/seeders/Product`.

This is not a blocker for `0002_seeder_role_contract.md`; it is a separate product scenario seeder restoration follow-up.

