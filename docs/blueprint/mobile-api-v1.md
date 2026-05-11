# HyperPOS Mobile API v1 Blueprint

Status: Draft 0 - pending owner answers
Scope: Companion mobile app for cashier product lookup and admin supplier invoice/payment proof workflow
Date: 2026-05-11

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

- API auth mechanism is not finalized.
- Kotlin UI toolkit is not finalized.
- HTTP client is not finalized.
- Token storage method is not finalized.
- Product zero-stock behavior is not finalized.
- Invoice search fields are not finalized.
- Payment proof workflow may be upload-only or create-payment-plus-upload.
- Notification mechanism is not finalized.
- Production distribution method is not finalized.

## 15. Next Step

Owner answers Q1-Q15.

After answers:
1. Lock API contract v1.
2. Create Laravel API route blueprint.
3. Implement auth API first.
4. Implement product search API.
5. Implement Kotlin skeleton installable on physical Redmi 12.
