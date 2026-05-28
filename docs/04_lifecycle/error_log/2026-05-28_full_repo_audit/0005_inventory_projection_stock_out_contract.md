# ERROR LOG 0005 - INVENTORY PROJECTION STOCK OUT CONTRACT

## FACT
- Laporan ini adalah analisis kontrak stock-out inventory projection, bukan patch inventory, bukan refactor, dan bukan klaim runtime failure yang sudah terbukti.
- Owner proof menyatakan `php artisan test --filter=RebuildInventoryProjectionFeatureTest` PASS dengan `2 passed` dan `9 assertions`.
- Owner proof juga menyebut audit command inventory menemukan:
  - test stock_out exist untuk issue, reverse, dan rebuild costing
  - `InventoryProjectionService::applyMovements` exists
  - `IssueInventoryOperation` creates `stock_out` dengan `qty_delta` negatif
  - `InventoryCostingProjectionBuilder` handles `stock_out`
- `ProductInventory::increase()` menolak `qty <= 0`.
- `IssueInventoryOperation` membuat movement `stock_out` dengan `qty_delta = -$qty`.

## OWNER PROOF
- `php artisan test --filter=RebuildInventoryProjectionFeatureTest` PASS:
  - `2 passed`
  - `9 assertions`
- `rg inventory` owner proof membuktikan:
  - stock_out tests exist untuk issue / reverse / rebuild costing
  - `InventoryProjectionService::applyMovements` exists
  - `IssueInventoryOperation` creates stock_out with negative qty_delta
  - `InventoryCostingProjectionBuilder` handles stock_out

## SOURCE EVIDENCE
- `app/Application/Inventory/Services/InventoryProjectionService.php:21-34` menunjukkan `applyMovements()`:
  - mengambil inventory existing atau membuat `ProductInventory::create(..., 0)`
  - memanggil `$inv->increase($m->qtyDelta())` untuk setiap movement
  - hanya update costing projection jika `movementType() === 'stock_in'`
- `app/Application/Inventory/Services/InventoryProjectionBuilder.php:16-31` menjumlahkan `qtyDelta` per product lalu membangun `ProductInventory` baru dari total tersebut.
- `app/Application/Inventory/Services/InventoryCostingProjectionBuilder.php:20-43` secara eksplisit menangani `stock_out` dengan `abs($m->qtyDelta())` dan mengurangi `qty/value` ketika stok tersedia.
- `app/Application/Inventory/Services/IssueInventoryOperation.php:29-51` membuat movement `stock_out` dengan `qty_delta = -$qty`, lalu menurunkan inventory dan costing secara langsung.
- `app/Core/Inventory/ProductInventory/ProductInventory.php:51-58` menunjukkan `increase()` menolak `qty <= 0`.
- `app/Core/Inventory/ProductInventory/ProductInventory.php:60-73` menunjukkan `decrease()` menolak `qty <= 0` dan mencegah qty on hand negatif.
- `tests/Feature/Inventory/RebuildInventoryProjectionFeatureTest.php:19-117` membuktikan rebuild projection baseline:
  - stale projection diganti dari official ledger
  - rebuild tetap aman saat ledger kosong
- `tests/Feature/Inventory/IssueInventoryFeatureTest.php:19-124` membuktikan issue inventory menghasilkan movement `stock_out` dengan `qty_delta` negatif dan menurunkan projection/costing.
- `tests/Feature/Inventory/RebuildInventoryCostingProjectionWithStockOutFeatureTest.php:18-56` membuktikan rebuild costing projection menangani `stock_out`.
- `tests/Feature/Inventory/ReverseIssuedInventoryOperationFeatureTest.php:19-179` membuktikan reverse issued stock_out mengembalikan qty inventory dan costing serta menolak reversal sumber duplikat.
- `tests/Feature/Inventory/ReverseNoteStoreStockInventoryOperationFeatureTest.php:18-93` membuktikan reverse note store stock mengembalikan qty inventory dan costing melalui stock_in reversal.

## WHAT IS PROVEN
- RebuildInventoryProjectionFeatureTest PASS membuktikan rebuild baseline tertentu aman pada jalur builder/rebuild yang diuji.
- Inventory costing rebuild juga terbukti menangani stock_out.
- Issue inventory yang menghasilkan movement stock_out negatif berjalan lewat jalur operation langsung, bukan lewat `InventoryProjectionService::applyMovements`.
- Reverse issued / reverse note store stock tests membuktikan reversal movement dapat mengembalikan qty dan costing sesuai contract yang diuji.
- `ProductInventory::increase()` memang bukan API yang menerima zero atau negatif; itu penting karena `applyMovements()` memanggil `increase($m->qtyDelta())` secara langsung.

