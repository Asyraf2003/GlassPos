# ADR-0007 — Admin Transaction Entry Behind Capability Policy

- Status: Accepted
- Date: 2026-03-09
- Deciders: Project Owner, Architecture Decision
- Scope: Core Domain / IdentityAccess / Authorization / Note / Audit

## Context

Sistem saat ini hanya memiliki dua role aktif:

- `admin`
- `kasir`

Kebutuhan bisnis yang sudah dikunci:

- kasir memang dapat melakukan input transaksi operasional
- admin **tidak otomatis** selalu boleh input transaksi
- harus ada policy/capability yang mengaktifkan izin input transaksi untuk admin
- ketika admin menggunakan capability tersebut, tindakan itu harus tercatat

Alasan bisnis di balik keputusan ini:

- admin memiliki cakupan kewenangan yang lebih luas, tetapi tidak berarti semua kewenangan operasional harian aktif secara otomatis
- perlu ada pemisahan antara role umum dan capability operasional sensitif
- transaksi adalah area sensitif karena memengaruhi uang, stok, audit, dan laporan
- sistem harus mendukung pengawasan saat admin masuk ke area kerja transaksi yang normalnya dipegang kasir

Domain ini juga sudah mengunci bahwa:

- perubahan sensitif harus diaudit
- role/middleware adalah adapter/policy, bukan logika bisnis liar di UI
- arsitektur harus future-ready untuk multi-role dan capability yang lebih kaya tanpa membongkar struktur inti

## Decision

Sistem menetapkan:

- **role `admin` tidak otomatis memiliki capability input transaksi**
- **capability input transaksi untuk admin harus diaktifkan melalui policy resmi**
- **pemeriksaan capability dilakukan di core authorization/application policy**
- **setiap penggunaan capability transaksi oleh admin harus dapat diaudit**
- **role dan capability dipisahkan secara konsep, walau saat ini role aktif baru `admin` dan `kasir`**

## Decision Details

### 1. Role vs capability

Role dan capability tidak disamakan.

Makna operasional:

- `kasir` adalah role yang secara normal dipakai untuk input transaksi
- `admin` adalah role dengan otoritas umum lebih luas
- namun otoritas umum admin tidak berarti capability input transaksi langsung otomatis aktif

Dengan demikian, keputusan akses transaksi tidak hanya bertanya:

- "apakah dia admin?"

tetapi lebih tepat:

- "apakah actor ini memiliki capability input transaksi yang aktif?"

### 2. Default behavior

Perilaku default resmi:

- `kasir` dapat mengakses use case input transaksi sesuai kebijakan operasional normal
- `admin` tanpa capability aktif **harus ditolak** bila mencoba input transaksi
- `admin` dengan capability aktif **boleh** input transaksi
- penggunaan capability admin untuk transaksi harus tercatat di audit

### 3. Audit requirements

Saat capability transaksi admin:

- diaktifkan
- dinonaktifkan
- digunakan untuk membuat/mengubah transaksi yang relevan

sistem harus menyimpan jejak audit yang cukup.

Minimal audit perlu dapat menjawab:

- siapa admin yang menggunakan capability tersebut
- kapan dilakukan
- transaksi mana yang terpengaruh
- policy/capability status apa yang berlaku saat tindakan dilakukan

### 4. Future-readiness

Walau saat ini sistem hanya memakai dua role, model harus membuka arah ke depan untuk:

- capability lain
- role tambahan
- kebijakan kontekstual
- trust score atau policy evolutif lain

Namun extension ini tidak boleh membebani kebutuhan aktif saat ini.

## Alternatives Considered

### Alternative A — Admin selalu boleh input transaksi
Ditolak.

Alasan penolakan:

- bertentangan dengan kebutuhan bisnis yang sudah dikunci
- mengaburkan batas antara kewenangan umum dan kewenangan operasional sensitif
- melemahkan pengawasan audit

### Alternative B — Hanya kasir yang boleh input transaksi selamanya
Ditolak.

Alasan penolakan:

- bisnis tetap membutuhkan kemungkinan admin melakukan input transaksi dalam kondisi tertentu
- terlalu kaku
- tidak sesuai kebutuhan operasional yang sudah dijelaskan

### Alternative C — Cek akses hanya berdasarkan role statis di middleware/UI
Ditolak.

Alasan penolakan:

- tidak cukup kuat untuk memodelkan capability yang bisa aktif/tidak aktif
- terlalu mudah bercampur dengan detail framework
- tidak portable untuk hexagonal architecture

## Consequences

### Positive

- akses transaksi menjadi lebih presisi
- audit terhadap tindakan admin lebih jelas
- fondasi authorization lebih sehat
- membuka jalan ke capability-based policy tanpa over-engineering
- sesuai kebutuhan bisnis saat ini

### Negative

- authorization model sedikit lebih kaya dibanding role-check sederhana
- implementasi adapter dan use case harus konsisten memeriksa capability
- testing authorization perlu mencakup kombinasi role dan policy state

## Invariants

- role `admin` tidak otomatis berarti capability input transaksi aktif
- input transaksi oleh admin tanpa capability aktif adalah invalid
- perubahan capability transaksi admin harus dapat diaudit
- penggunaan capability transaksi admin harus dapat ditelusuri
- role dan capability tidak boleh direduksi menjadi satu konsep tunggal secara implisit

## Implementation Notes

- abstraction yang direkomendasikan:
  - `TransactionEntryPolicy`
  - `ActorCapabilityChecker`
  - `AuthorizationService`
- error domain yang direkomendasikan:
  - `AUTH_FORBIDDEN`
  - `ADMIN_TRANSACTION_CAPABILITY_DISABLED`
- adapter middleware boleh membantu pre-check, tetapi keputusan final tetap harus dapat dijaga konsisten di application/core authorization policy
- policy ini harus dapat dibawa lintas framework/language tanpa mengubah makna domain

## Related Decisions

- ADR-001 — One Note Multi-Item Model
- ADR-005 — Paid Note Correction Requires Audit
- ADR-008 — Audit-First Sensitive Mutations
- ADR-010 — Telegram/WA Integration as Adapter
