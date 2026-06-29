# 0038 - Cashier note create/edit/refund/reporting consistency audit findings

Status:
RESOLVED / Historical Audit Findings / Final Closure In 0039

Final Resolution:
- This audit finding set is resolved by the Phase 0-7 workflow.
- Final closure pointer: [0039_cashier_note_create_edit_refund_reporting_final_closure.md](0039_cashier_note_create_edit_refund_reporting_final_closure.md)
- Final regression matrix: [0016_cashier_note_final_regression_matrix.md](../../03_blueprints/finance/0016_cashier_note_final_regression_matrix.md)
- Workflow ledger: [0011_cashier_note_consistency_workflow_index.md](../../03_blueprints/finance/0011_cashier_note_consistency_workflow_index.md)
- Final proof: `make verify` GREEN, 1276 passed, 7445 assertions, 54.12s.
- Do not treat this file as active patch scope unless a new bug/workflow explicitly reopens it.

Boundary:
- Web/PWA only.
- Mobile API retired.
- Supplier invoice payment proof out of scope.
- Operational Profit formula tidak diubah.
- Scope ini hanya create/edit/refund/payment/report kasir.

Purpose:
Dokumen ini adalah catatan historis finding dan owner decision. Semua finding dalam file ini sudah ditutup oleh workflow Phase 0-7. Progress final ditutup di 0011, 0016, dan 0039.
Progress pengerjaan dicatat di:
[docs/03_blueprints/finance/0011_cashier_note_consistency_workflow_index.md](../../03_blueprints/finance/0011_cashier_note_consistency_workflow_index.md)

Executive Summary:
- Backend create nota kasir punya 4 tipe line aktif:
  1. service_only
  2. service_with_external_purchase
  3. store_stock_sale_only
  4. service_with_store_stock_part
- Edit/revision sebagian besar reuse create mapper/normalizer.
- Refund mengikuti payment_component_allocations dan refund_component_allocations.
- Operational Profit tetap laporan kas operasional, bukan Service Package Profit Breakdown.
- Service Package Profit Breakdown harus report/section terpisah.
- UI/backend dan payload historis punya gap yang harus diputuskan owner sebelum patch.

## Owner Decision V2 - Locked Direction
- Package service harus fleksibel. Target awal adalah satu package row mendukung satu service utama dan banyak product/sparepart lines. Target lanjutan adalah banyak service components dan banyak product lines setelah source contract stabil.
- Template adalah package preset, bukan batas permanen. Template mengisi default service/product/harga, lalu kasir tetap boleh menambah atau manual sesuai aturan validasi.
- Create tetap menjadi source of truth untuk package semantics.
- Correction fee/package adjustment boleh, tetapi harus package-aware dan tidak boleh menurunkan adjusted service price di bawah package base/default service price.
- Revision payload wajib lengkap sebagai financial fingerprint historis.
- Sparepart luar tetap domain sendiri. External purchase package UI bukan target utama.
- Reporting memakai basis kombinasi: `transaction_date` untuk konteks transaksi, `payment_date` dan `refund_date` untuk realisasi uang, `movement_date` untuk inventory/COGS.
- Refund memakai component-type policy paling mentah: product toko refundable, service dan sparepart luar default non-refundable tetapi bisa manual exception, dan package refund harus bisa dipetakan ke komponen mentah.

Finding Groups:

## Group A - UI/backend mismatch create package
Severity: High

Summary:
Arahan final package adalah fleksibel, tetapi current UI cashier masih membatasi `service_store_stock` ke satu product line walaupun backend create sudah punya evidence multi-product direct POST.

Owner decision:
- Arah final adalah flexible package.
- Phase patch harus bertahap: mulai dari hardening dan characterization dulu, lalu UI flexible package.

## Group B - Template fast-entry vs multi-part package conflict
Severity: High

Summary:
Template sekarang mengarah ke fast-entry preset, sementara branch template backend masih menolak `product_lines` lebih dari 1.

Owner decision:
- Template dikunci sebagai package preset, bukan batas permanen.
- Multi-part package adalah arah final, tetapi rollout patch tetap bertahap.
- Exact behavior saat kasir menambah line di atas preset perlu characterization.

## Group C - Revision payload incomplete for package reporting
Severity: Medium/High

Summary:
work_item_service_details menyimpan package_profit/base/extra, tetapi note_revision_lines.payload belum menyimpan package_profit_rupiah, package_base_service_price_rupiah, package_service_extra_rupiah.

Owner decision:
- Revision payload wajib menjadi financial fingerprint.
- Report historis ke depan harus bisa dikembangkan tanpa membaca current mutable master data.

## Group D - Correction fee-only package risk
Severity: High

Summary:
Correction fee-only belum package-aware dan bisa membuat package metadata stale.

Owner decision:
- Jangan reject semua correction package.
- Correction harus package-aware.
- Adjusted service price tidak boleh turun di bawah `package_base_service_price_rupiah` atau default service price.
- Jika package base tidak tersedia, behavior masuk characterization test dulu.

## Group E - External purchase package UI gap
Severity: Medium

Summary:
Sparepart luar tetap domain sendiri. Backend masih punya jejak external package composer, tetapi external purchase package UI bukan target utama owner.

