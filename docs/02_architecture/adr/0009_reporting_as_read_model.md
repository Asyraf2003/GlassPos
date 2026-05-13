# ADR-0009 — Reporting as Read Model

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Reporting / Note / Payment / Inventory / Supplier / Expense / EmployeeFinance / Audit

## Context

Sistem ini membutuhkan laporan yang kritikal, termasuk:

- pembukuan bulanan
- laporan uang masuk/keluar
- laporan biaya operasional
- laporan hutang supplier
- laporan hutang karyawan
- laporan stok
- laporan profitabilitas operasional

Kebutuhan bisnis yang sudah dikunci:

- laporan sangat sensitif dan tidak boleh selisih 1 rupiah
- ada lebih dari satu cara pandang laba yang mungkin dipakai
- core harus menyimpan data mentah operasional secara lengkap
- laporan tidak boleh menjadi tempat menyembunyikan atau menciptakan logika bisnis baru
- perubahan sensitif harus tetap dapat ditelusuri melalui audit

Jika reporting dijadikan sumber kebenaran bisnis, maka risikonya:

- angka laporan bisa berbeda dari perilaku transaksi resmi
- logika keuangan tersebar di query-query terpisah
- perubahan aturan domain sulit dikontrol
- migrasi framework/language menjadi mahal

## Decision

Sistem menetapkan:

- **reporting diposisikan sebagai read model atas data domain final**
- **sumber kebenaran bisnis tetap berada di domain dan use case resmi**
- **laporan membaca hasil final dari transaksi, movement, payment, expense, payroll, debt, dan audit-relevant data**
- **reporting tidak boleh memperkenalkan aturan bisnis inti baru yang tidak ada di core**
- **perhitungan laporan harus konsisten dengan policy resmi domain, termasuk pricing, payment, inventory, dan costing**

## Decision Details

### 1. Source of truth separation

Source of truth utama tetap berada pada domain-operational records seperti:

- Note dan Work Item
- payment records
- inventory movements
- supplier invoice/receipt/payable
- expense entries
- payroll entries
- employee debt records
- audit/correction events

Reporting membaca dan merangkum data-data tersebut.

### 2. Why read model

Read model dipilih karena:

- kebutuhan laporan beragam
- query laporan dapat berbeda dari bentuk entity operasional
- laporan perlu efisien dibaca
- tetapi bentuk baca itu tidak boleh mengambil alih logika bisnis inti

Dengan kata lain:

- transactional model menangani behavior
- reporting model menangani presentation/aggregation

### 3. Financial view flexibility

Karena bisnis mengenal lebih dari satu cara pandang laba, core tidak mengunci satu definisi laba tunggal sebagai satu-satunya angka magis.

Sebaliknya:

- core mencatat data mentah dengan benar
- reporting dapat membangun beberapa view resmi selama sumber datanya konsisten

Contoh view yang dapat hidup di reporting:

- arus kas
- pendapatan
- biaya operasional
- hasil bersih operasional
- hutang/piutang
- stok dan nilai persediaan

Namun semua view itu harus tetap setia pada data domain final dan policy resmi.

### 4. Boundary with business logic

Reporting boleh melakukan:

- agregasi
- filtering
- grouping
- summarization
- projection

Reporting tidak boleh diam-diam mengubah arti data bisnis, misalnya:

- menganggap external purchase sebagai inventory usage
- menganggap paid status dari heuristik UI
- memakai rumus costing yang berbeda dari policy resmi
- menyembunyikan correction event sehingga angka tampak lebih "rapi"

### 5. Rebuildability

Karena read model adalah turunan, ia idealnya dapat:

- dibangun ulang
- diverifikasi ulang
- direkonsiliasi dengan domain records

Ini penting untuk domain sensitif dan audit-heavy seperti sistem ini.

## Alternatives Considered

### Alternative A — Reporting jadi sumber kebenaran utama
Ditolak.

Alasan penolakan:

- melanggar separation of concerns
- rawan drift antara transaksi dan laporan
- berbahaya untuk domain yang sensitif terhadap 1 rupiah exactness

### Alternative B — Semua laporan dihitung langsung on-demand dari entity operasional tanpa read model
Tidak dipilih sebagai satu-satunya pola resmi.

Alasan:

- bisa berguna pada beberapa query sederhana
- tetapi tidak cukup sebagai strategi umum untuk semua laporan kritikal
- berisiko membuat logika agregasi tersebar dan tidak terkontrol

### Alternative C — Reporting memiliki rumus bisnis sendiri untuk "menyederhanakan" data
Ditolak.

Alasan penolakan:

- menyamarkan ketidaksesuaian domain
- mempersulit audit
- membuka peluang angka laporan berbeda dari perilaku sistem resmi

## Consequences

### Positive

- domain logic dan reporting concerns terpisah dengan sehat
- laporan lebih mudah dijaga konsistensinya
- rekonsiliasi data lebih masuk akal
- mendukung lebih dari satu view bisnis tanpa merusak core
- cocok untuk sistem yang akan berkembang dan mungkin berpindah teknologi

### Negative

- perlu desain projection/read model tambahan
- perlu prosedur rebuild/reconcile yang disiplin
- developer harus menahan diri agar tidak menyelipkan business rules baru di reporting layer

## Invariants

- reporting bukan sumber kebenaran utama domain
- angka laporan harus dapat ditelusuri ke domain records resmi
- kebijakan pricing, payment, inventory, dan costing resmi tidak boleh diubah oleh reporting layer
- correction dan audit-relevant events tidak boleh diabaikan bila berdampak pada angka
- mismatch 1 rupiah antara report dan source of truth adalah defect kritikal

## Implementation Notes

- abstraction yang direkomendasikan:
  - `ReportProjectionBuilder`
  - `MonthlyLedgerReadModel`
  - `OperationalProfitReadModel`
  - `ReportReconciliationService`
- error domain/report yang direkomendasikan:
  - `REPORT_MISMATCH_AMOUNT`
  - `REPORT_REBUILD_FAILED`
- read model bisa dibangun:
  - synchronous setelah transaksi penting
  - asynchronous via event/projection
  - hybrid
  sesuai kebutuhan implementasi
- bila menggunakan event/projection async, perlu guard rekonsiliasi agar hasil akhir tetap dapat dipercaya
- laporan harus selalu konsisten dengan official domain policies yang aktif

## Related Decisions

- ADR-003 — External Spare Part as Case Cost
- ADR-005 — Paid Note Correction Requires Audit
- ADR-006 — Costing Strategy Default Average, FIFO-ready
- ADR-008 — Audit-First Sensitive Mutations
- ADR-011 — Money Stored as Integer Rupiah
