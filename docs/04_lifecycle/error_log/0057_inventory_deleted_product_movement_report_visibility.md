# 0057 - Inventory Deleted Product Movement Report Visibility

## Status

Resolved.

## Context

Slice ini memastikan laporan movement inventory tetap audit-friendly ketika produk sudah soft-deleted atau ketika data legacy/corrupt memiliki movement dengan `product_id` tanpa row `products`.

Hard boundary:

- Tidak mengubah costing engine.
- Tidak mengubah HPP.
- Tidak mengubah semantic `inventory_value_rupiah`.
- Tidak repair/write production DB.
- Tidak menambah migration.
- Tidak mengubah bucket membership `inventory_movements.source_type`.
- Tidak memasukkan ledger-only/orphan product ke current snapshot.

## Problem

Movement historis harus tetap terlihat untuk audit, walaupun produk sudah soft-deleted atau product row hilang.

Sebelum slice ini:

- Movement summary sudah memakai `leftJoin('products')`, sehingga deleted/orphan movement tidak hilang.
- Current snapshot sudah memakai `products` aktif dan `whereNull('products.deleted_at')`, sehingga deleted/orphan/ledger-only product tidak masuk snapshot.
- Namun fallback label orphan masih raw `product_id`.
- Soft-deleted product masih tampil seperti produk aktif, tanpa penanda bahwa produk sudah terhapus.

Ini berisiko membuat owner salah membaca movement historis sebagai produk aktif normal.

## Source Map

### Query

- `app/Adapters/Out/Reporting/InventoryMovementSummaryDatabaseQuery.php`
  - Source utama: `inventory_movements`
  - Join product: `leftJoin('products', 'products.id', '=', 'inventory_movements.product_id')`
  - Join projection: `leftJoin('product_inventory')`
  - Join costing: `leftJoin('product_inventory_costing')`
  - Label fallback:
    - active product: nama asli
    - soft-deleted product: `[Produk terhapus] {nama_barang}`
    - orphan/missing product: `[Produk tidak ditemukan: {product_id}]`

### Snapshot Guard

- `app/Adapters/Out/Reporting/InventoryCurrentSnapshotDatabaseQuery.php`
  - Source utama: `products`
  - Filter: `whereNull('products.deleted_at')`
  - Syarat masuk snapshot: punya `product_inventory` atau `product_inventory_costing`
  - Orphan/ledger-only product tetap tidak masuk current snapshot.

### Dataset

- `app/Application/Reporting/UseCases/GetInventoryStockValueReportDatasetHandler.php`
  - `movement_rows` dari movement summary.
  - `snapshot_rows` dari current snapshot.
  - `summary` dihitung dari dua dataset tersebut.

### Export/UI Impact

- Excel movement sheet memakai `movement_rows`, sehingga fallback label aman ikut tampil di export.
- Page/PDF saat ini memakai summary-only, bukan row-level movement, sehingga tidak terdampak label per produk.
- Summary period tetap dihitung dari movement ledger, bukan dari product row aktif.

## Test Added

File:

- `tests/Feature/Reporting/InventoryDeletedProductMovementReportVisibilityFeatureTest.php`

Test:

- `test_inventory_stock_value_dataset_keeps_deleted_and_orphan_movements_with_safe_labels_without_snapshot_pollution`

Coverage:

- Active product tetap masuk snapshot dan movement.
- Soft-deleted product tetap muncul di movement report.
- Orphan/missing product movement tetap muncul dan tidak crash.
- Soft-deleted label aman: `[Produk terhapus] Deleted Part`.
- Orphan label aman: `[Produk tidak ditemukan: product-orphan]`.
- Orphan/deleted tidak mencemari current snapshot.
- Summary period tetap menghitung movement qty/value.
- `total_inventory_value_rupiah` tetap dari current snapshot aktif.

## Implementation

Patch kecil di:

- `app/Adapters/Out/Reporting/InventoryMovementSummaryDatabaseQuery.php`

Perubahan:

- Tambah `products.id` dan `products.deleted_at` ke `groupBy`.
- Ubah `nama_barang` select expression menjadi CASE expression:
  - `products.id IS NULL` => fallback orphan.
  - `products.deleted_at IS NOT NULL` => fallback soft-deleted.
  - selain itu => nama produk normal.

## Proof

Owner reported targeted tests PASS:

```bash
php artisan test \
  tests/Feature/Reporting/InventoryDeletedProductMovementReportVisibilityFeatureTest.php \
  tests/Feature/Reporting/GetInventoryMovementSummaryFeatureTest.php \
  tests/Feature/Reporting/InventoryMovementSummaryHardeningFeatureTest.php \
  tests/Feature/Reporting/GetInventoryStockValueReportDatasetFeatureTest.php
```

Result:

```text
PASS
```

## Risk Classification

### P0

None found after source map.

Movement historis tidak hilang karena query movement memakai `inventory_movements` sebagai source utama dan `leftJoin('products')`.

### P1

Resolved.

Auditability gap pada label deleted/orphan product sudah ditutup.

### P2

Excel movement sheet sekarang menerima label yang lebih aman karena memakai dataset movement yang sama.

### P3

Tidak ada refactor dilakukan.

