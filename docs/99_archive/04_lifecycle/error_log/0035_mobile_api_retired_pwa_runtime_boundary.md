# 0035 - Mobile API retired; PWA/web runtime boundary locked

Status: Strict Fixed / Boundary Locked  
Keparahan: Medium  
Klasifikasi: architecture cleanup / stale runtime context / audit scope hardening

## Ringkasan

HyperPOS tidak lagi memakai Mobile API v1 sebagai runtime target.

Arah saat ini adalah PWA/web-only. Semua audit baru harus memperlakukan Blade/web route, middleware web, controller web, JavaScript page script, form submit, storage, projection, audit log, dan response redirect/session sebagai jalur utama.

Penyebutan lama tentang Mobile API, Kotlin Android, `/api/v1/*`, `routes/api.php`, `mobile-login`, dan `mobile_api_tokens` hanya boleh dibaca sebagai histori lama atau superseded context.

## Current Runtime Boundary

Runtime aktif:

- Laravel web/PWA.
- `routes/web.php`.
- Modular web routes di `routes/web/*.php`.
- Blade views di `resources/views`.
- Browser JavaScript di `public/assets/static/js`.
- Session/CSRF/web middleware.
- Form POST/redirect/session flash response.
- Local storage adapter untuk bukti bayar supplier invoice.
- Application/domain service yang dipakai oleh jalur web.

Runtime yang retired / tidak boleh dihidupkan ulang tanpa keputusan owner baru:

- `routes/api.php`.
- `/api/v1/*`.
- Mobile API auth.
- Mobile API supplier invoice endpoint.
- Mobile API payment proof endpoint.
- Kotlin Android companion app.
- `mobile-login` throttle boundary.
- Mobile API bearer token boundary.

## Source Reality Saat Log Ini Dibuat

Local verification membuktikan:

- `routes/api.php` absent.
- Active docs/index yang mengarah ke Mobile API/Kotlin sudah dibersihkan.
- `0033_web_and_mobile_login_without_rate_limiting.md` sudah dipatch agar bagian Mobile API dibaca sebagai retired/superseded context.
- Sisa penyebutan Mobile API di audit lama/db readiness/schema parity boleh tetap ada sebagai histori, bukan runtime source of truth.

## Kenapa Ini Penting

Sebelum log ini, beberapa dokumen lama masih bisa membuat AI/Codex salah fokus ke:

- Mobile API v1.
- Kotlin Android client.
- `/api/v1/auth/login`.
- `/api/v1/supplier-invoices`.
- `/api/v1/supplier-invoices/{id}/payment-proof`.
- `mobile_api_tokens`.
- route API yang sudah tidak menjadi target runtime.

Risikonya bukan hanya dokumentasi kotor, tetapi audit berikutnya bisa salah boundary dan mengusulkan patch di jalur yang sudah retired.

## Strict-Fixed Scope

Scope yang ditutup:

- Mobile API tidak lagi menjadi target audit runtime.
- Kotlin Android tidak lagi menjadi target implementasi HyperPOS Laravel.
- Codex/AI session berikutnya harus fokus ke PWA/web route.
- Error log lama yang menyebut Mobile API harus diperlakukan sebagai histori.
- Audit keamanan berikutnya harus memulai dari jalur web input sampai output.

Out of scope:

- Menghapus semua histori lama dari full repo audit.
- Mengubah migration lama hanya karena pernah ada `mobile_api_tokens`.
- Mengubah service/domain yang masih dipakai oleh web.
- Menghapus bukti audit lama yang berguna untuk memahami sejarah keputusan.
- Menghidupkan API baru.

## Next Audit Boundary

Audit keamanan berikutnya wajib memetakan alur:

1. UI input.
2. Browser JavaScript.
3. Form action / fetch request jika ada.
4. Web route.
5. Middleware.
6. Controller.
7. Validation.
8. Application use case/service.
9. Domain rule.
10. Repository/persistence.
11. File storage jika upload.
12. Projection/sync.
13. Audit log.
14. Response redirect/session/json jika ada.
15. UI feedback.

