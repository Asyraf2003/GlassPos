# HyperPOS Kotlin Android Skeleton Handoff

Date: 2026-05-12

## Scope

Continue HyperPOS Mobile API v1 and Kotlin Android companion app.

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

## Final Goal

Build a small Kotlin Android companion app for HyperPOS Mobile API v1.

The Android app is not a full POS replacement.

Current mobile app target scope:

1. Login.
2. Cashier product search.
3. Cashier product stock and selling price view.
4. Admin supplier invoice search.
5. Admin supplier invoice filter by backend payment status.
6. Admin supplier invoice detail.
7. Admin supplier payment proof upload.
8. Admin supplier payment proof attachment view.
9. Due invoice list later.

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

Required rules for next session:

- Read rules, blueprint, and handoff before technical work.
- Command output from user or local terminal is source of truth.
- Work step by step.
- One active step per response.
- Do not claim done, safe, or tested without proof.
- If data is missing, state GAP explicitly.
- Use FACT, GAP, DECISION, ACTIVE STEP, COMMAND, PROOF TO SEND BACK, PROGRESS, and Session Context Health.
- Use markdown fences with tildes, not backticks.
- Use plain ASCII for terminal commands unless unavoidable.
- User handles git commit and push manually.
- Do not spend effort managing git remote unless user asks.

Relevant rules if needed:

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

Primary blueprint:

- docs/03_blueprints/mobile-api-v1.md

## Locked Decisions

Mobile API:

- Base path is /api/v1.
- API transport adapter is custom from zero.
- Do not expose Blade or web controllers directly as mobile API.
- Raw custom bearer token is used.
- Do not use Sanctum, JWT, or session cookie for v1 mobile auth.
- Token DB stores token_hash only.
- Plain token is returned only once at login.
- Laravel remains source of truth for auth, role, domain, audit, and security.

Kotlin Android:

- XML and ViewBinding.
- OkHttp only.
- Custom encrypted token storage from v1.
- First production install target is manual signed APK through USB or file.
- Kotlin project must be outside Laravel app repo.
- Kotlin path is /home/asyraf/Code/laravel/bengkel2/kotlin.

## Latest Proven Backend State

Mobile API auth implemented and locally verified.

Implemented routes:

- POST api/v1/auth/login
- POST api/v1/auth/logout
- GET api/v1/me
- GET api/v1/products/search
- GET api/v1/supplier-invoices
- GET api/v1/supplier-invoices/{supplierInvoiceId}
- GET api/v1/supplier-payment-proof-attachments/{attachmentId}
- POST api/v1/supplier-payments/{supplierPaymentId}/proofs

Latest focused Mobile API proof:

- 23 passed
- 75 assertions

Focused test files:

- tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php
- tests/Feature/MobileApi/Product/MobileApiProductSearchFeatureTest.php
- tests/Feature/MobileApi/Procurement/MobileApiSupplierInvoiceReadFeatureTest.php
- tests/Feature/MobileApi/Procurement/MobileApiSupplierPaymentProofFeatureTest.php

Latest Laravel repo HEAD before Kotlin docs update:

- 3b2c0c7d commit 1870
- main aligned with origin/main at that proof time

## Latest Docs State

Blueprint updated locally:

- docs/03_blueprints/mobile-api-v1.md

Handoff updated locally:

- docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-payment-proof-kotlin-skeleton-handoff.md

New Kotlin handoff file:

- docs/handoff/kotlin/2026-05-12-kotlin-android-skeleton-handoff.md

Latest docs diff proof before this file:

- git diff --check produced no output
- 2 files changed
- 63 insertions
- 3 deletions

After this file is created, expected status includes this new handoff file as untracked or added, depending on user action.

## Kotlin Skeleton State

Kotlin app path:

- /home/asyraf/Code/laravel/bengkel2/kotlin

Kotlin workspace is not inside the Laravel app git repo.
Kotlin workspace is not a git repo by itself yet.
This is expected for now, but tracking strategy is still undecided.

Created Kotlin files include:

- kotlin/settings.gradle.kts
- kotlin/build.gradle.kts
- kotlin/app/build.gradle.kts
- kotlin/gradle.properties
- kotlin/local.properties
- kotlin/.gitignore
- kotlin/README.md
- kotlin/app/src/main/AndroidManifest.xml
- kotlin/app/src/main/java/id/hyperpos/mobile/features/login/MainActivity.kt
- kotlin/app/src/main/res/layout/activity_main.xml
- kotlin/app/src/main/res/values/strings.xml
- kotlin/app/src/main/res/values/styles.xml

Package boundary placeholders created:

- id.hyperpos.mobile.domain
- id.hyperpos.mobile.application.ports
- id.hyperpos.mobile.adapters.http
- id.hyperpos.mobile.adapters.storage
- id.hyperpos.mobile.adapters.file
- id.hyperpos.mobile.features.login
- id.hyperpos.mobile.features.cashierproductsearch
- id.hyperpos.mobile.features.admininvoices
- id.hyperpos.mobile.features.paymentproofupload
- id.hyperpos.mobile.shared

## Kotlin Environment Fixes

local.properties:

- sdk.dir=/opt/android-sdk

