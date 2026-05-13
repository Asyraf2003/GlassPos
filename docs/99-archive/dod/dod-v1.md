# DoD Induk v1

## Status
Legacy baseline DoD reference.
This file defines broad completion criteria and is not proof that any current implementation is complete.

Di bawah ini DoD induk, termasuk make target, data testing, batas gagal, dan catatan proyek.

## 3.1 Definition of Done global

Sebuah feature core dianggap Done hanya jika:

- aturan domain tertulis jelas
- punya use case/application service
- punya test domain
- punya test integrasi minimal
- tidak melanggar dependency hexagonal
- audit tercatat untuk aksi sensitif
- semua nominal uang memakai integer rupiah
- tidak ada stok berubah tanpa movement resmi
- laporan yang terpengaruh ikut tervalidasi
- error domain terdefinisi jelas
- ada boundary sukses/gagal
- lolos audit 1-rupiah exactness

Kalau salah satu tidak terpenuhi, statusnya belum Done.

## 3.2 DoD per domain

### A. Catalog Done bila:

- product master bisa dibuat
- harga jual minimum tersimpan
- supplier invoice barang tak dikenal tertolak

### B. Supplier Done bila:

- supplier invoice valid
- due date valid
- stok receipt terbentuk dari invoice
- hutang supplier ter-update

### C. Inventory Done bila:

- movement masuk/keluar/adjustment tercatat
- stok negatif tertolak
- saldo bisa direkonstruksi dari movement

### D. Note Done bila:

- 1 nota multi-item valid
- 3 jenis sumber part valid
- subtotal per item valid
- total nota valid
- perubahan status per item valid

### E. Payment Done bila:

- pembayaran parsial valid
- sisa bayar akurat
- alokasi tidak melebihi outstanding
- pelunasan terdeteksi akurat

### F. Correction Done bila:

- paid note tidak bisa diedit bebas
- alasan wajib
- before/after tersimpan
- actor/timestamp otomatis ada

### G. Employee finance Done bila:

- payroll manual valid
- mode harian/mingguan/bulanan valid
- hutang karyawan valid
- pembayaran hutang valid

### H. Expense Done bila:

- kategori valid
- nominal valid
- tanggal valid
- laporan ikut berubah benar

### I. Reporting Done bila:

- semua angka konsisten dengan sumber domain
- selisih 1 rupiah gagal
- laporan bulanan bisa direplay dari data yang sama

## 3.3 Makefile contract induk

Ini daftar target minimal yang harus ada. Nama boleh sama persis agar konsisten.

- make dev
- make fmt
- make lint
- make test
- make test-unit
- make test-domain
- make test-integration
- make test-money
- make test-stock
- make test-report
- make test-audit
- make audit-hex
- make audit-contract
- make seed-demo
- make seed-test
- make reset-db
- make migrate
- make rollback
- make coverage
- make ci

Makna target

- make dev → jalankan app lokal
- make fmt → format code
- make lint → static analysis
- make test → seluruh test
- make test-unit → unit test umum
- make test-domain → rule domain
- make test-integration → repo/db/integration
- make test-money → test presisi rupiah
- make test-stock → test mutasi & saldo stok
- make test-report → test laporan
- make test-audit → test log perubahan
- make audit-hex → cek dependency direction
- make audit-contract → cek kontrak public/use case
- make seed-demo → data demo
- make seed-test → data khusus test
- make reset-db → reset data lokal
- make migrate → migration up
- make rollback → migration down
- make coverage → laporan coverage
- make ci → fmt + lint + test + audit

### DoD CI

Pull request/merge internal dianggap gagal jika:

- make ci gagal
- make test-money gagal
- make test-stock gagal
- make audit-hex gagal

## 3.4 Paket data testing induk

Berikut dataset minimum untuk menguji sistem nyata Anda.

### Master data

Users

- admin_1
- cashier_1

Customers

- customer_a
- customer_b

Employees

- employee_1
- employee_2

Suppliers

- supplier_aki
- supplier_oli

Products

- busi
- oli_mesin
- kampas_rem
- aki

Harga jual minimum/default contoh

- busi: 50_000
- oli_mesin: 75_000
- kampas_rem: 120_000
- aki: 350_000

Harga beli contoh

- busi: 35_000
- oli_mesin: 55_000
- kampas_rem: 90_000
- aki: 290_000

