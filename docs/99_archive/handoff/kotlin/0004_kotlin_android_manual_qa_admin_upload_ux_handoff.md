# HyperPOS Kotlin Android - Manual QA Admin Upload UX Handoff

Date: 2026-05-12
Workspace:

- Laravel app repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Kotlin Android app path: `/home/asyraf/Code/laravel/bengkel2/kotlin`

User handles git commit/push manually.
Do not commit/push unless explicitly requested.
Do not create Android/Kotlin files inside `/home/asyraf/Code/laravel/bengkel2/app`.
Kotlin Android source files must stay under `/home/asyraf/Code/laravel/bengkel2/kotlin`.

## Current Scope

Continue HyperPOS Kotlin Android companion app manual QA closure.

Current target scope is intentionally small:

1. Admin:
   - login
   - see supplier invoice list by default
   - search supplier invoice
   - open supplier invoice detail from list/search result
   - upload payment proof media
   - invoice becomes Lunas / outstanding 0 / proof uploaded
2. Kasir:
   - login
   - search/list product
   - see stock
   - see selling price

Explicitly out of scope:

- notification
- Android partial supplier payment
- supplier invoice for kasir
- product search for admin
- backend/Laravel rebuild unless Android proof exposes a backend contract mismatch

## Workflow Rules

- Use Indonesian.
- Local command output from user is source of truth.
- Start from blueprint before implementation.
- One active step per response.
- Do not claim done, safe, fixed, or tested without proof.
- Do not print raw API tokens.
- User handles git commit and push manually.
- Kotlin files must stay under `/home/asyraf/Code/laravel/bengkel2/kotlin`.
- UI stack remains native Android XML + AppCompat + ViewBinding + Espresso.
- Do not use Jetpack Compose.
- Maintain hexagonal discipline:
  - domain models under domain
  - application use cases/results under application
  - ports under application/ports
  - adapters/http/storage/etc under adapters
  - UI under features
- Production Kotlin `app/src/main/**/*.kt` must stay <=100 lines.
- `app/src/androidTest` is audit-only unless explicitly reopened.
- Do not fake backend contracts.
- Do not implement partial payment in Android mobile.
- Do not show Product Search to admin.
- Do not show Supplier Invoice to kasir.
- Do not reopen Laravel backend work unless Kotlin upload-success proof exposes a backend contract mismatch.

## Relevant Existing Docs

Read these first if context is lost:

- `docs/03_blueprints/mobile-api-v1.md`
- `docs/04_lifecycle/handoff/mobile_api/2026-05-12-mobile-api-v1-payment-proof-kotlin-skeleton-handoff.md`
- `docs/handoff/kotlin/2026-05-12-kotlin-android-skeleton-handoff.md`
- Any newer handoff in `docs/handoff/kotlin/`

## Latest Proven Automated State

Previous session already proved baseline before upload-success workflow:

- `./scripts/audit-kotlin-lines.sh`
  - Result: Kotlin production line audit passed. Limit: 100 lines.
- `./gradlew :app:assembleDebug`
  - Result: BUILD SUCCESSFUL.
- Focused HTTP adapter instrumentation package:
  - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.package=id.hyperpos.mobile.adapters.http`
  - Earlier baseline result: 5 tests, BUILD SUCCESSFUL.
- Earlier after UI split:
  - `MainActivitySupplierInvoiceInstrumentedTest`
  - Result: 5 tests, BUILD SUCCESSFUL.

Do not rerun baseline just to re-establish it.
Only request verification after a new change, an error, or before final closure.

## Upload-Success Verification Completed

A live backend fixture probe was run before adding upload-success proof.

Probe result:

- Supplier invoice list success: true
- Row count: 10
- Eligible count: 10
- Eligible condition:
  - `outstanding_rupiah > 0`
  - `can_record_payment = true`
  - `has_uploaded_proof = false`
  - `proof_attachment_count = 0`
  - `policy_state = editable`

Example eligible rows:

- `54188c8a-8613-4ab4-b23f-6cf527928fe6`
  - nomor faktur: `SI-BL-20260502-068`
  - outstanding: `10642500`
- `8d9aa963-9e91-4e5b-9bbe-af9d0a814928`
  - nomor faktur: `SI-BL-20260502-069`
  - outstanding: `15895000`
- `c6ce95d4-f0af-4751-9a20-0702f66dc92e`
  - nomor faktur: `SI-BL-20260502-066`
  - outstanding: `16046000`

A temporary Android instrumentation test was added:

- `OkHttpSupplierInvoiceApiClientInstrumentedTest#adminCanUploadSupplierInvoicePaymentProofAndAutoLunasFromEligibleInvoice`

