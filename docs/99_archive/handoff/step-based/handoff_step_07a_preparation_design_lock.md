# Handoff — Step 7 Preparation Design Lock

## Metadata
- Tanggal: 2026-03-13
- Nama slice / topik: Step 7 Preparation — Note Multi-Item Engine Design Lock
- Workflow step: Step 7
- Status: PREPARATION LOCKED
- Progres:
  - Preparation / Design Lock: 100%
  - Implementation / Coding: 0%

## Target halaman kerja
Mengunci kontrak persiapan Step 7 agar implementasi Note Multi-Item Engine bisa dikerjakan tanpa:
- membuka ulang domain final
- mengulang pola input lama yang tidak presisi
- membuat UI terlalu berat untuk kasir
- mencampur Step 7 dengan payment Step 8

Target halaman ini bukan menulis kode, tetapi memastikan implementasi berikutnya langsung punya arah yang jelas, kecil, dan aman.

## Referensi yang dipakai [REF]

### Dokumen utama
- AI Contract:
  - docs/setting_control/ai_contract.md
- Blueprint:
  - docs/blueprint/blueprint_v1.md
- Workflow:
  - docs/workflow/workflow_v1.md
- Handoff sebelumnya:
  - docs/handoff/handoff_step_6_inventory_engine.md

### ADR yang relevan
- docs/adr/0001-one-note-multi-item.md
- docs/adr/0003-external-spare-part-as-case-cost.md
- docs/adr/0004-minimum-selling-price-guard.md
- docs/adr/0005-paid-note-correction-requires-audit.md
- docs/adr/0006-costing-strategy-default-average-fifo-ready.md
- docs/adr/0011-money-stored-as-integer-rupiah.md

### Fakta operasional dari user
- Kasir terbiasa bekerja dengan 1 nota.
- Header nota cukup sederhana: nama customer dan tanggal.
- Tanggal default adalah hari ini.
- Isi nota berupa line yang berulang.
- Setiap line dipilih dulu tipe transaksinya.
- Setelah tipe dipilih, form detail muncul di bawah line itu.
- Kasir ingin UI sesederhana mungkin, seperti CRUD ringan.
- Service cukup terasa sebagai: service apa, harga berapa.
- Customer owned part tidak perlu menjadi form besar terpisah di UI.
- Harga part toko boleh berubah, tetapi tidak boleh di bawah harga default.
- Form store stock lebih nyaman bila harga awal sudah terisi default kali qty.
- Satu work item bisa memakai lebih dari satu barang.
- Barang yang sama cukup memakai qty.
- Barang yang berbeda harus menjadi line barang berbeda.
- Jual barang saja tanpa service harus tetap bisa didukung.

## Fakta terkunci [FACT]

### 1. Model domain inti Step 7 tidak dibuka ulang
Core domain Step 7 tetap:
- Note sebagai aggregate root
- WorkItem sebagai unit kerja di dalam note

UI boleh memakai istilah line, tetapi backend tetap memetakan line ke WorkItem.

### 2. Step 7 tetap dipisahkan dari Step 8
Step 7 hanya untuk:
- create note
- add work item
- add line detail per jenis transaksi
- status per item
- total note calculation

Step 7 belum mencakup:
- payment
- receivable
- outstanding
- paid detection
- over allocation
- correction paid note
- refund

### 3. Status item operasional disederhanakan
Status WorkItem yang dipakai untuk v1:
- open
- done
- canceled

Status berikut tidak dipakai di v1:
- pending
- in_progress

Alasannya:
- terlalu halus untuk operasional kasir
- belum ada bukti nyata bahwa kasir membutuhkan granularitas itu

Status lunas atau hutang tidak boleh dipakai di Step 7 karena itu milik Step 8.

### 4. Struktur UI Step 7 resmi
UI Step 7 dikunci sebagai:
- Note Header
- Repeatable Item Lines

Header dan line dipisahkan dengan jelas.

### 5. Field minimum Note Header resmi
Field minimum Note Header:
- customer_name
- transaction_date

Aturan:
- transaction_date default hari ini
- transaction_date milik Note, bukan milik Item Line

### 6. Field minimum Item Line shell resmi
Field minimum Item Line shell:
- line_no
- transaction_type

Aturan:
- setiap line mulai dari shell yang sama
- detail baru muncul setelah transaction_type dipilih
- tambah line mengulang pola line yang sama

### 7. Preset UI transaksi yang aktif
Preset UI besar yang dikunci:
- service_only
- service_with_store_stock_part
- service_with_external_purchase
- store_stock_sale_only

