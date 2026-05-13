# Note Finance Current Projection Addendum

## Metadata

- Tanggal: 2026-04-29
- Scope: Note Finance Stabilization
- Status: ACTIVE ADDENDUM
- Parent blueprint: docs/03_blueprints/v2/note_finance/2026-04-29-note-finance-stabilization-blueprint.md
- Related ADR: docs/02_architecture/adr/0024-note-current-projection-and-current-only-refund.md
- Related handoff: docs/99_archive/handoff/v2/note_finance/2026-04-29-current-projection-refund-edit-handoff.md

---

## 1. Purpose

Dokumen ini mengunci addendum desain untuk edit dan refund nota setelah root bug ditemukan.

Root bug yang sudah dibuktikan:

~~~text
Revision nota mencoba delete old work_items.
Old work_items sudah menjadi anchor refund_component_allocations.work_item_id.
Database menolak delete melalui FK fk_rca_work_item.
~~~

Masalah sebenarnya bukan FK error saja.

Masalah sebenarnya adalah work_items dipakai untuk dua peran yang harus dipisah:

~~~text
1. Current operational rows untuk halaman nota aktif.
2. Historical atau ledger anchors untuk payment, refund, inventory, dan audit.
~~~

---

## 2. Locked Product Behavior

### 2.1 Edit Always Available

Halaman nota harus selalu menyediakan aksi edit untuk versi aktif atau current.

Setiap submit edit berarti:

~~~text
- membuat hitungan current baru
- menghasilkan revision baru
- mengubah current projection
- tidak menghancurkan legacy, history, atau ledger lama
~~~

User gaptek yang menimpa nota lama adalah kondisi normal yang harus ditangani sistem.

Jika user membuat nota 8 line, lalu setelah refund menambahkan barang baru dengan cara mengedit nota lama, sistem harus tetap aman.

### 2.2 Refund Always Available For Current Version

Halaman nota harus selalu menyediakan aksi refund untuk versi aktif atau current.

Refund baru hanya boleh terhadap current active projection.

Versi lama tidak eligible untuk refund baru.

### 2.3 Legacy Rows Are Not Current Rows

Semua versi lama setelah edit menjadi legacy, history, dan audit.

Versi lama:

~~~text
- tidak ikut current total
- tidak ikut current outstanding
- tidak ikut current refund selection
- tidak ikut halaman nota aktif
- tetap tersedia untuk history dan audit
- tetap menjadi anchor event lama
~~~

### 2.4 Existing Ledger Events Remain Valid

Payment, refund, dan inventory movement lama tidak boleh dihapus atau di-rewrite.

Event lama tetap valid sebagai ledger dan audit event.

Contoh:

~~~text
Revision 1 memiliki 8 line.
Refund terjadi terhadap versi aktif saat itu.
Revision 2 dibuat setelah user edit nota.

Refund lama tetap valid.
Line lama menjadi legacy.
Current nota hanya membaca revision atau projection terbaru.
~~~

---

## 3. Final Direction

Arah final untuk scope ini adalah Hybrid C+:

~~~text
Immutable ledger dan history + current projection table.
~~~

Dengan pemisahan:

~~~text
work_items atau revision rows:
historical atau versioned source dan event anchor.

payment, refund, inventory_movements:
immutable ledger events.

current projection:
source of truth halaman nota aktif.
~~~

---

## 4. Current Projection Rule

Halaman nota aktif tidak boleh membaca historical work_items sebagai source utama.

Halaman nota aktif harus membaca current projection.

Candidate table name:

~~~text
note_current_lines
~~~

Candidate responsibilities:

~~~text
- menyimpan line aktif terakhir per note
- menjadi basis tampilan nota aktif
- menjadi basis current total
- menjadi basis refund selection aktif
- menjadi basis edit workspace aktif
~~~

Projection adalah materialized current state, bukan ledger.

Jika projection rusak, projection boleh direbuild dari revision atau current source.

Ledger event tidak boleh dianggap cache.

---

## 5. Ledger Rule

Ledger dan history tetap event-based.

Tables atau events yang tidak boleh dihancurkan:

~~~text
customer_payments
payment_allocations
payment_component_allocations
customer_refunds
refund_component_allocations
inventory_movements
note_revisions
note mutation events atau snapshots
historical work_items atau revision rows
~~~

Forbidden:

