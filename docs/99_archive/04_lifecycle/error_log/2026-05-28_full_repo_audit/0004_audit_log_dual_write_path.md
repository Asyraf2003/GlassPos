# ERROR LOG 0004 - AUDIT LOG DUAL WRITE PATH

## FACT
- Laporan ini adalah audit analitis untuk jalur tulis audit, bukan patch, bukan refactor, dan bukan klaim final coverage.
- Source code dan command output mengalahkan narasi dokumen jika ada konflik.
- `audit_logs`, `audit_events`, dan `audit_outbox` semuanya ada di schema migration source.
- Infrastructure service binding menunjukkan dua port audit yang aktif:
  - `AuditEventWriterPort -> DatabaseAuditOutboxWriterAdapter`
  - `AuditLogPort -> DatabaseAuditLogAdapter`
- Banyak application service masih bergantung pada `AuditLogPort`.
- Sebagian use case sudah bergantung pada `AuditEventWriterPort`.
- Ada adapter dan concern yang menulis `audit_events` langsung.
- Admin audit reader/mapper membaca dan menormalkan data dari `audit_logs` dan `audit_events`.

## OWNER PROOF
- Owner command `rg -n "audit_logs|audit_events|audit_outbox|AuditLogPort|AuditEventWriterPort" app database/migrations` membuktikan:
  - `audit_logs` table exists
  - `audit_events` table exists
  - `audit_outbox` table exists
  - `InfrastructureServiceProvider` binds `AuditEventWriterPort -> DatabaseAuditOutboxWriterAdapter`
  - `InfrastructureServiceProvider` binds `AuditLogPort -> DatabaseAuditLogAdapter`
  - banyak `Application` service masih memakai `AuditLogPort`
  - sebagian `UseCase` memakai `AuditEventWriterPort`
  - ada adapter yang insert `audit_events` langsung
  - admin audit reader/mapper membaca `audit_logs` dan `audit_events`

## SOURCE EVIDENCE
- `database/migrations/2026_03_10_000300_create_audit_logs_table.php:11-16` membuat `audit_logs` dengan `event`, `context`, dan `created_at`.
- `database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php:13-31` membuat `audit_events` dengan field struktural seperti `bounded_context`, `aggregate_type`, `aggregate_id`, `event_name`, `actor_id`, `actor_role`, `reason`, `source_channel`, `request_id`, `correlation_id`, `occurred_at`, `metadata_json`.
- `database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php:33-47` membuat `audit_event_snapshots` dengan `audit_event_id`, `snapshot_kind`, `payload_json`, dan foreign key ke `audit_events`.
- `database/migrations/2026_05_23_010000_create_audit_outbox_table.php:13-52` membuat `audit_outbox` sebagai tabel delivery/outbox yang menyalin field audit event plus delivery state seperti `status`, `attempts`, `last_error`, `available_at`, `locked_at`, `processed_at`.
- `app/Providers/InfrastructureServiceProvider.php:39-51` membuktikan binding aktif:
  - singleton `AuditEventWriterPort` ke `DatabaseAuditOutboxWriterAdapter`
  - singleton `AuditLogPort` ke `DatabaseAuditLogAdapter`
  - conditional `AuditEventWriterPort` ke `DatabaseAuditEventWriterAdapter` untuk `CreateNoteRevisionSurplusRefundDueHandler` dan `RecordNoteRevisionSurplusRefundPaymentHandler`
