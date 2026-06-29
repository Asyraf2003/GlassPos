# 0036 - Supplier invoice payment proof Web/PWA audit findings

Status: Fixed / Finding A-C Fixed / True Concurrency Characterization Gap Remains
Keparahan: Medium
Klasifikasi: procurement payment proof / attachment hardening / UI contract mismatch / idempotency test gap

## Boundary Decision

HyperPOS current runtime untuk audit ini adalah Web/PWA.

Dasar boundary:

- `docs/04_lifecycle/error_log/0035_mobile_api_retired_pwa_runtime_boundary.md` menetapkan HyperPOS tidak lagi memakai Mobile API v1 sebagai runtime target.
- Audit baru wajib memperlakukan Blade/web route, middleware web, controller web, browser JavaScript, form submit, storage, projection, audit log, dan response redirect/session sebagai jalur utama.
- `routes/api.php`, `/api/v1/*`, Mobile API auth/payment proof, dan Kotlin Android adalah retired runtime dan tidak boleh dihidupkan ulang tanpa keputusan owner baru.

Evidence:

- `docs/04_lifecycle/error_log/0035_mobile_api_retired_pwa_runtime_boundary.md:9`
- `docs/04_lifecycle/error_log/0035_mobile_api_retired_pwa_runtime_boundary.md:11`
- `docs/04_lifecycle/error_log/0035_mobile_api_retired_pwa_runtime_boundary.md:29`
- `docs/04_lifecycle/error_log/0035_mobile_api_retired_pwa_runtime_boundary.md:31`
- Command audit awal membuktikan `routes/api.php` absent dengan output `OK: routes/api.php absent`.

## Active Scope

Audit jalur pembayaran faktur supplier / supplier invoice payment proof auto-lunas di jalur Web/PWA.

Target owner:

- UI Bayar = kirim bukti bayar.
- Tidak ada konsep cicil di UI utama.
- Tidak ada input nominal bayar di UI utama jika flow owner adalah auto-lunas.
- Setelah bukti bayar dikirim, sistem mencatat pembayaran sebesar outstanding/sisa tagihan dan invoice menjadi lunas.
- Legacy/manual/partial payment boleh ada sebagai histori/internal compatibility, tetapi tidak boleh muncul sebagai konsep utama di UI Bayar kecuali masih dipakai oleh flow lain dan dibuktikan.

## Finding A - Attachment storage path validation defense-in-depth

Status: Fixed

Severity: Medium

Type: Attachment path validation / defense-in-depth

Finding:

Attachment serving membaca `storage_path` dari database lalu mengambil file dari disk `local` tanpa whitelist prefix atau normalisasi path eksplisit pada read path. Upload normal memang menghasilkan path random di prefix `supplier-payment-proofs/{paymentId}/`, tetapi serving path masih bergantung pada nilai database.

Evidence:

- Attachment reader mengambil `storage_path` langsung dari tabel `supplier_payment_proof_attachments`: `app/Adapters/Out/Procurement/DatabaseSupplierPaymentProofAttachmentReaderAdapter.php:16`
- Handler membaca file memakai `$attachment->storagePath()`: `app/Application/Procurement/UseCases/GetSupplierPaymentProofAttachmentFileHandler.php:27`
- Storage adapter `get()` memakai path yang diterima untuk `Storage::disk('local')->get($path)`: `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php:73`
- Upload-generated directory diprefix `supplier-payment-proofs/`: `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php:84`
- Filename upload dirandomisasi dengan `bin2hex(random_bytes(16))`: `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php:97`
- Disk `local` memakai root private storage: `config/filesystems.php:33`

Impact:

Normal upload path relatif aman karena path dibuat server-side dan filename random. Namun jika ada DB corruption, seed/test utility yang salah, bug internal lain, atau future write path yang tidak memakai storage adapter, attachment endpoint dapat mencoba membaca path yang tidak berada di namespace bukti pembayaran supplier.

Patch recommendation:

- Tambahkan validasi path sebelum `exists()` dan `get()`.
- Allow hanya path relatif yang dimulai dengan `supplier-payment-proofs/`.
- Reject path kosong, absolute-like path, backslash, NUL byte, dan segmen `..`.
- Untuk invalid path, kembalikan `null` sehingga controller tetap menghasilkan `404`.

