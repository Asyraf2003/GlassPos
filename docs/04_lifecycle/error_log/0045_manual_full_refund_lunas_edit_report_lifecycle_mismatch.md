# 0045 Manual Full Refund Lunas Edit Report Lifecycle Mismatch

## Status

Reported by owner on 2026-06-26 08:03 WITA. Forensic active. No fix has been
claimed yet.

This log captures manual UI/runtime evidence after the automated closure of
0043/0044 exposed a remaining end-to-end lifecycle mismatch.

## Scope

Audit the full note lifecycle from DB to Blade/JS/UI for:

- create transaction with product, service package, and service-only rows
- partial payment
- edit/revision that removes a product row and adds another partial payment
- full settlement
- full refund
- re-opened note action availability
- edit-after-refund behavior
- transaction cash report
- operational cash profit report
- service package profit report
- stock/inventory report
- per-note transaction report, screen/PDF/Excel headings and values

Primary concern: UI actions must match backend-allocatable financial components,
not only total-vs-net-cash summaries.

## FACT

Owner manual scenario:

1. Initial product stock was increased by `10` for all products used in the
   manual test.
2. Created transaction:
   - product: `17500`
   - service x product/package: `112500`
   - service-only: `60000`
   - total: `190000`
3. Paid partially:
   - paid: `55000`
   - remaining debt: `135000`
4. Edited transaction:
   - removed the standalone product row
   - paid partially again: `37500`
   - resulting transaction: `172500`
   - paid: `92500`
   - remaining debt: `80000`
5. Settled/lunasi the remaining debt.
6. Refunded everything.
7. Re-opened the transaction and the UI still allowed `lunasi` for `37500`,
   apparently from the service-package product component.
8. Executing that payment produced:
   - `Tidak ada komponen note yang bisa dialokasikan untuk payment ini.`

Manual report values observed after the scenario:

- Laporan kas transaksi:
  - Total Kejadian: `4`
  - Kas Masuk: `Rp 172.500`
  - Tunai Masuk: `Rp 172.500`
  - Transfer Masuk: `Rp 0`
  - Kas Keluar: `Rp 37.500`
  - Nilai Bersih: `Rp 135.000`
- Laba kas operasional:
  - Uang Masuk: `Rp 172.500`
  - Pengembalian Dana: `Rp 37.500`
  - Pembelian Eksternal: `Rp 0`
  - HPP Stok Toko: `Rp 8.000`
  - Harga Beli Produk: `Rp 8.000`
  - Biaya Operasional: `Rp 0`
  - Gaji: `Rp 0`
  - Hutang Karyawan: `Rp 0`
  - Laba Kas Operasional: `Rp 127.000`
- Laba paket service:
  - Jumlah Paket: `1`
  - Nilai Paket Terjual: `Rp 112.500`
  - Total Sparepart: `Rp 37.500`
  - HPP Sparepart: `Rp 0`
  - Margin Sparepart: `Rp 37.500`
  - Komponen Service: `Rp 75.000`
  - Refund Komponen Produk: `Rp 37.500`
  - Refund Komponen Service: `Rp 0`
  - Gross Profit Paket: `Rp 112.500`
- Stok dan nilai persediaan:
  - Produk Snapshot: `3`
  - Produk Bermutasi: `3`
  - Qty Tersedia: `30`
  - Nilai Persediaan: `Rp 30.000`
  - Qty Masuk Pembelian: `30`
  - Qty Keluar Penjualan: `10`
  - Qty Balik Refund/Reversal: `2`
  - Qty Koreksi/Revisi: `8`
  - Selisih Qty Periode: `30`
  - Selisih Nilai Pokok Periode: `Rp 30.000`
