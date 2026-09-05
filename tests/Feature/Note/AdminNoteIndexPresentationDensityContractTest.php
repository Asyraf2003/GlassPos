<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class AdminNoteIndexPresentationDensityContractTest extends TestCase
{
    public function test_admin_note_index_prioritizes_customer_and_avoids_duplicate_note_identity(): void
    {
        $view = $this->readViewSource('admin/notes/index.blade.php');
        $filter = $this->readViewSource('admin/notes/partials/filter-drawer.blade.php');
        $script = $this->readPublicAsset('assets/static/js/pages/admin-note-index.js');

        self::assertStringNotContainsString('Daftar Nota Admin', $view);
        self::assertStringNotContainsString('Daftar Nota Admin', $filter);
        self::assertStringContainsString('Filter Nota', $filter);
        self::assertStringContainsString('Cari pelanggan, no. HP, atau rincian', $view);
        self::assertStringNotContainsString('data-sort-by="note_number"', $view);
        self::assertStringContainsString('data-sort-by="customer_name"', $view);
        self::assertStringContainsString('colspan="8"', $view);
        self::assertStringNotContainsString('item.note_number ?? item.note_id', $script);
        self::assertStringNotContainsString('item.transaction_date ??', $script);
        self::assertStringContainsString("item.customer_name ?? 'Pelanggan'", $script);
        self::assertStringContainsString('>Buka</a>', $script);
        self::assertStringContainsString('colspan="8"', $script);
    }

    private function readViewSource(string $path): string
    {
        return (string) file_get_contents(resource_path('views/'.$path));
    }

    private function readPublicAsset(string $path): string
    {
        return (string) file_get_contents(public_path($path));
    }
}