Owner decision:
- UI cukup nama sparepart luar + total biaya keluar.
- Qty tidak perlu tampil di UI owner-facing.
- Jika struktur internal masih butuh `qty/unit_cost`, treat internal `qty=1` dan `unit_cost=total`.

## Group F - Reporting clarity gap
Severity: Medium

Summary:
Operational Profit bukan Service Package Profit Breakdown.

Owner decision:
- buat Service Package Profit Breakdown terpisah setelah source data dikunci.
- Basis report dikunci sebagai kombinasi tanggal, bukan satu tanggal tunggal.

## Group G - Refund package breakdown gap
Severity: Medium

Summary:
Refund sudah punya jalur component allocation, tetapi policy bisnis refund per jenis komponen masih perlu dikunci agar package refund bisa fleksibel.

Owner decision:
- Refund mengikuti component-type policy paling mentah.
- Product toko default refundable dan reverse stock memakai original unit cost.
- Service default non-refundable setelah DP/dikerjakan, tetapi manual exception bisa ada dengan reason/approval.
- Sparepart luar default non-refundable, tetapi manual exception bisa ada.
- Package refund harus bisa dipetakan ke komponen mentah: product saja, service saja, atau kombinasi.

Owner Decision Checklist:
- [x] Flexible package adalah arah final; target awal satu service utama + banyak product lines.
- [x] Template adalah package preset, bukan batas permanen.
- [x] Correction package boleh, tetapi harus package-aware dan tidak boleh turun di bawah base/default service price.
- [x] Revision payload wajib lengkap sebagai financial fingerprint.
- [x] Sparepart luar tetap domain sendiri; external purchase package UI bukan target utama.
- [x] Service Package Profit Breakdown memakai basis kombinasi tanggal.
- [x] Refund mengikuti component-type policy paling mentah.

Blueprint Map:
- [0011_cashier_note_consistency_workflow_index.md](../../03_blueprints/finance/0011_cashier_note_consistency_workflow_index.md)
- [0012_cashier_note_create_line_source_map.md](../../03_blueprints/finance/0012_cashier_note_create_line_source_map.md)
- [0013_cashier_note_edit_revision_payment_consistency.md](../../03_blueprints/finance/0013_cashier_note_edit_revision_payment_consistency.md)
- [0014_cashier_note_refund_reporting_consistency.md](../../03_blueprints/finance/0014_cashier_note_refund_reporting_consistency.md)
- [0015_service_package_profit_breakdown_source_contract.md](../../03_blueprints/finance/0015_service_package_profit_breakdown_source_contract.md)

Evidence:
- Web/PWA boundary and Mobile API retired: `docs/04_lifecycle/error_log/0035_mobile_api_retired_pwa_runtime_boundary.md:9`, `docs/04_lifecycle/error_log/0035_mobile_api_retired_pwa_runtime_boundary.md:29`
- Supplier invoice payment proof is separate Web/PWA scope: `docs/04_lifecycle/error_log/0036_supplier_invoice_payment_proof_web_pwa_audit_findings.md:7`
- Operational Profit formula boundary: `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:9`, `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:11`, `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:18`
- Four work item types exist in domain: `app/Core/Note/WorkItem/WorkItem.php:12`, `app/Core/Note/WorkItem/WorkItem.php:13`, `app/Core/Note/WorkItem/WorkItem.php:14`, `app/Core/Note/WorkItem/WorkItem.php:15`
- Create mapper selects product, external, store-stock, or service-only branches: `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:29`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:54`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:58`, `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php:62`
- Request accepts `manual_split`, `package_auto_split`, `package_total_rupiah`, product lines, external total, and inline payment: `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:26`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:28`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:35`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:44`, `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php:46`
- UI service_store_stock currently carries template requirement and package total: `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:14`, `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:15`, `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php:140`
- UI JS limits/preloads service_store_stock product lines to one: `public/assets/static/js/pages/cashier-note-workspace/rows.js:80`, `public/assets/static/js/pages/cashier-note-workspace/rows.js:81`, `public/assets/static/js/pages/cashier-note-workspace/rows.js:268`
- Template package branch rejects multiple product lines and stores package fields: `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:22`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:38`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:39`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:40`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches.php:41`
- External package backend composer exists: `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:19`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:30`, `app/Application/Note/Services/CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer.php:37`
- Revision builder reuses create mapper: `app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php:15`, `app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php:35`
- Revision payload has package total/parts/service price but not package profit/base/extra in inspected mapper: `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:61`, `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:62`, `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:63`, `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:64`
- Edit replacement reverses inventory, deletes/replaces work items, and rebuilds payment allocations: `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php:25`, `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php:31`, `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php:43`, `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php:46`
- Refund allocates through payment components and writes refund component allocations: `app/Application/Payment/Services/AllocateRefundAcrossComponents.php:36`, `app/Application/Payment/Services/AllocateRefundAcrossComponents.php:65`
- Store-stock refund reversal uses original movement unit cost: `app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php:48`, `app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php:77`
- Flexible package direction, template as preset, correction package-aware, full revision payload fingerprint, external purchase separate domain, reporting basis combination, dan refund component-type policy: owner decision V2 from current discussion
