# Security ADR-0020 Public Surface Workflow
## Status
Canonical Workflow.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0020-public-surface.md`


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
8. final handoff update in docs/04_lifecycle/handoff

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

    sed -n '1,260p' docs/02_architecture/adr/0020-public-surface-output-storage-attachment-security.md
    sed -n '1,320p' docs/03_blueprints/security/adr-0020-public-surface.md

### Error Log Snapshot

Run before selecting ADR-0020 slice:

    sed -n '1,220p' docs/04_lifecycle/error_log/007-admin-note-edit-page-exposes-stored-xss.md
    sed -n '1,220p' docs/04_lifecycle/error_log/023-public-helper-can-expose-private-storage.md
    sed -n '1,220p' docs/04_lifecycle/error_log/024-reflected-xss-in-expense-create-json-config.md
    sed -n '1,220p' docs/04_lifecycle/error_log/025-reflected-javascript-url-in-product-return-link.md
    sed -n '1,220p' docs/04_lifecycle/error_log/028-di-fix-exposes-unsafe-proof-attachment-content-type.md
    sed -n '1,220p' docs/04_lifecycle/error_log/029-cashier-create-page-leaks-total-note-count.md

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
    git diff -- docs/02_architecture/adr/0020-public-surface-output-storage-attachment-security.md docs/03_blueprints/security/adr-0020-public-surface.md
    git diff -- app routes resources tests docs/error_log

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

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0020-public-surface.md
- DoD: docs/03_blueprints/security/adr-0020-public-surface-dod.md
