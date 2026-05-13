# HyperPOS Mobile API v1 - Auth, Product Search, Supplier Invoice Read, Payment Proof Handoff

Date: 2026-05-12
Branch: main
Workspace:

- Laravel app repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Kotlin Android app path: `/home/asyraf/Code/laravel/bengkel2/kotlin`

User handles git commit/push manually.
Do not commit/push unless explicitly requested.
Do not create Android/Kotlin files inside `/home/asyraf/Code/laravel/bengkel2/app`.
Kotlin Android files must be created under `/home/asyraf/Code/laravel/bengkel2/kotlin`.

## Final Goal

Build a small Android mobile companion app backed by Laravel API.

The mobile app is not a full POS replacement.

Current target scope:

1. Login.
2. Cashier:
   - search product
   - view stock
   - view selling price
3. Admin:
   - search supplier invoices
   - filter supplier invoices by backend payment status
   - view supplier invoice detail
   - upload supplier payment proof photo/file
   - view supplier payment proof attachment
   - due invoice list later

Laravel remains source of truth for:

- auth
- role
- products
- stock
- price
- supplier invoices
- supplier payments
- proof attachments
- audit
- permission decisions

Kotlin Android remains client only.

## Workflow Rules

Required project rules for next session:

- Read rules/blueprint/handoff before technical work.
- Command output from user/local terminal is source of truth.
- Work step-by-step, one active step per response.
- Do not claim done/safe/tested without proof.
- If data is missing, state GAP explicitly.
- Use markdown/code fences with `~~~`, not triple backticks.
- If repo/project has rules, read rules first.
- User handles git commit/push manually.
- Do not spend effort managing git remote unless user asks.

Relevant rules to read if needed:

- `docs/01_standards/10_CORE/10_SCOPE_AND_FACTS.md`
- `docs/01_standards/10_CORE/11_BLUEPRINT_FIRST.md`
- `docs/01_standards/10_CORE/12_STEP_BY_STEP_EXECUTION.md`
- `docs/01_standards/10_CORE/13_PROOF_AND_PROGRESS.md`
- `docs/01_standards/20_WORKFLOW/20_RESPONSE_STRUCTURE.md`
- `docs/01_standards/20_WORKFLOW/21_ACTIVE_STEP_POLICY.md`
- `docs/01_standards/20_WORKFLOW/22_OPTION_EVALUATION.md`
- `docs/01_standards/30_OUTPUT/31_MARKDOWN_OUTPUT_RULE.md`
- `docs/01_standards/30_OUTPUT/33_TERMINAL_COMMAND_DELIVERY.md`
- `docs/01_standards/40_ARCHITECTURE/40_HEXAGONAL_BASELINE.md`
- `docs/01_standards/40_ARCHITECTURE/41_PUBLIC_CONTRACTS.md`
- `docs/01_standards/40_ARCHITECTURE/42_ERROR_HANDLING_AND_REDACTION.md`
- `docs/01_standards/40_ARCHITECTURE/44_AUDIT_AND_DOD.md`
- `docs/01_standards/60_STACK/60_LARAVEL_RULES.md`

Primary blueprint:

- `docs/03_blueprints/mobile-api-v1.md`

## Locked Decisions

Mobile API:

- Base path: `/api/v1`
- API transport adapter is custom from zero.
- Do not expose Blade/web controllers directly as mobile API.
- Raw custom bearer token, not Sanctum/JWT/session cookie.
- Token DB stores `token_hash` only.
- Plain token returned only once at login.
- Laravel remains source of truth for auth/role/domain/audit/security.

Product search:

- Cashier-only in v1.
- Admin mobile product search is rejected in v1.
- Zero-stock products are shown with `available_stock = 0`.
- Product payload includes split fields:
  - `kode_barang`
  - `nama_barang`
  - `merek`
  - `ukuran`

Supplier invoice read API:

