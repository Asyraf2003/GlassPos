# ADR-0001 — One Note Multi-Item Model

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Note / Payment / Inventory / Audit

## Context

Domain bengkel yang dibangun bukan model kasir retail biasa. Dalam operasional nyata, satu customer dapat datang dengan beberapa kebutuhan sekaligus dalam satu kunjungan, misalnya:

- 1 barang/service menggunakan sparepart toko
- 1 barang/service menggunakan sparepart milik customer
- 1 barang/service tanpa sparepart
- 1 barang/service menggunakan sparepart yang dibeli dari luar

Kebutuhan operasional yang sudah dikunci:

- admin/kasir tidak boleh dipaksa mengubah kebiasaan menjadi membuat beberapa nota terpisah hanya karena item atau status berbeda
- user input harus tetap sederhana: 1 customer dapat dibuatkan 1 nota berisi berbagai list transaksi/kasus
- status item di dalam nota dapat berbeda-beda
- satu customer tetap boleh punya lebih dari satu nota aktif di waktu yang sama bila memang ada kasus lain
- sistem harus tetap akurat untuk pembayaran parsial, audit, stok, dan laporan

Jika sistem memaksa satu barang atau satu kasus menjadi satu nota terpisah, maka itu dianggap gagal menyelesaikan masalah operasional client.

## Decision

Sistem menggunakan model:

- **1 Note sebagai aggregate root transaksi/customer-facing**
- **1 Note dapat memiliki banyak Work Item**
- **setiap Work Item dapat memiliki status, komponen biaya, dan sumber sparepart yang berbeda**
- **payment tercatat pada level Note, dengan kemungkinan alokasi ke Work Item atau saldo total Note melalui policy/use case**
- **audit dan koreksi tetap dilacak pada Note dan referensi item terkait**

## Decision Details

### 1. Struktur konseptual

Satu Note minimal memuat:

- note number
- customer reference
- created by
- created at
- note status
- list of work items
- payment records
- correction records
- audit references
- totals

Satu Work Item minimal memuat:

- item identifier
- deskripsi unit/barang/objek servis
- item status
- service lines
- store-stock part lines
- customer-owned part lines
- external purchase cost lines
- subtotal item
- catatan item

### 2. Sumber sparepart per item

Setiap Work Item dapat memiliki line dengan source berikut:

- `store_stock`
- `customer_owned`
- `external_purchase`

Perbedaan source ini wajib ada di core karena mempengaruhi:

- stok
- biaya kasus
- margin
- audit
- laporan

### 3. Status

Status Note dan status Work Item dipisahkan.

Alasan:

- satu nota bisa memuat item dengan progres berbeda
- satu item bisa selesai, item lain masih pending
- user tetap melihat satu nota operasional, sementara sistem tetap dapat melacak granularitas proses

### 4. Payment model

Payment dicatat terhadap Note karena pengalaman user operasional berpusat pada nota.

Namun desain harus membuka kemungkinan:

- payment hanya mengurangi saldo total Note
- payment dialokasikan ke item tertentu
- payment parsial dilakukan bertahap

Pilihan detail alokasi diputuskan di use case/policy, bukan dengan mengubah model dasar Note multi-item.

## Alternatives Considered

### Alternative A — Satu barang/satu kasus = satu nota
Ditolak.

Alasan penolakan:

- bertentangan dengan kebiasaan operasional client
- memperumit admin/kasir
- memaksa user mengubah cara kerja lapangan
- memperbanyak nota tanpa nilai bisnis yang dibutuhkan
- membuat pengalaman input tidak natural

### Alternative B — Sistem diam-diam membuat banyak nota di belakang layar
Ditolak.

Alasan penolakan:

- menambah kompleksitas sinkronisasi
- berpotensi merusak ekspektasi user terhadap 1 nota
- mempersulit audit, koreksi, dan pelacakan payment
- meningkatkan risiko mismatch laporan

### Alternative C — Satu nota dengan satu daftar line datar tanpa konsep Work Item
Ditolak.

Alasan penolakan:

- tidak cukup kuat untuk memodelkan beberapa kasus/status berbeda dalam satu nota
- sulit mengelola item-level status
- sulit dibawa ke audit dan koreksi yang presisi

## Consequences

### Positive

- sesuai kebiasaan user lapangan
- 1 nota tetap menjadi pusat interaksi customer-facing
- status item dapat dipisahkan dengan rapi
- cocok untuk payment parsial
- cocok untuk audit dan correction
- lebih fleksibel untuk laporan operasional bengkel

### Negative

- model domain lebih kompleks dibanding POS retail biasa
- perhitungan total dan status harus lebih disiplin
- implementasi UI harus menjaga kesederhanaan walau model internal lebih kaya
- payment allocation perlu dirancang hati-hati

## Invariants

- satu Note dapat memiliki banyak Work Item
- satu Work Item hanya milik satu Note
- satu customer boleh memiliki lebih dari satu Note aktif
- user tidak dipaksa membuat banyak nota untuk satu kunjungan hanya karena item/status berbeda
- perubahan pada paid Note tidak boleh bebas dan harus melalui correction flow ter-audit
- semua total uang dihitung dari line resmi dan disimpan dalam integer rupiah

## Implementation Notes

- Note menjadi aggregate root utama untuk operasi create, add item, total calculation, payment recording, correction, dan audit reference
- Work Item tidak boleh menjadi aggregate terpisah yang menyebabkan user experience berubah menjadi multi-note
- status item dan status note harus dipisah sejak awal
- laporan dapat membaca summary per Note dan per Work Item
- adapter UI/HTTP/Telegram tidak boleh mengubah model dasar ini

## Related Decisions

- ADR-002 — Negative Stock Policy Default Off
- ADR-003 — External Spare Part as Case Cost
- ADR-005 — Paid Note Correction Requires Audit
- ADR-009 — Reporting as Read Model
