# ADR-0011 — Money Stored as Integer Rupiah

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Note / Payment / Inventory / Supplier / Expense / EmployeeFinance / Reporting

## Context

Sistem ini memiliki aturan bisnis yang sangat ketat:

- toleransi selisih finansial adalah **0**
- selisih **1 rupiah** dianggap kegagalan sistem
- domain mencakup banyak area yang semuanya mengandung uang:
  - transaksi customer
  - pembayaran parsial
  - hutang supplier
  - biaya operasional
  - gaji
  - hutang karyawan
  - laporan bulanan
  - margin dan hasil bersih operasional

Dalam domain seperti ini, penggunaan representasi uang yang tidak presisi, seperti floating point, berisiko menghasilkan:

- pembulatan tersembunyi
- mismatch laporan
- perbedaan antar layer
- hasil perhitungan yang terlihat kecil tetapi secara bisnis fatal

Karena sistem ini memakai mata uang rupiah dan kebutuhan aktif saat ini tidak memerlukan pecahan desimal sub-rupiah, maka bentuk penyimpanan uang perlu dikunci secara eksplisit.

## Decision

Sistem menetapkan:

- **semua nilai uang disimpan dan diproses sebagai integer rupiah**
- **floating point tidak boleh dipakai untuk representasi nominal uang resmi**
- **seluruh perhitungan uang di domain, application, persistence, dan reporting harus mempertahankan model integer rupiah**
- **format presentasi ke UI boleh diberi pemisah ribuan, tetapi representasi internal tetap integer**

## Decision Details

### 1. Scope of monetary values

Keputusan ini berlaku untuk seluruh nominal resmi, minimal:

- unit price
- subtotal
- total note
- payment amount
- outstanding
- supplier invoice amount
- supplier payable/payment
- payroll amount
- employee debt/payment
- expense amount
- report totals
- margin/hasil bersih operasional
- inventory-related monetary values yang resmi

### 2. Why integer rupiah

Representasi integer rupiah dipilih karena:

- sesuai kebutuhan bisnis saat ini
- menghilangkan risiko pecahan semu dari floating point
- memudahkan rekonsiliasi 1 rupiah exactness
- lebih mudah dijaga konsistensinya lintas framework/language/database

### 3. Formatting boundary

Representasi tampilan seperti:

- `15.000`
- `90.000`

adalah concern presentation.

Core dan persistence tidak boleh menyimpan nilai tampilan berformat, tetapi menyimpan integer mentah, misalnya:

- `15000`
- `90000`

### 4. Arithmetic rules

Semua operasi aritmetika resmi yang melibatkan uang harus dilakukan dengan integer rupiah, termasuk:

- penjumlahan
- pengurangan
- perbandingan
- validasi outstanding
- validasi payment allocation
- validasi report totals

### 5. Boundary with percentage/rate

Bila di masa depan ada kebutuhan seperti:

- pajak
- komisi berbasis persentase
- diskon persentase
- rate lain

maka hasil akhirnya tetap harus diproyeksikan ke integer rupiah melalui aturan pembulatan resmi yang eksplisit.

Namun sampai ada kebutuhan dan keputusan tambahan, sistem inti saat ini mengunci:

- official money values are integer rupiah

## Alternatives Considered

### Alternative A — Menggunakan floating point untuk nominal
Ditolak.

Alasan penolakan:

- berisiko menghasilkan precision error
- bertentangan dengan toleransi 1 rupiah exactness
- tidak sehat untuk domain finansial sensitif

### Alternative B — Menyimpan uang sebagai string format tampilan
Ditolak.

Alasan penolakan:

- bukan bentuk data numerik yang sehat untuk aritmetika domain
- rawan parsing inconsistency
- mencampur concern presentation dengan business logic

### Alternative C — Menunda keputusan representasi uang
Ditolak.

Alasan penolakan:

- terlalu fundamental untuk dibiarkan ambigu
- berisiko membuat beberapa bagian sistem memakai representasi berbeda
- mempermahal koreksi di tengah proyek

## Consequences

### Positive

- perhitungan uang lebih aman
- rekonsiliasi laporan lebih kuat
- rule 1 rupiah exactness lebih realistis untuk dijaga
- implementasi lintas framework/language lebih konsisten
- testing finansial menjadi lebih jelas

### Negative

- developer harus disiplin agar tidak tergoda memakai float di helper/UI/service
- perhitungan future percentage/rate perlu aturan eksplisit tambahan
- import/export data harus hati-hati menjaga tipe numerik

## Invariants

- nominal uang resmi tidak boleh disimpan sebagai float
- nominal uang resmi tidak boleh disimpan sebagai string format tampilan
- seluruh total dan outstanding harus dapat direkonsiliasi secara integer rupiah
- mismatch 1 rupiah adalah defect kritikal
- reporting harus tetap setia pada model integer rupiah

## Implementation Notes

- value object yang direkomendasikan:
  - `Money`
  - `RupiahAmount`
- error/guard yang relevan:
  - `VALIDATION_INVALID_AMOUNT`
  - `REPORT_MISMATCH_AMOUNT`
- database column untuk uang sebaiknya memakai tipe integer/bigint sesuai kapasitas yang dibutuhkan
- parsing input UI harus menghapus formatting lalu mengubahnya ke integer sebelum masuk core
- serialisasi API harus konsisten dan jelas, misalnya mengirim integer untuk nominal resmi atau kontrak field yang tegas bila ada formatted companion field

## Related Decisions

- ADR-004 — Minimum Selling Price Guard
- ADR-005 — Paid Note Correction Requires Audit
- ADR-006 — Costing Strategy Default Average, FIFO-ready
- ADR-009 — Reporting as Read Model
- ADR-012 — Product Master Must Exist Before Supplier Receipt
