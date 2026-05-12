# Handoff AI_RULES Modular

Historical Notice:
This file is a historical handoff from the original AI_RULES modularization. Paths that mention root AI_RULES reflect the state at that time. The current canonical standards root is docs/01_standards unless newer proof says otherwise.



## Metadata

- Tanggal: 2026-03-26

- Nama slice / topik: AI_RULES modular constitution for GPT working contract

- Workflow step: Final handoff

- Status: Selesai

- Progres: 100%



## Target halaman kerja

Menyusun paket `AI_RULES/` modular agar GPT lain bisa mengikuti cara kerja project secara konsisten, berbasis fakta, blueprint, step-by-step, proof, domain contract, dan delivery rules.



## Referensi yang dipakai

- Blueprint: struktur modular AI_RULES yang sudah dikunci selama percakapan ini

- Workflow: build -> harden -> bootstrap -> audit -> handoff

- DoD: file structure lengkap, core/workflow/architecture/domain/stack/output rules terbentuk, bootstrap tersedia, audit helper lolos

- ADR: keputusan domain kasir yang sudah dikunci sebelumnya

- Handoff sebelumnya: tidak ada; ini adalah handoff final paket AI_RULES

- Snapshot repo / output command:

  - `find AI_RULES -maxdepth 3 -type f | sort`

  - `bash scripts/audit_ai_rules.sh`



## Fakta terkunci

- Paket `AI_RULES/` sudah memiliki entrypoint, decision policy, core rules, workflow rules, output rules, architecture rules, domain kasir rules, stack rules, bootstrap prompt, session start protocol, handoff template, final review checklist, dan changelog.

- `scripts/audit_ai_rules.sh` berhasil dijalankan sampai `== audit complete ==`.

- Semua required files pada audit helper terdeteksi `[OK]`.

- Keyword check utama untuk entrypoint, decision policy, blueprint gate, one active step, payment lifecycle, markdown output contract, dan handoff next step semuanya lolos.

- Paket AI_RULES siap dipakai secara operasional oleh GPT lain.



## Scope yang dipakai

### SCOPE-IN

- Struktur modular AI_RULES

- Hardening rule P0, P1, P2

- Bootstrap operasional GPT lain

- Session start protocol

- Handoff template

- Final review checklist

- Audit helper script



### SCOPE-OUT

- Integrasi CI

- Generator otomatis handoff

- Skill khusus

- Perubahan domain project di luar constitution AI_RULES



## GAP

- Belum ada integrasi CI untuk memaksa audit AI_RULES di pipeline.

- Belum ada README project-level yang menunjuk AI_RULES sebagai constitution resmi.

- Belum ada prompt injector otomatis untuk GPT lain; bootstrap masih dibaca manual dari file.



## Keputusan yang dikunci

- `AI_RULES/` dijadikan constitution modular resmi untuk menyalin pola kerja user ke GPT lain.

- `01_DECISION_POLICY.md` menjadi conflict protocol utama.

- Rule inti disusun dalam prioritas P0 / P1 / P2.

- GPT lain wajib mulai dari blueprint, satu step aktif, proof-based progress, dan no assumption.

- Domain kasir final, payment lifecycle, reporting boundary, dan delivery rules sudah dikunci dalam modul terpisah.

- Paket ditutup dengan bootstrap prompt, session protocol, handoff template, final review checklist, dan audit helper.



## File yang dibuat/diubah

### File baru

- `AI_RULES/00_INDEX.md`

- `AI_RULES/01_DECISION_POLICY.md`

- `AI_RULES/02_GPT_BOOTSTRAP_PROMPT.md`

- `AI_RULES/03_SESSION_START_PROTOCOL.md`

- `AI_RULES/04_HANDOFF_TEMPLATE.md`

- `AI_RULES/05_FINAL_REVIEW_CHECKLIST.md`

- `AI_RULES/10_CORE/10_SCOPE_AND_FACTS.md`

- `AI_RULES/10_CORE/11_BLUEPRINT_FIRST.md`