Catatan:
- customer owned part tidak dijadikan preset besar terpisah di UI
- customer owned part diperlakukan ringan dari sisi UI
- backend tetap wajib tahu bahwa source part adalah customer owned

### 8. Cardinality item transaksi
Satu line UI dipetakan menjadi satu WorkItem.

Satu WorkItem boleh mempunyai banyak komponen internal.
Implikasinya:
- service bisa memakai lebih dari satu barang
- qty barang yang sama cukup diwakili oleh qty
- barang berbeda harus menjadi line barang berbeda
- store stock sale only juga boleh berisi lebih dari satu line barang selama barangnya berbeda

### 9. Bentuk kontrak WorkItem
WorkItem dikunci memakai:
- common fields
- typed detail sesuai transaction_type

Field product, qty, pricing, external cost, dan marker source part tidak boleh dicampur menjadi common fields semuanya. Typed detail tetap diperlukan agar backend presisi dan extensible.

### 10. Customer owned part diposisikan ringan di UI, tegas di backend
Di UI:
- customer owned part tidak menjadi preset besar
- dapat muncul sebagai penanda ringan di service only

Di backend:
- source part tetap harus tercatat sebagai customer owned
- tidak boleh dianggap store stock
- tidak boleh menyentuh inventory toko
- tidak boleh ikut costing inventory toko

### 11. Pricing part toko dikunci
Untuk line yang memakai store stock:
- harga awal auto filled dari harga default product dikali qty
- harga tetap editable
- harga tidak boleh di bawah harga default atau minimum yang berlaku

Implikasi:
- UI tetap sederhana
- pricing floor tetap dijaga
- override tetap ada, tetapi tidak liar

### 12. Semua nominal uang tetap integer rupiah
Semua field harga, subtotal, biaya, dan total resmi tetap memakai integer rupiah.

## Scope yang dipakai

### [SCOPE-IN]
- kontrak domain awal Step 7
- pola UI note dan line
- status operasional item
- preset transaksi aktif
- positioning customer owned part
- pricing boundary untuk store stock
- field minimum input per preset
- keputusan bahwa backend lebih presisi daripada UI

### [SCOPE-OUT]
- implementasi code
- migration
- request dan controller
- binding port dan adapter
- feature test Step 7
- integrasi nyata ke IssueInventoryHandler
- payment, receivable, outstanding
- paid detection
- correction paid note
- refund
- audit mutation detail

## Konflik yang sudah diselesaikan

### 1. Konflik naming domain
Sempat muncul istilah:
- CustomerOrder
- CustomerTransaction
- CustomerTransactionLine

Kontrak final yang dipakai untuk Step 7 tetap:
- Note
- WorkItem

Alasan:
- paling selaras dengan blueprint dan ADR one note multi item
- tidak membuka ulang domain final
- tetap bisa memakai istilah lain di adapter atau read model bila nanti dibutuhkan, tetapi bukan sebagai kontrak inti Step 7

### 2. Konflik status operasional vs payment state
Status lunas atau hutang sempat muncul di usulan line barang.
Keputusan final:
- lunas atau hutang bukan status WorkItem
- status itu masuk Step 8
- Step 7 hanya pakai status operasional open, done, canceled

### 3. Konflik field product di common fields
Sempat muncul kecenderungan menaruh product_id di level item umum.
Keputusan final:
- product_id tidak masuk common fields WorkItem
- product_id tetap berada di typed detail line yang memang memerlukan product store stock

### 4. Konflik harga part toko
Sempat ada arah field minimum store stock tanpa field harga.
Keputusan final:
- field harga tetap ada di form store stock
- harga awal auto filled dari default kali qty
- harga boleh diubah
- harga tidak boleh turun di bawah floor

## Keputusan yang dikunci [DECISION]

### 1. Prinsip utama Step 7
UI dibuat sesederhana mungkin.
Backend dibuat sepresisi mungkin.

Makna praktis:
- kasir tidak dipaksa memahami domain yang kaya
- application layer bertanggung jawab menerjemahkan input sederhana ke struktur backend yang benar

### 2. Pola UI resmi Step 7
Note terdiri dari:
- header sederhana
- daftar line yang berulang

Setiap line:
- pilih transaction_type
- detail line muncul sesuai preset
- tambah line membuat line baru dengan pola yang sama

### 3. Preset UI resmi
Preset yang aktif dan sah:
- service_only
- service_with_store_stock_part
- service_with_external_purchase
- store_stock_sale_only

### 4. Customer owned part
Customer owned part:
- tidak menjadi preset besar di UI
- ditampilkan sebagai marker ringan pada service only
- tetap harus dipetakan ke backend secara tegas sebagai customer owned source

