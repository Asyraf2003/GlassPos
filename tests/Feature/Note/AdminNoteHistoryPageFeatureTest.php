<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminNoteHistoryPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_access_note_history_shell_page(): void
    {
        $user = $this->loginAsAuthorizedAdmin();

        $response = $this->actingAs($user)->get(route('admin.notes.index'));

        $response->assertOk();
        $response->assertSee('Daftar Nota');
        $response->assertDontSee('Daftar Nota Admin');
        $response->assertSee('Cari pelanggan, no. HP, atau rincian');
        $response->assertSee('admin-note-search-input', false);
        $response->assertSee('admin-note-table-body', false);
        $response->assertSee('admin-note-date-range', false);
        $response->assertSee('admin-note-date-from', false);
        $response->assertSee('admin-note-date-to', false);
        $response->assertSee('admin-note-index.js');
        $response->assertSee(json_encode(route('admin.notes.table')), false);
    }
}
