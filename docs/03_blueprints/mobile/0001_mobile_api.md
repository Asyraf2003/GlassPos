# HyperPOS Mobile API v1 Blueprint

Status: Draft 5 - owner decisions locked; Mobile API auth, cashier product search, admin supplier invoice read API, supplier payment proof upload/view API, and Kotlin Android skeleton build/install baseline locally verified
Scope: Companion mobile app for cashier product lookup and admin supplier invoice/payment proof workflow
Date: 2026-05-12

## 1. Final Goal

Build a small Android mobile companion app backed by Laravel API.

The mobile app is not a full POS replacement.

It supports:

1. Login.
2. Cashier role:
   - Search product.
   - View stock.
   - View selling price.
3. Admin role:
   - Search supplier invoices.
   - Filter supplier invoices by paid/unpaid/partial status.
   - View supplier invoice detail.
   - Upload payment proof photo/file.
   - Later: due invoice notification.

Laravel remains the source of truth for:
- auth
- roles
- product data
- stock
- selling price
- supplier invoice status
- supplier payment proof
- audit trail
- permission decision

Kotlin Android is only a client application.

## 2. Non-Goal

The mobile app must not implement:

- full cashier transaction entry
- stock mutation
- product creation/update
- supplier invoice creation/update
- payment amount mutation unless explicitly approved later
- refund flow
- offline-first sync
- local stock calculation
- local financial calculation
- local permission decision as source of truth
- PDF/report generation
- complex dashboard
- emulator-dependent workflow

## 3. Locked Working Direction

### 3.1 Laravel API

Build custom API endpoints from zero as a new transport adapter.

Do not expose Blade/web controllers directly as mobile API.

API controllers must be thin:
- validate request
- resolve actor/session
- call application use case/service
- map result to JSON/binary response

Application/domain rules stay in Laravel existing use cases/services/ports where possible.

### 3.2 Kotlin Android

Use Kotlin with hexagonal/light-clean architecture.

Start with one Android app module for low PC overhead.

Boundary is enforced by package/import rules first, not by Gradle multi-module.

No emulator. Development target is physical Redmi 12 through USB debugging.

### 3.3 Package Policy

Goal: minimal dependencies, no strange package pile.

Allowed by default:
- Kotlin language
- Android Gradle Plugin
- AndroidX core/appcompat or minimal AndroidX required by chosen UI
- Kotlin coroutines only if async/network implementation requires it and impact is accepted
- HTTP library only after owner decision

Pending decision:
- Retrofit vs raw OkHttp vs HttpURLConnection
- Jetpack Compose vs XML/ViewBinding
- DataStore/EncryptedSharedPreferences vs custom encrypted token storage wrapper
- WorkManager vs custom alarm/scheduled check for due invoice notification

Rule:
Any dependency must be justified by:
- performance impact
- APK size impact
- maintenance risk
- security impact
- amount of code it avoids
- replacement difficulty


## 3.4 Workspace Layout

Locked workspace layout:

- Laravel API/domain source: `/home/asyraf/Code/laravel/bengkel2/app`
- Kotlin Android app source: `/home/asyraf/Code/laravel/bengkel2/kotlin`

Rules:

- Android/Kotlin project files must not be created inside the Laravel app repo.
- Laravel API work is executed from `/home/asyraf/Code/laravel/bengkel2/app`.
- Kotlin Android work is executed from `/home/asyraf/Code/laravel/bengkel2/kotlin`.
- Laravel remains the source of truth for auth, role, permissions, product data, supplier invoices, proof attachments, and audit decisions.
- Kotlin remains a client only.

## 4. Role Scope

### 4.1 Cashier

Allowed:
- login
- product search
- view available stock
- view default/minimum selling price

Forbidden:
- supplier invoice access
- payment proof access
- admin due invoice access
- product mutation
- stock mutation

### 4.2 Admin

Allowed:
- login
- supplier invoice search
- supplier invoice paid/unpaid/partial filter
- supplier invoice detail
- supplier payment proof upload/view
- due invoice list/notification later

Forbidden by default:
- supplier invoice mutation from mobile
- supplier payment amount mutation from mobile unless explicitly approved later
- product mutation
- stock mutation

## 5. API Endpoint Draft

Base prefix:

/api/v1

### 5.1 Auth

POST /api/v1/auth/login

Request draft:
{
  "username": "string",
  "password": "string",
  "device_name": "string"
}

