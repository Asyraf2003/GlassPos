# Handoff V2 - Operational Expense Implementation

## Status
Selesai untuk scope implementasi halaman/domain `operational expense`.

Status akhir:
- penyederhanaan domain expense ke active row + soft delete: selesai
- penghapusan kontrak `status draft/posted/cancelled` dari jalur resmi expense: selesai
- penyesuaian migration/schema expense: selesai
- penyesuaian seed expense: selesai
- create expense tanpa status: selesai
- payment method dipersempit ke `cash` dan `tf`: selesai
- category search minimal 2 karakter pada create expense: selesai
- create kategori dari konteks expense via redirect: selesai
- auto-return ke create expense setelah create kategori: selesai
- keyboard flow create expense: selesai
- keyboard flow create kategori: selesai
- soft delete action resmi expense: selesai
- konfirmasi hapus expense via modal/dialog: selesai
- read-side table hanya baca active row: selesai
- reporting hanya baca active row: selesai
- feature test target modul expense: selesai
- handoff-ready: ya

Progress halaman ini: 100%

## Tujuan yang ditutup
Menutup implementasi `operational expense` agar sesuai target operasional ringan:

- create expense langsung aktif
- nonaktif dilakukan dengan soft delete
- tidak ada lifecycle `draft/posted/cancelled`
- operator bisa kerja cepat dari keyboard
- kategori bisa dicari cepat dari create expense
- kategori yang belum ada bisa dibuat dari alur expense
- penghapusan expense aman lewat dialog konfirmasi
- list dan reporting konsisten hanya membaca data aktif

## Scope in
- domain `operational_expenses`
- create expense
- create category dari konteks expense
- expense list page
- expense soft delete action
- reporting yang memakai `operational_expenses`
- seeder expense
- UX/JS keyboard-centric create expense
- UX/JS keyboard-centric create kategori expense

## Scope out
- edit expense
- restore expense
- hard delete expense
- versioning expense
- audit foundation baru khusus expense
- redesign category master di luar alur create expense
- reporting lain di luar query yang memang membaca `operational_expenses`

## Referensi baseline repo yang dipakai

### Expense write path dan request
- `app/Adapters/In/Http/Requests/Expense/StoreExpenseRequest.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/StoreExpenseController.php`
- `app/Application/Expense/UseCases/RecordOperationalExpenseHandler.php`
- `app/Core/Expense/OperationalExpense/OperationalExpense.php`
- `app/Core/Expense/OperationalExpense/OperationalExpenseAccessors.php`
- `app/Core/Expense/OperationalExpense/OperationalExpenseValidation.php`
- `app/Ports/Out/Expense/OperationalExpenseWriterPort.php`
- `app/Adapters/Out/Expense/DatabaseOperationalExpenseWriterAdapter.php`

### Expense read-side / table
- `app/Adapters/In/Http/Requests/Expense/ExpenseTableQueryRequest.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/ExpenseTableDataController.php`
- `app/Application/Expense/UseCases/GetExpenseTableHandler.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTableBaseQuery.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTableOrdering.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTablePayload.php`

### Expense category
- `app/Adapters/In/Http/Controllers/Admin/Expense/CreateExpenseCategoryPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/StoreExpenseCategoryController.php`
- `app/Application/Expense/UseCases/CreateExpenseCategoryHandler.php`
- `app/Adapters/Out/Expense/DatabaseExpenseCategoryListPageQuery.php`

### Expense UI
- `resources/views/admin/expenses/create.blade.php`
- `resources/views/admin/expenses/index.blade.php`
- `resources/views/admin/expenses/categories/create.blade.php`
- `assets/static/js/pages/admin-expense-create/category-search.js`
- `assets/static/js/pages/admin-expense-create/flow.js`
- `assets/static/js/pages/admin-expense-create/boot.js`
- `assets/static/js/pages/admin-expense-category-create.js`
- `assets/static/js/pages/admin-expenses-table.js`

### Routing
- `routes/web/admin_expenses.php`

### Schema / seed
- `database/migrations/2026_03_15_000150_create_operational_expenses_table.php`
- `database/seeders/ExpenseSeeder.php`

### Reporting yang terdampak
- `app/Adapters/Out/Reporting/DatabaseOperationalExpenseReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/Queries/OperationalProfitMetricsQuery.php`
- `app/Application/Reporting/Services/OperationalExpenseSummaryBuilder.php`
- `app/Application/Reporting/DTO/OperationalExpenseSummaryRow.php`
- `app/Application/Reporting/DTO/Concerns/OperationalExpenseSummaryRowAccessors.php`

