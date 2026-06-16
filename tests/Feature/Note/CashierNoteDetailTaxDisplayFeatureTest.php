<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNoteDetailTaxDisplayFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_detail_page_shows_note_level_tax_breakdown(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-tax-detail-1', 'Budi Pajak Nota', $today, 44400, 'open');

        DB::table('notes')->where('id', 'note-tax-detail-1')->update([
            'subtotal_before_note_tax_rupiah' => 40000,
            'note_tax_input' => '11%',
            'note_tax_mode' => 'percent',
            'note_tax_rate_basis_points' => 1100,
            'note_tax_amount_rupiah' => 4400,
        ]);

        $this->seedWorkItemBase(
            'wi-note-tax-detail-1',
            'note-tax-detail-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            40000,
        );

        $this->seedServiceDetailBase(
            'wi-note-tax-detail-1',
            'Servis Pajak Nota',
            40000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-tax-detail-1']));

        $response->assertOk();
        $response->assertSee('Subtotal Sebelum Pajak');
        $response->assertSee('Pajak Nota');
        $response->assertSee('11%');
        $response->assertSee('4.400', false);
        $response->assertSee('44.400', false);
    }

    public function test_detail_page_shows_product_line_tax_breakdown(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        DB::table('products')->insert([
            'id' => 'product-line-tax-detail-1',
            'kode_barang' => 'PLT-001',
            'nama_barang' => 'Oli Pajak Detail',
            'nama_barang_normalized' => 'oli pajak detail',
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 800,
            'harga_jual' => 20000,
        ]);

        $this->seedNoteBase('note-line-tax-detail-1', 'Budi Pajak Produk', $today, 44400, 'open');

        $this->seedWorkItemBase(
            'wi-line-tax-detail-1',
            'note-line-tax-detail-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_OPEN,
            44400,
        );

        $this->seedStoreStockLineBase(
            'sto-line-tax-detail-1',
            'wi-line-tax-detail-1',
            'product-line-tax-detail-1',
            2,
            44400,
        );

        DB::table('work_item_store_stock_lines')->where('id', 'sto-line-tax-detail-1')->update([
            'base_total_rupiah' => 40000,
            'tax_input' => '11%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1100,
            'tax_amount_rupiah' => 4400,
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-line-tax-detail-1']));

        $response->assertOk();
        $response->assertSee('Oli Pajak Detail');
        $response->assertSee('Pajak Produk');
        $response->assertSee('11%');
        $response->assertSee('4.400', false);
        $response->assertSee('44.400', false);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Tax Detail',
            'email' => 'kasir-tax-detail@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
