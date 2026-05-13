# Workflow Reporting V2 - Execution Order, Guard, and Test Discipline

Tanggal: 2026-04-14

## Tujuan

Dokumen ini menjabarkan alur kerja implementasi reporting v2 secara bertahap.

Workflow ini dibuat agar tim tidak:

- loncat ke dashboard dulu
- membuat export dengan query terpisah
- mencampur report, chart, dan logic domain
- menoleransi mismatch kecil
- menumpuk file besar yang sulit diaudit

Dokumen ini adalah workflow pelaksanaan dari blueprint:

- `docs/99-archive/handoff/v2/report/00-reporting-blueprint-handoff.md`

---

## Aturan Global Workflow

### 1. Kerjakan satu report aktif dalam satu halaman kerja

Satu halaman kerja hanya boleh punya satu fokus aktif.

Contoh aman:

- halaman 1 hanya Arus Kas Transaksi
- halaman 2 hanya Biaya Operasional
- halaman 3 hanya Hutang Karyawan

### 2. Jangan mulai dari dashboard

Dashboard hanya boleh di-wire setelah report sumber stabil.

### 3. Jangan mulai dari export

PDF dan Excel hanya boleh dibuat setelah dataset screen stabil.

### 4. Exactness lebih penting dari kosmetik

Jika harus memilih antara:

- tampilan cepat
- angka benar

pilih angka benar.

### 5. File kecil

Target file code sekitar 100 baris.

Jika melewati sekitar 100 baris, pecah file berdasarkan peran.

---

## Urutan Eksekusi Resmi

1. Arus Kas Transaksi
2. Biaya Operasional
3. Hutang Karyawan
4. Laba Kas Operasional
5. Hutang Supplier
6. Stok dan Nilai Persediaan
7. Laporan Transaksi
8. Dashboard wiring
9. PDF dan Excel parity

---

## Template Kerja Per Report

Setiap report harus melewati tahapan yang sama.

## Tahap A - Lock contract

Wajib dikunci dulu:

- tujuan report
- grain
- basis tanggal
- source domain
- inclusion rule
- summary cards
- total utama
- dashboard consumer
- export dependency

### Output Tahap A

- kontrak report tertulis
- tidak ada istilah ambigu yang belum dikunci

---

## Tahap B - Source mapping proof

Buktikan source domain yang dipakai memang ada dan sesuai repo.

Wajib dipetakan:

- tabel
- field tanggal
- field nominal
- status inclusion
- field relasi penting

### Output Tahap B

- daftar source final
- daftar guard
- gap yang tidak boleh diasumsikan

---

## Tahap C - Query dan builder

Bangun query dan builder sesuai kontrak.

Pisahkan file bila perlu:

- query raw
- dto
- builder
- reconciliation service
- use case handler

### Guard Tahap C

- jangan gabung semua logic di satu query class besar
- jangan campur query dengan formatting output
- jangan campur export mapping dengan query inti

---

## Tahap D - Reconciliation

Sebelum controller dan dashboard, report wajib punya reconciliation.

Jenis reconciliation minimum:

- detail ke summary
- harian ke mingguan
- mingguan ke bulanan
- report source ke report induk bila ada dependensi

---

## Tahap E - Screen contract

Baru setelah query dan reconciliation stabil, buka screen contract.

Wajib dikunci:

- filter
- summary cards
- table columns
- sort default
- empty state
- no stale-zero behavior

---

## Tahap F - Export contract

Setelah screen stabil, baru buka export.

Wajib dikunci:

- PDF source = screen source
- Excel source = screen source
- filter parity
- total parity
- formatting tidak mengubah nilai

---

## Tahap G - Dashboard dependency

Setelah report screen dan export stabil, baru hubungkan ke dashboard.

Wajib dikunci:

- widget mana consume report ini
- metric mana yang kritis
- apakah widget perlu fallback ke live source
- apakah chart boleh hybrid

---

## Tahap H - Feature test monster

Setelah semua kontrak di atas stabil, jalankan test monster.

---

## Jalur Test Wajib Per Report

## 1. Formula exactness

Dataset kecil, angka dapat dihitung tangan.

### Assert minimum

- semua komponen benar
- total akhir benar
- integer rupiah
- tidak ada pembulatan liar

## 2. Read-after-write

Setelah mutation domain commit:

- panggil report
- angka baru harus langsung terlihat

## 3. Period parity

- harian ke mingguan
- harian ke bulanan
- custom range ke detail

## 4. Detail vs summary

Total summary harus sama dengan total detail.

## 5. Screen vs export

- screen = PDF
- screen = Excel

## 6. Dashboard source parity

Widget dashboard yang memakai report ini harus sama dengan summary report.

## 7. Boundary test

Event di batas hari, minggu, bulan harus masuk bucket yang benar.

## 8. No stale-zero

Jika source domain ada, angka nol palsu tidak boleh tampil.

---

## Workflow Per Report

## Step 1 - Arus Kas Transaksi

### Fokus

Menjadikan uang masuk dan cash out transaksi sebagai source resmi pertama.

### Kenapa duluan

Ini fondasi definisi client untuk laba kas.

### Test prioritas

- payment allocation masuk report
- refund masuk report
- daily = weekly = monthly
- dashboard uang masuk reconcile

