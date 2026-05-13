# Handoff V2/UI - Procurement Edit Locked -> Koreksi

## Ringkasan
Scope ini menyelesaikan perbaikan UX dan contract untuk aksi edit nota procurement saat invoice sudah `locked`.

Hasil akhirnya:
- akses `/edit` untuk invoice `locked` tidak lagi dilempar ke detail, tetapi langsung ke route `revise`
- modal aksi di index procurement tidak lagi hardcode ke `/edit`
- index procurement sekarang menerima contract aksi edit final dari backend
- istilah UI untuk aksi locked diseragamkan menjadi **Koreksi**
- contract JSON table untuk field aksi edit sekarang sudah dikunci lewat feature test
- targeted verification untuk scope ini sudah lulus

## Scope yang selesai

### 1. Redirect edit saat invoice locked
Selesai:
- `EditSupplierInvoicePageController` untuk invoice `locked` sekarang redirect ke:
  - `admin.procurement.supplier-invoices.revise`
- perilaku lama redirect ke detail sudah dihapus

### 2. Action modal index mengikuti policy state
Selesai:
- payload table index sekarang mengirim field:
  - `policy_state`
  - `edit_action_kind`
  - `edit_action_label`
  - `edit_action_url`
- JS modal index sekarang membaca contract itu
- aksi edit di modal tidak lagi selalu menuju `/edit`

### 3. Konsistensi wording UI
Selesai:
- istilah `Correction / reversal` diganti menjadi `Koreksi` pada surface yang disentuh di scope ini:
  - detail policy view
  - modal aksi index fallback label
  - test detail procurement
  - contract test table index

## Rule / keputusan yang terkunci
- invoice procurement yang `locked` harus diarahkan ke flow **Koreksi**
- index tidak boleh lagi memutuskan aksi edit dengan hardcode route frontend saja
- contract aksi edit dari backend untuk row index harus tersedia dan bisa diuji
- istilah UI untuk aksi locked pada scope ini adalah:
  - **Koreksi**

## File yang diubah

### Redirect / controller
- `app/Adapters/In/Http/Controllers/Admin/Procurement/EditSupplierInvoicePageController.php`

### Detail policy wording
- `app/Adapters/In/Http/Controllers/Admin/Procurement/Concerns/BuildsProcurementInvoiceDetailPolicyView.php`

### Payload table index
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTablePayload.php`

### JS index procurement
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`

### Feature tests
- `tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php`
- `tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`

## Bukti verifikasi yang sudah lulus

### Edit redirect
~~~bash
php artisan test tests/Feature/Procurement/EditSupplierInvoicePageFeatureTest.php
~~~

Hasil:
- pass
- locked edit -> revise terbukti

### Detail page procurement
~~~bash
php artisan test tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php
~~~

Hasil:
- pass
- wording `Koreksi` di detail page terbukti

### Table data contract procurement
~~~bash
php artisan test tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php
~~~

Hasil:
- pass
- field berikut terkunci:
  - `policy_state`
  - `edit_action_kind`
  - `edit_action_label`
  - `edit_action_url`

### Update procurement regression
~~~bash
php artisan test tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php
~~~

Hasil:
- pass
- regression update procurement yang berdekatan dengan scope ini tetap aman

### JS syntax
~~~bash
node --check public/assets/static/js/pages/admin-procurement-invoices-table.js
~~~

Hasil:
- lulus
- tidak ada error syntax

## Status akhir scope ini
Scope berikut dianggap selesai:
- redirect edit locked ke koreksi
- konsistensi istilah `Koreksi` untuk surface yang disentuh
- contract index action edit dari backend
- penguncian test untuk contract index
- targeted regression verification

## Gap / belum disentuh
Ada satu isu yang **sengaja belum disentuh** pada scope ini:

- potensi mismatch policy domain antara:
  - payment pending
  - payment efektif

Catatan jujur:
- saat ini read-side index menilai locked berbasis keberadaan `payment_count` atau `receipt_count`
- sementara write-side/update masih punya skenario test yang menunjukkan update tetap bisa hidup pada kondisi payment tertentu
- ini bukan scope rename / polish UI
- ini perlu keputusan domain terpisah sebelum diubah

## Safest next step
Kalau buka scope lanjutan setelah handoff ini, urutan teraman adalah:

1. audit dan putuskan rule final procurement lock:
   - payment pending
   - payment efektif
   - receipt
   - efek turunan lain
2. setelah rule final dikunci, samakan:
   - read-side index
   - detail policy view
   - write guard / update policy
   - feature tests terkait

## Progress akhir
- scope handoff ini: **100% selesai**
