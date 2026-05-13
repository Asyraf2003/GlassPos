# NOTE REFUND UI FAILURE HANDOFF

## Ringkasan
Pekerjaan untuk refund UI pada halaman detail nota dihentikan pada titik ini karena implementasi interaksi line selection untuk refund belum stabil di runtime live local, walaupun beberapa fondasi backend dan data projection sudah berhasil diperbaiki.

Handoff ini khusus untuk area:
- detail nota
- edit nota berbasis revision
- billing/detail projection revision-aware
- refund stock reversal product-only
- refund UI selection by line di detail

---

## Status Akhir Page Ini

### Yang sudah berhasil
1. Edit nota sudah berpindah ke model revision workspace v2.
2. Edit workspace tidak lagi 500 pada flow utama yang sudah diperbaiki.
3. Current revision sudah terbaca untuk:
   - header/detail nota
   - daftar line nota
   - billing projection detail
4. Refund produk murni sudah berhasil mengembalikan stok melalui port reader tambahan untuk store stock line by work item.
5. Audit line-limit sempat beberapa kali gagal, lalu sudah dirapikan di area yang disentuh.
6. Beberapa test feature penting sudah lolos.

### Yang gagal / belum stabil
1. Refund UI option B pada detail nota belum stabil di runtime live local.
2. Klik row line refund belum menghasilkan perilaku final yang konsisten:
   - row belum jelas berubah menjadi selected
   - button refund masih bisa aktif dari awal atau state-nya tidak sinkron
   - ringkasan refund modal belum terisi konsisten dari line selection
3. Nama product pada preload edit masih sempat bermasalah dan belum ditutup final pada page ini.
4. Dampak refund terhadap pembacaan keuangan akhir belum diverifikasi tuntas secara manual.

---

## Fakta Terkunci

### Fakta backend / data
1. Root cause detail lama vs revision sudah ditutup:
   - detail line membaca current revision
   - billing panel membaca current revision summary
2. Root cause refund stock produk murni sudah ditutup:
   - component type `product_only_work_item` memakai `work_item_id` sebagai `component_ref_id`
   - reversal stok membutuhkan `work_item_store_stock_line.id`
   - solusi yang dipakai:
     - tambah `WorkItemStoreStockLineReaderPort`
     - tambah `DatabaseWorkItemStoreStockLineReaderAdapter`
     - `AutoReverseRefundedStoreStockInventory` sekarang resolve store stock lines dari work item untuk case product-only
3. Test refund stok product-only sudah berhasil lewat setelah patch port/adapter tersebut.

### Fakta UI refund
1. User memilih opsi B:
   - tidak ada checkbox di tabel
   - row yang diklik dianggap selected
   - row selected harus lebih gelap
   - button refund selalu ada tetapi disabled/burem sampai ada line dipilih
   - modal refund hanya untuk ringkasan + alasan + submit
2. Beberapa patch UI refund sudah dicoba, tetapi hasil live local tetap gagal.
3. Karena kegagalan berulang pada interaction layer, pekerjaan UI refund untuk page ini dihentikan dan harus diaudit ulang secara runtime-first.

---

## Perubahan Penting yang Sudah Masuk Sebelum Handoff Ini

### Fondasi revision/detail
- cashier edit submit diarahkan ke flow revision
- detail line membaca current revision
- billing detail membaca current revision totals
- payload/detail builder dipecah agar lolos line limit

### Refund stock product-only
- tambah port reader baru:
  - `app/Ports/Out/Note/WorkItemStoreStockLineReaderPort.php`
- tambah adapter:
  - `app/Adapters/Out/Note/DatabaseWorkItemStoreStockLineReaderAdapter.php`
- bind di:
  - `app/Providers/HexagonalServiceProvider.php`
- patch service:
  - `app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php`

### Test yang sudah pernah hijau pada area ini
- edit workspace page
- update transaction workspace
- cashier revision submit
- current revision detail lines
- current revision billing detail
- refund stock lifecycle service + store stock
- refund stock lifecycle product-only

Catatan:
Test hijau di atas tidak membuktikan refund UI selection di browser sudah benar. Masalah tersisa ada di runtime interaction, bukan lagi semata di feature backend.

---

## Area Gagal yang Harus Diambil Alih Oleh Pekerjaan Berikutnya

### 1. Refund UI runtime audit
Lakukan audit langsung di browser/runtime untuk:
- apakah script refund benar-benar load
- apakah button refund yang terlihat adalah elemen yang benar
- apakah ada elemen duplikat / modal duplikat
- apakah ada JS error runtime
- apakah event delegation bentrok dengan script lain
- apakah state disabled button ditimpa oleh Bootstrap atau script lain

### 2. Refund row selection redesign
Jangan lanjut patch buta.
Audit dulu:
- source runtime HTML final
- console error
- DOM state button refund saat page load
- DOM state row sebelum/sesudah klik
- apakah modal show dipicu oleh JS lain

### 3. Keuangan refund
Setelah refund UI stabil, lanjut verifikasi:
- amount refund
- payment status
- outstanding
- note state
- reporting impact bila ada projection yang membaca payment/refund totals

### 4. Product label preload edit
Masalah ini masih tersisa dan belum ditutup final di page ini.

---

## Root Cause Utama Kenapa Page Ini Ditutup
Masalah refund UI dipaksa beberapa kali di interaction layer tanpa bukti runtime browser yang cukup di awal. Akibatnya beberapa patch menjadi iterasi yang tidak menyentuh akar masalah live behavior. User menolak pendekatan asumsi/nebak, dan penolakan itu valid.

Keputusan akhir:
- hentikan patch refund UI pada page ini
- tutup dengan handoff jujur
- pekerjaan berikutnya harus runtime-first, bukan patch-first

---

## Bukti Terakhir Sebelum Handoff
Status terakhir yang dilaporkan user:
- refund stock produk murni sudah bisa balik
- edit/update sudah membaik
- refund UI detail tetap gagal:
  - row selection tidak memberikan hasil final yang benar
  - button refund state tidak sinkron
  - ringkasan modal tidak mengikuti selection

---

## Rekomendasi Next Step Paling Aman
1. Jangan patch refund UI lagi tanpa audit runtime browser.
2. Mulai dari inspect DOM + console + source rendered HTML.
3. Setelah runtime root cause ditemukan, baru tentukan apakah:
   - tetap pakai opsi B
   - atau rollback ke desain yang lebih eksplisit namun stabil
4. Setelah UI refund stabil, baru audit keuangan refund end-to-end.

---

## File Kunci untuk Dilanjutkan
- `resources/views/cashier/notes/show.blade.php`
- `resources/views/cashier/notes/partials/payment-actions.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-refund.js`
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Application/Note/Services/NoteWorkspacePanelDataBuilder.php`
- `app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php`
- `app/Ports/Out/Note/WorkItemStoreStockLineReaderPort.php`
- `app/Adapters/Out/Note/DatabaseWorkItemStoreStockLineReaderAdapter.php`
- `app/Providers/HexagonalServiceProvider.php`

---

## Penutup
Page ini ditutup bukan karena semua selesai, tetapi karena area refund UI sudah masuk fase gagal berulang dan harus dipindahkan ke handoff agar dilanjutkan dengan audit runtime yang disiplin.

