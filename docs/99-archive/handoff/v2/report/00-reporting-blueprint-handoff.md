# Handoff V2 - Reporting Blueprint, Exactness Guard, and Dashboard Data Contract

Tanggal: 2026-04-14

## Ringkasan Singkat

Halaman ini mengunci blueprint reporting v2 untuk admin.

Fokus utamanya bukan membuat domain baru, tetapi menata ulang pelaksanaan
reporting agar:

- side menu laporan jelas
- dashboard memakai data nyata
- angka report, dashboard, PDF, dan Excel konsisten
- tidak ada nol palsu setelah data kasir masuk
- selisih 1 rupiah diperlakukan sebagai defect kritikal
- jalur test bersifat ketat dan menyeluruh

Status blueprint ini:

- logic report inti terkunci
- dashboard contract v1 terkunci
- real data consistency contract terkunci
- urutan implementasi terkunci
- file implementasi belum dibuat pada halaman ini

---

## Referensi Wajib

Dokumen ini harus dibaca bersama referensi berikut:

- `docs/adr/0009-reporting-as-read-model.md`
- `docs/adr/0006-costing-strategy-default-average-fifo-ready.md`
- `docs/workflow/workflow_v1.md`
- `docs/handoff/handoff_step_12_reporting_v1_closed.md`
- `resources/views/admin/dashboard/index.blade.php`

Jika implementasi melanggar salah satu referensi ini, blueprint ini yang
menjadi pengunci arah kerja reporting v2.

---

## Tujuan Blueprint

Blueprint ini dibuat untuk mengunci:

1. side menu laporan admin
2. kontrak logic 7 laporan inti
3. kontrak dashboard sebagai consumer
4. kontrak data real agar request baca setelah commit melihat angka terbaru
5. kontrak PDF dan Excel agar satu source dengan screen
6. urutan implementasi report-by-report
7. jalur test monster agar exactness terlindungi

---

## Scope

### Scope in

- laporan admin
- dashboard data contract
- period mode harian, mingguan, bulanan, custom
- PDF dan Excel parity
- real-data consistency
- test strategy
- execution workflow
- split file discipline

### Scope out

- implementasi code di halaman ini
- wiring endpoint final
- styling UI baru
- redesign dashboard blade
- bot / Telegram / WA
- accounting ledger formal
- fixed asset non-persediaan

---

## Keputusan Inti yang Sudah Dikunci

### 1. Reporting adalah read model

Reporting tetap read model atas data domain final.

Reporting bukan source of truth baru.

Reporting tidak boleh memperkenalkan business rules inti baru yang tidak ada di
core.

### 2. Exactness adalah aturan keras

Selisih 1 rupiah antara:

- report dan source domain
- dashboard dan report
- screen dan PDF
- screen dan Excel
- agregasi harian dan mingguan
- agregasi mingguan dan bulanan

diperlakukan sebagai defect kritikal.

### 3. Dashboard adalah consumer, bukan kalkulator

Dashboard hanya menampilkan:

- ringkasan
- grafik
- insight
- kesimpulan

Dashboard tidak boleh menghitung ulang angka bisnis inti di frontend.

### 4. Data harus real

Setelah mutation domain berhasil commit, request baca berikutnya harus melihat
angka terbaru untuk metric kritis.

Jika dashboard menampilkan nol padahal data domain sudah ada, itu bug.

### 5. Hybrid ketat dipilih

Model yang dipakai untuk freshness adalah hybrid ketat:

- metric kritis wajib fresh pada request berikutnya
- chart berat boleh memakai source hybrid
- jika source ringkasan belum fresh, fallback ke source live
- nol palsu tidak boleh tampil

### 6. Export harus satu source

PDF dan Excel bukan query lain.

PDF dan Excel harus memakai dataset backend yang sama dengan screen report.

### 7. File code kecil dan terpecah

Target file code maksimal sekitar 100 baris.

