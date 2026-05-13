# Security ADR-0019 Access Boundary Definition of Done
## Status
Canonical DoD.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0019-access-boundary.md`


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

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0019-access-boundary.md
- Workflow: docs/03_blueprints/security/adr-0019-access-boundary-workflow.md
