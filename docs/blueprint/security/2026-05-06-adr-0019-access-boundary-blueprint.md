# ADR-0019 Access Boundary Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any error log as fixed.

This document exists to make ADR-0019 execution rigid enough for CLI-based implementation later.

## Source Of Truth

- docs/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/audit/codex-security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/error_log/009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
- docs/error_log/015-refunded-notes-expose-edit-workspace.md
- docs/error_log/016-unauthenticated-admin-capability-toggle-endpoints.md
- docs/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md
- docs/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md
- docs/error_log/020-admin-note-actions-bypass-transaction-capability.md
- docs/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
- docs/error_log/027-admin-invoice-creation-bypasses-transaction-entry-gate.md
- docs/error_log/029-cashier-create-page-leaks-total-note-count.md
- User owner decisions in planning session
- User command output from local repository
- Current source code at execution time

## Proof Available Before This Blueprint

Repository proof from user command output:

- Branch: main
- HEAD: b39a0792
- Recent commits shown: commit 1679 to commit 1675
- Route surface includes admin notes, cashier notes, identity access capability toggle, procurement supplier invoice, supplier payment proof, product, expense, payment, refund, workspace, and attachment routes

Route proof confirms ADR-0019 affects at least these route groups:

- admin/notes
- cashier/notes
- identity-access/admin-transaction-capability
- admin/procurement/supplier-invoices
- admin/procurement/supplier-payment-proof-attachments
- admin/procurement/supplier-payments
- cashier/notes/workspace
- notes/workspace/store

## Non Goals

- Do not patch application source from this document alone.
- Do not create or modify authorization code before characterization tests.
- Do not modify seeder structure in this ADR-0019 implementation.
- Do not solve output encoding, unsafe URL, public helper, MIME, or attachment content-type here.
- Do not solve payment allocation race conditions here.
- Do not modify finance settlement semantics here unless an ADR-0019 access test proves the boundary cannot work without reading domain eligibility.
- Do not mark any docs/error_log finding as fixed without test proof and owner acceptance.

## Explicit Scope

This blueprint covers access, authorization, capability, date window, supplier invoice gate, route guard, and cashier disclosure boundary.

This blueprint does not replace ADR-0018.

ADR-0019 decides whether an actor may enter a route or flow.

ADR-0018 decides whether the requested domain operation is valid after access is allowed.

The final mutation flow must pass both layers:

1. Access layer
2. Domain eligibility layer

## Owner Decisions Locked In This Planning Session

### Roles

Current active roles are only:

- admin
- kasir

No owner role or super-admin role exists in the active implementation scope.

A future owner or super-admin role may be added later, but this ADR-0019 implementation must not depend on it.

### Capability Toggle Actor

Admin may enable or disable admin transaction capability.

This is allowed for the current two-role model.

Capability toggle must be authenticated, authorized, CSRF protected, and audited.

### Capability Toggle Reason

Manual reason is not required for capability toggle.

Automatic audit context is enough for now.

The audit must still capture actor, target actor, before state, after state, timestamp, and action.

### Capability Usage Audit

All sensitive actions already go through logging or audit.

ADR-0019 adds this distinction:

- Some actions need normal audit only.
- Some high-risk actions need capability usage context.
- Some actions need manual reason because the domain mutation itself requires reason.

Capability usage audit is required for high-risk actions only.

High-risk actions for this ADR:

- note payment
- note refund
- note workspace revision or update
- note row add when it affects money, stock, projection, or reporting
- note reopen
- supplier invoice create
- supplier invoice update
- supplier invoice void
- supplier invoice receive
- supplier payment record
- supplier payment reverse
- supplier receipt reverse
- supplier payment proof upload
- supplier payment proof download or serving

### Manual Reason Policy

Manual reason requirement is not decided by capability alone.

Manual reason remains required for domain actions that already need human explanation, including but not limited to:

- edit or correction
- delete
- refund
- note revision when reason is part of official lifecycle
- employee debt mutation when current rules require reason
- sensitive reversal
- correction affecting money, stock, status, report, or audit trail

Capability toggle itself does not require manual reason for now.

### Cashier Date Window

Cashier note access is limited to today and yesterday.