- Admin-only.
- Cashier denied.
- Read-only.
- Reuses existing procurement read-side handlers/ports/adapters.
- Uses backend payment status terms.
- Current proven `payment_status` value in test: `outstanding`.

Supplier payment proof API:

- Admin-only.
- Cashier denied.
- Upload-proof-only scope.
- Does not mutate payment amount.
- Reuses existing `AttachSupplierPaymentProofHandler`.
- Reuses existing `GetSupplierPaymentProofAttachmentFileHandler`.
- Attachment response keeps safe headers, including `X-Content-Type-Options: nosniff`.

Kotlin:

- XML/ViewBinding.
- OkHttp only.
- Custom encrypted token storage from v1.
- First production install: manual signed APK via USB/file.
- Kotlin project must be outside Laravel app repo:
  - `/home/asyraf/Code/laravel/bengkel2/kotlin`

## Completed Work

### 1. Mobile API Auth Foundation

Implemented and locally verified:

- `POST /api/v1/auth/login`
- `GET /api/v1/me`
- `POST /api/v1/auth/logout`
- `mobile_api_tokens` table
- raw custom bearer token issue/verify/revoke
- token hash persistence only
- redacted JSON auth errors
- role resolution through existing identity access source
- Mobile API auth middleware

Verified behaviors:

- admin mobile login returns bearer token payload
- kasir mobile login returns bearer token payload
- invalid login rejected with safe JSON
- user without actor access rejected
- `/api/v1/me` requires bearer token
- `/api/v1/me` returns current actor for valid token
- logout revokes current token
- revoked token rejected after logout

### 2. Mobile API Cashier Product Search

Implemented and locally verified:

- `GET /api/v1/products/search`
- bearer token required
- cashier-only access
- admin rejected with `CASHIER_ONLY`
- short query returns empty rows
- product search returns:
  - `id`
  - `label`
  - `kode_barang`
  - `nama_barang`
  - `merek`
  - `ukuran`
  - `available_stock`
  - `default_unit_price_rupiah`
  - `minimum_unit_price_rupiah`
- zero-stock product included with `available_stock = 0`
- response meta includes `query` and `limit`

Implementation notes:

- Reuses existing `CashierNoteProductLookupData`.
- Does not expose or reuse web `ProductLookupController` directly.
- Does not copy web behavior that skips zero-stock products.
- Current limit is application-layer `array_slice` at 20 rows.
- Future improvement: query-level limit in `ProductReaderPort` or dedicated mobile product reader for large catalogs.

### 3. Mobile API Admin Supplier Invoice Read API

Implemented and locally verified:

- `GET /api/v1/supplier-invoices`
- `GET /api/v1/supplier-invoices/{supplierInvoiceId}`

Verified behaviors:

- list requires bearer token
- cashier mobile token rejected
- admin can read empty list using backend `payment_status=outstanding`
- detail not-found returns safe JSON with `SUPPLIER_INVOICE_NOT_FOUND`
- admin can read real supplier invoice list row from `supplier_invoice_list_projection`
- admin can read supplier invoice detail summary and current lines
- reuses existing procurement read-side handlers/ports/adapters

### 4. Mobile API Supplier Payment Proof Upload/View API

Implemented and locally verified:

- `POST /api/v1/supplier-payments/{supplierPaymentId}/proofs`
- `GET /api/v1/supplier-payment-proof-attachments/{attachmentId}`

Verified behaviors:

- upload requires bearer token
- cashier mobile token rejected from upload
- admin can upload supplier payment proof to existing supplier payment
- view requires bearer token
- cashier mobile token rejected from view
- admin can view supplier payment proof attachment with safe headers
- attachment response includes `X-Content-Type-Options: nosniff`
- upload reuses existing `AttachSupplierPaymentProofHandler`
- view reuses existing `GetSupplierPaymentProofAttachmentFileHandler`

Important bug fixed during implementation:

- `UploadMobileApiSupplierPaymentProofController` initially used `$actor->actorId`.
- Correct field is `$actor->id` based on `MobileApiActor`.
- After fix, targeted payment proof test passed.

