# Transition Work: Not Readiness Debt

This file separates natural stack-transition work from current Laravel/MySQL readiness debt.

## TW-001 - Creating Go Service, PostgreSQL Adapter, Migration Runner, And Shadow Compare Is Transition Work

ID: TW-001

Status: transition-work

Severity: P2

Area: Migration strategy

Type: transition-work

Evidence:

- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md`
- `database/migrations/README.md`

FACT:

- Existing docs treat PostgreSQL and Go as future work, not active implementation.
- The previous transition audit recommends staged proof, PostgreSQL harness, read-only Go API over projections, and later write ownership only after proof.

RISK:

- Misclassifying normal transition work as current-system debt can create unnecessary refactor pressure in Laravel.

GAP:

- No implementation plan exists in Stage 0.

Why it matters for smooth Go/PostgreSQL transition:

Separating debt from transition work prevents premature rewrites and keeps Stage 0 focused on evidence.

Recommended direction:

Keep Go service scaffolding, PostgreSQL adapter, migration runner, and shadow compare as future transition work unless evidence shows the current Laravel/MySQL design makes them unusually difficult.

Proof required:

- Stage 1 selection of first read-only target.
- Contract and parity harness requirements.

Suggested test/proof:

- Read-only projection API shadow compare before any write ownership.

Do not fix yet: yes

## Examples Of Transition Work

The following are not counted as Laravel/MySQL readiness debt by default:

- Creating a Go service.
- Creating a PostgreSQL adapter.
- Creating a migration runner.
- Creating a shadow compare job.
- Creating a read-only projection service.
- Creating a contract test harness.

## When Transition Work Becomes Readiness Debt

Transition work becomes readiness debt if evidence shows the current Laravel/MySQL design makes it unusually hard because:

- API contracts are unclear.
- Schema behavior is not portable.
- Audit facts are split or incomplete.
- Idempotency is absent for high-risk writes.
- Transaction boundaries and lock targets are unclear.
- Data parity cannot be measured.
- Browser/UI payload behavior is acting as hidden business logic.

DECISION:
Do not label normal stack-building work as current-system debt unless a source-backed finding shows the current design creates unusual friction or risk.
