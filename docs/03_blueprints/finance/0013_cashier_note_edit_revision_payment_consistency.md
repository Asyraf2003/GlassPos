# Blueprint 0013 - Cashier Note Edit Revision Payment Consistency

Status:
Draft / Edit-Revision-Payment Map / No Patch Yet

Links:
- [0038 audit findings](../../04_lifecycle/error_log/0038_cashier_note_create_edit_refund_reporting_audit_findings.md)
- [0011 workflow index](0011_cashier_note_consistency_workflow_index.md)

Scope:
Bandingkan edit/revision dengan create agar revised note tetap memakai semantics line, inventory, payment allocation, dan historical payload yang konsisten.

## Create Reuse
- Edit/revision payload builder memakai `CreateTransactionWorkspaceWorkItemPayloadMapper`.
- Replacement work item persistence memakai create persister setelah reverse/delete old rows.
- Payment allocations lama dihapus dan dibangun ulang dari current replacement components.

## Revision Payload
- Owner Decision V2 mengunci revision payload sebagai full financial fingerprint.
- Saat ini payload revision menyimpan transaction type, service name/price/part_source, external lines, store-stock lines, `pricing_mode`, `package_total_rupiah`, `parts_total_rupiah`, dan `service_price_rupiah`.
- Gap: `package_profit_rupiah`, `package_base_service_price_rupiah`, `package_service_extra_rupiah`, dan `total_service_component_rupiah` belum terlihat ditulis oleh inspected mapper.
- Payment/refund allocation references untuk report masih `needs characterization`.

## Payment Flow
- Create tanpa bayar: note open/outstanding; payment allocation tidak dibuat.
- Create DP/partial payment: customer_payments dan payment_component_allocations dibuat sesuai komponen.
- Create lunas langsung: payment allocation penuh dan note bisa close.
- Pelunasan susulan: payment handler mengalokasikan outstanding components.
- Edit naik: carry-forward payment direplay sampai component total; sisa menjadi outstanding.
- Edit turun: replay dibatasi component total; surplus/refund_due harus eksplisit sesuai settlement model.
- Edit setelah DP: allocations lama ditangkap, dikurangi refund, dihapus, lalu dibangun ulang.
- DP/pelunasan tidak boleh mengubah subtotal atau COGS; keduanya hanya mengubah payment realization dan allocation state.

## Inventory Flow
- Edit store-stock harus reverse old stock_out dan issue replacement stock_out.
- Reverse memakai source old line; replacement issue memakai current replacement line.
- COGS historis harus tetap dari inventory_movements, bukan current AVG saat report dibuka.
- Edit package harus preserve atau recalculate package fields secara package-aware saat replacement dibuat.

## Admin Update Impact
- product price: historical line totals harus berasal dari line snapshot, bukan current product price.
- product name: revision payload memakai snapshot jika tersedia; display bisa leak current name jika fallback lookup dipakai; needs re-check per UI detail.
- AVG/modal: COGS historical harus dari inventory movement unit_cost.
- service catalog: create/edit may sync service catalog; historical report money tidak boleh membaca current service catalog.
- service product template: template update tidak boleh mengubah nota lama; edit lama setelah update harus jelas memakai snapshot atau current template policy.

## Correction Fee-Only Package Risk
- Existing correction fee-only untuk service/store-stock part perlu guard.
- Owner Decision V2: correction package boleh, tetapi harus package-aware.
- Rule awal: adjusted service price harus `>= package_base_service_price_rupiah` atau default service price.
- Jika package base tidak tersedia, behavior masuk characterization test dulu.
- Tanpa package-aware recalculation, correction fee-only berisiko membuat service_price berubah tanpa package metadata ikut berubah.

