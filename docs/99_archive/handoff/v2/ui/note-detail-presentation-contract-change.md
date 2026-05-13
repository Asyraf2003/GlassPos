# Note Detail Presentation Contract Change

## Metadata
- Tanggal: 2026-04-21
- Nama slice / topik: Cashier Note Detail Presentation Contract Change
- Workflow step: Step 1C
- Status: LOCKED
- Progres: 100%

## Target halaman kerja
Menandai secara eksplisit bahwa `cashier.notes.show` akan mengalami perubahan contract presentasi dari panel lama ke launcher + modal.

## Referensi yang dipakai `[REF]`
- Public contract rule: `docs/AI_RULES/40_ARCHITECTURE/41_PUBLIC_CONTRACTS.md`
- Detail page builder baseline:
  - `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- Detail page controller baseline:
  - `app/Adapters/In/Http/Controllers/Cashier/Note/NoteDetailPageController.php`
- Detail page view baseline:
  - `resources/views/cashier/notes/show.blade.php`
  - `resources/views/cashier/notes/partials/note-rows-table.blade.php`
  - `resources/views/cashier/notes/partials/payment-form.blade.php`
  - `resources/views/cashier/notes/partials/refund-form.blade.php`
- JS consumer baseline:
  - `public/assets/static/js/pages/cashier-note-payment.js`
  - `public/assets/static/js/pages/cashier-note-refund.js`
- Impacted tests:
  - `tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php`
  - `tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php`
  - `tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`

## Fakta terkunci `[FACT]`
- Halaman detail note saat ini masih panel-centric untuk payment dan refund.
- Feature tests saat ini mengunci copy dan visibility panel lama.
- JS saat ini mengunci DOM panel lama sebagai interaction contract.
- Backend payment/refund selected-row tidak menjadi target perubahan pada fase ini.
- Route dan request contract payment/refund tidak perlu diubah pada fase ini.

## Scope yang dipakai
### `[SCOPE-IN]`
- Menandai perubahan contract presentasi secara eksplisit.
- Mendefinisikan old contract vs target contract.
- Mendefinisikan impacted tests dan preserved contracts.
- Mengunci boundary fase migrasi UI.

### `[SCOPE-OUT]`
- Mengubah route contract.
- Mengubah request contract payment/refund.
- Mengubah selected rows menjadi selected components.
- Mengubah finance engine / allocation engine.
- Mengubah reporting.

## Keputusan yang dikunci `[DECISION]`
- Fase ini adalah **Presentation Contract Change** untuk `cashier.notes.show`.
- Perubahan dari panel lama ke launcher + modal **bukan** incidental refactor.
- Contract backend payment/refund existing dipertahankan pada fase ini.
- Selected rows request contract dipertahankan pada fase ini.
- Migrasi UI akan dilakukan serempak pada fase implementasi, bukan setengah-setengah.

## Old Contract `[OLD-CONTRACT]`
### Detail page lama
- Payment dirender di panel kanan melalui `payment-form`.
- Refund dirender di panel kanan melalui `refund-form`.
- Tabel line memiliki checkbox tetap untuk payment/refund.
- Tombol aksi row mengarahkan user ke panel lama.
- JS payment/refund membaca DOM panel lama.

### Copy lama yang dianggap terkunci oleh test
- `Pembayaran Line Open Terpilih`
- `Panel Bayar`
- `Refund Line Close Terpilih`
- `Panel Refund`

## New Contract `[NEW-CONTRACT]`
### Detail page baru
- Detail note menjadi halaman baca + launcher.
- Payment dan refund dibuka melalui modal.
- Checklist line hanya muncul di dalam modal aksi.
- Tabel line tidak lagi menjadi tempat checkbox tetap.
- Tombol aksi row menjadi launcher modal.
- JS payment/refund menjadi modal-centric.

## Change Gate `[CHANGE-GATE]`
### Alasan perubahan
- UI lama kacau walaupun backend payment/refund relatif stabil.
- Interaksi panel + checkbox tabel menciptakan UX ganda dan membingungkan.
- Target UX yang diinginkan adalah aksi dulu, seleksi line di dalam modal.

### Dampak perubahan
- Feature tests view yang mengunci panel lama harus dimigrasi.
- JS lama panel-centric harus dipensiunkan atau diganti.
- View contract `cashier.notes.show` akan berubah secara eksplisit.

### Alternatif yang dipertimbangkan
- Compat-preserving transition dipertimbangkan, tetapi tidak dipilih.
- Dipilih explicit contract change karena masalah utama ada pada UI lama dan target akhir ingin bersih tanpa pola campuran.

### Bukti perubahan memang diperlukan
- Panel lama dikunci oleh test.
- DOM lama dikunci oleh JS.
- UX lama masih panel-centric dan checkbox-centric.
- Keputusan user: lanjut dengan Opsi B, namun harus ditandai dulu sistemnya.

## Preserved Contracts `[PRESERVED]`
- Route payment existing tetap.
- Route refund existing tetap.
- Request contract payment/refund existing tetap.
- Selected rows contract existing tetap.
- Finance engine / refund engine tetap.
- `NotePaymentStatusResolver` tetap return string.

## Impacted Tests `[IMPACTED-TESTS]`
### Wajib diubah pada fase implementasi UI
- `tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php`
- `tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php`
- `tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php` bagian assertion view

### Wajib tetap hijau tanpa perubahan contract backend
- Payment/refund HTTP behavior tests
- Allocation/refund persistence tests
- Backend tests yang tidak mengunci copy panel lama

## File target fase implementasi `[FILES]`
### View
- `resources/views/cashier/notes/show.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/payment-form.blade.php`
- `resources/views/cashier/notes/partials/refund-form.blade.php`

### JS
- `public/assets/static/js/pages/cashier-note-payment.js`
- `public/assets/static/js/pages/cashier-note-refund.js`

### Builder/controller
- `app/Application/Note/Services/NoteDetailPageDataBuilder.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/NoteDetailPageController.php`

### Tests
- `tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php`
- `tests/Feature/Note/CashierRefundedNoteDetailViewFeatureTest.php`
- `tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`

## Bukti verifikasi `[PROOF]`
- command:
  - `make verify`
  - hasil: baseline hijau sebelum phase implementasi
- command:
  - `git status --short`
  - hasil: working tree bersih sebelum phase implementasi

## Blocker aktif `[BLOCKER]`
- tidak ada blocker aktif untuk menandai contract change
- implementasi belum dimulai karena phase ini hanya penandaan contract

## State repo yang penting untuk langkah berikutnya
- Baseline repo bersih dan verify hijau.
- Contract change sudah ditandai eksplisit.
- Step implementasi berikutnya harus memperlakukan migrasi ini sebagai perubahan contract presentasi.

## Next step paling aman `[NEXT]`
- Susun implementation plan detail untuk phase UI migration yang:
  - mengubah builder/controller/view/JS/tests secara serempak
  - menjaga route dan request contract tetap stabil

## Catatan masuk halaman berikutnya
Saat membuka halaman kerja berikutnya, bawa minimal:
- file handoff ini
- `docs/AI_RULES/40_ARCHITECTURE/41_PUBLIC_CONTRACTS.md`
- referensi baseline detail page
- daftar impacted tests
- hasil `make verify` baseline terakhir

## Ringkasan singkat siap tempel

### Ringkasan
- target: tandai explicit presentation contract change untuk cashier note detail page
- status: locked
- progres: 100%
- hasil utama: perubahan panel lama ke modal launcher resmi dianggap contract change
- next step: susun implementation plan serempak untuk builder/controller/view/JS/tests

### Jangan dibuka ulang
- ini bukan incidental refactor
- route dan request contract payment/refund tidak berubah pada fase ini
- selected rows tetap dipakai pada fase ini

### Data minimum bila ingin lanjut
- baseline repo bersih
- daftar impacted tests
- file contract marker ini
