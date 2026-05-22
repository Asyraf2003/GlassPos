# 01_standards

Aturan wajib untuk semua sesi kerja AI di repo ini. Semua file di sini bersifat permanen dan tidak berubah kecuali ada keputusan eksplisit dari owner.

## Isi

| File / Folder | Isi |
|---|---|
| `0001_index.md` | Entry point. Mandatory read order dan module map. |
| `0002_decision_policy.md` | Hierarki keputusan dan prioritas rule. |
| `0003_gpt_bootstrap_prompt.md` | Prompt bootstrap untuk sesi AI baru. |
| `0004_session_start_protocol.md` | Protokol pembuka sesi kerja. |
| `0005_handoff_template.md` | Template canonical untuk membuat handoff baru. |
| `0006_final_review_checklist.md` | Checklist sebelum menutup sesi besar. |
| `0007_ai_usage_guide.md` | Panduan layer mana untuk informasi apa. |
| `0008_ai_personalization_profile.md` | Profil personalisasi AI repo-level dari owner. |
| `0099_changelog.md` | Log perubahan paket AI_RULES. |
| `core/` | Prinsip inti: scope, blueprint-first, step-by-step, proof. |
| `workflow/` | Aturan workflow: response, active step, handoff, capacity. |
| `output/` | Aturan format output: file, markdown, blade, terminal. |
| `architecture/` | Aturan arsitektur: hexagonal, contracts, error, debug, audit. |
| `domain/` | Kontrak domain kasir: domain map, UI terms, payment, reporting. |
| `stack/` | Aturan stack: Laravel, Go, AWS. |

## Aturan

Jangan tambahkan file baru ke sini kecuali itu adalah mandatory AI rule yang berlaku untuk semua sesi atau owner mempromosikannya sebagai standards canonical.
DoD, workflow, dan blueprint per topik ada di `docs/03_blueprints/`.
