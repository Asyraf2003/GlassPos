<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordCustomerPaymentHandler;
use App\Application\Shared\DTO\Result;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentWriterAdapter;
use App\Ports\Out\UuidPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Payment\CustomerPayment\CustomerPaymentCashDetail;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class RecordCustomerPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_customer_payment_handler_stores_new_payment(): void
    {
        $handler = new RecordCustomerPaymentHandler(
            new DatabaseCustomerPaymentWriterAdapter(),
            new class () implements UuidPort {
                public function generate(): string
                {
                    return 'payment-1';
                }
            },
        );

        $result = $handler->handle(
            150000,
            '2026-03-15',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseCount('customer_payments', 1);

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-1',
            'amount_rupiah' => 150000,
            'paid_at' => '2026-03-15',
        ]);
    }

    public function test_record_customer_payment_handler_rejects_zero_amount(): void
    {
        $handler = new RecordCustomerPaymentHandler(
            new DatabaseCustomerPaymentWriterAdapter(),
            new class () implements UuidPort {
                public function generate(): string
                {
                    return 'payment-1';
                }
            },
        );

        $result = $handler->handle(
            0,
            '2026-03-15',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['payment' => ['INVALID_CUSTOMER_PAYMENT']],
            $result->errors(),
        );

        $this->assertDatabaseCount('customer_payments', 0);
    }

    public function test_record_customer_payment_handler_rejects_invalid_paid_at(): void
    {
        $handler = new RecordCustomerPaymentHandler(
            new DatabaseCustomerPaymentWriterAdapter(),
            new class () implements UuidPort {
                public function generate(): string
                {
                    return 'payment-1';
                }
            },
        );

        $result = $handler->handle(
            150000,
            '15-03-2026',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['payment' => ['INVALID_CUSTOMER_PAYMENT']],
            $result->errors(),
        );

        $this->assertDatabaseCount('customer_payments', 0);
    }

    public function test_record_customer_payment_handler_stores_operational_timestamps_on_new_payment(): void
    {
        $handler = new RecordCustomerPaymentHandler(
            new DatabaseCustomerPaymentWriterAdapter(),
            new class () implements UuidPort {
                public function generate(): string
                {
                    return 'payment-timestamp-1';
                }
            },
        );

        $result = $handler->handle(
            150000,
            '2026-03-15',
        );

        $this->assertTrue($result->isSuccess());

        $row = DB::table('customer_payments')
            ->where('id', 'payment-timestamp-1')
            ->first(['created_at', 'updated_at']);

        $this->assertNotNull($row);
        $this->assertNotNull($row->created_at);
        $this->assertNotNull($row->updated_at);
        $this->assertSame($row->created_at, $row->updated_at);
    }

    public function test_customer_payment_writer_stores_operational_timestamps_on_cash_detail(): void
    {
        $payment = CustomerPayment::create(
            'payment-cash-timestamp-1',
            Money::fromInt(150000),
            new DateTimeImmutable('2026-03-15'),
            CustomerPayment::METHOD_CASH,
        );

        $cashDetail = CustomerPaymentCashDetail::create(
            'payment-cash-timestamp-1',
            Money::fromInt(150000),
            Money::fromInt(200000),
        );

        (new DatabaseCustomerPaymentWriterAdapter())->create($payment, $cashDetail);

        $row = DB::table('customer_payment_cash_details')
            ->where('customer_payment_id', 'payment-cash-timestamp-1')
            ->first(['created_at', 'updated_at']);

        $this->assertNotNull($row);
        $this->assertNotNull($row->created_at);
        $this->assertNotNull($row->updated_at);
        $this->assertSame($row->created_at, $row->updated_at);
    }

}
