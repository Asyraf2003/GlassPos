# Handoff — Product UI Interactive Table (Admin)

## Metadata
- Tanggal: 2026-03-19
- Nama slice / topik: Product UI Interactive Table (Admin)
- Workflow step: Turunan Step 4 Product Catalog, dipakai sebagai fondasi UI sebelum Supplier UI
- Status: CLOSED
- Progres:
  - Product CRUD web admin: 100%
  - Product interactive table admin: 100%
  - Slice Product UI admin: CLOSED
  - Target berikutnya: Supplier UI dengan pola read-side dan page shell yang seragam

## Target halaman kerja
Menutup slice UI admin untuk Product agar halaman berikutnya bisa lanjut ke Supplier UI tanpa membuka ulang keputusan dasar Product, pola folder, pola route, pola read-side JSON, aturan line-limit, dan pola interactive table.

Target yang dicapai pada slice ini:
- admin product index page hidup
- admin product create page hidup
- admin product edit/update page hidup
- interactive product table hidup
- live search native JS hidup
- server-side sort/filter/pagination hidup
- filter drawer kanan hidup
- URL state sync hidup
- stok tabel product dibaca dari projection inventory resmi, bukan dari product master

## Referensi yang dipakai `[REF]`
- Blueprint:
  - `docs/blueprint/blueprint_v1.md`
- Workflow:
  - `docs/workflow/workflow_v1.md`
- DoD:
  - `docs/dod/dod_v1.md`
