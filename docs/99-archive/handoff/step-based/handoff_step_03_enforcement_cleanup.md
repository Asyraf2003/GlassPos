# Handoff — Step 3 Enforcement & Legacy Cleanup

## Metadata
- Tanggal: 2026-03-14
- Nama slice / topik: Step 3 enforcement HTTP entrypoint + cleanup legacy HTTP/policy wiring
- Workflow step: Step 3 correction slice
- Status: CLOSED
- Progres:
  - Slice enforcement + cleanup: 100%
  - Workflow induk: tetap 50%

## Target halaman kerja
Menutup koreksi clean code yang berasal dari rekonsiliasi audit Codex pada area Step 3, dengan target spesifik:

- menghidupkan enforcement `transaction.entry` pada entrypoint HTTP transaksi aktif
- membuktikan kontrak akses:
  - unauthenticated ditolak `401`
  - admin tanpa capability aktif ditolak `403`
- menghapus dead path framework default yang tidak dipakai
- menghapus legacy policy wiring yang sudah orphan
- memastikan regression minimum tetap aman lewat syntax check + arch test + feature test

## Referensi yang dipakai `[REF]`
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- DoD:
  - `docs/dod/dod_v1.md`
- ADR:
  - `docs/adr/0007-admin-transaction-entry-behind-capability-policy.md`
- File implementasi terkait:
  - `routes/web.php`
  - `bootstrap/app.php`
  - `app/Adapters/In/Http/Middleware/IdentityAccess/EnsureTransactionEntryAllowed.php`
  - `app/Application/IdentityAccess/Policies/TransactionEntryPolicy.php`
  - `app/Application/Shared/DTO/Result.php`
  - `app/Providers/HexagonalServiceProvider.php`
  - `tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php`
- Snapshot repo / output command yang dipakai:
  - `sed -n '1,240p' routes/web.php`
  - `sed -n '1,260p' bootstrap/app.php`
  - `grep -R "transaction.entry" -n routes app bootstrap`
  - `grep -R "CapabilityPolicyPort" -n app bootstrap tests`
  - `grep -R "NullCapabilityPolicyAdapter" -n app bootstrap tests`
  - `grep -R "extends Controller" -n app tests`
  - `grep -R "use App\\\\Http\\\\Controllers\\\\Controller" -n app tests`
  - `grep -R -E "assertStatus\\((401|403)\\)|assertUnauthorized\\(|assertForbidden\\(" -n tests/Feature tests/Arch`
  - `grep -R -F "transaction.entry" -n tests/Feature tests/Arch routes app`
  - `sed -n '1,220p' app/Adapters/In/Http/Middleware/IdentityAccess/EnsureTransactionEntryAllowed.php`
  - `sed -n '1,260p' tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php`
  - `sed -n '1,260p' tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php`
  - `sed -n '1,220p' app/Models/User.php`
  - `find database/factories -maxdepth 2 -type f | sort`
  - `php artisan test tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php`
  - `php -l app/Providers/HexagonalServiceProvider.php`
  - `grep -R "CapabilityPolicyPort\|NullCapabilityPolicyAdapter" -n app tests bootstrap routes`
  - `find app/Http/Controllers -maxdepth 2 -type f | sort`
  - `php artisan test tests/Arch tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php`

## Fakta terkunci `[FACT]`
- Alias middleware `transaction.entry` sudah terdaftar di `bootstrap/app.php`.
- Sebelum koreksi, route transaksi aktif di `routes/web.php` belum memakai `transaction.entry`.
- Middleware `EnsureTransactionEntryAllowed` punya kontrak pasti:
  - request tanpa user terautentikasi mengembalikan `401`
  - actor yang gagal policy mengembalikan `403`
- `TransactionEntryPolicy` memutuskan:
  - kasir diizinkan input transaksi
  - role non-admin/non-kasir ditolak
  - admin tanpa capability aktif ditolak
  - admin dengan capability aktif diizinkan dan penggunaan capability diaudit
- Setelah koreksi, `routes/web.php` membungkus 4 route transaksi aktif dengan `Route::middleware('transaction.entry')->group(...)`.
- File test baru `tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php` berhasil membuktikan:
  - unauthenticated request ditolak `401`
  - authenticated admin tanpa capability aktif ditolak `403`
- `app/Http/Controllers/Controller.php` sebelumnya terbukti dead path:
  - tidak ada `extends Controller`
  - tidak ada `use App\Http\Controllers\Controller`
- Setelah cleanup, direktori `app/Http/Controllers` sudah tidak ada lagi pada snapshot verifikasi.
- `CapabilityPolicyPort` dan `NullCapabilityPolicyAdapter` sebelumnya hanya hidup sebagai interface/adapter/binding orphan tanpa bukti pemakaian aktif.
- Setelah cleanup, tidak ada lagi referensi `CapabilityPolicyPort` maupun `NullCapabilityPolicyAdapter` di `app`, `tests`, `bootstrap`, atau `routes`.
- `app/Providers/HexagonalServiceProvider.php` lolos syntax check setelah cleanup.
- `Tests\Arch\HexagonalDependencyTest` tetap pass setelah cleanup.
- `Tests\Feature\IdentityAccess\TransactionEntryMiddlewareFeatureTest` tetap pass setelah cleanup.

