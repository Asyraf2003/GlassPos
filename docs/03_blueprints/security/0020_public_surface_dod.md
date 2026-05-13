# Security ADR-0020 Public Surface Definition of Done
## Status
Canonical DoD.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0020-public-surface.md`


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

Do not update `docs/04_lifecycle/error_log/*.md` before implementation proof.

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

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0020-public-surface.md
- Workflow: docs/03_blueprints/security/adr-0020-public-surface-workflow.md
