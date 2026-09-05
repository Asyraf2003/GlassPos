<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Note\Queries\CashierNoteHistoryTableQuery;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierNoteHistoryTableClosurePolicyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_outstanding_focus_keeps_all_ages_and_completed_focus_uses_today_settlement(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $older = date('Y-m-d', strtotime('-2 day'));

        $this->seedNote('note-today-open', $today, 'open', 10000);
        $this->seedNote('note-yesterday-open', $yesterday, 'open', 11000);
        $this->seedNote('note-today-closed', $today, 'closed', 12000);
        $this->seedNote('note-older-open', $older, 'open', 13000);
        $this->seedFullPayment('note-today-closed', 12000);

        $this->syncNoteProjectionForTest('note-today-open');
        $this->syncNoteProjectionForTest('note-yesterday-open');
        $this->syncNoteProjectionForTest('note-today-closed');
        $this->syncNoteProjectionForTest('note-older-open');

        $result = app(CashierNoteHistoryTableQuery::class)->get([
            'date' => $today,
            'search' => '',
            'page' => 1,
        ]);

        $items = $result['items'];
        $noteIds = array_map(static fn (array $item): string => (string) $item['note_id'], $items);

        $this->assertContains('note-today-open', $noteIds);
        $this->assertContains('note-yesterday-open', $noteIds);
        $this->assertNotContains('note-today-closed', $noteIds);
        $this->assertContains('note-older-open', $noteIds);

        $completed = app(CashierNoteHistoryTableQuery::class)->get([
            'bucket' => 'completed',
            'page' => 1,
        ]);
        $completedIds = array_column($completed['items'], 'note_id');

        $this->assertSame(['note-today-closed'], $completedIds);
    }

    public function test_it_ignores_client_date_without_hiding_historical_outstanding_debt(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $historicalDate = '2025-01-15';

        $this->seedNote('note-today-open', $today, 'open', 10000);
        $this->seedNote('note-yesterday-open', $yesterday, 'open', 11000);
        $this->seedNote('note-historical-closed', $historicalDate, 'closed', 12000);

        $this->syncNoteProjectionForTest('note-today-open');
        $this->syncNoteProjectionForTest('note-yesterday-open');
        $this->syncNoteProjectionForTest('note-historical-closed');

        $result = app(CashierNoteHistoryTableQuery::class)->get([
            'date' => $historicalDate,
            'search' => '',
            'page' => 1,
        ]);

        $items = $result['items'];
        $noteIds = array_map(static fn (array $item): string => (string) $item['note_id'], $items);

        $this->assertSame($today, $result['filters']['date']);
        $this->assertContains('note-today-open', $noteIds);
        $this->assertContains('note-yesterday-open', $noteIds);
        $this->assertContains('note-historical-closed', $noteIds);
    }

    public function test_cashier_table_endpoint_ignores_client_date_and_keeps_historical_debt(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $historicalDate = '2025-01-15';

        $this->seedNote('note-today-open', $today, 'open', 10000);
        $this->seedNote('note-yesterday-open', $yesterday, 'open', 11000);
        $this->seedNote('note-historical-closed', $historicalDate, 'closed', 12000);

        $this->syncNoteProjectionForTest('note-today-open');
        $this->syncNoteProjectionForTest('note-yesterday-open');
        $this->syncNoteProjectionForTest('note-historical-closed');

        $response = $this->actingAs($this->cashierUser())->getJson(route('cashier.notes.table', [
            'date' => $historicalDate,
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.filters.date', $today);
        $response->assertJsonFragment(['note_id' => 'note-today-open']);
        $response->assertJsonFragment(['note_id' => 'note-yesterday-open']);
        $response->assertJsonFragment(['note_id' => 'note-historical-closed']);
    }

    private function cashierUser(): User
    {
        $user = User::query()->create([
            'name' => 'Kasir Riwayat JSON',
            'email' => 'cashier-note-history-019@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedNote(string $noteId, string $transactionDate, string $noteState, int $totalRupiah): void
    {
        DB::table('notes')->insert([
            'id' => $noteId,
            'customer_name' => 'Budi',
            'transaction_date' => $transactionDate,
            'note_state' => $noteState,
            'closed_at' => $noteState === 'closed' ? date('Y-m-d').' 12:00:00' : null,
            'total_rupiah' => $totalRupiah,
        ]);
    }

    private function seedFullPayment(string $noteId, int $amount): void
    {
        $paymentId = $noteId.'-payment';
        DB::table('customer_payments')->insert([
            'id' => $paymentId,
            'amount_rupiah' => $amount,
            'paid_at' => date('Y-m-d'),
        ]);
        DB::table('payment_allocations')->insert([
            'id' => $noteId.'-allocation',
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amount,
        ]);
    }
}
