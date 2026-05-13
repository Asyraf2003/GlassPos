# Error Log Strict Closure Protocol

## Purpose

This document is the canonical workflow for promoting `docs/04-lifecycle/error-log/*.md` items from patched-or-better status into strict fixed status.

This file exists because prose status, stale docs, and partial UI-only patches are not proof.

Local source and local command output always override older docs.

## Source of Truth Order

1. Local command output.
2. Current repo source.
3. ADR / workflow / rules docs.
4. docs/error_log.
5. Handoff / memory.
6. General model knowledge.

## Allowed Status Values

Every error log must use exactly one machine-readable status line:

Status: Reported
Status: Characterized RED
Status: Patched Unverified
Status: Targeted Verified
Status: Focused Verified
Status: Docs Aligned
Status: Strict Fixed
Status: Deferred

Free-form status lines are not eligible for strict closure.

Examples of invalid closure status:

- Patched.
- Patched, with verification gap.
- Fixed and locally verified for minimum scope.
- Follow-up verified.

Those may appear in history, but the current status line must use the allowed enum.

## State Definitions

### Reported

The issue is logged, but current source behavior has not been verified.

### Characterized RED

A local test or command proves the bug exists in current source.

### Patched Unverified

A source patch exists, but targeted test proof is missing.

### Targeted Verified

The specific regression/characterization test passes locally.

### Focused Verified

A small blast-radius suite around the affected behavior passes locally.

### Docs Aligned

The error log matches current source, targeted proof, focused proof, scope, ADR references, and remaining gaps.

### Strict Fixed

All strict closure gates pass.

### Deferred

The item cannot be strictly closed in the current phase because it needs broader migration, global audit, concurrency stress, browser E2E, or a separate design decision.

## Strict Fixed Gates

An item may be promoted to `Status: Strict Fixed` only if all gates pass.

### Gate 1 - Source Reality

The current source must be inspected after patch.

Required evidence:

- exact file paths
- relevant method, route, Blade block, policy, or use case
- invariant proven from current source

Docs claims alone are not proof.

### Gate 2 - RED Proof

For security, finance, mutation, route, payment, refund, inventory, Blade-action, and access-control issues, a RED characterization must exist unless impossible.

If RED is impossible, the document must explain why.

### Gate 3 - Targeted GREEN Proof

The targeted regression test must pass locally.

Proof must include:

- command
- pass/fail summary
- assertion count when available

### Gate 4 - Focused Blast-Radius Proof

Sensitive areas require focused proof.

Sensitive areas include:

- payment allocation
- note revision/replacement
- refund state
- admin/cashier route separation
- Blade action availability vs backend policy
- projection/reporting
- inventory / average cost / COGS
- settlement carry-forward
- legacy payment compatibility
- capability/auth gates
- XSS/storage/security surfaces

### Gate 5 - UI Blade Alignment

Required when the bug has visible UI/action impact.

Rules:

- UI must not show an action that backend policy rejects.
- Backend must still enforce the policy.
- UI hiding is not a security boundary.
- No inline PHP blocks in Blade.
- Fallback form submit must remain valid.
- Native JS progressive enhancement must not become the only path.
- Locked UI terms must be preserved:
  - Nota
  - Kasus
  - Rincian
  - Belum Lunas
  - Lunas
  - Batal
  - Refund

### Gate 6 - Server Boundary

Required when route/action/mutation is involved.

Proof must cover direct requests, not only hidden UI:

- direct GET route behavior if relevant
- direct POST/PATCH/DELETE route behavior if relevant
- no mutation after rejected request
- admin and cashier boundaries preserved

### Gate 7 - ADR Compatibility

Relevant ADRs must be checked.

If an ADR conflict exists, stop and record the conflict. Do not patch around it silently.

Common note/refund/workspace ADRs:

- docs/02-architecture/adr/0015-note-operational-status-open-close-editable-partial-payment.md
- docs/02-architecture/adr/0016-post-close-note-correction-and-refund-flexibility.md
- docs/02-architecture/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/02-architecture/adr/0021-note-detail-hybrid-versioning-billing-refund.md
- docs/02-architecture/adr/0022-payment-allocation-concurrency-and-over-allocation-protection.md
- docs/02-architecture/adr/0024-note-current-projection-and-current-only-refund.md
- docs/02-architecture/adr/0025-note-revision-carry-forward-settlement.md

### Gate 8 - Docs Alignment

The error log must include a strict closure packet.

### Gate 9 - Negative Search

Leak, XSS, visibility, route exposure, and action exposure bugs require negative checks.

Examples:

- route URL not present in rendered response
- unsafe string not present in HTML/JS sink
- old unguarded Blade block not present
- old route/controller branch not present

## Strict Closure Packet Template

Use this block when closing an item.

### Strict Closure Packet

Status: Strict Fixed
Strict-Fixed-Scope: <specific scope proven locally>

#### Root Cause

<final root cause>

#### Source Reality

- `<path>`: <current invariant>
- `<path>`: <current invariant>

#### UI Blade Impact

Impact: yes/no

If yes:

- View paths:
  - `<path>`
- UI invariant:
  - action is not rendered when backend policy rejects it
  - backend remains source of truth

#### Server Boundary

- Direct GET:
- Direct mutation request:
- No mutation proof:
- Admin boundary:
- Cashier boundary:

#### ADR / Rule Compatibility

- `<ADR path>`: <decision used>
- Conflict: none / <explain>

#### RED Proof

Command:

`<command>`

Observed failure:

`<exact summary>`

#### GREEN Proof

Command:

`<command>`

Observed pass:

`<exact summary>`

#### Focused Blast-Radius Proof

Command:

`<command>`

Observed pass:

`<exact summary>`

#### Remaining Gaps

- <gap, if any>
- <why it does not block strict closure>

#### Strict Closure Decision

Closed because:

- source behavior matches the root-cause fix
- targeted proof passed
- focused proof passed when required
- UI/server boundaries align
- ADR conflict is resolved or absent
- remaining gaps are explicit and out of strict scope

## Promotion Rule

Do not promote an item to `Status: Strict Fixed` from docs text alone.

Minimum promotion command packet must include:

1. source anchors
2. targeted test proof
3. focused test proof when sensitive
4. docs alignment
5. negative search when relevant

## Conflict Rule

If docs and source disagree, source wins.

If test and docs disagree, test wins.

If source/test and ADR disagree, stop and record the conflict.

## UI Security Rule

A hidden button is not a guard.

Blade alignment is required for UX correctness, but backend policy is required for security.
