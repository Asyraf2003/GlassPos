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

final class NoteDetailOperationalPackageVisibilityFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_detail_shows_operational_note_and_store_stock_package_breakdown(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Detail Package',
            'email' => 'kasir-detail-package@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        $this->seedNotePaymentProduct(
            'product-detail-a',
            'FILTER-DETAIL-A',
            'Filter Oli Detail',
            'Yamaha',
            100,
            50000,
        );
        $this->seedNotePaymentProduct(
            'product-detail-b',
            'BUSI-DETAIL-B',
            'Busi Iridium Detail',
            'NGK',
            1,
            30000,
        );

        $this->seedNoteBase('note-detail-package-1', 'Pelanggan Detail', $today, 250000);
        DB::table('notes')->where('id', 'note-detail-package-1')->update([
            'operational_note' => 'Keterangan operasional detail package',
        ]);

        $this->seedWorkItemBase(
            'wi-detail-package-1',
            'note-detail-package-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            250000,
        );
        $this->seedServiceDetailBase(
            'wi-detail-package-1',
            'Service Paket Detail',
            120000,
            ServiceDetail::PART_SOURCE_STORE_STOCK,
        );
        $this->seedStoreStockLineBase('sto-detail-a', 'wi-detail-package-1', 'product-detail-a', 2, 100000);
        $this->seedStoreStockLineBase('sto-detail-b', 'wi-detail-package-1', 'product-detail-b', 1, 30000);

        $this->seedCurrentRevision(
            'note-detail-package-1',
            'rev-detail-package-1',
            'Pelanggan Detail',
            null,
            $today,
            250000,
            [[
                'id' => 'rev-detail-package-1-l001',
                'work_item_root_id' => 'wi-detail-package-1',
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Service Paket Detail',
                'service_price_rupiah' => 120000,
                'subtotal_rupiah' => 250000,
                'payload' => [
                    'work_item_root_id' => 'wi-detail-package-1',
                    'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
                    'status' => WorkItem::STATUS_OPEN,
                    'external_purchase_lines' => [],
                    'store_stock_lines' => [
                        ['id' => 'sto-detail-a', 'product_id' => 'product-detail-a', 'qty' => 2, 'line_total_rupiah' => 100000],
                        ['id' => 'sto-detail-b', 'product_id' => 'product-detail-b', 'qty' => 1, 'line_total_rupiah' => 30000],
                    ],
                    'service' => [
                        'service_name' => 'Service Paket Detail',
                        'service_price_rupiah' => 120000,
                        'part_source' => ServiceDetail::PART_SOURCE_STORE_STOCK,
                    ],
                ],
            ]],
        );

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-detail-package-1']))
            ->assertOk()
            ->assertSee('Keterangan Nota')
            ->assertSee('Keterangan operasional detail package')
            ->assertSee('Paket total')
            ->assertSee('Total sparepart')
            ->assertSee('Sisa jasa')
            ->assertSee('Filter Oli Detail')
            ->assertSee('Busi Iridium Detail')
            ->assertSee('250.000')
            ->assertSee('130.000')
            ->assertSee('120.000');
    }
}
