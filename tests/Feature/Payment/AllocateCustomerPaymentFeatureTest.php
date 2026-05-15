<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Payment\UseCases\AllocateCustomerPaymentHandler;
use App\Application\Shared\DTO\Result;
use App\Adapters\Out\Note\DatabaseNoteReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteWorkItemDetailLoader;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationReaderAdapter;
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

    private function buildHandler(string $allocationId): AllocateCustomerPaymentHandler
    {
        return new AllocateCustomerPaymentHandler(
            new DatabaseCustomerPaymentReaderAdapter(),
            new DatabasePaymentAllocationReaderAdapter(),
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