The date basis is note business date or transaction date used by the domain.

The window uses Laravel application timezone.

Client-submitted date filters are not trusted as authorization truth.

The server must enforce the window.

### Out Of Scope Cashier Access Response

If cashier accesses a note outside the allowed date window, return direct HTTP 403.

Reason:

- This is a boundary violation.
- The actor is authenticated but not authorized for the requested resource.
- Redirecting may hide the violation and weaken test clarity.

### Admin Read Access

Admin can read all notes without transaction capability.

Admin read access is not the same as admin mutation authority.

### Admin Mutation Access

Admin mutation of transaction-sensitive note or procurement state requires active transaction capability.

Transaction-sensitive means the action can affect one or more of:

- money
- stock
- line items
- payment
- refund
- note revision
- current projection
- note status
- supplier invoice
- supplier receipt
- supplier payment
- payment proof
- reporting-relevant state
- audit-sensitive state

### Admin Metadata Only Correction

Admin metadata-only correction does not require transaction capability.

It still requires admin access and audit.

Metadata-only means the mutation does not change:

- money
- stock
- line items
- payment
- refund
- note status
- current projection
- supplier invoice amount
- supplier payment
- reporting-relevant financial values

Examples of metadata-only fields may include customer name, display note, description, or business date only if the date change is not used for financial/reporting period mutation.

If a date change affects reporting period, payment due period, cashier window, or financial report grouping, it is not metadata-only.

### Cashier Mutation Boundary

Cashier remains allowed to use official edit, refund, and revision flows for paid, closed, or refunded notes when all of these are true:

- note is within cashier date window
- route access guard allows the actor
- official audited lifecycle is used
- ADR-0018 domain policy allows the requested operation
- current projection or domain eligibility confirms the target row or note can be mutated

ADR-0019 must not globally block cashier edit or refund.

### ADR-0019 And ADR-0018 Boundary

ADR-0019 is the access boundary.

ADR-0018 is the domain eligibility and lifecycle boundary.

If an actor fails ADR-0019, the request must be denied before domain mutation.

If an actor passes ADR-0019, the request still must pass ADR-0018 domain rules before mutation.

This prevents two opposite failures:

- unauthorized user reaching sensitive mutation
- authorized user performing invalid domain mutation

### Supplier Invoice Gate

All supplier invoice and supplier payment proof sensitive flows require admin transaction capability.

This includes:

- supplier invoice create
- supplier invoice update
- supplier invoice void
- supplier invoice receive
- supplier payment record
- supplier payment reverse
- supplier receipt reverse
- supplier payment proof upload
- supplier payment proof download or serving

Read-only admin list, detail, and table pages may require admin access only unless they expose or serve protected proof attachment data.

Payment proof download or serving must be watched even though it is read-like because proof files are sensitive operational evidence.

### Cashier Count Disclosure

Cashier create page must not expose global note count.

Decision: remove global note count from cashier create page unless a clear cashier workflow need exists.

If a count is needed later, it must be scoped to cashier access window and computed through the same access policy.

Current direction: no global count for cashier.

## Error Log Coverage

| Error Log | Covered By This Blueprint | Notes |
|---|---|---|
| 009 cashiers can rewrite closed paid notes via workspace update | Yes | Access guard plus official lifecycle boundary |
| 015 refunded notes expose edit workspace | Yes | UI exposure must not be trusted; server-side guard required |
| 016 unauthenticated admin capability toggle endpoints | Yes | Capability toggle must require auth, authorization, CSRF, audit |
| 018 refunded notes bypass cashier closed note guards | Yes | Cashier scope and status-aware access guard |
| 019 cashiers can list historical closed notes by date | Yes | Server-side date window |
| 020 admin note actions bypass transaction capability | Yes | Admin mutation requires transaction capability |
| 022 cashier refund route bypasses note access guard | Yes | Refund route must pass actor access and domain eligibility |
| 027 admin invoice creation bypasses transaction entry gate | Yes | Supplier invoice gate |
| 029 cashier create page leaks total note count | Partially | Disclosure boundary here; output/security details may also belong to ADR-0020 |

## Access Model

### Actor Types

