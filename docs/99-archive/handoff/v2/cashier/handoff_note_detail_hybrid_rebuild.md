# Handoff — Cashier Note Detail Hybrid Rebuild

## Metadata
- Tanggal: 2026-04-21
- Nama slice / topik: Cashier Note Detail Hybrid Rebuild
- Status: READY_FOR_AUDIT
- Progres fase keputusan: 100%
- Progres implementasi: 0%
- ADR terkait:
  - `docs/adr/0021-note-detail-hybrid-versioning-billing-refund.md`

## Tujuan halaman kerja berikutnya
Menyiapkan full audit teknis dan implementation plan untuk rebuild halaman detail nota kasir menjadi model hybrid:

- Edit Nota = versioning
- Payment = billing projection + modal
- Bayar dan Lunasi = dua intent yang jelas
- Refund = selection-first + simple reversal
- Operasional adjustment ditunda

Chat berikutnya harus membaca file ini dan ADR terkait, lalu melakukan **audit dulu** sebelum eksekusi.

## Ringkasan keputusan yang sudah final `[DECISION]`

### 1. Edit Nota
- Edit Nota **bukan** aksi keuangan.
- Edit Nota harus membuka alur **versioning/revision**.
- Gaya presentasi versi mengikuti pola detail produk dan detail karyawan:
  - state saat ini
  - data awal / baseline
  - timeline revisi

### 2. Payment menggunakan 2 intent utama
#### A. Bayar
- tombol `Bayar`
- membuka modal
- default checklist kosong
- dipakai untuk pembayaran manual
- DP bukan engine baru; DP adalah preset dari flow Bayar

#### B. Lunasi Pembayaran
- tombol `Lunasi Pembayaran`
- membuka modal
- default checklist otomatis untuk semua billing rows yang masih outstanding / partial
- billing rows yang sudah fully paid tetap terlihat tetapi disabled / samar

### 3. DP adalah preset dari sistem Bayar
- Tidak dibuat engine DP terpisah.
- Dalam modal Bayar, DP dipahami sebagai mode/preset yang memprioritaskan billing rows yang mengandung produk.
- Untuk service x product, default DP hanya memilih komponen produk, bukan jasa.

### 4. Refund
- Refund memakai **selection-first**.
- Refund bukan granular billing row selection.
- Refund berbasis **line domain selection**.
- Refund untuk fase ini adalah **simple reversal**:
  - jika ada uang yang relevan untuk dibalik, uang balik
  - jika ada produk toko, stok kembali
  - produk luar / external dipermudah dulu

### 5. External product refund
Dipilih simplifikasi:
- external product tidak memicu stock return ke toko
- fase ini tidak membuka complexity procurement/external settlement
- treat simple dulu

### 6. Operasional adjustment ditunda
Jangan buka dulu domain:
- tambahan uang operasional
- tambahan biaya operasional
- perubahan operasional tengah jalan yang bukan payment/refund/versioning

## Fakta yang sudah terkunci `[FACT]`

### A. Repo punya pola versioning detail yang bisa dijadikan acuan
- detail produk punya current state + initial state + timeline versi
- detail karyawan punya current state + initial state + timeline versi

### B. Repo punya global feedback system
- layout utama include `resources/views/layouts/partials/alerts.blade.php`
- feedback sukses/error harus tetap lewat sistem global ini
- jangan membuat sistem feedback liar lokal yang menyalahi pola repo

### C. Detail note presentation contract sudah pernah berubah
Slice sebelumnya sudah memindahkan detail note dari panel lama ke launcher + modal.
Slice baru ini adalah **contract change lanjutan** untuk menyesuaikan model hybrid.

### D. Domain line dan payment selection tidak boleh dipaksa identik
1 line domain bisa mengandung lebih dari 1 komponen tagihan.
Karena itu dibutuhkan **billing projection rows** untuk payment UI.

## Model final yang harus dipegang `[TARGET-MODEL]`

