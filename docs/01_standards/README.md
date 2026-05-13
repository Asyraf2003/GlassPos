# 01-standards

Aturan wajib untuk semua sesi kerja AI di repo ini. Semua file di sini bersifat permanen dan tidak berubah kecuali ada keputusan eksplisit dari owner.

## Isi

| File / Folder | Isi |
|---|---|
| `00-index.md` | Entry point. Mandatory read order dan module map. |
| `01-decision-policy.md` | Hierarki keputusan dan prioritas rule. |
| `02-gpt-bootstrap-prompt.md` | Prompt bootstrap untuk sesi AI baru. |
| `03-session-start-protocol.md` | Protokol pembuka sesi kerja. |
| `04-handoff-template.md` | Template canonical untuk membuat handoff baru. |
| `05-final-review-checklist.md` | Checklist sebelum menutup sesi besar. |
| `ai-usage-guide.md` | Panduan layer mana untuk informasi apa. |
| `99-changelog.md` | Log perubahan paket AI_RULES. |
| `core/` | Prinsip inti: scope, blueprint-first, step-by-step, proof. |
| `workflow/` | Aturan workflow: response, active step, handoff, capacity. |
| `output/` | Aturan format output: file, markdown, blade, terminal. |
| `architecture/` | Aturan arsitektur: hexagonal, contracts, error, debug, audit. |
| `domain/` | Kontrak domain kasir: domain map, UI terms, payment, reporting. |
| `stack/` | Aturan stack: Laravel, Go, AWS. |

## Aturan

Jangan tambahkan file baru ke sini kecuali itu adalah mandatory AI rule yang berlaku untuk semua sesi.
DoD, workflow, dan blueprint per topik ada di `docs/03-blueprints/`.
