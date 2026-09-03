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

This foundation is intentionally not wired into the working multipart controllers yet. The next proof is to confirm a presigned PUT against R2, including the required R2 CORS policy, before cutting production forms over to direct upload.

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

It preserves the relative asset tree, sets explicit content types, and currently applies `Cache-Control: public, max-age=86400`.

Do not switch application asset URLs to the CDN until the real upload finishes with zero failures and representative CSS/JS/font/image URLs are sampled successfully through the custom domain.

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
- Added repeatable `r2:upload-public-assets` command.
- Dry-run inventoried 6,224 files / 54.11 MB payload.
- Added a storage-provider-agnostic direct-upload port and R2 adapter foundation for short-lived private PUT URLs.

## Remaining work

1. Finish the real static upload and require `uploaded: 6224/6224`, `failed: 0` proof.
2. Sample representative CSS/JS/font/image objects through `media.arbiconbengkel.my.id`.
3. Configure and prove R2 CORS + short-lived presigned PUT for `glasspos-private`.
4. Add direct-upload prepare/finalize application use cases and an integrity-bound upload intent.
5. Cut both supplier proof web flows from PHP multipart to direct browser -> R2 upload.
6. Verify finalization against real R2 object size/type/key before DB mutation.
7. Introduce one global CDN asset-base mechanism for Laravel `asset('assets/...')` references.
8. Convert the small set of hard-coded `/assets/...` paths in service worker/manifest/push payloads deliberately.
9. Run UI/PWA regression checks before removing local static copies from deployment.
10. Inventory existing legacy supplier-payment-proof rows and local files and migrate them to `glasspos-private` with DB/object parity proof.
11. Continue audit for any additional runtime media families.
12. Deploy to production and prove media no longer depends on durable cPanel storage or PHP multipart limits.
13. Remove obsolete local media/static copies and storage semantics only after rollback/parity proof.
