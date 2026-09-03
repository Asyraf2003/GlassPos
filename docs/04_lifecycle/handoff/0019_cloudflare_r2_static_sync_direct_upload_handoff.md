# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: static tree uploaded/proven; public CDN CORS gate is the active blocker before Laravel asset cutover
- Status: active / in progress
- Progress: R2 infrastructure and static object delivery proven; public font CORS is not yet enabled; application cutover, direct browser upload, legacy migration, production proof, and local cleanup remain

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
- Representative CSS, JS, SVG, vendor-extension, and Bootstrap Icons font objects return HTTP 200 through `media.arbiconbengkel.my.id` with expected MIME/content length.
- A request for the Bootstrap Icons WOFF2 object with `Origin: https://arbiconbengkel.my.id` returned HTTP 200 but no `Access-Control-Allow-Origin` header. Public static font CORS is therefore not yet ready for application cutover.
- A tracked Wrangler CORS policy exists at `deploy/cloudflare/glasspos-media-cors.json`; it intentionally allows public read-only GET/HEAD from any origin because the bucket is already public.
- The R2 endpoint currently configured in local `.env` identifies Cloudflare account `69316314aef2f38d89ba5e364b034e5d`.
- Wrangler OAuth is currently authenticated as `almustaqbal010@gmail.com` and only sees account `09449fd2f7378ddd4d4ded7831827c30`.
- Forcing `CLOUDFLARE_ACCOUNT_ID=69316314aef2f38d89ba5e364b034e5d` caused Cloudflare API authentication error `10000`; therefore the current Wrangler OAuth identity does not have access to the R2 account used by Laravel.
- The earlier Wrangler `bucket does not exist` error against account `09449...` was an account-selection/auth mismatch, not proof that `glasspos-media` is missing.
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
- Public static bucket CORS required by cross-origin fonts.
- Direct-to-private-R2 presigned upload foundation.
- Documentation/progress/handoff continuity.

### SCOPE-OUT
- Deleting `public/assets` before application cutover/regression proof.
- Exposing private supplier proof objects through a public URL.
- Rewriting unrelated cPanel/DNS/email records.
- Migrating framework-local logs/cache/session/temp storage to R2.
- Production cutover before local and real-R2 proof gates pass.

## GAP

- `glasspos-media` CORS policy has not yet been applied/proven against Cloudflare; current font probe lacks `Access-Control-Allow-Origin`.
- Wrangler is authenticated to a different Cloudflare account than the one encoded in Laravel's working R2 endpoint; correct-account authentication must be established before CLI CORS changes.
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
- Public static CORS may use wildcard origin for GET/HEAD because `glasspos-media` is intentionally public and contains no private supplier proof objects.
- Private R2 CORS must not copy the public wildcard policy; direct private PUT will use a separate stricter policy.
- `public/service-worker.js` and `public/manifest.webmanifest` stay application-origin during the first CDN cut.
- Local static/media copies are deleted only after parity, regression, production, and rollback proof.
- Do not repurpose Laravel's S3-compatible R2 Access Key ID / Secret Access Key as Wrangler REST API credentials. Wrangler account-management calls use Cloudflare account authentication, while Laravel object I/O uses the S3-compatible endpoint/credentials.

## Files Created / Changed

### New files
- `app/Console/Commands/UploadPublicAssetsToR2.php`
- `app/Ports/Out/Procurement/SupplierPaymentProofDirectUploadPort.php`
- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofDirectUploadAdapter.php`
- `tests/Feature/Procurement/SupplierPaymentProofDirectUploadAdapterFeatureTest.php`
- static uploader hardening test file under `tests/Feature/Infrastructure/`
- `deploy/cloudflare/glasspos-media-cors.json`
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
  - result: CSS, JS, SVG, and vendor JS returned HTTP/2 200 with expected MIME/content length
  - meaning: representative public asset delivery through the CDN domain is proven

- command:
  ```bash
  curl -sSI \
    -H 'Origin: https://arbiconbengkel.my.id' \
    'https://media.arbiconbengkel.my.id/assets/extensions/bootstrap-icons/font/fonts/bootstrap-icons.woff2'
  ```
  - result:
    ```text
    HTTP/2 200
    content-type: font/woff2
    content-length: 130764
    cache-control: public, max-age=86400
    ```
    No `Access-Control-Allow-Origin` header was returned.
  - meaning: object delivery is healthy, but cross-origin font CORS is not yet enabled/proven

- command:
  ```bash
  ACCOUNT_ID=$(php -r '...extract R2_ENDPOINT account id from .env...')
  CLOUDFLARE_ACCOUNT_ID="$ACCOUNT_ID" npx --yes wrangler@latest r2 bucket list
  ```
  - result:
    ```text
    R2 account: 69316314aef2f38d89ba5e364b034e5d
    Authentication error [code: 10000]
    Wrangler OAuth account: 09449fd2f7378ddd4d4ded7831827c30
    ```
  - meaning: Laravel's working R2 endpoint belongs to a different Cloudflare account than the account currently accessible through Wrangler OAuth

## Risks / Follow-up Notes

- `cf-cache-status: DYNAMIC` was observed. This is not a delivery failure; cache optimization should happen only after the application cutover is stable.
- Cloudflare documents that custom-domain responses reflect R2 CORS policy for requests containing a valid `Origin`; after changing CORS, already-cached assets may need cache purge before new CORS headers appear.
- A global Laravel `ASSET_URL` can accidentally move `service-worker.js` and `manifest.webmanifest` to the CDN unless those references are made explicitly same-origin first.
- CSS uses relative `url(...)` references, including fonts. Preserving the directory tree was therefore intentional.
- Do not run cleanup/deletion of local assets or legacy media until all application references and production behavior are proven.
- Do not ask the operator to paste credentials. R2 secrets remain environment-only.
- The current blocker is identity/account access, not object storage connectivity.

## Next Step

Establish Cloudflare management access to account `69316314aef2f38d89ba5e364b034e5d`, either by logging Wrangler into a user that belongs to that account or by applying the tracked CORS policy from the Cloudflare dashboard while signed into that account. Then list/verify the policy and repeat the font request with `Origin: https://arbiconbengkel.my.id` until `Access-Control-Allow-Origin` is present. Only after that proof may Laravel asset references be cut over to the public CDN.