| Actor | Read Note Scope | Mutation Scope | Transaction Capability Needed |
|---|---|---|---|
| Guest | None | None | Not applicable |
| Kasir | Today and yesterday only | Official cashier flows inside date window | No, kasir has normal transaction-entry role capability by role |
| Admin without transaction capability | All notes read | Metadata-only correction if allowed and audited | Cannot do transaction-sensitive mutation |
| Admin with transaction capability | All notes read | Transaction-sensitive mutation if domain policy allows | Yes, capability must be active |
| Future owner or super-admin | Deferred | Deferred | Deferred |

### Decision Rule

Access is granted only when:

1. actor is authenticated
2. actor role is allowed for the route group
3. actor scope allows target resource
4. required capability exists when action is transaction-sensitive
5. domain eligibility layer confirms the requested mutation is valid

## Route Matrix

### Identity Access Routes

| Route Group | Methods | Required Access | Manual Reason | Audit |
|---|---|---|---|---|
| identity-access/admin-transaction-capability/enable | POST | authenticated admin | No | Required |
| identity-access/admin-transaction-capability/disable | POST | authenticated admin | No | Required |

Required enforcement:

- route must be behind auth middleware
- route must reject guest
- route must reject kasir
- route must allow admin
- request must be CSRF protected under web middleware
- mutation must write audit record
- audit must include actor, target user, old capability state, new capability state, and timestamp

### Admin Note Routes

| Route Group | Mutation Sensitive | Required Access |
|---|---:|---|
| admin/notes index table show | No | admin read access |
| admin/notes products lookup | No or low | admin read access unless it mutates |
| admin/notes workspace draft GET | No | admin read access |
| admin/notes workspace draft POST | Depends | transaction capability if creates or changes transaction-sensitive draft |
| admin/notes note payments POST | Yes | admin plus transaction capability |
| admin/notes note refunds POST | Yes | admin plus transaction capability plus domain eligibility |
| admin/notes note reopen POST | Yes | admin plus transaction capability plus domain eligibility |
| admin/notes note rows POST | Yes | admin plus transaction capability plus domain eligibility |
| admin/notes note workspace PATCH | Yes | admin plus transaction capability plus domain eligibility |
| admin/notes note workspace edit GET | Read entry into sensitive flow | admin read access, but page must not expose mutation actions that bypass capability |

Important:

- GET page exposure is not enough security.
- POST and PATCH mutations must enforce policy server-side.
- Admin without transaction capability may see note details but must not mutate money, stock, lines, payment, refund, revision, or projection.

### Cashier Note Routes

| Route Group | Required Access | Notes |
|---|---|---|
| cashier/notes index table | kasir plus date-window scope | Must not list out-of-window notes |
| cashier/notes show | kasir plus date-window scope | Out-of-window returns 403 |
| cashier/notes workspace create | kasir | Must not expose global note count |
| cashier/notes workspace draft GET POST | kasir | Draft must not bypass policy |
| cashier/notes payments POST | kasir plus date-window plus domain eligibility | Payment domain rules still apply |
| cashier/notes refunds POST | kasir plus date-window plus domain eligibility | Must reject forged or historical rows |
| cashier/notes rows POST | kasir plus date-window plus domain eligibility | Must respect paid/closed lifecycle |
| cashier/notes workspace PATCH | kasir plus date-window plus domain eligibility | Official audited revision flow |
| cashier corrections service-only or status POST | kasir plus date-window plus domain eligibility | Manual reason if correction policy requires it |

### Procurement Routes

| Route Group | Mutation Sensitive | Required Access |
|---|---:|---|
| admin/procurement supplier invoices list table show | No | admin read access |
| admin/procurement supplier invoices create GET | No | admin read access |
| admin/procurement supplier invoices POST | Yes | admin plus transaction capability |
| admin/procurement supplier invoices PUT | Yes | admin plus transaction capability |
| admin/procurement supplier invoices receive POST | Yes | admin plus transaction capability |
| admin/procurement supplier invoices revise GET | Read entry into sensitive flow | admin read access, mutation later requires capability |
| admin/procurement supplier invoices void POST | Yes | admin plus transaction capability |
| admin/procurement supplier payments POST | Yes | admin plus transaction capability |
| admin/procurement supplier payments reverse POST | Yes | admin plus transaction capability |
| admin/procurement supplier receipts reverse POST | Yes | admin plus transaction capability |
| admin/procurement supplier payments proof upload POST | Yes | admin plus transaction capability |
| admin/procurement supplier payment proof attachment show GET | Sensitive read | admin plus transaction capability |

