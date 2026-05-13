# Handoff — Step 11 Operational Expense

## Metadata
- Tanggal: 2026-03-17
- Nama slice / topik: Step 11 — Operational Expense
- Workflow step: Step 11
- Status: CLOSED
- Progres:
  - Step 11: 100%
  - Workflow induk: 73%

---

## Target halaman kerja
Menutup Step 11 agar fondasi backend biaya operasional resmi hidup dan siap dipakai sebagai sumber domain final untuk reporting pada Step 12.

Target spesifik yang dikerjakan pada slice ini:
- expense category hidup
- operational expense entry hidup
- validasi domain minimum hidup
- persistensi database hidup
- binding hexagonal hidup
- feature test expense hidup
- reporting contract diperkuat agar tetap mengakui expense entries sebagai source domain final

Target yang tidak dikerjakan pada slice ini:
- recurring template opsional
- controller / request / route / UI
- read model reporting nyata
- audit khusus mutation expense

---

## Referensi yang dipakai `[REF]`
- Blueprint:
  - blueprint project kasir bengkel yang sudah dikunci di percakapan sebelumnya: biaya operasional adalah domain final yang harus bisa ikut mempengaruhi laporan
- Workflow:
  - `docs/workflow/workflow_v1.md`
- DoD:
  - `docs/dod/dod_v1.md`
- ADR:
  - `docs/adr/0009-reporting-as-read-model.md`
  - `docs/adr/0011-money-stored-as-integer-rupiah.md`
- Handoff sebelumnya:
  - `docs/handoff/handoff_step_9_correction_refund_audit.md`
  - `docs/handoff/handoff_step_8_final_audit.md`
- Snapshot repo / output command yang dipakai:
  - `find app -maxdepth 5 -type f | sort | grep -iE 'Expense|Report|Reporting|Ledger|ReadModel|Payroll|Payment|Audit|Provider'`
  - `find database/migrations -maxdepth 1 -type f | sort`
  - `find tests -maxdepth 5 -type f | sort | grep -iE 'Expense|Report|Reporting|Ledger|Projection|Payment|Audit'`
  - `sed -n '1,260p' app/Providers/HexagonalServiceProvider.php`
  - `sed -n '1,260p' tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`
  - `sed -n '1,260p' docs/adr/0009-reporting-as-read-model.md`
  - `sed -n '1,260p' app/Application/Payment/UseCases/RecordCustomerPaymentHandler.php`
  - `sed -n '1,260p' app/Application/EmployeeFinance/UseCases/DisbursePayrollHandler.php`
  - `sed -n '1,260p' app/Application/Shared/DTO/Result.php`
  - `sed -n '1,260p' tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php`
  - `sed -n '1,260p' tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`
  - `make verify`

---

## Fakta terkunci `[FACT]`
- Sebelum slice ini dikerjakan, repo belum memiliki domain `Expense`, belum ada migration `expense_*`, belum ada port/adapter expense, dan belum ada binding expense di `HexagonalServiceProvider`.
- Workflow repo memisahkan `Operational expense` di Step 11 dan `Reporting read models` di Step 12, sehingga Step 11 tidak boleh diam-diam mengerjakan reporting engine penuh.
- ADR-0009 sudah mengunci bahwa reporting adalah read model atas data domain final, dan secara eksplisit menyebut `expense entries` serta `laporan biaya operasional`.
- DoD domain expense yang aktif pada repo adalah:
  - kategori valid
  - nominal valid
  - tanggal valid
  - laporan ikut berubah benar
- Implementasi reporting nyata belum hidup; test reporting yang ada sebelum slice ini hanya mengunci boundary ADR/workflow, bukan perhitungan laporan.
- Setelah implementasi Step 11 minimal selesai, `make verify` terbukti lolos.
- Kegagalan gate yang sempat muncul bukan dari domain expense logic, melainkan:
  - assertion redundan di `PaymentAllocationPolicyTest`
  - audit line-count karena file domain expense awal terlalu panjang
- Kedua masalah gate tersebut sudah diperbaiki sampai `make verify` lolos.
- Money tetap disimpan sebagai integer rupiah, selaras dengan ADR-0011 dan pola repo yang sudah aktif.

