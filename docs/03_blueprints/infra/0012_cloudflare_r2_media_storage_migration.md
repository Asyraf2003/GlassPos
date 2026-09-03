# Cloudflare R2 Media and Static Asset Migration

Status: In progress
Date: 2026-09-03

## Goal

Move durable runtime media out of Laravel local/public storage and move the heavy static UI asset tree out of the application server where practical.

Laravel remains the control plane for authorization, metadata, upload orchestration, object naming, delete/read decisions, and private delivery. Cloudflare R2 becomes the durable object store and public static delivery layer.

A second goal is to remove large media upload bytes from the cPanel/PHP request path. The final private-media flow is direct-to-R2: Laravel authorizes and prepares the upload, the browser uploads directly to R2, then Laravel verifies and finalizes metadata/business state.

## Locked target boundary

```text
Laravel / application server
├── PHP application code
├── controllers / policies / use cases
├── metadata and storage object keys
├── signed upload orchestration
├── private read/download authorization
├── small boot-critical public files when needed
└── no durable runtime media storage

Cloudflare R2: glasspos-media             PUBLIC
└── custom domain: media.arbiconbengkel.my.id
    ├── assets/**                        static UI / Mazer / compiled assets
    └── future public runtime media

Cloudflare R2: glasspos-private           PRIVATE
└── no custom domain / no public development URL
    └── supplier-payment-proofs/**
```

Public and private objects are split by bucket, not merely by path prefix. A custom domain exposes the bound public bucket, so private evidence must never share that bucket.

## R2 setup proof

### Public bucket

- Bucket: `glasspos-media`
- Region placement: Cloudflare automatic, Asia Pacific
- Storage class: Standard
- Custom domain: `https://media.arbiconbengkel.my.id`
- Custom-domain status: Active
- Purpose: public static assets and future explicitly-public runtime media
- Real proof: Laravel wrote `healthcheck/public-r2-test.txt`, R2 reported it exists, and `curl` through the custom domain returned HTTP 200 with body `GlassPos public R2 OK`.
- The healthcheck object was deleted after proof.

### Private bucket

- Bucket: `glasspos-private`
- Region placement: Cloudflare automatic, Asia Pacific
- Storage class: Standard
- No custom domain
- No public development URL
- Purpose: authorization-controlled runtime media
- Real proof: `r2_private` completed write -> exists -> read -> delete -> missing against the actual bucket.

### Laravel integration

- S3-compatible adapter: `league/flysystem-aws-s3-v3`
- Credentials are environment-only and must never be committed.
- Public and private disks use separate credential variables so each token can be scoped to only the bucket it needs.

Expected environment contract:

```dotenv
R2_ENDPOINT=https://<cloudflare-account-id>.r2.cloudflarestorage.com
R2_REGION=auto
R2_USE_PATH_STYLE_ENDPOINT=false

R2_PUBLIC_BUCKET=glasspos-media
R2_PUBLIC_URL=https://media.arbiconbengkel.my.id
R2_PUBLIC_ACCESS_KEY_ID=...
R2_PUBLIC_SECRET_ACCESS_KEY=...

R2_PRIVATE_BUCKET=glasspos-private
R2_PRIVATE_ACCESS_KEY_ID=...
R2_PRIVATE_SECRET_ACCESS_KEY=...
```

## Laravel filesystem disks

`config/filesystems.php` defines explicit Cloudflare disks:

```text
r2_public  -> glasspos-media   -> public custom-domain bucket
r2_private -> glasspos-private -> private bucket
```

The generic Laravel `s3` disk remains temporarily for compatibility and migration diagnostics, but application media adapters must use the explicit R2 disk matching their security boundary.

## Current runtime media inventory

### Supplier payment proof attachments

Classification: PRIVATE runtime media.

Database metadata includes:

- `supplier_payment_proof_attachments.storage_path`
- original filename
- MIME type
- file size
- uploaded timestamp / actor metadata

Object key contract:

```text
supplier-payment-proofs/{supplier-payment-id}/{generated-filename}
```

The storage path guard validates an object key, not an absolute/local filesystem path, so the existing contract is R2-compatible.

Storage port:

```text
App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort
```

Adapter:

```text
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter
```

The durable storage adapter targets `r2_private` for:

- `putFileAs`
- delete
- exists
- get

Private delivery remains application-controlled. Existing attachment preview/serve flows resolve metadata, read through the storage port, and return the file through Laravel rather than exposing an R2 public URL.

## Current multipart bottleneck

The current web controllers still receive `UploadedFile` objects and obtain PHP temporary paths before the application sends them to R2:

```text
Browser -> cPanel/PHP multipart upload -> temp file -> Laravel -> r2_private
```

This still depends on hosting limits such as `upload_max_filesize`, `post_max_size`, request/body limits, timeout, and upload temp storage. R2 as the destination alone does not remove those limits.

