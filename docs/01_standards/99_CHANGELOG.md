# AI Standards Changelog

## 2026-05-12 - standards path normalization

- Clarified docs/01_standards as the canonical standards root.
- Aligned bootstrap and usage guide references away from legacy root AI_RULES paths.
- Kept historical handoff content historical instead of rewriting old proof.
- Deferred file and folder rename until backlink audit.

## 2026-04-26 - Session Capacity Policy

- Added `20_WORKFLOW/24_SESSION_CAPACITY_POLICY.md`.
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
- Menguatkan `00_INDEX.md` menjadi entrypoint enforcement.
- Menguatkan `01_DECISION_POLICY.md` dengan mandatory decision sequence, gap rule, forbidden shortcuts, dan stop conditions.
- Menguatkan `10_SCOPE_AND_FACTS.md` dengan classification dan inference rule.
- Menguatkan `11_BLUEPRINT_FIRST.md` dengan implementation gate.

## 2026-03-26 - harden execution and workflow
- Menguatkan `12_STEP_BY_STEP_EXECUTION.md` dengan validation gate dan forbidden behavior.
- Menguatkan `13_PROOF_AND_PROGRESS.md` dengan accepted proof, progress rule, dan larangan klaim tanpa bukti.
- Menguatkan `20_RESPONSE_STRUCTURE.md` sebagai struktur respons kerja default.
- Menguatkan `21_ACTIVE_STEP_POLICY.md` untuk disiplin satu step aktif.
- Menguatkan `22_OPTION_EVALUATION.md` agar evaluasi opsi selalu kontekstual dan punya plus/minus.
- Menguatkan `23_HANDOFF_POLICY.md` agar penutupan slice bisa diteruskan GPT lain tanpa asumsi.

## 2026-03-26 - harden architecture domain and stack
- Menguatkan `40_HEXAGONAL_BASELINE.md` dengan source of truth rule dan forbidden behavior.
- Menguatkan `41_PUBLIC_CONTRACTS.md` dengan change gate untuk contract publik.
- Menguatkan `42_ERROR_HANDLING_AND_REDACTION.md` dengan security principle dan larangan raw leak.
- Menguatkan `43_DEBUG_GATING.md` dan `44_AUDIT_AND_DOD.md`.
- Menguatkan `50_FINAL_DOMAIN_MAP.md`, `51_UI_TERMS_AND_STATUS.md`, `52_PAYMENT_LIFECYCLE.md`, dan `53_REPORTING_BOUNDARY.md`.
- Menguatkan stack rules untuk Laravel, Go, dan AWS baseline.

## 2026-03-26 - harden output and delivery
- Menguatkan `30_FILE_DELIVERY.md` agar delivery file wajib menyebut path exact dan isi final utuh.
- Menguatkan `31_MARKDOWN_OUTPUT_RULE.md` agar penulisan file markdown mengikuti contract satu code block dengan outer fence `text`.
- Menguatkan `32_BLADE_RULE.md` agar Blade tetap fokus pada presentasi dan menghindari inline PHP block.
- Menguatkan `33_TERMINAL_COMMAND_DELIVERY.md` agar delivery command terminal dibagi batch bila perlu dan selalu punya konteks eksekusi serta verifikasi.

## 2026-03-26 - add bootstrap and handoff support
- Menambahkan `02_GPT_BOOTSTRAP_PROMPT.md` sebagai bootstrap operasional untuk GPT lain.
- Menambahkan `03_SESSION_START_PROTOCOL.md` untuk standardisasi pembukaan sesi kerja.
- Menambahkan `04_HANDOFF_TEMPLATE.md` untuk penutupan slice yang bisa diteruskan tanpa asumsi.
- Memperbarui `00_INDEX.md` agar file bootstrap dan handoff masuk ke mandatory read order dan module map.

## 2026-03-26 - add final review support
- Menambahkan `05_FINAL_REVIEW_CHECKLIST.md` untuk pemeriksaan akhir paket AI_RULES.
- Menambahkan `scripts/audit_ai_rules.sh` sebagai helper audit ringan untuk memeriksa struktur file dan keyword penting.
- Memperbarui `00_INDEX.md` agar final review checklist masuk ke mandatory read order dan module map.
