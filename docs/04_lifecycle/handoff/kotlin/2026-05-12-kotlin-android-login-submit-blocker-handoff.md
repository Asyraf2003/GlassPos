# 2026-05-12 Kotlin Android Login Submit Blocker Handoff

## Source of truth

Read these before technical work:

- docs/handof/kotlin/2026-05-12-kotlin-login-backend-migration-blocker-handoff.md
- docs/handoff/kotlin/2026-05-12-kotlin-android-skeleton-handoff.md
- docs/03_blueprints/mobile-api-v1.md
- docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-payment-proof-kotlin-skeleton-handoff.md
- This file: docs/handoff/kotlin/2026-05-12-kotlin-android-login-submit-blocker-handoff.md

## Repositories and paths

Laravel app repo:

/home/asyraf/Code/laravel/bengkel2/app

Kotlin Android app repo:

/home/asyraf/Code/laravel/bengkel2/kotlin

GitHub connected repo:

Asyraf2003/hyperpos

Kotlin tracking decision:

Separate local repo, no submodule, no parent monorepo restructure.

## Rules

- Read rules, blueprint, and handoff before technical work.
- Local command output from user is highest source of truth.
- Work step by step.
- One active step per response.
- Start from blueprint before implementation.
- Do not claim done, safe, or tested without proof.
- If data is missing, state GAP explicitly.
- Use FACT, GAP, DECISION, ACTIVE STEP, COMMAND, PROOF TO SEND BACK, PROGRESS, and Session Context Health.
- Use markdown fences with tildes only if absolutely needed.
- Do not use backtick fences.
- Use plain ASCII for terminal commands unless unavoidable.
- User handles git commit and push manually.
- Do not commit or push unless explicitly requested.
- Do not create Android or Kotlin files inside /home/asyraf/Code/laravel/bengkel2/app.
- Kotlin files must stay under /home/asyraf/Code/laravel/bengkel2/kotlin.
- Do not print raw API tokens.
- Do not claim Android success-login until UI, backend token row, app PID, and app-scoped crash or leak proof are all green.

## Latest proven baseline from prior handoff

Laravel repo was previously proven aligned at:

b60ca78f commit 1872

Backend Mobile API focused proof from prior state:

23 passed, 75 assertions.

Proven api/v1 routes:

- POST api/v1/auth/login
- POST api/v1/auth/logout
- GET api/v1/me
- GET api/v1/products/search
- GET api/v1/supplier-invoices
- GET api/v1/supplier-invoices/{supplierInvoiceId}
- GET api/v1/supplier-payment-proof-attachments/{attachmentId}
- POST api/v1/supplier-payments/{supplierPaymentId}/proofs

## Backend migration blocker status

Initial blocker:

Valid smoke login returned HTTP 500 because Laravel tried to insert into missing table mobile_api_tokens.

Proven failing source from previous handoff:

- app/Adapters/Out/MobileApi/DatabaseMobileApiTokenStoreAdapter.php
- app/Application/MobileApi/Auth/Services/MobileApiTokenIssuer.php
- app/Application/MobileApi/Auth/UseCases/LoginMobileApiUserHandler.php
- app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php

Migration file exists:

database/migrations/2026_05_11_000100_create_mobile_api_tokens_table.php

Migration status before fix:

2026_05_11_000100_create_mobile_api_tokens_table Pending

Schema proof before fix:

has_mobile_api_tokens=no

Migration command was run specifically for this file:

php artisan migrate --path=database/migrations/2026_05_11_000100_create_mobile_api_tokens_table.php

Migration proof after fix:

2026_05_11_000100_create_mobile_api_tokens_table 95.69ms DONE

Migration status proof after fix:

2026_05_11_000100_create_mobile_api_tokens_table [2] Ran

Schema proof after fix:

has_mobile_api_tokens=yes

Backend migration blocker is fixed locally.

## Host login proof

Smoke user:

mobile-android-smoke@example.test

