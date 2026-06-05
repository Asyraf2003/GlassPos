# Go API AI_RULES Index

## Status

This document is the mandatory entry point for AI work in the future Go Echo API workspace.

`docsgo/` is intentionally separate from the current Laravel docs. It is a clean rule package for a pure Go API + PostgreSQL project.

## Purpose

The package exists so future work becomes strict, repeatable, and easy to audit.

It prevents:

- mixed architecture boundaries;
- undocumented API behavior;
- controller-driven business logic;
- database access outside persistence adapters;
- UI assumptions inside core domain;
- uncontrolled API capability exposure;
- handoff, blueprint, archive, and implementation docs overwriting each other;
- claims of completion without test or command proof.

## Mandatory Read Order

1. `0002_decision_policy.md`
2. `0003_session_start_protocol.md`
3. `core/0010_scope_and_facts.md`
4. `core/0011_blueprint_first.md`
5. `core/0012_step_by_step_execution.md`
6. `core/0013_proof_and_progress.md`
7. `architecture/0020_hexagonal_go_api.md`
8. `architecture/0021_package_boundaries.md`
9. `architecture/0022_api_capability_control.md`
10. `architecture/0023_public_contracts.md`
11. `domain/0030_domain_contracts.md`
12. `db/0040_postgresql_policy.md`
13. `api/0050_echo_http_contract.md`
14. `testing/0060_test_and_quality_gates.md`
15. `workflow/0070_docs_go_workflow.md`

## Constitution Summary

- Blueprint first.
- One active step.
- Proof before progress.
- Hexagonal architecture is mandatory.
- PostgreSQL is the database target.
- Echo is the HTTP transport.
- API contracts are public contracts.
- UI must be dynamic over API contracts and capability metadata.
- Capability control is a core system feature.
- Every domain operation must declare authorization, capability, transaction, audit, and test requirements.
- A file or package may only own one kind of responsibility.

## Canonical Package Map

- `core/`: AI behavior, scope, proof, and step discipline.
- `architecture/`: Go hexagonal architecture, package boundaries, public API contracts, and capability control.
- `domain/`: domain CRUD and mutation contract rules.
- `db/`: PostgreSQL schema, repository, migration, and transaction rules.
- `api/`: Echo HTTP contract and response envelope rules.
- `testing/`: unit, integration, contract, API, architecture, and script gates.
- `workflow/`: docs hygiene, blueprint/handoff/archive separation, and work sequencing.

## Non-Negotiable Behavior

- Do not implement from memory when a source file can be inspected.
- Do not make a domain decision in transport, SQL, or UI.
- Do not add an endpoint without a use case contract.
- Do not add a repository method without a port or use case need.
- Do not add a table without migration, constraints, repository tests, and rollback/forward policy.
- Do not expose an API without capability metadata.
- Do not claim tests passed without visible output.

