# Handoff V1 - Employee, Salary, Debt

## Status
Diskusi blueprint domain selesai untuk scope:
- master karyawan
- nota gaji per karyawan
- hutang karyawan
- pembayaran hutang manual
- auto potong hutang dari gaji
- history / versioning / audit

Status akhir pada handoff ini:
- blueprint domain: terkunci
- policy auto potong hutang: terkunci
- prinsip editable vs akurasi laporan: terkunci
- scope implementasi bertahap: terkunci
- implementasi code: belum dikerjakan pada handoff ini

## Ringkasan satu kalimat
Paket ini memakai model **Employee = master**, **EmployeeSalaryNote = nota transaksi gaji per karyawan**, **EmployeeDebt = hutang per kejadian**, dan **EmployeeDebtPayment = ledger pembayaran hutang**, dengan aturan bahwa UI boleh terasa editable, tetapi histori, data, dan laporan harus tetap akurat lewat snapshot, versioning, audit trail, dan correction path untuk data yang sudah punya efek.

## Tujuan yang ditutup
Menutup diskusi logic dan blueprint agar halaman implementasi berikutnya bisa langsung jalan tanpa membuka ulang domain inti.

Blueprint ini harus menjaga 4 hal sekaligus:
1. UI sederhana untuk user lapangan.
2. Realita operasional yang fleksibel.
3. Data dan laporan tetap akurat.
4. Kode tetap kecil, auditable, dan testable.

## Scope in
- employee master
- employee status active / nonactive
- basis gaji employee
- default nominal gaji employee bila ada
- salary note per employee
- draft / paid / canceled salary note
- salary snapshot
- employee debt per kejadian pinjam
- debt mode manual / auto deduct
- debt payment ledger
- auto deduct dari salary note
- audit log
- versioning / history edit
- implementasi bertahap berbasis slice

## Scope out
- absensi
- lembur
- pajak
- BPJS
- komponen HR formal lain
- payroll batch massal
- approval bertingkat
- kaitan ke nota kerja mekanis
- transfer massal bank
- scheduling gaji otomatis
- correction / reversal detail implementation

## Fakta kerja yang terkunci

### 1. Basis gaji
Employee harus mendukung basis gaji:
- `monthly`
- `weekly`
- `daily`
- `manual`

Makna operasional:
- `monthly`, `weekly`, `daily` dipakai bila pola dasar gaji cenderung stabil.
- `manual` dipakai bila nominal suka berubah, kadang naik turun, atau nominal akhir sering ditentukan langsung saat pembayaran.

### 2. Pembayaran gaji
Operasional nyata adalah pembayaran gaji per karyawan, satu-satu.

Maka objek transaksi utama bukan payroll batch besar, tetapi:
- **salary note per employee**

### 3. Hutang
Hutang employee harus mendukung 2 mode:
- `manual`
- `auto_deduct`

Makna operasional:
- `manual`: user bebas catat kapan pinjam dan kapan bayar, sesuai realita lapangan.
- `auto_deduct`: sistem ikut memotong otomatis dari salary note saat salary note dibayar.

### 4. Auto deduct policy
Untuk hutang auto deduct, rule resmi yang terkunci:
- `fixed_amount`
- `percentage_of_original_debt`

Untuk mode persen, user sudah memilih:
- persen dihitung dari **nominal hutang awal**
- bukan dari sisa hutang berjalan

### 5. Nominal cicilan terakhir
Jika hasil rumus lebih besar dari sisa hutang, maka nominal potongan terakhir harus sama dengan nominal pelunasan.

Rumus final:

~~~text
actual_deduction = min(calculated_deduction, outstanding_amount)
~~~

### 6. Status minimum salary note
Status minimum yang dikunci untuk salary note:
- `draft`
- `paid`
- `canceled`

Tidak ada status approval tambahan di UI publik.

### 7. Versioning / history
Versioning memang diperlukan, minimal untuk:
- employee
- salary note
- employee debt

### 8. Audit dan test wajib
Semua mutation harus punya:
- automated tests
- audit log

### 9. Batas file
Target file code maksimal sekitar 100 line.
Jika lebih, file harus dipecah.

## Prinsip inti yang dikunci

