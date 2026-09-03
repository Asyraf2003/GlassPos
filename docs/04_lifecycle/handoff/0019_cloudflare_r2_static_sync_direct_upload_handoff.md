# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: public CDN lane verified; repository verification green; private direct-upload adapter proof is next
- Status: active / in progress

## Goal

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

1. Latest local operator output
2. `docs/01_standards`
3. `docs/02_architecture/adr`
4. `docs/03_blueprints/infra/0012_cloudflare_r2_media_storage_migration.md`
5. This handoff

## Locked Facts

- Cloudflare is authoritative for `arbiconbengkel.my.id`.
- Public bucket: `glasspos-media`.
- Public custom domain: `https://media.arbiconbengkel.my.id`.
- Private bucket: `glasspos-private`; no public domain/development URL.
- Laravel has explicit `r2_public` and `r2_private` disks.
- Real `r2_private` write/read/delete proof passed.
- Real `r2_public` write/custom-domain HTTP delivery proof passed.
- Supplier payment proof durable adapter targets `r2_private`.
- Current supplier-proof controllers still receive PHP multipart bytes before R2 storage.
- `public/assets` inventory: 6,224 files / 56,737,036 bytes / 54.11 MB.
- All 6,224 static object paths were synchronized to `glasspos-media/assets/**` with zero final failures.
- Public font CORS is configured and proven.
- Laravel asset-root cutover is locally proven.
- `manifest.webmanifest` and `service-worker.js` remain application-origin.
- PWA icons and service-worker fallback icons use the public CDN.
- Error layout no longer depends on local `public/assets` file existence.
- Push outbound adapter resolves neutral `/assets/**` icon/badge paths through `config('app.asset_url')`.
- Public privacy gate passed: `glasspos-media/supplier-payment-proofs/**` object count is `0`.
- Local `public/assets` remains until browser/PWA regression, rollback, production proof, and final cleanup.
- Direct-to-private-R2 port/adapter foundation exists but is not wired to production controllers/UI yet.

## Public Static Proof

### Sync completion

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

### Representative CDN delivery

Representative CSS, JS, SVG, vendor JS and Bootstrap Icons WOFF2 returned HTTP 200 from the custom domain.

### Public font CORS

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

Wildcard read-only CORS is acceptable only for `glasspos-media`, which is intentionally public. Never copy this policy to `glasspos-private`.

### Public privacy gate

```bash
php artisan tinker --execute='$files=Storage::disk("r2_public")->allFiles("supplier-payment-proofs"); dump(count($files));'
```

Result:

```text
0
```

Meaning: no known private supplier-proof objects are present in the public bucket prefix at this checkpoint.

## Application CDN Cutover - LOCAL PASS

Current config contract:

```php
'asset_url' => env('ASSET_URL', env('R2_PUBLIC_URL')),
```

Runtime proof:

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

Focused CDN contract proof:

```text
Tests: 6 passed (25 assertions)
```

## Repository Verification - PASS

The initial `make verify` surfaced two migration follow-ups:

1. PHPStan could not see `temporaryUploadUrl()` on the broad filesystem contract.
2. The static R2 uploader command violated the repository 100-line file limit.

Fixes applied:

- direct-upload adapter disk annotated as concrete `Illuminate\Filesystem\FilesystemAdapter` for static analysis only;
- `UploadPublicAssetsToR2` split into small support classes under `app/Console/Support/PublicAssets/`;
- `make verify` now uses Pest `--compact` while preserving the same Pest runner;
- supplier payment proof feature fixtures were migrated from `Storage::fake('local')` to `Storage::fake('r2_private')` to match the real durable-storage contract.

Targeted regression after fixture correction:

```text
Tests: 24 passed (149 assertions)
Duration: 6.04s
```

Final operator `make verify` proof:

```text
PHPStan: [OK] No errors
line-count audit: PASS
Blade PHP/directive audit: PASS
Contract audit: PASS
Tests: 1505 passed (9415 assertions)
Duration: 58.89s
```

Repository quality gate is therefore green at this checkpoint.

## Public CORS Management History

Retain these failures so later sessions do not repeat them:

- Default Wrangler OAuth saw account `09449fd2f7378ddd4d4ded7831827c30`.
- Working Laravel R2 endpoint belongs to account `69316314aef2f38d89ba5e364b034e5d`.
- Forcing Wrangler to the R2 account returned auth error `10000`.
- `wrangler login --profile` is invalid on Wrangler 4.128.0.
- Named-profile OAuth failed with browser CSRF `request_forbidden`.
- `PutBucketCors` through application S3 credentials reached the correct bucket but returned `AccessDenied`.
- Decision: keep application object credentials least-privilege and manage bucket CORS through the correct Cloudflare management plane/dashboard.
- Wrangler and Dashboard CORS JSON shapes differ; both policy files remain tracked.

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

Current bottleneck:

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

Foundation:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

The adapter targets `r2_private`, generates safe opaque keys, clamps TTL, propagates required content type, and fails closed.

## Locked Decisions

- Public/private data use separate buckets.
- Database stores object keys, not hard-coded R2 public URLs.
- Laravel/controller/application code remains storage-provider agnostic for business media.
- Static R2 preserves the `assets/**` tree.
- `manifest.webmanifest` and `service-worker.js` stay same-origin.
- Laravel `asset()` is the static cutover seam.
- Final private uploads send bytes browser -> R2, not browser -> PHP -> R2.
- Client MIME/size is not trusted as final verification.
- Private bucket CORS must be origin/method constrained, never public wildcard.
- Local asset/media copies are deleted only after parity, browser regression, rollback, and production proof.

## Remaining Work in Order

1. Run strict private direct-upload adapter tests and record PASS.
2. Run browser UI/PWA/font/icon smoke while local `public/assets` remains rollback.
3. Configure strict `glasspos-private` CORS for the real application origin and required PUT headers/methods.
4. Prove a real short-lived presigned PUT against `glasspos-private`.
5. Add prepare/finalize use cases plus actor/business-scope-bound upload intent and real-object verification.
6. Cut both supplier-proof flows from multipart PHP upload to direct browser -> R2.
7. Inventory/migrate legacy supplier proof rows/files and prove DB/object parity.
8. Continue audit for any other durable runtime-media families.
9. Verify Composer manifest/lock coherence for `league/flysystem-aws-s3-v3`.
10. Deploy to production and prove cPanel no longer owns durable media or private upload bytes.
11. Remove obsolete local media/static copies only after rollback/parity/production proof.

## Exact Next Operator Proof

Run the strict direct-upload adapter suite:

```bash
php artisan test --compact tests/Feature/Procurement/SupplierPaymentProofDirectUploadAdapterFeatureTest.php
```

Required result: all strict adapter tests PASS before private CORS or real presigned PUT work advances.
