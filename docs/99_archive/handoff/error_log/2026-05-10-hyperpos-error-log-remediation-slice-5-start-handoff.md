# 2026-05-10 HyperPOS Error-Log Remediation Slice 5 Start Handoff

## Purpose

This handoff captures the start of Slice 5 remediation after Slice 4 closure, with initial #013 intake proving that the #013 error-log document is contradicted by current local source.

This file exists so the next session can continue safely without re-trusting stale document status or accidentally patching before RED proof.

## Current Repo Proof

Last pasted local proof from user:

- Branch: main
- HEAD: 5941dd68
- Latest log label: commit 1794
- origin/main: aligned with main
- Local vs origin: 0 0
- Working tree:
  - only known untracked file before creating this handoff standard:
    - docs/handoff/error_log/2026-05-10-hyperpos-error-log-remediation-slice-4-closure-handoff.md
- Slice 4 closure handoff:
  - file exists locally
  - 475 lines
  - still untracked at latest proof

Important: after this handoff and README are created, working tree will include additional untracked documentation files unless the user commits them manually.

## Progress

- Strict Fixed Progress: 19/28 = 67.9%
- Slice 1: complete
- Slice 2: complete
- Slice 3: complete
- Slice 4: complete at handoff level, 7/7 issues closed:
  - #009
  - #011
  - #016
  - #019
  - #020
  - #027
  - #029
- Slice 5 Progress: 0/6 verified in this session
- Current Issue Step: #013 source reality intake partially complete; contradiction proven; RED test not yet added or run

Do not increase progress without proof.

## Active Slice

Slice 5 - Refund Lifecycle, Parent Note Eligibility, Terminal State, and UI Entry.

Slice 5 issues:

- docs/error_log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md
- docs/error_log/014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md
- docs/error_log/021-refunds-can-be-recorded-on-open-notes.md
- docs/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
- docs/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md
- docs/error_log/015-refunded-notes-expose-edit-workspace.md

## Active Issue

Active issue:

docs/error_log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md

Classification:

contradicted

Reason:

The #013 document claims the selected-row refund transaction was patched so `FinalizeRefundedNoteFromActiveRows::execute()` only runs when `allocation_count > 0`.

Current local source at HEAD 5941dd68 contradicts that claim.

## Locked Rules

- One active slice only.
- Active slice is Slice 5.
- Active issue is #013 until closed or explicitly paused.
- Source/test proof wins over document status.
- Local command output is the primary source of truth.
- RED proof required before patch, except when source is already patched and explicitly recorded.
- Do not patch before source/test intake.
- Do not commit/push unless explicitly asked.
- User handles git commit/push manually.
- UI hiding is not a security boundary.
- Do not claim strict fixed/global/browser/manual verified without proof.
- Progress uses workflow count only:
  - Strict Fixed Progress
  - Slice Progress
  - Current Issue Step
  - Proof
  - Gap

## Slice 5 Doc Status Intake

Latest grep output:

docs/error_log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md

- Status: Patched for auto-finalization, with verification gap and residual validation risk.
- Residual Risk section exists.
- Document admits behavior verification gap.

docs/error_log/014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md

- Status: Patched, with verification gap.

docs/error_log/021-refunds-can-be-recorded-on-open-notes.md

- Status: Patched, with verification gap.

docs/error_log/022-cashier-refund-route-bypasses-note-access-guard.md

- Status: Fixed and locally verified for cashier refund route note-access enforcement.
- Has RED proof and GREEN proof.
- Browser/manual QA not reported.
- Full make verify not claimed because audit-lines deferred by owner decision.

docs/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md

- Status: Fixed with proof.
- Has RED characterization proof and targeted GREEN proof.
- Remaining verification gaps exist.
- Full make verify not claimed because audit-lines deferred by owner decision.

docs/error_log/015-refunded-notes-expose-edit-workspace.md

- Status: Patched, with server-side authorization verification gap.

Treat these statuses as untrusted until source/test proof confirms them.

## #013 Document Reality

The #013 document says:

- Status: Patched for auto-finalization, with verification gap and residual validation risk.
- Patch summary claims `RecordSelectedRowsRefundPlanTransaction` no longer calls finalization by default.
- Patch summary claims finalizer only runs if `allocation_count > 0`.
- Verification gap admits no focused behavior test was reported.
- Missing proof includes:
  - forged unpaid selected-row refund no longer finalizes note as refunded
  - allocation_count = 0 results in finalized=false
  - no customer refund/refund allocation is created for unpaid rows
  - legitimate selected-row refund with allocation_count > 0 still finalizes when appropriate
  - finalization failure still rolls back transaction
  - audit record clearly distinguishes finalized=false
  - projection state remains consistent after zero-allocation cancellation

## #013 Source Reality

Source file:

app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php

Observed behavior:

- It processes payment buckets:
  - `$processed = $this->buckets->process(...)`
- It cancels selected rows:
  - `$canceled = $this->cancelRows->execute(...)`
