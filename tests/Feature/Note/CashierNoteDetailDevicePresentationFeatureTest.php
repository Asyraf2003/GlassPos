<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierNoteDetailDevicePresentationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_detail_uses_desktop_layout_marker_for_desktop_requests(): void
    {
        $this->seedOpenNote();
        $this->loginAsKasir();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?0'])
            ->get(route('cashier.notes.show', ['noteId' => 'note-device-1']));

        $response->assertOk()
            ->assertSee('data-note-device="desktop"', false)
            ->assertSee('data-note-detail-layout="desktop"', false)
            ->assertDontSee('data-detail-toggle', false);
    }

    public function test_note_detail_uses_compact_handset_layout_marker_for_handset_requests(): void
    {
        $this->seedOpenNote();
        $this->loginAsKasir();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?1'])
            ->get(route('cashier.notes.show', ['noteId' => 'note-device-1']));

        $response->assertOk()
            ->assertSee('data-note-device="handset"', false)
            ->assertSee('data-note-detail-layout="compact"', false)
            ->assertDontSee('data-detail-toggle', false);
    }

    private function seedOpenNote(): void
    {
        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-device-1',
            'customer_name' => 'Budi Device',
            'customer_phone' => '0812000009',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-device-1',
            'note_id' => 'note-device-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-device-1',
            'service_name' => 'Servis Device',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);
    }
}