### Prinsip 1. Editable bebas di UI, tetapi histori tidak boleh bohong
Konsep user adalah:
- terasa editable
- tidak terasa ribet
- operasional bisa fleksibel

Konsep sistem yang harus menjaganya adalah:
- data yang belum punya efek boleh diedit langsung
- data yang sudah punya efek tidak boleh dioverwrite secara diam-diam
- histori transaksi lama harus tetap akurat
- laporan tidak boleh berubah karena master terbaru diubah

Jadi, kalimat **editable bebas** hanya benar bila dibaca seperti ini:
- bebas di level operasional user
- tetapi tetap dijaga oleh snapshot, history, audit, dan lock rule

### Prinsip 2. Pre-effect boleh edit biasa
Untuk data yang belum memberi efek ke laporan / ledger, edit biasa diperbolehkan.

Contoh:
- salary note `draft` boleh diedit
- debt yang belum punya payment record boleh diedit

### Prinsip 3. Post-effect tidak boleh edit overwrite
Untuk data yang sudah memberi efek nyata:
- tidak boleh diedit overwrite secara bebas
- perubahan sesudah itu harus lewat jalur revisi yang menjaga histori

Pada fase awal, ini dikunci sebagai policy domain.
Detail correction / reversal boleh jadi slice lanjutan.

### Prinsip 4. Snapshot wajib untuk transaksi
Transaksi salary note harus menyimpan snapshot minimum supaya laporan lama tidak ikut berubah ketika master employee berubah.

### Prinsip 5. Hutang harus ledger-based
Outstanding hutang tidak boleh menjadi angka bebas edit.
Outstanding harus diturunkan dari:
- nominal hutang awal
- total payment records

## Domain model yang dikunci

## A. Employee

### Peran
Master data karyawan.

### Tanggung jawab minimum
- simpan identitas karyawan
- simpan status aktif / nonaktif
- simpan basis gaji default
- simpan nominal default bila ada
- jadi sumber autofill untuk create salary note

### Field minimum konseptual
- `id`
- `employee_code`
- `employee_name`
- `employment_status`
- `salary_basis_type`
- `default_salary_amount` nullable
- `started_at` nullable
- `ended_at` nullable
- timestamps

### Rule domain
- nonactive tidak menghapus histori
- perubahan employee hanya memengaruhi transaksi berikutnya
- transaksi lama tetap pakai snapshot sendiri
- mutation employee harus masuk history / audit

## B. EmployeeSalaryNote

### Peran
Dokumen transaksi gaji per employee.

### Kenapa bukan payroll batch
Karena pola operasional nyata adalah bayar satu-satu.
Maka salary note per employee lebih sederhana, jujur, dan lebih cocok untuk kebutuhan client.

### Field minimum konseptual
- `id`
- `employee_id`
- `status`
- `note_date`
- `salary_basis_snapshot`
- `default_salary_amount_snapshot` nullable
- `manual_salary_amount` nullable
- `gross_salary_amount`
- `debt_deduction_amount`
- `net_salary_amount`
- `payment_method_note` nullable
- `notes` nullable
- timestamps

### Snapshot minimum
Saat note dibentuk, minimal snapshot yang harus tersimpan:
- nama employee saat itu
- basis gaji saat itu
- nominal default saat itu
- nominal salary yang dipakai saat itu
- total potongan hutang saat itu
- total net salary saat itu

### Rule domain
- create note boleh autofill dari employee
- draft boleh diedit
- paid tidak boleh edit overwrite biasa
- canceled hanya untuk note yang belum memberi efek pembayaran final
- hutang auto deduct hanya preview saat draft
- hutang baru benar-benar terpotong saat note berubah menjadi `paid`

## C. EmployeeDebt

### Peran
Mencatat satu kejadian hutang / pinjaman employee.

### Field minimum konseptual
- `id`
- `employee_id`
- `debt_mode`
- `original_amount`
- `outstanding_amount`
- `auto_deduct_type` nullable
- `auto_deduct_value` nullable
- `started_at`
- `notes` nullable
- timestamps

### Mode resmi
- `manual`
- `auto_deduct`

### Jika auto deduct
- `auto_deduct_type`:
  - `fixed_amount`
  - `percentage_of_original_debt`
- `auto_deduct_value`

