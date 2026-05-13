
# Handoff V2 - Employee + Payroll Manual + Debt Summary Integration



Tanggal: 2026-04-11



## Ringkasan Singkat



Scope kerja halaman ini sudah ditutup untuk domain berikut:

- employee

- employee detail

- payroll manual

- debt summary integration ke detail employee

- reversal-aware reporting yang terkait



Status akhir verifikasi:

- regression final hijau

- 86 tests passed

- 446 assertions



Fokus berikutnya sebaiknya bukan kembali ke employee/payroll dasar, tetapi pindah ke hutang teknis domain lain atau polish minor berdasarkan audit live.



---



## Keputusan Domain yang Sudah Dikunci



### Employee

- detail employee harus version-aware

- identitas awal tidak boleh asal ambil versi tertua

- prioritas baseline:

  1. `employee_created`

  2. `first_recorded_version`

  3. `unavailable`

- jika baseline resmi tidak ada, UI harus jujur menandai sumber sebagai versi tercatat pertama



### Payroll

- payroll saat ini adalah flow manual

- pencairan gaji dilakukan satu per satu

- tidak ada edit langsung payroll

- koreksi payroll dilakukan lewat:

  1. reversal

  2. catat ulang payroll yang benar

- batch payroll admin dipensiunkan dari flow aktif

- auto payroll jam 17:00 dianggap fitur masa depan, bukan kontrak saat ini



### Debt

- payment debt bersifat append-only

- reversal payment ada dan aktif

- read model/reporting tidak boleh menghitung payment yang sudah direversal



---



## Yang Sudah Selesai



## 1. Employee versioning diseragamkan

Sudah dibereskan:

- create employee menulis ke `employee_versions`

- update employee menulis revisi baru

- audit event + snapshot berjalan

- detail employee menampilkan:

  - identitas saat ini

  - identitas awal

  - timeline versi

  - actor

  - reason

  - snapshot per revisi



Hasil:

- detail employee sekarang setara pola produk dalam hal baseline + timeline versioning



---



## 2. Seeder employee dibetulkan

Masalah awal:

- data seed employee hanya masuk ke `employees`

- tidak menulis baseline versioning resmi

- akibatnya identitas awal tidak terbaca sebagai baseline resmi



Yang sudah dilakukan:

- `EmployeeFinanceBaselineSeeder` diperbarui agar juga menulis:

  - `employee_versions`

  - `audit_events`

  - `audit_event_snapshots`



Hasil:

- data seed employee sekarang punya baseline awal resmi

- detail employee tidak lagi terlihat seperti data tanpa histori awal



---



## 3. Detail employee diubah jadi lebih operasional

Sebelumnya:

- halaman terlalu ringkasan identitas + timeline panjang

- belum terasa seperti dashboard operasional



Yang sudah dilakukan:

- layout detail employee diubah jadi lebih dashboard-like

- area atas sekarang fokus ke:

  - identitas saat ini

  - ringkasan hutang

  - ringkasan gaji

- area bawah menampilkan:

  - riwayat gaji karyawan

  - riwayat versi karyawan



Hasil:

- admin bisa baca kondisi cepat dari satu halaman

- versi tetap ada, tetapi tidak lagi mendominasi UX



---



## 4. Ringkasan hutang minor di detail employee

Sudah ditambahkan:

- total record hutang

- jumlah hutang aktif

- jumlah hutang lunas

- total nominal hutang

- total sisa hutang



Tetap dipisah:

- detail penuh hutang tetap di halaman hutang



---



## 5. Ringkasan gaji minor di detail employee

Sudah ditambahkan:

- total payroll aktif

- total nominal cair aktif

- tanggal pencairan terakhir



Tetap dipisah:

- create payroll tetap di halaman payroll

- reversal tetap di riwayat payroll detail employee



---



## 6. Riwayat payroll employee dipasang di detail employee

Sudah aktif:

- riwayat gaji karyawan tampil di detail employee

- status aktif / direversal tampil

- alasan reversal tampil

- reversal bisa dilakukan dari riwayat payroll employee

- reverse membutuhkan alasan eksplisit



Hasil:

- koreksi payroll sekarang punya jalur operasional yang jelas

- tidak perlu edit langsung



---



## 7. Payroll admin diubah jadi single manual

Sebelumnya:

- create payroll UI batch-first

- backend masih punya flow single

- kontrak payroll membingungkan



Yang sudah dilakukan:

- create payroll diubah jadi single manual

- route/admin flow batch dipensiunkan dari flow aktif

- store payroll admin sekarang single

- batch-related admin artifacts dibersihkan dari flow aktif



Hasil:

- create payroll sekarang satu suara:

  - manual

  - single

  - no direct edit

  - reverse + recatat



---



## 8. UX create payroll diperbaiki

Target UX yang diminta:

- saat buka halaman create, cursor langsung di field awal

- ketik 2 huruf, kandidat karyawan muncul

- Enter pilih karyawan

- Enter lanjut antar field sampai submit



Yang sudah dilakukan:

- create payroll sekarang punya employee search input

- hasil kandidat muncul saat minimal 2 huruf

- bisa pilih dengan Enter

- fokus lanjut ke nominal

- Enter lanjut ke tanggal

- Enter lanjut ke mode

- Enter lanjut ke catatan

- Enter di catatan submit



Catatan:

- ini sudah lolos test dan sudah dikonfirmasi aman di audit live



---