## Latest Proven Routes

Latest route proof showed 8 `/api/v1` routes:

- `POST api/v1/auth/login`
- `POST api/v1/auth/logout`
- `GET|HEAD api/v1/me`
- `GET|HEAD api/v1/products/search`
- `GET|HEAD api/v1/supplier-invoices`
- `GET|HEAD api/v1/supplier-invoices/{supplierInvoiceId}`
- `GET|HEAD api/v1/supplier-payment-proof-attachments/{attachmentId}`
- `POST api/v1/supplier-payments/{supplierPaymentId}/proofs`

## Latest Verification Proof

Latest focused Mobile API proof:

- `git diff --check` produced no output.
- Focused Mobile API tests passed:
  - `23 passed`
  - `75 assertions`

Focused test files:

- `tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php`
- `tests/Feature/MobileApi/Product/MobileApiProductSearchFeatureTest.php`
- `tests/Feature/MobileApi/Procurement/MobileApiSupplierInvoiceReadFeatureTest.php`
- `tests/Feature/MobileApi/Procurement/MobileApiSupplierPaymentProofFeatureTest.php`

Latest docs sync proof:

- `docs/03_blueprints/mobile-api-v1.md` updated to Draft 4.
- Payment proof proof log added.
- Current Gaps no longer say supplier payment proof upload/view is not implemented.
- Next Step now points to Kotlin skeleton in `/home/asyraf/Code/laravel/bengkel2/kotlin`.
- `git diff --check` produced no output after blueprint update.
- At docs-sync proof time, changed file was:
  - `M docs/03_blueprints/mobile-api-v1.md`

## Known Gaps

- Full global Laravel test suite was not run.
- API sanity curl against a running local server was not run.
- Browser/manual QA was not run.
- Due invoice list API is not implemented yet.
- Kotlin Android project is not created yet.
- Android encrypted token storage is not implemented yet.
- No APK build/install proof yet.
- No physical Redmi 12 build/install proof yet.
- Product search still uses application-layer `array_slice` limit, not query-level limit.
- Need verify whether latest blueprint handoff/docs changes are committed/pushed by user.

## Files/Areas Changed in This Mobile API Slice

Known source/test/docs areas touched across the session:

- `routes/api.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/ListMobileApiSupplierInvoicesController.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/ShowMobileApiSupplierInvoiceController.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierPaymentProofController.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Procurement/ShowMobileApiSupplierPaymentProofAttachmentController.php`
- `tests/Feature/MobileApi/Procurement/MobileApiSupplierInvoiceReadFeatureTest.php`
- `tests/Feature/MobileApi/Procurement/MobileApiSupplierPaymentProofFeatureTest.php`
- `docs/03_blueprints/mobile-api-v1.md`

Note:

- User may have committed some source/test changes between steps.
- Do not infer local dirty/clean state without fresh `git status`.

## Safest Next Step

Start next session with repo verification first.

Recommended first command:

pwd
git status --short --untracked-files=all
git branch --show-current
git log -1 --oneline

sed -n '1,940p' docs/03_blueprints/mobile-api-v1.md
sed -n '1,260p' docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-payment-proof-kotlin-skeleton-handoff.md

php artisan route:list --path=api/v1

php artisan test \
  tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php \
  tests/Feature/MobileApi/Product/MobileApiProductSearchFeatureTest.php \
  tests/Feature/MobileApi/Procurement/MobileApiSupplierInvoiceReadFeatureTest.php \
  tests/Feature/MobileApi/Procurement/MobileApiSupplierPaymentProofFeatureTest.php

git diff --check

Then choose one active step only.

Recommended next active step:

- Kotlin Android skeleton in `/home/asyraf/Code/laravel/bengkel2/kotlin`

Initial Kotlin skeleton scope:

