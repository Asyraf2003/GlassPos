# Handoff: Kotlin Android Admin Supplier Invoice Payment Proof Upload

Date: 2026-05-12

## Final Goal

Implement Kotlin Android admin supplier invoice payment proof upload for the Laravel backend endpoint:

POST /api/v1/supplier-invoices/{supplierInvoiceId}/payment-proof

Request:
- multipart/form-data
- proof_files[] required
- 1-3 files
- allowed: jpg, jpeg, png, pdf
- max: 2048 KB per file

Expected behavior:
- Admin can upload supplier invoice payment proof from Android.
- Upload pays full outstanding amount on backend.
- Invoice becomes paid / Lunas after success.
- Android refreshes invoice list/detail after success.

## Working Rules

- Local command output is highest source of truth.
- One active step per response.
- Start from blueprint before implementation.
- Do not claim done, safe, or tested without proof.
- Do not print raw API tokens.
- User handles git commit and push manually.
- Kotlin files must stay under `/home/asyraf/Code/laravel/bengkel2/kotlin`.
- UI stack remains native Android XML + AppCompat + ViewBinding + Espresso.
- Do not use Jetpack Compose.
- Maintain hexagonal discipline:
  - domain models under `domain`
  - application use cases/results under `application`
  - ports under `application/ports`
  - adapters/http/storage/etc under `adapters`
  - UI under `features`
- Production Kotlin `app/src/main/**/*.kt` must be <= 100 lines.
- `app/src/androidTest/**/*.kt` is audit-only for now unless explicitly reopened.
- Do not fake backend contracts.
- Do not implement partial payment in Android mobile.
- Do not show Product Search to admin.
- Do not show Supplier Invoice to kasir.

## Repo

Kotlin Android repo:

`/home/asyraf/Code/laravel/bengkel2/kotlin`

Baseline before session:

`919e9d5 commit 17`

State before this session:
- Role-aware UI foundation already pushed.
- Admin sees only Supplier Invoice flow.
- Kasir sees only Product Search.
- Supplier Invoice list/detail/search/session behavior verified.
- Android upload media/proof was not implemented yet.

## Backend Contract

Backend endpoint:

POST `/api/v1/supplier-invoices/{supplierInvoiceId}/payment-proof`

Request:
- multipart/form-data
- `proof_files[]`
- allowed file types: jpg, jpeg, png, pdf
- max 2048 KB each

Auth:
- Bearer mobile API token
- admin only

Expected success response:

~~~json
{
  "success": true,
  "data": {
    "supplier_invoice_id": "...",
    "supplier_payment_id": "...",
    "amount_rupiah": 100000,
    "outstanding_rupiah": 0,
    "proof_status": "uploaded",
    "attachment_count": 1
  },
  "message": "Bukti pembayaran supplier berhasil diunggah.",
  "errors": null
}

Backend behavior:

Locks supplier invoice.
Rejects missing invoice.
Rejects voided invoice.
Rejects already paid invoice.
Creates supplier_payment for full outstanding amount.
Stores proof attachment.
Marks proof uploaded.
Syncs supplier_invoice_list_projection.
After success, invoice becomes paid / lunas.

Backend proof from previous session:

RED test first failed 404 for missing route.
GREEN targeted test passed:
MobileApiSupplierPaymentProofFeatureTest
admin_can_upload_supplier_invoice_payment_proof_and_auto_lunas
1 passed, 15 assertions
Focused backend regression passed:
MobileApiSupplierPaymentProofFeatureTest: 7 passed, 37 assertions
MobileApiSupplierInvoiceReadFeatureTest: 6 passed, 17 assertions
AttachSupplierPaymentProofFeatureTest + SupplierPaymentProofFileStorageAdapterFeatureTest: 3 passed, 27 assertions

GAP:

Laravel full make verify is not proven in this Kotlin session.
Do not claim full backend DoD without full Laravel proof.
Completed Kotlin Work
1. Added Android Upload Contract

Added:

app/src/main/java/id/hyperpos/mobile/domain/procurement/MobileSupplierInvoicePaymentProofUpload.kt
app/src/main/java/id/hyperpos/mobile/application/procurement/SupplierInvoicePaymentProofFile.kt
app/src/main/java/id/hyperpos/mobile/application/procurement/UploadSupplierInvoicePaymentProofResult.kt
app/src/main/java/id/hyperpos/mobile/application/procurement/UploadSupplierInvoicePaymentProofUseCase.kt

Updated:

app/src/main/java/id/hyperpos/mobile/application/ports/SupplierInvoiceApiPort.kt

Added port method:

fun uploadPaymentProofBySupplierInvoiceId(
    token: String,
    supplierInvoiceId: String,
    proofFiles: List<SupplierInvoicePaymentProofFile>,
): UploadSupplierInvoicePaymentProofResult
2. Added OkHttp Multipart Upload

