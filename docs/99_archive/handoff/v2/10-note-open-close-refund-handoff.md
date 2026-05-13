# Handoff V2 — Nota Kasir Open/Close, Editable Partial Payment, Refund on Close

- Status: In Progress
- Date: 2026-04-15
- Scope: Kasir / Nota / Payment / Refund / Detail UI
- Progress: 95%

## Ringkasan

Pekerjaan ini mengubah arah operasional nota kasir menjadi lebih tegas:

- status utama kasir dibaca sebagai `open` / `close`
- `open` tetap boleh diedit walau sudah ada pembayaran sebagian
- `close` tidak boleh diedit dari workspace
- `close` menyediakan refund flow resmi
- ledger payment/refund historis tetap immutable
- halaman detail kasir diarahkan agar mendekati gaya workspace create/edit

Perubahan ini tidak hanya menyentuh UI, tetapi juga contract domain-operasional, access policy kasir, guard editability, resolver status, projection settlement operasional, refund HTTP flow, dan handoff/ADR.

## Keputusan yang Terkunci

### 1. Status utama kasir

Status utama nota untuk flow kasir adalah:

- `open`
- `close`

Makna operasional:

- `open` bila `net_paid < total_note_terbaru`
- `close` bila `net_paid >= total_note_terbaru`

### 2. Editability

- nota `open` boleh diedit lewat workspace
- nota `open` tetap boleh diedit walau sudah ada pembayaran sebagian
- nota `close` tidak boleh diedit lewat workspace

### 3. Refund

- refund resmi hanya untuk nota `close`
- refund dicatat lewat flow HTTP resmi
- refund tidak mengubah mundur payment/alokasi historis

### 4. Ledger historis

Tetap immutable:

- customer payment
- payment allocation
- customer refund
- refund component allocation

### 5. Settlement operasional

UI detail tidak lagi dibaca langsung dari histori component allocation lama per baris, tetapi dari projection operasional terhadap struktur note terbaru.

## Dokumen Keputusan

ADR baru yang dibuat:

- `docs/adr/0015-note-operational-status-open-close-editable-partial-payment.md`

Isi ADR mengunci:

- open/close sebagai status operasional utama
- editable partial payment
- refund on close
- immutable ledger
- projection operasional untuk UI

## Paket yang Sudah Diselesaikan

### Paket 1 — ADR + guard editability

Selesai.

Hasil:

- ADR baru tercipta
- guard editability tidak lagi memakai rule lama `allocated > 0 => tidak boleh edit`
- guard sekarang mengikuti status operasional note

### Paket 2 — Sumber tunggal status operasional

Selesai.

Hasil:

- evaluator status operasional tersedia
- resolver status operasional tersedia
- guard dan detail builder membaca sumber status yang sama

### Paket 3 — Projection settlement operasional

Selesai.

Hasil:

- projection settlement per baris dibentuk dari total allocation dan total refund level-note
- projection disusun deterministik berdasarkan `line_no`
- histori ledger tidak diubah

### Paket 4 — Refund HTTP resmi untuk kasir

Selesai.

Hasil:

- route refund kasir tersedia
- request refund tersedia
- controller refund tersedia
- controller terhubung ke `RecordCustomerRefundHandler`

### Paket 5A — Access policy kasir untuk note close

Selesai.

Hasil:

- kasir boleh membuka detail note `close` dalam window tanggal yang diizinkan
- kasir tetap tidak boleh workspace edit note `close`
- policy dipisah menjadi view vs mutate-open

### Paket 5B — Detail UI dan refund form

Selesai untuk jalur utama.

Hasil:

- detail note `close` menampilkan refund form resmi
- detail note `open` menampilkan payment panel
- overview detail menggunakan mode `Open/Close`
- wording history dipindah dari nuansa `Correction` menjadi `Mutasi Nota`

## File yang Diubah / Ditambahkan

### ADR / Handoff

- `docs/adr/0015-note-operational-status-open-close-editable-partial-payment.md`

### Application / Policy / Service

- `app/Application/Note/Policies/CashierNoteAccessGuard.php`
- `app/Application/Note/Services/EditableWorkspaceNoteGuard.php`
- `app/Application/Note/Services/NoteOperationalStatusEvaluator.php`
- `app/Application/Note/Services/NoteOperationalStatusResolver.php`
- `app/Application/Note/Services/NoteOperationalRowSettlementProjector.php`
- `app/Application/Note/Services/NoteRefundPaymentOptionsBuilder.php`
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`

### HTTP Request / Controller / Route / Middleware

- `app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php`
- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/NoteDetailPageController.php`
- `app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php`
- `routes/web/note.php`

### View

- `resources/views/cashier/notes/show.blade.php`
- `resources/views/cashier/notes/partials/note-overview.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/payment-form.blade.php`
- `resources/views/cashier/notes/partials/refund-form.blade.php`
- `resources/views/cashier/notes/partials/add-rows-form.blade.php`
- `resources/views/cashier/notes/partials/correction-history.blade.php`

### Test

