# 028 - Perbaikan DI mengekspos content type lampiran bukti pembayaran yang tidak aman

Status: Fixed with proof
Keparahan: Medium
Klasifikasi: file error-log unik baru
Commit introduksi: c1baf1f
Status patch: fixed and locally verified for supplier payment proof attachment MIME/content-disposition hardening

## Ringkasan

Masalah keamanan menjadi dapat dieksploitasi setelah binding DI untuk `SupplierPaymentProofFileStoragePort` diperbaiki, sehingga jalur upload dan serve lampiran bukti pembayaran supplier menjadi reachable.

Refactor pada `storedFile()` dan `deleteMany()` sendiri bersifat mempertahankan perilaku. Perubahan yang relevan secara keamanan adalah binding yang benar membuat jalur yang menyimpan metadata MIME dari client menjadi aktif, lalu metadata itu dipakai lagi sebagai HTTP response `Content-Type` untuk response lampiran inline.

Controller upload memvalidasi tipe file yang diizinkan dengan rule file Laravel, tetapi sebelumnya menyimpan `UploadedFile::getClientMimeType()`. Nilai ini berasal dari metadata multipart request dan dapat dikendalikan uploader. Controller serve sebelumnya mengembalikan file tersimpan secara inline dan mengisi `Content-Type` dari nilai tersimpan tersebut.

Admin yang sudah login dapat mengupload payload yang byte-nya lolos validasi PDF/JPG/PNG, sementara multipart `Content-Type` dikirim sebagai `text/html`. Ketika admin lain membuka link lampiran bukti pembayaran, aplikasi dapat menyajikan lampiran tersebut secara inline sebagai HTML dari origin HyperPOS, sehingga stored XSS berjalan di sesi admin korban.

Nama file asli juga sebelumnya digabung langsung ke `Content-Disposition` tanpa konstruksi header yang aman.

## Kenapa ini file baru

Ini bukan masalah yang sama dengan laporan private storage/public helper exposure yang sudah ada. Storage tetap private, dan jalur eksploitnya adalah controller serve lampiran di aplikasi yang membutuhkan autentikasi.

Ini juga bukan masalah yang sama dengan temuan reflected XSS sebelumnya. Sink pada laporan ini adalah metadata lampiran tersimpan ditambah penyajian file inline dari origin aplikasi yang sama.

## File terdampak

Production:

- `app/Adapters/In/Http/Controllers/Admin/Procurement/AttachSupplierPaymentProofController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ServeSupplierPaymentProofAttachmentController.php`
- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php`

Tests:

- `tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php`
- `tests/Feature/Procurement/ExtremeSupplierPaymentProofMatrixFeatureTest.php`
- `tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php`

Related route/config surface:

- `app/Providers/HexagonalServiceProvider.php`
- `routes/web/admin_procurement.php`

## Bukti awal masalah

`HexagonalServiceProvider` melakukan binding `SupplierPaymentProofFileStoragePort` ke `LaravelSupplierPaymentProofFileStorageAdapter`, sehingga jalur storage file bukti pembayaran supplier dapat di-resolve.

`routes/web/admin_procurement.php` mengekspos route upload dan serve lampiran yang terdampak di bawah middleware `web`, `auth`, dan `admin.page`.

`AttachSupplierPaymentProofController` memvalidasi file bukti pembayaran sebagai `jpg`, `jpeg`, `png`, atau `pdf`, tetapi upload path masih mengirim metadata dari `getClientOriginalName()` dan `getClientMimeType()` ke storage boundary.

Sebelum patch, `LaravelSupplierPaymentProofFileStorageAdapter` mempertahankan `original_filename` dan `mime_type` dari metadata file yang diberikan tanpa menghitung ulang MIME di server dan tanpa allowlist.

Sebelum patch, `ServeSupplierPaymentProofAttachmentController` menyajikan lampiran secara inline secara default dan mengisi:

- `Content-Type` dari `$file->mimeType()`
- `Content-Disposition` dengan menggabungkan nama file asli ke string header

Sebelum patch, response lampiran belum menyetel `X-Content-Type-Options: nosniff`.

## Jalur serangan

Admin terautentikasi mengupload file bukti pembayaran supplier -> validasi upload menerima byte sebagai PDF/JPG/PNG yang diizinkan -> controller menyimpan metadata MIME multipart yang dikendalikan attacker -> adapter storage mempertahankan MIME tanpa perubahan -> admin korban membuka route lampiran bukti pembayaran -> controller serve mengembalikan response inline dengan `Content-Type` tersimpan -> browser dapat memperlakukan response sebagai HTML/script same-origin -> stored XSS berjalan di sesi admin korban.

## Dampak

Eksploit yang berhasil dapat menjalankan JavaScript dari origin aplikasi HyperPOS di browser admin korban. Script tersebut dapat membaca halaman admin atau response API same-origin yang tersedia untuk korban, serta mengirim request state-changing same-origin sebagai korban.

Keparahan medium karena route membutuhkan admin yang sudah login dan eksploit membutuhkan admin attacker serta interaksi dari admin korban. Laporan ini tidak menunjukkan kompromi unauthenticated, RCE, kompromi deployment luas, atau dampak cross-tenant.

## Prasyarat

- Attacker memiliki akun admin yang sudah login.
- Attacker dapat mengupload file bukti pembayaran supplier.
- Byte file upload lolos validasi PDF/JPG/PNG.
- Attacker mengendalikan multipart `Content-Type`, misalnya `text/html`.
- Admin korban membuka route lampiran tanpa `download=true`.
- Tidak ada header server eksternal yang menimpa perilaku response tidak aman ini.

## Kontrol yang sudah ada

- Route dilindungi middleware `web`, `auth`, dan `admin.page`.
- Proteksi CSRF Laravel berlaku untuk route POST web.
- Validasi upload membatasi file ke `jpg`, `jpeg`, `png`, dan `pdf`.
- Validasi upload membatasi file bukti pembayaran maksimal 3 file dan maksimal 2048 KB per file.
- File disimpan di private disk Laravel, bukan langsung di public storage.
- Cookie session Laravel diharapkan memakai HttpOnly dan SameSite sesuai default framework.

## Patch scope

Serve boundary diperbaiki di `ServeSupplierPaymentProofAttachmentController`.

Perilaku setelah patch:

- Response tidak lagi memakai MIME tersimpan/client-controlled sebagai `Content-Type`.
- Content file dibaca lebih dulu dari storage.
- MIME dideteksi ulang server-side menggunakan `finfo(FILEINFO_MIME_TYPE)->buffer($content)`.
- Inline hanya diizinkan untuk allowlist:
  - `application/pdf`
  - `image/jpeg`
  - `image/png`
- Unknown/risky MIME fallback ke `application/octet-stream`.
- Unknown/risky MIME dipaksa `Content-Disposition: attachment`.
- `download=true` tetap memaksa attachment.
- Response menyetel `X-Content-Type-Options: nosniff`.
- `Content-Disposition` dibuat memakai Symfony/Laravel header helper `makeDisposition()`, bukan string concat manual.
- Nama file output disanitasi lewat `safeFilename()` sebelum masuk `makeDisposition()`.

Storage boundary diperbaiki di `LaravelSupplierPaymentProofFileStorageAdapter`.

Perilaku setelah patch:

- Stored metadata `mime_type` tidak lagi memakai input `$file['mime_type']`.
- MIME dihitung ulang dari temporary source path menggunakan `finfo(FILEINFO_MIME_TYPE)->file($path)`.
- Stored MIME hanya mempertahankan allowlist:
  - `application/pdf`
  - `image/jpeg`
  - `image/png`
- Unknown/risky MIME disimpan sebagai `application/octet-stream`.

Test fixture lama di `ServeSupplierPaymentProofAttachmentFeatureTest` dan `ExtremeSupplierPaymentProofMatrixFeatureTest` disesuaikan agar memakai byte PDF/JPEG minimal yang valid, bukan string dummy yang secara benar terdeteksi sebagai `application/octet-stream` oleh server-side MIME detection.

## RED serve proof

Command:

`php artisan test tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php --filter='supplier_payment_proof_attachment_does_not_serve_client_controlled_html_mime_inline'`

Result before patch:

`FAIL`

Failure proof:

`Expected: text/html; charset=utf-8`

`Not to contain: text/html`

Summary:

`Tests: 1 failed (2 assertions)`

Kesimpulan: controller serve terbukti menyajikan client/stored MIME `text/html`.

## GREEN serve proof

Command:

`php artisan test tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php --filter='supplier_payment_proof_attachment_does_not_serve_client_controlled_html_mime_inline'`

Result after patch:

`PASS`

Summary:

`Tests: 1 passed (5 assertions)`

Assertion behavior after patch:

- `Content-Type` tidak mengandung `text/html`.
- `Content-Type` mengandung `application/octet-stream`.
- `X-Content-Type-Options` bernilai `nosniff`.
- `Content-Disposition` mengandung `attachment`.

## Serve file proof

Command:

`php artisan test tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php`

Result:

`PASS`

Summary:

`Tests: 3 passed (11 assertions)`

Coverage:

- Admin can preview valid supplier payment proof PDF inline.
- Admin can download valid supplier payment proof JPEG.
- Client-controlled/stored HTML MIME is not served inline.

## RED storage proof

Command:

`php artisan test tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php`

Result before patch:

`FAIL`

Failure proof:

`Failed asserting that two strings are identical.`

`-'application/pdf'`

`+'text/html'`

Summary:

`Tests: 1 failed (3 assertions)`

Kesimpulan: storage adapter terbukti masih menyimpan MIME dari input/client metadata.

## GREEN storage proof

Command:

`php artisan test tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php`

Result after patch:

`PASS`

Summary:

`Tests: 1 passed (5 assertions)`

Coverage:

- Input metadata sengaja memakai `mime_type = text/html`.
- Source file berisi byte PDF valid.
- Stored metadata menjadi `application/pdf`.
- Stored path berada di prefix `supplier-payment-proofs/payment-1/`.
- File tersimpan di disk local fake.

## Syntax proof

Commands:

`php -l app/Adapters/In/Http/Controllers/Admin/Procurement/ServeSupplierPaymentProofAttachmentController.php`

`php -l app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php`

`php -l tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php`

`php -l tests/Feature/Procurement/ExtremeSupplierPaymentProofMatrixFeatureTest.php`

`php -l tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php`

Result:

`No syntax errors detected` in all 5 files.

## Focused blast-radius proof

Command:

`php artisan test tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php tests/Feature/Procurement/ServeSupplierPaymentProofAttachmentFeatureTest.php tests/Feature/Procurement/ExtremeSupplierPaymentProofMatrixFeatureTest.php tests/Feature/Procurement/ExtremeProcurementAdminGuardAndAttachmentFailureMatrixFeatureTest.php tests/Feature/Procurement/AttachSupplierPaymentProofFeatureTest.php`

Result:

`PASS`

Summary:

`Tests: 17 passed (75 assertions)`

Coverage:

- Storage adapter server-side MIME detection regression.
- Serve response MIME/content-disposition hardening.
- Supplier payment proof preview/download matrix.
- Procurement admin guard and attachment failure matrix.
- Supplier payment proof upload flow.

## Current source verification

Current local source at HEAD `0beadefa` shows:

- `ServeSupplierPaymentProofAttachmentController` contains `INLINE_MIME_TYPES`.
- `ServeSupplierPaymentProofAttachmentController` detects safe MIME from file content.
- `ServeSupplierPaymentProofAttachmentController` sets `X-Content-Type-Options: nosniff`.
- `ServeSupplierPaymentProofAttachmentController` uses `$response->headers->makeDisposition(...)`.
- `ServeSupplierPaymentProofAttachmentController` has `safeMimeType()`, `detectMimeType()`, and `safeFilename()`.
- `LaravelSupplierPaymentProofFileStorageAdapter` contains `ALLOWED_MIME_TYPES`.
- `LaravelSupplierPaymentProofFileStorageAdapter` stores MIME from `$this->safeMimeType($sourcePath)`.
- `LaravelSupplierPaymentProofFileStorageAdapter` has `safeMimeType()` and `detectMimeType()`.
- `SupplierPaymentProofFileStorageAdapterFeatureTest` covers client-controlled `text/html` metadata being replaced by server-detected `application/pdf`.

`git status --short --untracked-files=all` returned clean before docs patch.

## Residual gaps

- Full global suite was not run for this patch.
- Browser/manual QA for inline preview/download was not run.
- Deployment/proxy-level security headers outside this controller response were not verified.
- CSP was not added or verified at repo/deployment level.
- Existing upload controller may still call `getClientMimeType()`, but storage adapter no longer trusts that value for persisted MIME metadata.
- Existing original filename is still accepted as metadata, but serve path sanitizes output filename and builds `Content-Disposition` through `makeDisposition()`.
- Commit hash for this docs closure depends on the owner/user manual commit.
