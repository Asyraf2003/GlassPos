# Handoff: Restore Cancelled Note C2

## Metadata
- Date: 2026-09-26
- Slice / topic: C2 restore a cancelled note as a new accepted revision
- Workflow step: implementation and verification; preparing traceable PR
- Status: implementation/proof complete locally; review and merge pending
- Progress: C2 evidence complete; campaign remains active

## Target Work Page
Blueprint0019 C2: restore a cancelled root through its immutable revision history, preserving the cancellation event and committing new current effects as an accepted child revision.

## References Used
- Blueprint: `docs/03_blueprints/finance/0019_whole_note_cancellation_and_restore.md`
- Workflow: `docs/04_lifecycle/handoff/0027_primitive_lifecycle_ordered_execution_handoff.md`
- DoD: Blueprint0018 slices 1-12 baseline and Blueprint0019 restore gates
- ADR: ADR-0042, ADR-0044, ADR-0045, ADR-0046
- Previous handoff: `0030_unpaid_whole_note_cancellation_c1_handoff.md`; raw owner evidence remains in `0029_whole_note_cancellation_owner_decision_handoff.md`
- Repo snapshot / command output: branch `feat/5-restore-cancelled-note`, based on merged main `5f5f2c3e`; Issue #5

## Locked Facts
- C1 merged as PR #4. It preserves R1 and blocks ordinary mutation/payment/refund against a cancelled root.
- `CreateNoteRevisionWorkflow` is the existing accepted revision engine and rejects cancelled roots by default.
- Restore uses that workflow only after validating the current cancellation identity and base while holding the canonical root lock.
- The selected source revision must exist under the same root and is mapped from immutable snapshots. Restore history may intentionally use an older source revision than the current cancelled revision.
- The revision applier now accepts an internal trusted snapshot argument for this server-selected historical source; ordinary revision calls retain their existing default behavior.
- Reuse of a restored cancellation event is rejected even when a later cancellation cycle has returned the root to cancelled state.
- Historical external-purchase source revisions are rejected at the existing external boundary; no supplier/refund lifecycle is created here.
- Current history reader labels the restore as `Pulihkan Transaksi`; Detail actor display is part of C5 presentation work.
- `.codex/` is untracked local context and is intentionally excluded.

## Scope Used
### SCOPE-IN
- Restore command/request/controller, authorization/date check, root lock, base and cancellation identity validation.
- Restore through a new accepted revision/current pointer, with fresh work-item and stock identities.
- `note_restored` timeline and durable audit linked to cancellation/source/base/result revision.
- Operation-specific idempotency, source revision ownership, stock validation, rollback and runtime overlap proof.
- Blueprint and handoff checkpoint updates.

### SCOPE-OUT
- Cashier Detail restore UI, refund expansion, external supplier restoration, Auto presets, browser/device acceptance, unrelated fixes.

## GAP
- C5 Detail UI is not implemented.
- Historical external-purchase revision restoration remains unsupported pending bounded C4 work.
- Physical cashier acceptance and campaign-final broad scan remain pending.
- C2 PR review/merge remains pending.

## Locked Decisions
- Restore creates a new accepted child revision and reissues only the selected source revision's current effects with fresh identities. It never restores by state flag or soft-delete.
- Cancellation event identity is required in addition to current base revision; prior restored cancellation events cannot authorize another lifecycle cycle.
- Use existing revision and inventory primitives. Do not copy payments/refunds or invent a new finance/stock engine.
- Tests remain focused by slice; run a repository-wide scan at campaign closeout unless new blast radius warrants an earlier scan.

## Files Created / Changed
### New files
- `app/Adapters/In/Http/Controllers/Note/RestoreCancelledNoteController.php`
- `app/Adapters/In/Http/Requests/Note/RestoreCancelledNoteRequest.php`
- `app/Application/Note/Services/NoteRestoreIdempotency.php`
- `app/Application/Note/Services/RestoreCancelledNoteWorkflow.php`
- `app/Application/Note/UseCases/RestoreCancelledNoteHandler.php`
- `tests/Feature/Note/WholeNoteRestoreFeatureTest.php`
- `tests/Feature/Note/WholeNoteRestoreAtomicityFeatureTest.php`
- `tests/Feature/Note/WholeNoteRestoreConcurrencyFeatureTest.php`

### Changed files
- `app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php`
- `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`
- `app/Application/Note/Services/NoteCancellationAccess.php`
- `app/Adapters/Out/Note/DatabaseNoteCorrectionHistoryReaderAdapter.php`
- `app/Ports/Out/Note/NoteCorrectionHistoryReaderPort.php`
- `app/Core/Note/Note/NoteCancellation.php`
- Cashier note route access and `routes/web/note.php`
- Blueprint0019 and lifecycle handoff index

## Verification Proof
- command: initial characterization `env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3321 DB_DATABASE=glasspos_cancellation_c1_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/WholeNoteRestoreFeatureTest.php --compact --stop-on-failure`
  - result: expected RED, restore endpoint returned 404 before route/implementation existed.
  - meaning: the new lifecycle gap was reproduced before implementation.
- command: `env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3321 DB_DATABASE=glasspos_cancellation_c1_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/WholeNoteRestoreFeatureTest.php tests/Feature/Note/WholeNoteRestoreAtomicityFeatureTest.php tests/Feature/Note/WholeNoteRestoreConcurrencyFeatureTest.php tests/Feature/Note/WholeNoteCancellationFeatureTest.php --compact --stop-on-failure`
  - result: 12 passed, 164 assertions after final production change.
  - meaning: product/service/package restore; older source revision; cancellation identity cycles; idempotent replay/conflict; stale base; unsupported external history; insufficient stock; actual outbox failure rollback/retry; and independent-session overlapping restore are covered.
- command: `env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3321 DB_DATABASE=glasspos_cancellation_c1_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest tests/Feature/Note/PrimitiveCancelCorrectionVersionChainFeatureTest.php tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/NoteRevisionStoreStockRollbackFeatureTest.php tests/Feature/Note/NoteRevisionRollbackFeatureTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php --compact --stop-on-failure`
  - result: 10 passed, 140 assertions after final production change.
  - meaning: adjacent accepted-revision, stock reversal/issue, rollback, and inventory operation contracts remain green.
- command: `vendor/bin/pint --test <C2 changed PHP paths>`
  - result: passed after applying repository formatting.
  - meaning: changed PHP paths conform to the repository formatter.
- command: `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
  - result: no errors.
  - meaning: static analysis passes for the current C2 implementation.
- command: `git diff --check`
  - result: passed after final test run.
  - meaning: patch whitespace is clean.

## Risks / Follow-up Notes
- Runtime overlap test uses separate MySQL sessions, a held transaction barrier, and observed lock wait; sequential execution is not used as concurrency proof.
- Audit failure is injected after the actual bound outbox adapter insert; full source graph comparison and same-key retry are verified.
- No repository-wide scan was run for C2 by design. C1's earlier full-suite result is a point-in-time C1 result, not C2 proof.
- Current external/refund limitations remain separate existing dependencies; this restore path rejects unsupported historical external source data without mutation.

## Next Step
Finalize C2 diff review, create granular meaningful commits, push `feat/5-restore-cancelled-note`, open a PR that closes Issue #5, obtain real review/checks, resolve any findings, and merge only when coherent/green.
