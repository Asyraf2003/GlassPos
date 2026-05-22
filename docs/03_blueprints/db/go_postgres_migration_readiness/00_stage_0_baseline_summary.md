# Stage 0 Baseline Summary

Source baseline: [../0013_go_postgres_migration_readiness_stage_0.md](../0013_go_postgres_migration_readiness_stage_0.md)

## Migration Goal

Make the current Laravel/MySQL system mature enough that a future transition to Go API and PostgreSQL can happen gradually, smoothly, audit-ready, rollback-friendly, and without a large rewrite.

## Non-Goals

- Not a rewrite plan.
- Not patch execution.
- Not a final PostgreSQL cutover plan.
- Not a Go implementation plan.
- Not a claim that the system is ready.
- Not an exhaustive audit.

## Readiness Vocabulary

Migration Readiness Debt:
Current Laravel/MySQL design, implementation, schema, contract, or proof weakness that would make a future Go API/PostgreSQL transition expensive, regression-prone, unsafe, or likely to require a large rewrite.

Transition Work:
Natural work caused by changing stack. Examples include Go service creation, PostgreSQL adapter creation, migration runner creation, contract test harnesses, and shadow compare jobs.

Accepted Risk:
A known risk that remains open by explicit decision, with scope, owner, proof status, and rollback expectation recorded.

Proof Gap:
A claim that cannot yet be made because no test, runtime run, audit output, fixture parity, or source-level proof has been read.

Contract Gap:
An API, DTO, command, result, error, auth, idempotency, audit, or schema contract that is not frozen or not consistently enforced.

Runtime Gap:
A behavior that may be designed or documented, but has not been proven in the target runtime such as PostgreSQL, Go, production-like proxy/session settings, or browser flow.

Data Parity Gap:
No proof that the same source data produces the same rows, amounts, statuses, projections, reports, API response, or audit facts across old and new paths.

## Severity Model

P0:
Absolute blocker for smooth migration, data correctness, security, finance, stock, payment, refund, audit, rollback, or direct cutover.

P1:
High-risk blocker that makes migration expensive or regression-prone.

P2:
Migration friction that should be resolved before cutover or Go ownership.

P3:
Maintainability debt that slows audit, porting, or onboarding.

P4:
Nice-to-have.

## Current Readiness Estimates

These are discovery estimates only, not proof and not readiness claims.

- Blueprint maturity: around 90%
- Issue register initial readiness: around 85%
- Security/API route-surface blueprint: around 75%
- DB/PostgreSQL readiness coverage: around 35% until Batch 2 is completed and registered
- Architecture/system readiness coverage: around 45%
- Blade/JS readiness coverage: around 20%
- Test/proof readiness coverage: around 30%
- Full migration readiness blueprint: around 40%
- Actual smooth migration readiness: around 25%

Important note:
Security/API discovery coverage improved after Batch 1B because route-surface findings were registered. Overall smooth transition readiness remains conservative because PostgreSQL runtime proof, parity harnesses, API contract freeze, mutation proof maps, and browser proof are still GAP.

## Stage 0 Decisions

- DECISION: Use Stage 0 as the discovery baseline.
- DECISION: Keep discovery separate from remediation.
- DECISION: Batch 1B route-surface findings are registered as readiness debt or proof gaps, not as final vulnerability claims.
- NEXT: Continue with Batch 2 PostgreSQL/database readiness discovery.
