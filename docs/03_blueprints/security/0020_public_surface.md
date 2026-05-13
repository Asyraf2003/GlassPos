# ADR-0020 Public Surface, Output Context, Storage Boundary, Attachment Security Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any `docs/04_lifecycle/error_log/*.md` finding as fixed.

This document exists to make ADR-0020 execution rigid enough for CLI-based implementation later.

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

## Source Of Truth

- docs/02_architecture/adr/0020-public-surface-output-storage-attachment-security.md
- docs/05_audits/codex_security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/03_blueprints/security/adr-0019-access-boundary.md
- docs/02_architecture/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/02_architecture/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/04_lifecycle/error_log/007-admin-note-edit-page-exposes-stored-xss.md
- docs/04_lifecycle/error_log/023-public-helper-can-expose-private-storage.md
- docs/04_lifecycle/error_log/024-reflected-xss-in-expense-create-json-config.md
- docs/04_lifecycle/error_log/025-reflected-javascript-url-in-product-return-link.md
- docs/04_lifecycle/error_log/028-di-fix-exposes-unsafe-proof-attachment-content-type.md
- docs/04_lifecycle/error_log/029-cashier-create-page-leaks-total-note-count.md
- User owner decisions in planning session
- User command output from local repository
- Current source code at execution time

## Proof Available Before This Blueprint

Local proof from user command output:

    git status --short

showed:

    ?? docs/02_architecture/adr/0020-public-surface-output-storage-attachment-security.md

Meaning:

- ADR-0020 file exists locally as untracked file.
- ADR-0020 is not committed yet.
- Implementation has not started yet.
- No error log is fixed by this proof.

## Decision Boundary

ADR-0020 decides public surface, output context, storage boundary, attachment serving, unsafe URL, and disclosure policy.

ADR-0019 decides access boundary, cashier date window, admin transaction capability, route guard, and capability audit.

ADR-0018 decides finance lifecycle, note revision, refund, settlement, carry-forward, and current projection domain behavior.

Final sensitive request handling may require all relevant layers:

1. ADR-0019 access or capability layer
2. ADR-0018 domain eligibility layer when finance lifecycle is involved
3. ADR-0020 output, URL, storage, attachment, or disclosure layer

ADR-0020 must not silently replace ADR-0019 or ADR-0018.

## Non Goals

Do not patch application source from this document alone.

Do not create or modify production code before characterization tests.

Do not solve ADR-0019 access/capability implementation here.

Do not solve cashier date window access policy here.

Do not solve admin transaction capability policy here.

Do not solve payment concurrency here.

Do not solve payment over-allocation here.

Do not solve seeder credential safety here.

Do not solve finance settlement, carry-forward, overpaid, kembalian, refund lifecycle, or current projection here.

Do not rewrite large UI flows.

Do not change domain terms.

Do not introduce new public route behavior without policy.

Do not mark any error log as fixed without proof and owner acceptance.

## Explicit Scope

This blueprint covers:

- stored XSS
- reflected XSS
- Blade output context
- HTML text output
- HTML attribute output
- JavaScript configuration embedded in HTML
- JSON embedded in script context
- href/action/back/return URL values
- redirects using request-provided targets
- public/private storage boundary
- public helper usage for private files
- supplier payment proof attachment serving
- attachment MIME and content-type trust
- attachment download or inline policy
- cashier/admin count and statistic disclosure boundary
- safe default behavior for unknown public-surface cases

## Owner Decisions Locked

### Umbrella Scope

ADR-0020 is the umbrella for public surface, output, unsafe URL, storage, attachment, MIME/content-type, and disclosure security.

It covers:

- XSS
- unsafe URL
- public helper leak
- private storage serving
- attachment content type
- count/stat disclosure

### Strict System, Loose-Feeling UI

The UI may feel flexible.

Users may type ordinary text freely.

The system must remain strict.

No user/admin/cashier/database/request/upload input is trusted as HTML by default.

If a user enters HTML-looking text, the default behavior is safe text rendering, not markup execution.

Raw HTML requires future explicit sanitizer/whitelist decision and ADR update.

### Return URL Policy

Allowed by default:

- relative internal path only
- starts with one `/`
- resolves inside application

Forbidden by default:

- `javascript:`
- `data:`
- full external URL
- protocol-relative URL beginning with `//`
- raw unvalidated request value
- user-controlled redirect target without validation

Invalid return URL must fall back to a safe default route.

### Storage Boundary

Private files must not be exposed through:

- `asset()`
- public helper
- direct public path
- public symlink shortcut
- raw request path
- user-controlled disk/path input
- permanent public URL for private proof material

Private or sensitive files must be served through controlled route/controller with auth, policy, disk allow-list, path allow-list, path traversal protection, and no local path leakage.

### Attachment Serving

User-friendly default is allowed only through safe serving.

Images and PDFs may be inline when authorized and server-validated.

Unknown, risky, or unsupported files must be download.

Fallback MIME:

    application/octet-stream

Required hardening:

- do not trust client MIME
- do not trust extension alone
- sanitize output filename
- use `X-Content-Type-Options: nosniff`
- use safe `Content-Disposition`
- force download for risky content types
- prevent path traversal
- never expose private storage internals

### Count And Statistic Disclosure

Default disclosure policy is deny-by-default.

Cashier must not see global note count, global statistics, or cross-window operational counts unless an explicit rule allows it.

If cashier count is required later, it must be scoped through cashier access policy and working window.

Admin authorized read pages may show global counts.

Admin sensitive mutation remains governed by ADR-0019 transaction capability.

