# Supplier bank and optional payment evidence

Base: main 1e38acd7. Scope: supplier master create/edit/list and supplier payment presentation.

Audit: SupplierService.resolve owns find-or-create; supplier writers persist the domain. Supplier list uses a projection. Invoice snapshots are separate. Mobile history currently starts from attachments, omitting valid legacy payments. Direct upload already supports supplier_payment scope, including reversal guards.

Decision: additive nullable string bank columns with no backfill; extend existing domain/service/writer and synchronize the supplier projection after standalone create. Read current bank values alongside existing table queries without per-row queries. Preserve invoice snapshots and payment calculation. History starts from payments with optional attachments, retaining one row per existing attachment. Missing evidence opens canonical existing-payment upload; existing evidence downloads securely.

Sequence: master schema/domain/forms; payment presentation/history; focused regression tests; make verify. Risks: accidentally settling twice, losing leading zeros, dropping multiple attachments, ambiguous joined columns. Tests must prove these boundaries, snapshot preservation, nullable migration, and reversal behavior.

Excluded: customer transactions, stock, outstanding formula, payment/reversal semantics, auth, push, manifest, unrelated reporting. Deployment: apply the new migration before serving updated code; existing supplier bank values remain NULL until edited. No backfill.

Business clarification: bank details are only the current transfer destination, never reconstructed historical transfer data. Payment history shows payment and optional evidence without current bank details. Renaming keeps invoice name snapshots; existing current/snapshot context remains. Bank edits must leave every invoice, payment, attachment and outstanding unchanged. Desktop canonical payment action currently links to the payment page; both that page and retained modal receive current bank presentation.

## Changed files

- `app/Adapters/In/Http/Controllers/Admin/Procurement/Concerns/BuildsProcurementInvoiceDetailSummaryView.php`
- `app/Adapters/In/Http/Controllers/Admin/Supplier/CreateSupplierController.php`
- `app/Adapters/In/Http/Controllers/Admin/Supplier/UpdateSupplierController.php`
- `app/Adapters/In/Http/Requests/Procurement/UpdateSupplierRequest.php`
- `app/Adapters/Out/Procurement/Concerns/BuildsProcurementInvoiceTableRowPayload.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailPayload.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailSummaryQuery.php`
- `app/Adapters/Out/Procurement/Concerns/SupplierTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/SupplierTablePayload.php`
- `app/Adapters/Out/Procurement/DatabaseMobileSupplierHubReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseProcurementInvoiceTableReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierReaderAdapter.php`
- `app/Adapters/Out/Procurement/DatabaseSupplierWriterAdapter.php`
- `app/Application/Procurement/Services/SupplierService.php`
- `app/Application/Procurement/UseCases/CreateSupplierHandler.php`
- `app/Application/Procurement/UseCases/GetMobileSupplierHubPayloadHandler.php`
- `app/Application/Procurement/UseCases/UpdateSupplierHandler.php`
- `app/Core/Procurement/Supplier/Supplier.php`
- `app/Ports/Out/Procurement/MobileSupplierHubReaderPort.php`
- `app/Support/SupplierBankLabel.php`
- `database/migrations/2026_10_01_000001_add_bank_details_to_suppliers.php`
- `docs/03_blueprints/ui/0020_supplier_bank_and_payment_evidence.md`
- `public/assets/static/js/pages/admin-mobile-supplier-hub.js`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`
- `public/assets/static/js/pages/admin-suppliers-table.js`
- `public/assets/static/js/shared/supplier-payment-proof-direct-upload.js`
- `resources/views/admin/dashboard/mobile_supplier_hub.blade.php`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`
- `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php`
- `resources/views/admin/suppliers/edit.blade.php`
- `resources/views/admin/suppliers/index.blade.php`
- `resources/views/admin/suppliers/partials/bank_fields.blade.php`
- `resources/views/admin/suppliers/partials/create_modal.blade.php`
- `routes/web/admin_suppliers.php`
- `tests/Feature/Database/SupplierBankMigrationFeatureTest.php`
- `tests/Feature/Procurement/FinalizeSupplierPaymentProofDirectUploadContractFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoicePaymentProofPageFeatureTest.php`
- `tests/Feature/Procurement/SupplierBankMasterFeatureTest.php`
- `tests/Feature/Procurement/SupplierPaymentBankPresentationFeatureTest.php`
- `tests/Frontend/supplier-payment-presentation.test.cjs`

## Verification status

GREEN after syncing latest main `3763300575cd3caafbd160de4697d3ebcaae5510` (baseline PR #70). All 40 supplier feature files matched their preservation hashes after the fast-forward; no feature reimplementation was needed.

- Focused procurement + nullable migration + admin handset + supplier payable temporal regressions: 306 passed (2093 assertions), 57.70 seconds.
- Frontend: `node --test tests/Frontend/*.test.cjs`, 14 passed, 0 failed/skipped.
- Canonical `make verify`: GREEN, exit 0; 1857 passed (14392 assertions), 497.56 seconds.
- PHPStan: no errors; line-count and Blade contract audits PASS; `git diff --check` PASS.

Initial clean main failed one PHPStan check and ten regression assertions. They were repaired separately in issue #68 / PR #70 with one production mapper-expression correction and contract-aligned fixtures/tests; no supplier feature or financial semantics changed. Baseline full artisan: 1847 passed (14273 assertions); baseline canonical verify: 1847 passed (14272 assertions). Issue #68 is closed and the baseline is merged.

Feature issue: https://github.com/Asyraf2003/GlassPos/issues/69. Feature scope and changed-file manifest are above. Logs: storage/logs/supplier-synced-{focused,frontend,verify}.log.

Deployment: the canonical migration `2026_10_01_000001_add_bank_details_to_suppliers.php` is confirmed locally Ran (batch 13), consistent with the owner's migration evidence. Apply it in each deployment environment before serving updated code. Both columns are nullable strings; existing production rows remain NULL without backfill. Publish updated JavaScript through the normal asset-version/cache workflow. Bank details describe the current transfer destination only; historical transfer evidence remains the payment media.
