# ADR-0020 Public Surface, Output Context, Storage Boundary, Attachment Security Blueprint, DoD, and CLI Workflow

## Status

Planning blueprint.

This document is not an implementation patch.

This document does not mark any `docs/error_log/*.md` finding as fixed.

This document exists to make ADR-0020 execution rigid enough for CLI-based implementation later.

HyperPOS is a rigid finance-sensitive POS and operational system.

This is not a prototype, demo, or reduced-scope system.

## Source Of Truth

- docs/adr/0020-public-surface-output-storage-attachment-security.md
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
- User owner decisions in planning session
- User command output from local repository
- Current source code at execution time

## Proof Available Before This Blueprint

Local proof from user command output:

    git status --short

showed:

    ?? docs/adr/0020-public-surface-output-storage-attachment-security.md

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

## Characterization Test Matrix

Tests must be written before production patch for each slice.

### Slice 1 — Stored XSS And HTML Output Context

Goal:

- prove stored user-controlled fields are rendered as safe text, not executable HTML/JS

Required tests:

1. admin note edit page escapes stored note/customer text payload
2. product name payload is rendered as text when shown in relevant page
3. supplier or expense description payload is rendered as text where relevant
4. raw Blade output is not used for user-controlled field in affected view
5. unsafe payload does not create executable script or event handler

Example payloads:

    <script>alert(1)</script>
    <img src=x onerror=alert(1)>
    "><svg onload=alert(1)>

Expected:

- payload visible only as safe text or escaped output
- no raw executable tag or event handler in response
- targeted test fails before patch when bug exists
- targeted test passes after patch

### Slice 2 — JavaScript Config Encoding

Goal:

- prove server data embedded in script context is safe JSON, not raw string injection

Required tests:

1. expense create JSON config escapes reflected payload safely
2. payload cannot break out of JS string/object
3. script context does not contain raw `</script>` injection
4. encoded config remains parseable by browser/client expectations

Example payloads:

    </script><script>alert(1)</script>
    ";alert(1);//
    {"x":"</script><img src=x onerror=alert(1)>"}

Expected:

- JSON is safe
- script remains syntactically valid
- payload cannot execute

### Slice 3 — Return URL Allow-List

Goal:

- prove unsafe return/back URL cannot become executable link or redirect

Required tests:

1. `javascript:alert(1)` return URL is rejected or replaced by safe fallback
2. `data:text/html,<script>alert(1)</script>` is rejected
3. `https://evil.example/path` is rejected by default
4. `//evil.example/path` is rejected
5. `/admin/products` or approved internal path is accepted
6. invalid URL falls back to safe default

Expected:

- unsafe URL does not appear as active href/action/redirect target
- safe internal path still works
- response behavior matches route convention

### Slice 4 — Storage/Public Helper Boundary

Goal:

- prove private storage cannot be exposed through public helper or public path shortcut

Required tests:

1. private proof attachment is not served via public helper URL
2. request cannot traverse path using `../`
3. direct request path cannot select arbitrary private file
4. private file serving requires authenticated actor
5. unauthorized actor gets 403 or configured deny response
6. missing file gets 404

Expected:

- no private path leak
- no absolute local path in response
- no public URL for private proof
- policy controls access

### Slice 5 — Attachment MIME And Content-Disposition

Goal:

- prove attachment response is safe, user-friendly, and does not trust client MIME

Required tests:

1. safe PDF proof can be inline when authorized and server-validated
2. safe image proof can be inline when authorized and server-validated
3. unknown MIME falls back to download
4. risky content type is forced download
5. response includes `X-Content-Type-Options: nosniff`
6. filename is sanitized
7. client-provided MIME does not override server policy

Expected:

- user can view/download common proof files
- unknown/risky files do not inline execute
- headers are safe

### Slice 6 — Count And Statistic Disclosure

Goal:

- prove cashier create page does not leak global note count

Required tests:

1. cashier create page does not include global note count
2. default customer naming does not require global note count
3. any count shown to cashier is explicitly scoped or removed
4. admin authorized page may keep global count if appropriate
5. no hidden input or JS config leaks global count to cashier

Expected:

- cashier response contains no global count leak
- access/disclosure rule is server-side, not only visual hiding

## Implementation Order

The safest order:

1. Start with local baseline proof.
2. Read ADR-0020 and this blueprint.
3. Read relevant error logs for the selected slice.
4. Inventory exact route/controller/view/helper/storage surface.
5. Choose one slice only.
6. Add red characterization test.
7. Run targeted test and confirm expected failure.
8. Patch the smallest safe boundary.
9. Run targeted test again.
10. Run relevant blast-radius tests.
11. Show `git diff --stat` and focused diff.
12. Update `docs/error_log` only after proof.
13. Commit only after owner reviews diff and proof.
14. Move to next slice.

