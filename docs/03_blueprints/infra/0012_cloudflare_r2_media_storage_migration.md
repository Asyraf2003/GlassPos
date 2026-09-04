# Cloudflare R2 Media and Static Asset Migration

Status: In progress
Date: 2026-09-04
Current phase: local direct-upload application, real R2, and both browser UI flows proven; legacy migration and production rollout pending

## Goal

Move durable runtime media and the heavy public UI asset tree away from cPanel while keeping Laravel as the authorization and business control plane.

The final architecture must also remove private media bytes from the cPanel/PHP multipart request path. Laravel authorizes and finalizes private uploads, while the browser sends file bytes directly to Cloudflare R2.

## Locked target boundary

```text
Laravel / application server
├── PHP application code
├── controllers / policies / use cases
├── database metadata and object keys
├── signed upload orchestration
├── private read/download authorization
├── small origin-sensitive public files
└── no durable runtime media storage

Cloudflare R2: glasspos-media             PUBLIC
└── https://media.arbiconbengkel.my.id
    ├── assets/**                        static UI / Mazer / compiled assets
    └── future explicitly-public runtime media

Cloudflare R2: glasspos-private           PRIVATE
└── no custom domain / no public development URL
    ├── supplier-payment-proof-uploads/** staging direct uploads
    └── supplier-payment-proofs/**        finalized durable proofs
```

Public and private objects are split by bucket. A public custom domain must never expose private supplier evidence.

## R2 infrastructure - PROVEN

### Public bucket

- bucket: `glasspos-media`
- custom domain: `https://media.arbiconbengkel.my.id`
- placement: Cloudflare automatic / Asia Pacific
- storage class: Standard
- real Laravel write/exists proof passed
- real custom-domain HTTP delivery proof passed

### Private bucket

- bucket: `glasspos-private`
- no custom domain
- no public development URL
- real Laravel write -> exists -> read -> delete -> missing proof passed

### Laravel disks

```text
r2_public  -> glasspos-media
r2_private -> glasspos-private
```

The generic `s3` disk remains temporarily for compatibility/migration diagnostics. Application media adapters must use the explicit disk matching the security boundary.

Expected environment contract:

```dotenv
APP_URL=https://arbiconbengkel.my.id
ASSET_URL=https://media.arbiconbengkel.my.id

R2_ENDPOINT=https://<cloudflare-account-id>.r2.cloudflarestorage.com
R2_REGION=auto
R2_USE_PATH_STYLE_ENDPOINT=false
R2_HTTP_CONNECT_TIMEOUT=10
R2_HTTP_TIMEOUT=30

R2_PUBLIC_BUCKET=glasspos-media
R2_PUBLIC_URL=https://media.arbiconbengkel.my.id
R2_PUBLIC_ACCESS_KEY_ID=...
R2_PUBLIC_SECRET_ACCESS_KEY=...

R2_PRIVATE_BUCKET=glasspos-private
R2_PRIVATE_ACCESS_KEY_ID=...
R2_PRIVATE_SECRET_ACCESS_KEY=...
```

Credentials remain environment-only and must not be committed or pasted into chat.

## Public static asset migration - PROVEN LOCALLY

### Inventory and R2 parity

```text
files:   6224
bytes:   56737036
payload: 54.11 MB
```

The R2 copy preserves the exact `assets/**` relative tree so CSS-relative fonts/images continue to resolve naturally.

Resumable completion proof:

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

Therefore static object path parity is proven at `6224/6224` for this migration run.

### Representative CDN delivery - PROVEN

HTTP 200 with expected MIME/content length was proven for representative objects under:

```text
assets/compiled/css/app.css
assets/compiled/js/app.js
assets/compiled/svg/favicon.svg
assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js
assets/extensions/bootstrap-icons/font/fonts/bootstrap-icons.woff2
```

Objects currently carry:

```text
cache-control: public, max-age=86400
```

`cf-cache-status: DYNAMIC` is a later optimization concern, not a delivery failure.

