# 029 - Halaman create kasir membocorkan total jumlah nota

Status: Fixed with proof
Keparahan: Medium
Klasifikasi: file error-log unik baru
Commit introduksi: 69cf998
Status patch: fixed and locally verified for cashier create workspace neutral default customer label

## Ringkasan

Halaman create transaction workspace kasir sebelumnya membocorkan total jumlah nota global melalui default customer name yang dihasilkan.

`CreateTransactionWorkspacePageController` memanggil `CreateTransactionWorkspacePageDataBuilder::build()` dan membaca `defaultCustomerName`. Sebelum patch, builder membuat nilai tersebut sebagai `Pelanggan no ` ditambah `NoteReaderPort::countAll() + 1`.

Adapter note reader produksi mengimplementasikan `countAll()` sebagai hitung tanpa scope atas seluruh tabel `notes`. Nilai itu kemudian dirender ke halaman create workspace yang terlihat oleh kasir sebagai default customer name atau placeholder, dan juga tersedia melalui data konfigurasi halaman.

Browsing nota kasir di tempat lain dibatasi oleh window tanggal. Karena itu, mengekspos total jumlah nota global melewati batas visibilitas kasir yang seharusnya dan membocorkan metadata volume bisnis.

Patch minimum mengganti default customer name visible di halaman create kasir menjadi label netral statis `Pelanggan baru`, sehingga halaman create tidak lagi bergantung pada global count nota.

## Kenapa ini file baru

Ini bukan masalah yang sama dengan laporan historical closed note disclosure. Laporan tersebut mengekspos baris nota historis melalui perilaku browsing yang dapat diakses kasir.

Masalah ini mengekspos volume nota global berbentuk aggregate melalui default customer label di halaman create kasir. Nilai yang bocor bukan baris nota, tetapi tetap membocorkan metadata volume bisnis di luar visibilitas date-windowed normal kasir.

## File terdampak

Production:

- `app/Application/Note/Services/CreateTransactionWorkspacePageDataBuilder.php`

Regression test:

- `tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php`

Context/source inspected but not changed for this patch:

- `app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspacePageController.php`
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Adapters/Out/Note/Queries/CashierNoteHistoryBaseQuery.php`
- `resources/views/cashier/notes/workspace/partials/info-card.blade.php`
- `app/Ports/Out/Note/NoteReaderPort.php`

## Bukti awal

`CreateTransactionWorkspacePageController` memanggil page data builder dan memakai `defaultCustomerName` ketika tidak ada old input atau draft customer name yang menimpa nilai default.

Sebelum patch, `CreateTransactionWorkspacePageDataBuilder::build()` membuat:

- `defaultCustomerName` = `Pelanggan no ` ditambah `NoteReaderPort::countAll() + 1`

`DatabaseNoteReaderAdapter::countAll()` menjalankan count tanpa scope atas seluruh tabel `notes`.

`CashierNoteHistoryBaseQuery` membatasi visibilitas history kasir ke window tanggal terpilih, sehingga count lifetime tanpa scope lebih luas daripada visibilitas nota kasir biasa.

Blade workspace info card merender default customer name yang berasal dari count tersebut ke halaman create yang terlihat oleh kasir.

## Jalur serangan sebelum patch

Sesi kasir terautentikasi -> buka create transaction workspace -> controller memanggil page data builder -> builder memanggil count nota tanpa scope -> adapter database menghitung semua nota -> halaman merender `Pelanggan no {global_count + 1}` -> kasir dapat menyimpulkan total lifetime nota atau metadata volume bisnis.

## Dampak

Kasir dapat menyimpulkan jumlah global nota atau transaksi, termasuk record di luar window tanggal normal kasir.

Dampaknya medium karena ini membocorkan metadata aggregate volume bisnis, tetapi tidak membocorkan isi lengkap nota, PII pelanggan, kredensial, detail pembayaran, data inventory, atau kemampuan write.

## Prasyarat

- Aplikasi web Laravel menyajikan route cashier note workspace.
- Actor memiliki sesi terautentikasi dengan akses kasir.
- Actor dapat mengakses create transaction workspace.
- Tidak ada old input atau draft customer name yang menimpa nilai default.
- Total jumlah nota global dianggap metadata bisnis sensitif yang tidak dimaksudkan terlihat oleh semua kasir.

## Kontrol yang sudah ada

- Route membutuhkan autentikasi session Laravel.
- Middleware cashier area access berlaku.
- Middleware transaction entry berlaku.
- Query history kasir dibatasi window tanggal.
- Escaping output Blade mengurangi risiko script injection, tetapi tidak mencegah disclosure metadata.

## Kontrol yang hilang sebelum patch

- `countAll()` tidak memiliki scope sesuai visibilitas kasir.
- Default customer name bergantung pada volume tabel nota global.
- Halaman create mengekspos aggregate global yang lebih luas daripada scope history kasir.
- Tidak ada sumber sequence non-sensitif terpisah untuk placeholder label yang terlihat oleh kasir.

## Patch

Patch minimum mengganti default customer name visible di halaman create kasir menjadi label netral:

- dari: `Pelanggan no ` + `NoteReaderPort::countAll() + 1`
- menjadi: `Pelanggan baru`

`CreateTransactionWorkspacePageDataBuilder` tidak lagi menerima dependency `NoteReaderPort` dan tidak lagi memanggil `countAll()`.

Current source anchor pada HEAD `77770796`:

- `app/Application/Note/Services/CreateTransactionWorkspacePageDataBuilder.php`
- `defaultCustomerName` = `Pelanggan baru`

Current regression test anchors pada HEAD `77770796`:

- `tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php`
- test name: `test_workspace_create_does_not_expose_global_note_count_as_default_customer_name`
- asserts `assertDontSee('Pelanggan no 2', false)`
- asserts `assertSee('value="Pelanggan baru"', false)`
- asserts `assertSee('placeholder="Contoh: Pelanggan baru"', false)`

## Proof - RED

Command:

    php -l tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php

    php artisan test tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php

Output penting:

    No syntax errors detected in tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php

    FAIL  Tests\Feature\Note\CreateTransactionWorkspaceDefaultCustomerNameFeatureTest
    ⨯ workspace create does not expose global note count…

    Expected: <!DOCTYPE html>
    ...
    Not to contain: Pelanggan no 2

    at tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php:69

    Tests: 1 failed (4 assertions)

Kesimpulan RED:

Halaman create kasir masih merender `Pelanggan no 2`, sehingga disclosure global note count terbukti melalui HTTP feature test.

## Proof - GREEN targeted

Command:

    php -l app/Application/Note/Services/CreateTransactionWorkspacePageDataBuilder.php
    php -l tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php

    php artisan test tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php

Output:

    No syntax errors detected in app/Application/Note/Services/CreateTransactionWorkspacePageDataBuilder.php
    No syntax errors detected in tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php

    PASS  Tests\Feature\Note\CreateTransactionWorkspaceDefaultCustomerNameFeatureTest
    ✓ workspace create does not expose global note count…

    Tests: 1 passed (6 assertions)

Additional source grep after patch:

    === COUNTALL REFERENCES AFTER PATCH ===
    app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php:64:    public function countAll(): int

Kesimpulan GREEN targeted:

Create workspace page tidak lagi membocorkan `Pelanggan no 2` dan memakai label netral `Pelanggan baru`.

## Proof - focused blast radius

Command:

    php artisan test \
      tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceTemplateContractFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php

Output:

    PASS  Tests\Feature\Note\CreateTransactionWorkspaceDefaultCustomerNameFeatureTest
    ✓ workspace create does not expose global note count…

    PASS  Tests\Feature\Note\CreateTransactionWorkspaceTemplateContractFeatureTest
    ✓ workspace create page embeds explicit service part source values

    PASS  Tests\Feature\Note\CreateTransactionWorkspaceSkipFeatureTest
    ✓ cashier can store workspace and redirect to history when skipping payment

    PASS  Tests\Feature\Note\CreateTransactionWorkspaceFullCashFeatureTest
    ✓ cashier can store workspace with full cash payment

    PASS  Tests\Feature\Note\CreateTransactionWorkspacePartialTransferFeatureTest
    ✓ cashier can store workspace with selected partial transfer payment

    Tests: 5 passed (22 assertions)

## Proof - HEAD/source verification

Command:

    git status --short --untracked-files=all
    git rev-parse --abbrev-ref HEAD
    git rev-parse --short HEAD
    git log --oneline -5
    git show --stat --oneline --decorate -1

    git show HEAD:app/Application/Note/Services/CreateTransactionWorkspacePageDataBuilder.php | grep -nE "Pelanggan baru|countAll|NoteReaderPort" || true

    git show HEAD:tests/Feature/Note/CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php | grep -nE "does_not_expose|Pelanggan no 2|Pelanggan baru|assertDontSee" || true

Output penting:

    main
    77770796
    77770796 (HEAD -> main, origin/main, origin/HEAD) commit 1767
    d2071c86 commit 1766
    cebe7789 commit 1765
    0beadefa commit 1764
    f4675bc4 commit 1763

    77770796 (HEAD -> main, origin/main, origin/HEAD) commit 1767
    ...CreateTransactionWorkspacePageDataBuilder.php | 9 +--------
    1 file changed, 1 insertion(+), 8 deletions(-)

    === HEAD BUILDER ANCHORS ===
    15:            'defaultCustomerName' => 'Pelanggan baru',

    === HEAD TEST ANCHORS ===
    16:    public function test_workspace_create_does_not_expose_global_note_count_as_default_customer_name(): void
    69:        $createResponse->assertDontSee('Pelanggan no 2', false);
    70:        $createResponse->assertSee('value="Pelanggan baru"', false);
    71:        $createResponse->assertSee('placeholder="Contoh: Pelanggan baru"', false);

Kesimpulan HEAD verification:

Source/test patch #029 ada di HEAD lokal `77770796`, dan branch lokal sejajar dengan `origin/main` berdasarkan output `HEAD -> main, origin/main, origin/HEAD`.

## Proof - countAll reference after patch

Command:

    grep -RInE "countAll\(|function countAll" app tests || true

Output:

    app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php:64:    public function countAll(): int
    app/Ports/Out/Note/NoteReaderPort.php:15:    public function countAll(): int;

Kesimpulan:

`countAll()` masih ada di port/adapter, tetapi jalur create workspace kasir tidak lagi memanggilnya. Untuk scope #029, jalur disclosure visible di halaman create sudah diputus.

## Residual gaps

- Full global suite belum hijau untuk sesi ini.
- `make verify` sudah dijalankan, tetapi gagal pada PHPStan issue di file #028, bukan pada source #029:

    Line tests/Feature/Procurement/SupplierPaymentProofFileStorageAdapterFeatureTest.php
    41 Call to an undefined method Illuminate\Contracts\Filesystem\Filesystem::assertExists().
    [ERROR] Found 1 error
    make: *** [mk/hexagonal.mk:7: lint] Error 1

- Karena `make verify` gagal, full DoD/global verification belum boleh diklaim.
- Browser/manual QA untuk halaman create kasir tidak dijalankan.
- `NoteReaderPort::countAll()` dan `DatabaseNoteReaderAdapter::countAll()` masih ada sebagai unused/dead API surface setelah patch ini. Tidak dihapus dalam scope #029 agar blast radius tetap kecil. Cleanup kontrak bisa dilakukan terpisah jika diperlukan.
- Patch ini memakai placeholder netral statis. Jika nanti bisnis membutuhkan nomor sementara yang human-friendly, harus memakai sequence yang tidak membocorkan count global lifetime.
- Docs closure ini tidak membuktikan ulang full HTTP suite setelah docs-only edit.

## Status akhir

Fixed untuk disclosure #029 pada jalur create workspace kasir.

Targeted proof menunjukkan halaman create tidak lagi menampilkan `Pelanggan no 2` dan memakai `Pelanggan baru`.

Focused blast-radius proof untuk create workspace utama lulus `5 passed (22 assertions)`.

Full `make verify` belum hijau karena PHPStan blocker terpisah di test procurement #028.