### Response Policy

Default response policy:

- guest access: existing auth middleware behavior
- authenticated but unauthorized: HTTP 403
- unsafe return URL: validation error or safe fallback
- missing private file: HTTP 404
- unauthorized private attachment access: HTTP 403
- anti-enumeration 404: allowed only if explicitly chosen per route/policy

### Deny-By-Default

If no explicit rule allows it, deny it.

If output context is unknown, escape it.

If URL is unsafe or unknown, reject it or fallback safe.

If file type is unknown, serve it as safe download.

If count/stat scope is unclear, do not disclose it.

## Error Log Coverage

| Error Log | Covered By ADR-0020 | Coverage Notes |
|---|---|---|
| 007 admin note edit page exposes stored XSS | Yes | Stored user-controlled output context |
| 023 public helper can expose private storage | Yes | Private storage/public helper boundary |
| 024 reflected XSS in expense create JSON config | Yes | JavaScript config encoding |
| 025 reflected JavaScript URL in product return link | Yes | Return URL allow-list and safe fallback |
| 028 unsafe proof attachment content type | Yes | Attachment MIME/content-type and safe serving |
| 029 cashier create page leaks total note count | Partial | Disclosure default deny here; access boundary remains ADR-0019 |

## Surface Model

### Surface 1 — HTML Text

Examples:

- note customer name
- note description
- product name
- supplier name
- expense description
- file display name
- audit reason
- refund reason
- correction reason

Required:

- escape user-controlled text
- preserve readable UI
- render unsafe-looking input as text, not HTML

Forbidden:

- raw user-controlled HTML rendering
- trusting database text as safe HTML

### Surface 2 — HTML Attribute

Examples:

- `value`
- `title`
- `placeholder`
- `data-*`
- `href`
- `src`
- `action`

Required:

- escape attribute values
- validate URLs before URL-like attributes
- avoid unsafe string concatenation

Forbidden:

- user-controlled event handler attributes
- unsafe URI schemes
- raw request values inside attributes

### Surface 3 — JavaScript Context

Examples:

- page config object
- embedded JSON
- expense create config
- product search config
- route config in script
- UI bootstrap data

Required:

- safe JSON encoding
- no raw interpolation of user-controlled strings
- no manual quote escaping as security control

Forbidden:

- concatenating request/database values into script
- HTML escaping used as substitute for JavaScript encoding
- inline script object built from raw strings

### Surface 4 — URL And Redirect Context

Examples:

- return URL
- back URL
- next URL
- redirect target
- form action
- product return link
- note edit return link

Required:

- relative internal path validation
- unsafe scheme rejection
- safe fallback route

Forbidden:

- arbitrary external redirect
- `javascript:`
- `data:`
- `//host`
- unvalidated request target

### Surface 5 — File Path Context

Examples:

- supplier payment proof attachment path
- private storage path
- attachment identifier
- proof file download route

Required:

- file resolved from server-owned identifier
- disk allow-list
- path allow-list
- path traversal protection
- no absolute local path leak

Forbidden:

- direct request path to filesystem
- private path to public helper
- public URL for private evidence

### Surface 6 — Attachment Response Context

Examples:

- proof attachment download
- proof attachment inline preview
- PDF/image proof serving

Required:

- auth and policy
- server-side MIME decision
- safe fallback MIME
- safe content disposition
- sanitized filename
- `nosniff`

Forbidden:

- trusting client MIME
- trusting extension alone
- inline unknown/risky content
- serving private proof without authorization

### Surface 7 — Count And Statistic Context

Examples:

- total note count
- cashier create default count
- global notes statistic
- global operational statistics
- dashboard snippets exposed to cashier

Required:

- actor-scoped visibility
- default deny
- cashier count only when explicitly scoped and needed

Forbidden:

- cashier global count leak
- count query as hidden naming helper when it exposes global scope
- accidental admin/global stats shown to cashier

## Preferred Policy Components

Exact class names may be adjusted after source inspection.

### SafeReturnUrlPolicy

Purpose:

- validate return/back/next URL
- allow relative internal path
- reject unsafe/external targets
- return safe fallback when invalid

Inputs:

- raw URL string
- fallback route/path
- optional allowed path prefixes

Output:

- safe internal path

### SafeOutputEncodingGuideline Or Helper

Purpose:

- define context-specific output rules
- prevent raw user-controlled HTML
- standardize JS config encoding pattern

This may be implemented as documentation plus tests if framework helpers already cover the behavior.

### PrivateFileServingPolicy

Purpose:

- decide whether actor may serve a private file
- ensure disk/path allow-list
- prevent private storage exposure through public helper

Inputs:

- actor
- file owner/resource
- file disk/path or server-owned file id
- action: inline or download

Output:

- allowed/denied
- safe serving metadata

### AttachmentResponseFactory

Purpose:

- centralize safe file response construction
- sanitize filename
- choose safe MIME
- choose inline/download disposition
- apply `nosniff`

Inputs:

- server-resolved file path/stream
- server-detected MIME or trusted server metadata
- original filename as display hint
- requested disposition

Output:

- safe response

### DisclosurePolicy

Purpose:

- decide whether count/stat is visible to actor
- enforce cashier deny-by-default for global count
- allow scoped counts only when explicitly needed

Inputs:

- actor
- statistic name
- scope
- route/use-case context

Output:

- allowed/denied
- optional scoped query constraints


---

## Related Documents

- DoD: docs/03_blueprints/security/adr-0020-public-surface-dod.md
- Workflow: docs/03_blueprints/security/adr-0020-public-surface-workflow.md
