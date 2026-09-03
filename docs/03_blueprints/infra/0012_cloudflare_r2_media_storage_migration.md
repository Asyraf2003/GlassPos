# Cloudflare R2 Media and Static Asset Migration

Status: In progress
Date: 2026-09-03
Current phase: application CDN cutover verification

## Goal

Move durable runtime media and the heavy public UI asset tree away from cPanel while keeping Laravel as the authorization and business control plane.

The final architecture must also remove private media bytes from the cPanel/PHP multipart request path. Laravel authorizes and finalizes private uploads, while the browser sends the file bytes directly to Cloudflare R2.

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

## Public static asset migration

### Inventory

`public/assets` contains:

```text
files:   6224
bytes:   56737036
payload: 54.11 MB
```

The larger `du` allocation previously observed is expected because thousands of small files consume filesystem blocks beyond their payload size.

The tree contains application assets under:

```text
assets/extensions/**
assets/compiled/**
assets/static/**
```

The R2 copy preserves the exact `assets/**` relative tree so CSS-relative fonts/images continue to resolve naturally.

### Resumable uploader - PROVEN

The migration command is:

```text
php artisan r2:upload-public-assets --dry-run
php artisan r2:upload-public-assets
```

The hardened uploader:

- lists existing `assets/**` objects once
- skips already-present object keys by default
- supports `--force` for deliberate overwrite
- retries failed uploads using a fresh stream
- uses bounded connect/request timeouts
- prints frequent progress
- exits non-zero when failures remain

Operator resume proof:

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

Therefore static object parity is considered proven at `6224/6224` for this migration run.

### Representative CDN delivery - PROVEN

The custom domain returned HTTP 200 with expected MIME and content length for representative objects:

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

`cf-cache-status: DYNAMIC` was observed during proof. That is not a delivery failure; cache optimization is deferred until cutover behavior is stable.

## Public font CORS - PROVEN

Cross-origin fonts initially returned HTTP 200 without `Access-Control-Allow-Origin`, so application CDN cutover was correctly blocked.

The public bucket now has a dashboard-applied read-only CORS policy equivalent to:

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

Final operator font proof:

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

The public static CORS gate is therefore closed.

This wildcard policy is allowed only for `glasspos-media`, which is intentionally public. It must not be copied to the private bucket.

## Application CDN cutover contract

Most application static references already use Laravel's `asset('assets/...')` helper. The selected cutover therefore uses Laravel's asset root rather than rewriting every Blade file.

Current contract:

```php
// config/app.php
'asset_url' => env('ASSET_URL', env('R2_PUBLIC_URL')),
```

This routes `asset('assets/...')` to the public CDN when `ASSET_URL` or `R2_PUBLIC_URL` is configured.

Origin-sensitive root resources must not follow the CDN asset root. The main layout therefore uses same-origin `url()` for:

```text
/manifest.webmanifest
/service-worker.js
```

while CSS, JS, icons, fonts, images, and Mazer/vendor files continue using `asset('assets/...')`.

A focused contract test exists at:

```text
tests/Feature/Infrastructure/PublicAssetCdnContractFeatureTest.php
```

The code is pushed, but local runtime proof from the operator is still required before this cutover is counted as verified.

## Root/public files kept local during first cut

Do not move or delete these as part of the first static cut:

```text
public/index.php
public/robots.txt
public/service-worker.js
public/manifest.webmanifest
public/favicon.ico
```

The manifest and service worker may later reference CDN-hosted icons, but their own URLs remain application-origin.

## Known hard-coded `/assets/**` references

A smaller set does not use Laravel's asset helper and therefore remains same-origin for now, including:

- `public/service-worker.js` default icon/badge
- `public/manifest.webmanifest` PWA icons
- push-notification payload factories

The local `public/assets` fallback remains present while these references are deliberately migrated and browser/PWA regression is performed.

The error layout also contains `file_exists(public_path('assets/...'))` guards. Those guards must be changed before local `public/assets` is physically removed, otherwise CDN-backed error styling could be incorrectly suppressed.

## Private supplier-payment-proof storage

Classification: PRIVATE runtime media.

Database metadata includes object path, original filename, MIME type, size, and audit/upload metadata.

Object key contract:

```text
supplier-payment-proofs/{supplier-payment-id}/{generated-filename}
```

Current outbound file storage:

```text
App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter
```

The durable adapter now targets `r2_private` for store/delete/exists/get. Private reads remain authorization-controlled through Laravel.

## Current private-upload bottleneck

The current web flows still receive `UploadedFile` through PHP:

```text
Browser
-> cPanel/PHP multipart body
-> PHP temporary file
-> Laravel
-> glasspos-private
```

Affected controllers include:

```text
UploadSupplierInvoicePaymentProofController
AttachSupplierPaymentProofController
```

Therefore R2 is already the durable destination, but cPanel upload limits still constrain incoming file bytes.

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

Existing direct-upload foundation:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

It targets `r2_private`, generates opaque final keys, clamps signed URL TTL, and fails closed. A strict feature-test suite is committed, but operator PASS output is still pending.

No production controller has been cut over to this foundation yet.

## Private direct-upload security requirements

The final direct-upload implementation must preserve all of the following:

- Laravel owns the object key; client cannot submit an arbitrary storage path
- short-lived signed upload authorization
- current max-file-count and file-size rules unless product requirements change explicitly
- allowed media types remain constrained
- client MIME and size are not final proof
- finalize verifies the real R2 object before DB mutation
- upload intent is bound to actor and business scope
- replay/idempotency is handled deliberately
- orphan/staging cleanup exists
- no long DB transaction remains open while the browser uploads
- private bucket CORS is origin/method constrained and is not the public wildcard policy

## Local Laravel storage

The framework-local disks and `public/storage` mapping are not removed yet. Current scans have not proven an active durable runtime-media flow using `Storage::disk('public')`, `asset('storage/...')`, or `storage/app/public`, but legacy-data checks remain mandatory.

Framework logs, cache, sessions and temporary files are outside this migration.

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
- Added the application-wide CDN asset-root contract while protecting manifest/service-worker origin.
- Added a focused application CDN contract test; local PASS proof remains pending.
- Added private direct-upload port/adapter foundation and strict tests; local PASS proof remains pending.

## Remaining work in order

1. Pull the application CDN cutover, clear config, run the focused contract test, and prove generated URLs: `assets/**` must resolve to `media.arbiconbengkel.my.id`, while manifest/service-worker remain application-origin.
2. Convert remaining hard-coded `/assets/**` references and error-layout local-file guards deliberately.
3. Run browser UI/PWA/font/icon regression while local `public/assets` remains as rollback.
4. Prove that `glasspos-media` contains zero `supplier-payment-proofs/**` private objects before final public/static cleanup.
5. Run the committed strict private direct-upload tests locally and record PASS.
6. Configure strict private-bucket CORS and prove a real short-lived presigned PUT against `glasspos-private`.
7. Add prepare/finalize application use cases plus integrity-bound upload intent and real-object verification.
8. Cut both supplier-proof web flows from PHP multipart to direct browser -> R2.
9. Inventory legacy supplier-proof DB rows/local files, migrate to `glasspos-private`, and prove DB/object parity.
10. Continue the audit for any other durable runtime-media families.
11. Verify `league/flysystem-aws-s3-v3` is committed coherently in Composer manifest/lock before fresh production install.
12. Deploy the completed static/runtime changes to production and prove cPanel no longer owns durable media or private upload bytes.
13. Remove obsolete local media/static copies only after rollback/parity/production proof.
