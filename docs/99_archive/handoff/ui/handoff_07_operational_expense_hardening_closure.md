# Handoff — UI Operational Expense Hardening Closure

## Metadata
- **Tanggal:** 2026-03-23
- **Nama slice / topik:** UI Operational Expense Hardening + Category Lifecycle
- **Status:** CLOSED
- **Progres:** 100%

---

## Target Halaman Kerja
Menutup gap pasca UI expense fase 1, lalu menaikkan modul expense ke kondisi operasional yang lebih konsisten dan siap dipakai:
- Histori expense aman walau kategori berubah.
- Kategori expense punya lifecycle resmi.
- Table expense resmi.
- Table kategori resmi.
- Form expense bersih, tidak misleading.
- Jalur resmi tetap dipakai end-to-end.
- Audit line/blade tetap aman.

---

## Referensi yang Dipakai `[REF]`
- `routes/web/admin_expenses.php`
- `app/Providers/HexagonalServiceProvider.php`

### Expense Histori + Create Flow
- `database/migrations/2026_03_23_000100_add_category_snapshot_columns_to_operational_expenses_table.php`
- `app/Core/Expense/OperationalExpense/OperationalExpense.php`
- `app/Core/Expense/OperationalExpense/OperationalExpenseAccessors.php`
- `app/Application/Expense/UseCases/RecordOperationalExpenseHandler.php`
- `app/Adapters/Out/Expense/DatabaseOperationalExpenseWriterAdapter.php`
- `app/Adapters/In/Http/Requests/Expense/StoreExpenseRequest.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/StoreExpenseController.php`
- `resources/views/admin/expenses/create.blade.php`

### Expense Table Resmi
- `app/Adapters/In/Http/Requests/Expense/ExpenseTableQueryRequest.php`
- `app/Application/Expense/DTO/ExpenseTableQuery.php`
- `app/Application/Expense/UseCases/GetExpenseTableHandler.php`
- `app/Ports/Out/Expense/OperationalExpenseTableReaderPort.php`
- `app/Adapters/Out/Expense/DatabaseOperationalExpenseTableReaderAdapter.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTableBaseQuery.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTableFilters.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTableOrdering.php`
- `app/Adapters/Out/Expense/Concerns/OperationalExpenseTablePayload.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/ExpenseTableDataController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/ExpenseIndexPageController.php`
- `resources/views/admin/expenses/index.blade.php`
- `resources/views/admin/expenses/partials/filter_drawer.blade.php`
- `assets/static/js/pages/admin-expenses-table.js`
- `public/assets/static/js/pages/admin-expenses-table.js`

### Category Lifecycle Audited
- `app/Core/Expense/ExpenseCategory/ExpenseCategory.php`
- `app/Ports/Out/Expense/ExpenseCategoryReaderPort.php`
- `app/Ports/Out/Expense/ExpenseCategoryWriterPort.php`
- `app/Adapters/Out/Expense/DatabaseExpenseCategoryReaderAdapter.php`
- `app/Adapters/Out/Expense/DatabaseExpenseCategoryWriterAdapter.php`
- `app/Application/Expense/UseCases/UpdateExpenseCategoryHandler.php`
- `app/Application/Expense/UseCases/ActivateExpenseCategoryHandler.php`
- `app/Application/Expense/UseCases/DeactivateExpenseCategoryHandler.php`
- `app/Adapters/In/Http/Requests/Expense/UpdateExpenseCategoryRequest.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/EditExpenseCategoryPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/UpdateExpenseCategoryController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/ActivateExpenseCategoryController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/DeactivateExpenseCategoryController.php`
- `resources/views/admin/expenses/categories/edit.blade.php`

### Category Table Resmi
- `app/Adapters/In/Http/Requests/Expense/ExpenseCategoryTableQueryRequest.php`
- `app/Application/Expense/DTO/ExpenseCategoryTableQuery.php`
- `app/Application/Expense/UseCases/GetExpenseCategoryTableHandler.php`
- `app/Ports/Out/Expense/ExpenseCategoryTableReaderPort.php`
- `app/Adapters/Out/Expense/DatabaseExpenseCategoryTableReaderAdapter.php`
- `app/Adapters/Out/Expense/Concerns/ExpenseCategoryTableBaseQuery.php`
- `app/Adapters/Out/Expense/Concerns/ExpenseCategoryTableFilters.php`
- `app/Adapters/Out/Expense/Concerns/ExpenseCategoryTableOrdering.php`
- `app/Adapters/Out/Expense/Concerns/ExpenseCategoryTablePayload.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/ExpenseCategoryTableDataController.php`
- `app/Adapters/In/Http/Controllers/Admin/Expense/ExpenseCategoryIndexPageController.php`
- `resources/views/admin/expenses/categories/index.blade.php`
- `resources/views/admin/expenses/categories/partials/filter_drawer.blade.php`
- `assets/static/js/pages/admin-expense-categories-table.js`
- `public/assets/static/js/pages/admin-expense-categories-table.js`

