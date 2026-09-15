<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\In\Http\Requests\Note\StoreTransactionWorkspacePaymentValidator;
use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentAmountResolver;
use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentRecorder;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class WorkspaceFullCashPayableBoundaryFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    #[DataProvider('cashTenders')]
    public function test_full_cash_uses_backend_payable_after_existing_payment(int $received, bool $accepted): void
    {
        $this->seedNoteBase('payable-note', 'Payable QA', '2026-09-15', 857500);
        $this->seedWorkItemBase('payable-service', 'payable-note', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 857500);
        $this->seedServiceDetailBase('payable-service', 'Service', 857500, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('existing-payment', 293500, '2026-09-14');
        $this->seedPaymentAllocationBase('existing-allocation', 'existing-payment', 'payable-note', 293500);
        $note = app(NoteReaderPort::class)->getById('payable-note');
        self::assertNotNull($note);
        $payment = [
            'decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => '2026-09-15',
            // A forged client amount must not determine full-payment credit.
            'amount_paid_rupiah' => 1, 'amount_received_rupiah' => $received,
        ];
        self::assertSame(564000, app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, $payment));
        $validator = Validator::make([], []);
        StoreTransactionWorkspacePaymentValidator::validate([
            'items' => [[
                'entry_mode' => 'service', 'part_source' => 'none',
                'service' => ['name' => 'Service', 'price_rupiah' => 857500],
                'product_lines' => [], 'external_purchase_lines' => [],
            ]],
            'inline_payment' => $payment,
        ], $validator);
        self::assertSame([], $validator->errors()->toArray(), 'Request must defer to backend payable.');

        if (! $accepted) {
            try {
                app(CreateTransactionWorkspaceInlinePaymentRecorder::class)->record($note, $payment);
                self::fail('Cash below backend payable must be rejected.');
            } catch (DomainException $exception) {
                self::assertSame('Uang diterima cash tidak boleh kurang dari nominal dibayar.', $exception->getMessage());
            }
            self::assertSame(1, DB::table('customer_payments')->count());
            self::assertSame(0, DB::table('customer_payment_cash_details')->count());
            self::assertSame(0, DB::table('payment_component_allocations')->count());

            return;
        }

        $summary = app(CreateTransactionWorkspaceInlinePaymentRecorder::class)->record($note, $payment);
        self::assertSame(564000, $summary['amount_paid_rupiah']);
        self::assertSame($received - 564000, $summary['change_rupiah']);
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 564000, 'amount_received_rupiah' => $received,
            'change_rupiah' => $received - 564000,
        ]);
        self::assertSame(857500, (int) DB::table('customer_payments')->sum('amount_rupiah'));
    }

    public static function cashTenders(): array
    {
        return [
            'manual QA 600000' => [600000, true],
            'exact payable' => [564000, true],
            'one rupiah short' => [563999, false],
        ];
    }
}
