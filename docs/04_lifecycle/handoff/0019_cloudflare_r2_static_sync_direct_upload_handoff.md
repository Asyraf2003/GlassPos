# Cloudflare R2 Static Sync and Direct Upload Handoff

## Metadata
- Date: 2026-09-03
- Slice / topic: Cloudflare R2 public static offload + private supplier-payment-proof migration
- Workflow step: public CDN lane verified; repository verification green; strict private direct-upload adapter suite passed; private R2 CORS is next
- Status: active / in progress

## Goal

Final target:

```text
public static
Browser -> media.arbiconbengkel.my.id -> glasspos-media

private supplier proof
Browser -> Laravel prepare/authorize
Browser -> glasspos-private direct PUT
Browser -> Laravel finalize
Laravel -> verify real object -> DB/audit mutation
```

Private media bytes must not transit cPanel/PHP in the final architecture.

## Locked Facts

- Cloudflare is authoritative for `arbiconbengkel.my.id`.
- Public bucket: `glasspos-media` with custom domain `https://media.arbiconbengkel.my.id`.
- Private bucket: `glasspos-private`; no public domain/development URL.
- Laravel disks: `r2_public` and `r2_private`.
- Real private R2 write/read/delete proof passed.
- Real public R2 write/custom-domain delivery proof passed.
- Public static inventory: 6,224 files / 56,737,036 bytes / 54.11 MB.
- Static R2 parity: 6,224/6,224 paths with zero final failures.
- Public font CORS is configured and proven.
- Laravel asset-root CDN cutover is locally proven.
- `manifest.webmanifest` and `service-worker.js` remain application-origin.
- PWA icons, service-worker fallback icons, error-page assets, and outbound push asset paths no longer depend on local-only public assets.
- Public privacy gate passed: `glasspos-media/supplier-payment-proofs/**` count is `0`.
- Supplier-payment-proof durable storage adapter uses `r2_private`.
- Existing web upload controllers still receive PHP multipart bytes before forwarding to private R2.
- Direct-upload port/adapter foundation exists but is not wired to production controllers/UI yet.
- Local `public/assets` remains until browser/PWA regression, rollback, production proof, and cleanup.

## Public Static Proof

```text
files: 6224
bytes: 56737036 (54.11 MB)
existing objects: 6028
processed: 6224/6224
uploaded: 196
skipped existing: 6028
failed: 0
elapsed: 1m 09s
```

Representative CSS/JS/SVG/vendor/font assets returned HTTP 200 from the custom domain.

Public font CORS final proof:

```text
HTTP/2 200
content-type: font/woff2
vary: Origin
access-control-expose-headers: ETag
access-control-allow-origin: *
cache-control: public, max-age=86400
```

Public private-data gate:

```text
Storage::disk('r2_public')->allFiles('supplier-payment-proofs')
count = 0
```

## Application CDN Cutover - PASS

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

Focused CDN contract:

```text
6 passed / 25 assertions
```

## Repository Quality Gate - PASS

Migration follow-ups found by verification were fixed:

- PHPStan concrete-filesystem typing for `temporaryUploadUrl()`;
- oversized `UploadPublicAssetsToR2` split into small support classes under `app/Console/Support/PublicAssets/`;
- `make verify` now uses Pest `--compact` while preserving the Pest runner;
- supplier-proof feature fixtures changed from fake `local` storage to fake `r2_private` so tests match the real durable-storage contract.

Targeted supplier-proof regression:

```text
24 passed / 149 assertions
```

Final operator `make verify` proof:

```text
PHPStan: PASS
line-count audit: PASS
Blade audit: PASS
Contract audit: PASS
Tests: 1505 passed (9415 assertions)
Duration: 58.89s
```

## Private Direct Upload Foundation - STRICT TEST PASS

Foundation:

```text
App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort
App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter
```

Current adapter behavior:

- targets `r2_private`;
- Laravel owns generated object keys;
- opaque stored filenames;
- TTL clamped to 60..3600 seconds, default 900;
- required `Content-Type` propagated into signing options;
- invalid metadata/path input fails closed;
- signing exceptions fail closed.

Operator proof:

```text
Tests: 8 passed (35 assertions)
Duration: 0.20s
```

This closes the adapter/unit-contract gate. It does **not** prove browser CORS, a real presigned PUT, finalize verification, or production controller cutover.

## Private CORS Policy Prepared

Tracked strict policies now exist for `glasspos-private`:

```text
deploy/cloudflare/glasspos-private-cors.json
deploy/cloudflare/glasspos-private-cors-dashboard.json
```

Dashboard policy intent:

```json
[
  {
    "AllowedOrigins": ["https://arbiconbengkel.my.id"],
    "AllowedMethods": ["PUT"],
    "AllowedHeaders": ["Content-Type"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 900
  }
]
```

This policy is intentionally narrower than the public bucket policy. Do not use `*` for the private bucket.

## Current Migration State

Already changed in the real migration:

```text
public static bytes        -> R2 public          DONE
Laravel static URLs        -> public CDN         DONE locally
supplier proof durable I/O -> R2 private         DONE in application adapter
public/private bucket gate -> separated          PROVEN
```

Still unchanged / pending:

```text
browser upload path
Browser -> PHP multipart -> Laravel -> R2 private
```

Therefore the most important remaining architectural change is removing PHP/cPanel from the private upload byte path.

Legacy supplier-proof rows/files have not yet been inventoried/migrated. Production cutover and deletion of local fallback assets/media have not happened.

## Locked Decisions

- Public/private data use separate buckets.
- Database stores object keys, not hard-coded R2 URLs.
- Business/application code remains provider-agnostic where possible.
- `manifest.webmanifest` and `service-worker.js` stay same-origin.
- Final private uploads send bytes browser -> R2 directly.
- Client MIME/size is not final verification.
- Finalize must verify the actual R2 object before DB/audit mutation.
- Upload intent must be actor/business-scope bound and replay/idempotency aware.
- Private CORS must be origin/method/header constrained, never public wildcard.
- Do not delete local rollback copies before browser, parity, rollback, and production proof.

## Remaining Work in Order

1. Apply strict CORS to `glasspos-private` through the Cloudflare dashboard.
2. Prove private CORS/preflight behavior and a real short-lived presigned PUT against `glasspos-private`.
3. Run browser UI/PWA/font/icon smoke for the public CDN cutover while local `public/assets` remains rollback.
4. Add prepare/finalize use cases with actor/business-scope-bound upload intent and real-object verification.
5. Cut both supplier-proof web flows from PHP multipart to direct browser -> R2.
6. Inventory/migrate legacy supplier-proof DB rows/local files and prove DB/object parity.
7. Continue audit for any other durable runtime-media families.
8. Verify Composer manifest/lock coherence for `league/flysystem-aws-s3-v3`.
9. Deploy to production and prove cPanel no longer owns durable media or private upload bytes.
10. Remove obsolete local media/static copies only after rollback/parity/production proof.

## Exact Next Operator Step

Cloudflare Dashboard:

```text
R2 -> glasspos-private -> Settings -> CORS Policy -> Add/Edit
```

Paste the contents of:

```text
deploy/cloudflare/glasspos-private-cors-dashboard.json
```

Do not modify `glasspos-media` during this step.
