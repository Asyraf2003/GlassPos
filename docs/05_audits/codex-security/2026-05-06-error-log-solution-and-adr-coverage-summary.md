# Error Log Solution, Option, and ADR Coverage Summary

## Status

Draft for owner review.

## Purpose

This document summarizes the solution direction for all `docs/04-lifecycle/error-log/*.md` findings.

The purpose is not to decide final fixes.

Final domain, security, ADR, and rollout decisions belong to the project owner.

The assistant role is limited to:

- classify the finding
- identify the risk
- propose fix options
- explain pros and cons
- recommend the safest technical path
- define safe implementation steps
- identify ADR or policy gaps
- stop before production patch when owner decision is missing

## Decision Boundary

### Owner owns

- final domain decision
- final security policy
- ADR approval
- whether a finding is accepted as fixed
- whether a residual risk is acceptable
- whether a fix is hotfix, ADR-first, or deferred
- final merge/commit acceptance

### Assistant owns

- technical analysis
- option design
- risk comparison
- safe fix sequence
- test strategy
- blast-radius assessment
- ADR draft proposal
- proof checklist

### Explicit Rule

No finding in this document is considered fully solved only because the error log says `Patched`.

A finding is only final when the owner accepts the decision and verification proof exists.

## Source Of Truth

- Local repository files under `docs/04-lifecycle/error-log/*.md`
- Existing ADR and blueprint documents in `docs/02-architecture/adr/` and `docs/03-blueprints/`
- Existing project rules under `docs/AI_RULES/`
- User command output
- Source code current state when implementation starts

## Non-Goals

- No application source patch
- No test creation
- No ADR finalization
- No claim that all findings are fixed
- No claim that ADR-0019 or ADR-0020 exists unless files exist and owner approves them

## Executive Conclusion

There are 29 error log files.

The findings are not one single fix.

They fall into these serious clusters:

1. Note finance, revision, refund, settlement, inventory, and payment lifecycle
2. Access boundary, cashier/admin capability, and date-window policy
3. Output context, XSS, unsafe URL, and public surface leakage
4. Storage, public helper, attachment serving, and content-type trust
5. Seeder/default credential safety
6. Payment concurrency and over-allocation

ADR coverage is partial.

Known current ADR coverage:

- ADR-0018 covers the finance/revision/refund/inventory domain direction.
- Carry-forward settlement ADR exists but was draft for owner review.
- ADR-0019 is recommended for access/capability boundary, but not proven accepted.
- ADR-0020 is recommended for public surface/output/attachment security, but not proven accepted.
- Seeder credential and payment concurrency may need dedicated ADRs or explicit addenda.

## Solution Model

Each finding should be handled using this lifecycle:

1. Read error log
2. Read current source
3. Classify current status
4. Identify owner decision required
5. Present options with pros and cons
6. Owner chooses direction
7. Draft/update ADR if needed
8. Add red characterization test
9. Patch one finding or one tightly coupled cluster
10. Run targeted test
11. Run blast-radius test
12. Show diff
13. Owner approves commit

## Cluster Matrix

| Cluster | Findings | Existing ADR Coverage | Owner Decision Needed | Assistant Recommendation |
|---|---:|---|---|---|
| Finance / note revision / refund / settlement / inventory lifecycle | 001, 003, 004, 005, 006, 008, 011, 012, 013, 014, 017, 021 | Partially covered by ADR-0018 | Yes, for residual semantics and verification acceptance | Use ADR-0018 as umbrella. Fix through application/domain flow, not UI-only or generic reader-only patches. |
| Access / capability / cashier-admin boundary | 009, 015, 016, 018, 019, 020, 022, 027, 029 | Missing or not proven. ADR-0019 recommended. | Yes | Draft ADR-0019 before more access patches. Enforce server-side policy, not UI hiding. |
| Public surface / output context / XSS / unsafe URL / count leak | 007, 024, 025, 029 | Missing or not proven. ADR-0020 recommended. | Yes | Draft ADR-0020. Centralize output encoding, URL validation, public surface policy, and count disclosure policy. |
| Storage / public helper / attachment content type | 023, 028 | Missing or not proven. ADR-0020 recommended. | Yes | Include in ADR-0020. Treat file serving as security boundary. Never trust stored MIME/client filename blindly. |
| Seeder / default credential safety | 002 | Missing or not proven | Yes | Create security baseline addendum or dedicated ADR. No predictable production-capable credentials. |
| Payment concurrency / over-allocation | 010, 026 | Partial through finance domain, but locking/idempotency not proven | Yes | Dedicated ADR/addendum for transaction lock, allocation invariant, idempotency, and DB constraints. |

