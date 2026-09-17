<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProductFixture;
use Tests\TestCase;

final class RefundRevisionOperationalReopenFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProductFixture;

    public function test_authorized_revision_after_refund_reopens_root_and_new_settlement_closes_it_again(): void
    {
        $clock = new class implements ClockPort {
            public DateTimeImmutable $time;

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };
        $clock->time = new DateTimeImmutable('2026-09-15 09:00:00+08:00');
        $this->app->instance(ClockPort::class, $clock);
        $admin = $this->loginAsAuthorizedAdmin();
        $actorId = (string) $admin->getAuthIdentifier();
        $date = date('Y-m-d');
        $this->seedMinimalProduct('reopen-product', 'ROP-001', 'Historical product', 'Test', null, 100000);
        DB::table('product_inventory')->insert(['product_id' => 'reopen-product', 'qty_on_hand' => 5]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => 'reopen-product', 'avg_cost_rupiah' => 40000, 'inventory_value_rupiah' => 200000,
        ]);

        $this->actingAs($admin)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'operational-reopen-create',
            'note' => ['customer_name' => 'Operational reopen', 'transaction_date' => $date],
            'items' => [
                ['entry_mode' => 'product', 'product_lines' => [
                    ['product_id' => 'reopen-product', 'qty' => 1, 'unit_price_rupiah' => 100000],
                ]],
                ['entry_mode' => 'service', 'service' => ['name' => 'Retained service', 'price_rupiah' => 200000]],
            ],
            'inline_payment' => [
                'decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => $date,
                'amount_paid_rupiah' => 300000, 'amount_received_rupiah' => 300000,
            ],
        ])->assertSessionHasNoErrors();

        $noteId = (string) DB::table('notes')->value('id');
        $firstClose = $clock->now()->format('Y-m-d H:i:s');
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'closed', 'closed_at' => $firstClose]);
        self::assertSame(1, DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_closed')->count());
        $productRow = (string) DB::table('work_items')->where('note_id', $noteId)
            ->where('transaction_type', 'store_stock_sale_only')->value('id');

        $clock->time = $clock->time->modify('+10 minutes');
        $this->actingAs($admin)->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
            'selected_row_ids' => [$productRow], 'refunded_at' => $date,
            'reason' => 'Historical component refund', 'idempotency_key' => 'operational-reopen-refund',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'closed', 'reopened_at' => null]);
        self::assertSame(100000, (int) DB::table('customer_refunds')->where('note_id', $noteId)->sum('amount_rupiah'));

        $clock->time = $clock->time->modify('+10 minutes');
        $reopenedAt = $clock->now()->format('Y-m-d H:i:s');
        $this->actingAs($admin)->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
            'base_revision_id' => $this->revisionBaseForTest($noteId),
            'reason' => 'Authorized new receivable after refund',
            'note' => ['customer_name' => 'Operational reopen', 'transaction_date' => $date],
            'items' => [
                ['entry_mode' => 'service', 'service' => ['name' => 'Revised service', 'price_rupiah' => 865000]],
            ],
            'inline_payment' => [
                'decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => $date,
                'amount_paid_rupiah' => 20000, 'amount_received_rupiah' => 20000,
            ],
        ])->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))->assertSessionHasNoErrors();

        $revisionId = (string) DB::table('notes')->where('id', $noteId)->value('current_revision_id');
        $this->assertDatabaseHas('note_revision_settlements', [
            'note_revision_id' => $revisionId, 'settlement_status' => 'underpaid',
            'net_paid_rupiah' => 220000, 'outstanding_rupiah' => 645000,
        ]);
        $this->assertDatabaseHas('notes', [
            'id' => $noteId, 'note_state' => 'open', 'closed_at' => $firstClose,
            'reopened_at' => $reopenedAt, 'reopened_by_actor_id' => $actorId,
        ]);
        $this->assertDatabaseHas('note_mutation_events', [
            'note_id' => $noteId, 'mutation_type' => 'note_reopened', 'actor_id' => $actorId,
        ]);
        self::assertSame(1, DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_closed')->count());

        $clock->time = $clock->time->modify('+10 minutes');
        $payment = app(RecordAndAllocateNotePaymentHandler::class)->handle($noteId, 645000, $date, [], 'cash', 700000);
        self::assertTrue($payment->isSuccess(), $payment->message());
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'outstanding_rupiah' => 0]);
        self::assertSame(865000, (int) DB::table('payment_component_allocations')->where('note_id', $noteId)->sum('allocated_amount_rupiah'));
        self::assertSame(965000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        $this->assertDatabaseHas('notes', [
            'id' => $noteId, 'note_state' => 'closed', 'closed_at' => $clock->now()->format('Y-m-d H:i:s'),
            'closed_by_actor_id' => 'system', 'reopened_at' => $reopenedAt, 'reopened_by_actor_id' => $actorId,
        ]);
        $closes = DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_closed')->orderBy('occurred_at')->get();
        self::assertCount(2, $closes);
        self::assertSame('AUTO_CLOSE_ON_FULL_PAYMENT', $closes[1]->reason);
        self::assertNotSame($closes[0]->id, $closes[1]->id);
        self::assertNotSame($closes[0]->related_customer_payment_id, $closes[1]->related_customer_payment_id);
        self::assertSame($clock->now()->format('Y-m-d H:i:s'), $closes[1]->occurred_at);
        $this->assertDatabaseHas('customer_payments', ['id' => $closes[1]->related_customer_payment_id, 'amount_rupiah' => 645000]);
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'outstanding_rupiah' => 0]);
        self::assertSame(100000, (int) DB::table('customer_refunds')->where('note_id', $noteId)->sum('amount_rupiah'));
    }
}
