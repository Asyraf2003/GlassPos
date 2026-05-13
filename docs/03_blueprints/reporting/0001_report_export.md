# PDF and Excel Export for Reports and Dashboard Blueprint

## Status
Draft.

## Metadata
- Date: 2026-05-01
- Scope: Reporting, Dashboard, PDF Export, Excel Export
- Project: Hyperpos

## Goal

Membangun export PDF dan Excel untuk laporan dan dashboard tanpa mismatch angka terhadap screen.

Export harus menjadi adapter output dari dataset laporan/dashboard yang sudah stabil, bukan query baru, bukan hitung ulang di Blade, bukan hitung ulang di JavaScript, dan bukan scrape DOM.

Target utama:

screen = PDF = Excel untuk angka utama
filter screen = filter export
summary = detail reconciliation
dashboard export tidak menjadi source of truth
laporan tetap membaca domain final
export tidak memperlambat page load dashboard/report
PDF aman untuk cetak bulanan
Excel aman untuk analisis maksimal satu tahun
## Background

Reporting V2 sudah mengunci bahwa export hanya boleh dibuat setelah screen/report stabil.

Export tidak boleh dimulai sebelum kontrak data jelas karena risiko utamanya bukan layout, melainkan mismatch angka.

Risiko yang harus dicegah:

screen menampilkan angka benar, PDF angka lain
Excel memakai query sendiri lalu total berbeda
dashboard chart memakai angka yang tidak reconcile dengan report
PDF mencetak range terlalu besar dan tidak berguna dibaca
Excel menyimpan rupiah sebagai string sehingga sulit dianalisis
export membuat dashboard/report normal menjadi lambat
## Source of Truth Rule

Report dan dashboard export harus memakai dataset/use case yang sama dengan screen.

Flow resmi:

Request filter
→ Controller
→ Application use case
→ Reporting reader/query
→ Report dataset / dashboard payload
→ Screen renderer
→ PDF renderer
→ Excel renderer

Larangan:

query export terpisah dari query screen
hitung ulang angka bisnis di Blade
hitung ulang angka bisnis di JavaScript
export dari DOM
export dari chart rendered data
export dengan filter yang berbeda dari screen
formatting mengubah nilai numeric
## Export Types
### Report PDF

Tujuan:

cetak bulanan
arsip manusia
diskusi owner/admin
bukti ringkas laporan periode

Policy:

basis periode: 1 bulan
maksimal range: 1 bulan kalender
layout harus readable saat dicetak
summary tampil di awal
detail table boleh dipaginasi
angka harus sama dengan screen
### Report Excel

Tujuan:

analisis
rekonsiliasi
filter manual
pivot akuntansi
audit operasional

Policy:

maksimal range: 1 tahun / 366 hari
value rupiah harus numeric integer
formatting rupiah hanya visual Excel number format
data table utama tidak boleh memakai merged cells
wajib punya metadata sheet
wajib punya summary sheet
wajib punya detail sheet
boleh punya reconciliation sheet bila report kompleks
### Dashboard PDF

Tujuan:

snapshot bulanan dashboard untuk owner
ringkasan posisi bisnis periode berjalan/bulan tertentu
cetak satu paket ringkas

Policy:

basis periode: 1 bulan
bukan pengganti laporan detail
berisi summary, indikator utama, dan konteks dashboard
chart boleh tampil sebagai visual, tetapi angka chart bukan source of truth
dashboard PDF harus mencantumkan report source/period metadata
### Dashboard Excel

Tujuan:

workbook analisis dari dashboard
breakdown data dashboard dalam sheet terpisah
validasi lintas metric

Policy:

maksimal range: 1 tahun
workbook multi-sheet
setiap sheet mewakili metric/section
summary dashboard harus reconcile ke report source
tidak boleh memakai chart/JS sebagai sumber data
Period Policy
PDF

PDF memakai basis cetak bulanan.

Aturan:

input utama: month=YYYY-MM
date_from = awal bulan
date_to = akhir bulan atau today untuk bulan aktif bila policy screen sudah seperti itu
custom date range hanya boleh jika tidak melebihi 1 bulan dan tidak melanggar screen contract
jika range lebih dari 1 bulan, request PDF harus ditolak dengan pesan validasi yang jelas