1. Verify Kotlin workspace path.
2. Do not create files inside Laravel app repo.
3. Create minimal Android project structure.
4. Use XML/ViewBinding.
5. Use OkHttp only.
6. Add package boundary skeleton:
   - `id.hyperpos.mobile.domain`
   - `id.hyperpos.mobile.application`
   - `id.hyperpos.mobile.application.ports`
   - `id.hyperpos.mobile.adapters.http`
   - `id.hyperpos.mobile.adapters.storage`
   - `id.hyperpos.mobile.features.login`
   - `id.hyperpos.mobile.features.cashierproductsearch`
   - `id.hyperpos.mobile.features.admininvoices`
   - `id.hyperpos.mobile.features.paymentproofupload`
   - `id.hyperpos.mobile.shared`
7. First proof should be:
   - Gradle build green
   - debug APK install green on physical Redmi 12 if device available
   - no feature UI beyond skeleton until build/install proof is green

Do not start due invoice list API unless Kotlin skeleton is intentionally deferred.

## Opening Prompt For Next Session

Continue HyperPOS Mobile API v1 + Kotlin Android companion app.

Repo/workspace:

- Laravel app repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Kotlin Android app path: `/home/asyraf/Code/laravel/bengkel2/kotlin`
- Branch usually `main`
- User handles git commit/push manually
- Do not commit/push unless explicitly requested
- Do not create Android/Kotlin files inside `/home/asyraf/Code/laravel/bengkel2/app`
- Kotlin app must be under `/home/asyraf/Code/laravel/bengkel2/kotlin`

Read first:

- `docs/03_blueprints/mobile-api-v1.md`
- `docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-payment-proof-kotlin-skeleton-handoff.md`

Latest proven backend state:

- Mobile API auth implemented and verified
- Cashier product search implemented and verified
- Admin supplier invoice read API implemented and verified
- Admin supplier payment proof upload/view API implemented and verified
- Latest focused proof: `23 passed (75 assertions)`
- Latest route proof: 8 `/api/v1` routes
- Blueprint updated to Draft 4
- Current backend gaps: due invoice list API, full global suite, sanity curl, browser/manual QA

Locked Kotlin decisions:

- XML/ViewBinding
- OkHttp only
- custom encrypted token storage from v1
- first production install manual signed APK via USB/file

First active step only:

Verify repo/workspace state and then start Kotlin Android skeleton under `/home/asyraf/Code/laravel/bengkel2/kotlin`, not inside Laravel app.

Do not implement feature UI yet. First Kotlin goal is project skeleton + package boundary + build/install proof.

## Update - Kotlin Android Skeleton Build and Device Install Proof

Local proof after this handoff:

- Kotlin Android skeleton was created under /home/asyraf/Code/laravel/bengkel2/kotlin.
- It was not created inside /home/asyraf/Code/laravel/bengkel2/app.
- local.properties points to /opt/android-sdk.
- buildToolsVersion is pinned to 35.0.0 because /opt/android-sdk already has build-tools/35.0.0 and is not writable for normal user package auto-install.
- gradle.properties enables AndroidX and sets org.gradle.java.home to /usr/lib/jvm/java-17-openjdk.
- JDK 17 was installed locally and javac 17.0.19 is available.
- ./gradlew clean assembleDebug passed.
- app/build/outputs/apk/debug/app-debug.apk was created at 3.9M.
- adb detected device 52344d4a7d7c.
- ./gradlew installDebug installed app-debug.apk on 23053RN02A - 15.
- Follow-up ./gradlew assembleDebug smoke build passed in 2s.
- Kotlin workspace is not inside the Laravel app git repo and is not a git repo by itself yet.

Remaining gaps:

- Manual app launch/UI confirmation is not proven.
- Signed release APK is not proven.
- Encrypted token storage is not implemented.
- Login/API integration is not implemented.
- Kotlin project tracking/repository strategy is not decided.

Next safest step:

- Decide Kotlin tracking strategy without moving Kotlin files into the Laravel app repo.
- Then start encrypted token storage blueprint before login API integration.
