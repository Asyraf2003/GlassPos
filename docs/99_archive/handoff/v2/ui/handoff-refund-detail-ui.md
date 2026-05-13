# HANDOFF — DETAIL NOTA / REFUND / PARTIAL PAYMENT / STATUS UI

Tanggal: 2026-04-23
Konteks: audit + patch bertahap pada detail nota pelanggan di repo `bengkelnativejs`
Status: handoff untuk dilanjutkan besok

---

## 1) Ringkasan Eksekutif

Area refund inti sudah jauh lebih stabil dibanding awal sesi:
- selected row untuk refund sudah terkunci
- backend refund wajib `selected_row_ids`
- alokasi refund hanya ke row terpilih sudah terbukti test
- lifecycle refund untuk service only, product internal, service + store stock, dan service + external purchase sudah punya proof test
- external purchase refund tidak menyentuh stok internal
- reporting refund/profit ternyata sudah punya coverage test dan query yang benar
- backend read-model `refund_impact` sudah masuk
- UI refund modal sudah mulai membaca `refund_impact`

Tetapi masih ada beberapa masalah nyata yang belum ditutup:
1. Billing Projection masih tampil di UI detail dan harus dihapus dari page ini.
2. Refund untuk line belum dibayar masih keblok, padahal arah bisnis terbaru: line tetap harus bisa dinetralkan walau uang balik = 0.
3. UI bayar sebagian bermasalah pada kasus campuran multi-line, termasuk kalkulator/saran nominal yang akhirnya bikin submit gagal atau menyesatkan.
4. Setelah edit nota, daftar line sudah pakai revision terbaru, tetapi status/aksi level nota tidak sinkron.
5. Tombol/aksi refund menghilang setelah edit, padahal edit dan refund harus tetap muncul konsisten.
6. Kasus refund 1 produk murni secara uang sudah benar, tetapi overall UX/UI masih chaos pada beberapa kombinasi.

---

## 2) Fakta yang Sudah Locked

### 2.1 Refund selected line
Sudah selesai dan terbukti:
- UI launcher refund sudah tersambung lagi
- request refund wajib `selected_row_ids`
- test controller refund lama sudah disinkronkan
- selected-row refund untuk external purchase juga sudah ada test

### 2.2 Lifecycle refund
Sudah ada proof test untuk:
- service only full refund
- product only internal full refund
- service + store stock full refund
- service + store stock partial refund
- service + external purchase partial refund
- service + external purchase full refund
- external purchase refund tidak membuat `inventory_movements`

### 2.3 Rule domain yang sekarang aktif
Untuk line `service + external purchase`, prioritas allocation payment sudah diubah agar saat refund:
- external purchase part dinetralkan dulu
- baru service fee

### 2.4 Reporting
Sudah terbukti dari test/query:
- `refunded_rupiah` masuk ke transaction summary
- refund muncul di cash ledger
- operational profit menghitung `cash_in - refunded - cost - expenses ...`
- histori payment/refund tetap immutable

### 2.5 Refund impact detail payload
Sudah diimplementasikan di backend read-model:
- `refund_impact` masuk ke root note row mapper
- `refund_impact` masuk ke current revision row mapper
- revision payload sekarang menyimpan scalar array untuk `store_stock_lines` dan `external_purchase_lines`
- UI refund modal sudah mulai parse dan render detail `refund_impact`

---

## 3) Masalah Terbuka yang Perlu Dilanjutkan

## 3.1 Hapus Billing Projection dari UI detail

### Fakta
Di detail nota masih ada section `Billing Projection` yang sekarang tidak Anda inginkan muncul di UI detail.

### File kandidat
- `resources/views/shared/notes/partials/line-workspace.blade.php`
- `resources/views/cashier/notes/partials/billing-table.blade.php`
- kemungkinan juga `resources/views/shared/notes/show.blade.php`

### Catatan
Builder page masih membentuk `billingRows`, tetapi ini belum tentu harus dihapus dari backend. Kemungkinan cukup dihapus dari tampilan detail page dulu agar tidak mengganggu layer lain.

### Safest next step
- hide/remove include `billing-table` dari detail page
- jangan ubah builder backend dulu kecuali memang ada dampak lain

---

## 3.2 Refund line belum dibayar masih keblok

### Fakta
Arah bisnis terbaru dari user:
- line yang belum dibayar tetap harus bisa “di-refund” dalam arti dinetralkan / dibatalkan
- kalau belum ada uang masuk, maka tidak ada uang yang dikembalikan
- kalau pakai stok internal, stok tetap harus balik
- intinya hasil line jadi nol walau refund amount = 0

