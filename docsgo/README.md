# docsgo

## What This Is

`docsgo/` is the working rules package for the future Go Echo API + PostgreSQL project.

This folder is the first place to read before planning, coding, reviewing, or asking an AI assistant to work in the Go API repository.

It explains:

- how AI must work;
- how humans should navigate the docs;
- how the Go API architecture is constrained;
- how domain CRUD must be designed;
- how API capabilities can be enabled or disabled;
- how PostgreSQL, Echo, tests, and scripts must be handled.

## Why This Exists

The Go API project must be cleaner than the current Laravel transition docs.

The goal is not only clean code. The goal is clean work:

- no overlapping blueprint and handoff;
- no archive document overriding active rules;
- no hidden architecture decision in chat;
- no endpoint without capability control;
- no domain mutation without transaction, audit, authorization, and tests;
- no claim of completion without proof.

## Project Direction

The target project is:

- Go;
- Echo HTTP framework;
- pure API, no server-rendered UI ownership;
- PostgreSQL;
- hexagonal architecture;
- dynamic UI consumption through API contracts and capability metadata;
- strict test and script gates.

## First Read Order

Read in this order:

1. `AGENTS.md`
2. `0001_index.md`
3. `0002_decision_policy.md`
4. `0003_session_start_protocol.md`
5. `core/0010_scope_and_facts.md`
6. `core/0011_blueprint_first.md`
7. `architecture/0020_hexagonal_go_api.md`
8. `architecture/0022_api_capability_control.md`
9. `domain/0030_domain_contracts.md`
10. `db/0040_postgresql_policy.md`
11. `api/0050_echo_http_contract.md`
12. `testing/0060_test_and_quality_gates.md`
13. `workflow/0070_docs_go_workflow.md`

`README.md` is the human entry point.

`AGENTS.md` is the AI bootstrap file.

`0001_index.md` is the canonical rule index.

## Folder Map

```text
docsgo/
  README.md
  AGENTS.md
  0001_index.md
  0002_decision_policy.md
  0003_session_start_protocol.md
  core/
  architecture/
  domain/
  db/
  api/
  testing/
  workflow/
```

## Core Rule Summary

Before work:

- define active scope;
- inspect facts;
- mark gaps;
- write or read the blueprint;
- choose one active step;
- state proof needed.

During work:

- keep package boundaries strict;
- keep domain logic outside Echo handlers;
- keep SQL inside PostgreSQL adapters;
- keep public API contracts stable;
- keep capability control in the official registry/policy path;
- keep tests close to the risk.

After work:

- show proof;
- report progress only from proof;
- write handoff only when needed;
- do not move to the next step without feedback.

## Architecture Rule In One Line

```text
Echo -> application use case -> domain rules -> ports -> PostgreSQL adapter
```

Do not reverse this direction.

## API Capability Rule In One Line

Every protected API operation must have a capability key and must be controllable from the capability control surface.

Example:

```text
products.create
products.update
products.delete
products.show
products.list
```

Disabled capability means the request must stop before the use case runs.

## Domain Rule In One Line

Every database-backed domain must declare create, update, delete, show, and list behavior, or explicitly document why an operation is forbidden.

## Documentation Discipline

Documents must not overlap roles.

- Standards live in `docsgo/`.
- Blueprints describe planned work.
- ADRs record accepted decisions.
- Handoffs continue sessions.
- Evidence records proof.
- Archive is historical and cannot override active rules.

## For AI Assistants

Do not start with implementation.

Start with:

- FACT;
- GAP;
- DECISION;
- BLUEPRINT;
- ACTIVE STEP;
- PROOF;
- NEXT;
- PROGRESS.

If a source file, command output, or contract is missing, mark it as GAP instead of guessing.

## For Humans

Use this folder as the rulebook before asking for Go API work.

When asking for a change, name the active domain or API clearly, for example:

```text
Work on products CRUD capability blueprint.
```

or:

```text
Implement products.show from the accepted blueprint.
```

That named scope becomes the active scope until changed.

