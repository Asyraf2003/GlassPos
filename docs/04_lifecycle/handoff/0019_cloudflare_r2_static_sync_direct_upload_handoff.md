# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: public CDN cutover and privacy boundary are locally proven; `make verify` PHPStan blocker patched and awaiting operator rerun
- Status: active / in progress
- Progress: public R2 infrastructure, static sync, CDN delivery, font CORS, Laravel asset-root boundary, hard-coded PWA/error/push cleanup, focused regression, and public-bucket privacy gate are proven; private direct upload, legacy migration, production proof, and final cleanup remain

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

Private media bytes must not transit cPanel/PHP in the final architecture.

## Source of Truth

Operator output is highest truth. Relevant repo contracts:

- `docs/03_blueprints/infra/0012_cloudflare_r2_media_storage_migration.md`
- `docs/01_standards/0007_ai_usage_guide.md`
- `docs/01_standards/0005_handoff_template.md`

## Locked Facts

- Cloudflare is authoritative for `arbiconbengkel.my.id`.
- Public bucket: `glasspos-media`.
- Public custom domain: `https://media.arbiconbengkel.my.id`.
- Private bucket: `glasspos-private`; no public domain/development URL.
- Laravel has explicit `r2_public` and `r2_private` disks.
- Real `r2_private` write/read/delete passed.
- Real `r2_public` write/custom-domain HTTP delivery passed.
- Supplier payment proof durable adapter targets `r2_private`.
- Current supplier-proof controllers still receive PHP multipart `UploadedFile` bytes before R2 storage.
- `public/assets` inventory is 6,224 files / 56,737,036 bytes / 54.11 MB payload.
- All 6,224 static object paths were synchronized to `glasspos-media/assets/**` with zero final failures.
- Representative CSS, JS, SVG, vendor JS and Bootstrap Icons WOFF2 return HTTP 200 from the custom domain.
- Public font CORS is configured and proven.
- Laravel application asset-root contract is pulled and proven locally.
- Origin-sensitive `manifest.webmanifest` and `service-worker.js` remain application-origin.
- PWA icons and service-worker fallback icons now point to the public CDN.
- Error layout no longer suppresses CDN assets based on local `public/assets` file existence.
- Push payload factories may retain neutral `/assets/**` values; the outbound WebPush adapter resolves those through `config('app.asset_url')` before sending.
- Public privacy gate is proven: `Storage::disk('r2_public')->allFiles('supplier-payment-proofs')` returned count `0`.
- Therefore no private supplier-proof object was found under the public R2 bucket prefix at this proof point.
- Local `public/assets` still remains until browser/PWA regression, rollback, production proof, and final cleanup are complete.
- Direct-to-private-R2 port/adapter foundation exists but is not wired to production controllers/UI yet.
- Strict direct-upload adapter tests are committed but still lack operator PASS output.
- Operator `make verify` found one PHPStan error in `LaravelSupplierPaymentProofDirectUploadAdapter`: facade `Storage::disk()` was inferred as `Illuminate\Contracts\Filesystem\Filesystem`, which does not declare `temporaryUploadUrl()`.
- The direct-upload adapter was patched with an explicit `Illuminate\Filesystem\FilesystemAdapter` static type annotation. This is a static-analysis correction only; runtime behavior is unchanged. Operator rerun is still required before counting `make verify` PASS.

## Public Static Proof

### Static inventory and sync

```text
files: 6224
bytes: 56737036 (54.11 MB)

existing objects: 6028
processed: 6224/6224
uploaded: 196
uploaded bytes: 5246381 (5.00 MB)
skipped existing: 6028
skipped bytes: 51490655 (49.11 MB)
failed: 0
elapsed: 1m 09s
```

Meaning: static object path parity for this migration run is proven and resumable upload worked after interruption.

### Public font CORS - PASS

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

The public read-only wildcard CORS policy is acceptable only for `glasspos-media`, which is intentionally public. It must never be copied to `glasspos-private`.

### Public private-data gate - PASS

Operator proof:

```bash
php artisan tinker --execute='$files=Storage::disk("r2_public")->allFiles("supplier-payment-proofs"); dump(count($files));'
```

Result:

```text
0
```

Meaning: the known private supplier-proof prefix is absent from the public bucket at this checkpoint. If a future migration adds public runtime media, this boundary remains mandatory.

## Public CORS Management History

Retain these failed attempts so a future session does not repeat them:

- Default Wrangler OAuth saw Cloudflare account `09449fd2f7378ddd4d4ded7831827c30`.
- Working Laravel R2 endpoint belongs to account `69316314aef2f38d89ba5e364b034e5d`.
- Forcing Wrangler to the R2 account returned auth error `10000`.
- `wrangler login --profile` is invalid on Wrangler 4.128.0.
- Named-profile OAuth then failed with browser CSRF `request_forbidden`.
- `PutBucketCors` through application S3 credentials reached the correct bucket but returned `AccessDenied`.
- Decision: keep object credentials least-privilege and manage bucket CORS through the correct Cloudflare management plane/dashboard.
- Wrangler and Dashboard CORS JSON shapes differ; both tracked files are retained:

```text
deploy/cloudflare/glasspos-media-cors.json
deploy/cloudflare/glasspos-media-cors-dashboard.json
```

## Application CDN Cutover - LOCAL PASS

Current config contract:

```php
// config/app.php
'asset_url' => env('ASSET_URL', env('R2_PUBLIC_URL')),
```

Production env contract:

```dotenv
ASSET_URL=https://media.arbiconbengkel.my.id
R2_PUBLIC_URL=https://media.arbiconbengkel.my.id
```

Main layout boundary:

```text
asset('assets/...')          -> public CDN
url('/manifest.webmanifest') -> APP_URL origin
url('/service-worker.js')    -> APP_URL origin
```

Operator runtime proof:

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

`http://localhost` is correct for the operator's local `APP_URL`; the important proof is that the two origin-sensitive root resources did not inherit the CDN asset base.

Focused contract test after hard-coded PWA/error/push cleanup:

```text
PASS Tests\Feature\Infrastructure\PublicAssetCdnContractFeatureTest
6 passed / 25 assertions
```

Covered contracts include:

- application CDN asset root
- same-origin manifest/service-worker registration
- CDN-backed static layout assets
- CDN PWA icons and service-worker fallback icons
- error layout no longer depending on local file existence
- outbound push adapter resolving neutral application asset paths through the CDN base

The operator also ran:

```bash
php -d memory_limit=512M artisan test --compact
```

The command printed the compact progress stream and returned to the shell prompt with no failure output shown. Do not invent an exact suite count because compact output did not print one in the captured transcript.

## Current Public Runtime Files

Keep these application-origin resources during the first cut:

```text
public/index.php
public/robots.txt
public/service-worker.js
public/manifest.webmanifest
public/favicon.ico
```

Their referenced heavy images/icons may use the public CDN.

## Private Supplier Payment Proof State

Classification: PRIVATE runtime media.

Object key contract:

```text
supplier-payment-proofs/{supplier-payment-id}/{generated-filename}
```

Current durable storage:

```text
App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter
-> r2_private
```

Current bottleneck remains:

```text
Browser -> cPanel/PHP multipart -> PHP temp -> Laravel -> glasspos-private
```

Target:

```text
Browser -> Laravel prepare/authorize
Browser -> glasspos-private direct PUT
Browser -> Laravel finalize
Laravel -> verify real object -> DB/audit mutation
```

Foundation exists:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

The adapter targets `r2_private`, generates safe opaque keys, clamps TTL, propagates required content type, and fails closed.

### Static analysis blocker discovered

Operator command:

```bash
make verify
```

PHPStan result before patch:

```text
Call to an undefined method Illuminate\Contracts\Filesystem\Filesystem::temporaryUploadUrl().
```

Root cause: Laravel facade return typing is broader than the concrete adapter capability used here. Runtime R2/Flysystem adapter is an `Illuminate\Filesystem\FilesystemAdapter`, which exposes the temporary upload URL method used by this foundation.

Patch pushed:

```text
app/Adapters/Out/Procurement/LaravelSupplierPaymentProofDirectUploadAdapter.php
```

The patch imports `Illuminate\Filesystem\FilesystemAdapter` and annotates the disk variable explicitly for PHPStan. No runtime branch, cast, or behavior change was introduced.

Operator must rerun `make verify`; do not claim PASS until that output is provided.

## GAP

- Browser UI/PWA/icon regression has not yet been performed against the CDN cutover.
- `make verify` post-patch PASS is not yet proven.
- Strict private direct-upload feature tests have no operator PASS proof yet.
- Private-bucket CORS is not configured/proven.
- A real short-lived browser-style presigned PUT to `glasspos-private` is not proven.
- Prepare/finalize direct-upload use cases and integrity-bound upload intent are not complete.
- Existing supplier-proof multipart controllers still send bytes through PHP.
- Legacy supplier-proof DB rows/local files have not been inventoried/migrated/repaired.
- Composer manifest/lock must still be checked before production to ensure `league/flysystem-aws-s3-v3` is committed coherently.
- Production deployment, rollback proof, and local static/media cleanup remain pending.

## Locked Decisions

- Public/private data use separate buckets.
- `glasspos-media` is intentionally public; `glasspos-private` remains private.
- Database stores object keys, not hard-coded R2 URLs.
- Laravel/controller/application code remains storage-provider agnostic for business media.
- Static R2 preserves the `assets/**` relative tree.
- `manifest.webmanifest` and `service-worker.js` stay same-origin.
- Laravel `asset()` is the static cutover seam.
- Push application payloads may remain provider-neutral; the outbound adapter owns conversion of `/assets/**` to the configured public asset base.
- Client MIME/size is not trusted as final upload verification.
- Final private uploads send bytes browser -> R2, not browser -> PHP -> R2.
- Local asset/media copies are deleted only after parity, browser regression, privacy, rollback, and production proof.
- Static-analysis type fixes must not be counted as runtime or verification proof until the operator reruns the relevant checks.

## Remaining Work in Order

1. Rerun `make verify` after the PHPStan type patch.
2. Run the strict private direct-upload adapter test and record PASS.
3. Run browser UI/PWA/font/icon smoke while local `public/assets` remains rollback.
4. Configure strict `glasspos-private` CORS for the real application origin and required PUT headers/methods.
5. Prove a real short-lived presigned PUT against `glasspos-private`.
6. Add prepare/finalize use cases plus actor/business-scope-bound upload intent and real-object verification.
7. Cut both supplier-proof flows from multipart PHP upload to direct browser -> R2.
8. Inventory/migrate legacy supplier proof rows/files and prove DB/object parity.
9. Continue audit for any other durable runtime-media families.
10. Verify Composer manifest/lock coherence for the R2 Flysystem dependency.
11. Deploy to production and prove cPanel no longer owns durable media or private upload bytes.
12. Remove obsolete local media/static copies only after rollback/parity/production proof.

## Exact Next Operator Proof

Pull the PHPStan type patch and rerun repository verification:

```bash
sai pull && make verify
```

Required result: PHPStan must no longer report `temporaryUploadUrl()` as undefined. If another verification error appears, treat that exact output as the next active blocker before advancing to private CORS or controller cutover.
