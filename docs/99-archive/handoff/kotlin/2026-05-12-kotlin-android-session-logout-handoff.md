# HyperPOS Kotlin Android Session Logout Handoff

Date: 2026-05-12

Scope: Continue HyperPOS Kotlin Android after Product Search foundation and UI regression hardening. Next candidate scope is Android session invalid/logout handling before admin supplier invoice flow.

## Primary repos

- Laravel app repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Kotlin Android app repo: `/home/asyraf/Code/laravel/bengkel2/kotlin`
- Laravel GitHub repo: `Asyraf2003/hyperpos`
- Kotlin GitHub repo: `Asyraf2003/kotlin-hyperpos`

## Rules

- Use Indonesian.
- Local command output from owner is highest source of truth.
- One active step per response.
- Start from blueprint before implementation.
- Do not claim done, safe, or tested without proof.
- Do not print raw API tokens.
- Owner handles git commit and push manually.
- Do not commit or push unless owner explicitly asks.
- Do not create Android/Kotlin files inside Laravel app repo.
- Kotlin files must stay under `/home/asyraf/Code/laravel/bengkel2/kotlin`.
- Laravel docs/handoff files stay under `/home/asyraf/Code/laravel/bengkel2/app/docs/handoff/kotlin`.
- Do not start Android admin supplier invoice flow before Product Search and session handling status are intentionally closed or deferred.

## Current proven state

Product Search foundation is closed.

Known pushed repo proof from owner:

- Kotlin repo: `bd87893 (HEAD -> main, origin/main) commit 3`
- Laravel repo: `a38ea403 (HEAD -> main, origin/main, origin/HEAD) commit 1880`

Meaning:

- Kotlin Product Search work has been committed and pushed by owner.
- Laravel Product Search handoff/docs update has been committed and pushed by owner.
- Previous local untracked Kotlin state is no longer the current working assumption after this proof.

## Product Search completed scope

Closed/proven items:

- Login integration unblock is closed.
- Android Keystore token read-back is proven.
- Android `/api/v1/me` using stored token is proven.
- Android Product Search API layer using stored token is proven.
- Minimum Android Product Search UI is proven.
- Product Search UI regression instrumentation hardening is proven.
- No raw token was printed.

Minimum Product Search UI proof:

- Login UI success showed `Login berhasil: Mobile Android Smoke (kasir)`.
- Product Search section showed `Pencarian Produk Kasir`.
- UI proof query was `pis`.
- UI showed `Hasil untuk "pis" (2/20)`.
- UI rendered stock and prices:
  - `Harga jual: Rp 185.000`
  - `Harga jual: Rp 275.000`
- Backend token rows for smoke user included latest token row with `last_used_at=yes`.

Focused Product Search regression proof:

- `adb reverse tcp:8000 tcp:8000`
- Product Search API instrumentation:
  - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.adapters.http.OkHttpProductSearchApiClientInstrumentedTest`
  - Result: `BUILD SUCCESSFUL in 23s`
- Product Search UI instrumentation:
  - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivityProductSearchInstrumentedTest`
  - Result: `BUILD SUCCESSFUL in 2m 26s`
- XML proof:
  - `tests="1"`
  - `failures="0"`
  - `errors="0"`
  - `skipped="0"`
  - testcase: `cashierCanLoginAndSearchProductsFromUi`

Behavior note:

- During `connectedDebugAndroidTest`, Android may open the app, run instrumentation, force stop, and uninstall/cleanup app/test APK. This is expected Android test runner behavior, not manual app deletion.

## Latest Kotlin auth/session source snapshot

Current Kotlin source has:

- `MainActivity`
  - handles login UI
  - hides Product Search before login
  - shows Product Search after `LoginResult.Success`
  - calls `SearchProductsUseCase`
  - does not expose raw token in UI
- `LoginUseCase`
  - validates email/password/device name
  - calls `AuthApiPort.login`
  - saves token on success through `SessionTokenStore`
- `CurrentSessionUseCase`
  - reads stored token
  - returns failure when no token exists
  - calls `AuthApiPort.currentSession(token)`
- `AuthApiPort`
  - has `login(request)`
  - has `currentSession(token)`
  - does not yet have `logout(token)`
- `SessionTokenStore`
  - has `save(token)`
  - has `read()`
  - has `clear()`
- `AndroidKeystoreSessionTokenStore`
  - encrypts token using Android Keystore AES/GCM
  - stores IV and ciphertext in app preferences
  - clears token on decrypt failure
- `OkHttpAuthApiClient`
  - supports `/auth/login`
  - supports `/me`
  - maps IO failure to `Tidak bisa terhubung ke server HyperPOS.`
  - maps invalid response to `Respons server tidak valid.`

