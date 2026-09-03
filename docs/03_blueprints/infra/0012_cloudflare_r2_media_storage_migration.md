# Cloudflare R2 Media and Static Asset Migration

Status: In progress
Date: 2026-09-03
Current phase: browser/PWA regression before production direct-upload wiring

## Goal

Move durable runtime media and the heavy public UI asset tree away from cPanel while keeping Laravel as the authorization and business control plane.

The final architecture must also remove private media bytes from the cPanel/PHP multipart request path. Laravel authorizes and finalizes private uploads, while the browser sends file bytes directly to Cloudflare R2.

## Locked target boundary

```text
Laravel / application server
├── PHP application code
├── controllers / policies / use cases
├── database metadata and object keys
├── signed upload orchestration
├── private read/download authorization
├── small origin-sensitive public files
└── no durable runtime media storage

Cloudflare R2: glasspos-media             PUBLIC
└── https://media.arbiconbengkel.my.id
    ├── assets/**                        static UI / Mazer / compiled assets
    └── future explicitly-public runtime media

Cloudflare R2: glasspos-private           PRIVATE
└── no custom domain / no public development URL
    └── supplier-payment-proofs/**
```

Public and private objects are split by bucket. A public custom domain must never expose private supplier evidence.

## R2 infrastructure - PROVEN

### Public bucket

- bucket: `glasspos-media`
- custom domain: `https://media.arbiconbengkel.my.id`
- placement: Cloudflare automatic / Asia Pacific
- storage class: Standard
- real Laravel write/exists proof passed
- real custom-domain HTTP delivery proof passed

### Private bucket

- bucket: `glasspos-private`
- no custom domain
- no public development URL
- real Laravel write -> exists -> read -> delete -> missing proof passed

### Laravel disks

```text
r2_public  -> glasspos-media
r2_private -> glasspos-private
```

The generic `s3` disk remains temporarily for compatibility/migration diagnostics. Application media adapters must use the explicit disk matching the security boundary.

Expected environment contract:

```dotenv
APP_URL=https://arbiconbengkel.my.id
ASSET_URL=https://media.arbiconbengkel.my.id

R2_ENDPOINT=https://<cloudflare-account-id>.r2.cloudflarestorage.com
R2_REGION=auto
R2_USE_PATH_STYLE_ENDPOINT=false
R2_HTTP_CONNECT_TIMEOUT=10
R2_HTTP_TIMEOUT=30

R2_PUBLIC_BUCKET=glasspos-media
R2_PUBLIC_URL=https://media.arbiconbengkel.my.id
R2_PUBLIC_ACCESS_KEY_ID=...
R2_PUBLIC_SECRET_ACCESS_KEY=...

R2_PRIVATE_BUCKET=glasspos-private
R2_PRIVATE_ACCESS_KEY_ID=...
R2_PRIVATE_SECRET_ACCESS_KEY=...
```

Credentials remain environment-only and must not be committed or pasted into chat.

## Public static asset migration - PROVEN LOCALLY

### Inventory and R2 parity

```text
files:   6224
bytes:   56737036
payload: 54.11 MB
```

The R2 copy preserves the exact `assets/**` relative tree so CSS-relative fonts/images continue to resolve naturally.

Resumable completion proof:

```text
existing objects: 6028
processed: 6224/6224
uploaded: 196
uploaded bytes: 5246381 (5.00 MB)
skipped existing: 6028
skipped bytes: 51490655 (49.11 MB)
failed: 0
elapsed: 1m 09s
```

Therefore static object path parity is proven at `6224/6224` for this migration run.

### Representative CDN delivery - PROVEN

HTTP 200 with expected MIME/content length was proven for representative objects under:

```text
assets/compiled/css/app.css
assets/compiled/js/app.js
assets/compiled/svg/favicon.svg
assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js
assets/extensions/bootstrap-icons/font/fonts/bootstrap-icons.woff2
```

Objects currently carry:

```text
cache-control: public, max-age=86400
```

`cf-cache-status: DYNAMIC` is a later optimization concern, not a delivery failure.

## Public font CORS - PROVEN

The public bucket uses a dashboard-applied read-only CORS policy equivalent to:

```json
[
  {
    "AllowedOrigins": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 86400
  }
]
```

Tracked policy files:

```text
deploy/cloudflare/glasspos-media-cors.json
deploy/cloudflare/glasspos-media-cors-dashboard.json
```

Final operator proof:

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

The wildcard policy is allowed only for intentionally-public `glasspos-media`. It must never be copied to `glasspos-private`.

## Application CDN cutover - LOCAL PASS

