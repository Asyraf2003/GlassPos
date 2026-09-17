<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionReportRevisionCashTruthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_downward_revision_reports_historical_cash_less_actual_surplus_refund_once(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();
        $payload = [
            '_actor_id' => (string) $admin->getAuthIdentifier(),
            'note' => ['customer_name' => 'Revision cash truth', 'customer_phone' => null, 'transaction_date' => '2026-09-11'],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => ['name' => 'Service', 'price_rupiah' => 300000, 'notes' => null],
                'product_lines' => [],
                'external_purchase_lines' => [],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => '2026-09-11',
                'amount_paid_rupiah' => 300000, 'amount_received_rupiah' => 350000,
            ],
        ];
        $created = app(CreateTransactionWorkspaceHandler::class)->handle($payload);
        self::assertTrue($created->isSuccess(), (string) $created->message());
        $noteId = $created->data()['note']['id'];

        $payload['base_revision_id'] = $this->revisionBaseForTest($noteId);
        $payload['reason'] = 'Downward correction after full payment';
        $payload['idempotency_key'] = 'report-cash-revision';
        $payload['note']['transaction_date'] = '2026-09-13';
        $payload['items'][0]['service']['price_rupiah'] = 100000;
        $payload['inline_payment'] = ['decision' => 'skip'];
        $this->actingAs($admin)->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $payload)
            ->assertSessionHasNoErrors();

        self::assertSame(300000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(200000, (int) DB::table('note_revision_surplus_refund_payments')->sum('amount_rupiah'));
        self::assertSame(0, DB::table('customer_refunds')->count());
        $result = app(GetTransactionReportDatasetHandler::class)->handle('2026-09-13', '2026-09-13');
        self::assertTrue($result->isSuccess());
        $summary = $result->data()['summary'];
        self::assertSame(100000, $summary['net_cash_collected_rupiah']);
        self::assertSame(100000, $summary['gross_transaction_rupiah']);
        self::assertSame(100000, $summary['allocated_payment_rupiah']);
        self::assertSame(0, $summary['outstanding_rupiah']);
        self::assertSame(200000, $summary['surplus_refund_paid_rupiah']);
    }
}
