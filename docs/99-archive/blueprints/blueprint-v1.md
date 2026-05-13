# 1) Blueprint Induk v1

## 1.1 Tujuan sistem

Berdasarkan fakta yang Anda beri, tujuan sistem inti adalah:

Mengelola operasional bengkel dengan 1 nota yang dapat memuat banyak item/kasus/status, pergerakan stok yang ketat, pembayaran fleksibel/parsial, biaya operasional dan SDM, serta audit penuh, dengan toleransi selisih keuangan = 0 rupiah.

Jadi pusat sistem ini bukan POS retail, tetapi:

- nota operasional bengkel
- arus sparepart
- arus uang
- audit perubahan

## 1.2 Prinsip arsitektur induk

### A. Hexagonal sungguhan

Core tidak bergantung pada:

- Laravel
- Go
- database tertentu
- Telegram
- UI full JS
- middleware framework

Semua itu hanya adapter.

### B. System menyesuaikan kebiasaan user

Karena fakta Anda jelas:

- kasir/admin terbiasa bikin 1 nota
- di dalamnya bisa ada banyak item berbeda
- status bisa berbeda-beda

Maka struktur domain harus mendukung 1 nota multi-item, bukan memaksa multi-nota.

### C. Uang dan stok adalah domain sensitif

Semua logika uang dan stok wajib hidup di:

- domain
- application/use case

Bukan di controller atau UI.

### D. Presisi mutlak

Semua nilai uang: integer rupiah

Tidak boleh float

Selisih 1 rupiah = gagal sistem

### E. Audit wajib

Perubahan sensitif harus punya:

- alasan
- actor otomatis
- timestamp otomatis
- before/after otomatis

### F. Policy-driven, bukan hardcoded

Karena Anda mau struktur mudah dimanuver, maka aturan seperti:

- stok negatif
- costing average/FIFO
- admin boleh input transaksi atau tidak
- katalog harga minimum

harus dibuat sebagai policy/domain rule, bukan hardcoded liar.

## 1.3 Bounded context induk

### 1) Identity & Access

Ruang lingkup:

- user
- role admin
- role kasir
- policy akses input transaksi
- kerangka untuk pengembangan multi-role / trust score di masa depan

Keputusan aktif v1:

- role aktif: admin, kasir
- admin tidak otomatis jadi inputter transaksi
- akses input transaksi untuk admin harus diaktifkan oleh policy dan tercatat

### 2) Product Catalog

Ruang lingkup:

- master barang/sparepart
- harga jual default/minimum
- kategori/satuan bila dibutuhkan nanti

Aturan inti:

- nama barang harus dibuat di master dulu
- supplier invoice tidak boleh menambah barang baru kalau master product belum ada

### 3) Procurement / Supplier

Ruang lingkup:

- supplier
- faktur supplier
- harga beli
- hutang supplier
- jatuh tempo
- pembayaran supplier

Aturan inti:

- penambahan stok normal hanya boleh berasal dari supplier invoice yang valid

### 4) Inventory

Ruang lingkup:

- saldo stok
- mutasi stok
- adjustment
- konsumsi sparepart untuk servis
- penjualan sparepart langsung

Aturan inti:

- stok negatif default dilarang
- aturan ini dibuat extensible sebagai policy
- stok bertambah bebas lewat edit manual tidak boleh

### 5) Nota Operasional / Service-Sales Case

Ini jantung sistem.

Bukan “transaksi retail”, tetapi 1 nota yang memuat banyak item/kasus.

Struktur konsepnya:

Nota = aggregate root

Work Item / Case Item = unit kerja di dalam nota

Satu nota bisa memuat banyak work item, misalnya:

- item 1: servis + sparepart toko
- item 2: servis + sparepart customer
- item 3: servis tanpa sparepart
- item 4: servis + sparepart beli luar

Dengan begitu:

- user tetap input 1 nota
- sistem tetap bisa melacak status per item
- tidak mengubah kebiasaan client

Isi minimal nota:

- customer
- actor input
- daftar work item
- daftar line jasa
- daftar line sparepart toko
- daftar line sparepart customer
- daftar line biaya sparepart luar
- total
- pembayaran
- sisa tagihan
- audit correction

Jenis sumber part di work item:

