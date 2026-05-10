<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierNoteHistoryTableFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_note_history_table_returns_real_note_rows_for_window_and_open_yesterday_rule(): void
    {
        $this->loginAsKasir();
        $user = $this->cashierUser();

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        DB::table('notes')->insert([
            [
                'id' => 'NOTE-TODAY-1',
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '08123456789',
                'transaction_date' => $today,
                'total_rupiah' => 150000,
            ],
            [
                'id' => 'NOTE-YESTERDAY-OPEN',
                'customer_name' => 'Andi Saputra',
                'customer_phone' => null,
                'transaction_date' => $yesterday,
                'total_rupiah' => 200000,
            ],
        ]);

        DB::table('work_items')->insert([
            [
                'id' => 'WI-TODAY-1',
                'note_id' => 'NOTE-TODAY-1',
                'line_no' => 1,
                'transaction_type' => 'service_only',
                'status' => 'open',
                'subtotal_rupiah' => 150000,
            ],
            [
                'id' => 'WI-YESTERDAY-1',
                'note_id' => 'NOTE-YESTERDAY-OPEN',
                'line_no' => 1,
                'transaction_type' => 'service_only',
                'status' => 'done',
                'subtotal_rupiah' => 120000,
            ],
            [
                'id' => 'WI-YESTERDAY-2',
                'note_id' => 'NOTE-YESTERDAY-OPEN',
                'line_no' => 2,
                'transaction_type' => 'service_only',
                'status' => 'open',
                'subtotal_rupiah' => 80000,
            ],
        ]);

        $this->syncNoteProjectionForTest('NOTE-TODAY-1');
        $this->syncNoteProjectionForTest('NOTE-YESTERDAY-OPEN');

        $response = $this->actingAs($user)->getJson(route('cashier.notes.table', [
            'date' => '2026-03-15',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.filters.date', $today);
        $response->assertJsonPath('data.pagination.total', 2);
        $response->assertJsonPath('data.items.0.note_number', 'NOTE-TODAY-1');
        $response->assertJsonPath('data.items.0.payment_status_label', 'Belum Dibayar');
        $response->assertJsonPath('data.items.0.work_status_label', 'Open: 1 • Selesai: 0 • Batal: 0');
        $response->assertJsonPath('data.items.1.note_number', 'NOTE-YESTERDAY-OPEN');
        $response->assertJsonPath('data.items.1.work_status_label', 'Open: 1 • Selesai: 1 • Batal: 0');
    }

    private function cashierUser(): User
    {
        $user = User::query()->create([
            'name' => 'Kasir Riwayat JSON',
            'email' => 'cashier-note-history-table@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