Reason:

PDF untuk dibaca manusia. PDF tahunan untuk report detail biasanya berubah jadi novel akuntansi yang tidak ingin dibaca siapa pun, termasuk orang yang mencetaknya.

Excel

Excel memakai basis analisis.

Aturan:

input boleh month, year, atau custom date range
maksimal 366 hari
jika lebih dari 366 hari, request ditolak
export lebih dari 1 tahun harus dipisah per tahun atau nanti masuk queued export bila terbukti perlu

Reason:

Excel dipakai untuk analisis dan rekonsiliasi, sehingga range lebih besar masih masuk akal.

Data Contract

Setiap export wajib punya contract berikut:

report_key
report_title
period_label
date_from
date_to
basis_date_label
generated_at
generated_by
filter_payload
summary_rows
detail_rows
totals
reconciliation_rows optional
source_dataset_name
source_screen_route
source_export_route
Metadata Contract

Setiap PDF/Excel wajib menampilkan metadata:

nama report/dashboard
periode
basis tanggal
waktu generate
actor/generator
filter aktif
source dataset/use case
note bahwa export memakai source yang sama dengan screen
halaman / page number untuk PDF
app/report version jika tersedia

GAP:

sumber resmi nama perusahaan/header belum dibuktikan.
sumber resmi timezone aplikasi belum dibuktikan.
source app version/export version belum dibuktikan.

Selama GAP belum ditutup, gunakan metadata minimal yang sudah tersedia dari request/session/config yang terbukti.

Formatting Contract
Rupiah

Rules:

internal value tetap integer rupiah
PDF boleh tampil Rp 15.000
Excel cell value harus numeric 15000
Excel number format boleh menampilkan Rp #,##0
tidak boleh menyimpan Rp 15.000 sebagai string di cell data utama
Date

Rules:

display Indonesia: dd-mm-yyyy
metadata boleh punya ISO date tambahan jika perlu
Excel cell date harus date-compatible jika library mendukung
jangan campur yyyy-mm-dd di UI export kecuali untuk metadata teknis
Quantity

Rules:

qty numeric
decimal policy mengikuti source report
jangan format qty menjadi string jika di Excel detail sheet
PDF Display Contract

Default:

ukuran: A4
dashboard: portrait
report summary: portrait
report detail table yang lebar: landscape
margin konsisten
header muncul minimal di halaman pertama
footer berisi page number, generated_at, dan report key
table header repeat di halaman baru jika library mendukung

PDF section order:

Header report
Metadata periode/filter
Summary cards/table
Reconciliation note jika ada
Detail table
Footer

PDF empty state:

jika tidak ada data, PDF tetap berhasil dibuat
tampilkan pesan empty state yang sama maknanya dengan screen
total harus nol
metadata tetap tampil

PDF forbidden:

chart sebagai satu-satunya bukti angka
table terlalu kecil sampai tidak terbaca
export lebih dari 1 bulan
angka hasil formatting yang tidak bisa ditelusuri ke dataset
Excel Display Contract

Workbook default sheets:

Metadata
Summary
Details
Reconciliation optional
Dashboard_* sheets untuk dashboard export

Sheet Metadata columns:

key
value

Sheet Summary columns:

metric
value
unit
notes optional

Sheet Details:

satu row per detail record
header frozen
autofilter enabled jika library mendukung
no merged cells
no decorative blank rows di area data utama
numeric columns tetap numeric
date columns tetap date/string konsisten sesuai library

Excel forbidden:

merged cells pada table detail utama
rupiah sebagai string
formula tersembunyi untuk total utama jika total sudah dihitung server
angka dihitung ulang oleh Excel sebagai source of truth
styling mengorbankan data usability
Report Coverage Plan

Export akan diterapkan bertahap sesuai urutan Reporting V2.

1. Laporan Transaksi

Priority: pertama untuk template export.

Reason:

dekat dengan Nota
memuat gross, payment, refund, outstanding
penting untuk audit operasional
cocok untuk membuktikan screen/PDF/Excel parity

Expected data:

note/order identity
transaction date
customer/name if available
gross amount
paid amount
refund amount
outstanding amount
status
payment/refund context if available

