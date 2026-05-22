# Master Issue Register

This is the main list of known findings from Stage 0 and Batch 1B. It is not exhaustive. Each entry points to the detail file that should own future expansion.

## MRD-001

Severity: P0
Area: Finance/stock/payment/refund write readiness
Type: readiness-debt
Status: open
Short problem: Finance, Stock, Payment, Refund Write Ownership Is Not Safe For Early Go Ownership
Source detail file: [findings/05_domain_mutation_readiness.md](findings/05_domain_mutation_readiness.md)
Current proof status: GAP - no complete write mutation map, Go/Laravel golden parity suite, or PostgreSQL write parity proof.
Next proof needed: Complete mutation map with owner, lock, idempotency, audit, rollback, and parity proof.

## MRD-002

Severity: P0
Area: PostgreSQL/schema/query readiness
Type: gap
Status: needs-proof
Short problem: PostgreSQL Fresh Migration And Runtime Parity Are Not Proven
Source detail file: [findings/03_database_postgresql_readiness.md](findings/03_database_postgresql_readiness.md)
Current proof status: GAP - no PostgreSQL fresh migration, seed/import, or parity harness proof.
Next proof needed: PostgreSQL fresh migration, fixture import, row-count/rupiah reconciliation, and critical query parity.

## MRD-003

Severity: P1
Area: API contract/readiness
Type: readiness-debt
Status: open
Short problem: API Contract And Error Envelope Are Not Frozen
Source detail file: [findings/02_api_contract_readiness.md](findings/02_api_contract_readiness.md)
Current proof status: GAP - no OpenAPI/schema contract or cross-implementation contract tests read.
Next proof needed: Frozen JSON envelope, error shape, pagination/filter/sort, auth failure, and mobile compatibility fixtures.

## MRD-004

Severity: P1
Area: PostgreSQL/schema/query readiness
Type: readiness-debt
Status: needs-proof
Short problem: Raw SQL And Query Builder Portability Need Classification
Source detail file: [findings/03_database_postgresql_readiness.md](findings/03_database_postgresql_readiness.md)
Current proof status: GAP - raw SQL and query-builder constructs are scanned but not fully classified.
Next proof needed: Query inventory marked portable, needs rewrite, needs PostgreSQL test, or accepted risk.

## MRD-005

Severity: P1
Area: Transaction/idempotency/audit readiness
Type: readiness-debt
Status: open
Short problem: Transaction Boundary Contract Is Too Thin For Cross-Stack Ownership
Source detail file: [findings/04_transaction_idempotency_audit.md](findings/04_transaction_idempotency_audit.md)
Current proof status: GAP - no global transaction boundary map or isolation/retry policy.
Next proof needed: Per-use-case transaction, lock, retry, rollback, and after-commit policy map.

## MRD-006

Severity: P1
Area: Transaction/idempotency/audit readiness
Type: readiness-debt
Status: open
Short problem: Idempotency Coverage Is Partial And Not A Global Write Contract
Source detail file: [findings/04_transaction_idempotency_audit.md](findings/04_transaction_idempotency_audit.md)
Current proof status: GAP - no full mutation idempotency matrix.
Next proof needed: Idempotency matrix with key source, uniqueness scope, replay behavior, expiration, and audit policy.

## MRD-007

Severity: P1
Area: Transaction/idempotency/audit readiness
Type: readiness-debt
Status: needs-proof
Short problem: Audit Canonical Path And Legacy Audit Log Split Need Write-Path Classification
Source detail file: [findings/04_transaction_idempotency_audit.md](findings/04_transaction_idempotency_audit.md)
Current proof status: GAP - no full audit writer usage map or canonical-vs-legacy policy per bounded context.
Next proof needed: Audit write-path matrix with required facts and parity tests.

## MRD-008

Severity: P1
Area: Security/public surface
Type: readiness-debt
Status: needs-proof
Short problem: Security Public Surface Needs Route/Auth/Rate/Deployment Proof
Source detail file: [findings/01_security_public_surface.md](findings/01_security_public_surface.md)
Current proof status: GAP - no full public route matrix, brute-force proof, or production deployment security proof.
Next proof needed: Public route inventory with auth, role, throttle, validation, upload, envelope, and deployment assumptions.

## MRD-009

Severity: P2
Area: Blade/JS/UI payload readiness
Type: readiness-debt
Status: needs-proof
Short problem: Blade/JS Payload Coupling Remains A Contract And Browser Proof Gap
Source detail file: [findings/06_blade_js_ui_payload_readiness.md](findings/06_blade_js_ui_payload_readiness.md)
Current proof status: GAP - no full browser QA, Blade/JS sink audit, or backend-vs-UI contract matrix.
Next proof needed: UI payload contract map, tampered payload tests, and browser/sink audit.

## MRD-010

Severity: P2
Area: Test/proof readiness
Type: gap
Status: needs-proof
Short problem: Test And Proof Readiness Is Broad But Not Migration-Complete
Source detail file: [findings/07_test_proof_readiness.md](findings/07_test_proof_readiness.md)
Current proof status: GAP - no full suite, PostgreSQL suite, Go/Laravel parity suite, or shadow compare harness run.
Next proof needed: Migration proof matrix and minimal PostgreSQL/API/browser/shadow harnesses.

## MRD-011

Severity: P3
Area: Architecture/hexagonal boundary readiness
Type: gap
Status: needs-proof
Short problem: Hexagonal Boundary Exists But Runtime/Contract Completeness Is Not Proven
Source detail file: [findings/08_architecture_boundary_readiness.md](findings/08_architecture_boundary_readiness.md)
Current proof status: FACT for structure; GAP for complete port/provider/command/result/error review.
Next proof needed: Port and adapter matrix, provider binding map, use case command/result/error map.

