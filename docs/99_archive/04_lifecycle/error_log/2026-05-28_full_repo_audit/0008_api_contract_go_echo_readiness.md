# ERROR LOG 0008 - API CONTRACT AND GO ECHO READINESS

## FACT
- Mobile API v1 sudah punya baseline auth yang hijau dari owner proof: `php artisan test --filter=MobileApiAuthenticationFeatureTest` lulus `7 passed, 25 assertions`.
- `php artisan route:list` menunjukkan surface `api/v1` yang relevan untuk mobile: `auth/login`, `auth/logout`, `me`, `products/search`, `supplier-invoices`, `supplier-invoices/{supplierInvoiceId}`, `supplier-invoices/{supplierInvoiceId}/payment-proof`, `supplier-payments/{supplierPaymentId}/proofs`, dan `supplier-payment-proof-attachments/{attachmentId}`.
- `composer test` juga hijau penuh: `1112 passed, 2 skipped, 6205 assertions`.
- Blueprint mobile menegaskan Laravel tetap source of truth untuk auth, role, permissions, product data, supplier invoices, proof attachments, dan audit decisions. Kotlin/Go client hanya consumer.

## OWNER PROOF
- `php artisan test --filter=MobileApiAuthenticationFeatureTest` PASS: `7 passed`, `25 assertions`.
- `php artisan route:list` menampilkan endpoint `api/v1` yang menjadi inventory kontrak mobile.
- `composer test` PASS: `1112 passed`, `2 skipped`, `6205 assertions`.

## SOURCE EVIDENCE
- [`routes/api.php`](../../../../routes/api.php)
- [`app/Adapters/In/Http/Middleware/MobileApi/AuthenticateMobileApiToken.php`](../../../../app/Adapters/In/Http/Middleware/MobileApi/AuthenticateMobileApiToken.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Auth/LoginMobileApiController.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Auth/MeMobileApiController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Auth/MeMobileApiController.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Auth/LogoutMobileApiController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Auth/LogoutMobileApiController.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Product/SearchMobileApiProductsController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Product/SearchMobileApiProductsController.php)
- [`app/Application/MobileApi/Product/UseCases/SearchMobileApiProductsHandler.php`](../../../../app/Application/MobileApi/Product/UseCases/SearchMobileApiProductsHandler.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Procurement/ListMobileApiSupplierInvoicesController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Procurement/ListMobileApiSupplierInvoicesController.php)
- [`app/Application/Procurement/UseCases/GetProcurementInvoiceTableHandler.php`](../../../../app/Application/Procurement/UseCases/GetProcurementInvoiceTableHandler.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Procurement/ShowMobileApiSupplierInvoiceController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Procurement/ShowMobileApiSupplierInvoiceController.php)
- [`app/Application/Procurement/UseCases/GetProcurementInvoiceDetailHandler.php`](../../../../app/Application/Procurement/UseCases/GetProcurementInvoiceDetailHandler.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierInvoicePaymentProofController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierInvoicePaymentProofController.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierPaymentProofController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Procurement/UploadMobileApiSupplierPaymentProofController.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Procurement/ShowMobileApiSupplierPaymentProofAttachmentController.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Procurement/ShowMobileApiSupplierPaymentProofAttachmentController.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Procurement/Support/MobileSupplierPaymentProofAttachmentResponseFactory.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Procurement/Support/MobileSupplierPaymentProofAttachmentResponseFactory.php)
- [`app/Adapters/In/Http/Controllers/Api/V1/Procurement/Support/MobileSupplierPaymentProofUploadRequest.php`](../../../../app/Adapters/In/Http/Controllers/Api/V1/Procurement/Support/MobileSupplierPaymentProofUploadRequest.php)
- [`docs/03_blueprints/mobile/0001_mobile_api.md`](../../../../docs/03_blueprints/mobile/0001_mobile_api.md)
- [`tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php`](../../../../tests/Feature/MobileApi/Auth/MobileApiAuthenticationFeatureTest.php)
- [`tests/Feature/MobileApi/Product/MobileApiProductSearchFeatureTest.php`](../../../../tests/Feature/MobileApi/Product/MobileApiProductSearchFeatureTest.php)
- [`tests/Feature/MobileApi/Procurement/MobileApiSupplierInvoiceReadFeatureTest.php`](../../../../tests/Feature/MobileApi/Procurement/MobileApiSupplierInvoiceReadFeatureTest.php)
- [`tests/Feature/MobileApi/Procurement/MobileApiSupplierPaymentProofFeatureTest.php`](../../../../tests/Feature/MobileApi/Procurement/MobileApiSupplierPaymentProofFeatureTest.php)

## API SURFACE INVENTORY
| Endpoint | Method | Contract Type | Boundary | Status |
| --- | --- | --- | --- | --- |
| `/api/v1/auth/login` | `POST` | JSON envelope | public auth bootstrap | proven |
| `/api/v1/auth/logout` | `POST` | JSON envelope | token revoke | proven |
| `/api/v1/me` | `GET` | JSON envelope | authenticated actor lookup | proven |
| `/api/v1/products/search` | `GET` | JSON envelope | read-only cashier product search | proven |
| `/api/v1/supplier-invoices` | `GET` | JSON envelope | read-only admin invoice list | proven |
| `/api/v1/supplier-invoices/{supplierInvoiceId}` | `GET` | JSON envelope | read-only admin invoice detail | proven |
| `/api/v1/supplier-invoices/{supplierInvoiceId}/payment-proof` | `POST` | JSON envelope | mutation/upload | proven as contract, not safe-first |
| `/api/v1/supplier-payments/{supplierPaymentId}/proofs` | `POST` | JSON envelope | mutation/upload | proven as contract, not safe-first |
| `/api/v1/supplier-payment-proof-attachments/{attachmentId}` | `GET` | binary response + fallback JSON error | attachment delivery | proven as contract, special case |

