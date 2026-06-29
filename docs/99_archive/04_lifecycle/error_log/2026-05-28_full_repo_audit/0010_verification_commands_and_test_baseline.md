# ERROR LOG 0010 - VERIFICATION COMMANDS AND TEST BASELINE

## FACT
- Baseline runtime dan static scan untuk audit full-repo ini sudah punya proof owner yang cukup untuk indeks verifikasi.
- Sumber proof yang dipakai di dokumen ini hanya output command owner yang sudah diberikan.
- Dokumen ini adalah ringkasan baseline verifikasi, bukan patch plan dan bukan implementasi.

## COMMANDS PROVIDED BY OWNER
1. `git status -sb`
   - Output: `## main...origin/main`
2. `git rev-parse --short HEAD`
   - Output: `266af29a`
3. `rg -n "TINYINT|GENERATED ALWAYS|SHOW INDEX|AFTER delete_reason" database/migrations`
4. `rg -n "createActorAccessOnly|role' => 'user'|Role::KASIR|const KASIR" database/seeders app/Core app/Adapters/Out/IdentityAccess`
5. `rg -n "Route::middleware\\(\\['web', 'transaction.entry'\\]|transaction.entry" routes bootstrap/app.php app/Adapters/In/Http/Middleware`
6. `rg -n "audit_logs|audit_events|audit_outbox|AuditLogPort|AuditEventWriterPort" app database/migrations`
7. `rg -n "function applyMovements|increase\\(\\$m->qtyDelta\\)|stock_out|qty_delta" app/Application/Inventory app/Core/Inventory tests/Feature/Inventory`
8. `rg -n 'innerHTML|insertAdjacentHTML' public/assets/static/js -g '*.js'`
9. `rg -n '<script|@php|\\{!!' resources/views -g '*.blade.php'`
10. `php artisan route:list`
    - Output summary: `Showing [154] routes`
11. `php artisan migrate:status`
    - Output summary: listed migrations shown `Ran`
12. `php artisan test --filter=WebPageAccessFeatureTest`
    - Output: `PASS, 8 passed, 20 assertions, Duration 6.91s`
13. `php artisan test --filter=MobileApiAuthenticationFeatureTest`
    - Output: `PASS, 7 passed, 25 assertions, Duration 6.83s`
14. `php artisan test --filter=RebuildInventoryProjectionFeatureTest`
    - Output: `PASS, 2 passed, 9 assertions, Duration 6.55s`
15. `composer test`
    - Output: `config cache cleared, 2 skipped, 1112 passed, 6205 assertions, Duration 55.07s`

## PASSING TEST BASELINE
- `php artisan test --filter=WebPageAccessFeatureTest` lulus dengan `8 passed, 20 assertions`.
- `php artisan test --filter=MobileApiAuthenticationFeatureTest` lulus dengan `7 passed, 25 assertions`.
- `php artisan test --filter=RebuildInventoryProjectionFeatureTest` lulus dengan `2 passed, 9 assertions`.
- `composer test` lulus dengan `2 skipped, 1112 passed, 6205 assertions`.
- Interpretasi baseline runtime: hijau.

## STATIC SCAN BASELINE
- Owner menyediakan hasil scan `rg` untuk pola PostgreSQL migration, seeder role, `transaction.entry`, audit path, inventory projection, dan JS/Blade boundary.
- Owner juga menyediakan scan `rg` untuk `innerHTML`/`insertAdjacentHTML` di `public/assets/static/js` serta `<script`/`@php`/`{!!` di `resources/views`.
- Interpretasi baseline static scan: scan JS/Blade sudah berhasil dan menunjukkan pemakaian luas `innerHTML` serta inline script.

## MIGRATION BASELINE
- `php artisan migrate:status` menunjukkan listed migrations berstatus `Ran`.
- Interpretasi baseline migrasi: database migrasi lokal jalan.
- PostgreSQL compatibility tetap belum terbukti dari baseline ini saja, karena tidak ada proof `APP_ENV=testing DB_CONNECTION=pgsql php artisan migrate:fresh --force` pada output owner yang tersedia.

## ROUTE BASELINE
- `php artisan route:list` menunjukkan `Showing [154] routes`.
- Interpretasi baseline route: surface aplikasi sudah terinventarisasi dan route count stabil untuk audit ini.
- Proof route security khusus `transaction.entry` tetap belum lengkap di baseline ini; yang sudah ada hanya baseline akses dashboard dari test `WebPageAccessFeatureTest`.

## INTERPRETATION
- Runtime baseline is green.
- Current DB migration status is `Ran`.
- PostgreSQL compatibility remains unproven until `pgsql migrate:fresh` dijalankan dan dilaporkan sebagai proof owner.
- JS/Blade scan sekarang sudah berhasil dan menunjukkan pemakaian luas `innerHTML` serta inline script.
- Audit dual path tetap source-confirmed dari scan owner pada audit path.
- Seeder role mismatch tetap source-confirmed dari scan owner pada seeder/identity access path.
- Route-specific `transaction.entry` security proof masih gap di luar dashboard access tests.

## GAP
- Belum ada proof owner untuk `APP_ENV=testing DB_CONNECTION=pgsql php artisan migrate:fresh --force`.
- Belum ada proof owner yang menutup seluruh boundary `transaction.entry` secara route-specific beyond dashboard access tests.
- Dokumen ini tidak memuat detail hasil setiap `rg`; hanya ringkasan dan command yang memang diberikan owner.

## FOLLOW-UP COMMANDS
- `APP_ENV=testing DB_CONNECTION=pgsql php artisan migrate:fresh --force`
- `php artisan test --filter=TransactionEntry`
- `php artisan test --filter=MobileApiSupplierInvoiceReadFeatureTest`
- `php artisan test --filter=MobileApiSupplierPaymentProofFeatureTest`
- `php artisan test --filter=Inventory`

## FINAL STATUS
- Baseline verifikasi full-repo audit: hijau untuk runtime, migrasi lokal, route count, dan suite utama.
- Gap tetap ada untuk PostgreSQL compatibility dan route-specific `transaction.entry` proof.
- Dokumen ini hanya indeks baseline verifikasi owner, bukan keputusan implementasi.
