# 034 - Product lookup fetches unbounded product rows and performs per-row inventory reads

Status: Strict Fixed
Keparahan: Medium
Klasifikasi: performance / scalability / lookup consistency / hexagonal discipline

## Ringkasan

Product lookup cashier, mobile, dan procurement sekarang memakai dedicated lookup port yang bounded di query database.

Sebelumnya jalur lookup mengambil product search tanpa query-level limit, lalu beberapa jalur membaca inventory per product row. Mobile memang memotong response menjadi 20 row, tetapi pemotongan terjadi setelah product search mengembalikan array penuh.

Patch menutup masalah runtime lookup tanpa menjadikan Laravel API sebagai fokus produk baru. Mobile endpoint ikut dirapikan karena endpoint itu sudah menjadi boundary sistem yang ada.

## Strict-Fixed-Scope

Scope yang ditutup:

- cashier product lookup hanya mengambil product in-stock dengan cap query-level;
- mobile product search mengambil product dengan cap query-level;
- procurement product lookup ikut memakai bounded lookup agar contract lookup product seragam;
- inventory availability dibaca melalui join lookup, bukan N+1 per-row inventory read;
- controller/handler tidak melakukan query shortcut langsung ke database.

Out of scope untuk log ini:

- mengubah arah produk menjadi Laravel API-first;
- migrasi Go/PostgreSQL;
- menghapus seluruh generic `ProductReaderPort::search()`;
- mengubah response contract product lookup yang sudah dipakai UI/mobile.

## Root cause

Contract product search lama mengembalikan array penuh dan tidak membawa limit eksplisit.

Stock availability juga difilter di application loop dengan per-row inventory read, bukan di query lookup yang bisa dibatasi dan dijalankan sekali.

## Source Reality Setelah Patch

`app/Ports/Out/ProductCatalog/ProductLookupReaderPort.php`

- port lookup khusus dengan `DEFAULT_LIMIT = 20` dan `MAX_LIMIT = 50`;
- method `search(string $query, int $limit = self::DEFAULT_LIMIT, bool $onlyInStock = false): array`.

`app/Adapters/Out/ProductCatalog/DatabaseProductLookupReaderAdapter.php`

- implementasi database berada di adapter out;
- memakai `products` left join `product_inventory`;
- memilih `COALESCE(product_inventory.qty_on_hand, 0) as available_stock`;
- memakai qualified columns untuk mencegah ambiguity;
- membatasi hasil dengan bounded `limit`;
- filter `onlyInStock` dilakukan di query.

`app/Application/Note/Services/CashierNoteProductLookupData.php`

- cashier lookup memakai `ProductLookupReaderPort`;
- `searchAvailableProducts()` meminta product in-stock dari port;
- tidak lagi membaca inventory per product row.

`app/Application/MobileApi/Product/UseCases/SearchMobileApiProductsHandler.php`

- mobile search meminta limit ke lookup port;
- tidak lagi memakai `array_slice()` setelah fetch penuh;
- tidak lagi membaca inventory per product row.

`app/Application/Procurement/Services/ProcurementProductLookupData.php`

- procurement lookup ikut memakai `ProductLookupReaderPort` agar lookup product lintas channel seragam.

## Hexagonal Discipline

Patch menjaga boundary:

- DB access product lookup hanya berada di out adapter;
- application service/use case hanya bergantung pada port;
- HTTP controller hanya mapping DTO ke public response;
- tidak ada controller yang mengambil jalan pintas dengan query database langsung untuk lookup ini.

Ini selaras dengan baseline hexagonal: transport/UI tidak menjadi tempat keputusan persistence atau source-of-truth.

## PostgreSQL Readiness

Query lookup memakai Laravel query builder dengan SQL umum:

- `LEFT JOIN`;
- `COALESCE`;
- `LIKE`;
- `LIMIT`;
- qualified column names.

Tidak ada syntax MySQL-only baru di patch ini. Catatan migrasi: perilaku case-sensitivity `LIKE` dapat berbeda antara MySQL collation dan PostgreSQL; saat migrasi Go/PostgreSQL, contract search perlu diputuskan eksplisit apakah memakai `LIKE`, `ILIKE`, atau normalized search column.

## RED Proof

Command:

```bash
php artisan test tests/Feature/Note/ProductLookupPerformanceFeatureTest.php tests/Feature/MobileApi/Product/MobileApiProductSearchFeatureTest.php tests/Feature/Procurement/ProductLookupFeatureTest.php
```

Hasil sebelum production patch:

- exit code `1`;
- `4 failed, 9 passed, 44 assertions`;
- cashier lookup mengembalikan 25/30 row, bukan 20;
- mobile lookup query count `24`, melewati batas `<= 8`;
- procurement lookup mengembalikan 25 row, bukan 20.

## GREEN Proof

Targeted command yang sama setelah patch:

- `PASS`;
- `13 passed, 50 assertions`.

Focused blast-radius command:

```bash
php artisan test tests/Feature/Note/ProductLookupPerformanceFeatureTest.php tests/Feature/MobileApi/Product tests/Feature/Procurement/ProductLookupFeatureTest.php tests/Feature/ProductCatalog
```

Hasil:

- `PASS`;
- `84 passed, 324 assertions`.

Full verification command:

```bash
make verify
```

Hasil:

- PHPStan `1798/1798`, `[OK] No errors`;
- line-count audit passed;
- Blade audit passed;
- contract audit passed;
- Pest `1179 passed, 6670 assertions`;
- exit code `0`.

## Negative Search

Search terhadap jalur lama menunjukkan runtime lookup tidak lagi memakai:

- `getInventoryByProductId` pada product lookup loop;
- `array_slice($this->lookupData->searchProducts(...), 0, $limit)` pada mobile product search;
- DB query shortcut di controller lookup.

Search terhadap port/adapter menunjukkan lookup DB access berada pada:

- `ProductLookupReaderPort`;
- `DatabaseProductLookupReaderAdapter`;
- binding `ProductCatalogServiceProvider`;
- application lookup services.

## Remaining Gap

`ProductReaderPort::search()` generic masih ada dan belum diubah menjadi bounded contract.

Gap ini tidak membuka kembali 0034 karena cashier/mobile/procurement runtime lookup sudah dipindahkan ke dedicated bounded lookup port. Jika nanti ada consumer baru yang memakai generic product search untuk lookup besar, contract generic reader perlu diaudit atau didepresiasi.

## Kesimpulan

0034 ditutup sebagai `Strict Fixed`.

Masalah utama sudah ditutup di level sistem: lookup product bounded di query, inventory availability tidak lagi N+1, jalur lookup tetap lewat port/out adapter, dan patch tidak menambah ketergantungan MySQL-only.
