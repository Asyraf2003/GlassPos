# ADR-0004 — Minimum Selling Price Guard

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Catalog / Note / Payment / Authorization / Audit

## Context

Sistem ini mengelola transaksi sparepart dan jasa dalam domain bengkel dengan sensitivitas finansial yang sangat tinggi. Kebutuhan bisnis yang sudah dikunci:

- harga jual resmi barang ditetapkan di master product
- harga jual pada transaksi boleh diubah per kasus
- harga jual transaksi **tidak boleh lebih rendah** dari harga jual minimum/default yang ditetapkan
- bila di lapangan customer pada akhirnya membayar lebih murah, hal itu tidak boleh merusak harga resmi sistem
- sistem tidak boleh melegalkan penurunan harga resmi di bawah batas minimum tanpa mekanisme kebijakan baru yang eksplisit

Risiko bila guard ini tidak ada:

- margin barang menjadi tidak terkendali
- laporan penjualan dan profit menjadi menipu
- kasir dapat menurunkan harga resmi tanpa kontrol
- audit harga menjadi lemah
- batas antara kebijakan harga dan improvisasi lapangan menjadi kabur

Domain ini juga sudah mengunci bahwa:

- semua nominal uang memakai integer rupiah
- payment parsial adalah fitur inti
- correction pada transaksi sensitif wajib diaudit
- laporan membaca data final domain, bukan menjadi sumber kebenaran baru

## Decision

Sistem menetapkan:

- **setiap product memiliki harga jual minimum/default yang menjadi batas bawah resmi**
- **harga jual line pada transaksi/note dapat di-override per kasus**
- **override harga hanya sah jika nilainya tidak lebih rendah dari harga minimum/default yang berlaku**
- **core domain harus menolak line transaksi yang berada di bawah batas minimum**
- **guard ini dijalankan di domain/application policy, bukan hanya di UI**

## Decision Details

### 1. Official price boundary

Untuk setiap transaksi yang menggunakan product store stock, sistem harus membandingkan harga line yang diajukan dengan batas minimum selling price yang resmi.

Bila:

- `transaction_line_price < minimum_selling_price`

maka operasi harus ditolak.

### 2. Scope of enforcement

Guard ini minimal berlaku pada:

- penjualan sparepart langsung
- penggunaan sparepart toko pada work item/note
- koreksi line price yang melibatkan product resmi
- perubahan harga line pada transaksi yang masih memperbolehkan edit secara domain

### 3. Domain placement

Guard harga minimum wajib hidup pada core domain/application policy.

Tidak boleh bergantung pada:

- validasi frontend
- controller
- JavaScript UI
- query database ad hoc

Alasannya:

- ini adalah aturan bisnis inti
- harus konsisten pada semua adapter
- harus tetap benar bila transaksi masuk dari HTTP, CLI, import, atau adapter lain

### 4. Boundary with real-world underpayment

Kebutuhan bisnis saat ini hanya mengunci bahwa harga resmi tidak boleh di bawah batas minimum.

Artinya:

- sistem **tidak** mencatat official line price di bawah minimum
- jika di lapangan ada praktik customer membayar lebih murah dan selisih ditanggung pribadi oleh kasir, mekanisme tersebut **bukan bagian dari guard harga minimum ini**
- bila nanti diperlukan mekanisme resmi untuk:
  - subsidi internal
  - write-off
  - approved discount exception
  - kasir menutup selisih melalui flow resmi

  maka itu harus diputuskan melalui ADR terpisah agar tidak mencampur price integrity dengan cash handling exception

Sampai ada keputusan baru, perilaku resmi sistem adalah:

- official selling price may vary upward or equal to floor
- official selling price may not go below floor

## Alternatives Considered

### Alternative A — Harga transaksi bebas selama total pembayaran cocok
Ditolak.

Alasan penolakan:

- bertentangan dengan kebutuhan bisnis
- merusak kontrol margin
- membuka peluang manipulasi harga resmi
- melemahkan audit operasional

### Alternative B — Harga minimum dijaga hanya di UI
Ditolak.

Alasan penolakan:

- mudah dilewati entry point lain
- bukan penempatan aturan yang tepat dalam hexagonal architecture
- tidak menjamin konsistensi pada semua adapter

### Alternative C — Harga minimum di-hardcode dalam controller atau query
Ditolak.

Alasan penolakan:

- tidak portable
- sulit dipelihara
- melanggar separation of concerns
- menyulitkan migrasi framework/language

### Alternative D — Membiarkan harga di bawah minimum dan membenarkannya sebagai diskon biasa
Ditolak.

Alasan penolakan:

- tidak ada kebutuhan bisnis yang mengizinkan itu saat ini
- mendistorsi arti harga resmi
- menyamarkan exception operasional menjadi perilaku normal

## Consequences

### Positive

- integritas harga resmi terjaga
- margin minimal lebih terkendali
- laporan penjualan sparepart lebih dapat dipercaya
- audit transaksi lebih sehat
- perilaku sistem sesuai kebutuhan bisnis yang sudah dikunci

### Negative

- transaksi yang di lapangan ingin lebih murah akan gagal lebih cepat
- bila bisnis ingin flow exception resmi di masa depan, perlu keputusan tambahan
- implementasi correction dan change price harus memanggil policy ini secara konsisten

## Invariants

- setiap product store stock memiliki referensi harga jual minimum/default
- official transaction line price untuk product resmi tidak boleh berada di bawah floor
- guard ini wajib konsisten pada semua adapter
- customer-owned part tidak tunduk pada aturan selling price product store stock
- external purchase cost line bukan subject dari minimum selling price product guard
- perubahan harga sensitif harus tetap dapat diaudit bila relevan

## Implementation Notes

- domain service/policy yang direkomendasikan:
  - `MinSellingPricePolicy`
  - `SellingPriceGuard`
- error domain yang direkomendasikan:
  - `PRICING_BELOW_MINIMUM_SELLING_PRICE`
- catalog harus menjadi sumber harga minimum resmi
- policy perlu mempertimbangkan effective product price state yang berlaku saat transaksi dibuat atau dikoreksi, sesuai aturan implementasi detail nanti
- bila nanti ada override khusus berbasis approval, itu harus menjadi decision terpisah dan tidak mengubah default invariant ini

## Related Decisions

- ADR-001 — One Note Multi-Item Model
- ADR-005 — Paid Note Correction Requires Audit
- ADR-006 — Costing Strategy Default Average, FIFO-ready
- ADR-008 — Audit-First Sensitive Mutations
- ADR-011 — Money Stored as Integer Rupiah