## Server Side Policy Boundary

Implementation should avoid scattered checks inside Blade.

The preferred boundary order:

1. route middleware handles auth and broad role area
2. controller or application service checks route-specific policy
3. application use case checks domain eligibility
4. audit service records sensitive attempts and successful sensitive mutations
5. view only reflects allowed actions, but never becomes the security boundary

UI hiding is presentation only.

Server-side policy is the boundary.

## Proposed Policy Components

Exact class names may be adjusted after source inspection.

Suggested components:

### TransactionCapabilityPolicy

Purpose:

- decide whether actor can perform transaction-sensitive admin mutation
- distinguish admin read from admin mutation
- treat kasir transaction-entry as role-permitted inside cashier scope

Input:

- actor id
- actor role
- action name
- target resource type
- target resource id if available

Output:

- allowed or denied
- denial reason code
- whether capability usage audit is required

### CashierNoteAccessPolicy

Purpose:

- enforce cashier note access window
- ensure note business date is today or yesterday in app timezone
- reject out-of-window note access with 403

Input:

- actor
- note id
- note business date
- action name

Output:

- allowed or denied
- denial reason code

### NoteMutationAccessPolicy

Purpose:

- combine actor policy and route action sensitivity
- prevent admin without transaction capability from mutation
- prevent cashier out-of-window mutation
- delegate domain validity to ADR-0018 use cases

Input:

- actor
- note
- action name

Output:

- access allowed or denied before domain mutation

### ProcurementMutationAccessPolicy

Purpose:

- gate supplier invoice and supplier payment proof sensitive actions
- require admin transaction capability for mutations and sensitive proof serving

Input:

- actor
- supplier invoice or payment id
- action name

Output:

- access allowed or denied

### CapabilityTogglePolicy

Purpose:

- allow authenticated admin to toggle transaction capability
- reject guest
- reject kasir
- audit every toggle

Input:

- actor
- target user
- requested state

Output:

- allowed or denied
- audit payload

## Audit Requirements

### Universal Sensitive Audit

All sensitive mutations should be logged or audited.

Audit payload should include:

- acting user id
- acting role
- target resource type
- target resource id
- action name
- timestamp
- route or use case name
- request context sufficient for investigation
- before state when available
- after state when available
- denial reason for denied sensitive attempt when useful

### Capability Toggle Audit

Must include:

- actor id
- actor role
- target user id
- old capability state
- new capability state
- timestamp
- action enable or disable

Manual reason is not required.

### Capability Usage Audit

Required for high-risk actions.

Must include:

- actor id
- actor role
- active transaction capability at action time
- action name
- target resource type
- target resource id
- timestamp
- success or denied result when useful

Manual reason is only required if the underlying domain action requires it.

### Domain Manual Reason

Manual reason remains required for actions already defined as requiring human explanation.

Examples:

- refund
- correction
- delete
- edit or revision if existing lifecycle requires reason
- reversal
- employee debt mutation when current policy requires reason

## Characterization Test Matrix

Tests must be written before production patch for each slice.

### Slice 1 Admin Note Mutation Capability

Goal:

- prove admin role is not enough for transaction-sensitive note mutation

Required tests:

1. admin without transaction capability cannot POST admin note payment
2. admin with transaction capability can POST admin note payment when domain validation passes
3. admin without transaction capability cannot POST admin note refund
4. admin with transaction capability can reach refund use case when domain validation passes
5. admin without transaction capability cannot PATCH admin note workspace update
6. admin with transaction capability can reach workspace update when domain validation passes
7. admin can GET admin note detail without transaction capability
8. admin metadata-only correction does not require transaction capability, if metadata-only route exists

Expected status:

- denied mutation returns 403
- allowed mutation follows existing route behavior
- read page remains accessible to admin

### Slice 2 Cashier Date Window

Goal:

- prove cashier cannot read or mutate out-of-window note

Required tests:

1. cashier can read today note
2. cashier can read yesterday note
3. cashier cannot read note older than yesterday
4. cashier cannot post payment for older-than-yesterday note
5. cashier cannot post refund for older-than-yesterday note
6. cashier cannot open workspace edit for older-than-yesterday note
7. cashier cannot patch workspace update for older-than-yesterday note
8. out-of-window denial returns 403

Expected status:

- out-of-window read and mutation return 403

### Slice 3 Supplier Invoice And Proof Gate

Goal:

- prove procurement sensitive flows require transaction capability

Required tests:

1. admin without transaction capability cannot create supplier invoice
2. admin with transaction capability can create supplier invoice when domain validation passes
3. admin without transaction capability cannot update supplier invoice
4. admin without transaction capability cannot void supplier invoice
5. admin without transaction capability cannot receive supplier invoice
6. admin without transaction capability cannot record supplier payment
7. admin without transaction capability cannot upload supplier payment proof
8. admin without transaction capability cannot download or serve supplier payment proof attachment
9. admin read-only supplier invoice list/detail remains accessible without transaction capability

Expected status:

- denied sensitive action returns 403
- read-only action remains accessible to admin

### Slice 4 Capability Toggle Authorization And Audit

Goal:

- prove capability toggle endpoint is protected and audited

Required tests:

1. guest cannot enable capability
2. guest cannot disable capability
3. kasir cannot enable capability
4. kasir cannot disable capability
5. admin can enable capability
6. admin can disable capability
7. enable writes audit record
8. disable writes audit record
9. audit contains actor, target, old state, new state, timestamp

Expected status:

- guest redirects or returns auth-required according to existing auth pattern
- kasir returns 403
- admin succeeds
- audit exists

### Slice 5 Cashier Count Disclosure

Goal:

- prove cashier create page no longer exposes global note count

Required tests:

1. cashier create page does not include global note count
2. default customer naming does not require global note count
3. if any count remains, it is scoped to cashier window
4. admin pages may keep global count only where authorized

Expected status:

- cashier page renders without global count leak

## Implementation Order

The safest order:

1. Inventory exact classes used by affected routes
2. Add tests for Slice 1 admin note mutation capability
3. Patch minimal policy enforcement for admin note mutation
4. Run targeted tests for Slice 1
5. Add tests for Slice 2 cashier date window
6. Patch cashier note access policy
7. Run targeted tests for Slice 2
8. Add tests for Slice 3 supplier invoice and proof gate
9. Patch procurement access policy
10. Run targeted tests for Slice 3
11. Add tests for Slice 4 capability toggle audit
12. Patch capability toggle authorization or audit if missing
13. Run targeted tests for Slice 4
14. Add tests for Slice 5 cashier count disclosure
15. Patch cashier create page or data provider
16. Run targeted tests for Slice 5
17. Run ADR-0019 blast-radius test suite
18. Update docs/error_log only after proof
19. Commit only after owner reviews diff and proof

## CLI Workflow

Workflow is flexible because each error fix may reveal a new failure.

However, these rules are fixed:

1. Start every slice with git status.
2. Read relevant ADR and error log before editing.
3. Read route and controller source before editing.
4. Add red characterization test first.
5. Run targeted test and confirm it fails for the expected reason.
6. Patch the smallest safe boundary.
7. Run the targeted test again.
8. Run local blast-radius tests for affected cluster.
9. Show git diff.
10. Update docs/error_log only with proof.
11. Commit only after owner approval.

## Required Commands For Execution Sessions

### Start Session Snapshot

Run before any implementation slice:

    git status --short
    git rev-parse --abbrev-ref HEAD
    git log --oneline -5

### Route Snapshot

Run when route behavior is involved:

    php artisan route:list | grep -Ei "note|refund|capabil|supplier|invoice|cashier|proof|attachment|payment" || true

### ADR And Error Log Snapshot

