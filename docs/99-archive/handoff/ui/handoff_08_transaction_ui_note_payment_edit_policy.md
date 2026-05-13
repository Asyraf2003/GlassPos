# Handoff UI Transaksi Nota Tunggal — Create, Payment, Editability, Audit, dan Snapshot Historis

## Metadata
- Tanggal: 2026-03-25
- Nama slice / topik: UI transaksi nota tunggal berbasis baris dinamis + payment setelah create + editability auditable
- Workflow step: UI transaksi / nota
- Status: Logic & blueprint final terkunci, siap masuk halaman eksekusi
- Progres: 100%

---

## Tujuan handoff
Dokumen ini mengunci **alur kasir**, **aturan admin**, **status sistem**, **batas edit**, **payment flow**, **snapshot historis**, dan **ekspektasi laporan** untuk UI transaksi berbasis **1 nota**.

Executor halaman berikutnya **tidak perlu membuat blueprint baru** dan **tidak boleh membuka ulang logic inti**.  
Yang boleh relatif dan disesuaikan hanya:
- desain visual UI,
- layout,
- copywriting kecil,
- detail styling,
- bentuk komponen interaktif.

Yang **tidak boleh diubah** tanpa alasan domain yang sangat kuat:
- alur create,
- alur payment,
- arti state,
- policy edit kasir/admin,
- paid note correction-only,
- prinsip audit,
- prinsip snapshot historis.

---

## Target halaman kerja
Target halaman kerja yang harus dipahami executor:

1. Membuat **1 nota** dengan **baris dinamis**.
2. Baris nota hanya punya 2 tipe:
   - `Produk`
   - `Servis`
3. Setelah nota dibuat, user bisa:
   - langsung lanjut bayar,
   - atau skip dan hanya membuka nota.
4. Payment dilakukan **setelah create**, bukan dipaksa melekat rumit saat input baris.
5. Payment dapat:
   - bayar semua,
   - bayar sebagian,
   - atau skip.
6. Kasir tetap merasa sedang memakai sistem kasir biasa:
   - buat nota,
   - tambah baris,
   - lihat grand total,
   - pilih yang dibayar sekarang,
   - input uang masuk,
   - lihat kembalian,
   - selesai.
7. Nota tidak membutuhkan ritual manual “close” yang membebani kasir.
8. Open note tetap editable sesuai policy.
9. Paid note tidak editable bebas; harus masuk correction-style flow.
10. Laporan harus tetap presisi walaupun budaya client menuntut editable dan operasional yang fleksibel.

---

## Referensi yang dipakai [REF]
Gunakan ini sebagai acuan saat eksekusi:
- Blueprint project kasir v1
- Workflow v1
- ADR one-note multi-item
- ADR paid-note correction requires audit
- ADR audit-first sensitive mutations
- Handoff Step 07 note multi item
- Handoff Step 08 payment receivable
- Handoff Step 09 correction refund audit
- Tree repo terbaru yang sudah diberikan di diskusi
- Keputusan final yang terkunci di percakapan ini

Catatan:
- Executor tidak perlu bertanya ulang soal logic bisnis inti bila masih berada dalam pagar keputusan dokumen ini.
- Jika executor menemukan konflik nyata di repo existing, tampilkan konflik konkret dan usulan penyesuaian minimum, bukan membuka blueprint dari nol.

---

## Fakta terkunci [FACT]

### Struktur bisnis
- Sistem transaksi menggunakan **1 nota** sebagai pusat interaksi user.
- 1 nota dapat berisi **banyak baris dinamis**.
- Baris dapat dicampur antara produk dan servis.
- User lapangan tetap berpikir dalam pola “tulis nota”, bukan “buat beberapa entitas domain”.

### Tipe baris
- Tipe baris final hanya:
  - `Produk`
  - `Servis`
- Tidak ada tipe baris terpisah untuk “servis dengan sparepart milik pembeli”.
- Jika pembeli membawa sparepart sendiri, perlakukan sebagai **servis biasa** dan bila perlu simpan pada keterangan.

### Harga
- Harga produk mengikuti master admin.
- Harga servis diinput manual oleh kasir/admin sesuai policy halaman.
- Grand total nota adalah penjumlahan semua subtotal baris.

### Telepon customer
- Nomor telepon customer bersifat **opsional**.
- Telepon ditaruh pada header nota, bukan dijadikan entitas atau flow terpisah.

