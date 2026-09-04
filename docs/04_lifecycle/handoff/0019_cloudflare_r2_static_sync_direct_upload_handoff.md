# Cloudflare R2 Supplier Payment Proof Direct Upload Handoff

## Metadata

- Date: 2026-09-04
- Scope: local supplier-payment-proof direct upload application + UI + real R2/browser runtime proof
- Status: local, configured-real-R2, normal local-origin, approved-origin, and rejected-origin proof complete; stopped before legacy migration, production data, and deploy
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
- redacted server-side prepare failure reporting with stage and safe runtime/config context;
- two-phase cPanel pre-bootstrap cache clear and web OPcache reset using a unique, token-gated maintenance filename;
- web-runtime private-R2 disk resolution and staging-only presign readiness check during maintenance;
- reusable browser flow: JSON prepare -> direct PUT(s) -> JSON finalize.
- exact private R2 CORS origins for production plus normal `localhost:8000` and `127.0.0.1:8000` operation;
- centralized trusted Bahasa Indonesia UI failure mapping with no raw browser exception rendering;
- strict prepare response validation that safely normalizes PHP's empty JSON header list to an empty browser header map.

## ROOT CAUSES

### Web runtime versus fresh CLI

The actual HTTP failure was reproduced with a cached web configuration that omitted `filesystems.disks.r2_private`:

```text
prepare HTTP -> intent persisted -> Storage::disk('r2_private')
             -> InvalidArgumentException: disk has no configured driver
             -> safe generic HTTP 422/PRESIGN_FAILED
```

Fresh CLI passed because it read the current `config/filesystems.php`; the web process still read the old deployed `bootstrap/cache/config.php`. The old cPanel maintenance script booted Laravel before `optimize:clear`, so it could not repair the configuration used to bootstrap that same request. Package extraction excluded new cache files but did not delete a stale cache already present at the destination. A stable `clear.php` URL also left an avoidable OPcache reuse risk.

This was not a DB migration, R2 credential, staging path, route, hydration, generic UI validation, or basic presign defect.

The fix places disk resolution/presigning inside a redacted observable boundary and changes deployment maintenance to two HTTP phases: pre-bootstrap cache removal plus web OPcache reset, then a fresh framework bootstrap that must resolve/presign `r2_private` before migrate/optimize success. No public debug route was added.

### Manual local browser after successful prepare

The later manual operator failure had a different proven cause:

```text
browser origin:             localhost/127.0.0.1:8000
Laravel prepare:            HTTP 200
real private-bucket CORS:   production origin only
direct R2 PUT:              rejected by browser CORS
Laravel finalize:           never requested
Laravel exception log:      empty as expected
UI:                         leaked native Failed to fetch
```

The prior Chromium proof used the approved production HTTPS origin mapped to local Laravel, so it could not prove a normal port-8000 operator session. The fix updated both tracked CORS files and the real bucket to three exact origins, retained PUT-only/`Content-Type`-only access, and replaced raw `error.message` rendering with typed internal UI failures and a trusted message map.

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
- the previously lost prepare exception is logged with `prepare.presign.exception`, exception class, config-cache state, and configuration-presence booleans without presigned URL/signature/credential leakage;
- a stale web runtime missing `r2_private` reproduces safe HTTP failure while leaving payment, attachment, and audit tables untouched;
- expired/stale cleanup does not touch active or finalized objects and retries failed deletion safely;
- UI code performs prepare -> PUT -> finalize and contains neither `FormData` nor a final-key prefix.
- native fetch rejection cannot expose `Failed to fetch` or another browser exception message;
- malformed prepare, application network, R2 network/CORS, R2 non-2xx, finalize, and unknown failures have distinct trusted Bahasa Indonesia mappings;
- an R2 PUT failure exits before any finalize request;
- the private-bucket policy has no wildcard, GET method, or header beyond `Content-Type`.

## LOCAL PROOF

Final focused supplier-proof backend/security/UI/runtime regression for the CORS/error-boundary patch:

```text
68 passed (481 assertions)
```

Final full repository gate after implementation and formatting:

```text
PHPStan: PASS, 0 errors
line-count audit: PASS
Blade audit: PASS
contract audit: PASS
tests: 1550 passed (9797 assertions)
make verify: exit 0
```