## Options By Cluster

### C1 - Finance / Revision / Refund / Settlement / Inventory

#### Option A - ADR-0018-first settlement lifecycle implementation

Description:
Implement fixes under explicit settlement/revision/current-projection lifecycle.

Pros:
- Best domain correctness
- Preserves audit/history
- Reduces patch ping-pong between refund and payment readers
- Compatible with future Telegram/PDF/reporting because core semantics live in application/domain flow

Cons:
- More implementation work
- Requires careful test matrix
- May need DB/model additions for kembalian/overpaid state

Recommended by assistant:
Yes.

Safe fix steps:
1. Confirm ADR-0018 owner acceptance for target behavior.
2. Pick one error log or one tight sub-cluster.
3. Add red characterization test at use-case/feature level.
4. Patch application/domain service.
5. Keep reader-level change minimal unless consumer semantics prove it.
6. Run targeted test.
7. Run relevant finance/revision/refund blast-radius tests.
8. Update error log status with proof.

#### Option B - Patch individual readers/controllers

Pros:
- Faster for one failing path
- Smaller diff

Cons:
- High risk of moving bug from one settlement path to another
- Can conflict with current projection design
- May create false pass in one test while breaking refunds/revisions

Recommended by assistant:
No, except for very small verified defects.

#### Option C - Temporary containment

Pros:
- Reduces immediate production risk
- Useful if implementation is too large

Cons:
- Can block valid cashier workflow
- Must not become fake final solution
- Requires explicit owner approval

Recommended by assistant:
Only if owner chooses containment.

### C2 - Access / Capability / Cashier-Admin Boundary

#### Option A - ADR-0019 first, then server-side policy enforcement

Pros:
- Clear owner-approved access boundary
- Prevents inconsistent admin/cashier patches
- Avoids relying on UI hiding
- Audit trail can be designed once

Cons:
- Slower than patching one route
- Requires policy inventory

Recommended by assistant:
Yes.

Safe fix steps:
1. Draft ADR-0019.
2. Define actor matrix: cashier, admin without capability, admin with capability.
3. Define date-window access rule.
4. Define transaction-entry capability enforcement.
5. Add route/controller/use-case tests.
6. Patch policy/middleware/use-case boundary.
7. Run access/security blast-radius tests.

#### Option B - Patch each route directly

Pros:
- Fast for isolated endpoint
- Lower initial complexity

Cons:
- Easy to miss routes
- Duplicates policy logic
- Higher regression risk

Recommended by assistant:
No, unless hotfixing a confirmed exposed endpoint.

### C3 - Output Context / XSS / Unsafe URL / Count Leak

#### Option A - ADR-0020 first, then central output/public-surface rules

Pros:
- Creates one standard for Blade, JS config, return URLs, and disclosure
- Reduces repeated XSS fixes
- Easier audit

Cons:
- Needs inventory of surfaces
- Requires careful distinction between HTML, JS, URL, JSON, and attribute contexts

Recommended by assistant:
Yes.

Safe fix steps:
1. Draft ADR-0020.
2. Define safe encoder per context.
3. Define allowed return URL policy.
4. Define count/stat disclosure policy.
5. Add tests for XSS payloads and unsafe URLs.
6. Patch helpers/views/controllers.
7. Run output/security focused tests.

#### Option B - Patch each Blade/view issue

Pros:
- Fast
- Small diff

Cons:
- Repeats mistakes
- Misses shared unsafe patterns
- Not enough for serious public-surface cluster

Recommended by assistant:
Only as emergency hotfix after ADR direction is clear.

### C4 - Storage / Public Helper / Attachment Serving

#### Option A - Treat file serving as explicit security boundary

Pros:
- Prevents private storage exposure
- Prevents dangerous content type trust
- Creates consistent attachment serving policy

Cons:
- May require route/controller/storage refactor
- May require migration away from public helper assumptions

Recommended by assistant:
Yes.