Run before selecting a slice:

    sed -n '1,260p' docs/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
    sed -n '1,220p' docs/error_log/009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
    sed -n '1,220p' docs/error_log/015-refunded-notes-expose-edit-workspace.md
    sed -n '1,220p' docs/error_log/016-unauthenticated-admin-capability-toggle-endpoints.md
    sed -n '1,220p' docs/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md
    sed -n '1,220p' docs/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md
    sed -n '1,220p' docs/error_log/020-admin-note-actions-bypass-transaction-capability.md
    sed -n '1,220p' docs/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
    sed -n '1,220p' docs/error_log/027-admin-invoice-creation-bypasses-transaction-entry-gate.md
    sed -n '1,220p' docs/error_log/029-cashier-create-page-leaks-total-note-count.md

### Exact Class Discovery

Run before writing tests:

    grep -RIn "admin.notes.*payments\|admin.notes.*refunds\|admin.notes.*workspace\|cashier.notes.*payments\|cashier.notes.*refunds\|cashier.notes.*workspace\|admin-transaction-capability\|supplier-invoices\|supplier-payment-proof" routes app tests 2>/dev/null || true

### Test Run Pattern

Run targeted tests first.

Then run blast-radius tests.

Suggested targeted command per slice must be created after exact test files are chosen.

## DoD For Planning

Planning is complete only when:

- impacted files are categorized
- owner decisions are captured
- actor matrix is defined
- route matrix is defined
- policy boundary is defined
- audit requirements are defined
- manual reason distinction is defined
- test matrix is defined
- execution workflow is defined
- seeder is explicitly marked legacy backlog and out of scope
- ADR-0020 and payment concurrency are not mixed into ADR-0019 implementation
- no application source patch is made during planning

## DoD For Implementation

Implementation is complete only when all relevant conditions are proven:

### Source Boundary

- authorization is enforced server-side
- UI hiding is not the only protection
- cashier out-of-window access returns 403
- admin mutation requires transaction capability
- admin read access remains available without transaction capability
- metadata-only correction remains possible without transaction capability when audited
- supplier invoice and proof sensitive flows require transaction capability
- capability toggle is authenticated, authorized, CSRF protected, and audited
- cashier create page does not expose global note count

### Tests

- red characterization test exists before patch
- targeted test fails before patch for expected reason
- targeted test passes after patch
- blast-radius tests pass for affected cluster
- no unrelated tests are weakened
- no test is changed just to hide a failure

### Audit

- capability toggle audit exists
- high-risk capability usage audit exists where required
- manual reason remains required for domain actions that require reason
- audit includes actor, resource, action, timestamp, and useful before or after context when available

### Documentation

- docs/error_log finding is updated only after proof
- ADR is not rewritten casually during implementation
- any deviation from this blueprint is recorded with reason
- seeder legacy backlog remains untouched unless owner explicitly opens seeder scope

### Git

- git status is checked before and after
- diff contains only files in approved slice
- commit message references the narrow fix
- owner reviews proof before commit

## Stop Conditions

Stop immediately if any of these happen:

- source code contradicts ADR-0019 owner decisions
- test requires changing finance/refund semantics beyond access boundary
- patch requires modifying seeder structure
- patch requires ADR-0020 output encoding or attachment security decisions
- patch requires payment allocation locking or concurrency semantics
- admin read access breaks while fixing admin mutation access
- cashier edit or refund is globally blocked instead of scoped
- authorization is only implemented in Blade or JavaScript
- route returns success for unauthorized sensitive mutation
- failing test reason is not understood
- broad refactor is needed before exact affected files are proven

## Seeder Status

Seeder is out of scope for ADR-0019.

Seeder status:

BACKLOG_LEGACY_DO_NOT_TOUCH_YET

Meaning:

- existing seeders remain as legacy compatibility seeders
- predictable credentials are known legacy debt, not accepted final production behavior
- seeder reset and rebuild will be handled after ADR-0019, ADR-0020, and payment concurrency planning
- future seeder work should introduce base, domain, scenario, and load seed levels
- current ADR-0019 implementation must not move, delete, or rewrite seeder structure

## Next Blueprint After This

After ADR-0019 blueprint is accepted, continue with:

1. ADR-0020 public surface, output context, unsafe URL, storage, attachment serving blueprint
2. Payment concurrency and over-allocation blueprint
3. Seeder legacy marker and future seeder reset blueprint