### Payment
- Payment adalah kejadian finansial di level **nota**, bukan pembayaran penuh per baris dengan uang masuk/kembalian terpisah.
- User boleh memilih:
  - bayar semua baris,
  - bayar sebagian baris,
  - atau tidak bayar saat itu.
- Metode payment hanya:
  - `cash`
  - `tf`
- Kembalian hanya relevan untuk `cash`.
- Untuk `tf`, tidak ada kembalian.

### Budaya client
- Sistem harus menyesuaikan budaya client, bukan memaksa client belajar sistem rumit.
- Editable diperbolehkan selama tetap punya jejak, alasan, dan snapshot yang jelas.
- Laporan wajib tetap menjelaskan kondisi sekarang, kondisi awal, dan perubahan yang terjadi.

---

## Scope yang dipakai

### [SCOPE-IN]
- Header nota
- Baris dinamis produk/servis
- Ringkasan grand total
- Flow create nota
- Flow bayar sekarang setelah create
- Bayar semua / sebagian / skip
- Modal payment gaya kalkulator
- Cash / transfer
- Kembalian
- Payment status
- Workspace/item status
- Policy edit kasir
- Policy edit admin
- Paid note correction-only
- Audit reason
- Snapshot historis
- Tampilan current vs previous/original di detail/index/report
- Ekspektasi laporan uang/transaksi/stok/history

### [SCOPE-OUT]
- Refund detail
- Koreksi finansial rinci paid note di luar prinsip correction-only
- Integrasi Telegram
- PDF
- Detail teknis implementasi controller/service/DB
- Desain visual final
- Naming route final
- Naming request/DTO final
- Detail komponen front-end final

---

## Keputusan yang dikunci [DECISION]

### 1. Nota tetap satu, barisnya banyak
- 1 nota boleh berisi banyak baris.
- User dapat menambah baris berulang kali.
- Setiap baris memilih salah satu tipe:
  - `Produk`
  - `Servis`

### 2. Create dan payment dipisah 2 langkah operasional
- Langkah 1: buat nota dan semua barisnya.
- Langkah 2: setelah nota tersimpan, user bisa langsung lanjut ke flow payment atau skip.

### 3. Payment tidak dibuat rumit per baris
- Yang dipilih per baris hanyalah **apakah dibayar sekarang**.
- Uang masuk dan kembalian tetap terjadi **sekali per payment event** di level nota.

### 4. Kasir tidak dibebani tombol close manual
- Sistem tidak boleh bergantung pada kedisiplinan kasir untuk klik “tutup nota” manual sebagai syarat utama.
- Nota bisa tetap dibuka lagi selama policy edit mengizinkan.
- Secara bahasa operasional, saat seluruh outstanding = 0, nota dianggap selesai secara finansial.

### 5. “Paid” tidak sama dengan “riwayat hilang”
- Walaupun seluruh uang sudah diterima dan barang belum diambil, nota tetap sah sebagai bukti:
  - order,
  - barang,
  - uang masuk,
  - histori perubahan.

### 6. Editable boleh, tetapi auditable wajib
- Prinsip sistem:
  - boleh editable sesuai budaya client,
  - tetapi harus presisi,
  - harus ada alasan edit,
  - harus jelas siapa yang mengedit,
  - harus jelas role apa saat edit itu terjadi,
  - harus bisa membedakan nilai sebelum dan sesudah edit.

### 7. Admin mengikuti opsi 3
- Admin boleh edit open note lebih luas.
- Setelah lewat window kasir, edit admin tetap boleh **tetapi mode-nya ketat**:
  - alasan wajib,
  - snapshot before/after wajib,
  - user editor wajib terlihat,
  - role editor wajib terlihat.

### 8. Paid note tidak editable bebas
- Setelah nota `paid`, perubahan tidak lewat edit biasa.
- Harus masuk jalur **correction-style**.
- Perubahan paid note wajib:
  - alasan,
  - actor,
  - role,
  - before/after,
  - waktu perubahan.

---

## Model mental user yang harus dipertahankan
User harus merasa bahwa sistem hanya meminta dia melakukan ini:

1. Isi nota.
2. Tambah baris produk/servis.
3. Simpan nota.
4. Kalau mau langsung bayar:
   - pilih baris yang dibayar sekarang,
   - pilih cash atau tf,
   - masukkan uang,
   - lihat kembalian kalau cash.