### Kiblat UX/JS yang dipakai
- `resources/views/cashier/notes/workspace/create.blade.php`
- `app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspacePageController.php`
- `public/assets/static/js/pages/admin-payroll-create.js`
- `public/assets/static/js/pages/admin-procurement-create.js`
- `public/assets/static/js/shared/admin-date-input.js`
- `resources/views/admin/products/index.blade.php`
- `public/assets/static/js/pages/admin-products-table.js`

## Fakta yang terkunci

1. `operational_expenses` bukan lagi transaksi mini dengan lifecycle `draft/posted/cancelled`.
2. Kontrak resmi expense sekarang adalah:
   - create = row aktif
   - nonaktif = soft delete
3. Sumber kebenaran aktif/nonaktif expense adalah `deleted_at`.
4. Jalur resmi create expense tidak lagi menerima field `status`.
5. `payment_method` expense dikunci ke:
   - `cash`
   - `tf`
6. Create expense tetap menulis snapshot kategori:
   - `category_code_snapshot`
   - `category_name_snapshot`
7. List expense hanya membaca row dengan `deleted_at is null`.
8. Reporting expense hanya membaca row dengan `deleted_at is null`.
9. Soft delete expense dilakukan lewat route resmi `DELETE`.
10. Hapus expense tidak lagi memakai `confirm()` browser, tetapi dialog/modal.
11. Create expense memakai category search minimal 2 karakter.
12. Saat kategori tidak ditemukan dari create expense, user diarahkan ke create kategori.
13. Setelah kategori baru berhasil dibuat dari konteks expense, user dikembalikan ke create expense dengan kategori baru sudah terseleksi.
14. Create expense dan create kategori dibuat keyboard-centric untuk flow operasional ringan.
15. Date field expense tetap mengikuti shared hook `AdminDateInput`, bukan init datepicker custom per page.

## Keputusan arsitektur yang dikunci

### 1. Model expense
Dipakai model:

- active row + soft delete

Maknanya:
- `operational_expenses` tetap menjadi row operasional utama
- tidak ada versioning formal untuk expense
- tidak ada lifecycle status transaksi
- histori nonaktif cukup ditandai via `deleted_at`

### 2. Kontrak create expense
Input minimum:
- `category_id`
- `amount_rupiah`
- `expense_date`
- `description`
- `payment_method`

Tidak ada:
- `status`

### 3. Nonaktif expense
Dipakai:
- soft delete

Tidak dipakai:
- cancel status
- hard delete
- restore flow
- immutable replace/versioning

### 4. Read-side expense
Default list:
- hanya row aktif

Tidak ada mode list bawaan untuk menampilkan deleted row.

### 5. Reporting expense
Rule inclusion:
- hanya row `deleted_at is null`

### 6. UX category dari create expense
Dipakai:
- search minimal 2 karakter
- Enter pilih hasil aktif
- jika hasil tidak ada, redirect ke form create kategori
- setelah save kategori, balik ke form expense dan auto-select

### 7. UX keyboard flow
Urutan create expense:
- kategori
- tanggal
- nominal
- metode bayar
- deskripsi
- submit

Urutan create kategori:
- kode
- nama
- deskripsi
- submit

## Kontrak migration/schema final yang dikunci

### A. `operational_expenses`
Kolom final minimum:
- `id`
- `category_id`
- `category_code_snapshot`
- `category_name_snapshot`
- `amount_rupiah`
- `expense_date`
- `description`
- `payment_method`
- `reference_no`
- `created_at`
- `updated_at`
- `deleted_at`

Catatan:
- `status` tidak lagi menjadi bagian schema resmi
- `deleted_at` wajib ada sebagai penanda soft delete
- `category_id` tetap mengarah ke `expense_categories`

Index minimum:
- `expense_date`
- `category_id`
- `deleted_at`

### B. `expense_categories`
Tidak ada redesign domain besar di batch ini.

Yang ditambah di flow:
- context `source=expense_create`
- prefill keyword dari query `q`
- redirect balik ke create expense setelah sukses bila datang dari expense create

## Implementasi yang sudah selesai

### 1. Schema expense disederhanakan
- migration dasar `operational_expenses` ditimpa agar langsung memakai `softDeletes()`
- kolom legacy `status` dikeluarkan dari schema resmi

### 2. Seeder expense disesuaikan
- `ExpenseSeeder` tidak lagi menulis `status`
- `ExpenseSeeder` tetap menulis snapshot kategori
- `ExpenseSeeder` memakai `payment_method` yang dibatasi ke `cash` dan `tf`