POST /api/v1/auth/logout

GET /api/v1/me


### 5.1.1 Auth Implementation Decision

Decision: raw custom bearer token, not Sanctum/JWT/session cookie.

Reason:

- HyperPOS Mobile API v1 is a first-party companion app, not a public third-party API platform.
- Current Laravel app does not use Sanctum.
- Raw custom token keeps dependency footprint low.
- Token lifecycle can follow HyperPOS auditability and redaction requirements from the first version.

Implemented foundation:

- `POST /api/v1/auth/login`
- `GET /api/v1/me`
- `POST /api/v1/auth/logout`
- `mobile_api_tokens` table stores only `token_hash`, never plaintext token.
- Plain token is returned only once in the login response.
- Revoked/expired/missing token returns a redacted JSON error payload.
- Actor role is resolved server-side from existing identity access source.

### 5.2 Cashier Product Search

GET /api/v1/products/search?q=...

Response draft:
{
  "success": true,
  "data": {
    "rows": [
      {
        "id": "string",
        "label": "string",
        "kode_barang": "string|null",
        "nama_barang": "string",
        "merek": "string|null",
        "ukuran": "string|null",
        "available_stock": 0,
        "default_unit_price_rupiah": 0,
        "minimum_unit_price_rupiah": 0
      }
    ]
  },
  "meta": {
    "query": "string",
    "limit": 20
  }
}

Performance target draft:
- p95 under 300 ms on LAN/local Wi-Fi
- p95 under 700 ms over normal mobile internet
- max 20 rows per request by default
- minimum query length 2 characters
- no heavy relation payload

Implemented behavior:

- `GET /api/v1/products/search`
- Bearer token is required.
- Access is cashier-only for v1.
- Admin mobile product search is rejected with `CASHIER_ONLY`.
- Query shorter than 2 characters returns empty rows.
- Product search returns split fields:
  - `kode_barang`
  - `nama_barang`
  - `merek`
  - `ukuran`
- Product search includes:
  - `available_stock`
  - `default_unit_price_rupiah`
  - `minimum_unit_price_rupiah`
- Zero-stock products are shown with `available_stock = 0`.
- Response meta includes `query` and `limit`.

Implementation note:

- Product search reuses existing `CashierNoteProductLookupData`.
- It does not expose or reuse the web `ProductLookupController` directly.
- It intentionally does not copy the web behavior that skips zero-stock products.
- Current result limit is applied in the application layer with `array_slice` at 20 rows.
- Future improvement: query-level limit in `ProductReaderPort` or a dedicated mobile product reader for large catalogs.

### 5.3 Admin Supplier Invoice Search

GET /api/v1/supplier-invoices?search=&payment_status=paid|unpaid|partial&page=1&per_page=20

Response draft:
{
  "success": true,
  "data": {
    "rows": [
      {
        "id": "string",
        "invoice_number": "string",
        "supplier_name": "string",
        "invoice_date": "YYYY-MM-DD",
        "due_date": "YYYY-MM-DD|null",
        "total_rupiah": 0,
        "paid_rupiah": 0,
        "remaining_rupiah": 0,
        "payment_status": "paid|unpaid|partial",
        "is_due": false
      }
    ]
  },
  "meta": {
    "page": 1,
    "per_page": 20,
    "has_next": false
  }
}

Performance target draft:
- p95 under 500 ms on LAN/local Wi-Fi
- p95 under 1000 ms over normal mobile internet
- indexed search/filter required before production use

### 5.4 Admin Supplier Invoice Detail

GET /api/v1/supplier-invoices/{supplierInvoiceId}

Response draft:
{
  "success": true,
  "data": {
    "invoice": {},
    "items": [],
    "payments": [],
    "proof_attachments": []
  }
}

### 5.5 Payment Proof Upload

POST /api/v1/supplier-payments/{supplierPaymentId}/proofs

Content-Type:
multipart/form-data

Field:
proof_files[]

Constraints draft:
- max 3 files per upload
- allowed MIME: image/jpeg, image/png, application/pdf
- max 2 MB per file initially
- server must re-detect MIME
- server must sanitize filename
- server must reject unsupported content
- upload action must be audited

### 5.6 Payment Proof Attachment View

GET /api/v1/supplier-payment-proof-attachments/{attachmentId}

Response:
binary file response with safe Content-Type, Content-Disposition, and X-Content-Type-Options: nosniff

