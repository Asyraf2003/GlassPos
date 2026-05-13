# HANDOFF PHASE NOTE HYBRID + PSEUDO-VERSIONING

## 1. Konteks & aturan kerja yang wajib dipatuhi

Project: `Asyraf2003/bengkelnativejs`

### Aturan kerja dari user yang wajib diikuti
1. Audit dulu
2. Identifikasi gap
3. Kalau ada gap / data belum cukup, berhenti dan tanya user dengan opsi + plus/minus
4. Baru implementation plan
5. Baru patch terminal dari root dalam format `mkdir`, `cat`, dll
6. Jangan push / jangan edit diam-diam
7. Zero assumption:
   - jangan asumsi method/class/contract ada
   - jangan asumsi test lama otomatis obsolete
   - jangan eksekusi kalau audit belum jelas

### Introspeksi penting dari chat ini
Assistant sempat melanggar zero-assumption dan sempat terlalu cepat eksekusi beberapa arah. Itu jangan diulang di chat berikutnya.

---

## 2. Keputusan scope phase yang sudah dikunci

### In scope phase ini
- Hybrid payment/refund
- Pseudo-versioning read model

### Out of scope phase ini
- True note revision model
- True note revision persistence
- edit note berbasis revision flow penuh

Formulasi phase yang sudah dikunci:
- detail note dibangun ke model hybrid penuh untuk payment/refund
- note versioning diperlakukan sebagai target arsitektur final
- implementasi phase ini hanya menyiapkan pseudo-versioning read model dari state existing yang tersedia
- true note revision persistence/model ditunda ke slice lanjutan eksplisit

---

## 3. Fakta implementasi yang sudah selesai

### A. Hybrid detail note sudah hidup
Halaman cashier note detail sekarang memakai model hybrid:
- line domain tetap tampil sebagai layer baca
- billing projection dipakai untuk payment
- refund tetap selection-first berbasis line domain

### B. Pseudo-versioning read model sudah hidup
Pseudo-versioning dibangun dari:
- current state
- baseline yang tersedia dari history existing
- timeline mutasi existing

Builder utama:
- `app/Application/Note/Services/NoteBillingProjectionBuilder.php`
- `app/Application/Note/Services/NotePseudoVersioningBuilder.php`

### C. Workspace/edit flow belum diubah menjadi true revision flow
Ini sengaja belum disentuh.
Sidebar workspace sekarang menjelaskan:
- phase ini belum membuka true note revision flow
- edit isi nota masih lewat workspace existing
- pseudo-versioning hanya read model

### D. Refund options diputus hanya untuk hybrid/component flow
Keputusan final phase ini:
- legacy refund compatibility tidak dipertahankan
- `NoteRefundPaymentOptionsBuilder` hanya membaca jalur component allocation
- legacy refund launcher expectation dipensiunkan sebagai obsolete contract phase lama

### E. Regression gate yang terbukti
User sudah menjalankan dan mengonfirmasi:
- `make verify` pass
- `make test` pass
- final state terakhir: `709 passed (3679 assertions)`

---

## 4. File inti yang berubah / ditambahkan di phase ini

### Service / builder / mapper
- `app/Application/Note/Services/NoteBillingProjectionBuilder.php`
- `app/Application/Note/Services/NoteBillingProjectionRowMapper.php`
- `app/Application/Note/Services/NoteBillingProjectionSupport.php`
- `app/Application/Note/Services/NotePseudoVersioningBuilder.php`
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Application/Note/Services/NoteDetailNotePayloadBuilder.php`
- `app/Application/Note/Services/NoteDetailRowMapper.php`
- `app/Application/Note/Services/NoteDetailRowPresentationSupport.php`
- `app/Application/Note/Services/NoteRefundPaymentOptionsBuilder.php`

### View / partial
- `resources/views/cashier/notes/show.blade.php`
- `resources/views/cashier/notes/partials/note-overview.blade.php`
- `resources/views/cashier/notes/partials/note-pseudo-versioning.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/billing-table.blade.php`
- `resources/views/cashier/notes/partials/payment-actions.blade.php`
- `resources/views/cashier/notes/partials/payment-modal.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `resources/views/cashier/notes/partials/add-rows-form.blade.php`