## Public font CORS - PROVEN

The public bucket uses a dashboard-applied read-only CORS policy equivalent to:

```json
[
  {
    "AllowedOrigins": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 86400
  }
]
```

Tracked policy files:

```text
deploy/cloudflare/glasspos-media-cors.json
deploy/cloudflare/glasspos-media-cors-dashboard.json
```

Final operator proof:

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

The wildcard policy is allowed only for intentionally-public `glasspos-media`. It must never be copied to `glasspos-private`.

## Application CDN cutover - LOCAL PASS

Static application references use Laravel `asset('assets/...')`, with:

```php
// config/app.php
'asset_url' => env('ASSET_URL', env('R2_PUBLIC_URL')),
```

Origin-sensitive root resources remain same-origin through `url()`:

```text
/manifest.webmanifest
/service-worker.js
```

PWA icon references and service-worker fallback notification icons use the public CDN. Error pages no longer suppress CDN CSS/icons based on `file_exists(public_path('assets/...'))` checks. Push application payloads may keep neutral `/assets/**` values; the outbound WebPush adapter resolves them to the configured CDN base before sending.

Operator generated-URL proof:

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

Focused regression:

```text
PASS Tests\Feature\Infrastructure\PublicAssetCdnContractFeatureTest
6 passed / 25 assertions
```

Operator browser/PWA/CDN smoke is also reported PASS for the current migration checkpoint. Local `public/assets` still remains as rollback until production parity and cleanup proof.

## Public private-data boundary - PROVEN

Mandatory real-bucket audit:

```php
Storage::disk('r2_public')->allFiles('supplier-payment-proofs')
```

Operator result:

```text
0
```

No object was found under the known private supplier-proof prefix in `glasspos-media` at this checkpoint.

## Repository quality gate - PROVEN

Migration verification surfaced and closed several follow-ups:

- PHPStan concrete-filesystem typing for `temporaryUploadUrl()`;
- oversized static uploader split into small support classes;
- `make verify` uses Pest `--compact` while preserving the same runner;
- supplier-proof feature fixtures use fake `r2_private`, matching production durable storage.

Final operator proof:

```text
PHPStan: PASS
line-count audit: PASS
Blade audit: PASS
Contract audit: PASS
Tests: 1505 passed (9415 assertions)
Duration: 58.89s
```

## Root/public files kept local during first cut

Do not move or delete these as part of the first static cut:

```text
public/index.php
public/robots.txt
public/service-worker.js
public/manifest.webmanifest
public/favicon.ico
```

Local `public/assets` also remains available until rollback, production proof, and final cleanup are complete.

## Private supplier-payment-proof storage

Classification: PRIVATE runtime media.

Final object key contract:

```text
supplier-payment-proofs/{supplier-payment-id}/{generated-filename}
```

Current outbound file storage:

```text
App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter
```

The durable adapter targets `r2_private` for store/delete/exists/get. Private reads remain authorization-controlled through Laravel.

## Production deployment bottleneck

The current production web flows still receive `UploadedFile` through PHP:

```text
Browser
-> cPanel/PHP multipart body
-> PHP temporary file
-> Laravel
-> glasspos-private
```

Affected flows include:

```text
UploadSupplierInvoicePaymentProofController
AttachSupplierPaymentProofController
```

R2 is already the durable destination. The local UI/controller cutover is complete, but the deployed production application still follows this multipart path until a separately authorized rollout.

## Direct-to-private-R2 target

Locked high-level flow:

```text
1. Browser -> Laravel
   request upload authorization with filename/type/size/context

2. Laravel
   authenticate + authorize + business validation
   create/reuse actor/scope-bound upload intent
   allocate safe opaque staging object keys
   issue short-lived signed PUT URLs and required headers

3. Browser -> glasspos-private directly
   media bytes bypass cPanel/PHP

4. Browser -> Laravel
   finalize upload intent

5. Laravel
   claim intent safely
   verify real staging objects, size and server-trusted type
   promote verified objects to opaque final keys
   revalidate business state
   commit payment/attachment metadata + audit + replay result
```

