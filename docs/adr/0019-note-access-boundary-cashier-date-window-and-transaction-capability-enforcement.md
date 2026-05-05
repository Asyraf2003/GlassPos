# ADR-0019 — Note Access Boundary, Cashier Date Window, and Transaction Capability Enforcement

- Status: Draft for owner review
- Date: 2026-05-06
- Deciders: Project Owner, Architecture Decision
- Scope: IdentityAccess / Authorization / Note / Refund / Payment / SupplierInvoice / Capability / Audit / Security
- Related:
  - ADR-0007 — Admin Transaction Entry Behind Capability Policy
  - ADR-0008 — Audit-First Sensitive Mutations
  - ADR-0016 — Post-Close Note Correction and Refund Flexibility
  - ADR-0018 — Note Revision Settlement, External Product, and Inventory Lifecycle
  - ADR-0021 — Note Detail Hybrid Model: Versioning + Billing Projection + Simple Refund
  - ADR — Note Current Projection And Current-Only Refund
  - ADR — Note Revision Carry-Forward Settlement

## Status Note

This ADR is an expanded owner-review draft.

The owner selected the expanded ADR direction.

This ADR is not a production patch.

This ADR does not mark any error log as fixed.

This ADR must be reviewed by the owner before implementation.

## Context

Existing ADRs already lock several access and mutation principles.

ADR-0007 locks that:

- role and capability are separate concepts
- `admin` does not automatically have transaction-entry capability
- `kasir` can perform normal transaction entry
- admin without active transaction-entry capability must be denied for transaction entry
- admin with active transaction-entry capability may perform transaction entry
- transaction capability activation, deactivation, and usage must be auditable

ADR-0008 locks that sensitive mutations are audit-first.

Sensitive mutations include capability activation/deactivation and any mutation that affects money, stock, final status, reports, or authorization.

ADR-0016 locks that:

- closed, paid, and refunded notes are not absolute terminal mutation locks
- post-consequence note mutation is allowed only through official audited, revisioned, evented flows
- kasir may access notes for today and yesterday
- admin may access all notes
- admin must not bypass audit
- kasir must not bypass audit because the date scope is narrow

ADR-0018 locks that edit/refund/revision must be proper application/domain lifecycle, not UI-only workaround.

ADR-0018 also says XSS, route authorization, private storage exposure, content-type, and information leak findings must be handled as separate security issues unless they directly affect the note finance lifecycle.

The current error-log cluster shows that the existing decisions are not centralized enough.

Findings in this cluster include:

- admin note actions bypass transaction capability
- unauthenticated or under-protected capability toggle endpoints
- cashier can list historical closed notes by date
- cashier refund route bypasses note access guard
- cashier/refunded/closed note workspace exposure gaps
- admin supplier invoice creation bypasses transaction-entry gate
- cashier create page leaks total note count

The core issue is not one route.

The core issue is that note access, transaction-entry capability, date-window access, refund access, supplier invoice gate, and information disclosure policy are not expressed as one server-side authorization boundary.

## Decision Summary

HyperPOS adopts a centralized access boundary for transaction-sensitive note and supplier-invoice flows.

The system must enforce authorization server-side in application/policy/middleware boundaries.

UI hiding is not a security boundary.

The system must separate:

- role
- capability
- date-window access
- note current-projection eligibility
- mutation eligibility
- audit requirement
- information disclosure scope

## Decision Traceability

| Decision | Source |
|---|---|
| Admin does not automatically have transaction-entry capability | ADR-0007 |
| Kasir can perform normal transaction entry | ADR-0007 |
| Admin transaction-entry capability must be auditable | ADR-0007, ADR-0008 |
| Sensitive mutations must be audit-first | ADR-0008 |
| Kasir can access notes for today and yesterday | ADR-0016 |
| Admin can access all notes | ADR-0016 |
| Closed/paid/refunded notes may still be changed through official audited lifecycle | ADR-0016, ADR-0018 |
| Refund/edit/revision must use current projection and immutable ledger/history boundary | ADR Current Projection, ADR-0018 |
| Cashier edit remains allowed and must not be blocked as generic containment without owner decision | Carry-forward ADR |
| Route authorization and information leak findings need separate security decision | ADR-0018 |

## Owner Decisions Introduced By This ADR

This ADR expands the existing decisions into one enforceable boundary.

### 1. Server-Side Authorization Is Mandatory

Every sensitive note, refund, payment, revision, transaction-entry, supplier-invoice, and capability mutation path must enforce access server-side.

UI-only button hiding, link hiding, Blade conditional rendering, or front-end checks are presentation only.

They are not authorization.

### 2. Cashier Date Window

Kasir note access is limited to today and yesterday.

The date basis must be the note business date / transaction date used by the domain, not a client-controlled arbitrary request date.

The server decides the allowed window.

Client input may request a date filter, but the server must clamp or reject access outside cashier policy.

### 3. Admin Note Access

Admin may read notes across all dates.

