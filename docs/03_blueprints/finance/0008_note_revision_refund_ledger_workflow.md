# Note Revision Refund Ledger Workflow

## Status
Draft locked for design review.

## Metadata
- Date: 2026-05-12
- Scope: workflow for serious note edit, revision, refund, settlement, inventory, reporting version mode, UI, and future API

1. Purpose

This workflow defines the required order of work.

It does not describe every code step.

It defines the chain of domains that must be analyzed and fixed in order so each later fix stands on a stable earlier foundation.

The workflow is designed to prevent random patching.

2. Working Principle

Fix the deepest truth first.

Then fix projection.

Then fix report.

Then fix UI.

Then expose API.

Correct chain:

Decision
DB and domain contract
Core and application
Adapter
Projection
Reporting
UI
API
Audit and docs

Wrong chain:

UI
Controller
Query hack
Hope
Regret

The wrong chain is forbidden.

3. Phase 0 Decision Intake

Goal:

Understand the active problem before changing files.

Read only the minimum source of truth required for the active slice.

Required output:

FACT
GAP
ASSUMPTION
DECISION
RISK
NEXT

Required source groups:

Relevant ADR
Relevant blueprint
Relevant error log
Current source
Current tests
Local command output

Stop if:

Final behavior is not decided
DB ownership is unclear
Existing source contradicts docs and no decision is made
Test target is unknown
Affected file map is unknown
4. Phase 1 Source Reality Audit

Goal:

Prove how the current system behaves.

Audit groups:

Revision schema
Revision payload
Revision commit flow
Delete and rebuild paths
Payment carry forward
Refund carry forward
Refund effect flow
Inventory movement source contract
Current projection
Report source readers
UI calculation and preview

Required output:

Source map
Dangerous file list
Current behavior summary
Missing invariant list
Test inventory
Recommended first slice

No production code changes in this phase.

5. Phase 2 Domain Decision Lock

Goal:

Lock business decisions before DB or code.

Required decisions:

What is the source of truth for revision history
What is the source of truth for current active state
What happens when revised total is lower than carried forward paid amount
What happens when refund returns money but stock does not return
What happens when stock returns but money becomes credit
What happens to external purchase refund before procurement
What happens to external purchase refund after supplier payment
What report modes are supported
What fields must be auditable
What legacy uncertainty markers are allowed

Decision output must name:

Accepted behavior
Rejected behavior
Migration impact
Test impact
UI impact
Report impact
6. Phase 3 DB Additive Foundation

Goal:

Create storage for missing financial truth.

Work areas:

Revision settlement
Customer balance ledger
Optional inventory revision effect
Optional report version snapshot

Rules:

Prefer additive migration.
Do not destructively alter legacy data.
Do not add nullable fields without reason.
Add indexes for actual read paths.
Add database feature tests.
Add rollback method.
Do not create table before contract is clear.

Required proof:

Migration file exists
Migration test passes
Table names and fields match contract
Indexes are justified
No destructive migration
7. Phase 4 Core and Application Contract

Goal:

Introduce domain and application contracts without UI dependence.

Work areas:

Revision settlement DTO or core model
Customer balance entry DTO or core model
Refund effect plan DTO
Revision effect plan DTO
Report version mode value object
Settlement builder
Customer balance service
Preview use cases
Commit use cases

Rules:

Core cannot use Laravel.
Application cannot query DB directly.
Application orchestrates ports.
Domain invariants are tested in unit tests.
Application orchestration is tested in feature tests if DB involved.

Required proof:

Unit tests for invariants
Feature tests for transaction behavior
Failure path test
Rollback test when transaction fails
8. Phase 5 Adapter and Persistence

Goal:

Persist new contracts safely.

Work areas:

DB writer adapter
DB reader adapter
Port binding
Query mapper
Transaction integration
Audit integration

Rules:

Adapter maps only persistence details.
Adapter does not decide business policy.
Writer uses explicit fields.
Reader preserves integer rupiah.
No raw unvalidated JSON for financial truth.

Required proof:

Adapter feature tests
Port binding proof
Query output proof
Error path proof
9. Phase 6 Revision Commit Hardening

Goal:

Make revision commit produce immutable business truth.

Work areas:

Create revision snapshot
Create settlement snapshot
Create customer balance entry if surplus exists
Update current revision pointer
Rebuild or update active work items only after snapshot is safe
Rebuild current payment allocation only if historical settlement is safe
Sync projection
Record audit

Rules:

Commit must be transaction bound.
Current pointer update must not happen before revision persistence.
Active row rebuild must not destroy unprotected history.
Overpaid must not be hidden.
Failed commit must roll back all new writes.

Required proof:

Equal total revision test
Upward revision test
Downward revision test
Failure rollback test
Previous revision remains readable
Current revision pointer updated
Audit recorded
10. Phase 7 Refund Plan Hardening

Goal:

Make refund explicit as effect plan.

Work areas:

Refund eligibility
Refund amount calculation
Stock return decision
Receivable cancellation
Service cancellation
External purchase effect
Customer balance effect
Commit transaction
Projection sync
Audit

Rules:

Plan preview must be backend generated.
Commit must use server plan or server recomputation.
UI selected rows are intent only.
Money effect and stock effect are separate.
Unpaid row cancellation can be zero money.
Double refund is rejected.
External purchase effect cannot be guessed.

Required proof:

Paid row refund test
Unpaid row zero money cancellation test
Stock return true test
Stock return false test
Service only cancellation test
External purchase pending procurement test
External purchase already procured test
Double refund rejection test
11. Phase 8 Current Projection Hardening

Goal:

Keep fast reads correct after revision, refund, payment, stock effect, and customer balance.

Work areas:

note_history_projection
payment status resolver
outstanding resolver
refund count
open line count
close line count
surplus or customer balance display if needed
projection rebuild command if needed

Rules:

Projection is derived.
Projection is not historical truth.
Projection sync occurs inside successful mutation flow.
Projection can be rebuilt or verified.
Projection does not hide overpaid as unpaid.

Required proof:

Projection after payment
Projection after revision
Projection after refund
Projection after downward revision
Projection after zero money cancellation
Projection after customer credit creation
12. Phase 9 Report Version Mode

Goal:

Make report semantics explicit.

Work areas:

Query DTO
Source reader port
Current report source
Original report source
Revision report source
As of report source
Ledger actual source
Export builders
View labels

Rules:

Every report query must carry version mode.
Export output must show version mode.
Current projection is used only for current mode.
Revision mode uses revision snapshot and settlement.
Ledger actual mode uses ledger events.
As of mode uses event or effective dates consistently.
Unknown legacy precision must be marked.

Required proof:

Current mode test
Original mode test
Revision mode test
As of mode test
Ledger actual mode test
PDF label proof
Excel label proof
Reconciliation proof
13. Phase 10 UI Integration

Goal:

Make UI reflect backend truth.

Work areas:

Revision preview UI
Refund preview UI
Customer balance display
Report mode selector
Error messages
Progressive enhancement
No JavaScript final truth

Rules:

UI can render backend plan.
UI can request backend preview.
UI cannot compute final refund amount.
UI cannot compute final stock return truth.
UI cannot hide server side authorization gap.
Fallback submit must work.
API and Blade must share use cases.

Required proof:

Blade render test
HTTP submit test
JavaScript behavior if covered
Fallback no JavaScript path
Error validation path
Authorization path
14. Phase 11 API Integration

Goal:

Expose stable use cases to API without duplicating domain logic.

Work areas:

Preview revision endpoint
Commit revision endpoint
Preview refund endpoint
Commit refund endpoint
Version timeline endpoint
Version detail endpoint
Versioned report endpoint

Rules:

API controller is transport.
API request validates shape.
Same use case as Blade.
Response must not leak sensitive raw data.
Error envelope must be stable.

Required proof:

API preview test
API commit test
API validation test
API authorization test
API and Blade parity test if practical
15. Phase 12 Final Audit and Docs

Goal:

Close the slice safely.

Required output:

Final source map
Files changed
Tests run
Proof output
Remaining gaps
Docs updated
Decision changes recorded
Next safest slice

No progress may increase without proof.

16. Recommended Chain Order

Recommended big chain:

Revision settlement and customer balance foundation
Revision commit with explicit settlement
Current projection hardening
Refund effect plan split
Inventory and COGS trace hardening
Report version mode foundation
UI preview and commit integration
API parity

Reason:

Overpaid and carry forward are the core blocker.
Refund depends on settlement and customer balance.
Report depends on revision settlement.
UI depends on backend plan.
API depends on stable use case.
17. Required Stop Review After Each Chain

After each chain, stop and report:

What is proven
What is not proven
What changed
What did not change
What new risk appeared
Whether the next chain is safe

Do not continue automatically into the next chain if the previous chain lacks proof.

