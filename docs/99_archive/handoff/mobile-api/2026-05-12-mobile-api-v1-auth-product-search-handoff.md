# HyperPOS Mobile API v1 - Auth and Product Search Handoff

Date: 2026-05-12  
Latest proven branch: main  
Latest proven HEAD: 6940ae63  
Latest proven commit label: commit 1858  
Latest proven remote state: HEAD, origin/main, and origin/HEAD aligned in user command output.

## Final Goal

Build a small Android mobile companion app backed by Laravel API.

The mobile app is not a full POS replacement.

Scope:

1. Login.
2. Cashier:
   - search product
   - view stock
   - view selling price
3. Admin:
   - search supplier invoices
   - filter supplier invoices by paid/unpaid/partial status
   - view supplier invoice detail
   - upload/view supplier payment proof photo/file
   - due invoice list first, notification later

## Workspace Layout

Locked workspace layout:

- Laravel API/domain source: /home/asyraf/Code/laravel/bengkel2/app
- Kotlin Android app source: /home/asyraf/Code/laravel/bengkel2/kotlin

Rules:

- Android/Kotlin project files must not be created inside the Laravel app repo.
- Laravel API work is executed from /home/asyraf/Code/laravel/bengkel2/app.
- Kotlin Android work is executed from /home/asyraf/Code/laravel/bengkel2/kotlin.
- Laravel remains source of truth for auth, role, products, stock, price, supplier invoice, proof attachment, audit, and permission.
- Kotlin remains client only.

## Locked Decisions

- Mobile API base path: /api/v1
- API transport adapter is custom from zero.
- Do not expose Blade/web controllers directly as mobile API.
- Auth strategy: raw custom bearer token, not Sanctum/JWT/session cookie.
- Token persistence: database stores token_hash only, never plaintext token.
- Plain token is returned only once from login response.
- Token revocation is current-token logout only for v1.
- Role source remains existing Laravel identity access source.
- Login identifier uses existing Laravel login field: email.
- Product search v1 is cashier-only.
- Admin mobile product search is rejected in v1.
- Product search must show zero-stock products with available_stock = 0.
- Product payload includes split fields, not label-only:
  - kode_barang
  - nama_barang
  - merek
  - ukuran
- Kotlin UI decision: XML/ViewBinding.
- Kotlin HTTP client decision: OkHttp only.
- Kotlin token storage decision: custom encrypted storage from v1.
- First production install: manual signed APK via USB/file.
- User handles git commit/push manually.

## Rules Followed

Relevant project rules already read in this session:

- docs/01_standards/10_CORE/10_SCOPE_AND_FACTS.md
- docs/01_standards/10_CORE/11_BLUEPRINT_FIRST.md
- docs/01_standards/10_CORE/12_STEP_BY_STEP_EXECUTION.md
- docs/01_standards/10_CORE/13_PROOF_AND_PROGRESS.md
- docs/01_standards/20_WORKFLOW/20_RESPONSE_STRUCTURE.md
- docs/01_standards/20_WORKFLOW/21_ACTIVE_STEP_POLICY.md
- docs/01_standards/20_WORKFLOW/22_OPTION_EVALUATION.md
- docs/01_standards/30_OUTPUT/31_MARKDOWN_OUTPUT_RULE.md
- docs/01_standards/30_OUTPUT/33_TERMINAL_COMMAND_DELIVERY.md
- docs/01_standards/40_ARCHITECTURE/40_HEXAGONAL_BASELINE.md
- docs/01_standards/40_ARCHITECTURE/41_PUBLIC_CONTRACTS.md
- docs/01_standards/40_ARCHITECTURE/42_ERROR_HANDLING_AND_REDACTION.md
- docs/01_standards/40_ARCHITECTURE/44_AUDIT_AND_DOD.md
- docs/01_standards/60_STACK/60_LARAVEL_RULES.md
- docs/03_blueprints/mobile-api-v1.md

## Completed Work

### 1. Android/Linux Setup Verification

Verified on user machine:

- Java available.
- JDK 21 available.
- Gradle available.
- ADB available.
- Android SDK installed at /opt/android-sdk.
- SDK packages installed:
  - build-tools;35.0.0
  - cmdline-tools;latest
  - platform-tools
  - platforms;android-35
- Redmi 12 detected via ADB.
- Redmi 12 device facts:
  - manufacturer: Xiaomi
  - model: 23053RN02A
  - device: fire
  - Android release: 15
  - SDK: 35
  - ABI: arm64-v8a

### 2. Mobile API Auth Foundation

Implemented and locally verified:

- POST /api/v1/auth/login
- GET /api/v1/me
- POST /api/v1/auth/logout
- mobile_api_tokens table
- raw custom bearer token issue/verify/revoke
- token_hash persistence only
- redacted JSON auth errors
- role resolution through existing identity access source
- Mobile API auth middleware

Source areas added/changed:

- routes/api.php
- bootstrap/app.php
- app/Providers/HexagonalServiceProvider.php
- database/migrations/2026_05_11_000100_create_mobile_api_tokens_table.php
- app/Adapters/In/Http/Controllers/Api/V1/Auth
- app/Adapters/In/Http/Middleware/MobileApi
- app/Adapters/In/Http/Requests/Api/V1/Auth
- app/Adapters/Out/MobileApi
- app/Application/MobileApi/Auth
- app/Ports/Out/MobileApi
- tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php

Verified behavior:

- admin mobile login returns bearer token payload
- kasir mobile login returns bearer token payload
- invalid login rejected with safe JSON
- user without actor access rejected
- /api/v1/me requires bearer token
- /api/v1/me returns current actor for valid token
- logout revokes current token
- revoked token rejected after logout

### 3. Mobile API Product Search

Implemented and locally verified:

- GET /api/v1/products/search
- bearer token required
- cashier-only access
- admin rejected with CASHIER_ONLY
- short query returns empty rows
- product search returns:
  - id
  - label
  - kode_barang
  - nama_barang
  - merek
  - ukuran
  - available_stock
  - default_unit_price_rupiah
  - minimum_unit_price_rupiah
- zero-stock product is included with available_stock = 0
- response meta includes query and limit

Source areas added/changed:

- routes/api.php
- app/Adapters/In/Http/Controllers/Api/V1/Product/SearchMobileApiProductsController.php
- app/Application/MobileApi/Product/UseCases/SearchMobileApiProductsHandler.php
- tests/Feature/MobileApi/Product/MobileApiProductSearchFeatureTest.php

Implementation note:

- Product search reuses existing CashierNoteProductLookupData.
- It does not expose or reuse the web ProductLookupController directly.
- It intentionally does not copy the web behavior that skips zero-stock products.
- Current result limit is applied in application layer with array_slice at 20 rows.
- Future improvement: query-level limit in ProductReaderPort or dedicated mobile product reader for large catalogs.

## Latest Verification Proof

Latest user-provided focused verification output at HEAD 6940ae63:

Route proof:

- POST api/v1/auth/login
- POST api/v1/auth/logout
- GET|HEAD api/v1/me
- GET|HEAD api/v1/products/search

Targeted Mobile API auth + product proof:

- Tests: 11 passed
- Assertions: 36

Focused web product/inventory/database blast-radius proof:

- Tests: 6 passed
- Assertions: 30

Whitespace proof:

- git diff --check produced no output.

Source anchor proof showed:

- routes/api.php has /products/search route.
- SearchMobileApiProductsController returns CASHIER_ONLY for non-kasir role.
- SearchMobileApiProductsHandler returns split fields and available_stock.
- Tests assert zero-stock product appears with available_stock = 0.

Latest final git status from user output:

- clean working tree.

## Known Gaps

- Full global Laravel suite was not run in this handoff.
- Browser/manual QA was not run.
- Blueprint proof log may need inspection to confirm product search proof was recorded after commit 1858.
- Supplier invoice search/detail API is not implemented yet.
- Supplier payment proof upload/view API is not implemented yet.
- Due invoice list API is not implemented yet.
- Kotlin Android project is not created yet.
- Android encrypted token storage is not implemented yet.
- No APK build/install proof yet.
- No API sanity curl against a running local server yet.
- Product search uses application-layer array_slice limit; not query-level limit yet.

## Current Safe Next Step

Start next session by verifying repo state and reading this handoff plus the mobile blueprint.

Recommended first command:

pwd
git status --short
git branch --show-current
git log -1 --oneline
sed -n '1,260p' docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-auth-product-search-handoff.md
grep -n "Mobile API Auth Foundation\|products/search\|Product search\|Implementation Proof Log\|Next Step" docs/03_blueprints/mobile-api-v1.md | sed -n '1,220p'
php artisan route:list --path=api/v1

Then choose one active step only:

Option A, documentation cleanup:
- If docs/03_blueprints/mobile-api-v1.md does not yet record product search implementation proof, update blueprint proof log first.

Option B, next feature:
- Implement admin supplier invoice search/detail API.

Option C, Kotlin start:
- If blueprint explicitly allows after product search proof, create Kotlin Android skeleton in /home/asyraf/Code/laravel/bengkel2/kotlin only.

Recommended safest next step:
- Inspect blueprint product search proof first.
- If missing, update docs.
- Then start Kotlin skeleton or supplier invoice API based on owner priority.

## Session Opening Prompt for Next Chat

Continue HyperPOS Mobile API v1 + Kotlin Android companion app.

Repo/workspace:
- Laravel app repo: /home/asyraf/Code/laravel/bengkel2/app
- Kotlin app path: /home/asyraf/Code/laravel/bengkel2/kotlin
- Branch usually main
- User handles git commit/push manually
- Do not create Android files inside /app

Read first:
- docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-auth-product-search-handoff.md
- docs/03_blueprints/mobile-api-v1.md
- relevant rules under docs/01_standards

Latest proven state from prior session:
- HEAD 6940ae63 commit 1858, origin aligned, clean working tree
- Mobile API auth foundation implemented and verified
- Mobile API product search implemented and verified
- Routes proven:
  - POST /api/v1/auth/login
  - POST /api/v1/auth/logout
  - GET /api/v1/me
  - GET /api/v1/products/search
- Targeted Mobile API auth + product tests: 11 passed / 36 assertions
- Focused product/inventory/database blast-radius: 6 passed / 30 assertions
- git diff --check no output

Locked decisions:
- raw custom bearer token, not Sanctum/JWT/session cookie
- token_hash stored in DB, plaintext token returned once
- product search cashier-only
- zero-stock products shown
- Kotlin path is /home/asyraf/Code/laravel/bengkel2/kotlin
- Kotlin uses XML/ViewBinding and OkHttp only for v1

Next safest step:
- Verify current repo state.
- Inspect whether docs/03_blueprints/mobile-api-v1.md includes product search proof.
- If missing, update blueprint proof log.
- Do not start new implementation until proof is read.
