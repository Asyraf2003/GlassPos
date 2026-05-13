# Handoff Seeder Levels v2

## Ringkasan

Seeder level sekarang sudah dipisah menjadi 3 level operasional:

- `seed 1` untuk akses minimal
- `seed 2` untuk baseline operasional 1 minggu dengan semua skenario fitur utama hidup
- `seed 3` untuk dataset monster 1 tahun dengan volume agresif untuk beban sistem

Eksekusi via `Makefile` juga sudah tersedia dan tervalidasi.

## Tujuan

Tujuan pekerjaan ini adalah:

- memisahkan seeding menjadi level yang jelas
- membuat dataset baseline yang repeatable
- membuat dataset monster yang tetap deterministic
- menjaga flow domain sensitif seperti transaction, payment, refund, correction, procurement, dan inventory tetap konsisten
- menyediakan target `make` yang mudah dipakai

## Scope yang Diselesaikan

### 1. Wrapper Seeder Level

Wrapper level yang sudah dibuat:

- `database/seeders/SeedLevel1Seeder.php`
- `database/seeders/SeedLevel2Seeder.php`
- `database/seeders/SeedLevel3Seeder.php`

### 2. Helper Support Seeder

Helper support yang sudah dibuat:

- `database/seeders/Support/SeedWindow.php`
- `database/seeders/Support/SeedDensity.php`

Helper ini dipakai untuk:

- horizon waktu baseline vs monster
- density baseline vs monster
- konsistensi angka kerja antar seeder

### 3. Baseline Seeder Level 2

Seeder baseline yang sudah dibuat:

- `database/seeders/Expense/ExpenseBaselineSeeder.php`
- `database/seeders/Transaction/CustomerTransactionBaselineSeeder.php`
- `database/seeders/Transaction/CustomerPaymentBaselineSeeder.php`
- `database/seeders/Transaction/CustomerRefundBaselineSeeder.php`
- `database/seeders/Transaction/CustomerCorrectionBaselineSeeder.php`

Karakter dataset level 2:

- horizon 7 hari
- ringan
- deterministic
- rerun-safe
- menutup skenario fitur utama yang memang sudah hidup di repo

### 4. Monster Seeder Level 3

Seeder monster yang sudah dibuat:

- `database/seeders/Load/ExpenseLoadSeeder.php`
- `database/seeders/Load/ProcurementLoadSeeder.php`
- `database/seeders/Load/CustomerTransactionLoadSeeder.php`
- `database/seeders/Load/CustomerPaymentLoadSeeder.php`
- `database/seeders/Load/CustomerRefundLoadSeeder.php`
- `database/seeders/Load/CustomerCorrectionLoadSeeder.php`

Karakter dataset level 3:

- horizon 1 tahun
- volume agresif
- deterministic
- rerun-safe pada prefix seed yang dipakai
- cocok untuk beban sistem, query, reporting source, dan validasi flow domain yang padat

### 5. Wiring Makefile

Target make yang sudah tersedia:

- `make seed-1`
- `make seed-2`
- `make seed-3`

Alias pendek:

- `make 1`
- `make 2`
- `make 3`

Format generic:

- `make seed LEVEL=1`
- `make seed LEVEL=2`
- `make seed LEVEL=3`

## Mapping Seeder per Level

### Seed Level 1

Entry:
~~~text
Database\Seeders\SeedLevel1Seeder
~~~

Isi:
- `UserSeeder`

Tujuan:
- akses minimal
- admin
- kasir
- actor access
- capability state yang dibutuhkan

### Seed Level 2

Entry:
~~~text
Database\Seeders\SeedLevel2Seeder
~~~