Affected web flows include:

```text
UploadSupplierInvoicePaymentProofController
AttachSupplierPaymentProofController
```

Therefore the final runtime architecture must not make PHP carry the media bytes.

## Direct-to-R2 target

Locked flow:

```text
1. Browser -> Laravel
   request upload authorization with filename/type/size metadata

2. Laravel
   authenticate + authorize + validate business scope
   allocate final object key
   return short-lived signed PUT URL + required headers

3. Browser -> glasspos-private directly
   file bytes do not transit cPanel/PHP

4. Browser -> Laravel
   finalize uploaded object key

5. Laravel
   verify key/object/size/type
   commit payment + attachment metadata + audit transaction
```

Controllers must remain storage-provider agnostic. The expected dependency direction is:

```text
Controller -> Use Case -> Storage Port -> R2 Adapter
```

### Direct-upload foundation already added

A dedicated outbound capability now exists:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

The adapter uses `r2_private` and Laravel's temporary upload URL capability to prepare short-lived PUT URLs while preserving the existing final object-key prefix. It is bound through `ProcurementPaymentServiceProvider`.

A strict feature-test suite also exists for this foundation. It covers the service-provider binding, private-disk selection, opaque generated object names, MIME header propagation, 60-second minimum TTL, one-hour maximum TTL, path-traversal rejection, incomplete metadata rejection, and fail-closed behavior when presigning throws.

The test code is committed, but local PASS output from the operator is still required before this lane is counted as verified.

This foundation is intentionally not wired into the working multipart controllers yet. The next private-upload proof is to confirm a real presigned PUT against R2, including the required R2 CORS policy, before cutting production forms over to direct upload.

## Static asset inventory

Earlier `du` measurement reported about 74 MB of allocated filesystem space under `public/assets`. The uploader dry-run counts actual file payload instead:

```text
files: 6224
bytes: 56737036
payload: 54.11 MB
```

The difference is expected because `du` counts filesystem blocks while the uploader sums actual file sizes; thousands of small extension/vendor files amplify block overhead.

The dominant local area is the Mazer/vendor extension tree. The public scan also showed widespread application references to:

```text
assets/extensions/**
assets/compiled/**
assets/static/**
```

Most Blade references use Laravel `asset('assets/...')`. A smaller set is hard-coded as `/assets/...` in the service worker, web manifest, and push-notification payload factories.

## Static CDN decision

Static application assets are not runtime media, but they are intentionally included in the broader Cloudflare offload because keeping the large tree on every Laravel deployment is wasteful.

Locked direction:

```text
local public/assets/**
        -> sync preserving relative tree
R2 glasspos-media/assets/**
        -> https://media.arbiconbengkel.my.id/assets/**
```

Preserving `assets/**` rather than adding an extra `public/` prefix keeps CSS-relative URLs and existing application paths simpler.

The repeatable uploader is:

```text
php artisan r2:upload-public-assets --dry-run
php artisan r2:upload-public-assets
```

The uploader now supports interruption-safe resume semantics. It lists existing `assets/**` objects, skips already-present keys, retries failed uploads, prints frequent progress, and has bounded S3 connect/request timeouts. `--force` remains available for deliberate overwrite.

It preserves the relative asset tree, sets explicit content types, and currently applies `Cache-Control: public, max-age=86400`.

## Static sync proof - PROVEN

Operator proof on 2026-09-03:

```text
local files:       6224
existing on R2:    6028
resume uploaded:    196
resume skipped:    6028
failed:                0
payload total:     54.11 MB
resume elapsed:    1m 09s
```

The first long upload was interrupted manually after the process remained alive but slow. The hardened uploader then resumed instead of restarting the batch. This proves the already-uploaded objects survived interruption and the resume path completed the missing 196 objects with zero failures.

Representative public custom-domain probes also passed:

```text
assets/compiled/css/app.css
HTTP/2 200
content-type: text/css; charset=utf-8
content-length: 337533
cache-control: public, max-age=86400

assets/compiled/js/app.js
HTTP/2 200
content-type: text/javascript; charset=utf-8
content-length: 173876
cache-control: public, max-age=86400

assets/compiled/svg/favicon.svg
HTTP/2 200
content-type: image/svg+xml
content-length: 387
cache-control: public, max-age=86400

assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js
HTTP/2 200
content-type: text/javascript; charset=utf-8
content-length: 19549
cache-control: public, max-age=86400
```

All four probes were served through `media.arbiconbengkel.my.id`. `cf-cache-status: DYNAMIC` was observed; that does not invalidate object delivery and cache optimization is a later concern after the application cutover is stable.

Static tree upload and representative CSS/JS/SVG/vendor delivery are therefore considered proven.

## Application CDN cutover safety gate

Do not delete `public/assets` yet.

