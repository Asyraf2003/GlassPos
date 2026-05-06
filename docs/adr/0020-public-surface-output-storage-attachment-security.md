# ADR-0020 — Public Surface, Output Context, Storage Boundary, and Attachment Security

## Status

Accepted for planning.

Implementation status: not implemented yet.

This ADR records owner-approved security direction for public surface, output context, storage boundary, attachment serving, unsafe URL handling, and disclosure policy.

This ADR does not mark any `docs/error_log/*.md` finding as fixed.

A finding is fixed only after characterization test, implementation proof, relevant blast-radius verification, owner review, and accepted diff.

## Context

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

The system handles operational finance, notes, payments, refunds, stock, supplier evidence, reports, and cashier/admin workflows. Therefore public output, private storage, attachment serving, and disclosure behavior must be treated as security boundaries.

Existing security audit summary classified multiple error logs into public surface, output context, unsafe URL, storage, attachment, and disclosure clusters.

ADR-0019 handles access boundary, cashier date window, admin transaction capability, route guard, supplier invoice capability, and capability audit.

ADR-0020 handles public/output/storage/attachment security.

ADR-0020 must not replace ADR-0019.

Final sensitive request handling may require both:

1. ADR-0019 access/capability policy
2. ADR-0020 output/storage/public-surface policy

## Problem Statement

The system has multiple public-surface and output-security risks:

1. Stored or reflected user-controlled data can reach HTML, JavaScript, URL, or attribute contexts.
2. Return URL or back-link values can become unsafe if trusted directly.
3. Private storage files can be exposed if served through public helpers or public paths.
4. Attachment content type can be unsafe if the system trusts client MIME, filename, or file extension.
5. Cashier-facing pages can leak global counts or statistics outside the cashier's legitimate working scope.
6. Patch-by-route fixes can close one issue while leaving the same class of bug in another context.

The core issue is not one Blade bug or one unsafe attachment route.

The core issue is missing explicit public-surface policy.

## Decision

ADR-0020 is the umbrella decision for:

- stored XSS
- reflected XSS
- Blade output context safety
- JavaScript config encoding
- unsafe return URL handling
- public/private storage boundary
- proof attachment serving
- MIME and content-type trust
- cashier/admin count and statistic disclosure
- safe default behavior for unknown public-surface cases

The default rule is:

If no explicit rule allows the behavior, deny, escape, fallback to a safe route, or serve as safe download.

No output, URL, file, MIME, path, or count is trusted merely because it came from an authenticated user, database row, request parameter, uploaded file, or existing UI flow.

## Owner Decisions Locked

### 1. Umbrella Scope

ADR-0020 covers all public surface, output context, storage, attachment, unsafe URL, and disclosure issues.

ADR-0020 must not be split into small route-specific decisions unless a future conflict requires a narrower ADR.

### 2. Strict System, Loose-Feeling UI

The system must feel flexible to users but remain strict internally.

Users may type ordinary text freely.

The system must not treat user/admin/cashier input as trusted HTML.

If a user enters:

    <b>test</b>

the default behavior is to display it as safe text, not execute it as HTML.

Raw HTML from database, request, user profile, note fields, expense fields, product fields, customer fields, supplier fields, descriptions, file metadata, or imported data is not trusted.

If rich text is needed later, it requires a dedicated sanitizer or whitelist decision and an ADR update.

### 3. Return URL Policy

Return URL means a path used for redirecting or linking the user back after an action.

Allowed by default:

- relative internal path only
- path starts with single `/`
- path resolves inside the application

Forbidden by default:

- `javascript:` URL
- `data:` URL
- external full URL
- protocol-relative URL beginning with `//`
- raw unvalidated URL from request
- redirect target controlled by user input without validation
- route action that redirects to arbitrary request-provided URL

Invalid return URL must fall back to a safe default route.

External redirect allow-listing is out of scope for this ADR and requires a future explicit decision if needed.

### 4. Storage Boundary

Private files must never be exposed through:

- `asset()`
- public helper
- direct `public_path`
- public symlink as a shortcut
- raw request path
- user-controlled disk/path input
- permanent public URL for private proof material

Private or sensitive files must be served only through a controlled route or controller that enforces:

- authentication
- authorization or policy
- disk allow-list
- path allow-list
- path traversal protection
- no absolute local path leak
- no client-controlled file path trust

### 5. Attachment Serving

Default attachment behavior must be safe and user-friendly.

Images and PDFs may be served inline only when all conditions are true:

- actor is authorized
- file belongs to an allowed disk/path
- server-side MIME detection or trusted server metadata allows inline viewing
- content type is on the inline allow-list
- response includes safe headers

Unknown, risky, or unsupported files must be served as downloads.

Fallback MIME:

    application/octet-stream

Required attachment response hardening:

- do not trust client-provided MIME
- do not trust file extension alone
- sanitize output filename
- use `X-Content-Type-Options: nosniff`
- use safe `Content-Disposition`
- force download for risky content types
- prevent path traversal
- deny or 404 missing files according to route policy
- never expose private storage internals

### 6. Count And Statistic Disclosure

Default disclosure policy is deny-by-default.

Cashier must not see global note count, global statistics, or cross-window operational counts unless an explicit rule allows it.

If cashier count is required later, it must be scoped through cashier access policy and working window.

Current default:

- cashier global count: denied
- cashier count outside date/window scope: denied
- admin authorized read pages may show global counts
- admin sensitive mutation remains governed by ADR-0019 transaction capability

### 7. Unauthorized And Invalid Response Policy

Default response policy:

- guest access: existing auth middleware behavior
- authenticated but unauthorized: HTTP 403
- unsafe return URL: validation error or safe fallback
- missing private file: HTTP 404
- unauthorized private attachment access: HTTP 403
- anti-enumeration 404: allowed only if explicitly chosen per route/policy

Security denial must not silently continue to a dangerous action.

### 8. Rollout Priority

Implementation priority:

1. output context inventory
2. stored/reflected XSS tests
3. JavaScript config safe encoding
4. return URL allow-list and safe fallback
5. storage/private helper inventory
6. attachment serving policy
7. MIME/content-disposition hardening
8. count/stat disclosure hardening
9. security blast-radius tests
10. update `docs/error_log` only after proof

### 9. Non-Goals

ADR-0020 does not solve:

- ADR-0019 access/capability implementation
- admin transaction capability policy
- cashier date window access policy
- payment concurrency
- payment over-allocation
- seeder credential safety
- finance settlement
- carry-forward settlement
- overpaid/kembalian workflow
- refund lifecycle semantics
- current projection domain design
- large UI rewrite
- domain terminology changes
- new public route design outside approved policy
- large upload lifecycle redesign beyond minimal attachment serving safety

### 10. Error Log Fixed Rule

No error log is final fixed only because a patch exists.

No error log is final fixed only because a document says `Patched`.

Final fixed requires:

- red characterization test exists before patch
- targeted test fails for expected reason
- patch is scoped to the approved slice
- targeted test passes after patch
- relevant blast-radius tests pass
- diff is reviewed
- owner accepts proof
- `docs/error_log` is updated only after proof

### 11. Deny-By-Default Rule

For public/output/storage/disclosure behavior:

If no explicit rule allows it, deny it.

If output context is unknown, escape it.

If URL is unsafe or unknown, reject it or fallback safe.

If file type is unknown, serve as download with safe fallback MIME.

If count/stat scope is unclear, do not disclose it.

## Scope In

ADR-0020 applies to:

- Blade templates
- server-rendered HTML
- HTML attributes
- JavaScript configuration embedded in HTML
- JSON embedded in script context
- href/action/back/return URL values
- redirects using request-provided targets
- product/customer/note/supplier/expense text fields shown in UI
- file names shown in UI
- uploaded proof attachment names
- supplier payment proof serving
- private storage file serving
- public helper usage
- attachment MIME/content-type handling
- cashier-visible count/stat values
- admin-visible public-surface output where user-controlled data is displayed

## Scope Out

ADR-0020 does not create a full generic sanitization framework.

ADR-0020 does not approve rich-text rendering.

ADR-0020 does not change existing domain decisions.

ADR-0020 does not replace access control.

ADR-0020 does not decide payment or refund business eligibility.

ADR-0020 does not authorize any public exposure of private files.

ADR-0020 does not mark legacy unsafe patterns as acceptable.

## Binding Principles

### Principle 1 — Context-Aware Output

Escaping must match the output context.

HTML text, HTML attribute, URL, JavaScript string, JavaScript object, and JSON contexts are different contexts.

A value safe in HTML text may still be unsafe inside script context or URL context.

### Principle 2 — No Raw User-Controlled HTML

User-controlled data must not be rendered with raw HTML output.

Forbidden for user-controlled values:

- raw Blade output
- manual script string concatenation
- unescaped attribute injection
- unvalidated href/action injection
- direct rendering of database text as HTML

### Principle 3 — Safe JavaScript Config