### Rule domain
- satu pinjam = satu debt record
- edit biasa hanya boleh sebelum ada payment record
- setelah ada payment record, tidak boleh edit overwrite diam-diam
- state bisa dibaca dari outstanding:
  - `open` bila outstanding > 0
  - `settled` bila outstanding = 0

## D. EmployeeDebtPayment

### Peran
Ledger pembayaran hutang.

### Sumber pembayaran
- `manual_payment`
- `salary_deduction`

### Field minimum konseptual
- `id`
- `employee_debt_id`
- `employee_id`
- `source_type`
- `amount`
- `paid_at`
- `salary_note_id` nullable
- `notes` nullable
- timestamps

### Rule domain
- semua pengurangan hutang harus lewat record ini
- tidak boleh langsung ubah outstanding tanpa ledger entry
- salary deduction baru tercatat saat salary note `paid`

## Policy detail yang terkunci

### Policy 1. Salary amount source
Saat create salary note:
- jika employee punya default salary amount, form boleh autofill
- user tetap boleh override saat draft
- mode `manual` tetap didukung penuh

### Policy 2. Auto deduction calculation
Untuk setiap debt auto deduct yang masih open:

Jika type = `fixed_amount`:
~~~text
calculated_deduction = fixed_amount
~~~

Jika type = `percentage_of_original_debt`:
~~~text
calculated_deduction = percentage x original_debt_amount
~~~

Lalu:
~~~text
actual_deduction = min(calculated_deduction, outstanding_amount)
~~~

### Policy 3. Draft does not change debt
Salary note `draft` hanya preview.
Belum ada efek ke:
- debt outstanding
- debt payment ledger
- laporan pembayaran final

### Policy 4. Paid applies effects
Saat salary note menjadi `paid`, sistem wajib:
1. hitung auto deduction yang sah
2. buat `EmployeeDebtPayment` untuk deduction yang dipakai
3. kurangi outstanding debt sesuai ledger
4. simpan snapshot salary note final
5. tulis audit event

### Policy 5. Reports stay accurate
Laporan harus membaca:
- snapshot transaksi untuk transaksi masa lalu
- ledger hutang untuk outstanding dan histori pembayaran
- bukan master live semata

### Policy 6. Editable with accuracy
Interpretasi final dari konsep user:
- bebas edit untuk data yang belum punya efek
- bebas revisi secara operasional melalui jalur resmi
- tetapi histori lama dan laporan tetap dijaga akurat

## Lock rule yang dikunci

### Employee
- boleh edit
- perubahan tidak mengubah transaksi lama

### EmployeeSalaryNote
- `draft`: editable
- `paid`: locked untuk edit overwrite biasa
- `canceled`: tidak aktif untuk payment effect baru

### EmployeeDebt
- belum ada payment record: editable
- sudah ada payment record: locked untuk edit overwrite biasa

## Keputusan arsitektur yang dikunci

### 1. Pattern domain
Paket ini mengikuti pattern yang sudah terbukti di repo:
- master data terpisah
- transaksi terpisah
- history / versioning terpisah
- audit mutation wajib
- editability guard untuk pre-effect vs post-effect

### 2. Tidak memakai payroll run besar
Tidak dibuat payroll batch besar karena:
- tidak cocok dengan operasional nyata user
- UI akan jadi lebih ribet
- scope akan melebar tanpa bukti kebutuhan client

### 3. Correction / reversal tidak dibangun di slice pertama
Namun policy correction / reversal harus sudah dihormati dari awal.
Artinya struktur data awal tidak boleh mengunci sistem ke edit overwrite.

## Kebutuhan implementasi wajib

### 1. Tests wajib
Minimal coverage yang harus ada saat implementasi:
- employee create
- employee update
- employee deactivate / reactivate bila dipakai
- employee history read
- debt create manual
- debt create auto fixed amount
- debt create auto percentage original debt
- debt manual payment record
- auto deduction preview on draft salary note
- auto deduction apply on paid salary note
- last deduction clamps to outstanding
- salary note draft update
- salary note paid becomes locked
- debt becomes locked after first payment record
- salary snapshot remains stable after employee update
- audit events for all mutations

