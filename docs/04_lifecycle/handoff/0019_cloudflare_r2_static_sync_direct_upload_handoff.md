# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: static tree uploaded/proven; application CDN cutover safety gate next
- Status: active / in progress
- Progress: R2 infrastructure and static object delivery proven; application cutover, direct browser upload, legacy migration, production proof, and local cleanup remain

## Target Work Page

Move GlassPos durable media and heavy public assets away from cPanel while keeping Laravel as the authorization/business control plane.

Final target:

```text
static assets
Browser -> media.arbiconbengkel.my.id -> glasspos-media

private supplier proof upload
Browser -> Laravel prepare/authorize
Browser -> glasspos-private directly
Browser -> Laravel finalize/verify/business mutation
```

The private-media file bytes must not transit cPanel/PHP in the final architecture.

## References Used
- Blueprint: `docs/03_blueprints/infra/0012_cloudflare_r2_media_storage_migration.md`
- Workflow: incremental proof-driven migration; do not delete local fallback before parity/regression proof
- DoD: final production state has public static assets on R2/CDN, private supplier proof objects in private R2, direct browser upload, legacy DB/object parity, and no durable media dependency on cPanel
- ADR: existing repo architecture/hexagonal rules; no new permanent ADR created in this slice yet
- Previous handoff: no R2-specific active handoff existed
- Repo snapshot / command output: operator outputs from 2026-09-03 are summarized below

## Locked Facts

- Cloudflare is authoritative for `arbiconbengkel.my.id`.
- Public R2 bucket is `glasspos-media`.
- Public custom domain is `https://media.arbiconbengkel.my.id` and is active.
- Private R2 bucket is `glasspos-private`; it has no public custom domain/development URL.
- Laravel has explicit `r2_public` and `r2_private` disks.
- Real `r2_private` write/read/delete proof passed.
- Real `r2_public` write and custom-domain HTTP 200 proof passed.
- Supplier payment proof durable adapter now targets `r2_private`.
- Current supplier-proof web controllers still receive multipart `UploadedFile` bytes through PHP/cPanel before R2 storage.
- `public/assets` contains 6,224 files with 56,737,036 bytes actual payload (54.11 MB).
- Public asset tree is now fully present in R2 at `assets/**`.
- Representative CSS, JS, SVG, and vendor-extension objects return HTTP 200 through `media.arbiconbengkel.my.id` with expected MIME/content length.
- Local `public/assets` must not be deleted yet.
- Direct-to-R2 port/adapter foundation exists but production multipart controllers have intentionally not been cut over yet.
- Strict direct-upload adapter tests are committed, but no local PASS output has yet been provided by the operator.

## Scope Used

### SCOPE-IN
- Cloudflare R2 public/private bucket split.
- Laravel R2 filesystem integration.
- Supplier payment proof durable storage boundary.
- Public Mazer/static asset upload and delivery.
- Resumable static upload tooling.
- Direct-to-private-R2 presigned upload foundation.
- Documentation/progress/handoff continuity.

### SCOPE-OUT
- Deleting `public/assets` before application cutover/regression proof.
- Exposing private supplier proof objects through a public URL.
- Rewriting unrelated cPanel/DNS/email records.
- Migrating framework-local logs/cache/session/temp storage to R2.
- Production cutover before local and real-R2 proof gates pass.

## GAP

- Cross-origin font/CORS behavior is not yet proven for application-origin pages loading fonts from `media.arbiconbengkel.my.id`.
- Laravel application references still generally use local `asset('assets/...')` paths.
- `service-worker.js` and `manifest.webmanifest` require deliberate same-origin handling before any global `ASSET_URL`-style cutover.
- Strict direct-upload test suite has no operator PASS proof yet.
- Private R2 CORS and a real presigned browser-style PUT have not been proven.
- Direct-upload prepare/finalize use cases and upload-intent integrity verification are not yet implemented.
- Both supplier payment proof multipart controllers still send file bytes through PHP.
- Legacy supplier proof DB rows/files have not been inventoried/migrated/repaired.
- Production deployment and rollback/parity proof remain pending.

## Locked Decisions

- Public and private R2 data use separate buckets, not only separate prefixes.
- `glasspos-media` is public; `glasspos-private` remains private.
- Database stores object keys, not hard-coded public R2 URLs.
- Laravel/controller/application layers remain storage-provider agnostic; Cloudflare/S3 details stay in outbound adapters/config.
- Final private upload is direct browser -> R2; Laravel only prepares/authorizes/finalizes/verifies.
- Client-supplied MIME/size metadata is not trusted as final verification.
- Static R2 keeps the existing `assets/**` relative tree so CSS-relative dependencies continue to resolve naturally.
- `public/service-worker.js` and `public/manifest.webmanifest` stay application-origin during the first CDN cut.
- Local static/media copies are deleted only after parity, regression, production, and rollback proof.

