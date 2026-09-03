# Cloudflare R2 Media and Static Asset Migration

Status: In progress
Date: 2026-09-03

## Goal

Move durable runtime media out of Laravel local/public storage and move the heavy static UI asset tree out of the application server where practical.

Laravel remains the control plane for authorization, metadata, upload orchestration, object naming, delete/read decisions, and private delivery. Cloudflare R2 becomes the durable object store and public static delivery layer.

## Locked target boundary

```text
Laravel / application server
├── PHP application code
├── controllers / policies / use cases
├── metadata and storage object keys
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

Public and private objects are split by bucket, not merely by path prefix. A custom domain exposes the whole bound public bucket, so private evidence must never share that bucket.

## R2 setup proof

### Public bucket

- Bucket: `glasspos-media`
- Region placement: Cloudflare automatic, Asia Pacific
- Storage class: Standard
- Custom domain: `https://media.arbiconbengkel.my.id`
- Custom-domain status: Active
- Purpose: public static assets and future explicitly-public runtime media

### Private bucket

- Bucket: `glasspos-private`
- Region placement: Cloudflare automatic, Asia Pacific
- Storage class: Standard
- No custom domain
- No public development URL
- Purpose: authorization-controlled runtime media

### Laravel integration

- S3-compatible adapter: `league/flysystem-aws-s3-v3`
- R2 S3 connectivity was proven locally against the original public bucket with write + exists returning `true`.
- Credentials are environment-only and must never be committed.
- Public and private disks now use separate credential variables so each token can be scoped to only the bucket it needs.

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

The existing credential originally scoped only to `glasspos-media` is suitable only for the public disk. A separate Object Read & Write credential scoped to `glasspos-private` is required before the private runtime proof.

## Laravel filesystem disks

`config/filesystems.php` now defines explicit Cloudflare disks:

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

The adapter now targets `r2_private` for:

- `putFileAs`
- delete
- exists
- get

The targeted feature test uses `Storage::fake('r2_private')`, preserving the storage boundary without contacting Cloudflare during the test suite.

Private delivery remains application-controlled. Existing attachment preview/serve flows resolve metadata, read through the storage port, and return the file through Laravel rather than exposing an R2 public URL.

## Static asset inventory

Local measurement on 2026-09-03:

```text
public/assets      74M
├── extensions    68M
├── compiled     3.3M
└── static       2.5M
```

The dominant cost is the Mazer/vendor extension tree. The public scan also showed widespread application references to:

```text
assets/extensions/**
assets/compiled/**
assets/static/**
```

Most Blade references use Laravel `asset('assets/...')`. A smaller set is hard-coded as `/assets/...` in the service worker, web manifest, and push-notification payload factories.

## Static CDN decision

Static application assets are not runtime media, but they are intentionally included in the broader Cloudflare offload because keeping the 74 MB tree on every Laravel deployment is wasteful.

Locked direction:

```text
local public/assets/**
        -> sync preserving relative tree
R2 glasspos-media/assets/**
        -> https://media.arbiconbengkel.my.id/assets/**
```

Preserving `assets/**` rather than adding an extra `public/` prefix keeps CSS-relative URLs and existing application paths simpler.

Do not switch application asset URLs to the CDN until the entire asset tree has been uploaded and sampled successfully through the custom domain.

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
5. Keep domain/application ports storage-provider agnostic.
6. Cloudflare/S3 details belong in adapters/configuration.
7. Preserve the existing `assets/**` relative tree during static sync.
8. Do not point application asset URLs at R2 before upload + HTTP proof succeeds.
9. Do not delete local legacy media until object/path parity and rollback are proven.
10. Private object access remains authorization-controlled.

## Completed

- Created `glasspos-media` public bucket.
- Connected and activated `media.arbiconbengkel.my.id`.
- Created `glasspos-private` private bucket.
- Installed Laravel S3/Flysystem adapter.
- Proved S3-compatible R2 connectivity.
- Added explicit `r2_public` and `r2_private` Laravel disks.
- Routed supplier payment proof storage adapter to `r2_private`.
- Updated targeted feature test to fake `r2_private`.
- Measured and mapped the heavy `public/assets` tree.

## Remaining work

1. Create/confirm an R2 credential scoped to `glasspos-private`, then populate `R2_PRIVATE_*` locally.
2. Clear config and prove `r2_private` write/read/delete against real R2.
3. Build a repeatable CLI sync for `public/assets/**` -> `glasspos-media/assets/**`.
4. Upload the static tree and sample CSS/JS/font/image URLs through `media.arbiconbengkel.my.id`.
5. Introduce one global CDN asset-base mechanism for Laravel `asset('assets/...')` references.
6. Convert the small set of hard-coded `/assets/...` paths in service worker/manifest/push payloads deliberately.
7. Run UI/PWA regression checks before removing local copies from deployment.
8. Inventory existing legacy supplier-payment-proof rows and local files and migrate them to `glasspos-private` with parity proof.
9. Continue audit for any additional runtime media families.
10. Remove unused local public-media storage semantics only after all proofs are complete.
