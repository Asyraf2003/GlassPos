# 0026 Post AI Pro Triage Context Trim Handoff

## Purpose

Trim context for the next HyperPOS refund/edit/payment sniper session.

The next session must not repeat broad analysis from older handoffs. It must focus only on selecting or proving the next valid unresolved target after the AI Pro triage targets were closed/session-safe.

## Repo

Root:

/home/asyraf/Code/laravel/bengkel2/app

## Working Rules

- Blueprint/proof-first.
- One active target per step.
- Do not broad audit the repo.
- Do not start with git status, git log, git push, remote sync, or make verify.
- Owner handles git commit/push/manual sync.
- Local command output from owner is source of truth.
- Do not generate code unless the issue is proven from local repo/source/test output.
- If data is insufficient, stop at GAP and request one minimal proof/output.
- Use rg/fd/sed, not grep -R/find.
- Do not claim fixed/full green/make verify without local command proof.

## Latest Locked User Facts

- make verify is already latest and green.
- git push is already latest.
- Do not re-prove make verify/git sync unless:
  - user explicitly asks;
  - a new patch is made;
  - a new regression proof appears;
  - new command output shows conflict.

## Closed / Do Not Reopen Without New Regression Proof

From docs and latest user facts:

- HP-UI-001: session-safe locally.
- HP-REFUND-001: Confirmed RED, fixed GREEN, make verify green.
- HP-INV-001: Confirmed RED, fixed GREEN, focused verified.
- HP-ROWS-001: Confirmed RED, fixed GREEN, focused verified.
- HP-REPORT-001: Confirmed RED, fixed GREEN, focused verified.
- HP-IDEMP-001: no production patch required / backend guarded / characterization verified.

HP-IDEMP latest local proof:

- Baseline HTTP controller:
  - RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest
  - PASS: 4 passed / 25 assertions.
- After characterization tests:
  - RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest
  - PASS: 6 passed / 45 assertions.
- Focused blast-radius:
  - RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest
  - RecordNoteRevisionSurplusRefundPaymentHandlerTest
  - DatabaseNoteRevisionSurplusRefundPaymentAdapterTest
  - AdminNoteSurplusRefundPaidUiFeatureTest
  - NoteDetailSurplusDispositionPayloadFeatureTest
  - PASS: 17 passed / 113 assertions.

HP-IDEMP final classification:

- No confirmed financial overpay issue.
- No production source patch required.
- Backend already guards idempotency:
  - same idempotency_key + same payload reuses existing refund_paid;
  - same idempotency_key + different payload is rejected;
  - row/audit does not duplicate under tested HTTP path.
- Remaining concern is UX/stale-tab semantics only, not confirmed production financial corruption.

Known HP-IDEMP changed files:

- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php
- docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md

Production source changed for HP-IDEMP:

- None.

## Important Stale Context Warning

Older handoffs such as 0013, 0014, and 0023 contain historical gaps around refund_paid execution, UI/admin transport, idempotency, concurrency, report/export, and audit timeline.

Do not treat those older gaps as still open automatically.

Known closures after those older docs:

- refund_paid audit timeline read model: closed by 0016.
- report/cash ledger backend read model: closed by 0017.
- report screen/export visibility: closed by 0018.
- ADR 0030 S12 carry-forward/doc closure: closed by 0021/0022.
- admin surplus refund_paid UI path was worked after 0023 and later verified green.
- AI Pro HP findings from 0024/0025 are now closed/session-safe per latest user facts.

If an older handoff says "Remaining" or "Next active step", cross-check against later handoffs and latest user facts before acting. Do not resurrect stale work.

## Current State

No valid implementation target is currently selected.

The next session must not patch code immediately.

The next valid step is target selection only:

- read the newest relevant handoff/status docs;
- identify a candidate that is not already closed by later proof;
- classify it as:
  - Suspected;
  - Confirmed source-risk;
  - Needs proof;
  - Stale/closed;
- ask for one minimal local proof/output if not enough for implementation.

## Recommended Next Active Step

Find the next unresolved debt after 0025/0026 without broad repo scan.

Start from docs only.

Use read-only targeted commands.

Do not inspect production source unless a candidate target is selected from docs and not stale.

Suggested command:

printf '\n--- latest sniper handoff status anchors only ---\n'
rg -n "Status:|Remaining|Remaining Gaps|Next active|Next safest|Recommended Next|TODO|Pending|GAP|not fixed|unfixed|Suspected|Needs narrowing|Needs proof|No production patch required|session-safe|fixed GREEN|Fixed GREEN|browser/manual|global safe-state|UX/stale-tab" \
  docs/99_archive/handoff/v2/edit_refund_sniper/0024_ai_pro_repo_logic_audit_triage_handoff.md \
  docs/99_archive/handoff/v2/edit_refund_sniper/0025_ai_pro_hp_ui_hp_refund_session_handoff.md \
  docs/99_archive/handoff/v2/edit_refund_sniper/0026_post_ai_pro_triage_context_trim_handoff.md

If no unresolved candidate appears, stop and ask owner for next target source. Do not invent backlog.

## Progress Baseline

Final Goal Progress: 15%.

Main Process Progress: 80%.

Current Sub-step Progress: 0% for next target selection because no new unresolved target is selected yet.

Do not increase progress without new proof/output.

## Session Context Health

New session should start clean.

This handoff exists to prevent context bloat and stale target resurrection.
