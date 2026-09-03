# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: public static R2 + CORS proven; Laravel application CDN cutover code pushed and awaiting local runtime proof
- Status: active / in progress
- Progress: static infrastructure/delivery/CORS proven; application asset-root verification is next; private direct upload, legacy migration, production proof, and cleanup remain

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

## References Used
- Blueprint: `docs/03_blueprints/infra/0012_cloudflare_r2_media_storage_migration.md`
- Canonical handoff template: `docs/01_standards/0005_handoff_template.md`
- AI workflow: `docs/01_standards/0007_ai_usage_guide.md`
- Operator output from 2026-09-03 is the highest source of truth

## Locked Facts

- Cloudflare is authoritative for `arbiconbengkel.my.id`.
- Public bucket: `glasspos-media`.
- Public custom domain: `https://media.arbiconbengkel.my.id`.
- Private bucket: `glasspos-private`; no public domain/development URL.
- Laravel has explicit `r2_public` and `r2_private` disks.
- Real `r2_private` write/read/delete passed.
- Real `r2_public` write/custom-domain HTTP delivery passed.
- Supplier payment proof durable adapter targets `r2_private`.
- Current supplier-proof web controllers still receive PHP multipart `UploadedFile` bytes before R2 storage.
- `public/assets` inventory is 6,224 files / 56,737,036 bytes / 54.11 MB payload.
- All 6,224 static object paths were synchronized to `glasspos-media/assets/**` with zero final failures.
- Representative CSS, JS, SVG, vendor JS and Bootstrap Icons WOFF2 return HTTP 200 from the custom domain.
- Public font CORS is now configured and proven.
- Laravel application CDN cutover code is pushed to `main`, but local operator test/runtime URL proof has not yet been provided.
- Local `public/assets` must remain present until hard-coded paths, browser/PWA regression, rollback, and production proof are complete.
- Direct-to-private-R2 port/adapter foundation exists but is not wired to production controllers/UI yet.
- Strict direct-upload adapter tests are committed but still lack operator PASS output.

## Scope Used

### SCOPE-IN
- public/private R2 boundary
- static Mazer/application asset offload
- resumable public asset migration
- public read-only CORS for cross-origin static fonts
- Laravel application asset-root cutover
- private supplier-proof durable-storage boundary
- direct browser -> private R2 foundation
- docs/handoff continuity

### SCOPE-OUT
- deleting `public/assets` now
- exposing private supplier proof objects publicly
- copying public wildcard CORS to private R2
- migrating framework logs/cache/session/temp to R2
- production cutover without local/browser proof

## GAP

- Operator has not yet pulled and proven the new Laravel CDN asset-root contract.
- Remaining hard-coded `/assets/**` references in service worker/manifest/push payloads still resolve same-origin.
- Error layout still uses local `file_exists(public_path('assets/...'))` guards and must be changed before local asset deletion.
- Public bucket privacy audit for `supplier-payment-proofs/**` is still unproven and must return exactly zero before final cleanup.
- Strict direct-upload feature tests have no operator PASS proof yet.
- Private-bucket CORS and a real presigned PUT have not been proven.
- Prepare/finalize direct-upload use cases and integrity-bound upload intent are not yet complete.
- Existing supplier-proof multipart controllers still send bytes through PHP.
- Legacy supplier-proof DB rows/local files have not been inventoried/migrated/repaired.
- Production deployment and rollback/parity proof remain pending.
- Composer manifest/lock must still be checked before production to ensure the R2 Flysystem dependency is committed coherently.

## Locked Decisions

- Public and private R2 objects use separate buckets.
- `glasspos-media` is intentionally public; `glasspos-private` remains private.
- Database stores object keys, not hard-coded R2 public URLs.
- Laravel/controller/application code stays storage-provider agnostic where business/runtime media is concerned.
- Static R2 preserves the existing `assets/**` tree.
- Public bucket read-only CORS may use wildcard origin because the bucket is already public.
- Public wildcard CORS must never be copied to `glasspos-private`.
- Existing application object credentials remain least-privilege; do not broaden them merely to manage bucket CORS.
- `manifest.webmanifest` and `service-worker.js` remain same-origin during the first CDN cut.
- Laravel `asset('assets/...')` is the application-wide static cutover seam rather than rewriting every Blade reference.
- Local asset/media copies are deleted only after parity, browser regression, rollback, and production proof.
- Final private upload is browser -> R2 direct; Laravel prepares/authorizes/finalizes/verifies.
- Client MIME/size is not sufficient final verification.

## Public CORS Management History

The object data plane worked before the bucket management plane did. Important failed attempts are retained here so a later session does not repeat them:

- Default Wrangler OAuth saw Cloudflare account `09449fd2f7378ddd4d4ded7831827c30`.
- Working Laravel R2 endpoint belongs to account `69316314aef2f38d89ba5e364b034e5d`.
- Forcing Wrangler to `693...` returned authentication error `10000`.
- `wrangler login --profile` is invalid on Wrangler 4.128.0.
- Named-profile OAuth creation then failed with browser CSRF `request_forbidden`.
- S3-compatible `PutBucketCors` using Laravel object credentials reached the correct bucket but returned `AccessDenied`.
- Decision: do not broaden application object credentials; configure bucket CORS through the Cloudflare dashboard/management plane.
- Wrangler-format CORS and Dashboard-format CORS are different JSON shapes; both are tracked separately.

