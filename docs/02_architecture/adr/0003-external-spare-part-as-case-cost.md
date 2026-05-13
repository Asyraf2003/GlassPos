# ADR-0003 — External Spare Part as Case Cost

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Note / Payment / Reporting / Inventory

## Context

Dalam operasional bengkel ini terdapat kasus nyata ketika sparepart untuk pekerjaan customer tidak diambil dari stok toko, tetapi dibeli dari luar untuk memenuhi kebutuhan kasus tertentu.

Kebutuhan bisnis yang sudah dikunci:

- sparepart beli luar tidak masuk inventory produk toko
- pencatatan paling sehat adalah menjadikannya biaya pada kasus
- alasan utama: user perlu melihat dengan jelas kenapa hasil bersih suatu servis menjadi sekian
- biaya part luar tidak boleh disamarkan menjadi jasa bersih karena itu berisiko membingungkan audit dan laporan
- contoh domain nyata:
  - charge ke customer: 90.000
  - biaya beli part luar: 30.000
  - hasil bersih operasional kasus: 60.000

Sistem juga sudah mengunci bahwa dalam satu note dapat ada beberapa work item dengan sumber sparepart berbeda-beda.

## Decision

Sistem menetapkan:

- **sparepart yang dibeli dari luar dicatat sebagai biaya kasus/work item**
- **sparepart beli luar tidak menambah stok inventory toko**
- **sparepart beli luar tidak diperlakukan sebagai product inventory receipt**
- **perhitungan margin/hasil bersih kasus harus dapat membaca pendapatan dan biaya eksternal ini secara terpisah**

## Decision Details

### 1. Domain placement

Biaya sparepart beli luar menjadi bagian dari domain Note/Work Item, bukan domain Inventory.

Secara konseptual, line ini diperlakukan sebagai:

- `external_purchase_cost_line`
- atau nama setara yang jelas menunjukkan bahwa ini biaya eksternal untuk menyelesaikan item/kasus

### 2. Financial effect

Saat line external purchase cost dicatat:

- total tagihan ke customer tetap mengikuti line resmi yang ditagihkan
- biaya eksternal tercatat sebagai komponen cost pada item/kasus
- inventory store stock tidak berubah
- laporan operasional dapat menghitung margin item/kasus dengan benar

### 3. Reporting effect

Karena biaya part luar dicatat eksplisit, laporan dapat membedakan:

- pendapatan jasa dan/atau pendapatan item
- biaya part luar
- hasil bersih operasional kasus

Ini penting agar tidak muncul pertanyaan operasional seperti:

- kenapa jasa tampak terlalu kecil
- kenapa margin kasus tidak jelas
- kenapa angka laporan tidak bisa ditelusuri

### 4. Boundary with inventory

External purchase cost tidak boleh dianggap sebagai:

- product receipt
- purchase stock
- on-hand inventory
- store-stock part usage

Jika suatu hari bisnis ingin memodelkan skenario lain, misalnya barang luar terlebih dahulu masuk inventory lalu dikeluarkan lagi, maka itu memerlukan keputusan baru. Sampai ada keputusan baru, model resmi tetap:

- external purchase is case cost, not inventory

## Alternatives Considered

### Alternative A — External purchase langsung dianggap bagian dari harga jasa bersih
Ditolak.

Alasan penolakan:

- menyembunyikan biaya nyata
- berisiko membingungkan user saat audit
- membuat margin sulit dijelaskan
- mempersulit laporan biaya dan analisis operasional

### Alternative B — External purchase dipaksa masuk inventory toko
Ditolak untuk default domain ini.

Alasan penolakan:

- tidak sesuai kebutuhan bisnis yang sudah dikunci
- menambah langkah operasional yang tidak perlu
- memalsukan realitas proses ketika barang memang hanya dibeli untuk kasus tertentu
- menambah noise di inventory movement

### Alternative C — External purchase dicatat di luar Note saja
Ditolak.

Alasan penolakan:

- memutus keterkaitan biaya dengan kasus sumbernya
- mempersulit pelacakan margin per note/work item
- membuat audit dan reporting menjadi lebih lemah

## Consequences

### Positive

- biaya kasus tercatat eksplisit
- margin/hasil bersih kasus lebih transparan
- inventory tetap bersih dari item yang tidak pernah benar-benar menjadi stok toko
- cocok dengan kebutuhan operasional lapangan
- memudahkan audit dan analisis laporan

### Negative

- model financial line di Note/Work Item menjadi lebih kaya
- laporan perlu membedakan pendapatan dan external cost dengan disiplin
- implementasi UI harus tetap sederhana walau struktur internal lebih detail

## Invariants

- external purchase cost tidak mengubah saldo inventory toko
- external purchase cost harus terikat ke note/work item yang relevan
- nilai biaya eksternal harus tercatat eksplisit
- margin item/kasus harus dapat ditelusuri dari data mentah
- line external purchase tidak boleh diproses oleh costing strategy inventory store stock

## Implementation Notes

- work item perlu mendukung line khusus untuk biaya eksternal
- penamaan line harus jelas dan tidak ambigu dengan service revenue atau store-stock part line
- laporan minimal perlu mampu menampilkan:
  - pendapatan note/item
  - biaya external purchase
  - hasil bersih operasional
- error/guard yang perlu dijaga:
  - line external purchase tidak boleh mengurangi stok
  - line external purchase tidak boleh diperlakukan sebagai product receipt
- bila nanti ada kebutuhan supplier payable untuk pembelian eksternal semacam ini, keputusan itu harus dibahas terpisah agar tidak mencampur jalur procurement stock dan case cost

## Related Decisions

- ADR-001 — One Note Multi-Item Model
- ADR-002 — Negative Stock Policy Default Off
- ADR-005 — Paid Note Correction Requires Audit
- ADR-006 — Costing Strategy Default Average, FIFO-ready
- ADR-009 — Reporting as Read Model
