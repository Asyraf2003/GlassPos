# Blueprint First

## Purpose

Implementation must start from a blueprint so decisions are auditable.

## Minimum Blueprint

Every blueprint must state:

- target;
- current state;
- facts already known;
- gaps still open;
- scope in;
- scope out;
- affected public API contracts;
- affected domain invariants;
- affected DB tables;
- affected capability-control rules;
- dependencies;
- risks;
- test/proof plan;
- step order.

## Implementation Gate

Code may start only when:

- active scope is clear;
- public contract impact is known;
- domain owner is known;
- package boundary is known;
- DB transaction boundary is known for mutations;
- capability rule is known for any endpoint;
- test path is known.

## Forbidden Behavior

- Do not implement a mutation from a vague request.
- Do not create packages before the package role is defined.
- Do not add CRUD endpoints before domain lifecycle rules are defined.
- Do not replace a blueprint with comments in code.

