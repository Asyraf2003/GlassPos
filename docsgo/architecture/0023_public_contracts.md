# Public Contracts

## Purpose

API contracts are public contracts. They must be stable, tested, and versioned.

## Contract Scope

A public contract includes:

- method and path;
- request body;
- query parameters;
- path parameters;
- response envelope;
- data DTO;
- error codes;
- validation errors;
- auth requirement;
- permission requirement;
- capability key;
- idempotency behavior;
- pagination/filter/sort behavior;
- timestamp format;
- money format.

## Versioning Rule

Breaking changes require one of:

- new API version;
- explicit migration period;
- owner decision and contract test update.

## Forbidden Behavior

- Do not return raw database rows.
- Do not expose internal enum names unless they are public terms.
- Do not change JSON field names casually.
- Do not return mixed envelope formats.
- Do not make UI parse human messages as machine state.

