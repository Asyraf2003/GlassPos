docs/handoff/handoff_auth_ui_access_slice_2026-03-18.md

# Handoff — Auth UI & Access Slice

## Metadata
- Tanggal: 2026-03-18
- Nama slice / topik: Auth UI, logout, page access policy, admin cashier-area access
- Workflow step: Cross-step hardening on top of Identity & Access baseline
- Status: CLOSED
- Progres:
  - Slice auth/UI halaman ini: 100%
  - Hardening verify/test halaman ini: 100%
  - Kesiapan handoff: 100%

---

## Target halaman kerja
Menutup slice auth/UI web yang sebelumnya belum hidup penuh, dengan target spesifik:

- login web hidup
- logout web hidup
- halaman admin hanya bisa diakses admin
- halaman kasir bisa diakses kasir, dan admin hanya bila capability cashier-area aktif
- register publik tidak dipakai
- layout app/auth bersih dari service resolution di Blade
- alerts/flash punya partial khusus
- route contract lama yang sempat hilang dipulihkan agar test lama tidak 404
- seluruh perubahan lolos `make verify`

---

## Referensi yang dipakai `[REF]`
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- ADR:
  - `docs/adr/0007-admin-transaction-entry-behind-capability-policy.md`
- AI contract:
  - `docs/setting_control/ai_contract.md`
- Handoff sebelumnya:
  - tidak ada handoff khusus auth/UI web sebelum halaman ini
  - konteks Identity & Access sebelumnya diturunkan dari Step 3 dan enforcement cleanup
- Snapshot repo / output command yang dipakai:
  - `routes/web.php`
  - `routes/web/*.php`
  - `bootstrap/app.php`
  - `app/Providers/HexagonalServiceProvider.php`
  - controller/request/middleware/policy/auth files
  - layout/sidebar/topbar/auth views
  - `make verify`
  - `php artisan route:list`
  - feature tests auth/access yang baru ditambahkan

---

## Fakta terkunci `[FACT]`

- authentication web resmi memakai `users` + session Laravel
- authorization resmi tetap membaca `actor_accesses`, bukan `users.role`
- `users` tidak memiliki kolom `role`, sehingga akses tidak boleh diputuskan dari `auth()->user()->role`
- login web sebelumnya hanya mock UI dan bypass ke dashboard lewat anchor link
- route contract sempat terpotong sehingga endpoint lama `health`, `identity-access`, `notes`, `product-catalog`, dan `procurement` menjadi `404`
- route contract sudah dipulihkan kembali dan test lama tidak lagi gagal karena `404`
- logout web sudah hidup
- admin page access dan cashier-area access sekarang dibedakan
- admin masuk area kasir memakai capability terpisah: `admin_cashier_area_access`
- capability transaksi admin lama (`admin_transaction_entry`) tidak direuse untuk akses area kasir
- route JSON bisnis tetap dijaga `transaction.entry`, bukan middleware page access web
- presentational boundary sudah dirapikan:
  - service resolution keluar dari Blade
  - context shell dibagikan lewat middleware
  - alerts/flash dipusatkan di partial khusus
- regression test auth/access web sudah ditambahkan
- `make verify` terakhir lulus

---

## Scope yang dipakai

### `[SCOPE-IN]`
- login page web
- login attempt web
- logout web
- proteksi halaman admin
- proteksi halaman kasir
- admin akses area kasir dengan capability eksplisit
- cleanup layout app/auth
- alerts partial
- restore route contract lama yang hilang
- hardening test auth/access web
- verifikasi final lewat `make verify`

### `[SCOPE-OUT]`
- browser/UI automation test (Dusk/Playwright/Cypress)
- redesign domain Identity & Access
- perluasan capability selain cashier-area access
- refactor besar provider agar <100 LOC
- pembuatan ADR baru pada halaman ini
- revisi workflow induk / blueprint induk

---

## Keputusan yang dikunci `[DECISION]`