Dependency direction remains:

```text
Controller -> Use Case -> Storage Port -> R2 Adapter
```

Existing foundation:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

The existing adapter targets `r2_private`, generates opaque keys, clamps signed URL TTL, propagates required Content-Type, normalizes real PSR header arrays for browser use, and fails closed.

Strict adapter proof after normalization:

```text
Tests: 8 passed (35 assertions)
Duration: 0.18s
```

The adapter foundation predates the staging/final split locked below and may require a narrow contract adaptation during implementation.

## Private bucket CORS - PROVEN

Tracked policies:

```text
deploy/cloudflare/glasspos-private-cors.json
deploy/cloudflare/glasspos-private-cors-dashboard.json
```

Wrangler/dashboard policy:

```json
[
  {
    "AllowedOrigins": [
      "https://arbiconbengkel.my.id",
      "http://127.0.0.1:8000",
      "http://localhost:8000"
    ],
    "AllowedMethods": ["PUT"],
    "AllowedHeaders": ["Content-Type"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 900
  }
]
```

Wrangler `4.129.0` applied the tracked policy to the real `glasspos-private` bucket. Post-apply list proof is:

```text
allowed_origins:  https://arbiconbengkel.my.id, http://127.0.0.1:8000, http://localhost:8000
allowed_methods:  PUT
allowed_headers:  Content-Type
exposed_headers:  ETag
max_age_seconds:  900
```

Real production-origin presigned preflight proof:

```text
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
Vary: Origin
Access-Control-Allow-Headers: content-type
Access-Control-Allow-Methods: PUT
```

This keeps the production origin and adds only the two origins used by normal local `php artisan serve` operation. The private bucket remains non-public, has no wildcard origin, and permits neither browser GET nor arbitrary request headers.

## Real direct PUT infrastructure proof - PROVEN

A fresh short-lived presigned URL was generated through the Laravel direct-upload port. An external HTTP client then sent the file bytes directly to `glasspos-private` with the production Origin header.

PUT response:

```text
HTTP/1.1 200 OK
Access-Control-Allow-Origin: https://arbiconbengkel.my.id
ETag: "879442678317eb3d7ba745bfb20d8ef1"
Vary: Origin
```

Laravel verification through `r2_private`:

```text
storage_path = supplier-payment-proofs/direct-proof/15f79d37002e001a57db8ebb099c40c5.txt
exists = true
content_match = true
bytes = 22
exists_after_delete = false
```

Therefore the infrastructure byte path is proven end-to-end:

```text
Laravel creates presigned authorization
Browser-style HTTP PUT -> glasspos-private
Laravel read-back verifies exact bytes
cleanup removes proof object
```

The proof object was deleted. This does not yet imply the production forms/controllers use the direct path or that the final staging/promote lifecycle is proven.

## Private direct-upload security requirements

The final direct-upload implementation must preserve all of the following:

- Laravel owns every staging and final object key; client cannot submit an arbitrary storage path
- short-lived signed upload authorization
- current max-file-count and file-size rules unless requirements change explicitly
- allowed media types remain constrained
- client MIME and size are not final proof
- finalize verifies the real R2 object before DB mutation
- upload intent is bound to actor and business scope
- replay/idempotency is handled deliberately
- orphan/staging cleanup exists
- no long DB transaction remains open while the browser uploads or while R2 verification/promotion runs
- private bucket CORS is origin/method/header constrained and is not the public wildcard policy
- browser-managed Host/Content-Length headers are not emitted as JavaScript-managed upload headers
- a browser upload URL must not remain capable of overwriting finalized durable evidence

## Direct-upload prepare/finalize application contract - LOCKED DESIGN

### Purpose

Replace both supplier-proof PHP multipart paths with an application-level prepare/finalize protocol while preserving procurement domain and audit contracts.