Stok awal hasil supplier receipt

- busi: 10
- oli_mesin: 8
- kampas_rem: 6
- aki: 4

## 3.5 Skenario uji inti

### Skenario 1 — Service tanpa sparepart

- 1 nota
- 1 work item
- jasa 90_000
- tanpa part
- bayar full 90_000

Harus sukses:

- stok tidak berubah
- pendapatan 90_000
- outstanding 0

### Skenario 2 — Service dengan sparepart toko

- jasa 60_000
- busi 1 x 50_000
- total 110_000
- bayar 50_000 dulu

Harus sukses:

- stok busi berkurang 1
- outstanding 60_000
- nota belum lunas

### Skenario 3 — Service dengan sparepart customer

- jasa 80_000
- customer bawa part sendiri
- total 80_000

Harus sukses:

- stok tidak berubah
- line part customer tercatat
- outstanding sesuai pembayaran

### Skenario 4 — Sparepart beli luar

- charge ke customer 90_000
- biaya beli part luar 30_000
- stok toko tidak berubah

Harus sukses:

- external part cost tercatat 30_000
- inventory tidak bergerak
- margin kasus dapat terbaca 60_000 secara operasional

### Skenario 5 — 1 nota multi-item campuran

Dalam 1 nota:

- item A: service + part toko
- item B: service + part customer
- item C: service only
- item D: service + external purchase

Harus sukses:

- 1 nota saja
- masing-masing item punya status sendiri
- stok hanya terpotong untuk item yang pakai part toko
- perhitungan total konsisten

### Skenario 6 — Pembayaran parsial bertahap

- total nota 300_000
- bayar 100_000
- bayar 75_000
- bayar 125_000

Harus sukses:

- sisa bayar bertahap benar
- status lunas tepat di pembayaran ketiga
- tidak ada selisih

### Skenario 7 — Harga di bawah batas minimum

- busi minimum 50_000
- kasir mencoba input 49_000

Harus gagal:

- transaksi ditolak
- error domain jelas
- tidak ada perubahan data

### Skenario 8 — Supplier invoice pakai barang yang belum ada di master

- invoice masukkan kampas_x_custom yang belum ada

Harus gagal:

- invoice ditolak atau line ditolak
- stok tidak berubah

### Skenario 9 — Stok negatif

- stok busi tersisa 0
- kasir coba pakai busi 1

Harus gagal:

- line part toko ditolak
- stok tidak berubah

### Skenario 10 — Koreksi nota lunas

- nota sudah lunas
- user ubah nominal karena salah input
- alasan diisi

Harus sukses terkontrol:

- sistem simpan audit otomatis
- before/after ada
- actor/time ada
- perubahan terjejak

### Skenario 11 — Koreksi nota lunas tanpa alasan

Harus gagal:

- sistem tolak
- tidak ada perubahan data

### Skenario 12 — Admin input transaksi tanpa policy aktif

Harus gagal:

- akses ditolak
- audit akses bisa dicatat bila perlu

### Skenario 13 — Admin input transaksi dengan policy aktif

Harus sukses:

- transaksi bisa dibuat
- audit actor mencatat admin menggunakan capability transaksi

### Skenario 14 — Gaji manual

- employee_1
- tanggal tertentu
- nominal 500_000

Harus sukses:

- payroll entry tersimpan
- laporan biaya tenaga kerja berubah sesuai

### Skenario 15 — Hutang karyawan dan pembayaran

- hutang 200_000
- bayar 50_000
- bayar 150_000

Harus sukses:

- outstanding hutang akurat
- status lunas tepat

### Skenario 16 — Biaya operasional

- listrik 300_000
- makan 150_000

Harus sukses:

- tercatat per kategori
- laporan biaya berubah akurat

## 3.6 Batas gagal fitur core v1

Anda minta batas mana fitur berhasil/gagal. Ini boundary resmi core v1.

Harus gagal bila:

- ada selisih perhitungan 1 rupiah
- stok menjadi negatif
- supplier invoice menambah barang yang belum ada di master
- harga jual di bawah batas minimum
- pembayaran melebihi outstanding dengan alokasi yang tidak sah
- koreksi transaksi lunas tanpa alasan
- perubahan sensitif tanpa audit entry
- admin input transaksi tanpa policy aktif
- line part toko mengurangi stok tanpa movement resmi
- external purchase dicatat sebagai inventory store stock
- report total berbeda dari data sumber
- actor/timestamp audit tidak tercatat
- edit paid note menambah item baru pada nota yang sama
- satu nota multi-item gagal menyimpan status item secara terpisah

