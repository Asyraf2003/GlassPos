# 03-blueprints

Design blueprints, DoD, dan Workflow per topik implementasi.

## Struktur

Setiap subfolder topik berisi tiga jenis file yang berdampingan:

| Suffix | Jenis | Isi |
|---|---|---|
| `topic-name.md` | Blueprint | Owner decisions, scope, access model, policy design |
| `topic-name-dod.md` | DoD | Kriteria selesai — planning dan implementation |
| `topic-name-workflow.md` | Workflow | Test matrix, implementation order, CLI workflow, commands |

## Subfolder

| Folder | Topik |
|---|---|
| `security/` | ADR-0019 access boundary, ADR-0020 public surface, ADR-0022 payment concurrency, ADR-0023 seeder safety |
| `finance/` | Note finance stabilization, finance residual, note revision refund ledger |
| `reporting/` | Report export, reporting execution workflow |
| `seeder/` | Legacy-to-clean seeder migration |
| `mobile/` | Mobile API v1 |
| `error-log-remediation/` | Proses remediasi error log |
| `feature-continuation/` | Feature continuation scope blueprint |