The browser uploads only to private R2 staging objects. Final durable object keys are never writable through the browser presigned URL.

### Scope model

Every upload intent is bound to exactly one authenticated actor and one business scope.

Allowed scope types:

```text
supplier_payment
supplier_invoice
```

Scope meaning:

```text
supplier_payment
  scope_id = existing supplier_payment_id

supplier_invoice
  scope_id = existing supplier_invoice_id
  reserved_supplier_payment_id = server-generated UUID allocated during prepare
```

For `supplier_invoice`, reserving the supplier payment ID during prepare does not itself create a payment or financial mutation. The reserved ID becomes the real supplier payment ID only after finalize revalidates the invoice and commits the payment transaction.

### Authorization boundary

Prepare and finalize remain behind the authenticated admin procurement boundary. The application use case receives the authenticated actor ID explicitly.

An intent must never be usable by another actor or another business scope. Possession of an intent ID alone is not authorization.

### File contract

One intent contains:

```text
minimum files: 1
maximum files: 3
maximum size per file: 10 MiB / 10,485,760 bytes
```

Allowed final MIME types:

```text
image/jpeg
image/png
image/webp
image/heic
image/heif
application/pdf
```

Client filename, client MIME, and client size are declaration metadata only. They are validated during prepare to fail early, but they are not sufficient proof for finalize.

### Upload intent persistence

Use dedicated persistence rather than overloading the generic transaction `idempotency_records` table.

Working parent table:

```text
supplier_payment_proof_upload_intents
```

Required logical fields:

```text
id
actor_id
scope_type
scope_id
reserved_supplier_payment_id nullable
idempotency_key
request_hash
status
locked_at nullable
finalized_at nullable
expires_at
result_payload_json nullable
created_at
updated_at
```

Allowed lifecycle statuses:

```text
prepared
finalizing
finalized
expired
```

Required uniqueness:

```text
actor_id + scope_type + scope_id + idempotency_key
```

Working child table:

```text
supplier_payment_proof_upload_intent_files
```

Required logical fields:

```text
id
upload_intent_id
ordinal
staging_path
final_storage_path nullable until verified/promoted
original_filename
declared_mime_type
declared_size_bytes
verified_mime_type nullable
verified_size_bytes nullable
created_at
updated_at
```

Required uniqueness includes:

```text
upload_intent_id + ordinal
staging_path
final_storage_path when populated
```

Do not store presigned URLs in the database.

### Idempotency and replay contract

Prepare requires an explicit idempotency key.

Canonical request hash includes at minimum:

```text
scope_type
scope_id
ordered file declarations:
  original_filename
  normalized declared MIME
  declared size
```

Behavior:

```text
same actor + scope + key + same hash + prepared
  -> reuse the same intent and persisted staging paths
  -> presign those same staging paths again when still eligible

same actor + scope + key + different hash
  -> reject as idempotency payload conflict

same actor + scope + key + finalized
  -> return the stored successful result
  -> never create another payment or attachment set

different actor or different scope
  -> must not resolve/use the intent
```

Finalize replay after a successful finalize returns the stored result and does not repeat payment, attachment, projection, or audit mutations.

### Staging object contract

Browser PUT URLs target staging objects only.

Working prefix:

```text
supplier-payment-proof-uploads/{upload-intent-id}/{opaque-file-id}
```

Laravel owns every staging path. The client never submits an arbitrary storage path.

The final durable filename extension must not be trusted from the original client filename.

### Prepare flow

Prepare performs:

```text
1. authenticate actor at HTTP boundary
2. validate scope type/id
3. validate 1..3 file declarations
4. validate declared size <= 10 MiB/file
5. validate declared MIME against the allowed set
6. perform read-only business preflight
7. find/create actor+scope-bound upload intent idempotently
8. reserve supplier_payment_id when scope is supplier_invoice
9. allocate opaque staging paths
10. persist intent + file declaration metadata
11. leave the DB transaction
12. issue short-lived presigned PUT URLs for the persisted staging paths
```

