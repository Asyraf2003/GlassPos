<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteRevisionSettlementCarryForwardFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_revision_after_partial_payment_carries_paid_amount_into_underpaid_settlement(): void
    {
        $this->seedServiceOnlyNoteWithComponentPayment(
            noteId: 'note-carry-001',
            currentRevisionId: 'note-carry-001-r001',
            workItemId: 'wi-carry-001',
            paymentId: 'payment-carry-001',
            legacyAllocationId: 'payment-allocation-carry-001',
            componentAllocationId: 'pca-carry-001',
            customerName: 'Budi Carry Partial',
            transactionDate: '2026-05-20',
            noteTotalRupiah: 100000,
            paidRupiah: 40000,
        );

        $result = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-carry-001',
            array_replace($this->revisionPayload(
                customerName: 'Budi Carry Partial Revised',
                transactionDate: '2026-05-21',
                serviceName: 'Servis Carry Partial Revised',
                servicePriceRupiah: 120000,
            ), ['base_revision_id' => $this->revisionBaseForTest('note-carry-001')]),
            'admin-test-001',
            false,
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-carry-001-r002-settlement',
            'note_revision_id' => 'note-carry-001-r002',
            'note_root_id' => 'note-carry-001',
            'gross_total_rupiah' => 120000,
            'carry_forward_paid_rupiah' => 40000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 40000,
            'outstanding_rupiah' => 80000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);
    }

    public function test_revision_after_ordinary_refund_counts_refund_once_in_settlement(): void
    {
        $this->seedServiceOnlyNoteWithComponentPayment(
            noteId: 'note-carry-refund-001',
            currentRevisionId: 'note-carry-refund-001-r001',
            workItemId: 'wi-carry-refund-001',
            paymentId: 'payment-carry-refund-001',
            legacyAllocationId: 'payment-allocation-carry-refund-001',
            componentAllocationId: 'pca-carry-refund-001',
            customerName: 'Budi Carry Refund',
            transactionDate: '2026-05-20',
            noteTotalRupiah: 100000,
            paidRupiah: 100000,
            noteState: 'closed',
        );

        $this->seedOrdinaryRefundComponentAllocation(
            refundId: 'refund-carry-refund-001',
            componentRefundAllocationId: 'rca-carry-refund-001',
            paymentId: 'payment-carry-refund-001',
            noteId: 'note-carry-refund-001',
            workItemId: 'wi-carry-refund-001',
            refundedAt: '2026-05-20',
            refundedRupiah: 30000,
        );

        $result = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-carry-refund-001',
            array_replace($this->revisionPayload(
                customerName: 'Budi Carry Refund Revised',
                transactionDate: '2026-05-21',
                serviceName: 'Servis Carry Refund Revised',
                servicePriceRupiah: 70000,
            ), ['base_revision_id' => $this->revisionBaseForTest('note-carry-refund-001')]),
            'admin-test-001',
            false,
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-carry-refund-001-r002-settlement',
            'note_revision_id' => 'note-carry-refund-001-r002',
            'note_root_id' => 'note-carry-refund-001',
            'gross_total_rupiah' => 70000,
            'carry_forward_paid_rupiah' => 100000,
            'carry_forward_refunded_rupiah' => 30000,
            'net_paid_rupiah' => 70000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 0,
            'settlement_status' => 'paid',
        ]);
    }

    private function seedServiceOnlyNoteWithComponentPayment(
        string $noteId,
        string $currentRevisionId,
        string $workItemId,
        string $paymentId,
        string $legacyAllocationId,
        string $componentAllocationId,
        string $customerName,
        string $transactionDate,
        int $noteTotalRupiah,
        int $paidRupiah,
        string $noteState = 'open',
    ): void {
        $this->seedNoteBase($noteId, $customerName, $transactionDate, $noteTotalRupiah, $noteState);

        $this->seedWorkItemBase(
            $workItemId,
            $noteId,
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            $noteTotalRupiah,
        );

        $this->seedServiceDetailBase(
            $workItemId,
            'Servis Carry Forward Fixture',
            $noteTotalRupiah,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedServiceOnlyCurrentRevision(
            $noteId,
            $currentRevisionId,
            $workItemId,
            $customerName,
            $transactionDate,
            $noteTotalRupiah,
            'Servis Carry Forward Fixture',
            $noteTotalRupiah,
        );

        $this->seedCustomerPaymentBase($paymentId, $paidRupiah, $transactionDate);
        $this->seedPaymentAllocationBase($legacyAllocationId, $paymentId, $noteId, $paidRupiah);

        DB::table('payment_component_allocations')->insert([
            'id' => $componentAllocationId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => $noteTotalRupiah,
            'allocated_amount_rupiah' => $paidRupiah,
            'allocation_priority' => 1,
        ]);
    }

    private function seedOrdinaryRefundComponentAllocation(
        string $refundId,
        string $componentRefundAllocationId,
        string $paymentId,
        string $noteId,
        string $workItemId,
        string $refundedAt,
        int $refundedRupiah,
    ): void {
        DB::table('customer_refunds')->insert([
            'id' => $refundId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $refundedRupiah,
            'refunded_at' => $refundedAt,
            'reason' => 'Ordinary refund carry-forward characterization fixture.',
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => $componentRefundAllocationId,
            'customer_refund_id' => $refundId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $workItemId,
            'refunded_amount_rupiah' => $refundedRupiah,
            'refund_priority' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function revisionPayload(
        string $customerName,
        string $transactionDate,
        string $serviceName,
        int $servicePriceRupiah,
    ): array {
        return [
            'reason' => 'Carry-forward settlement characterization.',
            'note' => [
                'customer_name' => $customerName,
                'customer_phone' => '08123456789',
                'transaction_date' => $transactionDate,
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => $serviceName,
                        'price_rupiah' => $servicePriceRupiah,
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }
}
