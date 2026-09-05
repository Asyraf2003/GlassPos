<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class PaymentTimelineRevisionTruthFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_upward_revision_does_not_rewrite_historical_payment_remaining_balance(): void
    {
        Carbon::setTestNow('2026-09-05 09:00:00');
        $admin = $this->loginAsAuthorizedAdmin();
        $this->seedFiveHundredThousandServiceNote('timeline-revision-up');

        Carbon::setTestNow('2026-09-05 10:00:00');
        $this->actingAs($admin)
            ->post(route('admin.notes.payments.store', ['noteId' => 'timeline-revision-up']), $this->paymentPayload(
                'timeline-revision-up',
                149000,
                'revision-up-payment',
            ))
            ->assertSessionHasNoErrors();

        Carbon::setTestNow('2026-09-05 12:00:00');
        $revision = app(CreateNoteRevisionHandler::class)->handle(
            'timeline-revision-up',
            $this->revisionPayload(650000, 'revision-up'),
            (string) $admin->getAuthIdentifier(),
            false,
        );
        self::assertTrue($revision->isSuccess(), $revision->message());

        $timeline = $this->timeline('timeline-revision-up');
        self::assertCount(1, $timeline);
        self::assertSame(149000, $timeline[0]['payment_amount_rupiah']);
        self::assertSame(351000, $timeline[0]['remaining_after_rupiah']);
        self::assertSame('Bayar Sebagian', $timeline[0]['semantic_label']);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => 'timeline-revision-up',
            'total_rupiah' => 650000,
            'net_paid_rupiah' => 149000,
            'outstanding_rupiah' => 501000,
        ]);
    }

    public function test_downward_revision_auto_refund_keeps_original_payment_event_truthful(): void
    {
        Carbon::setTestNow('2026-09-05 09:00:00');
        $admin = $this->loginAsAuthorizedAdmin();
        $this->seedFiveHundredThousandServiceNote('timeline-revision-down');

        Carbon::setTestNow('2026-09-05 10:00:00');
        $this->actingAs($admin)
            ->post(route('admin.notes.payments.store', ['noteId' => 'timeline-revision-down']), $this->paymentPayload(
                'timeline-revision-down',
                200000,
                'revision-down-payment',
            ))
            ->assertSessionHasNoErrors();

        Carbon::setTestNow('2026-09-05 12:00:00');
        $revision = app(CreateNoteRevisionHandler::class)->handle(
            'timeline-revision-down',
            $this->revisionPayload(100000, 'revision-down'),
            (string) $admin->getAuthIdentifier(),
            false,
        );
        self::assertTrue($revision->isSuccess(), $revision->message());

        self::assertSame(200000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(100000, (int) DB::table('note_revision_surplus_refund_payments')->sum('amount_rupiah'));
        $timeline = $this->timeline('timeline-revision-down');
        self::assertCount(1, $timeline);
        self::assertSame(200000, $timeline[0]['payment_amount_rupiah']);
        self::assertSame(300000, $timeline[0]['remaining_after_rupiah']);
        self::assertSame('Bayar Sebagian', $timeline[0]['semantic_label']);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => 'timeline-revision-down',
            'total_rupiah' => 100000,
            'outstanding_rupiah' => 0,
        ]);
    }

    public function test_payment_after_refund_uses_net_lifecycle_without_rewriting_original_event(): void
    {
        Carbon::setTestNow('2026-09-05 09:00:00');
        $this->seedFiveHundredThousandServiceNote('timeline-refund-repay');

        DB::table('customer_payments')->insert([
            [
                'id' => 'timeline-refund-repay-payment-1',
                'amount_rupiah' => 500000,
                'paid_at' => '2026-09-05',
                'payment_method' => 'transfer',
                'recorded_at' => '2026-09-05 10:00:00.000000',
                'created_at' => '2026-09-05 10:00:00',
            ],
            [
                'id' => 'timeline-refund-repay-payment-2',
                'amount_rupiah' => 50000,
                'paid_at' => '2026-09-05',
                'payment_method' => 'cash',
                'recorded_at' => '2026-09-05 12:00:00.000000',
                'created_at' => '2026-09-05 12:00:00',
            ],
        ]);
        DB::table('payment_component_allocations')->insert([
            $this->componentAllocation('timeline-refund-repay-allocation-1', 'timeline-refund-repay-payment-1', 500000, 1),
            $this->componentAllocation('timeline-refund-repay-allocation-2', 'timeline-refund-repay-payment-2', 50000, 2),
        ]);
        DB::table('customer_refunds')->insert([
            'id' => 'timeline-refund-repay-refund',
            'customer_payment_id' => 'timeline-refund-repay-payment-1',
            'note_id' => 'timeline-refund-repay',
            'amount_rupiah' => 100000,
            'refunded_at' => '2026-09-05',
            'reason' => 'Refund sebelum pembayaran pengganti.',
            'created_at' => '2026-09-05 11:00:00',
        ]);
        DB::table('refund_component_allocations')->insert([
            'id' => 'timeline-refund-repay-refund-allocation',
            'customer_refund_id' => 'timeline-refund-repay-refund',
            'customer_payment_id' => 'timeline-refund-repay-payment-1',
            'note_id' => 'timeline-refund-repay',
            'work_item_id' => 'timeline-refund-repay-work',
            'component_type' => 'service_fee',
            'component_ref_id' => 'timeline-refund-repay-work',
            'refunded_amount_rupiah' => 100000,
            'refund_priority' => 1,
        ]);
        $this->syncNoteProjectionForTest('timeline-refund-repay');

        $timeline = $this->timeline('timeline-refund-repay');
        self::assertSame([50000, 500000], array_column($timeline, 'payment_amount_rupiah'));
        self::assertSame([50000, 0], array_column($timeline, 'remaining_after_rupiah'));
        self::assertSame(['Bayar Sebagian', 'Pelunasan'], array_column($timeline, 'semantic_label'));
    }

    private function seedFiveHundredThousandServiceNote(string $noteId): void
    {
        $workItemId = $noteId.'-work';
        $this->seedNoteBase($noteId, 'Timeline Revision Customer', '2026-09-05', 500000);
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 500000);
        $this->seedServiceDetailBase($workItemId, 'Servis Timeline Revision', 500000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceOnlyCurrentRevision(
            $noteId,
            $noteId.'-r001',
            $workItemId,
            'Timeline Revision Customer',
            '2026-09-05',
            500000,
            'Servis Timeline Revision',
            500000,
        );
    }

    /** @return array<string, mixed> */
    private function paymentPayload(string $noteId, int $amount, string $idempotencyKey): array
    {
        return [
            'selected_row_ids' => [$noteId.'-work::service_fee::'.$noteId.'-work'],
            'payment_scope' => 'partial',
            'payment_method' => 'cash',
            'amount_paid' => $amount,
            'amount_received' => $amount,
            'paid_at' => '2026-09-05',
            'idempotency_key' => $idempotencyKey,
        ];
    }

    /** @return array<string, mixed> */
    private function revisionPayload(int $newTotal, string $idempotencyKey): array
    {
        return [
            'idempotency_key' => $idempotencyKey,
            'reason' => 'Regression payment timeline after revision.',
            'note' => [
                'customer_name' => 'Timeline Revision Customer',
                'customer_phone' => null,
                'transaction_date' => '2026-09-05',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'none',
                'service' => [
                    'name' => 'Servis Timeline Revision',
                    'price_rupiah' => $newTotal,
                    'notes' => null,
                ],
                'product_lines' => [],
                'external_purchase_lines' => [],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => null,
                'amount_paid_rupiah' => null,
                'amount_received_rupiah' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function componentAllocation(string $id, string $paymentId, int $amount, int $priority): array
    {
        return [
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => 'timeline-refund-repay',
            'work_item_id' => 'timeline-refund-repay-work',
            'component_type' => 'service_fee',
            'component_ref_id' => 'timeline-refund-repay-work',
            'component_amount_rupiah_snapshot' => 500000,
            'allocated_amount_rupiah' => $amount,
            'allocation_priority' => $priority,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function timeline(string $noteId): array
    {
        $data = app(NoteDetailPageDataBuilder::class)->build($noteId);
        self::assertIsArray($data);

        return $data['note']['payment_timeline'];
    }
}