Local dev smoke password was reset to:

MobileSmoke123!

Password reset proof:

smoke_user_found=yes
password_reset=yes

Host valid login proof after migration fix:

http_code=200
success=true
top_level_keys=data,errors,success

Sanitized response shape proof:

data=object:actor,expires_at,token,token_type
data.actor=object:email,id,name,role
data.actor.email=string:length=33
data.actor.id=string:length=1
data.actor.name=string:length=20
data.actor.role=string:length=5
data.expires_at=string:length=25
data.token=string:length=64
token_path_found=data.token
data.token_type=string:length=6
errors=null
success=bool
raw_token_value_printed=no

Backend host login is green.

Important contract decision:

Kotlin must parse token from data.token.

## Android install and launch proof

Kotlin repo path:

/home/asyraf/Code/laravel/bengkel2/kotlin

Device proof:

52344d4a7d7c device

ADB reverse proof:

UsbFfs tcp:8000 tcp:8000

Install proof:

./gradlew installDebug installed app-debug.apk on 23053RN02A - 15

Build proof:

BUILD SUCCESSFUL

Application ID:

id.hyperpos.mobile

Package installed proof:

package:id.hyperpos.mobile

Clean launch proof:

Events injected: 1

PID after launch:

30416

Foreground activity proof:

mCurrentFocus=Window{... id.hyperpos.mobile/id.hyperpos.mobile.features.login.MainActivity}
mFocusedApp=ActivityRecord{... id.hyperpos.mobile/.features.login.MainActivity ...}

Launch crash interpretation:

No HyperPOS launch crash was proven. AndroidRuntime lines in the launch log came from com.android.commands.monkey.Monkey, not from a HyperPOS fatal exception.

## UI structure proof

Current Android UI XML proved these nodes:

emailInput:

resource-id=id.hyperpos.mobile:id/emailInput
class=android.widget.EditText
bounds=[66,975][1014,1074]

passwordInput:

resource-id=id.hyperpos.mobile:id/passwordInput
class=android.widget.EditText
password=true
bounds=[66,1074][1014,1173]

deviceNameInput:

resource-id=id.hyperpos.mobile:id/deviceNameInput
class=android.widget.EditText
text=Redmi 12
bounds=[66,1173][1014,1272]

loginButton:

resource-id=id.hyperpos.mobile:id/loginButton
class=android.widget.Button
text=LOGIN
bounds=[66,1272][1014,1404]

statusText:

resource-id=id.hyperpos.mobile:id/statusText
class=android.widget.TextView
text=Email wajib diisi.
bounds=[66,1404][1014,1485]

## Android login attempts and current blocker

Earlier manual or incomplete attempts did not submit a valid login.

Visible UI text before ADB fill showed:

Email
Password
LOGIN

Backend token issuance proof at that point:

android_smoke_token_row_found=no

Latest backend token rows before Android valid submit attempt showed only host-created rows:

latest_token_rows_count=2
row id=2 user_id=3 device_name=android-host-shape-smoke has_expires_at=yes revoked_at=null created_at=2026-05-11 18:02:03
row id=1 user_id=3 device_name=android-host-smoke has_expires_at=yes revoked_at=null created_at=2026-05-11 18:01:22

ADB fill and tap command was run.

Latest UI text after ADB login tap:

mobile-android-smoke@example.test
masked password was visible
LOGIN

Latest app PID after ADB login tap:

app_pid=30416

Latest app-scoped crash or leak proof after ADB login tap:

No output.

Meaning:

- App process stayed alive.
- No matched FATAL EXCEPTION.
- No matched AndroidRuntime from the app-scoped process.
- No matched smoke email leak.
- No matched smoke password leak.
- No matched Bearer leak.
- No matched data.token leak.
- No matched token pattern leak.

Latest backend Android token issuance proof after ADB login tap:

android_smoke_token_row_found=no

Current blocker:

Android UI fields are filled, app does not crash, but pressing Login does not create a backend mobile_api_tokens row.

