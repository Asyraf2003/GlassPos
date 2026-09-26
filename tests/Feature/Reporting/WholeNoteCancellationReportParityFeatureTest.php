<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class WholeNoteCancellationReportParityFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cancelled_current_exports_are_empty_but_cross_date_cost_history_remains(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsAuthorizedAdmin();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[0]], 'export-create'))->assertSessionHasNoErrors();
        $note = (string) DB::table('notes')->value('id');
        Carbon::setTestNow('2026-09-16 11:00:00');
        $this->postJson(route('admin.notes.cancel', ['noteId' => $note]), [
            'base_revision_id' => DB::table('notes')->value('current_revision_id'), 'idempotency_key' => 'export-cancel', 'reason' => 'Customer cancelled next day',
        ])->assertOk();
        $before = [$this->primitiveRows('inventory_movements'), $this->primitiveRows('note_revisions'), $this->primitiveRows('note_revision_lines')];
        $profit = app(GetOperationalProfitSummaryHandler::class);
        self::assertSame(59163, $profit->handle('2026-09-15', '2026-09-15')->data()['row']['store_stock_cogs_rupiah']);
        self::assertSame(-59163, $profit->handle('2026-09-16', '2026-09-16')->data()['row']['store_stock_cogs_rupiah']);
        self::assertSame(0, $profit->handle('2026-09-15', '2026-09-16')->data()['row']['store_stock_cogs_rupiah']);
        $dataset = app(GetTransactionReportDatasetHandler::class)->handle('2026-09-15', '2026-09-16')->data();
        self::assertSame([], $dataset['rows']);
        self::assertSame(0, $dataset['summary']['gross_transaction_rupiah']);
        self::assertSame(0, $dataset['summary']['outstanding_rupiah']);
        $filters = ['period_mode' => 'custom', 'date_from' => '2026-09-15', 'date_to' => '2026-09-16'];
        $this->get(route('admin.reports.transaction_summary.index', $filters))->assertOk()->assertViewHas('summary', $dataset['summary']);
        $pdf = $this->get(route('admin.reports.transaction_summary.export_pdf', $filters))->assertOk();
        self::assertStringStartsWith('%PDF', $pdf->getContent());
        $excel = $this->get(route('admin.reports.transaction_summary.export_excel', $filters))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'cancellation-export-');
        try {
            file_put_contents($path, $excel->streamedContent());
            $book = IOFactory::load($path);
            self::assertSame(0, $book->getSheetByName('Ringkasan')->getCell('B7')->getValue());
            self::assertSame(1, $book->getSheetByName('Rincian Nota')->getHighestDataRow());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        self::assertSame($before, [$this->primitiveRows('inventory_movements'), $this->primitiveRows('note_revisions'), $this->primitiveRows('note_revision_lines')]);
    }
}
