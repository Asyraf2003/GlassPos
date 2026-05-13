# Handoff V2: Employee Finance

## Ringkasan Status

Domain employee finance sudah keluar dari fase migrasi schema lama ke schema final, dan sekarang masuk ke fase penguatan policy, auditability, dan UX operasional.

Posisi saat ini:

- employee master sudah stabil dan bisa dipakai
- test suite sempat dibersihkan sampai `make verify` lolos
- baseline seeder V1 sudah bisa dipakai untuk `php artisan migrate:fresh --seed`
- UI employee dan UI hutang dasar sudah bisa dipakai operasional awal
- istilah UI harus bahasa Indonesia untuk client
- backend/internal naming boleh tetap teknis
- data finansial tidak boleh diedit diam-diam

Arah desain yang paling aman:

- employee master: versioned
- hutang pokok: koreksi lewat adjustment
- gaji: batalkan catatan lalu catat ulang, bukan edit langsung
- pembayaran hutang: idealnya immutable juga, jangan edit langsung

---

## Fakta Domain Final yang Sudah Terkunci

Schema employee final yang aktif:

- `employee_name`
- `salary_basis_type`
- `default_salary_amount`
- `employment_status`
- `started_at`
- `ended_at`

Prinsip UI/operasional yang sudah disepakati:

- UI full bahasa Indonesia
- backend boleh tetap teknis
- data keuangan tidak diedit langsung
- perubahan finansial harus meninggalkan jejak
- angka lama tidak boleh ditimpa diam-diam
- lebih aman memakai:
  - pembatalan
  - koreksi
  - pencatatan ulang

---

## Yang Sudah Dilakukan

### 1. Employee master

Sudah dibereskan:

- migrasi schema lama ke schema final
- query/read model dibersihkan ke kolom final
- request/controller/test disesuaikan ke kontrak final
- route dan halaman utama employee stabil:
  - index
  - create
  - detail
  - edit
- kalender pada create/edit sudah mengikuti pola template tanggal modul lain
- index employee sudah punya modal aksi yang lebih rapi
- action ke modul hutang sudah bisa prefill employee terpilih
- employee id baseline seeder sudah memakai UUID valid
- employee master sudah dianggap aman untuk fase dasar

### 2. Testing dan fondasi

Sudah dibereskan:

- failure massal akibat schema lama di test dan read model
- `make verify` sempat lolos penuh
- `php artisan migrate:fresh --seed` baseline V1 berhasil

### 3. Seeder baseline employee finance V1

Sudah dibuat dan dipakai:

- baseline V1 ringan
- employee finance bisa di-seed bersih
- dipakai untuk uji manual UI dan alur operasional

### 4. Domain hutang

Sudah dibereskan:

- index hutang punya modal aksi
- create hutang punya autocomplete nama karyawan
- submit tetap memakai `employee_id` UUID valid
- create hutang bisa prefill employee dari modul lain
- detail hutang sudah punya:
  - ringkasan hutang
  - catat pembayaran
  - koreksi principal
  - riwayat pembayaran
  - riwayat koreksi

### 5. Domain gaji

Sudah dibereskan sebagian:

- query payroll table sudah dibersihkan ke schema final
- test payroll table sudah dibersihkan
- reversal gaji sudah terbukti ada dan tercatat
- endpoint payroll per employee sudah berjalan

---

## Yang Belum Selesai

### 1. Hutang end-to-end

Belum final:

- policy pembayaran hutang
- policy saat pembayaran hutang salah input
- apakah pembayaran hutang akan:
  - immutable penuh
  - bisa dibatalkan
  - bisa dikoreksi
  - harus dicatat ulang
- UI pembatalan/koreksi pembayaran hutang
- closure audit trail pembayaran hutang

### 2. Gaji/payroll end-to-end

Belum final:

- istilah UI yang sederhana untuk client
- flow catat gaji
- flow batalkan catatan gaji
- flow catat gaji pengganti
- alur UI payroll end-to-end
- penguatan auditability untuk pencatatan gaji biasa

### 3. Policy lintas domain finansial

Belum final:

- mana yang wajib immutable
- mana yang adjustment-based
- mana yang reversal-based
- mana yang butuh version table
- mana yang secara tegas dilarang edit langsung

### 4. Seeder V2 dense

Belum dibuat:

- dataset 1 tahun padat
- payroll padat
- hutang dan pembayaran lebih variatif
- bahan uji pagination/report/stress/demo

