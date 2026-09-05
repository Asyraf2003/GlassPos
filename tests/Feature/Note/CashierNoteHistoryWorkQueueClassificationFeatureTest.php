<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Note\Queries\CashierNoteHistoryTableQuery;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierNoteHistoryWorkQueueClassificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_focus_contains_only_notes_with_financial_outstanding(): void
    {
        $this->seedProjectedNote('unpaid-done', 100000, 0, Note::STATE_OPEN, WorkItem::STATUS_DONE);
        $this->seedProjectedNote('partial-done', 100000, 60000, Note::STATE_OPEN, WorkItem::STATUS_DONE);
        $this->seedProjectedNote('paid-open-work', 100000, 100000, Note::STATE_CLOSED, WorkItem::STATUS_OPEN);
        $this->seedProjectedNote('paid-done', 100000, 100000, Note::STATE_CLOSED, WorkItem::STATUS_DONE);

        $result = app(CashierNoteHistoryTableQuery::class)->get([]);
        $items = collect($result['items'])->keyBy('note_id');

        self::assertSame('unfinished', $result['filters']['bucket']);
        self::assertEqualsCanonicalizing(['unpaid-done', 'partial-done'], $items->keys()->all());
        self::assertSame('Sisa tagihan', $items['unpaid-done']['focus_status_label']);
        self::assertSame('Sisa tagihan', $items['partial-done']['focus_status_label']);
        self::assertArrayNotHasKey('paid-open-work', $items->all());
        self::assertArrayNotHasKey('paid-done', $items->all());
    }

    public function test_completed_today_keeps_operational_and_terminal_domain_status_as_context(): void
    {
        $closedAt = date('Y-m-d').' 15:00:00';
        $this->seedProjectedNote('paid-done', 100000, 100000, Note::STATE_CLOSED, WorkItem::STATUS_DONE, closedAt: $closedAt);
        $this->seedProjectedNote('paid-open-work', 100000, 100000, Note::STATE_CLOSED, WorkItem::STATUS_OPEN, closedAt: $closedAt);
        $this->seedProjectedNote('refunded-terminal', 100000, 0, Note::STATE_REFUNDED, WorkItem::STATUS_OPEN, closedAt: $closedAt);
        $this->seedProjectedNote('canceled-work', 100000, 100000, Note::STATE_CLOSED, WorkItem::STATUS_CANCELED, closedAt: $closedAt);

        $result = app(CashierNoteHistoryTableQuery::class)->get(['bucket' => 'completed']);
        $items = collect($result['items'])->keyBy('note_id');

        self::assertEqualsCanonicalizing(['paid-done', 'paid-open-work', 'refunded-terminal', 'canceled-work'], $items->keys()->all());
        self::assertSame('Belum Selesai: 1 • Selesai: 0 • Batal: 0', $items['paid-open-work']['work_status_label']);
        self::assertSame('Dikembalikan', $items['refunded-terminal']['domain_status_label']);
        self::assertFalse($items['refunded-terminal']['can_edit']);
        self::assertNull($items['refunded-terminal']['edit_url']);
        self::assertSame('Ada pekerjaan batal', $items['canceled-work']['domain_status_label']);
        self::assertTrue($items['canceled-work']['can_edit']);
    }

    public function test_search_is_scoped_inside_selected_bucket(): void
    {
        $this->seedProjectedNote('needle-unfinished', 50000, 0, Note::STATE_OPEN, WorkItem::STATUS_DONE, 'Needle Customer');
        $this->seedProjectedNote(
            'needle-completed',
            50000,
            50000,
            Note::STATE_CLOSED,
            WorkItem::STATUS_DONE,
            'Needle Customer',
            closedAt: date('Y-m-d').' 14:00:00',
        );

        $unfinished = app(CashierNoteHistoryTableQuery::class)->get(['search' => 'Needle']);
        $completed = app(CashierNoteHistoryTableQuery::class)->get(['bucket' => 'completed', 'search' => 'Needle']);

        self::assertSame(['needle-unfinished'], array_column($unfinished['items'], 'note_id'));
        self::assertSame(['needle-completed'], array_column($completed['items'], 'note_id'));
    }

    public function test_pagination_total_and_pages_are_calculated_after_bucket_classification(): void
    {
        foreach (range(1, 11) as $number) {
            $this->seedProjectedNote('unfinished-'.$number, 10000, 0, Note::STATE_OPEN, WorkItem::STATUS_DONE);
        }
        $this->seedProjectedNote('completed-only', 10000, 10000, Note::STATE_CLOSED, WorkItem::STATUS_DONE);

        $secondPage = app(CashierNoteHistoryTableQuery::class)->get(['page' => 2]);

        self::assertSame(11, $secondPage['pagination']['total']);
        self::assertSame(2, $secondPage['pagination']['last_page']);
        self::assertSame(1, count($secondPage['items']));
        self::assertNotContains('completed-only', array_column($secondPage['items'], 'note_id'));
    }

    public function test_same_transaction_date_is_ordered_by_created_at_newest_first(): void
    {
        $this->seedProjectedNote(
            'zzz-oldest-id',
            10000,
            0,
            Note::STATE_OPEN,
            WorkItem::STATUS_DONE,
            createdAt: date('Y-m-d').' 08:00:00',
        );
        $this->seedProjectedNote(
            'mmm-middle-id',
            10000,
            0,
            Note::STATE_OPEN,
            WorkItem::STATUS_DONE,
            createdAt: date('Y-m-d').' 12:00:00',
        );
        $this->seedProjectedNote(
            'aaa-newest-id',
            10000,
            0,
            Note::STATE_OPEN,
            WorkItem::STATUS_DONE,
            createdAt: date('Y-m-d').' 16:00:00',
        );

        $result = app(CashierNoteHistoryTableQuery::class)->get([]);

        self::assertSame(
            ['aaa-newest-id', 'mmm-middle-id', 'zzz-oldest-id'],
            array_column($result['items'], 'note_id'),
        );
    }

    public function test_old_outstanding_debt_remains_in_unfinished_focus_without_date_window(): void
    {
        $oldDate = date('Y-m-d', strtotime('-21 days'));
        $this->seedProjectedNote(
            'old-outstanding-debt',
            500000,
            149000,
            Note::STATE_OPEN,
            WorkItem::STATUS_DONE,
            transactionDate: $oldDate,
            createdAt: $oldDate.' 09:00:00',
        );

        $result = app(CashierNoteHistoryTableQuery::class)->get([]);

        self::assertSame(['old-outstanding-debt'], array_column($result['items'], 'note_id'));
        self::assertSame('Rp 351.000', $result['items'][0]['outstanding_text']);
    }

    public function test_financially_settled_today_is_completed_even_when_work_remains_open(): void
    {
        $oldDate = date('Y-m-d', strtotime('-10 days'));
        $this->seedProjectedNote(
            'old-settled-today-open-work',
            100000,
            100000,
            Note::STATE_CLOSED,
            WorkItem::STATUS_OPEN,
            transactionDate: $oldDate,
            createdAt: $oldDate.' 08:00:00',
            closedAt: date('Y-m-d').' 15:00:00',
        );

        $unfinished = app(CashierNoteHistoryTableQuery::class)->get([]);
        $completed = app(CashierNoteHistoryTableQuery::class)->get(['bucket' => 'completed']);

        self::assertNotContains('old-settled-today-open-work', array_column($unfinished['items'], 'note_id'));
        self::assertSame(['old-settled-today-open-work'], array_column($completed['items'], 'note_id'));
        self::assertSame('Belum Selesai: 1 • Selesai: 0 • Batal: 0', $completed['items'][0]['work_status_label']);
    }

    public function test_history_reads_projection_and_get_does_not_mutate_business_tables(): void
    {
        $this->seedNoteOnly('without-projection', 25000, Note::STATE_OPEN);
        $this->seedProjectedNote('with-projection', 25000, 0, Note::STATE_OPEN, WorkItem::STATUS_DONE);
        $before = $this->businessCounts();

        $result = app(CashierNoteHistoryTableQuery::class)->get([]);

        self::assertSame(['with-projection'], array_column($result['items'], 'note_id'));
        self::assertSame($before, $this->businessCounts());
    }

    public function test_endpoint_rejects_unknown_bucket_instead_of_silently_reclassifying(): void
    {
        $this->loginAsKasir();

        $this->getJson(route('cashier.notes.table', ['bucket' => 'refund']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bucket']);
    }

    private function seedProjectedNote(
        string $id,
        int $total,
        int $netPaid,
        string $noteState,
        string $workStatus,
        string $customer = 'Customer Queue',
        ?string $createdAt = null,
        ?string $transactionDate = null,
        ?string $closedAt = null,
    ): void {
        $transactionDate ??= date('Y-m-d');
        $this->seedNoteOnly($id, $total, $noteState, $customer, $createdAt, $transactionDate, $closedAt);
        DB::table('work_items')->insert([
            'id' => $id.'-work',
            'note_id' => $id,
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => $workStatus,
            'subtotal_rupiah' => $total,
        ]);
        DB::table('note_history_projection')->insert([
            'note_id' => $id,
            'transaction_date' => $transactionDate,
            'note_state' => $noteState,
            'customer_name' => $customer,
            'customer_name_normalized' => mb_strtolower($customer),
            'customer_phone' => null,
            'total_rupiah' => $total,
            'allocated_rupiah' => $netPaid,
            'refunded_rupiah' => $noteState === Note::STATE_REFUNDED ? $total : 0,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => max($total - $netPaid, 0),
            'line_open_count' => $netPaid < $total ? 1 : 0,
            'line_close_count' => $netPaid >= $total ? 1 : 0,
            'line_refund_count' => $noteState === Note::STATE_REFUNDED ? 1 : 0,
            'has_open_lines' => $netPaid < $total,
            'has_close_lines' => $netPaid >= $total,
            'has_refund_lines' => $noteState === Note::STATE_REFUNDED,
            'projected_at' => now(),
        ]);
    }

    private function seedNoteOnly(
        string $id,
        int $total,
        string $noteState,
        string $customer = 'Customer Queue',
        ?string $createdAt = null,
        ?string $transactionDate = null,
        ?string $closedAt = null,
    ): void {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $customer,
            'customer_phone' => null,
            'transaction_date' => $transactionDate ?? date('Y-m-d'),
            'note_state' => $noteState,
            'closed_at' => $closedAt,
            'total_rupiah' => $total,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    /** @return array<string, int> */
    private function businessCounts(): array
    {
        return [
            'notes' => DB::table('notes')->count(),
            'work_items' => DB::table('work_items')->count(),
            'payments' => DB::table('customer_payments')->count(),
            'inventory_movements' => DB::table('inventory_movements')->count(),
            'audit_outbox' => DB::table('audit_outbox')->count(),
        ];
    }
}