Current Kotlin gap:

- No logout use case yet.
- No logout API port yet.
- No logout UI button yet.
- No explicit invalid-session local token clear yet.
- Product Search failure currently shows message but does not clear invalid token or reset UI.

## Backend mobile auth/logout contract discovery

Backend route anchors proved mobile logout already exists.

Route file:

- `routes/api.php`

Relevant route contract:

- `POST /api/v1/auth/login`
- `GET /api/v1/me`
- `POST /api/v1/auth/logout`
- Authenticated routes use middleware `mobile.api.auth`.

Relevant backend files discovered:

- `app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Auth/LogoutMobileApiController.php`
- `app/Adapters/In/Http/Controllers/Api/V1/Auth/MeMobileApiController.php`
- `app/Adapters/In/Http/Middleware/MobileApi/AuthenticateMobileApiToken.php`
- `app/Application/MobileApi/Auth/UseCases/LoginMobileApiUserHandler.php`
- `app/Application/MobileApi/Auth/UseCases/LogoutMobileApiTokenHandler.php`
- `app/Application/MobileApi/Auth/Services/MobileApiTokenVerifier.php`
- `app/Application/MobileApi/Auth/Services/MobileApiTokenIssuer.php`
- `app/Application/MobileApi/Auth/Services/MobileApiTokenHasher.php`
- `app/Adapters/Out/MobileApi/DatabaseMobileApiTokenStoreAdapter.php`
- `app/Ports/Out/MobileApi/MobileApiTokenStorePort.php`
- `database/migrations/2026_05_11_000100_create_mobile_api_tokens_table.php`

Important backend behavior discovered from anchors:

- `LogoutMobileApiController` reads `mobile_api_token_id` from request attributes.
- `LogoutMobileApiTokenHandler` calls token store revoke behavior.
- `DatabaseMobileApiTokenStoreAdapter` has `revokeById`.
- Token table has nullable indexed `revoked_at`.
- Middleware `AuthenticateMobileApiToken` sets `mobile_api_token_id`.

## Working decision for next session

Next safe scope: Android session invalid/logout handling.

Do not start Android admin supplier invoice flow yet.

Recommended implementation blueprint, pending source read:

1. Read backend logout controller, middleware, token verifier, and test shape.
2. Add Kotlin auth logout boundary:
   - `AuthApiPort.logout(token)`
   - `LogoutResult`
   - `LogoutUseCase`
3. Logout use case behavior:
   - if no local token exists, clear local token and return success/no-session result
   - if token exists, call backend logout
   - regardless of backend success for local UX, clear local token after explicit logout attempt only if policy chosen
4. UI behavior:
   - show logout button only after login success
   - logout clears local token
   - hide Product Search container
   - clear Product Search status/results
   - show `Logout berhasil.`
5. Invalid session behavior:
   - when API returns unauthenticated/session invalid, clear local token
   - hide Product Search container
   - show login-required message
6. Verification:
   - targeted Kotlin compile
   - targeted auth/logout instrumentation
   - targeted UI logout instrumentation
   - backend token row proof may check `revoked_at` without printing raw token

## Current gaps

- Backend logout response shape has not been read in detail yet.
- Backend tests for logout have not been inspected.
- Kotlin logout has not been implemented.
- Kotlin invalid-session cleanup has not been implemented.
- Full Android suite not run for this next scope.
- Full Laravel global suite not needed yet because no new backend patch is planned, unless backend changes become necessary.

## Recommended first active step in next session

Read backend logout source detail and Kotlin auth files before patch.

Suggested command from Laravel repo:

~~~bash
cd /home/asyraf/Code/laravel/bengkel2/app

echo "--- backend mobile logout source detail ---"
sed -n '1,220p' routes/api.php
sed -n '1,220p' app/Adapters/In/Http/Controllers/Api/V1/Auth/LogoutMobileApiController.php
sed -n '1,260p' app/Adapters/In/Http/Middleware/MobileApi/AuthenticateMobileApiToken.php
sed -n '1,220p' app/Application/MobileApi/Auth/UseCases/LogoutMobileApiTokenHandler.php
sed -n '1,260p' app/Adapters/Out/MobileApi/DatabaseMobileApiTokenStoreAdapter.php
sed -n '1,200p' app/Ports/Out/MobileApi/MobileApiTokenStorePort.php

echo "--- backend mobile auth tests inventory ---"
find tests -type f | grep -Ei 'MobileApi|mobile.*api|auth|logout|token' | sort | sed -n '1,220p'