- store_stock
- customer_owned
- external_purchase

### 6) Payments & Cash

Ruang lingkup:

- pembayaran customer
- pembayaran parsial
- alokasi pembayaran
- kas masuk
- kas keluar yang relevan
- sisa tagihan / piutang transaksi

Aturan inti:

- pembayaran parsial adalah default
- pembayaran bisa fleksibel
- alokasi bisa ke item tertentu atau ke total nota, tergantung use case yang dipilih nanti di implementasi
- status lunas tidak ditentukan manual, tapi hasil hitung domain

### 7) Employee Finance

Ruang lingkup:

- data karyawan
- penggajian
- hutang karyawan
- pembayaran hutang karyawan

Mode gaji yang harus didukung:

- harian
- mingguan
- bulanan
- manual tanggal + nominal

siap untuk auto di masa depan

Keputusan desain:

- core v1: manual entry adalah fondasi
- auto payroll = policy/fitur lanjutan di atas fondasi manual

### 8) Operational Expense

Ruang lingkup:

- biaya operasional
- kategori biaya
- template pengulangan bulanan bila nanti dipakai

Contoh kategori:

- listrik
- air
- makan
- transport
- lainnya

### 9) Reporting / Accounting Read Model

Ruang lingkup:

- pembukuan bulanan
- laporan kas
- laporan stok
- laporan hutang supplier
- laporan hutang karyawan
- laporan pendapatan servis
- laporan penjualan sparepart
- laporan laba

Keputusan inti:

- laporan adalah read model
- laporan tidak boleh menyimpan logika bisnis utama
- laporan membaca data final domain

### 10) Audit & Activity Log

Ruang lingkup:

- log aksi
- log koreksi
- jejak siapa melakukan apa
- perubahan data sensitif
- alasan perubahan

Wajib untuk:

- edit nota
- edit pembayaran
- edit supplier invoice
- edit stok/adjustment
- edit biaya
- edit hutang/gaji
- aktifkan akses transaksi admin

## 1.4 Desain inti nota: 1 nota multi-item

Ini bagian yang paling penting karena tadi Anda koreksi langsung.

### Model final

Aggregate: Note

Header utama:

- nomor nota
- customer
- tanggal
- actor pembuat
- status nota
- total nota
- total dibayar
- sisa bayar

Child collection: WorkItem[]

Masing-masing mewakili satu unit/pekerjaan/kelompok kasus di dalam nota.

Setiap WorkItem bisa punya:

- deskripsi unit/objek servis
- status item
- jasa
- part toko
- part customer
- biaya part luar
- subtotal item
- catatan item

Child collection lain:

- Payments[]
- PaymentAllocations[]
- Corrections[]
- AuditRefs[]

### Manfaat model ini

Berdasarkan fakta Anda:

- user tetap 1 nota
- setiap item bisa beda kondisi
- status bisa beda
- pembayaran tetap satu pintu atau dialokasikan
- audit tetap rapi

Ini sesuai kebiasaan client dan tetap sehat secara hexagonal.

## 1.5 Aturan domain inti yang dikunci

### Uang

Semua nominal uang disimpan sebagai integer rupiah.

Tidak ada pembulatan tersembunyi.

Total nota = hasil penjumlahan seluruh komponen resmi.

Total dibayar = hasil penjumlahan pembayaran sah.

Sisa tagihan = total nota - total dibayar.

Selisih 1 rupiah = defect kritikal.

### Harga

Harga jual per kasus boleh berubah.

Harga jual tidak boleh di bawah harga minimum/default yang ditetapkan.

Jika realita lapangan customer dibayar lebih murah, sistem tidak boleh merusak harga resmi; selisih harus tercatat lewat mekanisme lain yang sah bila nanti dibuka di ADR.

### Supplier & Catalog

Product baru harus dibuat dulu di master.

Faktur supplier tidak boleh membuat product baru diam-diam.

Harga beli resmi berasal dari supplier invoice.

### Inventory

Stok tambah normal hanya dari supplier receipt yang valid.

Stok kurang karena:

- penjualan sparepart
- konsumsi sparepart servis
- adjustment resmi

Stok negatif dilarang by default.

Policy stok negatif harus extensible, tapi default tetap off.

### Nota multi-item

