<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminNoteDetailPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_see_final_note_detail_layout_on_closed_note(): void
    {
        $this->loginAsAuthorizedAdmin();

        DB::table('notes')->insert([
            'id' => 'note-closed',
            'customer_name' => 'Budi',
            'customer_phone' => '08123',
            'transaction_date' => now()->toDateString(),
            'total_rupiah' => 50000,
            'note_state' => 'closed',
            'closed_at' => now()->format('Y-m-d H:i:s'),
            'closed_by_actor_id' => 'admin-legacy',
        ]);

        $response = $this->get(route('admin.notes.show', ['noteId' => 'note-closed']));

        $response->assertOk();
        $response->assertSee('Detail Nota');
        $response->assertSee('Info Nota');
        $response->assertSee('Rincian Nota');
        $response->assertSee('Riwayat Nota')
            ->assertSee('Riwayat Perubahan Nota');
        $response->assertDontSee('Status Operasional Admin');
        $response->assertDontSee('Buka Ulang Nota');
    }

    public function test_authorized_admin_sees_same_final_detail_layout_on_open_note(): void
    {
        $this->loginAsAuthorizedAdmin();

        DB::table('notes')->insert([
            'id' => 'note-open',
            'customer_name' => 'Andi',
            'customer_phone' => '08234',
            'transaction_date' => now()->toDateString(),
            'total_rupiah' => 30000,
            'note_state' => 'open',
        ]);

        $response = $this->get(route('admin.notes.show', ['noteId' => 'note-open']));

        $response->assertOk();
        $response->assertSee('Detail Nota');
        $response->assertSee('Info Nota');
        $response->assertSee('Rincian Nota');
        $response->assertSee('Riwayat Nota')
            ->assertSee('Riwayat Perubahan Nota');
        $response->assertDontSee('Status Operasional Admin');
        $response->assertDontSee('Buka Ulang Nota');
    }
}
