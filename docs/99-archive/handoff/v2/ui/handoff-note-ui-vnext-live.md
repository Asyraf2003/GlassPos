
# HANDOFF NOTE UI VNEXT - LIVE LOCAL GAPS

Tanggal: 2026-04-22

Status: verify hijau, live local masih ada gap nyata

Owner next chat: lanjut audit -> identifikasi gap -> baru patch

Rule: jangan asumsi. Kalau data belum cukup, audit dulu lalu tanya dengan opsi.



---



## 1. KONTEKS SINGKAT



Pekerjaan yang sudah dilakukan pada slice nota:



- Root note + revision chain sudah aktif.

- Payment selection contract sudah dipindah ke billing-row aware.

- Refund selection-first sudah diuji.

- Detail page admin dan cashier sudah dicutover ke shared final layout awal.

- Legacy reopen tidak lagi jadi kontrak UI final.

- `make verify` terakhir sudah hijau penuh.



Catatan penting:

- Lolos verify **bukan** berarti live local sudah beres.

- Setelah diuji manual di local, masih ada beberapa bug/gap produk/UI/runtime yang nyata dan harus diprioritaskan.



---



## 2. YANG SUDAH SELESAI / TERBUKTI



### 2.1 Core note revision

- Bootstrap initial revision sudah hidup.

- Submit edit note sekarang masuk ke revision flow.

- Current revision jadi source utama detail page.

- Note lama tidak langsung overwrite saat masuk edit; perubahan baru efektif saat submit berhasil.



### 2.2 Payment contract

- Selection payment tidak lagi pakai line id buta.

- Mixed-line payment sudah diarahkan ke billing row id.

- Intent Bayar/Lunasi/DP preset sudah punya proof test.



### 2.3 Refund contract

- Refund selection-first berbasis line domain sudah diuji.

- Reject line open operasional juga sudah diuji.



### 2.4 UI migration

- Admin dan cashier detail sudah mulai diarahkan ke shared detail page.

- UI final target sekarang:

  - satu layout detail untuk admin dan cashier

  - beda utama di scope index/filter

  - tanpa reopen

  - edit dan refund tetap bisa pada nota beres

  - versioning ringkas di bawah list line



### 2.5 Verify

- `make verify` sudah lolos terakhir.



---



## 3. HASIL UJI LIVE LOCAL - MASALAH NYATA YANG MASIH TERJADI



Berikut masalah dari pengujian manual live local. Ini prioritas utama next chat.



### P1. Create note baru masih membawa data sebelumnya

Gejala:

- setelah membuat nota, saat mau membuat nota baru lagi, data sebelumnya masih nempel.



Dampak:

- sangat berbahaya untuk kasir

- rawan salah transaksi

- indikasi draft/session/old input tidak direset



Dugaan area audit:

- workspace draft store/show

- old input/session flash

- local storage / JS hydration create page

- default row bootstrap yang tidak dibersihkan setelah submit sukses



Target perilaku:

- setelah create sukses, buka create note baru harus benar-benar bersih

- kecuali memang ada mode "lanjut draft" yang eksplisit



---



### P2. Refund gagal pada beberapa kasus produk banyak, dan justru bisa saat uang belum 100%

Gejala:

- pada beberapa kasus multi-produk, refund gagal

- refund justru bisa ketika uang belum 100%



Dampak:

- kontrak refund produk dunia nyata rusak

- kasir tidak bisa membatalkan transaksi sesuai kebutuhan



Dugaan area audit:

- resolver refundable amount

- filter refundable allocations

- prasyarat note closed / fully paid / operational close

- relasi antara paid allocations vs selected lines vs product lines

- kemungkinan refund sekarang masih terlalu bergantung pada payment allocation pattern tertentu



Target perilaku:

- refund line harus bisa terjadi sesuai keputusan bisnis final:

  - line yang dipilih dianggap dibatalkan

  - uang dan stok kembali sesuai komponen

  - semua masuk audit



Perlu dikonfirmasi ulang di next chat:

- apakah refund selalu boleh walau payment 0 / partial / full

- atau ada rule finansial minimum tertentu

Saat ini user menginginkan refund selalu bisa dari detail untuk line terpilih.



---



### P3. Refund tidak mengembalikan stok produk

