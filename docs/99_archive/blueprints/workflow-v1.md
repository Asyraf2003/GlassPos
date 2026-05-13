# 2) Workflow Induk v1

Ini urutan kerja membangun sistem dari fondasi ke stabil. Saya susun berdasarkan fakta domain Anda, bukan berdasarkan enaknya framework.

## Step 1 — Kunci ADR inti

Tujuan: mengunci keputusan yang tidak boleh bergeser diam-diam.

ADR yang harus langsung dibuat:

- ADR-001: 1 nota multi-item
- ADR-002: stok negatif dilarang default
- ADR-003: sparepart luar = biaya kasus, bukan inventory
- ADR-004: minimum selling price guard
- ADR-005: paid note correction with audit
- ADR-006: costing default average, pluggable FIFO
- ADR-007: admin transaction entry behind policy
- ADR-008: audit mandatory for sensitive actions
- ADR-009: reporting = read model
- ADR-010: bot integration as adapter
- ADR-011: uang yg dikunci dalam bentuk integer

Output wajib:

- semua keputusan inti tertulis
- semua tim/AI/dev mengacu ke ADR ini

## Step 2 — Bangun skeleton hexagonal

Tujuan: fondasi struktur, bukan fitur dulu.

Kerja:

- susun folder core/application/ports/adapters
- siapkan abstraction repository, clock, id generator, unit of work
- siapkan exception/error base class/domain error base
- siapkan audit contract

Output wajib:

- project skeleton berdiri
- dependency direction benar
- adapter tidak bocor ke domain

## Step 3 — Identity & Access minimal

Tujuan: fondasi role, capability, policy akses transaksi, dan audit perubahan policy hidup dulu.

Kerja:

- user / actor access minimum
- role admin/kasir
- capability admin transaction entry
- policy keputusan akses input transaksi
- audit saat capability / policy transaksi diubah
- jalur operasional minimum enable/disable capability

Output wajib:

- role aktif `admin` dan `kasir` hidup sebagai fondasi Identity & Access
- `TransactionEntryPolicy` hidup sebagai pengambil keputusan akses input transaksi
- admin tidak otomatis boleh input transaksi tanpa capability aktif
- perubahan capability admin transaksi tercatat
- enable/disable capability admin transaksi terbukti operasional end-to-end
- pembuktian input transaksi operasional nyata dilakukan pada step bounded context Nota Operasional / Service-Sales Case saat entry point transaksi sudah ada

## Step 4 — Product Catalog

Tujuan: master barang resmi jadi sumber validasi.

Kerja:

- create product master
- update product master
- harga_jual sebagai default / minimum price
- validasi supplier invoice terhadap product master

Output wajib:

- product master resmi hidup sebagai source of truth
- harga_jual minimum tervalidasi
- product baru tidak bisa lahir dari supplier invoice
- invariant domain ADR-0012 terkunci: supplier flow tidak boleh menciptakan product baru secara implisit


## Step 5 — Supplier + Inventory Receiving

Tujuan: jalur stok masuk resmi aktif.

Kerja:

- supplier
- supplier invoice
- validasi line supplier invoice terhadap product master existing
- harga beli
- due date
- receive inventory
- supplier payable

Output wajib:

- stok masuk hanya dari jalur ini
- line invoice ke product master valid
- receive stok membentuk inventory movement resmi

## Step 6 — Inventory engine

Tujuan: mesin stok siap dipakai note.

Kerja:

- stock balance
- inventory movement
- stock adjustment
- negative stock policy
- costing average strategy

Output wajib:

- stok keluar/masuk bisa dihitung ulang
- stok negatif tertolak
- costing average tersedia

## Step 7 — Note multi-item engine

Tujuan: jantung bisnis hidup.

Kerja:

- create note
- add work item
- add service line
- add store-stock part line
- add customer-owned part line
- add external purchase cost line
- status per work item
- total note calculation

Output wajib:

- 1 nota bisa memuat banyak item
- status per item berbeda-beda
- sparepart toko potong stok
- sparepart customer tidak potong stok
- sparepart luar tidak masuk inventory, tapi masuk biaya kasus

## Step 8 — Payment & receivable engine

Tujuan: pembayaran fleksibel hidup.

Kerja:

- record payment
- partial payment
- payment allocation
- outstanding calculation
- full paid detection

Output wajib:

- bayar sebagian valid
- sisa tagihan tepat
- status lunas akurat
- over-allocation tertolak

## Step 9 — Correction, refund, audit

Tujuan: perubahan sensitif aman.

Kerja:

- correction flow
- paid note edit guard
- alasan wajib
- before/after snapshot otomatis
- refund/adjustment flow bila diperlukan

Output wajib:

- transaksi lunas tidak bisa diubah bebas
- koreksi selalu punya alasan
- audit lengkap tersimpan

## Step 10 — Employee finance

Tujuan: domain SDM aktif.

Kerja:

- employee
- payroll manual
- payroll mode harian/mingguan/bulanan
- employee debt
- debt payment

Output wajib:

- gaji manual dengan tanggal dan nominal valid
- hutang karyawan dan pembayaran hutang tercatat

## Step 11 — Operational expense

Tujuan: biaya keluar bisnis resmi aktif.

Kerja:

- expense category
- expense entry
- recurring template opsional

Output wajib:

- biaya listrik/air/makan dll bisa tercatat
- mempengaruhi laporan

## Step 12 — Reporting read models

Tujuan: laporan kritikal siap.

Kerja:

- laporan bulanan
- arus kas
- hutang supplier
- hutang karyawan
- pendapatan nota
- biaya operasional
- stok
- laba model operasional

Output wajib:

- laporan baca dari data final
- angka konsisten
- perbedaan 1 rupiah terdeteksi sebagai defect

## Step 13 — Notification integration

Tujuan: siapkan jalur Telegram tanpa merusak core.

Kerja:

- outbound notification adapter
- event note paid
- event supplier due soon
- event correction happened
- event daily/monthly summary

Output wajib:

- domain tidak tahu Telegram
- notification hanya adapter

## Step 14 — Hardening & migration safety

Tujuan: project siap dipelihara dan aman dipindah.

Kerja:

- lock public contracts
- hexagonal audit script
- concurrency test
- data migration discipline
- replay test untuk laporan

Output wajib:

- struktur portable
- core tidak terikat framework
- behavior penting dilindungi test