It asserted:

- target invoice selected from eligible live list
- upload success response:
  - `supplierInvoiceId` matches target
  - `supplierPaymentId` is not blank
  - `amountRupiah > 0`
  - `outstandingRupiah == 0`
  - `proofStatus == uploaded`
  - `attachmentCount >= 1`
- detail refresh after upload:
  - same supplier invoice id
  - outstanding is 0
- list refresh after upload:
  - same supplier invoice exists in refreshed list
  - outstanding is 0
  - `hasUploadedProof == true`
  - `proofAttachmentCount >= 1`

Focused upload-success proof:

- Command:
  - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.adapters.http.OkHttpSupplierInvoiceApiClientInstrumentedTest#adminCanUploadSupplierInvoicePaymentProofAndAutoLunasFromEligibleInvoice`
- Device:
  - `23053RN02A - 15`
- Result:
  - 1 test
  - BUILD SUCCESSFUL

Important note:

- First run failed because device could not reach backend:
  - `Tidak bisa terhubung ke server HyperPOS.`
- Cause:
  - physical device needs `adb reverse tcp:8000 tcp:8000`
  - `127.0.0.1` from Android device points to the device itself unless adb reverse is active.
- After `adb reverse`, focused upload-success test passed.

## Temporary Test Cleanup Completed

The temporary live-mutating upload-success test was removed after proof.

Reason:

- The test mutates live supplier invoices by auto-lunasing one eligible invoice per run.
- It is valid as one-time proof, but unsafe as permanent package regression because it can consume eligible invoices one by one.

Cleanup proof:

- `REMOVED_TEMP_UPLOAD_SUCCESS_TEST_ROBUST`
- HTTP adapter package rerun:
  - Command:
    - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.package=id.hyperpos.mobile.adapters.http`
  - Result:
    - Starting 5 tests
    - Finished 5 tests
    - BUILD SUCCESSFUL

This confirms the package returned to the stable 5-test count after temporary test removal.

## Final Compact Regression After Upload Proof

After cleanup, compact regression commands were run:

1. Kotlin production line audit:

   Command:
   - `./scripts/audit-kotlin-lines.sh`

   Result:
   - `Kotlin production line audit passed. Limit: 100 lines.`

2. Assemble debug:

   Command:
   - `./gradlew :app:assembleDebug`

   Result:
   - BUILD SUCCESSFUL

3. Supplier invoice UI instrumentation:

   Command:
   - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivitySupplierInvoiceInstrumentedTest`

   Result:
   - Starting 5 tests on `23053RN02A - 15`
   - Finished 5 tests
   - BUILD SUCCESSFUL

## Latest Manual QA Result

Manual QA was performed by user after automated proof.

Manual QA scope:

Admin:

- login
- list supplier invoice
- search supplier invoice
- detail supplier invoice
- upload media/payment proof to mark invoice paid

Kasir:

- login
- product list/search
- stock
- selling price

Manual QA result:

- Kasir: PASS for current simple scope.
- Admin: FAIL for upload proof DoD.

### Kasir Manual QA

Proven by user:

- Kasir login works.
- Product list/search works.
- Product search works.
- Product stock is visible.
- Product selling price is visible.

Scope is enough for current kasir DoD.

### Admin Manual QA

Proven by user:

- Admin login works.
- Admin can see supplier invoice list.
- Admin can search supplier invoice.
- Admin can open/view detail, but desired UX is click from list/search result.
- Admin cannot find a way to upload payment proof media.
- Therefore admin upload proof manual QA is FAIL.

User UX notes:

- Device name in login is confusing and unnecessary for operator.
- User wants web admin account and Kotlin admin account to be the same.
- User wants list faktur visible by default after admin login.
- User wants faktur in list/search result to be clickable to show detail.
- User wants upload media flow discoverable from detail.
- Bitwarden autofill did not work well on current login screen.
- Notification is intentionally deferred.

## Source Facts From Current Kotlin UI

Known source behavior from inspection:

- `MainActivity` wires proof picker with `ActivityResultContracts.OpenDocument()`.
- Upload picker is launched from `SupplierInvoicePaymentProofUiController`.
- Upload button exists in `activity_main.xml` as:
  - `supplierInvoicePaymentProofButton`
- Upload status text exists as:
  - `supplierInvoicePaymentProofStatusText`
- `SupplierInvoicePaymentProofActionView.sync(canUpload)` hides the upload button with `View.GONE` when `canUpload = false`.
- `SupplierInvoiceListResultView.applySuccess(...)` selects the first row automatically when no keep id exists.
- Supplier invoice rows are rendered into a single `TextView` string by `MobileUiTextRenderer.supplierInvoiceRows(...)`.
- There is no real clickable invoice row UI yet.
- Detail currently depends on a separate `supplierInvoiceDetailButton`, not clicking an invoice row.
- Login screen shows `deviceNameInput`.
- `LoginUiController` reads `deviceNameInput` into `LoginRequest.deviceName`.

## Current Diagnosis

Backend/API upload path is proven.

Android OkHttp upload path is proven.

The remaining blocker is UI/UX, not backend contract.

Admin manual QA fails because:

1. Supplier invoice list is text-only and not clickable.
2. Detail open flow is button-based, not row-based.
3. Upload proof button is hidden unless selected row is uploadable.
4. User cannot discover upload flow from manual UI.
5. Device name field creates friction at login.
6. Admin list is not default-loaded after login.

## Locked Decision

Next work must be Android UI/UX patch only.

Do not reopen Laravel backend unless Android upload proof exposes a real backend contract mismatch.

Do not implement notification.

Do not implement Android partial payment.

Do not replace XML/ViewBinding with Compose.

## Recommended Next Active Step

Implement Admin Supplier Invoice UX P0.

Target behavior:

1. After admin login:
   - supplier invoice list loads automatically.
   - default list is visible without pressing manual list button.
2. Supplier invoice list/search result:
   - result items are clickable/selectable.
   - clicking an invoice opens detail.
   - selected invoice state is clear enough for operator.
3. Supplier invoice detail:
   - if invoice is eligible for proof upload:
     - show clear `Upload bukti pembayaran` button.
   - if not eligible:
     - show a clear reason/status or hide button with understandable message.
4. Upload flow:
   - button opens file picker.
   - allowed types remain:
     - JPG
     - PNG
     - PDF
   - max file size remains:
     - 2 MB
   - after upload:
     - show success message
     - show status Lunas
     - show attachment count
     - refresh list and detail
5. Login UX:
   - remove operator burden from device name.
   - acceptable minimal patch:
     - keep device name internally/defaulted
     - hide it from normal user form
   - keep backend `device_name` contract intact.

Recommended implementation approach:

- Do not introduce RecyclerView unless necessary.
- Use small auditable XML/ViewBinding components.
- Keep files <=100 LOC.
- Keep native Android XML + AppCompat + ViewBinding.
- Prefer simple dynamic row buttons/LinearLayout for supplier invoice result rows.
- Keep current domain/application/adapter contracts unchanged.

Likely files to inspect/patch:

- Kotlin repo:
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/res/layout/activity_main.xml`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/features/login/MainActivity.kt`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/features/login/LoginUiController.kt`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/features/login/SupplierInvoiceUiController.kt`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/features/login/SupplierInvoiceListResultView.kt`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/features/login/SupplierInvoicePaymentProofUiController.kt`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/features/login/SupplierInvoicePaymentProofActionView.kt`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/main/java/id/hyperpos/mobile/features/login/MobileUiTextRenderer.kt`
  - `/home/asyraf/Code/laravel/bengkel2/kotlin/app/src/androidTest/java/id/hyperpos/mobile/features/login/MainActivitySupplierInvoiceInstrumentedTest.kt`