### Tests
- `tests/Feature/Expense/RecordOperationalExpenseFeatureTest.php`
- `tests/Feature/Expense/StoreExpenseHttpFeatureTest.php`
- `tests/Feature/Expense/CreateExpensePageFeatureTest.php`
- `tests/Feature/Expense/ExpenseIndexPageFeatureTest.php`
- `tests/Feature/Expense/ExpenseTableDataAccessFeatureTest.php`
- `tests/Feature/Expense/ExpenseTableDataQueryFeatureTest.php`
- `tests/Feature/Expense/ExpenseTableDataValidationFeatureTest.php`
- `tests/Feature/Expense/UpdateExpenseCategoryFeatureTest.php`
- `tests/Feature/Expense/ActivateExpenseCategoryFeatureTest.php`
- `tests/Feature/Expense/DeactivateExpenseCategoryFeatureTest.php`
- `tests/Feature/Expense/ExpenseCategoryEditPageFeatureTest.php`
- `tests/Feature/Expense/UpdateExpenseCategoryHttpFeatureTest.php`
- `tests/Feature/Expense/ActivateExpenseCategoryHttpFeatureTest.php`
- `tests/Feature/Expense/DeactivateExpenseCategoryHttpFeatureTest.php`
- `tests/Feature/Expense/ExpenseCategoryTableDataAccessFeatureTest.php`
- `tests/Feature/Expense/ExpenseCategoryTableDataQueryFeatureTest.php`
- `tests/Feature/Expense/ExpenseCategoryTableDataValidationFeatureTest.php`
- `tests/Feature/Expense/ExpenseCategoryIndexPageFeatureTest.php`

---

## Fakta Terkunci `[FACT]`

### 1. Histori expense sekarang aman
- `operational_expenses` sudah menyimpan snapshot kategori:
  - `category_code_snapshot`
  - `category_name_snapshot`
- Pembacaan expense table memakai snapshot, bukan master kategori terbaru.
- Perubahan kategori tidak lagi mengubah wajah histori expense lama.

### 2. Create expense flow sudah dibersihkan
- Create expense submit ke route resmi, bukan placeholder.
- Default tanggal create expense = hari ini.
- Teks misleading "batch berikutnya" sudah dihapus.
- `reference_no` sudah dikeluarkan dari jalur resmi expense karena tidak punya nilai operasional yang terbukti.

### 3. Expense table resmi sudah hidup
- Expense index sekarang memakai jalur resmi:
  - Request query
  - DTO
  - Use case
  - Reader port / reader adapter
  - Endpoint JSON
  - Shell UI + JS page
- Fitur minimum yang sudah hidup:
  - Search
  - Filter kategori
  - Filter tanggal
  - Sort
  - Paginate seragam

### 4. Category lifecycle resmi sudah hidup
Kategori expense sekarang bukan lagi status kosmetik. Jalur resmi yang sudah ada:
- Create
- Edit / Update
- Activate
- Deactivate

### 5. Audit mutation kategori sudah hidup
Mutasi kategori masuk audit event resmi:
- `expense_category_updated`
- `expense_category_activated`
- `expense_category_deactivated`

### 6. Category table resmi sudah hidup
Category index sudah dinaikkan ke pola resmi yang sama dengan expense/product table:
- Endpoint JSON
- Search
- Filter status
- Sort
- Paginate
- Shell UI + JS page
- Row action edit / aktifkan / nonaktifkan

### 7. Penyebab "tes hijau tapi live kosong" sudah ditemukan dan ditutup
Masalah live sebelumnya bukan pada query/filter logic dulu, tetapi pada asset JS page yang belum tersedia di `public/`:
- File sumber ada di:
  - `assets/static/js/pages/admin-expenses-table.js`
  - `assets/static/js/pages/admin-expense-categories-table.js`
- Tetapi sebelumnya belum ada di:
  - `public/assets/static/js/pages/admin-expenses-table.js`
  - `public/assets/static/js/pages/admin-expense-categories-table.js`
- **Dampak:**
  - Browser mendapat 404 untuk dua JS page itu.
  - Tabel/filter tidak pernah terinisialisasi walau backend dan tests hijau.
- **Status akhir:**
  - Kedua file sudah dipublish ke `public/assets/static/js/pages/`.
  - HTTP asset check berubah dari `404` menjadi `200 OK`.
  - Blocker runtime JS utama sudah ditutup.

### 8. Boundary presentation tetap aman
- Blade expense/category tetap bersih dari directive PHP liar.
- Logic presentational status dipindah ke jalur resmi data shaping.
- `make audit-blade` dan `make audit-lines` tetap aman di penutupan slice.

