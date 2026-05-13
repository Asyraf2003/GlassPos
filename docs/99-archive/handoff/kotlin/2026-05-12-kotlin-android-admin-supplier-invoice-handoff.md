# HyperPOS Kotlin Android Admin Supplier Invoice Handoff

Date: 2026-05-12

Scope: Kotlin Android admin supplier invoice flow after Product Search, session logout/invalid handling, and role-aware UI cleanup.

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
- Kotlin files must stay under `/home/asyraf/Code/laravel/bengkel2/kotlin`.
- Laravel handoff files stay under `/home/asyraf/Code/laravel/bengkel2/app/docs/handoff/kotlin`.
- UI stack remains native Android XML + AppCompat + ViewBinding + Espresso instrumentation.
- Do not jump to Jetpack Compose without a locked decision/ADR.
- Kotlin architecture must stay disciplined:
  - domain models under `domain`
  - use cases/results under `application`
  - ports under `application/ports`
  - HTTP adapters under `adapters/http`
  - UI under `features`
- Do not mix backend/domain shortcuts into Android UI.

## Latest proven Kotlin state

Latest pushed Kotlin state proven by owner:

- `a433d44` commit 16
- Scope: role-aware mobile UI foundation

Latest local Kotlin change after commit 16:

- Added Supplier Invoice search-by-nomor-faktur UI regression.
- File changed:
  - `app/src/androidTest/java/id/hyperpos/mobile/features/login/MainActivitySupplierInvoiceInstrumentedTest.kt`
- Local dirty state after proof:
  - `M app/src/androidTest/java/id/hyperpos/mobile/features/login/MainActivitySupplierInvoiceInstrumentedTest.kt`
- Diff:
  - 1 file changed
  - 41 insertions

Latest local fixture proof:

- `supplier_invoice_search_id=3934b9a3-604b-40ee-a7ef-d6c320c6d8f3`
- `supplier_invoice_search_nomor_faktur=SI-BL-20260502-067`

Latest local verification proof:

- `./gradlew :app:assembleDebug`
  - Result: `BUILD SUCCESSFUL in 1s`
- `./gradlew :app:connectedDebugAndroidTest -Pandroid.testInstrumentationRunnerArguments.class=id.hyperpos.mobile.features.login.MainActivitySupplierInvoiceInstrumentedTest`
  - Result: `BUILD SUCCESSFUL in 1m 4s`
  - Device: `23053RN02A - 15`
  - Tests: 5

Previously proven in this flow:

- Supplier Invoice role-aware UI test:
  - 4 tests
  - `BUILD SUCCESSFUL in 1m 5s`
- Product Search role-aware regression rerun:
  - 3 tests
  - `BUILD SUCCESSFUL in 51s`
- Supplier Invoice API regression:
  - 2 tests
  - `BUILD SUCCESSFUL in 32s`

## Locked mobile UI direction

### Kasir

After login role `kasir`:

- Show only Product Search.
- Product Search displays:
  - product label/name
  - stock
  - selling price
- Do not show purchase price.
- Do not show Supplier Invoice UI.

### Admin

After login role `admin`:

- Show only Supplier Invoice flow.
- Do not show Product Search/form kasir.
- Supplier Invoice flow:
  - list supplier invoices
  - search by `nomor_faktur`
  - open/detail invoice
  - show simplified payment status:
    - `Lunas`
    - `Belum lunas`
- No partial payment in mobile.
- No complex web payment flow in mobile.
- If proof/media already exists and invoice is paid, show media.
- If proof/media does not exist and invoice is unpaid, upload media from camera/gallery.
- After media upload succeeds, backend must auto-set invoice as paid/lunas in DB.
- No media share/send feature for now.
- No WhatsApp/share/download feature for now unless explicitly reopened.

## Completed Kotlin scope

### Auth/session

Closed before this handoff:

- Login works with stored token.
- `/api/v1/me` works using stored token.
- Logout revokes backend token.
- Logout clears local encrypted token.
- Revoked/invalid token clears local token and resets UI.
- Logout button appears after login.
- Logout button hides after logout.
- Authenticated UI hides after logout/session invalid.
- No raw API token printed.

### Product Search

Closed before and preserved after role-aware cleanup:

- Product Search API client exists.
- Product Search UI works for kasir.
- Product Search shows product label, stock, and selling price.
- Product Search does not show Supplier Invoice for kasir.
- Product Search role-aware regression passed after role-aware UI cleanup.

### Supplier Invoice API foundation

Implemented in Kotlin:

Domain models:

- `MobileSupplierInvoiceListRow`
- `MobileSupplierInvoiceSummary`
- `MobileSupplierInvoiceLine`

Application layer:

- `SupplierInvoiceListResult`
- `SupplierInvoiceDetailResult`
- `ListSupplierInvoicesUseCase`
- `GetSupplierInvoiceDetailUseCase`

Port:

- `SupplierInvoiceApiPort`

Adapter:

- `OkHttpSupplierInvoiceApiClient`

Instrumentation coverage:

- Admin can read supplier invoice list/detail using stored token.
- Invalid token returns unauthenticated for supplier invoice list/detail.
- No raw token printed.

### Supplier Invoice UI

Implemented in Kotlin:

- Admin-only Supplier Invoice container.
- Kasir cannot see Supplier Invoice UI.
- Admin cannot see Product Search UI.
- Supplier Invoice search input exists:
  - `supplierInvoiceSearchInput`