Additional explicit architecture gate:

```text
make audit-hex
HEXAGONAL AUDIT: OK
```

The architecture gate required moving pre-existing framework-bound application queries behind adapter/port boundaries. Their focused Audit/Payment/Product/Service regression proof is `14 passed (134 assertions)`.

### Server-side configured-real-R2 smoke

The deterministic UI tests intentionally fake the object-store boundary and assert the JavaScript contract; they remain insufficient as sole proof of browser CORS/provider behavior. The explicit opt-in gate closes the configured-adapter/provider boundary without making normal `make verify` depend on external credentials:

```text
RUN_REAL_R2_SUPPLIER_PROOF_SMOKE=1 make smoke-r2-supplier-payment-proof
1 passed (24 assertions)
```

It uses the real `LaravelSupplierPaymentProofDirectUploadAdapter`, configured `r2_private` disk, HTTP prepare/finalize handlers, a real PUT, actual-object verification, server-side promotion, DB finalization, and deterministic object/DB cleanup. Without the opt-in flag it does not run; after opt-in, missing local credentials fail closed.

### Normal local operator browser proof

Real Chromium used normal `php artisan serve` origins with no TLS-origin impersonation. Both UI scopes passed on `http://localhost:8000`, and the already-running manual owner runtime independently passed on `http://127.0.0.1:8000`:

```text
localhost supplier_invoice:  prepare JSON 243 bytes -> R2 staging PUT -> finalize {} -> PASS
localhost supplier_payment:  prepare JSON 246 bytes -> R2 staging PUT -> finalize {} -> PASS
127.0.0.1 supplier_invoice:  prepare JSON 243 bytes -> R2 staging PUT -> finalize {} -> PASS
```

The first post-patch run adversarially exposed a valid response-shape edge: empty PHP headers encoded as `[]`. The UI localized it as malformed rather than leaking native text; the boundary now accepts only an empty list or a header object and normalizes the empty list to `{}`. The retry passed.

### Approved production-origin browser proof

The approved `https://arbiconbengkel.my.id` origin remains green against an isolated local Laravel runtime:

```text
prepare JSON 248 bytes -> R2 staging PUT -> finalize {} -> PASS
```

### Rejected-origin browser/CORS proof

Real Chromium from `http://127.0.0.1:8001`, which is intentionally absent from the policy, proved the negative path:

```text
prepare status:            200
CORS failure:              PreflightMissingAllowOriginHeader
finalize request count:    0
payment/attachment/audit:  0 / 0 / 0
localized UI:              Penyimpanan privat tidak dapat dihubungi. Periksa koneksi lalu coba lagi.
native error leaked:       false
```

Across successful runs, Laravel prepare/finalize bodies were JSON metadata only and did not contain the PDF fixture marker. Chromium sent the PDF only to the cross-origin R2 staging URL; the final prefix was never writable from the browser. Server logs showed only prepare/finalize application requests for successes and prepare only for the rejected origin. Real-R2 objects, browser profiles, OAuth token, certificates, and DB fixtures were removed after proof.

## GAP — PRODUCTION / LEGACY

- There is no remaining local configured-R2/browser/CORS gap for the supplier-payment-proof flow.
- The application change has not been deployed to production, so production web-runtime cache/OPcache maintenance and production browser/read/delete behavior remain unproven.
- Legacy production supplier-proof rows/files have not been inventoried or migrated.
- No production DB, production credential, legacy media, or deployment was changed in this phase.

## PRODUCTION NEXT

Execute only in a separately authorized production phase:

1. inventory legacy supplier-proof rows and local files;
2. migrate legacy objects to `glasspos-private` with DB/object parity and rollback proof;
3. deploy the application/static package and run its unique two-phase maintenance URL;
4. verify production routes, CORS, audit, projection, private read, and delete behavior;
5. prove production cPanel/PHP receives only prepare/finalize JSON metadata, never supplier-proof bytes;
6. remove obsolete local copies only after parity, rollback, and production evidence are complete.

Do not broaden private credentials, expose a private bucket publicly, or issue browser-writable URLs for `supplier-payment-proofs/**`.
