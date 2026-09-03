# Cloudflare R2 Media Storage Migration

Status: In progress
Date: 2026-09-03

## Goal

Move all runtime application media out of Laravel local/public storage and into Cloudflare R2. Laravel remains the control plane for authorization, metadata, upload orchestration, object naming, delete/read decisions, and private delivery.

Static application assets that are part of the deployed UI bundle remain application assets and are not treated as runtime media.

## Target boundary

```text
Laravel / application server
├── controllers / policies / use cases
├── metadata and storage object keys
├── compiled/static application assets
└── no durable runtime media storage

Cloudflare R2: glasspos-media
├── private runtime objects
└── public runtime objects when/if public media is introduced
```

## R2 setup proof

- Bucket: `glasspos-media`
- Region placement: Cloudflare automatic, Asia Pacific
- Storage class: Standard
- Laravel S3 adapter: `league/flysystem-aws-s3-v3`
- R2 S3 connectivity was proven locally with a write + exists healthcheck returning `true`.
- Credentials are environment-only and must not be committed.
- Account token is scoped to Object Read & Write for `glasspos-media` only.

Expected environment contract:

```dotenv
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=auto
AWS_BUCKET=glasspos-media
AWS_ENDPOINT=https://<cloudflare-account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

`AWS_URL` is intentionally not part of the current private-object flow.

## Current runtime media inventory

### 1. Supplier payment proof attachments

Classification: PRIVATE runtime media.

Database metadata:

- `supplier_payment_proof_attachments.storage_path`
- original filename
- MIME type
- file size
- uploaded timestamp / actor metadata

Object key contract:

```text
supplier-payment-proofs/{supplier-payment-id}/{generated-filename}
```

The storage path guard validates an object key, not an absolute/local filesystem path. This makes the existing path contract compatible with R2 object keys.

Storage port:

```text
App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort
```

Adapter:

```text
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter
```

The adapter was switched from Laravel disk `local` to disk `s3` for:

- `putFileAs`
- delete
- exists
- get

Targeted adapter tests pass using `Storage::fake('s3')`:

```text
2 passed
10 assertions
```

Private delivery remains application-controlled. Existing attachment preview/serve flows resolve metadata, read through the storage port, and return the file through Laravel rather than exposing the R2 bucket directly.

## Public directory classification

The current `public/` tree is overwhelmingly application/static UI content, including:

- compiled CSS/JS
- Mazer/vendor extensions
- fonts
- icons
- PWA icons and manifest assets
- service worker
- error illustrations
- application logo/favicon
- bundled images such as dashboard/visual assets

Examples:

```text
public/assets/compiled/**
public/assets/static/**
public/assets/extensions/**
public/service-worker.js
public/manifest.webmanifest
```

These are NOT runtime media uploads and must not be mechanically moved to R2 merely because they are images/fonts/files.

They are referenced with Laravel `asset(...)`, `public_path(...)`, or direct static paths and are part of the deployed application UI/runtime bundle.

## Mazer decision

Mazer is currently an application UI asset dependency, not user-generated/runtime media.

Therefore:

- Do not move the Mazer/vendor asset tree to the R2 media bucket as part of this migration.
- Keep Mazer/static/compiled assets with the application deployment unless a separate static-CDN migration is intentionally designed later.
- R2 media migration and static asset CDN migration are separate concerns.

This avoids coupling application boot-critical CSS/JS/fonts to an object-storage media migration.

## Local Laravel storage decision

`config/filesystems.php` still defines:

- `local` -> `storage/app/private`
- `public` -> `storage/app/public`
- `public/storage` symlink mapping

Current repository scans did not prove an active runtime media flow using `Storage::disk('public')`, `asset('storage/...')`, or `storage/app/public`.

Do NOT remove the disks/symlink configuration yet. Removal is allowed only after a complete runtime-media audit and regression proof confirms no application flow depends on them.

Framework-local storage such as logs, sessions, cache, and temporary files is explicitly outside the media migration scope.

## Public vs private R2 strategy

### Private

Use R2 object keys with no public bucket URL. Laravel remains responsible for authorization and delivery, or a future short-lived signed URL strategy may be introduced deliberately.

Current supplier payment proofs belong here.

### Public

No active user-generated public-media flow has been proven by the repository scan yet.

When such flows exist, use a deliberate public object namespace / delivery domain rather than reusing Laravel `public/storage` semantics by accident.

A future public namespace may follow a structure such as:

```text
public/{domain}/{entity-id}/{filename}
```

but this is not locked until a real public runtime-media use case is identified.

## Migration rules

1. Never store R2 credentials in repository files.
2. Store object keys in the database, not absolute R2 URLs.
3. Keep domain/application ports storage-provider agnostic.
4. Cloudflare/S3 details belong in adapters/configuration.
5. Do not migrate compiled/vendor/static application assets as if they were runtime media.
6. Do not delete local media paths until legacy data is copied, row/object parity is proven, and rollback exists.
7. Private object access must remain authorization-controlled.

## Remaining work

- Prove the switched supplier-payment-proof adapter against real R2 through the application/runtime flow, including write/read/delete.
- Inventory existing legacy supplier payment proof rows and physical local files, if any.
- Design and execute legacy local -> R2 copy with object-count/path parity proof before deleting local copies.
- Continue repository/database audit for any additional runtime media families not found by the first scan.
- Identify any true public runtime-media use cases before creating a public R2 delivery domain.
- Only after all runtime media is externalized, consider removing unused `public` storage disk/symlink semantics.

## Explicit non-goal

This migration does not move the entire Laravel `public/` directory to Cloudflare R2. Static UI delivery/CDN optimization is a separate architectural decision.
