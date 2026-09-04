# Cloudflare R2 Supplier Payment Proof Direct Upload Handoff

## Metadata

- Date: 2026-09-04
- Scope: local supplier-payment-proof direct upload application + UI + real R2/browser runtime proof
- Status: local and configured-real-R2 proof complete; stopped before legacy migration, production data, and deploy
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

## ROOT CAUSE

The actual HTTP failure was reproduced with a cached web configuration that omitted `filesystems.disks.r2_private`:

```text
prepare HTTP -> intent persisted -> Storage::disk('r2_private')
             -> InvalidArgumentException: disk has no configured driver
             -> safe generic HTTP 422/PRESIGN_FAILED
```

Fresh CLI passed because it read the current `config/filesystems.php`; the web process still read the old deployed `bootstrap/cache/config.php`. The old cPanel maintenance script booted Laravel before `optimize:clear`, so it could not repair the configuration used to bootstrap that same request. Package extraction excluded new cache files but did not delete a stale cache already present at the destination. A stable `clear.php` URL also left an avoidable OPcache reuse risk.

This was not a DB migration, R2 credential, staging path, route, hydration, generic UI validation, or basic presign defect.

The fix places disk resolution/presigning inside a redacted observable boundary and changes deployment maintenance to two HTTP phases: pre-bootstrap cache removal plus web OPcache reset, then a fresh framework bootstrap that must resolve/presign `r2_private` before migrate/optimize success. No public debug route was added.

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

## LOCAL PROOF

Final focused supplier-proof backend/security/UI/runtime regression:

```text
54 passed (486 assertions)
```

Final full repository gate after implementation and formatting:

```text
PHPStan: PASS, 0 errors
line-count audit: PASS
Blade audit: PASS
contract audit: PASS
tests: 1547 passed (9752 assertions)
make verify: exit 0
```

Additional explicit architecture gate:

```text
make audit-hex
HEXAGONAL AUDIT: OK
```

The architecture gate required moving pre-existing framework-bound application queries behind adapter/port boundaries. Their focused Audit/Payment/Product/Service regression proof is `14 passed (134 assertions)`.

The deterministic UI tests intentionally fake the object-store boundary and assert the JavaScript contract; they were insufficient as sole proof of browser CORS/provider behavior. The explicit opt-in gate closes the configured-adapter/provider boundary without making normal `make verify` depend on external credentials:

```text
RUN_REAL_R2_SUPPLIER_PROOF_SMOKE=1 make smoke-r2-supplier-payment-proof
1 passed (24 assertions)
```

It uses the real `LaravelSupplierPaymentProofDirectUploadAdapter`, configured `r2_private` disk, HTTP prepare/finalize handlers, a real PUT, actual-object verification, server-side promotion, DB finalization, and deterministic object/DB cleanup. Without the opt-in flag it does not run; after opt-in, missing local credentials fail closed.

Real Chromium proof used `https://arbiconbengkel.my.id` mapped by a local TLS proxy to the isolated Laravel runtime, retaining the already-configured production-origin private-R2 CORS policy. Both UI scopes passed:

```text
supplier_invoice: prepare 200 -> R2 staging PUT 200 -> finalize 200
supplier_payment: prepare 200 -> R2 staging PUT 200 -> finalize 200
```

For the two runs, Laravel prepare bodies were JSON metadata only (`245` and `249` bytes); finalize bodies were `{}` (`2` bytes); neither contained the 95-byte PDF fixture. Chromium sent the PDF only in the cross-origin PUT to `*.r2.cloudflarestorage.com` under `supplier-payment-proof-uploads/**`. The post-finalize pages rendered the attachment and removed the completed upload form. Server logs showed only prepare/finalize application requests; real-R2 browser artifacts were deleted after proof.

## GAP — PRODUCTION / LEGACY

- There is no remaining local configured-R2/browser gap for the supplier-payment-proof flow.
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
