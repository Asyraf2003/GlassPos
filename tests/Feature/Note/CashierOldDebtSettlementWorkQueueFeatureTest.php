<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Note\Queries\CashierNoteHistoryTableQuery;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierOldDebtSettlementWorkQueueFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_can_settle_old_debt_and_it_moves_to_completed_today(): void
    {
        $cashier = $this->loginAsKasir();
        $noteId = 'old-debt-settled-today';
        $today = date('Y-m-d');
        $oldDate = (new DateTimeImmutable($today))->modify('-21 days')->format('Y-m-d');
        $workItemId = $noteId.'-work';

        $this->seedNoteBase($noteId, 'Pelanggan Utang Lama', $oldDate, 165000);
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_DONE, 165000);
        $this->seedServiceDetailBase($workItemId, 'Servis Utang Lama', 165000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceOnlyCurrentRevision(
            $noteId,
            $noteId.'-r001',
            $workItemId,
            'Pelanggan Utang Lama',
            $oldDate,
            165000,
            'Servis Utang Lama',
            165000,
            WorkItem::STATUS_DONE,
        );
        $this->syncNoteProjectionForTest($noteId);

        $this->actingAs($cashier)
            ->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), [
                'selected_row_ids' => [$workItemId.'::service_fee::'.$workItemId],
                'payment_method' => 'cash',
                'amount_paid' => 165000,
                'amount_received' => 200000,
                'paid_at' => $today,
                'idempotency_key' => 'old-debt-settlement-payment',
            ])
            ->assertRedirect(route('cashier.notes.show', ['noteId' => $noteId]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_payments', ['amount_rupiah' => 165000]);
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 165000,
            'amount_received_rupiah' => 200000,
            'change_rupiah' => 35000,
        ]);
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'closed']);
        self::assertStringStartsWith(
            $today.' ',
            (string) DB::table('notes')->where('id', $noteId)->value('closed_at'),
        );

        $unfinishedAfter = app(CashierNoteHistoryTableQuery::class)->get([]);
        $completedToday = app(CashierNoteHistoryTableQuery::class)->get(['bucket' => 'completed']);
        self::assertNotContains($noteId, array_column($unfinishedAfter['items'], 'note_id'));
        self::assertContains($noteId, array_column($completedToday['items'], 'note_id'));
        self::assertSame(0, (int) DB::table('note_history_projection')->where('note_id', $noteId)->value('outstanding_rupiah'));
    }
}
