# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: real browser-style presigned PUT to private R2 is proven; browser/PWA public-CDN smoke is next
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
- Strict direct-upload adapter suite passed after PSR header normalization: 8 tests / 35 assertions.
- Private R2 CORS preflight against a real presigned URL passed.
- Real browser-style PUT of bytes to `glasspos-private` passed, Laravel read-back matched exactly, and cleanup deletion passed.
- Existing production web upload controllers still receive PHP multipart bytes before forwarding to private R2.
- Direct-upload port/adapter foundation exists but is not yet wired to production controllers/UI.
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

```text
PHPStan: PASS
line-count audit: PASS
Blade audit: PASS
Contract audit: PASS
Tests: 1505 passed (9415 assertions)
Duration: 58.89s
```

Migration follow-ups fixed during verification:

- PHPStan concrete-filesystem typing for `temporaryUploadUrl()`;
- oversized `UploadPublicAssetsToR2` split into small support classes under `app/Console/Support/PublicAssets/`;
- `make verify` now uses Pest `--compact` while preserving the Pest runner;
- supplier-proof feature fixtures use fake `r2_private`, matching the real durable-storage contract.

## Private Direct Upload Foundation - PASS

Foundation:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

Adapter contract:

- targets `r2_private`;
- Laravel owns generated object keys;
- opaque stored filenames;
- TTL clamped to 60..3600 seconds, default 900;
- `Content-Type` participates in signing;
- invalid metadata/path input fails closed;
- signing exceptions fail closed;
- PSR array-valued headers are normalized for browser use;
- browser-managed `Host` and `Content-Length` are not exposed to JavaScript.

Post-normalization operator proof:

```text
Tests: 8 passed (35 assertions)
Duration: 0.18s
```

## Private CORS - PASS

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

Real preflight against a generated presigned URL:

```text
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
Vary: Origin
Access-Control-Allow-Headers: content-type
Access-Control-Allow-Methods: PUT
```

## Real Presigned PUT - PASS

Operator generated a fresh short-lived presigned URL through the Laravel direct-upload port, then sent bytes directly to R2 using an external HTTP client with the production browser origin.

PUT response:

```text
HTTP/1.1 200 OK
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
ETag: "879442678317eb3d7ba745bfb20d8ef1"
Vary: Origin
```

Laravel verification against `r2_private`:

```text
storage_path = supplier-payment-proofs/direct-proof/15f79d37002e001a57db8ebb099c40c5.txt
exists = true
content_match = true
bytes = 22
exists_after_delete = false
```

Meaning:

```text
Laravel generates authorization/presign       PASS
cross-origin browser-style PUT reaches R2     PASS
object exists in private bucket               PASS
read-back content matches exact bytes         PASS
cleanup delete removes proof object           PASS
```

This proves the infrastructure byte path required by the final architecture:

```text
Browser -> glasspos-private
```

without PHP/cPanel carrying the file body. It does **not** yet mean the production controllers/forms use this path.

## Current Migration State

```text
public static bytes        -> R2 public          DONE
Laravel static URLs        -> public CDN         DONE locally
public font CORS           -> proven             DONE
public/private privacy     -> proven             DONE
supplier proof durable I/O -> R2 private         DONE
private direct adapter     -> strict tests       PASS
private CORS preflight     -> real presign       PASS
real direct PUT bytes      -> R2 private         PASS
browser/PWA public smoke   -> pending
prepare/finalize use cases -> pending
controller/UI cutover      -> pending
legacy migration           -> pending
production cleanup         -> pending
```

Current production upload byte path remains:

```text
Browser -> PHP multipart -> Laravel -> R2 private
```

Target production path:

```text
Browser -> Laravel prepare
Browser -> R2 private direct PUT
Browser -> Laravel finalize
Laravel -> verify object -> DB/audit
```

## Locked Decisions

- Public/private data use separate buckets.
- Database stores object keys, not hard-coded R2 public URLs.
- Final private uploads send bytes browser -> R2 directly.
- Client MIME/size is not final verification.
- Finalize must verify the actual R2 object before DB/audit mutation.
- Upload intent must be actor/business-scope bound and replay/idempotency aware.
- Private CORS is origin/method/header constrained, never public wildcard.
- Browser-managed request headers such as `Host` and `Content-Length` are not exposed as JS upload headers.
- No DB transaction may remain open while the browser performs the R2 PUT.
- Do not delete local rollback copies before browser, parity, rollback, and production proof.

## Remaining Work in Order

1. Run browser UI/PWA/font/icon smoke for the public CDN cutover while local `public/assets` remains rollback.
2. Add prepare/finalize use cases with actor/business-scope-bound upload intent and real-object verification.
3. Cut both supplier-proof web flows from PHP multipart to direct browser -> R2.
4. Inventory/migrate legacy supplier-proof DB rows/local files and prove DB/object parity.
5. Continue audit for any other durable runtime-media families.
6. Verify Composer manifest/lock coherence for `league/flysystem-aws-s3-v3`.
7. Deploy to production and prove cPanel no longer owns durable media or private upload bytes.
8. Remove obsolete local media/static copies only after rollback/parity/production proof.

## Exact Next Operator Step

Run a browser smoke against the local application while `ASSET_URL=https://media.arbiconbengkel.my.id` remains configured and `public/assets` is still present as rollback.

Required checks:

```text
1. Open login/auth page: layout CSS/icons render.
2. Open admin dashboard: CSS/JS/vendor icons render; no obvious broken asset.
3. DevTools Network: representative `assets/**` requests resolve to `media.arbiconbengkel.my.id`.
4. Console: no CORS/font loading errors.
5. Open/install PWA path if available: manifest loads from application origin and icons render from CDN.
6. Confirm service worker registration still points to application-origin `/service-worker.js`.
```

Report only anomalies or `semua aman` if the smoke is clean.