However, admin read access is not the same as admin mutation capability.

Admin mutation of transaction-sensitive note state must still pass transaction-entry capability policy when the action affects money, stock, note lines, payment, refund, revision, current projection, or reporting-relevant state.

### 4. Admin Transaction Capability Enforcement

Admin without active transaction-entry capability must be denied for transaction-sensitive mutations.

Admin with active transaction-entry capability may perform transaction-sensitive mutations if the target note or resource also passes the relevant domain policy.

Capability does not bypass:

- audit-first mutation
- paid/closed/refunded official lifecycle
- current projection policy
- inventory policy
- payment/refund policy
- price policy
- reporting integrity

### 5. Kasir Mutation Boundary

Kasir remains allowed to perform normal transaction-entry work within cashier scope.

Kasir remains allowed to use official edit/refund/revision flows when the note is within cashier access scope and the flow passes domain policy.

Kasir must not be globally blocked from edit/revision/refund solely because a note is paid, closed, or refunded.

Paid, closed, and refunded note changes must use the official audited lifecycle from ADR-0016 and ADR-0018.

### 6. Refund Route Access

Refund routes must enforce both:

1. actor access policy
2. current projection/domain eligibility

A user must not be able to refund historical/superseded rows by forging row ids.

A cashier must not be able to refund notes outside the cashier date window.

An admin must not bypass required transaction capability for transaction-sensitive refund mutation.

### 7. Current Projection Guard

For operational note mutation and refund selection:

- current note state comes from current projection
- historical rows are audit/history only
- historical rows must not become eligible for new operational refund/edit merely because their ids are submitted
- ledger/history rows remain immutable anchors

### 8. Capability Toggle Authorization

Capability activation/deactivation is itself a sensitive mutation.

Capability toggle endpoints must require:

- authenticated actor
- authorized admin-level actor
- CSRF/session protection for web routes
- audit-first recording
- before/after capability state
- target actor id
- acting actor id
- timestamp
- reason or system context sufficient for investigation

For the current two-role model, capability toggle is admin-only.

If a future owner-admin/super-admin role exists, this ADR allows tightening capability toggle to that narrower role without changing the transaction-entry domain model.

### 9. Capability Usage Audit

When admin uses transaction-entry capability, the system must record enough context to reconstruct:

- acting actor
- role
- active capability at time of action
- affected note or transaction resource
- action type
- timestamp
- relevant before/after or mutation context

Manual reason is required when the underlying sensitive mutation requires a reason.

Routine capability usage may use automatic audit context if the use case itself does not require manual reason.

Capability activation/deactivation should require a reason unless a later owner decision narrows this requirement.

### 10. Supplier Invoice Creation Gate

Supplier invoice creation affects stock, cost, payable, and reporting.

Until a dedicated procurement capability exists, supplier invoice creation and revision are treated as transaction-sensitive mutations and must require the same admin transaction-entry capability for admin actors.

This is a bridge policy.

A future ADR may split procurement into a dedicated capability if the owner decides supplier/procurement should have a separate authorization model.

### 11. Cashier Information Disclosure Boundary

Cashier-facing pages must not expose global note counts, global historical counts, or cross-date aggregate operational numbers unless the owner explicitly approves that disclosure.

Cashier count/stat display must be scoped to the cashier access window and operational need.

If a count is not needed for cashier workflow, omit it.

If a count is needed, compute it from the same access policy used for list/read access.

### 12. Admin Information Disclosure Boundary

Admin may see global operational data according to admin read access.

However, admin read access still does not grant mutation capability.

### 13. Error Handling

Authorization failures must not leak sensitive resource existence across access boundaries.

Preferred behavior:

- return forbidden or not found according to existing application pattern
- avoid exposing whether out-of-scope note ids exist to unauthorized cashiers
- keep error messages operator-safe
- record denied sensitive attempts when useful for audit/security investigation

## Conflict Resolution

### Cashier Edit Must Remain Allowed

This ADR does not reverse the decision that cashier edit/revision can remain available.

Cashier edit is allowed only when:

- the note is within cashier access scope
- the route passes server-side access policy
- the mutation uses official audited revision/event flow
- current projection/domain rules permit the operation

This resolves the tension between access control and the carry-forward ADR.

The solution is not to block cashier edit globally.

The solution is to enforce scope, audit, and official lifecycle.

### Paid/Closed/Refunded Is Not A Generic Deny

Paid, closed, and refunded states do not automatically block all changes.

They require official audited lifecycle.

Access control decides who may enter the flow.

Domain policy decides whether the flow is valid.

Audit policy records why and how it happened.

### Admin Authority Is Not Transaction Capability

Admin broad read authority does not imply transaction mutation authority.

This ADR preserves ADR-0007.

### Supplier Invoice Boundary

Supplier invoice creation is included under transaction-sensitive gate as a bridge policy because it affects stock/cost/payable/reporting.

If this conflicts with future procurement workflow needs, a future ADR may split it into a dedicated procurement capability.