- auth web resmi tetap memakai session Laravel pada adapter/web layer
- source of truth authorization tetap `actor_accesses`
- admin page access dan cashier-area access dipisahkan secara policy
- admin boleh masuk area kasir hanya bila capability `admin_cashier_area_access` aktif
- capability transaksi admin lama tetap khusus transaksi dan tidak dipakai ulang untuk area kasir
- layout app tidak boleh membaca role dari `User`
- Blade tidak boleh resolve port/service langsung untuk shell data
- data shell layout dibagikan melalui middleware `ShareAppShellData`
- alerts/validation errors dipusatkan ke partial `resources/views/layouts/partials/alerts.blade.php`
- route contract lama dipulihkan melalui file route terpisah:
  - `health`
  - `identity_access`
  - `note`
  - `product_catalog`
  - `procurement`
- health endpoint aplikasi dipulihkan ke `/health` tanpa menghapus `/up` bawaan bootstrap
- untuk dokumentasi formal, rekomendasi setelah handoff ini adalah mempertimbangkan ADR baru terpisah untuk kebijakan akses area kasir, bukan mengubah ADR-0007

---

## File yang dibuat/diubah `[FILES]`

### File baru
- `app/Adapters/In/Http/Controllers/Auth/AuthenticateController.php`
- `app/Adapters/In/Http/Controllers/Auth/LogoutController.php`
- `app/Adapters/In/Http/Requests/Auth/LoginRequest.php`
- `app/Core/IdentityAccess/Capability/AdminCashierAreaAccessState.php`
- `app/Ports/Out/IdentityAccess/AdminCashierAreaAccessStatePort.php`
- `app/Adapters/Out/IdentityAccess/DatabaseAdminCashierAreaAccessStateAdapter.php`
- `app/Application/IdentityAccess/Policies/AdminPageAccessPolicy.php`
- `app/Application/IdentityAccess/Policies/CashierAreaAccessPolicy.php`
- `app/Adapters/In/Http/Middleware/IdentityAccess/EnsureAdminPageAccess.php`
- `app/Adapters/In/Http/Middleware/IdentityAccess/EnsureCashierAreaAccess.php`
- `app/Adapters/In/Http/Middleware/IdentityAccess/ShareAppShellData.php`
- `database/migrations/2026_03_18_000100_create_admin_cashier_area_access_states_table.php`
- `routes/web/health.php`
- `routes/web/identity_access.php`
- `routes/web/note.php`
- `routes/web/product_catalog.php`
- `routes/web/procurement.php`
- `resources/views/layouts/partials/alerts.blade.php`
- `tests/Feature/Auth/WebAuthenticationFeatureTest.php`
- `tests/Feature/Auth/WebPageAccessFeatureTest.php`

### File diubah
- `routes/web.php`
- `routes/web/auth.php`
- `routes/web/dashboard.php`
- `bootstrap/app.php`
- `app/Providers/HexagonalServiceProvider.php`
- `app/Adapters/In/Http/Controllers/Auth/LoginPageController.php`
- `database/seeders/UserSeeder.php`
- `resources/views/auth/login.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/auth.blade.php`
- `resources/views/layouts/partials/topbar.blade.php`
- `resources/views/layouts/partials/sidebar-admin.blade.php`
- `resources/views/layouts/partials/sidebar-cashier.blade.php`
- `resources/views/layouts/partials/footer.blade.php`
- `resources/views/admin/dashboard/index.blade.php`
- `resources/views/cashier/dashboard/index.blade.php`

---

## Bukti verifikasi `[PROOF]`

- command:
  - `php artisan route:list | grep -E 'login|logout|admin/dashboard|cashier/dashboard'`
  - hasil:
    - route login GET/POST hidup
    - route logout hidup
    - route admin dashboard hidup
    - route cashier dashboard hidup

- command:
  - `php artisan route:list | grep -E 'health|identity-access|notes/create|product-catalog|procurement'`
  - hasil:
    - route health dan seluruh route JSON bisnis lama yang sebelumnya `404` kembali terdaftar

- command:
  - `php -l` terhadap file auth/controller/request/middleware/policy/route/test yang baru
  - hasil:
    - syntax check lolos

