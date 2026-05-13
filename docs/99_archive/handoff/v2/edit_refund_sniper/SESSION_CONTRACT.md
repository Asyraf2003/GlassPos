# Edit Refund Sniper Session Contract

## Metadata

- Scope: edit refund sniper session chain
- Stability: stable contract for this folder
- Date policy: date belongs inside file metadata, not filenames

## Purpose

This file defines the stable rules for every AI session that works from this folder.

The goal is continuity.

Session 2, session 8, and session 24 must follow the same discipline.

Each later session should become more sniper:
- less broad rediscovery
- more specific source audit
- fewer repeated context ceremonies
- stricter proof discipline

## Entry Rule

A new AI session must start from this folder.

Required first reads:

1. docs/01_standards/0001_index.md
2. docs/01_standards/0002_decision_policy.md
3. docs/99_archive/handoff/v2/edit_refund_sniper/README.md
4. docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
5. docs/99_archive/handoff/v2/edit_refund_sniper/PROMPT_TEMPLATE.md
6. docs/99_archive/handoff/v2/edit_refund_sniper/HANDOFF_TEMPLATE.md
7. latest handoff named by README

Do not start with broad repo analysis.

Do not start from UI.

Do not start from controller.

Do not start from generic query patch.

## Source Priority

Use this order:

1. Local command output from user
2. Current source code
3. Latest ADR or blueprint nearest active domain
4. Error log with proof
5. Latest handoff in this folder
6. Older handoff or archive
7. Memory or assumption

If source and docs conflict, source wins until docs are updated.

If user command output conflicts with remote GitHub, user command output wins.

## Baseline Proof Policy

The owner usually commits and pushes every completed action.

Do not ask for routine git status, git log, or git diff at the start of every session as ceremony.

Accepted baseline proof can be:

- pushed commit output from user
- latest HEAD line from user
- explicit statement from user that current local main is already pushed
- relevant command output attached to the current task

Use git status or git diff only when needed.

Git checks are required only if:

- there may be uncommitted local changes
- the next action will edit files and changed-file inventory matters
- a test failure references files that may have changed locally
- source and docs conflict
- exact changed-file inventory is needed
- final closure needs proof and no push or commit output was provided
- user explicitly asks for git verification

Do not ask for git proof as ceremony.

Preserve context for edit/refund source audit.

## Anti Review Loop Policy

Later sessions must not restart broad review just because a new session started.

If the latest handoff states that source audit, targeted proof, focused proof, docs proof, or make verify already passed, the next session must treat that as prior completed work and continue from the documented Next Active Step.

Re-review is allowed only when there is a real trigger:

- user provides new contradictory command output
- tests fail unexpectedly
- source and docs conflict
- the next slice depends on source that was not previously inspected
- the handoff explicitly marks a gap that blocks the next step
- owner requests a review

Owner statements are accepted as source-of-truth baseline proof:

- clean
- pushed
- latest
- local and repo identical except ignored files
- make verify pass

These statements are FACT.

They are not assumptions.

They are not gaps.

Do not ask for git status, git log, git diff, git diff --check, or make verify as a ritual.

If a post-closure issue appears later, record it as technical debt or a next-session blocker unless it contradicts the current proof.

The goal is continuity, not repeatedly charging the owner a context tax because AI has the memory discipline of a caffeinated goldfish.

## Required Response Shape

Every technical implementation response must include:

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

If the answer is only a small clarification, this full shape may be skipped.

If the answer can lead to code or docs change, use the full shape.

## Implementation Gate

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

## Assumption Rule

Assumptions are allowed only when clearly labeled.

Never hide an assumption inside FACT or DECISION.

If an assumption can change implementation direction, stop and ask for the minimum missing proof or owner decision.

## Markdown Safety Rule

Handoff files, prompt templates, and Markdown file content must be fence-safe.

Rules:

- Do not use triple backtick.
- Avoid Markdown fences inside handoff files.
- Prefer indented command blocks.
- Do not put dates in filenames.
- Put date inside file metadata.
- Do not create nested heredoc or nested fence traps.
- Before final handoff closure, scan for literal Markdown fence tokens using a scanner that builds tokens from character codes.

## Filename Rule

Handoff filenames must use sequence plus scope only.

Correct:

    0001_verify_baseline_and_next_session_handoff.md
    0002_revision_settlement_source_audit_handoff.md
    0003_customer_balance_foundation_handoff.md

Wrong:

    0002_2026-05-13_revision_settlement_source_audit_handoff.md

Date belongs inside metadata.

## End Of Session Rule

At the end of any session that changes context, decisions, proof, files, gaps, or next active step, the AI must create a new handoff file in this folder.

The handoff must use HANDOFF_TEMPLATE.md.

After creating a new handoff, update README latest handoff pointer.

If no meaningful state changed, do not create a handoff just for ceremony.

## Stability Rule

PROMPT_TEMPLATE.md and HANDOFF_TEMPLATE.md are stable contracts.

Do not rewrite them casually.

If a session finds a template gap:

1. state the gap
2. explain why current template fails
3. update SESSION_CONTRACT.md or the relevant template in a small docs-only patch
4. record the change in the next handoff

## Hard Rules

- No progress claim without proof.
- No fixed claim without proof.
- No ledger/history rewrite to hide mismatch.
- No cascade delete financial history.
- No nullable FK shortcut without immutable snapshot model.
- No UI-only financial truth.
- No JavaScript-only validation for finance.
- No direct report query without explicit mode when touching versioned report behavior.
- No file over 100 lines in app unless justified with valid audit bypass.
- Prefer splitting files cleanly instead of compressing dense logic.
- make verify must pass before claiming final safe state.
- Owner handles commit and push manually unless explicitly asked.
