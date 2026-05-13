# Handoff UI V2 - Note Workspace Unification

Tanggal: 2026-04-19

## Ringkasan

Scope aktif ini sudah selesai untuk target berikut:

- create dan edit nota kasir memakai shell workspace yang sama
- payment modal sekarang tersedia di create dan edit
- update workspace sekarang menerima dan memproses `inline_payment`
- refund modal sudah ditanam ke workspace edit
- regression target untuk create, edit, payment, refund, dan view sudah hijau
- `audit-lines` dan `audit-blade` juga hijau

Status akhir scope ini: **READY**

---

## Tujuan yang diselesaikan

Target utama yang diminta:

- create dan edit nota kasir harus seragam
- layout tetap:
  - header/info di kanan
  - list/rincian di kiri
- flow bayar penuh, bayar sebagian, dan simpan tanpa bayar memakai modal/dialog yang sama keluarga
- kalkulator cash tetap dipakai di flow yang sama
- refund mulai dipindah ke paradigma modal workspace edit
- jangan cuma seragam di UI, backend edit juga harus benar-benar setara untuk `inline_payment`

---

## Fakta yang sudah terkunci

### 1. Workspace create dan edit sekarang satu keluarga
- `resources/views/cashier/notes/workspace/create.blade.php` dipakai sebagai shell utama untuk create dan edit
- mode dibedakan lewat `workspaceMode`
- `back_url` sudah dipasang
- intro workspace sudah diseragamkan
- panel kanan sudah memakai tombol utama `Proses Nota`

### 2. Payment modal sekarang ikut aktif di edit
- payment modal tidak lagi eksklusif create
- `payment-flow.js` ikut diload untuk edit
- `info-card` edit sekarang tidak memakai pola lama "Simpan Perubahan" sebagai CTA utama
- create dan edit sekarang sama-sama berangkat dari tombol `Proses Nota`

### 3. Refund modal sudah masuk ke workspace edit
- `resources/views/cashier/notes/workspace/partials/refund-modal.blade.php` sudah dibuat
- builder edit sekarang memasok payload refund minimum:
  - `refundAction`
  - `refundDateDefault`
  - `refundPaymentOptions`
  - `workspaceRefundRows`
  - `canShowRefundModal`
- tombol `Refund` hanya tampil pada mode edit bila memang ada row eligible refund

### 4. Backend edit sekarang benar-benar memproses inline payment
Sebelum perubahan:
- create menerima `inline_payment`
- edit hanya update header dan item

Sesudah perubahan:
- `UpdateTransactionWorkspaceInputNormalizer` menerima `inline_payment`
- `UpdateTransactionWorkspaceRules` memvalidasi `inline_payment`
- `UpdateTransactionWorkspaceValidator` memvalidasi `inline_payment`
- `UpdateTransactionWorkspaceHandler` sekarang merekam inline payment seperti create
- `UpdateTransactionWorkspaceResultBuilder` sekarang ikut membawa ringkasan payment dan success message yang sesuai

### 5. Regression akhir untuk scope aktif sudah hijau
Regression target yang dijalankan:

- `tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php`
- `tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php`
- `tests/Feature/Note/CreateTransactionWorkspaceFullTransferFeatureTest.php`
- `tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php`
- `tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php`
- `tests/Feature/Note/CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php`
- `tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php`
- `tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php`
- `tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
- `tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`
- `tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php`

Hasil akhir:
- **16 passed**
- **99 assertions**
- `make audit-lines` pass
- `make audit-blade` pass

---

## File yang berubah

### Fondasi UI
- `public/assets/static/css/ui-foundation.css`
- `resources/views/layouts/app.blade.php`

### Product UI acuan
- `resources/views/admin/products/create.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/admin/products/show.blade.php`
- `resources/views/admin/products/stock-edit.blade.php`

### Procurement UI
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `resources/views/admin/procurement/supplier_invoices/edit.blade.php`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`

