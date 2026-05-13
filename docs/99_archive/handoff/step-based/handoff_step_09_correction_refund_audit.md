# Handoff — Step 9 Correction, Refund, Audit

## Metadata

* Tanggal: 2026-03-15
* Nama slice / topik: Step 9 — Correction, Refund, Audit
* Workflow step: Step 9
* Status: CLOSED untuk scope Step 9 yang dikunci pada halaman kerja ini
* Progres:

  * Step 9 scope terkunci: 100%
  * Workflow transisi Step 8 → Step 9: 100%

## Target halaman kerja

Menutup Step 9 berdasarkan fakta repo dan bukti test, dengan urutan kerja sebagai berikut:

* menyelesaikan gate pembuka dari akhir Step 8 ke awal Step 9
* mengunci desain arsitektural Step 9 dengan prinsip “B-now, C-direction”
* menghidupkan correction flow resmi pada paid note
* menghidupkan refund sebagai fakta finansial baru tanpa mengubah payment/allocation lama
* menutup slice nominal correction pertama secara aman, dibatasi ke `service_only`
* memastikan semua perubahan lolos feature proof dan audit hexagonal

Hasil akhirnya pada halaman ini:

* paid note tidak bisa diubah bebas lewat flow biasa
* correction status pada paid note hidup dengan reason + before/after + audit
* refund hidup sebagai fakta baru dan audited
* paid-status note membaca net settlement (`allocation - refund`)
* nominal correction pertama hidup untuk `service_only` dan mengembalikan `refund_required_rupiah`
* seluruh proof utama `tests/Feature/Note`, `tests/Feature/Payment`, dan `tests/Arch` pass

## Referensi yang dipakai `[REF]`

* Blueprint:
  * `docs/blueprint/blueprint_v1.md`
* Workflow:
  * `docs/workflow/workflow_v1.md`
  * Step 9: correction flow, paid note edit guard, alasan wajib, before/after snapshot otomatis, refund/adjustment flow bila diperlukan
* DoD:
  * `docs/dod/dod_v1.md`
* ADR:
  * `docs/adr/0005-paid-note-correction-requires-audit.md`
  * `docs/adr/0008-audit-first-sensitive-mutations.md`
  * `docs/adr/0009-reporting-as-read-model.md` sebagai referensi batas reporting/read model saat closing gate sebelumnya
  * `docs/adr/0011-money-stored-as-integer-rupiah.md`
* Handoff sebelumnya:
  * `docs/handoff/handoff_step_8_payment_receivable_engine_transition_to_step_9.md`
  * handoff refactoring/final audit yang dikirim user di awal halaman ini
* Snapshot repo / output command yang dipakai:
  * audit Makefile/DoD gate: `lint`, `audit-contract`, `test-report`, `test-audit`
  * snapshot area Note, Payment, Audit, Ports, Adapters, migrations, tests
  * output test parsial dan regresi untuk Note, Payment, Arch
  * output audit hexagonal yang sempat gagal lalu diperbaiki

## Fakta terkunci `[FACT]`

* Step 8 ditutup secara governance dengan defer `seed-demo` dan `seed-test` ke backlog fase laporan/demo/integrasi; dari titik itu Step 9 sah dibuka.
* `test-report` berhasil ditutup dengan test nyata `tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php` dan target make `test-report`.
* `test-audit` berhasil ditutup dengan test nyata `EnableAdminTransactionCapabilityFeatureTest` dan `DisableAdminTransactionCapabilityFeatureTest`; bukan placeholder.
* Correction pada paid note yang hidup di repo pada akhir halaman ini ada dua:
  * correction status work item pada paid note
  * correction nominal `service_only` pada paid note
* Flow biasa sekarang menolak paid note untuk mutasi sensitif yang sudah dicakup:
  * add item biasa ke paid note ditolak
  * update status biasa pada paid note ditolak
* `NoteCorrectionSnapshotBuilder` hidup dan dipakai untuk before/after snapshot correction.
* `CustomerRefund` hidup sebagai fakta baru terpisah; refund tidak mengubah `customer_payments` lama dan tidak mengubah `payment_allocations` lama.
* `reason` pada refund disimpan di refund record dan juga tercatat di audit log.
* `NotePaidStatusPolicy` tidak lagi membaca paid-status dari allocation saja; rule final adalah net settlement note = total allocation note - total refund note.
* Nominal correction `service_only` tidak auto-mencatat refund; handler mengembalikan `refund_required_rupiah` sebagai informasi kebutuhan refund setelah correction.
* Scope nominal correction pada halaman ini sengaja dibatasi ke `service_only`; `external_purchase`, `store_stock_sale_only`, dan `service_with_store_stock_part` tidak diimplementasikan untuk nominal correction.
* Audit hexagonal sempat gagal karena policy paid-note ditempatkan di `app/Core` dan bergantung ke `App\Ports`; pelanggaran itu diperbaiki dengan memindahkan policy ke `app/Application`.
* Pada akhir halaman ini bukti final menunjukkan:
  * `tests/Feature/Note` pass
  * `tests/Feature/Payment` pass
  * `tests/Arch` pass