5. Kalau belum mau bayar:
   - cukup buka nota lagi nanti.

User **tidak** boleh dipaksa memahami:
- note aggregate,
- payment allocation engine,
- correction flow domain,
- audit log model,
- state model yang rumit.

Semua itu adalah tanggung jawab sistem.

---

## Blueprint final alur kasir

### A. Create nota
Urutan:
1. Kasir buka halaman create nota.
2. Isi header nota:
   - nama customer
   - telp opsional
   - tanggal nota
3. Kasir klik `Tambah Baris`.
4. Pada tiap baris, kasir pilih tipe:
   - `Produk`
   - `Servis`
5. Form baris menyesuaikan:
   - jika `Produk`:
     - pilih produk
     - qty
     - harga otomatis dari master
     - subtotal otomatis
   - jika `Servis`:
     - isi nama/keterangan servis
     - isi harga servis manual
     - subtotal manual
6. Kasir dapat menambah banyak baris.
7. Sistem menghitung `Grand Total Nota`.
8. Kasir klik `Simpan Nota`.

### Hasil setelah create
- Nota berhasil tersimpan.
- Payment status awal:
  - `unpaid`
- Item/workspace status awal:
  - default `open` kecuali nanti ada policy lebih sempit saat implementasi.
- Sistem redirect / membuka detail nota.

---

### B. Setelah create: pilih bayar atau skip
Di detail nota setelah create, user diberi alur yang ringan:

Pilihan:
- `Bayar Sekarang`
- atau `Skip / Hanya Buka Nota`

Jika skip:
- nota tetap tersimpan,
- dapat dibuka lagi nanti,
- belum ada payment event baru,
- outstanding tetap sesuai total yang belum dibayar.

Jika bayar sekarang:
- user masuk ke mode pilih baris mana yang dibayar sekarang.

---

### C. Pilih baris yang dibayar sekarang
Aturan:
- User dapat:
  - `ceklist semua` dengan 1 klik,
  - atau ceklist sebagian baris.
- Tujuan ceklist:
  - menentukan subtotal mana yang masuk payment event saat ini.
- Yang tidak dicentang:
  - tetap outstanding.

Executor wajib menjaga bahwa:
- pilihan bayar sekarang terjadi **setelah create**, bukan membebani input create.
- logika ini harus ringan, cepat, dan minim klik.

---

### D. Modal payment gaya kalkulator
Saat user lanjut bayar:

1. Tampilkan modal payment dengan konsep kalkulator.
2. Pilih metode:
   - `cash`
   - `tf`
3. Tampilkan total yang dibayar sekarang.
4. Input nominal uang masuk.
5. Jika `cash`:
   - sistem hitung kembalian.
6. Jika `tf`:
   - tidak ada kembalian.

Tujuan modal:
- cepat untuk kasir,
- terasa seperti proses kasir konvensional,
- tidak terasa seperti form akuntansi.

---

### E. Hasil setelah payment
Setelah payment diproses:

- Sistem menambah uang masuk pada nota.
- Sistem memperbarui:
  - `Total Sudah Dibayar`
  - `Sisa Tagihan`
  - `Payment Status`
- Payment status mengikuti kondisi:
  - `unpaid` jika belum ada uang masuk valid
  - `partial` jika sebagian sudah masuk
  - `paid` jika seluruh outstanding = 0

Catatan:
- Jika seluruh outstanding = 0, secara operasional nota dianggap selesai secara finansial.
- Sistem tidak boleh mengandalkan tindakan manual close dari kasir untuk membuat uang masuk dianggap valid.

---

## State model final yang harus dipakai

### 1. Payment Status
Ini murni status uang:
- `unpaid`
- `partial`
- `paid`

### 2. Item / Workspace Status
Ini murni status kerja/baris:
- `open`
- `done`
- `canceled`

### 3. Editability Policy
Ini bukan badge utama UI, tetapi aturan sistem:
- Kasir:
  - boleh edit `open note`
  - selama **hari ini** atau **kemarin**
- Admin:
  - mengikuti opsi 3
  - boleh edit `open note` lebih luas
  - tetapi setelah lewat window kasir, mode edit harus lebih ketat:
    - reason wajib
    - before/after wajib
    - user editor jelas
    - role editor jelas

### Larangan keras
Jangan gabungkan:
- status uang,
- status kerja,
- status edit,
ke dalam satu status tunggal.