### JS
- `public/assets/static/js/pages/cashier-note-payment.js`
- `public/assets/static/js/pages/cashier-note-refund.js`

### Proof/tests yang sudah ditambah/diubah
- `tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php`
- `tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php`
- `tests/Feature/Note/LegacyAllocatedNoteDetailFeatureTest.php`
- `tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php`
- `tests/Unit/Application/Note/Services/NotePseudoVersioningBuilderTest.php`
- `tests/Unit/Application/Note/Services/NoteRefundPaymentOptionsBuilderTest.php`
- `tests/Unit/Application/Note/Services/NoteBillingProjectionRowMapperTest.php`
- `tests/Unit/Application/Note/Services/NoteDetailRowMapperTest.php`

---

## 5. Yang done vs belum done

### DONE untuk phase ini
1. Hybrid payment/refund
2. Billing projection read-side
3. Pseudo-versioning read model
4. Legacy refund compatibility diputus secara eksplisit
5. View + JS hybrid basic behavior
6. Regression gate hijau (`make verify`, `make test`)

### BELUM DONE tapi memang out of scope by decision
1. True note revision model
2. True note revision persistence
3. Edit note berbasis revision flow penuh
4. Version timeline ala product/employee yang benar-benar entity-versioned, bukan pseudo read model

---

## 6. Hal penting yang harus dipahami chat berikutnya

### Jangan salah baca progress
- Phase ini selesai
- ADR final keseluruhan belum selesai

Jangan pernah klaim:
- “note versioning sudah selesai penuh”
- “edit note sudah berbasis revision system”
- “roadmap ADR final sudah 100%”

Yang benar:
- phase hybrid + pseudo-versioning sudah selesai
- next slice adalah true note revision

---

## 7. Risiko / boundary yang harus dijaga
1. Jangan hidupkan lagi compatibility legacy refund secara diam-diam
2. Jangan ubah pseudo-versioning menjadi seolah-olah true versioning
3. Jangan arahkan tombol edit ke narasi revision penuh kalau persistence/model-nya belum ada
4. Semua perubahan berikutnya tetap wajib:
   - audit dulu
   - gap dulu
   - kalau kurang data, berhenti dan tanya opsi

---

## 8. Tugas chat berikutnya seharusnya apa

### Next slice yang logis
True Note Revision Slice

Targetnya:
1. audit model existing untuk note revision persistence
2. cari apakah sudah ada tabel/model/event yang bisa direuse
3. kalau tidak ada, berhenti dan ajukan opsi desain
4. baru plan
5. baru patch terminal dari root

### Scope calon next slice
- true note revision model
- true note revision persistence
- edit note tidak lagi ke workspace existing
- detail note memakai revision-aware flow yang nyata, bukan pseudo read model

### Yang jangan dikerjakan ulang di next chat
- jangan bongkar lagi hybrid payment/refund yang sudah hijau
- jangan balikkan keputusan legacy refund compatibility kecuali user eksplisit minta
- jangan sentuh request/route payment-refund existing tanpa audit baru

---

## 9. Release gate state terakhir
Status terakhir yang sudah dibuktikan user:
- `make verify` ✅
- `make test` ✅
- final output test: `709 passed (3679 assertions)`

---

## 10. Instruksi operasional untuk assistant di chat berikutnya
1. Baca handoff ini dulu
2. Pegang zero assumption
3. Jangan eksekusi kalau audit belum jelas
4. Kalau perlu implementasi:
   - kirim command terminal dari root
   - jangan edit repo diam-diam
5. Pisahkan tegas:
   - done
   - deferred by decision
   - next slice
