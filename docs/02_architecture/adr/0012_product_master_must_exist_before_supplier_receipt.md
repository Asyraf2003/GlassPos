# ADR-0012 — Product Master Must Exist Before Supplier Receipt

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Catalog / Supplier / Inventory / Reporting / Audit

## Context

Sistem ini mengelola sparepart toko dengan alur bisnis yang sudah dikunci:

- data barang tidak boleh bertambah kecuali dari alur yang sah
- supplier tidak boleh menambah barang baru bila nama barang belum ada di master
- barang dibuat lebih dulu di master dengan nama dan harga jual
- faktur supplier berfungsi untuk menambah stok dan mencatat harga beli
- stok masuk normal berasal dari supplier invoice/receipt yang valid

Kebutuhan ini berarti ada pemisahan yang tegas antara:

- **product catalog creation**
- **supplier procurement / stock receipt**

Tanpa pemisahan itu, akan muncul risiko:

- item baru lahir liar dari faktur supplier
- penamaan barang menjadi tidak konsisten
- harga jual default/minimum tidak terkendali
- laporan stok dan penjualan sulit direkonsiliasi
- supplier flow mengambil alih fungsi catalog

## Decision

Sistem menetapkan:

- **product master harus sudah ada sebelum barang dapat diterima melalui supplier flow**
- **supplier invoice/receipt tidak boleh menciptakan product baru secara implisit**
- **catalog creation dan supplier receipt adalah dua alur domain yang berbeda**
- **supplier flow hanya boleh mereferensikan product master yang valid**

## Decision Details

### 1. Separation of responsibilities

Catalog bertanggung jawab atas:

- identitas product
- nama product
- referensi dasar product
- harga jual minimum/default
- atribut master lain yang relevan

Supplier/procurement bertanggung jawab atas:

- supplier
- harga beli
- quantity masuk
- due date
- hutang supplier
- penerimaan stok

Dengan demikian, supplier flow tidak menjadi tempat lahirnya product master baru.

### 2. Validation rule

Pada saat membuat supplier invoice line atau supplier receipt line, sistem wajib memvalidasi bahwa setiap line:

- mereferensikan product master yang sudah ada dan sah

Bila product belum ada di master, operasi harus ditolak atau line tersebut harus dianggap invalid sesuai detail use case.

### 3. Why product must exist first

Alasan keputusan ini:

- menjaga kebersihan catalog
- memastikan harga jual resmi ditentukan di tempat yang benar
- mencegah duplikasi/spelling variant barang
- menjaga inventory dan reporting tetap konsisten
- sesuai dengan kebutuhan operasional yang sudah dikunci

### 4. Boundary with external purchase case cost

Keputusan ini berlaku untuk alur procurement/stock receipt toko.

Keputusan ini tidak mengubah ADR tentang:

- external spare part yang dibeli untuk kasus tertentu lalu dicatat sebagai biaya kasus, bukan inventory

Artinya:

- bila suatu pembelian memang tidak dimaksudkan menjadi inventory toko, ia tidak otomatis harus masuk jalur supplier receipt inventory
- namun bila pembelian itu dimaksudkan menjadi stok toko, product master tetap harus ada lebih dulu

## Alternatives Considered

### Alternative A — Supplier invoice boleh otomatis membuat product baru
Ditolak.

Alasan penolakan:

- bertentangan langsung dengan kebutuhan bisnis
- merusak kebersihan product catalog
- membuka risiko duplikasi barang
- mengaburkan sumber kebenaran harga jual

### Alternative B — Product dibuat otomatis dari supplier invoice lalu dirapikan nanti
Ditolak.

Alasan penolakan:

- memindahkan masalah ke belakang
- meningkatkan utang data
- tidak cocok untuk domain dengan stok dan laporan sensitif

### Alternative C — Catalog dan supplier flow dilebur jadi satu proses
Ditolak.

Alasan penolakan:

- tanggung jawab domain menjadi kabur
- sulit menjaga governance data barang
- menyulitkan portabilitas dan audit

## Consequences

### Positive

- product catalog lebih bersih
- naming dan pricing lebih terkontrol
- inventory receipt lebih disiplin
- supplier flow tetap fokus pada pembelian/stok masuk
- laporan stok dan penjualan lebih mudah direkonsiliasi

### Negative

- operasional harus memastikan product master dibuat lebih dulu
- ada langkah validasi tambahan pada supplier flow
- UI perlu membantu user memilih product master yang sudah ada agar tetap terasa sederhana

## Invariants

- supplier invoice line untuk inventory toko harus mereferensikan product master yang valid
- supplier flow tidak boleh menciptakan product master baru secara implisit
- harga jual resmi product ditentukan di catalog, bukan di supplier receipt
- harga beli resmi procurement berasal dari supplier invoice/receipt
- stock receipt untuk inventory toko hanya sah bila product reference valid

## Implementation Notes

- abstraction yang direkomendasikan:
  - `ProductRepository`
  - `SupplierInvoiceValidator`
  - `CatalogGuard`
- error domain yang direkomendasikan:
  - `SUPPLIER_INVOICE_UNKNOWN_PRODUCT`
  - `VALIDATION_UNKNOWN_PRODUCT`
- UI/adapter sebaiknya menyediakan lookup/search product master agar supplier input tetap nyaman tanpa mengorbankan rule domain
- bila nanti ada kebutuhan draft supplier invoice sebelum product dibentuk, perilaku itu harus diputuskan eksplisit agar tidak merusak invariant ini

## Related Decisions

- ADR-002 — Negative Stock Policy Default Off
- ADR-004 — Minimum Selling Price Guard
- ADR-006 — Costing Strategy Default Average, FIFO-ready
- ADR-009 — Reporting as Read Model
- ADR-011 — Money Stored as Integer Rupiah
