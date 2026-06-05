# Scope And Facts

## Purpose

Keep every work step grounded in proof.

## FACT

FACT is only:

- inspected source code;
- inspected docs;
- user-provided command output;
- visible test output;
- explicit owner decision.

## GAP

GAP is anything important that is not proven yet.

Examples:

- unknown table owner;
- unknown API contract;
- unknown capability rule;
- unknown transaction boundary;
- unknown authorization behavior;
- unknown test coverage.

## SCOPE-IN

Only the package, API, domain, table, migration, or document explicitly selected for the active step.

## SCOPE-OUT

Related cleanup, refactors, route redesign, schema redesign, UI redesign, and unrelated tests stay out unless explicitly selected.

## Forbidden Behavior

- Do not infer file contents.
- Do not claim a package is clean without inspection.
- Do not claim an endpoint works without request/test proof.
- Do not call a design "done" when only a plan exists.