Belum didukung di core v1 dan harus dianggap out-of-scope / ditolak jelas:

- deposit customer lintas banyak nota
- promo/discount engine kompleks
- registrasi terbuka
- trust score aktif
- multi-role kompleks di luar admin/kasir
- jurnal akuntansi double-entry penuh
- FIFO aktif sebagai default langsung tanpa strategy implementation
- bot command kompleks yang bypass rule domain

## 3.7 Error catalog induk

Format error harus konsisten. Minimal kategori:

### Authorization

- AUTH_FORBIDDEN
- ADMIN_TRANSACTION_CAPABILITY_DISABLED

### Validation

- VALIDATION_REQUIRED_REASON
- VALIDATION_INVALID_AMOUNT
- VALIDATION_INVALID_DATE
- VALIDATION_UNKNOWN_PRODUCT

### Inventory

- INVENTORY_INSUFFICIENT_STOCK
- INVENTORY_NEGATIVE_STOCK_NOT_ALLOWED
- INVENTORY_INVALID_MOVEMENT_SOURCE

### Pricing

- PRICING_BELOW_MINIMUM_SELLING_PRICE

### Note

- NOTE_ALREADY_PAID
- NOTE_NEW_ITEMS_NOT_ALLOWED_AFTER_PAID
- NOTE_INVALID_WORK_ITEM_STATE

### Payment

- PAYMENT_OVER_ALLOCATION
- PAYMENT_EXCEEDS_OUTSTANDING
- PAYMENT_INVALID_TARGET

### Supplier

- SUPPLIER_INVOICE_UNKNOWN_PRODUCT
- SUPPLIER_RECEIPT_INVALID_INVOICE

### Audit

- AUDIT_REASON_REQUIRED
- AUDIT_SNAPSHOT_FAILED

### Reporting

- REPORT_MISMATCH_AMOUNT
- REPORT_REBUILD_FAILED

## 3.8 Catatan ADR induk

ADR yang wajib ada dari awal:

- ADR-001: One Note Multi-Item Model
- ADR-002: Negative Stock Policy Default Off
- ADR-003: External Spare Part as Case Cost
- ADR-004: Minimum Selling Price Guard
- ADR-005: Paid Note Correction Requires Audit
- ADR-006: Costing Strategy Default Average, FIFO-ready
- ADR-007: Admin Transaction Entry Behind Capability Policy
- ADR-008: Audit-First Sensitive Mutations
- ADR-009: Reporting as Read Model, Not Business Source
- ADR-010: Telegram/WA Integration as Adapter
- ADR-011: Money Stored as Integer Rupiah
- ADR-012: Product Master Must Exist Before Supplier Receipt

## 3.9 Catatan kecil proyek

Ini bukan fitur, tapi guardrail proyek:

- UI tidak boleh jadi sumber kebenaran
- Controller tipis
- Use case tidak boleh lompat langsung ke UI concern
- Domain tidak boleh tahu framework
- Audit bukan addon
- Laporan bukan tempat akal-akalan angka
- Perubahan kebiasaan client harus seminimal mungkin
- Kalau ada konflik antara enak coding vs benar domain, pilih benar domain

## Ringkasan akhir

### Bentuk sistem yang terkunci

Sistem Anda sekarang terdefinisi sebagai:

Aplikasi operasional bengkel berbasis hexagonal dengan 1 nota multi-item, stok ketat, pembayaran parsial fleksibel, biaya sparepart luar sebagai biaya kasus, koreksi transaksi yang diaudit, dan laporan yang presisi sampai 1 rupiah.

### Nilai kekuatan blueprint ini

- cocok untuk Laravel sekarang
- tetap bisa dipindah ke Go nanti
- tidak mengubah kebiasaan admin/kasir
- sehat untuk audit
- sehat untuk laporan
- sehat untuk portfolio karena problem-nya nyata, bukan demo dangkal

## Status progres

- Progress dokumen induk: 100%
- Blueprint Induk v1: selesai
- Workflow Induk v1: selesai
- DoD Induk v1: selesai