- `AI_RULES/10_CORE/12_STEP_BY_STEP_EXECUTION.md`

- `AI_RULES/10_CORE/13_PROOF_AND_PROGRESS.md`

- `AI_RULES/20_WORKFLOW/20_RESPONSE_STRUCTURE.md`

- `AI_RULES/20_WORKFLOW/21_ACTIVE_STEP_POLICY.md`

- `AI_RULES/20_WORKFLOW/22_OPTION_EVALUATION.md`

- `AI_RULES/20_WORKFLOW/23_HANDOFF_POLICY.md`

- `AI_RULES/30_OUTPUT/30_FILE_DELIVERY.md`

- `AI_RULES/30_OUTPUT/31_MARKDOWN_OUTPUT_RULE.md`

- `AI_RULES/30_OUTPUT/32_BLADE_RULE.md`

- `AI_RULES/30_OUTPUT/33_TERMINAL_COMMAND_DELIVERY.md`

- `AI_RULES/40_ARCHITECTURE/40_HEXAGONAL_BASELINE.md`

- `AI_RULES/40_ARCHITECTURE/41_PUBLIC_CONTRACTS.md`

- `AI_RULES/40_ARCHITECTURE/42_ERROR_HANDLING_AND_REDACTION.md`

- `AI_RULES/40_ARCHITECTURE/43_DEBUG_GATING.md`

- `AI_RULES/40_ARCHITECTURE/44_AUDIT_AND_DOD.md`

- `AI_RULES/50_DOMAIN_KASIR/50_FINAL_DOMAIN_MAP.md`

- `AI_RULES/50_DOMAIN_KASIR/51_UI_TERMS_AND_STATUS.md`

- `AI_RULES/50_DOMAIN_KASIR/52_PAYMENT_LIFECYCLE.md`

- `AI_RULES/50_DOMAIN_KASIR/53_REPORTING_BOUNDARY.md`

- `AI_RULES/60_STACK/60_LARAVEL_RULES.md`

- `AI_RULES/60_STACK/61_GO_RULES.md`

- `AI_RULES/60_STACK/62_AWS_BASELINE.md`

- `AI_RULES/99_CHANGELOG.md`

- `AI_RULES/HANDOFF_AI_RULES_MODULAR_2026_03_26.md`

- `scripts/audit_ai_rules.sh`



### File diubah

- `AI_RULES/00_INDEX.md`

- `AI_RULES/99_CHANGELOG.md`



## Bukti verifikasi

- command:

  - `find AI_RULES -maxdepth 3 -type f | sort`

  - hasil: seluruh file modular AI_RULES tampil lengkap

  - arti: struktur constitution berhasil terbentuk

- command:

  - `bash scripts/audit_ai_rules.sh`

  - hasil: semua required files `[OK]` dan audit berakhir di `== audit complete ==`

  - arti: paket AI_RULES lengkap dan lolos pemeriksaan dasar

- command:

  - `grep` checks di dalam `scripts/audit_ai_rules.sh`

  - hasil: keyword kunci pada entrypoint, decision policy, blueprint gate, one active step, payment lifecycle, markdown contract, dan handoff next step semuanya ditemukan

  - arti: isi rule inti masih konsisten dengan tujuan constitution



## Risiko / catatan lanjutan

- Jika nanti ada perubahan domain/arsitektur besar, AI_RULES harus ikut diperbarui dan changelog ditambah.

- Jika ingin enforcement lebih keras, langkah berikut yang paling masuk akal adalah menghubungkan audit script ini ke CI atau pre-merge checklist.

- GPT lain tetap perlu membaca `00_INDEX.md` dan bootstrap files; constitution ini belum auto-injected.



## Next step

Step aktif berikutnya yang paling sah di luar slice ini adalah memilih salah satu:

1. menghubungkan `scripts/audit_ai_rules.sh` ke CI / make target / pre-merge check

2. menambahkan referensi `AI_RULES/` ke README project utama

3. memakai `AI_RULES/02_GPT_BOOTSTRAP_PROMPT.md` sebagai bootstrap saat membuka sesi GPT kerja baru