### 5.7 Due Invoice

Phase: later, after base invoice API and upload proof are stable.

GET /api/v1/supplier-invoices/due?within_days=7

Notification phase 1:
- local notification from app periodic check

Notification phase 2:
- push notification only if needed

## 6. API Output Policy

Default JSON envelope:

Success:
{
  "success": true,
  "data": {},
  "meta": {}
}

Failure:
{
  "success": false,
  "message": "Safe user-facing message",
  "errors": {
    "field": ["CODE_OR_MESSAGE"]
  }
}

Do not leak:
- stack trace
- SQL
- file path
- internal exception
- raw storage path
- token value in logs

## 7. Latency Policy

Targets are draft until measured.

Product search:
- request payload small
- response max 20 rows
- no unnecessary nested objects
- avoid N+1 query
- use existing product/inventory lookup service if safe
- add DB index only based on query proof

Invoice search:
- response max 20 rows by default
- include only list fields
- detail endpoint carries larger payload
- no attachment binary in JSON
- use status/date filters that can be indexed

Upload proof:
- optimize correctness over speed
- do not compress client-side in v1 unless measurement proves needed
- server validates MIME and size
- server cleanup on failed DB transaction

## 8. Kotlin Architecture Draft

Package boundary:

id.hyperpos.mobile.domain
id.hyperpos.mobile.application
id.hyperpos.mobile.application.ports
id.hyperpos.mobile.adapters.http
id.hyperpos.mobile.adapters.storage
id.hyperpos.mobile.adapters.file
id.hyperpos.mobile.features.login
id.hyperpos.mobile.features.cashierproductsearch
id.hyperpos.mobile.features.admininvoices
id.hyperpos.mobile.features.paymentproofupload
id.hyperpos.mobile.shared

Import rules:

domain:
- must not import android.*
- must not import androidx.*
- must not import retrofit2.*
- must not import okhttp3.*
- must not depend on JSON DTO

application:
- must not import UI, Activity, Context, Retrofit, OkHttp
- may depend on domain and ports only

features:
- may import Android UI/ViewModel
- call use cases only
- no direct HTTP calls

adapters.http:
- owns HTTP implementation
- owns request/response DTO
- maps DTO to domain/application models

adapters.storage:
- owns token/session persistence

adapters.file:
- owns Android Uri/file handling for proof upload

## 9. Kotlin Initial Use Cases

Auth:
- LoginUseCase
- LogoutUseCase
- GetCurrentSessionUseCase

Cashier:
- SearchProductsUseCase

Admin:
- SearchSupplierInvoicesUseCase
- GetSupplierInvoiceDetailUseCase
- GetDueSupplierInvoicesUseCase
- UploadSupplierPaymentProofUseCase

## 10. Development Workflow

Device:
- physical Redmi 12
- USB debugging
- no emulator

Build:
- Neovim + Gradle CLI
- Android Studio only optional for SDK manager or emergency inspection

Commands:
- ./gradlew assembleDebug
- ./gradlew installDebug
- adb devices
- adb logcat

## 11. Laravel Test Matrix Draft

Auth:
- login success admin
- login success cashier
- login invalid password
- logout revokes token
- unauthorized request rejected

Product API:
- cashier can search products
- admin can search products only if explicitly allowed
- query under 2 chars returns empty rows
- zero-stock product hidden or shown based on owner decision
- response shape stable

Supplier Invoice API:
- admin can search invoices
- cashier cannot search invoices
- paid/unpaid/partial filters work
- pagination works
- detail returns safe payload only

Payment Proof API:
- admin can upload allowed file
- cashier cannot upload supplier proof
- invalid MIME rejected
- oversized file rejected
- max file count enforced
- stored file cleaned up when DB transaction fails
- attachment response uses safe headers

Security:
- no stack trace in JSON
- no raw storage path in JSON
- token not logged
- permission denial is audited if required

## 12. Kotlin Test Matrix Draft

Domain:
- ProductSearchResult mapping
- SupplierInvoice payment status model
- PaymentProofFile validation if used

Application:
- LoginUseCase success/failure
- SearchProductsUseCase validates minimum query
- SearchSupplierInvoicesUseCase passes filters
- UploadSupplierPaymentProofUseCase validates file count before port call if decided

Adapters:
- HTTP maps success JSON
- HTTP maps failure JSON
- token header attached
- multipart proof request shape