Isi:
- `UserSeeder`
- `ProductSeeder`
- `SupplierSeeder`
- `EmployeeFinanceBaselineSeeder`
- `SupplierInvoiceScenarioSeeder`
- `SupplierInvoiceBaselineSeeder`
- `ExpenseBaselineSeeder`
- `CustomerTransactionBaselineSeeder`
- `CustomerPaymentBaselineSeeder`
- `CustomerRefundBaselineSeeder`
- `CustomerCorrectionBaselineSeeder`

Tujuan:
- baseline operasional 1 minggu
- semua fitur utama hidup
- cocok untuk development, QA, dan verifikasi flow bisnis

### Seed Level 3

Entry:
~~~text
Database\Seeders\SeedLevel3Seeder
~~~

Isi:
- `UserSeeder`
- `ProductSeeder`
- `SupplierSeeder`
- `EmployeeFinanceBaselineSeeder`
- `ProcurementLoadSeeder`
- `ExpenseLoadSeeder`
- `CustomerTransactionLoadSeeder`
- `CustomerPaymentLoadSeeder`
- `CustomerRefundLoadSeeder`
- `CustomerCorrectionLoadSeeder`

Tujuan:
- dataset monster 1 tahun
- volume tinggi
- cocok untuk beban sistem dan reporting

## Perbaikan Penting yang Dilakukan Selama Implementasi

### 1. Product Seeder Existing Dibuat Lebih Aman untuk Rerun

Seeder existing yang perlu dipatch agar rerun-safe:

- `database/seeders/Product/ProductScenarioEditedSeeder.php`
- `database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php`

Masalah sebelumnya:
- create langsung pada rerun memicu duplicate unique `kode_barang`

Perbaikan:
- resolve existing product by code dulu
- hanya create jika memang belum ada
- update tetap dijalankan setelah product id didapat

### 2. Correction Seeder Type Safety

Masalah:
- nominal correction sempat mengirim `float` ke parameter `int`

Perbaikan:
- casting eksplisit ke `int` pada hitungan harga baseline dan load correction

File terkait:
- `database/seeders/Transaction/CustomerCorrectionBaselineSeeder.php`
- `database/seeders/Load/CustomerCorrectionLoadSeeder.php`

### 3. Procurement Load Overflow Costing

Masalah:
- `inventory_value_rupiah` overflow karena schema masih `integer`

Perbaikan di `ProcurementLoadSeeder`:
- turunkan intensitas `qty` dan `unit_cost` per line
- tambahkan guard `INT_MAX`
- projection costing tetap muat di batas schema saat ini

File terkait:
- `database/seeders/Load/ProcurementLoadSeeder.php`

### 4. Customer Transaction Load Insufficient Stock

Masalah:
- konsumsi stok terlalu terkonsentrasi ke sedikit produk
- volume transaksi monster menghabiskan stok lebih cepat dari distribusi procurement

Perbaikan:
- distribusi pemilihan produk dibuat lebih menyebar dan deterministic
- konsumsi stok tidak lagi menabrak produk yang sama terus-menerus

File terkait:
- `database/seeders/Load/CustomerTransactionLoadSeeder.php`

### 5. Payment Baseline dan Load Dilengkapi Component Allocation

Masalah:
- refund flow resmi membaca `payment_component_allocations`
- payment seeder awal baru menulis note-level allocation

Perbaikan:
- tambahkan `payment_component_allocations`
- allocation mengikuti payable component note secara deterministic

File terkait:
- `database/seeders/Transaction/CustomerPaymentBaselineSeeder.php`
- `database/seeders/Load/CustomerPaymentLoadSeeder.php`

## Verifikasi yang Sudah Berhasil

### Make Level 2

Baseline level 2 sudah berhasil jalan penuh setelah `migrate:fresh`.

Verifikasi:
~~~bash
php artisan migrate:fresh
make 2
~~~

Hasil:
- seluruh chain level 2 selesai tanpa error
- correction baseline berhasil
- payment baseline berhasil
- refund baseline berhasil

### Make Level 3

Monster level 3 juga sudah berhasil setelah patch procurement overflow dan distribusi stok transaction load.