Jika melewati sekitar 100 baris, pecah file berdasarkan peran.

Contoh pemecahan yang disarankan:

- query
- dto
- builder
- reconciliation service
- handler
- controller
- transformer
- export mapper

Markdown docs tidak wajib mengikuti target ini, tetapi tetap dijaga ringkas dan
terbaca.

---

## Side Menu Laporan Final

Menu laporan admin dikunci sebagai berikut:

1. Laporan Transaksi
2. Arus Kas Transaksi
3. Hutang Supplier
4. Stok dan Nilai Persediaan
5. Laba Kas Operasional
6. Biaya Operasional
7. Hutang Karyawan

Catatan:

- `Jatuh Tempo` hidup sebagai bagian dari Laporan Hutang Supplier
- `Nilai Aset` pada konteks ini berarti `Nilai Persediaan Saat Ini`
- `Uang Masuk`, `Trend`, `Grafik`, dan `Kesimpulan` hidup di dashboard
- `Index` dan `Riwayat` operasional tidak dibahas di halaman ini

---

## Kontrak Global Semua Laporan

Semua laporan wajib mengikuti pola global berikut.

### Mode periode

Setiap report wajib mendukung:

- harian
- mingguan
- bulanan
- custom range

### Struktur output minimum

Setiap report wajib punya:

- summary cards
- total utama
- tabel report
- agregasi per periode
- data source yang bisa diekspor

### Output channel

Setiap report wajib punya satu dataset backend yang bisa dipakai untuk:

- screen
- PDF
- Excel
- dashboard widget terkait

### Parity rule

Untuk filter yang sama:

- screen = PDF = Excel
- card dashboard = report summary source
- chart dashboard = report period source

### No stale-zero policy

Jika data domain sudah ada, report atau dashboard tidak boleh menampilkan nol
palsu.

Untuk widget non-kritis, loading atau status refresh masih boleh.

Untuk widget kritis, wajib fallback ke source live.

---

## Kontrak 7 Laporan Inti

## 1. Laporan Transaksi

### Tujuan

Menampilkan transaksi per note pada periode transaksi.

### Grain

1 baris = 1 note

### Basis tanggal

- `notes.transaction_date`

### Source domain

- `notes`
- `work_items`
- `customer_payments`
- `payment_allocations`
- `customer_refunds`

### Angka utama

- gross transaction rupiah
- allocated payment rupiah
- refunded rupiah
- net cash collected rupiah
- outstanding rupiah

### Status tampilan

- belum dibayar
- sebagian
- lunas
- refund terjadi

### Dashboard consumer

Dipakai untuk:

- total transaksi periode
- total gross transaksi
- outstanding transaksi
- refund transaksi

### Export

PDF dan Excel wajib memakai dataset summary per note yang sama.

---

## 2. Arus Kas Transaksi

### Tujuan

Menampilkan event kas transaksi pelanggan.

### Grain

1 baris = 1 event kas untuk 1 note

### Basis tanggal

- payment memakai `customer_payments.paid_at`
- refund memakai `customer_refunds.refunded_at`

### Source domain

- `payment_allocations`
- `customer_payments`
- `customer_refunds`
- `notes`

### Event v1

- `payment_allocation`
- `refund`

### Angka utama

- cash in
- cash out
- net amount

### Dashboard consumer

Dipakai untuk:

- uang masuk
- cash out refund
- grafik uang masuk
- grafik cash in vs cash out

### Guard

`payment_allocations` tidak punya tanggal sendiri.

Tanggal allocation wajib mengikuti parent payment.

---

## 3. Hutang Supplier

### Tujuan

Menampilkan posisi hutang supplier per invoice, termasuk status jatuh tempo.

### Grain

1 baris = 1 supplier invoice

### Basis tanggal

- filter utama memakai `supplier_invoices.tanggal_pengiriman`

### Source domain

