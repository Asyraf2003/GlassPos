# HyperPOS Kotlin Android Login Handoff

Date: 2026-05-12

## Workspace

Laravel app repo:

- /home/asyraf/Code/laravel/bengkel2/app

Kotlin Android app path:

- /home/asyraf/Code/laravel/bengkel2/kotlin

GitHub connected repo:

- Asyraf2003/hyperpos

User handles git commit and push manually.
Do not commit or push unless explicitly requested.
Do not create Android or Kotlin files inside /home/asyraf/Code/laravel/bengkel2/app.
Kotlin Android files must stay under /home/asyraf/Code/laravel/bengkel2/kotlin.

## Source Of Truth

Read these before technical work:

- docs/handoff/kotlin/2026-05-12-kotlin-android-skeleton-handoff.md
- docs/03_blueprints/mobile-api-v1.md
- docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-payment-proof-kotlin-skeleton-handoff.md
- docs/handof/kotlin/2026-05-12-kotlin-login-backend-migration-blocker-handoff.md

Command output from the user remains the highest source of truth.

## Final Goal

Build a small Kotlin Android companion app for HyperPOS Mobile API v1.

The app is not a full POS replacement.

Current app scope:

1. Login.
2. Cashier product search.
3. Cashier stock and selling price view.
4. Admin supplier invoice search and filter.
5. Admin supplier invoice detail.
6. Admin supplier payment proof upload.
7. Admin supplier payment proof attachment view.
8. Due invoice list later.

Laravel remains source of truth for auth, role, permissions, products, stock, price, supplier invoices, supplier payments, proof attachments, audit, and domain decisions.

Kotlin Android remains client only.

## Locked Decisions

Backend Mobile API:

- Base path is /api/v1.
- API transport adapter is custom from zero.
- Do not expose Blade or web controllers directly as mobile API.
- Raw custom bearer token is used.
- Do not use Sanctum, JWT, or session cookie for Mobile API v1.
- Token database stores token hash only.
- Plain token is returned only once at login.
- Laravel remains source of truth for auth, role, domain, audit, and security.

Kotlin Android:

- XML and ViewBinding.
- OkHttp only.
- Custom encrypted token storage from v1.
- First production install target is manual signed APK through USB or file.
- Kotlin project must be outside the Laravel app repo.
- Kotlin path is /home/asyraf/Code/laravel/bengkel2/kotlin.
- Kotlin repo strategy is a separate local Git repo at /home/asyraf/Code/laravel/bengkel2/kotlin.
- Kotlin branch is main.
- No submodule.
- No parent monorepo restructure.

## Latest Proven Backend State

Previously proven Mobile API focused proof:

- 23 passed.
- 75 assertions.

Proven api/v1 routes:

- POST api/v1/auth/login
- POST api/v1/auth/logout
- GET api/v1/me
- GET api/v1/products/search
- GET api/v1/supplier-invoices
- GET api/v1/supplier-invoices/{supplierInvoiceId}
- GET api/v1/supplier-payment-proof-attachments/{attachmentId}
- POST api/v1/supplier-payments/{supplierPaymentId}/proofs

Latest local repo proof before this handoff:

- Laravel repo path: /home/asyraf/Code/laravel/bengkel2/app
- Branch: main
- HEAD: b60ca78f commit 1872
- Local main aligned with origin/main at that proof time.
- git status short printed no dirty files.
- git diff check printed no output.

## Latest Kotlin Tracking State

Kotlin repo exists separately at:

- /home/asyraf/Code/laravel/bengkel2/kotlin

Proof:

- git init succeeded.
- git rev-parse showed /home/asyraf/Code/laravel/bengkel2/kotlin.
- branch was normalized to main.
- local.properties is ignored by .gitignore.
- app/build is ignored by .gitignore.
- build is ignored by .gitignore.

Kotlin initial commit is not proven.
Kotlin remote repository is not created or proven.

## Kotlin Skeleton Proof

Kotlin Android skeleton exists outside Laravel app repo.

Known Kotlin environment state:

- local.properties points to /opt/android-sdk.
- buildToolsVersion is pinned to 35.0.0.
- gradle.properties enables AndroidX.
- gradle.properties sets org.gradle.java.home to /usr/lib/jvm/java-17-openjdk.
- JDK 17 installed locally.
- javac 17.0.19 available.
- Gradle daemon uses /usr/lib/jvm/java-17-openjdk.

Proof:

- ./gradlew clean assembleDebug passed.
- Debug APK created at app/build/outputs/apk/debug/app-debug.apk.
- APK size was 3.9M.
- adb detected device 52344d4a7d7c.
- ./gradlew installDebug installed app-debug.apk on 23053RN02A - 15.
- Follow-up ./gradlew assembleDebug smoke build passed.
- Later Kotlin smoke build also passed with BUILD SUCCESSFUL in 2s.

