# Report and Dashboard Export V1 Workflow

## Status
Draft.

## Metadata
- Date: 2026-05-01
- Scope: PDF and Excel export workflow

## Goal

Memberikan urutan kerja implementasi export PDF dan Excel agar tidak merusak reporting, dashboard, performa, atau auditability.

## Global Rules
Satu sesi hanya boleh punya satu active report/export target.
Jangan implementasi PDF dan Excel untuk semua report sekaligus.
Jangan mulai dari dashboard export.
Jangan mulai dari tampilan PDF.
Mulai dari dataset contract.
Excel dibuat sebelum PDF untuk membuktikan angka.
PDF dibuat setelah dataset dan Excel parity jelas.
Dashboard export terakhir.
Tidak boleh klaim selesai tanpa test/proof.
Tidak boleh menaikkan progress hanya karena blueprint sudah ada.
## Phase 0 - Documentation Lock

Goal:

blueprint export dibuat
workflow export dibuat
DoD export dibuat
scope audit dan eksekusi dipisah

Output:

blueprint path jelas
workflow path jelas
DoD path jelas
tidak ada kode export dibuat

Exit condition:

dokumen disetujui
active implementation target belum dibuka
## Phase 1 - Audit Existing Report Screen

Goal:

Membuktikan source screen sebelum export dibuat.

Active target pertama:

Laporan Transaksi

Audit checklist:

route screen
controller
use case
query/read model
Blade table columns
filter inputs
summary cards
total fields
date basis
status inclusion
dashboard dependency
existing tests

Output:

source mapping report
GAP list
decision apakah dataset sudah reusable atau perlu adapter dataset kecil

Forbidden:

membuat export route
menambah dependency PDF/Excel
mengubah formula report

Exit condition:

source mapping terbukti dari repo
screen contract tertulis
GAP minimum ditutup atau diberi explicit blocker
## Phase 2 - Export Dataset Contract

Goal:

Membuat contract dataset yang akan dipakai screen/PDF/Excel.

Output contract:

metadata
filters
summary rows
detail rows
totals
reconciliation rows optional
filename base

Rules:

dataset tidak boleh mengubah formula
dataset boleh membungkus hasil report existing
dataset harus cukup untuk PDF dan Excel
dataset harus testable tanpa render PDF/Excel

Exit condition:

unit/feature test dataset contract lulus
screen behavior tidak berubah
## Phase 3 - Excel Export First

Goal:

Membuat Excel dari dataset yang sama.

Why Excel first:

angka lebih mudah diverifikasi
numeric type bisa diuji
detail row bisa dibandingkan dengan dataset
layout lebih sederhana daripada PDF

Implementation checklist:

route export Excel
controller transport-only
Excel writer adapter
metadata sheet
summary sheet
detail sheet
filename policy
max range 1 tahun validation
role/middleware sama dengan screen

Test checklist:

authorized user can export
unauthorized user cannot export
invalid range rejected
summary value equals dataset
detail row count equals dataset
numeric rupiah not string
filter parity

Exit condition:

targeted tests pass
no formula mismatch
normal screen test still pass
## Phase 4 - PDF Export Second

Goal:

Membuat PDF dari dataset yang sama.

Implementation checklist:

route export PDF
controller transport-only
PDF writer adapter
monthly range validation
PDF view/template
metadata header
summary section
detail table
footer/page number if supported
filename policy

Test checklist:

authorized user can export
unauthorized user cannot export
invalid range rejected
PDF generated for 1 month
PDF content contains key metadata
PDF content contains summary total
PDF uses same dataset as screen/export contract

Exit condition:

targeted PDF tests pass
no screen regression
no route/middleware regression
## Phase 5 - Parity Tests

Goal:

Membuktikan screen/PDF/Excel parity.

Test minimum:

screen dataset total = export dataset total
Excel total = export dataset total
PDF visible total = export dataset total
detail sum = summary
filter parity
boundary date
empty state
refund/payment/outstanding exactness for transaction report

Exit condition:

parity tests pass
mismatch 1 rupiah fails
## Phase 6 - Performance Sanity

Goal:

Membuktikan export tidak merusak page load.

Checks:

normal report page load tidak menghitung export
export route query count bounded
PDF 1 month under acceptable local threshold
Excel 1 year under acceptable local threshold or marked queued-export candidate

Exit condition:

performance proof recorded
if slow, do not silently accept; decide optimization or queued export backlog
## Phase 7 - Replicate to Other Reports

Order:

Laporan Transaksi
Arus Kas Transaksi
Biaya Operasional
Hutang Karyawan
Laba Kas Operasional
Hutang Supplier
Stok dan Nilai Persediaan
Dashboard

Rules:

each report gets its own audit/source mapping
do not copy blindly
each report must have parity tests
dashboard waits until source reports stable
## Phase 8 - Dashboard Export

Goal:

Export dashboard as snapshot/analysis workbook.

Dashboard PDF:

monthly snapshot
summary-first
no detail overload
generated metadata
chart optional visual only

Dashboard Excel:

max 1 year
multi-sheet workbook
data-first
sheets reconcile to dashboard/report source

Exit condition:

dashboard PDF/Excel generated from dashboard/report dataset
no JS/chart source dependency
no added load to dashboard page
dashboard export tests pass
Stop Conditions

Stop implementation if:

source report contract unclear
screen itself has mismatch
report formula is unstable
export needs new query not shared with screen
period policy conflicts with existing filter behavior
role/access boundary unclear
export performance is unbounded
audit requirement conflicts with current audit design
Handoff Rule

Every export implementation session must record:

active report
files changed
source dataset
route names
period policy
tests run
proof output
remaining GAP
next active step