Satu customer boleh punya lebih dari satu nota aktif.

Satu nota boleh punya banyak work item.

Tiap work item boleh punya status berbeda.

User tidak dipaksa memecah jadi banyak nota.

### Sparepart luar

Sparepart beli luar tidak masuk inventory.

Sparepart beli luar masuk sebagai biaya kasus.

Margin kasus harus bisa dibaca jelas: pendapatan vs biaya eksternal.

### Pembayaran

Pembayaran parsial adalah fitur inti.

Alokasi pembayaran harus tervalidasi.

Nota lunas bukan status manual, tapi hasil hitung.

### Koreksi

Nota lunas tidak boleh diubah bebas.

Penambahan item baru setelah lunas → transaksi/kasus baru.

Salah input tetap bisa dikoreksi.

Koreksi wajib alasan.

Sistem wajib otomatis menyimpan siapa, kapan, dan perubahan before/after.

### Valuasi

Default costing strategy = average

Struktur harus siap diganti ke FIFO

## 1.6 Policy yang wajib dibuat sebagai extension point

Agar mudah manuver, policy berikut harus berbentuk interface/strategy:

- NegativeStockPolicy
- CostingPolicy
- MinSellingPricePolicy
- AdminTransactionEntryPolicy
- CorrectionPolicy
- PaymentAllocationPolicy
- AuditPolicy

Dengan ini, saat pindah Laravel → Go, yang dibawa adalah:

- aturan
- kontrak
- struktur domain

bukan sekadar folder.

## 1.7 Port hexagonal induk

### Inbound ports

Use case yang masuk ke core, minimal:

- create note
- add work item to note
- add service line
- add store-stock part line
- add customer-owned part line
- add external-purchase cost line
- recalc note totals
- record customer payment
- allocate payment
- finalize note
- correct paid note
- create product
- create supplier invoice
- receive supplier stock
- adjust stock
- create payroll entry
- create employee debt
- record employee debt payment
- create operational expense
- activate admin transaction capability
- generate reports
- publish notification event

### Outbound ports

Yang dibutuhkan core ke luar:

- user repository
- role/policy repository
- product repository
- supplier repository
- inventory repository
- note repository
- payment repository
- payroll repository
- expense repository
- audit repository
- report read model repository
- unit of work / transaction boundary
- clock
- id generator
- notifier
- file storage
- event bus

## 1.8 Struktur folder generik portable

    /src

      /Core

        /Domain

          /IdentityAccess

          /Catalog

          /Supplier

          /Inventory

          /Note

          /Payment

          /EmployeeFinance

          /Expense

          /Audit

          /Reporting

        /Application

          /UseCase

          /DTO

          /Policy

          /Service

        /Ports

          /Inbound

          /Outbound



      /Adapters

        /Inbound

          /Http

          /Cli

          /Telegram

        /Outbound

          /Persistence

          /Notification

          /Storage

          /Clock

          /IdGenerator

          /Queue



      /Bootstrap

      /Config

      /Tests

Ini bisa diterjemahkan ke:

- Laravel module/folder
- Go package
- worker terpisah
- bot adapter terpisah

## 1.9 Integrasi Telegram

Karena Anda bilang ini belakangan, posisinya bukan core.

Telegram bisa jadi:

- outbound adapter: kirim notif, laporan, reminder jatuh tempo, bukti pembayaran
- inbound adapter: command bot untuk cek data atau trigger proses tertentu

Keputusan arsitektur:

- Telegram tidak tahu detail domain
- Telegram hanya panggil inbound port / terima domain event
- kalau nanti pindah ke WA/bot lain, domain tidak berubah

## 1.10 Boundary core v1

Agar blueprint tetap sehat, batas core v1 perlu tegas.

### Masuk core v1

- note multi-item
- supplier, stok, payment partial
- employee finance
- expense
- audit
- reports dasar
- policy-based access
- costing average pluggable

### Tidak dipaksa masuk core v1

- register terbuka
- trust score aktif
- multi-role kompleks
- bot command kompleks
- workflow UI advanced
- promo/discount engine kompleks
- deposit wallet lintas nota
- akuntansi jurnal double-entry penuh

Kalau nanti dibutuhkan, masuk ADR/blueprint turunan.
