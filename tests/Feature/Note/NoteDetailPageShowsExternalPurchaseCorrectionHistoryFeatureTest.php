<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_note_detail_shows_versioning_family_for_external_purchase_history(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenExternalPurchaseNote();

        $response = $this->actingAs($user)->get('/cashier/notes/note-1');

        $response->assertOk();
        $response->assertSee('Riwayat Perubahan Nota');
        $response->assertSee('Perubahan Aktif');
        $response->assertDontSee('Correction Fee Service + Part External');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir External Purchase History',
            'email' => 'kasir-external-purchase-history@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedOpenExternalPurchaseNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 52000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, WorkItem::STATUS_OPEN, 52000);
        $this->seedServiceDetailBase('wi-1', 'Servis AC', 50000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('work_item_external_purchase_lines')->insert([
            'id' => 'ext-1',
            'work_item_id' => 'wi-1',
            'cost_description' => 'Beli luar',
            'unit_cost_rupiah' => 2000,
            'qty' => 1,
            'line_total_rupiah' => 2000,
        ]);
    }
}
