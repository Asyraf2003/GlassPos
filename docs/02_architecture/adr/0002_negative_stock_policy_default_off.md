# ADR-0002 — Negative Stock Policy Default Off

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Inventory / Note / Supplier / Reporting

## Context

Sistem ini mengelola stok sparepart untuk operasional bengkel. Dari kebutuhan bisnis yang sudah dikunci:

- stok negatif tidak sehat untuk operasional dan keuangan
- stok negatif default harus dilarang
- namun struktur hexagonal harus tetap memudahkan perubahan di masa depan bila suatu saat bisnis ingin mengizinkan behavior berbeda, misalnya model backorder atau kebijakan khusus lain

Di sisi lain, sistem ini juga memiliki aturan lain:

- stok bertambah normal hanya dari supplier receipt yang valid
- stok berkurang karena penjualan sparepart, pemakaian sparepart untuk servis, atau adjustment resmi
- supplier invoice tidak boleh menambah product baru bila master product belum ada
- laporan dan keuangan tidak boleh selisih bahkan 1 rupiah

Jika stok negatif dibiarkan secara default, maka:

- laporan persediaan menjadi tidak sehat
- perhitungan biaya dan laba menjadi tidak dapat dipercaya
- operasional berisiko mencatat penjualan/pemakaian barang yang sebenarnya tidak ada

## Decision

Sistem menetapkan kebijakan dasar:

- **stok negatif dilarang secara default**
- **validasi larangan stok negatif dijalankan di core domain inventory**
- **aturan ini dibuat sebagai policy/strategy yang dapat diganti di masa depan tanpa membongkar struktur inti**

## Decision Details

### 1. Default business rule

Pada semua use case yang mengurangi stok store inventory, sistem harus menolak operasi jika quantity keluar menyebabkan saldo menjadi kurang dari nol.

Use case yang terdampak minimal:

- pemakaian sparepart toko untuk work item/note
- penjualan sparepart langsung
- stock adjustment minus
- koreksi yang berdampak pada pengurangan stok

### 2. Domain location

Larangan stok negatif wajib hidup di core domain/application policy, bukan di:

- UI
- controller
- query database mentah
- validasi frontend

Alasan:

- aturan ini adalah aturan bisnis inti
- harus konsisten lintas adapter
- harus tetap benar bila nanti ada HTTP, CLI, Telegram, atau adapter lain

### 3. Extensibility

Walau default saat ini adalah larangan stok negatif, desain harus tetap membuka extension point seperti:

- `NegativeStockPolicy`
- `InventoryAvailabilityPolicy`

Dengan demikian, bila suatu hari bisnis berubah, implementasi dapat diganti tanpa mengubah arah arsitektur.

Namun selama belum ada ADR baru yang mengubahnya, perilaku resmi tetap:

- negative stock not allowed

## Alternatives Considered

### Alternative A — Mengizinkan stok negatif sejak awal
Ditolak.

Alasan penolakan:

- bertentangan langsung dengan kebutuhan bisnis
- merusak kesehatan operasional dan laporan
- menyulitkan kontrol stok nyata di bengkel
- berisiko membuat keuangan tampak sehat padahal data persediaan salah

### Alternative B — Larangan stok negatif hanya di UI
Ditolak.

Alasan penolakan:

- mudah ditembus oleh adapter lain
- tidak menjamin konsistensi lintas entry point
- bukan penempatan aturan yang benar untuk hexagonal architecture

### Alternative C — Larangan stok negatif hanya lewat database constraint
Ditolak sebagai solusi utama.

Alasan penolakan:

- constraint database berguna sebagai guard tambahan, tetapi tidak cukup untuk mengekspresikan keputusan bisnis
- error yang keluar cenderung tidak ramah domain
- sulit menjaga perilaku konsisten di level use case dan audit

## Consequences

### Positive

- kontrol stok lebih sehat
- laporan stok dan operasional lebih dapat dipercaya
- membantu menjaga akurasi COGS dan margin
- mencegah transaksi yang tidak didukung ketersediaan barang
- sesuai dengan kebutuhan bisnis yang sudah dikunci

### Negative

- beberapa transaksi lapangan akan gagal lebih cepat dan butuh penanganan operasional yang benar
- implementasi correction/reversal perlu hati-hati bila stok sudah terlanjur berubah
- testing inventory menjadi lebih ketat

## Invariants

- stok store inventory tidak boleh kurang dari nol
- semua pengurangan stok harus melalui movement resmi
- semua penambahan stok normal harus berasal dari jalur yang sah
- external purchase cost tidak boleh memakai jalur store inventory
- customer-owned part tidak boleh memengaruhi store inventory

## Implementation Notes

- perhitungan ketersediaan stok harus berbasis saldo yang dapat direkonstruksi dari movement resmi
- validasi stok perlu dijalankan sebelum commit mutasi yang mengurangi stok
- error domain yang direkomendasikan:
  - `INVENTORY_INSUFFICIENT_STOCK`
  - `INVENTORY_NEGATIVE_STOCK_NOT_ALLOWED`
- database constraint boleh dipakai sebagai lapisan pertahanan tambahan, tetapi bukan sumber utama aturan bisnis
- laporan harus dapat mengasumsikan bahwa saldo negatif tidak valid secara domain

## Related Decisions

- ADR-001 — One Note Multi-Item Model
- ADR-003 — External Spare Part as Case Cost
- ADR-006 — Costing Strategy Default Average, FIFO-ready
- ADR-011 — Money Stored as Integer Rupiah
- ADR-012 — Product Master Must Exist Before Supplier Receipt
