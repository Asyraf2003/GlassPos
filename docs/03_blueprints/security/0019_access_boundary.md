# ADR-0019 Access Boundary Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any error log as fixed.

This document exists to make ADR-0019 execution rigid enough for CLI-based implementation later.

## Source Of Truth

- docs/02_architecture/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/02_architecture/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/05_audits/codex_security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/04_lifecycle/error_log/009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
- docs/04_lifecycle/error_log/015-refunded-notes-expose-edit-workspace.md
- docs/04_lifecycle/error_log/016-unauthenticated-admin-capability-toggle-endpoints.md
- docs/04_lifecycle/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md
- docs/04_lifecycle/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md
- docs/04_lifecycle/error_log/020-admin-note-actions-bypass-transaction-capability.md
- docs/04_lifecycle/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
- docs/04_lifecycle/error_log/027-admin-invoice-creation-bypasses-transaction-entry-gate.md
- docs/04_lifecycle/error_log/029-cashier-create-page-leaks-total-note-count.md
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


---

## Related Documents

- DoD: docs/03_blueprints/security/adr-0019-access-boundary-dod.md
- Workflow: docs/03_blueprints/security/adr-0019-access-boundary-workflow.md