This means Android success-login is now proven by manual-input UI success, app PID/foreground proof, and backend token row proof.

## Update 2026-05-12 - Android manual login success proof

Status:

Android login submit blocker is closed by manual-input proof.

Important interpretation:

The previous ADB-driven submit failure was caused by unreliable ADB text input for the smoke password, most likely the special `!` character in `MobileSmoke123!`.

Do not treat the ADB invalid-credential response as a Kotlin login source bug.

Manual login proof:

- Email input: `mobile-android-smoke@example.test`.
- Password input was entered manually on the physical device.
- Device name: `android-device-success-smoke`.
- UI status after tap: `Login berhasil: Mobile Android Smoke (kasir)`.
- App PID after manual login: `17335`.
- Foreground activity after manual login: `id.hyperpos.mobile/id.hyperpos.mobile.features.login.MainActivity`.
- Backend token row proof:
  - `android_success_token_row_found=yes`.
  - `row id=4 user_id=3 device_name=android-device-success-smoke has_expires_at=yes revoked_at=null created_at=2026-05-11 18:56:37`.

Verified conclusion:

- Login button submit path works.
- Kotlin request path works.
- Backend `/api/v1/auth/login` accepts the Android request when credentials are entered correctly.
- Kotlin parses `data.token` successfully enough for the login flow to return success.
- Backend creates `mobile_api_tokens` row for the Android device.
- App stays alive after successful login.
- No raw token was printed in proof.

Remaining gaps:

- Android token read-back from encrypted token store is not separately proven.
- `/api/v1/me` from Android using the stored token is not proven.
- Product search Android flow is not started.
- Full Android automated input remains unreliable for this password and should not be used as final login proof without a safer input mechanism.


## Current interpretation

Backend is green for valid host login.

Android app is green for install, launch, foreground activity, PID, and no scoped crash or leak after login tap.

Android success-login submit path is no longer blocked. The remaining issue was ADB text input reliability for the smoke password, not the Kotlin login source.

Most likely areas to inspect next:

- Kotlin login button click listener.
- Whether MainActivity reads the filled EditText values correctly.
- Whether deviceNameInput is included in LoginRequest.
- Whether LoginUseCase is invoked after button tap.
- Whether OkHttpAuthApiClient sends POST api/v1/auth/login.
- Whether network failure or validation error is swallowed without statusText update.
- Whether statusText remains hidden or not updated after failed request.
- Whether ADB input text with special characters causes partial input issues.
- Whether password value sent by app matches MobileSmoke123!
- Whether app dev base URL is still http://127.0.0.1:8000/api/v1.
- Whether adb reverse is still active during tap.

Do not assume which one is true without source or runtime proof.

## GAP

Missing proof:

- No proof that button click handler fired.
- No proof that LoginUseCase was invoked.
- No proof that OkHttpAuthApiClient sent a request.
- No proof of Android-side HTTP status for the submit attempt.
- Backend token row for android-device-success-smoke is now proven. Remaining gap: Android token read-back from encrypted storage is not separately proven.
- No UI success status after Android login.
- No source inspection in this session after the submit blocker appeared.

## Recommended next active step

Do not patch first.

Read Kotlin login source files and route the investigation through source evidence.

Suggested command for next session:

cd /home/asyraf/Code/laravel/bengkel2/kotlin

echo "--- kotlin login source inventory ---"
find app/src -type f | grep -Ei 'login|auth|session|api|mainactivity|token' | sort

echo "--- MainActivity proof ---"
sed -n '1,240p' app/src/main/java/id/hyperpos/mobile/features/login/MainActivity.kt

echo "--- login related Kotlin source proof ---"
for f in $(find app/src -type f | grep -Ei 'Login|Auth|Session|Api|Token|MobileApi' | sort); do
  echo "--- FILE: $f ---"
  sed -n '1,240p' "$f"
done

After reading source, decide the smallest safe step.

