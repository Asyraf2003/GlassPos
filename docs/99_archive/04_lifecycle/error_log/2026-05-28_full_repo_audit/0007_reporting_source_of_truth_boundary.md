# ERROR LOG 0007 - REPORTING SOURCE OF TRUTH BOUNDARY

## FACT
- Reporting di HyperPOS adalah pembaca domain final, bukan penentu state domain.
- Kontrak standar sudah jelas:
  - reporting hanya membaca final domain,
  - reporting tidak boleh menjadi source of truth,
  - reporting tidak boleh menjadi correction layer.
- Dari source yang diperiksa, reporting memang banyak memakai read-model/summary query di atas tabel final domain, bukan melakukan mutasi.
- Ada fallback/compatibility semantics yang benar-benar terkonfirmasi di reporting, terutama pada transaction cash ledger dan transaction summary:
  - ada query fallback yang membaca `customer_refunds` untuk membentuk cash ledger payment row ketika allocation rows belum ada,
  - ada query fallback pada transaction summary yang menggabungkan legacy payment allocation row dan refunded-payment fallback row.
- Ada juga compatibility filtering yang kuat di reporting:
  - reversal tables dipakai untuk mengecualikan row yang sudah direversal,
  - `COALESCE`, `CASE`, `GREATEST`, dan `whereNull(reversal.id)` dipakai untuk merapikan summary, bukan memperbaiki domain state.
- Saya menjalankan owner command `php artisan test --filter=TransactionCashLedger` dan `php artisan test --filter=GetOperationalProfitSummary`. Output lokal tidak clean karena environment MySQL tidak tersedia untuk banyak feature test; ada unit reporting test yang pass, tetapi feature test utama banyak gagal di layer koneksi. Itu GAP environment, bukan proof defect reporting.

## SOURCE EVIDENCE
- `docs/01_standards/domain/0053_reporting_boundary.md`
  - reporting only reads the final domain.
  - reporting must not become a source of truth.
  - do not place domain correction logic in reporting.
- `docs/01_standards/domain/0050_final_domain_map.md`
  - `product_inventory` + `inventory_movements` adalah source of truth stok.
  - `supplier_invoices` adalah basis stock entry point dan avg_cost/COGS.
  - reports must not move source of truth away from the final map.
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php`
  - cash, refund, dan surplus-refund-outflow digabung lalu diurutkan.
  - reconciliation menjumlahkan arah `in` dan `out`.
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php`
  - meng-union legacy payment allocation rows, component allocation rows, dan refunded-payment fallback rows.
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundedPaymentFallbackRowsQuery.php`
  - memakai `whereNotExists(payment_allocations)` dan `whereNotExists(payment_component_allocations)`.
  - membaca `customer_refunds` + `customer_payments` untuk fallback cash row.
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerLegacyPaymentAllocationRowsQuery.php`
  - memakai `payment_allocations` sebagai row source legacy.
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerComponentAllocationRowsQuery.php`
  - memakai `payment_component_allocations` sebagai row source component.
  - mengecualikan row yang sudah punya legacy `payment_allocations`.
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundRowsQuery.php`
  - membaca `customer_refunds` langsung sebagai outflow refund.
- `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerSurplusRefundPaidRowsQuery.php`
  - membaca `note_revision_surplus_refund_payments` aktif sebagai outflow tersendiri.
- `app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php`
  - cash payment totals menggabungkan `payment_allocations` dan fallback `customer_refunds` yang tidak punya allocation rows.
  - memakai `COALESCE` untuk merapikan summary.
- `app/Adapters/Out/Reporting/Queries/TransactionSummaryRefundDueTotalsQuery.php`
  - membaca `note_revision_surplus_dispositions` aktif sebagai refund due source.
- `app/Adapters/Out/Reporting/Queries/TransactionSummarySurplusRefundPaymentTotalsQuery.php`
  - membaca `note_revision_surplus_refund_payments` aktif sebagai surplus refund paid source.
- `app/Adapters/Out/Reporting/DatabaseSupplierPayableReportingSourceReaderAdapter.php`
  - summary dan reconciliation membaca `supplier_invoices`, `supplier_payments`, `supplier_receipts`, dan `supplier_receipt_lines`.
  - memakai `COALESCE` untuk total paid/receipt/qty.
- `app/Adapters/Out/Reporting/SupplierPayableReportingQueryFactory.php`
  - payment totals mengabaikan `supplier_payment_reversals` dengan `leftJoin` + `whereNull`.
  - filtered invoices dipakai sebagai batas dataset.