Gejala:

- setelah refund, stok produk tidak berubah.



Dampak:

- fatal secara inventori

- uang bisa kembali tapi barang tidak balik ke stok



Dugaan area audit:

- operation/transaction refund belum memanggil restock flow

- restock hanya terjadi untuk branch tertentu

- mapping selected line -> store stock lines tidak sampai ke inventory mutation

- refund allocation tercatat tapi stock mutation tidak dieksekusi



Target perilaku:

- kalau line yang direfund mengandung produk toko, stok harus kembali

- kalau line service only, stok tidak disentuh

- semua mutasi stok harus audit-able



---



### P4. Di detail, line belum selalu bisa dipilih untuk refund

Gejala:

- saat status masih bayar sebagian, list line tidak bisa diklik untuk munculkan refund

- user ingin refund selalu bisa, baik sudah bayar maupun belum, karena secara bisnis dianggap transaksi itu "dibatalkan / dianggap tidak pernah ada", tapi tetap ada catatan audit



Dampak:

- UI dan backend refund masih terlalu restriktif

- bertentangan dengan rule produk final



Dugaan area audit:

- `can_show_refund_form`

- gating refund berdasar close/closed payment status

- JS line selection hanya aktif di state tertentu

- modal refund hanya dibuka kalau note memenuhi kondisi lama



Target perilaku final:

- refund tombol aktif bila ada line terpilih

- selection line harus selalu bisa pada detail

- refund modal wajib alasan

- backend memutus sendiri konsekuensi:

  - uang kembali

  - stok kembali

  - audit before/after



Catatan:

- ini kemungkinan butuh perubahan aturan domain, bukan cuma UI.



---



### P5. Layout detail tertukar dari target

Gejala:

- di live local, line sebelah kiri dan header sebelah kanan

- padahal target final:

  - kiri = header

  - kanan = list line



Dampak:

- implementasi shared page belum sesuai keputusan produk final



Dugaan area audit:

- shared detail view final

- urutan kolom / CSS utility / responsive order

- ada kemungkinan wrapper lama atau partial lama masih dipakai



Target perilaku:

- kiri header summary + status + action payment

- kanan line list + toolbar edit/refund + versioning di bawah line



---



### P6. Detail page terasa lambat

Gejala:

- halaman detail terasa sangat lambat saat ditampilkan



Dampak:

- UX buruk

- kemungkinan ada query N+1 atau builder terlalu berat



Dugaan area audit:

- detail page data builder

- revision timeline builder

- note revision repository row mapper

- query per revision -> query per lines

- correction history builder

- payment/refund aggregations

- partial yang memicu query tambahan di view/composer



Hipotesis teknis yang layak dicek:

- N+1 pada revision timeline dan line snapshots

- detail build memuat terlalu banyak blok sekaligus

- query koreksi/revision/payment/refund tidak di-batch



Target perilaku:

- detail render cepat walau note punya banyak line/revision

- perlu profiling query count dan wall time



---



### P7. Edit flow masih false validation: "ada line belum diisi"

Gejala:

- user sudah isi form edit

- sistem bilang gagal karena ada line belum diisi



Dampak:

- edit flow revision tidak usable

- ada mismatch payload UI vs validator/builder backend



Dugaan area audit:

- request validation edit workspace

- payload line_type / field naming

- hidden/template row kosong ikut terkirim

- JS add/remove row tidak membersihkan empty rows

- builder revision payload terlalu kaku terhadap row kosong



Target perilaku:

- row yang benar-benar kosong harus dibuang aman

- validator hanya menolak row yang memang dipilih/aktif tapi belum lengkap

- edit page create-style harus toleran pada template row kosong



---



## 4. KEPUTUSAN PRODUK FINAL YANG SUDAH TERKUNCI



Ini jangan diubah sembarang tanpa konfirmasi user.



### 4.1 Detail layout

- satu layout detail untuk admin dan kasir

- beda utama hanya scope index/filter

- kiri = header

- kanan = list line



### 4.2 Status utama

Prioritas label operasional:

1. Refunded

2. Refund Sebagian

3. Lunas

4. Sebagian

5. Belum Bayar



### 4.3 Edit

- edit = draft-style revision

- current lama tetap aktif sampai submit berhasil

