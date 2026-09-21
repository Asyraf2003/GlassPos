<?php

declare(strict_types=1);
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

require dirname(__DIR__, 2).'/vendor/autoload.php';

// Explicitly disposable database only; no migrations or cleanup in this fixture.
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'glasspos_slice12_browser'
    || getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_PORT') !== '3319') {
    throw new RuntimeException('Live proof requires the isolated Slice12 testing database on localhost3319.');
}

final class PrimitiveLifecycleLiveFixture extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;

    public function generate(): void
    {
        $this->setUp();
        self::assertSame(0, DB::table('notes')->count(), 'Start with a clean disposable schema.');
        $this->preparePrimitiveFixture();
        Carbon::setTestNow();
        $cashier = $this->loginAsKasir();
        $cashier->forceFill(['email' => 'slice12@example.test'])->save();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'slice12-live-create');
        // Keep this browser fixture inside the real server cashier date window.
        $create['note']['transaction_date'] = (new DateTimeImmutable('now', new DateTimeZone(config('app.timezone'))))->format('Y-m-d');
        $create['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => $create['note']['transaction_date'], 'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $create)->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $pay = route('cashier.notes.payments.store', ['noteId' => $id]);
        $this->post($pay, $this->primitivePayment('slice12-live-transfer', 89457, 'transfer'))->assertSessionHasNoErrors();
        $revision = array_replace($this->primitiveWorkspace($this->primitiveItems(81258), 'slice12-live-revision'), ['base_revision_id' => $this->revisionBaseForTest($id)]);
        $revision['note']['transaction_date'] = $create['note']['transaction_date'];
        $this->patch(route('cashier.notes.workspace.update', ['noteId' => $id]), $revision)->assertSessionHasNoErrors();
        $manifest = ['note_id' => $id, 'show' => parse_url(route('cashier.notes.show', ['noteId' => $id]), PHP_URL_PATH), 'pay' => parse_url($pay, PHP_URL_PATH), 'refund' => parse_url(route('cashier.notes.refunds.store', ['noteId' => $id]), PHP_URL_PATH)];
        $target = getenv('PRIMITIVE_LIVE_MANIFEST') ?: '/tmp/glasspos-slice12-live.json';
        file_put_contents($target, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo 'Live fixture persisted at A3: '.$target.PHP_EOL;
    }
}

(new PrimitiveLifecycleLiveFixture('generate'))->generate();
