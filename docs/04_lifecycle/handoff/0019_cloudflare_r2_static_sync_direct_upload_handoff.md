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
- Wrangler OAuth is authenticated as `almustaqbal010@gmail.com` and only sees account `09449fd2f7378ddd4d4ded7831827c30`.
- Forcing `CLOUDFLARE_ACCOUNT_ID=69316314aef2f38d89ba5e364b034e5d` caused Cloudflare API authentication error `10000`; current Wrangler OAuth therefore does not have management access to the R2 account used by Laravel.
- `wrangler login --profile glasspos` is invalid on Wrangler 4.128.0; named profiles use the newer auth commands.
- A subsequent OAuth profile creation attempt failed in the browser with `request_forbidden` due to CSRF state mismatch, so CLI management authentication remains unresolved.
- Attempting `PutBucketCors` through the existing Laravel S3-compatible R2 credentials reached the correct bucket endpoint but returned `AccessDenied`.
- Therefore the existing Laravel public R2 token is sufficient for object I/O but does not have bucket-configuration permission. It must not be assumed capable of changing CORS.
- The post-failure font request still returns HTTP 200 and no CORS header, confirming the failed `PutBucketCors` did not alter the bucket policy.
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

- `glasspos-media` CORS policy has not yet been applied/proven; current font probe lacks `Access-Control-Allow-Origin`.
- Wrangler is authenticated to a different Cloudflare account than the one encoded in Laravel's working R2 endpoint; correct-account management access is still unresolved.
- Existing S3-compatible application credentials cannot change bucket CORS (`PutBucketCors` -> `AccessDenied`).
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
- Do not broaden the current application R2 token merely to solve management-plane CORS. Prefer using the Cloudflare dashboard or a separate least-privilege management credential.

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

- Real private R2 proof:
  - write -> exists -> read -> delete -> missing passed against `r2_private`.
- Real public R2 proof:
  - write/exists passed and custom-domain HTTP 200 returned expected payload.
- Static inventory:
  - `6224` files / `56,737,036` bytes / `54.11 MB`.
- Static resume proof:
  ```text
  existing objects: 6028
  processed: 6224/6224
  uploaded: 196
  skipped existing: 6028
  failed: 0
  elapsed: 1m 09s
  ```
- Representative public delivery:
  - CSS, JS, SVG, vendor JS, and Bootstrap Icons WOFF2 return HTTP/2 200 with expected MIME/content length.
- Font CORS probe:
  ```text
  HTTP/2 200
  content-type: font/woff2
  cache-control: public, max-age=86400
  ```
  No `Access-Control-Allow-Origin` header.
- Wrangler account proof:
  ```text
  R2 account: 69316314aef2f38d89ba5e364b034e5d
  Wrangler OAuth account: 09449fd2f7378ddd4d4ded7831827c30
  forcing R2 account -> Authentication error [code: 10000]
  ```
- S3 management-plane proof:
  ```text
  PutBucketCors -> AccessDenied
  target endpoint: glasspos-media.69316314aef2f38d89ba5e364b034e5d.r2.cloudflarestorage.com
  ```
  Meaning: correct bucket/account endpoint was reached, but the object token lacks bucket-CORS configuration permission.

## Risks / Follow-up Notes

- `cf-cache-status: DYNAMIC` was observed. This is not a delivery failure; cache optimization should happen only after the application cutover is stable.
- Cloudflare documents that custom-domain responses reflect R2 CORS policy for requests containing a valid `Origin`; after changing CORS, cached assets may need refresh/purge before new headers appear.
- A global Laravel `ASSET_URL` can accidentally move `service-worker.js` and `manifest.webmanifest` to the CDN unless those references are made explicitly same-origin first.
- CSS uses relative `url(...)` references, including fonts. Preserving the directory tree was intentional.
- Do not run cleanup/deletion of local assets or legacy media until all application references and production behavior are proven.
- Do not ask the operator to paste credentials. R2 secrets remain environment-only.
- The current blocker is management-plane permission/access, not object storage connectivity.

## Next Step

Use the Cloudflare dashboard while signed into the account that contains `glasspos-media` and add the tracked policy from `deploy/cloudflare/glasspos-media-cors.json` under R2 -> `glasspos-media` -> Settings -> CORS Policy. Then repeat the font request with `Origin: https://arbiconbengkel.my.id` and require `Access-Control-Allow-Origin` before Laravel asset cutover.