Implemented endpoint:

POST supplier-invoices/{supplierInvoiceId}/payment-proof

Multipart field:

proof_files[]

Upload result parsing maps:

supplier_invoice_id
supplier_payment_id
amount_rupiah
outstanding_rupiah
proof_status
attachment_count

Invalid-token upload behavior was added to:

app/src/androidTest/java/id/hyperpos/mobile/adapters/http/OkHttpSupplierInvoiceApiClientInstrumentedTest.kt

3. Added UI Upload Wiring

Updated:

app/src/main/res/values/strings.xml
app/src/main/res/layout/activity_main.xml

Added UI:

supplierInvoicePaymentProofButton
supplierInvoicePaymentProofStatusText

Important XML fix:

Button label uses &amp;, not raw &.

File picker:

ActivityResultContracts.OpenDocument

Allowed MIME:

image/jpeg
image/png
application/pdf

Max local file size:

2 * 1024 * 1024 bytes

Upload action enabled only when selected supplier invoice row satisfies:

outstandingRupiah > 0
canRecordPayment == true
hasUploadedProof == false
4. Added Kotlin Production Line Audit

Added:

./scripts/audit-kotlin-lines.sh

Rule:

app/src/main/**/*.kt must be <= 100 lines.
app/src/androidTest/**/*.kt is audit-only for now.
5. Refactored MainActivity/UI

Original:

MainActivity.kt was 648 lines.

After split, all files under app/src/main/java/id/hyperpos/mobile/features/login are <=100 lines:

MainActivity.kt
MainActivityDependencies.kt
LoginUiController.kt
ProductSearchUiController.kt
SupplierInvoiceUiController.kt
SupplierInvoiceListResultView.kt
SupplierInvoiceDetailResultView.kt
SupplierInvoicePaymentProofUiController.kt
SupplierInvoicePaymentProofActionView.kt
SupplierInvoicePaymentProofFileReader.kt
SupplierInvoicePaymentProofPolicy.kt
MobileUiTextRenderer.kt
6. Refactored HTTP Adapters

Original over-limit files:

OkHttpProductSearchApiClient.kt 104 lines
OkHttpAuthApiClient.kt 137 lines
OkHttpSupplierInvoiceApiClient.kt 290 lines

After split, all production Kotlin files pass line audit.

New/changed HTTP files include:

MobileHttpConstants.kt
MobileJsonMediaTypes.kt
MobileJsonExtensions.kt
MobileActorJsonMapper.kt
AuthLoginHttpCall.kt
AuthCurrentSessionHttpCall.kt
AuthLogoutHttpCall.kt
OkHttpAuthApiClient.kt
ProductSearchJsonMapper.kt
OkHttpProductSearchApiClient.kt
SupplierInvoiceListJsonMapper.kt
SupplierInvoiceDetailJsonMapper.kt
SupplierInvoicePaymentProofJsonMapper.kt
SupplierInvoiceListHttpCall.kt
SupplierInvoiceDetailHttpCall.kt
SupplierInvoicePaymentProofUploadHttpCall.kt
OkHttpSupplierInvoiceApiClient.kt
Latest Proof

Latest local proof from current session:

./scripts/audit-kotlin-lines.sh
Kotlin production line audit passed. Limit: 100 lines.
./gradlew :app:assembleDebug
BUILD SUCCESSFUL

Focused HTTP adapter instrumentation:

./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.package=id.hyperpos.mobile.adapters.http
Starting 5 tests
Finished 5 tests
BUILD SUCCESSFUL

Earlier after UI split:

Supplier invoice UI focused test passed:
MainActivitySupplierInvoiceInstrumentedTest
5 tests
BUILD SUCCESSFUL
Important Gaps
Android upload-success proof has not been run yet.
Full connectedDebugAndroidTest has not been rerun after final HTTP split.
Laravel full make verify is not proven in this Kotlin session.
No git commit/push proof. User handles git manually.
Safest Next Step

First verify current local state and focused proof again:

cd /home/asyraf/Code/laravel/bengkel2/kotlin

echo "--- status ---"
git status --short --branch
git log --oneline -5

echo "--- production line audit ---"
./scripts/audit-kotlin-lines.sh

echo "--- upload/refactor anchors ---"
grep -RIn "uploadPaymentProofBySupplierInvoiceId\|proof_files\[\]\|SupplierInvoicePaymentProof\|ActivityResultContracts.OpenDocument\|supplierInvoicePaymentProof" app/src/main app/src/androidTest | sed -n '1,360p'

echo "--- compile proof ---"
./gradlew :app:assembleDebug

