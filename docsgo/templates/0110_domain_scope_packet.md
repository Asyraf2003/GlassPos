# Domain Scope Packet Template

Use this template when sending one domain/API scope to another AI or another session.

## Scope

Active domain/API:

```text
REPLACE_WITH_DOMAIN_OR_API
```

Goal:

```text
REPLACE_WITH_GOAL
```

## Included Files

Read-only context:

```text
REPLACE_WITH_FILES
```

Editable files:

```text
REPLACE_WITH_FILES
```

Forbidden files:

```text
REPLACE_WITH_FILES
```

## Required Rules To Read

- `docsgo/README.md`
- `docsgo/AGENTS.md`
- `docsgo/0002_decision_policy.md`
- `docsgo/architecture/0020_hexagonal_go_api.md`
- `docsgo/architecture/0022_api_capability_control.md`
- `docsgo/domain/0030_domain_contracts.md`
- `docsgo/api/0050_echo_http_contract.md`
- `docsgo/testing/0060_test_and_quality_gates.md`
- `docsgo/workflow/0071_handoff_protocol.md`

## Domain Contract

Tables:

```text
REPLACE_WITH_TABLES
```

Allowed operations:

```text
create:
update:
delete:
show:
list:
```

Forbidden operations:

```text
REPLACE_WITH_FORBIDDEN_OPERATIONS
```

Capability keys:

```text
REPLACE_WITH_CAPABILITY_KEYS
```

## Expected Output

The receiving AI must return:

- files changed;
- summary of implementation;
- proof commands run or commands the owner must run;
- gaps;
- handoff for next step.

## Proof Required

Minimum proof:

```bash
make verify
```

Focused proof:

```bash
REPLACE_WITH_FOCUSED_COMMANDS
```

## Handoff

Next valid active step:

```text
REPLACE_WITH_NEXT_STEP
```