No database transaction remains open while the browser uploads.

A presign failure does not create a financial mutation. An existing prepared intent may be retried/re-presigned according to its expiry policy.

### Invoice prepare rule

`SupplierInvoicePaymentProofPreflight::prepare()` currently uses a `getByIdForUpdate()` reader and therefore belongs to the final mutation boundary, not to the browser upload interval.

Prepare needs a non-locking eligibility check. Finalize must re-run authoritative invoice validation while holding the appropriate short transaction/lock before recording the payment.

Prepare success never guarantees finalize success because business state may change during the browser upload interval.

### Finalize claim

Finalize must claim the intent using an atomic status transition:

```text
prepared -> finalizing
```

Only one caller may acquire that transition.

Behavior:

```text
finalized
  -> replay stored successful result

finalizing
  -> do not execute a second finalize concurrently

expired
  -> reject

prepared
  -> one caller atomically acquires finalizing
```

The application must not keep a database transaction open while it performs R2 verification or object promotion.

Crash/stale-finalizing recovery and expired-intent cleanup must be explicit before production cleanup is considered complete.

### Real-object verification

For every staging object finalize must verify the actual private R2 object.

Required checks:

```text
object exists
storage path exactly matches persisted intent metadata
actual byte size > 0
actual byte size <= 10 MiB
actual byte size equals the prepared declaration
actual content MIME is detected server-side
detected MIME belongs to the allowed final set
all files belonging to the intent are present
file count remains 1..3
```

`Content-Type` supplied during presigned PUT is client-controlled metadata and is not final MIME proof.

Server-side MIME verification must inspect the actual object content through a bounded temporary verification path or stream. Temporary verification bytes are not durable media and must not be written under application public/media storage.

HEIC/HEIF MIME detection behavior must be characterized with real fixtures before that verifier is considered proven.

### Final object promotion

A verified staging object is promoted to a new opaque durable key.

Final prefix remains:

```text
supplier-payment-proofs/{supplier-payment-id}/{opaque-final-filename}
```

The final filename extension is derived from the server-verified MIME mapping, not from the client-provided original filename.

Browser presigned URLs never target final durable paths.

Promotion must occur inside private R2 without routing the upload body through PHP. The selected R2/Flysystem promotion implementation requires proof that the operation uses server-side object copy/move semantics rather than an application download-and-reupload byte path.

After promotion, Laravel verifies the final object exists and matches the verified byte size before DB mutation.

### Finalize business transaction

Only after all files are verified and promoted may Laravel enter the short business transaction.

Inside that transaction:

```text
1. lock/re-read the upload intent
2. confirm actor, scope, idempotency state and finalizing ownership
3. revalidate the real procurement business state
4. for supplier_invoice:
     create SupplierPayment using reserved_supplier_payment_id
5. build SupplierPaymentProofAttachment records from verified FINAL metadata
6. persist attachments
7. update proof status
8. write existing audit event
9. synchronize invoice projection
10. mark upload intent finalized
11. persist replay result
12. commit
```

No browser upload occurs inside this transaction. No client-declared MIME or size is written as final attachment truth when it disagrees with server verification.

### Finalize failure behavior

Before DB commit:

```text
verification failure
  -> no payment/attachment/audit mutation

promotion failure
  -> no payment/attachment/audit mutation

business revalidation failure
  -> no payment/attachment/audit mutation
```

Any promoted final objects created by a finalize attempt that cannot commit must be cleaned safely or recorded for deterministic cleanup.

A retryable infrastructure failure must not silently become a successful idempotency replay.

### Finalization output

Successful finalize returns/stores enough result data to replay the existing user-facing outcome without executing the mutation again.

For invoice scope this includes at minimum:

```text
supplier_invoice_id
supplier_payment_id
proof_status
attachment_count
```

For existing payment scope this includes at minimum:

```text
supplier_payment_id
supplier_invoice_id
proof_status
attachment_count
```

