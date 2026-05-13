CATATAN HANDOFF — KASIR BENGKEL — PENUTUP STEP 3a

## Metadata
- Tanggal: 2026-03-10
- Nama slice / topik: Step 3a — Identity & Access minimal slice
- Workflow step: Step 3a of Step 3
- Status: SELESAI untuk Step 3a (minimal slice)
- Progres: 100% untuk Step 3a
- Progres terhadap Step 3 induk: belum final

## Target halaman kerja
Menyelesaikan minimal slice Step 3 Identity & Access: 

- implementasi minimum Step 3 ada
- test minimum Step 3 pass
- verifikasi Step 3 pass
- halaman ini bukan penutup penuh Step 3 induk
- halaman ini hanya menutup Step 3a minimal slice

## Referensi yang dipakai `[REF]`

### Dokumen
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- DoD:
  - `docs/dod/dod_v1.md`
- ADR:
  - `docs/adr/0007-admin-transaction-entry-behind-capability-policy.md`

### Handoff sebelumnya
- Tidak ada handoff formal yang dijadikan source of truth utama saat membuka halaman ini.
- Jika perlu jejak fase sebelumnya, file yang tersedia di repo adalah:
  - `docs/handoff/handoff_step_2.md`

### Snapshot repo / output command yang dipakai
- tree / inspeksi area:
  - `tree app`
  - `tree tests`
  - `tree bootstrap app/Http routes`
- file yang diinspeksi:
  - `app/Ports/Out/CapabilityPolicyPort.php`
  - `app/Ports/Out/AuditLogPort.php`
  - `app/Adapters/Out/Policy/NullCapabilityPolicyAdapter.php`
  - `app/Adapters/Out/Audit/NullAuditLogAdapter.php`
  - `app/Providers/HexagonalServiceProvider.php`
  - `app/Application/Shared/DTO/Result.php`
  - `app/Adapters/In/Http/Presenters/JsonPresenter.php`
  - `tests/TestCase.php`
  - `phpunit.xml`
  - `bootstrap/app.php`
  - `Makefile`
- output command verifikasi:
  - `php artisan test tests/Unit`
  - `php artisan test tests/Arch`

## Fakta terkunci `[FACT]`

- Step aktif yang benar untuk fase ini adalah Step 3 — Identity & Access minimal, bukan Product Master.
- Role aktif v1 yang dipakai saat ini hanya `admin` dan `kasir`.
- `kasir` boleh input transaksi.
- `admin` tidak otomatis boleh input transaksi.
- `admin` hanya boleh input transaksi bila capability/policy transaksi aktif.
- Perubahan capability admin transaksi harus diaudit.
- Penggunaan capability transaksi oleh admin harus dapat ditelusuri / diaudit.
- `app/Models/User.php`, migration user bawaan Laravel, dan seeder bawaan Laravel bukan source of truth domain Step 3.
- Struktur implementasi Step 3 diarahkan ke hexagonal di folder `app/`.
- `app/Application/Shared/DTO/Result.php` existing dipakai sebagai contract hasil application.
- Success/error response dikontrol lewat satu area presenter response.
- `php artisan test tests/Unit` pass dengan hasil: 27 tests, 74 assertions.
- `php artisan test tests/Arch` pass dengan hasil: 1 test, 2 assertions.

## Scope yang dipakai

### `[SCOPE-IN]`
- kontrak minimum actor / role / capability / audit
- policy keputusan akses input transaksi
- use case enable / disable capability admin transaksi
- response shape untuk UI
- middleware pre-check transaksi
- unit test minimum
- verifikasi boundary hexagonal

### `[SCOPE-OUT]`
- Product Master
- Supplier Invoice
- payment / refund / laporan
- multi-role aktif
- multi-policy aktif
- trust score aktif
- persistence / storage final untuk actor / capability / audit
- controller / request / route operasional final untuk kelola capability
- migration final actor / role / capability / audit

## Keputusan yang dikunci `[DECISION]`

- Actor v1 memakai satu role aktif per actor.
- Role v1 hanya `admin` dan `kasir`.
- Role dipisah sebagai konsep eksplisit, tidak disebar sebagai string liar.
- Capability admin transaksi dipisah dari role dan dimodelkan sebagai state per actor.
- `TransactionEntryPolicy` menjadi pengambil keputusan final akses transaksi.
- Middleware hanya delegator / pre-check HTTP, bukan pengambil keputusan final.
- `AuditLogPort` existing direuse.
- `CapabilityPolicyPort` existing tidak dijadikan pusat Step 3.
- Ditambah port khusus Step 3:
  - `ActorAccessReaderPort`
  - `AdminTransactionCapabilityStatePort`
- `Result.php` tetap dipakai di application.
- Success/error response dikonsolidasikan di presenter response folder.
- `TransactionEntryPolicy` dipertahankan final.
- Test middleware disusun memakai policy asli + fake ports, bukan subclass atas policy final.
- Area infra / persistence / entrypoint yang belum punya fakta final ditetapkan sebagai `DEFER`, bukan dipaksa diisi sekarang.

## File yang dibuat / diubah `[FILES]`

