# Final Review Checklist

## Tujuan
Checklist ini dipakai untuk memeriksa apakah paket AI_RULES di docs/01_standards masih utuh, terbaca, dan konsisten untuk dipakai GPT lain.

## Checklist Struktur
Checklist ini adalah target pemeriksaan. Jangan klaim semua file ada kecuali sudah dibuktikan dengan output find atau command setara.

- docs/01_standards/00-index.md
- docs/01_standards/01-decision-policy.md
- docs/01_standards/02-gpt-bootstrap-prompt.md
- docs/01_standards/03-session-start-protocol.md
- docs/01_standards/04-handoff-template.md
- docs/01_standards/core/
- docs/01_standards/workflow/
- docs/01_standards/output/
- docs/01_standards/architecture/
- docs/01_standards/domain/
- docs/01_standards/stack/
- docs/01_standards/99-changelog.md

## Checklist Isi Minimum
- docs/01_standards/00-index.md memuat mandatory read order
- docs/01_standards/01-decision-policy.md memuat rule hierarchy dan GAP rule
- docs/01_standards/core/11-blueprint-first.md memuat implementation gate
- docs/01_standards/core/12-step-by-step-execution.md memuat one active step rule
- docs/01_standards/core/13-proof-and-progress.md memuat progress hanya naik jika ada proof
- docs/01_standards/domain/50-final-domain-map.md memuat final domain map
- docs/01_standards/domain/52-payment-lifecycle.md memuat rule refund vs cancel
- docs/01_standards/output/31-markdown-output-rule.md memuat contract markdown
- docs/01_standards/02-gpt-bootstrap-prompt.md memuat start-of-session checklist
- docs/01_standards/04-handoff-template.md memuat section proof dan next step

## Checklist Operasional
- GPT lain bisa membaca docs/01_standards/00-index.md lalu tahu urutan baca
- GPT lain bisa memakai docs/01_standards/02-gpt-bootstrap-prompt.md sebagai bootstrap
- GPT lain bisa membuka sesi dengan docs/01_standards/03-session-start-protocol.md
- GPT lain bisa menutup slice dengan docs/01_standards/04-handoff-template.md

## Checklist Isi Folder
- Setiap file Markdown punya tepat satu heading H1.
- File canonical standards aktif punya tujuan dan aturan yang jelas.
- File historical diberi status historical atau notice yang jelas.
- File specialized DoD atau legacy reference diberi status yang jelas.
- File specialized DoD tidak boleh dibaca sebagai proof implementasi selesai.
- Tidak ada active stale path menuju legacy standards root atau legacy usage guide location.
- Rename atau move file hanya boleh dilakukan setelah backlink audit dan owner decision.

## Rule Penutupan
Jika checklist di atas terpenuhi berdasarkan proof lokal, paket AI_RULES di docs/01_standards dapat dianggap siap pakai secara operasional.
