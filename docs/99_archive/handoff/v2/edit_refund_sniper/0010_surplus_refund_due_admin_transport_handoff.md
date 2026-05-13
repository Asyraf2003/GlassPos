# Handoff 0010 - Surplus Refund Due Admin Transport

## Metadata

- Date: 2026-05-13
- Sequence: 0010
- Scope: minimum admin HTTP transport for refund_due surplus disposition
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0009_surplus_refund_due_use_case_handoff.md
- Status: minimum admin transport implemented and locally verified with targeted plus focused blast-radius proof
- Owner workflow: owner handles commit and push manually

## Session Goal

Continue HyperPOS edit/refund sniper chain from handoff 0009.

The active target was not UI, not report query, not refund_paid execution, not customer_credit, and not customer_balance_entries.

The active target was:

Design and implement the minimum admin transport for invoking the existing backend use case:

    CreateNoteRevisionSurplusRefundDue

The transport slice must call the existing application use case.

The transport slice must not compute final refund amount itself.

The transport slice must not write audit_logs.

The transport slice must not treat refund_due as refund_paid.

The transport slice must not introduce customer_credit or customer_balance_entries.

## Baseline Facts

Owner baseline facts accepted for this session:

- Owner always commits and pushes manually.
- Local and repo are identical after push except ignored files.
- Owner statement clean, pushed, latest, or make verify pass is FACT.
- Local command output and owner statement win over GitHub or docs when there is conflict.
- Do not ask for git status, git log, git diff, git diff --check, or make verify as ritual.
- Git and make verify are used only when there is a real trigger.

Required source/context files used for this slice:

- docs/01_standards/0001_index.md
- docs/01_standards/0002_decision_policy.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0008_surplus_disposition_backend_foundation_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0009_surplus_refund_due_use_case_handoff.md
- docs/02_architecture/adr/0026_note_revision_surplus_disposition.md
- docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md
- docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md

## Locked Decisions Used

Decision source:

- ADR 0026
- ADR 0027
- ADR 0028
- handoff 0009
- current source proof from targeted and focused tests
- owner command output in this session

Locked domain decisions:

- refund_due-only remains active for this slice.
- refund_due is a surplus disposition decision.
- refund_due is not refund_paid.
- refund_due does not mean money already left the business.
- overpaid_pending is not revenue.
- overpaid_pending is not automatic refund paid.
- overpaid_pending is not automatic customer credit.
- customer_credit remains blocked until customer identity is locked.
- customer_balance_entries remains out of scope.
- refund_paid execution remains out of scope.
- audit_events and audit_event_snapshots are canonical for new finance-sensitive audit.
- audit_logs remains legacy or compatibility storage.
- UI is not financial truth.
- Controller and route are transport adapters.
- Report query is out of scope.
- PostgreSQL implementation is out of scope.
- Go API implementation is out of scope.

Permission decision:

- First refund_due surplus disposition transport is admin-only.
- It must use the existing admin page boundary.
- It must not reuse admin_transaction_entry capability.
- Reason: ADR 0027 explicitly says the first slice is admin-only and says not to reuse admin_transaction_entry capability for surplus disposition.

## Source Inspection Summary

Existing admin route shape was inspected.

File:

- routes/web/note.php

Relevant existing pattern:

- Admin notes group uses middleware:
  - auth
  - EnsureAdminPageAccess
  - app.shell
- Existing admin note mutation routes for payments, refunds, rows, and workspace update sit inside nested EnsureTransactionEntryAllowed.
- This nested capability middleware returns JSON 403 for admins without transaction capability.
- For refund_due transport, this nested middleware is intentionally not used because ADR 0027 rejects reuse of admin_transaction_entry capability.

Existing admin access boundary was inspected.

Files:

- app/Adapters/In/Http/Middleware/IdentityAccess/EnsureAdminPageAccess.php
- app/Application/IdentityAccess/Services/AdminPageRouteAccessDecision.php
- app/Application/IdentityAccess/Policies/AdminPageAccessPolicy.php

Relevant behavior:

- unauthenticated actor is redirected to login
- unknown actor is logged out and redirected to login
- kasir actor is redirected to cashier.dashboard
- non-admin actor is denied
- admin actor is allowed

Existing nearby transport patterns were inspected.

Files:

- app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php
- app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php
- app/Adapters/In/Http/Controllers/Note/RecordNotePaymentController.php
- app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php
- app/Adapters/In/Http/Requests/Note/RecordNotePaymentRequest.php
- app/Adapters/In/Http/Controllers/Note/Support/NoteRouteAreaResolver.php

Relevant pattern:

