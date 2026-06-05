# Echo HTTP Contract

## Purpose

Echo is only the HTTP adapter. It must not become the application core.

## Handler Responsibility

Handlers may:

- read path/query/body;
- call request validation;
- call one use case/query;
- map application result to response envelope;
- map errors to public error format.

Handlers may not:

- run SQL;
- make domain decisions;
- mutate multiple use cases without an application service;
- inspect capability state manually when middleware/policy owns it;
- return inconsistent JSON envelopes.

## Response Envelope

Use one consistent envelope family:

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

```json
{
  "success": false,
  "error": {
    "code": "validation_failed",
    "message": "Validation failed",
    "fields": {}
  },
  "meta": {}
}
```

Human messages are not machine state.

## HTTP Status Rule

- `200`: successful show/list/update when response body exists.
- `201`: successful create.
- `204`: successful delete with no body.
- `400`: malformed request.
- `401`: unauthenticated.
- `403`: unauthorized or capability disabled.
- `404`: resource not found.
- `409`: domain conflict or idempotency conflict.
- `422`: validation failed.
- `500`: unexpected server error with redacted detail.

## Route Rule

Every route must declare:

- method;
- path;
- handler;
- use case/query;
- permission;
- capability key;
- request DTO;
- response DTO;
- tests.

