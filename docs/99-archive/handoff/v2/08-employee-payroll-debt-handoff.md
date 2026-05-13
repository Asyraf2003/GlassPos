# Handoff V2 - 08

## Ringkasan
Sesi ini menutup rangkaian perapihan UI dan kontrak operasional modul hutang karyawan, dengan fokus pada pemisahan tanggung jawab antar halaman, penyederhanaan istilah untuk user operasional, dan penghentian jalur domain yang salah.

Arah kerja yang dikunci selama sesi ini:
- Detail Karyawan fokus ke profil/identitas dan riwayat versi, tidak lagi mencampur hutang dan gaji.
- Detail Hutang fokus ke ringkasan dan riwayat, tidak lagi memuat form bayar maupun form principal.
- Principal/Tambah Hutang menjadi halaman terpisah.
- Principal hanya untuk tambah hutang, tidak boleh lagi dipakai mengurangi hutang.
- Status hutang di tabel riwayat hutang disederhanakan menjadi status operasional tunggal: `Aktif` atau `Lunas`.
- Aksi halaman hutang dirapikan menjadi: Detail Karyawan, Tambah Hutang, Bayar Hutang, Detail Hutang.
- Jalur `Detail Gaji` di halaman hutang dihapus karena tidak relevan.
- Modal bayar hutang mulai dipasang di halaman hutang, namun implementasi terakhir memunculkan regresi tombol `Aksi` dan patch guard JS sudah disiapkan, tetapi belum ada konfirmasi final dari user bahwa perilaku sudah pulih.

---

## Fakta yang sudah dikunci

### 1. Detail Karyawan
Kontrak lama yang menampilkan:
- Ringkasan Hutang
- Ringkasan Gaji
- Riwayat Gaji Karyawan
- tombol Lihat Hutang Karyawan

sudah diganti. Detail Karyawan sekarang difokuskan ke:
- Ringkasan Karyawan
- identitas awal
- riwayat versi karyawan

Test lama yang masih menagih UI lama sudah disesuaikan dan verify kembali hijau.

### 2. Detail Hutang
Halaman Detail Hutang sudah dipersempit menjadi halaman baca:
- ringkasan hutang
- riwayat pembayaran
- riwayat reversal pembayaran
- riwayat koreksi hutang

Form berikut sudah dikeluarkan dari halaman ini:
- Catat Pembayaran Hutang
- Koreksi Principal Hutang

### 3. Halaman Principal / Tambah Hutang
Halaman principal dipisah dari detail hutang dan diubah agar lebih operasional:
- form utama di kiri atas
- ringkasan di kiri bawah
- riwayat di kanan
- istilah UI disederhanakan ke Bahasa Indonesia yang lebih mudah

Arah domain yang dikunci:
- principal hanya untuk **Tambah Hutang**
- pengurangan hutang **tidak boleh** lewat principal
- pengurangan hutang harus lewat **Bayar Hutang**

### 4. Jalur pengurangan hutang via principal dihentikan
Step 1 untuk opsi domain yang lebih ketat sudah dikerjakan:
- request principal hanya menerima `increase`
- handler principal menolak `decrease`
- pesan sukses/failure principal diubah agar spesifik ke penambahan hutang
- riwayat data lama `decrease` tetap ditampilkan sebagai data lama, bukan perilaku baru
- test terkait principal/invariant disesuaikan dan verify kembali hijau

### 5. Status hutang di tabel hutang
Tampilan status yang tadinya berupa hitungan record seperti:
- `2 aktif / 1 lunas`

sudah diganti menjadi status operasional tunggal:
- `Aktif`
- `Lunas`

Secara data backend, count record masih ada di query untuk kebutuhan internal, tetapi tidak lagi dibocorkan ke UI utama.

### 6. Aksi halaman hutang
Aksi halaman hutang sudah dirapikan:
- `Detail Gaji` dihapus
- diganti menjadi `Detail Hutang`
- `Tambah Hutang` diarahkan ke halaman principal jika debt record sudah ada
- fallback ke create hutang tetap disediakan sebagai pengaman

