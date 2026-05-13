# ADR-0021 — Note Detail Hybrid Model: Versioning + Billing Projection + Simple Refund

## Status
ACCEPTED

## Tanggal
2026-04-21

## Context
Halaman detail nota kasir sebelumnya sudah dimigrasikan dari panel lama ke launcher + modal. Slice itu sudah selesai dan lolos regression.

Namun setelah evaluasi domain dan UX lebih dalam, ditemukan bahwa interaction model launcher-per-row belum cukup cocok dengan realitas operasional user. Domain nota kasir/bengkel memiliki karakteristik berikut:

- 1 nota dapat berisi campuran:
  - produk toko
  - service only
  - service x product
  - produk luar x service
- payment sebenarnya tidak mengenal cicilan panjang. Secara praktik hanya ada:
  - bayar awal / DP
  - bayar lunas
- refund jarang terjadi dan untuk fase ini harus disederhanakan.
- edit nota tidak boleh menjadi edit in-place yang merusak histori. Edit harus menjadi revisi/versioning.
- sistem harus mengikuti user lapangan, bukan memaksa user mengikuti model UI yang terlalu generik.

Selain itu, repo sudah memiliki pola versioning detail yang konsisten pada:
- detail produk
- detail karyawan

Repo juga sudah memiliki sistem global feedback yang tersentral di:
- `resources/views/layouts/partials/alerts.blade.php`

Keputusan baru dibutuhkan agar next phase tidak ambigu.

## Problem Statement
Dibutuhkan model detail nota baru yang:
- tetap mudah dipakai kasir
- memisahkan pembacaan line domain dan seleksi tagihan
- mendukung DP vs lunas tanpa membuat engine pembayaran ganda
- mendukung refund yang sederhana dan aman
- menjaga histori nota melalui versioning
- tidak membuka domain operasional adjustment pada fase ini

## Considered Options

### Opsi 1 — Semua aksi disatukan dalam satu action bar checklist
- line dicentang
- semua aksi muncul bersama
- edit, bayar, lunasi, refund jadi satu keluarga

#### Kelemahan
- edit secara semantik bukan aksi keuangan
- mixed state sangat rumit
- service x product tetap bentrok dengan model line mentah
- refund dan payment saling mengganggu

### Opsi 2 — Semua intent-first
- semua aksi dibuka dari tombol
- selection selalu di dalam modal

#### Kelemahan
- refund menjadi kurang hati-hati
- user kehilangan rasa “pilih yang dibatalkan”
- kurang sesuai preferensi lapangan

### Opsi 3 — Hybrid
- edit nota = versioning
- pembayaran = intent-first
- refund = selection-first
- line domain tetap dibaca sebagai line asli
- payment memakai billing projection rows
- refund memakai line domain selection
- operasional adjustment ditunda

#### Kelebihan
- paling cocok dengan domain
- paling cocok dengan kebiasaan user
- audit trail tetap hidup
- DP vs lunas dapat dijelaskan dengan preset selection
- refund tetap aman dan sederhana

## Decision
Dipilih **Opsi 3 (Hybrid)**.

## Final Architecture Decision

### 1. Edit Nota = Versioning
Edit nota tidak dilakukan in-place.

Edit nota harus membuka alur revisi/versioning, dengan sifat:
- nota saat ini tetap punya histori
- revisi berikutnya menjadi versi baru
- versi lama tetap dapat dibaca
- pola presentasi mengikuti gaya detail produk dan detail karyawan:
  - current state
  - initial state / baseline
  - revision timeline

Edit nota **bukan** bagian dari action checklist keuangan.

### 2. Detail page memakai dua layer baca/aksi
#### A. Line Domain Layer
Halaman detail tetap menampilkan line domain asli agar user tetap merasa “1 nota berisi line”.

#### B. Billing Projection Layer
Untuk kebutuhan pembayaran, UI tidak memakai line domain mentah sebagai basis selection utama.

Sebagai gantinya, UI memakai **billing projection rows**, yaitu proyeksi tagihan yang boleh memecah 1 line domain menjadi beberapa row UI.

Contoh:
- service only -> 1 billing row: jasa
- product only -> 1 billing row: produk
- service x product -> 2 billing rows:
  - produk
  - jasa
- produk luar x service -> 2 billing rows:
  - produk luar
  - jasa

