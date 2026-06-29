# App Kasir Hexagonal

Sistem kasir dan operasional bengkel untuk servis, sparepart, stok, pembelian supplier, pembayaran, koreksi transaksi, audit perubahan, dan laporan.

Project ini dibangun untuk satu jenis masalah yang sering diremehkan: aplikasi kasir yang terlihat sederhana di layar, tetapi sebenarnya menyentuh uang, stok, hutang, piutang, koreksi data, histori transaksi, dan laporan bisnis. Kalau bagian itu longgar, aplikasi tetap bisa terlihat “jalan”, sambil diam-diam membuat laporan dan stok rusak.

## Gambaran singkat

App ini bukan hanya form penjualan.

App ini mencakup alur operasional bengkel dari depan sampai belakang:

- kasir membuat nota servis dan sparepart;
- admin mengelola produk, stok, supplier, faktur supplier, dan laporan;
- transaksi bisa berisi jasa, sparepart toko, produk luar, atau paket servis;
- pembayaran bisa dicatat dan dilacak;
- nota yang sudah berjalan tetap bisa dikoreksi lewat jalur yang diaudit;
- refund dan perubahan setelah transaksi tidak dibiarkan menghilang tanpa jejak;
- stok dan nilai persediaan dijaga agar tetap bisa dijelaskan;
- laporan dibangun dari data yang punya riwayat, bukan dari angka yang jatuh dari langit.

## Kenapa app ini padat?

Karena domain bengkel tidak sesederhana “jual barang, cetak nota”.

Dalam praktik nyata:

- satu nota bisa berisi jasa dan sparepart sekaligus;
- barang bisa dari stok toko atau produk luar;
- pembayaran bisa terjadi sebelum atau setelah koreksi;
- nota bisa lunas, belum lunas, dibatalkan, direvisi, atau direfund;
- faktur supplier bisa mengubah harga modal dan pajak;
- stok tidak boleh minus tanpa aturan;
- laporan harus tetap cocok dengan transaksi, pembayaran, refund, dan stok;
- perubahan data harus bisa dilacak siapa, kapan, dan kenapa.

App ini mencoba menangani keruwetan itu secara eksplisit, bukan menutupinya dengan tombol “Simpan”.

## Modul utama

### Transaksi kasir

Kasir dapat membuat nota transaksi untuk servis dan penjualan sparepart. Nota dapat memuat beberapa jenis rincian, termasuk jasa, barang toko, produk luar, dan paket servis.

Setiap nota membawa data yang dibutuhkan untuk pembayaran, stok, histori, dan laporan.

### Workspace transaksi

Alur input transaksi dibuat sebagai workspace agar transaksi kompleks tetap bisa dikelola. Tujuannya bukan sekadar cepat input, tetapi juga menjaga agar rincian transaksi tidak kehilangan konteks.

### Produk dan stok

Produk sparepart dikelola sebagai master data. Stok diperlakukan sebagai data sensitif karena memengaruhi operasional, pembelian, laporan, dan nilai persediaan.

Perubahan stok tidak dianggap sebagai angka bebas. Perubahan harus punya sumber dan alasan yang bisa dilacak.

### Supplier dan faktur pembelian

Admin dapat mencatat faktur supplier, rincian barang, tanggal pengiriman, jatuh tempo, pajak supplier, total faktur, status penerimaan, dan riwayat revisi.

Faktur supplier tidak hanya disimpan sebagai dokumen diam. Jika direvisi, sistem menyimpan versi dan ringkasan perubahan agar owner bisa melihat perubahan antar versi.

### Pembayaran

Sistem menangani pembayaran customer dan pembayaran supplier sebagai bagian dari lifecycle transaksi.

Pembayaran tidak boleh membuat data menjadi ambigu. Status transaksi, total tagihan, saldo, dan laporan harus tetap sejalan.

### Koreksi nota

Transaksi yang sudah dibuat dapat dikoreksi lewat jalur yang terkontrol. Koreksi setelah transaksi selesai adalah area yang rawan merusak data, jadi sistem membuatnya eksplisit dan tercatat.

