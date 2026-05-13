# 03_blueprints

Design blueprints, DoD, dan Workflow per topik implementasi.

## Struktur

Setiap subfolder topik berisi tiga jenis file yang berdampingan:

| Suffix | Jenis | Isi |
|---|---|---|
| `NNNN_topic_name.md` | Blueprint | Owner decisions, scope, access model, policy design |
| `NNNN_topic_name_dod.md` | DoD | Kriteria selesai — planning dan implementation |
| `NNNN_topic_name_workflow.md` | Workflow | Test matrix, implementation order, CLI workflow, commands |

## Subfolder

| Folder | Topik |
|---|---|
| `security/` | ADR-0019 access boundary, ADR-0020 public surface, ADR-0022 payment concurrency, ADR-0023 seeder safety |
| `finance/` | Note finance stabilization, finance residual, note revision refund ledger |
| `reporting/` | Report export, reporting execution workflow |
| `seeder/` | Legacy-to-clean seeder migration |
| `mobile/` | Mobile API v1 |
| `error_log_remediation/` | Proses remediasi error log |
| `feature_continuation/` | Feature continuation scope blueprint |
