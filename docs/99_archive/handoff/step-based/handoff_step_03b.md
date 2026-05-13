CATATAN HANDOFF — KASIR BENGKEL — PENUTUP STEP 3

## Metadata
- Tanggal: 2026-03-11
- Nama slice / topik: Step 3b — operasionalisasi Identity & Access minimal
- Workflow step: Step 3b of Step 3
- Status: SELESAI
- Progres: 100% untuk Step 3b
- Progres terhadap Step 3 induk: 100% / SELESAI

## Target halaman kerja
Menutup sisa output Step 3 Identity & Access minimal agar Step 3 induk bisa dinyatakan selesai:

- capability state punya source of truth nyata
- perubahan capability admin transaksi benar-benar tercatat
- jalur operasional minimum enable/disable capability tersedia
- verifikasi end-to-end tersedia

## Referensi yang dipakai `[REF]`

### Dokumen
- `docs/setting_control/first_in.md`
- `docs/setting_control/ai_contract.md`
- potongan workflow:
  - `Step 3 — Identity & Access minimal`
- handoff sumber utama:
  - `CATATAN HANDOFF — KASIR BENGKEL — PENUTUP STEP 3a`

### Snapshot repo / output command yang dipakai
- `tree app/Adapters/Out`
- `tree database`
- `tree app/Adapters/In/Http`
- `tree routes`
- isi file:
  - `app/Core/IdentityAccess/Actor/ActorAccess.php`
  - `app/Core/IdentityAccess/Capability/AdminTransactionCapabilityState.php`
  - `app/Core/IdentityAccess/Role/Role.php`
  - `app/Ports/Out/IdentityAccess/ActorAccessReaderPort.php`
  - `app/Ports/Out/IdentityAccess/AdminTransactionCapabilityStatePort.php`
  - `app/Ports/Out/AuditLogPort.php`
  - `app/Adapters/Out/Audit/NullAuditLogAdapter.php`
  - `app/Adapters/Out/IdentityAccess/NullActorAccessReaderAdapter.php`
  - `app/Adapters/Out/IdentityAccess/NullAdminTransactionCapabilityStateAdapter.php`
  - `app/Providers/HexagonalServiceProvider.php`
  - `bootstrap/app.php`
  - `routes/web.php`
  - `phpunit.xml`

## Fakta terkunci `[FACT]`

- Step 3a sebelumnya sudah menyelesaikan minimal slice:
  - kontrak role/actor/capability
  - `TransactionEntryPolicy`
  - middleware pre-check transaksi
  - response presenter pattern
  - unit test minimum
- Step 3 induk belum bisa ditutup dari Step 3a saja karena output:
  - `semua perubahan policy tercatat`
  
  belum terbukti secara operasional saat itu.
- Kontrak data minimum Step 3 yang dipakai di Step 3b:
  - actor access = `actor_id`, `role`
  - admin transaction capability state = `actor_id`, `active`
  - audit minimum perubahan capability = `event`, `target_actor_id`, `performed_by_actor_id`, `capability`
- `users` bawaan Laravel tetap **bukan** source of truth Step 3.
- Jalur transport tetap memakai `web.php`; tidak dipindah ke `api.php`.
- Pembuktian operasional jalur web dilakukan lewat **Feature Test**, bukan lewat perubahan besar transport layer.

## Scope yang dipakai

### `[SCOPE-IN]`
- persistence minimum untuk actor access
- persistence minimum untuk admin transaction capability state
- persistence minimum untuk audit log
- binding provider ke adapter DB nyata
- jalur HTTP minimum enable/disable capability
- verifikasi end-to-end enable/disable capability

### `[SCOPE-OUT]`
- auth final
- authorization final siapa yang boleh enable/disable capability
- multi-role aktif
- multi-policy aktif
- API split / route api
- Step 4
- domain lain di luar Identity & Access minimal

## Keputusan yang dikunci `[DECISION]`

- Step 3 tetap memakai jalur `web`, bukan diubah ke `api`.
- Pembuktian operasional untuk route web dilakukan lewat Feature Test Laravel.
- Untuk Step 3b minimum, `performed_by_actor_id` dibawa eksplisit dari request body, karena auth/authorization final enable-disable masih defer.
- Binding port Step 3 diarahkan ke adapter DB nyata:
  - `AuditLogPort` → `DatabaseAuditLogAdapter`
  - `ActorAccessReaderPort` → `DatabaseActorAccessReaderAdapter`
  - `AdminTransactionCapabilityStatePort` → `DatabaseAdminTransactionCapabilityStateAdapter`
