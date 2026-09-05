<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProductFixture;
use Tests\TestCase;

final class CashierNoteDetailAccessGuardFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProductFixture;

    public function test_cashier_can_access_open_note_for_today(): void
    {
        $user = $this->seedKasir();
        $this->seedMinimalNote('note-1', date('Y-m-d'), 'open');

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk();
    }

    public function test_cashier_can_access_open_note_for_yesterday(): void
    {
        $user = $this->seedKasir();
        $this->seedMinimalNote('note-2', date('Y-m-d', strtotime('-1 day')), 'open');

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-2']))
            ->assertOk();
    }

    public function test_cashier_can_access_old_open_note_with_outstanding_debt(): void
    {
        $user = $this->seedKasir();
        $this->seedMinimalNote('note-3', date('Y-m-d', strtotime('-2 day')), 'open');

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-3']))
            ->assertOk();
    }

    public function test_cashier_can_access_closed_note_if_still_within_date_window(): void
    {
        $user = $this->seedKasir();
        $this->seedMinimalNote('note-4', date('Y-m-d'), 'closed');

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-4']))
            ->assertOk();
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Guard',
            'email' => 'cashier-guard@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedMinimalNote(string $noteId, string $transactionDate, string $noteState): void
    {
        $this->seedMinimalProduct(
            id: 'product-1',
            kodeBarang: 'KB-001',
            namaBarang: 'Ban Luar',
            merek: 'Federal',
            ukuran: 100,
            hargaJual: 10000,
        );

        DB::table('notes')->insert([
            'id' => $noteId,
            'customer_name' => 'Budi',
            'transaction_date' => $transactionDate,
            'note_state' => $noteState,
            'total_rupiah' => 10000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-'.$noteId,
            'note_id' => $noteId,
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 10000,
        ]);

        DB::table('work_item_store_stock_lines')->insert([
            'id' => 'sto-'.$noteId,
            'work_item_id' => 'wi-'.$noteId,
            'product_id' => 'product-1',
            'qty' => 1,
            'line_total_rupiah' => 10000,
        ]);
    }
}
