# ERROR LOG 0006 - PAYMENT CONCURRENCY CHARACTERIZATION GAP

## FACT
- Kontrak domain payment sudah tegas: `paid` tidak bisa dibatalkan; kalau perlu reversal, jalurnya adalah refund. Ini selaras dengan `docs/01_standards/domain/0052_payment_lifecycle.md`.
- Partial payment memang kontrak aktif, bukan mode samping. Exactness outstanding/remaining juga sudah dipasang di level policy dan resolver.
- Ada boundary transaksi yang jelas pada jalur inti payment/refund:
  - `RecordAndAllocateNotePaymentOperation` memanggil `getByIdForUpdate()` pada note sebelum menghitung outstanding dan melakukan alokasi.
  - `RecordCustomerRefundOperation` juga memanggil `getByIdForUpdate()` pada note sebelum menghitung batas refund.
- Ada idempotency record dan service untuk replay duplicate submit pada workspace create flow.
- Karena itu, masalah ini bukan "payment tidak punya guard sama sekali". Masalahnya adalah belum ada proof runtime yang secara khusus meng-characterize perilaku saat dua request payment/refund bersaing pada note/outstanding yang sama.

## SOURCE EVIDENCE
- `docs/01_standards/domain/0052_payment_lifecycle.md`
  - `paid` cannot be cancelled; reversal must go through refund.
  - delete hanya untuk `draft`.
- `docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`
  - same-note finance mutation harus diserialisasi.
  - `lockForUpdate()` pada target note row adalah boundary yang disukai.
  - idempotency membantu duplicate submit, tetapi bukan pengganti lock.
- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php`
  - seluruh alur berada di dalam transaction boundary.
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
  - `notes->getByIdForUpdate(trim($noteId))`
  - `PaymentAllocationPolicy::assertAllocatable(...)`
  - alokasi lalu persist payment dan allocation, lalu auto-close jika eligible.
- `app/Application/Payment/Services/RecordCustomerRefundOperation.php`
  - `notes->getByIdForUpdate(trim($noteId))`
  - `RefundPairLimitGuard::assertWithinAllocated(...)`
  - refund lalu refund allocation.
- `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentAmountResolver.php`
  - outstanding dihitung dari total note dikurangi total allocated saat itu.
  - partial payment ditolak jika `amount >= outstandingAmount`.
- `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`
  - resolver, policy, allocation, persist payment, persist allocation, auto-close, audit.
- `app/Application/Note/Services/CreateTransactionWorkspaceIdempotencyService.php`
  - replay/processing/succeeded untuk duplicate submit berdasarkan `actor_id`, `operation`, dan `idempotency_key`.
- `database/migrations/2026_05_25_235500_create_idempotency_records_table.php`
  - unique scope key ada, plus index status dan expires.
- `tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php`
  - duplicate submit tanpa idempotency guard menghasilkan duplikasi.
  - same idempotency key + same payload tidak membuat duplicate note.
  - retry setelah rollback tidak meninggalkan stale idempotency record.
- `tests/Unit/Application/Payment/Policies/PaymentAllocationPolicyTest.php`
  - amount yang melebihi outstanding ditolak.
- `tests/Unit/Application/Note/Services/NoteRefundPaymentOptionsBuilderTest.php`
  - refund options dibangun dari component allocations, bukan legacy-only rows.

## PAYMENT LIFECYCLE CONTRACT
- `paid` cannot be canceled.
- Jika reversal diperlukan, jalurnya refund, bukan cancel.
- Partial payment harus tetap exact terhadap outstanding yang valid.
- Outstanding yang dipakai untuk validasi harus dihitung dari state aktual, bukan asumsi UI.
- idempotency adalah guard duplicate submit, bukan pengganti lock concurrency.

## FINDINGS
1. CONFIRMED: kontrak lifecycle payment sudah menolak model cancel pada `paid` dan menegaskan refund sebagai reversal path.
2. CONFIRMED: exactness pembayaran sudah dijaga oleh `CreateTransactionWorkspaceInlinePaymentAmountResolver` dan `PaymentAllocationPolicy`; partial payment yang sama atau lebih besar dari outstanding ditolak.
3. CONFIRMED: jalur inti payment/refund utama memakai transaction boundary dan `getByIdForUpdate()` pada note, jadi ada indikasi kuat bahwa same-note serialization memang direncanakan.
4. SUSPECTED / GAP: belum ada proof runtime yang menunjukkan bagaimana dua pembayaran paralel ke note yang sama berperilaku di bawah interleaving nyata. Ini belum boleh dinaikkan menjadi bug confirmed.
5. GAP: interaksi `idempotency_records` dengan collision concurrent payment yang sebenarnya belum dibuktikan. Proof yang ada baru duplicate-submit serial/replay behavior.
6. GAP: belum ada karakterisasi langsung terhadap path workspace inline payment di bawah konflik concurrent request yang menyasar outstanding yang sama.

## IMPACT
- Jika serialization ternyata tidak konsisten di salah satu entry point, risiko yang masuk akal adalah:
  - dua request melihat outstanding yang sama,
  - alokasi menjadi tidak exact,
  - auto-close membaca state stale,
  - refund/paid lifecycle terlihat benar di UI tetapi tidak terjaga di interleaving nyata.
- Namun saat ini ini masih impact potensial, bukan failure runtime yang sudah terbukti.
- Baseline test suite yang sudah hijau tetap berguna sebagai regresi normal-path, tetapi tidak otomatis menutup race characterization.

## GAP
- Tidak ada proof concurrent dua request payment terhadap note yang sama.
- Tidak ada proof concurrent payment versus refund pada note yang sama.
- Tidak ada proof collision pada `idempotency_records` yang digabung dengan same-note concurrency.
- Tidak ada proof bahwa semua payment entry point memakai boundary locking yang sama pada skenario live interleaving.
- Tidak ada proof bahwa auto-close atau refund lifecycle tetap exact ketika dua request selesai sangat berdekatan.

## CLASSIFICATION
- `SUSPECTED` untuk risiko karakterisasi race pada payment concurrency yang belum diuji.
- `GAP` untuk ketiadaan proof runtime concurrency, bukan `CONFIRMED bug`.
- `CONFIRMED` hanya untuk kontrak lifecycle, boundary transaction, dan exactness policy yang sudah terlihat di source.

## SOLUTION DIRECTION, NO IMPLEMENTATION
- Pertahankan prinsip: same-note finance mutation harus diserialisasi di boundary server, bukan di UI.
- Pastikan semua entry point yang menulis payment/refund/allocation mengacu ke boundary yang sama, lalu buktikan dengan test karakterisasi.
- Jadikan idempotency sebagai pelindung duplicate submit, bukan sebagai pengganti lock.
- Pertahankan refund sebagai satu-satunya reversal path untuk `paid`.
- Blueprints verifikasi harus fokus pada interleaving nyata, bukan pada validasi satu request saja.

## SUGGESTED NEXT PROOF
1. Buat test karakterisasi dua request concurrent yang sama-sama menarget `RecordAndAllocateNotePaymentHandler` pada note dan outstanding yang sama.
2. Buat test yang sama untuk workspace inline payment bila jalur itu masih dipakai sebagai entry point pembayaran.
3. Uji payment versus refund collision pada note yang sama, lalu verifikasi outstanding final, jumlah allocation, dan auto-close/refund outcome.
4. Tambahkan karakterisasi `idempotency_records` saat ada duplicate submit dan saat request kedua datang bersamaan, bukan hanya setelah request pertama selesai.

## MINIMUM OWNER COMMANDS
```bash
rg -n "PaymentAllocation|CustomerPayment|Allocate|outstanding|lockForUpdate|idempotency|DB::transaction" app database tests -g '*.php'
php artisan test --filter=Payment
php artisan test --filter=Note
```

## FINAL STATUS
- Status akhir: `GAP`.
- Alasan: kontrak payment dan boundary transaksi sudah terlihat, tetapi proof runtime untuk same-note concurrent payment characterization belum ada.
- Dengan data yang ada sekarang, tidak sah menaikkan temuan ini menjadi confirmed runtime bug.

## REMEDIATION UPDATE - 2026-05-29

### FACT
- This issue started as `SUSPECTED / GAP`, not a confirmed runtime bug.
- Runtime characterization was added before production remediation.
- Payment/payment same-note collision was characterized first and passed.
- Payment/refund same-note collision produced confirmed runtime RED proof before patch.
- The RED proof was not over-allocation.
- The RED proof was a transient MySQL concurrency exception escaping the refund transaction boundary:
  - `Illuminate\Database\QueryException`
  - `SQLSTATE[HY000]: General error: 1020 Record has changed since last read in table 'notes'`
  - failing SQL shape: `select * from notes where id = note-payment-refund-concurrency-1 limit 1 for update`
- Remediation added a shared retry runner around payment/refund transaction boundaries.
- Retry classification is isolated in `PaymentConcurrencyTransientExceptionClassifier`.
- Retry execution is isolated in `PaymentTransactionRetryRunner`.

### CHANGED FILES
- `app/Application/Payment/Services/PaymentConcurrencyTransientExceptionClassifier.php`
- `app/Application/Payment/Services/PaymentTransactionRetryRunner.php`
- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php`
- `app/Application/Payment/Services/RecordCustomerRefundTransaction.php`
- `tests/Feature/Payment/PaymentConcurrencyCharacterizationFeatureTest.php`
- `tests/Feature/Payment/PaymentRefundConcurrencyCharacterizationFeatureTest.php`

### PROOF
- Payment/payment characterization:
  - `Tests: 1 passed (10 assertions)`
  - `Duration: 11.28s`
- Payment/refund characterization before patch:
  - RED with `SQLSTATE[HY000]: General error: 1020 Record has changed since last read in table 'notes'`
- Targeted concurrency proof after patch:
  - `Tests: 2 passed (23 assertions)`
  - `Duration: 22.30s`
- Full verification after patch:
  - `Tests: 2 skipped, 1118 passed (6285 assertions)`
  - `Duration: 71.16s`

### FINAL STATUS
- Status akhir: `FIXED WITH PROOF` for MySQL local payment/payment and payment/refund same-note concurrency characterization.
- Remaining gap:
  - true concurrent idempotency collision is not yet characterized.
  - PostgreSQL behavior is not proven by this MySQL proof.
  - unrelated full-repo audit issues remain outside this remediation.
