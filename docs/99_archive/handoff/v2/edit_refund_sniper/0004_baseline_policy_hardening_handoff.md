# Handoff 0004 - Baseline Policy Hardening

## Metadata

- Date: 2026-05-13
- Sequence: 0004
- Scope: baseline policy hardening
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0003_downward_surplus_revision_commit_handoff.md
- Latest proven commit or push proof: Owner stated make verify passed; owner workflow states local and repo are identical after push except ignored files.

## Status

docs-only update

## Session Goal

Strengthen the edit refund sniper handoff rules so later sessions continue from completed proof instead of restarting baseline review.

This session exists to reduce repeated context tax and preserve precision across limited-context AI sessions.

## Facts

- Owner stated make verify passed for Phase 1C-B.
- Owner workflow states local and repo are identical after push except ignored files.
- Owner handles commit and push manually.
- Owner does not want git status, git log, git diff, git diff --check, or make verify requested as ceremony.
- Baseline proof from owner clean, pushed, latest, or make verify pass must be treated as FACT.
- README had wording that could be misread as requiring baseline git commands at every session start.
- SESSION_CONTRACT already discouraged routine git proof, but needed stronger anti-review-loop wording.
- Handoff 0003 recorded Phase 1C-B downward surplus revision commit.
- Phase 1C-B focused proof passed with 15 tests and 64 assertions.
- Final make verify passed with 972 tests and 5156 assertions.
- This session changed docs only.

## Gaps

- No runtime gap.
- No source gap.
- No test gap.
- No baseline git gap.
- No make verify gap.

## Assumptions

No implementation assumption accepted.

## Decisions

- Decision source: owner statement.
  - Owner clean, pushed, latest, and make verify pass statements are accepted as FACT.
- Decision source: owner workflow.
  - Local and repo identity after push is owner-controlled and must not be rechecked as ceremony.
- Decision source: session proof.
  - Future sessions must continue from latest handoff Next Active Step unless a real contradiction appears.
- Decision source: docs policy.
  - Git and verify commands are conditional tools, not session-entry rituals.

## Active Slice

Selected active slice:

Baseline Policy Hardening.

Scope in:

- README baseline wording
- SESSION_CONTRACT anti-review-loop policy
- handoff 0004 creation
- README latest handoff pointer update

Scope out:

- app source
- tests
- database
- UI
- reporting
- refund lifecycle
- customer balance lifecycle
- any git command requirement

Files to touch:

- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0004_baseline_policy_hardening_handoff.md

Files not touched:

- app/*
- tests/*
- database/*
- resources/*
- public/*

DB impact:

- None.

UI impact:

- None.

Report impact:

- None.

API impact:

- None.

Audit impact:

- Docs-only continuity policy improved.

## Source Audit Summary

Source audited in this docs-only slice:

- path: docs/99_archive/handoff/v2/edit_refund_sniper/HANDOFF_TEMPLATE.md
  - relevant section: required handoff structure
  - current behavior: requires structured handoff sections for continuity
  - risk: handoff drift can cause later sessions to re-review instead of continue
  - scope: in scope

- path: docs/99_archive/handoff/v2/edit_refund_sniper/README.md
  - relevant section: baseline command policy
  - current behavior before patch: wording could be misread as mandatory session-entry git checks
  - risk: later sessions waste context and repeat review
  - scope: in scope

- path: docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
  - relevant section: baseline proof policy
  - current behavior before patch: discourages routine git proof but needed stronger anti-review-loop wording
  - risk: later sessions may ask for ritual proof despite owner baseline
  - scope: in scope

## Files Changed

- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0004_baseline_policy_hardening_handoff.md

## Tests And Proof

Runtime proof inherited from Phase 1C-B:

    make verify

Result:

    972 passed, 5156 assertions

Focused Phase 1C-B proof inherited from previous handoff:

    15 passed, 64 assertions

Docs proof for this slice must be run after writing files:

    Markdown fence scan across docs/99_archive/handoff/v2/edit_refund_sniper

Expected result:

    no output

## Residual Risks

Blocks next step:

- None.

Does not block next step:

- Later AI sessions may still be careless, because optimism is not a control mechanism.
- The strengthened docs reduce that risk but cannot eliminate it.

Needs owner decision:

- None for this docs-only slice.

Future improvement:

- If another AI session still asks for baseline git proof as ceremony, update AI_RULES core or workflow policy, not just sniper handoff docs.

## Next Active Step

Goal:

Continue from latest handoff without restarting broad review.

Command if needed:

    no command required unless owner asks or a real contradiction appears

Expected proof:

    next session starts from this handoff and continues the documented active slice

Stop condition:

    stop if new local command output contradicts handoff, docs, or source.

## Next Session Opening Prompt

    Kita lanjut HyperPOS dari edit/refund sniper handoff.

    Baca berurutan:
    docs/01_standards/0001_index.md
    docs/01_standards/0002_decision_policy.md
    docs/99_archive/handoff/v2/edit_refund_sniper/README.md
    docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
    docs/99_archive/handoff/v2/edit_refund_sniper/0004_baseline_policy_hardening_handoff.md

    Baseline policy:
    - Saya selalu push setiap aksi.
    - Local dan repo identik setelah push kecuali ignored files.
    - Kalau saya menyatakan clean, pushed, latest, atau make verify pass, itu FACT.
    - Jangan minta git status, git log, git diff, git diff --check, atau make verify sebagai ritual.
    - Gunakan git atau make verify hanya kalau ada trigger nyata: dirty state dicurigai, changed-file inventory kritikal, source/docs conflict, test failure aneh, final closure tanpa owner proof, atau saya eksplisit minta.
    - Kalau issue muncul setelah closure, catat sebagai technical debt atau next-session blocker kecuali ada kontradiksi proof.

    Latest completed slice:
    - Phase 1C-B downward surplus revision commit selesai.
    - Downward paid revision can commit by capping replay allocation to revised payable total.
    - Customer payment remains preserved.
    - note_revision_settlements records overpaid_pending surplus.
    - Focused proof: 15 passed, 64 assertions.
    - Final make verify: 972 passed, 5156 assertions.
    - Handoff 0004 strengthened baseline policy to prevent repeated review loops.

    Locked domain decision:
    - Downward revision surplus is pending undecided money state.
    - It is not revenue.
    - It is not automatic refund.
    - It is not automatic customer credit.
    - customer_balance_entries remains deferred until surplus disposition is decided.
    - UI is not financial truth.
    - No ledger/history rewrite.
    - No generic reader/query patch without consumer proof.

    Current active target:
    - Continue only from a scoped next active step.
    - Do not restart broad repo review.
    - Do not ask for git baseline proof unless a real trigger appears.

    Required response shape:
    FACT
    GAP
    ASSUMPTION
    DECISION
    ACTIVE STEP
    FILES TO TOUCH
    FILES NOT TO TOUCH
    COMMAND
    EXPECTED PROOF
    NEXT

## README Update Required

Yes.

README latest handoff pointer must point to:

    docs/99_archive/handoff/v2/edit_refund_sniper/0004_baseline_policy_hardening_handoff.md

## Session Context Health

78 percent.

Reason:

This session includes a behavior-changing Phase 1C-B source patch, final make verify proof, docs policy hardening, and multiple handoff updates. Next session should start from this handoff before any implementation.
