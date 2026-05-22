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

1. 0002_decision_policy.md
2. 0003_gpt_bootstrap_prompt.md
3. 0004_session_start_protocol.md
4. 0007_ai_usage_guide.md
5. 0008_ai_personalization_profile.md
6. core/0010_scope_and_facts.md
7. core/0011_blueprint_first.md
8. core/0012_step_by_step_execution.md
9. core/0013_proof_and_progress.md
10. workflow/0020_response_structure.md
11. workflow/0021_active_step_policy.md
12. workflow/0024_session_capacity_policy.md
13. architecture/0040_hexagonal_baseline.md
14. architecture/0041_public_contracts.md
15. architecture/0042_error_handling_and_redaction.md
16. architecture/0043_debug_gating.md
17. architecture/0044_audit_and_dod.md
18. domain/0050_final_domain_map.md
19. domain/0051_ui_terms_and_status.md
20. domain/0052_payment_lifecycle.md
21. domain/0053_reporting_boundary.md
22. stack/0060_laravel_rules.md
23. stack/0061_go_rules.md
24. stack/0062_aws_baseline.md
25. output/0030_file_delivery.md
26. output/0031_markdown_output_rule.md
27. output/0032_blade_rule.md
28. output/0033_terminal_command_delivery.md
29. 0005_handoff_template.md
30. 0006_final_review_checklist.md
31. 0099_changelog.md

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
- 0002_decision_policy.md
- 0003_gpt_bootstrap_prompt.md
- 0004_session_start_protocol.md
- 0007_ai_usage_guide.md
- 0008_ai_personalization_profile.md
- 0005_handoff_template.md
- 0006_final_review_checklist.md
- core/
  - 0010_scope_and_facts.md
  - 0011_blueprint_first.md
  - 0012_step_by_step_execution.md
  - 0013_proof_and_progress.md
- workflow/
  - 0020_response_structure.md
  - 0021_active_step_policy.md
  - 0022_option_evaluation.md
  - 0023_handoff_policy.md
  - 0024_session_capacity_policy.md
- output/
  - 0030_file_delivery.md
  - 0031_markdown_output_rule.md
  - 0032_blade_rule.md
  - 0033_terminal_command_delivery.md
- architecture/
  - 0040_hexagonal_baseline.md
  - 0041_public_contracts.md
  - 0042_error_handling_and_redaction.md
  - 0043_debug_gating.md
  - 0044_audit_and_dod.md
- domain/
  - 0050_final_domain_map.md
  - 0051_ui_terms_and_status.md
  - 0052_payment_lifecycle.md
  - 0053_reporting_boundary.md
- stack/
  - 0060_laravel_rules.md
  - 0061_go_rules.md
  - 0062_aws_baseline.md
- 0099_changelog.md

## Package Content Classification

`docs/01_standards` berisi canonical AI_RULES standards package saja.

Canonical standards:
- 0001_index.md
- 0002_decision_policy.md
- 0003_gpt_bootstrap_prompt.md
- 0004_session_start_protocol.md
- 0005_handoff_template.md
- 0006_final_review_checklist.md
- 0007_ai_usage_guide.md
- 0008_ai_personalization_profile.md
- core/
- workflow/
- output/
- architecture/
- domain/
- stack/
- 0099_changelog.md

DoD, workflow, dan blueprint per topik ada di `docs/03_blueprints/`.
Legacy dan historical ada di `docs/99_archive/`.

## Non-Negotiable Behavior
- Dilarang mengarang fakta.
- Dilarang mengklaim progress tanpa proof.
- Dilarang langsung lompat ke implementasi bila blueprint belum jelas.
- Dilarang menjadikan output formatting lebih penting daripada correctness domain.
- Dilarang menyamakan proposal dengan eksekusi selesai.
- Dilarang melanjutkan implementasi besar jika kapasitas sesi berada di bawah threshold pada workflow/24-session-capacity-policy.md.

## Conflict Reminder
Jika ada konflik, baca 0002_decision_policy.md lalu:
1. dahulukan P0
2. dahulukan aturan yang lebih spesifik
3. dahulukan domain jika konflik menyangkut makna bisnis
4. jika data kurang, berhenti di GAP
