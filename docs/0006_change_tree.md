# Central Documentation Map

Peta navigasi seluruh dokumentasi sistem Hyperpos.

## Struktur Folder (L1)

### [01_standards](./01_standards/)

Aturan wajib untuk semua sesi kerja AI. Bersifat statis, tidak berubah kecuali ada keputusan eksplisit.

- `core/` — prinsip dasar: scope, blueprint-first, step-by-step, proof
- `workflow/` — response structure, active step policy, handoff policy, session capacity
- `output/` — file delivery, markdown rule, blade rule, terminal delivery
- `architecture/` — hexagonal baseline, public contracts, error handling, debug gating, audit-dod
- `domain/` — final domain map, UI terms, payment lifecycle, reporting boundary
- `stack/` — Laravel rules, Go rules, AWS baseline

### [02_architecture](./02_architecture/)

Permanent decision records.

- `adr/` — ADR files. Naming: `NNNN-kebab-title.md`. Sequential, tidak menggunakan tanggal.
  Jika keputusan berubah: buat ADR baru yang supersede, tandai ADR lama sebagai superseded.

### [03_blueprints](./03_blueprints/)

Design blueprints, DoD, dan Workflow per topik. Flat dalam subfolder topik.

- `security/` — ADR-0019 s/d ADR-0023: access boundary, public surface, payment concurrency, seeder safety
- `finance/` — note finance stabilization, finance residual, note revision refund ledger
- `reporting/` — report export, reporting execution workflow
- `seeder/` — legacy-to-clean
- `mobile/` — mobile API
- `error-log-remediation/` — DoD, sequence, workflow, strict closure protocol
- `feature-continuation/` — feature continuation blueprint

Naming file: `topic-name.md` (blueprint), `topic-name-dod.md` (DoD), `topic-name-workflow.md` (Workflow).

### [04_lifecycle](./04_lifecycle/)

Runtime records — ongoing, bukan historical.

- `error-log/` — bug dan security findings. Naming: `NNN-kebab-title.md`
- `handoff/` — session recovery notes sesi aktif. Naming: `YYYY-MM-DD-topic-handoff.md`

### [05_audits](./05_audits/)

Formal audit records. Naming: `YYYY-MM-DD-topic.md`.

### [99_archive](./99_archive/)

Semua legacy, superseded, historical. Copy penuh, tidak dimodifikasi.

- `standards/` — old standards (handoff-ai-rules-modular)
- `blueprints/` — blueprint v1, workflow v1
- `dod/` — dod v1
- `handoff/` — semua handoff lama (step-based, ui, v2, kotlin, mobile-api, seeder, error_log, codex-security)

---

## Naming Convention

| Jenis | Format | Contoh |
|---|---|---|
| ADR | `NNNN-kebab-title.md` | `0019-note-access-boundary.md` |
| Blueprint | `topic-name.md` | `finance-residual.md` |
| DoD | `topic-name-dod.md` | `finance-residual-dod.md` |
| Workflow | `topic-name-workflow.md` | `finance-residual-workflow.md` |
| Error log | `NNN-kebab-title.md` | `009-cashiers-can-rewrite.md` |
| Audit record | `YYYY-MM-DD-topic.md` | `2026-05-06-error-log-coverage.md` |
| Handoff aktif | `YYYY-MM-DD-topic-handoff.md` | `2026-05-12-skeleton-handoff.md` |
| Folder | `kebab-case` | `error-log/`, `01_standards/` |

## Log Perubahan Struktur

- **2026-05-13**: Reorganisasi penuh ke standard hexagonal docs. Kebab-case konsisten, topik-based blueprint subfolders, semua legacy ke 99_archive, path references difix, duplikat konten dihilangkan.
- **2026-05-11**: Migrasi awal dari flat-legacy ke hybrid L1.