Verifikasi:
~~~bash
php artisan migrate:fresh
make 3
~~~

Hasil:
- procurement load berhasil
- expense load berhasil
- transaction load berhasil
- payment load berhasil
- refund load berhasil
- correction load berhasil

## Catatan Operasional

### Rerun Safety

Seeder yang dibuat pada scope ini didesain agar rerun-safe pada prefix seed masing-masing.

Prefix utama yang dipakai:

- level 2
  - `seed-note-bl-*`
  - `seed-pay-bl-*`
  - `seed-ref-bl-*`
  - `seed-exp-bl-*`

- level 3
  - `seed-note-load-*`
  - `seed-pay-load-*`
  - `seed-ref-load-*`
  - `seed-exp-load-*`
  - `seed-load-si-*`
  - `seed-load-sr-*`
  - `seed-load-im-*`
  - `seed-load-sp-*`

### Batas Pemakaian

Seeder ini aman untuk:

- dev
- test
- QA
- local verification
- fresh database validation

Seeder ini tidak ditujukan untuk:

- database operasional yang sudah bercampur data manual pengguna
- environment yang menganggap histori audit manual sebagai data permanen yang harus tetap bersih dari seed

## Command Resmi yang Dipakai

### Seed Level 1
~~~bash
make seed-1
~~~

atau

~~~bash
make 1
~~~

atau

~~~bash
make seed LEVEL=1
~~~

### Seed Level 2
~~~bash
make seed-2
~~~

atau

~~~bash
make 2
~~~

atau

~~~bash
make seed LEVEL=2
~~~

### Seed Level 3
~~~bash
make seed-3
~~~

atau

~~~bash
make 3
~~~

atau

~~~bash
make seed LEVEL=3
~~~

## File yang Dibuat atau Diubah

### File Baru

- `database/seeders/Support/SeedWindow.php`
- `database/seeders/Support/SeedDensity.php`
- `database/seeders/SeedLevel1Seeder.php`
- `database/seeders/SeedLevel2Seeder.php`
- `database/seeders/SeedLevel3Seeder.php`
- `database/seeders/Expense/ExpenseBaselineSeeder.php`
- `database/seeders/Transaction/CustomerTransactionBaselineSeeder.php`
- `database/seeders/Transaction/CustomerPaymentBaselineSeeder.php`
- `database/seeders/Transaction/CustomerRefundBaselineSeeder.php`
- `database/seeders/Transaction/CustomerCorrectionBaselineSeeder.php`
- `database/seeders/Load/ExpenseLoadSeeder.php`
- `database/seeders/Load/ProcurementLoadSeeder.php`
- `database/seeders/Load/CustomerTransactionLoadSeeder.php`
- `database/seeders/Load/CustomerPaymentLoadSeeder.php`
- `database/seeders/Load/CustomerRefundLoadSeeder.php`
- `database/seeders/Load/CustomerCorrectionLoadSeeder.php`

### File Existing yang Diubah

- `database/seeders/Product/ProductScenarioEditedSeeder.php`
- `database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php`
- `Makefile`

## Status Akhir

Status pekerjaan:

- `seed 1` selesai
- `seed 2` selesai
- `seed 3` selesai
- target `make` selesai
- verifikasi level 2 selesai
- verifikasi level 3 selesai

## Safest Next Step

Langkah aman berikutnya, jika mau dilanjutkan:

1. tambahkan target helper opsional di `Makefile`, misalnya:
   - `make seed-fresh-2`
   - `make seed-fresh-3`

2. tambahkan command verifikasi pasca-seed, misalnya:
   - cek jumlah note baseline
   - cek jumlah note monster
   - cek refund count
   - cek correction count
   - cek procurement invoice count

3. buat README singkat untuk tim agar format penggunaan seed level ini konsisten

## Progress Final

Progress pekerjaan seeder level ini: **100%**