## Scope yang dipakai

### `[SCOPE-IN]`

* gate pembuka Step 9 dari akhir Step 8
* correction flow resmi untuk paid note
* paid note edit guard pada flow biasa yang relevan
* reason wajib untuk correction/refund yang sudah diimplementasikan
* before/after snapshot untuk correction yang sudah diimplementasikan
* refund sebagai fakta finansial baru
* perbaikan paid-status agar net of refund
* nominal correction pertama yang dibatasi ke `service_only`
* bukti regresi Note, Payment, dan Arch

### `[SCOPE-OUT]`

* nominal correction untuk:
  * `service_with_external_purchase`
  * `store_stock_sale_only`
  * `service_with_store_stock_part`
* auto-create refund dari nominal correction
* HTTP/UI final correction dan refund
* redesign audit storage/table yang lebih kaya dari `event/context/created_at`
* reporting/refund projection
* Step 10 Employee Finance

## Keputusan yang dikunci `[DECISION]`

* Gate akhir Step 8 ditutup dengan revisi governance: `seed-demo` dan `seed-test` dipindahkan menjadi backlog, bukan blocker Step 8.
* Arah desain Step 9 dikunci sebagai “B-now, C-direction”:
  * target jangka menengah tetap menuju struktur correction/refund yang lebih lengkap
  * eksekusi tahap pertama dilakukan dengan slice kecil dan terkontrol
* Correction pada paid note tidak boleh ditambal ke flow biasa; harus lewat handler correction resmi yang terpisah.
* Policy paid-note dipusatkan dan dipindah ke layer Application agar lolos audit hexagonal.
* Refund dikunci sebagai fakta baru terpisah (`CustomerRefund`), bukan negative payment, bukan edit mundur pada payment/alokasi lama.
* `reason` refund harus ada pada refund record; tidak cukup hanya di audit log.
* Setelah refund hidup, paid-status note wajib dihitung dari net settlement (`allocated - refunded`).
* Nominal correction pada Step 9 dibatasi dulu ke `service_only` saja karena itu yang paling kecil blast radius dan tidak menyentuh inventory/costing/external lines.
* Nominal correction `service_only` tidak auto-create refund pada slice ini; handler hanya menghitung dan mengembalikan `refund_required_rupiah`.
* Nominal correction append-only delta line ditolak untuk repo saat ini karena model `WorkItem`/`ServiceDetail`/`StoreStockLine`/`ExternalPurchaseLine` tidak mendukung correction line negatif dan seluruh guard masih mengunci komponen positif.

## File yang dibuat/diubah `[FILES]`

### File baru

* `app/Application/Note/Services/NoteCorrectionSnapshotBuilder.php`
* `app/Application/Note/UseCases/CorrectPaidWorkItemStatusHandler.php`
* `app/Application/Note/UseCases/CorrectPaidServiceOnlyWorkItemHandler.php`
* `app/Application/Note/Policies/NotePaidStatusPolicy.php`
* `app/Application/Note/Policies/NoteAddabilityPolicy.php`
* `app/Core/Payment/CustomerRefund/CustomerRefund.php`
* `app/Ports/Out/Payment/CustomerRefundWriterPort.php`
* `app/Ports/Out/Payment/CustomerRefundReaderPort.php`
* `database/migrations/2026_03_16_000100_create_customer_refunds_table.php`
* `app/Adapters/Out/Payment/DatabaseCustomerRefundWriterAdapter.php`
* `app/Adapters/Out/Payment/DatabaseCustomerRefundReaderAdapter.php`
* `tests/Unit/Core/Payment/CustomerRefund/CustomerRefundTest.php`
* `tests/Unit/Application/Note/Policies/NotePaidStatusPolicyTest.php`
* `tests/Feature/Note/CorrectPaidWorkItemStatusFeatureTest.php`
* `tests/Feature/Note/CorrectPaidServiceOnlyWorkItemFeatureTest.php`
* `tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`

### File diubah