## Target Audit Awal yang Direkomendasikan

Fokus pertama:

- Supplier invoice payment proof upload.
- Auto-lunas saat bukti bayar dikirim.
- Payment action modal di daftar supplier invoice.
- Payment proof detail page.
- Attachment serving.
- Reverse payment / void invoice interaction.
- Projection paid/outstanding.
- Audit trail.
- Double submit / race condition.

## Outlier Matrix Wajib Untuk Audit Berikutnya

Audit berikutnya wajib mencari bukti untuk kondisi berikut:

- Submit dua kali cepat / double click.
- Dua tab membuka invoice yang sama lalu submit bersamaan.
- Invoice sudah lunas sebelum submit.
- Invoice voided sebelum submit.
- Invoice berubah outstanding setelah modal dibuka.
- File kosong.
- File lebih dari 3.
- File melewati batas ukuran.
- MIME palsu.
- Ekstensi benar tapi MIME salah.
- File valid tapi storage gagal.
- Storage berhasil tapi DB gagal.
- DB berhasil tapi projection gagal.
- Audit log gagal.
- Attachment record ada tapi file hilang.
- Attachment path invalid.
- User bukan admin.
- Session expired.
- CSRF expired.
- Back button setelah success.
- Refresh setelah success.
- Old input membawa invoice id lama.
- Form action salah karena modal stale.
- UI menampilkan lunas tapi projection belum sinkron.
- UI menerima format yang backend tolak.
- UI help text tidak sama dengan backend validation.
- Nilai nominal lama/legacy masih muncul di UI padahal konsep UI sekarang auto-lunas.
- Kata/konsep `cicil`, `sebagian`, `partial`, `nominal bayar`, atau `payment_amount` muncul di UI utama padahal owner ingin UI Bayar = kirim bukti lalu lunas.

## Stop Rule Untuk AI/Codex

AI/Codex tidak boleh langsung patch.

Urutan wajib:

1. Map route dan file target.
2. Buktikan jalur input sampai output dengan file:line.
3. Buktikan state paid/unpaid/voided/reversed.
4. Buktikan validation dan storage behavior.
5. Buktikan test coverage yang sudah ada.
6. Tulis finding.
7. Baru rekomendasikan patch kecil.
8. Patch hanya setelah owner minta eksekusi.

## Verification Commands

Gunakan command berikut sebagai proof awal:

```bash
test -f artisan || { echo 'ERROR: bukan root Laravel'; exit 1; }

test ! -f routes/api.php && echo 'OK: routes/api.php absent' || echo 'WARN: routes/api.php exists'

rg -n "api:|routes/api\\.php|Route::prefix\\(['\"]api|/api/v1|mobile-login|mobile_api_tokens" \
  bootstrap routes app database config tests \
  --glob '!vendor' --glob '!node_modules' --glob '!storage' --glob '!bootstrap/cache' || true

php artisan route:list --name=procurement

rg -n "cicil|sebagian|partial|nominal bayar|payment_amount|payment_date|Kirim Bukti|Tandai Lunas|proof_files|payment-proof|payments.store|RecordSupplierPayment|AttachSupplierPaymentProof|UploadSupplierInvoicePaymentProof|ReverseSupplierPayment" \
  routes app resources public tests docs \
  --glob '!vendor' --glob '!node_modules' --glob '!storage' --glob '!bootstrap/cache' || true
```

## Final Decision

HyperPOS Laravel audit boundary setelah log ini adalah PWA/web-only.

Mobile API/Kotlin context retired.

Audit keamanan berikutnya harus fokus pada jalur web aktual, terutama procurement supplier invoice payment proof auto-lunas, dari input UI sampai output persistence/projection/audit/response.