- Controllers are thin HTTP adapters.
- FormRequest validates input.
- Controllers call application use cases/services.
- Controllers redirect back with session errors on failure.
- Controllers redirect to note pages or index on success.
- Controller does not own finance truth.

Existing admin detail payload was inspected only enough to decide not to implement UI yet.

Files:

- app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php
- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- app/Application/Note/Services/NoteDetailNotePayloadBuilder.php
- app/Application/Note/Services/NoteDetailRevisionViewDataBuilder.php
- resources/views/shared/notes/show.blade.php
- resources/views/shared/notes/partials/payment-summary-actions.blade.php

Finding:

- Current admin detail payload does not yet expose pending surplus entries or refund_due action data.
- Therefore UI/Blade was intentionally not touched in this slice.
- Next UI work must start from backend data exposure blueprint, not from a button.

## Endpoint Contract Implemented

Route method:

    POST

Route path:

    /admin/notes/revision-settlements/{settlementId}/refund-due

Route name:

    admin.notes.revision-settlements.refund-due.store

Route boundary:

- inside admin notes route group
- uses auth
- uses EnsureAdminPageAccess
- uses app.shell
- intentionally outside EnsureTransactionEntryAllowed

Request fields:

    amount_rupiah
        required
        integer
        min:1

    reason
        required
        string

Route parameter:

    settlementId
        source note_revision_settlements id
        trimmed by controller before command creation

Transport-populated command fields:

    actorId
        current authenticated user id

    actorRole
        admin

    occurredAt
        null, so use case uses ClockPort

    sourceChannel
        web_admin

    requestId
        X-Request-Id header when present

    correlationId
        X-Correlation-Id header when present

Success behavior:

- redirect to admin.notes.show
- noteId comes from use case result data note_root_id
- flash success message:
  - Refund due berhasil dicatat.

Failure behavior:

- redirect back
- with input
- session error key:
  - refund_due
- fallback message:
  - Refund due gagal dicatat.

Unexpected throwable behavior:

- Controller does not swallow unexpected throwables.
- Use case already owns rollback around Throwable.
- Laravel handles unexpected error path.

## Files Created Or Changed

New files:

- app/Adapters/In/Http/Controllers/Admin/Note/CreateNoteRevisionSurplusRefundDueController.php
- app/Adapters/In/Http/Requests/Note/CreateNoteRevisionSurplusRefundDueRequest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

Modified file:

- routes/web/note.php

Not touched:

- resources/*
- public/assets/*
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueCommand.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueResult.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueGuard.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueAuditEventFactory.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueDispositionFactory.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueResultFactory.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php
- app/Adapters/Out/Reporting/*
- app/Ports/Out/AuditLogPort.php
- app/Adapters/Out/Audit/DatabaseAuditLogAdapter.php
- database/migrations/*
- customer_credit files
- customer_balance_entries files
- refund_paid execution files
- PostgreSQL implementation files
- Go API implementation files

## Implementation Details

Controller:

- namespace:
  - App\Adapters\In\Http\Controllers\Admin\Note
- class:
  - CreateNoteRevisionSurplusRefundDueController
- dependencies:
  - CreateNoteRevisionSurplusRefundDueRequest
  - CreateNoteRevisionSurplusRefundDueHandler
- creates:
  - CreateNoteRevisionSurplusRefundDueCommand
- does not compute:
  - unresolved pending amount
  - after pending amount
  - final refund amount
  - audit payload
  - disposition id
  - audit event id
- does not write:
  - audit_logs
  - audit_events directly
  - note_revision_surplus_dispositions directly
- source truth:
  - existing application use case

Request:

- namespace:
  - App\Adapters\In\Http\Requests\Note
- class:
  - CreateNoteRevisionSurplusRefundDueRequest
- validation:
  - amount_rupiah required integer min 1
  - reason required string
- authorize:
  - true
- reason:
  - authorization is handled by route middleware and application guard
  - use case still checks actorRole admin

Route:

- admin-only group route
- route uses existing admin page boundary
- not nested inside transaction.entry capability group
- route name intentionally specific to revision settlements and refund_due

Test:

- file:
  - tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
- proves:
  - admin can create refund_due from pending surplus settlement
  - validation requires valid amount and reason
  - use case failure redirects back with refund_due error
  - cashier cannot access admin refund_due route
  - admin without transaction capability can create refund_due
  - disposition row is created
  - audit_events row is created
  - audit_event_snapshots before and after rows are created
  - success redirect uses note_root_id from use case result

## Proof

Syntax proof:

    No syntax errors detected in app/Adapters/In/Http/Controllers/Admin/Note/CreateNoteRevisionSurplusRefundDueController.php
    No syntax errors detected in app/Adapters/In/Http/Requests/Note/CreateNoteRevisionSurplusRefundDueRequest.php

Targeted transport plus backend contract proof command:

    php -l app/Adapters/In/Http/Controllers/Admin/Note/CreateNoteRevisionSurplusRefundDueController.php
    php -l app/Adapters/In/Http/Requests/Note/CreateNoteRevisionSurplusRefundDueRequest.php
    php artisan test \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php \
      tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php

Targeted result:

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueControllerFeatureTest
    ✓ admin can create refund due from pending surplus settlement
    ✓ refund due request requires valid amount and reason
    ✓ use case failure redirects back with refund due error
    ✓ cashier cannot access admin refund due route
    ✓ admin without transaction capability can create refund due

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueHandlerTest
    ✓ rejects non admin actor
    ✓ rejects empty reason
    ✓ rejects missing or invalid pending settlement
    ✓ rejects amount greater than unresolved pending
    ✓ writes audit event snapshots disposition and updates pending
    ✓ rolls back audit event and disposition when second write fails

    PASS  Tests\Feature\Note\AdminNoteTransactionCapabilityFeatureTest
    ✓ admin without transaction capability can still read admin note pages
    ✓ admin without transaction capability is rejected from admin note mutation routes

    Tests: 13 passed (61 assertions)
    Duration: 5.97s

Focused blast-radius proof command:

    php artisan test \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php \
      tests/Feature/Note/AdminNoteTransactionCapabilityFeatureTest.php \
      tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php \
      tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php

Focused blast-radius result:

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueControllerFeatureTest
    ✓ admin can create refund due from pending surplus settlement
    ✓ refund due request requires valid amount and reason
    ✓ use case failure redirects back with refund due error
    ✓ cashier cannot access admin refund due route
    ✓ admin without transaction capability can create refund due

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueHandlerTest
    ✓ rejects non admin actor
    ✓ rejects empty reason
    ✓ rejects missing or invalid pending settlement
    ✓ rejects amount greater than unresolved pending
    ✓ writes audit event snapshots disposition and updates pending
    ✓ rolls back audit event and disposition when second write fails

    PASS  Tests\Feature\Note\AdminNoteTransactionCapabilityFeatureTest
    ✓ admin without transaction capability can still read admin note pages
    ✓ admin without transaction capability is rejected from admin note mutation routes

    PASS  Tests\Feature\Note\AdminNoteWorkspaceReplacementFeatureTest
    ✓ admin can open and submit closed note workspace replacement as revision
    ✓ admin workspace config json escapes script breaking sequences from stored fields
    ✓ admin workspace config json escapes script breaking sequences from product label

    PASS  Tests\Feature\Note\RecordClosedNoteRefundControllerFeatureTest
    ✓ cashier can record refund for closed note
    ✓ cashier cannot record refund for historical note outside cashier access window
    ✓ cashier cannot record refund for open partially paid row
    ✓ refund request requires reason
    ✓ refund allocates only selected rows

    Tests: 21 passed (122 assertions)
    Duration: 6.74s

## Current State

Refund_due backend-to-transport chain now has:

- canonical audit writer foundation
- surplus disposition migration
- surplus disposition DTOs
- surplus disposition reader/writer ports
- surplus disposition DB adapter
- refund_due application use case
- admin HTTP route
- admin HTTP request validation
- admin HTTP controller
- targeted transport proof
- focused route/controller adjacency proof

Still missing:

- admin UI data exposure for pending surplus
- admin UI form/button to submit refund_due
- clear UI payload contract
- report query for refund_due visibility
- refund_paid execution
- customer_credit
- customer_balance_entries
- cancel/reverse refund_due use case
- idempotency token for repeated submit
- explicit row locking/concurrency hardening for multi-admin high-contention disposition
- full make verify proof
- browser/manual QA proof

## Residual Gaps

Blocking before Blade/UI patch:

- Need decide how admin note detail payload obtains pending surplus data.
- Need decide view model shape for pending surplus disposition actions.
- Need decide whether pending surplus is shown in payment summary card or a separate surplus disposition card.
- Need decide exact form fields and validation display behavior.
- Need decide empty state behavior when no unresolved overpaid_pending settlement exists.
- Need targeted UI/render test before Blade patch.

Not blocking current transport closure:

- customer identity contract, because customer_credit is out of scope.
- customer_balance_entries, because customer_credit and credit_used are out of scope.
- refund_paid execution, because refund_due is not refund_paid.
- PostgreSQL implementation.
- Go API implementation.
- Report query.
- Full make verify, because this slice is not claiming final safe-state closure yet.

Technical debt or future hardening:

- Reader currently computes pending from settlement and active disposition sum but does not explicitly lock settlement row.
- No idempotency token for repeated refund_due submit.
- No cancel or reverse refund_due use case.
- No refund_paid execution use case.
- No report visibility for refund_due liability.
- No UI timeline read model for audit_events yet.

## Next Active Step

Recommended next step:

Design UI data exposure for admin pending surplus refund_due action.

Important:

Do not start by writing Blade.

Do not start by writing JavaScript.

Do not start from report query.

Do not duplicate backend finance logic in UI.

Do not compute final refund amount in UI.

Do not compute after_pending in UI.

Do not write audit_logs.

Do not implement refund_paid.

Do not implement customer_credit.

Do not create customer_balance_entries.

Do not implement PostgreSQL.

Do not implement Go API.

## Next Step Source Inspection Scope

Inspect only the files needed for UI data contract.

Start with:

- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- app/Application/Note/Services/NoteDetailNotePayloadBuilder.php
- app/Application/Note/Services/NoteDetailRevisionViewDataBuilder.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
- app/Ports/Out/Note/NoteRevisionSurplusDispositionReaderPort.php
- app/Application/Note/DTO/NoteRevisionSurplusPending.php
- app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php

Only after payload contract is decided, inspect:

- resources/views/shared/notes/show.blade.php
- resources/views/shared/notes/partials/payment-summary-actions.blade.php
- related rendered-detail tests if present

Avoid broad repo archaeology.

Avoid searching all report/refund/payment files unless a test failure requires it.

## Proposed UI Data Contract For Next Blueprint

Recommended shape to evaluate, not yet implemented:

Add a small application service or builder dedicated to surplus disposition view data.

Possible new service:

- app/Application/Note/Services/NoteRevisionSurplusDispositionActionViewDataBuilder.php

Possible injected dependency:

- NoteRevisionSurplusDispositionReaderPort

Input:

- note root id or current revision context

Output added to note payload:

    surplus_disposition
        has_pending_refund_due_action: bool
        pending_items: list

Each pending item:

    note_revision_settlement_id
    note_revision_id
    note_root_id
    surplus_rupiah
    disposed_rupiah
    unresolved_pending_rupiah
    disposition_type
    action_url
    amount_default_rupiah
    reason_required

Important constraints:

- action_url should be generated in controller/view layer or passed through a route-name plus params structure, depending current app style.
- unresolved_pending_rupiah must come from backend reader.
- UI can display unresolved_pending_rupiah.
- UI can submit amount_rupiah and reason.
- UI must not compute after_pending_rupiah.
- UI must not decide refund_paid.
- UI must not create customer credit.
- UI must not treat refund_due as money already paid out.

## Suggested UI Test Plan For Next Slice

Minimum tests before or with UI patch:

1. Admin note detail shows pending surplus refund_due action when unresolved overpaid_pending exists.

Expected proof:

- response OK
- sees pending amount formatted
- sees form action route admin.notes.revision-settlements.refund-due.store
- sees reason input
- sees amount input
- does not see refund_paid wording

2. Admin note detail does not show refund_due action when no unresolved pending surplus exists.

Expected proof:

- response OK
- no form action for refund_due
- no misleading refund_due button

3. Existing admin note detail/workspace tests still pass.

Expected proof:

- AdminNoteWorkspaceReplacementFeatureTest remains green

4. Existing transport tests still pass.

Expected proof:

- CreateNoteRevisionSurplusRefundDueControllerFeatureTest remains green

## Suggested Files To Touch Next Slice

Only if UI data blueprint is accepted:

- app/Application/Note/Services/NoteRevisionSurplusDispositionActionViewDataBuilder.php
- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- app/Application/Note/Services/NoteDetailNotePayloadBuilder.php
- resources/views/shared/notes/partials/payment-summary-actions.blade.php or a new partial
- tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php

Possible but avoid unless needed:

- app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php

Do not touch:

- backend refund_due use case
- report queries
- migration files
- refund_paid files
- customer_credit files
- customer_balance_entries files

## Suggested Commands For Next Slice

After UI data blueprint and patch:

    php -l app/Application/Note/Services/NoteRevisionSurplusDispositionActionViewDataBuilder.php
    php -l app/Application/Note/Services/NoteDetailPageDataBuilder.php
    php -l app/Application/Note/Services/NoteDetailNotePayloadBuilder.php
    php artisan test \
      tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php \
      tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php

Run broader only if these pass and the UI patch touches shared note detail behavior.

## Progress Snapshot

Final Goal Progress:

- 54 percent for refund_due admin-operable chain.
- Reason: backend use case and minimum admin transport are proven, but UI data exposure and action form are missing.

Main Process Progress:

- 75 percent for backend-to-admin-transport refund_due slice.
- Reason: transport exists and focused tests pass, but docs handoff and later UI/report visibility remain.

Sub-step Progress:

- 100 percent for minimum admin transport targeted plus focused proof.
- Proof:
  - targeted 13 passed / 61 assertions
  - focused 21 passed / 122 assertions

## Session Context Health

79 percent.

Reason:

The session now includes backend use case history, route boundary decisions, admin capability exclusion decision, transport implementation, focused proof, and next UI data exposure constraints.

A new session should start from this handoff to avoid repeating route/controller/use case analysis.

## Next Session Opening Prompt

    Kita lanjut HyperPOS dari edit/refund sniper handoff 0010.

    Baca berurutan:
    docs/01_standards/0001_index.md
    docs/01_standards/0002_decision_policy.md
    docs/99_archive/handoff/v2/edit_refund_sniper/README.md
    docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
    docs/99_archive/handoff/v2/edit_refund_sniper/0010_surplus_refund_due_admin_transport_handoff.md
    docs/02_architecture/adr/0026_note_revision_surplus_disposition.md
    docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md
    docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md

    Baseline FACT:
    - Saya selalu push setiap aksi.
    - Local dan repo identik setelah push kecuali ignored files.
    - Kalau saya menyatakan clean, pushed, latest, atau make verify pass, itu FACT.
    - Local command output dan owner statement menang atas GitHub/docs kalau ada konflik.
    - Jangan minta git status/log/diff/diff --check/make verify sebagai ritual.
    - Git dan make verify hanya dipakai kalau ada trigger nyata.

    Latest completed and proven:
    - Canonical audit writer foundation exists.
    - note_revision_surplus_dispositions migration exists.
    - Surplus disposition reader/writer adapter exists.
    - CreateNoteRevisionSurplusRefundDue backend use case exists.
    - Backend targeted proof passed 6 tests / 26 assertions in handoff 0009.
    - Focused backend contract proof passed 14 tests / 77 assertions in handoff 0009.
    - Minimum admin transport exists:
      - route admin.notes.revision-settlements.refund-due.store
      - request CreateNoteRevisionSurplusRefundDueRequest
      - controller CreateNoteRevisionSurplusRefundDueController
      - feature test CreateNoteRevisionSurplusRefundDueControllerFeatureTest
    - Targeted transport proof passed 13 tests / 61 assertions.
    - Focused blast-radius proof passed 21 tests / 122 assertions.

    Locked decisions:
    - refund_due-only remains active.
    - refund_due is a surplus disposition decision, not refund_paid.
    - refund_due does not mean money already left the business.
    - customer_credit remains blocked until customer identity is locked.
    - customer_balance_entries is out of scope.
    - refund_paid execution is out of scope.
    - PostgreSQL implementation is out of scope.
    - Go API implementation is out of scope.
    - audit_events and audit_event_snapshots are canonical for this new finance-sensitive audit.
    - audit_logs remains legacy/compatibility and must not become final finance audit truth.
    - Transport must call existing application use case.
    - Controller/UI/report must not compute final finance truth.
    - Admin transport intentionally does not reuse EnsureTransactionEntryAllowed because ADR 0027 says not to reuse admin_transaction_entry for surplus disposition.

    Current next active target:
    Design UI data exposure for admin pending surplus refund_due action.

    Required scope:
    - Do not start by writing Blade.
    - Do not start by writing JavaScript.
    - Do not start from report query.
    - First inspect only relevant detail payload/data builder/source reader files.
    - Decide payload shape before UI patch.
    - UI must display backend-generated pending surplus state.
    - UI must call admin.notes.revision-settlements.refund-due.store.
    - UI must not compute after_pending_rupiah.
    - UI must not treat refund_due as refund_paid.
    - UI must not introduce customer_credit or customer_balance_entries.

    Suggested first inspection targets:
    app/Application/Note/Services/NoteDetailPageDataBuilder.php
    app/Application/Note/Services/NoteDetailNotePayloadBuilder.php
    app/Application/Note/Services/NoteDetailRevisionViewDataBuilder.php
    app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
    app/Ports/Out/Note/NoteRevisionSurplusDispositionReaderPort.php
    app/Application/Note/DTO/NoteRevisionSurplusPending.php
    app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php

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

    Hard rule:
    One active step per response.
    No progress claim without proof.
    No broad repo archaeology.
    No UI implementation before payload blueprint.