### 5. Store stock pricing
Store stock pricing:
- auto filled dari default product dikali qty
- tetap editable
- tetap tunduk pada floor pricing

### 6. Payment state tidak masuk Step 7
Seluruh bahasa lunas, hutang, outstanding, partial payment, dan paid detection ditahan untuk Step 8.

## Blueprint hasil lock

## 1. Struktur Note di UI
### Note Header
- customer_name
- transaction_date

### Item Line shell
- line_no
- transaction_type

## 2. Preset UI dan field minimum per preset

### service_only
Field minimum:
- service_name
- service_price_rupiah
- customer_owned_part = yes or no

Catatan:
- ini preset paling ringan
- dipakai untuk service biasa
- dipakai juga untuk service dengan barang milik customer dari sisi pengalaman UI

### service_with_store_stock_part
Field minimum:
- service_name
- service_price_rupiah
- store_stock_lines
  - product_id
  - qty
  - line_total_rupiah

Aturan:
- line_total_rupiah auto filled dari default kali qty
- line_total_rupiah editable
- nilai tidak boleh di bawah floor total yang sah

### service_with_external_purchase
Field minimum:
- service_name
- service_price_rupiah
- external_purchase_lines
  - cost_description
  - unit_cost_rupiah
  - qty

Aturan:
- external purchase adalah biaya kasus
- tidak masuk inventory toko
- tidak boleh diproses sebagai store stock

### store_stock_sale_only
Field minimum:
- store_stock_lines
  - product_id
  - qty
  - line_total_rupiah

Aturan:
- line_total_rupiah auto filled dari default kali qty
- line_total_rupiah editable
- nilai tidak boleh di bawah floor total yang sah

## 3. Status WorkItem
Status WorkItem resmi:
- open
- done
- canceled

Makna:
- open = item masih aktif
- done = item selesai
- canceled = item batal

## 4. Mapping mental model
Dari sisi UI:
- user merasa sedang mengisi line nota

Dari sisi backend:
- setiap line dipetakan menjadi WorkItem
- setiap WorkItem lalu memiliki typed detail sesuai transaction_type

## Mapping UI ke backend yang wajib diingat untuk implementasi

### 1. Note Header
UI:
- customer_name
- transaction_date

Backend:
- menjadi data header Note

### 2. Item Line shell
UI:
- line_no
- transaction_type

Backend:
- line_no bisa menjadi penanda urutan tampilan
- transaction_type menentukan bentuk typed detail WorkItem

### 3. service_only
UI:
- service_name
- service_price_rupiah
- customer_owned_part yes or no

Backend:
- WorkItem bertipe service_only
- service_name dan service_price_rupiah menjadi service detail
- bila customer_owned_part bernilai yes:
  - backend tetap harus menandai source part sebagai customer owned
  - tidak boleh memengaruhi inventory store
  - tidak boleh ikut costing inventory store

### 4. service_with_store_stock_part
UI:
- service_name
- service_price_rupiah
- daftar store stock lines

Backend:
- WorkItem bertipe service_with_store_stock_part
- service detail tetap dicatat
- setiap store stock line harus memuat identitas product dan qty
- pricing final line harus tervalidasi terhadap floor
- pengurangan stok nantinya tidak boleh menulis projection langsung
- integrasi stok harus lewat inventory issue resmi pada fase implementasi

### 5. service_with_external_purchase
UI:
- service_name
- service_price_rupiah
- daftar external purchase cost lines

Backend:
- WorkItem bertipe service_with_external_purchase
- service detail tetap dicatat
- setiap external purchase line menjadi biaya kasus
- tidak boleh masuk inventory
- tidak boleh ikut costing inventory toko

### 6. store_stock_sale_only
UI:
- daftar store stock lines

Backend:
- WorkItem bertipe store_stock_sale_only
- tidak ada service detail
- setiap store stock line tetap harus tervalidasi pricing floor
- pengurangan stok nantinya tetap harus lewat engine inventory resmi

## Hal yang sengaja belum dikunci penuh pada halaman ini

Bagian berikut sengaja belum difinalkan sampai implementasi Step 7 dimulai:
- field internal final entity Note
- field internal final entity WorkItem
- bentuk persistence table
- apakah typed detail akan memakai table terpisah atau struktur tertentu
- kontrak use case final
- detail request dan response HTTP
- bentuk presenter
- wiring service provider
- feature test scenario final

Alasan:
- halaman ini fokus mengunci design contract dan arah implementasi
- implementasi teknis harus tetap membaca tree repo terbaru sebelum file dibuat
- AI contract melarang turun ke migration dan entity tanpa memastikan kontrak minimum lebih dulu

## Implikasi implementasi untuk halaman berikutnya

