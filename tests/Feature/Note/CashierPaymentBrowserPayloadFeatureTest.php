<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierPaymentBrowserPayloadFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    #[DataProvider('scenarios')]
    public function test_browser_payload_persists_the_same_settlement(string $query, int $total, int $intent, ?int $tender): void
    {
        Carbon::setTestNow('2026-09-14 09:00:00');
        try {
            $browser = new Process(['node', 'scripts/test-cashier-payment-intent.mjs', $query], base_path());
            $browser->mustRun();
            $payload = json_decode(trim($browser->getOutput()), true, flags: JSON_THROW_ON_ERROR)['payload'];
            self::assertSame($intent, $payload['paid']);
            $cashier = $this->loginAsKasir();
            $date = '2026-09-14';
            if (str_contains($query, 'surface=detail')) {
                $noteId = 'intent-note';
                $this->seedNoteBase($noteId, 'Browser intent', $date, $total);
                $this->seedWorkItemBase('intent-service', $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, $total);
                $this->seedServiceDetailBase('intent-service', 'Service', $total, ServiceDetail::PART_SOURCE_NONE);
                $this->seedServiceOnlyCurrentRevision($noteId, $noteId.'-r001', 'intent-service', 'Browser intent', $date, $total, 'Service', $total);
                $this->actingAs($cashier)->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), [
                    'selected_row_ids' => [$payload['selected_row_ids[]']],
                    'payment_scope' => $intent < $total ? 'partial' : null,
                    'payment_method' => $payload['method'], 'paid_at' => $date,
                    'amount_paid' => $payload['paid'], 'amount_received' => $payload['received'],
                ])->assertSessionHasNoErrors();
            } else {
                $this->actingAs($cashier)->post(route('notes.workspace.store'), [
                    'idempotency_key' => 'browser-payment-intent',
                    'note' => ['customer_name' => 'Browser intent', 'transaction_date' => $date],
                    'items' => [[
                        'entry_mode' => 'service', 'part_source' => 'none',
                        'service' => ['name' => 'Service', 'price_rupiah' => $total],
                        'product_lines' => [], 'external_purchase_lines' => [],
                    ]],
                    'inline_payment' => [
                        'decision' => $payload['decision'], 'payment_method' => $payload['method'],
                        'paid_at' => $payload['date'], 'amount_paid_rupiah' => $payload['paid'],
                        'amount_received_rupiah' => $payload['received'],
                    ],
                ])->assertSessionHasNoErrors();
                $noteId = (string) DB::table('notes')->value('id');
            }
            self::assertSame(1, DB::table('customer_payments')->count());
            self::assertSame($intent, (int) DB::table('customer_payments')->sum('amount_rupiah'));
            self::assertSame($intent, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
            $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'outstanding_rupiah' => $total - $intent]);
            if ($tender !== null) {
                $this->assertDatabaseHas('customer_payment_cash_details', [
                    'amount_paid_rupiah' => $intent, 'amount_received_rupiah' => $tender, 'change_rupiah' => $tender - $intent,
                ]);
            } else self::assertSame(0, DB::table('customer_payment_cash_details')->count());
            $timeline = app(NoteDetailPageDataBuilder::class)->build($noteId)['note']['payment_timeline'];
            self::assertCount(1, $timeline);
            self::assertSame($intent, $timeline[0]['payment_amount_rupiah']);
            self::assertSame(0, DB::table('inventory_movements')->count());
        } finally { Carbon::setTestNow(); }
    }

    public static function scenarios(): array
    {
        return [
            ['surface=workspace', 780000, 280000, 300000],
            ['surface=detail', 780000, 280000, 300000],
            ['surface=workspace&reopen=1', 780000, 280000, 300000],
            ['surface=detail&reopen=1', 780000, 280000, 300000],
            ['surface=workspace&mode=full&total=480000&tender=500000', 480000, 480000, 500000],
            ['surface=detail&mode=full&total=480000&tender=500000', 480000, 480000, 500000],
            ['surface=workspace&mode=full&total=340&tender=400', 340, 340, 400],
            ['surface=detail&mode=full&total=340&tender=400', 340, 340, 400],
            ['surface=workspace&method=transfer', 780000, 280000, null],
            ['surface=detail&method=transfer', 780000, 280000, null],
            ['surface=simple&tender=280000', 780000, 280000, 280000],
            ['surface=simple&stale=1&tender=280000', 780000, 280000, 280000],
            ['surface=simple&mode=full&total=480000&tender=480000', 480000, 480000, 480000],
        ];
    }
}