Recommended slice order:

1. Slice 1 stored XSS and HTML output
2. Slice 2 JavaScript config encoding
3. Slice 3 return URL allow-list
4. Slice 4 storage/public helper boundary
5. Slice 5 attachment MIME/content-disposition
6. Slice 6 count/stat disclosure
7. ADR-0020 blast-radius verification
8. final docs/handoff update

## CLI Workflow

Workflow is flexible because each slice may reveal source-specific details.

However, these rules are fixed:

1. Start every slice with git status.
2. Read relevant ADR, blueprint, and error log before editing.
3. Read current source before writing tests.
4. Add red characterization test first.
5. Run targeted test and confirm expected failure.
6. Patch the smallest safe boundary.
7. Run targeted test again.
8. Run relevant blast-radius tests.
9. Show diff.
10. Update error_log only with proof.
11. Commit only after owner approval.

## Required Commands For Execution Sessions

### Start Session Snapshot

Run before any implementation slice:

    git status --short
    git rev-parse --abbrev-ref HEAD
    git rev-parse --short HEAD
    git log --oneline -5

### ADR-0020 Document Snapshot

Run before selecting a slice:

    sed -n '1,260p' docs/adr/0020-public-surface-output-storage-attachment-security.md
    sed -n '1,320p' docs/blueprint/security/2026-05-06-adr-0020-public-surface-output-storage-attachment-blueprint.md

### Error Log Snapshot

Run before selecting ADR-0020 slice:

    sed -n '1,220p' docs/error_log/007-admin-note-edit-page-exposes-stored-xss.md
    sed -n '1,220p' docs/error_log/023-public-helper-can-expose-private-storage.md
    sed -n '1,220p' docs/error_log/024-reflected-xss-in-expense-create-json-config.md
    sed -n '1,220p' docs/error_log/025-reflected-javascript-url-in-product-return-link.md
    sed -n '1,220p' docs/error_log/028-di-fix-exposes-unsafe-proof-attachment-content-type.md
    sed -n '1,220p' docs/error_log/029-cashier-create-page-leaks-total-note-count.md

### Surface Discovery

Run before writing tests:

    grep -RIn "{!!\|return_url\|redirect\|back_url\|next_url\|asset(\|public_path\|Storage::url\|response()->file\|response()->download\|Content-Type\|mime\|supplier-payment-proof\|total.*note\|notes.*count" routes app resources tests 2>/dev/null || true

### Route Discovery

Run when route behavior is involved:

    php artisan route:list | grep -Ei "note|expense|product|supplier|proof|attachment|payment|return|cashier|admin" || true

### Test Run Pattern

Run targeted tests first.

Then run relevant blast-radius tests.

Exact test command must be selected after the test file is created.

Preferred Laravel project pattern:

    php artisan test --filter=TargetedTestName

Relevant broader test groups may include:

    php artisan test tests/Feature/Admin
    php artisan test tests/Feature/Cashier
    php artisan test tests/Feature/Procurement
    php artisan test tests/Feature/Expense
    php artisan test tests/Feature/Product

Only run broad suites when affected by the slice and local environment can handle it.

### Final Diff Snapshot

Before docs/error_log update or commit:

    git status --short
    git diff --stat
    git diff -- docs/adr/0020-public-surface-output-storage-attachment-security.md docs/blueprint/security/2026-05-06-adr-0020-public-surface-output-storage-attachment-blueprint.md
    git diff -- app routes resources tests docs/error_log

## DoD For Planning

Planning is complete only when:

- ADR-0020 exists
- ADR-0020 owner decisions are captured
- impacted error logs are mapped
- output contexts are defined
- URL policy is defined
- storage boundary is defined
- attachment serving policy is defined
- count/stat disclosure policy is defined
- deny-by-default rule is defined
- error response policy is defined
- route/surface inventory method is defined
- characterization test matrix is defined
- implementation order is defined
- CLI workflow is defined
- stop conditions are defined
- ADR-0019 access scope is not mixed into ADR-0020
- ADR-0018 finance scope is not mixed into ADR-0020
- payment concurrency is explicitly out of scope
- seeder credential safety is explicitly out of scope
- no application source patch is made during planning

## DoD For Implementation

Implementation is complete only when all relevant conditions for the selected slice are proven.

### Source Boundary

