# 0063 - Static Asset URL Implicitly Coupled To R2 Public URL

## Status

Fixed and verified locally. Production configuration and CDN sync remain deployment work.

## Context

The cashier note workspace HTML rendered the new Blade markup locally, but Laravel's `asset()` helper generated URLs under `https://media.arbiconbengkel.my.id` even when `ASSET_URL` was not explicitly configured.

Observed effect:

- the new workspace CSS returned 404;
- the new `presentation.js` returned 404;
- previously uploaded workspace JavaScript returned HTTP 200 but could be stale;
- local UI acceptance therefore did not exercise the checked-out static assets.

## Root Cause

`config/app.php` configured:

```php
'asset_url' => env('ASSET_URL', env('R2_PUBLIC_URL')),
```

This coupled two independent concerns. `R2_PUBLIC_URL` is the public object-storage/media base, while `ASSET_URL` is Laravel's static UI asset base. Configuring runtime R2 media therefore redirected every `asset()` URL to the shared CDN.

The public asset upload command also had only two broad modes: resume skipped existing objects, while `--force` overwrote the complete local public asset inventory. It lacked a safe way to publish one changed asset.

## Fix

- `config('app.asset_url')` now reads only `ASSET_URL`.
- With no explicit `ASSET_URL`, local static assets resolve on the application origin.
- `R2_PUBLIC_URL` remains owned by the `r2_public` filesystem disk; public/private storage boundaries were not changed.
- `.env.example` documents explicit production `ASSET_URL` and release-specific `ASSET_VERSION`.
- `r2:upload-public-assets` now accepts repeatable `--path` values relative to canonical `public/assets`.
- Targeted mode overwrites only requested objects, preserves `assets/<relative-path>`, retains MIME/cache metadata, rejects unsafe/outside/missing paths, and never enumerates or mutates unrelated remote objects.

## Regression Proof

- `PublicAssetCdnContractFeatureTest` proves same-origin local UI assets, unchanged R2 media URL, and the existing `ASSET_VERSION` -> `APP_VERSION` cache-busting contract.
- `UploadPublicAssetsToR2CommandFeatureTest` proves targeted overwrite, new upload, object key, MIME, Cache-Control, remote isolation, and traversal/absolute/missing-path rejection with fake storage.
- Chromium loaded all ten cashier workspace assets from `127.0.0.1` without workspace asset failures.

## Deployment Boundary

No shared production CDN object was uploaded during this local implementation. Production must explicitly set `ASSET_URL`, advance the release asset version, run the reviewed targeted sync command, and verify the deployed asset responses.