---

## Scope yang dipakai

### `[SCOPE-IN]`
- domain `ExpenseCategory`
- domain `OperationalExpense`
- status `draft` / `posted` / `cancelled`
- migration category + expense entry
- port reader/writer untuk expense category
- port writer untuk operational expense
- database adapter untuk category + expense
- create expense category handler
- record operational expense handler
- provider binding untuk expense ports
- feature test category
- feature test expense entry
- penguatan reporting contract test agar tetap mengakui expense entries sebagai source domain final

### `[SCOPE-OUT]`
- recurring template opsional
- controller, request, route, presenter, halaman UI
- projection/read model laporan riil
- agregasi laba rugi / ledger / monthly report
- audit khusus mutation expense
- edit/cancel use case expense
- seed demo / seed test khusus expense
- make target khusus expense terpisah

---

## Keputusan yang dikunci `[DECISION]`
- Step 11 diimplementasikan sebagai domain baru `Expense`, tidak ditumpangkan ke `Payment` atau `EmployeeFinance`.
- Implementasi yang dipilih adalah backend minimal valid, bukan shortcut tabel datar langsung ke reporting.
- `ExpenseCategory` menjadi master resmi untuk pengelompokan biaya operasional.
- `OperationalExpense` menjadi record domain final untuk biaya keluar operasional.
- Status expense yang dihidupkan pada slice ini:
  - `draft`
  - `posted`
  - `cancelled`
- Handler `CreateExpenseCategoryHandler` memakai pola `Result`, mengikuti pola slice payment yang sudah lebih stabil di repo.
- Handler `RecordOperationalExpenseHandler` juga memakai pola `Result` dan memvalidasi:
  - kategori harus ada
  - kategori harus aktif
  - nominal > 0
  - tanggal format `Y-m-d`
  - deskripsi wajib ada
  - payment method wajib ada
  - status harus valid
- Integrasi reporting pada Step 11 dibatasi hanya pada kontrak boundary:
  - reporting ADR harus tetap menyebut expense entries
  - workflow tetap menempatkan reporting real di Step 12
- Tidak dibuat ADR baru karena ADR-0009 sudah cukup untuk boundary expense ke reporting.
- Tidak dibuat recurring template karena bersifat opsional pada Step 11 dan belum dibutuhkan untuk menutup DoD minimal.
- Tidak dibuat seeder pada slice ini karena belum ada bukti kebutuhan langsung untuk menutup Step 11.

---

## File yang dibuat/diubah `[FILES]`

### File baru
- `database/migrations/2026_03_17_000100_create_expense_categories_table.php`
- `database/migrations/2026_03_17_000200_create_operational_expenses_table.php`
- `app/Core/Expense/ExpenseCategory/ExpenseCategory.php`
- `app/Core/Expense/ExpenseCategory/ExpenseCategoryValidation.php`
- `app/Core/Expense/OperationalExpense/OperationalExpense.php`
- `app/Core/Expense/OperationalExpense/OperationalExpenseStatus.php`
- `app/Core/Expense/OperationalExpense/OperationalExpenseValidation.php`
- `app/Ports/Out/Expense/ExpenseCategoryReaderPort.php`
- `app/Ports/Out/Expense/ExpenseCategoryWriterPort.php`
- `app/Ports/Out/Expense/OperationalExpenseWriterPort.php`
- `app/Adapters/Out/Expense/DatabaseExpenseCategoryReaderAdapter.php`
- `app/Adapters/Out/Expense/DatabaseExpenseCategoryWriterAdapter.php`
- `app/Adapters/Out/Expense/DatabaseOperationalExpenseWriterAdapter.php`
- `app/Application/Expense/UseCases/CreateExpenseCategoryHandler.php`
- `app/Application/Expense/UseCases/RecordOperationalExpenseHandler.php`
- `tests/Feature/Expense/CreateExpenseCategoryFeatureTest.php`
- `tests/Feature/Expense/RecordOperationalExpenseFeatureTest.php`