- command:
  - uji manual login admin
  - hasil:
    - admin berhasil login dan redirect ke `/admin/dashboard`

- command:
  - uji manual login kasir
  - hasil:
    - kasir berhasil login dan redirect ke `/cashier/dashboard`

- command:
  - uji manual invalid login
  - hasil:
    - tetap di login dan error tampil

- command:
  - uji manual logout
  - hasil:
    - logout berhasil dan kembali ke login

- command:
  - uji manual admin masuk area kasir
  - hasil:
    - admin bisa masuk area kasir bila capability aktif
    - link switch muncul di sidebar admin

- command:
  - uji manual kasir akses admin page
  - hasil:
    - kasir ditolak dari halaman admin

- command:
  - `php artisan test tests/Feature/Auth/WebAuthenticationFeatureTest.php`
  - hasil:
    - lulus

- command:
  - `php artisan test tests/Feature/Auth/WebPageAccessFeatureTest.php`
  - hasil:
    - lulus

- command:
  - `make verify`
  - hasil:
    - lulus setelah:
      - perbaikan pemanggilan `Auth::attempt()` / `Auth::logout()` untuk PHPStan
      - penghapusan assertion test yang memakai `auth()->id()`
      - pemulihan route contract lama yang hilang

---

## Sinkronisasi ke dokumen `[DOC-CHECK]`

### Selaras dengan `workflow_v1.md`
- Step 3 tetap tidak dilanggar:
  - role `admin` / `kasir` tetap fondasi aktif
  - `TransactionEntryPolicy` tetap hidup untuk input transaksi
  - capability transaksi admin tetap khusus transaksi
- slice auth/UI ini adalah hardening web layer di atas fondasi tersebut, bukan pembukaan ulang keputusan Step 3

### Selaras dengan `blueprint_v1.md`
- authentication Laravel tetap berada di adapter/web layer
- otorisasi sensitif tetap tidak diletakkan liar di UI
- policy sensitif tetap dipusatkan
- tidak ada perpindahan source of truth authorization ke model UI/framework

### Hubungan dengan `ADR-0007`
- ADR-0007 tetap utuh dan tetap khusus `admin transaction entry behind capability policy`
- keputusan baru tentang `admin_cashier_area_access` tidak dimasukkan ke ADR-0007 agar scope ADR-0007 tidak kabur
- bila ingin dibakukan formal, lebih sehat membuat ADR baru terpisah setelah handoff ini

### Selaras dengan `ai_contract.md`
- kerja dilakukan berbasis bukti
- perbaikan dilakukan satu langkah aktif per fase
- keputusan ditelusurkan ke dokumen dan repo
- handoff disusun sebelum menutup halaman kerja

---

## Blocker aktif

- tidak ada blocker teknis aktif untuk slice auth/UI ini
- keputusan yang masih terbuka hanya level dokumentasi arsitektur:
  - apakah `admin_cashier_area_access` perlu dibakukan sebagai ADR baru atau cukup hidup dulu di handoff

---

## Next step paling aman `[NEXT]`

1. buat ADR baru terpisah bila project owner ingin mengunci keputusan:
   - admin masuk area kasir harus lewat capability `admin_cashier_area_access`
   - capability area kasir dipisah dari capability transaksi
2. bila ADR baru belum ingin dibuat sekarang, lanjutkan halaman kerja berikutnya dengan membawa handoff ini sebagai referensi resmi
3. optional hardening berikutnya bila dibutuhkan nanti:
   - browser/UI automation test
   - refactor lebih lanjut shell/menu bila menu bertambah banyak
   - penempatan enable/disable `admin_cashier_area_access` ke jalur operasional admin yang resmi

---

## Ringkasan penutupan
Halaman kerja ini berhasil menutup gap auth/UI web yang sebelumnya belum hidup dan sempat menimbulkan regresi route `404`. Slice auth/access sekarang sudah:

- operasional
- teruji
- lolos verify
- selaras dengan workflow/blueprint yang ada

Sehingga halaman berikutnya dapat langsung memakai handoff ini sebagai baseline resmi untuk auth/UI dan access web.