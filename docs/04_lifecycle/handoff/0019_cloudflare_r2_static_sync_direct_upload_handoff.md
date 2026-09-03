# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: private CORS preflight and normalized presign-header regression are proven; real presigned PUT is next
- Status: active / in progress

## Goal

```text
public static
Browser -> media.arbiconbengkel.my.id -> glasspos-media

private supplier proof
Browser -> Laravel prepare/authorize
Browser -> glasspos-private direct PUT
Browser -> Laravel finalize
Laravel -> verify real object -> DB/audit mutation
```

Private media bytes must not transit cPanel/PHP in the final architecture.

## Locked Facts

- Cloudflare is authoritative for `arbiconbengkel.my.id`.
- Public bucket: `glasspos-media` with custom domain `https://media.arbiconbengkel.my.id`.
- Private bucket: `glasspos-private`; no public domain/development URL.
- Laravel disks: `r2_public` and `r2_private`.
- Real private R2 write/read/delete proof passed.
- Real public R2 write/custom-domain delivery proof passed.
- Public static inventory: 6,224 files / 56,737,036 bytes / 54.11 MB.
- Static R2 parity: 6,224/6,224 paths with zero final failures.
- Public font CORS is configured and proven.
- Laravel asset-root CDN cutover is locally proven.
- Public privacy gate passed: `glasspos-media/supplier-payment-proofs/**` count is `0`.
- Supplier-payment-proof durable storage adapter uses `r2_private`.
- Existing web upload controllers still receive PHP multipart bytes before forwarding to private R2.
- Direct-upload port/adapter foundation exists but is not wired to production controllers/UI yet.
- Local `public/assets` remains until browser/PWA regression, rollback, production proof, and cleanup.

## Public Lane Proof

```text
static files: 6224 / 54.11 MB
R2 parity: 6224/6224
public privacy prefix count: 0
focused CDN contract: 6 passed / 25 assertions
```

Public font CORS proof:

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

## Repository Quality Gate - PASS

Final operator proof:

```text
PHPStan: PASS
line-count audit: PASS
Blade audit: PASS
Contract audit: PASS
Tests: 1505 passed (9415 assertions)
Duration: 58.89s
```

Uploader command was split into small support classes to satisfy the repository 100-line application-file rule. Supplier-proof feature fixtures now fake `r2_private`, matching the real durable-storage contract.

## Private Direct Upload Foundation - STRICT TEST PASS

Foundation:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

Initial operator proof:

```text
Tests: 8 passed (35 assertions)
Duration: 0.20s
```

Adapter contract:

- targets `r2_private`;
- Laravel owns generated object keys;
- opaque stored filenames;
- TTL clamped to 60..3600 seconds, default 900;
- `Content-Type` participates in signing;
- invalid metadata/path input fails closed;
- signing exceptions fail closed.

## Private CORS - APPLIED AND PREFLIGHT PROVEN

Tracked policies:

```text
deploy/cloudflare/glasspos-private-cors.json
deploy/cloudflare/glasspos-private-cors-dashboard.json
```

Dashboard policy:

```json
[
  {
    "AllowedOrigins": ["https://arbiconbengkel.my.id"],
    "AllowedMethods": ["PUT"],
    "AllowedHeaders": ["Content-Type"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 900
  }
]
```

Operator applied the policy to `glasspos-private` and generated a real short-lived presigned upload URL through the Laravel direct-upload adapter.

Preflight proof against that real presigned URL:

```text
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
Vary: Origin
Access-Control-Allow-Headers: content-type
Access-Control-Allow-Methods: PUT
```

Meaning: the private browser CORS gate for the intended production origin, PUT method, and Content-Type request header is proven.

## Presigned Header Normalization - PASS

The first real presign run exposed a warning:

```text
WARNING Array to string conversion
app/Adapters/Out/Procurement/LaravelSupplierPaymentProofDirectUploadAdapter.php line 67
```

Root cause: Laravel's S3 adapter returns PSR request headers where each header value may be an array. The first implementation assumed scalar values and cast them directly to strings.

Patch:

```text
app/Adapters/Out/Procurement/SupplierPaymentProofUploadHeaderNormalizer.php
app/Adapters/Out/Procurement/LaravelSupplierPaymentProofDirectUploadAdapter.php
tests/Feature/Procurement/SupplierPaymentProofDirectUploadAdapterFeatureTest.php
```

Behavior:

- array-valued PSR headers are normalized to browser-friendly strings;
- browser-managed `Host` and `Content-Length` are not returned to JavaScript;
- strict adapter regression models real PSR header arrays.

Post-patch operator proof:

```text
Tests: 8 passed (35 assertions)
Duration: 0.18s
```

No array-to-string warning was shown. The header-normalization regression gate is therefore closed.

## Current Migration State

```text
public static bytes        -> R2 public          DONE
Laravel static URLs        -> public CDN         DONE locally
public font CORS           -> proven             DONE
public/private privacy gate-> proven             DONE
supplier proof durable I/O -> R2 private         DONE
private direct adapter     -> strict tests       PASS
private CORS preflight     -> real presign       PASS
presign header normalization                    PASS
real presigned PUT bytes   -> pending            NEXT
```

Current production upload byte path is still:

```text
Browser -> PHP multipart -> Laravel -> R2 private
```

Target remains:

```text
Browser -> Laravel prepare
Browser -> R2 private direct PUT
Browser -> Laravel finalize
Laravel -> verify object -> DB/audit
```

Legacy supplier-proof rows/files have not yet been inventoried/migrated. Production cutover and deletion of local fallback assets/media have not happened.

## Locked Decisions

- Public/private data use separate buckets.
- Database stores object keys, not hard-coded R2 public URLs.
- Final private uploads send bytes browser -> R2 directly.
- Client MIME/size is not final verification.
- Finalize must verify the actual R2 object before DB/audit mutation.
- Upload intent must be actor/business-scope bound and replay/idempotency aware.
- Private CORS is origin/method/header constrained, never public wildcard.
- Browser-managed request headers such as `Host` and `Content-Length` are not exposed as JS upload headers.
- Do not delete local rollback copies before browser, parity, rollback, and production proof.

## Remaining Work in Order

1. Prove a real short-lived PUT of bytes to `glasspos-private`, verify object exists/content, then delete the proof object.
2. Run browser UI/PWA/font/icon smoke for the public CDN cutover while local `public/assets` remains rollback.
3. Add prepare/finalize use cases with actor/business-scope-bound upload intent and real-object verification.
4. Cut both supplier-proof web flows from PHP multipart to direct browser -> R2.
5. Inventory/migrate legacy supplier-proof DB rows/local files and prove DB/object parity.
6. Continue audit for any other durable runtime-media families.
7. Verify Composer manifest/lock coherence for `league/flysystem-aws-s3-v3`.
8. Deploy to production and prove cPanel no longer owns durable media or private upload bytes.
9. Remove obsolete local media/static copies only after rollback/parity/production proof.

## Exact Next Operator Step

Generate a fresh presigned URL, upload dummy bytes with an external HTTP client, verify the object through `r2_private`, and delete it. Required proof:

```text
PUT response: success
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
r2_private exists: true
content exact match: true
delete succeeds
r2_private exists after delete: false
```