UI:
- login loading/error/success
- cashier product search debounce/loading/result/empty/error
- admin invoice list filter paid/unpaid/partial
- upload proof progress/success/error

## 13. Owner Questions Before Final Contract

Q1. Login identifier should use which field?
Options:
A. username
B. email
C. existing login field in current Laravel users table

Answer:
C. existing login field in current Laravel users table

Decision: C
Reason: use the existing Laravel identity source and avoid parallel mobile-only identity.
Q2. Should admin also be allowed to use product search on mobile?
Options:
A. yes
B. no, cashier only

Answer:
B. no, cashier only

Decision: B
Reason: keep mobile role scope strict. Cashier gets product lookup. Admin gets supplier invoice workflow.
Q3. For product search, should zero-stock products be hidden or shown?
Options:
A. hidden
B. shown with available_stock = 0
C. configurable by role

Answer:
B. shown with available_stock = 0

Decision: B
Reason: mobile product search is for checking product, stock, and price, not direct transaction entry.
Q4. Product search response should include only selling price, or also minimum price?
Options:
A. selling price only
B. selling price + minimum price
C. selling price + minimum price + brand/code/size split fields

Answer:
C. selling price + minimum price + brand/code/size split fields

Decision: C
Reason: keep UI flexible without parsing label strings. Payload remains small.
Q5. Supplier invoice search term should match what?
Options:
A. invoice number only
B. supplier name only
C. invoice number + supplier name
D. invoice number + supplier name + item/product keyword

Answer:
C. invoice number + supplier name

Decision: C
Reason: enough for v1 and safer for latency than item/product keyword search.
Q6. Payment status terms should use which API values?
Options:
A. paid/unpaid/partial
B. lunas/belum_lunas/sebagian
C. existing backend terms if already locked

Answer:
C. existing backend terms if already locked

Decision: C
Reason: API must not invent terms that conflict with backend domain status.
Q7. Does mobile admin only upload proof for an existing supplier payment, or can mobile also create the supplier payment amount?
Options:
A. upload proof only
B. create payment + upload proof
C. both, but separate phases

Answer:
A. upload proof only

Decision: A
Reason: v1 must not mutate payment amount from mobile. Proof upload is the only write flow.
Q8. Proof upload source:
Options:
A. camera only
B. gallery/file picker only
C. camera + gallery/file picker

Answer:
C. camera + gallery/file picker

Decision: C
Reason: real operators may capture directly or upload existing WhatsApp/gallery/file proof.
Q9. Proof max file size:
Options:
A. 2 MB current server behavior
B. 5 MB
C. other

Answer:
A. 5 MB current server behavior

Decision: A
Reason: start from current server constraint and increase only after measured need.
Q10. Due notification should happen:
Options:
A. local notification from app periodic check
B. server push notification
C. start with due list only, notification later

Answer:
C. start with due list only, notification later

Decision: C
Reason: due list API is lower risk. Notification becomes phase 2.
Q11. UI priority:
Options:
A. fastest raw UI, XML/ViewBinding
B. faster development, Compose
C. decide after prototype benchmark

Answer:
A. fastest raw UI, XML/ViewBinding

Decision: A
Reason: prioritize raw UI performance, lower build overhead, and better fit for low-RAM development machine.
Q12. HTTP client policy:
Options:
A. raw HttpURLConnection, minimal dependency, more manual code
B. OkHttp only, stable low-level HTTP, less manual pain
C. Retrofit + OkHttp, faster implementation, more abstraction

Answer:
B. OkHttp only, stable low-level HTTP, less manual pain

Decision: B
Reason: lighter and more explicit than Retrofit while avoiding excessive manual HttpURLConnection code.
Q13. Token storage policy:
Options:
A. encrypted Android storage wrapper
B. plain storage only for local dev, encrypted before production
C. custom encrypted storage from first version

Answer:
C. custom encrypted storage from first version

Decision: C
Reason: token storage is security-sensitive and should be production-minded from v1.
Q14. Should the API use same Laravel app/domain/database, or separate API subdomain later?
Options:
A. same app path /api/v1
B. api subdomain later
C. decide after deployment plan

Answer:
A. same app path /api/v1

Decision: A
Reason: keep deployment simple and reuse the current Laravel app/domain/database.
Q15. Target first production install:
Options:
A. manual signed APK through USB/file
B. GitHub Release/private link
C. Play Store internal testing

