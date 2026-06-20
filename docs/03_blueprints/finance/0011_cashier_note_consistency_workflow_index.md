# Blueprint 0011 - Cashier Note Consistency Workflow Index

Status:
Draft / Workflow Index / No Patch Yet

Purpose:
File ini adalah progress ledger utama untuk pekerjaan create/edit/refund/payment/report kasir.
Semua progress phase dicatat di file ini.

Related docs:
- [0038_cashier_note_create_edit_refund_reporting_audit_findings.md](../../04_lifecycle/error_log/0038_cashier_note_create_edit_refund_reporting_audit_findings.md)
- [0012_cashier_note_create_line_source_map.md](0012_cashier_note_create_line_source_map.md)
- [0013_cashier_note_edit_revision_payment_consistency.md](0013_cashier_note_edit_revision_payment_consistency.md)
- [0014_cashier_note_refund_reporting_consistency.md](0014_cashier_note_refund_reporting_consistency.md)
- [0015_service_package_profit_breakdown_source_contract.md](0015_service_package_profit_breakdown_source_contract.md)

Progress Marker Convention:
Gunakan status berikut untuk setiap phase:

- TODO: belum mulai.
- AUDIT_READY: scope dan evidence cukup untuk mulai.
- DECISION_REQUIRED: butuh keputusan owner sebelum patch.
- TEST_DESIGNED: test plan sudah ditulis.
- RED_READY: RED test siap/atau sudah terbukti gagal.
- PATCH_READY: patch boleh mulai.
- IN_PROGRESS: sedang dieksekusi.
- BLOCKED: tertahan karena dependency/decision/test.
- VERIFYING: patch selesai, test sedang diverifikasi.
- FIXED: phase selesai dan targeted tests pass, atau docs-only phase selesai tanpa test.
- DEFERRED: sengaja ditunda.
- REJECTED: keputusan owner menolak phase.

Progress Ledger:

| Phase | Status | Owner Decision | Target Docs | Target Source | Target Tests | Last Evidence | Next Action | Stop Condition |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Phase 0 Docs lock | FIXED | Not required | 0038, 0011, 0012, 0013, 0014, 0015 | None | None | File docs created in docs-only phase | Owner review findings and decisions | Semua file dibuat; progress ledger ada; owner checklist ada |
| Phase 0A Owner Decision V2 Docs lock | FIXED | Locked in docs | 0038, 0011, 0012, 0013, 0014, 0015 | None | None | Owner Decision V2 written in docs | Keep ledger and blueprint wording aligned | Owner Decision V2 reflected in all target docs |
| Phase 1 Characterization tests only | FIXED | Locked direction exists; current behavior characterized through Batch 1, Batch 2, and Batch 3 | 0011-0015 | None | CreateTransactionWorkspaceLineTypeCharacterizationTest, EditTransactionWorkspaceRevisionPaymentCharacterizationTest, RefundReportingOwnerDecisionV2CharacterizationTest, focused refund/reporting regressions | Batch 1 create/package GREEN. Batch 2 edit/revision/payment GREEN. Batch 3 refund/reporting GREEN: `php artisan test --filter=RefundReportingOwnerDecisionV2CharacterizationTest`; `php artisan test --filter=ClosedNoteFullRefund`; `php artisan test --filter=RecordSelectedRowsCustomerRefund`; `php artisan test --filter=TransactionSummary`; `php artisan test --filter=TransactionCashLedger`; `php artisan test --filter=OperationalProfit`; plus Batch 1/2 characterization filters. | Prepare Phase 2 hardening guards from characterized gaps | Phase 1 ledger closed; do not start Phase 2 in this batch |
| Phase 2 Hardening guards | IN_PROGRESS | Required for template, correction, external purchase UI simplification gates | 0011, 0012, 0013 | Guard and correction candidates | Guard tests | package-aware correction floor guard GREEN locally; targeted correction tests GREEN; Batch 2 characterization GREEN; broad Payment regression GREEN: 233 passed, 1538 assertions | Continue with next Phase 2 guard candidate after docs sync | all Phase 2 guard candidates selected/implemented and targeted tests pass |
| Phase 3 Revision payload historical fingerprint | TODO | Required: revision payload financial fingerprint or active rows/movement/allocation only | 0011, 0013, 0015 | NoteRevisionLinePayloadMapper candidate | Revision payload tests | `app/Application/Note/Services/NoteRevisionLinePayloadMapper.php:61` | Decide payload contract | revision payload can become financial fingerprint |
| Phase 4 UI flexible package | TODO | Flexible package direction locked; current source gap characterized, final browser patch contract still deferred | 0011, 0012 | Blade/JS workspace candidates | Page/submit contract tests | `public/assets/static/js/pages/cashier-note-workspace/rows.js:81` | Align UI to flexible package source contract | UI and backend contract sama |
| Phase 5 Refund component-type policy | TODO | Locked policy; exact approval/exception behavior needs characterization | 0011, 0014 | Refund policy and guard candidates | Refund policy tests | Owner Decision V2 plus existing refund component evidence | Encode raw component refund policy | refund behavior matches locked component-type policy |
| Phase 6 Report query | TODO | Combination basis locked; exact query field mapping still needs characterization | 0011, 0015 | Query/source candidates only | Query tests | `docs/04_lifecycle/error_log/0037_profit_calculation_logic_audit_findings.md:23` | Lock source contract and implement query only | query reconciled and no mutable master leak for money |
| Phase 7 Regression matrix | TODO | Not required unless prior phases accepted | 0011 | None or phase patches | Focused suites | needs re-check | Build final regression command index | focused suites green |