Halaman implementasi berikutnya harus langsung mulai dari urutan ini:

### 1. Kunci field internal Note
Target minimum:
- identitas note
- customer reference atau customer name strategy
- transaction_date
- status note operasional
- total note resmi dalam integer rupiah

Catatan:
- note status tidak perlu dibahas ulang bila belum diperlukan untuk coding awal
- fokus dulu ke kontrak minimum yang diperlukan untuk create note dan add line

### 2. Kunci field internal WorkItem
Target minimum:
- identitas work item
- relasi ke note
- transaction_type
- status item
- label atau identifier item
- subtotal resmi item

Catatan:
- jangan campur product specific fields ke common fields

### 3. Kunci typed detail backend
Harus dibedakan minimal untuk:
- service detail
- store stock detail
- external purchase cost detail
- customer owned marker atau source detail

### 4. Kunci mapping UI ke backend
Wajib ditulis jelas agar implementasi tidak liar:
- field mana yang berasal dari header note
- field mana yang berasal dari line shell
- field mana yang menjadi typed detail
- field mana yang hanya concern UI dan tidak perlu menjadi field domain mentah

### 5. Baru turun ke code plan
Setelah empat langkah di atas selesai, baru turun ke:
- entity
- use case
- ports
- adapters
- migration
- feature test

## Arahan implementasi agar tetap selaras dengan repo saat ini

### 1. Jangan melanggar boundary inventory Step 6
Store stock consumption atau sale:
- tidak boleh mutasi projection langsung
- tidak boleh menulis inventory data secara liar
- nanti harus diintegrasikan lewat engine inventory issue resmi

### 2. Jangan bocorkan payment ke Step 7
Tidak boleh mulai menulis:
- outstanding
- paid detection
- receivable
- hutang customer
- payment allocation

### 3. Jangan ubah domain final tanpa konflik nyata
Kontrak inti yang sudah dikunci:
- Note
- WorkItem
- one note multi item
- pricing floor untuk store stock
- external purchase sebagai case cost
- money sebagai integer rupiah

### 4. UI sederhana bukan alasan backend longgar
Karena targetnya adalah:
- frontend ringan
- backend presisi

Maka implementasi harus menjaga:
- typed detail tetap tegas
- source part tetap bisa ditelusuri
- subtotal dan total tetap dihitung domain
- floor pricing tetap dijaga

## Bukti verifikasi [PROOF]
Halaman ini tidak menghasilkan perubahan kode, test, atau migration.

Output nyata halaman ini adalah:
- kontrak desain Step 7 terkunci
- konflik naming domain selesai
- konflik status item vs payment state selesai
- konflik pricing part toko selesai
- pola UI resmi Step 7 terkunci
- field minimum input per preset terkunci
- boundary UI sederhana versus backend presisi terkunci

Seluruh keputusan di atas diambil dari:
- blueprint
- workflow
- ADR
- handoff Step 6
- penjelasan operasional nyata dari user

## Blocker aktif
- tidak ada blocker domain aktif
- implementasi code belum dimulai
- belum ada blocker repo yang diketahui dari halaman ini karena belum turun ke inspeksi file dan penulisan kode

## Next step paling aman
Masuk ke halaman implementasi Step 7 dengan target:

### Tahap 1
- kunci field internal Note
- kunci field internal WorkItem
- kunci typed detail backend
- kunci mapping UI ke backend

### Tahap 2
- susun code plan
- tentukan file baru dan file yang diubah
- tulis entity dan use case minimum
- siapkan port dan adapter yang diperlukan
- siapkan migration minimum
- siapkan feature test minimum

### Tahap 3
- eksekusi implementasi slice kecil pertama Step 7
- buktikan dengan test yang relevan

## Status untuk laporan
- Step 7 Preparation / Design Lock: selesai
- Step 7 Implementation / Coding: belum mulai

## Ringkasan eksekutif untuk halaman berikutnya
Jangan ulang diskusi domain dari nol.

Pada halaman berikutnya, asumsikan hal berikut sudah final:
- model inti tetap Note dan WorkItem
- UI Step 7 adalah header note plus repeatable item lines
- status WorkItem hanya open, done, canceled
- customer owned part tidak menjadi preset UI besar
- store stock pricing auto filled, editable, dan dijaga floor
- field minimum per preset sudah terkunci
- Step 7 belum boleh membahas payment

Mulailah langsung dari kontrak internal implementasi Step 7, lalu turunkan ke code plan dan file yang akan dibuat atau diubah.

## Progres terakhir
- Step 7 Preparation / Design Lock: 100%
- Step 7 Implementation / Coding: 0%
