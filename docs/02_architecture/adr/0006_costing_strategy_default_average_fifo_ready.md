# ADR-0006 — Costing Strategy Default Average, FIFO-ready

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Inventory / Reporting / Note / Procurement

## Context

Sistem ini mengelola sparepart toko yang memengaruhi:

- stok
- cost of goods / biaya konsumsi sparepart toko
- laporan profitabilitas
- pembukuan operasional

Kebutuhan bisnis yang sudah dikunci:

- default saat ini menggunakan pendekatan **average**
- namun struktur hexagonal harus memungkinkan manuver ke **FIFO** di masa depan
- perubahan strategi costing tidak boleh memaksa pembongkaran arsitektur inti
- laporan tidak boleh menjadi tempat hardcode logika costing

Karena domain ini sensitif terhadap akurasi, strategi costing harus ditentukan secara eksplisit. Bila tidak diputuskan, maka:

- implementasi bisa diam-diam bercampur
- hasil laporan dapat tidak konsisten
- perpindahan ke FIFO di masa depan menjadi mahal

## Decision

Sistem menetapkan:

- **strategi costing default v1 adalah average**
- **perhitungan costing harus diletakkan pada abstraction/policy yang dapat diganti**
- **desain wajib FIFO-ready, tetapi FIFO belum menjadi default resmi**
- **laporan harus membaca hasil/domain data yang konsisten dengan costing strategy resmi, bukan membuat rumus costing sendiri di layer reporting**

## Decision Details

### 1. Official default

Selama belum ada ADR baru yang menggantikannya, strategi costing resmi untuk inventory store stock adalah:

- average costing

### 2. Extensibility requirement

Walau average menjadi default, desain wajib menyediakan extension point seperti:

- `CostingPolicy`
- `InventoryCostingStrategy`
- `COGSCalculator`

Tujuannya:

- implementasi saat ini sehat untuk average
- perpindahan ke FIFO di masa depan tidak memaksa perubahan bentuk domain inti
- adapter/report tidak bergantung pada satu algoritma yang di-hardcode sembunyi-sembunyi

### 3. Scope

ADR ini berlaku untuk item yang memang berasal dari inventory toko, misalnya:

- sparepart toko yang dijual
- sparepart toko yang dipakai pada work item/note

ADR ini **tidak** berlaku untuk:

- customer-owned part
- external purchase cost line yang tidak pernah masuk inventory toko

### 4. Boundary with reporting

Reporting tidak boleh menghitung costing dengan logika sendiri yang berbeda dari strategi resmi.

Artinya:

- reporting harus membaca hasil yang konsisten dengan policy costing resmi
- jika costing dihitung ulang, perhitungan ulang itu tetap harus memakai strategy yang sama
- read model tidak boleh menjadi sumber keputusan costing baru

### 5. Why not FIFO now

Keinginan bisnis jangka panjang mungkin condong ke FIFO, tetapi saat ini dipilih average karena:

- lebih sederhana untuk fondasi awal
- cukup untuk memulai sistem secara sehat
- tetap dapat dimanuver kemudian bila struktur policy dari awal sudah benar

## Alternatives Considered

### Alternative A — Langsung memakai FIFO sekarang
Tidak dipilih sebagai default saat ini.

Alasan:

- belum menjadi keputusan operasional final
- menambah kompleksitas lebih awal
- tidak perlu memaksa kompleksitas tambahan bila average sudah diterima untuk fase saat ini

### Alternative B — Tidak mengunci strategi costing dulu
Ditolak.

Alasan penolakan:

- menghasilkan ambiguitas
- rawan percampuran logika di beberapa tempat
- berisiko membuat laporan dan inventory berbeda asumsi
- menyulitkan migrasi ke strategi lain secara disiplin

### Alternative C — Hardcode average di reporting atau query repository
Ditolak.

Alasan penolakan:

- melanggar separation of concerns
- menyulitkan perubahan ke FIFO
- berpotensi menciptakan perhitungan berbeda antara domain dan laporan

## Consequences

### Positive

- fondasi awal lebih sederhana
- keputusan costing menjadi eksplisit
- perpindahan ke FIFO di masa depan lebih murah
- domain dan laporan dapat tetap konsisten
- sesuai dengan arah hexagonal yang portable

### Negative

- implementasi awal tetap perlu abstraction tambahan
- developer harus disiplin agar tidak menyisipkan rumus costing di luar policy
- bila nanti berpindah ke FIFO, perlu migrasi aturan dan validasi ulang laporan

## Invariants

- hanya store inventory items yang tunduk pada costing strategy inventory
- customer-owned part tidak ikut costing inventory toko
- external purchase cost line tidak ikut costing inventory toko
- official costing strategy aktif hanya satu untuk satu perilaku resmi pada satu waktu
- reporting harus mengikuti strategi costing resmi, bukan membuat strategi sendiri

## Implementation Notes

- abstraction yang direkomendasikan:
  - `CostingPolicy`
  - `InventoryCostingStrategy`
  - `CalculateInventoryCost`
- error/guard yang relevan dapat muncul saat data inventory tidak konsisten, tetapi detail error ditentukan di implementasi inventory/reporting
- supplier receipt, stock issue, dan stock adjustment perlu menyiapkan data yang cukup agar costing strategy dapat bekerja konsisten
- bila suatu hari FIFO diaktifkan, perubahan harus dilakukan melalui ADR baru dan disertai rencana dampak ke:
  - laporan
  - data historis
  - rekalkulasi bila diperlukan

## Related Decisions

- ADR-002 — Negative Stock Policy Default Off
- ADR-003 — External Spare Part as Case Cost
- ADR-009 — Reporting as Read Model
- ADR-011 — Money Stored as Integer Rupiah
- ADR-012 — Product Master Must Exist Before Supplier Receipt