Test recommendation:

- Seed attachment dengan `storage_path` invalid seperti `../private.txt`, `/absolute/path`, `supplier-payment-proofs/../x.pdf`, dan path kosong.
- Assert route `admin.procurement.supplier-payment-proof-attachments.show` menghasilkan `404`.
- Pastikan valid path existing tetap bisa inline/download.

Fixed evidence:

- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php` sekarang memiliki `DIRECTORY_PREFIX = 'supplier-payment-proofs/'`.
- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php` sekarang memiliki `isValidPath()` guard.
- `exists()` hanya mengecek disk jika path valid.
- `get()` memakai `exists()`, sehingga hanya membaca path valid.
- Guard menolak path kosong, path di luar prefix, `..`, backslash, NUL byte, leading slash, skema/protokol seperti `file://` atau `http://`, dan path absolute-like.
- `app/Adapters/Out/Procurement/DatabaseSupplierPaymentProofAttachmentReaderAdapter.php` mengembalikan `null` untuk attachment row dengan invalid `storage_path`, sehingga controller menghasilkan `404` seperti file tidak ditemukan.
- Test baru `test_admin_gets_404_when_supplier_payment_proof_attachment_storage_path_is_tampered` mencakup outside-prefix, traversal, leading slash, backslash, protocol URL, absolute-like path, empty path, dan NUL byte.

Fixed test proof:

- `php artisan test --filter=ServeSupplierPaymentProofAttachmentFeatureTest`
  - PASS
  - `4 passed (21 assertions)`
- `php artisan test --filter=ExtremeProcurementAdminGuardAndAttachmentFailureMatrixFeatureTest`
  - PASS
  - `5 passed (9 assertions)`
- `php artisan test --filter=UploadSupplierInvoicePaymentProofFeatureTest`
  - PASS
  - `4 passed (44 assertions)`

## Finding B - UI/backend contract mismatch for file format and max size

Status: Fixed

Severity: Medium

Type: UI/backend contract mismatch

Finding:

Kontrak format file dan ukuran maksimal tidak konsisten antara UI utama, halaman payment proofs, backend validation, dan safe MIME handling storage/serving.

Evidence:

- Index modal accept mencantumkan JPG/JPEG/PNG/WEBP/HEIC/HEIF/PDF dan MIME eksplisit: `resources/views/admin/procurement/supplier_invoices/index.blade.php:232`
- Payment proofs page accept mencantumkan `.jpg,.jpeg,.png,.pdf,image/*,application/pdf`: `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php:53`
- Payment proofs page help text menulis maksimal 3 file, format JPG/JPEG/PNG/PDF, maksimal 2 MB per file: `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php:57`
- Backend invoice-level upload menerima `image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf`: `app/Adapters/In/Http/Controllers/Admin/Procurement/UploadSupplierInvoicePaymentProofController.php:25`
- Backend invoice-level upload memakai `max:10240` dan pesan 10 MB: `app/Adapters/In/Http/Controllers/Admin/Procurement/UploadSupplierInvoicePaymentProofController.php:26`
- Storage safe MIME detector hanya mempertahankan `application/pdf`, `image/jpeg`, dan `image/png`, selain itu menjadi `application/octet-stream`: `app/Adapters/Out/Procurement/SupplierPaymentProofMimeTypeDetector.php:12`
- Attachment inline MIME whitelist juga hanya PDF/JPEG/PNG: `app/Adapters/In/Http/Controllers/Admin/Procurement/ServeSupplierPaymentProofAttachmentController.php:18`

Impact:

User bisa melihat UI yang menolak atau tidak menyebut format yang sebenarnya diterima backend, dan bisa melihat batas ukuran 2 MB padahal backend menerima 10 MB. Sebaliknya, `image/*` di halaman payment proofs bisa memberi sinyal bahwa semua image type diterima, padahal backend hanya menerima daftar MIME tertentu. Untuk WEBP/HEIC/HEIF, backend upload menerima file, tetapi storage/serving safe MIME dapat menurunkannya menjadi binary generic.

