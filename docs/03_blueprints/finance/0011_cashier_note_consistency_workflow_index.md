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
- [0016_cashier_note_final_regression_matrix.md](0016_cashier_note_final_regression_matrix.md)

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
| Phase 2 Hardening guards | FIXED | Required for template, correction, external purchase UI simplification gates | 0011, 0012, 0013 | Guard and correction candidates | Guard tests | package-aware correction floor guard GREEN; template preset multi-product remains blocked until Phase 4 contract GREEN; external purchase package backend path blocked until explicit label+total contract GREEN; `make verify` GREEN: 1274 passed, 7403 assertions | Prepare Phase 3 revision payload historical fingerprint source-map | Phase 2 guard candidates closed locally |
| Phase 3 Revision payload historical fingerprint | FIXED | Required: revision payload financial fingerprint or active rows/movement/allocation only | 0011, 0013, 0015 | NoteRevisionLinePayloadMapper | Revision payload tests | Phase 3 fingerprint fields GREEN. RED first: `php artisan test --filter=NoteRevisionLinePayloadMapperTest` failed on missing `package_base_service_price_rupiah`; after patch targeted tests GREEN and `make verify` GREEN: 1275 passed, 7417 assertions. | Stop; do not start Phase 4 until owner opens UI flexible package scope | revision payload can become financial fingerprint for minimal package fields |
| Phase 4 UI flexible package | FIXED | Flexible package direction locked; template as preset; external purchase separate domain | 0011, 0012, 0013 | Blade/JS workspace, request validator/mapper, store-stock package template branch | Page/submit contract tests | Phase 4 UI flexible package GREEN: service_store_stock UI supports one service + many product lines; template preset multi-product extension supported; external purchase owner-facing label + total supported; external package_auto_split still blocked; targeted filters GREEN; `make verify` GREEN: 1275 passed, 7423 assertions. | Prepare Phase 5 refund component-type policy | UI dan backend contract sama |
| Phase 5 Refund component-type policy | FIXED | Locked policy encoded; manual exception remains deferred unless approval path is explicitly designed | 0011, 0014, 0015 | Refund policy and guard candidates | Refund policy tests | Phase 5 refund component-type policy GREEN: product/store-stock components default refundable; service_fee and external_purchase components default blocked; package refund maps to raw components; cancellable rows limited to fully refundable rows; targeted refund/report/edit tests GREEN; `make verify` GREEN. | Prepare Phase 6 report query / Service Package Profit Breakdown source contract | refund behavior matches locked component-type policy |
| Phase 6 Report query | FIXED | Combination basis locked; Service Package Profit Breakdown implemented as separate query/read-model; query split to satisfy audit-lines | 0011, 0015 | ServicePackageProfitBreakdownQuery + split helper queries/mappers | ServicePackageProfitBreakdownQueryTest | RED: missing query class; GREEN: query test 1 passed / 17 assertions; targeted reporting boundary regression GREEN; final make verify GREEN by operator confirmation | STOP; do not start Phase 7 in this slice | query reconciled and no mutable master leak for money |
| Phase 7 Regression matrix | VERIFYING | Phase 0-6 accepted; Phase 7 is verification/docs-only | 0011, 0016 | None | Focused regression matrix + `make verify` | Final regression matrix docs created; focused regression and final verify pending | If all commands are GREEN, update Phase 7 to FIXED | focused suites and `make verify` green |

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
- Selesai sebagai read-model/query terpisah, bukan perubahan Operational Profit.

Implemented source:
- `app/Adapters/Out/Reporting/Queries/ServicePackageProfitBreakdownQuery.php`
- `app/Adapters/Out/Reporting/Queries/ServicePackageProfitBreakdown/BreakdownSourceRowsQuery.php`
- `app/Adapters/Out/Reporting/Queries/ServicePackageProfitBreakdown/PartsTotalSubquery.php`
- `app/Adapters/Out/Reporting/Queries/ServicePackageProfitBreakdown/CogsSubqueries.php`
- `app/Adapters/Out/Reporting/Queries/ServicePackageProfitBreakdown/RefundComponentSubqueries.php`
- `app/Adapters/Out/Reporting/Queries/ServicePackageProfitBreakdown/BreakdownRowMapper.php`
- `tests/Feature/Reporting/ServicePackageProfitBreakdownQueryTest.php`

Formula locked in this slice:
- `parts_total_rupiah = SUM(work_item_store_stock_lines.line_total_rupiah)`.
- `sparepart_cogs_rupiah = stock_out COGS - stock_in reversal COGS from inventory_movements`.
- `sparepart_margin_rupiah = parts_total_rupiah - sparepart_cogs_rupiah`.
- `total_service_component_rupiah = service_price_rupiah + package_profit_rupiah`.
- `total_package_gross_profit_rupiah = sparepart_margin_rupiah + total_service_component_rupiah`.
- `refunded_product_component_rupiah` sums `product_only_work_item` and `service_store_stock_part`.
- `refunded_service_component_rupiah` sums explicit `service_fee` refund components only.

Evidence:
- RED: `ServicePackageProfitBreakdownQuery` missing.
- GREEN: `php artisan test tests/Feature/Reporting/ServicePackageProfitBreakdownQueryTest.php` -> 1 passed, 17 assertions.
- Boundary regression GREEN:
  - `php artisan test --filter=OperationalProfit` -> 16 passed, 101 assertions.
  - `php artisan test --filter=RefundReportingOwnerDecisionV2CharacterizationTest` -> 6 passed, 63 assertions.
  - `php artisan test --filter=TransactionSummary` -> 5 passed, 49 assertions.
  - `php artisan test --filter=TransactionCashLedger` -> 34 passed, 263 assertions.
- Final closure: `make verify` GREEN by operator confirmation after testing DB reset.

Stop condition:
- query angka reconciled dan tidak membaca current mutable master untuk historical money.
- Operational Profit formula tetap tidak berubah.
- No migration, no route/config, no supplier invoice payment proof, no Mobile API.
- Phase 7 tidak dimulai di slice ini.

## Phase 7 - Regression Matrix
Goal:
- Protect create/edit/refund/payment/report invariants after Phase 0-6.
- Build final regression matrix/index and document final test commands.
- Verification and documentation only.

Docs:
- `docs/03_blueprints/finance/0016_cashier_note_final_regression_matrix.md`

Rules:
- No feature change.
- No migration.
- No route/config change.
- No supplier invoice payment proof.
- No Mobile API.
- No Operational Profit formula change.
- No refund policy change.
- No ServicePackageProfitBreakdown behavior change unless regression proves a bug.

Test command index:
- See `0016_cashier_note_final_regression_matrix.md`.

Stop condition:
- focused regression matrix green.
- final `make verify` green.
- this ledger row updated to `FIXED`.
