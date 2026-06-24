# 0043 Service Package Component Refund Pay Again Inventory Cash Mismatch

## Status

Forensic audit only. Belum patch.

## Scope

Manual QA HyperPOS Laravel menemukan lifecycle kasir yang membuat laporan terlihat ngawur setelah:

1. Create nota kasir berisi service x product package, package product parts, service lain, dan pembayaran sebagian.
2. Edit/revision package.
3. Lunasi nota.
4. Refund selected rows/components.
5. Aksi lunasi muncul lagi.
6. User melunasi lagi.
7. Laporan cash/profit/package breakdown menjadi tidak sinkron.

Scope audit ini hanya data lifecycle:

- note state
- revision
- payments
- payment component allocations
- customer refunds
- refund component allocations
- inventory movements
- transaction summary
- cash ledger
- operational profit
- service package profit breakdown

Tidak mengubah Mobile API.
Tidak mengubah formula Operational Profit.
Tidak patch report sebelum akar write-side jelas.

## FACT

Kasus runtime lokal:

- note_id: `e5ed4fbc-4e3b-4105-af12-227886b1ab22`
- transaction_date: `2026-06-24`
- customer: `tes`
- final note_state: `closed`
- current_revision_id: `e5ed4fbc-4e3b-4105-af12-227886b1ab22-r002`
- final active total: `265000`

Audit membuktikan ini bukan full refund seluruh nota.

Refund yang tercatat:

- customer_refunds sum: `182500`
- refund_component_allocations sum: `182500`

Payment yang tercatat:

- payment 1: `210000` cash
- payment 2: `82500` cash
- payment 3 after refund/pay-again: `155000` transfer
- payment_component_allocations sum: `447500`
- net component paid after refund: `265000`

## DATA FOUND

Runtime sequence:

- `07:24:09` payment 1 dibuat sebesar `210000`.
- `07:28:49` revision r002 aktif.
- `07:32:51` payment 2 dibuat sebesar `82500`.
- `07:32:51` note auto-closed via `AUTO_CLOSE_ON_FULL_PAYMENT`.
- `07:33:57` refund dibuat sebesar `182500`.
- `07:33:57` mutation event hanya `note_rows_canceled_via_refund`.
- Tidak ada mutation event `note_refunded`.
- `07:35:44` payment 3 dibuat sebesar `155000`.

Refund component allocation:

- package part `prod-year-009`: `37500`
- package part `prod-year-030`: `90000`
- package part `prod-year-005`: `27500`
- canceled product-only work item: `27500`
- total: `182500`

Yang tetap paid setelah refund:

- service package service fee: `50000`
- service only: `60000`
- retained paid total: `110000`

Outstanding setelah refund:

- active note total: `265000`
- retained net paid: `110000`
- outstanding: `155000`

Payment ketiga sebesar `155000` dialokasikan lagi ke tiga package product components yang sebelumnya direfund.

Inventory movements:

- seluruh store stock line source punya net_qty `0`
- seluruh store stock line source punya net_cost `0`

Artinya stok untuk package parts sudah keluar lalu direversal masuk lagi, tetapi payment ketiga membayar ulang package parts yang sama tanpa stock_out baru.

## BROKEN INVARIANT

Invariant yang rusak bukan “full refunded note bisa dibayar lagi”.

Invariant yang rusak:

Selected/component refund pada service package membalik inventory product components, tetapi service package row tetap active dan product components yang sama tetap payable lagi.

Akibatnya:

- cash layer melihat product components sebagai outstanding lagi
- inventory layer sudah mengembalikan stok
- payment ketiga membayar product components yang sama
- tidak ada inventory stock_out baru untuk payment ketiga
- report membaca event cash/payment/refund yang memang sudah tidak sinkron secara write-side

## ROOT CAUSE CANDIDATE

Kandidat root cause utama:

Refund selected rows/components belum punya policy domain tegas untuk service package product components.

Ambiguitas policy:

1. Jika product components package direfund, apakah entire package row harus canceled?
2. Jika package row tetap active, apakah refunded product components boleh payable lagi?
3. Jika payable lagi, apakah inventory harus stock_out ulang?
4. Jika inventory sudah direversal, apakah report boleh tetap menganggap package sold amount aktif?

