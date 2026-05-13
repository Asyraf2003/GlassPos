# Handoff 0003 - Downward Surplus Revision Commit

## Metadata

- Date: 2026-05-13
- Sequence: 0003
- Scope: downward surplus revision commit
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0002_revision_settlement_foundation_handoff.md
- Latest proof: Phase 1C-B focused proof passed locally with 15 tests and 64 assertions; final make verify passed with 972 tests and 5156 assertions.

## Status

implementation final-safe-state verified

This handoff records Phase 1C-B.

Final safe-state proof passed after the Phase 1C-B source, test, and docs changes.

## Session Goal

Allow a downward paid note revision to commit without losing money state.

The goal is to preserve the revised payable amount in component allocations while keeping the surplus as DB-backed pending money state in note_revision_settlements.

## Facts

- Phase 1A and 1B already created note_revision_settlements foundation.
- Source audit proved the previous downward surplus failure came from payment replay sending full carried payment into AllocatePaymentAcrossComponents.
- AllocatePaymentAcrossComponents remains strict and still rejects unallocated remainder outside the revision replay cap boundary.
- Phase 1C-B changed replay behavior in NoteReplacementPaymentAllocationReconciler.
- Downward paid revision now caps replay allocation to revised payable component total.
- Customer payment remains preserved at original paid amount.
- Surplus is stored in note_revision_settlements as overpaid_pending.
- Projection, operational status, outstanding resolver, reporting, UI, refund lifecycle, and customer balance were not touched.
- App file line-count proof:
  - app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php: 94 lines
- Test file line-count proof:
  - tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php: 264 lines

## Gaps

- Full browser/manual QA has not been run.
- No customer balance lifecycle exists yet.
- No surplus disposition workflow exists yet.
- Pending surplus is not integrated into reporting by design.
- No UI display decision for pending surplus was implemented in this slice.
- Final surplus disposition remains undecided.

## Assumptions

No implementation assumption accepted.

## Decisions

- Downward revision surplus remains pending undecided money state.
- Surplus is not revenue.
- Surplus is not automatic refund.
- Surplus is not automatic customer credit.
- customer_balance_entries remains deferred until surplus disposition is locked.
- AllocatePaymentAcrossComponents must remain strict.
- The revision replay boundary may cap carried payment allocation to revised payable component total.
- note_revision_settlements is the DB-backed record for pending surplus at revision commit.
- No ledger/history rewrite is allowed.
- UI is not financial truth.
- Projection/reporting semantics must not be changed without explicit consumer proof.

## Active Slice

Selected active slice:

Downward Surplus Revision Commit.

Scope in:

- cap revision replacement payment replay to revised payable total
- preserve customer payment amount
- persist overpaid_pending settlement
- targeted test
- file-level focused test
- focused settlement/revision blast-radius test

Scope out:

- refund due lifecycle
- customer credit lifecycle
- owner-retained surplus lifecycle
- customer_balance_entries
- reporting changes
- UI changes
- projection changes
- operational status resolver changes
- outstanding resolver changes
- generic reader/query patches

## Files Changed

Production:

- app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php

Tests:

- tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php

Docs:

- docs/99_archive/handoff/v2/edit_refund_sniper/0003_downward_surplus_revision_commit_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

## Files Intentionally Not Touched

