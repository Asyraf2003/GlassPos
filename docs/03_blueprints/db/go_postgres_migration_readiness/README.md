# Go API + PostgreSQL Migration Readiness Problem Blueprint

Status: Stage 0 modularized discovery baseline

Parent baseline: [../0013_go_postgres_migration_readiness_stage_0.md](../0013_go_postgres_migration_readiness_stage_0.md)

This folder breaks the large Stage 0 discovery baseline into smaller problem categories that can be reviewed and worked one by one later.

This is documentation only. It is not a patch plan, not remediation execution, not a rewrite plan, and not a claim that the system is ready for Go API ownership or PostgreSQL cutover.

## Current Progress

- DECISION: Stage 0 baseline exists in the parent document.
- DECISION: Batch 1B route-surface findings are registered.
- NEXT: Batch 2 remains PostgreSQL/database readiness discovery.
- GAP: The modular blueprint is not exhaustive and must be expanded after each audit batch.

## Navigation

- [00 Stage 0 Baseline Summary](00_stage_0_baseline_summary.md)
- [01 Master Issue Register](01_master_issue_register.md)
- [02 Readiness Scoring](02_readiness_scoring.md)
- [Security Public Surface](findings/01_security_public_surface.md)
- [API Contract Readiness](findings/02_api_contract_readiness.md)
- [Database PostgreSQL Readiness](findings/03_database_postgresql_readiness.md)
- [Transaction, Idempotency, Audit](findings/04_transaction_idempotency_audit.md)
- [Domain Mutation Readiness](findings/05_domain_mutation_readiness.md)
- [Blade/JS UI Payload Readiness](findings/06_blade_js_ui_payload_readiness.md)
- [Test Proof Readiness](findings/07_test_proof_readiness.md)
- [Architecture Boundary Readiness](findings/08_architecture_boundary_readiness.md)
- [Transition Work: Not Readiness Debt](transition_work/01_not_readiness_debt.md)
- [Stage 0 Evidence Summary](evidence/01_codex_stage_0_evidence.md)
- [Batch 1B Route Surface Evidence](evidence/02_codex_batch_1b_route_surface_evidence.md)
- [Finding Template](templates/finding_template.md)

## Working Rule

Do not start remediation before a finding is registered, classified, and has proof requirement.

## Readiness Guardrail

The system must not be called ready unless P0 is zero open, P1 has proof or explicit accepted-risk status, PostgreSQL parity harness exists, API contracts are frozen, and the write mutation proof map is complete.
