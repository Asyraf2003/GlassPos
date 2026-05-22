# Test And Proof Readiness Findings

Scope source: Stage 0 baseline.

## MRD-010 - Test And Proof Readiness Is Broad But Not Migration-Complete

ID: MRD-010

Status: needs-proof

Severity: P2

Area: Test/proof readiness

Type: proof-gap

Evidence:

- `find tests -maxdepth 2 -type d -print`
- `find tests/Feature/MobileApi -maxdepth 2 -type f -print`
- `find tests/Feature/Database -maxdepth 2 -type f -print`
- `find tests/Feature/Payment -maxdepth 2 -type f -print`
- `find tests/Feature/Procurement -maxdepth 2 -type f -print`
- `find tests/Arch -maxdepth 2 -type f -print`
- `tests/Arch/HexagonalDependencyTest.php`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `database/migrations/README.md`

FACT:

- Unit, feature, database, mobile API, procurement, payment, reporting, and architecture test directories exist.
- Mobile API, payment, procurement, and database feature tests exist.
- Architecture test file exists.
- Full test suite was intentionally not run in Stage 0 discovery.

RISK:

- Existing tests may prove current Laravel/MySQL behavior but not PostgreSQL runtime, Go contract parity, shadow compare, or browser/API cutover safety.

GAP:

- No full suite run for this discovery.
- No PostgreSQL test suite execution.
- No Go/Laravel contract parity tests.
- No shadow compare harness.
- No browser QA.
- No live DB parity.

Why it matters for smooth Go/PostgreSQL transition:

Migration safety depends on proof, not structure. Without parity tests, every phased migration step relies on manual inspection.

Recommended direction:

Treat proof infrastructure as Stage 1 work after Stage 0 inventory is complete.

Proof required:

- Test coverage matrix by domain and migration risk.
- PostgreSQL compatibility job.
- API contract fixture parity.
- Shadow compare plan.
- Browser/UI payload proof.
- Live DB parity proof before any real cutover.

Suggested test/proof:

- Minimal PostgreSQL migration smoke suite.
- Contract snapshots for read-only APIs.
- Domain/use case tests for finance/stock invariants.
- Browser tests for UI payload boundaries.
- Shadow compare reports for read-only endpoints.

Do not fix yet: yes

## Required Proof Types

Status: non-register note

Area: Test/proof readiness

Type: proof-gap summary

Evidence:

- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `database/migrations/README.md`
- Stage 0 `MRD-010`

FACT:

- Existing docs require proof before PostgreSQL cutover and Go API production use.

RISK:

- Running only Laravel/MySQL tests does not prove cross-stack migration safety.

GAP:

- PostgreSQL runtime proof.
- DB compatibility tests.
- API contract tests.
- Adapter/query parity tests.
- UI/browser proof.
- Data parity proof.

Why it matters for smooth Go/PostgreSQL transition:

Each migration step needs a proof type that matches the failure mode it can introduce.

Recommended direction:

Build the proof matrix before remediation execution begins.

Proof required:

- Mapping from each finding to required test/proof type.

Suggested test/proof:

- One minimal proof harness per high-risk category before implementation changes.

Do not fix yet: yes