- Laporan transaksi per nota:
  - Jumlah Nota: `1`
  - Nilai Bruto Transaksi: `Rp 172.500`
  - Pembayaran Dialokasikan: `Rp 172.500`
  - Dana Dikembalikan: `Rp 37.500`
  - Kas Bersih: `Rp 135.000`
  - Refund Due: `Rp 0`
  - Surplus Refund Paid: `Rp 0`
  - Sisa Refund Due: `Rp 0`
  - Sisa Tagihan: `Rp 37.500`
  - Note status count: `1 close`, `1 refund`

Additional manual UI evidence:

- Refund action remained visible/active on the service row, but execution failed
  with a guard that refund can only be recorded for a closed/lunas note.
- Edit remained possible after refund.
- After editing and removing all rows except the package, the UI showed a
  `112500` bill but partial/full payment was unavailable.
- Saving that note made the status become `lunas` unexpectedly.

## GAP

The following are not yet proven from DB/source in this session:

- exact note id and revision ids for the manual scenario
- whether report values are reading stale/current projections correctly
- whether payment UI availability is based on report-style outstanding instead
  of backend-allocatable components
- whether refund UI availability is based on note status, row status, or a stale
  modal payload
- whether edit-after-refund should be blocked or allowed with explicit surplus /
  refund_due / paid-state recalculation
- whether PDF/Excel exports use the same source and headings as the screen
  reports for this scenario

## ROOT CAUSE CANDIDATE

Preliminary candidate from the manual evidence:

- `Sisa Tagihan` and the `lunasi` UI appear to be derived from
  `gross total - net paid after refund`.
- The payment allocator appears to derive payable capacity from active,
  non-refunded component balances.
- After full refund of the only remaining product component, those two models
  disagree:
  - UI/report sees `37500` outstanding.
  - backend allocation sees no component that can receive payment.

This is not safe to patch as a UI-only hide rule until DB/source proves the
canonical lifecycle invariant.

## DECISION

Do not start a broad rewrite yet.

First prove the exact invariant boundary:

1. Is a fully refunded component considered canceled/non-payable?
2. Should a refunded amount reduce payment credit, reduce active bill, or both?
3. Should a refunded note remain editable?
4. If edit is allowed after refund, how are prior payments, refunds, inventory
   reversal, payable components, and note status replayed?
5. Should reports show accounting cash history, current collectible debt, or both
   as separate columns?

## ACTIVE STEP

Forensic step 1:

- locate the latest manual note in DB
- map note/revision/payment/refund/allocation/inventory rows
- map Blade/JS action availability for bayar/lunasi/refund/edit
- map report query sources for screen/PDF/Excel
- then add characterization tests before changing behavior

## PROOF

Current proof is owner manual evidence only. Automated/source/DB proof is still
pending.

## 2026-06-26 Forensic Update 1

### FACT

Local DB read-only proof found the manual note:

- note id: `6f228325-df1c-425d-a038-9a4a3c7778c1`
- customer: `Pelanggan baru`
- current DB note state: `closed`
- current total: `112500`
- current revision: `6f228325-df1c-425d-a038-9a4a3c7778c1-r005`
- latest revision number: `5`

Revision chain matches owner scenario:

- r001 total `190000`, 3 lines
- r002 total `172500`, 2 lines
- r003 total `190000`, 3 lines
- r004 total `172500`, 2 lines
- r005 total `112500`, 1 line

`note_history_projection` after r005:

- total_rupiah: `112500`
- allocated_rupiah: `112500`
- refunded_rupiah: `37500`
- net_paid_rupiah: `75000`
- outstanding_rupiah: `37500`
- line_close_count: `1`
- line_refund_count: `1`

Actual allocation/refund rows:

- refund `384911ea-0a35-43d5-b4b6-61dd826de00d` refunded `37500`
  against r004 service package product components:
  - `023567b3-1b7d-4e90-aeed-c0f1d91c2115`: `17500`
  - `94a6a870-0adf-4e9c-82ae-bc2f6fbe0a62`: `20000`