## Layer 1 — Domain Line Read Table
Halaman detail tetap menampilkan table line domain asli:
- untuk dibaca
- untuk memahami isi nota
- untuk basis refund selection
- bukan lagi basis utama payment selection

## Layer 2 — Billing Projection Table
Tambahkan table/projection khusus tagihan untuk payment:
- 1 line domain bisa menghasilkan 1..N billing rows UI
- ini dipakai untuk modal Bayar / Lunasi
- ini bukan sumber kebenaran domain, hanya projection UI

Contoh projection:
- service only -> row jasa
- product only -> row produk
- service x product -> row produk + row jasa
- produk luar x service -> row produk luar + row jasa

## Layer 3 — Payment Modals
### Bayar
- no default selection
- modal tetap dipakai
- mode/preset DP hidup di modal Bayar

### Lunasi Pembayaran
- default select seluruh billing rows outstanding/partial
- fully paid rows terlihat disabled

## Layer 4 — Refund Selection
- selection-first
- pilih line domain yang dibatalkan
- tombol refund aktif / terlihat sesuai keputusan UI nanti
- UI harus menampilkan dampak refund sederhana:
  - uang kembali / tidak
  - stok kembali / tidak
  - external diperlakukan sederhana

## Layer 5 — Note Versioning
- Edit Nota membuka revisi
- histori versi nota harus bisa dibaca
- jangan edit in-place

## Keputusan UI yang sudah dipilih `[UI-DECISION]`

### Decision 1
Dipilih **C**
- dua layer:
  - line domain tetap tampil untuk dibaca
  - billing projection table dipakai untuk aksi keuangan

### Decision 2
Dipilih **B**
- tidak ada tombol DP terpisah
- cukup tombol `Bayar`
- mode/preset DP hidup di modal Bayar

### Decision 3
Dipilih **C**
- refund berdasarkan line domain
- UI menunjukkan dampaknya, bukan refund granular per billing component

### Refund external product
Dipilih **A**
- disederhanakan dulu
- tidak membuka domain settlement external yang kompleks

## Scope yang dipakai `[SCOPE-IN]`
- redesign detail note ke model hybrid
- note versioning discovery/audit
- billing projection discovery/audit
- payment UI redesign audit
- refund simple reversal audit
- test impact mapping
- file impact mapping
- contract boundary mapping

## Scope yang tidak dipakai `[SCOPE-OUT]`
- operasional adjustment
- procurement / external settlement kompleks
- reporting redesign
- changes ke finance engine besar-besaran
- selected components contract final
- background reconciliation kompleks

## File yang kemungkinan besar terdampak `[FILES-CANDIDATE]`

### View / page shell
- `resources/views/cashier/notes/show.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`

### Payment / refund UI
- `resources/views/cashier/notes/partials/payment-modal.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`

### Kemungkinan partial baru
- `resources/views/cashier/notes/partials/billing-table.blade.php`
- `resources/views/cashier/notes/partials/payment-actions.blade.php`
- `resources/views/cashier/notes/partials/refund-actions.blade.php`
- kemungkinan partial version timeline note

### JS
- `public/assets/static/js/pages/cashier-note-payment.js`
- `public/assets/static/js/pages/cashier-note-refund.js`
- kemungkinan helper JS baru untuk:
  - billing selection state
  - refund selection preview
  - versioning launcher jika dibutuhkan

