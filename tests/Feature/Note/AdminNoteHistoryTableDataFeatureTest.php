<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminNoteHistoryTableDataFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_fetch_note_history_table_items(): void
    {
        $this->loginAsAuthorizedAdmin();
        $today = now()->toDateString();

        $this->seedOpenUnpaidNote('note-open', $today, 'Budi', '08123');
        $this->seedClosedPaidNote('note-closed', $today, 'Andi', '08234');

        $response = $this->getJson(route('admin.notes.table', [
            'date_from' => $today,
            'date_to' => $today,
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.pagination.total', 2);

        /** @var Collection<string, array<string, mixed>> $items */
        $items = collect($response->json('data.items'))->keyBy('note_id');

        $this->assertSame('1 Belum Selesai', $items->get('note-open')['line_summary_label']);
        $this->assertSame([
            'open' => 1,
            'close' => 0,
            'refund' => 0,
        ], $items->get('note-open')['line_summary_counts']);
        $this->assertStringContainsString('/admin/notes/note-open', (string) $items->get('note-open')['action_url']);

        $this->assertSame('1 Selesai', $items->get('note-closed')['line_summary_label']);
        $this->assertSame([
            'open' => 0,
            'close' => 1,
            'refund' => 0,
        ], $items->get('note-closed')['line_summary_counts']);
        $this->assertStringContainsString('/admin/notes/note-closed', (string) $items->get('note-closed')['action_url']);
    }

    public function test_authorized_admin_can_filter_note_history_by_line_status(): void
    {
        $this->loginAsAuthorizedAdmin();
        $today = now()->toDateString();

        $this->seedOpenUnpaidNote('note-open', $today, 'Budi', '08123');
        $this->seedClosedPaidNote('note-closed', $today, 'Andi', '08234');

        $response = $this->getJson(route('admin.notes.table', [
            'date_from' => $today,
            'date_to' => $today,
            'line_status' => 'close',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.pagination.total', 1);
        $response->assertJsonPath('data.items.0.note_id', 'note-closed');
        $response->assertJsonPath('data.items.0.line_summary_label', '1 Selesai');
    }

    public function test_default_order_uses_created_at_before_lexically_misleading_note_id(): void
    {
        $this->loginAsAuthorizedAdmin();
        $today = now()->toDateString();
        $this->seedProjectedAdminNote('zzz-oldest-id', $today, 10000, 0, $today.' 08:00:00');
        $this->seedProjectedAdminNote('mmm-middle-id', $today, 20000, 0, $today.' 12:00:00');
        $this->seedProjectedAdminNote('aaa-newest-id', $today, 30000, 0, $today.' 16:00:00');

        $response = $this->getJson(route('admin.notes.table', [
            'date_from' => $today,
            'date_to' => $today,
        ]))->assertOk();

        self::assertSame(
            ['aaa-newest-id', 'mmm-middle-id', 'zzz-oldest-id'],
            array_column($response->json('data.items'), 'note_id'),
        );
    }

    public function test_default_search_prioritizes_exact_note_identity_over_newer_customer_match(): void
    {
        $this->loginAsAuthorizedAdmin();
        $today = now()->toDateString();
        $this->seedProjectedAdminNote('FOCUS-NOTE', $today, 10000, 0, $today.' 08:00:00');
        $this->seedProjectedAdminNote('newer-customer-match', $today, 20000, 0, $today.' 16:00:00');
        DB::table('note_history_projection')->where('note_id', 'newer-customer-match')->update([
            'customer_name' => 'Pelanggan FOCUS-NOTE Umum',
            'customer_name_normalized' => 'pelanggan focus-note umum',
        ]);

        $response = $this->getJson(route('admin.notes.table', [
            'date_from' => $today,
            'date_to' => $today,
            'search' => 'FOCUS-NOTE',
        ]))->assertOk();

        $response->assertJsonPath('data.items.0.note_id', 'FOCUS-NOTE')
            ->assertJsonPath('data.filters.sort_by', 'relevance');
    }

    public function test_server_side_money_sort_is_applied_before_pagination(): void
    {
        $this->loginAsAuthorizedAdmin();
        $today = now()->toDateString();

        foreach (range(1, 11) as $number) {
            $this->seedProjectedAdminNote(
                sprintf('sort-note-%02d', $number),
                $today,
                $number * 10000,
                $number * 1000,
                $today.' 10:00:00',
            );
        }

        $response = $this->getJson(route('admin.notes.table', [
            'date_from' => $today,
            'date_to' => $today,
            'sort_by' => 'total_rupiah',
            'sort_dir' => 'asc',
            'page' => 2,
        ]))->assertOk();

        $response->assertJsonPath('data.pagination.total', 11);
        $response->assertJsonPath('data.pagination.page', 2);
        $response->assertJsonPath('data.items.0.note_id', 'sort-note-11');
        $response->assertJsonPath('data.items.0.grand_total_text', 'Rp 110.000');
        $response->assertJsonPath('data.filters.sort_by', 'total_rupiah');
        $response->assertJsonPath('data.filters.sort_dir', 'asc');
    }

    public function test_admin_can_sort_created_total_paid_and_outstanding_at_query_boundary(): void
    {
        $this->loginAsAuthorizedAdmin();
        $today = now()->toDateString();
        $this->seedProjectedAdminNote('sort-a', $today, 500000, 100000, $today.' 08:00:00');
        $this->seedProjectedAdminNote('sort-b', $today, 300000, 200000, $today.' 12:00:00');
        $this->seedProjectedAdminNote('sort-c', $today, 400000, 0, $today.' 16:00:00');

        $expectations = [
            ['created_at', 'asc', ['sort-a', 'sort-b', 'sort-c']],
            ['total_rupiah', 'asc', ['sort-b', 'sort-c', 'sort-a']],
            ['net_paid_rupiah', 'desc', ['sort-b', 'sort-a', 'sort-c']],
            ['outstanding_rupiah', 'asc', ['sort-b', 'sort-c', 'sort-a']],
        ];

        foreach ($expectations as [$sortBy, $sortDir, $expectedIds]) {
            $response = $this->getJson(route('admin.notes.table', [
                'date_from' => $today,
                'date_to' => $today,
                'search' => 'Admin Sort sort-',
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ]))->assertOk();

            self::assertSame($expectedIds, array_column($response->json('data.items'), 'note_id'));
            $response->assertJsonPath('data.filters.sort_by', $sortBy);
            $response->assertJsonPath('data.filters.sort_dir', $sortDir);
        }
    }

    public function test_admin_note_table_rejects_unknown_sort_key(): void
    {
        $this->loginAsAuthorizedAdmin();

        $this->getJson(route('admin.notes.table', ['sort_by' => 'raw_sql']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort_by']);
    }

    private function seedOpenUnpaidNote(string $noteId, string $date, string $customerName, string $phone): void
    {
        DB::table('notes')->insert([
            'id' => $noteId,
            'customer_name' => $customerName,
            'customer_phone' => $phone,
            'transaction_date' => $date,
            'total_rupiah' => 40000,
            'note_state' => 'open',
        ]);

        DB::table('work_items')->insert([
            'id' => $noteId.'-wi-1',
            'note_id' => $noteId,
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 40000,
        ]);

        $this->syncNoteProjectionForTest($noteId);
    }

    private function seedClosedPaidNote(string $noteId, string $date, string $customerName, string $phone): void
    {
        DB::table('notes')->insert([
            'id' => $noteId,
            'customer_name' => $customerName,
            'customer_phone' => $phone,
            'transaction_date' => $date,
            'total_rupiah' => 50000,
            'note_state' => 'closed',
        ]);

        DB::table('work_items')->insert([
            'id' => $noteId.'-wi-1',
            'note_id' => $noteId,
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_DONE,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('customer_payments')->insert([
            'id' => $noteId.'-pay-1',
            'amount_rupiah' => 50000,
            'paid_at' => $date,
        ]);

        DB::table('payment_allocations')->insert([
            'id' => $noteId.'-alloc-1',
            'customer_payment_id' => $noteId.'-pay-1',
            'note_id' => $noteId,
            'amount_rupiah' => 50000,
        ]);

        $this->syncNoteProjectionForTest($noteId);
    }

    private function seedProjectedAdminNote(
        string $noteId,
        string $date,
        int $total,
        int $netPaid,
        string $createdAt,
    ): void {
        DB::table('notes')->insert([
            'id' => $noteId,
            'customer_name' => 'Admin Sort '.$noteId,
            'transaction_date' => $date,
            'total_rupiah' => $total,
            'note_state' => $netPaid >= $total ? 'closed' : 'open',
            'created_at' => $createdAt,
        ]);

        DB::table('note_history_projection')->insert([
            'note_id' => $noteId,
            'transaction_date' => $date,
            'note_state' => $netPaid >= $total ? 'closed' : 'open',
            'customer_name' => 'Admin Sort '.$noteId,
            'customer_name_normalized' => 'admin sort '.$noteId,
            'customer_phone' => null,
            'total_rupiah' => $total,
            'allocated_rupiah' => $netPaid,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => max($total - $netPaid, 0),
            'line_open_count' => 1,
            'line_close_count' => 0,
            'line_refund_count' => 0,
            'has_open_lines' => true,
            'has_close_lines' => false,
            'has_refund_lines' => false,
            'projected_at' => $createdAt,
        ]);
    }
}
