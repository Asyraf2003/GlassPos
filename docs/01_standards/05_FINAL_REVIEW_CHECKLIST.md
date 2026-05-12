# Final Review Checklist

## Tujuan
Checklist ini dipakai untuk memeriksa apakah paket AI_RULES di docs/01_standards masih utuh, terbaca, dan konsisten untuk dipakai GPT lain.

## Checklist Struktur
Checklist ini adalah target pemeriksaan. Jangan klaim semua file ada kecuali sudah dibuktikan dengan output find atau command setara.

- docs/01_standards/00_INDEX.md
- docs/01_standards/01_DECISION_POLICY.md
- docs/01_standards/02_GPT_BOOTSTRAP_PROMPT.md
- docs/01_standards/03_SESSION_START_PROTOCOL.md
- docs/01_standards/04_HANDOFF_TEMPLATE.md
- docs/01_standards/10_CORE/
- docs/01_standards/20_WORKFLOW/
- docs/01_standards/30_OUTPUT/
- docs/01_standards/40_ARCHITECTURE/
- docs/01_standards/50_DOMAIN_KASIR/
- docs/01_standards/60_STACK/
- docs/01_standards/99_CHANGELOG.md

## Checklist Isi Minimum
- docs/01_standards/00_INDEX.md memuat mandatory read order
- docs/01_standards/01_DECISION_POLICY.md memuat rule hierarchy dan GAP rule
- docs/01_standards/10_CORE/11_BLUEPRINT_FIRST.md memuat implementation gate
- docs/01_standards/10_CORE/12_STEP_BY_STEP_EXECUTION.md memuat one active step rule
- docs/01_standards/10_CORE/13_PROOF_AND_PROGRESS.md memuat progress hanya naik jika ada proof
- docs/01_standards/50_DOMAIN_KASIR/50_FINAL_DOMAIN_MAP.md memuat final domain map
- docs/01_standards/50_DOMAIN_KASIR/52_PAYMENT_LIFECYCLE.md memuat rule refund vs cancel
- docs/01_standards/30_OUTPUT/31_MARKDOWN_OUTPUT_RULE.md memuat contract markdown
- docs/01_standards/02_GPT_BOOTSTRAP_PROMPT.md memuat start-of-session checklist
- docs/01_standards/04_HANDOFF_TEMPLATE.md memuat section proof dan next step

## Checklist Operasional
- GPT lain bisa membaca docs/01_standards/00_INDEX.md lalu tahu urutan baca
- GPT lain bisa memakai docs/01_standards/02_GPT_BOOTSTRAP_PROMPT.md sebagai bootstrap
- GPT lain bisa membuka sesi dengan docs/01_standards/03_SESSION_START_PROTOCOL.md
- GPT lain bisa menutup slice dengan docs/01_standards/04_HANDOFF_TEMPLATE.md

## Rule Penutupan
Jika checklist di atas terpenuhi berdasarkan proof lokal, paket AI_RULES di docs/01_standards dapat dianggap siap pakai secara operasional.
