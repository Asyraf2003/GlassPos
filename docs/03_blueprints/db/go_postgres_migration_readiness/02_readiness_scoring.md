# Readiness Scoring

These scores are discovery estimates only. They are not readiness claims and must not be used as production cutover approval.

## Current Estimates

- Stage 0 blueprint preparation: around 90%
- Issue register initial readiness: around 85%
- Security/API route-surface blueprint: around 75%
- Full migration readiness blueprint: around 40%
- Actual smooth migration readiness: around 25%

## Why Actual Readiness Is Lower Than Blueprint Readiness

FACT:
The blueprint has enough structure to guide discovery and remediation sequencing.

RISK:
Documentation maturity can hide runtime and parity gaps if it is mistaken for implementation proof.

GAP:
The system still lacks proven PostgreSQL runtime migration, DB parity harness, API contract freeze, mutation map, idempotency/audit/transaction proof, browser/UI payload proof, and complete test/proof matrix.

## What Would Increase Blueprint Scores

- Register all P0/P1 candidates with evidence, proof gaps, and owner-ready next proof.
- Complete Batch 2 PostgreSQL/database readiness discovery.
- Complete architecture, domain mutation, Blade/JS, and test/proof batches.
- Keep every finding linked to source evidence.

## What Would Increase Actual Smooth Migration Readiness

- PROOF: PostgreSQL fresh migration runs successfully.
- PROOF: Seed/import or fixture load works against PostgreSQL.
- PROOF: Row-count, rupiah, payment/refund/allocation, inventory/projection, and audit parity reports exist.
- PROOF: API contracts are frozen with golden fixtures.
- PROOF: High-risk mutation map is complete with transaction, idempotency, audit, rollback, and concurrency expectations.
- PROOF: Browser/UI payload boundaries are tested.
- PROOF: Shadow compare and rollback paths are exercised.

## What Cannot Increase Without Proof

- PostgreSQL runtime migration.
- DB parity harness.
- API contract freeze.
- Mutation map.
- Idempotency/audit/transaction proof.
- Browser/UI payload proof.
- Test/proof matrix.

## Scoring Rule

DECISION:
If a claim is only documented but not proven by runtime execution, tests, contract fixtures, or source-level evidence, it remains a GAP and cannot raise actual smooth migration readiness.
