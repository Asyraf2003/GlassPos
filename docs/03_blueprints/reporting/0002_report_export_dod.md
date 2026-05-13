# Report and Dashboard Export Definition of Done

## Status
Draft.
This file defines completion criteria for report and dashboard exports.
It is not proof that export implementation is complete.

## Metadata
- Date: 2026-05-01
- Scope: PDF and Excel export for reports and dashboard

## Purpose

Mengunci syarat selesai untuk export PDF dan Excel agar fitur tidak dianggap selesai hanya karena tombol download muncul.

Tombol download tanpa parity adalah dekorasi berbahaya. Cantik, bisa diklik, dan diam-diam menusuk laporan.

## Global DoD

Export baru boleh dianggap selesai jika semua poin berikut terpenuhi:

source screen terbukti
dataset export memakai source yang sama dengan screen
PDF tidak memakai query bisnis sendiri
Excel tidak memakai query bisnis sendiri
filter screen = filter export
summary screen = summary export
detail sum = summary
PDF maksimal 1 bulan
Excel maksimal 1 tahun
access control sama dengan screen
rupiah Excel numeric
PDF readable untuk cetak
export tidak memperlambat page load normal
targeted tests pass
relevant existing report tests pass
audit/performance proof dicatat bila scope menyentuh area itu
## Source Contract DoD

Wajib ada proof untuk:

route screen
route export PDF
route export Excel
controller
use case
read model/query
Blade/screen columns
filter inputs
date basis
status inclusion
total fields

Tidak boleh ada GAP pada:

source nominal utama
source tanggal utama
source status inclusion
source total utama

GAP yang masih boleh sementara:

styling final PDF
optional chart rendering
optional audit event for export
queued export
## PDF DoD

PDF export selesai jika:

hanya menerima range maksimal 1 bulan
metadata tampil
periode tampil
basis tanggal tampil
generated_at tampil
generated_by tampil jika actor tersedia
summary tampil
detail tampil atau empty state tampil
total sama dengan dataset
file dapat dibuka
filename aman dan jelas
tidak ada error internal bocor
access unauthorized ditolak

### PDF validation

range lebih dari 1 bulan ditolak
invalid date/month ditolak
user tanpa akses ditolak

### PDF proof

test generate PDF 1 bulan
test invalid range
test unauthorized
test content metadata/summary
manual open/download jika memungkinkan
## Excel DoD

Excel export selesai jika:

maksimal range 1 tahun / 366 hari
workbook punya Metadata sheet
workbook punya Summary sheet
workbook punya Details sheet
Reconciliation sheet ada bila report kompleks
rupiah numeric
qty numeric
date konsisten
summary sama dengan dataset
detail row count sama dengan dataset
filter metadata sama dengan request
file dapat dibuka
filename aman dan jelas
access unauthorized ditolak

### Excel validation

range lebih dari 1 tahun ditolak
invalid date/year/month ditolak
user tanpa akses ditolak

### Excel proof

test generate Excel 1 tahun
test invalid range
test unauthorized
test metadata sheet
test numeric rupiah
test summary/detail parity
## Screen PDF Excel Parity DoD

Wajib lulus:

screen summary = dataset summary
PDF summary = dataset summary
Excel summary = dataset summary
detail sum = summary
filter parity
period boundary parity
empty state parity
refund/payment/outstanding parity untuk Laporan Transaksi

### Mismatch rule

selisih 1 rupiah = fail
selisih 1 qty = fail
missing row = fail
wrong period inclusion = fail
## Dashboard Export DoD

Dashboard PDF selesai jika:

snapshot 1 bulan
tidak memakai JS/chart sebagai source data
summary reconcile dengan dashboard payload
metadata periode tampil
generated_at tampil
generated_by tampil jika tersedia
PDF readable
tidak memperlambat /admin/dashboard

Dashboard Excel selesai jika:

maksimal 1 tahun
workbook multi-sheet
sheet summary tersedia
metric sheets tersedia
value numeric
dashboard summary reconcile dengan source report/dashboard dataset
tidak memakai chart/DOM sebagai source data
## Performance DoD

Minimum:

normal screen page tidak mengeksekusi export generation
export action hanya berjalan saat route export dipanggil
query count tidak menunjukkan N+1 brutal
PDF 1 bulan selesai dalam threshold yang disepakati
Excel 1 tahun selesai dalam threshold yang disepakati atau ditandai sebagai queued candidate

