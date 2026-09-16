<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\BuildCreateNoteRevisionSettlement;
use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentAmountResolver;
use App\Application\Note\Services\NoteOutstandingPaymentAmountResolver;
use App\Application\Note\Services\NotePaymentSettlementPreviewResolver;
use App\Application\Note\Services\NoteReplacementPaymentAllocationReconciler;
use App\Ports\Out\ClockPort;
use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Ports\Out\Note\NoteReaderPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProductFixture;
use Tests\TestCase;

final class PrimitiveSettlementSourceParityFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProductFixture;

    public function test_d05_second_rebuild_conserves_surviving_settlement_after_ordinary_refund(): void
    {
        $clock = new class implements ClockPort
        {
            public DateTimeImmutable $time;

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };
        $date = date('Y-m-d');
        $clock->time = new DateTimeImmutable($date.' 09:00:00+08:00');
        $this->app->instance(ClockPort::class, $clock);
        $this->loginAsAuthorizedAdmin();
        $this->seedMinimalProduct('d05-product', 'D05-P', 'D05 refundable product', 'QA', null, 47513);
        DB::table('product_inventory')->insert(['product_id' => 'd05-product', 'qty_on_hand' => 17]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => 'd05-product', 'avg_cost_rupiah' => 19721, 'inventory_value_rupiah' => 335257,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 'd05-opening', 'product_id' => 'd05-product', 'movement_type' => 'stock_in',
            'source_type' => 'test_opening_balance', 'source_id' => 'd05-opening',
            'tanggal_mutasi' => $date, 'qty_delta' => 17,
            'unit_cost_rupiah' => 19721, 'total_cost_rupiah' => 335257,
        ]);

        $this->post(route('notes.workspace.store'), [
            'idempotency_key' => 'd05-create',
            'note' => ['customer_name' => 'D05 settlement conservation', 'transaction_date' => $date],
            'items' => [
                ['entry_mode' => 'product', 'product_lines' => [
                    ['product_id' => 'd05-product', 'qty' => 1, 'unit_price_rupiah' => 47513],
                ]],
                ['entry_mode' => 'service', 'service' => ['name' => 'Surviving service', 'price_rupiah' => 163719]],
            ],
            'inline_payment' => [
                'decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => $date,
                'amount_paid_rupiah' => 211232, 'amount_received_rupiah' => 220003,
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $paymentId = (string) DB::table('customer_payments')->value('id');
        $productRow = (string) DB::table('work_items')->where('note_id', $noteId)
            ->where('transaction_type', 'store_stock_sale_only')->value('id');
        $stockLine = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $productRow)->value('id');
        $paymentRows = $this->rows('customer_payments');
        $cashRows = $this->rows('customer_payment_cash_details');
        self::assertCount(1, $paymentRows);
        self::assertSame(211232, (int) $paymentRows[0]['amount_rupiah']);
        self::assertSame(211232, $this->allocated($noteId));

        $clock->time = $clock->time->modify('+1 minute');
        $this->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
            'selected_row_ids' => [$productRow], 'refunded_at' => $date,
            'reason' => 'D05 ordinary product refund', 'idempotency_key' => 'd05-refund',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $refundRows = $this->rows('customer_refunds');
        $refundAllocations = $this->rows('refund_component_allocations');
        self::assertCount(1, $refundRows);
        self::assertCount(1, $refundAllocations);
        self::assertSame(47513, (int) $refundRows[0]['amount_rupiah']);
        self::assertSame($paymentId, $refundRows[0]['customer_payment_id']);
        self::assertSame(47513, (int) $refundAllocations[0]['refunded_amount_rupiah']);
        self::assertSame($productRow, $refundAllocations[0]['component_ref_id']);
        self::assertSame([$paymentId => 163719], app(NoteReplacementPaymentAllocationReconciler::class)->captureAllocatedAmounts($noteId));
        $movements = $this->rows('inventory_movements');
        self::assertCount(3, $movements); // Opening, one issue, one ordinary refund return.
        self::assertSame(1, DB::table('inventory_movements')->where('source_id', $stockLine)
            ->where('source_type', 'work_item_store_stock_line_reversal')->count());
        $history = [];

        foreach ([2 => 181258, 3 => 199487] as $revision => $gross) {
            $oldRevisions = $this->rows('note_revisions');
            $oldLines = $this->rows('note_revision_lines');
            $clock->time = $clock->time->modify('+1 minute');
            $this->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
                'idempotency_key' => 'd05-revision-'.$revision,
                'reason' => 'D05 service price increase '.$revision,
                'note' => ['customer_name' => 'D05 settlement conservation', 'transaction_date' => $date],
                'items' => [
                    ['entry_mode' => 'service', 'service' => ['name' => 'Surviving service', 'price_rupiah' => $gross]],
                ],
                'inline_payment' => ['decision' => 'skip'],
            ])->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))->assertSessionHasNoErrors();

            // Capture actual named owners independently; no shared expected-formula helper.
            $history['R'.$revision] = $this->sourceEvidence($noteId, $gross, $clock->now());
            self::assertSame($paymentRows, $this->rows('customer_payments'), 'Accepted historical money is immutable.');
            self::assertSame($cashRows, $this->rows('customer_payment_cash_details'), 'Tender/change are immutable.');
            self::assertSame($refundRows, $this->rows('customer_refunds'), 'No new or rewritten ordinary refund.');
            self::assertSame($refundAllocations, $this->rows('refund_component_allocations'), 'Refund source identities are immutable.');
            self::assertSame($movements, $this->rows('inventory_movements'), 'Service-only revisions must not repeat the stock return.');
            foreach (['note_revisions' => $oldRevisions, 'note_revision_lines' => $oldLines] as $table => $rows) {
                self::assertSame($rows, DB::table($table)->whereIn('id', array_column($rows, 'id'))
                    ->orderBy('id')->get()->map(static fn ($row): array => (array) $row)->all());
            }
            self::assertSame($revision, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
            self::assertSame(0, DB::table('note_revision_surplus_dispositions')->count());
            self::assertSame(0, DB::table('note_revision_surplus_refund_payments')->count());
            $allocations = $history['R'.$revision]['allocation_rows'];
            self::assertCount(1, $allocations);
            self::assertSame($paymentId, $allocations[0]['customer_payment_id']);
            self::assertNotSame($productRow, $allocations[0]['work_item_id']);
            $currentRevision = (string) DB::table('notes')->where('id', $noteId)->value('current_revision_id');
            self::assertSame($allocations[0]['work_item_id'], DB::table('note_revision_lines')
                ->where('note_revision_id', $currentRevision)->value('work_item_root_id'));

            // ADR-0045: two price-only revisions cannot consume additional customer money.
            // R2 establishes the first net replay; R3 must conserve that same surviving payment.
            self::assertSame(163719, $this->allocated($noteId),
                'D05 R'.$revision.' allocation conservation: '.json_encode($history, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        }
        foreach ([4 => 151983, 5 => 190007] as $revision => $gross) {
            $clock->time = $clock->time->modify('+1 minute');
            $this->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
                'idempotency_key' => 'd05-surplus-revision-'.$revision,
                'reason' => 'Slice 1 surplus carry '.$revision,
                'note' => ['customer_name' => 'D05 settlement conservation', 'transaction_date' => $date],
                'items' => [['entry_mode' => 'service', 'service' => ['name' => 'Surviving service', 'price_rupiah' => $gross]]],
                'inline_payment' => ['decision' => 'skip'],
            ])->assertRedirect()->assertSessionHasNoErrors();
            self::assertSame(151983, $this->allocated($noteId), 'Surplus clipping must remain unavailable on later revision.');
            self::assertSame(1, DB::table('note_revision_surplus_dispositions')->count());
            self::assertSame(1, DB::table('note_revision_surplus_refund_payments')->count());
            self::assertSame(11736, (int) DB::table('note_revision_surplus_refund_payments')->sum('amount_rupiah'));
            self::assertSame($paymentRows, $this->rows('customer_payments'));
            self::assertSame($refundRows, $this->rows('customer_refunds'));
            self::assertSame($refundAllocations, $this->rows('refund_component_allocations'));
        }
        $surplusEvidence = $this->sourceEvidence($noteId, 190007, $clock->now());
        foreach (['S05' => $surplusEvidence['S05']['outstanding'],
            'S06' => $surplusEvidence['S06']['data']['outstanding_rupiah'],
            'S07_preview' => $surplusEvidence['S07_preview']['outstanding_rupiah'],
            'S07_inline' => $surplusEvidence['S07_inline_full']] as $owner => $actual) {
            self::assertSame(38024, $actual, $owner.' after paid surplus: '.json_encode($surplusEvidence, JSON_THROW_ON_ERROR));
        }
        $clock->time = $clock->time->modify('+1 minute');
        $partial = app(RecordAndAllocateNotePaymentHandler::class)->handle($noteId, 26288, $date, [], 'cash', 30007);
        self::assertTrue($partial->isSuccess(), $partial->message());
        self::assertSame(178271, $this->allocated($noteId));
        self::assertSame(11736, app(NoteOutstandingPaymentAmountResolver::class)->resolveFull($noteId)->data()['outstanding_rupiah']);
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open']);
    }

    private function allocated(string $noteId): int
    {
        return (int) DB::table('payment_component_allocations')->where('note_id', $noteId)->sum('allocated_amount_rupiah');
    }

    private function rows(string $table): array
    {
        $key = $table === 'customer_payment_cash_details' ? 'customer_payment_id' : 'id';

        return DB::table($table)->orderBy($key)->get()->map(static fn ($row): array => (array) $row)->all();
    }

    private function sourceEvidence(string $noteId, int $gross, DateTimeImmutable $at): array
    {
        $revisionId = (string) DB::table('notes')->where('id', $noteId)->value('current_revision_id');
        $s05 = app(BuildCreateNoteRevisionSettlement::class)->build('d05-probe', $revisionId, $noteId, $gross, $at);
        $s06 = app(NoteOutstandingPaymentAmountResolver::class)->resolveFull($noteId);
        $s07 = app(NotePaymentSettlementPreviewResolver::class)->preview($noteId);
        $note = app(NoteReaderPort::class)->getById($noteId);
        self::assertNotNull($note);

        return [
            'S05' => ['paid' => $s05->carryForwardPaidRupiah, 'refunded' => $s05->carryForwardRefundedRupiah,
                'net' => $s05->netPaidRupiah, 'outstanding' => $s05->outstandingRupiah],
            'S06' => ['success' => $s06->isSuccess(), 'data' => $s06->data()],
            'S07_preview' => $s07->data(),
            'S07_inline_full' => app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, ['decision' => 'pay_full']),
            'S10_next_capture_by_payment' => app(NoteReplacementPaymentAllocationReconciler::class)->captureAllocatedAmounts($noteId),
            'allocation_rows' => $this->rows('payment_component_allocations'),
            'legacy_allocation_rows' => $this->rows('payment_allocations'),
            'payment_rows' => $this->rows('customer_payments'),
            'refund_rows' => $this->rows('customer_refunds'),
            'refund_allocation_rows' => $this->rows('refund_component_allocations'),
        ];
    }
}
