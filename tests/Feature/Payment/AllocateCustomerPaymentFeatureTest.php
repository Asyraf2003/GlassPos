<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Payment\UseCases\AllocateCustomerPaymentHandler;
use App\Application\Shared\DTO\Result;
use App\Adapters\Out\Note\DatabaseNoteReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteWorkItemDetailLoader;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentReaderAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerRefundReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationReaderAdapter;
use App\Adapters\Out\Payment\Queries\DatabaseNotePaymentAmountByNoteIdQuery;
use App\Adapters\Out\Payment\DatabasePaymentAllocationWriterAdapter;
use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AllocateCustomerPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocate_customer_payment_handler_stores_allocation_and_calculates_partial_outstanding(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 100000);
        $this->seedCustomerPayment('payment-1', 150000, '2026-03-15');

        $handler = $this->buildHandler('allocation-1');
        $result = $handler->handle('payment-1', 'note-1', 40000);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertDatabaseHas('payment_allocations', [
            'id' => 'allocation-1',
            'amount_rupiah' => 40000,
        ]);
    }


    public function test_allocate_customer_payment_handler_stores_operational_timestamps_on_legacy_allocation(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 100000);
        $this->seedCustomerPayment('payment-1', 150000, '2026-03-15');

        $handler = $this->buildHandler('allocation-timestamp-1');
        $result = $handler->handle('payment-1', 'note-1', 40000);

        $this->assertTrue($result->isSuccess());

        $row = DB::table('payment_allocations')
            ->where('id', 'allocation-timestamp-1')
            ->first(['created_at', 'updated_at']);

        $this->assertNotNull($row);
        $this->assertNotNull($row->created_at);
        $this->assertNotNull($row->updated_at);
        $this->assertSame($row->created_at, $row->updated_at);
    }

    public function test_allocate_customer_payment_handler_rejects_invalid_target(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 100000);

        $handler = $this->buildHandler('allocation-1');
        $result = $handler->handle('payment-missing', 'note-1', 50000);

        $this->assertTrue($result->isFailure());
        $this->assertSame(['payment' => ['PAYMENT_INVALID_TARGET']], $result->errors());
    }


    public function test_allocate_customer_payment_allows_replacement_payment_after_refund(): void
    {
        $this->seedNote('note-refund-allocate-1', 'Budi Refund Allocate', '2026-03-14', 100000);
        $this->seedCustomerPayment('payment-existing-refund-allocate-1', 100000, '2026-03-15');
        $this->seedCustomerPayment('payment-replacement-refund-allocate-1', 40000, '2026-03-16');

        DB::table('payment_allocations')->insert([
            'id' => 'allocation-existing-refund-allocate-1',
            'customer_payment_id' => 'payment-existing-refund-allocate-1',
            'note_id' => 'note-refund-allocate-1',
            'amount_rupiah' => 100000,
        ]);

        DB::table('customer_refunds')->insert([
            'id' => 'refund-refund-allocate-1',
            'customer_payment_id' => 'payment-existing-refund-allocate-1',
            'note_id' => 'note-refund-allocate-1',
            'amount_rupiah' => 40000,
            'refunded_at' => '2026-03-16',
            'reason' => 'Refund sebagian sebelum allocate ulang',
        ]);

        $handler = $this->buildHandler('allocation-replacement-refund-allocate-1');
        $result = $handler->handle(
            'payment-replacement-refund-allocate-1',
            'note-refund-allocate-1',
            40000
        );

        $this->assertTrue($result->isSuccess(), $result->message() ?? 'Allocation should succeed after refund reopens outstanding.');

        $this->assertDatabaseHas('payment_allocations', [
            'id' => 'allocation-replacement-refund-allocate-1',
            'customer_payment_id' => 'payment-replacement-refund-allocate-1',
            'note_id' => 'note-refund-allocate-1',
            'amount_rupiah' => 40000,
        ]);

        self::assertSame(
            140000,
            (int) DB::table('payment_allocations')
                ->where('note_id', 'note-refund-allocate-1')
                ->sum('amount_rupiah')
        );

        self::assertSame(
            40000,
            (int) DB::table('customer_refunds')
                ->where('note_id', 'note-refund-allocate-1')
                ->sum('amount_rupiah')
        );

        self::assertSame(
            100000,
            (int) DB::table('payment_allocations')
                ->where('note_id', 'note-refund-allocate-1')
                ->sum('amount_rupiah')
                - (int) DB::table('customer_refunds')
                    ->where('note_id', 'note-refund-allocate-1')
                    ->sum('amount_rupiah')
        );
    }


    private function buildHandler(string $allocationId): AllocateCustomerPaymentHandler
    {
        return new AllocateCustomerPaymentHandler(
            new DatabaseCustomerPaymentReaderAdapter(),
            new DatabasePaymentAllocationReaderAdapter(new DatabaseNotePaymentAmountByNoteIdQuery()),
            new DatabaseCustomerRefundReaderAdapter(),
            new DatabasePaymentAllocationWriterAdapter(),
            new DatabaseNoteReaderAdapter(
                new DatabaseNoteWorkItemDetailLoader(),
            ),
            new PaymentAllocationPolicy(),
            new class () implements TransactionManagerPort {
                public function begin(): void
                {
                    DB::beginTransaction();
                }

                public function commit(): void
                {
                    DB::commit();
                }

                public function rollBack(): void
                {
                    DB::rollBack();
                }
            },
            new class ($allocationId) implements UuidPort {
                public function __construct(
                    private readonly string $id,
                ) {
                }

                public function generate(): string
                {
                    return $this->id;
                }
            },
            new AllocatePaymentErrorClassifier(),
            new class () implements AuditLogPort {
                public function record(string $event, array $context = []): void
                {
                }
            }
        );
    }

    private function seedNote(string $id, string $name, string $date, int $total): void
    {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $name,
            'transaction_date' => $date,
            'total_rupiah' => $total,
        ]);
    }

    private function seedCustomerPayment(string $id, int $amount, string $paidAt): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amount,
            'paid_at' => $paidAt,
        ]);
    }
}
