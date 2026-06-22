<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class LegacyCreateNoteAtomicityFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_legacy_create_rolls_back_note_rows_and_inventory_when_later_row_fails(): void
    {
        $user = $this->loginAsAuthorizedAdmin();

        $this->seedNotePaymentProduct(
            'product-legacy-create-atomic-valid',
            'PRD-LEGACY-ATOMIC-VALID',
            'Produk Legacy Atomic Valid',
            'Legacy',
            100,
            100000
        );

        DB::table('product_inventory')->insert([
            'product_id' => 'product-legacy-create-atomic-valid',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-legacy-create-atomic-valid',
            'avg_cost_rupiah' => 60000,
            'inventory_value_rupiah' => 600000,
        ]);

        $response = $this->actingAs($user)
            ->from('/notes/create')
            ->post(route('notes.create'), [
                'customer_name' => 'Legacy Atomic Customer',
                'customer_phone' => '08123456789',
                'transaction_date' => date('Y-m-d'),
                'rows' => [
                    [
                        'line_type' => 'product',
                        'product_id' => 'product-legacy-create-atomic-valid',
                        'qty' => 1,
                    ],
                    [
                        'line_type' => 'product',
                        'product_id' => 'product-legacy-create-atomic-missing',
                        'qty' => 1,
                    ],
                ],
            ]);

        $response->assertRedirect('/notes/create');
        $response->assertSessionHasErrors('note');

        $this->assertDatabaseMissing('notes', [
            'customer_name' => 'Legacy Atomic Customer',
        ]);

        self::assertSame(
            0,
            DB::table('work_items')
                ->whereIn(
                    'id',
                    DB::table('work_item_store_stock_lines')
                        ->select('work_item_id')
                        ->where('product_id', 'product-legacy-create-atomic-valid')
                )
                ->count(),
            'Successful earlier rows must be rolled back when a later row fails.'
        );

        self::assertSame(
            0,
            DB::table('inventory_movements')
                ->where('product_id', 'product-legacy-create-atomic-valid')
                ->where('source_type', 'work_item_store_stock_line')
                ->where('qty_delta', '<', 0)
                ->count(),
            'Inventory issue from successful earlier rows must be rolled back when a later row fails.'
        );

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-legacy-create-atomic-valid',
            'qty_on_hand' => 10,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-legacy-create-atomic-valid',
            'avg_cost_rupiah' => 60000,
            'inventory_value_rupiah' => 600000,
        ]);
    }
}