## 9. Debt payment reversal + read model/reporting hygiene

Sudah dibereskan:

- reversal payment hutang

- double reversal ditolak

- debt detail query mengecualikan payment yang direversal

- payment list by employee mengecualikan payment yang direversal

- reporting source mengecualikan payment yang direversal



---



## 10. Payroll reversal + reporting hygiene

Sudah dibereskan:

- reversal payroll aman

- payroll global table reversal-aware

- employee payroll summary reversal-aware

- employee payroll history reversal-aware

- reporting laba operasional mengecualikan payroll yang direversal



---



## File / Area Penting yang Sudah Berubah



## Employee

- `app/Adapters/Out/EmployeeFinance/DatabaseEmployeeDetailPageQuery.php`

- `app/Adapters/In/Http/Controllers/Admin/Employee/EmployeeDetailPageController.php`

- `resources/views/admin/employees/show.blade.php`

- `tests/Feature/EmployeeFinance/EmployeeDetailPageFeatureTest.php`

- `tests/Feature/EmployeeFinance/EmployeeDetailVersionTimelineFeatureTest.php`



## Seeder

- `database/seeders/EmployeeFinanceBaselineSeeder.php`



## Payroll

- `routes/web/admin_payrolls.php`

- `resources/views/admin/payrolls/create.blade.php`

- `public/assets/static/js/pages/admin-payroll-create.js`

- `public/assets/static/js/pages/admin-employee-payroll-table.js`

- `tests/Feature/EmployeeFinance/CreatePayrollPageFeatureTest.php`

- `tests/Feature/EmployeeFinance/StorePayrollFeatureTest.php`

- `tests/Feature/EmployeeFinance/ReversePayrollDisbursementFeatureTest.php`



## Debt / Reporting

- area debt payment reversal

- area debt read model

- area reporting debt summary

- area operational profit summary



---



## Artefak yang Sudah Dipensiunkan / Tidak Lagi Jadi Flow Aktif



Untuk admin payroll flow:

- batch payroll admin bukan flow aktif lagi

- direct edit payroll tidak dibuat

- batch payroll dianggap bukan kontrak operasional saat ini



Catatan:

- bila nanti dibutuhkan auto payroll jam 17:00, itu diperlakukan sebagai fitur baru dan bisa dibangun ulang dengan kontrak yang jelas, bukan menghidupkan kembali batch admin lama secara diam-diam



---



## Yang Belum Selesai



Ini adalah sisa yang memang belum dikerjakan atau sengaja di luar scope:



## 1. Auto payroll jam 17:00

Belum dikerjakan.

Ini future scope.

Butuh desain baru:

- scheduler

- source of truth eligibility

- audit

- failure handling

- idempotency



## 2. Dedicated payroll detail page

Belum ada halaman detail payroll khusus.

Saat ini payroll cukup ditangani oleh:

- create payroll

- payroll index/table

- employee detail payroll history

- reversal flow dari employee detail



Ini bukan blocker saat ini.



## 3. Polish minor UI employee detail

Masih mungkin ada minor polish:

- spacing

- wording label

- komposisi visual card

- ringkasan tambahan kecil



Ini bukan debt struktural lagi.

Ini polishing berdasarkan audit live.



## 4. Hutang teknis domain lain

Belum disentuh di halaman ini:

- technical debt domain lain di luar employee/payroll/debt-summary integration

- jika lanjut, kandidat paling masuk akal adalah audit hutang end-to-end atau domain lain yang diprioritaskan dari audit live



---



## Known Behavior Saat Ini



## Create Payroll

- buka halaman create payroll

- focus langsung ke search karyawan

- ketik minimal 2 huruf

- hasil kandidat muncul

- Enter pilih kandidat

- Enter lanjut antar field

- Enter di notes submit



## Reversal Payroll

- reversal payroll ada di:

  - detail employee

  - blok riwayat gaji karyawan

- alasan reversal wajib diisi

- payroll yang direversal tetap terlihat di riwayat, tapi statusnya berubah



## Employee Detail

- halaman employee sekarang punya:

  - current identity

  - initial identity

  - debt summary

  - payroll summary

  - payroll history

  - version history



---



## Rekomendasi Saat Lanjut Kerja



Kalau setelah audit live masih ada temuan, urutan aman:

1. catat temuan live yang benar-benar mengganggu operasional

2. klasifikasikan:

   - bug

   - mismatch domain

   - polish minor

3. jika bukan employee/payroll lagi, lanjut ke hutang teknis domain berikutnya



Kalau tidak ada blocker baru dari audit live:

- page ini dianggap closed

- lanjut ke hutang teknis berikutnya sesuai prioritas repo



---



## Status Final



### Closed

- employee create/edit/detail/versioning

- employee baseline seeder

- employee detail dashboard-like summary

- payroll manual single-first

- payroll reversal flow

- payroll history integration in employee detail

- debt summary integration in employee detail

- reversal-aware reporting terkait



### Not in current scope

- auto payroll scheduler

- dedicated payroll detail page

- polish minor UI yang tidak memblokir operasional

- technical debt domain lain



---



## Bukti Verifikasi Terakhir



Verifikasi final terakhir:

- 86 tests passed

- 446 assertions

- area EmployeeFinance + Reporting hijau



Tambahan verifikasi UX create payroll:

- test create/store/reversal/detail related hijau

- audit live sudah dikonfirmasi aman untuk flow keyboard-first payroll create