Presigned URLs, R2 credentials, internal exception messages, and sensitive storage implementation details must not be stored in the replay payload.

### Local controller/UI result

The two existing multipart controllers remain as local fallback code:

```text
UploadSupplierInvoicePaymentProofController
AttachSupplierPaymentProofController
```

Their old POST routes are no longer active, and both supplier-proof forms have been cut locally from:

```text
Browser -> PHP multipart -> Laravel -> R2
```

to:

```text
Browser -> Laravel prepare
Browser -> private R2 staging PUT
Browser -> Laravel finalize
```

### Implementation/proof order

```text
1. lock this detailed blueprint
2. RED tests for prepare/finalize application contract
3. upload-intent persistence migration + ports/adapters
4. staging presign adaptation
5. real-object verifier
6. server-side final-object promotion
7. prepare use case
8. finalize use case
9. focused application/infrastructure GREEN proof
10. HTTP routes/controllers contract
11. browser JavaScript direct upload
12. cut both old PHP multipart paths
13. orphan/expired staging cleanup proof
14. browser/real-R2 end-to-end proof
```

Steps 1-14 are complete in the isolated local/test environment. The browser proof used the configured real private R2 bucket and the production HTTPS origin mapped to the local Laravel runtime, so the real private-bucket CORS policy remained active. This does not authorize or prove a production deployment.

### Web-runtime stale configuration failure - ROOT CAUSE PROVEN

The reported prepare failure was reproduced through the real HTTP kernel with the same observable state: HTTP `422`, safe generic `PRESIGN_FAILED`, and a persisted `prepared` invoice-scope intent plus child file row.

The exact server-side exception was:

```text
InvalidArgumentException: Disk [r2_private] does not have a configured driver.
```

The failing web process was loading a stale Laravel configuration cache that predated the `r2_private` disk. A fresh CLI process loaded the current filesystem configuration, which explains why direct CLI presigning, port resolution, intent hydration, and `SupplierPaymentProofPrepareResponse::make()` all passed while the browser request failed.

The deployment lifecycle made that split possible: the prior maintenance entry booted Laravel before calling `optimize:clear`; extracting a new package also did not remove an old deployed `bootstrap/cache/config.php`; and a reused `clear.php` path could itself remain in web OPcache. The presign adapter resolved `Storage::disk('r2_private')` outside its prior exception boundary, while the prepare handler converted the resulting throwable into a generic response without a durable diagnostic.

The local fix now:

- records prepare-stage exceptions server-side with safe stage/runtime/config-presence context;
- keeps the browser response generic and removes URL, signature, token, credential, and secret-like values from logged exception messages;
- resolves the real storage disk inside the observable presign boundary;
- emits a unique token-derived maintenance filename per package;
- removes disposable Laravel PHP caches before framework bootstrap;
- resets enabled web OPcache in phase one and boots Laravel only on the redirected phase-two request;
- resolves `r2_private` and creates a staging-only PUT presign in that web runtime before migration/optimization can be reported successful;
- keeps the maintenance endpoint token-gated, one-time/self-deleting, and free of raw internal exception output.

### Manual local browser CORS failure - ROOT CAUSE PROVEN

The later manual operator symptom had a separate boundary and was reproduced without reopening the already-proven Laravel prepare path:

```text
normal local browser origin: http://localhost:8000 or http://127.0.0.1:8000
prepare:                      HTTP 200
private R2 CORS before fix:   https://arbiconbengkel.my.id only
browser PUT result:           CORS/network rejection
finalize:                     not requested
Laravel exception log:        none, as expected
```

The prior production-origin Chromium proof passed because it deliberately mapped `https://arbiconbengkel.my.id` to an isolated local Laravel runtime. It did not prove normal localhost operator ergonomics.

The root fix has two parts:

- real private-bucket CORS now permits the production origin plus exact local port-8000 origins, while remaining PUT-only and `Content-Type`-only;
- the browser upload boundary maps same-origin application network failure, R2 network/CORS rejection, R2 non-2xx, malformed prepare output, finalize failure, and unknown exceptions to centralized trusted Bahasa Indonesia messages.

