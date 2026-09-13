<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\BuildCreateNoteRevisionSettlement;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RevisionSettlementHistoricalPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_revision_carries_payment_preserved_only_by_refund_history_after_allocation_replacement(): void
    {
        $this->seedNoteBase('note-history', 'Historical payer', '2026-09-13', 150000, 'closed');
        $this->seedCustomerPaymentBase('old-cash', 300000, '2026-09-11');
        $this->seedCustomerPaymentBase('later-transfer', 580000, '2026-09-12');
        $this->seedCustomerPaymentBase('unrelated', 900000, '2026-09-12');
        // Replacement retains only the later payment's current allocation.
        $this->seedPaymentAllocationBase('current', 'later-transfer', 'note-history', 150000);
        foreach ([['cash-refund-1', 'old-cash', 60000], ['cash-refund-2', 'old-cash', 40000], ['transfer-refund', 'later-transfer', 340000]] as [$id, $paymentId, $amount]) {
            DB::table('customer_refunds')->insert([
                'id' => $id,
                'customer_payment_id' => $paymentId,
                'note_id' => 'note-history',
                'amount_rupiah' => $amount,
                'refunded_at' => '2026-09-12',
                'reason' => 'Stock refund before replacement',
            ]);
        }

        $settlement = app(BuildCreateNoteRevisionSettlement::class)->build(
            'settlement-history', 'revision-history', 'note-history', 150000,
            new DateTimeImmutable('2026-09-13'),
        );

        self::assertSame(880000, $settlement->carryForwardPaidRupiah);
        self::assertSame(440000, $settlement->carryForwardRefundedRupiah);
        self::assertSame(440000, $settlement->netPaidRupiah);
        self::assertSame(0, $settlement->outstandingRupiah);
        self::assertSame(290000, $settlement->surplusRupiah);
    }
}