~~~text
- cascade delete financial history
- nullable FK asal-asalan
- rewrite refund allocation lama ke work item baru
- delete old work item yang sudah jadi anchor event
- current UI membaca legacy rows tanpa filter atau projection
~~~

---

## 6. Refund Eligibility Rule

Refund baru hanya dari current active projection.

Allowed:

~~~text
current active line -> refund baru
~~~

Forbidden:

~~~text
legacy line -> refund baru
historical superseded row -> refund baru
old work_item -> refund baru setelah line tersebut tidak current
~~~

Important:

Refund lama yang sudah terjadi tetap valid karena pada saat event terjadi row itu current.

---

## 7. Edit Rule

Edit tidak boleh destructive terhadap ledger atau history.

Saat edit:

~~~text
1. create revision baru
2. preserve old rows dan history
3. rebuild current projection
4. create new current line representation
5. reconcile current totals
6. write inventory adjustments atau reversals as event movements when required
7. keep old payment, refund, dan inventory anchors valid
~~~

Old current data setelah edit menjadi legacy.

Legacy data tidak ikut current calculation.

---

## 8. Reporting Boundary

### 8.1 Current Operational Report

Current operational report boleh membaca projection terbaru.

Contoh:

~~~text
current note total
current outstanding
current active lines
current refundable lines
current operational UI state
~~~

### 8.2 Ledger Or Historical Report

Ledger report harus membaca event source.

Contoh:

~~~text
cash in from payments
cash out from refunds
inventory movements
payment or refund history
audit trail
~~~

### 8.3 Forbidden Reporting Behavior

Tidak boleh:

~~~text
- double-count old legacy rows sebagai current rows
- menghapus efek event lama dari ledger hanya karena nota diedit
- mencampur projection current dengan ledger event tanpa label
- memakai current projection sebagai bukti tunggal historical finance
~~~

---

## 9. Open Implementation Details

Detail berikut belum diputuskan dan harus menjadi decision gate saat implementasi:

~~~text
- nama final table current projection
- struktur kolom projection
- cara rebuild projection
- apakah work_items tetap dipakai sebagai historical row atau dibuat note_revision_lines sebagai source utama
- relasi exact antara projection line dan source revision line
- indexing dan unique constraint current projection
- cara report current vs ledger diberi label di UI
~~~

Tidak boleh diputuskan diam-diam saat patch.

---

## 10. Minimum Implementation Phases

### Phase A - Documentation Lock

Create:

~~~text
blueprint addendum
ADR
handoff
~~~

### Phase B - Characterization

Keep failing test:

~~~text
tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
~~~

Target root failure:

~~~text
FK fk_rca_work_item blocks delete old work item after refund.
~~~

### Phase C - Projection Design

Design table and ports before migration.

Candidate files:

~~~text
app/Ports/Out/Note/NoteCurrentLineProjectionPort.php
app/Adapters/Out/Note/DatabaseNoteCurrentLineProjectionAdapter.php
database/migrations/*_create_note_current_lines_table.php
~~~

### Phase D - Safe Write Path

Revision write path must become transaction-safe:

~~~text
create revision
preserve history
rebuild current projection
sync note total
reconcile current payment or refund state
write inventory movements or reversals
commit atomically
~~~

### Phase E - Reader Migration

Move current readers from historical work_items to projection.

At minimum inspect and decide for:

~~~text
DatabaseNoteReaderAdapter
DatabaseNoteActiveWorkItemFilter
SelectedActiveWorkItemsResolver
EditTransactionWorkspacePageDataBuilder
NoteDetailPageDataBuilder
NoteRefundPaymentOptionsBuilder
NoteBillingProjectionBuilder
reporting queries using work_items
dashboard queries using work_items
history queries using work_items
~~~

### Phase F - Verification

Required proof:

~~~text
targeted red test becomes green
existing replacement tests reviewed or updated
existing refund tests pass
current note UI manual test
refund current-only manual test
ledger or history manual check
make verify
~~~

---

## 11. Definition of Done

This addendum is not done until:

~~~text
- current projection table exists
- current note page reads current projection
- edit rebuilds projection without deleting ledger anchors
- refund only targets current projection
- legacy rows do not appear in current calculation
- existing refund/payment anchors remain valid
- reporting separates current projection from ledger events
- tests prove revision after refund does not FK crash
- make verify passes
~~~

No progress claim without command output.
