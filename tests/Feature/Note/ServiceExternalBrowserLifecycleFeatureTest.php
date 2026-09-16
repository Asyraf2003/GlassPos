<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class ServiceExternalBrowserLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(\App\Ports\Out\ClockPort::class, new class implements \App\Ports\Out\ClockPort
        {
            public function now(): \DateTimeImmutable
            {
                return \DateTimeImmutable::createFromInterface(Carbon::now());
            }
        });
    }


    public function test_catalog_browser_payload_creates_and_reloads_external_service_without_inventory(): void
    {
        Carbon::setTestNow('2026-09-14 09:00:00');
        try {
            $browser = new Process(['node', 'scripts/test-cashier-payment-intent.mjs', 'surface=external'], base_path());
            $browser->mustRun();
            $item = json_decode(trim($browser->getOutput()), true, flags: JSON_THROW_ON_ERROR)['payload'];
            $cashier = $this->loginAsKasir();
            $this->actingAs($cashier)->post(route('notes.workspace.store'), [
                'idempotency_key' => 'external-browser',
                'note' => ['customer_name' => 'External browser', 'transaction_date' => '2026-09-14'],
                'items' => [$item], 'inline_payment' => ['decision' => 'skip'],
            ])->assertSessionHasNoErrors();
            $noteId = (string) DB::table('notes')->value('id');
            $this->assertDatabaseHas('notes', ['id' => $noteId, 'total_rupiah' => 205000]);
            $this->assertDatabaseHas('work_items', ['note_id' => $noteId, 'transaction_type' => 'service_with_external_purchase', 'subtotal_rupiah' => 205000]);
            $this->assertDatabaseHas('work_item_external_purchase_lines', ['cost_description' => 'Bearing NTN', 'line_total_rupiah' => 80000]);
            $this->actingAs($cashier)->get(route('cashier.notes.show', ['noteId' => $noteId]))->assertOk()->assertSee('Servis luar')->assertSee('Bearing NTN');
            $this->actingAs($cashier)->get(route('cashier.notes.workspace.edit', ['noteId' => $noteId]))->assertOk()->assertSee('Servis luar')->assertSee('Bearing NTN');
            self::assertSame(0, DB::table('inventory_movements')->count());
            self::assertSame(0, DB::table('customer_payments')->count());
        } finally { Carbon::setTestNow(); }
    }
}