---

## Scope yang Selesai

### `[SCOPE-IN]`
- Snapshot kategori expense.
- Cleanup form expense.
- Remove `reference_no`.
- Expense table resmi.
- Category lifecycle audited.
- Category table resmi.
- HTTP/UI category lifecycle.
- Publish asset JS expense/category ke `public/`.
- Tests access/query/validation/store/update/activate/deactivate terkait expense & category.

### `[SCOPE-OUT]`
- Edit expense.
- Cancel expense.
- Attachment / bukti pembayaran expense.
- Reporting UI expense.
- Bulk actions category.
- Delete / archive category.
- Filter drawer lanjutan di luar scope minimum yang sudah hidup.

---

## Keputusan yang Dikunci `[DECISION]`
1. Histori expense memakai snapshot kategori, bukan master kategori terbaru.
2. Kategori expense adalah master data aktif dengan lifecycle resmi.
3. Audit wajib untuk mutasi kategori.
4. Expense table dan category table mengikuti kontrak table resmi repo.
5. `reference_no` dihapus dari jalur resmi expense.
6. Default tanggal create expense = hari ini.
7. Tidak ada happy path:
   - Mutation lewat use case.
   - HTTP flow lewat request/controller resmi.
   - Table interaktif lewat contract resmi `rows/meta`.
8. Asset JS page yang dipanggil Blade harus tersedia di `public/assets/static/js/pages/`, bukan cukup ada di `assets/static/js/pages/`.

---

## Bukti Verifikasi `[PROOF]`

### Server / Tests / Audit
Batch-batch berikut sudah dinyatakan oleh operator sebagai `clean`, `hijau`, `aman`, dan `pass`. Jalur yang sudah ditutup hijau meliputi:
- Snapshot kategori expense
- Cleanup form expense
- Expense table server contract
- Expense table shell UI + JS
- Category lifecycle server contract + audit
- Category lifecycle HTTP/UI
- Category table server contract
- Category table shell UI + JS
- Cleanup `reference_no`

### Runtime Asset Verification
Diverifikasi manual:
- Route table ada:
  - `admin.expenses.table`
  - `admin.expenses.categories.table`
- File sumber asset ada:
  - `assets/static/js/pages/admin-expenses-table.js`
  - `assets/static/js/pages/admin-expense-categories-table.js`
- File publik awalnya hilang:
  - `public/assets/static/js/pages/admin-expenses-table.js`
  - `public/assets/static/js/pages/admin-expense-categories-table.js`
- Setelah publish manual:
  - Kedua file ada di `public/assets/static/js/pages/`
  - HTTP HEAD untuk kedua asset = `200 OK`

---

## State Akhir Sistem

### Yang sekarang sudah hidup:
- Create expense
- Histori expense snapshot kategori
- Expense table resmi
- Category CRUD lifecycle resmi
- Category table resmi
- Audit mutation kategori
- Default tanggal expense = hari ini
- UI expense/category konsisten dengan pattern repo
- Asset JS expense/category tersedia di jalur publik yang dipanggil Blade

### Yang sekarang belum hidup:
- Edit expense
- Cancel expense
- Attachment proof expense
- Reporting UI expense
- Bulk action category
- Delete/archive category
- Filter lanjutan di luar minimum saat ini
- Mekanisme publish asset JS ke `public/` yang otomatis/terstandar di workflow repo

---

## Register Masalah Final

| No | Masalah | Status |
|:---|:---|---:|
| 1 | Lifecycle kategori expense + histori | 100% |
| 2 | Status kategori tanpa flow resmi | 100% |
| 3 | Tulisan kecil batch berikutnya | 100% |
| 4 | Submit operational expense | 100% |
| 5 | Default tanggal = today | 100% |
| 6 | `reference_no` tidak jelas | 100% |
| 7 | Search/filter kategori/tgl | 100% |
| 8 | Table interaktif JS | 100% |
| 9 | Paginate seragam | 100% |

### Catatan Penting
Walau isu fungsional modul expense/category ditutup 100%, ada **catatan operasional repo** yang perlu diingat:
- Jika menambah JS page baru di pola `asset('assets/static/js/pages/...')`, pastikan file itu juga dipublish ke: `public/assets/static/js/pages/...`
- Tanpa itu, tests PHP bisa tetap hijau tetapi runtime browser bisa gagal.

---

## Risiko Tersisa / Next Slice yang Disarankan
Slice ini layak dianggap **CLOSED**. Slice berikut yang paling masuk akal bila ingin lanjut:
1. Standarisasi workflow publish asset JS ke `public/`
2. Edit/cancel expense
3. Attachment / bukti pembayaran expense
4. Reporting UI expense
5. Refactor konsistensi modul lain agar runtime asset gap seperti ini tidak terulang

---