- It then unconditionally calls finalizer:
  - `$finalized = $this->finalizeRefunded->execute(...)`
- There is no gate checking `(int) $processed['allocation_count'] > 0`.

Conclusion:

The document patch summary is contradicted by current source.

Source file:

app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php

Observed behavior:

- Reads note by ID.
- Normalizes selected row IDs.
- Verifies each selected row exists on the note.
- Rejects already inactive or already refunded rows through `isAlreadyInactive`.
- Builds payment buckets from payment component allocations and refund allocations.
- Builds `unpaidRowIds` as selected rows not represented in paid buckets.
- Creates `SelectedRowsRefundPlan` even when selected rows include unpaid rows.
- Does not reject `unpaidRowIds`.
- Does not require payment buckets to be non-empty.

Conclusion:

Resolver can produce a plan for unpaid selected rows with empty payment buckets.

Source file:

app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php

Observed behavior:

- Loops through `$plan->paymentBuckets()`.
- If buckets are empty, no refund operation runs.
- Returns:
  - `refund_ids` empty
  - `allocation_count` 0

Conclusion:

An unpaid selected-row plan can produce zero refund allocations.

Source file:

app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php

Observed behavior:

- Reads note.
- Resolves selected active rows.
- Cancels each selected row through `WorkItemStatusTransitionService`.
- Updates work item statuses.
- Replaces note work items with remaining items.
- Syncs note total from remaining active rows.
- Records `note_rows_canceled_via_refund`.
- Does not check whether selected rows were paid/refundable.
- Does not check allocation_count.

Conclusion:

Unpaid selected rows can be canceled and removed from active note total through refund flow.

Source file:

app/Application/Note/Services/FinalizeRefundedNoteFromActiveRows.php

Observed behavior:

- Reads note.
- If note total is greater than 0 or already refunded, returns finalized false.
- If note total is 0 and note is open, closes it.
- If note is closed, refunds it.
- Persists operational state.
- Records `note_refunded_after_selected_rows_refund`.
- Returns finalized true.

Conclusion:

If all active rows were canceled and note total becomes 0, the finalizer can convert an open unpaid note into refunded state.

Source file:

app/Application/Note/Services/SelectedActiveWorkItemsResolver.php

Observed behavior:

- Validates selected IDs are not empty.
- Validates selected IDs exist in current note work items.
- Splits selected vs remaining items.
- Does not check paid/refundable status.

Conclusion:

It supports the exploit chain by allowing active unpaid selected rows to be canceled.

## #013 Exploit Chain As Currently Proven By Source

Based on source intake:

1. HTTP request posts selected_row_ids to refund endpoint.
2. Request validates only array/date/reason shape.
3. Controller resolves selected rows into refund plan.
4. Resolver accepts selected rows if they exist and are not already inactive/refunded.
5. Resolver creates plan even when payment buckets are empty and unpaidRowIds is non-empty.
6. Bucket processor creates no customer refund and no refund allocations when paymentBuckets is empty.
7. Transaction still cancels selected rows.
8. Cancel/sync removes selected rows from active note total.
9. If selected rows were all active rows, active total becomes 0.
10. Transaction unconditionally invokes finalizer.
11. Finalizer closes/refunds note when total is 0.
12. Unpaid note can be marked refunded without real refund allocation.

This source chain is enough to justify a RED characterization test.

## Test Reality

Candidate existing test files inspected:

tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

Useful because:

- Already targets selected-row closed/open refund HTTP behavior.
- Has route `cashier.notes.refunds.store`.
- Uses `SeedsMinimalNotePaymentFixture`.
- Contains helper seed for open note with one paid line.
- Best candidate for #013 forged unpaid selected-row RED test.

tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php

Useful but broader because:

- Tests cashier refund controller behavior.
- Has open note refund behavior.
- Contains older expectations around open-note refunds.

tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php

Important because:

- Current test name says `test_refund_allows_operationally_open_line_selection_under_a1_contract`.
- It proves operationally open lines can be selected when they have paid allocation.
- Do not interpret "open line" as "unpaid line" without care.
- #013 should target unpaid/no-allocation selected row auto-finalization, not legitimate paid open-line refund.

Existing test inventory shows relevant files:

- tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php
- tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php
- tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php
- tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php
- tests/Feature/Note/ClosedNoteFullRefundLifecycleFeatureTest.php
- tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php
- tests/Feature/Note/ClosedNoteFullRefundProductOnlyInventoryLifecycleFeatureTest.php
- tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php

## Minimum RED Test Recommendation

Recommended file:

tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

Recommended test name:

test_forged_unpaid_selected_row_refund_does_not_auto_finalize_open_unpaid_note

Minimum scenario:

- User is kasir.
- Seed open note `note-unpaid-refund` with total 50000.
- Seed one service-only work item `wi-unpaid-refund-1`, status open, subtotal 50000.
- Do not seed customer payment.
- Do not seed payment allocation.
- POST to `cashier.notes.refunds.store` with selected_row_ids containing `wi-unpaid-refund-1`.
- Expected secure behavior for #013 minimum scope:
  - no customer_refunds
  - no refund_component_allocations
  - note must not become refunded
  - no `note_refunded_after_selected_rows_refund` timeline/audit entry