echo "--- kotlin auth source detail ---"
cd /home/asyraf/Code/laravel/bengkel2/kotlin
sed -n '1,300p' app/src/main/java/id/hyperpos/mobile/features/login/MainActivity.kt
sed -n '1,220p' app/src/main/java/id/hyperpos/mobile/application/ports/AuthApiPort.kt
sed -n '1,220p' app/src/main/java/id/hyperpos/mobile/application/ports/SessionTokenStore.kt
sed -n '1,320p' app/src/main/java/id/hyperpos/mobile/adapters/http/OkHttpAuthApiClient.kt
sed -n '1,260p' app/src/main/java/id/hyperpos/mobile/adapters/storage/AndroidKeystoreSessionTokenStore.kt
find app/src/androidTest/java -type f | sort | sed -n '1,220p'

After reading proof, decide exact Kotlin patch.

Progress

Final Goal Progress: 17 percent.

Reason:

Product Search foundation is closed.
Login, Keystore, /me, Product Search API, Product Search UI, and Product Search UI regression hardening are proven.
Admin supplier invoice/payment Android flows are not started.

Main Process Progress: 100 percent for Android Product Search foundation plus UI regression hardening.

Next Process Progress: 10 percent for Android session invalid/logout handling.

Reason:

Kotlin auth/session source was read.
Backend route anchors prove logout/revoke contract exists.
Exact backend logout response source still needs detailed read before Kotlin patch.

Sub-step Progress: 0 percent for Kotlin logout implementation.

Reason:

No Kotlin logout files changed yet.
No logout tests run yet.
Opening prompt for next session

Continue HyperPOS Kotlin Android session invalid/logout handling after Product Search closure.

Use Indonesian. Follow my project rules strictly.

Primary repos:

Laravel app repo: /home/asyraf/Code/laravel/bengkel2/app
Kotlin Android app repo: /home/asyraf/Code/laravel/bengkel2/kotlin
Laravel GitHub repo: Asyraf2003/hyperpos
Kotlin GitHub repo: Asyraf2003/kotlin-hyperpos

Read this handoff first:

/home/asyraf/Code/laravel/bengkel2/app/docs/handoff/kotlin/2026-05-12-kotlin-android-session-logout-handoff.md

Important workflow rules:

Local command output from me is the highest source of truth.
One active step per response.
Start from blueprint before implementation.
Do not claim done, safe, or tested without proof.
Do not print raw API tokens.
I handle git commit and push manually.
Do not commit or push unless I explicitly ask.
Do not create Android/Kotlin files inside the Laravel app repo.
Kotlin files must stay under /home/asyraf/Code/laravel/bengkel2/kotlin.
Laravel docs/handoff files stay under /home/asyraf/Code/laravel/bengkel2/app/docs/handoff/kotlin.
If data is missing, state GAP explicitly.
Use FACT, GAP, DECISION, ACTIVE STEP, COMMAND, PROOF TO SEND BACK, PROGRESS, and Session Context Health for technical workflow.
Do not start admin supplier invoice Android flow before session logout/invalid handling status is intentionally closed or deferred.

Current proven state:

Kotlin Product Search is closed and pushed at bd87893.
Laravel Product Search handoff/docs are closed and pushed at a38ea403.
Login integration unblock is closed.
Android Keystore token read-back is proven.
Android /api/v1/me using stored token is proven.
Android Product Search API layer using stored token is proven.
Minimum Android Product Search UI is proven.
Product Search UI regression hardening is proven.
No raw token was printed.

Backend logout contract discovery:

routes/api.php exposes POST /api/v1/auth/logout inside mobile.api.auth middleware.
LogoutMobileApiController exists.
LogoutMobileApiTokenHandler exists.
DatabaseMobileApiTokenStoreAdapter has revokeById.
mobile_api_tokens has revoked_at.

Recommended first active step:

Read backend logout source detail and Kotlin auth files. Do not patch yet. Then decide the minimal Kotlin logout/session invalid blueprint.

## Update: Android session logout/invalid handling closed

Status: Closed and locally verified.

Kotlin repo: /home/asyraf/Code/laravel/bengkel2/kotlin
Kotlin GitHub repo: Asyraf2003/kotlin-hyperpos
Latest proven Kotlin pushed commit: dac538e commit 9