- `supplier_invoices`
- `supplier_receipts`
- `supplier_receipt_lines`
- `supplier_payments`

### Angka utama

- grand total rupiah
- total paid rupiah
- outstanding rupiah
- receipt count
- total received qty

### Jatuh tempo

Status dihitung terhadap `reference_date`.

Default:

- `reference_date = end_date`

### Status jatuh tempo

- lunas
- belum jatuh tempo
- jatuh tempo hari ini
- lewat jatuh tempo

### Dashboard consumer

Dipakai untuk:

- total outstanding supplier
- total overdue
- jumlah overdue invoice
- top supplier outstanding

### Guard

`proof_status` tidak memfilter angka finansial.

---

## 4. Stok dan Nilai Persediaan

### Tujuan

Memisahkan histori stok dari current snapshot.

### Struktur

Satu menu, dua bagian:

- mutasi stok
- posisi stok dan nilai persediaan saat ini

### Bagian A: Mutasi Stok

#### Grain

1 baris = 1 product dengan movement dalam scope

#### Basis tanggal

- `inventory_movements.tanggal_mutasi`

#### Source domain

- `inventory_movements`

#### Angka utama

- qty in
- qty out
- net qty delta
- total in cost rupiah
- total out cost rupiah
- net cost delta rupiah

### Bagian B: Posisi Stok dan Nilai Persediaan Saat Ini

#### Grain

1 baris = 1 product pada current snapshot

#### Source domain

- `product_inventory`
- `product_inventory_costing`

#### Angka utama

- qty on hand
- avg cost rupiah
- inventory value rupiah

### Dashboard consumer

Dipakai untuk:

- nilai persediaan saat ini
- total qty stok saat ini
- grafik mutasi stok masuk vs keluar
- distribusi nilai persediaan
- stok menipis
- barang paling rame

### Guard

Snapshot current state tidak boleh dipakai untuk mengarang histori.

---

## 5. Laba Kas Operasional

### Tujuan

Menjawab definisi client:

uang masuk dikurangi harga beli produk, operasional, gaji, dan hutang
karyawan.

### Catatan penting

Ini adalah report baru.

Ini tidak boleh diam-diam mengganti `operational_profit_summary` lama tanpa
keputusan eksplisit.

### Nama kerja

- `cash_operational_profit_summary`

### Source utama

- arus kas transaksi
- biaya operasional
- payroll disbursement
- domain hutang karyawan
- external purchase cost
- stock COGS

### Rumus inti

`laba_kas_operasional_rupiah` =
`uang_masuk_rupiah`
- `refund_rupiah`
- `harga_beli_produk_rupiah`
- `operational_expense_rupiah`
- `payroll_disbursement_rupiah`
- `employee_debt_cash_out_rupiah`

### Dashboard consumer

Dipakai untuk:

- laba kas operasional
- grafik laba harian
- grafik laba mingguan
- grafik laba bulanan
- kesimpulan minus atau profit

### Guard

Jalur `employee_debt_cash_out_rupiah` wajib dibuktikan dari repo sebelum
implementasi final.

Tidak boleh diasumsikan.

---

## 6. Biaya Operasional

### Tujuan

Menampilkan seluruh biaya operasional posted.

### Grain

1 baris = 1 operational expense entry

### Basis tanggal

- `operational_expenses.expense_date`

### Inclusion rule

Hanya:

- `status = posted`

### Source domain

- `operational_expenses`
- `expense_categories` bila diperlukan untuk summary

### Angka utama

- total biaya operasional
- jumlah entry posted
- kategori terbesar
- rata-rata harian

### Dashboard consumer

Dipakai untuk:

- biaya operasional periode
- grafik biaya per periode
- kategori biaya terbesar

### Guard

Report ini tidak boleh mencampur:

- payroll
- hutang karyawan
- COGS
- supplier payment
- refund

---

## 7. Hutang Karyawan

### Tujuan

Menampilkan posisi dan histori hutang karyawan per debt record.