- Supplier Invoice list works.
- Supplier Invoice detail works.
- Supplier Invoice list/detail displays simplified mobile status:
  - `Status pembayaran: Lunas`
  - `Status pembayaran: Belum lunas`
- Logout resets Supplier Invoice list/detail state.
- Revoked token clears Supplier Invoice UI state.
- Search by nomor faktur regression locally green:
  - search fixture `SI-BL-20260502-067`
  - test asserts UI displays `Nomor faktur: SI-BL-20260502-067`

## Current known files touched in Kotlin flow

Main Kotlin source files involved:

- `app/src/main/java/id/hyperpos/mobile/features/login/MainActivity.kt`
- `app/src/main/res/layout/activity_main.xml`
- `app/src/main/res/values/strings.xml`
- `app/src/main/java/id/hyperpos/mobile/adapters/http/OkHttpSupplierInvoiceApiClient.kt`
- `app/src/main/java/id/hyperpos/mobile/application/ports/SupplierInvoiceApiPort.kt`
- `app/src/main/java/id/hyperpos/mobile/application/procurement/GetSupplierInvoiceDetailUseCase.kt`
- `app/src/main/java/id/hyperpos/mobile/application/procurement/ListSupplierInvoicesUseCase.kt`
- `app/src/main/java/id/hyperpos/mobile/application/procurement/SupplierInvoiceDetailResult.kt`
- `app/src/main/java/id/hyperpos/mobile/application/procurement/SupplierInvoiceListResult.kt`
- `app/src/main/java/id/hyperpos/mobile/domain/procurement/MobileSupplierInvoiceLine.kt`
- `app/src/main/java/id/hyperpos/mobile/domain/procurement/MobileSupplierInvoiceListRow.kt`
- `app/src/main/java/id/hyperpos/mobile/domain/procurement/MobileSupplierInvoiceSummary.kt`

Instrumentation files involved:

- `app/src/androidTest/java/id/hyperpos/mobile/adapters/http/OkHttpSupplierInvoiceApiClientInstrumentedTest.kt`
- `app/src/androidTest/java/id/hyperpos/mobile/features/login/MainActivitySupplierInvoiceInstrumentedTest.kt`
- `app/src/androidTest/java/id/hyperpos/mobile/features/login/MainActivityProductSearchInstrumentedTest.kt`

## Important backend/mobile contract gap

Payment proof/media upload is not safe to implement in Kotlin yet.

Observed local DB proof:

- `supplier_payments_columns=id,supplier_invoice_id,amount_rupiah,paid_at,proof_status,proof_storage_path`
- Query for non-reversed supplier payment returned:
  - `supplier_payment_id=NONE`

Known backend/mobile proof upload route from earlier source read:

- `POST /api/v1/supplier-payments/{supplierPaymentId}/proofs`

Conflict:

- Existing upload route requires `supplierPaymentId`.
- Locked mobile behavior wants invoice-based flow:
  - select supplier invoice
  - upload media
  - backend auto-lunas
- Current Android detail/list flow does not have a proven valid `supplier_payment_id`.
- There is no local supplier payment row available for instrumentation proof.

Decision:

- Do not fake upload proof in Kotlin with hardcoded or missing payment IDs.
- Do not implement payment proof UI until backend contract supports mobile flow safely.
- Required backend contract direction is likely one of:
  - endpoint by `supplier_invoice_id` that creates/records payment and proof atomically
  - detail API exposes an upload target/payment id
  - backend creates supplier payment during proof upload and auto-sets invoice as lunas

## Current gaps

- Supplier payment proof/media upload is not implemented.
- Supplier payment proof/media viewer is not implemented.
- No camera/gallery upload integration exists yet.
- No auto-lunas media upload backend contract is proven yet.
- No notification flow exists yet:
  - stock below critical point
  - due supplier invoice
- Current latest local change, search-by-nomor-faktur regression, may still need owner commit/push if not already persisted.

## Next safest step

Recommended next step:

1. Do not continue payment proof upload yet.
2. Define backend/mobile contract for invoice-based media upload with auto-lunas behavior.
3. After backend contract is locked and verified, implement Kotlin in this order:
   - domain/application result for upload proof by invoice
   - port
   - OkHttp multipart adapter
   - focused instrumentation proof
   - then UI camera/gallery upload

If staying Kotlin-only temporarily:

- Keep current role-aware UI and Supplier Invoice search/detail proof.
- Avoid adding payment proof upload UI that cannot submit safely.
- Avoid adding fake disabled UI unless explicitly accepted as placeholder.

## Progress

Final Goal Progress: 25%.

Android admin supplier invoice flow: 92%.

Breakdown:

- Auth/session handling: 100%.
- Product Search foundation: 100%.
- Product Search role-aware regression: 100%.
- Supplier Invoice API foundation: 100%.
- Supplier Invoice list UI: 100%.
- Supplier Invoice detail UI: 100%.
- Supplier Invoice role-aware UI foundation: 100%.
- Supplier Invoice search-by-nomor-faktur UI regression: 100% locally verified.
- Payment proof/media upload: 0%, blocked by backend contract/data gap.
- Notification flow: 0%, not started.

## Session Context Health

Risk: 72%.

Mini-summary:

- Locked mobile UI: kasir only Product Search, admin only Supplier Invoice.
- No partial payment in Android.
- Media upload should auto-lunas later.
- Current Kotlin UI/API proof is green.
- Current local Kotlin dirty change is only Supplier Invoice search-by-nomor-faktur UI regression test.
- Next safe architectural decision is backend contract for invoice-based media upload.
