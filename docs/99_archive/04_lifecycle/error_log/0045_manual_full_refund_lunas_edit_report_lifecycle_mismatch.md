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

## 2026-06-26 Full Verify Regression Update

### FACT

Owner ran `make verify` and reported 12 failing tests after the first lifecycle
patch set.

The failures clustered into three causes:

- selected-row refund briefly allowed `service_fee`, breaking the existing
  domain contract that only product/store-stock payment components are
  refundable
- detail billing rows still rendered canceled rows as payable/refundable rows
- revision projection sync ran before the new revision was committed as
  `notes.current_revision_id`, so projection settlement could still read the
  previous revision

### DECISION

Keep the current refund contract narrow:

- `service_fee` remains non-refundable for default and selected-row refund
- current owner issue is handled by aligning current revision settlement,
  projection, reports, and UI row visibility, not by making service fee
  refundable

### ACTIVE STEP

Move note-history projection sync in the revision workflow so it runs after the
new note revision is inserted and set as `current_revision_id`.

### PROOF

Patch applied:

- `ApplyNoteRevisionAsActiveReplacement` no longer syncs history projection
  before the revision commit
- `CreateNoteRevisionWorkflow` now syncs history projection immediately after
  `CreateNoteRevisionCommitter::commit()`

Subset proof:

Command, from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
php artisan test tests/Feature/Note/CashierDetailRenderedBillingRowsPaymentFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundLifecycleFeatureTest.php tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php tests/Feature/Note/RefundReportingOwnerDecisionV2CharacterizationTest.php tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php
```

Result:

- `29 passed`
- `297 assertions`
- proves the 12 reported `make verify` failures no longer reproduce in the
  targeted regression group

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
  - selected-row refund remains limited to the same default-refundable
    product/store-stock components after full-regression review.
- `app/Application/Note/Services/SelectedRowsRefundBucketsBuilder.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundPlanFactory.php`
- `app/Application/Payment/Services/RefundablePaymentAllocations.php`
  - selected-row refund policy is explicit and does not include service fee.
- `tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php`
  - adds proof that current closed revision refund is accepted after historical
    package component refund without reviving historical refunded product debt.
- `tests/Unit/Application/Note/Services/NoteOperationalStatusResolverTest.php`
  - keeps legacy no-current-revision behavior covered.

Important regression found and fixed:

- Making `service_fee` globally default-refundable broke generic refund policy.
- Full-regression correction: service fee remains non-refundable for default and
  selected-row refunds.

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
- Selected-row refund service fee: blocked by policy.
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

Final patch set after owner full-verify failures:

- `NoteBillingProjectionFromWorkspaceRowsBuilder`
  - skips canceled current-revision rows so Blade does not render them as
    payable/refundable billing rows.
- `BuildsNoteHistoryCurrentRevisionSettlement`
- `ResolvesNoteOperationalCurrentRevisionSettlement`
  - cap current-revision outstanding by the active revision grand total.
- `ApplyNoteRevisionAsActiveReplacement`
- `CreateNoteRevisionWorkflow`
  - history projection sync moved after the new revision is inserted and set as
    `notes.current_revision_id`.
- `RefundComponentTypePolicy`
  - selected-row refund policy stays aligned with default product/store-stock
    refundable components.

Proof command from `/home/asyraf/Code/laravel/bengkel2/app`:

```bash
make verify
```

Proof result:

- PHPStan: no errors
- line-limit audit: passed
- Blade PHP/directive audit: passed
- contract audit: passed
- Pest: `1420 passed`, `8450 assertions`
- duration: `99.51s`

## CURRENT STATUS

- UI create/edit/payment/refund payload alignment for the owner-reported
  lifecycle is fixed at backend data-source level.
- Blade/JS contract tests for payment workflow remain green; no direct Blade/JS
  patch was required for this proven defect.
- Transaction summary/per-note report, cash ledger, package profit, stock value,
  PDF, and Excel report suites pass full verify.
- Existing dirty manual notes created before this patch may still have stale
  projection rows until they are recreated or projection is resynced.

## NEXT SAFE STEP

For owner manual Brave verification, reset/recreate the manual scenario from a
fresh note after deploying this patch set, or explicitly resync projection for
the old note before comparing reports.

## 2026-06-26 Manual Brave Reopen 2

### FACT

Owner created a fresh manual note after restocking products 1-5 with quantity
`10` each.

Initial transaction composition:

- service + store-stock part: `92500`
  - paid cash/down payment: `17500`
  - outstanding: `75000`
- store-stock product sale: `55000`
  - paid cash: `55000`
  - outstanding: `0`
- service only: `50000`
  - outstanding: `50000`
- service + external part: `100000`
  - paid cash/down payment: `30000`
  - outstanding: `70000`

Initial expected baseline matched owner reports:

- grand total: `297500`
- paid: `102500`
- outstanding: `195000`
- cash ledger net: `102500`
- operational profit: `65780`
- package profit: `90260`
- inventory available quantity: `47`

Manual defects observed after follow-up actions:

1. Refund action is clickable for rows that are not fully paid/closed. Backend
   rejects after the owner enters a reason, but the UI should disable the action
   from the start.
2. Payment model is intentionally one down payment plus one settlement action;
   second/third installment payments are not part of this system. UI currently
   correctly offers only `Lunasi` after initial down payment.
3. Selected refund on service + store-stock part and service + external part
   succeeded. After refunding the store-stock product component, detail still
   showed a `17500` outstanding row:
   - line 1 service + store-stock part subtotal: `92500`
   - paid: `75000`
   - refund: `17500`
   - outstanding: `17500`
   - payment UI offered `Lunasi` for `17500`
4. Executing that offered settlement failed with:
   `Tidak ada komponen note yang bisa dialokasikan untuk payment ini.`
5. Owner edited the note by deleting all lines and leaving one new product-only
   line worth `20000`.
6. Edit screen could not pay partial/full; saving made the note become paid:
   - grand total: `20000`
   - paid: `20000`
   - refund: `0`
   - outstanding: `0`
   - operational status: `Lunas`
   - pending refund due: `157500`
7. Owner marked refund due successfully.

Reports after the problematic edit/refund due sequence:

- transaction cash ledger:
  - total events: `3`
  - cash in: `122500`
  - cash out: `17500`
  - net: `105000`
- operational profit:
  - money in: `297500`
  - refunds: `17500`
  - external purchase: `0`
  - store stock COGS: `6720`
  - operational profit: `273280`
- package profit:
  - package value: `92500`
  - sparepart: `17500`
  - sparepart COGS: `0`
  - product refund: `17500`
  - gross profit: `92500`
- inventory:
  - available quantity: `49`
  - sold quantity: `4`
  - refund/reversal quantity: `1`
  - correction/revision quantity: `2`
- transaction per note:
  - gross value: `20000`
  - allocated payment: `122500`
  - refund paid: `17500`
  - net cash: `105000`
  - refund due: `157500`
  - remaining refund due: `157500`
  - outstanding: `0`
  - paid notes: `1`

### GAP

Source-level proof is still pending for this reopened manual evidence.

Suspected boundaries to inspect:

- detail refund action availability for non-closed/non-fully-paid rows
- detail payment billing rows after selected product-component refund when
  service fee is intentionally non-refundable
- downward edit settlement/surplus carry-forward from a previously partially
  paid/refunded multi-line note to a small current revision
- report distinction between cash ledger money-in and transaction-report
  allocated payment after downward revision and refund due

### DECISION

Reopen 0045 for manual lifecycle mismatch. Do not patch blindly.

The next implementation pass must proceed as:

1. map source and DB for the fresh manual note
2. add/adjust characterization tests for each proven defect
3. patch one defect at a time
4. update this log after each proof

### ACTIVE STEP

Find the latest manual note and map note/detail payload fields for refund,
payment, edit settlement, and report rows.

### PROOF

Current proof is owner manual Brave evidence only. Local DB/source proof is
pending.

## 2026-06-26 Reopen 2 Source Proof 1

### FACT

Local DB read attempt failed from this session:

- command attempted to read latest `notes`
- connection: MySQL `127.0.0.1:3306`, database `bengkelhex`
- failure: `SQLSTATE[HY000] [2002] Unknown error while connecting`

Source proof found without DB:

- `CurrentRevisionDetailRowMapper` sets `can_refund=true` for both `open` and
  `close` line status.
- `SelectedNoteRowsRefundEligibilityGuard` rejects non-close rows with
  `Line open/belum lunas tidak boleh direfund.`
- Therefore refund UI can show/click a row that backend will reject. This
  matches the owner complaint that the button should not be usable from the
  start.
- `NoteBillingProjectionComponentRowsBuilder` turns refunded component money
  into renewed outstanding by calculating:
  `net_paid = allocated - refunded`, then `outstanding = total - net_paid`.
- `AllocatePaymentAcrossComponents` then skips refunded store-stock part
  components when `ReversedRefundedStoreStockPartPaymentGuard` sees an inventory
  reversal for that component.
- Therefore payment UI can offer a refunded/reversed store-stock component as
  payable while backend allocation correctly refuses it. This matches the
  `Tidak ada komponen note yang bisa dialokasikan untuk payment ini.` symptom.

### GAP

The `157500` refund-due amount after editing down to one `20000` product line
still needs DB-level proof. Source formula alone explains surplus behavior, but
not the exact reported amount.

### DECISION

First candidate fixes should be scoped in this order:

1. row refund UI eligibility must match backend close-only refund guard
2. billing/payment rows must exclude or disable refunded store-stock components
   that backend payment allocation will skip
3. downward edit surplus/refund-due math needs DB/test reproduction before
   changing settlement logic

### ACTIVE STEP

Create characterization tests for the two source-proven UI/backend mismatches
before patching them.

### PROOF

Source files inspected:

- `CurrentRevisionDetailRowMapper`
- `SelectedNoteRowsRefundEligibilityGuard`
- `NoteBillingProjectionComponentRowsBuilder`
- `AllocatePaymentAcrossComponents`
- `ReversedRefundedStoreStockPartPaymentGuard`

## 2026-06-26 Reopen 2 Test Proof 1

### FACT

Added characterization tests:

- `CashierRefundRejectsOpenLineFeatureTest::test_detail_does_not_render_open_partially_paid_line_as_refundable`
- `ManualFullRefundEditLifecycleMismatchFeatureTest::test_detail_payment_does_not_offer_refunded_reversed_store_stock_component_as_payable`

### PROOF

Command:

```bash
php artisan test tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php
```

Result:

- `2 failed`, `4 passed`
- open partially paid line still rendered `data-refund-row="1"`
- detail still had `can_show_payment_form=true` when the only outstanding row
  was a refunded/reversed store-stock component that backend allocation skips

### ACTIVE STEP

Patch refund row eligibility first, then patch payable billing row eligibility.

## 2026-06-26 Reopen 2 Patch Proof 1

### FACT

Patches applied:

- `CurrentRevisionDetailRowMapper`
  - `can_refund` is now true only for operationally `close` rows.
- `NoteDetailRowMapper`
  - legacy/detail row mapper now follows the same close-only refund UI rule.
- `NoteBillingProjectionComponentRowsBuilder`
  - skips rendering a store-stock component as payable when that component has
    refund money and an inventory reversal exists for the same
    `component_ref_id`.

### DECISION

The UI now mirrors backend guards:

- refund UI follows `SelectedNoteRowsRefundEligibilityGuard` close-only rule
- payment UI follows `ReversedRefundedStoreStockPartPaymentGuard` for refunded
  store-stock components

### PROOF

Command:

```bash
php artisan test tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php
```

Result:

- `6 passed`
- `44 assertions`

### NEXT

Run payment/refund/report regression subset before investigating the exact
`157500` refund-due report/edit-down amount.

## 2026-06-26 Reopen 2 Regression Proof 1

### PROOF

Command:

```bash
php artisan test tests/Feature/Note/CashierRefundSelectionFirstFeatureTest.php tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php tests/Feature/Payment/RecordSelectedRowsNotePaymentFeatureTest.php tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php
```

Result:

- `86 passed`
- `539 assertions`

### CURRENT STATUS

Fixed and proven:

- open/partially-paid rows no longer render as clickable refund rows
- refunded + inventory-reversed store-stock components no longer render as
  payable billing rows
- backend payment/refund/report regression subset remains green

Still open:

- exact edit-down-to-`20000` surplus/refund-due amount (`157500`) needs a DB or
  test reproduction before changing settlement/report behavior

## 2026-06-26 Reopen 2 Full Verify Follow-up

### FACT

First `make verify` after Reopen 2 patches found three stale UI expectations:

- unit mapper test still expected open rows to be refundable
- open-note standby view still expected refund/cancel action on unpaid row
- current-revision product-only detail row lost product-name display when the
  refund affordance was removed from open rows

### PATCH

- Updated tests to encode close-only refund UI contract.
- Added `CurrentRevisionStoreStockLineLabelResolver`.
- Updated `CurrentRevisionDetailBaseRowMapper` to render current-revision
  store-stock product labels/subtitles independently from refund affordance.

### PROOF

Command:

```bash
php artisan test tests/Unit/Application/Note/Services/NoteDetailRowMapperTest.php tests/Feature/Note/CashierNoteDetailProductNameDisplayFeatureTest.php tests/Feature/Note/CashierOpenNoteRefundStandbyViewFeatureTest.php
```

Result:

- `4 passed`
- `17 assertions`

## 2026-06-26 Reopen 2 Full Verify Proof

### PROOF

Command:

```bash
make verify
```

Result:

- PHPStan: no errors
- line-limit audit: passed
- Blade PHP/directive audit: passed
- contract audit: passed
- Pest: `1422 passed`
- assertions: `8459`
- duration: `99.67s`

### CURRENT STATUS

Fixed and verified:

- rows that are not fully paid no longer render clickable refund UI
- refunded + inventory-reversed store-stock components no longer render as
  payable billing rows
- current-revision product-only detail rows still show product names after
  refund affordance was removed from open rows

Still open for a separate pass:

- manual edit-down to one `20000` product line producing `157500` refund due
  needs exact DB/test reproduction before settlement/report logic changes

## 2026-06-26 Reopen 3 Owner Live Note Product Refund Proof

### FACT

Owner reported a new live MariaDB case:

- note id: `12cd7684-1cfe-453b-854b-e1cb86899c89`
- create flow contained four transaction types:
  - product-only store stock row
  - service-only row
  - service with store-stock package/product component row
  - service with external purchase row
- owner paid partially, then settled/lunasi, then refunded the product
  components.
- UI/report still showed:
  - Grand Total `242000`
  - Sudah Dibayar `207000`
  - Total Refund `35000`
  - Sisa Tagihan `35000`
- executing the offered `lunasi` failed with:
  `Billing row pembayaran yang dipilih tidak valid untuk nota ini.`

DB proof for the live note:

- `customer_payments` total cash in: `242000`
- `customer_refunds` total cash out: `35000`
- refunded components:
  - `service_store_stock_part` `17500`
  - `product_only_work_item` `17500`
- inventory movements for both refunded store-stock lines had stock-out then
  stock-in reversal.
- `work_items.status` for the product-only work item was `canceled`, but the
  current revision snapshot still had the original revision line status `open`.

### ROOT CAUSE

The remaining mismatch was not only a Blade button problem.

Current-revision settlement was still calculating line outstanding as:

```text
line subtotal - net paid after refund
```

That made fully refunded product components become collectible debt again even
though backend payment allocation correctly treated them as non-payable:

- product-only full refund should not reopen a payable billing row
- store-stock product refund with inventory reversal should not reopen a
  payable billing row
- reports should keep cash history visible but should not turn those refunded
  product components into current collectible debt

### PATCH

Patch behavior now separates cash history from collectible debt:

- `CurrentRevisionComponentCollectibleSettlementBuilder`
  - computes current line settlement from component-level paid/refund totals
  - treats fully refunded `product_only_work_item` components as
    non-collectible
  - treats refunded + inventory-reversed `service_store_stock_part`
    components as non-collectible
- `CurrentRevisionComponentSettlementSummaryBuilder`
  - uses collectible component settlement for `net_paid_rupiah` and
    `outstanding_rupiah`
- `CurrentRevisionRowSettlementProjector`
  - passes component-level payment/refund totals into the summary builder
- `NoteBillingProjectionRefundedStoreStockComponentSkipper`
  - skips fully refunded product-only billing rows, in addition to reversed
    store-stock part rows
- `NoteDetailOperationalPayloadBuilder`
  - treats `outstanding_rupiah <= 0` as close/lunas in the detail payload
- `NoteOperationalStatusResolver`
  - uses current-revision `outstanding_rupiah` as the close/open decision when
    current-revision settlement exists
- `ManualFullRefundEditLifecycleMismatchFeatureTest`
  - adds regression coverage for refunded canceled product-only rows not
    becoming collectible debt

### PROOF

Target proof:

```bash
php artisan test tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php
```

Result:

- `5 passed`
- `44 assertions`

Selected regression proof:

```bash
php artisan test tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php tests/Feature/Payment/RecordCustomerRefundFeatureTest.php tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php tests/Feature/Reporting/TransactionReportPageFeatureTest.php tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php tests/Unit/Application/Note/Services/NoteOperationalStatusResolverTest.php
```

Result:

- `80 passed`
- `548 assertions`

Full verify proof:

```bash
make verify
```

Result:

- PHPStan: no errors
- line-limit audit: passed
- Blade PHP/directive audit: passed
- contract audit: passed
- Pest: `1423 passed`
- assertions: `8467`
- duration: `107.05s`

Live note payload proof after patch:

```json
{
  "operational_status": "close",
  "grand_total_rupiah": 242000,
  "total_allocated_rupiah": 242000,
  "total_refunded_rupiah": 35000,
  "net_paid_rupiah": 207000,
  "outstanding_rupiah": 0,
  "can_show_payment_form": false,
  "can_show_settle_payment_action": false,
  "billing_outstanding": []
}
```

Projection rebuild command run locally:

```bash
php artisan projection:rebuild-indexes note
```

Result:

- `Note projection: 1/1`
- `Projection rebuild selesai.`

Live projection after rebuild:

```json
{
  "total_rupiah": 224500,
  "allocated_rupiah": 242000,
  "refunded_rupiah": 35000,
  "net_paid_rupiah": 207000,
  "outstanding_rupiah": 0,
  "line_open_count": 0,
  "line_close_count": 2,
  "line_refund_count": 2
}
```

### CURRENT STATUS

Fixed and verified:

- product-only full refund no longer opens payable billing debt
- refunded + inventory-reversed store-stock components no longer open payable
  billing debt
- detail payload hides payment/lunasi for the live note
- transaction summary/report projection can show cash history while current
  collectible outstanding is `0`
- local note projection was rebuilt for the live database case

Still open for a separate pass:

- manual edit-down to one `20000` product line producing `157500` refund due
  still needs exact DB/test reproduction before settlement/report logic changes
