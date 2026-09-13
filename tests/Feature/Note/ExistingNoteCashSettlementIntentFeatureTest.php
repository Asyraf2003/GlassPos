<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ExistingNoteCashSettlementIntentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_partial_cash_intent_and_later_settlement_preserve_two_tenders_and_credited_history(): void
    {
        $cashier = $this->loginAsKasir();
        $date = '2026-09-13';
        Carbon::setTestNow($date.' 09:00:00');
        $this->seedNoteBase('intent-note', 'Cash intent', $date, 100000);
        $this->seedWorkItemBase('intent-service', 'intent-note', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 100000);
        $this->seedServiceDetailBase('intent-service', 'Service', 100000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceOnlyCurrentRevision('intent-note', 'intent-note-r001', 'intent-service', 'Cash intent', $date, 100000, 'Service', 100000);
        $route = route('cashier.notes.payments.store', ['noteId' => 'intent-note']);
        $first = [
            'selected_row_ids' => ['intent-service::service_fee::intent-service'],
            'payment_scope' => 'partial', 'payment_method' => 'cash', 'paid_at' => $date,
            'amount_paid' => 20000, 'amount_received' => 100000, 'idempotency_key' => 'intent-first',
        ];
        $this->actingAs($cashier)->post($route, $first)->assertSessionHasNoErrors();
        self::assertSame(20000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        $firstPaymentId = (string) DB::table('customer_payments')->value('id');
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $firstPaymentId,
            'amount_paid_rupiah' => 20000, 'amount_received_rupiah' => 100000, 'change_rupiah' => 80000,
        ]);
        self::assertSame(20000, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $this->assertDatabaseHas('note_history_projection', ['note_id' => 'intent-note', 'net_paid_rupiah' => 20000, 'outstanding_rupiah' => 80000]);

        // Both intent and tender are semantic idempotency inputs.
        $this->actingAs($cashier)->post($route, $first)->assertSessionHasNoErrors();
        foreach ([['amount_paid' => 10000], ['amount_received' => 50000]] as $mutation) {
            $this->actingAs($cashier)->post($route, array_replace($first, $mutation))->assertSessionHasErrors(['payment']);
        }
        self::assertSame(1, DB::table('customer_payments')->count());

        Carbon::setTestNow($date.' 10:00:00');
        $second = array_replace($first, ['amount_paid' => 80000, 'idempotency_key' => 'intent-second']);
        $this->actingAs($cashier)->post($route, $second)->assertSessionHasNoErrors();
        $this->actingAs($cashier)->post($route, $second)->assertSessionHasNoErrors();
        self::assertSame(2, DB::table('customer_payments')->count());
        self::assertSame(100000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(100000, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $secondPaymentId = (string) DB::table('customer_payments')->where('id', '<>', $firstPaymentId)->value('id');
        $this->assertDatabaseHas('customer_payments', ['id' => $secondPaymentId, 'amount_rupiah' => 80000]);
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $secondPaymentId,
            'amount_paid_rupiah' => 80000, 'amount_received_rupiah' => 100000, 'change_rupiah' => 20000,
        ]);
        $this->assertDatabaseHas('note_history_projection', ['note_id' => 'intent-note', 'net_paid_rupiah' => 100000, 'outstanding_rupiah' => 0]);
        $timeline = app(NoteDetailPageDataBuilder::class)->build('intent-note')['note']['payment_timeline'];
        self::assertSame([80000, 20000], array_column($timeline, 'payment_amount_rupiah'));
        self::assertSame([100000, 100000], array_column($timeline, 'amount_received_rupiah'));
        self::assertSame([20000, 80000], array_column($timeline, 'change_rupiah'));
        self::assertSame([0, 80000], array_column($timeline, 'remaining_after_rupiah'));
        $this->actingAs($cashier)->get(route('cashier.notes.show', ['noteId' => 'intent-note']))->assertOk()
            ->assertSeeInOrder(['data-payment-amount-rupiah="80000"', 'data-payment-amount-rupiah="20000"'], false);

        $ledger = app(TransactionCashLedgerReportingQuery::class)->reconciliation($date, $date);
        self::assertSame(100000, $ledger['cash_in_rupiah']);
        self::assertSame(0, $ledger['total_out_rupiah']);
        $report = app(GetTransactionReportDatasetHandler::class)->handle($date, $date);
        self::assertTrue($report->isSuccess());
        self::assertSame(100000, $report->data()['summary']['net_cash_collected_rupiah']);
        $profit = app(GetOperationalProfitSummaryHandler::class)->handle($date, $date);
        self::assertTrue($profit->isSuccess());
        self::assertSame(100000, $profit->data()['row']['cash_in_rupiah']);
    }

    #[DataProvider('paymentBoundaries')]
    public function test_payment_presets_and_boundaries_use_the_same_credited_settlement(string $method, ?int $intent, ?int $tender, ?string $scope, ?int $expectedCredit): void
    {
        $cashier = $this->loginAsKasir();
        $date = '2026-09-13';
        $this->seedNoteBase('boundary-note', 'Payment boundary', $date, 100000);
        $this->seedWorkItemBase('boundary-service', 'boundary-note', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 100000);
        $this->seedServiceDetailBase('boundary-service', 'Service', 100000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceOnlyCurrentRevision('boundary-note', 'boundary-note-r001', 'boundary-service', 'Payment boundary', $date, 100000, 'Service', 100000);
        $response = $this->actingAs($cashier)->post(route('cashier.notes.payments.store', ['noteId' => 'boundary-note']), [
            'selected_row_ids' => ['boundary-service'], 'payment_method' => $method, 'payment_scope' => $scope,
            'amount_paid' => $intent, 'amount_received' => $tender, 'paid_at' => $date,
        ]);
        if ($expectedCredit === null) {
            $response->assertSessionHasErrors();
            self::assertSame(0, DB::table('customer_payments')->count());
            self::assertSame(0, DB::table('customer_payment_cash_details')->count());
            self::assertSame(0, DB::table('payment_component_allocations')->count());

            return;
        }
        $response->assertSessionHasNoErrors();
        self::assertSame($expectedCredit, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        $this->assertDatabaseHas('note_history_projection', ['note_id' => 'boundary-note', 'outstanding_rupiah' => 100000 - $expectedCredit]);
        if ($method === 'cash') {
            $this->assertDatabaseHas('customer_payment_cash_details', [
                'amount_paid_rupiah' => $expectedCredit, 'amount_received_rupiah' => $tender, 'change_rupiah' => $tender - $expectedCredit,
            ]);
        } else {
            self::assertSame(0, DB::table('customer_payment_cash_details')->count());
        }
    }

    public static function paymentBoundaries(): array
    {
        return [
            'simple partial exact cash' => ['cash', 20000, 20000, 'partial', 20000],
            'simple full preset' => ['cash', null, 100000, null, 100000],
            'full over tender' => ['cash', 100000, 150000, null, 100000],
            'transfer partial' => ['transfer', 20000, null, 'partial', 20000],
            'cash intent above outstanding' => ['cash', 100001, 150000, 'partial', null],
            'transfer above outstanding' => ['transfer', 100001, null, 'partial', null],
            'insufficient tender' => ['cash', 20000, 19000, 'partial', null],
            'partial intent missing' => ['cash', null, 100000, 'partial', null],
        ];
    }
}