* `app/Application/Note/UseCases/AddWorkItemHandler.php`
* `app/Application/Note/UseCases/UpdateWorkItemStatusHandler.php`
* `app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php`
* `app/Ports/Out/Note/WorkItemWriterPort.php`
* `app/Providers/HexagonalServiceProvider.php`
* `app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`
* `app/Ports/Out/Payment/PaymentAllocationReaderPort.php`
* `app/Application/Note/Policies/NotePaidStatusPolicy.php`
* `app/Adapters/Out/Payment/DatabaseCustomerRefundReaderAdapter.php`

## Bukti verifikasi `[PROOF]`

* command:
  * `php -l app/Core/Note/Policies/NotePaidStatusPolicy.php && php -l app/Core/Note/Policies/NoteAddabilityPolicy.php && php artisan test tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php && php artisan test tests/Feature/Note/AddServiceOnlyWorkItemFeatureTest.php`
  * hasil:
    * syntax policy awal valid
    * paid note tetap menolak add item
    * note total 0 tetap boleh menerima item pertama
* command:
  * `php -l app/Application/Note/Services/NoteCorrectionSnapshotBuilder.php && php artisan test tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php && php artisan test tests/Feature/Note/UpdateWorkItemStatusFeatureTest.php`
  * hasil:
    * snapshot builder valid
    * flow add item dan update status existing tetap pass
* command:
  * `php -l app/Application/Note/UseCases/UpdateWorkItemStatusHandler.php && php -l tests/Feature/Note/UpdateWorkItemStatusFeatureTest.php && php artisan test tests/Feature/Note/UpdateWorkItemStatusFeatureTest.php`
  * hasil:
    * update status handler valid
    * flow biasa tetap pass untuk unpaid note
    * paid note ditolak lewat flow biasa
* command:
  * `php -l app/Application/Note/UseCases/CorrectPaidWorkItemStatusHandler.php && php -l tests/Feature/Note/CorrectPaidWorkItemStatusFeatureTest.php && php artisan test tests/Feature/Note/CorrectPaidWorkItemStatusFeatureTest.php && php artisan test tests/Feature/Note/UpdateWorkItemStatusFeatureTest.php`
  * hasil:
    * correction status handler valid
    * correction status paid note hidup
    * reason wajib hidup
    * unpaid note ditolak correction status
* command:
  * `php -l app/Application/Note/Policies/NotePaidStatusPolicy.php && php -l app/Application/Note/Policies/NoteAddabilityPolicy.php && php -l app/Application/Note/UseCases/AddWorkItemHandler.php && php -l app/Application/Note/UseCases/UpdateWorkItemStatusHandler.php && php -l app/Application/Note/UseCases/CorrectPaidWorkItemStatusHandler.php && php -l app/Providers/HexagonalServiceProvider.php && php artisan test tests/Feature/Note && php artisan test tests/Feature/Payment && php artisan test tests/Arch`
  * hasil:
    * boundary repair syntax valid
    * `tests/Feature/Note` pass
    * `tests/Feature/Payment` pass
    * `tests/Arch` pass
* command:
  * `php -l app/Core/Payment/CustomerRefund/CustomerRefund.php && php -l tests/Unit/Core/Payment/CustomerRefund/CustomerRefundTest.php && php -l app/Ports/Out/Payment/CustomerRefundWriterPort.php && php -l app/Ports/Out/Payment/CustomerRefundReaderPort.php && php -l app/Ports/Out/Payment/PaymentAllocationReaderPort.php && php -l app/Adapters/Out/Payment/DatabaseCustomerRefundWriterAdapter.php && php -l app/Adapters/Out/Payment/DatabaseCustomerRefundReaderAdapter.php && php -l app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php && php -l app/Application/Payment/UseCases/RecordCustomerRefundHandler.php && php -l tests/Feature/Payment/RecordCustomerRefundFeatureTest.php && php -l app/Providers/HexagonalServiceProvider.php && php artisan test tests/Unit/Core/Payment/CustomerRefund/CustomerRefundTest.php && php artisan test tests/Feature/Payment/RecordCustomerRefundFeatureTest.php && php artisan test tests/Feature/Payment && php artisan test tests/Arch`
  * hasil:
    * refund domain valid
    * refund feature hidup
    * semua payment tests pass
    * arch pass
