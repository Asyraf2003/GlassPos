# Handoff V2 UI - Projection Index And Test Alignment

## Ringkasan

Halaman index yang sebelumnya lambat sudah dipindahkan ke read model projection untuk dua area utama:

1. procurement supplier invoice index
2. admin note history index

Selain itu, jalur mutation utama sudah menulis projection, command rebuild projection sudah tersedia, dan backfill runtime sudah berhasil untuk data existing.

Status terakhir:
- projection runtime sudah hidup
- count projection sudah cocok dengan source
- `make verify` sempat gagal karena audit line limit, lalu file-file besar sudah dipecah sampai blocker line-limit habis
- sisa blocker terakhir ada di test assertion copy UI, bukan di projection

---

## Scope Yang Sudah Selesai

### 1. Read model projection baru
Tabel projection yang dibuat:
- `supplier_invoice_list_projection`
- `note_history_projection`

Komponen yang sudah dibuat:
- port source reader projection
- port writer projection
- service projection
- adapter source reader projection
- adapter writer projection
- binding di `HexagonalServiceProvider`

### 2. Mutation path sudah sync projection
Procurement:
- create supplier invoice flow
- update supplier invoice
- receive supplier invoice
- record supplier payment
- attach supplier payment proof
- void supplier invoice
- reverse supplier receipt
- reverse supplier payment

Note:
- create note
- create note rows
- create transaction workspace
- update transaction workspace
- record and allocate note payment
- record customer refund
- reopen closed note
- correct paid work item status
- correct paid service only work item

### 3. Read path index sudah pindah ke projection
Sudah diganti:
- procurement invoice table reader
- admin note history table query

### 4. Rebuild command projection sudah tersedia
Command:
- `php artisan projection:rebuild-indexes procurement --chunk=200`
- `php artisan projection:rebuild-indexes note --chunk=200`
- `php artisan projection:rebuild-indexes all --chunk=200`

### 5. Backfill runtime berhasil
Sudah terbukti berjalan:
- procurement projection rebuild sukses
- note projection rebuild sukses

### 6. Count source vs projection cocok
Hasil verifikasi:
- `supplier_invoices=29`
- `supplier_invoice_list_projection=29`
- `notes=56`
- `note_history_projection=56`

---

## Perubahan Penting Di Test Layer

Karena reader index sekarang membaca projection, banyak test lama yang seed langsung ke source table menjadi kosong jika projection tidak disync.

Perbaikan yang sudah dilakukan:
- `tests/TestCase.php`
  - tambah helper:
    - `syncSupplierInvoiceProjectionForTest(string $supplierInvoiceId): void`
    - `syncNoteProjectionForTest(string $noteId): void`

- `tests/Support/SeedsMinimalProcurementFixture.php`
  - seed invoice, line, payment, receipt, dan receipt line sekarang auto sync projection procurement

- `tests/Feature/Note/AdminNoteHistoryTableDataFeatureTest.php`
  - seed lokal note sekarang auto sync projection note

- test procurement manual insert yang tidak memakai trait shared juga sudah diarahkan untuk sync projection di titik yang diperlukan

---

## File Besar Yang Sudah Dipecah Untuk Lolos Audit Line Limit

Split yang sudah dilakukan di area projection/read model/use case:
- helper subquery procurement payment projection
- helper subquery procurement receipt projection
- helper filter dan mapper admin note history projection
- helper filter dan sorting procurement invoice projection
- split beberapa handler besar ke service transaction / mutation / finalizer

Tujuan split:
- lolos `audit-lines`
- tetap menjaga behavior
- tidak menambah bypass label

---

## Status Verifikasi Terakhir

Sudah terbukti:
- migration syntax oke
- provider syntax oke
- service dan adapter syntax oke
- rebuild command terdaftar
- rebuild runtime berhasil
- subset test projection procurement/note yang sebelumnya kosong sudah pulih
- total test sudah turun dari kegagalan belasan menjadi sisa 5 gagal

Sisa 5 gagal terakhir bukan projection, tetapi mismatch assertion copy UI:
- `tests/Feature/EmployeeFinance/EmployeeDebtIndexPageFeatureTest.php`
- `tests/Feature/EmployeeFinance/PayrollIndexPageFeatureTest.php`
- `tests/Feature/Expense/ExpenseCategoryIndexPageFeatureTest.php`
- `tests/Feature/Note/AdminNoteDetailPageFeatureTest.php`

Arah keputusan yang sudah dikunci:
- **UI yang sekarang dianggap benar**
- **test yang harus disesuaikan**
- jangan ubah Blade/UI hanya untuk memuaskan copy lama di test

---

## Sisa Pekerjaan Aktif

### A. Sesuaikan 4 file test copy UI
Yang perlu dilakukan:
- update assertion text di:
  - `EmployeeDebtIndexPageFeatureTest`
  - `PayrollIndexPageFeatureTest`
  - `ExpenseCategoryIndexPageFeatureTest`
  - `AdminNoteDetailPageFeatureTest`

Arah expectation:
- employee debt: pakai headline yang sekarang benar-benar ada di Blade
- payroll: sesuaikan string tanpa titik jika view memang tanpa titik
- expense category: pakai copy heading/card title yang sekarang
- note detail: hapus assertion `Detail Nota Admin` bila UI final memang tidak lagi memakai label itu, lalu pertahankan assertion section yang stabil:
  - `Header Nota`
  - `Status Operasional Admin`
  - `Alasan Reopen`
  - `Reopen tidak diperlukan`
  - `Buka Ulang Nota`

### B. Ulang verifikasi akhir
Setelah test copy diperbarui:
1. jalankan 4 file test tersebut
2. jalankan `make verify`

---

## Command Penting

### Rebuild projection
~~~bash
php artisan projection:rebuild-indexes procurement --chunk=200
php artisan projection:rebuild-indexes note --chunk=200
~~~

### Cek count projection
~~~bash
php artisan tinker --execute="
echo 'supplier_invoices=' . DB::table('supplier_invoices')->count() . PHP_EOL;
echo 'supplier_invoice_list_projection=' . DB::table('supplier_invoice_list_projection')->count() . PHP_EOL;
echo 'notes=' . DB::table('notes')->count() . PHP_EOL;
echo 'note_history_projection=' . DB::table('note_history_projection')->count() . PHP_EOL;
"
~~~

### Verifikasi akhir
~~~bash
make verify
~~~

---

## Risiko / Catatan

1. Jangan tambahkan fallback reader dari projection ke source table.
   Itu akan merusak tujuan optimasi index dan membuat behavior runtime bercabang.

2. Jika nanti ada test baru yang seed langsung ke:
   - `supplier_invoices`
   - `supplier_invoice_lines`
   - `supplier_payments`
   - `supplier_receipts`
   - `supplier_receipt_lines`
   - `notes`
   - `work_items`
   - `payment_allocations`
   - `customer_refunds`

   maka projection test helper harus ikut dipanggil.

3. Hasil runtime sekarang menunjukkan fondasi projection benar. Jadi sisa pekerjaan adalah alignment test, bukan redesign domain.

---

## Safest Next Step

1. update 4 file test copy UI
2. jalankan 4 file test tersebut
3. jalankan `make verify`
4. jika hijau, lanjut ukur latency endpoint index via terminal

---

## Status Akhir Saat Handoff

- projection implementation: selesai
- projection runtime backfill: selesai
- source vs projection count: cocok
- read path index ke projection: selesai
- test adaptation untuk projection seed: mayoritas selesai
- sisa blocker: 4 file test copy UI
- progress kerja efektif: 99 persen, tertahan di alignment assertion test