### Konflik dengan implementasi sekarang
Saat ini refund masih dikunci oleh logika `net_paid_rupiah > 0` di beberapa tempat:
- UI refund launcher/submit membaca refundable amount
- request refund masih butuh `amount_rupiah >= 1`
- builder/UI refund masih identik dengan “money refund only”

### Implikasi
Ini bukan bug kecil UI. Ini perubahan kontrak refund:
- refund no-money / zero-amount neutralization harus diperbolehkan
- kemungkinan `amount_rupiah` perlu boleh `0`
- `can_refund` tidak boleh semata bergantung pada uang masuk

### Area kandidat
- `app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php`
- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundAmountResolver.php`
- `app/Application/Payment/UseCases/RecordCustomerRefundHandler.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-refund.js`
- kemungkinan penentu `can_refund` di row/status builder

### Risiko
Tinggi. Ini menyentuh definisi refund vs cancel/neutralize. Jangan patch tanpa lock rule minimal dulu.

### Safest next step
Desain minimum dulu:
- apakah tetap memakai route refund yang sama
- apakah `amount_rupiah = 0` legal
- apakah stock reversal untuk internal tetap berjalan saat amount 0
- apakah note status/refund history tetap dicatat sebagai refund atau butuh status/jenis event baru

---

## 3.3 UI bayar sebagian bermasalah pada kasus campuran multi-line

### Gejala dari user
Kasus yang dites user mencampur beberapa line seperti:
- produk
- service + produk / store stock
- service + part luar / external purchase

Lalu saat bayar sebagian:
- input nominal di bawah total seharusnya valid
- tetapi kalkulator/saran nominal ikut bermasalah
- submit akhirnya gagal atau menyarankan nominal tertentu sendiri

### Dugaan area masalah
Kemungkinan besar ada masalah di layer payment flow UI / partial payment selection / billing projection translation.

### File kandidat
- `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`
- `public/assets/static/js/pages/cashier-note-workspace.js`
- `public/assets/static/js/pages/cashier-note-workspace/summary.js`
- `public/assets/static/js/pages/cashier-note-workspace/rows.js`
- builder billing / payment selection yang dipakai detail page
- kemungkinan juga request validator / normalizer payment flow

### Hipotesis kerja
Ada mismatch antara:
- total yang ditampilkan
- komponen yang eligible dibayar sebagian
- preset DP / calculator suggestion
- payload final yang disubmit

### Safest next step
Audit dengan repro terarah:
1. seed / manual case 3 line campuran
2. catat total per component
3. catat nominal yang diinput user
4. log suggestion/normalization JS
5. bandingkan payload submit actual

---

## 3.4 Setelah edit nota, daftar line sinkron tetapi status/aksi nota tidak sinkron

### Gejala dari user
Setelah edit nota:
- daftar line di UI sudah benar / mengikuti revision
- tetapi status nota dan aksi level nota masih gagal sinkron
- refund UI bahkan hilang
- padahal edit dan refund harus selalu ada, dengan style:
  - edit biru
  - refund merah

### Fakta teknis yang relevan
Sudah ada proof test bahwa:
- detail line bisa membaca current revision
- billing panel juga bisa membaca current revision

Tetapi page-level payload note masih dibangun oleh `NoteDetailPageDataBuilder`, yang menyusun:
- `workspacePanel`
- `operational`
- `billingRows`
- `refundRows`
- `note payload`

Ada kemungkinan level page note masih campur root note state + current revision state secara tidak konsisten.

### File kandidat
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Application/Note/Services/NoteDetailNotePayloadBuilder.php`
- `app/Application/Note/Services/NoteDetailRevisionViewDataBuilder.php`
- `resources/views/shared/notes/partials/line-workspace.blade.php`
- `resources/views/shared/notes/show.blade.php`

### Hipotesis kerja
Kemungkinan bug ada di salah satu ini:
1. refund/edit button visibility masih membaca flag dari root note, bukan revision-aware payload
2. `refundRows`/`can_show_refund_form` kebentuk dari data yang belum sinkron sesudah edit
3. page-level status label/payment status masih pakai kombinasi root note + operational lama

### Safest next step
Audit payload final yang dikirim ke blade untuk detail note setelah revision aktif:
- note state
- payment status
- can_show_refund_form
- can_edit_workspace
- rows can_refund
- refund rows count

---

## 3.5 UI refund masih perlu verifikasi browser final

