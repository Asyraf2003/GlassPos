# Handoff — Nota Pelanggan UI, Payment, Versioning, Report Label Polish

## File Identity

- Path: `docs/handoff/v2/ui/note-ui-payment-report-polish-handoff.md`
- Scope: UI/detail nota, payment action sync, versioning label, report/index human-readable label polish
- Status: handoff-ready
- Last known proof from local run:
  - Tests: 740 passed
  - Assertions: 3856
  - Latest user confirmation: all latest patches passed

---

## Current Status

~~~text
Core nota/payment/refund/detail: 99.8%
Report/index/versioning polish: 98.8%
Overall current: ±99.0%
~~~

Not claimed as 100% because transaction summary / rekap transaksi per nota still needs audit and patch, and final live smoke across all reports has not been completed. Proof beats vibes. Annoying, but useful.

---

## Completed Work

### 1. Detail Payment UI Aligned with Create Flow

Completed:

~~~text
✅ Detail cash calculator now follows create payment flow.
✅ Tagihan, Uang Pelanggan, and Kembalian use the large calculator layout.
✅ Payment mode is clearer.
✅ Double-submit guard added.
✅ Payment buttons disabled after first submit.
~~~

Main files touched:

~~~text
resources/views/cashier/notes/partials/payment-modal.blade.php
public/assets/static/js/pages/cashier-note-payment.js
tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php
~~~

---

### 2. Payment Status and Action Sync Fixed

Major bug closed:

~~~text
Before:
- Status could show Belum Lunas while billing rows were all Lunas.
- Payment buttons could disappear while outstanding still existed.
- UI could look like repeated payment was possible.
- Revision rows and root note settlement had conflicting truths.

After:
- root work items = active operational/payment source.
- revision = timeline/versioning only.
- paid note shows Lunas.
- outstanding becomes 0.
- Bayar Sebagian / Lunasi buttons disappear when fully paid.
~~~

Live proof from the previously broken note:

~~~text
payment_status_label: Lunas
operational_status: close
grand_total_rupiah: 2200000
net_paid_rupiah: 2200000
outstanding_rupiah: 0
can_show_payment_form: false
can_show_partial_payment_action: false
can_show_settle_payment_action: false
billing_rows: all Lunas
~~~

Main files touched:

~~~text
app/Application/Note/Services/NoteWorkspacePanelDataBuilder.php
app/Application/Note/Services/NoteDetailPageDataBuilder.php
app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php
app/Application/Note/Services/SelectedNoteRowsPaymentSelectionExpander.php
app/Application/Payment/Services/ResolveNotePayableComponentsSelectedRows.php
~~~

---

### 3. Payment Selected Row Shorthand Supported

Completed:

~~~text
✅ Backend can receive work_item_id as shorthand.
✅ Backend can still accept old billing component IDs.
✅ Allocation remains component-level.
✅ UI does not need to expose complicated component IDs.
✅ Reports remain precise at component allocation level.
~~~

Contract:

~~~text
UI may send:
- work_item_id
- old billing component id

Backend:
- expands selected row IDs to payable components
- writes payment_component_allocations
~~~

---

### 4. Product Labels Fixed in Detail Nota

Completed:

~~~text
✅ Detail line product displays nama_barang.
✅ Service + store stock product displays product name.
✅ UI no longer shows raw product_id such as product-oli-1 x2.
~~~

Main files touched:

~~~text
app/Application/Note/Services/NoteDetailProductLabelResolver.php
app/Application/Note/Services/NoteDetailRowPrimaryLabelResolver.php
app/Application/Note/Services/NoteDetailRowSubtitleBuilder.php
tests/Feature/Note/CashierNoteDetailProductNameDisplayFeatureTest.php
tests/Feature/Note/CashierNoteDetailServiceProductNameDisplayFeatureTest.php
~~~

---

### 5. Report / Index Label Polish Batch 1

Completed:

~~~text
✅ Hutang Supplier report uses nomor_faktur + supplier_name.
✅ Stok dan Nilai Persediaan report uses nama_barang / kode_barang.
✅ Arus Kas Transaksi report uses human-readable note label.
✅ Payment ID / Refund ID are no longer primary table labels.
~~~

Main files touched:

~~~text
resources/views/admin/reporting/supplier_payable/index.blade.php
app/Adapters/Out/Reporting/DatabaseSupplierPayableReportingSourceReaderAdapter.php
app/Application/Reporting/DTO/SupplierPayableSummaryRow.php
app/Application/Reporting/Services/SupplierPayableSummaryBuilder.php

resources/views/admin/reporting/inventory_stock_value/index.blade.php
app/Adapters/Out/Reporting/InventoryMovementSummaryDatabaseQuery.php
app/Adapters/Out/Reporting/InventoryMovementSummaryRowMapper.php