### Cashier notes show/panel
- `resources/views/cashier/notes/show.blade.php`
- `resources/views/cashier/notes/partials/note-overview.blade.php`
- `resources/views/cashier/notes/partials/payment-form.blade.php`
- `resources/views/cashier/notes/partials/refund-form.blade.php`
- `resources/views/cashier/notes/partials/add-rows-form.blade.php`

### Cashier workspace
- `resources/views/cashier/notes/workspace/create.blade.php`
- `resources/views/cashier/notes/workspace/partials/info-card.blade.php`
- `resources/views/cashier/notes/workspace/partials/refund-modal.blade.php`

### Backend workspace note
- `app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php`
- `app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceInputNormalizer.php`
- `app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceRules.php`
- `app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceValidator.php`
- `app/Application/Note/Services/UpdateTransactionWorkspaceResultBuilder.php`
- `app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php`

### Test yang diperbarui
- `tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php`
- `tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php`

---

## Keputusan desain yang sudah dikunci

1. Create dan edit nota kasir memakai workspace shell yang sama.
2. CTA utama panel kanan adalah `Proses Nota`.
3. Payment flow create/edit memakai modal yang sama.
4. Refund untuk edit masuk ke modal terpisah tapi satu keluarga visual.
5. Backend update workspace wajib setara dengan create untuk `inline_payment`.
6. Test kontrak view lama yang bertentangan dengan UI baru harus diperbarui mengikuti desain final, bukan memaksa UI balik ke pola lama.

---

## Scope yang selesai

### Selesai
- unifikasi create/edit workspace nota kasir
- payment parity antara create dan edit
- refund modal workspace edit
- regression target untuk note workspace, payment, refund, dan view
- fondasi UI bersama yang sudah dipakai di halaman yang disentuh

### Belum diselesaikan di handoff ini
- standardisasi total semua halaman UI lintas seluruh modul
- penyederhanaan final `cashier.notes.show` menjadi launcher murni
- hardening UX kecil seperti auto-open refund modal saat validation error
- standardisasi lanjutan untuk seluruh halaman procurement/product yang belum dipoles lebih jauh

---

## Risiko aktif

Tidak ada blocker aktif untuk scope ini.

Risiko residual yang masih perlu diingat:
- refund modal workspace edit sudah ada, tetapi belum ada hardening UX lanjutan seperti auto-open modal saat validation error
- masih ada pekerjaan standardisasi UI lintas modul yang belum ditutup penuh, jadi jangan menganggap seluruh aplikasi sudah seragam

---

## Safest next step

Langkah paling aman setelah handoff ini:

1. lanjutkan standardisasi UI ke halaman kasir/procurement lain yang belum sepenuhnya ikut pattern baru
2. pertahankan aturan yang sudah terkunci:
   - shell sama
   - action hierarchy sama
   - modal action konsisten
   - backend parity dulu, baru kosmetik
3. bila fokus tetap di kasir:
   - sederhanakan `cashier.notes.show` menjadi halaman baca/ringkas + launcher
   - hardening UX modal refund workspace edit

---

## Bukti verifikasi akhir

Command final yang sudah hijau:

```bash
php artisan test \
  tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php \
  tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php \
  tests/Feature/Note/CreateTransactionWorkspaceFullTransferFeatureTest.php \
  tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php \
  tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php \
  tests/Feature/Note/CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php \
  tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php \
  tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php \
  tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php \
  tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php && \
make audit-lines && \
make audit-blade
```

# Hasil:

- 16 tests passed
- 99 assertions
- audit-lines success
- audit-blade success

## Status akhir

- Scope handoff ini: READY
- Risiko fungsional utama: rendah
- Bukti verifikasi: lengkap untuk scope aktif
- Aman diteruskan ke halaman berikutnya tanpa membuka ulang keputusan domain note workspace

## Progress akhir

- backend lifecycle: 100%
- cleanup backend sebelum UI: 100%
- unifikasi create/edit nota kasir: 100%
- verifikasi final scope aktif: 100%
- handoff readiness: 100%
