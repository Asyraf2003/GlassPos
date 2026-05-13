# ADR-0017 - Audit Log Retention, Archive Evaluation, and One-Month Load Proof

- Status: Accepted
- Date: 2026-05-01
- Deciders: Project Owner, Architecture Decision
- Scope: Audit / Archive / Retention / Reporting / Operations / Database Load
- Related:
  - ADR-0008 - Audit-First Sensitive Mutations
  - ADR-0016 - Post-Close Note Correction and Refund Flexibility

## Context

Sistem Hyperpos memiliki mutasi sensitif yang memengaruhi:

- uang
- stok
- hutang/piutang
- nota
- refund
- pembayaran
- laporan
- akses admin/kasir
- perubahan data master yang berdampak finansial

ADR-0008 sudah mengunci bahwa mutasi sensitif wajib audit-first. ADR-0016 juga mengunci bahwa closed, paid, dan refunded note tetap boleh dimutasi melalui jalur resmi yang audited, revisioned, evented, dan dapat diproyeksikan ulang.

Dengan semakin banyaknya mutasi yang wajib diaudit, tabel audit log akan terus bertambah. Ada kebutuhan untuk mempertimbangkan fitur:

- export audit log
- import audit log/archive
- purge atau hapus audit log lama
- pembacaan audit log lama jika terjadi masalah

Namun belum ada bukti produksi selama satu bulan penuh tentang:

- volume audit log per hari
- volume audit log per bulan
- ukuran tabel audit log
- ukuran index audit log
- waktu load halaman audit log
- waktu search/filter audit log
- dampak audit log terhadap backup database
- kebutuhan operasional nyata untuk membaca audit lama
- apakah audit log sudah cukup konsisten dari semua fitur sensitif

Membangun export/import/purge penuh sebelum ada data nyata berisiko membuat sistem terlalu kompleks terlalu dini.

## Decision

Sistem tidak akan langsung membangun fitur export, import, dan purge audit log sebagai active scope.

Untuk fase awal, keputusan yang dikunci adalah:

1. Audit log tetap append-only.
2. Audit log aktif tidak boleh dihapus selama periode pengukuran awal.
3. Semua mutasi sensitif tetap wajib mencatat audit log sesuai ADR-0008 dan ADR-0016.
4. Alasan perubahan wajib dicatat pada fitur asal saat mutation terjadi, bukan ditambahkan belakangan di halaman audit log.
5. Halaman audit log berperan sebagai pusat baca, filter, dan investigasi audit.
6. Export/import/purge audit log masuk backlog sampai ada load proof minimal satu bulan.
7. Setelah satu bulan pemakaian nyata, keputusan archive/purge baru boleh dibuat berdasarkan data aktual.

## Why One-Month Load Proof Is Required

Audit log adalah bukti operasional, bukan sekadar data duplikat.

Tanpa bukti beban nyata, keputusan export/import/purge dapat menjadi salah arah:

- terlalu cepat membangun fitur archive yang belum dibutuhkan
- terlalu cepat menghapus bukti audit dari database aktif
- membuat import JSON yang berisiko mencampur archive dengan audit aktif
- mengoptimasi storage sebelum audit completeness terbukti
- membuat mekanisme kompleks sebelum query/index dasar diuji

Sistem harus mengukur dulu beban aktual selama satu siklus operasional minimal satu bulan.

## Audit Log Input Rule

Alasan mutasi harus dimasukkan di fitur asal.

Contoh:

- edit nota meminta alasan di form edit nota
- refund meminta alasan di flow refund
- edit produk meminta alasan di flow edit produk
- edit supplier invoice meminta alasan di flow supplier invoice
- edit expense meminta alasan di flow expense
- perubahan akses admin meminta alasan di flow capability/access

Halaman audit log tidak boleh menjadi tempat mengisi alasan setelah mutasi selesai.

Alasan yang ditambahkan setelah mutasi dapat merusak nilai forensik audit karena user bisa membuat pembenaran belakangan.

## Audit Log Page Role

Halaman audit log boleh menjadi pusat:

- melihat semua event audit
- melihat alasan perubahan
- melihat actor
- melihat waktu perubahan
- melihat entity yang berubah
- melihat before/after atau context perubahan
- filter berdasarkan tanggal
- filter berdasarkan event
- filter berdasarkan actor
- filter berdasarkan entity
- membaca audit aktif
- membaca archive audit secara read-only jika fitur archive sudah diputuskan di masa depan

Halaman audit log tidak boleh menjadi jalur bypass untuk:

- mengubah audit lama
- menulis alasan belakangan
- menghapus audit aktif secara manual
- mengimpor archive langsung ke audit aktif tanpa prosedur recovery resmi

## One-Month Evaluation Data

Selama satu bulan pertama, sistem harus mengumpulkan atau dapat menghitung data berikut:

### Volume

- total audit rows
- audit rows per day
- audit rows per event
- audit rows per actor
- audit rows per entity type
- average context size
- maximum context size

### Storage

- ukuran tabel audit log
- ukuran index audit log
- estimasi pertumbuhan per bulan
- estimasi pertumbuhan per tahun
- dampak terhadap ukuran backup database

### Query Performance

- waktu load halaman audit log
- waktu search audit log
- waktu filter by date
- waktu filter by event
- waktu filter by actor
- waktu filter by entity
- query audit log paling lambat

### Operational Usage

- apakah audit log dibuka oleh admin
- audit event apa yang paling sering dicari
- apakah alasan edit cukup jelas
- apakah owner/admin butuh membaca audit lama
- apakah ada kasus investigasi lintas bulan
- apakah audit log membantu menemukan mismatch laporan/stok/uang

### Audit Completeness

- fitur sensitif mana yang sudah mencatat reason
- fitur sensitif mana yang sudah mencatat actor
- fitur sensitif mana yang sudah mencatat entity reference
- fitur sensitif mana yang sudah mencatat before/after atau context yang cukup
- fitur sensitif mana yang masih belum audit-first

## Decision Matrix After One Month

Keputusan setelah satu bulan harus mengikuti data aktual.

### Case A - Low Load

Jika audit log kurang dari 100.000 rows per bulan dan halaman audit tetap cepat:

- export/import/purge belum perlu dibangun
- audit log tetap disimpan di database aktif
- fokus pada audit completeness
- tambah filter/index bila diperlukan
- retention aktif direkomendasikan minimal 12 bulan

### Case B - Medium Load

Jika audit log berada di kisaran 100.000 sampai 1.000.000 rows per bulan:

- siapkan export bulanan read-only
- jangan langsung purge kecuali storage mulai mengganggu
- tambah index dan filter yang queryable
- kurangi pencarian JSON liar
- pertimbangkan kolom queryable untuk actor, entity, reason, event, dan created_at

### Case C - High Load

Jika audit log lebih dari 1.000.000 rows per bulan atau mulai mengganggu backup/query:

- bangun archive bulanan
- archive harus punya metadata dan checksum
- purge hanya boleh untuk row yang sudah verified archived
- audit aktif tetap menyimpan retention minimal sesuai policy
- archive viewer harus read-only

### Case D - Large Context Problem

Jika ukuran audit membengkak karena context terlalu besar:

- evaluasi format snapshot
- gunakan structured diff untuk field tertentu
- gunakan hybrid snapshot untuk transaksi finansial/stok sensitif
- jangan menyimpan data besar yang tidak relevan
- lampiran/file besar disimpan sebagai reference/hash, bukan dicopy penuh ke context audit

## Retention Policy

Default awal:

- audit log aktif tidak dihapus selama satu bulan pertama
- setelah evaluasi, retention aktif direkomendasikan 12 bulan
- retention lebih pendek dari 12 bulan hanya boleh diputuskan jika storage terbukti bermasalah
- retention minimum darurat adalah 3 sampai 6 bulan, dengan syarat archive verified sudah tersedia

Audit lama tidak boleh dipurge hanya karena tabel terlihat ramai.

Purge hanya boleh dilakukan berdasarkan policy yang dapat diaudit.

## Archive Policy

Jika archive dibangun di masa depan, archive harus memenuhi aturan:

- export berdasarkan range tanggal atau range ID
- format direkomendasikan NDJSON/JSONL, satu audit event per baris
- archive metadata wajib disimpan
- metadata wajib mencatat row count
- metadata wajib mencatat range ID
- metadata wajib mencatat range created_at
- metadata wajib mencatat checksum SHA-256
- metadata wajib mencatat actor yang melakukan export
- metadata wajib mencatat waktu export
- archive harus read-only setelah dibuat
- archive harus dapat diverifikasi sebelum purge

## Purge Policy

Purge audit log aktif hanya boleh dilakukan jika:

- archive untuk range tersebut sudah dibuat
- checksum archive valid
- row count archive cocok
- archive metadata tersimpan
- actor memiliki permission yang sah
- range tidak sedang dibutuhkan untuk investigasi aktif
- range lebih tua dari retention aktif yang diputuskan

Purge tidak boleh berbentuk tombol hapus bebas.

Purge harus berbasis verified archive lifecycle.

## Import Policy

Import JSON tidak boleh langsung memasukkan data ke tabel audit log aktif.

Default import di masa depan harus berperan sebagai archive viewer read-only.

Alasan:

- mencegah duplikasi audit event
- mencegah bentrok ID
- mencegah campur event aktif dan event archive
- menjaga urutan audit tetap jelas
- menjaga forensik audit tetap kuat

Restore archive ke audit aktif hanya boleh menjadi maintenance/recovery procedure khusus, bukan fitur UI normal.

## Audit Storage Shape Direction

Audit log aktif sebaiknya tidak selamanya hanya bergantung pada JSON context.

Kolom queryable yang direkomendasikan untuk evaluasi masa depan:

- id
- event
- actor_id
- actor_name
- entity_type
- entity_id
- reason
- context
- created_at
- archived_at nullable
- export_batch_id nullable

Context tetap boleh menyimpan detail tambahan, tetapi field utama yang sering difilter harus tersedia sebagai kolom.

## Invariants

- Audit log aktif bersifat append-only.
- Audit log tidak boleh diedit untuk memperbaiki alasan setelah mutasi terjadi.
- Alasan mutasi wajib dicatat pada flow asal saat mutasi terjadi.
- Mutasi sensitif tanpa audit yang diwajibkan adalah invalid.
- Export/import/purge tidak boleh dibangun sebelum audit completeness dan load proof satu bulan tersedia.
- Purge tanpa verified archive adalah invalid.
- Import archive ke audit aktif bukan default behavior.
- Archive viewer harus read-only.
- Audit harus tetap bisa menjawab siapa, kapan, melakukan apa, terhadap apa, kenapa, sebelum/sesudahnya apa, dan dampaknya apa.

## Implementation Direction

Urutan implementasi yang aman:

1. Pastikan semua mutasi sensitif masuk audit log.
2. Pastikan reason, actor, entity, timestamp, dan before/after/context cukup tersedia.
3. Tambahkan filter dasar audit log bila dibutuhkan.
4. Ukur beban audit log selama satu bulan.
5. Buat report/command untuk audit volume, storage, dan query performance.
6. Setelah satu bulan, evaluasi berdasarkan decision matrix ADR ini.
7. Jika low load, tunda export/import/purge.
8. Jika medium load, bangun export read-only.
9. Jika high load, bangun archive verified dan purge policy.
10. Jika archive sudah ada, import hanya sebagai archive viewer read-only.

## Non-Goals

ADR ini tidak membangun langsung:

- export audit log
- import audit log
- purge audit log
- archive viewer
- audit schema migration
- audit dashboard metrics

ADR ini hanya mengunci policy, urutan keputusan, dan syarat proof sebelum fitur archive/purge dibuat.

## Consequences

### Positive

- mencegah over-engineering sebelum ada data nyata
- menjaga audit log tetap kuat untuk investigasi
- menjaga purge tetap aman dan berbasis verified archive
- memisahkan audit aktif dari archive read-only
- memberi dasar evaluasi yang terukur setelah satu bulan

### Negative

- export/import/purge belum tersedia di fase awal
- audit log aktif akan terus bertambah selama periode pengukuran
- perlu disiplin mengumpulkan data volume/storage/performance
- perlu audit completeness sebelum archive lifecycle dibangun

