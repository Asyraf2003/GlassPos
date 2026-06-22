# 0035 - Supplier invoice received tax revision can leave inventory costing stale

Status: Reported

## Ringkasan

Supplier invoice tax diperlakukan sebagai landed cost/modal. Pada create flow, pajak sudah masuk ke `line_total_rupiah`, `unit_cost_rupiah`, inventory movement, dan inventory costing.

Bug-risk ditemukan pada update/revision invoice yang sudah received:

- invoice line dapat berubah dari modal lama ke modal baru;
- tetapi jika product/qty tidak berubah, delta inventory movement bisa kosong;
- akibatnya `supplier_invoice_lines` berubah, tetapi `product_inventory_costing` tetap pada nilai lama.

Dampak:

- struktur modal stok bisa tidak sama dengan invoice supplier terbaru;
- laporan inventory value bisa stale;
- laporan keuntungan/COGS berisiko memakai modal lama;
- selisih tidak terlihat sebagai error transaksi.

## Proof saat reported

Test hardening baru:

```bash
php artisan test tests/Feature/Procurement/SupplierInvoiceTaxFinancialInvariantFeatureTest.php
```

Hasil awal:

```text
3 passed
1 failed

Failure:
received invoice same qty tax revision keeps inventory costing presise

Expected product_inventory_costing:

avg_cost_rupiah = 11000
inventory_value_rupiah = 22000

Actual:

avg_cost_rupiah = 10000
inventory_value_rupiah = 20000
```

## Policy sementara

Sebelum inventory revaluation dirancang eksplisit, sistem harus reject revision invoice yang sudah received jika revision mengubah unit cost/modal line existing.

Alasan:

- silent mismatch lebih berbahaya daripada hard reject;
- revaluation otomatis butuh movement type / audit path sendiri;
- UI harus memberi error jelas agar operator tidak mengira revisi sudah aman.

## Target test matrix

Total target hardening baru: 28 test.

Progress:
- Step 1 fixed: received invoice unit-cost revision is rejected until revaluation exists.
- Step 2 fixed: sibling received-invoice guard tests pass.
- Step 3 added: payment/lunas matrix tests for exact paid total, partial paid below-total rejection, and reversed payment exclusion.


- 4 test dibuat di `SupplierInvoiceTaxFinancialInvariantFeatureTest`.
- 3 pass.
- 1 menjadi characterization proof untuk bug costing stale.

Sisa target:

- Parser/input pajak: 5 test.
- Allocator landed cost/unit cost: 5 test.
- Legacy DB/data lama tanpa tax metadata: 6 test.
- Paid/lunas revision matrix: 5 test.
- Received invoice/inventory costing/modal stok: 6 test.
- Reporting profit + stock value: 5 test.

## Next steps

1. Patch policy reject untuk received invoice unit cost revision.
2. Ubah test merah menjadi test reject policy.
3. Tambah sibling tests:
   - received invoice remove tax rejected;
   - received invoice fixed tax to percent rejected;
   - received invoice base price decrease rejected;
   - received invoice qty change with same unit cost still allowed;
   - received invoice product replacement still follows stock-out/stock-in path.
4. Setelah safe reject stabil, baru rancang revaluation otomatis jika owner memang mau revision modal pada barang received.