- r005 payment component allocations were replayed to new r005 package root
  `a86434d5-59fd-4de5-988d-be40971d18a2`:
  - product component `f7b8e707-713d-47ab-93da-45371959e6de`: `17500`
  - product component `e2abbe5d-4337-4d33-8932-6f9794298088`: `20000`
  - service fee `a86434d5-59fd-4de5-988d-be40971d18a2`: `75000`

Inventory proof:

- r004 refunded product components have stock_out then stock_in reversal.
- r005 package product components have new stock_out rows dated `2026-06-26`.

Blade/detail payload proof for current r005 page:

- `can_show_payment_form=false`
- `can_show_settle_payment_action=false`
- `can_show_refund_form=true`
- `can_edit_workspace=true`
- current revision row is close/refundable.
- billing projection still includes both:
  - old r004 refunded package components as outstanding `37500`
  - new r005 package components/service fee as paid `112500`

### SOURCE MAP

Payment/detail UI action source:

- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Application/Note/Services/NoteDetailNotePayloadBuilder.php`
- `app/Application/Note/Services/NoteDetailActionModalPayloadBuilder.php`

Current revision panel source:

- `app/Application/Note/Services/NoteWorkspacePanelDataBuilder.php`
- `app/Application/Note/Services/CurrentRevision/CurrentRevisionRowSettlementProjector.php`
- `app/Application/Note/Services/CurrentRevision/CurrentRevisionDetailRowMapper.php`

Billing projection source:

- `app/Application/Note/Services/NoteBillingProjectionBuilder.php`
- `app/Application/Note/Services/NoteBillingProjectionRowMapper.php`

Payment execution source:

- `app/Application/Payment/Services/ResolveNotePayableComponents.php`
- `app/Application/Payment/Services/AllocatePaymentAcrossComponents.php`
- `app/Application/Payment/Services/ReversedRefundedStoreStockPartPaymentGuard.php`

Refund execution source:

- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundEligibilityGuard.php`

Projection/report source:

- `app/Application/Note/Services/NoteHistoryProjectionService.php`
- `app/Adapters/Out/Note/DatabaseNoteHistoryProjectionSourceReaderAdapter.php`
- `app/Adapters/Out/Note/Queries/NoteHistoryComponentLineSummarySubquery.php`

### PRELIMINARY CONCLUSION

The manual report is enough to locate a real lifecycle bug.

There are two competing UI/read models after edit/refund:

1. Current revision panel reads only current revision snapshots and can say the
   r005 package is paid/close.
2. Billing/projection/report read broader note-level allocation/refund history
   and can still surface old r004 refunded components as outstanding.

This explains the owner-observed pattern:

- report/per-note can show `Sisa Tagihan 37500`
- UI can expose payment/refund/edit decisions from a model that is not aligned
  with backend allocatable components
- backend payment allocator rejects payment when all apparent outstanding
  components are reversed/refunded or otherwise not allocatable
- edit-after-refund can create a new package root while old refunded component
  rows remain part of note-level billing/projection history

### DECISION

The next patch should not be a simple hide button only.

First characterization must lock the invariant:

- current revision, billing projection, note history projection, reports, and
  payment/refund action flags must agree on which components are current,
  payable, refundable, reversed, historical, or closed.

## 2026-06-26 Characterization Test Proof

Added:

- `tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`

Initial proof command:

- `php artisan test tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`

Initial result:

- FAIL, 2 tests.

Failures are intentionally aligned with owner bug:

1. Detail billing projection still exposes old historical refunded package root
   `wi-owner-old-package` as current outstanding components:
   - `ssl-owner-old-1` outstanding `17500`
   - `ssl-owner-old-2` outstanding `20000`
   - old package service fee outstanding `75000`
2. Note history projection computes:
   - allocated `112500`
   - refunded `37500`
   - net_paid `75000`
   instead of current-revision collectible settlement net paid `112500`.

This proves the issue is not only a Blade button. The current revision panel,
detail billing projection, and note history/report projection are reading
different lifecycle surfaces.