---

## Rule matrix edit final

### A. Kasir
Kasir boleh:
- edit open note
- window:
  - hari ini
  - kemarin
- edit biasa hanya untuk nota yang masih berada di jalur editable normal

Kasir wajib saat edit:
- memberikan alasan edit
- tercatat user siapa
- tercatat role apa
- tercatat timestamp
- menyisakan jejak before/after untuk field yang sensitif atau penting secara laporan

Kasir tidak boleh:
- edit bebas paid note
- menghapus jejak perubahan lama
- menimpa histori sehingga nilai lama hilang total

---

### B. Admin
Admin mengikuti **opsi 3**:

Admin boleh:
- edit open note secara lebih luas daripada kasir
- menangani edit setelah window kasir lewat

Tetapi admin wajib:
- reason edit wajib
- user editor wajib terlihat
- role editor wajib terlihat
- before/after wajib
- historisasi perubahan wajib jelas di laporan/detail

Admin tidak boleh:
- menjadikan edit admin sebagai overwrite tanpa histori
- menghapus konteks nilai lama
- menjadikan paid note editable bebas seperti open note biasa

---

### C. Paid Note
Paid note:
- tidak editable bebas
- tidak boleh diperlakukan sama dengan open note

Jika ada perubahan pada paid note:
- harus lewat flow correction-style
- alasan wajib
- user wajib
- role wajib
- before/after wajib
- histori harus bisa menunjukkan:
  - nilai awal
  - nilai sesudah correction
  - siapa yang mengubah
  - alasan perubahan

---

## Snapshot historis yang wajib ada

### Prinsip utama
Sistem harus bisa membedakan:
- nilai saat ini,
- nilai saat dibuat,
- atau nilai sebelum edit terakhir / correction terakhir.

### Pola tampilan yang dikunci
Gunakan pola ini di detail/index/report:

- **teks utama** = nilai current / terbaru
- **teks kecil sekunder** = snapshot historis

Contoh pola:
- Nama utama: `PT terbaru`
- Teks kecil: `Saat nota dibuat: PT lama`

Pola yang sama bisa dipakai untuk:
- customer name
- harga/baris tertentu
- nama produk yang berubah di master
- field penting lain yang berpotensi berubah

### Tujuan
- user tetap melihat data current
- histori tetap tidak hilang
- laporan tetap presisi
- koreksi dan edit tetap bisa dipahami

---

## Angka yang wajib muncul pada detail nota
Executor wajib menjaga bahwa detail nota minimal memiliki angka berikut:

1. `Grand Total Nota`
   - total semua subtotal baris

2. `Total Dipilih untuk Dibayar Sekarang`
   - total dari baris yang dicentang saat payment event ini

3. `Total Sudah Dibayar`
   - total uang valid yang sudah diterima untuk nota ini

4. `Sisa Tagihan`
   - selisih outstanding saat ini

5. `Uang Masuk Sekarang`
   - nominal yang diproses pada modal payment event saat ini

6. `Kembalian`
   - hanya jika metode `cash`

Tanpa angka-angka ini, user akan bingung membedakan:
- total nota,
- total dibayar hari ini,
- total sudah pernah dibayar,
- sisa hutang/tagihan.

---

## Reporting guarantees yang tidak boleh rusak
Implementasi nanti harus memastikan hasil ini tetap benar.

### 1. Laporan uang masuk harian
- uang yang benar-benar diterima pada hari itu harus masuk laporan hari itu
- cash dan tf harus dapat dibedakan
- payment event note-level harus tercermin bersih

### 2. Laporan transaksi
- nota tetap tercatat jelas
- isi baris produk/servis tetap terlihat
- terlihat mana yang pernah dibayar dan mana yang masih outstanding

### 3. Laporan stok produk
- hanya baris `Produk` yang memengaruhi stok produk toko
- baris `Servis` tidak otomatis mengurangi stok produk toko kecuali memang nanti diimplementasikan aturan tersendiri yang valid secara domain dan sudah diputuskan khusus

### 4. Histori edit / audit report
- dapat menunjukkan current value
- dapat menunjukkan original / previous snapshot
- dapat menunjukkan:
  - user editor
  - role editor
  - alasan edit
  - waktu edit

---

## UI behavior yang dikunci
Yang boleh relatif:
- tata letak
- warna
- modal style
- bentuk tombol
- list atau card
- drawer vs inline panel