### GAP

threshold angka final belum dikunci.
threshold harus diputuskan setelah audit dataset/report pertama.

Temporary threshold recommendation untuk local proof:

PDF 1 bulan: target < 3 detik pada dataset realistis
Excel 1 tahun: target < 10 detik pada dataset realistis
dashboard/report page normal: tidak boleh naik signifikan akibat tombol export
Security DoD

Wajib:

route export pakai auth
route export pakai middleware role/access yang sama dengan screen
forbidden user tidak bisa export
filename tidak mengandung data sensitif berlebihan
error tidak leak SQL/internal stack
export tidak membuka public temporary URL tanpa kontrol akses

Optional, dibahas di sesi audit:

audit event saat export
export row_count metadata
export checksum metadata
export history
Auditability DoD

Untuk setiap export implementation handoff, wajib tulis:

report key
format export
actor/access rule
period rule
source dataset
tests run
proof output
remaining GAP

Jika export action diaudit:

audit tidak menyimpan isi file penuh
audit menyimpan actor
audit menyimpan report_key
audit menyimpan format
audit menyimpan period/filter
audit menyimpan generated_at
audit menyimpan row_count jika tersedia
Documentation DoD

Wajib update:

blueprint jika ada keputusan desain berubah
workflow jika urutan berubah
DoD jika syarat selesai berubah
handoff setelah implementasi session
ADR hanya jika keputusan permanen lintas sistem dikunci
Final Review Checklist

Sebelum merge/commit final export report:

git diff --check
targeted tests export
targeted tests report source
auth/access tests
parity tests
performance sanity
file length audit jika repo mewajibkan
no query duplication
no JS business calculation
no formatting value mutation
manual download/open proof bila memungkinkan
Diskusi Tampilan yang Dikunci Dulu
PDF

DECISION:

PDF harus ringkas, bukan tempat menumpahkan seluruh database. Untuk report detail, PDF satu bulan sudah tepat.

Default tampilan:

header berisi nama report
subheader berisi periode dan basis tanggal
metadata kecil di kanan/kiri: generated_at, generated_by
summary cards/table di halaman pertama
detail table setelah summary
footer berisi page number dan report key
empty state tetap printable
landscape untuk tabel yang lebar

Untuk dashboard PDF:

jangan terlalu banyak detail
fokus ke summary owner
tampilkan konteks periode
chart boleh ada kalau mudah, tapi angka tetap dari dataset
kalau chart membuat PDF rumit, tunda chart; angka dulu, seni rupa nanti
Excel

DECISION:

Excel harus data-first.

Workbook minimal:

Metadata
Summary
Details

Untuk dashboard:

Metadata
Dashboard Summary
Cashflow
Operational Performance
Inventory
Top Selling
Supplier Payable
Reconciliation Notes

Rules tampilan:

header row bold boleh
freeze header boleh
autofilter boleh
no merged cells untuk data table
rupiah numeric
tanggal konsisten
summary jangan menggantikan detail
detail jangan kehilangan row karena pagination screen
Tombol UI

Di screen report:

tombol Cetak PDF
tombol Export Excel
tampilkan hint:
PDF: maksimal 1 bulan
Excel: maksimal 1 tahun

Jika filter melebihi limit:

disable tombol PDF atau validasi saat klik
tampilkan pesan jelas: PDF hanya dapat dicetak untuk periode maksimal 1 bulan. Gunakan Excel untuk analisis periode lebih panjang.

Untuk dashboard:

Cetak Snapshot PDF
Export Dashboard Excel
Scope Split untuk Sesi Berikutnya
Sesi Audit

Tujuan:

baca repo
mapping route/controller/usecase/query/view untuk Laporan Transaksi
cek apakah dataset reusable
cek filter dan columns screen
cek test yang sudah ada
cek akses route
hasilnya berupa audit report + GAP

Tidak implementasi.

Sesi Eksekusi

Tujuan:

tambah file docs dulu jika belum
implement Excel Laporan Transaksi
implement PDF Laporan Transaksi
test parity
handoff proof

Tidak menyentuh semua report sekaligus. Kita bukan sedang melempar granat ke codebase.