### File baru
- `app/Core/IdentityAccess/Role/Role.php`
- `app/Core/IdentityAccess/Actor/ActorAccess.php`
- `app/Core/IdentityAccess/Capability/AdminTransactionCapabilityState.php`
- `app/Core/IdentityAccess/Score/TransactionEntryScore.php`
- `app/Ports/Out/IdentityAccess/ActorAccessReaderPort.php`
- `app/Ports/Out/IdentityAccess/AdminTransactionCapabilityStatePort.php`
- `app/Application/IdentityAccess/Policies/TransactionEntryPolicy.php`
- `app/Application/IdentityAccess/UseCases/EnableAdminTransactionCapabilityHandler.php`
- `app/Application/IdentityAccess/UseCases/DisableAdminTransactionCapabilityHandler.php`
- `app/Adapters/Out/IdentityAccess/NullActorAccessReaderAdapter.php`
- `app/Adapters/Out/IdentityAccess/NullAdminTransactionCapabilityStateAdapter.php`
- `app/Adapters/In/Http/Presenters/Response/JsonResultResponder.php`
- `app/Adapters/In/Http/Middleware/IdentityAccess/EnsureTransactionEntryAllowed.php`
- `tests/Unit/Application/IdentityAccess/Policies/TransactionEntryPolicyTest.php`
- `tests/Unit/Application/IdentityAccess/UseCases/EnableAdminTransactionCapabilityHandlerTest.php`
- `tests/Unit/Application/IdentityAccess/UseCases/DisableAdminTransactionCapabilityHandlerTest.php`
- `tests/Unit/Adapters/In/Http/Presenters/Response/JsonResultResponderTest.php`
- `tests/Unit/Adapters/In/Http/Presenters/JsonPresenterTest.php`
- `tests/Unit/Adapters/In/Http/Middleware/IdentityAccess/EnsureTransactionEntryAllowedTest.php`

### File diubah
- `app/Adapters/In/Http/Presenters/JsonPresenter.php`
- `app/Providers/HexagonalServiceProvider.php`
- `bootstrap/app.php`

## Bukti verifikasi `[PROOF]`

- command:
  - `php artisan test tests/Unit/Adapters/In/Http/Presenters`
  - hasil: PASS, 4 tests lulus
- command:
  - `php artisan test tests/Unit`
  - hasil: PASS, 27 tests lulus, 74 assertions
- command:
  - `php artisan test tests/Arch`
  - hasil: PASS, `Tests\Arch\HexagonalDependencyTest` lulus, 1 test, 2 assertions

## Blocker aktif `[BLOCKER]`
- tidak ada blocker aktif untuk Step 3a
- masih ada sisa scope Step 3 induk yang belum dikerjakan dan sudah diklasifikasikan sebagai area defer untuk Step 3b

## State repo yang penting untuk langkah berikutnya

- Minimal slice Step 3 sudah hidup di:
  - `app/Core/IdentityAccess`
  - `app/Application/IdentityAccess`
  - `app/Ports/Out/IdentityAccess`
  - adapter HTTP / response terkait
- `bootstrap/app.php` adalah titik registrasi middleware pada repo ini; `app/Http/Kernel.php` tidak ada.
- `TransactionEntryPolicy` tetap final dan menjadi source of truth keputusan akses transaksi.
- `JsonResultResponder` adalah titik kontrol response success/error yang dipakai bersama `Result`.
- Port `ActorAccessReaderPort` dan `AdminTransactionCapabilityStatePort` saat ini masih dibind ke null adapter baseline.
- Area berikut belum diisi dan tetap resmi ditunda:
  - adapter actor access nyata
  - adapter capability state nyata
  - audit adapter nyata
  - aturan siapa yang boleh enable / disable capability
  - registrasi middleware lanjutan di luar yang sudah dibutuhkan minimal slice
  - controller / request / route operasional capability
  - migration actor / role / capability / audit

## Next step paling aman `[NEXT]`

- Jangan buka ulang Step 3a kecuali ada:
  - bug nyata pada implementasi Step 3, atau
  - kebutuhan baru yang menyentuh area `DEFER`.
- Buka halaman kerja baru untuk Step 3b — operasionalisasi Identity & Access agar sisa output Workflow Induk Step 3 bisa ditutup tanpa mengubah Workflow Induk.
- Step 4 tetap tertutup sampai Step 3 induk selesai penuh.

## Catatan masuk halaman berikutnya

Saat membuka halaman kerja berikutnya, bawa minimal:
- file handoff ini
- `docs/setting_control/first_in.md`
- `docs/setting_control/ai_contract.md`
- referensi docs yang relevan saja
- snapshot file / output terbaru bila diperlukan

## Ringkasan singkat siap tempel

### Ringkasan
- target: selesaikan Step 3a minimal slice Identity & Access
- status: selesai
- progres: 100% untuk Step 3a
- hasil utama:
  - policy akses transaksi admin/kasir sudah dibangun
  - response success/error sudah dikonsolidasikan
  - middleware pre-check transaksi sudah ada
  - unit test minimum pass
  - arch test pass
- next step: buka Step 3b di halaman baru untuk menutup sisa Step 3 induk

### Jangan dibuka ulang
- keputusan role aktif v1 hanya `admin` dan `kasir`
- keputusan bahwa `admin` butuh capability aktif untuk input transaksi
- keputusan bahwa `TransactionEntryPolicy` adalah pengambil keputusan final
- keputusan bahwa `Result + response presenter` menjadi pola response Step 3
- area `DEFER` jangan dipaksa diisi tanpa fakta / file konkret

### Data minimum bila ingin lanjut
- handoff ini
- referensi Step 3b yang relevan
- snapshot repo terbaru hanya bila Step 4 menyentuh area yang perlu inspeksi baru