- `app/Adapters/Out/Audit/DatabaseAuditLogAdapter.php:15-20` menulis ke `audit_logs` dengan `event` dan `context`.
- `app/Adapters/Out/Audit/DatabaseAuditEventWriterAdapter.php:19-51` menulis ke `audit_events` dan `audit_event_snapshots` secara langsung.
- `app/Adapters/Out/Audit/DatabaseAuditOutboxWriterAdapter.php:24-52` menulis ke `audit_outbox` sebagai pending event delivery.
- `app/Adapters/Out/Audit/AuditLogAdminListQuery.php:25-46` membaca dari dua sumber, `audit_logs` dan `audit_events`.
- `app/Adapters/Out/Audit/AuditLogAdminListQuery.php:52-70` menggabungkan hasil legacy dan event rows lalu mengurutkannya.
- `app/Adapters/Out/Audit/AuditLogAdminRowMapper.php:27-45` memetakan legacy row dari `audit_logs`.
- `app/Adapters/Out/Audit/AuditLogAdminRowMapper.php:50-68` memetakan event row dari `audit_events` menjadi bentuk admin yang seragam.
- `app/Adapters/Out/Audit/DatabaseAuditLogReaderAdapter.php:24-65` membaca `audit_logs` untuk note correction history dan memakai `AuditLogAdminListQuery` / `AuditLogAdminRowMapper` untuk admin list.
- `app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php:16-24, 44-57` memakai `AuditEventWriterPort`.
- `app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php:17-26, 60-74` memakai `AuditEventWriterPort`.
- `app/Application/Expense/UseCases/UpdateExpenseCategoryHandler.php:19-25, 53-75` memakai `AuditEventWriterPort` dan menulis snapshot before/after.
- `app/Adapters/Out/Procurement/Concerns/PersistsVersionedSupplierInvoiceWrites.php:20-27, 40-61` menulis langsung ke `audit_events` dan `audit_event_snapshots`.
- `app/Adapters/Out/EmployeeFinance/Concerns/PersistsVersionedEmployeeWrites.php:21-30, 45-58` menulis langsung ke `audit_events` dan `audit_event_snapshots`.
- `app/Adapters/Out/ProductCatalog/Concerns/RecordsProductHistory.php:50-67` menulis langsung ke `audit_events`.
- `app/Adapters/Out/Procurement/DatabaseSupplierInvoiceVoidWriterAdapter.php:54-71` menulis ke `audit_logs` secara conditional bila tabel ada.
- Owner proof juga menyebut banyak application service masih memakai `AuditLogPort`, misalnya pada identity access, procurement, payment, inventory, note, dan employee finance path.

## FINDINGS
- CONFIRMED: ada dual active audit write path.
  - Legacy path tetap aktif lewat `AuditLogPort -> DatabaseAuditLogAdapter -> audit_logs`.
  - Structured path aktif lewat `AuditEventWriterPort` dengan default outbox binding dan beberapa use case/adapters yang menulis `audit_events` langsung.
- CONFIRMED: legacy `audit_logs` schema lebih sederhana dan kurang terstruktur dibanding `audit_events`.
  - `audit_logs` hanya menyimpan `event`, `context`, dan `created_at`.
  - `audit_events` menyimpan bounded context, aggregate identity, actor, reason, source channel, request/correlation id, occurred_at, metadata, plus snapshots di tabel terpisah.
- CONFIRMED: `audit_outbox` ada dan menjadi binding default untuk `AuditEventWriterPort`.
- CONFIRMED: admin audit reader/mapper memang membaca dua sumber, `audit_logs` dan `audit_events`, lalu menormalkannya untuk list UI.
- GAP: lifecycle coverage matrix belum complete.
  - Belum semua call site `AuditLogPort::record`, `AuditEventWriterPort::write`, dan direct `DB::table('audit_events')` dipetakan sebagai satu matriks coverage yang final.
- GAP: payload consistency actor/target/before/after/reason belum fully proven untuk seluruh lifecycle.
  - Ada source yang jelas membawa `reason`, `actor_id`, `actor_role`, `before/after`, dan metadata.
  - Tetapi belum ada per-lifecycle comparison matrix yang membuktikan format/payload itu konsisten untuk note payment, refund, revision, procurement, inventory, expense, dan employee finance.