### Grain

1 baris = 1 debt record

### Basis tanggal

- `employee_debts.created_at`

### Source domain

- `employee_debts`
- `employee_debt_payments`

### Inclusion rule

Semua debt dalam scope tanggal masuk report, termasuk status `paid`.

### Angka utama

- total debt
- total paid back
- remaining balance
- status

### Dashboard consumer

Dipakai untuk:

- total sisa hutang karyawan
- jumlah hutang aktif
- trend hutang karyawan

### Guard

Report ini tidak mencampur payroll.

---

## Kontrak Dashboard V1

Dashboard existing dianggap tetap dipakai.

Yang berubah hanyalah source datanya.

### Kartu ringkas utama

1. uang masuk
2. biaya operasional
3. laba kas operasional
4. outstanding hutang supplier
5. nilai persediaan saat ini
6. total sisa hutang karyawan

### Grafik utama

1. uang masuk per periode
2. biaya operasional per periode
3. laba kas operasional per periode
4. uang masuk vs total pengeluaran
5. mutasi stok masuk vs keluar
6. top produk paling rame
7. distribusi nilai persediaan
8. outstanding supplier per bucket jatuh tempo

### Grafik tambahan v1.1

9. trend hutang karyawan
10. kategori biaya operasional terbesar
11. hari paling rame bulan ini
12. top supplier outstanding

### Insight final yang dikunci

- barang paling rame = by qty
- hari paling rame bulan ini = by jumlah transaksi
- distribusi nilai persediaan = by produk

### Kesimpulan otomatis

Kesimpulan hanya boleh berasal dari perbandingan angka resmi antar periode.

Tidak boleh dibuat dari rumus liar di frontend.

---

## Real Data Consistency Contract

Kontrak ini wajib dijaga pada semua implementasi reporting dan dashboard.

### Read-after-write

Setelah mutation domain sukses commit, request baca berikutnya untuk metric
kritis wajib melihat data terbaru.

### Metric kritis yang wajib fresh

- uang masuk
- biaya operasional
- laba kas operasional
- outstanding hutang supplier
- nilai persediaan saat ini
- total sisa hutang karyawan

### Widget non-kritis

Chart berat dan insight boleh hybrid, tetapi:

- tidak boleh nol palsu
- jika source ringkasan belum fresh, fallback ke source live
- atau tampilkan status refresh, bukan angka palsu

### No JS business calculation

Frontend hanya merender data backend.

Frontend tidak boleh menghitung ulang angka bisnis.

---

## PDF dan Excel Contract

PDF dan Excel adalah adapter output.

Keduanya wajib memakai dataset backend yang sama dengan screen.

### Aturan wajib

- filter sama
- source sama
- total sama
- rounding tambahan tidak boleh
- formatting tidak boleh mengubah nilai

### Consequence

Jika screen, PDF, dan Excel berbeda untuk filter yang sama, itu defect kritikal.

---

## Urutan Implementasi Final

Urutan implementasi dikunci sebagai berikut:

1. Arus Kas Transaksi
2. Biaya Operasional
3. Hutang Karyawan
4. Laba Kas Operasional
5. Hutang Supplier
6. Stok dan Nilai Persediaan
7. Laporan Transaksi
8. Dashboard wiring
9. PDF dan Excel parity

### Alasan urutan

Urutan ini mengikuti kebutuhan client yang cash-first.

Laba kas tidak boleh dibangun sebelum source uang masuk, biaya operasional,
dan hutang karyawan stabil.

Dashboard tidak boleh di-wire sebelum report sumber stabil.

Export tidak boleh dibangun sebagai jalur query baru.

---

## Struktur Implementasi yang Disarankan

Berikut struktur implementasi yang aman dan mudah diaudit.

### Application

- `app/Application/Reporting/DTO`
- `app/Application/Reporting/UseCases`
- `app/Application/Reporting/Services`
- `app/Application/Reporting/Exports`
- `app/Application/Reporting/Presenters`