---

## Closure Goal

Closure domain ini bukan sekadar halaman bisa dibuka, tetapi:

### Closure employee master

- create/edit/detail/index stabil
- versioning employee jelas
- UI bahasa Indonesia rapi
- modal aksi konsisten
- seed baseline dan dense tersedia

### Closure hutang

- create hutang stabil
- bayar hutang stabil
- koreksi hutang stabil
- riwayat hutang, pembayaran, koreksi jelas
- policy tidak edit langsung terkunci
- jika ada salah input, tersedia flow resmi
- audit trail cukup untuk pemeriksaan

### Closure gaji

- catat gaji stabil
- batalkan catatan gaji stabil
- catat gaji pengganti stabil
- UI dan istilah mudah dipahami client
- tidak ada edit langsung nominal/riwayat gaji
- audit trail cukup untuk pemeriksaan

### Closure integrasi

- employee ↔ hutang ↔ gaji saling nyambung dari sisi navigasi
- action dari modul satu bisa prefill modul lain
- data seed mendukung uji manual end-to-end
- baseline dan dense dataset tersedia

---

## Hutang Teknis

### A. Policy hutang finansial

Belum ada keputusan final untuk:

- pembayaran hutang yang salah input
- pembatalan pembayaran hutang
- koreksi pembayaran hutang
- pencatatan ulang pembayaran hutang

Risiko:

- nanti user meminta edit langsung karena sistem belum menyediakan alternatif resmi

### B. Policy payroll/gaji

Belum selesai dirapikan:

- istilah UI untuk client
- bahasa operasional non-teknis
- flow pembatalan dan penggantian catatan gaji

### C. Konsistensi auditability

Belum semua flow finansial terbukti punya audit log atau strategi append-only yang konsisten.

### D. Seeder dense V2

Belum ada implementasi 1 tahun padat untuk uji report, pagination, dan operasional skala lebih besar.

---

## Area Abu-abu

Ini area yang belum boleh diasumsikan selesai:

1. Apakah pembayaran hutang boleh dibatalkan?  
   Belum final.

2. Apakah pembayaran hutang boleh dikoreksi sebagian?  
   Belum final.

3. Apakah pencatatan gaji biasa sudah punya audit log yang cukup?  
   Belum terbukti kuat.

4. Apakah hutang dan gaji akan memakai version table penuh seperti employee master?  
   Belum final.

5. Apakah edit akan tampil di UI client untuk domain finansial?  
   Rekomendasi kuat: jangan tampilkan edit langsung untuk data keuangan inti.

---

## Prinsip Desain yang Harus Dijaga

- UI full bahasa Indonesia
- backend boleh teknis
- data keuangan tidak diedit langsung
- perubahan finansial harus meninggalkan jejak
- angka lama tidak boleh ditimpa diam-diam
- lebih aman memakai:
  - pembatalan
  - koreksi
  - pencatatan ulang

Rekomendasi desain:

- employee master: versioned
- hutang pokok: immutable base + adjustment-based
- pembayaran hutang: immutable + reversal/correction-based
- payroll disbursement: immutable + reversal-based
- edit langsung untuk uang/hutang: dihindari

---

## Bahasa Sederhana untuk Client

Jangan pakai istilah UI seperti:

- disburse
- reverse
- disburse ulang
- adjustment principal

Pakai istilah yang dipahami client.

### Untuk gaji

Gunakan:

- `Catat Gaji`
- `Batalkan Catatan Gaji`
- `Catat Gaji Pengganti`

### Untuk hutang

Gunakan:

- `Catat Hutang`
- `Catat Pembayaran Hutang`
- `Koreksi Hutang`

### Untuk riwayat

Gunakan:

- `Riwayat Catatan Gaji`
- `Riwayat Pembayaran Hutang`
- `Riwayat Koreksi Hutang`

### Penjelasan sederhana ke client

Gunakan kalimat seperti ini:

> Data gaji dan hutang tidak diubah langsung supaya riwayat tetap utuh. Kalau ada kesalahan, sistem mencatat pembatalan, koreksi, atau catatan pengganti. Jadi semua perubahan tetap bisa ditelusuri.

Versi singkat untuk helper text UI:

> Data keuangan tidak diedit langsung. Perubahan dilakukan lewat pembatalan, koreksi, atau pencatatan ulang agar riwayat tetap utuh.

---

## Status Audit dan Jejak Per Flow