## Encrypted Token Storage State

Implemented files:

- app/src/main/java/id/hyperpos/mobile/application/ports/SessionTokenStore.kt
- app/src/main/java/id/hyperpos/mobile/adapters/storage/AndroidKeystoreSessionTokenStore.kt

Design:

- Application port is SessionTokenStore.
- Android storage implementation is AndroidKeystoreSessionTokenStore.
- Adapter owns Android Context, SharedPreferences, KeyStore, and Cipher.
- Storage uses Android Keystore.
- Storage uses AES/GCM/NoPadding.
- Preference name is hyperpos_secure_session.
- Key alias is hyperpos_mobile_api_token_v1.
- Stored data is IV and cipher text only.
- Plain token is not stored directly.
- Decrypt failure clears corrupted stored token and returns null.

Proof:

- Source anchors showed SessionTokenStore.
- Source anchors showed AndroidKeystoreSessionTokenStore.
- Source anchors showed hyperpos_secure_session.
- Source anchors showed AES/GCM/NoPadding.
- Build passed after implementation.
- Runtime smoke was installed and launched on device.
- UI showed HyperPOS encrypted token storage ready.
- Crash proof showed no fatal exception.
- Temporary fake token smoke code was then removed from MainActivity.
- Build after cleanup passed.

Remaining gaps:

- No instrumentation test.
- No persistent session restore proof.
- No token read after app restart proof from real login.

## Android Network Smoke State

Development network strategy:

- Laravel dev server runs on host 127.0.0.1:8000.
- Android app accesses it through adb reverse.
- adb reverse command used: adb reverse tcp:8000 tcp:8000.
- Android dev base URL used: http://127.0.0.1:8000/api/v1.

Proof:

- Host curl to http://127.0.0.1:8000/api/v1/me returned 401 Unauthorized.
- Response had UNAUTHENTICATED token error.
- adb reverse list showed UsbFfs tcp:8000 tcp:8000.
- Device shell had no curl or wget, so app level OkHttp smoke was used.
- Temporary OkHttp network smoke called /api/v1/me.
- UI showed HyperPOS API reachable.
- Crash proof showed no fatal exception.

Important:

- android:usesCleartextTraffic=true is currently temporary for dev.
- It must be revisited before production signed release.

## Structured Kotlin Login State

Added architecture files:

- app/src/main/java/id/hyperpos/mobile/domain/auth/MobileActor.kt
- app/src/main/java/id/hyperpos/mobile/domain/auth/MobileSession.kt
- app/src/main/java/id/hyperpos/mobile/application/auth/LoginRequest.kt
- app/src/main/java/id/hyperpos/mobile/application/auth/LoginResult.kt
- app/src/main/java/id/hyperpos/mobile/application/auth/LoginUseCase.kt
- app/src/main/java/id/hyperpos/mobile/application/ports/AuthApiPort.kt
- app/src/main/java/id/hyperpos/mobile/adapters/http/MobileApiConfig.kt
- app/src/main/java/id/hyperpos/mobile/adapters/http/OkHttpAuthApiClient.kt

Login UI added in MainActivity and activity_main.xml:

- Email field.
- Password field.
- Device name field.
- Login button.
- Status text.

Current dev base URL in MainActivity:

- http://127.0.0.1:8000/api/v1

LoginUseCase behavior:

- Rejects blank email.
- Rejects blank password.
- Rejects blank device name.
- Calls AuthApiPort.
- On success, saves token using SessionTokenStore.
- Does not display token.

Proof:

- Source anchors showed AuthApiPort.
- Source anchors showed LoginUseCase.
- Source anchors showed OkHttpAuthApiClient.
- Source anchors showed SessionTokenStore.
- Source anchors showed tokenStore.save.
- Build passed.
- Install passed.
- UI showed Email, Password, Redmi 12, LOGIN, and HyperPOS login ready.
- Crash proof showed no fatal exception.

## Invalid Login Runtime Proof

Invalid login was tested through Android UI.

Proof:

- adb reverse was active.
- Login screen launched.
- UI showed Email atau password tidak valid.
- This proves Android UI to LoginUseCase to OkHttpAuthApiClient to Laravel API to JSON parsing to UI failure rendering.
- Crash proof showed no fatal exception.
- Grep did not show Bearer, token, or password leak.

## Current Blocker

Success login is blocked by backend local database schema.

Smoke user proof:

- email: mobile-android-smoke@example.test
- password check: yes
- role: kasir

Host login proof for valid smoke user:

- HTTP 500 Internal Server Error.
- JSON response: message Terjadi gangguan pada sistem, status 500.

Laravel log proof:

- SQLSTATE 42S02.
- Base table or view not found.
- Missing table: bengkelhex.mobile_api_tokens.
- Failing query inserts into mobile_api_tokens.
- Failing adapter: app/Adapters/Out/MobileApi/DatabaseMobileApiTokenStoreAdapter.php.
- Failing service: app/Application/MobileApi/Auth/Services/MobileApiTokenIssuer.php.
- Failing handler: app/Application/MobileApi/Auth/UseCases/LoginMobileApiUserHandler.php.
- Failing controller: app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php.

Schema proof:

- has_table=no for mobile_api_tokens.

Migration file exists:

- database/migrations/2026_05_11_000100_create_mobile_api_tokens_table.php

Conclusion:

- Backend success login cannot pass until the mobile_api_tokens migration is applied or schema state is fixed.
- Do not continue Android success login until host login returns success true and has_token yes without leaking token.

## Current Gaps

Backend gaps:

- mobile_api_tokens table missing in local database.
- Success login returns HTTP 500 for valid smoke user.
- Full global Laravel test suite not rerun.
- API sanity curl success login blocked by missing table.
- Browser or manual QA not run.
- Due invoice list API not implemented.
- Product search still uses application layer array_slice limit.

Kotlin gaps:

- Kotlin initial commit not proven.
- Kotlin remote repo not created.
- Success login not proven.
- Real bearer token encrypted save not proven.
- Stored token read after app restart not proven.
- GET /api/v1/me with stored token not implemented or proven.
- Logout not implemented.
- Product search UI not implemented.
- Supplier invoice UI not implemented.
- Supplier payment proof upload UI not implemented.
- Signed release APK not proven.
- Cleartext HTTP dev setting not production safe.

Tooling gaps:

- Gradle deprecated features warning remains.
- kotlinOptions deprecation remains.

## Safest Next Step

Do not continue Android success login yet.

First verify and fix backend migration state for mobile_api_tokens.

Step 1 command:

cd /home/asyraf/Code/laravel/bengkel2/app

echo "--- migration status proof ---"
php artisan migrate:status | grep -E "mobile_api_tokens|2026_05_11_000100" || true

echo "--- mobile api token migration file proof ---"
sed -n '1,180p' database/migrations/2026_05_11_000100_create_mobile_api_tokens_table.php

echo "--- current schema proof ---"
php artisan tinker --execute='
use Illuminate\Support\Facades\Schema;
echo "has_mobile_api_tokens=".(Schema::hasTable("mobile_api_tokens") ? "yes" : "no").PHP_EOL;
'

If migration is pending and the file is correct, run:

cd /home/asyraf/Code/laravel/bengkel2/app

echo "--- run mobile api token migration ---"
php artisan migrate --path=database/migrations/2026_05_11_000100_create_mobile_api_tokens_table.php

echo "--- schema after migration ---"
php artisan tinker --execute='
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "has_mobile_api_tokens=".(Schema::hasTable("mobile_api_tokens") ? "yes" : "no").PHP_EOL;
if (Schema::hasTable("mobile_api_tokens")) {
    foreach (Schema::getColumnListing("mobile_api_tokens") as $column) {
        echo "column=".$column.PHP_EOL;
    }
    echo "count=".DB::table("mobile_api_tokens")->count().PHP_EOL;
}
'

Then retry sanitized host login proof:

cd /home/asyraf/Code/laravel/bengkel2/app

curl -sS -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --data '{"email":"mobile-android-smoke@example.test","password":"password123","device_name":"curl-smoke"}' \
  | php -r '
$raw = stream_get_contents(STDIN);
$json = json_decode($raw, true);
echo "json_valid=".($json === null ? "no" : "yes").PHP_EOL;
echo "success=".var_export($json["success"] ?? null, true).PHP_EOL;
echo "message=".($json["message"] ?? "").PHP_EOL;
echo "token_type=".($json["data"]["token_type"] ?? "").PHP_EOL;
echo "actor_email=".($json["data"]["actor"]["email"] ?? "").PHP_EOL;
echo "actor_role=".($json["data"]["actor"]["role"] ?? "").PHP_EOL;
echo "has_token=".((isset($json["data"]["token"]) && $json["data"]["token"] !== "") ? "yes" : "no").PHP_EOL;
'

Only after host login proof returns success true and has_token yes, retry Android success login.

## Progress

Final Goal Progress: 23 percent.

Reason:

- Backend Mobile API foundation exists.
- Kotlin skeleton exists.
- Kotlin encrypted token storage source, build, and runtime smoke are proven.
- Android to Laravel network path is proven.
- Structured login UI builds and installs.
- Invalid login flow is proven.
- Success login is blocked by backend missing table.

Main Process Progress: 50 percent.

Reason:

- Login slice is partially integrated, but real success path cannot proceed until backend local schema is fixed.

Sub-step Progress: 75 percent for minimal structured login implementation.

Reason:

- Build, install, UI, network path, and invalid-login proof passed.
- Success login and stored token proof are blocked by missing mobile_api_tokens table.

## Session Context Health

78 percent.

Risk is high enough that this handoff should be used before continuing large work.