Safe fix steps:
1. Include in ADR-0020.
2. Define allowed disks and paths.
3. Define MIME detection server-side.
4. Force safe download headers where appropriate.
5. Prevent inline serving for risky content types.
6. Add tests for private path traversal and unsafe MIME.

### C5 - Seeder / Default Credential Safety

#### Option A - Dedicated seeder/security baseline decision

Pros:
- Clear production safety
- Prevents accidental predictable admin credentials
- Easy to audit

Cons:
- Adds environment rules
- May require local/dev workflow adjustment

Recommended by assistant:
Yes.

Safe fix steps:
1. Decide allowed dev-only seeded credentials.
2. Block predictable credentials outside local/testing.
3. Require env-provided password or random generated secret.
4. Add test for production-like environment.
5. Update handoff/runbook.

### C6 - Payment Concurrency / Over-Allocation

#### Option A - Dedicated concurrency ADR/addendum

Pros:
- Prevents race-condition patching by vibes, society's most regrettable engineering method
- Defines lock and idempotency strategy
- Protects money invariants

Cons:
- Needs deeper implementation proof
- Testing concurrency is harder

Recommended by assistant:
Yes.

Safe fix steps:
1. Define invariant: total allocated must not exceed payable/outstanding.
2. Choose lock strategy: DB transaction + row lock or allocation ledger constraint.
3. Decide idempotency behavior.
4. Add concurrency characterization test if environment supports it.
5. Patch service/repository transaction boundary.
6. Add DB constraint only if compatible with current schema.
7. Run payment blast-radius tests.

## Error Log Decision Matrix

| Error Log | Cluster | Decision Owner | Assistant Recommendation |
|---|---|---|---|
| 001-refunds-counted-as-paid-in-note-totals | C1 | Owner decides final settlement semantics acceptance | Use ADR-0018 semantics and verify no active refund double-count. |
| 002-seeder-introduces-predictable-admin-credentials | C5 | Owner decides security baseline | Add seeder credential policy/addendum; no predictable prod-capable credentials. |
| 003-refunded-revised-notes-are-misclassified-as-underpaid | C1 | Owner decides carry-forward settlement behavior | Use settlement-level fix, not generic reader-only patch. |
| 004-refunded-work-items-survive-revisions-and-inflate-stock | C1 | Owner decides lifecycle semantics | Use current projection + inventory reversal lifecycle. |
| 005-note-revision-silently-drops-overpaid-allocations | C1 | Owner decides kembalian/overpaid workflow | Implement DB-backed overpaid/kembalian state per ADR-0018. |
| 006-client-controlled-price-basis-bypasses-minimum-price-checks | C1 | Owner decides edit price policy acceptance | Enforce server-side price basis and old minimum snapshot rule. |
| 007-admin-note-edit-page-exposes-stored-xss | C3 | Owner decides output policy | ADR-0020; context-aware escaping and no raw JS/HTML injection. |
| 008-legacy-paid-notes-can-be-paid-again | C1 | Owner decides legacy payment handling | Normalize paid-state detection through settlement policy. |
| 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update | C2 | Owner decides cashier edit boundary | ADR-0019; server-side mutation guard. |
| 010-revision-reallocation-can-lose-concurrent-payments | C6 | Owner decides concurrency strategy | Add locking/idempotency decision before patch. |
| 011-cashier-revision-path-mutates-settled-note-state | C1/C2 | Owner decides settled-note revision boundary | Use ADR-0018 + ADR-0019 boundary. |
| 012-canceled-note-rows-re-enter-payment-flows | C1 | Owner decides active/current projection rule | Current-only payable components. |
| 013-forged-row-refund-can-auto-finalize-unpaid-notes | C1/C2 | Owner decides refund validation boundary | Server-side row ownership/current-state validation. |
| 014-refund-endpoint-can-cancel-open-or-unpaid-note-rows | C1/C2 | Owner decides refund eligibility | Enforce paid/current refund eligibility. |
| 015-refunded-notes-expose-edit-workspace | C2 | Owner decides workspace exposure policy | Server-side authorization; UI hiding is not boundary. |
| 016-unauthenticated-admin-capability-toggle-endpoints | C2 | Owner decides capability administration policy | ADR-0019; authenticated/admin-only/audited capability mutation. |
| 017-workspace-edit-payments-ignore-existing-note-payments | C1 | Owner decides existing-money carry-forward behavior | Settlement flow must include existing payment/carry-forward. |
| 018-refunded-notes-bypass-cashier-closed-note-guards | C2 | Owner decides cashier closed/refunded guard | ADR-0019; status-aware cashier policy. |
| 019-cashiers-can-list-historical-closed-notes-by-date | C2 | Owner decides cashier date-window visibility | ADR-0019; date window and role-based historical access. |
| 020-admin-note-actions-bypass-transaction-capability | C2 | Owner decides admin capability enforcement | Admin mutation requires explicit transaction capability. |
| 021-refunds-can-be-recorded-on-open-notes | C1/C2 | Owner decides refund eligibility | Refund only through eligible settled/current states. |
| 022-cashier-refund-route-bypasses-note-access-guard | C2 | Owner decides note access guard | Centralize route/use-case access guard. |
| 023-public-helper-can-expose-private-storage | C4 | Owner decides storage serving policy | ADR-0020; never expose private storage via public helper. |
| 024-reflected-xss-in-expense-create-json-config | C3 | Owner decides JS config output policy | Safe JSON encoding for script context. |
| 025-reflected-javascript-url-in-product-return-link | C3 | Owner decides URL policy | Allow-list internal return URLs; reject javascript/data URLs. |
| 026-concurrent-note-payments-can-over-allocate-balances | C6 | Owner decides concurrency/locking policy | Use transaction/lock/idempotency invariant. |
| 027-admin-invoice-creation-bypasses-transaction-entry-gate | C2 | Owner decides supplier invoice capability boundary | Gate invoice creation behind transaction-entry capability if business policy says so. |
| 028-di-fix-exposes-unsafe-proof-attachment-content-type | C4 | Owner decides attachment serving policy | Server-detected MIME and safe download/inline policy. |
| 029-cashier-create-page-leaks-total-note-count | C2/C3 | Owner decides disclosure boundary | Avoid global count leak to cashier unless explicitly allowed. |