## Files Created / Changed

### New files
- `app/Console/Commands/UploadPublicAssetsToR2.php`
- `app/Ports/Out/Procurement/SupplierPaymentProofDirectUploadPort.php`
- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofDirectUploadAdapter.php`
- `tests/Feature/Procurement/SupplierPaymentProofDirectUploadAdapterFeatureTest.php`
- static uploader hardening test file under `tests/Feature/Infrastructure/`
- `docs/04_lifecycle/handoff/0019_cloudflare_r2_static_sync_direct_upload_handoff.md`

### Changed files
- `config/filesystems.php`
- `.env.example`
- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php`
- `app/Providers/ProcurementPaymentServiceProvider.php`
- `tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php`
- `docs/03_blueprints/infra/0012_cloudflare_r2_media_storage_migration.md`

## Verification Proof

- command:
  ```bash
  php artisan tinker --execute='$d=Storage::disk("r2_private"); $p="healthcheck/private-r2-test.txt"; dump($d->put($p,"GlassPos private R2 OK"), $d->exists($p), $d->get($p)); $d->delete($p); dump($d->exists($p));'
  ```
  - result: `true`, `true`, exact payload, then `false` after delete
  - meaning: Laravel/Flysystem can write/read/delete against the real private bucket

- command:
  ```bash
  php artisan tinker --execute='$d=Storage::disk("r2_public"); $p="healthcheck/public-r2-test.txt"; dump($d->put($p,"GlassPos public R2 OK"), $d->exists($p), $d->url($p));'
  curl -i https://media.arbiconbengkel.my.id/healthcheck/public-r2-test.txt
  ```
  - result: Laravel write/exists `true`; generated custom-domain URL; HTTP/2 200; body `GlassPos public R2 OK`
  - meaning: public R2 and custom domain work end-to-end

- command:
  ```bash
  php artisan r2:upload-public-assets --dry-run
  ```
  - result: 6,224 files; 56,737,036 bytes / 54.11 MB
  - meaning: static payload inventory proven

- command:
  ```bash
  php artisan r2:upload-public-assets
  ```
  - first run: manually interrupted after the uploader had placed 6,028 objects
  - hardened resume result:
    ```text
    existing objects: 6028
    processed: 6224/6224
    uploaded: 196
    uploaded bytes: 5246381 (5.00 MB)
    skipped existing: 6028
    skipped bytes: 51490655 (49.11 MB)
    failed: 0
    elapsed: 1m 09s
    Sinkronisasi public/assets ke R2 selesai tanpa kegagalan.
    ```
  - meaning: 6,224/6,224 static object parity is proven and resume behavior survives interruption

- command:
  ```bash
  curl -sSI https://media.arbiconbengkel.my.id/<representative-assets>
  ```
  - result:
    ```text
    assets/compiled/css/app.css
    HTTP/2 200
    content-type: text/css; charset=utf-8
    content-length: 337533

    assets/compiled/js/app.js
    HTTP/2 200
    content-type: text/javascript; charset=utf-8
    content-length: 173876

    assets/compiled/svg/favicon.svg
    HTTP/2 200
    content-type: image/svg+xml
    content-length: 387

    assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js
    HTTP/2 200
    content-type: text/javascript; charset=utf-8
    content-length: 19549
    ```
  - meaning: representative public asset delivery through the CDN domain is proven

## Risks / Follow-up Notes

- `cf-cache-status: DYNAMIC` was observed. This is not a delivery failure; cache optimization should happen only after the application cutover is stable.
- Plain HTTP 200 does not prove browser font CORS. Font/icon behavior must be tested with an application-origin `Origin` request and later in-browser.
- A global Laravel `ASSET_URL` can accidentally move `service-worker.js` and `manifest.webmanifest` to the CDN unless those references are made explicitly same-origin first.
- CSS uses relative `url(...)` references, including fonts. Preserving the directory tree was therefore intentional.
- Do not run cleanup/deletion of local assets or legacy media until all application references and production behavior are proven.
- Do not ask the operator to paste credentials. R2 secrets remain environment-only.

## Next Step

Prove the static-CDN cross-origin font/CORS gate before changing Laravel asset URLs.

Use the known Bootstrap Icons font object:

```text
assets/extensions/bootstrap-icons/font/fonts/bootstrap-icons.woff2
```

Run a browser-origin-style header probe from the operator machine and inspect whether the response permits `https://arbiconbengkel.my.id` (or a safe wildcard for public static assets). Do not perform the global application CDN cutover until this gate is understood.
