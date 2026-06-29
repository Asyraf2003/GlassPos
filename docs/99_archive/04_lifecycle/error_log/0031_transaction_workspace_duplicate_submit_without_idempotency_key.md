# 031 - Transaction workspace duplicate submit can create duplicate financial rows without idempotency key

Status: Strict Fixed
Keparahan: High
Klasifikasi: financial integrity / duplicate submit / incomplete idempotency coverage

## Ringkasan

Create transaction workspace sebelumnya sudah memiliki idempotency service, tetapi proteksi hanya aktif jika payload membawa `idempotency_key`.

Jalur UI create workspace sebelumnya tidak mengirim hidden `idempotency_key`, dan validation rules sebelumnya masih memperbolehkan key kosong. Akibatnya direct duplicate submit dengan payload yang sama dapat membuat dua `notes`, dua `work_items`, dua payment records, dua allocation records, dan dua projection rows.

Patch sekarang menutup celah untuk create transaction workspace:

- page controller menghasilkan atau memakai ulang `idempotency_key` saat render form create;
- Blade create form mengirim hidden `idempotency_key`;
- request rules mewajibkan `idempotency_key`;
- direct POST tanpa key ditolak sebelum membuat row finansial;
- idempotency service tetap menangani same-key replay, same-key payload conflict, dan rollback retry.

## Strict-Fixed-Scope

Scope yang ditutup:

- `notes.workspace.store` untuk create transaction workspace.
- Duplicate submit dari UI normal create workspace.
- Direct POST create workspace tanpa `idempotency_key`.
- Same actor, same operation, same key, same payload replay.
- Same actor, same operation, same key, different payload conflict.
- Failed attempt rollback lalu retry dengan key sama.

Out of scope untuk log ini:

- edit/revision workspace idempotency;
- payment-after-note idempotency;
- refund idempotency;
- true parallel browser race stress test;
- payment allocation locking, karena itu domain ADR/log terpisah.

## Root cause

Idempotency guard sudah ada, tetapi coverage tidak lengkap karena key tidak diwajibkan dan tidak dihasilkan oleh UI normal.

Secara praktis, fitur idempotency hanya melindungi caller yang secara eksplisit mengirim key. Jalur UI utama create workspace sebelumnya tidak termasuk dalam proteksi tersebut.

## Source Reality Setelah Patch

`app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspacePageController.php`

- inject `App\Ports\Out\UuidPort`;
- membaca `old('idempotency_key')`;
- memakai ulang old key jika valid;
- membuat key baru melalui `$uuid->generate()` jika belum ada;
- mengirim `idempotencyKey` ke Blade.

`resources/views/cashier/notes/workspace/create.blade.php`

- untuk mode create, form render hidden field `name="idempotency_key"`;
- value memakai `old('idempotency_key', $idempotencyKey ?? '')`;
- untuk mode edit, hidden create idempotency key tidak dirender.

`app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php`

- `idempotency_key` sekarang `required`, `string`, `max:120`.

`app/Adapters/In/Http/Controllers/Cashier/Note/StoreTransactionWorkspaceController.php`

- tetap mengirim payload tervalidasi dan actor ke handler;
- tidak membuat fallback key di controller store, sehingga missing key gagal di boundary request.

`app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php`

- tetap menjalankan idempotency lifecycle: `replay()`, `start()`, `succeed()`.

`app/Application/Note/Services/CreateTransactionWorkspaceIdempotencyScopeResolver.php`

- masih mengembalikan `null` jika key kosong;
- kondisi kosong tidak bisa lewat dari `notes.workspace.store` setelah validation patch.

## UI Blade Impact

Ada perubahan Blade pada create workspace form.

Hidden key dibuat server-side dan dikirim melalui form normal. Tidak ada guard JavaScript yang dijadikan sumber kebenaran.

## Native JS Impact

Tidak ada perubahan native JS.

JavaScript boleh tetap menangani interaksi form, tetapi duplicate-submit integrity sekarang dijaga oleh server request validation dan DB-backed idempotency.

## Server Boundary

Direct POST tanpa `idempotency_key` sekarang ditolak oleh `StoreTransactionWorkspaceRules`.

Regression test memastikan request tanpa key tidak membuat row pada:

- `notes`;
- `work_items`;
- `work_item_service_details`;
- `customer_payments`;
- `customer_payment_cash_details`;
- `payment_component_allocations`;
- `note_history_projection`.