### 7. Modal Bayar Hutang
Implementasi modal bayar hutang **sudah mulai dipasang** di halaman hutang.

Namun ada regresi:
- setelah patch awal modal bayar, tombol `Aksi` sempat mati total
- investigasi browser menunjukkan error runtime:
  - `Cannot set properties of null (setting 'href')`
- patch guard JS disiapkan untuk menangani mismatch id antara Blade dan JS agar modal aksi tidak mati total bila sebagian elemen tidak sinkron

**Status terakhir untuk bagian ini:**
- patch guard sudah diberikan
- belum ada bukti final dari user bahwa perilaku tombol `Aksi` dan modal bayar sudah pulih sepenuhnya setelah patch guard diterapkan

---

## Hasil verifikasi yang sudah terbukti
Beberapa kali selama sesi ini `make verify` kembali hijau setelah penyesuaian kontrak test dan UI.

Status terakhir yang dikonfirmasi user sebelum regresi final modal:
- `491 passed`
- `make verify` hijau

Catatan penting:
- verify hijau **tidak otomatis** berarti interaksi frontend terakhir sudah sehat, karena regresi tombol `Aksi` muncul setelah patch modal bayar dan pembuktiannya berhenti di tahap identifikasi error + penyediaan patch guard.

---

## File yang dipastikan berubah selama sesi
Berikut daftar file yang disentuh atau diganti selama sesi ini berdasarkan pekerjaan yang sudah diarahkan:

### Modul employee
- `resources/views/admin/employees/show.blade.php`
- `resources/views/admin/employees/index.blade.php`
- `public/assets/static/js/pages/admin-employees-table.js`
- `public/assets/static/js/pages/admin-employee-table-actions.js`
- `app/Adapters/Out/EmployeeFinance/DatabaseEmployeeTableReaderAdapter.php`
- `app/Adapters/Out/EmployeeFinance/DatabaseEmployeeDetailPageQuery.php`

### Modul employee debt
- `resources/views/admin/employee_debts/index.blade.php`
- `resources/views/admin/employee_debts/show.blade.php`
- `resources/views/admin/employee_debts/principal.blade.php`
- `public/assets/static/js/pages/admin-employee-debts-table.js`
- `public/assets/static/js/pages/admin-employee-debt-table-actions.js`
- `app/Adapters/Out/EmployeeFinance/DatabaseEmployeeDebtListPageQuery.php`
- `app/Adapters/Out/EmployeeFinance/DatabaseEmployeeDebtAdjustmentListQuery.php`
- `app/Adapters/In/Http/Controllers/Admin/EmployeeDebt/EmployeeDebtPrincipalPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/EmployeeDebt/StoreEmployeeDebtAdjustmentController.php`
- `app/Adapters/In/Http/Requests/EmployeeFinance/AdjustEmployeeDebtPrincipalRequest.php`
- `app/Application/EmployeeFinance/UseCases/AdjustEmployeeDebtPrincipalHandler.php`
- `routes/web/admin_employee_debts.php`

### Test yang sudah disesuaikan
- `tests/Feature/EmployeeFinance/EmployeeDetailPageFeatureTest.php`
- `tests/Feature/EmployeeFinance/ReversePayrollDisbursementFeatureTest.php`
- `tests/Feature/EmployeeFinance/AdjustEmployeeDebtFeatureTest.php`
- `tests/Feature/EmployeeFinance/EmployeeDebtInvariantFeatureTest.php`
- `tests/Feature/EmployeeFinance/EmployeeDebtDetailPageFeatureTest.php`

---

## Masalah yang sudah selesai
1. Detail Karyawan terlalu gemuk dan mencampur domain gaji/hutang.
2. Detail Hutang terlalu banyak aksi form di satu halaman.
3. Principal bisa dipakai mengurangi hutang.
4. Aksi halaman hutang memuat menu yang tidak relevan.
5. Status hutang tampil sebagai jumlah record, bukan status operasional.
6. Kontrak test lama masih menagih UI lama dan sudah disesuaikan.

---

## Masalah yang masih terbuka