GAP:

nama class use case/query final harus diaudit dari repo sebelum implementasi.
exact column screen harus diaudit dari Blade/controller.
2. Arus Kas Transaksi

Expected data:

cash in
refund out
net cash
payment method
paid_at/refunded_at
note reference
actor/context if available
3. Biaya Operasional

Expected data:

expense date
category
description
amount
status posted
actor/context if available
4. Hutang Karyawan

Expected data:

employee
debt record
payment history
outstanding balance
period activity
5. Laba Kas Operasional

Expected data:

cash in
operational expense
debt cash out
payroll disbursement
stock COGS
external purchase cost
operational profit

Special rule:

report sintesis harus reconcile ke report sumber.
tidak boleh mengarang komponen yang source-nya belum terbukti.
6. Hutang Supplier

Expected data:

supplier
invoice
invoice date
due date
total invoice
paid amount
outstanding
overdue status
7. Stok dan Nilai Persediaan

Expected data:

product
current stock snapshot
movement period in/out
avg cost/value
basis movement date
warning if snapshot and movement period punya makna berbeda

Special rule:

movement history tidak boleh dicampur dengan current snapshot secara menyesatkan.
8. Dashboard

Dashboard export dilakukan setelah report export stabil.

Expected PDF sections:

period summary
cash summary
operational profit summary
inventory summary
supplier payable summary
top selling / ledger context
generated metadata

Expected Excel sheets:

Metadata
Dashboard Summary
Cashflow
Operational Performance
Inventory
Top Selling
Supplier Payable
Reconciliation Notes
Security and Access

Rules:

export route wajib berada di balik auth dan role/middleware yang sama dengan screen
user yang tidak bisa melihat report tidak boleh export report
export tidak boleh membuka data lewat public URL
filename tidak boleh mengandung data sensitif berlebihan
generated_by harus masuk metadata jika actor tersedia

Audit policy:

export action boleh dicatat sebagai audit event ringan bila report berisi data sensitif
audit export tidak boleh menyimpan isi file penuh
audit cukup menyimpan actor, report_key, filter, format, generated_at, row_count jika tersedia

GAP:

keputusan apakah semua export wajib audit log belum dikunci.
halaman audit akan membahas ini di scope terpisah.
Performance Policy

Rules:

export tidak boleh dieksekusi saat page load normal
page hanya menampilkan tombol/link export
export dihitung saat user klik
PDF 1 bulan boleh synchronous jika proof cepat
Excel 1 tahun boleh synchronous hanya jika proof aman
jika export berat, upgrade ke queued export di scope terpisah

Performance proof minimum per report:

export PDF 1 bulan tidak timeout
export Excel 1 tahun tidak timeout pada dataset uji realistis
normal page load tidak bertambah berat hanya karena tombol export
query count bounded
no N+1 brutal
Error Handling

Validation errors:

PDF range lebih dari 1 bulan
Excel range lebih dari 1 tahun
invalid month/year/date
user tidak punya akses
report key tidak dikenal

Response behavior:

screen request: redirect back with clear error
direct export request: show/download-safe error response sesuai existing app pattern
jangan leak SQL/error internal
Implementation Order
Commit blueprint/workflow/DoD docs.
Audit existing report screen contract.
Pilih Laporan Transaksi sebagai template pertama.
Lock export dataset contract untuk Laporan Transaksi.
Implement Excel first.
Implement PDF second.
Add parity tests.
Add route/controller tests.
Add performance sanity test.
Replicate pattern ke report lain.
Implement dashboard export terakhir.
Non-Goals

Scope ini tidak langsung membangun:

queued export
archive export storage
export scheduling
email export
import Excel
import PDF
audit log archive/purge
chart image rendering sebagai source data
redesign formula report
Invariants
Export tidak boleh mengubah state domain.
Export tidak boleh menjadi source of truth.
Export tidak boleh punya formula bisnis sendiri.
Screen, PDF, dan Excel harus memakai dataset yang sama.
PDF maksimal 1 bulan.
Excel maksimal 1 tahun.
Rupiah di Excel harus numeric.
Dashboard export adalah snapshot/analysis output, bukan report source.
Mismatch 1 rupiah atau 1 qty adalah failure.
