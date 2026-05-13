# HyperPOS Error-Log Remediation Handoff Standard

This directory stores session handoffs for HyperPOS error-log remediation work.

The goal is not to make a pretty diary. The goal is to let the next AI/session resume safely without inventing state, skipping proof, or treating Markdown optimism as production reality.

## Source of Truth Priority

Use this order:

1. Local command output from the user.
2. Current source code and tests in the local repo.
3. Workflow documents:
   - docs/workflow/error-log-remediation-workflow.md
   - docs/workflow/error-log-remediation-dod.md
   - docs/workflow/error-log-remediation-sequence.md
4. Error-log documents in docs/error_log.
5. Previous handoff files.

Never treat an error-log `Status` line as truth until source/test proof confirms it.

## Locked Workflow Rules

- One active slice only.
- One active issue only unless the workflow document says otherwise.
- Do not patch before source reality intake.
- RED proof is required before patch, except when source is already patched and this is explicitly recorded.
- Source/test proof wins over document status.
- Local command output is the primary source of truth.
- User handles git commit/push manually.
- Do not commit or push unless explicitly asked.
- UI hiding is not a security boundary.
- Do not claim strict fixed, global verification, browser/manual QA, or full DoD without proof.
- Progress uses workflow count only:
  - Strict Fixed Progress
  - Slice Progress
  - Current Issue Step
  - Proof
  - Gap

## Handoff File Naming

Use:

YYYY-MM-DD-hyperpos-error-log-remediation-slice-N-short-scope-handoff.md

Examples:

- 2026-05-10-hyperpos-error-log-remediation-slice-4-closure-handoff.md
- 2026-05-10-hyperpos-error-log-remediation-slice-5-start-handoff.md
- 2026-05-10-hyperpos-error-log-remediation-slice-5-013-red-proof-handoff.md

## Required Handoff Sections

Every handoff should include these sections:

1. Title
2. Purpose
3. Current Repo Proof
4. Progress
5. Active Slice
6. Active Issue
7. Locked Rules
8. Completed Work
9. Current Source Reality
10. Test Reality
11. Gaps
12. Next Safest Step
13. Copy-Paste Command for Next Session
14. Do Not Do
15. Opening Prompt for Next Session

## Status Classification

Use these labels when classifying an error-log item:

### trusted

The document status is supported by current local source and local test proof.

Requirements:

- Source matches the claimed patch.
- RED/GREEN or equivalent behavior proof exists.
- No contradiction found in current local repo.

### weak

The document has a status or patch claim, but proof is incomplete.

Common causes:

- Only syntax proof exists.
- Only commit proof exists.
- No focused behavior test.
- Source has not been inspected in the current session.
- Existing tests do not cover the reported exploit path.

### contradicted

The document claims something that current local source/test reality disproves.

Common causes:

- Document says patched/fixed but source lacks the guard.
- Test proves the bug still exists.
- Claimed file/path does not exist.
- Claimed behavior is not implemented.

## Minimum Intake Pattern

Before patching a new issue, run:

1. Repo status.
2. Latest log.
3. Local vs origin.
4. Relevant error-log status grep.
5. Full issue document intake.
6. Relevant source file intake.
7. Relevant tests inventory.
8. Existing references grep.

Do not skip source reality just because the docs sound confident. Documentation has no runtime, which is inconvenient but apparently important.

## Shell Safety

When looping over files, use a valid shell loop:

for f in \
path/one.md \
path/two.md
do
  printf '\n-- %s --\n' "$f"
  grep -nE 'Status|Patched|Fixed|RED|GREEN|Residual|Gap' "$f" | head -n 80
done

Do not paste a plain newline-separated file list after `for f in` without backslashes. Bash will try to execute the filenames. It is very committed to being literal.

## Progress Rules

Strict Fixed Progress only increases when an issue is fixed according to the workflow count and proof exists.

Slice Progress only increases when an issue inside the active slice is verified fixed/closed with proof.

Current Issue Step may increase for intake, RED proof, GREEN proof, docs update, and closure, but must state exactly what the percent refers to.

Plans do not increase progress.

## Recommended Handoff Template

# YYYY-MM-DD HyperPOS Error-Log Remediation Slice N Handoff

## Purpose

Explain why this handoff exists.

## Current Repo Proof

- Branch:
- HEAD:
- Origin alignment:
- Working tree:
- Untracked files:

## Progress

- Strict Fixed Progress:
- Slice Progress:
- Current Issue Step:

## Active Slice

- Slice:
- Issues:

## Active Issue

- Issue:
- Classification:
- Current status:

## Locked Rules

List only rules relevant to continuing safely.

## Completed Work

State what was proven. Do not list unproven plans as completed.

## Current Source Reality

List exact files and observed behavior.

## Test Reality

List exact tests inspected or run.

## Gaps

List missing proof.

## Next Safest Step

One step only.

## Copy-Paste Command for Next Session

Use shell-safe commands.

## Do Not Do

List dangerous actions to avoid.

## Opening Prompt for Next Session

Write a prompt that can be pasted into a new ChatGPT session without corrupting Markdown.