Decision Log:
- Owner Decision V2 locked:
  flexible package direction; template as preset; package-aware correction; full revision payload fingerprint; external purchase remains separate domain; reporting basis combination; refund component-type policy.
- Source:
  current owner discussion in this session.

Workflow Rules:
- Tidak boleh naik phase sebelum stop condition phase sebelumnya terpenuhi.
- Patch source hanya boleh mulai dari Phase 2.
- Phase 1 hanya test/characterization.
- Phase 3 tidak boleh jalan sebelum owner decision soal revision payload.
- Phase 4 tidak boleh jalan sebelum Phase 1 characterization menutup browser contract gap yang relevan.
- Phase 5 tidak boleh jalan sebelum Phase 1 characterization menutup raw component refund gap yang relevan.
- Phase 6 tidak boleh jalan sebelum source contract di 0015 dikunci.
- Operational Profit formula tidak diubah.
- Payment proof supplier invoice tetap out of scope.
- Mobile API tetap retired.

Phase Roadmap:

## Phase 0 - Docs Lock
Goal:
- Buat 0038 dan blueprint 0011-0015.
- Tidak patch source.

Target docs:
- 0038
- 0011
- 0012
- 0013
- 0014
- 0015

Tests:
- Tidak ada.

Stop condition:
- Semua file dibuat.
- Progress ledger ada.
- Owner decision checklist ada.

## Phase 0A - Owner Decision V2 Docs Lock
Goal:
- Tulis Owner Decision V2 ke 0038 dan blueprint 0011-0015.
- Tidak patch source.

Tests:
- Tidak ada.

Stop condition:
- Keputusan owner V2 terlihat konsisten di seluruh docs target.
- Phase 0A status `FIXED`.

## Phase 1 - Characterization Tests Only
Goal:
- Membuktikan current behavior vs Owner Decision V2.

Target tests:
- create 4 tipe line subtotal benar.
- create package_auto_split single-product.
- create package_auto_split multi-product backend.
- UI contract multi-product service_store_stock.
- template preset can be extended manually according to intended contract, or current gap is proven RED.
- correction package-aware floor behavior; if package base missing, mark `needs characterization`.
- external purchase simplified domain behavior vs current backend structure.
- edit naik -> underpaid/outstanding.
- edit turun -> overpaid/surplus/refund_due.
- revision payload captures full package fingerprint fields, or current missing fields are proven RED.
- edit package preserves/rebuilds payment allocations.
- DP/pelunasan does not change subtotal/COGS, only payment realization/allocation.
- refund store-stock uses original unit cost.
- refund package selected row tidak target stale old components.
- refund raw component policy: product/service/external/manual exception coverage.
- admin update product price tidak mengubah historical line totals.
- admin update AVG tidak mengubah historical COGS.