## ADR Recommendation

### ADR-0018

Status:
Existing and accepted for domain direction.

Use for:
- finance/revision/refund/settlement/inventory lifecycle
- carry-forward settlement
- overpaid/kembalian domain
- current projection
- external product lifecycle

Remaining owner decisions:
- whether implementation proof is enough per finding
- whether kembalian/overpaid exact storage/workflow is accepted
- whether concurrency needs addendum

### ADR-0019

Status:
Recommended, not proven accepted.

Purpose:
Note access boundary, cashier date window, transaction capability enforcement.

Should cover:
- cashier historical visibility
- admin transaction capability
- note access guard
- refund route guard
- paid/refunded workspace exposure
- capability toggle authorization/audit

### ADR-0020

Status:
Recommended, not proven accepted.

Purpose:
Public surface, output context, unsafe URL, attachment serving security.

Should cover:
- stored/reflected XSS
- JS config encoding
- URL allow-listing
- public/private storage boundary
- attachment content type
- count/stat disclosure policy

### Seeder Security ADR or Addendum

Status:
Recommended.

Purpose:
Seeder/default credential safety.

Should cover:
- local-only predictable credentials
- production-like environment block
- env-generated or random credentials
- handoff/runbook update

### Payment Concurrency ADR or ADR-0018 Addendum

Status:
Recommended.

Purpose:
Payment allocation concurrency and over-allocation protection.

Should cover:
- DB transaction boundary
- row lock strategy
- idempotency key
- allocation invariant
- rollback behavior
- test strategy

## Recommended Next Owner Decisions

1. Approve ADR-0019 direction or reject/modify it.
2. Approve ADR-0020 direction or reject/modify it.
3. Decide whether payment concurrency gets dedicated ADR or ADR-0018 addendum.
4. Decide whether seeder credential safety gets dedicated ADR or security baseline update.
5. Decide which cluster gets implementation priority after ADR direction is accepted.

## Stop Conditions

- Do not patch app source from this document alone.
- Do not treat assistant recommendation as owner decision.
- Do not mark an issue final fixed without test/audit proof.
- Do not use UI hiding as security boundary.
- Do not use client-controlled date, URL, MIME, row id, price basis, or capability as trusted truth.
- If source code conflicts with error log status, source code wins.

## Generated Inventory Count

- Error log files expected: 29
- Final count must be verified from repo command output before commit.
