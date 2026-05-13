# ADR-0010 — Telegram/WA Integration as Adapter

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Adapters / Notification / Core Domain / Application / Reporting / IdentityAccess

## Context

Sistem ini direncanakan akan memiliki integrasi bot untuk kebutuhan operasional, antara lain:

- notifikasi
- informasi transaksi
- laporan
- bukti transfer atau bukti bayar supplier
- kemungkinan interaksi dari banyak user

Kebutuhan bisnis yang sudah dikunci:

- integrasi bot adalah kebutuhan **belakangan**, bukan fondasi awal core
- sistem inti harus tetap sehat walau belum ada bot
- arsitektur harus memudahkan perpindahan kanal dari Telegram ke WhatsApp atau kanal lain tanpa membongkar core
- domain utama tetap berpusat pada Note, Inventory, Payment, Supplier, Expense, Employee Finance, Reporting, dan Audit
- integrasi tidak boleh merusak hexagonal boundary

Risiko bila bot diikat langsung ke core atau framework:

- domain akan tahu detail Telegram/WA
- perpindahan channel menjadi mahal
- testing domain menjadi bergantung pada infrastruktur pesan
- notifikasi dan command bot dapat bocor menjadi logika bisnis inti

## Decision

Sistem menetapkan:

- **Telegram, WhatsApp, dan kanal bot/pesan lain diposisikan sebagai adapter**
- **core hanya berbicara melalui port/use case/domain event yang netral terhadap channel**
- **notifikasi keluar dipicu melalui outbound port**
- **interaksi masuk dari bot diperlakukan sebagai inbound adapter yang memanggil use case resmi**
- **pergantian Telegram ke WA atau channel lain tidak boleh mengubah domain model inti**

## Decision Details

### 1. Outbound adapter

Untuk kebutuhan seperti:

- notifikasi transaksi
- notifikasi jatuh tempo supplier
- laporan harian/bulanan
- pengiriman bukti atau ringkasan

Telegram/WA diperlakukan sebagai outbound adapter yang mengimplementasikan port semacam:

- `Notifier`
- `MessagePublisher`
- `ReportDeliveryPort`

Core tidak boleh tahu:

- API Telegram
- format chat Telegram
- webhook Telegram
- token bot
- struktur provider message tertentu

### 2. Inbound adapter

Bila di masa depan bot digunakan untuk menerima perintah, misalnya:

- cek laporan
- cek status transaksi
- kirim command tertentu

maka Telegram/WA diperlakukan sebagai inbound adapter yang:

- menerima pesan dari channel
- memetakan pesan ke command/use case resmi
- meneruskan actor/context yang relevan
- menerima hasil lalu merender balik ke format channel

Core tidak boleh tahu detail command syntax channel.

### 3. Security and authorization boundary

Karena bot/channel adalah adapter, maka:

- otorisasi tetap diperiksa oleh application/core policy resmi
- bot tidak boleh menjadi jalur bypass
- actor yang menggunakan bot tetap harus dipetakan ke identity/capability resmi
- audit tetap berlaku untuk aksi sensitif yang dipicu dari bot

### 4. Delivery model

Integrasi adapter boleh menggunakan:

- synchronous call
- queued delivery
- event-driven delivery
- hybrid

Namun keputusan delivery technical tidak mengubah posisi arsitektural bot sebagai adapter.

### 5. Why channel-neutral matters

Kebutuhan bisnis sudah menyebut kemungkinan pindah ke bot WA atau kanal lain.

Agar perpindahan itu murah, core harus berbicara dalam istilah:

- notification intent
- report payload
- command/use case input
- actor/capability context

bukan dalam istilah Telegram atau WA.

## Alternatives Considered

### Alternative A — Menanam Telegram logic langsung di controller/service domain
Ditolak.

Alasan penolakan:

- melanggar separation of concerns
- mempermahal perpindahan channel
- menyulitkan testing domain
- mencampur concern transport dengan business logic

### Alternative B — Menganggap Telegram sebagai bagian wajib core sejak awal
Ditolak.

Alasan penolakan:

- tidak sesuai prioritas proyek
- membebani fondasi awal
- membuat core tampak lebih rumit dari yang dibutuhkan saat ini

### Alternative C — Membiarkan bot mengakses database/domain langsung tanpa use case resmi
Ditolak.

Alasan penolakan:

- berbahaya untuk authorization
- merusak boundary hexagonal
- membuka jalur bypass audit dan policy bisnis

## Consequences

### Positive

- core tetap bersih dan channel-neutral
- migrasi Telegram ke WA atau kanal lain menjadi jauh lebih murah
- notifikasi dan command bisa berkembang tanpa membongkar domain
- testing domain tetap fokus pada aturan bisnis
- authorization dan audit tetap konsisten lintas channel

### Negative

- perlu adapter layer tambahan
- mapping message format ke use case butuh disiplin
- integrasi inbound bot akan memerlukan desain command contract yang jelas

## Invariants

- bot/channel integration bukan bagian dari core domain model
- bot tidak boleh bypass use case resmi
- authorization tetap diperiksa oleh policy resmi
- audit tetap berlaku untuk aksi sensitif yang berasal dari bot
- outbound delivery harus dapat diganti tanpa mengubah domain core

## Implementation Notes

- outbound port yang direkomendasikan:
  - `Notifier`
  - `ReportDeliveryPort`
  - `SupplierReminderPort`
- inbound adapter yang direkomendasikan:
  - `TelegramCommandAdapter`
  - `WhatsAppCommandAdapter`
- event yang cocok untuk notifikasi antara lain:
  - note paid
  - supplier due soon
  - correction happened
  - daily summary ready
  - monthly report ready
- format pesan, template, retry, dan provider-specific concerns harus tinggal di adapter/infrastructure layer
- bila di masa depan ada approval flow melalui bot, itu perlu keputusan tambahan agar tidak mencampur capability approval dengan sekadar transport

## Related Decisions

- ADR-007 — Admin Transaction Entry Behind Capability Policy
- ADR-008 — Audit-First Sensitive Mutations
- ADR-009 — Reporting as Read Model