## Scope yang dipakai

### `[SCOPE-IN]`
- enforcement `transaction.entry` pada route transaksi aktif
- verifikasi kontrak middleware `401` dan `403`
- cleanup dead path controller default Laravel
- cleanup legacy `CapabilityPolicyPort` + `NullCapabilityPolicyAdapter` + binding orphan di service provider
- regression minimum pada arch test dan feature test akses

### `[SCOPE-OUT]`
- rewrite `README.md`
- audit seluruh endpoint transaksi di luar 4 route aktif yang dikoreksi
- perubahan domain Step 6–7
- perubahan behaviour use case product catalog / procurement
- cleanup lain di luar area HTTP/policy wiring legacy

## Keputusan yang dikunci `[DECISION]`
- Enforcement Step 3 di HTTP entrypoint harus hidup sebagai guard nyata, tidak cukup hanya alias middleware di bootstrap.
- Route transaksi aktif yang wajib diproteksi pada slice ini adalah:
  - `/product-catalog/products/create`
  - `/product-catalog/products/{productId}/update`
  - `/procurement/supplier-invoices/create`
  - `/procurement/supplier-invoices/{supplierInvoiceId}/receive`
- Kontrak akses yang sah untuk middleware ini adalah:
  - `401` untuk unauthenticated
  - `403` untuk admin tanpa capability aktif
- Test enforcement dipisah sebagai feature test khusus Identity Access agar concern policy tidak tersebar ke test domain lain.
- `app/Http/Controllers/Controller.php` sah dihapus karena dead path.
- `CapabilityPolicyPort`, `NullCapabilityPolicyAdapter`, dan binding-nya di `HexagonalServiceProvider` sah dihapus karena merupakan wiring transisi orphan pada snapshot repo ini.
- Slice koreksi ini ditutup 100% karena seluruh target koreksi sudah punya bukti repo dan bukti verifikasi.

## File yang dibuat/diubah `[FILES]`

### File baru
- `tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php`
- `docs/handoff/handoff_step_3_enforcement_cleanup.md`

### File diubah
- `routes/web.php`
- `app/Providers/HexagonalServiceProvider.php`

### File dihapus
- `app/Http/Controllers/Controller.php`
- `app/Ports/Out/CapabilityPolicyPort.php`
- `app/Adapters/Out/Policy/NullCapabilityPolicyAdapter.php`

## Bukti verifikasi `[PROOF]`
- command:
  - `php artisan test tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php`
  - hasil:
    - `PASS`
    - `Tests: 2 passed (16 assertions)`

- command:
  - `php -l app/Providers/HexagonalServiceProvider.php`
  - hasil:
    - `No syntax errors detected in app/Providers/HexagonalServiceProvider.php`

- command:
  - `grep -R "CapabilityPolicyPort\|NullCapabilityPolicyAdapter" -n app tests bootstrap routes`
  - hasil:
    - tidak ada output

- command:
  - `find app/Http/Controllers -maxdepth 2 -type f | sort`
  - hasil:
    - `find: ‘app/Http/Controllers’: No such file or directory`

- command:
  - `php artisan test tests/Arch tests/Feature/IdentityAccess/TransactionEntryMiddlewareFeatureTest.php`
  - hasil:
    - `PASS  Tests\Arch\HexagonalDependencyTest`
    - `PASS  Tests\Feature\IdentityAccess\TransactionEntryMiddlewareFeatureTest`
    - `Tests: 3 passed (18 assertions)`

## Dampak hasil koreksi
- Guard akses Step 3 sekarang benar-benar aktif di entrypoint HTTP transaksi aktif.
- Ada regression test minimum yang membuktikan kontrak auth/policy pada route yang diproteksi.
- Noise arsitektur turun karena jalur HTTP legacy default Laravel sudah hilang.
- Service provider lebih bersih karena wiring policy lama yang orphan sudah dihapus.
- Cleanup ini tidak merusak boundary hexagonal maupun enforcement middleware yang baru dihidupkan.

## Blocker aktif
- Tidak ada blocker aktif untuk slice koreksi ini.

## Catatan lanjutan
- Slice ini sudah layak ditutup dan dipakai sebagai pijakan untuk kerja berikutnya.
- Pekerjaan yang masih tersisa tetapi di luar scope slice ini:
  - rewrite `README.md` agar mencerminkan domain kasir bengkel
  - audit coverage endpoint transaksi lain bila nantinya route aktif bertambah
  - handoff berikutnya bisa langsung melanjutkan cleanup dokumentasi atau workflow step berikutnya tanpa membuka ulang koreksi ini