Patch recommendation:

- Owner perlu lock kontrak final format dan size terlebih dahulu.
- Setelah kontrak terkunci, samakan:
  - accept attribute index modal;
  - accept attribute payment proofs page;
  - help text;
  - backend validation MIME;
  - backend max size;
  - storage safe MIME detector;
  - attachment inline/download behavior.

Test recommendation:

- Tambahkan test Blade/UI contract yang membuktikan dua form payment proof menampilkan format dan max size yang sama dengan backend.
- Tambahkan test upload untuk setiap format yang owner setujui.
- Tambahkan test rejected format untuk format di luar kontrak.

Fixed evidence:

- Owner locked final upload contract:
  - max file count `3`;
  - max size `10 MB` per file;
  - allowed formats `JPG/JPEG`, `PNG`, `WEBP`, `HEIC`, `HEIF`, `PDF`.
- `resources/views/admin/procurement/supplier_invoices/index.blade.php` now uses explicit accept contract:
  - `.jpg,.jpeg,.png,.webp,.heic,.heif,.pdf,image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf`.
- `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php` now uses the same explicit accept contract and no longer uses free `image/*`.
- Both UI upload forms now show the same help text:
  - `Maksimal 3 file. Format: JPG, JPEG, PNG, WEBP. Maksimal 10 MB per file.`
- `UploadSupplierInvoicePaymentProofController` already remained the backend source of truth with MIME validation `image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf` and max size `10240` KB.
- `SupplierPaymentProofMimeTypeDetector` now keeps server-detected `image/webp`, `image/heic`, and `image/heif` as safe MIME values instead of downgrading all non-PDF/JPEG/PNG files to `application/octet-stream`.
- `ServeSupplierPaymentProofAttachmentController` now treats PDF/JPEG/PNG/WEBP as safe inline MIME types, keeps HEIC/HEIF as safe MIME types, and serves unsupported inline-preview formats as attachment while preserving `X-Content-Type-Options: nosniff`.

Fixed test proof:

- `php artisan test --filter=ProcurementInvoiceIndexPageFeatureTest`
  - PASS
  - `3 passed (24 assertions)`
- `php artisan test --filter=ProcurementInvoicePaymentProofPageFeatureTest`
  - PASS
  - `5 passed (56 assertions)`
- `php artisan test --filter=ServeSupplierPaymentProofAttachmentFeatureTest`
  - PASS
  - `5 passed (25 assertions)`
- `php artisan test --filter=SupplierPaymentProofFileStorageAdapterFeatureTest`
  - PASS
  - `2 passed (10 assertions)`
- `php artisan test --filter=UploadSupplierInvoicePaymentProofFeatureTest`
  - PASS
  - `5 passed (58 assertions)`

## Finding C - UX/idempotency/concurrency test gap

Status: Fixed for UX guard and duplicate sequential POST; true concurrent two-tab characterization remains open

Severity: Low

Type: UX/idempotency/concurrency test gap

Finding:

Server-side sudah memiliki guard penting: invoice dibaca dengan `lockForUpdate`, outstanding dihitung ulang pada submit, dan invoice yang sudah lunas ditolak. Namun UI belum terlihat men-disable submit button pada payment form, dan belum ada bukti test khusus untuk double click atau concurrent submit dua tab pada route invoice-level payment proof.

Evidence:

- JS membuat action modal dan membuka payment modal berdasarkan row payload: `public/assets/static/js/pages/admin-procurement-invoices-table.js:362`
- JS mengisi action URL dari row invoice id: `public/assets/static/js/pages/admin-procurement-invoices-table.js:300`
- JS hanya memiliki submit guard untuk void form, bukan payment proof form: `public/assets/static/js/pages/admin-procurement-invoices-table.js:587`
- Preflight mengambil invoice dengan `getByIdForUpdate`: `app/Application/Procurement/Services/SupplierPaymentProof/SupplierInvoicePaymentProofPreflight.php:23`
- DB adapter menerapkan `lockForUpdate()`: `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceReaderAdapter.php:51`
- Outstanding dihitung ulang di preflight: `app/Application/Procurement/Services/SupplierPaymentProof/SupplierInvoicePaymentProofPreflight.php:37`
- Already-paid ditolak ketika outstanding kurang dari 1: `app/Application/Procurement/Services/SupplierPaymentProof/SupplierInvoicePaymentProofPreflight.php:40`
- Existing happy-path dan legacy partial tests ada: `tests/Feature/Procurement/UploadSupplierInvoicePaymentProofFeatureTest.php:20` dan `tests/Feature/Procurement/UploadSupplierInvoicePaymentProofFeatureTest.php:103`
- Belum ditemukan test khusus duplicate submit / concurrent submit untuk invoice-level payment proof route pada audit ini.