Source-level suspect:

- outstanding/payment allocation memperlakukan refund sebagai pengurang paid component, sehingga component bisa outstanding lagi.
- selected row cancel hanya membatalkan selected work items, bukan otomatis membatalkan service package row.
- finalize refunded note tidak jalan karena active note total masih `265000`, bukan `0`.
- normal payment flow masih memungkinkan pembayaran outstanding pada note closed/refund-sensitive flow.

## REPORT IMPACT

Transaction summary untuk note:

- gross_transaction_rupiah: `265000`
- allocated_payment_rupiah: `447500`
- refunded_rupiah: `182500`

Cash ledger untuk note:

- in cash: `210000`
- in cash: `82500`
- in transfer: `155000`
- out refund: `182500`
- net cash: `265000`

Operational profit:

- cash_in_rupiah: `447500`
- refunded_rupiah: `182500`
- store_stock_cogs_rupiah: `5573`
- cash_operational_profit_rupiah: `259427`

Service package profit breakdown:

- package_sold_amount_rupiah: `205000`
- parts_total_rupiah: `155000`
- refunded_product_component_rupiah: `155000`
- sparepart_cogs_rupiah: `0`
- sparepart_margin_rupiah: `155000`
- total_package_gross_profit_rupiah: `205000`

Report impact paling mencurigakan:

Service package breakdown menampilkan package sold amount dan parts total aktif, tetapi refunded product component sama dengan parts total dan COGS net zero. Ini gejala cash/inventory/package lifecycle tidak sinkron.

## WHY TEST SEBELUMNYA BELUM NANGKAP

Test sebelumnya mengunci bahwa partial refund boleh membuka outstanding lagi.

Gap test:

- belum ada test service package product component refund
- belum ada test pay-again setelah package product component refund
- belum ada assertion inventory reversal vs payment ulang
- belum ada assertion report package breakdown setelah component refund + pay again
- belum ada invariant bahwa package product components yang sudah inventory-reversed tidak boleh silently paid again tanpa stock_out baru

## WHAT IS SAFE / UNSAFE

Safe:

- tambah characterization test
- tambah policy decision doc
- audit source write-side refund/package/payment/inventory
- audit read model hanya setelah write-side invariant diputuskan

Unsafe:

- langsung ubah Operational Profit formula
- langsung ubah report agar “kelihatan benar”
- langsung block semua pay-again setelah refund
- langsung final refunded untuk semua selected refund
- ubah Mobile API
- patch tanpa test lifecycle package component refund

## PATCH PLAN

Belum patch.

Candidate patch plan setelah policy diputuskan:

1. Tambah characterization test untuk service package product component refund lalu pay-again.
2. Pilih satu domain policy:
   - refund product components package membatalkan entire package row; atau
   - package row tetap active, tapi refunded product components tidak boleh payable lagi; atau
   - pay-again allowed, tapi wajib create stock_out baru.
3. Terapkan guard write-side di payment/refund/package lifecycle, bukan hanya report.
4. Baru sesuaikan report jika write-side invariant sudah benar.

## TEST PLAN

Minimal tests:

- service package product component refund creates inventory reversal.
- after package component refund, same component cannot be silently paid again without stock_out.
- cash ledger after refund/pay attempt stays consistent with allowed lifecycle.
- transaction summary allocated/refunded does not exceed intended lifecycle.
- operational profit does not count cash for product components whose inventory is net zero unless stock_out is recreated.
- service package profit breakdown does not show full package profit from refunded product components with COGS zero.

## NEXT COMMAND

Next audit step should read source around:

- `SelectedNoteRowsPaymentAmountResolver`
- `NoteBillingProjectionBuilder`
- `ExistingPaymentComponentTotals`
- `AllocatePaymentAcrossComponents`
- `AllocateRefundAcrossComponents`
- `CancelSelectedRowsAndSyncActiveNoteTotal`
- `FinalizeRefundedNoteFromActiveRows`
- `AutoReverseRefundedStoreStockInventory`
- `ServicePackageProfitBreakdownQuery`

No patch before characterization test exists.