Reason:

- Android SDK exists at /opt/android-sdk.
- /opt/android-sdk is not writable by normal user.
- Gradle previously tried to install build-tools 34.0.0 and failed because SDK directory was not writable.

app/build.gradle.kts:

- compileSdk = 35
- buildToolsVersion = 35.0.0
- minSdk = 26
- targetSdk = 35
- applicationId = id.hyperpos.mobile
- versionCode = 1
- versionName = 0.1.0
- ViewBinding enabled
- OkHttp dependency present

gradle.properties:

- org.gradle.jvmargs=-Xmx2048m -Dfile.encoding=UTF-8
- android.useAndroidX=true
- android.nonTransitiveRClass=true
- kotlin.code.style=official
- org.gradle.java.home=/usr/lib/jvm/java-17-openjdk

JDK state:

- JDK 17 installed locally.
- javac 17.0.19 available.
- Gradle launcher may still show Java 26.
- Gradle daemon uses /usr/lib/jvm/java-17-openjdk from org.gradle.java.home.

## Kotlin Build and Install Proof

Latest proven Kotlin proof:

- ./gradlew clean assembleDebug passed.
- BUILD SUCCESSFUL in 1m 44s.
- 37 actionable tasks, 36 executed, 1 up-to-date.
- app/build/outputs/apk/debug/app-debug.apk created.
- APK size was 3.9M.
- adb detected device 52344d4a7d7c.
- ./gradlew installDebug passed.
- Installed app-debug.apk on 23053RN02A - 15.
- Follow-up ./gradlew assembleDebug smoke build passed.
- Smoke build BUILD SUCCESSFUL in 2s.
- 36 actionable tasks, 36 up-to-date.

## Current Gaps

Backend gaps:

- Full global Laravel test suite was not rerun after Kotlin and docs work.
- API sanity curl against running Laravel server was not run.
- Browser or manual QA was not run.
- Due invoice list API is not implemented.
- Product search still uses application-layer array_slice limit, not query-level limit.

Kotlin gaps:

- Manual app launch and UI text confirmation is not proven.
- Signed release APK is not proven.
- Encrypted token storage is not implemented.
- Login API integration is not implemented.
- Product search UI is not implemented.
- Supplier invoice UI is not implemented.
- Supplier payment proof upload UI is not implemented.
- Kotlin project tracking or repository strategy is not decided.

Tooling gaps:

- kotlinOptions is deprecated and should later migrate to compilerOptions.
- Deprecated Gradle features warning exists for future Gradle 10 compatibility.

## Current Safest Next Step

Do not implement login yet.

First:

1. Verify docs status and anchors.
2. Let user manually commit and push docs if desired.
3. Decide Kotlin tracking strategy without moving Kotlin files into Laravel app repo.
4. After tracking strategy is decided, start encrypted token storage blueprint.
5. Only after token storage blueprint, start login API integration.

## Suggested Verification Command For Next Session

Run from Laravel app repo:

cd /home/asyraf/Code/laravel/bengkel2/app

echo "--- laravel docs status proof ---"
pwd
git status --short --untracked-files=all
git diff --check
git diff --stat
git log -1 --oneline

echo "--- docs anchors proof ---"
grep -n "Draft 5\|Kotlin Android Skeleton Build and Device Install\|installed app-debug.apk\|Kotlin workspace is not inside" docs/03_blueprints/mobile-api-v1.md docs/04_lifecycle/handoff/mobile-api/2026-05-12-mobile-api-v1-payment-proof-kotlin-skeleton-handoff.md docs/handoff/kotlin/2026-05-12-kotlin-android-skeleton-handoff.md | sed -n '1,320p'

echo "--- kotlin smoke proof ---"
cd /home/asyraf/Code/laravel/bengkel2/kotlin
./gradlew assembleDebug
adb devices

## Opening Prompt For New Session

Continue HyperPOS Mobile API v1 and Kotlin Android companion app.

Use docs/handoff/kotlin/2026-05-12-kotlin-android-skeleton-handoff.md as source of truth. Work step by step with one active step per response. Laravel app repo is /home/asyraf/Code/laravel/bengkel2/app. Kotlin app path is /home/asyraf/Code/laravel/bengkel2/kotlin. Do not create Android or Kotlin files inside the Laravel app repo. User handles git commit and push manually. Do not commit or push unless explicitly requested.

Latest proven state:

- Backend Mobile API focused proof is green: 23 passed, 75 assertions.
- 8 api/v1 routes are proven.
- Kotlin Android skeleton is created outside Laravel app repo and locally verified.
- ./gradlew clean assembleDebug passed.
- Debug APK was created at app/build/outputs/apk/debug/app-debug.apk.
- APK size was 3.9M.
- adb detected device 52344d4a7d7c.
- ./gradlew installDebug installed app-debug.apk on 23053RN02A - 15.
- Follow-up ./gradlew assembleDebug smoke build passed.

Current safest next step:

Verify docs status, handoff anchors, and Kotlin smoke proof. Then let user manually commit and push docs if desired. After that, decide Kotlin tracking strategy without moving Kotlin into Laravel app repo. Only after that start encrypted token storage blueprint before login API integration.