* command:
  * `php -l app/Ports/Out/Payment/CustomerRefundReaderPort.php && php -l app/Adapters/Out/Payment/DatabaseCustomerRefundReaderAdapter.php && php -l app/Application/Note/Policies/NotePaidStatusPolicy.php && php -l tests/Unit/Application/Note/Policies/NotePaidStatusPolicyTest.php && php artisan test tests/Unit/Application/Note/Policies/NotePaidStatusPolicyTest.php && php artisan test tests/Feature/Note && php artisan test tests/Feature/Payment && php artisan test tests/Arch`
  * hasil:
    * paid-status policy net of refund valid
    * regression Note/Payment/Arch pass
* command:
  * `php -l app/Ports/Out/Note/WorkItemWriterPort.php && php -l app/Adapters/Out/Note/DatabaseWorkItemWriterAdapter.php && php artisan test tests/Feature/Note && php artisan test tests/Arch`
  * hasil:
    * writer `updateServiceOnly(...)` valid
    * regression Note/Arch pass
* command:
  * `php -l app/Application/Note/UseCases/CorrectPaidServiceOnlyWorkItemHandler.php && php -l tests/Feature/Note/CorrectPaidServiceOnlyWorkItemFeatureTest.php && php artisan test tests/Feature/Note/CorrectPaidServiceOnlyWorkItemFeatureTest.php && php artisan test tests/Feature/Note && php artisan test tests/Feature/Payment && php artisan test tests/Arch`
  * hasil:
    * nominal correction `service_only` valid
    * feature test nominal correction pass
    * seluruh `tests/Feature/Note`, `tests/Feature/Payment`, `tests/Arch` pass

## Blocker aktif `[BLOCKER]`

* tidak ada blocker aktif untuk scope Step 9 yang dikunci pada halaman ini

## State repo yang penting untuk langkah berikutnya

* Policy paid/addability untuk Note sekarang berada di layer `Application`, bukan `Core`, agar boundary hexagonal tetap bersih.
* Paid-status note sekarang memakai net settlement note: total allocation note dikurangi total refund note.
* Refund sudah menjadi fakta baru di tabel `customer_refunds` dan reason ada di refund record.
* Correction nominal yang hidup baru `service_only`; tipe work item lain belum boleh diasumsikan punya nominal correction path.
* `CorrectPaidServiceOnlyWorkItemHandler` mengembalikan `refund_required_rupiah`; slice ini belum auto-create refund setelah correction nominal.
* `WorkItemWriterPort` sekarang punya mutation khusus `updateServiceOnly(...)`; belum ada mutation generic untuk semua tipe work item.
* Model `WorkItem`/`ServiceDetail`/`ExternalPurchaseLine`/`StoreStockLine` saat ini tidak mendukung append-only correction line negatif.
* Store-stock correction belum disentuh dan inventory/costing belum punya reversal/correction path resmi di Step 9.

## Next step paling aman `[NEXT]`

* Kunci desain ekspansi pasca-Step 9 untuk salah satu dari dua arah berikut:
  * auto-create refund setelah nominal correction `service_only`, atau
  * ekspansi nominal correction ke tipe work item berikutnya dengan analisis inventory/external-cost impact terlebih dulu

## Catatan masuk halaman berikutnya

Saat membuka halaman kerja berikutnya, bawa minimal:

* file handoff ini
* `docs/setting_control/first_in.md`
* `docs/setting_control/ai_contract.md`
* `docs/workflow/workflow_v1.md`
* `docs/adr/0005-paid-note-correction-requires-audit.md`
* `docs/adr/0008-audit-first-sensitive-mutations.md`
* snapshot file/output terbaru bila memang ingin melanjutkan ekspansi nominal correction atau auto-refund coupling

## Ringkasan singkat siap tempel

### Ringkasan
* target: menutup Step 9 untuk scope correction, refund, audit yang dikunci di repo saat ini
* status: CLOSED untuk scope terkunci
* progres: 100% pada scope Step 9 yang dikerjakan di halaman ini
* hasil utama:
  * correction status paid note hidup
  * refund fact hidup
  * paid-status net of refund hidup
  * nominal correction `service_only` hidup
  * reason + before/after + audit hidup
  * Note/Payment/Arch pass
* next step:
  * pilih ekspansi pasca-Step 9: auto-refund coupling atau nominal correction tipe lain

### Jangan dibuka ulang
* refund adalah fakta baru terpisah, bukan edit mundur payment/alokasi lama
* policy paid/addability Note harus tetap di layer Application, bukan Core
* nominal correction saat ini hanya `service_only`
* correction line append-only negatif ditolak untuk model repo saat ini

### Data minimum bila ingin lanjut
* file handoff ini
* snapshot file writer/mutation area yang akan disentuh bila ingin ekspansi
* output test terbaru bila ada perubahan setelah handoff ini
