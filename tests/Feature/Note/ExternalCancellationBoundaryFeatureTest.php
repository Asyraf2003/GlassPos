<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class ExternalCancellationBoundaryFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function boundaries(): array
    {
        return ['unpaid external' => [false, false], 'paid external' => [true, false], 'paid mixed' => [true, true]];
    }

    #[DataProvider('boundaries')]
    public function test_external_rejection_is_truthful_and_leaves_business_effects_unchanged(bool $paid, bool $mixed): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $items = [$this->primitiveItems()[3]];
        if ($mixed) {
            $items[] = $this->primitiveItems()[0];
        }
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($items, 'external-create'))->assertSessionHasNoErrors();
        $note = (string) DB::table('notes')->value('id');
        if ($paid) {
            $amount = (int) DB::table('notes')->value('total_rupiah');
            $this->post(route('cashier.notes.payments.store', ['noteId' => $note]), $this->primitivePayment('external-pay', $amount, 'transfer'))->assertSessionHasNoErrors();
        }
        $tables = ['notes', 'work_items', 'work_item_external_purchase_lines', 'note_revisions', 'note_revision_lines',
            'customer_payments', 'payment_allocations', 'payment_component_allocations', 'customer_refunds', 'refund_component_allocations',
            'inventory_movements', 'note_history_projection', 'note_mutation_events'];
        $before = array_map(fn ($table) => DB::table($table)->orderBy($table === 'note_history_projection' ? 'note_id' : 'id')->get()->toJson(), $tables);
        $report = json_encode(app(TransactionSummaryReportingQuery::class)->rows('2026-09-15', '2026-09-15'));
        $payload = ['base_revision_id' => DB::table('notes')->value('current_revision_id'), 'idempotency_key' => 'external-cancel', 'reason' => 'Permintaan batal pembelian luar'];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson(route('cashier.notes.cancel', ['noteId' => $note]), $payload)
                ->assertStatus(422)->assertJsonPath('code', 'EXTERNAL_REFUND_REQUIRED')
                ->assertJsonPath('message', 'Transaksi memuat pembelian luar. Pembatalan dan refund pembelian luar belum didukung pada alur ini. Transaksi tidak diubah.');
        }
        $external = (string) DB::table('work_items')->where('transaction_type', 'service_with_external_purchase')->value('id');
        $this->post(route('cashier.notes.refunds.store', ['noteId' => $note]), [
            'selected_row_ids' => [$external], 'stock_returns' => [], 'refunded_at' => '2026-09-15',
            'reason' => 'External refund attempt', 'idempotency_key' => 'external-refund',
        ])->assertSessionHasErrors();
        self::assertSame($before, array_map(fn ($table) => DB::table($table)->orderBy($table === 'note_history_projection' ? 'note_id' : 'id')->get()->toJson(), $tables));
        self::assertSame($report, json_encode(app(TransactionSummaryReportingQuery::class)->rows('2026-09-15', '2026-09-15')));
        self::assertSame(0, DB::table('audit_outbox')->whereIn('event_name', ['note_cancelled', 'selected_rows_refund_plan_recorded'])->count());
    }
}
