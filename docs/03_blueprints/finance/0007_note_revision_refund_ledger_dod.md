# Note Revision Refund Ledger Definition of Done

## Status
Draft locked for design review.

## Metadata
- Date: 2026-05-12
- Scope: DoD for note edit, revision, refund, settlement carry forward, customer balance, inventory, reporting version mode, UI, and future API

1. Global DoD

A slice is done only when all applicable items are proven.

Required:

Active goal is stated.
Source map is stated.
Decision used is stated.
Files changed are listed.
Files intentionally not touched are listed.
DB impact is stated.
Hexagonal boundary is preserved.
RED proof or source gap proof exists.
Targeted GREEN tests pass.
Focused blast radius tests pass.
Projection impact is checked if affected.
Report impact is checked if affected.
UI impact is checked if affected.
API impact is checked if affected.
Audit impact is checked if affected.
Docs are updated.
Residual gaps are stated.
No global safe claim is made without full suite proof.
2. Boundary DoD
2.1 Core

Core is valid only if:

It contains no Laravel dependency.
It contains no DB facade.
It contains no request or response object.
It contains no auth or session dependency.
It protects invariants.
It uses integer rupiah for money.
It rejects invalid states.
2.2 Application

Application is valid only if:

It orchestrates use cases.
It depends on ports for persistence.
It owns transaction orchestration where needed.
It does not query DB directly.
It does not depend on Blade.
It does not depend on JavaScript.
It returns stable result objects or DTOs.
2.3 Adapter

Adapter is valid only if:

It maps persistence or transport details.
It does not decide domain policy.
It preserves integer money.
It maps nullable fields intentionally.
It does not swallow errors silently.
It has feature coverage for critical persistence.
2.4 Transport

Transport is valid only if:

Controller only resolves route, actor, and request.
FormRequest only validates and normalizes input shape.
API request and Blade form call the same application use case.
UI does not decide financial truth.
Server side authorization is enforced.
3. DB DoD

A DB change is done only if:

Migration is additive unless a destructive ADR exists.
Table name matches domain language.
Columns match locked contract.
Money fields are unsigned integer or signed integer only when negative is intentional.
Indexes match read paths.
Foreign keys are added when practical.
Rollback exists.
Migration test passes.
Legacy compatibility is stated.
Backfill requirement is stated.

A DB change is not done if:

It adds generic JSON for critical financial truth without validation.
It hides surplus in note status text.
It requires destructive change without migration plan.
It creates nullable important fields without reason.
4. Revision DoD

Revision work is done only if tests prove:

Initial revision can be created.
Next revision number is monotonic.
Parent revision id is preserved.
Current revision pointer is updated.
Old revision remains readable.
New revision lines are readable.
Revision header snapshot is preserved.
Revision line snapshot is preserved.
Actor is captured.
Actor role is captured if required by the slice.
Reason is captured.
Revision total is correct.
Revision line count is correct.
Failed revision commit rolls back.

Additional proof required for financial revision:

Settlement snapshot exists.
Carried forward paid amount is correct.
Carried forward refunded amount is correct.
Outstanding is correct.
Surplus is correct.
Customer balance entry is created when needed.
5. Settlement DoD

Settlement work is done only if tests prove:

Equal revision total preserves paid state.
Upward revision creates outstanding.
Downward revision creates surplus.
Surplus does not disappear.
Surplus is not treated as unpaid.
Refund due can be created.
Customer credit can be created.
Overpaid pending can be created.
Existing payment history remains traceable.
Existing refund history remains traceable.
Allocation rollback is safe if commit fails.
Current projection reflects settlement.

Unsupported state must:

Reject safely
Roll back cleanly
Return user safe error
Document residual gap
6. Customer Balance DoD

Customer balance work is done only if tests prove:

Overpaid pending entry can be created.
Refund due entry can be created.
Customer credit entry can be created.
Refund paid entry can reduce remaining balance.
Credit used entry can reduce remaining balance.
Partial refund works.
Partial credit use works.
Balance cannot go negative unless explicit adjustment allows it.
Entry references source type and source id.
Entry references actor and reason.
Balance appears in relevant read model or report if in scope.
7. Refund DoD

Refund work is done only if tests prove:

Paid row refund creates money effect.
Unpaid row cancellation can commit with zero money.
Refund amount cannot exceed refundable amount.
Double refund is rejected.
Stock return true increases stock through inventory movement.
Stock return false does not increase stock.
Service only cancellation does not create stock movement.
External purchase before procurement follows locked decision.
External purchase after supplier payment follows locked decision.
Refund commit is transaction safe.
Refund audit references affected rows.
Refund audit references affected payment or refund ids.
Current projection updates.
UI can render backend refund plan if UI is touched.
8. Inventory and COGS DoD

Inventory and COGS work is done only if tests prove:

Original stock out is recorded.
Revision reversal is recorded.
New revision stock out is recorded.
Refund stock return is recorded only when selected.
No stock return path does not mutate stock.
Negative stock is blocked.
Unit cost snapshot is traceable.
Historical COGS is stable.
Current COGS can differ from historical COGS intentionally.
Inventory projection can be rebuilt from ledger.
Movement source type and source id are traceable to revision or refund effect.
9. Report DoD

Report work is done only if tests prove:

Current mode works.
Original mode works.
Revision mode works.
As of mode works.
Ledger actual mode works.
Mode is explicit in query DTO.
PDF output labels selected mode.
Excel output labels selected mode.
Current mode may use current projection.
Historical mode does not silently use current projection.
Totals reconcile with selected mode.
Unknown legacy precision is marked, not fabricated.
10. UI DoD

UI work is done only if tests or documented manual proof show:

UI displays backend generated revision plan.
UI displays backend generated refund plan.
UI supports zero money cancellation when backend allows it.
UI does not decide final refund amount.
UI does not decide final stock return truth.
UI displays customer balance or surplus when in scope.
UI displays report version mode when in scope.
Fallback form submit works.
Server side validation blocks invalid mutation.
Server side authorization blocks unauthorized mutation.
11. API DoD

API work is done only if tests prove:

API preview uses same application service as Blade.
API commit uses same application service as Blade.
API validation is transport only.
API authorization is server side.
API response envelope is stable.
API error messages are redacted and user safe.
API does not expose raw internal payload unless explicitly designed.
12. Audit DoD

Audit work is done only if audit event captures:

Actor id
Actor role
Reason
Event type
Note id
Revision id when relevant
Payment id when relevant
Refund id when relevant
Inventory movement id or effect id when relevant
Customer balance entry id when relevant
Before state or source snapshot when relevant
After state or target snapshot when relevant

Audit is not done if:

Reason is generic when user supplied reason is required.
Actor role is missing for sensitive mutation.
Event cannot reconstruct the business change.
Sensitive raw payload leaks to logs or UI.
13. Docs DoD

Docs are done only if:

Blueprint decision is updated.
Workflow impact is updated.
DoD impact is updated.
Error log status is updated if issue is being closed.
Residual gaps are stated.
Proof commands are recorded.
Legacy docs conflict is stated if discovered.
AI reading map is updated if file priority changes.
14. Closure DoD

A slice can be closed only after final response includes:

Final goal progress
Main process progress
Sub step progress
Files changed
Tests run
Proof output
Remaining gaps
Next safest step

Progress must not increase without proof.

