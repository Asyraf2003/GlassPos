# AI_RULES Index

## Status
Dokumen ini adalah entrypoint wajib untuk setiap GPT/AI assistant yang akan bekerja pada project ini.

AI_RULES adalah nama paket aturan kerja AI untuk repo ini. Lokasi canonical paket standards saat ini adalah docs/01_standards.

## Tujuan
AI_RULES mengunci cara kerja AI agar:
- tidak berasumsi
- tidak keluar dari blueprint
- tidak melompati step aktif
- tidak mengarang fakta, status repo, hasil test, atau keputusan
- tetap patuh pada contract domain dan architecture project

## Mandatory Read Order
Setiap GPT wajib membaca urutan ini sebelum memberi arahan kerja:

1. 01_DECISION_POLICY.md
2. 02_GPT_BOOTSTRAP_PROMPT.md
3. 03_SESSION_START_PROTOCOL.md
4. 10_CORE/10_SCOPE_AND_FACTS.md
5. 10_CORE/11_BLUEPRINT_FIRST.md
6. 10_CORE/12_STEP_BY_STEP_EXECUTION.md
7. 10_CORE/13_PROOF_AND_PROGRESS.md
8. 20_WORKFLOW/20_RESPONSE_STRUCTURE.md
9. 20_WORKFLOW/21_ACTIVE_STEP_POLICY.md
10. 40_ARCHITECTURE/
11. 50_DOMAIN_KASIR/
12. 60_STACK/
13. 30_OUTPUT/
14. 20_WORKFLOW/24_SESSION_CAPACITY_POLICY.md
15. 04_HANDOFF_TEMPLATE.md
16. 05_FINAL_REVIEW_CHECKLIST.md
17. 99_CHANGELOG.md

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
- 01_DECISION_POLICY.md
- 02_GPT_BOOTSTRAP_PROMPT.md
- 03_SESSION_START_PROTOCOL.md
- 04_HANDOFF_TEMPLATE.md
- 05_FINAL_REVIEW_CHECKLIST.md
- 10_CORE/
  - 10_SCOPE_AND_FACTS.md
  - 11_BLUEPRINT_FIRST.md
  - 12_STEP_BY_STEP_EXECUTION.md
  - 13_PROOF_AND_PROGRESS.md
- 20_WORKFLOW/
  - 20_RESPONSE_STRUCTURE.md
  - 21_ACTIVE_STEP_POLICY.md
  - 22_OPTION_EVALUATION.md
  - 23_HANDOFF_POLICY.md
  - 24_SESSION_CAPACITY_POLICY.md
- 30_OUTPUT/
  - 30_FILE_DELIVERY.md
  - 31_MARKDOWN_OUTPUT_RULE.md
  - 32_BLADE_RULE.md
  - 33_TERMINAL_COMMAND_DELIVERY.md
- 40_ARCHITECTURE/
  - 40_HEXAGONAL_BASELINE.md
  - 41_PUBLIC_CONTRACTS.md
  - 42_ERROR_HANDLING_AND_REDACTION.md
  - 43_DEBUG_GATING.md
  - 44_AUDIT_AND_DOD.md
- 50_DOMAIN_KASIR/
  - 50_FINAL_DOMAIN_MAP.md
  - 51_UI_TERMS_AND_STATUS.md
  - 52_PAYMENT_LIFECYCLE.md
  - 53_REPORTING_BOUNDARY.md
- 60_STACK/
  - 60_LARAVEL_RULES.md
  - 61_GO_RULES.md
  - 62_AWS_BASELINE.md
- 99_CHANGELOG.md

## Non-Negotiable Behavior
- Dilarang mengarang fakta.
- Dilarang mengklaim progress tanpa proof.
- Dilarang langsung lompat ke implementasi bila blueprint belum jelas.
- Dilarang menjadikan output formatting lebih penting daripada correctness domain.
- Dilarang menyamakan proposal dengan eksekusi selesai.
- Dilarang melanjutkan implementasi besar jika kapasitas sesi berada di bawah threshold pada 20_WORKFLOW/24_SESSION_CAPACITY_POLICY.md.

## Conflict Reminder
Jika ada konflik, baca 01_DECISION_POLICY.md lalu:
1. dahulukan P0
2. dahulukan aturan yang lebih spesifik
3. dahulukan domain jika konflik menyangkut makna bisnis
4. jika data kurang, berhenti di GAP