- Keep #013 scope narrow:
  - This issue is about auto-finalization without allocation.
  - Full rejection of unpaid row cancellation belongs to #014 if workflow keeps that separation.

Expected current RED result before patch:

The test should fail because source currently unconditionally finalizes after cancel/sync. Likely failure:

- expected note not refunded/open, but actual note becomes refunded
- or expected missing finalization timeline, but timeline exists

Do not patch until RED proof is captured.

## Minimum Patch Direction After RED

Do not implement until RED is proven.

Likely minimum source patch after RED:

app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php

Concept:

- Initialize `$finalized` as success finalized=false before finalizer.
- Run finalizer only when `(int) $processed['allocation_count'] > 0`.
- Preserve rollback behavior if finalizer runs and fails.
- Preserve audit recording with finalized=false data when allocation_count is 0.
- Preserve legitimate paid/refundable selected-row refund behavior.

Do not add resolver rejection in #013 unless the active workflow decides #014 is merged into #013. Current Slice 5 separation suggests #014 owns unpaid/open row cancellation eligibility.

## Gaps

#013 gaps:

- RED test not yet added.
- RED command not yet run.
- GREEN patch not applied.
- Legitimate selected-row refund regression not rerun after any patch.
- Docs #013 not updated after source/test proof.
- Full Slice 5 status not verified beyond grep.
- Browser/manual QA not run.
- Full global make verify not run in this step.
- Slice 4 closure handoff is still untracked unless user later commits it.

## Next Safest Step

Add one RED characterization test for #013 in:

tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

Then run only the targeted RED test.

Do not patch until the RED output is pasted and classified.

## Copy-Paste Command for Next Session

Use this as the first verification block in the next session before adding RED test:

printf '\n== REPO STATUS ==\n'
git status --short --branch --untracked-files=all

printf '\n== HEAD / ORIGIN ==\n'
git log --oneline --decorate -n 5
git rev-list --left-right --count origin/main...HEAD

printf '\n== #013 DOC STATUS ==\n'
grep -nE 'Status|Patched|Fixed|verification gap|RED|GREEN|Residual|make verify' docs/error_log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md | head -n 80

printf '\n== #013 SOURCE ANCHORS ==\n'
grep -RInE 'allocation_count|finalizeRefunded|FinalizeRefundedNoteFromActiveRows|unpaidRowIds|paymentBuckets|note_refunded_after_selected_rows_refund' \
  app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php \
  app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php \
  app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php \
  app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php \
  app/Application/Note/Services/FinalizeRefundedNoteFromActiveRows.php \
  app/Application/Note/Services/SelectedActiveWorkItemsResolver.php

printf '\n== #013 TARGET TEST FILE HEAD ==\n'
sed -n '1,220p' tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

## Do Not Do

- Do not mark #013 fixed from document status.
- Do not trust the #013 patch summary.
- Do not patch before RED test proof.
- Do not combine #013 and #014 unless explicitly decided.
- Do not commit/push.
- Do not claim Slice 5 progress until proof exists.
- Do not claim browser/manual/global verification.
- Do not treat UI hiding as authorization.

## Opening Prompt for Next Session

Continue HyperPOS error-log remediation from:

docs/handoff/error_log/2026-05-10-hyperpos-error-log-remediation-slice-5-start-handoff.md

Rules:

- One active slice only.
- Active slice is Slice 5.
- Active issue is #013.
- Do not patch before RED proof.
- Source/test proof wins over docs.
- Local command output is source of truth.
- User handles git commit/push manually.
- Do not commit/push unless explicitly asked.
- Use progress format:
  - Strict Fixed Progress
  - Slice Progress
  - Current Issue Step
  - Proof
  - Gap

Known proof:

- HEAD was 5941dd68, aligned with origin/main, before creating handoff docs.
- Slice 4 closure handoff existed locally with 475 lines and was untracked.
- #013 document says patched with verification gap.
- Current source contradicts #013 patch summary:
  - RecordSelectedRowsRefundPlanTransaction unconditionally calls finalizer.
  - SelectedNoteRowsRefundPlanResolver accepts unpaid selected rows into plan.
  - RecordSelectedRowsRefundPlanBucketProcessor returns allocation_count 0 when no buckets exist.
  - CancelSelectedRowsAndSyncActiveNoteTotal cancels selected rows and syncs total without allocation proof.
  - FinalizeRefundedNoteFromActiveRows refunds note when active total is 0.
- Classification for #013: contradicted.

Next safest step:

Add one RED characterization test in:

tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

Target behavior:

Forged selected-row refund on an open unpaid note with no payment allocations must not auto-finalize the note as refunded and must not create refund allocations.

Run only the targeted RED test first. Do not patch until RED output is available.
