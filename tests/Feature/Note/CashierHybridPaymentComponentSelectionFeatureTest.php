<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierHybridPaymentComponentSelectionFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_payment_can_target_only_selected_product_component_on_mixed_line(): void
    {
        $user = $this->seedKasir();
        $this->seedMixedNote();

        $response = $this->actingAs($user)->post(route('cashier.notes.payments.store', ['noteId' => 'note-1']), [
            'selected_row_ids' => ['wi-1::service_store_stock_part::ssl-1'],
            'payment_method' => 'cash',
            'paid_at' => date('Y-m-d'),
            'amount_paid' => '20000',
            'amount_received' => '20000',
        ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => 'ssl-1',
            'allocated_amount_rupiah' => 20000,
        ]);

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'allocated_amount_rupiah' => 20000,
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Component Select',
            'email' => 'kasir-component-select@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedMixedNote(): void
    {
        $today = date('Y-m-d');

        DB::table('products')->insert([
            'id' => 'prod-1',
            'kode_barang' => 'PRD-TEST-001',
            'nama_barang' => 'Sparepart A',
            'nama_barang_normalized' => 'sparepart a',
            'merek' => 'TEST',
            'merek_normalized' => 'test',
            'ukuran' => 1,
            'harga_jual' => 20000,
            'reorder_point_qty' => 1,
            'critical_threshold_qty' => 1,
        ]);

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis Campuran', 30000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('work_item_store_stock_lines')->insert([
            'id' => 'ssl-1',
            'work_item_id' => 'wi-1',
            'product_id' => 'prod-1',
            'qty' => 1,
            'line_total_rupiah' => 20000,
        ]);

        $this->seedServiceWithStoreStockCurrentRevision(
            'note-1',
            'note-1-r001',
            'wi-1',
            'Budi',
            $today,
            50000,
            'Servis Campuran',
            30000,
            'ssl-1',
            'prod-1',
            1,
            20000,
        );

    }
}