Scope closed:
- Added Kotlin logout boundary through AuthApiPort.logout(token).
- Added LogoutResult.
- Added LogoutUseCase.
- Implemented OkHttpAuthApiClient.logout(token) using POST /api/v1/auth/logout.
- Added explicit ProductSearchResult.Unauthenticated.
- Product Search now maps HTTP 401 to unauthenticated session state.
- SearchProductsUseCase returns unauthenticated result when local token is missing.
- MainActivity clears local token and resets authenticated UI on invalid Product Search session.
- Added logout button to the main UI.
- Logout UI clears local session, hides Product Search, and shows Logout berhasil.
- Added targeted logout API/usecase instrumentation.
- Added targeted logout UI instrumentation.
- Added targeted invalid-session UI instrumentation.
- Updated Product Search API instrumentation to handle the new sealed result branch.

Verification proof:
- ./gradlew :app:assembleDebug
  Result: BUILD SUCCESSFUL in 2s
- ./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.adapters.http.OkHttpAuthApiClientCurrentSessionInstrumentedTest
  Result: BUILD SUCCESSFUL in 21s, 2 tests
- ./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.adapters.http.OkHttpProductSearchApiClientInstrumentedTest
  Result: BUILD SUCCESSFUL in 19s, 1 test
- ./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivityProductSearchInstrumentedTest
  Result: BUILD SUCCESSFUL in 37s, 3 tests

Behavior proven:
- Login still works with stored token.
- /api/v1/me still works using stored token.
- Product Search API still works using stored token.
- Product Search UI still works after logout/session patch.
- Logout revokes backend token.
- Logout clears local encrypted token.
- Revoked token is rejected by /api/v1/me with Autentikasi diperlukan.
- Logout button appears after login.
- Logout button hides after logout.
- Product Search container hides after logout.
- Product Search with revoked token clears local token, hides authenticated UI, and shows Autentikasi diperlukan.

Security notes:
- No raw API token was printed in proof.
- Token was only asserted for existence/non-blank in tests.
- Local token clear was verified through SessionTokenStore.read() == null.

Known gaps:
- No full Android test suite run beyond focused instrumentation listed above.
- No manual browser/device QA beyond instrumentation.
- No Gradle deprecation cleanup; current warning is non-blocking for this scope.
- No Android admin supplier invoice flow started yet.

Next safe process:
Start Android admin supplier invoice flow only after verifying both repos are clean and reading the supplier invoice mobile API contract from Laravel source/tests.


## Update: Android Admin Supplier Invoice API + Minimum List UI

Kotlin repo: `/home/asyraf/Code/laravel/bengkel2/kotlin`

Latest proven pushed Kotlin commit after supplier invoice list UI:
- `16753ab` commit 11

Previous Kotlin supplier invoice API foundation commit:
- `64a49ba` commit 10

Scope completed:
- Supplier invoice domain models for mobile list/detail.
- Supplier invoice application result/usecase layer.
- `SupplierInvoiceApiPort`.
- `OkHttpSupplierInvoiceApiClient`.
- Focused supplier invoice API instrumentation test.
- Minimum admin-only supplier invoice list UI in existing native Android XML + AppCompat + ViewBinding stack.
- Cashier login does not show Supplier Invoice UI.
- Product Search UI regression remained green after Supplier Invoice UI patch.

Verification proof:
- `./gradlew :app:assembleDebug`
  - Result: `BUILD SUCCESSFUL in 6s`
- `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.adapters.http.OkHttpSupplierInvoiceApiClientInstrumentedTest`
  - Result: `BUILD SUCCESSFUL in 22s`
  - Device: `23053RN02A - 15`
  - Tests: 1
- `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivitySupplierInvoiceInstrumentedTest`
  - Result: `BUILD SUCCESSFUL in 50s`
  - Device: `23053RN02A - 15`
  - Tests: 2
- `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivityProductSearchInstrumentedTest`
  - Result: `BUILD SUCCESSFUL in 43s`
  - Device: `23053RN02A - 15`
  - Tests: 3

Current behavior:
- Admin login shows Product Search and Supplier Invoice UI.
- Cashier login shows Product Search but hides Supplier Invoice UI.
- Supplier Invoice list loads from `/api/v1/supplier-invoices`.
- UI renders invoice number, supplier name, outstanding amount, and policy state.
- Unauthenticated supplier invoice API result clears local token and resets authenticated UI through existing session invalid handling path.
- No raw API token was printed.

Known gaps:
- Supplier Invoice detail UI is not implemented yet.
- Supplier payment proof upload UI is not implemented yet.
- Supplier payment proof attachment viewer is not implemented yet.
- No Jetpack Compose migration decision exists.
- Full Android suite beyond the focused API/UI/Product Search regression classes was not run in this checkpoint.

Next safe step:
- Start Supplier Invoice detail UI slice from existing pushed API foundation.
- Do not implement payment proof upload until detail read flow is locally verified.