Line domain tetap satu kesatuan di backend. Pemecahan hanya terjadi di layer UI / projection.

### 3. Payment model disederhanakan menjadi 2 intent utama
#### A. Bayar
- tombol level pembayaran
- membuka modal
- default selection kosong
- dipakai untuk pembayaran manual
- DP diperlakukan sebagai preset/use case dari sistem pembayaran yang sama, bukan engine baru

#### B. Lunasi Pembayaran
- tombol level pembayaran
- membuka modal
- default selection otomatis untuk semua billing rows yang masih outstanding / partial
- billing rows yang sudah fully paid tetap terlihat tapi disabled/samar

### 4. DP bukan engine terpisah
Tidak ada kebutuhan engine DP terpisah.

DP dipahami sebagai:
- bagian dari sistem `Bayar`
- dengan preset selection default yang fokus ke komponen produk

Secara UI:
- tombol utama tetap `Bayar`
- modal `Bayar` dapat memiliki mode/preset DP
- kalau mode DP aktif, default selection memprioritaskan billing rows yang mengandung produk
- jasa tidak masuk default selection DP kecuali user memilih manual

### 5. Refund = simple reversal
Refund pada fase ini disederhanakan.

Refund diputuskan sebagai:
- aksi pembatalan transaksi
- kalau ada uang yang memang harus balik, uang balik
- kalau ada produk toko, stok kembali ke toko
- domain operasional adjustment tidak dibuka pada fase ini

#### Basis selection refund
Refund memakai **line domain selection**, bukan billing projection rows.

UI dapat menampilkan preview dampak refund, tetapi yang dipilih user tetap line domain.

#### External / produk luar
Untuk fase ini dipilih simplifikasi:
- **opsi A**
- refund external product tidak memicu stock return ke toko
- untuk mempermudah fase awal, uang external purchase tidak perlu dibuat rumit secara accounting terpisah
- treat external product refund secara sederhana agar tidak membuka domain operasional/procurement terlebih dahulu

Makna praktis:
- refund fokus pada reversal sederhana
- tidak membuka kasus lanjutan procurement settlement pada fase ini

### 6. Operasional adjustment ditunda
Tidak ada modul/aksi operasional khusus pada fase ini.

Tidak dibuka dulu:
- tambah biaya operasional
- tambah uang operasional
- adjustment tengah jalan yang bukan payment/refund/versioning

Semua itu sengaja ditunda agar fase ini tetap sederhana.

## Consequences

### Positif
- UX lebih dekat dengan cara user berpikir
- DP vs lunas menjadi jelas
- refund tidak dipaksa jadi granular yang berlebihan
- edit nota tetap aman lewat versioning
- domain line tetap utuh
- projection UI menjadi alat bantu, bukan sumber kebenaran utama

### Negatif
- UI menjadi hybrid, tidak 100% simetris
- test matrix bertambah
- butuh projection builder baru untuk billing rows
- butuh refund preview logic baru
- perlu redesign contract presentasi detail page sekali lagi

## Non-Goals
Phase ini tidak mencakup:
- operasional adjustment
- procurement/external settlement yang kompleks
- component-level refund granular
- perubahan route payment/refund
- perubahan request contract payment/refund
- perubahan finance engine mendasar
- perubahan reporting besar

## Preserved Contracts
Pada fase implementasi berikutnya, contract berikut harus dipertahankan:
- route payment existing
- route refund existing
- request payment/refund existing
- selected-row contract existing sampai ada keputusan eksplisit baru
- global feedback system
- versioning style detail page yang sudah hidup di repo
- immutable financial event mindset

## Required Follow-up
Sebelum implementasi, wajib dilakukan:
1. full audit teknis terhadap file detail note page
2. mapping file yang harus:
   - dihapus
   - diganti
   - ditambah
3. mapping test yang terdampak
4. definisi projection builder untuk billing rows
5. definisi refund preview sederhana
6. definisi minimal versioning note detail UX

## Execution Rule for Next Chat
Chat berikutnya tidak boleh langsung patch.

Chat berikutnya wajib:
1. baca ADR ini
2. baca handoff terkait
3. audit dulu
4. jika ada data kurang, berhenti dan bertanya dengan opsi + plus/minus
5. baru setelah data cukup, siapkan implementation plan
6. patch diberikan via command terminal dari root