### 2. Audit events minimum
- `employee_created`
- `employee_updated`
- `employee_deactivated`
- `employee_reactivated`
- `employee_salary_note_created`
- `employee_salary_note_updated`
- `employee_salary_note_paid`
- `employee_salary_note_canceled`
- `employee_debt_created`
- `employee_debt_updated`
- `employee_debt_manual_payment_recorded`
- `employee_debt_auto_deduction_applied`

### 3. File size discipline
Implementasi harus dipecah kecil.
Contoh file kecil yang direkomendasikan:
- salary note editability guard
- debt editability guard
- fixed deduction calculator
- percentage deduction calculator
- deduction clamp helper
- salary snapshot builder
- apply debt deduction handler
- debt outstanding projector

## Rekomendasi urutan slice implementasi

### Slice 1 - Employee master
Target:
- create employee
- edit employee
- index employee
- show employee
- active / nonactive employee
- history/versioning employee

Deliverable minimum:
- routes admin employee
- request + DTO + use case + ports + adapters
- index/create/edit/show blade
- tests + audit

### Slice 2 - Employee debt
Target:
- create debt
- show debt
- list debt
- manual payment
- auto deduct policy
- outstanding ledger
- editability guard debt

Deliverable minimum:
- debt create flow
- debt payment flow manual
- read side outstanding
- tests + audit

### Slice 3 - Employee salary note
Target:
- create salary note draft
- edit salary note draft
- pay salary note
- cancel salary note draft
- salary snapshot
- auto debt deduction apply on paid
- read history salary note

Deliverable minimum:
- note create/edit/pay/cancel
- draft preview deduction
- paid applies debt ledger
- tests + audit

### Slice 4 - Correction / reversal
Target:
- safe post-effect correction path
- salary note correction after paid
- debt correction after payment exists

Deliverable minimum:
- official revision flow
- no overwrite on post-effect data
- tests + audit

## Halaman UI yang disarankan

### Employee
- `/admin/employees`
- `/admin/employees/create`
- `/admin/employees/{employeeId}`
- `/admin/employees/{employeeId}/edit`

### Employee debt
- `/admin/employee-debts`
- `/admin/employee-debts/create`
- `/admin/employee-debts/{debtId}`
- `/admin/employee-debts/{debtId}/edit` hanya pre-payment
- `/admin/employee-debts/{debtId}/payments/create`

### Employee salary note
- `/admin/employee-salaries`
- `/admin/employee-salaries/create`
- `/admin/employee-salaries/{salaryNoteId}`
- `/admin/employee-salaries/{salaryNoteId}/edit` hanya draft

## Kontrak implementasi untuk halaman berikutnya
Pada halaman implementasi berikutnya, output implementasi harus mengikuti aturan ini:

1. Beri hasil dalam bentuk **command terminal copy-paste**.
2. Untuk setiap file yang dibuat / diubah, tampilkan:
   - path file yang tepat
   - isi file penuh
3. Jangan pakai inline PHP liar di Blade.
4. Mutation wajib lewat controller -> use case.
5. History dan audit jangan dilewati.
6. File dipecah kecil, target <= 100 line.
7. Sertakan test command yang relevan.
8. Sertakan verify command repo yang relevan.

## Bukti arah ini konsisten
Arah blueprint ini konsisten dengan pattern repo yang sudah ada:
- product memakai master + history + versioning
- procurement memakai pre-effect editability guard
- expense memakai official table + audited lifecycle + snapshot-safe history

## Risiko yang sengaja ditunda
Belum dikunci final pada handoff ini:
- apakah satu salary note boleh memotong beberapa debt sekaligus
- urutan prioritas bila employee punya lebih dari satu debt auto aktif
- detail correction / reversal implementation
- istilah UI final apakah tetap memakai kata “salary note” atau diterjemahkan ke istilah bisnis lokal

Keputusan penundaan ini disengaja agar implementasi awal tidak melebar.

## Safest next step
Urutan teraman setelah handoff ini:
1. implement Slice 1 - Employee master
2. lanjut Slice 2 - Employee debt
3. lanjut Slice 3 - Employee salary note
4. terakhir Slice 4 - Correction / reversal

## Progress akhir diskusi
- blueprint domain employee/salary/debt: **100% selesai untuk level diskusi logic dan handoff**
- implementasi code: **0% pada handoff ini**
