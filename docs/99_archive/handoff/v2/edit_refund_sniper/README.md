# Edit Refund Sniper Handoff

## Purpose

Folder ini adalah entrypoint ringan untuk sesi AI berikutnya yang akan bekerja pada rantai edit, revision, refund, settlement, inventory, reporting, UI, dan future API HyperPOS.

Tujuan folder ini:
- membuat sesi berikutnya makin sniper
- mengurangi pembacaan handoff lama yang tidak perlu
- menjaga AI tetap mulai dari proof lokal dan source audit spesifik
- mencegah patch asal pada rantai refund/edit

Folder ini tidak mengganti ADR, blueprint, workflow, atau DoD.

## Stable Contract Files

- docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
- docs/99_archive/handoff/v2/edit_refund_sniper/PROMPT_TEMPLATE.md
- docs/99_archive/handoff/v2/edit_refund_sniper/HANDOFF_TEMPLATE.md

## Latest Handoff

Baca file terbaru di folder ini setelah AI_RULES:

- docs/99_archive/handoff/v2/edit_refund_sniper/0010_surplus_refund_due_admin_transport_handoff.md

Jika ada handoff baru, tambahkan file bernomor berikutnya:

- 0002_scope_handoff.md
- 0003_scope_handoff.md

Lalu update bagian Latest Handoff.

## Markdown Safety Rule

Untuk handoff, prompt sesi berikutnya, dan file Markdown yang dikirim via chat:

- Jangan gunakan triple backtick.
- Jangan gunakan nested fenced block.
- Jangan gunakan fence Markdown di dalam heredoc.
- Jika isi file perlu command, tulis sebagai indented block biasa.
- Jika output dikirim sebagai command pembuat file, pastikan isi heredoc tidak mengandung fence.
- Sebelum final, grep fence di folder handoff harus kosong.

Proof command:

    Use a scanner that builds fence tokens from character codes instead of storing literal fence tokens in this file.

Expected proof:

    no output

## Filename Rule

Handoff filenames must not include dates.

Use sequence plus scope only:

    0001_verify_baseline_and_next_session_handoff.md
    0002_revision_settlement_source_audit_handoff.md

Put date inside the file metadata, not in the filename.

## Source Priority

Gunakan urutan ini:

1. Local command output dari user
2. Current source code lokal
3. Latest ADR atau blueprint terdekat domain
4. Error log dengan proof
5. Handoff terbaru di folder ini
6. Handoff lama atau archive
7. Memory atau asumsi

Jika docs dan source konflik, source lokal menang sampai docs diperbarui.

Jika source lokal dan command output konflik dengan remote GitHub, command output lokal menang.

## Mandatory First Read

Sesi berikutnya wajib baca minimal:

- docs/01_standards/0001_index.md
- docs/01_standards/0002_decision_policy.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0002_revision_settlement_foundation_handoff.md
- docs/99_archive/handoff/v2/note_finance/0003_note_revision_refund_ledger_ai_reading_map.md
- docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- docs/03_blueprints/finance/0007_note_revision_refund_ledger_dod.md
- docs/03_blueprints/finance/0008_note_revision_refund_ledger_workflow.md
- docs/02_architecture/adr/0018_note_revision_settlement_external_product_lifecycle.md
- docs/02_architecture/adr/0024_note_current_projection_and_current_only_refund.md
- docs/02_architecture/adr/0025_note_revision_carry_forward_settlement.md
- docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md
- docs/02_architecture/adr/0019_note_access_boundary_cashier_date_window_and_transaction_capability_enforcement.md

Do not start from UI.

Do not start from controller.

Do not start from generic query patch.

## Conditional Local Baseline Tools

Baseline git and verify commands are conditional tools, not session-entry requirements.

Owner workflow is accepted as source-of-truth baseline proof when owner states clean, pushed, latest, or make verify pass.

The following must be treated as FACT, not GAP and not ASSUMPTION:

- owner keeps local and repo identical after push, except ignored files
- owner handles commit and push manually
- owner runs make verify at final safe-state closure or when explicitly needed
- owner statement clean, pushed, latest, or make verify pass is accepted as baseline proof

Do not request these as ceremony:

- git status
- git log
- git diff
- git diff --check
- make verify

Use git or verify commands only when there is a real trigger:

- dirty state is suspected
- changed-file inventory is critical to the active slice
- source and docs conflict
- local command output contradicts GitHub or docs
- test failure indicates unexpected local drift
- final closure lacks owner clean, pushed, latest, or make verify pass statement
- owner explicitly asks for verification

If a later issue appears after owner-declared closure, record it as technical debt or next-session blocker unless it contradicts current proof.

## Required Response Shape

For any technical implementation response, AI must include:

- FACT
- GAP
- ASSUMPTION
- DECISION
- ACTIVE STEP
- FILES TO TOUCH
- FILES NOT TO TOUCH
- COMMAND
- EXPECTED PROOF
- NEXT

No production code patch is allowed before the active slice states:

- goal
- decision used
- source proof
- affected files
- files not touched
- DB impact
- hexagonal boundary
- test plan
- rollback or containment plan
- residual gap

## Hard Rules

- No progress claim without proof.
- No fixed claim without RED or source-gap proof, targeted GREEN, and focused blast-radius proof where applicable.
- No ledger/history rewrite to hide mismatch.
- No cascade delete financial history.
- No nullable FK shortcut without immutable snapshot model.
- No UI-only financial truth.
- No JavaScript-only validation for finance.
- No direct report query without explicit mode when touching versioned report behavior.
- No file over 100 lines in app unless justified with valid audit bypass.
- Prefer splitting files cleanly instead of compressing dense logic.
- make verify must pass before claiming final safe state.
- User handles commit and push manually unless explicitly asked.
