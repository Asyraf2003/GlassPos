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

final class CashierEditPageUsesCurrentRevisionFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_edit_page_preloads_current_revision_instead_of_old_root_state(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenServiceOnlyNote();

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))->assertOk();

        $this->actingAs($user)->patch(route('cashier.notes.workspace.update', ['noteId' => 'note-1']), [
            'base_revision_id' => $this->revisionBaseForTest('note-1'),
            'note' => [
                'customer_name' => 'Budi Revised Edit Page',
                'customer_phone' => '081234',
                'transaction_date' => date('Y-m-d'),
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => 'Servis Revision Aktif',
                        'price_rupiah' => '75000',
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
            'inline_payment' => [
                'decision' => 'skip',
            ],
        ])->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));

        $response = $this->actingAs($user)->get(route('cashier.notes.workspace.edit', ['noteId' => 'note-1']));

        $response->assertOk()
            ->assertSee('Budi Revised Edit Page')
            ->assertSee('Servis Revision Aktif');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Edit Revision',
            'email' => 'kasir-edit-revision@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedOpenServiceOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi Root Lama', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis Root Lama', 50000, ServiceDetail::PART_SOURCE_NONE);
    }
}
