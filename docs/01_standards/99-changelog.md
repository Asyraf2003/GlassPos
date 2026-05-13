# AI Standards Changelog

## 2026-05-12 - standards path normalization

- Clarified docs/01-standards as the canonical standards root.
- Aligned bootstrap and usage guide references away from legacy root AI_RULES paths.
- Kept historical handoff content historical instead of rewriting old proof.
- Deferred file and folder rename until backlink audit.

## 2026-04-26 - Session Capacity Policy

- Added `workflow/24-session-capacity-policy.md`.
- Required capacity footer at the end of every technical work response.
- Added below-80% threshold rule to stop large implementation and prepare handoff.
- Clarified that new sessions reset active chat clutter but do not imply perfect 100% capability.
- Updated index, session start protocol, and handoff policy references.

## 2026-03-26
- Membuat struktur awal AI_RULES modular.
- Menambahkan Decision Policy sebagai conflict protocol utama.
- Menambahkan modul core, workflow, output, architecture, domain kasir, dan stack.
- Mengunci domain map, UI terms, payment lifecycle, dan reporting boundary dalam aturan terpisah.

## Aturan update changelog
- Setiap perubahan rule penting harus menambah entri baru.
- Jika perubahan dipicu ADR atau handoff, cantumkan referensinya pada update berikutnya.

## 2026-03-26 - harden entrypoint and core P0
- Menguatkan `00-index.md` menjadi entrypoint enforcement.
- Menguatkan `01-decision-policy.md` dengan mandatory decision sequence, gap rule, forbidden shortcuts, dan stop conditions.
- Menguatkan `10-scope-and-facts.md` dengan classification dan inference rule.
- Menguatkan `11-blueprint-first.md` dengan implementation gate.

## 2026-03-26 - harden execution and workflow
- Menguatkan `12-step-by-step-execution.md` dengan validation gate dan forbidden behavior.
- Menguatkan `13-proof-and-progress.md` dengan accepted proof, progress rule, dan larangan klaim tanpa bukti.
- Menguatkan `20-response-structure.md` sebagai struktur respons kerja default.
- Menguatkan `21-active-step-policy.md` untuk disiplin satu step aktif.
- Menguatkan `22-option-evaluation.md` agar evaluasi opsi selalu kontekstual dan punya plus/minus.
- Menguatkan `23-handoff-policy.md` agar penutupan slice bisa diteruskan GPT lain tanpa asumsi.

## 2026-03-26 - harden architecture domain and stack
- Menguatkan `40-hexagonal-baseline.md` dengan source of truth rule dan forbidden behavior.
- Menguatkan `41-public-contracts.md` dengan change gate untuk contract publik.
- Menguatkan `42-error-handling-and-redaction.md` dengan security principle dan larangan raw leak.
- Menguatkan `43-debug-gating.md` dan `44-audit-and-dod.md`.
- Menguatkan `50-final-domain-map.md`, `51-ui-terms-and-status.md`, `52-payment-lifecycle.md`, dan `53-reporting-boundary.md`.
- Menguatkan stack rules untuk Laravel, Go, dan AWS baseline.

## 2026-03-26 - harden output and delivery
- Menguatkan `30-file-delivery.md` agar delivery file wajib menyebut path exact dan isi final utuh.
- Menguatkan `31-markdown-output-rule.md` agar penulisan file markdown mengikuti contract satu code block dengan outer fence `text`.
- Menguatkan `32-blade-rule.md` agar Blade tetap fokus pada presentasi dan menghindari inline PHP block.
- Menguatkan `33-terminal-command-delivery.md` agar delivery command terminal dibagi batch bila perlu dan selalu punya konteks eksekusi serta verifikasi.

## 2026-03-26 - add bootstrap and handoff support
- Menambahkan `02-gpt-bootstrap-prompt.md` sebagai bootstrap operasional untuk GPT lain.
- Menambahkan `03-session-start-protocol.md` untuk standardisasi pembukaan sesi kerja.
- Menambahkan `04-handoff-template.md` untuk penutupan slice yang bisa diteruskan tanpa asumsi.
- Memperbarui `00-index.md` agar file bootstrap dan handoff masuk ke mandatory read order dan module map.

## 2026-03-26 - add final review support
- Menambahkan `05-final-review-checklist.md` untuk pemeriksaan akhir paket AI_RULES.
- Menambahkan `scripts/audit_ai_rules.sh` sebagai helper audit ringan untuk memeriksa struktur file dan keyword penting.
- Memperbarui `00-index.md` agar final review checklist masuk ke mandatory read order dan module map.