- Step 3 induk dinyatakan selesai bila:
  - perubahan capability enable terbukti end-to-end
  - perubahan capability disable terbukti end-to-end
  - audit log terbukti tertulis

## File yang dibuat / diubah `[FILES]`

### File baru
- `database/migrations/2026_03_10_000100_create_actor_accesses_table.php`
- `database/migrations/2026_03_10_000200_create_admin_transaction_capability_states_table.php`
- `database/migrations/2026_03_10_000300_create_audit_logs_table.php`

- `app/Adapters/Out/IdentityAccess/DatabaseActorAccessReaderAdapter.php`
- `app/Adapters/Out/IdentityAccess/DatabaseAdminTransactionCapabilityStateAdapter.php`
- `app/Adapters/Out/Audit/DatabaseAuditLogAdapter.php`

- `app/Adapters/In/Http/Requests/IdentityAccess/EnableAdminTransactionCapabilityRequest.php`
- `app/Adapters/In/Http/Requests/IdentityAccess/DisableAdminTransactionCapabilityRequest.php`

- `app/Adapters/In/Http/Controllers/IdentityAccess/EnableAdminTransactionCapabilityController.php`
- `app/Adapters/In/Http/Controllers/IdentityAccess/DisableAdminTransactionCapabilityController.php`

- `tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php`
- `tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php`

- `.env.testing`

### File diubah
- `app/Providers/HexagonalServiceProvider.php`
- `routes/web.php`
- `phpunit.xml`

## Bukti verifikasi `[PROOF]`

### Struktur dan syntax
- adapter DB files ada di lokasi yang benar
- controller/request files ada di lokasi yang benar
- file baru lolos `php -l`

### Test
- `php artisan test tests/Unit`
  - PASS
  - `27 passed`, `74 assertions`

- `php artisan test tests/Arch`
  - PASS
  - `1 passed`, `2 assertions`

- `php artisan test tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php`
  - PASS
  - `1 passed`, `3 assertions`

- `php artisan test tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php`
  - PASS
  - `1 passed`, `3 assertions`

### Route
- `php artisan route:list`
  - route POST enable ada
  - route POST disable ada

### Testing environment
- `.env.testing` sudah dibuat
- jalur test dipindah dari sqlite memory ke MySQL test database
- database test:
  - `bengkelhex_test`
  - sudah ada / terbukti lewat `SHOW DATABASES LIKE 'bengkelhex_test';`

## Blocker aktif `[BLOCKER]`
- tidak ada blocker aktif untuk Step 3
- blocker SQLite / MySQL testing sudah selesai

## State repo yang penting untuk langkah berikutnya

- Step 3 sudah selesai penuh, tidak perlu dibuka ulang kecuali ada bug nyata.
- `TransactionEntryPolicy` tetap source of truth keputusan akses transaksi.
- Source of truth persistence Step 3 sekarang sudah nyata untuk:
  - actor access
  - admin transaction capability state
  - audit logs
- Entry point minimum enable/disable capability sudah ada di jalur web.
- Auth/authorization final siapa yang boleh enable/disable capability masih belum menjadi bagian penutupan Step 3 dan tetap defer.
- Jalur testing feature sudah siap memakai MySQL test DB terpisah.

## Next step paling aman `[NEXT]`

- Step 3 ditutup.
- Buka halaman kerja baru untuk step berikutnya sesuai workflow induk yang berlaku.
- Bawa minimal:
  - handoff ini
  - `docs/setting_control/first_in.md`
  - `docs/setting_control/ai_contract.md`
  - referensi workflow/blueprint step berikutnya yang relevan
  - snapshot repo tambahan hanya bila dibutuhkan oleh step berikutnya

## Ringkasan singkat siap tempel

### Ringkasan
- target: menutup Step 3b agar Step 3 induk selesai
- status: selesai
- progres: 100% untuk Step 3b, 100% untuk Step 3 induk
- hasil utama:
  - persistence minimum actor access/capability state/audit log sudah ada
  - provider binding sudah mengarah ke adapter DB nyata
  - endpoint enable/disable capability sudah ada
  - feature test enable pass
  - feature test disable pass
  - audit perubahan capability terbukti tercatat end-to-end

### Jangan dibuka ulang
- role aktif v1 hanya `admin` dan `kasir`
- `admin` butuh capability aktif untuk input transaksi
- `TransactionEntryPolicy` adalah pengambil keputusan final
- Step 3 tetap memakai jalur web, bukan diubah ke api
- auth/authorization final enable-disable capability belum menjadi scope penutupan Step 3

### Data minimum bila ingin lanjut
- handoff final Step 3 ini
- referensi step berikutnya yang relevan
- snapshot repo terbaru bila step berikutnya memang menyentuh area baru