Before changing the application-wide asset base, prove cross-origin dependencies that browsers enforce more strictly than plain `curl` HTTP success. In particular, Bootstrap/icon/font CSS contains relative font URLs such as:

```text
assets/extensions/bootstrap-icons/font/bootstrap-icons.css
  -> ./fonts/bootstrap-icons.woff2
  -> ./fonts/bootstrap-icons.woff
```

Because the document origin and media origin differ, font delivery/CORS must be proven before the application is globally pointed at the CDN.

The preferred cutover can use one global asset-base mechanism only after same-origin root assets are protected. `public/service-worker.js` and `public/manifest.webmanifest` must remain application-origin resources during the first cut, so any global Laravel asset-base solution must not accidentally move those two URLs to the CDN.

## Files that remain local initially

The first static-CDN cut does not blindly move the entire Laravel `public/` directory. Keep these local until separately proven safe:

```text
public/index.php
public/robots.txt
public/service-worker.js
public/manifest.webmanifest
public/favicon.ico
```

The service worker and manifest may reference CDN-hosted icons/assets, but their own origin-sensitive behavior should remain under the application origin during the first cut.

## Local Laravel storage decision

`config/filesystems.php` still defines:

- `local` -> `storage/app/private`
- `public` -> `storage/app/public`
- `public/storage` symlink mapping

Repository scans have not proven an active runtime media flow using `Storage::disk('public')`, `asset('storage/...')`, or `storage/app/public`.

Do not remove these definitions until the runtime-media audit and legacy-data checks are complete. Framework-local storage for logs, cache, sessions, temporary files, and similar runtime internals is outside this migration.

## Migration rules

1. Never store R2 credentials in repository files.
2. Public and private data use different R2 buckets.
3. Prefer bucket-scoped credentials, with separate public/private key variables.
4. Store object keys in the database, not absolute R2 URLs.
5. Keep controllers/domain/application storage-provider agnostic.
6. Cloudflare/S3 details belong in adapters/configuration.
7. Preserve the existing `assets/**` relative tree during static sync.
8. Do not point application asset URLs at R2 before upload + HTTP proof succeeds.
9. Do not delete local legacy media until object/path parity and rollback are proven.
10. Private object access remains authorization-controlled.
11. Final private uploads must send file bytes directly to R2 rather than through cPanel/PHP.
12. A direct-upload cutover must preserve server-side verification; client-provided MIME/type/size metadata is never sufficient proof by itself.
13. Keep `service-worker.js` and `manifest.webmanifest` same-origin during the first static-CDN cut.
14. Prove font/CORS behavior before a global application asset-base cutover.

## Completed

- Created `glasspos-media` public bucket.
- Connected and activated `media.arbiconbengkel.my.id`.
- Created `glasspos-private` private bucket.
- Added explicit `r2_public` and `r2_private` Laravel disks.
- Proved real private R2 write/read/delete.
- Proved real public R2 write + HTTP 200 custom-domain delivery.
- Routed supplier payment proof durable storage adapter to `r2_private`.
- Updated targeted storage feature test to fake `r2_private`.
- Measured and mapped the heavy `public/assets` tree.
- Added repeatable and resumable `r2:upload-public-assets` command.
- Dry-run inventoried 6,224 files / 54.11 MB payload.
- Completed R2 static-tree parity at 6,224/6,224 objects with zero resume failures.
- Proved representative CSS/JS/SVG/vendor assets through the custom domain with correct content type and size.
- Added a storage-provider-agnostic direct-upload port and R2 adapter foundation for short-lived private PUT URLs.
- Added strict tests for the direct-upload adapter foundation; operator PASS proof remains pending.

## Remaining work

1. Prove cross-origin font/CORS behavior from `arbiconbengkel.my.id` to `media.arbiconbengkel.my.id` before global static cutover.
2. Protect same-origin root resources (`service-worker.js`, `manifest.webmanifest`) and introduce one application-wide CDN asset-base mechanism for `assets/**`.
3. Convert the small set of hard-coded `/assets/...` references in service worker/manifest/push payloads deliberately.
4. Run UI/PWA/font/icon regression checks while local `public/assets` remains available as rollback.
5. Run the committed strict direct-upload test suite locally and record PASS proof.
6. Configure and prove private-bucket CORS + a real short-lived presigned PUT against `glasspos-private`.
7. Add direct-upload prepare/finalize application use cases and an integrity-bound upload intent.
8. Cut both supplier proof web flows from PHP multipart to direct browser -> R2 upload.
9. Verify finalization against real R2 object size/type/key before DB mutation.
10. Inventory existing legacy supplier-payment-proof rows and local files and migrate them to `glasspos-private` with DB/object parity proof.
11. Continue audit for any additional runtime media families.
12. Deploy the completed static/runtime changes to production and prove media no longer depends on durable cPanel storage or PHP multipart limits.
13. Remove obsolete local media/static copies and storage semantics only after rollback/parity proof.
