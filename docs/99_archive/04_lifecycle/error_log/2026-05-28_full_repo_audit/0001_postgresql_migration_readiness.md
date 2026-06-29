# ERROR LOG 0001 - POSTGRESQL MIGRATION READINESS

## FACT
- Laporan ini adalah analisis readiness untuk migrasi PostgreSQL, bukan patch, bukan refactor, dan bukan klaim cutover.
- Source code dan command output mengalahkan narasi dokumen jika ada konflik.
- Blueprint `docs/03_blueprints/db/0013_go_postgres_migration_readiness_stage_0.md` menyebut stage ini sebagai discovery baseline, bukan final migration plan.
- ADR `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md` menyatakan PostgreSQL implementation masih out of scope sampai project migrasi terpisah dibuka.
- Dua migrasi product dan dua migrasi supplier_invoice_lines yang diperiksa menunjukkan pola DDL / index lookup yang relevan untuk readiness PostgreSQL.
- `php artisan migrate:status` adalah baseline kesehatan migrasi lokal, tetapi bukan proof PostgreSQL compatibility.

## OWNER PROOF
- Owner scan command: `rg -n "TINYINT|GENERATED ALWAYS|SHOW INDEX|AFTER delete_reason" database/migrations`
- Owner scan output membuktikan:
  - `database/migrations/2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php`: `SHOW INDEX`
  - `database/migrations/2026_04_18_000100_alter_supplier_invoice_lines_for_revisioned_post_receipt_edit.php`: `SHOW INDEX`
  - `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php`: `TINYINT(1)`, `GENERATED ALWAYS`, `AFTER delete_reason`, `SHOW INDEX`
  - `database/migrations/2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php`: `SHOW INDEX`

## SOURCE EVIDENCE
- `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php:17-28` menambahkan `active_unique_marker` dengan raw `DB::statement`, memakai `TINYINT(1)`, `GENERATED ALWAYS AS (...) STORED`, dan `AFTER delete_reason`.
- `database/migrations/2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php:79-88` memakai `SHOW INDEX FROM \`products\`` di helper `hasIndex()`.
- `database/migrations/2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php:70-79` juga memakai `SHOW INDEX FROM \`products\`` untuk cek index sebelum rename / recreate.
- `database/migrations/2026_04_18_000100_alter_supplier_invoice_lines_for_revisioned_post_receipt_edit.php:141-150` memakai `SHOW INDEX FROM \`supplier_invoice_lines\`` untuk cek eksistensi index.
- `database/migrations/2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php:51-60` memakai `SHOW INDEX FROM \`supplier_invoice_lines\`` untuk cek index sebelum create unique.
- ADR `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md` menegaskan:
  - schema dan contract baru harus MySQL-compatible dan PostgreSQL-ready
  - MySQL-specific shortcuts adalah trap migrasi
  - PostgreSQL belum boleh dianggap aktif sebelum proof terpisah ada
- Blueprint `docs/03_blueprints/db/0012_postgresql_go_api_transition_audit.md` menegaskan jalur aman adalah compatibility harness dulu, lalu test suite PostgreSQL, lalu read-only Go API, dan write migration hanya setelah parity, transaction, lock, idempotency, dan audit proof ada.
- Blueprint `docs/03_blueprints/db/0013_go_postgres_migration_readiness_stage_0.md` secara eksplisit menyebut `->after()`, `->change()`, `unsigned*`, dan helper MySQL-oriented lain sebagai compatibility risk yang harus diuji terhadap PostgreSQL runtime.

## FINDINGS
- CONFIRMED: migrasi `2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php` memuat DDL MySQL-specific untuk generated column, yaitu `TINYINT(1)`, `GENERATED ALWAYS`, dan placement `AFTER delete_reason`. Itu adalah risiko/blocker readiness karena bukan portable PostgreSQL shape bila dipindahkan apa adanya.
- CONFIRMED: tiga migrasi yang diperiksa bergantung pada `SHOW INDEX` untuk branch logic schema. Itu adalah MySQL-specific operational pattern yang menjadi blocker/risk readiness PostgreSQL sampai ada pembuktian alternatif yang kompatibel.
- CONFIRMED: blueprint migrasi menempatkan PostgreSQL compatibility harness dan runtime migration test sebagai prasyarat, bukan asumsi.
- GAP: belum ada proof bahwa `APP_ENV=testing DB_CONNECTION=pgsql php artisan migrate:fresh --force` berhasil.
- NOTE: jika `php artisan migrate:status` menunjukkan `Ran`, itu hanya membuktikan baseline migrasi pada database aktif saat ini jalan, bukan proof bahwa migrasi pada PostgreSQL kompatibel.
- SUSPECTED: masih ada area migrasi lain di luar empat file yang wajib diperiksa yang mungkin memakai helper / SQL pattern yang juga tidak portable, tetapi itu belum dibuktikan oleh proof wajib di laporan ini.