## WHAT IS PROVEN
- Mobile auth baseline hijau sudah terbukti lewat test auth yang lulus.
- Response envelope JSON konsisten di endpoint JSON yang diperiksa: pola `success`, `data`, `message`, `errors`, dan pada banyak kasus `meta`.
- `products/search` adalah jalur read-only yang aman dijadikan kandidat extraction awal: auth diperlukan, cashier-only, hanya membaca data produk dan stok, tidak ada write path.
- `supplier-invoices` list dan `supplier-invoices/{supplierInvoiceId}` adalah jalur read-only yang juga aman sebagai kandidat awal: admin-only, mengembalikan data projection/detail, tanpa mutasi.
- `supplier-payment-proof-attachments/{attachmentId}` punya kontrak biner yang eksplisit: inline untuk `pdf/jpeg/png`, `nosniff`, filename disanitasi, dan fallback `application/octet-stream` bila mime tidak aman.
- Jalur upload payment proof dan invoice payment proof memang sudah terdefinisi sebagai contract, tetapi secara risiko tidak layak jadi extraction pertama karena ini mutasi, file handling, dan status projection update.

## WHAT REMAINS GAP
- Belum ada tabel inventori kontrak yang formal per endpoint berisi request, response, error code, status code, dan contoh payload final.
- Belum ada snapshot test JSON yang dipaku per endpoint untuk semua sukses/gagal/error path.
- Belum ada proof bahwa semua endpoint akan tetap memiliki envelope seragam saat dipindahkan ke Go Echo; karena implementasi sekarang masih manual di controller per endpoint, drift tetap mungkin.
- Belum ada OpenAPI-like markdown yang mengunci kontrak sebelum migrasi Go Echo.
- Belum ada proof bahwa endpoint mutasi, attachment delivery, dan auth lifecycle aman dipindahkan sebelum kontrak read-only dipaku terlebih dahulu.

## FINDINGS
1. CONFIRMED: mobile auth baseline green.
2. CONFIRMED: API surface sudah terinventarisasi di `routes/api.php` dan proof owner.
3. CONFIRMED: JSON envelope pattern dominan pada endpoint JSON mobile API.
4. CONFIRMED: binary attachment response contract ada dan tidak boleh dipaksa menjadi JSON envelope.
5. SUSPECTED: response envelope consistency risk tetap ada karena kontrak saat ini tersebar di banyak controller dan belum ada snapshot inventory formal untuk tiap endpoint.
6. GAP: Go Echo readiness belum bisa dinyatakan penuh karena belum ada contract table dan snapshot tests yang memaku perilaku lintas endpoint.

## IMPACT
- Migrasi Go Echo yang terlalu cepat berisiko memecah kontrak response JSON, khususnya status code, error code, dan field naming.
- Attachment endpoint berisiko paling tinggi jika dianggap sama dengan JSON endpoint; padahal ini binary response dengan header keamanan spesifik.
- Jalur mutasi procurement dapat menyebabkan regression lebih mahal dibanding read-only endpoint jika dipindahkan tanpa contract snapshot.
- Karena Laravel masih source of truth, kekeliruan contract di transport layer akan langsung terlihat sebagai mismatch client, bukan sekadar masalah UI.

## CLASSIFICATION
- `CONFIRMED`: baseline auth dan route inventory.
- `CONFIRMED`: safe extraction candidate untuk `product search` dan `supplier invoice list/detail` sebagai read-only boundary.
- `CONFIRMED`: binary attachment response contract.
- `SUSPECTED`: response envelope consistency risk yang butuh snapshot test untuk dibuktikan aman.
- `GAP`: Go Echo readiness end-to-end.

## SOLUTION DIRECTION, NO IMPLEMENTATION
- Prioritaskan extraction awal pada endpoint read-only yang sudah terbukti stabil: `products/search`, `supplier-invoices`, dan `supplier-invoices/{supplierInvoiceId}`.
- Paku kontrak JSON lebih dulu lewat inventory markdown dan snapshot tests sebelum mempertimbangkan perpindahan transport.
- Perlakukan `supplier-payment-proof-attachments/{attachmentId}` sebagai special contract, bukan sekadar JSON API biasa.
- Tahan dulu jalur mutasi payment/upload/revision/inventory/audit dari migrasi awal sampai contract inventory selesai dan disetujui owner.
- Jaga agar Go Echo hanya menggantikan transport layer, bukan mengubah domain source of truth atau format kontrak tanpa keputusan eksplisit.

## SUGGESTED NEXT PROOF
- JSON snapshot tests per endpoint untuk sukses, 401, 403, 404, dan validasi.
- API contract inventory table per endpoint.
- OpenAPI-like markdown sebelum Go Echo migration.

## MINIMUM OWNER COMMANDS
- `php artisan route:list`
- `php artisan test --filter=MobileApiAuthenticationFeatureTest`
- `php artisan test --filter=MobileApiProductSearchFeatureTest`
- `php artisan test --filter=MobileApiSupplierInvoiceReadFeatureTest`
- `php artisan test --filter=MobileApiSupplierPaymentProofFeatureTest`
- `composer test`

## FINAL STATUS
- Mobile auth baseline: green.
- Read-only mobile API boundary: proven enough for candidate extraction.
- Binary attachment contract: proven and special-cased.
- Go Echo readiness: GAP, pending contract inventory dan snapshot tests yang memaku semua endpoint.
