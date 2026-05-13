# Hyperpos Documentation Index

## Purpose

Direktori ini berisi seluruh dokumentasi teknis sistem Hyperpos — aturan kerja AI, keputusan arsitektur, blueprint implementasi, lifecycle records, dan audit.

Tujuan index ini: memudahkan manusia atau AI agent menemukan dokumen yang tepat tanpa harus baca semua.

## Fast Start

Baca dalam urutan ini:

    docs/01_standards/ai-usage-guide.md
    docs/01_standards/00-index.md
    Blueprint aktif untuk scope saat ini
    Output lokal terbaru dari operator

## Source Of Truth Priority

Gunakan urutan ini ketika dokumen saling bertentangan:

1. Output lokal dari operator (tertinggi)
2. `docs/01_standards`
3. `docs/02_architecture/adr`
4. Blueprint aktif di `docs/03_blueprints`
5. Handoff terbaru di `docs/04_lifecycle/handoff`
6. Archive di `docs/99_archive`
7. General model knowledge (terendah)

## Directory Map

### `docs/01_standards`

Aturan wajib untuk semua sesi kerja AI di repo ini.

Gunakan untuk: zero assumption rule, blueprint-first rule, one active step rule,
proof and progress rule, response structure, handoff policy, architecture boundary,
public contract protection, redaction rule, final domain map, stack rules.

Tidak untuk: bug notes, feature status, commit hash, temporary local state.

### `docs/02_architecture/adr`

Permanent decision records. Sequential numbered `NNNN-kebab-title.md`.

Gunakan untuk: keputusan arsitektur, keputusan domain, lifecycle decisions,
reporting boundary, data representation.

Jika keputusan berubah: buat ADR baru yang supersede, jangan edit ADR lama.

### `docs/03_blueprints`

Design blueprints + DoD + Workflow per topik. Diorganisir dalam subfolder:

- `security/` — ADR-0019 s/d ADR-0023 blueprints, dod, workflow
- `finance/` — note finance, residual, revision-refund-ledger
- `reporting/` — report export, reporting execution
- `seeder/` — legacy-to-clean
- `mobile/` — mobile API
- `error-log-remediation/` — error log remediation docs
- `feature-continuation/` — feature continuation blueprint

Naming: `topic-name.md` (blueprint), `topic-name-dod.md` (DoD), `topic-name-workflow.md` (Workflow).

### `docs/04_lifecycle`

Runtime records.

- `error-log/` — individual bug/security findings, numbered `NNN-kebab-title.md`
- `handoff/` — session recovery notes untuk sesi aktif/terbaru

### `docs/05_audits`

Formal audit records dengan date prefix `YYYY-MM-DD-topic.md`.

### `docs/99_archive`

Semua dokumen legacy, superseded, dan historical. Copy penuh, tidak dimodifikasi.

- `standards/` — old standards docs
- `blueprints/` — blueprint v1
- `dod/` — dod v1
- `handoff/` — semua handoff lama (step-based, ui, v2, kotlin, dll)

## Naming Convention

| Jenis | Format | Contoh |
|---|---|---|
| ADR | `NNNN-kebab-title.md` | `0019-note-access-boundary.md` |
| Blueprint | `topic-name.md` | `finance-residual.md` |
| DoD | `topic-name-dod.md` | `finance-residual-dod.md` |
| Workflow | `topic-name-workflow.md` | `finance-residual-workflow.md` |
| Error log | `NNN-kebab-title.md` | `009-cashiers-can-rewrite.md` |
| Audit record | `YYYY-MM-DD-topic.md` | `2026-05-06-error-log-coverage.md` |
| Handoff aktif | `YYYY-MM-DD-topic-handoff.md` | `2026-05-12-kotlin-skeleton-handoff.md` |
| Folder | `kebab-case` | `error-log/`, `01_standards/` |

## Promotion Rule

Jika handoff mengandung keputusan yang harus permanen:

1. Buat atau update ADR.
2. Referensikan handoff sebagai evidence.
3. Tandai handoff sebagai historical.
4. Jangan biarkan keputusan permanen hanya ada di handoff.