- cancel = current lama tetap aktif

- submit sukses = revision baru jadi current



### 4.4 Refund

- refund berbasis selected lines

- user ingin refund bisa dipakai dari detail walau bayar belum full

- refund modal wajib alasan

- refund harus dianggap pembatalan line/transaksi terpilih, tapi tetap tercatat audit



### 4.5 Reopen

- reopen tidak dibutuhkan dalam model final

- closed/beres cukup tetap bisa edit atau refund

- semua perubahan dicatat via revision/audit



### 4.6 Index

- kasir default hanya hari ini + kemarin

- pencarian kasir tidak boleh memunculkan nota lebih lama dari scope itu

- admin default juga hari ini + kemarin, tapi punya filter untuk tanggal lama



---



## 5. DUGAAN FILE / AREA YANG WAJIB DIAUDIT DI NEXT CHAT



Berikut area audit prioritas. Jangan patch sebelum audit jelas.



### A. Create / draft persistence

- page create workspace

- save/get workspace draft

- session old input

- JS hydration create form



### B. Refund runtime

- controller refund

- selected note rows refund resolver

- refund operation / transaction

- refundable payment allocations

- inventory restock/mutation integration

- UI refund enable/disable state



### C. Detail page shared layout

- shared note detail view

- shared partial header/payment/line/versioning

- wrapper admin/cashier yang mungkin masih salah order atau salah class



### D. Detail performance

- note detail page data builder

- revision timeline builder

- note revision repository / row mapper

- correction history loader

- query count profiling



### E. Edit revision flow

- request validator patch/edit

- builder payload revision dari UI form

- JS row template cleanup

- handling row kosong / placeholder



---



## 6. PRIORITAS PENYELESAIAN DI CHAT BERIKUTNYA



Urutan yang paling aman dan bernilai:



### PRIORITAS 1

Audit bug create note yang masih bawa data lama

- karena ini paling berbahaya untuk kasir operasional harian



### PRIORITAS 2

Audit refund domain:

- kenapa gagal pada multi-produk / full-paid

- kenapa stok tidak kembali

- kenapa refund tidak selalu tersedia dari detail



### PRIORITAS 3

Audit edit flow validation palsu

- kenapa line kosong palsu masih ikut terbaca



### PRIORITAS 4

Audit performa detail

- hitung query

- cari N+1

- ukur bagian terberat



### PRIORITAS 5

Rapikan layout order kiri/kanan yang tertukar

- ini penting, tapi tidak setinggi bug transaksi/refund/edit



---



## 7. ATURAN KERJA UNTUK NEXT CHAT



Wajib patuhi:



1. audit dulu

2. identifikasi gap

3. kalau ada gap produk/aturan, tanya user dengan opsi plus/minus

4. baru implementation plan

5. baru patch terminal

6. jangan klaim selesai kalau belum ada bukti runtime dan verify yang sah

7. jangan asumsi perilaku refund/edit/create tanpa audit payload dan runtime



---



## 8. DEFINITION OF DONE SEMENTARA UNTUK PR BERIKUTNYA



### DoD Create reset

- create note baru tidak membawa data nota sebelumnya

- draft/session lama tidak nempel kecuali eksplisit



### DoD Refund domain

- refund line selected bisa berjalan pada kasus target bisnis yang user mau

- stok kembali benar untuk produk

- uang kembali benar

- audit before/after tercatat



### DoD Edit revision

- edit submit tidak gagal palsu karena row kosong/template

- cancel tidak mengubah current

- submit sukses mengubah current ke revision baru



### DoD Detail performance

- query count dan bottleneck diketahui

- minimal ada 1 patch nyata untuk mengurangi latency



### DoD Layout

- kiri header, kanan line

- toolbar edit/refund sesuai final decision

- versioning ringkas tetap ada

- tidak ada reopen di jalur final



---



## 9. PENUTUP



Status sekarang:

- verify hijau

- fondasi core cukup stabil

- tetapi live local membuktikan masih ada gap besar pada create/reset, refund domain, stok, edit validation, layout order, dan performa



Jadi pekerjaan berikutnya bukan kosmetik.

Ini sudah masuk ke:

- audit runtime nyata

- kontrak domain refund/final edit

- performa detail