## Test Matrix Khusus Edit/Payment
- edit service_only subtotal and payment allocation.
- edit store_stock_sale_only reverse old stock and issue replacement.
- edit service_with_store_stock_part package multi-product rebuilds allocations.
- edit service_with_external_purchase keeps external/service split.
- edit naik -> underpaid/outstanding.
- edit turun -> overpaid/surplus/refund_due.
- edit after DP rebuilds payment_component_allocations.
- DP/pelunasan tidak mengubah subtotal/COGS.
- edit after admin product price update keeps historical line totals.
- edit after AVG update keeps original movement COGS where applicable.
- package-aware correction preserves/recalculates package fields.
- correction floor respects base/default service price.
- missing package base behavior is characterized explicitly if current data lacks base snapshot.

Evidence:
- Revision builder reuses create mapper: `app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php:15`, `app/Application/Note/UseCases/CreateNoteRevisionPayloadWorkItemBuilder.php:35`
- Replacement deletes/reverses then delegates to create persister: `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php:25`, `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php:26`, `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php:31`
- Edit applies payment delete/rebuild: `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php:27`, `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php:43`, `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php:46`
- Payment reconciler subtracts refunds and rebuilds against current components: `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php:30`, `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php:40`, `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php:56`, `app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php:72`
- Revision payload package fields currently mapped: `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:61`, `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:62`, `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:63`, `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:64`
- Original movement cost is reused by reversal operation: `app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php:48`, `app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php:77`
- Full financial fingerprint, package-aware correction, and DP/pelunasan behavior lock: owner decision V2 from current discussion

Progress Local:
- Status: FIXED
- Last checked: 2026-06-21
- Last evidence: Phase 4 UI flexible package GREEN. Edit/preload and revision regressions GREEN; `make verify` GREEN: 1275 passed, 7423 assertions.
- Current behavior after Phase 4:
  - Edit up/down and payment allocation behavior remains characterized by Batch 2 tests.
  - Correction fee-only package no longer accepts below-base service price when package base exists.
  - Revision payload now snapshots `package_total_rupiah`, `parts_total_rupiah`, `service_price_rupiah`, `package_base_service_price_rupiah`, `package_service_extra_rupiah`, `package_profit_rupiah`, `total_service_component_rupiah`, `store_stock_lines`, and `external_purchase_lines`.
  - Edit workspace preload/draft now preserves service_store_stock package multi-product lines instead of slicing to one line.
- Gap summary:
  - Phase 2 candidate closed locally: package-aware correction floor guard.
  - Phase 3 candidate closed locally: full revision payload fingerprint fields for service_with_store_stock_part package.
- Next action: Prepare Phase 5 refund component-type policy. Do not start Phase 6 report query until owner opens that phase.
- Tests linked: EditTransactionWorkspaceRevisionPaymentCharacterizationTest, EditTransactionWorkspacePackageAutoSplitCharacterizationTest, CorrectPaidServiceWithStoreStockPartServiceFeeOnlyFeatureTest.
- Owner decision dependency: none for V2 direction; base-missing behavior remains Phase 3/contract characterization if needed.

## Session Continuity - 2026-06-26 Static UI Consistency Audit

Active step:
- Edit workspace Blade/JS static consistency audit.

Owner rule:
- Every execution, check, fix, or proof must update docs/error log/workflow so future sessions know the last position.

Current focus:
- Verify active edit route first, because older docs distinguish active revision route from legacy `UpdateTransactionWorkspaceHandler`.
- Then audit shared workspace Blade/JS against active edit/revision payment/refund/stock rules.

Initial docs read:
- `docs/03_blueprints/finance/0013_cashier_note_edit_revision_payment_consistency.md`
- `docs/04_lifecycle/handoff/0014_edit_revision_service_store_stock_package_autosplit_phase3_handoff.md`
- `docs/04_lifecycle/handoff/0008_edit_transaction_lifecycle_characterization_handoff.md`

Current conclusion:
- Do not assume `UpdateTransactionWorkspaceHandler` is active edit production path until route proof is checked.
- Treat real Brave browser proof as manual/operator proof; this session focuses on Blade/JS/source consistency.