resources/views/admin/reporting/transaction_cash_ledger/index.blade.php
app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundRowsQuery.php
~~~

Test updated:

~~~text
tests/Feature/Reporting/GetSupplierPayableSummaryFeatureTest.php
~~~

Supplier payable display contract:

~~~text
- supplier_invoice_id remains available for backend/internal reference.
- nomor_faktur is the business-facing invoice label.
- supplier_id remains available for relation/internal reference.
- supplier_name is the business-facing supplier label.
~~~

---

### 6. Admin and Cashier Note Index Label Polish

Completed:

~~~text
✅ Header "No Nota" changed to "Nota".
✅ Search placeholder no longer emphasizes random note number.
✅ JS index no longer renders note_number as the primary label.
✅ Display uses customer + transaction date.
~~~

Main files touched:

~~~text
resources/views/cashier/notes/index.blade.php
resources/views/admin/notes/index.blade.php
public/assets/static/js/pages/cashier-note-index.js
public/assets/static/js/pages/admin-note-index.js
~~~

---

### 7. Versioning Nota Label Polish

Completed based on latest user confirmation: all tests passed.

Resolved issues:

~~~text
✅ revision_id is no longer displayed as the primary badge.
✅ raw created_by_actor_id removed from compact versioning display.
✅ product_id is no longer preferred fallback label in revision line snapshot.
✅ revision timeline displays line snapshot contents.
~~~

Main files touched:

~~~text
app/Application/Note/Services/NoteRevisionLineSnapshotLabelResolver.php
resources/views/cashier/notes/partials/note-revision-timeline.blade.php
resources/views/shared/notes/partials/versioning-compact.blade.php
~~~

---

## Locked Decisions

~~~text
1. root work items = active operational/payment/report settlement source.
2. revision = history/versioning/timeline only.
3. UI user-facing labels must not show UUID/product_id/note_id as the primary text.
4. Internal IDs remain allowed for route/backend/reference.
5. payment allocation remains component-level.
6. detail payment UI follows create payment flow DNA.
7. report labels must be business-readable:
   - nota: customer + tanggal
   - supplier payable: nomor faktur + supplier name
   - inventory: nama/kode barang
~~~

---

## Known Remaining Work

### P1 — Transaction Summary / Rekap Transaksi per Nota

Still needs audit and likely patch.

Likely file:

~~~text
resources/views/admin/reporting/transaction_summary/index.blade.php
~~~

Known issue from audit:

~~~text
<td>{{ $row['note_id'] }}</td>
~~~

Target:

~~~text
- Do not display raw note_id.
- Use customer_name + transaction_date as the primary note label.
- Add pagination template consistent with other index pages if data source supports it.
- If data source is not paginated yet, audit controller/handler before patching.
~~~

---

### P1 — Final Live Smoke

Do not claim 100% before live smoke proves these paths:

~~~text
1. create simpan nota
2. create bayar sebagian
3. detail lunasi
4. edit paid note
5. refund
6. supplier payable report
7. inventory stock value report
8. transaction cash ledger report
9. transaction summary report
10. versioning timeline
~~~

---

## Suggested Next Active Step

Start with transaction summary / rekap transaksi per nota.

Run audit:

~~~bash
echo "=== TRANSACTION SUMMARY FILES ==="
grep -RInE "transaction_summary|note_id|customer_name|pagination|links\\(|paginate\\(" \
  resources/views/admin/reporting \
  app/Adapters/Out/Reporting \
  app/Application/Reporting \
  tests/Feature/Reporting \
  | sed -n '1,260p'

echo "=== TRANSACTION SUMMARY VIEW ==="
sed -n '1,260p' resources/views/admin/reporting/transaction_summary/index.blade.php

echo "=== TRANSACTION SUMMARY TESTS ==="
find tests -type f | grep -Ei "TransactionSummary|transaction_summary|Reporting" | sort
~~~

Expected target patch:

~~~text
- view does not render note_id as primary user-facing text.
- query/row mapper already has or adds customer_name and transaction_date.
- pagination follows existing admin/cashier index style if source is paginated.
- if not paginated, audit controller/handler first.
~~~

---

## Final Handoff State

~~~text
Last known proof:
✅ Tests: 740 passed
✅ Assertions: 3856
✅ User confirmed latest versioning patch passed

Current progress:
Core nota/payment/refund/detail: 99.8%
Report/index/versioning polish: 98.8%
Overall: ±99.0%

Safest next step:
Audit + patch transaction_summary / rekap transaksi per nota.
~~~

Do not continue global ID-display replacement without file-level audit. The system already spent enough time pretending UUIDs are a language for cashiers.