Native `Error`, `TypeError`, and `DOMException` messages are never rendered. Only explicitly allowlisted backend public messages may cross the UI boundary. Runtime proof also found and fixed a response-shape edge case: an empty PHP header map can encode as JSON `[]`; the browser accepts only that empty list or a real header object and normalizes the empty list to `{}`.

#### Server-side configured-R2 smoke

```text
RUN_REAL_R2_SUPPLIER_PROOF_SMOKE=1 make smoke-r2-supplier-payment-proof
1 passed (24 assertions)
```

This proves the configured adapter/disk, HTTP prepare/finalize handlers, real staging PUT, trusted verification, CopyObject promotion, DB mutation, and cleanup. It does not prove browser CORS by itself.

#### Normal local operator browser proof

Real Chromium drove both UI scopes through a normal `php artisan serve` runtime at `http://localhost:8000`:

```text
supplier_invoice: prepare JSON 243 bytes -> staging PUT -> finalize JSON 2 bytes -> PASS
supplier_payment: prepare JSON 246 bytes -> staging PUT -> finalize JSON 2 bytes -> PASS
```

The already-running manual owner runtime at `http://127.0.0.1:8000` independently passed the invoice flow with prepare JSON 243 bytes, one staging PUT, and finalize `{}`. No TLS-origin impersonation was used for either local-origin proof.

#### Approved production-origin browser proof

The production origin remains allowed. Real Chromium using `https://arbiconbengkel.my.id` against an isolated local Laravel runtime passed:

```text
prepare JSON 248 bytes -> staging PUT -> finalize {} -> PASS
```

#### Rejected-origin browser/CORS proof

Real Chromium from the explicitly disallowed `http://127.0.0.1:8001` origin produced:

```text
prepare:                  HTTP 200
CORS result:              PreflightMissingAllowOriginHeader
finalize request count:   0
payment/attachment/audit: 0 / 0 / 0
UI message:               Penyimpanan privat tidak dapat dihubungi. Periksa koneksi lalu coba lagi.
native Failed to fetch:   not exposed
```

For every successful browser proof, application requests contained JSON metadata only and did not contain the PDF marker. The PDF was sent only in the cross-origin `PUT` to `supplier-payment-proof-uploads/**`; no browser-writable final path appeared. Browser/DB/object fixtures were removed after proof.

## Local Laravel storage

The framework-local disks and `public/storage` mapping are not removed yet. Legacy-data checks remain mandatory. Framework logs, cache, sessions, and temporary files are outside this migration.

## Migration rules

1. Never commit R2 credentials.
2. Public and private data use separate buckets.
3. Prefer least-privilege bucket-scoped credentials.
4. Store object keys in the database, not hard-coded R2 URLs.
5. Keep controllers/domain/application storage-provider agnostic.
6. Cloudflare/S3 details stay in configuration and outbound adapters.
7. Preserve the `assets/**` relative tree during static migration.
8. Keep `service-worker.js` and `manifest.webmanifest` same-origin.
9. Do not delete local static/media copies before browser regression, parity, rollback, and production proof.
10. Private object access remains authorization-controlled.
11. Final private uploads send bytes browser -> R2, not browser -> PHP -> R2.
12. Finalize must verify the real object; client metadata is insufficient.
13. The public wildcard CORS policy must never be copied to `glasspos-private`.
14. Do not broaden application object credentials merely for bucket management operations.
15. No application DB transaction may stay open across the browser-to-R2 upload interval.
16. Browser upload URLs target staging objects, not finalized durable evidence.

## Completed

