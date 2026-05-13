# ADR-0015 — Note Operational Status Uses Open Close With Editable Partial Payment

- Status: Accepted
- Date: 2026-04-15
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / Note / Payment / Refund / Cashier UI / Audit

## Context

Sistem saat ini sudah memiliki state operasional note `open` / `closed`, tetapi perilaku kasir masih bercampur dengan label `unpaid` / `partial` / `paid` dan guard edit workspace masih memblokir semua note yang sudah memiliki payment allocation, walaupun note tersebut belum settle penuh.

Kebutuhan bisnis yang dikunci untuk workflow kasir:

- status utama note disederhanakan menjadi `open` dan `close`
- `open` berarti note belum settle penuh
- `open` tetap boleh diedit walau sudah ada pembayaran sebagian
- `close` berarti note sudah settle penuh
- `close` tidak boleh diedit lewat workspace
- pembalikan untuk note `close` dilakukan lewat refund flow resmi
- ledger pembayaran, refund, dan allocation historis harus tetap immutable

Repo saat ini sudah memiliki:

- auto-close saat full payment
- refund sebagai fakta finansial baru yang terpisah
- flow correction terkontrol untuk note lunas
- audit untuk mutasi sensitif

Yang belum sesuai adalah contract editability dan contract status utama untuk flow kasir.

## Decision

Sistem menetapkan:

- status operasional utama note untuk flow kasir adalah `open` dan `close`
- note `open` berarti net paid resmi masih kurang dari total note terbaru
- note `close` berarti net paid resmi sama dengan atau lebih besar dari total note terbaru
- note `open` boleh diedit lewat workspace walau sudah ada partial payment
- note `close` tidak boleh diedit lewat workspace
- histori payment, refund, dan allocation tidak boleh diubah mundur
- pembacaan settlement note versi terbaru untuk UI operasional harus dilakukan melalui projection / read model, bukan dengan me-reallocate histori ledger
- pembalikan untuk note `close` dilakukan melalui refund flow resmi yang diaudit

## Decision Details

### 1. Boundary of `open` and `close`

Untuk flow kasir:

- `open` bila `net_paid < total_note_terbaru`
- `close` bila `net_paid >= total_note_terbaru`

`net_paid` dihitung dari:

- total allocation sah pada note
- dikurangi total refund sah pada note

Bukan dari toggle manual UI.

### 2. Editable partial payment

Selama note masih `open`, workspace edit tetap diizinkan walau partial payment sudah ada.

Artinya rule lama:

- “sudah ada pembayaran maka workspace edit dilarang”

tidak lagi berlaku untuk flow kasir yang baru.

### 3. Closed note behavior

Setelah note `close`:

- workspace edit dilarang
- flow biasa untuk mutasi note tidak dipakai
- pembalikan resmi dilakukan lewat refund flow
- histori finansial tetap append-only

### 4. Immutable financial ledger

Perubahan note setelah partial payment tidak boleh mengubah mundur:

- customer payment
- payment allocation
- customer refund
- refund component allocation

Kebutuhan membaca settlement note versi terbaru untuk UI operasional harus diselesaikan lewat projection yang terpisah dari histori ledger.

## Alternatives Considered

### Alternative A — Tetap blok edit bila ada pembayaran apa pun
Ditolak.

Alasan penolakan:

- tidak sesuai kebutuhan bisnis baru
- memaksa kasir membuat workflow operasional yang lebih kaku dari yang dibutuhkan
- bertentangan dengan definisi `open` yang sudah dikunci

### Alternative B — Reallocate ulang histori payment lama ke struktur note terbaru
Ditolak untuk ledger utama.

Alasan penolakan:

- blast radius tinggi
- berisiko merusak audit trail
- bertentangan dengan prinsip append-only untuk fakta finansial

### Alternative C — Pertahankan dua status utama sekaligus di UI kasir
Ditolak.

Alasan penolakan:

- membingungkan operasional
- membuat status utama note tidak tegas
- memperbesar risiko keputusan UI yang tidak konsisten

## Consequences

### Positive

- status kasir menjadi lebih sederhana
- partial payment tetap fleksibel
- edit note open tetap mungkin
- close dan refund punya boundary lebih jelas
- histori finansial tetap aman dan audit-friendly

### Negative

- dibutuhkan projection settlement operasional untuk note versi terbaru
- developer harus membedakan ledger historis vs projection UI operasional
- downstream UI dan dokumen yang masih memakai contract lama harus dirapikan

## Invariants

- note `close` tidak boleh diedit lewat workspace
- note `open` boleh diedit walau partial payment sudah ada
- payment / refund / allocation historis tetap immutable
- status `open` / `close` harus ditentukan dari net paid vs total note terbaru
- pembalikan note `close` dilakukan lewat refund flow resmi

## Implementation Notes

- Paket pertama cukup mengubah guard editability ke rule status operasional
- evaluator status operasional harus dijadikan sumber tunggal minimal untuk guard, lalu dipakai ulang di paket berikutnya
- UI detail kasir dan refund flow diselesaikan di paket implementasi berikutnya
- projection settlement operasional diselesaikan setelah contract guard dan status dikunci

## Related Decisions

- ADR-0005 — Paid Note Correction Requires Audit
- ADR-0008 — Audit-First Sensitive Mutations
- ADR-0009 — Reporting as Read Model
- ADR-0011 — Money Stored as Integer Rupiah