- public-surface behavior is enforced server-side where applicable
- UI hiding is not the only protection
- raw user-controlled HTML is not rendered
- JS config uses safe encoding
- return URL uses internal-relative allow-list or safe fallback
- private storage is not exposed through public helper
- attachment serving uses auth and policy
- file path is resolved from server-owned identifier
- path traversal is blocked
- MIME/content-type decision is server-owned
- unknown/risky attachment types are downloaded safely
- safe inline preview is limited to allowed image/PDF types
- `X-Content-Type-Options: nosniff` is applied for attachment serving
- filename output is sanitized
- cashier global count is not leaked
- disclosure is deny-by-default when scope is unclear

### Tests

- red characterization test exists before patch
- targeted test fails before patch for expected reason
- targeted test passes after patch
- relevant blast-radius tests pass
- no unrelated tests are weakened
- no test is changed merely to hide a failure
- XSS payloads are tested with realistic payloads
- unsafe URLs are tested with realistic payloads
- storage/attachment tests prove authorization and safe headers where relevant
- disclosure tests prove response does not leak protected count/stat

### Documentation

- docs/error_log finding is updated only after proof
- ADR is not rewritten casually during implementation
- any deviation from ADR-0020 is recorded with reason
- any deviation from this blueprint is recorded with reason
- verification gap remains visible when proof is incomplete
- no finding is marked fixed from patch existence alone

### Git

- git status is checked before and after
- diff contains only files in approved slice
- commit message references the narrow fix
- owner reviews proof before commit
- no untracked unexpected file is left unreviewed

## ADR-0020 Blast-Radius Suite

After all ADR-0020 slices are complete, run the narrowest available blast-radius suite that covers:

- admin note views
- expense create/update views
- product return links
- supplier payment proof attachment serving
- cashier create page
- relevant Blade/view audits
- relevant security tests

Suggested command set must be selected after exact test files exist.

Minimum final proof should include:

- targeted tests per fixed error_log
- relevant feature suite pass
- static or grep audit for raw output/public helper patterns
- final git diff stat
- final docs/error_log updates
- owner acceptance

## Error Log Update Rule

Do not update `docs/error_log/*.md` before implementation proof.

When updating an error log, include:

- status
- exact patch scope
- tests added
- targeted command output
- blast-radius command output
- residual gaps
- commit hash after commit, if committed
- owner acceptance note if applicable

Allowed statuses:

- Reported
- Accepted risk
- Planned
- Patched with verification gap
- Fixed with proof
- Deferred with owner acceptance

Forbidden status behavior:

- marking fixed because a patch exists
- hiding missing test proof
- converting uncertainty into success language
- deleting known gap without evidence

## Stop Conditions

Stop immediately if any of these happen:

- source code contradicts ADR-0020 owner decisions
- test requires changing ADR-0019 access policy
- test requires changing ADR-0018 finance lifecycle
- patch requires payment allocation locking or concurrency semantics
- patch requires seeder credential changes
- patch relies only on Blade/JavaScript hiding
- patch renders raw user-controlled HTML
- patch trusts client MIME, filename, extension, path, URL, or count scope
- patch exposes private storage through public helper
- patch adds external redirect support without explicit allow-list ADR
- patch serves private attachment without auth/policy
- patch allows cashier global count without explicit rule
- failing test reason is not understood
- broad refactor is needed before exact affected files are proven
- diff grows beyond approved slice
- error_log update is attempted before proof

## Handoff Rule

If session context becomes risky or implementation is paused, create handoff with:

- active ADR and blueprint
- selected slice
- owner decisions
- files changed
- tests added
- command proof
- failing tests
- residual gaps
- stop conditions triggered, if any
- safest next step
- exact opening prompt for next session

## Recommended Execution Sequence After Planning

After ADR-0020 and this blueprint are accepted, the next active implementation step should be:

1. local baseline proof
2. read ADR-0020
3. read this blueprint
4. read error_log 007
5. inventory admin note edit page and affected output surfaces
6. create stored XSS characterization test
7. confirm red failure
8. patch output context
9. confirm green test
10. run relevant admin/view blast-radius tests
11. update error_log 007 only after proof

Do not start with storage/attachment or disclosure until output context slice is either complete or explicitly deferred by owner decision.

## Final Rule

ADR-0020 treats output, URL, storage, attachment, MIME, and disclosure behavior as production security boundaries.

For HyperPOS, public surface bugs are not cosmetic defects.

If the system cannot prove a behavior is safe, the behavior must be denied, escaped, downloaded safely, or replaced with a safe fallback.
