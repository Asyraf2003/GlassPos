# ADR-0013 — Employee Finance Foundation

## Status
Accepted

## Context
Workflow Step 10 mengharuskan domain SDM aktif dengan cakupan:

- employee
- payroll manual
- payroll mode harian/mingguan/bulanan
- employee debt
- debt payment

Output wajib Step 10:

- gaji manual dengan tanggal dan nominal valid
- hutang karyawan dan pembayaran hutang tercatat

Audit implementasi menunjukkan bounded context Employee Finance sudah masuk ke repo, namun artefak awal dari AI sebelumnya tidak sinkron penuh dengan workflow resmi. Karena itu kontrak domain dikunci ulang lewat ADR ini.

## Decision

### 1. Employee adalah aggregate root untuk master karyawan
Field inti yang dikunci:
- `id`
- `name`
- `phone`
- `baseSalary`
- `payPeriod`
- `status`

Aturan:
- nama tidak boleh kosong
- base salary wajib lebih dari nol
- employee baru diregistrasikan dengan status aktif

### 2. Pay period yang sah untuk Employee hanya:
- `daily`
- `weekly`
- `monthly`

`manual` bukan pay period.

### 3. Payroll disbursement dicatat manual oleh admin
Payroll pada Step 10 bersifat manual, artinya:
- nominal dimasukkan eksplisit oleh admin
- tanggal pencairan dimasukkan eksplisit oleh admin
- pencairan tidak dihitung otomatis dari sistem absensi atau cron

Manual adalah cara pencatatan, bukan enum mode periodisasi.

### 4. Payroll disbursement memiliki mode:
- `daily`
- `weekly`
- `monthly`

Aturan:
- nominal payroll wajib lebih dari nol
- tanggal pencairan wajib valid
- employee harus ada sebelum payroll dicatat

### 5. Employee debt dipisah dari payroll disbursement
Debt ledger dan payroll ledger adalah aggregate yang berbeda agar histori finansial tetap bersih.

### 6. Debt payment adalah child entity di dalam EmployeeDebt
Aturan:
- nominal pembayaran wajib lebih dari nol
- pembayaran tidak boleh melebihi sisa hutang
- hutang yang sudah lunas tidak boleh menerima pembayaran lagi

### 7. Penurunan gaji pokok wajib alasan
Jika base salary baru lebih kecil daripada base salary lama, alasan wajib diisi.

### 8. Semua nominal uang disimpan sebagai integer rupiah
Seluruh nilai uang di Employee Finance memakai `Money` berbasis integer untuk menghindari drift pecahan.

### 9. Step 10 tidak mencakup auto payroll dan read model reporting
Yang tidak termasuk ADR ini:
- auto payroll
- timesheet / absensi
- reporting read model employee finance

## Consequences

### Positif
- Domain Employee Finance tetap selaras dengan workflow resmi Step 10.
- Payroll, debt, dan debt payment memiliki kontrak dasar yang cukup ketat.
- Arsitektur tetap konsisten dengan pola hexagonal repo.
- Step 10 sudah memiliki proof test eksplisit untuk register employee, update salary, debt record, debt payment, dan payroll disbursement.

### Negatif
- Step berikutnya tidak boleh mengubah kembali kontrak payroll/pay period/base salary tanpa dasar fakta baru.
- Karena payroll pada Step 10 tetap manual, otomasi absensi atau auto payroll harus dibuka sebagai slice baru, bukan diselundupkan ke kontrak ini.

### Netral
- Bounded context Employee Finance tidak perlu dibangun ulang.
- Perbaikan cukup dilakukan sebagai patch terarah pada kontrak domain, request, test, seeder, dan dokumen closure.
