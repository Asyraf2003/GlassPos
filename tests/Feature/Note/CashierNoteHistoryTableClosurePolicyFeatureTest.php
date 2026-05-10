<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Note\Queries\CashierNoteHistoryTableQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierNoteHistoryTableClosurePolicyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_today_and_yesterday_notes_without_forcing_open_only(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $older = date('Y-m-d', strtotime('-2 day'));

        $this->seedNote('note-today-open', $today, 'open', 10000);
        $this->seedNote('note-yesterday-open', $yesterday, 'open', 11000);
        $this->seedNote('note-today-closed', $today, 'closed', 12000);
        $this->seedNote('note-older-open', $older, 'open', 13000);

        $this->syncNoteProjectionForTest('note-today-open');
        $this->syncNoteProjectionForTest('note-yesterday-open');
        $this->syncNoteProjectionForTest('note-today-closed');
        $this->syncNoteProjectionForTest('note-older-open');

        $result = app(CashierNoteHistoryTableQuery::class)->get([
            'date' => $today,
            'search' => '',
            'payment_status' => '',
            'work_status' => '',
            'page' => 1,
        ]);

        $items = $result['items'];
        $noteIds = array_map(static fn (array $item): string => (string) $item['note_id'], $items);

        $this->assertContains('note-today-open', $noteIds);
        $this->assertContains('note-yesterday-open', $noteIds);
        $this->assertContains('note-today-closed', $noteIds);
        $this->assertNotContains('note-older-open', $noteIds);
    }

    public function test_it_ignores_client_supplied_historical_date_when_building_cashier_window(): void
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
            'payment_status' => '',
            'work_status' => '',
            'page' => 1,
        ]);

        $items = $result['items'];
        $noteIds = array_map(static fn (array $item): string => (string) $item['note_id'], $items);

        $this->assertSame($today, $result['filters']['date']);
        $this->assertContains('note-today-open', $noteIds);
        $this->assertContains('note-yesterday-open', $noteIds);
        $this->assertNotContains('note-historical-closed', $noteIds);
    }

    private function seedNote(string $noteId, string $transactionDate, string $noteState, int $totalRupiah): void
    {
        DB::table('notes')->insert([
            'id' => $noteId,
            'customer_name' => 'Budi',
            'transaction_date' => $transactionDate,
            'note_state' => $noteState,
            'total_rupiah' => $totalRupiah,
        ]);
    }
}