Expected next decision branches:

1. If button click does not call LoginUseCase, patch click wiring.
2. If request is sent but response parse misses data.token, patch parser.
3. If request fails but status is swallowed, patch error/status rendering.
4. If app base URL or adb reverse is stale, fix runtime setup only.
5. If ADB input did not correctly set password or device name, use manual login proof or UIAutomator instead of patching code.

## Do not do

- Android success-login may be claimed only for the manual-input proof scope recorded in this handoff.
- Do not claim token persistence.
- Do not claim Mobile API v1 login is complete end-to-end.
- Do not create Kotlin files inside Laravel app repo.
- Do not commit or push.
- Do not print raw API token.
- Do not run unrelated full refactor.
- Do not start supplier invoice Android screens before login proof is green.

## Progress

Final Goal Progress:

8 percent.

Reason:

Backend login blocker is fixed, Android app install and launch are proven, and Android manual login success is proven. Product search and admin flows are not started.

Main Process Progress:

80 percent for login integration unblock.

Reason:

Backend host login contract is green. Android runtime shell is green. Android manual submit path is green. Remaining login-adjacent gap is token read-back proof.

Sub-step Progress:

95 percent for Android success-login smoke.

Reason:

Manual form input, UI success, app PID/foreground, and backend token row are proven. Remaining gap is separate encrypted token store read-back proof.

## Latest proof summary

Backend:

- mobile_api_tokens migration ran.
- has_mobile_api_tokens=yes.
- Host login returned http_code=200.
- Host login returned success=true.
- Token path is data.token.
- Token length is 64.

Android:

- Device detected.
- ADB reverse active earlier.
- installDebug passed.
- Package installed.
- App launched.
- PID alive.
- Foreground MainActivity proven.
- UI fields exist.
- ADB filled email and password visibly.
- Login tap did not crash app.
- App-scoped crash or leak grep was empty.
- Backend token row for Android device name was not created.

## Opening prompt for next session

Continue HyperPOS Kotlin Android login submit blocker.

Use this file as source of truth:

docs/handoff/kotlin/2026-05-12-kotlin-android-login-submit-blocker-handoff.md

Current proven state:

Backend mobile_api_tokens migration is fixed. Host valid login returns http_code=200, success=true, and token path data.token. Android app installs, launches, has PID, and foregrounds id.hyperpos.mobile.features.login.MainActivity. ADB submit reached backend but failed due to invalid credential caused by unreliable ADB password input. Manual input on the physical device succeeded: UI showed Login berhasil, app PID/foreground stayed alive, and backend created a mobile_api_tokens row for android-device-success-smoke. Android success-login manual-input scope is proven.

One active step only:

Inspect Kotlin login source first. Do not patch before reading MainActivity, LoginUseCase, OkHttpAuthApiClient, AuthApiPort, LoginRequest, LoginResult, MobileApiConfig, and token store wiring. Determine whether the blocker is click wiring, request creation, response parsing, status rendering, base URL, ADB input, or swallowed error. Then propose the smallest patch with exact file paths and proof commands.

## Android token read-back proof

Status: Fixed and locally verified for Android Keystore save/read runtime proof.

Proof date: 2026-05-12.

Kotlin files changed for proof:

- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/build.gradle.kts`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/androidTest/java/id/hyperpos/mobile/adapters/storage/AndroidKeystoreSessionTokenStoreInstrumentedTest.kt`

Instrumentation proof:

- Device listed by adb: `52344d4a7d7c device`
- Test device: `23053RN02A - 15`
- Test class: `id.hyperpos.mobile.adapters.storage.AndroidKeystoreSessionTokenStoreInstrumentedTest`
- Test count: 2 tests
- Result: `BUILD SUCCESSFUL`
- Gradle task: `:app:connectedDebugAndroidTest`

Verified behavior:

- `readReturnsNullWhenNoTokenIsStored`
- `saveThenReadReturnsStoredToken`

Security note:

- No raw backend API token was printed.
- The test used a fake token only for Android Keystore read-back proof.

Current session decision:

- Android token read-back from `AndroidKeystoreSessionTokenStore` is proven.
- `/api/v1/me` using stored token is still not proven.
- Product search Android flow is still not started.

## Android current-session /me stored-token proof

Status: Fixed and locally verified for stored-token `/api/v1/me` runtime proof.

Proof date: 2026-05-12.

Kotlin files changed for proof:

- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/application/auth/CurrentSessionResult.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/application/auth/CurrentSessionUseCase.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/application/ports/AuthApiPort.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/adapters/http/OkHttpAuthApiClient.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/androidTest/java/id/hyperpos/mobile/adapters/http/OkHttpAuthApiClientCurrentSessionInstrumentedTest.kt`

Runtime proof:

- Android test class: `id.hyperpos.mobile.adapters.http.OkHttpAuthApiClientCurrentSessionInstrumentedTest`
- Test device: `23053RN02A - 15`
- Test count: 1 test
- Compile proof: `:app:assembleDebugAndroidTest` returned `BUILD SUCCESSFUL`
- Runtime proof: `:app:connectedDebugAndroidTest` returned `BUILD SUCCESSFUL in 29s`

Verified behavior:

- Android login uses `mobile-android-smoke@example.test`.
- Android login stores the backend token through `AndroidKeystoreSessionTokenStore`.
- Android reads the stored token back from encrypted local storage.
- Android calls `GET /api/v1/me` through `OkHttpAuthApiClient.currentSession()`.
- `/api/v1/me` succeeds using `Authorization: Bearer <stored token>`.
- Returned actor is `Mobile Android Smoke` with email `mobile-android-smoke@example.test` and role `kasir`.

Security note:

- No raw backend API token was printed.
- Runtime proof validates the stored-token path without exposing the token value.

Current session decision:

- Android token read-back from `AndroidKeystoreSessionTokenStore` is proven.
- Android `/api/v1/me` using stored token is proven.
- Login integration unblock scope is closed.
- Product search Android flow is still not started.

## Android product search API stored-token proof

Status: Fixed and locally verified for Android Product Search API stored-token runtime proof.

Proof date: 2026-05-12.

Kotlin files changed for proof:

- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/domain/product/MobileProductSearchRow.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/application/product/ProductSearchResult.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/application/product/SearchProductsUseCase.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/application/ports/ProductSearchApiPort.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/adapters/http/OkHttpProductSearchApiClient.kt`
- `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/androidTest/java/id/hyperpos/mobile/adapters/http/OkHttpProductSearchApiClientInstrumentedTest.kt`

Runtime proof:

- Android test class: `id.hyperpos.mobile.adapters.http.OkHttpProductSearchApiClientInstrumentedTest`
- Test device: `23053RN02A - 15`
- Test count: 1 test
- Compile proof: `:app:assembleDebugAndroidTest` returned `BUILD SUCCESSFUL in 4s`
- Runtime proof: `:app:connectedDebugAndroidTest` returned `BUILD SUCCESSFUL in 33s`

Verified behavior:

- Android login uses `mobile-android-smoke@example.test`.
- Android login stores the backend token through `AndroidKeystoreSessionTokenStore`.
- Android product search uses the stored token.
- Android calls `GET /api/v1/products/search?q=ban`.
- Product search response is parsed into Kotlin product search model.
- Search result query is `ban`.
- Search result limit is `20`.
- Search result rows are not empty.
- At least one returned label contains `Ban`.
- Returned stock values are non-negative.
- Returned default prices are positive.
- Returned minimum prices match current backend floor price.

Security note:

- No raw backend API token was printed.
- Product search proof validates the stored-token authenticated API path without exposing token value.

Current session decision:

- Login integration unblock scope is closed.
- Android `/api/v1/me` using stored token is proven.
- Android Product Search API layer using stored token is proven.
- Product Search UI flow is still not started.
