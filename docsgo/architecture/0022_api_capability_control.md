# API Capability Control

## Purpose

The system must have one administrative control surface for API capability exposure.

This exists so features can be enabled, disabled, focused, audited, and discovered dynamically by UI and operators.

## Capability Concept

An API capability is a named operation such as:

- `products.create`
- `products.update`
- `products.delete`
- `products.show`
- `products.list`
- `transactions.close`
- `payments.refund`

## Required Fields

Every capability must declare:

- key;
- domain;
- operation;
- endpoint method/path;
- default state;
- required permission;
- risk level;
- audit requirement;
- idempotency requirement;
- owner package;
- test proof reference.

## Runtime Rule

Every protected endpoint must pass:

```text
authn -> authz -> capability check -> request validation -> use case
```

Capability checks must happen before business mutation.

## Admin Control Surface

The API must provide an admin-only surface to:

- list capabilities;
- show capability detail;
- enable capability;
- disable capability;
- explain why a capability is disabled;
- expose capability metadata for dynamic UI behavior.

## Forbidden Behavior

- Do not hide feature flags in handlers.
- Do not use environment variables as the only capability control.
- Do not let UI decide whether a forbidden API is actually allowed.
- Do not allow disabled mutation endpoints to reach use cases.
- Do not create endpoint routes without capability registry entries, unless the endpoint is explicitly public infrastructure such as health.