Stop condition:
- RED/GREEN characterization jelas.

## Phase 2 - Hardening Guards
Goal:
- Cegah jalur yang diam-diam merusak data.

Patch candidates:
- package-aware correction floor guard.
- explicit guard/copy untuk template preset extension yang belum fully supported.
- explicit guard/copy untuk external purchase domain simplification jika UI masih menyajikan field yang tidak owner butuhkan.

Tests:
- package-aware correction keeps adjusted service price above base/default floor.
- non-package correction fee-only tetap pass.
- template preset extension behavior sesuai owner decision and current allowed phase.

Stop condition:
- targeted tests pass.

## Phase 3 - Revision Payload Historical Fingerprint
Goal:
- Lengkapi snapshot historis untuk package breakdown.

Patch candidates:
- NoteRevisionLinePayloadMapper menulis package_total_rupiah.
- menulis parts_total_rupiah.
- menulis service_price_rupiah.
- NoteRevisionLinePayloadMapper menulis package_profit_rupiah.
- menulis package_base_service_price_rupiah.
- menulis package_service_extra_rupiah.
- menulis total_service_component_rupiah.
- menulis store_stock_lines snapshot dan external_purchase_lines snapshot final jika ada.
- allocation references jika memang dibutuhkan report; otherwise `needs characterization`.

Tests:
- edit setelah master data berubah tetap memakai snapshot.
- template package revision payload lengkap.

Stop condition:
- revision payload dapat menjadi financial fingerprint.

## Phase 4 - UI Create/Update Consistency
Goal:
- Selaraskan UI flexible package dengan backend.

Patch candidates:
- buka package row awal untuk satu service utama + banyak product lines.
- template diposisikan sebagai preset yang bisa dilengkapi manual sesuai rule validasi.
- many service components remains deferred until source contract stable.
- external purchase UI disederhanakan menjadi label + total biaya keluar.

Tests:
- page contract.
- submit multi-product package.
- submit template preset plus manual extension.
- submit simplified external purchase row.

Stop condition:
- UI dan backend contract sama.

## Phase 5 - Refund Component-Type Policy
Goal:
- Kunci refund behavior ke raw component-type policy.

Patch candidates:
- product store-stock refundable path and stock reversal guard.
- service default non-refundable guard after DP/performed, with manual exception path if approved.
- external purchase default non-refundable guard, with manual exception path if approved.
- package refund mapping to raw components.

Tests:
- product-only refund.
- service-only refund with default block and manual exception path.
- external purchase refund with default block and manual exception path.
- package mixed-component refund after revision.

Stop condition:
- refund behavior matches locked component-type policy.

## Phase 6 - Report Query
Goal:
- Query/report source tanpa UI dulu.

Formula candidate:
- sparepart_sales_total_rupiah = sum store_stock line totals.
- sparepart_cogs_rupiah = sum ABS inventory movement total_cost for linked store_stock lines.
- sparepart_margin_rupiah = sparepart_sales_total - sparepart_cogs.
- total_service_component_rupiah = package-aware service component from full payload fields and correction-aware recalculation.
- total_package_gross_profit_rupiah = sparepart_margin + total_service_component.

Tests:
- normal package.
- package after edit.
- package after refund.
- product price changed after note.
- AVG changed after note.
- multi-product package.
- non-template package.
- combination date basis across transaction/payment/refund/movement dates.

Stop condition:
- query angka reconciled dan tidak membaca current mutable master untuk historical money.

## Phase 7 - Regression Matrix
Goal:
- Protect create/edit/refund/payment/report invariants.

Test command index:
- `php artisan test --filter=CreateTransactionWorkspace`
- `php artisan test --filter=EditTransactionWorkspacePackageAutoSplitCharacterizationTest`
- `php artisan test --filter=CorrectPaidServiceWithStoreStockPartServiceFeeOnlyFeatureTest`
- `php artisan test --filter=ClosedNoteFullRefund`
- `php artisan test --filter=RecordSelectedRowsCustomerRefundFeatureTest`
- `php artisan test --filter=TransactionSummary`
- `php artisan test --filter=TransactionCashLedger`
- `php artisan test --filter=OperationalProfit`

Stop condition:
- focused suites green.