## WHAT REMAINS GAP
- Belum ada proof direct test yang memanggil `InventoryProjectionService::applyMovements()` dengan movement `stock_out` bernilai negatif.
- Belum ada proof direct test `applyMovements()` untuk kombinasi movement stock_out dan reverse movement dalam satu coverage yang eksplisit.
- Belum ada proof yang menunjukkan caller `applyMovements()` selalu menormalkan sign qtyDelta sebelum memanggil `increase()`.
- Belum ada proof bahwa seluruh edge case incremental projection stock-out sudah ditutup oleh test yang langsung menarget service ini.

## FINDINGS
- CONFIRMED: rebuild projection baseline tertentu aman, karena `RebuildInventoryProjectionFeatureTest` PASS dan handler rebuild memakai builder/replacement path yang diuji.
- CONFIRMED: `InventoryCostingProjectionBuilder` sudah menangani `stock_out` secara eksplisit, sehingga rebuild costing untuk stock-out bukan masalah yang belum dikenal.
- CONFIRMED: `IssueInventoryOperation` mengeluarkan movement `stock_out` dengan `qty_delta` negatif, jadi negative stock-out adalah bentuk data nyata di domain inventory.
- CONFIRMED: `ProductInventory::increase()` menolak qty <= 0, jadi ada kontrak domain yang membuat `applyMovements()` sensitif terhadap sign movement.
- GAP / EDGE RISK: `InventoryProjectionService::applyMovements()` memanggil `increase($m->qtyDelta())` untuk semua movement; jika movement `stock_out` bernilai negatif masuk ke jalur ini, maka itu berpotensi memicu DomainException atau kontradiksi kontrak input.
- CLASSIFICATION: ini adalah contract ambiguity / edge risk, bukan confirmed runtime failure, karena belum ada targeted test direct untuk `applyMovements()` dengan movement stock_out negatif.

## IMPACT
- Dampak utama ada pada incremental projection path, bukan pada rebuild baseline yang sudah PASS.
- Jika caller mengirim movement stock_out negatif ke `applyMovements()`, maka `ProductInventory::increase()` akan menolak nilai <= 0, sehingga incremental projection membutuhkan kontrak input yang jelas.
- Rebuild path aman tidak otomatis berarti incremental path aman; dua jalur itu berbeda dan harus diproven terpisah.
- Karena issue/reverse/rebuild costing tests sudah ada, risiko paling tajam bukan pada keberadaan stock_out, melainkan pada pemetaan sign dan siapa yang bertanggung jawab menormalkannya sebelum memanggil service projection incremental.

## CLASSIFICATION
- CONFIRMED
  - rebuild projection baseline aman pada test yang ada
  - costing rebuild menangani stock_out
  - issue inventory menghasilkan stock_out negatif
  - `ProductInventory::increase()` menolak qty <= 0
- EDGE RISK / CONTRACT AMBIGUITY
  - `InventoryProjectionService::applyMovements()` sensitif terhadap stock_out negatif
- GAP
  - direct targeted test `applyMovements()` dengan stock_out negatif
  - direct targeted test reverse movement pada `applyMovements()`

## SOLUTION DIRECTION, NO IMPLEMENTATION
- Tetapkan kontrak eksplisit untuk sign movement pada incremental projection service.
- Jika `applyMovements()` memang menerima `stock_out` negatif, maka service harus punya aturan normalisasi yang jelas dan teruji.
- Jika `applyMovements()` hanya untuk movement positif, maka caller harus menormalkan stock_out ke jalur lain sebelum masuk ke service ini.
- Pertahankan rebuild builder/rebuild costing sebagai baseline yang sudah dibuktikan, tetapi jangan gunakan itu sebagai bukti otomatis untuk incremental path.

## SUGGESTED NEXT PROOF
- Direct test `InventoryProjectionService::applyMovements()` dengan movement `stock_out` negatif.
- Direct test `InventoryProjectionService::applyMovements()` dengan reverse movement yang mengimbangi stock_out.
- Sample test yang menunjukkan siapa yang menormalkan sign movement sebelum service incremental dipanggil.
- Jika perlu, bandingkan hasil incremental applyMovements dengan rebuild builder pada ledger yang sama.

## MINIMUM OWNER COMMANDS
```bash
php artisan test --filter=RebuildInventoryProjectionFeatureTest
```

## FINAL STATUS
- Status: CONFIRMED rebuild baseline with GAP / edge risk
- Verdict: rebuild projection dan costing untuk stock_out terbukti aman pada test yang ada, tetapi incremental `InventoryProjectionService::applyMovements()` masih punya contract ambiguity pada movement stock_out negatif.
- Owner-facing summary: jangan klaim rebuild broken; yang belum terbukti adalah direct incremental service contract saat menerima stock_out negative movement, karena `increase()` menolak qty <= 0 dan belum ada targeted test yang menutup edge case itu.