Static application references use Laravel `asset('assets/...')`, with:

```php
// config/app.php
'asset_url' => env('ASSET_URL', env('R2_PUBLIC_URL')),
```

Origin-sensitive root resources remain same-origin through `url()`:

```text
/manifest.webmanifest
/service-worker.js
```

PWA icon references and service-worker fallback notification icons use the public CDN. Error pages no longer suppress CDN CSS/icons based on `file_exists(public_path('assets/...'))` checks. Push application payloads may keep neutral `/assets/**` values; the outbound WebPush adapter resolves them to the configured CDN base before sending.

Operator generated-URL proof:

```text
config("app.asset_url")
= https://media.arbiconbengkel.my.id

asset("assets/compiled/css/app.css")
= https://media.arbiconbengkel.my.id/assets/compiled/css/app.css

url("/manifest.webmanifest")
= http://localhost/manifest.webmanifest

url("/service-worker.js")
= http://localhost/service-worker.js
```

Focused regression:

```text
PASS Tests\Feature\Infrastructure\PublicAssetCdnContractFeatureTest
6 passed / 25 assertions
```

Browser/PWA smoke remains required before local fallback assets can be considered removable.

## Public private-data boundary - PROVEN

Mandatory real-bucket audit:

```php
Storage::disk('r2_public')->allFiles('supplier-payment-proofs')
```

Operator result:

```text
0
```

No object was found under the known private supplier-proof prefix in `glasspos-media` at this checkpoint.

## Repository quality gate - PROVEN

Migration verification surfaced and closed several follow-ups:

- PHPStan concrete-filesystem typing for `temporaryUploadUrl()`;
- oversized static uploader split into small support classes;
- `make verify` uses Pest `--compact` while preserving the same runner;
- supplier-proof feature fixtures use fake `r2_private`, matching production durable storage.

Final operator proof:

```text
PHPStan: PASS
line-count audit: PASS
Blade audit: PASS
Contract audit: PASS
Tests: 1505 passed (9415 assertions)
Duration: 58.89s
```

## Root/public files kept local during first cut

Do not move or delete these as part of the first static cut:

```text
public/index.php
public/robots.txt
public/service-worker.js
public/manifest.webmanifest
public/favicon.ico
```

Local `public/assets` also remains available until browser regression, rollback, production proof, and final cleanup are complete.

## Private supplier-payment-proof storage

Classification: PRIVATE runtime media.

Object key contract:

```text
supplier-payment-proofs/{supplier-payment-id}/{generated-filename}
```

Current outbound file storage:

```text
App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter
```

The durable adapter targets `r2_private` for store/delete/exists/get. Private reads remain authorization-controlled through Laravel.

## Current private-upload bottleneck

The current production web flows still receive `UploadedFile` through PHP:

```text
Browser
-> cPanel/PHP multipart body
-> PHP temporary file
-> Laravel
-> glasspos-private
```

Affected flows include:

```text
UploadSupplierInvoicePaymentProofController
AttachSupplierPaymentProofController
```

R2 is already the durable destination, but cPanel upload limits still constrain incoming file bytes until the UI/controller cutover is complete.

## Direct-to-private-R2 target

Locked flow:

```text
1. Browser -> Laravel
   request upload authorization with filename/type/size/context

2. Laravel
   authenticate + authorize + business validation
   allocate safe opaque object key
   issue short-lived signed PUT URL and required headers

3. Browser -> glasspos-private directly
   media bytes bypass cPanel/PHP

4. Browser -> Laravel
   finalize object key / upload intent

5. Laravel
   verify object exists, key, size and server-trusted type
   then commit payment/attachment metadata and audit state
```

Dependency direction remains:

```text
Controller -> Use Case -> Storage Port -> R2 Adapter
```

Existing foundation:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

The adapter targets `r2_private`, generates opaque final keys, clamps signed URL TTL, propagates required Content-Type, normalizes real PSR header arrays for browser use, and fails closed.

Strict adapter proof after normalization:

```text
Tests: 8 passed (35 assertions)
Duration: 0.18s
```

## Private bucket CORS - PROVEN

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

Real presigned preflight proof:

```text
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
Vary: Origin
Access-Control-Allow-Headers: content-type
Access-Control-Allow-Methods: PUT
```

This proves the intended production origin can perform the required browser PUT preflight without widening the private bucket to wildcard access.

## Real direct PUT infrastructure proof - PROVEN

A fresh short-lived presigned URL was generated through the Laravel direct-upload port. An external HTTP client then sent the file bytes directly to `glasspos-private` with the production Origin header.

PUT response:

```text
HTTP/1.1 200 OK
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
ETag: "879442678317eb3d7ba745bfb20d8ef1"
Vary: Origin
```

Laravel verification through `r2_private`:

```text
storage_path = supplier-payment-proofs/direct-proof/15f79d37002e001a57db8ebb099c40c5.txt
exists = true
content_match = true
bytes = 22
exists_after_delete = false
```

Therefore the infrastructure path is proven end-to-end:

```text
Laravel creates presigned authorization
Browser-style HTTP PUT -> glasspos-private
Laravel read-back verifies exact bytes
cleanup removes proof object
```

The proof object was deleted. This does not yet imply the production forms/controllers use the direct path.

## Private direct-upload security requirements

The final direct-upload implementation must preserve all of the following:

- Laravel owns the object key; client cannot submit an arbitrary storage path
- short-lived signed upload authorization
- current max-file-count and file-size rules unless requirements change explicitly
- allowed media types remain constrained
- client MIME and size are not final proof
- finalize verifies the real R2 object before DB mutation
- upload intent is bound to actor and business scope
- replay/idempotency is handled deliberately
- orphan/staging cleanup exists
- no long DB transaction remains open while the browser uploads
- private bucket CORS is origin/method/header constrained and is not the public wildcard policy
- browser-managed Host/Content-Length headers are not emitted as JavaScript-managed upload headers

## Local Laravel storage

The framework-local disks and `public/storage` mapping are not removed yet. Legacy-data checks remain mandatory. Framework logs, cache, sessions, and temporary files are outside this migration.

## Migration rules

1. Never commit R2 credentials.
2. Public and private data use separate buckets.
3. Prefer least-privilege bucket-scoped credentials.
4. Store object keys in the database, not hard-coded R2 URLs.
5. Keep controllers/domain/application storage-provider agnostic.
6. Cloudflare/S3 details stay in configuration and outbound adapters.
7. Preserve the `assets/**` relative tree during static migration.
8. Keep `service-worker.js` and `manifest.webmanifest` same-origin.
9. Do not delete local static/media copies before browser regression, parity, rollback, and production proof.
10. Private object access remains authorization-controlled.
11. Final private uploads send bytes browser -> R2, not browser -> PHP -> R2.
12. Finalize must verify the real object; client metadata is insufficient.
13. The public wildcard CORS policy must never be copied to `glasspos-private`.
14. Do not broaden application object credentials merely for bucket management operations.
15. No application DB transaction may stay open across the browser-to-R2 upload interval.

## Completed

- Cloudflare authoritative DNS established for the domain.
- Created and proved `glasspos-media` public bucket/custom domain.
- Created and proved `glasspos-private` private bucket.
- Added explicit `r2_public` and `r2_private` Laravel disks.
- Routed supplier payment proof durable storage to `r2_private`.
- Added and hardened repeatable/resumable static uploader.
- Inventoried 6,224 static files / 54.11 MB payload.
- Completed public R2 static parity at 6,224/6,224 with zero resume failures.
- Proved representative CSS/JS/SVG/vendor/font delivery through the CDN.
- Applied and proved public read-only font CORS.
- Added and locally proved application-wide CDN asset-root behavior while protecting manifest/service-worker origin.
- Converted PWA/error/push static dependencies away from local-only asset assumptions.
- Proved focused CDN contract test at 6 tests / 25 assertions.
- Proved public bucket contains zero objects under `supplier-payment-proofs/**`.
- Closed repository quality gate at 1,505 tests / 9,415 assertions plus PHPStan and contract audits.
- Added private direct-upload port/adapter foundation and proved 8 strict tests / 35 assertions.
- Applied strict private bucket CORS and proved a real presigned OPTIONS preflight.
- Proved a real browser-style presigned PUT to `glasspos-private`, exact Laravel read-back, and deletion cleanup.

## Remaining work in order

1. Run browser UI/PWA/font/icon regression while local `public/assets` remains rollback.
2. Add prepare/finalize application use cases plus actor/business-scope-bound upload intent and real-object verification.
3. Cut both supplier-proof web flows from PHP multipart to direct browser -> R2.
4. Inventory legacy supplier-proof DB rows/local files, migrate them to `glasspos-private`, and prove DB/object parity.
5. Continue the audit for any other durable runtime-media families.
6. Verify `league/flysystem-aws-s3-v3` is committed coherently in Composer manifest/lock before fresh production install.
7. Deploy the completed static/runtime changes to production and prove cPanel no longer owns durable media or private upload bytes.
8. Remove obsolete local media/static copies only after rollback/parity/production proof.
