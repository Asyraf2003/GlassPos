# Handoff

Folder ini menyimpan session recovery notes untuk sesi aktif atau terbaru.

## Aturan

- Satu file per sesi atau per topik sesi.
- Naming: `YYYY-MM-DD-topic-handoff.md`
- Setelah sesi selesai dan tidak relevan lagi, pindah ke `docs/99_archive/handoff/`.
- Jangan simpan keputusan permanen hanya di sini — promote ke `docs/02_architecture/adr`.
- Jangan simpan blueprint aktif di sini — promote ke `docs/03_blueprints`.
- Canonical handoff template: `docs/01_standards/04-handoff-template.md`

## Source of Truth Priority

1. Output lokal dari operator
2. `docs/01_standards`
3. `docs/02_architecture/adr`
4. Blueprint aktif di `docs/03_blueprints`
5. Handoff terbaru di folder ini
6. Archive di `docs/99_archive/handoff`

## Archive

Semua handoff lama ada di `docs/99_archive/handoff/`:

- `step-based/` — handoff step 02 s/d 12 (era v1)
- `ui/` — UI session handoffs
- `v2/` — feature continuation session handoffs
- `kotlin/` — Kotlin Android handoffs
- `mobile-api/` — Mobile API handoffs
- `seeder/` — Seeder handoffs
- `error-log/` — Error log remediation handoffs
- `codex-security/` — Security audit handoffs