### Fakta
Di level code + regression, UI refund detail sudah:
- kirim `data-refund-impact`
- punya section detail stok kembali dan external dinetralkan
- parse + render `refund_impact`

### Yang belum ada
Belum ada bukti manual browser final untuk:
- item detail benar-benar tampil rapi
- layout modal tidak janggal
- Enter submit tetap nyaman
- alignment kiri/kanan sudah enak

### File kandidat
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-refund.js`

### Safest next step
Manual browser verification dulu sebelum patch kosmetik lanjutan.

---

## 4) Area yang Sudah Aman dan Tidak Perlu Diutak-atik Dulu

Untuk besok, sebaiknya jangan bongkar dulu area ini kecuali ada bukti bug baru:
- lifecycle refund internal/external yang sudah hijau
- priority allocation external purchase yang baru diubah
- reporting refund/profit
- selected row refund contract
- backend `refund_impact` builder yang sudah pass test

---

## 5) Kandidat Urutan Kerja Besok

Urutan paling aman menurut kondisi sekarang:

### Opsi urutan yang direkomendasikan
1. **Hapus Billing Projection dari UI detail**
   - surface area kecil
   - cepat dibersihkan
   - minim risiko domain

2. **Audit payload note-level sesudah edit/revision**
   - cari kenapa status/aksi/refund hilang atau tidak sinkron
   - ini kemungkinan bug page assembler, bukan refund core

3. **Audit partial payment UI bug pada multi-line campuran**
   - ini kemungkinan bug terpisah dan cukup besar
   - butuh repro yang disiplin

4. **Baru lock desain refund zero-money untuk unpaid line**
   - ini perubahan domain yang lebih berat
   - jangan dicampur dengan bug UI payment flow

### Kenapa urutan ini
Karena kalau refund zero-money dibahas dulu, Anda akan campur:
- perubahan domain refund
- bug page-level status
- bug UI partial payment

Dan itu resep klasik untuk kehilangan jejak sebab-akibat.

---

## 6) Daftar File Penting untuk Dibuka Besok

### Detail page / note-level payload
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Application/Note/Services/NoteDetailNotePayloadBuilder.php`
- `app/Application/Note/Services/NoteDetailRevisionViewDataBuilder.php`
- `resources/views/shared/notes/show.blade.php`
- `resources/views/shared/notes/partials/line-workspace.blade.php`

### Refund UI
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-refund.js`

### Billing / partial payment UI
- `resources/views/cashier/notes/partials/billing-table.blade.php`
- `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`
- `public/assets/static/js/pages/cashier-note-workspace.js`
- `public/assets/static/js/pages/cashier-note-workspace/summary.js`
- `public/assets/static/js/pages/cashier-note-workspace/rows.js`

### Refund / payment domain yang sudah sensitif
- `app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundAmountResolver.php`
- `app/Application/Payment/UseCases/RecordCustomerRefundHandler.php`
- `app/Application/Payment/Services/PaymentComponentTypePriority.php`

---

## 7) Bukti Test yang Sudah Hijau Hari Ini

Area refund / note / reporting yang sudah hijau selama sesi ini mencakup setidaknya:
- `RecordClosedNoteRefundControllerFeatureTest`
- `ClosedNoteFullRefundLifecycleFeatureTest`
- `ClosedNoteFullRefundProductOnlyInventoryLifecycleFeatureTest`
- `ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest`
- `ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest`
- `RecordSelectedRowsCustomerRefundFeatureTest`
- `RecordCustomerRefundFeatureTest`
- `AllocatePaymentAcrossComponentsTest`
- `RefundImpactPayloadBuilderTest`
- `CashierNoteDetailUsesCurrentRevisionLinesFeatureTest`
- `CashierNoteDetailBillingUsesCurrentRevisionFeatureTest`
- `CashierClosedNoteRefundViewFeatureTest`

Ini penting supaya besok tidak membongkar area yang sebenarnya sudah locked.

---

## 8) Status Penutupan Hari Ini

### Sudah selesai / locked
- selected-row refund
- lifecycle refund internal + external
- refund impact backend read-model
- refund impact UI code-level integration
- reporting refund/profit proof

### Pending / belum selesai
- remove billing projection dari detail UI
- sinkronisasi status/aksi note setelah edit
- partial payment UI bug pada multi-line mixed case
- keputusan domain untuk refund zero-money pada unpaid line
- verifikasi browser final modal refund detail

### Safest first move besok
**Mulai dari hapus Billing Projection di UI detail, lalu audit page-level note payload setelah edit.**

