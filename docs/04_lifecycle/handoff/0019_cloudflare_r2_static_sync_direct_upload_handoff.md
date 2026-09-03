# Cloudflare R2 Supplier Payment Proof Direct Upload Handoff

## Metadata

- Date: 2026-09-03
- Scope: local supplier-payment-proof direct upload application + UI
- Status: local complete; stopped before legacy migration, production data, and deploy
- Blueprint: `docs/03_blueprints/infra/0012_cloudflare_r2_media_storage_migration.md`

## FACT

The local private-proof flow is now:

```text
Browser -> Laravel prepare
Browser -> private R2 staging PUT
Browser -> Laravel finalize
Laravel -> verify -> promote -> short DB mutation/audit/projection transaction
```

Browser PUT authorization is limited to:

```text
supplier-payment-proof-uploads/{intent-id}/{opaque-file-id}.upload
```

Final durable objects are created only by the server under:

```text
supplier-payment-proofs/{supplier-payment-id}/{sha256(intent-file-id)}.{verified-extension}
```

The two previous web flows now use the same direct-upload browser script:

- create payment from an outstanding supplier invoice;
- attach proof to an existing supplier payment.

The previous local multipart controllers/storage path remain in the repository as fallback code, but no supplier-proof web route or active form targets them.

## IMPLEMENTED

- declaration validation: 1..3 files, maximum 10 MiB each, strict MIME allowlist;
- actor/scope/idempotency-bound upload-intent persistence;
- non-locking prepare preflight and reserved payment ID for invoice scope;
- staging-only presigned PUT generation;
- actual object existence and byte-size verification;
- bounded server-side content read with `finfo` MIME detection;
- explicit HEIC/HEIF ISO base-media fallback and AVIF rejection;
- server-side copy from staging to opaque final key, followed by final size verification and staging deletion;
- atomic `prepared -> finalizing` claim and finalized replay payload;
- invoice state revalidation inside the final business transaction;
- attachment, audit, and projection mutation only after verification/promotion;
- partial-promotion and failed-business-mutation object cleanup;
- hourly expired/stale-finalizing cleanup with an atomic `locked_at` lease, retry, and stale-lease recovery;
- generic HTTP error responses for infrastructure exceptions;
- reusable browser flow: JSON prepare -> direct PUT(s) -> JSON finalize.

## SECURITY PROOF

Focused tests prove:

- missing/wrong actor cannot resolve or finalize another actor's intent;
- actor + scope + idempotency isolation and payload-conflict rejection;
- maximum 3 files and maximum 10 MiB/file;
- declared MIME allowlist at HTTP/application/adapter boundaries;
- client filename, MIME, and size are declarations only;
- missing, oversized, size-mismatched, or disallowed-content objects cause zero business mutation;
- actual WebP content declared as JPEG is stored as verified `image/webp` with an opaque `.webp` final name;
- browser prepare output contains only staging paths and staging PUT URLs;
- verification and promotion finish before the application opens the business transaction;
- a concurrent second finalize cannot acquire the atomic claim;
- finalized replay creates no duplicate payment, attachment, or audit event;
- invoice state is revalidated after the browser upload interval;
- partial promotion failure removes prior promoted objects and verified metadata;
- internal storage exception text is not returned by the HTTP endpoint;
- expired/stale cleanup does not touch active or finalized objects and retries failed deletion safely;
- UI code performs prepare -> PUT -> finalize and contains neither `FormData` nor a final-key prefix.

## LOCAL PROOF

Final focused supplier-proof backend/security/UI regression:

```text
53 passed (398 assertions)
```

Final full repository gate after implementation and formatting:

```text
PHPStan: PASS, 0 errors
line-count audit: PASS
Blade audit: PASS
contract audit: PASS
tests: 1543 passed (9716 assertions)
make verify: exit 0
```

Additional explicit architecture gate:

```text
make audit-hex
HEXAGONAL AUDIT: OK
```

The architecture gate required moving pre-existing framework-bound application queries behind adapter/port boundaries. Their focused Audit/Payment/Product/Service regression proof is `14 passed (134 assertions)`.

## GAP — REAL R2 / BROWSER

- Private R2 CORS and a real presigned browser-style PUT were proven in the earlier infrastructure phase.
- The newly completed current UI has not yet been driven by a real browser through prepare -> staging PUT -> finalize against private R2.
- Server-side CopyObject promotion through the configured real R2 credentials has not been re-proven from this final application flow.
- No production DB, production credentials, legacy media, or deployment was touched in this local phase.

## PRODUCTION NEXT

Execute only in a separately authorized production phase:

1. run real browser E2E with the staging-only UI and verify final object/read authorization;
2. inventory legacy supplier-proof rows and local files;
3. migrate legacy objects to `glasspos-private` with DB/object parity and rollback proof;
4. deploy application/static changes and verify production routes, CORS, audit, projection, read, and delete behavior;
5. prove cPanel/PHP no longer receives supplier-proof multipart bodies;
6. remove obsolete local copies only after parity, rollback, and production evidence are complete.

Do not broaden private credentials, expose a private bucket publicly, or issue browser-writable URLs for `supplier-payment-proofs/**`.