### 1. Modal Bayar Hutang belum ditutup tuntas
Tujuan akhirnya sudah jelas:
- `Bayar Hutang` di aksi halaman hutang harus membuka modal/dialog
- submit tetap memakai route pembayaran yang sudah ada

Status saat handoff:
- modal bayar sudah mulai dipasang
- sempat memunculkan regresi tombol `Aksi`
- investigasi menemukan mismatch elemen DOM vs JS
- patch guard JS sudah diberikan
- **belum ada konfirmasi final** bahwa modal bayar sekarang bekerja normal end-to-end

### 2. Data lama multi hutang aktif
User memilih arah domain yang lebih keras:
- per karyawan hanya boleh ada satu hutang aktif
- status UI harus tunggal: `Aktif/Lunas`

UI sudah disederhanakan, tetapi cleanup data lama belum dikerjakan.

Masih perlu diputuskan secara aman:
- bagaimana memperlakukan employee yang punya lebih dari satu record aktif
- apakah akan digabung, ditutup, dipilih salah satu, atau dibuat migrasi korektif dengan audit trail

### 3. Redirect pasca submit bayar
Modal bayar dirancang agar memicu submit dari halaman hutang.
Masih perlu dipastikan UX finalnya:
- setelah submit berhasil, tetap redirect ke detail hutang
- atau kembali ke halaman hutang dengan feedback sukses
- atau dialog cukup close lalu tabel refresh

Belum dikunci di sesi ini.

---

## Posisi kerja saat handoff
Posisi kerja bisa dianggap seperti ini:

### Selesai
- restrukturisasi Detail Karyawan
- restrukturisasi Detail Hutang
- pemisahan halaman principal
- principal hanya tambah hutang
- perapihan aksi halaman hutang
- penyederhanaan status hutang di UI
- penyesuaian test sampai verify hijau

### Belum selesai
- finalisasi modal Bayar Hutang
- cleanup data lama multi hutang aktif
- penutupan UX redirect setelah submit bayar

---

## Safest next step
Langkah paling aman setelah membuka sesi berikutnya:

1. Pastikan patch guard di:
   - `public/assets/static/js/pages/admin-employee-debt-table-actions.js`
   benar-benar sudah diterapkan di working tree lokal.

2. Hard reload browser:
   - `Ctrl + Shift + R`

3. Verifikasi manual flow ini:
   - halaman `Hutang Karyawan`
   - klik `Aksi`
   - modal aksi muncul
   - klik `Bayar Hutang`
   - modal pembayaran muncul
   - isi nominal + catatan
   - submit

4. Bila flow modal bayar sudah stabil, baru lanjut ke keputusan UX:
   - apakah redirect setelah submit tetap ke detail hutang
   - atau kembali ke halaman tabel hutang

5. Setelah modal bayar final, baru buka pekerjaan cleanup data legacy multi hutang aktif.

---

## Catatan domain yang sudah tidak boleh dibuka ulang tanpa bukti baru
- Detail Karyawan tidak lagi memuat ringkasan hutang dan gaji.
- Detail Hutang tidak lagi memuat form principal dan form bayar.
- Principal tidak lagi boleh dipakai mengurangi hutang.
- Pengurangan hutang hanya lewat pembayaran.
- Status UI di halaman hutang harus tampil sebagai `Aktif` atau `Lunas`.
- Aksi halaman hutang tidak lagi memuat `Detail Gaji`.

---

## Ringkasan satu paragraf
Sesi ini berhasil memisahkan ulang batas halaman employee dan employee debt agar lebih operasional: Detail Karyawan kini fokus ke identitas, Detail Hutang fokus ke ringkasan dan riwayat, Principal dipisah dan dibatasi hanya untuk tambah hutang, serta status hutang di tabel disederhanakan menjadi status tunggal. Semua penyesuaian kontrak test yang terdampak sudah dibuat hingga verify kembali hijau. Satu bagian yang masih perlu ditutup rapi adalah finalisasi modal Bayar Hutang dan verifikasi bahwa regresi tombol `Aksi` benar-benar sudah hilang setelah patch guard JS terakhir.
