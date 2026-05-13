# Session Start Protocol

## Tujuan

Menstandarkan cara GPT memulai sesi kerja baru agar tidak langsung melompat ke asumsi, salah prioritas, atau implementasi di luar scope user.

Sesi baru harus bisa melanjutkan project dengan aman dari:
- user prompt terbaru
- handoff terakhir
- AI_RULES
- blueprint / ADR / docs yang disebut user
- command output user
- current repo state yang terbukti

## Mandatory Opening Flow

Pada awal sesi kerja teknis, GPT harus:

1. Baca AI_RULES entrypoint dan decision policy.
2. Identifikasi user active scope dari prompt terbaru.
3. Identifikasi dokumen yang user minta dibaca.
4. Bedakan dokumen active scope dari dokumen constraint.
5. Identifikasi fakta yang tersedia.
6. Identifikasi tujuan user.
7. Identifikasi scope in dan scope out.
8. Petakan rules P0/P1 yang mengikat.
9. Baca blueprint/ADR/handoff yang relevan sebelum memberi arahan.
10. Susun blueprint singkat hanya untuk active scope.
11. Nyatakan satu active step.
12. Sebut proof yang tersedia atau proof minimum yang dibutuhkan.
13. Tutup dengan progress dan session context health jika ini kerja project.

## Active Scope Derivation

User prompt adalah sumber utama untuk active scope setelah AI_RULES.

GPT wajib mengikuti urutan derivasi berikut:

1. Explicit active step dari user.
2. Blueprint / ADR / error log yang disebut user.
3. Handoff terakhir yang user berikan atau yang tersedia di konteks.
4. Repo/source state yang terbukti.
5. Rekomendasi prioritas dari blueprint.

GPT tidak boleh memilih cluster lain hanya karena:
- lebih mudah
- lebih kecil
- lebih isolated
- terlihat high severity
- muncul di audit matrix global
- lebih nyaman untuk model

Jika user meminta finance residual, jangan mulai dari seeder.
Jika user meminta access boundary, jangan mulai dari XSS.
Jika user meminta security blueprint sebagai boundary, jangan otomatis menjadikannya implementation scope.
Jika ada alasan kuat untuk mengubah prioritas, minta owner decision dulu.

## Document Role Classification

Setelah membaca dokumen, GPT wajib mengklasifikasikan peran dokumen:

- ACTIVE: dokumen yang menentukan step implementasi sekarang.
- CONSTRAINT: dokumen yang membatasi patch agar tidak melanggar keputusan lain.
- REFERENCE: dokumen pendukung untuk konteks.
- DEFERRED: dokumen valid tapi bukan bagian active step.

Contoh:

Jika user meminta:
- finance residual blueprint
- blueprint/security/
- ADR-0022
- ADR-0023
- current projection ADR
- carry-forward ADR

Maka default klasifikasi:

- ACTIVE: finance residual blueprint.
- CONSTRAINT: ADR-0022 jika payment/concurrency tersentuh.
- CONSTRAINT: current projection ADR dan carry-forward ADR untuk settlement/revision behavior.
- REFERENCE/DEFERRED: seeder credential ADR kecuali user membuka error log 002.
- REFERENCE/DEFERRED: public surface/security docs kecuali active slice menyentuh output/storage/URL.

## Implementation Boundary

Untuk project ini, default implementasi adalah lokal di mesin user.

Allowed by default:
- membaca repo via connector untuk source/docs/commit
- memberi terminal command lokal copy-paste
- memberi full file content via heredoc
- meminta output test/audit/diff sebagai proof

Forbidden by default:
- membuat branch remote
- mengedit file via remote connector
- commit via remote connector
- push via remote connector
- mengklaim test pass tanpa output user
- mengklaim local working tree clean tanpa output user

Remote write hanya boleh jika user eksplisit meminta.

## Jika Konteks Belum Cukup

Jika konteks belum cukup:

- Tandai GAP secara eksplisit.
- Jangan berpura-pura konteks sudah cukup.
- Jangan menulis implementasi spekulatif.
- Minta satu proof minimum, bukan dump besar.
- Proof minimum boleh berupa `git status -sb`, `git rev-parse --short HEAD`, targeted grep, atau targeted test output.

## Jika User Meminta Lanjut

Jika user meminta lanjut:

- lanjut hanya ke step berikut yang sah menurut workflow
- jangan membuka dua step aktif sekaligus
- jangan melewati validation gate
- jangan menukar cluster tanpa owner decision
- jangan update docs/error_log sebelum patch dan test proof

## Source Of Truth Order

Saat ada konflik, gunakan urutan ini:

1. AI_RULES P0.
2. User explicit instruction / owner decision.
3. Command output user.
4. Current source code inspected from repo/local proof.
5. ADR accepted / blueprint active.
6. Handoff latest.
7. Docs/error_log status.
8. Assistant recommendation.

Status naratif di `docs/04-lifecycle/error-log/*.md` tidak boleh mengalahkan source code atau command output.

## Wrong-Scope Recovery

Jika GPT memilih step di luar active scope:

1. Stop immediately.
2. Tulis bahwa active step salah scope.
3. Nyatakan scope yang benar.
4. Jangan lanjut patch yang salah.
5. Jika sudah memberi command salah dan belum dijalankan user, minta abaikan command itu.
6. Jika file lokal terlanjur dibuat dari command salah, berikan cleanup command.
7. Reset progress active implementation ke nilai sebelum kesalahan.
8. Lanjut hanya dengan command sesuai scope benar.

## Session Capacity Baseline

At the start of a new technical work session, GPT must initialize an operational capacity estimate.

A new page does not mean perfect 100% capability. Use the latest handoff, active repo facts, and current task complexity to estimate:

~~~text
Kapasitas sesi:
- Kemampuan menalar: xx%
- Jendela konteks: xx%
- Kemampuan sisa: xx%
- Status: aman / mulai rawan / ganti halaman baru

For a clean new page with a reliable handoff, the usual starting range is:

Kapasitas sesi:
- Kemampuan menalar: 92-95%
- Jendela konteks: 95-98%
- Kemampuan sisa: 92-95%
- Status: aman

These are operational risk estimates, not exact internal telemetry.

Minimal Session Reminder

GPT harus ingat:

user prompt defines active scope
blueprint dulu
satu step aktif
proof-based progress
no assumption
remote read is allowed
local command implementation is default
no remote write unless explicitly requested
