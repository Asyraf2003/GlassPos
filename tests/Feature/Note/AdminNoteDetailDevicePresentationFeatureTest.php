<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminNoteDetailDevicePresentationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_note_detail_uses_desktop_layout_marker_for_desktop_requests(): void
    {
        $this->seedNote();
        $this->loginAsAuthorizedAdmin();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?0'])
            ->get(route('admin.notes.show', ['noteId' => 'admin-note-device-1']));

        $response->assertOk()
            ->assertSee('data-note-device="desktop"', false)
            ->assertSee('data-note-detail-layout="desktop"', false)
            ->assertSee(route('admin.notes.workspace.edit', ['noteId' => 'admin-note-device-1']), false);
    }

    public function test_admin_note_detail_uses_compact_layout_marker_for_handset_requests(): void
    {
        $this->seedNote();
        $this->loginAsAuthorizedAdmin();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?1'])
            ->get(route('admin.notes.show', ['noteId' => 'admin-note-device-1']));

        $response->assertOk()
            ->assertSee('data-note-device="handset"', false)
            ->assertSee('data-note-detail-layout="compact"', false)
            ->assertSee(route('admin.notes.workspace.edit', ['noteId' => 'admin-note-device-1']), false);
    }

    private function seedNote(): void
    {
        DB::table('notes')->insert([
            'id' => 'admin-note-device-1',
            'customer_name' => 'Admin Device',
            'customer_phone' => '0812000010',
            'transaction_date' => now()->toDateString(),
            'note_state' => 'open',
            'total_rupiah' => 0,
        ]);
    }
}