- ADR:
  - `docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
- Handoff sebelumnya:
  - `docs/handoff/handoff_step_04.md`
  - `docs/handoff/handoff_auth_ui_access_slice.md`
- Snapshot repo / output command yang dipakai di halaman ini:
  - `sed -n ... routes/web/product_catalog.php`
  - `sed -n ... app/Providers/HexagonalServiceProvider.php`
  - `sed -n ... app/Adapters/In/Http/Controllers/Admin/Product/*`
  - `sed -n ... app/Adapters/In/Http/Requests/ProductCatalog/*`
  - `sed -n ... app/Adapters/Out/ProductCatalog/*`
  - `sed -n ... app/Application/ProductCatalog/*`
  - `sed -n ... resources/views/admin/products/*`
  - `sed -n ... public/assets/static/js/pages/admin-products-table.js`
  - `php artisan test tests/Feature/ProductCatalog/ProductIndexPageFeatureTest.php`
  - `php artisan test tests/Feature/ProductCatalog/ProductTableDataAccessFeatureTest.php`
  - `php artisan test tests/Feature/ProductCatalog/ProductTableDataQueryFeatureTest.php`
  - `php artisan test tests/Feature/ProductCatalog/ProductTableDataValidationFeatureTest.php`
  - `make audit-lines`
  - `make lint`

## Fakta terkunci `[FACT]`
- Product master tetap terpisah dari stok/jumlah. Kolom jumlah pada table Product tidak boleh diambil dari entity master product.
- Kolom jumlah yang dipakai pada interactive table adalah `stok_saat_ini`, dibaca dari projection `product_inventory.qty_on_hand`.
- Interactive table Product memakai route HTML page terpisah dari route JSON data:
  - `admin.products.index` untuk shell halaman
  - `admin.products.table` untuk JSON read-side
- Read-side interactive table dipisah dari CRUD reader lama melalui port dan adapter baru, sehingga `ProductReaderPort` lama tidak menjadi tempat semua concern list/filter/sort/paginate.
- Search, sort, filter, dan pagination pada interactive table bersifat server-side; JS hanya menjadi adapter UI.
- Search native JS sekarang aktif otomatis mulai 2 huruf dengan debounce.
- State search/filter/sort/page sudah disinkronkan ke URL query string.
- Page size V1 dikunci `10`.
- Sort whitelist V1 dikunci ke:
  - `nama_barang`
  - `merek`
  - `ukuran`
  - `harga_jual`
  - `stok_saat_ini`
- Filter V1 dikunci ke:
  - `q`
  - `merek`
  - `ukuran_min`
  - `ukuran_max`
  - `harga_min`
  - `harga_max`
- Filter drawer kanan hanyalah UI adapter; logic filter tetap di backend request + reader.
- Semua file yang berpotensi melewati 100 baris harus dipecah lebih awal ke concern/helper/partial yang jelas, bukan ditambal dengan bypass line-limit bila masih bisa dipisah rapi.

## Scope yang dipakai
### `[SCOPE-IN]`
- admin product index page
- admin product create page
- admin product edit/update page
- route admin product page
- route JSON admin product table
- read-side JSON khusus interactive table
- filter, sort, search, pagination server-side
- live search native JS + sort indicator + URL state sync
- filter drawer kanan
- penggunaan projection inventory untuk `stok_saat_ini`
- feature tests untuk access/query/validation interactive table
- audit hygiene line-limit dan lint

### `[SCOPE-OUT]`
- Supplier UI
- supplier invoice / supplier receipt UI
- ready-to-sell / reserved / dipakai semantics
- full jQuery DataTable backend protocol
- browser automation / Dusk / Playwright
- reusable shared JS table framework lintas modul
- export/import
- perubahan blueprint
- ADR baru khusus UI table

## Keputusan yang dikunci `[DECISION]`
- UI mode untuk table admin Product adalah **Mazer table + native jQuery/fetch custom**, bukan full jQuery DataTable plugin.
- Product interactive table memakai **page shell + endpoint JSON terpisah**. HTML page tidak lagi merender semua row product secara server-render.
- Route HTML dan route data dipisah:
  - HTML: `GET /admin/products`
  - JSON: `GET /admin/products/table`
- Controller page admin tetap berada di namespace area:
  - `app/Adapters/In/Http/Controllers/Admin/Product/`
- Request read-side tetap berada di bounded context ProductCatalog:
  - `app/Adapters/In/Http/Requests/ProductCatalog/`
- Read-side interactive table memakai kontrak terpisah:
  - `ProductTableQuery`
  - `ProductTableReaderPort`
  - `DatabaseProductTableReaderAdapter`
  - `GetProductTableHandler`
- Interactive table tidak reuse reporting reader inventory; join ke `product_inventory` dilakukan di adapter read khusus table admin Product.
- Kolom jumlah yang ditampilkan pada Product UI V1 adalah **`stok_saat_ini`** dari `qty_on_hand` projection, bukan istilah lain.
- Page size default dan satu-satunya yang diizinkan pada V1 adalah `10`.
- Search native JS dimulai saat input memiliki panjang minimal 2 huruf; input kosong me-reload list normal.
- Header kolom sortable memakai klik toggle `asc/desc` dan indikator visual `↑`, `↓`, `↕`.
- Supplier UI berikutnya harus meniru pola serupa bila membutuhkan interactive table:
  - shell page tipis
  - JSON read endpoint khusus
  - whitelist filter/sort
  - query contract jelas
  - JS adapter tipis

## File yang dibuat/diubah `[FILES]`

### File baru
- `app/Adapters/In/Http/Controllers/Admin/Product/ProductIndexPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/StoreProductController.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/EditProductPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/UpdateProductController.php`
- `app/Adapters/In/Http/Controllers/Admin/Product/ProductTableDataController.php`
- `app/Adapters/In/Http/Requests/ProductCatalog/ProductTableQueryRequest.php`
- `app/Application/ProductCatalog/DTO/ProductTableQuery.php`
- `app/Application/ProductCatalog/UseCases/GetProductTableHandler.php`
- `app/Ports/Out/ProductCatalog/ProductTableReaderPort.php`
- `app/Adapters/Out/ProductCatalog/DatabaseProductTableReaderAdapter.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductTableBaseQuery.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductTableFilters.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductTableOrdering.php`
- `app/Adapters/Out/ProductCatalog/Concerns/ProductTablePayload.php`
- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/products/create.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/admin/products/partials/filter_drawer.blade.php`
- `public/assets/static/js/pages/admin-products-table.js`
- `tests/Feature/ProductCatalog/ProductIndexPageFeatureTest.php`
- `tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php`
- `tests/Feature/ProductCatalog/ProductEditPageFeatureTest.php`
- `tests/Feature/ProductCatalog/ProductTableDataAccessFeatureTest.php`
- `tests/Feature/ProductCatalog/ProductTableDataQueryFeatureTest.php`
- `tests/Feature/ProductCatalog/ProductTableDataValidationFeatureTest.php`

### File diubah
- `routes/web/product_catalog.php`
- `app/Providers/HexagonalServiceProvider.php`
- `resources/views/layouts/partials/sidebar-admin.blade.php`
- `app/Ports/Out/ProductCatalog/ProductReaderPort.php`
- `app/Adapters/Out/ProductCatalog/DatabaseProductReaderAdapter.php`

## Bukti verifikasi `[PROOF]`
- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductIndexPageFeatureTest.php`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductCreatePageFeatureTest.php`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductEditPageFeatureTest.php`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductTableDataAccessFeatureTest.php`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductTableDataQueryFeatureTest.php`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - `php artisan test tests/Feature/ProductCatalog/ProductTableDataValidationFeatureTest.php`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - `make audit-lines`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - `make lint`
  - hasil:
    - PASS pada halaman kerja ini
- command:
  - verifikasi manual browser untuk `/admin/products`
  - hasil:
    - live search mulai 2 huruf berjalan
    - sort toggle berjalan
    - filter drawer kanan berjalan
    - pagination berjalan
    - URL state sync berjalan

## Blocker aktif `[BLOCKER]`
- tidak ada blocker aktif

## State repo yang penting untuk langkah berikutnya
- `Product Catalog` tetap punya boundary lama dari Step 4: product master valid lebih dulu, supplier tidak boleh menciptakan product baru implisit.
- Untuk interactive admin table, pola final yang sudah dipilih adalah **page shell tipis + endpoint JSON khusus + JS custom**.
- Jika halaman berikutnya (Supplier UI) butuh tabel interaktif, jangan mulai dari plugin DataTable penuh. Mulai dari contract read-side dulu.
- Kolom stok di UI Product tidak lahir dari entity product master; ia lahir dari projection inventory. Supplier UI tidak boleh mengaburkan fakta ini.
- Namespace/folder yang sekarang seragam untuk slice Product UI:
  - page/web controllers: `app/Adapters/In/Http/Controllers/Admin/Product/`
  - request HTTP: `app/Adapters/In/Http/Requests/ProductCatalog/`
  - application DTO/use case read-side: `app/Application/ProductCatalog/`
  - port read-side table: `app/Ports/Out/ProductCatalog/`
  - adapter DB read-side table: `app/Adapters/Out/ProductCatalog/`
  - concern pemecah line-limit: `app/Adapters/Out/ProductCatalog/Concerns/`
  - blade page: `resources/views/admin/products/`
  - blade partial khusus page: `resources/views/admin/products/partials/`
  - JS page: `public/assets/static/js/pages/`
  - tests feature page/data: `tests/Feature/ProductCatalog/`
- Untuk file yang mulai membesar, pecah lebih awal. Jangan tunggu gagal `audit-lines` baru dipisah.
- Untuk table V1, page size dikunci 10. Kalau Supplier nanti butuh 25/50, itu harus dikunci eksplisit lewat request whitelist dan reader contract.
- Untuk URL/query-driven interactive table, state yang harus konsisten adalah:
  - `q`
  - `page`
  - `sort_by`
  - `sort_dir`
  - filter fields yang dipilih

## Next step paling aman `[NEXT]`
- Buka Supplier UI discovery dengan pola yang sama:
  1. identifikasi boundary data supplier vs procurement vs inventory receiving
  2. tentukan apakah Supplier UI butuh shell page biasa atau interactive table
  3. kalau butuh interactive table, buat read contract terpisah dulu
  4. jangan bawa ulang keputusan Product master/stok yang sudah dikunci

## Catatan masuk halaman berikutnya
Saat membuka halaman kerja berikutnya, bawa minimal:
- file handoff ini
- `docs/setting_control/first_in.md`
- `docs/setting_control/ai_contract.md`
- `docs/blueprint/blueprint_v1.md`
- `docs/workflow/workflow_v1.md`
- `docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
- `docs/handoff/handoff_step_04.md`
- snapshot repo terbaru area supplier/procurement
- output test/audit terakhir dari halaman ini bila Supplier UI ingin meniru pola table Product

## Ringkasan singkat siap tempel
### Ringkasan
- target: menutup slice Product UI admin sampai interactive table hidup dan siap dijadikan pola untuk halaman berikutnya
- status: CLOSED
- progres: Product UI admin 100%
- hasil utama:
  - CRUD web Product admin hidup
  - interactive table Product admin hidup
  - search native 2 huruf + debounce hidup
  - sort/filter/pagination server-side hidup
  - URL state sync hidup
  - stok tabel product dibaca dari projection inventory resmi
  - pola folder, route, request, use case, reader, blade, JS, dan test sudah seragam
- next step: masuk ke Supplier UI discovery dan ulang pola read-side/page shell yang sama bila Supplier butuh table interaktif

### Jangan dibuka ulang
- kontrak Product master yang sudah dikunci pada Step 4
- stok/jumlah bukan bagian product master
- interactive table Product tidak memakai full DataTable backend protocol
- kolom jumlah Product UI V1 = `stok_saat_ini` dari projection inventory
- page shell dan data endpoint dipisah

### Data minimum bila ingin lanjut
- handoff ini
- `docs/handoff/handoff_step_04.md`
- `docs/adr/0012-product-master-must-exist-before-supplier-receipt.md`
- snapshot repo terbaru area supplier/procurement
- file Product UI interaktif sebagai referensi pola folder/route/request/JS/test