## TW-001

Severity: P2
Area: Migration strategy
Type: transition-work
Status: transition-work
Short problem: Creating Go Service, PostgreSQL Adapter, Migration Runner, And Shadow Compare Is Transition Work
Source detail file: [transition_work/01_not_readiness_debt.md](transition_work/01_not_readiness_debt.md)
Current proof status: FACT - existing docs treat Go/PostgreSQL as future staged work.
Next proof needed: Stage 1 selection of first read-only target and proof harness requirements.

## SEC-1B-01

Severity: P1
Area: Security/public surface
Type: proof-gap
Status: needs-proof
Short problem: Login/auth surfaces have no route-level rate-limit proof
Source detail file: [findings/01_security_public_surface.md](findings/01_security_public_surface.md)
Current proof status: GAP - no route-level throttle or external limiter proof read.
Next proof needed: Login throttle policy and feature tests.

## SEC-1B-02

Severity: P1
Area: Security/API readiness
Type: readiness-debt
Status: needs-proof
Short problem: Legacy transaction writes using transaction.entry need explicit migration auth/capability matrix
Source detail file: [findings/01_security_public_surface.md](findings/01_security_public_surface.md)
Current proof status: GAP - no complete route-to-capability matrix.
Next proof needed: Route-by-route capability matrix and policy tests.

## SEC-1B-03

Severity: P1
Area: API contract/readiness
Type: readiness-debt
Status: needs-proof
Short problem: Mobile supplier proof upload is authenticated finance/procurement write API with upload/rate/idempotency proof gaps
Source detail file: [findings/05_domain_mutation_readiness.md](findings/05_domain_mutation_readiness.md)
Current proof status: GAP - no route-level rate, idempotency, retry-safety, or full mobile client contract proof.
Next proof needed: Upload/payment mutation contract with storage, idempotency, audit, and response fixtures.

## SEC-1B-04

Severity: P1
Area: Transaction/idempotency/audit readiness
Type: readiness-debt
Status: open
Short problem: Note/payment/refund/procurement writes need per-mutation auth/validation/transaction/idempotency/audit matrix
Source detail file: [findings/04_transaction_idempotency_audit.md](findings/04_transaction_idempotency_audit.md)
Current proof status: GAP - no complete per-mutation map.
Next proof needed: Command/result/error, auth, validation, transaction, lock, audit, projection, idempotency, and rollback matrix.

## SEC-1B-B-01

Severity: P1
Area: Finance/stock/payment/refund write readiness
Type: readiness-debt
Status: open
Short problem: Stock, payroll, employee debt, and expense mutation routes lack route-level idempotency/transaction/rollback proof
Source detail file: [findings/05_domain_mutation_readiness.md](findings/05_domain_mutation_readiness.md)
Current proof status: GAP - no route-level idempotency, transaction, rollback, or reversal parity proof.
Next proof needed: Domain mutation map and duplicate-submit/reversal/rollback tests.

## SEC-1B-B-02

Severity: P1
Area: Security/public surface
Type: readiness-debt
Status: needs-proof
Short problem: Reporting/export routes expose sensitive finance/stock/payroll data; export governance/rate/volume proof is GAP
Source detail file: [findings/01_security_public_surface.md](findings/01_security_public_surface.md)
Current proof status: GAP - no route-level throttle, download governance, or per-report volume policy proof.
Next proof needed: Report/export contracts, volume limits, governance decision, and parity fixtures.

## SEC-1B-B-03

Severity: P1
Area: Security/API readiness
Type: proof-gap
Status: needs-proof
Short problem: Broad admin.page is common admin gate; route-specific capability proof is GAP for high-value surfaces
Source detail file: [findings/01_security_public_surface.md](findings/01_security_public_surface.md)
Current proof status: GAP - no route-specific capability matrix for high-value admin surfaces.
Next proof needed: Route-to-role/capability matrix and equivalent Go auth fixtures.

## SEC-1B-B-04

Severity: P2
Area: API contract/readiness
Type: contract-gap
Status: needs-proof
Short problem: Read-only table JSON endpoints have query validation but no frozen API envelope/rate/versioning contract
Source detail file: [findings/02_api_contract_readiness.md](findings/02_api_contract_readiness.md)
Current proof status: GAP - no versioned API contract, rate proof, or cross-stack fixture read.
Next proof needed: Query parameter, pagination, error, and envelope contract with snapshots.

## SEC-1B-B-05

Severity: P2
Area: Security/public surface
Type: proof-gap
Status: needs-proof
Short problem: Push notification subscription routes have validation but rate/origin/browser lifecycle proof is GAP
Source detail file: [findings/01_security_public_surface.md](findings/01_security_public_surface.md)
Current proof status: GAP - no rate, origin/CORS, browser lifecycle, or idempotent delete proof.
Next proof needed: Store/delete contract and browser lifecycle proof.

## SEC-1B-B-06

Severity: P2
Area: API contract/readiness
Type: contract-gap
Status: needs-proof
Short problem: Dashboard analytics uses raw Request; month validation and read contract are GAP
Source detail file: [findings/02_api_contract_readiness.md](findings/02_api_contract_readiness.md)
Current proof status: GAP - no explicit month FormRequest, response contract, or rate/volume proof read.
Next proof needed: Month input contract, JSON response fixture, and parity proof for analytics totals.

## SEC-1B-B-07

Severity: P2
Area: Audit/security readiness
Type: proof-gap
Status: needs-proof
Short problem: Audit-log page is admin-only and paginated, but read governance/access logging proof is GAP
Source detail file: [findings/01_security_public_surface.md](findings/01_security_public_surface.md)
Current proof status: GAP - no access logging, retention/export policy, or route-specific capability proof.
Next proof needed: Audit-log read governance and access policy proof.