Possible new files if needed:

- `SupplierInvoiceRowListView.kt`
- `SupplierInvoiceSelection.kt`
- small view helper under `features/login`

Avoid large controller bloat.

## Minimum Verification After UX Patch

After code patch, request only relevant proof:

1. Kotlin line audit:
   - `./scripts/audit-kotlin-lines.sh`

2. Assemble:
   - `./gradlew :app:assembleDebug`

3. Focused admin supplier invoice UI instrumentation:
   - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivitySupplierInvoiceInstrumentedTest`

4. HTTP adapter package only if upload wiring or adapter-facing behavior changes:
   - `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.package=id.hyperpos.mobile.adapters.http`

5. Manual QA admin only:
   - admin login
   - default invoice list visible
   - search invoice
   - click invoice row from list/search
   - detail opens
   - upload media proof
   - status becomes Lunas / outstanding 0 / proof uploaded

Do not request full connectedDebugAndroidTest unless final closure needs it.

## Manual QA Accounts Used So Far

Admin:

- Email: `mobile-admin-android-supplier-invoice@example.test`
- Password: `MobileAdminSmoke123!`

Kasir:

- Email: `mobile-android-smoke@example.test`
- Password: `MobileSmoke123!`

User wants future Kotlin manual QA to use the same accounts as web admin/kasir where possible.

Do not print raw API tokens.

## Current Gaps

- Admin upload media manual QA is not passed.
- Admin clickable invoice row is not implemented.
- Admin invoice list does not auto-load by default after login.
- Login still exposes device name field.
- Bitwarden autofill is not smooth.
- Full Android connected suite was not rerun after upload proof cleanup.
- Laravel full `make verify` is not proven in this Kotlin session.
- Manual QA on separate admin/kasir phones may vary by Android version; do not claim cross-device readiness without proof.
- Notification is intentionally deferred.
- Release/signed APK proof is not included in this handoff.

## Progress Snapshot

- Upload-success automated proof: 100%
- Temporary live-mutating proof test cleanup: 100%
- HTTP adapter package stable regression: 100%
- Kotlin line audit: 100%
- AssembleDebug: 100%
- Supplier invoice UI instrumentation: 100%
- Kasir manual QA current simple scope: PASS
- Admin manual QA current DoD: FAIL
- Overall Kotlin Android current MVP manual QA: not closed

## Safest Opening Prompt For Next Session

Continue HyperPOS Kotlin Android admin supplier invoice UX patch.

Use Indonesian. Follow project rules strictly.

Primary repos/workspaces:

- Laravel app docs repo:
  - `/home/asyraf/Code/laravel/bengkel2/app`
- Kotlin Android app:
  - `/home/asyraf/Code/laravel/bengkel2/kotlin`

Read first:

- `/home/asyraf/Code/laravel/bengkel2/app/docs/handoff/kotlin/2026-05-12-kotlin-android-manual-qa-admin-upload-ux-handoff.md`

Do not restart broad verification.

Latest proven automated state:

- Upload-success Android proof passed with a temporary focused instrumentation test.
- Temporary live-mutating test was removed.
- HTTP adapter package returned to 5 tests and passed.
- Kotlin production line audit passed.
- `:app:assembleDebug` passed.
- `MainActivitySupplierInvoiceInstrumentedTest` passed 5 tests.
- Kasir manual QA passed simple login/product search/stock/price scope.
- Admin manual QA failed because upload proof UI is not discoverable and invoice rows are not clickable.

Current active task:

- Patch Android admin supplier invoice UX only.

Required behavior:

- Admin login shows supplier invoice list by default.
- Invoice list/search result is clickable/selectable.
- Clicking invoice opens detail.
- Detail clearly exposes upload proof button when invoice is eligible.
- Upload proof flow opens picker and after upload shows Lunas/proof uploaded.
- Login device name should not burden operator; hide/default it if possible while keeping backend device_name contract.

Do not:

- reopen Laravel backend
- implement notification
- implement Android partial payment
- show Product Search to admin
- show Supplier Invoice to kasir
- use Jetpack Compose
- commit or push unless explicitly requested