### Exit condition

Arus Kas Transaksi siap dipakai oleh Laba Kas Operasional dan dashboard.

---

## Step 2 - Biaya Operasional

### Fokus

Menjadikan biaya posted sebagai source resmi pengurang kas operasional.

### Test prioritas

- hanya `posted`
- total detail = total summary
- period parity
- dashboard biaya reconcile

### Exit condition

Biaya Operasional siap dipakai oleh Laba Kas Operasional dan dashboard.

---

## Step 3 - Hutang Karyawan

### Fokus

Menjadikan posisi hutang karyawan dan payment history resmi.

### Guard khusus

Jangan campur payroll.

### Test prioritas

- debt record masuk report
- payment history reconcile
- saldo hutang benar
- period parity

### Exit condition

Report Hutang Karyawan siap dipakai dashboard dan siap dijadikan source
pembuktian cash-out debt untuk Laba Kas Operasional.

---

## Step 4 - Laba Kas Operasional

### Fokus

Merakit report sintesis dari source yang sudah stabil.

### Source dependency

- Arus Kas Transaksi
- Biaya Operasional
- Hutang Karyawan
- payroll disbursement
- stock COGS
- external purchase cost

### Guard khusus

`employee_debt_cash_out_rupiah` tidak boleh diasumsikan.

Harus dibuktikan dari repo sebelum implementasi final.

### Test prioritas

- hari bisa minus
- minggu bisa positif
- bulan bisa sangat profit
- semua komponen reconcile
- selisih 1 rupiah = fail

### Exit condition

Laba Kas Operasional siap jadi kartu utama dan chart utama dashboard.

---

## Step 5 - Hutang Supplier

### Fokus

Posisi hutang supplier dan jatuh tempo.

### Test prioritas

- outstanding benar
- overdue bucket benar
- reference date benar
- dashboard supplier reconcile

### Exit condition

Hutang Supplier siap dipakai dashboard liability.

---

## Step 6 - Stok dan Nilai Persediaan

### Fokus

Memisahkan mutasi stok dari current snapshot.

### Struktur kerja

- bagian A mutasi
- bagian B snapshot

### Test prioritas

- histori memakai movement
- snapshot memakai current state
- tidak saling mengarang
- dashboard persediaan reconcile

### Exit condition

Nilai Persediaan Saat Ini siap jadi kartu dashboard.

---

## Step 7 - Laporan Transaksi

### Fokus

Ringkasan transaksi per note.

### Test prioritas

- gross
- allocated payment
- refund
- outstanding
- period parity

### Exit condition

Report transaksi siap dipakai untuk audit operasional dan summary transaksi.

---

## Step 8 - Dashboard wiring

### Fokus

Mengganti angka hard-code dashboard dengan data nyata.

### Urutan wiring

1. cards kritis
2. charts utama
3. insight
4. kesimpulan

### Guard

- widget kritis wajib fresh
- chart boleh hybrid dengan fallback
- nol palsu tidak boleh
- JS tidak menghitung ulang angka bisnis

### Test prioritas

- no stale-zero
- read-after-write
- widget parity ke report source

### Exit condition

Dashboard memakai data nyata yang reconcile dengan report.

---

## Step 9 - PDF dan Excel parity

### Fokus

Membuat export sebagai adapter output dari dataset yang sudah stabil.

### Guard

- jangan buat query export terpisah
- jangan ubah total lewat formatting
- filter parity wajib

### Test prioritas

- screen = PDF = Excel
- filter sama
- total sama

### Exit condition

Export aman untuk dipakai user tanpa risiko mismatch.

---

## Checklist Wajib Sebelum Menutup Satu Report

Satu report baru boleh dianggap selesai jika:

- kontrak logic tertulis
- source mapping terbukti
- file code kecil dan terpecah
- screen source stabil
- period parity lulus
- reconciliation lulus
- dashboard dependency jelas
- export parity lulus bila sudah dikerjakan
- read-after-write lulus
- no stale-zero lulus

---

## Anti-Pattern yang Dilarang

- mulai dari dashboard dulu
- mulai dari PDF dulu
- mulai dari Excel dulu
- membuat query dashboard terpisah dari report
- membuat query export terpisah dari screen
- menghitung angka di JS
- memakai current snapshot untuk mengarang histori
- membiarkan file query besar menumpuk banyak peran
- menoleransi selisih 1 rupiah

---

## Definisi Selesai Tahap Reporting V2

Reporting v2 baru dianggap sehat jika:

- 7 report inti hidup
- dashboard memakai data nyata
- card kritis fresh setelah commit
- chart tidak nol palsu
- PDF dan Excel parity
- period parity
- cross-report reconciliation
- exactness 1 rupiah terlindungi oleh test

---

## Rekomendasi Halaman Kerja Berikutnya

Gunakan workflow ini sebagai panduan halaman implementasi.

Urutan halaman implementasi yang aman:

1. Arus Kas Transaksi
2. Biaya Operasional
3. Hutang Karyawan
4. Laba Kas Operasional
5. Hutang Supplier
6. Stok dan Nilai Persediaan
7. Laporan Transaksi
8. Dashboard wiring
9. PDF dan Excel parity

Satu halaman kerja hanya mengerjakan satu langkah aktif.