Answer:
A. manual signed APK through USB/file

Decision: A
Reason: simplest controlled internal production path for early rollout.
## 14. Current Gaps

- Due invoice list API is not implemented yet.
- Kotlin Android skeleton is created outside the Laravel app repo and locally build/install verified; feature UI, API integration, and encrypted token storage are not implemented yet.
- Android encrypted token storage is not implemented yet.
- Full global Laravel test suite has not been run for the Mobile API auth and product search foundation.
- Browser/manual QA has not been run for the Mobile API auth and product search foundation.
- API sanity curl against a running local server has not been run for the Mobile API auth and product search foundation.
- Product search currently uses application-layer `array_slice` limit; query-level limit may be needed for large catalogs.
- Notification remains phase 2.

## 15. Next Step

Next active implementation step:

1. Record Kotlin Android skeleton build/install proof, then start encrypted token storage blueprint before login API integration.
2. Keep Kotlin files outside Laravel app.
3. Use locked Kotlin decisions:
   - XML/ViewBinding
   - OkHttp only
   - custom encrypted token storage from v1
   - first production install manual signed APK via USB/file
4. First Kotlin scope should stay small:
   - project skeleton
   - package boundary
   - build/install proof on physical Redmi 12
   - no feature UI until skeleton proof is green.
5. Due invoice list API remains backend follow-up after Kotlin skeleton or before admin due UI, whichever becomes safer from proof.

## 16. Implementation Proof Log

### 2026-05-11 - Mobile API Auth Foundation

Status: Implemented and locally verified.

Commit proof:

- `be57014e (HEAD -> main, origin/main, origin/HEAD) commit 1854`

Implemented route proof:

- `POST api/v1/auth/login`
- `POST api/v1/auth/logout`
- `GET|HEAD api/v1/me`

Verification proof:

- Syntax check passed for all new/modified Mobile API auth files.
- Targeted Mobile API auth test: `7 passed (25 assertions)`.
- Focused web auth + identity access blast-radius: `15 passed (63 assertions)`.
- `git diff --check` produced no output.
- Raw token inspection anchors show `mobile_api_tokens.token_hash` storage and no plaintext token persistence in the database adapter.

Verified scope:

- Mobile API login success for admin and kasir.
- Invalid credential rejection.
- User without actor access rejection.
- `/api/v1/me` requires bearer token.
- `/api/v1/me` returns current actor for valid token.
- Logout revokes current token.
- Revoked token is rejected after logout.
- Existing web auth and identity access middleware remain green in focused tests.

Not verified in this proof:

- Full global test suite.
- Product search API.
- Supplier invoice API.
- Payment proof upload API.
- Kotlin Android client.
- Browser/manual QA.

### 2026-05-12 - Mobile API Cashier Product Search

Status: Implemented and locally verified.

Commit and route proof:

- Handoff records latest product search verification at `6940ae63`, commit `1858`.
- Current repo verification showed `924394a4 (HEAD -> main, origin/main, origin/HEAD) commit 1859`.
- Current route list still includes `GET|HEAD api/v1/products/search`.

Implemented route proof:

- `GET|HEAD api/v1/products/search`

Verification proof:

- Targeted Mobile API auth + product tests: `11 passed (36 assertions)`.
- Focused web product/inventory/database blast-radius tests: `6 passed (30 assertions)`.
- `git diff --check` produced no output in the prior product search verification.
- Source anchors showed `/products/search` route registration.
- Source anchors showed `CASHIER_ONLY` for non-kasir role.
- Source anchors showed split fields and `available_stock` in the product search handler.
- Test anchors showed zero-stock product coverage with `available_stock = 0`.

Verified scope:

- Bearer token required.
- Cashier can search products.
- Admin mobile product search is rejected in v1.
- Short query returns empty rows.
- Product payload includes `id`, `label`, `kode_barang`, `nama_barang`, `merek`, `ukuran`, `available_stock`, `default_unit_price_rupiah`, and `minimum_unit_price_rupiah`.
- Zero-stock product is included with `available_stock = 0`.
- Response meta includes `query` and `limit`.

Not verified in this proof:

- Full global test suite.
- API sanity curl against a running local server.
- Supplier invoice API.
- Payment proof upload/view API.
- Kotlin Android client.
- Browser/manual QA.

### 2026-05-12 - Mobile API Admin Supplier Invoice Read API

Status: Implemented and locally verified.

Implemented route proof:

- `GET|HEAD api/v1/supplier-invoices`
- `GET|HEAD api/v1/supplier-invoices/{supplierInvoiceId}`

Verification proof:

- Syntax checks passed for supplier invoice Mobile API controllers and route file in the implementation step.
- Targeted supplier invoice Mobile API test after route/controller patch: `4 passed (11 assertions)`.
- Targeted supplier invoice Mobile API test after success-path fixture: `6 passed (17 assertions)`.
- Focused Mobile API auth + product + procurement tests: `17 passed (53 assertions)`.
- `git diff --check` produced no output.
- Route proof showed 6 `/api/v1` routes.

Verified scope:

- Supplier invoice list requires bearer token.
- Cashier mobile token is rejected from supplier invoice list.
- Admin can read empty supplier invoice list using backend `payment_status=outstanding`.
- Admin supplier invoice detail not-found returns safe JSON with `SUPPLIER_INVOICE_NOT_FOUND`.
- Admin can read real supplier invoice list row from `supplier_invoice_list_projection`.
- Admin can read supplier invoice detail summary and current lines.
- Mobile supplier invoice API reuses existing procurement read-side handlers/ports/adapters.

Not verified in this proof:

- Full global Laravel test suite.
- API sanity curl against a running local server.
- Supplier payment proof upload/view API.
- Due invoice list API.
- Kotlin Android client.
- Browser/manual QA.

### 2026-05-12 - Mobile API Supplier Payment Proof Upload and View

Status: Implemented and locally verified.

Implemented route proof:

- `POST api/v1/supplier-payments/{supplierPaymentId}/proofs`
- `GET|HEAD api/v1/supplier-payment-proof-attachments/{attachmentId}`

Verification proof:

- Syntax checks passed for supplier payment proof Mobile API controllers and route file in the implementation step.
- RED targeted payment proof Mobile API test before route/controller patch: `6 failed`, all from missing route `404`.
- Targeted payment proof Mobile API test after actor ID fix: `6 passed (22 assertions)`.
- Focused Mobile API auth + product + procurement + payment proof tests: `23 passed (75 assertions)`.
- `git diff --check` produced no output.
- Route proof showed 8 `/api/v1` routes.

Verified scope:

- Supplier payment proof upload requires bearer token.
- Cashier mobile token is rejected from supplier payment proof upload.
- Admin can upload supplier payment proof to an existing supplier payment.
- Supplier payment proof attachment view requires bearer token.
- Cashier mobile token is rejected from supplier payment proof attachment view.
- Admin can view supplier payment proof attachment with safe headers.
- Attachment response keeps `X-Content-Type-Options: nosniff`.
- Mobile supplier payment proof upload reuses existing `AttachSupplierPaymentProofHandler`.
- Mobile supplier payment proof view reuses existing `GetSupplierPaymentProofAttachmentFileHandler`.

Not verified in this proof:

- Full global Laravel test suite.
- API sanity curl against a running local server.
- Due invoice list API.
- Kotlin Android client.
- Browser/manual QA.

### 2026-05-12 - Kotlin Android Skeleton Build and Device Install

Status: Implemented and locally verified.

Scope:

- Kotlin Android skeleton created under /home/asyraf/Code/laravel/bengkel2/kotlin.
- Laravel app repo remains separate at /home/asyraf/Code/laravel/bengkel2/app.
- Kotlin app is client-only.
- XML and ViewBinding are enabled.
- OkHttp dependency is present.
- Package boundary placeholders are present for domain, application ports, HTTP adapter, storage adapter, file adapter, feature packages, and shared code.

Proof:

- Gradle uses /usr/lib/jvm/java-17-openjdk through org.gradle.java.home.
- ./gradlew clean assembleDebug: BUILD SUCCESSFUL, 37 actionable tasks.
- Debug APK created at app/build/outputs/apk/debug/app-debug.apk, 3.9M.
- adb detected device 52344d4a7d7c.
- ./gradlew installDebug installed app-debug.apk on 23053RN02A - 15.
- Follow-up smoke build: ./gradlew assembleDebug BUILD SUCCESSFUL in 2s.

Remaining gaps:

- Manual app launch/UI confirmation is not proven.
- Signed release APK is not proven.
- Encrypted token storage is not implemented.
- Login/API integration is not implemented.
- Kotlin workspace is not inside the Laravel app git repo and is not a git repo by itself yet.
