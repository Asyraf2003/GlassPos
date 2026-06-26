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

## NEXT SAFE STEP

Read the local source and DB state for the latest matching note. Update this log
again before any patch with:

- actual note id
- actual allocation/refund rows
- source paths that decide each action
- first characterization test target