Server data embedded in JavaScript must use safe JSON encoding.

Manual string concatenation into `<script>` is forbidden for user-controlled data.

JavaScript config must be generated through a helper, framework-safe encoder, or explicit safe pattern.

### Principle 4 — URL Allow-List

URL targets must be validated before use.

Return URL policy is internal-relative by default.

Unsafe schemes and external URLs are denied unless a future allow-list decision exists.

### Principle 5 — Private Storage Is Not Public Surface

Private storage remains private.

Serving private file content requires an authorized controller path.

A helper that turns private paths into public URLs violates this ADR unless the file has been explicitly classified public.

### Principle 6 — Server Owns MIME Decision

The server owns content type decisions.

Client-provided MIME, filename, and extension are hints at most, not authority.

### Principle 7 — Disclosure Is Authorization

Counts and statistics can reveal business information.

Count/stat visibility is an authorization question, not a decorative UI question.

### Principle 8 — UI Convenience Must Not Weaken Security

The UI may be easy.

The server boundary must remain strict.

If convenience conflicts with safety, the system must choose safety and provide a user-friendly safe fallback.

## Error Log Coverage

ADR-0020 covers or partially covers these findings:

| Error Log | Coverage | Decision |
|---|---|---|
| 007 admin note edit page exposes stored XSS | Full | Context-aware escaping and no raw user-controlled HTML |
| 023 public helper can expose private storage | Full | Private storage cannot be served through public helper |
| 024 reflected XSS in expense create JSON config | Full | Safe JSON encoding for script context |
| 025 reflected JavaScript URL in product return link | Full | Relative internal return URL allow-list |
| 028 unsafe proof attachment content type | Full | Server-side MIME policy and safe attachment serving |
| 029 cashier create page leaks total note count | Partial | Count/stat disclosure default deny; access slice remains ADR-0019 |

ADR-0020 does not cover the access authorization half of `029`; ADR-0019 remains source of truth for actor access.

## Policy Requirements By Surface

### HTML Text

Required:

- escape user-controlled text
- preserve readable UI
- render unsafe-looking input as visible text, not markup

Forbidden:

- raw user-controlled HTML rendering
- trusting database text as safe HTML

### HTML Attribute

Required:

- escape attribute values
- validate URLs before inserting into `href`, `src`, `action`, or similar attributes
- avoid building attributes through string concatenation

Forbidden:

- unvalidated request value inside attribute
- unsafe URI scheme in attribute
- user-controlled event handler attributes

### JavaScript Context

Required:

- safe JSON encoding
- safe config object generation
- no raw interpolation of user-controlled string into script

Forbidden:

- manual quote escaping as security control
- concatenating request/database value into script
- using HTML escaping as a substitute for JavaScript encoding

### URL And Redirect Context

Required:

- relative internal path validation
- safe fallback route
- rejection of unsafe schemes
- rejection of external targets unless future allow-list exists

Forbidden:

- trusting `return_url`, `next`, `back`, `redirect`, or similar request values
- accepting `javascript:` or `data:`
- accepting `//host`
- accepting full external URL by default

### File Path Context

Required:

- route/controller resolves file from server-owned identifier
- disk/path allow-list
- path traversal protection
- no direct path from request to filesystem
- no absolute path leak

Forbidden:

- using request path directly
- exposing storage path through public helper
- returning private local path in response

### Attachment Response Context

Required:

- auth/policy
- server-side MIME decision
- safe fallback MIME
- safe content disposition
- sanitized filename
- `nosniff`

Forbidden:

- trusting client MIME
- trusting extension alone
- inline serving unknown or risky content
- serving private proof without authorization

### Count And Statistic Context

Required:

- actor-scoped visibility
- default deny when no rule exists
- cashier counts scoped only when explicitly needed

Forbidden:

- cashier global count leak
- count query used as default customer naming source if it exposes global scope
- exposing admin/global operational stats to cashier by accident

## Alternatives Considered

### Alternative A — Patch Each Reported View Or Route Individually

Rejected as final direction.

Pros:

- fast for one issue
- smaller initial diff

Cons:

- repeated XSS/storage mistakes
- no shared policy
- hard to audit
- same class of bug can reappear elsewhere
- weak for finance-sensitive POS

Allowed only as emergency hotfix after ADR-0020 direction is accepted.

### Alternative B — Allow Admin Raw HTML

Rejected for current scope.

Pros:

- flexible display
- rich formatting possible

Cons:

- stored XSS risk
- admin account compromise becomes script execution path
- hard to distinguish trusted from untrusted content
- unnecessary for current operational POS workflow