### Yang sudah cukup aman arahnya

#### Employee master
- paling matang
- sudah mengarah ke versioning yang lebih serius

#### Koreksi principal hutang
- append record ke adjustment
- ada audit log
- tidak sekadar edit diam-diam

#### Reversal gaji
- tidak menimpa payroll lama
- mencatat reversal terpisah
- ada audit log
- arah desain sudah benar untuk domain uang

### Yang belum matang setara

#### Bayar hutang
- belum terbukti setegas employee master
- belum terbukti punya audit trail yang cukup kuat

#### Catat gaji biasa
- belum terbukti setegas reversal gaji
- audit trail pencatatan biasa masih perlu dipastikan

#### Edit hutang / edit pembayaran hutang / edit gaji
- belum punya policy final
- sangat berisiko jika dibuka sebagai edit langsung

---

## Rekomendasi Policy Final

### Employee master
- boleh berubah
- wajib versioned

### Hutang pokok
- jangan edit langsung
- gunakan koreksi hutang

### Pembayaran hutang
- jangan edit langsung
- target ideal:
  - immutable
  - kalau salah, pakai pembatalan/koreksi pembayaran
  - lalu catat ulang bila perlu

### Gaji/payroll
- jangan edit langsung
- target ideal:
  - catat gaji
  - batalkan catatan gaji
  - catat gaji pengganti

---

## Urutan Eksekusi Halaman Berikutnya

Jangan loncat-loncat. Kerjakan per domain sampai bersih.

### Halaman kerja 1: Employee master
Status:
- praktis selesai untuk fase dasar

Target closure:
- tidak ada pekerjaan inti besar lagi di employee master
- employee jadi baseline referensi untuk domain lain

### Halaman kerja 2: Hutang end-to-end
Fokus:
- policy bayar hutang
- apakah pembayaran hutang immutable
- flow salah input bayar hutang
- apakah perlu pembatalan pembayaran hutang
- apakah perlu koreksi pembayaran hutang
- UI detail hutang + payment action + history
- audit trail minimal per flow

Deliverable closure:
- matriks action hutang
- policy final
- UI yang sesuai policy
- audit trail yang cukup
- tidak ada edit langsung yang liar

### Halaman kerja 3: Gaji end-to-end
Fokus:
- istilah UI sederhana untuk client
- flow catat gaji
- flow batalkan catatan gaji
- flow catat gaji pengganti
- tidak ada edit langsung
- audit trail minimal per flow
- integrasi dari employee/detail dan payroll index

Deliverable closure:
- policy final payroll
- UI payroll client-friendly
- alur pembatalan dan pencatatan ulang jelas
- query/report tetap konsisten

### Halaman kerja 4: Integrasi lintas domain
Fokus:
- linking employee ↔ hutang ↔ gaji
- prefill action
- navigasi lintas modul
- empty state dan keyboard flow
- konsistensi modal aksi

Deliverable closure:
- operasional admin terasa satu sistem, bukan tiga modul terpisah

### Halaman kerja 5: Seeder dense V2
Fokus:
- dataset 1 tahun
- payroll padat
- hutang dan pembayaran lebih variatif
- bahan uji pagination/report dan demo operasional

Deliverable closure:
- `DatabaseLoadSeeder` untuk stress/demo
- baseline tetap ringan
- dense tetap deterministik

---

## Matriks Ringkas Status Domain

### Employee master
- Fondasi: selesai
- UI dasar: selesai
- Versioning: ada
- Auditability: cukup
- Closure dasar: tercapai

### Hutang
- Fondasi: cukup
- UI dasar: cukup
- Payment policy: belum final
- Editability policy: belum final
- Auditability: sebagian ada, belum seragam
- Closure: belum

### Gaji
- Fondasi: cukup
- UI: belum beres penuh
- Reversal: ada arah yang benar
- Bahasa client: belum beres penuh
- Auditability disbursement biasa: belum cukup jelas
- Closure: belum

---

## Fokus Halaman Berikutnya

Jika halaman berikutnya ingin langsung eksekusi, pembuka yang paling benar adalah:

> Domain aktif: hutang

Lalu fokus kerjanya hanya:

- kunci policy pembayaran hutang
- larang edit langsung
- tentukan alternatif resmi saat salah input
- rapikan UI dan audit trail berdasarkan policy itu

Setelah domain hutang bersih, baru lanjut domain gaji end-to-end.