### Builder / controller / application service
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/NoteDetailPageController.php`
- kemungkinan builder baru:
  - `NoteBillingProjectionBuilder`
  - `NoteRefundPreviewProjector`
  - note version summary/timeline builder

### Tests yang hampir pasti terdampak
- detail page feature tests
- payment/refund interaction feature tests
- UI contract tests detail note
- tests baru untuk:
  - bayar no default selection
  - lunasi auto-select outstanding
  - DP preset selection
  - refund selection-first
  - external product refund simplification
  - note versioning detail

## Pertanyaan / gap yang masih harus diaudit `[GAPS]`
Chat berikutnya wajib audit ini dulu:

### A. Versioning note
- apakah note revision model/domain sudah ada?
- apakah timeline nota sudah ada atau harus dibuat baru?
- apakah edit nota membuat draft revision baru atau final revision langsung?
- bagaimana relasi revision note ke financial events lama?

### B. Billing projection
- data domain mana yang sudah cukup untuk membangun billing projection rows?
- apakah projection bisa dibangun murni read-side tanpa ubah engine?
- bagaimana representasi row untuk:
  - service only
  - product only
  - service x product
  - produk luar x service

### C. DP preset
- field/indikator apa yang membedakan product store vs external product di current domain?
- bagaimana menandai billing rows mana yang masuk preset DP?

### D. Refund simple reversal
- bagaimana menentukan “uang kembali / tidak” secara sederhana?
- bagaimana menentukan “stok kembali / tidak” untuk store product vs external product?
- apa konsekuensi note status setelah refund?

### E. Contract boundary
- mana contract yang wajib dipertahankan?
- mana yang boleh diubah di slice ini?

## Preserved contracts `[PRESERVED]`
Pada fase implementasi nanti, kecuali diputuskan ulang secara eksplisit, pertahankan:
- route payment existing
- route refund existing
- request payment/refund existing
- global feedback system
- immutable financial events
- style versioning detail page
- current regression baseline discipline

## Risk register `[RISK]`

### Risk 1
Projection UI terlalu jauh dari line domain
#### Mitigasi
- tampilkan line domain tetap sebagai read table terpisah

### Risk 2
DP preset ambigu
#### Mitigasi
- audit billing projection dulu sebelum patch
- jangan asumsi semua komponen produk seragam

### Risk 3
Refund external product membingungkan
#### Mitigasi
- pakai simplifikasi A dulu
- dokumentasikan eksplisit bahwa external dipermudah pada fase ini

### Risk 4
Versioning note terlalu besar scope-nya
#### Mitigasi
- audit terpisah dulu
- kalau perlu pecah implementation phase

## Next step paling aman `[NEXT]`
Chat berikutnya wajib melakukan:

### Step 1
Full audit teknis hybrid:
- map file remove / replace / add
- map contract boundary
- map test impact
- map builder baru yang dibutuhkan
- map domain gap

### Step 2
Jika ada data kurang:
- **jangan patch**
- berhenti dan tanyakan ke user dengan beberapa opsi + plus/minus

### Step 3
Jika data cukup:
- susun implementation plan bertahap
- baru keluarkan patch terminal dari root

## Aturan kerja untuk chat berikutnya `[EXECUTION-RULE]`
- jangan asumsi
- jangan langsung eksekusi
- kalau butuh keputusan, tanyakan dengan opsi dan plus/minus
- semua patch harus via command terminal dari root
- jaga line-limit dari awal
- ikuti pola repo existing
- gunakan feedback global existing
- jangan campur domain operasional ke slice ini

## Ringkasan singkat siap tempel

### Ringkasan
Target berikutnya adalah rebuild detail note menjadi model hybrid:
- Edit Nota = versioning
- Bayar = modal, default kosong
- Lunasi = modal, default outstanding selected
- DP = preset dalam flow Bayar
- Refund = selection-first, simple reversal
- Operasional ditunda

### Keputusan final
- architecture = hybrid
- detail read layer tetap line domain
- payment layer pakai billing projection rows
- refund layer pakai line domain selection
- external product refund = simple dulu
- feedback global existing tetap dipakai

### Jangan dibuka ulang
- operasional adjustment
- procurement/external settlement kompleks
- cicilan multi-step
- granular component refund penuh

### Data minimum untuk lanjut
- ADR 0021 ini
- handoff ini
- detail produk show
- detail karyawan show
- current note detail files
- test map impacted
- baseline verify hijau

