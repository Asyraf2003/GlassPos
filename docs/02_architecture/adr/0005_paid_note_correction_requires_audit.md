# ADR-0005 — Paid Note Correction Requires Audit

- Status: SUPERSEDED IN PART by ADR-0016
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Note / Payment / Audit / Reporting / Authorization

## Supersession Note

ADR-0005 is superseded in part by ADR-0016.

Still valid from ADR-0005:

- paid note mutation must not be silent overwrite
- reason is required
- actor is required
- timestamp is required
- before/after snapshot is required
- sensitive mutation must be auditable
- reporting must be able to reconstruct change history

Changed by ADR-0016:

- closed/paid/refunded note is no longer treated as a terminal mutation lock
- adding line after paid/refund can be valid when performed through audited revision/event flow
- refund can neutralize selected rows in the same note
- refund/cancel of unpaid row can still reverse inventory and reduce outstanding
- post-consequence note mutation is allowed if financial ledger, inventory ledger, audit timeline, and reporting projection remain consistent

## Context

Domain transaksi pada sistem bengkel ini sangat sensitif karena:

- pembayaran dapat parsial
- satu note dapat memuat banyak work item
- laporan dan saldo tidak boleh selisih 1 rupiah
- perubahan pada transaksi yang sudah dibayar penuh berpotensi merusak audit, laporan, dan jejak operasional

Kebutuhan bisnis yang sudah dikunci:

- transaksi/note yang sudah lunas **tidak boleh diubah bebas**
- bila hanya ingin menambah item baru setelah transaksi selesai/lunas, sebaiknya dibuat transaksi/kasus baru
- bila ada salah input, koreksi tetap **boleh**
- user cukup mengisi **alasan**
- sistem wajib otomatis mencatat:
  - siapa
  - kapan
  - perubahan sebelum/sesudah

Dengan demikian bisnis tidak memilih model immutable total, dan juga tidak memilih model editable bebas. Yang dipilih adalah:

- editable only through controlled correction with full audit

## Decision

Sistem menetapkan:

- **paid Note tidak boleh diedit secara bebas**
- **setiap perubahan pada paid Note hanya boleh melalui correction flow yang terkontrol**
- **correction wajib memiliki alasan yang diisi user**
- **sistem wajib otomatis menyimpan actor, timestamp, before snapshot, after snapshot, dan referensi perubahan**
- **penambahan item baru setelah paid Note harus dibuat sebagai transaksi/kasus baru, bukan menambah line baru ke paid Note yang sama**

## Decision Details

### 1. Boundary of “paid”

Untuk kebutuhan domain ini, Note dianggap paid bila outstanding resmi yang dihitung domain sama dengan nol.

Status paid harus berasal dari:

- total note
- total payment sah
- allocation/outstanding rule resmi

Bukan dari toggle manual UI.

### 2. Allowed behavior after paid

Setelah Note berada dalam keadaan paid:

- perubahan bebas dilarang
- add new item ke Note yang sama dilarang
- correction diperbolehkan hanya melalui use case resmi
- refund atau adjustment dapat dipakai bila implementasi detail membutuhkannya
- semua perubahan harus meninggalkan jejak audit penuh

### 3. Required user input

User hanya wajib memasukkan:

- alasan correction

Data berikut tidak boleh dibebankan sebagai input manual user:

- actor
- timestamp
- before snapshot
- after snapshot

Semua data tersebut harus dibangkitkan dan disimpan otomatis oleh sistem.

### 4. Why new items must become new transaction

Bila setelah Note lunas user ternyata ingin menambah pekerjaan atau item baru, perilaku resmi bukan mengedit Note lama, tetapi:

- buat transaksi/kasus baru

Alasan:

- menjaga arti historis transaksi yang sudah diselesaikan
- menghindari campur aduk antara koreksi dan penambahan bisnis baru
- menjaga laporan tetap dapat ditelusuri

### 5. Audit scope

Audit correction minimal harus menangkap:

- note reference
- actor
- timestamp
- reason
- before state
- after state
- affected fields or structured diff
- relation to payment/refund/correction event bila ada

## Alternatives Considered

### Alternative A — Paid Note immutable total tanpa pengecualian
Ditolak.

Alasan penolakan:

- tidak sesuai kebutuhan bisnis nyata
- bisnis tetap membutuhkan kemampuan memperbaiki salah input
- akan mendorong praktik operasional informal di luar sistem

### Alternative B — Paid Note boleh diedit bebas selama user hati-hati
Ditolak.

Alasan penolakan:

- bertentangan dengan kebutuhan bisnis
- merusak audit trail
- berisiko besar terhadap laporan dan rekonsiliasi
- tidak sesuai dengan sensitivitas domain 1 rupiah exactness

### Alternative C — Paid Note boleh diedit bebas asalkan ada log teks sederhana
Ditolak.

Alasan penolakan:

- tidak cukup kuat
- tidak menjamin snapshot before/after
- sulit untuk forensik perubahan
- terlalu lemah untuk domain finansial sensitif

## Consequences

### Positive

- salah input tetap dapat diperbaiki secara resmi
- histori transaksi lunas tetap terjaga
- audit lebih kuat
- laporan lebih dapat dipercaya
- perbedaan antara correction dan bisnis baru tetap jelas

### Negative

- correction flow lebih kompleks dibanding edit biasa
- implementasi snapshot/diff perlu disiplin
- developer tidak bisa memakai update CRUD sederhana untuk paid Note
- UI harus membedakan edit biasa dan correction

## Invariants

- paid Note tidak boleh menerima penambahan item baru
- correction pada paid Note wajib memiliki reason
- actor dan timestamp correction wajib otomatis tercatat
- before/after snapshot wajib tersedia untuk perubahan sensitif
- total/outstanding resmi harus tetap dapat direkonstruksi setelah correction event
- perubahan paid Note di luar correction flow dianggap invalid secara domain

## Implementation Notes

- use case yang direkomendasikan:
  - `CorrectPaidNote`
  - `RecordRefund`
  - `RecordAdjustment`
- error domain yang direkomendasikan:
  - `NOTE_ALREADY_PAID`
  - `NOTE_NEW_ITEMS_NOT_ALLOWED_AFTER_PAID`
  - `AUDIT_REASON_REQUIRED`
- correction flow tidak boleh digantikan dengan update repository langsung
- reporting harus dapat mengenali bahwa perubahan berasal dari correction event resmi
- desain audit dapat memakai snapshot penuh, structured diff, atau keduanya, selama before/after dapat dipertanggungjawabkan

## Related Decisions

- ADR-001 — One Note Multi-Item Model
- ADR-004 — Minimum Selling Price Guard
- ADR-008 — Audit-First Sensitive Mutations
- ADR-009 — Reporting as Read Model
- ADR-011 — Money Stored as Integer Rupiah