echo "--- focused supplier invoice UI proof ---"
adb reverse tcp:8000 tcp:8000
./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivitySupplierInvoiceInstrumentedTest

echo "--- focused HTTP adapter proof ---"
adb reverse tcp:8000 tcp:8000
./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.package=id.hyperpos.mobile.adapters.http

If all green, choose upload-success verification strategy:

Option A: Manual proof with Android file picker on known unpaid supplier invoice.
Option B: Instrumentation proof only if backend fixture can safely target or create unpaid invoice without mutating shared fixture unpredictably.
Opening Prompt For Next Session

Mulai sesi baru untuk HyperPOS Kotlin Android admin supplier invoice payment proof upload.

Use Indonesian. Follow project rules strictly.

Primary repo:
Kotlin Android:
/home/asyraf/Code/laravel/bengkel2/kotlin

Rules:

Local command output from me is highest source of truth.
One active step per response.
Start from blueprint before implementation.
Do not claim done, safe, or tested without proof.
Do not print raw API tokens.
I handle git commit and push manually.
Kotlin files must stay under /home/asyraf/Code/laravel/bengkel2/kotlin.
UI stack remains native Android XML + AppCompat + ViewBinding + Espresso.
Do not use Jetpack Compose.
Maintain hexagonal discipline:
domain models under domain
application use cases/results under application
ports under application/ports
adapters/http/storage/etc under adapters
UI under features
Production Kotlin app/src/main/**/*.kt must be <=100 lines.
app/src/androidTest is audit-only for now unless explicitly reopened.
Do not fake backend contracts.
Do not implement partial payment in Android mobile.
Do not show Product Search to admin.
Do not show Supplier Invoice to kasir.

Current completed work:

Added upload contract:
MobileSupplierInvoicePaymentProofUpload
SupplierInvoicePaymentProofFile
UploadSupplierInvoicePaymentProofResult
UploadSupplierInvoicePaymentProofUseCase
SupplierInvoiceApiPort.uploadPaymentProofBySupplierInvoiceId(...)
Added OkHttp multipart upload:
POST supplier-invoices/{supplierInvoiceId}/payment-proof
field proof_files[]
Added invalid-token upload client test inside OkHttpSupplierInvoiceApiClientInstrumentedTest.
Added UI upload button/status in activity_main.xml and strings.xml.
Added file picker via ActivityResultContracts.OpenDocument.
Upload enabled only for selected invoice with:
outstandingRupiah > 0
canRecordPayment == true
hasUploadedProof == false
Added scripts/audit-kotlin-lines.sh.
Refactored MainActivity.kt from 648 lines into smaller UI controllers.
Refactored HTTP adapters into call classes and JSON mappers.
Latest proof:
./scripts/audit-kotlin-lines.sh passed.
./gradlew :app:assembleDebug BUILD SUCCESSFUL.
Focused HTTP adapter instrumentation package ran 5 tests and BUILD SUCCESSFUL.
Earlier supplier invoice UI focused test ran 5 tests and BUILD SUCCESSFUL after UI split.

Important gaps:

No Android upload-success proof yet.
No full connectedDebugAndroidTest proof after final HTTP split.
No Laravel full make verify proof in this session.
Do not claim full backend DoD.

First command to run in new session:

cd /home/asyraf/Code/laravel/bengkel2/kotlin

echo "--- status ---"
git status --short --branch
git log --oneline -5

echo "--- production line audit ---"
./scripts/audit-kotlin-lines.sh

echo "--- upload/refactor anchors ---"
grep -RIn "uploadPaymentProofBySupplierInvoiceId|proof_files|SupplierInvoicePaymentProof|ActivityResultContracts.OpenDocument|supplierInvoicePaymentProof" app/src/main app/src/androidTest | sed -n '1,360p'

echo "--- compile proof ---"
./gradlew :app:assembleDebug

echo "--- focused supplier invoice UI proof ---"
adb reverse tcp:8000 tcp:8000
./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivitySupplierInvoiceInstrumentedTest

echo "--- focused HTTP adapter proof ---"
adb reverse tcp:8000 tcp:8000
./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.package=id.hyperpos.mobile.adapters.http

Next active decision after proof:
If all green, choose upload-success verification strategy:
A. Manual proof with Android file picker on unpaid supplier invoice.
B. Instrumentation proof only if backend fixture can safely target/create unpaid invoice without mutating shared fixture unpredictably.

Progress

Final Goal Progress: 35%

Contract, client, UI wiring, audit hygiene, and focused HTTP behavior are green.
Upload-success proof is still missing.

Main Process Progress: 70%

Remaining: upload-success verification, broader regression, optional docs/handoff commit.

Refactor Governance Progress: 100%

Production Kotlin line limit is green.

Session Context Health at handoff: 78% risky.