### Ports

- `app/Ports/Out/Reporting`
- `app/Ports/In/Reporting` bila diperlukan

### Adapters Out

- `app/Adapters/Out/Reporting`
- `app/Adapters/Out/Reporting/Queries`
- `app/Adapters/Out/Reporting/Exports`

### Adapters In

- `app/Adapters/In/Http/Controllers/Admin/Reporting`
- `app/Adapters/In/Http/Requests/Admin/Reporting`

### Tests

- `tests/Feature/Reporting`
- `tests/Feature/Dashboard`
- `tests/Feature/ReportingExports`

### Split rule

Jika satu file cenderung melewati sekitar 100 baris, pecah berdasarkan peran.

Jangan menumpuk:

- query
- transform
- aggregation
- reconciliation
- export mapping
- response formatting

di satu file besar.

---

## Jalur Test Monster

### 1. Formula exactness test

Setiap report wajib punya fixture kecil yang bisa dihitung tangan.

Assert semua komponen dan hasil akhir.

### 2. Read-after-write test

Setelah mutation commit:

- buka report
- buka dashboard
- angka harus berubah

### 3. No stale-zero test

Jika data domain ada, report dan dashboard tidak boleh menampilkan nol palsu.

### 4. Period reconciliation test

- total harian 7 hari = mingguan
- total harian 1 bulan = bulanan
- custom range = sum detail dalam range

### 5. Screen vs export parity test

- screen = PDF
- screen = Excel
- PDF = Excel

### 6. Cross-report reconciliation test

Contoh wajib:

- biaya operasional report = komponen biaya operasional di laba kas
- uang masuk report = kartu uang masuk dashboard
- nilai persediaan report = kartu persediaan dashboard
- hutang supplier report = widget hutang supplier dashboard

### 7. Boundary date test

Event di batas hari, akhir minggu, dan akhir bulan harus masuk bucket yang
benar.

### 8. Fallback live-source test

Jika source summary belum fresh, widget kritis harus fallback ke source live.

### 9. Concurrency test

Dua mutation cepat berturut-turut tidak boleh membuat angka akhir salah.

---

## Checklist Exit Per Tahap

Setiap report dianggap selesai jika semua syarat berikut terpenuhi:

- kontrak logic sudah terkunci
- basis tanggal jelas
- grain jelas
- summary dan total jelas
- mode harian, mingguan, bulanan hidup
- PDF dan Excel source sama
- dashboard dependency jelas
- feature test hijau
- reconciliation test hijau
- no stale-zero test hijau
- file code tetap kecil dan terpecah

---

## Open Guard yang Tidak Boleh Dilangkahi

1. Jalur `employee_debt_cash_out_rupiah` harus dibuktikan dari repo.
2. Dashboard tidak boleh membuat angka sendiri di JS.
3. Export tidak boleh memakai query alternatif.
4. Current snapshot tidak boleh dipakai mengarang histori.
5. Selisih 1 rupiah tidak boleh ditoleransi.

---

## Rekomendasi Halaman Kerja Berikutnya

Setelah blueprint ini dibuat, halaman kerja berikutnya harus fokus tunggal:

1. Arus Kas Transaksi
2. Biaya Operasional
3. Hutang Karyawan
4. Laba Kas Operasional
5. report berikutnya sesuai urutan

Jangan mulai dari dashboard.

Jangan mulai dari PDF.

Jangan mulai dari Excel.

Jangan menyatukan banyak report dalam satu halaman implementasi.

---

## Status Final Blueprint

### Locked

- side menu laporan final
- kontrak 7 laporan inti
- dashboard v1 chart-heavy contract
- real data consistency contract
- PDF dan Excel parity contract
- execution order
- monster test strategy
- split file discipline

### Not implemented here

- code reporting baru
- endpoint
- export
- dashboard wiring real data
- test file baru