### 3. Create expense disederhanakan
- field `status` dihapus dari create page
- request validation tidak lagi menerima `status`
- use case create expense tidak lagi menerima `status`
- writer create expense menulis row aktif biasa

### 4. Soft delete expense resmi
Ditambahkan jalur:
- route delete expense
- controller delete expense
- handler delete expense
- writer soft delete expense

Behavior:
- hanya row aktif yang bisa di-soft-delete
- row yang sudah deleted tidak dianggap valid target delete lagi

### 5. Table expense aktif-only
- base query expense table membaca `whereNull('deleted_at')`
- payload table tetap ringan dan tidak memuat `status`

### 6. Reporting aktif-only
Disesuaikan:
- expense summary source reader
- operational profit query
- summary builder / DTO reporting

### 7. Payment method dipersempit
Expense create dan validasi hanya menerima:
- `cash`
- `tf`

### 8. Create expense keyboard-centric
Create expense dipecah per concern:
- `admin-expense-create/category-search.js`
- `admin-expense-create/flow.js`
- `admin-expense-create/boot.js`

Behavior:
- fokus awal ke category search
- Enter memilih kategori aktif
- setelah kategori terpilih fokus pindah ke tanggal
- Enter maju antar field sampai submit

### 9. Create kategori dari konteks expense
Ditambahkan behavior:
- query string `source=expense_create`
- query string `q=<keyword>`
- halaman create kategori menampilkan context info
- setelah sukses, redirect kembali ke:
  - `admin.expenses.create?category_id=<new_id>`

### 10. Create kategori keyboard-centric
Ditambahkan JS:
- `admin-expense-category-create.js`

Behavior:
- fokus awal ke `code`
- Enter pindah:
  - `code` -> `name`
  - `name` -> `description`
  - `description` -> submit

### 11. Hapus expense via modal/dialog
- halaman index expense memakai modal Bootstrap untuk konfirmasi hapus
- JS table expense membuka modal dan mengikat form delete ke row yang dipilih
- native `confirm()` dihapus

## Verifikasi yang sudah terbukti lulus

### Migration / seed
- `php artisan migrate:fresh --seed`

### Expense create / store
- `php artisan test tests/Feature/Expense/CreateExpensePageFeatureTest.php`
- `php artisan test tests/Feature/Expense/StoreExpenseHttpFeatureTest.php`

### Expense category from expense flow
- `php artisan test tests/Feature/Expense/CreateExpenseCategoryPageFeatureTest.php`
- `php artisan test tests/Feature/Expense/StoreExpenseCategoryHttpFeatureTest.php`

### Expense delete
- `php artisan test tests/Feature/Expense/SoftDeleteOperationalExpenseHttpFeatureTest.php`

### Expense list / data
- `php artisan test tests/Feature/Expense/ExpenseIndexPageFeatureTest.php`
- `php artisan test tests/Feature/Expense/ExpenseTableDataAccessFeatureTest.php`
- `php artisan test tests/Feature/Expense/ExpenseTableDataQueryFeatureTest.php`

### Reporting
- `php artisan test tests/Feature/Reporting/GetOperationalExpenseSummaryFeatureTest.php`
- `php artisan test tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`

## File yang berubah di batch ini

### Schema / seed
- `database/migrations/2026_03_15_000150_create_operational_expenses_table.php`
- `database/seeders/ExpenseSeeder.php`

### Expense write path
- `app/Adapters/In/Http/Requests/Expense/StoreExpenseRequest.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/StoreExpenseController.php`
- `app/Application/Expense/UseCases/RecordOperationalExpenseHandler.php`
- `app/Core/Expense/OperationalExpense/OperationalExpense.php`
- `app/Core/Expense/OperationalExpense/OperationalExpenseAccessors.php`
- `app/Core/Expense/OperationalExpense/OperationalExpenseValidation.php`
- `app/Ports/Out/Expense/OperationalExpenseWriterPort.php`
- `app/Adapters/Out/Expense/DatabaseOperationalExpenseWriterAdapter.php`

### Expense delete path
- `app/Application/Expense/UseCases/SoftDeleteOperationalExpenseHandler.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/SoftDeleteOperationalExpenseController.php`
- `routes/web/admin_expenses.php`

### Expense category create flow
- `app/Adapters/In/Http/Controllers/Admin/Expense/CreateExpensePageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/CreateExpenseCategoryPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/StoreExpenseCategoryController.php`

