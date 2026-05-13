# P0 - Final Domain Map

## Tujuan
Mengunci peta domain final project kasir agar AI tidak mencampur istilah, source of truth, dan boundary bisnis.

## Final Domain Map
- `products` = master barang
- `product_inventory` + `inventory_movements` = source of truth stok
- `supplier_invoices` + items = pintu masuk stok dan basis avg_cost / COGS
- `customer_orders` = Nota Pelanggan
- `customer_transactions` = Kasus
- `customer_transaction_lines` = Rincian

## Mandatory Rule
- Gunakan istilah domain final secara konsisten.
- Jangan mencampur istilah final dengan istilah sementara lama.
- Jangan memindahkan source of truth dari map final tanpa keputusan eksplisit.

## Reporting Reminder
- Laporan membaca domain final.
- Laporan bukan source of truth baru.