- `app/Adapters/Out/Reporting/DatabasePayrollReportingSourceReaderAdapter.php`
  - payroll report mengecualikan `payroll_disbursement_reversals` dan memakai `COALESCE`.
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/PayrollDisbursementPerDayQuery.php`
  - per-day payroll juga mengecualikan reversal rows.
- `app/Adapters/Out/Reporting/DatabaseEmployeeDebtReportingSourceReaderAdapter.php`
  - employee debt report memakai payment reversals exclusion dan summary reconciliation.
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/EmployeeDebtCashOutPerDayQuery.php`
  - employee debt cash out harian dijumlahkan langsung dari `employee_debts`.
- `app/Adapters/Out/Reporting/DatabaseOperationalExpenseReportingSourceReaderAdapter.php`
  - operational expense report membaca `operational_expenses` aktif saja.
- `app/Adapters/Out/Reporting/Queries/DashboardOperationalPerformance/OperationalExpensePerDayQuery.php`
  - operational expense harian memakai `deleted_at is null`.
- `app/Adapters/Out/Reporting/Queries/OperationalProfitMetricsQuery.php`
  - operational profit adalah komposit dari cash in, refund, product costs, expense, payroll, dan employee debt.
- `app/Adapters/Out/Reporting/Queries/OperationalProfit/CashFlowMetricQuery.php`
  - cash in dari `customer_payments`.
  - refund memasukkan `customer_refunds` + surplus refund paid aktif.
- `app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php`
  - external purchase cost mengurangi `refund_component_allocations` untuk component refund.
  - store stock COGS memakai `inventory_movements` dan membedakan `stock_out`/reversal via `ABS`/positif.
- `app/Adapters/Out/Reporting/Queries/DashboardInventory/DashboardInventorySnapshotSummaryQuery.php`
  - inventory value snapshot membaca `product_inventory` + `product_inventory_costing`.
  - `CASE` dipakai untuk klasifikasi stock-safe/low/critical/unconfigured.
- `app/Adapters/Out/Reporting/Queries/DashboardInventory/DashboardInventoryMovementSummaryQuery.php`
  - movement summary memakai `CASE` untuk supply in, sale out, refund reversal, dan revision correction classification.
- `resources/views/admin/reporting/operational_profit/index.blade.php`
  - view hanya menampilkan metric report.
- `resources/views/admin/reporting/payroll/index.blade.php`
  - view menampilkan note bahwa payroll yang direversal tidak ikut dihitung.
- `tests/Feature/Reporting/RefundedNoteCashReportingFallbackFeatureTest.php`
  - fully refunded note dengan allocation kosong tetap menghasilkan net cash dan outstanding yang benar dari fallback dataset.
- `tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php`
  - cash ledger menggabungkan cash records, component allocations, refunds, dan surplus refund paid.
- `tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`
  - operational profit netting terhadap refunded note, reversed payroll, deleted expense, dan employee debt berjalan sesuai dataset.
- `tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php`
  - stock value report menggabungkan snapshot dan movement summary secara konsisten.
- `tests/Feature/Reporting/GetPayrollReportDatasetFeatureTest.php`
  - payroll dataset mengecualikan payroll yang direversal.
- `tests/Feature/Reporting/GetSupplierPayableReportDatasetFeatureTest.php`
  - supplier payable report menghitung paid/outstanding dari invoice/payment/receipt dataset.

## REPORTING CONTRACT
- Reporting hanya membaca final domain.
- Reporting boleh melakukan transformasi read-model, agregasi, filtering, dan compatibility join untuk menampilkan data final.
- Reporting tidak boleh menulis ulang domain state.
- Reporting tidak boleh menjadi tempat memperbaiki data yang rusak.
- Compatibility fallback yang sudah ada harus diperlakukan sebagai keputusan legacy compatibility, bukan otomatis sebagai defect domain.
- Jika ada fallback legacy yang masih dibutuhkan, owner perlu memutuskan apakah itu:
  - compatibility yang dipertahankan,
  - atau path yang nanti harus dipindahkan ke domain final setelah migrasi data/contract selesai.

## FINDINGS
1. CONFIRMED: reporting memang membaca final domain dan memakai query read-only, bukan mutasi.
2. CONFIRMED: ada fallback legacy yang eksplisit di transaction cash ledger dan transaction summary.
3. CONFIRMED: ada compatibility filter untuk reversal / deleted rows di payroll, supplier payable, operational expense, employee debt, dan operational profit.
4. CONFIRMED: inventory stock value report membaca snapshot source (`product_inventory` + `product_inventory_costing`) dan period movement summary, bukan membangun source of truth baru.
5. SUSPECTED / GAP: tidak semua fallback/compatibility pattern berarti defect. Sebagian adalah legacy bridge yang mungkin masih diperlukan.
6. GAP: belum ada owner decision yang memetakan fallback reporting mana yang harus dipertahankan sebagai compatibility dan mana yang harus dipindahkan/ditiadakan saat domain final sudah stabil.
7. GAP: belum ada proof lengkap bahwa semua reporting endpoint memiliki dataset coverage untuk kasus normal payment/refund/allocation dan kasus legacy tanpa allocation rows pada semua report yang relevan.