## IMPACT
- Risiko utama ada pada cutover atau shadow migration ke PostgreSQL: DDL ini dapat gagal saat `migrate:fresh` berjalan pada PostgreSQL atau saat schema diff / replay dijalankan.
- `active_unique_marker` adalah contoh konkret dari skema yang memanfaatkan fitur MySQL-style generated column dan index existence check, sehingga perlu pembuktian vendor-aware sebelum readiness bisa dinaikkan.
- Supplier invoice line revision logic memakai index existence checks; jika perilaku metadata index berbeda pada PostgreSQL, migrasi bisa berhenti di layer schema sebelum test domain berjalan.
- Karena ADR 0028 memosisikan PostgreSQL-ready contract sebagai syarat, temuan ini mempengaruhi kesiapan migration path, bukan hanya implementasi file tunggal.

## GAP
- Belum ada runtime proof dengan `APP_ENV=testing DB_CONNECTION=pgsql php artisan migrate:fresh --force`.
- Belum ada proof bahwa seluruh migration slice yang relevan lulus pada PostgreSQL tanpa manual intervention.
- Belum ada proof parity untuk schema/index behavior pada PostgreSQL untuk file-file ini.
- Belum ada proof bahwa pengganti portable untuk generated column dan index existence lookup sudah disahkan oleh ADR atau blueprint lanjutan.

## CLASSIFICATION
- CONFIRMED
  - MySQL-specific generated column syntax pada `2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php`.
  - `SHOW INDEX` usage pada empat migrasi yang diperiksa.
  - Blueprint dan ADR yang menempatkan PostgreSQL readiness sebagai project terpisah dengan proof runtime.
- SUSPECTED
  - Ada migrasi lain di luar empat file wajib yang mungkin membawa pola serupa, tetapi belum dibuktikan di laporan ini.
  - Ada potensi compatibility issue tambahan pada helper schema portability yang belum tersentuh oleh proof ini.
- GAP
  - Tidak ada proof `APP_ENV=testing DB_CONNECTION=pgsql php artisan migrate:fresh --force`.
  - Tidak ada proof runtime PostgreSQL yang menunjukkan schema migration benar-benar jalan end-to-end.

## SOLUTION DIRECTION, NO IMPLEMENTATION
- Pertahankan Laravel/MySQL sebagai source of truth sampai proof PostgreSQL runtime ada.
- Pecah readiness menjadi dua lapis: schema compatibility harness dan runtime migration proof.
- Buat jalur portable untuk DDL yang saat ini bergantung pada MySQL-specific generated column atau metadata index lookup.
- Validasi ulang seluruh migration slice yang menyentuh `products` dan `supplier_invoice_lines` pada PostgreSQL test database.
- Jadikan hasil `migrate:fresh` PostgreSQL sebagai gate sebelum ada klaim readiness lanjutan.

## SUGGESTED NEXT PROOF
- Jalankan runtime migration proof pada PostgreSQL test database.
- Baca ulang output `migrate:fresh` untuk memastikan tidak ada DDL error, metadata error, atau vendor-specific failure.
- Setelah itu, jalankan subset test yang menyentuh schema / model / projection yang bergantung pada migrasi ini.
- Jika runtime proof gagal, catat failure mode exact, file, dan SQL fragment yang memicunya.

## MINIMUM OWNER COMMANDS
```bash
rg -n "TINYINT|GENERATED ALWAYS|SHOW INDEX|AFTER delete_reason" database/migrations
php artisan migrate:status
APP_ENV=testing DB_CONNECTION=pgsql php artisan migrate:fresh --force
```

## FINAL STATUS
- Status: GAP
- Verdict: PostgreSQL migration readiness belum terbukti.
- Owner-facing summary: ada blocker/risk yang sudah CONFIRMED dari source, tetapi runtime PostgreSQL compatibility belum dibuktikan dengan `migrate:fresh` pada `DB_CONNECTION=pgsql`.
