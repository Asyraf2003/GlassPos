# Handoff

This folder stores session recovery notes for the active or latest session.

## Rules

- One file per session or per session topic.
- Naming: `NNNN_topic_handoff.md`
- After a session is finished and no longer relevant, move it to `docs/99_archive/handoff/`.
- Do not keep permanent decisions here only - promote them to `docs/02_architecture/adr`.
- Do not keep active blueprints here - promote them to `docs/03_blueprints`.
- Canonical handoff template: `docs/01_standards/0005_handoff_template.md`

## Note

This folder is only for active or latest handoffs. Once a session is closed, archive it to `docs/99_archive/handoff/` so the history remains while the active workflow stays clean.

## Source of Truth Priority

1. Latest local operator output
2. `docs/01_standards`
3. `docs/02_architecture/adr`
4. Active blueprint in `docs/03_blueprints`
5. Latest handoff in this folder
6. Archive in `docs/99_archive/handoff`

## Active Handoffs

| File | Topic | Status |
|---|---|---|
| `0001_audit_write_path_canonical_pilot_handoff.md` | Audit write path canonical pilot | superseded by audit outbox runtime handoff |
| `0002_audit_outbox_runtime_switch_handoff.md` | Audit outbox runtime switch | continue in next session |
| `0018_service_catalog_lookup_migration_handoff.md` | Service catalog lookup + migration backfill | focused verified |

## Archive

All old handoffs live in `docs/99_archive/handoff/`:

- `step-based/` - handoffs from steps 02 through 12 (v1 era)
- `ui/` - UI session handoffs
- `v2/` - feature continuation session handoffs
- `kotlin/` - Kotlin Android handoffs
- `mobile-api/` - Mobile API handoffs
- `seeder/` - Seeder handoffs
- `error_log/` - Error-log remediation handoffs
- `codex-security/` - Security audit handoffs
