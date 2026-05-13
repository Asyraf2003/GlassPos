# Note Finance Stabilization Blueprint

## Metadata

- Tanggal: 2026-04-29
- Nama scope: Note Finance Stabilization
- Status: ACTIVE BLUEPRINT
- Pemilik keputusan: project owner / engineer
- Sumber aturan kerja:
  - docs/01_standards/00-index.md
  - docs/01_standards/01-decision-policy.md
  - docs/01_standards/architecture/
  - docs/01_standards/domain/
- Scope besar:
  - note revision
  - payment allocation
  - refund allocation
  - inventory movement
  - reporting/projection
  - FC-003 cash change denomination reporting

## 1. Final Goal

Membangun fondasi note/finance yang stabil, auditable, dan aman untuk operasional kasir/bengkel.

Sistem harus mendukung:

1. Nota bisa dibuat, dibayar, direfund, dan direvisi tanpa merusak histori.
2. Payment dan refund menjadi financial event yang traceable.
3. Work item lama yang sudah menjadi anchor payment/refund/history tidak boleh dihancurkan secara fisik.
4. Current operational projection harus hanya membaca versi aktif/current.
5. Historical/audit view harus tetap bisa menjelaskan versi lama, refund lama, payment lama, dan inventory movement lama.
6. Reporting harus membedakan current projection dan ledger/history.
7. FC-003 cash change denomination masuk dalam scope reporting/kasir dashboard, tetapi kalkulasi denominasinya tetap pure service dan tidak boleh mengubah ledger.

## 2. Core Problem Statement

Masalah utama bukan sekadar foreign key error.

Masalah utama adalah work_items saat ini dipakai untuk dua peran yang saling konflik:

1. Current operational row
   - dipakai untuk tampilan nota aktif
   - dipakai untuk edit/revision
   - dipakai untuk current payment/refund selection

2. Historical financial anchor
   - dipakai payment component allocations
   - dipakai refund component allocations
   - dipakai note revision lines
   - dipakai inventory movement trace
   - dipakai audit/reporting

Saat note revision melakukan delete old work items lalu create work items baru, proses ini aman hanya selama old work items belum menjadi referensi financial/historical.

Setelah refund terjadi, old work items sudah menjadi anchor untuk refund_component_allocations.work_item_id.

Karena itu physical delete terhadap old work items tidak boleh dilakukan.

## 3. Locked Domain Decisions

### 3.1 Nota revision tetap harus didukung

Sistem final tidak boleh mengambil shortcut:

- melarang edit nota setelah refund sebagai solusi final
- menghapus kemampuan revision setelah payment/refund
- memaksa user membuat nota baru hanya karena nota lama sudah punya refund

Guard sementara untuk mencegah HTTP 500 boleh dipertimbangkan hanya sebagai containment darurat, bukan final domain.

### 3.2 Ledger/history tidak boleh dihancurkan

Payment/refund/inventory movement yang sudah terjadi adalah bukti financial/historical.

Tidak boleh:

- cascade delete financial allocation
- nullable FK tanpa replacement audit model
- detach refund allocation dari component/work item lama tanpa snapshot kuat
- rewrite history diam-diam
- rebuild refund allocation lama ke work item baru tanpa explicit decision dan test

### 3.3 Current projection harus dipisahkan dari history

Sistem final harus bisa menjawab dua pertanyaan berbeda:

1. Kondisi nota sekarang apa?
2. Bagaimana sejarah transaksi/stock/payment/refund sampai ke kondisi itu?

Jika satu query dipakai untuk menjawab dua pertanyaan itu tanpa boundary, report akan rawan double-count atau misleading.

### 3.4 FC-003 cash change masuk scope reporting

FC-003 bukan root cause refund/revision, tetapi tetap masuk scope final stabilization karena dashboard/report kasir harus menjelaskan kebutuhan denominal kembalian dengan benar.

Aturan:

- kalkulator denomination tetap pure
- tidak boleh mengubah ledger
- tidak boleh membaca mutable UI state sebagai source of truth
- input harus berasal dari data pembayaran/change amount yang sudah jelas
- output hanya reporting/helper operational

## 4. Scope

### 4.1 Scope In

- Audit current note revision flow.
- Audit payment allocation lifecycle.
- Audit refund allocation lifecycle.
- Audit inventory stock_out/stock_in reversal source type.
- Audit operational profit query.
- Audit note history/current projection query.
- Buat failing characterization tests untuk refund plus revision.
- Buat failing/reporting tests untuk current projection vs ledger.
- Patch supaya old referenced work items tidak physical delete.
- Tambah current/historical marker atau projection boundary.
- Pastikan current UI hanya membaca active/current rows.
- Pastikan historical view tetap bisa membaca old rows.
- Pastikan report tidak double-count old superseded rows.
- Masukkan FC-003 ke reporting/dashboard verification flow.

### 4.2 Scope Out Untuk Fase Awal

- Redesign seluruh UI kasir.
- Mengubah istilah domain locked.
- Mengubah route public tanpa change gate.
- Mengubah payment/refund request contract tanpa explicit contract document.
- Menghapus selected-row refund contract tanpa ADR baru.
- Optimasi besar sebelum bug lifecycle stabil.
- PDF/Telegram expansion.

## 5. Architecture Boundary

### 5.1 Domain/Application

Domain/application harus mengandung keputusan lifecycle:

- apakah work item boleh dihapus
- apakah work item harus disupersede
- bagaimana revision menjadi current
- bagaimana old rows tetap historis
- bagaimana refund allocation tetap valid

### 5.2 Infrastructure

Infrastructure boleh menyimpan perubahan, tetapi tidak boleh menyembunyikan lifecycle domain.

Forbidden:

- delete langsung di adapter tanpa policy domain
- cascade delete untuk menyelesaikan FK crash
- nullable FK tanpa immutable snapshot
- query report yang diam-diam mencampur current dan history

### 5.3 Transport/UI

UI hanya consumer dari projection.

UI tidak boleh menjadi tempat memperbaiki mismatch finance lifecycle.

## 6. Data Model Direction

Final model minimal harus punya pemisahan current vs historical.

Opsi yang dipilih sebagai arah awal:

- old work items tidak physical delete jika sudah menjadi financial/historical reference
- old work items diberi marker historical/superseded
- new work items dibuat sebagai active/current revision
- current projection membaca active/current rows saja
- historical/audit membaca semua relevant rows dengan revision context

Candidate fields atau model, final detail menunggu audit migration lokal:

- is_current
- superseded_at
- superseded_by_revision_id
- revision_id
- atau projection table khusus untuk current note lines

Keputusan field/table final tidak boleh dibuat sebelum snapshot schema lokal dibaca.

## 7. Reporting Direction

Reporting harus eksplisit membedakan ledger/audit report dan current operational projection.

### 7.1 Ledger/Audit Report

Menjelaskan event yang benar-benar terjadi:

- payment diterima
- refund dibayar
- stock_out terjadi
- stock_in reversal terjadi
- revision terjadi
- movement source type

### 7.2 Current Operational Projection

Menjelaskan kondisi nota aktif sekarang:

- line aktif
- total aktif
- open/paid/refunded state aktif
- outstanding aktif
- current COGS/profit view bila memang didefinisikan sebagai current view

### 7.3 Forbidden Reporting Behavior

- Menghitung old superseded work items sebagai current active lines.
- Menghitung COGS lama sebagai current cost bila line sudah diganti tanpa adjustment jelas.
- Mengurangi refund tanpa inventory reversal yang traceable.
- Menggunakan label laba untuk angka yang sebenarnya ledger movement campuran.
- Membiarkan report menghasilkan Uang Masuk, Refund, HPP, Laba yang tidak bisa dijelaskan oleh event/projection.