### Expense UI
- `resources/views/admin/expenses/create.blade.php`
- `resources/views/admin/expenses/index.blade.php`
- `resources/views/admin/expenses/categories/create.blade.php`
- `assets/static/js/pages/admin-expense-create/category-search.js`
- `assets/static/js/pages/admin-expense-create/flow.js`
- `assets/static/js/pages/admin-expense-create/boot.js`
- `assets/static/js/pages/admin-expense-category-create.js`
- `assets/static/js/pages/admin-expenses-table.js`
- public copies JS terkait di `public/assets/static/js/...`

### Expense read-side / reporting
- `app/Adapters/In/Http/Requests/Expense/ExpenseTableQueryRequest.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTableBaseQuery.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTableOrdering.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTablePayload.php`
- `app/Adapters/Out/Reporting/DatabaseOperationalExpenseReportingSourceReaderAdapter.php`
- `app/Adapters/Out/Reporting/Queries/OperationalProfitMetricsQuery.php`
- `app/Application/Reporting/Services/OperationalExpenseSummaryBuilder.php`
- `app/Application/Reporting/DTO/OperationalExpenseSummaryRow.php`
- `app/Application/Reporting/DTO/Concerns/OperationalExpenseSummaryRowAccessors.php`

### Feature tests
- `tests/Feature/Expense/CreateExpensePageFeatureTest.php`
- `tests/Feature/Expense/StoreExpenseHttpFeatureTest.php`
- `tests/Feature/Expense/ExpenseTableDataAccessFeatureTest.php`
- `tests/Feature/Expense/ExpenseTableDataQueryFeatureTest.php`
- `tests/Feature/Expense/ExpenseIndexPageFeatureTest.php`
- `tests/Feature/Expense/CreateExpenseCategoryPageFeatureTest.php`
- `tests/Feature/Expense/StoreExpenseCategoryHttpFeatureTest.php`
- `tests/Feature/Expense/SoftDeleteOperationalExpenseHttpFeatureTest.php`
- `tests/Feature/Reporting/GetOperationalExpenseSummaryFeatureTest.php`
- `tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`

## Gaps yang masih jujur diakui

1. Belum ada restore expense.
2. Belum ada edit expense.
3. Belum ada replace/recreate expense terstruktur.
4. Belum ada tampilan deleted expense untuk admin history mode.
5. Belum ada bulk delete expense.
6. Belum ada dedicated manual/browser test checklist tertulis untuk keyboard flow dan modal delete.
7. Belum ada handoff UI screenshot atau rekam langkah manual.

## Workflow halaman berikutnya
Jika halaman berikutnya ingin melanjutkan modul expense, urutan paling aman:

1. audit apakah perlu edit expense atau memang tetap cukup create baru + soft delete lama
2. bila perlu history admin, tambahkan mode baca deleted expense di list/query
3. bila perlu restore, definisikan policy restore dulu sebelum implementasi
4. tambahkan manual checklist UX keyboard flow
5. baru pertimbangkan hardening audit/read model lebih jauh

## Active step untuk halaman berikutnya
Step aktif pertama yang paling aman:

- putuskan apakah modul expense perlu `edit/restore` atau memang final cukup `create + soft delete`

Jangan langsung implementasi fitur tambahan tanpa mengunci policy itu dulu.

## Proof / bukti kenapa halaman ini boleh ditutup

Halaman ini boleh ditutup karena target bisnis yang dikunci di awal sudah tercapai:

- create expense langsung aktif
- nonaktif dilakukan dengan soft delete
- status draft/cancelled tidak lagi hidup di jalur resmi
- create expense lebih ringan untuk operator
- create kategori dari konteks expense sudah jalan
- delete expense sudah aman lewat dialog
- list dan reporting konsisten hanya membaca row aktif
- test target utama modul expense dan reporting yang terdampak sudah lulus

## Safest next step
Mulai halaman baru hanya jika ada kebutuhan nyata berikut:
- restore expense
- history deleted expense
- edit/replace expense
- manual UX hardening

Kalau tidak ada kebutuhan baru, modul expense bisa dianggap closed untuk target operasional ringan.

## Ringkasan satu kalimat
Kasus implementasi `operational expense` selesai dengan baseline final: create langsung aktif, nonaktif lewat soft delete, tanpa status draft/cancelled, create flow keyboard-centric, category lookup cepat dengan redirect create kategori saat tidak ditemukan, delete lewat modal, dan list/reporting hanya membaca row aktif.