Yang tidak boleh diubah:
- create dulu, payment sesudah create
- ceklist semua / sebagian untuk bayar sekarang
- modal kalkulator
- cash/tf
- kembalian hanya untuk cash
- edit kasir open note hari ini/kemarin
- admin opsi 3
- paid note correction-only
- snapshot historis current vs previous/original

---

## Larangan untuk executor
Executor **tidak boleh** melakukan ini tanpa eskalasi ulang yang sangat konkret:

1. Membuat blueprint baru yang mengubah alur inti.
2. Memindahkan payment rumit ke level tiap baris dengan uang masuk dan kembalian masing-masing.
3. Mewajibkan kasir menutup nota manual sebagai inti sistem.
4. Menghapus snapshot nilai lama saat edit.
5. Mengizinkan paid note diedit bebas seperti open note.
6. Menggabungkan status uang, status kerja, dan status edit menjadi satu status tunggal.
7. Mengubah baris dinamis menjadi hanya satu item statis.
8. Menganggap user dan role menempel menjadi satu identitas; sistem harus tetap jelas membaca:
   - user siapa
   - role apa saat edit itu terjadi

---

## Catatan ADR
### Apakah perlu ADR baru?
**Tidak wajib membuat ADR baru** jika eksekusi masih berada dalam pagar keputusan berikut:
- satu nota multi-baris
- payment note-level
- create lalu payment sesudah create
- paid note correction-only
- edit admin opsi 3
- audit-first mutation
- snapshot historis current vs previous/original

### ADR wajib dipertimbangkan / diupdate jika executor menemukan kebutuhan mengubah salah satu ini:
- paid note boleh edit bebas
- payment dipindah total ke workspace-level penuh
- arti paid/open berubah
- rule kasir hari ini/kemarin diubah
- admin opsi 3 diubah
- snapshot historis dihilangkan
- close manual dijadikan wajib

Jika tidak ada perubahan kontrak domain seperti di atas, **cukup eksekusi berdasarkan handoff ini**, tidak perlu ADR baru.

---

## Expected hasil akhir executor
Setelah eksekusi, orang yang membuka halaman transaksi harus langsung merasakan ini:

### Dari sisi kasir
- gampang buat nota
- gampang tambah produk/servis
- gampang lihat grand total
- gampang pilih bayar semua/sebagian
- gampang input uang dan lihat kembalian
- tidak dipaksa ritual close manual

### Dari sisi admin
- bisa membuka open note
- bisa edit sesuai opsi 3
- histori edit terbaca jelas
- user dan role editor terlihat

### Dari sisi laporan
- uang masuk harian bersih
- transaksi jelas
- stok tidak rusak
- snapshot edit/history jelas

---

## Definition of done untuk halaman eksekusi
Halaman eksekusi dianggap benar bila:

1. User bisa membuat 1 nota dengan banyak baris produk/servis.
2. Produk menarik harga default dari master.
3. Servis mengisi harga manual.
4. Grand total otomatis akurat.
5. Setelah create, user bisa langsung:
   - skip,
   - bayar semua,
   - atau bayar sebagian.
6. Modal payment bergaya kalkulator ada.
7. Metode payment hanya cash/tf.
8. Kembalian hanya muncul untuk cash.
9. Nota open bisa diedit oleh kasir dalam window hari ini/kemarin.
10. Admin mengikuti opsi 3.
11. Paid note tidak editable bebas.
12. Semua edit punya:
   - reason
   - user
   - role
   - before/after
13. Detail/index/report bisa menampilkan current vs historical snapshot untuk field penting.
14. Executor tidak membuat ulang blueprint atau bertanya ulang soal alur inti yang sudah terkunci di sini.

---

## Penutup
Dokumen ini adalah kontrak logic final untuk slice UI transaksi nota tunggal.

Executor halaman berikutnya harus membaca dokumen ini dengan pemahaman:
- “Saya tidak perlu mendesain ulang sistem.”
- “Saya tinggal menerjemahkan keputusan yang sudah final ini ke implementasi.”
- “Kalau saya bertanya, pertanyaan saya hanya boleh seputar preferensi desain UI atau konflik konkret di repo, bukan membuka ulang aturan bisnis.”

Jika tidak ada konflik nyata di repo existing, **eksekusi harus langsung jalan berdasarkan handoff ini**.