Tracked files:

```text
deploy/cloudflare/glasspos-media-cors.json
deploy/cloudflare/glasspos-media-cors-dashboard.json
```

## Files Created / Changed

### R2/static foundation
- `app/Console/Commands/UploadPublicAssetsToR2.php`
- `config/filesystems.php`
- `.env.example`
- `tests/Feature/Infrastructure/UploadPublicAssetsToR2CommandFeatureTest.php`

### Private supplier proof foundation
- `app/Ports/Out/Procurement/SupplierPaymentProofDirectUploadPort.php`
- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofDirectUploadAdapter.php`
- `app/Adapters/Out/Procurement/LaravelSupplierPaymentProofFileStorageAdapter.php`
- `app/Providers/ProcurementPaymentServiceProvider.php`
- `tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php`
- `tests/Feature/Procurement/SupplierPaymentProofDirectUploadAdapterFeatureTest.php`

### Public CORS
- `deploy/cloudflare/glasspos-media-cors.json`
- `deploy/cloudflare/glasspos-media-cors-dashboard.json`

### Application CDN cutover pushed this slice
- `config/app.php`
- `.env.example`
- `resources/views/layouts/app.blade.php`
- `tests/Feature/Infrastructure/PublicAssetCdnContractFeatureTest.php`

### Docs
- `docs/03_blueprints/infra/0012_cloudflare_r2_media_storage_migration.md`
- `docs/04_lifecycle/handoff/0019_cloudflare_r2_static_sync_direct_upload_handoff.md`
- `docs/04_lifecycle/handoff/README.md`

## Verification Proof

### Real private R2

```text
put -> true
exists -> true
get -> exact payload
delete
exists -> false
```

Meaning: actual private bucket object I/O works through Laravel/Flysystem.

### Static inventory

```text
files: 6224
bytes: 56737036 (54.11 MB)
```

### Static resume completion

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

Meaning: public static object parity for the migration run is proven and interruption-safe resume worked.

### Representative custom-domain delivery

```text
CSS     HTTP/2 200  text/css
JS      HTTP/2 200  text/javascript
SVG     HTTP/2 200  image/svg+xml
vendor  HTTP/2 200  text/javascript
font    HTTP/2 200  font/woff2
```

### Final public font CORS proof - PASS

Operator output:

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

Meaning: browser cross-origin font gate from the application origin to the public CDN is closed.

### Application CDN cutover code state

Pushed commits in this cutover sequence:

```text
0089331cb8b03655fd5de3a2962a40d8db9102a2  config app asset root
c55ebb9f12a91e93bf6f44af2738c7395a665b6c  env example asset URL
21664072b95f60063a33a3d4b95117d49d1b15fe  same-origin manifest/service worker
0272563063a01411c1287482dcaa1ccd3ff46eff  focused CDN contract test
69aa014586673562be15493d964aac22a6576510  active blueprint refresh
```

Code contract now intends:

```text
asset('assets/...')
-> https://media.arbiconbengkel.my.id/assets/...

url('/manifest.webmanifest')
-> APP_URL origin

url('/service-worker.js')
-> APP_URL origin
```

This intention is not counted as runtime PASS until the operator provides local output.

## Risks / Follow-up Notes

- `cf-cache-status: DYNAMIC` is a later optimization concern, not current delivery failure.
- Hard-coded root-relative asset URLs deliberately remain for the next slice while local fallback still exists.
- Do not delete `public/assets` yet.
- Do not ask the operator to paste credentials.
- Do not use Laravel's S3-compatible Access Key/Secret as Cloudflare REST/Wrangler management credentials.
- A global asset root is safe only because origin-sensitive manifest/service-worker URLs were moved to `url()` first.
- Before production, verify the R2 Flysystem dependency is represented in both Composer manifest and lock so a fresh install cannot lose S3 support.

## Next Step

Pull the latest `main`, clear Laravel config, run the focused CDN contract test, then prove generated URL boundaries in the real local environment.

Command:

```bash
sai pull && \
php artisan config:clear && \
php artisan test tests/Feature/Infrastructure/PublicAssetCdnContractFeatureTest.php && \
php artisan tinker --execute='dump(
    config("app.asset_url"),
    asset("assets/compiled/css/app.css"),
    url("/manifest.webmanifest"),
    url("/service-worker.js")
);'
```

Required meaning of the output:

```text
config app.asset_url     -> https://media.arbiconbengkel.my.id
asset assets/...         -> https://media.arbiconbengkel.my.id/assets/...
manifest                 -> APP_URL origin, not media.arbiconbengkel.my.id
service worker           -> APP_URL origin, not media.arbiconbengkel.my.id
focused test             -> PASS
```

Do not move to hard-coded-path cleanup until this proof passes.
