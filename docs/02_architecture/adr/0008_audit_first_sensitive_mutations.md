# ADR-0008 — Audit-First Sensitive Mutations

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Audit / Note / Payment / Inventory / Supplier / EmployeeFinance / Expense / Authorization

## Context

Sistem ini memiliki sensitivitas tinggi terhadap:

- uang
- stok
- hutang/piutang
- laporan
- perubahan transaksi yang sudah final atau sensitif

Kebutuhan bisnis yang sudah dikunci:

- toleransi selisih finansial = 0 rupiah
- perubahan sensitif wajib tercatat
- user cukup memasukkan alasan pada perubahan yang memang mensyaratkan alasan
- sistem wajib otomatis mencatat siapa, kapan, sebelum/sesudah, dan konteks perubahan
- edit uang masuk, edit barang, edit faktur, edit transaksi, dan tindakan sensitif lain harus masuk activity log/audit log

Karena domain ini kompleks, tanpa audit-first mutation policy akan muncul risiko:

- perubahan data final tanpa jejak
- sulit menelusuri sumber mismatch laporan
- correction dan manipulasi terlihat sama
- pergantian developer atau framework merusak integritas historis

## Decision

Sistem menetapkan:

- **semua mutasi sensitif wajib bersifat audit-first**
- **audit bukan addon atau best-effort, tetapi bagian wajib dari flow resmi**
- **aksi sensitif hanya dianggap sah bila audit yang diwajibkan berhasil dicatat**
- **untuk perubahan yang mensyaratkan alasan, alasan menjadi input wajib user**
- **data audit lain seperti actor, timestamp, before/after, dan context harus dihasilkan otomatis oleh sistem**

## Decision Details

### 1. Apa yang termasuk mutasi sensitif

Minimal kategori mutasi sensitif mencakup:

- correction pada paid Note
- perubahan pembayaran atau cash-in/cash-out yang relevan
- stock adjustment
- perubahan supplier invoice yang berdampak finansial/stok
- perubahan biaya operasional
- perubahan hutang karyawan dan pembayarannya
- aktivasi/non-aktivasi capability transaksi admin
- perubahan data harga/minimum selling price yang relevan
- tindakan lain yang memengaruhi uang, stok, status final, atau laporan

### 2. Audit-first meaning

Audit-first berarti:

- flow resmi harus menganggap audit sebagai bagian dari transaksi bisnis
- bukan log teks opsional yang ditulis belakangan
- bila mutation membutuhkan audit dan audit gagal dicatat, mutation dianggap gagal/tidak sah sesuai batas transaksi implementasi

### 3. Required audit fields

Audit minimal harus dapat merekam:

- actor
- action type
- entity type
- entity reference
- timestamp
- reason bila diwajibkan
- before state atau snapshot relevan
- after state atau snapshot relevan
- context metadata yang dibutuhkan untuk pelacakan

### 4. Activity log vs audit log

Implementasi boleh memisahkan:

- activity log
- audit log

atau menggabungkannya secara internal.

Namun secara perilaku bisnis, kebutuhan minimal tetap:

- tindakan sensitif dapat ditelusuri secara andal
- perubahan before/after dapat dipertanggungjawabkan
- log tidak hanya berisi pesan teks generik

### 5. Why automated metadata matters

User tidak boleh dibebani untuk mengisi:

- siapa
- kapan
- perubahan sebelum/sesudah

karena:

- data itu sudah diketahui sistem
- input manual menambah risiko salah
- audit harus seandal mungkin

## Alternatives Considered

### Alternative A — Audit opsional untuk beberapa mutasi
Ditolak.

Alasan penolakan:

- tidak sesuai sensitivitas bisnis
- membuka celah perubahan tanpa jejak
- membuat kualitas kontrol tidak konsisten

### Alternative B — Audit berupa catatan teks sederhana
Ditolak sebagai standar minimal.

Alasan penolakan:

- terlalu lemah untuk domain finansial sensitif
- sulit dipakai untuk forensik perubahan
- tidak menjamin before/after tersedia

### Alternative C — Audit hanya di level database triggers
Ditolak sebagai solusi utama.

Alasan penolakan:

- trigger bisa membantu sebagai guard teknis, tetapi tidak cukup untuk memodelkan alasan bisnis, context, dan action semantics
- tidak portable
- sulit dijadikan bahasa domain yang jelas

## Consequences

### Positive

- integritas perubahan lebih kuat
- mismatch laporan/stok lebih mudah ditelusuri
- correction berbeda jelas dari manipulasi liar
- mendukung kebutuhan portfolio dan migrasi lintas framework
- memperkuat kepercayaan terhadap sistem

### Negative

- implementasi mutation menjadi lebih disiplin
- repository/update mentah menjadi tidak boleh dipakai sembarangan
- test audit harus menjadi bagian rutin
- desain snapshot/diff memerlukan perhatian

## Invariants

- mutasi sensitif tanpa audit yang diwajibkan adalah invalid
- reason wajib hanya untuk aksi yang memang membutuhkannya, sesuai policy/use case
- actor dan timestamp audit harus otomatis
- before/after atau representasi perubahan yang cukup harus tersedia untuk aksi sensitif
- audit tidak boleh tergantung pada UI tertentu
- audit harus konsisten lintas adapter

## Implementation Notes

- abstraction yang direkomendasikan:
  - `AuditService`
  - `SensitiveMutationPolicy`
  - `ActivityRecorder`
- error domain yang direkomendasikan:
  - `AUDIT_REASON_REQUIRED`
  - `AUDIT_SNAPSHOT_FAILED`
- transaksi aplikasi yang memuat mutation sensitif sebaiknya mendefinisikan batas commit yang memastikan data bisnis dan audit konsisten
- audit storage dapat dipisah dari entity business tables, tetapi akses pelacakannya harus stabil
- detail format snapshot boleh berupa:
  - full snapshot
  - structured diff
  - hybrid
  selama cukup untuk investigasi dan pembuktian perubahan

## Related Decisions

- ADR-005 — Paid Note Correction Requires Audit
- ADR-007 — Admin Transaction Entry Behind Capability Policy
- ADR-009 — Reporting as Read Model
- ADR-011 — Money Stored as Integer Rupiah
