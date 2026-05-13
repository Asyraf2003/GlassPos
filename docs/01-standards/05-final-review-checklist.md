# Final Review Checklist

## Tujuan
Checklist ini dipakai untuk memeriksa apakah paket AI_RULES di docs/01-standards masih utuh, terbaca, dan konsisten untuk dipakai GPT lain.

## Checklist Struktur
Checklist ini adalah target pemeriksaan. Jangan klaim semua file ada kecuali sudah dibuktikan dengan output find atau command setara.

- docs/01-standards/00-index.md
- docs/01-standards/01-decision-policy.md
- docs/01-standards/02-gpt-bootstrap-prompt.md
- docs/01-standards/03-session-start-protocol.md
- docs/01-standards/04-handoff-template.md
- docs/01-standards/core/
- docs/01-standards/workflow/
- docs/01-standards/output/
- docs/01-standards/architecture/
- docs/01-standards/domain/
- docs/01-standards/stack/
- docs/01-standards/99-changelog.md

## Checklist Isi Minimum
- docs/01-standards/00-index.md memuat mandatory read order
- docs/01-standards/01-decision-policy.md memuat rule hierarchy dan GAP rule
- docs/01-standards/core/11-blueprint-first.md memuat implementation gate
- docs/01-standards/core/12-step-by-step-execution.md memuat one active step rule
- docs/01-standards/core/13-proof-and-progress.md memuat progress hanya naik jika ada proof
- docs/01-standards/domain/50-final-domain-map.md memuat final domain map
- docs/01-standards/domain/52-payment-lifecycle.md memuat rule refund vs cancel
- docs/01-standards/output/31-markdown-output-rule.md memuat contract markdown
- docs/01-standards/02-gpt-bootstrap-prompt.md memuat start-of-session checklist
- docs/01-standards/04-handoff-template.md memuat section proof dan next step

## Checklist Operasional
- GPT lain bisa membaca docs/01-standards/00-index.md lalu tahu urutan baca
- GPT lain bisa memakai docs/01-standards/02-gpt-bootstrap-prompt.md sebagai bootstrap
- GPT lain bisa membuka sesi dengan docs/01-standards/03-session-start-protocol.md
- GPT lain bisa menutup slice dengan docs/01-standards/04-handoff-template.md

## Checklist Isi Folder
- Setiap file Markdown punya tepat satu heading H1.
- File canonical standards aktif punya tujuan dan aturan yang jelas.
- File historical diberi status historical atau notice yang jelas.
- File specialized DoD atau legacy reference diberi status yang jelas.
- File specialized DoD tidak boleh dibaca sebagai proof implementasi selesai.
- Tidak ada active stale path menuju legacy standards root atau legacy usage guide location.
- Rename atau move file hanya boleh dilakukan setelah backlink audit dan owner decision.

## Rule Penutupan
Jika checklist di atas terpenuhi berdasarkan proof lokal, paket AI_RULES di docs/01-standards dapat dianggap siap pakai secara operasional.