- app/Application/Payment/Services/AllocatePaymentAcrossComponents.php
- app/Application/Note/Services/NoteHistoryProjectionService.php
- app/Adapters/Out/Note/DatabaseNoteHistoryProjectionSourceReaderAdapter.php
- app/Application/Note/Services/NoteOperationalStatusResolver.php
- app/Application/Note/Services/NoteOutstandingPaymentAmountResolver.php
- resources/views/*
- public/assets/static/js/*
- reporting files
- refund lifecycle files
- customer balance files

## Source Audit Summary

- NoteReplacementPaymentAllocationReconciler previously captured net payment and replayed the full amount into revised components.
- AllocatePaymentAcrossComponents requires full allocation of the supplied amount and throws when any remainder exists.
- This made downward paid revisions reject and roll back when revised payable total was below carried payment amount.
- Phase 1C-B keeps allocator strict and moves the revision-specific cap to the reconciler boundary.
- The cap prevents replay allocation from exceeding revised payable total.
- The unallocated surplus remains represented by note_revision_settlements, not by fake allocation, revenue, refund, or customer credit.

## Tests And Proof

Final safe-state proof:

    make verify

Result:

    972 passed, 5156 assertions

PHPStan blocker resolved before final proof:

- NoteReplacementPaymentAllocationReconciler PHPDoc used an invalid escaped FQCN type.
- Fixed by importing PayableNoteComponent and using list<PayableNoteComponent> in the PHPDoc.

Initial containment proof:

    php artisan test tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php

Result:

    1 passed, 8 assertions

RED fixture failure:

    Current revision untuk note root tidak ditemukan.

Resolution:

- Test fixture was missing current revision baseline.
- The fixture was updated to seed note-1-r001 as the current revision.

Valid RED proof:

    php artisan test tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php --filter=downward_replacement_commits_with_pending_surplus_settlement

Result:

    failed with Payment tidak bisa dialokasikan penuh ke komponen note.

Targeted GREEN proof:

    php artisan test tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php --filter=downward_replacement_commits_with_pending_surplus_settlement

Result:

    1 passed, 6 assertions

File-level focused proof:

    php artisan test tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php

Result:

    2 passed, 11 assertions

Focused blast-radius proof:

    php artisan test tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php tests/Unit/Application/Note/Services/BuildNoteRevisionSettlementTest.php tests/Feature/Note/DatabaseNoteRevisionSettlementAdapterTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php

Result:

    15 passed, 64 assertions

Syntax proof:

- app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php: no syntax errors
- tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php: no syntax errors

Line-count proof:

    94 app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
    264 tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php
    358 total

## Residual Risks

- Final make verify passed with 972 tests and 5156 assertions.
- Surplus disposition is still undecided.
- Reporting has no explicit pending surplus mode.
- UI has no explicit pending surplus display.
- Customer balance lifecycle is intentionally deferred.
- Manual/browser QA has not been run.

## Next Active Step

Goal:

Owner commit and push manually after docs hygiene is complete.

Recommended next command:

    make verify

Expected proof:

    make verify passes without gaps.

If make verify passes, owner can commit and push manually.

## Next Session Opening Prompt

    Kita lanjut HyperPOS dari edit/refund sniper handoff.

    Baca berurutan:
    docs/01_standards/0001_index.md
    docs/01_standards/0002_decision_policy.md
    docs/99_archive/handoff/v2/edit_refund_sniper/README.md
    docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
    docs/99_archive/handoff/v2/edit_refund_sniper/0003_downward_surplus_revision_commit_handoff.md

    Baseline policy:
    - Saya selalu push setiap aksi.
    - Local dan repo dianggap identik kecuali ignored files jika saya sudah bilang clean/pushed/latest.
    - Jangan minta git status/log/diff sebagai ritual.
    - make verify dijalankan di akhir sesi/final safe-state proof, bukan tiap micro-step.
    - Targeted/focused tests cukup untuk proof intermediate.
    - Minta git check hanya untuk dirty state, changed-file inventory kritikal, source/docs conflict, test failure aneh, atau final closure tanpa push proof.

    Latest completed slice:
    - Revision Settlement Foundation Phase 1A and Phase 1B selesai.
    - Phase 1C-B downward surplus revision commit focused-verified.
    - Downward paid revision can commit by capping replay allocation to revised payable total.
    - Customer payment remains preserved.
    - note_revision_settlements records overpaid_pending surplus.
    - Focused proof: 15 passed, 64 assertions.
    - App line-count proof: NoteReplacementPaymentAllocationReconciler.php 94 lines.
    - final make verify passed with 972 tests and 5156 assertions.

    Locked domain decision:
    - Downward revision surplus is pending undecided money state.
    - It is not revenue.
    - It is not automatic refund.
    - It is not automatic customer credit.
    - It must remain flexible and DB-backed.
    - customer_balance_entries remains deferred until surplus disposition is decided.
    - UI is not financial truth.
    - No ledger/history rewrite.
    - No generic reader/query patch without consumer proof.

    Current active target:
    - Owner commit and push manually after docs hygiene.
    - Otherwise continue only from a new scoped source audit.

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

Latest handoff pointer must point to:

    docs/99_archive/handoff/v2/edit_refund_sniper/0003_downward_surplus_revision_commit_handoff.md

## Session Context Health

72 percent.

Reason:

This session added a new domain decision, a behavior-changing production patch, targeted/focused proof, residual gaps, and next active step.
