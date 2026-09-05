<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNotePaymentTimelineChainFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_three_payment_events_remain_visible_newest_first_with_exact_financial_detail(): void
    {
        $user = $this->seedCashierAndNote();
        $today = date('Y-m-d');
        $route = route('cashier.notes.payments.store', ['noteId' => 'note-payment-timeline']);
        $selectedRows = ['work-payment-timeline::service_fee::work-payment-timeline'];

        Carbon::setTestNow($today.' 10:00:00');
        $this->actingAs($user)->post($route, [
            'selected_row_ids' => $selectedRows,
            'payment_scope' => 'partial',
            'payment_method' => 'cash',
            'amount_paid' => 100000,
            'amount_received' => 120000,
            'paid_at' => $today,
            'idempotency_key' => 'timeline-payment-100',
        ])->assertSessionHasNoErrors();

        Carbon::setTestNow($today.' 12:00:00');
        $this->actingAs($user)->post($route, [
            'selected_row_ids' => $selectedRows,
            'payment_scope' => 'partial',
            'payment_method' => 'transfer',
            'amount_paid' => 150000,
            'paid_at' => $today,
            'idempotency_key' => 'timeline-payment-150',
        ])->assertSessionHasNoErrors();

        Carbon::setTestNow($today.' 14:00:00');
        $this->actingAs($user)->post($route, [
            'selected_row_ids' => $selectedRows,
            'payment_method' => 'cash',
            'amount_paid' => 250000,
            'amount_received' => 300000,
            'paid_at' => $today,
            'idempotency_key' => 'timeline-payment-250',
        ])->assertSessionHasNoErrors();

        self::assertSame(3, DB::table('customer_payments')->count());
        self::assertSame(500000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(3, DB::table('payment_component_allocations')->count());
        self::assertSame(500000, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 120000,
            'amount_received_rupiah' => 120000,
            'change_rupiah' => 0,
        ]);
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 230000,
            'amount_received_rupiah' => 300000,
            'change_rupiah' => 70000,
        ]);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => 'note-payment-timeline',
            'net_paid_rupiah' => 500000,
            'outstanding_rupiah' => 0,
        ]);

        $data = app(NoteDetailPageDataBuilder::class)->build('note-payment-timeline');
        self::assertNotNull($data);
        $timeline = $data['note']['payment_timeline'];

        self::assertSame([230000, 150000, 120000], array_column($timeline, 'payment_amount_rupiah'));
        self::assertSame(['Pelunasan', 'Bayar Sebagian', 'Bayar Sebagian'], array_column($timeline, 'semantic_label'));
        self::assertSame([0, 230000, 380000], array_column($timeline, 'remaining_after_rupiah'));
        self::assertSame(['cash', 'transfer', 'cash'], array_column($timeline, 'payment_method'));
        self::assertSame(300000, $timeline[0]['amount_received_rupiah']);
        self::assertSame(70000, $timeline[0]['change_rupiah']);

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-payment-timeline']))
            ->assertOk()
            ->assertSeeInOrder([
                'data-payment-amount-rupiah="230000"',
                'data-payment-amount-rupiah="150000"',
                'data-payment-amount-rupiah="120000"',
            ], false)
            ->assertSee('Diterima Rp 300.000')
            ->assertSee('Kembalian Rp 70.000')
            ->assertSee('Transfer')
            ->assertSee('Sisa Rp 0');
    }

    private function seedCashierAndNote(): User
    {
        $user = $this->loginAsKasir();
        $today = date('Y-m-d');

        Carbon::setTestNow($today.' 09:00:00');
        $this->seedNoteBase('note-payment-timeline', 'Pelanggan Timeline', $today, 500000);
        $this->seedWorkItemBase(
            'work-payment-timeline',
            'note-payment-timeline',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            500000,
        );
        $this->seedServiceDetailBase('work-payment-timeline', 'Servis Timeline', 500000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceOnlyCurrentRevision(
            'note-payment-timeline',
            'note-payment-timeline-r001',
            'work-payment-timeline',
            'Pelanggan Timeline',
            $today,
            500000,
            'Servis Timeline',
            500000,
        );

        return $user;
    }
}