### File diubah
- `app/Providers/HexagonalServiceProvider.php`
- `tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`
- `tests/Unit/Core/Payment/Policies/PaymentAllocationPolicyTest.php`

---

## Bukti verifikasi `[PROOF]`
- command:
  - `php artisan test tests/Unit/Core/Payment/Policies/PaymentAllocationPolicyTest.php`
  - hasil:
    - test unit payment allocation policy lolos setelah assertion redundan diperbaiki
- command:
  - `php artisan migrate`
  - hasil:
    - migration expense berhasil dijalankan dan schema baru aktif
- command:
  - `php artisan test tests/Feature/Expense/CreateExpenseCategoryFeatureTest.php`
  - hasil:
    - create expense category berhasil diverifikasi
- command:
  - `php artisan test tests/Feature/Expense/RecordOperationalExpenseFeatureTest.php`
  - hasil:
    - record operational expense berhasil diverifikasi
- command:
  - `php artisan test tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php`
  - hasil:
    - reporting contract tetap lolos dan mengunci expense sebagai bagian source domain final
- command:
  - `make verify`
  - hasil:
    - awalnya gagal di lint karena assertion redundan pada `PaymentAllocationPolicyTest`
    - setelah koreksi, lint lolos
    - lalu gagal di audit line count karena:
      - `app/Core/Expense/OperationalExpense/OperationalExpense.php`
      - `app/Core/Expense/ExpenseCategory/ExpenseCategory.php`
    - setelah validasi dipecah ke trait, `make verify` lolos penuh

---

## Blocker aktif
- Tidak ada blocker aktif untuk penutupan Step 11 minimal backend.
- Reporting engine nyata masih belum hidup, tetapi itu bukan blocker Step 11 karena memang scope Step 12.
- Recurring template masih belum dikerjakan, tetapi ini tidak memblokir Step 11 karena statusnya opsional.

---

## Gap yang sengaja ditunda
- recurring template expense
- endpoint HTTP / controller / request / UI management expense
- mutation audit untuk expense
- cancel/edit flow expense
- projection/read model laporan berbasis expense
- laporan laba rugi / monthly ledger / operational profit actual implementation
- dataset seed demo dan seed test domain expense

---

## Dampak ke langkah berikutnya
Step 11 sekarang sudah menyiapkan fondasi yang bisa dipakai Step 12:

- source domain final untuk biaya operasional sudah resmi ada
- category + expense entry sudah punya storage resmi
- validasi minimum sudah dikunci
- reporting contract sudah tetap mengakui expense entries sebagai source domain final

Artinya Step 12 tidak perlu lagi mendebat:
- apakah expense domain resmi atau bukan
- apakah expense harus dicatat terpisah dari payment/payroll
- apakah reporting boleh membaca expense entries

Semua itu sudah terkunci oleh hasil Step 11 ini.

---

## Rekomendasi pembuka Step 12
Saat membuka Step 12, fokus awal yang paling aman:

1. definisikan read model minimum yang benar-benar dibutuhkan
2. tentukan laporan pertama yang akan dihidupkan
3. pakai data domain final yang sudah ada:
   - notes / work items
   - customer payments / refunds / allocations
   - supplier payments
   - payroll disbursements
   - employee debts
   - operational expenses
4. kunci rule exactness agar mismatch 1 rupiah menjadi defect
5. jangan tambahkan rule bisnis baru di reporting layer

Urutan implementasi yang direkomendasikan untuk Step 12:
- kontrak read model pertama
- test laporan pertama
- projection/build logic pertama
- rekonsiliasi terhadap source domain final
- perluas ke view laporan berikutnya

---

## Ringkasan penutupan
Step 11 ditutup sebagai backend minimal valid untuk operational expense.

Yang sudah sah:
- expense category hidup
- operational expense hidup
- validasi minimal hidup
- persistence hidup
- binding hexagonal hidup
- feature test hidup
- verify gate lolos

Yang belum dikerjakan sengaja:
- recurring template
- UI
- reporting read model nyata

Status akhir:
- Step 11 selesai dan siap menjadi pijakan Step 12.