## 8. Workflow

### Phase 0 - Local Baseline Proof

Tujuan:
Membuktikan kondisi lokal sebelum membuat patch.

Command wajib:

- git status --short
- git rev-parse --short HEAD
- git branch --show-current
- git log --oneline -10
- git stash list with first five rows

Output wajib dikirim sebelum patch.

Exit criteria:

- branch diketahui
- HEAD diketahui
- dirty files diketahui
- stash risk diketahui
- local source of truth terkunci

### Phase 1 - Characterization Tests

Tujuan:
Membuat test merah yang menangkap bug nyata.

Minimal tests:

1. tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
   - paid note
   - refund selected/full line
   - submit note revision
   - old work item tetap ada sebagai historical anchor
   - new work item menjadi current
   - tidak FK crash

2. tests/Feature/Note/RevisionAfterRefundDoesNotRewriteRefundAllocationFeatureTest.php
   - refund allocation lama tetap menunjuk old historical work item
   - refund allocation tidak dipindah diam-diam ke work item baru

3. tests/Feature/Note/CurrentProjectionExcludesSupersededWorkItemsFeatureTest.php
   - old superseded rows tidak muncul sebagai current line
   - current note view/projection hanya menampilkan active rows

4. tests/Feature/Reporting/OperationalProfitRefundRevisionProjectionFeatureTest.php
   - scenario Uang Masuk, Refund, HPP, Laba direproduce
   - expected behavior dikunci eksplisit
   - report tidak double-count current/history

Exit criteria:

- minimal satu test merah membuktikan FK/lifecycle bug
- expected final behavior tertulis di assertion
- tidak ada patch domain sebelum test merah

### Phase 2 - Minimal Lifecycle Patch

Tujuan:
Mengganti pola delete/recreate menjadi supersede/create-current.

Target awal:

- stop physical delete untuk referenced work items
- preserve refund/payment historical anchor
- create new active current rows
- mark old rows as superseded/historical
- keep inventory reversal traceable
- do not rewrite refund allocation silently

Exit criteria:

- characterization tests dari Phase 1 hijau
- existing refund tests tetap hijau
- existing note replacement tests disesuaikan hanya jika test lama memang mengunci behavior yang salah

### Phase 3 - Projection Query Hardening

Tujuan:
Membuat current view dan history view tidak saling mencemari.

Target:

- current note detail hanya active/current rows
- payment selection hanya active/current eligible rows
- refund selection sesuai contract final
- history/audit tetap bisa membaca old rows
- note history component summary tidak double-count old superseded rows sebagai current

Exit criteria:

- current projection tests hijau
- history/audit tests hijau
- no regression untuk selected-row refund

### Phase 4 - Reporting Hardening

Tujuan:
Merapikan report cash/refund/COGS/profit supaya bisa dijelaskan.

Target:

- definisi ledger report dan current projection report eksplisit
- source type inventory reversal diaudit
- transaction_workspace_updated vs work_item_store_stock_line_reversal diputuskan dengan test
- angka manual user bisa direproduce dan dijelaskan

Exit criteria:

- operational profit targeted test hijau
- report manual local sesuai expected
- tidak ada double-count COGS dari old superseded rows

### Phase 5 - FC-003 Cash Change Integration

Tujuan:
Memasukkan cash change denomination ke reporting/kasir dashboard secara aman.

Rules:

- service calculator tetap pure
- input berasal dari payment/change data yang jelas
- tidak mengubah ledger
- tidak mencampur kalkulasi kembalian dengan lifecycle refund/revision
- dashboard hanya membaca output projection/helper

Exit criteria:

- unit tests calculator hijau
- feature/reporting tests cash change hijau
- manual dashboard test menunjukkan denominal benar
- make verify hijau

### Phase 6 - Final Verification And Report

Tujuan:
Menutup scope dengan proof lengkap.

Wajib ada:

- daftar file changed
- targeted tests output
- regression tests output
- make verify output
- manual local test notes
- known gaps
- final report/handoff

Exit criteria:

- no untracked unexpected files
- no failing targeted tests
- no failing verify
- manual local scenario selesai
- final report ditulis

## 9. Definition of Done

Scope ini belum boleh disebut selesai sebelum semua item berikut terpenuhi.

### 9.1 Documentation DoD

- Blueprint ini ada di docs/03_blueprints/v2/note_finance/.
- Setiap keputusan domain baru dicatat di blueprint atau ADR/handoff terkait.
- Jika AI_RULES perlu pointer baru, changelog harus ditambah.
- Tidak semua detail scope dimasukkan ke AI_RULES; AI_RULES tetap constitution kerja, blueprint ini menjadi contract scope.

### 9.2 Test DoD

Wajib ada proof untuk:

- refund plus revision tidak FK crash
- old refunded work item tetap historical
- new work item menjadi current
- refund allocation tidak rewrite diam-diam
- current projection exclude superseded rows
- history/audit tetap bisa trace old rows
- operational profit/report tidak double-count
- FC-003 cash change calculator tetap pure
- FC-003 dashboard/report integration benar

### 9.3 Command DoD

Minimal command final:

- targeted note revision/refund tests
- targeted reporting tests
- targeted FC-003 tests
- existing refund lifecycle tests
- existing note replacement tests
- make verify

Tidak boleh klaim pass tanpa paste output.

### 9.4 Manual Verification DoD

Manual test minimal:

1. Buat nota store stock dengan COGS known.
2. Bayar nota.
3. Refund selected/full line.
4. Edit/revision nota.
5. Pastikan tidak FK crash.
6. Pastikan detail nota menampilkan versi aktif.
7. Pastikan history/audit masih menjelaskan versi lama dan refund lama.
8. Pastikan report Uang Masuk, Refund, HPP, Laba sesuai definisi.
9. Pastikan cash change denomination tampil benar pada dashboard/report terkait.

### 9.5 Safety DoD

- Tidak cascade delete financial history.
- Tidak nullable FK asal-asalan.
- Tidak rewrite refund allocation tanpa explicit test.
- Tidak mengganti route/request public contract tanpa change gate.
- Tidak mengganti istilah domain locked.
- Tidak menaikkan progress tanpa proof.
- Tidak menggabungkan patch besar tanpa targeted test.

## 10. Working Rules For Future Sessions

Saat membuka sesi baru untuk scope ini, AI wajib membaca:

1. docs/01_standards/00-index.md
2. docs/01_standards/01-decision-policy.md
3. docs/03_blueprints/v2/note_finance/2026-04-29-note-finance-stabilization-blueprint.md
4. handoff terakhir yang relevan
5. command output lokal terbaru dari user

Setiap respons kerja teknis wajib membedakan:

- FACT
- GAP
- DECISION
- ACTIVE STEP
- PROOF
- NEXT

Progress hanya boleh naik jika ada proof nyata.

Jika local command output bertentangan dengan remote/GitHub, local command output menang.

## 11. Current Known Gaps

- Local HEAD belum dibuktikan dalam sesi ini.
- Local dirty state belum dibuktikan.
- Local stash belum dibuktikan.
- Schema final current-vs-history belum diputuskan.
- Failing tests belum dibuat.
- Reporting expected behavior untuk ledger vs current projection belum dikunci dengan test.
- FC-003 sudah punya unit direction, tetapi integration ke dashboard/report masih harus masuk Phase 5.

## 12. Next Active Step

Ambil local baseline proof.

Command lokal yang harus dijalankan setelah blueprint dibuat:

- git status --short
- git rev-parse --short HEAD
- git branch --show-current
- git log --oneline -10
- git stash list with first five rows

Setelah proof lokal tersedia, lanjut ke Phase 1: failing characterization test untuk refund plus note revision.
