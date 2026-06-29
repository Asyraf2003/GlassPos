<!-- HYPERPOS_LATEST_HANDOFF_START -->
Latest Handoff: docs/99_archive/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md
Latest Supporting Handoff: docs/99_archive/04_lifecycle/handoff/0050_legacy_timestamp_repair_handoff.md
Status: FINAL CLOSED / 0049-0050 FIXED / no production timestamp repair recommended
<!-- HYPERPOS_LATEST_HANDOFF_END -->

# App Kasir Hexagonal

App Kasir Hexagonal adalah sistem operasional bengkel untuk mengelola transaksi servis, sparepart, stok, pembelian supplier, pembayaran, koreksi data, dan laporan.

Project ini bukan sekadar aplikasi kasir sederhana. Fokus utamanya adalah menjaga data uang, stok, riwayat perubahan, dan laporan tetap bisa ditelusuri dengan jelas.

## Untuk siapa aplikasi ini?

Aplikasi ini dirancang untuk operasional bengkel atau toko sparepart yang butuh sistem kasir dengan kontrol data lebih ketat.

Contoh kebutuhan yang ditangani:

- mencatat transaksi servis dan penjualan sparepart;
- mengelola stok barang;
- mencatat faktur pembelian dari supplier;
- mencatat pembayaran customer dan pembayaran supplier;
- mengelola koreksi transaksi setelah nota dibuat;
- menyimpan riwayat perubahan penting;
- membuat laporan operasional dan keuangan.

## Kenapa project ini terlihat padat?

Karena domain kasir bengkel ternyata tidak sesederhana “barang keluar, uang masuk”.

Dalam operasional nyata, banyak kasus yang harus tetap aman:

- nota bisa salah input;
- barang bisa direvisi setelah transaksi;
- pembayaran bisa sebagian atau penuh;
- refund bisa terjadi setelah nota selesai;
- harga modal bisa berubah setelah barang diterima;
- faktur supplier bisa direvisi;
- stok tidak boleh berubah tanpa jejak;
- laporan harus tetap cocok dengan riwayat transaksi.

Kalau hal seperti ini ditangani asal-asalan, sistem kasir bisa terlihat jalan, tapi laporan dan stok pelan-pelan rusak. Kelihatannya sepele, lalu tiba-tiba manusia panik di depan Excel. Tradisi purba yang sayangnya masih bertahan.

## Prinsip utama

Project ini dibangun dengan beberapa prinsip:

1. Data uang harus presisi.
2. Perubahan stok harus punya jejak.
3. Koreksi data harus tercatat.
4. UI boleh dibuat mudah, tapi aturan bisnis tidak boleh kabur.
5. Laporan harus berasal dari data yang bisa dipertanggungjawabkan.
6. Perubahan penting harus diuji sebelum dianggap selesai.

## Fitur utama

### Transaksi bengkel

Sistem mendukung transaksi yang bisa berisi beberapa jenis item, seperti jasa servis, sparepart toko, dan item luar.

Transaksi tidak hanya dicatat sebagai total akhir, tetapi juga membawa detail yang diperlukan untuk stok, pembayaran, laporan, dan audit.

### Stok dan produk

Produk sparepart dikelola sebagai data utama. Perubahan stok tidak dianggap sekadar angka, tetapi bagian dari riwayat operasional.

Tujuannya agar stok tidak berubah diam-diam tanpa alasan yang jelas.

### Faktur supplier

Sistem mendukung pencatatan faktur supplier, rincian barang, pajak, total nota, status penerimaan, dan riwayat revisi.

Jika faktur direvisi, sistem menyimpan versi perubahan agar owner bisa melihat apa yang berubah.

### Pembayaran

Pembayaran customer dan supplier diperlakukan sebagai bagian penting dari lifecycle transaksi.

Sistem menjaga agar pembayaran tidak membuat saldo, laporan, atau status transaksi menjadi tidak konsisten.

### Koreksi dan refund

Aplikasi mendukung koreksi transaksi setelah nota dibuat, termasuk alur refund tertentu.

Bagian ini dibuat hati-hati karena koreksi setelah transaksi selesai adalah salah satu sumber kerusakan data paling umum di aplikasi operasional. Rupanya uang tidak suka diperlakukan seperti teks bebas di kolom komentar.

### Laporan

Laporan dibuat untuk membantu owner melihat kondisi operasional, bukan sekadar menampilkan tabel.

Targetnya adalah laporan yang bisa dipercaya karena berasal dari data transaksi, pembayaran, stok, dan audit yang saling terkait.

## Arsitektur singkat

Project ini menggunakan pendekatan Hexagonal Architecture.

Penjelasan sederhananya:

- aturan bisnis utama ditempatkan di bagian inti aplikasi;
- controller dan tampilan tidak menjadi sumber kebenaran;
- database, UI, dan framework adalah lapisan luar;
- perubahan UI tidak boleh diam-diam merusak aturan bisnis.

Pendekatan ini membuat project lebih padat, tapi lebih aman untuk aplikasi yang menyentuh uang, stok, dan laporan.

## Status project

Project ini aktif dikembangkan dan sudah melewati banyak siklus audit internal, perbaikan bug, dan penguatan lifecycle.

Workflow terbaru yang sudah ditutup:

- supplier invoice revision/reason;
- supplier invoice version timeline;
- note correction history;
- timestamp display Asia/Makassar;
- production timestamp diagnostic;
- keputusan bahwa production timestamp repair tidak diperlukan saat ini.

Dokumen teknis dan closure terbaru ada di:

- `docs/99_archive/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md`
- `docs/99_archive/04_lifecycle/handoff/0050_legacy_timestamp_repair_handoff.md`

## Untuk pembaca teknis

README ini sengaja dibuat lebih mudah dipahami.

Dokumentasi teknis tersedia di:

- `README_TECHNICAL.md`
- `docs/01_standards/`
- `docs/02_architecture/`
- `docs/03_blueprints/`
- `docs/04_lifecycle/`
- `docs/99_archive/`

## Catatan

Project ini tidak mengejar tampilan repository yang minimalis.

Project ini mengejar satu hal yang lebih penting untuk aplikasi operasional: perubahan data harus bisa dijelaskan.

Kalau sebuah sistem menyentuh uang, stok, transaksi, dan laporan, maka “yang penting jalan” bukan standar yang cukup.