- `tests/Unit/Application/Note/Services/NoteOperationalStatusEvaluatorTest.php`
- `tests/Unit/Application/Note/Services/NoteOperationalStatusResolverTest.php`
- `tests/Unit/Application/Note/Services/NoteOperationalRowSettlementProjectorTest.php`
- `tests/Unit/Application/Note/Services/NoteRefundPaymentOptionsBuilderTest.php`
- `tests/Feature/Note/EditableWorkspaceNoteGuardFeatureTest.php`
- `tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
- `tests/Feature/Note/CashierProtectedNoteRoutesAccessGuardFeatureTest.php`
- `tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php`
- `tests/Feature/Note/CashierNoteMutationHistoryViewFeatureTest.php`

## Perubahan Perilaku yang Sekarang Sudah Berlaku

### 1. Workspace edit

- note `open` tanpa payment boleh edit
- note `open` dengan partial payment tetap boleh edit
- note `close` tidak boleh edit

### 2. Detail note

- kasir bisa buka detail note `open`
- kasir bisa buka detail note `close` dalam date window yang diizinkan
- note `open` menampilkan:
  - mode open
  - edit action
  - payment panel
- note `close` menampilkan:
  - mode close
  - refund panel
  - payment panel tersembunyi

### 3. Refund

- refund HTTP resmi sudah tersedia untuk kasir
- refund hanya legal untuk note `close`
- validasi reason tersedia
- submit refund tersambung ke handler domain yang sudah ada

### 4. History

- wording UI di detail sekarang menampilkan `Riwayat Mutasi Nota`
- wording lama `Riwayat Correction` di partial detail sudah dibersihkan

## Proof Verifikasi

Verifikasi yang sudah benar-benar dijalankan dan pass selama pekerjaan ini:

### Unit

- `Tests\Unit\Application\Note\Services\NoteOperationalStatusEvaluatorTest`
- `Tests\Unit\Application\Note\Services\NoteOperationalStatusResolverTest`
- `Tests\Unit\Application\Note\Services\NoteOperationalRowSettlementProjectorTest`
- `Tests\Unit\Application\Note\Services\NoteRefundPaymentOptionsBuilderTest`

### Feature

- `Tests\Feature\Note\EditableWorkspaceNoteGuardFeatureTest`
- `Tests\Feature\Note\RecordClosedNoteRefundControllerFeatureTest`
- `Tests\Feature\Note\CashierProtectedNoteRoutesAccessGuardFeatureTest`
- `Tests\Feature\Note\CashierClosedNoteRefundViewFeatureTest`
- `Tests\Feature\Note\CashierNoteMutationHistoryViewFeatureTest`

## Kontrak UI Saat Ini

### Edit vs Create

Sudah seragam.

Alasan:
- edit dan create memang berbagi workspace yang sama
- gaya dasar keduanya sudah satu family dari awal pekerjaan ini

### Detail vs Create

Belum 100% identik, tetapi sudah cukup dekat secara pola:

- mode badge
- struktur card
- CTA utama
- panel kanan
- open/close separation
- refund form pada close mode

Yang masih belum sepenuhnya identik:

- detail tetap memakai halaman ringkasan + tabel + panel
- create/edit masih berupa workspace transaksi penuh
- naming internal lama di beberapa layer kode masih tersisa

## Risiko / Catatan Penting

### 1. Note state domain vs operational resolver

Saat ini:

- entity note masih punya `note_state`
- UI kasir utama membaca `operational_status` dari resolver

Artinya ada dua lapis state:

- state domain persisted
- state operasional hasil hitung terbaru

Ini sengaja dipertahankan karena kebutuhan transisi dan agar ledger historis tetap aman.

### 2. Refund dapat membuat note kembali terbaca open

Resolver status operasional akan membaca kembali posisi note setelah refund berdasarkan:

- total terbaru
- allocation
- refund

Jadi secara operasional note dapat kembali terbaca `open` bila net paid turun di bawah total.

### 3. Naming internal lama masih ada

Beberapa nama internal masih memakai kata:

- correction
- paid status
- note state lama

UI utama sudah dibersihkan sebagian, tetapi layer internal belum semuanya direname.

## Pending / Belum Dikerjakan

### 1. Cleanup internal naming lebih lanjut

Masih bisa dirapikan:

- `NoteCorrectionHistoryBuilder`
- partial/variable bernuansa correction lama
- naming yang masih campur antara `paid` vs `close`

### 2. Cleanup komponen lama

Perlu audit final apakah masih ada partial lama yang:

- tidak lagi dipakai
- masih membawa narasi lama
- bisa dipensiunkan

### 3. Regression suite yang lebih luas

Belum dijalankan di handoff ini:

- seluruh suite note/payment/refund
- test end-to-end workflow kasir lebih besar
- audit terhadap area admin terkait reopen/correction

### 4. Handoff final merge-ready

Dokumen ini sudah cukup sebagai handoff kerja V2, tetapi masih bisa dilengkapi lagi bila diperlukan untuk release note / merge summary.

## Langkah Lanjutan yang Paling Aman

Urutan paling aman setelah handoff ini:

1. audit file/partial sisa yang masih membawa narasi lama
2. rename internal naming yang masih menyesatkan
3. jalankan regression suite note/payment/refund yang lebih lebar
4. siapkan merge summary / final handoff bila branch ini siap digabung

## Ringkasan Keadaan Saat Diserahkan

- target inti sudah tercapai
- backend utama stabil
- refund flow resmi untuk close note sudah hidup
- access policy kasir sudah sesuai target baru
- UI detail sudah cukup dekat dengan gaya create/edit
- remaining work tinggal cleanup, naming, dan regression yang lebih lebar

## Snapshot Singkat

### Sudah jadi
- open/close main status
- edit partial payment
- refund on close
- close note accessible for cashier detail
- refund form visible on close detail
- payment form visible on open detail
- mutation history wording

### Belum final
- cleanup internal naming
- cleanup komponen lama
- regression suite yang lebih luas
- final merge packaging