Until then, the safer rule is to gate it.

### Information Disclosure vs Output Security

This ADR controls who may see what.

Output encoding, XSS prevention, unsafe URLs, attachment content type, and public/private storage serving belong to the later public-surface ADR.

However, count leaks and note existence leaks are access-boundary concerns and are included here.

## Alternatives Considered

### Alternative A — Keep ADR-0007 Only

Rejected.

ADR-0007 correctly defines admin transaction-entry capability, but it does not centralize cashier date-window, refund route access, supplier invoice gate, current projection access guard, and count disclosure policy.

### Alternative B — Patch Each Route Independently

Rejected as final direction.

Route-level patching can be used for emergency hotfixes, but it risks inconsistent policy, missed endpoints, and UI-only security assumptions.

### Alternative C — Block Cashier Edit/Refund Broadly

Rejected.

This conflicts with existing owner decisions that cashier edit/revision/refund flows remain part of operational reality when done through official audited lifecycle.

### Alternative D — Make Admin Capability Equivalent To Admin Role

Rejected.

This violates ADR-0007.

Role and capability must remain separate.

### Alternative E — Create Separate Procurement Capability Immediately

Deferred.

A dedicated procurement capability may be cleaner later, but it adds a new policy dimension before the current transaction-entry capability is consistently enforced.

The bridge policy is to require transaction-entry capability for supplier invoice creation/revision until a future ADR splits procurement capability.

## Consequences

### Positive

- Access decisions become centralized and traceable.
- Existing owner decisions are preserved.
- Admin read access and admin mutation capability are separated.
- Cashier date-window is enforced server-side.
- Refund route access is tied to both actor scope and current projection.
- Capability toggle becomes clearly audit-first.
- Supplier invoice creation gets a safe bridge gate.
- Information disclosure to cashier becomes explicit.

### Negative

- More tests are required.
- Some existing route/controller assumptions may need refactor.
- Supplier invoice capability may need future split if procurement workflow grows.
- Capability usage audit may add implementation overhead.
- Access policy must be kept in application/policy layer, not scattered through Blade views.

## Implementation Direction

Implementation must not patch every route randomly.

Safe order:

1. Inventory affected routes/controllers/use cases.
2. Add characterization tests for denied/allowed matrix.
3. Introduce or consolidate access policy abstraction.
4. Enforce cashier date-window server-side.
5. Enforce admin transaction capability for transaction-sensitive admin note actions.
6. Enforce refund route access + current projection guard.
7. Enforce supplier invoice bridge gate.
8. Enforce capability toggle auth + audit.
9. Remove or downgrade UI-only assumptions.
10. Run targeted access/security tests.
11. Run note/refund/payment/procurement blast-radius tests.
12. Update relevant error logs with proof.

## Required Test Matrix

### Actor Matrix

- kasir within today/yesterday note window can access allowed operational note flows
- kasir outside date window cannot read/mutate note
- admin can read all notes
- admin without transaction-entry capability cannot perform transaction-sensitive note mutations
- admin with transaction-entry capability can perform transaction-sensitive note mutations when domain policy allows

### Capability Toggle

- unauthenticated actor cannot toggle capability
- kasir cannot toggle capability
- admin can toggle capability only through authorized route
- toggle creates audit record
- toggle records before/after state

### Refund Route

- cashier cannot refund out-of-window note
- admin without capability cannot perform transaction-sensitive refund mutation
- forged historical row id cannot refund superseded/history-only row
- refund selection uses current projection only

### Supplier Invoice Gate

- admin without transaction-entry capability cannot create supplier invoice
- admin with transaction-entry capability can create supplier invoice when domain validation passes
- supplier invoice mutation audit exists if the flow is sensitive

### Disclosure

- cashier note list/count only includes cashier-scoped notes
- cashier create/list page does not expose global note count
- unauthorized actors cannot infer out-of-scope note existence through error messages

## Non-Goals

- This ADR does not implement code.
- This ADR does not finalize public-surface/output encoding policy.
- This ADR does not define file attachment MIME/content-type serving policy.
- This ADR does not replace ADR-0018 finance/revision/refund lifecycle.
- This ADR does not create a dedicated procurement capability yet.
- This ADR does not block cashier edit/revision/refund globally.

## Follow-Up ADRs

Recommended follow-up:

1. ADR-0020 — Public Surface, Output Context, Unsafe URL, Attachment Serving, and Information Exposure Security
2. Procurement capability ADR or addendum if supplier invoice authorization needs to split from transaction-entry capability
3. Payment concurrency/locking ADR or ADR-0018 addendum

## Stop Conditions

- Do not patch app source until this ADR is owner-reviewed.
- Do not treat UI hiding as authorization.
- Do not treat admin role as transaction capability.
- Do not block cashier edit/refund globally without explicit owner decision.
- Do not trust client-submitted date, row id, note id, URL, MIME, or capability state.
- If source code conflicts with this ADR after acceptance, source code must be fixed or the ADR must be revised by owner decision.