Koreksi bukan sekadar edit bebas. Koreksi harus tetap menjaga hubungan antara nota, pembayaran, refund, stok, dan laporan.

### Refund

Refund diperlakukan sebagai event bisnis, bukan sekadar angka negatif. Sistem membedakan tanggal bisnis refund dan timestamp audit agar laporan tidak kacau hanya karena timezone atau format tampilan.

### Laporan

Sistem menyediakan laporan untuk membantu owner melihat kondisi operasional, transaksi, pembayaran, stok, dan profit.

Targetnya bukan hanya “ada tabel”, tetapi laporan yang bisa dipertanggungjawabkan karena sumber datanya jelas.

### Audit dan riwayat perubahan

Bagian penting dari sistem ini adalah auditability.

Perubahan sensitif harus punya riwayat. Owner harus bisa melihat data berubah dari apa ke apa, kapan terjadi, dan alasan perubahan jika relevan.

## Nilai utama project

### 1. Presisi data

Uang, stok, pembayaran, dan laporan tidak boleh “kurang lebih benar”. Selisih kecil dalam sistem operasional bisa menjadi masalah besar.

### 2. Auditability

Setiap perubahan penting harus bisa dijelaskan. Sistem tidak boleh menjadi kotak hitam yang hanya menyimpan hasil akhir.

### 3. Editable, tapi tetap aman

Operasional nyata butuh data bisa dikoreksi. Tetapi koreksi tidak boleh menghapus jejak atau membuat laporan kehilangan makna.

### 4. Arsitektur modular

Project ini menggunakan pendekatan Hexagonal Architecture agar aturan bisnis tidak tenggelam di controller, view, atau query database.

### 5. Testing serius

Project ini memiliki test suite besar karena banyak alur bisnis saling terkait. Ketika satu fitur menyentuh uang, stok, atau laporan, perubahan kecil pun harus dibuktikan.

## Untuk siapa repository ini?

Repository ini cocok dibaca oleh:

- owner bisnis yang ingin memahami arah sistem;
- recruiter atau reviewer yang ingin melihat kompleksitas project;
- developer yang ingin melihat contoh aplikasi Laravel dengan domain operasional yang padat;
- auditor teknis yang ingin melihat bagaimana transaksi, stok, pembayaran, dan laporan dijaga;
- saya di masa depan, ketika lupa kenapa semua ini dibuat seketat ini.

## Stack ringkas

- Laravel
- Blade
- MySQL
- Hexagonal Architecture
- Feature tests dan characterization tests
- Dokumentasi ADR, blueprint, lifecycle, dan archive

## Dokumentasi teknis

README ini sengaja dibuat untuk pembaca umum.

Untuk pembahasan teknis berat, lihat:

- [`README_TECHNICAL.md`](README_TECHNICAL.md)
- [`docs/01_standards/`](docs/01_standards/)
- [`docs/02_architecture/`](docs/02_architecture/)
- [`docs/03_blueprints/`](docs/03_blueprints/)
- [`docs/04_lifecycle/`](docs/04_lifecycle/)
- [`docs/99_archive/`](docs/99_archive/)## Status

Project ini aktif dikembangkan dan sudah melewati banyak siklus audit, perbaikan bug, hardening, dan verifikasi.

Beberapa area yang sudah diperkuat:

- transaksi kasir multi-item;
- koreksi nota setelah transaksi;
- refund dan laporan refund;
- supplier invoice dan version timeline;
- pajak supplier;
- payment lifecycle;
- audit log;
- owner-facing Indonesian UI cleanup;
- timestamp display Asia/Makassar;
- production diagnostic tanpa repair data berbahaya.

## Catatan akhir

App ini padat karena masalahnya memang padat.

Aplikasi kasir yang menyentuh uang, stok, pembayaran, hutang, piutang, refund, dan laporan tidak cukup hanya “bisa CRUD”. Kalau sistem seperti ini dibuat terlalu santai, yang santai biasanya cuma developernya. Datanya tidak.