- Cloudflare authoritative DNS established for the domain.
- Created and proved `glasspos-media` public bucket/custom domain.
- Created and proved `glasspos-private` private bucket.
- Added explicit `r2_public` and `r2_private` Laravel disks.
- Routed supplier payment proof durable storage to `r2_private`.
- Added and hardened repeatable/resumable static uploader.
- Inventoried 6,224 static files / 54.11 MB payload.
- Completed public R2 static parity at 6,224/6,224 with zero final failures.
- Proved representative CSS/JS/SVG/vendor/font delivery through the CDN.
- Applied and proved public read-only font CORS.
- Added and locally proved application-wide CDN asset-root behavior while protecting manifest/service-worker origin.
- Converted PWA/error/push static dependencies away from local-only asset assumptions.
- Proved focused CDN contract test at 6 tests / 25 assertions.
- Proved browser/PWA/CDN smoke for the public CDN cutover.
- Proved public bucket contains zero objects under `supplier-payment-proofs/**`.
- Closed repository quality gate at 1,505 tests / 9,415 assertions plus PHPStan and contract audits.
- Added private direct-upload port/adapter foundation and proved 8 strict tests / 35 assertions.
- Applied strict private bucket CORS and proved a real presigned OPTIONS preflight.
- Proved a real browser-style presigned PUT to `glasspos-private`, exact Laravel read-back, and deletion cleanup.
- Locked the detailed actor/scope-bound prepare/finalize design with staging/final object separation and replay-safe finalize semantics.
- Implemented the real-object verifier with bounded reads, actual-size checks, server-side MIME detection, and HEIC/HEIF characterization.
- Implemented server-side staging-to-final promotion with opaque filenames and extensions derived only from verified MIME.
- Implemented actor/scope/idempotency-bound prepare and atomic finalize handlers for both existing-payment and invoice scopes.
- Revalidated invoice business state inside the short finalize transaction after all object I/O completed.
- Cut both supplier-proof UI flows from PHP multipart to browser PUT against staging-only presigned URLs.
- Added prepare/finalize HTTP endpoints with generic infrastructure-failure responses and no internal exception leakage.
- Added deterministic promotion-failure cleanup plus leased/retryable cleanup for expired and stale-finalizing intents.
- Proved the local flow through focused application, HTTP, UI, MIME, cleanup, replay, and regression tests.
- Closed PHPStan, line-count, Blade contract, and hexagonal architecture gates without suppressions.
- Reproduced the web-versus-CLI prepare failure and proved stale deployed Laravel configuration as its exact root cause.
- Added redacted server-side failure reporting and a two-phase cPanel cache/OPcache maintenance lifecycle that validates private-R2 presigning in the web runtime.
- Added an explicit opt-in real-R2 smoke gate; it exercises prepare, real PUT, verification, CopyObject promotion, finalize, and cleanup through the configured adapters.
- Drove both supplier-proof forms in real Chromium through `prepare -> private R2 staging PUT -> finalize` with the production HTTPS origin/CORS policy and proved final attachment rendering.
- Applied and listed the strict private-bucket CORS policy with exact production, `127.0.0.1:8000`, and `localhost:8000` origins through Wrangler `4.129.0`.
- Proved both local operator origins without TLS impersonation, retained the approved production-origin flow, and proved an unlisted origin is blocked before finalize with a localized UI failure.
- Replaced raw browser exception rendering with a centralized trusted Bahasa Indonesia direct-upload error boundary and adversarial contract tests.
- Closed the current repository gate at 1,550 tests / 9,797 assertions, with focused supplier-proof proof at 68 tests / 481 assertions and real-R2 smoke at 1 test / 24 assertions.

## Remaining work in order

1. Inventory legacy supplier-proof production DB rows/local files under separate authorization.
2. Migrate legacy objects to `glasspos-private` and prove DB/object parity plus rollback.
3. Continue the audit for any other durable runtime-media families.
4. Deploy the completed static/runtime changes to production, run the unique two-phase maintenance entry, and prove its web-runtime private-R2 readiness check.
5. Run production browser/read/delete verification and prove cPanel no longer receives private upload bodies.
6. Remove obsolete local media/static copies only after rollback/parity/production proof.