May be reconsidered only with sanitizer/whitelist and explicit ADR update.

### Alternative C — Trust Client MIME Or Filename

Rejected.

Pros:

- simple implementation
- less server work

Cons:

- unsafe file serving
- content sniffing risk
- dangerous inline execution risk
- client-controlled metadata is not authority

### Alternative D — Make All Attachments Download Only

Rejected as default final behavior but allowed for risky files.

Pros:

- safest serving model
- simple

Cons:

- worse user experience for images/PDF proof files
- unnecessary friction for common operational evidence

Chosen compromise:

- safe image/PDF inline when authorized and server-validated
- download fallback for unknown/risky files

### Alternative E — Public Disk For Proof Attachments

Rejected.

Pros:

- simple URL serving
- minimal controller code

Cons:

- private evidence exposure
- difficult revocation
- weak audit boundary
- violates finance-sensitive evidence handling

## Consequences

### Positive Consequences

- Output security becomes policy-driven, not patch-by-patch.
- XSS fixes become auditable by context.
- Private files remain behind authorization.
- Attachment behavior remains user-friendly without trusting unsafe metadata.
- Cashier disclosure becomes deny-by-default.
- Future public-surface fixes can refer to one ADR.

### Negative Consequences

- More tests are required before patch.
- Some existing UI shortcuts may need refactoring.
- Inline attachment preview requires MIME and header discipline.
- Some return URL convenience may be removed.
- Some templates may need helper extraction to avoid repeated encoding logic.

### Accepted Tradeoff

The system chooses strict server-side safety over shortcut convenience.

UI may remain friendly, but public-surface behavior must be explicitly authorized and context-safe.

## Implementation Direction

Implementation must be slice-based.

Recommended slices:

1. XSS and output context
2. JavaScript config encoding
3. return URL allow-list and fallback
4. storage/public helper boundary
5. attachment serving and MIME hardening
6. count/stat disclosure
7. blast-radius verification and documentation update

Each slice must start with a characterization test.

No broad refactor is allowed before exact affected files are identified.

## Required Verification Themes

Implementation proof must include relevant tests for:

- stored XSS payload rendered as safe text
- reflected XSS payload not executable
- JavaScript config encoded safely
- unsafe return URL rejected or safe fallback used
- `javascript:` URL rejected
- `data:` URL rejected
- protocol-relative URL rejected
- external URL rejected by default
- private storage not exposed through public helper
- proof attachment requires auth/policy
- unknown attachment MIME served as download with safe fallback
- server-side MIME/content-disposition policy applied
- cashier global count not exposed
- no error_log status update before proof

## Documentation Rule

`docs/error_log/*.md` may be updated only after implementation proof exists.

ADR and blueprint may be created before implementation.

Error log status must distinguish:

- reported
- accepted risk
- planned
- patched with verification gap
- fixed with proof
- deferred with owner acceptance

If proof is missing, the status must remain a gap.

## Stop Conditions

Stop immediately if:

- patch relies on UI hiding as security boundary
- patch trusts client MIME, filename, extension, path, URL, or count scope
- patch exposes private storage through public helper
- patch renders raw user-controlled HTML
- patch adds external redirect support without allow-list ADR
- patch changes ADR-0019 access policy without opening ADR-0019 scope
- patch changes payment/refund/finance lifecycle
- patch touches seeder credential behavior
- test failure reason is not understood
- diff grows beyond the approved slice
- source code contradicts owner decisions in this ADR

## Related Documents

- docs/audit/codex-security/2026-05-06-error-log-solution-and-adr-coverage-summary.md
- docs/blueprint/security/2026-05-06-adr-0019-access-boundary-blueprint.md
- docs/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
- docs/adr/0018-note-revision-settlement-external-product-lifecycle.md
- docs/error_log/007-admin-note-edit-page-exposes-stored-xss.md
- docs/error_log/023-public-helper-can-expose-private-storage.md
- docs/error_log/024-reflected-xss-in-expense-create-json-config.md
- docs/error_log/025-reflected-javascript-url-in-product-return-link.md
- docs/error_log/028-di-fix-exposes-unsafe-proof-attachment-content-type.md
- docs/error_log/029-cashier-create-page-leaks-total-note-count.md

## Final Rule

Public surface security is not optional UI polish.

For HyperPOS, output, URL, storage, attachment, and disclosure behavior are production safety boundaries.

If the system cannot prove the behavior is safe, the behavior must be denied, escaped, downloaded safely, or replaced with a safe fallback.