## ADR dan Blueprint Compatibility

`docs/03_blueprints/db/0018_create_transaction_idempotency_contract.md`

- selaras dengan requirement explicit `idempotency_key`;
- selaras dengan hidden key pada Blade create form;
- selaras dengan validation required setelah UI siap;
- selaras dengan same-key same-payload replay;
- selaras dengan same-key different-payload reject;
- selaras dengan failed-attempt retry.

`docs/02_architecture/adr/0019_note_access_boundary_cashier_date_window_and_transaction_capability_enforcement.md`

- selaras karena boundary mutasi enforced server-side, bukan hanya UI.

`docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md`

- tidak digantikan oleh patch ini;
- idempotency create workspace bersifat additive, sedangkan payment allocation concurrency lock tetap domain terpisah.

Konflik ADR/blueprint: tidak ditemukan.

## RED Proof

Command:

```bash
php artisan test tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php
```

Hasil sebelum production patch:

- exit code `1`;
- `2 failed, 3 passed, 48 assertions`;
- form create belum memiliki `name="idempotency_key"`;
- POST tanpa key masih redirect sukses ke index, bukan ditolak di create boundary.

## Targeted GREEN Proof

Command:

```bash
php artisan test tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php
```

Hasil setelah patch:

- `PASS`;
- `5 passed, 58 assertions`.

Coverage targeted:

- rendered create form membawa hidden `idempotency_key`;
- POST tanpa key ditolak dan tidak membuat row finansial;
- same-key same-payload tidak membuat nota kedua;
- same-key different-payload ditolak;
- rollback retry dengan key sama tetap bisa sukses satu kali.

## Focused Blast-Radius Proof

Command:

```bash
php artisan test tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceFullTransferFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePartialCashFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceRollbackFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php tests/Feature/Note/TransactionWorkspaceServiceCatalogSyncFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceTemplateContractFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php
```

Hasil:

- `PASS`;
- `34 passed, 338 assertions`.

Command:

```bash
php artisan test tests/Feature/Note tests/Feature/Payment
```

Hasil:

- `PASS`;
- `300 passed, 2009 assertions`;
- duration `45.26s`.

Command:

```bash
php artisan test tests/Feature/Reporting/PackageAutoSplitCreateReportImpactFeatureTest.php
```

Hasil:

- `PASS`;
- `1 passed, 20 assertions`.

## Full Verification Proof

Command:

```bash
make verify
```

Hasil:

- PHPStan `1794/1794`, `[OK] No errors`;
- line-count audit passed;
- Blade audit passed;
- contract audit passed;
- Pest `1172 passed, 6615 assertions`;
- duration `88.66s`;
- exit code `0`.

## Negative Search

Command:

```bash
rg -n -U "post\\(route\\('notes\\.workspace\\.store'\\), \\[\\n\\s+'note'" tests/Feature/Note -g '*.php'
```

Hasil:

- tidak ada match pada direct create-workspace POST fixture di `tests/Feature/Note` yang langsung mulai payload dengan `note` tanpa `idempotency_key`.

Command:

```bash
rg -n "idempotency_key" resources/views/cashier/notes/workspace/create.blade.php app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php app/Application/Note/Services/CreateTransactionWorkspaceIdempotencyScopeResolver.php
```

Hasil relevan:

- hidden input ada di Blade create workspace;
- request rule source memakai `required`;
- resolver masih membersihkan `idempotency_key` dari fingerprint payload;
- resolver masih skip jika key kosong, tetapi route store tidak lagi menerima key kosong.

## Remaining Gaps

Belum ada true parallel same-key browser race stress test pada scope log ini.

Gap tersebut tidak membuka ulang status 0031 karena:

- duplicate submit normal sudah memiliki RED/GREEN proof;
- direct POST tanpa key sudah ditolak;
- idempotency DB-backed lifecycle existing sudah diuji untuk replay, conflict, dan rollback retry;
- concurrency/locking payment allocation tetap dikawal oleh ADR/domain terpisah.

## Strict Closure Decision

0031 ditutup sebagai `Strict Fixed` untuk create transaction workspace duplicate submit.

Dasar closure:

- root cause sudah dipatch di UI render dan request validation;
- direct POST tanpa key tidak lagi bisa membuat row finansial;
- targeted duplicate-submit regression pass;
- focused create-workspace blast-radius pass;
- wider Note + Payment suite pass;
- global `make verify` pass.