## IMPACT
- Karena dua jalur audit aktif bersamaan, admin audit UI harus menormalisasi dua skema sumber data dengan karakter payload yang berbeda.
- Legacy `audit_logs` masih berguna untuk history tertentu, tetapi tidak membawa struktur domain sekomprehensif `audit_events`.
- Structured `audit_events` sudah dipakai untuk event yang butuh actor, reason, aggregate, dan snapshots, jadi ia bergerak ke arah canonical audit spine.
- Tanpa coverage matrix penuh, ada risiko sebagian lifecycle masih menulis audit berbeda-beda sehingga konsistensi actor, target, before, after, dan reason tidak bisa dianggap selesai.
- Dalam audit/traceability, dual path itu sah sebagai transitional state, tetapi tidak boleh dibaca sebagai final canonicalization tanpa proof tambahan.

## GAP
- Belum ada matrix lengkap seluruh pemanggil `AuditLogPort::record`.
- Belum ada matrix lengkap seluruh pemanggil `AuditEventWriterPort::write`.
- Belum ada matrix lengkap semua direct `DB::table('audit_events')->insert(...)`.
- Belum ada sample payload comparison lintas lifecycle untuk:
  - note payment
  - refund
  - revision
  - procurement
  - inventory
  - expense
  - employee finance
- Belum ada proof end-to-end bahwa admin audit UI source column handling dan normalisasi context sudah cocok untuk semua bentuk payload yang masuk.

## CLASSIFICATION
- CONFIRMED
  - dual active audit write path
  - legacy `audit_logs` schema kurang terstruktur dari `audit_events`
  - `audit_outbox` exists and is bound for `AuditEventWriterPort`
  - admin reader/mapper membaca `audit_logs` dan `audit_events`
- GAP
  - lifecycle coverage matrix belum complete
  - payload consistency actor/target/before/after/reason belum fully proven
- TRANSITIONAL
  - direct `audit_events` insert oleh adapter/use case menunjukkan structured path aktif, tetapi belum ada pembuktian bahwa semua lifecycle sudah pindah ke pola yang seragam.

## SOLUTION DIRECTION, NO IMPLEMENTATION
- Tetapkan satu canonical audit contract per lifecycle sebelum menghapus jalur lama.
- Buat matrix coverage untuk semua audit write site:
  - legacy `AuditLogPort`
  - structured `AuditEventWriterPort`
  - direct `audit_events` insert
  - conditional legacy inserts yang masih fallback
- Standarkan payload minimal:
  - actor
  - target/aggregate
  - reason
  - before
  - after
  - occurred_at
  - request/correlation id bila relevan
- Pertahankan admin UI normalizer sampai seluruh source writer sudah seragam.
- Setelah matriks lengkap, baru tentukan apakah `audit_logs` tetap legacy-only, digantikan, atau dijadikan compatibility read path semata.

## SUGGESTED NEXT PROOF
- Matrix semua call to:
  - `AuditLogPort::record`
  - `AuditEventWriterPort::write`
  - `DB::table('audit_events')->insert`
- Sample payload comparison untuk:
  - note payment
  - refund
  - revision
  - procurement
  - inventory
  - expense
  - employee finance
- Verify admin audit UI source column handling dan row mapping untuk `audit_logs` vs `audit_events`.
- Jika perlu, jalankan audit payload diff untuk melihat mana yang masih kurang actor, target, before, after, atau reason.

## MINIMUM OWNER COMMANDS
```bash
rg -n "audit_logs|audit_events|audit_outbox|AuditLogPort|AuditEventWriterPort" app database/migrations
```

## FINAL STATUS
- Status: CONFIRMED dual write path with GAP
- Verdict: jalur audit legacy dan structured sama-sama aktif; `audit_outbox` adalah binding default untuk event writer, tetapi coverage matrix dan payload consistency lintas lifecycle belum lengkap.
- Owner-facing summary: audit writer sudah terbelah antara legacy `audit_logs` dan structured `audit_events/audit_outbox`, admin audit UI sudah membaca keduanya, namun laporan ini belum bisa menutup kualitas payload dan coverage seluruh lifecycle tanpa matrix proof tambahan.