## IMPACT
- Fallback compatibility yang tidak diklasifikasikan dengan benar bisa membuat reporting terlihat "benar" walaupun sebenarnya sedang mengandalkan legacy bridge.
- Sebaliknya, memaksa menghapus fallback tanpa owner decision bisa merusak laporan historis yang masih bergantung pada legacy data.
- Karena reporting adalah boundary pembaca final domain, kesalahan klasifikasi di sini bisa mengaburkan apakah masalah sebenarnya ada di domain, migrasi data, atau hanya di compatibility layer report.
- Risiko terbesar bukan pada mutasi domain dari reporting, tetapi pada salah tafsir sumber kebenaran saat membaca hasil report.

## GAP
- Tidak ada owner decision eksplisit di source yang saya periksa untuk memisahkan:
  - fallback compatibility yang wajib dipertahankan,
  - fallback yang hanya sementara,
  - dan fallback yang harus dipindah ke domain final.
- Tidak ada proof bahwa semua laporan reporting sudah punya fixture pasangan:
  - payment/refund/allocation normal,
  - legacy payment tanpa allocation rows,
  - fully refunded / reversed / deleted compatibility case.
- Tidak ada proof bahwa UI report rendering tidak mengubah makna domain, walaupun view yang diperiksa sejauh ini hanya render metric.

## CLASSIFICATION
- `CONFIRMED` untuk boundary reporting sebagai read-only final-domain consumer.
- `CONFIRMED` untuk existence of fallback/compatibility queries yang eksplisit.
- `SUSPECTED` untuk anggapan bahwa fallback tertentu adalah defect; itu belum terbukti tanpa owner decision dan fixture comparison.
- `GAP` untuk klasifikasi akhir mana yang wajib dipertahankan sebagai legacy compatibility dan mana yang harus dihapus.

## SOLUTION DIRECTION, NO IMPLEMENTATION
- Pertahankan prinsip bahwa reporting hanya membaca final domain.
- Jangan ubah reporting menjadi correction layer.
- Jangan hapus fallback legacy hanya karena terlihat "tidak murni"; fallback harus dinilai terhadap kebutuhan kompatibilitas historis.
- Buat keputusan owner untuk tiap fallback yang ditemukan:
  - dipertahankan,
  - dipindahkan ke domain final,
  - atau dihapus setelah migrasi data selesai.
- Untuk inventory, supplier payable, payroll, employee debt, operational expense, dan operational profit, gunakan final domain map sebagai acuan utama, lalu nilai compatibility bridge secara terpisah.

## SUGGESTED NEXT PROOF
1. Siapkan fixture normal untuk payment/refund/allocation dan pastikan reporting cash ledger, transaction summary, dan operational profit menghasilkan angka exact.
2. Siapkan fixture legacy payment yang tidak punya allocation rows, lalu verifikasi apakah fallback reporting memang masih diperlukan dan hasilnya konsisten.
3. Siapkan fixture fully refunded / reversed / deleted untuk payroll, supplier payable, dan operational expense, lalu cocokkan apakah exclusion semantics sesuai kontrak final domain.
4. Lakukan cross-check untuk inventory stock value: snapshot normal, movement period normal, dan movement dengan refund reversal/correction classification.
5. Minta owner memutuskan per query apakah fallback itu compatibility bridge yang dipertahankan atau candidate untuk dipindahkan dari reporting.

## MINIMUM OWNER COMMANDS
```bash
rg -n "fallback|whereNotExists|customer_refunds|payment_allocations|payment_component_allocations|refund|surplus|COALESCE|CASE" app/Adapters/Out/Reporting tests/Feature/Reporting -g '*.php'
php artisan test --filter=TransactionCashLedger
php artisan test --filter=GetOperationalProfitSummary
```

## FINAL STATUS
- Status akhir: `GAP`.
- Reporting boundary-nya sendiri sudah jelas dan terkonfirmasi.
- Yang belum selesai adalah klasifikasi owner-level untuk fallback/compatibility semantics di beberapa query report.
- Dengan data yang ada sekarang, laporan ini tidak boleh disimpulkan sebagai defect reporting source-of-truth.