## 2026-06-26 Patch Proof 1

Patched:

- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
  - detail `billing_rows` now uses current revision workspace rows through
    `NoteBillingProjectionFromWorkspaceRowsBuilder`, not all historical
    `work_items`.
- `app/Application/Note/Services/NoteHistoryProjectionService.php`
  - when a current revision exists, collectible `net_paid_rupiah`,
    `outstanding_rupiah`, and line open/close/refund counts are derived from
    current revision settlement rows.
  - cash/history fields `allocated_rupiah` and `refunded_rupiah` remain ledger
    history and are not hidden.

Proof commands:

- `php -l app/Application/Note/Services/NoteHistoryProjectionService.php`
- `php -l app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `php artisan test tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`

Proof result:

- syntax PASS for both patched PHP files.
- target test PASS: `2 passed, 18 assertions`.

Remaining scope:

- Transaction summary/per-note report still has its own DTO formula for
  outstanding: `gross - allocated + refunded`. It must be checked and aligned
  with projection/current collectible outstanding before this issue is closed.

## 2026-06-26 Patch Proof 2

Patched transaction summary/per-note report path:

- `app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php`
  - joins `note_history_projection`
  - exposes `outstanding_rupiah` from projection when available
  - keeps fallback formula for legacy rows without projection
- `app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php`
  - accepts optional report/projection outstanding override
  - keeps old formula as fallback
- `app/Application/Reporting/Services/TransactionSummaryPerNoteBuilder.php`
  - passes query `outstanding_rupiah` into the DTO
- `tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`
  - now asserts report raw row and DTO row keep cash history while showing
    collectible outstanding as `0`

Proof commands:

- `php -l app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php`
- `php -l app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php`
- `php -l app/Application/Reporting/Services/TransactionSummaryPerNoteBuilder.php`
- `php -l tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`
- `php artisan test tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`

Proof result:

- syntax PASS for all changed report/test files.
- target lifecycle test PASS: `2 passed, 24 assertions`.

Current invariant proven by target test:

- detail billing no longer exposes old refunded package root as current
  outstanding.
- projection collectible outstanding is `0`.
- transaction summary raw/DTO outstanding is `0`.
- cash history remains visible as allocated/refunded/net cash.

## 2026-06-26 Regression Proof

Additional fixes after regression:

- `NoteHistoryProjectionService` now forces active total `0` notes to
  collectible `net_paid=0` and `outstanding=0`, so stale/current revision
  snapshots cannot recreate debt after a full active-line refund.
- `TransactionSummaryReportingQuery` only uses projection outstanding as an
  override for refund-sensitive rows. Non-refund revision/surplus rows keep the
  legacy safe formula.

Regression command:

- `php artisan test tests/Feature/Note/NoteDetailPageFeatureTest.php tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php`

Regression result:

- PASS: `17 passed, 162 assertions`.

Status after this proof:

- The owner-reported payment/detail/report outstanding mismatch is patched with
  focused automated proof.
- Browser/manual Brave QA is still not run in this session.
- Broader report families outside transaction summary, cash ledger, and package
  revision regression were not fully re-run in this step.

## 2026-06-26 Patch Proof 3

Owner follow-up asked whether there are UI Blade/JS/report fixes.

Answer from source/test work:

- No direct Blade or JS file was required for the first fix because the visible
  UI bug came from the data payload sent to Blade.
- Report path was patched in transaction summary/per-note report.
- A second UI/backend mismatch remained: current revision refund action could be
  visible, but submit was rejected by backend with:
  `Refund hanya bisa dicatat untuk nota yang sudah close/lunas.`

Additional patched files:

- `app/Application/Note/Services/NoteOperationalStatusResolver.php`
  - when current revision exists, operational open/close status uses current
    revision grand total and current revision settlement, not historical
    aggregate work_items.
- `app/Providers/NoteApplicationServiceProvider.php`
  - binds `NoteOperationalStatusResolver` with current revision dependencies in
    runtime Laravel container.
- `app/Application/Payment/Services/RefundComponentTypePolicy.php`
  - keeps generic/default refund limited to product/store-stock parts.
  - adds selected-row refund policy that also allows `service_fee`.
- `app/Application/Note/Services/SelectedRowsRefundBucketsBuilder.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundPlanFactory.php`
- `app/Application/Payment/Services/RefundablePaymentAllocations.php`
  - selected-row refund now includes service fee for the selected current row,
    while generic refund behavior remains unchanged.
- `tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`
  - adds proof that current closed revision refund is accepted after historical
    package component refund and refunds the current row total `112500`.
- `tests/Unit/Application/Note/Services/NoteOperationalStatusResolverTest.php`
  - keeps legacy no-current-revision behavior covered.

Important regression found and fixed:

- Making `service_fee` globally default-refundable broke generic refund policy.
- Final fix makes service fee refundable only for selected-row refunds.

Proof commands:

- `php -l app/Application/Note/Services/NoteOperationalStatusResolver.php`
- `php -l app/Providers/NoteApplicationServiceProvider.php`
- `php -l app/Application/Payment/Services/RefundComponentTypePolicy.php`
- `php -l app/Application/Note/Services/SelectedRowsRefundBucketsBuilder.php`
- `php -l app/Application/Note/Services/SelectedNoteRowsRefundPlanFactory.php`
- `php -l app/Application/Payment/Services/RefundablePaymentAllocations.php`
- `php artisan test tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`
- `php artisan test tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php tests/Feature/Payment/RecordCustomerRefundFeatureTest.php tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php tests/Unit/Application/Note/Services/NoteOperationalStatusResolverTest.php`

Proof result:

- syntax PASS for all changed PHP files listed above.
- target refund/report policy test PASS: `7 passed, 47 assertions`.
- selected regression PASS: `78 passed, 535 assertions`.

Current status:

- UI detail payload: fixed.
- Backend refund guard matching UI current revision: fixed.
- Selected-row refund service fee: fixed.
- Generic refund default product/part behavior: preserved.
- Transaction summary/per-note report outstanding: fixed.
- Direct Blade/JS edits: not needed yet for these proven defects.

## 2026-06-26 Full Verify Regression

Owner ran `make verify` and reported failures after Patch Proof 3.

Important failures:

- `CashierDetailRenderedBillingRowsPaymentFeatureTest`
  - canceled rows rendered as billing rows after switching detail billing to
    workspace/current-revision rows.
- `ClosedNoteFullRefund*` and `RefundReportingOwnerDecisionV2CharacterizationTest`
  - selected-row refund started accepting `service_fee`, breaking established
    policy that service-only/external/service fee refunds are blocked by default.
- `RecordSelectedRowsCustomerRefundFeatureTest`
  - selected-row refund order/policy changed from default product/part refund
    to service fee.
- `EditTransactionWorkspacePackageAutoSplitCharacterizationTest`
  - package selected refund amount changed from product components only
    (`130000`) to full package including service (`200000`).
- `NoteRevisionStoreStockInventoryLifecycleFeatureTest`
  - projection outstanding became `350000` while current revision total was
    `250000`; current revision row settlement must be capped to revision grand
    total.

Updated decision:

- Revert `service_fee` as selected-row/default refundable.
- The current domain contract remains: normal selected-row refund refunds
  product/store-stock components, not service fee.
- Fix owner-visible issue by keeping current-revision status/report alignment
  and by preventing canceled/historical/non-current rows from appearing as
  payable/refundable UI rows.
- Cap current-revision projection outstanding to current revision grand total.

## NEXT SAFE STEP

Read the local source and DB state for the latest matching note. Update this log
again before any patch with:

- actual note id
- actual allocation/refund rows
- source paths that decide each action
- first characterization test target