Impact:

Kemungkinan overpay secara domain terlihat ditekan oleh lock dan outstanding preflight. Namun UX double-click masih bisa menghasilkan second submit/failure yang membingungkan, dan behavior concurrent submit belum dikunci oleh regression test.

Patch recommendation:

- Tambahkan UI guard ringan: disable submit button pada submit pertama dan pertahankan native form behavior.
- Jangan jadikan UI guard sebagai satu-satunya kontrol; server lock tetap source of truth.
- Tambahkan duplicate/concurrency test untuk membuktikan hanya satu payment efektif yang tercatat.

Test recommendation:

- Test dua POST berurutan cepat ke route invoice-level payment proof: satu sukses, berikutnya gagal already-paid, total payment tidak melebihi grand total.
- Jika runtime test mendukung concurrency, tambahkan concurrent characterization test dua worker terhadap invoice yang sama.
- Assert projection tetap `paid`, outstanding `0`, dan payment sum tepat sebesar grand total.

Fixed evidence:

- `resources/views/admin/procurement/supplier_invoices/index.blade.php` now gives the modal payment proof submit button a stable `id="procurement-payment-submit"` and `data-submitting-label="Mengirim..."`.
- `public/assets/static/js/pages/admin-procurement-invoices-table.js` now listens to the payment proof form submit event, checks native browser validity, disables the submit button only for valid submits, and changes the text to `Mengirim...`.
- `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php` now gives the direct payment proof page form and submit button stable IDs and applies the same valid-submit disable behavior.
- The guard does not change server-side source of truth. Server-side preflight still recalculates outstanding under lock and rejects already-paid invoices.
- Duplicate sequential POST test was added in `UploadSupplierInvoicePaymentProofFeatureTest`: first upload records the outstanding and paid projection; second upload to the same invoice is rejected and does not create a second payment.

Fixed test proof:

- `php artisan test --filter=UploadSupplierInvoicePaymentProofFeatureTest`
  - PASS
  - `5 passed (58 assertions)`
- `php artisan test --filter=ProcurementInvoiceIndexPageFeatureTest`
  - PASS
  - `3 passed (24 assertions)`
- `php artisan test --filter=ProcurementInvoicePaymentProofPageFeatureTest`
  - PASS
  - `5 passed (56 assertions)`

Remaining gap:

- True concurrent two-tab request characterization is not yet covered by a stable concurrency test. The server-side lock and already-paid preflight remain the source-of-truth controls, but a forked/two-worker characterization test is deferred to avoid introducing flaky DB-runtime behavior in this patch.

## Safe Patch Order

1. Finding A fixed: attachment path whitelist/normalization + invalid path test.
2. Finding B fixed: owner-locked file contract aligned across UI/backend/storage/serving.
3. Finding C fixed for UX guard and duplicate sequential POST; true concurrent two-tab characterization remains deferred.

## Non-Goals

- Tidak menghidupkan kembali Mobile API.
- Tidak membuat `routes/api.php`.
- Tidak membuat `/api/v1` endpoint.
- Tidak menyentuh Kotlin/Android.
- Tidak menghapus histori audit lama hanya karena menyebut Mobile API.
- Tidak mengubah source app dalam log ini.

## Current Status

Finding A sudah fixed.

Finding B sudah fixed.

Finding C sudah fixed untuk UX guard dan duplicate sequential POST.

Remaining gap:

- True concurrent two-tab characterization test belum ditambahkan.
