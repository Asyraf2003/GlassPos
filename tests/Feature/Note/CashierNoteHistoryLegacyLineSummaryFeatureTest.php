<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierNoteHistoryLegacyLineSummaryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_table_marks_legacy_fully_paid_note_as_close_in_line_summary(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Legacy History',
            'email' => 'cashier-legacy-history@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = now()->toDateString();

        DB::table('notes')->insert([
            'id' => 'note-legacy',
            'customer_name' => 'Legacy Paid',
            'customer_phone' => '08123',
            'transaction_date' => $today,
            'total_rupiah' => 50000,
            'note_state' => 'open',
        ]);

        DB::table('work_items')->insert([
            'id' => 'note-legacy-wi-1',
            'note_id' => 'note-legacy',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'note-legacy-pay-1',
            'amount_rupiah' => 50000,
            'paid_at' => $today,
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'note-legacy-alloc-1',
            'customer_payment_id' => 'note-legacy-pay-1',
            'note_id' => 'note-legacy',
            'amount_rupiah' => 50000,
        ]);

        $this->syncNoteProjectionForTest('note-legacy');

        $response = $this->actingAs($user)->getJson(route('cashier.notes.table'));

        $response->assertOk();
        $response->assertJsonPath('success', true);

        /** @var Collection<string, array<string, mixed>> $items */
        $items = collect($response->json('data.items'))->keyBy('note_id');

        $this->assertSame('1 Selesai', $items->get('note-legacy')['line_summary_label']);
        $this->assertSame('Lunas', $items->get('note-legacy')['payment_status_label']);
        $this->assertSame('Pekerjaan aktif', $items->get('note-legacy')['focus_status_label']);
        $this->assertStringContainsString('/cashier/notes/note-legacy', (string) $items->get('note-legacy')['action_url']);
    }
}
