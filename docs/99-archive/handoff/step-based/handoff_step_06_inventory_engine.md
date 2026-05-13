# Handoff — Step 6 Inventory Engine

## Metadata
- Tanggal: 2026-03-13
- Nama slice / topik: Inventory Engine
- Workflow step: Step 6
- Status: CLOSED
- Progres: 100%

---

# Target halaman kerja

Menyelesaikan **Inventory Engine** sebagai fondasi domain stok sebelum masuk ke transaksi pelanggan (Step 7).

Target yang harus tercapai sesuai workflow:

- stok masuk dan keluar tercatat sebagai **inventory movement ledger**
- saldo stok dapat dihitung ulang dari ledger
- **negative stock dilarang**
- costing menggunakan **average cost**
- projection dapat **direbuild dari ledger**

Engine ini menjadi fondasi utama sebelum:

- note / transaksi pelanggan
- COGS
- laporan inventory

---

# Referensi yang dipakai `[REF]`

## Dokumen

Blueprint:
- `docs/blueprint/blueprint_v1.md`

Workflow:
- `docs/workflow/workflow_v1.md`

ADR:
- `docs/adr/0002-negative-stock-policy-default-off.md`
- ADR-006 Costing strategy average

DoD:
- `docs/dod/dod_v1.md`

Handoff sebelumnya:
- Step 3 Identity & Access
- Step 4 Product Master
- Step 5 Procurement Engine

Snapshot repo yang dipakai:
- movement ledger
- costing projection
- rebuild handlers
- feature tests inventory

---

# Fakta terkunci `[FACT]`

Berikut fakta implementasi yang **sudah terbukti lewat test suite**.

## 1 Inventory ledger hidup

Table:

- `inventory_movements`

Movement menyimpan:

- product_id
- movement_type
- source_type
- source_id
- tanggal_mutasi
- qty_delta
- unit_cost_rupiah
- total_cost_rupiah

Movement type yang aktif:

- `stock_in`
- `stock_out`

Ledger menjadi **source of truth inventory**.

---

## 2 Qty projection hidup

Table:

- `product_inventory`

Menjadi **projection saldo qty**.

Projection ini:

- bukan source of truth
- dapat direbuild dari ledger

Handler:

RebuildInventoryProjectionHandler

Test:

RebuildInventoryProjectionFeatureTest

Projection inventory dapat direbuild kapan saja dari ledger movement.

Projection ini menulis ulang saldo stok pada table:

product_inventory

Projection **bukan source of truth**.

Source of truth tetap berada pada:

inventory_movements

---

# Costing Projection Hidup

Table:

product_inventory_costing

Table ini menyimpan:

- avg_cost_rupiah
- inventory_value_rupiah

Projection costing menggunakan:

average costing strategy

Costing projection dapat direbuild dari ledger movement.

Handler:

RebuildInventoryCostingProjectionHandler

Test:

RebuildInventoryCostingProjectionFeatureTest

---

# Inventory Issue Engine Hidup

Engine ini digunakan untuk **stok keluar generik**.

Handler:

IssueInventoryHandler

Perilaku handler:

- membuat movement `stock_out`
- menurunkan qty projection
- menurunkan inventory value
- menjaga avg_cost tetap konsisten

Test:

IssueInventoryFeatureTest

---

# Negative Stock Policy Hidup

Policy domain:

app/Core/Inventory/Policies/NegativeStockPolicy.php

Implementasi default:

app/Application/Inventory/Policies/DefaultNegativeStockPolicy.php

Perilaku:

- stok tidak boleh menjadi negatif
- issue ditolak jika qty tidak cukup

Policy ini **extensible** sesuai ADR.

---

# Costing Rebuild Mendukung Stock Out

Handler:

RebuildInventoryCostingProjectionHandler

Sekarang rebuild costing memperhitungkan:

- stock_in
- stock_out

Test regression:

RebuildInventoryCostingProjectionWithStockOutFeatureTest

Tujuan:

memastikan projection costing selalu dapat direkonstruksi dari ledger.

---

# Scope yang Dipakai

## [SCOPE-IN]

Inventory engine mencakup:

- inventory movement ledger
- qty projection
- costing projection
- stock_in
- stock_out
- negative stock policy
- rebuild projection
- rebuild costing
- issue inventory generic

---

## [SCOPE-OUT]

Belum termasuk:

- transaksi pelanggan
- note / order
- COGS dari transaksi
- laporan inventory
- stock adjustment UI
- integration telegram
- PDF reporting

Hal-hal tersebut akan masuk pada workflow berikutnya:

Step 7+

---

# Keputusan yang Dikunci

## Ledger adalah Source of Truth Inventory

Saldo stok tidak menjadi sumber kebenaran.

Saldo harus dapat dihitung ulang dari:

inventory_movements

---

## Projection Boleh Dihapus dan Dibangun Ulang

Projection berikut:

- product_inventory
- product_inventory_costing

boleh dihapus dan direbuild dari ledger kapan saja.

---

## Negative Stock Default Tidak Diperbolehkan

Aturan domain:

stok tidak boleh negatif

Aturan ini dijalankan melalui **policy domain**.

---

## Costing Strategy Default adalah Average

Perhitungan cost inventory menggunakan:

average costing

Namun desain tetap membuka kemungkinan extension di masa depan (misalnya FIFO).

---

## Stock Out Harus Melewati Inventory Issue Engine

Stok keluar **tidak boleh dimanipulasi langsung** melalui projection.

Semua pengurangan stok harus melalui:

IssueInventoryHandler

Handler ini bertanggung jawab untuk:

- membuat movement ledger
- mengubah projection
- menjaga costing tetap konsisten

---

# File yang Dibuat / Diubah

## Core Domain

app/Core/Inventory/ProductInventory/ProductInventory.php

app/Core/Inventory/Movement/InventoryMovement.php

app/Core/Inventory/Costing/ProductInventoryCosting.php

---

## Policies

app/Core/Inventory/Policies/NegativeStockPolicy.php

app/Application/Inventory/Policies/DefaultNegativeStockPolicy.php

---

## Use Cases

app/Application/Inventory/UseCases/IssueInventoryHandler.php

app/Application/Inventory/UseCases/RebuildInventoryProjectionHandler.php

app/Application/Inventory/UseCases/RebuildInventoryCostingProjectionHandler.php

---

## Adapters

DatabaseInventoryMovementReaderAdapter

DatabaseInventoryMovementWriterAdapter

DatabaseProductInventoryReaderAdapter

DatabaseProductInventoryWriterAdapter

DatabaseProductInventoryCostingReaderAdapter

DatabaseProductInventoryCostingWriterAdapter

DatabaseProductInventoryCostingProjectionWriterAdapter

---

## Tests

tests/Feature/Inventory/IssueInventoryFeatureTest.php

tests/Feature/Inventory/RebuildInventoryProjectionFeatureTest.php

tests/Feature/Inventory/RebuildInventoryCostingProjectionFeatureTest.php

tests/Feature/Inventory/RebuildInventoryCostingProjectionWithStockOutFeatureTest.php

---

# Bukti Verifikasi

Command yang dijalankan:

~~~
php artisan test tests/Feature/Inventory
php artisan test tests/Feature/Procurement
php artisan test tests/Arch
~~~

Hasil:

Inventory tests

- 7 tests
- 28 assertions
- PASS

Procurement tests

- 8 tests
- 77 assertions
- PASS

Architecture tests

- 1 test
- PASS

Tidak ada regresi setelah:

- inventory issue
- costing rebuild
- negative stock policy

---

# Blocker Aktif

Tidak ada blocker aktif.

Inventory engine sudah stabil.

---

# Persiapan Step 7

Step berikutnya:

Step 7 — Note Multi Item Engine

Tujuan Step 7 adalah membangun engine transaksi pelanggan.

Domain utama yang akan hidup:

- CustomerOrder
- CustomerTransaction
- CustomerTransactionLine

---

# Tujuan Step 7

Membangun **note pelanggan multi-item** yang:

- dapat berisi beberapa barang
- menarik stok dari inventory engine
- menghitung COGS
- mencatat transaksi pelanggan

---

# Integrasi dengan Inventory Engine

Step 7 akan menggunakan:

IssueInventoryHandler

untuk mengeluarkan stok dari penjualan sparepart.

Alur transaksi pelanggan nantinya:

1. membuat transaksi
2. membuat transaction lines
3. memanggil inventory issue engine
4. menghasilkan movement ledger

---

# Prinsip Penting Step 7

Inventory **tidak boleh dimodifikasi langsung** oleh note.

Note hanya boleh memanggil:

Inventory Issue Engine

---

# Domain Contract Step 7

Entity utama yang akan muncul:

- CustomerOrder
- CustomerTransaction
- CustomerTransactionLine

---

# Output Minimal Step 7

- nota pelanggan
- multi item
- stok keluar otomatis
- COGS dihitung dari costing engine

---

# Status Workflow

Step 1 — ADR foundation DONE  
Step 2 — skeleton DONE  
Step 3 — identity & access DONE  
Step 4 — product master DONE  
Step 5 — procurement engine DONE  
Step 6 — inventory engine DONE  
Step 7 — note multi item engine NEXT

---

# Penutup

Inventory engine sekarang:

- stabil
- dapat direbuild dari ledger
- sudah terlindungi negative stock
- costing konsisten

Engine ini menjadi fondasi aman untuk membangun **transaksi pelanggan pada Step 7**.
