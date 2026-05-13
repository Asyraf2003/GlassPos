# AI_RULES Index

## Status
Dokumen ini adalah entrypoint wajib untuk setiap GPT/AI assistant yang akan bekerja pada project ini.

AI_RULES adalah nama paket aturan kerja AI untuk repo ini. Lokasi canonical paket standards saat ini adalah docs/01-standards.

## Tujuan
AI_RULES mengunci cara kerja AI agar:
- tidak berasumsi
- tidak keluar dari blueprint
- tidak melompati step aktif
- tidak mengarang fakta, status repo, hasil test, atau keputusan
- tetap patuh pada contract domain dan architecture project

## Mandatory Read Order
Setiap GPT wajib membaca urutan ini sebelum memberi arahan kerja:

1. 01-decision-policy.md
2. 02-gpt-bootstrap-prompt.md
3. 03-session-start-protocol.md
4. core/10-scope-and-facts.md
5. core/11-blueprint-first.md
6. core/12-step-by-step-execution.md
7. core/13-proof-and-progress.md
8. workflow/20-response-structure.md
9. workflow/21-active-step-policy.md
10. architecture/
11. domain/
12. stack/
13. output/
14. workflow/24-session-capacity-policy.md
15. 04-handoff-template.md
16. 05-final-review-checklist.md
17. 99-changelog.md

## Constitution Summary
- Jangan berasumsi.
- Semua arahan harus berbasis fakta, kondisi saat ini, tujuan step, dan bukti.
- Mulai dari blueprint.
- Setelah blueprint, susun workflow step-by-step.
- Satu respons kerja hanya boleh punya satu step aktif.
- Setelah satu step aktif selesai, tunggu feedback user.
- Setiap respons kerja teknis wajib menutup dengan status kapasitas sesi.
- Progres hanya boleh naik jika ada proof nyata.
- Jangan buka ulang keputusan final domain tanpa konflik nyata dan bukti kuat.

## Priority Model
- P0 = rule inti, tidak boleh dilanggar tanpa keputusan eksplisit
- P1 = workflow enforcement dan architecture alignment
- P2 = delivery format dan output preference

## Operational Bootstrap for GPT
Sebelum menjawab, GPT wajib memastikan:
1. apa fakta yang benar-benar ada
2. apa tujuan step saat ini
3. apa scope in dan scope out
4. rule P0 apa yang mengikat
5. apakah data cukup untuk melanjutkan
6. bila data tidak cukup, berhenti di GAP
7. apakah kapasitas sesi masih aman untuk implementasi besar

## Module Map
- 01-decision-policy.md
- 02-gpt-bootstrap-prompt.md
- 03-session-start-protocol.md
- 04-handoff-template.md
- 05-final-review-checklist.md
- core/
  - 10-scope-and-facts.md
  - 11-blueprint-first.md
  - 12-step-by-step-execution.md
  - 13-proof-and-progress.md
- workflow/
  - 20-response-structure.md
  - 21-active-step-policy.md
  - 22-option-evaluation.md
  - 23-handoff-policy.md
  - 24-session-capacity-policy.md
- output/
  - 30-file-delivery.md
  - 31-markdown-output-rule.md
  - 32-blade-rule.md
  - 33-terminal-command-delivery.md
- architecture/
  - 40-hexagonal-baseline.md
  - 41-public-contracts.md
  - 42-error-handling-and-redaction.md
  - 43-debug-gating.md
  - 44-audit-and-dod.md
- domain/
  - 50-final-domain-map.md
  - 51-ui-terms-and-status.md
  - 52-payment-lifecycle.md
  - 53-reporting-boundary.md
- stack/
  - 60-laravel-rules.md
  - 61-go-rules.md
  - 62-aws-baseline.md
- 99-changelog.md

## Package Content Classification

`docs/01-standards` berisi canonical AI_RULES standards package saja.

Canonical standards:
- 00-index.md
- 01-decision-policy.md
- 02-gpt-bootstrap-prompt.md
- 03-session-start-protocol.md
- 04-handoff-template.md
- 05-final-review-checklist.md
- ai-usage-guide.md
- core/
- workflow/
- output/
- architecture/
- domain/
- stack/
- 99-changelog.md

DoD, workflow, dan blueprint per topik ada di `docs/03-blueprints/`.
Legacy dan historical ada di `docs/99-archive/`.

## Non-Negotiable Behavior
- Dilarang mengarang fakta.
- Dilarang mengklaim progress tanpa proof.
- Dilarang langsung lompat ke implementasi bila blueprint belum jelas.
- Dilarang menjadikan output formatting lebih penting daripada correctness domain.
- Dilarang menyamakan proposal dengan eksekusi selesai.
- Dilarang melanjutkan implementasi besar jika kapasitas sesi berada di bawah threshold pada workflow/24-session-capacity-policy.md.

## Conflict Reminder
Jika ada konflik, baca 01-decision-policy.md lalu:
1. dahulukan P0
2. dahulukan aturan yang lebih spesifik
3. dahulukan domain jika konflik menyangkut makna bisnis
4. jika data kurang, berhenti di GAP
